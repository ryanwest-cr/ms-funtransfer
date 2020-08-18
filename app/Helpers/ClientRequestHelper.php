<?php
namespace App\Helpers;

use GuzzleHttp\Client;
use App\Helpers\Helper;
use App\Helpers\GameLobby;
use App\Helpers\ProviderHelper;
use DB; 

class ClientRequestHelper{
    
    public static function getTransactionId($player_token,$game_round){
        $transaction = DB::table("player_session_tokens as pst")
                        ->leftJoin("game_transactions as gt","pst.token_id","=","gt.token_id")
                        ->where("pst.player_token",$player_token)
                        ->where("gt.round_id",$game_round)
                        ->first();
        if($transaction){
            $transaction->game_trans_id = $transaction->game_trans_id;
        }
        else{
            $transaction = DB::table("game_transactions")->latest()->first();
            $transaction->game_trans_id = $transaction->game_trans_id +1;
        }
        $transaction_ext = DB::table("game_transaction_ext")->latest()->first();
        $data = array(
            "transferId" => $transaction_ext->game_trans_ext_id + 1,
            "roundId" => $transaction->game_trans_id
        );
        return $data;
    }


}