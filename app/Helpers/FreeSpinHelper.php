<?php

namespace App\Helpers;

use App\Services\AES;
use GuzzleHttp\Client;
use DB;
class FreeSpinHelper{

    public static function getFreeSpinBalance($player_id,$game_id){
        $query = DB::select("SELECT * FROM freespin WHERE player_id=".$player_id." AND game_id = ".$game_id." AND status = 1 AND bonus_type = 1 ORDER BY created_at ASC LIMIT 1");
        $result = count($query);
        $bonusdata= array();
        if($result > 0 ){
            $bonusfreespin["spins"] = array(
                "id" => "code_".$query[0]->freespin_id."_FREESPIN ".$query[0]->total_spin,
                "amount" => $query[0]->spin_remaining,
                "options" => array(
                    "gambleEnabled" => true,
                    "betPerLine" => $query[0]->coins,
                    "denomination" => $query[0]->denominations * 1000
                )
            );
            // foreach($query as $freespin){
            //     if($freespin->bonus_type == 1){
            //         $bonusfreespin["spins"] = array(
            //                 "id" => "FREESPIN".$freespin->total_spin,
            //                 "amount" => $freespin->spin_remaining,
            //                 "options" => array(
            //                     "gambleEnabled" => true,
            //                     "betPerLine" => $freespin->coins,
            //                     "denomination" => $freespin->denominations
            //                 )
            //         );
            //     }
            //     else if($freespin->bonus_type == 2){
            //         $bonusfreespin = array(
            //                 "options" => array(
            //                     "gambleEnabled" => true,
            //                     "maxBet" => $freespin->coins,
            //                     "maxLines" => true
            //                 )
            //         );
            //     }
            //     array_push($bonusdata,$bonusfreespin);
            // }
            return $bonusfreespin["spins"];
        }
        else{
            return null;
        }
    }
}
?>