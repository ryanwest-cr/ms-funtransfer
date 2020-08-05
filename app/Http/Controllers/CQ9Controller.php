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

	public $api_url = 'http://api.cqgame.games';
	public $api_token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyaWQiOiI1ZjIyODY1ZWNkNTY1ZjAwMDEzZjUyZDAiLCJhY2NvdW50IjoidGlnZXJnYW1lcyIsIm93bmVyIjoiNWYyMjg2NWVjZDU2NWYwMDAxM2Y1MmQwIiwicGFyZW50Ijoic2VsZiIsImN1cnJlbmN5IjoiVVNEIiwianRpIjoiMjQ3NDQ1MTQzIiwiaWF0IjoxNTk2MDk4MTQyLCJpc3MiOiJDeXByZXNzIiwic3ViIjoiU1NUb2tlbiJ9.fdoQCWGPkYNLoROGR9jzMs4axnZbRJCnnLZ8T2UDCwU';

	// /gameboy/player/logout
	// /gameboy/game/list/cq9
	// /gameboy/game/halls
	// 

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

	public function gameLaunch(){
		$client = new Client([
            'headers' => [ 
                'Authorization' => $this->api_token,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        ]);
        $response = $client->post($this->api_url.'/gameboy/player/sw/gamelink', [
            'form_params' => [
                'account'=> 'TG1_98',
                'gamehall'=> 'cq9',
                'gamecode'=> '1',
                'gameplat'=> 'WEB',
                'lang'=> 'en',
            ],
        ]);
        $game_launch = json_decode((string)$response->getBody(), true);
        return $game_launch;
	}

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

    public function CheckPlayer($account){
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details != null){
    		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
			$data = [
	    		"data" => true,
	    		"status" => [
	    			"code" => 0,
	    			"message" => 'Success',
	    			"datetime" => '2017-01-20T01:14:48-04:00'
	    		]
	    	];
    	}else{
    		$data = [
	    		"data" => false,
	    		"status" => [
	    			"code" => 0,
	    			"message" => 'Success',
	    			"datetime" => '2017-01-20T01:14:48-04:00'
	    		]
	    	];
    	}
    	return $data;
    }

    public function CheckBalance($account){
    	
    }
}
