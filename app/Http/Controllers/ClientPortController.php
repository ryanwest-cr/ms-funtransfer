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
    public function __construct(){
		/*$this->middleware('oauth', ['except' => ['index']]);*/
		/*$this->middleware('authorize:' . __CLASS__, ['except' => ['index', 'store']]);*/
	}

	public function playerDetailsRequest(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		$guzzle_response = ClientRequestHelper::playerDetailsCall($json_data['token']);

		$client_details = ProviderHelper::getClientDetails('token', $json_data['token']);
		$is_test_player = ($client_details->test_player == 1 ? true : false);
		$guzzle_response->playerdetailsresponse->is_test_players = $is_test_player;
		$guzzle_response->playerdetailsresponse->internal_id = $client_details->player_id;

		$http_status = 200;
		return response()->json($guzzle_response, $http_status);
	}

	public function fundTransferRequest(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);

		$client_details = ProviderHelper::getClientDetails('token', $json_data['token']);
		$guzzle_response = ClientRequestHelper::fundTransfer($client_details, $json_data['amount'], $json_data['game_code'], $json_data['game_name'], $json_data['transaction_id'], $json_data['round_id'], $json_data['transaction_type'], $json_data['rollback'] = false);

		$http_status = 200;
		return response()->json($guzzle_response, $http_status);

	}

}
