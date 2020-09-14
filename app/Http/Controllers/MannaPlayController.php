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
use App\Helpers\ProviderHelper;
use App\Helpers\ClientRequestHelper;

use App\Support\RouteParam;

use Illuminate\Http\Request;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;

use DB;

class MannaPlayController extends Controller
{
    public function __construct(){
		/*$this->middleware('oauth', ['except' => ['index']]);*/
		/*$this->middleware('authorize:' . __CLASS__, ['except' => ['index', 'store']]);*/
	}

	public function getBalance(Request $request) 
	{
		$http_status = 200;
		$response = [
						"errorCode" =>  10100,
						"message" => "Server is not ready!",
					];

		$json_data = json_decode(file_get_contents("php://input"), true);
		$api_key = $request->header('apiKey');

		if(!CallParameters::check_keys($json_data, 'account', 'sessionId')) {
				$http_status = 200;
				$response = [
						"errorCode" =>  10102,
						"message" => "Post data is invalid!",
					];
		}
		else
		{
			if($api_key != config("providerlinks.manna.CLIENT_API_KEY")) {
				$http_status = 200;
				$response = [
							"errorCode" =>  10105,
							"message" => "Authenticate fail!",
						];
			}
			else
			{
				$http_status = 200;
				$response = [
								"errorCode" =>  10204,
								"message" => "Account is not exist!",
							];

				// Find the player and client details
				$client_details = $this->_getClientDetails('token', $json_data['sessionId']);
				
				if ($client_details) {
					$client_response = ClientRequestHelper::playerDetailsCall($client_details->player_token);
					
					$client_response = json_decode($guzzle_response->getBody()->getContents());
					if(isset($client_response->playerdetailsresponse->status->code) 
					&& $client_response->playerdetailsresponse->status->code == "200") {

						$http_status = 200;
						$response = [
							"balance" => bcdiv($client_response->playerdetailsresponse->balance, 1, 2)
						];
					}
				
				}
			}
		}

		Helper::saveLog('manna_balance', 16, file_get_contents("php://input"), $response);
		return response()->json($response, $http_status);

	}

	public function debitProcess(Request $request) 
	{
		$http_status = 200;
		$response = [
						"errorCode" =>  10100,
						"message" => "Server is not ready!",
					];

		$json_data = json_decode(file_get_contents("php://input"), true);
		$api_key = $request->header('apiKey');

		if(!CallParameters::check_keys($json_data, 'account', 'sessionId', 'amount', 'game_id', 'round_id', 'transaction_id')) {
				$http_status = 200;
				$response = [
						"errorCode" =>  10102,
						"message" => "Post data is invalid!",
					];
		}
		else
		{
			if($api_key != config("providerlinks.manna.CLIENT_API_KEY")) {
				$http_status = 200;
				$response = [
							"errorCode" =>  10105,
							"message" => "Authenticate fail!",
						];
			}
			else
			{
				$http_status = 200;
				$response = [
								"errorCode" =>  10204,
								"message" => "Account is not exist!",
							];

				$body = ['error' => 'true'];
				$game_transaction_id = 0;
				$client_response = ['error' => 'true'];

				$client_details = $this->_getClientDetails('token', $json_data['sessionId']);

				if ($client_details/* && $player_details != NULL*/) {
					GameRound::create($json_data['round_id'], $client_details->token_id);

					// Check if the game is available for the client
					$subscription = new GameSubscription();
					$client_game_subscription = $subscription->check($client_details->client_id, 16, $json_data['game_id']);
					
					if(!$client_game_subscription) {
						$http_status = 200;
							$response = [
								"errorCode" =>  10109,
								"message" => "Game not found!",
							];
					}
					else
					{
						if(!GameRound::check($json_data['round_id'])) {
							$http_status = 200;
							$response = [
								"errorCode" =>  10209,
								"message" => "Round id is exists!",
							];
						}
						else
						{
							$json_data['income'] = $json_data['amount'];
							$json_data['roundid'] = $json_data['round_id'];
							$json_data['transid'] = $json_data['transaction_id'];
							$game_details = Game::find($json_data["game_id"], config("providerlinks.manna.PROVIDER_ID"));

							$game_transaction_id = GameTransaction::save('debit', $json_data, $game_details, $client_details, $client_details);

							$game_trans_ext_id = ProviderHelper::createGameTransExtV2($game_transaction_id, $json_data['transaction_id'], $json_data['round_id'], $json_data['amount'], 1);

							// change $json_data['round_id'] to $game_transaction_id
			                $client_response = ClientRequestHelper::fundTransfer($client_details, $json_data['amount'], $game_details->game_code, $game_details->game_name, $game_trans_ext_id, $game_transaction_id, 'debit');
							

							if(isset($client_response->fundtransferresponse->status->code) 
						&& $client_response->fundtransferresponse->status->code == "402") {
								$http_status = 200;
								$response = [
												"errorCode" =>  10203,
												"message" => "Insufficient balance",
											];

							}
							else
							{
								if(isset($client_response->fundtransferresponse->status->code) 
							&& $client_response->fundtransferresponse->status->code == "200") {

									$http_status = 200;
									$response = [
										"transaction_id" => $json_data['transaction_id'],
										"balance" => bcdiv($client_response->fundtransferresponse->balance, 1, 2) 
									];
								}
							}

							ProviderHelper::updatecreateGameTransExt($game_trans_ext_id, $json_data, $response, $client_response->requestoclient, $client_response, $json_data);
						}
					}
				}
			}
		}
		
		Helper::saveLog('manna_debit', 16, file_get_contents("php://input"), $response);
		return response()->json($response, $http_status);

	}

	public function creditProcess(Request $request)
	{
		$http_status = 200;
		$response = [
						"errorCode" =>  10100,
						"message" => "Server is not ready!",
					];

		$json_data = json_decode(file_get_contents("php://input"), true);
		/*$client_code = RouteParam::get($request, 'brand_code');*/
		$api_key = $request->header('apiKey');

		if(!CallParameters::check_keys($json_data, 'account', 'sessionId', 'amount', 'game_id', 'round_id', 'transaction_id')) {

				$http_status = 200;
				$response = [
						"errorCode" =>  10102,
						"message" => "Post data is invalid!",
					];
		}
		else
		{
			if($api_key != config("providerlinks.manna.CLIENT_API_KEY")) {
				$http_status = 200;
				$response = [
							"errorCode" =>  10105,
							"message" => "Authenticate fail!",
						];
			}
			else
			{
				$http_status = 200;
				$response = [
								"errorCode" =>  10204,
								"message" => "Account is not exist!",
							];

				$body = ['error' => 'true'];
				$game_transaction_id = 0;
				$client_response = ['error' => 'true'];

				$client_details = $this->_getClientDetails('token', $json_data['sessionId']);

				if ($client_details/* && $player_details != NULL*/) {

					// Check if the game is available for the client
					$subscription = new GameSubscription();
					$client_game_subscription = $subscription->check($client_details->client_id, 16, $json_data['game_id']);

					if(!$client_game_subscription) {
						$http_status = 200;
							$response = [
								"errorCode" =>  10109,
								"message" => "Game not found!",
							];
					}
					else
					{
						if(!GameRound::check($json_data['round_id'])) {
							$http_status = 200;
								$response = [
									"errorCode" =>  10209,
									"message" => "Round id is exists!",
								];
						}
						else
						{
							if($json_data['amount'] < 0) {
								$http_status = 200;
								$response = [
									"errorCode" =>  10201,
									"message" => "Warning value must not be less 0.",
								];
							}
							else
							{
								if(GameTransaction::find($json_data['transaction_id']) ) {
									$http_status = 200;
									$response = [
										"errorCode" =>  10208,
										"message" => "Transaction id is exists!",
									];
								}
								else
								{
									$game_details = Game::find($json_data["game_id"], config("providerlinks.manna.PROVIDER_ID"));

									$json_data['income'] = $json_data['amount'] - $json_data["amount"];
									$json_data['roundid'] = $json_data['round_id'];
									$json_data['transid'] = $json_data['transaction_id'];

									$game_transaction_id = GameTransaction::update('credit', $json_data, $game_details, $client_details, $client_details);

									$game_trans_ext_id = ProviderHelper::createGameTransExtV2($game_transaction_id, $json_data['transaction_id'], $json_data['round_id'], $json_data['amount'], 1);

									// change $json_data['round_id'] to $game_transaction_id
			               			$client_response = ClientRequestHelper::fundTransfer($client_details, $json_data['amount'], $game_details->game_code, $game_details->game_name, $game_trans_ext_id, $game_transaction_id, 'credit');

									if(isset($client_response->fundtransferresponse->status->code) 
								&& $client_response->fundtransferresponse->status->code == "200") {
										
										$http_status = 200;
										$response = [
											"transaction_id" => $json_data['transaction_id'],
											"balance" => bcdiv($client_response->fundtransferresponse->balance, 1, 2) 
										];
									}

									ProviderHelper::updatecreateGameTransExt($game_trans_ext_id, $json_data, $response, $client_response->requestoclient, $client_response, $json_data);
								}
							}
						}
					}
				}
			}
		}
		
		Helper::saveLog('manna_credit', 16, file_get_contents("php://input"), $response);
		return response()->json($response, $http_status);

	}

	public function rollBackTransaction(Request $request) 
	{
		$http_status = 200;
		$response = [
						"errorCode" =>  10100,
						"message" => "Server is not ready!",
					];

		$json_data = json_decode(file_get_contents("php://input"), true);
		/*$client_code = RouteParam::get($request, 'brand_code');*/
		$api_key = $request->header('apiKey');

		if(!CallParameters::check_keys($json_data, 'sessionId', 'amount', 'game_id', 'round_id', 'transaction_id')) {
				$http_status = 200;
				$response = [
						"errorCode" =>  10102,
						"message" => "Post data is invalid!",
					];
		}
		else
		{
			if($api_key != config("providerlinks.manna.CLIENT_API_KEY")) {
				$http_status = 200;
				$response = [
							"errorCode" =>  10105,
							"message" => "Authenticate fail!",
						];
			}
			else
			{
				$http_status = 200;
				$response = [
								"errorCode" =>  10204,
								"message" => "Account is not exist!",
							];



				$client_details = $this->_getClientDetails('token', $json_data['sessionId']);
				/*$player_details = PlayerHelper::getPlayerDetails($json_data['sessionId'], 'token');*/

				if ($client_details/* && $player_details != NULL*/) {
					
					// Check if "originaltransid" is present in the Solid Gaming request
					if(array_key_exists('transaction_id', $json_data)) {
						
						// Check if the transaction exist
						$game_transaction = GameTransaction::find($json_data['transaction_id']);

						// If transaction is not found
						if(!$game_transaction) {
							$http_status = 200;
							$response = [
								"errorCode" =>  10210,
								"message" => "Target transaction id not found!",
							];
						}
						else
						{
							// If transaction is found, send request to the client
							$json_data['roundid'] = $json_data['round_id'];
							$json_data['transid'] = $json_data['transaction_id'];
							$json_data['income'] = 0;
							$game_details = Game::find($json_data["game_id"], config("providerlinks.manna.PROVIDER_ID"));

							$game_transaction_id = GameTransaction::save('rollback', $json_data, $game_transaction, $client_details, $client_details);

							$game_trans_ext_id = ProviderHelper::createGameTransExtV2($game_transaction_id, $json_data['transaction_id'], $json_data['round_id'], $json_data['amount'], 3);

							// change $json_data['round_id'] to $game_transaction_id
	               			$client_response = ClientRequestHelper::fundTransfer($client_details, $json_data['amount'], $game_details->game_code, $game_details->game_name, $game_trans_ext_id, $game_transaction_id, 'credit', true);

							
							// If client returned a success response
							if($client_response->fundtransferresponse->status->code == "200") {
								
								$http_status = 200;
								$response = [
									"transaction_id" => $json_data['transaction_id'],
									/*"balance" => bcdiv($client_response->fundtransferresponse->balance, 1, 2) */
								];
							}

							ProviderHelper::updatecreateGameTransExt($game_trans_ext_id, $json_data, $response, $client_response->requestoclient, $client_response, $json_data);
						}
					}
					
				}
			}
		}

		Helper::saveLog('manna_rollback', 16, file_get_contents("php://input"), $response);
		return response()->json($response, $http_status);

	}


	/*private function _getClientDetails($client_code) {
		$query = DB::table("clients AS c")
				 ->select('c.client_id', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
				 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
				 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id")
				 ->where('client_code', $client_code);

				 $result= $query->first();

		return $result;
	}*/

	private function _getClientDetails($type = "", $value = "") {
		$query = DB::table("clients AS c")
				 ->select('p.client_id', 'p.player_id', 'p.client_player_id', 'p.username', 'p.email', 'p.language', 'c.default_currency', 'c.default_currency AS currency', 'pst.token_id', 'pst.player_token' , 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
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


}
