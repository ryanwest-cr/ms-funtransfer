<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use App\Helpers\SkyWind;
use App\Helpers\GameLobby;
use App\Helpers\ClientRequestHelper;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Client;
use Carbon\Carbon;
use DB;


/**
 * @author's note: ‘Transfer In’ and ‘Transfer Out’ use the same account for all games
 * @author's note: provider ticket will be player token on our side!
 * @author's note: cust_idis prefixed with TG client id,client player id, eg TG8_98
 *
 */
class SkyWindController extends Controller
{

    public $api_url, $seamless_key, $seamless_username, $seamless_password, $merchant_data, $merchant_password;
    public $provider_db_id; // ID ON OUR DATABASE
    public $prefix_user;

    public function __construct(){
        $this->prefix_user = config('providerlinks.skywind.prefix_user');
        $this->provider_db_id = config('providerlinks.skywind.provider_db_id');
        $this->api_url = config('providerlinks.skywind.api_url');
        $this->seamless_key = config('providerlinks.skywind.seamless_key');
        $this->seamless_username = config('providerlinks.skywind.seamless_username');
        $this->seamless_password = config('providerlinks.skywind.seamless_password');
        $this->merchant_data = config('providerlinks.skywind.merchant_data');
        $this->merchant_password = config('providerlinks.skywind.merchant_password');
    }

    public function getAuth(Request $request){

        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
            ],
        ]);
        $requesttosend = [
             "secretKey" =>"47138d18-6b46-4bd4-8ae1-482776ccb82d",
             "username" => "TGAMESU_USER",
             "password" => "Tgames1234"
        ];
        $guzzle_response = $client->post('https://api.gcpstg.m27613.com/v1/login',
                ['body' => json_encode($requesttosend)]
        );
        // $client_response = json_decode($guzzle_response->getBody()->getContents());
        // return $client_response;
        $response = json_encode(json_decode($guzzle_response->getBody()->getContents()));
        $url = json_decode($response, true);
        return $url;
    }

    public function getGamelist(){
        $player_login = SkyWind::userLogin();
        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
                'X-ACCESS-TOKEN' => $player_login->accessToken,
            ]
        ]);
        $response = $client->get($this->api_url.'/games/info/search?limit=20');
        $response = $response->getBody()->getContents();
        return $response;
    }

    /**
     * @author's note: provider ticket will be player token on our side!
     * @param  Request = [ticket,merch_id,merch_pwd,ip=optional]
     * @return [json array]
     * 
     */
    public function validateTicket(Request $request){
      Helper::saveLog('Skywind validateTicket - EH', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT HIT!');
      $raw_request = file_get_contents("php://input");
      parse_str($raw_request, $data);
      $token = $data['ticket'];
      $client_details = Providerhelper::getClientDetails('token',$token); // ticket
      if($client_details == null){
         $response = ["error_code" => -2];
          Helper::saveLog('Skywind validateTicket - NO PLAYER FOUND', $this->provider_db_id, json_encode(file_get_contents("php://input")), $response);
          return $response;
      }
      $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
      if($player_details != 'false'){
          $response = [
          "error_code" => 0,
          "cust_session_id" => $client_details->player_token,
          "cust_id" => $this->prefix_user.$client_details->client_id.'_'.$client_details->player_id,
          "currency_code" => $client_details->default_currency,
          "test_cust" => false,
          // "country" => "GB", // Optional
          // "game_group" => "Double Bets Group", // Optional
          // "rci" => 60, // Optional
          // "rce" => 11  // Optional
        ];
      }else{
        $response = ["error_code" => -1];
      }
      Helper::saveLog('Skywind validateTicket - SUCCESS', $this->provider_db_id, json_encode(file_get_contents("php://input")), $response);
    	return $response;
    }

    /**
     * @author's note: provider ticket will be player token on our side!
     * @param  Request = [ticket,merch_id,merch_pwd,ip=optional]
     * @return [json array]
     * 
     */
    public  function getTicket(Request $request){
        Helper::saveLog('Skywind getTicket EH', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT HIT!');
        $raw_request = file_get_contents("php://input");
        parse_str($raw_request, $data);

        $cust_id = $data['cust_id'];
        $player_id_qry = Providerhelper::explodeUsername('_', $cust_id);
        $client_details = Providerhelper::getClientDetails('player_id', $player_id_qry); // ticket
        if($client_details == null){
             $response = ["error_code" => -2];
            Helper::saveLog('Skywind getTicket - NO PLAYER FOUND', $this->provider_db_id, json_encode(file_get_contents("php://input")), $response);
            return $response;
        }
        if(isset($data['currency_code'])){
          if($client_details->default_currency != $data['currency_code']){
            $response = ["error_code" => 702]; // currency dont match
            Helper::saveLog('Skywind getTicket - Currency Dont Match', $this->provider_db_id, json_encode(file_get_contents("php://input")), $response);
            return $response;
          }
        }
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token, true);
        if($player_details != 'false'){
           $response = [
              "ticket" => $player_details->playerdetailsresponse->refreshtoken,
           ];
        }else{
          $response = ["error_code" => -1]; // internal error
          Helper::saveLog('Skywind getTicket - FATAL ERROR', $this->provider_db_id, json_encode(file_get_contents("php://input")), $response);
          return $response;
        }

        Helper::saveLog('Skywind getTicket - SUCCESS', $this->provider_db_id, json_encode(file_get_contents("php://input")), $response);
        return $response;
    }

    public  function getBalance(Request $request){
      // Helper::saveLog('Skywind getBalance EH', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT HIT!');
      $raw_request = file_get_contents("php://input");
      parse_str($raw_request, $data);
      // dd($data);
      $cust_id = $data['cust_id'];
      $player_id_qry = Providerhelper::explodeUsername('_', $cust_id);
      $client_details = Providerhelper::getClientDetails('player_id', $player_id_qry);
      if($client_details == null){
           $response = ["error_code" => -2];
          Helper::saveLog('Skywind getBalance - NO PLAYER FOUND', $this->provider_db_id, json_encode(file_get_contents("php://input")), $response);
          return $response;
      }
      $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
      if($player_details != 'false'){
        $response = [
            "error_code" => 0,
            "balance" =>  Providerhelper::amountToFloat($player_details->playerdetailsresponse->balance),
            "currency_code" => $client_details->default_currency,
        ];
      }else{
        $response = ["error_code" => -1];
      }
      // Helper::saveLog('Skywind getBalance - SUCCESS', $this->provider_db_id, json_encode(file_get_contents("php://input")), $response);
      return $response;
    }

    /**
     * @param  Request = [merch_id,merch_pwd,cust_session_id, round_id, amount, currency_code, game_code,trx_id, game_id, event_type, event_id, game_type]
     * @return [json array]
     * 
     */
    public  function gameDebit(Request $request){
        Helper::saveLog('Skywind Debit', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');
        $raw_request = file_get_contents("php://input");
        parse_str($raw_request, $data);
        $general_details = ["aggregator" => [], "provider" => [], "client" => []];
        $cust_id = $data['cust_id'];
        $amount = $data['amount'];
        $event_type = $data['event_type'];
        $bet_amount = abs($data['amount']);
        $pay_amount =  0; //abs($data['amount']);
        $income = $bet_amount - $pay_amount;
        $win_type = 0;
        $method = 1;
        $win_or_lost = 5; // 0 lost,  5 processing
        $payout_reason = 'Game bets';
        $provider_trans_id = $data['trx_id'];
        $round_id = $data['round_id'];
        $game_code = $data['game_code'];

        $cust_id = $data['cust_id'];
        $player_id_qry = Providerhelper::explodeUsername('_', $cust_id);
        $client_details = Providerhelper::getClientDetails('player_id', $player_id_qry);
        if($client_details == null){  // details/player not found
            $response = ["error_code" => -2];
            Helper::saveLog('SkyWind gameDebit - DUPLICATE', $this->provider_db_id,json_encode($request->all()), $response);
            return $response;
        }
        $game_information = Helper::findGameDetails('game_code', $this->provider_db_id, $game_code);
        if($game_information == null){  // game not found
            $response = [  "error_code" => 240];
            Helper::saveLog('SkyWind gameDebit - DUPLICATE', $this->provider_db_id,json_encode($request->all()), $response);
            return $response;
        }
        $game_ext_check = ProviderHelper::findGameExt($provider_trans_id, 1, 'transaction_id');
        if($game_ext_check != 'false'){ // Duplicate transaction
            $response = ["error_code" => 1];
            Helper::saveLog('SkyWind gameDebit - DUPLICATE', $this->provider_db_id,json_encode($request->all()), $response);
            return $response;
        }

        $game_transaction_type = 1; // 1 Bet, 2 Win
        $game_code = $game_information->game_id;
        $token_id = $client_details->token_id;

        $check_bet_round = ProviderHelper::findGameExt($round_id, 1, 'round_id');
        if($check_bet_round != 'false'){
          $existing_bet_details = Providerhelper::findGameTransaction($check_bet_round->game_trans_id, 'game_transaction');

          $pay_amount = $existing_bet_details->pay_amount;
          $bet_amount = $existing_bet_details->bet_amount + $amount;
          $income = $bet_amount - $pay_amount; //$existing_bet_details->income;

          $this->updateGameTransaction($existing_bet_details->game_trans_id, $pay_amount, $income, $existing_bet_details->win, $existing_bet_details->entry_id,$bet_amount);
          $game_transextension = ProviderHelper::createGameTransExtV2($existing_bet_details->game_trans_id,$provider_trans_id, $round_id, $amount, $game_transaction_type);
          $gamerecord = $existing_bet_details->game_trans_id;

        }else{
          $gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_code, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
          $game_transextension = ProviderHelper::createGameTransExtV2($gamerecord,$provider_trans_id, $round_id, $pay_amount, $game_transaction_type);
        }

        try {
          $client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_information->game_code,$game_information->game_name,$game_transextension,$gamerecord, 'debit');
          Helper::saveLog('SkyWind gameDebit CRID '.$gamerecord, $this->provider_db_id,json_encode($request->all()), $client_response);
           
        } catch (\Exception $e) {
          $response = ["error_code" => -1];
          ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $response, 'FAILED', $e->getMessage(), 'FAILED', $general_details);
          Helper::saveLog('SkyWind gameDebit - FATAL ERROR', $this->provider_db_id, $response, Helper::datesent());
          return $response;
        }

        if(isset($client_response->fundtransferresponse->status->code) 
             && $client_response->fundtransferresponse->status->code == "200"){
            $response = [
                "error_code" => 0,
                "balance" => Providerhelper::amountToFloat($client_response->fundtransferresponse->balance),
                "trx_id" => $provider_trans_id,
            ];
         ProviderHelper::updatecreateGameTransExt($game_transextension, $data, $response, $client_response->requestoclient, $client_response, $response,$general_details);

        }elseif(isset($client_response->fundtransferresponse->status->code) 
                    && $client_response->fundtransferresponse->status->code == "402"){
          $response = ["error_code" => -4];
          ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $data, 'FAILED', $client_response, 'FAILED', $general_details);
        }else{ // Unknown Response Code
          $response = [
                "error_code" => -1,
                // "balance" => $client_response->fundtransferresponse->balance,
                // "trx_id" => $provider_trans_id,
          ];
          ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $data, 'FAILED', $client_response, 'FAILED', $general_details);
          Helper::saveLog('SkyWind gameDebit - FATAL ERROR '.$gamerecord, $this->provider_db_id,json_encode($request->all()), $client_response);
          return $response;
        }  

        Helper::saveLog('SkyWind gameDebit - SUCCESS '.$gamerecord, $this->provider_db_id,json_encode($request->all()), $client_response);
        return $response;
    }

    /**
     * [gameCredit description]
     * @param  $[event_type] [<win, bonus, transfer_out>]
     * @param  $[trx_id] [<original bet id>]
     * @param  $[game_type] [<normal, freegame, bonusgame>]
     * 
     */
    public  function gameCredit(Request $request){
        Helper::saveLog('Skywind Credit', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');
        $raw_request = file_get_contents("php://input");
        parse_str($raw_request, $data);
        $general_details = ["aggregator" => [], "provider" => [], "client" => []];
        $cust_id = $data['cust_id'];
        $amount = $data['amount'];
        $trx_id = $data['trx_id'];
        $roundid = $data['round_id'];
        $game_code = $data['game_code'];

        $cust_id = $data['cust_id'];
        $player_id_qry = Providerhelper::explodeUsername('_', $cust_id);
        $client_details = Providerhelper::getClientDetails('player_id', $player_id_qry);
        if($client_details == null){   // details/player not found
            $response = ["error_code" => -2];
            return $response;
        }
        $game_information = Helper::findGameDetails('game_code', $this->provider_db_id, $game_code);
        if($game_information == null){ // game not found
            $response = ["error_code" => 240];
            return $response;
        }
        $game_ext_idempotency = ProviderHelper::findGameExt($trx_id, 2, 'transaction_id');
        if($game_ext_idempotency != 'false'){ // Duplicate transaction
            $response = ["error_code" => 1];
            return $response;
        }
        // $game_ext_check = ProviderHelper::findGameExt($trx_id, 1, 'transaction_id');
        $game_ext_check = ProviderHelper::findGameExt($roundid, 1, 'round_id');
        if($game_ext_check == 'false'){ // Transaction not found
            $response = ["error_code" => -7];
            return $response;
        }

        $provider_trans_id = $trx_id;
        $roundid = $roundid;
        $existing_bet = ProviderHelper::findGameTransaction($game_ext_check->game_trans_id, 'game_transaction'); // Find if win has bet record

        if($amount > 0){
          $win = 1;
          $entry_id = 2;
        }else{
          $win = 0;
          $entry_id = 1;
        }
        
        $pay_amount = $existing_bet->pay_amount + $amount;
        $income = $existing_bet->bet_amount - $pay_amount;
        $game_transaction_type = 2;

        $game_transextension = ProviderHelper::createGameTransExtV2($existing_bet->game_trans_id,$provider_trans_id, $roundid, $amount, $game_transaction_type);

        try {
          $client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_information->game_code,$game_information->game_name,$game_transextension, $existing_bet->game_trans_id, 'credit');
          Helper::saveLog('SkyWind gameCredit CRID '.$existing_bet->game_trans_id, $this->provider_db_id,json_encode($request->all()), $client_response);
           
        } catch (\Exception $e) {
          $response = ["error_code" => -1];
          ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $response, 'FAILED', $e->getMessage(), 'FAILED', $general_details);
          Helper::saveLog('SkyWind gameCredit - FATAL ERROR', $this->provider_db_id, $response, Helper::datesent());
          return $response;
        }

        if(isset($client_response->fundtransferresponse->status->code) 
             && $client_response->fundtransferresponse->status->code == "200"){
          $response = [
              "error_code" => 0,
              "balance" => Providerhelper::amountToFloat($client_response->fundtransferresponse->balance),
              "trx_id" => $trx_id,
          ];
          $this->updateBetTransaction($existing_bet->game_trans_id, $pay_amount, $income, $win, $entry_id);
          // $this->updateBetTransaction($trx_id, $amount, $income, $win, $entry_id);
          ProviderHelper::updatecreateGameTransExt($game_transextension, $data, $response, $client_response->requestoclient, $client_response, $response);

        }elseif(isset($client_response->fundtransferresponse->status->code) 
                    && $client_response->fundtransferresponse->status->code == "402"){
            $response = ["error_code" => -4];
        }else{ // Unknown Response Code
            $response = ["error_code" => -1];
            Helper::saveLog('SkyWind gameCredit - FATAL ERROR ', $this->provider_db_id,json_encode($request->all()), $client_response);
             ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $data, 'FAILED', $client_response, 'FAILED', $general_details);
            return $response;
        }  
        Helper::saveLog('SkyWind gameCredit - SUCCESS ', $this->provider_db_id,json_encode($request->all()), $client_response);
        return $response;
    }


    /**
     * [gameCredit description]
     * @param  $[event_type] [<rollback>]
     * @param  $[trx_id] [<original bet id>]
     * 
     */
    public  function gameRollback(Request $request){
        Helper::saveLog('Skywind gameRollback', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');
        // dd($this->seamless_key);
        $raw_request = file_get_contents("php://input");
        parse_str($raw_request, $data);
        $general_details = ["aggregator" => [], "provider" => [], "client" => []];
        $cust_id = $data['cust_id'];
        $trx_id = $data['trx_id'];
        $game_code = $data['game_code'];
        $event_id = $data['event_id'];
        $event_type = $data['event_type'];

        $cust_id = $data['cust_id'];
        $player_id_qry = Providerhelper::explodeUsername('_', $cust_id);
        $client_details = Providerhelper::getClientDetails('player_id', $player_id_qry);
        if($client_details == null){ 
             $response = [
                "error_code" => -2, // details/player not found
            ];
            return $response;
        }
        $game_information = Helper::findGameDetails('game_code', $this->provider_db_id, $game_code);
        if($game_information == null){
             $response = [
                "error_code" => 240,  // game not found
            ];
            return $response;
        }
        $game_ext_idempotency = ProviderHelper::findGameExt($trx_id, 3, 'transaction_id');
        if($game_ext_idempotency != 'false'){ // Duplicate transaction
            $response = ["error_code" => 1];
            return $response;
        }
        $game_ext_check = ProviderHelper::findGameExt($trx_id, 1, 'transaction_id'); // find bet
        if($game_ext_check == 'false'){ // Transaction not found
            $response = ["error_code" => -7];
            return $response;
        }
        $existing_bet = ProviderHelper::findGameTransaction($game_ext_check->game_trans_id, 'game_transaction'); // Find if win has bet record
        $amount = $existing_bet->bet_amount;
        $provider_trans_id = $trx_id;
        $roundid = $event_id;
        $win = 4;
        $entry_id = $existing_bet->entry_id;
        $pay_amount = $amount;
        $income = $existing_bet->bet_amount - $pay_amount;
        $game_transaction_type = 3;

        $game_transextension = ProviderHelper::createGameTransExtV2($existing_bet->game_trans_id,$provider_trans_id, $roundid, $pay_amount, $game_transaction_type);

        try {
          $client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_information->game_code,$game_information->game_name,$game_transextension,$existing_bet->game_trans_id, 'credit', true);
          Helper::saveLog('SkyWind gameRollback CRID '.$existing_bet->game_trans_id, $this->provider_db_id,json_encode($request->all()), $client_response);
           
        } catch (\Exception $e) {
          $response = ["error_code" => -1];
          ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $response, 'FAILED', $e->getMessage(), 'FAILED', $general_details);
          Helper::saveLog('SkyWind gameRollback - FATAL ERROR', $this->provider_db_id, $response, Helper::datesent());
          return $response;
        }

        if(isset($client_response->fundtransferresponse->status->code) 
             && $client_response->fundtransferresponse->status->code == "200"){
          $response = [
              "error_code" => 0,
              "balance" => Providerhelper::amountToFloat($client_response->fundtransferresponse->balance),
              "trx_id" => $trx_id,
          ];
          $this->updateBetTransaction($existing_bet->game_trans_id, $amount, $income, $win, $entry_id);
          // $this->updateBetTransaction($trx_id, $amount, $income, $win, $entry_id);
          ProviderHelper::updatecreateGameTransExt($game_transextension, $data, $response, $client_response->requestoclient, $client_response, $response, $general_details);

         }elseif(isset($client_response->fundtransferresponse->status->code) 
                    && $client_response->fundtransferresponse->status->code == "402"){
            $response = ["error_code" => -4];
        }else{ // Unknown Response Code
            $response = ["error_code" => -1];
            Helper::saveLog('SkyWind gameRollback - FATAL ERROR ', $this->provider_db_id,json_encode($request->all()), $client_response);
            ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $data, 'FAILED', $client_response, 'FAILED', $general_details);
            return $response;
        } 
        Helper::saveLog('SkyWind gameRollback - SUCCESS ', $this->provider_db_id,json_encode($request->all()), $client_response);
        return $response;        
    }


    /**
     * [gameCredit description]
     * @param  $[event_type] [<rollback>]
     * @param  $[trx_id] [<original bet id>]
     * 
     */
    public  function getFreeBet(Request $request){
        Helper::saveLog('Skywind Freebet Hit', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');
        $raw_request = file_get_contents("php://input");
        parse_str($raw_request, $data);
  
        $merch_id = $data['merch_id'];
        $cust_id = $data['cust_id'];
        $merch_pwd = $data['merch_pwd'];
        $cust_session_id = $data['cust_session_id'];
        $game_code = $data['game_code'];
        $coin_multiplier = $data['coin_multiplier'];
        $coin_multiplier = $data['stake_all'];

       $response = [
         "error_code" => 0,
         "free_bet_count" => 0, //10,
         "free_bet_coin" => 0 //0.1
       ];

       return $response;
    }

    // /**
    //  * Create Game Extension Logs bet/Win/Refund
    //  * @param [int] $[gametransaction_id] [<ID of the game transaction>]
    //  * @param [json array] $[provider_request] [<Incoming Call>]
    //  * @param [json array] $[mw_request] [<Outgoing Call>]
    //  * @param [json array] $[mw_response] [<Incoming Response Call>]
    //  * @param [json array] $[client_response] [<Incoming Response Call>]
    //  * 
    //  */
    // public  function createGameTransaction($token_id, $game_id, $bet_amount, $payout, $entry_id,  $win=0, $transaction_reason = null, $payout_reason = null , $income=null, $provider_trans_id=null, $round_id=1) {
    //     $data = [
    //                 "token_id" => $token_id,
    //                 "game_id" => $game_id,
    //                 "round_id" => $round_id,
    //                 "bet_amount" => $bet_amount,
    //                 "provider_trans_id" => $provider_trans_id,
    //                 "pay_amount" => $payout,
    //                 "income" => $income,
    //                 "entry_id" => $entry_id,
    //                 "win" => $win,
    //                 "transaction_reason" => $transaction_reason,
    //                 "payout_reason" => $payout_reason
    //             ];
    //     $data_saved = DB::table('game_transactions')->insertGetId($data);
    //     return $data_saved;
    // }

    /**
     * Find bet and update to win 
     * @param [int] $[round_id] [<ID of the game transaction>]
     * @param [int] $[pay_amount] [<amount to change>]
     * @param [int] $[income] [<bet - payout>]
     * @param [int] $[win] [<0 Lost, 1 win, 3 draw, 4 refund, 5 processing>]
     * @param [int] $[entry_id] [<1 bet, 2 win>]
     * 
     */
    public  function updateBetTransaction($game_trans_id, $pay_amount, $income, $win, $entry_id) {
        $update = DB::table('game_transactions')
                ->where('game_trans_id', $game_trans_id)
                // ->where('provider_trans_id', $provider_trans_id)
                ->update(['pay_amount' => $pay_amount, 
                      'income' => $income, 
                      'win' => $win, 
                      'entry_id' => $entry_id,
                      'transaction_reason' => ProviderHelper::updateReason($win),
                ]);
        return ($update ? true : false);
    }

    public  function updateGameTransaction($game_trans_id, $pay_amount, $income, $win, $entry_id,$bet_amount) {
        $update = DB::table('game_transactions')
                ->where('game_trans_id', $game_trans_id)
                ->update(['pay_amount' => $pay_amount, 
                      'income' => $income, 
                      'win' => $win, 
                      'entry_id' => $entry_id,
                      'bet_amount' => $bet_amount, 
                      'transaction_reason' => ProviderHelper::updateReason($win),
                ]);
        return ($update ? true : false);
    }


    /**
     * HELPER
     * Create Game Transaction Extension
     * @param  $[game_type] [<1=bet,2=win,3=refund>]
     * 
     */
    public function createGameTransExt($game_trans_id, $provider_trans_id, $round_id, $amount, $game_type, $provider_request, $mw_response, $mw_request, $client_response, $transaction_detail){
        $gametransactionext = array(
            "game_trans_id" => $game_trans_id,
            "provider_trans_id" => $provider_trans_id,
            "round_id" => $round_id,
            "amount" => $amount,
            "game_transaction_type"=>$game_type,
            "provider_request" => json_encode($provider_request),
            "mw_response" =>json_encode($mw_response),
            "mw_request"=>json_encode($mw_request),
            "client_response" =>json_encode($client_response),
            "transaction_detail" =>json_encode($transaction_detail),
        );
        $gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
        return $gametransactionext;

    }

}
