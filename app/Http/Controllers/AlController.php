<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\AlHelper;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use GuzzleHttp\Client;
use Session;
use Auth;
use DB;

/**
 *  DEBUGGING! CALLS! -RiAN ONLY! 10:21:51
 */
class AlController extends Controller
{
    public function index(Request $request){

      // $token = Helper::tokenCheck('n58ec5e159f769ae0b7b3a0774fdbf80');

      // // dd($token .' ' .Helper::datesent());
      // dd($token);

        // https://asset-dev.betrnk.games/images/games/casino/habanero/Habanero_12Zodiacs_384x216.png

        $gg = DB::table('games as g')
            ->where('provider_id', $request->provider_id)
            ->where('sub_provider_id', $request->subprovider)
            ->get();

        $array = array();  
        foreach($gg as $g){
            DB::table('games')
                   ->where('provider_id',$request->provider_id)
                   ->where('sub_provider_id',$request->subprovider)
                   ->where('game_id', $g->game_id)
                   ->update(['icon' => 'https://asset-dev.betrnk.games/images/games/casino/'.$request->prefix.'/'.$g->game_code.'.'.$request->extension.'']);
                   // ->update(['icon' => 'https://asset-dev.betrnk.games/images/casino/'.$request->prefix.'/eng/388x218/'.$g->game_code.'.jpg']);
                    
        }     
        return 'ok';    

    }


    public function checkCLientPlayer(Request $request){
        $client_details = Providerhelper::getClientDetails('token', $request->token);
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
        return $player_details;
    }

  // public function tapulan(){

  //     $array = '[
  //       {
  //           "gameId": 258,
  //           "gameName": "Turbo Play",
  //           "provider": "wazdan",
  //           "RTP": 96.1,
  //           "freeRoundsSupported": false,
  //           "widgetSupported": false,
  //           "goldenchipsSupported": false
  //       },
  //       {
  //           "gameId": 328030,
  //           "gameName": "Magic Of The Ring",
  //           "provider": "wazdan",
  //           "RTP": 96.47,
  //           "freeRoundsSupported": true,
  //           "widgetSupported": false,
  //           "goldenchipsSupported": false
  //       },
  //       {
  //           "gameId": 393566,
  //           "gameName": "Sizzling 70s",
  //           "provider": "wazdan",
  //           "RTP": 0,
  //           "freeRoundsSupported": true,
  //           "widgetSupported": false,
  //           "goldenchipsSupported": false
  //       }
  //   ]';

  //   // $someArray = json_decode($array, JSON_FORCE_OBJECT);
  //   $foo = utf8_encode($array);
  //   $data = json_decode($foo, true);

  //   $data2 = array();
  //   foreach($data as $g){
  //       // if($g['game_type_id'] == "Slot"){
  //       //   $game_type = 1;
  //       // }else if($g['game_type_id'] == "BlackJack"){
  //       //   $game_type = 5;
  //       // }else if($g['game_type_id'] == "Video"){
  //       //   $game_type = 12;
  //       // }else if($g['game_type_id'] == "Table"){
  //       //   $game_type = 5;
  //       // }else if($g['game_type_id'] == "Poker"){
  //       //   $game_type = 3;
  //       // }else if($g['game_type_id'] == "Roulette"){
  //       //   $game_type = 5;
  //       // }else if($g['game_type_id'] == "Fixed Odds"){
  //       //   $game_type = 17;
  //       // }

  //       $game = array(
  //           "game_type_id"=> 1,
  //           "provider_id"=> 33,
  //           "sub_provider_id"=> 57,
  //           "game_name"=> $g['gameName'],
  //           "game_code"=>$g["gameId"],
  //           "icon"=> 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcS9Oza5sOOuv12mmaLfvpzkjoCKTx2oFKbpPQ&usqp=CAU'
  //       );
  //       array_push($data2,$game);
  //   }
  //   DB::table('games')->insert($data2);
  //   return 'OK';
  // }

    // public function tapulan(){
    //   $array = '[{
    //     "game_code": "SGHeySushi",
    //     "game_name": "Hey Sushi",
    //     "provider_id": "24",
    //     "sub_provider": "47",
    //     "game_type_id": "1",
    //     "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
    //   },
    //   {
    //     "game_code": "TensorBetter100Hand",
    //     "game_name": "Tens Or Better 100 Hand",
    //     "provider_id": "24",
    //     "sub_provider": "47",
    //     "game_type_id": "1",
    //     "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
    //   }
    // ]';

    //   // $someArray = json_decode($array, JSON_FORCE_OBJECT);
    //   $foo = utf8_encode($array);
    //   $data = json_decode($foo, true);

    //   $data2 = array();
    //   foreach($data as $g){
    //       $game = array(
    //           "game_type_id"=>1,
    //           "provider_id"=>$g['provider_id'],
    //           "sub_provider_id"=> $g['sub_provider'],
    //           "game_name"=> $g['game_name'],
    //           "game_code"=>$g["game_code"],
    //           "icon"=>$g["icon"]
    //       );
    //       array_push($data2,$game);
    //   }
    //   DB::table('games')->insert($data2);
    //   return 'ok';
  // }

  // public function insertGamesTapulanMode(Request $request){
  //     $games = file_get_contents("http://api.prerelease-env.biz/IntegrationService/v3/http/CasinoGameAPI/getCasinoGames/?secureLogin=tg_tigergames&hash=502c379dee1413a935959d072c9a2b35");
  //      $obj = json_decode($games);
      
  //      foreach($obj->gameList as $item){
  //         // echo "gameID: ". $item->gameID."<br>";
  //         // echo "gameName: ". $item->gameName."<br>";
  //         // echo "typeDescription: ". $item->typeDescription."<br><br><br>";


  //         // echo "http://api.prerelease-env.biz/game_pic/rec/325/".$item->gameID.".png<br>";

  //         $insert = DB::table('games')->where("provider_id","=","26")->where("sub_provider_id","=","49")->where("game_code","=",$item->gameID)->update([
  //             "game_type_id" => "1",
  //             "provider_id" => "26",
  //             "sub_provider_id" => "49",
  //             "game_name" => $item->gameName,
  //             "icon" => "https://asset-dev.betrnk.games/images/games/casino/PragmaticPlay/".$item->gameID.".png",
  //             "game_code" => $item->gameID,
  //             "on_maintenance" => "0"
  //         ]);
  //      }

  // }


}
