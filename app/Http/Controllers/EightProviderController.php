<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\CallParameters;
use App\Helpers\ProviderHelper;
use App\Helpers\ClientRequestHelper;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;

use DB;


/**
 * 8Provider (API Version 2 POST DATA METHODS)
 *
 * @version 1.0
 * @method index
 * @method gameBet
 * @method gameWin
 * @method gameRefund
 * Available Currencies
 * AUD,BRL,BTC,CAD,CNY,COP,CZK,EUR,GBP,GHS,HKD,HRK,IDR,INR,IRR,JPY,KRW,KZT,MDL,MMK,MYR,NOK,PLN,RUB,SEK,THB,TRY,TWD,UAH,USD,VND,XOF,ZAR
 */
class EightProviderController extends Controller
{

	// public $api_url = 'http://api.8provider.com';
	// public $secret_key = 'c270d53d4d83d69358056dbca870c0ce';
	// public $project_id = '1042';
	public $provider_db_id = 19;

	public $api_url, $secret_key, $project_id = '';

	public function __construct(){
    	$this->api_url = config('providerlinks.evoplay.api_url');
    	$this->project_id = config('providerlinks.evoplay.project_id');
    	$this->secret_key = config('providerlinks.evoplay.secretkey');
    }

    /**
     * @return string
     *
     */
	public function getSignature($system_id, $callback_version, array $args, $system_key){
	    $md5 = array();
	    $md5[] = $system_id;
	    $md5[] = $callback_version;

	    $signature = $args['signature']; // store the signature
	    unset($args['signature']); // remove signature from the array

	    $args = array_filter($args, function($val){ return !($val === null || (is_array($val) && !$val));});
	    foreach ($args as $required_arg) {
	        $arg = $required_arg;
	        if (is_array($arg)) {
	            $md5[] = implode(':', array_filter($arg, function($val){ return !($val === null || (is_array($val) && !$val));}));
	        } else {
	            $md5[] = $arg;
	        }
	    };

	    $md5[] = $system_key;
	    $md5_str = implode('*', $md5);
	    $md5 = md5($md5_str);

	    if($md5 == $signature){  // Generate Hash And Check it also!
	    	return 'true';
	    }else{
	    	return 'false';
	    }
	}

	/**
	 * @author's note single method that will handle 4 API Calls
	 * @param name = bet, win, refund,
	 * 
	 */
	public function index(Request $request){
		// DB::enableQueryLog();
		$this->saveLog('8P index '.$request->name, $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');

		$signature_checker = $this->getSignature($this->project_id, 2, $request->all(), $this->secret_key);
		if($signature_checker == 'false'):
			$msg = array(
						"status" => 'error',
						"error" => ["scope" => "user","no_refund" => 1,"message" => "Signature is invalid!"]
					);
			$this->saveLog('8P Signature Failed '.$request->name, $this->provider_db_id, json_encode($request->all()), $msg);
			return $msg;
		endif;

		$data = $request->all();
		if($request->name == 'init'){

			$player_details = ProviderHelper::playerDetailsCall($data['token']);
			$client_details = ProviderHelper::getClientDetails('token', $data['token']);
			$response = array(
				'status' => 'ok',
				'data' => [
					'balance' => (string)$player_details->playerdetailsresponse->balance,
					'currency' => $client_details->default_currency,
				],
			);
			$this->saveLog('8P GAME INIT', $this->provider_db_id, json_encode($data), $response);
			return $response;

		}elseif($request->name == 'bet'){

			$game_ext = $this->checkTransactionExist($data['callback_id'], 1);
			if($game_ext == 'false'): // NO BET
				$string_to_obj = json_decode($data['data']['details']);
			    $game_id = $string_to_obj->game->game_id;
			    $game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $game_id);	
			    $player_details = ProviderHelper::playerDetailsCall($data['token']);
			   	if($player_details->playerdetailsresponse->balance < $data['data']['amount']):
			   		$msg = array(
						"status" => 'error',
						"error" => ["scope" => "user","no_refund" => 1,"message" => "Not enough money"]
					);
					$this->saveLog('8Provider gameBet PC', $this->provider_db_id, json_encode($player_details), $msg);
					return $msg;
			   	endif;

			    $client_details = ProviderHelper::getClientDetails('token', $data['token']);
				try {
					$this->saveLog('8Provider gameBet 1', $this->provider_db_id, json_encode($data), 1);
					$payout_reason = 'Bet';
			 		$win_or_lost = 0; // 0 Lost, 1 win, 3 draw, 4 refund, 5 processing
			 		$method = 1; // 1 bet, 2 win
			 	    $token_id = $client_details->token_id;
			 	    $bet_payout = 0; // Bet always 0 payout!
			 	    $income = $data['data']['amount'];
			 	    $provider_trans_id = $data['callback_id'];
			 	    $round_id = $data['data']['round_id'];

					$game_trans = ProviderHelper::createGameTransaction($token_id, $game_details->game_id, $data['data']['amount'],  $data['data']['amount'], $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);

					$game_transextension = ProviderHelper::createGameTransExtV2($game_trans,$provider_trans_id, $round_id, $data['data']['amount'], 1);

					try {
						$client_response = ClientRequestHelper::fundTransfer($client_details,ProviderHelper::amountToFloat($data['data']['amount']),$game_details->game_code,$game_details->game_name,$game_transextension,$game_trans,'debit');
			       	     $this->saveLog('8Provider gameBet CRID '.$round_id, $this->provider_db_id, json_encode($data), $client_response);
					} catch (\Exception $e) {
						$msg = array("status" => 'error',"message" => $e->getMessage());
						ProviderHelper::updateGameTransactionStatus($game_trans, 99, 99);
						ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $msg, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
						$this->saveLog('8Provider gameBet - FATAL ERROR', $this->provider_db_id, json_encode($data), Helper::datesent());
						return $msg;
					}


					if(isset($client_response->fundtransferresponse->status->code) 
					             && $client_response->fundtransferresponse->status->code == "200"){
						$this->saveLog('8Provider gameBet 2', $this->provider_db_id, json_encode($data), 2);
						$response = array(
							'status' => 'ok',
							'data' => [
								'balance' => (string)$client_response->fundtransferresponse->balance,
								'currency' => $client_details->default_currency,
							],
					 	);
				 	    ProviderHelper::updatecreateGameTransExt($game_transextension, $data, $response, $client_response->requestoclient, $client_response, $response);
					}elseif(isset($client_response->fundtransferresponse->status->code) 
					            && $client_response->fundtransferresponse->status->code == "402"){
						if(ProviderHelper::checkFundStatus($client_response->fundtransferresponse->status->status)):
							   ProviderHelper::updateGameTransactionStatus($game_trans, 2, 6);
						else:
						   ProviderHelper::updateGameTransactionStatus($game_trans, 2, 99);
						endif;
						$response = array(
							"status" => 'error',
							"error" => ["scope" => "user","no_refund" => 1,"message" => "Not enough money"]
						);

					}

			   		// $trans_ext = $this->create8PTransactionExt($game_trans, $data, $requesttosend, $client_response, $client_response,$data, 1, $data['data']['amount'], $provider_trans_id,$round_id);
			   		$this->saveLog('8Provider gameBet', $this->provider_db_id, json_encode($data), $response);
				  	return $response;
				}catch(\Exception $e){
					$msg = array(
						"status" => 'error',
						"message" => $e->getMessage(),
					);
					$this->saveLog('8P gameBet - FATAL ERROR', $this->provider_db_id, json_encode($data), $e->getMessage());
					return $msg;
				}
		    else:
		    	// NOTE IF CALLBACK WAS ALREADY PROCESS PROVIDER DONT NEED A ERROR RESPONSE! LEAVE IT AS IT IS!
		    	$this->saveLog('8Provider gameBet 3', $this->provider_db_id, json_encode($data), 3);
		    	$player_details = ProviderHelper::playerDetailsCall($data['token']);
				$client_details = ProviderHelper::getClientDetails('token', $data['token']);
				// dd($client_details);
				$response = array(
					'status' => 'ok',
					'data' => [
						'balance' => (string)$player_details->playerdetailsresponse->balance,
						'currency' => $client_details->default_currency,
					],
			 	 );
				$this->saveLog('8Provider'.$data['data']['round_id'], $this->provider_db_id, json_encode($data), $response);
				return $response;
		    endif;

		}elseif($request->name == 'win'){

			$string_to_obj = json_decode($data['data']['details']);
		$game_id = $string_to_obj->game->game_id;
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $game_id);
		$player_details = ProviderHelper::playerDetailsCall($data['token']);
		$client_details = ProviderHelper::getClientDetails('token', $data['token']);

		$game_ext = $this->checkTransactionExist($data['callback_id'], 2); // Find if this callback in game extension
		// $game_ext = ProviderHelper::findGameExt($data['callback_id'], 2, 'transaction_id'); // Find if this callback in game extension
		$this->saveLog('8P game_ext', $this->provider_db_id, json_encode($data), 'game_ext');
		if($game_ext == 'false'):
			$existing_bet = $this->findGameTransaction($data['data']['round_id'], 'round_id', 1); // Find if win has bet record
			$this->saveLog('8P existing_bet', $this->provider_db_id, json_encode($data), 'existing_bet');
			// $client_details = ProviderHelper::getClientDetails('token', $data['token']);
			if($existing_bet != 'false'): // Bet is existing, else the bet is already updated to win
					 // No Bet was found check if this is a free spin and proccess it!
					$this->saveLog('8P existing_bet = false', $this->provider_db_id, json_encode($data), 'existing_bet = false');
				    if($string_to_obj->game->action == 'freespin'):
				    	$this->saveLog('8Provider freespin 1', $this->provider_db_id, json_encode($data), 1);
				  	    // $client_details = ProviderHelper::getClientDetails('token', $data['token']);
							try {
								$this->saveLog('8P FREESPIN', $this->provider_db_id, json_encode($data), 'FREESPIN');
								$payout_reason = 'Free Spin';
						 		$win_or_lost = 1; // 0 Lost, 1 win, 3 draw, 4 refund, 5 processing
						 		$method = 2; // 1 bet, 2 win
						 	    $token_id = $client_details->token_id;
						 	    $provider_trans_id = $data['callback_id'];
						 	    $round_id = $data['data']['round_id'];
						
						 	    $bet_payout = 0; // Bet always 0 payout!
						 	    $income = '-'.$data['data']['amount']; // NEgative

								$game_ext = ProviderHelper::findGameExt($round_id, 1, 'round_id');
								if($game_ext != 'false'){
									$game_trans = $game_ext->game_trans_id;
									$existing_bet = $this->findGameTransID($game_ext->game_trans_id);
									$payout = $existing_bet->pay_amount+$data['data']['amount'];
									$this->updateBetTransaction($game_ext->game_trans_id, $payout, $existing_bet->bet_amount-$payout, $existing_bet->win, $existing_bet->entry_id);
								}else{

									$game_ext = ProviderHelper::findGameExt($round_id, 2, 'round_id');
									if($game_ext != 'false'){
										$game_trans = $game_ext->game_trans_id;
										$existing_bet = $this->findGameTransID($game_ext->game_trans_id);
										$payout = $existing_bet->pay_amount+$data['data']['amount'];
										$this->updateBetTransaction($game_ext->game_trans_id, $payout, $existing_bet->bet_amount-$payout, $existing_bet->win, $existing_bet->entry_id);
									}else{
										$game_trans = ProviderHelper::createGameTransaction($token_id, $game_details->game_id, 0, $data['data']['amount'], $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
									}
									
								}
						 	    
								$game_transextension = ProviderHelper::createGameTransExtV2($game_trans,$provider_trans_id, $round_id, $data['data']['amount'], $method); // method 5 freespin?

								try {
									$client_response = ClientRequestHelper::fundTransfer($client_details,ProviderHelper::amountToFloat($data['data']['amount']),$game_details->game_code,$game_details->game_name,$game_transextension,$game_trans,'credit');
								    $this->saveLog('8Provider Win Freespin CRID = '.$round_id, $this->provider_db_id, json_encode($data), $client_response);
								} catch (\Exception $e) {
									$msg = array("status" => 'error',"message" => $e->getMessage());
									ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $msg, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
									$this->saveLog('8Provider Freespin - FATAL ERROR', $this->provider_db_id, json_encode($data), Helper::datesent());
									return $msg;
								}

								if(isset($client_response->fundtransferresponse->status->code) 
								    && $client_response->fundtransferresponse->status->code == "200"){
									$response = array(
										'status' => 'ok',
										'data' => [
											'balance' => (string)$client_response->fundtransferresponse->balance,
											'currency' => $client_details->default_currency,
										],
								 	 );

									ProviderHelper::updatecreateGameTransExt($game_transextension, $data, $response, $client_response->requestoclient, $client_response, $response);
									$this->saveLog('8P FREESPIN', $this->provider_db_id, json_encode($data), $response);
							  		return $response;
								}


							}catch(\Exception $e){
								$msg = array(
									"status" => 'error',
									"message" => $e->getMessage(),
								);
								$this->saveLog('8P ERROR FREESPIN - FATAL ERROR', $this->provider_db_id, json_encode($data), $e->getMessage());
								return $msg;
							}
				    else:
							try {
								$this->saveLog('8P win normal 1', $this->provider_db_id, json_encode($data), 'normal');
								$amount = $data['data']['amount'];
								$round_id = $data['data']['round_id'];

								// WIN IS ALWAYS WIN OLD
								// if($existing_bet->bet_amount > $amount):
								if($amount == 0):
									$win = 0; // lost
									$entry_id = 1; //lost
									$income = $existing_bet->bet_amount - $amount;
								else:
									$win = 1; //win
									$entry_id = 2; //win
									$income = $existing_bet->bet_amount - $amount;
								endif;
								// END OLD
								
								// REVISION 08/21/20
								// $win = 1; //win
								// $entry_id = 2; //win
								// $income = $existing_bet->bet_amount - $amount;

								$game_transextension = ProviderHelper::createGameTransExtV2($existing_bet->game_trans_id,$data['callback_id'], $round_id, $data['data']['amount'], 2);

								try {
									$this->saveLog('Win Call Request', $this->provider_db_id, json_encode($data), 1);
									$client_response = ClientRequestHelper::fundTransfer($client_details,ProviderHelper::amountToFloat($data['data']['amount']),$game_details->game_code,$game_details->game_name,$game_transextension,$existing_bet->game_trans_id,'credit');
									$this->saveLog('Win Response Receive', $this->provider_db_id, json_encode($data), 1);
									$this->saveLog('8Provider gameWin CRID = '.$round_id, $this->provider_db_id, json_encode($data), $client_response);
								} catch (\Exception $e) {
									$msg = array("status" => 'error',"message" => $e->getMessage());
									ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $msg, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
									$this->saveLog('8Provider gameWin - FATAL ERROR', $this->provider_db_id, json_encode($data), Helper::datesent());
									return $msg;
								}

								if(isset($client_response->fundtransferresponse->status->code) 
									&& $client_response->fundtransferresponse->status->code == "200"){
									$this->updateBetTransaction($existing_bet->game_trans_id, $amount, $income, $win, $entry_id);
									$this->saveLog('Bet Updated', $this->provider_db_id, json_encode($data), 1);
									$response = array(
										'status' => 'ok',
										'data' => [
											'balance' => (string)$client_response->fundtransferresponse->balance,
											'currency' => $client_details->default_currency,
										],
									);

									ProviderHelper::updatecreateGameTransExt($game_transextension, $data, $response, $client_response->requestoclient, $client_response, $response);
								}
								
								$this->saveLog('8Provider Win', $this->provider_db_id, json_encode($data), $response);
								return $response;

							}catch(\Exception $e){
								$msg = array(
									"status" => 'error',
									"message" => $e->getMessage(),
								);
								$this->saveLog('8P ERROR WIN', $this->provider_db_id, json_encode($data), $e->getMessage());
								return $msg;
							}
				    endif;	
			else: 
				$this->saveLog('8Provider win 4', $this->provider_db_id, json_encode($data), 1);
				$response = array(
					'status' => 'ok',
					'data' => [
						'balance' => (string)$player_details->playerdetailsresponse->balance,
						'currency' => $client_details->default_currency,
					],
			 	);
				$this->saveLog('8Provider'.$data['data']['round_id'], $this->provider_db_id, json_encode($data), $response);
				return $response;  
			endif;
		else:
			    // NOTE IF CALLBACK WAS ALREADY PROCESS PROVIDER DONT NEED A ERROR RESPONSE! LEAVE IT AS IT IS!
				$response = array(
					'status' => 'ok',
					'data' => [
						'balance' => (string)$player_details->playerdetailsresponse->balance,
						'currency' => $client_details->default_currency,
					],
			 	);
			 	$this->saveLog('8Provider win 5', $this->provider_db_id, json_encode($data), 1);
				$this->saveLog('8Provider'.$data['data']['round_id'], $this->provider_db_id, json_encode($data), $response);
				return $response;
		endif;

		}elseif($request->name == 'refund'){

			$string_to_obj = json_decode($data['data']['details']);
			$game_id = $string_to_obj->game->game_id;
			$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $game_id);
			$game_refund = ProviderHelper::findGameExt($data['callback_id'], 4, 'transaction_id'); // Find if this callback in game extension	
			if($game_refund == 'false'): // NO REFUND WAS FOUND PROCESS IT!

			$player_details = ProviderHelper::playerDetailsCall($data['token']);
			if($player_details == 'false'){
				$msg = array("status" => 'error',"message" => $e->getMessage());
				$this->saveLog('8Provider gameRefund - FATAL ERROR', $this->provider_db_id, json_encode($data), Helper::datesent());
				return $msg;
			}
			$client_details = ProviderHelper::getClientDetails('token', $data['token']);
			$game_transaction_ext = ProviderHelper::findGameExt($data['data']['refund_round_id'], 1, 'round_id'); // Find GameEXT
			if($game_transaction_ext == 'false'):
				// $player_details = ProviderHelper::playerDetailsCall($data['token']);
				// $client_details = ProviderHelper::getClientDetails('token', $data['token']);
				$response = array(
					'status' => 'ok',
					'data' => [
						'balance' => (string)$player_details->playerdetailsresponse->balance,
						'currency' => $client_details->default_currency,
					],
				);
				$this->saveLog('8Provider'.$data['data']['refund_round_id'], $this->provider_db_id, json_encode($data), $response);
				return $response;
			endif;

			$game_transaction_ext_refund = ProviderHelper::findGameExt($data['data']['refund_round_id'], 4, 'round_id'); // Find GameEXT
			if($game_transaction_ext_refund != 'false'):
				// $player_details = ProviderHelper::playerDetailsCall($data['token']);
				// $client_details = ProviderHelper::getClientDetails('token', $data['token']);
				$response = array(
					'status' => 'ok',
					'data' => [
						'balance' => (string)$player_details->playerdetailsresponse->balance,
						'currency' => $client_details->default_currency,
					],
				);
				$this->saveLog('8Provider'.$data['data']['refund_round_id'], $this->provider_db_id, json_encode($data), $response);
				return $response;
			endif;


			$existing_transaction = $this->findGameTransID($game_transaction_ext->game_trans_id);
			if($existing_transaction != 'false'): // IF BET WAS FOUND PROCESS IT!
				$transaction_type = $game_transaction_ext->game_transaction_type == 1 ? 'credit' : 'debit'; // 1 Bet
				// $client_details = ProviderHelper::getClientDetails('token', $data['token']);
				if($transaction_type == 'debit'):
					// $player_details = ProviderHelper::playerDetailsCall($data['token']);
					if($player_details->playerdetailsresponse->balance < $data['data']['amount']):
						$msg = array(
							"status" => 'error',
							"error" => ["scope" => "user","no_refund" => 1,"message" => "Not enough money"]
						);
						return $msg;
					endif;
				endif;

				try {

					$this->updateBetTransaction($existing_transaction->game_trans_id, $existing_transaction->pay_amount, $existing_transaction->income, 4, $existing_transaction->entry_id); // UPDATE BET TO REFUND!

					$game_transextension = ProviderHelper::createGameTransExtV2($existing_transaction->game_trans_id,$data['callback_id'], $data['data']['refund_round_id'], $data['data']['amount'], 4);

					try {
						$client_response = ClientRequestHelper::fundTransfer($client_details,ProviderHelper::amountToFloat($data['data']['amount']),$game_details->game_code,$game_details->game_name,$game_transextension,$existing_transaction->game_trans_id, $transaction_type);
						$this->saveLog('8Provider Refund CRID '.$data['data']['refund_round_id'], $this->provider_db_id, json_encode($data), $client_response);
					} catch (\Exception $e) {
						$msg = array("status" => 'error',"message" => $e->getMessage());
						ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $msg, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
						$this->saveLog('8Provider gameRefund - FATAL ERROR', $this->provider_db_id, json_encode($data), Helper::datesent());
						return $msg;
					}

					if(isset($client_response->fundtransferresponse->status->code) 
								&& $client_response->fundtransferresponse->status->code == "200"){
						$response = array(
							'status' => 'ok',
							'data' => [
								'balance' => (string)$client_response->fundtransferresponse->balance,
								'currency' => $client_details->default_currency,
							],
						);

						ProviderHelper::updatecreateGameTransExt($game_transextension, $data, $response, $client_response->requestoclient, $client_response, $response);
						$this->saveLog('8P REFUND', $this->provider_db_id, json_encode($data), $response);
						return $response;
					}
										

				}catch(\Exception $e){
					$msg = array(
						"status" => 'error',
						"message" => $e->getMessage(),
					);
					$this->saveLog('8P ERROR REFUND', $this->provider_db_id, json_encode($data), $e->getMessage());
					return $msg;
				}
			else:
				// NO BET WAS FOUND DO NOTHING
				// $player_details = ProviderHelper::playerDetailsCall($data['token']);
				// $client_details = ProviderHelper::getClientDetails('token', $data['token']);
				$response = array(
					'status' => 'ok',
					'data' => [
						'balance' => (string)$player_details->playerdetailsresponse->balance,
						'currency' => $client_details->default_currency,
					],
				);
				$this->saveLog('8Provider'.$data['data']['refund_round_id'], $this->provider_db_id, json_encode($data), $response);
				return $response;
			endif;
			else:
				// NOTE IF CALLBACK WAS ALREADY PROCESS/DUPLICATE PROVIDER DONT NEED A ERROR RESPONSE! LEAVE IT AS IT IS!
				// $player_details = ProviderHelper::playerDetailsCall($data['token']);
				// $client_details = ProviderHelper::getClientDetails('token', $data['token']);
				$response = array(
					'status' => 'ok',
					'data' => [
						'balance' => (string)$player_details->playerdetailsresponse->balance,
						'currency' => $client_details->default_currency,
					],
				);
				$this->saveLog('8Provider'.$data['data']['refund_round_id'], $this->provider_db_id, json_encode($data), $response);
				return $response;
			endif;
		}
	}

	public function checkTransactionExist($identifier, $transaction_type){
		$query = DB::select('select `game_transaction_type` from game_transaction_ext where `provider_trans_id`  = "'.$identifier.'" AND `game_transaction_type` = "'.$transaction_type.'" LIMIT 1');
    	$data = count($query);
		return $data > 0 ? $query[0] : 'false';
	}

	public  function findGameTransaction($identifier, $type, $entry_type='') {

    	if ($type == 'transaction_id') {
		 	$where = 'where gt.provider_trans_id = "'.$identifier.'" AND gt.entry_id = '.$entry_type.'';
		}
		if ($type == 'game_transaction') {
		 	$where = 'where gt.game_trans_id = "'.$identifier.'"';
		}
		if ($type == 'round_id') {
			$where = 'where gt.round_id = "'.$identifier.'" AND gt.entry_id = '.$entry_type.'';
		}
	 	
	 	$filter = 'LIMIT 1';
    	$query = DB::select('select *, (select transaction_detail from game_transaction_ext where game_trans_id = gt.game_trans_id order by game_trans_id limit 1) as transaction_detail from game_transactions gt '.$where.' '.$filter.'');
    	$client_details = count($query);
		return $client_details > 0 ? $query[0] : 'false';
    }

	public function findGameTransID($game_trans_id){
		$query = DB::select('select `game_trans_id`,`token_id`, `provider_trans_id`, `round_id`, `bet_amount`, `win`, `pay_amount`, `income`, `entry_id` from game_transactions where `game_trans_id`  = '.$game_trans_id.' LIMIT 1');
    	$data = count($query);
		return $data > 0 ? $query[0] : 'false';
	}


	/**
	 * Create Game Extension Logs bet/Win/Refund
	 * @param [int] $[gametransaction_id] [<ID of the game transaction>]
	 * @param [json array] $[provider_request] [<Incoming Call>]
	 * @param [json array] $[mw_request] [<Outgoing Call>]
	 * @param [json array] $[mw_response] [<Incoming Response Call>]
	 * @param [json array] $[client_response] [<Incoming Response Call>]
	 * 
	 */
	public  function create8PTransactionExt($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response, $transaction_detail,$game_transaction_type, $amount=null, $provider_trans_id=null, $round_id=null){
		$gametransactionext = array(
			"game_trans_id" => $gametransaction_id,
			"provider_trans_id" => $provider_trans_id,
			"round_id" => $round_id,
			"amount" => $amount,
			"game_transaction_type"=>$game_transaction_type,
			"provider_request" => json_encode($provider_request),
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
			"transaction_detail" =>json_encode($transaction_detail),
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gamestransaction_ext_ID;
	}

	public  function saveLog($method, $provider_id = 0, $request_data, $response_data) {
		$data = [
				"method_name" => $method,
				"provider_id" => $provider_id,
				"request_data" => json_encode(json_decode($request_data)),
				"response_data" => json_encode($response_data)
			];
		return DB::table('seamless_request_logs')->insertGetId($data);
	}


	/**
	 * Find bet and update to win 
	 * @param [int] $[round_id] [<ID of the game transaction>]
	 * @param [int] $[pay_amount] [<amount to change>]
	 * @param [int] $[income] [<bet - payout>]
	 * @param [int] $[win] [<0 Lost, 1 win, 3 draw, 4 refund, 5 processing>]
	 * @param [int] $[entry_id] [<1 bet, 2 win>]
	 * 
	 */
	public  function updateBetTransaction($round_id, $pay_amount, $income, $win, $entry_id) {
   	    $update = DB::table('game_transactions')
                // ->where('round_id', $round_id)
   	    ->where('game_trans_id', $round_id)
                ->update(['pay_amount' => $pay_amount, 
	        		  'income' => $income, 
	        		  'win' => $win, 
	        		  'entry_id' => $entry_id,
	        		  'transaction_reason' => $this->updateReason($win),
	    		]);
		return ($update ? true : false);
	}


	/**
	 * Find bet and update to win 
	 * @param [int] $[win] [< Win TYPE>][<0 Lost, 1 win, 3 draw, 4 refund, 5 processing>]
	 * 
	 */
	public  function updateReason($win) {
		$win_type = [
		 "1" => 'Transaction updated to win',
		 "2" => 'Transaction updated to bet',
		 "3" => 'Transaction updated to Draw',
		 "4" => 'Transaction updated to Refund',
		 "5" => 'Transaction updated to Processing',
		];
		if(array_key_exists($win, $win_type)){
    		return $win_type[$win];
    	}else{
    		return 'Transaction Was Updated!';
    	}
	}

	 public function getClientDetails($type = "", $value = "", $gg=1, $providerfilter='all') {
	    if ($type == 'token') {
		 	$where = 'where pst.player_token = "'.$value.'"';
		}
		if($providerfilter=='fachai'){
		    if ($type == 'player_id') {
				$where = 'where '.$type.' = "'.$value.'" AND pst.status_id = 1 ORDER BY pst.token_id desc';
			}
		}else{
	        if ($type == 'player_id') {
			   $where = 'where '.$type.' = "'.$value.'"';
			}
		}
		if ($type == 'username') {
		 	$where = 'where p.username = "'.$value.'"';
		}
		if ($type == 'token_id') {
			$where = 'where pst.token_id = "'.$value.'"';
		}
		if($providerfilter=='fachai'){
		 	$filter = 'LIMIT 1';
		}else{
		    // $result= $query->latest('token_id')->first();
		    $filter = 'order by token_id desc LIMIT 1';
		}

		$query = DB::select('select `p`.`client_id`, `p`.`player_id`, `p`.`email`, `p`.`client_player_id`,`p`.`language`, `p`.`currency`, `p`.`test_player`, `p`.`username`,`p`.`created_at`,`pst`.`token_id`,`pst`.`player_token`,`c`.`client_url`,`c`.`default_currency`,`pst`.`status_id`,`p`.`display_name`,`op`.`client_api_key`,`op`.`client_code`,`op`.`client_access_token`,`ce`.`player_details_url`,`ce`.`fund_transfer_url`,`p`.`created_at` from player_session_tokens pst inner join players as p using(player_id) inner join clients as c using (client_id) inner join client_endpoints as ce using (client_id) inner join operator as op using (operator_id) '.$where.' '.$filter.'');

		 $client_details = count($query);
		 return $client_details > 0 ? $query[0] : null;
	}

	
}
