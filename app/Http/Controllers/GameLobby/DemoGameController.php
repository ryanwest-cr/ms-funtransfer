<?php

namespace App\Http\Controllers\GameLobby;

use App\Http\Controllers\Controller;
use App\Helpers\DemoHelper;
use App\Helpers\GameLobby;
use Illuminate\Http\Request;

class DemoGameController extends Controller
{

    public function __construct(){
		// $this->middleware('oauth', ['except' => ['index']]);
		// $this->middleware('authorize:' . __CLASS__, ['except' => ['index', 'store']]);
    }
    
    public function GameDemo(Request $request){

        $data = json_decode(json_encode($request->all()));

        if(!$request->has("game_code") || !$request->has("game_provider")){
            $msg = array(
                "game_code" => $data->game_code,
                "url" => config('providerlinks.play_betrnk') . '/tigergames/api?msg=Missing Input',
                "game_launch" => false
            );
            return $msg;
        }
       
        $game_details = DemoHelper::findGameDetails($data->game_provider, $data->game_code);
        if($game_details == false){
            $msg = array(
                "game_code" => $data->game_code,
                "url" => config('providerlinks.play_betrnk') . '/tigergames/api?msg=No Game Found',
                "game_launch" => false
            );
            return $msg;
        }

        $provider_id = GameLobby::checkAndGetProviderId($data->game_provider);
        $provider_code = $provider_id->sub_provider_id;
        
        if($provider_code == 33){ // Bole Gaming
            $response = array(
                "game_code" => $data->game_code,
                "url" => DemoHelper::getStaticUrl($data->game_code, $data->game_provider),
                "game_launch" => true
            );
        }
        elseif(in_array($provider_code, [39, 78, 79, 80, 81, 82, 83])){
            $msg = array(
                "game_code" => $data->game_code,
                // "url" => DemoHelper::oryxLaunchUrl($data->game_code,$data->token,$data->exitUrl), 
                "url" => DemoHelper::oryxLaunchUrl($data->game_code), 
                "game_launch" => true
            );
            return response($msg,200)
            ->header('Content-Type', 'application/json');
        }
        elseif($provider_code == 55){ // pgsoft
            $response = array(
                "game_code" => $data->game_code,
                "url" => DemoHelper::pgSoft($data->game_code),
                "game_launch" => true
            );
        }
        elseif($provider_code == 60){ // ygg drasil direct
            $response = array(
                "game_code" => $data->game_code,
                "url" => DemoHelper::yggDrasil($data->game_code),
                "game_launch" => true
            );
        }
        elseif($provider_code == 40){  // Evoplay
            $response = array(
                "game_code" => $data->game_code,
                "url" => DemoHelper::evoplay($data->game_code),
                "game_launch" => true
            );
        }
        elseif($provider_code == 75){  // KAGaming
            $response = array(
                "game_code" => $data->game_code,
                "url" => DemoHelper::kagaming($data->game_code),
                "game_launch" => true
            );
        }
        // elseif($provider_code == 34){ // EDP
        //     // TEST ONLY
        //     $game_code = $json_data['game_code'];
        //     $game_name = explode('_', $game_code);
        //     $game_code = explode('@', $game_name[1]);
        //     $game_gg = $game_code[0];
        //     $arr = preg_replace("([A-Z])", " $0", $game_gg);
        //     $arr = explode(" ", trim($arr));
        //     if (count($arr) == 1) {
        //         $url = 'https://endorphina.com/games/' . strtolower($arr[0]) . '/play';
        //     } else {
        //         $url = 'https://endorphina.com/games/' . strtolower($arr[0]) . '-' . strtolower($arr[1]) . '/play';
        //     }
        //     $msg = array(
        //         "game_code" => $json_data['game_code'],
        //         "url" => $url,
        //         "game_launch" => true
        //     );
        //     return response($msg, 200)
        //     ->header('Content-Type', 'application/json');
        // }
        else{
            $response = array(
                "game_code" => $data->game_code,
                "url" => config('providerlinks.play_betrnk') . '/tigergames/api?msg=No Demo Available',
                "game_launch" => false
            );
        }

        return $response;
    }


}
