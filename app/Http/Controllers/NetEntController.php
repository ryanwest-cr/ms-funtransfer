<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use GuzzleHttp\Client;
use Carbon\Carbon;
use App\Helpers\NetEntHelper;
use App\Helpers\ClientRequestHelper;
use DB;

class NetEntController extends Controller
{
	public function __construct(){
		$this->provider_db_id = 44;//mw operator sub_provider 76
	}

	public function currency(Request $request, $player){
		try {
			$playersid = explode('_', $player);
			$getClientDetails = ProviderHelper::getClientDetails('player_id',$playersid[1]);
			$response = [
				"responseCode" => 0,
				"currencyISOCode" => $getClientDetails->default_currency,
				"responseMessage" => "Success"
			];
			NetEntHelper::saveLog('NetEnt Currency', $this->provider_db_id,  json_encode($request->all()), $response);
			return json_encode($response);
		} catch(\Exception $e){
			$reponse = array (
				'responseCode' => 99,
				'responseMessage' => 'Retry exception',
				'balance' => 0,
				'serverToken' => 'NULL',
				'serverTransactionRef' => 'NULL',
				'messagesToPlayer' => 'NULL'
			);
			NetEntHelper::saveLog('NetEnt Currency Exception', $this->provider_db_id,  json_encode($request->all()), $response);
			return json_encode($response);
		}
		
	}

	public function balance(Request $request, $player){
		try{
			$playersid = explode('_', $player);
			$getClientDetails = NetEntHelper::getClientDetails('player_id',$playersid[1]);
			if ($getClientDetails->default_currency != $request["currency"]) {
				$response = array (
					'responseCode' => 2,
					'responseMessage' => 'Invalid currency',
					'balance' => 0,
					'serverToken' => NULL,
					'serverTransactionRef' => NULL,
					'messagesToPlayer' => NULL
				);
				NetEntHelper::saveLog('NetEnt Balance Invalid currency', $this->provider_db_id,  json_encode($request->all()), $response);
				return json_encode($response);
			}
			$player_details = NetEntHelper::playerDetailsCall($getClientDetails);
			$num = $player_details->playerdetailsresponse->balance;
			$balance = floatval(number_format((float)$num, 6, '.', ''));
			$response = [
				"responseCode" => 0,
				"serverToken" => $getClientDetails->player_token,
				"balance" =>  $balance,
				"responseMessage" => "Success"
			];
			NetEntHelper::saveLog('NetEnt Balance', $this->provider_db_id,  json_encode($request->all()), $response);
			return json_encode($response);
		}catch(\Exception $e){
			$response = array (
				'responseCode' => 99,
				'responseMessage' => 'Retry exception',
				'balance' => 0,
				'serverToken' => NULL,
				'serverTransactionRef' => NULL,
				'messagesToPlayer' => NULL,
			);
			NetEntHelper::saveLog('NetEnt Balance Exception', $this->provider_db_id,  json_encode($request->all()), $response);
			return json_encode($response);
		}
	}
	//bet process
	public function withdraw(Request $request, $player){
		
		$game_details = NetEntHelper::findGameDetails('game_code', $this->provider_db_id, $request["game"]); //get game details here
		$playersid = explode('_', $player);
		$client_details = ProviderHelper::getClientDetails('player_id',$playersid[1]);
		$player_details = NetEntHelper::playerDetailsCall($client_details);

		$existing_bet = NetEntHelper::findGameTransaction($request["gameRoundRef"], 'round_id', 1); 

		if (!array_key_exists('amountToWithdraw', $request->all())) { //Rollback bet
			
			if ($existing_bet != "false") { // ROLLBACK A WITHDRAW TRANSACTION
				$transaction_uuid = $request['gameRoundRef'];
				$reference_transaction_uuid = $request['transactionRef'];

				// $bet_transaction = $this->findGameTransaction($existing_bet->game_trans_id, 'game_transaction');
				$game_trans_ext_id = NetEntHelper::createGameTransExt($existing_bet->game_trans_id,$transaction_uuid, $reference_transaction_uuid, $existing_bet->bet_amount, 4, $request->all(), $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);
				
				$type = "credit";
				$rollback = true;
				$client_response = ClientRequestHelper::fundTransfer($client_details,$existing_bet->bet_amount,$game_details[0]->game_code,$game_details[0]->game_name,$game_trans_ext_id,$existing_bet->game_trans_id,$type,$rollback);
				//reponse to provider
				
				$response = array (
					'responseCode' => 0,
					'responseMessage' => 'Success',
					'serverTransactionRef' => $existing_bet->game_trans_id,
					'serverToken' => $client_details->player_token,
					'balance' => round($client_response->fundtransferresponse->balance,3)
				);
				
				//Initialize data to pass
				$win = 4; /// 1win 0lost
				$type = "refund" ;
				$request_data = [
					'win' => $win,
					'amount' => $existing_bet->bet_amount,
					'payout_reason' => NetEntHelper::updateReason(4),
					'transid' => $reference_transaction_uuid,
					'roundid' => $transaction_uuid,
				];
				//update transaction
				Helper::updateGameTransaction($existing_bet,$request_data,$type);
				NetEntHelper::updateGameTransactionExt($game_trans_ext_id,$client_response->requestoclient,$client_response->fundtransferresponse,$response);
				NetEntHelper::saveLog('NetEnt Deposit success', $this->provider_db_id, json_encode($request->all()), $response);
				return json_encode($response);
			
			} else { 
			//ROLLBACK AN UNKNOWN TRANSACTION and IDOM
				$response = array (
					'responseCode' => 0,
					'responseMessage' => 'Success',
					'serverToken' => $client_details->player_token,
					'balance' => round($player_details->playerdetailsresponse->balance,3)
				);
				NetEntHelper::saveLog('NetEnt Withdraw transactionRef does not exist ', $this->provider_db_id,  json_encode($request->all()), $response);
				return json_encode($response);
			}
			
		}

		
		
		if($existing_bet != 'false'): // this will be IDOM
			$response = array (
				'responseCode' => 0,
				'responseMessage' => 'Success',
				'serverTransactionRef' => $existing_bet->game_trans_id,
				'serverToken' => $client_details->player_token,
				'balance' => (float)$player_details->playerdetailsresponse->balance
			);
			NetEntHelper::saveLog('NetEnt Withdraw Idom', $this->provider_db_id,  json_encode($request->all()), $response);
			return json_encode($response);
		endif;

		try {

			$bet_amount = $request["amountToWithdraw"];

			if($bet_amount > $player_details->playerdetailsresponse->balance){ // Not Enough money return success
				$response = array (
					'responseCode' => 1,
					'responseMessage' => 'Not enough money in player account',
					'serverToken' => $client_details->player_token,
					'balance' => (float)$player_details->playerdetailsresponse->balance
				);
				NetEntHelper::saveLog('NetEnt Withdraw Not Enough money', $this->provider_db_id,  json_encode($request->all()), $response);
				return json_encode($response);
			}

			if($bet_amount < 0 ){ // NEGATIVE DEPOSIT RESPONSE

				$response = array (
					'responseCode' => 4,
					'responseMessage' => 'Negative withdraw',
					'balance' => 0,
					'serverToken' => NULL,
					'serverTransactionRef' => NULL,
					'messagesToPlayer' => NULL,
				);
				NetEntHelper::saveLog('NetEnt Negative withdraw', $this->provider_db_id,  json_encode($request->all()), $response);
				return json_encode($response);

			}

				$pay_amount = 0;
				$income = 0;
				$win_type = 1;
				$method = 1;
				$win_or_lost = 5; // 0 lost,  5 processing
				$payout_reason = NetEntHelper::updateReason(2);
				$provider_trans_id = $request["transactionRef"];
				$bet_id = $request["gameRoundRef"];
				//Create GameTransaction, GameExtension
				if ($request["reason"] == "GAME_PLAY_FINAL") {
					$income = $request["amountToWithdraw"];
					$win_or_lost = 0;
					$payout_reason = NetEntHelper::updateReason(1);
					$win_type = 2;
				}
				$game_trans_id  = ProviderHelper::createGameTransaction($client_details->token_id, $game_details[0]->game_id, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $bet_id);
				
				$game_trans_ext_id = NetEntHelper::createGameTransExt($game_trans_id,$provider_trans_id, $bet_id, $bet_amount, $win_type, $request->all(), $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);
				
				//requesttosend, and responsetoclient client side
				$type = "debit";
				$rollback = false;
				
				$client_response = ClientRequestHelper::fundTransfer($client_details,$bet_amount,$game_details[0]->game_code,$game_details[0]->game_name,$game_trans_ext_id,$game_trans_id,$type,$rollback);
				
				$response = array (
					'responseCode' => 0,
					'responseMessage' => 'Success',
					'serverTransactionRef' => $game_trans_id,
					'serverToken' => $client_details->player_token,
					'balance' => round($client_response->fundtransferresponse->balance,3)
				);
				//UPDATE gameExtension
				NetEntHelper::updateGameTransactionExt($game_trans_ext_id,$client_response->requestoclient,$client_response->fundtransferresponse,$response);	
			    NetEntHelper::saveLog('NetEnt Withdraw success', $this->provider_db_id, json_encode($request->all()), $response);
			    return $response;
			
		} catch(\Exception $e) { // ERROR HANDLING
			$response = array (
				'responseCode' => 99,
				'responseMessage' => 'Retry exception',
				'balance' => 0,
				'serverToken' => NULL,
				'serverTransactionRef' => NULL,
				'messagesToPlayer' => NULL,
			);
			NetEntHelper::saveLog('NetEnt Withdraw Exception '.$e->getMessage(), $this->provider_db_id, json_encode($request->all()), $response);
			return json_encode($response);
		}


	}


	//win process
	public function deposit(Request $request, $player){

		$game_details = NetEntHelper::findGameDetails('game_code', $this->provider_db_id, $request["game"]); //get game details here
		$playersid = explode('_', $player);
		$client_details = ProviderHelper::getClientDetails('player_id',$playersid[1]);
		$player_details = NetEntHelper::playerDetailsCall($client_details);

		$amount = $request['amountToDeposit'];
		if($amount < 0 ){
			$response = array (
				'responseCode' => 3,
				'responseMessage' => 'Negative deposit',
				'balance' => 0,
				'serverToken' => NULL,
				'serverTransactionRef' => NULL,
				'messagesToPlayer' => NULL,
			);
			NetEntHelper::saveLog('NetEnt Negative deposit', $this->provider_db_id,  json_encode($request->all()), $response);
			return json_encode($response);
		}

		// $existing_win =NetEntHelper::findGameExt($request['gameRoundRef'], 2, 'transaction_id'); 
		$existing_win = NetEntHelper::findGameTransaction($request['gameRoundRef'], 'round_id', 2); 
	
		// $existing_win = NetEntHelper::findGameTransaction($request["gameRoundRef"], 'round_id', 2); 
		if($existing_win == 'false'):

			$existing_bet = NetEntHelper::findGameTransaction($request['gameRoundRef'], 'round_id', 1); 
			// No Bet was found check if this is a free spin and proccess it!
			if($existing_bet != 'false'): 
				
				$transaction_uuid = $request['gameRoundRef'];
				$reference_transaction_uuid = $request['transactionRef'];

				// $bet_transaction = $this->findGameTransaction($existing_bet->game_trans_id, 'game_transaction');
				$game_trans_ext_id = NetEntHelper::createGameTransExt($existing_bet->game_trans_id,$transaction_uuid, $reference_transaction_uuid, $amount, 2, $request->all(), $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);
				
				$type = "credit";
				$rollback = false;
				$client_response = ClientRequestHelper::fundTransfer($client_details,$amount,$game_details[0]->game_code,$game_details[0]->game_name,$game_trans_ext_id,$existing_bet->game_trans_id,$type,$rollback);
				//reponse to provider
				
				$response = array (
					'responseCode' => 0,
					'responseMessage' => 'Success',
					'serverTransactionRef' => $existing_bet->game_trans_id,
					'serverToken' => $client_details->player_token,
					'balance' => round($client_response->fundtransferresponse->balance,3)
				);
				
				//Initialize data to pass
				$win = $amount > 0  ?  1 : 0;  /// 1win 0lost
				$type = $amount > 0  ? "credit" : "debit";
				$request_data = [
					'win' => $win,
					'amount' => $amount,
					'payout_reason' => NetEntHelper::updateReason(1),
				];
				//update transaction
				Helper::updateGameTransaction($existing_bet,$request_data,$type);
				NetEntHelper::updateGameTransactionExt($game_trans_ext_id,$client_response->requestoclient,$client_response->fundtransferresponse,$response);
				NetEntHelper::saveLog('NetEnt Deposit success', $this->provider_db_id, json_encode($request->all()), $response);
				return json_encode($response);
			else: 

				//TOURNAMENT PROCESS
				if ($request["reason"] == "AWARD_TOURNAMENT_WIN") {
					//TEMPORARY RESPONSE
					$response = array (
						'responseCode' => 0,
						'responseMessage' => 'Success',
						'serverTransactionRef' => NULL,
						'serverToken' => $client_details->player_token,
						'balance' => (float)$player_details->playerdetailsresponse->balance
					);
					NetEntHelper::saveLog('NetEnt Deposit Idom', $this->provider_db_id,  json_encode($request->all()), $response);
					return json_encode($response);
					// $pay_amount = $amount;
					// $income = 0 - $amount;
					// $method = 2;
					// $win_or_lost = 1; // 0 lost,  5 processing
					// $payout_reason = NetEntHelper::updateReason(1);
					// $provider_trans_id = $request["transactionRef"];
					// $bet_id = $request["transactionRef"];
					// $bet_amount = 0;
					// //Create GameTransaction, GameExtension
					// $game_trans_id  = ProviderHelper::createGameTransaction($client_details->token_id, $game_details[0]->game_id,$bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $bet_id);
					
					// $game_trans_ext_id = NetEntHelper::createGameTransExt($game_trans_id,$provider_trans_id, $bet_id, $bet_amount, 2, $request->all(), $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);
					
					// //requesttosend, and responsetoclient client side
					// $type = "credit";
					// $rollback = false;
					
					// $client_response = ClientRequestHelper::fundTransfer($client_details,$pay_amount,$game_details[0]->game_code,$game_details[0]->game_name,$game_trans_ext_id,$game_trans_id,$type,$rollback);
					
					// $response = array (
					// 	'responseCode' => 0,
					// 	'responseMessage' => 'Success',
					// 	'serverTransactionRef' => $game_trans_id,
					// 	'serverToken' => $client_details->player_token,
					// 	'balance' => round($client_response->fundtransferresponse->balance,3)
					// );
					// //UPDATE gameExtension
					// NetEntHelper::updateGameTransactionExt($game_trans_ext_id,$client_response->requestoclient,$client_response->fundtransferresponse,$response);	
				 //    NetEntHelper::saveLog('NetEnt Deposit TOURNAMENT WIN', $this->provider_db_id, json_encode($request->all()), $response);
				 //    return $response;

				} else {
					// NO BET FOUND RETURN SUCESS
					$response = array (
						'responseCode' => 0,
						'responseMessage' => 'Success',
						'serverTransactionRef' => NULL,
						'serverToken' => $client_details->player_token,
						'balance' => (float)$player_details->playerdetailsresponse->balance
					);
					NetEntHelper::saveLog('NetEnt Deposit Idom', $this->provider_db_id,  json_encode($request->all()), $response);
					return json_encode($response);
				}
				
			endif;
		
		else:	
		    // NOTE IF CALLBACK WAS ALREADY PROCESS PROVIDER DONT NEED A ERROR RESPONSE! LEAVE IT AS IT IS!
			$response = array (
				'responseCode' => 0,
				'responseMessage' => 'Success',
				'serverTransactionRef' => $existing_win->game_trans_id,
				'serverToken' => $client_details->player_token,
				'balance' => (float)$player_details->playerdetailsresponse->balance
			);
			NetEntHelper::saveLog('NetEnt Deposit Idom', $this->provider_db_id,  json_encode($request->all()), $response);
			return json_encode($response);
			
		endif;
	}


}
