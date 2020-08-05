<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ProviderHelper;
use DB;
use App\Helpers\Helper;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;


class PragmaticPLayController extends Controller
{
    public function authenticate(Request $request)
    {
        
        $enc_body = file_get_contents("php://input");
        parse_str($enc_body, $data);
        $json_encode = json_encode($data, true);
        $data = json_decode($json_encode);

        
        $providerId = $data->providerId;
        $hash = $data->hash;
        $token = $data->token;
        $client_details = ProviderHelper::getClientDetails('token',$token);
        
        if($client_details != null)
        {
            $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
            $currency = $client_details->default_currency;
            $country = $player_details->playerdetailsresponse->country_code;
            $balance = $player_details->playerdetailsresponse->balance;
            $userid = "TGaming_".$client_details->player_id;

            $response = array(
                "userId" => $userid,
                "currency" => $currency,
                "cash" => $balance,
                "bonus" => 0.00,
                "country" => $country,
                "jurisdiction" => "99",
                "error" => 0,
                "decription" => "Success"
            );

            Helper::saveLog('PP authenticate', 49, json_encode($data) , $response);

            
        }else{
            $response = [
                "error" => 4,
                "decription" => "Success"
            ];
        }

        return $response;
    }

    public function balance(Request $request)
    {
        
        $enc_body = file_get_contents("php://input");
        parse_str($enc_body, $data);
        $json_encode = json_encode($data, true);
        $data = json_decode($json_encode);

        Helper::saveLog('PP balance', 49, json_encode($data) , "balance");


        $playerId = ProviderHelper::explodeUsername('_',$data->userId);
        $client_details = ProviderHelper::getClientDetails('player_id',$playerId);
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
   
        $response = array(
            "currency" => $client_details->default_currency,
            "cash" => $player_details->playerdetailsresponse->balance,
            "bonus" => 0.00,
            "error" => 0,
            "description" => "Success"
        );

        Helper::saveLog('PP balance', 49, json_encode($data) , $response);
        
        return $response;
    }

    public function bet(Request $request)
    {
        $enc_body = file_get_contents("php://input");
        parse_str($enc_body, $data);
        $json_encode = json_encode($data, true);
        $data = json_decode($json_encode);

        Helper::saveLog('PP bet request', 49,json_encode($data), "");

        $playerId = ProviderHelper::explodeUsername('_',$data->userId);
        $client_details = ProviderHelper::getClientDetails('player_id',$playerId);
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
        $game_details = Helper::findGameDetails('game_code', 26, $data->gameId);


        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$client_details->client_access_token
            ]
        ]);

        $tokenId = $client_details->token_id;
        $game_code = $data->gameId;
        $bet_amount = $data->amount;
        $roundId = $data->roundId;

        if($bet_amount > $player_details->playerdetailsresponse->balance){

            $response = array(
                "cash" => $player_details->playerdetailsresponse->balance,
                "error" => 1,
                "description" => "Not Enough Balance"
            );

        }else{

            $checkGameTrans = DB::table('game_transactions')->where("round_id","=",$data->roundId)->get();
            if(count($checkGameTrans) > 0){

                $response = array(
                    "transactionId" => $checkGameTrans[0]->game_trans_id,
                    "currency" => $client_details->default_currency,
                    "cash" => $player_details->playerdetailsresponse->balance,
                    "bonus" => 0.00,
                    "usedPromo" => 0,
                    "error" => 0,
                    "description" => "Success"
                );

                Helper::saveLog('PP bet final', 49,json_encode($data) , $response);

            }else{

                $responseDetails = $this->responsetosend($client_details->client_access_token, $client_details->client_api_key, $game_details->game_code, $game_details->game_name, $client_details->client_player_id, $client_details->player_token, $bet_amount, $client, $client_details->fund_transfer_url, "debit",$client_details->default_currency );
                
                $gametrans = ProviderHelper::createGameTransaction($tokenId, $game_details->game_id, $bet_amount, 0.00, 1, 0, null, null, $bet_amount, $data->reference, $roundId);
        
                $response = array(
                    "transactionId" => $gametrans,
                    "currency" => $client_details->default_currency,
                    "cash" => $responseDetails['client_response']->fundtransferresponse->balance,
                    "bonus" => 0.00,
                    "usedPromo" => 0,
                    "error" => 0,
                    "description" => "Success"
                );
        
                $game_trans = DB::table('game_transactions')->where("round_id","=",$data->roundId)->get();
        
                // $response = array(
                //     "transactionId" => $game_trans[0]->game_trans_id,
                //     "currency" => $client_details->default_currency,
                //     "cash" => $clientDetalsResponse['client_response']->fundtransferresponse->balance,
                //     "bonus" => 0,
                //     "error" => 0,
                //     "description" => "Success"
                // );
        
                $trans_details = array(
                    "game_trans_id" => $game_trans[0]->game_trans_id,
                    "bet_amount" => $game_trans[0]->bet_amount,
                    "pay_amount" => $data->amount,
                    "win" => false,
                    "response" => $response 
                );
        
                $game_trans_ext = ProviderHelper::createGameTransExt( $gametrans, $game_trans[0]->provider_trans_id, $game_trans[0]->round_id, $data->amount, 2, $data, $response, $responseDetails['requesttosend'], $responseDetails['client_response'], $trans_details);
                Helper::saveLog('PP bet initial', 49,json_encode($data) , $response);
            }    

        }

        return $response;
    }

    public function result(Request $request)
    {
        $enc_body = file_get_contents("php://input");
        parse_str($enc_body, $data);
        $json_encode = json_encode($data, true);
        $data = json_decode($json_encode);

        Helper::saveLog('PP result request', 49, json_encode($data) ,"result");

        $playerId = ProviderHelper::explodeUsername('_',$data->userId);
        $client_details = ProviderHelper::getClientDetails('player_id',$playerId);
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
        $game_details = Helper::findGameDetails('game_code', 26, $data->gameId);

        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$client_details->client_access_token
            ]
        ]);

        $responseDetails = $this->responsetosend($client_details->client_access_token, $client_details->client_api_key, $game_details->game_code, $game_details->game_name, $client_details->client_player_id, $client_details->player_token, $data->amount, $client, $client_details->fund_transfer_url, "credit",$client_details->default_currency );
        
        $game_trans = DB::table('game_transactions')->where("round_id","=",$data->roundId)->get();

        $income = $game_trans[0]->bet_amount - $data->amount;
        $win = 1;
        
        $updateGameTrans = DB::table('game_transactions')
            ->where("round_id","=",$data->roundId)
            ->update([
                "win" => $win,
                "pay_amount" => $data->amount,
                "income" => $income,
                "entry_id" => 2
            ]);

        $response = array(
            "transactionId" => $game_trans[0]->game_trans_id,
            "currency" => $client_details->default_currency,
            "cash" => $responseDetails['client_response']->fundtransferresponse->balance,
            "bonus" => 0,
            "error" => 0,
            "description" => "Success",
        );

        $trans_details = array(
            "game_trans_id" => $game_trans[0]->game_trans_id,
            "bet_amount" => $game_trans[0]->bet_amount,
            "pay_amount" => $data->amount,
            "win" => true,
            "response" => $response 
        );

        $game_trans_ext = ProviderHelper::createGameTransExt($game_trans[0]->game_trans_id, $game_trans[0]->provider_trans_id, $game_trans[0]->round_id, $data->amount, 2, $data, $response, $responseDetails['requesttosend'], $responseDetails['client_response'], $trans_details);

        Helper::saveLog('PP result', 49, json_encode($data) , $response);
        
        return $response;
    }

    public function endRound(Request $request)
    {
        $enc_body = file_get_contents("php://input");
        parse_str($enc_body, $data);
        $json_encode = json_encode($data, true);
        $data = json_decode($json_encode);

        Helper::saveLog('PP endRound request', 49, json_encode($data) ,"endRound");


        $playerId = ProviderHelper::explodeUsername('_',$data->userId);
        $client_details = ProviderHelper::getClientDetails('player_id',$playerId);
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
   
        $response = array(
            "cash" => $player_details->playerdetailsresponse->balance,
            "bonus" => 0.00,
            "error" => 0,
            "description" => "Success"
        );

        return $response;
    }
    
    public function getBalancePerGame(Request $request)
    {
        $enc_body = file_get_contents("php://input");
        parse_str($enc_body, $data);
        $json_encode = json_encode($data, true);
        $data = json_decode($json_encode);
        
        Helper::saveLog('PP getBalancePerGame request', 49, json_encode($data) ,"getBalancePerGame");

        $playerId = ProviderHelper::explodeUsername('_',$data->userId);
        $client_details = ProviderHelper::getClientDetails('player_id',$playerId);
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);

       
        $response = [
            "gamesBalances" => [
                
                "gameID" => $data->gameIdList,
                "cash" => $player_details->playerdetailsresponse->balance,
                "bonus" => 0.00
                
            ]
        ];
        return $response;
    }

    public function sessionExpired(Request $request)
    {
        $enc_body = file_get_contents("php://input");
        parse_str($enc_body, $data);
        $json_encode = json_encode($data, true);
        $data = json_decode($json_encode);
        
        Helper::saveLog('PP sessionExpired request', 49, json_encode($data) ,"sessionExpired");

        $response = array(
            "error" => 0,
            "description" => "Success"
        );

        Helper::saveLog('PP sessionExpired request', 49, json_encode($data) , $response);
        return $response;

    }

    public function refund(Request $request)
    {
        $enc_body = file_get_contents("php://input");
        parse_str($enc_body, $data);
        $json_encode = json_encode($data, true);
        $data = json_decode($json_encode);

        $game_trans = DB::table("game_transactions")->where("round_id","=",$data->roundId)->get();
        $game_details = DB::table("games")->where("game_id","=",$game_trans[0]->game_id)->first();
        
        $playerId = ProviderHelper::explodeUsername('_',$data->userId);
        $client_details = ProviderHelper::getClientDetails('player_id',$playerId);
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
        // $game_details = Helper::findGameDetails('game_code', 25, $gameDetails->game_code);

        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$client_details->client_access_token
            ]
        ]);
        
        
        $bet_amount = $game_trans[0]->bet_amount;


        $responseDetails = $this->responsetosend($client_details->client_access_token, $client_details->client_api_key, $game_details->game_code, $game_details->game_name, $client_details->client_player_id, $client_details->player_token, $bet_amount, $client, $client_details->fund_transfer_url, "debit",$client_details->default_currency, true );

        $refund_update = DB::table('game_transactions')->where("round_id","=",$data->roundId)->update(['win' => '4']);
        
        $response = array(
            "transactionId" => $game_trans[0]->game_trans_id,
            "error" => 0,
            "description" => "Success"
        );

        $trans_details = array(
            "refund" => true,
            "bet_amount" => $bet_amount,
            "response" => $response,
        );

        $game_trans_ext = ProviderHelper::createGameTransExt($game_trans[0]->game_trans_id, $game_trans[0]->provider_trans_id, $game_trans[0]->round_id, $bet_amount, 3, $data, $response, $responseDetails['requesttosend'], $responseDetails['client_response'], $trans_details);

        return $response;
    }

    public function bonusWin(Request $request)
    {
        $enc_body = file_get_contents("php://input");
        parse_str($enc_body, $data);
        $json_encode = json_encode($data, true);
        $data = json_decode($json_encode);

        $game_trans = DB::table("game_transactions")->where("round_id","=",$data->roundId)->first();
        $game_details = DB::table("games")->where("game_id","=",$game_trans->game_id)->first();
        
        $playerId = ProviderHelper::explodeUsername('_',$data->userId);
        $client_details = ProviderHelper::getClientDetails('player_id',$playerId);
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);

        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$client_details->client_access_token
            ]
        ]);

        
    }

    public function jackpotWin(Request $request){
        $enc_body = file_get_contents("php://input");
        parse_str($enc_body, $data);
        $json_encode = json_encode($data, true);
        $data = json_decode($json_encode);


        $game_trans = DB::table("game_transactions")->where("round_id","=",$data->roundId)->first();
        $game_details = DB::table("games")->where("game_id","=",$game_trans->game_id)->first();
        
        $playerId = ProviderHelper::explodeUsername('_',$data->userId);
        $client_details = ProviderHelper::getClientDetails('player_id',$playerId);
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);

        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$client_details->client_access_token
            ]
        ]);
    }

   

    public function responsetosend($client_access_token,$client_api_key,$game_code,$game_name,$client_player_id,$player_token,$amount,$client,$fund_transfer_url,$transtype,$currency,$rollback=false){
        $requesttosend = [
            "access_token" => $client_access_token,
            "hashkey" => md5($client_api_key.$client_access_token),
            "type" => "fundtransferrequest",
            "datesent" => Helper::datesent(),
            "gamedetails" => [
            "gameid" => $game_code, // $game_code
            "gamename" => $game_name
            ],
            "fundtransferrequest" => [
                "playerinfo" => [
                "client_player_id" => $client_player_id,
                "token" => $player_token,
                ],
                "fundinfo" => [
                        "gamesessionid" => "",
                        "transactiontype" => $transtype,
                        "transferid" => "",
                        "rollback" => $rollback,
                        "currencycode" => $currency,
                        "amount" => $amount
                ],
            ],
        ];
        
        $guzzle_response = $client->post($fund_transfer_url,
            ['body' => json_encode($requesttosend)]
        );

        $client_response = json_decode($guzzle_response->getBody()->getContents());
        $data = [
            'requesttosend' => $requesttosend,
            'client_response' => $client_response,
        ];
        return $data;
    }
    

}
