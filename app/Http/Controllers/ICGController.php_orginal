<?php

namespace App\Http\Controllers;


use App\Models\PlayerDetail;
use App\Models\PlayerSessionToken;
use App\Helpers\Helper;
use App\Helpers\GameTransaction;
use App\Helpers\GameSubscription;
use App\Helpers\GameRound;
use App\Helpers\Game;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use DB;
class ICGController extends Controller
{
    //
    public function index(){
        $http = new Client();

        $response = $http->post('https://admin-stage.iconic-gaming.com/service/login', [
            'form_params' => [
                'username' => 'betrnk',
                'password' => 'betrnk168!^*',
            ],
        ]);

        return json_decode((string) $response->getBody(), true)["token"];
    }
    public function getGameList(){
        $http = new Client();

        $response = $http->get('https://admin-stage.iconic-gaming.com/service/api/v1/games?type=all&lang=en', [
            'headers' =>[
                'Authorization' => 'Bearer '.$this->index(),
                'Accept'     => 'application/json' 
            ]
        ]);
        // $data = array();
        // $games = json_decode((string) $response->getBody(), true);
        // foreach($games["data"] as $game){
        //     if($game["type"]=="fish"){
        //         $type_id = 9;
        //     }
        //     elseif($game["type"]=="slot"){
        //         $type_id = 1;
        //     }
        //     elseif($game["type"]=="card"){
        //         $type_id = 4;
        //     }
        //     $game_data = array(
        //         "game_type_id" => $type_id,
        //         "provider_id" => 12,
        //         "sub_provider_id" => 1,
        //         "game_name" => $game["name"],
        //         "icon" => $game["src"]["image_s"],
        //         "game_code" => $game["productId"]
        //     );
        //     array_push($data,$game_data);
        // }
        // DB::table('games')->insert($data);
        return json_decode((string) $response->getBody(), true);
    }
    public function gameLaunchURL(Request $request){
        if($request->has('token')&&$request->has('client_id')
        &&$request->has('client_player_id')
        &&$request->has('username')
        &&$request->has('email')
        &&$request->has('display_name')
        &&$request->has('game_code')){
            if($token=Helper::checkPlayerExist($request->client_id,$request->client_player_id,$request->username,$request->email,$request->display_name,$request->token,$request->game_code)){
                
                $game_list =$this->getGameList();
                foreach($game_list["data"] as $game){
                        if($game["productId"] == $request->game_code){
                            Helper::savePLayerGameRound($game["productId"],$token);
                            $msg = array(
                                "url" => $game["href"].'&token='.$token.'&lang=en&home_URL=http://demo.freebetrnk.com/icgaming',
                                "game_launch" => true
                            );
                            return response($msg,200)
                            ->header('Content-Type', 'application/json');
                        }
                    }
            }
        }
        else{
            $msg = array("error"=>"Invalid input or missing input");
            return response($msg,200)
            ->header('Content-Type', 'application/json');
        }
    }
    public function authPlayer(Request $request){
        Helper::saveLog('AuthPlayer(ICG)', 2, json_encode(array("token"=>$request->token)), "test");
        if($request->has("token")){
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
                
                $balance = round($client_response->playerdetailsresponse->balance*100,2);
                $msg = array(
                    "data" => array(
                        "statusCode" => 0,
                        "username" => $client_details->username,
                        "balance" => $balance,
                        "hash" => md5("2c00c099-f32b-4fc1-a69d-661d8c51c6ae".$client_details->username."".$balance),
                    ),
                );
                Helper::saveLog('AuthPlayer(ICG)', 12, json_encode(array("token"=>$request->token)), $client_details);
                return response($msg,200)->header('Content-Type', 'application/json');
            }
            else{
                $msg = array(
                    "data" => array(
                        "statusCode" => 999,
                    ),
                    "error" => array(
                        "title"=> "Undefined Errors",
                        "description"=> "Undefined Errors"
                    )
                );
                return response($msg,400)->header('Content-Type', 'application/json');
            }
        }
        else{
            $msg = array(
                "data" => array(
                    "statusCode" => 1,
                ),
                "error" => array(
                    "title"=>"TOKEM_IS_NULL",
                    "description"=>"Token is nil"
                )
            );
            return response($msg,400)->header('Content-Type', 'application/json');
        }
        
    }
    public function playerDetails(Request $request){
        if($request->has("token")){
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
                $balance = round($client_response->playerdetailsresponse->balance*100,2);
                $msg = array(
                    "data" => array(
                        "statusCode" => 0,
                        "username" => $client_details->username,
                        "balance" => $balance,
                        "hash" => md5("2c00c099-f32b-4fc1-a69d-661d8c51c6ae".$client_details->username."".$balance),
                    ),
                );
                Helper::saveLog('PlayerBalance(ICG)', 12, json_encode(array("token"=>$request->token)), $msg);
                return response($msg,200)->header('Content-Type', 'application/json');
            }
            else{
                $msg = array(
                    "data" => array(
                        "statusCode" => 999,
                    ),
                    "error" => array(
                        "title"=> "Undefined Errors",
                        "description"=> "Undefined Errors"
                    )
                );
                return response($msg,400)->header('Content-Type', 'application/json');
            }
        }
        else{
            $msg = array(
                "data" => array(
                    "statusCode" => 1,
                ),
                "error" => array(
                    "title"=>"TOKEN_IS_NULL",
                    "description"=>"Token is nil"
                )
            );
            return response($msg,400)->header('Content-Type', 'application/json');
        }
    }
    public function betGame(Request $request){
        $json = json_decode($request->getContent(),TRUE);
        if($json["token"]){
            $client_details = $this->_getClientDetails('token', $json["token"]);
            if($client_details){
                $game_transaction = Helper::checkGameTransaction($json["transactionId"]);
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
                                      "amount" => "-".round($json["amount"]/100,2)
                                ]
                              ]
                            ]
                    )],
                    ['defaults' => [ 'exceptions' => false ]]
                );

                $client_response = json_decode($guzzle_response->getBody()->getContents());
                $balance = round($client_response->fundtransferresponse->balance * 100,2);
                $game_details = Helper::getInfoPlayerGameRound($json["token"]);
                $json_data = array(
                    "transid" => $json["transactionId"],
                    "amount" => round($json["amount"]/100,2),
                    "roundid" => $json["roundId"]
                );

                if(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "200"){
                    
                    $response =array(
                        "data" => array(
                            "statusCode"=>0,
                            "username" => $client_details->username,
                            "balance" =>$balance,
                            "hash" => md5("2c00c099-f32b-4fc1-a69d-661d8c51c6ae".$client_details->username."".$balance),
                        ),
                    );
                    if(!$game_transaction){
                        $game_transaction_id=Helper::createGameTransaction('debit', $json_data, $game_details, $client_details);
                        Helper::saveGame_trans_ext($game_transaction_id,json_encode($json));
                        Helper::saveLog('betGame(ICG)', 12, json_encode($json), $response);
                    }  
                    return response($response,200)
                        ->header('Content-Type', 'application/json');
                }
                elseif(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "402"){
                    $response =array(
                        "data" => array(
                            "statusCode"=>1,
                            "username" => $client_details->username,
                            "balance" =>$balance,
                        ),
                        "error" => array(
                            "title"=> "Not Enough Balance",
                            "description"=>"Not Enough Balance"
                        )
                    ); 
                    return response($response,400)
                    ->header('Content-Type', 'application/json');
                }
                

            }
        } 
    }
    public function cancelBetGame(Request $request){
        Helper::saveLog('cancelBetGame(ICG)', 12, json_encode($request->getContent()), "cancel");
        return response("",200)
                    ->header('Content-Type', 'application/json');
    }
    public function winGame(Request $request){
        $json = json_decode($request->getContent(),TRUE);
        // Helper::saveLog('winGame(ICG)', 2, json_encode($json), "data");
        if($json["token"]){
            $client_details = $this->_getClientDetails('token', $json["token"]);
            if($client_details){
                //$game_transaction = Helper::checkGameTransaction($json["transactionId"]);
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
                                      "transactiontype" => "credit",
                                      "transferid" => "",
                                      "rollback" => "false",
                                      "currencycode" => $client_details->currency,
                                      "amount" => round($json["amount"]/100,2)
                                ]
                              ]
                            ]
                    )],
                    ['defaults' => [ 'exceptions' => false ]]
                );
                $win = $json["amount"] == 0 ? 0 : 1;
                $client_response = json_decode($guzzle_response->getBody()->getContents());
                $balance = round($client_response->fundtransferresponse->balance * 100,2);
                $game_details = Helper::getInfoPlayerGameRound($json["token"]);
                $json_data = array(
                    "transid" => $json["transactionId"],
                    "amount" => round($json["amount"]/100,2),
                    "roundid" => $json["roundId"],
                    "payout_reason" => null,
                    "win" => $win,
                );
                $game_transaction_id =Helper::createGameTransaction('credit', $json_data, $game_details, $client_details);
                Helper::saveGame_trans_ext($game_transaction_id,json_encode($json));
                Helper::saveLog('winGame(ICG)', 12, json_encode($json), "data");
                if(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "200"){
                    
                    $response =array(
                        "data" => array(
                            "statusCode"=>0,
                            "username" => $client_details->username,
                            "balance" =>$balance,
                            "hash" => md5("2c00c099-f32b-4fc1-a69d-661d8c51c6ae".$client_details->username."".$balance),
                        ),
                    );
                    
                    return response($response,200)
                        ->header('Content-Type', 'application/json');
                }
                elseif(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "402"){
                    $response =array(
                        "data" => array(
                            "statusCode"=>1,
                            "username" => $client_details->username,
                            "balance" =>$balance,
                        ),
                        "error" => array(
                            "title"=> "Not Enough Balance",
                            "description"=>"Not Enough Balance"
                        )
                    ); 
                    return response($response,400)
                    ->header('Content-Type', 'application/json');
                }
                

            }
        } 
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
