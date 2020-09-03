<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SimpleXMLElement;
use App\Helpers\PNGHelper;
use GuzzleHttp\Client;
use App\Helpers\Helper;
use App\Helpers\ClientRequestHelper;
use DB;
class PNGController extends Controller
{
    //
    public function authenticate(Request $request){
        $data = $request->getContent();
        $xmlparser = new SimpleXMLElement($data);
        $accessToken = "secrettoken";
        if($xmlparser->username){
            $client_details = $this->_getClientDetails('token', $xmlparser->username);
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
                                    "gamelaunch" => true
                                ]]
                    )]
                );
                $client_response = json_decode($guzzle_response->getBody()->getContents());
                Helper::saveLog('AuthPlayer(PNG)', 12, json_encode($xmlparser),$client_response);

                $array_data = array(
                    "externalId" => $client_details->player_id,
                    "statusCode" => 0,
                    "statusMessage" => "ok",
                    "userCurrency" => $client_details->default_currency,
                    "country" => "SE",
                    "birthdate"=> "1990-04-29",
                    "externalGameSessionId" => $xmlparser->username,
                    "real"=> number_format($client_response->playerdetailsresponse->balance,2,'.', ''),
                );
                return PNGHelper::arrayToXml($array_data,"<authenticate/>");
            }
            else{
                $array_data = array(
                    "statusCode" => 4,
                );
                return PNGHelper::arrayToXml($array_data,"<authenticate/>");
            }
        }
        else{
            $array_data = array(
                "statusCode" => 4,
            );
            return PNGHelper::arrayToXml($array_data,"<authenticate/>");
        }
        
    }
    public function reserve(Request $request){
        $data = $request->getContent();
        $xmlparser = new SimpleXMLElement($data);
        $accessToken = "secrettoken";
        if($xmlparser->externalGameSessionId){
            $client_details = $this->_getClientDetails('token', $xmlparser->externalGameSessionId);
            if($client_details){
                $game_transaction = Helper::checkGameTransaction($xmlparser->transactionId);
                if(Helper::getBalance($client_details) < $xmlparser->real){
                    $array_data = array(
                        "real" => round(Helper::getBalance($client_details),2),
                        "statusCode" => 7,
                    );
                    return PNGHelper::arrayToXml($array_data,"<reserve/>");  
                }
                if(PNGHelper::gameTransactionExtChecker($xmlparser->transactionId)){
                    $array_data = array(
                        "real" => round(Helper::getBalance($client_details),2),
                        "statusCode" => 0,
                    );
                    return PNGHelper::arrayToXml($array_data,"<reserve/>");       
                }
                $game_details = Helper::getInfoPlayerGameRound($xmlparser->externalGameSessionId);
                $json_data = array(
                    "transid" => $xmlparser->transactionId,
                    "amount" => (float)$xmlparser->real,
                    "roundid" => $xmlparser->roundId
                );
                $game = Helper::getGameTransaction($xmlparser->externalGameSessionId,$xmlparser->roundId);
                if(!$game){
                    $gametransactionid=Helper::createGameTransaction('debit', $json_data, $game_details, $client_details); 
                }
                else{
                    PNGHelper::updateGameTransaction($game,$xmlparser,'debit');
                    $gametransactionid = $game->game_trans_id;
                }
                $transactionId=PNGHelper::createPNGGameTransactionExt($gametransactionid,$xmlparser,null,null,null,1);
                $client_response = ClientRequestHelper::fundTransfer($client_details,(float)$xmlparser->real,$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"debit");
                $balance = round($client_response->fundtransferresponse->balance,2);
                if(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "200"){
                    
                    $array_data = array(
                        "real" => $balance,
                        "statusCode" => 0,
                    );
                    
                    Helper::updateGameTransactionExt($transactionId,$client_response->requestoclient,$array_data,$client_response);
                    
                    return PNGHelper::arrayToXml($array_data,"<reserve/>");
                }
                elseif(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "402"){
                    $array_data = array(
                        "statusCode" => 7,
                    );
                    return PNGHelper::arrayToXml($array_data,"<reserve/>");
                }
                

            }
            else{
                $array_data = array(
                    "statusCode" => 4,
                );
                return PNGHelper::arrayToXml($array_data,"<authenticate/>");
            }
        } 
    }
    public function release(Request $request){
        $data = $request->getContent();
        $xmlparser = new SimpleXMLElement($data);
        $accessToken = "secrettoken";
        if($xmlparser->externalGameSessionId){
            $client_details = $this->_getClientDetails('token',$xmlparser->externalGameSessionId);
            if($client_details){
                $returnWinTransaction = PNGHelper::gameTransactionExtChecker($xmlparser->transactionId);
                if($returnWinTransaction){
                    $array_data = array(
                        "real" => round(Helper::getBalance($client_details),2),
                        "statusCode" => 0,
                    );
                    return PNGHelper::arrayToXml($array_data,"<release/>");
                }
                $win = $xmlparser->real == 0 ? 0 : 1;
                $game_details = Helper::getInfoPlayerGameRound($xmlparser->externalGameSessionId);
                $json_data = array(
                    "transid" => $xmlparser->transactionId,
                    "amount" => (float)$xmlparser->real,
                    "roundid" => $xmlparser->roundId,
                    "payout_reason" => null,
                    "win" => $win,
                );
                $game = Helper::getGameTransaction($xmlparser->externalGameSessionId,$xmlparser->roundId);
                if(!$game){
                    $gametransactionid=Helper::createGameTransaction('credit', $json_data, $game_details, $client_details); 
                }
                else{
                    //$json_data["amount"] = round($data["args"]["win"],2)+ $game->pay_amount;
                    if($win == 0){
                        $gameupdate = Helper::updateGameTransaction($game,$json_data,"debit");
                    }else{
                        $gameupdate = Helper::updateGameTransaction($game,$json_data,"credit");
                    }
                    $gametransactionid = $game->game_trans_id;
                }
                $transactionId = PNGHelper::createPNGGameTransactionExt($gametransactionid,$xmlparser,null,null,null,2);
                $client_response = ClientRequestHelper::fundTransfer($client_details,(float)$xmlparser->real,$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"credit"); 
                $balance = round($client_response->fundtransferresponse->balance,2);
                if(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "200"){
                    $array_data = array(
                        "real" => $balance,
                        "statusCode" => 0,
                    );
                    Helper::updateGameTransactionExt($transactionId,$client_response->requestoclient,$array_data,$client_response);
                    return PNGHelper::arrayToXml($array_data,"<release/>");
                }
                else{
                    return "something error with the client";
                }
            }
            else{
                $array_data = array(
                    "statusCode" => 4,
                );
                return PNGHelper::arrayToXml($array_data,"<authenticate/>");
            }
        } 
    }
    public function balance(Request $request){
        $data = $request->getContent();
        $xmlparser = new SimpleXMLElement($data);
        $accessToken = "secrettoken";
        if($accessToken != $xmlparser->accessToken){
            $array_data = array(
                "statusCode" => 4,
            );
            return PNGHelper::arrayToXml($array_data,"<balance/>");
        }
        if($xmlparser->externalGameSessionId){
            $client_details = $this->_getClientDetails('token', $xmlparser->externalGameSessionId);
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
                                    "gamelaunch" => true
                                ]]
                    )]
                );
                $client_response = json_decode($guzzle_response->getBody()->getContents());
                Helper::saveLog('AuthPlayer(PNG)', 12, json_encode($xmlparser),$client_response);

                $array_data = array(
                    "statusCode" => 0,
                    "real"=> number_format($client_response->playerdetailsresponse->balance,2,'.', ''),
                );
                return PNGHelper::arrayToXml($array_data,"<balance/>");
            }
            else{
                $array_data = array(
                    "statusCode" => 4,
                );
                return PNGHelper::arrayToXml($array_data,"<balance/>");
            }
        }
    }
    public function cancelReserve(Request $request){
        $data = $request->getContent();
        $xmlparser = new SimpleXMLElement($data);
        $accessToken = "secrettoken";
        if($xmlparser->externalGameSessionId){
            $client_details = $this->_getClientDetails('token', $xmlparser->externalGameSessionId);
        if($client_details){
            $reservechecker = PNGHelper::gameTransactionExtChecker($xmlparser->transactionId);
            $rollbackchecker = PNGHelper::gameTransactionRollbackExtChecker($xmlparser->transactionId,3);
            if($reservechecker==false){
                $array_data = array(
                    "statusCode" => 0,
                    "externalTransactionId"=>""
                );
                Helper::saveLog('refundAlreadyexist(PNG)', 50,json_encode($xmlparser), $array_data);
                return PNGHelper::arrayToXml($array_data,"<cancelReserve/>");
            }
            if($rollbackchecker){
                $array_data = array(
                    "statusCode" => 0,
                    "externalTransactionId"=>$rollbackchecker->game_trans_ext_id
                );
                Helper::saveLog('refundAlreadyexist(PNG)', 50,json_encode($xmlparser), $array_data);
                return PNGHelper::arrayToXml($array_data,"<cancelReserve/>");
            }
                $win = 0;
                $game_details = Helper::getInfoPlayerGameRound($client_details->player_token);
                $json_data = array(
                    "transid" => $xmlparser->transactionId,
                    "amount" => (float)$xmlparser->real,
                    "roundid" => 0,
                );
                $game = Helper::getGameTransaction($xmlparser->externalGameSessionId,$xmlparser->roundId);
                if(!$game){
                    $gametransactionid=Helper::createGameTransaction('refund', $json_data, $game_details, $client_details); 
                }
                else{
                    $gameupdate = Helper::updateGameTransaction($game,$json_data,"refund");
                    $gametransactionid = $game->game_trans_id;

                }
                $transactionId=PNGHelper::createPNGGameTransactionExt($gametransactionid,$xmlparser,null,null,null,3);

                $client_response = ClientRequestHelper::fundTransfer($client_details,(float)$xmlparser->real,$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"credit",true);
                $balance = number_format($client_response->fundtransferresponse->balance,2,'.', '');
                
                if(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "200"){
                    $array_data = array(
                        "statusCode" => 0,
                    );
                    Helper::updateGameTransactionExt($transactionId,$client_response->requestoclient,$array_data,$client_response);
                    return PNGHelper::arrayToXml($array_data,"<cancelReserve/>");
                }
            }
            else{
                $array_data = array(
                    "statusCode" => 4,
                );
                return PNGHelper::arrayToXml($array_data,"<cancelReserve/>");
            }
        } 
    }
    private function _getClientDetails($type = "", $value = "") {

		$query = DB::table("clients AS c")
				 ->select('p.client_id', 'p.player_id', 'p.client_player_id','p.username', 'p.email', 'p.language', 'p.currency','p.created_at', 'pst.token_id', 'pst.player_token' , 'pst.status_id', 'p.display_name','c.default_currency', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
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
