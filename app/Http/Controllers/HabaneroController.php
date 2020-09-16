<?php

namespace App\Http\Controllers;

use App\Helpers\ClientRequestHelper;
use Illuminate\Http\Request;
use App\Helpers\ProviderHelper;
use DB;
use App\Helpers\Helper;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class HabaneroController extends Controller
{
    
    public $passkey ;
    public $provider_id = 24; //provider_id from database
    public function __construct(){
    	$this->passkey = config('providerlinks.habanero.passKey');
    }

    public static function sessionExpire($token){
		$token = DB::table('player_session_tokens')
			        ->select("*", DB::raw("NOW() as IMANTO"))
			    	->where('player_token', $token)
			    	->first();
		if($token != null){
			$check_token = DB::table('player_session_tokens')
			->selectRaw("TIME_TO_SEC(TIMEDIFF( NOW(), '".$token->created_at."'))/60 as `time`")
			->first();
		    if(1440 > $check_token->time) {  // TIMEGAP IN MINUTES!
		        $token = true; // True if Token can still be used!
		    }else{
		    	$token = false; // Expired Token
		    }
		}else{
			$token = false; // Not Found Token
		}
	    return $token;
	}

    public function playerdetailrequest(Request $request){
     

        $data = file_get_contents("php://input");
        $details = json_decode($data);
        
        $client_details = Providerhelper::getClientDetails('token', $details->playerdetailrequest->token);
        if($client_details == null) {
            $response = [
                "playerdetailresponse" => [
                    "status" => [
                        "success" => false,
                        "message" => "Player does not exist"
                    ]
                ]
            ];
            Helper::saveLog('HBN player not exist', 24, json_encode($details), $response);
            return $response;
        }else{
            try{
                $player_details = Providerhelper::playerDetailsCall($client_details->player_token,true);
                $response = [
                    "playerdetailresponse" => [
                        "status" => [
                            "success" => true,
                            "autherror" => false,
                            "message" => ""
                        ],
                        "accountid" => $client_details->player_id,
                        "accountname" => $client_details->username,
                        "balance" => $player_details->playerdetailsresponse->balance,
                        "currencycode" => $client_details->default_currency
                    ]
                ];
                if($details->playerdetailrequest->gamelaunch == true):
                    Helper::saveLog('HBN auth', 24, json_encode($details), $response);
                endif;
                return $response;
            }catch(\Exception $e){
                $msg = array(
                    'message' => $e->getMessage(),
                );
                Helper::saveLog('HBN auth error', $this->provider_id, json_encode($details,JSON_FORCE_OBJECT), $msg);
                return json_encode($msg, JSON_FORCE_OBJECT); 
            }
        }
    }


    public function fundtransferrequest(Request $request){
        $data = file_get_contents("php://input");
        $details = json_decode($data);
        Helper::saveLog('HBN request --------', 24, json_encode($details),"request");
        $client_details = Providerhelper::getClientDetails('token', $details->fundtransferrequest->token);

        $checktoken = $this->sessionExpire($client_details->player_token);
        if($details->auth->passkey != $this->passkey){
            $response = [
                "fundtransferresponse" => [
                    "status" => [
                        "success" => false,
                        "message" => "Passkey don't match!"
                    ]
                ]
            ];
            Helper::saveLog('HBN trans passkey', 24, json_encode($details), $response);
            return $response;
        }
        if($checktoken == false): //session check
            $response = [
                "fundtransferresponse" => [
                    "status" => [
                        "success" => false,
                        "autherror" => true,
                    ]
                ]
            ];
            Helper::saveLog('HBN trans session', 24, json_encode($details), $response);
            return $response;
        endif;

        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
        $game_details = Helper::findGameDetails('game_code', $this->provider_id, $details->basegame->keyname);
        
        $token_id = $client_details->token_id;
        $game_id = $game_details->game_id;
        $game_name = $game_details->game_name;
        $game_code = $details->basegame->keyname;
        $bet_amount = abs($details->fundtransferrequest->funds->fundinfo[0]->amount);
        $payout = 0;
        $entry_id = 1;
        $income = 0;
        
        $refund = $details->fundtransferrequest->isrefund;
        $gamestatemode = $details->fundtransferrequest->funds->fundinfo[0]->gamestatemode;

        $debitandcredit = $details->fundtransferrequest->funds->debitandcredit;
        $provider_trans_id = $details->fundtransferrequest->gameinstanceid;
        $round_id = $details->fundtransferrequest->funds->fundinfo[0]->transferid;

        if($player_details->playerdetailsresponse->balance < $bet_amount):
            $response = [
                "fundtransferresponse" => [
                    "status" => [
                        "success" => false,
                        "nofunds" => true,
                    ],
                    "balance" => $player_details->playerdetailsresponse->balance,
                    "currencycode" => $client_details->default_currency
                ]
            ];
            Helper::saveLog('HBN trans balance not enough', 24, json_encode($details), $response);
            return $response;
        endif;

        $checkTrans = DB::table('game_transactions')->where('provider_trans_id','=',$provider_trans_id)->get();
     
        if(count($checkTrans) > 0):
            $checkT = DB::table('game_transactions')->where('provider_trans_id','=',$provider_trans_id)->where('round_id','=',$round_id)->get();
            $getTransExt = DB::table('game_transaction_ext')->where('game_trans_id','=',$checkTrans[0]->game_trans_id)->get();
            if($refund == true):

                if(count($checkT) > 0):
                    $response = [
                        "fundtransferresponse" => [
                            "status" => [
                                "success" => true,
                                "refundstatus" => 1,
                            ],
                            "balance" => floatval(number_format($player_details->playerdetailsresponse->balance, 2, '.', '')),
                            "currencycode" => $client_details->default_currency,
                        ]
                    ];  
                    Helper::saveLog('HBN trans duplicate call if refund', 24, json_encode($details), $response);
                    return $response;
                endif;

                $refund_amount = abs($details->fundtransferrequest->funds->refund->amount);
                $client_response = ClientRequestHelper::fundTransfer($client_details, $refund_amount, $game_code, $game_name, $getTransExt[0]->game_trans_ext_id , $checkTrans[0]->game_trans_id, 'credit', true);
                try{
                    $response = [
                        "fundtransferresponse" => [
                            "status" => [
                                "success" => true,
                                "refundstatus" => 1,
                            ],
                            "balance" => floatval(number_format($client_response->fundtransferresponse->balance, 2, '.', '')),
                            "currencycode" => $client_details->default_currency,
                        ]
                    ];     
                    $refund_update = DB::table('game_transactions')->where('game_trans_id','=',$checkTrans[0]->game_trans_id)->update(['win' => '4', 'transaction_reason' => 'refund']);
                    $refund_update = DB::table('game_transaction_ext')->where('game_trans_ext_id','=',$getTransExt[0]->game_trans_ext_id)->update(["amount" => $refund_amount,"game_transaction_type" => 2,"provider_request" => json_encode($details),"mw_response" => json_encode($response),"mw_request" =>$client_response->requestoclient,"client_response" => json_encode($client_response),"transaction_detail" => json_encode($response) ]);
                    Helper::saveLog('HBN trans refund', 24, json_encode($details), $response);
                    return $response;
                }catch(\Exception $e){
                    $msg = array(
                        'message' => $e->getMessage(),
                    );
                    Helper::saveLog('HBN trans refund error', $this->provider_id, json_encode($details,JSON_FORCE_OBJECT), $msg);
                    return json_encode($msg, JSON_FORCE_OBJECT); 
                }
            endif;
            
            
            $getTrans = DB::table('game_transactions')->where('game_trans_id','=',$checkTrans[0]->game_trans_id)->get();
            $isretry = $details->fundtransferrequest->isretry;
            $isrecredit = $details->fundtransferrequest->isrecredit;
            
            if($isretry == true && $isrecredit == true):
                
                if(count($checkT) > 0):
                    $response = [
                        "fundtransferresponse" => [
                            "status" => [
                                "success" => true,
                            ],
                            "balance" => floatval(number_format($player_details->playerdetailsresponse->balance, 2, '.', '')),
                            "currencycode" => $client_details->default_currency,
                        ]
                    ];  
                    Helper::saveLog('HBN trans duplicate call is retry and recredit', 24, json_encode($details), $response);
                    return $response;
                endif;
                $response = [
                    "fundtransferresponse" => [
                        "status" => [
                            "success" => true,
                        ],
                        "balance" => $player_details->playerdetailsresponse->balance,
                        "currencycode" => $client_details->default_currency,
                    ]
                ];
                $game_trans_ext = ProviderHelper::createGameTransExtV2($checkTrans[0]->game_trans_id, $provider_trans_id, $round_id, $bet_amount, $entry_id, json_encode($details), $response,"isretry", "isrecredit", "isrecredit" );
                Helper::saveLog('HBN trans failed', 24, json_encode($details), $response);
                return $response;
            endif;
            $amount = $details->fundtransferrequest->funds->fundinfo[0]->amount;
            if($amount < 0 && $gamestatemode == 0):
                if(count($checkT) > 0):
                    $response = [
                        "fundtransferresponse" => [
                            "status" => [
                                "success" => true,
                            ],
                            "balance" => floatval(number_format($player_details->playerdetailsresponse->balance, 2, '.', '')),
                            "currencycode" => $client_details->default_currency,
                        ]
                    ];  
                    Helper::saveLog('HBN trans duplicate call amt = 0 state = 2 amount <0 state = 0', 24, json_encode($details), $response);
                    return $response;
                endif;
                try{
                    $client_response = ClientRequestHelper::fundTransfer($client_details, $bet_amount, $game_code, $game_name, $getTransExt[0]->game_trans_ext_id, $checkTrans[0]->game_trans_id, 'debit');
                    $amount = $getTrans[0]->bet_amount + abs($amount);

                    $response = [
                        "fundtransferresponse" => [
                            "status" => [
                                "success" => true,
                            ],
                            "balance" => floatval(number_format($client_response->fundtransferresponse->balance, 2, '.', '')),
                            "currencycode" => $client_details->default_currency,
                        ]
                    ];
                    $update = DB::table('game_transactions')->where("game_trans_id","=",$checkTrans[0]->game_trans_id)->update(["round_id" => $round_id, "bet_amount" => $amount, "pay_amount" => 0.00, "income" => $amount, "win" => 0 ]);
                    $updateGameTransExt = DB::table('game_transaction_ext')->where('game_trans_ext_id','=',$getTransExt[0]->game_trans_ext_id)->update(["amount" => $amount,"game_transaction_type" => 2,"provider_request" => json_encode($details),"mw_response" => json_encode($response),"mw_request" => json_encode($client_response->requestoclient),"client_response" => json_encode($client_response),"transaction_detail" => json_encode($response) ]);
                    Helper::saveLog('HBN trans double', 24, json_encode($details), $response);
                    return $response;
                }catch(\Exception $e){
                    $msg = array(
                        'message' => $e->getMessage(),
                    );
                    Helper::saveLog('HBN trans double error', $this->provider_id, json_encode($details,JSON_FORCE_OBJECT), $msg);
                    return json_encode($msg, JSON_FORCE_OBJECT); 
                }
            endif;
            if($amount == 0 && $gamestatemode == 2):
                $response = [
                    "fundtransferresponse" => [
                        "status" => [
                            "success" => true,
                        ],
                        "balance" => floatval(number_format($player_details->playerdetailsresponse->balance, 2, '.', '')),
                        "currencycode" => $client_details->default_currency,
                    ]
                ];  
                Helper::saveLog('HBN trans duplicate call amt = 0 state = 2', 24, json_encode($details), $response);
                return $response;
            endif;
            if($amount > 0 && $gamestatemode == 0 ):
                if(count($checkT) > 0):
                    $response = [
                        "fundtransferresponse" => [
                            "status" => [
                                "success" => true,
                            ],
                            "balance" => floatval(number_format($player_details->playerdetailsresponse->balance, 2, '.', '')),
                            "currencycode" => $client_details->default_currency,
                        ]
                    ];  
                    $update = DB::table('game_transactions')->where("game_trans_id","=",$checkTrans[0]->game_trans_id)->update(["round_id" => $round_id ]);
                    Helper::saveLog('HBN trans duplicate call amoung > 0 state = 0', 24, json_encode($details), $response);
                    return $response;
                endif;
                try{
                    $game_trans_ext_v2 = ProviderHelper::createGameTransExtV2( $checkTrans[0]->game_trans_id, $provider_trans_id, $round_id, $bet_amount, $entry_id);

                    $client_response = ClientRequestHelper::fundTransfer($client_details, $bet_amount, $game_code, $game_name, $game_trans_ext_v2, $checkTrans[0]->game_trans_id, 'credit');
                    $amounts = $getTrans[0]->bet_amount + $amount;

                    $response = [
                        "fundtransferresponse" => [
                            "status" => [
                                "success" => true,
                            ],
                            "balance" => floatval(number_format($client_response->fundtransferresponse->balance, 2, '.', '')),
                            "currencycode" => $client_details->default_currency,
                        ]
                    ];
                    $payout = $getTrans[0]->pay_amount + $amount;
                    $income = $checkTrans[0]->bet_amount - $payout;
                    $win = $amount > 0 ? 1 : 0;
                    $entry_id = $win == 0 ? '1' : '2';

                    $update = DB::table('game_transactions')->where("game_trans_id","=",$checkTrans[0]->game_trans_id)->update(["round_id" => $round_id, "pay_amount" => $payout, "income" => $income, "win" => $win, "entry_id" => $entry_id ]);
                    $updateGameTransExt = DB::table('game_transaction_ext')->where('game_trans_ext_id','=',$game_trans_ext_v2)->update(["amount" => $amount,"game_transaction_type" => $entry_id,"provider_request" => json_encode($details),"mw_response" => json_encode($response),"mw_request" => json_encode($client_response->requestoclient),"client_response" => json_encode($client_response),"transaction_detail" => json_encode($response) ]);
                    Helper::saveLog('HBN trans win', 24, json_encode($details), $response);
                    return $response;
                }catch(\Exception $e){
                    $msg = array(
                        'message' => $e->getMessage(),
                    );
                    Helper::saveLog('HBN trans win error', $this->provider_id, json_encode($details,JSON_FORCE_OBJECT), $msg);
                    return json_encode($msg, JSON_FORCE_OBJECT); 
                }
            endif;
            if($amount > 0 && $gamestatemode == 2 ):
                if(count($checkT) > 0):
                    $response = [
                        "fundtransferresponse" => [
                            "status" => [
                                "success" => true,
                            ],
                            "balance" => floatval(number_format($player_details->playerdetailsresponse->balance, 2, '.', '')),
                            "currencycode" => $client_details->default_currency,
                        ]
                    ];  
                    Helper::saveLog('HBN trans duplicate call amt > 0 and state = 2', 24, json_encode($details), $response);
                    return $response;
                endif;
                try{
                    $game_trans_ext_v2 = ProviderHelper::createGameTransExtV2( $checkTrans[0]->game_trans_id, $provider_trans_id, $round_id, $bet_amount, $entry_id);

                    $client_response = ClientRequestHelper::fundTransfer($client_details, $bet_amount, $game_code, $game_name, $game_trans_ext_v2, $checkTrans[0]->game_trans_id, 'credit');
                    $amount = $getTrans[0]->pay_amount + $amount;

                    $response = [
                        "fundtransferresponse" => [
                            "status" => [
                                "success" => true,
                            ],
                            "balance" => floatval(number_format($client_response->fundtransferresponse->balance, 2, '.', '')),
                            "currencycode" => $client_details->default_currency,
                        ]
                    ];
                    $income = $checkTrans[0]->bet_amount - $amount;
                    $win = $amount > 0 ? 1 : 0;
                    $entry_id = $win == 0 ? '1' : '2';

                    $update = DB::table('game_transactions')->where("game_trans_id","=",$checkTrans[0]->game_trans_id)->update(["round_id" => $round_id, "pay_amount" => $amount, "income" => $income, "win" => $win, "entry_id" => $entry_id ]);
                    $updateGameTransExt = DB::table('game_transaction_ext')->where('game_trans_ext_id','=',$game_trans_ext_v2)->update(["amount" => $amount,"game_transaction_type" => $entry_id,"provider_request" => json_encode($details),"mw_response" => json_encode($response),"mw_request" => json_encode($client_response->requestoclient),"client_response" => json_encode($client_response),"transaction_detail" => json_encode($response) ]);
                    Helper::saveLog('HBN trans win', 24, json_encode($details), $response);
                    return $response;
                }catch(\Exception $e){
                    $msg = array(
                        'message' => $e->getMessage(),
                    );
                    Helper::saveLog('HBN trans win error', $this->provider_id, json_encode($details,JSON_FORCE_OBJECT), $msg);
                    return json_encode($msg, JSON_FORCE_OBJECT); 
                }
            endif;
            if(count($checkT) > 0):
                $response = [
                    "fundtransferresponse" => [
                        "status" => [
                            "success" => true,
                        ],
                        "balance" => floatval(number_format($player_details->playerdetailsresponse->balance, 2, '.', '')),
                        "currencycode" => $client_details->default_currency,
                    ]
                ];  
                Helper::saveLog('HBN trans duplicate call if all', 24, json_encode($details), $response);
                return $response;
            endif;
        endif;
        $checkTrans2 = DB::table('game_transactions')->where('provider_trans_id','=',$provider_trans_id)->where('round_id','=',$round_id)->get();
        if(!count($checkTrans2) > 0):
            $gamerecord = $this->createGameTransaction($token_id, $game_id, $bet_amount, $payout, $entry_id, 0, null, null, $income, $provider_trans_id, $round_id);
            $game_trans_ext = ProviderHelper::createGameTransExtV2($gamerecord, $provider_trans_id, $round_id, $bet_amount, $entry_id);
            if($details->fundtransferrequest->funds->fundinfo[0]->isbonus == true ):
                $client_response = ClientRequestHelper::fundTransfer($client_details, $bet_amount, $game_code, $game_name, $game_trans_ext, $gamerecord, 'credit');
                $response = [
                    "fundtransferresponse" => [
                        "status" => [
                            "success" => true,
                            "successdebit" => true,
                            "successcredit" => true
                        ],
                        "balance" => floatval(number_format($client_response->fundtransferresponse->balance, 2, '.', '')),
                        "currencycode" => $client_details->default_currency,
                    ]
                ];
               

                $updateGameTrans = DB::table('game_transactions')->where('game_trans_id','=',$gamerecord)->update([ "round_id" => $round_id,"bet_amount" => 0.00, "win" => 1 , "pay_amount" => $bet_amount, "income" => $bet_amount, "entry_id" => 2, "transaction_reason" => "Bonus Win" ]);
                $updateGameTransExt = DB::table('game_transaction_ext')->where('game_trans_ext_id','=',$game_trans_ext)->update(["amount" => $payout,"game_transaction_type" => $entry_id,"provider_request" => json_encode($details),"mw_response" => json_encode($response),"mw_request" => json_encode($client_response->requestoclient),"client_response" => json_encode($client_response),"transaction_detail" => json_encode($response) ]);
                Helper::saveLog('HBN trans bunos', 24, json_encode($details), $response);
                return $response;
                
            endif;
            if($debitandcredit == 'true'):
                try{
                    $fundinfo =  $details->fundtransferrequest->funds->fundinfo;
                    foreach($fundinfo as $funds):
                        if($funds->gamestatemode == 1){ //debit
                            $client_response = ClientRequestHelper::fundTransfer($client_details, abs($funds->amount), $game_code, $game_name, $game_trans_ext, $gamerecord, 'debit');
                            $bet_amount = abs($funds->amount);
                        }else{ // credit
                            $client_response = ClientRequestHelper::fundTransfer($client_details, abs($funds->amount), $game_code, $game_name, $game_trans_ext, $gamerecord, 'credit');
                            $payout = abs($funds->amount);
                        }
                    endforeach;
                    
                    $response = [
                        "fundtransferresponse" => [
                            "status" => [
                                "success" => true,
                                "successdebit" => true,
                                "successcredit" => true
                            ],
                            "balance" => floatval(number_format($client_response->fundtransferresponse->balance, 2, '.', '')),
                            "currencycode" => $client_details->default_currency,
                            ]
                    ];
                    $income = $bet_amount - $payout;
                    $win = $payout > 0 ? 1 : 0;
                    $entry_id = $win == 0 ? '1' : '2';
                    
                    $updateGameTrans = DB::table('game_transactions')->where('game_trans_id','=',$gamerecord)->update([ "round_id" => $round_id,"win" => $win, "pay_amount" => $payout, "income" => $income, "entry_id" => $entry_id ]);
                    $updateGameTransExt = DB::table('game_transaction_ext')->where('game_trans_ext_id','=',$game_trans_ext)->update(["amount" => $payout,"game_transaction_type" => $entry_id,"provider_request" => json_encode($details),"mw_response" => json_encode($response),"mw_request" => json_encode($client_response->requestoclient),"client_response" => json_encode($client_response),"transaction_detail" => json_encode($response) ]);
                    Helper::saveLog($win == 0 ? 'HBN trans loss':'HBN trans win' , 24, json_encode($details), $response);
                    return $response;
                }catch(\Exception $e){
                    $msg = array(
                        'message' => $e->getMessage(),
                    );
                    Helper::saveLog('HBN trans Win error', $this->provider_id, json_encode($details,JSON_FORCE_OBJECT), $msg);
                    return json_encode($msg, JSON_FORCE_OBJECT); 
                }
            else:
                
                    try{
                        $client_response = ClientRequestHelper::fundTransfer($client_details, $bet_amount, $game_code, $game_name, $game_trans_ext, $gamerecord, 'debit');
                        $response = [
                            "fundtransferresponse" => [
                                "status" => [
                                    "success" => true,
                                ],
                                "balance" => floatval(number_format($client_response->fundtransferresponse->balance, 2, '.', '')),
                                "currencycode" => $client_details->default_currency,
                                ]
                            ];
                        $updateGameTrans = DB::table('game_transactions')->where('game_trans_id','=',$gamerecord)->update([ "win" => 0, "pay_amount" => 0.00, "income" => $bet_amount, "entry_id" => 1 ]);
                        $updateGameTransExt = DB::table('game_transaction_ext')->where('game_trans_ext_id','=',$game_trans_ext)->update(["amount" => $bet_amount,"game_transaction_type" => 1,"provider_request" => json_encode($details),"mw_response" => json_encode($response),"mw_request" => json_encode($client_response->requestoclient),"client_response" => json_encode($client_response),"transaction_detail" => json_encode($response) ]);
                        Helper::saveLog('HBN trans loss', 24, json_encode($details), $response);
                        return $response;
                    }catch(\Exception $e){
                        $msg = array(
                            'message' => $e->getMessage(),
                        );
                        Helper::saveLog('HBN trans loss error', $this->provider_id, json_encode($details,JSON_FORCE_OBJECT), $msg);
                        return json_encode($msg, JSON_FORCE_OBJECT); 
                    }
                
            endif;
        endif; //end of check trans
    }

    public function queryrequest(Request $request){
        $data = file_get_contents("php://input");
        $details = json_decode($data);

        $queryRequest = DB::table("game_transactions")->where("provider_trans_id","=",$details->queryrequest->gameinstanceid)->get();
        Helper::saveLog('queryrequest HBN', $this->provider_id,$data," ");
        if(count($queryRequest) > 0){
            $response = [
                "fundtransferresponse" => [
                    "status" => [
                        "success" => true,
                    ]
                ]
            ];
        }else{
            $response = [
                "fundtransferresponse" => [
                    "status" => [
                        "success" => false,
                    ]
                ]
            ];
        }

        return $response;
    }

    public static function createGameTransaction($token_id, $game_id, $bet_amount, $payout, $entry_id,  $win=0, $transaction_reason = null, $payout_reason = null , $income=null, $provider_trans_id=null, $round_id=1) {
		$data = [
					"token_id" => $token_id,
					"game_id" => $game_id,
					"round_id" => $round_id,
					"bet_amount" => $bet_amount,
					"provider_trans_id" => $provider_trans_id,
					"pay_amount" => $payout,
					"income" => $income,
					"entry_id" => $entry_id,
					"win" => $win,
					"transaction_reason" => $transaction_reason,
					"payout_reason" => $payout_reason
				];
		$data_saved = DB::table('game_transactions')->insertGetId($data);
		return $data_saved;
	}
    
    public function createGameTransExt($game_trans_id, $provider_trans_id, $round_id, $amount, $game_type, $provider_request, $mw_response, $mw_request, $client_response, $transaction_detail){

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
			"transaction_detail" =>json_encode($transaction_detail),
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gametransactionext;

	}
    
}
