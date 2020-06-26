<?php

namespace App\Http\Controllers\GameLobby;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Helpers\Helper;
use Session;
use DB;

class GameFavoriteController extends Controller
{

	public function index(Request $request){

		$client_details = $this->_getClientDetails('token', $request->token);
		$game_provider = $this->_getProviderGame('provider_name', $request->provider_name);
		$game_details = $this->_findGame($request->game_code, $game_provider->provider_id);
		if($game_details){
			$data = [
				"client_id" => $client_details->client_id,
				"player_id" => $client_details->player_id, // Player ID in MW not the client player!
				"game_id" => $game_details->game_id,
				"game_code" => $request->game_code,
				"provider_id" => $game_provider->provider_id,
			];
		}else{
			$data = [
				"status" => 'failed',
				"message" => 'No Game Was Found!',
			];
		}
		
		return json_encode($data);

		// COMMENTED WORKING DONT DELETE
	 	// $game_provider = $this->_getProviderGame('provider_name', $request->provider_name);
	 	// $game_details = $this->_findGame($request->game_code, $game_provider->provider_id);
		//$client_details = $this->_getClientDetails('token', $request->token);
		//$check = $this->_checkFavorite( $client_details->client_id, $client_details->player_id, $request->game_code, $game_provider->provider_id);


		// $msg = [
		// 	'success' => 'false',
		// 	'message' => 'Already Added',
		// ];
		// if(!$check){
		// 	$data = [
		// 		"client_id" => $client_details->client_id,
		// 		"player_id" => $client_details->player_id,
		// 		"game_id" => $game_details->game_id,
		// 		"game_code" => $request->game_code,
		// 		"provider_id" => $game_provider->provider_id,
		// 	];
		// 	$data_saved = DB::table('client_player_favorites')->insertGetId($data);
		// 	$msg = [
		// 		'success' => 'true',
		// 		'message' => 'Successfully Added',
		// 	];
		// }
	
		// return $msg;

	}

	public function playerInfo(Request $request){
		$client_details = $this->_getClientDetails('token', $request->token);
		$data = [
				"client_id" => $client_details->client_id,
				"player_id" => $client_details->player_id,
		];
		return $data;
	}

	public function playerFavorite(Request $request){
		// $client_details = $this->_getClientDetails('token', $request->token);
		// $data = DB::table("client_player_favorites as cpf")
		// 			 ->select('cpf.game_code', 'g.game_name', 'gt.game_type_name', 'g.icon as game_icon', 'g.game_code', 'p.provider_name', 'p.icon as provider_icon')
		// 			 ->leftJoin('games as g', "g.game_code", "=", "cpf.game_code")
		// 			 ->leftJoin('providers as p', "cpf.provider_id", "=", "p.provider_id")
		// 			 ->leftJoin('game_types as gt', "gt.game_type_id", "=", "g.game_type_id")
		// 			 ->where('cpf.client_id', $client_details->client_id)
		// 			 ->where('cpf.player_id', $client_details->player_id)
		//  			 ->get();
		// return $data;	

		// TEST ARRAY JSON
		$json_data = json_decode(file_get_contents("php://input"), true);
		// Helper::saveLog('AuFAVORITES', 14, file_get_contents("php://input"), file_get_contents("php://input"));
		$games = DB::table('games as g')
					->select('g.game_name', 'gt.game_type_name', 'g.icon as game_icon', 'g.game_code', 'p.provider_name', 'p.icon as provider_icon')
                    ->leftJoin('providers as p', "g.provider_id", "=", "p.provider_id")
                     ->leftJoin('game_types as gt', "gt.game_type_id", "=", "g.game_type_id")
                    ->whereIn('g.game_id', $json_data['favorite_games'])
                    ->get();
		return $games;
	}

	public function _getClientDetails($type = "", $value = "") {
		$query = DB::table("clients AS c")
					 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.token_id', 'pst.player_token' , 'c.client_url', 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
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

					 $result= $query
					 			->latest('token_id')
					 			->first();
			return $result;
	}

	public function _getProviderGame($type = "", $value = ""){
			$query = DB::table("providers AS p")
					 ->select('p.provider_id', 'p.provider_name', 'g.game_id', 'g.game_code')
					 ->leftJoin("games AS g", "g.provider_id", "=", "p.provider_id");
					
			if ($type == 'provider_name') {
					$query->where([
				 		["p.provider_name", "=", $value],
				 	]);
			}	
					
			$result= $query
		 			->first();
			return $result;	 
	}

	public function _findGame($game_code, $provider_id){
			$query = DB::table("providers AS p")
					 ->select('p.provider_id', 'p.provider_name', 'g.game_id', 'g.game_code')
					 ->leftJoin("games AS g", "g.provider_id", "=", "p.provider_id");
					
			if ("ashen" == "ashen") { // xD
					$query->where([
				 		["g.game_code", "=", $game_code],
				 		["p.provider_id", "=", $provider_id],
				 	]);
			}	
					
			$result= $query
		 			->first();
			return $result;	 
	}

	public function _checkFavorite($client_id, $player_id, $game_code, $provider_id){

			$query = DB::table("client_player_favorites")
					 ->select('*')
					 ->where('client_id', $client_id)
					 ->where('player_id', $player_id)
					 ->where('game_code', $game_code)
					 ->where('provider_id', $provider_id)
		 			->first();

			return $query ? true : false;	 
	}

}