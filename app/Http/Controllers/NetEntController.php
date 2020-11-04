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
		$this->casinoID = "CasinoID";//casino id
		$this->url = "url";//url provide the provider
		$this->secret_token = "secrete_token";//provider by provider
		$this->provider_db_id = 45;//mw operator sub_provider 76
	}

	public function currency(Request $request, $player){
		try {
			$getClientDetails = ProviderHelper::getClientDetails('player_id',$player);
			$response = [
				"responseCode" => 0,
				"currencyISOCode" => $getClientDetails->default_currency,
				"responseMessage" => "Success"
			];
			Helper::saveLog('NetEnt Currency', $this->provider_db_id,  json_encode($request->all()), json_encode($response));
			return json_encode($response);
		} catch(\Exception $e){
			$reponse = array (
				'responseCode' => 99,
				'responseMessage' => 'Retry exception',
				'balance' => 0,
				'serverToken' => NULL,
				'serverTransactionRef' => NULL,
				'messagesToPlayer' => NULL,
			);
			Helper::saveLog('NetEnt Currency Exception', $this->provider_db_id,  json_encode($request->all()), json_encode($response));
			return json_encode($response);
		}
		
	}

	public function balance(Request $request, $player){
		try{
			$getClientDetails = ProviderHelper::getClientDetails('player_id',$player);
			$player_details = Providerhelper::playerDetailsCall($getClientDetails->player_token);
			$response = [
				"responseCode" => 0,
				"serverToken" =>$getClientDetails->player_token,
				"balance" =>  $player_details->playerdetailsresponse->balance,
				"responseMessage" => "Success"
			];
			Helper::saveLog('NetEnt Balance', $this->provider_db_id,  json_encode($request->all()), json_encode($response));
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
			Helper::saveLog('NetEnt Balance Exception', $this->provider_db_id,  json_encode($request->all()), json_encode($response));
			return json_encode($response);
		}
	}
	//bet process
	public function withdraw(Request $request, $player){
		$game_details = NetEntHelper::findGameDetails('game_code', $this->provider_db_id, $request["game"]); //get game details here
		$player_details = NetEntHelper::playerDetailsCall($request["serverToken"]);
		$client_details = NetEntHelper::getClientDetails('token', $request["serverToken"]);
		
		$existing_bet =NetEntHelper::findGameTransaction($request["transactionRef"], 'round_id', 1); 
		if($existing_bet != 'false'): // NO BET
			$response = array (
				'responseCode' => 0,
				'responseMessage' => 'Success',
				'serverTransactionRef' => $existing_bet->game_trans_id,
				'serverToken' => $client_details->player_token,
				'balance' => $player_details->playerdetailsresponse->balance
			);
			Helper::saveLog('NetEnt Withdraw Idom', $this->provider_db_id,  json_encode($request->all()), json_encode($response));
			return json_encode($response);
		endif;
		if ($player_details->playerdetailsresponse->balance < 0) {
			$response = array (
				'responseCode' => 1,
				'responseMessage' => 'Not enough money in player account',
				'serverToken' => $client_details->player_token,
				'balance' => $player_details->playerdetailsresponse->balance
			);
			Helper::saveLog('NetEnt Withdraw Balance', $this->provider_db_id,  json_encode($request->all()), json_encode($response));
			return json_encode($response);
		}

		try {
			$bet_amount = abs($request["amountToWithdraw"]);
			$pay_amount = 0;
			$income = 0;
			$win_type = 0;
			$method = 1;
			$win_or_lost = 0; // 0 lost,  5 processing
			$payout_reason = NetEntHelper::updateReason(2);
			$provider_trans_id = $request["gameRoundRef"];
			$bet_id = $request["transactionRef"];
			
			//Create GameTransaction, GameExtension
			$game_trans_id  = ProviderHelper::createGameTransaction($client_details->token_id, $game_details[0]->game_id, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $bet_id);
			
			$game_trans_ext_id = NetEntHelper::createGameTransExt($game_trans_id,$provider_trans_id, $bet_id, $bet_amount, 1, $request, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);
			
			//requesttosend, and responsetoclient client side
			$type = "debit";
			$rollback = false;
			
			$client_response = ClientRequestHelper::fundTransfer($client_details,$bet_amount,$game_details[0]->game_id,$game_details[0]->game_name,$game_trans_ext_id,$game_trans_id,$type,$rollback);
			$response = array (
				'responseCode' => 0,
				'responseMessage' => 'Success',
				'serverTransactionRef' => 4686,
				'serverToken' => 'n58ec5e159f769ae0b7b3a0774fdbf80',
				'balance' => 37.0
			);
			//UPDATE gameExtension
			NetEntHelper::updateGameTransactionExt($game_trans_ext_id,$client_response->requestoclient,$client_response->fundtransferresponse,$response);	
		    NetEntHelper::saveLog('NetEnt Deposit success', $this->provider_db_id, json_encode($request->all()), $response);
		    return $response;

		} catch(\Exception $e) {
			$response = array (
				'responseCode' => 99,
				'responseMessage' => 'Retry exception',
				'balance' => 0,
				'serverToken' => NULL,
				'serverTransactionRef' => NULL,
				'messagesToPlayer' => NULL,
			);
			NetEntHelper::saveLog('NetEnt Deposit Exception', $this->provider_db_id, json_encode($request->all()), $response);
			return json_encode($response);
		}


	}


	//win process
	public function deposit(Request $request, $player){
		
		$game_details = NetEntHelper::findGameDetails('game_code', $this->provider_db_id, $request->game); //get game details here
		$player_details = NetEntHelper::playerDetailsCall($request['token']);
		$client_details = NetEntHelper::getClientDetails('token', $request['token']);

		$game_ext = NetEntHelper::checkTransactionExist($request['callback_id'], 2); 
		if($game_ext == 'false'):

			$existing_bet =NetEntHelper::findGameTransaction($request['data']['round_id'], 'round_id', 1); 

			// No Bet was found check if this is a free spin and proccess it!
			if($existing_bet != 'false'): 
				
				if(isset($string_to_obj->game->action) && $string_to_obj->game->action == 'freespin'):
					
					$game_transaction_type = 2; // 1 Bet, 2 Win
					$game_code = $game_details[0]->game_id;
					$token_id = $client_details->token_id;
					$bet_amount = 0;
					$pay_amount = abs($request['data']['amount']);
					$income = $bet_amount - $pay_amount;
					$method = $pay_amount == 0 ? 1 : 2;
					$win_or_lost =  $pay_amount == 0 ? 0 : 1;; // 0 lost,  5 processing
					$payout_reason = 'Freespin';
					$transaction_uuid = $request['callback_id'];
					$reference_transaction_uuid = $request['data']['round_id'];

					//Create GameTransaction, GameExtension
					$game_trans_id  = ProviderHelper::createGameTransaction($token_id, $game_code, $bet_amount,  $pay_amount, $method, $win_or_lost,  NetEntHelper::updateReason(1), $payout_reason, $income, $transaction_uuid, $reference_transaction_uuid);
					
					$game_trans_ext_id = NetEntHelper::createGameTransExt($game_trans_id,$transaction_uuid, $reference_transaction_uuid, $pay_amount, $game_transaction_type, $request, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);

					
					$type = "credit";
					$rollback = false;
					$client_response = ClientRequestHelper::fundTransfer($client_details,$pay_amount,$game_code,$game_details[0]->game_name,$game_trans_ext_id,$game_trans_id,$type,$rollback);

					$response = array (
						'responseCode' => 0,
						'responseMessage' => 'Success',
						'serverTransactionRef' => 4686,
						'serverToken' => 'n58ec5e159f769ae0b7b3a0774fdbf80',
						'balance' => 37.0,
						'messagesToPlayer' => 
						array (
							'messages' => 
							array (
							0 => 
								array (
									'code' => 993,
									'message' => 'Happy Birthday',
								),
							1 => 
								array (
									'code' => 993,
									'message' => 'You have 100 bonus',
								),
							),
						),
					);

					NetEntHelper::updateGameTransactionExt($game_trans_ext_id,$client_response->requestoclient,$client_response->fundtransferresponse,$response);
					NetEntHelper::saveLog('NetEnt Withdraw success', $this->provider_db_id, json_encode($request->all()), $response);
					return $response;
				else:
					$game_code = $game_details[0]->game_id;
					$amount = abs($request['data']['amount']);
					$transaction_uuid = $request['callback_id'];
					$reference_transaction_uuid = $request['data']['round_id'];

					// $bet_transaction = $this->findGameTransaction($existing_bet->game_trans_id, 'game_transaction');
					$game_trans_ext_id = NetEntHelper::createGameTransExt($existing_bet->game_trans_id,$transaction_uuid, $reference_transaction_uuid, $amount, 2, $request, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);
					
					$type = "credit";
					$rollback = false;
					$client_response = ClientRequestHelper::fundTransfer($client_details,$amount,$game_code,$game_details[0]->game_name,$game_trans_ext_id,$existing_bet->game_trans_id,$type,$rollback);
					//reponse to provider
					
					$response = array (
						'responseCode' => 0,
						'responseMessage' => 'Success',
						'serverTransactionRef' => 4686,
						'serverToken' => 'n58ec5e159f769ae0b7b3a0774fdbf80',
						'balance' => 37.0,
						'messagesToPlayer' => 
						array (
							'messages' => 
							array (
							0 => 
								array (
									'code' => 993,
									'message' => 'Happy Birthday',
								),
							1 => 
								array (
									'code' => 993,
									'message' => 'You have 100 bonus',
								),
							),
						),
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
					NetEntHelper::saveLog('NetEnt Withdraw success', $this->provider_db_id, json_encode($request->all()), $response);
					
					return $response;
				endif;
			else:
				$response = array(
				'status' => 'ok',
				'data' => [
					'balance' => (string)$player_details->playerdetailsresponse->balance,
					'currency' => $client_details->default_currency,
				],
				);
				NetEntHelper::saveLog('TGG no bet found in win proccess', $this->provider_db_id, json_encode($request), $response);
				return $response;
			endif;
		
		else:	
		    // NOTE IF CALLBACK WAS ALREADY PROCESS PROVIDER DONT NEED A ERROR RESPONSE! LEAVE IT AS IT IS!
			$response = array(
				'status' => 'ok',
				'data' => [
					'balance' => (string)$player_details->playerdetailsresponse->balance,
					'currency' => $client_details->default_currency,
				],
			);
			NetEntHelper::saveLog('TGG win duplicate', $this->provider_db_id, json_encode($request), $response);
			return $response;
			
		endif;
	}

	

	public function rollback(Request $request){
		return 20;
	}

}
