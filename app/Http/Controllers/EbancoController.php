<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as Psr7Request;
use App\Helpers\Helper as Helper;
use Carbon\Carbon;
use App\Helpers\PaymentHelper;
use DB;


class EbancoController extends Controller
{


    public function connectTo(){
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




   	public function getBankList(){

    	$http = new Client();
        $response = $http->get('https://e-banco.net/api/v1/banklist', [
        // $response = $http->get('127.0.0.1:8880/api/v1/banklist', [
            'headers' =>[
                'Authorization' => 'Bearer '.$this->connectTo(),
                'Accept'     => 'application/json' 
            ]
        ]);

        return  $response->getBody();
         
    } 


    public function getCurrencyConvertion($input_currency){
        $currency = PaymentHelper::currency();
        foreach($currency["rates"] as $currency){
            if($currency["currency"] == $input_currency){
                return $currency["rate"];
            }
        }
    }


  public function updateDeposit(Request $request){

		 $identification_id = $request->deposit_id;

		DB::table('pay_transactions')
		    ->where('identification_id', $identification_id)
		    ->where('payment_id', 4)
		    ->update(
		 	array(
                'status_id'=> 5,
		));


		$transaction_track = DB::table("pay_transactions AS pt")
						 ->select('pt.token_id', 'pt.payment_id', 'pt.entry_id', 'pt.id as trans_id', 'pt.identification_id', 'pst.player_id', 'pst.token_id', 'p.player_id', 'p.client_id', 'p.client_player_id', 'pt.trans_update_url')
						 ->leftJoin("player_session_tokens AS pst", "pt.token_id", "=", "pst.token_id")
						 ->leftJoin("players AS p", "p.player_id", "=", "pst.player_id")
						 ->where("pt.payment_id", 4)
						 ->where('pt.identification_id', $identification_id)
						 ->first();		
		
		 $http = new Client();
         // $response = $http->post('127.0.0.1:8000/depositupdate', [
         // $response = $http->post('http://demo.freebetrnk.com/depositupdate', [
         $response = $http->post($transaction_track->trans_update_url, [
            'form_params' => [
		           'transaction_id' => $transaction_track->trans_id,
		           'client_player_id' => $transaction_track->client_player_id
		        ],
        ]);     


        return $response->getBody()->getContents();

  }


  public function makeDeposit(Request $request){

    	   $client_check = DB::table('clients')
				->where('client_url', $request->site_url)
				->first();

			$currencyType = $request->input("currency_type");	
			$currency = (float)$this->getCurrencyConvertion($currencyType);
			$finalcurrency =((float)$request->input("amount")*$currency);	


			if($client_check){
				$player_check = DB::table('players')
					->where('client_id', $client_check->client_id)
					->where('username', $request->merchant_user)
					->first();

					if($player_check){
						$client_details = DB::table("players AS p")
							 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.player_token' , 'pst.status_id','pst.token_id' , 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
							 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
							 ->leftJoin("clients AS c", "c.client_id", "=", "p.client_id")
							 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
							 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id")
							 ->where("p.player_id", $player_check->player_id)
							 ->where("pst.status_id", 1)
							 ->latest('token_id')
							 ->first();	


						DB::table('player_session_tokens')->insert(
						        array('player_id' => $player_check->player_id, 'player_token' =>  $request->player_token, 'status_id' => '1')
						);

						$token_id = $client_details->token_id;


					     /* REQUEST TO EBANCO */
				         $http = new Client();
				         // $response = $http->post('127.0.0.1:8880/api/v1/makedeposit', [
				         $response = $http->post('https://e-banco.net/api/v1/makedeposit', [
				            'headers' =>[
				                'Authorization' => 'Bearer '.$this->connectTo(),
				                'Accept'     => 'application/json' 
				            ],
				            'form_params' => [
							           'amount' => $finalcurrency,
							           'bankname' => $request->bankname
								    ],
				         ]);

				        $res = json_decode($response->getBody(), true);


						DB::table('pay_transactions')->insert(
						        array('token_id' => $token_id, 'payment_id' =>  4, 'identification_id' =>  $res['deposit_id'], 'status_id' => 6, 'amount' => $res["deposit_amount"], 'entry_id' => 2, 'trans_type_id' => 1, 'trans_update_url' => $request->trans_update_url, 'created_at' => Carbon::now())
				    	);

						$transaction_id = DB::getPDO()->lastInsertId();


				    	$trans_msg = array("pay_transaction_id"=>$transaction_id,
	                                       "deposit_id"=>$res["deposit_id"],
	                                       "bank_name"=> $res["bank_name"],
	                                       "bank_account_no"=>$res["bank_account_no"],
	                                       "bank_account_name"=>$res["bank_account_name"],
	                                       "bank_branch_name"=>$res["bank_branch_name"],
	                                       "deposit_amount"=>$res["deposit_amount"],
	                                       "status"=>$res["status"],
	                                   );

				        // dd($trans_msg);

	                    return $trans_msg;

					}else{
						// dd(2);
						DB::table('players')->insert(
						        array('client_id' => $client_check->client_id, 'client_player_id' =>  $request->merchant_user_id, 'username' => $request->merchant_user, 'email' => $request->merchant_user_email,'display_name' => $request->merchant_user_display_name)
						);

						$last_player_id = DB::getPDO()->lastInsertId();

						DB::table('player_session_tokens')->insert(
						        array('player_id' => $last_player_id, 'player_token' =>  $request->player_token, 'status_id' => '1')
						);

						$token_id = $client_details->token_id;

						/* REQUEST TO EBANCO */
			            $http = new Client();
				         $response = $http->post('https://e-banco.net/api/v1/makedeposit', [
				        // $response = $http->post('127.0.0.1:8880/api/v1/makedeposit', [
				            'headers' =>[
				                'Authorization' => 'Bearer '.$this->connectTo(),
				                'Accept'     => 'application/json' 
				            ],
				            'form_params' => [
										           'amount' => $finalcurrency,
										           'bankname' => $request->bankname
										       ],
				         ]);

				        $res = json_decode($response->getBody(), true);

						DB::table('pay_transactions')->insert(
						        array('token_id' => $token_id, 'payment_id' =>  4, 'identification_id' =>  $res['deposit_id'], 'status_id' => 6, 'amount' => $res["deposit_amount"], 'entry_id' => 2, 'trans_type_id' => 1, 'trans_update_url' => $request->trans_update_url, 'created_at' => Carbon::now())
				    	);

						$transaction_id = DB::getPDO()->lastInsertId();

				    	$trans_msg = array("pay_transaction_id"=>$transaction_id,
	                                       "deposit_id"=>$res["deposit_id"],
	                                       "bank_name"=> $res["bank_name"],
	                                       "bank_account_no"=>$res["bank_account_no"],
	                                       "bank_account_name"=>$res["bank_account_name"],
	                                       "bank_branch_name"=>$res["bank_branch_name"],
	                                       "deposit_amount"=>$res["deposit_amount"],
	                                       "status"=>$res["status"],
	                                   );
	                    return $trans_msg;

					}

			}else{
			 	return 'Your Not Subscribed!';
			}	
    	  
         
    }

    public function sendReceipt(Request $request){

     	 $http = new Client();
	     // $response = $http->post('127.0.0.1:8880/api/v1/senddepositreceipt', [
	     $response = $http->post('https://e-banco.net/api/v1/senddepositreceipt', [
	        'headers' =>[
	            'Authorization' => 'Bearer '.$this->connectTo(),
	            'Accept'     => 'application/json',
	            'content-type' => 'application/x-www-form-urlencoded',
	        ],
	        'form_params' => [
		        'receipt' => $request->receipt,
                'transaction_id' => $request->transaction_id
	        ],
	     ]);

	     return  $response->getBody();	

     }


    public function depositInfo(Request $request){

     	 $http = new Client();
	     // $response = $http->post('127.0.0.1:8880/api/v1/deposittransaction', [
	     $response = $http->post('https://e-banco.net/api/v1/deposittransaction', [
	        'headers' =>[
	            'Authorization' => 'Bearer '.$this->connectTo(),
	            'Accept'     => 'application/json', 
	        ],
	        'form_params' => [
	            'deposit_id' => $request->transaction_id
	        ],
	     ]);

	     return  $response->getBody();

     }
     

      public function depositHistory(Request $request){
     		
     	 return 'Not Available!';	

     	 $http = new Client();
	     // $response = $http->post('127.0.0.1:8880/api/v1/deposittransactions', [
	     $response = $http->post('https://e-banco.net/api/v1/deposittransactions', [
	        'headers' =>[
	            'Authorization' => 'Bearer '.$this->connectTo(),
	            'Accept'     => 'application/json', 
	        ],
	        'form_params' => [
                'username' => $username // USER ID OF THE CLIENT TEMPORARY
            ],
	     ]);

	     return  $response->getBody();

     }




     // public function test(){
     // 	     // return array("deposit_amount" =>  123);

	   	// 	$deposit_info = array(
	    //                "deposit_amount" =>  123,
	    //                "bank_name" =>  1233
     //              ); 

	   	// 	return $deposit_info;

     // }


	/****************************************************************/


	public function testrequest(){
		return array("message"=>"success request");
	}
}

