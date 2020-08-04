<?php
namespace App\Helpers;

use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use App\Helpers\GameLobby;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;
use DB;

class SkyWind{

    public static function userLogin(){
        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
            ]
        ]);
        $url = ''.config('providerlinks.skywind.api_url').'/login';
        $requesttosend = [
             "secretKey" => config('providerlinks.skywind.seamless_key'), // Seamless key
             "username" => config('providerlinks.skywind.seamless_username'),
             "password" => config('providerlinks.skywind.seamless_password')
        ];
        Helper::saveLog('Skywind Key Request', config('providerlinks.skywind.provider_db_id'), json_encode($requesttosend), $requesttosend);
        $guzzle_response = $client->post($url,
                ['body' => json_encode($requesttosend)]
        );
        $client_response = json_decode($guzzle_response->getBody()->getContents());
        Helper::saveLog('Skywind Key Request', config('providerlinks.skywind.provider_db_id'), json_encode($client_response), $requesttosend);
        return $client_response;
    }

    // Note Used!
	public static function getJWToken(){
        $requesttosend = [
            "signatureKey" => config('providerlinks.skywind.seamless_key'),
            "username" => config('providerlinks.skywind.seamless_username'),
            "password" => config('providerlinks.skywind.seamless_password')
        ];
        $guzzle_response = $client->post($client_details->fund_transfer_url,
            ['body' => json_encode($requesttosend)]
        );
        $client_response = json_decode($guzzle_response->getBody()->getContents());
        return $client_response;
    }

	public static function getGameUrl($game_code, $ticket){
         $http = new Client();
         $requesttosend = [
             'gameCode' => $game_code,
             'ticket' => $ticket
         ];
         $response = $http->post(config('providerlinks.skywind.api_url').'fun/games/'.$game_code, [
            'form_params' => $requesttosend,
         ]);

        $response = $response->getBody()->getContents();
        Helper::saveLog('Skywind Game Launch', 21, $requesttosend, json_encode($response));
        return $response;
    }

    // DEPRECATED PROVIDER WILL USE THIS TO CALL US
    public static function getGetTicket($game_code){
        $http = new Client();
        $requesttosend = [
             'merch_id' => config('providerlinks.skywind.merchant_data'),
             'merch_pwd' => config('providerlinks.skywind.merchant_password'),
             // 'cust_id' => $request->token // optional
        ];
        $response = $http->post(config('providerlinks.skywind.api_url').'api/get_ticket'.$game_code, [
            'form_params' => $requesttosend,
        ]);
        $response = $response->getBody()->getContents();
        Helper::saveLog('Skywind Get Ticket', 21, $requesttosend, json_encode($response));
        return $response;
    }


}