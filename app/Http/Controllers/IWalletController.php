<?php

namespace App\Http\Controllers;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Client;
use App\Helpers\Helper;
use App\Helpers\PaymentHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DB;



class IWalletController extends Controller
{

	/*	
	 * ## NOTE ##
	 * SETTLEMENT URL IS HARDCODED IN THE PAY LOBBY
	 */
	// public $p_num = '10086';
	// public $user_name = 'wellTreasureTech';
	// public $password = '0e2jmTFEgqv';
	// public $account_number = '53789422';

	// TEST ACC
	public $p_num = '10107'; // P Number
	public $user_name = 'wellTreasureTech'; // Username
	public $password = '06uf5z7HtQX'; // Iwallet Password
	public $account_number = '49425435'; // Account Number Sa 
	public $to_account = '65933077'; // Player Account No.


	/*	
	 * # PAYMENT/DEPOSIT KEY/SIGNATURE GENERATOR
	 */
	public function createSignature(){
		$chashen = $this->user_name.$this->password.$this->p_num;
		$cha1 = hash('sha256', $chashen);
		return $cha1;
	}




	/*	
	 * FOR PAYMENT/DEPOSIT SETTLEMENT
	 * ## NOTE ## 
	 * from_account is request coming from iwallet, base on the paylobby form signature and p_num
	 * to_account (dynamic) is the one who actually login for the deposit transaction
	 * inputs, player_token, player_username, orderId, site_url, exitUrl, callBackUrl
	 */
	public function makeSettlement(Request $request){
		$str = array(
			"amount" =>$request->amount,
			"currency" => $request->currency,
			"p_num" => $request->p_num,
			"transaction_number" => $request->transaction_number,
			"result"=> $request->result,
			"to_fee" => $request->to_fee,
			"_token" => $request->_token,
			"payment_method"=> $request->payment_method,
			"token" => $request->token,
			"exitUrl" => $request->exitUrl,
			"from_account"=>$request->from_account,
			"to_account"=> $request->to_account,
		);
		//return $str;
		$request_data = json_encode($str);

		// Log The Request COming From The Iwallet Platform
		Helper::saveLog('settlementPaymentCall', 2, json_encode($request->token), 'receivedStrReplace');


				// Make The Settements
				// SUCCESS 
		        if($request->result == 0){

		            Helper::saveLog('IWalletCallBack01', 2, 22, 'iwalletSuccess1');

		            $player_details = $this->_getClientDetails("token",$request->token);

		            $update_deposit = DB::table('pay_transactions')
					    ->where('token_id', $player_details->token_id)
					    ->where('payment_id', 10) 
					    ->update(
					 	array(
			                'status_id'=> 5,
					));

		            $client_player_id = DB::table('player_session_tokens as pst')
                                    ->select("p.client_player_id","p.client_id")
                                    ->leftJoin("players as p","pst.player_id","=","p.player_id")
                                    ->where("pst.token_id",$player_details->token_id)
                                    ->first();

                    $transaction =   DB::table('pay_transactions as pt')
                                    ->where("pt.token_id",$player_details->token_id)
                                    ->first();              

                    $request_status = 'SUCCESS';                

    			    $key = $transaction->id.'|'.$client_player_id->client_player_id.'|'.$request_status;
    			    $authenticationCode = hash_hmac("sha256",$client_player_id->client_id,$key);
		            $http = new Client();
		            $response = $http->post($transaction->trans_update_url,[
		                'form_params' => [
                            'transaction_id' => $transaction->id,
                            'order_id' => $transaction->orderId,
                            'client_player_id' => $client_player_id->client_player_id,
                            'status' => "SUCCESS",
                            'message' => 'Your Transaction Order '.$transaction->id.'has been updated to SUCCESS',
                            'AuthenticationCode' => $authenticationCode
                        ],
		            ]); 
		            $response = json_decode((string) $response->getBody(), true);
		            Helper::saveLog('IWalletCallBack02', 2, $response, 'demoRes');
		            return 0; // SUCCESS

		        }elseif($request->result == 1){
					Helper::saveLog('IWalletCallBack1', 2, 'Failed', 'iwalletFailed');
					return 1; // FAILED
		        }else{
		        	return 1; // FAILED
		        }

     
	}	





	
	/*	
	 * # DEPRECATED / USED PAYMENTGATEWAYCONTROLLER
	 * # FOR WITHDRAWAL/REMITTANCE
	 * # Waiting For Approval From The Back Office
	 * @params requests order_id,payment_id,status_id
	 * # status_id, 
	 * 5 = Approved 
	 * 6 = Pending
	 * 9 = Rejected
     * 7 = Held
     * 
	 */
	public function makeRemittance(Request $request){
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

				// UPDATE THE CLIENT WITHRAWAL REQUEST
				$client_player_id = DB::table('player_session_tokens as pst')
                            ->select("p.client_player_id","p.client_id")
                            ->leftJoin("players as p","pst.player_id","=","p.player_id")
                            ->where("p.player_id",$request->user_id)
                            ->first();

                $key = $order_details->id.'|'.$client_player_id->client_player_id.'|SUCCESS';
                $authenticationCode = hash_hmac("sha256",$client_player_id->client_id,$key);

					if($request->status_id == 5){

						$http = new Client();
						$response = $http->post($order_details->trans_update_url, [
					        'form_params' => [
					            'transaction_id' => $order_details->id,
                                'order_id' => $order_details->orderId,
	                            'client_player_id' => $client_player_id->client_player_id,
	                            'status' => "SUCCESS",
	                            'message' => 'Your Transaction Order '.$order_details->id.'has been updated to SUCCESS',
	                            'AuthenticationCode' => $authenticationCode
					        ]
					    ]);

					    $client_response = json_decode($response->getBody()->getContents(), true);
					    if($client_response['STATUS']== 'SUCCESS'){

					    	// MAKE DEPOSIT TO THE ACCOUNT OWNER IWALLET
						    $make_remittance = $this->account_number.$this->password.$this->p_num.$pay_body->amount;
							$remi_cha1 = hash('sha256', $make_remittance);
							$http = new Client();
							$response = $http->post('https://test.iwl.world/api/MoneyRequest', [
						        'form_params' => [
						            'p_num' => $this->p_num,
						            'signature' => $remi_cha1, 
						            'from_account' => $this->account_number, 
						            'to_account' => $pay_body->to_account, 
						            'currency' => $pay_body->currency,
						            'amount' =>  $pay_body->amount,
						            'debit_currency' => $pay_body->currency,
						        ]
						    ]);

							$this->_updateTransaction($order_details->orderId, $order_details->id, 5);
	                        $this->_updateWithdraw($order_details->orderId, $request->user_id, 5);

	                        $remittance_request = [
	                        	'p_num' => $this->p_num,
						        'signature' => $remi_cha1, 
						        'from_account' => $this->account_number, 
						        'to_account' => $pay_body->to_account, 
						        'currency' => $pay_body->currency,
						        'amount' =>  $pay_body->amount,
						        'debit_currency' => $pay_body->currency
	                        ];

						    $client_response = json_decode($response->getBody()->getContents(), true);
						    Helper::saveLog('iwalletRemitance', 2, json_encode($response->getBody()->getContents()), 'Settled');
						    PaymentHelper::savePayTransactionLogs($order_details->id,json_encode($remittance_request, true), $response->getBody(),"IWALLET REQUEST REMITANCE");

						     $message = [
						            'STATUS' => 'SUCCESS'
						     ];     
						     return $message;

					    }else{

					    	 $message = [
						            'STATUS' => 'FAILED'
						     ];     
						     return $message;
					    }
					    

					}
					elseif($request->status_id == 9){

						$http = new Client();
						$response = $http->post($order_details->trans_update_url, [
					        'form_params' => [
					            'transaction_id' => $order_details->id,
                                'order_id' => $order_details->orderId,
	                            'client_player_id' => $client_player_id->client_player_id,
	                            'status' => "FAILED",
	                            'message' => 'Your Transaction Order '.$order_details->id.'has been updated to FAILED',
	                            'AuthenticationCode' => $authenticationCode
					        ]
					    ]);

				        $client_response = json_decode($response->getBody()->getContents(), true);
					    if($client_response['STATUS']== 'SUCCESS'){
					    	 $this->_updateTransaction($order_details->orderId, $order_details->id, 9);
                     	     $this->_updateWithdraw($order_details->orderId, $request->user_id, 9);

						     $message = [
						            'STATUS' => 'SUCCESS'
						     ];     
						     return $message;
					    }else{

					    	 $message = [
						            'STATUS' => 'FAILED'
						     ];     
						     return $message;
					    }


					}
					elseif($request->status_id == 6){

						$http = new Client();
						$response = $http->post($order_details->trans_update_url, [
					        'form_params' => [
					            'transaction_id' => $order_details->id,
                                'order_id' => $order_details->orderId,
	                            'client_player_id' => $client_player_id->client_player_id,
	                            'status' => "HELD",
	                            'message' => 'Your Transaction Order '.$order_details->id.'has been updated to HELD',
	                            'AuthenticationCode' => $authenticationCode
					        ]
					    ]);

				        $client_response = json_decode($response->getBody()->getContents(), true);
					    if($client_response['STATUS']== 'SUCCESS'){
					    	 $this->_updateTransaction($order_details->orderId, $order_details->id, 6);
                     	     $this->_updateWithdraw($order_details->orderId, $request->user_id, 6);
                     	     
						     $message = [
						            'STATUS' => 'SUCCESS'
						     ];     
						     return $message;
					    }else{

					    	 $message = [
						            'STATUS' => 'FAILED'
						     ];     
						     return $message;
					    }


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
	 * Check Player Using Token if its already register in the MW database if not register it!
	 */
	public function checkClientPlayer($site_url, $merchant_user ,$token = false)
	{

				// Check Client Server Name
				$client_check = DB::table('clients')
	          	 	 ->where('client_url', $site_url)
	           		 ->first();

	           	$data = [
		        	"msg" => "Client Not Found",
		        	"httpstatus" => "404"
		        ];  	 

	            if($client_check){  

		                $player_check = DB::table('players')
		                    ->where('client_id', $client_check->client_id)
		                    ->where('username', $merchant_user)
		                    ->first();

		                if($player_check){

		                    DB::table('player_session_tokens')->insert(
		                            array('player_id' => $player_check->player_id, 
	                            		  'player_token' =>  $token, 
		                            	  'status_id' => '1')
		                    );    

		                    $token_player_id = $this->_getPlayerTokenId($player_check->player_id);

		                    $data = [
						        	"token" => $token,
						        	"httpstatus" => "200",
						        	"new" => false
					        ];   

		                }else{

	                	try
	                	{
						        $client_details = $this->_getClientDetails('site_url', $site_url);

								$client = new Client([
								    'headers' => [ 
								    	'Content-Type' => 'application/json',
								    	'Authorization' => 'Bearer '.$client_details->client_access_token
								    ]
								]);

								$guzzle_response = $client->post($client_details->player_details_url,
								    ['body' => json_encode(
								        	["access_token" => $client_details->client_access_token,
												"hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
												"type" => "playerdetailsrequest",
												"datesent" => "",
												"gameid" => "",
												"clientid" => $client_details->client_id,
												"playerdetailsrequest" => [
													"token" => $token,
													"gamelaunch" => true
												]]
								    )]
								);

								$client_response = json_decode($guzzle_response->getBody()->getContents());

								DB::table('players')->insert(
		                            array('client_id' => $client_check->client_id, 
		                            	  'client_player_id' =>  $client_response->playerdetailsresponse->accountid, 
		                            	  'username' => $client_response->playerdetailsresponse->username, 
		                            	  'email' => $client_response->playerdetailsresponse->email,
		                            	  'display_name' => $client_response->playerdetailsresponse->accountname)
			                    );

			                	$last_player_id = DB::getPDO()->lastInsertId();

			                	DB::table('player_session_tokens')->insert(
				                            array('player_id' => $last_player_id, 
				                            	  'player_token' =>  $token, 
				                            	  'status_id' => '1')
			                    );

			                	$token_player_id = $this->_getPlayerTokenId($last_player_id);
						}
						catch(ClientException $e)
						{
						  $client_response = $e->getResponse();
						  $response = json_decode($client_response->getBody()->getContents(),True);
						  return response($response,$client_response->getStatusCode())
						   ->header('Content-Type', 'application/json');
						}
				                $data = [
						        	"token" => $token,
						        	"httpstatus" => "200",
						        	"new" => true
						        ];   
		      			}     
				}
		        return $data;
		}
		public function _getClientDetails($type = "", $value = "") {
		$query = DB::table("clients AS c")
					 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.token_id', 'pst.player_token' , 'c.client_url', 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
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

					if ($type == 'site_url') {
						$query->where([
					 		["c.client_url", "=", $value],
					 	]);
					}
					if ($type == 'username') {
						$query->where([
					 		["p.username", $value],
					 	]);
					}

					 $result= $query
					 			->latest('token_id')
					 			->first();

			return $result;

		}
		public function _getPlayerTokenId($player_id){

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



} /* END */
