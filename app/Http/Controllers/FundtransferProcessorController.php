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
use App\Helpers\Game;
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
        // sleep(10);
        try{
            if($payload->action->custom->provider == 'tpp'){
                $gteid = $payload->action->mwapi->roundId;
            }else{
                $gteid = ClientRequestHelper::generateGTEID(
                    $payload->request_body->fundtransferrequest->fundinfo->roundId,
                    $payload->action->provider->provider_trans_id, 
                    $payload->action->provider->provider_round_id, 
                    $payload->request_body->fundtransferrequest->fundinfo->amount,
                    $game_transaction_type, 
                    $payload->action->provider->provider_request, 
                    $payload->action->mwapi->mw_response
                );
            }
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
                    ProviderHelper::saveLog('RID'.$requesttocient['fundtransferrequest']['fundinfo']['roundId']. 'TIME = '.$stats->getTransferTime(), 999, json_encode($stats->getHandlerStats()), $requesttocient);
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
                # If you dont want to use custom update change payload type to general!
                if($payload->action->type == 'custom'){
                    if($payload->action->custom->provider == 'allwayspin'){
                        # No need to update my gametransaction data :) 1 way flight, only the gametransaction extension
                        $gteid = ClientRequestHelper::updateGTEID($gteid,$requesttocient,$client_response,'success','success' );
                    }
                    elseif($payload->action->custom->provider == 'evolution'){
                        $gteid = ClientRequestHelper::updateGTEID($gteid,$requesttocient,$client_response,'success','success' );
                    }
                    if($payload->action->custom->provider == 'tpp'){
                        $updateGameTransExt = DB::table('game_transaction_ext')->where('game_trans_ext_id','=',$gteid)->update(["amount" => $payload->request_body->fundtransferrequest->fundinfo->amount ,"game_transaction_type" => $game_transaction_type, "provider_request" => json_encode($payload->action->provider->provider_request),"mw_response" => json_encode($payload->action->mwapi->mw_response),"mw_request" => json_encode($requesttocient),"client_response" => json_encode($client_response),"transaction_detail" => "success" ]);
                    }
                }else{
                    # Normal/general Update Game Transaction if you need to update your gametransaction you can add new param to the action payload!
                    $gteid = ClientRequestHelper::updateGTEID($gteid,$requesttocient,$client_response,'success','success' );
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


    /**
     * HI GUYZZZ LEGENDARY MARVIN HERE
     * THIS METHOD FROM TWO CALLBACK DEBIT AND CREDIT AND FUNDSTRANFER PROCESS 
     * METHOD USE INSERT/UPDATE/FUNDSTRANFER
     * REQUEST FORMAT EXAMPLE
     * body => [
          "token" => "n58ec5e159f769ae0b7b3a0774fdbf80"
          "rollback" => false
          "game_details" => [
            "game_code" => "GHG_HAWAIIAN_DREAM"
            "provider_id" => 18
          ]
          "game_transaction" => [
            "provider_trans_id" => "ORYX2P168_1614181"
            "round_id" => "ORYX2P168_2040870"
            "amount" => 4
          ]
          "provider_request" => [
            "sessionToken" => "n58ec5e159f769ae0b7b3a0774fdbf80"
            "playerId" => "4411"
            "roundId" => "ORYX2P168_1614181"
            "roundAction" => "CLOSE"
            "gameCode" => "GHG_HAWAIIAN_DREAM"
            "win" => [
              "transactionId" => "ORYX2P168_2040870"
              "amount" => 400
              "timestamp" => 1609070687
            ]
          ]
          //this for the extension for credit process
          "existing_bet" => [
            "game_trans_id" => 158
          ]
        ]
     */

    public function backgroundProcessDebitCreditFund(Request $request, $type)
    {
        $response = [];
        $details = json_decode(file_get_contents("php://input"), true);
        Helper::saveLog('backgroundProcessDebitCreditFund', 88, json_encode($details), "ENDPOINT HIT");
        $client_details = ProviderHelper::getClientDetails('token', $details["token"]);
       
        
        $game_details = Game::find($details["game_details"]["game_code"], $details["game_details"]["provider_id"]);
        
        $provider_trans_id = $details["game_transaction"]["provider_trans_id"];
        $round_id =  $details["game_transaction"]["round_id"];
        $amount = $details["game_transaction"]["amount"]; // amount should be fixed after sending data
        $provider_request = $details["provider_request"];
        
        
        if ($type == "debit") {
            $pay_amount = 0;
            $income = 0;
            $method = 1;
            $win_or_lost = 5; // 0 lost,  5 processing
            $payout_reason = ProviderHelper::updateReason(2);
            $game_transaction_id  = ProviderHelper::createGameTransaction($client_details->token_id, $game_details->game_id, $amount,  $pay_amount, $method, $win_or_lost, $payout_reason, $payout_reason, $income, $provider_trans_id, $round_id);
            $game_trans_ext_id = ProviderHelper::createGameTransExtV2($game_transaction_id, $provider_trans_id, $round_id, $amount, 1, $provider_request);
        } elseif ($type == "credit") {
            $game_transaction_id = $details["existing_bet"]["game_trans_id"];
            $existing_bet = ProviderHelper::findGameTransaction($game_transaction_id, "game_transaction");
            $win = $amount > 0  ?  1 : 0;  /// 1win 0lost
            $type = $amount > 0  ? "credit" : "debit";
            $request_data = [
                'win' => $win,
                'amount' => $amount,
                'payout_reason' => ProviderHelper::updateReason(1),
            ];
           
            $game_trans_ext_id = ProviderHelper::createGameTransExtV2($existing_bet->game_trans_id, $provider_trans_id, $round_id, $amount, 2, $provider_request);
            Helper::updateGameTransaction($existing_bet,$request_data,$type);
        }
        

        try {
            $client_response = ClientRequestHelper::fundTransfer($client_details, $amount, $game_details->game_code, $game_details->game_name, $game_trans_ext_id, $game_transaction_id, $type, $details["rollback"]);
        } catch (\Exception $e) {
            ProviderHelper::updatecreateGameTransExt($game_trans_ext_id, 'FAILED', 'FAILED', 'FAILED', 'FAILED', 'FAILED', 'FAILED');
            ProviderHelper::updateGameTransactionStatus($game_transaction_id, 2, 99);
            $mw_payload = ProviderHelper::fundTransfer_requestBody($client_details,$amount,$game_details->game_code,$game_details->game_name,$game_trans_ext_id,$game_transaction_id,$type);
            ProviderHelper::createRestrictGame($game_details->game_id, $client_details->player_id, $game_trans_ext_id, $mw_payload);
            Helper::saveLog('backgroundProcessDebitCreditFund FATAL ERROR', 88, json_encode($details), Helper::datesent());
        }
       
        if(isset($client_response->fundtransferresponse->status->code) && $client_response->fundtransferresponse->status->code == "200") 
        {
            // updateting balance
            ProviderHelper::_insertOrUpdate($client_details->token_id, $client_response->fundtransferresponse->balance); 
            
            // AND HERE PROVIDER LOGIC USE FUNCTION MAKE ORGANIZE
            // if ($provider_id = 12) {
                // function call here
            // }
            $response = [
                "status" => "ok",
                "balance" => $client_response->fundtransferresponse->balance,
            ];
            $this->updateGameTransactionExt($game_trans_ext_id,$client_response->requestoclient,$client_response->fundtransferresponse,$response);
            Helper::saveLog('backgroundProcessDebitCreditFund', 88, json_encode($details), $response);
        } 
        elseif (isset($client_response->fundtransferresponse->status->code) && $client_response->fundtransferresponse->status->code == "402")
        {
            ProviderHelper::_insertOrUpdate($client_details->token_id, $client_response->fundtransferresponse->balance); 
            $response = [
                "status" => "error",
                "balance" => $client_response->fundtransferresponse->balance,
            ];
            $this->updateGameTransactionExt($game_trans_ext_id,$client_response->requestoclient,$client_response->fundtransferresponse,$response);
            ProviderHelper::updateGameTransactionStatus($game_transaction_id, 2, 99);
            // ProviderHelper::createRestrictGame($game_details->game_id, $client_details->player_id, $game_trans_ext_id, $client_response->requestoclient);
            Helper::saveLog('backgroundProcessDebitCreditFund', 88, json_encode($details), $response);
        }
    }

    public static function updateGameTransactionExt($gametransextid,$mw_request,$mw_response,$client_response){
        $gametransactionext = array(
            "mw_request"=>json_encode($mw_request),
            "mw_response" =>json_encode($mw_response),
            "client_response" =>json_encode($client_response),
            "transaction_detail" => "null",
        );
        DB::table('game_transaction_ext')->where("game_trans_ext_id",$gametransextid)->update($gametransactionext);
    }
   
}
