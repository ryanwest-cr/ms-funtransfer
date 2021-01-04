<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ClientRequestHelper;
use App\Helpers\EVGHelper;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use DB;
class EvolutionController extends Controller
{
    //

    public function authentication(Request $request){
        if($request->has("authToken")&& $request->authToken == config("providerlinks.evolution.owAuthToken")){
            $data = json_decode($request->getContent(),TRUE);
            $client_details = ProviderHelper::getClientDetails("player_id",$data["userId"]);
            if($client_details){
                // $client_response=ClientRequestHelper::playerDetailsCall($client_details->player_token);
                $msg = array(
                    "status"=>"OK",
                    "sid" => $data["sid"],
                    "uuid"=>$data["uuid"],
                );
                return response($msg,200)->header('Content-Type', 'application/json');
            }
            else{
                $msg = array(
                    "status"=>"INVALID_PARAMETER",
                    "uuid"=>$data["uuid"],
                );
                return response($msg,200)->header('Content-Type', 'application/json');
            }
        }
        else{
            $data = json_decode($request->getContent(),TRUE);
            $msg = array(
                "status"=>"INVALID_TOKEN_ID",
                "uuid"=>$data["uuid"],
            );
            return response($msg,200)->header('Content-Type', 'application/json');
        }
    }
    public function sid(Request $request){
        if($request->has("authToken")&& $request->authToken == config("providerlinks.evolution.owAuthToken")){
            $data = json_decode($request->getContent(),TRUE);
            $client_details = ProviderHelper::getClientDetails("player_id",$data["userId"]);
            if($client_details){
                // $client_response=ClientRequestHelper::playerDetailsCall($client_details->player_token);
                $msg = array(
                    "status"=>"OK",
                    "sid" => substr("abcdefghijklmnopqrstuvwxyz1234567890", mt_rand(0, 25), 1).substr(md5(time()), 1),
                    "uuid"=>$data["uuid"],
                );
                return response($msg,200)->header('Content-Type', 'application/json');
            }
            else{
                $msg = array(
                    "status"=>"INVALID_PARAMETER",
                    "uuid"=>$data["uuid"],
                );
                return response($msg,200)->header('Content-Type', 'application/json');
            }
        }
        else{
            $data = json_decode($request->getContent(),TRUE);
            $msg = array(
                "status"=>"INVALID_TOKEN_ID",
                "uuid"=>$data["uuid"],
            );
            return response($msg,200)->header('Content-Type', 'application/json');
        }
    }
    public function balance(Request $request){
        if($request->has("authToken")&& $request->authToken == config("providerlinks.evolution.owAuthToken")){
            $data = json_decode($request->getContent(),TRUE);
            $client_details = ProviderHelper::getClientDetails("player_id",$data["userId"]);
            if($client_details){
                // $client_response=ClientRequestHelper::playerDetailsCall($client_details->player_token);
                // $msg = array(
                //     "status"=>"OK",
                //     "balance" => (float)number_format($client_response->playerdetailsresponse->balance,2,'.', ''),
                //     "uuid"=>$data["uuid"],
                // );

                $msg = array(
                    "status"=>"OK",
                    "balance" => (float)number_format($client_details->balance,2,'.', ''),
                    "uuid"=>$data["uuid"],
                );
                return response($msg,200)->header('Content-Type', 'application/json');
            }
            else{
                $msg = array(
                    "status"=>"INVALID_PARAMETER",
                    "uuid"=>$data["uuid"],
                );
                return response($msg,200)->header('Content-Type', 'application/json');
            }
        }
        else{
            $data = json_decode($request->getContent(),TRUE);
            $msg = array(
                "status"=>"INVALID_TOKEN_ID",
                "uuid"=>$data["uuid"],
            );
            return response($msg,200)->header('Content-Type', 'application/json');
        }
    }
    public function debit(Request $request){
        $startTime =  microtime(true);
        if($request->has("authToken")&& $request->authToken == config("providerlinks.evolution.owAuthToken")){
            $data = json_decode($request->getContent(),TRUE);
            $client_details = $this->getClientDetails("player_id",$data["userId"]);
            if($client_details){
                $game_transaction = $this->checkGameTransaction($data["transaction"]["id"]);
                if($game_transaction){
                    $msg = array(
                        "status"=>"BET_ALREADY_EXIST",
                        "uuid"=>$data["uuid"],
                    );
                    return response($msg,200)->header('Content-Type', 'application/json');
                }
                else{
                    $bet_amount = $game_transaction ? 0 : round($data["transaction"]["amount"],2);
                    $bet_amount = $bet_amount < 0 ? 0 :$bet_amount;
                    if(config("providerlinks.evolution.env") == 'test'){
                        $game_details = EVGHelper::getGameDetails($data["game"]["details"]["table"]["id"],$data["game"]["type"],config("providerlinks.evolution.env"));
                    }
                    if(config("providerlinks.evolution.env") == 'production'){
                        $game_details = EVGHelper::getGameDetails($data["game"]["details"]["table"]["id"],null,config("providerlinks.evolution.env"));
                    }
                    $json_data = array(
                        "transid" => $data["transaction"]["id"],
                        "amount" => round($data["transaction"]["amount"],2),
                        "roundid" => $data["transaction"]["refId"]
                    );
                    $game = $this->getGameTransaction($client_details->player_token,$data["transaction"]["refId"]);
                    if(!$game){
                        $gametransactionid=EVGHelper::createGameTransaction('debit', $json_data, $game_details, $client_details); 
                    }
                    else{
                        $gametransactionid= $game->game_trans_id;
                    }
                    if(!$game_transaction){
                        $transactionId=EVGHelper::createEVGGameTransactionExt($gametransactionid,$data,null,null,null,1);
                    }
                    
                    $msg = array(
                        "status"=>"OK",
                        "balance"=>$client_details->balance-round($data["transaction"]["amount"],2),
                        "uuid"=>$data["uuid"],
                    );

					$action_payload = [
						"type" => "custom", #genreral,custom :D # REQUIRED!
						"custom" => [
							"provider" => 'evolution',
						],
						"provider" => [
							"provider_request" => $data, #R
							"provider_trans_id"=>$data["transaction"]["id"], #R
							"provider_round_id"=>$data["transaction"]["refId"], #R
						],
						"mwapi" => [
							"roundId"=>$gametransactionid, #R
							"type"=>2, #R
							"game_id" => $game_details->game_id, #R
							"player_id" => $client_details->player_id, #R
							"mw_response" => $msg, #R
						]
					];

                    $sendtoclient =  microtime(true);  
                    // $client_response = ClientRequestHelper::fundTransfer_TG($client_details,round($data["transaction"]["amount"],2),$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"credit");
            		$client_response = ClientRequestHelper::fundTransfer_TG($client_details,round($data["transaction"]["amount"],2),$game_details->game_code,$game_details->game_name,$gametransactionid,'debit',false,$action_payload);
                    $client_response_time = microtime(true) - $sendtoclient;
                    if(isset($client_response->fundtransferresponse->status->code) 
                    && $client_response->fundtransferresponse->status->code == "200"){
                        ProviderHelper::_insertOrUpdate($client_details->token_id, $client_response->fundtransferresponse->balance);
                        $msg = array(
                            "status"=>"OK",
                            "balance"=>$client_details->balance-round($data["transaction"]["amount"],2),
                            "uuid"=>$data["uuid"],
                        );
                        //Helper::updateGameTransactionExt($transactionId,$client_response->requestoclient,$msg,$client_response);
                        //Helper::saveLog('responseTime(EVG)', 12, json_encode(["type"=>"debitproccess","stating"=>$startTime,"response"=>microtime(true)]), ["response"=>microtime(true) - $startTime,"mw_response"=> microtime(true) - $startTime - $client_response_time,"clientresponse"=>$client_response_time]);
                        return response($msg,200)
                            ->header('Content-Type', 'application/json');
                    }
                    elseif(isset($client_response->fundtransferresponse->status->code) 
                    && $client_response->fundtransferresponse->status->code == "402"){
                        $game = $this->getGameTransaction($client_details->player_token,$data["transaction"]["refId"]);
                        Helper::updateGameTransaction($game,$json_data,"fail");
                        $msg = array(
                            "status"=>"INSUFFICIENT_FUNDS",
                            "uuid"=>$data["uuid"],
                        );
                        Helper::updateGameTransactionExt($transactionId,$client_response->requestoclient,$msg,$client_response);
                        return response($msg,200)
                        ->header('Content-Type', 'application/json');
                    }
                }
            }
            else{
                $msg = array(
                    "status"=>"INVALID_PARAMETER",
                    "uuid"=>$data["uuid"],
                );
                return response($msg,200)->header('Content-Type', 'application/json');
            }
        }
        else{
            $data = json_decode($request->getContent(),TRUE);
            $msg = array(
                "status"=>"INVALID_TOKEN_ID",
                "uuid"=>$data["uuid"],
            );
            return response($msg,200)->header('Content-Type', 'application/json');
        }
    }


    public function credit(Request $request){
        $startTime =  microtime(true);
        if($request->has("authToken")&& $request->authToken == config("providerlinks.evolution.owAuthToken")){
            $data = json_decode($request->getContent(),TRUE);
            $client_details = $this->getClientDetails("player_id",$data["userId"]);
            if($client_details){
                $game_transaction = $this->checkGameTransaction($data["transaction"]["id"]);
                if($game_transaction){
                    $msg = array(
                        "status"=>"BET_ALREADY_SETTLED",
                        "uuid"=>$data["uuid"],
                    );
                    return response($msg,200)->header('Content-Type', 'application/json');
                }
                else{
                    $win = $data["transaction"]["amount"] == 0 ? 0 : 1;
                    if(config("providerlinks.evolution.env") == 'test'){
                        $game_details = EVGHelper::getGameDetails($data["game"]["details"]["table"]["id"],$data["game"]["type"],config("providerlinks.evolution.env"));
                    }
                    if(config("providerlinks.evolution.env") == 'production'){
                        $game_details = EVGHelper::getGameDetails($data["game"]["details"]["table"]["id"],null,config("providerlinks.evolution.env"));
                    }
                    $json_data = array(
                        "transid" => $data["transaction"]["id"],
                        "amount" => round($data["transaction"]["amount"],2),
                        "roundid" => $data["transaction"]["refId"],
                        "payout_reason" => null,
                        "win" => $win,
                    );
                    $game = $this->getGameTransactionbyround($data["transaction"]["refId"]);
                    if(!$game){
                        //$gametransactionid=Helper::createGameTransaction('credit', $json_data, $game_details, $client_details); 
                        $msg = array(
                            "status"=>"BET_DOES_NOT_EXIST",
                            "uuid"=>$data["uuid"],
                        );
                        return response($msg,200)->header('Content-Type', 'application/json');
                    }
                    else{
                        if($win == 0){
                            $gameupdate = Helper::updateGameTransaction($game,$json_data,"debit");
                        }else{
                            $gameupdate = Helper::updateGameTransaction($game,$json_data,"credit");
                        }
                        $gametransactionid = $game->game_trans_id;
                    }
                    // $transactionId= EVGHelper::createEVGGameTransactionExt($gametransactionid,$data,null,null,null,2);


                    $msg = array(
                        "status"=>"OK",
                        "balance"=>$client_details->balance+round($data["transaction"]["amount"],2),
                        "uuid"=>$data["uuid"],
                    );

					$action_payload = [
						"type" => "custom", #genreral,custom :D # REQUIRED!
						"custom" => [
							"provider" => 'evolution',
						],
						"provider" => [
							"provider_request" => $data, #R
							"provider_trans_id"=>$data["transaction"]["id"], #R
							"provider_round_id"=>$data["transaction"]["refId"], #R
						],
						"mwapi" => [
							"roundId"=>$gametransactionid, #R
							"type"=>2, #R
							"game_id" => $game_details->game_id, #R
							"player_id" => $client_details->player_id, #R
							"mw_response" => $msg, #R
						]
					];

                    $sendtoclient =  microtime(true);  
                    // $client_response = ClientRequestHelper::fundTransfer_TG($client_details,round($data["transaction"]["amount"],2),$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"credit");
            		$client_response = ClientRequestHelper::fundTransfer_TG($client_details,round($data["transaction"]["amount"],2),$game_details->game_code,$game_details->game_name,$gametransactionid,'credit',false,$action_payload);
                    $client_response_time = microtime(true) - $sendtoclient;
                    $balance = number_format($client_response->fundtransferresponse->balance,2,'.', '');
                    if(isset($client_response->fundtransferresponse->status->code) 
                    && $client_response->fundtransferresponse->status->code == "200"){
                        ProviderHelper::_insertOrUpdate($client_details->token_id, $client_response->fundtransferresponse->balance);
                        $msg = array(
                            "status"=>"OK",
                            "balance"=>(float)$balance,
                            "uuid"=>$data["uuid"],
                        );
                        // Helper::updateGameTransactionExt($transactionId,$client_response->requestoclient,$msg,$client_response);
                        //Helper::saveLog('responseTime(EVG)', 12, json_encode(["type"=>"creditproccess","stating"=>$startTime,"response"=>microtime(true)]), ["response"=>microtime(true) - $startTime,"mw_response"=> microtime(true) - $startTime - $client_response_time,"clientresponse"=>$client_response_time]);
                        return response($msg,200)
                            ->header('Content-Type', 'application/json');
                    }
                }
            }
            else{
                $msg = array(
                    "status"=>"INVALID_PARAMETER",
                    "uuid"=>$data["uuid"],
                );
                return response($msg,200)->header('Content-Type', 'application/json');
            }
        }
        else{
            $data = json_decode($request->getContent(),TRUE);
            $msg = array(
                "status"=>"INVALID_TOKEN_ID",
                "uuid"=>$data["uuid"],
            );
            return response($msg,200)->header('Content-Type', 'application/json');
        }
    }


    // public function credit(Request $request){
    //     $startTime =  microtime(true);
    //     if($request->has("authToken")&& $request->authToken == config("providerlinks.evolution.owAuthToken")){
    //         $data = json_decode($request->getContent(),TRUE);
    //         $client_details = ProviderHelper::getClientDetails("player_id",$data["userId"]);
    //         if($client_details){
    //             $game_transaction = Helper::checkGameTransaction($data["transaction"]["id"]);
    //             if($game_transaction){
    //                 $msg = array(
    //                     "status"=>"BET_ALREADY_SETTLED",
    //                     "uuid"=>$data["uuid"],
    //                 );
    //                 return response($msg,200)->header('Content-Type', 'application/json');
    //             }
    //             else{
    //                 $win = $data["transaction"]["amount"] == 0 ? 0 : 1;
    //                 if(config("providerlinks.evolution.env") == 'test'){
    //                     $game_details = EVGHelper::getGameDetails($data["game"]["details"]["table"]["id"],$data["game"]["type"],config("providerlinks.evolution.env"));
    //                 }
    //                 if(config("providerlinks.evolution.env") == 'production'){
    //                     $game_details = EVGHelper::getGameDetails($data["game"]["details"]["table"]["id"],null,config("providerlinks.evolution.env"));
    //                 }
    //                 $json_data = array(
    //                     "transid" => $data["transaction"]["id"],
    //                     "amount" => round($data["transaction"]["amount"],2),
    //                     "roundid" => $data["transaction"]["refId"],
    //                     "payout_reason" => null,
    //                     "win" => $win,
    //                 );
    //                 $game = $this->getGameTransactionbyround($data["transaction"]["refId"]);
    //                 if(!$game){
    //                     //$gametransactionid=Helper::createGameTransaction('credit', $json_data, $game_details, $client_details); 
    //                     $msg = array(
    //                         "status"=>"BET_DOES_NOT_EXIST",
    //                         "uuid"=>$data["uuid"],
    //                     );
    //                     return response($msg,200)->header('Content-Type', 'application/json');
    //                 }
    //                 else{
    //                     if($win == 0){
    //                         $gameupdate = Helper::updateGameTransaction($game,$json_data,"debit");
    //                     }else{
    //                         $gameupdate = Helper::updateGameTransaction($game,$json_data,"credit");
    //                     }
    //                     $gametransactionid = $game->game_trans_id;
    //                 }
    //                 $transactionId= EVGHelper::createEVGGameTransactionExt($gametransactionid,$data,null,null,null,2);
    //                 $sendtoclient =  microtime(true);  
    //                 $client_response = ClientRequestHelper::fundTransfer($client_details,round($data["transaction"]["amount"],2),$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"credit");
    //                 $client_response_time = microtime(true) - $sendtoclient;
    //                 $balance = number_format($client_response->fundtransferresponse->balance,2,'.', '');
    //                 if(isset($client_response->fundtransferresponse->status->code) 
    //                 && $client_response->fundtransferresponse->status->code == "200"){
    //                     $msg = array(
    //                         "status"=>"OK",
    //                         "balance"=>(float)$balance,
    //                         "uuid"=>$data["uuid"],
    //                     );
    //                     Helper::updateGameTransactionExt($transactionId,$client_response->requestoclient,$msg,$client_response);
    //                     //Helper::saveLog('responseTime(EVG)', 12, json_encode(["type"=>"creditproccess","stating"=>$startTime,"response"=>microtime(true)]), ["response"=>microtime(true) - $startTime,"mw_response"=> microtime(true) - $startTime - $client_response_time,"clientresponse"=>$client_response_time]);
    //                     return response($msg,200)
    //                         ->header('Content-Type', 'application/json');
    //                 }
    //             }
    //         }
    //         else{
    //             $msg = array(
    //                 "status"=>"INVALID_PARAMETER",
    //                 "uuid"=>$data["uuid"],
    //             );
    //             return response($msg,200)->header('Content-Type', 'application/json');
    //         }
    //     }
    //     else{
    //         $data = json_decode($request->getContent(),TRUE);
    //         $msg = array(
    //             "status"=>"INVALID_TOKEN_ID",
    //             "uuid"=>$data["uuid"],
    //         );
    //         return response($msg,200)->header('Content-Type', 'application/json');
    //     }
    // }
    public function cancel(Request $request){
        $startTime =  microtime(true);
        if($request->has("authToken")&& $request->authToken == config("providerlinks.evolution.owAuthToken")){
            $data = json_decode($request->getContent(),TRUE);
            $client_details = ProviderHelper::getClientDetails("player_id",$data["userId"]);
            if($client_details){
                $game_transaction = Helper::checkGameTransaction($data["transaction"]["id"],$data["transaction"]["refId"],3);
                if($game_transaction){
                    $msg = array(
                        "status"=>"BET_ALREADY_SETTLED",
                        "uuid"=>$data["uuid"],
                    );
                    return response($msg,200)->header('Content-Type', 'application/json');
                }
                else{
                    $check_bet_exist = Helper::checkGameTransaction($data["transaction"]["id"],$data["transaction"]["refId"],1);
                    if(!$check_bet_exist){
                        $msg = array(
                            "status"=>"BET_DOES_NOT_EXIST",
                            "uuid"=>$data["uuid"],
                        );
                        return response($msg,200)->header('Content-Type', 'application/json');
                    }
                    else{
                        $win = 0;
                        if(config("providerlinks.evolution.env") == 'test'){
                            $game_details = EVGHelper::getGameDetails($data["game"]["details"]["table"]["id"],$data["game"]["type"],config("providerlinks.evolution.env"));
                        }
                        if(config("providerlinks.evolution.env") == 'production'){
                            $game_details = EVGHelper::getGameDetails($data["game"]["details"]["table"]["id"],null,config("providerlinks.evolution.env"));
                        }
                        $json_data = array(
                            "transid" => $data["transaction"]["id"],
                            "amount" => round($data["transaction"]["amount"],2),
                            "roundid" => $data["transaction"]["refId"],
                        );
                        $game = Helper::getGameTransaction($client_details->player_token,$data["transaction"]["refId"]);
                        if(!$game){
                            $gametransactionid=Helper::createGameTransaction('refund', $json_data, $game_details, $client_details); 
                        }
                        else{
                            $gameupdate = Helper::updateGameTransaction($game,$json_data,"refund");
                            $gametransactionid = $game->game_trans_id;
                        }
                        $transactionId= EVGHelper::createEVGGameTransactionExt($gametransactionid,$data,null,null,null,3); 
                        $sendtoclient =  microtime(true);
                        $client_response = ClientRequestHelper::fundTransfer($client_details,round($data["transaction"]["amount"],2),$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"credit",true);
                        $client_response_time = microtime(true) - $sendtoclient;
                        $balance = number_format($client_response->fundtransferresponse->balance,2,'.', '');
                        if(isset($client_response->fundtransferresponse->status->code) 
                        && $client_response->fundtransferresponse->status->code == "200"){
                            ProviderHelper::_insertOrUpdate($client_details->token_id, $client_response->fundtransferresponse->balance);
                            $msg = array(
                                "status"=>"OK",
                                "balance"=>(float)$balance,
                                "uuid"=>$data["uuid"],
                            );
                            Helper::updateGameTransactionExt($transactionId,$client_response->requestoclient,$msg,$client_response);
                            //Helper::saveLog('responseTime(EVG)', 12, json_encode(["type"=>"creditproccess","stating"=>$startTime,"response"=>microtime(true)]), ["response"=>microtime(true) - $startTime,"mw_response"=> microtime(true) - $startTime - $client_response_time,"clientresponse"=>$client_response_time]);
                            return response($msg,200)
                                ->header('Content-Type', 'application/json');
                        }
                    }
                }
            }
            else{
                $msg = array(
                    "status"=>"INVALID_PARAMETER",
                    "uuid"=>$data["uuid"],
                );
                return response($msg,200)->header('Content-Type', 'application/json');
            }
        }
        else{
            $data = json_decode($request->getContent(),TRUE);
            $msg = array(
                "status"=>"INVALID_TOKEN_ID",
                "uuid"=>$data["uuid"],
            );
            return response($msg,200)->header('Content-Type', 'application/json');
        }
    }
    public function gameLaunch(Request $request){
        return EVGHelper::gameLaunch($request->token,"139.180.159.34",$request->game_code);
    }
    public function internalrefund(Request $request){
        if($request->has("authToken")&& $request->authToken == config("providerlinks.evolution.owAuthToken")){
            $data = json_decode($request->getContent(),TRUE);
            Helper::saveLog('cancelrequest(EVG)', 50, json_encode($data), "cancel");
            $client_details = ProviderHelper::getClientDetails("player_id",$data["userId"]);
            if($client_details){
                $game_transaction = Helper::checkGameTransaction($data["transaction"]["id"],$data["transaction"]["refId"],3);
                if($game_transaction){
                    $msg = array(
                        "status"=>"BET_ALREADY_SETTLED",
                        "uuid"=>$data["uuid"],
                    );
                    return response($msg,200)->header('Content-Type', 'application/json');
                }
                else{
                    $check_bet_exist = Helper::checkGameTransaction($data["transaction"]["id"],$data["transaction"]["refId"],1);
                        $win = 0;
                        if(config("providerlinks.evolution.env") == 'test'){
                            $game_details = EVGHelper::getGameDetails($data["game"]["details"]["table"]["id"],$data["game"]["type"],config("providerlinks.evolution.env"));
                        }
                        if(config("providerlinks.evolution.env") == 'production'){
                            $game_details = EVGHelper::getGameDetails($data["game"]["details"]["table"]["id"],null,config("providerlinks.evolution.env"));
                        }
                        $json_data = array(
                            "transid" => $data["transaction"]["id"],
                            "amount" => round($data["transaction"]["amount"],2),
                            "roundid" => $data["transaction"]["refId"],
                        );
                        if($data["transaction"]["refId"]){
                            $gametransactionid=$data["transaction"]["refId"];
                        }
                        else{
                            $msg = array(
                                "status"=>"INVALID_PARAMETER",
                                "uuid"=>$data["uuid"],
                            );
                            return response($msg,200)->header('Content-Type', 'application/json');
                        }
                        $transactionId= EVGHelper::createEVGGameTransactionExt($gametransactionid,$data,null,null,null,3); 
                        $client_response = ClientRequestHelper::fundTransfer($client_details,round($data["transaction"]["amount"],2),$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"credit",true);
                        $balance = number_format($client_response->fundtransferresponse->balance,2,'.', '');
                        if(isset($client_response->fundtransferresponse->status->code) 
                        && $client_response->fundtransferresponse->status->code == "200"){
                            ProviderHelper::_insertOrUpdate($client_details->token_id, $client_response->fundtransferresponse->balance);
                            $msg = array(
                                "status"=>"OK",
                                "balance"=>(float)$balance,
                                "uuid"=>$data["uuid"],
                            );
                            Helper::updateGameTransactionExt($transactionId,$client_response->requestoclient,$msg,$client_response);
                            return response($msg,200)
                                ->header('Content-Type', 'application/json');
                        }
                }
            }
            else{
                $msg = array(
                    "status"=>"INVALID_PARAMETER",
                    "uuid"=>$data["uuid"],
                );
                return response($msg,200)->header('Content-Type', 'application/json');
            }
        }
        else{
            $data = json_decode($request->getContent(),TRUE);
            $msg = array(
                "status"=>"INVALID_TOKEN_ID",
                "uuid"=>$data["uuid"],
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
				 	])->orderBy('pst.token_id','desc')->limit(1);
				}

				 $result= $query->first();

		return $result;
    }

    public  function getGameTransaction($player_token,$game_round){
        DB::enableQueryLog();
		$game = DB::connection('mysql2')->select("SELECT
						entry_id,bet_amount,game_trans_id,pay_amount
						FROM game_transactions g
						INNER JOIN player_session_tokens USING (token_id)
						WHERE player_token = '".$player_token."' and round_id = '".$game_round."'");
        $result = count($game);
		return $result > 0 ? $game[0] : null;
    }
    public function getGameTransactionbyround($game_round){
		$game = DB::connection('mysql2')->select("SELECT * FROM game_transactions WHERE round_id = '".$game_round."'");
        $result = count($game);
		return $result > 0 ? $game[0] : null;
    }
    /**
	 * GLOBAL
	 * Client Info
	 * @return [Object]
	 * @param $[type] [<token, player_id, site_url, username>]
	 * @param $[value] [<value to be searched>]
	 * 
	 */
    public function getClientDetails($type = "", $value = "", $gg=1, $providerfilter='all') {
    	// DB::enableQueryLog();
	    if ($type == 'token') {
		 	$where = 'where pst.player_token = "'.$value.'"';
		}
		if($providerfilter=='fachai'){
		    if ($type == 'player_id') {
				$where = 'where '.$type.' = "'.$value.'" AND pst.status_id = 1 ORDER BY pst.token_id desc';
			}
		}else{
	        if ($type == 'player_id') {
			   $where = 'where '.$type.' = "'.$value.'"';
			}
        }
		if ($type == 'username') {
		 	$where = 'where p.username = "'.$value.'"';
		}
		if ($type == 'token_id') {
			$where = 'where pst.token_id = "'.$value.'"';
		}
		if($providerfilter=='fachai'){
		 	$filter = 'LIMIT 1';
		}else{
		    // $result= $query->latest('token_id')->first();
		    $filter = 'order by token_id desc LIMIT 1';
		}

		$query = DB::connection('mysql2')->select('select `p`.`client_id`, `p`.`player_id`, `p`.`email`, `p`.`client_player_id`,`p`.`language`, `p`.`currency`, `p`.`test_player`, `p`.`username`,`p`.`created_at`,`pst`.`token_id`,`pst`.`player_token`,`c`.`client_url`,`c`.`default_currency`,`pst`.`status_id`,`p`.`display_name`,`op`.`client_api_key`,`op`.`client_code`,`op`.`client_access_token`,`ce`.`player_details_url`,`ce`.`fund_transfer_url`,`p`.`created_at` from player_session_tokens pst inner join players as p using(player_id) inner join clients as c using (client_id) inner join client_endpoints as ce using (client_id) inner join operator as op using (operator_id) '.$where.' '.$filter.'');

		 $client_details = count($query);
		 // Helper::saveLog('GET CLIENT LOG', 999, json_encode(DB::getQueryLog()), "TIME GET CLIENT");
		 return $client_details > 0 ? $query[0] : null;
    }
    /**
	 * GLOBAL
	 * Client Player Details API Call
	 * @return [Object]
	 * @param $[player_token] [<players token>]
	 * @param $[refreshtoken] [<Default False, True token will be requested>]
	 * 
	 */
	public function playerDetailsCall($player_token, $client_details,$refreshtoken=false, $type=1){
		
		if($client_details){
			$client = new Client([
			    'headers' => [ 
			    	'Content-Type' => 'application/json',
			    	'Authorization' => 'Bearer '.$client_details->client_access_token
			    ]
			]);
			$datatosend = ["access_token" => $client_details->client_access_token,
				"hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
				"type" => "playerdetailsrequest", 
				"datesent" => Helper::datesent(),
                "gameid" => "",
				"clientid" => $client_details->client_id,
				"playerdetailsrequest" => [
					"player_username"=>$client_details->username,
					"client_player_id" => $client_details->client_player_id,
					"token" => $player_token,
					"gamelaunch" => true,
					"refreshtoken" => $refreshtoken
				]
			];

			// Filter Player If Disabled
			$player= DB::connection('mysql2')->table('players')->where('client_id', $client_details->client_id)
					->where('player_id', $client_details->player_id)->first();
			if(isset($player->player_status)){
				if($player != '' || $player != null){
					if($player->player_status == 3){
					Helper::saveLog('ALDEBUG PLAYER BLOCKED = '.$player->player_status,  999, json_encode($datatosend), $datatosend);
					 return 'false';
					}
				}
			}

			// return json_encode($datatosend);
			try{	
				$guzzle_response = $client->post($client_details->player_details_url,
				    ['body' => json_encode($datatosend)]
				);
				$client_response = json_decode($guzzle_response->getBody()->getContents());
				Helper::saveLog('ALDEBUG REQUEST SEND = '.$player_token,  99, json_encode($client_response), $datatosend);
				if(isset($client_response->playerdetailsresponse->status->code) && $client_response->playerdetailsresponse->status->code != 200 || $client_response->playerdetailsresponse->status->code != '200'){
					if($refreshtoken == true){
						if(isset($client_response->playerdetailsresponse->refreshtoken) &&
					    $client_response->playerdetailsresponse->refreshtoken != false || 
					    $client_response->playerdetailsresponse->refreshtoken != 'false'){
							DB::table('player_session_tokens')->insert(
	                        array('player_id' => $client_details->player_id, 
	                        	  'player_token' =>  $client_response->playerdetailsresponse->refreshtoken, 
	                        	  'status_id' => '1')
	                        );
						}
					}
					// Helper::saveLog('PLAYER DETAILS LOG', 999, json_encode(DB::getQueryLog()), "TIME PLAYERDETAILS");
					return 'false';
				}else{
					if($refreshtoken == true){
						if(isset($client_response->playerdetailsresponse->refreshtoken) &&
					    $client_response->playerdetailsresponse->refreshtoken != false || 
					    $client_response->playerdetailsresponse->refreshtoken != 'false'){
							DB::table('player_session_tokens')->insert(
		                        array('player_id' => $client_details->player_id, 
		                        	  'player_token' =>  $client_response->playerdetailsresponse->refreshtoken, 
		                        	  'status_id' => '1')
		                    );
						}
					}
					// Helper::saveLog('PLAYER DETAILS LOG', 999, json_encode(DB::getQueryLog()), "TIME PLAYERDETAILS");
			 		return $client_response;
				}

            }catch (\Exception $e){
               // Helper::saveLog('PLAYER DETAILS LOG', 999, json_encode(DB::getQueryLog()), "TIME PLAYERDETAILS");
               Helper::saveLog('ALDEBUG client_player_id = '.$client_details->client_player_id,  99, json_encode($datatosend), $e->getMessage());
               return 'false';
            }
		}else{
			// Helper::saveLog('PLAYER DETAILS LOG', 999, json_encode(DB::getQueryLog()), "TIME PLAYERDETAILS");
			// Helper::saveLog('ALDEBUG Token Not Found = '.$player_token,  99, json_encode($datatosend), 'TOKEN NOT FOUND');
			return 'false';
		}
    }
    public function checkGameTransaction($provider_transaction_id,$round_id=false,$type=false){
		if($type&&$round_id){
			// $game = DB::table('game_transaction_ext')
			// 	->where('provider_trans_id',$provider_transaction_id)
			// 	->where('round_id',$round_id)
			// 	->where('game_transaction_type',$type)
			// 	->first();
			$game = DB::connection('mysql2')->select("SELECT game_transaction_type
								FROM game_transaction_ext
								WHERE round_id = '".$round_id."' AND provider_trans_id='".$provider_transaction_id."' AND game_transaction_type = ".$type."");
		}
		else{
			$game = DB::connection('mysql2')->select("SELECT game_trans_ext_id
			FROM game_transaction_ext
			where provider_trans_id='".$provider_transaction_id."' limit 1");
		}
		return $game ? true :false;
	}
}
