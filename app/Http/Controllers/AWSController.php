<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use App\Helpers\AWSHelper;
use App\Helpers\GameLobby;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;
use DB;


/**
 * @author's note : There are two kinds of method in here Single Wallet Callback and Backoffice Calls
 * @author's note : Backoffice call for directly communicate the Provider Backoffice!
 * @author's note : Single Wallet call is the main methods tobe checked!
 * @author's note : Username/Player is Prefixed with the merchant_id_TG(player_id)
 * 
 */
class AWSController extends Controller
{

	public $api_url = 'https://sapi.shisaplay.com';
    public $merchant_id = 'TGUSD';
    public $merchant_key = '7e7b86f44fa240ccffaee944e190cce9d99d0510debf357a073b915eff301d2573ea38c98dd0b30cae7cb4f9d32299281ca132698e2b2a7dc503f28ce9135a3c';

	/**
	 * SINGLE WALLET
	 * @author's note : Player Single Balance 
	 *
	 */
	public function singleBalance(Request $request)
	{
		$data = file_get_contents("php://input");
		$details = json_decode($data);
		Helper::saveLog('AWS Single Balance', 21, $data, 'ENDPOINT HIT');
		$prefixed_username = explode("_TG", $details->accountId);
		$client_details = Providerhelper::getClientDetails('player_id', $prefixed_username[1]);
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
		$response = [
			"msg"=> "success",
			"code"=> 0,
			"data"=> [
				"currency"=> $client_details->default_currency,
				"balance"=> $player_details->playerdetailsresponse->balance,
				"bonusBalance"=> 0
			]
		];
		return $response;
	}

	/**
	 * SINGLE WALLET
	 * @author's note : Player Single Fund Transfer 
	 *
	 */
	public function singleFundTransfer(Request $request){
		$data = file_get_contents("php://input");
		$details = json_decode($data);
		Helper::saveLog('AWS Single Fund Transfer', 21, $data, 'ENDPOINT HIT');
		$prefixed_username = explode("_TG", $details->accountId);
		$client_details = Providerhelper::getClientDetails('player_id', $prefixed_username[1]);
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
		$response = [
			"msg"=> "success",
			"code"=> 0,
			"data"=> [
				"currency"=> "CNY",
				"amount"=> 0.1,
				"accountId"=> "AWS_A1234",
				"txnId"=> "AWS_1362340549386498",
				"eventTime"=> "2019-09-27 16=>50:53",
				"balance" => 91263.98,
				"bonusBalance" => 0
			]
		];
		return $response;
	}

	/**
	 * SINGLE WALLET
	 * @author's note : Player Single Fund Query 
	 *
	 */
	public function singleFundQuery(Request $request){
		$data = file_get_contents("php://input");
		$details = json_decode($data);
		Helper::saveLog('AWS Single Fund Query', 21, $data, 'ENDPOINT HIT');
		$prefixed_username = explode("_TG", $details->accountId);
		$client_details = Providerhelper::getClientDetails('player_id', $prefixed_username[1]);
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
		$response = [
			"msg"=> "success",
			"code"=> 0,
			"data"=> [
				"currency"=> "CNY",
				"amount"=> 0.1,
				"accountId"=> "AWS_A1234",
				"txnId"=> "AWS_1362340549386498",
				"eventTime"=> "2019-09-27 16=>50:53",
				"balance" => 91263.98,
				"bonusBalance" => 0
			]
		];
		return $response;
	}

	/**
	 * MERCHANT BACKOFFICE
	 * @author's note : this is centralized in the gamelaunch (DEPRECATED) 
	 *
	 */
	public function playerRegister(Request $request)
	{
	   $register_player = AWSHelper::playerRegister($request->token);
	   $register_player->code == 2217 || $register_player->code == 0;
	}
	
	/**
	 * MERCHANT BACKOFFICE
	 * @author's NOTE : Launch Game (DEPRECATED/CENTRALIZED)
	 *
	 */
	public function launchGame(Request $request){
		$lang = GameLobby::getLanguage('All Way Spin','en');
		$client_details = ProviderHelper::getClientDetails('token', $request->token);
		$client = new Client([
		    'headers' => [ 
		    	'Content-Type' => 'application/json',
		    ]
		]);
		$requesttosend = [
			"merchantId" => $this->merchant_id,
			"currentTime" => AWSHelper::currentTimeMS(),
			"username" => $this->merchant_id.'_TG'.$client_details->player_id,
			"playmode" => 0, // Mode of gameplay, 0: official
			"device" => 1, // Identifying the device. Device, 0: mobile device 1: webpage
			"gameId" => 'AWS_1',
			"language" => $lang,
		];
		$requesttosend['sign'] = AWSHelper::hashen($requesttosend);
		$guzzle_response = $client->post($this->api_url.'/api/login',
		    ['body' => json_encode($requesttosend)]
		);
	    $provider_response = json_decode($guzzle_response->getBody()->getContents());
	    Helper::saveLog('AWS BO Launch Game', 21, json_encode($requesttosend), $provider_response);
	    return isset($provider_response->data->gameUrl) ? $provider_response->data->gameUrl : 'false';
	}


	/**
	 * MERCHANT BACKOFFICE
	 * @author's NOTE : Get All Game List
	 *
	 */
	public function gameList(Request $request){
		$client = new Client([
		    'headers' => [ 
		    	'Content-Type' => 'application/json',
		    ]
		]);
		$requesttosend = [
			"merchantId" => $this->merchant_id,
			"currentTime" => AWSHelper::currentTimeMS(),
			"sign" => $this->hashen(AWSHelper::currentTimeMS()),
			"language" => 'en_US'
		];
		$guzzle_response = $client->post($this->api_url.'/game/list',
		    ['body' => json_encode($requesttosend)]
		);
	    $client_response = json_decode($guzzle_response->getBody()->getContents());
	    dd($client_response);
	}


	/**
	 * MERCHANT BACKOFFICE
	 * @author's NOTE : This is only if we want to disable/enable a player on this provider 
	 * @param   $[request->status] [<enable or disable>]
	 *
	 */
	public function playerManage(Request $request){
		Helper::saveLog('AWS BO Player Manage', 21, file_get_contents("php://input"), 'ENDPOINT HIT');
		$status = $request->has('status') ? $request->status : 'enable';
		$client_details = ProviderHelper::getClientDetails('token', $request->token);
		$client = new Client([
		    'headers' => [ 
		    	'Content-Type' => 'application/json',
		    ]
		]);
		$requesttosend = [
			"merchantId" => $this->merchant_id,
			"currentTime" => AWSHelper::currentTimeMS(),
			"username" => $this->merchant_id.'_'.$client_details->player_id,
			"sign" => $this->hashen($this->merchant_id.'_'.$client_details->player_id, AWSHelper::currentTimeMS()),
		];
		$guzzle_response = $client->post($this->api_url.'/user/'.$status,
		    ['body' => json_encode($requesttosend)]
		);
	    $client_response = json_decode($guzzle_response->getBody()->getContents());
	    return $client_response;
	}

	/**
	 * MERCHANT BACKOFFICE
	 * @author's NOTE : This is only if we want to check the player status on this provider
	 *
	 */
	public function playerStatus(Request $request){
		Helper::saveLog('AWS BO Player Status', 21, file_get_contents("php://input"), 'ENDPOINT HIT');
		$status = $request->has('status') ? $request->status : 'enable';
		$client_details = ProviderHelper::getClientDetails('token', $request->token);
		$client = new Client([
		    'headers' => [ 
		    	'Content-Type' => 'application/json',
		    ]
		]);
		$requesttosend = [
			"merchantId" => $this->merchant_id,
			"currentTime" => AWSHelper::currentTimeMS(),
			"username" => $this->merchant_id.'_'.$client_details->player_id,
			"sign" => $this->hashen($this->merchant_id.'_'.$client_details->player_id, AWSHelper::currentTimeMS()),
		];
		$guzzle_response = $client->post($this->api_url.'/user/status',
		    ['body' => json_encode($requesttosend)]
		);
	    $client_response = json_decode($guzzle_response->getBody()->getContents());
	    return $client_response;
	}

	/**
	 * MERCHANT BACKOFFICE
	 * @author's NOTE : This is only if we want to check the player balance on this provider
	 *
	 */
	public function playerBalance(Request $request){
		Helper::saveLog('AWS BO Player Balance', 21, file_get_contents("php://input"), 'ENDPOINT HIT');
		$client_details = ProviderHelper::getClientDetails('token', $request->token);
		$client = new Client([
		    'headers' => [ 
		    	'Content-Type' => 'application/json',
		    ]
		]);
		$requesttosend = [
			"merchantId" => $this->merchant_id,
			"currentTime" => AWSHelper::currentTimeMS(),
			"username" => $this->merchant_id.'_'.$client_details->player_id,
			"sign" => $this->hashen($this->merchant_id.'_'.$client_details->player_id, AWSHelper::currentTimeMS()),
		];
		$guzzle_response = $client->post($this->api_url.'/user/balance',
		    ['body' => json_encode($requesttosend)]
		);
	    $client_response = json_decode($guzzle_response->getBody()->getContents());
	    return $client_response;
	}

	/**
	 * MERCHANT BACKOFFICE
	 * @author's NOTE : Fund Transfer
	 *
	 */
	public function fundTransfer(Request $request){
		Helper::saveLog('AWS BO Fund Transfer', 21, file_get_contents("php://input"), 'ENDPOINT HIT');
		$client_details = ProviderHelper::getClientDetails('token', $request->token);
		$client = new Client([
		    'headers' => [ 
		    	'Content-Type' => 'application/json',
		    ]
		]);
		$requesttosend = [
			"merchantId" => $this->merchant_id,
			"currentTime" => AWSHelper::currentTimeMS(),
			"username" => $this->merchant_id.'_'.$client_details->player_id,
			"amount" => $request->amount,
			"merchantTransId" => 'AWSF2019123199999', // for each player account, the transfer transaction code has to be unique
			"sign" => $this->hashen($this->merchant_id.'_'.$client_details->player_id, AWSHelper::currentTimeMS()),
		];
		$guzzle_response = $client->post($this->api_url.'/user/balance',
		    ['body' => json_encode($requesttosend)]
		);
	    $client_response = json_decode($guzzle_response->getBody()->getContents());
	    return $client_response;
	}

	/**
	 * MERCHANT BACKOFFICE
	 * @author's NOTE : Query status of fund transfer
	 *
	 */
	public function queryStatus(Request $request){
		Helper::saveLog('AWS BO Query Status', 21, file_get_contents("php://input"), 'ENDPOINT HIT');
		$client_details = ProviderHelper::getClientDetails('token', $request->token);
		$client = new Client([
		    'headers' => [ 
		    	'Content-Type' => 'application/json',
		    ]
		]);
		$requesttosend = [
			"merchantId" => $this->merchant_id,
			"currentTime" => AWSHelper::currentTimeMS(),
			"username" => $this->merchant_id.'_'.$client_details->player_id,
			"merchantTransId" => 'AWSF2019123199999', // for each player account, the transfer transaction code has to be unique
			"sign" => $this->hashen($this->merchant_id.'_'.$client_details->player_id, AWSHelper::currentTimeMS()),
		];
		$guzzle_response = $client->post($this->api_url.'/fund/queryStatus',
		    ['body' => json_encode($requesttosend)]
		);
	    $client_response = json_decode($guzzle_response->getBody()->getContents());
	    return $client_response;
	}

	/**
	 * MERCHANT BACKOFFICE
	 * @author's NOTE : Query status of fund transfer
	 *
	 */
	public function queryOrder(Request $request){
		Helper::saveLog('AWS BO Query Order', 21, file_get_contents("php://input"), 'ENDPOINT HIT');
		$client_details = ProviderHelper::getClientDetails('token', $request->token);
		$client = new Client([
		    'headers' => [ 
		    	'Content-Type' => 'application/json',
		    ]
		]);
		$requesttosend = [
			"merchantId" => $this->merchant_id,
			"currentTime" => AWSHelper::currentTimeMS(),
			"username" => $this->merchant_id.'_'.$client_details->player_id,
			"sign" => $this->hashen($this->merchant_id.'_'.$client_details->player_id, AWSHelper::currentTimeMS()),
		];
		$guzzle_response = $client->post($this->api_url.'/order/query',
		    ['body' => json_encode($requesttosend)]
		);
	    $client_response = json_decode($guzzle_response->getBody()->getContents());
	    return $client_response;
	}


	/**
	 * MERCHANT BACKOFFICE
	 * @author's NOTE : Logout the player
	 *
	 */
	public function playerLogout(Request $request){
		Helper::saveLog('AWS BO Player Logout', 21, file_get_contents("php://input"), 'ENDPOINT HIT');
		$client_details = ProviderHelper::getClientDetails('token', $request->token);
		$client = new Client([
		    'headers' => [ 
		    	'Content-Type' => 'application/json',
		    ]
		]);
		$requesttosend = [
			"merchantId" => $this->merchant_id,
			"currentTime" => AWSHelper::currentTimeMS(),
			"username" => $this->merchant_id.'_'.$client_details->player_id,
			"sign" => $this->hashen($this->merchant_id.'_'.$client_details->player_id, AWSHelper::currentTimeMS()),
		];
		$guzzle_response = $client->post($this->api_url.'/api/logout',
		    ['body' => json_encode($requesttosend)]
		);
	    $client_response = json_decode($guzzle_response->getBody()->getContents());
	    return $client_response;
	}

	
}
