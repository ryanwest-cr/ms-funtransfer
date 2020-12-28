<?php
namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use App\Helpers\Helper;
use App\Helpers\GameLobby;
use App\Helpers\ProviderHelper;
use App\Payment;
use DB;
use ErrorException;

use function GuzzleHttp\json_decode;

class ClientRequestHelper{
    
    public static function getTransactionId($player_token,$game_round){
        $transaction = DB::table("player_session_tokens as pst")
                        ->leftJoin("game_transactions as gt","pst.token_id","=","gt.token_id")
                        ->where("pst.player_token",$player_token)
                        ->where("gt.round_id",$game_round)
                        ->first();
        if($transaction){
            $transaction->game_trans_id = $transaction->game_trans_id;
        }
        else{
            $transaction = DB::table("game_transactions")->latest()->first();
            $transaction->game_trans_id = $transaction->game_trans_id +1;
        }
        $transaction_ext = DB::table("game_transaction_ext")->latest()->first();
        $data = array(
            "transferId" => $transaction_ext->game_trans_ext_id + 1,
            "roundId" => $transaction->game_trans_id
        );
        return $data;
    }
    public static function fundTransfer($client_details,$amount,$game_code,$game_name,$transactionId,$roundId,$type,$rollback=false){
        $sendtoclient =  microtime(true);
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
            "datetsent" => Helper::datesent(),
            "gamedetails" => [
              "gameid" => $game_code,
              "gamename" => $game_name
            ],
            "fundtransferrequest" => [
                  "playerinfo" => [
                  "player_username"=>$client_details->username,
                  "client_player_id"=>$client_details->client_player_id,
                  "token" => $client_details->player_token
              ],
              "fundinfo" => [
                    "gamesessionid" => "",
                    "transactiontype" => $type,
                    "transactionId" => $transactionId, // this id is equivalent to game_transaction_ext game_trans_ext_id
                    "roundId" => $roundId,// this id is equivalent to game_transaction game_trans_id
                    "rollback" => $rollback,
                    "currencycode" => $client_details->default_currency,
                    "amount" => $amount #change data here
              ]
            ]
              ];
            $guzzle_response = $client->post($client_details->fund_transfer_url,
            [
                'on_stats' => function (TransferStats $stats) use ($requesttocient){
                    Helper::saveLog('TIME = '.$stats->getTransferTime() .' GEID = '.$requesttocient['fundtransferrequest']['fundinfo']['transactionId'].' '.$requesttocient['fundtransferrequest']['fundinfo']['transactiontype'], 999, json_encode($stats->getHandlerStats()), $requesttocient);
                },
                'body' => json_encode(
                    $requesttocient
            )],
            ['defaults' => [ 'exceptions' => false ]]
        );
        $client_reponse = json_decode($guzzle_response->getBody()->getContents());
        $client_response_time = microtime(true) - $sendtoclient;
        Helper::saveLog('fundTransfer(ClientRequestHelper)', 12, json_encode(["type"=>"funtransfer","game"=>$game_name]), ["clientresponse"=>$client_response_time,"client_reponse_data"=>$client_reponse,"client_request"=>$requesttocient]);
        $client_reponse->requestoclient = $requesttocient;
        //ClientRequestHelper::currencyRateConverter($client_details->default_currency,$roundId);
        return $client_reponse;
    }
    public static function currencyRateConverter($currency,$roundId=1){
        try{
            $currency_conversion_list = DB::table('currencies_convert_list')->where('currency_code',$currency)->first();
            $currency_conversion_list = json_decode($currency_conversion_list->convert_list,TRUE);
            $rates = array(
                "USD_rate"=> $currency_conversion_list["USD"]["rate"],
                "JPY_rate"=> $currency_conversion_list["JPY"]["rate"],
                "EUR_rate"=> $currency_conversion_list["EUR"]["rate"],
                "CNY_rate"=> $currency_conversion_list["CNY"]["rate"],
                "THB_rate"=> $currency_conversion_list["THB"]["rate"],
            );
            $update_transaction_rate = DB::table('game_transactions')->where('game_trans_id',$roundId)->update($rates);
            if($update_transaction_rate){
                Helper::saveLog('currencyRateConverter("success")', 0, json_encode($rates), "Transaction update successfully!");
            }
            else{
                Helper::saveLog('currencyRateConverter("failed")', 0, json_encode($rates), "Transaction did not exist!");
            }
        }
        catch(ErrorException $e){
            Helper::saveLog('currencyRateConverter("failed")', 0, json_encode($e->getMessage()), "Transaction did not exist!");
        }
    }

    /**
     * GLOBAL
     * Client Player Details API Call
     * @return [Object]
     * @param $[player_token] [<players token>]
     * @param $[refreshtoken] [<Default False, True token will be requested>]
     * 
     */
    public static function playerDetailsCall($player_token, $refreshtoken=false){
        $client_details = ProviderHelper::getClientDetails('token', $player_token);

        if($client_details){
            try{
                $client = new Client([
                    'headers' => [ 
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$client_details->client_access_token
                    ]
                ]);
                $datatosend = ["access_token" => $client_details->client_access_token,
                    "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
                    "type" => "playerdetailsrequest",
                    "datesent" => Helper::datesent(),
                    "gameid" => "",
                    "clientid" => $client_details->client_id,
                    "playerdetailsrequest" => [
                        "player_username"=>$client_details->username,
                        "client_player_id" => $client_details->client_player_id,
                        "token" => $player_token,
                        "gamelaunch" => true,
                        "refreshtoken" => $refreshtoken
                    ]
                ];
        
                $guzzle_response = $client->post($client_details->player_details_url,
                    ['body' => json_encode($datatosend)]
                );

                $client_response = json_decode($guzzle_response->getBody()->getContents());

                /** [START] Additional information needed for UltraPlay Integration **/

                if($client_response->playerdetailsresponse->status->code == 200) {
                    $client_response->playerdetailsresponse->internal_id = $client_details->player_id;
                    $client_response->playerdetailsresponse->is_test_player = ($client_details->test_player == 1 ? true : false);
                }

                /** [END] Additional information needed for UltraPlay Integration **/

                
                return $client_response;
            }catch (\Exception $e){
               return 'false';
            }
        }else{
            return 'false';
        }
    }



    /**
     * Custom Fundtransfer for CREDIT ASYNC PURPOSE -RiAN
     * USAGE just add _TG to existing fundtransfer, and action array parameter!
     * Required param, 
     * client_details, round_id, amount,game_name, game_provider,
     * 
     */
    // public static function fundTransfer_TG($client_details,$amount,$game_code,$game_name,$transactionId,$roundId,$type,$rollback=false,$action=array()){
    public static function fundTransfer_TG($client_details,$amount,$game_code,$game_name,$roundId,$type,$rollback=false,$action=array()){

        // if($type == 'credit'){
        //     $game_transaction_type = 2;
        // }else{
        //     $game_transaction_type = 1;
        // }
        
        // $gteid = ClientRequestHelper::generateGTEID($roundId, $action['provider_trans_id'], $roundId, $amount, $game_transaction_type, $action['provider_request'], $action['mw_response']);
        
        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$client_details->client_access_token,
            ]
        ]);
        $requesttocient = [
            "request_body" => [
                "access_token" => $client_details->client_access_token,
                "hashkey" => md5($client_details->client_api_key . $client_details->client_access_token),
                "type" => "fundtransferrequest",
                "datetsent" => Helper::datesent(),
                "gamedetails" => [
                    "gameid" => $game_code,
                    "gamename" => $game_name
                ],
                "fundtransferrequest" => [
                    "playerinfo" => [
                        "player_username" => $client_details->username,
                        "client_player_id" => $client_details->client_player_id,
                        "token" => $client_details->player_token
                    ],
                    "fundinfo" => [
                        "gamesessionid" => "",
                        "transactiontype" => $type,
                        "transactionId" => 999, # THIS WILL BE OVERWRITTEN IN FundTransferProcessorController!
                        "roundId" => $roundId, 
                        "rollback" => $rollback,
                        "currencycode" => $client_details->default_currency,
                        "amount" => $amount 
                    ]
                ]
            ],
            "header" => [
                "auth" => 'Bearer '.$client_details->client_access_token,
                "endpoint" => $client_details->fund_transfer_url
            ],
        ];
        
        # REQUIRED PARAMETER IN ACTION ARRAY
        if(count($action) > 0){
            $requesttocient["action"] = $action;
        }

        try{
            # This will call our server for async request! Cut The Connection within 10ms and leave it to the server!
            $guzzle_response = $client->post(config('providerlinks.oauth_mw_api.mwurl').'/tigergames/fundtransfer',
                [
                    'on_stats' => function (TransferStats $stats) use ($requesttocient) {
                        Helper::saveLog('TIME = ' . $stats->getTransferTime() . ' GEID = ' . $requesttocient['fundtransferrequest']['fundinfo']['transactionId'] . ' ' . $requesttocient['fundtransferrequest']['fundinfo']['transactiontype'], 999, json_encode($stats->getHandlerStats()), $requesttocient);
                    },
                    'timeout' => 0.10, # enough tobe received by the server!
                    'body' => json_encode($requesttocient)
                ],
                ['defaults' => ['exceptions' => false]]
            );
        }catch(\Exception $e){
            if($type == 'debit'){
                $balance = $client_details->balance - $amount;
            }else{
                $balance = $client_details->balance + $amount;
            }
            $response = array(
                "fundtransferresponse" => array(
                    "status" => array(
                        "code" => 200,
                        "status" => "OK",
                        "message" => "The request was initiated by TG!"
                    ),
                    'balance' => $balance
                )
            );
            $client_reponse = json_decode(json_encode($response));
            $client_reponse->requestoclient = $requesttocient;
            return $client_reponse;
        }
        // $client_reponse = json_decode($guzzle_response->getBody()->getContents());
        // $client_response_time = microtime(true) - $sendtoclient;
        // Helper::saveLog('fundTransfer(ClientRequestHelper)', 12, json_encode(["type"=>"funtransfer","game"=>$game_name]), ["clientresponse"=>$client_response_time,"client_reponse_data"=>$client_reponse,"client_request"=>$requesttocient]);
        // $client_reponse->requestoclient = $requesttocient;
        // //ClientRequestHelper::currencyRateConverter($client_details->default_currency,$roundId);
        // return $client_reponse;
    }




     /**
     * NOTE ONLY FOR WIN!
     * PL
     * 
     */
    // public function createGameTransExt($game_trans_id, $provider_trans_id, $round_id, $amount, $game_type, $provider_request, $mw_response, $mw_request, $client_response, $transaction_detail, $general_details=null){
    public static function generateGTEID($game_trans_id, $provider_trans_id, $round_id, $amount, $game_type, $provider_request, $mw_response){
        $gametransactionext = array(
            "game_trans_id" => $game_trans_id, #RoundID/GameTransactionID
            "provider_trans_id" => $provider_trans_id, # PL
            "round_id" => $round_id, # PL
            "amount" => $amount, # PL
            "game_transaction_type"=>$game_type, # PL
            "provider_request" => json_encode($provider_request), #PL
            "mw_response" =>json_encode($mw_response), #PL
            "transaction_detail" =>'pending' #PL
            
            // "client_response" =>json_encode($client_response),
            // "mw_request"=>json_encode($mw_request), 
            // "general_details" =>json_encode($general_details)
        );
        $gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
        return $gamestransaction_ext_ID;
    }
        
    /**
     * NOTE ONLY FOR WIN!
     */
    public static function updateGTEID($game_trans_ext_id, $mw_request, $client_response,$transaction_detail='success',$general_details='custom'){
        $update = DB::table('game_transaction_ext')
        ->where('game_trans_ext_id', $game_trans_ext_id)
        ->update([
            "mw_request"=>json_encode($mw_request),
            "client_response" =>json_encode($client_response),
            "transaction_detail" =>json_encode($transaction_detail),
            "general_details" =>json_encode($general_details)
        ]);
        // Helper::saveLog('updatecreateGameTransExt', 999, json_encode(DB::getQueryLog()), "TIME updatecreateGameTransExt");
        return ($update ? true : false);
    }
    
}