<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ProviderHelper;
use DB;
use App\Helpers\Helper;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use App\Helpers\ClientRequestHelper;

class YGGController extends Controller
{
    public $provider_id;
    public $org;

    public function __construct(){
        $this->provider_id = config("providerlinks.ygg.provider_id");
        $this->org = config("providerlinks.ygg.Org");
        $this->topOrg = config("providerlinks.ygg.topOrg");
    }

    public function playerinfo(Request $request)
    {
        Helper::saveLog("YGG playerinfo req", $this->provider_id, json_encode($request->all()), "");

        $client_details = ProviderHelper::getClientDetails('token',$request->sessiontoken);
        if($client_details == null){ 
            $response = array(
                "code" => 1000,
                "msg" => "Session expired. Please log in again."
            );
            return $response;
            Helper::saveLog("YGG playerinfo response", $this->provider_id,json_encode($request->all(),JSON_FORCE_OBJECT), $response);
        }
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);  
        $player_id = "TGaming_".$client_details->player_id;
        $balance = floatval(number_format($player_details->playerdetailsresponse->balance, 2, '.', ''));

        $response = array(
            "code" => 0,
            "data" => array(
                "gender" => "",
                "playerId" => $player_id,
                "organization" => $this->org,
                "balance" => $balance,
                "applicableBonus" => "",
                "currency" => $client_details->default_currency,
                "homeCurrency" => $client_details->default_currency,
                "nickName" => $client_details->display_name,
                "country" => $player_details->playerdetailsresponse->country_code
            ),
            "msg" => "Success"
        );
        Helper::saveLog("YGG playerinfo response", $this->provider_id, json_encode($request->all()), $response);
        return $response;   
    }

    public function wager(Request $request)
    {
        $playerId = ProviderHelper::explodeUsername('_',$request->playerid);
        $client_details = ProviderHelper::getClientDetails('player_id',$playerId);
        if($client_details == null){ 
            $response = array(
                "code" => 1000,
                "msg" => "Session expired. Please log in again."
            );
            Helper::saveLog("YGG wager response", $this->provider_id, json_encode($request->all(),JSON_FORCE_OBJECT), $response);
            return $response;
        }
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);

        $gamecode = '';
        $game_name = '';
        for ($x = 1; $x <= 9; $x++) {
            if($request['cat'.$x] != ''){
                $qry = "select * from games where provider_id = ".$this->provider_id." and game_code = '".$request['cat'.$x]."'" ;
                $game_details = DB::select($qry);
            }else{
                break;
            }
            if(count($game_details) > 0){
                $gamecode = $game_details[0]->game_code;
                $game_name = $game_details[0]->game_name;
            }
        } 
        
        $balance = $player_details->playerdetailsresponse->balance;
        // $tokenId = $client_details->token_id;
        // $bet_amount = $request->amount;
        // $provider_trans_id = $request->reference;
        // $round_id = $request->subreference;
        
        $tokenId = $client_details->token_id;
        $game_code = $game_details[0]->game_code;
        $game_id = $game_details[0]->game_id;
        $bet_amount = $request->amount;
        $roundId = $request->subreference;
        $provider_trans_id = $request->reference;
        $bet_payout = 0; // Bet always 0 payout!
        $method = 1; // 1 bet, 2 win
        $win_or_lost = 0; // 0 Lost, 1 win, 3 draw, 4 refund, 5 processing
        $payout_reason = 'Bet';
        $income = $request->amount;
        $checkTrans = DB::table('game_transactions')->where('provider_trans_id','=',$request->reference)->where('round_id','=',$request->subreference)->get();
        
       

        // $gametrans = ProviderHelper::createGameTransaction($tokenId, $game_details[0]->game_id, $bet_amount, 0.00, 1, 0, null, null, $bet_amount, $provider_trans_id, $round_id);
        // $game_trans_ext = ProviderHelper::createGameTransExt( $gametrans, $provider_trans_id, $round_id, $bet_amount, 1, json_encode($request->all()), $response, , $client_response['client_response'], "");  

        try{
          
            if(count($checkTrans) > 0){
                $response = array(
                    "code" => 0,
                    "data" => array(
                        "currency" => $client_details->default_currency,
                        "applicableBonus" => 0.00,
                        "homeCurrency" => $client_details->default_currency,
                        "organization" => $this->org,
                        "balance" => floatval(number_format($player_details->playerdetailsresponse->balance, 2, '.', '')),
                        "nickName" => $client_details->display_name,
                        "playerId" => "TGaming_".$client_details->player_id
                    ),
                );
                Helper::saveLog("YGG wager dubplicate", $this->provider_id, json_encode($request->all(),JSON_FORCE_OBJECT), $response);
                return $response;
            }
           

            $game_trans = ProviderHelper::createGameTransaction($tokenId, $game_id, $bet_amount,  $bet_payout, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $roundId);
      
            $game_transextension = ProviderHelper::createGameTransExtV2($game_trans,$provider_trans_id, $roundId, $bet_amount, 1);

            if($balance < $request->amount){
                $response = array(
                    "code" => 1006,
                    "msg" => "You do not have sufficient fundsfor the bet."
                );
                Helper::saveLog("YGG wager response", $this->provider_id, json_encode($request->all(),JSON_FORCE_OBJECT), $response);
                return $response;
            }
            
            $client_response = ClientRequestHelper::fundTransfer($client_details, $bet_amount,$game_details[0]->game_code,$game_details[0]->game_name,$game_transextension,$game_trans,'debit');

            $response = array(
                "code" => 0,
                "data" => array(
                    "currency" => $client_details->default_currency,
                    "applicableBonus" => 0.00,
                    "homeCurrency" => $client_details->default_currency,
                    "organization" => $this->org,
                    "balance" => floatval(number_format($client_response->fundtransferresponse->balance, 2, '.', '')),
                    "nickName" => $client_details->display_name,
                    "playerId" => "TGaming_".$client_details->player_id
                ),
            );
            
            ProviderHelper::updatecreateGameTransExt($game_transextension, $request->all(), $response, $client_response->requestoclient, $client_response, $response);

            Helper::saveLog('Yggdrasil wager', $this->provider_id, json_encode($request->all(),JSON_FORCE_OBJECT), $response);
            return $response;

        }catch(\Exception $e){
            $msg = array(
                'error' => '1',
                'message' => $e->getMessage(),
            );
            Helper::saveLog('Yggdrasil wager error', $this->provider_id, json_encode($request->all(),JSON_FORCE_OBJECT), $msg);
            return json_encode($msg, JSON_FORCE_OBJECT); 
        }

        
    }

    public function cancelwager(Request $request)
    {
        $playerId = ProviderHelper::explodeUsername('_',$request->playerid);
        $client_details = ProviderHelper::getClientDetails('player_id',$playerId);
        Helper::saveLog("YGG cancelwager request", $this->provider_id, json_encode($request->all(),JSON_FORCE_OBJECT), "");
        if($client_details == null){ 
            $response = array(
                "code" => 1000,
                "msg" => "Session expired. Please log in again."
            );
            Helper::saveLog("YGG cancelwager login", $this->provider_id, json_encode($request->all(),JSON_FORCE_OBJECT), $response);
            return $response;
        }
        $provider_trans_id = $request->reference;
        $round_id = $request->subreference;
        $checkTrans = DB::table('game_transactions')->where('provider_trans_id','=',$provider_trans_id)->where('round_id','=',$round_id)->get();
        $game_details = DB::table("games")->where("game_id","=",$checkTrans[0]->game_id)->first();
        $gamecode = $game_details->game_code;
        $game_name = $game_details->game_name;
        $bet_amount = $checkTrans[0]->bet_amount;

        
        $game_trans_ext_v2 = ProviderHelper::createGameTransExtV2( $checkTrans[0]->game_trans_id, $provider_trans_id, $round_id, $bet_amount, '3');


        if(count($checkTrans) > 0){
            
            $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
            if($checkTrans[0]->win == 4){
                $response = array(
                    "code" => 0,
                    "data" => array(
                        "playerId" => "TGaming_".$client_details->player_id,
                        "organization" => $this->org,
                        "balance" => floatval(number_format($player_details->playerdetailsresponse->balance, 2, '.', '')),
                        "currency" => $client_details->default_currency,
                    )
                );
                Helper::saveLog('Yggdrasil cancelwager duplicate call', $this->provider_id, json_encode($request->all(),JSON_FORCE_OBJECT), $response);
                return $response;
            }
            $client_response = ClientRequestHelper::fundTransfer($client_details, $bet_amount, $game_details->game_code, $game_details->game_name, $game_trans_ext_v2, $checkTrans[0]->game_trans_id, 'credit', 'true');
           
            $update = DB::table('game_transactions')
                        ->where('game_trans_id','=',$checkTrans[0]->game_trans_id)
                        ->update(["win" => 4, "entry_id" => 2, "transaction_reason" => "refund"]);
            $response = array(
                "code" => 0,
                "data" => array(
                    "playerId" => "TGaming_".$client_details->player_id,
                    "organization" => $this->org,
                    "balance" => floatval(number_format($client_response->fundtransferresponse->balance, 2, '.', '')),
                    "currency" => $client_details->default_currency
                )
            );
            $updateGameTransExt = DB::table('game_transaction_ext')->where('game_trans_ext_id','=',$game_trans_ext_v2)->update(["amount" => $bet_amount,"game_transaction_type" => '3',"provider_request" => json_encode($request->all(),JSON_FORCE_OBJECT),"mw_response" => json_encode($response),"mw_request" => json_encode($client_response->requestoclient),"client_response" => json_encode($client_response),"transaction_detail" => json_encode($response) ]);

            return $response;
        }else{
            $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
            $response = array(
                "code" => 0,
                "data" => array(
                    "playerId" => "TGaming_".$client_details->player_id,
                    "organization" => $this->org,
                    "balance" => floatval(number_format($player_details->playerdetailsresponse->balance, 2, '.', '')),
                    "currency" => $client_details->default_currency
                )
            );
            Helper::saveLog('Yggdrasil cancelwager not exist', $this->provider_id, json_encode($request->all(),JSON_FORCE_OBJECT), $response);
            return $response;
        }
    }

    public function appendwagerresult(Request $request)
    {
        
        Helper::saveLog('Yggdrasil appendwagerresult request', $this->provider_id, json_encode($request->all(),JSON_FORCE_OBJECT), "" );
        $playerId = ProviderHelper::explodeUsername('_',$request->playerid);
        $client_details = ProviderHelper::getClientDetails('player_id',$playerId);

        if($client_details == null){ 
            $response = array(
                "code" => 1000,
                "msg" => "Session expired. Please log in again."
            );
            Helper::saveLog("YGG appendwagerresult login", $this->provider_id,json_encode($request->all(),JSON_FORCE_OBJECT), $response);
            return $response;
        }
        $gamecode = '';
        $game_name = '';
        for ($x = 1; $x <= 9; $x++) {
            if($request['cat'.$x] != ''){
                $qry = "select * from games where provider_id = ".$this->provider_id." and game_code = '".$request['cat'.$x]."'" ;
                $game_details = DB::select($qry);
            }else{
                break;
            }
            if(count($game_details) > 0){
                $gamecode = $game_details[0]->game_code;
                $game_name = $game_details[0]->game_name;
            }
        } 
        $checkTrans = DB::table('game_transactions')->where('provider_trans_id','=',$request->reference)->where('round_id','=',$request->subreference)->get();
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
        $balance = $player_details->playerdetailsresponse->balance;
        $tokenId = $client_details->token_id;
        $bet_amount = $request->amount;
        $provider_trans_id = $request->reference;
        $round_id = $request->subreference;

        
       

        try{
            

            if(count($checkTrans) > 0){
                // $bonusBal = DB::select('select * from seamless_request_logs where provider_id = 38 and request_data like "%TGaming_188%"  and request_data like "%getbonusprize%" and response_data like "%bonusprize%" ');
                // $bonusAmt = 0;
                // foreach($bonusBal as $item){
                //     $bunos = json_decode($item->request_data,true);
                //     $bonusAmt += $bunos['bonusprize'];
                // }

                $response = array(
                    "code" => 0,
                    "data" => array(
                        "currency" => $client_details->default_currency,
                        "applicableBonus" => floatval(number_format($player_details->playerdetailsresponse->balance, 2, '.', '')),
                        "homeCurrency" => $client_details->default_currency,
                        "organization" => $this->org,
                        "balance" => floatval(number_format($player_details->playerdetailsresponse->balance, 2, '.', '')),
                        "nickName" => $client_details->display_name,
                        "playerId" => "TGaming_".$client_details->player_id,
                        "bonus" => 0
                    ),
                );
                Helper::saveLog("YGG appendwagerresult dubplicate", $this->provider_id, json_encode($request->all(),JSON_FORCE_OBJECT), $response);
                return $response;
            }
            
            $gametrans = ProviderHelper::createGameTransaction($tokenId, $game_details[0]->game_id, $bet_amount, 0.00, 1, 0, null, null, $bet_amount, $provider_trans_id, $round_id);

            $game_trans_ext_v2 = ProviderHelper::createGameTransExtV2( $gametrans, $provider_trans_id, $round_id, $bet_amount, '1');

            // $balance = $player_details->playerdetailsresponse->balance;
            // $tokenId = $client_details->token_id;
            // $bet_amount = $request->amount;
            // $provider_trans_id = $request->reference;
            // $round_id = $request->subreference;
       

            // $client_response = $this->fundTransferRequest(
            //         $client_details->client_access_token,
            //         $client_details->client_api_key, 
            //         $gamecode, 
            //         $game_name, 
            //         $client_details->client_player_id, 
            //         $client_details->player_token, 
            //         $bet_amount,
            //         $client_details->fund_transfer_url, 
            //         "credit",
            //         $client_details->default_currency, 
            //         false
            //     ); 
            $client_response = ClientRequestHelper::fundTransfer($client_details, $bet_amount, $game_details[0]->game_code, $game_details[0]->game_name, $game_trans_ext_v2, $gametrans, 'credit');

            $bonus = 'getbonusprize';
            Helper::saveLog('Yggdrasil appendwagerresult bonus', $this->provider_id, json_encode($request->all(),JSON_FORCE_OBJECT), $bonus );


            // $bonusBal = DB::select('select * from seamless_request_logs where provider_id = 38 and request_data like "%TGaming_188%"  and request_data like "%getbonusprize%" and response_data like "%bonusprize%" ');
            // // $bunos = json_decode($bonusBal[0]->request_data,true);
            // $bonusAmt = 0;
            // foreach($bonusBal as $item){
            //     $bunos = json_decode($item->request_data,true);
            //     $bonusAmt += $bunos['bonusprize'];
            // }
            $response = array(
                "code" => 0,
                "data" => array(
                    "currency" => $client_details->default_currency,
                    "applicableBonus" =>  floatval(number_format($client_response->fundtransferresponse->balance, 2, '.', '')),
                    "homeCurrency" => $client_details->default_currency,
                    "organization" => $this->org,
                    "balance" =>  floatval(number_format($client_response->fundtransferresponse->balance, 2, '.', '')),
                    "nickName" => $client_details->display_name,
                    "playerId" => "TGaming_".$client_details->player_id,
                    "bonus" => 0
                ),
            );
            
            
            $updateGameTransExt = DB::table('game_transaction_ext')->where('game_trans_ext_id','=',$game_trans_ext_v2)->update(["amount" => $bet_amount,"game_transaction_type" => '2',"provider_request" => json_encode($request->all(),JSON_FORCE_OBJECT),"mw_response" => json_encode($response),"mw_request" => json_encode($client_response->requestoclient),"client_response" => json_encode($client_response),"transaction_detail" => json_encode($response) ]);

            Helper::saveLog('Yggdrasil appendwagerresult', $this->provider_id, json_encode($request->all(),JSON_FORCE_OBJECT), $response);
            return $response;

        }catch(\Exception $e){
            $msg = array(
                'error' => '1',
                'message' => $e->getMessage(),
            );
            Helper::saveLog('Yggdrasil appendwagerresult error', $this->provider_id, json_encode($request->all(),JSON_FORCE_OBJECT), $msg);
            return json_encode($msg, JSON_FORCE_OBJECT); 
        }

    }

    public function endwager(Request $request)
    {
        $playerId = ProviderHelper::explodeUsername('_',$request->playerid);
        $client_details = ProviderHelper::getClientDetails('player_id',$playerId);
        if($client_details == null){ 
            $response = array(
                "code" => 1000,
                "msg" => "Session expired. Please log in again."
            );
            Helper::saveLog("YGG endwager login", $this->provider_id,json_encode($request->all(),JSON_FORCE_OBJECT), $response);
            return $response;
        }
        $checkTrans = DB::table('game_transaction_ext')->where('provider_trans_id','=',$request->reference)->where('round_id','=',$request->subreference)->get();
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
        $gamecode = '';
        $game_name = '';
        for ($x = 1; $x <= 9; $x++) {
            if($request['cat'.$x] != ''){
                $qry = "select * from games where provider_id = ".$this->provider_id." and game_code = '".$request['cat'.$x]."'" ;
                $game_details = DB::select($qry);
            }else{
                break;
            }
            if(count($game_details) > 0){
                $gamecode = $game_details[0]->game_code;
                $game_name = $game_details[0]->game_name;
            }
        } 

        $balance = $player_details->playerdetailsresponse->balance;
        $tokenId = $client_details->token_id;
        $bet_amount = $request->amount;
        $provider_trans_id = $request->reference;
        $round_id = $request->subreference;
        $getTrans = DB::table('game_transactions')->where('provider_trans_id','=',$provider_trans_id)->get();
        $income = $getTrans[0]->bet_amount - $bet_amount;
        $entry_id = $bet_amount > 0 ? 2 : 1;
        $win = $bet_amount > 0 ? 1 : 0;
        

        
        if(count($checkTrans) > 0){
            $response = array(
                "code" => 0,
                "data" => array(
                    "currency" => $client_details->default_currency,
                    "applicableBonus" => 0.00,
                    "homeCurrency" => $client_details->default_currency,
                    "organization" => $this->org,
                    "balance" => floatval(number_format($player_details->playerdetailsresponse->balance, 2, '.', '')),
                    "nickName" => $client_details->display_name,
                    "playerId" => "TGaming_".$client_details->player_id,
                    "balik" => true
                ),
            );
            Helper::saveLog("YGG endwager(win) dubplicate", $this->provider_id, json_encode($request->all(),JSON_FORCE_OBJECT), $response);
            return $response;
        }

        $game_trans_ext_v2 = ProviderHelper::createGameTransExtV2( $getTrans[0]->game_trans_id, $provider_trans_id, $round_id, $bet_amount, $entry_id);
       
        try{



            $client_response = ClientRequestHelper::fundTransfer($client_details, $bet_amount, $game_details[0]->game_code, $game_details[0]->game_name, $game_trans_ext_v2, $getTrans[0]->game_trans_id, 'credit');

            
            $response = array(
                "code" => 0,
                "data" => array(
                    "currency" => $client_details->default_currency,
                    "applicableBonus" => 0.00,
                    "homeCurrency" => $client_details->default_currency,
                    "organization" => $this->org,
                    "balance" => floatval(number_format($client_response->fundtransferresponse->balance, 2, '.', '')),
                    "nickName" => $client_details->display_name,
                    "playerId" => "TGaming_".$client_details->player_id
                ),
            );
            
            $update = DB::table('game_transactions')
                        ->where('game_trans_id','=',$getTrans[0]->game_trans_id)
                        ->update(["win" => $win, "pay_amount" => $bet_amount, "entry_id" => $entry_id, "income" => $income]);

            $updateGameTransExt = DB::table('game_transaction_ext')->where('game_trans_ext_id','=',$game_trans_ext_v2)->update(["amount" => $bet_amount,"game_transaction_type" => $entry_id,"provider_request" => json_encode($request->all(),JSON_FORCE_OBJECT),"mw_response" => json_encode($response),"mw_request" => json_encode($client_response->requestoclient),"client_response" => json_encode($client_response),"transaction_detail" => json_encode($response) ]);
            Helper::saveLog("YGG endwager (win)", $this->provider_id, json_encode($request->all(),JSON_FORCE_OBJECT), $response);
            return $response;

        }catch(\Exception $e){
            $msg = array(
                'error' => '1',
                'message' => $e->getMessage(),
            );
            Helper::saveLog('Yggdrasil endwager error', $this->provider_id, json_encode($request->all(),JSON_FORCE_OBJECT), $msg);
            return json_encode($msg, JSON_FORCE_OBJECT); 
        }
    }

    public function campaignpayout(Request $request)
    {
        Helper::saveLog('Yggdrasil campaignpayout request', $this->provider_id, json_encode($request->all(),JSON_FORCE_OBJECT), "");
        $playerId = ProviderHelper::explodeUsername('_',$request->playerid);
        $client_details = ProviderHelper::getClientDetails('player_id',$playerId);
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
        $response = array(
            "code" => 0,
            "data" => array(
                "currency" => $client_details->default_currency,
                "applicableBonus" => floatval(number_format($player_details->playerdetailsresponse->balance, 2, '.', '')),
                "homeCurrency" => $client_details->default_currency,
                "organization" => $this->org,
                "balance" => floatval(number_format($player_details->playerdetailsresponse->balance, 2, '.', '')),
                "nickName" => $client_details->display_name,
                "playerId" => "TGaming_".$client_details->player_id
            ),
        );
        Helper::saveLog("YGG campaignpayout response", $this->provider_id, json_encode($request->all(),JSON_FORCE_OBJECT), $response);
        return $response;
    }

    public function getbalance(Request $request)
    {
        Helper::saveLog('Yggdrasil getbalance request', $this->provider_id, json_encode($request->all(),JSON_FORCE_OBJECT), "");
        $client_details = ProviderHelper::getClientDetails('token',$request->sessiontoken);
        if($client_details == null){ 
            $response = array(
                "code" => 1000,
                "msg" => "Session expired. Please log in again."
            );
            return $response;
            Helper::saveLog("YGG playerinfo response", $this->provider_id,json_encode($request->all(),JSON_FORCE_OBJECT), $response);
        }
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);  
        $player_id = "TGaming_".$client_details->player_id;
        $balance = floatval(number_format($player_details->playerdetailsresponse->balance, 2, '.', ''));

        $response = array(
            "code" => 0,
            "data" => array(
                "currency" => $client_details->default_currency,
                "applicableBonus" => 0,
                "homeCurrency" => $client_details->default_currency,
                "organization" => $this->org,
                "balance" => $balance,
                "nickName" => $client_details->display_name,
                "playerId" => $player_id,
            )
        );
        Helper::saveLog("YGG getbalance response", $this->provider_id, json_encode($request->all()), $response);
        return $response; 
        
    }
    
    public function fundTransferRequest($client_access_token,$client_api_key,$game_code,$game_name,$client_player_id,$player_token,$amount,$fund_transfer_url,$transtype,$currency,$rollback=false){
        try {
            $client = new Client([
                'headers' => [ 
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer '.$client_access_token
                ]
            ]);
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
            // return $requesttosend;
            $guzzle_response = $client->post($fund_transfer_url,
                ['body' => json_encode($requesttosend)]
            );
            $client_response = json_decode($guzzle_response->getBody()->getContents());
            $data = [
                'requesttosend' => $requesttosend,
                'client_response' => $client_response,
            ];
            return $data;
            //
        } catch (\Exception $e) {
            Helper::saveLog('Called Failed!', $this->provider_db_id, json_encode($requesttosend), $e->getMessage());
            return 'false';
        }
    }

}
