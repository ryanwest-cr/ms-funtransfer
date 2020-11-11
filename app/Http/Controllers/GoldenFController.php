<?php

namespace App\Http\Controllers;

use App\Helpers\ClientRequestHelper;
use Illuminate\Http\Request;
use App\Helpers\ProviderHelper;
use App\Helpers\GoldenFHelper;
use App\Helpers\SessionWalletHelper;
use DB;
use App\Helpers\Helper;
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
    public function GetPlayerBalance(Request $request)
    {
        if(!$request->has("token")){
            $msg = array("status" =>"error","message" => "Token Invalid");
           GoldenFHelper::saveLog('GetPlayerBalance', $this->provider_db_id,json_encode($request->all()), $msg);
            return response($msg,200)->header('Content-Type', 'application/json');
        }

        $client_details = ProviderHelper::getClientDetails('token', $request->token);
        if($client_details == null || $client_details == 'false'){
            $msg = array("status" =>"error","message" => "Token Invalid");
           GoldenFHelper::saveLog('GetPlayerBalance', $this->provider_db_id,json_encode($request->all()), $msg);
            return response($msg,200)->header('Content-Type', 'application/json');
        }

        try {
            $client_response = GoldenFHelper::playerDetailsCall($client_details);  
            // $client_response = Providerhelper::playerDetailsCall($client_details->player_token);  
            $balance = round($client_response->playerdetailsresponse->balance,2);
            $msg = array(
                "status" => "ok",
                "message" => "Balance Request Success",
                "balance" => $balance
            );
           GoldenFHelper::saveLog('GetPlayerBalance', $this->provider_db_id,json_encode($request->all()), $msg);
            return response($msg,200)->header('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $msg = array(
                "status" =>"error",
                "message" => $e->getMessage()
            );
           GoldenFHelper::saveLog('GetPlayerBalance', $this->provider_db_id,json_encode($request->all()), $msg);
            return response($msg,200)->header('Content-Type', 'application/json');
        }

    }


    public function getPlayerWalletBalance(Request $request){
        $client_details = ProviderHelper::getClientDetails('token', $request->token);
        if($client_details == null || $client_details == 'false'){
            $msg = array("status" =>"error","message" => "Token Invalid");
           GoldenFHelper::saveLog('GetPlayerBalance', $this->provider_db_id,json_encode($request->all()), $msg);
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
    //     $client_details = ProviderHelper::getClientDetails('token','n58ec5e159f769ae0b7b3a0774fdbf80');
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
        $game_details = GoldenFHelper::getInfoPlayerGameRound($request->token);
        $client_details = ProviderHelper::getClientDetails('token', $request->token);
        $client_response = GoldenFHelper::playerDetailsCall($client_details); 
        $balance = round($client_response->playerdetailsresponse->balance,2);

        if(!is_numeric($request->amount)){
            $msg = array(
                "status" => "error",
                "message" => "Undefined Amount!"
            );
           GoldenFHelper::saveLog('TransferIn Undefined Amount', $this->provider_db_id,json_encode($request->all()), $msg);
            return response($msg,200)->header('Content-Type', 'application/json');
        }

        if($balance < $request->amount){
            $msg = array(
                "status" => "error",
                "message" => "Not Enough Balance",
            );
           GoldenFHelper::saveLog('TransferIn Low Balance', $this->provider_db_id,json_encode($request->all()), $msg);
            return response($msg,200)->header('Content-Type', 'application/json');
        }

        // if($client_details->default_currency != 'CNY'){
        //     $msg = array(
        //         "status" => "error",
        //         "message" => "Currency Not Supported",
        //     );
        //    GoldenFHelper::saveLog('TransferIn Currency Not Supported', $this->provider_db_id,json_encode($request->all()), $msg);
        //     return response($msg,200)->header('Content-Type', 'application/json');
        // }

        $json_data = array(
            "transid" => "GFTID".Carbon::now()->timestamp,
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

       GoldenFHelper::saveLog('TransferIn', $this->provider_db_id,json_encode($request->all()), 'Golden IF HIT');
        $game = GoldenFHelper::getGameTransaction($request->token,$json_data["roundid"]);
        if(!$game){
            // $gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_details->game_code, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
            // $game_transextension = ProviderHelper::createGameTransExtV2($gamerecord,$provider_trans_id, $round_id, $bet_amount, $game_transaction_type);
            $gamerecord = GoldenFHelper::createGameTransaction('debit', $json_data, $game_details, $client_details); 
        }
        else{
            $gameupdate = GoldenFHelper::updateGameTransaction($game,$json_data,"debit");
            $gamerecord = $game->game_trans_id;
        }

        $token = SessionWalletHelper::checkIfExistWalletSession($request->token);
        if($token == false){
            SessionWalletHelper::createWalletSession($request->token, $request->all());
        }else{
            SessionWalletHelper::updateSessionTime($request->token);
        }

        // $gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_details->game_code, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
        $game_transextension = ProviderHelper::createGameTransExtV2($gamerecord,$provider_trans_id, $round_id, $bet_amount, $game_transaction_type);

        try {
          GoldenFHelper::saveLog('TransferIn fundTransfer', $this->provider_db_id,json_encode($request->all()), 'Client Request');
           // $client_response = ClientRequestHelper::fundTransfer($client_details,$bet_amount,$game_details->game_code,$game_details->game_name,$game_transextension,$gamerecord,"debit");
           $client_response = ClientRequestHelper::fundTransfer($client_details,$request->amount,$game_details->game_code,$game_details->game_name,$game_transextension,$gamerecord,"debit");
          GoldenFHelper::saveLog('TransferIn fundTransfer', $this->provider_db_id,json_encode($request->all()), 'Client Responsed');
           
        } catch (\Exception $e) {
            $response = ["status" => "Server Timeout", "statusCode" =>  1, 'msg' => $e->getMessage()];
            if(isset($gamerecord)){
                ProviderHelper::updateGameTransactionStatus($gamerecord, 2, 99);
                ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', json_encode($response), 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
            }
           GoldenFHelper::saveLog('TransferIn', $this->provider_db_id,json_encode($request->all()), $response);
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
               GoldenFHelper::saveLog('GoldenF TransferIn Success', $this->provider_db_id,json_encode($request->all()), $golden_response);
            } catch (\Exception $e) {
                $response = ["status" => "error", 'message' => $e->getMessage()];
                if(isset($gamerecord)){
                    ProviderHelper::updateGameTransactionStatus($gamerecord, 2, 99);
                    ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', json_encode($response), 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
                }
               GoldenFHelper::saveLog('GoldenF TransferIn Failed', $this->provider_db_id,json_encode($request->all()), $response);
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
            ProviderHelper::updatecreateGameTransExt($game_transextension, $data, $msg, $client_response->requestoclient, $client_response, $response, 'NO DATA');
            
            response($msg,200)->header('Content-Type', 'application/json');
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
       GoldenFHelper::saveLog('GoldenF TransferOut Success', $this->provider_db_id,json_encode($request->all()), 'Closed triggered');
        $data = $request->all();
        $game_details = GoldenFHelper::getInfoPlayerGameRound($request->token);

        $json_data = array(
            "transid" => "GFTID".Carbon::now()->timestamp,
            // "amount" => $request->amount,
            "roundid" => 0,
        );

        $client_details = ProviderHelper::getClientDetails('token', $request->token);
        // $client_response = Providerhelper::playerDetailsCall($client_details->player_token); 
        if(!$client_details){
            $msg = array("status" =>"error","message" => "Invalid Token or Token not found"); 
           GoldenFHelper::saveLog('GoldenF TransferOut FAILED', $this->provider_db_id,json_encode($request->all()), $response);
            return response($msg,200)->header('Content-Type', 'application/json'); 
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
           GoldenFHelper::saveLog('GoldenF TransferOut GetPlayerBalance Success', $this->provider_db_id,json_encode($request->all()), $golden_response_balance);
            if(isset($golden_response_balance['data']['action_result']) && $golden_response_balance['data']['action_result'] == 'Success'){
                $TransferOut_amount = $golden_response_balance['data']['balance'];
            }else{
                $response = ["status" => "error", 'message' => 'cant connect'];
               GoldenFHelper::saveLog('GoldenF TransferOut FAILED', $this->provider_db_id,json_encode($request->all()), $response);
                return $response;
            }
        } catch (\Exception $e) {
            $response = ["status" => "error", 'message' => $e->getMessage()];
           GoldenFHelper::saveLog('GoldenF TransferOut Failed', $this->provider_db_id,json_encode($request->all()), $response);
            return $response;
        }


        if($request->has("token")&&$request->has("player_id")){
              GoldenFHelper::saveLog('GoldenF TransferOut Processing withrawing', $this->provider_db_id,json_encode($request->all()), $response);
                $client_details = ProviderHelper::getClientDetails('token', $request->token);
          
                $game_details = Helper::getInfoPlayerGameRound($request->token);
                $json_data = array(
                    "transid" => Carbon::now()->timestamp,
                    "amount" => $TransferOut_amount,
                    "roundid" => 0,
                    "win"=>1,
                    "payout_reason" => "TransferOut from round",
                );
             
                $game = GoldenFHelper::getGameTransaction($request->token,$json_data["roundid"]);
                if($game){
                    $gamerecord = $game->game_trans_id;
                }else{
                    $response = ["status" => "error", 'message' => 'No Transaction Recorded'];
                   GoldenFHelper::saveLog('GoldenF TransferOut Failed', $this->provider_db_id,json_encode($request->all()), $response);
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

               GoldenFHelper::saveLog('GoldenF TransferOut Processing withrawing 2', $this->provider_db_id,json_encode($request->all()), 'GG');
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
                   GoldenFHelper::saveLog('GoldenF TransferOut Success Withdraw', $this->provider_db_id,json_encode($request->all()), $golden_response_balance);
                    if(isset($golden_response_balance['data']['action_result']) && $golden_response_balance['data']['action_result'] == 'Success'){

                        try {
                            $game_transextension = ProviderHelper::createGameTransExtV2($gamerecord,$provider_trans_id, $round_id, $bet_amount, $game_transaction_type);
                            $client_response = ClientRequestHelper::fundTransfer($client_details,$TransferOut_amount,$game_details->game_code,$game_details->game_name,$game_transextension,$gamerecord,"credit");
                           GoldenFHelper::saveLog('GoldenF TransferOut Client Request', $this->provider_db_id,json_encode($request->all()), 'Request to client');
                        } catch (\Exception $e) {
                            $response = ["status" => "error", 'message' => $e->getMessage()];
                           GoldenFHelper::saveLog('GoldenF TransferOut client_response failed', $this->provider_db_id,json_encode($request->all()), $response);
                            return $response;
                        }

                        if(isset($client_response->fundtransferresponse->status->code) && $client_response->fundtransferresponse->status->code == "200"){
                            $msg = array(
                                "status" => "ok",
                                "message" => "Transaction success",
                                "balance"   =>  round($client_response->fundtransferresponse->balance,2)
                            );
                            $gameupdate = GoldenFHelper::updateGameTransaction($game,$json_data,"credit");
                            ProviderHelper::updatecreateGameTransExt($game_transextension, $data, $msg, $client_response->requestoclient, $client_response, $msg, 'NO DATA');

                           SessionWalletHelper::deleteSession($request->token);
                           GoldenFHelper::saveLog('GoldenF TransferOut Success Responded', $this->provider_db_id,json_encode($request->all()), 'SUCCESS GOLDENF');
                            return response($msg,200)
                                ->header('Content-Type', 'application/json');
                        }else{
                            $msg = array(
                                "status" => "ok",
                                "message" => "Transaction Failed Unknown Client Response",
                            );
                           GoldenFHelper::saveLog('GoldenF TransferOut Success Responded but failed', $this->provider_db_id,json_encode($request->all()), 'FAILED GOLDENF');
                            return response($msg,200)
                                ->header('Content-Type', 'application/json');
                        }
                    }else{
                        $response = ["status" => "error", 'message' => 'cant connect'];
                        GoldenFHelper::saveLog('GoldenF TransferOut Failed Withdraw', $this->provider_db_id,json_encode($request->all()), $response);
                        return $response;
                    }
                } catch (\Exception $e) {
                    $response = ["status" => "error", 'message' => $e->getMessage()];
                   GoldenFHelper::saveLog('GoldenF TransferOut Failed', $this->provider_db_id,json_encode($request->all()), $response);
                    return $response;
                }
                
        } // END IF
    }


    public function BetRecordGet(Request $request)
    {
       GoldenFHelper::saveLog("GoldenF BetRecordGet req", $this->provider_id, json_encode($request->all()), "");
    }

    public function BetRecordPlayerGet(Request $request)
    {
       GoldenFHelper::saveLog("GoldenF BetRecordPlayerGet req", $this->provider_id, json_encode($request->all()), "");
    }

    public function TransactionRecordGet(Request $request)
    {
       GoldenFHelper::saveLog("GoldenF TransactionRecordGet req", $this->provider_id, json_encode($request->all()), "");
    }

    public function TransactionRecordPlayerGet(Request $request)
    {
       GoldenFHelper::saveLog("GoldenF TransactionRecordPlayerGet req", $this->provider_id, json_encode($request->all()), "");
    }

    public function BetRecordDetail(Request $request)
    {
       GoldenFHelper::saveLog("GoldenF BetRecordDetail req", $this->provider_id, json_encode($request->all()), "");
    }
}
