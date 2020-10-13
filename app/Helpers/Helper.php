<?php
namespace App\Helpers;
use DB;
use GuzzleHttp\Client;
use Carbon\Carbon;

class Helper
{
	
	public static function datesent(){
		$date = Carbon::now();
		return $date->toDateTimeString();
	}

	// public static function tokenCheck($token){
	// 	$token = DB::table('player_session_tokens')
	// 		        ->select("*", DB::raw("NOW() as IMANTO"))
	// 		    	->where('player_token', $token)
	// 		    	->first();
	// 	if($token != null){
	// 		$check_token = DB::table('player_session_tokens')
 //            ->selectRaw("TIME_TO_SEC(TIMEDIFF('".$token->created_at."', '".$token->IMANTO."'))/60 as TIMEGAP")
	//         ->first();
	//         // return $check_token;
	//         // return $token->created_at.' '.$token->IMANTO;
	//         // 60 Minutes 86400 seconds = 1 DAY!
	//         // 86400/60 = 1440 minutes!
	// 	    if(1440 > $check_token->TIMEGAP) {  // TIMEGAP IN MINUTES!
	// 	        $token = true; // True if Token can still be used!
	// 	    }else{
	// 	    	$token = false; // Expired Token
	// 	    }
	// 	}else{
	// 		$token = false; // Not Found Token
	// 	}
	//     return $token;
	// }

	public static function tokenCheck($token){
		$token = DB::table('player_session_tokens')
			        ->select("*", DB::raw("NOW() as IMANTO"))
			    	->where('player_token', $token)
			    	->first();
		if($token != null){
			$check_token = DB::table('player_session_tokens')
			->selectRaw("TIME_TO_SEC(TIMEDIFF( NOW(), '".$token->created_at."'))/60 as `time`")
			->first();
		    if(1440 > $check_token->time) {  // TIMEGAP IN MINUTES!
		        $token = true; // True if Token can still be used!
		    }else{
		    	$token = false; // Expired Token
		    }
		}else{
			$token = false; // Not Found Token
		}
	    return $token;
	}

	public static function getSubProvider($sub_provider_name){
		$sub_provider = DB::table('sub_providers')
	    	->where('sub_provider_name', $sub_provider_name)
	    	->first();
	    return $sub_provider ? $sub_provider : 'false';
	}

	// client_id, subprovider id, game_code
	// if excluded = no , client cant play the game
	// if exluded = yes, client can play the game!
	// Function Will Return false if the following has matched
	// no game code for that game
	// sub provider doest exist
	// and if the game is excluded for that client
	public static function checkClientGameAccess($client_id, $sub_provider_name, $game_code){
		$sub_prodivder_id = Helper::getSubProvider($sub_provider_name);
		if($sub_prodivder_id == 'false'){
			return 'false';
		}
		$excluded_game = DB::select("SELECT g.game_id, g.game_name, gt.game_type_id, g.provider_id, gt.game_type_name, g.icon, case when game_id IN ( select game_id from game_exclude where cgs_id = ( select cgs_id from client_game_subscribe where client_id = ".$client_id." )) then 'no' else 'yes' end as excluded FROM games g left join game_types gt using(game_type_id) where `sub_provider_id` = ".$sub_prodivder_id->sub_provider_id." AND `game_code` = ".$game_code."");
		if(count($excluded_game) > 0){
			if($excluded_game[0]->excluded == 'no'){
				return 'false'; // CANNOT PLAY!
			}else{
				return 'true'; // CAN PLAY
			}
		}else{
			return 'false'; // NULL NO GAME FOUND!
		}
	}

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

	public static function saveClientLog($method, $provider_id = 0, $sent_data, $response_data) {
		$data = [
					"method_name" => $method,
					"provider_id" => $provider_id,
					"sent_data" => $sent_data,
					"response_data" => json_encode($response_data)
				];

		DB::table('seamless_sent_logs')->insert($data);
	}

	/* NEW 061620 */
	public static function findGameDetails($type, $provider_id, $identification) {
		    $game_details = DB::table("games as g")
				->leftJoin("providers as p","g.provider_id","=","p.provider_id");
				
		    if ($type == 'game_code') {
				$game_details->where([
			 		["g.provider_id", "=", $provider_id],
			 		["g.game_code",'=', $identification],
			 	]);
			}
			$result= $game_details->first();
	 		return $result;
	}

    /* ERAIN 
 	 * Added new $income 05-08-20
 	 * $income=null
     */		
    public static function saveGame_transaction($token_id, $game_id, $bet_amount, $payout, $entry_id,  $win=0, $transaction_reason = null, $payout_reason = null , $income=null, $provider_trans_id=null, $round_id=1) {
		$data = [
					"token_id" => $token_id,
					"game_id" => $game_id,
					"round_id" => $round_id,
					"bet_amount" => $bet_amount,
					"provider_trans_id" => $provider_trans_id,
					"pay_amount" => $payout,
					"income" => $income,
					"entry_id" => $entry_id,
					"win" => $win,
					"transaction_reason" => $transaction_reason,
					"payout_reason" => $payout_reason
				];
		$data_saved = DB::table('game_transactions')->insertGetId($data);
		return $data_saved;
	}

	public static function saveGame_trans_ext($trans_id, $transaction_detail) {
		$data = [
					"game_trans_id" => $trans_id,
					"transaction_detail" => $transaction_detail
				];
		$transaction_saved = DB::table('game_transaction_ext')->insertGetId($data);
		return $transaction_saved;
	}


	public static function save_player($client_id, $client_player_id, $username,  $email, $display_name, $lang='en', $currency='USD') {
		$data = [
					"client_id" => $client_id,
					"client_player_id" => $client_player_id,
					"username" => $username,
					"email" => $email,
					"display_name" => $display_name
				];
		return DB::table('players')->insertGetId($data);
	}
	public static function checkPlayerExist($client_id, $client_player_id, $username,  $email, $display_name,$token,$player_ip_address=false){
		$player = DB::table('players')
					->where('client_id',$client_id)
					->where('client_player_id',$client_player_id)
					// ->where('username',$username)
					->first();
		if($player){
			return Helper::createPlayerSessionToken($player->player_id,$token,$player_ip_address);
		}
		else{
			$player_id=Helper::save_player($client_id,$client_player_id,$username,$email,$display_name);
			return Helper::createPlayerSessionToken($player_id,$token,$player_ip_address);
		}
	}
	public static function createPlayerSessionToken($player_id,$token,$player_ip_address){
		$player_session_token = array(
			"player_id" => $player_id,
			"player_token" => $token,
			"player_ip_address" => $player_ip_address,
			"status_id" => 1
		);
		DB::table('player_session_tokens')->insert($player_session_token);
		return $token;
	}
	public static function savePLayerGameRound($game_code,$player_token,$sub_provider_name){
		$sub_provider_id = DB::table("sub_providers")->where("sub_provider_name",$sub_provider_name)->first();
		Helper::saveLog('SAVEPLAYERGAME(ICG)', 12, json_encode($sub_provider_id), $sub_provider_name);
		$game = DB::table("games")->where("game_code",$game_code)->where("sub_provider_id",$sub_provider_id->sub_provider_id)->first();
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
					$trans_data["pay_amount"] = 0;
					$trans_data["entry_id"] = 1;
					$trans_data["income"] = 0;
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
					$trans_data["provider_trans_id"] = $request_data["transid"];
					$trans_data["bet_amount"] = 0;
					$trans_data["win"] = 0;
					$trans_data["pay_amount"] = $request_data["amount"];
					$trans_data["entry_id"] = 2;
					$trans_data["payout_reason"] = "Refund of this transaction ID: ".$request_data["transid"]."of GameRound ".$request_data["roundid"];
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
				->first();
		return $game;
	}
	public static function checkGameTransaction($provider_transaction_id,$round_id=false,$type=false){
		DB::enableQueryLog();
		if($type&&$round_id){
			$game = DB::table('game_transaction_ext')
				->where('provider_trans_id',$provider_transaction_id)
				->where('round_id',$round_id)
				->where('game_transaction_type',$type)
				->first();
		}
		else{
			$game = DB::table('game_transaction_ext')
				->where('provider_trans_id',$provider_transaction_id)
				->first();
		}
		Helper::saveLog('TIMEcheckGameTransaction(EVG)', 189, json_encode(DB::getQueryLog()), "DB TIME");
		return $game ? true :false;
	}
	public static function getBalance($client_details){
            if($client_details){
                $client = new Client([
                    'headers' => [ 
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$client_details->client_access_token
                    ]
                ]);
                
                $guzzle_response = $client->post($client_details->player_details_url,
                    ['body' => json_encode(
                            [
                                "access_token" => $client_details->client_access_token,
                                "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
                                "type" => "playerdetailsrequest",
                                "datesent" => "",
                                "gameid" => "",
                                "clientid" => $client_details->client_id,
                                "playerdetailsrequest" => [
                                	"client_player_id" => $client_details->client_player_id,
                                    "token" => $client_details->player_token,
                                    "gamelaunch" => "false",
                                    "refreshtoken" => "false"
                                ]]
                    )]
                );
                $client_response = json_decode($guzzle_response->getBody()->getContents());
                return $client_response->playerdetailsresponse->balance;
        }
	}
	public static function createICGGameTransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response,$game_transaction_type){
		$gametransactionext = array(
			"provider_trans_id" => $provider_request["transactionId"],
			"game_trans_id" => $gametransaction_id,
			"round_id" =>array_key_exists("roundId",$provider_request)?$provider_request["roundId"]:0,
			"amount" =>round($provider_request["amount"]/100,2),
			"game_transaction_type"=>$game_transaction_type,
			"provider_request" =>json_encode($provider_request),
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gamestransaction_ext_ID;
	}
	public static function updateICGGameTransactionExt($gametransextid,$mw_request,$mw_response,$client_response){
		$gametransactionext = array(
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		DB::table('game_transaction_ext')->where("game_trans_ext_id",$gametransextid)->update($gametransactionext);
	}
	public static function createBNGGameTransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response,$game_transaction_type){
		$gametransactionext = array(
			"provider_trans_id" => $provider_request["uid"],
			"game_trans_id" => $gametransaction_id,
			"round_id" =>$provider_request["args"]["round_id"],
			"amount" =>$game_transaction_type==1?round($provider_request["args"]["bet"],2):round($provider_request["args"]["win"],2),
			"game_transaction_type"=>$game_transaction_type,
			"provider_request" =>json_encode($provider_request),
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gamestransaction_ext_ID;
	}
	public static function updateBNGGameTransactionExt($gametransextid,$mw_request,$mw_response,$client_response){
		$gametransactionext = array(
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		DB::table('game_transaction_ext')->where("game_trans_ext_id",$gametransextid)->update($gametransactionext);
	}
	public static function createGameTransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response,$game_transaction_type){
		$gametransactionext = array(
			"game_trans_id" => $gametransaction_id,
			"round_id" =>$provider_request->gameId,
			"amount" =>$provider_request->amount,
			"game_transaction_type"=>$game_transaction_type,
			"provider_request" =>json_encode($provider_request->getContent()),
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		if($provider_request->has("id")){
			$gametransactionext["provider_trans_id"] = $provider_request->id;
		}
		else{
			$gametransactionext["provider_trans_id"] = 0;
		}
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gamestransaction_ext_ID;
	}
	public static function updateGameTransactionExt($gametransextid,$mw_request,$mw_response,$client_response){
		$gametransactionext = array(
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		DB::table('game_transaction_ext')->where("game_trans_ext_id",$gametransextid)->update($gametransactionext);
	}

	public static function createMannaGameTransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response,$game_transaction_type){
		$gametransactionext = array(
			"game_trans_id" => $gametransaction_id,
			"provider_trans_id" => $provider_request['transaction_id'],
			"round_id" =>$provider_request['round_id'],
			"amount" =>$provider_request['amount'],
			"game_transaction_type"=>$game_transaction_type,
			"provider_request" =>json_encode($provider_request),
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
	
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gametransactionext;
	}

	public static function createSolidGameTransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response,$game_transaction_type){
		$gametransactionext = array(
			"game_trans_id" => $gametransaction_id,
			"provider_trans_id" => $provider_request['transid'],
			"round_id" =>$provider_request['roundid'],
			"amount" =>$provider_request['amount'],
			"game_transaction_type"=>$game_transaction_type,
			"provider_request" =>json_encode($provider_request),
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
	
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gametransactionext;
	}

	public static function createVivoGameTransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response,$game_transaction_type){
		$gametransactionext = array(
			"game_trans_id" => $gametransaction_id,
			"provider_trans_id" => $provider_request['TransactionID'],
			"round_id" =>$provider_request['roundId'],
			"amount" =>$provider_request['Amount'],
			"game_transaction_type"=>$game_transaction_type,
			"provider_request" =>json_encode($provider_request),
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>addslashes($mw_response),
			"client_response" =>json_encode($client_response),
		);
	
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gametransactionext;
	}

	public static function createOryxGameTransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response,$game_transaction_type){
		$gametransactionext = array(
			"game_trans_id" => $gametransaction_id,
			"provider_trans_id" => $provider_request['transactionId'],
			"round_id" =>$provider_request['roundId'],
			"amount" =>$provider_request['Amount'],
			"game_transaction_type"=>$game_transaction_type,
			"provider_request" =>json_encode($provider_request),
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
	
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gametransactionext;
	}

	public static function updateGameTransaction($existingdata,$request_data,$type){
		DB::enableQueryLog();
		switch ($type) {
			case "debit":
					$trans_data["win"] = 0;
					$trans_data["pay_amount"] = 0;
					$trans_data["income"]=$existingdata->bet_amount-$request_data["amount"];
					$trans_data["entry_id"] = 1;
				break;
			case "credit":
					$trans_data["win"] = $request_data["win"];
					$trans_data["pay_amount"] = abs($request_data["amount"]);
					$trans_data["income"]=$existingdata->bet_amount-$request_data["amount"];
					$trans_data["entry_id"] = 2;
					$trans_data["payout_reason"] = $request_data["payout_reason"];
				break;
			case "refund":
					$trans_data["win"] = 4;
					$trans_data["pay_amount"] = $request_data["amount"];
					$trans_data["entry_id"] = 2;
					$trans_data["income"]= $existingdata->bet_amount-$request_data["amount"];
					$trans_data["payout_reason"] = "Refund of this transaction ID: ".$request_data["transid"]."of GameRound ".$request_data["roundid"];
				break;
			case "fail":
				$trans_data["win"] = 2;
				$trans_data["pay_amount"] = $request_data["amount"];
				$trans_data["entry_id"] = 1;
				$trans_data["income"]= 0;
				$trans_data["payout_reason"] = "Fail  transaction ID: ".$request_data["transid"]."of GameRound ".$request_data["roundid"] .":Insuffecient Balance";
			break;
			default:
		}
		/*var_dump($trans_data); die();*/
		Helper::saveLog('TIMEupdateGameTransaction(EVG)', 189, json_encode(DB::getQueryLog()), "DB TIME");
		return DB::table('game_transactions')->where("game_trans_id",$existingdata->game_trans_id)->update($trans_data);
	}
	/**
	 * @token = player_token
	 * @response_data = token | player_id |
	 */
	public static function getGameCode($token,$provider_id){
		$game_code = DB::table('seamless_request_logs')
			->where('response_data', $token)
			->where('provider_id',$provider_id)
	    	->first();
	    return $game_code ? $game_code : 'false';
	}
	/**
	 * @request_data = game_code
	 * @response_data = token | player_id |
	 */
	public static function saveLogCode($method, $provider_id = 0, $request_data, $response_data) {
		$data = [
			"method_name" => $method,
			"provider_id" => $provider_id,
			"request_data" => $request_data,
			"response_data" => $response_data
		];
		DB::table('seamless_request_logs')->insert($data);
	}

	
}