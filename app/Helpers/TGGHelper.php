<?php
namespace App\Helpers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Carbon\Carbon;
use DB;

class TGGHelper{
	/* ISOLATION METHODDS FOR TESTING PERFORMANCE OPTIMAZTION */
	/* PROVIDER HELPERS */

	public static function playerDetailsCall($client_details, $refreshtoken=false, $type=1){
		// if($type == 1){
		// 	$client_details = TGGHelper::getClientDetails('token', $player_token);
		// 	// return 1;
  //       }elseif($type == 2){
		// 	$client_details = TGGHelper::getClientDetails('token', $player_token, 2);
		// 	// return 2;
		// }
		
		if($client_details){
			$client = new Client([
			    'headers' => [ 
			    	'Content-Type' => 'application/json',
			    	'Authorization' => 'Bearer '.$client_details->client_access_token
			    ]
			]);
			$datatosend = [
				"access_token" => $client_details->client_access_token,
				"hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
				"type" => "playerdetailsrequest",
				"datesent" => Helper::datesent(),
                "gameid" => "",
				"clientid" => $client_details->client_id,
				"playerdetailsrequest" => [
					"player_username"=>$client_details->username,
					"client_player_id" => $client_details->client_player_id,
					"token" => $client_details->player_token,
					"gamelaunch" => true,
					"refreshtoken" => $refreshtoken
				]
			];
			
			// Filter Player If Disabled
			// $player= DB::table('players')->where('client_id', $client_details->client_id)
			// 		->where('player_id', $client_details->player_id)->first();
			// if(isset($player->player_status)){
			// 	if($player != '' || $player != null){
			// 		if($player->player_status == 3){
			// 		Helper::saveLog('ALDEBUG PLAYER BLOCKED = '.$player->player_status,  999, json_encode($datatosend), $datatosend);
			// 		 return 'false';
			// 		}
			// 	}
			// }

			// return $datatosend;
			try{	
				$guzzle_response = $client->post($client_details->player_details_url,
				    ['body' => json_encode($datatosend)]
				);
				$client_response = json_decode($guzzle_response->getBody()->getContents());
				Helper::saveLog('ALDEBUG REQUEST SEND = '.$client_details->player_token,  99, json_encode($client_response), $datatosend);
				if(isset($client_response->playerdetailsresponse->status->code) && $client_response->playerdetailsresponse->status->code != 200 || $client_response->playerdetailsresponse->status->code != '200'){
					if($refreshtoken == true){
						if(isset($client_response->playerdetailsresponse->refreshtoken) &&
					    $client_response->playerdetailsresponse->refreshtoken != false || 
					    $client_response->playerdetailsresponse->refreshtoken != 'false'){
							DB::table('player_session_tokens')->insert(
	                        array('player_id' => $client_details->player_id, 
	                        	  'player_token' =>  $client_response->playerdetailsresponse->refreshtoken, 
	                        	  'status_id' => '1')
	                        );
						}
					}
					return 'false';
				}else{
					if($refreshtoken == true){
						if(isset($client_response->playerdetailsresponse->refreshtoken) &&
					    $client_response->playerdetailsresponse->refreshtoken != false || 
					    $client_response->playerdetailsresponse->refreshtoken != 'false'){
							DB::table('player_session_tokens')->insert(
		                        array('player_id' => $client_details->player_id, 
		                        	  'player_token' =>  $client_response->playerdetailsresponse->refreshtoken, 
		                        	  'status_id' => '1')
		                    );
						}
					}
			 		return $client_response;
				}

            }catch (\Exception $e){
               Helper::saveLog('ALDEBUG client_player_id = '.$client_details->client_player_id,  99, json_encode($datatosend), $e->getMessage());
               return 'false';
            }
		}else{
			return 'false';
		}
	}


	public static function getClientDetails($type = "", $value = "", $gg=1, $providerfilter='all') {
	    if ($type == 'token') {
		 	$where = 'where pst.player_token = "'.$value.'"';
		}
		if($providerfilter=='fachai'){
		    if ($type == 'player_id') {
				$where = 'where '.$type.' = "'.$value.'" AND pst.status_id = 1 ORDER BY pst.token_id desc';
			}
		}else{
	        if ($type == 'player_id') {
			   $where = 'where '.$type.' = "'.$value.'"';
			}
		}
		if ($type == 'username') {
		 	$where = 'where p.username = "'.$value.'"';
		}
		if ($type == 'token_id') {
			$where = 'where pst.token_id = "'.$value.'"';
		}

		$filter = 'order by token_id desc LIMIT 1';
		
		$query = DB::select('select `p`.`client_id`, `p`.`player_id`, `p`.`email`, `p`.`client_player_id`,`p`.`language`, `p`.`currency`, `p`.`test_player`, `p`.`username`,`p`.`created_at`,`pst`.`token_id`,`pst`.`player_token`,`c`.`client_url`,`c`.`default_currency`,`pst`.`status_id`,`p`.`display_name`,`op`.`client_api_key`,`op`.`client_code`,`op`.`client_access_token`,`ce`.`player_details_url`,`ce`.`fund_transfer_url`,`p`.`created_at` from player_session_tokens pst inner join players as p using(player_id) inner join clients as c using (client_id) inner join client_endpoints as ce using (client_id) inner join operator as op using (operator_id) '.$where.' '.$filter.'');

		 $client_details = count($query);
		 return $client_details > 0 ? $query[0] : null;
	}


	public static function checkTransactionExist($identifier, $transaction_type){
		$query = DB::select('select `game_transaction_type` from game_transaction_ext where `provider_trans_id`  = "'.$identifier.'" AND `game_transaction_type` = "'.$transaction_type.'" LIMIT 1');
    	$data = count($query);
		return $data > 0 ? $query[0] : 'false';
	}


	public static  function findGameTransaction($identifier, $type, $entry_type='') {

    	if ($type == 'transaction_id') {
		 	$where = 'where gt.provider_trans_id = "'.$identifier.'" AND gt.entry_id = '.$entry_type.'';
		}
		if ($type == 'game_transaction') {
		 	$where = 'where gt.game_trans_id = "'.$identifier.'"';
		}
		if ($type == 'round_id') {
			$where = 'where gt.round_id = "'.$identifier.'" AND gt.entry_id = '.$entry_type.'';
		}
	 	
	 	$filter = 'LIMIT 1';
    	$query = DB::select('select *, (select transaction_detail from game_transaction_ext where game_trans_id = gt.game_trans_id order by game_trans_id limit 1) as transaction_detail from game_transactions gt '.$where.' '.$filter.'');
    	$client_details = count($query);
		return $client_details > 0 ? $query[0] : 'false';
    }

	public static function findGameTransID($game_trans_id){
		$query = DB::select('select `game_trans_id`,`token_id`, `provider_trans_id`, `round_id`, `bet_amount`, `win`, `pay_amount`, `income`, `entry_id` from game_transactions where `game_trans_id`  = '.$game_trans_id.' ');
    	$data = count($query);
		return $data > 0 ? $query[0] : 'false';
	}


	public static function findGameExt($provider_transaction_id, $game_transaction_type, $type) {
		$transaction_db = DB::table('game_transaction_ext as gte');
        if ($type == 'transaction_id') {
			$transaction_db->where([
		 		["gte.provider_trans_id", "=", $provider_transaction_id],
		 		["gte.game_transaction_type", "=", $game_transaction_type],
		 	]);
		}
		if ($type == 'round_id') {
			$transaction_db->where([
		 		["gte.round_id", "=", $provider_transaction_id],
		 		["gte.game_transaction_type", "=", $game_transaction_type],
		 	]);
		}  
		$result= $transaction_db->first();
		return $result ? $result : 'false';
	}

	public static function findGameDetails($type, $provider_id, $identification) {
		if ($type == "game_code") {
			$details = "where g.provider_id = ".$provider_id." and g.game_code = ".$identification." limit 1";
		}
		$game_details = DB::select('select g.game_name, g.game_code, g.game_id from games g left join providers as p using (provider_id) '.$details.' ');
		
	 	return $game_details ? $game_details : "false";
	}


	/**
	 * Create Game Extension Logs bet/Win/Refund
	 * @param [int] $[gametransaction_id] [<ID of the game transaction>]
	 * @param [json array] $[provider_request] [<Incoming Call>]
	 * @param [json array] $[mw_request] [<Outgoing Call>]
	 * @param [json array] $[mw_response] [<Incoming Response Call>]
	 * @param [json array] $[client_response] [<Incoming Response Call>]
	 * 
	 */
	public static function creteTGGtransaction($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response, $transaction_detail,$game_transaction_type, $amount=null, $provider_trans_id=null, $round_id=null){
		$gametransactionext = array(
			"game_trans_id" => $gametransaction_id,
			"provider_trans_id" => $provider_trans_id,
			"round_id" => $round_id,
			"amount" => $amount,
			"game_transaction_type"=>$game_transaction_type,
			"provider_request" => json_encode($provider_request),
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
			"transaction_detail" =>json_encode($transaction_detail),
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gametransactionext;
	}


	public static function saveLog($method, $provider_id = 0, $request_data, $response_data) {
		$data = [
				"method_name" => $method,
				"provider_id" => $provider_id,
				"request_data" => json_encode(json_decode($request_data)),
				"response_data" => json_encode($response_data)
			];
		return DB::table('seamless_request_logs')->insertGetId($data);
		// return DB::table('debug')->insertGetId($data);
	}


	/**
	 * Find bet and update to win 
	 * @param [int] $[round_id] [<ID of the game transaction>]
	 * @param [int] $[pay_amount] [<amount to change>]
	 * @param [int] $[income] [<bet - payout>]
	 * @param [int] $[win] [<0 Lost, 1 win, 3 draw, 4 refund, 5 processing>]
	 * @param [int] $[entry_id] [<1 bet, 2 win>]
	 * 
	 */
	public static function updateBetTransaction($round_id, $pay_amount, $income, $win, $entry_id) {
		$update = DB::table('game_transactions')
			 // ->where('round_id', $round_id)
			 ->where('game_trans_id', $round_id) 
			 ->update(['pay_amount' => $pay_amount, 
				   'income' => $income, 
				   'win' => $win, 
				   'entry_id' => $entry_id,
				   'transaction_reason' => TGGHelper::updateReason($win),
			 ]);
	 return ($update ? true : false);
 	}

	/**
	 * Find bet and update to win 
	* @param [int] $[win] [< Win TYPE>][<0 Lost, 1 win, 3 draw, 4 refund, 5 processing>]
	* 
	*/
	public static function updateReason($win) {
		$win_type = [
		"1" => 'Transaction updated to win',
		"2" => 'Transaction updated to bet',
		"3" => 'Transaction updated to Draw',
		"4" => 'Transaction updated to Refund',
		"5" => 'Transaction updated to Processing',
		];
		if(array_key_exists($win, $win_type)){
			return $win_type[$win];
		}else{
			return 'Transaction Was Updated!';
		}
	}

	public static  function createGameTransExt($game_trans_id, $provider_trans_id, $round_id, $amount, $game_type, $provider_request, $mw_response, $mw_request, $client_response, $transaction_detail){
		$gametransactionext = array(
			"game_trans_id" => $game_trans_id,
			"provider_trans_id" => $provider_trans_id,
			"round_id" => $round_id,
			"amount" => $amount,
			"game_transaction_type"=>$game_type,
			"provider_request" => json_encode($provider_request),
			"mw_response" =>json_encode($mw_response),
			"mw_request"=>json_encode($mw_request),
			"client_response" =>json_encode($client_response),
			"transaction_detail" =>json_encode($transaction_detail)
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gamestransaction_ext_ID;
	}

	public static  function updateGameTransactionExt($gametransextid,$mw_request,$mw_response,$client_response){
		$gametransactionext = array(
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		DB::table('game_transaction_ext')->where("game_trans_ext_id",$gametransextid)->update($gametransactionext);
	}

}

?>