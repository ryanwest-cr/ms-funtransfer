<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use GuzzleHttp\Client;
use Carbon\Carbon;
use DB;

class PGSoftController extends Controller
{
    // 
    public $provider_db_id = 31;

    public function __construct(){
    	$this->operator_token = config('providerlinks.pgsoft.operator_token');
    	$this->secret_key = config('providerlinks.pgsoft.secret_key');
    	$this->api_url = config('providerlinks.pgsoft.api_url');
    }

    public function verifySession(Request $request){
        Helper::saveLog('PGSoft VerifySession', $this->provider_db_id, json_encode($request->all(), JSON_FORCE_OBJECT), 'ENDPOINT HIT');
        $data = $request->all();
        if($data["operator_token"] != $this->operator_token || $data["secret_key"] != $this->secret_key):
            $errormessage = array(
                'data' => null,
                'error' => [
				'code' 	=> '1034',
                'message'  	=> 'Invalid request'
                ]
            );
            Helper::saveLog('PGSoft VerifySession error', $this->provider_db_id,  json_encode($request->all(),JSON_FORCE_OBJECT), $errormessage);
            return json_encode($errormessage, JSON_FORCE_OBJECT); 
        endif;

        $client_details = ProviderHelper::getClientDetails('token',$data["operator_player_session"]);
        
        if($client_details != null){
            $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
				$data =  [
                    "data" => [
                        "player_name" => $client_details->username,
                        "nickname" => $client_details->display_name,
                        "currency" => $client_details->default_currency
                    ],
                    "error" => null
                ];
				Helper::saveLog('PGSoft VerifySession Process', $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $data);
                return json_encode($data, JSON_FORCE_OBJECT); 
		}else{
            $errormessage = array(
                'data' => null,
                'error' => [
				'code' 	=> '1034',
                'message'  	=> 'Invalid request'
                ]
            );
            Helper::saveLog('PGSoft VerifySession error', $this->provider_db_id,  json_encode($request->all(),JSON_FORCE_OBJECT), $errormessage);
            return json_encode($errormessage, JSON_FORCE_OBJECT); 
		}
    }
    
    public function cashGet(Request $request){ // Wallet Check Balance Endpoint Hit
        Helper::saveLog('PGSoft CashGet', $this->provider_db_id, json_encode($request->all(), JSON_FORCE_OBJECT ), 'ENDPOINT HIT');
        $data = $request->all();
        if($data["operator_token"] != $this->operator_token || $data["secret_key"] != $this->secret_key):
            $errormessage = array(
                'data' => null,
                'error' => [
                    'code' 	=> '1034',
                    'message'  	=> 'Invalid request'
                ]
            );
            Helper::saveLog('PGSoft CashGet error', $this->provider_db_id,  json_encode($request->all(),JSON_FORCE_OBJECT), $errormessage);
            return json_encode($errormessage, JSON_FORCE_OBJECT); 
        endif;

        $client_details = ProviderHelper::getClientDetails('token',$data["operator_player_session"]);
       
        if($client_details != null){
            $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
				$currency = $client_details->default_currency;
				$num = $player_details->playerdetailsresponse->balance;
                $balance = (double)$num;
				$response =  [
                    "data" => [
                        "currency_code" => $currency,
                        "balance_amount" => $balance,
                        "updated_time" => $this->getMilliseconds()
                    ],
                    "error" => null
                ];
				Helper::saveLog('PGSoft CashGet Process', $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $response);
                return json_encode($response, JSON_FORCE_OBJECT); 
		}else{
            $errormessage = array(
                'data' => null,
                'error' => [
				'code' 	=> '1034',
                'message'  	=> 'Invalid request'
                ]
            );
            Helper::saveLog('PGSoft CashGet error', $this->provider_db_id,  json_encode($request->all(),JSON_FORCE_OBJECT), $errormessage);
            return json_encode($errormessage, JSON_FORCE_OBJECT); 
		}
    }

    public function transferOut(Request $request){
        Helper::saveLog('PGSoft Bet ', $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), 'ENDPOINT HIT');
        $data = $request->all();
        // $data = array (
        //     'secret_key' => '02f314db35a0dfe4635dff771b607f34',//pgsoft
        //     'operator_token' => '642052d1627c8cae4a288fc82a8bf892',//pgsoft
        //     'operator_player_session' => 'n58ec5e159f769ae0b7b3a0774fdbf80',//operator
        //     'player_name' => 'charity',
        //     'game_id' => 'diaochan',
        //     'parent_bet_id' => '1', //mw
        //     'bet_id' => '1',
        //     'bet_type' => '1',//bet
        //     'currency_code' => 'USD',
        //     'create_time' => '1596853129881',
        //     'updated_time' => '1596853129881',
        //     'transfer_amount' => '200',//amount bet
        //     'transaction_id' => '1-1-201-',//bet_id=1-parent_id=1-transaction_type=201 mw insert
        //     'is_validate_bet' => 'false',
        // );
        //PGsoft provider
        if($data["operator_token"] != $this->operator_token && $data["secret_key"] != $this->secret_key):
            $errormessage = array(
                'data' => null,
                'error' => [
				'code' 	=> '3001',
                'message'  	=> 'Value cannot be null'
                ]
            );
            Helper::saveLog('PGSoft Bet error', $this->provider_db_id,  json_encode($request->all(),JSON_FORCE_OBJECT), $errormessage);
            return json_encode($errormessage, JSON_FORCE_OBJECT); 
        endif;

        $client_details = ProviderHelper::getClientDetails('token',$data["operator_player_session"]);
       
        if($client_details != null){
            $player_details = Providerhelper::playerDetailsCall($client_details->player_token);

            $game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $data["game_id"]);
            
            $game_ext = Providerhelper::findGameExt($data['transaction_id'], 1, 'transaction_id'); 
          
            if($game_ext == 'false'): // NO BET found mw
			
                //if the amount is grater than to the bet amount  error message
                if($player_details->playerdetailsresponse->balance < $data['transfer_amount']):
                    $errormessage = array(
                        'data' => null,
                        'error' => [
                        'code' 	=> '3202',
                        'message'  	=> 'No enough cash balance to bet'
                        ]
                    );
                    Helper::saveLog('PGSoft Bet error '.$data["transaction_id"], $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $errormessage);
                    return json_encode($errormessage, JSON_FORCE_OBJECT); 
                endif;
    
                $requesttosend = [
                  "access_token" => $client_details->client_access_token,
                  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
                  "type" => "fundtransferrequest",
                  "datesent" => Helper::datesent(),
                  "gamedetails" => [
                    "gameid" =>  "",
                    "gamename" => ""
                  ],
                  "fundtransferrequest" => [
                        "playerinfo" => [
                        "client_player_id" => $client_details->client_player_id,
                        "token" => $data['operator_player_session'],
                    ],
                    "fundinfo" => [
                          "gamesessionid" => "",
                          "transferid" => "",
                          "transactiontype" => 'debit',
                          "rollback" => "false",
                          "currencycode" => $client_details->default_currency,
                          "amount" => $data['transfer_amount']
                    ]
                  ]
                ];
            
                try {
                    $client = new Client([
                        'headers' => [ 
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Bearer '.$client_details->client_access_token
                        ]
                    ]);
                    $guzzle_response = $client->post($client_details->fund_transfer_url,
                        ['body' => json_encode($requesttosend)]
                    );

                    $client_response = json_decode($guzzle_response->getBody()->getContents());
                    $response =  [
                        "data" => [
                            "currency_code" => $client_details->default_currency,
                            "balance_amount" => $client_response->fundtransferresponse->balance,
                            "updated_time" => $this->getMilliseconds()
                        ],
                        "error" => null
                    ];

                    $token_id = $client_details->token_id;
                    $game_id = $game_details->game_id;
                    $bet_amount =  $data['transfer_amount'];
                    $payout = 0;
                    $entry_id = 1; //1 bet , 2win
                    $win = 0;// 0 Lost, 1 win, 3 draw, 4 refund, 5 processing
                    $payout_reason = 'Bet';
                    $income = 0;
                    $provider_trans_id = $data['transaction_id'];
                    $round_id = $data['bet_id'];
    
                    $gametransaction_id = Helper::saveGame_transaction($token_id, $game_id, $bet_amount, $payout, $entry_id,  $win, null, $payout_reason , $income, $provider_trans_id, $round_id);
                    
                    $provider_request = $data;
                    $mw_request = $requesttosend;
                    $mw_response = $client_response;
                    $client_response = $client_response;
                    $transaction_detail = $client_response;
                    $game_transaction_type = 1;
    
                    $this->cretePGSofttransaction($gametransaction_id, $provider_request,$mw_request,$mw_response,$client_response, $transaction_detail,$game_transaction_type, $bet_amount, $provider_trans_id, $round_id);
                
                    Helper::saveLog('PGSoft Bet Process '.$data["transaction_id"], $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $response);
                    return json_encode($response, JSON_FORCE_OBJECT); 
                }catch(\Exception $e){
                    $msg = array(
                        "data" => null,
                        "error" => [
                            'code' => '3001',
                            "message" => $e->getMessage(),
                        ]
                    );
                    Helper::saveLog('PGSoft Bet error'.$data['transaction_id'], $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $msg);
                    return json_encode($msg, JSON_FORCE_OBJECT); 
                }
            else:
                //if found
                // NOTE IF CALLBACK WAS ALREADY PROCESS PROVIDER NEED A ERROR RESPONSE!
                $errormessage = array(
                    'data' => null,
                    'error' => [
                        'code' 	=> '3033',
                        'message'  	=> 'Bet failed'
                    ]
                );
                Helper::saveLog('PGSoft Bet error '.$request['transaction_id'], $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $errormessage);
                return json_encode($errormessage, JSON_FORCE_OBJECT); 
            endif;
			
		}else{
            
            $errormessage = array(
                'data' => null,
                'error' => [
				'code' 	=> '3004',
                'message'  	=> 'Player is not exist'
                ]
            );
            Helper::saveLog('PGSoft Bet error', $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT),  $errormessage);
            return json_encode($errormessage, JSON_FORCE_OBJECT); 
		}
        
    }

    public function transferIn(Request $request){
        Helper::saveLog('PGSoft Payout', $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT),  "ENDPOINT HIT");

        return $request->all();
    }

    public function getMilliseconds(){
        return $milliseconds = round(microtime(true) * 1000);
    }

    public static function cretePGSofttransaction($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response, $transaction_detail,$game_transaction_type, $amount=null, $provider_trans_id=null, $round_id=null){
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
			"transaction_detail" =>json_encode($transaction_detail),
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gametransactionext;
	}
    
}
