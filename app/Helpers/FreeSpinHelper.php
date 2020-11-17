<?php

namespace App\Helpers;

use App\Services\AES;
use GuzzleHttp\Client;
use DB;
class FreeSpinHelper{

    public static function getFreeSpinBalance($player_id,$game_id){
        $query = DB::select("SELECT * FROM freespin WHERE player_id=".$player_id." AND game_id=".$game_id."");
        $result = count($query);
        return $result > 0 ? $query[0]:null;
    }

}
?>