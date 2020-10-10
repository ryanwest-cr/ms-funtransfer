<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use App\Helpers\ClientRequestHelper;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use DB;

class KAGamingController extends Controller
{

    public $gamelaunch, $ka_api, $access_key, $secret_key = '';
	// public $gamelaunch = 'https://gamesstage.kaga88.com/';
	// public $ka_api = 'https://rmpstage.kaga88.com/kaga/';
	// public $access_key = 'A95383137CE37E4E19EAD36DF59D589A';
	// public $secret_key = '40C6AB9E806C4940E4C9D2B9E3A0AA25';
    public $provider_db_id = 43; // Nothing todo with the provider


    public function __construct(){
        $this->gamelaunch = config('providerlinks.kagaming.gamelaunch');
        $this->ka_api = config('providerlinks.kagaming.ka_api');
        $this->access_key = config('providerlinks.kagaming.access_key');
        $this->secret_key = config('providerlinks.kagaming.secret_key');
    }

	public function generateHash($msg=''){
		return hash_hmac('sha256', json_encode($msg), $this->secret_key);
	}

    public function verifyHash($request_body, $hashen){
        $data = json_decode($request_body);
        if(isset($data->hash)){
           unset($data->hash); 
        }
        $body = json_encode($data);
        $hash = hash_hmac('sha256', $body, $this->secret_key);
        if($hash == $hashen){
            return true;
        }else{
            return false;
        }
    }

    public function index(){
    	$client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json'
            ]
        ]);
		$body = [
   			"partnerName" => 'TIGER',
            "accessKey" => $this->access_key,
            "language" => "en",
            "randomId" => 1,
        ];
        $guzzle_response = $client->post($this->ka_api.'gameList?hash='.$this->generateHash($body),
            ['body' => json_encode(
                    $body
            )]
        );
        $client_response = json_decode($guzzle_response->getBody()->getContents());

        $gamelist = array();
        foreach ($client_response->games as $key) {
            $game = [
                "game_name" => $key->gameName,
                "game_type" => $key->gameType,
                "game_code" => $key->gameId
            ];
            array_push($gamelist, $game);
        }

        return $gamelist;
    }

    public function formatBalance($amount){
        return round($amount*100,2);
    }

    public function formatAmounts($amount){
         return round($amount/100,2);
    }

    public function gameStart(Request $request){
        // Helper::saveLog('KAGaming gameStart - EH', $this->provider_db_id, json_encode($request->all()), $request->input("hash"));
        $request_body = $request->getContent();
        if(!$request->input("hash") != ''){
            return  $response = ["status" => "failed", "statusCode" =>  3];
        }
        if(!$this->verifyHash($request_body, $request->input("hash"))){
            return  $response = ["status" => "failed", "statusCode" =>  3];
        }
        $data = json_decode($request_body);
        $session_check = Providerhelper::getClientDetails('token',$data->token);
        if($session_check == 'false'){
            return  $response = ["status" => "failed", "statusCode" =>  100];
        }
        $client_details = Providerhelper::getClientDetails('player_id',$data->partnerPlayerId);
        if($client_details == 'false'){
            return  $response = ["status" => "failed", "statusCode" =>  4];
        }
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
        if($player_details == 'false'){
            return  $response = ["status" => "Server Timeout", "statusCode" =>  1];
        }
        $response = [
            "playerId" => $client_details->player_id,
            "sessionId" => $client_details->player_token,
            "balance" => $this->formatBalance($player_details->playerdetailsresponse->balance),
            "currency" =>  $client_details->default_currency,
            "status" => "success",
            "statusCode" =>  0
        ];
        return $response;
    }


    public function playerBalance(Request $request){
        // Helper::saveLog('KAGaming playerBalance - EH', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');
        $request_body = $request->getContent();

        if(!$request->input("hash") != ''){
            return  $response = ["status" => "failed", "statusCode" =>  3];
        }
        if(!$this->verifyHash($request_body, $request->input("hash"))){
            return  $response = ["status" => "failed", "statusCode" =>  3];
        }
        $data = json_decode($request_body);
        $session_check = Providerhelper::getClientDetails('token',$data->sessionId);
        if($session_check == 'false'){
            return  $response = ["status" => "failed", "statusCode" =>  100];
        }
        $client_details = Providerhelper::getClientDetails('player_id',$data->partnerPlayerId);
        if($client_details == 'false'){
            return  $response = ["status" => "failed", "statusCode" =>  4];
        }
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
        if($player_details == 'false'){
            return  $response = ["status" => "Server Timeout", "statusCode" =>  1];
        }
        $response = [
            "balance" => $this->formatBalance($player_details->playerdetailsresponse->balance),
            "status" => "success",
            "statusCode" =>  0
        ];
        return $response;
    }


    public function checkPlay(Request $request){
        Helper::saveLog('KAGaming checkPlay - EH', $this->provider_db_id, json_encode($request->all()), $request->input("hash"));
        // $request_body = file_get_contents("php://input");
        $request_body = $request->getContent();

        if(!$request->input("hash") != ''){
            return  $response = ["status" => "failed", "statusCode" =>  3];
        }
        if(!$this->verifyHash($request_body, $request->input("hash"))){
            return  $response = ["status" => "failed", "statusCode" =>  3];
        }

        $data = json_decode($request_body);
        $general_details = ["aggregator" => [], "provider" => [], "client" => []];
        $freeGames = $data->freeGames; 
        $provider_trans_id = $data->transactionId;
        $round_id = $provider_trans_id.'_'.$data->round;
        $game_code = $data->gameId;

        if($freeGames == true){
            $bet_amount = 0;
        }else{
            $bet_amount = $this->formatAmounts($data->betAmount);
        }

        #1DEBUGGING
        // $client_details = Providerhelper::getClientDetails('player_id',$data->partnerPlayerId);
        // $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
        // $balance = ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance);
        // if(!$freeGames) {
        //    if(($balance - $this->formatAmounts($data->betAmount)) > 0) {
        //       $balance -= $this->formatAmounts($data->betAmount);
        //       $balance += $this->formatAmounts($data->winAmount);
        //    }
        // } else { // is free games
        //     $balance += $this->formatAmounts($data->winAmount);
        // }
        // return $balance;
        #1DEBUGGING
        
        $amount = $this->formatAmounts($data->betAmount);
        $win_amount = $this->formatAmounts($data->winAmount);
        $pay_amount =  0; //abs($data['amount']);
        $method = 1;
        $income = $bet_amount - $pay_amount;
        $entry_id = 1;
        $win_or_lost = 5; // 0 lost,  5 processing
        $payout_reason = 'Game Bets and Win';
        
        $session_check = Providerhelper::getClientDetails('token',$data->sessionId);
        if($session_check == 'false'){
            return  $response = ["status" => "failed", "statusCode" =>  100];
        }
        $client_details = Providerhelper::getClientDetails('player_id',$data->partnerPlayerId);
        if($client_details == 'false'){
            return  $response = ["status" => "failed", "statusCode" =>  4];
        }
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
        if($player_details == 'false'){
            return  $response = ["status" => "Server Timeout", "statusCode" =>  1];
        }
        $game_information = Helper::findGameDetails('game_code', $this->provider_db_id, $game_code);
        if($game_information == null){ 
            return  $response = ["status" => "Game Not Found", "statusCode" =>  1];
        }
        $game_ext_check = ProviderHelper::findGameExt($round_id, 1, 'round_id');
        if($game_ext_check != 'false'){ // Duplicate transaction
            return  $response = ["status" => "Duplicate transaction", "statusCode" =>  1];
        }
        if(ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance) < $amount){
             return  $response = ["status" => "Insufficient balance", "statusCode" =>  200];
        }

        $general_details['client']['before_balance'] = ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance);
        $general_details['client']['action'] = 'play';
        $game_transaction_type = 1; // 1 Bet, 2 Win
        $game_code = $game_information->game_id;
        $token_id = $client_details->token_id;


        $check_bet_round = ProviderHelper::findGameExt($provider_trans_id, 1, 'transaction_id');
        if($check_bet_round != 'false'){
          $existing_bet_details = Providerhelper::findGameTransaction($check_bet_round->game_trans_id, 'game_transaction');
          $gamerecord = $existing_bet_details->game_trans_id;
          $game_transextension = ProviderHelper::createGameTransExtV2($existing_bet_details->game_trans_id,$provider_trans_id, $round_id, $amount, $game_transaction_type);
        }else{
           #1 DEBIT OPERATION
           $gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_code, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
           $game_transextension = ProviderHelper::createGameTransExtV2($gamerecord,$provider_trans_id, $round_id, $bet_amount, $game_transaction_type);
        }

        try {
          $client_response = ClientRequestHelper::fundTransfer($client_details,abs($bet_amount),$game_information->game_code,$game_information->game_name,$game_transextension,$gamerecord, 'debit');
          Helper::saveLog('KAGaming checkPlay CRID '.$gamerecord, $this->provider_db_id,json_encode($request->all()), $client_response);
           
        } catch (\Exception $e) {
          $response = ["status" => "Server Timeout", "statusCode" =>  1];
            if(isset($gamerecord)){
                if($check_bet_round == 'false'){
                    ProviderHelper::updateGameTransactionStatus($gamerecord, 2, 99);
                    ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $response, 'FAILED', $e->getMessage(), 'FAILED', $general_details);
                }
            }
          Helper::saveLog('KAGaming checkPlay - FATAL ERROR', $this->provider_db_id, $response, Helper::datesent());
          return $response;
        }
        if(isset($client_response->fundtransferresponse->status->code) 
             && $client_response->fundtransferresponse->status->code == "200"){
            #2 CREDIT OPERATION   
            $game_transextension_credit = ProviderHelper::createGameTransExtV2($gamerecord,$provider_trans_id, $round_id, $win_amount, 2);
            $client_response_credit = ClientRequestHelper::fundTransfer($client_details,abs($win_amount),$game_information->game_code,$game_information->game_name,$game_transextension_credit,$gamerecord, 'credit');
            $general_details['client']['after_balance'] = ProviderHelper::amountToFloat($client_response_credit->fundtransferresponse->balance);
            $response = [
                "balance" => $this->formatBalance($client_response_credit->fundtransferresponse->balance),
                "status" => "success",
                "statusCode" =>  0
            ];
            if($check_bet_round != 'false'){
                $win_or_lost = $existing_bet_details->win;
                $entry_id = $existing_bet_details->entry_id;

                $pay_amount = $existing_bet_details->pay_amount + $win_amount;
                $bet_amount = $existing_bet_details->bet_amount + $bet_amount;
                $income = $bet_amount - $pay_amount; //$existing_bet_details->income;

                if($pay_amount == $bet_amount){
                    $win_or_lost = 3;
                }

                ProviderHelper::updateGameTransaction($gamerecord, $pay_amount, $income, $win_or_lost, $entry_id,'game_trans_id',$bet_amount,$multi_bet=true);
            }else{
                $pay_amount = $win_amount;
                $income = $bet_amount - $pay_amount;
                if($win_amount > 0){
                   $win_or_lost = 1;
                   $entry_id = 2;
                }else{
                   $win_or_lost = 0;
                   $entry_id = 1;
                }

                if($pay_amount == $bet_amount){
                    $win_or_lost = 3;
                }
                
                ProviderHelper::updateGameTransaction($gamerecord, $pay_amount, $income, $win_or_lost, $entry_id);
            }
            ProviderHelper::updatecreateGameTransExt($game_transextension, $data, $response, $client_response->requestoclient, $client_response, $response,$general_details);
            ProviderHelper::updatecreateGameTransExt($game_transextension_credit, $data, $response, $client_response_credit->requestoclient, $client_response_credit, $response,$general_details);
        }elseif(isset($client_response->fundtransferresponse->status->code) 
                    && $client_response->fundtransferresponse->status->code == "402"){
            if($check_bet_round == 'false'){
                 if(ProviderHelper::checkFundStatus($client_response->fundtransferresponse->status->status)):
                       ProviderHelper::updateGameTransactionStatus($gamerecord, 2, 6);
                else:
                   ProviderHelper::updateGameTransactionStatus($gamerecord, 2, 99);
                endif;
            }
          $response = ["status" => "Low Balance", "statusCode" =>  200];
          ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $data, 'FAILED', $client_response, 'FAILED', $general_details);
        }else{ // Unknown Response Code
          $response = ["status" => "Client Error", "statusCode" =>  1];
          ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $response, 'FAILED', 'FAILED', 'FAILED', $general_details);
          Helper::saveLog('KAGaming checkPlay - FATAL ERROR', $this->provider_db_id, $response, Helper::datesent());
        }  
        return $response;
    }


    public function gameCredit(Request $request){
        Helper::saveLog('KAGaming gameCredit - EH', $this->provider_db_id, json_encode($request->all()), $request->input("hash"));

        $request_body = $request->getContent();
        if(!$request->input("hash") != ''){
            return  $response = ["status" => "failed", "statusCode" =>  3];
        }
        if(!$this->verifyHash($request_body, $request->input("hash"))){
            return  $response = ["status" => "failed", "statusCode" =>  3];
        }

        $data = json_decode($request_body);
        $general_details = ["aggregator" => [], "provider" => [], "client" => []];
        $amount = $this->formatAmounts($data->amount);

        $payout_reason = 'Credited Side Bets';
        $provider_trans_id = $data->transactionId;
        $game_code = $data->gameId;
        $session_check = Providerhelper::getClientDetails('token',$data->sessionId);
        if($session_check == 'false'){
            return  $response = ["status" => "failed", "statusCode" =>  100];
        }
        $client_details = Providerhelper::getClientDetails('player_id',$data->partnerPlayerId);
        if($client_details == 'false'){
            return  $response = ["status" => "failed", "statusCode" =>  4];
        }
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
        if($player_details == 'false'){
            return  $response = ["status" => "Server Timeout", "statusCode" =>  1];
        }
        $game_information = Helper::findGameDetails('game_code', $this->provider_db_id, $game_code);
        if($game_information == null){ 
            return  $response = ["status" => "Game Not Found", "statusCode" =>  1];
        }
        $game_ext_check = ProviderHelper::findGameExt($provider_trans_id, 1, 'transaction_id');
        if($game_ext_check == 'false'){ // Duplicate transaction
            return  $response = ["status" => "Licensee or operator denied crediting to player (cashable or bonus) / Transaction Not Found", "statusCode" =>  301];
        }
        $general_details['client']['before_balance'] = ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance);
        $general_details['client']['action'] = 'credit';

        $game_ext_check_win = ProviderHelper::findGameExt($provider_trans_id, 2, 'transaction_id');
        if($game_ext_check_win != 'false'){
            $transaction_general_details = json_decode($game_ext_check_win->general_details);
            if(isset($transaction_general_details->client->action) && $transaction_general_details->client->action == 'credit'){
                return  $response = ["status" => "Double transactionId with an action credit", "statusCode" =>  301];
            }
        }

        $gamerecord = $game_ext_check->game_trans_id;
        $existing_bet = ProviderHelper::findGameTransaction($gamerecord,'game_transaction');

        $round_id = $existing_bet->round_id;
      
       
        $bet_amount = $existing_bet->bet_amount;
        $pay_amount =  $existing_bet->pay_amount + $amount; //abs($data['amount']);
        $income = $bet_amount - $pay_amount;

        if($pay_amount > 0){
            $entry_id = 2; // Credit
            $win_or_lost = 1; // 0 lost,  5 processing
        }else{
            $entry_id = 1; // Debit
            $win_or_lost = 0; // 0 lost,  5 processing
        }

        $game_transaction_type = 2; // 1 Bet, 2 Win
        $game_code = $game_information->game_id;
        $token_id = $client_details->token_id;

        $game_transextension = ProviderHelper::createGameTransExtV2($gamerecord,$provider_trans_id, $round_id, $bet_amount, $game_transaction_type);

        try {
          $client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_information->game_code,$game_information->game_name,$game_transextension,$gamerecord, 'credit');
          Helper::saveLog('KAGaming gameCredit CRID '.$gamerecord, $this->provider_db_id,json_encode($request->all()), $client_response);
           
        } catch (\Exception $e) {
          $response = ["status" => "Server Timeout", "statusCode" =>  1];
          ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $response, 'FAILED', $e->getMessage(), 'FAILED', $general_details);
          Helper::saveLog('KAGaming gameCredit - FATAL ERROR', $this->provider_db_id, $response, Helper::datesent());
          return $response;
        }
        if(isset($client_response->fundtransferresponse->status->code) 
             && $client_response->fundtransferresponse->status->code == "200"){
            $general_details['client']['after_balance'] = ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance);
            $response = [
                "balance" => $this->formatBalance($client_response->fundtransferresponse->balance),
                "status" => "success",
                "statusCode" =>  0
            ];
            ProviderHelper::updateGameTransaction($gamerecord, $pay_amount, $income, $win_or_lost, $entry_id);
            ProviderHelper::updatecreateGameTransExt($game_transextension, $data, $response, $client_response->requestoclient, $client_response, $response,$general_details);
        }elseif(isset($client_response->fundtransferresponse->status->code) 
                    && $client_response->fundtransferresponse->status->code == "402"){
          $response = ["status" => "success", "statusCode" =>  200];
          ProviderHelper::updateGameTransaction($gamerecord, $pay_amount, $income, 2, $entry_id);
          ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $data, 'FAILED', $client_response, 'FAILED', $general_details);
        }else{ // Unknown Response Code
          $response = ["status" => "Client Error", "statusCode" =>  1];
          ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $response, 'FAILED', 'FAILED', 'FAILED', $general_details);
          Helper::saveLog('KAGaming gameCredit - FATAL ERROR', $this->provider_db_id, $response, Helper::datesent());
        }  
        return $response;
    }


    public function gameRevoke(Request $request){
        Helper::saveLog('KAGaming gameRevoke - EH', $this->provider_db_id, json_encode($request->all()), $request->input("hash"));
        $request_body = $request->getContent();
        if(!$request->input("hash") != ''){
            return  $response = ["status" => "failed", "statusCode" =>  3];
        }
        if(!$this->verifyHash($request_body, $request->input("hash"))){
            return  $response = ["status" => "failed", "statusCode" =>  3];
        }
        $data = json_decode($request_body);
        $general_details = ["aggregator" => [], "provider" => [], "client" => []];
        $game_code = $data->gameId;
        $provider_trans_id = $data->transactionId;
        $round_id = $provider_trans_id.'_'.$data->round;

        $session_check = Providerhelper::getClientDetails('token',$data->sessionId);
        if($session_check == 'false'){
            return  $response = ["status" => "failed", "statusCode" =>  100];
        }
        $client_details = Providerhelper::getClientDetails('player_id',$data->partnerPlayerId);
        if($client_details == 'false'){
            return  $response = ["status" => "failed", "statusCode" =>  4];
        }
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
        if($player_details == 'false'){
            return  $response = ["status" => "Server Timeout", "statusCode" =>  1];
        }
        $game_information = Helper::findGameDetails('game_code', $this->provider_db_id, $game_code);
        if($game_information == null){ 
            return  $response = ["status" => "Game Not Found", "statusCode" =>  1];
        }
        $transaction_details = ProviderHelper::findGameExt($provider_trans_id, 1, 'transaction_id');
        if($transaction_details == 'false'){ // Duplicate transaction
            return  $response = ["status" => "revoke Transaction does not exist", "statusCode" =>  400];
        }
        // $check_revoked = ProviderHelper::findGameExt($provider_trans_id, 3, 'transaction_id');
        $check_revoked = ProviderHelper::findGameExt($round_id, 3, 'round_id');
        if($check_revoked != 'false'){ // Duplicate transaction
            return  $response = ["status" => "Transaction no longer revocable", "statusCode" =>  401];
        }
        $general_details['client']['before_balance'] = ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance);

        $all_round = $this->findAllGameExt($provider_trans_id, 'all', $round_id);
        $bet_amounts = array();
        $win_amounts = array();
        if(count($all_round) != 0){
            foreach ($all_round as $key) {
                if($key->game_transaction_type == 1){
                    array_push($bet_amounts, $key->amount);
                }elseif($key->game_transaction_type == 2){
                    array_push($win_amounts, $key->amount);
                }
            }
        }
        $refund_amount = array_sum($bet_amounts)-array_sum($win_amounts);
        
        $method = $transaction_details->game_transaction_type;
        $entry_id = $transaction_details->game_transaction_type;
        $win_or_lost = 4; // 0 lost,  5 processing
        $payout_reason = 'Refund - Revoked';
        $game_code = $data->gameId;

        $game_transaction_type = 3; // 1 Bet, 2 Win
        $game_code = $game_information->game_id;
        $token_id = $client_details->token_id;

        $gamerecord = $transaction_details->game_trans_id;

        if($refund_amount < 0){
           $transaction_type = 'debit';
           $pay_amount =  0; //abs($data['amount']);
           $income = 0;
           if(ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance) < abs($refund_amount)){
                 return  $response = ["status" => "Insufficient balance", "statusCode" =>  200];
           }
        }else{
           $transaction_type = 'credit';
           $pay_amount =  0; //abs($data['amount']);
           $income = 0;
        }

        #1 DEBIT OPERATION
        $game_transextension = ProviderHelper::createGameTransExtV2($gamerecord,$provider_trans_id, $round_id, abs($refund_amount), $game_transaction_type);

        try {
          $client_response = ClientRequestHelper::fundTransfer($client_details,abs($refund_amount),$game_information->game_code,$game_information->game_name,$game_transextension,$gamerecord, $transaction_type, true);
          Helper::saveLog('KAGaming gameRevoke CRID '.$gamerecord, $this->provider_db_id,json_encode($request->all()), $client_response);
           
        } catch (\Exception $e) {
          $response = ["status" => "Server Timeout", "statusCode" =>  1];
          ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $response, 'FAILED', $e->getMessage(), 'FAILED', $general_details);
          Helper::saveLog('KAGaming gameRevoke - FATAL ERROR', $this->provider_db_id, $response, Helper::datesent());
          return $response;
        }
        if(isset($client_response->fundtransferresponse->status->code) 
             && $client_response->fundtransferresponse->status->code == "200"){
            #2 CREDIT OPERATION   
            $general_details['client']['after_balance'] = ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance);
            $response = [
                "balance" => $this->formatBalance($client_response->fundtransferresponse->balance),
                "status" => "success",
                "statusCode" =>  0
            ];
            ProviderHelper::updateGameTransaction($gamerecord, $pay_amount, $income, $win_or_lost, $entry_id);
            ProviderHelper::updatecreateGameTransExt($game_transextension, $data, $response, $client_response->requestoclient, $client_response, $response,$general_details);
        }elseif(isset($client_response->fundtransferresponse->status->code) 
                    && $client_response->fundtransferresponse->status->code == "402"){
          $response = ["status" => "success", "statusCode" =>  200];
          ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $data, 'FAILED', $client_response, 'FAILED', $general_details);
        }else{ // Unknown Response Code
          $response = ["status" => "Client Error", "statusCode" =>  1];
          ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $response, 'FAILED', 'FAILED', 'FAILED', $general_details);
          Helper::saveLog('KAGaming gameRevoke - FATAL ERROR', $this->provider_db_id, $response, Helper::datesent());
        }  
        return $response;
    }


    public function gameEnd(Request $request){
        Helper::saveLog('KAGaming gameEnd - EH', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');
        $request_body = $request->getContent();

        if(!$request->input("hash") != ''){
            return  $response = ["status" => "failed", "statusCode" =>  3];
        }
        if(!$this->verifyHash($request_body, $request->input("hash"))){
            return  $response = ["status" => "failed", "statusCode" =>  3];
        }
        $data = json_decode($request_body);
        $session_check = Providerhelper::getClientDetails('token',$data->sessionId);
        if($session_check == 'false'){
            return  $response = ["status" => "failed", "statusCode" =>  100];
        }
        $client_details = Providerhelper::getClientDetails('player_id',$data->partnerPlayerId);
        if($client_details == 'false'){
            return  $response = ["status" => "failed", "statusCode" =>  4];
        }
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
        if($player_details == 'false'){
            return  $response = ["status" => "Server Timeout", "statusCode" =>  1];
        }
        $response = [
            "balance" => $this->formatBalance($player_details->playerdetailsresponse->balance),
            "status" => "success",
            "statusCode" =>  0
        ];
        return $response;
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
