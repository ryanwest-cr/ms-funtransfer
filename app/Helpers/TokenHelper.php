<?php
namespace App\Helpers;
use DB;

class TokenHelper
{
	public static function saveIfNotExist($player_id, $player_token) {
		$check_if_token_exist = DB::table('player_session_tokens')
								->where('player_id', $player_id)
								->where('player_token', $player_token)
								->first();
		
		/*var_dump($check_if_token_exist); die();*/

		if(!$check_if_token_exist) {
			$data = ["player_id" => $player_id, "player_token" => $player_token];
			DB::table('player_session_tokens')->insert($data);
		}
		
	}

	public static function changeStatus($player_id, $action) {
		 DB::table('player_session_tokens')
                ->where('player_id', $player_id)
                ->update(['status_id' => 5]);
	}
}