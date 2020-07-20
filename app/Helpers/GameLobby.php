<?php
namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use App\Helpers\Helper;
use App\Helpers\IAHelper;
use App\Helpers\AWSHelper;
use App\Helpers\ProviderHelper;
use DB;             
use Carbon\Carbon;
class GameLobby{
    public static function icgLaunchUrl($game_code,$token,$exitUrl,$lang="en"){
        $client = GameLobby::getClientDetails("token",$token);
        
        $game_list =GameLobby::icgGameUrl($client->default_currency);
        Helper::saveLog('GAMELAUNCH ICG', 11, json_encode($game_code), json_encode($game_list));
        foreach($game_list["data"] as $game){
            if($game["productId"] == $game_code){
                $lang = GameLobby::getLanguage("Iconic Gaming",$lang);
                Helper::savePLayerGameRound($game["productId"],$token);
                Helper::saveLog('GAMELAUNCH ICG', 11, json_encode($game_code), json_encode($game["href"]));
                return $game["href"].'&token='.$token.'&lang='.$lang.'&home_URL='.$exitUrl;
                
            }
        }
    }
    public static function booongoLaunchUrl($game_code,$token,$exitUrl){
        $lang = "en";
        $timestamp = Carbon::now()->timestamp;
        $exit_url = $exitUrl;
        Helper::savePLayerGameRound($game_code,$token);
        $gameurl =  config("providerlinks.boongo.PLATFORM_SERVER_URL")
                  .config("providerlinks.boongo.PROJECT_NAME").
                  "/game.html?wl=".config("providerlinks.boongo.WL").
                  "&token=".$token."&game=".$game_code."&lang=".$lang."&sound=1&ts=".
                  $timestamp."&quickspin=1&platform=desktop".
                  "&exir_url=".$exit_url;
        return $gameurl;
    }
    public static function edpLaunchUrl($game_code,$token,$exitUrl){
        $profile = "nofullscreen_money.xml";
        $sha1key = sha1($exitUrl.''.config("providerlinks.endorphina.nodeId").''.$profile.''.$token.''.config("providerlinks.endorphina.secretkey"));
        $sign = $sha1key; 
        Helper::savePLayerGameRound($game_code,$token);
        Helper::saveLog('GAMELAUNCH EDP', 11, json_encode(config("providerlinks.endorphina.url").'?exit='.$exitUrl.'&nodeId='.config("providerlinks.endorphina.nodeId").'&profile='.$profile.'&token='.$token.'&sign='.$sign), json_encode($sign));
        return config("providerlinks.endorphina.url").'?exit='.$exitUrl.'&nodeId='.config("providerlinks.endorphina.nodeId").'&profile='.$profile.'&token='.$token.'&sign='.$sign;
    }
    public static function boleLaunchUrl($game_code,$token,$exitUrl, $country_code){

        $scene_id = '';
        if(strpos($game_code, 'slot') !== false) {
            $game_code = explode("_", $game_code);
            $scene_id = $game_code[1];
            $game_code = 'slot';
        }else{
            $game_code = $game_code;
        }

        $nonce = rand();
        $timestamp = time();
        $key = config('providerlinks.bolegaming.access_key_secret').$nonce.$timestamp;
        $signature = sha1($key);
        $sign = [
            "timestamp" => $timestamp,
            "nonce" => $nonce,
            "signature" => $signature,
        ];
        $client_player_details = GameLobby::getClientDetails('token', $token);
        try {
            $http = new Client();
            $data = [
                'game_code' => $game_code,
                'scene' => $scene_id,
                'player_account' => $client_player_details->player_id,
                'country'=> $country_code,
                'ip'=> $_SERVER['REMOTE_ADDR'],
                'AccessKeyId'=> config('providerlinks.bolegaming.AccessKeyId'),
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
            $response = $http->post(config('providerlinks.bolegaming.login_url'), [
                'form_params' => $data,
            ]);
            $client_response = json_decode($response->getBody()->getContents());
            Helper::saveLog('GAMELAUNCH BOLE', 11, json_encode($data), json_decode($response->getBody()));
            return isset($client_response->resp_data->url) ? $client_response->resp_data->url : false;
        } catch (Exception $e) {
            return false;        
        }
        // Helper::saveLog('GAMELAUNCH BOLE', 11, json_encode($data), json_encode($response->getBody()->getContents()));
        
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

     public static function awsLaunchUrl($token,$game_code,$lang){
        $player_check = AWSHelper::playerCheck($token);
        if($player_check->code == 100){ // Not Registered!
            $register_player = AWSHelper::playerRegister($token);
            if($register_player->code != 2217 || $register_player->code != 0){
                 Helper::saveLog('AWS BO Launch Game Failed', 21, $register_player, $register_player);
                 return 'false';
            }
        }
        $client_details = ProviderHelper::getClientDetails('token', $token);
        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
            ]
        ]);
        $requesttosend = [
            "merchantId" => config('providerlinks.aws.merchant_id'),
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
        Helper::saveLog('AWS BO Launch Game', 21, json_encode($requesttosend), $provider_response);
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

    public static function rsgLaunchUrl($game_code,$token,$exitUrl,$lang='en'){
        $url = $exitUrl;
        $domain = parse_url($url, PHP_URL_HOST);
        $url = 'https://partnerapirgs.betadigitain.com/GamesLaunch/Launch?gameid='.$game_code.'&playMode=real&token='.$token.'&deviceType=1&lang='.$lang.'&operatorId=B9EC7C0A&mainDomain='.$domain.'';
        return $url;
    }


    public static function iaLaunchUrl($game_code,$token,$exitUrl)
    {
        $player_details = GameLobby::getClientDetails('token', $token);
        $username = config('providerlinks.iagaming.prefix').'_'.$player_details->player_id;
        $currency_code = 'USD'; 
        // $currency_code = $request->has('currency_code') ? $request->currency_code : 'USD'; 
        $params = [
                "register_username" => $username,
                "lang" => 2,
                "currency_code" => $currency_code,
        ];
        $uhayuu = IAHelper::hashen($params);
        $header = ['pch:'. config('providerlinks.iagaming.pch')];
        $timeout = 5;
        $client_response = IAHelper::curlData(config('providerlinks.iagaming.url_register'), $uhayuu, $header, $timeout);
        $data = json_decode(IAHelper::rehashen($client_response[1], true));
        if($data->status): // IF status is 1/true //user already register
            $data = IAHelper::userlunch($username);
            // $msg = array(
            //     "game_code" =>  $game_code,
            //     "url" => $data,
            //     "game_launch" => true
            // );
            // return response($msg,200)
            // ->header('Content-Type', 'application/json');
            return $data;
        else: // Else User is successfull register
            $data = IAHelper::userlunch($username);
            // $msg = array(
            //     "game_code" => $game_code,
            //     "url" => $data,
            //     "game_launch" => true
            // );
            // return response($msg,200)
            // ->header('Content-Type', 'application/json');
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
                 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.token_id', 'pst.player_token' , 'pst.status_id', 'p.display_name', 'c.client_code','c.default_currency','c.default_language','c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
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
        /*$client_details = GameLobby::getClientDetails('token', $token);*/
        $client_code = 'BETRNKMW'; /*$client_details->client_code ? $client_details->client_code : 'BETRNKMW';*/
        $url = $exitUrl;
        $url = 'https://instage.solidgaming.net/api/launch/'.$client_code.'/'.$game_code.'?language=en&currency=USD&token='.$token.'';
        return $url;
    }

    public static function mannaLaunchUrl($game_code,$token,$exitUrl){
        $client_details = GameLobby::getClientDetails('token', $token);

        // Authenticate New Token
        $auth_token = new Client([ // auth_token
                'headers' => [ 
                    'Content-Type' => 'application/json',
                    'apiKey' => 'GkyPIN1mD*yzjxzQumq@cZZC!Vw%b!kIVy&&hk!a'
                ]
            ]);

        $auth_token_response = $auth_token->post('https://api.mannagaming.com/agent/specify/betrnk/authenticate/auth_token',
                ['body' => json_encode(
                        [
                            "id" => "betrnk",
                            "account" => $client_details->username,
                            "currency" => 'USD',
                            "sessionId" => $token,
                            "channel" => ""
                        ]
                )]
            );

        $auth_result = json_decode($auth_token_response->getBody()->getContents());

        // Generate Game Link
        $game_link = new Client([
                'headers' => [ 
                    'Content-Type' => 'application/json',
                    'apiKey' => 'GkyPIN1mD*yzjxzQumq@cZZC!Vw%b!kIVy&&hk!a',
                    'token' => $auth_result->token
                ]
            ]);

        $game_link_response = $game_link->post('https://api.mannagaming.com/agent/specify/betrnk/gameLink/link',
                ['body' => json_encode(
                        [
                            "account" => $client_details->username,
                            "sessionId" => $token,
                            "language" => "en-US",
                            "gameId" => $game_code,
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
        $url = 'https://cdn.oryxgaming.com/badges/ORX/_P168/2019-P09.05/index.html?token='.$token.'&gameCode='.$game_code.'&languageCode=ENG&play_mode=REAL&lobbyUrl=OFF';
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
