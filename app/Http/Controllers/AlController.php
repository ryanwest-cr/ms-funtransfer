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
    "game_name": "Sisters of Oz: Jackpots",
    "game_type": "Slot",
    "game_code": "SMG_sistersOfOzJackpots"
  },
  {
    "game_name": "Alchemist Stone ",
    "game_type": "Slot",
    "game_code": "SMG_alchemistStone"
  },
  {
    "game_name": "WP Golden Pig Race",
    "game_type": "QiPai",
    "game_code": "SFG_WPGoldenPigRace"
  },
  {
    "game_name": "108 Heroes",
    "game_type": "Slot",
    "game_code": "SMG_108Heroes"
  },
  {
    "game_name": "108 Heroes Multiplier Fortunes",
    "game_type": "Slot",
    "game_code": "SMG_108heroesMultiplierFortunes"
  },
  {
    "game_name": "168 Spin Live",
    "game_type": "Live Host",
    "game_code": "SFB_168spinLive"
  },
  {
    "game_name": "5 Reel Drive",
    "game_type": "Slot",
    "game_code": "SMG_5ReelDrive"
  },
  {
    "game_name": "9 Masks of Fire",
    "game_type": "Slot",
    "game_code": "SMG_9masksOfFire"
  },
  {
    "game_name": "9 Pots of Gold",
    "game_type": "Slot",
    "game_code": "SMG_9potsOfGold"
  },
  {
    "game_name": "A Dark Matter",
    "game_type": "Slot",
    "game_code": "SMG_aDarkMatter"
  },
  {
    "game_name": "Absolootly Mad™: Mega Moolah",
    "game_type": "Slot",
    "game_code": "SMG_absolootlyMadMegaMoolah"
  },
  {
    "game_name": "Aces and Eights",
    "game_type": "Table",
    "game_code": "SMG_acesAndEights"
  },
  {
    "game_name": "Aces and Faces Poker",
    "game_type": "Table",
    "game_code": "SMG_acesAndFaces"
  },
  {
    "game_name": "ActionOps: Snow and Sable",
    "game_type": "Slot",
    "game_code": "SMG_actionOpsSnowAndSable"
  },
  {
    "game_name": "Adventure Palace",
    "game_type": "Slot",
    "game_code": "SMG_adventurePalace"
  },
  {
    "game_name": "Age of Conquest",
    "game_type": "Slot",
    "game_code": "SMG_ageOfConquest"
  },
  {
    "game_name": "Age of Discovery",
    "game_type": "Slot",
    "game_code": "SMG_ageOfDiscovery"
  },
  {
    "game_name": "Agent Jane Blonde",
    "game_type": "Slot",
    "game_code": "SMG_agentJaneBlonde"
  },
  {
    "game_name": "Agent Jane Blonde Returns",
    "game_type": "Slot",
    "game_code": "SMG_agentjaneblondereturns"
  },
  {
    "game_name": "Alaskan Fishing",
    "game_type": "Slot",
    "game_code": "SMG_alaskanFishing"
  },
  {
    "game_name": "Alchemy Blast",
    "game_type": "Arcade",
    "game_code": "SMG_alchemyBlast"
  },
  {
    "game_name": "Alien Rush",
    "game_type": "Arcade",
    "game_code": "SFB_Cascading_Alien"
  },
  {
    "game_name": "All Aces",
    "game_type": "Table",
    "game_code": "SMG_RubyAllAces"
  },
  {
    "game_name": "American Roulette (Jade)",
    "game_type": "Table",
    "game_code": "SMG_switchAmericanRoulette"
  },
  {
    "game_name": "Ancient Fortunes: Zeus",
    "game_type": "Slot",
    "game_code": "SMG_ancientFortunesZeus"
  },
  {
    "game_name": "Ariana",
    "game_type": "Slot",
    "game_code": "SMG_ariana"
  },
  {
    "game_name": "Asian Beauty",
    "game_type": "Slot",
    "game_code": "SMG_asianBeauty"
  },
  {
    "game_name": "Astro Legends: Lyra and Erion",
    "game_type": "Arcade",
    "game_code": "SMG_astroLegendsLyraandErion"
  },
  {
    "game_name": "Astro Rocks! ",
    "game_type": "Arcade",
    "game_code": "SFB_AstroRocks"
  },
  {
    "game_name": "Astro Rocks! Live",
    "game_type": "Live Host",
    "game_code": "SFB_AstroRocksLive"
  },
  {
    "game_name": "Atlantean Treasures: Mega Moolah",
    "game_type": "Slot",
    "game_code": "SMG_atlanteanTreasures"
  },
  {
    "game_name": "Atlantic City Blackjack (Jade)",
    "game_type": "Table",
    "game_code": "SMG_switchAtlanticCityBlackjack"
  },
  {
    "game_name": "Atlantic City Blackjack Gold",
    "game_type": "Table",
    "game_code": "SMG_atlanticCityBlackjackGold"
  },
  {
    "game_name": "Aurora Wilds",
    "game_type": "Slot",
    "game_code": "SMG_auroraWilds"
  },
  {
    "game_name": "Avalon",
    "game_type": "Slot",
    "game_code": "SMG_avalon"
  },
  {
    "game_name": "Baccarat - Playboy",
    "game_type": "Live Dealer",
    "game_code": "SMG_titaniumLiveGames_Baccarat_Playboy"
  },
  {
    "game_name": "Baccarat - Playboy (NC)",
    "game_type": "Live Dealer",
    "game_code": "SMG_titaniumLiveGames_BaccaratplayboyNC"
  },
  {
    "game_name": "Baccarat (Jade)",
    "game_type": "Table",
    "game_code": "SMG_switchBaccarat"
  },
  {
    "game_name": "Baccarat (NC)",
    "game_type": "Live Dealer",
    "game_code": "SMG_titaniumLiveGames_BaccaratNC"
  },
  {
    "game_name": "Baccarat Live",
    "game_type": "Live Dealer",
    "game_code": "SMG_titaniumLiveGames_Baccarat"
  },
  {
    "game_name": "Badminton Hero",
    "game_type": "Slot",
    "game_code": "SMG_badmintonHero"
  },
  {
    "game_name": "Banana Odyssey",
    "game_type": "Slot",
    "game_code": "SMG_bananaOdyssey"
  },
  {
    "game_name": "Bar Bar Black Sheep - 5 Reel",
    "game_type": "Slot",
    "game_code": "SMG_barBarBlackSheep5Reel"
  },
  {
    "game_name": "Bars & Stripes",
    "game_type": "Slot",
    "game_code": "SMG_BarsAndStripes"
  },
  {
    "game_name": "Baseball Duel",
    "game_type": "Arcade",
    "game_code": "SFB_BaseballDuel"
  },
  {
    "game_name": "Basketball Star",
    "game_type": "Slot",
    "game_code": "SMG_basketballStar"
  },
  {
    "game_name": "Basketball Star Deluxe",
    "game_type": "Slot",
    "game_code": "SMG_basketballStarDeluxe"
  },
  {
    "game_name": "Battle Royale",
    "game_type": "Arcade",
    "game_code": "SMG_battleRoyale"
  },
  {
    "game_name": "Beach Babes",
    "game_type": "Slot",
    "game_code": "SMG_beachBabes"
  },
  {
    "game_name": "Beautiful Bones",
    "game_type": "Slot",
    "game_code": "SMG_beautifulBones"
  },
  {
    "game_name": "Big Kahuna",
    "game_type": "Slot",
    "game_code": "SMG_bigKahuna"
  },
  {
    "game_name": "Big Top",
    "game_type": "Slot",
    "game_code": "SMG_bigTop"
  },
  {
    "game_name": "Bikini Party",
    "game_type": "Slot",
    "game_code": "SMG_bikiniParty"
  },
  {
    "game_name": "Boat of Fortune",
    "game_type": "Slot",
    "game_code": "SMG_boatofFortune"
  },
  {
    "game_name": "Bonus Deuces Wild",
    "game_type": "Table",
    "game_code": "SMG_bonusDeucesWild"
  },
  {
    "game_name": "Boogie Monsters",
    "game_type": "Slot",
    "game_code": "SMG_boogieMonsters"
  },
  {
    "game_name": "Book Of Oz",
    "game_type": "Slot",
    "game_code": "SMG_bookOfOz"
  },
  {
    "game_name": "Book of Oz - Lock N Spin",
    "game_type": "Slot",
    "game_code": "SMG_bookOfOzLockNSpin"
  },
  {
    "game_name": "Bookie of Odds",
    "game_type": "Slot",
    "game_code": "SMG_bookieOfOdds"
  },
  {
    "game_name": "Boom Pirates",
    "game_type": "Slot",
    "game_code": "SMG_boomPirates"
  },
  {
    "game_name": "Break Away",
    "game_type": "Slot",
    "game_code": "SMG_breakAway"
  },
  {
    "game_name": "Break Away Deluxe",
    "game_type": "Slot",
    "game_code": "SMG_breakAwayDeluxe"
  },
  {
    "game_name": "Break Away Lucky Wilds",
    "game_type": "Slot",
    "game_code": "SMG_breakAwayLuckyWilds"
  },
  {
    "game_name": "Break da Bank",
    "game_type": "Slot",
    "game_code": "SMG_breakDaBank"
  },
  {
    "game_name": "Break da Bank Again",
    "game_type": "Slot",
    "game_code": "SMG_breakDaBankAgain"
  },
  {
    "game_name": "Break Da Bank Again Respin",
    "game_type": "Slot",
    "game_code": "SMG_breakDaBankAgainRespin"
  },
  {
    "game_name": "Bridesmaids",
    "game_type": "Slot",
    "game_code": "SMG_bridesmaids"
  },
  {
    "game_name": "Bullseye",
    "game_type": "Slot",
    "game_code": "SMG_bullseyeGameshow"
  },
  {
    "game_name": "Burning Desire",
    "game_type": "Slot",
    "game_code": "SMG_burningDesire"
  },
  {
    "game_name": "Bush Telegraph",
    "game_type": "Slot",
    "game_code": "SMG_bushTelegraph"
  },
  {
    "game_name": "Bust the Bank",
    "game_type": "Slot",
    "game_code": "SMG_bustTheBank"
  },
  {
    "game_name": "Candy Dreams",
    "game_type": "Slot",
    "game_code": "SMG_candyDreams"
  },
  {
    "game_name": "Carnaval",
    "game_type": "Slot",
    "game_code": "SMG_carnaval"
  },
  {
    "game_name": "Cash Crazy",
    "game_type": "Slot",
    "game_code": "SMG_cashCrazy"
  },
  {
    "game_name": "Cash of Kingdoms",
    "game_type": "Slot",
    "game_code": "SMG_cashOfKingdoms"
  },
  {
    "game_name": "Cash Splash 5 Reel",
    "game_type": "Slot",
    "game_code": "SMG_CashSplash5Reel"
  },
  {
    "game_name": "Cashapillar",
    "game_type": "Slot",
    "game_code": "SMG_cashapillar"
  },
  {
    "game_name": "CashOccino",
    "game_type": "Slot",
    "game_code": "SMG_cashoccino"
  },
  {
    "game_name": "Cashville",
    "game_type": "Slot",
    "game_code": "SMG_cashville"
  },
  {
    "game_name": "Castle Builder II",
    "game_type": "Slot",
    "game_code": "SMG_castleBuilder2"
  },
  {
    "game_name": "Centre Court",
    "game_type": "Slot",
    "game_code": "SMG_centreCourt"
  },
  {
    "game_name": "Classic 243",
    "game_type": "Slot",
    "game_code": "SMG_classic243"
  },
  {
    "game_name": "Classic Blackjack (Jade)",
    "game_type": "Table",
    "game_code": "SMG_switchClassicBlackjack"
  },
  {
    "game_name": "Classic Blackjack Gold",
    "game_type": "Table",
    "game_code": "SMG_classicBlackjackGold"
  },
  {
    "game_name": "Cool Buck - 5 Reel",
    "game_type": "Slot",
    "game_code": "SMG_coolBuck5Reel"
  },
  {
    "game_name": "Cool Wolf",
    "game_type": "Slot",
    "game_code": "SMG_coolWolf"
  },
  {
    "game_name": "Couch Potato",
    "game_type": "Slot",
    "game_code": "SMG_couchPotato"
  },
  {
    "game_name": "Crazy Chameleons",
    "game_type": "Slot",
    "game_code": "SMG_crazyChameleons"
  },
  {
    "game_name": "Cricket Star",
    "game_type": "Slot",
    "game_code": "SMG_cricketStar"
  },
  {
    "game_name": "Crystal Rift",
    "game_type": "Slot",
    "game_code": "SMG_CrystalRift"
  },
  {
    "game_name": "Deck the Halls",
    "game_type": "Slot",
    "game_code": "SMG_deckTheHalls"
  },
  {
    "game_name": "Deco Diamonds",
    "game_type": "Slot",
    "game_code": "SMG_decoDiamonds"
  },
  {
    "game_name": "Deuces Wild",
    "game_type": "Table",
    "game_code": "SMG_deucesWild"
  },
  {
    "game_name": "Diamond Empire",
    "game_type": "Slot",
    "game_code": "SMG_diamondEmpire"
  },
  {
    "game_name": "Dolphin Coast",
    "game_type": "Slot",
    "game_code": "SMG_dolphinCoast"
  },
  {
    "game_name": "Dolphin Quest",
    "game_type": "Slot",
    "game_code": "SMG_dolphinQuest"
  },
  {
    "game_name": "Double Double Bonus Poker",
    "game_type": "Table",
    "game_code": "SMG_doubleDoubleBonus"
  },
  {
    "game_name": "Double Up",
    "game_type": "Live Host",
    "game_code": "SFB_DoubleUp"
  },
  {
    "game_name": "Double Up Playboy Live",
    "game_type": "Live Host",
    "game_code": "SFB_LivePBDoubleCards"
  },
  {
    "game_name": "Double Wammy",
    "game_type": "Slot",
    "game_code": "SMG_doubleWammy"
  },
  {
    "game_name": "Dragon Dance",
    "game_type": "Slot",
    "game_code": "SMG_dragonDance"
  },
  {
    "game_name": "Dragon Shard",
    "game_type": "Slot",
    "game_code": "SMG_dragonShard"
  },
  {
    "game_name": "Dragonz",
    "game_type": "Slot",
    "game_code": "SMG_dragonz"
  },
  {
    "game_name": "Dream Date",
    "game_type": "Slot",
    "game_code": "SMG_dreamDate"
  },
  {
    "game_name": "Eagles Wings",
    "game_type": "Slot",
    "game_code": "SMG_eaglesWings"
  },
  {
    "game_name": "EmotiCoins",
    "game_type": "Slot",
    "game_code": "SMG_emotiCoins"
  },
  {
    "game_name": "Emperor of the Sea",
    "game_type": "Slot",
    "game_code": "SMG_emperorOfTheSea"
  },
  {
    "game_name": "European Blackjack (Jade)",
    "game_type": "Table",
    "game_code": "SMG_switchEuropeanBlackjack"
  },
  {
    "game_name": "European Blackjack Gold",
    "game_type": "Table",
    "game_code": "SMG_europeanBlackjackGold"
  },
  {
    "game_name": "European Roulette (Jade)",
    "game_type": "Table",
    "game_code": "SMG_switchEuropeanRoulette"
  },
  {
    "game_name": "Exotic Cats",
    "game_type": "Slot",
    "game_code": "SMG_exoticCats"
  },
  {
    "game_name": "Fan Tan",
    "game_type": "Live Host",
    "game_code": "SFB_FanTan"
  },
  {
    "game_name": "Fire Dice Deluxe",
    "game_type": "Live Host",
    "game_code": "SFB_LiveFireDice"
  },
  {
    "game_name": "Fire Dice Turbo",
    "game_type": "Live Host",
    "game_code": "SFB_LiveFireDiceTurbo"
  },
  {
    "game_name": "Fish Party",
    "game_type": "Slot",
    "game_code": "SMG_fishParty"
  },
  {
    "game_name": "Football Star",
    "game_type": "Slot",
    "game_code": "SMG_footballStar"
  },
  {
    "game_name": "Football Star Deluxe",
    "game_type": "Slot",
    "game_code": "SMG_footballStarDeluxe"
  },
  {
    "game_name": "Forbidden Throne",
    "game_type": "Slot",
    "game_code": "SMG_forbiddenThrone"
  },
  {
    "game_name": "Fortune Girl",
    "game_type": "Slot",
    "game_code": "SMG_fortuneGirl"
  },
  {
    "game_name": "Fortunium",
    "game_type": "Slot",
    "game_code": "SMG_fortunium"
  },
  {
    "game_name": "Fruit Blast",
    "game_type": "Arcade",
    "game_code": "SMG_fruitBlast"
  },
  {
    "game_name": "Fruit vs Candy",
    "game_type": "Slot",
    "game_code": "SMG_fruitVSCandy"
  },
  {
    "game_name": "Galaxy Glider",
    "game_type": "Arcade",
    "game_code": "SMG_galaxyGlider"
  },
  {
    "game_name": "Gems Odyssey",
    "game_type": "Arcade",
    "game_code": "SMG_gemsOdyssey"
  },
  {
    "game_name": "Giant Riches",
    "game_type": "Slot",
    "game_code": "SMG_giantRiches"
  },
  {
    "game_name": "Girls With Guns - Jungle Heat",
    "game_type": "Slot",
    "game_code": "SMG_girlsWithGunsJungleHeat"
  },
  {
    "game_name": "Gnome Wood",
    "game_type": "Slot",
    "game_code": "SMG_gnomeWood"
  },
  {
    "game_name": "Gold Factory",
    "game_type": "Slot",
    "game_code": "SMG_goldFactory"
  },
  {
    "game_name": "Golden Era",
    "game_type": "Slot",
    "game_code": "SMG_goldenEra"
  },
  {
    "game_name": "Golden Noodles Live",
    "game_type": "Live Host",
    "game_code": "SFB_GoldenNoodlesLive"
  },
  {
    "game_name": "Golden Princess",
    "game_type": "Slot",
    "game_code": "SMG_goldenPrincess"
  },
  {
    "game_name": "Gopher Gold",
    "game_type": "Slot",
    "game_code": "SMG_gopherGold"
  },
  {
    "game_name": "Halloween",
    "game_type": "Slot",
    "game_code": "SMG_halloween "
  },
  {
    "game_name": "Halloweenies",
    "game_type": "Slot",
    "game_code": "SMG_halloweenies"
  },
  {
    "game_name": "Happy Holidays",
    "game_type": "Slot",
    "game_code": "SMG_HappyHolidays"
  },
  {
    "game_name": "Happy Monster Claw",
    "game_type": "Arcade",
    "game_code": "SMG_happyMonsterClaw"
  },
  {
    "game_name": "harveys",
    "game_type": "Slot",
    "game_code": "SMG_harveys"
  },
  {
    "game_name": "Hidden Treasures of River Kwai (Thai Market)",
    "game_type": "Slot",
    "game_code": "SMG_hiddenTreasuresOfRiverKwai"
  },
  {
    "game_name": "High Society",
    "game_type": "Slot",
    "game_code": "SMG_highSociety"
  },
  {
    "game_name": "Highlander",
    "game_type": "Slot",
    "game_code": "SMG_highlander"
  },
  {
    "game_name": "Hitman",
    "game_type": "Slot",
    "game_code": "SMG_hitman"
  },
  {
    "game_name": "Holly Jolly Penguins",
    "game_type": "Slot",
    "game_code": "SMG_hollyJollyPenguins"
  },
  {
    "game_name": "Hound Hotel",
    "game_type": "Slot",
    "game_code": "SMG_HoundHotel"
  },
  {
    "game_name": "Huangdi - The Yellow Emperor",
    "game_type": "Slot",
    "game_code": "SMG_huangdiTheYellowEmperor"
  },
  {
    "game_name": "Immortal Romance",
    "game_type": "Slot",
    "game_code": "SMG_immortalRomance"
  },
  {
    "game_name": "Incan Adventure",
    "game_type": "Arcade",
    "game_code": "SMG_incanAdventure"
  },
  {
    "game_name": "Incredible Balloon Machine",
    "game_type": "Arcade",
    "game_code": "SMG_theIncredibleBalloonMachine"
  },
  {
    "game_name": "Instant Football",
    "game_type": "Virtual Sports",
    "game_code": "SVS_Instant_Football"
  },
  {
    "game_name": "Instant Greyhounds",
    "game_type": "Virtual Sports",
    "game_code": "SVS_instant_greyhounds"
  },
  {
    "game_name": "Instant Horses",
    "game_type": "Virtual Sports",
    "game_code": "SVS_instant_horses"
  },
  {
    "game_name": "Instant Racing (Lobby)",
    "game_type": "Virtual Sports",
    "game_code": "SVS_instant_racing"
  },
  {
    "game_name": "Instant Speedway",
    "game_type": "Virtual Sports",
    "game_code": "SVS_instant_speedway"
  },
  {
    "game_name": "Instant Trotting",
    "game_type": "Virtual Sports",
    "game_code": "SVS_instant_trotting"
  },
  {
    "game_name": "Instant Velodrome",
    "game_type": "Virtual Sports",
    "game_code": "SVS_instant_velodrome"
  },
  {
    "game_name": "Isis",
    "game_type": "Slot",
    "game_code": "SMG_isis"
  },
  {
    "game_name": "Jacks or Better",
    "game_type": "Table",
    "game_code": "SMG_jacksOrBetter"
  },
  {
    "game_name": "Jewel Quest Riches",
    "game_type": "Arcade",
    "game_code": "SMG_jewelQuestRiches"
  },
  {
    "game_name": "Jungle Jim - El Dorado",
    "game_type": "Slot",
    "game_code": "SMG_jungleJimElDorado"
  },
  {
    "game_name": "Jungle Jim and the Lost Sphinx ",
    "game_type": "Slot",
    "game_code": "SMG_jungleJimAndTheLostSphinx"
  },
  {
    "game_name": "Jungle King Live",
    "game_type": "Live Host",
    "game_code": "SFB_JungleKingLive"
  },
  {
    "game_name": "Jurassic World",
    "game_type": "Slot",
    "game_code": "SMG_jurassicWorld"
  },
  {
    "game_name": "Karaoke Party",
    "game_type": "Slot",
    "game_code": "SMG_karaokeParty"
  },
  {
    "game_name": "Kathmandu",
    "game_type": "Slot",
    "game_code": "SMG_kathmandu"
  },
  {
    "game_name": "King of the Ring (Thai Market)",
    "game_type": "Slot",
    "game_code": "SMG_kingofTheRing"
  },
  {
    "game_name": "King Tusk",
    "game_type": "Slot",
    "game_code": "SMG_kingTusk"
  },
  {
    "game_name": "Kings of Cash",
    "game_type": "Slot",
    "game_code": "SMG_kingsOfCash"
  },
  {
    "game_name": "Kitty Cabana",
    "game_type": "Slot",
    "game_code": "SMG_KittyCabana"
  },
  {
    "game_name": "Ladies Nite",
    "game_type": "Slot",
    "game_code": "SMG_ladiesNite"
  },
  {
    "game_name": "Ladies Nite 2 Turn Wild",
    "game_type": "Slot",
    "game_code": "SMG_ladiesNite2TurnWild"
  },
  {
    "game_name": "Lady in Red",
    "game_type": "Slot",
    "game_code": "SMG_ladyInRed"
  },
  {
    "game_name": "Lara Croft - Temples and Tombs",
    "game_type": "Slot",
    "game_code": "SMG_laraCroftTemplesAndTombs"
  },
  {
    "game_name": "League of Bird Hunting",
    "game_type": "Fishing",
    "game_code": "SMF_BirdHunting"
  },
  {
    "game_name": "League of Fishing Joy",
    "game_type": "Fishing",
    "game_code": "SMF_FishingJoy"
  },
  {
    "game_name": "Legend Keno",
    "game_type": "Arcade",
    "game_code": "SMG_legendKeno"
  },
  {
    "game_name": "Legend of the Moon Lovers",
    "game_type": "Slot",
    "game_code": "SMG_LegendOftheMoonLovers"
  },
  {
    "game_name": "Life of Riches",
    "game_type": "Slot",
    "game_code": "SMG_lifeOfRiches"
  },
  {
    "game_name": "Lions Pride",
    "game_type": "Slot",
    "game_code": "SMG_lionsPride"
  },
  {
    "game_name": "Liquid Gold",
    "game_type": "Slot",
    "game_code": "SMG_liquidGold"
  },
  {
    "game_name": "Loaded",
    "game_type": "Slot",
    "game_code": "SMG_loaded"
  },
  {
    "game_name": "Long Mu Fortunes",
    "game_type": "Slot",
    "game_code": "SMG_longMuFortunes"
  },
  {
    "game_name": "Lost Vegas",
    "game_type": "Slot",
    "game_code": "SMG_lostVegas"
  },
  {
    "game_name": "Lucha Legends",
    "game_type": "Slot",
    "game_code": "SMG_luchaLegends"
  },
  {
    "game_name": "Lucky Bachelors",
    "game_type": "Slot",
    "game_code": "SMG_luckyBachelors"
  },
  {
    "game_name": "Lucky Firecracker",
    "game_type": "Slot",
    "game_code": "SMG_luckyfirecracker"
  },
  {
    "game_name": "Lucky Koi",
    "game_type": "Slot",
    "game_code": "SMG_luckyKoi"
  },
  {
    "game_name": "Lucky Leprechaun",
    "game_type": "Slot",
    "game_code": "SMG_luckyLeprechaun"
  },
  {
    "game_name": "Lucky Little Gods",
    "game_type": "Slot",
    "game_code": "SMG_luckyLittleGods"
  },
  {
    "game_name": "Lucky Riches Hyperspins",
    "game_type": "Slot",
    "game_code": "SMG_luckyRichesHyperspins"
  },
  {
    "game_name": "Lucky Thai Lanterns  (Thai Market)",
    "game_type": "Slot",
    "game_code": "SMG_luckyThaiLanterns"
  },
  {
    "game_name": "Lucky Twins",
    "game_type": "Slot",
    "game_code": "SMG_luckyTwins"
  },
  {
    "game_name": "Lucky Twins Jackpot",
    "game_type": "Slot",
    "game_code": "SMG_luckyTwinsJackpot"
  },
  {
    "game_name": "Lucky Zodiac",
    "game_type": "Slot",
    "game_code": "SMG_luckyZodiac"
  },
  {
    "game_name": "Mad Hatters",
    "game_type": "Slot",
    "game_code": "SMG_madHatters"
  },
  {
    "game_name": "Magic of Sahara",
    "game_type": "Slot",
    "game_code": "SMG_magicOfSahara"
  },
  {
    "game_name": "Major Millions 5 Reel",
    "game_type": "Slot",
    "game_code": "SMG_MajorMillions5Reel"
  },
  {
    "game_name": "Marmot Mayhem",
    "game_type": "Arcade",
    "game_code": "SFB_MarmotMayhem"
  },
  {
    "game_name": "Marmot Mayhem Live",
    "game_type": "Live Host",
    "game_code": "SFB_MarmotMayhemLive"
  },
  {
    "game_name": "Max Damage and the Alien Attack",
    "game_type": "Arcade",
    "game_code": "SMG_maxDamageArcade"
  },
  {
    "game_name": "Mayan Princess",
    "game_type": "Slot",
    "game_code": "SMG_mayanPrincess"
  },
  {
    "game_name": "Mega Money Multiplier",
    "game_type": "Slot",
    "game_code": "SMG_megaMoneyMultiplier"
  },
  {
    "game_name": "Mega Money Rush",
    "game_type": "Arcade",
    "game_code": "SMG_megaMoneyRush"
  },
  {
    "game_name": "Mega Moolah",
    "game_type": "Slot",
    "game_code": "SMG_MegaMoolah"
  },
  {
    "game_name": "Mermaids Millions",
    "game_type": "Slot",
    "game_code": "SMG_mermaidsMillions"
  },
  {
    "game_name": "Moby Dick Online Slot",
    "game_type": "Slot",
    "game_code": "SMG_mobyDickOnlineSlot"
  },
  {
    "game_name": "Monster Blast",
    "game_type": "Arcade",
    "game_code": "SMG_monsterBlast"
  },
  {
    "game_name": "Monster Wheels",
    "game_type": "Slot",
    "game_code": "SMG_monsterWheels"
  },
  {
    "game_name": "MP Baccarat",
    "game_type": "Live Dealer",
    "game_code": "SMG_titaniumLiveGames_MP_Baccarat"
  },
  {
    "game_name": "MP Baccarat - Playboy",
    "game_type": "Live Dealer",
    "game_code": "SMG_titaniumLiveGames_MP_Baccarat_Playboy"
  },
  {
    "game_name": "Munchkins",
    "game_type": "Slot",
    "game_code": "SMG_munchkins"
  },
  {
    "game_name": "Mystic Dreams",
    "game_type": "Slot",
    "game_code": "SMG_mysticDreams"
  },
  {
    "game_name": "Oink Country Love",
    "game_type": "Slot",
    "game_code": "SMG_oinkCountryLove"
  },
  {
    "game_name": "Our Days",
    "game_type": "Slot",
    "game_code": "SMG_ourDaysA"
  },
  {
    "game_name": "Party Island",
    "game_type": "Slot",
    "game_code": "SMG_partyIsland"
  },
  {
    "game_name": "Peek-a-Boo - 5 Reel",
    "game_type": "Slot",
    "game_code": "SMG_peekABoo5Reel"
  },
  {
    "game_name": "Pets Go Wild",
    "game_type": "Arcade",
    "game_code": "SMG_petsGoWild"
  },
  {
    "game_name": "Ping Pong Star",
    "game_type": "Slot",
    "game_code": "SMG_pingPongStar"
  },
  {
    "game_name": "Pistoleras",
    "game_type": "Slot",
    "game_code": "SMG_pistoleras"
  },
  {
    "game_name": "Playboy",
    "game_type": "Slot",
    "game_code": "SMG_playboy"
  },
  {
    "game_name": "Playboy Fortunes ™",
    "game_type": "Slot",
    "game_code": "SMG_playboyFortunes"
  },
  {
    "game_name": "Playboy Gold",
    "game_type": "Slot",
    "game_code": "SMG_playboyGold"
  },
  {
    "game_name": "Playboy™ Gold Jackpots",
    "game_type": "Slot",
    "game_code": "SMG_playboyGoldJackpots"
  },
  {
    "game_name": "Poke The Guy",
    "game_type": "Arcade",
    "game_code": "SMG_pokeTheGuy"
  },
  {
    "game_name": "Pollen Party",
    "game_type": "Slot",
    "game_code": "SMG_pollenParty"
  },
  {
    "game_name": "Pretty Kitty",
    "game_type": "Slot",
    "game_code": "SMG_prettyKitty"
  },
  {
    "game_name": "Pure Platinum",
    "game_type": "Slot",
    "game_code": "SMG_purePlatinum"
  },
  {
    "game_name": "Queen of Crystal Rays™",
    "game_type": "Slot",
    "game_code": "SMG_queenOfTheCrystalRays"
  },
  {
    "game_name": "Rabbit in the Hat",
    "game_type": "Slot",
    "game_code": "SMG_rabbitinthehat"
  },
  {
    "game_name": "Reel Gems",
    "game_type": "Slot",
    "game_code": "SMG_reelGems"
  },
  {
    "game_name": "Reel Spinner",
    "game_type": "Slot",
    "game_code": "SMG_reelSpinner"
  },
  {
    "game_name": "Reel Strike  ",
    "game_type": "Slot",
    "game_code": "SMG_reelStrike"
  },
  {
    "game_name": "Reel Talent ",
    "game_type": "Slot",
    "game_code": "SMG_ReelTalent"
  },
  {
    "game_name": "Reel Thunder",
    "game_type": "Slot",
    "game_code": "SMG_reelThunder"
  },
  {
    "game_name": "Relic Seekers",
    "game_type": "Slot",
    "game_code": "SMG_relicSeekers"
  },
  {
    "game_name": "Retro Reels",
    "game_type": "Slot",
    "game_code": "SMG_retroReels"
  },
  {
    "game_name": "Retro Reels - Diamond Glitz",
    "game_type": "Slot",
    "game_code": "SMG_retroReelsDiamondGlitz"
  },
  {
    "game_name": "Retro Reels - Extreme Heat",
    "game_type": "Slot",
    "game_code": "SMG_retroReelsExtremeHeat"
  },
  {
    "game_name": "Rhyming Reels - Georgie Porgie",
    "game_type": "Slot",
    "game_code": "SMG_rhymingReelsGeorgiePorgie"
  },
  {
    "game_name": "Rhyming Reels - Hearts & Tarts",
    "game_type": "Slot",
    "game_code": "SMG_rhymingReelsHeartsAndTarts"
  },
  {
    "game_name": "Riviera Riches",
    "game_type": "Slot",
    "game_code": "SMG_rivieraRiches"
  },
  {
    "game_name": "Robin of Sherwood Online Slot",
    "game_type": "Slot",
    "game_code": "SMG_robinOfSherwoodOnlineSlot"
  },
  {
    "game_name": "Robot Dice",
    "game_type": "Arcade",
    "game_code": "SFB_KO_RobotDice"
  },
  {
    "game_name": "Rolling Golds",
    "game_type": "Live Host",
    "game_code": "SFB_RollingGolds"
  },
  {
    "game_name": "Romanov Riches",
    "game_type": "Slot",
    "game_code": "SMG_romanovRiches"
  },
  {
    "game_name": "Roulette ",
    "game_type": "Live Dealer",
    "game_code": "SMG_titaniumLiveGames_Roulette"
  },
  {
    "game_name": "Rugby Star",
    "game_type": "Slot",
    "game_code": "SMG_rugbyStar"
  },
  {
    "game_name": "Rugby Star Deluxe",
    "game_type": "Slot",
    "game_code": "SMG_rugbyStarDeluxe"
  },
  {
    "game_name": "Santa Paws",
    "game_type": "Slot",
    "game_code": "SMG_santaPaws"
  },
  {
    "game_name": "Santas Wild Ride",
    "game_type": "Slot",
    "game_code": "SMG_santasWildRide"
  },
  {
    "game_name": "Scrooge",
    "game_type": "Slot",
    "game_code": "SMG_scrooge"
  },
  {
    "game_name": "Secret Admirer",
    "game_type": "Slot",
    "game_code": "SMG_secretAdmirer"
  },
  {
    "game_name": "Secret Romance",
    "game_type": "Slot",
    "game_code": "SMG_secretRomance"
  },
  {
    "game_name": "Shanghai Beauty",
    "game_type": "Slot",
    "game_code": "SMG_shanghaiBeauty"
  },
  {
    "game_name": "Sherlock of London™",
    "game_type": "Slot",
    "game_code": "SMG_sherlockOfLondonOnlineSlot"
  },
  {
    "game_name": "Shogun of Time",
    "game_type": "Slot",
    "game_code": "SMG_shogunofTime"
  },
  {
    "game_name": "Showdown Saloon",
    "game_type": "Slot",
    "game_code": "SMG_showdownSaloon"
  },
  {
    "game_name": "Sicbo Live",
    "game_type": "Live Dealer",
    "game_code": "SMG_titaniumLiveGames_Sicbo"
  },
  {
    "game_name": "Silver Fang",
    "game_type": "Slot",
    "game_code": "SMG_silverFang"
  },
  {
    "game_name": "Silver Lioness4x",
    "game_type": "Slot",
    "game_code": "SMG_silverLioness4x"
  },
  {
    "game_name": "Six Acrobats",
    "game_type": "Slot",
    "game_code": "SMG_sixAcrobats"
  },
  {
    "game_name": "Snake Lady",
    "game_type": "Slot",
    "game_code": "SFB_SnakeLady"
  },
  {
    "game_name": "Snake Lady Live",
    "game_type": "Live Host",
    "game_code": "SFB_SnakeLadyLive"
  },
  {
    "game_name": "So Many Monsters",
    "game_type": "Slot",
    "game_code": "SMG_soManyMonsters"
  },
  {
    "game_name": "So Much Candy",
    "game_type": "Slot",
    "game_code": "SMG_soMuchCandy"
  },
  {
    "game_name": "So Much Sushi",
    "game_type": "Slot",
    "game_code": "SMG_soMuchSushi"
  },
  {
    "game_name": "Songkran Party (Thai Market)",
    "game_type": "Slot",
    "game_code": "SMG_songkranParty"
  },
  {
    "game_name": "Spring Break",
    "game_type": "Slot",
    "game_code": "SMG_springBreak"
  },
  {
    "game_name": "StarDust",
    "game_type": "Slot",
    "game_code": "SMG_stardust"
  },
  {
    "game_name": "Starlight Kiss",
    "game_type": "Slot",
    "game_code": "SMG_starlightKiss"
  },
  {
    "game_name": "Stash of the Titans",
    "game_type": "Slot",
    "game_code": "SMG_stashOfTheTitans"
  },
  {
    "game_name": "Sterling Silver",
    "game_type": "Slot",
    "game_code": "SMG_sterlingSilver"
  },
  {
    "game_name": "Sugar Parade",
    "game_type": "Slot",
    "game_code": "SMG_sugarParade"
  },
  {
    "game_name": "Summer Holiday",
    "game_type": "Slot",
    "game_code": "SMG_summerHoliday"
  },
  {
    "game_name": "Summertime",
    "game_type": "Slot",
    "game_code": "SMG_summertime"
  },
  {
    "game_name": "SunQuest",
    "game_type": "Slot",
    "game_code": "SMG_sunQuest"
  },
  {
    "game_name": "SunTide",
    "game_type": "Slot",
    "game_code": "SMG_sunTide"
  },
  {
    "game_name": "Supe It Up",
    "game_type": "Slot",
    "game_code": "SMG_supeItUp"
  },
  {
    "game_name": "Sure Win",
    "game_type": "Slot",
    "game_code": "SMG_sureWin"
  },
  {
    "game_name": "Tally Ho",
    "game_type": "Slot",
    "game_code": "SMG_tallyHo"
  },
  {
    "game_name": "Tarzan",
    "game_type": "Slot",
    "game_code": "SMG_tarzan"
  },
  {
    "game_name": "Tasty Street",
    "game_type": "Slot",
    "game_code": "SMG_tastyStreet"
  },
  {
    "game_name": "The Finer Reels of Life",
    "game_type": "Slot",
    "game_code": "SMG_theFinerReelsOfLife"
  },
  {
    "game_name": "The Grand Journey",
    "game_type": "Slot",
    "game_code": "SMG_theGrandJourney"
  },
  {
    "game_name": "The Great Albini",
    "game_type": "Slot",
    "game_code": "SMG_theGreatAlbini"
  },
  {
    "game_name": "The Heat Is On",
    "game_type": "Slot",
    "game_code": "SMG_theHeatIsOn"
  },
  {
    "game_name": "The Phantom of the Opera",
    "game_type": "Slot",
    "game_code": "SMG_thePhantomOfTheOpera"
  },
  {
    "game_name": "The Rat Pack",
    "game_type": "Slot",
    "game_code": "SMG_theRatPack"
  },
  {
    "game_name": "The Twisted Circus",
    "game_type": "Slot",
    "game_code": "SMG_theTwistedCircus"
  },
  {
    "game_name": "Thunderstruck",
    "game_type": "Slot",
    "game_code": "SMG_thunderstruck"
  },
  {
    "game_name": "Thunderstruck II",
    "game_type": "Slot",
    "game_code": "SMG_thunderstruck2"
  },
  {
    "game_name": "Tigers Eye",
    "game_type": "Slot",
    "game_code": "SMG_tigersEye"
  },
  {
    "game_name": "Tiki Vikings",
    "game_type": "Slot",
    "game_code": "SMG_tikiVikings"
  },
  {
    "game_name": "Titans of the Sun - Hyperion",
    "game_type": "Slot",
    "game_code": "SMG_titansOfTheSunHyperion"
  },
  {
    "game_name": "Titans of the Sun - Theia",
    "game_type": "Slot",
    "game_code": "SMG_titansOfTheSunTheia"
  },
  {
    "game_name": "Tomb Raider",
    "game_type": "Slot",
    "game_code": "SMG_tombRaider"
  },
  {
    "game_name": "Tomb Raider Secret of the Sword",
    "game_type": "Slot",
    "game_code": "SMG_RubyTombRaiderII"
  },
  {
    "game_name": "Treasure Dash",
    "game_type": "Arcade",
    "game_code": "SMG_treasureDash"
  },
  {
    "game_name": "Treasure Nile",
    "game_type": "Slot",
    "game_code": "SMG_treasureNile"
  },
  {
    "game_name": "Treasure Palace",
    "game_type": "Slot",
    "game_code": "SMG_treasurePalace"
  },
  {
    "game_name": "Treasures of Lion City",
    "game_type": "Slot",
    "game_code": "SMG_treasuresOfLionCity"
  },
  {
    "game_name": "Untamed - Giant Panda",
    "game_type": "Slot",
    "game_code": "SMG_untamedGiantPanda"
  },
  {
    "game_name": "Vegas Downtown Blackjack (Jade)",
    "game_type": "Table",
    "game_code": "SMG_switchVegasDowntownBlackjack"
  },
  {
    "game_name": "Vegas Downtown Blackjack Gold",
    "game_type": "Table",
    "game_code": "SMG_vegasDowntownBlackjackGold"
  },
  {
    "game_name": "Vegas Single Deck Blackjack (Jade)",
    "game_type": "Table",
    "game_code": "SMG_switchVegasSingleDeckBlackjack"
  },
  {
    "game_name": "Vegas Single Deck Blackjack Gold",
    "game_type": "Table",
    "game_code": "SMG_vegasSingleDeckBlackjackGold"
  },
  {
    "game_name": "Vegas Strip Blackjack (Jade)",
    "game_type": "Table",
    "game_code": "SMG_switchVegasStripBlackjack"
  },
  {
    "game_name": "Vegas Strip Blackjack Gold",
    "game_type": "Table",
    "game_code": "SMG_vegasStripBlackjackGold"
  },
  {
    "game_name": "Village People® Macho Moves",
    "game_type": "Slot",
    "game_code": "SMG_villagePeople"
  },
  {
    "game_name": "Vinyl Countdown",
    "game_type": "Slot",
    "game_code": "SMG_vinylCountdown"
  },
  {
    "game_name": "Virtual Football",
    "game_type": "Virtual Sports",
    "game_code": "SVS_virtual_football"
  },
  {
    "game_name": "Virtual Greyhounds",
    "game_type": "Virtual Sports",
    "game_code": "SVS_virtual_greyhounds"
  },
  {
    "game_name": "Virtual Horses",
    "game_type": "Virtual Sports",
    "game_code": "SVS_virtual_horses"
  },
  {
    "game_name": "Virtual Racing (Lobby)",
    "game_type": "Virtual Sports",
    "game_code": "SVS_virtual_racing"
  },
  {
    "game_name": "Virtual Speedway",
    "game_type": "Virtual Sports",
    "game_code": "SVS_virtual_speedway"
  },
  {
    "game_name": "Virtual Tennis",
    "game_type": "Virtual Sports",
    "game_code": "SVS_virtual_tennis"
  },
  {
    "game_name": "Virtual Trotting",
    "game_type": "Virtual Sports",
    "game_code": "SVS_virtual_trotting"
  },
  {
    "game_name": "Virtual Velodrome",
    "game_type": "Virtual Sports",
    "game_code": "SVS_virtual_velodrome"
  },
  {
    "game_name": "Voila",
    "game_type": "Slot",
    "game_code": "SMG_voila"
  },
  {
    "game_name": "Volcano Journey",
    "game_type": "Slot",
    "game_code": "SFB_VolcanoJourney"
  },
  {
    "game_name": "Volcano Journey Live",
    "game_type": "Live Host",
    "game_code": "SFB_VolcanoJourneyLive"
  },
  {
    "game_name": "Wacky Panda",
    "game_type": "Slot",
    "game_code": "SMG_wackyPanda"
  },
  {
    "game_name": "WD FuWa Fishing",
    "game_type": "Fishing",
    "game_code": "SFG_WDFuWaFishing"
  },
  {
    "game_name": "What A Hoot",
    "game_type": "Slot",
    "game_code": "SMG_whatAHoot"
  },
  {
    "game_name": "Wheel of Wishes",
    "game_type": "Slot",
    "game_code": "SMG_wheelofWishes"
  },
  {
    "game_name": "Wicked Tales: Dark Red",
    "game_type": "Slot",
    "game_code": "SMG_wickedTalesDarkRed"
  },
  {
    "game_name": "Wild Catch (New)",
    "game_type": "Slot",
    "game_code": "SMG_wildCatchNew"
  },
  {
    "game_name": "Wild Orient",
    "game_type": "Slot",
    "game_code": "SMG_wildOrient"
  },
  {
    "game_name": "Wild Scarabs",
    "game_type": "Slot",
    "game_code": "SMG_wildScarabs"
  },
  {
    "game_name": "Win Sum Dim Sum",
    "game_type": "Slot",
    "game_code": "SMG_winSumDimSum"
  },
  {
    "game_name": "WP 5PK",
    "game_type": "QiPai",
    "game_code": "SFG_WP5PK"
  },
  {
    "game_name": "WP Banker Niu Niu",
    "game_type": "QiPai",
    "game_code": "SFG_WPBankerNiuNiu"
  },
  {
    "game_name": "WP Banker Niu Niu (3 Open cards)",
    "game_type": "QiPai",
    "game_code": "SFG_WPBankerNiuNiu_3cards"
  },
  {
    "game_name": "WP Banker Niu Niu (4 Open cards)",
    "game_type": "QiPai",
    "game_code": "SFG_WPBankerNiuNiu_4cards"
  },
  {
    "game_name": "WP Bonus Texas",
    "game_type": "QiPai",
    "game_code": "SFG_WPBonusTexas"
  },
  {
    "game_name": "WP CaiShen Fruit Mario (Arcade Edition)",
    "game_type": "QiPai",
    "game_code": "SFG_WPCaiShenFruitMario"
  },
  {
    "game_name": "WP Chuhan Texas",
    "game_type": "QiPai",
    "game_code": "SFG_WPChuhanTexas"
  },
  {
    "game_name": "WP Doudizhu",
    "game_type": "QiPai",
    "game_code": "SFG_Doudizhu"
  },
  {
    "game_name": "WP Forest Party (JP)",
    "game_type": "QiPai",
    "game_code": "SFG_WPForestPartyJP"
  },
  {
    "game_name": "WP Golden Flower",
    "game_type": "QiPai",
    "game_code": "SFG_GoldenFlower"
  },
  {
    "game_name": "WP Golden Shark",
    "game_type": "QiPai",
    "game_code": "SFG_WPGoldenShark"
  },
  {
    "game_name": "WP Instant Golden Flower",
    "game_type": "QiPai",
    "game_code": "SFG_WPInstantGoldenFlower"
  },
  {
    "game_name": "WP Mahjong (2P Arcade)",
    "game_type": "QiPai",
    "game_code": "SFG_WPMahjong_2P"
  },
  {
    "game_name": "WP Niu Niu 100+ Players",
    "game_type": "QiPai",
    "game_code": "SFG_WP100NiuNiu"
  },
  {
    "game_name": "WP Tavern",
    "game_type": "QiPai",
    "game_code": "SFG_WPTavern"
  },
  {
    "game_name": "Zombie Blast",
    "game_type": "Arcade",
    "game_code": "SFB_Cascading_Zombie"
  },
  {
    "game_name": "Zombie Hoard",
    "game_type": "Slot",
    "game_code": "SMG_zombieHoard"
  }
]';

    // $someArray = json_decode($array, JSON_FORCE_OBJECT);
    $foo = utf8_encode($array);
    $data = json_decode($foo, true);

    $data2 = array();
    foreach($data as $g){
        if($g['game_type'] == "Live Dealer"){
          $game_type = 12;
        }else if($g['game_type'] == "Table"){
          $game_type = 5;
        }else if($g['game_type'] == "Virtual Sports"){
          $game_type = 16;
        }else if($g['game_type'] == "Live Host"){
          $game_type = 18;
        }else if($g['game_type'] == "Fishing"){
          $game_type = 9;
        }else if($g['game_type'] == "QiPai"){
          $game_type = 8;
        }else if($g['game_type'] == "Arcade"){
          $game_type = 8;
        }else if($g['game_type'] == "Slot"){
          $game_type = 1;
        }

        $game = array(
            "game_type_id"=> $game_type,
            "provider_id"=> 40,
            "sub_provider_id"=> 76,
            "game_name"=> $g['game_name'],
            "game_code"=>$g["game_code"],
            "icon"=> 'https://encrypted-tbn0.gstatic.com/images?q=tbn%3AANd9GcS9Oza5sOOuv12mmaLfvpzkjoCKTx2oFKbpPQ&usqp=CAU'
        );
        array_push($data2,$game);
    }
    DB::table('games')->insert($data2);
    return 'OK';
  }

    public function testTransaction(){
      return ClientRequestHelper::getTransactionId("43210","87654321");
    }

}
