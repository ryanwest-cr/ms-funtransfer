<?php
namespace App\Helpers;

use GuzzleHttp\Client;
use App\Helpers\Helper;
use App\Helpers\GameLobby;
use App\Helpers\ProviderHelper;
use App\Payment;
use DB;
use ErrorException;

class DemoHelper{
    
    public static function DemoGame($json_data){


        $data = json_decode(json_encode($json_data));

        
        if($data->game_provider == 'Bole Gaming'){
            $response = array(
                "game_code" => $json_data['game_code'],
                "url" => DemoHelper::getStaticUrl($data->game_code, $data->game_provider),
                "game_launch" => false
            );
        }else{
            $response = array(
                "game_code" => $json_data['game_code'],
                "url" => config('providerlinks.play_betrnk') . '/tigergames/api?msg=No Demo Available',
                "game_launch" => false
            );
        }

        return $response;     
    }


    # Providers That Has Static URL DEMO LINK IN THE DATABASE
    public static function getStaticUrl($game_code, $game_provider){
        $game_demo = DB::table('games as g')
        ->select('g.game_demo')
        ->leftJoin('providers as p', "g.provider_id", "=", "p.provider_id")
        ->where('g.game_code', $game_code)
        ->where('p.provider_name', $game_provider)
        ->first();
        return $game_demo->game_demo;
    }
}