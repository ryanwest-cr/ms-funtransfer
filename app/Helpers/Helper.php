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
	public static function checkPlayerExist($client_id, $client_player_id, $username,  $email, $display_name,$token){
		$player = DB::table('players')
					->where('client_id',$client_id)
					->where('client_player_id',$client_player_id)
					->where('username',$username)
					->first();
		if($player){
			return Helper::createPlayerSessionToken($client_player_id,$token);
		}
		else{
			Helper::save_player($client_id,$client_player_id,$username,$email,$display_name);
			return Helper::createPlayerSessionToken($client_player_id,$token);
		}
	}
	public static function createPlayerSessionToken($player_id,$token){
		$player_session_token = array(
			"player_id" => $player_id,
			"player_token" => $token,
			"status_id" => 1
		);
		DB::table('player_session_tokens')->insert($player_session_token);
		return $token;
	}
	public static function savePLayerGameRound($game_code,$player_token){
		$game = DB::table("games")->where("game_code",$game_code)->first();
		$player_game_round = array(
			"player_token" => $player_token,
			"game_id" => $game->game_id,
			"status_id" => 1
		);
		DB::table("player_game_rounds")->insert($player_game_round);
	}
	public static function getInfoPlayerGameRound($player_token){
		$game = DB::table("player_game_rounds as pgr")
				->leftJoin("player_session_tokens as pst","pst.player_token","=","pgr.player_token")
				->leftJoin("games as g" , "g.game_id","=","pgr.game_id")
				->leftJoin("players as ply" , "pst.player_id","=","ply.player_id")
				->where("pgr.player_token",$player_token)
				->first();
		return $game ? $game : false;
	}
	public static function createGameTransaction($method, $request_data, $game_data, $client_data){
		/*var_dump($request_data); die();*/
		$trans_data = [
			"token_id" => $client_data->token_id,
			"game_id" => $game_data->game_id,
			"round_id" => $request_data["roundid"]
		];

		switch ($method) {
			case "debit":
					$trans_data["provider_trans_id"] = $request_data["transid"];
					$trans_data["bet_amount"] = abs($request_data["amount"]);
					$trans_data["win"] = 0;
					$trans_data["pay_amount"] = abs($request_data["amount"]);
					$trans_data["entry_id"] = 1;
				break;
			case "credit":
					$trans_data["provider_trans_id"] = $request_data["transid"];
					$trans_data["bet_amount"] = 0;
					$trans_data["win"] = $request_data["win"];
					$trans_data["pay_amount"] = abs($request_data["amount"]);
					$trans_data["entry_id"] = 2;
					$trans_data["payout_reason"] = $request_data["payout_reason"];
				break;
			case "refund":
					$trans_data["bet_amount"] = 0;
					$trans_data["win"] = 0;
					$trans_data["pay_amount"] = $request_data["amount"];
					$trans_data["entry_id"] = 2;
					$trans_data["payout_reason"] = "Refund of transaction ID: ".$request_data["transid"]."of GameRound ".$request_data["roundid"];
				break;

			default:
		}
		/*var_dump($trans_data); die();*/
		return DB::table('game_transactions')->insertGetId($trans_data);			
	}
	public static function getGameTransaction($player_token,$game_round){
		$game = DB::table("player_session_tokens as pst")
				->leftJoin("game_transactions as gt","pst.token_id","=","gt.token_id")
				->where("pst.player_token",$player_token)
				->where("gt.round_id",$game_round)
				->where("gt.entry_id",1)
				->first();
		return $game;
	}
	public static function checkGameTransaction($provider_transaction_id,$round_id=false,$type=false){
		if($type&&$round_id){
			$game = DB::table('game_transactions')
				->where('provider_trans_id',$provider_transaction_id)
				->where('round_id',$round_id)
				->where('entry_id',$type)
				->first();
		}
		else{
			$game = DB::table('game_transactions')
				->where('provider_trans_id',$provider_transaction_id)
				->first();
		}
		return $game ? true :false;
	}

}