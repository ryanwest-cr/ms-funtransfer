<?php
namespace App\Helpers;
use DB;

class GameSubscription
{
	public function check($client_id, $provider_id, $game_code) {
		$result = false;

		$game_id = $this->_getGameId($provider_id, $game_code);
		
		if($game_id) {
			$client_subscription = DB::table('client_game_subscribe')->where('client_id', $client_id)->first();

			if($client_subscription->provider_selection_type == 'all') {
				$result = true;
			}
			else
			{
				$cgs_id = $client_subscription->cgs_id;
				$provider_subscription = DB::table('selected_providers')
												->leftJoin("game_exclude", "selected_providers.sp_id", "=", "game_exclude.sp_id")
												->where(['cgs_id' => $cgs_id,
														 'provider_id' => $provider_id,
														 'game_id' => $game_id])
												->first();

				if($provider_subscription == NULL) {
					$result = true;
				}
			}
		}


		return $result;

	}

	private function _getGameId($provider_id, $game_code) {
		$result = DB::table("games AS g")
				 ->select('g.game_id')
				 ->where("g.provider_id", $provider_id)
				 ->where("g.game_code", $game_code)
				 ->first();

		return ($result ? $result->game_id : false);
	}



}