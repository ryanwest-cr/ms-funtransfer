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
            return $bonusfreespin["spins"];
        }
        else{
            return null;
        }
    }
    public static function getFreeSpinBalanceByFreespinId($freespin_id){
        $exploded_value = explode('_', $freespin_id);
        $query = DB::select("SELECT * FROM freespin WHERE freespin_id=".$exploded_value[1]." AND status = 1");
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
            return $bonusfreespin["spins"];
        }
        else{
            return null;
        }
    }
    public static function updateFreeSpinBalance($freespin_id){
        $exploded_value = explode('_', $freespin_id);
        $beforeupdatequery = DB::select("SELECT * FROM freespin WHERE freespin_id=".$exploded_value[1]." AND status = 1");
        $count = count($beforeupdatequery);
        if($count > 0){
            $spin_remaining = $beforeupdatequery[0]->spin_remaining - 1;
            if($spin_remaining == 0){
                $update_freespin = DB::select("UPDATE freespin SET spin_remaining=".$spin_remaining.",status=0 WHERE freespin_id = ".$exploded_value[1]."");
            }
            else{
                $update_freespin = DB::select("UPDATE freespin SET spin_remaining=".$spin_remaining." WHERE freespin_id = ".$exploded_value[1]."");
            }
            $query = DB::select("SELECT * FROM freespin WHERE freespin_id=".$exploded_value[1]."");
                $bonusfreespin["spins"] = array(
                    "id" => "code_".$query[0]->freespin_id."_FREESPIN ".$query[0]->total_spin,
                    "amount" => $query[0]->spin_remaining,
                    "options" => array(
                        "gambleEnabled" => true,
                        "betPerLine" => $query[0]->coins,
                        "denomination" => $query[0]->denominations * 1000
                    )
                );
                return $bonusfreespin["spins"];
        }
        else{
            return null;
        }
    }
}
?>