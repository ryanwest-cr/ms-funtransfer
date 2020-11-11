<?php
namespace App\Helpers;

use GuzzleHttp\Client;
use App\Helpers\Helper;
use App\Helpers\GameLobby;
use App\Helpers\ProviderHelper;
use DB; 

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
	    Helper::saveLog('AWS BO Register Resp', 21, json_encode($client_response), $requesttosend);
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
	    Helper::saveLog('AWS BO Player Check Resp', 21, json_encode($client_response), $requesttosend);
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


	/**
	 * HELPER
	 * Create Game Transaction
	 * 
	 */
	public static function createGameTransaction($token_id, $game_id, $bet_amount, $payout, $entry_id,  $win=0, $transaction_reason = null, $payout_reason = null , $income=null, $provider_trans_id=null, $round_id=1) {
		$data = [
					"token_id" => $token_id,
					"game_id" => $game_id,
					"round_id" => $round_id,
					"bet_amount" => $bet_amount,
					"provider_trans_id" => $provider_trans_id,
					"pay_amount" => $payout,
					"income" => $income,
					"entry_id" => $entry_id,
					"win" => $win,
					"transaction_reason" => $transaction_reason,
					"payout_reason" => $payout_reason
				];
		$data_saved = DB::table('game_transactions')->insertGetId($data);
		return $data_saved;
	}



	/***************************************************  END  AWS MAIN HELPER   *************************************************** */










	/* PROVIDER HELPER GLOBAL FUNCTION BUT ISOLATED FOR MANUAL UPDATING THE PROVIDER */


	public static function saveLog($method, $provider_id = 0, $request_data, $response_data) {
		$data = [
				"method_name" => $method,
				"provider_id" => $provider_id,
				"request_data" => json_encode(json_decode($request_data)),
				"response_data" => json_encode($response_data)
			];
		// return DB::table('seamless_request_logs')->insertGetId($data);
		return DB::table('debug')->insertGetId($data);
	}


	public static function playerDetailsCall($client_details, $refreshtoken = false)
	{
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
			return $client_response;
		} catch (\Exception $e) {
			Helper::saveLog('ALDEBUG client_player_id = ' . $client_details->client_player_id,  99, json_encode($datatosend), $e->getMessage());
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
		
		$query = DB::select('select `p`.`client_id`, `p`.`player_id`, `p`.`email`, `p`.`client_player_id`,`p`.`language`, `p`.`currency`, `p`.`test_player`, `p`.`username`,`p`.`created_at`,`pst`.`token_id`,`pst`.`player_token`,`c`.`client_url`,`c`.`default_currency`,`pst`.`status_id`,`p`.`display_name`,`op`.`client_api_key`,`op`.`client_code`,`op`.`client_access_token`,`ce`.`player_details_url`,`ce`.`fund_transfer_url`,`p`.`created_at` from player_session_tokens pst inner join players as p using(player_id) inner join clients as c using (client_id) inner join client_endpoints as ce using (client_id) inner join operator as op using (operator_id) '.$where.' '.$filter.'');

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


	public static function findGameExt($provider_transaction_id, $game_transaction_type, $type) {
		$transaction_db = DB::table('game_transaction_ext as gte');
        if ($type == 'transaction_id') {
			$transaction_db->where([
		 		["gte.provider_trans_id", "=", $provider_transaction_id],
		 		["gte.game_transaction_type", "=", $game_transaction_type],
		 	]);
		}
		if ($type == 'round_id') {
			$transaction_db->where([
		 		["gte.round_id", "=", $provider_transaction_id],
		 		["gte.game_transaction_type", "=", $game_transaction_type],
		 	]);
		}  
		$result= $transaction_db->first();
		return $result ? $result : 'false';
	}


	public static function findGameDetails($type, $provider_id, $identification) {
		if ($type == "game_code") {
			$details = "where g.provider_id = ".$provider_id." and g.game_code = ".$identification." limit 1";
		}
		$game_details = DB::select('select g.game_name, g.game_code, g.game_id from games g left join providers as p using (provider_id) '.$details.' ');
		
	 	return $game_details ? $game_details : "false";
	}


}