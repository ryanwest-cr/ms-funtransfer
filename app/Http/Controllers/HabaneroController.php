<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ProviderHelper;
use DB;
use App\Helpers\Helper;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class HabaneroController extends Controller
{
    
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
        }else{
            
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
                    "currencycode" => "USD"
                ]
            ];
        }

        return $response;
    }


    public function responsetosend($client_access_token,$client_api_key,$game_code,$game_name,$client_player_id,$player_token,$amount,$client,$fund_transfer_url,$transtype,$currency,$rollback=false){
        $requesttosend = [
            "access_token" => $client_access_token,
            "hashkey" => md5($client_api_key.$client_access_token),
            "type" => "fundtransferrequest",
            "datesent" => Helper::datesent(),
            "gamedetails" => [
            "gameid" => $game_code, // $game_code
            "gamename" => $game_name
            ],
            "fundtransferrequest" => [
                "playerinfo" => [
                "client_player_id" => $client_player_id,
                "token" => $player_token,
                ],
                "fundinfo" => [
                        "gamesessionid" => "",
                        "transactiontype" => $transtype,
                        "transferid" => "",
                        "rollback" => $rollback,
                        "currencycode" => $currency,
                        "amount" => $amount
                ],
            ],
        ];
        
        $guzzle_response = $client->post($fund_transfer_url,
            ['body' => json_encode($requesttosend)]
        );

        $client_response = json_decode($guzzle_response->getBody()->getContents());
        $data = [
            'requesttosend' => $requesttosend,
            'client_response' => $client_response,
        ];
        return $data;
    }
    
    public function fundtransferrequest(Request $request){
        $data = file_get_contents("php://input");
        $details = json_decode($data);
        
        $client_details = Providerhelper::getClientDetails('token', $details->fundtransferrequest->token);

        $checktoken = Helper::tokenCheck($client_details->player_token);

        if($checktoken == false){ #check if session expire 1hour duration 

            $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
            $game_details = Helper::findGameDetails('game_code', 24, $details->basegame->keyname);
    
            $client = new Client([
                'headers' => [ 
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$client_details->client_access_token
                ]
            ]);

            if($details->fundtransferrequest->isrefund == true){ // check if refund is true

                $clientDetalsResponse = $this->responsetosend($client_details->client_access_token, $client_details->client_api_key, $game_details->game_code, $game_details->game_name, $client_details->client_player_id, $client_details->player_token, abs($details->fundtransferrequest->funds->refund->amount) , $client, $client_details->fund_transfer_url, "credit",$client_details->default_currency, true);

                $response = [
                    "fundtransferresponse" => [
                        "status" => [
                            "success" => true,
                            "refundstatus" => 1,
                        ],
                        "balance" => $clientDetalsResponse['client_response']->fundtransferresponse->balance,
                        "currencycode" => $client_details->default_currency,
                    ]
                ];

            

                $game_ins_id = $details->fundtransferrequest->gameinstanceid;
                $refund_update = DB::table('game_transactions')->where('provider_trans_id','=',$game_ins_id)->update(['win' => '4']);
                $game_trans_id = DB::table('game_transactions')->where('provider_trans_id','=',$game_ins_id)->get();

                $transaction_detail = [
                    'game_trans_id' => $game_trans_id[0]->game_trans_id,
                    'bet_amount' => abs($details->fundtransferrequest->funds->refund->amount),
                    'payout' => $game_trans_id[0]->pay_amount,
                    'refund' => true,
                    'response' => $response,
                ];

                $game_transextension = $this->createGameTransExt($game_trans_id[0]->game_trans_id, $details->fundtransferrequest->gameinstanceid, $details->fundtransferrequest->gameinstanceid, $game_trans_id[0]->pay_amount, 3, $data, $response, $clientDetalsResponse['requesttosend'], $clientDetalsResponse['client_response'], $transaction_detail);

                
            }else{

                $response = [
                    "fundtransferresponse" => [
                        "status" => [
                            "success" => false,
                            "autherror" => true,
                        ]
                    ]
                ];

            }
        }else{

        
            $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
            $game_details = Helper::findGameDetails('game_code', 24, $details->basegame->keyname);
    
            $client = new Client([
                'headers' => [ 
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$client_details->client_access_token
                ]
            ]);
            $array = [
                "client_details" => $client_details,
                "player_details" => $player_details,
                "game_details" => $game_details,
            ];
            if($details->fundtransferrequest->isrefund == true){ // check if refund is true

                $clientDetalsResponse = $this->responsetosend($client_details->client_access_token, $client_details->client_api_key, $game_details->game_code, $game_details->game_name, $client_details->client_player_id, $client_details->player_token, abs($details->fundtransferrequest->funds->refund->amount) , $client, $client_details->fund_transfer_url, "credit",$client_details->default_currency, true);

                $response = [
                    "fundtransferresponse" => [
                        "status" => [
                            "success" => true,
                            "refundstatus" => 1,
                        ],
                        "balance" => $clientDetalsResponse['client_response']->fundtransferresponse->balance,
                        "currencycode" => $client_details->default_currency,
                    ]
                ];

            

                $game_ins_id = $details->fundtransferrequest->gameinstanceid;
                $refund_update = DB::table('game_transactions')->where('provider_trans_id','=',$game_ins_id)->update(['win' => '4']);
                $game_trans_id = DB::table('game_transactions')->where('provider_trans_id','=',$game_ins_id)->get();

                $transaction_detail = [
                    'game_trans_id' => $game_trans_id[0]->game_trans_id,
                    'bet_amount' => abs($details->fundtransferrequest->funds->refund->amount),
                    'payout' => $game_trans_id[0]->pay_amount,
                    'refund' => true,
                    'response' => $response,
                ];

                $game_transextension = $this->createGameTransExt($game_trans_id[0]->game_trans_id, $details->fundtransferrequest->gameinstanceid, $details->fundtransferrequest->gameinstanceid, $game_trans_id[0]->pay_amount, 3, $data, $response, $clientDetalsResponse['requesttosend'], $clientDetalsResponse['client_response'], $transaction_detail);

                
            }else{ // else refund is false

                if($details->fundtransferrequest->funds->fundinfo[0]->isbonus == true ){ // check if bunos is true

                    $payout =abs($details->fundtransferrequest->funds->fundinfo[0]->amount);

                    $freeSpin = $this->responsetosend($client_details->client_access_token, $client_details->client_api_key, $game_details->game_code, $game_details->game_name, $client_details->client_player_id, $client_details->player_token, abs($details->fundtransferrequest->funds->fundinfo[0]->amount) , $client, $client_details->fund_transfer_url, "credit",$client_details->default_currency);

                    $response = [
                        "fundtransferresponse" => [
                            "status" => [
                                "success" => true,
                                "successdebit" => true,
                                "successcredit" => true
                            ],
                            "balance" => $freeSpin['client_response']->fundtransferresponse->balance,
                            "currencycode" => $client_details->default_currency,
                        ]
                    ];

                    $income = $payout;  
                    
                    $jpwin = $details->fundtransferrequest->funds->fundinfo[0]->jpwin;
                    $bonuswin = $details->fundtransferrequest->funds->fundinfo[0]->isbonus;
                    
                    

                    $transaction_detail = [
                        'bet_amount' => 0.00,
                        'payout' => $payout,
                        'response' => $response,
                        'update' => true,
                        'jackpotwin' => $jpwin,
                        'bonuswin' => $bonuswin
                    ];

                    $gamerecord = $this->createGameTransaction($client_details->token_id, $game_details->game_id, 0.00, $payout, 2,  1, null, null, $income, $details->fundtransferrequest->gameinstanceid, $details->fundtransferrequest->gameinstanceid);
                    $game_transextension = $this->createGameTransExt( $gamerecord, $details->fundtransferrequest->gameinstanceid, $details->fundtransferrequest->gameinstanceid, $payout, 2, $data, $response, $freeSpin['requesttosend'], $freeSpin['client_response'], $transaction_detail);
                
                }else{ // check if bonus is false
                    
                    $check_game_trans = DB::table('game_transactions')->where("provider_trans_id","=",$details->fundtransferrequest->gameinstanceid)->get();

                    if(count($check_game_trans) > 0){ //check if game round is exist

                        if($details->fundtransferrequest->funds->fundinfo[0]->amount < 0 && $details->fundtransferrequest->funds->fundinfo[0]->gamestatemode == 0){ // checking for "double" in table games

                            $bet_amount = $check_game_trans[0]->bet_amount + abs($details->fundtransferrequest->funds->fundinfo[0]->amount);

                            $freeSpin = $this->responsetosend($client_details->client_access_token, $client_details->client_api_key, $game_details->game_code, $game_details->game_name, $client_details->client_player_id, $client_details->player_token, abs($details->fundtransferrequest->funds->fundinfo[0]->amount) , $client, $client_details->fund_transfer_url, "debit",$client_details->default_currency);
                            $response = [
                                "fundtransferresponse" => [
                                    "status" => [
                                        "success" => true,
                                        "successdebit" => true,
                                        "successcredit" => true
                                    ],
                                    "balance" => $freeSpin['client_response']->fundtransferresponse->balance,
                                    "currencycode" => $client_details->default_currency,
                                ]
                            ];
    
                            $income = $bet_amount;  
                            
                            $jpwin = $details->fundtransferrequest->funds->fundinfo[0]->jpwin;
                            $bonuswin = $details->fundtransferrequest->funds->fundinfo[0]->isbonus;
                            
                            
                            $update = DB::table('game_transactions')->where("game_trans_id","=",$check_game_trans[0]->game_trans_id)->update(["bet_amount" => $bet_amount, "pay_amount" => 0.00, "income" => $income, "win" => 0 ]);
    
                            $transaction_detail = [
                                'game_code' => $check_game_trans[0]->game_trans_id,
                                'bet_amount' => $bet_amount,
                                'payout' => 0.00,
                                'response' => $response,
                                'update' => true,
                                'jackpotwin' => $jpwin,
                                'bonuswin' => $bonuswin,
                                'double' => true
                            ];
    
    
                            $game_transextension = $this->createGameTransExt($check_game_trans[0]->game_trans_id, $details->fundtransferrequest->gameinstanceid, $details->fundtransferrequest->gameinstanceid, 0.00, $check_game_trans[0]->entry_id, $data, $response, $freeSpin['requesttosend'], $freeSpin['client_response'], $transaction_detail);

                        }else if($details->fundtransferrequest->isretry == true && $details->fundtransferrequest->isrecredit == true){

                            $payout = $check_game_trans[0]->pay_amount;

                            $response = [
                                "fundtransferresponse" => [
                                    "status" => [
                                        "success" => true,
                                    ],
                                    "balance" => $player_details->playerdetailsresponse->balance,
                                    "currencycode" => $client_details->default_currency,
                                ]
                            ];

                            $game_transextension = $this->createGameTransExt($check_game_trans[0]->game_trans_id, $details->fundtransferrequest->gameinstanceid, $details->fundtransferrequest->gameinstanceid, $payout, $check_game_trans[0]->entry_id, $data, $response, "isretry", "isrecredit", "isrecredit");

                            return $response;

                        }else{ 

                            $payout = $check_game_trans[0]->pay_amount + abs($details->fundtransferrequest->funds->fundinfo[0]->amount);

                            $entry_id = $payout == 0 ? 1 : 2;
                            $entry = $payout == 0 ? 'debit' : 'credit';

                            $freeSpin = $this->responsetosend($client_details->client_access_token, $client_details->client_api_key, $game_details->game_code, $game_details->game_name, $client_details->client_player_id, $client_details->player_token, abs($details->fundtransferrequest->funds->fundinfo[0]->amount) , $client, $client_details->fund_transfer_url, $entry,$client_details->default_currency);

                            $response = [
                                "fundtransferresponse" => [
                                    "status" => [
                                        "success" => true,
                                        "successdebit" => true,
                                        "successcredit" => true
                                    ],
                                    "balance" => $freeSpin['client_response']->fundtransferresponse->balance,
                                    "currencycode" => $client_details->default_currency,
                                ]
                            ];
    
                            $income = $check_game_trans[0]->bet_amount - $payout;  
                            
                            $jpwin = $details->fundtransferrequest->funds->fundinfo[0]->jpwin;
                            $bonuswin = $details->fundtransferrequest->funds->fundinfo[0]->isbonus;
                            
                            $win = $payout == 0 ? 0 : 1;

                            $update = DB::table('game_transactions')->where("game_trans_id","=",$check_game_trans[0]->game_trans_id)->update(["pay_amount" => $payout, "income" => $income, "win" => $win, "entry_id" => $entry_id ]);
    
                            $transaction_detail = [
                                'game_code' => $check_game_trans[0]->game_trans_id,
                                'bet_amount' => $check_game_trans[0]->bet_amount,
                                'payout' => $payout,
                                'response' => $response,
                                'update' => true,
                                'jackpotwin' => $jpwin,
                                'bonuswin' => $bonuswin,
                                'double' => false
                            ];
    
    
                            $game_transextension = $this->createGameTransExt($check_game_trans[0]->game_trans_id, $details->fundtransferrequest->gameinstanceid, $details->fundtransferrequest->gameinstanceid, $payout, $check_game_trans[0]->entry_id, $data, $response, $freeSpin['requesttosend'], $freeSpin['client_response'], $transaction_detail);
                        }


                    }else{ // new game transactions

                        if($player_details->playerdetailsresponse->balance > abs($details->fundtransferrequest->funds->fundinfo[0]->amount)){
            
                        
                            if($details->fundtransferrequest->funds->debitandcredit == 'true'){
                                
                                foreach($details->fundtransferrequest->funds->fundinfo as $funds){
                                    if($funds->gamestatemode == 1){
                                        $clientDetalsResponse = $this->responsetosend($client_details->client_access_token, $client_details->client_api_key, $game_details->game_code, $game_details->game_name, $client_details->client_player_id, $client_details->player_token, abs($funds->amount) , $client, $client_details->fund_transfer_url, "debit",$client_details->default_currency);
                                        $bet_amount = abs($funds->amount);
                                    }else{
                                        $clientDetalsResponse = $this->responsetosend($client_details->client_access_token, $client_details->client_api_key, $game_details->game_code, $game_details->game_name, $client_details->client_player_id, $client_details->player_token, abs($funds->amount) , $client, $client_details->fund_transfer_url, "credit",$client_details->default_currency);
                                        $payout = abs($funds->amount);
                                    }
                                }
            
                                
                                $response = [
                                    "fundtransferresponse" => [
                                        "status" => [
                                            "success" => true,
                                            "successdebit" => true,
                                            "successcredit" => true
                                        ],
                                        "balance" => $clientDetalsResponse['client_response']->fundtransferresponse->balance,
                                        "currencycode" => $client_details->default_currency,
                                    ]
                                ];
                                $income = $bet_amount - $payout;
                                $win = $payout > 0 ? 1 : 0;
                                $entry_id = $win == 0 ? '1' : '2';
            
                                $gamerecord = $this->createGameTransaction($client_details->token_id, $game_details->game_id, $bet_amount, $payout, $entry_id,  $win, null, null, $income, $details->fundtransferrequest->gameinstanceid, $details->fundtransferrequest->gameinstanceid);
                                
                                $transaction_detail = [
                                    'game_code' => $gamerecord,
                                    'bet_amount' => $bet_amount,
                                    'payout' => $payout,
                                    'response' => $response,
                                    'debitcreddit' => true
                                ];
            
            
                                $game_transextension = $this->createGameTransExt($gamerecord, $details->fundtransferrequest->gameinstanceid, $details->fundtransferrequest->gameinstanceid, $payout, $entry_id, $data, $response, $clientDetalsResponse['requesttosend'], $clientDetalsResponse['client_response'], $transaction_detail);
            
                            }else{
                                

                                $clientDetalsResponse = $this->responsetosend($client_details->client_access_token, $client_details->client_api_key, $game_details->game_code, $game_details->game_name, $client_details->client_player_id, $client_details->player_token, abs($details->fundtransferrequest->funds->fundinfo[0]->amount) , $client, $client_details->fund_transfer_url, "debit",$client_details->default_currency);
                                    
                                $response = [
                                    "fundtransferresponse" => [
                                        "status" => [
                                            "success" => true,
                                        ],
                                        "balance" => $clientDetalsResponse['client_response']->fundtransferresponse->balance,
                                        "currencycode" => $client_details->default_currency
                                    ]
                                ];
            
                                $bet_amount = abs($details->fundtransferrequest->funds->fundinfo[0]->amount);
                                $payout = 0;
                                $entry_id = 1;
                                $win = 0;
                                $income = $bet_amount;
                                
                                $gamerecord = $this->createGameTransaction($client_details->token_id, $game_details->game_id, $bet_amount, $payout, $entry_id,  $win, null, null, $income, $details->fundtransferrequest->gameinstanceid, $details->fundtransferrequest->gameinstanceid);
            
                                $transaction_detail = [
                                    'game_code' => $gamerecord,
                                    'bet_amount' => $bet_amount,
                                    'payout' => $payout,
                                    'response' => $response,
                                    'debitcredit' => false
                                ];
            
                                $game_transextension = $this->createGameTransExt($gamerecord, $details->fundtransferrequest->gameinstanceid, $details->fundtransferrequest->gameinstanceid, $payout, $entry_id, $data, $response, $clientDetalsResponse['requesttosend'], $clientDetalsResponse['client_response'], $transaction_detail);
                            }
            
                        }else{
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
                        }

                    }

                }
            }

        }    
        Helper::saveLog('fundtransferrequest', 47,$data,$response);
        return $response;
    }
    public function queryrequest(Request $request){
        $data = file_get_contents("php://input");
        $details = json_decode($data);

        $queryRequest = DB::table("game_transactions")->where("provider_trans_id","=",$details->queryrequest->gameinstanceid)->get();
        Helper::saveLog('queryrequest HBN', 47,$data," ");
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
