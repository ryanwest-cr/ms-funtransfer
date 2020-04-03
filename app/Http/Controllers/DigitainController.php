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



	public function authMethod($operatorId, $timestamp, $signature){

	  	// "operatorId":111,
		// "timestamp":"202003092113371560",
		// "signature":"ba328e6d2358f6d77804e3d342cdee06c2afeba96baada218794abfd3b0ac926",
		// "token":"90dbbb443c9b4b3fbcfc59643206a123"

		$digitain_key = "sampledigitainkey";
	    $operator_id = $operatorId;
	    $time_stamp = $timestamp;
	    $message = $digitain_key.$operator_id.$time_stamp;

	    $hmac = hash_hmac("sha256", $message, $digitain_key);
		$result = false;

            if($hmac == $signature) {
			    $result = true;
            }

        return $result;

	}


	public function createSignature($timestamp){
		$digitain_key = "sampledigitainkey";
	    $operator_id = 111; /* STATIC FOR NOW */
	    $time_stamp = $timestamp;
	    $message = $digitain_key.$operator_id.$time_stamp;
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



    public function authPlayer(Request $request)
    {

		$json_data = json_decode(file_get_contents("php://input"), true);

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

		}else{
			return $response;
		}
	
	}






	public function getBalance()
	{

		$json_data = json_decode(file_get_contents("php://input"), true);

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
			return $response;
		}
	}






}
