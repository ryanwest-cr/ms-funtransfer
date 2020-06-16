<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
// use App\Helpers\CryptAES;
use App\Helpers\CallParameters;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;
use DB;



/**
 * IA ESports Controller
 *
 * @version 1.1
 * @var username = MW player_id (NOT THE CLIENT PLAYER ID/USERNAME) ,prefixed with BETRNK_{$mw_id}
 * @method lunch 
 * @method register
 * @method userWithdraw = deprecated
 * @method userDeposit = deprecated
 * @method userbalance = deprecated
 * @method userWager = deprecated
 * @method hotgames
 * @method orders
 * @method activity logs
 * @method seamlessBalance
 * @method seamlessDeposit
 * @method seamlessWithdrawal
 * @method seamlessSearchOrder
 *
 */
class IAESportsController extends Controller
{
    
	// public $url = 'https://middleware.freebetrnk.com/public/game/launchurl';
	public $prefix = 'BETRNK';
	public $auth_key = '54bc08c471ae3d656e43735e6ffc9bb6';
	public $pch = 'BRNK';
	public $iv = '45b80556382b48e5';
	public $url_lunch = 'http://api.ilustretest.com/user/lunch';
	public $url_register = 'http://api.ilustretest.com/user/register';
	public $url_withdraw = 'http://api.ilustretest.com/user/withdraw';
	public $url_deposit = 'http://api.ilustretest.com/user/deposit';
	public $url_balance = 'http://api.ilustretest.com/user/balance';
	public $url_wager = 'http://api.ilustretest.com/wager/getproject';
	public $url_hotgames = 'http://api.ilustretest.com/index/gethotgame';
	public $url_orders = 'http://api.ilustretest.com/user/searchprders';
	public $url_activity_logs = 'http://api.ilustretest.com/user/searchprders';


	/**
	 * Create Hash Key
	 * @return Encrypted AES string
	 *
	 */
	public function hashen($params=[])
	{
		$params['auth_key'] = $this->getMD5ParamsString($params);
		$plaintext = json_encode($params);
		$iv = $this->iv;
		$method = 'AES-256-CBC';
		$hashen = base64_encode(openssl_encrypt($plaintext, $method, $this->auth_key, OPENSSL_RAW_DATA, $iv));
		return $hashen;
	}

	/**
	 * Decode Hashen
	 * @return Decoded Hashen AES string
	 *
	 */
	public function rehashen($hashen)
	{
		$method = 'AES-256-CBC';
		$iv = $this->iv;
		$rehashen = openssl_decrypt(base64_decode($hashen), $method,$this->auth_key, OPENSSL_RAW_DATA, $iv);
		return $rehashen;
	}

	/**
	 * Decode Hashen
	 * @return Sorted Array Keys
	 *
	 */
    // public function getMD5ParamsString($params)
    public function getMD5ParamsString($params=[])
    {
        ksort($params);
        $arr = [];
        foreach($params as $key => $val)
        {
            $arr[] = $key . '=' . $val;
        }
        return md5(join(',', $arr));
    }

    /**
	 * Generate URL Launch
	 * @return game url
	 *
	 */
	public function userlunch($username)
	{
	    $params = [
            "username" => $username,
			//"client" => 1, // Not Required!
			"lang" => 2, // Default English
        ];
        $uhayuu = $this->hashen($params);
		$header = ['pch:'. $this->pch];
        $timeout = 5;
		$client_response = $this->curlData($this->url_lunch, $uhayuu, $header, $timeout);
		$data = json_decode($this->rehashen($client_response[1], true));
		// dd($data->message);
		// dd($data->data->url); // URL
		// dd($data);
		return $data->data->url;
	}

	/**
	 * Register Player and call the userlunch method after!
	 *
	 */
	public function userRegister(Request $request)
	{
		Helper::saveLog('IA REGISTER', 2, 'REGISTER', 'DEMO CALL');
		$token=Helper::checkPlayerExist($request->client_id,$request->client_player_id,$request->username,$request->email,$request->display_name,$request->token,$request->game_code);
		$player_details = $this->_getClientDetails('token', $token);
		$username = $this->prefix.'_'.$player_details->player_id;
		// $prefixed_username = explode("_", $request->username);
		// dd($prefixed_username[1]);
		// $player = $this->_getClientDetails('username_and_cid', $request->username, $request->client_id);
		// $currency_code = $request->has('currency_code') ? $request->currency_code : 'RMB'; 
		$currency_code = $request->has('currency_code') ? $request->currency_code : 'USD'; 
		// $this->currencyCheck('USD'); // Check if currency is available
	    $params = [
				"register_username" => $username,
				"lang" => 2,
				"currency_code" => $currency_code,
				// "amount" => $amount,  // not required
				// "limit_money" => 1, // not required
        ];
        $uhayuu = $this->hashen($params);
        $header = ['pch:'. $this->pch];
        $timeout = 5;
		$client_response = $this->curlData($this->url_register, $uhayuu, $header, $timeout);
		$data = json_decode($this->rehashen($client_response[1], true));
		// dd($data);
		if($data->status): // IF status is 1/true //user already register
			$data = $this->userlunch($username);
			$msg = array(
                "game_code" => $request->input("game_code"),
                "url" => $data,
                "game_launch" => true
            );
            return response($msg,200)
            ->header('Content-Type', 'application/json');
		else: // Else User is successfull register
			$data = $this->userlunch($username);
			$msg = array(
                "game_code" => $request->input("game_code"),
                "url" => $data,
                "game_launch" => true
            );
            return response($msg,200)
            ->header('Content-Type', 'application/json');
		endif;	
	}

	/**
	 * Deposit, Deposit Win/Credit From The Client add as debit to our system!
	 *
	 */
	public function seamlessDeposit(Request $request)
	{
		Helper::saveLog('IA Deposit', 2, '', 'CALL RECEIVED');
		Helper::saveLog('IA Deposit', 2, json_encode(file_get_contents("php://input")), 'IA CALL');
		$data = file_get_contents("php://input");
		$cha = json_decode($this->rehashen($data, true));
		$desc_json = json_decode($cha->desc,JSON_UNESCAPED_SLASHES);
		$prefixed_username = explode("_", $cha->username);
		$client_details = $this->_getClientDetails('player_id', $prefixed_username[1]);
		if(!$client_details):
			$params = [
	            "code" => 111003,
	            "data" => [],
				"message" => "User does not exist",
	        ];	
			return $params;
		endif;	
		if($this->getOrder($cha->orderId)):
			$params = [
	            "code" => 111007,
	            "data" => [],
				"message" => "Order number already exists",
	        ];	
			return $params;
		endif;
		// $cha_data = $cha->currencyInfo;
		// $chachi = json_decode($cha_data,JSON_UNESCAPED_SLASHES);
		// return $chachi['short_name'];
		$status_code = 200;
		$game_code = '';
		$transaction_type = 'credit';
		$token_id = $client_details->token_id;
		// $game_details = Game::find($json_data->game_code);
		$game_details = 1; // TEST //$game_details->game_id
		$bet_amount = $cha->money;
		$pay_amount = $cha->money; // Zero Payout
		$method = $transaction_type == 'debit' ? 1 : 2;
		$win_or_lost = 1;
		$payout_reason = $this->getCodeType($desc_json['code']) .' : '.$desc_json['message'];
		$income = 0;	
		$provider_trans_id = $cha->orderId;
		// $gamerecord  = Helper::saveGame_transaction($token_id, $game_details, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id);
		// $game_transextension = Helper::saveGame_trans_ext($gamerecord, json_encode($cha));
		$client = new Client([
		    'headers' => [ 
		    	'Content-Type' => 'application/json',
		    	'Authorization' => 'Bearer '.$client_details->client_access_token
		    ]
		]);
		$guzzle_response = $client->post($client_details->fund_transfer_url,
		    ['body' => json_encode(
		        	[
					  "access_token" => $client_details->client_access_token,
					  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
					  "type" => "fundtransferrequest",
					  "datetsent" => "",
					  "gamedetails" => [
					    "gameid" => $game_code,
					    "gamename" => ""
					  ],
					  "fundtransferrequest" => [
							"playerinfo" => [
							"token" => $client_details->player_token
						],
						"fundinfo" => [
						      "gamesessionid" => "",
						      "transactiontype" => $transaction_type,
						      "transferid" => "",
						      "rollback" => "false",
						      "currencycode" => $client_details->currency,
						      "amount" => $cha->money // Amount to be send!
						]
					  ]
					]
		    )]
		);
	    $client_response = json_decode($guzzle_response->getBody()->getContents());
	    // Not Enough Balance Need Double Check
	    if(isset($client_response->error_code) && $client_response->error_code == 'NOT_SUFFICIENT_FUNDS'):
	    	$status_code = 111004; // Not enough balance!
	    else:	
			$gamerecord  = Helper::saveGame_transaction($token_id, $game_details, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id);
			$game_transextension = Helper::saveGame_trans_ext($gamerecord, json_encode($cha));
	    endif;
	    $params = [
            "code" => $status_code,
            "data" => [
            	"available_balance" => $client_response->fundtransferresponse->balance,
            	"status" => 1,
            ],
			"message" => "Success",
        ];	
		Helper::saveLog('IA Deposit Request And Response', 2,json_encode($cha), json_encode($params));
		return $params;
	}


	/**
	 * Withdrawal, Deduct Bet/Debit From The Client add as Credit to our system!
	 *
	 */
	public function seamlessWithdrawal(Request $request)
	{
		// Helper::saveLog('IA Withrawal', 2, '', 'CALL RECEIVED');
		// Helper::saveLog('IA Withrawal', 2, json_encode(file_get_contents("php://input")), 'IA CALL');
		$data = file_get_contents("php://input");
		$cha = json_decode($this->rehashen($data, true));
		$desc_json = json_decode($cha->desc,JSON_UNESCAPED_SLASHES);
		$prefixed_username = explode("_", $cha->username);
		$client_details = $this->_getClientDetails('player_id', $prefixed_username[1]);
		// $cha_data = $cha->currencyInfo;
		// $chachi = json_decode($cha_data,JSON_UNESCAPED_SLASHES);
		// return $chachi['short_name'];
		if(!$client_details):
			$params = [
	            "code" => 111003,
	            "data" => [],
				"message" => "User does not exist",
	        ];	
			return $params;
		endif;	
		// dd($cha->orderId);
		if($this->getOrder($cha->orderId)):
			$params = [
	            "code" => 111007,
	            "data" => [],
				"message" => "Order number already exists",
	        ];	
			return $params;
		endif;
		$status_code = 200;
		$game_code = '';
		$transaction_type = 'debit';
		$token_id = $client_details->token_id;
		// $game_details = Game::find($json_data->game_code);
		$game_details = 1; // TEST //$game_details->game_id
		$bet_amount = $cha->money;
		$pay_amount = 0; // Zero Payout
		$method = $transaction_type == 'debit' ? 1 : 2;
		$win_or_lost = 0;
		$payout_reason = $this->getCodeType($desc_json['code']) .' : '.$desc_json['message'];
		$income = $cha->money;	
		$provider_trans_id = $cha->orderId;

		$client = new Client([
		    'headers' => [ 
		    	'Content-Type' => 'application/json',
		    	'Authorization' => 'Bearer '.$client_details->client_access_token
		    ]
		]);
		$guzzle_response = $client->post($client_details->fund_transfer_url,
		    ['body' => json_encode(
		        	[
					  "access_token" => $client_details->client_access_token,
					  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
					  "type" => "fundtransferrequest",
					  "datetsent" => "",
					  "gamedetails" => [
					    "gameid" => $game_code,
					    "gamename" => ""
					  ],
					  "fundtransferrequest" => [
							"playerinfo" => [
							"token" => $client_details->player_token
						],
						"fundinfo" => [
						      "gamesessionid" => "",
						      "transactiontype" => $transaction_type,
						      "transferid" => "",
						      "rollback" => "false",
						      "currencycode" => $client_details->currency,
						      "amount" => $cha->money // Amount to be send!
						]
					  ]
					]
		    )]
		);
	    $client_response = json_decode($guzzle_response->getBody()->getContents());
	    // Not Enough Balance Need Double Check
	    if(isset($client_response->error_code) && $client_response->error_code == 'NOT_SUFFICIENT_FUNDS'):
	    	$status_code = 111004; // Not enough balance!
	    else:	
			$gamerecord  = Helper::saveGame_transaction($token_id, $game_details, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id);
			$game_transextension = Helper::saveGame_trans_ext($gamerecord, json_encode($cha));
	    endif;	
	    $params = [
            "code" => $status_code,
            "data" => [
            	"available_balance" => $client_response->fundtransferresponse->balance,
            	"status" => 1,
            ],
			"message" => "Success",
        ];	
		Helper::saveLog('IA Withrawal Request And Response', 2,json_encode($cha), json_encode($params));
		return $params;
	}

	/**
	 * @return Player Balance
	 *
	 */
	public function seamlessBalance(Request $request)
	{	
		// decrpting the data for test
		// $data_received = file_get_contents("php://input");
		// $cha = json_decode($this->rehashen($data_received, true));
		// dd($cha);
		// dd($data_received);
		// end decrpyting the data
		Helper::saveLog('IA Balance', 2, '', 'CALL RECEIVED');
		Helper::saveLog('IA Balance', 2, json_encode(file_get_contents("php://input")), 'IA CALL');
		$data_received = file_get_contents("php://input");
		$cha = json_decode($this->rehashen($data_received, true));
		// dd(gettype($cha));
		// Helper::saveLog('IA Balance', 2, json_encode($cha), 'IA CALL DECODED');
		$prefixed_username = explode("_", $cha->username);
		$client_details = $this->_getClientDetails('player_id', $prefixed_username[1]);
		// dd($client_details);
		$client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$client_details->client_access_token
            ]
        ]);
		$guzzle_response = $client->post($client_details->player_details_url,
					    ['body' => json_encode(
					        	["access_token" => $client_details->client_access_token,
									"hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
									"type" => "playerdetailsrequest",
									"datesent" => "",
									"gameid" => "",
									"clientid" => $client_details->client_id,
									"playerdetailsrequest" => [
										"token" => $client_details->player_token,
										"gamelaunch" => true
									]
								]
					    )]
		);
		$client_response = json_decode($guzzle_response->getBody()->getContents());
		$params = [
            "code" => '200',
            "data" => [
            	"available_balance" => $client_response->playerdetailsresponse->balance,
            ],
			"message" => "Success",
        ];	
        // Helper::saveLog('IA Balance Request & Response',2,json_encode($cha), json_encode($params));
        return $params;
		// dd($this->hashen($params));
	}

	/**
	 * Check Order ID if exist
	 *
	 */
	public function seamlessSearchOrder(Request $request)
	{	
		// Qg3Tmg1/dfEmjRe/7CaMcLXf1vKseFeXleVuoiWn6efxu72Ab5wKNDocAFL3+Fwm2hvo07BE+p6T3zdbNHEMIK+TP+lqo76t3wlxV6SGXrn4955poVusgarXrQpCWgUb
		// $params = ["orderId" => 'SGVFVUITDSUBBSRCGEJJ'];	
  		// $uhayuu = $this->hashen($params);
 		// dd($uhayuu);
		Helper::saveLog('IA Search Order', 2, '', 'CALL RECEIVED');
		Helper::saveLog('IA Search Order', 2, json_encode(file_get_contents("php://input")), 'IA CALL');
		$data_received = file_get_contents("php://input");
		$cha = json_decode($this->rehashen($data_received, true));
		if($this->getOrder($cha->orderId)):
			$params = [
	            "code" => 200,
	            "data" => [
	            	"status" => 1,
	            ],
					"message" => "Success",
	        ];	
	    else:
    	    $params = [
	            "code" => 200,
	            "data" => [
	            	"status" => 0,
	            ],
					"message" => "Order number doesnt exists",
	        ];	  
		endif;
		Helper::saveLog('IA Search Order', 2, json_encode($cha), json_encode($params));
		return $params;
	}





	/**
	 * DEPRECATED < WE USE SEAMLESS NOW >
	 * Register Player and call the userlunch method after!
	 * @see EXPECTED PARAMETERS
	 * @param username = player username 
	 * @param client_id = player origin
	 * @param amount = amount
	 *
	 */
	public function userDeposit(Request $request)
	{
		return 'ASHEN';
		Helper::saveLog('IA Deposit', 2, 'haha', 'DEMO CALL');
		Helper::saveLog('IA Deposit', 2, file_get_contents("php://input"), 'DEMO CALL');
		$player_details = $this->_getClientDetails('username_and_cid', $request->username, $request->client_id);
		$username = $this->prefix.'_'.$player_details->player_id;
		$params = [
				"username" => $username,
				"amount" => $request->amount,
				"order_id" => 'TEST', // MW IDENTIFICATION
        ];
        $uhayuu = $this->hashen($params);
        $header = ['pch:'. $this->pch];
        $timeout = 5;
        // dd($params);
		$client_response = $this->curlData($this->url_deposit, $uhayuu, $header, $timeout);
		$data = json_decode($this->rehashen($client_response[1], true));
		dd($data);
	}

	/**
	 *  Dreprecated!
	 * 
	 */
	public function userWithdraw(Request $request)
	{
		return 'ASHEN';
		Helper::saveLog('IA Withdraw', 2, 'haha', 'DEMO CALL');
		Helper::saveLog('IA Withdraw', 2, file_get_contents("php://input"), 'DEMO CALL');
	}

	/**
	 * DEPRECATED < WE USE SEAMLESS NOW >
	 * Register Player and call the userlunch method after!
	 * @see EXPECTED PARAMETERS
	 * @param username = player username 
	 * @param client_id = player origin
	 *
	 */
	public function userBalance(Request $request)
	{
		return 'ASHEN';
		Helper::saveLog('IA Balance', 2, 'haha', 'DEMO CALL');
		Helper::saveLog('IA Balance', 2, file_get_contents("php://input"), 'DEMO CALL');
		$player_details = $this->_getClientDetails('username_and_cid', $request->username, $request->client_id);
		$username = $this->prefix.'_'.$player_details->player_id;
		$params = [
				"username" => $username,
        ];
        $uhayuu = $this->hashen($params);
        $header = ['pch:'. $this->pch];
        $timeout = 5;
        // dd($params);
		$client_response = $this->curlData($this->url_balance, $uhayuu, $header, $timeout);
		$data = json_decode($this->rehashen($client_response[1], true));
		//dd($data);
	}

	/**
	 * Deprecated!
	 * 
	 */
	public function userWager(Request $request)
	{
		return 'ASHEN';
		Helper::saveLog('IA Wager', 2, 'haha', 'DEMO CALL');
		Helper::saveLog('IA Wager', 2, file_get_contents("php://input"), 'DEMO CALL');
	}

	/**
	 * Deprecated!
	 * 
	 */
	public function getHotGames(Request $request)
	{
		return 'ASHEN';
		$header = ['pch:'. $this->pch];
        $params = array();
        $uhayuu = $this->hashen($params);
		$timeout = 5;
		$client_response = $this->curlData($this->url_hotgames, $uhayuu, $header, $timeout);
		$data = json_decode($this->rehashen($client_response[1], true));
		dd($data);
	}
	
	/**
	 * Deprecated!
	 * 
	 */
	public function userActivityLog(Request $request)
	{
		return 'ASHEN';
		Helper::saveLog('IA Log', 2, 'haha', 'DEMO CALL');
		Helper::saveLog('IA Log', 2, file_get_contents("php://input"), 'DEMO CALL');
		// $header = ['pch' => $this->pch];
	    // $body = ['body' => json_encode(
		//         	[
		// 				"start_time" => 1523600346,
		// 				"end_time" => 1523604346,
		// 				"page" => 1,
		// 				"limit" => 10000,
		// 			]
		// 	    )];
		// $call = $this->apiCall($body, $this->url_activity_logs, $header);
		// return $call;
	}

	/**
	 * Currency Check
	 * @return Boolean!
	 * 
	 */
	public function currencyCheck($currency_code){
		// IA Available Currencies, 
		// Ren Min Bi, Thai baht, Dollar, Ringgit, Indonesian rupiah, Vietnamese Dong, Indian rupee, New Taiwan Currency, Hong Kong Dollar, South Korean Won, Australian Dollar, Vietnamese
		$available_currency = array("RMB", "THB", "USD", "MYR", 'IDR', 'VND', 'INR', 'TWD', 'HKD', 'KRW', 'AUD');
		return in_array($currency_code, $available_currency) ? true : false;
	}


	/**
	 * Check order
	 * @return Code Type!
	 * 
	 */
    public  function getOrder($order_id) 
    {
		$game_transactions = DB::table("game_transactions")
					->where('provider_trans_id', $order_id)
				    ->latest()
				    ->first();
		return $game_transactions ? true : false;
    }


	/**
	 * Get Code Type
	 * @return Code Type!
	 * 
	 */
    public  function getCodeType($getCodeType) 
    {
    		switch ($getCodeType) {
				case 11:
					$type = 'Stake deduction';
					break;
				case 12:
					$type = 'Winnings payout';
					break;
				case 13:
					$type = 'Refund after cancellation';
					break;
				case 14:
					$type = 'Payout including winnings after cancellation';
					break;
				case 15:
					$type = 'Refund after settlement';
					break;
				case 16:
					$type = 'IA Auto Chess Credit Deductions';
					break;
				case 17:
					$type = 'IA Auto Chess Winning Payouts';
					break;
				default:		  
			}	
				return $type;
	}

	/**
	 * Get Client Details
	 * @param type = token, player_id, site_url, username, username_and_cid
	 * @param value = actual value to be query
	 * @param client_id = optional
	 * 
	 */
	public function _getClientDetails($type = "", $value = "", $client_id="") 
	{
		$query = DB::table("clients AS c")
				 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.token_id', 'pst.player_token' , 'c.client_url', 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
				 ->leftJoin("players AS p", "c.client_id", "=", "p.client_id")
				 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
				 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
				 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id");
					if ($type == 'token') {
						$query->where([
					 		["pst.player_token", "=", $value],
					 		["pst.status_id", "=", 1]
					 	]);
					}
					if ($type == 'player_id') {
						$query->where([
					 		["p.player_id", "=", $value],
					 		["pst.status_id", "=", 1]
					 	]);
					}
					if ($type == 'site_url') {
						$query->where([
					 		["c.client_url", "=", $value],
					 	]);
					}
					if ($type == 'username') {
						$query->where([
					 		["p.username", $value],
					 	]);
					}
					if ($type == 'username_and_cid') {
						$query->where([
					 		["p.username", $value],
					 		["p.client_id", $client_id],
					 	]);
					}
					$result= $query
					 			->latest('token_id')
					 			->first();

			return $result;
	}



	/**
	 * Api Call
	 * 
	 * @param postData = encoded string using mcrypt
	 * @param header = header parameters
	 * @return ereturn array($status, $handles, $error)
	 * 
	 */
	public function curlData($url, $postData = array(), $header = false, $timeout = 10)
	{
	    $error = '';
	    $status = 1;
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    if(!empty($header))
	    {
	        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	    }
	    
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)');
	    curl_setopt($ch, CURLOPT_URL, $url);
	    if(!empty($postData))
	    {
	        curl_setopt($ch, CURLOPT_POST, true);
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	    }
	    
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	    $handles = curl_exec($ch);
	    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    if($httpcode < 200 || $httpcode >= 300)
	    {
	        $status = 0;
	        $error = $httpcode;
	    }
	    if(curl_errno($ch))
	    {
	        $error = curl_error($ch);
	        $status = 0;
	    }
	    
	    curl_close($ch);
	    
	    return array($status, $handles, $error);
	}



	// /**
	//  * Api Call
	//  * 
	//  * @author json_decode because api return data as json
	//  * @param header = header of call default null
	//  * @param body = hrequest body
	//  * @return errorcode, body, json
	//  * 
	//  */
	// public function apiCall($body, $url, $header=null)
	// {		
	// 		$client = new Client([
	// 			['headers' => 
	// 				$header
	// 			]
	// 		]);
	// 	    try
	// 	    {         
	// 	       $guzzle_response = $client->post($url, [
	// 	        	$body
	// 	       ]);
	// 	       $client_response = json_decode($guzzle_response->getBody()->getContents(), True);
	// 	    }
 //            catch(ClientException $e) // CLient Exception
 //            {
 //              $client_response = $e->getResponse();
 //              return $client_response->getStatusCode();
 //            }
 //            catch(RequestException $e) // Request Exception
 //            {  
 //                if ($e->hasResponse()) {
 //                    return $e->getCode();  // Get the response code!
 //                }
 //            }
 //            catch (\Exception $e) 
 //            {	
 //            	return $e->getCode();
 //            }

 //            return $client_response;
	// }

}
