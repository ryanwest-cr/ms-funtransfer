<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use App\Helpers\Helper; # Deprecated Centralized to KAHelper (Single Load)
use App\Helpers\ProviderHelper;
use App\Helpers\KAHelper;
use App\Helpers\TransferWalletHelper;
use App\Helpers\SessionWalletHelper;
use App\Helpers\ClientRequestHelper;
use GuzzleHttp\Exception\GuzzleException;
use App\Helpers\TidyHelper;
use Carbon\Carbon;
use GuzzleHttp\Client;
use DB;

class FuntaTranferWalletController extends Controller
{

    public $funta_api, $client_id = '';
    // public $gamelaunch = 'https://gamesstage.kaga88.com/';
    // public $funta_api = 'https://rmpstage.kaga88.com/kaga/';
    // public $access_key = 'A95383137CE37E4E19EAD36DF59D589A';
    // public $secret_key = '40C6AB9E806C4940E4C9D2B9E3A0AA25';
    public $provider_db_id = 23; // Nothing todo with the provider


    public function __construct()
    {

    	$this->funta_api = config('providerlinks.tidygaming.API_URL');
    	$this->client_id = config('providerlinks.tidygaming.client_id');
       
    }

    /************************************************************************************************************************/
    # Transfer Wallet Setup
    public function getPlayerBalance(Request $request)
    {
        KAHelper::saveLog('GetPlayerBalance', $this->provider_db_id, json_encode($request->all()), 'HIT');
        if (!$request->has("token")) {
            $msg = array("status" => "error", "message" => "Token Invalid");
            KAHelper::saveLog('GetPlayerBalance', $this->provider_db_id, json_encode($request->all()), $msg);
            return response($msg, 200)->header('Content-Type', 'application/json');
        }

        $client_details = KAHelper::getClientDetails('token', $request->token);
        if ($client_details == null || $client_details == 'false') {
            $msg = array("status" => "error", "message" => "Token Invalid");
            KAHelper::saveLog('GetPlayerBalance', $this->provider_db_id, json_encode($request->all()), $msg);
            return response($msg, 200)->header('Content-Type', 'application/json');
        }

        try {
            $client_response = KAHelper::playerDetailsCall($client_details);
            $balance = round($client_response->playerdetailsresponse->balance, 2);
            $msg = array(
                "status" => "ok",
                "message" => "Balance Request Success",
                "balance" => $balance
            );
            KAHelper::saveLog('GetPlayerBalance', $this->provider_db_id, json_encode($request->all()), $msg);
            return response($msg, 200)->header('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $msg = array(
                "status" => "error",
                "message" => $e->getMessage()
            );
            KAHelper::saveLog('GetPlayerBalance', $this->provider_db_id, json_encode($request->all()), $msg);
            return response($msg, 200)->header('Content-Type', 'application/json');
        }
    }

    // SET UP DONE
    public function getPlayerWalletBalance(Request $request)
    {
        $client_details = KAHelper::getClientDetails('token', $request->token);
        if ($client_details == 'false') {
            $msg = array("status" => "error", "message" => "Token Invalid");
            KAHelper::saveLog('GetPlayerBalance', $this->provider_db_id, json_encode($request->all()), $msg);
            return response($msg, 200)->header('Content-Type', 'application/json');
        }

        $game_details = TransferWalletHelper::getInfoPlayerGameRound($request->token);
        if ($game_details == false) {
            $msg = array("status" => "error", "message" => "Game Not Found");
            TransferWalletHelper::saveLog('TransferWallet TransferOut FAILED', $this->provider_db_id, json_encode($request->all()), $msg);
            return response($msg, 200)->header('Content-Type', 'application/json');
        }
        
        // $client = new Client(['headers' => ['Content-Type' => 'application/json']]);
        // $body = [
        //     "username" => $client_details->player_id,
        //     "partnerName" => $this->tw_partnerName,
        //     "gameName" => $game_details->game_name,
        //     "currency" => $client_details->default_currency,
        //     "accessKey" => $this->tw_access_key,
        //     'online' => true
        // ];
        // $guzzle_response = $client->post(
        //     $this->funta_api . 'wallet/balance?hash=' . $this->generateHash($body, 2),
        //     ['body' => json_encode(
        //         $body
        //     )]
        // );
        // $wallet_balance = json_decode($guzzle_response->getBody()->getContents());

        $url = $this->funta_api . '/api/user/outside/balance';
        $requesttosend = [
            'client_id' => $this->client_id,
            'username'  => $client_details->username
        ];
        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . TidyHelper::generateToken($requesttosend)
            ]
        ]);
        $guzzle_response = $client->get($url,['body' => json_encode($requesttosend)]);
        $wallet_balance = json_decode($guzzle_response->getBody()->getContents());
        
        if(isset($wallet_balance->user)){
            $TransferOut_amount = $wallet_balance->user->balance;
            $msg = array(
                "status" => "success",
                "balance" => $TransferOut_amount,
                "message" => 'Balance Acquired',
            );
        } else {
            $msg = array(
                "status" => "error",
                "balance" => 0,
                "message" => 'Something went wrong',
            );
        }

        return response($msg, 200)->header('Content-Type', 'application/json');
    }

    public function makeDeposit(Request $request)
    {


        if (!$request->has("token") || !$request->has("player_id") || !$request->has("amount") || !$request->has("callback_transfer_out") || !$request->has("callback_transfer_in")) {
            $msg = array("status" => "error", "message" => "Missing Required Fields!");
            KAHelper::saveLog('Funta Missing Fields', $this->provider_db_id, json_encode($request->all()), $msg);
            return response($msg, 200)->header('Content-Type', 'application/json');
        }

        #1 DEBIT OPERATION
        $data = $request->all();
        $game_details = TransferWalletHelper::getInfoPlayerGameRound($request->token);

        if ($game_details == false) {
            $msg = array("status" => "error", "message" => "Game Not Found");
            TransferWalletHelper::saveLog('TransferWallet TransferOut FAILED', $this->provider_db_id, json_encode($request->all()), $msg);
            return response($msg, 200)->header('Content-Type', 'application/json');
        }

        $client_details = KAHelper::getClientDetails('token', $request->token);
        if ($client_details == 'false') {
            $msg = array("status" => "error", "message" => "Invalid Token or Token not found");
            TransferWalletHelper::saveLog('TransferWallet TransferOut FAILED', $this->provider_db_id, json_encode($request->all()), $msg);
            return response($msg, 200)->header('Content-Type', 'application/json');
        }

        $client_response = KAHelper::playerDetailsCall($client_details);

        $balance = round($client_response->playerdetailsresponse->balance, 2);


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

        if (!is_numeric($request->amount)) {
            $msg = array(
                "status" => "error",
                "message" => "Undefined Amount!"
            );
            KAHelper::saveLog('TransferIn Undefined Amount', $this->provider_db_id, json_encode($request->all()), $msg);
            return response($msg, 200)->header('Content-Type', 'application/json');
        }

         

        if ($balance < $request->amount) {
            $msg = array(
                    "status" => "error",
                    "message" => "Not Enough Balance",
                );
            KAHelper::saveLog('TransferIn Low Balance', $this->provider_db_id, json_encode($request->all()), $msg);
            return response($msg, 200)->header('Content-Type', 'application/json');
        }

        $json_data = array(
            "transid" => "FGID" . Carbon::now()->timestamp,
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


        $game = TransferWalletHelper::getGameTransaction($request->token, $json_data["roundid"]);
       
        if (!$game) {
            $gamerecord = TransferWalletHelper::createGameTransaction('debit', $json_data, $game_details, $client_details);
        } else {
            $gameupdate = TransferWalletHelper::updateGameTransaction($game, $json_data, "debit");
            $gamerecord = $game->game_trans_id;
        }

        $token = SessionWalletHelper::checkIfExistWalletSession($request->token);
        if ($token == false) {
            SessionWalletHelper::createWalletSession($request->token, $request->all());
        } else {
            SessionWalletHelper::updateSessionTime($request->token);
        }

        $game_transextension = TransferWalletHelper::createGameTransExtV2($gamerecord, $provider_trans_id, $round_id, $bet_amount, $game_transaction_type);
        try {
            TransferWalletHelper::saveLog('TransferIn fundTransfer', $this->provider_db_id, json_encode($request->all()), 'Client Request');
            $client_response = ClientRequestHelper::fundTransfer($client_details, $request->amount, $game_details->game_code, $game_details->game_name, $game_transextension, $gamerecord, "debit");
            TransferWalletHelper::saveLog('TransferIn fundTransfer', $this->provider_db_id, json_encode($request->all()), 'Client Responsed');
        } catch (\Exception $e) {
            $response = ["status" => "Server Timeout", "statusCode" =>  1, 'msg' => $e->getMessage()];
            if (isset($gamerecord)) {
                TransferWalletHelper::updateGameTransactionStatus($gamerecord, 2, 99);
                TransferWalletHelper::updatecreateGameTransExt($game_transextension, 'FAILED', json_encode($response), 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
            }
            TransferWalletHelper::saveLog('TransferIn', $this->provider_db_id, json_encode($request->all()), $response);
            return $response;
        }


        if (isset($client_response->fundtransferresponse->status->code) && $client_response->fundtransferresponse->status->code == "200") {
            try {


            	$url = $this->funta_api . '/api/cash/outside/deposit';
		 	    $requesttosend = [
	                'client_id' => $this->client_id,
	                'username'	=> $client_details->username,
	                'amount'  => ProviderHelper::amountToFloat($bet_amount),
	                'currency'	=> TidyHelper::currencyCode($client_details->default_currency)
	            ];
	            $client = new Client([
	                'headers' => [ 
	                    'Content-Type' => 'application/json',
	                    'Authorization' => 'Bearer '.TidyHelper::generateToken($requesttosend)
	                ]
	            ]);
	            $response = $client->post($url,['body' => json_encode($requesttosend)]);
	            $make_deposit_response = json_decode($response->getBody()->getContents());

                // $client = new Client(['headers' => ['Content-Type' => 'application/json']]);
                // $body = [
                //     "username" => $client_details->player_id,
                //     "partnerName" => $this->tw_partnerName,
                //     "gameName" => 'Fastbreak',
                //     "currency" => $client_details->default_currency,
                //     "accessKey" => $this->tw_access_key,
                //     'depositAmount' => $this->formatBalance($bet_amount)
                // ];
                // $response = $client->post(
                //     $this->funta_api . 'wallet/deposit?hash=' . $this->generateHash($body, 2),
                //     ['body' => json_encode(
                //         $body
                //     )]
                // );
                // $make_deposit_response = json_decode($response->getBody()->getContents());
                
                # If the deposit to the provider Wallet Failed
                // if(isset($make_deposit_response->error_code) && isset($make_deposit_response->error_msg)){

                //     $response = ["status" => "error", 'message' => 'Somthing Went Wrong'];
                //     if (isset($gamerecord)) {
                //         TransferWalletHelper::updateGameTransactionStatus($gamerecord, 2, 99);

                //         TransferWalletHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $response, 'FAILED', 'FAILED', 'FAILED', 'FAILED');
                //     }
                //     TransferWalletHelper::saveLog('TransferIn Failed', $this->provider_db_id, json_encode($request->all()), $response);
                //     return $response;
                // }


                # TransferWallet
                $token = SessionWalletHelper::checkIfExistWalletSession($request->token);
                if ($token == false) { // This token doesnt exist in wallet_session
                    SessionWalletHelper::createWalletSession($request->token, $request->all());
                }

                TransferWalletHelper::saveLog('TransferIn Success', $this->provider_db_id, json_encode($request->all()), $make_deposit_response);
            } catch (\Exception $e) {
                $response = ["status" => "error", 'message' => $e->getMessage()];
                if (isset($gamerecord)) {
                    TransferWalletHelper::updateGameTransactionStatus($gamerecord, 2, 99);
                    TransferWalletHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $response, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
                }
                TransferWalletHelper::saveLog('TransferIn Failed', $this->provider_db_id, json_encode($request->all()), $response);
                return $response;
            }

            SessionWalletHelper::updateSessionTime($request->token);
            $msg = array(
                "status" => "ok",
                "message" => "Transaction success",
                "balance" => round($client_response->fundtransferresponse->balance, 2)
            );

            TransferWalletHelper::updatecreateGameTransExt($game_transextension, $data, $msg, $client_response->requestoclient, $client_response, $response, 'NO DATA');
            return response($msg, 200)->header('Content-Type', 'application/json');

        } elseif (isset($client_response->fundtransferresponse->status->code) && $client_response->fundtransferresponse->status->code == "402") {
            $msg = array(
                "status" => 8,
                "message" => array(
                    "text" => "Insufficient funds",
                )
            );
            return response($msg, 200)->header('Content-Type', 'application/json');
        }

    
    }


    public function makeWithdraw(Request $request)
    {

        if (!$request->has("token") || !$request->has("player_id") || !$request->has("callback_transfer_out") || !$request->has("callback_transfer_in")) {
            $msg = array("status" => "error", "message" => "Missing Required Fields!");
            KAHelper::saveLog('Funta Missing Fields', $this->provider_db_id, json_encode($request->all()), $msg);
            return response($msg, 200)->header('Content-Type', 'application/json');
        }

        TransferWalletHelper::saveLog('TransferWallet TransferOut Success', $this->provider_db_id, json_encode($request->all()), 'Closed triggered');
        $data = $request->all();
        $game_details = TransferWalletHelper::getInfoPlayerGameRound($request->token);

        if($game_details == false){
            $msg = array("status" => "error", "message" => "Game Not Found");
            TransferWalletHelper::saveLog('TransferWallet TransferOut FAILED', $this->provider_db_id, json_encode($request->all()), $msg);
            return response($msg, 200)->header('Content-Type', 'application/json');
        }
        $json_data = array(
            "transid" => "KATID" . Carbon::now()->timestamp,
            "roundid" => 0,
        );

        $client_details = KAHelper::getClientDetails('token', $request->token);
        // dd($client_details);
        if ($client_details == 'false') {
            $msg = array("status" => "error", "message" => "Invalid Token or Token not found");
            TransferWalletHelper::saveLog('TransferWallet TransferOut FAILED', $this->provider_db_id, json_encode($request->all()), $msg);
            return response($msg, 200)->header('Content-Type', 'application/json');
        }


        //GET BALANCE
        try {
    		$url = $this->funta_api . '/api/user/outside/balance';
	 	    $requesttosend = [
                'client_id' => $this->client_id,
                'username'	=> $client_details->username
            ];
            $client = new Client([
                'headers' => [ 
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.TidyHelper::generateToken($requesttosend)
                ]
            ]);
            $guzzle_response = $client->get($url,['body' => json_encode($requesttosend)]);
            $wallet_balance = json_decode($guzzle_response->getBody()->getContents());
           
            // $client = new Client(['headers' => ['Content-Type' => 'application/json']]);
            // $body = [
            //     "username" => $client_details->player_id,
            //     "partnerName" => $this->tw_partnerName,
            //     "gameName" => $game_details->game_name,
            //     "currency" => $client_details->default_currency,
            //     "accessKey" => $this->tw_access_key,
            //     'online' => true
            // ];
            // $guzzle_response = $client->post(
            //     $this->funta_api . 'wallet/balance?hash=' . $this->generateHash($body, 2),
            //     ['body' => json_encode(
            //         $body
            //     )]
            // );
            // $wallet_balance = json_decode($guzzle_response->getBody()->getContents());
            if(isset($wallet_balance->user)){
                $TransferOut_amount = $wallet_balance->user->balance; // Amount to withdraw from the player wallet need tobe formatted
            }else{
                $msg = array("status" => "error", "message" => "Wallet Balance Failed");
                TransferWalletHelper::saveLog('TransferWallet TransferOut FAILED', $this->provider_db_id, json_encode($request->all()), $wallet_balance);
                //return response($msg, 200)->header('Content-Type', 'application/json');
            }

        } catch (\Exception $e) {
            $response = ["status" => "error", 'message' => $e->getMessage()];
            TransferWalletHelper::saveLog('TransferWallet TransferOut Failed', $this->provider_db_id, json_encode($request->all()), $response);
           // return $response;
        }

        $TransferOut_amount = 1000;
        if ($request->has("token") && $request->has("player_id")) {

            TransferWalletHelper::saveLog('TransferWallet TransferOut Processing withrawing', $this->provider_db_id, json_encode($request->all()), $data);

            $json_data = array(
                "transid" => Carbon::now()->timestamp,
                "amount" => $TransferOut_amount,
                "roundid" => 0,
                "win" => 1,
                "payout_reason" => "TransferOut from round",
            );
            $game = TransferWalletHelper::getGameTransaction($request->token, $json_data["roundid"]);
           	// dd($game);
            if ($game) {
                $gamerecord = $game->game_trans_id;
            } else {
                SessionWalletHelper::deleteSession($request->token);
                $response = ["status" => "error", 'message' => 'No Transaction Recorded'];
                TransferWalletHelper::saveLog('TransferWallet TransferOut Failed', $this->provider_db_id, json_encode($request->all()), $response);
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


            TransferWalletHelper::saveLog('TransferWallet TransferOut Processing withrawing 2', $this->provider_db_id, json_encode($request->all()), 'GG');
            try {

            	$url = $this->funta_api . '/api/cash/outside/withdraw';
		 	    $requesttosend = [
	                'client_id' => $this->client_id,
	                'username'	=> $client_details->username,
	                'amount'  => ProviderHelper::amountToFloat($TransferOut_amount),
	                'currency'	=> TidyHelper::currencyCode($client_details->default_currency)
	            ];
	            $client = new Client([
	                'headers' => [ 
	                    'Content-Type' => 'application/json',
	                    'Authorization' => 'Bearer '.TidyHelper::generateToken($requesttosend)
	                ]
	            ]);
	            $guzzle_response = $client->post($url,['body' => json_encode($requesttosend)]);
	            $wallet_withdraw = json_decode($guzzle_response->getBody()->getContents());

                // $client = new Client(['headers' => ['Content-Type' => 'application/json']]);
                // $body = [
                //     "username" => $client_details->player_id,
                //     "partnerName" => $this->tw_partnerName,
                //     "gameName" => $game_details->game_name,
                //     "currency" => $client_details->default_currency,
                //     "accessKey" => $this->tw_access_key,
                //     'withdrawalAmount' => $TransferOut_amount,
                // ];
                // // $balance_form = [1 => $this->formatBalance($TransferOut_amount), 2 => $this->formatAmounts($TransferOut_amount), 3=> $TransferOut_amount];

                // $guzzle_response = $client->post(
                //     $this->funta_api . 'wallet/withdraw?hash=' . $this->generateHash($body, 2),
                //     ['body' => json_encode(
                //         $body
                //     )]
                // );
                // $wallet_withdraw = json_decode($guzzle_response->getBody()->getContents());

                TransferWalletHelper::saveLog('TransferWallet TransferOut Success Withdraw', $this->provider_db_id, json_encode($request->all()), $wallet_withdraw);
                if ((isset($wallet_withdraw->error_code) && isset($wallet_withdraw->error_msg)) ) {

                    try {
                        $game_transextension = TransferWalletHelper::createGameTransExtV2($gamerecord, $provider_trans_id, $round_id, $bet_amount, $game_transaction_type);
                        $client_response = ClientRequestHelper::fundTransfer($client_details, $pay_amount, $game_details->game_code, $game_details->game_name, $game_transextension, $gamerecord, "credit");
                        TransferWalletHelper::saveLog('TransferWallet TransferOut Client Request', $this->provider_db_id, json_encode($request->all()), 'Request to client');
                    } catch (\Exception $e) {
                        $response = ["status" => "error", 'message' => $e->getMessage()];
                        TransferWalletHelper::saveLog('TransferWallet TransferOut client_response failed', $this->provider_db_id, json_encode($request->all()), $response);
                        return $response;
                    }

                    if (isset($client_response->fundtransferresponse->status->code) && $client_response->fundtransferresponse->status->code == "200") {
                        $msg = array(
                            "status" => "ok",
                            "message" => "Transaction success",
                            "balance"   =>  round($client_response->fundtransferresponse->balance, 2)
                        );
                        $gameupdate = TransferWalletHelper::updateGameTransaction($game, $json_data, "credit");
                        TransferWalletHelper::updatecreateGameTransExt($game_transextension, $data, $msg, $client_response->requestoclient, $client_response, $msg, 'NO DATA');

                        SessionWalletHelper::deleteSession($request->token);
                        TransferWalletHelper::saveLog('TransferWallet TransferOut Success Responded', $this->provider_db_id, json_encode($request->all()), 'SUCCESS TransferWallet');
                        return response($msg, 200)
                            ->header('Content-Type', 'application/json');
                    } else {
                        $msg = array(
                            "status" => "ok",
                            "message" => "Transaction Failed Unknown Client Response",
                        );
                        TransferWalletHelper::saveLog('TransferWallet TransferOut Success Responded but failed', $this->provider_db_id, json_encode($request->all()), 'FAILED TransferWallet');
                        return response($msg, 200)
                            ->header('Content-Type', 'application/json');
                    }
                } else {
                    $response = ["status" => "error", 'message' => 'cant connect'];
                    TransferWalletHelper::saveLog('TransferWallet TransferOut Failed Withdraw', $this->provider_db_id, json_encode($request->all()), $response);
                    return $response;
                }
            } catch (\Exception $e) {
                $response = ["status" => "error", 'message' => $e->getMessage()];
                TransferWalletHelper::saveLog('TransferWallet TransferOut Failed', $this->provider_db_id, json_encode($request->all()), $response);
                return $response;
            }
        } // END IF
      
    }


    public function checkPlayerWallet(Request $request)
    {
        $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);


        $body = [
            "username" => '98',
            "partnerName" => $this->tw_partnerName,
            "gameName" => 'Fastbreak',
            "currency" => 'USD',
            "accessKey" => $this->tw_access_key,
            'online' => true
        ];

        // $body['hash'] = hash_hmac('sha256', json_encode($body), $this->tw_secret_key);
        // return json_encode($body);

        $guzzle_response = $client->post(
            $this->funta_api . 'wallet/balance?hash=' . $this->generateHash($body, 2),
            ['body' => json_encode(
                $body
            )]
        );
        $client_response = json_decode($guzzle_response->getBody()->getContents());

        dd($client_response);
    }


    public function checkPlayerTransactions(Request $request)
    {
        $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json'
            ]
        ]);


        $body = [
            "username" => 'RiANDRAFT',
            "partnerName" => $this->tw_partnerName,
            "gameName" => 'Fastbreak',
            "currency" => 'USD',
            "accessKey" => $this->tw_access_key,
            'walletTransactionId' => '0BA7193D0486277FCA3125BD017A2A2D'
        ];

        // $body['hash'] = hash_hmac('sha256', json_encode($body), $this->tw_secret_key);
        // return json_encode($body);

        $guzzle_response = $client->post(
            $this->funta_api . 'wallet/checkTransaction?hash=' . $this->generateHash($body, 2),
            ['body' => json_encode(
                $body
            )]
        );
        $client_response = json_decode($guzzle_response->getBody()->getContents());

        dd($client_response);
    }



    public  function findAllGameExt($provider_identifier, $type, $second_identifier='') {
        $transaction_db = DB::table('game_transaction_ext as gte');
        if ($type == 'transaction_id') {
            $transaction_db->where([
                ["gte.provider_trans_id", "=", $provider_identifier],
            ]);
        }
        if ($type == 'round_id') {
            $transaction_db->where([
                ["gte.round_id", "=", $provider_identifier],
            ]);
        }  
        if ($type == 'all') {
            $transaction_db->where([
                ["gte.round_id", "=", $second_identifier],
                ["gte.provider_trans_id", "=", $provider_identifier],
            ]);
        }  
        $result = $transaction_db->latest()->get();
        return $result ? $result : 'false';
    }


}
