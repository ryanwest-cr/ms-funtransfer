<?php
namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use App\Helpers\Helper;
use App\Helpers\IAHelper;
use App\Helpers\AWSHelper;
use App\Helpers\SAHelper;
use App\Helpers\TidyHelper;
use App\Helpers\FCHelper;
use App\Helpers\ProviderHelper;
use App\Helpers\MGHelper;
use App\Helpers\EVGHelper;


use DB;             
use Carbon\Carbon;
class GameLobby{
    public static function icgLaunchUrl($game_code,$token,$exitUrl,$provider,$lang="en"){
        $client = GameLobby::getClientDetails("token",$token);
        
        $game_list =GameLobby::icgGameUrl($client->default_currency);
        Helper::saveLog('GAMELAUNCH ICG', 11, json_encode($game_code), json_encode($game_list));
        foreach($game_list["data"] as $game){
            if($game["productId"] == $game_code){
                $lang = GameLobby::getLanguage("Iconic Gaming",$lang);
                Helper::savePLayerGameRound($game["productId"],$token,$provider);
                Helper::saveLog('GAMELAUNCH ICG', 11, json_encode($game_code), json_encode($game["href"]));
                return $game["href"].'&token='.$token.'&lang='.$lang.'&home_URL='.$exitUrl;
                
            }
        }
    }
    public static function fcLaunchUrl($game_code,$token,$exitUrl,$provider,$lang="en"){
        $client = GameLobby::getClientDetails("token",$token);
        Helper::savePLayerGameRound($game_code,$token,$provider);
        $data = FCHelper::loginGame($client->player_id,$game_code,1,$exitUrl);
        return $data["Url"];
    }
    public static function booongoLaunchUrl($game_code,$token,$provider,$exitUrl){
        $lang = "en";
        $timestamp = Carbon::now()->timestamp;
        $exit_url = $exitUrl;
        Helper::savePLayerGameRound($game_code,$token,$provider);
        $gameurl =  config("providerlinks.boongo.PLATFORM_SERVER_URL")
                  .config("providerlinks.boongo.PROJECT_NAME").
                  "/game.html?wl=".config("providerlinks.boongo.WL").
                  "&token=".$token."&game=".$game_code."&lang=".$lang."&sound=1&ts=".
                  $timestamp."&quickspin=1&platform=desktop".
                  "&exir_url=".$exit_url;
        return $gameurl;
    }
    public static function wazdanLaunchUrl($game_code,$token,$provider,$exitUrl){
        $lang = "en";
        $timestamp = Carbon::now()->timestamp;
        $exit_url = $exitUrl;
        Helper::savePLayerGameRound($game_code,$token,$provider);
        $gameurl = config('providerlinks.wazdan.gamelaunchurl').config('providerlinks.wazdan.partnercode').'/gamelauncher?operator='.config('providerlinks.wazdan.operator').
                  '&game='.$game_code.'&mode=real&token='.$token.'&license='.config('providerlinks.wazdan.license').'&lang='.$lang.'&platform=desktop';
        return $gameurl;
    }
    public static function pngLaunchUrl($game_code,$token,$provider,$exitUrl,$lang){
        $timestamp = Carbon::now()->timestamp;
        $exit_url = $exitUrl;
        $lang = GameLobby::getLanguage("PlayNGo",$lang);
        Helper::savePLayerGameRound($game_code,$token,$provider);
        $gameurl = config('providerlinks.png.root_url').'/casino/ContainerLauncher?pid='.config('providerlinks.png.pid').'&gid='.$game_code.'&channel='.
                   config('providerlinks.png.channel').'&lang='.$lang.'&practice='.config('providerlinks.png.practice').'&ticket='.$token.'&origin='.$exit_url;
        return $gameurl;
    }
    public static function edpLaunchUrl($game_code,$token,$provider,$exitUrl){
        $profile = "nofullscreen_money.xml";
        $sha1key = sha1($exitUrl.''.config("providerlinks.endorphina.nodeId").''.$profile.''.$token.''.config("providerlinks.endorphina.secretkey"));
        $sign = $sha1key; 
        Helper::savePLayerGameRound($game_code,$token,$provider);
        Helper::saveLog('GAMELAUNCH EDP', 11, json_encode(config("providerlinks.endorphina.url").'?exit='.$exitUrl.'&nodeId='.config("providerlinks.endorphina.nodeId").'&profile='.$profile.'&token='.$token.'&sign='.$sign), json_encode($sign));
        return config("providerlinks.endorphina.url").'?exit='.$exitUrl.'&nodeId='.config("providerlinks.endorphina.nodeId").'&profile='.$profile.'&token='.$token.'&sign='.$sign;
    }
    public static function microgamingLaunchUrl($game_code,$token,$provider,$exitUrl){
        $client_details = ProviderHelper::getClientDetails('token', $token);
        Helper::savePLayerGameRound($game_code,$token,$provider);
        $url = MGHelper::launchGame($token,$client_details->player_id,$game_code);
        return $url;
    }
    public static function evolutionLaunchUrl($game_code,$token,$provider,$exitUrl,$player_ip,$lang){
        $client_details = ProviderHelper::getClientDetails('token', $token);
        $lang = GameLobby::getLanguage("EvolutionGaming",$lang);
        Helper::savePLayerGameRound($game_code,$token,$provider);
        $url = EVGHelper::gameLaunch($token,$player_ip,$game_code,$lang,$exitUrl,config('providerlinks.evolution.env'));
        return $url;
    }
    public static function boleLaunchUrl($game_code,$token,$exitUrl, $country_code='PH'){

        $client_details = ProviderHelper::getClientDetails('token', $token);
        if($client_details != null){
            $AccessKeyId = config('providerlinks.bolegaming.'.$client_details->default_currency.'.AccessKeyId');
            $access_key_secret = config('providerlinks.bolegaming.'.$client_details->default_currency.'.access_key_secret');
            $app_key = config('providerlinks.bolegaming.'.$client_details->default_currency.'.app_key');
            $login_url = config('providerlinks.bolegaming.'.$client_details->default_currency.'.login_url');
            $logout_url = config('providerlinks.bolegaming.'.$client_details->default_currency.'.logout_url');
        }else{
            return false;
        }

        $scene_id = '';
        if(strpos($game_code, 'slot') !== false) {
            if($game_code == 'slot'){
                $scene_id = "";
                $game_code = 'slot'; 
            }else{
                $game_code = explode("_", $game_code);
                $scene_id = $game_code[1];
                $game_code = 'slot'; 
            }
        }else{
            $game_code = $game_code;
        }

        $nonce = rand();
        $timestamp = time();
        $key = $access_key_secret.$nonce.$timestamp;
        $signature = sha1($key);
        $sign = [
            "timestamp" => $timestamp,
            "nonce" => $nonce,
            "signature" => $signature,
        ];
        try {
            $http = new Client();
            $data = [
                'game_code' => $game_code,
                'scene' => $scene_id,
                'player_account' => $client_details->player_id,
                'country'=> $country_code,
                'ip'=> $_SERVER['REMOTE_ADDR'],
                'AccessKeyId'=> $AccessKeyId,
                'Timestamp'=> $sign['timestamp'],
                'Nonce'=> $sign['nonce'],
                'Sign'=> $sign['signature'],
                //'op_pay_url' => 'http://middleware.freebetrnk.com/public/api/bole/wallet',
                'op_race_return_type' => 1, // back to previous game
                'op_return_type' => 3, //hide home button for games test
                //'op_home_url' => 'https://demo.freebetrnk.com/casino', //hide home button for games test
                'ui_hot_list_disable' => 1, //hide latest game menu
                'ui_category_disable' => 1 //hide category list
            ];
            $response = $http->post($login_url, [
                'form_params' => $data,
            ]);
            $client_response = json_decode($response->getBody()->getContents());
            Helper::saveLog('GAMELAUNCH BOLE', 11, json_encode($data), json_decode($response->getBody()));
            return isset($client_response->resp_data->url) ? $client_response->resp_data->url : false;
        } catch (\Exception $e) {
            return false;        
        }
        
    }

    public static function evoplayLunchUrl($token,$game_code){
        $client_player_details = GameLobby::getClientDetails('token', $token);
        $requesttosend = [
          "project" => config('providerlinks.evoplay.project_id'),
          "version" => 1,
          "token" => $token,
          "game" => $game_code, //game_code, game_id
          "settings" =>  [
            'user_id'=> $client_player_details->player_id,
            'language'=> $client_player_details->language ? $client_player_details->language : 'en',
          ],
          "denomination" => '1', // game to be launched with values like 1.0, 1, default
          "currency" => $client_player_details->default_currency,
          "return_url_info" => true, // url link
          "callback_version" => 2, // POST CALLBACK
        ];
        $signature =  ProviderHelper::getSignature($requesttosend, config('providerlinks.evoplay.secretkey'));
        $requesttosend['signature'] = $signature;
        // Helper::saveLog('GAMELAUNCH EVOPLAY', 15, json_encode($requesttosend), json_encode($requesttosend));
        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        ]);
        $response = $client->post(config('providerlinks.evoplay.api_url').'/game/geturl',[
            'form_params' => $requesttosend,
        ]);
        $res = json_decode($response->getBody(),TRUE);
        Helper::saveLog('8Provider GAMELAUNCH EVOPLAY', 15, json_encode($requesttosend), json_decode($response->getBody()));
        return isset($res['data']['link']) ? $res['data']['link'] : false;
    }

     public static function awsLaunchUrl($token,$game_code,$lang='en'){
        $client_details = ProviderHelper::getClientDetails('token', $token);
        $provider_reg_currency = Providerhelper::getProviderCurrency(21, $client_details->default_currency);
        if($provider_reg_currency == 'false'){
            return 'false';
        }
        $player_check = AWSHelper::playerCheck($token);
        if($player_check->code == 100){ // Not Registered!
            $register_player = AWSHelper::playerRegister($token);
            if($register_player->code != 2217 || $register_player->code != 0){
                 Helper::saveLog('AWS Launch Game Failed', 21, json_encode($register_player), $register_player);
                 return 'false';
            }
        }
        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
            ]
        ]);
        $requesttosend = [
            "merchantId" => config('providerlinks.aws.merchant_id'),
            "currency" => $client_details->default_currency,
            "currentTime" => AWSHelper::currentTimeMS(),
            "username" => config('providerlinks.aws.merchant_id').'_TG'.$client_details->player_id,
            "playmode" => 0, // Mode of gameplay, 0: official
            "device" => 1, // Identifying the device. Device, 0: mobile device 1: webpage
            "gameId" => $game_code,
            "language" => $lang,
        ];
        $requesttosend['sign'] = AWSHelper::hashen($requesttosend);
        $guzzle_response = $client->post(config('providerlinks.aws.api_url').'/api/login',
            ['body' => json_encode($requesttosend)]
        );
        $provider_response = json_decode($guzzle_response->getBody()->getContents());
        Helper::saveLog('AWS Launch Game', 21, json_encode($requesttosend), $provider_response);
        return isset($provider_response->data->gameUrl) ? $provider_response->data->gameUrl : 'false';
    }

    public static function betrnkLaunchUrl($token){

        $http = new Client();
        $player_details = GameLobby::playerDetailsCall($token);
        $response = $http->post('http://betrnk-lotto.com/api/v1/index.php', [
            'form_params' => [
                'cmd' => 'auth', // auth request command
                'username' => 'freebetrnk',  // client subscription acc
                'password' => 'w34KM)!##$$#',
                'merchant_user'=> $player_details->playerdetailsresponse->username,
                'merchant_user_balance'=> $player_details->playerdetailsresponse->balance,
            ],
        ]);

        $game_url = json_decode((string) $response->getBody(), true)["response"]["game_url"];
        return $game_url.'&player_token='.$token;
    }

    public static function rsgLaunchUrl($game_code,$token,$exitUrl,$lang='en', $provider_sub_name){
        $url = $exitUrl;
        $domain = parse_url($url, PHP_URL_HOST);
        Helper::savePLayerGameRound($game_code,$token,$provider_sub_name);
        $url = 'https://partnerapirgs.betadigitain.com/GamesLaunch/Launch?gameid='.$game_code.'&playMode=real&token='.$token.'&deviceType=1&lang='.$lang.'&operatorId=B9EC7C0A&mainDomain='.$domain.'';
        return $url;
    }

    

    public static function skyWindLaunch($game_code, $token){
        $player_login = SkyWind::userLogin();

        $client_details = ProviderHelper::getClientDetails('token', $token, 2);

        Helper::saveLog('Skywind Game Launch', config('providerlinks.skywind.provider_db_id'), json_encode($client_details), $client_details);

        $client = new Client([
              'headers' => [ 
                  'Content-Type' => 'application/json',
                  'X-ACCESS-TOKEN' => $player_login->accessToken,
              ]
        ]);
        // $url = ''.config('providerlinks.skywind.api_url').'/fun/games/'.$game_code.'';
         // $url = ''.config('providerlinks.skywind.api_url').'/players/'.config('providerlinks.skywind.seamless_username').'/games/'.$game_code.'?playmode=real&ticket='.$token.'';

        // TG8_98
        // $url = ''.config('providerlinks.skywind.api_url').'/players/TG'.$client_details->client_id.'_'.$client_details->player_id.'/games/'.$game_code.'?playmode=real&ticket='.$token.'';
        
        $url = 'https://api.gcpstg.m27613.com/v1/players/TG'.$client_details->client_id.'_'.$client_details->player_id.'/games/'.$game_code.'?playmode=real&ticket='.$token.'';
        try {

            $response = $client->get($url);
            $response = json_encode(json_decode($response->getBody()->getContents()));
            Helper::saveLog('Skywind Game Launch = '.$url, config('providerlinks.skywind.provider_db_id'), $response, $url);
            $url = json_decode($response, true);
            return isset($url['url']) ? $url['url'] : 'false';
            
        } catch (\Exception $e) {
            Helper::saveLog('Skywind Game Launch Failed = '.$url, config('providerlinks.skywind.provider_db_id'), json_encode($player_login), $e->getMessage());
            return 'false';
        }
    }

    public static function cq9LaunchUrl($game_code, $token, $provider_sub_name){
        Helper::savePLayerGameRound($game_code,$token,$provider_sub_name);
        $client_details = ProviderHelper::getClientDetails('token', $token);
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
        $api_tokens = config('providerlinks.cqgames.api_tokens');
        if(array_key_exists($client_details->default_currency, $api_tokens)){
            $auth = $api_tokens[$client_details->default_currency];
            // $auth = $api_tokens['USD'];
        }else{
            return 'false';
        }
        $client = new Client([
            'headers' => [ 
                'Authorization' => $auth,
                // 'Authorization' => config('providerlinks.cqgames.api_token'),
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        ]);
        $requesttosend = [
            'account'=> 'TG'.$client_details->client_id.'_'.$client_details->player_id,
            'gamehall'=> 'cq9',
            'gamecode'=> $game_code,
            'gameplat'=> 'WEB',
            'lang'=> 'en',
        ];
        $response = $client->post(config('providerlinks.cqgames.api_url').'/gameboy/player/sw/gamelink', [
            'form_params' => $requesttosend,
        ]);
        $game_launch = json_decode((string)$response->getBody(), true);
        Helper::saveLog('CQ9 Game Launch', config('providerlinks.cqgames.pdbid'), json_encode($game_launch), $requesttosend);
        foreach ($game_launch as $key => $value) {
            $url = isset($value['url']) ? $value['url'] : 'false';
            return $url;
        }
    }
    
    public static function kaGamingLaunchUrl($game_code,$token,$exitUrl,$lang='en'){
        $url = $exitUrl;
        // $domain = parse_url($url, PHP_URL_HOST);
        $client_details = Providerhelper::getClientDetails('token', $token);
        $url = ''.config('providerlinks.kagaming.gamelaunch').'/?g='.$game_code.'&p='.config('providerlinks.kagaming.partner_name').'&u='.$client_details->player_id.'&t='.$token.'&cr='.$client_details->default_currency.'&loc='.$lang.'&t='.$token.'&l='.$url.'&da='.$client_details->username.'&tl=TIGERGAMES'.'&ak='.config('providerlinks.kagaming.access_key').'';
        return $url;
    }

    public static function saGamingLaunchUrl($game_code,$token,$exitUrl,$lang='en'){
        $url = $exitUrl;
        $lang = SAHelper::lang($lang);
        $domain = parse_url($url, PHP_URL_HOST);
        $client_details = Providerhelper::getClientDetails('token', $token);
        if(!empty($client_details)){
            $check_user = SAHelper::userManagement(config('providerlinks.sagaming.prefix').$client_details->player_id, 'VerifyUsername');
            if(isset($check_user->IsExist) && $check_user->IsExist == true){
                $login_token = SAHelper::userManagement(config('providerlinks.sagaming.prefix').$client_details->player_id, 'LoginRequest');
            }else{
                $check_user = SAHelper::userManagement(config('providerlinks.sagaming.prefix').$client_details->player_id, 'RegUserInfo');
                $login_token = SAHelper::userManagement(config('providerlinks.sagaming.prefix').$client_details->player_id, 'LoginRequest');
            }

            if(isset($login_token->Token)){
                $url = 'https://www.sai.slgaming.net/app.aspx?username='.config('providerlinks.sagaming.prefix').$client_details->player_id.'&token='.$login_token->Token.'&lobby='.config('providerlinks.sagaming.lobby').'&lang='.$lang.'&returnurl='.$url.'';
                return $url;
            }else{
                return false;
            }
           
        }else{
            return false;
        }
       
    }

     public static function tidylaunchUrl( $game_code = null, $token = null){
        $url = config('providerlinks.tidygaming.url_lunch');
        $client_details = Providerhelper::getClientDetails('token', $token);
        $get_code_currency = TidyHelper::currencyCode($client_details->default_currency);
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
        $requesttosend = [
            'client_id' =>  config('providerlinks.tidygaming.client_id'),
            'game_id' => $game_code,
            'username' => $client_details->username,
            'token' => $token,
            'uid' => 'TG_'.$client_details->player_id,
            'currency' => $get_code_currency
        ];
        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.TidyHelper::generateToken($requesttosend)
            ]
        ]);
        $guzzle_response = $client->post($url,['body' => json_encode($requesttosend)]
        );
        $client_response = json_decode($guzzle_response->getBody()->getContents());
        return $client_response->link;
    }

    public static function tgglaunchUrl( $game_code = null, $token = null){
        $client_player_details = Providerhelper::getClientDetails('token', $token);
        $requesttosend = [
          "project" => config('providerlinks.tgg.project_id'),
          "version" => 1,
          "token" => $token,
          "game" => $game_code, //game_code, game_id
          "settings" =>  [
            'user_id'=> $client_player_details->player_id,
            'language'=> $client_player_details->language ? $client_player_details->language : 'en',
            'https' => 1,
            'platform' => 'mobile'
          ],
          "denomination" => '1', // game to be launched with values like 1.0, 1, default
          "currency" => $client_player_details->default_currency,
          "return_url_info" => 1, // url link
          "callback_version" => 2, // POST CALLBACK
        ];
        $signature =  ProviderHelper::getSignature($requesttosend, config('providerlinks.tgg.api_key'));
        $requesttosend['signature'] = $signature;
        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        ]);
        $response = $client->post(config('providerlinks.tgg.api_url').'/game/geturl',[
            'form_params' => $requesttosend,
        ]);
        $res = json_decode($response->getBody(),TRUE);
        Helper::saveLog('TGG GAMELAUNCH TOPGRADEGAMES', 29, json_encode($requesttosend), json_decode($response->getBody()));
        return isset($res['data']['link']) ? $res['data']['link'] : false;
        
    }

    public static function pgsoftlaunchUrl( $game_code = null, $token = null){
        $operator_token = config('providerlinks.pgsoft.operator_token');
        $url = "https://m.pg-redirect.net/".$game_code."/index.html?language=en-us&bet_type=1&operator_token=".urlencode($operator_token)."&operator_player_session=".urlencode($token);
        return $url;
    }

    public static function boomingGamingUrl($data){
        Helper::saveLog('Booming session ', config('providerlinks.booming.provider_db_id'), json_encode($data), "ENDPOINT HIT");
        $url = config('providerlinks.booming.api_url').'/v2/session';
        $client_details = ProviderHelper::getClientDetails('token',$data["token"]);
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
        $get_previous = ProviderHelper::getNonceprevious(config('providerlinks.booming.provider_db_id'));
        try{
         
            $nonce = date('YmdHis');
            
            if(!($get_previous == "false")){
                $i = 0;
                do{
                    $nonce = date('YmdHis', strtotime('+'.$i.' hours'));
                    $i++;
                }while($get_previous->response_data > $nonce);
            }   

            $requesttosend = array (
                'game_id' => $data["game_code"],
                'balance' => $player_details->playerdetailsresponse->balance,
                'locale' => 'en',
                'variant' => 'mobile', // mobile, desktop
                'currency' => $client_details->default_currency,
                'player_id' => (string)$client_details->player_id,
                'callback' =>  config('providerlinks.booming.call_back'),
                'rollback_callback' =>  config('providerlinks.booming.roll_back')
            );

            $sha256 =  hash('sha256', json_encode($requesttosend, JSON_FORCE_OBJECT));
            $concat = '/v2/session'.$nonce.$sha256;
            $secrete = hash_hmac('sha512', $concat, config('providerlinks.booming.api_secret'));
            $client = new Client([
                'headers' => [ 
                    'Content-Type' => 'application/vnd.api+json',
                    'X-Bg-Api-Key' => config('providerlinks.booming.api_key'),
                    'X-Bg-Nonce'=> $nonce,
                    'X-Bg-Signature' => $secrete
                ]
            ]);
            $guzzle_response = $client->post($url,  ['body' => json_encode($requesttosend)]);
            $client_response = json_decode($guzzle_response->getBody()->getContents());
            Helper::saveLogCode('Booming nonce', config('providerlinks.booming.provider_db_id'), $nonce, $nonce);
            Helper::saveLog('Booming session process', config('providerlinks.booming.provider_db_id'), json_encode($data), $client_response);
            return $client_response;
        }catch(\Exception $e){
            $error = [
                'error' => $e->getMessage()
            ];
            Helper::saveLog('Booming session error', config('providerlinks.booming.provider_db_id'), json_encode($data), $e->getMessage());
            return $error;
        }

    }

    public static function spadeLaunch($game_code,$token,$exitUrl,$lang='en_US'){
        $client_details = ProviderHelper::getClientDetails('token', $token);
        $domain =  $exitUrl;
        $url = 'https://lobby-egame-staging.sgplay.net/TIGERG/auth/?acctId=TIGERG_'.$client_details->player_id.'&language='.$lang.'&token='.$token.'&game='.$game_code.'';
        return $url;
    }
    
    public static function majagamesLaunch($game_code,$token){
        try{
            if($game_code == 'tapbet'){
                //arcade game
                $client_details = ProviderHelper::getClientDetails('token',$token);
                $requesttosend = [
                    'player_unique_token' => $token.'_'.$client_details->player_id,
                    'player_name' => $client_details->username,
                    'currency' => $client_details->default_currency,
                    'is_demo' => false,
                    'language' =>  "en"
                ];
                $client = new Client([
                    'headers' => [ 
                        'Authorization' => config('providerlinks.majagames.auth')
                    ]
                ]);
                $guzzle_response = $client->post(config('providerlinks.majagames.tapbet_api_url').'/launch-game',  ['form_params' => $requesttosend]);
                $client_response = json_decode($guzzle_response->getBody()->getContents());
                return $client_response->data->game_url;
            }else { 
                // this is for slot game
                $client_details = ProviderHelper::getClientDetails('token',$token);
                $requesttosend = [
                    'player_unique_id' => config('providerlinks.majagames.prefix').$client_details->player_id,
                    'player_name' => $client_details->username,
                    'player_currency' => $client_details->default_currency,
                    'game_id' => $game_code,
                    'is_demo' => false,
                    'agent_code' =>  config('providerlinks.majagames.prefix').$client_details->player_id,
                    'agent_name' =>  $client_details->username
                ];
                $client = new Client([
                    'headers' => [ 
                        'Authorization' => config('providerlinks.majagames.auth')
                    ]
                ]);
                $guzzle_response = $client->post(config('providerlinks.majagames.api_url').'/launch-game',  ['form_params' => $requesttosend]);
                $client_response = json_decode($guzzle_response->getBody()->getContents());
                return $client_response->data->game_url;
            }
        }catch(\Exception $e){
            $error_code = [
                'error_code' => 500,
                'error_msg' => $e->getMessage()
            ];
            Helper::saveLog('MajaGames gamelaunch error', config('providerlinks.majagames.provider_id'), json_encode($error_code), $e->getMessage());
        }
        
    }

    public static function spadeCuracaoLaunch($game_code,$token){
        $client_details = ProviderHelper::getClientDetails('token', $token);
        $url = config('providerlinks.spade_curacao.lobby_url').'acctId=TIGERG_'.$client_details->player_id.'&language=en_US&token='.$token.'&game='.$game_code.'';
        return $url;
    }
    public static function habanerolaunchUrl( $game_code = null, $token = null){
        // $brandID = "2416208c-f3cb-ea11-8b03-281878589203";
        // $apiKey = "3C3C5A48-4FE0-4E27-A727-07DE6610AAC8";
        $brandID = config('providerlinks.habanero.brandID');
        $apiKey = config('providerlinks.habanero.apiKey');
        $api_url = config('providerlinks.habanero.api_url');

        $client_details = Providerhelper::getClientDetails('token', $token);
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);

        // $url = "https://app-test.insvr.com/go.ashx?brandid=$brandID&keyname=$game_code&token=$token&mode=real&locale=en&mobile=0";
        $url = $api_url."brandid=$brandID&keyname=$game_code&token=$token&mode=real&locale=en&mobile=0";
        Helper::saveLog('HBN gamelaunch', 24, json_encode($url), "");
        return $url;
    }
    
    public static function pragmaticplaylauncher($game_code = null, $token = null)
    {
        $stylename = config('providerlinks.tpp.secureLogin');
        $key = config('providerlinks.tpp.secret_key');
        $gameluanch_url = config('providerlinks.tpp.gamelaunch_url');

        $client_details = Providerhelper::getClientDetails('token', $token);
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
        
        $userid = "TGaming_".$client_details->player_id;
        $currency = $client_details->default_currency;
        $hashCreatePlayer = md5('currency='.$currency.'&externalPlayerId='.$userid.'&secureLogin='.$stylename.$key);
        

        // $createPlayer = "https://api.prerelease-env.biz/IntegrationService/v3/http/CasinoGameAPI/player/account/create/?secureLogin=$stylename&externalPlayerId=$userid&currency=$currency&hash=$hashCreatePlayer";
        // $createP = file_get_contents($createPlayer);
        // $createP = json_encode($createP);
        // $createP = json_decode(json_decode($createP));

        

        // $hashCurrentBalance =  md5("externalPlayerId=".$userid."&secureLogin=".$stylename.$key);
        // $currentBalance = "https://api.prerelease-env.biz/IntegrationService/v3/http/CasinoGameAPI/balance/current/?externalPlayerId=$userid&secureLogin=$stylename&hash=$hashCurrentBalance";

        $paramEncoded = urlencode("token=".$token."&symbol=".$game_code."&technology=H5&platform=WEB&language=en&lobbyUrl=daddy.betrnk.games");
        $url = "$gameluanch_url?key=$paramEncoded&stylename=$stylename";
        // $result = file_get_contents($url);
        $result = json_encode($url);
        
        // $result = json_decode(json_decode($result));
        Helper::saveLog('start game url PP', 49, $result,"$result");
        return $url;

        // return isset($result->gameURL) ? $result->gameURL : false;

        // $url = "https://tigergames-sg0.prerelease-env.biz/gs2c/playGame.do?key=$token&stylename=$stylename&symbol=$game_code&technology=H5&platform=WEB&language=en";
        
        // return $url;
    }

    public static function yggdrasillaunchUrl($data){
        $provider_id = config("providerlinks.ygg.provider_id");
        Helper::saveLog('YGG gamelaunch', $provider_id, json_encode($data), "Endpoing hit");
        $url = config("providerlinks.ygg.api_url");
        $org = config("providerlinks.ygg.Org");
        $client_details = ProviderHelper::getClientDetails('token',$data['token']);
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
        try{
            $url = $url."gameid=".$data['game_code']."&lang=".$client_details->language."&currency=".$client_details->default_currency."&org=".$org."&channel=pc&key=".$data['token'];
            Helper::saveLog('YGG gamelaunch', $provider_id, json_encode($data), $url);
            return $url;
        }catch(\Exception $e){
            $error = [
                'error' => $e->getMessage()
            ];
            Helper::saveLog('YGG gamelaunch', $provider_id, json_encode($data), $e->getMessage());
            return $error;
        }

    }

    public static function goldenFLaunchUrl($data){
        $operator_token = config("providerlinks.goldenF.operator_token");
        $api_url = config("providerlinks.goldenF.api_url");
        $secrete_key = config("providerlinks.goldenF.secrete_key");
        $provider_id = config("providerlinks.goldenF.provider_id");
        $client_details = ProviderHelper::getClientDetails('token',$data['token']);
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
        $player_id = "TG_".$client_details->player_id;
        $nickname = str_replace(' ', '_', $client_details->display_name);
        try{
            $url_create = $api_url ."/Player/Create?secret_key=".$secrete_key."&operator_token=".$operator_token."&player_name=".$player_id."&currency=".$client_details->default_currency;
        
            $http = new Client();
            // TRY BOTH
            // $response = $http->get($url_create);
            $response = $http->post($url_create);
            $create_player = json_decode($response->getBody()->getContents());
            // Helper::saveLog('GoldenF Create Player response', $provider_id, json_encode($data), $response);
            Helper::saveLog('GoldenF Create Player', $provider_id, json_encode($data), $url_create);
            Helper::saveLog('GoldenF Create Player response', $provider_id, json_encode($data), $create_player);
         
            if($create_player->data->action_result == "Success"):
                $gameluanch_url = $api_url."/Launch?secret_key=".$secrete_key."&operator_token=".$operator_token."&game_code=".$data['game_code']."&player_name=".$player_id."&nickname=".$nickname."&language=".$client_details->language;

                $response = $http->post($gameluanch_url);
                $get_url = json_decode($response->getBody()->getContents());
                Helper::saveLog('GoldenF gamelaunch', $provider_id, json_encode($data), $gameluanch_url);
                Helper::saveLog('GoldenF game url', $provider_id, json_encode($data), $get_url->data->game_url);

                $get_bal_url = $api_url."/GetPlayerBalance?secret_key=".$secrete_key."&operator_token=".$operator_token."&player_name=".$player_id;
                $get_bal = $http->post($get_bal_url);
                $bal = json_decode($get_bal->getBody()->getContents());
                Helper::saveLog('GoldenF balance', $provider_id, json_encode($bal), $get_bal_url); 

                return $get_url->data->game_url;
            endif;
        }catch(\Exception $e){
            $error = [
                'error' => $e->getMessage()
            ];
            Helper::saveLog('GoldenF gamelaunch err', $provider_id, json_encode($data), $e->getMessage());
            return $error;
        }
    }

    public static function iaLaunchUrl($game_code,$token,$exitUrl)
    {
        $player_details = Providerhelper::getClientDetails('token', $token);
        $provider_reg_currency = Providerhelper::getProviderCurrency(15, $player_details->default_currency);
        if($provider_reg_currency == 'false'){
            return 'false';
        }
        $username = config('providerlinks.iagaming.prefix').$player_details->client_id.'_'.$player_details->player_id;
        $currency_code = $player_details->default_currency; 
        $params = [
                "register_username" => $username,
                "lang" => 2,
                "currency_code" => $currency_code,
        ];
        $uhayuu = IAHelper::hashen($params);
        $header = ['pch:'. config('providerlinks.iagaming.pch')];
        $timeout = 5;
        $client_response = IAHelper::curlData(config('providerlinks.iagaming.url_register'), $uhayuu, $header, $timeout);
         Helper::saveLog('IA Launch Game', 15, json_encode($client_response), $params);
        $data = json_decode(IAHelper::rehashen($client_response[1], true));
        if($data->status): // IF status is 1/true //user already register
            $data = IAHelper::userlunch($username);
            return $data;
        else: // Else User is successfull register
            $data = IAHelper::userlunch($username);
            return $data;
        endif;  
    }

    private static function icgGameUrl($currency){
        $http = new Client();
        $response = $http->get(config("providerlinks.icgaminggames"), [
            'headers' =>[
                'Authorization' => 'Bearer '.GameLobby::icgConnect($currency),
                'Accept'     => 'application/json'
            ]
        ]);
        return json_decode((string) $response->getBody(), true);
    }
    private static function icgConnect($currency){
        $http = new Client();
        switch($currency){
            case "JPY":
                $username = config("providerlinks.icgagents.jpyagents.username");
                $password = config("providerlinks.icgagents.jpyagents.password");
            break;
            case "CNY":
                $username = config("providerlinks.icgagents.cnyagents.username");
                $password = config("providerlinks.icgagents.cnyagents.password");
            break;
            case "EUR":
                $username = config("providerlinks.icgagents.euragents.username");
                $password = config("providerlinks.icgagents.euragents.password");
            break;
            case "KRW":
                $username = config("providerlinks.icgagents.krwagents.username");
                $password = config("providerlinks.icgagents.krwagents.password");
            break;
            case "PHP":
                $username = config("providerlinks.icgagents.phpagents.username");
                $password = config("providerlinks.icgagents.phpagents.password");
            break;
            case "THB":
                $username = config("providerlinks.icgagents.thbagents.username");
                $password = config("providerlinks.icgagents.thbagents.password");
            break;
            case "TRY":
                $username = config("providerlinks.icgagents.tryagents.username");
                $password = config("providerlinks.icgagents.tryagents.password");
            break;
            case "TWD":
                $username = config("providerlinks.icgagents.twdagents.username");
                $password = config("providerlinks.icgagents.twdagents.password");
            break;
            case "VND":
                $username = config("providerlinks.icgagents.vndagents.username");
                $password = config("providerlinks.icgagents.vndagents.password");
            break;
            default:
                $username = config("providerlinks.icgagents.usdagents.username");
                $password = config("providerlinks.icgagents.usdagents.password");

        }
        $response = $http->post(config("providerlinks.icgaminglogin"), [
            'form_params' => [
                'username' => $username,
                'password' => $password,
            ],
        ]);

        return json_decode((string) $response->getBody(), true)["token"];
    }
    public static function getClientDetails($type = "", $value = "") {

        $query = DB::table("clients AS c")
                 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'p.test_player', 'pst.token_id', 'pst.player_token' , 'pst.status_id', 'p.display_name', 'c.client_code','c.default_currency','c.default_language','c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
                 ->leftJoin("players AS p", "c.client_id", "=", "p.client_id")
                 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
                 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
                 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id");
                 
                if ($type == 'token') {
                    $query->where([
                        ["pst.player_token", "=", $value],
                        ["pst.status_id", "=", 1]
                    ]);
                }

                if ($type == 'player_id') {
                    $query->where([
                        ["p.player_id", "=", $value],
                        ["pst.status_id", "=", 1]
                    ]);
                }

                 $result= $query->first();

        return $result;

    }

    public static function solidLaunchUrl($game_code,$token,$exitUrl){
        $client_code = config("providerlinks.solid.BRAND");
        $launch_url = config("providerlinks.solid.LAUNCH_URL");
        $url = $launch_url.$client_code.'/'.$game_code.'?language=en&currency=USD&token='.$token.'&exiturl='.$exitUrl.'';
        return $url;
    }

    public static function mannaLaunchUrl($game_code,$token,$exitUrl){
        $client_details = GameLobby::getClientDetails('token', $token);

        // Authenticate New Token
        $auth_token = new Client([ // auth_token
                'headers' => [ 
                    'Content-Type' => 'application/json',
                    'apiKey' => config("providerlinks.manna.AUTH_API_KEY")
                ]
            ]);

        $auth_token_response = $auth_token->post(config("providerlinks.manna.AUTH_URL"),
                ['body' => json_encode(
                        [
                            "id" => "betrnk",
                            "account" => $client_details->username,
                            "currency" => 'USD',
                            "sessionId" => $token,
                            "channel" => ($client_details->test_player ? "demo" : "")
                        ]
                )]
            );

        $auth_result = json_decode($auth_token_response->getBody()->getContents());

        // Generate Game Link
        $game_link = new Client([
                'headers' => [ 
                    'Content-Type' => 'application/json',
                    'apiKey' => config("providerlinks.manna.AUTH_API_KEY"),
                    'token' => $auth_result->token
                ]
            ]);

        $game_link_response = $game_link->post(config("providerlinks.manna.GAME_LINK_URL"),
                ['body' => json_encode(
                        [
                            "account" => $client_details->username,
                            "sessionId" => $token,
                            "language" => "en-US",
                            "gameId" => $game_code,
                            "exitUrl" => $exitUrl
                        ]
                )]
            );

        $link_result = json_decode($game_link_response->getBody()->getContents());

        return $link_result->url;
    }

    public static function aoyamaLaunchUrl($game_code,$token,$exitUrl){
        /*$client_details = GameLobby::getClientDetails('token', $token);*/
        $client_code = 'BETRNKMW'; /*$client_details->client_code ? $client_details->client_code : 'BETRNKMW';*/
        $url = $exitUrl;
        $url = 'https://svr.betrnk.games/winwin/';
        return $url;
    }

    public static function oryxLaunchUrl($game_code,$token,$exitUrl){
        $url = $exitUrl;
        /*$url = 'https://cdn.oryxgaming.com/badges/ORX/_P168/2019-P09.05/index.html?token='.$token.'&gameCode='.$game_code.'&languageCode=ENG&play_mode=REAL&lobbyUrl=OFF';*/
        $url = 'https://play-prodcopy.oryxgaming.com/agg_plus_public/launch/wallets/WELLTREASURETECH/games/'.$game_code.'/open?token='.$token.'&languageCode=ENG&playMode=REAL';
        return $url;
    }

    public static function vivoGamingLaunchUrl($game_code,$token,$exitUrl,$provider){
        $operator_id = config("providerlinks.vivo.OPERATOR_ID");
        $server_id = config("providerlinks.vivo.SERVER_ID");

        switch ($provider) {
            case 'Vivo Gaming':
                $url = config("providerlinks.vivo.VIVO_URL").'?token='.$token.'&operatorid='.$operator_id.'&serverid='.$server_id.'&IsSwitchLobby=true&Application=lobby&language=EN&IsInternalPop=True';
                break;

            case 'Betsoft':
                $url = config("providerlinks.vivo.BETSOFT_URL").'?Token='.$token.'&GameID='.$game_code.'&OperatorId='.$operator_id.'&lang=EN&cashierUrl=&homeUrl=';
                break;

            case 'Spinomenal':
                $url = config("providerlinks.vivo.SPINOMENAL_URL").'?token='.$token.'&operatorID='.$operator_id.'&GameID='.$game_code.'&PlatformId=1';
                break;

            case 'Tom Horn':
                $url = config("providerlinks.vivo.TOMHORN_URL").'?GameID='.$game_code.'&Token='.$token.'&lang=EN&OperatorID='.$operator_id.'';
                break;

            case 'Nucleus':
                $url = config("providerlinks.vivo.NUCLEUS_URL").'?token='.$token.'&operatorid='.$operator_id.'&GameID='.$game_code.'';
                break;

             case 'Platipus':
                $launch_id = substr($game_code, strpos($game_code, "-") + 1);

                $url = config("providerlinks.vivo.PLATIPUS_URL").'?token='.$token.'&operatorID='.$operator_id.'&room=154&gameconfig='.$launch_id.'';
                break;

            case 'Leap':
                $url = config("providerlinks.vivo.LEAP_URL").'?tableguid=JHN3978RJH39UR93USDF34&token='.$token.'&OperatorId='.$operator_id.'&language=en&cashierUrl=&homeUrl=&GameID='.$game_code.'&mode=real&skinid=37&siteid=1&currency=USD';
                break;
            
            default:
                # code...
                break;
        }
        
        return $url;
    }

    public static function simplePlayLaunchUrl($game_code,$token,$exitUrl){
        $url = $exitUrl;
        $dateTime = date("YmdHis", strtotime(Helper::datesent()));
        $secretKey = config("providerlinks.simpleplay.SECRET_KEY");
        $md5Key = config("providerlinks.simpleplay.MD5_KEY");
        
        $client_details = Providerhelper::getClientDetails('token', $token);

        /* [START] LoginRequest */
        $queryString = "method=LoginRequest&Key=".$secretKey."&Time=".$dateTime."&Username=".$client_details->username."&CurrencyType=".$client_details->default_currency."&GameCode=".$game_code."&Mobile=0";
        $hashedString = md5($queryString.$md5Key.$dateTime.$secretKey);
        $response = ProviderHelper::simplePlayAPICall($queryString, $hashedString);
        $url = (string) $response['data']->GameURL;
        /* [END] LoginRequest */


        /* [START] RegUserInfo */
        /* $queryString = "method=RegUserInfo&Key=".$secretKey."&Time=".$dateTime."&Username=".$client_details->username."&CurrencyType=".$client_details->default_currency;

        $hashedString = md5($queryString.$md5Key.$dateTime.$secretKey);

        $response = ProviderHelper::simplePlayAPICall($queryString, $hashedString);
        var_dump($response); die(); */
        /* [END] RegUserInfo */

        return $url;
    }

    
    
    public static function getLanguage($provider_name,$language){
        $provider_language = DB::table("providers")->where("provider_name",$provider_name)->get();
        $languages = json_decode($provider_language[0]->languages,TRUE);
        if(array_key_exists($language,$languages)){
            return $languages[$language];
        }
        else{
            return $languages["en"];
        }
    }


     /**
     * Client Player Details API Call
     * @return [Object]
     * @param $[player_token] [<players token>]
     * @param $[refreshtoken] [<Default False, True token will be requested>]
     * 
     */
    public static function playerDetailsCall($player_token, $refreshtoken=false){
        $client_details = DB::table("clients AS c")
                     ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.client_player_id', 'p.language', 'p.currency', 'pst.token_id', 'pst.player_token' , 'c.client_url', 'c.default_currency', 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
                     ->leftJoin("players AS p", "c.client_id", "=", "p.client_id")
                     ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
                     ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
                     ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id")
                     ->where("pst.player_token", "=", $player_token)
                     ->latest('token_id')
                     ->first();
        if($client_details){
            try{
                $client = new Client([
                    'headers' => [ 
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$client_details->client_access_token
                    ]
                ]);
                $datatosend = ["access_token" => $client_details->client_access_token,
                    "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
                    "type" => "playerdetailsrequest",
                    "clientid" => $client_details->client_id,
                    "playerdetailsrequest" => [
                        "client_player_id" => $client_details->client_player_id,
                        "token" => $player_token,
                        // "playerId" => $client_details->client_player_id,
                        // "currencyId" => $client_details->currency,
                        "gamelaunch" => false,
                        "refreshtoken" => $refreshtoken
                    ]
                ];
                $guzzle_response = $client->post($client_details->player_details_url,
                    ['body' => json_encode($datatosend)]
                );
                $client_response = json_decode($guzzle_response->getBody()->getContents());
                return $client_response;
            }catch (\Exception $e){
               return false;
            }
        }else{
            return false;
        }
    }

}

?>
