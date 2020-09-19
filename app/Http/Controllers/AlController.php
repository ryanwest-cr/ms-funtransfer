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

      $array = '[
    {
        "game_name": "Golden Dragon",
        "game_type": "fish",
        "game_code": "GoldenDragon"
    },
    {
        "game_name": "Bonus Mania",
        "game_type": "slots",
        "game_code": "BonusMania"
    },
    {
        "game_name": "Treasure Bowl",
        "game_type": "slots",
        "game_code": "Treasurebowl"
    },
    {
        "game_name": "Golden Ball",
        "game_type": "slots",
        "game_code": "GoldenBall"
    },
    {
        "game_name": "Cocorico",
        "game_type": "slots",
        "game_code": "Cocorico"
    },
    {
        "game_name": "Space Cat",
        "game_type": "fish",
        "game_code": "SpaceCat"
    },
    {
        "game_name": "SuperShot",
        "game_type": "slots",
        "game_code": "SuperShot"
    },
    {
        "game_name": "Quick Play Candy",
        "game_type": "slots",
        "game_code": "QuickPlayCandy"
    },
    {
        "game_name": "Aladdin",
        "game_type": "slots",
        "game_code": "Aladdin"
    },
    {
        "game_name": "KA Fish Hunter",
        "game_type": "fish",
        "game_code": "KAFishHunter"
    },
    {
        "game_name": "Luck88",
        "game_type": "slots",
        "game_code": "Luck88"
    },
    {
        "game_name": "Genghis Khan",
        "game_type": "slots",
        "game_code": "GenghisKhan"
    },
    {
        "game_name": "Chinese Valentines Day",
        "game_type": "slots",
        "game_code": "ChineseValentinesDay"
    },
    {
        "game_name": "Red Boy",
        "game_type": "slots",
        "game_code": "RedBoy"
    },
    {
        "game_name": "King Octopus",
        "game_type": "fish",
        "game_code": "KingOctopus"
    },
    {
        "game_name": "Air Combat 1942",
        "game_type": "fish",
        "game_code": "AirCombat1942"
    },
    {
        "game_name": "Medal Winner Megaways",
        "game_type": "slots",
        "game_code": "MedalWinner"
    },
    {
        "game_name": "Mexicaliente",
        "game_type": "slots",
        "game_code": "Mexicaliente"
    },
    {
        "game_name": "Emoji",
        "game_type": "slots",
        "game_code": "Emoji"
    },
    {
        "game_name": "Space Storm",
        "game_type": "slots",
        "game_code": "SpaceStorm"
    },
    {
        "game_name": "Golden Shanghai",
        "game_type": "slots",
        "game_code": "GoldenShanghai"
    },
    {
        "game_name": "The Nut Cracker",
        "game_type": "slots",
        "game_code": "Nutcracker"
    },
    {
        "game_name": "The Apes",
        "game_type": "slots",
        "game_code": "Apes"
    },
    {
        "game_name": "Medieval Knights",
        "game_type": "slots",
        "game_code": "Knights"
    },
    {
        "game_name": "Yamato",
        "game_type": "slots",
        "game_code": "Yamato"
    },
    {
        "game_name": "Masquerade",
        "game_type": "slots",
        "game_code": "Masquerade"
    },
    {
        "game_name": "Fishing Expedition",
        "game_type": "slots",
        "game_code": "FishingExpedition"
    },
    {
        "game_name": "The Four Scholars",
        "game_type": "slots",
        "game_code": "FourScholars"
    },
    {
        "game_name": "Spring Blossom",
        "game_type": "slots",
        "game_code": "SpringBlossom"
    },
    {
        "game_name": "Yu Gong",
        "game_type": "slots",
        "game_code": "YuGong"
    },
    {
        "game_name": "Muscle Cars",
        "game_type": "slots",
        "game_code": "MuscleCars"
    },
    {
        "game_name": "Fortune Piggy Bank",
        "game_type": "slots",
        "game_code": "FortunePiggyBank"
    },
    {
        "game_name": "Nian",
        "game_type": "slots",
        "game_code": "Nian"
    },
    {
        "game_name": "Shopping Fiend",
        "game_type": "slots",
        "game_code": "ShoppingFiend"
    },
    {
        "game_name": "Lands of Gold",
        "game_type": "slots",
        "game_code": "LandOfGold"
    },
    {
        "game_name": "Dragon Gate",
        "game_type": "slots",
        "game_code": "DragonGate"
    },
    {
        "game_name": "Last Fantasy",
        "game_type": "slots",
        "game_code": "LastFantasy"
    },
    {
        "game_name": "KungFu Kash",
        "game_type": "slots",
        "game_code": "KungFu"
    },
    {
        "game_name": "Ba Wang Bie Ji",
        "game_type": "slots",
        "game_code": "BaWangBieJi"
    },
    {
        "game_name": "Dia De Muertos",
        "game_type": "slots",
        "game_code": "DayOfDead"
    },
    {
        "game_name": "Route 66",
        "game_type": "slots",
        "game_code": "Route66"
    },
    {
        "game_name": "KungFu Kaga",
        "game_type": "slots",
        "game_code": "KungFuKaga"
    },
    {
        "game_name": "Formosan Birds",
        "game_type": "slots",
        "game_code": "FormosanBirds"
    },
    {
        "game_name": "Won Won Rich",
        "game_type": "slots",
        "game_code": "WonWonRich"
    },
    {
        "game_name": "Triple Dragons",
        "game_type": "slots",
        "game_code": "TripleDragons"
    },
    {
        "game_name": "Fantasy 777",
        "game_type": "slots",
        "game_code": "Fantasy777"
    },
    {
        "game_name": "Three Gods",
        "game_type": "slots",
        "game_code": "ThreeGods"
    },
    {
        "game_name": "SuperShot 2",
        "game_type": "slots",
        "game_code": "SuperShot2"
    },
    {
        "game_name": "Fantasy Park",
        "game_type": "slots",
        "game_code": "FantasyPark"
    },
    {
        "game_name": "7 Heroines",
        "game_type": "slots",
        "game_code": "SevenHeroines"
    },
    {
        "game_name": "The Great Voyages",
        "game_type": "slots",
        "game_code": "GreatVoyages"
    },
    {
        "game_name": "The Golden Ax",
        "game_type": "slots",
        "game_code": "TheGoldenAx"
    },
    {
        "game_name": "Daji",
        "game_type": "slots",
        "game_code": "LeagueOfGods"
    },
    {
        "game_name": "Legend of Paladin",
        "game_type": "slots",
        "game_code": "LegendOfPaladin"
    },
    {
        "game_name": "Chi You",
        "game_type": "slots",
        "game_code": "ChiYou"
    },
    {
        "game_name": "Aurora",
        "game_type": "slots",
        "game_code": "Aurora"
    },
    {
        "game_name": "Shadow Play",
        "game_type": "slots",
        "game_code": "ShadowPlay"
    },
    {
        "game_name": "Legend of the White Snake",
        "game_type": "slots",
        "game_code": "WhiteSnakeLegend"
    },
    {
        "game_name": "Lounge Club",
        "game_type": "slots",
        "game_code": "LoungeClub"
    },
    {
        "game_name": "Bombing Fruit",
        "game_type": "slots",
        "game_code": "BombingFruit"
    },
    {
        "game_name": "Quadruple Dragons",
        "game_type": "slots",
        "game_code": "QuadrupleDragons"
    },
    {
        "game_name": "Millionaires",
        "game_type": "slots",
        "game_code": "Millionaires"
    },
    {
        "game_name": "Wild Vick",
        "game_type": "slots",
        "game_code": "WildVick"
    },
    {
        "game_name": "Leprechauns",
        "game_type": "slots",
        "game_code": "Leprechauns"
    },
    {
        "game_name": "God of Love",
        "game_type": "slots",
        "game_code": "GodofLove"
    },
    {
        "game_name": "Snow Leopards",
        "game_type": "slots",
        "game_code": "SnowLeopards"
    },
    {
        "game_name": "The Wizard of Oz",
        "game_type": "slots",
        "game_code": "WizardofOz"
    },
    {
        "game_name": "Big Apple",
        "game_type": "slots",
        "game_code": "BigApple"
    },
    {
        "game_name": "Three Heroes",
        "game_type": "slots",
        "game_code": "ThreeHeroes"
    },
    {
        "game_name": "Dim Sum",
        "game_type": "slots",
        "game_code": "DimSum"
    },
    {
        "game_name": "Age of Vikings",
        "game_type": "slots",
        "game_code": "Viking"
    },
    {
        "game_name": "California Gold Rush",
        "game_type": "slots",
        "game_code": "GoldRush"
    },
    {
        "game_name": "Super Video Poker",
        "game_type": "vpoker",
        "game_code": "SuperVideoPoker"
    },
    {
        "game_name": "Ares God of War",
        "game_type": "slots",
        "game_code": "Ares"
    },
    {
        "game_name": "Hua Mulan",
        "game_type": "slots",
        "game_code": "HuaMulan"
    },
    {
        "game_name": "Dark Fortress",
        "game_type": "slots",
        "game_code": "DarkFortress"
    },
    {
        "game_name": "Enchanted",
        "game_type": "slots",
        "game_code": "Enchanted"
    },
    {
        "game_name": "Pandoras Box",
        "game_type": "slots",
        "game_code": "Pandora"
    },
    {
        "game_name": "Siberian Wolves",
        "game_type": "slots",
        "game_code": "SiberianWolves"
    },
    {
        "game_name": "Gold Magic",
        "game_type": "slots",
        "game_code": "GoldMagic"
    },
    {
        "game_name": "Mythic",
        "game_type": "slots",
        "game_code": "Mythic"
    },
    {
        "game_name": "Tai Chi",
        "game_type": "slots",
        "game_code": "TaiChi"
    },
    {
        "game_name": "Alice In Wonderland",
        "game_type": "slots",
        "game_code": "AliceInWonderland"
    },
    {
        "game_name": "Baccarat",
        "game_type": "table",
        "game_code": "Baccarat"
    },
    {
        "game_name": "Super Keno",
        "game_type": "other",
        "game_code": "SuperKeno"
    },
    {
        "game_name": "Mahjong Master",
        "game_type": "slots",
        "game_code": "Mahjong"
    },
    {
        "game_name": "Captain Pirate",
        "game_type": "slots",
        "game_code": "Pirate"
    },
    {
        "game_name": "777 Vegas",
        "game_type": "slots",
        "game_code": "777Vegas"
    },
    {
        "game_name": "Dragon Ball",
        "game_type": "other",
        "game_code": "DragonBall"
    },
    {
        "game_name": "Ming Imperial Guards",
        "game_type": "slots",
        "game_code": "ImperialGuards"
    },
    {
        "game_name": "Party Girl",
        "game_type": "slots",
        "game_code": "PartyGirl"
    },
    {
        "game_name": "Primeval Rainforest",
        "game_type": "slots",
        "game_code": "PrimevalForest"
    },
    {
        "game_name": "Flaming 7s",
        "game_type": "slots",
        "game_code": "Flaming7"
    },
    {
        "game_name": "Lucky Penguins",
        "game_type": "slots",
        "game_code": "LuckyPenguins"
    },
    {
        "game_name": "Moon Goddess",
        "game_type": "slots",
        "game_code": "MoonGoddess"
    },
    {
        "game_name": "Heng and Ha",
        "game_type": "slots",
        "game_code": "HengandHa"
    },
    {
        "game_name": "Mayan Gold",
        "game_type": "slots",
        "game_code": "MayanGold"
    },
    {
        "game_name": "Super Bonus Mania",
        "game_type": "slots",
        "game_code": "SuperBonusMania"
    },
    {
        "game_name": "Mazu",
        "game_type": "slots",
        "game_code": "Mazu"
    },
    {
        "game_name": "Wild Alaska",
        "game_type": "slots",
        "game_code": "WildAlaska"
    },
    {
        "game_name": "Live Streaming Star",
        "game_type": "slots",
        "game_code": "LiveStreamingStar"
    },
    {
        "game_name": "Africa Run",
        "game_type": "slots",
        "game_code": "AfricaRun"
    },
    {
        "game_name": "Fire Dragons",
        "game_type": "slots",
        "game_code": "FireDragons"
    },
    {
        "game_name": "Cu Ju",
        "game_type": "slots",
        "game_code": "CuJu"
    },
    {
        "game_name": "Tao",
        "game_type": "slots",
        "game_code": "Tao"
    },
    {
        "game_name": "Dr. Geek",
        "game_type": "slots",
        "game_code": "DrGeek"
    },
    {
        "game_name": "Nine Lucks",
        "game_type": "slots",
        "game_code": "NineLucks"
    },
    {
        "game_name": "Giants",
        "game_type": "slots",
        "game_code": "Giants"
    },
    {
        "game_name": "Boxing Roo",
        "game_type": "slots",
        "game_code": "BoxingRoo"
    },
    {
        "game_name": "Pets",
        "game_type": "slots",
        "game_code": "Pets"
    },
    {
        "game_name": "Glacial Epoch",
        "game_type": "slots",
        "game_code": "GlacialEpoch"
    },
    {
        "game_name": "Modern 7 Wonders",
        "game_type": "slots",
        "game_code": "SevenWonders"
    },
    {
        "game_name": "Spinning In Space",
        "game_type": "slots",
        "game_code": "Space"
    },
    {
        "game_name": "Animal Fishing",
        "game_type": "slots",
        "game_code": "AnimalFishing"
    },
    {
        "game_name": "Hu Yeh",
        "game_type": "slots",
        "game_code": "HuYeh"
    },
    {
        "game_name": "Rarities",
        "game_type": "slots",
        "game_code": "Rarities"
    },
    {
        "game_name": "da Vinci",
        "game_type": "slots",
        "game_code": "DaVinci"
    },
    {
        "game_name": "Journey to the West",
        "game_type": "slots",
        "game_code": "JourneyToWest"
    },
    {
        "game_name": "Peter Pan",
        "game_type": "slots",
        "game_code": "PeterPan"
    },
    {
        "game_name": "SNS Friends",
        "game_type": "slots",
        "game_code": "SNS"
    },
    {
        "game_name": "Pinocchio",
        "game_type": "slots",
        "game_code": "Pinocchio"
    },
    {
        "game_name": "Erlang Shen",
        "game_type": "slots",
        "game_code": "ErlangShen"
    },
    {
        "game_name": "Silk Road",
        "game_type": "slots",
        "game_code": "SilkRoad"
    },
    {
        "game_name": "Snow Queen",
        "game_type": "slots",
        "game_code": "SnowQueen"
    },
    {
        "game_name": "Bakery Sweetness",
        "game_type": "slots",
        "game_code": "Bakery"
    },
    {
        "game_name": "Party Girl Ways",
        "game_type": "slots",
        "game_code": "PartyGirlWays"
    },
    {
        "game_name": "The Mask of Zorro",
        "game_type": "slots",
        "game_code": "Zorro"
    },
    {
        "game_name": "Come On Rhythm",
        "game_type": "slots",
        "game_code": "ComeOnRhythm"
    },
    {
        "game_name": "Speakeasy",
        "game_type": "slots",
        "game_code": "Speakeasy"
    },
    {
        "game_name": "Hou Yi",
        "game_type": "slots",
        "game_code": "HouYi"
    },
    {
        "game_name": "Mermaid Seas",
        "game_type": "slots",
        "game_code": "Mermaid"
    },
    {
        "game_name": "The King of Dinosaurs",
        "game_type": "slots",
        "game_code": "TRex"
    },
    {
        "game_name": "Fruit Mountain",
        "game_type": "other",
        "game_code": "FlowersFruitMountain"
    },
    {
        "game_name": "Farm Mania",
        "game_type": "slots",
        "game_code": "HappyFarm"
    },
    {
        "game_name": "Taiwan Black Bear",
        "game_type": "slots",
        "game_code": "TaiwanBlackBear"
    },
    {
        "game_name": "Lion Dance",
        "game_type": "slots",
        "game_code": "LionDance"
    },
    {
        "game_name": "Luxury Garage",
        "game_type": "slots",
        "game_code": "LuxuryGarage"
    },
    {
        "game_name": "A Thirsty Crow",
        "game_type": "slots",
        "game_code": "ThirstyCrow"
    },
    {
        "game_name": "Ghostbuster",
        "game_type": "slots",
        "game_code": "Ghostbuster"
    },
    {
        "game_name": "Pinata",
        "game_type": "slots",
        "game_code": "Pinata"
    },
    {
        "game_name": "Wizardry",
        "game_type": "slots",
        "game_code": "Wizardry"
    },
    {
        "game_name": "Nvwa",
        "game_type": "slots",
        "game_code": "Nvwa"
    },
    {
        "game_name": "Snow White",
        "game_type": "slots",
        "game_code": "SnowWhite"
    },
    {
        "game_name": "Catch The Thief",
        "game_type": "slots",
        "game_code": "CatchTheThief"
    },
    {
        "game_name": "Wolf Warrior",
        "game_type": "slots",
        "game_code": "WolfWarrior"
    },
    {
        "game_name": "Blocky Block",
        "game_type": "slots",
        "game_code": "BlockyBlocks"
    },
    {
        "game_name": "Legend of Dragons",
        "game_type": "slots",
        "game_code": "DragonsLegend"
    },
    {
        "game_name": "Royal Demeanor",
        "game_type": "slots",
        "game_code": "RoyalDemeanor"
    },
    {
        "game_name": "Zombie Land",
        "game_type": "slots",
        "game_code": "ZombieLand"
    },
    {
        "game_name": "Dreamcatcher",
        "game_type": "slots",
        "game_code": "Dreamcatcher"
    },
    {
        "game_name": "Volcano Adventure",
        "game_type": "slots",
        "game_code": "VolcanoAdventure"
    },
    {
        "game_name": "Rich Squire",
        "game_type": "slots",
        "game_code": "RichSquire"
    },
    {
        "game_name": "Samurai Way",
        "game_type": "slots",
        "game_code": "Samurai"
    },
    {
        "game_name": "Fast Blast",
        "game_type": "slots",
        "game_code": "FastBlast"
    },
    {
        "game_name": "Polynesian",
        "game_type": "slots",
        "game_code": "Polynesian"
    },
    {
        "game_name": "Musketeers",
        "game_type": "slots",
        "game_code": "Musketeers"
    },
    {
        "game_name": "Imperial Girls",
        "game_type": "slots",
        "game_code": "ImperialGirls"
    },
    {
        "game_name": "Mysterious Pyramid",
        "game_type": "slots",
        "game_code": "Egypt"
    },
    {
        "game_name": "Three Little Pigs",
        "game_type": "slots",
        "game_code": "ThreeLittlePigs"
    },
    {
        "game_name": "The Grandmaster",
        "game_type": "slots",
        "game_code": "TheGrandmaster"
    },
    {
        "game_name": "Street Racing",
        "game_type": "slots",
        "game_code": "Speed"
    },
    {
        "game_name": "Jungle",
        "game_type": "slots",
        "game_code": "Jungle"
    },
    {
        "game_name": "Egyptian Mythology",
        "game_type": "slots",
        "game_code": "EgyptianMythology"
    },
    {
        "game_name": "Wild Wild Bell",
        "game_type": "slots",
        "game_code": "WildWildBell"
    },
    {
        "game_name": "Origin Of Fire",
        "game_type": "slots",
        "game_code": "OriginOfFire"
    },
    {
        "game_name": "Neanderthals",
        "game_type": "slots",
        "game_code": "Neanderthals"
    },
    {
        "game_name": "Egyptian Empress",
        "game_type": "slots",
        "game_code": "EgyptianEmpress"
    },
    {
        "game_name": "Robots",
        "game_type": "slots",
        "game_code": "Robots"
    },
    {
        "game_name": "Romance of the Three Kingdoms",
        "game_type": "fish",
        "game_code": "ThreeKingdoms"
    },
    {
        "game_name": "WanFu JinAn",
        "game_type": "slots",
        "game_code": "WanFuJinAn"
    },
    {
        "game_name": "The Gingerbread Land",
        "game_type": "slots",
        "game_code": "TheGingerbreadLand"
    },
    {
        "game_name": "Veggies Plot",
        "game_type": "slots",
        "game_code": "VeggiesPlot"
    },
    {
        "game_name": "Sunny Bikini",
        "game_type": "slots",
        "game_code": "SunnyBikini"
    },
    {
        "game_name": "Bull Stampede",
        "game_type": "slots",
        "game_code": "BullStampede"
    },
    {
        "game_name": "Stonehenge",
        "game_type": "slots",
        "game_code": "Stonehenge"
    },
    {
        "game_name": "Horoscope",
        "game_type": "slots",
        "game_code": "Horoscope"
    },
    {
        "game_name": "Pirate King",
        "game_type": "slots",
        "game_code": "PirateKing"
    },
    {
        "game_name": "Trippy Mushrooms",
        "game_type": "slots",
        "game_code": "Mushrooms"
    },
    {
        "game_name": "Cowboys",
        "game_type": "slots",
        "game_code": "Cowboys"
    },
    {
        "game_name": "Poseidons Treasure",
        "game_type": "slots",
        "game_code": "Poseidon"
    },
    {
        "game_name": "Dragons Way",
        "game_type": "slots",
        "game_code": "DragonsWay"
    },
    {
        "game_name": "Lost Realm",
        "game_type": "slots",
        "game_code": "LostRealm"
    },
    {
        "game_name": "Bonus Mania Deluxe",
        "game_type": "slots",
        "game_code": "BonusManiaDeluxe"
    },
    {
        "game_name": "Four Beauties",
        "game_type": "slots",
        "game_code": "FourBeauties"
    },
    {
        "game_name": "Fortune Lions",
        "game_type": "slots",
        "game_code": "FortuneLions"
    },
    {
        "game_name": "X-Bomber",
        "game_type": "slots",
        "game_code": "XBomber"
    },
    {
        "game_name": "X-Elements",
        "game_type": "slots",
        "game_code": "XElements"
    },
    {
        "game_name": "Vampires Tale",
        "game_type": "slots",
        "game_code": "Vampire"
    },
    {
        "game_name": "Fluffy Buddy",
        "game_type": "slots",
        "game_code": "FluffyBuddy"
    },
    {
        "game_name": "Bubble Double",
        "game_type": "slots",
        "game_code": "BubbleDouble"
    },
    {
        "game_name": "Archer Robin Hood",
        "game_type": "slots",
        "game_code": "ArcherRobinHood"
    },
    {
        "game_name": "Fortune God",
        "game_type": "slots",
        "game_code": "FortuneGod"
    },
    {
        "game_name": "Bumble Bee",
        "game_type": "slots",
        "game_code": "BumbleBee"
    },
    {
        "game_name": "Chinese Opera",
        "game_type": "slots",
        "game_code": "ChineseOpera"
    },
    {
        "game_name": "Red Riding Hood",
        "game_type": "slots",
        "game_code": "RedRidingHood"
    },
    {
        "game_name": "Yun Cai Tong Zi",
        "game_type": "slots",
        "game_code": "YunCaiTongZi"
    },
    {
        "game_name": "Don Quixote",
        "game_type": "slots",
        "game_code": "DonQuixote"
    },
    {
        "game_name": "Stocked Bar",
        "game_type": "slots",
        "game_code": "StockedBar"
    },
    {
        "game_name": "Kitty Living",
        "game_type": "slots",
        "game_code": "Kitty"
    },
    {
        "game_name": "Nezha",
        "game_type": "slots",
        "game_code": "Nezha"
    },
    {
        "game_name": "Wu Gang",
        "game_type": "slots",
        "game_code": "WuGang"
    },
    {
        "game_name": "A Girls Best Friend",
        "game_type": "slots",
        "game_code": "Gem"
    },
    {
        "game_name": "Dragon Boat",
        "game_type": "slots",
        "game_code": "DragonBoat"
    },
    {
        "game_name": "Quick Play Jewels",
        "game_type": "slots",
        "game_code": "QuickPlayJewels"
    },
    {
        "game_name": "Boy Toys",
        "game_type": "slots",
        "game_code": "BoyToys"
    },
    {
        "game_name": "Fairy Dust",
        "game_type": "slots",
        "game_code": "FairyDust"
    },
    {
        "game_name": "Safari Slots",
        "game_type": "slots",
        "game_code": "Safari"
    },
    {
        "game_name": "Hat Seller",
        "game_type": "slots",
        "game_code": "HatSeller"
    },
    {
        "game_name": "Princess Wencheng",
        "game_type": "slots",
        "game_code": "Wencheng"
    },
    {
        "game_name": "Tower of Babel",
        "game_type": "slots",
        "game_code": "TowerofBabel"
    },
    {
        "game_name": "Artist Studio",
        "game_type": "slots",
        "game_code": "ArtistStudio"
    },
    {
        "game_name": "Fruit Party",
        "game_type": "slots",
        "game_code": "FruitParty"
    },
    {
        "game_name": "Miss Tiger",
        "game_type": "slots",
        "game_code": "MissTiger"
    },
    {
        "game_name": "UFO",
        "game_type": "slots",
        "game_code": "UFO"
    },
    {
        "game_name": "Jellymania",
        "game_type": "slots",
        "game_code": "JellyMania"
    },
    {
        "game_name": "Glass Slipper",
        "game_type": "slots",
        "game_code": "Cinderella"
    },
    {
        "game_name": "Candy Storm",
        "game_type": "slots",
        "game_code": "CandyStorm"
    },
    {
        "game_name": "The Lotus Lamp",
        "game_type": "slots",
        "game_code": "LotusLamp"
    },
    {
        "game_name": "Cai Yuan Guang Jin",
        "game_type": "slots",
        "game_code": "CaiYuanGuangJin"
    },
    {
        "game_name": "Deep Sea Adventure",
        "game_type": "slots",
        "game_code": "DeepSea"
    },
    {
        "game_name": "Fastbreak",
        "game_type": "slots",
        "game_code": "Fastbreak"
    },
    {
        "game_name": "Quick Play Mahjong",
        "game_type": "slots",
        "game_code": "QuickPlayMahjong"
    },
    {
        "game_name": "Honey Money",
        "game_type": "slots",
        "game_code": "HoneyMoney"
    },
    {
        "game_name": "Frankenstein",
        "game_type": "slots",
        "game_code": "Frankenstein"
    },
    {
        "game_name": "Joker Slot",
        "game_type": "slots",
        "game_code": "JokerSlot"
    },
    {
        "game_name": "Crazy Circus",
        "game_type": "slots",
        "game_code": "CrazyCircus"
    }]';

    // $someArray = json_decode($array, JSON_FORCE_OBJECT);
    $foo = utf8_encode($array);
    $data = json_decode($foo, true);

    $data2 = array();
    foreach($data as $g){
         if($g['game_type'] == "table"){
           $game_type = 5;
         }else if($g['game_type'] == "other"){
           $game_type = 13;
         }else if($g['game_type'] == "vpoker"){
           $game_type = 16;
         }else if($g['game_type'] == "fish"){
           $game_type = 9;
         }else if($g['game_type'] == "slots"){
           $game_type = 1;
         }

        $game = array(
            "game_type_id"=> $game_type,
            "provider_id"=> 43,
            "sub_provider_id"=> 75,
            "game_name"=> $g['game_name'],
            "game_code"=>$g["game_code"],
            "icon"=> 'https://www.gamblerspick.com/uploads/monthly_2019_05/kagaming.png.23d9f8479ee4e7a28e6ac4ee8509994e.png'
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
