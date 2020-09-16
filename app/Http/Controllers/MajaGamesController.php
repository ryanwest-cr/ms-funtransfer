<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use GuzzleHttp\Client;
use Carbon\Carbon;
use App\Helpers\ClientRequestHelper;
use DB;

class MajaGamesController extends Controller
{

    public function __construct(){
    	$this->auth = config('providerlinks.majagames.auth');
		$this->provider_db_id = config('providerlinks.majagames.provider_id');
	}

	public function bet(Request $request){
		// $header = $request->header('Authorization');
		// if($header != $this->auth):
		// 	$errormessage = array(
		// 		'status' => '400',
		// 		'error_code' => '1000',
		// 		'error_msg' => 'Invalid request parameters'
		// 	);
		// 	Helper::saveLog('MajaGames Authorization Bet error '.$header, $this->provider_db_id, json_encode($request->all()), $errormessage);
		// 	return $errormessage;
		// endif;
	    // Helper::saveLog('MajaGames Authorization BET', $this->provider_db_id, json_encode($request->all()), $header);
		Helper::saveLog('MajaGames Authorization BET', $this->provider_db_id, json_encode($request->all()), "BET Endpoint");
		$data =  json_decode(json_encode($request->all()));
		$player_id = $data->player_unique_id;
		$game_id = $data->game;
		$amount = $data->amount;
		$bet_id = $data->game_round_id;
		$transaction_uuid = $data->transaction_id;
		$player_id =  ProviderHelper::explodeUsername('_', $player_id);
		$client_details = ProviderHelper::getClientDetails('player_id',$player_id);
		$getPlayer = ProviderHelper::playerDetailsCall($client_details->player_token);
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $game_id);

		$transaction_check = ProviderHelper::findGameExt($transaction_uuid, 1,'transaction_id');
		if($transaction_check != 'false'){
			$errormessage = array(
				'status' => '500',
				'error_code' => '1000',
				'error_msg' => 'Transaction bet exist'
			);
			Helper::saveLog('MajaGames Bet error', $this->provider_db_id, json_encode($request->all()), $errormessage);
			return $errormessage;
		}
		try{
			//Initialize
			$game_transaction_type = 1; // 1 Bet, 2 Win
			$game_code = $game_details->game_id;
			$token_id = $client_details->token_id;
			$bet_amount = abs($amount);
			$pay_amount = 0;
			$income = 0;
			$method = 1;
			$win_or_lost = 0; // 0 lost,  5 processing
			$payout_reason = 'Bet';
			$provider_trans_id = $transaction_uuid;

			//Create GameTransaction, GameExtension
			$gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_code, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $bet_id);
			$game_transextension = $this->createGameTransExt($gamerecord,$provider_trans_id, $bet_id, $bet_amount, $game_transaction_type, $data, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);
			
			//get Round_id, Transaction_id
			$transaction_id = ProviderHelper::findGameExt($transaction_uuid, 1,'transaction_id');
			
			//requesttosend, and responsetoclient client side
			$type = "debit";
			$rollback = false;
			$client_response = ClientRequestHelper::fundTransfer($client_details,$amount,$game_code,$game_details->game_name,$transaction_id->game_trans_ext_id,$transaction_id->game_trans_id,$type,$rollback);

			//response to provider				
			$data_response = [
				"code" => 0,
				"data" => [
					"balance" => floatval(number_format((float)$client_response->fundtransferresponse->balance, 2, '.', ''))
				]
			];
			//UPDATE gameExtension
			
			$this->updateGameTransactionExt($transaction_id->game_trans_ext_id,$client_response->requestoclient,$client_response->fundtransferresponse,$data_response);
			Helper::saveLog('MajaGames Bet Processed', $this->provider_db_id,  json_encode($request->all()), $data_response);
			return $data_response;
		}catch(\Exception $e){
			$errormessage = array(
				'status' => '400',
				'error_code' => '1000',
				'error_msg' => 'Invalid request parameters'
			);
			Helper::saveLog('MajaGames Bet Internal error '.$e->getMessage(), $this->provider_db_id, json_encode($request->all()), $errormessage);
			return $errormessage;
		}
		
	}

	public function settlement(Request $request){
		// $header = $request->header('Authorization');
		// if($header != $this->auth):
		// 	$errormessage = array(
		// 		'status' => '400',
		// 		'error_code' => '1000',
		// 		'error_msg' => 'Invalid request parameters'
		// 	);
		// 	Helper::saveLog('MajaGames Authorization Settlement error'.$header, $this->provider_db_id,  json_decode(json_encode(file_get_contents("php://input"))), $errormessage);
		// 	return $errormessage;
		// endif;
	    Helper::saveLog('MajaGames Authorization Win', $this->provider_db_id, json_decode(json_encode(file_get_contents("php://input"))), "Win Endpoint");
		
		//JSON_FORMAT CONVERT
		$data = file_get_contents("php://input");
		$data = json_decode($data);
		
		//INITIALIZE DATA
		$player_id = $data->player_unique_id;
		$game_id = $data->game;
		$amount = $data->amount;
		$transaction_uuid = $data->transaction_id;
		$reference_transaction_uuid = $data->game_round_id;
		//CHECKING TOKEN
		$player_id =  ProviderHelper::explodeUsername('_', $player_id);
		$client_details = ProviderHelper::getClientDetails('player_id',$player_id);
		$getPlayer = ProviderHelper::playerDetailsCall($client_details->player_token);
		if($getPlayer == null){
			$errormessage = array(
				'status' => '400',
				'error_code' => '1000',
				'error_msg' => 'Invalid request parameters'
			);
			Helper::saveLog('MajaGames settlement error', $this->provider_db_id,  json_decode(json_encode(file_get_contents("php://input"))), $errormessage);
			return $errormessage;
		}
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $game_id);
		
		//CHECKING if BET EXISTING game_transaction_ext IF FALSE no bet record
		$existing_bet = ProviderHelper::findGameExt($reference_transaction_uuid, 1,'round_id');
		if($existing_bet == 'false'){
			$errormessage = array(
				'status' => '400',
				'error_code' => '1000',
				'error_msg' => 'Invalid request parameters'
			);
			Helper::saveLog('MajaGames settlement error', $this->provider_db_id,  json_decode(json_encode(file_get_contents("php://input"))), $errormessage);
			return $errormessage;
		}

		//CHECKING WIN EXISTING game_transaction_ext IF WIN ALREADY PROCESS
		$transaction_check = ProviderHelper::findGameExt($transaction_uuid, 2,'round_id');
		if($transaction_check != 'false'){
			$errormessage = array(
				'status' => '400',
				'error_code' => '1000',
				'error_msg' => 'Invalid request parameters'
			);
			Helper::saveLog('MajaGames settlement error', $this->provider_db_id,  json_decode(json_encode(file_get_contents("php://input"))), $errormessage);
			return $errormessage;
		}
		
		try{
			//get details on game_transaction
			$bet_transaction = ProviderHelper::findGameTransaction($existing_bet->game_trans_id, 'game_transaction');
			$game_transextension = $this->createGameTransExt($bet_transaction->game_trans_id,$transaction_uuid, $reference_transaction_uuid, $amount, 2, $data, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);
			
			//get game_trans_id and game_trans_ext
			$transaction_id = ProviderHelper::findGameExt($transaction_uuid, 2,'transaction_id');

			//requesttosend, and responsetoclient client side
			$type = "credit";
			$rollback = false;
			$client_response = ClientRequestHelper::fundTransfer($client_details,$amount,$game_details->game_code,$game_details->game_name,$transaction_id->game_trans_ext_id,$transaction_id->game_trans_id,$type,$rollback);

			//reponse to provider
		    $data_response = [
				"code" => 0,
				"data" => [
					"balance" => floatval(number_format((float)$client_response->fundtransferresponse->balance, 2, '.', ''))
				]
			];
			
			//Initialize data to pass
			$win = $amount > 0  ?  1 : 0;  /// 1win 0lost
			$type = $amount > 0  ? "credit" : "debit";
			$request_data = [
				'win' => $win,
				'amount' => $amount,
				'payout_reason' => 2
			];
			//update transaction
			Helper::updateGameTransaction($bet_transaction,$request_data,$type);
			$this->updateGameTransactionExt($transaction_id->game_trans_ext_id,$client_response->requestoclient,$client_response->fundtransferresponse,$data_response);
		    Helper::saveLog('MajaGames Win Processed', $this->provider_db_id, json_decode(json_encode(file_get_contents("php://input"))), $data_response);
	        return $data_response;
		}catch(\Exception $e){
			$errormessage = array(
				'status' => '400',
				'error_code' => '1000',
				'error_msg' => 'Invalid request parameters'
			);
			Helper::saveLog('MajaGames settlement error'.$e->getMessage(), $this->provider_db_id,  json_decode(json_encode(file_get_contents("php://input"))), $errormessage);
			return $errormessage;
		}
	}

	public function getBalance(Request $request){
		try{
			$data =  json_decode(json_encode($request->all()));
			// $header = $request->header('Authorization');
			// if($header != $this->auth):
			// 	$errormessage = array(
			// 		'status' => '400',
			// 		'error_code' => '1000',
			// 		'error_msg' => 'Invalid request parameters'
			// 	);
			// 	Helper::saveLog('MajaGames Authorization Balance error '.$header, $this->provider_db_id, json_encode($request->all()), $errormessage);
			// 	return $errormessage;
			// endif;
			Helper::saveLog('MajaGames Authorization Balance', $this->provider_db_id, json_encode($request->all()), "Balance Endpoint");
			$player_id = $data->player_unique_id;
			$player_id =  ProviderHelper::explodeUsername('_', $player_id);
			$client_details = ProviderHelper::getClientDetails('player_id',$player_id);
		

			if($client_details != null){
				$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
				$data =  [
					'code' => 0,
					'data' =>[
						'balance' => floatval(number_format((float)$player_details->playerdetailsresponse->balance, 2, '.', ''))
					]
				];
				Helper::saveLog('MajaGames Check Balance Response', $this->provider_db_id, json_encode($request->all()), $data);
				return $data;
			}else{
				$errormessage = array(
					'status' => '400',
					'error_code' => '1000',
					'error_msg' => 'Invalid request parameters'
				);
				Helper::saveLog('MajaGames Balance error', $this->provider_db_id, json_encode($request->all()), $errormessage);
				return $errormessage;
			}
		}catch(\Exception $e){
			$errormessage = array(
				'status' => '500',
				'error_code' => '1000',
				'error_msg' => $e->getMessage()
			);
			Helper::saveLog('MajaGames Balance error', $this->provider_db_id, json_encode($request->all()) , $errormessage);
			return $errormessage;
		}
	}

	public function cancel(Request $request){
		return 1;
	}

	public  static function findGameExt($provider_identifier, $type) {
		$transaction_db = DB::table('game_transaction_ext as gte');
        if ($type == 'transaction_id') {
			$transaction_db->where([
		 		["gte.provider_trans_id", "=", $provider_identifier]
		 	
		 	]);
		}
		if ($type == 'round_id') {
			$transaction_db->where([
		 		["gte.round_id", "=", $provider_identifier],
		 	]);
		}  
		$result= $transaction_db->first();
		return $result ? $result : 'false';
	}

	public static function createGameTransExt($game_trans_id, $provider_trans_id, $round_id, $amount, $game_type, $provider_request, $mw_response, $mw_request, $client_response, $transaction_detail){
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
			"transaction_detail" =>json_encode($transaction_detail)
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gamestransaction_ext_ID;
	}

	public static function updateGameTransactionExt($gametransextid,$mw_request,$mw_response,$client_response){
		$gametransactionext = array(
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		DB::table('game_transaction_ext')->where("game_trans_ext_id",$gametransextid)->update($gametransactionext);
	}
}
