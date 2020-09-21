<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use GuzzleHttp\Client;
use Carbon\Carbon;
use App\Helpers\ClientRequestHelper;
use DB;

class SpadeController extends Controller
{
	
    public function __construct(){
    	$this->prefix = config('providerlinks.spade.prefix');
    	$this->merchantCode = config('providerlinks.spade.merchantCode');
		$this->siteId = config('providerlinks.spade.siteId');
		$this->api_url = config('providerlinks.spade.api_url');
		$this->provider_db_id = config('providerlinks.spade.provider_id');
	}

	public function generateSerialNo(){
    	// $guid = vsprintf('%s%s-%s-4000-8%.3s-%s%s%s0',str_split(dechex( microtime(true) * 1000 ) . bin2hex( random_bytes(8) ),4));
    	$guid = substr("abcdefghijklmnopqrstuvwxyz1234567890", mt_rand(0, 25), 1).substr(md5(time()), 1);;
    	return $guid;
	}
	
	public function getGameList(Request $request){
		$api = $this->api_url;
		
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
		if(!$request->header('API')){
			$response = [
				"msg" => "Missing Parameters",
				"code" => 105
			];
			Helper::saveLog('Spade error API', $this->provider_db_id,  '', $response);
			return $response;
		}
		$header = [
            'API' => $request->header('API'),
        ];
		$data = file_get_contents("php://input");
		$details = json_decode($data);
		Helper::saveLog('Spade '.$header['API'], $this->provider_db_id,  json_encode($details), $header);
		if($details->merchantCode != $this->merchantCode){
			$response = [
				"msg" => "Merchant Not Found",
				"code" => 10113
			];
			Helper::saveLog('Spade index error', $this->provider_db_id,  json_encode($details), $response);
			return $response;
		}
		if($header['API'] == 'authorize'){
			return $this->_authorize($details,$header);
		}elseif($header['API'] == 'getBalance'){
			return $this->_getBalance($details,$header);
		}elseif($header['API'] == 'transfer'){
			return $this->_transfer($details,$header);
		}
	}

	public function _authorize($details,$header){
		$acctId =  ProviderHelper::explodeUsername('_', $details->acctId);
		$client_details = Providerhelper::getClientDetails('player_id', $acctId);
		if($client_details == null){
			$response = [
				"msg" => "Acct Not Found",
				"code" => 50100
			];
			Helper::saveLog('Spade '.$header['API'].' error', $this->provider_db_id, json_encode($details), $response);
			return $response;
			
		}
		if($details->merchantCode != $this->merchantCode){
			$response = [
				"msg" => "Merchant Not Found",
				"code" => 10113
			];
			Helper::saveLog('Spade '.$header['API'].' error', $this->provider_db_id, json_encode($details), $response);
			return $response;
		}
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
		$response = [
			"acctInfo" => [
				"acctId" => $this->prefix.'_'.$acctId,
				"balance" => floatval(number_format((float)$player_details->playerdetailsresponse->balance, 2, '.', '')),
				"userName" => $this->prefix.$acctId,
				"currency" => "JPY",
				"siteId" => $this->siteId
			],
			"merchantCode" => $this->merchantCode,"msg" => "success","code" => 0,"serialNo" => $details->serialNo
		];
		Helper::saveLog('Spade '.$header['API'].' process', $this->provider_db_id, json_encode($details), $response);
		return $response;
	}

	public function _getBalance($details,$header){
		$acctId =  ProviderHelper::explodeUsername('_', $details->acctId);
		$client_details = Providerhelper::getClientDetails('player_id', $acctId);
		if($client_details == null){
			$response = [
				"msg" => "Acct Not Found",
				"code" => 50100
			];
			Helper::saveLog('Spade '.$header['API'].' error', $this->provider_db_id, json_encode($details), $response);
			return $response;
			
		}
		if($details->merchantCode != $this->merchantCode){
			$response = [
				"msg" => "Merchant Not Found",
				"code" => 10113
			];
			Helper::saveLog('Spade '.$header['API'].' error', $this->provider_db_id, json_encode($details), $response);
			return $response;
		}
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
    	$response = [
			"acctInfo" => [
				"acctId" => $this->prefix.'_'.$acctId,
				"balance" => floatval(number_format((float)$player_details->playerdetailsresponse->balance, 2, '.', '')),
				"userName" => $this->prefix.$acctId,
				"currency" => "JPY"
			],
			"merchantCode" => $this->merchantCode,"msg" => "success","code" => 0,"serialNo" => $details->serialNo
		];
		Helper::saveLog('Spade '.$header['API'].' process', $this->provider_db_id, json_encode($details), $response);
		return $response;
	}

	public function _transfer($details,$header){
		$acctId =  ProviderHelper::explodeUsername('_', $details->acctId);
		$client_details = Providerhelper::getClientDetails('player_id', $acctId);
		if($client_details == null){
			$response = [
				"msg" => "Acct Not Found",
				"code" => 50100
			];
			Helper::saveLog('Spade '.$header['API'].' error', $this->provider_db_id, json_encode($details), $response);
			return $response;
			
		}
		if($details->merchantCode != $this->merchantCode){
			$response = [
				"msg" => "Merchant Not Found",
				"code" => 10113
			];
			Helper::saveLog('Spade '.$header['API'].' error', $this->provider_db_id,  json_encode($details), $response);
			return $response;
		}
		if($details->type == 1){
			return $this->_placeBet($details,$header);
		}else if($details->type == 2){
			return $this->cancelBet($details,$header);
		}else if($details->type == 7){
			return $this->_bonus($details,$header);
		}else if($details->type == 4){
			return $this->_payout($details,$header);
		}
	}

	public function _placeBet($details,$header){
		$acctId =  ProviderHelper::explodeUsername('_', $details->acctId);
		$client_details = Providerhelper::getClientDetails('player_id', $acctId);
		$getPlayer = ProviderHelper::playerDetailsCall($client_details->player_token);
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $details->gameCode);

		if($getPlayer->playerdetailsresponse->balance < $details->amount){
			$response = [
				"msg" => "Insufficient Balance",
				"code" => 50110
			];
			Helper::saveLog('Spade '.$header['API'].' bet error', $this->provider_db_id,  json_encode($details), $response);
			return $response;
		}
		$transaction_check = ProviderHelper::findGameExt($details->transferId, 1,'transaction_id');
		if($transaction_check != 'false'){
			$response = [
				"msg" => "Acct Exist",
				"code" => 50099
			];
			Helper::saveLog('Spade '.$header['API'].' bet error', $this->provider_db_id,  json_encode($details), $response);
			return $response;
		}

		try{
			//Initialize
			$game_transaction_type = 1; // 1 Bet, 2 Win
			$game_code = $game_details->game_id;
			$token_id = $client_details->token_id;
			$bet_amount = abs($details->amount);
			$pay_amount = 0;
			$income = 0;
			$win_type = 0;
			$method = 1;
			$win_or_lost = 0; // 0 lost,  5 processing
			$payout_reason = 'Bet';
			$provider_trans_id = $details->transferId;
			$bet_id = $details->serialNo;
			//Create GameTransaction, GameExtension
			$gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_code, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $bet_id);
			$game_transextension = $this->createGameTransExt($gamerecord,$provider_trans_id, $bet_id, $bet_amount, $game_transaction_type, $details, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);
			
			//get Round_id, Transaction_id
			$transaction_id = ProviderHelper::findGameExt($details->transferId, 1,'transaction_id');
			$round_id = ProviderHelper::findGameTransaction($details->transferId, 'transaction_id',1) ;
			
			//requesttosend, and responsetoclient client side
			$type = "debit";
			$rollback = "false";
			$client_response = ClientRequestHelper::fundTransfer($client_details,$bet_amount,$game_code,$game_details->game_name,$transaction_id->game_trans_ext_id,$transaction_id->game_trans_id,$type,$rollback);

			//response to provider				
			$response = [
				"transferId" => (string)$round_id->game_trans_id,
				"merchantCode" => $this->merchantCode,
				"merchantTxId" => (string)$transaction_id->game_trans_ext_id,
				"acctId" => $details->acctId,
				"balance" => floatval(number_format((float)$client_response->fundtransferresponse->balance, 2, '.', '')),
				"msg" => "success",
				"code" => 0,
				"serialNo" => $details->serialNo,
			];
			//UPDATE gameExtension
			
			$this->updateGameTransactionExt($transaction_id->game_trans_ext_id,$client_response->requestoclient,$client_response->fundtransferresponse,$response);
			Helper::saveLog('Spade '.$header['API'].' Bet Processed', $this->provider_db_id,  json_encode($details), $response);
			return $response;
		}catch(\Exception $e){
			$response = [
				"msg" => "System Error",
				"code" => 1
			];
			Helper::saveLog('Spade '.$header['API'].' bet error = '.$e->getMessage(), $this->provider_db_id,  json_encode($details), $response);
			return $response;
		}		
	}

	public function _payout($details,$header){
		$acctId =  ProviderHelper::explodeUsername('_', $details->acctId);
		$client_details = Providerhelper::getClientDetails('player_id', $acctId);
		$getPlayer = ProviderHelper::playerDetailsCall($client_details->player_token);
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $details->gameCode);

		//CHECKING if BET EXISTING game_transaction_ext IF FALSE no bet record
		$existing_bet = ProviderHelper::findGameExt($details->referenceId, 1,'transaction_id');
		if($existing_bet == 'false'){
			$response = [
				"msg" => "Acct Exist",
				"code" => 50099
			];
			Helper::saveLog('Spade '.$header['API'].' Payout error', $this->provider_db_id,  json_encode($details), $response);
			return $response;
		}

		//CHECKING WIN EXISTING game_transaction_ext IF WIN ALREADY PROCESS
		$transaction_check = ProviderHelper::findGameExt($details->referenceId, 2,'transaction_id');
		if($transaction_check != 'false'){
			$response = [
				"msg" => "Acct Exist",
				"code" => 50099
			];
			Helper::saveLog('Spade '.$header['API'].' Payout error', $this->provider_db_id,  json_encode($details), $response);
			return $response;
		}

		try{
			//get details on game_transaction
			$bet_transaction = ProviderHelper::findGameTransaction($existing_bet->game_trans_id, 'game_transaction');
			$game_transextension = $this->createGameTransExt($bet_transaction->game_trans_id,$details->transferId, $details->referenceId, $details->amount, 2, $details, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);
			
			//get game_trans_id and game_trans_ext
			$transaction_id = ProviderHelper::findGameExt($details->transferId, 2,'transaction_id');

			//requesttosend, and responsetoclient client side
			$round_id = $bet_transaction->game_trans_id;
			$type = "credit";
			$rollback = false;
			$client_response = ClientRequestHelper::fundTransfer($client_details,$details->amount,$game_details->game_code,$game_details->game_name,$transaction_id->game_trans_ext_id,$round_id,$type,$rollback);
			//reponse to provider
			$response = [
				"transferId" => (string)$round_id,
				"merchantCode" => $this->merchantCode,
				"merchantTxId" => (string)$transaction_id->game_trans_ext_id,
				"acctId" => $details->acctId,
				"balance" => floatval(number_format((float)$client_response->fundtransferresponse->balance, 2, '.', '')),
				"msg" => "success",
				"code" => 0,
				"serialNo" => $details->serialNo
			];
			
			//Initialize data to pass
			$win = $details->amount > 0  ?  1 : 0;  /// 1win 0lost
			$type = $details->amount > 0  ? "credit" : "debit";
			$request_data = [
				'win' => $win,
				'amount' => $details->amount,
				'payout_reason' => 2
			];
			//update transaction
			Helper::updateGameTransaction($bet_transaction,$request_data,$type);
			$this->updateGameTransactionExt($transaction_id->game_trans_ext_id,$client_response->requestoclient,$client_response->fundtransferresponse,$response);
			Helper::saveLog('Spade '.$header['API'].' Payout Processed', $this->provider_db_id,  json_encode($details), $response);
	        return $response;
		}catch(\Exception $e){
			$response = [
				"msg" => "System Error",
				"code" => 1
			];
			Helper::saveLog('Spade '.$header['API'].' Payout error', $this->provider_db_id,  json_encode($details), $response);
			return $response;
		}
	}

	public function _cancelbet($details,$header){
		return '_cancelbet';
	}
	public function _bonus($details,$header){
		return '_bonus';
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
