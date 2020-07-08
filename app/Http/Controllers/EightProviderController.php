<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\CallParameters;
use App\Helpers\ProviderHelper;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;

use DB;


/**
 * 8Provider (API Version 2 POST DATA METHODS)
 *
 * @version 1.0
 * @method register
 *
 */
class EightProviderController extends Controller
{

	public $api_url = 'http://api.8provider.com';
	public $secret_key = 'c270d53d4d83d69358056dbca870c0ce';
	public $project_id = '1042';

	// return md5('1042*1*c270d53d4d83d69358056dbca870c0ce');
	// 97e464b9b6e4d2de3b5d36facffa9556

    /**
     * GetSignature 
     * @return string
     *
     */
	public function getSignature(array $args, $system_key){
	    $md5 = array();
	    $args = array_filter($args, function($val){ return !($val === null || (is_array($val) && !$val));});
	    foreach ($args as $required_arg) {
	        $arg = $required_arg;
	        if (is_array($arg)) {
	            $md5[] = implode(':', array_filter($arg, function($val){ return !($val === null || (is_array($val) && !$val));}));
	        } else {
	            $md5[] = $arg;
	        }
	    };
	    $md5[] = $system_key;
	    $md5_str = implode('*', $md5);
	    $md5 = md5($md5_str);
	    return $md5;
	}

	/**
	 * @author's note single method that will consume different API call
	 * @param name = bet, win, refund,
	 * 
	 */
	public function index(Request $request){

		Helper::saveLog('8P index FORMDATA', 19, json_encode($request->all()), 'ENDPOINT HIT');
		if($request->name == 'init'){

			$game_init = $this->gameInitialize($request->all());
			return json_encode($game_init);

		}elseif($request->name == 'bet'){
				
			$bet_handler = $this->gameBet($request->all());
			return json_encode($bet_handler);

		}elseif($request->name == 'win'){

			$win_handler = $this->gameWin($request->all());
			return json_encode($win_handler);

		}elseif($request->name == 'refund'){

			$refund_handler = $this->gameRefund($request->all());
			return json_encode($refund_handler);
		}

	}



	/**
	 * @param data [array]
	 * NOTE APPLY FILTER BALANCE
	 * 
	 */
	public function gameBet($data){

		    // return $details = $data;
		    // $newStr = str_replace("\\", '', $details);
		    // $fist_encode = json_encode($newStr, false);
		    // return gettype($fourth_encode);

		    $client_details = ProviderHelper::getClientDetails('token', $data['token']);
		    // $player_details = ProviderHelper::playerDetailsCall($data['token']);
		  	$requesttosend = [
			  "access_token" => $client_details->client_access_token,
			  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
			  "type" => "fundtransferrequest",
			  "datesent" => Helper::datesent(),
			  "gamedetails" => [
			    "gameid" =>  "",
			    "gamename" => ""
			  ],
			  "fundtransferrequest" => [
					"playerinfo" => [
					"token" => $data['token'],
				],
				"fundinfo" => [
				      "gamesessionid" => "",
				      "transactiontype" => 'debit',
				      "rollback" => "false",
				      "currencycode" => $client_details->default_currency,
				      "amount" => $data['data']['amount']
				]
			  ]
			];
			try {
				$client = new Client([
                    'headers' => [ 
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$client_details->client_access_token
                    ]
                ]);
				$guzzle_response = $client->post($client_details->fund_transfer_url,
					['body' => json_encode($requesttosend)]
				);
				$client_response = json_decode($guzzle_response->getBody()->getContents());
				$response = array(
					'status' => 'ok',
					'data' => [
						'balance' => $client_response->fundtransferresponse->balance,
						'currency' => $client_details->default_currency,
					],
			 	 );
			  	return $response;

			}catch(\Exception $e){
				return array(
					"status" => 'failed',
					"message" => $e->getMessage(),
				);
			}
	}



	public function gameWin(Request $request){

		$client_details = ProviderHelper::getClientDetails('token', $data['token']);
		$requesttosend = [
			  "access_token" => $client_details->client_access_token,
			  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
			  "type" => "fundtransferrequest",
			  "datesent" => Helper::datesent(),
			  "gamedetails" => [
			    "gameid" =>  "",
			    "gamename" => ""
			  ],
			  "fundtransferrequest" => [
					"playerinfo" => [
					"token" => $data['token'],
				],
				"fundinfo" => [
				      "gamesessionid" => "",
				      "transactiontype" => 'credit',
				      "rollback" => "false",
				      "currencycode" => $client_details->default_currency,
				      "amount" => $data['data']['amount']
				]
			  ]
			];
			try {
				$client = new Client([
                    'headers' => [ 
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$client_details->client_access_token
                    ]
                ]);
				$guzzle_response = $client->post($client_details->fund_transfer_url,
					['body' => json_encode($requesttosend)]
				);
				$client_response = json_decode($guzzle_response->getBody()->getContents());
				$response = array(
					'status' => 'ok',
					'data' => [
						'balance' => $client_response->fundtransferresponse->balance,
						'currency' => $client_details->default_currency,
					],
			 	 );
			  	return $response;

			}catch(\Exception $e){
				return array(
					"status" => 'failed',
					"message" => $e->getMessage(),
				);
			}

	}


	public function gameInitialize($data){

		$player_details = ProviderHelper::playerDetailsCall($data['token']);
		$client_details = ProviderHelper::getClientDetails('token', $data['token']);

		$response = array(
			'status' => 'ok',
			'data' => [
				'balance' => $player_details->playerdetailsresponse->balance,
				'currency' => $client_details->default_currency,
			],
	 	 );
	  	return $response;
	}

	
	public function gameRefund(Request $request){
		    $client_details = ProviderHelper::getClientDetails('token', $data['token']);
		    // $player_details = ProviderHelper::playerDetailsCall($data['token']);
		  	$requesttosend = [
			  "access_token" => $client_details->client_access_token,
			  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
			  "type" => "fundtransferrequest",
			  "datesent" => Helper::datesent(),
			  "gamedetails" => [
			    "gameid" =>  "",
			    "gamename" => ""
			  ],
			  "fundtransferrequest" => [
					"playerinfo" => [
					"token" => $data['token'],
				],
				"fundinfo" => [
				      "gamesessionid" => "",
				      "transactiontype" => 'debit',
				      "rollback" => "true",
				      "currencycode" => $client_details->default_currency,
				      "amount" => $data['data']['amount']
				]
			  ]
			];
			try {
				$client = new Client([
                    'headers' => [ 
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$client_details->client_access_token
                    ]
                ]);
				$guzzle_response = $client->post($client_details->fund_transfer_url,
					['body' => json_encode($requesttosend)]
				);
				$client_response = json_decode($guzzle_response->getBody()->getContents());
				$response = array(
					'status' => 'ok',
					'data' => [
						'balance' => $client_response->fundtransferresponse->balance,
						'currency' => $client_details->default_currency,
					],
			 	 );
			  	return $response;

			}catch(\Exception $e){
				return array(
					"status" => 'failed',
					"message" => $e->getMessage(),
				);
			}
	}

	public function registerBunos(){
		// $client = new Client([
  //           'headers' => [ 
  //               'Content-Type' => 'application/x-www-form-urlencoded',
  //           ]
  //       ]);
		// $response = $client->post('http://api.8provider.com/game/registerbonus',[
		// 	'form_params' => [
		// 		  "project" => $this->project_id,
	 //              "version" => 2,
		// 		  "token" => 'j45h67j45h7g45k45j7hk45j74k57g4k5jg74k574k7j4k57',
		// 		  "game" => '99',
		// 		  'currency'=>'USD',
		// 		  "settings" => ['user_id' => '61'],
		// 		  "extra_bonuses" => [],
		// 		  "signature" => md5('1042*2*j45hg67j45h7g45k45j7hk45j74k57g4k5jg74k574k7j4k57*USD*99*61*c270d53d4d83d69358056dbca870c0ce'),
		// 	],
		// ]);
  //       $res = json_decode($response->getBody(),TRUE);
  //        return $res;
	}


	/**
	 *  TEST
	 * 
	 */
	public function gameDeposit(Request $request){
		// Helper::saveLog('8P Deposit', 19, 19, 'ENDPOINT HIT');
		// $response = array(
		// 	'status' => 'ok',
		// 	'data' => [
		// 		'balance' => 456455.66,
		// 		'currency' => 'USD',
		// 	],
		// );
	
		return $response;
	}

	/**
	 *  TEST
	 * 
	 */
	public function gameWithdrawal(Request $request){
		Helper::saveLog('8P Withdrawal', 19, 19, 'ENDPOINT HIT');
		$response = array(
			'status' => 'ok',
			'data' => [
				'balance' => 456455.66,
				'currency' => 'USD',
			],
		);
		return $response;
	}
	

	
}
