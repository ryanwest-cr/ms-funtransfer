<?php
namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Helpers\Helper;
use App\Helpers\AWSHelper;
use App\Helpers\DESHelper;
use DB;

// use function GuzzleHttp\json_decode;

/**
 * @author's note : if you changed something please add comments thanks - RiAN
 * 
 */
class ProviderHelper{
	
	/**
	 * EVOPLAY ONLY -RiAN
	 * @param $args [array of data], 
	 * @param $system_key [system key], 
	 * 
	 */
	public static function getSignature(array $args, $system_key)
    {
        $md5 = array();
	    $args = array_filter($args, function($val){ return !($val === null || (is_array($val) && !$val));});
	    foreach ($args as $required_arg) {
	        $arg = $required_arg;
	        if (is_array($arg)) {
	            $md5[] = implode(':', array_filter($arg, function($val){ return !($val === null || (is_array($val) && !$val));}));
	        } else {
	            $md5[] = $arg;
	        }
	    };
	    $md5[] = $system_key;
	    $md5_str = implode('*', $md5);
	    $md5 = md5($md5_str);

	    return $md5;
    }

    /**
     * GLOBAL 
     * @param  [amount] $username [int]
     * @return [float] [float two decimal number]
     */
    public static function amountToFloat($amount){
    	$float = floatval(number_format((float)$amount, 2, '.', ''));
    	return $float;
    }

    /**
     * GLOBAL 
     * JSON PASS AS PARAMS OR IN THE BODY RAW TEST -RiAN 
     * SAMPLE = [{\"mtcode\":\"rel-win-3497138713:cq9\",\"amount\":1004,\"eventtime\":\"2020-08-06T02:48:41-04:00\",\"validbet\":0}]
     * @param  [string/json] $data
     * @param  [int] $depth = 0 return single obj, 1 multiple return obj
     * @return [obj
     */
    public static function rawToObj($data, $multiple=false){
    	$array = (array)$data;
	    $newStr = str_replace("\\", '', $array[0]);
	    $newStr2 = str_replace(';', '', $newStr);
		$string_to_obj = json_decode($newStr2);
		if($multiple == false){
			return $string_to_obj[0];
		}else{
			return $string_to_obj;
		}
    }

    public static function checkIfHasUnderscore($string){
    	// if(preg_match('/^[a-z]+_[a-z]+$/i', $string)){
    	if(strpos($string, '_')){
		   return true;
		}else{
		   return false;
		}
	}

    /**
     * GLOBAL 
     * [explodeUsername description]
     * @author 's note sample = TG_Al98, 98 is the player id on MW Database
     * @param  [type] $username [string]
     * @return [int] [Database Player ID]
     */
    public static function explodeUsername($explode_point, $username){
    	$prefixed_username = explode($explode_point, $username);
    	return $prefixed_username[1];
    }

    /**
	 * GLOBAL
	 * Client Info
	 * @return [Object]
	 * @param $[type] [<token, player_id, site_url, username>]
	 * @param $[value] [<value to be searched>]
	 * 
	 */
    public static function getClientDetails($type = "", $value = "", $gg=1, $providerfilter='all') {
    	// DB::enableQueryLog();
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
		if($providerfilter=='fachai'){
		 	$filter = 'LIMIT 1';
		}else{
		    // $result= $query->latest('token_id')->first();
		    $filter = 'order by token_id desc LIMIT 1';
		}

		$query = DB::select('select `p`.`client_id`, `p`.`player_id`, `p`.`email`, `p`.`client_player_id`,`p`.`language`, `p`.`currency`, `p`.`test_player`, `p`.`username`,`p`.`created_at`,`pst`.`token_id`,`pst`.`player_token`,`pst`.`balance`,`c`.`client_url`,`c`.`default_currency`,`pst`.`status_id`,`p`.`display_name`,`op`.`client_api_key`,`op`.`client_code`,`op`.`client_access_token`,`ce`.`player_details_url`,`ce`.`fund_transfer_url`,`p`.`created_at` from player_session_tokens pst inner join players as p using(player_id) inner join clients as c using (client_id) inner join client_endpoints as ce using (client_id) inner join operator as op using (operator_id) '.$where.' '.$filter.'');

		 $client_details = count($query);
		 // Helper::saveLog('GET CLIENT LOG', 999, json_encode(DB::getQueryLog()), "TIME GET CLIENT");
		 return $client_details > 0 ? $query[0] : null;
	}


	/**
	 * GLOBAL
	 * @param $[sub_provider_id], $[game_code], 
	 * 
	 */
	public static function getSubGameDetails($sub_provider_id, $game_code){
		$query = DB::select('select * from games where sub_provider_id = "'.$sub_provider_id.'" and game_code = "'.$game_code.'"');
		$game_details = count($query);
		return $game_details > 0 ? $query[0] : false;
	}
	// public static function createGameRestriction(){
	// 	$query = DB::select("insert into `game_transactions` (`token_id`, `game_id`, `round_id`, `bet_amount`, `provider_trans_id`, `pay_amount`, `income`, `entry_id`, `win`, `transaction_reason`, `payout_reason`) values ($token_id, $game_id, '$round_id', $bet_amount, '$provider_trans_id', $payout, '$income', $entry_id, $win, '$transaction_reason', '$payout_reason')");
	// 	return DB::connection()->getPdo()->lastInsertId();
	// }

	// /**
	//  * GLOBAL
	//  * Check Player Session Status
	//  * @param [Object] $[client_details]
	//  * 
	//  */
	// public static function checkPlayerSessionStatus($client_details){
	// 	$query = DB::select('select player_session_tokens  '.$where.' '.$filter.'');
	// 	$client_details = count($query);
	// 	return $client_details > 0 ? $query[0] : 'false';
	// }


	/**
	 * GLOBAL
	 * Client Player Details API Call
	 * @return [Object]
	 * @param $[player_token] [<players token>]
	 * @param $[refreshtoken] [<Default False, True token will be requested>]
	 * 
	 */
	public static function playerDetailsCall($player_token, $refreshtoken=false, $type=1){
		// DB::enableQueryLog();
		if($type == 1){
			$client_details = ProviderHelper::getClientDetails('token', $player_token);
			// return 1;
        }elseif($type == 2){
			$client_details = ProviderHelper::getClientDetails('token', $player_token, 2);
			// return 2;
		}
		// dd($client_details);
		if($client_details){
			$client = new Client([
			    'headers' => [ 
			    	'Content-Type' => 'application/json',
			    	'Authorization' => 'Bearer '.$client_details->client_access_token
			    ]
			]);
			$datatosend = ["access_token" => $client_details->client_access_token,
				"hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
				"type" => "playerdetailsrequest",
				"datesent" => Helper::datesent(),
                "gameid" => "",
				"clientid" => $client_details->client_id,
				"playerdetailsrequest" => [
					"player_username"=>$client_details->username,
					"client_player_id" => $client_details->client_player_id,
					"token" => $player_token,
					"gamelaunch" => true,
					"refreshtoken" => $refreshtoken
				]
			];

			// Filter Player If Disabled
			$player= DB::table('players')->where('client_id', $client_details->client_id)
					->where('player_id', $client_details->player_id)->first();
			if(isset($player->player_status)){
				if($player != '' || $player != null){
					if($player->player_status == 3){
					Helper::saveLog('ALDEBUG PLAYER BLOCKED = '.$player->player_status,  999, json_encode($datatosend), $datatosend);
					 return 'false';
					}
				}
			}

			// return json_encode($datatosend);
			try{	
				$guzzle_response = $client->post($client_details->player_details_url,
				    ['body' => json_encode($datatosend)]
				);
				$client_response = json_decode($guzzle_response->getBody()->getContents());
				Helper::saveLog('ALDEBUG REQUEST SEND = '.$player_token,  99, json_encode($client_response), $datatosend);
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
					// Helper::saveLog('PLAYER DETAILS LOG', 999, json_encode(DB::getQueryLog()), "TIME PLAYERDETAILS");
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
					// Helper::saveLog('PLAYER DETAILS LOG', 999, json_encode(DB::getQueryLog()), "TIME PLAYERDETAILS");
			 		return $client_response;
				}

            }catch (\Exception $e){
               // Helper::saveLog('PLAYER DETAILS LOG', 999, json_encode(DB::getQueryLog()), "TIME PLAYERDETAILS");
               Helper::saveLog('ALDEBUG client_player_id = '.$client_details->client_player_id,  99, json_encode($datatosend), $e->getMessage());
               return 'false';
            }
		}else{
			// Helper::saveLog('PLAYER DETAILS LOG', 999, json_encode(DB::getQueryLog()), "TIME PLAYERDETAILS");
			// Helper::saveLog('ALDEBUG Token Not Found = '.$player_token,  99, json_encode($datatosend), 'TOKEN NOT FOUND');
			return 'false';
		}
	}



	public static function clientPlayerDetailsCall($client_details, $refreshtoken=false){
        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$client_details->client_access_token
            ]
        ]);
        $datatosend = ["access_token" => $client_details->client_access_token,
            "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
            "type" => "playerdetailsrequest",
            "datesent" => Helper::datesent(),
            "clientid" => $client_details->client_id,
            "playerdetailsrequest" => [
                "player_username"=>$client_details->username,
                "client_player_id" => $client_details->client_player_id,
                "token" => $client_details->player_token,
                "gamelaunch" => true,
                "refreshtoken" => $refreshtoken
            ]
        ];
        try{    
            $guzzle_response = $client->post($client_details->player_details_url,
                ['body' => json_encode($datatosend)]
            );
            $client_response = json_decode($guzzle_response->getBody()->getContents());
            return $client_response;
        }catch (\Exception $e){
           Helper::saveLog('ALDEBUG client_player_id = '.$client_details->client_player_id,  99, json_encode($datatosend), $e->getMessage());
           return 'false';
        }
    }

	/**
	 * GLOBAL
     * Find Game Transaction
     * @param [string] $[identifier] [<ID of the game transaction>]
     * @param [int] $[type] [<transaction_id, round_id, refundbet>]
     * @param [int] $[entry_type] [<1 bet/debit, 2 win/credit>]
     * 
     */
    public  static function findGameTransaction($identifier, $type, $entry_type='') {
    	// DB::enableQueryLog();
        $transaction_db = DB::table('game_transactions as gt')
                        ->select('gt.*', 'gte.transaction_detail')
                        ->leftJoin("game_transaction_ext AS gte", "gte.game_trans_id", "=", "gt.game_trans_id");
                       
        if ($type == 'transaction_id') {
            $transaction_db->where([
                ["gt.provider_trans_id", "=", $identifier],
                ["gt.entry_id", "=", $entry_type],
            ]);
        }
        if ($type == 'game_transaction') {
            $transaction_db->where([
                ["gt.game_trans_id", "=", $identifier],
            ]);
        }
        if ($type == 'round_id') {
            $transaction_db->where([
                ["gt.round_id", "=", $identifier],
                ["gt.entry_id", "=", $entry_type],
            ]);
        }
        if ($type == 'refundbet') { // TEST
            $transaction_db->where([
                ["gt.round_id", "=", $identifier],
                ["gt.entry_id", "=", $entry_type],
            ]);
        }
        $result= $transaction_db
            ->first();
        // Helper::saveLog('Find Game Transaction', 999, json_encode(DB::getQueryLog()), "TIME Find Game Transaction");
        return $result ? $result : 'false';
	}
	
	public static  function findGameTransaction_raw($identifier, $type, $entry_type='') {

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

    /**
     * GLOBAL
	 * Find Game Transaction Ext
	 * @param [string] $[provider_transaction_id] [<provider transaction id>]
	 * @param [int] $[game_transaction_type] [<1 bet, 2 win, 3 refund>]
	 * @param [string] $[type] [<transaction_id, round_id>]
	 * 
	 */
	public  static function findGameExt($provider_identifier, $game_transaction_type, $type) {
		// DB::enableQueryLog();
		$transaction_db = DB::table('game_transaction_ext as gte');
        if ($type == 'transaction_id') {
			$transaction_db->where([
		 		["gte.provider_trans_id", "=", $provider_identifier],
		 		["gte.game_transaction_type", "=", $game_transaction_type],
		 		["gte.transaction_detail", "!=", '"FAILED"'], // TEST OVER ALL
		 	]);
		}
		if ($type == 'round_id') {
			$transaction_db->where([
		 		["gte.round_id", "=", $provider_identifier],
		 		["gte.game_transaction_type", "=", $game_transaction_type],
		 		["gte.transaction_detail", "!=", '"FAILED"'], // TEST OVER ALL
		 	]);
		}  
		if ($type == 'game_transaction_ext_id') {
			$transaction_db->where([
				["gte.game_transaction_type", "=", $game_transaction_type],
		 		["gte.game_trans_ext_id", "=", $provider_identifier],
		 	]);
		} 
		if ($type == 'game_trans_id') {
			$transaction_db->where([
				["gte.game_transaction_type", "=", $game_transaction_type],
		 		["gte.game_trans_id", "=", $provider_identifier],
		 	]);
		} 
		$result = $transaction_db->latest()->first(); // Added Latest (CQ9) 08-12-20 - Al
		// Helper::saveLog('Find Game Extension', 999, json_encode(DB::getQueryLog()), "TIME Find Game Extension");
		return $result ? $result : 'false';
	}


	public  static function findGameExt_raw($provider_identifier, $game_transaction_type, $type)
	{

		if ($type == 'transaction_id') {
			$where = 'where gte.provider_trans_id = "' . $provider_identifier . '" AND gte.game_transaction_type = ' . $game_transaction_type . ' AND gte.transaction_detail != "FAILED"';
		}
		if ($type == 'round_id') {
			$where = 'where gte.round_id = "' . $provider_identifier . '" AND gte.game_transaction_type = ' . $game_transaction_type . ' AND gte.transaction_detail != "FAILED"';
		}
		if ($type == 'game_transaction_ext_id') {
			$where = 'where gte.provider_trans_id = "' . $provider_identifier . '"';
		}
		if ($type == 'game_trans_id') {
			$where = 'where gte.game_trans_id = "' . $provider_identifier . '"';
		}

		$filter = 'LIMIT 1';

		$query = DB::select('select * from game_transaction_ext as gte ' . $where . ' ' . $filter . '');
		$data = count($query);
		return $data > 0 ? $query[0] : 'false';
	}

	
	public static function findAllFailedGameExt($provider_identifier, $type) {
		$transaction_db = DB::table('game_transaction_ext as gte');
        if ($type == 'transaction_id') {
			$transaction_db->where([
		 		["gte.provider_trans_id", "=", $provider_identifier],
		 		["gte.transaction_detail", "=", '"FAILED"'] // Intentionally qouted for DB QUERY
		 	]);
		}
		if ($type == 'round_id') {
			$transaction_db->where([
		 		["gte.round_id", "=", $provider_identifier],
		 		["gte.transaction_detail", "=", '"FAILED"'] // Intentionally qouted for DB QUERY
		 	]);
		}  
		$result = $transaction_db->latest()->get();
		return $result ? $result : 'false';
	}


	/**
	 * GLOBAL
	 * Find bet and update to win 
	 * @param [int] $[round_id] [<ID of the game transaction>]
	 * @param [int] $[pay_amount] [<amount to change>]
	 * @param [int] $[income] [<bet - payout>]
	 * @param [int] $[win] [<0 Lost, 1 win, 3 draw, 4 refund, 5 processing>]
	 * @param [int] $[entry_id] [<1 bet, 2 win>]
	 * 
	 */
	public  static function updateBetTransaction($round_id, $pay_amount, $income, $win, $entry_id) {
		// DB::enableQueryLog();
   	    $update = DB::table('game_transactions')
                ->where('game_trans_id', $round_id)
                // ->where('round_id', $round_id)
                ->update(['pay_amount' => $pay_amount, 
	        		  'income' => $income, 
	        		  'win' => $win, 
	        		  'entry_id' => $entry_id,
	        		  'transaction_reason' => ProviderHelper::updateReason($win),
	    		]);
	    // Helper::saveLog('updateBetTransaction', 999, json_encode(DB::getQueryLog()), "TIME updateBetTransaction");
		return ($update ? true : false);
	}

	public static function updateBetTransaction_raw($round_id, $pay_amount, $income, $win, $entry_id)
	{
		$reason = ProviderHelper::updateReason($win);
		$update = DB::select("update `game_transactions` set `pay_amount` = $pay_amount, `income` = $income, `win` = $win, `entry_id` = $entry_id, `transaction_reason` = '$reason' where `game_trans_id` = $round_id");
	}

	/**
	 * GLOBAL
	 * Test Only -RiAN
	 * @param [int] $[win] [<0 Lost, 1 win, 3 draw, 4 refund, 5 processing>] (SUGGESTION 2 NOT ENOUGH BALANCE)
	 * @param [int] $[entry_id] [<1 bet, 2 win>]
	 * 
	 */
	public static function updateGameTransaction($identifier, $pay_amount, $income, $win, $entry_id,$type='game_trans_id',$bet_amount=0,$multi_bet=false) {
		// DB::enableQueryLog();
        $update = DB::table('game_transactions');
        if ($type == 'game_trans_id') {
            $update->where([
                ["game_trans_id", "=", $identifier],
            ]);
        }
        if ($type == 'round_id') {
            $update->where([
                ["round_id", "=", $identifier],
            ]);
        }
        if ($type == 'provider_trans_id') {
            $update->where([
                ["provider_trans_id", "=", $identifier],
            ]);
        }
        $update->update([
          'pay_amount' => $pay_amount, 
          'income' => $income, 
          'win' => $win, 
          'entry_id' => $entry_id,
          'transaction_reason' => ProviderHelper::updateReason($win),
        ]);
        if($multi_bet == true){
            $update->update(['bet_amount' => $bet_amount]);
        }
        // Helper::saveLog('updateGameTransaction', 999, json_encode(DB::getQueryLog()), "TIME updateGameTransaction");
        return ($update ? true : false);
    }


	/**
	 * GLOBAL
	 * Find game transaction and update the reason
	 * @param game_trans_id = the game_transaction_id, $win type
	 * 
	 */
	public  static function updateGameTransactionStatus($game_trans_id, $win, $reason) {
		// DB::enableQueryLog();
   	    $update = DB::table('game_transactions')
                ->where('game_trans_id', $game_trans_id)
                ->update([
        		  'win' => $win, 
        		  'transaction_reason' => ProviderHelper::updateReason($win),
        		  'payout_reason' => ProviderHelper::updateReason($reason),
	    		]);
	    // Helper::saveLog('updateGameTransactionStatus', 999, json_encode(DB::getQueryLog()), "TIME updateGameTransactionStatus");
		return ($update ? true : false);
	}

	public static function updateGameTransactionStatus_raw($game_trans_id, $win, $reason){
		$reason = ProviderHelper::updateReason($reason);
		$update = DB::select("update `game_transactions` set `win` = $win, `transaction_reason` = '$reason' where `game_trans_id` = $game_trans_id");
	}


	/**
	 * GLOBAL
	 * Find bet and update to win 
	 * @param [int] $[win] [< Win TYPE>][<0 Lost, 1 win, 3 draw, 4 refund, 5 processing>]
	 * 
	 */
	public static function updateReason($win) {
		$win_type = [
		 "1" => 'Transaction updated to Win',
		 "2" => 'Transaction updated to Bet',
		 "3" => 'Transaction updated to Draw',
		 "4" => 'Transaction updated to Refund',
		 "5" => 'Transaction updated to Processing',
		 "6" => 'Transaction FAILED - Low Balance',
		 "99" => 'Transaction FAILED - FATAL ERROR',
		];
		if(array_key_exists($win, $win_type)){
    		return $win_type[$win];
    	}else{
    		return 'Transaction Was Updated!';
    	}
	}

	public static function checkFundStatus($win) {
		$status_type = [
		 "ok" => 'success code',
		 "Ok" => 'success code',
		 "OK" => 'success code',
		 "success" => 'success code',
		 "Success" => 'success code',
		 "SUCCESS" => 'success code',
		];
		if(array_key_exists($win, $status_type)){
    		return true; // if success
    	}else{
    		return false; // if failed
    	}
	}

	/**
	 * GLOBAL
	 * Create Game Transaction
	 * 
	 */
	public static function createGameTransaction($token_id, $game_id, $bet_amount, $payout, $entry_id,  $win=0, $transaction_reason = null, $payout_reason = null , $income=null, $provider_trans_id=null, $round_id=1) {
		// DB::enableQueryLog();
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
		// Helper::saveLog('createGameTransaction', 999, json_encode(DB::getQueryLog()), "TIME createGameTransaction");
		return $data_saved;
	}

	public static function createGameTransaction_raw($token_id, $game_id, $bet_amount, $payout, $entry_id,  $win = 0, $transaction_reason = null, $payout_reason = null, $income = null, $provider_trans_id = null, $round_id = 1)
	{
		$query = DB::select("insert into `game_transactions` (`token_id`, `game_id`, `round_id`, `bet_amount`, `provider_trans_id`, `pay_amount`, `income`, `entry_id`, `win`, `transaction_reason`, `payout_reason`) values ($token_id, $game_id, '$round_id', $bet_amount, '$provider_trans_id', $payout, '$income', $entry_id, $win, '$transaction_reason', '$payout_reason')");
		return DB::connection()->getPdo()->lastInsertId();
	}

	/**
	 * GLOBAL
	 * Create Game Transaction Extension
	 * @param  $[game_type] [<1=bet,2=win,3=refund>]
	 * 
	 */
	public static function createGameTransExt($game_trans_id, $provider_trans_id, $round_id, $amount, $game_type, $provider_request, $mw_response, $mw_request, $client_response, $transaction_detail, $general_details=null){
		// DB::enableQueryLog();
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
			"transaction_detail" =>json_encode($transaction_detail),
			"general_details" =>json_encode($general_details)
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		// Helper::saveLog('createGameTransExt', 999, json_encode(DB::getQueryLog()), "TIME createGameTransExt");
		return $gamestransaction_ext_ID;
	}

	/**
	 * GLOBAL
	 * Create Game Transaction Extension
	 * @param  $[game_type] [<1=bet,2=win,3=refund>]
	 * 
	 */
	public static function createGameTransExtV2($game_trans_id, $provider_trans_id, $round_id, $amount, $game_type, $provider_request='FAILED', $mw_response='FAILED', $mw_request='FAILED', $client_response='FAILED', $transaction_detail='FAILED', $general_details=null){
		// DB::enableQueryLog();
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
			"transaction_detail" =>json_encode($transaction_detail),
			"general_details" =>json_encode($general_details)
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		// Helper::saveLog('createGameTransExtV2', 999, json_encode(DB::getQueryLog()), "TIME createGameTransExtV2");
		return $gamestransaction_ext_ID;
	}

	public static function createGameTransExtV2_raw($game_trans_id, $provider_trans_id, $round_id, $amount, $game_type, $provider_request = 'FAILED', $mw_response = 'FAILED', $mw_request = 'FAILED', $client_response = 'FAILED', $transaction_detail = 'FAILED', $general_details = null)
	{
		$provider_request = json_encode($provider_request);
		$mw_response = json_encode($mw_response);
		$mw_request = json_encode($mw_request);
		$client_response = json_encode($client_response);
		$transaction_detail = json_encode($transaction_detail);
		$general_details = json_encode($general_details);

		$query = DB::select("insert into `game_transaction_ext` (`game_trans_id`, `provider_trans_id`, `round_id`, `amount`, `game_transaction_type`, `provider_request`, `mw_response`, `mw_request`, `client_response`, `transaction_detail`, `general_details`) values ($game_trans_id,'$provider_trans_id','$round_id',$amount,$game_type,'$provider_request','$mw_response','$mw_request','$client_response','$transaction_detail','$general_details')");

		return DB::connection()->getPdo()->lastInsertId();
	}

	/**
	 * GLOBAL
	 * Update
	 */
	public  static function updatecreateGameTransExt($game_trans_ext_id, $provider_request, $mw_response, $mw_request, $client_response,$transaction_detail,$general_details='NO DATA') {
		// DB::enableQueryLog();
   	    $update = DB::table('game_transaction_ext')
                ->where('game_trans_ext_id', $game_trans_ext_id)
                ->update([
					"provider_request" => json_encode($provider_request),
					"mw_response" =>json_encode($mw_response),
					"mw_request"=>json_encode($mw_request),
					"client_response" =>json_encode($client_response),
					"transaction_detail" =>json_encode($transaction_detail),
					"general_details" =>json_encode($general_details)
	    		]);
	    // Helper::saveLog('updatecreateGameTransExt', 999, json_encode(DB::getQueryLog()), "TIME updatecreateGameTransExt");
		return ($update ? true : false);
	}

	public  static function updatecreateGameTransExt_raw($game_trans_ext_id, $provider_request, $mw_response, $mw_request, $client_response, $transaction_detail, $general_details = 'NO DATA')
	{
		$provider_request = json_encode($provider_request);
		$mw_response = json_encode($mw_response);
		$mw_request = json_encode($mw_request);
		$client_response = json_encode($client_response);
		$transaction_detail = json_encode($transaction_detail);
		$general_details = json_encode($general_details);
		$query = DB::select("update `game_transaction_ext` set `provider_request` = '$provider_request', `mw_response` = '$mw_response', `mw_request` = '$mw_request', `client_response` = '$client_response', `transaction_detail` = '$transaction_detail', `general_details` = '$general_details' where `game_trans_ext_id` = $game_trans_ext_id");
	}

	/**
	 * GLOBAL
	 * Check Provider if currency is registered 
	 * 
	 */
	public static function getProviderCurrency($provider_id,$currency){
        $provider_currencies = DB::table("providers")->where("provider_id",$provider_id)->get();
        $currencies = json_decode($provider_currencies[0]->currencies,TRUE);
        if(array_key_exists($currency,$currencies)){
            return $currencies[$currency];
        }
        else{
            return 'false';
        }
    }

    /**
	 * GLOBAL
	 * Check Provider languages
	 * 
	 */
    public static function getLanguage($identifier, $language,$type='id'){
        $provider_language = DB::table("providers");
        if ($type == 'name') {
            $provider_language->where([
                ["provider_name", "=", $identifier],
            ]);
        }
        if ($type == 'id') {
            $provider_language->where([
                ["provider_id", "=", $identifier],
            ]);
        }
        $provider_language = $provider_language->get();
        $languages = json_decode($provider_language[0]->languages,TRUE);
        if(array_key_exists($language,$languages)){
            return $languages[$language];
        }
        else{
            return $languages["en"];
        }
    }

     /**
	 * GLOBAL
	 * Find Token OWNER
	 * 
	 */
	public static function findTokenID($token_id) {
		$token = DB::table('player_session_tokens')
						->where('token_id', $token_id)
						->first();	
		return $token ? $token : 'false';
	}
	
    /**
	 * GLOBAL
	 * Find Game ID
	 * 
	 */
	public static function findGameID($game_id) {
		$game = DB::table('games')
						->where('game_id', $game_id)
						->first();	
		return $game ? $game : 'false';
	}

    // BACKUP FUNCTION
    // public static function find($game_code) {
	// 	$search_result = DB::table('games')
	// 							->where('game_code', $game_code)
	// 							->first();	
	// 	return ($search_result ? $search_result : false);
	// }

	// public static function findbyid($game_id) {
	// 	$search_result = DB::table('games')
	// 							->where('game_id', $game_id)
	// 							->first();	
	// 	return ($search_result ? $search_result : false);
	// }

	public static function getNonceprevious($provider_id) {
		$nonce_previous = DB::table('seamless_request_logs')
			->where('method_name', 'Booming nonce')
			->where('provider_id',$provider_id)
			->latest()->first();
	    return $nonce_previous ? $nonce_previous : 'false';
	}

	public static function simplePlayAPICall ($queryString, $hashedString) {     
        $result = ['error' => 1, 'message'=> 'An error occurred.', 'data' => []];

        $encryptKey = config("providerlinks.simpleplay.ENCRYPT_KEY");
        $apiURL = config("providerlinks.simpleplay.API_URL");
        
        $DES = new DESHelper($encryptKey);
    
        // Encrypt Query String (q)
        $encryptedString = $DES->encrypt($queryString);

        $requestBody = ['q' => $encryptedString, 's' => $hashedString];
       
        $options = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($requestBody)
            )
        );

        $context  = stream_context_create($options);
        $result = file_get_contents($apiURL, false, $context);

        if ($result === FALSE) {
		    $result = ['error' => 1, 'message'=> 'Error in request.', 'data' => []];
		}
		else
		{
			$xml = simplexml_load_string($result) or die("Error: Cannot create object");

			$result = ['error' => 0, 'message'=> 'Request Successful.', 'data' => $xml];
		}

		return $result;
	}
	
	/**
	 * GLOBAL
	 * iso_type = 639-type
	 * 
	 */
	public static function getLangIso($lang,$iso_type=1) {
		$lang = strtolower($lang);
		$language = [
			"en" => ['1' => 'en', '2' => 'eng', '3' => 'eng'],
			"zh" => ['1' => 'zh', '2' => 'zho', '3' => 'chi'],
			"ru" => ['1' => 'ru', '2' => 'rus', '3' => 'rus'],
			"ja" => ['1' => 'ja', '2' => 'jpn', '3' => 'jpn'],
			"th" => ['1' => 'th', '2' => 'tha', '3' => 'tha'],
			"ko" => ['1' => 'ko', '2' => 'kor', '3' => 'kor'],
			"te" => ['1' => 'te', '2' => 'tel', '3' => 'tel'],
			"te" => ['1' => 'tr', '2' => 'tur', '3' => 'tur'],
		];
		if(array_key_exists($lang, $language)){
    		return $language[$lang][$iso_type];
    	}else{
    		return false;
    	}
	}

	public static function errorOccur($player_id,$game_id,$mw_payload,$game_trans_ext_id){
		$block = DB::table('game_player_restriction')->insert([
				"player_id" => $player_id, 
				"game_id" => $game_id, 
				"mw_payload" => $mw_payload, 
				"game_trans_ext_id" => $game_trans_ext_id, 
				"status" => 1]);
		return $block == true ? true : false;
	}


	/***************************************************  EXPERIMENTAL   *************************************************** */

	/**
	 * GLOBAL
	 * Lock the players last session token
	 * player_session_tokens (status_id = 1:active, 2:lock)
	 * @param [Object] $[client_details]
	 * 
	 */
	public static function updatePlayerSession($client_details,$status_id){
		DB::select('update player_session_tokens set `status_id` = '.$status_id.' where token_id = '.$client_details->token_id);
	}

	// $query = DB::select("insert into `game_player_restriction` (`game_id`, `player_id`, `game_trans_ext_id`) values ($game_id, $player_id, '$game_trans_ext')");

	public static function createRestrictGame($game_id, $player_id,$game_trans_ext,$mwp_payload=false){
		// if($mwp_payload != false){
		// 	// $mw_col = "`mw_payload`, ";
		// 	// $mw_val = "'".$mwp_payload."',";
		// 	// $mw_val = $mwp_payload;
		// 	// $mw_val =json_encode($mwp_payload);
		// 	$mw_val = json_decode(json_encode($mwp_payload));
		// 	$query = DB::select("insert into `game_player_restriction` (`game_id`, `player_id`,`mw_payload`, `game_trans_ext_id`) values ($game_id, $player_id,".$mw_val.", $game_trans_ext)");
		// }else{
		// 	$query = DB::select("insert into `game_player_restriction` (`game_id`, `player_id`,`mw_payload`, `game_trans_ext_id`) values ($game_id, $player_id,".$mw_val.", $game_trans_ext)");
		// }
		// return DB::connection()->getPdo()->lastInsertId();

		if($mwp_payload != false){
			$mw_val = json_encode($mwp_payload);
		}else{
			$mw_val = 'FAILED';
		}
		$gametransactionext = array(
			"game_id" => $game_id,
			"player_id" => $player_id,
			"mw_payload" => $mw_val,
			"game_trans_ext_id" => $game_trans_ext,
		);
		$gamestransaction_ext_ID = DB::table("game_player_restriction")->insertGetId($gametransactionext);
		return $gamestransaction_ext_ID;
	}

	

	public static function checkGameRestricted($game_id, $player_id){
		$query = DB::select('select * from game_player_restriction where player_id = '.$player_id.' and game_id = '.$game_id.'');
		$client_details = count($query);
		return $client_details > 0 ? true : false;
	}
	

	public static function deleteGameRestricted($type, $identifier){
		if($type == 'id'){
			$where = 'where gpr_id = "'.$identifier.'"';
		}
		$filter = 'order by gpr_id desc LIMIT 1';
		DB::select('delete from game_player_restriction '.$where.' '.$filter.'');
	}


	public static function checkClientPlayer($client_id, $client_player_id){
		$player = DB::table('players')
					->where('client_id',$client_id)
					->where('client_player_id',$client_player_id)
					->first();
		return $player;
	}

	public static function fundTransfer_requestBody($client_details,$amount,$game_code,$game_name,$transactionId,$roundId,$type,$rollback=false){
     
        $requesttocient = [
            "access_token" => $client_details->client_access_token,
            "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
            "type" => "fundtransferrequest",
            "datetsent" => Helper::datesent(),
            "gamedetails" => [
              "gameid" => $game_code,
              "gamename" => $game_name
            ],
            "fundtransferrequest" => [
                  "playerinfo" => [
                  "player_username"=>$client_details->username,
                  "client_player_id"=>$client_details->client_player_id,
                  "token" => $client_details->player_token
              ],
              "fundinfo" => [
                    "gamesessionid" => "",
                    "transactiontype" => $type,
                    "transactionId" => $transactionId, // this id is equivalent to game_transaction_ext game_trans_ext_id
                    "roundId" => $roundId,// this id is equivalent to game_transaction game_trans_id
                    "rollback" => $rollback,
                    "currencycode" => $client_details->default_currency,
                    "amount" => $amount #change data here
              ]
            ]
		];

		return $requesttocient;
    }

	# EXPERIMENTAL FINALLY SETUP
	public static function playerDetailsCall_inhouse($client_details){
		$player_details = DB::select("SELECT * FROM player_session_tokens WHERE token_id = '".$client_details->token_id."'");
		$data = count($player_details);
		if($data > 0){
			$in_house_player_details = [
				'playerdetailsresponse' => [
					'balance' => $player_details[0]->balance
				]
			];
			return json_decode(json_encode($in_house_player_details));
		}else{
			return false;
		}
	}

	/**
	 * GLOBAL
	 * [Transaction Helper]
	 * saveBalance (provider that has refreash token should have data gametoken)
	 * 
	 */
	public static function saveBalance($token){
		$client_details = AWSHelper::getClientDetails('token', $token);
		if($client_details){
			$client = new Client([
			    'headers' => [ 
			    	'Content-Type' => 'application/json',
			    	'Authorization' => 'Bearer '.$client_details->client_access_token
			    ]
			]);
			$datatosend = ["access_token" => $client_details->client_access_token,
				"hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
				"type" => "playerdetailsrequest",
				"datesent" => Helper::datesent(),
                "gameid" => "",
				"clientid" => $client_details->client_id,
				"playerdetailsrequest" => [
					"player_username"=>$client_details->username,
					"client_player_id" => $client_details->client_player_id,
					"token" => $token,
					"gamelaunch" => true,
					"refreshtoken" => false
				]
			];
		}
		try{	
			$guzzle_response = $client->post($client_details->player_details_url,
				['body' => json_encode($datatosend)]
			);
			$client_response = json_decode($guzzle_response->getBody()->getContents());
			AWSHelper::saveLog('PLAYER DETAILS LOG', 999, json_encode($client_response), $datatosend);
			if(isset($client_response->playerdetailsresponse->status->code) && $client_response->playerdetailsresponse->status->code == 200){
				AWSHelper::_insertOrUpdate($client_details->token_id,$client_response->playerdetailsresponse->balance);
				return true;
			}else{
				return false;
			}
		}catch (\Exception $e){
			return false;
		 }
	}

	public static function _insertOrUpdate($token_id,$balance){
		$balance_query = DB::select("SELECT * FROM player_session_tokens WHERE token_id = '".$token_id."'");
		$data = count($balance_query);
		if($data > 0){
			return DB::select("UPDATE player_session_tokens SET balance=".$balance." WHERE token_id ='".$token_id."'");
		}
		else{
			return DB::select("INSERT INTO  player_session_tokens (token_id,balance) VALUEs ('".$token_id."',".$balance.")");
		}
	}

	public static function idenpotencyTable($provider_trans_id){
		return DB::select("INSERT INTO  transaction_idempotent (provider_trans_id) VALUEs ('".$provider_trans_id."')");
	}
}