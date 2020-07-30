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
	 public $api_version = 1;
	 public $api_key = '29abd3790d0a5acd532194c5104171c8';
	 public $api_url = 'http://api.flexcontentprovider.com';
	 public $provider_db_id = 25;

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
		$enc_body = file_get_contents("php://input");
        parse_str($enc_body, $data);
        $json_encode = json_encode($data, true);
        $data = json_decode($json_encode);

		$url = $this->api_url.'/Game/getList';
		$data = [
			'need_extra_data' => 1
		];
		$signature =  $this->getSignature($this->project_id,$this->api_version,$data,$this->api_key);
 	    $requesttosend = [
 	    	'project' 	=> $this->project_id,
 	    	'api_version'   => $this->api_version,
 	    	'signature' => $this->getSignature($this->project_id,$this->api_version,$data,$this->api_key)
 	    ];
        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json'
            ]
        ]);
        $guzzle_response = $client->post($url.'?project='.$this->project_id.'&api_version='.$this->api_version.'&signature='.$signature);
        $client_response = json_decode($guzzle_response->getBody()->getContents());
        return json_encode($client_response);
	}

	public function getAvailablePayouts(){

	}	


	public function gameBet(Request $request){
		$header = $request->header('Authorization');
	    Helper::saveLog('TGG Authorization Logger BET', $this->provider_db_id, json_encode(file_get_contents("php://input")), $header);

		$enc_body = file_get_contents("php://input");
        parse_str($enc_body, $data);
        $json_encode = json_encode($data, true);
        $data = json_decode($json_encode);
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
