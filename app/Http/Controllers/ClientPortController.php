<?php

namespace App\Http\Controllers;

use App\Models\PlayerDetail;
use App\Models\PlayerSessionToken;
use App\Helpers\Helper;
use App\Helpers\GameTransaction;
use App\Helpers\GameSubscription;
use App\Helpers\GameRound;
use App\Helpers\Game;
use App\Helpers\CallParameters;
use App\Helpers\PlayerHelper;
use App\Helpers\TokenHelper;
use App\Helpers\ProviderHelper;
use App\Helpers\ClientRequestHelper;

use App\Support\RouteParam;

use Illuminate\Http\Request;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

use DB;

class ClientPortController extends Controller
{

	public function playerDetailsRequest(Request $request) 
	{
		$payload = $request->all();

		$guzzle_response = ClientRequestHelper::playerDetailsCall($payload['token']);
             
		if($guzzle_response === 'false') {
			$guzzle_response = [
				"playerdetailsresponse" => [
		            "status" => [
		                    "code" => 401,
		                    "status" => "Failed",
		                    "message" => "Player not found.",
		                ]
		        ]
			];
		}

		$http_status = 200;
		return response()->json($guzzle_response, $http_status);
	}

	public function fundTransferRequest(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);

		$client_details = ProviderHelper::getClientDetails('token', $json_data['token']);
		
		if(!$client_details) {
			$guzzle_response = [
				"fundtransferresponse" => [
		            "status" => [
		                    "code" => 401,
		                    "status" => "Failed",
		                    "message" => "Player not found.",
		                ]
		        ]
			];
		}
		else
		{
			$guzzle_response = ClientRequestHelper::fundTransfer($client_details, $json_data['amount'], $json_data['game_code'], $json_data['game_name'], $json_data['transaction_id'], $json_data['round_id'], $json_data['transaction_type'], $json_data['rollback'] = false);
		}
		
		
		$http_status = 200;
		return response()->json($guzzle_response, $http_status);

	}

}
