<?php
namespace App\Helpers;
use DB;

class GameRound
{
	public static function find($round_id) {
		$search_result = DB::table('game_rounds')
								->where('round_id', $round_id)
								->first();	
		return ($search_result ? true : false);
	}


	public static function check($round_id) {
		$valid_round_result = DB::table('game_rounds')
								->where(['round_id' => $round_id,
										 'status_id' => 1])
								->first();	
		return ($valid_round_result ? true : false);
	}

	public static function end($round_id) {
		$end_round_result = DB::table('game_rounds')
                ->where('round_id', $round_id)
                ->update(['status_id' => 5]);
                
		return ($end_round_result ? true : false);
	}

	public static function create($round_id, $token_id) {
		$check_if_round_exist = DB::table('game_rounds')
								->where('round_id', $round_id)
								->first();
								
		if(!$check_if_round_exist) {
			$data = ["round_id" => $round_id, "token_id" => $token_id];
			DB::table('game_rounds')->insert($data);
		}
		
	}

}