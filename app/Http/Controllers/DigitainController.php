<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\GameRound;
use App\Helpers\GameTransaction;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use App\Helpers\ClientRequestHelper;
use App\Helpers\CallParameters;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;

use DB;

/**
 *  UPDATED 06-27-20
 *	Api Documentation v3 -> v3.7.0-1
 *	Current State : v3 updating to v3.7.0-1 
 *  @author's NOTE: You cannot win if you dont bet! Bet comes first fellows!
 *	@author's NOTE: roundId is intentionally PREFIXED with RSG to separate from other roundid, safety first!
 *	@method refund method additionals = requests: holdEarlyRefund
 *	@method win method additionals = requests:  returnBetsAmount, bonusTicketId
 *	@method bet method additionals = requests:  checkRefunded, bonusTicketId
 *	@method betwin method additionals = requests:  bonusTicketId,   ,response: playerId, roundId, currencyId
 *	
 */
class DigitainController extends Controller
{
    private $digitain_key = "BetRNK3184223";
    private $operator_id = 'B9EC7C0A';
    private $provider_db_id = 14;
    private $provider_and_sub_name = 'Digitain'; // nothing todo with the provider


    /**
	 *	Verify Signature
	 *	@return  [Bolean = True/False]
	 *
	 */
	public function authMethod($operatorId, $timestamp, $signature){
		$digitain_key = $this->digitain_key;
	    $operator_id = $operatorId;
	    $time_stamp = $timestamp;
	    $message = $time_stamp.$operator_id;
	    $hmac = hash_hmac("sha256", $message, $digitain_key);
		$result = false;
            if($hmac == $signature) {
			    $result = true;
            }
        return $result;
	}

	public function formatBalance($balance){
		// return formatBalance($balance);
		return floatval(number_format((float)$balance, 2, '.', ''));
	}

	/**
	 *	Create Signature
	 *	@return  [String]
	 *
	 */
	public function createSignature($timestamp){
	    $digitain_key = $this->digitain_key;
	    $operator_id = $this->operator_id;
	    $time_stamp = $timestamp;
	    $message = $time_stamp.$operator_id;
	    $hmac = hash_hmac("sha256", $message, $digitain_key);
	    return $hmac;
	}

	public function noBody(){
		return $response = [
			"timestamp" => date('YmdHisms'),
			"signature" => $this->createSignature(date('YmdHisms')),
			"errorCode" => 17 //RequestParameterMissing
		];
	}

	public function authError(){
		return $response = ["timestamp" => date('YmdHisms'),"signature" => $this->createSignature(date('YmdHisms')),"errorCode" => 12];
	}
	
	public function wrongOperatorID(){
		return $response = ["timestamp" => date('YmdHisms'),"signature" => $this->createSignature(date('YmdHisms')),"errorCode" => 15];
	}

	public function array_has_dupes($array) {
	   return count($array) !== count(array_unique($array));
	}

	/**
	 * Player Detail Request
	 * @return array [Client Player Data]
	 * 
	 */
    public function authenticate(Request $request)
    {	
		$json_data = json_decode(file_get_contents("php://input"), true);
		Helper::saveLog('RSG authenticate - EH', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		if($json_data == null){
			return $this->noBody();
		}
		if($json_data['operatorId'] != $this->operator_id){
			return $this->wrongOperatorID();
		}
		if (!$this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])){
			return $this->authError();
		}
		$client_details = ProviderHelper::getClientDetails('token', $json_data["token"]);	
		if ($client_details == null){
			$response = [
				"timestamp" => date('YmdHisms'),
				"signature" => $this->createSignature(date('YmdHisms')),
				// "token" => $json_data['token'],
				"errorCode" => 2 // SessionNotFound
			];
			Helper::saveLog('RSG authenticate', $this->provider_db_id, file_get_contents("php://input"), $response);
			return $response;
		}
		$token_check = Helper::tokenCheck($json_data["token"]);
		if($token_check != true){
			$response = [
				"timestamp" => date('YmdHisms'),
				"signature" => $this->createSignature(date('YmdHisms')),
				"errorCode" => 3 // SessionExpired!
			];
			Helper::saveLog('RSG authenticate', $this->provider_db_id, file_get_contents("php://input"), $response);
			return $response;
		}
		$client_response = ProviderHelper::playerDetailsCall($json_data["token"]);
		if($client_response == 'false'){
			$response = [
				"timestamp" => date('YmdHisms'),
				"signature" => $this->createSignature(date('YmdHisms')),
				"errorCode" => 999, // client cannot be reached! http errors etc!
			];
			Helper::saveLog('RSG authenticate', $this->provider_db_id, file_get_contents("php://input"), $response);
			return $response;
		}
		if(isset($client_response->playerdetailsresponse->status->code) &&
			     $client_response->playerdetailsresponse->status->code == "200"){

			$dob = isset($client_response->playerdetailsresponse->birthday) ? $client_response->playerdetailsresponse->birthday : '1996-03-01 00:00:00.000';
			$gender_pref = isset($client_response->playerdetailsresponse->gender) ? strtolower($client_response->playerdetailsresponse->gender) : 'male';
			$gender = ['male' => 1,'female' => 2];

			$response = [
				"timestamp" => date('YmdHisms'),
				"signature" => $this->createSignature(date('YmdHisms')),
				"errorCode" => 1,
				"playerId" => $client_details->player_id, // Player ID Here is Player ID in The MW DB, not the client!
				"userName" => $client_response->playerdetailsresponse->accountname,
				// "currencyId" => $client_response->playerdetailsresponse->currencycode,
				"currencyId" => $client_details->default_currency,
				"balance" => $this->formatBalance($client_response->playerdetailsresponse->balance),
				"birthDate" => $dob, // Optional
				"firstName" => $client_response->playerdetailsresponse->firstname, // required
				"lastName" => $client_response->playerdetailsresponse->lastname, // required
				"gender" => $gender[$gender_pref], // Optional
				"email" => $client_response->playerdetailsresponse->email,
				"isReal" => true
			];
		}
		Helper::saveLog('RSG authenticate - SUCCESS', $this->provider_db_id, file_get_contents("php://input"), $response);
		return $response;
	}

	/**
	 * Get the player balance
	 * @author's NOTE [Error codes, 12 = Invalid Signature, 16 = invalid currency type, 999 = general error (HTTP)]
	 * @return  [<json>]
	 * 
	 */
	public function getBalance()
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		Helper::saveLog('RSG getBalance - EH', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		if($json_data == null){
			return $this->noBody();
		}
		if($json_data['operatorId'] != $this->operator_id){ //Wrong Operator Id 
			return $this->wrongOperatorID();
		}
		if(!$this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])){
			return $this->authError();
		}
		$client_details = ProviderHelper::getClientDetails('token', $json_data["token"]);	
		if ($client_details == null){
			$response = [
				"timestamp" => date('YmdHisms'),
				"signature" => $this->createSignature(date('YmdHisms')),
				"errorCode" => 2 // SessionNotFound
			];
			Helper::saveLog('RSG getBalance', $this->provider_db_id, file_get_contents("php://input"), $response);
			return $response;
		}
		$token_check = Helper::tokenCheck($json_data["token"]);
		if($token_check != true){
			$response = [
				"timestamp" => date('YmdHisms'),
				"signature" => $this->createSignature(date('YmdHisms')),
				"errorCode" => 3 // Token is expired!
			];
			Helper::saveLog('RSG getBalance', $this->provider_db_id, file_get_contents("php://input"), $response);
			return $response;
		}
		$client_response = ProviderHelper::playerDetailsCall($json_data["token"]);
		if($client_response == 'false'){
			$response = [
				"timestamp" => date('YmdHisms'),
				"signature" => $this->createSignature(date('YmdHisms')),
				"errorCode" => 999, // client cannot be reached! http errors etc!
			];
			return $response;
		}
		if($client_details->player_id != $json_data["playerId"]){
			$response = [
				"timestamp" => date('YmdHisms'),
				"signature" => $this->createSignature(date('YmdHisms')),
				"errorCode" => 4, // client cannot be reached! http errors etc!
			];
			return $response;
		}
		if($json_data["currencyId"] == $client_details->default_currency):
			$response = [
				"timestamp" => date('YmdHisms'),
				"signature" => $this->createSignature(date('YmdHisms')),
				"errorCode" => 1,
				"balance" => $this->formatBalance($client_response->playerdetailsresponse->balance),	
			];
		else:
			$response = [
				"timestamp" => date('YmdHisms'),
				"signature" => $this->createSignature(date('YmdHisms')),
				"token" => $json_data['token'],
				"errorCode" => 16, // Error Currency type
			];
		endif;
		return $response;
	}


	/**
	 * Call if Digitain wants a new token!
	 * @author's NOTE [Error codes, 12 = Invalid Signature, 999 = general error (HTTP)]
	 * @return  [<json>]
	 * 
	 */
	public function refreshtoken(){
		Helper::saveLog('RSG refreshtoken - EH', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$json_data = json_decode(file_get_contents("php://input"), true);
		if($json_data == null){ //Wrong Operator Id 
			return $this->noBody();
		}
		if($json_data['operatorId'] != $this->operator_id){ //Wrong Operator Id 
			return $this->wrongOperatorID();
		}
		if(!$this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])){ 
			return $this->authError();
		}
		$client_details = ProviderHelper::getClientDetails('token', $json_data["token"]);	
		if ($client_details == null){ // SessionNotFound
			$response = ["timestamp" => date('YmdHisms'),"signature" => $this->createSignature(date('YmdHisms')),"errorCode" => 2];
			Helper::saveLog('RSG refreshtoken', $this->provider_db_id, file_get_contents("php://input"), $response);
			return $response;
		}
		$token_check = Helper::tokenCheck($json_data["token"]);
		if($token_check != true){ // SessionExpired!
			$response = ["timestamp" => date('YmdHisms'),"signature" => $this->createSignature(date('YmdHisms')),"errorCode" => 3];
			Helper::saveLog('RSG refreshtoken', $this->provider_db_id, file_get_contents("php://input"), $response);
			return $response;
		}
		if($json_data['changeToken']): // IF TRUE REQUEST ADD NEW TOKEN
			$client_response = ProviderHelper::playerDetailsCall($json_data["token"], true);
			if($client_response):
				$game_details = Helper::getInfoPlayerGameRound($json_data["token"]);
				Helper::savePLayerGameRound($game_details->game_code, $client_response->playerdetailsresponse->refreshtoken, $this->provider_and_sub_name);

				DB::table('player_session_tokens')->insert(
	                        array('player_id' => $client_details->player_id, 
	                        	  'player_token' =>  $client_response->playerdetailsresponse->refreshtoken, 
	                        	  'status_id' => '1')
	            );
				$response = [
					"timestamp" => date('YmdHisms'),
					"signature" => $this->createSignature(date('YmdHisms')),
					"token" => $client_response->playerdetailsresponse->refreshtoken, // Return New Token!
					"errorCode" => 1
				];
			else:
				$response = [
					"timestamp" => date('YmdHisms'),
					"signature" => $this->createSignature(date('YmdHisms')),
					// "token" => $json_data['token'],
					"errorCode" => 999,
				];
			endif;
	 	else:
	 		$response = [
				"timestamp" => date('YmdHisms'),
				"signature" => $this->createSignature(date('YmdHisms')),
				"token" => $json_data['token'], // Return OLD Token
				"errorCode" => 1
			];
 		endif;
 		Helper::saveLog('RSG refreshtoken', $this->provider_db_id, file_get_contents("php://input"), $response);
 		return $response;

	}

	/**
	 * @author's NOTE:
	 * allOrNone - When True, if any of the items fail, the Partner should reject all items NO LOGIC YET!
	 * checkRefunded - no logic yet
	 * ignoreExpiry - no logic yet, expiry should be handle in the refreshToken call
	 * changeBalance - no yet implemented always true (RSG SIDE)
	 * UPDATE 4 filters - Player Low Balance, Currency code dont match, already exist, The playerId was not found
	 * @author's NOTE [Error codes, 12 = Invalid Signature, 6 = Player Low Balance!, 16 = Currency code dont match, 999 = general error (HTTP), 8 = already exist, 4 = The playerId was not found]
	 * 
	 */
	 public function bet(Request $request){
	 	Helper::saveLog('RSG bet - EH', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$json_data = json_decode(file_get_contents("php://input"), true);
		$global_error = 1;

		if($json_data == null){
			return $this->noBody();
		}
		if($json_data['operatorId'] != $this->operator_id){ //Wrong Operator Id 
			return $this->wrongOperatorID();
		}
		if(!$this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])){ 
			return $this->authError();
		}
		

		$items_allOrNone = array(); // ITEMS TO ROLLBACK IF ONE OF THE ITEMS FAILED!
		$items_array = array(); // ITEMS INFO
		$all_bets_amount = array();
		$duplicate_txid_request = array();

		$global_error = 1;
		$error_encounter = 0;
		# All or none is true

		# Missing Parameters
		if(!isset($json_data['providerId']) || !isset($json_data['allOrNone']) || !isset($json_data['signature']) || !isset($json_data['timestamp']) || !isset($json_data['operatorId']) || !isset($json_data['items'])){
			$response = array(
					 "timestamp" => date('YmdHisms'),
				     "signature" => $this->createSignature(date('YmdHisms')),
					 "errorCode" => 17,
					 "items" => $items_array,
   			);	
			return $response;
		}

		$total_bets = array_sum($all_bets_amount);
		$isset_allbets_amount = 0;
		foreach ($json_data['items'] as $key) { // FOREACH CHECK
		 		if($json_data['allOrNone'] == 'true'){ // IF ANY ITEM FAILED DONT PROCESS IT
		 			# Missing Parameters
					if(!isset($key['info']) || !isset($key['txId']) || !isset($key['betAmount']) || !isset($key['token']) || !isset($key['playerId']) || !isset($key['roundId']) || !isset($key['gameId'])){
						$items_array[] = [
							 "info" => isset($key['info']) ? $key['info'] : '', // Info from RSG, MW Should Return it back!
							 "errorCode" => 17, // transaction already refunded
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
		        	    ]; 
						$global_error = 17;
						$error_encounter = 1;
						continue;
					}
					if($isset_allbets_amount == 0){ # Calculate all total bets
						foreach ($json_data['items'] as $key) {
							array_push($all_bets_amount, $key['betAmount']);
							array_push($duplicate_txid_request, $key['txId']);  // Checking for same txId in the call
						}
						$isset_allbets_amount = 1;
					}
		 			$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $key["gameId"]);
					if($game_details == null){ // Game not found
						$items_array[] = [
							 "info" => isset($key['info']) ? $key['info'] : '', // Info from RSG, MW Should Return it back!
							 "errorCode" => 11, // transaction already refunded
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
		        	    ];
						$global_error = 11;
						$error_encounter= 1;
						continue;
					}
					$client_details = ProviderHelper::getClientDetails('token', $key["token"]);	
					if ($client_details == null){ // SessionNotFound
						$items_array[] = [
							 "info" => isset($key['info']) ? $key['info'] : '', // Info from RSG, MW Should Return it back!
							 "errorCode" => 2, // transaction already refunded
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
		        	    ];
						$global_error = 2;
						$error_encounter= 1;
						continue;
					}
					if($client_details != null){ // Wrong Player ID
						if($client_details->player_id != $key["playerId"]){
							$items_array[] = [
								 "info" => isset($key['info']) ? $key['info'] : '', // Info from RSG, MW Should Return it back!
								 "errorCode" => 4, // transaction already refunded
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
			        	    ];
							$global_error = 4;
							$error_encounter= 1;
							continue;
						}
						if($key['currencyId'] != $client_details->default_currency){
							$items_array[] = [
								 "info" => isset($key['info']) ? $key['info'] : '', // Info from RSG, MW Should Return it back!
								 "errorCode" => 16, // transaction already refunded
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
			        	    ];
							$global_error = 16;
							$error_encounter= 1; 
							continue;
						}
						$client_player = ProviderHelper::playerDetailsCall($key["token"]);
						if($client_player == 'false'){ // client cannot be reached! http errors etc!
							$items_array[] = [
								 "info" => isset($key['info']) ? $key['info'] : '', // Info from RSG, MW Should Return it back!
								 "errorCode" => 999, // transaction already refunded
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
			        	    ];
							$global_error = 999;
							$error_encounter = 1; 
							continue;
						}
						if(abs($client_player->playerdetailsresponse->balance) < $total_bets){
							$items_array[] = [
								 "info" => isset($key['info']) ? $key['info'] : '', // Info from RSG, MW Should Return it back!
								 "errorCode" => 6, // transaction already refunded
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
			        	    ];
							$global_error = 6; 
							$error_encounter = 1; 
							continue;
						}
						if($key['ignoreExpiry'] != 'false'){
							$token_check = Helper::tokenCheck($key["token"]);
							if($token_check != true){ // Token is expired!
								$items_array[] = [
									 "info" => isset($key['info']) ? $key['info'] : '', // Info from RSG, MW Should Return it back!
									 "errorCode" => 3, // transaction already refunded
									 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
				        	    ];
								$global_error = 3; 
								$error_encounter= 1;
								continue;
							}
			 			}
					}
					// $check_win_exist = $this->findGameTransaction($key['txId']);
					$check_bet_exist = ProviderHelper::findGameExt($key['txId'], 1,'transaction_id');
					if($check_bet_exist != 'false'){ // Bet Exist!
						$global_error = 8;
						$error_encounter = 1;
						continue;
					} 
					if($this->array_has_dupes($duplicate_txid_request)){
						$global_error = 8; // Duplicate TxId in the call
						$error_encounter = 1;
						continue;
					}
				} // END ALL OR NON
		} // END FOREACH CHECK
		if($error_encounter != 0){ // ELSE PROCEED TO CLIENT TRANSFERING
			$response = array(
					 "timestamp" => date('YmdHisms'),
				     "signature" => $this->createSignature(date('YmdHisms')),
					 "errorCode" => $global_error,
					 "items" => $items_array,
   			);	
			return $response;
		}

		// ALL GOOD
		$items_array = array(); // ITEMS INFO
		// $isset_before_balance = false;
		foreach ($json_data['items'] as $key){
			$general_details = ["aggregator" => [],"provider" => [],"client" => []];

			# Missing Parameters
			if(!isset($key['info']) || !isset($key['txId']) || !isset($key['betAmount']) || !isset($key['token']) || !isset($key['playerId']) || !isset($key['roundId']) || !isset($key['gameId'])){
				$items_array[] = [
					"info" => $key['info'], 
					"errorCode" => 17, 
					"metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
        	    ]; 
				continue;
			}
			// Provider Details Logger
			$general_details['provider']['operationType'] = $key['operationType'];
			$general_details['provider']['currencyId'] = $key['currencyId'];
			$general_details['provider']['amount'] = $key['betAmount'];
			$general_details['provider']['txCreationDate'] = $json_data['timestamp'];
			$general_details['provider']['txId'] = $key['txId'];
			// Provider Details Logger
			$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $key["gameId"]);
			if($game_details == null){ // Game not found
				$items_array[] = [
					 "info" => $key['info'], 
					 "errorCode" => 11, 
					 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
        	    ]; 
        	    continue;
			}
			$client_details = ProviderHelper::getClientDetails('token', $key["token"]);	
			if($client_details == null){ // SessionNotFound
				$items_array[] = [
					 "info" => $key['info'], 
					 "errorCode" => 2, 
					 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
        	    ];  
				continue;
			}
			if($client_details != null){ // SessionNotFound
				if($client_details->player_id != $key["playerId"]){
					$items_array[] = [
						 "info" => $key['info'], 
						 "errorCode" => 4, 
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
	        	    ];  
	        	    continue;
				}
				$client_player = ProviderHelper::playerDetailsCall($key["token"]);
				if($client_player == 'false'){ 
					$items_array[] = [
						 "info" => $key['info'], 
						 "errorCode" => 999, 
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
	        	    ];   
					continue;
				}
				if($key['currencyId'] != $client_details->default_currency){
	        		$items_array[] = [
						 "info" => $key['info'], 
						 "errorCode" => 16, 
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
	        	    ];   
	        	    continue;
				}
				if(abs($client_player->playerdetailsresponse->balance) < $key['betAmount']){
			        $items_array[] = array(
						 "info" => $key['info'], 
						 "errorCode" => 6, 
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
		   			);
		   			continue;
				}

				// if($isset_before_balance == false){
					$general_details['client']['beforebalance'] = $this->formatBalance(abs($client_player->playerdetailsresponse->balance));
					// $isset_before_balance = true;
				// }
				
				if($key['ignoreExpiry'] != 'false'){
			 		$token_check = Helper::tokenCheck($key["token"]);
					if($token_check != true){
						$items_array[] = array(
							 "info" => $key['info'], 
							 "errorCode" => 3, 
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
			   			);
						continue;
					}
				}
			}
			$check_bet_exist = ProviderHelper::findGameExt($key['txId'], 1,'transaction_id');
			if($check_bet_exist != 'false'){
				$items_array[] = [
					 "info" => $key['info'],
					 "errorCode" => 8,
					 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
        	    ];  
        	    continue;
			} 

			$operation_type = isset($key['operationType']) ? $key['operationType'] : 1;
	 		$payout_reason = 'Bet : '.$this->getOperationType($operation_type);
	 		$win_or_lost = 5; // 0 Lost, 1 win, 3 draw, 4 refund, 5 processing
	 		$method = 1; 
	 	    $token_id = $client_details->token_id;
	 	    if(isset($key['roundId'])){
	 	    	$round_id = $key['roundId'];
	 	    }else{
	 	    	$round_id = 'RSGNOROUNDID';
	 	    }
	 	    if(isset($key['txId'])){
	 	    	$provider_trans_id = $key['txId'];
	 	    }else{
	 	    	$provider_trans_id = 'RSGNOTXID';
	 	    }
	 	    $game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $key['gameId']);	
	 	    $bet_payout = 0; // Bet always 0 payout!
	 	    $income = $key['betAmount'] - $bet_payout;

	 		$game_trans = ProviderHelper::createGameTransaction($token_id, $game_details->game_id, $key['betAmount'],  $bet_payout, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);

	   		$game_transextension = ProviderHelper::createGameTransExtV2($game_trans, $provider_trans_id, $round_id, abs($key['betAmount']), 1);

			try {
				 $client_response = ClientRequestHelper::fundTransfer($client_details,abs($key['betAmount']),$game_details->game_code,$game_details->game_name,$game_transextension,$game_trans,'debit');
				 Helper::saveLog('RSG bet CRID = '.$game_trans, $this->provider_db_id, file_get_contents("php://input"), $client_response);
			} catch (\Exception $e) {
				$items_array[] = array(
					 "info" => $key['info'], 
					 "errorCode" => 999, 
					 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
	   			);
				ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $json_data, 'FAILED', $e->getMessage(), 'FAILED', $general_details);
				Helper::saveLog('RSG bet - FATAL ERROR', $this->provider_db_id, json_encode($items_array), Helper::datesent());
	   			continue;
			}

			if(isset($client_response->fundtransferresponse->status->code) 
	            && $client_response->fundtransferresponse->status->code == "200"){
			    if($key['checkRefunded'] == true){
			    	$refund_arrived = false;
			    	if($refund_arrived == false){
			    		$check_refund_exist_transaction = ProviderHelper::findGameExt($key['roundId'], 3,'round_id'); // round identifier
			    		if($check_refund_exist_transaction != 'false'){
			    			$refund_arrived = true;
			    		}
			    	}
			    	if($refund_arrived == false){
			    	    $check_refund_exist_transaction = ProviderHelper::findGameExt($key['txId'], 3,'round_id'); // Transaction identifier
			    		if($check_refund_exist_transaction != 'false'){
			    			$refund_arrived = true;
			    		}
			    	}
			    	if($refund_arrived == true){
			    		$refund_ext_id = $check_refund_exist_transaction->game_trans_ext_id;
			    		$bet_info_for_this_refund = ProviderHelper::findGameTransaction($game_trans, 'game_transaction');
			    		$updateTheBet = $this->updateBetToWin($bet_info_for_this_refund->round_id, 0, 0, 4, $bet_info_for_this_refund->entry_id);
			    		$client_response_refund = ClientRequestHelper::fundTransfer($client_details,abs($key['betAmount']),$game_details->game_code,$game_details->game_name,$refund_ext_id,$game_trans,'credit', true);
			    		$general_details['client']['afterbalance'] = $this->formatBalance(abs($client_response_refund->fundtransferresponse->balance));
			    		$general_details['aggregator']['externalTxId'] = $refund_ext_id;
			    		$general_details['aggregator']['transaction_status'] = 'SUCCESS';
						Helper::saveLog('RSG betCheckRefund CRID = '.$game_trans, $this->provider_db_id, file_get_contents("php://input"), $client_response_refund);
						$this->updateRSGRefund($refund_ext_id, $game_trans, $key['betAmount'], $json_data, $items_array, $client_response_refund->requestoclient, $client_response_refund, 'SUCCESS', $general_details);
						$items_array[] = [
			    	    	 "externalTxId" => $refund_ext_id,
							 "balance" => $this->formatBalance($client_response_refund->fundtransferresponse->balance),
							 "info" => $key['info'], 
							 "errorCode" => 1, 
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
			    	    ]; 
			    	}else{
			    		$items_array[] = [
			    	    	 "externalTxId" => $game_transextension,
							 "balance" => $this->formatBalance($client_response->fundtransferresponse->balance),
							 "info" => $key['info'], 
							 "errorCode" => 1, 
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
			    	    ];  
			   			$general_details['client']['afterbalance'] = $this->formatBalance(abs($client_response->fundtransferresponse->balance));
			    		$general_details['aggregator']['externalTxId'] = $game_transextension;
			    		$general_details['aggregator']['transaction_status'] = 'SUCCESS';
			    	}
			    }else{
			    	$items_array[] = [
		    	    	 "externalTxId" => $game_transextension,
						 "balance" => $this->formatBalance($client_response->fundtransferresponse->balance),
						 "info" => $key['info'], 
						 "errorCode" => 1, 
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
		    	    ];  
		    	    $general_details['client']['afterbalance'] = $this->formatBalance(abs($client_response->fundtransferresponse->balance));
			    	$general_details['aggregator']['externalTxId'] = $game_transextension;
			    	$general_details['aggregator']['transaction_status'] = 'SUCCESS';
			    }

			    $general_details['provider']['bet'] = $this->formatBalance(abs($key['betAmount']));
			    ProviderHelper::updatecreateGameTransExt($game_transextension,  $json_data, $items_array, $client_response->requestoclient, $client_response, 'SUCCESS', $general_details);
	    	    continue;
			}elseif(isset($client_response->fundtransferresponse->status->code) 
	            && $client_response->fundtransferresponse->status->code == "402"){
				$items_array[] = array(
					 "info" => $key['info'], 
					 "errorCode" => 6, 
					 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
	   			);
	   			$general_details['client']['afterbalance'] = $this->formatBalance(abs($client_response->fundtransferresponse->balance));
			    $general_details['aggregator']['externalTxId'] = $game_transextension;
			    $general_details['aggregator']['transaction_status'] = 'FAILED';
	   			ProviderHelper::updatecreateGameTransExt($game_transextension,  $json_data, $items_array, $client_response->requestoclient, $client_response, 'FAILED', $general_details);
	   			continue;
			}else{ // Unknown Response Code
				$items_array[] = array(
					 "info" => $key['info'], 
					 "errorCode" => 999, 
					 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
	   			);
	   			$general_details['aggregator']['externalTxId'] = $game_transextension;
			    $general_details['aggregator']['transaction_status'] = 'FAILED';
				ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $items_array, 'FAILED', $client_response, 'FAILED', $general_details);
				Helper::saveLog('RSG bet - FATAL ERROR', $this->provider_db_id, $items_array, Helper::datesent());
	   			continue;
			}   

		} // END FOREACH

		$response = array(
			 "timestamp" => date('YmdHisms'),
		     "signature" => $this->createSignature(date('YmdHisms')),
			 "errorCode" => 1,
			 "items" => $items_array,
		);	
		Helper::saveLog('RSG bet - SUCCESS', $this->provider_db_id, file_get_contents("php://input"), $response);
		return $response;
	}

	/**
	 *	
	 * @author's NOTE
	 * @author's NOTE [Error codes, 12 = Invalid Signature, 999 = general error (HTTP), 8 = already exist, 16 = error currency code]	
	 * if incorrect playerId ,incorrect gameId,incorrect roundId,incorrect betTxId, should be return errorCode 7
	 *
	 */
	public function win(Request $request){
		Helper::saveLog('RSG win - EH', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$json_data = json_decode(file_get_contents("php://input"), true);
		if($json_data == null){
			return $this->noBody();
		}
		if($json_data['operatorId'] != $this->operator_id){
			return $this->wrongOperatorID();
		}
		if (!$this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])){
			return $this->authError();
		}

		// # 1 CHECKER 
		$items_allOrNone = array(); // ITEMS TO ROLLBACK IF ONE OF THE ITEMS FAILED!
		$items_array = array(); // ITEMS INFO
		$all_wins_amount = array();
		$duplicate_txid_request = array();

		# Missing Parameters
		if(!isset($json_data['providerId']) || !isset($json_data['allOrNone']) || !isset($json_data['signature']) || !isset($json_data['timestamp']) || !isset($json_data['operatorId']) || !isset($json_data['items'])){
			$response = array(
					 "timestamp" => date('YmdHisms'),
				     "signature" => $this->createSignature(date('YmdHisms')),
					 "errorCode" => 17,
					 "items" => $items_array,
   			);	
			return $response;
		}

		# All or none is true
		$error_encounter = 0;
	    $datatrans_status = true;
	    $global_error = 1;
	    $isset_allwins_amount = 0;
		foreach ($json_data['items'] as $key) { // FOREACH CHECK
		 		if($json_data['allOrNone'] == 'true'){ // IF ANY ITEM FAILED DONT PROCESS IT
		 			if(!isset($key['info'])  || !isset($key['winAmount'])){
		 				//|| !isset($key['playerId']) || !isset($key['gameId']) || !isset($key['roundId'])
		 				$items_array[] = [
							 "info" => isset($key['info']) ? $key['info'] : '', // Info from RSG, MW Should Return it back!
							 "errorCode" => 17, // transaction already refunded
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
		        	    ];
						$global_error = 17;
						$error_encounter = 1;
						continue;
					}
		 			$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $key["gameId"]);
					if($game_details == null && $error_encounter == 0){ // Game not found
						$items_array[] = [
							 "info" => isset($key['info']) ? $key['info'] : '', // Info from RSG, MW Should Return it back!
							 "errorCode" => 11, // transaction already refunded
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
		        	    ];
						$global_error = 11;
						$error_encounter= 1;
						continue;
					}
					if($isset_allwins_amount == 0 ){
						foreach ($json_data['items'] as $key) {
							array_push($all_wins_amount, $key['winAmount']);
							array_push($duplicate_txid_request, $key['txId']);  // Checking for same txId in the call
						} # 1 CHECKER
						$isset_allwins_amount = 1;
					}

					if(isset($key['betTxId']) && $key['betTxId'] != ''){
			 		 	$datatrans = $this->findTransactionRefund($key['betTxId'], 'transaction_id');
			 		 	$transaction_identifier = $key['betTxId'];
	 					$transaction_identifier_type = 'provider_trans_id';
			 		 	if(!$datatrans): 
				 		 	$items_array[] = [
								 "info" => isset($key['info']) ? $key['info'] : '', 
								 "errorCode" => 7, 
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
			        	    ];
			 				$global_error = 7;
							$error_encounter= 1;
							continue;	
			 			else:
			 				// $jsonify = json_decode($datatrans->provider_request, true);
			 			    // $client_details = ProviderHelper::getClientDetails('player_id', $jsonify['items'][0]['playerId']);
			       			$client_details = ProviderHelper::getClientDetails('token_id', $datatrans->token_id);
		 			    	if ($client_details == null){ // SessionNotFound
								$items_array[] = [
									 "info" => isset($key['info']) ? $key['info'] : '', 
									 "errorCode" => 4,
									 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
				        	    ];
								$global_error = 4;
								$error_encounter= 1;
								continue;
							}
			 			endif;
			 		}else{ 
			 			$client_details = ProviderHelper::getClientDetails('player_id', $key['playerId']);
			 			if ($client_details == null){ // SessionNotFound
							$items_array[] = [
								 "info" => isset($key['info']) ? $key['info'] : '',
								 "errorCode" => 4, 
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
			        	    ];
							$global_error = 4;
							$error_encounter= 1;
							continue;
						}
			 			$datatrans = $this->findTransactionRefund($key['roundId'], 'round_id');
			 			$transaction_identifier = $key['roundId'];
	 					$transaction_identifier_type = 'round_id';
			 			if(!$datatrans): // Transaction Not Found!
				 			$items_array[] = [
								 "info" => isset($key['info']) ? $key['info'] : '', 
								 "errorCode" => 7, 
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
			        	    ];
			 				$global_error = 7;
							$error_encounter= 1;
							continue;	
			 			endif;
			 		}	
			 		if($datatrans != false){// Bet for this round is already Refunded
			 			if($datatrans->win == 4){
			 				$items_array[] = [
								 "info" => isset($key['info']) ? $key['info'] : '', 
								 "errorCode" => 14, 
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
			        	    ];
			 				$global_error = 14;
							$error_encounter= 1;
							continue;	
			 			}
		 			}
					if($client_details != null){ // Wrong Player ID
						if($key['currencyId'] != $client_details->default_currency){
							$items_array[] = [
								 "info" => isset($key['info']) ? $key['info'] : '', 
								 "errorCode" => 16, 
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
			        	    ];
							$global_error = 16;
							$error_encounter= 1; 
							continue;
						}
					}
					$client_player = ProviderHelper::playerDetailsCall($client_details->player_token);
					if($client_player == 'false'){ // client cannot be reached! http errors etc!
						$response = array(
							 "timestamp" => date('YmdHisms'),
						     "signature" => $this->createSignature(date('YmdHisms')),
							 "errorCode" => 999,
							 "items" => $items_array,
			   			);	
						return $response;
					}
					$check_win_exist = $this->gameTransactionEXTLog('provider_trans_id',$key['txId'], 2); 
		 			if($check_win_exist != false){
		 				$items_array[] = [
							 "info" => isset($key['info']) ? $key['info'] : '',
							 "errorCode" => 8, 
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
		        	    ];
		 				$global_error = 8; 
						$error_encounter = 1;
		        	    continue;
		 			}
					if($this->array_has_dupes($duplicate_txid_request)){
						$items_array[] = [
							 "info" => isset($key['info']) ? $key['info'] : '', 
							 "errorCode" => 8, 
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
		        	    ];
						$global_error = 8; // Duplicate TxId in the call
						$error_encounter = 1;
						continue;
					}
				} // END ALL OR NON
		} // END FOREACH CHECK
		if($error_encounter != 0){ // ELSE PROCEED TO CLIENT TRANSFERING
			$response = array(
					 "timestamp" => date('YmdHisms'),
				     "signature" => $this->createSignature(date('YmdHisms')),
					 "errorCode" => $global_error,
					 "items" => $items_array,
   			);	
			return $response;
		}

		// dd($datatrans);
		// dd($items_array);
		// return 1;
		// ALL GOOD
		$items_array = array(); // ITEMS INFO
		// $isset_before_balance = false;
		foreach ($json_data['items'] as $key){
				$general_details = ["aggregator" => [],"provider" => [],"client" => []];
				if(!isset($key['info'])  || !isset($key['winAmount'])){
	 				//|| !isset($key['playerId']) || !isset($key['gameId']) || !isset($key['roundId'])
					$items_array[] = [
						 "info" => $key['info'], // Info from RSG, MW Should Return it back!
						 "errorCode" => 17, //The playerId was not found
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
	        	    ];  
					continue;
				}
 				if(isset($key['betTxId']) && $key['betTxId'] != ''){
		 		 	$datatrans = $this->findTransactionRefund($key['betTxId'], 'transaction_id');
		 		 	$transaction_identifier = $key['betTxId'];
 					$transaction_identifier_type = 'provider_trans_id';
		 		 	if(!$datatrans): // Transaction Not Found!
			 		 	$items_array[] = [
							 "info" => isset($key['info']) ? $key['info'] : '', 
							 "errorCode" => 7, 
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
		        	    ];
						continue;	
		 			else:
		 				$jsonify = json_decode($datatrans->provider_request, true);
		 			    $client_details = ProviderHelper::getClientDetails('player_id', $jsonify['items'][0]['playerId']);
	 			    	if ($client_details == null){ // SessionNotFound
							$items_array[] = [
								 "info" => isset($key['info']) ? $key['info'] : '', 
								 "errorCode" => 4,
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
			        	    ];
							continue;
						}
		 			endif;
		 		}else{ // use originalTxid instead
		 			$client_details = ProviderHelper::getClientDetails('player_id', $key['playerId']);
		 			if ($client_details == null){ // SessionNotFound
						$items_array[] = [
							 "info" => isset($key['info']) ? $key['info'] : '',
							 "errorCode" => 4, 
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
		        	    ];
						continue;
					}
		 			$datatrans = $this->findTransactionRefund($key['roundId'], 'round_id');
		 			$transaction_identifier = $key['roundId'];
 					$transaction_identifier_type = 'round_id';
		 			if(!$datatrans): // Transaction Not Found!
			 			$items_array[] = [
							 "info" => isset($key['info']) ? $key['info'] : '', 
							 "errorCode" => 7, 
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
		        	    ];
						continue;	
		 			endif;
		 		}
		 		if($datatrans != false){// Bet for this round is already Refunded
		 			if($datatrans->win == 4){
		 				$items_array[] = [
							 "info" => isset($key['info']) ? $key['info'] : '', 
							 "errorCode" => 14, 
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
		        	    ];
						continue;	
		 			}
	 			}
		 		if($client_details != null){ // Wrong Player ID
					if($key['currencyId'] != $client_details->default_currency){
						$items_array[] = [
							 "info" => isset($key['info']) ? $key['info'] : '', 
							 "errorCode" => 16, 
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
		        	    ];
						continue;
					}
				}
				$client_player = ProviderHelper::playerDetailsCall($client_details->player_token);
				if($client_player == 'false'){ // client cannot be reached! http errors etc!
					$response = array(
						 "timestamp" => date('YmdHisms'),
					     "signature" => $this->createSignature(date('YmdHisms')),
						 "errorCode" => 999,
						 "items" => $items_array,
		   			);	
					return $response;
				}
		 		$check_win_exist = $this->gameTransactionEXTLog('provider_trans_id',$key['txId'], 2); 
	 			if($check_win_exist != false){
	 				$items_array[] = [
						 "info" => $key['info'], 
						 "errorCode" => 8, 
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
	        	    ];  
	        	    continue;
	 			}
 				$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $key["gameId"]);
				if($game_details == null){ // Game not found
					$items_array[] = [
						 "info" => $key['info'],
						 "errorCode" => 11,  // Game Not Found
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
	        	    ]; 
	        	    continue;
				}
				if($key['currencyId'] != $client_details->default_currency){
					$items_array[] = [
						 "info" => $key['info'], // Info from RSG, MW Should Return it back!
						 "errorCode" => 16, // Currency code dont match!
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
	        	    ];   
	        	    continue;
				}
				$client_player = ProviderHelper::playerDetailsCall($client_details->player_token);
				if($client_player == 'false'){ // client cannot be reached! http errors etc!
					$items_array[] = [
						 "info" => $key['info'], // Info from RSG, MW Should Return it back!
						 "errorCode" => 999,
						 "items" => $items_array,
		   			];	
					continue;
				}
				// if($isset_before_balance == false;){
					$general_details['client']['beforebalance'] = $this->formatBalance($client_player->playerdetailsresponse->balance);
					// $isset_before_balance = true;
				// }
				$general_details['provider']['operationType'] = $key['operationType'];
				$general_details['provider']['currencyId'] = $key['currencyId'];
				$general_details['provider']['amount'] = $key['winAmount'];
				$general_details['provider']['txCreationDate'] = $json_data['timestamp'];
				$general_details['provider']['txId'] = $key['txId'];

				$game_transextension = ProviderHelper::createGameTransExtV2($datatrans->game_trans_id, $key['txId'], $datatrans->round_id, abs($key['winAmount']), 2);

				try {
				 $client_response = ClientRequestHelper::fundTransfer($client_details,abs($key['winAmount']),$game_details->game_code,$game_details->game_name,$game_transextension,$datatrans->game_trans_id,'credit');
				 Helper::saveLog('RSG win CRID = '.$datatrans->game_trans_id, $this->provider_db_id, file_get_contents("php://input"), $client_response);
				} catch (\Exception $e) {
				$items_array[] = array(
					 "info" => $key['info'], 
					 "errorCode" => 999, 
					 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
					);
				ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $json_data, 'FAILED', $e->getMessage(), 'FAILED', $general_details);
				Helper::saveLog('RSG win - FATAL ERROR', $this->provider_db_id, json_encode($items_array), Helper::datesent());
					continue;
				}

				if(isset($client_response->fundtransferresponse->status->code) 
				             && $client_response->fundtransferresponse->status->code == "200"){
					$general_details['provider']['win'] = $this->formatBalance($client_response->fundtransferresponse->balance);
					$general_details['client']['afterbalance'] = $this->formatBalance($client_response->fundtransferresponse->balance);
					$general_details['aggregator']['externalTxId'] = $game_transextension;
					$general_details['aggregator']['transaction_status'] = 'SUCCESS';

					if($key['winAmount'] != 0){
		 	  			if($datatrans->bet_amount > $key['winAmount']){
		 	  				$win = 0; // lost
		 	  				$entry_id = 1; //lost
		 	  				$income = $datatrans->bet_amount - $key['winAmount'];
		 	  			}else{
		 	  				$win = 1; //win
		 	  				$entry_id = 2; //win
		 	  				$income = $datatrans->bet_amount - $key['winAmount'];
		 	  			}
	 	  				$updateTheBet = $this->updateBetToWin($datatrans->round_id, $key['winAmount'], $income, $win, $entry_id);
		 	  		}else{
		 	  			$updateTheBet = $this->updateBetToWin($datatrans->round_id, $datatrans->pay_amount, $datatrans->income, 0, $datatrans->entry_id);
		 	  		}
		 	  		
		 	  		ProviderHelper::updatecreateGameTransExt($game_transextension,  $json_data, $items_array, $client_response->requestoclient, $client_response, 'SUCCESS', $general_details);

		 	  		if(isset($key['returnBetsAmount']) && $key['returnBetsAmount'] == true){
		 	  			if(isset($key['betTxId'])){
	        	    		$datatrans = $this->findTransactionRefund($key['betTxId'], 'transaction_id');
	        	    	}else{
	        	    		$datatrans = $this->findTransactionRefund($key['roundId'], 'round_id');
	        	    	}
        	    		$gg = json_decode($datatrans->provider_request);
				 		$total_bets = array();
				 		foreach ($gg->items as $gg_tem) {
							array_push($total_bets, $gg_tem->betAmount);
				 		}
				 		$items_array[] = [
		        	    	 "externalTxId" => $game_transextension, // MW Game Transaction Id
							 "balance" => $this->formatBalance($client_response->fundtransferresponse->balance),
							 "betsAmount" => $this->formatBalance(array_sum($total_bets)),
							 "info" => $key['info'], // Info from RSG, MW Should Return it back!
							 "errorCode" => 1,
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : '', // Optional but must be here!
		        	    ];
		 	  		}else{
		 	  			$items_array[] = [
		        	    	 "externalTxId" => $game_transextension, // MW Game Transaction Id
							 "balance" => $this->formatBalance($client_response->fundtransferresponse->balance),
							 "info" => $key['info'], // Info from RSG, MW Should Return it back!
							 "errorCode" => 1,
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : '', // Optional but must be here!
		        	    ];
		 	  		}

				}else{ // Unknown Response Code
					$items_array[] = array(
						"info" => $key['info'], 
						"errorCode" => 999, 
						"metadata" => isset($key['metadata']) ? $key['metadata'] : ''
					);
					$general_details['aggregator']['externalTxId'] = $game_transextension;
					$general_details['aggregator']['transaction_status'] = 'FAILED';
					ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $json_data, 'FAILED', $client_response, 'FAILED', $general_details);
					Helper::saveLog('RSG win - FATAL ERROR', $this->provider_db_id, $items_array, Helper::datesent());
					continue;
				}    
		} // END FOREACH
		$response = array(
			 "timestamp" => date('YmdHisms'),
		     "signature" => $this->createSignature(date('YmdHisms')),
			 "errorCode" => 1,
			 "items" => $items_array,
		);	
		Helper::saveLog('RSG win - SUCCESS', $this->provider_db_id, file_get_contents("php://input"), $response);
		return $response;
	}

	/**
	 *	
	 * NOTE
	 * Accept Bet and Win At The Same Time!
	 */
	public function betwin(Request $request){
		Helper::saveLog('RSG betwin - EH', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$json_data = json_decode(file_get_contents("php://input"), true);
		if($json_data == null){
			return $this->noBody();
		}
		if($json_data['operatorId'] != $this->operator_id){ //Wrong Operator Id 
			return $this->wrongOperatorID();
		}
		if(!$this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])){ 
			return $this->authError();
		}
		
		$items_allOrNone = array(); // ITEMS TO ROLLBACK IF ONE OF THE ITEMS FAILED!
		$items_revert_update = array(); // If failed revert changes
		$items_array = array();
		$duplicate_txid_request = array();
		$all_bets_amount = array();
		$isset_allbets_amount = 0;
		# Missing Parameters
		if(!isset($json_data['providerId']) || !isset($json_data['allOrNone']) || !isset($json_data['signature']) || !isset($json_data['timestamp']) || !isset($json_data['operatorId']) || !isset($json_data['items'])){
			$response = array(
					 "timestamp" => date('YmdHisms'),
				     "signature" => $this->createSignature(date('YmdHisms')),
					 "errorCode" => 17,
					 "items" => $items_array,
   			);	
			return $response;
		}

	 	$error_encounter = 0;
	    $global_error = 1;
		foreach ($json_data['items'] as $key) { // FOREACH CHECK
		 		if($json_data['allOrNone'] == 'true'){ // IF ANY ITEM FAILED DONT PROCESS IT
		 			# Missing item param
					if(!isset($key['txId']) || !isset($key['betAmount']) || !isset($key['winAmount']) || !isset($key['token']) || !isset($key['playerId']) || !isset($key['roundId']) || !isset($key['gameId'])){
						$items_array[] = [
							 "betInfo" => isset($key['betInfo']) ? $key['betInfo'] : '', // Info from RSG, MW Should Return it back!
							 "winInfo" => isset($key['winInfo']) ? $key['winInfo'] : '', // Info from RSG, MW Should Return it back!
							 "errorCode" => 17, // transaction already refunded
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
		        	    ];
						$global_error = $global_error == 1 ? 17 : $global_error;
						$error_encounter = 1;
						continue;
					}
		 			$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $key["gameId"]);
					if($game_details == null){ // Game not found
						$items_array[] = [
							 "betInfo" => isset($key['betInfo']) ? $key['betInfo'] : '', // Info from RSG, MW Should Return it back!
							 "winInfo" => isset($key['winInfo']) ? $key['winInfo'] : '', // Info from RSG, MW Should Return it back!
							 "errorCode" => 11, // transaction already refunded
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
		        	    ];
						$global_error = $global_error == 1 ? 11 : $global_error;
						$error_encounter= 1;
						continue;
					}
					if($isset_allbets_amount == 0){ # Calculate all total bets
						foreach ($json_data['items'] as $key) {
							array_push($all_bets_amount, $key['betAmount']);
							array_push($duplicate_txid_request, $key['txId']);  // Checking for same txId in the call
						}
						$isset_allbets_amount = 1;
					}
					$client_details = ProviderHelper::getClientDetails('token', $key["token"]);	
					if ($client_details == null){ // SessionNotFound
						$items_array[] = [
							 "betInfo" => isset($key['betInfo']) ? $key['betInfo'] : '', // Info from RSG, MW Should Return it back!
							 "winInfo" => isset($key['winInfo']) ? $key['winInfo'] : '', // Info from RSG, MW Should Return it back!
							 "errorCode" => 2, // transaction already refunded
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
		        	    ];
						$global_error = $global_error == 1 ? 2 : $global_error;
						$error_encounter= 1;
						continue;
					}
					if($key['ignoreExpiry'] != 'false'){
				 		$token_check = Helper::tokenCheck($key["token"]);
						if($token_check != true){
							$items_array[] = [
								 "betInfo" => isset($key['betInfo']) ? $key['betInfo'] : '', // Info from RSG, MW Should Return it back!
								 "winInfo" => isset($key['winInfo']) ? $key['winInfo'] : '', // Info from RSG, MW Should Return it back!
								 "errorCode" => 3, // transaction already refunded
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
			        	    ];
							$global_error = $global_error == 1 ? 3 : $global_error;
							$error_encounter = 1;
							continue;
						}
					}
					if($client_details != null){ 
						if($client_details->player_id != $key["playerId"]){
							$items_array[] = [
								 "betInfo" => isset($key['betInfo']) ? $key['betInfo'] : '', 
								 "winInfo" => isset($key['winInfo']) ? $key['winInfo'] : '', 
								 "errorCode" => 4, // transaction already refunded
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
			        	    ];
							$global_error = $global_error == 1 ? 4 : $global_error;
							$error_encounter= 1;
							continue;
						}
						if($key['currencyId'] != $client_details->default_currency){
							$items_array[] = [
								 "betInfo" => isset($key['betInfo']) ? $key['betInfo'] : '', 
								 "winInfo" => isset($key['winInfo']) ? $key['winInfo'] : '', 
								 "errorCode" => 16, // transaction already refunded
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
			        	    ];
							$global_error = $global_error == 1 ? 16 : $global_error;
							$error_encounter= 1; 
							continue;
						}
						$client_player = ProviderHelper::playerDetailsCall($client_details->player_token);
						if($client_player == 'false'){ // client cannot be reached! http errors etc!
							$items_array[] = [
								 "betInfo" => isset($key['betInfo']) ? $key['betInfo'] : '', 
								 "winInfo" => isset($key['winInfo']) ? $key['winInfo'] : '', 
								 "errorCode" => 999, // transaction already refunded
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
			        	    ];
							$global_error = $global_error == 1 ? 999 : $global_error;
							$error_encounter= 1; 
							continue;
						}
					}
					$check_win_exist = $this->gameTransactionEXTLog('provider_trans_id',$key['txId'], 1); 
		 			if($check_win_exist != false){
		 				$items_array[] = [
							 "betInfo" => isset($key['betInfo']) ? $key['betInfo'] : '',
							 "winInfo" => isset($key['winInfo']) ? $key['winInfo'] : '',
							 "errorCode" => 8, 
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
		        	    ];
		 				$global_error = $global_error == 1 ? 8 : $global_error;
						$error_encounter = 1;
		        	    continue;
		 			}
		 			$check_win_exist = $this->gameTransactionEXTLog('provider_trans_id',$key['txId'], 2);
		 			if($check_win_exist != false){
		 				$items_array[] = [
							 "betInfo" => isset($key['betInfo']) ? $key['betInfo'] : '', 
							 "winInfo" => isset($key['winInfo']) ? $key['winInfo'] : '',
							 "errorCode" => 8, 
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
		        	    ];
		 				$global_error = $global_error == 1 ? 8 : $global_error;
						$error_encounter = 1;
		        	    continue;
		 			}
		 			if($this->array_has_dupes($duplicate_txid_request)){
						$items_array[] = [
							 "betInfo" => isset($key['betInfo']) ? $key['betInfo'] : '', 
							 "winInfo" => isset($key['winInfo']) ? $key['winInfo'] : '',
							 "errorCode" => 8, 
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
		        	    ];
		 				$global_error = $global_error == 1 ? 8 : $global_error;
						$error_encounter = 1;
		        	    continue;
					}
				} // END ALL OR NON
		} // END FOREACH CHECK
		if($error_encounter != 0){ // ELSE PROCEED TO CLIENT TRANSFERING
			$response = array(
					 "timestamp" => date('YmdHisms'),
				     "signature" => $this->createSignature(date('YmdHisms')),
					 "errorCode" => $global_error,
					 "items" => $items_array,
   			);	
			return $response;
		}

		// ALL GOOD
		$items_array = array(); // ITEMS INFO
		$duplicate_txid_request = array();
		$all_bets_amount = array();
		$isset_allbets_amount = 0;
		foreach ($json_data['items'] as $key){
				$general_details = ["aggregator" => [],"provider" => [],"client" => []];
				$general_details2 = ["aggregator" => [],"provider" => [],"client" => []];
				# Missing item param
				if(!isset($key['txId']) || !isset($key['betAmount']) || !isset($key['winAmount']) || !isset($key['token']) || !isset($key['playerId']) || !isset($key['roundId']) || !isset($key['gameId']) || !isset($key['betInfo']) || !isset($key['winInfo'])){
					 $items_array[] = [
						 "betInfo" => isset($key['betInfo']) ? $key['betInfo'] : '', // Betinfo
					     "winInfo" => isset($key['winInfo']) ? $key['winInfo'] : '', // IWininfo
						 "errorCode" => 17, //The playerId was not found
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
	        	    ]; 
					continue;
				}
				$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $key["gameId"]);
					if($game_details == null){ // Game not found
					$items_array[] = [
						 "betInfo" => $key['betInfo'], // Betinfo
					     "winInfo" => $key['winInfo'], // IWininfo
						 "errorCode" => 11, //The playerId was not found
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
	        	    ];  
	        	    continue;
				}
				if($isset_allbets_amount == 0){ # Calculate all total bets
					foreach ($json_data['items'] as $key) {
						array_push($all_bets_amount, $key['betAmount']);
						array_push($duplicate_txid_request, $key['txId']);  // Checking for same txId in the call
					}
					$isset_allbets_amount = 1;
				}
				$client_details = ProviderHelper::getClientDetails('token', $key["token"]);	
				if($client_details == null){
		 			$items_array[] = [
						 "betInfo" => $key['betInfo'], // Betinfo
					     "winInfo" => $key['winInfo'], // IWininfo
						 "errorCode" => 2, 
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
	        	    ];  
	        	    continue;
		 		}
		 		if($key['ignoreExpiry'] != 'false'){
			 		$token_check = Helper::tokenCheck($key["token"]);
					if($token_check != true){
						$items_array[] = [
							 "betInfo" => $key['betInfo'], // Betinfo
						     "winInfo" => $key['winInfo'], // IWininfo
							 "errorCode" => 3, 
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
		        	    ];  
						continue;
					}
				}
		 		if($client_details->player_id != $key["playerId"]){
					$items_array[] = [
						"betInfo" => $key['betInfo'], // Betinfo
					    "winInfo" => $key['winInfo'], // IWininfo
						"errorCode" => 4, 
						"metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
	        	    ];  
					continue;
				}
				if($key['currencyId'] != $client_details->default_currency){
					$items_array[] = [
						 "betInfo" => $key['betInfo'], // Betinfo
					     "winInfo" => $key['winInfo'], // IWininfo
						 "errorCode" => 16, // Currency code dont match!
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
	        	    ];   
	        	    continue;
				}
	 			$check_win_exist = $this->gameTransactionEXTLog('provider_trans_id',$key['txId'], 1); 
	 			if($check_win_exist != false){
	 				$items_array[] = [
						 "betInfo" => $key['betInfo'], // Betinfo
					     "winInfo" => $key['winInfo'], // IWininfo
						 "errorCode" => 8, 
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
	        	    ];  
	        	    continue;
	 			}
	 			$check_win_exist = $this->gameTransactionEXTLog('provider_trans_id',$key['txId'], 2);
	 			if($check_win_exist != false){
	 				$items_array[] = [
						 "betInfo" => $key['betInfo'], // Betinfo
					     "winInfo" => $key['winInfo'], // IWininfo
						 "errorCode" => 8, 
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
	        	    ]; 
	        	    continue;
	 			}
	 			$client_player = ProviderHelper::playerDetailsCall($client_details->player_token);
				if(abs($client_player->playerdetailsresponse->balance) < $key['betAmount']){
					$items_array[] = [
						 "betInfo" => $key['betInfo'], // Betinfo
						 "winInfo" => $key['winInfo'], // IWininfo
						 "errorCode" => 6, // Player Low Balance!
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
	        	    ]; 
	        	    continue;
				}
				// if($this->array_has_dupes($duplicate_txid_request)){
				// 	$items_array[] = [
				// 		 "betInfo" => isset($key['betInfo']) ? $key['betInfo'] : '', 
				// 		 "winInfo" => isset($key['winInfo']) ? $key['winInfo'] : '',
				// 		 "errorCode" => 8, 
				// 		 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
	   			//   ];
	   			//   continue;
				// }

				# Provider Transaction Logger
				$general_details['client']['beforebalance'] = $this->formatBalance($client_player->playerdetailsresponse->balance);
				$general_details['provider']['operationType'] = $key['betOperationType'];
				$general_details['provider']['currencyId'] = $key['currencyId'];
				$general_details['provider']['amount'] = $key['betAmount'];
				$general_details['provider']['txCreationDate'] = $json_data['timestamp'];
				$general_details['provider']['txId'] = $key['txId'];

				$general_details2['provider']['operationType'] = $key['winOperationType'];
				$general_details2['provider']['currencyId'] = $key['currencyId'];
				$general_details2['provider']['amount'] = $key['winAmount'];
				$general_details2['provider']['txCreationDate'] = $json_data['timestamp'];
				$general_details2['provider']['txId'] = $key['txId'];
				# Provider Transaction Logger
				
				## DEBIT
				$payout_reason = 'Bet : '.$this->getOperationType($key['betOperationType']);
		 		$win_or_lost = 0;
		 		$method = 1;
		 		$income = null; // Sample
		 	    $token_id = $client_details->token_id;
		 	    if(isset($key['roundId'])){
		 	    	$round_id = $key['roundId'];
		 	    }else{
		 	    	$round_id = 1;
		 	    }
		 	    if(isset($key['txId'])){
		 	    	$provider_trans_id = $key['txId'];
		 	    }else{
		 	    	$provider_trans_id = null;
		 	    }

				$game_trans = ProviderHelper::createGameTransaction($token_id, $game_details->game_id, $key['betAmount'],  $key['betAmount'], $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);

				$game_transextension = ProviderHelper::createGameTransExtV2($game_trans, $key['txId'], $key['roundId'], abs($key['betAmount']), 1);

				try {
				 $client_response = ClientRequestHelper::fundTransfer($client_details,abs($key['betAmount']),$game_details->game_code,$game_details->game_name,$game_transextension,$game_trans,'debit');
				 Helper::saveLog('RSG betwin CRID = '.$game_trans, $this->provider_db_id, file_get_contents("php://input"), $client_response);
				} catch (\Exception $e) {
				$items_array[] = array(
					 "info" => $key['info'], 
					 "errorCode" => 999, 
					 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
					);
				ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $json_data, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
				Helper::saveLog('RSG betwin - FATAL ERROR', $this->provider_db_id, json_encode($items_array), Helper::datesent());
					continue;
				}

				if(isset($client_response->fundtransferresponse->status->code) 
				             && $client_response->fundtransferresponse->status->code == "200"){
					# CREDIT
					$general_details['client']['afterbalance'] = $this->formatBalance($client_response->fundtransferresponse->balance);
					$general_details['aggregator']['externalTxId'] = $game_transextension;
					$general_details['aggregator']['transaction_status'] = 'SUCCESS';

					$game_transextension2 = ProviderHelper::createGameTransExtV2($game_trans, $key['txId'], $key['roundId'], abs($key['winAmount']), 2);
					$client_response2 = ClientRequestHelper::fundTransfer($client_details,abs($key['winAmount']),$game_details->game_code,$game_details->game_name,$game_transextension2,$game_trans,'credit');
					 Helper::saveLog('RSG betwin CRID = '.$game_trans, $this->provider_db_id, file_get_contents("php://input"), $client_response2);

					$general_details2['client']['beforebalance'] = $this->formatBalance($client_response->fundtransferresponse->balance);
					$general_details2['client']['afterbalance'] = $this->formatBalance($client_response2->fundtransferresponse->balance);
					$general_details2['aggregator']['externalTxId'] = $game_transextension2;
					$general_details2['aggregator']['transaction_status'] = 'SUCCESS';

			 		$payout_reason = 'Win : '.$this->getOperationType($key['winOperationType']);
			 		$win_or_lost = 1;
			 		$method = 2;
			 	    $token_id = $client_details->token_id;
			 	    if(isset($key['roundId'])){
			 	    	$round_id = $key['roundId'];
			 	    }else{
			 	    	$round_id = 1;
			 	    }
			 	    if(isset($key['txId'])){
			 	    	$provider_trans_id = $key['txId'];
			 	    }else{
			 	    	$provider_trans_id = null;
			 	    }
			 	    if(isset($key['betTxId'])){
        	    		$bet_transaction_detail = $this->findGameTransaction($key['betTxId']);
        	    		$bet_transaction = $bet_transaction_detail->bet_amount;
        	    	}else{
        	    		$bet_transaction_detail = $this->findPlayerGameTransaction($key['roundId'], $key['playerId']);
        	    		$bet_transaction = $bet_transaction_detail->bet_amount;
        	    	}
			 	    $income = $bet_transaction - $key['winAmount']; // Sample	
		 	  		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $key['gameId']);
					if($key['winAmount'] != 0){
		 	  			if($bet_transaction_detail->bet_amount > $key['winAmount']){
		 	  				$win = 0; // lost
		 	  				$entry_id = 1; //lost
		 	  				$income = $bet_transaction_detail->bet_amount - $key['winAmount'];
		 	  			}else{
		 	  				$win = 1; //win
		 	  				$entry_id = 2; //win
		 	  				$income = $bet_transaction_detail->bet_amount - $key['winAmount'];
		 	  			}
		 	  				$updateTheBet = $this->updateBetToWin($key['roundId'], $key['winAmount'], $income, $win, $entry_id);
		 	  		}
					# CREDIT
					$items_array[] = [
	        	    	 "externalTxId" => $game_transextension2, // MW Game Transaction Only Save The Last Game Transaction Which is the credit!
						 "balance" => $this->formatBalance($client_response2->fundtransferresponse->balance),
						 "betInfo" => $key['betInfo'], // Betinfo
						 "winInfo" => $key['winInfo'], // IWininfo
						 "errorCode" => 1,
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
	        	    ];
	        	    ProviderHelper::updatecreateGameTransExt($game_transextension,  $json_data, $items_array, $client_response->requestoclient, $client_response, 'SUCCESS', $general_details);
	        	    ProviderHelper::updatecreateGameTransExt($game_transextension2,  $json_data, $items_array, $client_response2->requestoclient, $client_response2, 'SUCCESS', $general_details2);

				}elseif(isset($client_response->fundtransferresponse->status->code) 
				            && $client_response->fundtransferresponse->status->code == "402"){

					$general_details['client']['afterbalance'] = $this->formatBalance($client_response->fundtransferresponse->balance);
					$general_details['aggregator']['externalTxId'] = $game_transextension;
					$general_details['aggregator']['transaction_status'] = 'SUCCESS';
					
					$items_array[] = [
						 "betInfo" => $key['betInfo'], // Betinfo
						 "winInfo" => $key['winInfo'], // IWininfo
						 "errorCode" => 6, // Player Low Balance!
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
	        	    ]; 
	        	    ProviderHelper::updatecreateGameTransExt($game_transextension,  $json_data, $items_array, $client_response->requestoclient, $client_response, 'FAILED', $general_details);
	        	    continue;
				}else{ // Unknown Response Code
					$general_details['aggregator']['transaction_status'] = 'SUCCESS';
					$items_array[] = [
						 "betInfo" => $key['betInfo'], // Betinfo
						 "winInfo" => $key['winInfo'], // IWininfo
						 "errorCode" => 6, // Player Low Balance!
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
	        	    ]; 
					ProviderHelper::updatecreateGameTransExt($game_transextension,  $json_data, $items_array, $client_response->requestoclient, $client_response, 'FAILED', $general_details);
					Helper::saveLog('RSG betwin - FATAL ERROR', $this->provider_db_id, $items_array, Helper::datesent());
	        	    continue;
				}    
				## DEBIT
		} # END FOREACH
		$response = array(
			 "timestamp" => date('YmdHisms'),
		     "signature" => $this->createSignature(date('YmdHisms')),
			 "errorCode" => 1,
			 "items" => $items_array,
		);
		Helper::saveLog('RSG BETWIN - SUCCESS', $this->provider_db_id, file_get_contents("php://input"), $response);
		return $response;
	}

	/**
	 * UNDERCONSTRUCTION
	 * Refund Find Logs According to gameround, or TransactionID and refund whether it  a bet or win
	 *
	 * refundOriginalBet (No proper explanation on the doc!)	
	 * originalTxtId = either its winTxd or betTxd	
	 * refundround is true = always roundid	
	 * if roundid is missing always originalTxt, same if originaltxtid use roundId
	 *
	 */
	public function refund(Request $request){
		Helper::saveLog('RSG refund - EH', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$json_data = json_decode(file_get_contents("php://input"), true);
		if($json_data == null){
			return $this->noBody();
		}
		if($json_data['operatorId'] != $this->operator_id){ //Wrong Operator Id 
			return $this->wrongOperatorID();
		}
		if(!$this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])){ 
			return $this->authError();
		}

		# Missing Parameters
		if(!isset($json_data['items']) || !isset($json_data['operatorId']) || !isset($json_data['timestamp']) || !isset($json_data['signature']) || !isset($json_data['allOrNone']) || !isset($json_data['providerId'])){
			$response = array(
					 "timestamp" => date('YmdHisms'),
				     "signature" => $this->createSignature(date('YmdHisms')),
					 "errorCode" => 17,
					 "items" => $items_array,
   			);	
			return $response;
		}

		$items_allOrNone = array(); // ITEMS TO ROLLBACK IF ONE OF THE ITEMS FAILED!
		$items_revert_update = array(); // If failed revert changes
		$items_array = array();
		$error_encounter = 0;
		$global_error = 1;

		foreach ($json_data['items'] as $key) { // #1 FOREACH CHECK
			if(isset($key['originalTxId']) && $key['originalTxId'] != ''){// if both playerid and roundid is missing
			    $datatrans = $this->findTransactionRefund($key['originalTxId'], 'transaction_id');
				$transaction_identifier = $key['originalTxId'];
				$transaction_identifier_type = 'provider_trans_id';
				if($datatrans != false){
			        $player_id = ProviderHelper::getClientDetails('token_id', $datatrans->token_id)->player_id; // IF EXIT
				}else{
					$player_id = $key['playerId']; // IF NOT DID NOT EXIST
				}
	 		}else{ // use originalTxid instead
	 		 	$datatrans = $this->findTransactionRefund($key['roundId'], 'round_id');
				$transaction_identifier = $key['roundId'];
				$transaction_identifier_type = 'round_id';
				$player_id = $key['playerId'];
	 		}
	 		$transaction_to_refund = array();
	 		$is_bet = array();
	 		$is_win = array();
			if($json_data['allOrNone'] == 'true'){ // #2 IF ANY ITEM FAILED DONT PROCESS IT
 		    	if($transaction_identifier_type == 'provider_trans_id'){  // originalTxt, no need to filter playerID
					if($datatrans != false){
						$entry_type = $datatrans->game_transaction_type == 1 ? 'debit' : 'credit';
			    		$check_bet_exist_transaction = ProviderHelper::findGameExt($datatrans->round_id, 1,'round_id');
			    		if($check_bet_exist_transaction != 'false'){
			    			$bet_item = [
								"game_trans_id" => $check_bet_exist_transaction->game_trans_id,
								"game_trans_ext_id"  => $check_bet_exist_transaction->game_trans_ext_id,
								"amount" => $check_bet_exist_transaction->amount,
								"game_transaction_type" => $check_bet_exist_transaction->game_transaction_type,
							];
							$is_bet[] = $bet_item;
							$transaction_to_refund[] = $bet_item;
			    		}
			    	    $check_win_exist_transaction = ProviderHelper::findGameExt($datatrans->round_id, 2,'round_id');
			    		if($check_win_exist_transaction != 'false'){
			    			$win_item = [
								"game_trans_id" => $check_win_exist_transaction->game_trans_id,
								"game_trans_ext_id"  => $check_win_exist_transaction->game_trans_ext_id,
								"amount" => $check_win_exist_transaction->amount,
								"game_transaction_type" => $check_win_exist_transaction->game_transaction_type,
							];
							$is_win[] = $win_item;
							$transaction_to_refund[] = $win_item;
			    		}
					}
				}else{  // RoundID, if round ID filter Player ID, and round ID
					if($datatrans != false){
						$entry_type = $datatrans->game_transaction_type == 1 ? 'debit' : 'credit';
			    		$check_bet_exist_transaction = ProviderHelper::findGameExt($datatrans->round_id, 1,'round_id');
			    		if($check_bet_exist_transaction != 'false'){
			    			$bet_item = [
								"game_trans_id" => $check_bet_exist_transaction->game_trans_id,
								"game_trans_ext_id"  => $check_bet_exist_transaction->game_trans_ext_id,
								"amount" => $check_bet_exist_transaction->amount,
								"game_transaction_type" => $check_bet_exist_transaction->game_transaction_type,
							];
							$is_bet[] = $bet_item;
							$transaction_to_refund[] = $bet_item;
			    		}
			    	    $check_win_exist_transaction = ProviderHelper::findGameExt($datatrans->round_id, 2,'round_id');
			    		if($check_win_exist_transaction != 'false'){
			    			$win_item = [
								"game_trans_id" => $check_win_exist_transaction->game_trans_id,
								"game_trans_ext_id"  => $check_win_exist_transaction->game_trans_ext_id,
								"amount" => $check_win_exist_transaction->amount,
								"game_transaction_type" => $check_win_exist_transaction->game_transaction_type,
							];
							$is_win[] = $win_item;
							$transaction_to_refund[] = $win_item;
			    		}
					}
				}
				# FILTER IF THE ITEMS SHOULD BE PROCESSED
				if($key['holdEarlyRefund'] == false){
					if($datatrans == false){
						$items_array[] = [
							 "info" => $key['info'],
							 "errorCode" => 7, // this transaction is not found
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
					    ]; 
				    	$global_error = $global_error == 1 ? 7 : $global_error;
						$error_encounter = 1;
						continue;
					}
					if($datatrans != false){
						$refund_check = $this->gameTransactionEXTLog('provider_trans_id', $key['txId'], 3);
			 		    if($refund_check != false){
			 		    	if($refund_check->transaction_detail == '"PROCESSING"'){
			 		    		$items_array[] = [
									 "info" => $key['info'],
									 "errorCode" => 8, // this transaction is already in exist
									 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
							    ]; 
						    	$global_error = $global_error == 1 ? 8 : $global_error;
								$error_encounter = 1;
								continue;
			 		    	}else{
								$items_array[] = [
									 "info" => $key['info'],
									 "errorCode" => 14, // this transaction already rolleback
									 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
							    ]; 
						    	$global_error = $global_error == 1 ? 14 : $global_error;
								$error_encounter = 1;
								continue;
							}
			 		    } 
					}
				}else{ // hold early refundtrue
					$refund_check = $this->gameTransactionEXTLog('provider_trans_id', $key['txId'], 3);
		 		    if($refund_check != false){
		 		    	if($refund_check->transaction_detail == '"PROCESSING"' || $refund_check->transaction_detail == '"FAILED"'){
		 		    		$items_array[] = [
								 "info" => $key['info'],
								 "errorCode" => 8, // this transaction is already in exist
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
						    ]; 
					    	$global_error = $global_error == 1 ? 8 : $global_error;
							$error_encounter = 1;
							continue;
		 		    	}else{
							$items_array[] = [
								 "info" => $key['info'],
								 "errorCode" => 14, // this transaction already rolleback
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
						    ]; 
					    	$global_error = $global_error == 1 ? 14 : $global_error;
							$error_encounter = 1;
							continue;
						}
					}
				}
				# IF BET IS ALREADY WON WHEN REFUNDROUND IS FALSE
				if(count($transaction_to_refund) > 0){
					if($key['refundRound'] == false){
						if($entry_type == 'debit'){
							if(count($is_win) > 0){ // This Bet Has Already Wonned
								$items_array[] = [
									 "info" => $key['info'],
									 "errorCode" => 20,
									 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
							    ]; 
						    	$global_error = $global_error == 1 ? 20 : $global_error;
								$error_encounter= 1;
								continue;
							}
						}
					}
				}
				$client_details = ProviderHelper::getClientDetails('player_id', $player_id);
	 		    if($client_details == null){
	 		    	$items_array[] = [
						 "info" => $key['info'],
						 "errorCode" => 4, 
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
				    ];  
					$global_error = $global_error == 1 ? 4 : $global_error;
					$error_encounter = 1;
					continue;
	 		    }
	 		    if($key['operationType'] != 3){
	 		    	$items_array[] = [
						 "info" => $key['info'],
						 "errorCode" => 19, 
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
				    ];  
					$global_error = $global_error == 1 ? 19 : $global_error;
					$error_encounter = 1;
					continue;
	 		    }
				$client_player = ProviderHelper::playerDetailsCall($client_details->player_token);
				if($client_player == 'false'){ // client cannot be reached! http errors etc!
					$items_array[] = [
						 "info" => $key['info'],
						 "errorCode" =>999, 
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
				    ];
					$global_error = $global_error == 1 ? 999 : $global_error;
					$error_encounter = 1; 
					continue;
				}
				
			
			} // #2 ALL OR NONE
		} // #1 END FOREACH CHECK

		if($error_encounter != 0){ // ELSE PROCEED TO CLIENT TRANSFERING
			$response = array(
					 "timestampa" => date('YmdHisms'),
				     "signature" => $this->createSignature(date('YmdHisms')),
					 "errorCode" => $global_error,
					 "items" => $items_array,
   			);	
			return $response;
		}
		// transaction_identifier
		// dd($items_array);
		// dd($is_bet);
		// dd($entry_type);
		// dd($transaction_to_refund);
		// return 1;	

		// ALL GOOD
		$items_array = array();
		$transaction_to_refund = array();
		foreach ($json_data['items'] as $key) { 
			$general_details = ["aggregator" => [], "provider" => [], "client" => []];
			if(isset($key['originalTxId']) && $key['originalTxId'] != ''){// if both playerid and roundid is missing
			    $datatrans = $this->findTransactionRefund($key['originalTxId'], 'transaction_id');
				$transaction_identifier = $key['originalTxId'];
				$transaction_identifier_type = 'provider_trans_id';
				if($datatrans != false){
			        $player_id = ProviderHelper::getClientDetails('token_id', $datatrans->token_id)->player_id; // IF EXIT
				}else{
					$player_id = $key['playerId']; // IF NOT DID NOT EXIST
				}
	 		}else{ // use originalTxid instead
	 		 	$datatrans = $this->findTransactionRefund($key['roundId'], 'round_id');
				$transaction_identifier = $key['roundId'];
				$transaction_identifier_type = 'round_id';
				$player_id = $key['playerId'];
	 		}
	 		$transaction_to_refund = array();
	 		$is_bet = array();
	 		$is_win = array();
		    	if($transaction_identifier_type == 'provider_trans_id'){  // originalTxt, no need to filter playerID
				if($datatrans != false){
					$entry_type = $datatrans->game_transaction_type == 1 ? 'debit' : 'credit';
		    		$check_bet_exist_transaction = ProviderHelper::findGameExt($datatrans->round_id, 1,'round_id');
		    		if($check_bet_exist_transaction != 'false'){
		    			$bet_item = [
							"game_trans_id" => $check_bet_exist_transaction->game_trans_id,
							"game_trans_ext_id"  => $check_bet_exist_transaction->game_trans_ext_id,
							"amount" => $check_bet_exist_transaction->amount,
							"game_transaction_type" => $check_bet_exist_transaction->game_transaction_type,
						];
						$is_bet[] = $bet_item;
						$transaction_to_refund[] = $bet_item;
		    		}
		    	    $check_win_exist_transaction = ProviderHelper::findGameExt($datatrans->round_id, 2,'round_id');
		    		if($check_win_exist_transaction != 'false'){
		    			$win_item = [
							"game_trans_id" => $check_win_exist_transaction->game_trans_id,
							"game_trans_ext_id"  => $check_win_exist_transaction->game_trans_ext_id,
							"amount" => $check_win_exist_transaction->amount,
							"game_transaction_type" => $check_win_exist_transaction->game_transaction_type,
						];
						$is_win[] = $win_item;
						$transaction_to_refund[] = $win_item;
		    		}
				}
			}else{  // RoundID, if round ID filter Player ID, and round ID
				if($datatrans != false){
					$entry_type = $datatrans->game_transaction_type == 1 ? 'debit' : 'credit';
		    		$check_bet_exist_transaction = ProviderHelper::findGameExt($datatrans->round_id, 1,'round_id');
		    		if($check_bet_exist_transaction != 'false'){
		    			$bet_item = [
							"game_trans_id" => $check_bet_exist_transaction->game_trans_id,
							"game_trans_ext_id"  => $check_bet_exist_transaction->game_trans_ext_id,
							"amount" => $check_bet_exist_transaction->amount,
							"game_transaction_type" => $check_bet_exist_transaction->game_transaction_type,
						];
						$is_bet[] = $bet_item;
						$transaction_to_refund[] = $bet_item;
		    		}
		    	    $check_win_exist_transaction = ProviderHelper::findGameExt($datatrans->round_id, 2,'round_id');
		    		if($check_win_exist_transaction != 'false'){
		    			$win_item = [
							"game_trans_id" => $check_win_exist_transaction->game_trans_id,
							"game_trans_ext_id"  => $check_win_exist_transaction->game_trans_ext_id,
							"amount" => $check_win_exist_transaction->amount,
							"game_transaction_type" => $check_win_exist_transaction->game_transaction_type,
						];
						$is_win[] = $win_item;
						$transaction_to_refund[] = $win_item;
		    		}
				}
			}
			# FILTER IF THE ITEMS SHOULD BE PROCESSED
			if($key['holdEarlyRefund'] == false){
				if($datatrans == false){
					$items_array[] = [
						 "info" => $key['info'],
						 "errorCode" => 7, // this transaction is not found
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
				    ]; 
					continue;
				}
				if($datatrans != false){
					$refund_check = $this->gameTransactionEXTLog('provider_trans_id', $key['txId'], 3);
		 		    if($refund_check != false){
		 		    	if($refund_check->transaction_detail == '"PROCESSING"'){
		 		    		$items_array[] = [
								 "info" => $key['info'],
								 "errorCode" => 8, // this transaction is already in exist
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
						    ]; 
							continue;
		 		    	}else{
							$items_array[] = [
								 "info" => $key['info'],
								 "errorCode" => 14, // this transaction already rolleback
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
						    ]; 
							continue;
						}
		 		    } 
				}
			}else{ // hold early refundtrue
				$refund_check = $this->gameTransactionEXTLog('provider_trans_id', $key['txId'], 3);
	 		    if($refund_check != false){
	 		    	if($refund_check->transaction_detail == '"PROCESSING"' || $refund_check->transaction_detail == '"FAILED"'){
	 		    		$items_array[] = [
							 "info" => $key['info'],
							 "errorCode" => 8, // this transaction is already in exist
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
					    ]; 
						continue;
	 		    	}else{
						$items_array[] = [
							 "info" => $key['info'],
							 "errorCode" => 14, // this transaction already rolleback
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
					    ]; 
						continue;
					}
				}
			}
			# IF BET IS ALREADY WON WHEN REFUNDROUND IS FALSE
			if(count($transaction_to_refund) > 0){
				if($key['refundRound'] == false){
					if($entry_type == 'debit'){
						if(count($is_win) > 0){ // This Bet Has Already Wonned
							$items_array[] = [
								 "info" => $key['info'],
								 "errorCode" => 20,
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
						    ]; 
							continue;
						}
					}
				}
			}
			$client_details = ProviderHelper::getClientDetails('player_id', $player_id);
 		    if($client_details == null){
 		    	$items_array[] = [
					 "info" => $key['info'],
					 "errorCode" => 4, 
					 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
			    ];  
				continue;
 		    }
 		    if($key['operationType'] != 3){
 		    	$items_array[] = [
					 "info" => $key['info'],
					 "errorCode" => 19, 
					 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
			    ];  
				continue;
 		    }
			$client_player = ProviderHelper::playerDetailsCall($client_details->player_token);
			if($client_player == 'false'){ // client cannot be reached! http errors etc!
				$items_array[] = [
					 "info" => $key['info'],
					 "errorCode" =>999, 
					 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
			    ];
				continue;
			}

			if($datatrans != false){ // TRANSACTION IS FOUND
					$game_details = Helper::getInfoPlayerGameRound($client_details->player_token);
					$round_id = $transaction_identifier;
					$bet_amount = $datatrans->bet_amount;
					$entry_id = $datatrans->entry_id;
					$win = 4; //3 draw, 4 refund, 1 lost win is refunded
					$pay_amount = 0;
  				    $income = 0;
			 		
					if($key['refundRound'] == false){ // 1 Transaction to refund
						if($entry_type == 'credit'){ 
							$is_win_amount = count($is_win) > 0 ? $is_win[0]['amount'] : 0;
						    $amount = $is_win_amount;
						    $transactiontype = 'debit';
						    if(abs($client_player->playerdetailsresponse->balance) < abs($amount)){
								$items_array[] = [
							 	 	"info" => $key['info'],
								 	"errorCode" => 6, // Player Low Balance!
									"metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
				        	    ]; 
			        	   	    continue;
				        	}
				        	$win = 1; //3 draw, 4 refund, 1 lost win is refunded
	  						$entry_id = 2;
	  						$pay_amount = 0;
	  						$income = $bet_amount - $pay_amount;
						}else{ 
							$is_bet_amount = count($is_bet) > 0 ? $is_bet[0]['amount'] : 0;
						    $amount = $is_bet_amount;
						    $transactiontype = 'credit';
						}
					
					}else{
						$is_bet_amount = count($is_bet) > 0 ? $is_bet[0]['amount'] : 0;
					    $is_win_amount = count($is_win) > 0 ? $is_win[0]['amount'] : 0;
					    $amount = abs($is_bet_amount)-abs($is_win_amount);
						if($amount < 0){
		  					$transactiontype = 'debit'; // overwrite the transaction type
		  					if(abs($client_player->playerdetailsresponse->balance) < abs($amount)){
								$items_array[] = [
							 	 	"info" => $key['info'],
								 	"errorCode" => 6, // Player Low Balance!
									"metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
				        	    ]; 
			        	   	    continue;
				        	}
		  				}else{
		  					$transactiontype = 'credit'; // overwrite the transaction type
		  				}
					}

					// $pay_amount = $transactiontype == 'credit' ? $datatrans->pay_amount + $amount : $datatrans->pay_amount - $amount;
  					// $income = $bet_amount - $pay_amount;
  				    
	  				
	  				$game_transextension = ProviderHelper::createGameTransExtV2($datatrans->game_trans_id, $key['txId'], $round_id, abs($amount), 3);
								 	
					try {
					$client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$datatrans->game_trans_id,$transactiontype,true);
					 Helper::saveLog('RSG refund CRID = '.$datatrans->game_trans_id, $this->provider_db_id, file_get_contents("php://input"), $client_response);
					} catch (\Exception $e) {
					$items_array[] = array(
						 "info" => $key['info'], 
						 "errorCode" => 999, 
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
						);
					ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $json_data, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
					Helper::saveLog('RSG refund - FATAL ERROR', $this->provider_db_id, json_encode($items_array), Helper::datesent());
						continue;
					}

					if(isset($client_response->fundtransferresponse->status->code) 
					             && $client_response->fundtransferresponse->status->code == "200"){

				 		    # Provider Transaction Logger
				 		    $general_details['client']['beforebalance'] = $this->formatBalance($client_player->playerdetailsresponse->balance);
							$general_details['provider']['operationType'] = $key['operationType'];
							$general_details['provider']['currencyId'] = $key['currencyId'];
							$general_details['provider']['txCreationDate'] = $json_data['timestamp'];
							$general_details['provider']['txId'] = $key['txId'];
							# Provider Transaction Logger

							$general_details['provider']['amount'] = $amount; // overall amount
							$general_details['client']['afterbalance'] = $this->formatBalance($client_response->fundtransferresponse->balance);
							$general_details['aggregator']['externalTxId'] = $game_transextension;
							$general_details['aggregator']['transaction_status'] = 'SUCCESS';

							$updateTheBet = $this->updateBetToWin($datatrans->round_id, $pay_amount, $income, $win, $entry_id);
							$items_array[] = [
			        	    	 "externalTxId" => $game_transextension, // MW Game Transaction Id
								 "balance" => $this->formatBalance($client_response->fundtransferresponse->balance),
								 "info" => $key['info'], // Info from RSG, MW Should Return it back!
								 "errorCode" => 1,
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
			        	    ];
							ProviderHelper::updatecreateGameTransExt($game_transextension,  $json_data, $items_array, $client_response->requestoclient, $client_response, 'SUCCESS', $general_details);
							continue;

					}elseif(isset($client_response->fundtransferresponse->status->code) 
					            && $client_response->fundtransferresponse->status->code == "402"){

						$general_details['provider']['amount'] = $amount; // overall amount
						$general_details['client']['afterbalance'] = $this->formatBalance($client_response->fundtransferresponse->balance);
						$general_details['aggregator']['externalTxId'] = $game_transextension;
						$general_details['aggregator']['transaction_status'] = 'SUCCESS';

						$items_array[] = [
					 	 	"info" => $key['info'],
						 	"errorCode" => 6, // Player Low Balance!
							"metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
		        	    ]; 
		        	    ProviderHelper::updatecreateGameTransExt($game_transextension,  $json_data, $items_array, $client_response->requestoclient, $client_response, 'SUCCESS', $general_details);
		        	    continue;
					}else{ // Unknown Response Code
						$items_array[] = [
					 	 	"info" => $key['info'],
						 	"errorCode" => 999, // Player Low Balance!
							"metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
		        	    ]; 
		        	    ProviderHelper::updatecreateGameTransExt($game_transextension,  $json_data, $items_array, $client_response->requestoclient, $client_response, 'FAILED', $general_details);
		        	    continue;
					}   

			}else{
				if($key['holdEarlyRefund'] == true){
					// wait for the corresponding bet and refund it its impossible to have refund win if the bet has no winning yet
			   		$game_transextension = ProviderHelper::createGameTransExtV2(999999999, $key['txId'], $transaction_identifier, 0, 3);
			   		$general_details['client']['beforebalance'] = $this->formatBalance($client_player->playerdetailsresponse->balance);
					$general_details['provider']['operationType'] = $key['operationType'];
					$general_details['provider']['currencyId'] = $key['currencyId'];
					$general_details['provider']['amount'] = 0;
					$general_details['provider']['txCreationDate'] = $json_data['timestamp'];
					$general_details['provider']['txId'] = $key['txId'];
					$general_details['client']['afterbalance'] = $this->formatBalance($client_player->playerdetailsresponse->balance);
					$general_details['aggregator']['externalTxId'] = $game_transextension;
					$general_details['aggregator']['transaction_status'] = 'SUCCESS';
			   		$general_details['provider']['description'] = 'EARLYREFUND IF BET HAS NOT ARRIVED OR WILL NOT ARRIVED THIS LOG IS NO VALUE';
			   		$items_array[] = [
			   			"externalTxId" => $game_transextension,
			   			"balance" => $this->formatBalance($client_player->playerdetailsresponse->balance),
						"info" => $key['info'],
						"errorCode" => 1, 
						"metadata" => isset($key['metadata']) ? $key['metadata'] : ''
				    ];
				    ProviderHelper::updatecreateGameTransExt($game_transextension,  'EARLYREFUND', 'EARLYREFUND', 'EARLYREFUND','EARLYREFUND', 'PROCESSING', $general_details);

				    continue;
				}else{
					$items_array[] = [
						 "info" => $key['info'],
						 "errorCode" => 7, // this transaction is not found
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
				    ]; 
					continue;
				}
			}

		} # END FOREARCH

		$response = array(
			 "timestamp" => date('YmdHisms'),
		     "signature" => $this->createSignature(date('YmdHisms')),
			 "errorCode" => 1,
			 "items" => $items_array,
		);	
		Helper::saveLog('RSG refund - SUCCESS', $this->provider_db_id, file_get_contents("php://input"), $response);
		return $response;
	}

	/**
	 * Amend Win
	 */
	public function amend(Request $request){
		Helper::saveLog('RSG amend - EH', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$json_data = json_decode(file_get_contents("php://input"), true);
		if($json_data == null){
			return $this->noBody();
		}
		if (!$this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])){
			return $this->authError();
		}
		if($json_data['operatorId'] != $this->operator_id){
			return $this->wrongOperatorID();
		}
		# Missing Parameters
		if(!isset($json_data['providerId']) || !isset($json_data['allOrNone']) || !isset($json_data['signature']) || !isset($json_data['timestamp']) || !isset($json_data['operatorId']) || !isset($json_data['items'])){
			$response = array(
					 "timestamp" => date('YmdHisms'),
				     "signature" => $this->createSignature(date('YmdHisms')),
					 "errorCode" => 17,
					 "items" => $items_array,
   			);	
			return $response;
		}
		$items_array = array(); // ITEMS INFO
		# All or none is true
		$error_encounter = 0;
	    $datatrans_status = true;
	    $duplicate_txid_request = array();
	    $all_bets_amount = array();
		$isset_allbets_amount = 0;
	    $global_error = 1;
		foreach ($json_data['items'] as $key) { // FOREACH CHECK
		 		if($json_data['allOrNone'] == 'true'){ // IF ANY ITEM FAILED DONT PROCESS IT
		 				# Missing item param
						if(!isset($key['playerId']) || !isset($key['gameId']) || !isset($key['roundId']) || !isset($key['txId']) || !isset($key['winTxId']) || !isset($key['winOperationType']) || !isset($key['currencyId']) || !isset($key['info']) || !isset($key['amendAmount'])){
							$items_array[] = [
								 "info" => isset($key['info']) ? $key['info'] : '', // Info from RSG, MW Should Return it back!
								 "errorCode" => 17, //The playerId was not found
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
			        	    ];  
							$global_error = $global_error == 1 ? 17 : $global_error;
							$error_encounter = 1;
							continue;
						}
						if($isset_allbets_amount == 0){ # Calculate all total bets
							foreach ($json_data['items'] as $key) {
								array_push($duplicate_txid_request, $key['txId']);
								array_push($all_bets_amount, $key['amendAmount']);
							}
							$isset_allbets_amount = 1;
						}
						if($this->array_has_dupes($duplicate_txid_request)){
							$items_array[] = [
								 "info" => $key['info'],
								 "errorCode" => 8, 
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
			        	    ];
			 				$global_error = $global_error == 1 ? 8 : $global_error;
							$error_encounter = 1;
			        	    continue;
						}
						$client_details = ProviderHelper::getClientDetails('player_id', $key['playerId']);
						if($client_details == null){
							$items_array[] = [
								 "info" => $key['info'], // Info from RSG, MW Should Return it back!
								 "errorCode" => 7, //The playerId was not found
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
			        	    ];  
							$global_error = $global_error == 1 ? 7 : $global_error;
							$error_encounter= 1;
							continue;
						}
						$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $key["gameId"]);
						if($game_details == null){ // Game not found
							$items_array[] = [
								 "info" => isset($key['info']) ? $key['info'] : '', // Info from RSG, MW Should Return it back!
								 "errorCode" => 11, // transaction already refunded
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
			        	    ];
							$global_error = $global_error == 1 ? 11 : $global_error;
							$error_encounter= 1;
							continue;
						}
    					// $checkLog = $this->checkRSGExtLog($key['txId'],$key['roundId'],2);
    					if(isset($key['winTxId'])){
							$checkLog = ProviderHelper::findGameExt($key['winTxId'], 1, 'transaction_id'); // isbet?
							if($checkLog == 'false'){
								$checkLog = ProviderHelper::findGameExt($key['winTxId'], 2, 'transaction_id'); // iswin?
								if($checkLog == 'false'){
									$items_array[] = [
										 "info" => $key['info'], // Info from RSG, MW Should Return it back!
										 "errorCode" => 7, // Win Transaction not found
										 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
					        	    ]; 
					        	    $global_error = $global_error == 1 ? 7 : $global_error;
									$error_encounter= 1;
									continue;
								}else{
									$db_operation_type = 2; // one for win
								}
							}else{
								// $db_operation_type = $checkLog->game_transaction_type;
								$db_operation_type = 1; // one for bet
							}
    					}

						if($checkLog == 'false'){
							$items_array[] = [
								 "info" => $key['info'], // Info from RSG, MW Should Return it back!
								 "errorCode" => 7, // Win Transaction not found
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
			        	    ]; 
			        	    $global_error = $global_error == 1 ? 7 : $global_error;
							$error_encounter= 1;
							continue;
						}
						if($checkLog != 'false'){
							if($checkLog->round_id != $key['roundId']){
								$items_array[] = [
									 "info" => $key['info'], // Info from RSG, MW Should Return it back!
									 "errorCode" => 7, // round is unknown
									 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
				        	    ]; 
				        	    $global_error = $global_error == 1 ? 7 : $global_error;
							    $error_encounter= 1;
								continue;
							}
							if($db_operation_type != $key['winOperationType']){
								$items_array[] = [
									 "info" => $key['info'], // Info from RSG, MW Should Return it back!
									 "errorCode" => 18, // Unknow Operation Type for win amend
									 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
				        	    ]; 
				        	    $global_error = $global_error == 1 ? 18 : $global_error;
								$error_encounter= 1;
								continue;
							}
							if($key['amendAmount'] > $checkLog->amount){
								$items_array[] = [
									 "info" => $key['info'], // Info from RSG, MW Should Return it back!
									 "errorCode" => 18, // amount is too big for the exact win amount
									 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
				        	    ]; 
				        	    $global_error = $global_error == 1 ? 18 : $global_error;
							    $error_encounter= 1;
								continue;
							}
						}
						$is_refunded = ProviderHelper::findGameExt($key['txId'], 3, 'transaction_id');
						if($is_refunded != 'false'){
							$items_array[] = [
								 "info" => $key['info'], // Info from RSG, MW Should Return it back!
								 "errorCode" => 8, // transaction already refunded
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
			        	    ]; 
			        	    $global_error = $global_error == 1 ? 8 : $global_error;
							$error_encounter= 1;
							continue;
						}
						if($key['currencyId'] != $client_details->default_currency){
							$items_array[] = [
								 "info" => $key['info'], // Info from RSG, MW Should Return it back!
								 "errorCode" => 16, // Currency code dont match!
								 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
			        	    ];   	
	        	 	 	    $global_error = $global_error == 1 ? 16 : $global_error;
							$error_encounter= 1;
							continue;
						} 

		 		}
	 	}// END FOREACH CHECK
		if($error_encounter != 0){ // ELSE PROCEED TO CLIENT TRANSFERING
			$response = array(
					 "timestamp" => date('YmdHisms'),
				     "signature" => $this->createSignature(date('YmdHisms')),
					 "errorCode" => $global_error,
					 "items" => $items_array,
   			);	
			return $response;
		}

		$items_array = array(); // ITEMS INFO
		$duplicate_txid_request = array();
	    $all_bets_amount = array();
		$isset_allbets_amount = 0;
		// ALL GOOD PROCESS IT
		foreach ($json_data['items'] as $key) {
			$general_details = ["aggregator" => [],"provider" => [],"client" => []];
			if(!isset($key['playerId']) || !isset($key['gameId']) || !isset($key['roundId']) || !isset($key['txId']) || !isset($key['winTxId']) || !isset($key['winOperationType']) || !isset($key['currencyId']) || !isset($key['info']) || !isset($key['amendAmount'])){
				$items_array[] = [
					 "info" => isset($key['info']) ? $key['info'] : '', // Info from RSG, MW Should Return it back!
					 "errorCode" => 17, //The playerId was not found
					 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
        	    ];  
				continue;
			}
			$client_details = ProviderHelper::getClientDetails('player_id', $key['playerId']);
			if($client_details == null){
				$items_array[] = [
					 "info" => $key['info'], // Info from RSG, MW Should Return it back!
					 "errorCode" => 7, //The playerId was not found
					 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
        	    ];  
				continue;
			}
			$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $key["gameId"]);
			if($game_details == null){ // Game not found
				$items_array[] = [
					 "info" => isset($key['info']) ? $key['info'] : '', // Info from RSG, MW Should Return it back!
					 "errorCode" => 11, // transaction already refunded
					 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
        	    ];
				continue;
			}
			if($isset_allbets_amount == 0){ # Calculate all total bets
				foreach ($json_data['items'] as $key) {
					array_push($duplicate_txid_request, $key['txId']);
					array_push($all_bets_amount, $key['amendAmount']);
				}
				$isset_allbets_amount = 1;
			}
			if($this->array_has_dupes($duplicate_txid_request)){
				$items_array[] = [
					 "info" => $key['info'],
					 "errorCode" => 8, 
					 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
        	    ];
 				$global_error = $global_error == 1 ? 8 : $global_error;
				$error_encounter = 1;
        	    continue;
			}
			if(isset($key['winTxId'])){
				$checkLog = ProviderHelper::findGameExt($key['winTxId'], 1, 'transaction_id'); // isbet?
				if($checkLog == 'false'){
					$checkLog = ProviderHelper::findGameExt($key['winTxId'], 2, 'transaction_id'); // iswin?
					if($checkLog == 'false'){
						$items_array[] = [
							 "info" => $key['info'], // Info from RSG, MW Should Return it back!
							 "errorCode" => 7, // Win Transaction not found
							 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
		        	    ]; 
		        	    $global_error = $global_error == 1 ? 7 : $global_error;
						$error_encounter= 1;
						continue;
					}else{
						$db_operation_type = 2; // one for win
					}
				}else{
					// $db_operation_type = $checkLog->game_transaction_type;
					$db_operation_type = 1; // one for bet
				}
			}
			if($checkLog != 'false'){
				if($checkLog->round_id != $key['roundId']){
					$items_array[] = [
						 "info" => $key['info'], // Info from RSG, MW Should Return it back!
						 "errorCode" => 7, // round is unknown
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
	        	    ]; 
					continue;
				}
				if($db_operation_type != $key['winOperationType']){
					$items_array[] = [
						 "info" => $key['info'], // Info from RSG, MW Should Return it back!
						 "errorCode" => 18, // Unknow Operation Type for win amend
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
	        	    ]; 
	        	    $global_error = $global_error == 1 ? 18 : $global_error;
					$error_encounter= 1;
					continue;
				}
				if($key['amendAmount'] > $checkLog->amount){
					$items_array[] = [
						 "info" => $key['info'], // Info from RSG, MW Should Return it back!
						 "errorCode" => 18, // amount is too big for the exact win amount
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
	        	    ]; 
					continue;
				}
			}
			if($key['currencyId'] != $client_details->default_currency){
				$items_array[] = [
					 "info" => $key['info'], // Info from RSG, MW Should Return it back!
					 "errorCode" => 16, // Currency code dont match!
					 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
        	    ];   	
				continue;
			} 
			$is_refunded = ProviderHelper::findGameExt($key['txId'], 3, 'transaction_id');
			if($is_refunded != 'false'){
				$items_array[] = [
					 "info" => $key['info'], // Info from RSG, MW Should Return it back!
					 "errorCode" => 8, // transaction already refunded
					 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
        	    ]; 
				continue;
			}
			$client_response = ProviderHelper::playerDetailsCall($client_details->player_token);
			if($client_response == 'false'){
				$items_array[] = [
					 "info" => $key['info'], // Info from RSG, MW Should Return it back!
					 "errorCode" => 999, // transaction already refunded
					 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' // Optional but must be here!
        	    ]; 
				continue;
			}
			$general_details['client']['beforebalance'] = $this->formatBalance($client_response->playerdetailsresponse->balance);
			$game_details = Helper::getInfoPlayerGameRound($client_details->player_token);
			// $gametransaction_details = $this->findTransactionRefund($key['winTxId'], 'provider_id');
			// $win_exist_details = ProviderHelper::findGameExt($key['winTxId'], 2, 'transaction_id');
			$gametransaction_details = ProviderHelper::findGameTransaction($checkLog->game_trans_id,'game_transaction');
			// 37 Amend correction withdrawing money
			// 38 Amend  correction depositing money.
			if(isset($key['operationType'])){
				if($key['operationType'] == 37){
					$transaction_type = 'debit'; 
				}elseif($key['operationType'] == 38){
					$transaction_type = 'credit'; 
				}else{
					$items_array[] = [
						 "info" => $key['info'], 
						 "errorCode" => 19, 
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : '' 
	        	    ]; 
					continue;
				}
			}
	 		$amount = $key['amendAmount'];

 		    $token_id = $client_details->token_id;
	 	    if(isset($key['roundId'])){
	 	    	$round_id = $key['roundId'];
	 	    }else{
	 	    	$round_id = 'RSGNOROUND';
	 	    }
	 	    if(isset($key['txId'])){
	 	    	$provider_trans_id = $key['txId'];
	 	    }else{
	 	    	$provider_trans_id = 'RSGNOPROVIDERTXID';
	 	    }
	 	    $round_id = $key['roundId'];

			if($key['winOperationType'] == 1){ // This is for bet
					$win = $gametransaction_details->win; //win
					$entry_id = $gametransaction_details->entry_id; // BET
					$pay_amount = $gametransaction_details->pay_amount;
					$income = $gametransaction_details->income;
					if($key['operationType'] == 37){ // CREADIT/ADD
						$the_transaction_bet = $gametransaction_details->bet_amount + $amount;
					}else{ // DEBIT/SUBTRACT
						$the_transaction_bet = $gametransaction_details->bet_amount - $amount;
					}
			}else{
				if($key['operationType'] == 37){ // CREADIT/ADD
					$pay_amount = $gametransaction_details->pay_amount + $amount;
					$income = $gametransaction_details->bet_amount - $pay_amount;
				}else{ // DEBIT/SUBTRACT
					$pay_amount = $gametransaction_details->pay_amount - $amount;
					$income = $gametransaction_details->bet_amount - $pay_amount;
				}
				if($pay_amount > $gametransaction_details->bet_amount){
					$win = 4; //lost
					$entry_id = 1; //lost
				}else{
					$win = 4; //win
					$entry_id = 2; //win
				}
			}

 			$game_transextension = ProviderHelper::createGameTransExtV2($gametransaction_details->game_trans_id,$provider_trans_id, $round_id, abs($amount), 3);

	 		try {
			 $client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$gametransaction_details->game_trans_id,$transaction_type,true);
			 Helper::saveLog('RSG amend CRID = '.$gametransaction_details->game_trans_id, $this->provider_db_id, file_get_contents("php://input"), $client_response);
			} catch (\Exception $e) {
			$items_array[] = array(
				 "info" => $key['info'], 
				 "errorCode" => 999, 
				 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
			);
			ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $json_data, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
			Helper::saveLog('RSG win - FATAL ERROR', $this->provider_db_id, json_encode($items_array), Helper::datesent());
				continue;
			}

			if(isset($client_response->fundtransferresponse->status->code) 
			             && $client_response->fundtransferresponse->status->code == "200"){

				$general_details['provider']['operationType'] = $key['operationType'];
				$general_details['provider']['currencyId'] = $key['currencyId'];
				$general_details['provider']['amount'] = abs($amount);
				$general_details['provider']['txCreationDate'] = $json_data['timestamp'];
				$general_details['provider']['txId'] = $key['txId'];
				$general_details['client']['afterbalance'] = $this->formatBalance($client_response->fundtransferresponse->balance);
				$general_details['aggregator']['externalTxId'] = $game_transextension;
				$general_details['aggregator']['transaction_status'] = 'SUCCESS';

				if($key['winOperationType'] == 1){
					$updateTheBet = $this->updateBetToWin($gametransaction_details->round_id, $pay_amount, $income, $win, $entry_id, 2, $the_transaction_bet);
				}else{
					$updateTheBet = $this->updateBetToWin($gametransaction_details->round_id, $pay_amount, $income, $win, $entry_id);
				}

				$items_array[] = [
        	    	 "externalTxId" => $game_transextension, // MW Game Transaction Id
					 "balance" => $this->formatBalance($client_response->fundtransferresponse->balance),
					 "info" => $key['info'], // Info from RSG, MW Should Return it back!
					 "errorCode" => 1,
					 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''// Optional but must be here!
        	    ];
				ProviderHelper::updatecreateGameTransExt($game_transextension,  $json_data, $items_array, $client_response->requestoclient, $client_response, 'SUCCESS', $general_details);

			}elseif(isset($client_response->fundtransferresponse->status->code) 
			            && $client_response->fundtransferresponse->status->code == "402"){

				$general_details['provider']['operationType'] = $key['operationType'];
				$general_details['provider']['currencyId'] = $key['currencyId'];
				$general_details['provider']['amount'] = abs($amount);
				$general_details['provider']['txCreationDate'] = $json_data['timestamp'];
				$general_details['provider']['txId'] = $key['txId'];

				$general_details['client']['afterbalance'] = $this->formatBalance($client_response->fundtransferresponse->balance);
				$general_details['aggregator']['externalTxId'] = $game_transextension;
				$general_details['aggregator']['transaction_status'] = 'SUCCESS';

				$items_array[] = array(
						 "info" => $key['info'], 
						 "errorCode" => 6, 
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
		   		);
		   		ProviderHelper::updatecreateGameTransExt($game_transextension,  $json_data, $items_array, $client_response->requestoclient, $client_response, 'SUCCESS', $general_details);
			}else{ // Unknown Response Code
				$items_array[] = array(
						 "info" => $key['info'], 
						 "errorCode" => 999, 
						 "metadata" => isset($key['metadata']) ? $key['metadata'] : ''
		   		);
				ProviderHelper::updatecreateGameTransExt($game_transextension,  'FAILED', 'FAILED', $client_response->requestoclient, $client_response, 'FAILED', 'FAILED');
			}    
		} // END FOREACH
		$response = array(
					 "timestamp" => date('YmdHisms'),
				     "signature" => $this->createSignature(date('YmdHisms')),
					 "errorCode" => 1,
					 "items" => $items_array,
		);	
		Helper::saveLog('RSG amend - SUCCESS', $this->provider_db_id, file_get_contents("php://input"), $response);
		return $response;
	}

	public function PromoWin(){
		Helper::saveLog('RSG amend - EH', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$json_data = json_decode(file_get_contents("php://input"), true);
		$general_details = ["aggregator" => [], "provider" => [], "client" => []];
		$response = array(
				 "timestamp" => date('YmdHisms'),
			     "signature" => $this->createSignature(date('YmdHisms')),
				 "errorCode" => 999,
				 "message" => 'TIGER GAMES DONT SUPPORT PROMOWIN AND BUNOS YET!',
				 "info" => $json_data['info'],
		);	
		return $response;
		if($json_data == null){
			return $this->noBody();
		}
		if (!$this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])){
			return $this->authError();
		}
		if($json_data['operatorId'] != $this->operator_id){
			return $this->wrongOperatorID();
		}
		# Missing Parameters
		if(!isset($json_data['providerId']) || !isset($json_data['operatorId']) || !isset($json_data['signature']) || !isset($json_data['timestamp']) || !isset($json_data['playerId']) || !isset($json_data['campaignId']) || !isset($json_data['campaignType']) || !isset($json_data['amount']) || !isset($json_data['currencyId']) || !isset($json_data['txId']) || !isset($json_data['info'])){
			$response = array(
					 "timestamp" => date('YmdHisms'),
				     "signature" => $this->createSignature(date('YmdHisms')),
					 "errorCode" => 17,
					 "items" => $items_array,
   			);	
			return $response;
		}
		$client_details = ProviderHelper::getClientDetails('player_id', $json_data['playerId']);
		if($client_details == null){
		$response = [
				 "info" => $json_data['info'], // Info from RSG, MW Should Return it back!
				 "errorCode" => 4, //The playerId was not found
				 "metadata" => isset($json_data['metadata']) ? $json_data['metadata'] : '' // Optional but must be here!
    	    ];  
			return $response;
		}
		if($json_data['currencyId'] != $client_details->default_currency){
		$response = [
				 "info" => $json_data['info'], // Info from RSG, MW Should Return it back!
				 "errorCode" => 16, // Currency code dont match!
				 "metadata" => isset($json_data['metadata']) ? $json_data['metadata'] : '' // Optional but must be here!
    	    ];   	
			return $response;
		} 
		$is_refunded = ProviderHelper::findGameExt($json_data['txId'], 2, 'transaction_id');
		if($is_refunded != 'false'){
			$response = [
				 "info" => $json_data['info'], // Info from RSG, MW Should Return it back!
				 "errorCode" => 8, // transaction already refunded
				 "metadata" => isset($json_data['metadata']) ? $json_data['metadata'] : '' // Optional but must be here!
    	    ]; 
			return $response;
		}
		$client_response = ProviderHelper::playerDetailsCall($client_details->player_token);
		if($client_response == 'false'){
			$response = [
				 "info" => $json_data['info'], // Info from RSG, MW Should Return it back!
				 "errorCode" => 999, // transaction already refunded
				 "metadata" => isset($json_data['metadata']) ? $json_data['metadata'] : '' // Optional but must be here!
    	    ]; 
			return $response;
		}
		$general_details['client']['beforebalance'] = $this->formatBalance($client_response->playerdetailsresponse->balance);

		$game_details = Helper::getInfoPlayerGameRound($client_details->player_token);

		$token_id = $client_details->token_id;
		$bet_amount = 0;
		$promo_amount = $json_data['amount'];
		$income = 0;
		$provider_trans_id = $json_data['txId'];
		$round_id = $json_data['txId'];
		$method = 0;
		$win_or_lost = 1;
		$payout_reason = 'PROMO WIN';

		$game_trans = ProviderHelper::createGameTransaction($token_id, $game_details->game_id, $bet_amount, $bet_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);

		$game_transextension = ProviderHelper::createGameTransExtV2($game_trans,$provider_trans_id , $provider_trans_id , $bet_amount, 1);

		try {
			$client_response = ClientRequestHelper::fundTransfer($client_details,abs($bet_amount),$game_details->game_code,$game_details->game_name,$game_transextension,$game_trans,'debit');
			Helper::saveLog('RSG PromoWin CRID = '.$game_trans, $this->provider_db_id, file_get_contents("php://input"), $client_response);
		} catch (\Exception $e) {
			$response = [
				 "info" => $json_data['info'], // Info from RSG, MW Should Return it back!
				 "errorCode" => 999, // transaction already refunded
				 "info"=> $json_data['info'],
				 "metadata" => isset($json_data['metadata']) ? $json_data['metadata'] : '' // Optional but must be here!
		    ]; 
			ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $json_data, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
			Helper::saveLog('RSG win - FATAL ERROR', $this->provider_db_id, json_encode($response), Helper::datesent());
			return $response;
		}

		if(isset($client_response->fundtransferresponse->status->code) 
             && $client_response->fundtransferresponse->status->code == "200"){

			$game_transextension2 = ProviderHelper::createGameTransExtV2($game_trans,$provider_trans_id, $provider_trans_id, abs($promo_amount), 2);
			$client_response2 = ClientRequestHelper::fundTransfer($client_details,abs($promo_amount),$game_details->game_code,$game_details->game_name,$game_transextension2,$game_trans,'credit');

			$general_details['provider']['operationType'] = $this->getOperationcampaignType($json_data['campaignType']);
			$general_details['provider']['currencyId'] = $json_data['currencyId'];
			$general_details['provider']['amount'] = abs($promo_amount);
			$general_details['provider']['txCreationDate'] = $json_data['timestamp'];
			$general_details['provider']['txId'] = $json_data['txId'];

			$general_details['client']['afterbalance'] = $this->formatBalance($client_response2->fundtransferresponse->balance);
			$general_details['aggregator']['externalTxId'] = $game_transextension2;
			$general_details['aggregator']['transaction_status'] = 'SUCCESS';

			$response = [
				 "timestamp"=>"202007092113371560",
				 "signature"=>"4712bf92ffc2917d15a2f5a273e39f0116667419aa4b6ac0b3baaf26fa3c4d20",
				 "operationType"=> $this->getOperationcampaignType($json_data['campaignType']), // win tournament = 35, bunos win = 5, 
				 "txCreationDate"=> $json_data['timestamp'],
				 "externalTxId"=> $game_transextension2,
				 "currencyId"=> $client_details->default_currency,
				 "balance"=> $this->formatBalance($client_response2->fundtransferresponse->balance),
				 "bonusBalance"=> 0, // Tiger games dont have bunos wallet yet!
				 "info"=> $json_data['info'],
				 "errorCode"=> 1,
				 "metadata"=>  isset($json_data['metadata']) ? $json_data['metadata'] : ''
			];

		 	ProviderHelper::updatecreateGameTransExt($game_transextension,  $json_data, $response, $client_response->requestoclient, $client_response, 'SUCCESS', $general_details);
    	    ProviderHelper::updatecreateGameTransExt($game_transextension2,  $json_data, $response, $client_response2->requestoclient, $client_response2, 'SUCCESS', $general_details);

			$updateTheBet = $this->updateBetToWin($round_id, $promo_amount, '-'.$promo_amount, 1, 2);

		}

		return $response;
	}

	public function CheckTxStatus(){
		Helper::saveLog('RSG CheckTxStatus - EH', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
		$json_data = json_decode(file_get_contents("php://input"), true);
		if($json_data == null){
			return $this->noBody();
		}
		if($json_data['operatorId'] != $this->operator_id){
			return $this->wrongOperatorID();
		}
		if (!$this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])){
			return $this->authError();
		}
		// if no externalTxId find the provider TxId instead
		if(isset($json_data['externalTxId']) && $json_data['externalTxId'] != ''){
			$transaction_general_details = $this->findTransactionRefund($json_data['externalTxId'], 'game_trans_ext_id');
		}else{
			// return $json_data['providerTxId'];
			$transaction_general_details = $this->findTransactionRefund($json_data['providerTxId'], 'transaction_id');
		}
		// dd($transaction_general_details);
	    if($transaction_general_details != false){
	    	$general_details = json_decode($transaction_general_details->general_details);
			$txStatus = $general_details->aggregator->transaction_status == 'SUCCESS' ? true : false;
			$response = [
				"timestamp" => date('YmdHisms'),
			    "signature" => $this->createSignature(date('YmdHisms')),
				"txStatus" => $txStatus,  // true if transaction process successfully
				"operationType" => $general_details->provider->operationType, // transaction operation type
				"txCreationDate" => $general_details->provider->txCreationDate, // transaction created date
				"externalTxId" => $general_details->aggregator->externalTxId, // aggregator identifier
				"balanceBefore" => $general_details->client->beforebalance, // players before balance;
				"balanceAfter" => $general_details->client->afterbalance, // players after balance
				"currencyId" => $general_details->provider->currencyId, // players currency
				"amount" => $general_details->provider->amount, // amount of the transaction
				"errorCode" => 1 // error code
			];
	    }else{
	    	if(isset($json_data['externalTxId']) && $json_data['externalTxId'] != ''){
	    		$find_externalTxId = $this->findexternalTxId($json_data['externalTxId']);
	    		$general_details = json_decode($find_externalTxId->general_details);
				$txStatus = $general_details->aggregator->transaction_status == 'SUCCESS' ? true : false;
				$response = [
					"timestamp" => date('YmdHisms'),
				    "signature" => $this->createSignature(date('YmdHisms')),
					"txStatus" => $txStatus,  // true if transaction process successfully
					"operationType" => $general_details->provider->operationType, // transaction operation type
					"txCreationDate" => $general_details->provider->txCreationDate, // transaction created date
					"externalTxId" => $general_details->aggregator->externalTxId, // aggregator identifier
					"balanceBefore" => $general_details->client->beforebalance, // players before balance;
					"balanceAfter" => $general_details->client->afterbalance, // players after balance
					"currencyId" => $general_details->provider->currencyId, // players currency
					"amount" => $general_details->provider->amount, // amount of the transaction
					"errorCode" => 1 // error code
				];
	    	}else{
	    		$response = [
		    		"timestamp" => date('YmdHisms'),
				    "signature" => $this->createSignature(date('YmdHisms')),
				    "errorCode" => 7 // error code
		    	];
	    	}
	    }
		return $response;
	}

	/**
	 * Pull out data from the Game exstension logs!
	 * 
	 */
	public  function checkRSGExtLog($provider_transaction_id,$round_id=false,$type=false){
		if($type&&$round_id){
			$game = DB::table('game_transaction_ext')
				   ->where('provider_trans_id',$provider_transaction_id)
				   ->where('round_id',$round_id)
				   ->where('game_transaction_type',$type)
				   ->first();
		}
		else{
			$game = DB::table('game_transaction_ext')
				    ->where('provider_trans_id',$provider_transaction_id)
				    ->first();
		}
		return $game ? true :false;
	}

	/**
	 * Pull out data from the Game exstension logs!
	 * @param $trans_type = round_id/provider_trans_id
	 * @param $trans_identifier = identifier
	 * @param $type = 1 = lost, 2 = win, 3 = refund
	 * 
	 */
	public  function gameTransactionEXTLog($trans_type,$trans_identifier,$type=false){

		$game = DB::table('game_transaction_ext')
				   ->where($trans_type, $trans_identifier)
				   ->where('game_transaction_type',$type)
				   ->first();
		return $game ? $game :false;
	}

	/**
	 * Create Game Extension Logs bet/Win/Refund
	 * @param [int] $[gametransaction_id] [<ID of the game transaction>]
	 * @param [json array] $[provider_request] [<Incoming Call>]
	 * @param [json array] $[mw_request] [<Outgoing Call>]
	 * @param [json array] $[mw_response] [<Incoming Response Call>]
	 * @param [json array] $[client_response] [<Incoming Response Call>]
	 * 
	 */
	// public  function createRSGTransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response, $transaction_detail,$game_transaction_type, $amount=null, $provider_trans_id=null, $round_id=null){

	// 	$provider_request_details = array();
	// 	foreach($provider_request['items'] as $prd){
	// 		$provider_request_details = $prd;
	// 	}

	// 	// game_transaction_type = 1=bet,2=win,3=refund	
	// 	if($game_transaction_type == 1){
	// 		// $amount = $provider_request_details['bet'];
	// 		$amount = $amount;
	// 	}elseif($game_transaction_type == 2){
	// 		// $amount = $provider_request_details['winAmount'];
	// 		$amount = $amount;
	// 	}elseif($game_transaction_type == 3){
	// 		$amount = $amount;
	// 	}

	// 	$gametransactionext = array(
	// 		"game_trans_id" => $gametransaction_id,
	// 		"provider_trans_id" => $provider_trans_id,
	// 		"round_id" => $round_id,
	// 		"amount" => $amount,
	// 		"game_transaction_type"=>$game_transaction_type,
	// 		"provider_request" => json_encode($provider_request),
	// 		"mw_request"=>json_encode($mw_request),
	// 		"mw_response" =>json_encode($mw_response),
	// 		"client_response" =>json_encode($client_response),
	// 		"transaction_detail" =>json_encode($transaction_detail),
	// 	);
	// 	$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
	// 	return $gamestransaction_ext_ID;
	// }


	public  function createGameTransExtV2($game_trans_id, $provider_trans_id, $round_id, $amount, $game_type, $provider_request='FAILED', $mw_response='FAILED', $mw_request='FAILED', $client_response='FAILED', $transaction_detail='FAILED', $general_details=null){

		// $provider_request_details = array();
		// foreach($provider_request['items'] as $prd){
		// 	$provider_request_details = $prd;
		// }

		// // game_transaction_type = 1=bet,2=win,3=refund	
		// if($game_transaction_type == 1){
		// 	// $amount = $provider_request_details['bet'];
		// 	$amount = $amount;
		// }elseif($game_transaction_type == 2){
		// 	// $amount = $provider_request_details['winAmount'];
		// 	$amount = $amount;
		// }elseif($game_transaction_type == 3){
		// 	$amount = $amount;
		// }

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
			"transaction_detail" =>json_encode($transaction_detail),
		);

		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gamestransaction_ext_ID;
	}

	public function findexternalTxId($transaction_id){
		$result = DB::table('game_transaction_ext')
						->select('*')
						->where('game_trans_ext_id', '=', $transaction_id)
						->first();
		return $result ? $result : false;
	}


    /**
	 * Find The Transactions For Refund, Providers Transaction ID
	 * 
	 */
    public  function findTransactionRefund($transaction_id, $type) {

    		$transaction_db = DB::table('game_transactions as gt')
					    	// ->select('gt.*', 'gte.transaction_detail')
					    	->select('*')
						    ->leftJoin("game_transaction_ext AS gte", "gte.game_trans_id", "=", "gt.game_trans_id");
		  
		    if ($type == 'transaction_id') {
				$transaction_db->where([
			 		["gte.provider_trans_id", "=", $transaction_id],
			 	]);
			}
			if ($type == 'game_trans_ext_id') {
				$transaction_db->where([
			 		["gte.game_trans_ext_id", "=", $transaction_id],
			 	]);
			}
			if ($type == 'round_id') {
				$transaction_db->where([
			 		["gte.round_id", "=", $transaction_id],
			 	]);
			}
			if ($type == 'bet') { // TEST
				$transaction_db->where([
			 		["gt.round_id", "=", $transaction_id],
			 		["gt.payout_reason",'like', '%BET%'],
			 	]);
			}
			if ($type == 'refundbet') { // TEST
				$transaction_db->where([
			 		["gt.round_id", "=", $transaction_id],
			 	]);
			}
			$result= $transaction_db
	 			->latest('token_id')
	 			->first();

			if($result){
				return $result;
			}else{
				return false;
			}
	}

	/**
	 * Find The Transactions For Win/bet, Providers Transaction ID
	 * 
	 */
	public  function findGameTransaction($transaction_id) {
    		$transaction_db = DB::table('game_transactions as gt')
		 				   ->where('gt.provider_trans_id', $transaction_id)
		 				   ->latest()
		 				   ->first();
		   	return $transaction_db ? $transaction_db : false;
	}

	/**
	 * Find win to amend
	 * @param $roundid = roundid, $transaction_type 1=bet, 2=win
	 * 
	 */
	public  function amendWin($roundid, $transaction_type) {
    		$transaction_db = DB::table('game_transactions as gt')
					    	->select('gt.token_id' ,'gte.*', 'gte.transaction_detail')
						    ->leftJoin("game_transaction_ext AS gte", "gte.game_trans_id", "=", "gt.game_trans_id")
						    ->where("gte.game_transaction_type" , $transaction_type) // Win Type
						    ->where("gte.round_id", $roundid)
						    ->first();
			return $transaction_db ? $transaction_db : false;
	}

	/**
	 * Find bet and update to win 
	 *
	 */
	public  function updateBetToWin($round_id, $pay_amount, $income, $win, $entry_id, $type=1,$bet_amount=0) {
   	    if($type == 1){
   	    	$update = DB::table('game_transactions')
            ->where('round_id', $round_id)
            ->update(['pay_amount' => $pay_amount, 
        		  'income' => $income, 
        		  'win' => $win, 
        		  'entry_id' => $entry_id,
        		  'transaction_reason' => 'Bet updated to win'
    		]);
   	    }else{
   	    	$update = DB::table('game_transactions')
            ->where('round_id', $round_id)
            ->update(['pay_amount' => $pay_amount, 
        		  'income' => $income, 
        		  'bet_amount' => $bet_amount, 
        		  'win' => $win, 
        		  'entry_id' => $entry_id,
        		  'transaction_reason' => 'Bet updated to win'
    		]);
   	    }
		return ($update ? true : false);
	}


	public  function updateRSGRefund($game_trans_ext_id, $game_trans_id, $amount, $provider_request, $mw_response, $mw_request, $client_response,$transaction_detail,$general_details='NO DATA') {
   	    $update = DB::table('game_transaction_ext')
                ->where('game_trans_ext_id', $game_trans_ext_id)
                ->update([
                	"game_trans_id" => $game_trans_id,
                	"amount" => $amount,
					"provider_request" => json_encode($provider_request),
					"mw_response" =>json_encode($mw_response),
					"mw_request"=>json_encode($mw_request),
					"client_response" =>json_encode($client_response),
					"transaction_detail" =>json_encode($transaction_detail),
					"general_details" =>json_encode($general_details)
	    		]);
		return ($update ? true : false);
	}

	/**
	 * Find The Transactions For Win/bet, Providers Transaction ID
	 */
	public  function findPlayerGameTransaction($round_id, $player_id) {
	    $player_game = DB::table('game_transactions as gts')
		    		->select('*')
		    		->join('player_session_tokens as pt','gts.token_id','=','pt.token_id')
                    ->join('players as pl','pt.player_id','=','pl.player_id')
                    ->where('pl.player_id', $player_id)
                    ->where('gts.round_id', $round_id)
                    ->first();
        // $json_data = json_encode($player_game);
	    return $player_game;
	}

	/**
	 * Find The Transactions For Refund, Providers Transaction ID
	 * @return  [<string>]
	 * 
	 */
    public  function getOperationcampaignType($operation_type) {
  		// 1- Tournament
		// 2- Bonus Award
		// 3- Chat Game Winning
    	$operation_types = [
    		'1' => 35, // win tournament
    		'2' => 5, // bunos win
    		'3' => 65, // FreeWinAmount
    	];
    	if(array_key_exists($operation_type, $operation_types)){
    		return $operation_types[$operation_type];
    	}else{
    		return 35;
    	}
	}

	/**
	 * Find The Transactions For Refund, Providers Transaction ID
	 * @return  [<string>]
	 * 
	 */
    public  function getOperationType($operation_type) {

    	$operation_types = [
    		'1' => 'General Bet',
    		'2' => 'General Win',
    		'3' => 'Refund',
    		'4' => 'Bonus Bet',
    		'5' => 'Bonus Win',
    		'6' => 'Round Finish',
    		'7' => 'Insurance Bet',
    		'8' => 'Insurance Win',
    		'9' => 'Double Bet',
    		'10' => 'Double Win',
    		'11' => 'Split Bet',
    		'12' => 'Split Win',
    		'13' => 'Ante Bet',
    		'14' => 'Ante Win',
    		'15' => 'General Bet Behind',
    		'16' => 'General Win Behind',
    		'17' => 'Split Bet Behind',
    		'18' => 'Split Win Behind',
    		'19' => 'Double Bet Behind',
    		'20' => 'Double Win Behind',
    		'21' => 'Insurance Bet Behind',
    		'22' => 'Insurance Win Behind',
    		'23' => 'Call Bet',
    		'24' => 'Call Win',
    		'25' => 'Jackpot Bet',
    		'26' => 'Jackpot Win',
    		'27' => 'Tip',
    		'28' => 'Free Bet Win',
    		'29' => 'Free Spin Win',
    		'30' => 'Gift Bet',
    		'31' => 'Gift Win',
    		'32' => 'Deposit',
    		'33' => 'Withdraw',
    		'34' => 'Fee',
    		'35' => 'Win Tournament',
    		'36' => 'Cancel Fee',
    		'37' => 'Amend Credit',
    		'38' => 'Amend Debit',
    		'39' => 'Feature Trigger Bet',
    		'40' => 'Feature Trigger Win',
    	];
    	if(array_key_exists($operation_type, $operation_types)){
    		return $operation_types[$operation_type];
    	}else{
    		return 'Operation Type is unknown!!';
    	}

	}


	/**
	 * Helper method
	 * @return  [<Reversed Data>]
	 * 
	 */
	public function reverseDataBody($requesttosend){

		$reversed_data = $requesttosend;
	    $transaction_to_reverse = $reversed_data['fundtransferrequest']['fundinfo']['transactiontype'];
		$reversed_transaction_type =  $transaction_to_reverse == 'debit' ? 'credit' : 'debit';
		$reversed_data['fundtransferrequest']['fundinfo']['transactiontype'] = $reversed_transaction_type;
		$reversed_data['fundtransferrequest']['fundinfo']['rollback'] = 'true';

		return $reversed_data;
	}

	/**
	 * Client Player Details API Call
	 * @param $[data] [<array of data>]
	 * @param $[refreshtoken] [<Default False, True token will be requested>]
	 * 
	 */
	public function megaRollback($data_to_rollback, $items=[]){

		foreach($data_to_rollback as $rollback){
	    	try {
	    		$client = new Client([
				    'headers' => [ 
					    	'Content-Type' => 'application/json',
					    	'Authorization' => 'Bearer '.$rollback['header']
					    ]
				]);
				$datatosend = $rollback['body'];
				$guzzle_response = $client->post($rollback['url'],
				    ['body' => json_encode($datatosend)]
				);
				$client_response = json_decode($guzzle_response->getBody()->getContents());
				Helper::saveLog('RSG Rollback Succeed', $this->provider_db_id, json_encode($datatosend), $client_response);
				Helper::saveLog('RSG Rollback Succeed', $this->provider_db_id, json_encode($items), $client_response);
	    	} catch (\Exception $e) {
	    		Helper::saveLog('RSG rollback failed  response as item', $this->provider_db_id, json_encode($datatosend), json_encode($items));
	    	}
		}

	}

	/**
	 * Revert the changes made to bet/win transaction
	 * @param $[items_revert_update] [<array of data>]
	 * 
	 */
	public function rollbackChanges($items_revert_update){
		foreach($items_revert_update as $undo):
		     DB::table('game_transactions')
            ->where('game_trans_id', $undo['game_trans_id'])
            ->update(['win' => $undo['win'], 
        		  'income' => $undo['income'], 
        		  'entry_id' =>$undo['entry_id'],
        		  'pay_amount' =>$undo['pay_amount'],
        		  'transaction_reason' => 'Bet updated to win'
    		]);
		endforeach;
	}



	// public function findGameDetails($type, $provider_id, $identification) {
	// 	    $game_details = DB::table("games as g")
	// 			->leftJoin("providers as p","g.provider_id","=","p.provider_id");
				
	// 	    if ($type == 'game_code') {
	// 			$game_details->where([
	// 		 		["g.provider_id", "=", $provider_id],
	// 		 		["g.game_code",'=', $identification],
	// 		 	]);
	// 		}
	// 		$result= $game_details->first();
	//  		return $result;
	// }


	// public static function saveGame_transaction($token_id, $game_id, $bet_amount, $payout, $entry_id,  $win=0, $transaction_reason = null, $payout_reason = null , $income=null, $provider_trans_id=null, $round_id=1) {
	// 	$data = [
	// 				"token_id" => $token_id,
	// 				"game_id" => $game_id,
	// 				"round_id" => $round_id,
	// 				"bet_amount" => $bet_amount,
	// 				"provider_trans_id" => $provider_trans_id,
	// 				"pay_amount" => $payout,
	// 				"income" => $income,
	// 				"entry_id" => $entry_id,
	// 				"win" => $win,
	// 				"transaction_reason" => $transaction_reason,
	// 				"payout_reason" => $payout_reason
	// 			];
	// 	$data_saved = DB::table('game_transactions')->insertGetId($data);
	// 	return $data_saved;
	// }

}


// NOTES DONT DELETE!

// UPDATE ERROR CODES!
// Error code Error description
// 1 No errors were encountered
// 2 Session Not Found
// 3 Session Expired
// 4 Wrong Player Id
// 5 Player Is Blocked
// 6 Low Balance
// 7 Transaction Not Found
// 8 Transaction Already Exists
// 9 Provider Not Allowed For Partner
// 10 Provider's Action Not Found
// 11 Game Not Found
// 12 Wrong API Credentials
// 13 Invalid Method
// 14 Transaction Already Rolled Back
// 15 Wrong Operator Id
// 16 Wrong Currency Id
// 17 Request Parameter Missing
// 18 Invalid Data
// 19 Incorrect Operation Type
// 20 Transaction already won
// 999 General Error


// PREVIOUS AUTH CREDENTIALS
// private $apikey ="321dsfjo34j5olkdsf";
// private $access_token = "123iuysdhfb09875v9hb9pwe8f7yu439jvoiefjs";

// private $digitain_key = "rgstest";
// private $operator_id = '5FB4E74E';

// private $digitain_key = "rgstest";
// private $operator_id = 'D233911A';

// "operatorId":111,
// "timestamp":"202003092113371560",
// "signature":"ba328e6d2358f6d77804e3d342cdee06c2afeba96baada218794abfd3b0ac926",
// "token":"90dbbb443c9b4b3fbcfc59643206a123"
// $digitain_key = "P5rWDliAmIYWKq6HsIPbyx33v2pkZq7l";