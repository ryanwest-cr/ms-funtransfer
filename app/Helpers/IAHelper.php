<?php
namespace App\Helpers;

use Carbon\Carbon;
use GuzzleHttp\Client;
use App\Helpers\Helper;
use DB;


/**
 * @author's note : Isolated Function for Single File Debugging According to the provider setup - RiAN
 * @author's note : Dont Remove commented line please
 * 
 */
class IAHelper{


	#################################################################################################
	# IA GAMING

	// public static $prefix = 'BETRNK';
	// public static $auth_key = '54bc08c471ae3d656e43735e6ffc9bb6';
	// public static $pch = 'BRNK';
	// public static $iv = '45b80556382b48e5';
	// public static $url_lunch = 'http://api.ilustretest.com/user/lunch';
	// public static $url_register = 'http://api.ilustretest.com/user/register';
	// self::$pch

	public static function datesent()
	{
		$date = Carbon::now();
		return $date->toDateTimeString();
	}

	public static function amountToFloat($amount)
	{
		$float = floatval(number_format((float)$amount, 2, '.', ''));
		return $float;
	}


	public static function checkFundStatus($win)
	{
		$status_type = [
			"ok" => 'success code',
			"Ok" => 'success code',
			"OK" => 'success code',
			"success" => 'success code',
			"Success" => 'success code',
			"SUCCESS" => 'success code',
		];
		if (array_key_exists($win, $status_type)) {
			return true; // if success
		} else {
			return false; // if failed
		}
	}

	public static function userlunch($username)
    {
        $params = [
            "username" => $username,
            "lang" => 2, // Default English
            // "client" => 2,  // 2 for wap, 1 for PC
        ];
        $uhayuu = IAHelper::hashen($params);
        $header = ['pch:'. config('providerlinks.iagaming.pch')];
        $timeout = 5;
        $client_response = IAHelper::curlData(config('providerlinks.iagaming.url_lunch'), $uhayuu, $header, $timeout);
        $data = json_decode(IAHelper::rehashen($client_response[1], true));
        IAHelper::saveLog('IA User Launch 1', 15, json_encode($client_response), $params);
        IAHelper::saveLog('IA User Launch 2', 15, json_encode($data), $uhayuu);
        return $data->data->url;
    }


    /**
	 * Create Hash Key
	 * @return Encrypted AES string
	 *
	 */
    public static function hashen($params=[])
	{
		$params['auth_key'] = IAHelper::getMD5ParamsString($params);
		$plaintext = json_encode($params);
		$iv = config('providerlinks.iagaming.iv');
		$method = 'AES-256-CBC';
		$hashen = base64_encode(openssl_encrypt($plaintext, $method, config('providerlinks.iagaming.auth_key'), OPENSSL_RAW_DATA, $iv));
		return $hashen;
	}

	/**
	 * Decode Hashen
	 * @return Decoded Hashen AES string
	 *
	 */
	public static function rehashen($hashen)
	{
		$method = 'AES-256-CBC';
		$iv = config('providerlinks.iagaming.iv');
		$rehashen = openssl_decrypt(base64_decode($hashen), $method,config('providerlinks.iagaming.auth_key'), OPENSSL_RAW_DATA, $iv);
		return $rehashen;
	}

    /**
	 * Decode Hashen
	 * @return Sorted Array Keys
	 *
	 */
    public static function getMD5ParamsString($params=[])
    {
        ksort($params);
        $arr = [];
        foreach($params as $key => $val)
        {
            $arr[] = $key . '=' . $val;
        }
        return md5(join(',', $arr));
    }


    /**
	 * Api Call
	 * 
	 * @param postData = encoded string using mcrypt
	 * @param header = header parameters
	 * @return ereturn array($status, $handles, $error)
	 * 
	 */
	public static function curlData($url, $postData = array(), $header = false, $timeout = 10)
	{
	    $error = '';
	    $status = 1;
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    if(!empty($header))
	    {
	        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	    }
	    
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)');
	    curl_setopt($ch, CURLOPT_URL, $url);
	    if(!empty($postData))
	    {
	        curl_setopt($ch, CURLOPT_POST, true);
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
	    }
	    
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
	    $handles = curl_exec($ch);
	    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    if($httpcode < 200 || $httpcode >= 300)
	    {
	        $status = 0;
	        $error = $httpcode;
	    }
	    if(curl_errno($ch))
	    {
	        $error = curl_error($ch);
	        $status = 0;
	    }
	    
	    curl_close($ch);
	    
	    return array($status, $handles, $error);
	}


	######################################################################################################################
	# ISOLATED FUNCTION FOR SINGLE DEBUGGING ON THE GO (ProviderHelper::class)

	public static function saveLog($method, $provider_id = 0, $request_data, $response_data)
	{
		$data = [
			"method_name" => $method,
			"provider_id" => $provider_id,
			"request_data" => json_encode(json_decode($request_data)),
			"response_data" => json_encode($response_data)
		];
		return DB::table('seamless_request_logs')->insert($data);
	}

	public static function createGameTransaction($token_id, $game_id, $bet_amount, $payout, $entry_id,  $win = 0, $transaction_reason = null, $payout_reason = null, $income = null, $provider_trans_id = null, $round_id = 1)
	{
		$query = DB::select("insert into `game_transactions` (`token_id`, `game_id`, `round_id`, `bet_amount`, `provider_trans_id`, `pay_amount`, `income`, `entry_id`, `win`, `transaction_reason`, `payout_reason`) values ($token_id, $game_id, '$round_id', $bet_amount, '$provider_trans_id', $payout, '$income', $entry_id, $win, '$transaction_reason', '$payout_reason')");
		return DB::connection()->getPdo()->lastInsertId();
	}
	
	
	public  static function updateGameTransactionStatus($game_trans_id, $win, $reason)
	{
		$reason = ProviderHelper::updateReason($reason);
		$update = DB::select("update `game_transactions` set `win` = $win, `transaction_reason` = '$reason' where `game_trans_id` = $game_trans_id");
	}

	public static function updateGameTransaction($identifier, $pay_amount, $income, $win, $entry_id, $type = 'game_trans_id', $bet_amount = 0, $multi_bet = false)
	{
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
		if ($multi_bet == true) {
			$update->update(['bet_amount' => $bet_amount]);
		}
		return ($update ? true : false);
	}


	public static  function findGameTransaction($identifier, $type, $entry_type = '')
	{

		if ($type == 'transaction_id') {
			$where = 'where gt.provider_trans_id = "' . $identifier . '" AND gt.entry_id = ' . $entry_type . '';
		}
		if ($type == 'game_transaction') {
			$where = 'where gt.game_trans_id = "' . $identifier . '"';
		}
		if ($type == 'round_id') {
			$where = 'where gt.round_id = "' . $identifier . '" AND gt.entry_id = ' . $entry_type . '';
		}

		$filter = 'LIMIT 1';
		$query = DB::select('select *, (select transaction_detail from game_transaction_ext where game_trans_id = gt.game_trans_id order by game_trans_id limit 1) as transaction_detail from game_transactions gt ' . $where . ' ' . $filter . '');
		$client_details = count($query);
		return $client_details > 0 ? $query[0] : 'false';
	}

	public  static function findGameExt($provider_identifier, $game_transaction_type, $type)
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

	public static function createGameTransExtV2($game_trans_id, $provider_trans_id, $round_id, $amount, $game_type, $provider_request = 'FAILED', $mw_response = 'FAILED', $mw_request = 'FAILED', $client_response = 'FAILED', $transaction_detail = 'FAILED', $general_details = null)
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

	public  static function updatecreateGameTransExt($game_trans_ext_id, $provider_request, $mw_response, $mw_request, $client_response, $transaction_detail, $general_details = 'NO DATA')
	{
		// $provider_request = json_encode($provider_request);
		// $mw_response = json_encode($mw_response);
		// $mw_request = json_encode($mw_request);
		// $client_response = json_encode($client_response);
		// $transaction_detail = json_encode($transaction_detail);
		// $general_details = json_encode($general_details);
		// $query = DB::select("update `game_transaction_ext` set `provider_request` = '$provider_request', `mw_response` = '$mw_response', `mw_request` = '$mw_request', `client_response` = '$client_response', `transaction_detail` = "$transaction_detail", `general_details` = "$general_details" where `game_trans_ext_id` = $game_trans_ext_id");
		// $query = DB::select('update `game_transaction_ext` set `provider_request` = "$provider_request", `mw_response` = "$mw_response", `mw_request` = "$mw_request", `client_response` = "$client_response", `transaction_detail` = "$transaction_detail", `general_details` = "$general_details" where `game_trans_ext_id` = $game_trans_ext_id');

		$update = DB::table('game_transaction_ext')
		->where('game_trans_ext_id', $game_trans_ext_id)
			->update([
				"provider_request" => json_encode($provider_request),
				"mw_response" => json_encode($mw_response),
				"mw_request" => json_encode($mw_request),
				"client_response" => json_encode($client_response),
				"transaction_detail" => json_encode($transaction_detail),
				"general_details" => json_encode($general_details)
			]);
		// Helper::saveLog('updatecreateGameTransExt', 999, json_encode(DB::getQueryLog()), "TIME updatecreateGameTransExt");
		return ($update ? true : false);
	}

	public static function getClientDetails($type = "", $value = "", $gg = 1, $providerfilter = 'all')
	{
		if ($type == 'token') {
			$where = 'where pst.player_token = "' . $value . '"';
		}
		if ($providerfilter == 'fachai') {
			if ($type == 'player_id') {
				$where = 'where ' . $type . ' = "' . $value . '" AND pst.status_id = 1 ORDER BY pst.token_id desc';
			}
		} else {
			if ($type == 'player_id') {
				$where = 'where ' . $type . ' = "' . $value . '"';
			}
		}
		if ($type == 'username') {
			$where = 'where p.username = "' . $value . '"';
		}
		if ($type == 'token_id') {
			$where = 'where pst.token_id = "' . $value . '"';
		}

		$filter = 'order by token_id desc LIMIT 1';

		$query = DB::select('select `p`.`client_id`, `p`.`player_id`, `p`.`email`, `p`.`client_player_id`,`p`.`language`, `p`.`currency`, `p`.`test_player`, `p`.`username`,`p`.`created_at`,`pst`.`token_id`,`pst`.`player_token`,`c`.`client_url`,`c`.`default_currency`,`pst`.`status_id`,`p`.`display_name`,`op`.`client_api_key`,`op`.`client_code`,`op`.`client_access_token`,`ce`.`player_details_url`,`ce`.`fund_transfer_url`,`p`.`created_at` from player_session_tokens pst inner join players as p using(player_id) inner join clients as c using (client_id) inner join client_endpoints as ce using (client_id) inner join operator as op using (operator_id) ' . $where . ' ' . $filter . '');

		$client_details = count($query);
		return $client_details > 0 ? $query[0] : 'false';
	}

	public static function playerDetailsCall($client_details, $refreshtoken = false)
	{
		$client = new Client([
			'headers' => [
				'Content-Type' => 'application/json',
				'Authorization' => 'Bearer ' . $client_details->client_access_token
			]
		]);
		$datatosend = [
			"access_token" => $client_details->client_access_token,
			"hashkey" => md5($client_details->client_api_key . $client_details->client_access_token),
			"type" => "playerdetailsrequest",
			"datesent" => IAHelper::datesent(),
			"clientid" => $client_details->client_id,
			"playerdetailsrequest" => [
				"player_username" => $client_details->username,
				"client_player_id" => $client_details->client_player_id,
				"token" => $client_details->player_token,
				"gamelaunch" => true,
				"refreshtoken" => $refreshtoken
			]
		];
		try {
			$guzzle_response = $client->post(
				$client_details->player_details_url,
				['body' => json_encode($datatosend)]
			);
			$client_response = json_decode($guzzle_response->getBody()->getContents());
			return $client_response;
		} catch (\Exception $e) {
			IAHelper::saveLog('ALDEBUG client_player_id = ' . $client_details->client_player_id,  99, json_encode($datatosend), $e->getMessage());
			return 'false';
		}
	}

	public static function findGameDetails($type, $provider_id, $game_code)
	{
		$query = DB::Select("SELECT game_id,game_code,game_name FROM games WHERE game_code = '" . $game_code . "' AND provider_id = '" . $provider_id . "'");
		$result = count($query);
		return $result > 0 ? $query[0] : null;
	}

}