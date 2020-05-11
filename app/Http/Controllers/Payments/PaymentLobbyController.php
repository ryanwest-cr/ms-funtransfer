<?php

namespace App\Http\Controllers\Payments;

use App\Helpers\Helper;
use App\Helpers\PaymentHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use App\PayTransaction;
use DB;
class PaymentLobbyController extends Controller
{
    //
    private $payment_lobby_url = "https://pay-test.betrnk.games";
    // private $payment_lobby_url = 'http://middleware.freebetrnk.com/public';
    // private $payment_lobby_url = "http://localhost:8001";
    public function paymentLobbyLaunchUrl(Request $request){
        if($request->has("callBackUrl")
            &&$request->has("exitUrl")
            &&$request->has("client_id")
            &&$request->has("player_id")
            &&$request->has("player_username")
            &&$request->has("amount")
            &&$request->has("payment_method")
            &&$request->has("orderId")
            &&$request->has("email")){
                if(!$this->minMaxAmountChecker($request->amount,$request->payment_method)){
                    $response = array(
                        "error" => "INVALID_AMOUNT",
                        "message" => "Amount Value is Invalid"
                    );
                    return response($response,401)->header('Content-Type', 'application/json');
                }
                $token = substr("abcdefghijklmnopqrstuvwxyz1234567890", mt_rand(0, 25), 1).substr(md5(time()), 1);
                if($token = Helper::checkPlayerExist($request->client_id,$request->player_id,$request->player_username,$request->email,$request->player_username,$token)){
                    $payment_method_code = "";
                    if($request->payment_method == "PAYMONGO")
                    {
                        /*
                        entry type 2 = credit
                        transaction type 1 = deposit
                        status 6 = pending
                        method_id = 2 paymongo
                        */
                        $payment_method = "paymongo";
                        $payment_method_code = "PAYMONGO";
                        $player_details = $this->_getClientDetails("token",$token);
                        $transaction = PaymentHelper::payTransactions($player_details->token_id,$request->input("orderId"),null,2,$request->input("amount"),2,1,$request->input("callBackUrl"),6);
                    }
                    elseif($request->payment_method == "COINSPAYMENT")
                    {
                        /*
                        entry type 2 = credit
                        transaction type 1 = deposit
                        status 6 = pending
                        method_id = 1 coinspayment
                        */
                        $payment_method = "coinspayment";
                        $payment_method_code = "COINSPAYMENT";
                        $player_details = $this->_getClientDetails("token",$token);
                        $transaction = PaymentHelper::payTransactions($player_details->token_id,$request->input("orderId"),null,1,$request->input("amount"),2,1,$request->input("callBackUrl"),6);
                    }
                    elseif($request->payment_method == "QAICASH")
                    {
                        /*
                        entry type 2 = credit
                        transaction type 1 = deposit
                        status 6 = pending
                        method_id = 9 qaicash
                        */
                        $payment_method = "qaicash";
                        $payment_method_code = "QAICASH";
                        $player_details = $this->_getClientDetails("token",$token);
                        $transaction = PaymentHelper::payTransactions($player_details->token_id,$request->input("orderId"),null,9,$request->input("amount"),2,1,$request->input("callBackUrl"),6);
                    }
                    elseif($request->payment_method == "VPRICA")
                    {
                        /*
                        entry type 2 = credit
                        transaction type 1 = deposit
                        status 6 = pending
                        method_id = 9 qaicash
                        */
                        $payment_method = "vprica";
                        $payment_method_code = "VPRICA";
                        $amount_usd = $request->amount * $this->getCurrencyConvertion("JPY");
                        $player_details = $this->_getClientDetails("token",$token);
                        $transaction = PaymentHelper::payTransactions($player_details->token_id,$request->input("orderId"),null,3,$amount_usd,2,1,$request->input("callBackUrl"),6);
                        $lang = "";
                        if($request->has("lang")){
                            $lang= $request->lang;
                        }
                        $response = array(
                            "transaction_id" => $transaction->id,
                            "orderId" => $transaction->orderId,
                            "payment_method" => $payment_method_code,
                            "usd_val" => round($amount_usd,2),
                            "url" => $this->payment_lobby_url."/".$payment_method."?payment_method=".$request->payment_method."&amount=".$request->amount."&token=".$token."&exitUrl=".$request->input("exitUrl")."&VAL=".$amount_usd."&lang=".$lang,
                            "status" => "PENDING"
                        );
                        PaymentHelper::savePayTransactionLogs($transaction->id,json_encode($request->getContent()),json_encode($response),"PaymentUrl"); 
                        return response($response,200)->header('Content-Type', 'application/json');
                    }
                    elseif($request->payment_method == "EBANCO")
                    {
                        /*
                        entry type 2 = credit
                        transaction type 1 = deposit
                        status 6 = pending
                        method_id = 4 ebanco
                        */
                        $payment_method = "ebanco";
                        $payment_method_code = "EBANCO";
                        $player_details = $this->_getClientDetails("token",$token);
                        $transaction = PaymentHelper::payTransactions($player_details->token_id,$request->input("orderId"),null,4,$request->input("amount"),2,1,$request->input("callBackUrl"),6);
                    }
                    elseif($request->payment_method == "IWALLET")
                    {
                        /*
                        entry type 2 = credit
                        transaction type 1 = deposit
                        status 6 = pending
                        method_id = 10 IWALLET
                        */
                        $payment_method = "iwallet";
                        $payment_method_code = "IWALLET";
                        $player_details = $this->_getClientDetails("token",$token);
                        $transaction = PaymentHelper::payTransactions($player_details->token_id,$request->input("orderId"),null,10,$request->input("amount"),2,1,$request->input("callBackUrl"),6);
                    }
                    else{
                        $response = array(
                            "error" => "INVALID_METHOD",
                            "message" => "Invalid payment method / payment method doesnt exist"
                        );
                        return response($response,402)->header('Content-Type', 'application/json');
                    }
                    $lang = "";
                    if($request->has("lang")){
                        $lang= $request->lang;
                    }
                    $response = array(
                        "transaction_id" => $transaction->id,
                        "orderId" => $transaction->orderId,
                        "payment_method" => $payment_method_code,
                        "url" => $this->payment_lobby_url."/".$payment_method."?payment_method=".$request->payment_method."&amount=".$request->amount."&token=".$token."&exitUrl=".$request->input("exitUrl")."&lang=".$lang,
                        "status" => "PENDING"
                    );
                    PaymentHelper::savePayTransactionLogs($transaction->id,json_encode($request->getContent()),json_encode($response),"PaymentUrl"); 
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
            if($this->checkPayTransaction($player_details->token_id)){
                $response = array(
                    "error" => "INVALID_REQUEST",
                    "message" => "Transaction are no longer in Payment Mode / Payment has been sent."
                );
                return response($response,401)->header('Content-Type', 'application/json');
            }
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
                            "amount" => $paymongo_transaction["equivalent_point"],
                            "status_id" => 5
                            );
                            $transaction = PaymentHelper::updateTransaction($data);
                            PaymentHelper::savePayTransactionLogs($transaction->id,json_encode($request->getContent()),json_encode($transaction),"PayMongo Payment Transaction");
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
                                        'orderId' => $transaction->orderId,
                                        "amount" => $paymongo_transaction["equivalent_point"],
                                        'client_player_id' => $client_player_id->client_player_id,
                                        'status' => "SUCCESS",
                                        'message' => 'Thank you! Your Payment using PAYMONGO has successfully completed.',
                                        'AuthenticationCode' => $authenticationCode
                                    ],
                                ]);
                                $datatorequest = array(
                                        'transaction_id' => $transaction->id,
                                        'orderId' => $transaction->orderId,
                                        "amount" => $paymongo_transaction["equivalent_point"],
                                        'client_player_id' => $client_player_id->client_player_id,
                                        'status' => "SUCCESS",
                                        'message' => 'Thank you! Your Payment using PAYMONGO has successfully completed.',
                                        'AuthenticationCode' => $authenticationCode
                                );
                                PaymentHelper::savePayTransactionLogs($transaction->id,json_encode($datatorequest),json_encode($response_client->getBody()),"PayMongo Payment Update Transaction"); 
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
                elseif($request->input("payment_method")== "COINSPAYMENT"){
                    if($request->has("amount")&&$request->has("currency")&&$request->has("digital_currency")){
                        $dgcurrencyrate = $this->getCoinspaymentSingleRate($request->input("digital_currency"));//okiey
                            $currency = (float)$this->getCurrencyConvertion($request->input("currency"));
                            $finalcurrency =((float)$request->input("amount")*$currency)/(float)$dgcurrencyrate;
                            $cointransaction = PaymentHelper::coinspayment($finalcurrency,$request->input("digital_currency"));
                            if($cointransaction){
                                $data = array(
                                    "token_id" => $player_details->token_id,
                                    "purchase_id" => $cointransaction["purchaseid"],
                                    "amount" => $cointransaction["purchase_amount"],
                                    "status_id" => 6
                                    );
                                $transaction = PaymentHelper::updateTransaction($data);
                                $response = array(
                                    "transaction_number" => $transaction->id,
                                    "order_id" => $transaction->orderId,
                                    "digi_currency" =>$request->input("digital_currency"),
                                    "digi_currency_value"=>$finalcurrency,
                                    "checkout_url"=>$cointransaction["checkout_url"],
                                    "wallt_address"=>$cointransaction["wallet_address"],
                                    "httpcode" => "SUCCESS",
                                    "message" => "Coinspayment Transaction is successful"
                                );
                                PaymentHelper::savePayTransactionLogs($transaction->id,json_encode($request->getContent()),json_encode($response),"Coinpayment Payment Transaction");
                                return response($response,200)->header('Content-Type', 'application/json');
                            }
                    }
                    else{
                        $response = array(
                            "error" => "INVALID_REQUEST",
                            "message" => "Invalid input / missing input in coinspayment"
                        );
                        return response($response,401)->header('Content-Type', 'application/json');
                    }
                    
                }
                elseif($request->input("payment_method") == "QAICASH"){
                    if($request->has("amount")&&$request->has("currency")&&$request->has("deposit_method")&&$request->has("exitUrl")){
                        $qaicash_transaction = PaymentHelper::QAICASHMakeDeposit($request->input("amount"),$request->input("currency"),$request->input("deposit_method"),$player_details->player_id
                                                                ,$player_details->email,$player_details->display_name,$request->input("exitUrl"));
                        if($qaicash_transaction){
                            $data = array(
                                "token_id" => $player_details->token_id,
                                "purchase_id" => $qaicash_transaction["purchase_id"],
                                "amount" => $qaicash_transaction["purchase_amount"],
                                "status_id" => 6
                                );
                            $transaction = PaymentHelper::updateTransaction($data);
                            $response = array(
                                "transaction_id"=>$transaction->id,
                                "order_id" => $transaction->orderId,
                                "payment_page_url"=>$qaicash_transaction["payment_page_url"],
                                "status"=>$qaicash_transaction["status"],
                                "currency"=>$qaicash_transaction["currency"],
                            );
                            $status="HELD";
                            $key = $transaction->id.'|'.$player_details->player_id.'|'.$status;
                            $authenticationCode = hash_hmac("sha256",$player_details->client_id,$key);
                            $http = new Client();
                            $responsefromclient = $http->post($transaction->trans_update_url,[
                                'form_params' => [
                                    'transaction_id' => $transaction->id,
                                    'orderId' => $transaction->orderId,
                                    'amount'=> $transaction->amount,
                                    'client_player_id' => $player_details->player_id,
                                    'status' => $status,
                                    'message' => "Hi! Thank you for choosing Qaicash for Payment. Your request will be approved first by the management. We will notify and email you once it is approved.",
                                    'AuthenticationCode' => $authenticationCode
                                ],
                            ]);
                            $requesttoclient = array(
                                    'transaction_id' => $transaction->id,
                                    'orderId' => $transaction->orderId,
                                    'amount'=> $transaction->amount,
                                    'client_player_id' => $player_details->player_id,
                                    'status' => $status,
                                    'message' => "Hi! Thank you for choosing Qaicash for Payment. Your request will be approved first by the management. We will notify and email you once it is approved.",
                                    'AuthenticationCode' => $authenticationCode
                            );
                            PaymentHelper::savePayTransactionLogs($transaction->id,json_encode($requesttoclient),json_encode($responsefromclient->getBody()),"QAICASH Payment Transaction");
                            return array(
                                "transaction_id"=>$transaction->id,
                                "order_id" => $transaction->orderId,
                                "payment_page_url"=>$qaicash_transaction["payment_page_url"],
                                "status"=>$qaicash_transaction["status"],
                                "currency"=>$qaicash_transaction["currency"],
                            );
                        }
                    }
                    else{
                        $response = array(
                            "error" => "INVALID_REQUEST",
                            "message" => "Invalid input / missing input in qaicash"
                        );
                        return response($response,401)->header('Content-Type', 'application/json');
                    }
                }
                elseif($request->input("payment_method") == "VPRICA"){
                    if($request->has("cardnumber")&&$request->has("amount")){
                        if(PaymentHelper::vpricaCardnumberExist($request->input("cardnumber"))){
                            $response = array(
                                "error" => "INVALID_REQUEST",
                                "message" => "Card code already exist. "
                            );
                            return response($response,401)->header('Content-Type', 'application/json');
                        }
                        $vprica_trans = PaymentHelper::vprica($request->input("cardnumber"),$request->input("amount"));
                        if($vprica_trans){
                            $data = array(
                                "token_id" => $player_details->token_id,
                                "purchase_id" => $vprica_trans["purchase_id"],
                                "amount" => $vprica_trans["purchase_amount"],
                                "status_id" => 6
                                );
                            $transaction = PaymentHelper::updateTransaction($data);
                            $response = array(
                                "transaction_id"=>$transaction->id,
                                "payment_method" => "VPRICA",
                                "order_id" => $transaction->orderId,
                                "status" => "HELD"
                            );
                            $status="HELD";
                            $key = $transaction->id.'|'.$player_details->player_id.'|'.$status;
                            $authenticationCode = hash_hmac("sha256",$player_details->client_id,$key);
                            $http = new Client();
                            $responsefromclient = $http->post($transaction->trans_update_url,[
                                'form_params' => [
                                    'transaction_id' => $transaction->id,
                                    'orderId' => $transaction->orderId,
                                    'amount'=> $transaction->amount,
                                    'client_player_id' => $player_details->player_id,
                                    'status' => $status,
                                    'message' => "Hi! Thank you for choosing VPRICA. The code number and the amount you filled in will be verified first. We will send notification and email once we verify and approve your payment.",
                                    'AuthenticationCode' => $authenticationCode
                                ],
                            ]);
                            $requesttoclient = array(
                                    'transaction_id' => $transaction->id,
                                    'orderId' => $transaction->orderId,
                                    'amount'=> $transaction->amount,
                                    'client_player_id' => $player_details->player_id,
                                    'status' => $status,
                                    'message' => "Hi! Thank you for choosing VPRICA. The code number and the amount you filled in will be verified first. We will send notification and email once we verify and approve your payment.",
                                    'AuthenticationCode' => $authenticationCode
                            );
                            PaymentHelper::savePayTransactionLogs($transaction->id,json_encode($requesttoclient),json_encode($responsefromclient->getBody()),"VPRICA Payment Transaction");
                            return array(
                                "transaction_id"=>$transaction->id,
                                "payment_method" => "VPRICA",
                                "order_id" => $transaction->orderId,
                                "status" => "PENDING"
                            );
                        }
                    }
                    else{
                        $response = array(
                            "error" => "INVALID_REQUEST",
                            "message" => "Invalid input / missing input in vprica"
                        );
                        return response($response,401)->header('Content-Type', 'application/json');
                    }
                }
                elseif($request->input("payment_method") == "EBANCO"){
                    if($request->has("amount")&&$request->has("bank_name")&&$request->has("currency")){
                        $currencyType = $request->input("currency");    
                        $currency = (float)$this->getCurrencyConvertion($currencyType);
                        $amount =((float)$request->input("amount")*$currency);                           
                        $ebanco_trans = PaymentHelper::ebanco($amount,$request->input("bank_name"));
                        if($ebanco_trans){
                            $data = array(
                                "token_id" => $player_details->token_id,
                                "purchase_id" => $ebanco_trans["deposit_id"],
                                "amount" => $amount,
                                "status_id" => 6
                                );
                            $transaction = PaymentHelper::updateTransaction($data);
                            $response = array(
                                "transaction_id"=>$transaction->id,
                                "payment_method" => "EBANCO",
                                "deposit_id"=>$ebanco_trans["deposit_id"],
                                "bank_name"=> $ebanco_trans["bank_name"],
                                "bank_account_no"=>$ebanco_trans["bank_account_no"],
                                "bank_account_name"=>$ebanco_trans["bank_account_name"],
                                "bank_branch_name"=>$ebanco_trans["bank_branch_name"],
                                "deposit_amount"=>$ebanco_trans["deposit_amount"],
                                "status"=>$ebanco_trans["status"],
                            );
                            $status="HELD";
                            $key = $transaction->id.'|'.$player_details->player_id.'|'.$status;
                            $authenticationCode = hash_hmac("sha256",$player_details->client_id,$key);
                            $http = new Client();
                            $responsefromclient = $http->post($transaction->trans_update_url,[
                                'form_params' => [
                                    'transaction_id' => $transaction->id,
                                    'orderId' => $transaction->orderId,
                                    'amount'=> $transaction->amount,
                                    'client_player_id' => $player_details->player_id,
                                    'status' => $status,
                                    'message' => "Hi! Thank you for choosing e-Banco.net. We will verify first your transaction from the bank you are choosing. We will send notification and email once we verify and approve your payment.",
                                    'AuthenticationCode' => $authenticationCode
                                ],
                            ]);
                            $requesttoclient = array(
                                    'transaction_id' => $transaction->id,
                                    'orderId' => $transaction->orderId,
                                    'amount'=> $transaction->amount,
                                    'client_player_id' => $player_details->player_id,
                                    'status' => $status,
                                    'message' => "Hi! Thank you for choosing e-Banco.net. We will verify first your transaction from the bank you are choosing. We will send notification and email once we verify and approve your payment.",
                                    'AuthenticationCode' => $authenticationCode
                            );
                            PaymentHelper::savePayTransactionLogs($transaction->id,json_encode($requesttoclient),json_encode($responsefromclient->getBody()),"EBANCO Payment Transaction");
                            return array(
                                "transaction_id"=>$transaction->id,
                                "payment_method" => "EBANCO",
                                "deposit_id"=>$ebanco_trans["deposit_id"],
                                "bank_name"=> $ebanco_trans["bank_name"],
                                "bank_account_no"=>$ebanco_trans["bank_account_no"],
                                "bank_account_name"=>$ebanco_trans["bank_account_name"],
                                "bank_branch_name"=>$ebanco_trans["bank_branch_name"],
                                "deposit_amount"=>$ebanco_trans["deposit_amount"],
                                "status"=>$ebanco_trans["status"],
                            );
                        }
                    }
                    else{
                        $response = array(
                            "error" => "INVALID_REQUEST",
                            "message" => "Invalid input / missing input in EBANCO"
                        );
                        return response($response,401)->header('Content-Type', 'application/json');
                    }
                }
                else{
                    $response = array(
                        "error" => "INVALID_REQUEST",
                        "message" => "Invalid Payment Method"
                    );
                    return response($response,401)->header('Content-Type', 'application/json');
                }

            }
            else{
                $response = array(
                    "error" => "TOKEN_INVALID",
                    "message" => "Invalid request token"
                );
                return response($response,401)->header('Content-Type', 'application/json');
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
    public function checkTokenExist(Request $request){
        if($request->has("token")){
            $token = DB::table('player_session_tokens')->where("player_token",$request->token)->first();
            if($token){
                $response = array(
                   "token" => $token->player_token,
                   "status" => "OK"
                ); 
                return response($response,200)->header('Content-Type', 'application/json');
            }
            else{
                $response = array(
                    "token" => null,
                    "status" => "Unauthorized or Unregistered URL"
                 ); 
                 return response($response,403)->header('Content-Type', 'application/json');
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
    public function getPaymentMethod(Request $request){
        $data = array();
        if($request->has("client_id")){
            if($this->clientExist($request->client_id)){
                $payment_methods = DB::table("payment_gateway")->where("transaction_id",1)->get();  
                foreach($payment_methods as $payment_method){
                    $disabled = DB::table("client_disabled_payment")->where("client_id",$request->client_id)->where("payment_id",$payment_method->id)->first();
                    if(!$disabled){
                        $payment_method_to_add = array(
                            "id" => $payment_method->id,
                            "payment_method_name" => $payment_method->name,
                            "payment_method_code" => $payment_method->payment_method_code,
                            "min_amount" => $payment_method->min_amount,
                            "max_amount" => $payment_method->max_amount
                        );
                        array_push($data,$payment_method_to_add);
                    }
                }
            }
            else{
                $response = array(
                    "error" => "UNAUTHORIZED_CLIENT",
                    "message" => "Invalid credential/client does not exist"
                );
                return response($response,403)->header('Content-Type', 'application/json');
            }
        }
        else{
            $response = array(
                "error" => "INVALID_REQUEST",
                "message" => "Invalid input / missing input"
            );
            return response($response,401)->header('Content-Type', 'application/json');
        }
        return $data;
    }
    public function getPayoutMethod(Request $request){
        $data = array();
        if($request->has("client_id")){
            if($this->clientExist($request->client_id)){
                $payment_methods = DB::table("payment_gateway")->where("transaction_id",2)->get();  
                foreach($payment_methods as $payment_method){
                    $disabled = DB::table("client_disabled_payment")->where("client_id",$request->client_id)->where("payment_id",$payment_method->id)->first();
                    if(!$disabled){
                        $payment_method_to_add = array(
                            "id" => $payment_method->id,
                            "payout_method_name" => $payment_method->name,
                            "payout_method_code" => $payment_method->payment_method_code,
                            "min_amount" => $payment_method->min_amount,
                            "max_amount" => $payment_method->max_amount
                        );
                        array_push($data,$payment_method_to_add);
                    }
                }
            }
            else{
                $response = array(
                    "error" => "UNAUTHORIZED_CLIENT",
                    "message" => "Invalid credential/client does not exist"
                );
                return response($response,403)->header('Content-Type', 'application/json');
            }
        }
        else{
            $response = array(
                "error" => "INVALID_REQUEST",
                "message" => "Invalid input / missing input"
            );
            return response($response,401)->header('Content-Type', 'application/json');
        }
        return $data;
    }
    public function payoutLobbyLaunchUrl(Request $request){
        if($request->has("callBackUrl")
            &&$request->has("exitUrl")
            &&$request->has("client_id")
            &&$request->has("player_id")
            &&$request->has("player_username")
            &&$request->has("amount")
            &&$request->has("payout_method")
            &&$request->has("payoutId")
            &&$request->has("email")){
                $token = substr("abcdefghijklmnopqrstuvwxyz1234567890", mt_rand(0, 25), 1).substr(md5(time()), 1);
                if($token = Helper::checkPlayerExist($request->client_id,$request->player_id,$request->player_username,$request->email,$request->player_username,$token)){
                    $payout_method_code = "";
                    
                    if($request->payout_method == "QAICASHPAYOUT")
                    {
                        /*
                        entry type 1 = debit
                        transaction type 2 = withdraw
                        status 6 = pending
                        method_id = 9 qaicash
                        */
                        $payout_method = "qaicashpayout";
                        $payout_method_code = "QAICASHPAYOUT";
                        $player_details = $this->_getClientDetails("token",$token);
                        $transaction = PaymentHelper::payTransactions($player_details->token_id,$request->input("payoutId"),null,11,$request->input("amount"),1,2,$request->input("callBackUrl"),6);
                    }
                    elseif($request->payout_method  == "IWALLETPAYOUT"){
                        
                        /*
                        entry type 1 = debit
                        transaction type 2 = withdraw
                        status 6 = pending
                        method_id = 12 iwallet payout
                        */
                        $payout_method = "iwalletpayout";
                        $payout_method_code = "IWALLETPAYOUT";
                        $player_details = $this->_getClientDetails("token",$token);
                        $transaction = PaymentHelper::payTransactions($player_details->token_id,$request->input("payoutId"),null,12,$request->input("amount"),1,2,$request->input("callBackUrl"),6);           
                        
                    }
                    else{
                        $response = array(
                            "error" => "INVALID_METHOD",
                            "message" => "Invalid payout method / payout method doesnt exist"
                        );
                        return response($response,402)->header('Content-Type', 'application/json');
                    }
                    $response = array(
                        "transaction_id" => $transaction->id,
                        "orderId" => $transaction->orderId,
                        "payment_method" => $payout_method_code,
                        "url" => $this->payment_lobby_url."/".$payout_method."?payout_method=".$request->payout_method."&amount=".$request->amount."&token=".$token."&exitUrl=".$request->input("exitUrl"),
                        "status" => "PENDING"
                    );
                    PaymentHelper::savePayTransactionLogs($transaction->id,json_encode($request->getContent()),json_encode($response),"PayoutUrl"); 
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
    public function payout(Request $request){
        if($request->has("payout_method")
           &&$request->has("amount")
           &&$request->has("token")){
            $player_details = $this->_getClientDetails("token",$request->input("token"));
            if($player_details){
                if($request->input("payout_method") == "QAICASHPAYOUT"){
                    if($request->has("amount")&&$request->has("currency")&&$request->has("qaicashpayout_method")&&$request->has("exitUrl")){
                        $qaicash_transaction = PaymentHelper::QAICASHMakePayout($request->input("amount"),$request->input("currency"),$request->input("qaicashpayout_method"),$player_details->player_id
                                                                ,$player_details->email,$player_details->display_name,$request->input("exitUrl"));
                        if($qaicash_transaction){
                            $data = array(
                                "token_id" => $player_details->token_id,
                                "purchase_id" => $qaicash_transaction["withdrawal_id"],
                                "amount" => $qaicash_transaction["withdrawal_amount"],
                                "status_id" => 6
                                );
                            $transaction = PaymentHelper::updateTransaction($data);
                            $response = array(
                                "transaction_id"=>$transaction->id,
                                "order_id" => $transaction->orderId,
                                "payout_page_url"=>$qaicash_transaction["payment_page_url"],
                                "status"=>$qaicash_transaction["status"],
                                "currency"=>$qaicash_transaction["currency"],
                            );
                            PaymentHelper::savePayTransactionLogs($transaction->id,json_encode($request->getContent()),json_encode($response),"QAICASH Payout Transaction");
                            return array(
                                "transaction_id"=>$transaction->id,
                                "order_id" => $transaction->orderId,
                                "payout_page_url"=>$qaicash_transaction["payment_page_url"],
                                "status"=>$qaicash_transaction["status"],
                                "currency"=>$qaicash_transaction["currency"],
                            );
                        }
                    }
                    else{
                        $response = array(
                            "error" => "INVALID_REQUEST",
                            "message" => "Invalid input / missing input in qaicash"
                        );
                        return response($response,401)->header('Content-Type', 'application/json');
                    }
                }
                elseif($request->input("payout_method") == "IWALLETPAYOUT"){

                    if($request->has("amount")&&$request->has("currency")){

                        $player_details = $this->_getClientDetails("token",$request->input("token"));

                        $transaction =   DB::table('pay_transactions as pt')
                        ->where("pt.token_id",$player_details->token_id)
                        ->first();

                        // UPDATE THE STATUS TO HELD AND ADD THE ACCOUNT_NUMBER
                        // status_id = 7 #HELD
                        $update_deposit = DB::table('pay_transactions')
                            ->where('orderId', $transaction->orderId)
                            ->where('token_id', $transaction->token_id)
                            ->where('id', $transaction->id)
                            ->update(
                                array(
                                     'status_id'=> 7,
                                     'to_acc_number'=> $request->input("to_account")
                        ));

                        $widthdraw_table = [
                            "user_id" => $player_details->player_id,
                            "order_id" => $transaction->orderId,
                            "payment_id" => $transaction->payment_id,
                            "amount" => $transaction->amount,
                            "status_id" => 7, 
                        ];
                        $data_saved = DB::table('withdraw')->insertGetId($widthdraw_table);  

                        // STORE THE REQUEST TO PAY_TRANS_EXT other data for the iwallet request
                        $pay_ext = [
                            "pay_trans_id" => $transaction->id,
                            "pay_body" => json_encode($request->all(), true),
                        ];
                        $data_saved = DB::table('pay_trans_ext')->insertGetId($pay_ext);

                        PaymentHelper::savePayTransactionLogs($transaction->id,json_encode($request->all(), true),'PENDING',"IWALLET Payout Transaction");

                        return array(
                                "transaction_id"=> $transaction->id,
                                "order_id" => $transaction->orderId,
                                "status"=> 'PENDING'
                        );

                    }
                    else{
                        $response = array(
                            "error" => "INVALID_REQUEST",
                            "message" => "Invalid input / missing input in iwallet"
                        );
                        return response($response,401)->header('Content-Type', 'application/json');
                    }
                }
                else{
                    $response = array(
                        "error" => "INVALID_REQUEST",
                        "message" => "Invalid Payout Method"
                    );
                    return response($response,401)->header('Content-Type', 'application/json');
                }

            }
            else{
                $response = array(
                    "error" => "TOKEN_INVALID",
                    "message" => "Invalid request token"
                );
                return response($response,401)->header('Content-Type', 'application/json');
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
    private function clientExist($client_id){
        $client = DB::table("clients")->where("client_id",$client_id)->first();
        if($client){
            return true;
        }
        else{
            return false;
        }
    }
    private function minMaxAmountChecker($amount,$method){
        $min_max = DB::table("payment_gateway")->where("payment_method_code",$method)->first();
        if($amount >= $min_max->min_amount && $amount <= $min_max->max_amount){
            return true;
        }
        return  false;
    }
    public function getPayTransactionDetails(Request $request){
        $get_token_id = $this->_getClientDetails("token",$request->token);
        $transaction = PayTransaction::where("token_id",$get_token_id->token_id)->first();
        return $transaction;
    }
    public function cancelPayTransaction(Request $request){
        $get_token_id = $this->_getClientDetails("token",$request->token);
        $deleted = PayTransaction::where("token_id",$get_token_id->token_id)->delete();
    }
    private function checkPayTransaction($token_id){
        $transaction = PayTransaction::where("token_id",$token_id)->first();
        if($transaction->identification_id){
            return true;
        }
    }
    
}
