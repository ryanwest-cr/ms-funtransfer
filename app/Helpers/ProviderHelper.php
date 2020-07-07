<?php
namespace App\Helpers;

use GuzzleHttp\Client;
use App\Helpers\Helper;
use DB; 

class ProviderHelper{

	// EVOPLAY 
	/**
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

}