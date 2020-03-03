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

	public static function saveLog($method, $provider_id = 0, $data) {
		$data = [
				"method_name" => $method,
				"provider_id" => $provider_id,
				"request_data" => json_encode(json_decode($data))
				];
		DB::table('seamless_request_logs')->insert($data);
	}

}