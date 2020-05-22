<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;

use DB;



class DigitainController extends Controller
{

	// private $apikey ="321dsfjo34j5olkdsf";
	// private $access_token = "123iuysdhfb09875v9hb9pwe8f7yu439jvoiefjs";

	// private $digitain_key = "rgstest";
    // private $operator_id = '5FB4E74E';

    // private $digitain_key = "rgstest";
    // private $operator_id = 'D233911A';


    private $digitain_key = "BetRNK3184223";
    private $operator_id = 'B9EC7C0A';

	public function authMethod($operatorId, $timestamp, $signature){

	  	// "operatorId":111,
		// "timestamp":"202003092113371560",
		// "signature":"ba328e6d2358f6d77804e3d342cdee06c2afeba96baada218794abfd3b0ac926",
		// "token":"90dbbb443c9b4b3fbcfc59643206a123"

		// $digitain_key = "P5rWDliAmIYWKq6HsIPbyx33v2pkZq7l";
		$digitain_key = "BetRNK3184223";
	    $operator_id = $operatorId;
	    $time_stamp = $timestamp;
	    $message = $time_stamp.$operator_id;

	    $hmac = hash_hmac("sha256", $message, $digitain_key);
		$result = false;

            if($hmac == $signature) {
			    $result = true;
            }

        return $result;

	}


	// $timestampe = date('YmdHisms'); //sample output //202005041214380538

	public function createSignature($timestamp){
		// $digitain_key = "P5rWDliAmIYWKq6HsIPbyx33v2pkZq7l";
	    // $operator_id = 'D233911A'; /* STATIC FOR NOW */
		// $digitain_key = "rgstest";
	 //    $operator_id = '5FB4E74E';
	    $digitain_key = $this->digitain_key;
	    $operator_id = $this->operator_id;
	    $time_stamp = $timestamp;
	    $message = $time_stamp.$operator_id;
	    $hmac = hash_hmac("sha256", $message, $digitain_key);
	    return $hmac;
	}

	// TEST
	public function getTimestamp($daterequest){
		$date1 = str_replace("-", "", $daterequest);
		$date2 = str_replace(":", "", $date1);
		$date3 = str_replace(" ", "", $date2);

		return $date3;
	}

	/*
     * # Request , token, username, email, site_url, gamecode
	 */
	public function createGameSession(){

		$json_data = json_decode(file_get_contents("php://input"), true);

		$check_client = $this->checkClientPlayer($json_data['site_url'], 
													$json_data['playerdetailsrequest']['username'], 
													$json_data['playerdetailsrequest']['token']);

		if($check_client['httpstatus'] != 200){
				return $check_client;
		}

			$client_details = $this->_getClientDetails('token', $json_data['playerdetailsrequest']['token']);


			 $response = [
			 	"errorcode" =>  "CLIENT_NOT_FOUND",
				"errormessage" => "Client not found",
				"httpstatus" => "404"
			 ];

			 if ($client_details) { 

			 // 	$subscription = new GameSubscription();
				// $client_game_subscription = $subscription->check($client_details->client_id, 11, $json_data['gamecode']);

			 // 	if(!$client_game_subscription) {
				// 	$response = [
				// 			"errorcode" =>  "GAME_NOT_FOUND",
				// 			"errormessage" => "Game not found",
				// 			"httpstatus" => "404"
				// 		];
				// }
				// else{

					$response = array(
                                "url" => 'https://partnerapirgs.betadigitain.com/GamesLaunch/Launch?gameid='.$json_data['gamecode'].'&playMode=real
&token='.$json_data['playerdetailsrequest']['token'].'&deviceType=1&lang=EN&operatorId='.$this->operator_id.'&mainDomain='.$json_data['site_url'].'',
                                "game_launch" => true
                            );
				// }

			 }

			 // Helper::saveLog('register', 2, $response, 'resBoleReg');
	         return $response;

	}


    public function authenticate(Request $request)
    {
		$json_data = json_decode(file_get_contents("php://input"), true);
		Helper::saveLog('authenticationRGS', 2, 123, 'authenticate');
		Helper::saveLog('authentication', 2, file_get_contents("php://input"), 'RiANDRAFT');

		$response = [
						"errorcode" =>  "INVALID_TOKEN",
						"errormessage" => "The provided token could not be verified/Token already authenticated",
						"httpstatus" => "404"
					];
		

		if ($this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])) {

		$player_token = $json_data["token"];

		$client_details = DB::table("clients AS c")
						 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.player_token' , 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
						 ->leftJoin("players AS p", "c.client_id", "=", "p.client_id")
						 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
						 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
						 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id")
						 ->where("pst.player_token", $player_token)
						 ->first();


		if ($client_details) {
			
			$client = new Client([
			    'headers' => [ 
			    	'Content-Type' => 'application/json',
			    	'Authorization' => 'Bearer '.$client_details->client_access_token
			    ]
			]);
			
			$guzzle_response = $client->post($client_details->player_details_url,
			    ['body' => json_encode(
			        	["access_token" => $client_details->client_access_token,
							"hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
							"type" => "playerdetailsrequest",
							"datesent" => "",
							"gameid" => "",
							"clientid" => $client_details->client_id,
							"playerdetailsrequest" => [
								"token" => $json_data["token"],
								"gamelaunch" => true
							]
						]
			    )]
			);

			$client_response = json_decode($guzzle_response->getBody()->getContents());
			// $time_formatted = $this->getTimestamp($client_response->playerdetailsresponse->daterequest);
			// dd($client_response->playerdetailsresponse->status->code);

			if(isset($client_response->playerdetailsresponse->status->code) &&
				     $client_response->playerdetailsresponse->status->code == "200"){

				$response = [
					"timestamp" => date('YmdHisms'),
					"signature" => $this->createSignature(date('YmdHisms')),
					"errorCode" => 1,
					"playerId" => $client_response->playerdetailsresponse->accountid,
					"userName" => $client_response->playerdetailsresponse->accountname,
					"currencyId" => $client_response->playerdetailsresponse->currencycode,
					"balance" => $client_response->playerdetailsresponse->balance,
					"birthDate" => $client_response->playerdetailsresponse->birthday,
					"firstName" => $client_response->playerdetailsresponse->firstname,
					"lastName" => $client_response->playerdetailsresponse->lastname,
					"gender" => $client_response->playerdetailsresponse->gender,
					"email" => $client_response->playerdetailsresponse->email,
					"isReal" => false
				];

			}

			Helper::saveLog('authentication', 2, file_get_contents("php://input"), $response);
			echo json_encode($response);

		}

		

		}else{
			return $response;
		}
	
	}






	public function getBalance()
	{

		$json_data = json_decode(file_get_contents("php://input"), true);
		Helper::saveLog('authenticationRGS', 2, 123, 'GETBALANCE');


		$response = [
						"errorcode" =>  "INVALID_TOKEN",
						"errormessage" => "The provided token could not be verified/Token already authenticated",
						"httpstatus" => "404"
					];

		if ($this->authMethod($json_data['operatorId'], $json_data['timestamp'], $json_data['signature'])) {
			
		$player_token = $json_data["token"];

		$client_details = DB::table("clients AS c")
						 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.player_token' , 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
						 ->leftJoin("players AS p", "c.client_id", "=", "p.client_id")
						 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
						 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
						 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id")
						 ->where("pst.player_token", $player_token)
						 ->first();

		if ($client_details) {


			$client = new Client([
			    'headers' => [ 
			    	'Content-Type' => 'application/json',
			    	'Authorization' => 'Bearer '.$client_details->client_access_token
			    ]
			]);
			
			$guzzle_response = $client->post($client_details->player_details_url,
			    ['body' => json_encode(
			        	["access_token" => $client_details->client_access_token,
							"hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
							"type" => "playerdetailsrequest",
							"clientid" => $client_details->client_id,
							"playerdetailsrequest" => [
								"token" => $json_data["token"],
								"playerId" => $json_data["playerId"],
								"currencyId" => $json_data["currencyId"],
								"gamelaunch" => true
							]
						]
			    )]
			);

			$client_response = json_decode($guzzle_response->getBody()->getContents());


			$timenow = Carbon::now();
	        $date = $timenow->format("yymd");
	        $Time = $timenow->format("His");
	        $ml = $timenow->format("u");
	        // date('YmdHisms');
	       	// $milliseconds = round(microtime(true) * 1000);
	       	$currentMilliSecond = (int) (microtime(true) * 1000);


			$response = [
				"timestamp" => $date.$Time.substr(sprintf('%04d', $currentMilliSecond),0,4),
				"signature" => $this->createSignature($date.$Time.$ml),
				"errorCode" => 1,
				"balance" => $client_response->playerdetailsresponse->balance,
				"email" => $client_response->playerdetailsresponse->email,
				"updatedAt" => date('YmdHis.ms')
			];

		}

			echo json_encode($response);

		}else{
			echo json_encode($response);
		}
	}



	public function refreshtoken(){
		Helper::saveLog('authenticationRGS', 2, 123, 'refreshtoken');
		return $this->createSignature(date('YmdHisms'));
	}

	public function bet(){
		Helper::saveLog('authenticationRGS', 2, 123, 'bet');
	}

	public function betwin(){
		Helper::saveLog('authenticationRGS', 2, 123, 'betwin');
	}

	public function refund(){
		Helper::saveLog('authenticationRGS', 2, 123, 'refund');
	}

	public function amend(){
		Helper::saveLog('authenticationRGS', 2, 123, 'amend');
    }











    /*
		 * Check Player Using Token if its already register in the MW database if not register it!
		 */
		public function checkClientPlayer($site_url, $merchant_user ,$token = false)
		{

				// Check Client Server Name
				$client_check = DB::table('clients')
	          	 	 ->where('client_url', $site_url)
	           		 ->first();

	           	$data = [
		        	"msg" => "Client Not Found",
		        	"httpstatus" => "404"
		        ];  	 

	            if($client_check){  

		                $player_check = DB::table('players')
		                    ->where('client_id', $client_check->client_id)
		                    ->where('username', $merchant_user)
		                    ->first();

		                if($player_check){

		                    DB::table('player_session_tokens')->insert(
		                            array('player_id' => $player_check->player_id, 
	                            		  'player_token' =>  $token, 
		                            	  'status_id' => '1')
		                    );    

		                    $token_player_id = $this->_getPlayerTokenId($player_check->player_id);

		                    $data = [
						        	"token" => $token,
						        	"httpstatus" => "200",
						        	"new" => false
					        ];   

		                }else{

	                	try
	                	{
						        $client_details = $this->_getClientDetails('site_url', $site_url);

								$client = new Client([
								    'headers' => [ 
								    	'Content-Type' => 'application/json',
								    	'Authorization' => 'Bearer '.$client_details->client_access_token
								    ]
								]);

								$guzzle_response = $client->post($client_details->player_details_url,
								    ['body' => json_encode(
								        	["access_token" => $client_details->client_access_token,
												"hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
												"type" => "playerdetailsrequest",
												"datesent" => "",
												"gameid" => "",
												"clientid" => $client_details->client_id,
												"playerdetailsrequest" => [
													"token" => $token,
													"gamelaunch" => true
												]]
								    )]
								);

								$client_response = json_decode($guzzle_response->getBody()->getContents());

								DB::table('players')->insert(
		                            array('client_id' => $client_check->client_id, 
		                            	  'client_player_id' =>  $client_response->playerdetailsresponse->accountid, 
		                            	  'username' => $client_response->playerdetailsresponse->username, 
		                            	  'email' => $client_response->playerdetailsresponse->email,
		                            	  'display_name' => $client_response->playerdetailsresponse->accountname)
			                    );

			                	$last_player_id = DB::getPDO()->lastInsertId();

			                	DB::table('player_session_tokens')->insert(
				                            array('player_id' => $last_player_id, 
				                            	  'player_token' =>  $token, 
				                            	  'status_id' => '1')
			                    );

			                	$token_player_id = $this->_getPlayerTokenId($last_player_id);

						}
						catch(ClientException $e)
						{
						  $client_response = $e->getResponse();
						  $response = json_decode($client_response->getBody()->getContents(),True);
						  return response($response,$client_response->getStatusCode())
						   ->header('Content-Type', 'application/json');
						}

				                $data = [
						        	"token" => $token,
						        	"httpstatus" => "200",
						        	"new" => true
						        ];   

		      			}     

				}

		        return $data;
			

		}



		public function _getClientDetails($type = "", $value = "") {

		$query = DB::table("clients AS c")
					 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.token_id', 'pst.player_token' , 'c.client_url', 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
					 ->leftJoin("players AS p", "c.client_id", "=", "p.client_id")
					 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
					 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
					 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id");
					 
					if ($type == 'token') {
						$query->where([
					 		["pst.player_token", "=", $value],
					 		["pst.status_id", "=", 1]
					 	]);
					}

					if ($type == 'player_id') {
						$query->where([
					 		["p.player_id", "=", $value],
					 		["pst.status_id", "=", 1]
					 	]);
					}


					if ($type == 'site_url') {
						$query->where([
					 		["c.client_url", "=", $value],
					 	]);
					}

					if ($type == 'username') {
						$query->where([
					 		["p.username", $value],
					 	]);
					}

					 $result= $query
					 			->latest('token_id')
					 			->first();

			return $result;

		}


		public function _getPlayerTokenId($player_id){

	       $client_details = DB::table("players AS p")
	                         ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.player_token' , 'pst.status_id','pst.token_id' , 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
	                         ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
	                         ->leftJoin("clients AS c", "c.client_id", "=", "p.client_id")
	                         ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
	                         ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id")
	                         ->where("p.player_id", $player_id)
	                         ->where("pst.status_id", 1)
	                         ->latest('token_id')
	                         ->first();

	        return $client_details->token_id;    
	        
	    }
}
