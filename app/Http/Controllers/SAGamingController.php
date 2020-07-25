<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use App\Helpers\SAHelper;
use App\Helpers\GameLobby;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;
use DB;

class SAGamingController extends Controller
{

	public $prefix = 'TG_SA';


	// public function gameLaunch(){
	// 	$http = new Client();
 //        $requesttosend = [
 //             "username" => config('providerlinks.sagaming.prefix'),
 //             "token" => $request->token,
 //             "lobby" => "A3107",
 //             "lang" => "Tgames1234", // optional
 //             "returnurl" => "Tgames1234", // optional
 //             "mobile" => "Tgames1234", // optional
 //             "options" => "Tgames1234"
 //        ];
 //        $response = $http->post('https://api.gcpstg.m27613.com/login', [
 //            'form_params' => $requesttosend,
 //        ]);
 //        $response = $response->getBody()->getContents();
 //        Helper::saveLog('Skywind Game Launch', 21, $requesttosend, json_encode($response));
 //        return $response;
	// }

    public function GetUserBalance(Request $request){
    	// return SAHelper::altest();
    	// filter currency
    	Helper::saveLog('SA Get Balance', 23, file_get_contents("php://input"), 'ENDPOINT HIT');

    	$prefixed_username = explode("_SA", $request->username);
    	$client_details = Providerhelper::getClientDetails('player_id', $prefixed_username[1]);
    	$player_details = Providerhelper::playerDetailsCall($client_details->player_token);

    	$response = [
    		"username" => $this->prefix.$client_details->player_id,
    		"currency" => $client_details->default_currency,
    		"amount" => $player_details->playerdetailsresponse->balance,
    		"error" => 0,
    	];

    	return $response;
    }

    public function PlaceBet(){
    	Helper::saveLog('SA Place Bet', 23, file_get_contents("php://input"), 'ENDPOINT HIT');
    }

    public function PlayerWin(){
    	Helper::saveLog('SA Player Win', 23, file_get_contents("php://input"), 'ENDPOINT HIT');
    }

    public function PlayerLost(){
    	Helper::saveLog('SA Player Lost', 23, file_get_contents("php://input"), 'ENDPOINT HIT');
    }

    public function PlaceBetCancel(){
    	Helper::saveLog('SA Place Bet Cancel', 23, file_get_contents("php://input"), 'ENDPOINT HIT');
    }

}
