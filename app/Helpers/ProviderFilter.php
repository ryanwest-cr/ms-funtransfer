<?php
namespace App\Helpers;

use GuzzleHttp\Client;
use App\Helpers\Helper;
use App\Helpers\GameLobby;
use App\Helpers\ProviderHelper;
use DB; 

/**
 * TEST ONLY
 */
class ProviderFilter{

	/**
	 * [currencyFilter]
	 * @return  object
	 * 
	 */
	public static function currencyFilter($provider_name,$currency,$game_code){

		if($provider_name == "Bole Gaming"){  // TEST FILTER ONLY FOR BOLE

			$provider_currency = DB::table("providers")->where("provider_name",$provider_name)->get();
	        $currencies = json_decode($provider_currency[0]->currencies,TRUE);
	        if(array_key_exists($currency,$currencies)){ 	// Currency is in the providers allowed currency
	            return true;
	        }
	        else{
	        	$msg = array(
                    "game_code" => $game_code,
                    "game_launch" => false
                );
                return $msg;
	        }

		}
       
    }


}