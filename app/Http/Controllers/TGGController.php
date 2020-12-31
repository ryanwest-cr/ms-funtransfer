<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\TGGHelper;
use App\Helpers\ProviderHelper;
use GuzzleHttp\Client;
use App\Helpers\ClientRequestHelper;
use Carbon\Carbon;
use DB;

class TGGController extends Controller
{
	public function __construct(){
    	$this->project_id = config('providerlinks.tgg.project_id');
    	$this->api_key = config('providerlinks.tgg.api_key');
    	$this->api_url = config('providerlinks.tgg.api_url');
    	$this->startTime = microtime(true);
    	$this->prefix = "TGTGG";
    	$this->middleware_api = config('providerlinks.oauth_mw_api.mwurl'); 
	}
	
	// public $provider_db_id = 29; // 29 on test ,, 27 prod
	public $provider_db_id = 29; 
	
	public function index(Request $request){
		TGGHelper::saveLog('TGG index '.$request->name, $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');

		// $signature_checker = $this->getSignature($this->project_id, 2, $request->all(), $this->api_key,'check_signature');
		// // return $signature_checker;
		// if($signature_checker == 'false'):
		// 	$msg = array(
		// 				"status" => 'error',
		// 				"error" => ["scope" => "user","no_refund" => 1,"message" => "Signature is invalid!"]
		// 			);
		// 	Helper::saveLog('TGG Signature Failed '.$request->name, $this->provider_db_id, json_encode($request->all()), $msg);
		// 	return $msg;
		// endif;
	
		if($request->name == 'init'){

			$details = $this->gameInit($request->all());
			return json_encode($details);
		
		}

		if($request->name == 'bet'){
			
			$details = $this->gameBet($request->all());
			return json_encode($details);
		
		}

		if($request->name == 'win'){

			$details = $this->gameWin($request->all());
			return json_encode($details);

		}

		if($request->name == 'refund'){

			$details = $this->gameRefund($request->all());
			return json_encode($details);

			

		}

		
	}

	public function gameBet($request){
		
		$string_to_obj = json_decode($request['data']['details']);
	    $game_code = $string_to_obj->game->game_id;

	    // GET CLIENT DETIALS
		$client_details = ProviderHelper::getClientDetails('token', $request['token']);
		if ($client_details != null) {
			$game_ext = TGGHelper::checkTransactionExist($request['callback_id'], 1);
			if($game_ext == 'false'): // NO BET
				
				$bet_amount = $request['data']['amount'];
				$balance = $client_details->balance - $bet_amount;
				$body_details = [
					"token" => $client_details->player_token,
					"rollback" => false,
					"game_details" => [
						"game_code" => $game_code,
						"provider_id" => $this->provider_db_id
					],
					"game_transaction" => [
						"provider_trans_id" => $request['callback_id'],
						"round_id" => $request['data']['action_id'],
						"amount" => $bet_amount
					],
					"provider_request" => $request
				];
				
				try {
					$client = new Client();
			 		$guzzle_response = $client->post($this->middleware_api . '/tigergames/debit/bg-fundtransfer',
			 			[ 'body' => json_encode($body_details), 'timeout' => '0.01']
			 		);
			 		
				} catch (\Exception $e) {
					$response = array(
						'status' => 'ok',
						'data' => [
							'balance' => (string)$balance,
							'currency' => $client_details->default_currency,
						],
				  	);
				  	TGGHelper::saveLog('TGG success BET PROCESS ', $this->provider_db_id, json_encode($request), $response);
				  	return $response;
				}

			else:
				$response = array(
					'status' => 'ok',
					'data' => [
						'balance' => (string)$client_details->balance,
						'currency' => $client_details->default_currency,
					],
				);
				TGGHelper::saveLog('TGG bet found 1 ', $this->provider_db_id, json_encode($request), $response);
				return $response; 

			endif;
		} else {
			$response = array(
				"status" => 'error',
				"error" => ["scope" => "user","no_refund" => 1,"message" => "Player not found"]
			);
			TGGHelper::saveLog('Player not found ', $this->provider_db_id, json_encode($request), $response);
			return $response; 
		}
		
	}

	public  function gameWin($request){

		$string_to_obj = json_decode($request['data']['details']);
	    $game_id = $string_to_obj->game->game_id;
	    
		$client_details = ProviderHelper::getClientDetails('token', $request['token']);
		$game_details = TGGHelper::findGameDetails('game_code', $this->provider_db_id, $game_id);
		$game_ext = TGGHelper::checkTransactionExist($request['callback_id'], 2); 

		if($game_ext == 'false'):
			
			$reference_transaction_uuid = $request['data']['action_id'];
			
			if (array_key_exists('round_id', $request['data']) ) {
				$reference_transaction_uuid = $request['data']['round_id'];
			}
			
			$existing_bet =TGGHelper::findGameTransaction($reference_transaction_uuid, 'round_id', 1); 
			
			if($existing_bet != 'false'): 
				
				if(isset($string_to_obj->game->action) && $string_to_obj->game->action == 'freespin'):
					
					$game_transaction_type = 2; // 1 Bet, 2 Win
					$game_code = $game_details[0]->game_id;
					$token_id = $client_details->token_id;
					$bet_amount = 0;
					$pay_amount = abs($request['data']['amount']);
					$income = $bet_amount - $pay_amount;
					$method = $pay_amount == 0 ? 1 : 2;
					$win_or_lost =  $pay_amount == 0 ? 0 : 1;; // 0 lost,  5 processing
					$payout_reason = 'Freespin';
					$transaction_uuid = $request['callback_id'];
					// $reference_transaction_uuid = $request['data']['round_id'];

					//Create GameTransaction, GameExtension
					$game_trans_id  = ProviderHelper::createGameTransaction($token_id, $game_code, $bet_amount,  $pay_amount, $method, $win_or_lost,  TGGHelper::updateReason(1), $payout_reason, $income, $transaction_uuid, $reference_transaction_uuid);
					
					$game_trans_ext_id = TGGHelper::createGameTransExt($game_trans_id,$transaction_uuid, $reference_transaction_uuid, $pay_amount, $game_transaction_type, $request, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);

					
					$type = "credit";
					$rollback = false;
					$client_response = ClientRequestHelper::fundTransfer($client_details,$pay_amount,$game_id,$game_details[0]->game_name,$game_trans_ext_id,$game_trans_id,$type,$rollback);

					$response = array(
					'status' => 'ok',
					'data' => [
						'balance' => (string)$client_response->fundtransferresponse->balance,
						'currency' => $client_details->default_currency,
						],
					  );

					TGGHelper::updateGameTransactionExt($game_trans_ext_id,$client_response->requestoclient,$client_response->fundtransferresponse,$response);
					TGGHelper::saveLog('TGG FREE SPIN success', $this->provider_db_id, json_encode($request), $response); 
					return $response;
				else:
					// NORMAL BET
					$bet_amount = $request['data']['amount'];
					$balance = $client_details->balance - $bet_amount;
					$body_details = [
						"token" => $client_details->player_token,
						"rollback" => false,
						"game_details" => [
							"game_code" => $game_id,
							"provider_id" => $this->provider_db_id
						],
						"game_transaction" => [
							"provider_trans_id" => $request['callback_id'],
							"round_id" => $reference_transaction_uuid,
							"amount" => $bet_amount
						],
						"provider_request" => $request,
						"existing_bet" => [
							"game_trans_id" => $existing_bet->game_trans_id
						]
					];
					
					try {
						$client = new Client();
				 		$guzzle_response = $client->post($this->middleware_api . '/tigergames/credit/bg-fundtransfer',
				 			[ 'body' => json_encode($body_details), 'timeout' => '0.01']
				 		);
				 		
					} catch (\Exception $e) {
						$response = array(
							'status' => 'ok',
							'data' => [
								'balance' => (string)$balance,
								'currency' => $client_details->default_currency,
							],
					  	);
					  	TGGHelper::saveLog('TGG success WIN PROCESS ', $this->provider_db_id, json_encode($request), $response);
					  	return $response;
					}
					
				endif;
			else:
				
				$response = array(
				'status' => 'ok',
				'data' => [
					'balance' => (string)$client_details->balance,
					'currency' => $client_details->default_currency,
				],
				);
				TGGHelper::saveLog('TGG no bet found in win proccess', $this->provider_db_id, json_encode($request), $response);
				return $response;
			endif;
		
		else:	
		    // NOTE IF CALLBACK WAS ALREADY PROCESS PROVIDER DONT NEED A ERROR RESPONSE! LEAVE IT AS IT IS!
		    $player_details = TGGHelper::playerDetailsCall($client_details);
			$response = array(
				'status' => 'ok',
				'data' => [
					'balance' => (string)$player_details->playerdetailsresponse->balance,
					'currency' => $client_details->default_currency,
				],
			);
			TGGHelper::saveLog('TGG win duplicate', $this->provider_db_id, json_encode($request), $response);
			return $response;
			
		endif;
	}

	public  function gameRefund($data){

		$string_to_obj = json_decode($data['data']['details']);
		$game_id = $string_to_obj->game->game_id;
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $game_id);
		$client_details = TGGHelper::getClientDetails('token', $data['token']);
		$game_refund = TGGHelper::findGameExt($data['data']['refund_round_id'], 4, 'round_id'); // Find if this callback in game extension	
		if($game_refund == 'false'): // NO REFUND WAS FOUND PROCESS IT!
			// $existing_transaction = TGGHelper::findGameExt($data['data']['refund_round_id'], 1, 'round_id'); // Find GameEXT
			$existing_transaction = TGGHelper::findGameTransaction($data['data']['refund_callback_id'], 'transaction_id', 1);

			if($existing_transaction != 'false'): // IF BET WAS FOUND PROCESS IT!
				$transaction_type = $existing_transaction->entry_id == 1 ? 'credit' : 'debit'; // 1 Bet
				try {
					$rollback = "true";
					$client_response = ClientRequestHelper::fundTransfer($client_details,$data['data']['amount'],$game_id,$game_details->game_name,$existing_transaction->game_trans_ext_id,$existing_transaction->game_trans_id,$transaction_type,$rollback);
					$response = array(
					'status' => 'ok',
					'data' => [
						'balance' => (string)$client_response->fundtransferresponse->balance,
						'currency' => $client_details->default_currency,
						],
					  );

					$this->updateBetTransaction($existing_transaction->game_trans_id, $existing_transaction->bet_amount, $existing_transaction->income, 4, $existing_transaction->entry_id); // UPDATE BET TO REFUND!
					TGGHelper::creteTGGtransaction($existing_transaction->game_trans_id, $data, $client_response->requestoclient, $client_response->fundtransferresponse, $response,NULL, 4, $existing_transaction->bet_amount, $data['callback_id'], $data['data']['refund_round_id']);
					TGGHelper::saveLog('TGG gameRefund success '.$data['data']['refund_round_id'], $this->provider_db_id, json_encode($data), 'success refund');
				  	return $response;
				}catch(\Exception $e){
					$msg = array(
						"status" => 'error',
						"message" => $e->getMessage(),
					);
					TGGHelper::saveLog('TGG ERROR catch'.$data["name"], $this->provider_db_id, json_encode($data), $e->getMessage());
					return $msg;
				}
			else:
				// NO BET WAS FOUND DO NOTHING
				$player_details = TGGHelper::playerDetailsCall($client_details);
				$response = array(
					'status' => 'ok',
					'data' => [
						'balance' => (string)$player_details->playerdetailsresponse->balance,
						'currency' => $client_details->default_currency,
					],
			 	 );
				TGGHelper::saveLog('TGG no bet found '.$data['data']['refund_round_id'], $this->provider_db_id, json_encode($data), $response);
				return $response;
			endif;

		else:
			// NOTE IF CALLBACK WAS ALREADY PROCESS/DUPLICATE PROVIDER DONT NEED A ERROR RESPONSE! LEAVE IT AS IT IS!
			$player_details = TGGHelper::playerDetailsCall($client_details);
			$response = array(
				'status' => 'ok',
				'data' => [
					'balance' => (string)$player_details->playerdetailsresponse->balance,
					'currency' => $client_details->default_currency,
				],
		 	 );
			TGGHelper::saveLog('TGG duplicate error '.$data['data']['refund_round_id'], $this->provider_db_id, json_encode($data), $response);
			return $response;
		endif;
	}
	
	public static function getSignature($system_id, $version, array $args, $system_key,$type){
		$md5 = array();
		$md5[] = $system_id;
		$md5[] = $version;
		
	
		if($type == 'check_signature'){
			$signature = $args['signature']; // store the signature
			unset($args['signature']); // remove signature from the array
		}

		foreach ($args as $required_arg) {
			$arg = $required_arg;
			if(is_array($arg)){
				if(count($arg)) {
					$recursive_arg = '';
					array_walk_recursive($arg, function($item) use (& $recursive_arg) {
						if(!is_array($item)) { $recursive_arg .= ($item . ':');} 
					});
					$md5[] = substr($recursive_arg, 0, strlen($recursive_arg)-1); // get rid of last
				} else {
					$md5[] = '';
				}
			} else {
				$md5[] = $arg;
			}
		};

		$md5[] = $system_key;
		$md5_str = implode('*', $md5);
		$md5 = md5($md5_str);
		if($type == 'check_signature'){
			if($md5 == $signature){  // Generate Hash And Check it also!
				return 'true';
			}else{
				return 'false';
			}
		}elseif($type == 'get_signature') {
			return $md5;
		}
	}

	public function getGamelist(Request $request){
		$data = [
			'signature' => 'e5e1757feaf0301856ad9c309741f283',
		];
		$signature =  $this->getSignature($this->project_id, 1,$data,$this->api_key,'get_signature');
		
		$url = $this->api_url.'/game/getlist';
        $requesttosend = [
            'project' =>  $this->project_id,
			'version' => 1 ,
			'signature' => $signature
		
		];
        $client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer '.$signature
            ]
        ]);
        $guzzle_response = $client->post($url,['body' => json_encode($requesttosend)]);
		$client_response = json_decode($guzzle_response->getBody()->getContents());
		return json_encode($client_response);
		
	}


	public function getURL(){
		$token = 'n58ec5e159f769ae0b7b3a0774fdbf80';
		$client_player_details = $this->getClientDetails('token', $token);
        $requesttosend = [
          "project" => config('providerlinks.tgg.project_id'),
          "version" => 1,
          "token" => $token,
          "game" => 498, //game_code, game_id
          "settings" =>  [
            'user_id'=> $client_player_details->player_id,
            'language'=> $client_player_details->language ? $client_player_details->language : 'en',
          ],
          "denomination" => '1', // game to be launched with values like 1.0, 1, default
          "currency" => $client_player_details->default_currency,
          "return_url_info" => 1, // url link
          "callback_version" => 2, // POST CALLBACK
        ];
        
        $signature =  ProviderHelper::getSignature($requesttosend, $this->api_key);
        $requesttosend['signature'] = $signature;
		$url = $this->api_url.'/game/getURL';
		$client = new Client([
            'headers' => [ 
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        ]);
        $response = $client->post($url,[
            'form_params' => $requesttosend,
        ]);
        $res = json_decode($response->getBody(),TRUE);
        return isset($res['data']['link']) ? $res['data']['link'] : false;
	}


	/**
	 * Initialize the balance 
	 */
	public function gameInit($request){
		$data = $request;
		$token = $data['token'];
		$client_details = TGGHelper::getClientDetails('token',$token);
		if($client_details != null){
			$player_details = TGGHelper::playerDetailsCall($client_details);
				$data_response = [
					'status' => 'ok',
					'data' => [
						'balance' => (string)$player_details->playerdetailsresponse->balance,
						'currency' => $client_details->default_currency,
						'display_name' => $client_details->display_name
					]
				];
				TGGHelper::saveLog('TGG Balance Response '.$data['name'], $this->provider_db_id, json_encode($data), $data_response);
				return $data_response;
		}else{
			$data_response = [
				'status' => 'error',
				'error' => [
					'scope' => "user",
					'message' => "not found",
					'detils' => ''
				]
			];
			TGGHelper::saveLog('TGG ERROR '.$data['name'], $this->provider_db_id,  json_encode($data), $data_response);
			return $data_response;
		}
	}

	public static function updateBetTransaction($round_id, $pay_amount, $income, $win, $entry_id) {
		$update = DB::table('game_transactions')
			 // ->where('round_id', $round_id)
			 ->where('game_trans_id', $round_id) 
			 ->update(['pay_amount' => $pay_amount, 
				   'income' => $income, 
				   'win' => $win, 
				   'entry_id' => $entry_id,
				   'transaction_reason' => TGGHelper::updateReason($win),
				   'payout_reason' => TGGHelper::updateReason($win),
			 ]);
	 return ($update ? true : false);
 	}
	

}
