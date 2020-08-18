<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use App\Helpers\SkyWind;
use App\Helpers\GameLobby;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Client;
use Carbon\Carbon;
use DB;


/**
 * @author's note: ‘Transfer In’ and ‘Transfer Out’ use the same account for all games
 * @author's note: provider ticket will be player token on our side!
 *
 */
class SkyWindController extends Controller
{

    public $api_url, $seamless_key, $seamless_username, $seamless_password, $merchant_data, $merchant_password;
    public $provider_db_id; // ID ON OUR DATABASE

    public function __construct(){
        $this->provider_db_id = config('providerlinks.skywind.provider_db_id');
        $this->api_url = config('providerlinks.skywind.api_url');
        $this->seamless_key = config('providerlinks.skywind.seamless_key');
        $this->seamless_username = config('providerlinks.skywind.seamless_username');
        $this->seamless_password = config('providerlinks.skywind.seamless_password');
        $this->merchant_data = config('providerlinks.skywind.merchant_data');
        $this->merchant_password = config('providerlinks.skywind.merchant_password');
    }

    //  public function getAuth(){
    //      $http = new Client();
    //      $requesttosend = [
    //          "secretKey" =>"47138d18-6b46-4bd4-8ae1-482776ccb82d",
    //          "username" => "TGAMESU_USER",
    //          "password" => "Tgames1234"
    //      ];
    //      $response = $http->post('https://api.gcpstg.m27613.com/v1/login', [
    //         'form_params' => $requesttosend,
    //      ]);
    //     // $response = $response->getBody()->getContents();
    //     // Helper::saveLog('Skywind Game Launch', 21, $requesttosend, json_encode($response));
    //     $response = json_encode(json_decode($response->getBody()->getContents()));
    //     $url = json_decode($response, true);
    //     return $url;
    // }

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
      Helper::saveLog('Skywind Validate Ticket', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT HIT!');
      $raw_request = file_get_contents("php://input");
      parse_str($raw_request, $data);
      $token = $data['ticket'];
      $client_details = Providerhelper::getClientDetails('token',$token); // ticket
      $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
    	$response = [
    		"error_code" => 0,
    		"cust_session_id" => $client_details->player_token,
    		"cust_id" => $client_details->player_id,
    		"currency_code" => $client_details->default_currency,
    		"test_cust" => false,
    		// "country" => "GB", // Optional
    		// "game_group" => "Double Bets Group", // Optional
    		// "rci" => 60, // Optional
    		// "rce" => 11  // Optional
    	];
    	return $response;
    }

    /**
     * @author's note: provider ticket will be player token on our side!
     * @param  Request = [ticket,merch_id,merch_pwd,ip=optional]
     * @return [json array]
     * 
     */
    public  function getTicket(Request $request){
        Helper::saveLog('Skywind getTicket', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT HIT!');
        $client_details = Providerhelper::getClientDetails('token', $request->token); // ticket
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
        $response = [
            "error_code" => 0,
            "cust_session_id" => 'tst',
            "cust_id" => $client_details->player_id,
            "currency_code" => $client_details->default_currency,
            "test_cust" => false,
        ];
        return $response;
    }

    public  function getBalance(Request $request){
      Helper::saveLog('Skywind getBalance Ticket', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT HIT!');
      $raw_request = file_get_contents("php://input");
      parse_str($raw_request, $data);
      $cust_id = $data['cust_id'];
      $client_details = Providerhelper::getClientDetails('player_id', $cust_id);
      if($client_details == null){
           $response = [
              "error_code" => -2,
          ];
          return $response;
      }
      $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
      $response = [
          "error_code" => 0,
          "balance" => $player_details->playerdetailsresponse->balance,
          "currency_code" => $client_details->default_currency,
      ];
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
        return $data;

        $cust_id = $data['cust_id'];
        $amount = $data['amount'];
        $bet_amount = abs($data['amount']);
        $pay_amount =  abs($data['amount']);
        $income = $bet_amount - $pay_amount;
        $win_type = 0;
        $method = 1;
        $win_or_lost = 0; // 0 lost,  5 processing
        $payout_reason = 'TEST';
        $provider_trans_id = $data['trx_id'];
        $game_code = $data['game_code'];

        $client_details = Providerhelper::getClientDetails('player_id', $cust_id);
        if($client_details == null){  // details/player not found
            $response = ["error_code" => -2];
            return $response;
        }
        $game_information = Helper::findGameDetails('game_code', $this->provider_db_id, $game_code);
        if($game_information == null){  // game not found
            $response = [  "error_code" => 240];
            return $response;
        }
        $game_ext_check = ProviderHelper::findGameExt($provider_trans_id, 1, 'transaction_id');
        if($game_ext_check != 'false'){ // Duplicate transaction
            $response = ["error_code" => 1];
            return $response;
        }

        $game_transaction_type = 1; // 1 Bet, 2 Win
        $game_code = $game_information->game_id;
        $token_id = $client_details->token_id;

        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$client_details->client_access_token
            ]
        ]);
        $requesttosend = [
              "access_token" => $client_details->client_access_token,
              "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
              "type" => "fundtransferrequest",
              "datesent" => Helper::datesent(),
              "gamedetails" => [
                "gameid" => $game_information->game_code, // $game_information->game_code
                "gamename" => $game_information->game_name
              ],
              "fundtransferrequest" => [
                  "playerinfo" => [
                    "client_player_id" => $client_details->client_player_id,
                    "token" => $client_details->player_token,
                  ],
                  "fundinfo" => [
                          "gamesessionid" => "",
                          "transactiontype" => 'debit',
                          "transferid" => "",
                          "rollback" => false,
                          "currencycode" => $client_details->default_currency,
                          "amount" => $amount
                   ],
              ],
        ];
        $guzzle_response = $client->post($client_details->fund_transfer_url,
            ['body' => json_encode($requesttosend)]
        );
        $client_response = json_decode($guzzle_response->getBody()->getContents());
        $response = [
            "error_code" => 0,
            "balance" => $client_response->fundtransferresponse->balance,
            "trx_id" => $provider_trans_id,
        ];
        $gamerecord  = $this->createGameTransaction($token_id, $game_code, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $provider_trans_id);
        $game_transextension = $this->createGameTransExt($gamerecord,$provider_trans_id, $provider_trans_id, $pay_amount, $game_transaction_type, $request->all(), $response, $requesttosend, $client_response, $response);
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

        $cust_id = $data['cust_id'];
        $amount = $data['amount'];
        $trx_id = $data['trx_id'];
        $game_code = $data['game_code'];

        $client_details = Providerhelper::getClientDetails('player_id', $cust_id);
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
        $game_ext_check = ProviderHelper::findGameExt($trx_id, 1, 'transaction_id');
        if($game_ext_check == 'false'){ // Transaction not found
            $response = ["error_code" => -7];
            return $response;
        }

        $provider_trans_id = $trx_id;
        $roundid = $trx_id;
        $existing_bet = ProviderHelper::findGameTransaction($game_ext_check->game_trans_id, 'game_transaction'); // Find if win has bet record
        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$client_details->client_access_token
            ]
        ]);
        $requesttosend = [
              "access_token" => $client_details->client_access_token,
              "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
              "type" => "fundtransferrequest",
              "datesent" => Helper::datesent(),
              "gamedetails" => [
                "gameid" => $game_information->game_code, // $game_information->game_code
                "gamename" => $game_information->game_name
              ],
              "fundtransferrequest" => [
                  "playerinfo" => [
                    "client_player_id" => $client_details->client_player_id,
                    "token" => $client_details->player_token,
                  ],
                  "fundinfo" => [
                          "gamesessionid" => "",
                          "transactiontype" => 'credit',
                          "transferid" => "",
                          "rollback" => false,
                          "currencycode" => $client_details->default_currency,
                          "amount" => $amount
                   ],
              ],
        ];
        $guzzle_response = $client->post($client_details->fund_transfer_url,
            ['body' => json_encode($requesttosend)]
        );
        $client_response = json_decode($guzzle_response->getBody()->getContents());
        $response = [
            "error_code" => 0,
            "balance" => $client_response->fundtransferresponse->balance,
            "trx_id" => $trx_id,
        ];

        $win = 2;
        $entry_id = 2;
        $pay_amount = $amount;
        $income = $existing_bet->bet_amount - $pay_amount;
        $game_transaction_type = 2;
           
        $this->updateBetTransaction($trx_id, $amount, $income, $win, $entry_id);
        $game_transextension = $this->createGameTransExt($existing_bet->game_trans_id,$provider_trans_id, $roundid, $pay_amount, $game_transaction_type, $data, $response, $requesttosend, $client_response, $response);
        return $response;
    }


    /**
     * [gameCredit description]
     * @param  $[event_type] [<rollback>]
     * @param  $[trx_id] [<original bet id>]
     * 
     */
    public  function gameRollback(Request $request){
        Helper::saveLog('Skywind Rolback', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');
        // dd($this->seamless_key);
        $raw_request = file_get_contents("php://input");
        parse_str($raw_request, $data);
        // return $data;
        $cust_id = $data['cust_id'];
        $trx_id = $data['trx_id'];
        $game_code = $data['game_code'];
        $event_id = $data['event_id'];
        $event_type = $data['event_type'];

        $client_details = Providerhelper::getClientDetails('player_id', $cust_id);
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

        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$client_details->client_access_token
            ]
        ]);
        $requesttosend = [
              "access_token" => $client_details->client_access_token,
              "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
              "type" => "fundtransferrequest",
              "datesent" => Helper::datesent(),
              "gamedetails" => [
                "gameid" => $game_information->game_code, // $game_information->game_code
                "gamename" => $game_information->game_name
              ],
              "fundtransferrequest" => [
                  "playerinfo" => [
                    "client_player_id" => $client_details->client_player_id,
                    "token" => $client_details->player_token,
                  ],
                  "fundinfo" => [
                          "gamesessionid" => "",
                          "transactiontype" => 'credit',
                          "transferid" => "",
                          "rollback" => true,
                          "currencycode" => $client_details->default_currency,
                          "amount" => $amount
                   ],
              ],
        ];
        $guzzle_response = $client->post($client_details->fund_transfer_url,
            ['body' => json_encode($requesttosend)]
        );
        $client_response = json_decode($guzzle_response->getBody()->getContents());
        $response = [
            "error_code" => 0,
            "balance" => $client_response->fundtransferresponse->balance,
            "trx_id" => $trx_id,
        ];
           
        $this->updateBetTransaction($trx_id, $amount, $income, $win, $entry_id);
        $game_transextension = $this->createGameTransExt($existing_bet->game_trans_id,$provider_trans_id, $roundid, $pay_amount, $game_transaction_type, $data, $response, $requesttosend, $client_response, $response);
        return $response;        

    }


    /**
     * [gameCredit description]
     * @param  $[event_type] [<rollback>]
     * @param  $[trx_id] [<original bet id>]
     * 
     */
    public  function getFreeBet(Request $request){
        Helper::saveLog('Skywind Rolback', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');
        $raw_request = file_get_contents("php://input");
        parse_str($raw_request, $data);
        return $data;
  
        $merch_id = $data['merch_id'];
        $cust_id = $data['cust_id'];
        $merch_pwd = $data['merch_pwd'];
        $cust_session_id = $data['cust_session_id'];
        $game_code = $data['game_code'];
        $coin_multiplier = $data['coin_multiplier'];
        $coin_multiplier = $data['stake_all'];
    }

    /**
     * Create Game Extension Logs bet/Win/Refund
     * @param [int] $[gametransaction_id] [<ID of the game transaction>]
     * @param [json array] $[provider_request] [<Incoming Call>]
     * @param [json array] $[mw_request] [<Outgoing Call>]
     * @param [json array] $[mw_response] [<Incoming Response Call>]
     * @param [json array] $[client_response] [<Incoming Response Call>]
     * 
     */
    public  function createGameTransaction($token_id, $game_id, $bet_amount, $payout, $entry_id,  $win=0, $transaction_reason = null, $payout_reason = null , $income=null, $provider_trans_id=null, $round_id=1) {
        $data = [
                    "token_id" => $token_id,
                    "game_id" => $game_id,
                    "round_id" => $round_id,
                    "bet_amount" => $bet_amount,
                    "provider_trans_id" => $provider_trans_id,
                    "pay_amount" => $payout,
                    "income" => $income,
                    "entry_id" => $entry_id,
                    "win" => $win,
                    "transaction_reason" => $transaction_reason,
                    "payout_reason" => $payout_reason
                ];
        $data_saved = DB::table('game_transactions')->insertGetId($data);
        return $data_saved;
    }

    /**
     * Find bet and update to win 
     * @param [int] $[round_id] [<ID of the game transaction>]
     * @param [int] $[pay_amount] [<amount to change>]
     * @param [int] $[income] [<bet - payout>]
     * @param [int] $[win] [<0 Lost, 1 win, 3 draw, 4 refund, 5 processing>]
     * @param [int] $[entry_id] [<1 bet, 2 win>]
     * 
     */
    public  function updateBetTransaction($provider_trans_id, $pay_amount, $income, $win, $entry_id) {
        $update = DB::table('game_transactions')
                ->where('provider_trans_id', $provider_trans_id)
                ->update(['pay_amount' => $pay_amount, 
                      'income' => $income, 
                      'win' => $win, 
                      'entry_id' => $entry_id,
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
