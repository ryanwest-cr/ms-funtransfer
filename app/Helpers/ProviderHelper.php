<?php
namespace App\Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use App\Helpers\Helper;
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
    public static function getClientDetails($type = "", $value = "") {
		$query = DB::table("clients AS c")
				 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.client_player_id','p.language', 'p.currency', 'pst.token_id', 'pst.player_token' , 'c.client_url', 'c.default_currency', 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url','p.created_at')
				 ->leftJoin("players AS p", "c.client_id", "=", "p.client_id")
				 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
				 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
				 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id");
				if ($type == 'token') {
					$query->where([
				 		["pst.player_token", "=", $value],
				 		// ["pst.status_id", "=", 1]
				 	]);
				}
				if ($type == 'player_id') {
					$query->where([
				 		["p.player_id", "=", $value],
				 		// ["pst.status_id", "=", 1]
				 	]);
				}
				if ($type == 'site_url') {
					$query->where([
				 		["c.client_url", "=", $value],
				 	]);
				}
				if ($type == 'username') {
					$query->where([
				 		["p.username", $value],
				 	]);
				}
				if ($type == 'token_id') {
					$query->where([
				 		["pst.token_id", $value],
				 	]);
				}
				$result= $query
				 			->latest('token_id')
				 			->first();

			    return $result;
	}

	/**
	 * GLOBAL
	 * Client Player Details API Call
	 * @return [Object]
	 * @param $[player_token] [<players token>]
	 * @param $[refreshtoken] [<Default False, True token will be requested>]
	 * 
	 */
	public static function playerDetailsCall($player_token, $refreshtoken=false){
		$client_details = ProviderHelper::getClientDetails('token', $player_token);
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
		}else{
			Helper::saveLog('ALDEBUG Token Not Found = '.$player_token,  99, json_encode($datatosend), 'TOKEN NOT FOUND');
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
		$transaction_db = DB::table('game_transaction_ext as gte');
        if ($type == 'transaction_id') {
			$transaction_db->where([
		 		["gte.provider_trans_id", "=", $provider_identifier],
		 		["gte.game_transaction_type", "=", $game_transaction_type],
		 		// ["gte.transaction_detail", "!=", '"FAILED"'],
		 	]);
		}
		if ($type == 'round_id') {
			$transaction_db->where([
		 		["gte.round_id", "=", $provider_identifier],
		 		["gte.game_transaction_type", "=", $game_transaction_type],
		 	]);
		}  
		$result = $transaction_db->latest()->first(); // Added Latest (CQ9) 08-12-20 - Al
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
		 		["gte.transaction_detail", "=", '"FAILED"']
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
   	    $update = DB::table('game_transactions')
                ->where('round_id', $round_id)
                ->update(['pay_amount' => $pay_amount, 
	        		  'income' => $income, 
	        		  'win' => $win, 
	        		  'entry_id' => $entry_id,
	        		  'transaction_reason' => ProviderHelper::updateReason($win),
	    		]);
		return ($update ? true : false);
	}


	/**
	 * GLOBAL
	 * Find game transaction and update the reason
	 * @param game_trans_id = the game_transaction_id, $win type
	 * 
	 */
	public  static function updateGameTransactionStatus($game_trans_id, $win, $reason) {
   	    $update = DB::table('game_transactions')
                ->where('game_trans_id', $game_trans_id)
                ->update([
        		  'win' => $win, 
        		  'transaction_reason' => ProviderHelper::updateReason($win),
        		  'payout_reason' => ProviderHelper::updateReason($reason),
	    		]);
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
		 "99" => 'Transaction FAILED - FATAL ERROR',
		];
		if(array_key_exists($win, $win_type)){
    		return $win_type[$win];
    	}else{
    		return 'Transaction Was Updated!';
    	}
	}

	/**
	 * GLOBAL
	 * Create Game Transaction
	 * 
	 */
	public static function createGameTransaction($token_id, $game_id, $bet_amount, $payout, $entry_id,  $win=0, $transaction_reason = null, $payout_reason = null , $income=null, $provider_trans_id=null, $round_id=1) {
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

	/**
	 * GLOBAL
	 * Create Game Transaction Extension
	 * @param  $[game_type] [<1=bet,2=win,3=refund>]
	 * 
	 */
	public static function createGameTransExt($game_trans_id, $provider_trans_id, $round_id, $amount, $game_type, $provider_request, $mw_response, $mw_request, $client_response, $transaction_detail, $general_details=null){
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
		return $gamestransaction_ext_ID;
	}

	/**
	 * GLOBAL
	 * Create Game Transaction Extension
	 * @param  $[game_type] [<1=bet,2=win,3=refund>]
	 * 
	 */
	public static function createGameTransExtV2($game_trans_id, $provider_trans_id, $round_id, $amount, $game_type, $provider_request='FAILED', $mw_response='FAILED', $mw_request='FAILED', $client_response='FAILED', $transaction_detail='FAILED', $general_details=null){
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
		return $gamestransaction_ext_ID;
	}

	/**
	 * GLOBAL
	 * Update
	 */
	public  static function updatecreateGameTransExt($game_trans_ext_id, $provider_request, $mw_response, $mw_request, $client_response,$transaction_detail,$general_details='NO DATA') {
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
    public static function getLanguage($provider_id,$language){
        $provider_language = DB::table("providers")->where("provider_id",$provider_id)->get();
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
	    	->first();
	    return $nonce_previous ? $nonce_previous : 'false';
	}

}