<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
// use App\Helpers\CryptAES;
use App\Helpers\ClientRequestHelper;
use App\Helpers\CallParameters;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;
use DB;



/**
 * IA ESports Controller (Seamless Setup)
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

	public $game_code = 'ia-lobby';
	public $game_name = 'IA Gaming';
	public $provider_db_id = 15;
	public $prefix = 'TGAMES';
	public $api_version = 'version 1.0';


	public function __construct(){
		// $this->middleware('oauth', ['except' => ['index','seamlessDeposit','seamlessWithdrawal','seamlessBalance','seamlessSearchOrder','userlaunch']]);
		// $this->middleware('authorize:' . __CLASS__, ['except' => ['index', 'store']]);
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
		Helper::saveLog('IA REGISTER', 2, 'REGISTER', 'DEMO CALL');
		$token=Helper::checkPlayerExist($request->client_id,$request->client_player_id,$request->username,$request->email,$request->display_name,$request->token,$request->game_code);
		$player_details = ProviderHelper::getClientDetails('token', $token);
		$username = $this->prefix.'_'.$player_details->player_id;
		// $prefixed_username = explode("_", $request->username);
		// dd($prefixed_username[1]);
		// $player = ProviderHelper::getClientDetails('username_and_cid', $request->username, $request->client_id);
		// $currency_code = $request->has('currency_code') ? $request->currency_code : 'RMB'; 
		// $currency_code = $request->has('currency_code') ? $request->currency_code : 'USD'; 
		$currency_code = isset($player_details->default_currency) ? $player_details->default_currency : 'USD'; 
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
		Helper::saveLog('IA Deposit', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'IA CALL');
		$data = file_get_contents("php://input");
		$cha = json_decode($this->rehashen($data, true)); // DECODE THE ENCRYPTION
		$desc_json = json_decode($cha->desc,JSON_UNESCAPED_SLASHES); // REMOVE SLASHES
		$transaction_code = $desc_json['code']; // 13,15 refund, 
		$rollback = $transaction_code == 13 || $transaction_code == 15 ? true : false;
		$prefixed_username = explode("_", $cha->username);
		$client_details = ProviderHelper::getClientDetails('player_id', $prefixed_username[1]);
		Helper::saveLog('IA seamlessDeposit EH', $this->provider_db_id,json_encode($cha), $data);
		// dd($cha);
		if(empty($client_details)):
			$params = [
	            "code" => 111003,
	            "data" => [],
				"message" => "User does not exist",
	        ];	
			return $params;
		endif;	
		$is_project_multiple = explode(',', $cha->projectId);
		
		// if($this->getOrder($cha->orderId)):
		// dd($this->findGameExt($cha->orderId, 2, 'transaction_id'));
		if($this->findGameExt($cha->orderId, 2, 'transaction_id') != 'false'):
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
		$game_details_info = Helper::findGameDetails('game_code', $this->provider_db_id, $this->game_code);
		$game_details = $game_details_info->game_id; 
		$bet_amount = $cha->money;
		$pay_amount = $cha->money; // Zero Payout
		$method = $transaction_type == 'debit' ? 1 : 2;
		$win_or_lost = 1;
		$payout_reason = $this->getCodeType($desc_json['code']) .' : '.$desc_json['message'];
		$income = 0;	
		$provider_trans_id = $cha->orderId;
	
		$client_player = ProviderHelper::playerDetailsCall($client_details->player_token);
		if($client_player == 'false'){
			$params = ["code" => 111006,"data" => [],"message" => "deposit failed client error"];
			Helper::saveLog('IA seamlessWithdrawal - FATAL ERROR', $this->provider_db_id, json_encode($cha), Helper::datesent());
	        return $params;
		}
		if($client_player->playerdetailsresponse->balance > $cha->money):


		    if($transaction_code == 16 || $transaction_code == 17){ // AUTO CHESS GAME // 1 WAY FLIGHT
		    	// IF CALL IS CREDIT AUTO BET IS ZERO AND WIN WILL BE THE EXACT AMOUNT
	        	$transaction_type = 'credit';
				$token_id = $client_details->token_id;
				$bet_amount = $cha->money;
				$pay_amount = $cha->money; // Zero Payout
				$method = 2;
				$entry_id = 2; //win
				$win_or_lost = 1;
				$payout_reason = $this->getCodeType($desc_json['code']) .' : '.$desc_json['message'];
				$income = '-'.$bet_amount;	
				$provider_trans_id = $cha->orderId;

	        	// FIRST CALL BET ZERO
	        	$auto_chess_bet = 0;
	        	$gamerecord1  = ProviderHelper::createGameTransaction($token_id, $game_details, 0, 0, 1, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $cha->projectId);
	        	$game_transextension1 = ProviderHelper::createGameTransExtV2($gamerecord1,$provider_trans_id, $cha->projectId, $auto_chess_bet, 1);

	        	try {
	        		$client_response1 = ClientRequestHelper::fundTransfer($client_details,$auto_chess_bet,$this->game_code,$this->game_name,$game_transextension1,$gamerecord1,'debit');
        		    Helper::saveLog('IA seamlessDeposit CRID',  $this->provider_db_id, json_encode($cha), $client_response1);
	        	} catch (\Exception $e) {
	        		$params = ["code" => 111006,"data" => [],"message" => "deposit failed client error"];
	        		if(isset($gamerecord1)){
		        		ProviderHelper::updateGameTransactionStatus($gamerecord1, 2, 99);
		        		ProviderHelper::updatecreateGameTransExt($game_transextension1, 'FAILED', $params, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
		        	}
            	    Helper::saveLog('IA seamlessDeposit - FATAL ERROR', $this->provider_db_id, json_encode($cha), Helper::datesent());
            	    return $params;
	        	}

        		if(isset($client_response1->fundtransferresponse->status->code) 
                && $client_response1->fundtransferresponse->status->code == "200"){

		        	// SECOND CALL ACTUAL WINNING
		        	$game_transextension2 = ProviderHelper::createGameTransExtV2($gamerecord1,$cha->orderId, $cha->projectId, $cha->money, 2);
		        	try {
		        		$client_response2 = ClientRequestHelper::fundTransfer($client_details,$cha->money,$this->game_code,$this->game_name,$game_transextension2,$gamerecord1,$transaction_type);
		        		Helper::saveLog('IA seamlessDeposit CRID',  $this->provider_db_id, json_encode($cha), $client_response2);
		        	} catch (\Exception $e) {
		        		$params = ["code" => 111006,"data" => [],"message" => "deposit failed client error"];
		        		if(isset($gamerecord1)){
		        			ProviderHelper::updateGameTransactionStatus($gamerecord1, 2, 99);
                 	   		ProviderHelper::updatecreateGameTransExt($game_transextension1, 'FAILED', $params, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
		        		}
            	        Helper::saveLog('IA seamlessDeposit - FATAL ERROR', $this->provider_db_id, json_encode($cha), Helper::datesent());
            	        return $params;
		        	}
	    			$params = [
			            "code" => $status_code,
			            "data" => [
			            	"available_balance" => ProviderHelper::amountToFloat($client_response2->fundtransferresponse->balance),
			            	"status" => 1,
			            ],
						"message" => "Success",
			        ];	
			        ProviderHelper::updatecreateGameTransExt($game_transextension1, $cha, $params, $client_response1->requestoclient, $client_response1,$params);
		       		ProviderHelper::updatecreateGameTransExt($game_transextension2, $cha, $params, $client_response2->requestoclient, $client_response2,$params);
	        		$this->updateBetToWin($cha->projectId, $pay_amount, $income, 1, 2);
	        		
	        	}elseif(isset($client_response->fundtransferresponse->status->code) 
		            && $client_response->fundtransferresponse->status->code == "402"){
	        		if(ProviderHelper::checkFundStatus($client_response->fundtransferresponse->status->status)):
		          	   ProviderHelper::updateGameTransactionStatus($gamerecord1, 2, 6);
		            else:
		               ProviderHelper::updateGameTransactionStatus($gamerecord1, 2, 99);
		            endif;
	        		$params = [
			            "code" => 111004,
			            "data" => [],
						"message" => "Insufficient balance",
			        ];
				}else{
					ProviderHelper::updateGameTransactionStatus($gamerecord1, 2, 6);
					$params = ["code" => 111005,"data" => [],"message" => "failed client error"];
				}
        		Helper::saveLog('IA seamlessDeposit - SUCCESS', $this->provider_db_id,json_encode($cha), $params);
				return $params;
	        }else{
	        	// $bet_details = $this->getOrderData($cha->projectId);
	        	$is_exist_bet = ProviderHelper::findGameExt($cha->projectId, 1,'round_id');
	        	if($is_exist_bet == 'false'){
	        		$params = [
			            "code" => 111006,
			            "data" => [],
						"message" => "Deposit Failed, Order number dont exist!",
			        ];	
					return $params;
	        	}
	        	$bet_details = ProviderHelper::findGameTransaction($is_exist_bet->game_trans_id,'game_transaction');
  				$win = 1; //win
  				$entry_id = 2; //win
  				$income = $bet_details->bet_amount - $cha->money;
	        	$win = $transaction_code == 13 || $transaction_code == 15 ? 4 : $win; // 4 to refund!
 	  			$is_refunded = $transaction_code == 13 || $transaction_code == 15 ? 3 : 2; // 3 to refund!

	        	$mw_request_data = json_decode($is_exist_bet->mw_request);
	        	$gamerecord = $mw_request_data->fundtransferrequest->fundinfo->roundId;
	        	$game_transextension = ProviderHelper::createGameTransExtV2($gamerecord,$cha->orderId, $cha->projectId, $cha->money, $is_refunded);
	        }

	        try {
	        	$client_response = ClientRequestHelper::fundTransfer($client_details,$cha->money,$this->game_code,$this->game_name,$game_transextension,$gamerecord,$transaction_type, $rollback);
	        	Helper::saveLog('IA seamlessDeposit CRID',  $this->provider_db_id, json_encode($cha), $client_response);
	        } catch (\Exception $e) {
	        	$params = ["code" => 111006,"data" => [],"message" => "deposit failed client error"];
    	        Helper::saveLog('IA seamlessDeposit - FATAL ERROR', $this->provider_db_id, json_encode($cha), Helper::datesent());
    	        return $params;
	        }


	        if(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "200"):

			    // $this->updateBetToWin($cha->projectId, $pay_amount, $income, $win, $entry_id);
			    ProviderHelper::updateGameTransaction($gamerecord, $pay_amount, $income, $win, $entry_id,'game_trans_id');

	        	$params = [
		            "code" => $status_code,
		            "data" => [
		            	"available_balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
		            	"status" => 1,
		            ],
					"message" => "Success",
		        ];	

		      	ProviderHelper::updatecreateGameTransExt($game_transextension, $cha, $params, $client_response->requestoclient, $client_response,$params);

			elseif(isset($client_response->fundtransferresponse->status->code)
	            && $client_response->fundtransferresponse->status->code == "402"):
	            // if(ProviderHelper::checkFundStatus($client_response->fundtransferresponse->status->status)):
	          	 //   ProviderHelper::updateGameTransactionStatus($gamerecord, 2, 6);
	            // else:
	            //    ProviderHelper::updateGameTransactionStatus($gamerecord, 2, 99);
	            // endif;
				$params = [
		            "code" => 111004,
		            "data" => [],
					"message" => "Insufficient balance",
		        ];
			endif;

	     else:
		    $params = [
	            "code" => 111004,
	            "data" => [],
				"message" => "Insufficient balance",
	        ];
		endif;
		Helper::saveLog('IA Deposit Response', $this->provider_db_id,json_encode($cha), $params);

		// $this->userWager(); // QUERY 1000 pages settle match
		return $params;

	}


	/**
	 * Withdrawal, Deduct Bet/Debit From The Client add as Credit to our system!
	 *
	 */
	public function seamlessWithdrawal(Request $request)
	{

		Helper::saveLog('IA Withrawal', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'IA CALL');
		$data = file_get_contents("php://input");
		$cha = json_decode($this->rehashen($data, true));
		// dd($cha);
		$desc_json = json_decode($cha->desc,JSON_UNESCAPED_SLASHES);
		$transaction_code = $desc_json['code']; // 13,15 refund, 
		$prefixed_username = explode("_", $cha->username);
		$client_details = ProviderHelper::getClientDetails('player_id', $prefixed_username[1]);
		Helper::saveLog('IA seamlessWithdrawal EH', $this->provider_db_id,json_encode($cha), $data);
		// $cha_data = $cha->currencyInfo;
		// $chachi = json_decode($cha_data,JSON_UNESCAPED_SLASHES);
		// return $chachi['short_name'];
		
		$is_project_multiple = explode(',', $cha->projectId);
		// $is_project_multiple = explode(',', 'GAMEVBDDCFBFAK');
		// dd(count($is_project_multiple));
		// dd($cha);
		// dd($client_details);
		if(empty($client_details)):
			$params = [
	            "code" => 111003,
	            "data" => [],
				"message" => "User does not exist",
	        ];	
			return $params;
		endif;	
		// if($this->getOrder($cha->orderId)):
		if($this->findGameExt($cha->orderId, 1, 'transaction_id') != 'false'):
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
		$game_details_info = Helper::findGameDetails('game_code', $this->provider_db_id, $this->game_code);
		$game_details = $game_details_info->game_id; 
		$bet_amount = $cha->money;
		$pay_amount = 0; // Zero Payout
		$method = $transaction_type == 'debit' ? 1 : 2;
		$win_or_lost = 5; // 0 lost,  5 processing // NO MORE WAITING MARK IT AS LOSE XD
		// $win_or_lost = 0; // 0 lost, 
		$payout_reason = $this->getCodeType($desc_json['code']) .' : '.$desc_json['message'];
		$income = $cha->money;	
		$provider_trans_id = $cha->orderId;

		$client_player = ProviderHelper::playerDetailsCall($client_details->player_token);
		if($client_player == 'false'){
			$params = ["code" => 111005,"data" => [],"message" => "withdrawal failed client error"];
			Helper::saveLog('IA seamlessWithdrawal - FATAL ERROR', $this->provider_db_id, json_encode($cha), Helper::datesent());
	        return $params;
		}
		if($client_player->playerdetailsresponse->balance > $cha->money):

	        if($transaction_code == 16 || $transaction_code == 17){ // AUTO CHESS GAME
	        	// IF CALL IS DEBIT AUTO BET IS EXACT AMOUNT AND WIN WILL BE 0
	        	$gamerecord1  = ProviderHelper::createGameTransaction($token_id, $game_details, $bet_amount,  $pay_amount, $method, 0, null, $payout_reason, $income, $provider_trans_id, $cha->projectId);
		 	    $game_transextension1 = ProviderHelper::createGameTransExtV2($gamerecord1,$cha->orderId, $cha->projectId, $cha->money, 1);

		 	    try {
		        	$client_response = ClientRequestHelper::fundTransfer($client_details,$cha->money,$this->game_code,$this->game_name,$game_transextension1,$gamerecord1,$transaction_type);
		        	Helper::saveLog('IA seamlessWithdrawal CRID',  $this->provider_db_id, json_encode($cha), $client_response);
		        } catch (\Exception $e) {
		        	if(isset($gamerecord1)){
		        		ProviderHelper::updateGameTransactionStatus($gamerecord1, 2, 99);
		        	}
		        	$params = ["code" => 111005,"data" => [],"message" => "withdrawal failed client error"];
		        	ProviderHelper::updateGameTransactionStatus($gamerecord1, 2, 99);
	         	    ProviderHelper::updatecreateGameTransExt($game_transextension1, 'FAILED', $params, 'FAILED', $e->getMessage(), false, 'FAILED');
	    	        Helper::saveLog('IA seamlessWithdrawal - FATAL ERROR', $this->provider_db_id, json_encode($cha), Helper::datesent());
	    	        return $params;
		        }

		 	    if(isset($client_response->fundtransferresponse->status->code) 
                  && $client_response->fundtransferresponse->status->code == "200"){
		 	   
		 	    	// AUTO MATIC 0 WIN AMOUNT
		 	    	$game_transextension2 = ProviderHelper::createGameTransExtV2($gamerecord1,$cha->orderId, $cha->projectId, 0, 2);

                  	try {
			        	$client_response2 = ClientRequestHelper::fundTransfer($client_details,0,$this->game_code,$this->game_name,$game_transextension2,$gamerecord1,'credit');
			        	Helper::saveLog('IA seamlessWithdrawal CRID',  $this->provider_db_id, json_encode($cha), $client_response2);
			        } catch (\Exception $e) {
			        	$params = ["code" => 111005,"data" => [],"message" => "withdrawal failed client error"];
		         	    ProviderHelper::updatecreateGameTransExt($game_transextension2, 'FAILED', $params, 'FAILED', $e->getMessage(), false, 'FAILED');
		    	        Helper::saveLog('IA seamlessWithdrawal - FATAL ERROR', $this->provider_db_id, json_encode($cha), Helper::datesent());
		    	        return $params;
			        }

              	 	$params = [
			            "code" => $status_code,
			            "data" => [
			            	"available_balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
			            	"status" => 1,
			            ],
						"message" => "Success",
			        ];	

		 	    	ProviderHelper::updatecreateGameTransExt($game_transextension1, $cha, $params, $client_response->requestoclient, $client_response,$params);

                  	ProviderHelper::updatecreateGameTransExt($game_transextension2, $cha, $params, $client_response2->requestoclient, $client_response2,$params);
                
		 	    }elseif(isset($client_response->fundtransferresponse->status->code) 
                  && $client_response->fundtransferresponse->status->code == "402"){
                  	if(ProviderHelper::checkFundStatus($client_response->fundtransferresponse->status->status)):
		          	   ProviderHelper::updateGameTransactionStatus($gamerecord1, 2, 6);
		            else:
		               ProviderHelper::updateGameTransactionStatus($gamerecord1, 2, 99);
		            endif;
		 	    	 $params = [
			            "code" => 111004,
			            "data" => [],
						"message" => "Insufficient balance",
			        ];

		 	    }else{
		 	    	// ERROR STATUS CODE
		 	    	ProviderHelper::updateGameTransactionStatus($gamerecord1, 2, 99);
		 	    	$params = ["code" => 111005,"data" => [],"message" => "withdrawal failed client error"];
		 	    }
              	Helper::saveLog('IA seamlessWithdrawal - SUCCESS', $this->provider_db_id,json_encode($cha), $params);
		 	    return $params;
	        }else{

        		$gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_details, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $cha->projectId);
        		$game_transextension = ProviderHelper::createGameTransExtV2($gamerecord,$cha->orderId, $cha->projectId, $cha->money, 1);
		 	    	
	        }

	        try {
	        	$client_response = ClientRequestHelper::fundTransfer($client_details,$cha->money,$this->game_code,$this->game_name,$game_transextension,$gamerecord,$transaction_type);
	        	Helper::saveLog('IA seamlessWithdrawal CRID',  $this->provider_db_id, json_encode($cha), $client_response);
	        } catch (\Exception $e) {
	        	$params = ["code" => 111005,"data" => [],"message" => "withdrawal failed client error e"];
	        	if(isset($gamerecord)){
        			ProviderHelper::updateGameTransactionStatus($gamerecord, 2, 99);
     	            ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $params, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
	        	}
    	        Helper::saveLog('IA seamlessWithdrawal - FATAL ERROR', $this->provider_db_id, json_encode($cha), Helper::datesent());
    	        return $params;
	        }

	        if(isset($client_response->fundtransferresponse->status->code) 
               && $client_response->fundtransferresponse->status->code == "200"):
        		$params = [
		            "code" => $status_code,
		            "data" => [
		            	"available_balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
		            	"status" => 1,
		            ],
					"message" => "Success",
		        ];	

		        if(count($is_project_multiple) > 1){
	        		// $gamerecords = array();
	        		$game_transextensions = array();
	        		foreach ($is_project_multiple as $round_project_id) {
	        			// $gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_details, 0,  0, $method, 1, null, $payout_reason, 0, $provider_trans_id, $round_project_id);
	        			// array_push($gamerecords, $gamerecord);
	        			$game_transextension_loop = ProviderHelper::createGameTransExtV2($gamerecord,$cha->orderId, $round_project_id, 0, 1);
	        			array_push($game_transextensions, $game_transextension_loop);
	        		}

	        		foreach ($game_transextensions as $gt_id) {
        				ProviderHelper::updatecreateGameTransExt($gt_id, $cha, $params, $client_response->requestoclient, $client_response,$params);
        			}

        			ProviderHelper::updatecreateGameTransExt($game_transextension, $cha, $params, $client_response->requestoclient, $client_response,$params);

		        }else{
	        	   ProviderHelper::updatecreateGameTransExt($game_transextension, $cha, $params, $client_response->requestoclient, $client_response,$params);
		        }
			elseif(isset($client_response->fundtransferresponse->status->code) 
               && $client_response->fundtransferresponse->status->code == "402"):
				if(ProviderHelper::checkFundStatus($client_response->fundtransferresponse->status->status)):
        			ProviderHelper::updateGameTransactionStatus($gamerecord, 2, 99);
     	            ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $params, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
	            else:
        			ProviderHelper::updateGameTransactionStatus($gamerecord, 2, 99);
     	            ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $params, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
	            endif;
				  $params = [
		            "code" => 111004,
		            "data" => [],
					"message" => "Insufficient balance",
		        ];
		    else:
    			ProviderHelper::updateGameTransactionStatus($gamerecord, 2, 99);
 	            ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $params, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
		    	$params = ["code" => 111005,"data" => [],"message" => "withdrawal failed client error"];
		    	ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $params, 'FAILED', $client_response, 'FAILED', 'FAILED');
			endif;

		else:
		    $params = [
	            "code" => 111004,
	            "data" => [],
				"message" => "Insufficient balance",
	        ];
		endif;
		Helper::saveLog('IA Withrawal Response', $this->provider_db_id,json_encode($cha), $params);
		return $params;
	}

	/**
	 * @return Player Balance
	 *
	 */
	public function seamlessBalance(Request $request)
	{	
		Helper::saveLog('IA Balance', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'IA CALL');
		$data_received = file_get_contents("php://input");
		$cha = json_decode($this->rehashen($data_received, true));
		// dd(gettype($cha));
		Helper::saveLog('IA Balance', $this->provider_db_id, json_encode($cha), 'IA CALL DECODED');
		$prefixed_username = explode("_", $cha->username);
		$client_details = ProviderHelper::getClientDetails('player_id', $prefixed_username[1]);
		$client_response = Providerhelper::playerDetailsCall($client_details->player_token);
		if($client_response == 'false'){
			$params = ["code" => 111003,"data" => [],"message" => "User does not exist"];
		}else{
			$params = [
	            "code" => '200',
	            "data" => [
	            	"available_balance" => ProviderHelper::amountToFloat($client_response->playerdetailsresponse->balance),
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
		// Qg3Tmg1/dfEmjRe/7CaMcLXf1vKseFeXleVuoiWn6efxu72Ab5wKNDocAFL3+Fwm2hvo07BE+p6T3zdbNHEMIK+TP+lqo76t3wlxV6SGXrn4955poVusgarXrQpCWgUb
		// $params = ["orderId" => 'SGVFVUITDSUBBSRCGEJJ'];	
  		// $uhayuu = $this->hashen($params);
 		// dd($uhayuu);
		// Helper::saveLog('IA Search Order', $this->provider_db_id, '', 'CALL RECEIVED');
		// Helper::saveLog('IA Search Order', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'IA CALL');
		$data_received = file_get_contents("php://input");
		$cha = json_decode($this->rehashen($data_received, true));
		// Helper::saveLog('IA Search DECODED', $this->provider_db_id,json_encode($cha), $data_received);
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
			return ["Tiger Games API" => $this->api_version,"date" => Helper::datesent(), "code" => 408, "msg" => "Server is busy/Request too frequently"];
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
				"date" => Helper::datesent(),
				"code" => 200,
				"msg" => "Success"
			];
			return $response_data;
		endif;
		
	}

	public function GG(){
		$params = ["code" => 999,"data" => [],"message" => "CRON JOB"];
		Helper::saveLog('IA SETTLED al:cron triggered', 1223, json_encode('CRONJOB WAS TRIGGERED'), Helper::datesent());
	}


	public function SettleRounds(){
		Helper::saveLog('IA SETTLED al:cron triggered', 1223, json_encode('CRONJOB WAS TRIGGERED'), Helper::datesent());
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
				Helper::saveLog('IA SETTLE ROUND - NO RESPONSE', $this->provider_db_id, json_encode($client_response), 'SETTLE ROUNDS FAILED');
					return;
			}
			$data = json_decode($this->rehashen($client_response[1], true));
			Helper::saveLog('CALL DATA', 1223, json_encode($data), 'ALL ROUNDS');
			if(!isset($data->data->list)){
					Helper::saveLog('IA SETTLE ROUND - NO LIST', $this->provider_db_id, json_encode($data), 'SETTLE ROUNDS FAILED II');
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
				    		$gt_data = ProviderHelper::findGameTransaction($gte_ids->game_trans_id,'game_transaction');
					    	// $existing_game_ext = ProviderHelper::findGameExt($up->round_id, 1, 'round_id');
					    	$client_details = ProviderHelper::getClientDetails('token_id', $gt_data->token_id);

					    	$existing_game_ext = ProviderHelper::findGameExt($gte_ids->round_id, 2, 'round_id');
					    	if($existing_game_ext != 'false'){
					    		$game_transextension = $existing_game_ext->game_trans_ext_id;
					    	}else{
					    		$game_transextension = ProviderHelper::createGameTransExtV2($gte_ids->game_trans_id,$gte_ids->round_id, $gte_ids->round_id, 0, 2);
					    	}
					    	
	            			try {
	            				$client_response = ClientRequestHelper::fundTransfer($client_details,0,$this->game_code,$this->game_name,$game_transextension,$gte_ids->game_trans_id,'credit');
		            			$response = [
		            				"message" => 'This was successfully Updated to lost',
		            				"orderid" => $gte_ids->round_id
		            			];
								ProviderHelper::updatecreateGameTransExt($game_transextension, $response, $response, $client_response->requestoclient, $client_response,$response);

								if($gt_data->win == 5){
									DB::table('game_transactions')
						                ->where('game_trans_id', $gte_ids->game_trans_id)
						                ->update([
						        		  'win' => 0, 
						        		  'transaction_reason' => 'Bet updated'
					    			]);
								}
						    	
	            			} catch (\Exception $e) {
	            				$existing_game_ext = ProviderHelper::findGameExt($gte_ids->round_id, 2, 'round_id');
	            				$response = [
		            				"message" => 'Failed to Updated to lost '.$e->getMessage(),
		            				"orderid" => $up->round_id
		            			];
								ProviderHelper::updatecreateGameTransExt($existing_game_ext->game_trans_ext_id, $response, $response, $response, $response,$response);
	            				continue;
	            			}
					    endforeach;
				    }
				endif;
	 		endif;

	 		Helper::saveLog('IA Search Order SUCCESS', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'SUCCESS');
		} catch (\Exception $e) {
			Helper::saveLog('IA Search Order Failed', $this->provider_db_id, json_encode(file_get_contents("php://input")), $e->getMessage());
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
		Helper::saveLog('IA API WAGER', 2, file_get_contents("php://input"), 'IA API WAGER');
		$data_body = json_decode(file_get_contents("php://input"));
		$roundIds = array();
		$orderIds = '';
		if(!isset($data_body->roundId)){
			Helper::saveLog('IA API WAGER', $this->provider_db_id, file_get_contents("php://input"), 'No Round ID');
			return ["Tiger Games API" => $this->api_version, "date" => Helper::datesent(), "code" => 400, "msg" => "missing parameter"];
		}
		if(count($data_body->roundId) == 0){
			Helper::saveLog('IA API WAGER', $this->provider_db_id, file_get_contents("php://input"), 'No Round ID');
			return ["Tiger Games API" => $this->api_version,"date" => Helper::datesent(),  "code" => 400, "msg" => "round id could not be empty"];
		}
		foreach ($data_body->roundId as $round) {
			// $round_details = ProviderHelper::findGameExt($round, 1, 'game_trans_id');
			$round_details = ProviderHelper::findGameTransaction($round,'game_transaction');
			if($round_details != 'false'){
				$is_project_multiple = explode(',', $round_details->round_id);
				foreach ($is_project_multiple as $key) {
					array_push($roundIds, $key);
				}
				// array_push($roundIds, $round_details->round_id);
			}
		}

		if(count($roundIds) == 0){
			Helper::saveLog('IA API WAGER', $this->provider_db_id, file_get_contents("php://input"), 'No Round ID II');
			return ["Tiger Games API" => $this->api_version,"date" => Helper::datesent(),  "code" => 404, "msg" => "round's not found"];
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
					Helper::saveLog('IA API WAGER - NO RESPONSE', $this->provider_db_id, file_get_contents("php://input"), $data);
					return ["Tiger Games API" => $this->api_version,"date" => Helper::datesent(), "code" => 408, "msg" => "Server is busy."];
				}
				$data = json_decode($this->rehashen($client_response[1], true));
				if(!isset($data->data->list)){
					Helper::saveLog('IA API WAGER - NO LIST', $this->provider_db_id, file_get_contents("php://input"), $data);
					return ["Tiger Games API" => $this->api_version,"date" => Helper::datesent(), "code" => 408, "msg" => "Server is busy.."];
				}
				if(count($data->data->list) == 0){
					Helper::saveLog('IA API WAGER - 0 list', $this->provider_db_id, file_get_contents("php://input"), $data);
					return ["Tiger Games API" => $this->api_version,"date" => Helper::datesent(), "code" => 404, "msg" => $msg];
				}

				$game_wager = array();
				if(isset($data)):
					Helper::saveLog('IA API WAGER - SUCCESS', $this->provider_db_id, file_get_contents("php://input"), $data);
					foreach ($data->data->list as $matches):
						$prefixed_username = explode("_", $matches->username);
						// $client_details = ProviderHelper::getClientDetails('player_id', 98);
						$client_details = ProviderHelper::getClientDetails('player_id', $prefixed_username[1]);
						$round_details = ProviderHelper::findGameExt($matches->order_id, 1, 'round_id');
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
						"date" => Helper::datesent(),
						"code" => 200,
						"msg" => "Success"
					];
				else:
					$response_data = [
						"Tiger Games API" => $this->api_version,
						"date" => Helper::datesent(),
						"code" => 404,
						"msg" => $msg
					];
				endif;
				Helper::saveLog('IA API WAGER - 0 list', $this->provider_db_id, file_get_contents("php://input"), $response_data);
				return $response_data;
			} catch (\Exception $e) {
				Helper::saveLog('IA API WAGER', 2, file_get_contents("php://input"), $e->getMessage());
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

			// 	    	$client_details = ProviderHelper::getClientDetails('token_id', $up->token_id);

			// 	    	$existing_game_ext = ProviderHelper::findGameExt($up->round_id, 2, 'round_id');
			// 	    	if($existing_game_ext != 'false'){
			// 	    		$game_transextension = $existing_game_ext->game_trans_ext_id;
			// 	    	}else{
			// 	    		$game_transextension = ProviderHelper::createGameTransExtV2($up->game_trans_id,$up->round_id, $up->round_id, 0, 2);
			// 	    	}
				    	
   //          			try {
   //          				$client_response = ClientRequestHelper::fundTransfer($client_details,0,$this->game_code,$this->game_name,$game_transextension,$up->game_trans_id,'credit');
	  //           			$response = [
	  //           				"message" => 'This was successfully Updated to lost',
	  //           				"orderid" => $up->round_id
	  //           			];
			// 				ProviderHelper::updatecreateGameTransExt($game_transextension, $response, $response, $client_response->requestoclient, $client_response,$response);

			// 		    	DB::table('game_transactions')
			// 	                ->where('round_id', $up->round_id)
			// 	                ->update([
			// 	        		  'win' => 0, 
			// 	        		  'transaction_reason' => 'Bet updated'
			//     			]);
   //          			} catch (\Exception $e) {
   //          				$existing_game_ext = ProviderHelper::findGameExt($up->round_id, 2, 'round_id');
   //          				$response = [
	  //           				"message" => 'Failed to Updated to lost '.$e->getMessage(),
	  //           				"orderid" => $up->round_id
	  //           			];
			// 				ProviderHelper::updatecreateGameTransExt($existing_game_ext->game_trans_ext_id, $response, $response, $response, $response,$response);
   //          				continue;
   //          			}

			// 	    endforeach;
			// 	endif;
	 	// 	endif;
	 	// 	Helper::saveLog('IA Search Order SUCCESS', $this->provider_db_id, json_encode($data), 'SUCCESS');
		// } catch (\Exception $e) {
		// 	Helper::saveLog('IA Search Order Failed', $this->provider_db_id, json_encode($params), $e->getMessage());
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
                ->where('round_id', $round_id)
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
	public  function findGameExt($provider_transaction_id, $game_transaction_type, $type) {
		$transaction_db = DB::table('game_transaction_ext as gte');
        if ($type == 'transaction_id') {
			$transaction_db->where([
		 		["gte.provider_trans_id", "=", $provider_transaction_id],
		 		["gte.game_transaction_type", "=", $game_transaction_type],
		 		["gte.transaction_detail", "!=", '"FAILED"'],
		 	]);
		}
		if ($type == 'round_id') {
			$transaction_db->where([
		 		["gte.round_id", "=", $provider_transaction_id],
		 		["gte.game_transaction_type", "=", $game_transaction_type],
		 		["gte.transaction_detail", "!=", '"FAILED"'],
		 	]);
		}  
		$result= $transaction_db->first();
		return $result ? $result : 'false';
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
	// public function _getClientDetails($type = "", $value = "", $client_id="") 
	// {
	// 	$query = DB::table("clients AS c")
	// 			 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.client_player_id','p.language', 'p.currency', 'pst.token_id', 'pst.player_token' , 'c.client_url', 'c.default_currency', 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
	// 			 ->leftJoin("players AS p", "c.client_id", "=", "p.client_id")
	// 			 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
	// 			 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
	// 			 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id");
	// 				if ($type == 'token') {
	// 					$query->where([
	// 				 		["pst.player_token", "=", $value],
	// 				 		// ["pst.status_id", "=", 1]
	// 				 	]);
	// 				}
	// 				if ($type == 'player_id') {
	// 					$query->where([
	// 				 		["p.player_id", "=", $value],
	// 				 		// ["pst.status_id", "=", 1]
	// 				 	]);
	// 				}
	// 				if ($type == 'site_url') {
	// 					$query->where([
	// 				 		["c.client_url", "=", $value],
	// 				 	]);
	// 				}
	// 				if ($type == 'username') {
	// 					$query->where([
	// 				 		["p.username", $value],
	// 				 	]);
	// 				}
	// 				if ($type == 'username_and_cid') {
	// 					$query->where([
	// 				 		["p.username", $value],
	// 				 		["p.client_id", $client_id],
	// 				 	]);
	// 				}
	// 				$result= $query
	// 				 			->latest('token_id')
	// 				 			->first();

	// 		return $result;
	// }



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


}
