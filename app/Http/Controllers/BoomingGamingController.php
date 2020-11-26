<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use GuzzleHttp\Client;
use App\Helpers\ClientRequestHelper;
use Carbon\Carbon;
use DB;


class BoomingGamingController extends Controller
{

    public function __construct(){
    	$this->api_key = config('providerlinks.booming.api_key');
    	$this->api_secret = config('providerlinks.booming.api_secret');
        $this->api_url = config('providerlinks.booming.api_url');
        $this->provider_db_id = config('providerlinks.booming.provider_db_id');
    }
    
    public function gameList(){
        $nonce = date('mdYhisu');
        $url =  $this->api_url.'/v2/games';
        $requesttosend = "";
        $sha256 =  hash('sha256', $requesttosend);
        $concat = '/v2/games'.$nonce.$sha256;
        $secrete = hash_hmac('sha512', $concat, $this->api_secret);

        
        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/vnd.api+json',
                'X-Bg-Api-Key' => $this->api_key,
                'X-Bg-Nonce'=> $nonce,
                'X-Bg-Signature' => $secrete
            ]
        ]);
       $guzzle_response = $client->get($url);
       $client_response = json_decode($guzzle_response->getBody()->getContents());
       return json_encode($client_response);
    }

    //THIS IS PART OF GAMELAUNCH GET SESSION AND URL
    public function callBack(Request $request){
        

        $header = [
            'bg_nonce' => $request->header('bg-nonce'),
            'bg_signature' => $request->header('bg-signature')
        ];
        Helper::saveLog('Booming Callback ', $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $header);
        $data = $request->all();
        $client_details = ProviderHelper::getClientDetails('player_id',$data["player_id"]);
        if($client_details != null){
            try{
                
                // $player_details = $this->playerDetailsCall($client_details);
                $game_details = Helper::getInfoPlayerGameRoundBooming($client_details->player_token);
                $game_code = $game_details[0]->game_code;

                // $game_ext = $this->findGameExt($data['session_id'], $data["round"], 2, 'transaction_id'); 
                // if($game_ext == 'false'): // NO BET found mw
                    if ($data["type"] == "freespin") {
                        $token_id = $client_details->token_id;
                        $bet_amount = abs($data["bet"]);
                        $pay_amount = $data["win"];
                        $income = $bet_amount - $pay_amount;
                        $win_or_lost = $data["win"] == 0.0 ? 0 : 1;  /// 1win 0lost
                        $type = $data["win"] == 0.0 ? "debit" : "credit";
                        $entry_id = $data["win"] == 0.0 ? 1 : 2;
                        $payout_reason = 'Game Round Freespin';
                        $provider_trans_id = $data['session_id']; // this is customerid
                        $round_id = $data['round'];// this is round
                        $transaction_reason = "Game Round Freespin";
                        $rollback = false;
                        //Create GameTransaction, GameExtension
                        $game_trans_id  = ProviderHelper::createGameTransaction($token_id, $game_details[0]->game_id, $bet_amount,  $pay_amount, $entry_id, $win_or_lost, $transaction_reason, $payout_reason, $income, $provider_trans_id, $round_id);
                        $game_trans_ext_id = $this->createGameTransExt($game_trans_id,$provider_trans_id, $round_id, $pay_amount, 2, $data, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);

                        $client_response = ClientRequestHelper::fundTransfer($client_details,$pay_amount,$game_code,$game_details[0]->game_name,$game_trans_ext_id,$game_trans_id,$type,$rollback);
                        
                        $data_response =  [
                            "balance" => (string)$client_response->fundtransferresponse->balance
                        ];

                        $this->updateGameTransactionExt($game_trans_ext_id,$client_response->requestoclient,$data_response,$client_response->fundtransferresponse);
                        
                        Helper::saveLog('Booming Callback Process Freespin', $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $data_response);
                        return json_encode($data_response, JSON_FORCE_OBJECT); 
                    }

                    $token_id = $client_details->token_id;
                    $bet_amount = abs($data["bet"]);
                    $pay_amount = $data["win"];
                    $income = $bet_amount - $pay_amount;
                    $win_or_lost = $data["win"] == 0.0 ? 0 : 1;  /// 1win 0lost
                    $entry_id = $data["win"] == 0.0 ? 1 : 2;// 1/bet/debit , 2//win/credit
                    $provider_trans_id = $data['session_id']; // this is customerid
                    $round_id = $data['round'];// this is round
                    $payout_reason = "Transaction updated to Win";
                    
                    //Create GameTransaction, GameExtension
                    $game_trans_id  = ProviderHelper::createGameTransaction($token_id, $game_details[0]->game_id, $bet_amount,  $pay_amount, $entry_id, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
                    $game_trans_ext_id = $this->createGameTransExt($game_trans_id,$provider_trans_id, $round_id, $bet_amount, 1, $data, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);
                    
                    //requesttosend, and responsetoclient client side
                    $type = "debit";
                    $rollback = false;
                    $client_response = ClientRequestHelper::fundTransfer($client_details,$bet_amount,$game_code,$game_details[0]->game_name,$game_trans_ext_id,$game_trans_id,$type,$rollback);
                    
                    $data_response =  [
                        "balance" => (string)$client_response->fundtransferresponse->balance
                    ];


                    $this->updateGameTransactionExt($game_trans_ext_id,$client_response->requestoclient,$data_response,$client_response->fundtransferresponse);
                    
                    $game_transextension = $this->createGameTransExt($game_trans_id,$provider_trans_id, $round_id, $data["win"], 2, $data, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);

                    $type = "credit";
                    $rollback = false;
                    $client_response = ClientRequestHelper::fundTransfer($client_details,$data["win"],$game_code,$game_details[0]->game_name,$game_transextension,$game_trans_id,$type,$rollback);

                    $data_response =  [
                        "balance" => (string)$client_response->fundtransferresponse->balance
                    ];
                    //update transaction
                    // Helper::updateGameTransaction($bet_transaction,$request_data,$type);

                    $this->updateGameTransactionExt($game_transextension,$client_response->requestoclient,$data_response,$client_response->fundtransferresponse);
                    // ProviderHelper::updateGameTransactionStatus($game_trans_id, $win, 1);
                    Helper::saveLog('Booming Callback Process ', $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $data_response);
                    return json_encode($data_response, JSON_FORCE_OBJECT); 
                   
            }catch(\Exception $e){
                $msg = array(
                    'error' => '3001',
                    'message' => $e->getMessage(),
                );
                Helper::saveLog('Booming Callback error', $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $msg);
                return json_encode($msg, JSON_FORCE_OBJECT); 
            }

		}else{
            $errormessage = array(
                'error' => '2012',
                'message' => 'Invalid Player ID'
            );
            Helper::saveLog('Booming Callback error', $this->provider_db_id, json_encode($request->all(), JSON_FORCE_OBJECT),  $errormessage);
            return json_encode($errormessage, JSON_FORCE_OBJECT); 
		}
        
    }

    public function rollBack(Request $request){
        $header = [
            'bg_nonce' => $request->header('bg-nonce'),
            'bg_signature' => $request->header('bg-signature')
        ];
        Helper::saveLog('Booming Rollback', $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $header);
        $data = $request->all();
        $client_details = ProviderHelper::getClientDetails('player_id',$data["player_id"]);
        $game_ext = $this->findGameExt($data['session_id'], $data["round"], 3, 'transaction_id');
        $game_details = Helper::getInfoPlayerGameRoundBooming($client_details->player_token);
        if($client_details != null):
            if($game_ext == 'false'):
                
                try {

                    $token_id = $client_details->token_id;
                    $bet_amount = abs($data["bet"]);
                    $pay_amount = $data["win"];
                    $income = $bet_amount - $pay_amount;
                    $win_or_lost = $data["win"] == 0.0 ? 0 : 1;  /// 1win 0lost
                    $entry_id = $data["win"] == 0.0 ? 1 : 2;// 1/bet/debit , 2//win/credit
                    $provider_trans_id = $data['session_id']; // this is customerid
                    $round_id = $data['round'];// this is round
                    $payout_reason = "Transaction updated to Win";
                    
                    //Create GameTransaction, GameExtension
                    $game_trans_id  = ProviderHelper::createGameTransaction($token_id, $game_details[0]->game_id, $bet_amount,  $pay_amount, $entry_id, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
                    $game_trans_ext_id = $this->createGameTransExt($game_trans_id,$provider_trans_id, $round_id, $bet_amount, 1, $data, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);

                    $type = "debit";
                    $rollback = false;
                    $client_response = ClientRequestHelper::fundTransfer($client_details,$bet_amount,$game_details[0]->game_code,$game_details[0]->game_name,$game_trans_ext_id,$game_trans_id,$type,$rollback);
                    
                    $data_response =  [
                        "balance" => (string)$client_response->fundtransferresponse->balance
                    ];


                    $this->updateGameTransactionExt($game_trans_ext_id,$client_response->requestoclient,$data_response,$client_response->fundtransferresponse);
                    
                    $game_transextension = $this->createGameTransExt($game_trans_id,$provider_trans_id, $round_id, $data["win"], 2, $data, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);

                    $type = "credit";
                    $rollback = false;
                    $client_response = ClientRequestHelper::fundTransfer($client_details,$data["win"],$game_details[0]->game_code,$game_details[0]->game_name,$game_transextension,$game_trans_id,$type,$rollback);

                    $data_response =  [
                        "balance" => (string)$client_response->fundtransferresponse->balance
                    ];
                    
                    $this->updateGameTransactionExt($game_transextension,$client_response->requestoclient,$data_response,$client_response->fundtransferresponse);
                    Helper::saveLog('Booming Rollback Process ', $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $data_response);
                    return json_encode($data_response, JSON_FORCE_OBJECT); 
                   
                }catch(\Exception $e){
                    $errormessage = [
                        'error' => '2099',
                        'message' => $e->getMessage()
                    ];
                    Helper::saveLog('Booming Rollback error', $this->provider_db_id,  json_encode($request->all(),JSON_FORCE_OBJECT), $errormessage);
                    return json_encode($errormessage, JSON_FORCE_OBJECT); 
                }
              
            else:
                    // NOTE IF CALLBACK WAS ALREADY PROCESS PROVIDER DONT NEED A ERROR RESPONSE! LEAVE IT AS IT IS!
                    $errormessage = [
                        'error' => '2010',
                        'message' => 'Unsupported parameters provided'
                    ];
                Helper::saveLog('Booming Rollback error', $this->provider_db_id,  json_encode($request->all(),JSON_FORCE_OBJECT), $errormessage);
                return json_encode($errormessage, JSON_FORCE_OBJECT); 
            endif;
        else:
            $errormsg = [
                'error' => '2012',
                'message' => 'Invalid Player ID'
            ];
            Helper::saveLog('Booming Rollback error', $this->provider_db_id, json_encode($request->all(), JSON_FORCE_OBJECT),  $errormsg);
            return json_encode($errormsg, JSON_FORCE_OBJECT); 
        endif;
        
    }

    public static function creteBoomingtransaction($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response, $game_transaction_type, $amount=null, $provider_trans_id=null, $round_id=null){
		$gametransactionext = array(
			"game_trans_id" => $gametransaction_id,
			"provider_trans_id" => $provider_trans_id,
			"round_id" => $round_id,
			"amount" => $amount,
			"game_transaction_type"=>$game_transaction_type,
			"provider_request" => json_encode($provider_request),
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gametransactionext;
    }

    public  static function findGameExt($provider_identifier,$round, $game_transaction_type, $type) {
		$transaction_db = DB::table('game_transaction_ext as gte');
        if ($type == 'transaction_id') {
			$transaction_db->where([
                ["gte.provider_trans_id", "=", $provider_identifier],
                ["gte.round_id", "=", $round],
		 		["gte.game_transaction_type", "=", $game_transaction_type],
		 	]);
		}
		$result = $transaction_db->latest()->first(); // Added Latest (CQ9) 08-12-20 - Al
		return $result ? $result : 'false';
    }


    //update 2020/09/21
    public static function createGameTransExt($game_trans_id, $provider_trans_id, $round_id, $amount, $game_type, $provider_request, $mw_response, $mw_request, $client_response, $transaction_detail){
		$gametransactionext = array(
			"game_trans_id" => $game_trans_id,
			"provider_trans_id" => $provider_trans_id,
			"round_id" => $round_id,
			"amount" => $amount,
			"game_transaction_type"=>$game_type,
			"provider_request" => json_encode($provider_request),
			"mw_response" =>json_encode($mw_response),
			"mw_request"=>json_encode($mw_request),
			"client_response" =>json_encode($client_response),
			"transaction_detail" =>json_encode($transaction_detail)
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gamestransaction_ext_ID;
    }
    
    public static function updateGameTransactionExt($gametransextid,$mw_request,$mw_response,$client_response){
		$gametransactionext = array(
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		DB::table('game_transaction_ext')->where("game_trans_ext_id",$gametransextid)->update($gametransactionext);
	}


    public static function playerDetailsCall($client_details, $refreshtoken=false, $type=1){
        if($client_details){
            $client = new Client([
                'headers' => [ 
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$client_details->client_access_token
                ]
            ]);
            $datatosend = [
                "access_token" => $client_details->client_access_token,
                "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
                "type" => "playerdetailsrequest",
                "datesent" => Helper::datesent(),
                "gameid" => "",
                "clientid" => $client_details->client_id,
                "playerdetailsrequest" => [
                    "player_username"=>$client_details->username,
                    "client_player_id" => $client_details->client_player_id,
                    "token" => $client_details->player_token,
                    "gamelaunch" => true,
                    "refreshtoken" => $refreshtoken
                ]
            ];

            // return $datatosend;
            try{    
                $guzzle_response = $client->post($client_details->player_details_url,
                    ['body' => json_encode($datatosend)]
                );
                $client_response = json_decode($guzzle_response->getBody()->getContents());
                
                // Helper::saveLog('ALDEBUG REQUEST SEND = '.$player_token,  99, json_encode($client_response), $datatosend);
                
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
                    return $client_response;
                }

            }catch (\Exception $e){
               // Helper::saveLog('ALDEBUG client_player_id = '.$client_details->client_player_id,  99, json_encode($datatosend), $e->getMessage());
               return 'false';
            }
        }else{
            return 'false';
        }
    }

    
    
}
