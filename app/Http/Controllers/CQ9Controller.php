<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use App\Helpers\AWSHelper;
use App\Helpers\GameLobby;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;
use DB;

class CQ9Controller extends Controller
{

	public $api_url, $api_token, $provider_db_id;

	// /gameboy/player/logout
	// /gameboy/game/list/cq9
	// /gameboy/game/halls

	public function __construct(){
    	$this->api_url = config('providerlinks.cqgames.api_url');
    	$this->api_token = config('providerlinks.cqgames.api_token');
    	$this->provider_db_id = config('providerlinks.cqgames.pdbid');
    }

    public function checkAuth($wtoken){
    	if($wtoken == $this->api_token){
    		return true;
    	}else{
    		return false;
    	}
    }

    // Adding Games!
	public function getGameList(){
		$client = new Client([
            'headers' => [ 
                'Authorization' => $this->api_token,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        ]);
        $response = $client->get($this->api_url.'/gameboy/game/halls');
        // $response = $client->get($this->api_url.'/gameboy/game/list/cq9');
        $game_list = json_decode((string)$response->getBody(), true);
        return $game_list;

  //       $data2 = array();
  //       foreach($game_list['data'] as $key){
  //         if($key['gametype'] == 'slot'){
  //         	$gametype = 1;
  //         }elseif($key['gametype'] == 'table'){
  //         	$gametype = 5;
  //     	  }elseif($key['gametype'] == 'fish'){
  //         	$gametype = 9;
  //         }else{
  //         	$gametype = 8; // arcade
  //         }
  //         $game = array(
  //             "game_type_id"=>$gametype,
  //             "provider_id"=> 30,
  //             "sub_provider_id"=> 54,
  //             "game_name"=> $key['gamename'],
  //             "game_code"=> $key["gamecode"],
  //             "icon"=> 'https://logopond.com/logos/a3134d028cc2ecd3b3f6cc4ff20947cd.png'
  //         );
  //         array_push($data2,$game);
  //       }
  //       DB::table('games')->insert($data2);
  //       return 'ok';
	}

	public function playerLogout(){
		$client = new Client([
            'headers' => [ 
                'Authorization' => $this->api_token,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        ]);
        $response = $client->post($this->$api_url.'/gameboy/player/logout', [
            'form_params' => [
                'account'=> 'player_id',
            ],
        ]);
        $logout = json_decode((string)$response->getBody(), true);
        return $logout;
	}

    public function CheckPlayer(Request $request, $account){
    	// $header = $request->header('Authorization');
    	$header = $request->header('wtoken');
    	// Helper::saveLog('CQ9 Check Player', $this->provider_db_id, json_encode($request->all()), $header);
    	$check_wtoken = $this->checkAuth($header);
    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details != null){
    		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
			$data = [
	    		"data" => true,
	    		"status" => [
	    			"code" => "0",
	    			"message" => 'Success',
	    			"datetime" => date(DATE_RFC3339)
	    		]
	    	];
    	}else{
    		$data = [
	    		"data" => false,
	    		"status" => [
	    			"code" => "0",
	    			"message" => 'Success',
	    			"datetime" => date(DATE_RFC3339)
	    		]
	    	];
    	}
    	// Helper::saveLog('CQ9 Check Player', $this->provider_db_id, json_encode($request->all()), $data);
    	return $data;
    }

    public function CheckBalance(Request $request, $account){
    	// Helper::saveLog('CQ9 Balance Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details != null){
    		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
			$data = [
	    		"data" => [
	    			"balance" => ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance),
	    			"currency" => $client_details->default_currency,
	    		],
	    		"status" => [
	    			"code" => "0",
	    			"message" => 'Success',
	    			"datetime" => date(DATE_RFC3339)
	    		]
	    	];
    	}else{
    		$data = [
	    		"data" => false,
	    		"status" => [
	    			"code" => "0",
	    			"message" => 'Success',
	    			"datetime" => date(DATE_RFC3339)
	    		]
	    	];
    	}
    	// Helper::saveLog('CQ9 Balance Player', $this->provider_db_id, json_encode($request->all()), $data);
    	return $data;
    }


    public function playerBet(Request $request){
    	Helper::saveLog('CQ9 playerBet Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	$provider_request = $request->all();
    	$account = $request->account;
    	$gamecode = $request->gamecode;
    	$gamehall = $request->gamehall;
    	$roundid = $request->roundid;
    	$amount = $request->amount;
    	$mtcode = $request->mtcode;

    	$check_wtoken = $this->checkAuth($header);
    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}

    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($amount < 0){
   			$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
   			$mw_response = [
	    		"data" => [
		    			"balance" => ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => [
		    			"code" => "1003",
		    			"message" => 'Amount cannot be negative!',
		    			"datetime" => date(DATE_RFC3339)
		    		]
	    	];
			return $mw_response;
   		}
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
		// if($game_details == null){}
		$game_ext_check = ProviderHelper::findGameExt($mtcode, 1, 'transaction_id');
		if($game_ext_check != 'false'){
			$mw_response = ["data" => [],"status" => [
	    			"code" => "2009",
	    			"message" => 'Transactiop duplicate',
	    			"datetime" => date(DATE_RFC3339)
	    		]
	    	];
			Helper::saveLog('CQ9 T ALready Exist', $this->provider_db_id, $provider_request, $mw_response);
			return $mw_response;
		}	

		try {
			$token_id = $client_details->token_id;
			$bet_amount = $amount;
			$pay_amount= 0;
			$method = 1;
			$win_or_lost = 5;
			$payout_reason = 'BET';
			$income = $amount;
			$provider_trans_id = $mtcode;
			$game_transaction_type = 1;
			$game_id = $game_details->game_id;

			$client = new Client([
			    'headers' => [ 
			    	'Content-Type' => 'application/json',
			    	'Authorization' => 'Bearer '.$client_details->client_access_token
			    ]
			]);
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
						"token" => $client_details->player_token,
					  ],
					  "fundinfo" => [
						      "gamesessionid" => "",
						      "transactiontype" => 'debit',
						      "transferid" => "",
						      "rollback" => false,
						      "currencycode" => $client_details->currency,
						      "amount" => abs($amount)
					   ],
				  ],
			];
			$guzzle_response = $client->post($client_details->fund_transfer_url,
			    ['body' => json_encode($requesttosend)]
			);
		    $client_response = json_decode($guzzle_response->getBody()->getContents());
		    $mw_response = [
	    		"data" => [
	    			"balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
	    			"currency" => $client_details->default_currency,
	    		],
	    		"status" => [
	    			"code" => "0",
	    			"message" => 'Success',
	    			"datetime" => date(DATE_RFC3339)
	    		]
	    	];
			$gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_id, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $roundid);
		    $game_transextension = ProviderHelper::createGameTransExt($gamerecord,$provider_trans_id, $roundid, $amount, $game_transaction_type, $provider_request, $mw_response, $requesttosend, $client_response, $mw_response);
			return $mw_response;
		} catch (\Exception $e) {
			$mw_response = [];
			Helper::saveLog('CQ9 playerBet Failed', $this->provider_db_id, json_encode($request->all()), $e->getMessage());
			return $mw_response;
		}
    }

    public function playrEndround(Request $request){
    	Helper::saveLog('CQ9 playrEndround Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	$provider_request = json_encode($request->all());
    	$data_details = ProviderHelper::rawToObj($request->data, true);
    	$account = $request->account;
    	$gamecode = $request->gamecode;
    	$gamehall = $request->gamehall;
    	$roundid = $request->roundid;

   //  	$check_wtoken = $this->checkAuth($header);
   //  	if(!$check_wtoken){
   //  		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			// Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			// return $mw_response;
   //  	}

    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
		// if($game_details == null){}
		$game_ext_check = ProviderHelper::findGameExt($roundid, 1, 'round_id');
		if($game_ext_check == 'false'){
			$mw_response = ["data" => [],"status" => [
	    			"code" => "1014",
	    			"message" => 'Transaction record not found',
	    			"datetime" => date(DATE_RFC3339)
	    		]
	    	];
			Helper::saveLog('CQ9 playrEndround ALready Exist', $this->provider_db_id, $provider_request, $mw_response);
			return $mw_response;
		}	
		$game_transaction = ProviderHelper::findGameTransaction($game_ext_check->game_trans_id, 'game_transaction');
		try {
			$client = new Client([
			    'headers' => [ 
			    	'Content-Type' => 'application/json',
			    	'Authorization' => 'Bearer '.$client_details->client_access_token
			    ]
			]);
	    	$total_amount = array();
	    	foreach($data_details as $data){
	    		if($data->amount < 0){
		   			$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
		   			$mw_response = [
			    		"data" => [
				    			"balance" => ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance),
				    			"currency" => $client_details->default_currency,
				    		],
				    		"status" => [
				    			"code" => "1003",
				    			"message" => 'Amount cannot be negative!',
				    			"datetime" => date(DATE_RFC3339)
				    		]
			    	];
					return $mw_response;
		   		}
	    		array_push($total_amount, $data->amount);
	    	}	
    		$total_amount = array_sum($total_amount);
	    	$token_id = $client_details->token_id;
			$pay_amount = $game_transaction->pay_amount + $total_amount;
			$payout_reason = 'ENDROUND WIN';
			$income = $game_transaction->bet_amount - $pay_amount;
			$provider_trans_id = $data->mtcode;
			if($total_amount > 0){
				$game_transaction_type = 2;
				$entry_id = 2;
				$win_or_lost = 1;
			}else{
				$game_transaction_type = 1;
				$entry_id = 1;
				$win_or_lost = 0;
			}
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
					"token" => $client_details->player_token,
				  ],
				  "fundinfo" => [
					      "gamesessionid" => "",
					      "transactiontype" => 'credit',
					      "transferid" => "",
					      "rollback" => false,
					      "currencycode" => $client_details->currency,
					      "amount" => $data->amount
				   ],
			  ],
			];
			$guzzle_response = $client->post($client_details->fund_transfer_url,
			    ['body' => json_encode($requesttosend)]
			);
		    $client_response = json_decode($guzzle_response->getBody()->getContents());
		    $mw_response = [
	    		"data" => [
		    			"balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => [
		    			"code" => "0",
		    			"message" => 'Success',
		    			"datetime" => date(DATE_RFC3339)
		    		]
	    	];
		    ProviderHelper::updateBetTransaction($game_transaction->round_id, $pay_amount, $income, $win_or_lost, $entry_id);
	 	    $game_transextension = ProviderHelper::createGameTransExt($game_ext_check->game_trans_id,$provider_trans_id, $roundid, $total_amount, $game_transaction_type, $provider_request, $mw_response, $requesttosend, $client_response, $mw_response);
			return $mw_response;
		} catch (\Exception $e) {
			$mw_response = [];
			Helper::saveLog('CQ9 playrEndround Failed', $this->provider_db_id, json_encode($request->all()), $e->getMessage());
			return $mw_response;
		}
    }

    public function playerCredit(Request $request){
    	Helper::saveLog('CQ9 playerCredit Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	$provider_request = json_encode($request->all());
    	$account = $request->account;
    	$gamecode = $request->gamecode;
    	$gamehall = $request->gamehall;
    	$roundid = $request->roundid;
    	$amount = $request->amount;
    	$mtcode = $request->mtcode;

    	$check_wtoken = $this->checkAuth($header);
    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}

    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
		// if($game_details == null){}
		$game_ext_check = ProviderHelper::findGameExt($mtcode, 1, 'transaction_id');
		if($game_ext_check != 'false'){
			$mw_response = ["data" => [],"status" => [
	    			"code" => "2009",
	    			"message" => 'Transactiop duplicate',
	    			"datetime" => date(DATE_RFC3339)
	    		]
	    	];
			Helper::saveLog('CQ9 playerCredit ALready Exist', $this->provider_db_id, $provider_request, $mw_response);
			return $mw_response;
		}	

		try {
			$token_id = $client_details->token_id;
			$bet_amount = $amount;
			$pay_amount= $amount;
			$method = 2;
			$win_or_lost = 1;
			$payout_reason = 'WIN';
			$income = $amount;
			$provider_trans_id = $mtcode;
			$game_transaction_type = 2;

			$client = new Client([
			    'headers' => [ 
			    	'Content-Type' => 'application/json',
			    	'Authorization' => 'Bearer '.$client_details->client_access_token
			    ]
			]);
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
						"token" => $client_details->player_token,
					  ],
					  "fundinfo" => [
						      "gamesessionid" => "",
						      "transactiontype" => 'credit',
						      "transferid" => "",
						      "rollback" => false,
						      "currencycode" => $client_details->currency,
						      "amount" => abs($amount)
					   ],
				  ],
			];
			$guzzle_response = $client->post($client_details->fund_transfer_url,
			    ['body' => json_encode($requesttosend)]
			);
		    $client_response = json_decode($guzzle_response->getBody()->getContents());
		    $mw_response = [
	    		"data" => [
	    			"balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
	    			"currency" => $client_details->default_currency,
	    		],
	    		"status" => [
	    			"code" => "0",
	    			"message" => 'Success',
	    			"datetime" => date(DATE_RFC3339)
	    		]
	    	];
			$gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_id, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $roundid);
		    $game_transextension = ProviderHelper::createGameTransExt($gamerecord,$provider_trans_id, $roundid, $amount, $game_transaction_type, $provider_request, $mw_response, $requesttosend, $client_response, $mw_response);
			return $mw_response;
		} catch (\Exception $e) {
			$mw_response = [];
			Helper::saveLog('CQ9 playerCredit Failed', $this->provider_db_id, json_encode($request->all()), $e->getMessage());
			return $mw_response;
		}
    }

    public function playerDebit(Request $request){
    	Helper::saveLog('CQ9 playerDebit Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	$provider_request = json_encode($request->all());
    	$account = $request->account;
    	$gamecode = $request->gamecode;
    	$gamehall = $request->gamehall;
    	$roundid = $request->roundid;
    	$amount = $request->amount;
    	$mtcode = $request->mtcode;

    	$check_wtoken = $this->checkAuth($header);
    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}

    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
		// if($game_details == null){}
		$game_ext_check = ProviderHelper::findGameExt($mtcode, 1, 'transaction_id');
		if($game_ext_check != 'false'){
			$mw_response = ["data" => [],"status" => [
	    			"code" => "2009",
	    			"message" => 'Transactiop duplicate',
	    			"datetime" => date(DATE_RFC3339)
	    		]
	    	];
			Helper::saveLog('CQ9 playerDebit ALready Exist', $this->provider_db_id, $provider_request, $mw_response);
			return $mw_response;
		}	

		try {
			$token_id = $client_details->token_id;
			$bet_amount = $amount;
			$pay_amount= 0;
			$method = 1;
			$win_or_lost = 0;
			$payout_reason = 'BET';
			$income = $amount;
			$provider_trans_id = $mtcode;
			$game_transaction_type = 1;

			$client = new Client([
			    'headers' => [ 
			    	'Content-Type' => 'application/json',
			    	'Authorization' => 'Bearer '.$client_details->client_access_token
			    ]
			]);
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
						"token" => $client_details->player_token,
					  ],
					  "fundinfo" => [
						      "gamesessionid" => "",
						      "transactiontype" => 'debit',
						      "transferid" => "",
						      "rollback" => false,
						      "currencycode" => $client_details->currency,
						      "amount" => abs($amount)
					   ],
				  ],
			];
			$guzzle_response = $client->post($client_details->fund_transfer_url,
			    ['body' => json_encode($requesttosend)]
			);
		    $client_response = json_decode($guzzle_response->getBody()->getContents());
		    $mw_response = [
	    		"data" => [
	    			"balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
	    			"currency" => $client_details->default_currency,
	    		],
	    		"status" => [
	    			"code" => "0",
	    			"message" => 'Success',
	    			"datetime" => date(DATE_RFC3339)
	    		]
	    	];
			$gamerecord  = ProviderHelper::createGameTransaction($token_id, $gamecode, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $roundid);
		    $game_transextension = ProviderHelper::createGameTransExt($gamerecord,$provider_trans_id, $roundid, $amount, $game_transaction_type, $provider_request, $mw_response, $requesttosend, $client_response, $mw_response);
			return $mw_response;
		} catch (\Exception $e) {
			$mw_response = [];
			Helper::saveLog('CQ9 playerDebit Failed', $this->provider_db_id, json_encode($request->all()), $e->getMessage());
			return $mw_response;
		}
    }

    public function playerRollout(Request $request){
    	Helper::saveLog('CQ9 playerRollout Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	$provider_request = $request->all();
    	$account = $request->account;
    	$gamecode = $request->gamecode;
    	$gamehall = $request->gamehall;
    	$roundid = $request->roundid;
    	$amount = $request->amount;
    	$mtcode = $request->mtcode;
   //  	$check_wtoken = $this->checkAuth($header);
   //  	if(!$check_wtoken){
   //  		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			// Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			// return $mw_response;
   //  	}

    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
		// if($game_details == null){}
		$game_ext_check = ProviderHelper::findGameExt($mtcode, 1, 'transaction_id');
		if($game_ext_check != 'false'){
			$mw_response = ["data" => [],"status" => [
	    			"code" => "2009",
	    			"message" => 'Transactiop duplicate',
	    			"datetime" => date(DATE_RFC3339)
	    		]
	    	];
			Helper::saveLog('CQ9 T ALready Exist', $this->provider_db_id, $provider_request, $mw_response);
			return $mw_response;
		}	
		try {
			$token_id = $client_details->token_id;
			$bet_amount = $amount;
			$pay_amount= 0;
			$method = 1;
			$win_or_lost = 5;
			$payout_reason = 'BET';
			$income = $amount;
			$provider_trans_id = $mtcode;
			$game_transaction_type = 1;
			$game_id = $game_details->game_id;

			$client = new Client([
			    'headers' => [ 
			    	'Content-Type' => 'application/json',
			    	'Authorization' => 'Bearer '.$client_details->client_access_token
			    ]
			]);
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
						"token" => $client_details->player_token,
					  ],
					  "fundinfo" => [
						      "gamesessionid" => "",
						      "transactiontype" => 'debit',
						      "transferid" => "",
						      "rollback" => false,
						      "currencycode" => $client_details->currency,
						      "amount" => abs($amount)
					   ],
				  ],
			];
			$guzzle_response = $client->post($client_details->fund_transfer_url,
			    ['body' => json_encode($requesttosend)]
			);
		    $client_response = json_decode($guzzle_response->getBody()->getContents());
		    $mw_response = [
	    		"data" => [
	    			"balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
	    			"currency" => $client_details->default_currency,
	    		],
	    		"status" => [
	    			"code" => "0",
	    			"message" => 'Success',
	    			"datetime" => date(DATE_RFC3339)
	    		]
	    	];
			$gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_id, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $roundid);
		    $game_transextension = ProviderHelper::createGameTransExt($gamerecord,$provider_trans_id, $roundid, $amount, $game_transaction_type, $provider_request, $mw_response, $requesttosend, $client_response, $mw_response);
			return $mw_response;
		} catch (\Exception $e) {
			$mw_response = [];
			Helper::saveLog('CQ9 playerRollout Failed', $this->provider_db_id, json_encode($request->all()), $e->getMessage());
			return $mw_response;
		}
    }


    public function playerTakeall(Request $request){
    	Helper::saveLog('CQ9 playerTakeall Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	$provider_request = json_encode($request->all());
    	$data_details = ProviderHelper::rawToObj($request->data, true);
    	$account = $request->account;
    	$gamecode = $request->gamecode;
    	$gamehall = $request->gamehall;
    	$roundid = $request->roundid;

   //  	$check_wtoken = $this->checkAuth($header);
   //  	if(!$check_wtoken){
   //  		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			// Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			// return $mw_response;
   //  	}

    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
		// if($game_details == null){}
		$game_ext_check = ProviderHelper::findGameExt($roundid, 1, 'round_id');
		if($game_ext_check == 'false'){
			$mw_response = ["data" => [],"status" => [
	    			"code" => "1014",
	    			"message" => 'Transaction record not found',
	    			"datetime" => date(DATE_RFC3339)
	    		]
	    	];
			Helper::saveLog('CQ9 playerTakeall ALready Exist', $this->provider_db_id, $provider_request, $mw_response);
			return $mw_response;
		}	
		$game_transaction = ProviderHelper::findGameTransaction($game_ext_check->game_trans_id, 'game_transaction');
		try {
			$client = new Client([
			    'headers' => [ 
			    	'Content-Type' => 'application/json',
			    	'Authorization' => 'Bearer '.$client_details->client_access_token
			    ]
			]);
	    	$total_amount = array();
	    	foreach($data_details as $data){
	    		array_push($total_amount, $data->amount);
	    	}	
    		$total_amount = array_sum($total_amount);
	    	$token_id = $client_details->token_id;
			$pay_amount = $game_transaction->pay_amount + $total_amount;
			$payout_reason = 'ENDROUND WIN';
			$income = $game_transaction->bet_amount - $pay_amount;
			$provider_trans_id = $data->mtcode;
			if($total_amount > 0){
				$game_transaction_type = 2;
				$entry_id = 2;
				$win_or_lost = 1;
			}else{
				$game_transaction_type = 1;
				$entry_id = 1;
				$win_or_lost = 0;
			}
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
					"token" => $client_details->player_token,
				  ],
				  "fundinfo" => [
					      "gamesessionid" => "",
					      "transactiontype" => 'credit',
					      "transferid" => "",
					      "rollback" => false,
					      "currencycode" => $client_details->currency,
					      "amount" => $data->amount
				   ],
			  ],
			];
			$guzzle_response = $client->post($client_details->fund_transfer_url,
			    ['body' => json_encode($requesttosend)]
			);
		    $client_response = json_decode($guzzle_response->getBody()->getContents());
		    $mw_response = [
	    		"data" => [
		    			"balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => [
		    			"code" => "0",
		    			"message" => 'Success',
		    			"datetime" => date(DATE_RFC3339)
		    		]
	    	];
		    ProviderHelper::updateBetTransaction($game_transaction->round_id, $pay_amount, $income, $win_or_lost, $entry_id);
	 	    $game_transextension = ProviderHelper::createGameTransExt($game_ext_check->game_trans_id,$provider_trans_id, $roundid, $total_amount, $game_transaction_type, $provider_request, $mw_response, $requesttosend, $client_response, $mw_response);
			return $mw_response;
		} catch (\Exception $e) {
			$mw_response = [];
			Helper::saveLog('CQ9 playerTakeall Failed', $this->provider_db_id, json_encode($request->all()), $e->getMessage());
			return $mw_response;
		}
    }

    public function playerRollin(Request $request){
    	Helper::saveLog('CQ9 playerRollin Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	$provider_request = $request->all();
    	$account = $request->account;
    	$gamecode = $request->gamecode;
    	$gamehall = $request->gamehall;
    	$roundid = $request->roundid;
    	$amount = $request->amount;
    	$mtcode = $request->mtcode;
   //  	$check_wtoken = $this->checkAuth($header);
   //  	if(!$check_wtoken){
   //  		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			// Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			// return $mw_response;
   //  	}
   //  	
   		

    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($amount < 0){
   			$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
   			$mw_response = [
	    		"data" => [
		    			"balance" => ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => [
		    			"code" => "1003",
		    			"message" => 'Amount cannot be negative!',
		    			"datetime" => date(DATE_RFC3339)
		    		]
	    	];
			return $mw_response;
   		}
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
		// if($game_details == null){}
		$game_ext_check = ProviderHelper::findGameExt($roundid, 1, 'round_id');
		if($game_ext_check == 'false'){
			$mw_response = ["data" => [],"status" => [
	    			"code" => "1014",
	    			"message" => 'Transaction record not found',
	    			"datetime" => date(DATE_RFC3339)
	    		]
	    	];
			Helper::saveLog('CQ9 playerRollin ALready Exist', $this->provider_db_id, $provider_request, $mw_response);
			return $mw_response;
		}	
		$game_transaction = ProviderHelper::findGameTransaction($game_ext_check->game_trans_id, 'game_transaction');
		// try {
			$client = new Client([
			    'headers' => [ 
			    	'Content-Type' => 'application/json',
			    	'Authorization' => 'Bearer '.$client_details->client_access_token
			    ]
			]);
	   
	    	$token_id = $client_details->token_id;
			$pay_amount =  $amount;
			$payout_reason = 'Roullout Fish Game';
			$income = $game_transaction->bet_amount - $pay_amount;
			$provider_trans_id = $mtcode;
			if($amount > $game_transaction->bet_amount){
				$game_transaction_type = 2;
				$entry_id = 2;
				$win_or_lost = 1;
			}else{
				$game_transaction_type = 1;
				$entry_id = 1;
				$win_or_lost = 0;
			}
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
					"token" => $client_details->player_token,
				  ],
				  "fundinfo" => [
					      "gamesessionid" => "",
					      "transactiontype" => 'credit',
					      "transferid" => "",
					      "rollback" => false,
					      "currencycode" => $client_details->currency,
					      "amount" => $amount
				   ],
			  ],
			];
			$guzzle_response = $client->post($client_details->fund_transfer_url,
			    ['body' => json_encode($requesttosend)]
			);
		    $client_response = json_decode($guzzle_response->getBody()->getContents());
		    $mw_response = [
	    		"data" => [
		    			"balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => [
		    			"code" => "0",
		    			"message" => 'Success',
		    			"datetime" => date(DATE_RFC3339)
		    		]
	    	];
		    ProviderHelper::updateBetTransaction($game_transaction->round_id, $pay_amount, $income, $win_or_lost, $entry_id);
	 	    $game_transextension = ProviderHelper::createGameTransExt($game_ext_check->game_trans_id,$provider_trans_id, $roundid, $amount, $game_transaction_type, $provider_request, $mw_response, $requesttosend, $client_response, $mw_response);
			return $mw_response;
		// } catch (\Exception $e) {
		// 	$mw_response = [];
		// 	Helper::saveLog('CQ9 playerRollin Failed', $this->provider_db_id, json_encode($request->all()), $e->getMessage());
		// 	return $mw_response;
		// }
    }

    public function playerBonus(Request $request){
    	Helper::saveLog('CQ9 playerBonus Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	Helper::saveLog('CQ9 playerBonus Player', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT 2');
    }

    public function playerPayoff(Request $request){
    	Helper::saveLog('CQ9 playerPayoff Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	Helper::saveLog('CQ9 playerPayoff Player', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT 2');
    }

    public function playerRefund(Request $request){
    	Helper::saveLog('CQ9 playerRefund Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	Helper::saveLog('CQ9 playerRefund Player', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT 2');
    }

    public function playerRecord(Request $request){
    	Helper::saveLog('CQ9 playerRecord Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	Helper::saveLog('CQ9 playerRecord Player', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT 2');
    }
    
    public function playerBets(Request $request){
    	Helper::saveLog('CQ9 playerBets Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	Helper::saveLog('CQ9 playerBets Player', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT 2');
    }

     public function playerRefunds(Request $request){
    	Helper::saveLog('CQ9 playerRefunds Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	Helper::saveLog('CQ9 playerRefunds Player', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT 2');
    }


      public function playerCancel(Request $request){
    	Helper::saveLog('CQ9 playerCancel Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	Helper::saveLog('CQ9 playerCancel Player', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT 2');
    }

     public function playerAmend(Request $request){
    	Helper::saveLog('CQ9 playerAmend Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	Helper::saveLog('CQ9 playerAmend Player', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT 2');
    }


}
