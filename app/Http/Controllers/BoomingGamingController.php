<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use GuzzleHttp\Client;
use Carbon\Carbon;
use DB;


class BoomingGamingController extends Controller
{
    public function gameList(){
        $url = 'https://api.eu.booming-games.com/v2/games';
        $requesttosend = [
           'variants' => 'desktop',
           'locales'=> 'en',
           'currencies'=> 'USD'
       ];
       $client = new Client([
           'headers' => [ 
               'Content-Type' => 'application/vnd.api+json',
               'X-Bg-Api-Key' =>'',
               'X-Bg-Nonce'=> '232432423223',
               'X-Bg-Signature' => $secrete,
               'Authorization' => 'Bearer '.TidyHelper::generateToken($requesttosend)

           ]
       ]);
       $secret= 'NQGRafUDbe/esU8r+zVWWW7cx6xZKE2gpqWXv4Fs17j88u0djV6NBi9Tgdtc0R6w';
       $secrete = hash_hmac($secret, concat($url, '232432423223', SHA256($requesttosend) ));
       $guzzle_response = $client->get($url);
       $client_response = json_decode($guzzle_response->getBody()->getContents());
       return json_encode($client_response);
    }
}
