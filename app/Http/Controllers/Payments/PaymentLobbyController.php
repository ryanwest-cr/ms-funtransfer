<?php

namespace App\Http\Controllers\Payments;

use App\Helpers\Helper;
use App\Helpers\PaymentHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use DB;
class PaymentLobbyController extends Controller
{
    //
    private $payment_lobby_url = "https://pay-test.betrnk.games";
    public function paymentLobbyLaunchUrl(Request $request){
        if($request->has("callBackUrl")
            &&$request->has("exitUrl")
            &&$request->has("client_id")
            &&$request->has("player_id")
            &&$request->has("player_username")
            &&$request->has("amount")
            &&$request->has("payment_method")
            &&$request->has("orderId")){
                $token = substr("abcdefghijklmnopqrstuvwxyz1234567890", mt_rand(0, 25), 1).substr(md5(time()), 1);
                if($token = Helper::checkPlayerExist($request->client_id,$request->player_id,$request->player_username,$email=null,$request->player_username,$token)){
                    if($request->payment_method == "PAYMONGO")
                    {
                        /*
                        entry type 2 = credit
                        transaction type 1 = deposit
                        status 6 = pending
                        method_id = 2 paymongo
                        */
                        $payment_method = "paymongo";
                        $player_details = $this->_getClientDetails("token",$token);
                        $transaction = PaymentHelper::payTransactions($player_details->token_id,$request->input("orderId"),null,2,$request->input("amount"),2,1,$request->input("callBackUrl"),6);
                    }
                    if($request->payment_method == "COINSPAYMENT")
                    {
                        /*
                        entry type 2 = credit
                        transaction type 1 = deposit
                        status 6 = pending
                        method_id = 1 coinspayment
                        */
                        $payment_method = "coinspayment";
                        $player_details = $this->_getClientDetails("token",$token);
                        $transaction = PaymentHelper::payTransactions($player_details->token_id,$request->input("orderId"),null,1,$request->input("amount"),2,1,$request->input("callBackUrl"),6);
                    }
                    $response = array(
                        "transaction_id" => $transaction->id,
                        "orderId" => $transaction->orderId,
                        "payment_method" => "PAYMONGO",
                        "url" => $this->payment_lobby_url."/".$payment_method."?payment_method=".$request->payment_method."&amount=".$request->amount."&token=".$token."&exitUrl=".$request->input("exitUrl"),
                        "status" => "PENDING"
                    ); 
                    return response($response,200)->header('Content-Type', 'application/json');
                }
        }
        else{
            $response = array(
                "error" => "INVALID_REQUEST",
                "message" => "Invalid input / missing input"
            );
            return response($response,401)->header('Content-Type', 'application/json');
        }
    }
    public function payment(Request $request){
        if($request->has("payment_method")
           &&$request->has("amount")
           &&$request->has("token")){
            $player_details = $this->_getClientDetails("token",$request->input("token"));
            if($player_details){
                if($request->input("payment_method")== "PAYMONGO"){
                    if($request->has("cardnumber")
                    &&$request->has("exp_month")
                    &&$request->has("exp_year")
                    &&$request->has("cvc")
                    &&$request->has("currency")){
                        $paymongo_transaction = PaymentHelper::paymongo($request->input("cardnumber"),$request->input("exp_year"),$request->input("exp_month"),$request->input("cvc"),$request->input("amount"),$request->input("currency"));
                        if($paymongo_transaction){
                            $data = array(
                            "token_id" => $player_details->token_id,
                            "purchase_id" => $paymongo_transaction["purchase_id"],
                            "status_id" => 5
                            );
                            $transaction = PaymentHelper::updateTransaction($data);
                            try{
                                $client_player_id = DB::table('player_session_tokens as pst')
                                    ->select("p.client_player_id","p.client_id")
                                    ->leftJoin("players as p","pst.player_id","=","p.player_id")
                                    ->where("pst.token_id",$transaction->token_id)
                                    ->first();
                                $key = $transaction->id.'|'.$client_player_id->client_player_id.'|SUCCESS';
                                $authenticationCode = hash_hmac("sha256",$client_player_id->client_id,$key);
                                $http = new Client();
                                $response_client = $http->post($transaction->trans_update_url,[
                                    'form_params' => [
                                        'transaction_id' => $transaction->id,
                                        'client_player_id' => $client_player_id->client_player_id,
                                        'status' => "SUCCESS",
                                        'message' => 'Your Transaction Order '.$transaction->id.'has been updated to SUCCESS',
                                        'AuthenticationCode' => $authenticationCode
                                    ],
                                ]); 
                            }
                            catch(ClientException $e){
                                $client_response = $e->getResponse();
                                $response = json_decode($client_response->getBody()->getContents(),True);
                                return response($response,200)
                                ->header('Content-Type', 'application/json');
                            }
                            catch(ConnectException $e){
                                $response = array(
                                    "error" => "CONNECT_ERROR",
                                    "message" => "Incorrect callBackUrl/callBackUrl is not found"
                                );
                                return response($response,200)
                                ->header('Content-Type', 'application/json');
                            }
                            $response = array(
                                "transaction_number" => $transaction->id,
                                "httpcode" => "SUCCESS",
                                "message" => "Paymongo Transaction is successful"
                            );
                            return response($response,200)->header('Content-Type', 'application/json');
                        }
                        else{
                            $response = array(
                                "error" => "INVALID_REQUEST",
                                "message" => "Invalid Paymongo input/missing input"
                            );
                            return response($response,401)->header('Content-Type', 'application/json');
                        }
                    }
                    else{
                        $response = array(
                            "error" => "INVALID_REQUEST",
                            "message" => "Invalid Paymongo input/missing input"
                        );
                        return response($response,401)->header('Content-Type', 'application/json');
                    }
                }
                if($request->input("payment_method")== "COINSPAYMENT"){
                    if($request->has("amount")&&$request->has("currency")&&$request->has("digital_currency")){
                        $dgcurrencyrate = $this->getCoinspaymentSingleRate($request->input("dgcurrency"));//okiey
                            $currency = (float)$this->getCurrencyConvertion($request->input("currency"));
                            $finalcurrency =((float)$request->input("amount")*$currency)/(float)$dgcurrencyrate;
                            $cointransaction = PaymentHelper::coinspayment($finalcurrency,$request->input("digital_currency"));
                            if($cointransaction){
                                $data = array(
                                    "token_id" => $player_details->token_id,
                                    "purchase_id" => $paymongo_transaction["purchase_id"],
                                    "status_id" => 6
                                    );
                                $transaction = PaymentHelper::updateTransaction($data);
                                $response = array(
                                    "transaction_number" => $transaction->id,
                                    "httpcode" => "SUCCESS",
                                    "message" => "Coinspayment Transaction is successful"
                                );
                                return response($response,200)->header('Content-Type', 'application/json');
                            }
                    }
                }
            }
        }
        else{
            $response = array(
                "error" => "INVALID_REQUEST",
                "message" => "Invalid input / missing input"
            );
            return response($response,401)->header('Content-Type', 'application/json');
        }
    }
    public function getCoinspaymentSingleRate($dgcurrency){
        $currencies = PaymentHelper::getCoinspaymentRate();
        foreach($currencies as $currency){
            if($dgcurrency == $currency["currency"]){
                return $currency["rate"];
            }
         }
    }
    public function getCurrencyConvertion($input_currency){
        $currency = PaymentHelper::currency();
        foreach($currency["rates"] as $currency){
            if($currency["currency"] == $input_currency){
                return $currency["rate"];
            }
        }
    }
    private function _getClientDetails($type = "", $value = "") {

		$query = DB::table("clients AS c")
				 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.token_id', 'pst.player_token' , 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
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
}
