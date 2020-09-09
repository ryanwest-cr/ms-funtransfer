<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\AlHelper;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use GuzzleHttp\Client;
use App\Helpers\ClientRequestHelper;
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

  public function tapulan(){

  $array = '[{
    "game_code": "rng-topcard00001_rng-topcard",
    "game_name": "First Person Top Card",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "vctlz20yfnmp1ylr_roulette",
    "game_name": "Roulette",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "n5emwq5c5dwepwam_tcp",
    "game_name": "Three Card Poker",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "rngbaccarat00000_rng-baccarat",
    "game_name": "First Person Baccarat",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "uwd2bl2khwcikjlz_blackjack",
    "game_name": "Blackjack A",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "MOWDream00000001_moneywheel",
    "game_name": "Dream Catcher",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "CrazyTime0000001_crazytime",
    "game_name": "Crazy Time",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "Craps00000000001_craps",
    "game_name": "Craps DNT",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "SBCTable00000001_sidebetcity",
    "game_name": "Side Bet City",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "CSPTable00000001_csp",
    "game_name": "Caribbean Stud Poker",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "zixzea8nrf1675oh_baccarat",
    "game_name": "Baccarat Squeeze",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "FreeBet000000001_freebetblackjack",
    "game_name": "Free Bet Blackjack",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "DragonTiger00001_dragontiger",
    "game_name": "Dragon Tiger",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "rng-dragontiger0_rng-dragontiger",
    "game_name": "First Person Dragon Tiger",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "rng-rt-lightning_rngeuropeanroulette",
    "game_name": "RNG European Lightning DNT",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "DHPTable00000001_dhp",
    "game_name": "2 Hand Casino Holdem",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "NoCommBac0000001_baccarat",
    "game_name": "No Commission Baccarat",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "oregywu3qpxaaqp2_roulette",
    "game_name": "French Roulette Gold",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "9dxyqtvp0rjqvu6r_roulette",
    "game_name": "Immersive Roulette",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "Monopoly00000001_monopoly",
    "game_name": "MONOPOLY Live",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "cpxl81x0rgi34cmo_blackjack",
    "game_name": "Blackjack VIP B",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "LightningTable01_roulette",
    "game_name": "Lightning Roulette",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "k2oswnib7jjaaznw_baccarat",
    "game_name": "Baccarat Control Squeeze",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "rng-rt-european0_rngeuropeanroulette",
    "game_name": "First Person Roulette",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "sni5cza6d1vvl50i_blackjack",
    "game_name": "Blackjack Party",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "LightningBac0001_baccarat",
    "game_name": "Lightning Baccarat",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "TRPTable00000001_trp",
    "game_name": "Triple Card Poker",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "DoubleBallRou001_roulette",
    "game_name": "Double Ball Roulette",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "48z5pjps3ntvqc1b_roulette",
    "game_name": "Auto-Roulette",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "HoldemTable00001_holdem",
    "game_name": "Casino Holdem",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "InstantRo0000001_instantroulette",
    "game_name": "Instant Roulette",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "dealnodeal000001_dealnodeal",
    "game_name": "Deal or No Deal",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "rng-bj-standard0_rngblackjack",
    "game_name": "First Person Blackjack",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "MegaBall00000001_megaball",
    "game_name": "Mega Ball",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "LightningDice001_lightningdice",
    "game_name": "Lightning Dice",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "lyhldhsafw61m7jx_blackjack",
    "game_name": "Blackjack B",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "THBTable00000001_thb",
    "game_name": "Texas Holdem Bonus Poker",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "RngMegaBall00001_rng-megaball",
    "game_name": "First Person Mega Ball",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "g48qnr279zcxecmd_blackjack",
    "game_name": "Blackjack C",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "oytmvb9m1zysmc44_baccarat",
    "game_name": "Baccarat A",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "ETHTable00000001_eth",
    "game_name": "Extreme Texas Holdem",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "mrfykemt5slanyi5_scalableblackjack",
    "game_name": "Infinite Blackjack",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "UTHTable00000001_uth",
    "game_name": "Ultimate Texas Holdem",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "TopCard000000001_topcard",
    "game_name": "Football studio",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "SuperSicBo000001_sicbo",
    "game_name": "Super Sic Bo",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "PowerInfiniteBJ1_powerscalableblackjack",
    "game_name": "Power Blackjack",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "AmericanTable001_americanroulette",
    "game_name": "American Roulette",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "rng-dreamcatcher_rngmoneywheel",
    "game_name": "First Person Dream Catcher",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "RngCraps00000001_rng-craps",
    "game_name": "First Person Craps",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "lvgmxv5gv5tsz43z_blackjack",
    "game_name": "Blackjack VIP A",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  },
  {
    "game_code": "wzg6kdkad1oe7m5k_roulette",
    "game_name": "VIP Roulette",
    "provider_id": 42,
    "sub_provider": 74,
    "game_type": 5
  }
]';


$someArray = json_decode($array, JSON_FORCE_OBJECT);
$foo = utf8_encode($array);
$data = json_decode($foo, true);

  $data2 = array();
  foreach($data as $g){
      $game = array(
          "game_type_id"=> $g['game_type'],
          "provider_id"=> $g['provider_id'],
          "sub_provider_id"=> $g['sub_provider'],
          "game_name"=> $g['game_name'],
          "game_code"=>$g["game_code"],
          "icon"=> 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcS9Oza5sOOuv12mmaLfvpzkjoCKTx2oFKbpPQ&usqp=CAU'
      );
      array_push($data2,$game);
  }
  DB::table('games')->insert($data2);
  return 'OK';
}

  //   public function tapulan(){
  //     $array = '[{
  //       "game_code": "SGHeySushi",
  //       "game_name": "Hey Sushi",
  //       "provider_id": "24",
  //       "sub_provider": "47",
  //       "game_type_id": "1",
  //       "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  //     },
  //     {
  //       "game_code": "TensorBetter100Hand",
  //       "game_name": "Tens Or Better 100 Hand",
  //       "provider_id": "24",
  //       "sub_provider": "47",
  //       "game_type_id": "1",
  //       "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  //     }
  //   ]';

  //     // $someArray = json_decode($array, JSON_FORCE_OBJECT);
  //     $foo = utf8_encode($array);
  //     $data = json_decode($foo, true);

  //     $data2 = array();
  //     foreach($data as $g){
  //         $game = array(
  //             "game_type_id"=>1,
  //             "provider_id"=>$g['provider_id'],
  //             "sub_provider_id"=> $g['sub_provider'],
  //             "game_name"=> $g['game_name'],
  //             "game_code"=>$g["game_code"],
  //             "icon"=>$g["icon"]
  //         );
  //         array_push($data2,$game);
  //     }
  //     DB::table('games')->insert($data2);
  //     return 'ok';
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
    public function testTransaction(){
      return ClientRequestHelper::getTransactionId("43210","87654321");
    }

}
