<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AES;
use App\Helpers\FCHelper;
use GuzzleHttp\Client;
use App\Helpers\ClientRequestHelper;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use DB;
class FCController extends Controller
{
    //
    public $provider_db_id=27;
    public function __construct() {
        $this->startTime = microtime(true);
    }
    public function SampleEncrypt(Request $request){
        $data = $request->getContent();

        return array("AESENCRYPT"=>FCHelper::AESEncode($data),"SIGN"=>md5($request->getContent()));
    }
    public function SampleDecrypt(){
        $data = '7Jhu1hCXPmisYLWVGIKhulHfbIWwss8oNfXCdmzP3VPIxJf7ZgYvHBfVPhcec5eo';
        return FCHelper::AESDecode($data);
    }
    public function gameLaunch(Request $request){
        //return FCHelper::addMember(117,1);
        return FCHelper::loginGame(117,21003,1,'https://daddy.betrnk.games/');
    }
    public function transactionMake(Request $request){
        $datareq = FCHelper::AESDecode((string)$request->Params);
        $data = json_decode($datareq,TRUE);
        $duplicatechecker = $this->checkGameTransactionupdate($data["BankID"],1);
        if($duplicatechecker){
            $response =array(
                "Result"=>205,
                "ErrorText" => "Duplicate Transaction ID number",
            );
            return response($response,200)
                ->header('Content-Type', 'application/json');
        }else{
            $client_details = ProviderHelper::getClientDetails("player_id",json_decode($datareq,TRUE)["MemberAccount"],1,'fachai');
            if($client_details){
                $bet_response = $this->_betGame($client_details,$data);
                if(isset($bet_response)&&array_key_exists("ErrorText",$bet_response)){
                    return response($bet_response,200)
                                ->header('Content-Type', 'application/json');
                }else{
                    return $this->_winGame($client_details,$data);
                }
            }
        }
        
    }
    private function _betGame($client_details,$data){
        if($client_details){
            $game_transaction = $this->checkGameTransaction($data["BankID"]);
            $bet_amount = $game_transaction ? 0 : round($data["Bet"],2);
            $bet_amount = $bet_amount < 0 ? 0 :$bet_amount;
            $findGameDetailsinit =  microtime(true);
            $game_details = $this->findGameDetails('game_code', $this->provider_db_id, $data["GameID"]);
            $endfindGameDetailsinit = microtime(true) - $findGameDetailsinit;
            $json_data = array(
                "transid" => $data["BankID"],
                "amount" => $data["Bet"],
                "roundid" => $data["RecordID"]
            );
            $getGameTransactioninit =  microtime(true);
            $game = $this->getGameTransactionupdate($client_details->player_token,$data["RecordID"]);
            $endgetGameTransaction = microtime(true) - $getGameTransactioninit;
            $createGameTransactioninit =  microtime(true);
            if(!$game){
                $gametransactionid=$this->createGameTransaction('debit', $json_data, $game_details, $client_details); 
            }
            else{
                $gametransactionid= $game[0]->game_trans_id;
            }
            if(!$game_transaction){
                $transactionId=$this->createFCGameTransactionExt($gametransactionid,$data,null,null,null,1);
            }
            $endcreateGameTransaction = microtime(true) - $createGameTransactioninit;
            $sendtoclient =  microtime(true);
            $client_response = ClientRequestHelper::fundTransfer($client_details,$data["Bet"],$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"debit");
            $client_response_time = microtime(true) - $sendtoclient;
            $balance = number_format($client_response->fundtransferresponse->balance,2,'.', '');
            //Helper::saveLog('betGamecheck(FC)', 2, json_encode($transactionId), "data");
            if(isset($client_response->fundtransferresponse->status->code) 
            && $client_response->fundtransferresponse->status->code == "200"){
                $response =array(
                    "recordID"=>$data["RecordID"],
                    "balance" =>$balance,
                );
                $updateFCGameTransactionExtinit =  microtime(true);
                $this->updateFCGameTransactionExt($transactionId,$client_response->requestoclient,$response,$client_response);
                $endupdateFCGameTransaction = microtime(true) - $updateFCGameTransactionExtinit;
                Helper::saveLog('responseTime(FC)', 12, json_encode(["type"=>"betsuccess","stating"=>$this->startTime,"response"=>microtime(true)]), ["response"=>microtime(true) - $this->startTime,"clientresponse"=>$client_response_time,"getgameTransaction"=>$endgetGameTransaction,"findgamedetails" =>$endfindGameDetailsinit,"creategameTransaction" => $endcreateGameTransaction,"updategametransaction"=>$endupdateFCGameTransaction]);
                return $response;
            }
            elseif(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "402"){
                    $response =array(
                        "Result"=>203,
                        "ErrorText"=> "Your Cash Balance not enough."
                    );
                Helper::saveLog('responseTime(FC)', 12, json_encode(["type"=>"betinsuficient","stating"=>$this->startTime,"response"=>microtime(true)]), ["response"=>microtime(true) - $this->startTime,"clientresponse"=>$client_response_time]);
                return $response;
            }
        }
    }
    private function _winGame($client_details,$data){
        if($client_details){
            $checkGameTransactioninit =  microtime(true);
            $game_transaction = $this->checkGameTransaction($data["BankID"],$data["RecordID"],2);
            $endcheckGameTransaction = microtime(true) - $checkGameTransactioninit;
            $win_amount = $game_transaction ? 0 : $data["Win"];
            $win_amount = $win_amount < 0 ? 0 :$win_amount;
            $win = $data["Win"] == 0 ? 0 : 1;
            $findGameDetailsinit =  microtime(true);
            $game_details = $this->findGameDetails('game_code', $this->provider_db_id, $data["GameID"]);
            $endfindGameDetailsinit = microtime(true) - $findGameDetailsinit;
            $json_data = array(
                "transid" => $data["BankID"],
                "amount" => $data["Win"],
                "roundid" => $data["RecordID"],
                "payout_reason" => null,
                "win" => $win,
            );
            $getGameTransactioninit =  microtime(true);
            $game = $this->getGameTransactionupdate($client_details->player_token,$data["RecordID"]);
            $endgetGameTransaction = microtime(true) - $getGameTransactioninit;
            $updateGameTransactioninit =  microtime(true);
            if(!$game){
                $gametransactionid=$this->createGameTransaction('credit', $json_data, $game_details, $client_details); 
            }
            else{
                if($win == 0){
                    $gameupdate = $this->updateGameTransaction($game,$json_data,"debit");
                }else{
                    $gameupdate = $this->updateGameTransaction($game,$json_data,"credit");
                }
                $gametransactionid = $game[0]->game_trans_id;
            }
            $endupdateGameTransaction = microtime(true) - $updateGameTransactioninit;
            if(!$game_transaction){
                $transactionId=$this->createFCGameTransactionExt($gametransactionid,$data,null,null,null,2);
            }
            $sendtoclient =  microtime(true);
            $client_response = ClientRequestHelper::fundTransfer($client_details,$win_amount,$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"credit");
            $client_response_time = microtime(true) - $sendtoclient;
            $balance = number_format($client_response->fundtransferresponse->balance,2,'.', '');
            
            if(isset($client_response->fundtransferresponse->status->code) 
            && $client_response->fundtransferresponse->status->code == "200"){
                $response =array(
                    "Result"=>0,
                    "MainPoints" => $balance,
                );
                $updateFCGameTransactionExtinit =  microtime(true);
                $this->updateFCGameTransactionExt($transactionId,$client_response->requestoclient,$response,$client_response);
                $endupdateFCGameTransaction = microtime(true) - $updateFCGameTransactionExtinit;
                Helper::saveLog('responseTime(FC)', 12, json_encode(["type"=>"winsuccess","stating"=>$this->startTime,"response"=>microtime(true)]), ["response"=>microtime(true) - $this->startTime,"clientresponse"=>$client_response_time,"checkGameTransaction"=>$endcheckGameTransaction,"findgamedetails" =>$endfindGameDetailsinit,"getGameTransaction"=>$endgetGameTransaction,"updateGameTransaction"=>$endupdateGameTransaction,"udpateGamtransactionExt"=>$endupdateFCGameTransaction]);
                return response($response,200)
                    ->header('Content-Type', 'application/json');
            }
            else{
                Helper::saveLog('responseTime(FC)', 12, json_encode(["type"=>"winerror","stating"=>$this->startTime,"response"=>microtime(true)]), ["response"=>microtime(true) - $this->startTime,"clientresponse"=>$client_response_time]);
                return "something error with the client";
            }
        }
    }
    public function cancelBet(Request $request){
        $datareq = FCHelper::AESDecode((string)$request->Params);
        $data = json_decode($datareq,TRUE);
        //return $data;
        $client_details = ProviderHelper::getClientDetails("player_id",json_decode($datareq,TRUE)["MemberAccount"],1,'fachai');
        if($client_details){
            $rollbackchecker = $this->checkGameTransaction($data["BankID"]);
            if(!$rollbackchecker){
                $response =array(
                    "Result"=>221,
                    "ErrorText" => "BankID does not exist.",
                );
                Helper::saveLog('responseTime(FC)', 12, json_encode(["type"=>"refundBankIdnotexist","stating"=>$this->startTime,"response"=>microtime(true)]), microtime(true) - $this->startTime);
                return response($response,200)
                    ->header('Content-Type', 'application/json');
            }
            else{
                $duplicatechecker = $this->checkGameTransactionupdate($data["BankID"],3);
                if(!$duplicatechecker){
                    $response =array(
                        "Result"=>205,
                        "ErrorText" => "Duplicate Transaction ID number",
                    );
                    Helper::saveLog('responseTime(FC)', 12, json_encode(["type"=>"refundduplicate","stating"=>$this->startTime,"response"=>microtime(true)]), microtime(true) - $this->startTime);
                    return response($response,200)
                        ->header('Content-Type', 'application/json');
                }else{
                    $game_transaction = $this->checkGameTransactionData($data["BankID"]);
                    $refund_amount = empty($game_transaction) ? 0 : $game_transaction[0]->amount;
                    $refund_amount = $refund_amount < 0 ? 0 :$refund_amount;
                    $win = 0;
                    $game_details = Helper::getInfoPlayerGameRound($client_details->player_token);
                    $json_data = array(
                        "transid" => $data["BankID"],
                        "amount" => round($refund_amount,2),
                        "roundid" => 0,
                    );
                    $game = $this->getGameTransactionupdate($client_details->player_token,$data["BankID"]);
                    if(!$game){
                        $gametransactionid=$this->createGameTransaction('refund', $json_data, $game_details, $client_details); 
                    }
                    else{
                        $gameupdate = $this->updateGameTransaction($game,$json_data,"refund");
                        $gametransactionid = $game[0]->game_trans_id;

                    }
                    if(!empty($game_transaction)){
                        $data["RecordID"]= $game_transaction[0]->round_id;
                        $data["Win"] = $refund_amount;
                        $transactionId=$this->createFCGameTransactionExt($gametransactionid,$data,null,null,null,3);
                    }
                    $sendtoclient =  microtime(true);
                    $client_response = ClientRequestHelper::fundTransfer($client_details,round($refund_amount,2),$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"credit",true);
                    $client_response_time = microtime(true) - $sendtoclient;
                    $balance = number_format($client_response->fundtransferresponse->balance,2,'.', '');
                    
                    if(isset($client_response->fundtransferresponse->status->code) 
                    && $client_response->fundtransferresponse->status->code == "200"){
                        $response =array(
                            "Result"=>0,
                            "MainPoints" => $balance,
                        );
                        $this->updateFCGameTransactionExt($transactionId,$client_response->requestoclient,$response,$client_response);
                        Helper::saveLog('responseTime(FC)', 12, json_encode(["type"=>"refundsuccess","stating"=>$this->startTime,"response"=>microtime(true)]), ["response"=>microtime(true) - $this->startTime,"clientresponse"=>$client_response_time]);
                        return response($response,200)
                            ->header('Content-Type', 'application/json');
                    }
                }
            }
        }
        else{
            $response =array(
                "Result"=>500,
                "ErrorText" => "Player ID not exist.",
            );
            Helper::saveLog('responseTime(FC)', 12, json_encode(["type"=>"refundplayerIdExist","stating"=>$this->startTime,"response"=>microtime(true)]), microtime(true) - $this->startTime); 
            return response($response,200)
            ->header('Content-Type', 'application/json');
        }
    }
    public function getBalance(Request $request){
        if($request->has("Params")){
            $datareq = FCHelper::AESDecode((string)$request->Params);
            $client_details = ProviderHelper::getClientDetails("player_id",json_decode($datareq,TRUE)["MemberAccount"],1,'fachai');
            if($client_details){
                $sendtoclient =  microtime(true);
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
                $client_response_time = microtime(true) - $sendtoclient;
                $msg = array(
                    "Result"=>0,
                    "MainPoints"=>(float)number_format($client_response->playerdetailsresponse->balance,2,'.', '')
                );
                Helper::saveLog('responseTime(FC)', 12, json_encode(["type"=>"getbalance","stating"=>$this->startTime,"response"=>microtime(true)]), ["response"=>microtime(true) - $this->startTime,"clientresponse"=>$client_response_time]);
                return response($msg,200)->header('Content-Type', 'application/json');
            }
            else{
                $msg = array(
                    "Result"=>500,
                    "ErrorText"=>"Account does not exist.",
                );
                Helper::saveLog('responseTime(FC)', 12, json_encode(["type"=>"getbalanceAccountnotexist","stating"=>$this->startTime,"response"=>microtime(true)]), microtime(true) - $this->startTime);
                return response($msg,200)->header('Content-Type', 'application/json');
            }
        }
        else{
            $msg = array(
                "Result"=>500,
                "ErrorText"=>"Account does not exist.",
            );
            Helper::saveLog('responseTime(FC)', 12, json_encode(["type"=>"getbalanceAccountnotexist","stating"=>$this->startTime,"response"=>microtime(true)]), microtime(true) - $this->startTime);
            return response($msg,200)->header('Content-Type', 'application/json');
        }
    }

    private function checkGameTransactionupdate($round_id=false,$type=false){
        $game = DB::select("SELECT game_trans_ext_id
        FROM game_transaction_ext
        where provider_trans_id='".$round_id."' and game_transaction_type = ".$type." limit 1");
    return $game ? true :false;
    }
    private function checkGameTransactionData($provider_transaction_id){
		$game = DB::select("SELECT game_trans_ext_id,mw_response,amount
        FROM game_transaction_ext
        where provider_trans_id='".$provider_transaction_id."' limit 1");
		return $game;
    }
    private function checkGameTransaction($provider_transaction_id,$round_id=false,$type=false){
		if($type&&$round_id){
			// $game = DB::table('game_transaction_ext')
			// 	->where('provider_trans_id',$provider_transaction_id)
			// 	->where('round_id',$round_id)
			// 	->where('game_transaction_type',$type)
			// 	->first();
			$game = DB::select("SELECT game_transaction_type
								FROM game_transaction_ext
								WHERE round_id = '".$round_id."' AND provider_trans_id='".$provider_transaction_id."' AND game_transaction_type = ".$type."");
		}
		else{
			$game = DB::select("SELECT game_trans_ext_id
			FROM game_transaction_ext
			where provider_trans_id='".$provider_transaction_id."' limit 1");
		}
		return $game ? true :false;
    }
    private function createGameTransaction($method, $request_data, $game_data, $client_data){
		$trans_data = [
			"token_id" => $client_data->token_id,
			"game_id" => $game_data->game_id,
			"round_id" => $request_data["roundid"]
		];

		switch ($method) {
			case "debit":
					$trans_data["provider_trans_id"] = $request_data["transid"];
					$trans_data["bet_amount"] = abs($request_data["amount"]);
					$trans_data["win"] = 0;
					$trans_data["pay_amount"] = 0;
					$trans_data["entry_id"] = 1;
					$trans_data["income"] = 0;
				break;
			case "credit":
					$trans_data["provider_trans_id"] = $request_data["transid"];
					$trans_data["bet_amount"] = 0;
					$trans_data["win"] = $request_data["win"];
					$trans_data["pay_amount"] = abs($request_data["amount"]);
					$trans_data["entry_id"] = 2;
					$trans_data["payout_reason"] = $request_data["payout_reason"];
				break;
			case "refund":
					$trans_data["provider_trans_id"] = $request_data["transid"];
					$trans_data["bet_amount"] = 0;
					$trans_data["win"] = 0;
					$trans_data["pay_amount"] = 0;
					$trans_data["entry_id"] = 2;
					$trans_data["payout_reason"] = "Refund of this transaction ID: ".$request_data["transid"]."of GameRound ".$request_data["roundid"];
				break;

			default:
		}
		/*var_dump($trans_data); die();*/
		return DB::table('game_transactions')->insertGetId($trans_data);			
    }
    private function getGameTransactionupdate($player_token,$game_round){
		$game = DB::select("SELECT
						entry_id,bet_amount,game_trans_id
						FROM game_transactions g
						INNER JOIN player_session_tokens USING (token_id)
						WHERE player_token = '".$player_token."' and round_id = '".$game_round."'");
		return $game;
    }
    private function createFCGameTransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response,$game_transaction_type){
		$gametransactionext = array(
			"provider_trans_id" => $provider_request["BankID"],
			"game_trans_id" => $gametransaction_id,
			"round_id" =>$provider_request["RecordID"],
			"amount" =>$game_transaction_type==1?round($provider_request["Bet"],2):round($provider_request["Win"],2),
			"game_transaction_type"=>$game_transaction_type,
			"provider_request" =>json_encode($provider_request),
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gamestransaction_ext_ID;
    }
    private function updateGameTransaction($existingdata,$request_data,$type){
		switch ($type) {
			case "debit":
					$trans_data["win"] = 0;
					$trans_data["pay_amount"] = 0;
					$trans_data["income"]=$existingdata[0]->bet_amount-$request_data["amount"];
					$trans_data["entry_id"] = 1;
				break;
			case "credit":
					$trans_data["win"] = $request_data["win"];
					$trans_data["pay_amount"] = abs($request_data["amount"]);
					$trans_data["income"]=$existingdata[0]->bet_amount-$request_data["amount"];
					$trans_data["entry_id"] = 2;
					$trans_data["payout_reason"] = $request_data["payout_reason"];
				break;
			case "refund":
					$trans_data["win"] = 4;
					$trans_data["pay_amount"] = $request_data["amount"];
					$trans_data["entry_id"] = 2;
					$trans_data["income"]= $existingdata[0]->bet_amount-$request_data["amount"];
					$trans_data["payout_reason"] = "Refund of this transaction ID: ".$request_data["transid"]."of GameRound ".$request_data["roundid"];
				break;
			case "fail":
				$trans_data["win"] = 2;
				$trans_data["pay_amount"] = $request_data["amount"];
				$trans_data["entry_id"] = 1;
				$trans_data["income"]= 0;
				$trans_data["payout_reason"] = "Fail  transaction ID: ".$request_data["transid"]."of GameRound ".$request_data["roundid"] .":Insuffecient Balance";
			break;
			default:
		}
		/*var_dump($trans_data); die();*/
		return DB::table('game_transactions')->where("game_trans_id",$existingdata[0]->game_trans_id)->update($trans_data);
    }
    private function findGameDetails($type,$provider_id,$game_code){
        $query = DB::Select("SELECT game_id,game_code,game_name FROM games WHERE game_code = ".$game_code." AND provider_id = ".$provider_id."");
        $result = count($query);
        return $result > 0 ? $query[0] : null;
    }
    private  function updateFCGameTransactionExt($gametransextid,$mw_request,$mw_response,$client_response){
		$gametransactionext = array(
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		DB::table('game_transaction_ext')->where("game_trans_ext_id",$gametransextid)->update($gametransactionext);
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
