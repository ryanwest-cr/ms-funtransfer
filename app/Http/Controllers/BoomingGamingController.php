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
            $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
            $get_savelog = Helper::getGameCode($data["session_id"], $this->provider_db_id);
            $request_data = json_decode($get_savelog->request_data); // get request_data 
            $game_code = $request_data->game_code;
            $url = $request_data->url;
            $game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $game_code);
            $game_ext = $this->findGameExt($data['session_id'], $data["round"], 2, 'transaction_id'); 
                if($game_ext == 'false'): // NO BET found mw
                    //if the amount is grater than to the bet amount  error message
                    if($player_details->playerdetailsresponse->balance < $data['bet']):
                        $errormessage = array(
                            'error' => '2006',
                            'message' => 'Invalid balance'
                            
                        );
                        Helper::saveLog('Booming Callback error ', $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $errormessage);
                        return json_encode($errormessage, JSON_FORCE_OBJECT); 
                    endif;
                    //DEBIT
                    $game_code = $game_details->game_id;
                    $token_id = $client_details->token_id;
                    $bet_amount = abs($data["bet"]);
                    $pay_amount = 0;
                    $income = 0;
                    $win_type = 0;
                    $method = 1;
                    $win_or_lost = 5; // 0 lost,  5 processing
                    $payout_reason = 'Bet';
                    $provider_trans_id = $data['session_id']; // this is customerid
                    $round_id = $data['round'];// this is round

                    //Create GameTransaction, GameExtension
                    $gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_code, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
                    $game_transextension = $this->createGameTransExt($gamerecord,$provider_trans_id, $round_id, $bet_amount, 1, $data, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);
                      
                    //get Round_id, Transaction_id
                    $transaction_id = $this->findGameExt($provider_trans_id, $round_id, 1, 'transaction_id'); //findGameProcess
                    
                    //requesttosend, and responsetoclient client side
                    $type = "debit";
                    $rollback = false;
                    $client_response = ClientRequestHelper::fundTransfer($client_details,$bet_amount,$game_code,$game_details->game_name,$transaction_id->game_trans_ext_id,$transaction_id->game_trans_id,$type,$rollback);
                    $data_response =  [
                        "balance" => (string)$client_response->fundtransferresponse->balance
                    ];
                    $this->updateGameTransactionExt($transaction_id->game_trans_ext_id,$client_response->requestoclient,$client_response->fundtransferresponse,$data_response);
                    //END OF DEBIT
                    $game_transextension = $this->createGameTransExt($transaction_id->game_trans_id,$provider_trans_id, $round_id, $data["win"], 2, $data, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);

                    $type = "credit";
                    $rollback = false;
                    $client_response = ClientRequestHelper::fundTransfer($client_details,$data["win"],$game_details->game_code,$game_details->game_name,$game_transextension,$transaction_id->game_trans_id,$type,$rollback);

                    $bet_transaction = ProviderHelper::findGameTransaction($gamerecord, 'game_transaction');
                    $win = $data["win"] == 0.0 ? 0 : 1;  /// 1win 0lost
                    $type = $data["win"] == 0.0 ? "debit" : "credit";
                    $request_data = [
                        'win' => $win,
                        'amount' => $data["win"],
                        'payout_reason' => 2
                    ];
                    
                    $data_response =  [
                        "balance" => (string)$client_response->fundtransferresponse->balance
                    ];
                    //update transaction
                    Helper::updateGameTransaction($bet_transaction,$request_data,$type);
                    $this->updateGameTransactionExt($game_transextension,$client_response->requestoclient,$client_response->fundtransferresponse,$data_response);
                    ProviderHelper::updateGameTransactionStatus($gamerecord, $win, 1);
                    Helper::saveLog('Booming Callback Process ', $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $data_response);
                    return json_encode($data_response, JSON_FORCE_OBJECT); 
                   
                else:
                    $errormessage = array(
                        'error' => '2099',
                        'message' => 'Generic validation error'
                    );
                    Helper::saveLog('Booming Callback error', $this->provider_db_id, json_encode($request->all(), JSON_FORCE_OBJECT), $errormessage);
                    return json_encode($errormessage, JSON_FORCE_OBJECT); 
                endif;
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
        Helper::saveLog('Booming Rollback ', $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $header);
        $data = $request->all();
        $client_details = ProviderHelper::getClientDetails('player_id',$data["player_id"]);
        $game_ext = $this->findGameExt($data['session_id'], $data["round"], 3, 'transaction_id');
        $get_savelog = Helper::getGameCode($data["session_id"], $this->provider_db_id);
        $request_data = json_decode($get_savelog->request_data); // get request_data 
        $game_code = $request_data->game_code;
        $url = $request_data->url;
        $game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $game_code);
        if($client_details != null):
            try{
                if($game_ext == 'false'):
                   
                        try {
                            $game_code = $game_details->game_id;
                            $token_id = $client_details->token_id;
                            $bet_amount = abs($data["bet"]);
                            $pay_amount = 0;
                            $income = 0;
                            $win_type = 0;
                            $method = 1;
                            $win_or_lost = 5; // 0 lost,  5 processing
                            $payout_reason = 'Bet';
                            $provider_trans_id = $data['session_id']; // this is customerid
                            $round_id = $data['round'];// this is round
        
                            //Create GameTransaction, GameExtension
                            $gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_code, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
                            $game_transextension = $this->createGameTransExt($gamerecord,$provider_trans_id, $round_id, $bet_amount, 1, $data, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);
                              
                            //get Round_id, Transaction_id
                            $transaction_id = $this->findGameExt($provider_trans_id, $round_id, 1, 'transaction_id'); //findGameProcess
                            
                            //requesttosend, and responsetoclient client side
                            $type = "debit";
                            $rollback = false;
                            $client_response = ClientRequestHelper::fundTransfer($client_details,$bet_amount,$game_code,$game_details->game_name,$transaction_id->game_trans_ext_id,$transaction_id->game_trans_id,$type,$rollback);
                            $data_response =  [
                                "balance" => (string)$client_response->fundtransferresponse->balance
                            ];
                            $this->updateGameTransactionExt($transaction_id->game_trans_ext_id,$client_response->requestoclient,$client_response->fundtransferresponse,$data_response);
                            //END OF DEBIT

                            $game_transextension = $this->createGameTransExt($transaction_id->game_trans_id,$provider_trans_id, $round_id, $data["win"], 3, $data, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);
        
                            $type = "credit";
                            $rollback = "true";
                            $client_response = ClientRequestHelper::fundTransfer($client_details,$data["win"],$game_details->game_code,$game_details->game_name,$game_transextension,$transaction_id->game_trans_id,$type,$rollback);
        
                            $bet_transaction = ProviderHelper::findGameTransaction($gamerecord, 'game_transaction');
                            $request_data = [
                                'amount' => $data["win"],
                                'transid' => $transaction_id->game_trans_ext_id,
                                'roundid' => $transaction_id->game_trans_id
                            ];
                            
                            $data_response =  [
                                "balance" => (string)$client_response->fundtransferresponse->balance
                            ];
                            //update transaction
                            Helper::updateGameTransaction($bet_transaction,$request_data,"refund");
                            $this->updateGameTransactionExt($game_transextension,$client_response->requestoclient,$client_response->fundtransferresponse,$data_response);
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
            }catch(\Exception $e){
                $errormsg = [
                    'error' => '3001',
                    'message' => $e->getMessage()
                ];
                Helper::saveLog('Booming Rollback error', $this->provider_db_id, json_encode($request->all(), JSON_FORCE_OBJECT),  $errormsg);
                return json_encode($errormsg, JSON_FORCE_OBJECT); 
            }
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

    
    
}
