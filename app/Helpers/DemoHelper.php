<?php
namespace App\Helpers;

use App\Helpers\GameLobby;
use DB;

class DemoHelper{
    
    public static function DemoGame($json_data){


        $data = json_decode(json_encode($json_data));
        $provider_id = GameLobby::checkAndGetProviderId($data->game_provider);
        $provider_code = $provider_id->sub_provider_id;

        
        if($provider_code == 33){
            $response = array(
                "game_code" => $json_data['game_code'],
                "url" => DemoHelper::getStaticUrl($data->game_code, $data->game_provider),
                "game_launch" => false
            );
        }
        elseif(in_array($provider_code, [39, 78, 79, 80, 81, 82, 83])){
            $msg = array(
                "game_code" => $json_data['game_code'],
                "url" => DemoHelper::oryxLaunchUrl($request->game_code,$request->token,$request->exitUrl), 
                "game_launch" => true
            );
            return response($msg,200)
            ->header('Content-Type', 'application/json');
        } 
        else{
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
    public static function oryxLaunchUrl($game_code,$token,$exitUrl){
        $url = $exitUrl;
        $url = config("providerlinks.oryx.GAME_URL").$game_code.'/open?token='.$token.'&languageCode=ENG&playMode=FUN';
        return $url;
    }
}