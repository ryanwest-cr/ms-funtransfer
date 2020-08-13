<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use GuzzleHttp\Client;
use Carbon\Carbon;
use DB;


class BoomingGamingController extends Controller
{
    public $provider_db_id = 36; // no update databse insert provider

    public function __construct(){
    	$this->api_key = config('providerlinks.booming.api_key');
    	$this->api_secret = config('providerlinks.booming.api_secret');
    	$this->api_url = config('providerlinks.booming.api_url');
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
        $bg_nonce = $request->header('bg-nonce');
        $bg_signature = $request->header('bg-signature');
        Helper::saveLog('Booming Callback ', $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $bg_signature);
        $data = $request->all();
        $client_details = ProviderHelper::getClientDetails('player_id',$data["player_id"]);
        if($client_details != null){
            try{
            $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
            $game_details = $this->findGameCode('game_code', $this->provider_db_id, $data['game_id']);
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
                        Helper::saveLog('Booming Bet error '.$data["transaction_id"], $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $errormessage);
                        return json_encode($errormessage, JSON_FORCE_OBJECT); 
                    endif;

                    $requesttosend = [
                        "access_token" => $client_details->client_access_token,
                        "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
                        "type" => "fundtransferrequest",
                        "datesent" => Helper::datesent(),
                        "gamedetails" => [
                            "gameid" => $game_details->game_code, // $game_details->game_code
                            "gamename" => $game_details->game_name
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
                        $bet_amount =  $data['transfer_amount'];
                        $payout = 0;
                        $entry_id = 1; //1 bet , 2win
                        $win = 0;// 0 Lost, 1 win, 3 draw, 4 refund, 5 processing
                        $payout_reason = 'Bet';
                        $income = 0;
                        $provider_trans_id = $data['transaction_id'];
                        $round_id = $data['bet_id'];

                        $gametransaction_id = Helper::saveGame_transaction($token_id, $game_details->game_id, $bet_amount, $payout, $entry_id,  $win, null, $payout_reason , $income, $provider_trans_id, $round_id);
                        
                        $provider_request = $data;
                        $mw_request = $requesttosend;
                        $mw_response = $response;
                        $client_response = $client_response;
                        $game_transaction_type = 1;

                        $this->creteBoomingtransaction($gametransaction_id, $provider_request,$mw_request,$mw_response,$client_response,$game_transaction_type, $bet_amount, $provider_trans_id, $round_id);
                    
                        Helper::saveLog('Booming Bet Process '.$data["transaction_id"], $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $response);
                        return json_encode($response, JSON_FORCE_OBJECT); 
                    }catch(\Exception $e){
                        $msg = array(
                            "data" => null,
                            "error" => [
                                'code' => '3001',
                                "message" => $e->getMessage(),
                            ]
                        );
                        Helper::saveLog('Booming Bet error '.$data['transaction_id'], $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $msg);
                        return json_encode($msg, JSON_FORCE_OBJECT); 
                    }
                else:
                    //if found
                    // NOTE IF CALLBACK WAS ALREADY PROCESS PROVIDER NEED A ERROR RESPONSE!
                    $game_not_succes = Providerhelper::findGameExt($data['transaction_id'], 2, 'transaction_id'); 
                    if($game_not_succes == 'false'): // if no process win it means thi is not succeful make idempotent response
                        Helper::saveLog('Booming Bet idempotent response'.$data['transaction_id'], $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), json_decode($game_ext->mw_response));
                        return $game_ext->mw_response;
                    else:
                        $errormessage = array(
                            'data' => null,
                            'error' => [
                                'code' 	=> '3033',
                                'message'  	=> 'Bet failed'
                            ]
                        );
                        Helper::saveLog('Booming Bet error '.$request['transaction_id'], $this->provider_db_id, json_encode($request->all(), JSON_FORCE_OBJECT), $errormessage);
                        return json_encode($errormessage, JSON_FORCE_OBJECT); 
                    endif;
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
        Helper::saveLog('Booming Payout', $this->provider_db_id, json_encode($request->all(), JSON_FORCE_OBJECT),  "ENDPOINT HIT");
        $data = $request->all();
        if($data["operator_token"] != $this->operator_token && $data["secret_key"] != $this->secret_key):
            $errormessage = array(
                'data' => null,
                'error' => [
				'code' 	=> '3001',
                'message'  	=> 'Value cannot be null'
                ]
            );
            Helper::saveLog('Booming Payout error', $this->provider_db_id,  json_encode($request->all(),JSON_FORCE_OBJECT), $errormessage);
            return json_encode($errormessage, JSON_FORCE_OBJECT); 
        endif;

        $client_details = ProviderHelper::getClientDetails('token',$data["operator_player_session"]);

        $existing_bet = ProviderHelper::findGameTransaction($data['bet_transaction_id'], 'transaction_id', 1); // Find if win has bet record
		$game_ext = ProviderHelper::findGameExt($data['bet_transaction_id'], 2, 'transaction_id'); // Find if this callback in game extension
        $game_details = $this->findGameCode('game_code', $this->provider_db_id, $data['game_id']);
       
        if($game_ext == 'false'):
			if($existing_bet != 'false'): // Bet is existing, else the bet is already updated to win //temporary == make it !=
				$requesttosend = [
					  "access_token" => $client_details->client_access_token,
					  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
					  "type" => "fundtransferrequest",
					  "datesent" => Helper::datesent(),
					  "gamedetails" => [
					     "gameid" => $game_details->game_code, // $game_details->game_code
				         "gamename" => $game_details->game_name
					  ],
					  "fundtransferrequest" => [
							"playerinfo" => [
							"client_player_id" => $client_details->client_player_id,
							"token" => $data['operator_player_session'],
						],
						"fundinfo" => [
						      "gamesessionid" => "",
						      "transferid" => "",
						      "transactiontype" => 'credit',
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

						$amount = $data['transfer_amount'];
				 	    $round_id = $data['bet_id'];
				 	    if($amount == 0 || $amount == '0' ):
		 	  				$win = 0; // lost
		 	  				$entry_id = 1; //lost
		 	  				$income = $existing_bet->bet_amount - $amount;
		 	  			else:
		 	  				$win = 1; //win
		 	  				$entry_id = 2; //win
		 	  				$income = $existing_bet->bet_amount - $amount;
						   endif;
						   
                        $this->updateBetTransaction($round_id, $amount, $income, $win, $entry_id);
                        $provider_request = $data;
                        $mw_request = $requesttosend;
                        $mw_response = $response;
                        $client_response = $client_response;
                        $game_transaction_type = 2;

                        $this->creteBoomingtransaction($existing_bet->game_trans_id, $provider_request,$mw_request,$mw_response,$client_response,$game_transaction_type, $amount, $data["transaction_id"], $round_id);
						
                        Helper::saveLog('Booming Win process', $this->provider_db_id, json_encode($request->all(), JSON_FORCE_OBJECT),  $response);
					  	return $response;

					}catch(\Exception $e){
                        $errormessage = array(
                            'data' => null,
                            'error' => [
                            'code' 	=> '3034',
                            'message'  	=> $e->getMessage(),
                            ]
                        );
                        Helper::saveLog('Booming Payout error', $this->provider_db_id,  json_encode($request->all(),JSON_FORCE_OBJECT), $errormessage);
                        return json_encode($errormessage, JSON_FORCE_OBJECT); 
					}
                endif;
		else:
			    // NOTE IF CALLBACK WAS ALREADY PROCESS PROVIDER DONT NEED A ERROR RESPONSE! LEAVE IT AS IT IS!
            $errormessage = array(
                'data' => null,
                'error' => [
                'code' 	=> '3034',
                'message'  	=> 'Payout failed'
                ]
            );
            Helper::saveLog('Booming Payout error', $this->provider_db_id,  json_encode($request->all(),JSON_FORCE_OBJECT), $errormessage);
            return json_encode($errormessage, JSON_FORCE_OBJECT); 
		endif;
    }
}
