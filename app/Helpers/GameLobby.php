<?php
namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use App\Helpers\Helper;
use App\Helpers\IAHelper;
use DB;             
class GameLobby{
    public static function icgLaunchUrl($game_code,$token,$exitUrl,$lang="en"){
        $client = GameLobby::getClientDetails("token",$token);
        Helper::saveLog('GAMELAUNCH ICG', 11, json_encode($game_code), json_encode($lang));
        $game_list =GameLobby::icgGameUrl($client->default_currency);
        foreach($game_list["data"] as $game){
            if($game["productId"] == $game_code){
                $lang = GameLobby::getLanguage("Iconic Gaming",$lang);
                Helper::savePLayerGameRound($game["productId"],$token);
                return $game["href"].'&token='.$token.'&lang='.$lang.'&home_URL='.$exitUrl;
            }
        }
    }
    public static function edpLaunchUrl($game_code,$token,$exitUrl){
        $profile = "nofullscreen_money.xml";
        $sha1key = sha1($exitUrl.''.config("providerlinks.endorphina.nodeId").''.$profile.''.$token.''.config("providerlinks.endorphina.secretkey"));
        $sign = $sha1key; 
        Helper::savePLayerGameRound($game_code,$token);
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

}

?>
