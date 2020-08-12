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
      "game_code": "threehandholdem",
      "game_type_id": "Table",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "5x Magic",
      "game_code": "5xmagic",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "7 Sins",
      "game_code": "sevensins",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Ace of Spades",
      "game_code": "aceofspades",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Agent Destiny",
      "game_code": "agentdestiny",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Ankh of Anubis",
      "game_code": "ankhofanubis",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Annihilator",
      "game_code": "annihilator",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Aztec Idols",
      "game_code": "aztecidols",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Aztec Warrior Princess",
      "game_code": "aztecwarriorprincess",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Bakers Treat",
      "game_code": "bakerstreat",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Banana Rock",
      "game_code": "bananarock",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Battle Royal",
      "game_code": "battleroyal",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Bell of Fortune",
      "game_code": "belloffortune",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Big Win 777",
      "game_code": "bigwin777",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Big Win Cat",
      "game_code": "bigwincat",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Black Mamba",
      "game_code": "blackmamba",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "BlackJack MH",
      "game_code": "blackjackmh",
      "game_type_id": "BlackJack",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Blinged",
      "game_code": "blinged",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Book of Dead",
      "game_code": "bookofdead",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Bugs Party",
      "game_code": "bugsparty",
      "game_type_id": "Video",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Cash Pump",
      "game_code": "cashpump",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Cash Vandal",
      "game_code": "cashvandal",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Casino Holdem",
      "game_code": "casinoholdem",
      "game_type_id": "Table",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Casino Stud Poker",
      "game_code": "casinostudpoker",
      "game_type_id": "Table",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Cat Wilde and the Doom of Dead",
      "game_code": "doomofdead",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Cats and Cash",
      "game_code": "catsandcash",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Charlie Chance in Hell to Pay",
      "game_code": "charliechancehelltopay",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Chinese New Year",
      "game_code": "chinesenewyear",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Chronos Joker",
      "game_code": "chronosjoker",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Cloud Quest",
      "game_code": "cloudquest",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Contact",
      "game_code": "contact",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "CopsnRobbers",
      "game_code": "copsnrobbers",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Coywolf Cash",
      "game_code": "coywolfcash",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Crazy Cows",
      "game_code": "crazycows",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Crystal Sun",
      "game_code": "crystalsun",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Dawn of Egypt",
      "game_code": "dawnofegypt",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Demon",
      "game_code": "demon",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Deuces Wild MH",
      "game_code": "deuceswild",
      "game_type_id": "Poker",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Diamond Vortex",
      "game_code": "diamondvortex",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Divine Showdown",
      "game_code": "divineshowdown",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Doom of Egypt",
      "game_code": "doomofegypt",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Double Exposure BlackJack MH",
      "game_code": "doubleexposureblackjackmh",
      "game_type_id": "BlackJack",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Dragon Maiden",
      "game_code": "dragonmaiden",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Dragon Ship",
      "game_code": "dragonship",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Easter Eggs",
      "game_code": "eastereggs",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Enchanted Crystals",
      "game_code": "enchantedcrystals",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Enchanted Meadow",
      "game_code": "enchantedmeadow",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Energoonz",
      "game_code": "energoonz",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "European BlackJack MH",
      "game_code": "europeanblackjackmh",
      "game_type_id": "BlackJack",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "European Roulette Pro",
      "game_code": "europeanroulette",
      "game_type_id": "Roulette",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Eye of the Kraken",
      "game_code": "eyeofthekraken",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Feline Fury",
      "game_code": "felinefury",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Fire Joker",
      "game_code": "firejoker",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Firefly Frenzy",
      "game_code": "fireflyfrenzy",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Flying Pigs",
      "game_code": "flyingpigs",
      "game_type_id": "Video",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Fortune Teller",
      "game_code": "fortuneteller",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Fortunes of Ali Baba",
      "game_code": "fortunesofalibaba",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Fruit Bonanza",
      "game_code": "fruitbonanza",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "FU ER DAI",
      "game_code": "fuerdai",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Game of Gladiators",
      "game_code": "gameofgladiators",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Gemix",
      "game_code": "gemix",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Gift Shop",
      "game_code": "giftshop",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Gold King",
      "game_code": "goldking",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Gold Trophy 2",
      "game_code": "goldtrophy2",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Gold Volcano",
      "game_code": "goldvolcano",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Golden Caravan",
      "game_code": "goldencaravan",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Golden Colts",
      "game_code": "goldencolts",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Golden Goal",
      "game_code": "goldengoal",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Golden Legend",
      "game_code": "goldenlegend",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Golden Ticket",
      "game_code": "goldenticket",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Golden Ticket 2",
      "game_code": "goldenticket2",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Grim Muerto",
      "game_code": "grimmuerto",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Gunslinger: Reloaded",
      "game_code": "gunslingerreloaded",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Happy Halloween",
      "game_code": "happyhalloween",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Helloween",
      "game_code": "helloween",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Holiday Season",
      "game_code": "holidayseason",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Honey Rush",
      "game_code": "honeyrush",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "House of Doom",
      "game_code": "houseofdoom",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Hugo",
      "game_code": "hugo",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Hugo 2",
      "game_code": "hugotwo",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Hugo Goal",
      "game_code": "hugogoal",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Hugos Adventure",
      "game_code": "hugosadventure",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Imperial Opera",
      "game_code": "imperialopera",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Inferno Joker",
      "game_code": "infernojoker",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Inferno Star",
      "game_code": "infernostar",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Irish Gold",
      "game_code": "irishgold",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Iron Girl",
      "game_code": "irongirl",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Jackpot Poker",
      "game_code": "jackpotpoker",
      "game_type_id": "Poker",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Jacks or Better MH",
      "game_code": "jacksorbetter",
      "game_type_id": "Poker",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Jade Magician",
      "game_code": "jademagician",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Jewel Box",
      "game_code": "jewelbox",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Joker Poker MH",
      "game_code": "jokerpoker",
      "game_type_id": "Poker",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Jolly Roger",
      "game_code": "jollyroger",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Keno",
      "game_code": "keno",
      "game_type_id": "Fixed Odds",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Lady of Fortune",
      "game_code": "ladyoffortune",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Legacy of Dead",
      "game_code": "legacyofdead",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Legacy of Egypt",
      "game_code": "legacyofegypt",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Leprechaun goes Egypt",
      "game_code": "leprechaungoesegypt",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Leprechaun goes to Hell",
      "game_code": "leprechaungoestohell",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Leprechaun Goes Wild",
      "game_code": "leprechaungoeswild",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Lucky Diamonds",
      "game_code": "luckydiamonds",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Madame Ink",
      "game_code": "madameink",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Mahjong 88",
      "game_code": "mahjong88",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Matsuri",
      "game_code": "matsuri",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Mermaids Diamond",
      "game_code": "mermaidsdiamond",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Merry Xmas",
      "game_code": "merryxmas",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Mini Baccarat",
      "game_code": "minibaccarat",
      "game_type_id": "Table",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Mission Cash",
      "game_code": "missioncash",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Money Wheel",
      "game_code": "moneywheel",
      "game_type_id": "Table",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Moon Princess",
      "game_code": "moonprincess",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "MULTIFRUIT 81",
      "game_code": "multifruit81",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Mystery Joker",
      "game_code": "mysteryjoker",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Mystery Joker 6000",
      "game_code": "mysteryjoker6000",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Myth",
      "game_code": "myth",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Ninja Fruits",
      "game_code": "ninjafruits",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Nyjah Huston: Skate for Gold",
      "game_code": "skateforgold",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Pearl Lagoon",
      "game_code": "pearllagoon",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Pearls of India",
      "game_code": "pearlsofindia",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Perfect Gems",
      "game_code": "perfectgems",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Phoenix Reborn",
      "game_code": "phoenixreborn",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Photo Safari",
      "game_code": "photosafari",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Pimped",
      "game_code": "pimped",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Planet Fortune",
      "game_code": "planetfortune",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Prissy Princess",
      "game_code": "prissyprincess",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Prosperity Palace",
      "game_code": "prosperitypalace",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Queens Day Tilt",
      "game_code": "queensdaytilt",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Rage to Riches",
      "game_code": "ragetoriches",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Raging Rex",
      "game_code": "ragingrex",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Rainforest Magic",
      "game_code": "rainforestmagic",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Rainforest Magic Bingo",
      "game_code": "rainforestmagicbingo",
      "game_type_id": "Video",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Rally 4 Riches",
      "game_code": "rally4riches",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Reactoonz",
      "game_code": "reactoonz",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Reactoonz 2",
      "game_code": "reactoonztwo",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Rich Wilde & The Shield of Athena",
      "game_code": "shieldofathena",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Riches of RA",
      "game_code": "richesofra \r\n",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Riches of Robin",
      "game_code": "richesofrobin",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Riddle Reels: A Case of Riches",
      "game_code": "riddlereels",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Ring of Odin",
      "game_code": "ringofodin",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Rise of Dead",
      "game_code": "riseofdead",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Rise of Merlin",
      "game_code": "riseofmerlin",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Rise of Olympus",
      "game_code": "riseofolympus",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Royal Masquerade",
      "game_code": "masquerade",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Sabaton",
      "game_code": "sabaton",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Sails of Gold",
      "game_code": "sailsofgold",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Samba Carnival",
      "game_code": "sambacarnival",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Saxon",
      "game_code": "saxon",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Sea Hunter",
      "game_code": "seahunter",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Single Deck BlackJack MH",
      "game_code": "singledeckblackjackmh",
      "game_type_id": "BlackJack",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Sizzling Spins",
      "game_code": "sizzlingspins",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Space Race",
      "game_code": "spacerace",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Speed Cash",
      "game_code": "speedcash",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Spin Party",
      "game_code": "spinparty",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Star Joker",
      "game_code": "starjoker",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Sticky Joker",
      "game_code": "stickyjoker",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Street Magic",
      "game_code": "streetmagic",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Super Flip",
      "game_code": "superflip",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Super Wheel",
      "game_code": "superwheel",
      "game_type_id": "Table",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Sweet 27",
      "game_code": "sweet27",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Sweet Alchemy",
      "game_code": "sweetalchemy",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Sweet Alchemy Bingo",
      "game_code": "sweetalchemybingo",
      "game_type_id": "Video",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Testament",
      "game_code": "testament",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Thats Rich",
      "game_code": "thatsrich",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "The Sword and The Grail",
      "game_code": "theswordandthegrail",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Tome of Madness",
      "game_code": "tomeofmadness",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Tower Quest",
      "game_code": "towerquest",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Troll Hunters",
      "game_code": "trollhunters",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Troll Hunters 2",
      "game_code": "trollhunters2",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Twisted Sister",
      "game_code": "twistedsister",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Viking Runecraft",
      "game_code": "vikingrunecraft",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Wild Blood",
      "game_code": "wildblood",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Wild Blood 2",
      "game_code": "wildblood2",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Wild Falls",
      "game_code": "wildfalls",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Wild Frames",
      "game_code": "wildframes",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Wild Melon",
      "game_code": "wildmelon",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Wild North",
      "game_code": "wildnorth",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Wild Rails",
      "game_code": "wildrails",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Wildhound Derby",
      "game_code": "wildhoundderby",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Win-A-Beest",
      "game_code": "winabeest",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Wizard of Gems",
      "game_code": "wizardofgems",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Xmas Joker",
      "game_code": "christmasjoker",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Xmas Magic",
      "game_code": "xmasmagic",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Celebration of Wealth",
      "game_code": "celebrationofwealth",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Temple of Wealth ",
      "game_code": "templeofwealth",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Beast of Wealth",
      "game_code": "beastofwealth",
      "game_type_id": "Slot",
      "icon": "https://onlinegambling.com.ph/wp-content/uploads/2019/06/play-n-go.jpg",
      "provider_id": "32",
      "sub_provider_id": "57"
    },
    {
      "game_name": "Jolly Roger 2",
      "game_code": "jollyroger2",
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
    DB::table('games')->insert($data2);
    return 'OK';
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
