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

class VivoController extends Controller
{
    public function __construct(){
		/*$this->middleware('oauth', ['except' => ['index']]);*/
		/*$this->middleware('authorize:' . __CLASS__, ['except' => ['index', 'store']]);*/
	}

	public function show(Request $request) { }

	public function authPlayer(Request $request)
	{
		$client_code = RouteParam::get($request, 'brand_code');
		$client_details = $this->_getClientDetails('token', $request->token);
		
		header("Content-type: text/xml; charset=utf-8");
			$response = '<?xml version="1.0" encoding="utf-8"?>';
			$response .= '<VGSSYSTEM>
							<REQUEST>
								<TOKEN>'.$request->token.'</TOKEN>
								<HASH>'.$request->hash.'</HASH>
							</REQUEST>
							<TIME>'.Helper::datesent().'</TIME>
							<RESPONSE>
								<RESULT>FAILED</RESULT>
								<CODE>400</CODE>
							</RESPONSE>
						</VGSSYSTEM>';

		$hash = md5($request->token.env('VIVO_PASS_KEY'));

		if($hash != $request->hash) {
			header("Content-type: text/xml; charset=utf-8");
			$response = '<?xml version="1.0" encoding="utf-8"?>';
			$response .= '<VGSSYSTEM>
							<REQUEST>
								<TOKEN>'.$request->token.'</TOKEN>
								<HASH>'.$request->hash.'</HASH>
							</REQUEST>
							<TIME>'.Helper::datesent().'</TIME>
							<RESPONSE>
								<RESULT>FAILED</RESULT>
								<CODE>500</CODE>
							</RESPONSE>
						</VGSSYSTEM>';
		}
		else
		{
			if ($client_details) {
				$client = new Client([
				    'headers' => [ 
				    	'Content-Type' => 'application/json',
				    	'Authorization' => 'Bearer '.$client_details->client_access_token
				    ]
				]);

				$body = json_encode(
				        	["access_token" => $client_details->client_access_token,
								"hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
								"type" => "playerdetailsrequest",
								"datesent" => Helper::datesent(),
								"gameid" => "",
								"clientid" => $client_details->client_id,
								"playerdetailsrequest" => [
									"client_player_id" => $client_details->client_player_id,
									"token" => $request->token,
									"gamelaunch" => true
								]]);

				$guzzle_response = $client->post($client_details->player_details_url,
				    ['body' => $body]
				);

				
				$client_response = json_decode($guzzle_response->getBody()->getContents());

				if(isset($client_response->playerdetailsresponse->status->code) 
					&& $client_response->playerdetailsresponse->status->code == "200") {

					$http_status = 400;

					// save player details if not exist
					$player_id = PlayerHelper::saveIfNotExist($client_details, $client_response);

					// save token to system if not exist
					TokenHelper::saveIfNotExist($player_id, $request->token);

					header("Content-type: text/xml; charset=utf-8");
			 		$response = '<?xml version="1.0" encoding="utf-8"?>';
			 		$response .= '<VGSSYSTEM>
			 						<REQUEST>
				 						<TOKEN>'.$request->token.'</TOKEN>
				 						<HASH>'.$request->hash.'</HASH>
			 						</REQUEST>
			 						<TIME>'.Helper::datesent().'</TIME>
			 						<RESPONSE>
			 							<RESULT>OK</RESULT>
			 							<USERID>'.$player_id.'</USERID>
			 							<USERNAME>'.$client_response->playerdetailsresponse->accountname.'</USERNAME>
			 							<FIRSTNAME></FIRSTNAME>
			 							<LASTNAME></LASTNAME>
			 							<EMAIL>'.$client_response->playerdetailsresponse->email.'</EMAIL>
			 							<CURRENCY>'.$client_details->currency.'</CURRENCY>
			 							<BALANCE>'.$client_response->playerdetailsresponse->balance.'</BALANCE>
			 							<GAMESESSIONID></GAMESESSIONID>
			 						</RESPONSE>
			 					</VGSSYSTEM>';
				}
				else
				{
					// change token status to expired
					// TokenHelper::changeStatus($player_id, 'expired');
				}
			}
			
		}

		

		Helper::saveLog('vivo_authentication', 34, file_get_contents("php://input"), $response);
		echo $response;

	}

	public function gameTransaction(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		$client_code = RouteParam::get($request, 'brand_code');
		
		if($this->_isIdempotent($request->TransactionID)) {
			header("Content-type: text/xml; charset=utf-8");
			return '<?xml version="1.0" encoding="utf-8"?>'. $this->_isIdempotent($request->TransactionID)->mw_response;
		}

		$response = '';
		$response .= '<VGSSYSTEM><REQUEST><USERID>'.$request->userId.'</USERID><AMOUNT>'.$request->Amount.'</AMOUNT><TRANSACTIONID >'.$request->TransactionID.'</TRANSACTIONID><TRNTYPE>'.$request->TrnType.'</TRNTYPE><GAMEID>'.$request->gameId.'</GAMEID><ROUNDID>'.$request->roundId.'</ROUNDID><TRNDESCRIPTION>'.$request->TrnDescription.'</TRNDESCRIPTION><HISTORY>'.$request->History.'</HISTORY><ISROUNDFINISHED>'.$request->isRoundFinished.'</ISROUNDFINISHED><HASH>'.$request->hash.'</HASH></REQUEST><TIME>'.Helper::datesent().'</TIME><RESPONSE><RESULT>FAILED</RESULT><CODE>300</CODE></RESPONSE></VGSSYSTEM>';

		$client_details = $this->_getClientDetails('player_id', $request->userId);

		$hash = md5($request->userId.$request->Amount.$request->TrnType.$request->TrnDescription.$request->roundId.$request->gameId.$request->History.env('VIVO_PASS_KEY'));

		if($hash != $request->hash) {

			$response = '<VGSSYSTEM><REQUEST><USERID>'.$request->userId.'</USERID><AMOUNT>'.$request->Amount.'</AMOUNT><TRANSACTIONID >'.$request->TransactionID.'</TRANSACTIONID><TRNTYPE>'.$request->TrnType.'</TRNTYPE><GAMEID>'.$request->gameId.'</GAMEID><ROUNDID>'.$request->roundId.'</ROUNDID><TRNDESCRIPTION>'.$request->TrnDescription.'</TRNDESCRIPTION><HISTORY>'.$request->History.'</HISTORY><ISROUNDFINISHED>'.$request->isRoundFinished.'</ISROUNDFINISHED><HASH>'.$request->hash.'</HASH></REQUEST><TIME>'.Helper::datesent().'</TIME><RESPONSE><RESULT>FAILED</RESULT><CODE>500</CODE></RESPONSE></VGSSYSTEM>';
		}
		else
		{
			if ($client_details) {
			GameRound::create($request->roundId, $client_details->token_id);

			if(!GameRound::check($request->roundId)) {
				$response = '<VGSSYSTEM><REQUEST><USERID>'.$request->userId.'</USERID><AMOUNT>'.$request->Amount.'</AMOUNT><TRANSACTIONID >'.$request->TransactionID.'</TRANSACTIONID><TRNTYPE>'.$request->TrnType.'</TRNTYPE><GAMEID>'.$request->gameId.'</GAMEID><ROUNDID>'.$request->roundId.'</ROUNDID><TRNDESCRIPTION>'.$request->TrnDescription.'</TRNDESCRIPTION><HISTORY>'.$request->History.'</HISTORY><ISROUNDFINISHED>'.$request->isRoundFinished.'</ISROUNDFINISHED><HASH>'.$request->hash.'</HASH></REQUEST><TIME>'.Helper::datesent().'</TIME><RESPONSE><RESULT>FAILED</RESULT><CODE>300</CODE></RESPONSE></VGSSYSTEM>';
			}
			else
			{
				$client = new Client([
				    'headers' => [ 
				    	'Content-Type' => 'application/json',
				    	'Authorization' => 'Bearer '.$client_details->client_access_token
				    ]
				]);

					if($request->TrnType == 'CANCELED_BET') {
						
						// Check if the transaction exist
						/*$game_transaction = GameTransaction::find($request->TransactionID);
						
						// If transaction is not found
						if(!$game_transaction) {
							$response = '<VGSSYSTEM><REQUEST><USERID>'.$request->userId.'</USERID><AMOUNT>'.$request->Amount.'</AMOUNT><TRANSACTIONID >'.$request->TransactionID.'</TRANSACTIONID><TRNTYPE>'.$request->TrnType.'</TRNTYPE><GAMEID>'.$request->gameId.'</GAMEID><ROUNDID>'.$request->roundId.'</ROUNDID><TRNDESCRIPTION>'.$request->TrnDescription.'</TRNDESCRIPTION><HISTORY>'.$request->History.'</HISTORY><ISROUNDFINISHED>'.$request->isRoundFinished.'</ISROUNDFINISHED><HASH>'.$request->hash.'</HASH></REQUEST><TIME>'.Helper::datesent().'</TIME><RESPONSE><RESULT>FAILED</RESULT><CODE>300</CODE></RESPONSE></VGSSYSTEM>';
						}
						else
						{*/
							// If transaction is found, send request to the client
							$client = new Client([
							    'headers' => [ 
							    	'Content-Type' => 'application/json',
							    	'Authorization' => 'Bearer '.$client_details->client_access_token
							    ]
							]);
							
							$body = json_encode(
							        	[
										  "access_token" => $client_details->client_access_token,
										  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
										  "type" => "fundtransferrequest",
										  "datesent" => Helper::datesent(),
										  "gamedetails" => [
										    "gameid" => "",
										    "gamename" => ""
										  ],
										  "fundtransferrequest" => [
												"playerinfo" => [
													"client_player_id" => $client_details->client_player_id,
													"token" => $client_details->player_token
											],
											"fundinfo" => [
											      "gamesessionid" => "",
											      "transactiontype" => "credit",
											      "transferid" => "",
											      "rollback" => "true",
											      "currencycode" => $client_details->currency,
											      "amount" => $request->Amount
											]
										  ]
										]
							    );

							$guzzle_response = $client->post($client_details->fund_transfer_url,
							    ['body' => $body]
							);

							$client_response = json_decode($guzzle_response->getBody()->getContents());

							// If client returned a success response
							if($client_response->fundtransferresponse->status->code == "200") {
								$new_request = $request->all();
								$new_request['roundid'] = $request->roundId;
								$new_request['transid'] = $request->TransactionID;
								$new_request['amount'] = $request->Amount;
								$new_request['reason'] = NULL;
								$new_request['income'] = 0;

								$game_trans_array = ['pay_amount' => $request->Amount, 'game_trans_id' => 'N/A', 'game_id' => $request->gameId];
								$game_transaction = (object) $game_trans_array;								

								$transaction_id = GameTransaction::save('rollback', $new_request, $game_transaction, $client_details, $client_details);

								$response = '<VGSSYSTEM><REQUEST><USERID>'.$request->userId.'</USERID><AMOUNT>'.$request->Amount.'</AMOUNT><TRANSACTIONID>'.$request->TransactionID.'</TRANSACTIONID><TRNTYPE>'.$request->TrnType.'</TRNTYPE><GAMEID>'.$request->gameId.'</GAMEID><ROUNDID>'.$request->roundId.'</ROUNDID><TRNDESCRIPTION>'.$request->TrnDescription.'</TRNDESCRIPTION><HISTORY>'.$request->History.'</HISTORY><ISROUNDFINISHED>'.$request->isRoundFinished.'</ISROUNDFINISHED><HASH>'.$request->hash.'</HASH></REQUEST><TIME>'.Helper::datesent().'</TIME><RESPONSE><RESULT>OK</RESULT><ECSYSTEMTRANSACTIONID>'.$transaction_id.'</ECSYSTEMTRANSACTIONID><BALANCE>'.$client_response->fundtransferresponse->balance.'</BALANCE></RESPONSE></VGSSYSTEM>';	
								
						 		Helper::createVivoGameTransactionExt($transaction_id, $new_request, $body, $response, $client_response, 3);
							}
						/*}*/
					}
					else
					{
						$transactiontype = $request->TrnType;
						$key = ($transactiontype == 'BET' ? "bet" : "win");

						$body = json_encode(
						        	[
									  "access_token" => $client_details->client_access_token,
									  "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
									  "type" => "fundtransferrequest",
									  "datesent" => Helper::datesent(),
									  "gamedetails" => [
									    "gameid" => "",
									    "gamename" => ""
									  ],
									  "fundtransferrequest" => [
											"playerinfo" => [
												"client_player_id" => $client_details->client_player_id,
												"token" => $client_details->player_token
										],
										"fundinfo" => [
										      "gamesessionid" => "",
										      "transactiontype" => ($transactiontype == 'BET' ? "debit" : "credit"),
										      "transferid" => "",
										      "rollback" => "false",
										      "currencycode" => $client_details->currency,
										      "amount" => $request->Amount
										]
									  ]
									]
						    );

						$guzzle_response = $client->post($client_details->fund_transfer_url,
						    ['body' => $body]
						);

						$client_response = json_decode($guzzle_response->getBody()->getContents());

						if(isset($client_response->fundtransferresponse->status->code) 
					&& $client_response->fundtransferresponse->status->code == "402") {

							$response = '<VGSSYSTEM><REQUEST><USERID>'.$request->userId.'</USERID><AMOUNT>'.$request->Amount.'</AMOUNT><TRANSACTIONID >'.$request->TransactionID.'</TRANSACTIONID><TRNTYPE>'.$request->TrnType.'</TRNTYPE><GAMEID>'.$request->gameId.'</GAMEID><ROUNDID>'.$request->roundId.'</ROUNDID><TRNDESCRIPTION>'.$request->TrnDescription.'</TRNDESCRIPTION><HISTORY>'.$request->History.'</HISTORY><ISROUNDFINISHED>'.$request->isRoundFinished.'</ISROUNDFINISHED><HASH>'.$request->hash.'</HASH></REQUEST><TIME>'.Helper::datesent().'</TIME><RESPONSE><RESULT>FAILED</RESULT><CODE>300</CODE></RESPONSE></VGSSYSTEM>';
						}
						else
						{
							if(isset($client_response->fundtransferresponse->status->code) 
						&& $client_response->fundtransferresponse->status->code == "200") {

								$json_data['roundid'] = $request->roundId;
								$json_data['transid'] = $request->TransactionID;
								$json_data['amount'] = $request->Amount;
								$json_data['income'] = $request->Amount;
								$json_data['reason'] = NULL;

								$game_details = Game::findbyid($request->gameId);

								$transaction_id = GameTransaction::save(($transactiontype == 'BET' ? "debit" : "credit"), $json_data, $game_details, $client_details, $client_details);

						 		$response = '<VGSSYSTEM><REQUEST><USERID>'.$request->userId.'</USERID><AMOUNT>'.$request->Amount.'</AMOUNT><TRANSACTIONID>'.$request->TransactionID.'</TRANSACTIONID><TRNTYPE>'.$request->TrnType.'</TRNTYPE><GAMEID>'.$request->gameId.'</GAMEID><ROUNDID>'.$request->roundId.'</ROUNDID><TRNDESCRIPTION>'.$request->TrnDescription.'</TRNDESCRIPTION><HISTORY>'.$request->History.'</HISTORY><ISROUNDFINISHED>'.$request->isRoundFinished.'</ISROUNDFINISHED><HASH>'.$request->hash.'</HASH></REQUEST><TIME>'.Helper::datesent().'</TIME><RESPONSE><RESULT>OK</RESULT><ECSYSTEMTRANSACTIONID>'.$transaction_id.'</ECSYSTEMTRANSACTIONID><BALANCE>'.$client_response->fundtransferresponse->balance.'</BALANCE></RESPONSE></VGSSYSTEM>';
							}

							Helper::createVivoGameTransactionExt($transaction_id, $request->all(), $body, $response, $client_response, ($transactiontype == 'BET' ? 1 : 2));
						}
					}
				}
			}
		}

		$transactiontype = ($request->TrnType == 'BET' ? "debit" : "credit");
		Helper::saveLog($transactiontype, 34, file_get_contents("php://input"), $response);

		header("Content-type: text/xml; charset=utf-8");
		$final_response =  '<?xml version="1.0" encoding="utf-8"?>'. $response;
		echo $final_response;

	}

	public function transactionStatus(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);

		header("Content-type: text/xml; charset=utf-8");
		$response = '<?xml version="1.0" encoding="utf-8"?>';
		$response .= '<VGSSYSTEM>
						<REQUEST>
							<USERID>'.$request->userId.'</USERID>
							<HASH>'.$request->hash.'</HASH>
						</REQUEST>
						<TIME>'.Helper::datesent().'</TIME>
						<RESPONSE>
							<RESULT>FAILED</RESULT>
							<CODE>302</CODE>
						</RESPONSE>
					</VGSSYSTEM>';

		$client_details = $this->_getClientDetails('player_id', $request->userId);

		$hash = md5($request->userId.$request->casinoTransactionId.env('VIVO_PASS_KEY'));

		if($hash != $request->hash) {
			header("Content-type: text/xml; charset=utf-8");
			$response = '<?xml version="1.0" encoding="utf-8"?>';
			$response .= '<VGSSYSTEM>
							<REQUEST>
								<USERID>'.$request->userId.'</USERID>
								<HASH>'.$request->hash.'</HASH>
							</REQUEST>
							<TIME>'.Helper::datesent().'</TIME>
							<RESPONSE>
								<RESULT>FAILED</RESULT>
								<CODE>500</CODE>
							</RESPONSE>
						</VGSSYSTEM>';
		}
		else
		{
			
			// Check if the transaction exist
			$game_transaction = GameTransaction::find($request->casinoTransactionId);
			
			// If transaction is not found
			if($game_transaction) {
				header("Content-type: text/xml; charset=utf-8");
				$response = '<?xml version="1.0" encoding="utf-8"?>';
				$response .= '<VGSSYSTEM>
								<REQUEST>
									<USERID>'.$request->userId.'</USERID>
									<CASINOTRANSACTIONID>'.$request->casinoTransactionId.'</CASINOTRANSACTIONID>
									<HASH>'.$request->hash.'</HASH>
								</REQUEST>
								<TIME>'.Helper::datesent().'</TIME>
								<RESPONSE>
									<RESULT>OK</RESULT>
									<ECSYSTEMTRANSACTIONID>'.$game_transaction->game_trans_id.'</ECSYSTEMTRANSACTIONID>
								</RESPONSE>
							</VGSSYSTEM>';
			}

		}


		Helper::saveLog('status', 34, file_get_contents("php://input"), $response);
		echo $response;

	}

	public function getBalance(Request $request) 
	{
		$json_data = json_decode(file_get_contents("php://input"), true);
		$client_code = RouteParam::get($request, 'brand_code');

		header("Content-type: text/xml; charset=utf-8");
		$response = '<?xml version="1.0" encoding="utf-8"?>';
		$response .= '<VGSSYSTEM>
						<REQUEST>
							<USERID>'.$request->userId.'</USERID>
							<HASH>'.$request->hash.'</HASH>
						</REQUEST>
						<TIME>'.Helper::datesent().'</TIME>
						<RESPONSE>
							<RESULT>FAILED</RESULT>
							<CODE>302</CODE>
						</RESPONSE>
					</VGSSYSTEM>';

		$client_details = $this->_getClientDetails('player_id', $request->userId);

		$hash = md5($request->userId.env('VIVO_PASS_KEY'));

		if($hash != $request->hash) {
			header("Content-type: text/xml; charset=utf-8");
			$response = '<?xml version="1.0" encoding="utf-8"?>';
			$response .= '<VGSSYSTEM>
							<REQUEST>
								<USERID>'.$request->userId.'</USERID>
								<HASH>'.$request->hash.'</HASH>
							</REQUEST>
							<TIME>'.Helper::datesent().'</TIME>
							<RESPONSE>
								<RESULT>FAILED</RESULT>
								<CODE>500</CODE>
							</RESPONSE>
						</VGSSYSTEM>';
		}
		else
		{
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
									"token" => $client_details->player_token,
									"gamelaunch" => "false"
								]]
				    )]
				);

				$client_response = json_decode($guzzle_response->getBody()->getContents());
				
				if(isset($client_response->playerdetailsresponse->status->code) 
				&& $client_response->playerdetailsresponse->status->code == "200") {

					header("Content-type: text/xml; charset=utf-8");
					$response = '<?xml version="1.0" encoding="utf-8"?>';
					$response .= '<VGSSYSTEM>
									<REQUEST>
										<USERID>'.$request->userId.'</USERID>
										<HASH>'.$request->hash.'</HASH>
									</REQUEST>
									<TIME>'.Helper::datesent().'</TIME>
									<RESPONSE>
										<RESULT>OK</RESULT>
										<BALANCE>'.$client_response->playerdetailsresponse->balance.'</BALANCE>
									</RESPONSE>
								</VGSSYSTEM>';
				}
			}
		}

		Helper::saveLog('balance', 34, file_get_contents("php://input"), $response);
		echo $response;

	}

	private function _getClientDetails($type = "", $value = "") {
		$query = DB::table("clients AS c")
				 ->select('p.client_id', 'p.player_id', 'p.client_player_id', 'p.username', 'p.email', 'p.language', 'c.default_currency AS currency', 'pst.token_id', 'pst.player_token' , 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
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

	private function _isIdempotent($transaction_id, $is_rollback = false) {
		$result = false;
		$query = DB::table('game_transaction_ext')
								->where('provider_trans_id', $transaction_id);
		if ($is_rollback == true) {
					$query->where([
				 		["game_transaction_type", "=", 3]
				 	]);
				}

		$transaction_exist = $query->first();

		if($transaction_exist) {
			$result = $transaction_exist;
		}

		return $result;								
	}

	/*private function _getClientDetails($type = "", $value = "") {

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

	}*/


}
