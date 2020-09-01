<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\MGHelper;
use GuzzleHttp\Client;
use App\Services\AES;
use App\Helpers\FCHelper;
class MicroGamingController extends Controller
{
    //
    public function launchGame(Request $request){
        $key = "LUGTPyr6u8sRjCfh";
        $aes = new AES($key);
        $player_id = $request->player_id;
        $providerlinks = "https://api-tigergaming.k2net.io/api/v1/agents/Tiger_UPG_USD_MA_Test/players/".$player_id."/sessions";
        $http = new Client();
        $response = $http->post("https://api-tigergaming.k2net.io/api/v1/agents/Tiger_UPG_USD_MA_Test/players/".$player_id."/sessions",[
            'form_params' => [
                'platform' => "desktop",
                'langCode' => "en-EN",//needd to be dynamic
                'contentCode' => "UPG_auroraBeastHunter",//temporary this is the game code
            ]
            ,
            'headers' =>[
                'Authorization' => 'Bearer '.MGHelper::stsTokenizer(),
                'Accept'     => 'application/json' 
            ]
        ]);

        $url = json_decode((string) $response->getBody(), true)["gameURL"];
        $data = array(
            "url" => urlencode($url),
            "token" => $request->token,
        );
        $encoded_data = $aes->AESencode(json_encode($data));
        return "https://play.betrnk.games/loadgame?param=".urlencode($encoded_data);
    }
}
