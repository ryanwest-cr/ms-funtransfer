<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Helpers\GameRound;
use App\Helpers\GameTransaction;
use App\Helpers\Helper;
use App\Helpers\CallParameters;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;

use DB;


/**
 * UNDER CONSTRUCTION
 * 
 */
class LotteryController extends Controller
{


	// public $access_token = '321dsfjo34j5olkdsf';
	// public $api_key = '123iuysdhfb09875v9hb9pwe8f7yu439jvoiefjs';
	public $secret_key = 'freebetrnksecret';
	public $username = 'freebetrnk';

	// hash_hmac("sha256",'freebetrnk','freebetrnksecret'); // CORRCET HASH LOTTO TO SEND


	public function auth_key($hashkey) {
        $result = false;
            // API KEY STORED IN MW CLIENT API KEY
            if($hashkey == md5($this->api_key.$this->access_token)) {
                $result = true;
            }
        return $result;
    }


	public function getBalance(Request $request)
	{	
		Helper::saveLog('Lottery Balance', 10, json_encode($request->all()), 'ENDPOINT HIT');
		$merchant_user = $request->merchant_user;
		$client_details = DB::table("players AS p")
						 ->select('p.client_id', 'p.player_id', 'p.username', 'p.email', 'p.language', 'p.currency', 'pst.player_token' , 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
						 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
						 ->leftJoin("clients AS c", "c.client_id", "=", "p.client_id")
						 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
						 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id")
						 ->where("p.username", $merchant_user)
						 ->where("pst.status_id", 1)
						 ->first();				 

		if ($client_details) {
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
								"clientid" => $client_details->client_id,
								"playerdetailsrequest" => [
									"gamelaunch" => true,
									"token" => $client_details->player_token,
								]
							]
				    )]
				);
				$client_response = json_decode($guzzle_response->getBody()->getContents());
				Helper::saveLog('Lottery Balance Reply', 10, json_encode($client_response), 'ENDPOINT HIT');
				echo json_encode($client_response->playerdetailsresponse->balance);
		}
	}

	public function debitProcess(Request $request)
    {
		  Helper::saveLog('Lottery Debit', 10, json_encode($request->all()), 'ENDPOINT HIT');

		   if ($this->auth_key($request->hashkey)) {
		     	return 'true';
		   }else{
                return 'mali!';
	       }

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

			if ($client_details) {

	// 			   $http = new Client();
    //             $response = $http->post($client_details->fund_transfer_url, [
    //                 'form_params' => [
    //                     'hashkey' => md5($client_details->client_api_key.$client_details->client_access_token),
    //                     'debit' => $request->debit,
    //                     'merchant_user'=> $request->merchant_user,
    //                     'http' => 200,
    //                     'transactiontype' => 'debit',
    //                     'player_token' => $client_details->player_token
    //                 ],
    //             ]);
    //             $res = json_decode((string) $response->getBody(), true);
    //             $game_code = $res['playerdetailsresponse']['gamedetails']['gameid'];
    //             $db_game_id = DB::table('games')
    //             			 ->where('game_code', $game_code) 
    //             			 ->first();
    //             $token_id = $client_details->token_id;
    //             $game_id = $db_game_id->game_id;
    //             $played_amount = $request->debit;
    //             $payout = $request->debit;	

                // Helper::saveGame_transaction($token_id, $game_id, $played_amount,  $payout, 2);

                    $client = new Client([
	                    'headers' => [ 
	                        'Content-Type' => 'application/json',
	                        'Authorization' => 'Bearer '.$client_details->client_access_token
	                    ]
	                ]);

	                $requesttosend = [
					  "access_token" => $client_details->client_access_token,
					  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
					  "type" => "fundtransferrequest",
					  "datesent" => Helper::datesent(),
					  "gamedetails" => [
					    "gameid" =>  "",
					    "gamename" => ""
					  ],
					  "fundtransferrequest" => [
							"playerinfo" => [
							"token" => $client_details->player_token,
						],
						"fundinfo" => [
						      "gamesessionid" => "",
						      "transactiontype" => 'debit',
						      "rollback" => "false",
						      "currencycode" => $client_details->currency,
						      "amount" => $request->debit
						]
					  ]
					];

					try {

						$guzzle_response = $client->post($client_details->fund_transfer_url,
							['body' => json_encode($requesttosend)]
						);

						$client_response = json_decode($guzzle_response->getBody()->getContents());

			 		    $token_id = $client_details->token_id;
		                $game_id = $db_game_id->game_id;
		                $played_amount = $request->debit;
		                $payout = $request->debit;	
                        Helper::saveGame_transaction($token_id, $game_id, $played_amount,  $payout, 2);

					} catch (\Exception $e) {
						// IF ALL OR NONE IS TRUE IF ONE ITEM FAILED BREAK THE FLOW!!
					}

			}	

	}



	    //UPDATE TRACK

	/**
	 *  DEPRECATED CENTRALIZED
	 */
	// public function authPlayer(Request $request){
	// 	// dd(1);
	// 	 $client_check = DB::table('clients')
	// 			->where('client_url', $request->site_url)
	// 			->first();
	// 	 if($client_check){
	// 	 		// echo "meron";
	//  		    $player_check = DB::table('players')
	// 				->where('client_id', $client_check->client_id)
	// 				->where('username', $request->merchant_user)
	// 				->first();
	// 			if($player_check){
	// 				// echo "meron";
	// 				 $http = new Client();
	// 		         // $response = $http->post('http://localhost:8080/betrnkLotto-2-20-20/public_html/api/v1/index.php', [
	// 		         $response = $http->post('http://betrnk-lotto.com/api/v1/index.php', [
	// 		            'form_params' => [
	// 		                'cmd' => 'auth',
	// 		                'username' => $request->username,
	// 		                'password' => $request->password,
	// 		                'merchant_user'=> $request->merchant_user,
	// 		                'merchant_user_balance'=> $request->merchant_user_balance,
	// 		            ],
	// 		         ]);
	// 		       	 $game_url = json_decode((string) $response->getBody(), true)["response"]["game_url"];
	// 		       	 DB::table('player_session_tokens')->insert(
	// 				        array('player_id' => $player_check->player_id, 'player_token' =>  $request->player_token, 'status_id' => '1')
	// 				 );
	// 		       	 return $game_url.'&player_token='.$request->player_token;
	// 			}else{
	// 				// echo "wala";
	// 				DB::table('players')->insert(
	// 				        array('client_id' => $client_check->client_id, 'client_player_id' =>  $request->merchant_user_id, 'username' => $request->merchant_user, 'email' => $request->merchant_user_email,'display_name' => $request->merchant_user_display_name)
	// 				);
	// 				$last_player_id = DB::getPDO()->lastInsertId();
	// 				DB::table('player_session_tokens')->insert(
	// 				        array('player_id' => $last_player_id, 'player_token' =>  $request->player_token, 'status_id' => '1')
	// 				);

	// 				 $http = new Client();
	// 		         $response = $http->post('http://betrnk-lotto.com/api/v1/index.php', [
	// 		            'form_params' => [
	// 		                'cmd' => 'auth',
	// 		                'username' => $request->username,
	// 		                'password' => $request->password,
	// 		                'merchant_user'=> $request->merchant_user,
	// 		                'merchant_user_balance'=> $request->merchant_user_balance,
	// 		            ],
	// 		         ]);
	// 		       	 $game_url = json_decode((string) $response->getBody(), true)["response"]["game_url"];
	// 		       	 return $game_url.'&player_token='.$request->player_token;
	// 			}
	// 	 }else{
	// 	 	// echo "wala";
	// 	 	return 'Your Not Subscribed!';
	// 	 }	
	// }







		// $client = new Client([
  //           'headers' => [ 
  //               'Content-Type' => 'application/x-www-form-urlencoded',
  //           ]
  //       ]);
		// $response = $client->post('http://api.8provider.com/game/geturl',[
		// 	'form_params' => [
		// 		  "project" => 1042,
	 //              "version" => 1,
		// 		  "token" => 'sampletoken',
		// 		  "game" => '98',
		// 		  "currency" => "USD",
		// 		  "denomination" => '0.1',
		// 		  "return_url_info" => true,  // true converted to 1
	 //              "callback_version" => 1,
		// 		  "settings" =>  [
		// 		  	'user_id'=>'61',
		// 		  	'language'=>'en'
		// 		  ],
		// 		  "signature" => md5('1042*1*sampletoken*98*USD*0.1*1*1*user_id:61,language:en*c270d53d4d83d69358056dbca870c0ce'),
		// 	],
		// ]);





		// "signature" => md5('1042*1*sampletoken*98*USD*0.1*1*1*61,en*c270d53d4d83d69358056dbca870c0ce'),
		// "signature" => md5('1042*1*sampletoken*98*USD*0.1*1*1*61*en*c270d53d4d83d69358056dbca870c0ce'),
		// "signature" => md5('1042,1,sampletoken,98,USD,0.1,1,1,61,en,c270d53d4d83d69358056dbca870c0ce'),
		// "signature" => md5('1042*1*sampletoken*98*USD*0.1*1*1*user_id,61,language,en*c270d53d4d83d69358056dbca870c0ce'),


}


