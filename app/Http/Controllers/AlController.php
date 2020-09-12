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
    "game_type": "Slots",
    "game_code": "sw_8tr1qu",
    "game_name": "8 Treasures 1 Queen"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_88sf",
    "game_name": "88 Shi fu"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_888t",
    "game_name": "888Turtles"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_npot",
    "game_name": "9 Pandas on Top"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_9s1k",
    "game_name": "9 Sons, 1 King"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_al",
    "game_name": "Amazon Lady"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_af",
    "game_name": "Asian Fantasy"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_ar",
    "game_name": "Aztec Reel"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_azre",
    "game_name": "Aztec Respin"
  },
  {
    "game_type": "Table Game",
    "game_code": "sw_bac",
    "game_name": "Baccarat"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_bzxt",
    "game_name": "Bao Zhu Xuan Tian"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_bb",
    "game_name": "Big Buffalo"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_bl",
    "game_name": "Big Lion"
  },
  {
    "game_type": "Тable Game",
    "game_code": "sw_bjc",
    "game_name": "Blackjack"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_bd",
    "game_name": "Bonus Digger"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_bosl",
    "game_name": "Book of Shangri-La"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_bul",
    "game_name": "Buffalo Lightning"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_bm",
    "game_name": "Butterfly Moon"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_btrb",
    "game_name": "By the Rivers of Buffalo"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_cscf",
    "game_name": "Cai Shen Ci Fu"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_csy",
    "game_name": "Cai Shen Ye"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_ge1xas-te",
    "game_name": "Cai Shen Lai Le"
  },
  {
    "game_type": "Shooting Game",
    "game_code": "sw_cashdaha",
    "game_name": "Cai Shen Da Hai"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_ch8",
    "game_name": "Chaoji 888"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_chwi",
    "game_name": "Cheshire Wild"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_chfi",
    "game_name": "Chicken Fiesta"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_cf",
    "game_name": "Chilli Festival"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_cmw",
    "game_name": "China Mega Wild"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_cts",
    "game_name": "Chois Travelling Show"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_dhcf",
    "game_name": "Da Hei Ci Fu"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_dld",
    "game_name": "Da Lan Deluxe"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_daladejaab_jp",
    "game_name": "Da Lan Deluxe Jackpot"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_dmzc",
    "game_name": "Da Mao Zhao Cai: Money Cat"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_dtc",
    "game_name": "Diamonds Top Code"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_dd",
    "game_name": "Dolphin Delight"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_db",
    "game_name": "Double Bonus Slots"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_dc",
    "game_name": "Double Chilli"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_dj",
    "game_name": "Double Jungle"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_dosc7s",
    "game_name": "Double Scatter 7’s"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_dr",
    "game_name": "Dragon Riches"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_ewb",
    "game_name": "East Wind Battle"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_ec",
    "game_name": "Egypt Cash"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_es",
    "game_name": "Egypt Spin"
  },
  {
    "game_type": "Table Game",
    "game_code": "sw_er",
    "game_name": "European Roulette"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_filifo",
    "game_name": "Fei Lian Fortune"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_fkmj",
    "game_name": "Feng Kuang Ma Jiang"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_fbbls",
    "game_name": "Fire Baoding Balls"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_ff",
    "game_name": "Fire Festival"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_fr",
    "game_name": "Fire Reel"
  },
  {
    "game_type": "Table Game",
    "game_code": "sw_fish_prawn_crab",
    "game_name": "Fish Prawn Crab"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_whmj",
    "game_name": "Five Tiger Generals"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_fp",
    "game_name": "Flaming Phoenix"
  },
  {
    "game_type": "Shooting Game",
    "game_code": "sw_fj",
    "game_name": "Fly Jet"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_fcase",
    "game_name": "Fortune Case"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_fc",
    "game_name": "Fortune Castle"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_fl",
    "game_name": "Fortune Lions"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_fofefa",
    "game_name": "Four Femme Fatales"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_ffruits",
    "game_name": "Freaky Fruits"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_fg",
    "game_name": "Fruity Girl"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_fbb",
    "game_name": "Fu Bao Bao"
  },
  {
    "game_type": "Shooting Game",
    "game_code": "sw_fufarm",
    "game_name": "Fu Farm"
  },
  {
    "game_type": "Shooting Game",
    "game_code": "sw_fufarm_jp",
    "game_name": "Fu Farm Jackpot"
  },
  {
    "game_type": "Shooting Game",
    "game_code": "sw_fufish_intw",
    "game_name": "Fu Fish"
  },
  {
    "game_type": "Shooting Game",
    "game_code": "sw_fufish-jp",
    "game_name": "Fu Fish Jackpot"
  },
  {
    "game_type": "Shooting Game",
    "game_code": "sw_fuqsg",
    "game_name": "Fu Qi Shui Guo"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_fsqt",
    "game_name": "Fu Shou Qi Tian"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_fzyq",
    "game_name": "Fu Zai Yan Qian"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_mopa",
    "game_name": "Full Moon Madness"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_gk",
    "game_name": "Gem King"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_gq",
    "game_name": "Gem Queen"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_gq_ab_jp",
    "game_name": "Gem Queen Jackpot"
  },
  {
    "game_type": "Slots & Cascade",
    "game_code": "sw_gt",
    "game_name": "Gem Temple"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_gatc",
    "game_name": "Gems and the City"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_gtg",
    "game_name": "Genghis The Great"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_gemerenigael",
    "game_name": "Genie Mega Reels"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_ges",
    "game_name": "Genie Shot"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_gel",
    "game_name": "Glorious Top Elements"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_gg",
    "game_name": "Go Gold"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_gol",
    "game_name": "God of Lightning"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_go8d",
    "game_name": "Goddess of 8 Directions"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_gs",
    "game_name": "Gold Shot"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_ggdn",
    "game_name": "Golden Garden"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_ggrizzly",
    "game_name": "Golden Grizzly"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_gm",
    "game_name": "Gorilla Moon"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_gr",
    "game_name": "Gorilla’s Realm"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_gncs",
    "game_name": "Gou Nian Cai Shen"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_hcs",
    "game_name": "Hao Shi Cheng Shuang"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_h2h",
    "game_name": "Heart 2 Hert"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_hd",
    "game_name": "Hearts & Dragons"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_hp",
    "game_name": "Heavenly Phoenix"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_hr",
    "game_name": "Heavenly Ruler"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_hg",
    "game_name": "Highway Gold"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_hlcs",
    "game_name": "Huan Le Cai Shen"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_ijp",
    "game_name": "Inca Jackpot"
  },
  {
    "game_type": "Table Game",
    "game_code": "sw_jackob",
    "game_name": "Jacks or Better"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_jxl",
    "game_name": "Ji Xiang Long"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_jjbx",
    "game_name": "Jin Ji Bao Xi"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_jqw",
    "game_name": "Jin Qian Wa"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_jqw_ab_jp",
    "game_name": "Jin Qian Wa Jackpot"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_jodobi",
    "game_name": "Jogo do Bicho"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_jogowi",
    "game_name": "Joker Goes Wild"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_kog",
    "game_name": "King of Gods"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_ksm",
    "game_name": "King Solomon Mines"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_kiwi",
    "game_name": "Kitty Wild"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_ks",
    "game_name": "Knight’s Saga"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_kxcs",
    "game_name": "Ku Xuan Cai Shen"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_lodk",
    "game_name": "Legend of Dragon Koi"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_lohy",
    "game_name": "Legend of Hou Yi"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_ld",
    "game_name": "Legendary Dragons"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_le",
    "game_name": "Leprybunny"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_lll",
    "game_name": "Long Long Long"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_lomutabangno",
    "game_name": "Long Mu Tan Bao"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_loofthsp",
    "game_name": "Lord of the Spins"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_lomathtt",
    "game_name": "Lothar Matthaus. Be a Winner"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_lcc",
    "game_name": "Lucky Chan Chu"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_luckyfim",
    "game_name": "Lucky Fisherman"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_lucky_omq",
    "game_name": "Lucky OMQ"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_moo",
    "game_name": "Magic of Oz"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_mf",
    "game_name": "Maneki Fortunes"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_ms",
    "game_name": "Maverick Saloon"
  },
  {
    "game_type": "Cascade",
    "game_code": "sw_myjp",
    "game_name": "Maya Jackpot"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_mm",
    "game_name": "Maya Millions"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_mwol",
    "game_name": "Maya Wheel of Luck"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_mer",
    "game_name": "Mermaid Beauty"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_mj",
    "game_name": "Mermaid Jewels"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_mr",
    "game_name": "Metal Reel"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_mdls",
    "game_name": "Middle Shot"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_mls",
    "game_name": "Midnight Lucky Sky"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_mt",
    "game_name": "Mighty Trio"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_mc",
    "game_name": "Miss Candy"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_mpays",
    "game_name": "Monkey Pays"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_mpp",
    "game_name": "Monkey Pool Party"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_mp",
    "game_name": "Moon Palace"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_mrmnky",
    "game_name": "Mr. Monkey"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_nyg",
    "game_name": "New York Gangs"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_negogrwtldav",
    "game_name": "Newton Golden Gravity"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_nilazhnu",
    "game_name": "Niu Lang Zhi Nü"
  },
  {
    "game_type": "Arcade",
    "game_code": "sw_or",
    "game_name": "Ocean Ruler"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_omqjp",
    "game_name": "Old Master Q"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_olcaymsh",
    "game_name": "Olympic cash"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_pc",
    "game_name": "Panda Chef"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_pg",
    "game_name": "Panda Gold"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_pp",
    "game_name": "Panda Prize"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_pvg",
    "game_name": "Panda Vs Goat"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_pn",
    "game_name": "Party Night"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_pe",
    "game_name": "Pirate Empress"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_pote",
    "game_name": "Pirate on the Edge"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_pt",
    "game_name": "Polar Tale"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_ps",
    "game_name": "Pot Shot"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_qotp",
    "game_name": "Queen of the Pharaohs"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_qv",
    "game_name": "Queen of the Vikings"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_qow",
    "game_name": "Queen of Wands"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_qoiaf",
    "game_name": "Queens of Ice and Fire"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_rambo",
    "game_name": "Rambo"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_rf",
    "game_name": "Ramesses Fortune"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_reev",
    "game_name": "Resident Evil"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_reki",
    "game_name": "Respin King"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_rm",
    "game_name": "Respin Mania"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_remamere",
    "game_name": "Respin Mania Mega Reels"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_rm_ab_jp",
    "game_name": "Respin Mania Wu Shi Jackpot"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_rsyg",
    "game_name": "Ri Sheng Yue Geng"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_rs",
    "game_name": "Rising Samurai"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_rr",
    "game_name": "Riverboat Reel"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_roofsh",
    "game_name": "Robin Hood Mega Stacks"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_rc",
    "game_name": "Rocket Candies"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_roriyang",
    "game_name": "Royal Rings"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_rcr",
    "game_name": "Run Chicken Run"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_sf",
    "game_name": "San Fu"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_sc",
    "game_name": "Savannah Cash"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_sog",
    "game_name": "Sea of Gold"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_slws",
    "game_name": "Shao Lin Wu Seng"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_slbs",
    "game_name": "Shen Long Bao Shi"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_slbs_jp",
    "game_name": "Shen Long Bao Shi Jackpot"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_fd",
    "game_name": "Shen Qi Jiu Long"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_scyd",
    "game_name": "Sheng Cai You Dao"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_sx",
    "game_name": "Shuang Xi"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_sgcf",
    "game_name": "Shui Guo Cai Fu"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_sgcf_ab_jp",
    "game_name": "Shui Guo Cai Fu Jackpot"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_sdjg",
    "game_name": "Si Da Jin Gang"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_sl",
    "game_name": "Si Ling"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_silijaab_jp",
    "game_name": "Si Ling Jackpot"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_fb",
    "game_name": "Si Mei"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_simejaab_jp",
    "game_name": "Si Mei Jackpot"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_sixng",
    "game_name": "Si Xiang"
  },
  {
    "game_type": "Table Game",
    "game_code": "sw_scca2d",
    "game_name": "SnatchXCash"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_sq",
    "game_name": "Snowfall Queen"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_sctz",
    "game_name": "Song Cai Tong Zi"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_sland",
    "game_name": "Sugar Land"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_suli",
    "game_name": "Super Lion"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_sld",
    "game_name": "Super Lucky Dollar"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_ss",
    "game_name": "Sweet Strike"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_sod",
    "game_name": "Symphony of Diamonds"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_t2d",
    "game_name": "Tale of Two Dragons"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_taoftwdrjaed",
    "game_name": "Tale of Two Dragons Jackpot Edition"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_tewm",
    "game_name": "The Edge - Wild Meteors"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_tlotws",
    "game_name": "The Legend of the White Snake"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_totiatp",
    "game_name": "The Orca, the Iceberg and the Penguin"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_rmac",
    "game_name": "The Reel Macau"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_sfy",
    "game_name": "The Seventh Fairy"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_ts",
    "game_name": "Three Sisters"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_tc",
    "game_name": "Tiger Cash"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_tiki_luck",
    "game_name": "Tiki Luck"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_tch",
    "game_name": "Top Chase"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_dday",
    "game_name": "Top Cup Day"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_tr",
    "game_name": "T-Rex Cash"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_tm",
    "game_name": "Triple Monkey"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_twfr",
    "game_name": "Twin Fruits"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_vos",
    "game_name": "Valley of Spirits"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_vfv",
    "game_name": "Viva Fruit Vegas"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_vi",
    "game_name": "Volcano Island"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_wg",
    "game_name": "Warriors Gold"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_wrl",
    "game_name": "Water Reel"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_ws",
    "game_name": "West Shot"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_wi0",
    "game_name": "Wicked 777"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_wcup",
    "game_name": "Wild Cup"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_wf",
    "game_name": "Wild Five"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_wfot",
    "game_name": "Wild Flips on Top"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_wq",
    "game_name": "Wild Qilin"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_wr",
    "game_name": "Wild Racers"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_wws",
    "game_name": "Wild Wu Shi"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_wfl",
    "game_name": "Wu Fu Long"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_wfww",
    "game_name": "Wu Fu Wa Wa"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_xwk",
    "game_name": "Xiao Wu Kong"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_xybl",
    "game_name": "Xing Yun Bian Lian"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_xyjc",
    "game_name": "Xing Yun Jin Chan"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_xw",
    "game_name": "Xuan Wu"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_ycs",
    "game_name": "Ying Cai Shen"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_yxlb",
    "game_name": "Ying Xiong Lu Bu"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_yyy",
    "game_name": "Yu Yu Yu"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_ylns",
    "game_name": "Yue Liang Nü Shen"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_ylxn",
    "game_name": "Yue Liang Xian Nü"
  },
  {
    "game_type": "Slots & Cascade",
    "game_code": "sw_zhhu",
    "game_name": "Zhan Hun"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_shctz",
    "game_name": "Zhao Cai Tong Zi"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_shctz_ab_jp",
    "game_name": "Zhao Cai Tong Zi Jackpot"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_zcxm",
    "game_name": "Zhao Cai Xiong Mao"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_5_times",
    "game_name": "5 Times Pay"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_20_times",
    "game_name": "20 Times Pay"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_50_times",
    "game_name": "50 Times Pay"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_bbdf",
    "game_name": "Ba Ba Da Fa"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_bonus_cash",
    "game_name": "Bonus Cash "
  },
  {
    "game_type": "Slots",
    "game_code": "sw_captain_riches",
    "game_name": "Captain Riches"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_fbcs",
    "game_name": "Fan Bei Cai Shen"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_srt_f7",
    "game_name": "Fortune 777"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_goddess_of_flame",
    "game_name": "Goddess of Flame"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_lucky_monkey",
    "game_name": "Lucky Monkey"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_multi_diamond",
    "game_name": "Multi Diamond"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_omq",
    "game_name": "Old Master Q Classic"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_sskt",
    "game_name": "San Shi Kai Tai"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_star_7",
    "game_name": "Star 7"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_tjhf",
    "game_name": "Tian Jiang Hong Fu"
  },
  {
    "game_type": "Slots",
    "game_code": "sw_xyzp",
    "game_name": "Xing Yun Zhuan Pan"
  }
]';


$someArray = json_decode($array, JSON_FORCE_OBJECT);
$foo = utf8_encode($array);
$data = json_decode($foo, true);

$data2 = array();
foreach($data as $g){
    if($g['game_type'] == "Slots"){
      $game_type = 1;
    }else if($g['game_type'] == "Table Game"){
      $game_type = 5;
    }else if($g['game_type'] == "Shooting Game"){
      $game_type = 14;
    }else if($g['game_type'] == "Arcade"){
      $game_type = 23;
    }else if($g['game_type'] == "Slots & Cascade"){
      $game_type = 31;
    }else if($g['game_type'] == "Cascade"){
      $game_type = 14;
    }

    $game = array(
            "game_type_id"=> $game_type,
            "provider_id"=> 28,
            "sub_provider_id"=> 52,
            "game_name"=> $g['game_name'],
            "game_code"=>$g["game_code"],
            "icon"=> 'https://www.gamblerspost.com/wp-content/uploads/2020/03/2-400x250-1.jpg'
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
