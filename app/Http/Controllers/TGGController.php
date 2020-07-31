<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use GuzzleHttp\Client;
use Carbon\Carbon;
use DB;

class TGGController extends Controller
{
     public $project_id = 1421;
	 public $version = 1;
	 public $callback_version = 2;
	 public $api_key = '29abd3790d0a5acd532194c5104171c8';
	 public $api_url = 'http://api.flexcontentprovider.com';
	 public $provider_db_id = 25; // this is not final provider no register local

	/**
	* $system_id - your project ID (number)
	* $version - API version (number)for API request OR callback version (number) for callback call
	* $args - array with API method OR callback parameters. API method parameters list you can find in the API method description
	* $system_key - your API key (secret key)
	*/ 
	public static function  getSignature($system_id, $version, array $args, $system_key){
		$md5 = array();
		$md5[] = $system_id;
		$md5[] = $version;

		foreach ($args as $required_arg) {
			$arg = $required_arg;
			if(is_array($arg)){
				if(count($arg)) {
					$recursive_arg = '';
					array_walk_recursive($arg, function($item) use (& $recursive_arg) {
						if(!is_array($item)) { $recursive_arg .= ($item . ':');} 
					});
					$md5[] = substr($recursive_arg, 0, strlen($recursive_arg)-1); // get rid of last
				} else {
					$md5[] = '';
				}
			} else {
				$md5[] = $arg;
			}
		};

		$md5[] = $system_key;
		$md5_str = implode('*', $md5);
		$md5 = md5($md5_str);
		return $md5;
	}

	public function getGamelist(Request $request){
		$data = [
			'need_extra_data' => 1
		];
		$signature =  $this->getSignature($this->project_id,$this->version,$data,$this->api_key);
		$url = $this->api_url.'/game/getlist';
        $requesttosend = [
            'project' =>  $this->project_id,
			'version' => $this->version,
			'signature' => $signature,
			'need_extra_data' => 1
		];
        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$signature
            ]
        ]);
        $guzzle_response = $client->post($url,['body' => json_encode($requesttosend)]);
		$client_response = json_decode($guzzle_response->getBody()->getContents());
        return json_encode($client_response);
	}


	public function getURL(){
		$data = [
			'project'=> 113, //int
			'version'=> 1, //int
			'signature'=> 'f41979c1fe708313c66acada53f913d1',//string 32
			'token'=> 'j45hg67j45h7g45k45j7hk45j74k57g4k5jg74k574k7j4k57',//string 49
			'game'=> 'fullstate\html5\ugproduction\luckylimo',//string 34
			'settings'=> [
				'user_id'=> 'testuserG407',//string 12
				'exit_url'=> 'https://google.com?test=1&test2=2#exit_url',//string 43
				'cash_url'=> 'https://google.com?test=1&test2=2#cash_url',//string 43
				'language'=> 'en',//string 2
				'denominations'=> [
					0 => 0.01,//float
					1 => 0.1,//float
					2 => 0.25,//float
					3 => 1,//int
					4 => 10,//int
				],
				'https'=> 1,//int
			],
			'denomination'=> 1,
			'currency'=> 'USD',//string 3
			'return_url_info'=> 1,//int
			'callback_version'=> 2//int
		];
		return $data;
	}
	/**
	 * Initialize the balance 
	 */
	public function initBalance(Request $request){
		//Helper::saveLog('Tidy Check Balance', 23, json_encode(file_get_contents("php://input")), 'ENDPOINT HIT v2');
		//$data = json_decode(file_get_contents("php://input")); // INCASE RAW JSON / CHANGE IF NOT ARRAY
		$header = $request->header('Authorization');
		$enc_body = file_get_contents("php://input");
        parse_str($enc_body, $data);
        $json_encode = json_encode($data, true);
        $data = json_decode($json_encode);
	    // Helper::saveLog('Tidy Bal 1', 23, json_encode(file_get_contents("php://input")), $data);
	   
		$token = $data->token;
		$callback_id = $data->callback_id;
		$name = $data->name;
		$signature = $data->signature;

		$client_details = ProviderHelper::getClientDetails('token',$token);
	
		if($client_details != null){
			$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
				$data_response = [
					'status' => 'ok',
					'data' => [
						'balance' => $player_details->playerdetailsresponse->balance,
						'currency' => $client_details->default_currency,
						'display_name' => $client_details->display_name
					]
				];
				//Helper::saveLog('Tidy Check Balance Response', $this->provider_db_id, json_encode($request->all()), $data);
				return $data_response;
		}else{
			$data_response = [
				'status' => 'error',
				'error' => [
					'scope' => "user",
					'message' => "not found",
					'detils' => ''
				]
			];
			return $data_response;
		}
	}

	public function gameBet(Request $request){
		$header = $request->header('Authorization');
	    //Helper::saveLog('TGG Authorization Logger BET', $this->provider_db_id, json_encode(file_get_contents("php://input")), $header);

		$enc_body = file_get_contents("php://input");
        parse_str($enc_body, $data);
        $json_encode = json_encode($data, true);
		$data = json_decode($json_encode);

		return $request->all();
		
	}

	public function gameWin(Request $request){
		$header = $request->header('Authorization');
    	Helper::saveLog('TGG Authorization Logger WIN', $this->provider_db_id, json_encode(file_get_contents("php://input")), $header);

		$enc_body = file_get_contents("php://input");
        parse_str($enc_body, $data);
        $json_encode = json_encode($data, true);
        $data = json_decode($json_encode);

	}

	public function gameRefund(Request $request){
		$header = $request->header('Authorization');
	    Helper::saveLog('TGG Authorization Logger WIN', $this->provider_db_id, json_encode(file_get_contents("php://input")), $header);

		$enc_body = file_get_contents("php://input");
        parse_str($enc_body, $data);
        $json_encode = json_encode($data, true);
        $data = json_decode($json_encode);

	}
}
