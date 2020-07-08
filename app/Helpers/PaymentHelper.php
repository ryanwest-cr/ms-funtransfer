<?php
namespace App\Helpers;
use DB;
use GuzzleHttp\Client;
use App\PayTransaction;
use App\PayTransactionLogs;
use Carbon\Carbon;
class PaymentHelper
{
    public static function connectTo(){
        $http = new Client();
        // $response = $http->post('http://localhost:8000/oauth/token', [
        //     'form_params' => [
        //         'grant_type' => 'password',
        //         'client_id' => '3',
        //         'client_secret' => 'uAthPzJR6lk9hrgPljMUjzGHjnPvtT2Ps6eLHRv7',
        //         'username' => 'randybaby@gmail.com',
        //         'password' => 'epointexchange.com',
        //         'scope' => '*',
        //     ],
        // ]);

         $response = $http->post('https://epointexchange.com/oauth/token', [
            'form_params' => [
                'grant_type' => 'password',
                'client_id' => '3',
                'client_secret' => 'uAthPzJR6lk9hrgPljMUjzGHjnPvtT2Ps6eLHRv7',
                'username' => 'api@betrnk.games',
                'password' => ']WKtkT``mJCe8N3J',
                'scope' => '*',
            ],
        ]);

        return json_decode((string) $response->getBody(), true)["access_token"];
    }
    public static function ebancoConnectTo(){
        $http = new Client();
 
          $response = $http->post('https://e-banco.net/oauth/token', [
            'form_params' => [
              'grant_type' => 'password',
               'client_id' => '5',
               'client_secret' => 'o6xxbH3bYbTcZOIcrLqRx0YVLDxhUHD28G03cfcr',
               'username' => 'mychan@ash.gg',
               'password' => 'charoot1223',
               'scope' => '*',
             ],
        ]);
 
        return json_decode((string) $response->getBody(), true)["access_token"];
 
     }
    public static function paymongo($cardnumber,$exp_year,$exp_month,$cvc,$amount,$currency,$returnUrl){

        $http = new Client();
        $response = $http->post('https://epointexchange.com/api/v1/paymentportal/paymongo2', [
            'form_params' => [
                'pmcurrency' => $currency,
                'amount' => $amount,
                'cardnumber' => $cardnumber,
                'expmonth' => $exp_month,
                'expyear' => $exp_year,
                'cvc' => $cvc,
                'returnUrl'=>$returnUrl,
            ],
            'headers' =>[
                'Authorization' => 'Bearer '.PaymentHelper::connectTo(),
                'Accept'     => 'application/json' 
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
    public static function stripePayment($cardnumber,$exp_year,$exp_month,$cvc,$amount,$currency,$returnUrl){
        $http = new Client();
        $response = $http->post('https://epointexchange.com/api/v1/paymentportal/paymenintent', [
            'form_params' => [
                'currency' => $currency,
                'amount' => $amount,
                'cardnumber' => $cardnumber,
                'exp_month' => $exp_month,
                'exp_year' => $exp_year,
                'cvc' => $cvc,
                'returnUrl'=>$returnUrl,
            ],
            'headers' =>[
                'Authorization' => 'Bearer '.PaymentHelper::connectTo(),
                'Accept'     => 'application/json' 
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
    public static function paymongoUpdate($orderId){

        $http = new Client();
        $response = $http->post('https://epointexchange.com/api/v1/paymentportal/paymongo_checkupdate', [
            'form_params' => [
                'orderId' => $orderId,
            ],
            'headers' =>[
                'Authorization' => 'Bearer '.PaymentHelper::connectTo(),
                'Accept'     => 'application/json' 
            ]
        ]);

        return json_decode($response->getBody()->getContents(), true);
    }
    public static function vprica($cardnumber,$amount){
        $http = new Client();
        // $response = $http->post('http://localhost:8000/api/v1/paymentportal/vprica', [
        $response = $http->post('https://epointexchange.com/api/v1/paymentportal/vprica', [
            'form_params' => [
                'amount' => $amount,
                'cardnumber' => $cardnumber,
            ],
            'headers' =>[
                'Authorization' => 'Bearer '.PaymentHelper::connectTo(),
                'Accept'     => 'application/json' 
            ]
        ]);
        return json_decode($response->getBody(),TRUE);
    }
    public static function vpricaCardnumberExist($cardnumber){
        $http = new Client();
        // $response = $http->post('http://localhost:8000/api/v1/paymentportal/vprica', [
        $response = $http->post('https://epointexchange.com/api/v1/paymentportal/vprica_check_exist', [
            'form_params' => [
                'cardnumber' => $cardnumber,
            ],
            'headers' =>[
                'Authorization' => 'Bearer '.PaymentHelper::connectTo(),
                'Accept'     => 'application/json' 
            ]
        ]);
        return json_decode($response->getBody(),TRUE);
    }

    //coinspayment
    public static function getCoinspaymentRate(){
        $http = new Client();
        // $response = $http->get('http://localhost:8000/api/v1/paymentportal/coinpayment_rate', [
        $response = $http->get('https://epointexchange.com/api/v1/paymentportal/coinpayment_rate', [
            'headers' =>[
                'Authorization' => 'Bearer '.PaymentHelper::connectTo(),
                'Accept'     => 'application/json' 
            ]
        ]);
        return json_decode((string) $response->getBody(), true);
    }
    public static function coinspayment($amount,$currency){

        // dd('here you go!');

        $http = new Client();
        // $response = $http->post('http://localhost:8000/api/v1/paymentportal/coinpayment_transaction', [
        $response = $http->post('https://epointexchange.com/api/v1/paymentportal/coinpayment_transaction', [
            'form_params' => [
                'amount' => $amount,
                'currency' => $currency,
            ],
            'headers' =>[
                'Authorization' => 'Bearer '.PaymentHelper::connectTo(),
                'Accept'     => 'application/json' 
            ]
        ]);
        return json_decode((string) $response->getBody(), true);
    }
    public static function ebanco($amount,$bankname){
            $http = new Client();
            $response = $http->post('https://e-banco.net/api/v1/makedeposit', [
               'headers' =>[
                   'Authorization' => 'Bearer '.PaymentHelper::ebancoConnectTo(),
                   'Accept'     => 'application/json' 
               ],
               'form_params' => [
                          'amount' => $amount,
                          'bankname' => $bankname
                       ],
            ]);

            return json_decode((string) $response->getBody(), true);
    }
    public static function currency(){
        $client = new Client([
            'headers' => ['x-rapidapi-host' => 'currency-converter5.p.rapidapi.com',
            'x-rapidapi-key' => '8206256315mshcd8655ee7f5800dp1bf51bjsn355caa8858be',
            'Content-Type' => 'application/x-www-form-urlencoded'],
            'http_errors' => false,
        ]);
        $response = $client->get('https://currency-converter5.p.rapidapi.com/currency/convert', [
        ]);
        $data = json_decode($response->getBody(),TRUE);
        $currency = array(
            "main_currency"=>"USD",
            "rates"=>array()
        );
        foreach($data["rates"] as $key=>$rate){
            $usdcurrency = array(
                "currency" =>$key,
                "currency_name"=>$rate["currency_name"],
                "rate" => number_format((1/(float)$rate["rate"])/(1/(float)$data["rates"]["USD"]["rate"]), 5, '.', ''),
            );
            array_push($currency["rates"],$usdcurrency);
        }
        return $currency;
    }
    public static function currencyConverter($currency){
        $client = new Client([
            'headers' => ['x-rapidapi-host' => 'currency-converter5.p.rapidapi.com',
            'x-rapidapi-key' => '8206256315mshcd8655ee7f5800dp1bf51bjsn355caa8858be',
            'Content-Type' => 'application/x-www-form-urlencoded'],
            'http_errors' => false,
        ]);
        $response = $client->get('https://currency-converter5.p.rapidapi.com/currency/convert', [
        ]);
        $data = json_decode($response->getBody(),TRUE);
        $currencylist = array(
            "main_currency"=>$currency,
            "rates"=>array()
        );
        foreach($data["rates"] as $key=>$rate){
            $currencyconverted = array(
                "currency" =>$key,
                "currency_name"=>$rate["currency_name"],
                "rate" => number_format((1/(float)$rate["rate"])/(1/(float)$data["rates"][$currency]["rate"]), 5, '.', ''),
            );
            array_push($currencylist["rates"],$currencyconverted);
        }
        return $currencylist;
    }

    ///endcoinspayment


    //03-24-20
    public static function QAICASHDepositMethod($currency){
        $http = new Client();
        $response = $http->post('https://epointexchange.com/api/qaicash/depositmethods',[
            'form_params' => [
                'currency' => $currency,
            ],
            'headers' =>[
                'Authorization' => 'Bearer '.PaymentHelper::connectTo(),
                'Accept'     => 'application/json' 
            ]
        ]);
        return json_decode((string) $response->getBody(), true);
    }
    //04-01-20
    public static function QAICASHPayoutMethod($currency){
        $http = new Client();
        $response = $http->post('https://epointexchange.com/api/qaicash/payoutmethods',[
            'form_params' => [
                'currency' => $currency,
            ],
            'headers' =>[
                'Authorization' => 'Bearer '.PaymentHelper::connectTo(),
                'Accept'     => 'application/json' 
            ]
        ]);
        return json_decode((string) $response->getBody(), true);
    }

    public static function QAICASHPayoutApproved($order_id,$approved_by){
        $transaction = PayTransaction::find($order_id);
        $http = new Client();
        $response = $http->post('https://epointexchange.com/api/qaicash/approvewithdraw',[
            'form_params' => [
                'order_id' => $transaction->identification_id,
                'approved_by' => $approved_by
            ],
            'headers' =>[
                'Authorization' => 'Bearer '.PaymentHelper::connectTo(),
                'Accept'     => 'application/json' 
            ]
        ]);
        return json_decode((string) $response->getBody(), true);
    }
    public static function QAICASHPayoutReject($order_id,$rejected_by){
        $transaction = PayTransaction::find($order_id);
        $http = new Client();
        $response = $http->post('https://epointexchange.com/api/qaicash/rejectwithdraw',[
            'form_params' => [
                'order_id' => $transaction->identification_id,
                'rejected_by' => $rejected_by
            ],
            'headers' =>[
                'Authorization' => 'Bearer '.PaymentHelper::connectTo(),
                'Accept'     => 'application/json' 
            ]
        ]);
        return json_decode((string) $response->getBody(), true);
    }
    //04-01-20
    public static function QAICASHMakeDeposit($amount,$currency,$deposit_method,$depositor_UId,$depositor_email,$depositor_name,$redirectUrl){
        $http = new Client();
        $response = $http->post('https://epointexchange.com/api/qaicash/makedeposit',[
            'form_params' => [
                'amount'=>$amount,
                'currency' => $currency,
                'deposit_method'=>$deposit_method,
                'depositor_UId'=>$depositor_UId,
                'depositor_email'=> $depositor_email,
                'depositor_name'=> $depositor_name,
                'redirectUrl'=>$redirectUrl
            ],
            'headers' =>[
                'Authorization' => 'Bearer '.PaymentHelper::connectTo(),
                'Accept'     => 'application/json' 
            ]
        ]);
        return json_decode((string) $response->getBody(), true);
    }
    public static function QAICASHMakePayout($amount,$currency,$payout_method,$withdrawer_UId,$withdrawer_email,$withdrawer_name,$redirectUrl){
        $http = new Client();
        $response = $http->post('https://epointexchange.com/api/qaicash/makewithdraw',[
            'form_params' => [
                'amount'=>$amount,
                'currency' => $currency,
                'payout_method'=>$payout_method,
                'withdrawer_UId'=>$withdrawer_UId,
                'withdrawer_email'=> $withdrawer_email,
                'withdrawer_name'=> $withdrawer_name,
                'redirectUrl'=>$redirectUrl
            ],
            'headers' =>[
                'Authorization' => 'Bearer '.PaymentHelper::connectTo(),
                'Accept'     => 'application/json' 
            ]
        ]);
        return json_decode((string) $response->getBody(), true);
    }

    public static function launchCatPayPayment($order,$paytype,$orderId){
        $http = new Client();
        $response = $http->post(config('providerlinks.payment.catpay.url_order'),[
            'form_params' => [
                'order'=>$order,
                'timeStamp'=>(Carbon::now('Asia/Shanghai')->timestamp)*1000,
                'platformId'=>'WamRAOjZxH8vYG4rJU1',
                'platformToken'=>'azETahcH',
                'platformKey'=>'3a3343c316d947f68841fd7fd7c35636',
                'sign'=>md5('WamRAOjZxH8vYG4rJU1'.$orderId.'azETahcH3a3343c316d947f68841fd7fd7c35636786eea43c64af4e8dc26dc0c1cb896ea'),
                'payType'=>$paytype,
                'orderId'=>$orderId,
            ],
            // 'headers' =>[
            //     'Authorization' => 'Bearer '.PaymentHelper::connectTo(),
            //     'Accept'     => 'application/json' 
            // ]
        ]);
        return json_decode((string) $response->getBody(), true);
    }

    //end
    public static function payTransactions($token_id,$order_id,$purchase_id,$payment_id,$amount,$entry_id,$trans_type_id,$trans_update_url,$status_id){
        $pay_transaction = new PayTransaction();
        $pay_transaction->token_id = $token_id;
        $pay_transaction->orderId = $order_id;
        $pay_transaction->identification_id=$purchase_id;
        $pay_transaction->payment_id=$payment_id;
        $pay_transaction->amount=$amount;
        $pay_transaction->entry_id=$entry_id;
        $pay_transaction->trans_type_id=$trans_type_id;
        $pay_transaction->status_id=$status_id;
        $pay_transaction->trans_update_url=$trans_update_url;
        $pay_transaction->save();
        return $pay_transaction;
    }
    public static function updateTransaction($data){
        $pay_transaction = PayTransaction::where("token_id",$data["token_id"])->first();
        $pay_transaction->reference_number = $data["reference_number"];
        $pay_transaction->identification_id=$data["purchase_id"];
        $pay_transaction->status_id = $data["status_id"];
        $pay_transaction->from_currency = $data["from_currency"];
        $pay_transaction->input_amount = $data["input_amount"];
        $pay_transaction->exchange_rate = $data["exchange_rate"];
        $pay_transaction->amount=$data["amount"];
        $pay_transaction->save();
        return $pay_transaction;
    }
    public static function savePayTransactionLogs($transaction_id,$request,$response,$transaction_log_type){
        $pay_trasaction_log = new PayTransactionLogs();
        $pay_trasaction_log->transaction_id = $transaction_id;
        $pay_trasaction_log->request = $request;
        $pay_trasaction_log->response = $response;
        $pay_trasaction_log->transaction_log_type = $transaction_log_type;
        $pay_trasaction_log->save();
    }
    public static function getTransaction($type,$data){
        if($type == "token"){
            return PayTransaction::where("token_id",$data)->first();
        }
        elseif($type == "orderid"){
            return PayTransaction::where("orderId",$data)->first();
        }
    }
}
