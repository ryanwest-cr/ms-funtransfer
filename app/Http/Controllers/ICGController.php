<?php

namespace App\Http\Controllers;


use App\Models\PlayerDetail;
use App\Models\PlayerSessionToken;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use App\Helpers\GameTransaction;
use App\Helpers\GameSubscription;
use App\Helpers\GameRound;
use App\Helpers\Game;
use App\Helpers\ClientRequestHelper;
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

        $response = $http->get('https://admin-stage.iconic-gaming.com/service/api/v1/games?type=all&lang=zh', [
            'headers' =>[
                'Authorization' => 'Bearer '.$this->index(),
                'Accept'     => 'application/json' 
            ]
        ]);
       //  $data = array();
       //  $games = json_decode((string) $response->getBody(), true);
       //  foreach($games["data"] as $game){
       //      if($game["type"]=="fish"){
       //          $type_id = 9;
       //      }
       //      elseif($game["type"]=="slot"){
       //          $type_id = 1;
       //      }
       //      elseif($game["type"]=="card"){
       //          $type_id = 4;
       //      }
       //      $game_data = array(
       //          "game_type_id" => $type_id,
       //          "provider_id" => 12,
       //          "sub_provider_id" => 1,
       //     "game_name" => $game["name"],
       //      "icon" => $game["src"]["image_s"],
       //       "game_code" => $game["productId"]
       //   );
       //    array_push($data,$game_data);
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
                                "url" => $game["href"].'&token='.$token.'&lang=zh&home_URL=http://demo.freebetrnk.com/icgaming',
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
            $client_details = ProviderHelper::getClientDetails('token', $request->token);
            Helper::saveLog('AuthPlayer(ICG)', 12, json_encode(array("token"=>$request->token)), $client_details);
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
                                    "player_username"=>$client_details->username,
                                    "client_player_id"=>$client_details->client_player_id,
                                    "token" => $client_details->player_token,
                                    "gamelaunch" => "true"
                                ]]
                    )]
                );
                $client_response = json_decode($guzzle_response->getBody()->getContents());
                Helper::saveLog('AuthPlayer(ICG)', 12, json_encode(array("token"=>$request->token)), $client_response);
                $balance = round($client_response->playerdetailsresponse->balance*100,2);
                $msg = array(
                    "data" => array(
                        "statusCode" => 0,
                        "username" => $client_details->username,
                        "balance" => $balance,
                        "hash" => md5($this->changeSecurityCode($client_details->default_currency).$client_details->username."".$balance),
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
            $client_details = ProviderHelper::getClientDetails('token', $request->token);
            //Helper::saveLog('PlayerDetails(ICG)', 12, json_encode(array("token"=>$request->token)), $client_details);
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
                                    "player_username"=>$client_details->username,
                                    "client_player_id"=>$client_details->client_player_id,
                                    "token" => $client_details->player_token,
                                    "gamelaunch" => "false"
                                ]]
                    )]
                );
                $client_response = json_decode($guzzle_response->getBody()->getContents());
                Helper::saveLog('PlayerBalance(ICG)AfterClient', 12, json_encode(array("token"=>$request->token)), $client_response);
                $balance = round($client_response->playerdetailsresponse->balance*100,2);
                $msg = array(
                    "data" => array(
                        "statusCode" => 0,
                        "username" => $client_details->username,
                        "balance" => $balance,
                        "hash" => md5($this->changeSecurityCode($client_details->default_currency).$client_details->username."".$balance),
                    ),
                );
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
            $client_details = ProviderHelper::getClientDetails('token', $json["token"]);
            if($client_details){
                $game_transaction = Helper::checkGameTransaction($json["transactionId"]);
                if(Helper::getBalance($client_details) < round($json["amount"]/100,2)){
                    $response =array(
                        "data" => array(
                            "statusCode"=>2,
                            "username" => $client_details->username,
                            "balance" =>round(Helper::getBalance($client_details)*100,2),
                        ),
                        "error" => array(
                            "title"=> "Not Enough Balance",
                            "description"=>"Not Enough Balance"
                        )
                    ); 
                    Helper::saveLog('betGameInsuficientN(ICG)', 12, json_encode($json), $response);
                    return response($response,400)
                    ->header('Content-Type', 'application/json');
                }
                //Changes Starts here create the transaction and update later if the client already response with something
                $json_data = array(
                    "transid" => $json["transactionId"],
                    "amount" => round($json["amount"]/100,2),
                    "roundid" => $json["roundId"]
                );
                $game_details = Helper::getInfoPlayerGameRound($json["token"]);
                Helper::saveLog('checkGamedetails(ICG)', 12, json_encode($game_details), $game_details);
                $game = Helper::getGameTransaction($request->token,$request->gameId);
                if(!$game){
                    $gametransactionid=Helper::createGameTransaction('debit', $json_data, $game_details, $client_details); 
                }
                $transactionId = Helper::createICGGameTransactionExt($gametransactionid,$json,null,null,null,1);
                $client_response = ClientRequestHelper::fundTransfer($client_details,round($json["amount"]/100,2),$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"debit");
                $balance = round($client_response->fundtransferresponse->balance * 100,2);
                //End
                if(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "200"){
                    $response =array(
                        "data" => array(
                            "statusCode"=>0,
                            "username" => $client_details->username,
                            "balance" =>$balance,
                            "hash" => md5($this->changeSecurityCode($client_details->default_currency).$client_details->username."".$balance),
                        ),
                    );
                    $transactionId = Helper::updateICGGameTransactionExt($transactionId,$client_response->requestoclient,$response,$client_response);
                    return response($response,200)
                        ->header('Content-Type', 'application/json');
                }
                elseif(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "402"){
                    $response =array(
                        "data" => array(
                            "statusCode"=>2,
                            "username" => $client_details->username,
                            "balance" =>$balance,
                        ),
                        "error" => array(
                            "title"=> "Not Enough Balance",
                            "description"=>"Not Enough Balance"
                        )
                    ); 
                    Helper::saveLog('betGameInsuficient(ICG)', 12, json_encode($json), $response);
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
            $client_details = ProviderHelper::getClientDetails('token', $json["token"]);
            if($client_details){
                //$game_transaction = Helper::checkGameTransaction($json["transactionId"]);
                $win = $json["amount"] == 0 ? 0 : 1;
                $game_details = Helper::getInfoPlayerGameRound($json["token"]);
                $json_data = array(
                    "transid" => $json["transactionId"],
                    "amount" => round($json["amount"]/100,2),
                    "roundid" => $json["roundId"],
                    "payout_reason" => null,
                    "win" => $win,
                );
                $game = Helper::getGameTransaction($request->token,$json["transactionId"]);
                if(!$game){
                    $gametransactionid=Helper::createGameTransaction('credit', $json_data, $game_details, $client_details); 
                }
                else{
                    $gameupdate = Helper::updateGameTransaction($game,$json_data,"credit");
                    $gametransactionid = $game->game_trans_id;
                }
                $transactionId=Helper::createICGGameTransactionExt($gametransactionid,$json,null,null,null,2);
                $client_response = ClientRequestHelper::fundTransfer($client_details,round($json["amount"]/100,2),$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"credit");
                $balance = round($client_response->fundtransferresponse->balance * 100,2);
                if(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "200"){
                    
                    $response =array(
                        "data" => array(
                            "statusCode"=>0,
                            "username" => $client_details->username,
                            "balance" =>$balance,
                            "hash" => md5($this->changeSecurityCode($client_details->default_currency).$client_details->username."".$balance),
                        ),
                    );
                    Helper::updateICGGameTransactionExt($transactionId,$client_response->requestoclient,$response,$client_response);  
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
				 ->select('p.client_id', 'p.player_id', 'p.client_player_id','p.username', 'p.email', 'p.language', 'p.currency', 'pst.token_id', 'pst.player_token' , 'pst.status_id', 'p.display_name','c.default_currency', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
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
    public function withdraw(Request $request){
        $json = json_decode($request->getContent(),TRUE);
        if($json["token"]){
            $client_details = ProviderHelper::getClientDetails('token', $json["token"]);
            if($client_details){
                //$game_transaction = Helper::checkGameTransaction($json["transactionId"]);
                
                $win = $json["amount"] == 0 ? 0 : 1;
                $game_details = Helper::getInfoPlayerGameRound($json["token"]);
                $json_data = array(
                    "transid" => $json["transactionId"],
                    "amount" => round($json["amount"]/100,2),
                    "roundid" => 0,
                    "payout_reason" => null,
                    "win" => $win,
                );
                $game = Helper::getGameTransaction($request->token,0);
                if(!$game){
                    $gametransactionid=Helper::createGameTransaction('credit', $json_data, $game_details, $client_details); 
                }
                else{
                    $gameupdate = Helper::updateGameTransaction($game,$json_data,"credit");
                    $gametransactionid = $game->game_trans_id;
                }
                $transactionId=Helper::createICGGameTransactionExt($gametransactionid,$json,null,null,null,2);
                $client_response = ClientRequestHelper::fundTransfer($client_details,round($json["amount"]/100,2),$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"credit");
                $balance = round($client_response->fundtransferresponse->balance * 100,2);
                
                if(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "200"){
                    
                    $response =array(
                        "data" => array(
                            "statusCode"=>0,
                            "username" => $client_details->username,
                            "balance" =>$balance,
                            "hash" => md5($this->changeSecurityCode($client_details->default_currency).$client_details->username."".$balance),
                        ),
                    );
                    Helper::updateICGGameTransactionExt($transactionId,$client_response->requestoclient,$response,$client_response);
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
    public function deposit(Request $request){
        $json = json_decode($request->getContent(),TRUE);
        if($json["token"]){
            $client_details = ProviderHelper::getClientDetails('token', $json["token"]);
            if($client_details){
                $game_transaction = Helper::checkGameTransaction($json["transactionId"]);
                $game_details = Helper::getInfoPlayerGameRound($json["token"]);
                $json_data = array(
                    "transid" => $json["transactionId"],
                    "amount" => round($json["amount"]/100,2),
                    "roundid" => 0
                );
                $game = Helper::getGameTransaction($request->token,0);
                if(!$game){
                    $gametransactionid=Helper::createGameTransaction('debit', $json_data, $game_details, $client_details); 
                    // $game_transaction_id=Helper::createGameTransaction('debit', $json_data, $game_details, $client_details);
                    // Helper::saveGame_trans_ext($game_transaction_id,json_encode($json));
                    // Helper::saveLog('betGame(ICG)', 12, json_encode($json), $response);
                }
                else{
                    $json_data = array(
                        "amount" => round($json["amount"]/100,2),
                    );
                    $gameupdate = Helper::updateGameTransaction($game,$json_data,"debit");
                    $gametransactionid = $game->game_trans_id;
                }
                $transactionId=Helper::createICGGameTransactionExt($gametransactionid,$json,null,null,null,1);
                $client_response = ClientRequestHelper::fundTransfer($client_details,round($json["amount"]/100,2),$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"debit");
                $balance = round($client_response->fundtransferresponse->balance * 100,2);
                
                if(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "200"){
                    
                    $response =array(
                        "data" => array(
                            "statusCode"=>0,
                            "username" => $client_details->username,
                            "balance" =>$balance,
                            "hash" => md5($this->changeSecurityCode($client_details->default_currency).$client_details->username."".$balance),
                        ),
                    );
                    Helper::saveLog('PlaySonLaunch(ICG)', 12, json_encode($client_response), $client_response);
                    Helper::updateICGGameTransactionExt($transactionId,$client_response->requestoclient,$response,$client_response); 
                    return response($response,200)
                        ->header('Content-Type', 'application/json');
                }
                elseif(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "402"){
                    $response =array(
                        "data" => array(
                            "statusCode"=>2,
                            "username" => $client_details->username,
                            "balance" =>1,
                        ),
                        "error" => array(
                            "title"=> "Not Enough Balance",
                            "description"=>"Not Enough Balance"
                        )
                    ); 
                    Helper::saveLog('betGameInsuficientN(ICG)', 12, json_encode($json), $response);
                    return response($response,400)
                    ->header('Content-Type', 'application/json');
                }
                

            }
            else{
                $response =array(
                    "data" => array(
                        "statusCode"=>3,
                    ),
                    "error" => array(
                        "title"=> "Something Wrong In Parameters",
                        "description"=>"Something Wrong In Parameters"
                    )
                ); 
                return response($response,400)
                ->header('Content-Type', 'application/json');
            }
        } 
    }
    public function changeSecurityCode($currency){
        if($currency == "USD"){
            return config("providerlinks.icgagents.usdagents.secure_code");
        }
        elseif($currency == "JPY"){
            return config("providerlinks.icgagents.jpyagents.secure_code");
        }
        elseif($currency == "CNY"){
            return config("providerlinks.icgagents.cnyagents.secure_code");
        }
        elseif($currency == "EUR"){
            return config("providerlinks.icgagents.euragents.secure_code");
        }
        elseif($currency == "KRW"){
            return config("providerlinks.icgagents.krwagents.secure_code");
        }
        elseif($currency == "PHP"){
            return config("providerlinks.icgagents.phpagents.secure_code");
        }
        elseif($currency == "THB"){
            return config("providerlinks.icgagents.thbagents.secure_code");
        }
        elseif($currency == "TRY"){
            return config("providerlinks.icgagents.tryagents.secure_code");
        }
        elseif($currency == "TWD"){
            return config("providerlinks.icgagents.twdagents.secure_code");
        }
        elseif($currency == "VND"){
            return config("providerlinks.icgagents.vndagents.secure_code");
        }
    }
}
