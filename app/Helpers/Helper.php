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
					"request_data" => json_encode(json_decode($request_data))
					"request_data" => $response_data
				];
		DB::table('seamless_request_logs')->insert($data);
	}

}