<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
class CurrencyController extends Controller
{
    // Test
    public function currency(){
        $client = new Client([
            'headers' => ['x-rapidapi-host' => 'currency-converter5.p.rapidapi.com',
            'x-rapidapi-key' => '8206256315mshcd8655ee7f5800dp1bf51bjsn355caa8858be',
            'Content-Type' => 'application/x-www-form-urlencoded'],
            'http_errors' => false,
        ]);
        $response = $client->get('https://currency-converter5.p.rapidapi.com/currency/convert', [
        ]);
        $data = json_decode($response->getBody(),TRUE);
        $currency = array(
            "main_currency"=>"USD",
            "rates"=>array()
        );
        foreach($data["rates"] as $key=>$rate){
            $usdcurrency = array(
                "currency" =>$key,
                "currency_name"=>$rate["currency_name"],
                "rate" => number_format((1/(float)$rate["rate"])/(1/(float)$data["rates"]["USD"]["rate"]), 5, '.', ''),
            );
            array_push($currency["rates"],$usdcurrency);
        }
        return $currency;
    }
}
