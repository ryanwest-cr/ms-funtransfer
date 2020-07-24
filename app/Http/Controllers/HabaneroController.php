<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ProviderHelper;
use DB;
use App\Helpers\Helper;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class HabaneroController extends Controller
{
    
    public function playerdetailrequest(Request $request){
        Helper::saveLog('Habanero Gaming Request', 24, file_get_contents("php://input"), 'ENDPOINT HIT');

        $data = file_get_contents("php://input");
        $details = json_decode($data);
        
        $client_details = Providerhelper::getClientDetails('token', $details->playerdetailrequest->token);
        if($client_details == null) {
            $response = [
                "playerdetailresponse" => [
                    "status" => [
                        "success" => false,
                        "message" => "Player does not exist"
                    ]
                ]
            ];
        }else{
            
            $player_details = Providerhelper::playerDetailsCall($client_details->player_token,true);
            $response = [
                "playerdetailresponse" => [
                    "status" => [
                        "success" => true,
                        "autherror" => false,
                        "message" => ""
                    ]
                ],
                "accountid" => $client_details->player_id,
                "accountname" => $client_details->username,
                "balance" => $player_details->playerdetailsresponse->balance,
                "currencycode" => "USD"
            ];
        }
       
        Helper::saveLog('Habanero Gaming Response', 24, json_encode($response), 'ENDPOINT HIT');

        return $response;
    }


    public function responsetosend($client_access_token,$client_api_key,$game_code,$game_name,$client_player_id,$player_token,$amount,$client,$fund_transfer_url,$transtype){
        Helper::saveLog('Habanero Gaming', 24, file_get_contents("php://input"), 'ENDPOINT HIT');
        $requesttosend = [
            "access_token" => $client_access_token,
            "hashkey" => md5($client_api_key.$client_access_token),
            "type" => "fundtransferrequest",
            "datesent" => Helper::datesent(),
            "gamedetails" => [
            "gameid" => $game_code, // $game_code
            "gamename" => $game_name
            ],
            "fundtransferrequest" => [
                "playerinfo" => [
                "client_player_id" => $client_player_id,
                "token" => $player_token,
                ],
                "fundinfo" => [
                        "gamesessionid" => "",
                        "transactiontype" => $transtype,
                        "transferid" => "",
                        "rollback" => false,
                        "currencycode" => "USD",
                        "amount" => $amount
                ],
            ],
        ];
        
        $guzzle_response = $client->post($fund_transfer_url,
            ['body' => json_encode($requesttosend)]
        );

        $client_response = json_decode($guzzle_response->getBody()->getContents());

        return $client_response;
    }

    public function fundtransferrequest(Request $request){

        Helper::saveLog('Habanero Gaming', 24, file_get_contents("php://input"), 'ENDPOINT HIT');

        $data = file_get_contents("php://input");
        $details = json_decode($data);

        $client_details = Providerhelper::getClientDetails('token', $details->fundtransferrequest->token);
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);

        $game_details = Helper::findGameDetails('game_code', 23, $details->basegame->keyname);
        
        if($player_details->playerdetailsresponse->balance > abs($details->fundtransferrequest->funds->fundinfo[0]->amount)){
            $client = new Client([
                'headers' => [ 
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$client_details->client_access_token
                ]
            ]);

            if($details->fundtransferrequest->funds->debitandcredit == 'true'){

                foreach($details->fundtransferrequest->funds->fundinfo as $funds){

                    if($funds->gamestatemode == 1){
                        $clientDetalsResponse = $this->responsetosend($client_details->client_access_token, $client_details->client_api_key, $game_details->game_code, $game_details->game_name, $client_details->client_player_id, $client_details->player_token, abs($funds->amount) , $client, $client_details->fund_transfer_url, "debit");
                    }elseif($funds->gamestatemode == 0){
                        $clientDetalsResponse = $this->responsetosend($client_details->client_access_token, $client_details->client_api_key, $game_details->game_code, $game_details->game_name, $client_details->client_player_id, $client_details->player_token, abs($funds->amount) , $client, $client_details->fund_transfer_url, "credit");
                    }
                }
                $response = [
                    "fundtransferresponse" => [
                        "status" => [
                            "success" => true,
                            "successdebit" => true,
                            "successcredit" => true
                        ],
                        "balance" => $clientDetalsResponse->fundtransferresponse->balance,
                        "currencycode" => "USD"
                    ]
                ];
                
            }else{

                $clientDetalsResponse = $this->responsetosend($client_details->client_access_token, $client_details->client_api_key, $game_details->game_code, $game_details->game_name, $client_details->client_player_id, $client_details->player_token, abs($details->fundtransferrequest->funds->fundinfo[0]->amount) , $client, $client_details->fund_transfer_url, "debit");

                $response = [
                    "fundtransferresponse" => [
                        "status" => [
                            "success" => true,
                        ],
                        "balance" => $clientDetalsResponse->fundtransferresponse->balance,
                        "currencycode" => "USD"
                    ]
                ];
            }
          
        }else{
            $response = [
                "fundtransferresponse" => [
                    "status" => [
                        "success" => false,
                        "nofunds" => true,
                    ],
                    "balance" => $player_details->playerdetailsresponse->balance,
                    "currencycode" => "USD"
                ]
            ];
        }

        return $response;
    }
    public function queryrequest(Request $request){
        Helper::saveLog('Habanero Gaming', 24, file_get_contents("php://input"), 'ENDPOINT HIT');
        return "hi";
    }

}
