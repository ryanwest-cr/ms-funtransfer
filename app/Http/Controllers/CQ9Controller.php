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


	public function getGameList(){
		$client = new Client([
            'headers' => [ 
                'Authorization' => 'Bearer '.$this->api_token,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        ]);
        $response = $client->get($this->api_url.'/gameboy/game/halls');

        $game_list = json_decode((string)$response->getBody(), true);
        dd($game_list);
	}

    public function CheckPlayer($account){
    	// $client = new Client([
     //        'headers' => [ 
     //            'Authorization' => 'Bearer '.$this->$api_token,
     //            'Content-Type' => 'application/x-www-form-urlencoded',
     //        ]
     //    ]);
     //    $response = $client->post($this->$api_url.'/gameboy/game/halls', [
     //        'form_params' => [
     //            'cmd' => 'auth', // auth request command
     //            'username' => 'freebetrnk',  // client subscription acc
     //            'password' => 'w34KM)!##$$#',
     //            'merchant_user'=> $player_details->playerdetailsresponse->username,
     //            'merchant_user_balance'=> $player_details->playerdetailsresponse->balance,
     //        ],
     //    ]);
    }

    public function CheckBalance($account){

    }
}
