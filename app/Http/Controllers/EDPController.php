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
     
    // private $edp_endo_url = "http://localhost:8080/api/sessions/seamless/rest/v1";
    // private $nodeid = 777;
    // private $secretkey = "E09A0EF00E5D4B23B169E8548067B8E3";
    private $edp_endo_url = "https://test.endorphina.com/api/sessions/seamless/rest/v1";
    private $nodeid = 1002;
    private $secretkey = "67498C0AD6BD4D2DB8FDFE59BD9039EB";
    public function index(Request $request){
        $sha1key = sha1($request->param.''.$this->secretkey);
        if($sha1key == $request->sign){
            $sha1key = sha1($this->nodeid.''.$request->param.''.$this->secretkey);
            $data2 = array(
                "param" => $request->param,
                "compare" => $sha1key."==".$request->sign,
                "sha1key" => $sha1key,
                "sign" => $request->sign
            );
            Helper::saveLog('AuthPlayer(EDP)', 2, json_encode($data2), "EDP Request");
            $data = array(
                "nodeId" => $this->nodeid,
                "param" => $request->param,
                "sign"=>$sha1key);
            return response($data,200)->header('Content-Type', 'application/json');
        }
        
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
                $profile = "noexit.xml";
                $sha1key = sha1($exiturl.''.$this->nodeid.''.$profile.''.$token.''.$this->secretkey);
                $sign = $sha1key; 
                $gameLunchUrl = $this->edp_endo_url.'?exit='.$exiturl.'&nodeId='.$this->nodeid.'&profile='.$profile.'&token='.$token.'&sign='.$sign;
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
            $client_details = $this->_getClientDetails('token', $request->token);
            $sessions =array(
                "player" => $game->username,
                "currency"=> $client_details->default_currency,
                "game"   => $game->game_code
            );
            $data2 = array(
                "token" => $request->token,
                "sign" => $request->sign
            );
            Helper::saveLog('PlayerSession(EDP)', 2, json_encode($data2), $sessions); 
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
                                    "client_player_id"=>$client_details->client_player_id,
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
                $data2 = array(
                    "token" => $request->token,
                    "sign" => $request->sign
                );
                Helper::saveLog('PlayerSession(EDP)', 2, json_encode($data2), $sessions);
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
        Helper::saveLog('BetGame(EDP)', 2, json_encode($request->getContent()), "BEFORE BET");
        $sha1key = sha1($request->amount.''.$request->date.''.$request->gameId.''.$request->id.''.$request->token.''.$this->secretkey);
        if($sha1key == $request->sign){
            $client_details = $this->_getClientDetails('token', $request->token);
            if($client_details){
                $game_transaction = Helper::checkGameTransaction($request->id);
                $bet_amount = $game_transaction ? 0 : $request->amount;
                $bet_amount = $bet_amount < 0 ? 0 :$bet_amount;
                $game_details = Helper::getInfoPlayerGameRound($request->token);
                $client = new Client([
                    'headers' => [ 
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$client_details->client_access_token
                    ]
                ]);
                $requesttocient = [
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
                          "client_player_id"=>$client_details->client_player_id,
                          "token" => $client_details->player_token
                      ],
                      "fundinfo" => [
                            "gamesessionid" => "",
                            "transactiontype" => "debit",
                            "transferid" => "",
                            "rollback" => "false",
                            "currencycode" => $client_details->currency,
                            "amount" => number_format($bet_amount/1000,2, '.', '') #change here
                      ]
                    ]
                      ];
                    $guzzle_response = $client->post($client_details->fund_transfer_url,
                    ['body' => json_encode($requesttocient)],
                    ['defaults' => [ 'exceptions' => false ]]
                );
                
                $client_response = json_decode($guzzle_response->getBody()->getContents());
                $game_details = Helper::getInfoPlayerGameRound($request->token);
                $json_data = array(
                    "transid" => $request->id,
                    "amount" => $request->amount / 1000,
                    "roundid" => $request->gameId
                );
                $game = Helper::getGameTransaction($request->token,$request->gameId);
                $checkrefundid = Helper::checkGameTransaction($request->id,$request->gameId,3);
                if(!$game && !$checkrefundid){
                    $gametransactionid=Helper::createGameTransaction('debit', $json_data, $game_details, $client_details);        
                }
                elseif($checkrefundid){
                    $gametransactionid = 0;
                }
                else{
                    $gametransactionid = $game->game_trans_id;
                }
                if(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "200"){
                    $sessions =array(
                        "transactionId" => $request->id,
                        "balance"=>round($client_response->fundtransferresponse->balance * 1000,2)
                    );
                    Helper::createGameTransactionExt($gametransactionid,$request,$requesttocient,$sessions,$client_response,1);
                    return response($sessions,200)
                        ->header('Content-Type', 'application/json');
                }
                elseif(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "402"){
                    $response = array(
                        "code" =>"INSUFFICIENT_FUNDS",
                        "message"=>"Player has insufficient funds"
                    );
                    Helper::createGameTransactionExt($gametransactionid,$request,$requesttocient,$response,$client_response,1);
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
        Helper::saveLog("EDP WIN",9,json_encode($request->getContent()),"BEFORE WIN PROCESS");
        if($request->has("progressive")&&$request->has("progressiveDesc")){
            $sha1key = sha1($request->amount.''.$request->date.''.$request->gameId.''.$request->id.''.$request->progressive.''.$request->progressiveDesc.''.$request->token.''.$this->secretkey);
            $payout_reason = "Jackpot for the Game Round ID ".$request->gameId;
            
        }
        elseif($request->amount == 0){
            $sha1key = sha1($request->amount.''.$request->date.''.$request->gameId.''.$request->token.''.$this->secretkey);
            $payout_reason = "Win 0 for the Game Round ID ".$request->gameId;
           //Helper::saveLog("debit",9,json_encode($request->getContent()),"WIN");
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
                    //Helper::saveLog("credit",9,json_encode($request->getContent()),$getgametransaction);
                }
                
                
                $client = new Client([
                    'headers' => [ 
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$client_details->client_access_token
                    ]
                ]);
                $requesttocient = [
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
                          "client_player_id"=>$client_details->client_player_id,
                          "token" => $client_details->player_token
                      ],
                      "fundinfo" => [
                            "gamesessionid" => "",
                            "transactiontype" => "credit",
                            "transferid" => "",
                            "rollback" => "false",
                            "currencycode" => $client_details->currency,
                            "amount" => number_format($win_amount/1000,2, '.', '')
                      ]
                    ]
                      ];
                try{
                    $guzzle_response = $client->post($client_details->fund_transfer_url,
                    ['body' => json_encode(
                            $requesttocient
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
                $game = Helper::getGameTransaction($request->token,$request->gameId);
                if(!$game){
                    $gametransactionid=Helper::createGameTransaction('credit', $json_data, $game_details, $client_details);        
                }
                else{
                    $gameupdate = Helper::updateGameTransaction($game,$json_data,"credit");
                    $gametransactionid = $game->game_trans_id;
                }
                
                $sessions =array(
                    "transactionId" => $trans_id,
                    "balance"=>round($client_response->fundtransferresponse->balance * 1000,2)
                ); 
                Helper::createGameTransactionExt($gametransactionid,$request,$requesttocient,$sessions,$client_response,2);
                //Helper::saveGame_trans_ext($gametransactionid,json_encode(array("mw_response"=>$sessions,"type"=>"AFTERWIN")));
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
        Helper::saveLog('Refund(EDP)', 2, json_encode($request->getContent()), "all refund");
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
                $requesttocient = [
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
                          "client_player_id"=>$client_details->client_player_id,
                          "token" => $client_details->player_token
                      ],
                      "fundinfo" => [
                            "gamesessionid" => "",
                            "transactiontype" => "credit",
                            "transferid" => "",
                            "rollback" => "false",
                            "currencycode" => $client_details->currency,
                            "amount" => number_format($request->amount/1000,2, '.', '')
                      ]
                    ]
                      ];
                try{
                    $guzzle_response = $client->post($client_details->fund_transfer_url,
                    ['body' => json_encode(
                            $requesttocient
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
                $game = Helper::getGameTransaction($request->token,$request->gameId);
                if($game){
                    $gameupdate = Helper::updateGameTransaction($game,$json_data,"refund");
                    $gametransactionid = $game->game_trans_id;        
                }
                else{
                    $gametransactionid=0;
                }
                $sessions =array(
                    "transactionId" => $request->id,
                    "balance"=>round($client_response->fundtransferresponse->balance * 1000,2)
                ); 
                Helper::createGameTransactionExt($gametransactionid,$request,$requesttocient,$sessions,$client_response,3);
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
				 ->select('p.client_id', 'p.player_id','p.client_player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.token_id', 'pst.player_token' , 'pst.status_id', 'p.display_name','c.default_currency', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
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
