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


    public function tapulan(){

      $array = '[{
    "game_code": "SGHeySushi",
    "game_name": "Hey Sushi",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGScopa",
    "game_name": "Scopa",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGTechnoTumble",
    "game_name": "Techno Tumble",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGLuckyFortuneCat",
    "game_name": "Lucky Fortune Cat",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGKnockoutFootballRush",
    "game_name": "Knockout Football Rush",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGLoonyBlox",
    "game_name": "Loony Blox",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGFaCaiShenDeluxe",
    "game_name": "Fa Cai Shen Deluxe",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGNaughtySanta",
    "game_name": "Naughty Santa",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGHotHotHalloween",
    "game_name": "Hot Hot Halloween",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGWizardsWantWar ",
    "game_name": "Wizards Want War!",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGColossalGems",
    "game_name": "Colossal Gems",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGNuwa",
    "game_name": "Nuwa",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGWildTrucks",
    "game_name": "Wild Trucks",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGLuckyLucky ",
    "game_name": "Lucky Lucky",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGMagicOak",
    "game_name": "Magic Oak",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGMountMazuma",
    "game_name": "Mount Mazuma",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SG5LuckyLions",
    "game_name": "5 Lucky Lions",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGHotHotFruit ",
    "game_name": "Hot Hot Fruit",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGHappiestChristmasTree",
    "game_name": "Happiest Christmas Tree",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGPumpkinPatch",
    "game_name": "Pumpkin Patch",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGJump",
    "game_name": "Jump!",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGEgyptianDreamsDeluxe",
    "game_name": "Egyptian Dreams Deluxe",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGWaysOfFortune",
    "game_name": "Ways Of Fortune",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGKnockoutFootball ",
    "game_name": "Knockout Football",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGFortuneDogs",
    "game_name": "Fortune Dogs",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGPresto",
    "game_name": "Presto!",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGLondonHunter",
    "game_name": "London Hunter",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGFourDivineBeasts",
    "game_name": "Four Divine Beasts",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SG5Mariachis",
    "game_name": "5 Mariachis",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGSantasVillage",
    "game_name": "Santas Village",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGRollingRoger",
    "game_name": "Rolling Roger",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGCakeValley",
    "game_name": "Cake Valley",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGScruffyScallywags",
    "game_name": "Scruffy Scallywags",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGTheDeadEscape",
    "game_name": "The Dead Escape",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGPandaPanda",
    "game_name": "Panda Panda",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGBirdOfThunder",
    "game_name": "Bird of Thunder",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGTheKoiGate",
    "game_name": "Koi Gate",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGFireRooster",
    "game_name": "Fire Rooster",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGFenghuang",
    "game_name": "Fenghuang",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGJugglenaut",
    "game_name": "Jugglenaut",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGOceansCall",
    "game_name": "Oceas Call ",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGGangsters",
    "game_name": "Gangsters",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGSparta",
    "game_name": "Sparta",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGSuperTwister",
    "game_name": "Super Twister",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SG12Zodiacs",
    "game_name": "12 Zodiacs",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGArcaneElements",
    "game_name": "Arcane Elements",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGDragonsThrone",
    "game_name": "Dragos Throne ",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGCoyoteCrash",
    "game_name": "Coyote Crash",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGRomanEmpire",
    "game_name": "Roman Empire",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGRuffledUp",
    "game_name": "Ruffled Up",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGFaCaiShen",
    "game_name": "Fa Cai Shen",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGBombsAway",
    "game_name": "Bombs Away",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGGoldRush",
    "game_name": "Gold Rush",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGWickedWitch",
    "game_name": "Wicked Witch",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGGalacticCash",
    "game_name": "Galactic Cash",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGBuggyBonus",
    "game_name": "Buggy Bonus",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGKanesInferno",
    "game_name": "Kans Inferno ",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGAllForOne",
    "game_name": "All For One",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGArcticWonders",
    "game_name": "Arctic Wonders",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGAzlandsGold",
    "game_name": "Aztlas Gold ",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGBarnstormerBucks",
    "game_name": "Barnstormer Bucks",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGBikiniIsland",
    "game_name": "Bikini Island",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGBlackbeardsBounty",
    "game_name": "Blackbears Bounty ",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGCarnivalCash",
    "game_name": "Carnival Cash",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGCashosaurus",
    "game_name": "Cashosaurus",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGCashReef",
    "game_name": "Cash Reef",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGDiscoFunk",
    "game_name": "Disco Funk",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGDoubleODollars",
    "game_name": "Double O Dollars",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGDragonsRealm",
    "game_name": "Dragos Realm ",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGDrFeelgood",
    "game_name": "Dr Feelgood",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGEgyptianDreams",
    "game_name": "Egyptian Dreams",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGFlyingHigh",
    "game_name": "Flying High",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGFrontierFortunes",
    "game_name": "Frontier Fortunes",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGGlamRock",
    "game_name": "Glam Rock",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGGoldenUnicorn",
    "game_name": "Golden Unicorn",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGGrapeEscape",
    "game_name": "Grape Escape",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGHauntedHouse",
    "game_name": "Haunted House",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGIndianCashCatcher",
    "game_name": "Indian Cash Catcher",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGJungleRumble",
    "game_name": "Jungle Rumble",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGKingTutsTomb",
    "game_name": "King Tus Tomb ",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGLittleGreenMoney",
    "game_name": "Little Green Money",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGMonsterMashCash",
    "game_name": "Monster Mash Cash",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGMrBling",
    "game_name": "Mr Bling",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGMummyMoney",
    "game_name": "Mummy Money",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGMysticFortune",
    "game_name": "Mystic Fortune",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGPamperMe",
    "game_name": "Pamper Me",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGPiratesPlunder",
    "game_name": "Pirats Plunder ",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGPoolShark",
    "game_name": "Pool Shark",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGPuckerUpPrince",
    "game_name": "Pucker Up Prince",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGQueenOfQueens1024",
    "game_name": "Queen of Queens II",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGQueenOfQueens243",
    "game_name": "Queen of Queens",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGRideEmCowboy",
    "game_name": "Rideem Cowboy ",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGRodeoDrive",
    "game_name": "Rodeo Drive",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGShaolinFortunes100",
    "game_name": "Shaolin Fortunes 100",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGShaolinFortunes243",
    "game_name": "Shaolin Fortunes",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGShogunsLand",
    "game_name": "Shogus Land ",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGSirBlingalot",
    "game_name": "Sir Blingalot",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGSkysTheLimit",
    "game_name": "Sks the Limit ",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGSOS",
    "game_name": "S.O.S!",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGSpaceFortune",
    "game_name": "Space Fortune",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGSuperStrike",
    "game_name": "Super Strike",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGTheBigDeal",
    "game_name": "The Big Deal",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGTheDragonCastle",
    "game_name": "Dragon Castle",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGTowerOfPizza",
    "game_name": "Tower Of Pizza",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGTreasureDiver",
    "game_name": "Treasure Diver",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGTreasureTomb",
    "game_name": "Treasure Tomb",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGVikingsPlunder",
    "game_name": "Vikins Plunder ",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGWeirdScience",
    "game_name": "Weird Science",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGZeus",
    "game_name": "Zeus",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SGZeus2",
    "game_name": "Zeus 2",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "CaribbeanHoldem",
    "game_name": "Caribbean HolEm ",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "CaribbeanStud",
    "game_name": "Caribbean Stud",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "BlackJack3H",
    "game_name": "Blackjack 3 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "BlackJack3HDoubleExposure",
    "game_name": "Blackjack Double Exposure 3 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "TGBlackjackAmerican",
    "game_name": "American Blackjack",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "EURoulette",
    "game_name": "European Roulette",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "AmericanBaccarat",
    "game_name": "American Baccarat",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "Baccarat3HZC",
    "game_name": "American Baccarat Zero Commission",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "SicBo",
    "game_name": "Sicbo",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "TGThreeCardPoker",
    "game_name": "Three Card Poker",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "TGThreeCardPokerDeluxe",
    "game_name": "Three Card Poker Deluxe",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "TGWar",
    "game_name": "War",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "TGDragonTiger",
    "game_name": "Dragon Tiger",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "AcesandEights1Hand",
    "game_name": "Aces & Eights 1 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "AcesandEights5Hand",
    "game_name": "Aces & Eights 5 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "AcesandEights10Hand",
    "game_name": "Aces & Eights 10 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "AcesandEights50Hand",
    "game_name": "Aces & Eights 50 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "AcesandEights100Hand",
    "game_name": "Aces & Eights 100 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "AllAmericanPoker1Hand",
    "game_name": "All American Poker 1 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "AllAmericanPoker5Hand",
    "game_name": "All American Poker 5 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "AllAmericanPoker10Hand",
    "game_name": "All American Poker 10 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "AllAmericanPoker50Hand",
    "game_name": "All American Poker 50 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "AllAmericanPoker100Hand",
    "game_name": "All American Poker 100 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "BonusDuecesWild1Hand",
    "game_name": "Bonus Deuces Wild 1 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "BonusDuecesWild5Hand",
    "game_name": "Bonus Deuces Wild 5 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "BonusDuecesWild10Hand",
    "game_name": "Bonus Deuces Wild 10 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "BonusDuecesWild50Hand",
    "game_name": "Bonus Deuces Wild 50 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "BonusDuecesWild100Hand",
    "game_name": "Bonus Deuces Wild 100 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "BonusPoker1Hand",
    "game_name": "Bonus Poker 1 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "BonusPoker5Hand",
    "game_name": "Bonus Poker 5 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "BonusPoker10Hand",
    "game_name": "Bonus Poker 10 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "BonusPoker50Hand",
    "game_name": "Bonus Poker 50 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "BonusPoker100Hand",
    "game_name": "Bonus Poker 100 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "DuecesWild1Hand",
    "game_name": "Deuces Wild 1 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "DuecesWild5Hand",
    "game_name": "Deuces Wild 5 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "DuecesWild10Hand",
    "game_name": "Deuces Wild 10 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "DuecesWild50Hand",
    "game_name": "Deuces Wild 50 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "DuecesWild100Hand",
    "game_name": "Deuces Wild 100 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "DoubleBonusPoker1Hand",
    "game_name": "Double Bonus Poker 1 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "DoubleBonusPoker5Hand",
    "game_name": "Double Bonus Poker 5 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "DoubleBonusPoker10Hand",
    "game_name": "Double Bonus Poker 10 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "DoubleBonusPoker50Hand",
    "game_name": "Double Bonus Poker 50 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "DoubleBonusPoker100Hand",
    "game_name": "Double Bonus Poker 100 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "DoubleDoubleBonusPoker1Hand",
    "game_name": "Double Double Bonus Poker 1 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "DoubleDoubleBonusPoker5Hand",
    "game_name": "Double Double Bonus Poker 5 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "DoubleDoubleBonusPoker10Hand",
    "game_name": "Double Double Bonus Poker 10 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "DoubleDoubleBonusPoker50Hand",
    "game_name": "Double Double Bonus Poker 50 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "DoubleDoubleBonusPoker100Hand",
    "game_name": "Double Double Bonus Poker 100 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "JacksorBetter1Hand",
    "game_name": "Jacks or Better 1 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "JacksorBetter5Hand",
    "game_name": "Jacks or Better 5 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "JacksorBetter10Hand",
    "game_name": "Jacks or Better 10 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "JacksorBetter50Hand",
    "game_name": "Jacks or Better 50 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "JacksorBetter100Hand",
    "game_name": "Jacks or Better 100 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "JokerPoker1Hand",
    "game_name": "Joker Poker 1 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "JokerPoker5Hand",
    "game_name": "Joker Poker 5 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "JokerPoker10Hand",
    "game_name": "Joker Poker 10 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "JokerPoker50Hand",
    "game_name": "Joker Poker 50 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "JokerPoker100Hand",
    "game_name": "Joker Poker 100 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "TensorBetter1Hand",
    "game_name": "Tens Or Better 1 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "TensorBetter5Hand",
    "game_name": "Tens Or Better 5 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "TensorBetter10Hand",
    "game_name": "Tens Or Better 10 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "TensorBetter50Hand",
    "game_name": "Tens Or Better 50 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  },
  {
    "game_code": "TensorBetter100Hand",
    "game_name": "Tens Or Better 100 Hand",
    "provider_id": "24",
    "sub_provider": "47",
    "game_type_id": "1",
    "icon": "https://asset-dev.betrnk.games/images/games/casino/Habanero/subproviders/HabaneroGaming/HabaneroGaming.png"
  }
]';

    // $someArray = json_decode($array, JSON_FORCE_OBJECT);
    $foo = utf8_encode($array);
    $data = json_decode($foo, true);

    $data2 = array();
    foreach($data as $g){
        $game = array(
            "game_type_id"=>1,
            "provider_id"=>$g['provider_id'],
            "sub_provider_id"=> $g['sub_provider'],
            "game_name"=> $g['game_name'],
            "game_code"=>$g["game_code"],
            "icon"=>$g["icon"]
        );
        array_push($data2,$game);
    }
    DB::table('games')->insert($data2);
    return 'ok';
  }
}
