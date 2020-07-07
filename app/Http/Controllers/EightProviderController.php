<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\CallParameters;

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
	    // return $md5_str;	
	    $md5 = md5($md5_str);

	    return $md5;
	}

	public function index(){
		Helper::saveLog('8P gameBet', 19, 19, 'ENDPOINT HIT');
		Helper::saveLog('8P BET', 19, file_get_contents("php://input"), 'ENDPOINT HIT');
		Helper::saveLog('8P BET FORMDATA', 19, json_encode($request->all()), 'ENDPOINT HIT');

		
		return 'endpoint reached';
	}


	public function getGames(){

		$requesttosend = [
		  "project" => $this->project_id,
		  "version" => 2,
		];
		$signature = $this->getSignature($requesttosend, $this->secret_key);
		$requesttosend['signature'] = $signature;
		$client = new Client();
		$response = $client->post('http://api.8provider.com/game/getlist',[
				'form_params' => $requesttosend,
		]);

        return $res = json_decode($response->getBody(),TRUE);

	}

	//http://api.8provider.com/game/getlist?project=1&version=1&signature=5a4174196eb3b134f23c56deac02ac53 
	// public function gameUrl(Request $request){
	// 	Helper::saveLog('8P Game Init', 14, 14, 'ENDPOINT HIT');
	// 	$requesttosend = [
	// 	  "project" => $this->project_id,
 //          "version" => 1,
	// 	  "token" => 'j45h67j45h7g45k45j7hk45j74k57g4k5jg74k574k7j4k57',
	// 	  "game" => '98',
	// 	  "settings" =>  ['user_id'=>'61','language'=>'en'],
	// 	  "denomination" => '0.1',
	// 	  "currency" => "USD",
	// 	  "return_url_info" => true,
 //          "callback_version" => 2,
	// 	  "settings" =>  [
	// 		  "user_id"=>'61',
	// 		  "language"=>'en',
	// 	   ],
	// 	];
	// 	$signature = $this->getSignature($requesttosend, $this->secret_key);
	// 	$requesttosend['signature'] = $signature;
 //        $client = new Client([
 //            'headers' => [ 
 //                'Content-Type' => 'application/x-www-form-urlencoded',
 //            ]
 //        ]);
 //        $response = $client->post('http://api.8provider.com/game/geturl',[
	// 		'form_params' => $requesttosend,
	// 	]);
 //        $res = json_decode($response->getBody(),TRUE);
 //        dd($res);
	// }


	public function registerBunos(){
		$client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        ]);
		$response = $client->post('http://api.8provider.com/game/registerbonus',[
			'form_params' => [
				  "project" => $this->project_id,
	              "version" => 2,
				  "token" => 'j45h67j45h7g45k45j7hk45j74k57g4k5jg74k574k7j4k57',
				  "game" => '99',
				  'currency'=>'USD',
				  "settings" => ['user_id' => '61'],
				  "extra_bonuses" => [],
				  "signature" => md5('1042*2*j45hg67j45h7g45k45j7hk45j74k57g4k5jg74k574k7j4k57*USD*99*61*c270d53d4d83d69358056dbca870c0ce'),
			],
		]);
        $res = json_decode($response->getBody(),TRUE);
         return $res;
	}

	public function gameBet(Request $request){
		Helper::saveLog('8P gameBet', 19, 19, 'ENDPOINT HIT');
			
		Helper::saveLog('8P BET', 19, file_get_contents("php://input"), 'ENDPOINT HIT');

		Helper::saveLog('8P BET FORMDATA', 19, json_encode($request->all()), 'ENDPOINT HIT');


		return 'endpoint reached';

		// $guzzle_response = $client->post($client_details->fund_transfer_url,
		// 		['body' => json_encode($requesttosend)]
		// );
	}

	public function gameWin(Request $request){
		Helper::saveLog('8P gameWin', 19, 19, 'ENDPOINT HIT');
		Helper::saveLog('8P WIN', 19, file_get_contents("php://input"), 'ENDPOINT HIT');
		$response = array(
			'status' => 'ok',
			'data' => [
				'balance' => 456455.66,
				'currency' => 'USD',
			],
		);
		return $response;
	}
	
	public function gameRefund(Request $request){
		Helper::saveLog('8P gameRefund', 19, 19, 'ENDPOINT HIT');
		$response = array(
			'status' => 'ok',
			'data' => [
				'balance' => 456455.66,
				'currency' => 'USD',
			],
		);
		return $response;
	}


	/**
	 *  TEST
	 * 
	 */
	public function gameDeposit(Request $request){
		Helper::saveLog('8P Deposit', 19, 19, 'ENDPOINT HIT');
		$response = array(
			'status' => 'ok',
			'data' => [
				'balance' => 456455.66,
				'currency' => 'USD',
			],
		);
	
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
