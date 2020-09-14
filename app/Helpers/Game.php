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

	public static function findby($type, $value, $provider_id = 0) {
		$query = DB::table("game_transactions AS gt")
								->select('g.*')
								->leftJoin("games AS g", "gt.game_id", "=", "g.game_id")
								->where('g.provider_id', $provider_id);

				if ($type == 'round_id') {
					$query->where([
				 		["gt.round_id", "=", $value]
				 	]);
				}
				
				if ($type == 'trans_id') {
					$query->where([
				 		["gt.provider_trans_id", "=", $value]
				 	]);
				}

				$result = $query->first();	

		return ($result ? $result : false);
	}

}