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
	 * EVOPLAY ONLY
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
	 * Client PInfo
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

			try{
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
						"client_player_id" => $client_details->client_player_id,
						"token" => $player_token,
						"gamelaunch" => true,
						"refreshtoken" => $refreshtoken
					]
				];

			
				
				$guzzle_response = $client->post($client_details->player_details_url,
				    ['body' => json_encode($datatosend)]
				);

			

				$client_response = json_decode($guzzle_response->getBody()->getContents());
			 	return $client_response;
            }catch (Exception $e){
               return 'falsefdsgdsf';
            }
		}else{
			return 'falses';
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
		 	]);
		}
		if ($type == 'round_id') {
			$transaction_db->where([
		 		["gte.round_id", "=", $provider_identifier],
		 		["gte.game_transaction_type", "=", $game_transaction_type],
		 	]);
		}  
		$result= $transaction_db->first();
		return $result ? $result : 'false';
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
		];
		if(array_key_exists($win, $win_type)){
    		return $win_type[$win];
    	}else{
    		return 'Transaction Was Updated!';
    	}
	}

	/**
	 * GLOBAL
	 * Check Provider if currency is registered 
	 *  CURRENCY IN UPPERCASE
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
}