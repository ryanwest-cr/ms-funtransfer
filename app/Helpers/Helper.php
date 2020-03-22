<?php
namespace App\Helpers;
use DB;

class Helper
{
	public static function auth_key($api_key, $access_token) {
		$result = false;


		if($api_key == md5(env('API_KEY').$access_token)) {
			$result = true;
		}

		return $result;
	}

	public static function saveLog($method, $provider_id = 0, $request_data, $response_data) {
		$data = [
					"method_name" => $method,
					"provider_id" => $provider_id,
					"request_data" => json_encode(json_decode($request_data)),
					"response_data" => json_encode($response_data)
				];
		DB::table('seamless_request_logs')->insert($data);
	}


    /* ERAIN */		
    public static function saveGame_transaction($token_id, $game_id, $bet_amount, $payout, $entry_id,  $round_id=1, $win=0 ) {
		$data = [
					"token_id" => $token_id,
					"game_id" => $game_id,
					"round_id" => $round_id,
					"bet_amount" => $bet_amount,
					"pay_amount" => $payout,
					"entry_id" => $entry_id,
					"win" => $win
				];
		DB::table('game_transactions')->insert($data);
	}


	public static function save_player($client_id, $client_player_id, $username,  $email, $display_name, $lang='en', $currency='USD') {
		$data = [
					"client_id" => $client_id,
					"client_player_id" => $client_player_id,
					"username" => $username,
					"email" => $email,
					"display_name" => $display_name
				];
		DB::table('players')->insert($data);
	}

}