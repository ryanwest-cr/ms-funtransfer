<?php

namespace App\Http\Controllers\GameLobby;

use App\Http\Controllers\Controller;
use App\Helpers\DemoHelper;
use App\Helpers\GameLobby;
use App\Helpers\ProviderHelper;
use Illuminate\Http\Request;

class DemoGameController extends Controller
{

    public function __construct(){
		$this->middleware('oauth', ['except' => ['index']]);
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
       
        return DemoHelper::DemoGame($request->all());

    }


}
