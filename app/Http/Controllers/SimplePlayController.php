<?php

namespace App\Http\Controllers;

use App\Models\PlayerDetail;
use App\Models\PlayerSessionToken;
use App\Helpers\Helper;
use App\Helpers\GameTransaction;
use App\Helpers\GameSubscription;
use App\Helpers\GameRound;
use App\Helpers\Game;
use App\Helpers\CallParameters;
use App\Helpers\PlayerHelper;
use App\Helpers\TokenHelper;

use App\Support\RouteParam;

use Illuminate\Http\Request;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

use DB;

class SimplePlayController extends Controller
{
    public function __construct(){
		/*$this->middleware('oauth', ['except' => ['index']]);*/
		/*$this->middleware('authorize:' . __CLASS__, ['except' => ['index', 'store']]);*/
		$key = "g9G16nTs";
		$iv = 0;

		$this->key = $key;
        if( $iv == 0 ) {
            $this->iv = $key;
        } else {
            $this->iv = $iv;
        }
	}

	public function show(Request $request) { }

	public function authPlayer(Request $request)
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		$client_code = RouteParam::get($request, 'brand_code');
		$token = RouteParam::get($request, 'token');

		if(!CallParameters::check_keys($json_data, 'gameCode')) {
				$response = [
						"errorcode" =>  "BAD_REQUEST",
						"errormessage" => "The request was invalid.",
						"httpstatus" => "400"
					];
		}
		else
		{
			$response = [
							"responseCode" =>  "TOKEN_NOT_FOUND",
							"errorDescription" => "Token provided in request not found in Wallet."
						];
			
			$client_details = $this->_getClientDetails($client_code);

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

			
				if(isset($client_response->playerdetailsresponse->status->code) 
					&& $client_response->playerdetailsresponse->status->code == "200") {

					// save player details if not exist
					$player_id = PlayerHelper::saveIfNotExist($client_details, $client_response);

					// save token to system if not exist
					TokenHelper::saveIfNotExist($player_id, $token);

					$response = [
						"playerid" => "$player_id",
						"currencyCode" => "USD",
						"languageCode" => "ENG",
						"balance" => $client_response->playerdetailsresponse->balance,
						"sessionToken" => $token
					];
				}
				else
				{
					// change token status to expired
					// TokenHelper::changeStatus($player_id, 'expired');
				}
			}
		}

		Helper::saveLog('authentication', 2, file_get_contents("php://input"), $response);
		echo json_encode($response);

	}

	
	public function getBalance(Request $request) 
	{
		$string = file_get_contents("php://input");
		$decrypted_string = $this->decrypt(urldecode($string));
		$query = parse_url('http://test.url?'.$decrypted_string, PHP_URL_QUERY);
		parse_str($query, $request_params);

		$client_code = RouteParam::get($request, 'brand_code');

		$response = [
						"errorcode" =>  "PLAYER_NOT_FOUND",
						"errormessage" => "Player not found",
						"httpstatus" => "404"
					];

		// Find the player and client details
		$client_details = $this->_getClientDetails($client_code);
		$player_details = PlayerHelper::getPlayerDetails('username', $request_params['username']);

		if ($client_details) {

			$client = new Client([
			    'headers' => [ 
			    	'Content-Type' => 'application/json',
			    	'Authorization' => 'Bearer '.$client_details->client_access_token
			    ]
			]);
			
			$guzzle_response = $client->post($client_details->player_details_url,
			    ['body' => json_encode(
			        	[
			        		"access_token" => $client_details->client_access_token,
							"hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
							"type" => "playerdetailsrequest",
							"datesent" => "",
							"gameid" => "",
							"clientid" => $client_details->client_id,
							"playerdetailsrequest" => [
								"token" => $player_details->player_token,
								"gamelaunch" => "false"
							]]
			    )]
			);

			$client_response = json_decode($guzzle_response->getBody()->getContents());
			
			if(isset($client_response->playerdetailsresponse->status->code) 
			&& $client_response->playerdetailsresponse->status->code == "200") {
				header("Content-type: text/xml; charset=utf-8");
		 		$response = '<?xml version="1.0" encoding="utf-8"?>';
		 		$response .= '<RequestResponse><username>'.$player_details->username.'</username><currency>USD</currency><amount>'.$client_response->playerdetailsresponse->balance.'</amount><error>0</error></RequestResponse>';

			}
			
		}
		

		Helper::saveLog('balance', 2, file_get_contents("php://input"), $response);
 		echo $response;

	}

	public function debitProcess(Request $request) 
	{
		$string = file_get_contents("php://input");
		$decrypted_string = $this->decrypt(urldecode($string));
		$query = parse_url('http://test.url?'.$decrypted_string, PHP_URL_QUERY);
		parse_str($query, $request_params);

		$client_code = RouteParam::get($request, 'brand_code');

		$response = [
						"errorcode" =>  "PLAYER_NOT_FOUND",
						"errormessage" => "Player not found",
						"httpstatus" => "404"
					];

		$client_details = $this->_getClientDetails($client_code);
		$player_details = PlayerHelper::getPlayerDetails($request_params['username'], 'username');
		
		if ($client_details && $player_details != NULL) {
			//GameRound::create($json_data['roundid'], $player_details->token_id);

			// Check if the game is available for the client
			$subscription = new GameSubscription();
			$client_game_subscription = $subscription->check($client_details->client_id, 4, $request_params['gamecode']);

			if(!$client_game_subscription) {
				$response = [
						"errorcode" =>  "GAME_NOT_FOUND",
						"errormessage" => "Game not found",
						"httpstatus" => "404"
					];
			}
			else
			{
				$client = new Client([
				    'headers' => [ 
				    	'Content-Type' => 'application/json',
				    	'Authorization' => 'Bearer '.$client_details->client_access_token
				    ]
				]);
				
				$guzzle_response = $client->post($client_details->fund_transfer_url,
				    ['body' => json_encode(
				        	[
							  "access_token" => $client_details->client_access_token,
							  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
							  "type" => "fundtransferrequest",
							  "datetsent" => "",
							  "gamedetails" => [
							    "gameid" => "",
							    "gamename" => ""
							  ],
							  "fundtransferrequest" => [
									"playerinfo" => [
									"token" => $player_details->player_token
								],
								"fundinfo" => [
								      "gamesessionid" => "",
								      "transactiontype" => "debit",
								      "transferid" => "",
								      "rollback" => "false",
								      "currencycode" => $player_details->currency,
								      "amount" => "-".$request_params["amount"]
								]
							  ]
							]
				    )]
				);

				$client_response = json_decode($guzzle_response->getBody()->getContents());

				/*var_dump($client_response); die();*/

				if(isset($client_response->fundtransferresponse->status->code) 
			&& $client_response->fundtransferresponse->status->code == "402") {
					$response = [
						"errorcode" =>  "NOT_SUFFICIENT_FUNDS",
						"errormessage" => "Not sufficient funds",
						"httpstatus" => "402"
					];
				}
				else
				{
					if(isset($client_response->fundtransferresponse->status->code) 
				&& $client_response->fundtransferresponse->status->code == "200") {

						$game_details = Game::find($request_params["gamecode"]);
						
						$json_data = [
							'transid' => $request_params['txnid'],
							'amount' => $request_params['amount'],
							'reason' => '',
							'roundid' => 0
						];
						GameTransaction::save('debit', $json_data, $game_details, $client_details, $player_details);

						header("Content-type: text/xml; charset=utf-8");
				 		$response = '<?xml version="1.0" encoding="utf-8"?>';
				 		$response .= '<RequestResponse><username>'.$request_params['username'].'</username><currency>USD</currency><amount>'.$client_response->fundtransferresponse->balance.'</amount><error>0</error></RequestResponse>';
					}
				}
				
			}
		}
		
		Helper::saveLog('debit', 2, file_get_contents("php://input"), $response);
		echo $response;
	}

	public function creditProcess(Request $request) 
	{
		$string = file_get_contents("php://input");
		$decrypted_string = $this->decrypt(urldecode($string));
		$query = parse_url('http://test.url?'.$decrypted_string, PHP_URL_QUERY);
		parse_str($query, $request_params);

		$client_code = RouteParam::get($request, 'brand_code');

		$response = [
						"errorcode" =>  "PLAYER_NOT_FOUND",
						"errormessage" => "Player not found",
						"httpstatus" => "404"
					];

		$client_details = $this->_getClientDetails($client_code);
		$player_details = PlayerHelper::getPlayerDetails($request_params['username'], 'username');
		
		if ($client_details && $player_details != NULL) {
			//GameRound::create($json_data['roundid'], $player_details->token_id);

			// Check if the game is available for the client
			$subscription = new GameSubscription();
			$client_game_subscription = $subscription->check($client_details->client_id, 4, $request_params['gamecode']);

			if(!$client_game_subscription) {
				$response = [
						"errorcode" =>  "GAME_NOT_FOUND",
						"errormessage" => "Game not found",
						"httpstatus" => "404"
					];
			}
			else
			{
				$client = new Client([
				    'headers' => [ 
				    	'Content-Type' => 'application/json',
				    	'Authorization' => 'Bearer '.$client_details->client_access_token
				    ]
				]);
				
				$guzzle_response = $client->post($client_details->fund_transfer_url,
				    ['body' => json_encode(
				        	[
							  "access_token" => $client_details->client_access_token,
							  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
							  "type" => "fundtransferrequest",
							  "datetsent" => "",
							  "gamedetails" => [
							    "gameid" => "",
							    "gamename" => ""
							  ],
							  "fundtransferrequest" => [
									"playerinfo" => [
									"token" => $player_details->player_token
								],
								"fundinfo" => [
								      "gamesessionid" => "",
								      "transactiontype" => "credit",
								      "transferid" => "",
								      "rollback" => "false",
								      "currencycode" => $player_details->currency,
								      "amount" => $request_params["amount"]
								]
							  ]
							]
				    )]
				);

				$client_response = json_decode($guzzle_response->getBody()->getContents());


				if(isset($client_response->fundtransferresponse->status->code) 
			&& $client_response->fundtransferresponse->status->code == "402") {
					$response = [
						"errorcode" =>  "NOT_SUFFICIENT_FUNDS",
						"errormessage" => "Not sufficient funds",
						"httpstatus" => "402"
					];
				}
				else
				{
					if(isset($client_response->fundtransferresponse->status->code) 
				&& $client_response->fundtransferresponse->status->code == "200") {

						$game_details = Game::find($request_params["gamecode"]);
						
						$json_data = [
							'transid' => $request_params['txnid'],
							'amount' => $request_params['amount'],
							'reason' => '',
							'roundid' => 0
						];
						GameTransaction::save('credit', $json_data, $game_details, $client_details, $player_details);

						header("Content-type: text/xml; charset=utf-8");
				 		$response = '<?xml version="1.0" encoding="utf-8"?>';
				 		$response .= '<RequestResponse><username>'.$request_params['username'].'</username><currency>USD</currency><amount>'.$client_response->fundtransferresponse->balance.'</amount><error>0</error></RequestResponse>';
					}
				}
				
			}
		}
		
		Helper::saveLog('debit', 2, file_get_contents("php://input"), $response);
		echo $response;

	}

	public function lostTransaction(Request $request) 
	{
		$string = file_get_contents("php://input");
		$decrypted_string = $this->decrypt(urldecode($string));
		$query = parse_url('http://test.url?'.$decrypted_string, PHP_URL_QUERY);
		parse_str($query, $request_params);

		$client_code = RouteParam::get($request, 'brand_code');

		$response = [
						"errorcode" =>  "PLAYER_NOT_FOUND",
						"errormessage" => "Player not found",
						"httpstatus" => "404"
					];

		$client_details = $this->_getClientDetails($client_code);
		$player_details = PlayerHelper::getPlayerDetails($request_params['username'], 'username');
		
		if ($client_details && $player_details != NULL) {
			//GameRound::create($json_data['roundid'], $player_details->token_id);

			// Check if the game is available for the client
			$subscription = new GameSubscription();
			$client_game_subscription = $subscription->check($client_details->client_id, 4, $request_params['gamecode']);

			if(!$client_game_subscription) {
				$response = [
						"errorcode" =>  "GAME_NOT_FOUND",
						"errormessage" => "Game not found",
						"httpstatus" => "404"
					];
			}
			else
			{
				$client = new Client([
				    'headers' => [ 
				    	'Content-Type' => 'application/json',
				    	'Authorization' => 'Bearer '.$client_details->client_access_token
				    ]
				]);
				
				$guzzle_response = $client->post($client_details->fund_transfer_url,
				    ['body' => json_encode(
				        	[
							  "access_token" => $client_details->client_access_token,
							  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
							  "type" => "fundtransferrequest",
							  "datetsent" => "",
							  "gamedetails" => [
							    "gameid" => "",
							    "gamename" => ""
							  ],
							  "fundtransferrequest" => [
									"playerinfo" => [
									"token" => $player_details->player_token
								],
								"fundinfo" => [
								      "gamesessionid" => "",
								      "transactiontype" => "debit",
								      "transferid" => "",
								      "rollback" => "false",
								      "currencycode" => $player_details->currency,
								      "amount" => 0
								]
							  ]
							]
				    )]
				);

				$client_response = json_decode($guzzle_response->getBody()->getContents());

				/*var_dump($client_response); die();*/

				if(isset($client_response->fundtransferresponse->status->code) 
			&& $client_response->fundtransferresponse->status->code == "402") {
					$response = [
						"errorcode" =>  "NOT_SUFFICIENT_FUNDS",
						"errormessage" => "Not sufficient funds",
						"httpstatus" => "402"
					];
				}
				else
				{
					if(isset($client_response->fundtransferresponse->status->code) 
				&& $client_response->fundtransferresponse->status->code == "200") {

						$game_details = Game::find($request_params["gamecode"]);
						
						$json_data = [
							'transid' => $request_params['txnid'],
							'amount' => 0,
							'reason' => '',
							'roundid' => 0
						];
						GameTransaction::save('debit', $json_data, $game_details, $client_details, $player_details);

						header("Content-type: text/xml; charset=utf-8");
				 		$response = '<?xml version="1.0" encoding="utf-8"?>';
				 		$response .= '<RequestResponse><username>'.$request_params['username'].'</username><currency>USD</currency><amount>'.$client_response->fundtransferresponse->balance.'</amount><error>0</error></RequestResponse>';
					}
				}
				
			}
		}
		
		Helper::saveLog('debit', 2, file_get_contents("php://input"), $response);
		echo $response;
	}

	public function rollBackTransaction(Request $request) 
	{
		$string = file_get_contents("php://input");
		$decrypted_string = $this->decrypt(urldecode($string));
		$query = parse_url('http://test.url?'.$decrypted_string, PHP_URL_QUERY);
		parse_str($query, $request_params);

		$client_code = RouteParam::get($request, 'brand_code');

		$response = [
					"errorcode" =>  "PLAYER_NOT_FOUND",
					"errormessage" => "The provided playerid don’t exist.",
					"httpstatus" => "404"
				];

		$client_details = $this->_getClientDetails($client_code);
		$player_details = PlayerHelper::getPlayerDetails($request_params['username'], 'username');

		if ($client_details && $player_details != NULL) {
			// Check if the transaction exist
			$game_transaction = GameTransaction::find($request_params['txn_reverse_id']);

			// If transaction is not found
			if(!$game_transaction) {
				$response = [
					"errorcode" =>  "TRANS_NOT_FOUND",
					"errormessage" => "Transaction not found",
					"httpstatus" => "404"
				];
			}
			else
			{
				// If transaction is found, send request to the client
				$client = new Client([
				    'headers' => [ 
				    	'Content-Type' => 'application/json',
				    	'Authorization' => 'Bearer '.$client_details->client_access_token
				    ]
				]);
				
				$guzzle_response = $client->post($client_details->fund_transfer_url,
				    ['body' => json_encode(
				        	[
							  "access_token" => $client_details->client_access_token,
							  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
							  "type" => "fundtransferrequest",
							  "datetsent" => "",
							  "gamedetails" => [
							    "gameid" => "",
							    "gamename" => ""
							  ],
							  "fundtransferrequest" => [
									"playerinfo" => [
									"token" => $player_details->player_token
								],
								"fundinfo" => [
								      "gamesessionid" => "",
								      "transactiontype" => "credit",
								      "transferid" => "",
								      "rollback" => "true",
								      "currencycode" => $player_details->currency,
								      "amount" => $game_transaction->bet_amount
								]
							  ]
							]
				    )]
				);

				$client_response = json_decode($guzzle_response->getBody()->getContents());

				// If client returned a success response
				if($client_response->fundtransferresponse->status->code == "200") {
					
					$json_data = [
							'transid' => $request_params['txnid'],
							'amount' => 0,
							'reason' => '',
							'roundid' => 0
						];
					GameTransaction::save('rollback', $json_data, $game_transaction, $client_details, $player_details);
					
					$response = [
						"status" => "OK",
						"currency" => $client_response->fundtransferresponse->currencycode,
						"balance" => $client_response->fundtransferresponse->balance,
					];
				}
			}
			
		}
		

		Helper::saveLog('rollback', 4, file_get_contents("php://input"), $response);
		echo json_encode($response);

	}

	private function _getClientDetails($client_code) {

		$query = DB::table("clients AS c")
				 ->select('c.client_id', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
				 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
				 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id")
				 ->where('client_code', $client_code);

				 $result= $query->first();

		return $result;
	}

	private function encrypt($str) {
		return base64_encode( openssl_encrypt($str, 'DES-CBC', $this->key, OPENSSL_RAW_DATA, $this->iv  ) );
	}
    private function decrypt($str) {
		$str = openssl_decrypt(base64_decode($str), 'DES-CBC', $this->key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $this->iv);
		return rtrim($str, "\x01..\x1F");
    }
    private function pkcs5Pad($text, $blocksize) {
        $pad = $blocksize - (strlen ( $text ) % $blocksize);
        return $text . str_repeat ( chr ( $pad ), $pad );
    }

    private function sendResponse($content) {

	    $response = '<?xml version="1.0" encoding="utf-8"?>';
	    $response .= '<response><status>'.$type.'</status>';

	            $response = $response.'<remarks>'.$cause.'</remarks></response>';
	            return $response;
	 }

}
