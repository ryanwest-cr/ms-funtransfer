<?php

namespace App\Http\Controllers;

use App\Helpers\ClientRequestHelper;
use Illuminate\Http\Request;
// use App\Helpers\ProviderHelper; # Migrated To GoldenFHelper Query Builder To RAW SQL - RiAN
// use App\Helpers\GoldenFHelper; # Migrated To TransferWalletHelper (Centralization) Query Builder To RAW SQL DONT REMOVE COMMENT FOR NOW - RiAN
use App\Helpers\TransferWalletHelper;
use App\Helpers\SessionWalletHelper;
use DB;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;

class GoldenFController extends Controller
{

    public $provider_db_id, $api_url, $operator_token, $secret_key, $wallet_code;
    public function __construct(){
        $this->api_url = config("providerlinks.goldenF.api_url");
        $this->provider_db_id = config("providerlinks.goldenF.provider_id");
        $this->secret_key = config("providerlinks.goldenF.secrete_key");
        $this->operator_token = config("providerlinks.goldenF.operator_token");
        $this->wallet_code = config("providerlinks.goldenF.wallet_code");
    }

    /**
     * [GetPlayerBalance description]
     * @param Request $request [Trigger  by Play Game Iframe]
     * 
     */
    public function getPlayerBalance(Request $request)
    {
        if(!$request->has("token")){
            $msg = array("status" =>"error","message" => "Token Invalid");
           TransferWalletHelper::saveLog('GetPlayerBalance', $this->provider_db_id,json_encode($request->all()), $msg);
            return response($msg,200)->header('Content-Type', 'application/json');
        }

        $client_details = TransferWalletHelper::getClientDetails('token', $request->token);
        if($client_details == null || $client_details == 'false'){
            $msg = array("status" =>"error","message" => "Token Invalid");
           TransferWalletHelper::saveLog('GetPlayerBalance', $this->provider_db_id,json_encode($request->all()), $msg);
            return response($msg,200)->header('Content-Type', 'application/json');
        }

        try {
            $client_response = TransferWalletHelper::playerDetailsCall($client_details);  
            $balance = round($client_response->playerdetailsresponse->balance,2);
            $msg = array(
                "status" => "ok",
                "message" => "Balance Request Success",
                "balance" => $balance
            );
           TransferWalletHelper::saveLog('GetPlayerBalance', $this->provider_db_id,json_encode($request->all()), $msg);
            return response($msg,200)->header('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $msg = array(
                "status" =>"error",
                "message" => $e->getMessage()
            );
           TransferWalletHelper::saveLog('GetPlayerBalance', $this->provider_db_id,json_encode($request->all()), $msg);
            return response($msg,200)->header('Content-Type', 'application/json');
        }

    }


    public function getPlayerWalletBalance(Request $request){
        $client_details = TransferWalletHelper::getClientDetails('token', $request->token);
        if($client_details == null || $client_details == 'false'){
            $msg = array("status" =>"error","message" => "Token Invalid");
           TransferWalletHelper::saveLog('GetPlayerBalance', $this->provider_db_id,json_encode($request->all()), $msg);
            return response($msg,200)->header('Content-Type', 'application/json');
        }
        $http = new Client();
        $response = $http->post($this->api_url."/GetPlayerBalance",[
           'form_params' => [
            'secret_key' => $this->secret_key,
            'operator_token' => $this->operator_token,
            'player_name' => "TG_".$client_details->player_id,
            'wallet_code' => $this->wallet_code
            ]
        ]);
        $golden_response_balance = json_decode((string) $response->getBody(), true);
        $msg = array(
            "status" =>"success",
            "balance" => $golden_response_balance['data']['balance'],
            "message" => 'Something went wrong',
        );
        return response($msg,200)->header('Content-Type', 'application/json');
    }

    // public static function gameLaunch(){
    //     $operator_token = config("providerlinks.goldenF.operator_token");
    //     $api_url = config("providerlinks.goldenF.api_url");
    //     $secrete_key = config("providerlinks.goldenF.secrete_key");
    //     $provider_id = config("providerlinks.goldenF.provider_id");
    //     $client_details = TransferWalletHelper::getClientDetails('token','n58ec5e159f769ae0b7b3a0774fdbf80');
    //     $player_id = "TG_".$client_details->player_id;
    //     $gg = 'gps_knifethrow';
    //     $nickname = $client_details->username;
    //     $http = new Client();
    //     $gameluanch_url = $api_url."/Launch?secret_key=".$secrete_key."&operator_token=".$operator_token."&game_code=".$gg."&player_name=".$player_id."&nickname=".$nickname."&language=".$client_details->language;

    //     $response = $http->post($gameluanch_url);
    //     $get_url = json_decode($response->getBody()->getContents());

    //     // return $get_url->data->game_url;
    //     return json_encode($get_url);

    //     return 1;
    // }

    // client deduct 
    // deposit golden f
    public function TransferIn(Request $request)
    {

        #1 DEBIT OPERATION
        $data = $request->all();
        $game_details = TransferWalletHelper::getInfoPlayerGameRound($request->token);
        if ($game_details == false) {
            $msg = array("status" => "error", "message" => "Game Not Found");
            TransferWalletHelper::saveLog('TransferWallet TransferOut FAILED', $this->provider_db_id, json_encode($request->all()), $msg);
            return response($msg, 200)->header('Content-Type', 'application/json');
        }

        $client_details = TransferWalletHelper::getClientDetails('token', $request->token);
        if ($client_details == 'false') {
            $msg = array("status" => "error", "message" => "Invalid Token or Token not found");
            TransferWalletHelper::saveLog('TransferWallet TransferOut FAILED', $this->provider_db_id, json_encode($request->all()), $msg);
            return response($msg, 200)->header('Content-Type', 'application/json');
        }


        # TransferWallet  (DENY DEPOSIT FOR ALREADY PLAYING PLAYER)
        # Check Multiple user Session
        $session_count = SessionWalletHelper::isMultipleSession($client_details->player_id, $request->token);
        if ($session_count) {
            $response = array(
                "status" => "error",
                "message" => "Multiple Session Detected!"
            );
            return response($response, 200)->header('Content-Type', 'application/json');
        }

        $client_response = TransferWalletHelper::playerDetailsCall($client_details); 
        $balance = round($client_response->playerdetailsresponse->balance,2);

        if(!is_numeric($request->amount)){
            $msg = array(
                "status" => "error",
                "message" => "Undefined Amount!"
            );
           TransferWalletHelper::saveLog('TransferIn Undefined Amount', $this->provider_db_id,json_encode($request->all()), $msg);
            return response($msg,200)->header('Content-Type', 'application/json');
        }

        if($balance < $request->amount){
            $msg = array(
                "status" => "error",
                "message" => "Not Enough Balance",
            );
           TransferWalletHelper::saveLog('TransferIn Low Balance', $this->provider_db_id,json_encode($request->all()), $msg);
            return response($msg,200)->header('Content-Type', 'application/json');
        }

        // if($client_details->default_currency != 'CNY'){
        //     $msg = array(
        //         "status" => "error",
        //         "message" => "Currency Not Supported",
        //     );
        //    TransferWalletHelper::saveLog('TransferIn Currency Not Supported', $this->provider_db_id,json_encode($request->all()), $msg);
        //     return response($msg,200)->header('Content-Type', 'application/json');
        // }

        // $json_data = array(
        //     "transid" => "GFTID".Carbon::now()->timestamp,
        //     "amount" => $request->amount,
        //     "roundid" => 0,
        // );

        $json_data = array(
            "transid" => $request->token,
            "amount" => $request->amount,
            "roundid" => 0,
        );

        $token_id = $client_details->token_id;
        $bet_amount = $request->amount;
        $pay_amount = 0;
        $win_or_lost = 0;
        $method = 1; 
        $payout_reason = 'Transfer IN Debit';
        $income = $bet_amount - $pay_amount;
        $round_id = $json_data['roundid'];
        $provider_trans_id = $json_data['transid'];
        $game_transaction_type = 1;

       TransferWalletHelper::saveLog('TransferIn', $this->provider_db_id,json_encode($request->all()), 'Golden IF HIT');
        $game = TransferWalletHelper::getGameTransaction($request->token,$json_data["roundid"]);
        if(!$game){
            // $gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_details->game_code, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
            // $game_transextension = TransferWalletHelper::createGameTransExtV2($gamerecord,$provider_trans_id, $round_id, $bet_amount, $game_transaction_type);
            $gamerecord = TransferWalletHelper::createGameTransaction('debit', $json_data, $game_details, $client_details); 
        }
        else{
            $gameupdate = TransferWalletHelper::updateGameTransaction($game,$json_data,"debit");
            $gamerecord = $game->game_trans_id;
        }

        

        $game_transextension = TransferWalletHelper::createGameTransExtV2($gamerecord,$provider_trans_id, $round_id, $bet_amount, $game_transaction_type);

        try {
          TransferWalletHelper::saveLog('TransferIn fundTransfer', $this->provider_db_id,json_encode($request->all()), 'Client Request');
           // $client_response = ClientRequestHelper::fundTransfer($client_details,$bet_amount,$game_details->game_code,$game_details->game_name,$game_transextension,$gamerecord,"debit");
           $client_response = ClientRequestHelper::fundTransfer($client_details,$request->amount,$game_details->game_code,$game_details->game_name,$game_transextension,$gamerecord,"debit");
          TransferWalletHelper::saveLog('TransferIn fundTransfer', $this->provider_db_id,json_encode($request->all()), 'Client Responsed');
           
        } catch (\Exception $e) {
            $response = ["status" => "Server Timeout", "statusCode" =>  1, 'msg' => $e->getMessage()];
            if(isset($gamerecord)){
                TransferWalletHelper::updateGameTransactionStatus($gamerecord, 2, 99);
                TransferWalletHelper::updatecreateGameTransExt($game_transextension, 'FAILED', json_encode($response), 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
            }
           TransferWalletHelper::saveLog('TransferIn', $this->provider_db_id,json_encode($request->all()), $response);
            return $response;
        }

        if(isset($client_response->fundtransferresponse->status->code) && $client_response->fundtransferresponse->status->code == "200"){
            try {
                $http = new Client();
                $response = $http->post($this->api_url."/TransferIn",[
                   'form_params' => [
                    'secret_key' => $this->secret_key,
                    'operator_token' => $this->operator_token,
                    'player_name' => "TG_".$client_details->player_id,
                    'amount' => $request->amount,
                    'wallet_code' => $this->wallet_code
                    ]
                ]);
                $golden_response = json_decode((string) $response->getBody(), true);
                TransferWalletHelper::saveLog('GoldenF TransferIn Success', $this->provider_db_id,json_encode($request->all()), $golden_response);

                # TransferWallet
                $token = SessionWalletHelper::checkIfExistWalletSession($request->token);
                if ($token == false) { // This token doesnt exist in wallet_session
                    SessionWalletHelper::createWalletSession($request->token, $request->all());
                }

            } catch (\Exception $e) {
                $response = ["status" => "error", 'message' => $e->getMessage()];
                if(isset($gamerecord)){
                    TransferWalletHelper::updateGameTransactionStatus($gamerecord, 2, 99);
                    TransferWalletHelper::updatecreateGameTransExt($game_transextension, 'FAILED', json_encode($response), 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
                }
               TransferWalletHelper::saveLog('GoldenF TransferIn Failed', $this->provider_db_id,json_encode($request->all()), $response);
                return $response;
            }

            SessionWalletHelper::updateSessionTime($request->token);
            $msg = array(
                "status" => "ok",
                "message" => "Transaction success",
                "balance" => round($client_response->fundtransferresponse->balance,2)
            );

            // $entry_id = 1; // Debit/Bet
            // ProviderHelper::updateGameTransaction($gamerecord, $pay_amount, $income, $win_or_lost, $entry_id);
            TransferWalletHelper::updatecreateGameTransExt($game_transextension, $data, $msg, $client_response->requestoclient, $client_response, $response, 'NO DATA');
            
            return response($msg,200)
                ->header('Content-Type', 'application/json');
        }
        elseif(isset($client_response->fundtransferresponse->status->code) && $client_response->fundtransferresponse->status->code == "402"){
            $msg = array(
                "status" =>8,
                "message" => array(
                    "text"=>"Insufficient funds",
                )
            ); 
            return response($msg,200)
            ->header('Content-Type', 'application/json');
        }

    }

    public function TransferOut(Request $request)
    {
       TransferWalletHelper::saveLog('GoldenF TransferOut Success', $this->provider_db_id,json_encode($request->all()), 'Closed triggered');
        $data = $request->all();
        $game_details = TransferWalletHelper::getInfoPlayerGameRound($request->token);
        if ($game_details == false) {
            $msg = array("status" => "error", "message" => "Game Not Found");
            TransferWalletHelper::saveLog('TransferWallet TransferOut FAILED', $this->provider_db_id, json_encode($request->all()), $msg);
            return response($msg, 200)->header('Content-Type', 'application/json');
        }

        $json_data = array(
            "transid" => "GFTID".Carbon::now()->timestamp,
            // "amount" => $request->amount,
            "roundid" => 0,
        );

        $client_details = TransferWalletHelper::getClientDetails('token', $request->token);
        $client_details = TransferWalletHelper::getClientDetails('token', $request->token);
        if ($client_details == 'false') {
            $msg = array("status" => "error", "message" => "Invalid Token or Token not found");
            TransferWalletHelper::saveLog('TransferWallet TransferOut FAILED', $this->provider_db_id, json_encode($request->all()), $msg);
            return response($msg, 200)->header('Content-Type', 'application/json');
        }

        try {
            $http = new Client();
            $response = $http->post($this->api_url."/GetPlayerBalance",[
               'form_params' => [
                'secret_key' => $this->secret_key,
                'operator_token' => $this->operator_token,
                'player_name' => "TG_".$client_details->player_id,
                'wallet_code' => $this->wallet_code
                ]
            ]);
            $golden_response_balance = json_decode((string) $response->getBody(), true);
           TransferWalletHelper::saveLog('GoldenF TransferOut GetPlayerBalance Success', $this->provider_db_id,json_encode($request->all()), $golden_response_balance);
            if(isset($golden_response_balance['data']['action_result']) && $golden_response_balance['data']['action_result'] == 'Success'){
                $TransferOut_amount = $golden_response_balance['data']['balance'];
            }else{
                $response = ["status" => "error", 'message' => 'cant connect'];
               TransferWalletHelper::saveLog('GoldenF TransferOut FAILED', $this->provider_db_id,json_encode($request->all()), $response);
                return $response;
            }
        } catch (\Exception $e) {
            $response = ["status" => "error", 'message' => $e->getMessage()];
           TransferWalletHelper::saveLog('GoldenF TransferOut Failed', $this->provider_db_id,json_encode($request->all()), $response);
            return $response;
        }


        if($request->has("token")&&$request->has("player_id")){
              TransferWalletHelper::saveLog('GoldenF TransferOut Processing withrawing', $this->provider_db_id,json_encode($request->all()), $response);
                $client_details = TransferWalletHelper::getClientDetails('token', $request->token);
          
                $game_details = TransferWalletHelper::getInfoPlayerGameRound($request->token);
                $json_data = array(
                    "transid" => Carbon::now()->timestamp,
                    "amount" => $TransferOut_amount,
                    "roundid" => 0,
                    "win"=>1,
                    "payout_reason" => "TransferOut from round",
                );
             
                $game = TransferWalletHelper::getGameTransaction($request->token,$json_data["roundid"]);
                if($game){
                    $gamerecord = $game->game_trans_id;
                }else{
                    SessionWalletHelper::deleteSession($request->token);
                    $response = ["status" => "error", 'message' => 'No Transaction Recorded'];
                    TransferWalletHelper::saveLog('GoldenF TransferOut Failed', $this->provider_db_id,json_encode($request->all()), $response);
                    return $response;
                }

               $token_id = $client_details->token_id;
                $bet_amount = $game->bet_amount;
                $pay_amount = $TransferOut_amount;
                $win_or_lost = 1;
                $method = 2; 
                $payout_reason = 'TransferOut Credit';
                $income = $bet_amount - $pay_amount;
                $round_id = $json_data['roundid'];
                $provider_trans_id = $json_data['transid'];
                $game_transaction_type = 2;

               TransferWalletHelper::saveLog('GoldenF TransferOut Processing withrawing 2', $this->provider_db_id,json_encode($request->all()), 'GG');
                try {
                    $http = new Client();
                    $response = $http->post($this->api_url."/TransferOut",[
                       'form_params' => [
                            'secret_key' => $this->secret_key,
                            'operator_token' => $this->operator_token,
                            'player_name' => "TG_".$client_details->player_id,
                            'amount' => $TransferOut_amount,
                            'wallet_code' => $this->wallet_code
                        ]
                    ]);
                    $golden_response_balance = json_decode((string) $response->getBody(), true);
                   TransferWalletHelper::saveLog('GoldenF TransferOut Success Withdraw', $this->provider_db_id,json_encode($request->all()), $golden_response_balance);
                    if(isset($golden_response_balance['data']['action_result']) && $golden_response_balance['data']['action_result'] == 'Success'){

                        try {
                            $game_transextension = TransferWalletHelper::createGameTransExtV2($gamerecord,$provider_trans_id, $round_id, $bet_amount, $game_transaction_type);
                            $client_response = ClientRequestHelper::fundTransfer($client_details,$TransferOut_amount,$game_details->game_code,$game_details->game_name,$game_transextension,$gamerecord,"credit");
                           TransferWalletHelper::saveLog('GoldenF TransferOut Client Request', $this->provider_db_id,json_encode($request->all()), 'Request to client');
                        } catch (\Exception $e) {
                            $response = ["status" => "error", 'message' => $e->getMessage()];
                           TransferWalletHelper::saveLog('GoldenF TransferOut client_response failed', $this->provider_db_id,json_encode($request->all()), $response);
                            return $response;
                        }

                        if(isset($client_response->fundtransferresponse->status->code) && $client_response->fundtransferresponse->status->code == "200"){
                            $msg = array(
                                "status" => "ok",
                                "message" => "Transaction success",
                                "balance"   =>  round($client_response->fundtransferresponse->balance,2)
                            );
                            $gameupdate = TransferWalletHelper::updateGameTransaction($game,$json_data,"credit");
                            TransferWalletHelper::updatecreateGameTransExt($game_transextension, $data, $msg, $client_response->requestoclient, $client_response, $msg, 'NO DATA');

                           SessionWalletHelper::deleteSession($request->token);
                           TransferWalletHelper::saveLog('GoldenF TransferOut Success Responded', $this->provider_db_id,json_encode($request->all()), 'SUCCESS GOLDENF');
                            return response($msg,200)
                                ->header('Content-Type', 'application/json');
                        }else{
                            $msg = array(
                                "status" => "ok",
                                "message" => "Transaction Failed Unknown Client Response",
                            );
                           TransferWalletHelper::saveLog('GoldenF TransferOut Success Responded but failed', $this->provider_db_id,json_encode($request->all()), 'FAILED GOLDENF');
                            return response($msg,200)
                                ->header('Content-Type', 'application/json');
                        }
                    }else{
                        $response = ["status" => "error", 'message' => 'cant connect'];
                        TransferWalletHelper::saveLog('GoldenF TransferOut Failed Withdraw', $this->provider_db_id,json_encode($request->all()), $response);
                        return $response;
                    }
                } catch (\Exception $e) {
                    $response = ["status" => "error", 'message' => $e->getMessage()];
                   TransferWalletHelper::saveLog('GoldenF TransferOut Failed', $this->provider_db_id,json_encode($request->all()), $response);
                    return $response;
                }
                
        } // END IF
    }


    public function BetRecordGet(Request $request)
    {
       TransferWalletHelper::saveLog("GoldenF BetRecordGet req", $this->provider_id, json_encode($request->all()), "");
    }

    public function BetRecordPlayerGet(Request $request)
    {
       TransferWalletHelper::saveLog("GoldenF BetRecordPlayerGet req", $this->provider_id, json_encode($request->all()), "");
    }

    public function TransactionRecordGet(Request $request)
    {
       TransferWalletHelper::saveLog("GoldenF TransactionRecordGet req", $this->provider_id, json_encode($request->all()), "");
    }

    public function TransactionRecordPlayerGet(Request $request)
    {
       TransferWalletHelper::saveLog("GoldenF TransactionRecordPlayerGet req", $this->provider_id, json_encode($request->all()), "");
    }

    public function BetRecordDetail(Request $request)
    {
       TransferWalletHelper::saveLog("GoldenF BetRecordDetail req", $this->provider_id, json_encode($request->all()), "");
    }
}
