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

/**
 * @author's note : Provider has feature Front End and API Blocking IP
 * @author's note : There are two kinds of method in here Single Wallet Callback and Backoffice Calls
 * @author's note : Backoffice call for directly communicate to the Provider Backoffice!
 * @author's note : Single Wallet call is the main methods tobe checked!
 * @author's note : Username/Player is Prefixed with the merchant_id_TG(player_id)
 * @method  [playerRegister] Register The Player to AWS Provider (DEPRECATED CENTRALIZED) (BO USED)
 * @method  [launchGame] Request Gamelaunch (DEPRECATED CENTRALIZED) (BO USED)
 * @method  [gameList] List Of All Gamelist (DEPRECATED CENTRALIZED)
 * @method  [playerManage] Disable/Enable a player in AWS Backoffice
 * @method  [playerStatus] Check Player Status in AWS Backoffice (BO USED)
 * @method  [playerBalance] Check Player Balance in AWS Backoffice (BO USED)
 * @method  [fundTransfer] Transfer fund to Player in AWS Backoffice (BO USED) (NOT USED)
 * @method  [queryStatus] Check the fund stats of a Player in AWS Backoffice (BO USED) (NOT USED)
 * @method  [queryOrder] Check the order stats in AWS Backoffice (BO USED) (NOT USED)
 * @method  [playerLogout] Logout the player
 * @method  [singleBalance] Single Wallet : Check Player Balance
 * @method  [singleFundTransfer] Single Fund Transfer : Transfer fund Debit/Credit
 * @method  [singleFundQuery] Check Fund
 *
 * 01_User Registration, 02_User Enablement, 03_User Status, 07_Launch Game 26, 08_User Kick-out (BO METHOD WE ONLY NEED)
 */
class AWSController extends Controller
{

	public $api_url, $merchant_id, $merchant_key = '';
	public $provider_db_id = 21;

    public function __construct(){
    	$this->api_url = config('providerlinks.aws.api_url');
    	$this->merchant_id = config('providerlinks.aws.merchant_id');
    	$this->merchant_key = config('providerlinks.aws.merchant_key');
    }

    /**
	 * SINGLE WALLET
	 * @author's Signature combination for every callback
	 * @param [obj] $[details] [<json to obj>]
	 * @param [int] $[signature_type] [<1 = balance, 2 = fundtransfer, fundquery>] // SIGNATURE TYPE 2 REMOVED
	 *
	 */
    public function signatureCheck($details, $signature_type){
    	if($signature_type == 1){
    		$signature = md5($this->merchant_id.$details->currentTime.$details->accountId.$details->currency.base64_encode($this->merchant_key));
    	}elseif($signature_type == 2){
    		// $signature = false;
    		// $signature = array();
			// $signature_combo = [ // Only In Amount Sometimes has .0 sometimes it has nothing!
		  //   	'one' => $this->merchant_id.$details->currentTime.$details->amount.$details->accountId.$details->currency.$details->txnId.$details->txnTypeId.$details->gameId.base64_encode($this->merchant_key),
				// 'two' =>  $this->merchant_id.$details->currentTime.$details->amount.'0'.$details->accountId.$details->currency.$details->txnId.$details->txnTypeId.$details->gameId.base64_encode($this->merchant_key),
				// 'three' =>  $this->merchant_id.$details->currentTime.$details->amount.'.0'.$details->accountId.$details->currency.$details->txnId.$details->txnTypeId.$details->gameId.base64_encode($this->merchant_key),
				// 'four' =>  $this->merchant_id.$details->currentTime.$details->amount.'00'.$details->accountId.$details->currency.$details->txnId.$details->txnTypeId.$details->gameId.base64_encode($this->merchant_key)
				// 'one' => md5($this->merchant_id.$details->currentTime.$details->amount.$details->accountId.$details->currency.$details->txnId.$details->txnTypeId.$details->gameId.base64_encode($this->merchant_key)),
				// 'two' =>  md5($this->merchant_id.$details->currentTime.$details->amount.'0'.$details->accountId.$details->currency.$details->txnId.$details->txnTypeId.$details->gameId.base64_encode($this->merchant_key)),
				// 'three' =>  md5($this->merchant_id.$details->currentTime.$details->amount.'.0'.$details->accountId.$details->currency.$details->txnId.$details->txnTypeId.$details->gameId.base64_encode($this->merchant_key)),
				// 'four' =>  md5($this->merchant_id.$details->currentTime.$details->amount.'00'.$details->accountId.$details->currency.$details->txnId.$details->txnTypeId.$details->gameId.base64_encode($this->merchant_key))
			// ];
			// foreach ($signature_combo as $key) {
			// 	// array_push($signature, $key);
			// 	// if($key == $details->sign){
			// 	// 	$signature = $key;
			// 	// }
			// }
    	}

    	if($signature == $details->sign){
    		return true;
    	}else{
    		return false;
    	}
    }

	/**
	 * SINGLE WALLET
	 * @author's note : Player Single Balance 
	 *
	 */
	public function singleBalance(Request $request){
		$data = file_get_contents("php://input");
		$details = json_decode($data);

		Helper::saveLog('AWS Balance', 21, file_get_contents("php://input"), 'ENDPOINT HIT');
		$verify = $this->signatureCheck($details, 1);
		if(!$verify){
			$response = [
				"msg"=> "Sign check encountered error, please verify sign is correct",
				"code"=> 9200
			];
			Helper::saveLog('AWS Single Error Sign', $this->provider_db_id, $data, $response);
			return $response;
		}

		$prefixed_username = explode("_TG", $details->accountId);
		$client_details = Providerhelper::getClientDetails('player_id', $prefixed_username[1]);
		$provider_reg_currency = Providerhelper::getProviderCurrency($this->provider_db_id, $client_details->default_currency);
		if($provider_reg_currency == 'false'){
			$response = [
				"msg"=> "Currency not found",
				"code"=> 102
			];
			Helper::saveLog('AWS Single Currency Not Found', $this->provider_db_id, $data, $response);
			return $response;
		}

		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
		if($player_details != 'false'){
			$response = [
				"msg"=> "success",
				"code"=> 0,
				"data"=> [
					"currency"=> $client_details->default_currency,
					"balance"=> floatval(number_format((float)$player_details->playerdetailsresponse->balance, 2, '.', '')),
					"bonusBalance"=> 0
				]
			];
		}else{
			$response = [
				"msg"=> "User balance retrieval error",
				"code"=> 2211,
				"data"=> []
			];
		}
		return $response;
	}

	/**
	 * SINGLE WALLET
	 * @author's note : Player Single Fund Transfer 
	 * @author's note : Transfer amount, support 2 decimal places, negative number is withdraw/debit, positive number is deposit/credit
	 *
	 */
	public function singleFundTransfer(Request $request){
		$data = file_get_contents("php://input");
		$details = json_decode($data);

		Helper::saveLog('AWS Single Fund Transfer', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$prefixed_username = explode("_TG", $details->accountId);
		$client_details = Providerhelper::getClientDetails('player_id', $prefixed_username[1]);
		$explode1 = explode('"betAmount":', $data);
		$explode2 = explode('amount":', $explode1[0]);
		$amount_in_string = trim(str_replace(',', '', $explode2[1]));
		$amount_in_string = trim(str_replace('"', '', $amount_in_string));

		$signature = md5($this->merchant_id.$details->currentTime.$amount_in_string.$details->accountId.$details->currency.$details->txnId.$details->txnTypeId.$details->gameId.base64_encode($this->merchant_key));
		
		if($signature != $details->sign){
			$response = [
				"msg"=> "Sign check encountered error, please verify sign is correct",
				"code"=> 9200
			];
			Helper::saveLog('AWS Single Error Sign', $this->provider_db_id, $data, $response);
			return $response;
		}

		$provider_reg_currency = Providerhelper::getProviderCurrency($this->provider_db_id, $client_details->default_currency);
		if($provider_reg_currency == 'false'){
			$response = [
				"msg"=> "Currency not found",
				"code"=> 102
			];
			Helper::saveLog('AWS Single Currency Not Found', $this->provider_db_id, $data, $response);
			return $response;
		}

		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $details->gameId);
		if($game_details == null){
			$response = [
			"msg"=> "Game not found",
			"code"=> 1100
			];
			Helper::saveLog('AWS Single Fund Failed Game Not FOund', $this->provider_db_id, $data, $response);
			return $response;
		}

		$transaction_type = $details->amount > 0 ? 'credit' : 'debit';
		$game_transaction_type = $transaction_type == 'debit' ? 1 : 2; // 1 Bet, 2 Win

		$game_code = $game_details->game_id;
		$token_id = $client_details->token_id;
		$bet_amount = abs($details->betAmount);


		if($transaction_type == 'credit'){
			$pay_amount =  abs($details->amount);
			$income = $bet_amount - $pay_amount;
			$win_type = $income > 0 ? 0 : 1;
		}else{
			$pay_amount = $details->winAmount;
			$income = $bet_amount - $details->winAmount;
			$win_type = 0;
		}


		$method = $transaction_type == 'debit' ? 1 : 2;
		$win_or_lost = $win_type; // 0 lost,  5 processing
		$payout_reason = $this->getOperationType($details->txnTypeId);
		$provider_trans_id = $details->txnId;

		$game_ext_check = $this->findGameExt($details->txnId, $game_transaction_type, 'transaction_id');
		if($game_ext_check != 'false'){
			$response = [
			"msg"=> "marchantTransId already exist",
			"code"=> 2200,
			"data"=> [
					"currency"=> $client_details->default_currency,
					"balance"=> floatval(number_format((float)$player_details->playerdetailsresponse->balance, 2, '.', '')),
					"bonusBalance"=> 0
				]
			];
			Helper::saveLog('AWS Single Fund Failed', $this->provider_db_id, $data, $response);
			return $response;
		}
		if($transaction_type == 'debit'){
			if($bet_amount > $player_details->playerdetailsresponse->balance){
				$response = [
					"msg"=> "Insufficient balance",
					"code"=> 1201
				];
				Helper::saveLog('AWS Single Fund Failed', $this->provider_db_id, $data, $response);
				return $response;
			}
		}

		try {
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
			$guzzle_response = $client->post($client_details->fund_transfer_url,
			    ['body' => json_encode($requesttosend)]
			);
		    $client_response = json_decode($guzzle_response->getBody()->getContents());
			$response = [
				"msg"=> "success",
				"code"=> 0,
				"data"=> [
					"currency"=> $client_details->default_currency,
					"amount"=> (double)$details->amount,
					"accountId"=> $details->accountId,
					"txnId"=> $details->txnId,
					"eventTime"=> date('Y-m-d H:i:s'),
					"balance" => floatval(number_format((float)$client_response->fundtransferresponse->balance, 2, '.', '')),
					"bonusBalance" => 0
				]
			];
			$gamerecord  = $this->createGameTransaction($token_id, $game_code, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $provider_trans_id);
		    $game_transextension = $this->createGameTransExt($gamerecord,$provider_trans_id, $provider_trans_id, $pay_amount, $game_transaction_type, $details, $response, $requesttosend, $client_response, $response);
			return $response;
		} catch (Exception $e) {
			$response = [
				"msg"=> "Fund transfer encountered error",
				"code"=> 2205,
				// "data"=> [
				// 	"currency"=> $client_details->default_currency,
				// 	"amount"=> (double)$bet_amount,
				// 	"accountId"=> $details->accountId,
				// 	"txnId"=> $details->txnId,
				// 	"eventTime"=> date('Y-m-d H:i:s'),
				// ]
			];
			Helper::saveLog('AWS Single Fund Failed', $this->provider_db_id, $data, $response);
			return $response;
		}
	}

	/**
	 * SINGLE WALLET
	 * @author's note : PROVDER NOTE Query is prepare for if have transfer error, we can call "Query" to merchant to  make sure merchant received or not
	 * this function just check order not increase or decrease action.  (Debit/Credit)
	 *
	 */
	public function singleFundQuery(Request $request){
		$data = file_get_contents("php://input");
		$details = json_decode($data);
		Helper::saveLog('AWS Single Fund Query', $this->provider_db_id, $data, 'ENDPOINT HIT');

		$explode1 = explode('"betAmount":', $data);
		$explode2 = explode('amount":', $explode1[0]);
		$amount_in_string = trim(str_replace(',', '', $explode2[1]));
		$amount_in_string = trim(str_replace('"', '', $amount_in_string));
		$signature = md5($this->merchant_id.$details->currentTime.$amount_in_string.$details->accountId.$details->currency.$details->txnId.$details->txnTypeId.$details->gameId.base64_encode($this->merchant_key));
		
		if($signature != $details->sign){
			$response = [
				"msg"=> "Sign check encountered error, please verify sign is correct",
				"code"=> 9200
			];
			Helper::saveLog('AWS Single Error Sign', $this->provider_db_id, $data, $response);
			return $response;
		}

		$prefixed_username = explode("_TG", $details->accountId);
		$client_details = Providerhelper::getClientDetails('player_id', $prefixed_username[1]);
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);

		if($player_details == 'false'){
			$response = [
				"msg"=> "Fund transfer encountered error",
				"code"=> 2205
			];
			Helper::saveLog('AWS Single Error Sign', $this->provider_db_id, $data, $response);
			return $response;
		}

		$transaction_type = $details->amount > 0 ? 'credit' : 'debit';
		$game_transaction_type = $transaction_type == 'debit' ? 1 : 2; // 1 Bet, 2 Win
		$game_ext_check = $this->findGameExt($details->txnId, $game_transaction_type, 'transaction_id');
		// dd($game_ext_check);
		if($game_ext_check != 'false'){ // The Transaction Has Been Processed!
			$response = [
				"msg"=> "success",
				"code"=> 0,
				"data"=> [
					"currency"=> $client_details->default_currency,
					"amount"=> (double)$details->amount,
					"accountId"=> $details->accountId,
					"txnId"=> $details->txnId,
					"eventTime"=> date('Y-m-d H:i:s'),
					"balance" => floatval(number_format((float)$player_details->playerdetailsresponse->balance, 2, '.', '')),
					"bonusBalance" => 0
				]
			];
			return $response;
		}else{  // No Transaction Was Found 
			$response = [
			"msg"=> "Transfer history record not found",
			"code"=> 106,
			"data"=> [
					"currency"=> $client_details->default_currency,
					"amount"=> (double)$details->amount,
					"accountId"=> $details->accountId,
					"txnId"=> $details->txnId,
					"eventTime"=> date('Y-m-d H:i:s'),
					"balance" => floatval(number_format((float)$player_details->playerdetailsresponse->balance, 2, '.', '')),
					"bonusBalance" => 0
				]
			];
		}
		Helper::saveLog('AWS Single Fund Query', $this->provider_db_id, $data, $response);
		return $response;
	}

	/**
	 * MERCHANT BACKOFFICE
	 * @author's note : this is centralized in the gamelaunch (DEPRECATED/CENTRALIZED)
	 *
	 */
	// public function playerRegister(Request $request)
	// {
	//    $register_player = AWSHelper::playerRegister($request->token);
	//     // dd($register_player);
	//    // $register_player->code == 2217 || $register_player->code == 0;
	// }
	
	/**
	 * MERCHANT BACKOFFICE
	 * @author's NOTE : Launch Game (DEPRECATED/CENTRALIZED)
	 *
	 */
	// public function launchGame(Request $request){
	// 	$lang = GameLobby::getLanguage('All Way Spin','en');
	// 	$client_details = ProviderHelper::getClientDetails('token', $request->token);
	// 	$client = new Client([
	// 	    'headers' => [ 
	// 	    	'Content-Type' => 'application/json',
	// 	    ]
	// 	]);
	// 	$requesttosend = [
	// 		"merchantId" => $this->merchant_id,
	// 		"currentTime" => AWSHelper::currentTimeMS(),
	// 		"username" => $this->merchant_id.'_TG'.$client_details->player_id,
	// 		"playmode" => 0, // Mode of gameplay, 0: official
	// 		"device" => 1, // Identifying the device. Device, 0: mobile device 1: webpage
	// 		"gameId" => 'AWS_1',
	// 		"language" => $lang,
	// 	];
	// 	$requesttosend['sign'] = AWSHelper::hashen($requesttosend);
	// 	$guzzle_response = $client->post($this->api_url.'/api/login',
	// 	    ['body' => json_encode($requesttosend)]
	// 	);
	//     $provider_response = json_decode($guzzle_response->getBody()->getContents());
	//     Helper::saveLog('AWS BO Launch Game', 21, json_encode($requesttosend), $provider_response);
	//     return isset($provider_response->data->gameUrl) ? $provider_response->data->gameUrl : 'false';
	// }


	/**
	 * MERCHANT BACKOFFICE
	 * @author's NOTE : Get All Game List (NOT/USED) ONE TIME USAGE ONLY
	 *
	 */
	public function gameList(Request $request){
		$client = new Client([
		    'headers' => [ 
		    	'Content-Type' => 'application/json',
		    ]
		]);
		$requesttosend = [
			"merchantId" => $this->merchant_id,
			"currentTime" => AWSHelper::currentTimeMS(),
			"language" => 'en_US'
		];
		$requesttosend['sign'] = AWSHelper::hashen(AWSHelper::currentTimeMS(),$this->merchant_id);
		$guzzle_response = $client->post($this->api_url.'/game/list',
		    ['body' => json_encode($requesttosend)]
		);
	    $client_response = json_decode($guzzle_response->getBody()->getContents());
	    return json_encode($client_response);
	}


	/**
	 * MERCHANT BACKOFFICE (NOT/USED)
	 * @author's NOTE : This is only if we want to disable/enable a player on this provider 
	 * @param   $[request->status] [<enable or disable>]
	 *
	 */
	public function playerManage(Request $request){
		Helper::saveLog('AWS BO Player Manage', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$status = $request->has('status') ? $request->status : 'enable';
		$client_details = ProviderHelper::getClientDetails('token', $request->token);
		$client = new Client([
		    'headers' => [ 
		    	'Content-Type' => 'application/json',
		    ]
		]);
		$requesttosend = [
			"merchantId" => $this->merchant_id,
			"currentTime" => AWSHelper::currentTimeMS(),
			"username" => $this->merchant_id.'_'.$client_details->player_id,
			"sign" => $this->hashen($this->merchant_id.'_'.$client_details->player_id, AWSHelper::currentTimeMS()),
		];
		$guzzle_response = $client->post($this->api_url.'/user/'.$status,
		    ['body' => json_encode($requesttosend)]
		);
	    $client_response = json_decode($guzzle_response->getBody()->getContents());
	    return $client_response;
	}

	/**
	 * MERCHANT BACKOFFICE (NOT/USED)
	 * @author's NOTE : This is only if we want to check the player status on this provider
	 *
	 */
	public function playerStatus(Request $request){
		Helper::saveLog('AWS BO Player Status', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$status = $request->has('status') ? $request->status : 'enable';
		$client_details = ProviderHelper::getClientDetails('token', $request->token);
		$client = new Client([
		    'headers' => [ 
		    	'Content-Type' => 'application/json',
		    ]
		]);
		$requesttosend = [
			"merchantId" => $this->merchant_id,
			"currentTime" => AWSHelper::currentTimeMS(),
			"username" => $this->merchant_id.'_'.$client_details->player_id,
			"sign" => $this->hashen($this->merchant_id.'_'.$client_details->player_id, AWSHelper::currentTimeMS()),
		];
		$guzzle_response = $client->post($this->api_url.'/user/status',
		    ['body' => json_encode($requesttosend)]
		);
	    $client_response = json_decode($guzzle_response->getBody()->getContents());
	    return $client_response;
	}

	/**
	 * MERCHANT BACKOFFICE (NOT/USED)
	 * @author's NOTE : This is only if we want to check the player balance on this provider
	 *
	 */
	public function playerBalance(Request $request){
		Helper::saveLog('AWS BO Player Balance', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$client_details = ProviderHelper::getClientDetails('token', $request->token);
		$client = new Client([
		    'headers' => [ 
		    	'Content-Type' => 'application/json',
		    ]
		]);
		$requesttosend = [
			"merchantId" => $this->merchant_id,
			"currentTime" => AWSHelper::currentTimeMS(),
			"username" => $this->merchant_id.'_'.$client_details->player_id,
			"sign" => $this->hashen($this->merchant_id.'_'.$client_details->player_id, AWSHelper::currentTimeMS()),
		];
		$guzzle_response = $client->post($this->api_url.'/user/balance',
		    ['body' => json_encode($requesttosend)]
		);
	    $client_response = json_decode($guzzle_response->getBody()->getContents());
	    return $client_response;
	}

	/**
	 * MERCHANT BACKOFFICE (NOT/USED) (NOT APLLICABLE IN THE SINGLE WALLET)
	 * @author's NOTE : Fund Transfer
	 *
	 */
	public function fundTransfer(Request $request){
		Helper::saveLog('AWS BO Fund Transfer', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$client_details = ProviderHelper::getClientDetails('token', $request->token);
		$client = new Client([
		    'headers' => [ 
		    	'Content-Type' => 'application/json',
		    ]
		]);
		$requesttosend = [
			"merchantId" => $this->merchant_id,
			"currentTime" => AWSHelper::currentTimeMS(),
			"username" => $this->merchant_id.'_'.$client_details->player_id,
			"amount" => $request->amount,
			"merchantTransId" => 'AWSF2019123199999', // for each player account, the transfer transaction code has to be unique
			"sign" => $this->hashen($this->merchant_id.'_'.$client_details->player_id, AWSHelper::currentTimeMS()),
		];
		$guzzle_response = $client->post($this->api_url.'/user/balance',
		    ['body' => json_encode($requesttosend)]
		);
	    $client_response = json_decode($guzzle_response->getBody()->getContents());
	    return $client_response;
	}

	/**
	 * MERCHANT BACKOFFICE (NOT/USED) (NOT APLLICABLE IN THE SINGLE WALLET)
	 * @author's NOTE : Query status of fund transfer
	 *
	 */
	public function queryStatus(Request $request){
		Helper::saveLog('AWS BO Query Status', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$client_details = ProviderHelper::getClientDetails('token', $request->token);
		$client = new Client([
		    'headers' => [ 
		    	'Content-Type' => 'application/json',
		    ]
		]);
		$requesttosend = [
			"merchantId" => $this->merchant_id,
			"currentTime" => AWSHelper::currentTimeMS(),
			"username" => $this->merchant_id.'_'.$client_details->player_id,
			"merchantTransId" => 'AWSF2019123199999', // for each player account, the transfer transaction code has to be unique
			"sign" => $this->hashen($this->merchant_id.'_'.$client_details->player_id, AWSHelper::currentTimeMS()),
		];
		$guzzle_response = $client->post($this->api_url.'/fund/queryStatus',
		    ['body' => json_encode($requesttosend)]
		);
	    $client_response = json_decode($guzzle_response->getBody()->getContents());
	    return $client_response;
	}

	/**
	 * MERCHANT BACKOFFICE (NOT/USED) (NOT APLLICABLE IN THE SINGLE WALLET)
	 * @author's NOTE : Query status of fund transfer
	 *
	 */
	public function queryOrder(Request $request){
		Helper::saveLog('AWS BO Query Order', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$client_details = ProviderHelper::getClientDetails('token', $request->token);
		$client = new Client([
		    'headers' => [ 
		    	'Content-Type' => 'application/json',
		    ]
		]);
		$requesttosend = [
			"merchantId" => $this->merchant_id,
			"currentTime" => AWSHelper::currentTimeMS(),
			"username" => $this->merchant_id.'_'.$client_details->player_id,
			"sign" => $this->hashen($this->merchant_id.'_'.$client_details->player_id, AWSHelper::currentTimeMS()),
		];
		$guzzle_response = $client->post($this->api_url.'/order/query',
		    ['body' => json_encode($requesttosend)]
		);
	    $client_response = json_decode($guzzle_response->getBody()->getContents());
	    return $client_response;
	}


	/**
	 * MERCHANT BACKOFFICE
	 * @author's NOTE : Logout the player
	 *
	 */
	public function playerLogout(Request $request){
		Helper::saveLog('AWS BO Player Logout', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$client_details = ProviderHelper::getClientDetails('token', $request->token);
		$client = new Client([
		    'headers' => [ 
		    	'Content-Type' => 'application/json',
		    ]
		]);
		$requesttosend = [
			"merchantId" => $this->merchant_id,
			"currentTime" => AWSHelper::currentTimeMS(),
			"username" => $this->merchant_id.'_'.$client_details->player_id,
			"sign" => $this->hashen($this->merchant_id.'_'.$client_details->player_id, AWSHelper::currentTimeMS()),
		];
		$guzzle_response = $client->post($this->api_url.'/api/logout',
		    ['body' => json_encode($requesttosend)]
		);
	    $client_response = json_decode($guzzle_response->getBody()->getContents());
	    return $client_response;
	}


	/**
	 * HELPER
	 * Find The Transactions For Refund, Providers Transaction ID
	 * @return  [<string>]
	 * 
	 */
    public  function getOperationType($operation_type) {
    	$operation_types = [
    		'100' => 'Bet',
    		'200' => 'Adjust',
    		'300' => 'Lucky Draw',
    		'400' => 'Tournament',
    	];
    	if(array_key_exists($operation_type, $operation_types)){
    		return $operation_types[$operation_type];
    	}else{
    		return 'Operation Type is unknown!!';
    	}

	}

	/**
	 * HELPER
	 * Find Game Transaction Ext
	 * @param [string] $[provider_transaction_id] [<provider transaction id>]
	 * @param [int] $[game_transaction_type] [<1 bet, 2 win, 3 refund>]
	 * @param [string] $[type] [<transaction_id, round_id>]
	 * 
	 */
	public  function findGameExt($provider_transaction_id, $game_transaction_type, $type) {
		$transaction_db = DB::table('game_transaction_ext as gte');
        if ($type == 'transaction_id') {
			$transaction_db->where([
		 		["gte.provider_trans_id", "=", $provider_transaction_id],
		 		["gte.game_transaction_type", "=", $game_transaction_type],
		 	]);
		}
		if ($type == 'round_id') {
			$transaction_db->where([
		 		["gte.round_id", "=", $provider_transaction_id],
		 		["gte.game_transaction_type", "=", $game_transaction_type],
		 	]);
		}  
		$result= $transaction_db->first();
		return $result ? $result : 'false';
	}

	/**
	 * HELPER
	 * Find Game Transaction
	 * @param [string] $[round_ids] [<round id for bets>]
	 * @param [string] $[type] [<0 Lost, 1 win, 3 draw, 4 refund, 5 processing>]
	 * 
	 */
    public  function getAllGameTransaction($round_ids, $type) 
    {
		$game_transactions = DB::table("game_transactions")
					->where('win', $type)
				    ->whereIn('round_id', $round_ids)
				    ->get();
	    return (count($game_transactions) > 0 ? $game_transactions : 'false');
    }

	/**
	 * HELPER
	 * Create Game Transaction
	 * 
	 */
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

	/**
	 * HELPER
	 * Create Game Transaction Extension
	 * @param  $[game_type] [<1=bet,2=win,3=refund>]
	 * 
	 */
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
