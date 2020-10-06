<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\WazdanHelper;
use GuzzleHttp\Client;
use App\Helpers\ProviderHelper;
use App\Helpers\ClientRequestHelper;
use DB;
class WazdanController extends Controller
{
    //
    public function hashCode(Request $request){
        $operator = "tigergames";
        $license = "curacao";
        $key = "uTDVNr4wu6Y78SNbr36bqsSCH904Rcn1";
        $data = array(
            "how" => 'hash_hmac("sha256","'.$request->getContent().'",'.$key.')',
            "hmac"=>hash_hmac("sha256",$request->getContent(),$key)
        );
        return $data;
    }
    public function authenticate(Request $request){
        $data = $request->getContent();
        $datadecoded = json_decode($data,TRUE);
        if($datadecoded["token"]){
            $client_details = ProviderHelper::getClientDetails('token', $request->token);
            Helper::saveLog('AuthPlayer (Wazdan)', 50, $data, $client_details);
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
                Helper::saveLog('AuthPlayer(Wazdan)', 12, $data, $client_response);
                $balance = round($client_response->playerdetailsresponse->balance,2);
                $msg = array(
                    "status" => 0,
                    "user"=> array(
                        "id" => $client_details->player_id,
                        "currency" => $client_details->default_currency,
                    ),
                    "funds" => array(
                        "balance" => $balance
                    ),
                );
                return response($msg,200)->header('Content-Type', 'application/json');
            }
            else{
                $msg = array(
                    "status" =>1,
                    "message" => array(
                        "text"=>"This Session Already expired!",
                        "choices"=>array(
                            array(
                                "label" => "Go Back to Game List",
                                "action" => "close_game",
                                "response" => "quit"
                            )
                        )
                    )
                );
                return response($msg,200)->header('Content-Type', 'application/json');
            }
        }
        else{
            $msg = array(
                "status" =>1,
                "message" => array(
                    "text"=>"This Session Already expired please relaunch the game again",
                    "choices"=>array(
                        array(
                            "label" => "Go Back to Game List",
                            "action" => "close_game",
                            "response" => "quit"
                        )
                    )
                )
            );
            return response($msg,200)->header('Content-Type', 'application/json');
        }
    }
    public function getStake(Request $request){
        $data = $request->getContent();
        $datadecoded = json_decode($data,TRUE);
        Helper::saveLog('getStake(Wazdan)', 50, $data, "Initialize");
        if($datadecoded["user"]["token"]){
            $client_details = ProviderHelper::getClientDetails('token', $datadecoded["user"]["token"]);
            if($client_details){
                $game_transaction = Helper::checkGameTransaction($datadecoded["transactionId"]);
                if(Helper::getBalance($client_details) < round($datadecoded["amount"],2)){
                    $msg = array(
                        "status" =>8,
                        "message" => array(
                            "text"=>"Insufficient funds",
                        )
                    ); 
                    Helper::saveLog('betGameInsuficient(Wazdan)', 50, $data, $msg);
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                }
                if(WazdanHelper::gameTransactionExtChecker($datadecoded["transactionId"])){
                    $msg = array(
                        "status" => 0,
                        "funds" => array(
                            "balance" => round(Helper::getBalance($client_details),2)
                        ),
                    );
                    Helper::saveLog('betGameInsuficient(Wazdan)', 50, $data, $msg);
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                }
                $game_details = Helper::getInfoPlayerGameRound($datadecoded["user"]["token"]);
                $json_data = array(
                    "transid" => $datadecoded["transactionId"],
                    "amount" => round($datadecoded["amount"],2),
                    "roundid" => $datadecoded["roundId"]
                );
                $game = WazdanHelper::getGameTransaction($datadecoded["user"]["token"],$datadecoded["roundId"]);
                    if(!$game){
                        $gametransactionid=Helper::createGameTransaction('debit', $json_data, $game_details, $client_details); 
                        // $game_transaction_id=Helper::createGameTransaction('debit', $json_data, $game_details, $client_details);
                        // Helper::saveGame_trans_ext($game_transaction_id,json_encode($json));
                        // Helper::saveLog('betGame(ICG)', 12, json_encode($json), $response);
                    }
                    else{
                        $gameupdate = WazdanHelper::updateGameTransaction($game,$json_data,"debit");
                        $gametransactionid = $game->game_trans_id;
                    }
                    
                $transactionId =WazdanHelper::createWazdanGameTransactionExt($gametransactionid,$datadecoded,null,null,null,1);  
                $client_response = ClientRequestHelper::fundTransfer($client_details,round($datadecoded["amount"],2),$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"debit");
                $balance = round($client_response->fundtransferresponse->balance,2);
                if(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "200"){
                    
                    $msg = array(
                        "status" => 0,
                        "funds" => array(
                            "balance" => $balance
                        ),
                    );
                    Helper::updateGameTransactionExt($transactionId,$client_response->requestoclient,$msg,$client_response);
                    response($msg,200)->header('Content-Type', 'application/json');
                    return response($msg,200)
                        ->header('Content-Type', 'application/json');
                }
                elseif(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "402"){
                    $msg = array(
                        "status" =>8,
                        "message" => array(
                            "text"=>"Insufficient funds",
                        )
                    ); 
                    Helper::saveLog('betGameInsuficient(Wazdan)', 50, $data, $msg);
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                }
                

            }
        }
        else{
            $msg = array(
                "status" =>1,
                "message" => array(
                    "text"=>"This Session not Found",
                )
            ); 
            Helper::saveLog('betGameInsuficient(Wazdan)', 50, $data, $msg);
            return response($msg,200)
            ->header('Content-Type', 'application/json');
        } 
    }
    public function rollbackState(Request $request){
        
        $data = $request->getContent();
        $datadecoded = json_decode($data,TRUE);
        Helper::saveLog('rollbackStake(Wazdan)', 50, $data, "Initialize");
        if($datadecoded["user"]["token"]){
            $client_details = ProviderHelper::getClientDetails('token', $datadecoded["user"]["token"]);
        if($client_details){
            $rollbackchecker = WazdanHelper::gameTransactionExtChecker($datadecoded["transactionId"]);
            if($rollbackchecker){
                $msg = array(
                    "status" => 0,
                    "funds" => array(
                        "balance" => round(Helper::getBalance($client_details),2)
                    )
                );
                Helper::saveLog('refundAlreadyexist(Wazdan)', 50, $data, $msg);
                return response($msg,200)
                ->header('Content-Type', 'application/json');
            }
           
                $win = 0;
                $game_details = Helper::getInfoPlayerGameRound($client_details->player_token);
                $json_data = array(
                    "transid" => $datadecoded["transactionId"],
                    "amount" => round($datadecoded["amount"],2),
                    "roundid" => 0,
                );
                $gameExtension = WazdanHelper::getTransactionExt($datadecoded["originalTransactionId"]);
                if(!$gameExtension){
                    $msg = array(
                        "status" => 0,
                        "funds" => array(
                            "balance" => round(Helper::getBalance($client_details),2)
                        )
                    );
                    Helper::saveLog('refundAlreadyexist(Wazdan)', 50, $data, $msg);
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                }
                $datadecoded["roundId"] = $gameExtension->round_id;
                $game = WazdanHelper::getGameTransactionById($gameExtension->game_trans_id);
                if(!$game){
                    $gametransactionid=Helper::createGameTransaction('refund', $json_data, $game_details, $client_details); 
                }
                else{
                    $gameupdate = Helper::updateGameTransaction($game,$json_data,"refund");
                    $gametransactionid = $game->game_trans_id;

                }
                $transactionId = WazdanHelper::createWazdanGameTransactionExt($gametransactionid,$datadecoded,null,null,null,3);
                $client_response = ClientRequestHelper::fundTransfer($client_details,round($datadecoded["amount"],2),$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"credit",true);
                $balance = round($client_response->fundtransferresponse->balance,2);
                if(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "200"){
                    $msg = array(
                        "status" => 0,
                        "funds" => array(
                            "balance" => $balance
                        ),
                    );
                    Helper::updateGameTransactionExt($transactionId,$client_response->requestoclient,$msg,$client_response);
                    return response($msg,200)
                        ->header('Content-Type', 'application/json');
                }
            }
            else{
                $msg = array(
                    "status" =>1,
                    "message" => array(
                        "text"=>"session not found",
                        "choices"=>array(
                            array(
                                "label" => "Go Back to Game List",
                                "action" => "close_game",
                                "response" => "quit"
                            )
                        )
                    )
                );
                return response($msg,200)->header('Content-Type', 'application/json');
            }
        } 
    }
    public function returnWin(Request $request){
        $data = $request->getContent();
        $datadecoded = json_decode($data,TRUE);
        Helper::saveLog('returnWin(Wazdan)', 50, $data, "Initialize");
        if($datadecoded["user"]["token"]){
            $client_details = ProviderHelper::getClientDetails('token', $datadecoded["user"]["token"]);
            if($client_details){
                $returnWinTransaction = WazdanHelper::gameTransactionExtChecker($datadecoded["transactionId"]);
                if($returnWinTransaction){
                    $msg = array(
                        "status" => 0,
                        "funds" => array(
                            "balance" => round(Helper::getBalance($client_details),2)
                        )
                    );
                    Helper::saveLog('refundAlreadyexist(Wazdan)', 50, $data, $msg);
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                }
                $win = $datadecoded["amount"] == 0 ? 0 : 1;
                $game_details = Helper::getInfoPlayerGameRound($datadecoded["user"]["token"]);
                $json_data = array(
                    "transid" => $datadecoded["transactionId"],
                    "amount" => round($datadecoded["amount"],2),
                    "roundid" => $datadecoded["roundId"],
                    "payout_reason" => null,
                    "win" => $win,
                );
                $game = Helper::getGameTransaction($datadecoded["user"]["token"],$datadecoded["roundId"]);
                if(!$game){
                    $gametransactionid=Helper::createGameTransaction('credit', $json_data, $game_details, $client_details); 
                }
                else{
                    if($win == 0){
                        $gameupdate = Helper::updateGameTransaction($game,$json_data,"debit");
                    }else{
                        $gameupdate = Helper::updateGameTransaction($game,$json_data,"credit");
                    }
                    $gametransactionid = $game->game_trans_id;
                }
                $transactionId= WazdanHelper::createWazdanGameTransactionExt($gametransactionid,$datadecoded,null,null,null,2); 
                $client_response = ClientRequestHelper::fundTransfer($client_details,round($datadecoded["amount"],2),$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"credit");
                $balance = round($client_response->fundtransferresponse->balance,2);
                if(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "200"){
                    $msg = array(
                        "status" => 0,
                        "funds" => array(
                            "balance" => $balance
                        )
                    );
                    Helper::updateGameTransactionExt($transactionId,$client_response->requestoclient,$msg,$client_response);
                    return response($msg,200)
                        ->header('Content-Type', 'application/json');
                }
                else{
                    return "something error with the client";
                }
            }
        } 
    }
    public function gameClose(Request $request){
        $data = $request->getContent();
        $datadecoded = json_decode($data,TRUE);
        $msg = array(
            "status" => 0
        );
        return response($msg,200)
            ->header('Content-Type', 'application/json');
    }
    public function getFunds(Request $request){
        $data = $request->getContent();
        $datadecoded = json_decode($data,TRUE);
        Helper::saveLog('getFund(Wazdan)', 50, $data, "Initialize");
        if($datadecoded["user"]["token"]){
            $client_details = ProviderHelper::getClientDetails('token', $datadecoded["user"]["token"]);
            Helper::saveLog('GetFund (Wazdan)', 50, $data, $client_details);
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
                Helper::saveLog('AuthPlayer(Wazdan)', 12, $data, $client_response);
                $balance = round($client_response->playerdetailsresponse->balance,2);
                $msg = array(
                    "status" => 0,
                    "funds" => array(
                        "balance" => $balance
                    ),
                );
                return response($msg,200)->header('Content-Type', 'application/json');
            }
            else{
                $msg = array(
                    "status" =>1,
                    "message" => array(
                        "text"=>"This Session Already expired!",
                        "choices"=>array(
                            array(
                                "label" => "Go Back to Game List",
                                "action" => "close_game",
                                "response" => "quit"
                            )
                        )
                    )
                );
                return response($msg,200)->header('Content-Type', 'application/json');
            }
        }
        else{
            $msg = array(
                "status" =>1,
                "message" => array(
                    "text"=>"This Session Already expired please relaunch the game again",
                    "choices"=>array(
                        array(
                            "label" => "Go Back to Game List",
                            "action" => "close_game",
                            "response" => "quit"
                        )
                    )
                )
            );
            return response($msg,200)->header('Content-Type', 'application/json');
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
}
