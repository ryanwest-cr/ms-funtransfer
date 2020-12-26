<?php

namespace App\Helpers;

use App\Services\AES;
use GuzzleHttp\Client;
use App\Helpers\Helper;
use Carbon\Carbon;
use DB;


class KAHelper{


	######################################################################################################################
	# ISOLATED FUNCTION FOR SINGLE DEBUGGING ON THE GO (ProviderHelper::class)
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
		$provider_request = json_encode($provider_request);
		$mw_response = json_encode($mw_response);
		$mw_request = json_encode($mw_request);
		$client_response = json_encode($client_response);
		$transaction_detail = json_encode($transaction_detail);
		$general_details = json_encode($general_details);
		$query = DB::select("update `game_transaction_ext` set `provider_request` = '$provider_request', `mw_response` = '$mw_response', `mw_request` = '$mw_request', `client_response` = '$client_response', `transaction_detail` = '$transaction_detail', `general_details` = '$general_details' where `game_trans_ext_id` = $game_trans_ext_id");
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


	public static function getClientDetails($type = "", $value = "")
	{
		if ($type == 'token') {
			$where = 'where pst.player_token = "' . $value . '"';
		}
		if ($type == 'player_id') {
			$where = 'where ' . $type . ' = "' . $value . '"';
		}
		if ($type == 'username') {
			$where = 'where p.username = "' . $value . '"';
		}
		if ($type == 'token_id') {
			$where = 'where pst.token_id = "' . $value . '"';
		}

		$filter = 'order by token_id desc LIMIT 1';

		// $query = DB::select('select `p`.`client_id`, `p`.`player_id`, `p`.`email`, `p`.`client_player_id`,`p`.`language`, `p`.`currency`, `p`.`test_player`, `p`.`username`,`p`.`created_at`,`pst`.`token_id`,`pst`.`player_token`,`c`.`client_url`,`c`.`default_currency`,`pst`.`status_id`,`p`.`display_name`,`op`.`client_api_key`,`op`.`client_code`,`op`.`client_access_token`,`ce`.`player_details_url`,`ce`.`fund_transfer_url`,`p`.`created_at` from player_session_tokens pst inner join players as p using(player_id) inner join clients as c using (client_id) inner join client_endpoints as ce using (client_id) inner join operator as op using (operator_id) ' . $where . ' ' . $filter . '');
		$query = DB::select('select `p`.`client_id`, `p`.`player_id`, `p`.`email`, `p`.`client_player_id`,`p`.`language`, `p`.`currency`, `p`.`test_player`, `p`.`username`,`p`.`created_at`,`pst`.`token_id`,`pst`.`player_token`,`pst`.`balance`,`c`.`client_url`,`c`.`default_currency`,`pst`.`status_id`,`p`.`display_name`,`op`.`client_api_key`,`op`.`client_code`,`op`.`client_access_token`,`ce`.`player_details_url`,`ce`.`fund_transfer_url`,`p`.`created_at` from player_session_tokens pst inner join players as p using(player_id) inner join clients as c using (client_id) inner join client_endpoints as ce using (client_id) inner join operator as op using (operator_id) '.$where.' '.$filter.'');


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
			"datesent" => Helper::datesent(),
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
			Helper::saveLog('ALDEBUG client_player_id = ' . $client_details->client_player_id,  99, json_encode($datatosend), $e->getMessage());
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

?>