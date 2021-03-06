<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\GameRound;
use App\Helpers\GameTransaction;
use App\Helpers\Helper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use App\Helpers\ClientRequestHelper;
use App\Helpers\ProviderHelper;
use App\Helpers\TransactionHelper;
use App\Helpers\FreeSpinHelper;
use DB;
use Illuminate\Support\Facades\DB as FacadesDB;

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
            $client_details = ProviderHelper::getClientDetails('token', $request->token);
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
        $startTime =  microtime(true);
        $sha1key = sha1($request->token.''.$this->secretkey);
        if($sha1key == $request->sign){
            $client_details = ProviderHelper::getClientDetails('token', $request->token);
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
                                    "gamelaunch" => "false"
                                ]]
                    )]
                );
                $client_response = json_decode($guzzle_response->getBody()->getContents());
                $client_response_time = microtime(true) - $sendtoclient;
                $sessions =array(
                    "balance"=>round($client_response->playerdetailsresponse->balance * 1000,2)
                );
                $game_details = $this->getInfoPlayerGameRound($request->token);
                $freespin_balance = FreeSpinHelper::getFreeSpinBalance($client_details->player_id,$game_details->game_id);
                if($freespin_balance != null){
                    $sessions =array(
                        "balance"=>round($client_response->playerdetailsresponse->balance * 1000,2),
                        "spins"=>$freespin_balance
                    );
                }
                Helper::saveLog('responseTime(EDP)', 12, json_encode(["type"=>"debitproccess","stating"=>$startTime,"response"=>microtime(true)]), ["response"=>microtime(true) - $startTime,"mw_response"=> microtime(true) - $startTime - $client_response_time,"clientresponse"=>$client_response_time]);
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
        $startTime =  microtime(true);
        //Helper::saveLog('BetGame(EDP)', 2, json_encode($request->getContent()), "BEFORE BET");
        if($request->has("bonusId")){
            $sha1key = sha1($request->amount.''.$request->bonusId.''.$request->date.''.$request->gameId.''.$request->id.''.$request->token.''.$this->secretkey);
            // return $sha1key;
        }
        else{
            $sha1key = sha1($request->amount.''.$request->date.''.$request->gameId.''.$request->id.''.$request->token.''.$this->secretkey);
            //return $sha1key;
        }
        if($sha1key == $request->sign){
            $transaction = TransactionHelper::checkGameTransactionData($request->id);
            if($transaction){
                $transactionData = json_decode($transaction[0]->mw_response);
                $response = array(
                    "transactionId" => $transactionData->transactionId,
                    "balance" => $transactionData->balance
                );
                return response($response,402)
                    ->header('Content-Type', 'application/json');
            }
            $client_details = ProviderHelper::getClientDetails('token', $request->token);
            if($client_details){
                $game_transaction = Helper::checkGameTransaction($request->id);
                $bet_amount = $game_transaction ? 0 : $request->amount;
                $bet_amount = $bet_amount < 0 ? 0 :$bet_amount;
                $game_details = Helper::getInfoPlayerGameRound($request->token);
                $json_data = array(
                    "transid" => $request->id,
                    "amount" => $request->amount / 1000,
                    "roundid" => $request->gameId
                );
                $game = Helper::getGameTransaction($request->token,$request->gameId);
                $checkrefundid = Helper::checkGameTransaction($request->id,$request->gameId,3);
                if(!$game && !$checkrefundid){
                    if($request->has('bonusId')){
                        $gametransactionid=Helper::createGameTransaction('debit', $json_data, $game_details, $client_details,$is_freespin=1);
                    }else{
                        $gametransactionid=Helper::createGameTransaction('debit', $json_data, $game_details, $client_details);
                    } 
                }
                elseif($checkrefundid){
                    $gametransactionid = 0;
                }
                else{
                    $gametransactionid = $game->game_trans_id;
                }
                $transactionId=Helper::createGameTransactionExt($gametransactionid,$request,null,null,null,1);
                $sendtoclient =  microtime(true);
                if($request->has('bonusId')){
                    $client_response = ClientRequestHelper::fundTransfer($client_details,number_format(0  ,2, '.', ''),$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"debit");
                }else{
                    $client_response = ClientRequestHelper::fundTransfer($client_details,number_format($bet_amount/1000,2, '.', ''),$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"debit");
                }
                $client_response_time = microtime(true) - $sendtoclient;
                if(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "200"){
                    if($request->has('bonusId')){
                        $freespin = FreeSpinHelper::updateFreeSpinBalance($request->bonusId);
                        if($freespin !=null){
                            $sessions =array(
                                "transactionId" => $request->id,
                                "balance"=>round($client_response->fundtransferresponse->balance * 1000,2),
                                "bonus" => array(
                                    "id" => $request->bonusId,
                                    "bet" =>  "BONUS",
                                    "win" => "BOUS"
                                ),
                                "spins" => $freespin
                            );
                        }
                        else{
                            $sessions = array(
                                "code" =>"INSUFFICIENT_FUNDS",
                                "message"=>"Player has insufficient funds"
        
                            );
                        }   
                    }
                    else{
                        $sessions =array(
                            "transactionId" => $request->id,
                            "balance"=>round($client_response->fundtransferresponse->balance * 1000,2)
                        );
                    }
                    Helper::updateGameTransactionExt($transactionId,$client_response->requestoclient,$sessions,$client_response);
                    Helper::saveLog('responseTime(EDP)', 12, json_encode(["type"=>"debitproccess","stating"=>$startTime,"response"=>microtime(true)]), ["response"=>microtime(true) - $startTime,"mw_response"=> microtime(true) - $startTime - $client_response_time,"clientresponse"=>$client_response_time]);
                    return response($sessions,200)
                        ->header('Content-Type', 'application/json');
                }
                elseif(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "402"){
                    $response = array(
                        "code" =>"INSUFFICIENT_FUNDS",
                        "message"=>"Player has insufficient funds"

                    );
                    Helper::createGameTransactionExt($gametransactionid,$request,$client_response->requestoclient,$response,$client_response,1);
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
        $startTime =  microtime(true);
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
        elseif($request->has("bonusId")){
            $sha1key = sha1($request->amount.''.$request->bonusId.''.$request->date.''.$request->gameId.''.$request->id.''.$request->token.''.$this->secretkey);
        }
        else{
            $sha1key = sha1($request->amount.''.$request->date.''.$request->gameId.''.$request->id.''.$request->token.''.$this->secretkey);
            $payout_reason = null;
        }
        if($sha1key == $request->sign){
            $client_details = ProviderHelper::getClientDetails('token', $request->token);

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
                $game_details = Helper::getInfoPlayerGameRound($request->token);
                $json_data = array(
                    "transid" => $trans_id,
                    "amount" => $request->amount / 1000,
                    "roundid" => $request->gameId,
                    "payout_reason" => $payout_reason,
                    "win" => $request->has("bonusId")?7:$win
                );
                $game = Helper::getGameTransaction($request->token,$request->gameId);
                if(!$game){
                    $gametransactionid=Helper::createGameTransaction('credit', $json_data, $game_details, $client_details);        
                }
                else{
                    $gameupdate = Helper::updateGameTransaction($game,$json_data,"credit");
                    $gametransactionid = $game->game_trans_id;
                }
                $transactionId=Helper::createGameTransactionExt($gametransactionid,$request,null,null,null,2);
                $sendtoclient =  microtime(true);
                $client_response = ClientRequestHelper::fundTransfer($client_details,number_format($win_amount/1000,2, '.', ''),$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"credit");
                $client_response_time = microtime(true) - $sendtoclient;
                if(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "200"){
                    if($request->has("bonusId")){
                        $freespin = FreeSpinHelper::getFreeSpinBalanceByFreespinId($request->bonusId);
                        $sessions =array(
                            "transactionId" => $trans_id,
                            "balance"=>round($client_response->fundtransferresponse->balance * 1000,2),
                            "spins" => $freespin
                        );
                    }
                    else{
                        $sessions =array(
                        "transactionId" => $trans_id,
                        "balance"=>round($client_response->fundtransferresponse->balance * 1000,2)
                        );
                    } 
                Helper::updateGameTransactionExt($transactionId,$client_response->requestoclient,$sessions,$client_response);
                Helper::saveLog('responseTime(EDP)', 12, json_encode(["type"=>"debitproccess","stating"=>$startTime,"response"=>microtime(true)]), ["response"=>microtime(true) - $startTime,"mw_response"=> microtime(true) - $startTime - $client_response_time,"clientresponse"=>$client_response_time]);
                return response($sessions,200)
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
            $client_details = ProviderHelper::getClientDetails('token', $request->token);
            if($client_details){
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
                $transactionId=Helper::createGameTransactionExt($gametransactionid,$request,null,null,null,3);
                $client_response = ClientRequestHelper::fundTransfer($client_details,number_format($request->amount/1000,2, '.', ''),$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"credit",true);
                
                $sessions =array(
                    "transactionId" => $request->id,
                    "balance"=>round($client_response->fundtransferresponse->balance * 1000,2)
                ); 
                Helper::updateGameTransactionExt($transactionId,$client_response->requestoclient,$sessions,$client_response);
                return response($sessions,200)
                       ->header('Content-Type', 'application/json');
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
    
    public function freeSpin(Request $request){
       $freespin_update = FreeSpinHelper::updateFreeSpinBalance($request->freespin_id,$request->amount);
       dd($freespin_update);
    }

    public function getInfoPlayerGameRound($player_token){
        $games = DB::select("SELECT g.game_name,g.game_id,g.game_code FROM player_game_rounds as pgr JOIN player_session_tokens as pst ON pst.player_token = pgr.player_token JOIN games as g ON g.game_id = pgr.game_id JOIN players as ply ON pst.player_id = ply.player_id WHERE pgr.player_token = '".$player_token."'");
        $count = count($games);
        return $count > 0 ? $games[0] : null;
	}

}

