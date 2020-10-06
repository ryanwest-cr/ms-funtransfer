<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\MGHelper;
use GuzzleHttp\Client;
use App\Services\AES;
use App\Helpers\FCHelper;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use Carbon\Carbon;
use App\Helpers\ClientRequestHelper;
use DB;
class MicroGamingController extends Controller
{
    //
    public function launchGame(Request $request){
        $key = "LUGTPyr6u8sRjCfh";
        $aes = new AES($key);
        $player_id = $request->player_id;
        $providerlinks = "https://api-tigergaming.k2net.io/api/v1/agents/Tiger_UPG_USD_MA_Test/players/".$player_id."/sessions";
        $http = new Client();
        $response = $http->post("https://api-tigergaming.k2net.io/api/v1/agents/Tiger_UPG_USD_MA_Test/players/".$player_id."/sessions",[
            'form_params' => [
                'platform' => "desktop",
                'langCode' => "en-EN",//needd to be dynamic
                'contentCode' => "UPG_auroraBeastHunter",//temporary this is the game code
            ]
            ,
            'headers' =>[
                'Authorization' => 'Bearer '.MGHelper::stsTokenizer(),
                'Accept'     => 'application/json' 
            ]
        ]);

        $url = json_decode((string) $response->getBody(), true)["gameURL"];
        $data = array(
            "url" => urlencode($url),
            "token" => $request->token,
            "player_id" => $request->player_id
        );
        $encoded_data = $aes->AESencode(json_encode($data));
        return "https://play.betrnk.games/loadgame/microgaming?param=".urlencode($encoded_data);
    }
    public function getPlayerBalance(Request $request){
        if($request->has("token")){
            $client_details = ProviderHelper::getClientDetails('token', $request->token);
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
                $balance = round($client_response->playerdetailsresponse->balance,2);
                $msg = array(
                    "status" => "ok",
                    "message" => "Balance Request Success",
                    "balance" => $balance
                );
                return response($msg,200)->header('Content-Type', 'application/json');
            }
            else{
                $msg = array(
                    "status" =>"error",
                    "message" => "Token Invalid"
                );
                return response($msg,200)->header('Content-Type', 'application/json');
            }
        }
        else{
            $msg = array(
                "status" =>"error",
                "message" => "Token Invalid"
            );
            return response($msg,200)->header('Content-Type', 'application/json');
        }
        
    }
    public function makeDeposit(Request $request){
        if($request->has("token")&&$request->has("player_id")&&$request->has("amount")){
            $client_details = ProviderHelper::getClientDetails('token', $request->token);
            if($client_details){
                $game_details = Helper::getInfoPlayerGameRound($request->token);
                $json_data = array(
                    "transid" => Carbon::now()->timestamp,
                    "amount" => $request->amount,
                    "roundid" => 0,
                );
            $game = MGHelper::getGameTransaction($request->token,$json_data["roundid"]);
            if(!$game){
                $gametransactionid=Helper::createGameTransaction('debit', $json_data, $game_details, $client_details); 
                // $game_transaction_id=Helper::createGameTransaction('debit', $json_data, $game_details, $client_details);
                // Helper::saveGame_trans_ext($game_transaction_id,json_encode($json));
                // Helper::saveLog('betGame(ICG)', 12, json_encode($json), $response);
            }
            else{
                $gameupdate = MGHelper::updateGameTransaction($game,$json_data,"debit");
                $gametransactionid = $game->game_trans_id;
            }
            $transactionId =MGHelper::createMGGameTransactionExt($gametransactionid,$json_data,null,null,null,1);
            $client_response = ClientRequestHelper::fundTransfer($client_details,$request->amount,$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"debit");
            $balance = round($client_response->fundtransferresponse->balance,2);
                if(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "200"){
                    $http = new Client();
                    $response = $http->post("https://api-tigergaming.k2net.io/api/v1/agents/Tiger_UPG_USD_MA_Test/WalletTransactions",[
                        'form_params' => [
                            'playerId' => $request->player_id,
                            'type' => "deposit",
                            'amount' => $request->amount,
                            'externalTransactionId' => $transactionId,
                        ]
                        ,
                        'headers' =>[
                            'Authorization' => 'Bearer '.MGHelper::stsTokenizer(),
                            'Accept'     => 'application/json' 
                        ]
                    ]);
                    Helper::updateGameTransactionExt($transactionId,$client_response->requestoclient,json_decode($response->getBody()),$client_response);
                    $msg = array(
                        "status" => "ok",
                        "message" => "Transaction success",
                        "balance" => round($client_response->fundtransferresponse->balance,2)
                    );
                    
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
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                }
            }
            else{
                $msg = array(
                    "status" =>"error",
                    "message" => "Invalid Token or Token not found"
                ); 
                return response($msg,200)
                ->header('Content-Type', 'application/json');
            }
        }
    }
    public function makeWithdraw(Request $request){
        if($request->has("token")&&$request->has("player_id")){
            $client_details = ProviderHelper::getClientDetails('token', $request->token);
            if($client_details){
                $game_details = Helper::getInfoPlayerGameRound($request->token);
                $json_data = array(
                    "transid" => Carbon::now()->timestamp,
                    "amount" => 0,
                    "roundid" => 0,
                    "win"=>1,
                    "payout_reason" => "Withdraw from round"
                );
                $game = MGHelper::getGameTransaction($request->token,0);
                if($game){
                    $gametransactionid = $game->game_trans_id;
                }
                $transactionId =MGHelper::createMGGameTransactionExt($gametransactionid,$json_data,null,null,null,2);
                $http = new Client();
                $response = $http->post("https://api-tigergaming.k2net.io/api/v1/agents/Tiger_UPG_USD_MA_Test/WalletTransactions",[
                    'form_params' => [
                        'playerId' => $request->player_id,
                        'type' => "withdraw",
                        'amount' => null,
                        'externalTransactionId' => $transactionId,
                    ]
                    ,
                    'headers' =>[
                        'Authorization' => 'Bearer '.MGHelper::stsTokenizer(),
                        'Accept'     => 'application/json' 
                    ]
                ]);
                $data = json_decode($response->getBody(),TRUE);
                $json_data["amount"] = $game->pay_amount+$data["amount"];
                $gameupdate = MGHelper::updateGameTransaction($game,$json_data,"credit");
                $client_response = ClientRequestHelper::fundTransfer($client_details,$data["amount"],$game_details->game_code,$game_details->game_name,$transactionId,$gametransactionid,"credit");
                if(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "200"){
                    MGHelper::updateGameTransactionExt($transactionId,$data["amount"],$client_response->requestoclient,json_decode($response->getBody()),$client_response);
                    $msg = array(
                        "status" => "ok",
                        "message" => "Transaction success",
                        "balance"   =>  round($client_response->fundtransferresponse->balance,2)
                    );
                    
                    response($msg,200)->header('Content-Type', 'application/json');
                    return response($msg,200)
                        ->header('Content-Type', 'application/json');
                }
            }
            else{
                $msg = array(
                    "status" =>"error",
                    "message" => "Invalid Token or Token not found"
                ); 
                return response($msg,200)
                ->header('Content-Type', 'application/json');
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
}
