<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Hash;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
// use App\Helpers\AWSHelper;
use App\Helpers\ClientRequestHelper;
use GuzzleHttp\Client;
use GuzzleHttp\TransferStats;
use GuzzleHttp\Exception\GuzzleException;

use DB;



/**
 * [TEST ONLY XD]
 * Make Call To The Client Asynchronous BackGround  (BETA) -RiAN
 * 
 */
class FundtransferProcessorController extends Controller
{
    public static function fundTransfer(Request $request){

        Helper::saveLog('fundTransfer', 999, json_encode([]), "MAGIC END HIT");
        $payload = json_decode(file_get_contents("php://input"));

        if($payload->request_body->fundtransferrequest->fundinfo->transactiontype == 'credit'){
            $game_transaction_type = 2;
        }else{
            $game_transaction_type = 1;
        }

        try{
            $gteid = ClientRequestHelper::generateGTEID(
                $payload->request_body->fundtransferrequest->fundinfo->roundId,
                $payload->action->provider->provider_trans_id, 
                $payload->action->provider->provider_round_id, 
                $payload->request_body->fundtransferrequest->fundinfo->amount,
                $game_transaction_type, 
                $payload->action->provider->provider_request, 
                $payload->action->mwapi->mw_response
            );
        }catch(\Exception $e){
            Helper::saveLog('fundTransfer generateGTEID', 888, json_encode([]), $e->getMessage().' '.$e->getLine().' '.$e->getFile());
        }

        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
                'Authorization' => $payload->header->auth
            ]
        ]);
        $requesttocient = [
                "access_token" => $payload->request_body->access_token,
                "hashkey" => $payload->request_body->hashkey,
                "type" => "fundtransferrequest",
                "datetsent" => $payload->request_body->datetsent,
                "gamedetails" => [
                    "gameid" => $payload->request_body->gamedetails->gameid,
                    "gamename" => $payload->request_body->gamedetails->gamename
                ],
                "fundtransferrequest" => [
                "playerinfo" => [
                    "player_username" => $payload->request_body->fundtransferrequest->playerinfo->player_username,
                    "client_player_id" => $payload->request_body->fundtransferrequest->playerinfo->client_player_id,
                    "token" => $payload->request_body->fundtransferrequest->playerinfo->token,
                ],
                "fundinfo" => [
                    "gamesessionid" => "",
                    "transactiontype" => $payload->request_body->fundtransferrequest->fundinfo->transactiontype,
                    "transactionId" => $gteid, # Generated Here!
                    "roundId" => $payload->request_body->fundtransferrequest->fundinfo->roundId,
                    "rollback" => $payload->request_body->fundtransferrequest->fundinfo->rollback,
                    "currencycode" => $payload->request_body->fundtransferrequest->fundinfo->currencycode,
                    "amount" => $payload->request_body->fundtransferrequest->fundinfo->amount,
                ]
            ]
        ];
        try{
            $guzzle_response = $client->post($payload->header->endpoint,
            [
                'on_stats' => function (TransferStats $stats) use ($requesttocient){
                    Helper::saveLog('TIME = '.$stats->getTransferTime() .' GEID = '.$requesttocient['fundtransferrequest']['fundinfo']['transactionId'].' '.$requesttocient['fundtransferrequest']['fundinfo']['transactiontype'], 999, json_encode($stats->getHandlerStats()), $requesttocient);
                },
                'body' => json_encode($requesttocient)
            ],
            ['defaults' => [ 'exceptions' => false ]]
            );
            $client_response = json_decode($guzzle_response->getBody()->getContents());

            if(isset($client_response->fundtransferresponse->status->code) 
            && $client_response->fundtransferresponse->status->code == "200"){
                # NOTE DEBIT AND CREDIT SOMETIMES HAS DIFFERENT WAY OF UPDATING JUST USE YOUR CUSTOM!!

                # You can add your own helper for custom gametransaction update like general_details etc!
                if($payload->action->type == 'custom'){
                    if($payload->action->custom->provider == 'allwayspin'){
                        # No need to update my gametransaction data :) 1 way flight, only the gametransaction extension
                        $gteid = ClientRequestHelper::updateGTEID(
                            $gteid, 
                            $requesttocient, 
                            $client_response,
                            'success',
                            'success'
                        );
                    }
                    if($payload->action->custom->provider == 'tpp'){
                        $updateGameTransExt = DB::table('game_transaction_ext')->where('game_trans_ext_id','=',$payload->mwapi->roundId)->update(["amount" => $payload->request_body->fundtransferrequest->fundinfo->amount ,"game_transaction_type" => $game_transaction_type, "provider_request" => json_encode($payload->action->provider->provider_request),"mw_response" => json_encode($payload->action->mwapi->mw_response),"mw_request" => json_encode($requesttocient),"client_response" => json_encode($client_response),"transaction_detail" => "success" ]);
                    }
                }else{
                    # Normal/general Update Game Transaction if you need to update your gametransaction you can add new param to the action payload!
                    
                }

            }elseif(isset($client_response->fundtransferresponse->status->code) 
            && $client_response->fundtransferresponse->status->code == "402"){
                # Create a Restriction Entry
                # Sidenote
                Providerhelper::createRestrictGame($payload->action->mwapi->game_id,$payload->action->mwapi->player_id,$gteid, $requesttocient);
                //  ProviderHelper::updatecreateGameTransExt($payload->request_body->fundtransferrequest->fundinfo->transactionId, 'FAILED', 'FAILED', $client_response->requestoclient, $client_response,'success');

            }
            Helper::saveLog('fundTransfer', 999, json_encode([]), "MAGIC END HIT RECEIVED");
        }catch(\Exception $e){
            Helper::saveLog('fundTransfer TID'.$requesttocient['fundtransferrequest']['fundinfo']['transactionId'], 888, json_encode($requesttocient), $e->getMessage().' '.$e->getLine().' '.$e->getFile());
            Providerhelper::createRestrictGame($payload->action->mwapi->game_id,$payload->action->mwapi->player_id,$gteid, $requesttocient);
        }
    }
   
}
