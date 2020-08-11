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
                        Helper::saveLog('PGSoft Bet error '.$data["transaction_id"], $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $errormessage);
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

                        $this->cretePGSofttransaction($gametransaction_id, $provider_request,$mw_request,$mw_response,$client_response,$game_transaction_type, $bet_amount, $provider_trans_id, $round_id);
                    
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
                        Helper::saveLog('PGSoft Bet error '.$data['transaction_id'], $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $msg);
                        return json_encode($msg, JSON_FORCE_OBJECT); 
                    }
                else:
                    //if found
                    // NOTE IF CALLBACK WAS ALREADY PROCESS PROVIDER NEED A ERROR RESPONSE!
                    $game_not_succes = Providerhelper::findGameExt($data['transaction_id'], 2, 'transaction_id'); 
                    if($game_not_succes == 'false'): // if no process win it means thi is not succeful make idempotent response
                        Helper::saveLog('PGSoft Bet idempotent response'.$data['transaction_id'], $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), json_decode($game_ext->mw_response));
                        return $game_ext->mw_response;
                    else:
                        $errormessage = array(
                            'data' => null,
                            'error' => [
                                'code' 	=> '3033',
                                'message'  	=> 'Bet failed'
                            ]
                        );
                        Helper::saveLog('PGSoft Bet error '.$request['transaction_id'], $this->provider_db_id, json_encode($request->all(), JSON_FORCE_OBJECT), $errormessage);
                        return json_encode($errormessage, JSON_FORCE_OBJECT); 
                    endif;
                endif;
            }catch(\Exception $e){
                $msg = array(
                    "data" => null,
                    "error" => [
                        'code' => '3001',
                        "message" => $e->getMessage(),
                    ]
                );
                Helper::saveLog('PGSoft Bet error '.$data['transaction_id'], $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $msg);
                return json_encode($msg, JSON_FORCE_OBJECT); 
            }

		}else{
            $errormessage = array(
                'data' => null,
                'error' => [
				'code' 	=> '3004',
                'message'  	=> 'Player is not exist'
                ]
            );
            Helper::saveLog('PGSoft Bet error', $this->provider_db_id, json_encode($request->all(), JSON_FORCE_OBJECT),  $errormessage);
            return json_encode($errormessage, JSON_FORCE_OBJECT); 
		}
        
    }

    public function transferIn(Request $request){
        Helper::saveLog('PGSoft Payout', $this->provider_db_id, json_encode($request->all(), JSON_FORCE_OBJECT),  "ENDPOINT HIT");
        $data = $request->all();
        if($data["operator_token"] != $this->operator_token && $data["secret_key"] != $this->secret_key):
            $errormessage = array(
                'data' => null,
                'error' => [
				'code' 	=> '3001',
                'message'  	=> 'Value cannot be null'
                ]
            );
            Helper::saveLog('PGSoft Payout error', $this->provider_db_id,  json_encode($request->all(),JSON_FORCE_OBJECT), $errormessage);
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

                        $this->cretePGSofttransaction($existing_bet->game_trans_id, $provider_request,$mw_request,$mw_response,$client_response,$game_transaction_type, $amount, $data["transaction_id"], $round_id);
						
                        Helper::saveLog('PGSoft Win process', $this->provider_db_id, json_encode($request->all(), JSON_FORCE_OBJECT),  $response);
					  	return $response;

					}catch(\Exception $e){
                        $errormessage = array(
                            'data' => null,
                            'error' => [
                            'code' 	=> '3034',
                            'message'  	=> $e->getMessage(),
                            ]
                        );
                        Helper::saveLog('PGSoft Payout error', $this->provider_db_id,  json_encode($request->all(),JSON_FORCE_OBJECT), $errormessage);
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
            Helper::saveLog('PGSoft Payout error', $this->provider_db_id,  json_encode($request->all(),JSON_FORCE_OBJECT), $errormessage);
            return json_encode($errormessage, JSON_FORCE_OBJECT); 
		endif;
    }

    public function getMilliseconds(){
        return $milliseconds = round(microtime(true) * 1000);
    }

    public static function cretePGSofttransaction($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response, $game_transaction_type, $amount=null, $provider_trans_id=null, $round_id=null){
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
    
    public function updateBetTransaction($round_id, $pay_amount, $income, $win, $entry_id) {
		$update = DB::table('game_transactions')
			 ->where('round_id', $round_id)
			 ->update(['pay_amount' => $pay_amount, 
				   'income' => $income, 
				   'win' => $win, 
				   'entry_id' => $entry_id,
				   'transaction_reason' => $this->updateReason($win),
			 ]);
	 return ($update ? true : false);
     }
     public  function updateReason($win) {
		$win_type = [
		"1" => 'Transaction updated to win',
		"2" => 'Transaction updated to bet',
		"3" => 'Transaction updated to Draw',
		"4" => 'Transaction updated to Refund',
		"5" => 'Transaction updated to Processing',
		];
		if(array_key_exists($win, $win_type)){
			return $win_type[$win];
		}else{
			return 'Transaction Was Updated!';
		}
    }
    
    public static function findGameCode($type, $provider_id, $identification) {
        $array = [
            [1,"diaochan"],
            [2,"gem-saviour"],
            [3,"fortune-gods"],
            [4,"summon-conquer"],
            [6,"medusa2"],
            [7,"medusa"],
            [8,"peas-fairy"],
            [17,"wizdom-wonders"],
            [18,"hood-wolf"],
            [19,"steam-punk"],
            [24,"win-win-won"],
            [25,"plushie-frenzy"],
            [26,"fortune-tree"],
            [27,"restaurant-craze"],
            [28,"hotpot"],
            [29,"dragon-legend"],
            [33,"hip-hop-panda"],
            [34,"legend-of-hou-yi"],
            [35,"mr-hallow-win"],
            [36,"prosperity-lion"],
            [37,"santas-gift-rush"],
            [38,"gem-saviour-sword"],
            [39,"piggy-gold"],
            [40,"jungle-delight"],
            [41,"symbols-of-egypt"],
            [42,"ganesha-gold"],
            [43,"three-monkeys"],
            [44,"emperors-favour"],
            [45,"tomb-of-treasure"],
            [48,"double-fortune"],
            [52,"wild-inferno"],
            [53,"the-great-icescape"],                          
            [10,"joker-wild"],//tablegame
            [11,"blackjack-us"],//tablegame
            [12,"blackjack-eu"],//tablegame
            [31,"baccarat-deluxe"]//tablegame
        ];
        $game_code = '';
        for ($row = 0; $row < count($array); $row++) {
            if($array[$row][0] == $identification){
                $game_code = $array[$row][1];
            }
        }
        $game_details = DB::table("games as g")
            ->leftJoin("providers as p","g.provider_id","=","p.provider_id");
        
        if ($type == 'game_code') {
            $game_details->where([
                 ["g.provider_id", "=", $provider_id],
                 ["g.game_code",'=', $game_code],
             ]);
        }
        $result= $game_details->first();
         return $result;
}
}
