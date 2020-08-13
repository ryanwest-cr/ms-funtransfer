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

      $array = '[
        {
            "gameId": 258,
            "gameName": "Turbo Play",
            "provider": "wazdan",
            "RTP": 96.1,
            "freeRoundsSupported": false,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 259,
            "gameName": "Arcade",
            "provider": "wazdan",
            "RTP": 96.62,
            "freeRoundsSupported": false,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 260,
            "gameName": "Vegas Reels II",
            "provider": "wazdan",
            "RTP": 96.16,
            "freeRoundsSupported": false,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 272,
            "gameName": "Magic Hot",
            "provider": "wazdan",
            "RTP": 96.35,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 273,
            "gameName": "Vegas Hot",
            "provider": "wazdan",
            "RTP": 96.05,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 274,
            "gameName": "Black Horse™",
            "provider": "wazdan",
            "RTP": 96.07,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 276,
            "gameName": "Fire Bird",
            "provider": "wazdan",
            "RTP": 96.05,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 278,
            "gameName": "Captain Shark™",
            "provider": "wazdan",
            "RTP": 96.25,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 280,
            "gameName": "Lucky Queen",
            "provider": "wazdan",
            "RTP": 96.17,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 282,
            "gameName": "Hot 777™",
            "provider": "wazdan",
            "RTP": 96.19,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 283,
            "gameName": "Magic Fruits 27",
            "provider": "wazdan",
            "RTP": 96.37,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 286,
            "gameName": "Wild Jack 81",
            "provider": "wazdan",
            "RTP": 96.73,
            "freeRoundsSupported": false,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 290,
            "gameName": "Mystery Jack",
            "provider": "wazdan",
            "RTP": 96.79,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 292,
            "gameName": "Magic Fruits 81",
            "provider": "wazdan",
            "RTP": 96.42,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 295,
            "gameName": "Cube Mania",
            "provider": "wazdan",
            "RTP": 96.43,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 296,
            "gameName": "Criss Cross 81",
            "provider": "wazdan",
            "RTP": 96.29,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 297,
            "gameName": "Highway to Hell",
            "provider": "wazdan",
            "RTP": 96.18,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 298,
            "gameName": "Corrida Romance",
            "provider": "wazdan",
            "RTP": 96.33,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 299,
            "gameName": "Wild Girls",
            "provider": "wazdan",
            "RTP": 96.53,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 300,
            "gameName": "Burning Stars",
            "provider": "wazdan",
            "RTP": 96.5,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 301,
            "gameName": "Joker Explosion",
            "provider": "wazdan",
            "RTP": 96.5,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 302,
            "gameName": "Super Hot",
            "provider": "wazdan",
            "RTP": 96.16,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 303,
            "gameName": "Magic Stars",
            "provider": "wazdan",
            "RTP": 96.25,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 305,
            "gameName": "Lost Treasure",
            "provider": "wazdan",
            "RTP": 96.13,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 306,
            "gameName": "Beach Party New",
            "provider": "wazdan",
            "RTP": 96.37,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 307,
            "gameName": "Miami Beach New",
            "provider": "wazdan",
            "RTP": 96.32,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 310,
            "gameName": "Lucky Fortune",
            "provider": "wazdan",
            "RTP": 96.3,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 311,
            "gameName": "Golden Sphinx",
            "provider": "wazdan",
            "RTP": 96.48,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 313,
            "gameName": "Triple Star",
            "provider": "wazdan",
            "RTP": 96.28,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 314,
            "gameName": "Good Luck 40",
            "provider": "wazdan",
            "RTP": 96.62,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 315,
            "gameName": "Win And Replay",
            "provider": "wazdan",
            "RTP": 96.99,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 316,
            "gameName": "Kick Off",
            "provider": "wazdan",
            "RTP": 96.42,
            "freeRoundsSupported": false,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 318,
            "gameName": "Night Club 81",
            "provider": "wazdan",
            "RTP": 95.91,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 319,
            "gameName": "Crazy Cars",
            "provider": "wazdan",
            "RTP": 96.27,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 321,
            "gameName": "Bell Wizard",
            "provider": "wazdan",
            "RTP": 96.5,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 323,
            "gameName": "Demon Jack 27",
            "provider": "wazdan",
            "RTP": 96.09,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 324,
            "gameName": "Beach Party Hot",
            "provider": "wazdan",
            "RTP": 96.24,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 325,
            "gameName": "Vegas Hot 81",
            "provider": "wazdan",
            "RTP": 96.35,
            "freeRoundsSupported": false,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 326,
            "gameName": "Welcome To Hell 81",
            "provider": "wazdan",
            "RTP": 96.35,
            "freeRoundsSupported": false,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 327,
            "gameName": "Jack on Hold",
            "provider": "wazdan",
            "RTP": 96.4,
            "freeRoundsSupported": false,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 332,
            "gameName": "Jacks Ride",
            "provider": "wazdan",
            "RTP": 96.4,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 333,
            "gameName": "Mayan Ritual™",
            "provider": "wazdan",
            "RTP": 96.29,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 336,
            "gameName": "Power of Gods The Pantheon",
            "provider": "wazdan",
            "RTP": 96.2,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 337,
            "gameName": "Lucky Fish",
            "provider": "wazdan",
            "RTP": 96.5,
            "freeRoundsSupported": false,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 338,
            "gameName": "Spectrum",
            "provider": "wazdan",
            "RTP": 96.3,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 339,
            "gameName": "Space Spins",
            "provider": "wazdan",
            "RTP": 96.66,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 341,
            "gameName": "Magic Fruits Deluxe",
            "provider": "wazdan",
            "RTP": 96.41,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 342,
            "gameName": "Wild Guns",
            "provider": "wazdan",
            "RTP": 96.34,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 343,
            "gameName": "Mystery Jack Deluxe",
            "provider": "wazdan",
            "RTP": 96.49,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 344,
            "gameName": "Magic Target Deluxe",
            "provider": "wazdan",
            "RTP": 96.63,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 345,
            "gameName": "Bars & 7s",
            "provider": "wazdan",
            "RTP": 96.43,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 346,
            "gameName": "Fenix Play Deluxe",
            "provider": "wazdan",
            "RTP": 96.44,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 347,
            "gameName": "Fenix Play 27 Deluxe",
            "provider": "wazdan",
            "RTP": 96.25,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 348,
            "gameName": "Magic Fruits 4 Deluxe",
            "provider": "wazdan",
            "RTP": 96.1,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 350,
            "gameName": "Great Book Of Magic Deluxe",
            "provider": "wazdan",
            "RTP": 96.47,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 351,
            "gameName": "Draculas Castle",
            "provider": "wazdan",
            "RTP": 96.56,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 352,
            "gameName": "Fruit Mania Deluxe",
            "provider": "wazdan",
            "RTP": 96.59,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 353,
            "gameName": "Magic Stars 3",
            "provider": "wazdan",
            "RTP": 96.5,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 354,
            "gameName": "Highschool Manga",
            "provider": "wazdan",
            "RTP": 96.41,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 355,
            "gameName": "Dino Reels 81",
            "provider": "wazdan",
            "RTP": 96.44,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 356,
            "gameName": "Cube Mania Deluxe",
            "provider": "wazdan",
            "RTP": 96.59,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 357,
            "gameName": "Hot 777 Deluxe",
            "provider": "wazdan",
            "RTP": 96.45,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 358,
            "gameName": "Corrida Romance Deluxe",
            "provider": "wazdan",
            "RTP": 96.15,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 359,
            "gameName": "Black Hawk Deluxe",
            "provider": "wazdan",
            "RTP": 96.47,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 360,
            "gameName": "Hot Party Deluxe",
            "provider": "wazdan",
            "RTP": 96.42,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 361,
            "gameName": "Black Horse Deluxe™",
            "provider": "wazdan",
            "RTP": 96.23,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 363,
            "gameName": "Fruit Fiesta",
            "provider": "wazdan",
            "RTP": 96.32,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 365,
            "gameName": "Beauty Fruity",
            "provider": "wazdan",
            "RTP": 96.27,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 366,
            "gameName": "Space Gem",
            "provider": "wazdan",
            "RTP": 96.4,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 367,
            "gameName": "Larry the Leprechaun",
            "provider": "wazdan",
            "RTP": 96.47,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 368,
            "gameName": "Magic Stars 6",
            "provider": "wazdan",
            "RTP": 96.49,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 369,
            "gameName": "Lucky Reels",
            "provider": "wazdan",
            "RTP": 96.6,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 371,
            "gameName": "Dragons Lucky 8",
            "provider": "wazdan",
            "RTP": 96.34,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 372,
            "gameName": "Relic Hunters and the Book of Faith™",
            "provider": "wazdan",
            "RTP": 96.37,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 373,
            "gameName": "Neon City",
            "provider": "wazdan",
            "RTP": 96.25,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 375,
            "gameName": "Sonic Reels",
            "provider": "wazdan",
            "RTP": 96.24,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 376,
            "gameName": "Sic Bo Dragons",
            "provider": "wazdan",
            "RTP": 96.15,
            "freeRoundsSupported": false,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 377,
            "gameName": "Telly Reels™",
            "provider": "wazdan",
            "RTP": 96.19,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 379,
            "gameName": "Butterfly Lovers",
            "provider": "wazdan",
            "RTP": 96.16,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 381,
            "gameName": "Infinity Hero",
            "provider": "wazdan",
            "RTP": 96.24,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 385,
            "gameName": "Reel Hero™",
            "provider": "wazdan",
            "RTP": 96.22,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 386,
            "gameName": "Power of Gods™: Egypt",
            "provider": "wazdan",
            "RTP": 96.19,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 387,
            "gameName": "Choco Reels™",
            "provider": "wazdan",
            "RTP": 96.22,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 389,
            "gameName": "Lucky 9™",
            "provider": "wazdan",
            "RTP": 96.11,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 390,
            "gameName": "9 Tigers",
            "provider": "wazdan",
            "RTP": 96.15,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 513,
            "gameName": "Joker Poker",
            "provider": "wazdan",
            "RTP": 96.07,
            "freeRoundsSupported": false,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 514,
            "gameName": "Turbo Poker",
            "provider": "wazdan",
            "RTP": 95.94,
            "freeRoundsSupported": false,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 516,
            "gameName": "American Poker V",
            "provider": "wazdan",
            "RTP": 95.96,
            "freeRoundsSupported": false,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 518,
            "gameName": "Three Cards",
            "provider": "wazdan",
            "RTP": 95.47,
            "freeRoundsSupported": false,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 519,
            "gameName": "Magic Poker",
            "provider": "wazdan",
            "RTP": 96.61,
            "freeRoundsSupported": false,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 520,
            "gameName": "American Poker Gold",
            "provider": "wazdan",
            "RTP": 96.58,
            "freeRoundsSupported": false,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 769,
            "gameName": "BlackJack",
            "provider": "wazdan",
            "RTP": 99.59,
            "freeRoundsSupported": false,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 771,
            "gameName": "Caribbean Beach Poker",
            "provider": "wazdan",
            "RTP": 97.3,
            "freeRoundsSupported": false,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 1029,
            "gameName": "Extra Bingo",
            "provider": "wazdan",
            "RTP": 95.96,
            "freeRoundsSupported": false,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 1284,
            "gameName": "Casino Roulette",
            "provider": "wazdan",
            "RTP": 97.3,
            "freeRoundsSupported": false,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 1286,
            "gameName": "Gold Roulette",
            "provider": "wazdan",
            "RTP": 97.3,
            "freeRoundsSupported": false,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 65868,
            "gameName": "Jumping Fruits",
            "provider": "wazdan",
            "RTP": 96.4,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 65869,
            "gameName": "Los Muertos",
            "provider": "wazdan",
            "RTP": 96.29,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 65877,
            "gameName": "Magic Fruits",
            "provider": "wazdan",
            "RTP": 96.41,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 65878,
            "gameName": "Fruits Go Bananas",
            "provider": "wazdan",
            "RTP": 96.34,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 65879,
            "gameName": "Haunted Hospital",
            "provider": "wazdan",
            "RTP": 96.49,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 65880,
            "gameName": "In The Forest",
            "provider": "wazdan",
            "RTP": 96.63,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 65881,
            "gameName": "Double Tigers",
            "provider": "wazdan",
            "RTP": 96.43,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 65882,
            "gameName": "Fenix Play",
            "provider": "wazdan",
            "RTP": 96.44,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 65883,
            "gameName": "Fenix Play 27",
            "provider": "wazdan",
            "RTP": 96.25,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 65884,
            "gameName": "Magic Hot 4 Deluxe",
            "provider": "wazdan",
            "RTP": 96.1,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 65886,
            "gameName": "Magic Of The Ring Deluxe",
            "provider": "wazdan",
            "RTP": 96.47,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 65887,
            "gameName": "Highway to Hell Deluxe",
            "provider": "wazdan",
            "RTP": 96.56,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 65888,
            "gameName": "9 Lions",
            "provider": "wazdan",
            "RTP": 96.59,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 65892,
            "gameName": "Slot Jam",
            "provider": "wazdan",
            "RTP": 96.59,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 65895,
            "gameName": "Valhalla",
            "provider": "wazdan",
            "RTP": 96.47,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 65896,
            "gameName": "Sizzling 777 Deluxe",
            "provider": "wazdan",
            "RTP": 96.42,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 65904,
            "gameName": "Juicy Reels",
            "provider": "wazdan",
            "RTP": 96.49,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 131416,
            "gameName": "Burning Reels",
            "provider": "wazdan",
            "RTP": 96.63,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 131419,
            "gameName": "Wild Jack",
            "provider": "wazdan",
            "RTP": 96.25,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 131420,
            "gameName": "Magic Fruits 4",
            "provider": "wazdan",
            "RTP": 96.1,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 131422,
            "gameName": "Hungry Shark",
            "provider": "wazdan",
            "RTP": 96.47,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 131424,
            "gameName": "Football Mania Deluxe",
            "provider": "wazdan",
            "RTP": 96.59,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 131428,
            "gameName": "Jackpot Builders",
            "provider": "wazdan",
            "RTP": 96.59,
            "freeRoundsSupported": false,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 131431,
            "gameName": "Black Hawk",
            "provider": "wazdan",
            "RTP": 96.23,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 131432,
            "gameName": "Sizzling 777",
            "provider": "wazdan",
            "RTP": 96.42,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 196952,
            "gameName": "Magic Target",
            "provider": "wazdan",
            "RTP": 96.63,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 196956,
            "gameName": "Magic Hot 4",
            "provider": "wazdan",
            "RTP": 96.1,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 196958,
            "gameName": "Back to the 70s",
            "provider": "wazdan",
            "RTP": 96.47,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 196960,
            "gameName": "Fruit Mania",
            "provider": "wazdan",
            "RTP": 96.59,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 196968,
            "gameName": "Hot Party",
            "provider": "wazdan",
            "RTP": 96.48,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 262492,
            "gameName": "Colin the Cat™",
            "provider": "wazdan",
            "RTP": 96.1,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 262494,
            "gameName": "Great Book Of Magic",
            "provider": "wazdan",
            "RTP": 96.47,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 262496,
            "gameName": "Football Mania",
            "provider": "wazdan",
            "RTP": 96.59,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 262504,
            "gameName": "Magic Stars 5",
            "provider": "wazdan",
            "RTP": 96.42,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 328030,
            "gameName": "Magic Of The Ring",
            "provider": "wazdan",
            "RTP": 96.47,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        },
        {
            "gameId": 393566,
            "gameName": "Sizzling 70s",
            "provider": "wazdan",
            "RTP": 0,
            "freeRoundsSupported": true,
            "widgetSupported": false,
            "goldenchipsSupported": false
        }
    ]';

    // $someArray = json_decode($array, JSON_FORCE_OBJECT);
    $foo = utf8_encode($array);
    $data = json_decode($foo, true);

    $data2 = array();
    foreach($data as $g){
        // if($g['game_type_id'] == "Slot"){
        //   $game_type = 1;
        // }else if($g['game_type_id'] == "BlackJack"){
        //   $game_type = 5;
        // }else if($g['game_type_id'] == "Video"){
        //   $game_type = 12;
        // }else if($g['game_type_id'] == "Table"){
        //   $game_type = 5;
        // }else if($g['game_type_id'] == "Poker"){
        //   $game_type = 3;
        // }else if($g['game_type_id'] == "Roulette"){
        //   $game_type = 5;
        // }else if($g['game_type_id'] == "Fixed Odds"){
        //   $game_type = 17;
        // }

        $game = array(
            "game_type_id"=> 1,
            "provider_id"=> 33,
            "sub_provider_id"=> 57,
            "game_name"=> $g['gameName'],
            "game_code"=>$g["gameId"],
            "icon"=> 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcS9Oza5sOOuv12mmaLfvpzkjoCKTx2oFKbpPQ&usqp=CAU'
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
