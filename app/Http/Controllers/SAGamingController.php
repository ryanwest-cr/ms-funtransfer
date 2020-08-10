<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
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

    public $game_db_id = 1;
    public $game_db_code = 'SAGAMING';

    public function __construct(){
        header('Content-type: text/xml');
    }

    // public function debugme(Request $request){
    //     $user_id = Providerhelper::explodeUsername(config('providerlinks.sagaming.prefix'), $request->username);
    //     $client_details = Providerhelper::getClientDetails('player_id', $user_id);
    //     $time = date('YmdHms'); //20140101123456
    //     $method = $request->method;
    //     $querystring = [
    //         "method" => $method,
    //         "Key" => config('providerlinks.sagaming.SecretKey'),
    //         "Time" => $time,
    //         "Username" => config('providerlinks.sagaming.prefix').$client_details->player_id,
    //     ];
    //     $method == 'RegUserInfo' || $method == 'LoginRequest' ? $querystring['CurrencyType'] = $client_details->default_currency : '';
    //     $data = http_build_query($querystring); // QS
    //     $encrpyted_data = SAHelper::encrypt($data);
    //     $md5Signature = md5($data.config('providerlinks.sagaming.MD5Key').$time.config('providerlinks.sagaming.SecretKey'));
    //     $http = new Client();
    //     $response = $http->post(config('providerlinks.sagaming.API_URL'), [
    //         'form_params' => [
    //             'q' => $encrpyted_data, 
    //             's' => $md5Signature
    //         ],
    //     ]);
    //     $resp = simplexml_load_string($response->getBody()->getContents());
    //     dd($resp);
    // }

    // XML BUILD RECURSIVE FUNCTION
    public function siteMap()
    {
        $test_array = array (
            'bla' => 'blub',
            'foo' => 'bar',
            'another_array' => array (
                'stack' => 'overflow',
            ),
        );

        $xml_template_info = new \SimpleXMLElement("<?xml version=\"1.0\"?><template></template>");

        $this->array_to_xml($test_array,$xml_template_info);
        $xml_template_info->asXML(dirname(__FILE__)."/sitemap.xml") ;
        header('Content-type: text/xml');
        dd(readfile(dirname(__FILE__)."/sitemap.xml"));
    }

    public function array_to_xml(array $arr, \SimpleXMLElement $xml)
    {
      foreach ($arr as $k => $v) {
          is_array($v)
              ? $this->array_to_xml($v, $xml->addChild($k))
              : $xml->addChild($k, $v);
      }
      return $xml;
    }
   
    public function makeArrayXML($array){
        $xml_data = new \SimpleXMLElement('<?xml version="1.0"?><RequestResponse></RequestResponse>');
        $xml_file = $this->array_to_xml($array, $xml_data);
        return $xml_file->asXML();
    }

    public function GetUserBalance(Request $request){
        $enc_body = file_get_contents("php://input");
        $url_decoded = urldecode($enc_body);
        $decrypt_data = SAHelper::decrypt($url_decoded);
        parse_str($decrypt_data, $data);
        Helper::saveLog('SA Gaming Balance', config('providerlinks.sagaming.pdbid'), json_encode($data), $decrypt_data);

    	$user_id = Providerhelper::explodeUsername(config('providerlinks.sagaming.prefix'), $data['username']);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	$player_details = Providerhelper::playerDetailsCall($client_details->player_token);

    	$data_response = [
    		"username" => config('providerlinks.sagaming.prefix').$client_details->player_id,
    		"currency" => $client_details->default_currency,
    		"amount" => $player_details->playerdetailsresponse->balance,
    		"error" => 0,
    	];

        echo $this->makeArrayXML($data_response);
        return;
    }



    public function PlaceBet(){
        $enc_body = file_get_contents("php://input");
        $url_decoded = urldecode($enc_body);
        $decrypt_data = SAHelper::decrypt($url_decoded);
        parse_str($decrypt_data, $data);
        Helper::saveLog('SA Gaming Bet', config('providerlinks.sagaming.pdbid'), json_encode($data), $decrypt_data);

        // LOCAL TEST
        // $enc_body = file_get_contents("php://input");
        // parse_str($enc_body, $data);

        $username = $data['username'];
        $playersid = Providerhelper::explodeUsername(config('providerlinks.sagaming.prefix'), $username);
        $currency = $data['currency'];
        $amount = $data['amount'];
        $txnid = $data['txnid'];
        // $ip = $data['ip'];
        $gametype = $data['gametype'];
        // $game_id = $data['gameid'];
        $game_id = $this->game_db_code;
        // $betdetails = $data['betdetails'];
        $round_id = $data['gameid'];

        $client_details = ProviderHelper::getClientDetails('player_id',$playersid);
        if($client_details == null){
            $data_response = ["username" => $username,"currency" => $currency, "error" => 1005]; // 1000
            echo $this->makeArrayXML($data_response);
            return;
        }
        $getPlayer = ProviderHelper::playerDetailsCall($client_details->player_token);
        if($getPlayer == 'false'){
            $data_response = ["username" => $username,"currency" => $currency, "error" => 1005];  // 9999
            echo $this->makeArrayXML($data_response);
            return;
        }
        if($getPlayer->playerdetailsresponse->balance < $amount){
            $data_response = ["username" => $username,"currency" => $currency, "amount" => $getPlayer->playerdetailsresponse->balance, "error" => 1004];  // Low Balance1
            echo $this->makeArrayXML($data_response);
            return;
        }
        $game_details = Helper::findGameDetails('game_code', config('providerlinks.sagaming.pdbid'), $game_id);
        if($game_details == null){
            $data_response = ["username" => $username,"currency" => $currency, "error" => 1005]; // 134 
            echo $this->makeArrayXML($data_response);
            return;
        }
        $provider_reg_currency = ProviderHelper::getProviderCurrency(config('providerlinks.sagaming.pdbid'), $client_details->default_currency);
        $data_response = ["username" => $username,"currency" => $currency, "error" => 1005]; // 1001
        if($provider_reg_currency == 'false'){ // currency not in the provider currency agreement
            echo $this->makeArrayXML($data_response);
            return;
        }else{
            if($currency != $provider_reg_currency){
                echo $this->makeArrayXML($data_response);
                return;
            }
        }


            $transaction_check = ProviderHelper::findGameExt($txnid, 1,'transaction_id');
            if($transaction_check != 'false'){
                $data_response = ["username" => $username,"currency" => $currency, "amount" => $getPlayer->playerdetailsresponse->balance, "error" => 0]; // 122 // transaction not found!
                echo $this->makeArrayXML($data_response);
                return;
            }

            try {
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
                                  "currencycode" => $client_details->default_currency,
                                  "amount" => abs($amount)
                           ],
                      ],
                ];

                $guzzle_response = $client->post($client_details->fund_transfer_url,
                    ['body' => json_encode($requesttosend)]
                );
                $client_response = json_decode($guzzle_response->getBody()->getContents());

                $transaction_type = 'debit';
                $game_transaction_type = 1; // 1 Bet, 2 Win
                $game_code = $game_details->game_id;
                $token_id = $client_details->token_id;
                $bet_amount = $amount; 
                $pay_amount = 0;
                $income = 0;
                $win_type = 0;
                $method = 1;
                $win_or_lost = 5; // 0 lost,  5 processing
                $payout_reason = 'Bet';
                $provider_trans_id = $txnid;
                $round_id = $round_id;

                $data_response = [
                    "username" => $username,
                    "currency" => $client_details->default_currency,
                    "amount" => $client_response->fundtransferresponse->balance,
                    "error" => 0
                ];

                $game_trans_ext = ProviderHelper::findGameExt($round_id, 1, 'round_id');
                if($game_trans_ext == 'false'){
                    $gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_code, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
                    $game_transextension = ProviderHelper::createGameTransExt($gamerecord,$provider_trans_id, $round_id, $bet_amount, $game_transaction_type, $data, $data_response, $requesttosend, $client_response, $data_response);
                }else{
                    $game_transaction = ProviderHelper::findGameTransaction($game_trans_ext->game_trans_id,'game_transaction');
                    $bet_amount = $game_transaction->bet_amount + $amount;
                    $this->updateBetTransaction($round_id, $game_transaction->pay_amount, $bet_amount, $game_transaction->income, 5, $game_transaction->entry_id);
                    $game_transextension = ProviderHelper::createGameTransExt($game_trans_ext->game_trans_id,$provider_trans_id, $round_id, $amount, $game_transaction_type, $data, $data_response, $requesttosend, $client_response, $data_response);
                }
                Helper::saveLog('SA Gaming Bet', config('providerlinks.sagaming.pdbid'), json_encode($data), $data_response);
                echo $this->makeArrayXML($data_response);
                return;
            } catch (\Exception $e) {
                $data_response = [
                    "username" => $username,
                    // "currency" => $client_details->default_currency,
                    // "amount" => $client_response->fundtransferresponse->balance,
                    // "amount" => $getPlayer->playerdetailsresponse->balance,
                    "error" => 1005 // 9999
                ];
                Helper::saveLog('SA Gaming PB $e', config('providerlinks.sagaming.pdbid'), json_encode($data), $e->getMessage());
                echo $this->makeArrayXML($data_response);
                return;
            }
    }

    public function PlayerWin(){
        $enc_body = file_get_contents("php://input");
        $url_decoded = urldecode($enc_body);
        $decrypt_data = SAHelper::decrypt($url_decoded);
        parse_str($decrypt_data, $data);
        // Helper::saveLog('SA Gaming Win', config('providerlinks.sagaming.pdbid'), json_encode($data), $enc_body);
        Helper::saveLog('SA Gaming Win', config('providerlinks.sagaming.pdbid'), json_encode($data), $decrypt_data);

        // LOCAL TEST
        // $enc_body = file_get_contents("php://input");
        // parse_str($enc_body, $data); 

        $username = $data['username'];
        $playersid = Providerhelper::explodeUsername(config('providerlinks.sagaming.prefix'), $username);
        $currency = $data['currency'];
        $amount = $data['amount'];
        $txnid = $data['txnid'];
        $gametype = $data['gametype'];
        $game_id = $this->game_db_code;
        $round_id = $data['gameid'];

        $client_details = ProviderHelper::getClientDetails('player_id',$playersid);
        if($client_details == null){
            $data_response = ["username" => $username, "error" => 1005]; // 1000
            echo $this->makeArrayXML($data_response);
            return;
        }
        $getPlayer = ProviderHelper::playerDetailsCall($client_details->player_token); 
        if($getPlayer == 'false'){
            $data_response = ["username" => $username, "error" => 1005];  // 9999
            echo $this->makeArrayXML($data_response);
            return;
        }
        $game_details = Helper::findGameDetails('game_code', config('providerlinks.sagaming.pdbid'), $game_id);
        if($game_details == null){
            $data_response = ["username" => $username,"currency" => $currency, "error" => 1005];  // 134
            echo $this->makeArrayXML($data_response);
            return;
        }
        $provider_reg_currency = ProviderHelper::getProviderCurrency(config('providerlinks.sagaming.pdbid'), $client_details->default_currency);
        $data_response = ["username" => $username,"currency" => $currency, "error" => 1005]; // 1001
        if($provider_reg_currency == 'false'){ // currency not in the provider currency agreement
            echo $this->makeArrayXML($data_response);
            return;
        }else{
            if($currency != $provider_reg_currency){
                echo $this->makeArrayXML($data_response);
                return;
            }
        }

            $check_win_entry = ProviderHelper::findGameExt($round_id, 2,'round_id');
            if($check_win_entry != 'false'){
                $data_response = ["username" => $username,"currency" => $currency, "amount" => $getPlayer->playerdetailsresponse->balance, "error" => 0]; //122 win already exist!
                echo $this->makeArrayXML($data_response);
                return;
            }

            $transaction_check = ProviderHelper::findGameExt($round_id, 1,'round_id');
            if($transaction_check == 'false'){
                $data_response = ["username" => $username,"currency" => $currency, "amount" => $getPlayer->playerdetailsresponse->balance, "error" => 0]; //152
                echo $this->makeArrayXML($data_response);
                return;
            }


            try {
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
                                  "transactiontype" => "credit",
                                  "transferid" => "",
                                  "rollback" => false,
                                  "currencycode" => $client_details->default_currency,
                                  "amount" => abs($amount)
                           ],
                      ],
                ];

                $guzzle_response = $client->post($client_details->fund_transfer_url,
                    ['body' => json_encode($requesttosend)]
                );
                $client_response = json_decode($guzzle_response->getBody()->getContents());

                $game_trans = ProviderHelper::findGameTransaction($transaction_check->game_trans_id, 'game_transaction');

                $transaction_type = 'credit';
                $game_transaction_type = 2; // 1 Bet, 2 Win
                $game_code = $game_details->game_id;
                $token_id = $client_details->token_id;
                $bet_amount = $game_trans->bet_amount; 
                $pay_amount = $amount;
                $income = $bet_amount - $pay_amount;
                $win_type = 0;
                $method = 1;
                $win_or_lost = 1; // 0 lost,  5 processing
                $payout_reason = 'Win';
                $provider_trans_id = $txnid;

                $data_response = [
                    "username" => $username,
                    "currency" => $client_details->default_currency,
                    "amount" => $client_response->fundtransferresponse->balance,
                    "error" => 0
                ];

                ProviderHelper::updateBetTransaction($round_id, $pay_amount, $income, 1, 2);
                // $gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_code, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $provider_trans_id);
                $game_transextension = ProviderHelper::createGameTransExt($game_trans->game_trans_id,$provider_trans_id, $round_id, $pay_amount, $game_transaction_type, $data, $data_response, $requesttosend, $client_response, $data_response);
                Helper::saveLog('SA Gaming Win', config('providerlinks.sagaming.pdbid'), json_encode($data), $data_response);
                echo $this->makeArrayXML($data_response);
                return;
            } catch (\Exception $e) {
                $data_response = [
                    "username" => $username,
                    // "currency" => $client_details->default_currency,
                    // "amount" => $client_response->fundtransferresponse->balance,
                    // "amount" => $getPlayer->playerdetailsresponse->balance,
                    "error" => 1005 // 9999
                ];
                Helper::saveLog('SA Gaming WIN $e', config('providerlinks.sagaming.pdbid'), json_encode($data), $e->getMessage());
                echo $this->makeArrayXML($data_response);
                return;
            }
    }

    public function PlayerLost(){
        $enc_body = file_get_contents("php://input");
        $url_decoded = urldecode($enc_body);
        $decrypt_data = SAHelper::decrypt($url_decoded);
        parse_str($decrypt_data, $data);
        // Helper::saveLog('SA Gaming Lost', config('providerlinks.sagaming.pdbid'), json_encode($data), $enc_body);
        Helper::saveLog('SA Gaming Lost', config('providerlinks.sagaming.pdbid'), json_encode($data), $decrypt_data);
        // LOCAL TEST
        // $enc_body = file_get_contents("php://input");
        // parse_str($enc_body, $data);
            
        try {
         
            $username = $data['username'];
            $playersid = Providerhelper::explodeUsername(config('providerlinks.sagaming.prefix'), $username);
            $currency = $data['currency'];
            $txnid = $data['txnid'];
            $gametype = $data['gametype'];
            $game_id = $this->game_db_code;
            $round_id = $data['gameid'];

            $client_details = ProviderHelper::getClientDetails('player_id',$playersid);
            if($client_details == null){
                $data_response = ["username" => $username,"error" => 1005]; // 1000
                Helper::saveLog('SA Gaming LC Client Error', config('providerlinks.sagaming.pdbid'), json_encode($data), $data_response);
                echo $this->makeArrayXML($data_response);
                return;
            }
            $game_details = Helper::findGameDetails('game_code', config('providerlinks.sagaming.pdbid'), $game_id);
            if($game_details == null){
                $data_response = ["username" => $username, "error" => 1005];  // 134
                Helper::saveLog('SA Gaming LC Game Error', config('providerlinks.sagaming.pdbid'), json_encode($data), $data_response);
                echo $this->makeArrayXML($data_response);
                return;
            }
            $getPlayer = ProviderHelper::playerDetailsCall($client_details->player_token);
            if($getPlayer == 'false'){
                $data_response = ["username" => $username, "error" => 1005]; 
                Helper::saveLog('SA Gaming LC Player Not Found', config('providerlinks.sagaming.pdbid'), json_encode($data), $data_response);
                echo $this->makeArrayXML($data_response);
                return;
            }
            $transaction_check = ProviderHelper::findGameExt($round_id, 1,'round_id');
            if($transaction_check == 'false'){
                $data_response = ["username" => $username,"currency" => $client_details->default_currency, "amount" => $getPlayer->playerdetailsresponse->balance, "error" => 0];
                 Helper::saveLog('SA Gaming LC Round Not Found', config('providerlinks.sagaming.pdbid'), json_encode($data), $data_response);
                echo $this->makeArrayXML($data_response);
                return;
            }
            $game_trans = ProviderHelper::findGameTransaction($transaction_check->game_trans_id, 'game_transaction');

            $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
            $data_response = [
                "username" => $username,
                "currency" => $client_details->default_currency,
                "amount" => $player_details->playerdetailsresponse->balance,
                "error" => 0
            ];

            $game_trans_ext = ProviderHelper::findGameExt($round_id, 1, 'round_id');
            $game_transaction = ProviderHelper::findGameTransaction($game_trans_ext->game_trans_id,'game_transaction');
            ProviderHelper::updateBetTransaction($round_id, $game_transaction->pay_amount, $game_transaction->bet_amount, 0, $game_transaction->entry_id);
            Helper::saveLog('SA Gaming Bet Lost', config('providerlinks.sagaming.pdbid'), json_encode($data), $data_response);
            echo $this->makeArrayXML($data_response);
            return;

        } catch (\Exception $e) {
            $data_response = [
                "username" => $username,
                "error" => 1005 // 9999
            ];
            Helper::saveLog('SA Gaming LC $e', config('providerlinks.sagaming.pdbid'), json_encode($decrypt_data), $e->getMessage());
            echo $this->makeArrayXML($data_response);
            return;
        }
        
        
    }

    public function PlaceBetCancel(){
        $enc_body = file_get_contents("php://input");
        $url_decoded = urldecode($enc_body);
        $decrypt_data = SAHelper::decrypt($url_decoded);
        parse_str($decrypt_data, $data);
        // Helper::saveLog('SA Gaming BC', config('providerlinks.sagaming.pdbid'), json_encode($data), $enc_body);
        Helper::saveLog('SA Gaming BC', config('providerlinks.sagaming.pdbid'), json_encode($data), $decrypt_data);
     
        // LOCAL TEST
        // $enc_body = file_get_contents("php://input");
        // parse_str($enc_body, $data);

        $username = $data['username'];
        $playersid = Providerhelper::explodeUsername(config('providerlinks.sagaming.prefix'), $username);
        $currency = $data['currency'];
        $amount = $data['amount'];
        $txnid = $data['txnid'];
        $gametype = $data['gametype'];
        $gamecancel = $data['gamecancel'];
        $txn_reverse_id = $data['txn_reverse_id'];
        $game_id = $this->game_db_code;
        $round_id = $data['gameid'];

        $client_details = ProviderHelper::getClientDetails('player_id',$playersid);
        if($client_details == null){
            $data_response = ["username" => $username, "error" => 1005]; // 1000
            echo $this->makeArrayXML($data_response);
            return;
        }
        $game_details = Helper::findGameDetails('game_code', config('providerlinks.sagaming.pdbid'), $game_id);
        if($game_details == null){
            $data_response = ["username" => $username,"currency" => $currency, "error" => 1005];  // 134
            echo $this->makeArrayXML($data_response);
            return;
        }
        $transaction_check = ProviderHelper::findGameExt($round_id, 1,'round_id');
        if($transaction_check == 'false'){
            $data_response = ["username" => $username,"currency" => $client_details->default_currency,"amount" => $getPlayer->playerdetailsresponse->balance, "error" => 1005]; // 152
            echo $this->makeArrayXML($data_response);
            return;
        }

        $existing_refund_call = $this->GameTransactionExt($txnid, $round_id, 3);
        if($existing_refund_call != null){
            $data_response = ["username" => $username,"currency" => $client_details->default_currency,"amount" => $getPlayer->playerdetailsresponse->balance, "error" => 1005]; // 122
            echo $this->makeArrayXML($data_response);
            return;
        }

        $game_trans = ProviderHelper::findGameTransaction($transaction_check->game_trans_id, 'game_transaction');
        $client = new Client([
                'headers' => [ 
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$client_details->client_access_token
                ]
        ]);
        try {
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
                              "transactiontype" => "credit",
                              "transferid" => "",
                              "rollback" => true,
                              "currencycode" => $client_details->default_currency,
                              "amount" => abs($amount)
                       ],
                  ],
            ];
            $guzzle_response = $client->post($client_details->fund_transfer_url,
                ['body' => json_encode($requesttosend)]
            );
            $client_response = json_decode($guzzle_response->getBody()->getContents());
            $data_response = [
                "username" => $username,
                "currency" => $client_details->default_currency,
                "amount" => $client_response->fundtransferresponse->balance,
                "error" => 0
            ];

            $game_trans_ext = ProviderHelper::findGameExt($round_id, 1, 'round_id');
            $game_transaction = ProviderHelper::findGameTransaction($game_trans_ext->game_trans_id,'game_transaction');

            $all_bet = $this->getAllBet($round_id, 1);
            if(count($all_bet) > 0){ // MY BETS!
                if(count($all_bet) == 1){
                    ProviderHelper::updateBetTransaction($round_id, $game_transaction->pay_amount, $game_transaction->bet_amount, 4, $game_transaction->entry_id);
                }else{
                    $sum = 0;
                    foreach($all_bet as $key=>$value){
                      if(isset($value->amount))
                         $sum += $value->amount;
                    }
                    $bet_amount = $sum - $amount;
                    $this->updateBetTransaction($round_id, $game_transaction->pay_amount, $bet_amount, $game_transaction->income, $game_transaction->win, $game_transaction->entry_id);
                }
            }

            ProviderHelper::createGameTransExt($game_trans->game_trans_id,$txnid, $round_id, $amount, 3, $data, $data_response, $requesttosend, $client_response, $data_response);
            Helper::saveLog('SA Gaming Cancel Bet', config('providerlinks.sagaming.pdbid'), json_encode($data), $data_response);
            echo $this->makeArrayXML($data_response);
            return;
        } catch (\Exception $e) {
            $data_response = [
                "username" => $username,
                // "currency" => $client_details->default_currency,
                // "amount" => $client_response->fundtransferresponse->balance,
                "error" => 1005
            ];
            Helper::saveLog('SA Gaming CB $e', config('providerlinks.sagaming.pdbid'), json_encode($data), $e->getMessage());
            echo $this->makeArrayXML($data_response);
            return;
        }

        

    }


    public function GameTransactionExt($provider_trans_id, $round_id, $type){
        $game_ext = DB::table('game_transaction_ext as gte')
                    ->where('gte.provider_trans_id', $provider_trans_id)
                    ->where('gte.round_id', $round_id)
                    ->where('gte.game_transaction_type', $type)
                    ->first();
        return $game_ext;
    }


    public function updateBetTransaction($round_id, $pay_amount, $bet_amount, $income, $win, $entry_id){
        DB::table('game_transactions')
            ->where('round_id', $round_id)
            ->update(['pay_amount' => $pay_amount, 
                  'bet_amount' => $bet_amount, 
                  'income' => $income, 
                  'win' => $win, 
                  'entry_id' => $entry_id,
                  'transaction_reason' => ProviderHelper::updateReason($win),
            ]);
    }

    public function getAllBet($round_id, $gtt){
        $game_ext = DB::table('game_transaction_ext as gte')
                    // ->select(DB::raw('SUM(gte.amount) as total_bet'))
                    ->select('gte.amount')
                    ->where('gte.round_id', $round_id)
                    ->where('gte.game_transaction_type', $gtt)
                    ->get();
        return $game_ext;

    }
}
