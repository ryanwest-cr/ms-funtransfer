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
		$aws_config = config('providerlinks.aws');

		if(array_key_exists(($client_id), $aws_config)){
			return $aws_config[$client_id];
		}else{
			return false;
		}
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