<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use App\Helpers\Helper; # Removed
use App\Helpers\IAHelper;
// use App\Helpers\ProviderHelper; # Migrated To IAHelper Query Builder To RAW SQL - RiAN
use App\Helpers\ClientRequestHelper;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use DB;



/**
 * IA ESports Controller (Seamless Setup)
 *
 * @version 1.1
 * @author's note please dont remove commented line of codes - RiAN
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
    
	public $auth_key, $pch, $iv,  $url_lunch, $url_register, $url_withdraw, $url_deposit, $url_balance, $url_wager, $url_hotgames, $url_orders, $url_activity_logs = '';

	# Static Info From The Database
	public $game_code = 'ia-lobby';
	public $game_name = 'IA Gaming';
	public $provider_db_id = 15;
	public $prefix = 'TGAMES';
	public $api_version = 'version 1.0';


	public function __construct(){
		// $this->middleware('oauth', ['except' => ['index','seamlessDeposit','seamlessWithdrawal','seamlessBalance','seamlessSearchOrder','userlaunch']]);
		// $this->middleware('authorize:' . __CLASS__, ['except' => ['index', 'store']]);
    	$this->auth_key = config('providerlinks.iagaming.auth_key');
    	$this->pch = config('providerlinks.iagaming.pch');
    	$this->iv = config('providerlinks.iagaming.iv');
    	$this->url_lunch = config('providerlinks.iagaming.url_lunch');
    	$this->url_register = config('providerlinks.iagaming.url_register');
    	$this->url_withdraw = config('providerlinks.iagaming.url_withdraw');
    	$this->url_deposit = config('providerlinks.iagaming.url_deposit');
    	$this->url_balance = config('providerlinks.iagaming.url_balance');
    	$this->url_wager = config('providerlinks.iagaming.url_wager');
    	$this->url_hotgames = config('providerlinks.iagaming.url_hotgames');
    	$this->url_orders = config('providerlinks.iagaming.url_orders');
    	$this->url_activity_logs = config('providerlinks.iagaming.url_activity_logs');
	}

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
		return $data->data->url;
	}

	/**
	 * DEPRECATED CENTRALIZED
	 * Register Player and call the userlunch method after!
	 *
	 */
	public function userRegister(Request $request)
	{
		IAHelper::saveLog('IA REGISTER', 2, 'REGISTER', 'DEMO CALL');
		$token=Helper::checkPlayerExist($request->client_id,$request->client_player_id,$request->username,$request->email,$request->display_name,$request->token,$request->game_code);
		$player_details = IAHelper::getClientDetails('token', $token);
		$username = $this->prefix.'_'.$player_details->player_id;
		// $prefixed_username = explode("_", $request->username);
		// $player = IAHelper::getClientDetails('username_and_cid', $request->username, $request->client_id);
		// $currency_code = $request->has('currency_code') ? $request->currency_code : 'RMB'; 
		$currency_code = isset($player_details->default_currency) ? $player_details->default_currency : 'USD'; 
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
	 * Update Code 18, November 11 2020
	 *
	 */
	public function seamlessDeposit(Request $request)
	{	
		IAHelper::saveLog('IA Deposit', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'IA CALL');
		$data = file_get_contents("php://input");
		$cha = json_decode($this->rehashen($data, true)); // DECODE THE ENCRYPTION
		$desc_json = json_decode($cha->desc,JSON_UNESCAPED_SLASHES); // REMOVE SLASHES
		$transaction_code = $desc_json['code']; // 13,15,18 refund, 
		$rollback = $transaction_code == 13 || $transaction_code == 15 || $transaction_code == 14 || $transaction_code == 18 ? true : false;
		$prefixed_username = explode("_", $cha->username);
		$client_details = IAHelper::getClientDetails('player_id', $prefixed_username[1]);
		IAHelper::saveLog('IA seamlessDeposit EH', $this->provider_db_id,json_encode($cha), $data);
		if(empty($client_details)):
			$params = [
	            "code" => 111003,
	            "data" => [],
				"message" => "User does not exist",
	        ];	
			return $params;
		endif;	
		$is_project_multiple = explode(',', $cha->projectId);
		
		$check_exist_win = IAHelper::findGameExt($cha->orderId, 2, 'transaction_id');
		if($check_exist_win != 'false'):

			$params = [
	            "code" => 111007,
	            "data" => [],
				"message" => "Order number already exists",
	        ];	
			return $params;
		
		# 20-11-20 New Added Else For Trapping 
		else:  

			$status_code = 200;
			$game_code = '';
			$transaction_type = 'credit';
			$token_id = $client_details->token_id;
			$game_details_info = IAHelper::findGameDetails('game_code', $this->provider_db_id, $this->game_code);
			$game_details = $game_details_info->game_id;
			$bet_amount = $cha->money;
			$pay_amount = $cha->money; // Zero Payout
			$method = $transaction_type == 'debit' ? 1 : 2;
			$win_or_lost = 1;
			$payout_reason = $this->getCodeType($desc_json['code']) . ' : ' . $desc_json['message'];
			$income = 0;
			$provider_trans_id = $cha->orderId;

			$client_player = IAHelper::playerDetailsCall($client_details);
			if ($client_player == 'false') {
				$params = ["code" => 111006, "data" => [], "message" => "deposit failed client error"];
				IAHelper::saveLog('IA seamlessWithdrawal - FATAL ERROR', $this->provider_db_id, json_encode($cha), IAHelper::datesent());
				return $params;
			}

			// AUTO CHESS GAME // 1 WAY FLIGHT
			if ($transaction_code == 16 || $transaction_code == 17) {
				// IF CALL IS CREDIT AUTO BET IS ZERO AND WIN WILL BE THE EXACT AMOUNT
				$transaction_type = 'credit';
				$token_id = $client_details->token_id;
				$bet_amount = $cha->money;
				$pay_amount = $cha->money; // Zero Payout
				$method = 2;
				$entry_id = 2; //win
				$win_or_lost = 1;
				$payout_reason = $this->getCodeType($desc_json['code']) . ' : ' . $desc_json['message'];
				$income = '-' . $bet_amount;
				$provider_trans_id = $cha->orderId;

				// FIRST CALL BET ZERO
				$auto_chess_bet = 0;
				$check_exist_bet = IAHelper::findGameExt($cha->orderId, 1, 'transaction_id');
				if ($check_exist_bet != 'false') {
					$gamerecord1 = $check_exist_bet->game_trans_id;
					$game_transextension2 = IAHelper::createGameTransExtV2($gamerecord1, $cha->orderId, $cha->projectId, $cha->money, 2);
					try {
						$client_response2 = ClientRequestHelper::fundTransfer($client_details, $cha->money, $this->game_code, $this->game_name, $game_transextension2, $gamerecord1, $transaction_type);
						IAHelper::saveLog('IA seamlessDeposit CRID',  $this->provider_db_id, json_encode($cha), $client_response2);
					} catch (\Exception $e) {
						$params = ["code" => 111006, "data" => [], "message" => "deposit failed client error"];
						if (isset($gamerecord1)) {
							IAHelper::updateGameTransactionStatus($gamerecord1, 2, 99);
						}
						IAHelper::updatecreateGameTransExt($game_transextension2, 'FAILED', $params, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
						IAHelper::saveLog('IA seamlessDeposit - FATAL ERROR', $this->provider_db_id, json_encode($cha), IAHelper::datesent());
						return $params;
					}
					if (
						isset($client_response2->fundtransferresponse->status->code)
						&& $client_response2->fundtransferresponse->status->code == "200"
					) {
						$params = [
							"code" => $status_code,
							"data" => [
								"available_balance" => IAHelper::amountToFloat($client_response2->fundtransferresponse->balance),
								"status" => 1,
							],
							"message" => "Success",
						];
						IAHelper::updatecreateGameTransExt($game_transextension2, $cha, $params, $client_response2->requestoclient, $client_response2, $params);
						// $this->updateBetToWin($cha->projectId, $pay_amount, $income, 1, 2);
						$this->updateBetToWin($gamerecord1, $pay_amount, $income, 1, 2);
						return $params;
					} elseif (
						isset($client_response2->fundtransferresponse->status->code)
						&& $client_response2->fundtransferresponse->status->code == "402"
					) {
						IAHelper::updateGameTransactionStatus($gamerecord1, 2, 99);
						$params = ["code" => 111006, "data" => [], "message" => "deposit failed client error"];
						return $params;
					}
				} else {
					$gamerecord1  = IAHelper::createGameTransaction($token_id, $game_details, 0, 0, 1, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $cha->projectId);
					$game_transextension1 = IAHelper::createGameTransExtV2($gamerecord1, $provider_trans_id, $cha->projectId, $auto_chess_bet, 1);

					try {
						$client_response1 = ClientRequestHelper::fundTransfer($client_details, $auto_chess_bet, $this->game_code, $this->game_name, $game_transextension1, $gamerecord1, 'debit');
						IAHelper::saveLog('IA seamlessDeposit CRID',  $this->provider_db_id, json_encode($cha), $client_response1);
					} catch (\Exception $e) {
						$params = ["code" => 111006, "data" => [], "message" => "deposit failed client error"];
						if (isset($gamerecord1)) {
							IAHelper::updateGameTransactionStatus($gamerecord1, 2, 99);
						}
						IAHelper::updatecreateGameTransExt($game_transextension1, 'FAILED', $params, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
						IAHelper::saveLog('IA seamlessDeposit - FATAL ERROR', $this->provider_db_id, json_encode($cha), IAHelper::datesent());
						return $params;
					}

					if (isset($client_response1->fundtransferresponse->status->code)&& $client_response1->fundtransferresponse->status->code == "200") {

						IAHelper::updatecreateGameTransExt($game_transextension1, $cha, $data, $client_response1->requestoclient, $client_response1, $data);
						// SECOND CALL ACTUAL WINNING
						$game_transextension2 = IAHelper::createGameTransExtV2($gamerecord1, $cha->orderId, $cha->projectId, $cha->money, 2);
						try {
							$client_response2 = ClientRequestHelper::fundTransfer($client_details, $cha->money, $this->game_code, $this->game_name, $game_transextension2, $gamerecord1, $transaction_type);
							IAHelper::saveLog('IA seamlessDeposit CRID',  $this->provider_db_id, json_encode($cha), $client_response2);
						} catch (\Exception $e) {
							$params = ["code" => 111006, "data" => [], "message" => "deposit failed client error"];
							if (isset($gamerecord1)) {
								IAHelper::updateGameTransactionStatus($gamerecord1, 2, 99);
							}
							IAHelper::updatecreateGameTransExt($game_transextension2, 'FAILED', $params, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
							IAHelper::saveLog('IA seamlessDeposit - FATAL ERROR', $this->provider_db_id, json_encode($cha), IAHelper::datesent());
							return $params;
						}
						$params = [
							"code" => $status_code,
							"data" => [
								"available_balance" => IAHelper::amountToFloat($client_response2->fundtransferresponse->balance),
								"status" => 1,
							],
							"message" => "Success",
						];
						IAHelper::updatecreateGameTransExt($game_transextension2, $cha, $params, $client_response2->requestoclient, $client_response2, $params);
						$this->updateBetToWin($gamerecord1, $pay_amount, $income, 1, 2);
						// $this->updateBetToWin($cha->projectId, $pay_amount, $income, 1, 2);
					} elseif (isset($client_response1->fundtransferresponse->status->code)&& $client_response1->fundtransferresponse->status->code == "402") {
						if (IAHelper::checkFundStatus($client_response1->fundtransferresponse->status->status)) :
							IAHelper::updateGameTransactionStatus($gamerecord1, 2, 6);
						else :
							IAHelper::updateGameTransactionStatus($gamerecord1, 2, 99);
						endif;
						$params = [
							"code" => 111004,
							"data" => [],
							"message" => "Insufficient balance",
						];
					} else {
						IAHelper::updateGameTransactionStatus($gamerecord1, 2, 6);
						$params = ["code" => 111005, "data" => [], "message" => "failed client error"];
					}
					IAHelper::saveLog('IA seamlessDeposit - SUCCESS', $this->provider_db_id, json_encode($cha), $params);
					return $params;
				}
			} else {
				// EGAME TRANSACTION
				// $bet_details = $this->getOrderData($cha->projectId);
				$is_exist_bet = IAHelper::findGameExt($cha->projectId, 1, 'round_id');
				if ($is_exist_bet == 'false') {
					$params = [
						"code" => 111006,
						"data" => [],
						"message" => "Deposit Failed, Order number dont exist!",
					];
					return $params;

				# 20-11-20 New Added Else For Trapping 
				}else{ 
					$is_exist_bet_refunded = IAHelper::findGameExt($cha->projectId, 3, 'round_id');
					if ($is_exist_bet_refunded != 'false') {
						$params = [
							"code" => 111007,
							"data" => [],
							"message" => "Order number already exists",
						];
						return $params;

					# 20-11-20 New Added Else For Trapping 
					}else{
						$bet_details = IAHelper::findGameTransaction($is_exist_bet->game_trans_id, 'game_transaction');
						$win = 1; //win
						$entry_id = 2; //win
						$income = $bet_details->bet_amount - $cha->money;
						$win = $transaction_code == 13 || $transaction_code == 15 || $transaction_code == 14 || $transaction_code == 18 ? 4 : $win; // 4 to refund!
						$is_refunded = $transaction_code == 13 || $transaction_code == 15 || $transaction_code == 14 || $transaction_code == 18 ? 3 : 2; // 3 to refund!

						$mw_request_data = json_decode($is_exist_bet->mw_request);
						$gamerecord = $mw_request_data->fundtransferrequest->fundinfo->roundId;
						$game_transextension = IAHelper::createGameTransExtV2($gamerecord, $cha->orderId, $cha->projectId, $cha->money, $is_refunded);

						try {
							$client_response = ClientRequestHelper::fundTransfer($client_details, $cha->money, $this->game_code, $this->game_name, $game_transextension, $gamerecord, $transaction_type, $rollback);
							IAHelper::saveLog('IA seamlessDeposit CRID',  $this->provider_db_id, json_encode($cha), $client_response);
						} catch (\Exception $e) {
							$params = ["code" => 111006, "data" => [], "message" => "deposit failed client error"];
							IAHelper::saveLog('IA seamlessDeposit - FATAL ERROR', $this->provider_db_id, json_encode($cha), IAHelper::datesent());
							return $params;
						}

						if (isset($client_response->fundtransferresponse->status->code) && $client_response->fundtransferresponse->status->code == "200") :
							// $this->updateBetToWin($cha->projectId, $pay_amount, $income, $win, $entry_id);
							IAHelper::updateGameTransaction($gamerecord, $pay_amount, $income, $win, $entry_id, 'game_trans_id');
							$params = [
								"code" => $status_code,
								"data" => [
									"available_balance" => IAHelper::amountToFloat($client_response->fundtransferresponse->balance),
									"status" => 1,
								],
								"message" => "Success",
							];
							IAHelper::updatecreateGameTransExt($game_transextension, $cha, $params, $client_response->requestoclient, $client_response, $params);
						elseif (isset($client_response->fundtransferresponse->status->code) && $client_response->fundtransferresponse->status->code == "402") :
							$params = [
								"code" => 111004,
								"data" => [],
								"message" => "Insufficient balance",
							];
						endif;
						IAHelper::saveLog('IA Deposit Response', $this->provider_db_id, json_encode($cha), $params);
					}
				}

			}
			return $params;

		endif;
	}


	/**
	 * Withdrawal, Deduct Bet/Debit From The Client add as Credit to our system!
	 *
	 */
	public function seamlessWithdrawal(Request $request)
	{

		IAHelper::saveLog('IA Withrawal', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'IA CALL');
		$data = file_get_contents("php://input");
		$cha = json_decode($this->rehashen($data, true));
		$desc_json = json_decode($cha->desc,JSON_UNESCAPED_SLASHES);
		$transaction_code = $desc_json['code']; // 13,15 refund, 
		$prefixed_username = explode("_", $cha->username);
		$client_details = IAHelper::getClientDetails('player_id', $prefixed_username[1]);
		IAHelper::saveLog('IA seamlessWithdrawal EH', $this->provider_db_id,json_encode($cha), $data);
		
		$is_project_multiple = explode(',', $cha->projectId);
		if(empty($client_details)):
			$params = [
	            "code" => 111003,
	            "data" => [],
				"message" => "User does not exist",
	        ];	
			return $params;
		endif;	
		
		$check_exist_bet = IAHelper::findGameExt($cha->orderId, 1, 'transaction_id');
		// if($check_exist_bet != 'false'):
		// 	$params = [
	    //         "code" => 111007,
	    //         "data" => [],
		// 		"message" => "Order number already exists",
	    //     ];	
		// 	return $params;
		// endif;

		$status_code = 200;
		$game_code = '';
		$transaction_type = 'debit';
		$token_id = $client_details->token_id;
		$game_details_info = IAHelper::findGameDetails('game_code', $this->provider_db_id, $this->game_code);
		$game_details = $game_details_info->game_id; 
		$bet_amount = $cha->money;
		$pay_amount = 0; // Zero Payout
		$method = $transaction_type == 'debit' ? 1 : 2;
		$win_or_lost = 5; // 0 lost,  5 processing // NO MORE WAITING MARK IT AS LOSE XD
		$payout_reason = $this->getCodeType($desc_json['code']) .' : '.$desc_json['message'];
		$income = $cha->money;	
		$provider_trans_id = $cha->orderId;

		// $client_player = IAHelper::playerDetailsCall($client_details);
		// if($client_player == 'false'){
		// 	$params = ["code" => 111005,"data" => [],"message" => "withdrawal failed client error"];
		// 	IAHelper::saveLog('IA seamlessWithdrawal - FATAL ERROR', $this->provider_db_id, json_encode($cha), IAHelper::datesent());
	    //     return $params;
		// }

			// CHECK IF AUTO CHESS GAME
	        if($transaction_code == 16 || $transaction_code == 17){ 
				// IF CALL IS DEBIT AUTO BET IS EXACT AMOUNT AND WIN WILL BE 0
				if($check_exist_bet != 'false'){
					// IF BET IS EXISTING RESEND ONLY THE 0 WINNING CONFIRMATION
					$check_exist_win = IAHelper::findGameExt($cha->orderId, 2, 'transaction_id');
					if($check_exist_win != 'false'){
						$params = [
							"code" => 111007,
							"data" => [],
							"message" => "Order number already exists",
						];	
						return $params;
					}
					$gamerecord1 = $check_exist_bet->game_trans_id;
					$game_transextension2 = IAHelper::createGameTransExtV2($gamerecord1,$cha->orderId, $cha->projectId, 0, 2);
					try {
						$client_response2 = ClientRequestHelper::fundTransfer($client_details,0,$this->game_code,$this->game_name,$game_transextension2,$gamerecord1,'credit');
						IAHelper::saveLog('IA seamlessWithdrawal CRID',  $this->provider_db_id, json_encode($cha), $client_response2);
					} catch (\Exception $e) {
						if(isset($gamerecord1)){
							IAHelper::updateGameTransactionStatus($gamerecord1, 2, 99);
						}
						$params = ["code" => 111005,"data" => [],"message" => "withdrawal failed client error"];
						IAHelper::updatecreateGameTransExt($game_transextension2, 'FAILED', $params, 'FAILED', $e->getMessage(), false, 'FAILED');
						IAHelper::saveLog('IA seamlessWithdrawal - FATAL ERROR', $this->provider_db_id, json_encode($cha), IAHelper::datesent());
						return $params;
					}

					if(isset($client_response2->fundtransferresponse->status->code) 
					&& $client_response2->fundtransferresponse->status->code == "200"){
						$params = [
							"code" => $status_code,
							"data" => [
								"available_balance" => IAHelper::amountToFloat($client_response2->fundtransferresponse->balance),
								"status" => 1,
							],
							"message" => "Success",
						];	
						IAHelper::updatecreateGameTransExt($game_transextension2, $cha, $params, $client_response2->requestoclient, $client_response2,$params);
						IAHelper::updateGameTransaction($gamerecord1, 0, $check_exist_bet->amount, 0, 1,'game_trans_id');
						return $params;
					}elseif(isset($client_response2->fundtransferresponse->status->code) 
					&& $client_response2->fundtransferresponse->status->code == "402"){
						IAHelper::updateGameTransactionStatus($gamerecord1, 2, 99);
						$params = ["code" => 111005,"data" => [],"message" => "withdrawal failed client error"];
						IAHelper::updatecreateGameTransExt($game_transextension2, $cha, $params, $client_response2->requestoclient, $client_response2,$params);
						return $params;
					}

				}else{
					$gamerecord1  = IAHelper::createGameTransaction($token_id, $game_details, $bet_amount,  $pay_amount, $method, 0, null, $payout_reason, $income, $provider_trans_id, $cha->projectId);
					$game_transextension1 = IAHelper::createGameTransExtV2($gamerecord1,$cha->orderId, $cha->projectId, $cha->money, 1);

					try {
						$client_response = ClientRequestHelper::fundTransfer($client_details,$cha->money,$this->game_code,$this->game_name,$game_transextension1,$gamerecord1,$transaction_type);
						IAHelper::saveLog('IA seamlessWithdrawal CRID',  $this->provider_db_id, json_encode($cha), $client_response);
					} catch (\Exception $e) {
						if(isset($gamerecord1)){
							IAHelper::updateGameTransactionStatus($gamerecord1, 2, 99);
						}
						$params = ["code" => 111005,"data" => [],"message" => "withdrawal failed client error"];
						IAHelper::updatecreateGameTransExt($game_transextension1, 'FAILED', $params, 'FAILED', $e->getMessage(), false, 'FAILED');
						IAHelper::saveLog('IA seamlessWithdrawal - FATAL ERROR', $this->provider_db_id, json_encode($cha), IAHelper::datesent());
						return $params;
					}

					if(isset($client_response->fundtransferresponse->status->code) 
					&& $client_response->fundtransferresponse->status->code == "200"){
						
						IAHelper::updatecreateGameTransExt($game_transextension1, $cha, $data, $client_response->requestoclient, $client_response,$data);
						// AUTO MATIC 0 WIN AMOUNT
						$game_transextension2 = IAHelper::createGameTransExtV2($gamerecord1,$cha->orderId, $cha->projectId, 0, 2);

						try {
							$client_response2 = ClientRequestHelper::fundTransfer($client_details,0,$this->game_code,$this->game_name,$game_transextension2,$gamerecord1,'credit');
							IAHelper::saveLog('IA seamlessWithdrawal CRID',  $this->provider_db_id, json_encode($cha), $client_response2);
						} catch (\Exception $e) {
							if(isset($gamerecord1)){
								IAHelper::updateGameTransactionStatus($gamerecord1, 2, 99);
							}
							$params = ["code" => 111005,"data" => [],"message" => "withdrawal failed client error"];
							IAHelper::updatecreateGameTransExt($game_transextension2, 'FAILED', $params, 'FAILED', $e->getMessage(), false, 'FAILED');
							IAHelper::saveLog('IA seamlessWithdrawal - FATAL ERROR', $this->provider_db_id, json_encode($cha), IAHelper::datesent());
							return $params;
						}
						$params = [
							"code" => $status_code,
							"data" => [
								"available_balance" => IAHelper::amountToFloat($client_response->fundtransferresponse->balance),
								"status" => 1,
							],
							"message" => "Success",
						];	
						IAHelper::updatecreateGameTransExt($game_transextension2, $cha, $params, $client_response2->requestoclient, $client_response2,$params);
					
					}elseif(isset($client_response->fundtransferresponse->status->code) 
					&& $client_response->fundtransferresponse->status->code == "402"){
						if(IAHelper::checkFundStatus($client_response->fundtransferresponse->status->status)):
						IAHelper::updateGameTransactionStatus($gamerecord1, 2, 6);
						else:
						IAHelper::updateGameTransactionStatus($gamerecord1, 2, 99);
						endif;
						$params = [
							"code" => 111004,
							"data" => [],
							"message" => "Insufficient balance",
						];

					}else{
						// ERROR STATUS CODE
						IAHelper::updateGameTransactionStatus($gamerecord1, 2, 99);
						$params = ["code" => 111005,"data" => [],"message" => "withdrawal failed client error"];
					}
					IAHelper::saveLog('IA seamlessWithdrawal - SUCCESS', $this->provider_db_id,json_encode($cha), $params);
					return $params;
				}

				
	        }else{

				// Check for EGAME TRANSACTION
				if($check_exist_bet != 'false'){
					$params = [
						"code" => 111007,
						"data" => [],
						"message" => "Order number already exists",
					];	
					return $params;

				# 20-11-20 New Added Else For Trapping 
				}else{ 

					$gamerecord  = IAHelper::createGameTransaction($token_id, $game_details, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $cha->projectId);
					$game_transextension = IAHelper::createGameTransExtV2($gamerecord, $cha->orderId, $cha->projectId, $cha->money, 1);
					try {
						$client_response = ClientRequestHelper::fundTransfer($client_details, $cha->money, $this->game_code, $this->game_name, $game_transextension, $gamerecord, $transaction_type);
						IAHelper::saveLog('IA seamlessWithdrawal CRID',  $this->provider_db_id, json_encode($cha), $client_response);
					} catch (\Exception $e) {
						$params = ["code" => 111005, "data" => [], "message" => "withdrawal failed client error e"];
						if (isset($gamerecord)) {
							IAHelper::updateGameTransactionStatus($gamerecord, 2, 99);
							IAHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $params, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
						}
						IAHelper::saveLog('IA seamlessWithdrawal - FATAL ERROR', $this->provider_db_id, json_encode($cha), IAHelper::datesent());
						return $params;
					}

					if (isset($client_response->fundtransferresponse->status->code) && $client_response->fundtransferresponse->status->code == "200"):

						$params = [
							"code" => $status_code,
							"data" => [
								"available_balance" => IAHelper::amountToFloat($client_response->fundtransferresponse->balance),
								"status" => 1,
							],
							"message" => "Success",
						];

						if (count($is_project_multiple) > 1) {
							// $gamerecords = array();
							$game_transextensions = array();
							foreach ($is_project_multiple as $round_project_id) {
								// $gamerecord  = IAHelper::createGameTransaction($token_id, $game_details, 0,  0, $method, 1, null, $payout_reason, 0, $provider_trans_id, $round_project_id);
								// array_push($gamerecords, $gamerecord);
								$game_transextension_loop = IAHelper::createGameTransExtV2($gamerecord, $cha->orderId, $round_project_id, 0, 1);
								array_push($game_transextensions, $game_transextension_loop);
							}

							foreach ($game_transextensions as $gt_id) {
								IAHelper::updatecreateGameTransExt($gt_id, $cha, $params, $client_response->requestoclient, $client_response, $params);
							}

							IAHelper::updatecreateGameTransExt($game_transextension, $cha, $params, $client_response->requestoclient, $client_response, $params);
						} else {
							IAHelper::updatecreateGameTransExt($game_transextension, $cha, $params, $client_response->requestoclient, $client_response, $params);
						}

					elseif (isset($client_response->fundtransferresponse->status->code) && $client_response->fundtransferresponse->status->code == "402"):
						if (IAHelper::checkFundStatus($client_response->fundtransferresponse->status->status)) :
							IAHelper::updateGameTransactionStatus($gamerecord, 2, 99);
							IAHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $params, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
						else :
							IAHelper::updateGameTransactionStatus($gamerecord, 2, 99);
							IAHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $params, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
						endif;
						$params = [
							"code" => 111004,
							"data" => [],
							"message" => "Insufficient balance",
						];
					else :
						IAHelper::updateGameTransactionStatus($gamerecord, 2, 99);
						IAHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $params, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
						$params = ["code" => 111005, "data" => [], "message" => "withdrawal failed client error"];
						IAHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $params, 'FAILED', $client_response, 'FAILED', 'FAILED');
					endif;
				}
		 	    	
	        }

		IAHelper::saveLog('IA Withrawal Response', $this->provider_db_id,json_encode($cha), $params);
		return $params;
	}

	/**
	 * @return Player Balance
	 *
	 */
	public function seamlessBalance(Request $request)
	{	
		IAHelper::saveLog('IA Balance', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'IA CALL');
		$data_received = file_get_contents("php://input");
		$cha = json_decode($this->rehashen($data_received, true));
		IAHelper::saveLog('IA Balance', $this->provider_db_id, json_encode($cha), 'IA CALL DECODED');
		$prefixed_username = explode("_", $cha->username);
		$client_details = IAHelper::getClientDetails('player_id', $prefixed_username[1]);
		$client_response = IAHelper::playerDetailsCall($client_details);
		if($client_response == 'false'){
			$params = ["code" => 111003,"data" => [],"message" => "User does not exist"];
		}else{
			$params = [
	            "code" => '200',
	            "data" => [
	            	"available_balance" => IAHelper::amountToFloat($client_response->playerdetailsresponse->balance),
	            ],
				"message" => "Success",
	        ];	
		}
        return $params;
	}

	/**
	 * Check Order ID if exist
	 *
	 */
	public function seamlessSearchOrder(Request $request)
	{	
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
		IAHelper::saveLog('IA Search Order', 2, json_encode($cha), json_encode($params));
		return $params;
	}


	/**
	 * Deprecated But Dont Remove!
	 * 
	 */
	public function getHotGames(Request $request)
	{
		$header = ['pch:'. $this->pch];
        $params = array();
        $uhayuu = $this->hashen($params);
		$timeout = 5;
		$client_response = $this->curlData($this->url_hotgames, $uhayuu, $header, $timeout);
		$data = json_decode($this->rehashen($client_response[1], true));

		$event_list = array();
		if($data->status == 0):
			return ["Tiger Games API" => $this->api_version,"date" => IAHelper::datesent(), "code" => 408, "msg" => "Server is busy/Request too frequently"];
		elseif($data->status == 1):

			foreach ($data->data as $key):
				$event = [
					"event_name" => $key->event_name,
					"team_1" => [
						"team_name" => $key->team_name_1,
						"team_logo" => $key->team_logo_1,
					],
					"team_2" => [
						"team_name" => $key->team_name_2,
						"team_logo" => $key->team_logo_2,
					],
					"status" => $key->is_end == 0 ? "gaming"  : "ended"
				];
				array_push($event_list, $event);
			endforeach;
			$response_data = [
				"Tiger Games API" => $this->api_version,
				"events" => $event_list,
				"date" => IAHelper::datesent(),
				"code" => 200,
				"msg" => "Success"
			];
			return $response_data;
		endif;
		
	}

	public function GG(){
		$params = ["code" => 999,"data" => [],"message" => "CRON JOB"];
		IAHelper::saveLog('IA SETTLED al:cron triggered', 1223, json_encode('CRONJOB WAS TRIGGERED'), IAHelper::datesent());
	}


	public function SettleRounds(){
		IAHelper::saveLog('IA SETTLED al:cron triggered', 1223, json_encode('CRONJOB WAS TRIGGERED'), IAHelper::datesent());
		$start_time = strtotime('-3 day'); 
		$end_time = strtotime('+3 day'); 
		$header = ['pch:'. $this->pch];
        $params = array(
			"start_time" => $start_time, 
			"end_time" => $end_time, // 2023604346
			"page" => 1,
			"limit" => 10000,
			"is_settle" => -1, // Default:1, 1 is settled,0 is not ,-1 is all.
			// "order_id" => $order_id, //'GAMEVBDDCFBEJK,GAMEVBDDCFBFAK',
        );
		try {
	        $uhayuu = $this->hashen($params);
			$timeout = 5;
			$client_response = $this->curlData($this->url_wager, $uhayuu, $header, $timeout);
			if(!isset($client_response[1])){
				IAHelper::saveLog('IA SETTLE ROUND - NO RESPONSE', $this->provider_db_id, json_encode($client_response), 'SETTLE ROUNDS FAILED');
					return;
			}
			$data = json_decode($this->rehashen($client_response[1], true));
			IAHelper::saveLog('CALL DATA', 1223, json_encode($data), 'ALL ROUNDS');
			if(!isset($data->data->list)){
					IAHelper::saveLog('IA SETTLE ROUND - NO LIST', $this->provider_db_id, json_encode($data), 'SETTLE ROUNDS FAILED II');
					return;
			}
			$order_ids = array(); // round_id's to check in game_transaction with win type 5/processing
			if(isset($data)):
				foreach ($data->data->list as $matches):
					if($matches->prize_status == 2):
						array_push($order_ids, $matches->order_id);
					endif;
				endforeach;
			endif;
			if(count($order_ids) > 0):
				$update = $this->getAllGameTransaction(5);
				$game_transactions_ext = array();
				if($update != 'false'):
				    foreach($update as $up):
				    	$allgg = $this->getAllTransactionID($up->game_trans_id);
				    	foreach ($allgg as $key) {
				    		if(in_array($key->round_id, $order_ids)){
				    			array_push($game_transactions_ext, $key);
				    		}
				    	}				    
				    endforeach;

				    if(count($game_transactions_ext) > 0){
				    	foreach($game_transactions_ext as $gte_ids):
				    		$gt_data = IAHelper::findGameTransaction($gte_ids->game_trans_id,'game_transaction');
					    	// $existing_game_ext = IAHelper::findGameExt($up->round_id, 1, 'round_id');
					    	$client_details = IAHelper::getClientDetails('token_id', $gt_data->token_id);

					    	$existing_game_ext = IAHelper::findGameExt($gte_ids->round_id, 2, 'round_id');
					    	if($existing_game_ext != 'false'){
					    		$game_transextension = $existing_game_ext->game_trans_ext_id;
					    	}else{
					    		$game_transextension = IAHelper::createGameTransExtV2($gte_ids->game_trans_id,$gte_ids->round_id, $gte_ids->round_id, 0, 2);
					    	}
					    	
	            			try {
	            				$client_response = ClientRequestHelper::fundTransfer($client_details,0,$this->game_code,$this->game_name,$game_transextension,$gte_ids->game_trans_id,'credit');
		            			$response = [
		            				"message" => 'This was successfully Updated to lost',
		            				"orderid" => $gte_ids->round_id
		            			];
								IAHelper::updatecreateGameTransExt($game_transextension, $response, $response, $client_response->requestoclient, $client_response,$response);

								if($gt_data->win == 5){
									DB::table('game_transactions')
						                ->where('game_trans_id', $gte_ids->game_trans_id)
						                ->update([
						        		  'win' => 0, 
						        		  'transaction_reason' => 'Bet updated'
					    			]);
								}
						    	
	            			} catch (\Exception $e) {
	            				$existing_game_ext = IAHelper::findGameExt($gte_ids->round_id, 2, 'round_id');
	            				$response = [
		            				"message" => 'Failed to Updated to lost '.$e->getMessage(),
		            				"orderid" => $up->round_id
		            			];
								IAHelper::updatecreateGameTransExt($existing_game_ext->game_trans_ext_id, $response, $response, $response, $response,$response);
	            				continue;
	            			}
					    endforeach;
				    }
				endif;
	 		endif;

	 		IAHelper::saveLog('IA Search Order SUCCESS', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'SUCCESS');
		} catch (\Exception $e) {
			IAHelper::saveLog('IA Search Order Failed', $this->provider_db_id, json_encode(file_get_contents("php://input")), $e->getMessage());
		}
	}

	/**
	 * Check All Settled Matches
	 * Look for prize_status :2, //1 is win, 2 is lose
	 * IA Store matches for maximum of 3 Days Only
	 * 
	 */
	public function userWager(Request $request)
	{
		IAHelper::saveLog('IA API WAGER', 2, file_get_contents("php://input"), 'IA API WAGER');
		$data_body = json_decode(file_get_contents("php://input"));
		$roundIds = array();
		$orderIds = '';
		if(!isset($data_body->roundId)){
			IAHelper::saveLog('IA API WAGER', $this->provider_db_id, file_get_contents("php://input"), 'No Round ID');
			return ["Tiger Games API" => $this->api_version, "date" => IAHelper::datesent(), "code" => 400, "msg" => "missing parameter"];
		}
		if(count($data_body->roundId) == 0){
			IAHelper::saveLog('IA API WAGER', $this->provider_db_id, file_get_contents("php://input"), 'No Round ID');
			return ["Tiger Games API" => $this->api_version,"date" => IAHelper::datesent(),  "code" => 400, "msg" => "round id could not be empty"];
		}
		foreach ($data_body->roundId as $round) {
			// $round_details = IAHelper::findGameExt($round, 1, 'game_trans_id');
			$round_details = IAHelper::findGameTransaction($round,'game_transaction');
			if($round_details != 'false'){
				$is_project_multiple = explode(',', $round_details->round_id);
				foreach ($is_project_multiple as $key) {
					array_push($roundIds, $key);
				}
				// array_push($roundIds, $round_details->round_id);
			}
		}

		if(count($roundIds) == 0){
			IAHelper::saveLog('IA API WAGER', $this->provider_db_id, file_get_contents("php://input"), 'No Round ID II');
			return ["Tiger Games API" => $this->api_version,"date" => IAHelper::datesent(),  "code" => 404, "msg" => "round's not found"];
		}
		if(isset($data_body->filter) && $data_body->filter === 'settled'){
			$filter = 1; // settled
			$msg = 'There are no settled in the rounds';
		}elseif(isset($data_body->filter) &&  $data_body->filter === 'unsettled'){
			$filter = 0; // unsettled
			$msg = 'There are no unsettled in the rounds';
		}else{
			$filter = -1; // All
			$msg = 'Round Id not found/Record no longer Exists';
		}
		$order_id = implode(",", $roundIds);

		$start_time = strtotime('-3 day'); 
		$end_time = strtotime('+3 day'); 
		$header = ['pch:'. $this->pch];
        $params = array(
			"start_time" => $start_time, 
			"end_time" => $end_time, // 2023604346
			"page" => 1,
			"limit" => 10000,
			"is_settle" => $filter, // Default:1, 1 is settled,0 is not ,-1 is all.
			"order_id" => $order_id, //'GAMEVBDDCFBEJK,GAMEVBDDCFBFAK',
        );
        // return $params;
        $uhayuu = $this->hashen($params);
		$timeout = 5;

			try {
				$client_response = $this->curlData($this->url_wager, $uhayuu, $header, $timeout);
				if(!isset($client_response[1])){
					IAHelper::saveLog('IA API WAGER - NO RESPONSE', $this->provider_db_id, file_get_contents("php://input"), $data);
					return ["Tiger Games API" => $this->api_version,"date" => IAHelper::datesent(), "code" => 408, "msg" => "Server is busy."];
				}
				$data = json_decode($this->rehashen($client_response[1], true));
				if(!isset($data->data->list)){
					IAHelper::saveLog('IA API WAGER - NO LIST', $this->provider_db_id, file_get_contents("php://input"), $data);
					return ["Tiger Games API" => $this->api_version,"date" => IAHelper::datesent(), "code" => 408, "msg" => "Server is busy.."];
				}
				if(count($data->data->list) == 0){
					IAHelper::saveLog('IA API WAGER - 0 list', $this->provider_db_id, file_get_contents("php://input"), $data);
					return ["Tiger Games API" => $this->api_version,"date" => IAHelper::datesent(), "code" => 404, "msg" => $msg];
				}

				$game_wager = array();
				if(isset($data)):
					IAHelper::saveLog('IA API WAGER - SUCCESS', $this->provider_db_id, file_get_contents("php://input"), $data);
					foreach ($data->data->list as $matches):
						$prefixed_username = explode("_", $matches->username);
						// $client_details = IAHelper::getClientDetails('player_id', 98);
						$client_details = IAHelper::getClientDetails('player_id', $prefixed_username[1]);
						$round_details = IAHelper::findGameExt($matches->order_id, 1, 'round_id');
						if($client_details != 'false' && $round_details != 'false'):

							if($matches->team_name == "[team1]"){
								$team = $matches->team_name_1;
							}elseif($matches->team_name == "[team2]"){
								$team = $matches->team_name_2;
							}else{
								$team = $matches->team_name;
							}

							$my_team  = $team;
							$user_round = [
								"user_info" => [
									"player_username" => $client_details->username,
				                    "client_player_id" => $client_details->client_player_id,
				                    "token" => $client_details->player_token
								],
								"round_info" => [
									"roundId" => $round_details->game_trans_id,
									"event_name" => $matches->event_name,
									"game_name" => $matches->game_name,
									"team_name_1" => $matches->team_name_1,
									"team_name_2" => $matches->team_name_2,
									"my_team" => $my_team,
									"team_info_desc" => $matches->team_info_desc, // Optional
									"settled" => $matches->is_getprize == 1 ? true : false,
									"status"  => $matches->prize_status == 1 ? 'win' : 'lost'
								]
							];
							array_push($game_wager, $user_round);
						endif;
					endforeach;
				endif;

				if(count($game_wager) != 0):
					$response_data = [
						"Tiger Games API" => $this->api_version,
						"metadata" => $game_wager,
						"date" => IAHelper::datesent(),
						"code" => 200,
						"msg" => "Success"
					];
				else:
					$response_data = [
						"Tiger Games API" => $this->api_version,
						"date" => IAHelper::datesent(),
						"code" => 404,
						"msg" => $msg
					];
				endif;
				IAHelper::saveLog('IA API WAGER - 0 list', $this->provider_db_id, file_get_contents("php://input"), $response_data);
				return $response_data;
			} catch (\Exception $e) {
				IAHelper::saveLog('IA API WAGER', 2, file_get_contents("php://input"), $e->getMessage());
				return ["Tiger Games API" => $this->api_version, "code" => 500, "msg" => "Server is busy"];
			}

			
			
			
		// try {

			// BELOW NO USE FOR NOW @RiANDRAFT

			////////////////////////////////////////////////////////////////////////////////////////////
	
			// $order_ids = array(); // round_id's to check in game_transaction with win type 5/processing
			// if(isset($data)):
			// 	foreach ($data->data->list as $matches):
			// 		if($matches->prize_status == 2):
			// 			array_push($order_ids, $matches->order_id);
			// 		endif;
			// 	endforeach;
			// endif;
			// if(count($order_ids) > 0):
			// 	$update = $this->getAllGameTransaction($order_ids, 5);
			// 	if($update != 'false'):
			// 	    foreach($update as $up):

			// 	    	$client_details = IAHelper::getClientDetails('token_id', $up->token_id);

			// 	    	$existing_game_ext = IAHelper::findGameExt($up->round_id, 2, 'round_id');
			// 	    	if($existing_game_ext != 'false'){
			// 	    		$game_transextension = $existing_game_ext->game_trans_ext_id;
			// 	    	}else{
			// 	    		$game_transextension = IAHelper::createGameTransExtV2($up->game_trans_id,$up->round_id, $up->round_id, 0, 2);
			// 	    	}
				    	
   //          			try {
   //          				$client_response = ClientRequestHelper::fundTransfer($client_details,0,$this->game_code,$this->game_name,$game_transextension,$up->game_trans_id,'credit');
	  //           			$response = [
	  //           				"message" => 'This was successfully Updated to lost',
	  //           				"orderid" => $up->round_id
	  //           			];
			// 				IAHelper::updatecreateGameTransExt($game_transextension, $response, $response, $client_response->requestoclient, $client_response,$response);

			// 		    	DB::table('game_transactions')
			// 	                ->where('round_id', $up->round_id)
			// 	                ->update([
			// 	        		  'win' => 0, 
			// 	        		  'transaction_reason' => 'Bet updated'
			//     			]);
   //          			} catch (\Exception $e) {
   //          				$existing_game_ext = IAHelper::findGameExt($up->round_id, 2, 'round_id');
   //          				$response = [
	  //           				"message" => 'Failed to Updated to lost '.$e->getMessage(),
	  //           				"orderid" => $up->round_id
	  //           			];
			// 				IAHelper::updatecreateGameTransExt($existing_game_ext->game_trans_ext_id, $response, $response, $response, $response,$response);
   //          				continue;
   //          			}

			// 	    endforeach;
			// 	endif;
	 	// 	endif;
	 	// 	IAHelper::saveLog('IA Search Order SUCCESS', $this->provider_db_id, json_encode($data), 'SUCCESS');
		// } catch (\Exception $e) {
		// 	IAHelper::saveLog('IA Search Order Failed', $this->provider_db_id, json_encode($params), $e->getMessage());
		// }
	}

	// public function createGameTransExt($game_trans_id, $provider_trans_id, $round_id, $amount, $game_type, $provider_request, $mw_response, $mw_request, $client_response, $transaction_detail){

	// 	$gametransactionext = array(
	// 		"game_trans_id" => $game_trans_id,
	// 		"provider_trans_id" => $provider_trans_id,
	// 		"round_id" => $round_id,
	// 		"amount" => $amount,
	// 		"game_transaction_type"=>$game_type,
	// 		"provider_request" => json_encode($provider_request),
	// 		"mw_response" =>json_encode($mw_response),
	// 		"mw_request"=>json_encode($mw_request),
	// 		"client_response" =>json_encode($client_response),
	// 		"transaction_detail" =>json_encode($transaction_detail),
	// 	);
	// 	$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
	// 	return $gametransactionext;

	// }

	/**
	 * Find bet and update to win 
	 *
	 */
	public  function updateBetToWin($round_id, $pay_amount, $income, $win, $entry_id) {
   	    $update = DB::table('game_transactions')
                ->where('game_trans_id', $round_id)
				// ->where('round_id', $round_id)
                ->update(['pay_amount' => $pay_amount, 
	        		  'income' => $income, 
	        		  'win' => $win, 
	        		  'entry_id' => $entry_id,
	        		  'transaction_reason' => 'Bet updated to win'
	    		]);
		return ($update ? true : false);
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
	 * Find Game Transaction Ext
	 * @param [string] $[round_ids] [<round id for bets>]
	 * @param [string] $[type] [<0 Lost, 1 win, 3 draw, 4 refund, 5 processing>]
	 * 
	 */
    public  function getAllGameTransaction($type) 
    {
		$game_transactions = DB::table("game_transactions")
					->where('win', $type)
					->where('payout_reason', 'LIKE', '%Stake deduction%')
				    // ->whereIn('round_id', $round_ids)
				    ->get();
	    return (count($game_transactions) > 0 ? $game_transactions : 'false');
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


     public  function getOrderData($order_id) 
    {
		$game_transactions = DB::table("game_transactions")
					->where('round_id', $order_id)
				    ->latest()
				    ->first();		    
		return $game_transactions ? $game_transactions : false;
    }

    public  function getAllTransactionID($provider_identifier) {
		$transaction_db = DB::table('game_transaction_ext as gte');
		$transaction_db->where([
				["gte.game_transaction_type", "=", 1],
		 		["gte.game_trans_id", "=", $provider_identifier],
	 	]);
		$result = $transaction_db->get();
		return $result ? $result : 'false';
	}


    /**
	 * Find Game Transaction Ext
	 * @param [string] $[provider_transaction_id] [<provider transaction id>]
	 * @param [int] $[game_transaction_type] [<1 bet, 2 win, 3 refund>]
	 * @param [string] $[type] [<transaction_id, round_id>]
	 * 
	 */
	// public  function findGameExt($provider_transaction_id, $game_transaction_type, $type) {
	// 	$transaction_db = DB::table('game_transaction_ext as gte');
    //     if ($type == 'transaction_id') {
	// 		$transaction_db->where([
	// 	 		["gte.provider_trans_id", "=", $provider_transaction_id],
	// 	 		["gte.game_transaction_type", "=", $game_transaction_type],
	// 	 		["gte.transaction_detail", "!=", '"FAILED"'],
	// 	 	]);
	// 	}
	// 	if ($type == 'round_id') {
	// 		$transaction_db->where([
	// 	 		["gte.round_id", "=", $provider_transaction_id],
	// 	 		["gte.game_transaction_type", "=", $game_transaction_type],
	// 	 		["gte.transaction_detail", "!=", '"FAILED"'],
	// 	 	]);
	// 	}  
	// 	$result= $transaction_db->first();
	// 	return $result ? $result : 'false';
	// }


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
				case 18:
					$type = 'Bet Rejected Slip';
					break;
				default:		
					$type = 'Unknown operation type '.$getCodeType;
			}	
				return $type;
	}

	/**
	 * Api Call Provided by IA Gaming
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


}
