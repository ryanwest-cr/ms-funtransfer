<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\PaymentGateway;
use App\Helpers\PaymentHelper;
use App\PayTransaction;
use DB;
use GuzzleHttp\Client;
class PaymentGatewayController extends Controller
{
    //
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

                            if($request->has("amount")&&$request->has("currency")){

                                $cointransaction = PaymentHelper::coinspayment($request->input("amount"),$request->input("currency"));

                                if($cointransaction){

                                    $transaction = PaymentHelper::payTransactions($token_player_id,$cointransaction["purchaseid"],1,$cointransaction["purchase_amount"],1,2,$request->input("trans_update_url"),6);

                                    $trans_msg = array("pay_transaction_id"=>$transaction->id,
                                                        "purchase_id"=>$cointransaction["purchaseid"],
                                                        "txn_id"=> $cointransaction["txn_id"],
                                                        "wallt_address"=>$cointransaction["wallet_address"],
                                                        "purchase_amount"=>$cointransaction["purchase_amount"],
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
                                    return PaymentHelper::payTransactions($token_player_id,$vprica_trans["purchase_id"],3,$vprica_trans["purchase_amount"],1,2,$request->input("trans_update_url"),6);
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

                                $paymongo_trans = PaymentHelper::paymongo($request->input("cardnumber"),
                                $request->input("exp_year"),
                                $request->input("exp_month"),
                                $request->input("cvc"),
                                $request->input("amount"),
                                $request->input("currency"));
                                if(!empty($paymongo_trans)&&isset($paymongo_trans["purchase_id"])){
                                    return PaymentHelper::payTransactions($token_player_id,$paymongo_trans["purchase_id"],2,$paymongo_trans["equivalent_point"],1,2,$request->input("trans_update_url"),5);

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
                        if($request->has("amount")&&$request->has("currency")){
                            $cointransaction = PaymentHelper::coinspayment($request->input("amount"),$request->input("currency"));
                            if($cointransaction){

                                $transaction = PaymentHelper::payTransactions($token_player_id,$cointransaction["purchase_id"],1,$cointransaction["purchase_amount"],1,2,$request->input("trans_update_url"),6);

                                $trans_msg = array("pay_transaction_id"=>$transaction->id,
                                "purchase_id"=>$cointransaction["purchase_id"],
                                                    "txn_id"=> $cointransaction["txn_id"],
                                                    "wallt_address"=>$cointransaction["wallet_address"],
                                                    "purchase_amount"=>$cointransaction["purchase_amount"],
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
                                return PaymentHelper::payTransactions($token_player_id,$vprica_trans["purchase_id"],3,$vprica_trans["purchase_amount"],1,2,$request->input("trans_update_url"),6);
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
                            $paymongo_trans = PaymentHelper::paymongo($request->input("cardnumber"),
                            $request->input("exp_year"),
                            $request->input("exp_month"),
                            $request->input("cvc"),
                            $request->input("amount"),
                            $request->input("currency"));
                            if(!empty($paymongo_trans)&&isset($paymongo_trans["purchase_id"])){
                                return PaymentHelper::payTransactions($token_player_id,$paymongo_trans["purchase_id"],2,$paymongo_trans["equivalent_point"],1,2,$request->input("trans_update_url"),5);
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

    public function updatetransaction(Request $request){
        $secret = "mwapimiddleware";
        $key = "thisisapisecret";
        $hmac = hash_hmac("sha256",$secret,$key);
        if($hmac == $request->hmac){
            $transaction = PayTransaction::where("identification_id",$request->identification_id)->first();
            if($transaction){
                $transaction->status_id=5;
                $transaction->save();
                $client_player_id = DB::table('player_session_tokens as pst')
                                    ->select("p.client_player_id")
                                    ->leftJoin("players as p","pst.player_id","=","p.player_id")
                                    ->where("pst.token_id",$transaction->token_id)
                                    ->first();
                $http = new Client();
                $response = $http->post($transaction->trans_update_url,[
                    'form_params' => [
                        'transaction_id' => $transaction->id,
                        'client_player_id' => $client_player_id->client_player_id,
                    ],
                ]); 
                return json_decode((string) $response->getBody(), true);
            }
            else{
                return array("error"=>"Transaction Did not exist");
            }
        }
        return array("error"=>"invalid authentication message");
    }




}
