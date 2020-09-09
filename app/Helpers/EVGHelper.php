<?php
namespace App\Helpers;
use DB;
use GuzzleHttp\Client;
use App\Services\AES;
class EVGHelper
{
    public static function createEVGGameTransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response,$game_transaction_type){
		$gametransactionext = array(
			"provider_trans_id" => $provider_request["transaction"]["id"],
			"game_trans_id" => $gametransaction_id,
			"round_id" =>$provider_request["transaction"]["refId"],
			"amount" =>$provider_request["transaction"]["amount"],
			"game_transaction_type"=>$game_transaction_type,
			"provider_request" =>json_encode($provider_request),
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gamestransaction_ext_ID;
    }

}