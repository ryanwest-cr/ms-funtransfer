<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use App\Helpers\ClientRequestHelper;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use DB;

class KAGamingController extends Controller
{


	public $gamelaunch = 'https://gamesstage.kaga88.com';
	public $ka_api = 'https://rmpstage.kaga88.com/kaga/';
	public $message = 'https://gamesstage.kaga88.com';
	public $access_key = 'A95383137CE37E4E19EAD36DF59D589A';
	public $secret_key = '40C6AB9E806C4940E4C9D2B9E3A0AA25';


	public function generateHash(){
		return hash_hmac('sha256', $string, $this->access_key);
	}

    public function index(){

		$body = [
   			"partnerName" => 'TIGER',
            "accessKey" => $this->access_key,
            "language" => "zh",
            "randomId" => 1,
        ];

        $guzzle_response = $client->post($ka_api.'gameList?hash='.$this->generateHash());
        ['body' => json_encode(
            $body
        )];

        $client_reponse = json_decode($guzzle_response->getBody()->getContents());
        dd($client_response);
    	// return ['tiger games' => 'lets get it on ^_^'];
    }
}
