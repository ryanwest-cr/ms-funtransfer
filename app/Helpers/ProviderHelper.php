<?php
namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Helpers\Helper;
use App\Helpers\DESHelper;
use DB; 


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
    	DB::enableQueryLog();
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

		$query = DB::select('select `p`.`client_id`, `p`.`player_id`, `p`.`email`, `p`.`client_player_id`,`p`.`language`, `p`.`currency`, `p`.`test_player`, `p`.`username`,`p`.`created_at`,`pst`.`token_id`,`pst`.`player_token`,`c`.`client_url`,`c`.`default_currency`,`pst`.`status_id`,`p`.`display_name`,`op`.`client_api_key`,`op`.`client_code`,`op`.`client_access_token`,`ce`.`player_details_url`,`ce`.`fund_transfer_url`,`p`.`created_at` from player_session_tokens pst inner join players as p using(player_id) inner join clients as c using (client_id) inner join client_endpoints as ce using (client_id) inner join operator as op using (operator_id) '.$where.' '.$filter.'');

		 $client_details = count($query);
		 Helper::saveLog('GET CLIENT LOG', 999, json_encode(DB::getQueryLog()), "TIME GET CLIENT");
		 return $client_details > 0 ? $query[0] : null;
	}

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

	/**
	 * GLOBAL
     * Find Game Transaction
     * @param [string] $[identifier] [<ID of the game transaction>]
     * @param [int] $[type] [<transaction_id, round_id, refundbet>]
     * @param [int] $[entry_type] [<1 bet/debit, 2 win/credit>]
     * 
     */
    public  static function findGameTransaction($identifier, $type, $entry_type='') {
    	DB::enableQueryLog();
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
        Helper::saveLog('Find Game Transaction', 999, json_encode(DB::getQueryLog()), "TIME Find Game Transaction");
        return $result ? $result : 'false';
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
		DB::enableQueryLog();
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
		Helper::saveLog('Find Game Extension', 999, json_encode(DB::getQueryLog()), "TIME Find Game Extension");
		return $result ? $result : 'false';
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
		DB::enableQueryLog();
   	    $update = DB::table('game_transactions')
                ->where('game_trans_id', $round_id)
                // ->where('round_id', $round_id)
                ->update(['pay_amount' => $pay_amount, 
	        		  'income' => $income, 
	        		  'win' => $win, 
	        		  'entry_id' => $entry_id,
	        		  'transaction_reason' => ProviderHelper::updateReason($win),
	    		]);
	    Helper::saveLog('updateBetTransaction', 999, json_encode(DB::getQueryLog()), "TIME updateBetTransaction");
		return ($update ? true : false);
	}

	/**
	 * GLOBAL
	 * Test Only -RiAN
	 * @param [int] $[win] [<0 Lost, 1 win, 3 draw, 4 refund, 5 processing>] (SUGGESTION 2 NOT ENOUGH BALANCE)
	 * @param [int] $[entry_id] [<1 bet, 2 win>]
	 * 
	 */
	public static function updateGameTransaction($identifier, $pay_amount, $income, $win, $entry_id,$type='game_trans_id',$bet_amount=0,$multi_bet=false) {
		DB::enableQueryLog();
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
        Helper::saveLog('updateGameTransaction', 999, json_encode(DB::getQueryLog()), "TIME updateGameTransaction");
        return ($update ? true : false);
    }


	/**
	 * GLOBAL
	 * Find game transaction and update the reason
	 * @param game_trans_id = the game_transaction_id, $win type
	 * 
	 */
	public  static function updateGameTransactionStatus($game_trans_id, $win, $reason) {
		DB::enableQueryLog();
   	    $update = DB::table('game_transactions')
                ->where('game_trans_id', $game_trans_id)
                ->update([
        		  'win' => $win, 
        		  'transaction_reason' => ProviderHelper::updateReason($win),
        		  'payout_reason' => ProviderHelper::updateReason($reason),
	    		]);
	    Helper::saveLog('updateGameTransactionStatus', 999, json_encode(DB::getQueryLog()), "TIME updateGameTransactionStatus");
		return ($update ? true : false);
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
		DB::enableQueryLog();
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
		Helper::saveLog('createGameTransaction', 999, json_encode(DB::getQueryLog()), "TIME createGameTransaction");
		return $data_saved;
	}

	/**
	 * GLOBAL
	 * Create Game Transaction Extension
	 * @param  $[game_type] [<1=bet,2=win,3=refund>]
	 * 
	 */
	public static function createGameTransExt($game_trans_id, $provider_trans_id, $round_id, $amount, $game_type, $provider_request, $mw_response, $mw_request, $client_response, $transaction_detail, $general_details=null){
		DB::enableQueryLog();
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
		Helper::saveLog('createGameTransExt', 999, json_encode(DB::getQueryLog()), "TIME createGameTransExt");
		return $gamestransaction_ext_ID;
	}

	/**
	 * GLOBAL
	 * Create Game Transaction Extension
	 * @param  $[game_type] [<1=bet,2=win,3=refund>]
	 * 
	 */
	public static function createGameTransExtV2($game_trans_id, $provider_trans_id, $round_id, $amount, $game_type, $provider_request='FAILED', $mw_response='FAILED', $mw_request='FAILED', $client_response='FAILED', $transaction_detail='FAILED', $general_details=null){
		DB::enableQueryLog();
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
		Helper::saveLog('createGameTransExtV2', 999, json_encode(DB::getQueryLog()), "TIME createGameTransExtV2");
		return $gamestransaction_ext_ID;
	}

	/**
	 * GLOBAL
	 * Update
	 */
	public  static function updatecreateGameTransExt($game_trans_ext_id, $provider_request, $mw_response, $mw_request, $client_response,$transaction_detail,$general_details='NO DATA') {
		DB::enableQueryLog();
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
	    Helper::saveLog('updatecreateGameTransExt', 999, json_encode(DB::getQueryLog()), "TIME updatecreateGameTransExt");
		return ($update ? true : false);
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

}