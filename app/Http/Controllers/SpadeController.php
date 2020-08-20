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

class SpadeController extends Controller
{

	public $api_url, $merchant_id, $merchant_key = '';
	public $provider_db_id = 37;
	public $prefix = 'TIGERG';
	public $merchantCode = 'TIGERG';
	public $siteId = 'SITE_USD1';

    // public function __construct(){
    // 	$this->api_url = config('providerlinks.aws.api_url');
    // 	$this->merchant_id = config('providerlinks.aws.merchant_id');
    // 	$this->merchant_key = config('providerlinks.aws.merchant_key');
    // }

	public function getGameList(Request $request){
		$api = "https://api-egame-staging.sgplay.net/api";
		$requesttosend = [
			'serialNo' =>  $this->generateSerialNo(),
			'merchantCode' => $this->merchantCode,
			'currency' => 'USD'	
		];
		$client = new Client([
            'headers' => [ 
                'API' => "getGames",
                'DataType' => "JSON"
            ]
        ]);
		$guzzle_response = $client->post($api,['body' => json_encode($requesttosend)]);
		$client_response = json_decode($guzzle_response->getBody()->getContents());
		return json_encode($client_response);
	}

	public function index(Request $request){
		$interface_type = $request->header('API');
		$data = file_get_contents("php://input");
		$details = json_decode($data);
		Helper::saveLog('Spade authorize', $this->provider_db_id, json_encode($details), $interface_type);
		$acctId =  ProviderHelper::explodeUsername('_', $details->acctId);
		$client_details = Providerhelper::getClientDetails('player_id', $acctId);
		if($client_details == null){
			$response = [
				"msg" => "Acct Not Found",
				"code" => 50100
			];
			Helper::saveLog('Spade authorize error', $this->provider_db_id, json_encode($details), $response);
			return $response;
			
		}
		if($details->merchantCode != $this->merchantCode){
			$response = [
				"msg" => "Merchant Not Found",
				"code" => 10113
			];
			Helper::saveLog('Spade authorize error', $this->provider_db_id, json_encode($details), $response);
			return $response;
		}
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
		$response = [
			"acctInfo" => [
				"acctId" => $this->prefix.'_'.$acctId,
				"balance" => floatval(number_format((float)$player_details->playerdetailsresponse->balance, 2, '.', '')),
				"userName" => $this->prefix.$acctId,
				"currency" => $client_details->default_currency,
				"siteId" => $this->siteId
			],
			"merchantCode" => $this->merchantCode,"msg" => "success","code" => 0,"serialNo" => $this->generateSerialNo()
		];
		Helper::saveLog('Spade authorize process', $this->provider_db_id, json_encode($details), $response);
		return $response;
		// $interface_type = $request->header('API');
    	// $data = file_get_contents("php://input");
		// $details = json_decode($data);
		// Helper::saveLog('Spade index', $this->provider_db_id, json_encode($details), $interface_type);
		// if($details->merchantCode != $this->merchantCode){
		// 	$response = [
		// 		"msg" => "Merchant Not Found",
		// 		"code" => 10113
		// 	];
		// 	Helper::saveLog('Spade Transfer error', $this->provider_db_id,  json_encode($details), $response);
		// 	return $response;
		// }
		// if($interface_type == 'authorize'){
		// 	return $this->authorize($details);
		// }else if($interface_type == 'getBalance'){
		// 	return $this->getBalance($details);
		// }else if($details->type == 3){
		// 	return $this->makePayout($details);
		// }else if($details->type == 4){
		// 	return $this->spadeBunos($details);
		// }
	}
	
    public function authorize($details){
    	$data = file_get_contents("php://input");
		$details = json_decode($data);
		Helper::saveLog('Spade authorize', $this->provider_db_id, json_encode($details), "");
		$acctId =  ProviderHelper::explodeUsername('_', $details->acctId);
		$client_details = Providerhelper::getClientDetails('player_id', $acctId);
		if($client_details == null){
			$response = [
				"msg" => "Acct Not Found",
				"code" => 50100
			];
			Helper::saveLog('Spade authorize error', $this->provider_db_id, json_encode($details), $response);
			return $response;
			
		}
		if($details->merchantCode != $this->merchantCode){
			$response = [
				"msg" => "Merchant Not Found",
				"code" => 10113
			];
			Helper::saveLog('Spade authorize error', $this->provider_db_id, json_encode($details), $response);
			return $response;
		}
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
		$response = [
			"acctInfo" => [
				"acctId" => $this->prefix.'_'.$acctId,
				"balance" => floatval(number_format((float)$player_details->playerdetailsresponse->balance, 2, '.', '')),
				"userName" => $this->prefix.$acctId,
				"currency" => $client_details->default_currency,
				"siteId" => $this->siteId
			],
			"merchantCode" => $this->merchantCode,"msg" => "success","code" => 0,"serialNo" => $this->generateSerialNo()
		];
		Helper::saveLog('Spade authorize process', $this->provider_db_id, json_encode($details), $response);
		return $response;
    }

    public function getBalance(Request $request){
    	$data = file_get_contents("php://input");
		$details = json_decode($data);
		Helper::saveLog('Spade getBalance', $this->provider_db_id, json_encode($details), "");
		$acctId =  ProviderHelper::explodeUsername('_', $details->acctId);
		$client_details = Providerhelper::getClientDetails('player_id', $acctId);
		if($client_details == null){
			$response = [
				"msg" => "Acct Not Found",
				"code" => 50100
			];
			Helper::saveLog('Spade getBalance error', $this->provider_db_id, json_encode($details), $response);
			return $response;
			
		}
		if($details->merchantCode != $this->merchantCode){
			$response = [
				"msg" => "Merchant Not Found",
				"code" => 10113
			];
			Helper::saveLog('Spade getBalance error', $this->provider_db_id, json_encode($details), $response);
			return $response;
		}
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
    	$response = [
			"acctInfo" => [
				"acctId" => $this->prefix.'_'.$acctId,
				"balance" => floatval(number_format((float)$player_details->playerdetailsresponse->balance, 2, '.', '')),
				"userName" => $this->prefix.$acctId,
				"currency" => $client_details->default_currency
			],
			"merchantCode" => $this->merchantCode,"msg" => "success","code" => 0,"serialNo" => $this->generateSerialNo()
		];
		Helper::saveLog('Spade getBalance process', $this->provider_db_id, json_encode($details), $response);
		return $response;
    }

    public function generateSerialNo(){
    	// $guid = vsprintf('%s%s-%s-4000-8%.3s-%s%s%s0',str_split(dechex( microtime(true) * 1000 ) . bin2hex( random_bytes(8) ),4));
    	$guid = substr("abcdefghijklmnopqrstuvwxyz1234567890", mt_rand(0, 25), 1).substr(md5(time()), 1);;
    	return $guid;
    }

	/**
	 * @author's note 1 = place bet, 2 = cancel bet, 4 = payout, 7 = Bonus
	 */
    public function makeTransfer(Request $request){
    	$data = file_get_contents("php://input");
		$details = json_decode($data);
		if($details->merchantCode != $this->merchantCode){
			$response = [
				"msg" => "Merchant Not Found",
				"code" => 10113
			];
			Helper::saveLog('Spade Transfer error', $this->provider_db_id,  json_encode($details), $response);
			return $response;
		}
		if($details->type == 1){
			Helper::saveLog('Spade Bet', $this->provider_db_id, json_encode($details), "");
			return $this->placeBet($details);
		}else if($details->type == 2){
			return $this->cancelBet($details);
		}else if($details->type == 3){
			return $this->makePayout($details);
		}else if($details->type == 4){
			return $this->spadeBunos($details);
		}
    }

	public function placeBet($details){
			$serialNo = $this->generateSerialNo();
			$account = $details->acctId;
			$acctId =  ProviderHelper::explodeUsername('_', $account);
			$gameCode = $details->gameCode;
			$provider_trans_id =  $details->transferId;
			$roundid =  $details->ticketId;
			$default_currency =  $details->currency;
			$amount = $details->amount;
		    $client_details = Providerhelper::getClientDetails('player_id', $acctId);
			if($client_details == null){
				$response = [
					"msg" => "Acct Not Found",
					"code" => 50100
				];
				Helper::saveLog('Spade Bet error', $this->provider_db_id, json_encode($details), $response);
				return $response;
			}
			$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
			if($client_details->default_currency != $default_currency){
				$response = [
					"msg" => "Currency Invalid ",
					"code" => 50112
				];
				Helper::saveLog('Spade Bet error', $this->provider_db_id, json_encode($details), $response);
				return $response;
			}
			
			$trasaction_check = PRoviderHelper::findGameExt($provider_trans_id, 1, 'transaction_id');
			if($trasaction_check != 'false'){
				$response = [
					"msg" => "Invalid Parameters",
					"code" => 106
				];
				Helper::saveLog('Spade Bet error', $this->provider_db_id, json_encode($details), $response);
				return $response;
			}
			if($amount > $player_details->playerdetailsresponse->balance){
				$response = [
					"msg" => "Insufficient Balance",
					"code" => 50110
				];
				Helper::saveLog('Spade Bet error', $this->provider_db_id, json_encode($details), $response);
				return $response;
			}
			$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gameCode);
			$transaction_type = 'debit';
			$gameid = $game_details->game_id;
			$token_id = $client_details->token_id;
			$bet_amount = $amount;
			$pay_amount= 0;
			$income = $bet_amount - $pay_amount;
			$credit_debit = 1;
			$win_or_lost = 0;
			$payout_reason = 'BET';
			$provider_trans_id = $provider_trans_id;
			$roundid = $roundid;
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
						      "transactiontype" => $transaction_type,
						      "transferid" => "",
						      "rollback" => false,
						      "currencycode" => $client_details->currency,
						      "amount" => abs($details->amount)
					   ],
				  ],
			];
			try {
				$guzzle_response = $client->post($client_details->fund_transfer_url,
			   	 	['body' => json_encode($requesttosend)]
				);
				$client_response = json_decode($guzzle_response->getBody()->getContents());
				$gamerecord  = ProviderHelper::createGameTransaction($token_id, $gameid, $bet_amount,  $pay_amount, $credit_debit, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $roundid);
		        $response = [
					"transferId" => $gamerecord,
					"merchantCode" => $this->merchantCode,
					// "merchantTxId" => $gamerecord,
					"acctId" => $account,
					"balance" => floatval(number_format((float)$client_response->fundtransferresponse->balance, 2, '.', '')),
					"msg" => "success",
					"code" => 0,
					"serialNo" => $serialNo
				];
				$this->cretetransactionExt($gamerecord,$details,$requesttosend,$response,$client_response, $game_transaction_type, $bet_amount, $provider_trans_id, $roundid);
				Helper::saveLog('Spade Bet process', $this->provider_db_id, json_encode($details), $response);
				return $response;
			} catch (\Exception $e) {
				$response = [
					"msg" => "Request Timeout",
					"code" => 100
				];
				Helper::saveLog('Spade Failed Bet = '.$e->getMessage(), $this->provider_db_id, json_encode($details), $response);
				return $response;
			}
	}

	public function cancelBet($details){
		$serialNo = $this->generateSerialNo();
		$account = $details->acctId;
		$acctId =  ProviderHelper::explodeUsername('_', $account);
		$gameCode = $details->gameCode;
		$provider_trans_id =  $details->transferId;
		$roundid =  $details->referenceId;
		$default_currency =  $details->currency;
		$amount = $details->amount;
	    $client_details = Providerhelper::getClientDetails('player_id', $acctId);
		if($client_details == null){
			$response = [
				"acctInfo" => [],
				"merchantCode" => $this->merchantCode,"msg" => "Acct Not Found","code" => 50100,"serialNo" => $serialNo
			];
			Helper::saveLog('Spade Failed Payout = '.$serialNo, $this->provider_db_id, json_encode($details), $response);
			return $response;
		}
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
			if($client_details->default_currency != $default_currency){
			$response = [
				"acctInfo" => [],
				"merchantCode" => $this->merchantCode,"msg" => "Currency Invalid","code" => 50112,"serialNo" => $serialNo
			];
			Helper::saveLog('Spade Failed Payout = '.$serialNo, $this->provider_db_id, json_encode($details), $response);
			return $response;
		}
		$trasaction_check = PRoviderHelper::findGameExt($provider_trans_id, 2, 'transaction_id');
		if($trasaction_check != 'false'){
			$response = [
				"transferId" => $trasaction_check->game_trans_id,
				"merchantCode" => $this->merchantCode,
				// "merchantTxId" => $trasaction_check->game_trans_id,
				"acctId" => $account ,
				"balance" => floatval(number_format((float)$player_details->playerdetailsresponse->balance, 2, '.', '')),
				"msg" => "success (Duplicate TransferId)",
				"code" => 0,
				"serialNo" => $serialNo
			];
			Helper::saveLog('Spade Failed Payout = '.$serialNo, $this->provider_db_id, json_encode($details), $response);
			return $response;
		}
		$trasaction_check = PRoviderHelper::findGameExt($roundid, 1, 'transaction_id');
		// dd($trasaction_check);
		if($trasaction_check == 'false'){
			$response = [
				"acctInfo" => [],
				"merchantCode" => $this->merchantCode,"msg" => "Reference No. Not Found","code" => 109,"serialNo" => $serialNo
			];
			Helper::saveLog('Spade Failed Payout = '.$serialNo, $this->provider_db_id, json_encode($details), $response);
			return $response;
		}
		
		return 'hold-on';
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gameCode);
		$transaction_type = 'credit';
		$gameid = $game_details->game_id;
		$token_id = $client_details->token_id;
		$bet_amount = $trasaction_check->bet_amount;
		$pay_amount= $amount;
		$income = $bet_amount - $pay_amount;
		$credit_debit = 2;
		$win_or_lost = 1;
		$payout_reason = 'Payout/Win';
		$provider_trans_id = $provider_trans_id;
		$roundid = $roundid;
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
					      "transactiontype" => $transaction_type,
					      "transferid" => "",
					      "rollback" => false,
					      "currencycode" => $client_details->currency,
					      "amount" => abs($details->amount)
				   ],
			  ],
		];
		try {
			 $this->updateBetTransaction($roundid, $pay_amount, $income, $win_or_lost, $entry_id);
		 	 $game_transextension = ProviderHelper::createGameTransExt($game_ext_check->game_trans_id,$provider_trans_id, $roundid, $total_amount, $game_transaction_type, $provider_request, $mw_response, $client_response['requesttosend'], $client_response['client_response'], $mw_response, $general_details);
			//
		} catch (\Exception $e) {
			
		}
	}

	public function makePayout($details){
		$serialNo = $this->generateSerialNo();
		$account = $details->acctId;
		$acctId =  ProviderHelper::explodeUsername('_', $account);
		$gameCode = $details->gameCode;
		$provider_trans_id =  $details->transferId;
		$roundid =  $details->referenceId;
		$default_currency =  $details->currency;
		$amount = $details->amount;
	    $client_details = Providerhelper::getClientDetails('player_id', $acctId);
		if($client_details == null){
			$response = [
				"acctInfo" => [],
				"merchantCode" => $this->merchantCode,"msg" => "Acct Not Found","code" => 50100,"serialNo" => $serialNo
			];
			Helper::saveLog('Spade Failed Payout = '.$serialNo, $this->provider_db_id, json_encode($details), $response);
			return $response;
		}
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
			if($client_details->default_currency != $default_currency){
			$response = [
				"acctInfo" => [],
				"merchantCode" => $this->merchantCode,"msg" => "Currency Invalid","code" => 50112,"serialNo" => $serialNo
			];
			Helper::saveLog('Spade Failed Payout = '.$serialNo, $this->provider_db_id, json_encode($details), $response);
			return $response;
		}
		$trasaction_check = PRoviderHelper::findGameExt($provider_trans_id, 2, 'transaction_id');
		if($trasaction_check != 'false'){
			$response = [
				"transferId" => $trasaction_check->game_trans_id,
				"merchantCode" => $this->merchantCode,
				// "merchantTxId" => $trasaction_check->game_trans_id,
				"acctId" => $account ,
				"balance" => floatval(number_format((float)$player_details->playerdetailsresponse->balance, 2, '.', '')),
				"msg" => "success (Duplicate TransferId)",
				"code" => 0,
				"serialNo" => $serialNo
			];
			Helper::saveLog('Spade Failed Payout = '.$serialNo, $this->provider_db_id, json_encode($details), $response);
			return $response;
		}
		$trasaction_check = PRoviderHelper::findGameExt($roundid, 1, 'transaction_id');
		// dd($trasaction_check);
		if($trasaction_check == 'false'){
			$response = [
				"acctInfo" => [],
				"merchantCode" => $this->merchantCode,"msg" => "Reference No. Not Found","code" => 109,"serialNo" => $serialNo
			];
			Helper::saveLog('Spade Failed Payout = '.$serialNo, $this->provider_db_id, json_encode($details), $response);
			return $response;
		}
		
		return 'hold-on';
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gameCode);
		$transaction_type = 'credit';
		$gameid = $game_details->game_id;
		$token_id = $client_details->token_id;
		$bet_amount = $trasaction_check->bet_amount;
		$pay_amount= $amount;
		$income = $bet_amount - $pay_amount;
		$credit_debit = 2;
		$win_or_lost = 1;
		$payout_reason = 'Payout/Win';
		$provider_trans_id = $provider_trans_id;
		$roundid = $roundid;
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
					      "transactiontype" => $transaction_type,
					      "transferid" => "",
					      "rollback" => false,
					      "currencycode" => $client_details->currency,
					      "amount" => abs($details->amount)
				   ],
			  ],
		];
		try {
			 $this->updateBetTransaction($roundid, $pay_amount, $income, $win_or_lost, $entry_id);
		 	 $game_transextension = ProviderHelper::createGameTransExt($game_ext_check->game_trans_id,$provider_trans_id, $roundid, $total_amount, $game_transaction_type, $provider_request, $mw_response, $client_response['requesttosend'], $client_response['client_response'], $mw_response, $general_details);
			//
		} catch (\Exception $e) {
			
		}
	}

	public function spadeBunos($details){
		dd(1);
		return $details->type;
	}

	public  function updateBetTransaction($provider_trans_id, $pay_amount, $income, $win, $entry_id) {
   	    $update = DB::table('game_transactions')
                ->where('provider_trans_id', $provider_trans_id)
                ->update(['pay_amount' => $pay_amount, 
	        		  'income' => $income, 
	        		  'win' => $win, 
	        		  'entry_id' => $entry_id,
	        		  'transaction_reason' => ProviderHelper::updateReason($win),
	    		]);
		return ($update ? true : false);
	}

	public static function cretetransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response, $game_transaction_type, $amount=null, $provider_trans_id=null, $round_id=null){
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

}
