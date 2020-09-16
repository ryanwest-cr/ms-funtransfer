<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ClientRequestHelper;
use App\Helpers\EVGHelper;
use App\Helpers\Helper;
use DB;
class EvolutionController extends Controller
{
    //

    public function authentication(Request $request){
        if($request->has("authToken")&& $request->authToken == config("providerlinks.evolution.owAuthToken")){
            $data = json_decode($request->getContent(),TRUE);
            $client_details = $this->_getClientDetails("player_id",$data["userId"]);
            if($client_details){
                $client_response=ClientRequestHelper::playerDetailsCall($client_details->player_token);
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
            $client_details = $this->_getClientDetails("player_id",$data["userId"]);
            if($client_details){
                $client_response=ClientRequestHelper::playerDetailsCall($client_details->player_token);
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
            $client_details = $this->_getClientDetails("player_id",$data["userId"]);
            if($client_details){
                $client_response=ClientRequestHelper::playerDetailsCall($client_details->player_token);
                $msg = array(
                    "status"=>"OK",
                    "balance" => (float)number_format($client_response->playerdetailsresponse->balance,2,'.', ''),
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
        if($request->has("authToken")&& $request->authToken == config("providerlinks.evolution.owAuthToken")){
            $data = json_decode($request->getContent(),TRUE);
            Helper::saveLog('debitrequest(EVG)', 50, json_encode($data), "debit");
            $client_details = $this->_getClientDetails("player_id",$data["userId"]);
            if($client_details){
                $game_transaction = Helper::checkGameTransaction($data["transaction"]["id"]);
                if($game_transaction){
                    $msg = array(
                        "status"=>"BET_ALREADY_EXIST",
                        "uuid"=>$data["uuid"],
                    );
                    return response($msg,200)->header('Content-Type', 'application/json');
                }
                if(Helper::getBalance($client_details) < round($data["transaction"]["amount"],2)){
                    $msg = array(
                        "status"=>"INSUFFICIENT_FUNDS",
                        "uuid"=>$data["uuid"],
                    );
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                }
                $bet_amount = $game_transaction ? 0 : round($data["transaction"]["amount"],2);
                $bet_amount = $bet_amount < 0 ? 0 :$bet_amount;
                $game_details = EVGHelper::getGameDetails($data["game"]["details"]["table"]["id"],$data["game"]["type"]);
                $json_data = array(
                    "transid" => $data["transaction"]["id"],
                    "amount" => round($data["transaction"]["amount"],2),
                    "roundid" => $data["transaction"]["refId"]
                );
                $game = Helper::getGameTransaction($client_details->player_token,$data["transaction"]["refId"]);
                if(!$game){
                    $gametransactionid=EVGHelper::createGameTransaction('debit', $json_data, $game_details, $client_details); 
                }
                else{
                    $gametransactionid= $game->game_trans_id;
                }
                if(!$game_transaction){
                    $transactionId=EVGHelper::createEVGGameTransactionExt($gametransactionid,$data,null,null,null,1);
                } 
                $client_response = ClientRequestHelper::fundTransfer($client_details,round($data["transaction"]["amount"],2),$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"debit");
                $balance = number_format($client_response->fundtransferresponse->balance,2,'.', '');
                if(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "200"){
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
        if($request->has("authToken")&& $request->authToken == config("providerlinks.evolution.owAuthToken")){
            $data = json_decode($request->getContent(),TRUE);
            Helper::saveLog('creditrequest(EVG)', 50, json_encode($data), "credit");
            $client_details = $this->_getClientDetails("player_id",$data["userId"]);
            if($client_details){
                $game_transaction = Helper::checkGameTransaction($data["transaction"]["id"]);
                if($game_transaction){
                    $msg = array(
                        "status"=>"BET_ALREADY_SETTLED",
                        "uuid"=>$data["uuid"],
                    );
                    return response($msg,200)->header('Content-Type', 'application/json');
                }
                $win = $data["transaction"]["amount"] == 0 ? 0 : 1;
                $game_details = EVGHelper::getGameDetails($data["game"]["details"]["table"]["id"],$data["game"]["type"]);
                $json_data = array(
                    "transid" => $data["transaction"]["id"],
                    "amount" => round($data["transaction"]["amount"],2),
                    "roundid" => $data["transaction"]["refId"],
                    "payout_reason" => null,
                    "win" => $win,
                );
                $game = Helper::getGameTransaction($client_details->player_token,$data["transaction"]["refId"]);
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
                $transactionId= EVGHelper::createEVGGameTransactionExt($gametransactionid,$data,null,null,null,2); 
                $client_response = ClientRequestHelper::fundTransfer($client_details,round($data["transaction"]["amount"],2),$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"credit");
                $balance = number_format($client_response->fundtransferresponse->balance,2,'.', '');
                if(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "200"){
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
    public function cancel(Request $request){
        if($request->has("authToken")&& $request->authToken == config("providerlinks.evolution.owAuthToken")){
            $data = json_decode($request->getContent(),TRUE);
            Helper::saveLog('cancelrequest(EVG)', 50, json_encode($data), "cancel");
            $client_details = $this->_getClientDetails("player_id",$data["userId"]);
            if($client_details){
                $game_transaction = Helper::checkGameTransaction($data["transaction"]["id"],$data["transaction"]["refId"],3);
                if($game_transaction){
                    $msg = array(
                        "status"=>"BET_ALREADY_SETTLED",
                        "uuid"=>$data["uuid"],
                    );
                    return response($msg,200)->header('Content-Type', 'application/json');
                }
                $check_bet_exist = Helper::checkGameTransaction($data["transaction"]["id"],$data["transaction"]["refId"],1);
                if(!$check_bet_exist){
                    $msg = array(
                        "status"=>"BET_DOES_NOT_EXIST",
                        "uuid"=>$data["uuid"],
                    );
                    return response($msg,200)->header('Content-Type', 'application/json');
                }
                $win = 0;
                $game_details = EVGHelper::getGameDetails($data["game"]["details"]["table"]["id"],$data["game"]["type"]);
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
                $client_response = ClientRequestHelper::fundTransfer($client_details,round($data["transaction"]["amount"],2),$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"credit",true);
                $balance = number_format($client_response->fundtransferresponse->balance,2,'.', '');
                if(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "200"){
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
}
