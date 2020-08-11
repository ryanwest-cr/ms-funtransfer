<?php
namespace App\Helpers;
use DB;
use GuzzleHttp\Client;
use SimpleXMLElement;
class PNGHelper
{
    public static function arrayToXml($array, $rootElement = null, $xml = null){
        $_xml = $xml; 
      
        // If there is no Root Element then insert root 
        if ($_xml === null) { 
            $_xml = new SimpleXMLElement($rootElement !== null ? $rootElement : '<root/>'); 
        } 
        
        // Visit all key value pair 
        foreach ($array as $k => $v) { 
            
            // If there is nested array then 
            if (is_array($v)) {  
                
                // Call function for nested array 
                PNGHelper::arrayToXml($v, $k, $_xml->addChild($k)); 
                } 
                
            else { 
                
                // Simply add child element.  
                $_xml->addChild($k, $v); 
            } 
        } 
        
        return $_xml->asXML(); 
    }
    public static function createPNGGameTransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response,$game_transaction_type){
		$gametransactionext = array(
			"provider_trans_id" => $provider_request->transactionId,
			"game_trans_id" => $gametransaction_id,
			"round_id" =>$provider_request->roundId,
			"amount" =>$provider_request->real,
			"game_transaction_type"=>$game_transaction_type,
			"provider_request" =>json_encode($provider_request),
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gametransactionext;
    }
    public static function updateGameTransaction($existingdata,$request_data,$type){
		switch ($type) {
			case "debit":
                    $trans_data["win"] = 0;
                    $trans_data["bet_amount"] = $existingdata->bet_amount + $request_data->real;
					$trans_data["pay_amount"] = 0;
					$trans_data["entry_id"] = 1;
				break;
			case "credit":
					$trans_data["win"] = $request_data["win"];
					$trans_data["pay_amount"] = abs($request_data["amount"]);
					$trans_data["income"]=$existingdata->bet_amount-$request_data["amount"];
					$trans_data["entry_id"] = 2;
					$trans_data["payout_reason"] = $request_data["payout_reason"];
				break;
			case "refund":
					$trans_data["win"] = 4;
					$trans_data["pay_amount"] = $request_data["amount"];
					$trans_data["entry_id"] = 2;
					$trans_data["income"]= $existingdata->bet_amount-$request_data["amount"];
					$trans_data["payout_reason"] = "Refund of this transaction ID: ".$request_data["transid"]."of GameRound ".$request_data["roundid"];
				break;

			default:
		}
		/*var_dump($trans_data); die();*/
		return DB::table('game_transactions')->where("game_trans_id",$existingdata->game_trans_id)->update($trans_data);
    }
    public static function gameTransactionExtChecker($provider_trans_id){
        $gametransaction = DB::table('game_transaction_ext')->where("provider_trans_id",$provider_trans_id)->first();
        return $gametransaction?true:false;
    }
    public static function gameTransactionRollbackExtChecker($provider_trans_id,$type){
        $gametransaction = DB::table('game_transaction_ext')->where("provider_trans_id",$provider_trans_id)->where("game_transaction_type",$type)->first();
        return $gametransaction?$gametransaction:false;
    }
}