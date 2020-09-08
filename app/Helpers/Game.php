<?php
namespace App\Helpers;
use DB;

class Game
{
	public static function find($game_code, $provider_id = 0) {
		$search_result = DB::table('games')
								->where('game_code', $game_code)
								->where('provider_id', $provider_id)
								->first();	
		return ($search_result ? $search_result : false);
	}

	public static function findbyid($game_id) {
		$search_result = DB::table('games')
								->where('game_id', $game_id)
								->first();	
		return ($search_result ? $search_result : false);
	}

}