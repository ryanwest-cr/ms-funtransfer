<?php
namespace App\Helpers;
use DB;

class Game
{
	public static function find($game_code, $provider_id = 0) {
		// $search_result = DB::table('games')
		// 						->where('game_code', $game_code)
		// 						->where('provider_id', $provider_id)
		// 						->first();	
		// return ($search_result ? $search_result : false);

		$details = "where g.provider_id = ".$provider_id." and g.game_code = '".$game_code."' limit 1";
		$game_details = DB::select('select g.game_name, g.game_code, g.game_id from games g inner join providers as p using (provider_id) '.$details.' ');
		
	 	return $game_details ? $game_details[0] : false;
	}

	public static function findbyid($game_id) {
		// $search_result = DB::table('games')
		// 						->where('game_id', $game_id)
		// 						->first();	
		// return ($search_result ? $search_result : false);

		$details = "where g.game_id = ".$game_id." limit 1";
		$game_details = DB::select('select g.game_name, g.game_code, g.game_id from games g inner join providers as p using (provider_id) '.$details.' ');
		
	 	return $game_details ? $game_details[0] : false;
	}

	public static function findby($type, $value, $provider_id = 0) {
		if ($type == 'round_id') {
			$refference_id = "gt.round_id = '".$value."'";
		}elseif ($type == 'trans_id') {
			$refference_id = "gt.provider_trans_id  = '".$value."'";
		}
				
		$result = DB::select('select * from game_transactions gt inner join games as g using(game_id) where '.$refference_id.' and g.provider_id = '.$provider_id.' limit 1');


		// $query = DB::table("game_transactions AS gt")
		// 						->select('g.*')
		// 						->leftJoin("games AS g", "gt.game_id", "=", "g.game_id")
		// 						->where('g.provider_id', $provider_id);

		// 		if ($type == 'round_id') {
		// 			$query->where([
		// 		 		["gt.round_id", "=", $value]
		// 		 	]);
		// 		}
				
		// 		if ($type == 'trans_id') {
		// 			$query->where([
		// 		 		["gt.provider_trans_id", "=", $value]
		// 		 	]);
		// 		}

		// 		$result = $query->first();	

		return ($result ? $result[0] : false);
	}

}
