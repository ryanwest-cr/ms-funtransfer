<?php

namespace App\Traits;
use GuzzleHttp\Client;
trait ConsumeExternalServices{

    public function performRequest($method,$requestUrl,$formParams=[],$headers=[]){
        $client = new Client([
            'base_uri' => 'https://svr-test01.betrnk.games',
        ]);
        $response = $client->request($method, $requestUrl, [
            'form_params' => $formParams,
            'headers' => $headers
        ]);
        return $response->getBody()->getContents();
    }

}