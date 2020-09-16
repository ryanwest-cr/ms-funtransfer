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

   public function tapulan() {

     $array = '[
{
  "game_info": "Sic Bo", 
  "game_name": "Super Sic Bo", 
  "game_code": "SuperSicBo000001"}, 
{
  "game_info": "Dragon Tiger", 
  "game_name": "Dragon Tiger", 
  "game_code": "DragonTiger00001"}, 
{
  "game_info": "Baccarat", 
  "game_name": "Speed Baccarat A", 
  "game_code": "leqhceumaq6qfoug"}, 
{
  "game_info": "Baccarat", 
  "game_name": "Speed Baccarat B", 
  "game_code": "lv2kzclunt2qnxo5"}, 
{
  "game_info": "Baccarat", 
  "game_name": "Speed Baccarat C", 
  "game_code": "ndgvwvgthfuaad3q"}, 
{
  "game_info": "Baccarat", 
  "game_name": "Speed Baccarat D", 
  "game_code": "ndgvz5mlhfuaad6e"}, 
{
  "game_info": "Baccarat", 
  "game_name": "Speed Baccarat E", 
  "game_code": "ndgv45bghfuaaebf"}, 
{
  "game_info": "Baccarat", 
  "game_name": "Speed Baccarat F", 
  "game_code": "nmwde3fd7hvqhq43"}, 
{
  "game_info": "Baccarat", 
  "game_name": "Speed Baccarat G", 
  "game_code": "nmwdzhbg7hvqh6a7"}, 
{
  "game_info": "Baccarat", 
  "game_name": "Speed Baccarat H", 
  "game_code": "nxpj4wumgclak2lx"}, 
{
  "game_info": "Baccarat", 
  "game_name": "Speed Baccarat I", 
  "game_code": "nxpkul2hgclallno"}, 
{
  "game_info": "Baccarat", 
  "game_name": "Speed Baccarat J", 
  "game_code": "obj64qcnqfunjelj"}, 
{
  "game_info": "Baccarat", 
  "game_name": "Lightning Baccarat", 
  "game_code": "LightningBac0001"}, 
{
  "game_info": "Baccarat", 
  "game_name": "No Comm Speed Baccarat A", 
  "game_code": "ndgv76kehfuaaeec"}, 
{
  "game_info": "Baccarat", 
  "game_name": "No Commission Baccarat", 
  "game_code": "NoCommBac0000001"}, 
{
  "game_info": "Baccarat", 
  "game_name": "Baccarat A", 
  "game_code": "oytmvb9m1zysmc44"}, 
{
  "game_info": "Baccarat", 
  "game_name": "Baccarat B", 
  "game_code": "60i0lcfx5wkkv3sy"}, 
{
  "game_info": "Baccarat", 
  "game_name": "Baccarat C", 
  "game_code": "ndgvs3tqhfuaadyg"}, 
{
  "game_info": "Baccarat", 
  "game_name": "Baccarat Squeeze", 
  "game_code": "zixzea8nrf1675oh"}, 
{
  "game_info": "Baccarat", 
  "game_name": "Baccarat Control Squeeze", 
  "game_code": "k2oswnib7jjaaznw"}, 
{
  "game_info": "Baccarat", 
  "game_name": "Salon Privé Baccarat A", 
  "game_code": "SalPrivBac000001"}, 
{
  "game_info": "Baccarat", 
  "game_name": "Salon Privé Baccarat B", 
  "game_code": "n7ltqx5j25sr7xbe"}, 
{
  "game_info": "Baccarat", 
  "game_name": "Salon Privé Baccarat C", 
  "game_code": "ok37hvy3g7bofp4l"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack A", 
  "game_code": "uwd2bl2khwcikjlz"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack B", 
  "game_code": "xphpcthv8e6ivc16"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack White A", 
  "game_code": "k4r2ejwx4eqqb6tv"}, 
{
  "game_info": "FreeBet Blackjack", 
  "game_name": "Free Bet Blackjack", 
  "game_code": "FreeBet000000001"}, 
{
  "game_info": "Power Infinite Blackjack", 
  "game_name": "Power Blackjack", 
  "game_code": "PowerInfiniteBJ1"}, 
{
  "game_info": "Scalable Blackjack", 
  "game_name": "Infinite Blackjack", 
  "game_code": "mrfykemt5slanyi5"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack Party", 
  "game_code": "sni5cza6d1vvl50i"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Speed VIP Blackjack A", 
  "game_code": "SpeedBlackjack01"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Speed VIP Blackjack B", 
  "game_code": "SpeedBlackjack02"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Speed VIP Blackjack C", 
  "game_code": "SpeedBlackjack03"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Speed VIP Blackjack D", 
  "game_code": "SpeedBlackjack04"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack C", 
  "game_code": "jhs44mm0v3fi3aux"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack White B", 
  "game_code": "k4r2hyhw4eqqb6us"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack D", 
  "game_code": "xqyb2u7fqkexxpa0"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack White C", 
  "game_code": "k4r2kvd34eqqb6vh"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack E", 
  "game_code": "ylq4gmw8yl22u5dj"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack G", 
  "game_code": "1xwfnktjybsolkn6"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack H", 
  "game_code": "nc3u2l6y0khszjv7"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack I", 
  "game_code": "xstnlyzrm345ev95"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack J", 
  "game_code": "i5j1cyqhrypkih23"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack Silver A", 
  "game_code": "gkmq0o2hryjyqu30"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack Silver B", 
  "game_code": "9f4xhuhdd005xlbl"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack Silver C", 
  "game_code": "qlrc3fq3v7p6awm4"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack Silver D", 
  "game_code": "qckwjf2o52r9ikeb"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack Silver E", 
  "game_code": "gazgtkid9h1b0dn9"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack Silver F", 
  "game_code": "lnofoyxv756qaezy"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack Silver G", 
  "game_code": "lnofpmm3756qae2c"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack VIP A", 
  "game_code": "0mvn914lkmo9vaq8"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack VIP B", 
  "game_code": "cpxl81x0rgi34cmo"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack VIP C", 
  "game_code": "l5aug44hhzr3qvxs"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack VIP D", 
  "game_code": "o3d9tx3u8kd0yawc"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack VIP E", 
  "game_code": "psm2um7k4da8zwc2"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack VIP F", 
  "game_code": "ehw2fvl831m5n2km"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack VIP G", 
  "game_code": "z5pf5pichcsw3d2o"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack VIP H ", 
  "game_code": "s63nx2mpdomgjagb"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack VIP I", 
  "game_code": "lnofn2yl756qaezm"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack VIP J", 
  "game_code": "m6mfo66sb7eafnzz"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack VIP K", 
  "game_code": "m6mfsirtb7eafn5c"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack VIP L", 
  "game_code": "nbjettfehawanhes"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack VIP M", 
  "game_code": "nbjetztthawanhey"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack VIP N", 
  "game_code": "nkyiswhd2jpbw4i4"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack VIP O", 
  "game_code": "nkyivihc2jpbw4uy"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack VIP P", 
  "game_code": "nsxqkywul2nzcwwh"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack VIP Q", 
  "game_code": "oa7fvyaiqfueq5ob"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack VIP R", 
  "game_code": "nsxqpyiol2nzcz6t"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack VIP S", 
  "game_code": "nveq65dtmn6n4mnd"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack VIP T", 
  "game_code": "nveq66tfmn6n4moi"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack VIP U", 
  "game_code": "bciewncrf5ijneys"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack VIP V", 
  "game_code": "2uxabtm1rwaxcmdm"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack VIP X", 
  "game_code": "bghflgi59db7d7r2"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack VIP Z", 
  "game_code": "oa7fpshyqfueqxuj"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack Platinum VIP", 
  "game_code": "h463tlq1rhl1lfr2"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack Fortune VIP", 
  "game_code": "ejx1a04w4ben0mou"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack Grand VIP", 
  "game_code": "gfzrqe4hqv24kukc"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Blackjack Diamond VIP", 
  "game_code": "rdefcn4sffgo39l7"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Salon Privé Blackjack A", 
  "game_code": "mdkqdxtkdctrhnsx"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Salon Privé Blackjack B", 
  "game_code": "olbibp3fylzaxvhb"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Salon Privé Blackjack C", 
  "game_code": "mdkqfe74dctrhntj"}, 
{
  "game_info": "Blackjack", 
  "game_name": "Salon Privé Blackjack D", 
  "game_code": "olbinkuoylzayeoj"}, 
{
  "game_info": "Slingshot", 
  "game_name": "Lightning Roulette", 
  "game_code": "LightningTable01"}, 
{
  "game_info": "Roulette", 
  "game_name": "Roulette", 
  "game_code": "vctlz20yfnmp1ylr"}, 
{
  "game_info": "Roulette", 
  "game_name": "Immersive Roulette", 
  "game_code": "7x0b1tgh7agmf6hv"}, 
{
  "game_info": "Roulette", 
  "game_name": "Instant Roulette", 
  "game_code": "InstantRo0000001"}, 
{
  "game_info": "Roulette", 
  "game_name": "Speed Roulette", 
  "game_code": "lkcbrbdckjxajdol"}, 
{
  "game_info": "Roulette", 
  "game_name": "American Roulette", 
  "game_code": "AmericanTable001"}, 
{
  "game_info": "Roulette", 
  "game_name": "Double Ball Roulette", 
  "game_code": "DoubleBallRou001"}, 
{
  "game_info": "Slingshot", 
  "game_name": "Speed Auto Roulette", 
  "game_code": "SpeedAutoRo00001"}, 
{
  "game_info": "Roulette", 
  "game_name": "Salon Privé Roulette", 
  "game_code": "mdkqijp3dctrhnuv"}, 
{
  "game_info": "Sidebet City", 
  "game_name": "Side Bet City", 
  "game_code": "SBCTable00000001"}, 
{
  "game_info": "Casino Holdem", 
  "game_name": "Casino Holdem", 
  "game_code": "HoldemTable00001"}, 
{
  "game_info": "Double Hand Casino Holdem Poker", 
  "game_name": "2 Hand Casino Holdem", 
  "game_code": "DHPTable00000001"}, 
{
  "game_info": "Extreme Texas Holdem ", 
  "game_name": "Extreme Texas Holdem ", 
  "game_code": "ETHTable00000001"}, 
{
  "game_info": "Triple Card Poker ", 
  "game_name": "Triple Card Poker ", 
  "game_code": "TRPTable00000001"}, 
{
  "game_info": "Caribbean Stud Poker", 
  "game_name": "Caribbean Stud Poker", 
  "game_code": "CSPTable00000001"}, 
{
  "game_info": "Texas Holdem Bonus Poker", 
  "game_name": "Texas Holdem Bonus Poker", 
  "game_code": "THBTable00000001"}, 
{
  "game_info": "Crazy Time", 
  "game_name": "Crazy Time", 
  "game_code": "CrazyTime0000001"}, 
{
  "game_info": "Monopoly", 
  "game_name": "Monopoly Live", 
  "game_code": "Monopoly00000001"}, 
{
  "game_info": "Deal or No Deal", 
  "game_name": "Deal or No Deal", 
  "game_code": "dealnodeal000001"}, 
{
  "game_info": "Money Wheel", 
  "game_name": "Dream Catcher ", 
  "game_code": "MOWDream00000001"}, 
{
  "game_info": "Top Card", 
  "game_name": "Football studio", 
  "game_code": "TopCard000000001"}, 
{
  "game_info": "Lightning Dice", 
  "game_name": "Lightning Dice", 
  "game_code": "LightningDice001"}, 
{
  "game_info": "Bingo", 
  "game_name": "Mega Ball", 
  "game_code": "MegaBall00000001"}, 
{
  "game_info": "RNG Roulette", 
  "game_name": "First Person Lightning Roulette", 
  "game_code": "rng-rt-lightning"}, 
{
  "game_info": "RNG Roulette", 
  "game_name": "First Person Roulette", 
  "game_code": "rng-rt-european0"}, 
{
  "game_info": "RNG Blackjack", 
  "game_name": "First Person Blackjack", 
  "game_code": "rng-bj-standard0"}, 
{
  "game_info": "First Person Bingo", 
  "game_name": "First Person Mega Ball", 
  "game_code": "RngMegaBall00001"}, 
{
  "game_info": "RNG Money Wheel", 
  "game_name": "First Person Dream Catcher", 
  "game_code": "rng-dreamcatcher"}, 
{
  "game_info": "RNG Dragon Tiger", 
  "game_name": "First Person Dragon Tiger", 
  "game_code": "rng-dragontiger0"}, 
{
  "game_info": "RNG Top Card", 
  "game_name": "First Person Top Card", 
  "game_code": "rng-topcard00001"}, 
{
  "game_info": "RNG Baccarat", 
  "game_name": "First Person Baccarat", 
  "game_code": "rngbaccarat00000"}
]';

// $someArray = json_decode($array, JSON_FORCE_OBJECT);
$foo = utf8_encode($array);
$data = json_decode($foo, true);

$data2 = array();
    foreach($data as $g){
        $game_type = 18;
        $game = array(
          "info" => $g['game_info'],
            "game_type_id"=> 18,
            "provider_id"=> 42,
            "sub_provider_id"=> 74,
            "game_name"=> $g['game_name'],
            "game_code"=>$g["game_code"],
            "icon"=> 'https://asset-dev.betrnk.games/images/games/casino/EvolutionGaming/subproviders/EvolutionGaming-Direct/EvolutionGaming-Direct.jpg'
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
