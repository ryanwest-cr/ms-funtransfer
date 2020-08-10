<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SimpleXMLElement;
use App\Helpers\PNGHelper;
use GuzzleHttp\Client;
use App\Helpers\Helper;
use DB;
class PNGController extends Controller
{
    //
    public function authenticate(Request $request){
        $data = $request->getContent();
        $xmlparser = new SimpleXMLElement($data);
        $accessToken = "secrettoken";
        if($xmlparser->externalGameSessionId){
            $client_details = $this->_getClientDetails('token', $xmlparser->username);
            if($client_details){
                $client = new Client([
                    'headers' => [ 
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$client_details->client_access_token
                    ]
                ]);
                $guzzle_response = $client->post($client_details->player_details_url,
                    ['body' => json_encode(
                            [
                                "access_token" => $client_details->client_access_token,
                                "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
                                "type" => "playerdetailsrequest",
                                "datesent" => "",
                                "gameid" => "",
                                "clientid" => $client_details->client_id,
                                "playerdetailsrequest" => [
                                    "client_player_id"=>$client_details->client_player_id,
                                    "token" => $client_details->player_token,
                                    "gamelaunch" => "true"
                                ]]
                    )]
                );
                $client_response = json_decode($guzzle_response->getBody()->getContents());
                Helper::saveLog('AuthPlayer(PNG)', 12, json_encode($xmlparser),$client_response);

                $array_data = array(
                    "externalId" => $client_details->player_id,
                    "statusCode" => 0,
                    "statusMessage" => "ok",
                    "userCurrency" => $client_details->default_currency,
                    "country" => "SE",
                    "birthdate"=> "1990-04-29",
                    "registration"=> $client_details->created_at,
                    "language" => "EN",
                    "externalGameSessionId" => $xmlparser->username,
                    "real"=> number_format($client_response->playerdetailsresponse->balance,2,'.', ''),
                );
                return PNGHelper::arrayToXml($array_data,"<authenticate/>");
            }
            else{
                $msg = array(
                    "uid" => $data["uid"],
                    "error"=>array(
                        "code" => "INVALID_TOKEN"
                    ),
                );
                return response($msg,200)->header('Content-Type', 'application/json');
            }
        }
        
    }
    public function reserve(Request $request){
        $data = $request->getContent();
        $xmlparser = new SimpleXMLElement($data);
        $accessToken = "secrettoken";
        if($xmlparser->externalGameSessionId){
            $client_details = $this->_getClientDetails('token', $xmlparser->externalGameSessionId);
            if($client_details){
                $game_transaction = Helper::checkGameTransaction($xmlparser->transactionId);
                if(Helper::getBalance($client_details) < round($xmlparser->real,2)){
                    $response =array(
                        "data" => array(
                            "statusCode"=>2,
                            "username" => $client_details->username,
                            "balance" =>round(Helper::getBalance($client_details)*100,2),
                        ),
                        "error" => array(
                            "title"=> "Not Enough Balance",
                            "description"=>"Not Enough Balance"
                        )
                    ); 
                    Helper::saveLog('betGameInsuficientN(PNG)', 12, json_encode($xmlparser), $response);
                    return response($response,400)
                    ->header('Content-Type', 'application/json');
                }
                $client = new Client([
                    'headers' => [ 
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$client_details->client_access_token
                    ]
                ]);
                $requesttocient = [
                    "access_token" => $client_details->client_access_token,
                    "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
                    "type" => "fundtransferrequest",
                    "datetsent" => "",
                    "gamedetails" => [
                      "gameid" => "",
                      "gamename" => ""
                    ],
                    "fundtransferrequest" => [
                          "playerinfo" => [
                          "client_player_id"=>$client_details->client_player_id,
                          "token" => $client_details->player_token
                      ],
                      "fundinfo" => [
                            "gamesessionid" => "",
                            "transactiontype" => "debit",
                            "transferid" => "",
                            "rollback" => "false",
                            "currencycode" => $client_details->currency,
                            "amount" => round($xmlparser->real,2) #change data here
                      ]
                    ]
                      ];
                    $guzzle_response = $client->post($client_details->fund_transfer_url,
                    ['body' => json_encode(
                            $requesttocient
                    )],
                    ['defaults' => [ 'exceptions' => false ]]
                );

                $client_response = json_decode($guzzle_response->getBody()->getContents());
                $balance = round($client_response->fundtransferresponse->balance * 100,2);
                $game_details = Helper::getInfoPlayerGameRound($xmlparser->externalGameSessionId);
                $json_data = array(
                    "transid" => $xmlparser->transactionId,
                    "amount" => round($xmlparser->real,2),
                    "roundid" => $xmlparser->roundId
                );
                if(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "200"){
                    
                    $array_data = array(
                        "real" => $balance,
                        "statusCode" => 0,
                        "statusMessage" => "ok",
                    );
                    
                    $game = Helper::getGameTransaction($xmlparser->externalGameSessionId,$xmlparser->roundId);
                    if(!$game){
                        $gametransactionid=Helper::createGameTransaction('debit', $json_data, $game_details, $client_details); 
                    }
                    else{
                        
                    }
                    PNGHelper::createPNGGameTransactionExt($gametransactionid,$xmlparser,$requesttocient,$array_data,$client_response,1);
                    //Helper::createICGGameTransactionExt($gametransactionid,json,$requesttocient,$response,$client_response,1);  
                    return PNGHelper::arrayToXml($array_data,"<reserve/>");
                }
                elseif(isset($client_response->fundtransferresponse->status->code) 
                && $client_response->fundtransferresponse->status->code == "402"){
                    $response =array(
                        "data" => array(
                            "statusCode"=>2,
                            "username" => $client_details->username,
                            "balance" =>$balance,
                        ),
                        "error" => array(
                            "title"=> "Not Enough Balance",
                            "description"=>"Not Enough Balance"
                        )
                    ); 
                    Helper::saveLog('betGameInsuficient(ICG)', 12, json_encode($json), $response);
                    return response($response,400)
                    ->header('Content-Type', 'application/json');
                }
                

            }
        } 
    }
    private function _getClientDetails($type = "", $value = "") {

		$query = DB::table("clients AS c")
				 ->select('p.client_id', 'p.player_id', 'p.client_player_id','p.username', 'p.email', 'p.language', 'p.currency','p.created_at', 'pst.token_id', 'pst.player_token' , 'pst.status_id', 'p.display_name','c.default_currency', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
				 ->leftJoin("players AS p", "c.client_id", "=", "p.client_id")
				 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
				 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
				 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id");
				 
				if ($type == 'token') {
					$query->where([
				 		["pst.player_token", "=", $value],
				 		["pst.status_id", "=", 1]
				 	]);
				}

				if ($type == 'player_id') {
					$query->where([
				 		["p.player_id", "=", $value],
				 		["pst.status_id", "=", 1]
				 	]);
				}

				 $result= $query->first();

		return $result;
    }
}
