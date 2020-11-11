<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use App\Helpers\ClientRequestHelper;
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
    // public function signatureCheck($details, $signature_type){
    // 	if($signature_type == 1){
    // 		$signature = md5($this->merchant_id.$details->currentTime.$details->accountId.$details->currency.base64_encode($this->merchant_key));
    // 	}

    // 	if($signature == $details->sign){
    // 		return true;
    // 	}else{
    // 		return false;
    // 	}
    // }

	/**
	 * SINGLE WALLET
	 * @author's note : Player Single Balance 
	 *
	 */
	public function singleBalance(Request $request){
		$data = file_get_contents("php://input");
		$details = json_decode($data);
		AWSHelper::saveLog('AWS singleBalance - HIT 1', $this->provider_db_id, $data, $details);
		$prefixed_username = explode("_TG", $details->accountId);
		$client_details = AWSHelper::getClientDetails('player_id', $prefixed_username[1]);
		if($client_details == 'false'){
		   $response = [
				"msg"=> "Player Not Found - Client Failed To Respond",
				"code"=> 100,
			];
			AWSHelper::saveLog('AWS singleBalance - Hit 2', $this->provider_db_id, $data, $response);
			return $response;
		}

		if(!AWSHelper::findMerchantIdByClientId($client_details->client_id)){
            $response = [
				"msg"=> "Player Not Found - Client Failed To Respond",
				"code"=> 100,
			];
			AWSHelper::saveLog('AWS singleBalance - Client ID NOT FOUND', $this->provider_db_id, $data, $response);
			return $response;
        }

        $merchant_id = AWSHelper::findMerchantIdByClientId($client_details->client_id)['merchant_id'];
		$merchant_key = AWSHelper::findMerchantIdByClientId($client_details->client_id)['merchant_key'];

		$signature = md5($merchant_id.$details->currentTime.$details->accountId.$details->currency.base64_encode($merchant_key));
		
		if($signature != $details->sign){
			$response = [
				"msg"=> "Sign check encountered error, please verify sign is correct",
				"code"=> 9200
			];
			AWSHelper::saveLog('AWS Single Error Sign', $this->provider_db_id, $data, $response);
			return $response;
		}
	
		$provider_reg_currency = Providerhelper::getProviderCurrency($this->provider_db_id, $client_details->default_currency);
		if($provider_reg_currency == 'false'){
			$response = [
				"msg"=> "Currency not found",
				"code"=> 102
			];
			AWSHelper::saveLog('AWS Single Currency Not Found', $this->provider_db_id, $data, $response);
			return $response;
		}

		// $player_details = AWSHelper::playerDetailsCall($client_details->player_token);
		$player_details = AWSHelper::playerDetailsCall($client_details);
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
		AWSHelper::saveLog('AWS singleBalance - SUCCESS', $this->provider_db_id, $data, $response);
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
		AWSHelper::saveLog('AWS singleFundTransfer - HIT 1', $this->provider_db_id, file_get_contents("php://input"), Helper::datesent());
		$prefixed_username = explode("_TG", $details->accountId);
		$client_details = AWSHelper::getClientDetails('player_id', $prefixed_username[1]);
		if($client_details == 'false'){
		   $response = [
				"msg"=> "Player Not Found - Client Failed To Respond",
				"code"=> 100,
			];
			AWSHelper::saveLog('AWS singleFundTransfer - client_details not found', $this->provider_db_id, $data, $response);
			return $response;
		}

		// # 01 COMMENT THIS OUT WHEN DEBUGGING IN LOCAL
		$explode1 = explode('"betAmount":', $data);
		$explode2 = explode('amount":', $explode1[0]);
		$amount_in_string = trim(str_replace(',', '', $explode2[1]));
		$amount_in_string = trim(str_replace('"', '', $amount_in_string));

		$merchant_id = AWSHelper::findMerchantIdByClientId($client_details->client_id)['merchant_id'];
		$merchant_key = AWSHelper::findMerchantIdByClientId($client_details->client_id)['merchant_key'];

		$signature = md5($merchant_id.$details->currentTime.$amount_in_string.$details->accountId.$details->currency.$details->txnId.$details->txnTypeId.$details->gameId.base64_encode($merchant_key));

		if($signature != $details->sign){
			$response = [
				"msg"=> "Sign check encountered error, please verify sign is correct",
				"code"=> 9200
			];
			AWSHelper::saveLog('AWS Single Error Sign', $this->provider_db_id, $data, $response);
			return $response;
		}
		// # 01 END

		AWSHelper::saveLog('AWS singleFundTransfer - HIT 2 Sign Passed', $this->provider_db_id, $data, Helper::datesent());

		$provider_reg_currency = Providerhelper::getProviderCurrency($this->provider_db_id, $client_details->default_currency);
		if($provider_reg_currency == 'false'){
			$response = [
				"msg"=> "Currency not found",
				"code"=> 102
			];
			AWSHelper::saveLog('AWS singleFundTransfer - Currency Not Found', $this->provider_db_id, $data, $response);
			return $response;
		}

		AWSHelper::saveLog('AWS singleFundTransfer - D1 PlayerDetails', $this->provider_db_id, $data, Helper::datesent());
		$player_details = AWSHelper::playerDetailsCall($client_details);
		// $player_details = AWSHelper::playerDetailsCall($client_details->player_token);
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $details->gameId);
		if($game_details == null){
			$response = [
			"msg"=> "Game not found",
			"code"=> 1100
			];
			AWSHelper::saveLog('AWS singleFundTransfer - Game Not Found', $this->provider_db_id, $data, $response);
			return $response;
		}

		$transaction_type = $details->winAmount > 0 ? 'credit' : 'debit';
		// $transaction_type = $details->amount > 0 ? 'credit' : 'debit';
		// return $transaction_type;
		$game_transaction_type = $transaction_type == 'debit' ? 1 : 2; // 1 Bet, 2 Win

		$game_code = $game_details->game_id;
		$token_id = $client_details->token_id;
		$bet_amount = abs($details->betAmount);


		if($transaction_type == 'credit'){
			$method = 2;
			$pay_amount =  $details->winAmount;
			$win_type = 1;
			$income = $bet_amount-$pay_amount;
			// $pay_amount =  abs($details->amount);
			// $win_type = $income > 0 ? 1 : 0;
			// $win_type = $income > 0 ? 0 : 1;
		}else{
			$method = 1;
			$pay_amount = $details->winAmount; // payamount zero
			$income =$bet_amount-$pay_amount;
			$win_type = 0;
		}

		
		$win_or_lost = $win_type; // 0 lost,  5 processing
		$payout_reason = AWSHelper::getOperationType($details->txnTypeId);
		$provider_trans_id = $details->txnId;

		AWSHelper::saveLog('AWS singleFundTransfer - D1 findGameExt', $this->provider_db_id, $data, Helper::datesent());
		$game_ext_check = AWSHelper::findGameExt($details->txnId, $game_transaction_type, 'transaction_id');
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
			AWSHelper::saveLog('AWS singleFundTransfer - Order Already Exist', $this->provider_db_id, $data, $response);
			return $response;
		}
		if($transaction_type == 'debit'){
			if($bet_amount > $player_details->playerdetailsresponse->balance){
				$response = [
					"msg"=> "Insufficient balance",
					"code"=> 1201
				];
				AWSHelper::saveLog('AWS singleFundTransfer - Insufficient Balance', $this->provider_db_id, $data, $response);
				return $response;
			}
		}

		try {
			AWSHelper::saveLog('AWS singleFundTransfer - D1 createGameTransaction', $this->provider_db_id, $data, Helper::datesent());
			$gamerecord  = AWSHelper::createGameTransaction($token_id, $game_code, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $provider_trans_id);
			// AWS IS 1 WAY FLIGHT
			
			$bet_amount_2way = abs($details->betAmount);
			$win_amount_2way = abs($details->winAmount);
			AWSHelper::saveLog('AWS singleFundTransfer - D1 createGameTransExtV2', $this->provider_db_id, $data, Helper::datesent());
		    $game_transextension1 = ProviderHelper::createGameTransExtV2($gamerecord,$provider_trans_id, $provider_trans_id, $bet_amount_2way, 1);

           try {
           	AWSHelper::saveLog('AWS singleFundTransfer - D1 fundTransfer request', $this->provider_db_id, $data, Helper::datesent());
           	 $client_response = ClientRequestHelper::fundTransfer($client_details,abs($bet_amount_2way),$game_details->game_code,$game_details->game_name,$game_transextension1,$gamerecord,'debit');
       	 	 AWSHelper::saveLog('AWS singleFundTransfer - D1 fundTransfer responded', $this->provider_db_id, $data, Helper::datesent());
           	 AWSHelper::saveLog('AWS CR ID = '.$provider_trans_id, $this->provider_db_id, $data, $client_response);
           } catch (\Exception $e) {
           	    $response = ["msg"=> "Fund transfer encountered error","code"=> 2205,"data"=> []];
            	if(isset($gamerecord)){
	        		ProviderHelper::updateGameTransactionStatus($gamerecord, 2, 99);
            	    ProviderHelper::updatecreateGameTransExt($game_transextension1, 'FAILED', $response, 'FAILED', $e->getMessage(), false, 'FAILED');
	        	}
            	AWSHelper::saveLog('AWS singleFundTransfer - FATAL ERROR', $this->provider_db_id, json_encode($response), Helper::datesent());
            	return $response;
           }

            if(isset($client_response->fundtransferresponse->status->code) 
             && $client_response->fundtransferresponse->status->code == "200"){
            	AWSHelper::saveLog('AWS singleFundTransfer - C1 createGameTransExtV2', $this->provider_db_id, $data, Helper::datesent());
            	$game_transextension2 = ProviderHelper::createGameTransExtV2($gamerecord,$provider_trans_id, $provider_trans_id, $win_amount_2way, 2);

            	try {
            		AWSHelper::saveLog('AWS singleFundTransfer - C1 fundTransfer Request', $this->provider_db_id, $data, Helper::datesent());
            		$client_response2 = ClientRequestHelper::fundTransfer($client_details,abs($win_amount_2way),$game_details->game_code,$game_details->game_name,$game_transextension2,$gamerecord,'credit');
            		AWSHelper::saveLog('AWS singleFundTransfer - C1 fundTransfer Responded', $this->provider_db_id, $data, Helper::datesent());
            	} catch (\Exception $e) {
            		$response = ["msg"=> "Fund transfer encountered error","code"=> 2205,"data"=> []];
            		ProviderHelper::updatecreateGameTransExt($game_transextension2, 'FAILED', $response, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
            		AWSHelper::saveLog('AWS singleFundTransfer - FATAL ERROR', $this->provider_db_id, json_encode($response), Helper::datesent());
            		return $response;
            	}


            	if(isset($client_response2->fundtransferresponse->status->code) 
            	 && $client_response2->fundtransferresponse->status->code == "200"){
            		$response = [
						"msg"=> "success",
						"code"=> 0,
						"data"=> [
							"currency"=> $client_details->default_currency,
							"amount"=> (double)$details->amount,
							"accountId"=> $details->accountId,
							"txnId"=> $details->txnId,
							"eventTime"=> date('Y-m-d H:i:s'),
							"balance" => floatval(number_format((float)$client_response2->fundtransferresponse->balance, 2, '.', '')),
							"bonusBalance" => 0
						]
					];
					AWSHelper::saveLog('AWS singleFundTransfer - C1 updatecreateGameTransExt', $this->provider_db_id, $data, Helper::datesent());
					ProviderHelper::updatecreateGameTransExt($game_transextension1, $details, $response, $client_response->requestoclient, $client_response,$response);

					ProviderHelper::updatecreateGameTransExt($game_transextension2, $details, $response, $client_response2->requestoclient, $client_response,$response);
					AWSHelper::saveLog('AWS singleFundTransfer - C1 updatecreateGameTransExt UPDATED', $this->provider_db_id, $data, Helper::datesent());
            	}elseif(isset($client_response2->fundtransferresponse->status->code) 
           		 && $client_response2->fundtransferresponse->status->code == "402"){
            		if(ProviderHelper::checkFundStatus($client_response->fundtransferresponse->status->status)):
		          	   ProviderHelper::updateGameTransactionStatus($gamerecord, 2, 6);
		            else:
		               ProviderHelper::updateGameTransactionStatus($gamerecord, 2, 99);
		            endif;
		            $response = [
						"msg"=> "Insufficient balance",
						"code"=> 1201
					];
            	}

			}elseif(isset($client_response->fundtransferresponse->status->code) 
            && $client_response->fundtransferresponse->status->code == "402"){
            	if(ProviderHelper::checkFundStatus($client_response->fundtransferresponse->status->status)):
	          	   ProviderHelper::updateGameTransactionStatus($gamerecord, 2, 6);
	            else:
	               ProviderHelper::updateGameTransactionStatus($gamerecord, 2, 99);
	            endif;
	            // $response = ["msg"=> "Fund transfer encountered error","code"=> 2205,"data"=> []];
				$response = [
					"msg"=> "Insufficient balance",
					"code"=> 1201
				];
			}
			AWSHelper::saveLog('AWS singleFundTransfer - C1 BETWIN Processed', $this->provider_db_id, $data, Helper::datesent());
			AWSHelper::saveLog('AWS singleFundTransfer SUCCESS = '.$gamerecord, $this->provider_db_id, $data, $response);
			return $response;
			
		} catch (\Exception $e) {
			$response = ["msg"=> "Fund transfer encountered error","code"=> 2205];
			AWSHelper::saveLog('AWS singleFundTransfer - FATAL ERROR', $this->provider_db_id, $data, $response);
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
		AWSHelper::saveLog('AWS singleFundQuery EH', $this->provider_db_id, $data, Helper::datesent());

		$explode1 = explode('"betAmount":', $data);
		$explode2 = explode('amount":', $explode1[0]);
		$amount_in_string = trim(str_replace(',', '', $explode2[1]));
		$amount_in_string = trim(str_replace('"', '', $amount_in_string));
		
		if($signature != $details->sign){
			$response = [
				"msg"=> "Sign check encountered error, please verify sign is correct",
				"code"=> 9200
			];
			AWSHelper::saveLog('AWS singleFundQuery - Error Sign', $this->provider_db_id, $data, $response);
			return $response;
		}

		$prefixed_username = explode("_TG", $details->accountId);
		$client_details = AWSHelper::getClientDetails('player_id', $prefixed_username[1]);
		$player_details = AWSHelper::playerDetailsCall($client_details);
		// $player_details = AWSHelper::playerDetailsCall($client_details->player_token);

		if(!AWSHelper::findMerchantIdByClientId($client_details->client_id)){
            $response = [
				"msg"=> "Player Not Found - Client Failed To Respond",
				"code"=> 100,
			];
			AWSHelper::saveLog('AWS singleFundQuery - Error Sign', $this->provider_db_id, $data, $response);
			return $response;
        }

		$merchant_id = AWSHelper::findMerchantIdByClientId($client_details->client_id)['merchant_id'];
		$merchant_key = AWSHelper::findMerchantIdByClientId($client_details->client_id)['merchant_key'];

		$signature = md5($merchant_id.$details->currentTime.$amount_in_string.$details->accountId.$details->currency.$details->txnId.$details->txnTypeId.$details->gameId.base64_encode($merchant_key));

		if($player_details == 'false'){
			$response = [
				"msg"=> "Fund transfer encountered error",
				"code"=> 2205
			];
			AWSHelper::saveLog('AWS singleFundQuery - Error Sign', $this->provider_db_id, $data, $response);
			return $response;
		}

		$transaction_type = $details->amount > 0 ? 'credit' : 'debit';
		$game_transaction_type = $transaction_type == 'debit' ? 1 : 2; // 1 Bet, 2 Win
		$game_ext_check = AWSHelper::findGameExt($details->txnId, $game_transaction_type, 'transaction_id');
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
		AWSHelper::saveLog('AWS singleFundQuery - SUCCESS', $this->provider_db_id, $data, $response);
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
	// 	$client_details = AWSHelper::getClientDetails('token', $request->token);
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
	//     AWSHelper::saveLog('AWS BO Launch Game', 21, json_encode($requesttosend), $provider_response);
	//     return isset($provider_response->data->gameUrl) ? $provider_response->data->gameUrl : 'false';
	// }


	/**
	 * MERCHANT BACKOFFICE
	 * @author's NOTE : Get All Game List (NOT/USED) ONE TIME USAGE ONLY
	 *
	 */
	// public function gameList(Request $request){
	// 	$client = new Client([
	// 	    'headers' => [ 
	// 	    	'Content-Type' => 'application/json',
	// 	    ]
	// 	]);
	// 	$requesttosend = [
	// 		"merchantId" => $this->merchant_id,
	// 		"currentTime" => AWSHelper::currentTimeMS(),
	// 		"language" => 'en_US'
	// 	];
	// 	$requesttosend['sign'] = AWSHelper::hashen(AWSHelper::currentTimeMS(),$this->merchant_id);
	// 	$guzzle_response = $client->post($this->api_url.'/game/list',
	// 	    ['body' => json_encode($requesttosend)]
	// 	);
	//     $client_response = json_decode($guzzle_response->getBody()->getContents());
	//     return json_encode($client_response);
	// }


	/**
	 * MERCHANT BACKOFFICE (NOT/USED)
	 * @author's NOTE : This is only if we want to disable/enable a player on this provider 
	 * @param   $[request->status] [<enable or disable>]
	 *
	 */
	public function playerManage(Request $request){
		AWSHelper::saveLog('AWS BO Player Manage', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$status = $request->has('status') ? $request->status : 'enable';
		$client_details = AWSHelper::getClientDetails('token', $request->token);
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
		AWSHelper::saveLog('AWS BO Player Status', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$status = $request->has('status') ? $request->status : 'enable';
		$client_details = AWSHelper::getClientDetails('token', $request->token);
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
		$client_details = AWSHelper::getClientDetails('token', $request->token);
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
		AWSHelper::saveLog('AWS BO Fund Transfer', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$client_details = AWSHelper::getClientDetails('token', $request->token);
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
		AWSHelper::saveLog('AWS BO Query Status', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$client_details = AWSHelper::getClientDetails('token', $request->token);
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
		AWSHelper::saveLog('AWS BO Query Order', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$client_details = AWSHelper::getClientDetails('token', $request->token);
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
		AWSHelper::saveLog('AWS BO Player Logout', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$client_details = AWSHelper::getClientDetails('token', $request->token);
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
	
	
}
