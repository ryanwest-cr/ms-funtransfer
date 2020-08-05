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

class CQ9Controller extends Controller
{

	public $api_url, $api_token, $provider_db_id;

	// /gameboy/player/logout
	// /gameboy/game/list/cq9
	// /gameboy/game/halls

	public function __construct(){
    	$this->api_url = config('providerlinks.cqgames.api_url');
    	$this->api_token = config('providerlinks.cqgames.api_token');
    	$this->provider_db_id = config('providerlinks.cqgames.pdbid');
    }

	public function getGameList(){
		$client = new Client([
            'headers' => [ 
                'Authorization' => $this->api_token,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        ]);
        // $response = $client->get($this->api_url.'/gameboy/game/halls');
        $response = $client->get($this->api_url.'/gameboy/game/list/cq9');

        $game_list = json_decode((string)$response->getBody(), true);
        return $game_list;
	}

	// public function gameLaunch(){
	// 	$client = new Client([
 //            'headers' => [ 
 //                'Authorization' => $this->api_token,
 //                'Content-Type' => 'application/x-www-form-urlencoded',
 //            ]
 //        ]);
 //        $response = $client->post($this->api_url.'/gameboy/player/sw/gamelink', [
 //            'form_params' => [
 //                'account'=> 'TG1_98',
 //                'gamehall'=> 'cq9',
 //                'gamecode'=> '1',
 //                'gameplat'=> 'WEB',
 //                'lang'=> 'en',
 //            ],
 //        ]);
 //        $game_launch = json_decode((string)$response->getBody(), true);
 //        return $game_launch;
	// }

	public function playerLogout(){
		$client = new Client([
            'headers' => [ 
                'Authorization' => $this->api_token,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        ]);
        $response = $client->post($this->$api_url.'/gameboy/player/logout', [
            'form_params' => [
                'account'=> 'player_id',
            ],
        ]);
        $logout = json_decode((string)$response->getBody(), true);
        return $logout;
	}

    public function CheckPlayer(Request $request, $account){
    	// $header = $request->header('Authorization');
    	// $header = $request->header('wtoken');
    	Helper::saveLog('CQ9 Check Player', $this->provider_db_id, json_encode($request->all()), 'awe');
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details != null){
    		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
			$data = [
	    		"data" => true,
	    		"status" => [
	    			"code" => "0",
	    			"message" => 'Success',
	    			"datetime" => date(DATE_RFC3339)
	    		]
	    	];
    	}else{
    		$data = [
	    		"data" => false,
	    		"status" => [
	    			"code" => "0",
	    			"message" => 'Success',
	    			"datetime" => date(DATE_RFC3339)
	    		]
	    	];
    	}
    	Helper::saveLog('CQ9 Check Player', $this->provider_db_id, json_encode($request->all()), $data);
    	return $data;
    }

    public function CheckBalance(Request $request, $account){
    	Helper::saveLog('CQ9 Balance Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details != null){
    		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
			$data = [
	    		"data" => [
	    			"balance" => $player_details->playerdetailsresponse->balance,
	    			// "currency" => $client_details->default_currency,
	    			"currency" => "CNY",
	    		],
	    		"status" => [
	    			"code" => "0",
	    			"message" => 'Success',
	    			"datetime" => date(DATE_RFC3339)
	    		]
	    	];
    	}else{
    		$data = [
	    		"data" => false,
	    		"status" => [
	    			"code" => "0",
	    			"message" => 'Success',
	    			"datetime" => date(DATE_RFC3339)
	    		]
	    	];
    	}
    	Helper::saveLog('CQ9 Balance Player', $this->provider_db_id, json_encode($request->all()), $data);
    	return $data;
    }


    public function playrEndround(Request $request){
    	Helper::saveLog('CQ9 playrEndround Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	Helper::saveLog('CQ9 playrEndround Player', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT 2');
    }

    public function playerRollout(Request $request){
    	Helper::saveLog('CQ9 playerRollout Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	Helper::saveLog('CQ9 playerRollout Player', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT 2');
    }

    public function playerBet(Request $request){
    	Helper::saveLog('CQ9 playerBet Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	Helper::saveLog('CQ9 playerBet Player', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT 2');
    }

    public function playerTakeall(Request $request){
    	Helper::saveLog('CQ9 playerTakeall Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	Helper::saveLog('CQ9 playerTakeall Player', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT 2');
    }

    public function playerCredit(Request $request){
    	Helper::saveLog('CQ9 playerCredit Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	Helper::saveLog('CQ9 playerCredit Player', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT 2');
    }

     public function playerBonus(Request $request){
    	Helper::saveLog('CQ9 playerBonus Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	Helper::saveLog('CQ9 playerBonus Player', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT 2');
    }

     public function playerPayoff(Request $request){
    	Helper::saveLog('CQ9 playerPayoff Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	Helper::saveLog('CQ9 playerPayoff Player', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT 2');
    }

     public function playerRefund(Request $request){
    	Helper::saveLog('CQ9 playerRefund Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	Helper::saveLog('CQ9 playerRefund Player', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT 2');
    }

    public function playerBets(Request $request){
    	Helper::saveLog('CQ9 playerBets Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	Helper::saveLog('CQ9 playerBets Player', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT 2');
    }

     public function playerRefunds(Request $request){
    	Helper::saveLog('CQ9 playerRefunds Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	Helper::saveLog('CQ9 playerRefunds Player', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT 2');
    }


      public function playerCancel(Request $request){
    	Helper::saveLog('CQ9 playerCancel Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	Helper::saveLog('CQ9 playerCancel Player', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT 2');
    }

     public function playerAmend(Request $request){
    	Helper::saveLog('CQ9 playerAmend Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	Helper::saveLog('CQ9 playerAmend Player', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT 2');
    }


}
