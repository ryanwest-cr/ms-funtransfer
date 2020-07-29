<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\PaymentGateway;
use App\Helpers\Helper;
use App\Helpers\PaymentHelper;
use App\PayTransaction;
use App\PayTransactionLogs;
use DB;
use GuzzleHttp\Client;

class PaymentGatewayController extends Controller
{
	//
    public function __construct(){

		$this->middleware('oauth', ['except' => ['updatetransaction','updatePayoutTransaction','catpayCallback']]);
		/*$this->middleware('authorize:' . __CLASS__, ['except' => ['index', 'store']]);*/
	}
    public function index(){
        $payment_gateway = PaymentGateway::all();
        return $payment_gateway;
    }



    public function getPlayerTokenId($player_id){

       $client_details = DB::table("players AS p")
                         ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.player_token' , 'pst.status_id','pst.token_id' , 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
                         ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
                         ->leftJoin("clients AS c", "c.client_id", "=", "p.client_id")
                         ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
                         ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id")
                         ->where("p.player_id", $player_id)
                         ->where("pst.status_id", 1)
                         ->latest('token_id')
                         ->first();

        return $client_details->token_id;    
        
    }


    public function paymentPortal(Request $request){

        // if($request->has("payment_method")){
        //     if(!PaymentHelper::paymentAvailabilityChecker($request->input("payment_method"))){
        //         return array("error"=>"Payment Method is not Available as of the moment");
        //     }
        // }
        /* REQUEST HAS PLAYER */
        $client_check = DB::table('clients')
                ->where('client_url', $request->site_url)
                ->first();


        if($client_check){  

                $player_check = DB::table('players')
                    ->where('client_id', $client_check->client_id)
                    ->where('username', $request->merchant_user)
                    ->first();



                if($player_check){

                    DB::table('player_session_tokens')->insert(
                            array('player_id' => $player_check->player_id, 'player_token' =>  $request->player_token, 'status_id' => '1')
                     );

                    $token_player_id = $this->getPlayerTokenId($player_check->player_id);     


                        // if($request->has("payment_method")&&$request->has("token_id")){
                        if($request->has("payment_method")&&$token_player_id != ''&&$request->has("trans_update_url")){

                        $payment_method = $request->input("payment_method");

                        if($payment_method == "coinspayment"){

                            if($request->has("amount")&&$request->has("currency")&&$request->has("dgcurrency")){
                                $dgcurrencyrate = $this->getCoinspaymentSingleRate($request->input("dgcurrency"));
                                $currency = (float)$this->getCurrencyConvertion($request->input("currency"));
                                $finalcurrency =((float)$request->input("amount")*$currency)/(float)$dgcurrencyrate;
                                $cointransaction = PaymentHelper::coinspayment($finalcurrency,$request->input("dgcurrency"));
                                if($cointransaction){
                                    $transaction = PaymentHelper::payTransactions($token_player_id,null,$cointransaction["purchaseid"],1,number_format($cointransaction["purchase_amount"], 2, '.', ''),2,1,$request->input("trans_update_url"),6);
                                    $trans_msg = array("pay_transaction_id"=>$transaction->id,
                                                        "txn_id"=> $cointransaction["txn_id"],
                                                        "digi_currency" =>$request->input("dgcurrency"),
                                                        "digi_currency_value"=>$finalcurrency,
                                                        "wallt_address"=>$cointransaction["wallet_address"],
                                                        "purchase_amount"=>number_format($cointransaction["purchase_amount"], 2, '.', ''),
                                                        "checkout_url"=>$cointransaction["checkout_url"],);
                                    return $trans_msg;
                                }
                            }
                            else{
                                return array("error"=>"Input Request is Invalid");
                            }
                        }
                        else if($payment_method == "vprica"){
                            if($request->has("cardnumber")&&$request->has("amount")){
                                $vprica_trans = PaymentHelper::vprica($request->input("cardnumber"),$request->input("amount"));
                                if($vprica_trans){
                                    return PaymentHelper::payTransactions($token_player_id,null,$vprica_trans["purchase_id"],3,$vprica_trans["purchase_amount"],2,1,$request->input("trans_update_url"),6);
                                }
                                else{
                                    return array("error"=>"Transaction Cannot be made");
                                }
                            }
                            else{
                                return array("error"=>"Input Request is Invalid");
                            }
                        }
                        else if($payment_method == "stripe"){
                            return "stripe";
                        }
                        else if($payment_method == "paymongo"){


                            if($request->has("cardnumber")&&$request->has("currency")&&$request->has("exp_month")&&$request->has("exp_year")&&$request->has("amount")&&$request->has("cvc")){

                            // dd($request->input("pmcurrency"));
                                $currency = (float)$this->getCurrencyConvertion($request->input("currency"));
                                $amount = $currency * (float)$request->input("amount");
                                $paymongo_trans = PaymentHelper::paymongo($request->input("cardnumber"),
                                $request->input("exp_year"),
                                $request->input("exp_month"),
                                $request->input("cvc"),
                                $amount,
                                "USD");
                                if(!empty($paymongo_trans)&&isset($paymongo_trans["purchase_id"])){
                                    return PaymentHelper::payTransactions($token_player_id,null,$paymongo_trans["purchase_id"],2,$paymongo_trans["equivalent_point"],2,1,$request->input("trans_update_url"),5);

                                    // return 'Success';
                                }
                                else{
                                    return array("error"=>"Card is invalid please check the input");
                                }
                            }
                            else{
                                return array("error"=>"PayMongo Input Request is Invalid");
                            }
                            
                        }
                        else if($payment_method == "bitgo"){
                            return "bitgo";
                        }
                        else if($payment_method == "ebanco"){
                            return "ebanco";
                        }
                        else{
                            return array("error"=>"Payment Gateway is not valid!");
                        }
                    }  /* END PAYMENT TYPE */
                    else{
                        return array("error"=>"Input Request is Invalid");
                    }

                }else{

                    DB::table('players')->insert(
                            array('client_id' => $client_check->client_id, 'client_player_id' =>  $request->merchant_user_id, 'username' => $request->merchant_user, 'email' => $request->merchant_user_email,'display_name' => $request->merchant_user_display_name)
                    );

                    $last_player_id = DB::getPDO()->lastInsertId();


                    $token_player_id = $this->getPlayerTokenId($last_player_id);

                    DB::table('player_session_tokens')->insert(
                            array('player_id' => $last_player_id, 'player_token' =>  $request->player_token, 'status_id' => '1')
                    );


                    if($request->has("payment_method")&&$token_player_id != ''){
                    $payment_method = $request->input("payment_method");
                    if($payment_method == "coinspayment"){
                        if($request->has("amount")&&$request->has("currency")&&$request->has("dgcurrency")){
                            $dgcurrencyrate = $this->getCoinspaymentSingleRate($request->input("dgcurrency"));//okiey
                            $currency = (float)$this->getCurrencyConvertion($request->input("currency"));
                            $finalcurrency =((float)$request->input("amount")*$currency)/(float)$dgcurrencyrate;
                            $cointransaction = PaymentHelper::coinspayment($finalcurrency,$request->input("dgcurrency"));
                            if($cointransaction){
                                $transaction = PaymentHelper::payTransactions($token_player_id,null,$cointransaction["purchaseid"],1,number_format($cointransaction["purchase_amount"], 2, '.', ''),2,1,$request->input("trans_update_url"),6);
                                $trans_msg = array("pay_transaction_id"=>$transaction->id,
                                                    "txn_id"=> $cointransaction["txn_id"],
                                                    "digi_currency" =>$request->input("dgcurrency"),
                                                    "digi_currency_value"=>$finalcurrency,
                                                    "wallt_address"=>$cointransaction["wallet_address"],
                                                    "purchase_amount"=>number_format($cointransaction["purchase_amount"], 2, '.', ''),
                                                    "checkout_url"=>$cointransaction["checkout_url"],);
                                return $trans_msg;
                            }
                        }
                        else{
                            return array("error"=>"Input Request is Invalid");
                        }
                    }
                    else if($payment_method == "vprica"){
                        if($request->has("cardnumber")&&$request->has("amount")){
                            $vprica_trans = PaymentHelper::vprica($request->input("cardnumber"),$request->input("amount"));
                            if($vprica_trans){
                                return PaymentHelper::payTransactions($token_player_id,null,$vprica_trans["purchase_id"],3,$vprica_trans["purchase_amount"],2,1,$request->input("trans_update_url"),6);
                            }
                            else{
                                return array("error"=>"Transaction Cannot be made");
                            }
                        }
                        else{
                            return array("error"=>"Input Request is Invalid");
                        }
                    }
                    else if($payment_method == "stripe"){
                        return "stripe";
                    }
                    else if($payment_method == "paymongo"){
                        if($request->has("cardnumber")&&$request->has("currency")&&$request->has("exp_month")&&$request->has("exp_year")&&$request->has("amount")&&$request->has("cvc")){
                            $currency = (float)$this->getCurrencyConvertion($request->input("currency"));
                            $amount = $currency * (float)$request->input("amount");
                            $paymongo_trans = PaymentHelper::paymongo($request->input("cardnumber"),
                            $request->input("exp_year"),
                            $request->input("exp_month"),
                            $request->input("cvc"),
                            $amount,
                            "USD");
                            if(!empty($paymongo_trans)&&isset($paymongo_trans["purchase_id"])){
                                return PaymentHelper::payTransactions($token_player_id,null,$paymongo_trans["purchase_id"],2,$paymongo_trans["equivalent_point"],2,1,$request->input("trans_update_url"),5);
                            }
                            else{
                                return array("error"=>"Card is invalid please check the input");
                            }
                        }
                        else{
                            return array("error"=>"PayMongo Input Request is Invalid");
                        }
                        
                    }
                    else if($payment_method == "bitgo"){
                        return "bitgo";
                    }
                    else if($payment_method == "ebanco"){
                        return "ebanco";
                    }
                    else{
                        return array("error"=>"Payment Gateway is not valid!");
                    }
                }  /* END PAYMENT TYPE */
                else{
                    return array("error"=>"Input Request is Invalid");
                }

            }


        } /* END CLIENT CHECK */
        else{
             return array("error"=>"Your Not Subscribed!");
        }
    }




    public function getCoinspaymentRate(){
        return PaymentHelper::getCoinspaymentRate();
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
    //New Code for QAICASH
    public function getQAICASHDepositMethod(Request $request){
        if($request->has("currency")){
            return PaymentHelper::QAICASHDepositMethod($request->input("currency"));
        }
        else{
            return array("error"=>"need to provide input currency");
        }
        
    }
    public function getQAICASHPayoutMethod(Request $request){
        if($request->has("currency")){
            return PaymentHelper::QAICASHPayoutMethod($request->input("currency"));
        }
        else{
            return array("error"=>"need to provide input currency");
        }
        
    }
    //new approved payout transaction
    public function approvedPayoutQAICASH(Request $request){
        if($request->has("transaction_id")&&$request->has("approved_by")){
            $qaicash_transaction = PaymentHelper::QAICASHPayoutApproved($request->input("transaction_id"),$request->input("approved_by"));
            return $qaicash_transaction;
        }
        else{
            return array("error"=>"Please Provide the Required Input");
        }
    }
    public function rejectPayoutQAICASH(Request $request){
        if($request->has("transaction_id")&&$request->has("rejected_by")){
            $qaicash_transaction = PaymentHelper::QAICASHPayoutReject($request->input("transaction_id"),$request->input("rejected_by"));
            return $qaicash_transaction;
            
        }
        else{
            return array("error"=>"Please Provide the Required Input");
        }
    }
    //new need to update makePayoutWAICASH
    public function makePayoutQAICASH(Request $request){
        $client_check = DB::table('clients')
                ->where('client_url', $request->site_url)
                ->first();


        if($client_check){  

                $player_check = DB::table('players')
                    ->where('client_id', $client_check->client_id)
                    ->where('username', $request->merchant_user)
                    ->first();


                    if($player_check){


                    DB::table('player_session_tokens')->insert(
                            array('player_id' => $player_check->player_id, 'player_token' =>  $request->player_token, 'status_id' => '1')
                     );

                    $token_player_id = $this->getPlayerTokenId($player_check->player_id);   
                      if($request->has("amount")&&
                           $request->has("currency")&&
                           $request->has("payout_method")&&
                           $request->has("witdrawer_UId")&&
                           $request->has("witdrawer_email")&&
                           $request->has("witdrawer_name")&&
                           $request->has("redirectUrl")){
                           $token_player_id = $token_player_id; ///please change here the @alyer token id
                           $qaicash_transaction = PaymentHelper::QAICASHMakePayout($request->input("amount"),$request->input("currency"),$request->input("payout_method"),$request->input("witdrawer_UId")
                                                                        ,$request->input("witdrawer_email"),$request->input("witdrawer_name"),$request->input("redirectUrl"));
                            $payment_trans = PaymentHelper::payTransactions($token_player_id,null,$qaicash_transaction["withdrawal_id"],9,$qaicash_transaction["withdrawal_amount"],1,2,$request->input("trans_update_url"),6);
                            if($payment_trans){
                                return array(
                                    "transaction_id"=>$payment_trans["id"],
                                    "identification_id"=>$qaicash_transaction["withdrawal_id"],
                                    "purchase_amount" =>$qaicash_transaction["withdrawal_amount"],
                                    "purchase_date" =>$qaicash_transaction["withdrawal_date"],
                                    "payment_page_url"=>$qaicash_transaction["payment_page_url"],
                                    "status"=>$qaicash_transaction["status"],
                                    "currency"=>$qaicash_transaction["currency"],
                                );
                            }
                            else{
                                return array("error"=> "Something Went Wrong");
                            }
                        }
                        else{
                            return array("error"=>"Please Provide the Required Input");
                        }


                    }else{

                        DB::table('players')->insert(
                                array('client_id' => $client_check->client_id, 'client_player_id' =>  $request->merchant_user_id, 'username' => $request->merchant_user, 'email' => $request->merchant_user_email,'display_name' => $request->merchant_user_display_name)
                        );

                        $last_player_id = DB::getPDO()->lastInsertId();


                        $token_player_id = $this->getPlayerTokenId($last_player_id);

                        DB::table('player_session_tokens')->insert(
                                array('player_id' => $last_player_id, 'player_token' =>  $request->player_token, 'status_id' => '1')
                        );


                             if($request->has("amount")&&
                               $request->has("currency")&&
                               $request->has("payout_method")&&
                               $request->has("witdrawer_UId")&&
                               $request->has("witdrawer_email")&&
                               $request->has("witdrawer_name")&&
                               $request->has("redirectUrl")){
                               $token_player_id = $last_player_id; ///please change here the @alyer token id
                               $qaicash_transaction = PaymentHelper::QAICASHMakePayout($request->input("amount"),$request->input("currency"),$request->input("payout_method"),$request->input("witdrawer_UId")
                                                                            ,$request->input("witdrawer_email"),$request->input("witdrawer_name"),$request->input("redirectUrl"));
                                $payment_trans = PaymentHelper::payTransactions($token_player_id,null,$qaicash_transaction["withdrawal_id"],9,$qaicash_transaction["withdrawal_amount"],1,2,$request->input("trans_update_url"),6);
                                if($payment_trans){
                                    return array(
                                        "transaction_id"=>$payment_trans["id"],
                                        "identification_id"=>$qaicash_transaction["withdrawal_id"],
                                        "purchase_amount" =>$qaicash_transaction["withdrawal_amount"],
                                        "purchase_date" =>$qaicash_transaction["withdrawal_date"],
                                        "payment_page_url"=>$qaicash_transaction["payment_page_url"],
                                        "status"=>$qaicash_transaction["status"],
                                        "currency"=>$qaicash_transaction["currency"],
                                    );
                                }
                                else{
                                    return array("error"=> "Something Went Wrong");
                                }
                            }
                            else{
                                return array("error"=>"Please Provide the Required Input");
                            }
                        }
                    

        } /* END CLIENT CHECK */
            else{
             return array("error"=>"Your Not Subscribed!");
        }                   
    }
    public function makeDepositQAICASH(Request $request){

        // return 1;

        $client_check = DB::table('clients')
                ->where('client_url', $request->site_url)
                ->first();

        if($client_check){  

            $player_check = DB::table('players')
                    ->where('client_id', $client_check->client_id)
                    ->where('username', $request->merchant_user)
                    ->first();

            if($player_check){  
                 DB::table('player_session_tokens')->insert(
                            array('player_id' => $player_check->player_id, 'player_token' =>  $request->player_token, 'status_id' => '1')
                 );
                 $token_player_id = $this->getPlayerTokenId($player_check->player_id);


                 /* QAICASH DEPOSIT */
                if($request->has("amount")&&
                   $request->has("currency")&&
                   $request->has("deposit_method")&&
                   $request->has("depositor_UId")&&
                   $request->has("depositor_email")&&
                   $request->has("depositor_name")&&
                   $request->has("redirectUrl")){
                   $token_player_id = $token_player_id; ///please change here the @alyer token id
                   $qaicash_transaction = PaymentHelper::QAICASHMakeDeposit($request->input("amount"),$request->input("currency"),$request->input("deposit_method"),$request->input("depositor_UId")
                                                                ,$request->input("depositor_email"),$request->input("depositor_name"),$request->input("redirectUrl"));

                    $payment_trans = PaymentHelper::payTransactions($token_player_id,null,$qaicash_transaction["purchase_id"],9,$qaicash_transaction["purchase_amount"],2,1,$request->input("trans_update_url"),6);
                    if($payment_trans){
                        return array(
                            "transaction_id"=>$payment_trans["id"],
                            "identification_id"=>$qaicash_transaction["purchase_id"],
                            "purchase_amount" =>$qaicash_transaction["purchase_amount"],
                            "purchase_date" =>$qaicash_transaction["purchase_date"],
                            "payment_page_url"=>$qaicash_transaction["payment_page_url"],
                            "status"=>$qaicash_transaction["status"],
                            "currency"=>$qaicash_transaction["currency"],
                        );
                    }
                    else{
                        return array("error"=> "Something Went Wrong");
                    }
                }
                else{
                    return array("error"=>"Please Provide the Required Input");
                }/* QAICASH DEPOSIT */

            }else{   //player not existing

                DB::table('players')->insert(
                            array('client_id' => $client_check->client_id, 'client_player_id' =>  $request->depositor_UId, 'username' => $request->merchant_user, 'email' => $request->depositor_email,'display_name' => $request->depositor_name)
                    );

                $last_player_id = DB::getPDO()->lastInsertId();
                $token_player_id = $this->getPlayerTokenId($last_player_id);
                DB::table('player_session_tokens')->insert(
                        array('player_id' => $last_player_id, 'player_token' =>  $request->player_token, 'status_id' => '1')
                );
           
                /* QAICASH DEPOSIT */
                if($request->has("amount")&&
                   $request->has("currency")&&
                   $request->has("deposit_method")&&
                   $request->has("depositor_UId")&&
                   $request->has("depositor_email")&&
                   $request->has("depositor_name")&&
                   $request->has("redirectUrl")){
                   $token_player_id = $token_player_id; ///please change here the @alyer token id
                   $qaicash_transaction = PaymentHelper::QAICASHMakeDeposit($request->input("amount"),$request->input("currency"),$request->input("deposit_method"),$request->input("depositor_UId")
                                                                ,$request->input("depositor_email"),$request->input("depositor_name"),$request->input("redirectUrl"));

                   $payment_trans = PaymentHelper::payTransactions($token_player_id,null,$qaicash_transaction["purchase_id"],9,$qaicash_transaction["purchase_amount"],2,1,$request->input("trans_update_url"),6);
                   if($payment_trans){
                    return array(
                        "transaction_id"=>$payment_trans["id"],
                        "identification_id"=>$qaicash_transaction["purchase_id"],
                        "purchase_amount" =>$qaicash_transaction["purchase_amount"],
                        "purchase_date" =>$qaicash_transaction["purchase_date"],
                        "payment_page_url"=>$qaicash_transaction["payment_page_url"],
                        "status"=>$qaicash_transaction["status"],
                        "currency"=>$qaicash_transaction["currency"],
                    );
                   }
                   else{
                       return array("error"=> "Something Went Wrong");
                   }
                }
                else{
                    return array("error"=>"Please Provide the Required Input");
                }/* QAICASH DEPOSIT */


            }
        } /* END CLIENT CHECK */
            else{
             return array("error"=>"Your Not Subscribed!");
        }        
    }
    //end of QAICASH
    public function updatetransaction(Request $request){
        $secret = "mwapimiddleware";
        $key = "thisisapisecret";
        $hmac = hash_hmac("sha256",$secret,$key);
        if($hmac == $request->hmac){
            $payment_method_id = 0;
            if($request->payment_method_code == "VPRICA"){
                $payment_method_id = 3;
            }
            elseif($request->payment_method_code == "COINSPAYMENT"){
                $payment_method_id = 1;
            }
            elseif($request->payment_method_code == "EBANCO"){
                $payment_method_id = 4;
            }
            elseif($request->payment_method_code == "QAICASH"){
                $payment_method_id = 9;
            }
            elseif($request->payment_method_code == "STRIPE"){
                $payment_method_id = 13;
            }
            $transaction = PayTransaction::where("identification_id",$request->identification_id)->where("payment_id",$payment_method_id)->first();
            $message = "";
            if($request->payment_method_code == "VPRICA"){
                if($request->status == "SUCCESS"){
                    $message = "Thank you! Your Payment using VPRICA has successfully completed.";
                }
                elseif($request->status == "FAILED"){
                    $message = "Hi! Your VPRICA with transaction number ".$transaction->id." has failed. Your VPRICA code number cannot be verified.";
                }
            }
            elseif($request->payment_method_code == "COINSPAYMENT"){
                if($request->status == "SUCCESS"){
                    $message = "Thank you! Your Payment using COINPAYMENT has successfully completed.";
                }
                elseif($request->status == "FAILED"){
                    $message = "Hi! Your COINPAYMENT with transaction number ".$transaction->id." has failed. The amount you deposit might not the same or you entered the wrong address.";
                }
                elseif($request->status == "HELD"){
                    $message = "Your Coinpayments transaction still on process.. The process will be completed for at least 10 minutes. Thank you";
                }
            }
            elseif($request->payment_method_code == "EBANCO"){
                if($request->status == "SUCCESS"){
                    $message = "Thank you! Your Payment using EBANCO has successfully completed.";
                }
                elseif($request->status == "FAILED"){
                    $message = "Hi! Your e-Banco.net with transaction number ".$transaction->id." has rejected. We cannot verify your bank transaction. Please settle this problem to our Customer Service.";
                }
            }
            elseif($request->payment_method_code == "QAICASH"){
                if($request->status == "SUCCESS"){
                    $message = "Thank you! Your Payment using QAICASH has successfully completed.";
                }
                elseif($request->status == "FAILED"){
                    $message = "Hi! Your QAICASH with transaction number ".$transaction->id." has failed.";
                }
            }
            elseif($request->payment_method_code == "STRIPE"){
                if($request->status == "SUCCESS"){
                    $message = "Thank you! Your Payment using STRIPE has successfully completed.";
                }
                elseif($request->status == "FAILED"){
                    $message = "Hi! Your STRIPE with transaction number ".$transaction->id." has failed.";
                }
            }
            if($transaction){
                if($request->status == "SUCCESS"){
                    $transaction->status_id=5;
                    $transaction->save();
                }
                elseif($request->status == "FAILED"){
                    $transaction->status_id=3;
                    $transaction->save();
                }
                elseif($request->status == "HELD"){
                    $transaction->status_id=7;
                    $transaction->save();
                }
                
                $client_player_id = DB::table('player_session_tokens as pst')
                                    ->select("p.client_player_id","p.client_id")
                                    ->leftJoin("players as p","pst.player_id","=","p.player_id")
                                    ->where("pst.token_id",$transaction->token_id)
                                    ->first();
                $key = $transaction->id.'|'.$client_player_id->client_player_id.'|'.$request->status;
                $authenticationCode = hash_hmac("sha256",$client_player_id->client_id,$key);
                $http = new Client();
                $response = $http->post($transaction->trans_update_url,[
                    'form_params' => [
                        'transaction_type'=> "DEPOSIT",
                        'transaction_id' => $transaction->id,
                        'orderId' => $transaction->orderId,
                        'amount'=> $transaction->amount,
                        'client_player_id' => $client_player_id->client_player_id,
                        'status' => $request->status,
                        'message' => $message,
                        'AuthenticationCode' => $authenticationCode
                    ],
                ]);
                $request_to_client = array(
                        'transaction_type'=> "DEPOSIT",
                        'transaction_id' => $transaction->id,
                        'orderId' => $transaction->orderId,
                        'amount'=> $transaction->amount,
                        'client_player_id' => $client_player_id->client_player_id,
                        'status' => $request->status,
                        'message' => $message,
                        'AuthenticationCode' => $authenticationCode
                );
                PaymentHelper::savePayTransactionLogs($transaction->id,json_encode($request_to_client),json_encode($response->getBody()),"Payment Update Transaction"); 
                return json_decode((string) $response->getBody(), true);
            }
            else{
                return array("error"=>"Transaction Did not exist");
            }
        }
        return array("error"=>"invalid authentication message");
    }
    public function updatePayoutTransaction(Request $request){
        $secret = "mwapimiddleware";
        $key = "thisisapisecret";
        $hmac = hash_hmac("sha256",$secret,$key);
        if($hmac == $request->hmac){

            if($request->payout_method_code == 'QAICASHPAYOUT')
            {  

                $transaction = PayTransaction::where("identification_id",$request->identification_id)->where("payment_id",11)->where("entry_id",1)->where("trans_type_id",2)->first();
                if($transaction){
                    if($request->status == "SUCCESS"){
                        $transaction->status_id=5;
                        $transaction->save();
                        $message = "Thank you! Your Payout using QAICASH has successfully completed.";
                    }
                    elseif($request->status == "HELD"){
                        $transaction->status_id=7;
                        $transaction->save();
                        $message = "Hi! Thank you for choosing Qaicash for Payout. Your request will be approved first by the management. We will notify and email you once it is approved.";
                    }
                    elseif($request->status == "FAILED"){
                        $transaction->status_id=3;
                        $transaction->save();
                        $message = "Hi! Your Qaicash Payout request with transaction number ".$transaction->id." has rejected. Maybe your account has insufficient balance. For any inconvenience, please call our Customer Service.";
                    }
                    $client_player_id = DB::table('player_session_tokens as pst')
                                        ->select("p.client_player_id","p.client_id")
                                        ->leftJoin("players as p","pst.player_id","=","p.player_id")
                                        ->where("pst.token_id",$transaction->token_id)
                                        ->first();
                    if($request->status == "HELD"){
                        $data = array(
                            "user_id" => $client_player_id->client_player_id,
                            "order_id" => $transaction->id,
                            "amount" => $transaction->amount,
                            "status_id" => 7
                        );
                        DB::table("withdraw")->insert($data);
                    }
                    $key = $transaction->id.'|'.$client_player_id->client_player_id.'|'.$request->status;
                    $authenticationCode = hash_hmac("sha256",$client_player_id->client_id,$key);
                    $http = new Client();
                    $response = $http->post($transaction->trans_update_url,[
                        'form_params' => [
                            'transaction_type'=> "PAYOUT",
                            'transaction_id' => $transaction->id,
                            'payoutId' => $transaction->orderId,
                            'amount'=> $transaction->amount,
                            'client_player_id' => $client_player_id->client_player_id,
                            'client_id' =>$client_player_id->client_id,
                            'status' => $request->status,
                            'message'=> $message,
                            'AuthenticationCode' => $authenticationCode
                        ],
                    ]);
                    $request_to_client = array(
                            'transaction_type'=> "PAYOUT",
                            'transaction_id' => $transaction->id,
                            'payoutId' => $transaction->orderId,
                            'amount'=> $transaction->amount,
                            'client_player_id' => $client_player_id->client_player_id,
                            'client_id' =>$client_player_id->client_id,
                            'status' => $request->status,
                            'message'=> $message,
                            'AuthenticationCode' => $authenticationCode
                    );
                    PaymentHelper::savePayTransactionLogs($transaction->id,json_encode($request),json_encode($response->getBody()),"Payout Update Transaction"); 
                    return json_decode((string) $response->getBody(), true);
                }
                else{
                    return array("error"=>"Transaction Did not exist");
                }

            }
            elseif($request->payout_method_code == 'IWALLETPAYOUT')
            {
                 $p_num = '10107'; // P Number
                 $user_name = 'wellTreasureTech'; // Username
                 $password = '06uf5z7HtQX'; // Iwallet Password
                 $account_number = '49425435'; // Account Number Sa 
                 $to_account = '65933077'; // Player Account No.

                // dd('yourhere');
                if($request->has("user_id")
                   &&$request->has("order_id")
                   &&$request->has("payment_id")
                   &&$request->has("status_id")){

                        $order_details = $this->_getOrderID($request->order_id, $request->payment_id);
                        $transaction_ext = DB::table('pay_transaction_logs')
                             ->where("transaction_id", $order_details->id)
                             ->latest()
                             ->first();
                        $pay_body = json_decode($transaction_ext->request);

                        if ($transaction_ext){
                            $client_player_id = DB::table('player_session_tokens as pst')
                            ->select("p.client_player_id","p.client_id")
                            ->leftJoin("players as p","pst.player_id","=","p.player_id")
                            ->where("p.player_id",$request->user_id)
                            ->first();

                            $key = $order_details->id.'|'.$client_player_id->client_player_id.'|SUCCESS';
                            $authenticationCode = hash_hmac("sha256",$client_player_id->client_id,$key);


                            if($request->status_id == 5){
                                    // MAKE DEPOSIT TO THE ACCOUNT OWNER IWALLET
                                    $make_remittance = $account_number.$password.$p_num.$pay_body->amount;
                                    $remi_cha1 = hash('sha256', $make_remittance);
                                    $http = new Client();
                                    $response = $http->post('https://test.iwl.world/api/MoneyRequest', [
                                        'form_params' => [
                                            'p_num' => $p_num,
                                            'signature' => $remi_cha1, 
                                            'from_account' => $account_number, 
                                            'to_account' => $pay_body->to_account, 
                                            'currency' => $pay_body->currency,
                                            'amount' =>  $pay_body->amount,
                                            'debit_currency' => $pay_body->currency,
                                        ]
                                    ]);
                                    $provider_response = json_decode($response->getBody()->getContents(), true);
                                    Helper::saveLog('iwalletRemitance', 2, json_encode($provider_response), 'Settled');
                                    $this->_updateTransaction($order_details->orderId, $order_details->id, 5);
                                    $this->_updateWithdraw($order_details->orderId, $request->user_id, 5);
                                    $http = new Client();
                                    $responsefromclient = $http->post($order_details->trans_update_url, [
                                        'form_params' => [
                                            'transaction_type'=> "PAYOUT",
                                            'transaction_id' => $order_details->id,
                                            'payoutId' => $order_details->orderId,
                                            'amount' => $order_details->amount,
                                            'client_player_id' => $client_player_id->client_player_id,
                                            'status' => "SUCCESS",
                                            'message' => 'Your iWallet withdrawal request with transaction number '.$order_details->id.'  has been approved. The payment has been tranferred to your account. Thank you!',
                                            'AuthenticationCode' => $authenticationCode
                                        ]
                                    ]);
                                    $requesttoclient = array(
                                        'transaction_type'=> "PAYOUT",
                                        'transaction_id' => $order_details->id,
                                        'payoutId' => $order_details->orderId,
                                        'amount' => $order_details->amount,
                                        'client_player_id' => $client_player_id->client_player_id,
                                        'status' => "SUCCESS",
                                        'message' => 'Your iWallet withdrawal request with transaction number '.$order_details->id.'  has been approved. The payment has been tranferred to your account. Thank you!',
                                        'AuthenticationCode' => $authenticationCode
                                    );
                                    PaymentHelper::savePayTransactionLogs($order_details->id,json_encode($requesttoclient, true), $responsefromclient->getBody(),"IWALLETPAYOUT UPDATE TRANSACTION");
                            }
                            elseif($request->status_id == 3){
                                $http = new Client();
                                $responsefromclient = $http->post($order_details->trans_update_url, [
                                    'form_params' => [
                                        'transaction_id' => $order_details->id,
                                        'payoutId' => $order_details->orderId,
                                        'amount' => $order_details->amount,
                                        'client_player_id' => $client_player_id->client_player_id,
                                        'status' => "FAILED",
                                        'message' => "Your iWallet withdrawal request with transaction number ".$order_details->id." has been disapproved. Please call Customer Service if you have trouble on this.",
                                        'AuthenticationCode' => $authenticationCode
                                    ]
                                ]);
                                $requesttoclient = array(
                                    'transaction_id' => $order_details->id,
                                    'payoutId' => $order_details->orderId,
                                    'amount' => $order_details->amount,
                                    'client_player_id' => $client_player_id->client_player_id,
                                    'status' => "FAILED",
                                    'message' => "Your iWallet withdrawal request with transaction number ".$order_details->id." has been disapproved. Please call Customer Service if you have trouble on this.",
                                    'AuthenticationCode' => $authenticationCode
                                );
                                $this->_updateTransaction($order_details->orderId, $order_details->id, 3);
                                $this->_updateWithdraw($order_details->orderId, $request->user_id, 3);
                                PaymentHelper::savePayTransactionLogs($order_details->id,json_encode($requesttoclient, true), $responsefromclient->getBody(),"IWALLETPAYOUT UPDATE TRANSACTION");
                            }
                        }
                }
                 else{
                    $response = array(
                        "error" => "INVALID_REQUEST",
                        "message" => "Invalid Iwallet input/missing input"
                    );
                    return response($response,401)->header('Content-Type', 'application/json');
                }

            }
            elseif($request->payout_method_code == 'WMTPAYOUT')
            {
                 $p_num = '10037'; // P Number
                 $user_name = 'Tiger202007API'; // Username
                 $password = '0xYvz4HteKf'; // Iwallet Password
                 $account_number = '530116'; // Account Number Sa 
                 $to_account = '65933077'; // Player Account No.

                // dd('yourhere');
                if($request->has("user_id")
                   &&$request->has("order_id")
                   &&$request->has("payment_id")
                   &&$request->has("status_id")){
                        
                        $order_details = $this->_getOrderID($request->order_id, $request->payment_id);
                        $transaction_ext = DB::table('pay_transaction_logs')
                             ->where("transaction_id", $order_details->id)
                             ->latest()
                             ->first();
                        $pay_body = json_decode($transaction_ext->request);
                        Helper::saveLog('WMT REMITTANCE', 2, json_encode($pay_body), 'Settled');
                        if ($transaction_ext){
                            $client_player_id = DB::table('player_session_tokens as pst')
                            ->select("p.client_player_id","p.client_id")
                            ->leftJoin("players as p","pst.player_id","=","p.player_id")
                            ->where("p.player_id",$request->user_id)
                            ->first();

                            $key = $order_details->id.'|'.$client_player_id->client_player_id.'|SUCCESS';
                            $authenticationCode = hash_hmac("sha256",$client_player_id->client_id,$key);
                            $make_remittance = $account_number.$password.$p_num.(int)$pay_body->amount;
                            $remi_cha1 = hash('sha256', $make_remittance);
                            $http = new Client();
                            $response = $http->post('https://test-wm7.andex.cc/api/MoneyRequest.php', [
                                'form_params' => [
                                    'p_num' => 10037,
                                    'signature' => $remi_cha1, 
                                    'from_account' => $account_number, 
                                    'to_account' => $pay_body->to_account,
                                    'trans_id' =>  $order_details->id,
                                    'currency' => $pay_body->currency,
                                    'amount' =>  $pay_body->amount,
                                    'debit_currency' => $pay_body->currency,
                                ]
                            ]);
                            $provider_response = json_decode($response->getBody()->getContents(), true);
                            return $provider_response;
                            if($request->status_id == 5){
                                    // MAKE DEPOSIT TO THE ACCOUNT OWNER WMT
                                    $make_remittance = $account_number.$password.$p_num.$pay_body->amount;
                                    $remi_cha1 = hash('sha256', $make_remittance);
                                    $http = new Client();
                                    $response = $http->post('https://test-wm7.andex.cc/api/MoneyRequest.php', [
                                        'form_params' => [
                                            'p_num' => $p_num,
                                            'signature' => $remi_cha1, 
                                            'from_account' => $account_number, 
                                            'to_account' => $pay_body->to_account, 
                                            'currency' => $pay_body->currency,
                                            'amount' =>  $pay_body->amount,
                                            'debit_currency' => $pay_body->currency,
                                        ]
                                    ]);
                                    $provider_response = json_decode($response->getBody()->getContents(), true);
                                    Helper::saveLog('WMT REMITTANCE', 2, json_encode($provider_response), 'Settled');
                                    $this->_updateTransaction($order_details->orderId, $order_details->id, 5);
                                    $this->_updateWithdraw($order_details->orderId, $request->user_id, 5);
                                    $http = new Client();
                                    $responsefromclient = $http->post($order_details->trans_update_url, [
                                        'form_params' => [
                                            'transaction_type'=> "PAYOUT",
                                            'transaction_id' => $order_details->id,
                                            'payoutId' => $order_details->orderId,
                                            'amount' => $order_details->amount,
                                            'client_player_id' => $client_player_id->client_player_id,
                                            'status' => "SUCCESS",
                                            'message' => 'Your Tiger Pay withdrawal request with transaction number '.$order_details->id.'  has been approved. The payment has been tranferred to your account. Thank you!',
                                            'AuthenticationCode' => $authenticationCode
                                        ]
                                    ]);
                                    $requesttoclient = array(
                                        'transaction_type'=> "PAYOUT",
                                        'transaction_id' => $order_details->id,
                                        'payoutId' => $order_details->orderId,
                                        'amount' => $order_details->amount,
                                        'client_player_id' => $client_player_id->client_player_id,
                                        'status' => "SUCCESS",
                                        'message' => 'Your Tiger Pay withdrawal request with transaction number '.$order_details->id.'  has been approved. The payment has been tranferred to your account. Thank you!',
                                        'AuthenticationCode' => $authenticationCode
                                    );
                                    PaymentHelper::savePayTransactionLogs($order_details->id,json_encode($requesttoclient, true), $responsefromclient->getBody(),"IWALLETPAYOUT UPDATE TRANSACTION");
                            }
                            elseif($request->status_id == 3){
                                $http = new Client();
                                $responsefromclient = $http->post($order_details->trans_update_url, [
                                    'form_params' => [
                                        'transaction_id' => $order_details->id,
                                        'payoutId' => $order_details->orderId,
                                        'amount' => $order_details->amount,
                                        'client_player_id' => $client_player_id->client_player_id,
                                        'status' => "FAILED",
                                        'message' => "Your iWallet withdrawal request with transaction number ".$order_details->id." has been disapproved. Please call Customer Service if you have trouble on this.",
                                        'AuthenticationCode' => $authenticationCode
                                    ]
                                ]);
                                $requesttoclient = array(
                                    'transaction_id' => $order_details->id,
                                    'payoutId' => $order_details->orderId,
                                    'amount' => $order_details->amount,
                                    'client_player_id' => $client_player_id->client_player_id,
                                    'status' => "FAILED",
                                    'message' => "Your iWallet withdrawal request with transaction number ".$order_details->id." has been disapproved. Please call Customer Service if you have trouble on this.",
                                    'AuthenticationCode' => $authenticationCode
                                );
                                $this->_updateTransaction($order_details->orderId, $order_details->id, 3);
                                $this->_updateWithdraw($order_details->orderId, $request->user_id, 3);
                                PaymentHelper::savePayTransactionLogs($order_details->id,json_encode($requesttoclient, true), $responsefromclient->getBody(),"IWALLETPAYOUT UPDATE TRANSACTION");
                            }
                        }
                }
                 else{
                    $response = array(
                        "error" => "INVALID_REQUEST",
                        "message" => "Invalid Iwallet input/missing input"
                    );
                    return response($response,401)->header('Content-Type', 'application/json');
                }

            }     
            else
            {
                return array("error"=>"invalid payout");
            }

        }
        return array("error"=>"invalid authentication message");
    }



    /*
     * Get order_id details
     */
    public function _getOrderID($order_id, $payment_id)
    {

        $order_check = DB::table('pay_transactions')
                     ->where('orderId', $order_id)
                     ->where('payment_id', $payment_id)
                     ->first();

        return $order_check;             
    }

    /*
     * Update Transaction Status
     */
    public function _updateTransaction($orderId, $id, $status)
    {
        $update_transaction = DB::table('pay_transactions')
            ->where('orderId', $orderId)
            ->where('id', $id)
            ->update(
            array(
                'status_id'=> $status,
        ));

        return $update_transaction;    
    }

    /*
     * Update Withdraw Status
     */
    public function _updateWithdraw($orderId, $user_id, $status)
    {
        $update_withdraw = DB::table('withdraw')
            ->where('order_id', $orderId)
            ->where('user_id', $user_id)
            ->update(
            array(
                'status_id' => $status,
        ));

        return $update_withdraw;    
    }
    public function catpayCallback(Request $request){
        PaymentHelper::savePayTransactionLogs(123,json_encode($request->getContent()), "","CATPAY CALLBACK TRANSACTION");
        $datafromprovider=array(
            "statusid"=>$request->statusId,
            "orderId"=>$request->orderId,
            "blockCPayOrder" => $request->blockCPayOrder,
            "orderPrice"=>$request->orderPrice,
            "message" =>$request->message,
            "sign"=>$request->sign,
        );
        $md5Key = "786eea43c64af4e8dc26dc0c1cb896ea";
        $transaction = PayTransaction::where("orderId",$request->orderId)->where("payment_id",14)->first();
        if($datafromprovider["message"]=='success' && $datafromprovider["statusid"]=='15')
        {
            $price =sprintf("%.2f",$datafromprovider["orderPrice"]);
            $mysign = md5($datafromprovider["blockCPayOrder"].$price.$datafromprovider["orderId"].$datafromprovider["statusid"].$datafromprovider["message"].$md5Key);
            if($mysign != $datafromprovider["sign"])
            {
                return 'Failed verification';
            }
            $transaction->status_id =5;
            $transaction->save();
            $client_player_id = DB::table('player_session_tokens as pst')
                                    ->select("p.client_player_id","p.client_id")
                                    ->leftJoin("players as p","pst.player_id","=","p.player_id")
                                    ->where("pst.token_id",$transaction->token_id)
                                    ->first();
            $key = $transaction->id.'|'.$client_player_id->client_player_id.'|SUCCESS';
            $authenticationCode = hash_hmac("sha256",$client_player_id->client_id,$key);
            $message = "Thank you! Your Payment using CATPAY has successfully completed.";
            $http = new Client();
            $response = $http->post($transaction->trans_update_url,[
                'form_params' => [
                    'transaction_type'=> "DEPOSIT",
                    'transaction_id' => $transaction->id,
                    'payoutId' => $transaction->orderId,
                    'amount'=> $transaction->amount,
                    'client_player_id' => $client_player_id->client_player_id,
                    'client_id' =>$client_player_id->client_id,
                    'status' => "SUCCESS",
                    'message'=> $message,
                    'AuthenticationCode' => $authenticationCode
                ],
            ]);
            $request_to_client = array(
                    'transaction_type'=> "DEPOSIT",
                    'transaction_id' => $transaction->id,
                    'payoutId' => $transaction->orderId,
                    'amount'=> $transaction->amount,
                    'client_player_id' => $client_player_id->client_player_id,
                    'client_id' =>$client_player_id->client_id,
                    'status' => "SUCCESS",
                    'message'=> $message,
                    'AuthenticationCode' => $authenticationCode
            );
            PaymentHelper::savePayTransactionLogs($transaction->id,json_encode($request),json_encode($response->getBody()),"Payout Update Transaction"); 
            return 'SUCCESS';
        }
        $transaction->status_id =3;
        $transaction->save();
        $client_player_id = DB::table('player_session_tokens as pst')
                                    ->select("p.client_player_id","p.client_id")
                                    ->leftJoin("players as p","pst.player_id","=","p.player_id")
                                    ->where("pst.token_id",$transaction->token_id)
                                    ->first();
        $key = $transaction->id.'|'.$client_player_id->client_player_id.'|'.$request->status;
        $authenticationCode = hash_hmac("sha256",$client_player_id->client_id,$key);
        $message = "Hi! Your STRIPE with transaction number ".$transaction->id." has failed.";
        $http = new Client();
        $response = $http->post($transaction->trans_update_url,[
            'form_params' => [
                'transaction_type'=> "DEPOSIT",
                'transaction_id' => $transaction->id,
                'payoutId' => $transaction->orderId,
                'amount'=> $transaction->amount,
                'client_player_id' => $client_player_id->client_player_id,
                'client_id' =>$client_player_id->client_id,
                'status' => "FAILED",
                'message'=> $message,
                'AuthenticationCode' => $authenticationCode
            ],
        ]);
        $request_to_client = array(
                'transaction_type'=> "DEPOSIT",
                'transaction_id' => $transaction->id,
                'payoutId' => $transaction->orderId,
                'amount'=> $transaction->amount,
                'client_player_id' => $client_player_id->client_player_id,
                'client_id' =>$client_player_id->client_id,
                'status' => "FAILED",
                'message'=> $message,
                'AuthenticationCode' => $authenticationCode
        );
        PaymentHelper::savePayTransactionLogs($transaction->id,json_encode($request),json_encode($response->getBody()),"Payout Update Transaction"); 
        return 'FAIL';
        PaymentHelper::savePayTransactionLogs(123,json_encode($request, true), json_encode($datafromprovider),"CATPAY CALLBACK TRANSACTION");
    }   
}
