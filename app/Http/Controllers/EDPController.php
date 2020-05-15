<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\GameRound;
use App\Helpers\GameTransaction;
use App\Helpers\Helper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use DB;
class EDPController extends Controller
{
     
    private $edp_endo_url = "https://test.endrophina.com/api/sessions/seamless/rest/v1";
    private $nodeid = 1002;
    private $secretkey = "67498C0AD6BD4D2DB8FDFE59BD9039EB";
    public function index(Request $request){
        $sha1key = sha1($request->param.''.$this->secretkey);
        return response(array("sign"=>$sha1key),200)->header('Content-Type', 'application/json');
    } 
    //this is just for testing
    public function gameLaunchUrl(Request $request){
        if($request->has('token')&&$request->has('client_id')
        &&$request->has('client_player_id')
        &&$request->has('username')
        &&$request->has('email')
        &&$request->has('display_name')
        &&$request->has('game_code')){
            if($token=Helper::checkPlayerExist($request->client_id,$request->client_player_id,$request->username,$request->email,$request->display_name,$request->token,$request->game_code))
            {
                
                $exiturl = "http://demo.freebetrnk.com/";
                $sha1key = sha1($exiturl.''.$this->nodeid.''.$token.''.$this->secretkey);
                $sign = $sha1key; 
                $gameLunchUrl = $this->edp_endo_url.'?exit='.$exiturl.'&nodeId='.$this->nodeid.'&token='.$token.'&sign='.$sign;
                Helper::savePLayerGameRound($request->game_code,$token);
                return array(
                    "url"=>$gameLunchUrl,
                    "game_lunch"=>true,
                );
                
            }
            
        }
        else{
            $response = array(
                "error_code"=>"BAD_REQUEST",
                "error_message"=> "request is invalid/missing a required input"
            );
            return response($response,400)
               ->header('Content-Type', 'application/json');
        }
        
    }
    public function playerSession(Request $request){
        $sha1key = sha1($request->token.''.$this->secretkey);
        if($sha1key == $request->sign){
            $game = Helper::getInfoPlayerGameRound($request->token);
            $sessions =array(
                "player" => $game->username,
                "currency"=> "USD",
                "game"   => $game->game_code
            ); 
            return response($sessions,200)
                   ->header('Content-Type', 'application/json');
        }
        else{
            $response = array(
                "error_code"=>"ACCESS_DENIED",
                "error_message"=> "request is invalid/missing a required input"
            );
            return response($response,401)
               ->header('Content-Type', 'application/json');
        }
    }
    public function getBalance(Request $request){
        $sha1key = sha1($request->token.''.$this->secretkey);
        if($sha1key == $request->sign){
            $client_details = $this->_getClientDetails('token', $request->token);
            if($client_details){
                $client = new Client([
                    'headers' => [ 
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$client_details->client_access_token
                    ]
                ]);
                
                $guzzle_response = $client->post($client_details->player_details_url,
                    ['body' => json_encode(
                            [
                                "access_token" => $client_details->client_access_token,
                                "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
                                "type" => "playerdetailsrequest",
                                "datesent" => "",
                                "gameid" => "",
                                "clientid" => $client_details->client_id,
                                "playerdetailsrequest" => [
                                    "token" => $client_details->player_token,
                                    "gamelaunch" => "false"
                                ]]
                    )]
                );

                $client_response = json_decode($guzzle_response->getBody()->getContents());
                // return (array)$client_response;
                $sessions =array(
                    "balance"=>round($client_response->playerdetailsresponse->balance * 1000,2)
                ); 
                return response($sessions,200)
                       ->header('Content-Type', 'application/json');
            }
        }
        else{
            $response = array(
                "code"=>"ACCESS_DENIED",
                "message"=> "request is invalid/missing a required input"
            );
            return response($response,401)
               ->header('Content-Type', 'application/json');
        }
    }
    public function betGame(Request $request){
        $sha1key = sha1($request->amount.''.$request->date.''.$request->gameId.''.$request->id.''.$request->token.''.$this->secretkey);
        if($sha1key == $request->sign){
            $client_details = $this->_getClientDetails('token', $request->token);
            if($client_details){
                $game_transaction = Helper::checkGameTransaction($request->id);
                $bet_amount = $game_transaction ? 0 : $request->amount;
                $bet_amount = $bet_amount < 1000 ? 0 :$bet_amount;
                $client = new Client([
                    'headers' => [ 
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$client_details->client_access_token
                    ]
                ]);
                
                
                    $guzzle_response = $client->post($client_details->fund_transfer_url,
                    ['body' => json_encode(
                            [
                              "access_token" => $client_details->client_access_token,
                              "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
                              "type" => "fundtransferrequest",
                              "datetsent" => "",
                              "gamedetails" => [
                                "gameid" => "",
                                "gamename" => ""
                              ],
                              "fundtransferrequest" => [
                                    "playerinfo" => [
                                    "token" => $client_details->player_token
                                ],
                                "fundinfo" => [
                                      "gamesessionid" => "",
                                      "transactiontype" => "debit",
                                      "transferid" => "",
                                      "rollback" => "false",
                                      "currencycode" => $client_details->currency,
                                      "amount" => "-".round($bet_amount/1000,2)
                                ]
                              ]
                            ]
                    )],
                    ['defaults' => [ 'exceptions' => false ]]
                );

                $client_response = json_decode($guzzle_response->getBody()->getContents());
                $game_details = Helper::getInfoPlayerGameRound($request->token);
                $json_data = array(
                    "transid" => $request->id,
                    "amount" => $request->amount / 1000,
                    "roundid" => $request->gameId
                );
                if(!$game_transaction){
                    $gametransactionid=Helper::createGameTransaction('debit', $json_data, $game_details, $client_details);
                    Helper::saveGame_trans_ext($gametransactionid,json_encode(array("provider_request"=>$request->getContent(),"type"=>"BET")));
                }
                if(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "200"){
                    $sessions =array(
                        "transactionId" => $request->id,
                        "balance"=>round($client_response->fundtransferresponse->balance * 1000,2)
                    ); 
                    return response($sessions,200)
                        ->header('Content-Type', 'application/json');
                }
                elseif(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "402"){
                    $response = array(
                        "code" =>"INSUFFICIENT_FUNDS",
                        "message"=>"Player has insufficient funds"
                    );
                    return response($response,402)
                    ->header('Content-Type', 'application/json');
                }
            }

        }
        else{
            $response = array(
                "code"=>"ACCESS_DENIED",
                "message"=> "request is invalid/missing a required input"
            );
            return response($response,401)
               ->header('Content-Type', 'application/json');
        }
    }
    public function winGame(Request $request){
        // this is to identify the diff type of win
        //Helper::saveLog("debit",9,json_encode($request->getContent()),"WIN");
        if($request->has("progressive")&&$request->has("progressiveDesc")){
            $sha1key = sha1($request->amount.''.$request->date.''.$request->gameId.''.$request->id.''.$request->progressive.''.$request->progressiveDesc.''.$request->token.''.$this->secretkey);
            $payout_reason = "Jackpot for the Game Round ID ".$request->gameId;
            
        }
        elseif($request->amount == 0){
            $sha1key = sha1($request->amount.''.$request->date.''.$request->gameId.''.$request->token.''.$this->secretkey);
            $payout_reason = "Win 0 for the Game Round ID ".$request->gameId;
           // Helper::saveLog("debit",9,json_encode($request->getContent()),"WIN");
        }
        else{
            $sha1key = sha1($request->amount.''.$request->date.''.$request->gameId.''.$request->id.''.$request->token.''.$this->secretkey);
            $payout_reason = null;
        }
        if($sha1key == $request->sign){
            $client_details = $this->_getClientDetails('token', $request->token);

            GameRound::create($request->gameId, $client_details->token_id);
            if($client_details){
                if($request->amount != 0){
                    $game_transaction = Helper::checkGameTransaction($request->id);
                    $win_amount = $game_transaction ? 0 : $request->amount;
                    $win = 1;
                    $trans_id = $request->id;
                }
                else{
                    $getgametransaction = Helper::getGameTransaction($request->token,$request->gameId);
                    $game_transaction = Helper::checkGameTransaction($getgametransaction->provider_trans_id,$request->gameId,2);
                    $win_amount = 0;
                    $win = 0;
                    $trans_id = $getgametransaction->provider_trans_id;
                }
                
                
                $client = new Client([
                    'headers' => [ 
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$client_details->client_access_token
                    ]
                ]);
                
                try{
                    $guzzle_response = $client->post($client_details->fund_transfer_url,
                    ['body' => json_encode(
                            [
                              "access_token" => $client_details->client_access_token,
                              "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
                              "type" => "fundtransferrequest",
                              "datetsent" => "",
                              "gamedetails" => [
                                "gameid" => "",
                                "gamename" => ""
                              ],
                              "fundtransferrequest" => [
                                    "playerinfo" => [
                                    "token" => $client_details->player_token
                                ],
                                "fundinfo" => [
                                      "gamesessionid" => "",
                                      "transactiontype" => "credit",
                                      "transferid" => "",
                                      "rollback" => "false",
                                      "currencycode" => $client_details->currency,
                                      "amount" => round($win_amount/1000,2)
                                ]
                              ]
                            ]
                    )],
                    ['defaults' => [ 'exceptions' => false ]]
                );

                $client_response = json_decode($guzzle_response->getBody()->getContents());
                $game_details = Helper::getInfoPlayerGameRound($request->token);
                $json_data = array(
                    "transid" => $trans_id,
                    "amount" => $request->amount / 1000,
                    "roundid" => $request->gameId,
                    "payout_reason" => $payout_reason,
                    "win" => $win
                );
                if(!$game_transaction){
                    $gametransactionid=Helper::createGameTransaction('credit', $json_data, $game_details, $client_details);
                    Helper::saveGame_trans_ext($gametransactionid,json_encode(array("provider_request"=>$request->getContent(),"type"=>"WIN")));
                }
                
                $sessions =array(
                    "transactionId" => $trans_id,
                    "balance"=>round($client_response->fundtransferresponse->balance * 1000,2)
                ); 
                return response($sessions,200)
                       ->header('Content-Type', 'application/json');
                }
                catch(ClientException $e){
                  $client_response = $e->getResponse();
                  $response = json_decode($client_response->getBody()->getContents(),True);
                  return response($response,$client_response->getStatusCode())
                   ->header('Content-Type', 'application/json');
                }
            }

        }
        else{
            $response = array(
                "code"=>"ACCESS_DENIED",
                "message"=> "request is invalid/missing a required input"
            );
            return response($response,401)
               ->header('Content-Type', 'application/json');
        }
    }
    public function refundGame(Request $request){
        $sha1key = sha1($request->amount.''.$request->date.''.$request->gameId.''.$request->id.''.$request->token.''.$this->secretkey);
        if($sha1key == $request->sign){
            $game_transaction = Helper::checkGameTransaction($request->id,$request->gameId,1);
            $request->amount = $game_transaction?$request->amount:0;
            $client_details = $this->_getClientDetails('token', $request->token);
            if($client_details){
                $client = new Client([
                    'headers' => [ 
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$client_details->client_access_token
                    ]
                ]);
                
                try{
                    $guzzle_response = $client->post($client_details->fund_transfer_url,
                    ['body' => json_encode(
                            [
                              "access_token" => $client_details->client_access_token,
                              "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
                              "type" => "fundtransferrequest",
                              "datetsent" => "",
                              "gamedetails" => [
                                "gameid" => "",
                                "gamename" => ""
                              ],
                              "fundtransferrequest" => [
                                    "playerinfo" => [
                                    "token" => $client_details->player_token
                                ],
                                "fundinfo" => [
                                      "gamesessionid" => "",
                                      "transactiontype" => "credit",
                                      "transferid" => "",
                                      "rollback" => "false",
                                      "currencycode" => $client_details->currency,
                                      "amount" => round($request->amount/1000,2)
                                ]
                              ]
                            ]
                    )],
                    ['defaults' => [ 'exceptions' => false ]]
                );

                $client_response = json_decode($guzzle_response->getBody()->getContents());
                $game_details = Helper::getInfoPlayerGameRound($request->token);
                $json_data = array(
                    "transid" => $request->id,
                    "amount" => $request->amount / 1000,
                    "roundid" => $request->gameId
                );
                if($game_transaction){
                    $gametransactionid=Helper::createGameTransaction('refund', $json_data, $game_details, $client_details);
                    Helper::saveGame_trans_ext($gametransactionid,json_encode(array("provider_request"=>$request->getContent(),"type"=>"REFUND")));
                }
                
                
                
                $sessions =array(
                    "transactionId" => $request->id,
                    "balance"=>round($client_response->fundtransferresponse->balance * 1000,2)
                ); 
                return response($sessions,200)
                       ->header('Content-Type', 'application/json');
                }
                catch(ClientException $e){
                  $client_response = $e->getResponse();
                  $response = json_decode($client_response->getBody()->getContents(),True);
                  return response($response,$client_response->getStatusCode())
                   ->header('Content-Type', 'application/json');
                }
            }
        }  
    }
    public function endGameSession(Request $request){
        return response([],200)
        ->header('Content-Type', 'application/json');
    }
    private function _getClientDetails($type = "", $value = "") {

		$query = DB::table("clients AS c")
				 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.token_id', 'pst.player_token' , 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
				 ->leftJoin("players AS p", "c.client_id", "=", "p.client_id")
				 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
				 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
				 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id");
				 
				if ($type == 'token') {
					$query->where([
				 		["pst.player_token", "=", $value],
				 		["pst.status_id", "=", 1]
				 	]);
				}

				if ($type == 'player_id') {
					$query->where([
				 		["p.player_id", "=", $value],
				 		["pst.status_id", "=", 1]
				 	]);
				}

				 $result= $query->first();

		return $result;

	}

}
