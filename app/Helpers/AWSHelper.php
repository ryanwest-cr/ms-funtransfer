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
	 * @author's note : Register the player to the provider database
	 * @return object
	 * 
	 */
    public static function playerRegister($token, $provider='AllWaySpin', $lang='en')
	{
		$lang = GameLobby::getLanguage($provider,$lang);
		$client_details = ProviderHelper::getClientDetails('token', $token);
		$client = new Client([
		    'headers' => [ 
		    	'Content-Type' => 'application/json',
		    ]
		]);
		$requesttosend = [
			"merchantId" => config('providerlinks.aws.merchant_id'),
			"currency" => $client_details->default_currency,
			"currentTime" => AWSHelper::currentTimeMS(),
			"username" => config('providerlinks.aws.merchant_id').'_TG'.$client_details->player_id,
		];
		$requesttosend['sign'] = AWSHelper::hashen($requesttosend);
		$requesttosend['language'] = $lang;
		$guzzle_response = $client->post(config('providerlinks.aws.api_url').'/api/register',
		    ['body' => json_encode($requesttosend)]
		);

	    $client_response = json_decode($guzzle_response->getBody()->getContents());
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
		$client = new Client([
		    'headers' => [ 
		    	'Content-Type' => 'application/json',
		    ]
		]);
		$requesttosend = [
			"merchantId" => config('providerlinks.aws.merchant_id'),
			"currency" => $client_details->default_currency,
			"currentTime" => AWSHelper::currentTimeMS(),
			"username" => config('providerlinks.aws.merchant_id').'_TG'.$client_details->player_id,
		];
		$requesttosend['sign'] = AWSHelper::hashen($requesttosend);
		// $requesttosend['language'] = $lang;
		$guzzle_response = $client->post(config('providerlinks.aws.api_url').'/user/status',
		    ['body' => json_encode($requesttosend)]
		);

	    $client_response = json_decode($guzzle_response->getBody()->getContents());
	    return $client_response;
	}

    /**
	 * Helper Method
	 * @return MD5 String
	 *
	 */
	public static function hashen($data, $merchant_id=false)
	{	
		if(is_array($data)) {
			$signature = implode('', array_filter($data, function($val){ return !($val === null || (is_array($val) && !$val));}));
        } else {
            $signature = $data;
        }
	    $merchant_id = $merchant_id!=false?config('providerlinks.aws.merchant_id'):'';
	    $hashen = md5($merchant_id.$signature.base64_encode(config('providerlinks.aws.merchant_key')));
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