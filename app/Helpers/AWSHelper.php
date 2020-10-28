<?php
namespace App\Helpers;

use GuzzleHttp\Client;
use App\Helpers\Helper;
use App\Helpers\GameLobby;
use App\Helpers\ProviderHelper;
use DB; 

class AWSHelper{

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

		if(array_key_exists(($operator_id), $aws_config)){
			return $aws_config[$operator_id];
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

}