<?php

namespace App\Helpers;

use GuzzleHttp\Client;

class MGHelper{


     public static function stsTokenizer(){
        $http = new Client();
        $response = $http->post('https://sts-tigergaming.k2net.io/connect/token', [
            'form_params' => [
                'grant_type' => config('providerlinks.microgaming.grant_type'),
                'client_id' => config('providerlinks.microgaming.client_id'),
                'client_secret' => config('providerlinks.microgaming.client_secret'),
            ],
        ]);

        return json_decode((string) $response->getBody(), true)["access_token"];
     }
}

?>