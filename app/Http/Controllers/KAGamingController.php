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
        return $amount*100;
    }


    public function playerBalance(Request $request){
        Helper::saveLog('KAGaming playerBalance - EH', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');
        return  $response = ["status" => "TEST", "statusCode" =>  1];
    }

    public function playerStart(Request $request){
        Helper::saveLog('KAGaming playerStart - EH', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');
        return  $response = ["status" => "TEST", "statusCode" =>  1];
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
        $amount = abs($data->betAmount);
        $bet_amount = abs($data->betAmount);
        $win_amount = abs($data->winAmount);
        $pay_amount =  0; //abs($data['amount']);
        $income = $bet_amount - $pay_amount;
        $win_type = 0;
        $method = 1;
        $win_or_lost = 5; // 0 lost,  5 processing
        $payout_reason = 'Game bets';
        $provider_trans_id = $data->transactionId;
        $round_id = $data->transactionId;
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
        if($game_ext_check != 'false'){ // Duplicate transaction
            return  $response = ["status" => "Game Not Found", "statusCode" =>  1];
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
          $response = ["error_code" => -1];
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
         return  $response = ["status" => "TEST", "statusCode" =>  1];
    }


    public function gameRevoke(Request $request){
         Helper::saveLog('KAGaming gameRevoke - EH', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');
         return  $response = ["status" => "TEST", "statusCode" =>  1];
    }


    public function gameEnd(Request $request){
         Helper::saveLog('KAGaming gameEnd - EH', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');
         return  $response = ["status" => "TEST", "statusCode" =>  1];
    }


}
