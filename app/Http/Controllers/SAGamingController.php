<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use App\Helpers\SAHelper;
use App\Helpers\GameLobby;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;
use DB;

class SAGamingController extends Controller
{

    public $prefix = 'TG_SA';
	public $provider_db_id = 25;


	// public function gameLaunch(){
	// 	$http = new Client();
 //        $requesttosend = [
 //             "username" => config('providerlinks.sagaming.prefix'),
 //             "token" => $request->token,
 //             "lobby" => "A3107",
 //             "lang" => "Tgames1234", // optional
 //             "returnurl" => "Tgames1234", // optional
 //             "mobile" => "Tgames1234", // optional
 //             "options" => "Tgames1234"
 //        ];
 //        $response = $http->post('https://api.gcpstg.m27613.com/login', [
 //            'form_params' => $requesttosend,
 //        ]);
 //        $response = $response->getBody()->getContents();
 //        Helper::saveLog('Skywind Game Launch', 21, $requesttosend, json_encode($response));
 //        return $response;
	// }

    public function GetUserBalance(Request $request){
        $regUsr = SAHelper::regUser('TG_SG98');
        dd($regUsr);
        // $time = date('YmdHms'); //20140101123456
        // $querystring = [
        //     "method" => 'RegUserInfo',
        //     "Key" => config('providerlinks.sagaming.SecretKey'),
        //     "Time" => $time,
        //     "Username" => "TG_98",
        //     "CurrencyType" => "USD"
        // ];
        // $data = http_build_query($querystring); // QS
        // $encrpyted_data = SAHelper::encrypt($data);
        // $md5Signature = md5($data.config('providerlinks.sagaming.MD5Key').$time.config('providerlinks.sagaming.SecretKey'));
        // $http = new Client();
        // $response = $http->post('http://sai-api.sa-apisvr.com/api/api.aspx', [
        //     'form_params' => [
        //         'q' => $encrpyted_data, 
        //         's' => $md5Signature
        //     ],
        // ]);

        // $resp = simplexml_load_string($response->getBody()->getContents());
        // dd($resp->Username);

        // $body = $response->getBody();
        // echo $body;


        // 
        // return json_encode($regUsr);
        // $http = new Client();
        // $response = $http->post('http://sai-api.sa-apisvr.com/api/api.aspx', [
        //     'form_params' => [
        //         'q' => $regUsr['q'], 
        //         's' => $regUsr['s']
        //     ],
        // ]);

        // $client_response = json_decode($response->getBody()->getContents());
        // dd($client_response);
        // return date('YmdHms'); //yMdHms
       
    	// Helper::saveLog('SA Get Balance', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');

    	// $prefixed_username = explode("_SA", $request->username);
    	// $client_details = Providerhelper::getClientDetails('player_id', $prefixed_username[1]);
    	// $player_details = Providerhelper::playerDetailsCall($client_details->player_token);

    	// $response = [
    	// 	"username" => $this->prefix.$client_details->player_id,
    	// 	"currency" => $client_details->default_currency,
    	// 	"amount" => $player_details->playerdetailsresponse->balance,
    	// 	"error" => 0,
    	// ];

    	// return $response;
    }

    public function PlaceBet(){
    	Helper::saveLog('SA Place Bet', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
        // $data = json_decode(file_get_contents("php://input"));
        $enc_body = file_get_contents("php://input");
        parse_str($enc_body, $data);

        $username = $data['username'];
        $playersid = explode('_', $username);
        $currency = $data['currency'];
        $amount = $data['amount'];
        $txnid = $data['txnid'];
        $ip = $data['ip'];
        $gametype = $data['gametype'];
        $game_id = $data['gameid'];
        $betdetails = $data['betdetails'];

        $client_details = ProviderHelper::getClientDetails('player_id',$playersid[1]);
        if($client_details == null){
            $data_response = ["username" => $username,"currency" => $currency, "error" => 1000];
            return $data_response;
        }
        $getPlayer = ProviderHelper::playerDetailsCall($client_details->player_token);
        if($getPlayer == 'false'){
            $data_response = ["username" => $username,"currency" => $currency, "error" => 9999];  
            return $data_response;
        }
        $game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $game_id);
        if($game_details == null){
            $data_response = ["username" => $username,"currency" => $currency, "error" => 134];  
            return $data_response;
        }
        $provider_reg_currency = ProviderHelper::getProviderCurrency($this->provider_db_id, $client_details->default_currency);
        $data_response = ["username" => $username,"currency" => $currency, "error" => 1001];
        if($provider_reg_currency == 'false'){ // currency not in the provider currency agreement
            return $data_response;
        }else{
            if($currency != $provider_reg_currency){
                return $data_response;
            }
        }

            $transaction_check = ProviderHelper::findGameExt($txnid, 1,'transaction_id');
            if($transaction_check != 'false'){
                $data_response = ["username" => $username,"currency" => $currency, "error" => 122];
                return $data_response;
            }

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
                    "gameid" => $game_details->game_code, // $game_details->game_code
                    "gamename" => $game_details->game_name
                  ],
                  "fundtransferrequest" => [
                      "playerinfo" => [
                        "client_player_id" => $client_details->client_player_id,
                        "token" => $client_details->player_token,
                      ],
                      "fundinfo" => [
                              "gamesessionid" => "",
                              "transactiontype" => "debit",
                              "transferid" => "",
                              "rollback" => false,
                              "currency" => $client_details->default_currency,
                              "amount" => abs($amount)
                       ],
                  ],
            ];

            return $requesttosend;
            $guzzle_response = $client->post($client_details->fund_transfer_url,
                ['body' => json_encode($requesttosend)]
            );
            $client_response = json_decode($guzzle_response->getBody()->getContents());

            // TEST
            $transaction_type = 'debit';
            $game_transaction_type = 1; // 1 Bet, 2 Win
            $game_code = $game_details->game_id;
            $token_id = $client_details->token_id;
            $bet_amount = $amount; 
            $pay_amount = $amount;
            $income = $amount;
            $win_type = 0;
            $method = 1;
            $win_or_lost = $win_type; // 0 lost,  5 processing
            $payout_reason = 'Bet';
            $provider_trans_id = $transaction_uuid;
            // TEST

            $data_response = [
                "username" => $username,
                "currency" => $client_details->default_currency,
                "amount" => $client_response->fundtransferresponse->balance,
                "error" => 0
            ];

            $gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_code, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $provider_trans_id);
            $game_transextension = ProviderHelper::createGameTransExt($gamerecord,$provider_trans_id, $provider_trans_id, $pay_amount, $game_transaction_type, $data, $data_response, $requesttosend, $client_response, $data_response);

            return response($data_response,200)->header('Content-Type', 'application/json');
    }

    public function PlayerWin(){
    	Helper::saveLog('SA Player Win', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
    }

    public function PlayerLost(){
    	Helper::saveLog('SA Player Lost', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
    }

    public function PlaceBetCancel(){
    	Helper::saveLog('SA Place Bet Cancel', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT HIT');
    }

}
