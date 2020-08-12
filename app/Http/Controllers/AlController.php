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
      "game_name": "3-Hand Casino Holdem",
      "game_code": "threehandholdemmobile",
      "game_type_id": "Table",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "5x Magic",
      "game_code": "5xmagicmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "7 Sins",
      "game_code": "sevensinsmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Ace of Spades",
      "game_code": "aceofspadesmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Agent Destiny",
      "game_code": "agentdestinymobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Ankh of Anubis",
      "game_code": "ankhofanubismobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Annihilator",
      "game_code": "annihilatormobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Aztec Idols",
      "game_code": "aztecidolsmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Aztec Warrior Princess",
      "game_code": "aztecwarriorprincessmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Bakers Treat",
      "game_code": "bakerstreatmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Banana Rock",
      "game_code": "bananarockmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Battle Royal",
      "game_code": "battleroyalmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Bell of Fortune",
      "game_code": "belloffortunemobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Big Win 777",
      "game_code": "bigwin777mobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Big Win Cat",
      "game_code": "bigwincatmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Black Mamba",
      "game_code": "blackmambamobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "BlackJack MH",
      "game_code": "blackjackmhmobile",
      "game_type_id": "BlackJack",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Blinged",
      "game_code": "blingedmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Book of Dead",
      "game_code": "bookofdeadmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Bugs Party",
      "game_code": "bugspartymobile",
      "game_type_id": "Video",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Cash Pump",
      "game_code": "cashpumpmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Cash Vandal",
      "game_code": "cashvandalmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Casino Holdem",
      "game_code": "casinoholdemmobile",
      "game_type_id": "Table",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Casino Stud Poker",
      "game_code": "casinostudpokermobile",
      "game_type_id": "Table",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Cat Wilde and the Doom of Dead",
      "game_code": "doomofdeadmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Cats and Cash",
      "game_code": "catsandcashmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Charlie Chance in Hell to Pay",
      "game_code": "charliechancehelltopaymobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Chinese New Year",
      "game_code": "chinesenewyearmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Chronos Joker",
      "game_code": "chronosjokermobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Cloud Quest",
      "game_code": "cloudquestmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Contact",
      "game_code": "contactmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "CopsnRobbers",
      "game_code": "copsnrobbersmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Coywolf Cash",
      "game_code": "coywolfcashmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Crazy Cows",
      "game_code": "crazycowsmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Crystal Sun",
      "game_code": "crystalsunmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Dawn of Egypt",
      "game_code": "dawnofegyptmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Demon",
      "game_code": "demonmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Deuces Wild MH",
      "game_code": "deuceswildmobile",
      "game_type_id": "Poker",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Diamond Vortex",
      "game_code": "diamondvortexmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Divine Showdown",
      "game_code": "divineshowdownmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Doom of Egypt",
      "game_code": "doomofegyptmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Double Exposure BlackJack MH",
      "game_code": "doubleexposureblackjackmhmobile",
      "game_type_id": "BlackJack",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Dragon Maiden",
      "game_code": "dragonmaidenmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Dragon Ship",
      "game_code": "dragonshipmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Easter Eggs",
      "game_code": "eastereggsmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Enchanted Crystals",
      "game_code": "enchantedcrystalsmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Enchanted Meadow",
      "game_code": "enchantedmeadowmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Energoonz",
      "game_code": "energoonzmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "European BlackJack MH",
      "game_code": "europeanblackjackmhmobile",
      "game_type_id": "BlackJack",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "European Roulette Pro",
      "game_code": "europeanroulettemobile",
      "game_type_id": "Roulette",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Eye of the Kraken",
      "game_code": "eyeofthekrakenmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Feline Fury",
      "game_code": "felinefurymobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Fire Joker",
      "game_code": "firejokermobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Firefly Frenzy",
      "game_code": "fireflyfrenzymobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Flying Pigs",
      "game_code": "flyingpigsmobile",
      "game_type_id": "Video",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Fortune Teller",
      "game_code": "fortunetellermobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Fortunes of Ali Baba",
      "game_code": "fortunesofalibabamobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Fruit Bonanza",
      "game_code": "fruitbonanzamobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "FU ER DAI",
      "game_code": "fuerdaimobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Game of Gladiators",
      "game_code": "gameofgladiatorsmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Gemix",
      "game_code": "gemixmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Gift Shop",
      "game_code": "giftshopmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Gold King",
      "game_code": "goldkingmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Gold Trophy 2",
      "game_code": "goldtrophy2mobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Gold Volcano",
      "game_code": "goldvolcanomobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Golden Caravan",
      "game_code": "goldencaravanmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Golden Colts",
      "game_code": "goldencoltsmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Golden Goal",
      "game_code": "goldengoalmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Golden Legend",
      "game_code": "goldenlegendmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Golden Ticket",
      "game_code": "goldenticketmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Golden Ticket 2",
      "game_code": "goldenticket2mobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Grim Muerto",
      "game_code": "grimmuertomobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Gunslinger: Reloaded",
      "game_code": "gunslingerreloadedmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Happy Halloween",
      "game_code": "happyhalloweenmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Helloween",
      "game_code": "helloweenmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Holiday Season",
      "game_code": "holidayseasonmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Honey Rush",
      "game_code": "honeyrushmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "House of Doom",
      "game_code": "houseofdoommobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Hugo",
      "game_code": "hugomobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Hugo 2",
      "game_code": "hugotwomobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Hugo Goal",
      "game_code": "hugogoalmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Hugos Adventure",
      "game_code": "hugosadventuremobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Imperial Opera",
      "game_code": "imperialoperamobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Inferno Joker",
      "game_code": "infernojokermobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Inferno Star",
      "game_code": "infernostarmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Irish Gold",
      "game_code": "irishgoldmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Iron Girl",
      "game_code": "irongirlmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Jackpot Poker",
      "game_code": "jackpotpokermobile",
      "game_type_id": "Poker",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Jacks or Better MH",
      "game_code": "jacksorbettermobile",
      "game_type_id": "Poker",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Jade Magician",
      "game_code": "jademagicianmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Jewel Box",
      "game_code": "jewelboxmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Joker Poker MH",
      "game_code": "jokerpokermobile",
      "game_type_id": "Poker",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Jolly Roger",
      "game_code": "jollyrogermobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Keno",
      "game_code": "kenomobile",
      "game_type_id": "Fixed Odds",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Lady of Fortune",
      "game_code": "ladyoffortunemobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Legacy of Dead",
      "game_code": "legacyofdeadmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Legacy of Egypt",
      "game_code": "legacyofegyptmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Leprechaun goes Egypt",
      "game_code": "leprechaungoesegyptmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Leprechaun goes to Hell",
      "game_code": "leprechaungoestohellmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Leprechaun Goes Wild",
      "game_code": "leprechaungoeswildmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Lucky Diamonds",
      "game_code": "luckydiamondsmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Madame Ink",
      "game_code": "madameinkmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Mahjong 88",
      "game_code": "mahjong88mobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Matsuri",
      "game_code": "matsurimobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Mermaids Diamond",
      "game_code": "mermaidsdiamondmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Merry Xmas",
      "game_code": "merryxmasmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Mini Baccarat",
      "game_code": "minibaccaratmobile",
      "game_type_id": "Table",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Mission Cash",
      "game_code": "missioncashmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Money Wheel",
      "game_code": "moneywheelmobile",
      "game_type_id": "Table",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Moon Princess",
      "game_code": "moonprincessmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "MULTIFRUIT 81",
      "game_code": "multifruit81mobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Mystery Joker",
      "game_code": "mysteryjokermobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Mystery Joker 6000",
      "game_code": "mysteryjoker6000mobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Myth",
      "game_code": "mythmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Ninja Fruits",
      "game_code": "ninjafruitsmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Nyjah Huston: Skate for Gold",
      "game_code": "skateforgoldmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Pearl Lagoon",
      "game_code": "pearllagoonmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Pearls of India",
      "game_code": "pearlsofindiamobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Perfect Gems",
      "game_code": "perfectgemsmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Phoenix Reborn",
      "game_code": "phoenixrebornmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Photo Safari",
      "game_code": "photosafarimobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Pimped",
      "game_code": "pimpedmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Planet Fortune",
      "game_code": "planetfortunemobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Prissy Princess",
      "game_code": "prissyprincessmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Prosperity Palace",
      "game_code": "prosperitypalacemobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Queens Day Tilt",
      "game_code": "queensdaytiltmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Rage to Riches",
      "game_code": "ragetorichesmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Raging Rex",
      "game_code": "ragingrexmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Rainforest Magic",
      "game_code": "rainforestmagicmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Rainforest Magic Bingo",
      "game_code": "rainforestmagicbingomobile",
      "game_type_id": "Video",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Rally 4 Riches",
      "game_code": "rally4richesmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Reactoonz",
      "game_code": "reactoonzmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Reactoonz 2",
      "game_code": "reactoonztwomobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Rich Wilde & The Shield of Athena",
      "game_code": "shieldofathenamobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Riches of RA",
      "game_code": "richesoframobile \r\n",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Riches of Robin",
      "game_code": "richesofrobinmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Riddle Reels: A Case of Riches",
      "game_code": "riddlereelsmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Ring of Odin",
      "game_code": "ringofodinmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Rise of Dead",
      "game_code": "riseofdeadmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Rise of Merlin",
      "game_code": "riseofmerlinmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Rise of Olympus",
      "game_code": "riseofolympusmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Royal Masquerade",
      "game_code": "masquerademobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Sabaton",
      "game_code": "sabatonmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Sails of Gold",
      "game_code": "sailsofgoldmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Samba Carnival",
      "game_code": "sambacarnivalmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Saxon",
      "game_code": "saxonmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Sea Hunter",
      "game_code": "seahuntermobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Single Deck BlackJack MH",
      "game_code": "singledeckblackjackmhmobile",
      "game_type_id": "BlackJack",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Sizzling Spins",
      "game_code": "sizzlingspinsmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Space Race",
      "game_code": "spaceracemobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Speed Cash",
      "game_code": "speedcashmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Spin Party",
      "game_code": "spinpartymobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Star Joker",
      "game_code": "starjokermobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Sticky Joker",
      "game_code": "stickyjokermobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Street Magic",
      "game_code": "streetmagicmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Super Flip",
      "game_code": "superflipmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Super Wheel",
      "game_code": "superwheelmobile",
      "game_type_id": "Table",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Sweet 27",
      "game_code": "sweet27mobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Sweet Alchemy",
      "game_code": "sweetalchemymobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Sweet Alchemy Bingo",
      "game_code": "sweetalchemybingomobile",
      "game_type_id": "Video",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Testament",
      "game_code": "testamentmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Thats Rich",
      "game_code": "thatsrichmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "The Sword and The Grail",
      "game_code": "theswordandthegrailmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Tome of Madness",
      "game_code": "tomeofmadnessmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Tower Quest",
      "game_code": "towerquestmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Troll Hunters",
      "game_code": "trollhuntersmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Troll Hunters 2",
      "game_code": "trollhunters2mobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Twisted Sister",
      "game_code": "twistedsistermobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Viking Runecraft",
      "game_code": "vikingrunecraftmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Wild Blood",
      "game_code": "wildbloodmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Wild Blood 2",
      "game_code": "wildblood2mobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Wild Falls",
      "game_code": "wildfallsmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Wild Frames",
      "game_code": "wildframesmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Wild Melon",
      "game_code": "wildmelonmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Wild North",
      "game_code": "wildnorthmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Wild Rails",
      "game_code": "wildrailsmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Wildhound Derby",
      "game_code": "wildhoundderbymobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Win-A-Beest",
      "game_code": "winabeestmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Wizard of Gems",
      "game_code": "wizardofgemsmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Xmas Joker",
      "game_code": "christmasjokermobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Xmas Magic",
      "game_code": "xmasmagicmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Celebration of Wealth",
      "game_code": "celebrationofwealthmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Temple of Wealth ",
      "game_code": "templeofwealthmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Beast of Wealth",
      "game_code": "beastofwealthmobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Jolly Roger 2",
      "game_code": "jollyroger2mobile",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    }
  ]';

    // $someArray = json_decode($array, JSON_FORCE_OBJECT);
    $foo = utf8_encode($array);
    $data = json_decode($foo, true);

    $data2 = array();
    foreach($data as $g){
        if($g['game_type_id'] == "Slot"){
          $game_type = 1;
        }else if($g['game_type_id'] == "BlackJack"){
          $game_type = 5;
        }else if($g['game_type_id'] == "Video"){
          $game_type = 12;
        }else if($g['game_type_id'] == "Table"){
          $game_type = 5;
        }else if($g['game_type_id'] == "Poker"){
          $game_type = 3;
        }else if($g['game_type_id'] == "Roulette"){
          $game_type = 5;
        }else if($g['game_type_id'] == "Fixed Odds"){
          $game_type = 17;
        }

        $game = array(
            "game_type_id"=> $game_type,
            "provider_id"=>$g['provider_id'],
            "sub_provider_id"=> $g['sub_provider_id'],
            "game_name"=> $g['game_name'],
            "game_code"=>$g["game_code"],
            "icon"=>$g["icon"]
        );
        array_push($data2,$game);
    }
    // DB::table('games')->insert($data2);
    return $data2;
  }

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
