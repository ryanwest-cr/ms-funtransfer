<?php

namespace App\Helpers;

class Authorize
{
	if (!function_exists('check_key')) {
	  function check_key($hashkey, $access_token){
	    $result = false;

	    if($hashkey == md5(env('API_KEY').$access_token)) {
			$result = true;
		}
	  }
	}
}
