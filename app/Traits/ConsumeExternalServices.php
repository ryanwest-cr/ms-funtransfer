<?php

namespace App\Traits;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use App\Helpers\Helper;
trait ConsumeExternalServices{

    public function performRequest($method,$requestUrl,$formParams=[],$headers=[]){
        try{
            $client = new Client([
                'base_uri' => $this->baseUri,
            ]);
            $response = $client->request($method, $requestUrl, [
                'form_params' => $formParams,
                'headers' => $headers
            ]);
    
            return $response->getBody()->getContents();
        }
        catch(\Exception $e){
            $response = $e->getResponse();
            Helper::saveLog('ConsumeExternalServices', 888, json_encode(["request"=>$e->getRequest(),"response"=>$e->getResponse()]), "");
        }
    }

}