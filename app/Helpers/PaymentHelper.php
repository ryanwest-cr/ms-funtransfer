<?php
namespace App\Helpers;
use DB;
use GuzzleHttp\Client;
use App\PayTransaction;
use App\PayTransactionLogs;
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

        $response = $http->post('https://api.epointexchange.com/public/oauth/token', [
            'form_params' => [
                'grant_type' => 'password',
                'client_id' => '3',
                'client_secret' => 'uAthPzJR6lk9hrgPljMUjzGHjnPvtT2Ps6eLHRv7',
                'username' => 'api-test@betrnk.games',
                'password' => ']WKtkT``mJCe8N3J',
                'scope' => '*',
            ],
        ]);

        return json_decode((string) $response->getBody(), true)["access_token"];

    }
    public static function ebancoConnectTo(){
        $http = new Client();
 
        $response = $http->post('http://api.e-banco.net/oauth/token', [
            'form_params' => [
              'grant_type' => 'password',
               'client_id' => '5',
               'client_secret' => 'o6xxbH3bYbTcZOIcrLqRx0YVLDxhUHD28G03cfcr',
               'username' => 'api@betrnk.games',
               'password' => ']WKtkT``mJCe8N3J',
               'scope' => '*',
             ],
        ]);
        return json_decode((string) $response->getBody(), true)["access_token"];
 
     }
    public static function paymongo($cardnumber,$exp_year,$exp_month,$cvc,$amount,$currency,$returnUrl){

        $http = new Client();
        $response = $http->post('https://api.epointexchange.com/public/api/v1/paymentportal/paymongo', [
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
    public static function paymongoUpdate($orderId){

        $http = new Client();
        $response = $http->post('https://api.epointexchange.com/public/api/v1/paymentportal/paymongo_checkupdate', [
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
        $response = $http->post('https://api.epointexchange.com/public/api/v1/paymentportal/vprica', [
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
        $response = $http->post('https://api.epointexchange.com/public/api/v1/paymentportal/vprica_check_exist', [
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
        $response = $http->get('https://api.epointexchange.com/public/api/v1/paymentportal/coinpayment_rate', [
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
        $response = $http->post('https://api.epointexchange.com/public/api/v1/paymentportal/coinpayment_transaction', [
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
            $response = $http->post('https://api.e-banco.net/api/v1/makedeposit', [
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

    ///endcoinspayment


    //03-24-20
    public static function QAICASHDepositMethod($currency){
        $http = new Client();
        $response = $http->post('https://api.epointexchange.com/public/api/qaicash/depositmethods',[
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
        $response = $http->post('https://api.epointexchange.com/public/api/qaicash/payoutmethods',[
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
        $response = $http->post('https://api.epointexchange.com/public/api/qaicash/approvewithdraw',[
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
        $response = $http->post('https://api.epointexchange.com/public/api/qaicash/rejectwithdraw',[
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
        $response = $http->post('https://api.epointexchange.com/public/api/qaicash/makedeposit',[
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
        $response = $http->post('https://api.epointexchange.com/public/api/qaicash/makewithdraw',[
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
        $pay_transaction->identification_id=$data["purchase_id"];
        $pay_transaction->status_id = $data["status_id"];
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
}