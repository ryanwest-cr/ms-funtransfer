<?php

namespace App\Http\Controllers;

use App\Helpers\ClientRequestHelper;
use Illuminate\Http\Request;
use App\Helpers\ProviderHelper;
use DB;
use App\Helpers\Helper;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

class GoldenFController extends Controller
{

    public $provider_id;
    public function __construct(){
        $this->provider_id = config("providerlinks.goldenF.provider_id");
    }

    public function GetPlayerBalance(Request $request)
    {
        Helper::saveLog("GoldenF GetPlayerBalance req", $this->provider_id, json_encode($request->all()), "");
        $client_details = ProviderHelper::getClientDetails('token',$request->operator_token);
        if($client_details == null){ 
            $response = array(
                "code" => 3004,
                "msg" => "Player doesn't exist"
            );
            return $response;
            Helper::saveLog("GoldenF GetPlayerBalance auth err", $this->provider_id, $request->all(), $response);
        }
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);  
        $balance = floatval(number_format($player_details->playerdetailsresponse->balance, 2, '.', ''));
        // if($request->wallet_code == 'gf_main_balance'):
            $response = array(
                "data" => [
                    "player_name" => $client_details->display_name,
                    "currency" => $client_details->default_currency,
                    "balance" => $balance,
                ],
                "error" => [
                    "code" => "",
                    "message" => ""
                ]
            );
            Helper::saveLog("GoldenF GetPlayerBalance resonpse", $this->provider_id, json_encode($request->all()), $response);
            return $response;
        // else:
        //     $response = array(
        //         "error" => [
        //             "code" => "9423",
        //             "message" => "Operation Failed - Incorrect Wallet"
        //         ]
        //     );
        // endif;

    }

    public function TransferIn(Request $request)
    {
        Helper::saveLog("GoldenF TransferIn req", $this->provider_id, json_encode($request->all()), "");
        $client_details = ProviderHelper::getClientDetails('token',$request->operator_token);
        if($client_details == null){ 
            $response = array(
                "code" => 3004,
                "msg" => "Player doesn't exist"
            );
            return $response;
            Helper::saveLog("GoldenF TransferIn auth err", $this->provider_id, $request->all(), $response);
        }
        $game_details = Helper::getInfoPlayerGameRound($request->operator_token);
        $token = $request->operator_token;
        $bet_amount = $request->amount;
        $payout = 0;
        $entry_id = 1;
        $income = $bet_amount;
        $provider_trans_id = $request->traceId;
        $round_id = $request->traceId;
        $game_code = $game_details->game_code;
        $game_name = $game_details->game_name;
        
        $trans_exist = DB::table('game_transactions')->where('provider_trans_id','=',$provider_trans_id)->where('round_id','=',$round_id)->get();

        try {

            $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
            $balance = floatval(number_format($player_details->playerdetailsresponse->balance, 2, '.', ''));
            if(count($trans_exist) >0 ):
                $response = array(
                    "data" => [
                        "player_name" => $client_details->display_name,
                        "currency" => $client_details->default_currency,
                        "balance" => $balance,
                        "balance_main" => $balance,
                        "traceId" => $trans_exist[0]->game_trans_id,
                        "wallet_code" => "gf_main_balance"
                    ],
                    "error" => [
                        "code" => "",
                        "message" => ""
                    ]
                );
                Helper::saveLog("GoldenF TransferIn duplicate", $this->provider_id, json_encode($request->all()), $response);
                return $response;
            endif;
            
            $client_response = $this->fundTransferRequest(
                $client_details->client_access_token,
                $client_details->client_api_key, 
                $game_code, 
                $game_name, 
                $client_details->client_player_id, 
                $client_details->player_token, 
                $bet_amount,
                $client_details->fund_transfer_url, 
                "debit",
                $client_details->default_currency, 
                false
            ); 

            $gamerecord = $this->createGameTransaction($token, $game_code, $bet_amount, $payout, $entry_id, 0, null, null, $income, $provider_trans_id, $round_id);
            
            $response = array(
                "data" => [
                    "player_name" => $client_details->display_name,
                    "currency" => $client_details->default_currency,
                    "balance" => $balance,
                    "balance_main" => $balance,
                    "traceId" => $gamerecord,
                    "wallet_code" => "gf_main_balance"
                ],
                "error" => [
                    "code" => "",
                    "message" => ""
                    ]
            );
            $game_trans_ext = ProviderHelper::createGameTransExtV2($gamerecord, $provider_trans_id, $round_id, $bet_amount, $entry_id, json_encode($request->all()), $response, $client_response['requesttosend'], $client_response['client_response']);
            
            Helper::saveLog("GoldenF TransferIn resonpse", $this->provider_id, json_encode($request->all()), $response);
            return $response;
                
        } catch (\Exception $e) {
            $msg = array(
                'error' => '1',
                'message' => $e->getMessage(),
            );
            Helper::saveLog('GoldenF TransferIn resonpse error', $this->provider_id, json_encode($request->all(),JSON_FORCE_OBJECT), $msg);
            return json_encode($msg, JSON_FORCE_OBJECT); 
        }
    }


    public function TransferOut(Request $request)
    {
        Helper::saveLog("GoldenF TransferOut req", $this->provider_id, json_encode($request->all()), "");
        $client_details = ProviderHelper::getClientDetails('token',$request->operator_token);
        if($client_details == null){ 
            $response = array(
                "code" => 3004,
                "msg" => "Player doesn't exist"
            );
            return $response;
            Helper::saveLog("GoldenF TransferOut auth err", $this->provider_id, $request->all(), $response);
        }
        $game_details = Helper::getInfoPlayerGameRound($request->operator_token);
        $token = $request->operator_token;
        $bet_amount = $request->amount;
        $payout = 0;
        $entry_id = 1;
        $income = $bet_amount;
        $provider_trans_id = $request->traceId;
        $round_id = $request->traceId;
        $game_code = $game_details->game_code;
        $game_name = $game_details->game_name;
        
        $trans_exist = DB::table('game_transactions')->where('provider_trans_id','=',$provider_trans_id)->where('round_id','=',$round_id)->get();

        try {

            $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
            $balance = floatval(number_format($player_details->playerdetailsresponse->balance, 2, '.', ''));
            // if(count($trans_exist) >0 ):
            //     $response = array(
            //         "data" => [
            //             "player_name" => $client_details->display_name,
            //             "currency" => $client_details->default_currency,
            //             "balance" => $balance,
            //             "balance_main" => $balance,
            //             "traceId" => $trans_exist[0]->game_trans_id,
            //             "wallet_code" => "gf_main_balance"
            //         ],
            //         "error" => [
            //             "code" => "",
            //             "message" => ""
            //         ]
            //     );
            //     Helper::saveLog("GoldenF TransferOut duplicate", $this->provider_id, json_encode($request->all()), $response);
            //     return $response;
            // endif;
            
            $client_response = $this->fundTransferRequest(
                $client_details->client_access_token,
                $client_details->client_api_key, 
                $game_code, 
                $game_name, 
                $client_details->client_player_id, 
                $client_details->player_token, 
                $bet_amount,
                $client_details->fund_transfer_url, 
                "credit",
                $client_details->default_currency, 
                false
            ); 

            $income = $trans_exist[0]->bet_amount - $bet_amount;
            $entry_id = $bet_amount > 0 ? 2 : 1;
            $win = $bet_amount > 0 ? 1 : 0;

            $update = DB::table('game_transactions')
                        ->where('game_trans_id','=',$trans_exist[0]->game_trans_id)
                        ->update(["win" => $win, "pay_amount" => $bet_amount, "entry_id" => $entry_id, "income" => $income]);
            
            $response = array(
                "data" => [
                    "player_name" => $client_details->display_name,
                    "currency" => $client_details->default_currency,
                    "balance" => $balance,
                    "balance_main" => $balance,
                    "traceId" => $trans_exist[0]->game_trans_id,
                    "wallet_code" => "gf_main_balance"
                ],
                "error" => [
                    "code" => "",
                    "message" => ""
                    ]
            );
            $game_trans_ext = ProviderHelper::createGameTransExtV2($trans_exist[0]->game_trans_id, $provider_trans_id, $round_id, $bet_amount, $entry_id, json_encode($request->all()), $response, $client_response['requesttosend'], $client_response['client_response']);
            
            Helper::saveLog("GoldenF TransferOut resonpse", $this->provider_id, json_encode($request->all()), $response);
            return $response;
                
        } catch (\Exception $e) {
            $msg = array(
                'error' => '1',
                'message' => $e->getMessage(),
            );
            Helper::saveLog('GoldenF TransferOut resonpse error', $this->provider_id, json_encode($request->all(),JSON_FORCE_OBJECT), $msg);
            return json_encode($msg, JSON_FORCE_OBJECT); 
        }
    }


    public function BetRecordGet(Request $request)
    {
        Helper::saveLog("GoldenF BetRecordGet req", $this->provider_id, json_encode($request->all()), "");
    }

    public function BetRecordPlayerGet(Request $request)
    {
        Helper::saveLog("GoldenF BetRecordPlayerGet req", $this->provider_id, json_encode($request->all()), "");
    }

    public function TransactionRecordGet(Request $request)
    {
        Helper::saveLog("GoldenF TransactionRecordGet req", $this->provider_id, json_encode($request->all()), "");
    }

    public function TransactionRecordPlayerGet(Request $request)
    {
        Helper::saveLog("GoldenF TransactionRecordPlayerGet req", $this->provider_id, json_encode($request->all()), "");
    }

    public function BetRecordDetail(Request $request)
    {
        Helper::saveLog("GoldenF BetRecordDetail req", $this->provider_id, json_encode($request->all()), "");
    }
}
