<?php

namespace App\Http\Controllers\GameLobby;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Game;
use App\Models\GameType;
use App\Models\GameProvider;
use App\Models\GameSubProvider;
use App\Helpers\Helper;
use App\Helpers\GameLobby;
use App\Models\ClientGameSubscribe;
use Stripe\Balance;
use DB;
use GameLobby as GlobalGameLobby;

class GameLobbyController extends Controller
{

    public $image_url = 'https://bo-test.betrnk.games/';
    //
    // public function __construct(){
	// 	$this->middleware('oauth', ['except' => ['index']]);
	// 	/*$this->middleware('authorize:' . __CLASS__, ['except' => ['index', 'store']]);*/
	// }
    public function getGameList(Request $request){
        if($request->has("client_id")){
            
            $excludedlist = ClientGameSubscribe::with("selectedProvider")->with("gameExclude")->with("subProviderExcluded")->where("client_id",$request->client_id)->get();
            if(count($excludedlist)>0){
                $gamesexcludeId=array();
                foreach($excludedlist[0]->gameExclude as $excluded){
                    array_push($gamesexcludeId,$excluded->game_id);
                }
                $subproviderexcludeId=array();
                foreach($excludedlist[0]->subProviderExcluded as $excluded){
                    array_push($subproviderexcludeId,$excluded->sub_provider_id);
                }
                if($request->has("type")){
                    $type = GameType::with("game.provider")->get();
                    return $type;
                }
                else{
                    $data = array();
                    $sub_providers = GameSubProvider::with(["games.game_type","games"=>function($q)use($gamesexcludeId){
                        $q->whereNotIn("game_id",$gamesexcludeId)->where("on_maintenance",0);
                    }])->whereNotIn("sub_provider_id",$subproviderexcludeId)->where("on_maintenance",0)->get(["sub_provider_id","sub_provider_name", "icon"]);
                    foreach($sub_providers as $sub_provider){
                        $subproviderdata = array(
                            "provider_id" => "sp".$sub_provider->sub_provider_id,
                            "provider_name" => $sub_provider->sub_provider_name,
                            "icon" => $sub_provider->icon,
                            "games_list" => array(),
                        );
                        foreach($sub_provider->games as $game){
                            if($game->game_type){
                                $game = array(
                                    "game_id" => $game->game_id,
                                    "game_name"=>$game->game_name,
                                    "game_code"=>$game->game_code,
                                    "game_provider"=>$sub_provider->sub_provider_name,
                                    "game_type" => $game->game_type->game_type_name,
                                    "game_icon" => $game->icon,
                                );
                                array_push($subproviderdata["games_list"],$game);
                            }
                        }
                        array_push($data,$subproviderdata);
                    }
                    return $data;
                }
            }
            else{
                $msg = array(
                    "message" => "Client Id Doesnt Exist / Client doesnt have Subcription Yet!",
                );
                return response($msg,401)
                ->header('Content-Type', 'application/json');
            }
        }
        
    }
    public function gameLaunchUrl(Request $request){
        if($request->has('client_id')
        &&$request->has('client_player_id')
        &&$request->has('username')
        &&$request->has('email')
        &&$request->has('display_name')
        &&$request->has('game_code')
        &&$request->has('exitUrl')
        &&$request->has('game_provider')
        &&$request->has('token')){
            if($request->has('ip_address')){
                $ip_address = $request->ip_address;
            }
            else{
                $ip_address = "127.0.0.1";
            }
            // CLIENT SUBSCRIPTION FILTER
            // $subscription_checker = $this->checkGameAccess($request->input("client_id"), $request->input("game_code"));
            // if(!$subscription_checker){
            //     $msg = array(
            //         "game_code" => $request->input("game_code"),
            //         "game_launch" => false
            //     );
            //     return $msg;
            // }
            //
           
           $solid_gamings = ['Solid Gaming', 'Booongo', 'Concept', 'Espresso', 'EvoPlay', 'GameArt', 'Habanero', 'MultiSlot', 'NetEnt', 'Omi Gaming', 'Push Gaming', 'Revolver Gaming', 'RTG Asia', 'TPG', '1X2 Network', 'BetSoft', 'Booming', 'Leander', 'Lotus Gaming', 'No Limit City', 'One Touch', 'Quick Fire', 'Relax', 'Wazdan', 'Yggdrasil', 'Evolution Gaming'];

            $lang = $request->has("lang")?$request->input("lang"):"en";
            if($token=Helper::checkPlayerExist($request->client_id,$request->client_player_id,$request->username,$request->email,$request->display_name,$request->token,$ip_address)){
                if($request->input('game_provider')=="Iconic Gaming"){
                    $msg = array(
                        "game_code" => $request->input("game_code"),
                        "url" => GameLobby::icgLaunchUrl($request->game_code,$token,$request->exitUrl,$lang),
                        "game_launch" => true
                    );
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                }
                elseif($request->input('game_provider')=="Booongo" || $request->input('game_provider')=="Playson"){
                    $msg = array(
                        "game_code" => $request->input("game_code"),
                        "url" => GameLobby::booongoLaunchUrl($request->game_code,$token,$request->exitUrl),
                        "game_launch" => true
                    );
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                }
                elseif($request->input('game_provider')=="Endorphina Gaming"){
                    $msg = array(
                        "game_code" => $request->input("game_code"),
                        "url" => GameLobby::edpLaunchUrl($request->game_code,$token,$request->exitUrl),
                        "game_launch" => true
                    );
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                }
                elseif($request->input('game_provider')=="Fa Chai"){
                    $msg = array(
                        "game_code" => $request->input("game_code"),
                        "url" => GameLobby::fcLaunchUrl($request->game_code,$token,$request->exitUrl),
                        "game_launch" => true
                    );
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                }
                 elseif($request->input('game_provider')=="Bole Gaming"){
                    $country_code =  $request->has('country_code') ? $request->country_code : 'PH';
                    $url = GameLobby::boleLaunchUrl($request->game_code,$token,$request->exitUrl,$country_code);
                    if($url){
                        $msg = array(
                            "game_code" => $request->input("game_code"),
                            "url" => $url,
                            "game_launch" => true
                        );
                    }else{
                        $msg = array(
                            "game_code" => $request->input("game_code"),
                            "game_launch" => false
                        );
                    }
                    return $msg;
                }
                elseif($request->input('game_provider')=="Digitain"){ // request->token
                    Helper::saveLog('DEMO CALL', 11, json_encode($request->all()), 'DEMO');
                    $lang = GameLobby::getLanguage($request->game_provider,$request->lang);
                    $msg = array(
                        "game_code" => $request->input("game_code"),
                        "url" => GameLobby::rsgLaunchUrl($request->game_code,$request->token,$request->exitUrl,$lang), //TEST
                        "game_launch" => true
                    );
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                }
                elseif($request->input('game_provider')=="SkyWind"){ // request->token
                    $msg = array(
                        "game_code" => $request->input("game_code"),
                        "url" => GameLobby::skyWindLaunch($request->game_code,$token), //TEST
                        "game_launch" => true
                    );
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                }
                elseif($request->input('game_provider')=="CQGames"){ // request->token
                    $msg = array(
                        "game_code" => $request->input("game_code"),
                        "url" => GameLobby::cq9LaunchUrl($request->game_code,$token), //TEST
                        "game_launch" => true
                    );
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                }
                elseif($request->input('game_provider')=="SA Gaming"){ // request->token
                    $url = GameLobby::saGamingLaunchUrl($request->game_code,$request->token,$request->exitUrl,$lang);
                    if($url){
                        $msg = array(
                            "game_code" => $request->input("game_code"),
                            "url" => $url,
                            "game_launch" => true
                        );
                    }else{
                        $msg = array(
                            "game_code" => $request->input("game_code"),
                            "game_launch" => false
                        );
                    }
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                }
                elseif($request->input('game_provider')=="IA Gaming"){ // request->token
                    // $lang = GameLobby::getLanguage($request->game_provider,$request->lang);
                    $url = GameLobby::iaLaunchUrl($request->game_code,$request->token,$request->exitUrl);
                    if($url){
                        $msg = array(
                            "game_code" => $request->input("game_code"),
                            "url" => $url,
                            "game_launch" => true
                        );
                    }else{
                        $msg = array(
                            "game_code" => $request->input("game_code"),
                            "game_launch" => false
                        );
                    }
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                }
                elseif($request->input('game_provider')=="Betrnk"){ // TEST LOTTERY
                    // Helper::saveLog('DEMO CALL', 11, json_encode($request->all()), 'DEMO');
                    $msg = array(
                        "game_code" => $request->input("game_code"),
                        "url" => GameLobby::betrnkLaunchUrl($request->token, $request->game_code), //TEST
                        "game_launch" => true
                    );
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                }
                elseif($request->input('game_provider')=="AllWaySpin"){
                    $lang = GameLobby::getLanguage($request->game_provider,$request->lang);
                    $url = GameLobby::awsLaunchUrl($request->token,$request->game_code,$lang);
                    if($url){
                        $msg = array(
                            "game_code" => $request->input("game_code"),
                            "url" => $url,
                            "game_launch" => true
                        );
                    }else{
                        $msg = array(
                            "game_code" => $request->input("game_code"),
                            "game_launch" => false
                        );
                    }
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                }
                elseif($request->input('game_provider')=="EVOPLAY 8Provider"){
                    $url = GameLobby::evoplayLunchUrl($request->token,$request->game_code);
                    if($url){
                        $msg = array(
                            "game_code" => $request->input("game_code"),
                            "url" => $url,
                            "game_launch" => true
                        );
                    }else{
                        $msg = array(
                            "game_code" => $request->input("game_code"),
                            "game_launch" => false
                        );
                    }
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                }
                // elseif($request->input('game_provider')=="Solid Gaming"){
                elseif(in_array($request->input('game_provider'), $solid_gamings)){
                    $msg = array(
                        "game_code" => $request->input("game_code"),
                        "url" => GameLobby::solidLaunchUrl($request->game_code,$request->token,$request->exitUrl), 
                        "game_launch" => true
                    );
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                }
                elseif($request->input('game_provider')=="Manna Play"){
                    $msg = array(
                        "game_code" => $request->input("game_code"),
                        "url" => GameLobby::mannaLaunchUrl($request->game_code,$request->token,$request->exitUrl), 
                        "game_launch" => true
                    );
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                }
                elseif($request->input('game_provider')=="Aoyama Slots"){
                    $msg = array(
                        "game_code" => $request->input("game_code"),
                        "url" => GameLobby::aoyamaLaunchUrl($request->game_code,$request->token,$request->exitUrl), 
                        "game_launch" => true
                    );
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                }
                elseif($request->input('game_provider')=="Oryx Gaming"){
                    $msg = array(
                        "game_code" => $request->input("game_code"),
                        "url" => GameLobby::oryxLaunchUrl($request->game_code,$request->token,$request->exitUrl), 
                        "game_launch" => true
                    );
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                } 

                elseif($request->input('game_provider')=="Pragmatic Play"){

                    $url = GameLobby::pragmaticplaylauncher($request->game_code,$request->token,$request->exitUrl);
                    if($url){
                        $msg = array(
                            "game_code" => $request->input("game_code"),
                            "url" => $url,
                            "game_launch" => true
                        );
                    }else{
                        $msg = array(
                            "game_code" => $request->input("game_code"),
                            "game_launch" => false
                        );
                    }
                    return $msg;

                    // $msg = array(
                    //     "game_code" => $request->input("game_code"),
                    //     "url" => GameLobby::pragmaticplaylauncher($request->game_code,$request->token,$request->exitUrl), 
                    //     "game_launch" => true
                    // );
                    // return response($msg,200)
                    // ->header('Content-Type', 'application/json');
                } 
                elseif($request->input('game_provider')=="Tidy"){ 

                  
                    // Helper::saveLog('DEMO CALL', 11, json_encode($request->all()), 'DEMO');
                    // $lang = GameLobby::getLanguage($request->game_provider,$request->lang);
                    $msg = array(
                        "game_code" => $request->input("game_code"),
                        "url" => GameLobby::tidylaunchUrl($request->game_code,$request->token), //TEST
                        "game_launch" => true
                    );
                    
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                    
                }
                elseif($request->input('game_provider') == "Top Grade Games"){ 
                    // Helper::saveLog('DEMO CALL', 11, json_encode($request->all()), 'DEMO');
                    // $lang = GameLobby::getLanguage($request->game_provider,$request->lang);
                   
                    $url = GameLobby::tgglaunchUrl($request->game_code,$request->token);
                    $msg = array(
                        "game_code" => $request->input("game_code"),
                        "url" => $url,
                        "game_launch" => true
                    );
                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                }elseif($request->input('game_provider')=="HabaneroGaming"){ 
                    // Helper::saveLog('DEMO CALL', 11, json_encode($request->all()), 'DEMO');
                    // $lang = GameLobby::getLanguage($request->game_provider,$request->lang);
                    $msg = array(
                        "game_code" => $request->input("game_code"),
                        "url" => GameLobby::habanerolaunchUrl($request->game_code,$request->token), //TEST
                        "game_launch" => true
                    );

                    return response($msg,200)
                    ->header('Content-Type', 'application/json');
                }

            }
        }
        else{
            $msg = array(
                "error_code" => "INVALID_INPUT",
                "message" => "Missing Required Input"
            );
            return response($msg,200)
            ->header('Content-Type', 'application/json');
        }
    }
    public function getPlayerBalance(Request $request){
        if($request->has("token")){
            $player = GameLobby::getClientDetails("token",$request->token);
            $balance = DB::table("player_balance")->where("token_id",$player->token_id)->first();
            $gametransaction = DB::table("game_transactions")->select(DB::raw('SUM(bet_amount) as bet'),DB::raw('SUM(pay_amount) as win'))->where("token_id",$player->token_id)->first();
            $newbalance = (float)$balance->balance + (float)$gametransaction->win - (float)$gametransaction->bet;
            return $newbalance;
        }
        else{
            $msg = array(
                "error_code" => "TOKEN_INVALID",
                "message" => "Missing Required Input"
            );
            return response($msg,200)
            ->header('Content-Type', 'application/json');
        }
    }
    public function gameLobbyLaunchUrl(Request $request){
        $url = "https://daddy.betrnk.games/authenticate";
        if($request->has('client_id')
        &&$request->has('client_player_id')
        &&$request->has('username')
        &&$request->has('email')
        &&$request->has('display_name')
        &&$request->has('exitUrl')
        &&$request->has('token')){
           if($token=Helper::checkPlayerExist($request->client_id,$request->client_player_id,$request->username,$request->email,$request->display_name,$request->token)){
                $data = array(
                    "url" => $url."?token=".$token."&client_id=".$request->client_id."&user_id=".$request->client_player_id."&email=".$request->email."&displayname=".$request->display_name."&username=".$request->username."&exiturl=".$request->exitUrl,
                    "launch" => true
                );
                return $data;
            }
        }
        return "Invalid Input";
    }
    // TEST SINGLE PROVIDER
    public function getProviderDetails(Request $request, $provider_name){
        $clean_url = urldecode($provider_name);
        $providers = GameProvider::where("provider_name", $clean_url)
                    ->get(["provider_id","provider_name", "icon"]);
 
            $data = array();
            foreach($providers as $provider){
                $providerdata = array(
                    "provider_id" => $provider->provider_id,
                    "provider_name" => $provider->provider_name,
                    "icon" => $this->image_url.$provider->icon,
                    "games_list" => array(),
                );
                foreach($provider->games as $game){
                    $game = array(
                        "game_id" => $game->game_id,
                        "game_name"=>$game->game_name,
                        "game_code"=>$game->game_code,
                        "game_type" => $game->game_type->game_type_name,
                        "game_provider"=> $game->provider->provider_name,
                        "game_icon" => $game->icon,
                    );
                    array_push($providerdata["games_list"],$game);
                }
                array_push($data,$providerdata);
            }
            return $data;
    }

    public function checkGameAccess($client_id, $game_code){

         $excludedlist = ClientGameSubscribe::with("selectedProvider")->with("gameExclude")->with("subProviderExcluded")->where("client_id",$client_id)->get();
         if($excludedlist){
                $providerexcludeId=array();
                foreach($excludedlist[0]->selectedProvider as $providerexcluded){
                    array_push($providerexcludeId,$providerexcluded->provider_id);
                }
                $gamesexcludeId=array();
                foreach($excludedlist[0]->gameExclude as $excluded){
                    array_push($gamesexcludeId,$excluded->game_id);
                }
                $subproviderexcludeId=array();
                foreach($excludedlist[0]->subProviderExcluded as $excluded){
                    array_push($subproviderexcludeId,$excluded->sub_provider_id);
                }
                $providers = GameProvider::with(["games.game_type","games"=>function($q)use($gamesexcludeId){
                        $q->whereNotIn("game_id",$gamesexcludeId);
                }])->whereNotIn("provider_id",$providerexcludeId)->get(["provider_id"]);
                $data = array();
                foreach($providers as $provider){
                    foreach($provider->games as $game){
                        // if($game->sub_provider_id == 0){   // COMMENTED RiAN
                            $game = $game->game_code;
                            array_push($data,$game);
                        // }
                    }
                }
              return  in_array($game_code, $data) ? 1 : 0;

        }else{
            return false;
        }
    }

    public function getLanguage(Request $request){
        return GameLobby::getLanguage($request->provider_name,$request->language);
    }

}
