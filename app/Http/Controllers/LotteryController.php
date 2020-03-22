<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as Psr7Request;
use App\Helpers\Helper as Helper;
use DB;


class LotteryController extends Controller
{
    //

	public function authPlayer(Request $request){

		// dd(1);
		 $client_check = DB::table('clients')
				->where('client_url', $request->site_url)
				->first();

		 if($client_check){
		 		// echo "meron";
	 		    $player_check = DB::table('players')
					->where('client_id', $client_check->client_id)
					->where('username', $request->merchant_user)
					->first();

				if($player_check){
					// echo "meron";

					 $http = new Client();
			         // $response = $http->post('http://localhost:8080/betrnkLotto-2-20-20/public_html/api/v1/index.php', [
			         $response = $http->post('http://betrnk-lotto.com/api/v1/index.php', [
			            'form_params' => [
			                'cmd' => 'auth',
			                'username' => $request->username,
			                'password' => $request->password,
			                'merchant_user'=> $request->merchant_user,
			                'merchant_user_balance'=> $request->merchant_user_balance,
			            ],
			         ]);


			       	 $game_url = json_decode((string) $response->getBody(), true)["response"]["game_url"];

			       	 DB::table('player_session_tokens')->insert(
					        array('player_id' => $player_check->player_id, 'player_token' =>  $request->player_token, 'status_id' => '1')
					 );

			       	 return $game_url.'&player_token='.$request->player_token;

				}else{
					// echo "wala";

					DB::table('players')->insert(
					        array('client_id' => $client_check->client_id, 'client_player_id' =>  $request->merchant_user_id, 'username' => $request->merchant_user, 'email' => $request->merchant_user_email,'display_name' => $request->merchant_user_display_name)
					);

					$last_player_id = DB::getPDO()->lastInsertId();

					DB::table('player_session_tokens')->insert(
					        array('player_id' => $last_player_id, 'player_token' =>  $request->player_token, 'status_id' => '1')
					);


					 $http = new Client();
			         $response = $http->post('http://betrnk-lotto.com/api/v1/index.php', [
			            'form_params' => [
			                'cmd' => 'auth',
			                'username' => $request->username,
			                'password' => $request->password,
			                'merchant_user'=> $request->merchant_user,
			                'merchant_user_balance'=> $request->merchant_user_balance,
			            ],
			         ]);

			       	 $game_url = json_decode((string) $response->getBody(), true)["response"]["game_url"];

			       	 return $game_url.'&player_token='.$request->player_token;
				}


		 }else{
		 	// echo "wala";
		 	return 'Your Not Subscribed!';
		 }	

	}





	public function getBalance(Request $request)
	{	

		// dd(1);

		// $player_token = $request->player_token;
		$merchant_user = $request->merchant_user;


		// dd($merchant_user);
		$client_details = DB::table("players AS p")
						 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.player_token' , 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
						 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
						 ->leftJoin("clients AS c", "c.client_id", "=", "p.client_id")
						 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
						 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id")
						 ->where("p.username", $merchant_user)
						 ->where("pst.status_id", 1)
						 ->first();				 

		// dd($client_details);				 

	 	// dd($client_details);
		// $hashkey = 6f1190df5414d22490583434fe3622fe;
						 
		if ($client_details) {
			      // dd(md5($client_details->client_api_key.$client_details->client_access_token));
			 	  $http = new Client();
		          $response = $http->post($client_details->player_details_url, [ // 127.0.0.1:8000/getuserbalance
		            'form_params' => [
		                'merchant_user'=> $request->merchant_user,
		                'hashkey' => md5($client_details->client_api_key.$client_details->client_access_token),
		            ],
		         ]);

				 $balance = json_decode((string) $response->getBody(), true);

				 return $balance['playerdetailsresponse']['balance'];
		}


	}

	public function debitProcess(Request $request)
	{
		  // dd(1);	

		  $player_check = DB::table('players')
				->where('username', $request->merchant_user)
				->first();

	      // $player_token = $request->player_token;
	      // $player_id = $request->player_id;

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

			// return ($client_details->player_token);			 

		 	// dd($client_details->player_token);

			if ($client_details) {

			
				// dd($client_details);

				$http = new Client();
                $response = $http->post($client_details->fund_transfer_url, [
                    'form_params' => [
                        'hashkey' => md5($client_details->client_api_key.$client_details->client_access_token),
                        'debit' => $request->debit,
                        'merchant_user'=> $request->merchant_user,
                        'http' => 200,
                        'transactiontype' => 'debit',
                        'player_token' => $client_details->player_token
                    ],
                ]);

                $res = json_decode((string) $response->getBody(), true);
                // return   $res['playerdetailsresponse']['gamedetails']['gameid'];
                // {"status":{"success":true,"message":"Request successful."},"gamedetails":{"gameid":1,"gamename":"lotto"},"type":"debit"} 

                // return $res['playerdetailsresponse']['gamedetails']['gameid'];

                $game_code = $res['playerdetailsresponse']['gamedetails']['gameid'];

                $db_game_id = DB::table('games')
                			 ->where('game_code', $game_code) 
                			 ->first();


                // return $db_game_id;			 

                $token_id = $client_details->token_id;
                $game_id = $db_game_id->game_id;
                $played_amount = $request->debit;
                $payout = $request->debit;	

                Helper::saveGame_transaction($token_id, $game_id, $played_amount,  $payout, 2);

              
		        // return  'Success!';

			}	

	}

}


