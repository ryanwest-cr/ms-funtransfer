<?php
namespace App\Helpers;
use DB;

class PlayerHelper
{
	public static function saveIfNotExist($client_details, $player_details) {
		
		$check_if_player_exist = DB::table('players')
								->where('client_player_id', $player_details->playerdetailsresponse->accountid)
								->where('client_id', $client_details->client_id)
								->first();

		if($check_if_player_exist) {
			$player_id = $check_if_player_exist->player_id;
		}
		else
		{
			$player_details = $player_details->playerdetailsresponse;

			$data = [
					"client_id" => $client_details->client_id, 
					"client_player_id" => $player_details->accountid,
					"username" => $player_details->accountname,
					"email" => $player_details->email,
					"display_name" => $player_details->accountname,
					"language" => 'en',
					"currency" => $player_details->currencycode
				];

			$player_id =  DB::table('players')->insertGetId($data);
		}

		return $player_id;
	}

	public static function getPlayerDetails($player_id, $type = 'player_id') {
		$query = DB::table("players AS p")
				 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.token_id', 'pst.player_token' , 'pst.status_id', 'p.display_name')
				 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id");

				if ($type == 'token') {
					$query->where([
				 		["pst.player_token", "=", $player_id],
				 		["pst.status_id", "=", 1]
				 	]);
				}

				if ($type == 'player_id') {
					$query->where([
				 		["p.player_id", "=", $player_id],
				 		["pst.status_id", "=", 1]
				 	]);
				}

				if ($type == 'username') {
					$query->where([
				 		["p.username", "=", $player_id],
				 		["pst.status_id", "=", 1]
				 	]);
				}

				$result= $query->first();

		return $result;
	}

	public static function getPlayerIdByUsername($username) {
		$query = DB::table("players AS p")
				 ->select('p.player_id')
				 ->where('p.username', $username);
				 
				 $result = $query->first();

		return $result ? $result->player_id : false;
	}

}