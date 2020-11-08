<?php
namespace App\Helpers;

use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use App\Helpers\GameLobby;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;
use DB;

class DigitainHelper{

    public static $timeout = 2; // Seconds

    public static function tokenCheck($token){
        $token = DB::table('player_session_tokens')
                    ->select("*", DB::raw("NOW() as IMANTO"))
                    ->where('player_token', $token)
                    ->first();
        if($token != null){
            $check_token = DB::table('player_session_tokens')
            ->selectRaw("TIME_TO_SEC(TIMEDIFF( NOW(), '".$token->created_at."'))/60 as `time`")
            ->first();
            if(1440 > $check_token->time) {  // TIMEGAP IN MINUTES!
                $token = true; // True if Token can still be used!
            }else{
                $token = false; // Expired Token
            }
        }else{
            $token = false; // Not Found Token
        }
        return $token;
    }

    
    public static function increaseTokenLifeTime($seconds, $token,$type=1){
         $token = DB::table('player_session_tokens')
                    ->select("*", DB::raw("NOW() as IMANTO"))
                    ->where('player_token', $token)
                    ->first();
         $date_now = $token->created_at;

         if($type==1){
            $newdate = date("Y-m-d H:i:s", (strtotime(date($date_now)) + $seconds));
            $update = DB::table('player_session_tokens')
            ->where('token_id', $token->token_id)
            ->update(['created_at' => $newdate]);
        }else{
           $newdate = date('Y-m-d H:i:s', strtotime($date_now .' -1 day'));
           $update = DB::table('player_session_tokens')
            ->where('token_id', $token->token_id)
            ->update(['created_at' => $newdate]); 
        }
    }


    public static function SaveRefreshToken(){
        DB::table('player_session_tokens')->insert(
        array('player_id' => $client_details->player_id, 
              'player_token' =>  $client_response->playerdetailsresponse->refreshtoken, 
              'status_id' => '1')
        );
    }


    public  static function updateBetToWin($game_trans_id, $pay_amount, $income, $win, $entry_id, $type=1,$bet_amount=0) {
        if($type == 1){
            $update = DB::table('game_transactions')
            ->where('game_trans_id', $game_trans_id)
            ->update(['pay_amount' => $pay_amount, 
                  'income' => $income, 
                  'win' => $win, 
                  'entry_id' => $entry_id,
                  'transaction_reason' => 'Bet updated to win'
            ]);
        }else{
            $update = DB::table('game_transactions')
            ->where('game_trans_id', $game_trans_id)
            ->update(['pay_amount' => $pay_amount, 
                  'income' => $income, 
                  'bet_amount' => $bet_amount, 
                  'win' => $win, 
                  'entry_id' => $entry_id,
                  'transaction_reason' => 'Bet updated to win'
            ]);
        }
        return ($update ? true : false);
    }


     /**
     * [isolated helper class helper for digitain]
     * 
     */
    public static function findGameDetails($type, $provider_id, $identification) {
            $game_details = DB::table("games as g")
                ->leftJoin("providers as p","g.provider_id","=","p.provider_id");
                
            if ($type == 'game_code') {
                $game_details->where([
                    ["g.provider_id", "=", $provider_id],
                    ["g.game_code",'=', $identification],
                ]);
            }
            $result= $game_details->first();
            return $result;
    }

    public static function saveLog($method, $provider_id = 0, $request_data, $response_data) {
            $data = [
                        "method_name" => $method,
                        "provider_id" => $provider_id,
                        "request_data" => json_encode(json_decode($request_data)),
                        "response_data" => json_encode($response_data)
                    ];
            // return DB::table('debug')->insertGetId($data);
            return DB::table('seamless_request_logs')->insertGetId($data);
    }

    public static function savePLayerGameRound($game_code,$player_token,$sub_provider_name){
        $sub_provider_id = DB::table("sub_providers")->where("sub_provider_name",$sub_provider_name)->first();
        Helper::saveLog('SAVEPLAYERGAME(ICG)', 12, json_encode($sub_provider_id), $sub_provider_name);
        $game = DB::table("games")->where("game_code",$game_code)->where("sub_provider_id",$sub_provider_id->sub_provider_id)->first();
        $player_game_round = array(
            "player_token" => $player_token,
            "game_id" => $game->game_id,
            "status_id" => 1
        );
        DB::table("player_game_rounds")->insert($player_game_round);
    }


    public static function getInfoPlayerGameRound($player_token){
        $game = DB::table("player_game_rounds as pgr")
                ->leftJoin("player_session_tokens as pst","pst.player_token","=","pgr.player_token")
                ->leftJoin("games as g" , "g.game_id","=","pgr.game_id")
                ->leftJoin("players as ply" , "pst.player_id","=","ply.player_id")
                ->where("pgr.player_token",$player_token)
                ->first();
        return $game ? $game : false;
    }

    /**
     * [isolated provider class helper for digitain]
     * 
     */
    public  static function findGameExt($provider_identifier, $game_transaction_type, $type) {
        // DB::enableQueryLog();
        $transaction_db = DB::table('game_transaction_ext as gte');
        if ($type == 'transaction_id') {
            $transaction_db->where([
                ["gte.provider_trans_id", "=", $provider_identifier],
                ["gte.game_transaction_type", "=", $game_transaction_type],
                // ["gte.transaction_detail", "!=", '"FAILED"'], // TEST OVER ALL
            ]);
        }
        if ($type == 'round_id') {
            $transaction_db->where([
                ["gte.round_id", "=", $provider_identifier],
                ["gte.game_transaction_type", "=", $game_transaction_type],
                // ["gte.transaction_detail", "!=", '"FAILED"'], // TEST OVER ALL
            ]);
        }  
        if ($type == 'game_transaction_ext_id') {
            $transaction_db->where([
                ["gte.game_transaction_type", "=", $game_transaction_type],
                ["gte.game_trans_ext_id", "=", $provider_identifier],
            ]);
        } 
        if ($type == 'game_trans_id') {
            $transaction_db->where([
                ["gte.game_transaction_type", "=", $game_transaction_type],
                ["gte.game_trans_id", "=", $provider_identifier],
            ]);
        } 
        $result = $transaction_db->latest()->first(); // Added Latest (CQ9) 08-12-20 - Al
        // Helper::saveLog('Find Game Extension', 999, json_encode(DB::getQueryLog()), "TIME Find Game Extension");
        return $result ? $result : 'false';
    }


    public  static function findGameTransaction($identifier, $type, $entry_type='') {
        // DB::enableQueryLog();
        $transaction_db = DB::table('game_transactions as gt')
                        ->select('gt.*', 'gte.transaction_detail')
                        ->leftJoin("game_transaction_ext AS gte", "gte.game_trans_id", "=", "gt.game_trans_id");
                       
        if ($type == 'transaction_id') {
            $transaction_db->where([
                ["gt.provider_trans_id", "=", $identifier],
                ["gt.entry_id", "=", $entry_type],
            ]);
        }
        if ($type == 'game_transaction') {
            $transaction_db->where([
                ["gt.game_trans_id", "=", $identifier],
            ]);
        }
        if ($type == 'round_id') {
            $transaction_db->where([
                ["gt.round_id", "=", $identifier],
                ["gt.entry_id", "=", $entry_type],
            ]);
        }
        if ($type == 'refundbet') { // TEST
            $transaction_db->where([
                ["gt.round_id", "=", $identifier],
                ["gt.entry_id", "=", $entry_type],
            ]);
        }
        $result= $transaction_db
            ->first();
        // Helper::saveLog('Find Game Transaction', 999, json_encode(DB::getQueryLog()), "TIME Find Game Transaction");
        return $result ? $result : 'false';
    }


    public static function playerDetailsCall($client_details, $refreshtoken=false){
        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$client_details->client_access_token
            ]
        ]);
        $datatosend = ["access_token" => $client_details->client_access_token,
            "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
            "type" => "playerdetailsrequest",
            "datesent" => Helper::datesent(),
            "clientid" => $client_details->client_id,
            "playerdetailsrequest" => [
                "player_username"=>$client_details->username,
                "client_player_id" => $client_details->client_player_id,
                "token" => $client_details->player_token,
                "gamelaunch" => true,
                "refreshtoken" => $refreshtoken
            ]
        ];
        try{    
            $guzzle_response = $client->post($client_details->player_details_url,
                ['body' => json_encode($datatosend)]
            );
            $client_response = json_decode($guzzle_response->getBody()->getContents());
            return $client_response;
        }catch (\Exception $e){
           Helper::saveLog('ALDEBUG client_player_id = '.$client_details->client_player_id,  99, json_encode($datatosend), $e->getMessage());
           return 'false';
        }
    }

    public static function getClientDetails($type = "", $value = "", $gg=1, $providerfilter='all') {
        // DB::enableQueryLog();
        if ($type == 'token') {
            $where = 'where pst.player_token = "'.$value.'"';
        }
        if($providerfilter=='fachai'){
            if ($type == 'player_id') {
                $where = 'where '.$type.' = "'.$value.'" AND pst.status_id = 1 ORDER BY pst.token_id desc';
            }
        }else{
            if ($type == 'player_id') {
               $where = 'where '.$type.' = "'.$value.'"';
            }
        }
        if ($type == 'username') {
            $where = 'where p.username = "'.$value.'"';
        }
        if ($type == 'token_id') {
            $where = 'where pst.token_id = "'.$value.'"';
        }
        if($providerfilter=='fachai'){
            $filter = 'LIMIT 1';
        }else{
            // $result= $query->latest('token_id')->first();
            $filter = 'order by token_id desc LIMIT 1';
        }

        $query = DB::select('select `p`.`client_id`, `p`.`player_id`, `p`.`email`, `p`.`client_player_id`,`p`.`language`, `p`.`currency`, `p`.`test_player`, `p`.`username`,`p`.`created_at`,`pst`.`token_id`,`pst`.`player_token`,`c`.`client_url`,`c`.`default_currency`,`pst`.`status_id`,`p`.`display_name`,`op`.`client_api_key`,`op`.`client_code`,`op`.`client_access_token`,`ce`.`player_details_url`,`ce`.`fund_transfer_url`,`p`.`created_at` from player_session_tokens pst inner join players as p using(player_id) inner join clients as c using (client_id) inner join client_endpoints as ce using (client_id) inner join operator as op using (operator_id) '.$where.' '.$filter.'');

         $client_details = count($query);
         // Helper::saveLog('GET CLIENT LOG', 999, json_encode(DB::getQueryLog()), "TIME GET CLIENT");
         return $client_details > 0 ? $query[0] : null;
    }

    public  function gameTransactionEXTLog($trans_type,$trans_identifier,$type=false){
        $where = 'where `'.$trans_type.'` = "'.$trans_identifier.'"';
        $filter = 'LIMIT 1';
        $query = DB::select('select game_trans_ext_id, game_trans_id, provider_trans_id, round_id, amount, game_transaction_type, transaction_detail from `game_transaction_ext` '.$where.' '.$filter.'');
        $client_details = count($query);
        return $client_details > 0 ? $query[0] : false;
    }

}