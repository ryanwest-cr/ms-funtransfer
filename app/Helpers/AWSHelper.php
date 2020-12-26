<?php
namespace App\Helpers;

use GuzzleHttp\Client;
use App\Helpers\Helper;
use App\Helpers\GameLobby;
use App\Helpers\ProviderHelper;
use DB;
use DateTime;

class AWSHelper{

	/***************************************************   AWS MAIN HELPER   *************************************************** */

	/**
	 * MERCHANT BACKOFFICE
	 * @author's note : Every Client Should have submerchant in AWS Provider
	 * 
	 */

	public static function findMerchantIdByClientId($client_id){

		$client_key = DB::table('clients')->where('client_id', $client_id)->first();
		if(!$client_key){ return false; }
		$operator_id =  $client_key->operator_id;
		$aws_config = config('providerlinks.aws');

		// return $operator_id.$client_key->default_currency;
		$key_value = $operator_id.$client_key->default_currency;

		if(array_key_exists(($key_value), $aws_config)){
			return $aws_config[$key_value];
		}else{
			return false;
		}
		// if(array_key_exists(($operator_id), $merchant_keys)){
		// 	return $merchant_keys[$operator_id];
		// }else{
		// 	return false;
		// }

		 // $merchant_keys = [
		 // 	'1'=> [ // 
		 //            'merchant_id' => 'TG',
		 //            'merchant_key' => '5819e7a6d0683606e60cd6294edfc4c557a2dd8c9128dd6fbe1d58e77cd8067fead68c48cdb3ea85dcb2e05518bac60412a0914d156a36b4a2ecab359c7adfad',
		 //        ], 
		 //        '2' => [ // ASK THB
		 //            'merchant_id' => 'ASKME',
		 //            'merchant_key' => 'a44c3ca52ef01f55b0a8b3859610f554b05aa57ca36e4a508addd9ddae539a84d43f9407c72d555bc3093bf3516663d504e98b989f3ec3e3ff8407171f43ccdc',
		 //        ],
		 //        '3' => [ // XIGOLO USD
		 //            'merchant_id' => 'XIGOLO',
		 //            'merchant_key' => 'b7943fc2e48c3b74a2c31514aebdce25364bd2b1a97855f290c01831052b25478c35bdebdde8aa7a963e140a8c1e6401102321a2bd237049f9e675352c35c4cc',
		 //        ],
		 //        '4' => [  // ASK ME THB
		 //            'merchant_id' => 'TGC',
		 //            'merchant_key' => 'cb1bc0a2fc16bddfd549bdd8aae0954fba28c9b11c6a25e6ef886b56e846b033ae5fe29880be69fd8741ab400e6c4cb2f8c0f05e49dcc4568362370278ba044d',
		 //        ]
			// ];

		// if(array_key_exists(($operator_id), $merchant_keys)){
		// 	return $merchant_keys[$operator_id];
		// }else{
		// 	return false;
		// }
	}

	/**
	 * MERCHANT BACKOFFICE
	 * @author's note : Register the player to the provider database
	 * @return object
	 * 
	 */
    public static function playerRegister($token, $provider='AllWaySpin', $lang='en')
	{
		$lang = GameLobby::getLanguage($provider,$lang);
		$client_details = ProviderHelper::getClientDetails('token', $token);
		if($client_details == 'false'){
			return false;
		}
		if(!AWSHelper::findMerchantIdByClientId($client_details->client_id)){
			return false;
		}
		$client = new Client([
		    'headers' => [ 
		    	'Content-Type' => 'application/json',
		    ]
		]);
		$merchant_id = AWSHelper::findMerchantIdByClientId($client_details->client_id)['merchant_id'];
		$requesttosend = [
			"merchantId" => $merchant_id,
			"currency" => $client_details->default_currency,
			"currentTime" => AWSHelper::currentTimeMS(),
			"username" => $merchant_id.'_TG'.$client_details->player_id,
		];
		$requesttosend['sign'] = AWSHelper::hashen($requesttosend,$client_details->player_token);
		$requesttosend['language'] = $lang;
		$guzzle_response = $client->post(config('providerlinks.aws.api_url').'/api/register',
		    ['body' => json_encode($requesttosend)]
		);

	    $client_response = json_decode($guzzle_response->getBody()->getContents());
	    AWSHelper::saveLog('AWS BO Register Resp', 21, json_encode($client_response), $requesttosend);
	    return $client_response;
	}


	public  static function getOperationType($operation_type) {
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
	 * MERCHANT BACKOFFICE
	 * @author's note : Check Player Status If Not In Register It
	 * 
	 */
    public static function playerCheck($token)
	{
		$client_details = ProviderHelper::getClientDetails('token', $token);
		if($client_details == 'false'){
			return false;
		}
		if(!AWSHelper::findMerchantIdByClientId($client_details->client_id)){
			return false;
		}
		$merchant_id = AWSHelper::findMerchantIdByClientId($client_details->client_id)['merchant_id'];
		$client = new Client([
		    'headers' => [ 
		    	'Content-Type' => 'application/json',
		    ]
		]);
		$requesttosend = [
			"merchantId" => $merchant_id,
			"currency" => $client_details->default_currency,
			"currentTime" => AWSHelper::currentTimeMS(),
			"username" => $merchant_id.'_TG'.$client_details->player_id,
		];
		$requesttosend['sign'] = AWSHelper::hashen($requesttosend,$client_details->player_token);
		$guzzle_response = $client->post(config('providerlinks.aws.api_url').'/user/status',
		    ['body' => json_encode($requesttosend)]
		);

	    $client_response = json_decode($guzzle_response->getBody()->getContents());
	    AWSHelper::saveLog('AWS BO Player Check Resp', 21, json_encode($client_response), $requesttosend);
	    return $client_response;
	}

    /**
	 * Helper Method
	 * @return MD5 String
	 *
	 */
	public static function hashen($data, $token)
	{	
		$client_details = ProviderHelper::getClientDetails('token', $token);
		if($client_details == 'false'){
			return false;
		}
		if(!AWSHelper::findMerchantIdByClientId($client_details->client_id)){
			return false;
		}

		// $merchant_id = AWSHelper::findMerchantIdByClientId($client_details->client_id)['merchant_id'];
		$base65_key = AWSHelper::findMerchantIdByClientId($client_details->client_id)['merchant_key'];

		if(is_array($data)) {
			$signature = implode('', array_filter($data, function($val){ return !($val === null || (is_array($val) && !$val));}));
        } else {
            $signature = $data;
        }
	    // $merchant_id = $merchant_id!=false?config('providerlinks.aws.merchant_id'):'';
	    $merchant_id ='';
	    $hashen = md5($merchant_id.$signature.base64_encode($base65_key));
		return $hashen;
	}

	/**
	 * Helper Method
	 * @return timestamp with milleseconds
	 *
	 */
	public static function currentTimeMS()
	{
		return (int)$currenttime = round(microtime(true) * 1000);
	}

	/***************************************************  END  AWS MAIN HELPER   *************************************************** */


	/**
	 * GLOBAL
	 * [Transaction Helper]
	 * getBalance (provider that has refreash token should have data game)
	 * 
	 */
	public static function getBalance($token_id){
		// $balance_query = DB::connection('mysql2')->select("SELECT * FROM player_session_tokens WHERE token_id = '".$token_id."'");
		$balance_query = DB::select("SELECT * FROM player_session_tokens WHERE token_id = '".$token_id."'");
		$data = count($balance_query);
		return $data > 0 ?$balance_query[0]:null;
	}



	/* PROVIDER HELPER GLOBAL FUNCTION BUT ISOLATED FOR MANUAL UPDATING THE PROVIDER */

	public static function saveLog($method, $provider_id = 0, $request_data, $response_data) {
		// $micro_date = microtime();
		// $date_array = explode(" ", $micro_date);
		// $date = date("Y-m-d H:i:s", $date_array[1]);
		$now = DateTime::createFromFormat('U.u', microtime(true));
		$data = [
				"method_name" => $method,
				"provider_id" => $provider_id,
				"request_data" => json_encode(json_decode($request_data)),
				"response_data" => json_encode($response_data),
				"created_at" => $now->format("m-d-Y H:i:s.u"),
			];
		// return DB::table('seamless_request_logs')->insertGetId($data);
		// return DB::table('debug')->insertGetId($data);
		if(env('Al_DEBUG')){
			return DB::table('debug')->insert($data);
		}else{
			return DB::table('seamless_request_logs')->insert($data);
		}
	}

	public static function createGameTransaction($token_id, $game_id, $bet_amount, $payout, $entry_id,  $win = 0, $transaction_reason = null, $payout_reason = null, $income = null, $provider_trans_id = null, $round_id = 1)
	{

		$query = DB::select("insert into `game_transactions` (`token_id`, `game_id`, `round_id`, `bet_amount`, `provider_trans_id`, `pay_amount`, `income`, `entry_id`, `win`, `transaction_reason`, `payout_reason`) values ($token_id, $game_id, '$round_id', $bet_amount, '$provider_trans_id', $payout, '$income', $entry_id, $win, '$transaction_reason', '$payout_reason')");

		return DB::connection()->getPdo()->lastInsertId();
	}

	public static function updateBetTransaction($round_id, $pay_amount, $income, $win, $entry_id)
	{
		$reason = ProviderHelper::updateReason($win);
		$update = DB::select("update `game_transactions` set `pay_amount` = $pay_amount, `income` = $income, `win` = $win, `entry_id` = $entry_id, `transaction_reason` = '$reason' where `game_trans_id` = $round_id");
	}


	public  static function updateGameTransactionStatus($game_trans_id, $win, $reason)
	{
		$reason = ProviderHelper::updateReason($reason);
		$update = DB::select("update `game_transactions` set `win` = $win, `transaction_reason` = '$reason' where `game_trans_id` = $game_trans_id");
	}

	public static function createGameTransExtV2($game_trans_id, $provider_trans_id, $round_id, $amount, $game_type, $provider_request = 'FAILED', $mw_response = 'FAILED', $mw_request = 'FAILED', $client_response = 'FAILED', $transaction_detail = 'FAILED', $general_details = null)
	{
		$provider_request = json_encode($provider_request);
		$mw_response = json_encode($mw_response);
		$mw_request = json_encode($mw_request);
		$client_response = json_encode($client_response);
		$transaction_detail = json_encode($transaction_detail);
		$general_details = json_encode($general_details);

		$query = DB::select("insert into `game_transaction_ext` (`game_trans_id`, `provider_trans_id`, `round_id`, `amount`, `game_transaction_type`, `provider_request`, `mw_response`, `mw_request`, `client_response`, `transaction_detail`, `general_details`) values ($game_trans_id,'$provider_trans_id','$round_id',$amount,$game_type,'$provider_request','$mw_response','$mw_request','$client_response','$transaction_detail','$general_details')");

		return DB::connection()->getPdo()->lastInsertId();
	}

	public  static function updatecreateGameTransExt($game_trans_ext_id, $provider_request, $mw_response, $mw_request, $client_response, $transaction_detail, $general_details = 'NO DATA')
	{
		$provider_request = json_encode($provider_request);
		$mw_response = json_encode($mw_response);
		$mw_request = json_encode($mw_request);
		$client_response = json_encode($client_response);
		$transaction_detail = json_encode($transaction_detail);
		$general_details = json_encode($general_details);
		$query = DB::select("update `game_transaction_ext` set `provider_request` = '$provider_request', `mw_response` = '$mw_response', `mw_request` = '$mw_request', `client_response` = '$client_response', `transaction_detail` = '$transaction_detail', `general_details` = '$general_details' where `game_trans_ext_id` = $game_trans_ext_id");
	}

	public static function playerDetailsCall($client_details, $refreshtoken = false)
	{
		$sendtoclient =  microtime(true);
		$client = new Client([
			'headers' => [
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $client_details->client_access_token
			]
		]);
		$datatosend = [
			"access_token" => $client_details->client_access_token,
			"hashkey" => md5($client_details->client_api_key . $client_details->client_access_token),
			"type" => "playerdetailsrequest",
			"datesent" => Helper::datesent(),
			"clientid" => $client_details->client_id,
			"playerdetailsrequest" => [
				"player_username" => $client_details->username,
				"client_player_id" => $client_details->client_player_id,
				"token" => $client_details->player_token,
				"gamelaunch" => true,
				"refreshtoken" => $refreshtoken
			]
		];
		try {
			$guzzle_response = $client->post(
				$client_details->player_details_url,
				['body' => json_encode($datatosend)]
			);
			$client_response = json_decode($guzzle_response->getBody()->getContents());
			$client_response_time = microtime(true) - $sendtoclient;
			AWSHelper::saveLog('playerDetailsCall(HELPER)', 12, json_encode($datatosend), ["sendtoclient" => $sendtoclient,"clientresponse" => $client_response_time]);
			return $client_response;
		} catch (\Exception $e) {
			AWSHelper::saveLog('ALDEBUG client_player_id = ' . $client_details->client_player_id,  99, json_encode($datatosend), $e->getMessage());
			return 'false';
		}
	}


	public static function getClientDetails($type = "", $value = "", $gg=1, $providerfilter='all') {
	    if ($type == 'token') {
		 	$where = 'where pst.player_token = "'.$value.'"';
		}
		if($providerfilter=='fachai'){
		    if ($type == 'player_id') {
				$where = 'where '.$type.' = "'.$value.'" AND pst.status_id = 1 ORDER BY pst.token_id desc';
			}
		}else{
	        if ($type == 'player_id') {
			   $where = 'where '.$type.' = "'.$value.'"';
			}
		}
		if ($type == 'username') {
		 	$where = 'where p.username = "'.$value.'"';
		}
		if ($type == 'token_id') {
			$where = 'where pst.token_id = "'.$value.'"';
		}

		$filter = 'order by token_id desc LIMIT 1';
		
		$query = DB::select('select `p`.`client_id`, `p`.`player_id`, `p`.`email`, `p`.`client_player_id`,`p`.`language`, `p`.`currency`, `p`.`test_player`, `p`.`username`,`p`.`created_at`,`pst`.`token_id`,`pst`.`player_token`,`pst`.`balance`,`c`.`client_url`,`c`.`default_currency`,`pst`.`status_id`,`p`.`display_name`,`op`.`client_api_key`,`op`.`client_code`,`op`.`client_access_token`,`ce`.`player_details_url`,`ce`.`fund_transfer_url`,`p`.`created_at` from player_session_tokens pst inner join players as p using(player_id) inner join clients as c using (client_id) inner join client_endpoints as ce using (client_id) inner join operator as op using (operator_id) '.$where.' '.$filter.'');

		 $client_details = count($query);
		 return $client_details > 0 ? $query[0] : 'false';
	}


	public static function checkTransactionExist($identifier, $transaction_type){
		$query = DB::select('select `game_transaction_type` from game_transaction_ext where `provider_trans_id`  = "'.$identifier.'" AND `game_transaction_type` = "'.$transaction_type.'" LIMIT 1');
    	$data = count($query);
		return $data > 0 ? $query[0] : 'false';
	}


	public static  function findGameTransaction($identifier, $type, $entry_type='') {

    	if ($type == 'transaction_id') {
		 	$where = 'where gt.provider_trans_id = "'.$identifier.'" AND gt.entry_id = '.$entry_type.'';
		}
		if ($type == 'game_transaction') {
		 	$where = 'where gt.game_trans_id = "'.$identifier.'"';
		}
		if ($type == 'round_id') {
			$where = 'where gt.round_id = "'.$identifier.'" AND gt.entry_id = '.$entry_type.'';
		}
	 	
	 	$filter = 'LIMIT 1';
    	$query = DB::select('select *, (select transaction_detail from game_transaction_ext where game_trans_id = gt.game_trans_id order by game_trans_id limit 1) as transaction_detail from game_transactions gt '.$where.' '.$filter.'');
    	$client_details = count($query);
		return $client_details > 0 ? $query[0] : 'false';
    }

	public static function findGameTransID($game_trans_id){
		$query = DB::select('select `game_trans_id`,`token_id`, `provider_trans_id`, `round_id`, `bet_amount`, `win`, `pay_amount`, `income`, `entry_id` from game_transactions where `game_trans_id`  = '.$game_trans_id.' ');
    	$data = count($query);
		return $data > 0 ? $query[0] : 'false';
	}

	public  static function findGameExt($provider_identifier, $game_transaction_type, $type)
	{

		if ($type == 'transaction_id') {
			$where = 'where gte.provider_trans_id = "' . $provider_identifier . '" AND gte.game_transaction_type = ' . $game_transaction_type . ' AND gte.transaction_detail != "FAILED"';
		}
		if ($type == 'round_id') {
			$where = 'where gte.round_id = "' . $provider_identifier . '" AND gte.game_transaction_type = ' . $game_transaction_type . ' AND gte.transaction_detail != "FAILED"';
		}
		if ($type == 'game_transaction_ext_id') {
			$where = 'where gte.provider_trans_id = "' . $provider_identifier . '"';
		}
		if ($type == 'game_trans_id') {
			$where = 'where gte.game_trans_id = "' . $provider_identifier . '"';
		}

		$filter = 'LIMIT 1';

		$query = DB::select('select * from game_transaction_ext as gte ' . $where . ' ' . $filter . '');
		$data = count($query);
		return $data > 0 ? $query[0] : 'false';
	}

	// public static function findGameExt($provider_transaction_id, $game_transaction_type, $type) {
	// 	$transaction_db = DB::table('game_transaction_ext as gte');
    //     if ($type == 'transaction_id') {
	// 		$transaction_db->where([
	// 	 		["gte.provider_trans_id", "=", $provider_transaction_id],
	// 	 		["gte.game_transaction_type", "=", $game_transaction_type],
	// 	 	]);
	// 	}
	// 	if ($type == 'round_id') {
	// 		$transaction_db->where([
	// 	 		["gte.round_id", "=", $provider_transaction_id],
	// 	 		["gte.game_transaction_type", "=", $game_transaction_type],
	// 	 	]);
	// 	}  
	// 	$result= $transaction_db->first();
	// 	return $result ? $result : 'false';
	// }


	// public static function findGameDetails($type, $provider_id, $identification) {
	// 	if ($type == "game_code") {
	// 		$details = "where g.provider_id = ".$provider_id." and g.game_code = '".$identification."' limit 1";
	// 	}
	// 	$game_details = DB::select('select g.game_name, g.game_code, g.game_id from games g left join providers as p using (provider_id) '.$details.' ');
		
	//  	return $game_details ? $game_details : "false";
	// }

	public static function findGameDetails($type, $provider_id, $game_code)
	{
		$query = DB::Select("SELECT game_id,game_code,game_name FROM games WHERE game_code = '" . $game_code . "' AND provider_id = '" . $provider_id . "'");
		$result = count($query);
		return $result > 0 ? $query[0] : null;
	}

	public static function getProviderCurrency($provider_id, $currency)
	{
		$provider_currencies = DB::table("providers")->where("provider_id", $provider_id)->get();
		$currencies = json_decode($provider_currencies[0]->currencies, TRUE);
		if (array_key_exists($currency, $currencies)) {
			return $currencies[$currency];
		} else {
			return 'false';
		}
	}


	/***************************************************  EXPERIMENTAL   *************************************************** */

	# EXPERIMENTAL FINALLY SETUP
	public static function playerDetailsCall_inhouse($client_details){
		$player_details = DB::select("SELECT * FROM player_session_tokens WHERE token_id = '".$client_details->token_id."'");
		$data = count($player_details);
		if($data > 0){
			$in_house_player_details = [
				'playerdetailsresponse' => [
					'balance' => $player_details[0]->balance
				]
			];
			return json_decode(json_encode($in_house_player_details));
		}else{
			return false;
		}
	}

	/**
	 * GLOBAL
	 * [Transaction Helper]
	 * saveBalance (provider that has refreash token should have data gametoken)
	 * 
	 */
	public static function saveBalance($token){
		$client_details = AWSHelper::getClientDetails('token', $token);
		if($client_details){
			$client = new Client([
			    'headers' => [ 
			    	'Content-Type' => 'application/json',
			    	'Authorization' => 'Bearer '.$client_details->client_access_token
			    ]
			]);
			$datatosend = ["access_token" => $client_details->client_access_token,
				"hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
				"type" => "playerdetailsrequest",
				"datesent" => Helper::datesent(),
                "gameid" => "",
				"clientid" => $client_details->client_id,
				"playerdetailsrequest" => [
					"player_username"=>$client_details->username,
					"client_player_id" => $client_details->client_player_id,
					"token" => $token,
					"gamelaunch" => true,
					"refreshtoken" => false
				]
			];
		}
		try{	
			$guzzle_response = $client->post($client_details->player_details_url,
				['body' => json_encode($datatosend)]
			);
			$client_response = json_decode($guzzle_response->getBody()->getContents());
			AWSHelper::saveLog('PLAYER DETAILS LOG', 999, json_encode($client_response), $datatosend);
			if(isset($client_response->playerdetailsresponse->status->code) && $client_response->playerdetailsresponse->status->code == 200){
				AWSHelper::_insertOrUpdate($client_details->token_id,$client_response->playerdetailsresponse->balance);
				return true;
			}else{
				return false;
			}
		}catch (\Exception $e){
			return false;
		 }
	}

	public static function _insertOrUpdate($token_id,$balance){
		$balance_query = DB::select("SELECT * FROM player_session_tokens WHERE token_id = '".$token_id."'");
		$data = count($balance_query);
		if($data > 0){
			return DB::select("UPDATE player_session_tokens SET balance=".$balance." WHERE token_id ='".$token_id."'");
		}
		else{
			return DB::select("INSERT INTO  player_session_tokens (token_id,balance) VALUEs ('".$token_id."',".$balance.")");
		}
	}

}