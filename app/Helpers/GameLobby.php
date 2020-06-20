<?php
namespace App\Helpers;

use GuzzleHttp\Client;
use App\Helpers\Helper;
use App\Helpers\IAHelper;
use DB;             
class GameLobby{
    public static function icgLaunchUrl($game_code,$token,$exitUrl){
        $game_list =GameLobby::icgGameUrl();
        foreach($game_list["data"] as $game){
            if($game["productId"] == $game_code){
                Helper::savePLayerGameRound($game["productId"],$token);
                return $game["href"].'&token='.$token.'&lang=en&home_URL='.$exitUrl;
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
        $http = new Client();
        $response = $http->post(config('providerlinks.bolegaming.login_url'), [
            'form_params' => [
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
            ],
        ]);
        // Helper::saveLog('GAMELAUNCH BOLE', 11, json_encode($data), json_encode($response->getBody()->getContents()));
        $client_response = json_decode($response->getBody()->getContents());
        dd($client_response);
        return $client_response->resp_data->url;
        
        // return $client_response;
    }

    public static function rsgLaunchUrl($game_code,$token,$exitUrl){

        $url = $exitUrl;
        $domain = parse_url($url, PHP_URL_HOST);
        $url = 'https://partnerapirgs.betadigitain.com/GamesLaunch/Launch?gameid='.$game_code.'&playMode=real&token='.$token.'&deviceType=1&lang=EN&operatorId=B9EC7C0A&mainDomain='.$domain.'';
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

    private static function icgGameUrl(){
        $http = new Client();

        $response = $http->get(config("providerlinks.icgaminggames"), [
            'headers' =>[
                'Authorization' => 'Bearer '.GameLobby::icgConnect(),
                'Accept'     => 'application/json'
            ]
        ]);
        return json_decode((string) $response->getBody(), true);
    }
    private static function icgConnect(){
        $http = new Client();

        $response = $http->post(config("providerlinks.icgaminglogin"), [
            'form_params' => [
                'username' => 'betrnk',
                'password' => 'betrnk168!^*',
            ],
        ]);

        return json_decode((string) $response->getBody(), true)["token"];
    }
    public static function getClientDetails($type = "", $value = "") {

        $query = DB::table("clients AS c")
                 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.token_id', 'pst.player_token' , 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
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
}

?>