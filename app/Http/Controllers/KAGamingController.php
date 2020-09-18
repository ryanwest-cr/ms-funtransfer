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

	public $gamelaunch = 'https://gamesstage.kaga88.com/';
	public $ka_api = 'https://rmpstage.kaga88.com/kaga/';
	public $access_key = 'A95383137CE37E4E19EAD36DF59D589A';
	public $secret_key = '40C6AB9E806C4940E4C9D2B9E3A0AA25';
    public $provider_db_id = 43;

	public function generateHash($msg=''){
		return hash_hmac('sha256', json_encode($msg), $this->secret_key);
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
            "language" => "zh",
            "randomId" => 1,
        ];
        $guzzle_response = $client->post($this->ka_api.'gameList?hash='.$this->generateHash($body),
            ['body' => json_encode(
                    $body
            )]
        );

        $client_response = json_decode($guzzle_response->getBody()->getContents());
        return json_encode($client_response);
    }

    public function formatBalance($amount){
        return round($amount*100,2);
    }


    public function formatAmounts($amount){
         return round($amount/100,2);
    }

    public function gameStart(Request $request){
        // Helper::saveLog('KAGaming gameStart - EH', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');
        $request_body = file_get_contents("php://input");
        if(!$request->input("hash") != ''){
            return  $response = ["status" => "failed", "statusCode" =>  3];
        }
        // if($this->generateHash($request_body) != $request->input("hash")){
        //     return  $response = ["status" => "failed", "statusCode" =>  3];
        // }
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
        $request_body = file_get_contents("php://input");
        if(!$request->input("hash") != ''){
            return  $response = ["status" => "failed", "statusCode" =>  3];
        }
        // if($this->generateHash($request_body) != $request->input("hash")){
        //     return  $response = ["status" => "failed", "statusCode" =>  3];
        // }
        $data = json_decode($request_body);
        // $session_check = Providerhelper::getClientDetails('token',$data->sessionId);
        // if($session_check == 'false'){
        //     return  $response = ["status" => "failed", "statusCode" =>  100];
        // }
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
        Helper::saveLog('KAGaming checkPlay - EH', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');
        $request_body = file_get_contents("php://input");
        if(!$request->input("hash") != ''){
            return  $response = ["status" => "failed", "statusCode" =>  3];
        }
        // if($this->generateHash($request_body) != $request->input("hash")){
        //     return  $response = ["status" => "failed", "statusCode" =>  3];
        // }
        $data = json_decode($request_body);
        $general_details = ["aggregator" => [], "provider" => [], "client" => []];
        $freeGames = $data->freeGames; 
        $provider_trans_id = $data->transactionId;
        $round_id = isset($data->round) ? $data->round : $provider_trans_id;
        $game_code = $data->gameId;

        if($freeGames == true){
            $bet_amount = 0;
        }else{
            $bet_amount = $this->formatAmounts($data->betAmount);
        }

        $amount = $this->formatAmounts($data->betAmount);
        $win_amount = $this->formatAmounts($data->winAmount);
        $pay_amount =  0; //abs($data['amount']);
        $method = 1;
        $income = $bet_amount - $pay_amount;
        $entry_id = 1;
        $win_or_lost = 5; // 0 lost,  5 processing
        $payout_reason = 'Game Bets and Win';
        
        // $session_check = Providerhelper::getClientDetails('token',$data->sessionId);
        // if($session_check == 'false'){
        //     return  $response = ["status" => "failed", "statusCode" =>  100];
        // }
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
        if($game_ext_check != 'false'){ // Duplicate transaction
            return  $response = ["status" => "Duplicate transaction", "statusCode" =>  1];
        }

        $game_transaction_type = 1; // 1 Bet, 2 Win
        $game_code = $game_information->game_id;
        $token_id = $client_details->token_id;


        $check_bet_round = ProviderHelper::findGameExt($round_id, 1, 'round_id');
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
          ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $response, 'FAILED', $e->getMessage(), 'FAILED', $general_details);
          Helper::saveLog('KAGaming checkPlay - FATAL ERROR', $this->provider_db_id, $response, Helper::datesent());
          return $response;
        }
        if(isset($client_response->fundtransferresponse->status->code) 
             && $client_response->fundtransferresponse->status->code == "200"){
            #2 CREDIT OPERATION   
            $game_transextension_credit = ProviderHelper::createGameTransExtV2($gamerecord,$provider_trans_id, $round_id, $win_amount, 2);
            $client_response_credit = ClientRequestHelper::fundTransfer($client_details,abs($win_amount),$game_information->game_code,$game_information->game_name,$game_transextension_credit,$gamerecord, 'credit');
            $response = [
                "balance" => $this->formatBalance($client_response_credit->fundtransferresponse->balance),
                "status" => "success",
                "statusCode" =>  0
            ];
         
            if($check_bet_round != 'false'){
                $pay_amount = $existing_bet_details->pay_amount + $win_amount;
                $bet_amount = $existing_bet_details->bet_amount + $bet_amount;
                $income = $bet_amount - $pay_amount; //$existing_bet_details->income;
                $win_or_lost = $existing_bet_details->win;
                $entry_id = $existing_bet_details->entry_id;
                ProviderHelper::updateGameTransaction($gamerecord, $pay_amount, $income, $win_or_lost, $entry_id,'game_trans_id',$bet_amount,$multi_bet=true);
            }else{
                $pay_amount = $win_amount;
                $income = $bet_amount - $pay_amount;
                $win_or_lost = 1;
                $entry_id = 2;
                ProviderHelper::updateGameTransaction($gamerecord, $pay_amount, $income, $win_or_lost, $entry_id);
            }
            ProviderHelper::updatecreateGameTransExt($game_transextension, $data, $response, $client_response->requestoclient, $client_response, $response,$general_details);
            ProviderHelper::updatecreateGameTransExt($game_transextension_credit, $data, $response, $client_response_credit->requestoclient, $client_response_credit, $response,$general_details);
        }elseif(isset($client_response->fundtransferresponse->status->code) 
                    && $client_response->fundtransferresponse->status->code == "402"){
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
        Helper::saveLog('KAGaming gameCredit - EH', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');

        return  $response = ["status" => "Licensee or operator denied crediting to player (cashable or bonus)
        balance", "statusCode" =>  301];

        $request_body = file_get_contents("php://input");
        if(!$request->input("hash") != ''){
            return  $response = ["status" => "failed", "statusCode" =>  3];
        }
        // if($this->generateHash($request_body) != $request->input("hash")){
        //     return  $response = ["status" => "failed", "statusCode" =>  3];
        // }
        $data = json_decode($request_body);
        $general_details = ["aggregator" => [], "provider" => [], "client" => []];

        $bet_amount = 0;
        $amount = $this->formatAmounts($data->amount);
        $win_amount = $this->formatAmounts($data->amount);
        $pay_amount =  $win_amoun; //abs($data['amount']);
        $income = $bet_amount - $pay_amount;
        $win_type = 0;
        $method = 1;
        $win_or_lost = 5; // 0 lost,  5 processing
        $payout_reason = 'Free Bet';
        $provider_trans_id = $data->transactionId;
        $round_id = $data->transactionId;
        $game_code = $data->gameId;

        // $session_check = Providerhelper::getClientDetails('token',$data->sessionId);
        // if($session_check == 'false'){
        //     return  $response = ["status" => "failed", "statusCode" =>  100];
        // }
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
            return  $response = ["status" => "Duplicate transaction", "statusCode" =>  1];
        }

        $game_transaction_type = 1; // 1 Bet, 2 Win
        $game_code = $game_information->game_id;
        $token_id = $client_details->token_id;

        #1 DEBIT OPERATION
        $gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_code, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
        $game_transextension = ProviderHelper::createGameTransExtV2($gamerecord,$provider_trans_id, $round_id, $bet_amount, $game_transaction_type);

        try {
          $client_response = ClientRequestHelper::fundTransfer($client_details,abs($bet_amount),$game_information->game_code,$game_information->game_name,$game_transextension,$gamerecord, 'debit');
          Helper::saveLog('KAGaming checkPlay CRID '.$gamerecord, $this->provider_db_id,json_encode($request->all()), $client_response);
           
        } catch (\Exception $e) {
          $response = ["status" => "Server Timeout", "statusCode" =>  1];
          ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $response, 'FAILED', $e->getMessage(), 'FAILED', $general_details);
          Helper::saveLog('KAGaming checkPlay - FATAL ERROR', $this->provider_db_id, $response, Helper::datesent());
          return $response;
        }
        if(isset($client_response->fundtransferresponse->status->code) 
             && $client_response->fundtransferresponse->status->code == "200"){
            #2 CREDIT OPERATION   
            $game_transextension_credit = ProviderHelper::createGameTransExtV2($gamerecord,$provider_trans_id, $round_id, $win_amount, 2);
            $client_response_credit = ClientRequestHelper::fundTransfer($client_details,abs($win_amount),$game_information->game_code,$game_information->game_name,$game_transextension_credit,$gamerecord, 'credit');
            $response = [
                "balance" => $this->formatBalance($client_response_credit->fundtransferresponse->balance),
                "status" => "success",
                "statusCode" =>  0
            ];
            $pay_amount = $win_amount;
            $income = $bet_amount - $pay_amount;
            $win_or_lost = 1;
            $entry_id = 2;
            ProviderHelper::updateGameTransaction($gamerecord, $pay_amount, $income, $win_or_lost, $entry_id);
            ProviderHelper::updatecreateGameTransExt($game_transextension, $data, $response, $client_response->requestoclient, $client_response, $response,$general_details);
            ProviderHelper::updatecreateGameTransExt($game_transextension_credit, $data, $response, $client_response_credit->requestoclient, $client_response_credit, $response,$general_details);
        }elseif(isset($client_response->fundtransferresponse->status->code) 
                    && $client_response->fundtransferresponse->status->code == "402"){
          $response = ["status" => "success", "statusCode" =>  200];
          ProviderHelper::updateGameTransaction($gamerecord, $pay_amount, $income, 2, $entry_id);
          ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $data, 'FAILED', $client_response, 'FAILED', $general_details);
        }else{ // Unknown Response Code
          $response = ["status" => "Client Error", "statusCode" =>  1];
          ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $response, 'FAILED', 'FAILED', 'FAILED', $general_details);
          Helper::saveLog('KAGaming checkPlay - FATAL ERROR', $this->provider_db_id, $response, Helper::datesent());
        }  
        return $response;
    }


    public function gameRevoke(Request $request){
        Helper::saveLog('KAGaming gameRevoke - EH', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');
        $request_body = file_get_contents("php://input");
        if(!$request->input("hash") != ''){
            return  $response = ["status" => "failed", "statusCode" =>  3];
        }
        // if($this->generateHash($request_body) != $request->input("hash")){
        //     return  $response = ["status" => "failed", "statusCode" =>  3];
        // }
        $data = json_decode($request_body);
        $general_details = ["aggregator" => [], "provider" => [], "client" => []];
        $game_code = $data->gameId;
        $provider_trans_id = $data->transactionId;
        $round_id = $data->transactionId;

        // $session_check = Providerhelper::getClientDetails('token',$data->sessionId);
        // if($session_check == 'false'){
        //     return  $response = ["status" => "failed", "statusCode" =>  100];
        // }
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
        $check_revoked = ProviderHelper::findGameExt($provider_trans_id, 3, 'transaction_id');
        if($check_revoked != 'false'){ // Duplicate transaction
            return  $response = ["status" => "Transaction no longer revocable", "statusCode" =>  401];
        }


        $all_round = $this->findAllGameExt($round_id, 'round_id');
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
        $payout_reason = 'Refund';
        $game_code = $data->gameId;

        $game_transaction_type = 3; // 1 Bet, 2 Win
        $game_code = $game_information->game_id;
        $token_id = $client_details->token_id;

        $gamerecord = $transaction_details->game_trans_id;

        if($refund_amount < 0){
           $transaction_type = 'debit';
           $pay_amount =  0; //abs($data['amount']);
           $income = 0;
        }else{
           $transaction_type = 'credit';
           $pay_amount =  0; //abs($data['amount']);
           $income = 0;
        }

        #1 DEBIT OPERATION
        $game_transextension = ProviderHelper::createGameTransExtV2($gamerecord,$provider_trans_id, $round_id, abs($refund_amount), $game_transaction_type);

        try {
          $client_response = ClientRequestHelper::fundTransfer($client_details,abs($refund_amount),$game_information->game_code,$game_information->game_name,$game_transextension,$gamerecord, $transaction_type, true);
          Helper::saveLog('KAGaming checkPlay CRID '.$gamerecord, $this->provider_db_id,json_encode($request->all()), $client_response);
           
        } catch (\Exception $e) {
          $response = ["status" => "Server Timeout", "statusCode" =>  1];
          ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $response, 'FAILED', $e->getMessage(), 'FAILED', $general_details);
          Helper::saveLog('KAGaming checkPlay - FATAL ERROR', $this->provider_db_id, $response, Helper::datesent());
          return $response;
        }
        if(isset($client_response->fundtransferresponse->status->code) 
             && $client_response->fundtransferresponse->status->code == "200"){
            #2 CREDIT OPERATION   
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
          Helper::saveLog('KAGaming checkPlay - FATAL ERROR', $this->provider_db_id, $response, Helper::datesent());
        }  
        return $response;
    }


    public function gameEnd(Request $request){
        Helper::saveLog('KAGaming gameEnd - EH', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');
        $request_body = file_get_contents("php://input");
        if(!$request->input("hash") != ''){
            return  $response = ["status" => "failed", "statusCode" =>  3];
        }
        // if($this->generateHash($request_body) != $request->input("hash")){
        //     return  $response = ["status" => "failed", "statusCode" =>  3];
        // }
        $data = json_decode($request_body);
        // $session_check = Providerhelper::getClientDetails('token',$data->sessionId);
        // if($session_check == 'false'){
        //     return  $response = ["status" => "failed", "statusCode" =>  100];
        // }
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



    public  function findAllGameExt($provider_identifier, $type) {
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
        $result = $transaction_db->latest()->get();
        return $result ? $result : 'false';
    }


}
