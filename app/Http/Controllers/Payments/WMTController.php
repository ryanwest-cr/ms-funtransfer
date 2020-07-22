<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Helpers\Helper;
use App\PayTransaction;
use App\Helpers\PaymentHelper;
use DB;
use Carbon\Carbon;
class WMTController extends Controller
{
    //
    public function makeSettlement(Request $request){
        $requestfromprovider = json_decode($request->getContent(),TRUE);
        Helper::saveLog("WMT LOGS TEST",15,$request->getContent(),"test");
        if($requestfromprovider["txn_status"]==200){
            $player_details = $this->_getClientDetails("token",$requestfromprovider["merchant_txn_id"]);
            $converted = $this->currencyConverter($player_details->default_currency,$requestfromprovider["currency"],$requestfromprovider["amount"]);
            $update_deposit = DB::table('pay_transactions')
					    ->where('token_id', $player_details->token_id)
					    ->where('payment_id', 15) 
					    ->update(
					 	array(
                            'status_id'=> 5,
                            "from_currency" =>$converted[0]["currency_to"],
                            "input_amount"=>$requestfromprovider["amount"],
                            "exchange_rate"=>$converted[0]["exchange_rate"],
                            "amount" => $converted[0]["amount"],
                        ));
            $transaction =   DB::table('pay_transactions as pt')
                                    ->where("pt.token_id",$player_details->token_id)
                                    ->first(); 
            $request_status = 'SUCCESS';                

            $key = $transaction->id.'|'.$player_details->client_player_id.'|'.$request_status;
            $authenticationCode = hash_hmac("sha256",$player_details->client_id,$key);
            $http = new Client();
            $response = $http->post($transaction->trans_update_url,[
                'form_params' => [
                    'transaction_id' => $transaction->id,
                    'transaction_type'=>'DEPOSIT',
                    'order_id' => $transaction->orderId,
                    'amount'=>$transaction->amount,
                    'client_player_id' => $player_details->client_player_id,
                    'status' => "SUCCESS",
                    'message' => 'Thank you! Your Payment using TigerPay has successfully completed.',
                    'AuthenticationCode' => $authenticationCode
                ],
            ]); 
            $requesttoclient = [
                'transaction_id' => $transaction->id,
                'transaction_type'=>'DEPOSIT',
                'order_id' => $transaction->orderId,
                'amount'=>$transaction->amount,
                'client_player_id' => $player_details->client_player_id,
                'status' => "SUCCESS",
                'message' => 'Thank you! Your Payment using TigerPay has successfully completed.',
                'AuthenticationCode' => $authenticationCode
            ];
            PaymentHelper::savePayTransactionLogs($transaction->id,json_encode($requesttoclient),json_encode($transaction),"WMT Update Payment Transaction");
        }
        else{
            Helper::saveLog("WMT LOGS TEST",15,json_encode($requestfromprovider["txn_status"]),"test");
        }
        return 0;
    }


    private function _getClientDetails($type = "", $value = "") {

        $query = DB::table("clients AS c")
                 ->select('p.client_id', 'p.player_id','p.client_player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.token_id', 'pst.player_token' , 'pst.status_id', 'p.display_name', 'c.client_code','c.default_currency','c.default_language','c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
                 ->leftJoin("players AS p", "c.client_id", "=", "p.client_id")
                 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
                 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
                 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id");
                 
                if ($type == 'token') {
                    $query->where([
                        ["pst.player_token", "=", $value],
                        ["pst.status_id", "=", 1]
                    ]);
                }

                if ($type == 'player_id') {
                    $query->where([
                        ["p.player_id", "=", $value],
                        ["pst.status_id", "=", 1]
                    ]);
                }

                 $result= $query->first();

        return $result;

    }
    public function currencyConverter($client_currency,$player_currency,$amount){
        $currencies = PaymentHelper::currencyConverter($client_currency);
        $converted = array();
        foreach($currencies["rates"] as $currency){
            if($currency["currency"]== $player_currency){
                $finalconverted = array(
                    "currency_from" => $currencies["main_currency"],
                    "currency_to" => $player_currency,
                    "exchange_rate" => $currency["rate"],
                    "amount" => round($amount*$currency["rate"],2)
                );
                array_push($converted,$finalconverted);
            }
        }
        return $converted;
    }
}
