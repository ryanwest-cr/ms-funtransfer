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

	public function getBalance(Request $request) 
	{
		$string = file_get_contents("php://input");
		$decrypted_string = $this->decrypt(urldecode($string));
		$query = parse_url('http://test.url?'.$decrypted_string, PHP_URL_QUERY);
		parse_str($query, $request_params);
		
		$client_code = RouteParam::get($request, 'brand_code');

		$response = '<?xml version="1.0" encoding="utf-8"?>';
				 		$response .= '<RequestResponse><error>1007</error></RequestResponse>';

		// Find the player and client details
		$client_details = $this->_getClientDetails('username', $request_params['username']);

		if ($client_details) {
			$client_response = Providerhelper::playerDetailsCall($client_details->player_token);

			if(isset($client_response->playerdetailsresponse->status->code) 
			&& $client_response->playerdetailsresponse->status->code == "200") {
				header("Content-type: text/xml; charset=utf-8");
		 		$response = '<?xml version="1.0" encoding="utf-8"?>';
		 		$response .= '<RequestResponse><username>'.$client_details->username.'</username><currency>USD</currency><amount>'.$client_response->playerdetailsresponse->balance.'</amount><error>0</error></RequestResponse>';

			}
			
		}
		
		Helper::saveLog('simpleplay_balance', 35, json_encode($request_params), $response);
 		echo $response;

	}

	public function debitProcess(Request $request) 
	{
		$string = file_get_contents("php://input");
		$decrypted_string = $this->decrypt(urldecode($string));
		$query = parse_url('http://test.url?'.$decrypted_string, PHP_URL_QUERY);
		parse_str($query, $request_params);

		header("Content-type: text/xml; charset=utf-8");
		$response = '<?xml version="1.0" encoding="utf-8"?>';
		$response .= '<RequestResponse><error>1007</error></RequestResponse>';

		$client_details = $this->_getClientDetails('username', $request_params['username']);
		
		if ($client_details) {
			//GameRound::create($json_data['roundid'], $player_details->token_id);

			// Check if the game is available for the client
			/*$subscription = new GameSubscription();
			$client_game_subscription = $subscription->check($client_details->client_id, 35, $request_params['gamecode']);

			if(!$client_game_subscription) {
				$response = '<?xml version="1.0" encoding="utf-8"?>';
				 		$response .= '<RequestResponse><username>'.$request_params['username'].'</username><currency>USD</currency><amount>0</amount><error>135</error></RequestResponse>';
			}
			else
			{*/
				$json_data['amount'] = $request_params['amount'];
				$json_data['income'] = $request_params['amount'];
				$json_data['roundid'] = 'N/A';
				$json_data['transid'] = $request_params['txnid'];
				$game_details = Game::find($request_params["gamecode"], config("providerlinks.simpleplay.PROVIDER_ID"));

				$game_transaction_id = GameTransaction::save('debit', $json_data, $game_details, $client_details, $client_details);

				$game_trans_ext_id = ProviderHelper::createGameTransExtV2($game_transaction_id, $request_params['txnid'], 'N/A', $request_params['amount'], 1);

				// change $json_data['roundid'] to $game_transaction_id
                $client_response = ClientRequestHelper::fundTransfer($client_details, $request_params['amount'], $game_details->game_code, $game_details->game_name, $game_trans_ext_id, $game_transaction_id, 'debit');

				if(isset($client_response->fundtransferresponse->status->code) 
			&& $client_response->fundtransferresponse->status->code == "402") {
					header("Content-type: text/xml; charset=utf-8");
					$response = '<?xml version="1.0" encoding="utf-8"?>';
				 	$response .= '<RequestResponse><error>1004</error></RequestResponse>';
				}
				else
				{
					if(isset($client_response->fundtransferresponse->status->code) 
				&& $client_response->fundtransferresponse->status->code == "200") {

						header("Content-type: text/xml; charset=utf-8");
				 		$response = '<?xml version="1.0" encoding="utf-8"?>';
				 		$response .= '<RequestResponse><username>'.$request_params['username'].'</username><currency>USD</currency><amount>'.$client_response->fundtransferresponse->balance.'</amount><error>0</error></RequestResponse>';
					}
				}
				
			/*}*/
		}
		
		Helper::saveLog('simpleplay_debit', 35, json_encode($request_params), $response);
		echo $response;
	}

	public function creditProcess(Request $request) 
	{
		$string = file_get_contents("php://input");
		$decrypted_string = $this->decrypt(urldecode($string));
		$query = parse_url('http://test.url?'.$decrypted_string, PHP_URL_QUERY);
		parse_str($query, $request_params);

		header("Content-type: text/xml; charset=utf-8");
		$response = '<?xml version="1.0" encoding="utf-8"?>';
		$response .= '<RequestResponse><error>1007</error></RequestResponse>';

		$client_details = $this->_getClientDetails('username', $request_params['username']);
		
		if ($client_details) {
			//GameRound::create($json_data['roundid'], $player_details->token_id);

			// Check if the game is available for the client
			$subscription = new GameSubscription();
			$client_game_subscription = $subscription->check($client_details->client_id, 35, $request_params['gamecode']);

			if(!$client_game_subscription) {
				header("Content-type: text/xml; charset=utf-8");
				$response = '<?xml version="1.0" encoding="utf-8"?>';
				$response .= '<RequestResponse><error>135</error></RequestResponse>';
			}
			else
			{
				
				$game_details = Game::find($request_params["gamecode"], config("providerlinks.simpleplay.PROVIDER_ID"));
	
				$json_data['income'] = $request_params['amount'];
				$json_data['amount'] = $request_params['amount'];
				$json_data['roundid'] = 'N/A';
				$json_data['transid'] = $request_params['txnid'];

				$game_transaction_id = GameTransaction::update('credit', $json_data, $game_details, $client_details, $client_details);
				
				$game_trans_ext_id = ProviderHelper::createGameTransExtV2($game_transaction_id, $request_params['txnid'], 'N/A', $request_params['amount'], 2);

				// change $json_data['roundid'] to $game_transaction_id
       			$client_response = ClientRequestHelper::fundTransfer($client_details, $request_params['amount'], $game_details->game_code, $game_details->game_name, $game_trans_ext_id, $game_transaction_id, 'credit');


				if(isset($client_response->fundtransferresponse->status->code) 
			&& $client_response->fundtransferresponse->status->code == "402") {
					header("Content-type: text/xml; charset=utf-8");
					$response = '<?xml version="1.0" encoding="utf-8"?>';
				 	$response .= '<RequestResponse><error>1004</error></RequestResponse>';
				}
				else
				{
					if(isset($client_response->fundtransferresponse->status->code) 
				&& $client_response->fundtransferresponse->status->code == "200") {

						header("Content-type: text/xml; charset=utf-8");
				 		$response = '<?xml version="1.0" encoding="utf-8"?>';
				 		$response .= '<RequestResponse><username>'.$request_params['username'].'</username><currency>USD</currency><amount>'.$client_response->fundtransferresponse->balance.'</amount><error>0</error></RequestResponse>';
					}
				}
				
			}
		}
		
		Helper::saveLog('simpleplay_credit', 35, json_encode($request_params), $response);
		echo $response;

	}

	public function lostTransaction(Request $request) 
	{
		$string = file_get_contents("php://input");
		$decrypted_string = $this->decrypt(urldecode($string));
		$query = parse_url('http://test.url?'.$decrypted_string, PHP_URL_QUERY);
		parse_str($query, $request_params);

		$client_code = RouteParam::get($request, 'brand_code');

		header("Content-type: text/xml; charset=utf-8");
		$response = '<?xml version="1.0" encoding="utf-8"?>';
		$response .= '<RequestResponse><error>1007</error></RequestResponse>';

		$client_details = $this->_getClientDetails('username', $request_params['username']);

		if ($client_details) {
			//GameRound::create($json_data['roundid'], $player_details->token_id);

			// Check if the game is available for the client
			$subscription = new GameSubscription();
			$client_game_subscription = $subscription->check($client_details->client_id, 35, $request_params['gamecode']);

			if(!$client_game_subscription) {
				header("Content-type: text/xml; charset=utf-8");
				$response = '<?xml version="1.0" encoding="utf-8"?>';
				 $response .= '<RequestResponse><error>135</error></RequestResponse>';
			}
			else
			{
				$client_response = Providerhelper::playerDetailsCall($client_details->player_token);

				if(isset($client_response->playerdetailsresponse->status->code) 
			&& $client_response->playerdetailsresponse->status->code == "402") {
					$response = '<?xml version="1.0" encoding="utf-8"?>';
				 		$response .= '<RequestResponse><error>1004</error></RequestResponse>';
				}
				else
				{
					if(isset($client_response->playerdetailsresponse->status->code) 
				&& $client_response->playerdetailsresponse->status->code == "200") {
						header("Content-type: text/xml; charset=utf-8");
				 		$response = '<?xml version="1.0" encoding="utf-8"?>';
				 		$response .= '<RequestResponse><username>'.$request_params['username'].'</username><currency>USD</currency><amount>'.$client_response->playerdetailsresponse->balance.'</amount><error>0</error></RequestResponse>';
					}
				}
				
			}
		}
		
		Helper::saveLog('simpleplay_lost', 35, json_encode($request_params), $response);
		echo $response;
	}

	public function rollBackTransaction(Request $request) 
	{
		$string = file_get_contents("php://input");
		$decrypted_string = $this->decrypt(urldecode($string));
		$query = parse_url('http://test.url?'.$decrypted_string, PHP_URL_QUERY);
		parse_str($query, $request_params);

		$client_code = RouteParam::get($request, 'brand_code');

		header("Content-type: text/xml; charset=utf-8");
 		$response = '<?xml version="1.0" encoding="utf-8"?>';
 		$response .= '<RequestResponse><error>1007</error></RequestResponse>';

		$client_details = $this->_getClientDetails('username', $request_params['username']);


		if ($client_details) {
			// Check if the transaction exist
			$game_transaction = GameTransaction::find($request_params['txn_reverse_id']);

			// If transaction is not found
			if(!$game_transaction) {
				header("Content-type: text/xml; charset=utf-8");
		 		$response = '<?xml version="1.0" encoding="utf-8"?>';
		 		$response .= '<RequestResponse><error>1007</error></RequestResponse>';
			}
			else
			{
				// If transaction is found, send request to the client
				$json_data['roundid'] = 'N/A';
				$json_data['transid'] = $request_params['txnid'];
				$json_data['income'] = 0;
				
				$game_details = Game::find($request_params["gamecode"], config("providerlinks.simpleplay.PROVIDER_ID"));
				
				$game_transaction_id = GameTransaction::save('rollback', $json_data, $game_transaction, $client_details, $client_details);

				$game_trans_ext_id = ProviderHelper::createGameTransExtV2($game_transaction_id, $request_params['txnid'], 'N/A', $request_params['amount'], 3);

				// change $json_data['roundid'] to $game_transaction_id
       			$client_response = ClientRequestHelper::fundTransfer($client_details, $request_params['amount'], $game_details->game_code, $game_details->game_name, $game_trans_ext_id, $game_transaction_id, 'credit', true);

				// If client returned a success response
				if($client_response->fundtransferresponse->status->code == "200") {
					header("Content-type: text/xml; charset=utf-8");
				 		$response = '<?xml version="1.0" encoding="utf-8"?>';
				 		$response .= '<RequestResponse><username>'.$request_params['username'].'</username><currency>USD</currency><amount>'.$client_response->fundtransferresponse->balance.'</amount><error>0</error></RequestResponse>';
				}
			}
			
		}
		

		Helper::saveLog('simpleplay_rollback', 35, json_encode($request_params), $response);
		echo $response;

	}

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

				if ($type == 'username') {
					$query->where([
				 		["p.username", "=", $value],
				 		["pst.status_id", "=", 1]
				 	]);
				}

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
