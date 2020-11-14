<?php
namespace App\Helpers;
use Illuminate\Http\Request;
use App\Helpers\ProviderHelper;
use App\Helpers\Helper;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;
use DB;


class SessionWalletHelper
{

	public static $time_deduction = 20; // Seconds Deductions
	public static $session_time = 120; //  Seconds lifetime session

	/**
	 * [updateSessionTime - update set session to default $session_time]
	 * 
	 */
    public static function updateSessionTime($token){
    	DB::select('UPDATE wallet_session SET session_time = '.self::$session_time.' WHERE token = "'.$token.'"');
    }

    /**
	 * [deductSession - deductSession Session]
	 * 
	 */
    public static function deductSession(){
	  	DB::select('UPDATE wallet_session SET session_time = session_time-'.self::$time_deduction.'');
    }

    public static function checkIfExistWalletSession($token){
        // $token = DB::select('SELECT * FROM wallet_session WHERE token = "'.$request->token.'"');
    	$query = DB::select('SELECT * FROM wallet_session WHERE token = "'.$token.'"');
        $data = count($query);
		return $data > 0 ? $query[0] : false;
    }

    public static function checkIfExistWalletSession($token){
        // $token = DB::select('SELECT * FROM wallet_session WHERE token = "'.$request->token.'"');
    	$query = DB::select('SELECT * FROM wallet_session WHERE token = "'.$token.'"');
        $data = count($query);
        if($data>0){
            $player_wallet_session = DB::select('SELECT * FROM wallet_session WHERE player_id = "' . $query[0]['player_id'] . '"');
            $wallet_sessions = count($player_wallet_session);
            if($wallet_sessions > 1){
                return false; // morethan 1 session detected
            }
        }
		return $data > 0 ? $query[0] : false;
    }


    public static function createWalletSession($token, $metadata){
    	$query = DB::table('wallet_session')->insert(
        array('token' => $token, 
              'metadata' =>  json_encode($metadata))
        );
        return $query ? $query : false;
    }

    /**
	 * [deleteSession - delete Session once withrawn]
	 * 
	 */
    public static function deleteSession($token){
    	DB::select('DELETE FROM wallet_session WHERE token = "'.$token.'"');
    }

    /**
	 * [tokenizer - connect to middleware]
	 * 
	 */
    public  static function tokenizer(){
        $http = new Client();
        $middleware_request = $http->post(config('providerlinks.oauth_mw_api.access_url'), [
            'form_params' => [
                 'grant_type' => config('providerlinks.oauth_mw_api.grant_type'),
                 'client_id' => config('providerlinks.oauth_mw_api.client_id'),
                 'client_secret' => config('providerlinks.oauth_mw_api.client_secret'),
                 'username' => config('providerlinks.oauth_mw_api.username'),
                 'password' => config('providerlinks.oauth_mw_api.password'),
               ],
        ]);
        return json_decode((string) $middleware_request->getBody(), true)["access_token"];
    }

    public static function saveLog($method, $provider_id = 0, $request_data, $response_data) {
		$data = [
				"method_name" => $method,
				"provider_id" => $provider_id,
				"request_data" => json_encode(json_decode($request_data)),
				"response_data" => json_encode($response_data)
			];
		return DB::table('seamless_request_logs')->insertGetId($data);
		// return DB::table('debug')->insertGetId($data);
	}
}