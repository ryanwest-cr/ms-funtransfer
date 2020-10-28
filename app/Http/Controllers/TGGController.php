<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
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
	}
	
	public $provider_db_id = 29; // 29 on test ,, 27 prod

	public function index(Request $request){
		Helper::saveLog('TGG index '.$request->name, $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');

		// $signature_checker = $this->getSignature($this->project_id, 2, $request->all(), $this->api_key,'check_signature');
		// return $signature_checker;
		// if($signature_checker == 'false'):
		// 	$msg = array(
		// 				"status" => 'error',
		// 				"error" => ["scope" => "user","no_refund" => 1,"message" => "Signature is invalid!"]
		// 			);
		// 	Helper::saveLog('TGG Signature Failed '.$request->name, $this->provider_db_id, json_encode($request->all()), $msg);
		// 	return $msg;
		// endif;
	
		$data = $request->all();
		if($request->name == 'init'){


			$game_init = $this->gameInit($request->all());
			return json_encode($game_init);



		}elseif($request->name == 'bet'){


			$string_to_obj = json_decode($request['data']['details']);
		    $game_id = $string_to_obj->game->game_id;

			$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $game_id);
			// $game_ext = $this->findGameExt($request['callback_id'], 1, 'transaction_id'); // Find if this callback in game extension
			$game_ext = $this->checkTransactionExist($request['callback_id'], 1);

			$this->saveLog('TGG Bet playerdetails & clientdetails', $this->provider_db_id, json_encode($data), 'bet arrived');
			// $player_details = ProviderHelper::playerDetailsCall($request['token']);
			$client_details = $this->getClientDetails('token', $request['token']);
			$this->saveLog('TGG BET playerdetails & clientdetails 2', $this->provider_db_id, json_encode($data), 'bet arrived');
			if($game_ext == 'false'): // NO BET
				$this->saveLog('TGG Success Bet PD', $this->provider_db_id, json_encode($data), 'game_ext= false');
				$player_details = ProviderHelper::playerDetailsCall($request['token']);
				$this->saveLog('TGG Success Bet PD Arrived', $this->provider_db_id, json_encode($data), 'game_ext= false');

				//if the amount is grater than to the bet amount  error message
				if($player_details->playerdetailsresponse->balance < $request['data']['amount']):
					$msg = array(
						"status" => 'error',
						"error" => ["scope" => "user","no_refund" => 1,"message" => "Not enough money"]
					);
					Helper::saveLog('TGG not enough balance '.$request["name"], $this->provider_db_id, json_encode($data), $msg);
					return $msg;
				endif;
				try {
					
					$game_transaction_type = 1; // 1 Bet, 2 Win
					$game_code = $game_details->game_id;
					$token_id = $client_details->token_id;
					$bet_amount = abs($request['data']['amount']);
					$pay_amount = 0;
					$income = 0;
					$win_type = 0;
					$method = 1;
					$win_or_lost = 0; // 0 lost,  5 processing
					$payout_reason = 'Bet';
					$provider_trans_id = $request['callback_id'];
					$bet_id = $request['data']['round_id'];
		
					//Create GameTransaction, GameExtension
					$this->saveLog('TGG Success Bet createGameTransaction', $this->provider_db_id, json_encode($data), 'game_ext= false');
					$game_trans_id  = ProviderHelper::createGameTransaction($token_id, $game_code, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $bet_id);
					
					$this->saveLog('TGG Success Bet createGameTransExt', $this->provider_db_id, json_encode($data), 'game_ext= false');
					$game_trans_ext_id = $this->createGameTransExt($game_trans_id,$provider_trans_id, $bet_id, $bet_amount, $game_transaction_type, $request, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);
					//get Round_id, Transaction_id
					// $transaction_id = ProviderHelper::findGameExt($provider_trans_id, 1,'transaction_id');
					
					//requesttosend, and responsetoclient client side
					$type = "debit";
					$rollback = false;
					$this->saveLog('TGG Success Bet fundTransfer', $this->provider_db_id, json_encode($data), 'game_ext= false');
					$client_response = ClientRequestHelper::fundTransfer($client_details,$bet_amount,$game_code,$game_details->game_name,$game_trans_ext_id,$game_trans_id,$type,$rollback);
					$this->saveLog('TGG Success Bet fundTransfer response', $this->provider_db_id, json_encode($data), 'game_ext= false');
					//response to provider				
					$response = array(
						'status' => 'ok',
						'data' => [
							'balance' =>  (string)$client_response->fundtransferresponse->balance,
							'currency' => $client_details->default_currency,
						],
					  );
					//UPDATE gameExtension
					$this->saveLog('TGG Success Bet updateGameTransactionExt response', $this->provider_db_id, json_encode($data), 'game_ext= false');
					$this->updateGameTransactionExt($game_trans_ext_id,$client_response->requestoclient,$client_response->fundtransferresponse,$response);	
					$this->saveLog('TGG Success Bet updateGameTransactionExt updated and responsed', $this->provider_db_id, json_encode($data), 'game_ext= false');
					Helper::saveLog('TGG PROCESS '.$request['name'], $this->provider_db_id, json_encode($data), $response);
				    return $response;
				}catch(\Exception $e){
					$msg = array(
						"status" => 'error',
						"message" => $e->getMessage(),
					);
					Helper::saveLog('TGG ERROR BET'.$request['name'], $this->provider_db_id, json_encode($data), $msg);
					return $msg;
				}
			else:
				$player_details = ProviderHelper::playerDetailsCall($request['token']);
				$this->saveLog('TGG Bet Player Details', $this->provider_db_id, json_encode($data), 'game_ext != false');	
				$response = array(
					'status' => 'ok',
					'data' => [
						'balance' => (string)$player_details->playerdetailsresponse->balance,
						'currency' => $client_details->default_currency,
					],
				);
	
				Helper::saveLog('TGG second bet '.$request['name'].' '.$request['callback_id'], $this->provider_db_id, json_encode($data), $response);
				return $response;
			endif;



		}elseif($request->name == 'win'){


			$string_to_obj = json_decode($request['data']['details']);
		    $game_id = $string_to_obj->game->game_id;
			$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $game_id);
			
			// $game_ext = $this->checkTransactionExist($request['callback_id'], 2);
			// $game_ext = ProviderHelper::findGameTransaction($request['data']['round_id'], 'round_id', 2);
			$game_ext = $this->checkTransactionExist($request['callback_id'], 2);
			if($game_ext == 'false'):
				// dd(1);
				// Find if win has bet record
				$existing_bet = ProviderHelper::findGameTransaction($request['data']['round_id'], 'round_id', 1); 
				// Bet is existing, else the bet is already updated to win //temporary == make it !=
				// No Bet was found check if this is a free spin and proccess it!
				if($existing_bet != 'false'): 
					$this->saveLog('TGG Success WIN', $this->provider_db_id, json_encode($data), 'EXISTING BET WIN');
					if(isset($string_to_obj->game->action) && $string_to_obj->game->action == 'freespin'):
						$client_details = $this->getClientDetails('token', $request['token']);
						$game_transaction_type = 2; // 1 Bet, 2 Win
						$game_code = $game_details->game_id;
						$token_id = $client_details->token_id;
						$bet_amount = 0;
						$pay_amount = abs($request['data']['amount']);
						$income = $bet_amount - $pay_amount;
						$method = $pay_amount == 0 ? 1 : 2;
						$win_or_lost =  $pay_amount == 0 ? 0 : 1;; // 0 lost,  5 processing
						$payout_reason = 'Freespin';
						$transaction_uuid = $request['callback_id'];
						$reference_transaction_uuid = $request['data']['round_id'];

						//Create GameTransaction, GameExtension
						$game_trans_id  = ProviderHelper::createGameTransaction($token_id, $game_code, $bet_amount,  $pay_amount, $method, $win_or_lost,  $this->updateReason(1), $payout_reason, $income, $transaction_uuid, $reference_transaction_uuid);
						
						$game_trans_ext_id = $this->createGameTransExt($game_trans_id,$transaction_uuid, $reference_transaction_uuid, $pay_amount, $game_transaction_type, $request, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);

						
						$type = "credit";
						$rollback = false;
						$client_response = ClientRequestHelper::fundTransfer($client_details,$pay_amount,$game_code,$game_details->game_name,$game_trans_ext_id,$game_trans_id,$type,$rollback);

						$response = array(
							'status' => 'ok',
							'data' => [
								'balance' => (string)$client_response->fundtransferresponse->balance,
								'currency' => $client_details->default_currency,
							],
						);
						$this->updateGameTransactionExt($game_trans_ext_id,$client_response->requestoclient,$client_response->fundtransferresponse,$response);
						Helper::saveLog('TGG FREE SPIN', $this->provider_db_id, json_encode($data), $response); 
						return $response;
					else:
						$game_code = $game_details->game_id;
						$amount = abs($request['data']['amount']);
						$transaction_uuid = $request['callback_id'];
						$reference_transaction_uuid = $request['data']['round_id'];

						$this->saveLog('TGG win getClient', $this->provider_db_id, json_encode($data), 'EXISTING BET WIN');
						$client_details = $this->getClientDetails('token', $request['token']);
						// $bet_transaction = ProviderHelper::findGameTransaction($existing_bet->game_trans_id, 'game_transaction');
						$this->saveLog('TGG win createGameTransExt', $this->provider_db_id, json_encode($data), 'EXISTING BET WIN');
						$game_trans_ext_id = $this->createGameTransExt($existing_bet->game_trans_id,$transaction_uuid, $reference_transaction_uuid, $amount, 2, $request, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);
						
						$type = "credit";
						$rollback = false;
						$this->saveLog('TGG win fundTransfer', $this->provider_db_id, json_encode($data), 'EXISTING BET WIN');
						$client_response = ClientRequestHelper::fundTransfer($client_details,$amount,$game_details->game_code,$game_details->game_name,$game_trans_ext_id,$existing_bet->game_trans_id,$type,$rollback);
						$this->saveLog('TGG win fundTransfer client respond', $this->provider_db_id, json_encode($data), 'EXISTING BET WIN');
						//reponse to provider
						$response = array(
							'status' => 'ok',
							'data' => [
								'balance' => (string)$client_response->fundtransferresponse->balance,
								'currency' => $client_details->default_currency,
							],
						);
						
						//Initialize data to pass
						$win = $amount > 0  ?  1 : 0;  /// 1win 0lost
						$type = $amount > 0  ? "credit" : "debit";
						$request_data = [
							'win' => $win,
							'amount' => $amount,
							'payout_reason' => $this->updateReason(1),
						];
						//update transaction
						$this->saveLog('TGG win updateGameTransaction', $this->provider_db_id, json_encode($data), 'EXISTING BET WIN');
						Helper::updateGameTransaction($existing_bet,$request_data,$type);
						$this->saveLog('TGG win updateGameTransactionExt', $this->provider_db_id, json_encode($data), 'EXISTING BET WIN');
						$this->updateGameTransactionExt($game_trans_ext_id,$client_response->requestoclient,$client_response->fundtransferresponse,$response);
						Helper::saveLog('TGG success '.$request["name"].' '.$request['data']['round_id'], $this->provider_db_id, json_encode($data), $response); 
						$this->saveLog('TGG win response', $this->provider_db_id, json_encode($data), 'EXISTING BET WIN');  
						return $response;
					endif;
				else:
					$this->saveLog('TGG win Player Call', $this->provider_db_id, json_encode($data), 'EXISTING BET WIN');  
					$player_details = ProviderHelper::playerDetailsCall($request['token']);
					$client_details = $this->getClientDetails('token', $request['token']);
					$response = array(
						'status' => 'ok',
						'data' => [
							'balance' => (string)$player_details->playerdetailsresponse->balance,
							'currency' => $client_details->default_currency,
						],
						);
					Helper::saveLog('TGG second102 '.$request["name"].' '.$request['callback_id'], $this->provider_db_id, json_encode($data), $response);
					$this->saveLog('TGG win Player Call Response', $this->provider_db_id, json_encode($data), 'EXISTING BET WIN');  
					return $response;
				endif;
			
			else:	
			    // NOTE IF CALLBACK WAS ALREADY PROCESS PROVIDER DONT NEED A ERROR RESPONSE! LEAVE IT AS IT IS!
				$player_details = ProviderHelper::playerDetailsCall($request['token']);
				$client_details = $this->getClientDetails('token', $request['token']);
				$response = array(
					'status' => 'ok',
					'data' => [
						'balance' => (string)$player_details->playerdetailsresponse->balance,
						'currency' => $client_details->default_currency,
					],
				);
				$this->saveLog('TGG win no exist bet', $this->provider_db_id, json_encode($data), 'EXISTING BET WIN');  
				Helper::saveLog('TGG second101 '.$request["name"].' '.$request['callback_id'], $this->provider_db_id, json_encode($request), $response);
				return $response;
				
			endif;



		}elseif($request->name == 'refund'){


			$this->saveLog('TGG gameRefund', $this->provider_db_id, json_encode($data), 'game_ext != false');
			$string_to_obj = json_decode($data['data']['details']);
			$game_id = $string_to_obj->game->game_id;
			
		    $game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $game_id);
			$game_refund = $this->findGameExt($data['data']['refund_callback_id'], 4, 'transaction_id'); // Find if this callback in game extension	
			
			if($game_refund == 'false'): // NO REFUND WAS FOUND PROCESS IT!
			
			$game_transaction_ext = $this->findGameExt($data['data']['refund_round_id'], 1, 'round_id'); // Find GameEXT
			
			if($game_transaction_ext == 'false'):
			    $player_details = ProviderHelper::playerDetailsCall($data['token']);
				$client_details = $this->getClientDetails('token', $data['token']);
				$response = array(
					'status' => 'ok',
					'data' => [
						'balance' => (string)$player_details->playerdetailsresponse->balance,
						'currency' => $client_details->default_currency,
					],
			 	);
				Helper::saveLog('TGG '.$data["name"].' '.$data['data']['refund_round_id'], $this->provider_db_id, json_encode($data), $response);
				return $response;
			endif;

			$game_transaction_ext_refund = $this->findGameExt($data['data']['refund_round_id'], 4, 'round_id'); // Find GameEXT\
		
			if($game_transaction_ext_refund != 'false'):
			    $player_details = ProviderHelper::playerDetailsCall($data['token']);
				$client_details = $this->getClientDetails('token', $data['token']);
				$response = array(
					'status' => 'ok',
					'data' => [
						'balance' => (string)$player_details->playerdetailsresponse->balance,
						'currency' => $client_details->default_currency,
					],
			 	);
				Helper::saveLog('TGG '.$data["name"].' '.$data['data']['refund_round_id'], $this->provider_db_id, json_encode($data), $response);
				return $response;
			endif;


			$existing_transaction = ProviderHelper::findGameTransaction($game_transaction_ext->game_trans_id, 'game_transaction');
			
			if($existing_transaction != 'false'): // IF BET WAS FOUND PROCESS IT!
				$transaction_type = $game_transaction_ext->game_transaction_type == 1 ? 'credit' : 'debit'; // 1 Bet
				$client_details = $this->getClientDetails('token', $data['token']);
			
			    if($transaction_type == 'debit'):
				   	$player_details = ProviderHelper::playerDetailsCall($data['token']);
				   	if($player_details->playerdetailsresponse->balance < $data['data']['amount']):
				   		$msg = array(
							"status" => 'error',
							"error" => ["scope" => "user","no_refund" => 1,"message" => "Not enough money"]
						);
						return $msg;
				   	endif;
			    endif;
				try {
					$requesttosend = [
						"access_token" => $client_details->client_access_token,
						"hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
						"type" => "fundtransferrequest",
						"datesent" => Helper::datesent(),
						"gamedetails" => [
						   "gameid" => $game_details->game_code, // $game_details->game_code
						   "gamename" => $game_details->game_name
						],
						"fundtransferrequest" => [
							  "playerinfo" => [
							  "client_player_id" => $client_details->client_player_id,
							  "token" => $data['token'],
						  ],
						  "fundinfo" => [
								"gamesessionid" => "",
								"transferid" => "",
								"transactiontype" => $transaction_type,
								"rollback" => "true",
								"currencycode" => $client_details->default_currency,
								"amount" => $data['data']['amount']
						  ]
						]
					  ];
					$client = new Client([
	                    'headers' => [ 
	                        'Content-Type' => 'application/json',
	                        'Authorization' => 'Bearer '.$client_details->client_access_token
	                    ]
	                ]);
					$guzzle_response = $client->post($client_details->fund_transfer_url,
						['body' => json_encode($requesttosend)]
					);
					$client_response = json_decode($guzzle_response->getBody()->getContents());
					$response = array(
						'status' => 'ok',
						'data' => [
							'balance' => (string)$client_response->fundtransferresponse->balance,
							'currency' => $client_details->default_currency,
						],
				 	 );
					// $this->updateBetTransaction($data['data']['refund_round_id'], $existing_transaction->bet_amount, $existing_transaction->income, 4, $existing_transaction->entry_id); // UPDATE BET TO REFUND!
					$this->updateBetTransaction($game_transaction_ext->game_trans_id, $existing_transaction->bet_amount, $existing_transaction->income, 4, $existing_transaction->entry_id); // UPDATE BET TO REFUND!
					$this->creteTGGtransaction($game_transaction_ext->game_trans_id, $data, $requesttosend, $client_response, $client_response,$data, 4, $data['data']['amount'], $data['callback_id'], $data['data']['refund_round_id']);
					Helper::saveLog('TGG Success '.$data["name"].' '.$data['data']['refund_round_id'], $this->provider_db_id, json_encode($data), $response);
					$this->saveLog('TGG gameRefund responsed', $this->provider_db_id, json_encode($data), 'game_ext != false');
				  	return $response;

				}catch(\Exception $e){
					$msg = array(
						"status" => 'error',
						"message" => $e->getMessage(),
					);
					Helper::saveLog('TGG ERROR '.$data["name"], $this->provider_db_id, json_encode($data), $e->getMessage());
					return $msg;
				}
			else:
				// NO BET WAS FOUND DO NOTHING
				$this->saveLog('TGG gameRefund Player Details 1', $this->provider_db_id, json_encode($data), 'game_ext != false');
				$player_details = ProviderHelper::playerDetailsCall($data['token']);
				$client_details = $this->getClientDetails('token', $data['token']);
				$response = array(
					'status' => 'ok',
					'data' => [
						'balance' => (string)$player_details->playerdetailsresponse->balance,
						'currency' => $client_details->default_currency,
					],
			 	 );
				Helper::saveLog('TGG no bet found '.$data['data']['refund_round_id'], $this->provider_db_id, json_encode($data), $response);
				return $response;
			endif;
			else:
				// NOTE IF CALLBACK WAS ALREADY PROCESS/DUPLICATE PROVIDER DONT NEED A ERROR RESPONSE! LEAVE IT AS IT IS!
				$this->saveLog('TGG gameRefund Player Details 2', $this->provider_db_id, json_encode($data), 'game_ext != false');
				$player_details = ProviderHelper::playerDetailsCall($data['token']);
				$client_details = $this->getClientDetails('token', $data['token']);
				$response = array(
					'status' => 'ok',
					'data' => [
						'balance' => (string)$player_details->playerdetailsresponse->balance,
						'currency' => $client_details->default_currency,
					],
			 	 );
				Helper::saveLog('TGG duplicate error '.$data['data']['refund_round_id'], $this->provider_db_id, json_encode($data), $response);
				return $response;
			endif;



		}
	}

	/**
	* $system_id - your project ID (number)
	* $version - API version (number)for API request OR callback version (number) for callback call
	* $args - array with API method OR callback parameters. API method parameters list you can find in the API method description
	* $system_key - your API key (secret key)
	*/ 
	
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
		$client_details = $this->getClientDetails('token',$token);
		if($client_details != null){
			$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
				$data_response = [
					'status' => 'ok',
					'data' => [
						'balance' => $player_details->playerdetailsresponse->balance,
						'currency' => $client_details->default_currency,
						'display_name' => $client_details->display_name
					]
				];
				Helper::saveLog('TGG Balance Response '.$data['name'], $this->provider_db_id, json_encode($data), $data_response);
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
			Helper::saveLog('TGG ERROR '.$data['name'], $this->provider_db_id,  json_encode($data), $data_response);
			return $data_response;
		}
	}

	// public function gameBet($request){
	// 	// $array = (array)$request['data']['details'];
	//  //    $newStr = str_replace("\\", '', $array[0]);
	//  //    $newStr2 = str_replace(';', '', $newStr);
	// 	// $string_to_obj = json_decode($newStr2);
	// 	// $game_id = $string_to_obj->game->game_id;
	// 	$string_to_obj = json_decode($request['data']['details']);
	//     $game_id = $string_to_obj->game->game_id;

	// 	$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $game_id);
	// 	// $game_ext = $this->findGameExt($request['callback_id'], 1, 'transaction_id'); // Find if this callback in game extension
	// 	$game_ext = $this->checkTransactionExist($request['callback_id'], 1);
	// 	$this->saveLog('TGG Success Bet playerdetails and clientdetails', $this->provider_db_id, json_encode($request), 'bet arrived');
	// 	// $player_details = ProviderHelper::playerDetailsCall($request['token']);
	// 	$client_details = $this->getClientDetails('token', $request['token']);
	// 	$this->saveLog('TGG Success Bet playerdetails and clientdetails processed', $this->provider_db_id, json_encode($request), 'bet arrived');
	// 	if($game_ext == 'false'): // NO BET
	// 		$this->saveLog('TGG Success Bet PD', $this->provider_db_id, json_encode($request), 'game_ext= false');
	// 		$player_details = ProviderHelper::playerDetailsCall($request['token']);
	// 		$this->saveLog('TGG Success Bet PD Arrived', $this->provider_db_id, json_encode($request), 'game_ext= false');
	// 		//if the amount is grater than to the bet amount  error message
	// 		// if($player_details->playerdetailsresponse->balance < $request['data']['amount']):
	// 		// 	$msg = array(
	// 		// 		"status" => 'error',
	// 		// 		"error" => ["scope" => "user","no_refund" => 1,"message" => "Not enough money"]
	// 		// 	);
	// 		// 	Helper::saveLog('TGG not enough balance '.$request["name"], $this->provider_db_id, json_encode($request), $msg);
	// 		// 	return $msg;
	// 		// endif;
	// 		// try {
				
	// 			// $client_details = $this->getClientDetails('token', $request['token']);

	// 			$game_transaction_type = 1; // 1 Bet, 2 Win
	// 			$game_code = $game_details->game_id;
	// 			$token_id = $client_details->token_id;
	// 			$bet_amount = abs($request['data']['amount']);
	// 			$pay_amount = 0;
	// 			$income = 0;
	// 			$win_type = 0;
	// 			$method = 1;
	// 			$win_or_lost = 0; // 0 lost,  5 processing
	// 			$payout_reason = 'Bet';
	// 			$provider_trans_id = $request['callback_id'];
	// 			$bet_id = $request['data']['round_id'];
	
	// 			//Create GameTransaction, GameExtension
	// 			$this->saveLog('TGG Success Bet createGameTransaction', $this->provider_db_id, json_encode($request), 'game_ext= false');
	// 			$game_trans_id  = ProviderHelper::createGameTransaction($token_id, $game_code, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $bet_id);
				
	// 			$this->saveLog('TGG Success Bet createGameTransExt', $this->provider_db_id, json_encode($request), 'game_ext= false');
	// 			$game_trans_ext_id = $this->createGameTransExt($game_trans_id,$provider_trans_id, $bet_id, $bet_amount, $game_transaction_type, $request, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);
	// 			//get Round_id, Transaction_id
	// 			// $transaction_id = ProviderHelper::findGameExt($provider_trans_id, 1,'transaction_id');
				
	// 			//requesttosend, and responsetoclient client side
	// 			$type = "debit";
	// 			$rollback = false;
	// 			$this->saveLog('TGG Success Bet fundTransfer', $this->provider_db_id, json_encode($request), 'game_ext= false');
	// 			$client_response = ClientRequestHelper::fundTransfer($client_details,$bet_amount,$game_code,$game_details->game_name,$game_trans_ext_id,$game_trans_id,$type,$rollback);
	// 			$this->saveLog('TGG Success Bet fundTransfer response', $this->provider_db_id, json_encode($request), 'game_ext= false');
	// 			//response to provider				
	// 			$response = array(
	// 				'status' => 'ok',
	// 				'data' => [
	// 					'balance' =>  (string)$client_response->fundtransferresponse->balance,
	// 					'currency' => $client_details->default_currency,
	// 				],
	// 			  );
	// 			//UPDATE gameExtension
	// 			$this->saveLog('TGG Success Bet updateGameTransactionExt response', $this->provider_db_id, json_encode($request), 'game_ext= false');
	// 			$this->updateGameTransactionExt($game_trans_ext_id,$client_response->requestoclient,$client_response->fundtransferresponse,$response);	
	// 			$this->saveLog('TGG Success Bet updateGameTransactionExt updated and responsed', $this->provider_db_id, json_encode($request), 'game_ext= false');
	// 			Helper::saveLog('TGG PROCESS '.$request['name'], $this->provider_db_id, json_encode($request), $response);
	// 		    return $response;
	// 		// }catch(\Exception $e){
	// 		// 	$msg = array(
	// 		// 		"status" => 'error',
	// 		// 		"message" => $e->getMessage(),
	// 		// 	);
	// 		// 	Helper::saveLog('TGG ERROR BET'.$request['name'], $this->provider_db_id, json_encode($request), $msg);
	// 		// 	return $msg;
	// 		// }
	// 	else:
	// 		// NOTE IF CALLBACK WAS ALREADY PROCESS PROVIDER DONT NEED A ERROR RESPONSE! LEAVE IT AS IT IS!
	// 		// if($game_ext->provider_trans_id == $request["callback_id"]): //if same duplicate
	// 			$player_details = ProviderHelper::playerDetailsCall($request['token']);
	// 			$this->saveLog('TGG Bet Player Details', $this->provider_db_id, json_encode($request), 'game_ext != false');	
	// 			$response = array(
	// 				'status' => 'ok',
	// 				'data' => [
	// 					'balance' => (string)$player_details->playerdetailsresponse->balance,
	// 					'currency' => $client_details->default_currency,
	// 				],
	// 			);
	
	// 			Helper::saveLog('TGG second bet '.$request['name'].' '.$request['callback_id'], $this->provider_db_id, json_encode($request), $response);
	// 			return $response;
	// 		// else:
	// 		// 	$response = array(
	// 		// 		'status' => 'ok',
	// 		// 		'data' => [
	// 		// 			'balance' => $player_details->playerdetailsresponse->balance,
	// 		// 			'currency' => $client_details->default_currency,
	// 		// 		],
	// 		// 	);
	// 		// 	Helper::saveLog('TGG error second '.$request["name"], $this->provider_db_id, json_encode($request), $response);
	// 		// 	return $msg;
	// 		// endif;
	// 	endif;
	// }

	// public function gameWin($request){
	// 	// $array = (array)$request['data']['details'];
	// 	// $newStr = str_replace("\\", '', $array[0]);
	//  	// $newStr2 = str_replace(';', '', $newStr);
	// 	// $string_to_obj = json_decode($newStr2);
	// 	// $game_id = $string_to_obj->game->game_id;
	// 	$string_to_obj = json_decode($request['data']['details']);
	//     $game_id = $string_to_obj->game->game_id;
	// 	// $game_ext = ProviderHelper::findGameExt($request['callback_id'], 2, 'transaction_id'); // Find if this callback in game extension
	// 	$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $game_id);
	// 	// $game_details = Helper::findGameDetails('game_code', $this->provider_db_id, 1);
		
	// 	// $game_ext = $this->checkTransactionExist($request['callback_id'], 2);
	// 	// $game_ext = ProviderHelper::findGameTransaction($request['data']['round_id'], 'round_id', 2);
	// 	$game_ext = $this->checkTransactionExist($request['callback_id'], 2);
	// 	if($game_ext == 'false'):
			
	// 		$existing_bet = ProviderHelper::findGameTransaction($request['data']['round_id'], 'round_id', 1); // Find if win has bet record
	// 		if($existing_bet != 'false'): // Bet is existing, else the bet is already updated to win //temporary == make it !=
	// 				// No Bet was found check if this is a free spin and proccess it!
	// 			$this->saveLog('TGG Success WIN', $this->provider_db_id, json_encode($request), 'EXISTING BET WIN');
	// 			if(isset($string_to_obj->game->action) && $string_to_obj->game->action == 'freespin'):
	// 				$client_details = $this->getClientDetails('token', $request['token']);
	// 				$game_transaction_type = 2; // 1 Bet, 2 Win
	// 				$game_code = $game_details->game_id;
	// 				$token_id = $client_details->token_id;
	// 				$bet_amount = 0;
	// 				$pay_amount = abs($request['data']['amount']);
	// 				$income = $bet_amount - $pay_amount;
	// 				$method = $pay_amount == 0 ? 1 : 2;
	// 				$win_or_lost =  $pay_amount == 0 ? 0 : 1;; // 0 lost,  5 processing
	// 				$payout_reason = 'Freespin';
	// 				$transaction_uuid = $request['callback_id'];
	// 				$reference_transaction_uuid = $request['data']['round_id'];

	// 				//Create GameTransaction, GameExtension
	// 				$game_trans_id  = ProviderHelper::createGameTransaction($token_id, $game_code, $bet_amount,  $pay_amount, $method, $win_or_lost,  $this->updateReason(1), $payout_reason, $income, $transaction_uuid, $reference_transaction_uuid);
					
	// 				$game_trans_ext_id = $this->createGameTransExt($game_trans_id,$transaction_uuid, $reference_transaction_uuid, $pay_amount, $game_transaction_type, $request, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);

	// 				// $transaction_id = ProviderHelper::findGameExt($transaction_uuid, 2,'transaction_id');
					
	// 				$type = "credit";
	// 				$rollback = false;
	// 				$client_response = ClientRequestHelper::fundTransfer($client_details,$pay_amount,$game_code,$game_details->game_name,$game_trans_ext_id,$game_trans_id,$type,$rollback);

	// 				$response = array(
	// 					'status' => 'ok',
	// 					'data' => [
	// 						'balance' => (string)$client_response->fundtransferresponse->balance,
	// 						'currency' => $client_details->default_currency,
	// 					],
	// 				);
	// 				$this->updateGameTransactionExt($game_trans_ext_id,$client_response->requestoclient,$client_response->fundtransferresponse,$response);
	// 				Helper::saveLog('TGG FREE SPIN', $this->provider_db_id, json_encode($request), $response); 
	// 				return $response;
	// 			else:
	// 				$game_code = $game_details->game_id;
	// 				$amount = abs($request['data']['amount']);
	// 				$transaction_uuid = $request['callback_id'];
	// 				$reference_transaction_uuid = $request['data']['round_id'];

	// 				$this->saveLog('TGG win getClient', $this->provider_db_id, json_encode($request), 'EXISTING BET WIN');
	// 				$client_details = $this->getClientDetails('token', $request['token']);
	// 				// $bet_transaction = ProviderHelper::findGameTransaction($existing_bet->game_trans_id, 'game_transaction');
	// 				$this->saveLog('TGG win createGameTransExt', $this->provider_db_id, json_encode($request), 'EXISTING BET WIN');
	// 				$game_trans_ext_id = $this->createGameTransExt($existing_bet->game_trans_id,$transaction_uuid, $reference_transaction_uuid, $amount, 2, $request, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);
					
	// 				//get game_trans_id and game_trans_ext
	// 				// $transaction_id = ProviderHelper::findGameExt($transaction_uuid, 2,'transaction_id');
	// 				//requesttosend, and responsetoclient client side

	// 				$type = "credit";
	// 				$rollback = false;
	// 				$this->saveLog('TGG win fundTransfer', $this->provider_db_id, json_encode($request), 'EXISTING BET WIN');
	// 				$client_response = ClientRequestHelper::fundTransfer($client_details,$amount,$game_details->game_code,$game_details->game_name,$game_trans_ext_id,$existing_bet->game_trans_id,$type,$rollback);
	// 				$this->saveLog('TGG win fundTransfer client respond', $this->provider_db_id, json_encode($request), 'EXISTING BET WIN');
	// 				//reponse to provider
	// 				$response = array(
	// 					'status' => 'ok',
	// 					'data' => [
	// 						'balance' => (string)$client_response->fundtransferresponse->balance,
	// 						'currency' => $client_details->default_currency,
	// 					],
	// 				);
					
	// 				//Initialize data to pass
	// 				$win = $amount > 0  ?  1 : 0;  /// 1win 0lost
	// 				$type = $amount > 0  ? "credit" : "debit";
	// 				$request_data = [
	// 					'win' => $win,
	// 					'amount' => $amount,
	// 					'payout_reason' => $this->updateReason(1),
	// 				];
	// 				//update transaction
	// 				$this->saveLog('TGG win updateGameTransaction', $this->provider_db_id, json_encode($request), 'EXISTING BET WIN');
	// 				Helper::updateGameTransaction($existing_bet,$request_data,$type);
	// 				$this->saveLog('TGG win updateGameTransactionExt', $this->provider_db_id, json_encode($request), 'EXISTING BET WIN');
	// 				$this->updateGameTransactionExt($game_trans_ext_id,$client_response->requestoclient,$client_response->fundtransferresponse,$response);
	// 				Helper::saveLog('TGG success '.$request["name"].' '.$request['data']['round_id'], $this->provider_db_id, json_encode($request), $response); 
	// 				$this->saveLog('TGG win response', $this->provider_db_id, json_encode($request), 'EXISTING BET WIN');  
	// 				return $response;
	// 			endif;
	// 		else:
	// 			$this->saveLog('TGG win Player Call', $this->provider_db_id, json_encode($request), 'EXISTING BET WIN');  
	// 			$player_details = ProviderHelper::playerDetailsCall($request['token']);
	// 			$client_details = $this->getClientDetails('token', $request['token']);
	// 			$response = array(
	// 				'status' => 'ok',
	// 				'data' => [
	// 					'balance' => (string)$player_details->playerdetailsresponse->balance,
	// 					'currency' => $client_details->default_currency,
	// 				],
	// 				);
	// 			Helper::saveLog('TGG second102 '.$request["name"].' '.$request['callback_id'], $this->provider_db_id, json_encode($request), $response);
	// 			$this->saveLog('TGG win Player Call Response', $this->provider_db_id, json_encode($request), 'EXISTING BET WIN');  
	// 			return $response;
			
	// 		endif;
		
	// 	else:
	// 		    // NOTE IF CALLBACK WAS ALREADY PROCESS PROVIDER DONT NEED A ERROR RESPONSE! LEAVE IT AS IT IS!
	// 		// if($game_ext->provider_trans_id == $request["callback_id"]): //if same duplicate
	// 			$player_details = ProviderHelper::playerDetailsCall($request['token']);
	// 			$client_details = $this->getClientDetails('token', $request['token']);
	// 			$response = array(
	// 				'status' => 'ok',
	// 				'data' => [
	// 					'balance' => (string)$player_details->playerdetailsresponse->balance,
	// 					'currency' => $client_details->default_currency,
	// 				],
	// 			);
	// 			$this->saveLog('TGG win no exist bet', $this->provider_db_id, json_encode($request), 'EXISTING BET WIN');  
	// 			Helper::saveLog('TGG second101 '.$request["name"].' '.$request['callback_id'], $this->provider_db_id, json_encode($request), $response);
	// 			return $response;
	// 		// else:
	// 			// $msg = array(
	// 			// 	"status" => 'error',
	// 			// 	"error" => ["scope" => "user","no_refund" => 1,"message" => "Not enough money"]
	// 			// );
	// 			// Helper::saveLog('TGG error second '.$request["name"], $this->provider_db_id, json_encode($request), $msg);
	// 			// return $msg;
	// 		// endif;
	// 	endif;

	// }

	// public function gameRefund($request){
	// 	$this->saveLog('TGG gameRefund', $this->provider_db_id, json_encode($request), 'game_ext != false');
	// 	$string_to_obj = json_decode($data['data']['details']);
	// 	$game_id = $string_to_obj->game->game_id;
		
	//     $game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $game_id);
	// 	$game_refund = $this->findGameExt($data['data']['refund_callback_id'], 4, 'transaction_id'); // Find if this callback in game extension	
		
	// 	if($game_refund == 'false'): // NO REFUND WAS FOUND PROCESS IT!
		
	// 	$game_transaction_ext = $this->findGameExt($data['data']['refund_round_id'], 1, 'round_id'); // Find GameEXT
		
	// 	if($game_transaction_ext == 'false'):
	// 	    $player_details = ProviderHelper::playerDetailsCall($data['token']);
	// 		$client_details = $this->getClientDetails('token', $data['token']);
	// 		$response = array(
	// 			'status' => 'ok',
	// 			'data' => [
	// 				'balance' => (string)$player_details->playerdetailsresponse->balance,
	// 				'currency' => $client_details->default_currency,
	// 			],
	// 	 	);
	// 		Helper::saveLog('TGG '.$data["name"].' '.$data['data']['refund_round_id'], $this->provider_db_id, json_encode($data), $response);
	// 		return $response;
	// 	endif;

	// 	$game_transaction_ext_refund = $this->findGameExt($data['data']['refund_round_id'], 4, 'round_id'); // Find GameEXT\
	
	// 	if($game_transaction_ext_refund != 'false'):
	// 	    $player_details = ProviderHelper::playerDetailsCall($data['token']);
	// 		$client_details = $this->getClientDetails('token', $data['token']);
	// 		$response = array(
	// 			'status' => 'ok',
	// 			'data' => [
	// 				'balance' => (string)$player_details->playerdetailsresponse->balance,
	// 				'currency' => $client_details->default_currency,
	// 			],
	// 	 	);
	// 		Helper::saveLog('TGG '.$data["name"].' '.$data['data']['refund_round_id'], $this->provider_db_id, json_encode($data), $response);
	// 		return $response;
	// 	endif;


	// 	$existing_transaction = ProviderHelper::findGameTransaction($game_transaction_ext->game_trans_id, 'game_transaction');
		
	// 	if($existing_transaction != 'false'): // IF BET WAS FOUND PROCESS IT!
	// 		$transaction_type = $game_transaction_ext->game_transaction_type == 1 ? 'credit' : 'debit'; // 1 Bet
	// 		$client_details = $this->getClientDetails('token', $data['token']);
		
	// 	    if($transaction_type == 'debit'):
	// 		   	$player_details = ProviderHelper::playerDetailsCall($data['token']);
	// 		   	if($player_details->playerdetailsresponse->balance < $data['data']['amount']):
	// 		   		$msg = array(
	// 					"status" => 'error',
	// 					"error" => ["scope" => "user","no_refund" => 1,"message" => "Not enough money"]
	// 				);
	// 				return $msg;
	// 		   	endif;
	// 	    endif;
	// 		try {
	// 			$requesttosend = [
	// 				"access_token" => $client_details->client_access_token,
	// 				"hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
	// 				"type" => "fundtransferrequest",
	// 				"datesent" => Helper::datesent(),
	// 				"gamedetails" => [
	// 				   "gameid" => $game_details->game_code, // $game_details->game_code
	// 				   "gamename" => $game_details->game_name
	// 				],
	// 				"fundtransferrequest" => [
	// 					  "playerinfo" => [
	// 					  "client_player_id" => $client_details->client_player_id,
	// 					  "token" => $data['token'],
	// 				  ],
	// 				  "fundinfo" => [
	// 						"gamesessionid" => "",
	// 						"transferid" => "",
	// 						"transactiontype" => $transaction_type,
	// 						"rollback" => "true",
	// 						"currencycode" => $client_details->default_currency,
	// 						"amount" => $data['data']['amount']
	// 				  ]
	// 				]
	// 			  ];
	// 			$client = new Client([
 //                    'headers' => [ 
 //                        'Content-Type' => 'application/json',
 //                        'Authorization' => 'Bearer '.$client_details->client_access_token
 //                    ]
 //                ]);
	// 			$guzzle_response = $client->post($client_details->fund_transfer_url,
	// 				['body' => json_encode($requesttosend)]
	// 			);
	// 			$client_response = json_decode($guzzle_response->getBody()->getContents());
	// 			$response = array(
	// 				'status' => 'ok',
	// 				'data' => [
	// 					'balance' => (string)$client_response->fundtransferresponse->balance,
	// 					'currency' => $client_details->default_currency,
	// 				],
	// 		 	 );
	// 			// $this->updateBetTransaction($data['data']['refund_round_id'], $existing_transaction->bet_amount, $existing_transaction->income, 4, $existing_transaction->entry_id); // UPDATE BET TO REFUND!
	// 			$this->updateBetTransaction($game_transaction_ext->game_trans_id, $existing_transaction->bet_amount, $existing_transaction->income, 4, $existing_transaction->entry_id); // UPDATE BET TO REFUND!
	// 			$this->creteTGGtransaction($game_transaction_ext->game_trans_id, $data, $requesttosend, $client_response, $client_response,$data, 4, $data['data']['amount'], $data['callback_id'], $data['data']['refund_round_id']);
	// 			Helper::saveLog('TGG Success '.$data["name"].' '.$data['data']['refund_round_id'], $this->provider_db_id, json_encode($data), $response);
	// 			$this->saveLog('TGG gameRefund responsed', $this->provider_db_id, json_encode($request), 'game_ext != false');
	// 		  	return $response;

	// 		}catch(\Exception $e){
	// 			$msg = array(
	// 				"status" => 'error',
	// 				"message" => $e->getMessage(),
	// 			);
	// 			Helper::saveLog('TGG ERROR '.$data["name"], $this->provider_db_id, json_encode($data), $e->getMessage());
	// 			return $msg;
	// 		}
	// 	else:
	// 		// NO BET WAS FOUND DO NOTHING
	// 		$this->saveLog('TGG gameRefund Player Details 1', $this->provider_db_id, json_encode($request), 'game_ext != false');
	// 		$player_details = ProviderHelper::playerDetailsCall($data['token']);
	// 		$client_details = $this->getClientDetails('token', $data['token']);
	// 		$response = array(
	// 			'status' => 'ok',
	// 			'data' => [
	// 				'balance' => (string)$player_details->playerdetailsresponse->balance,
	// 				'currency' => $client_details->default_currency,
	// 			],
	// 	 	 );
	// 		Helper::saveLog('TGG no bet found '.$data['data']['refund_round_id'], $this->provider_db_id, json_encode($data), $response);
	// 		return $response;
	// 	endif;
	// 	else:
	// 		// NOTE IF CALLBACK WAS ALREADY PROCESS/DUPLICATE PROVIDER DONT NEED A ERROR RESPONSE! LEAVE IT AS IT IS!
	// 		$this->saveLog('TGG gameRefund Player Details 2', $this->provider_db_id, json_encode($request), 'game_ext != false');
	// 		$player_details = ProviderHelper::playerDetailsCall($data['token']);
	// 		$client_details = $this->getClientDetails('token', $data['token']);
	// 		$response = array(
	// 			'status' => 'ok',
	// 			'data' => [
	// 				'balance' => (string)$player_details->playerdetailsresponse->balance,
	// 				'currency' => $client_details->default_currency,
	// 			],
	// 	 	 );
	// 		Helper::saveLog('TGG duplicate error '.$data['data']['refund_round_id'], $this->provider_db_id, json_encode($data), $response);
	// 		return $response;
	// 	endif;
	// }

	public function findGameExt($provider_transaction_id, $game_transaction_type, $type) {
		$transaction_db = DB::table('game_transaction_ext as gte');
        if ($type == 'transaction_id') {
			$transaction_db->where([
		 		["gte.provider_trans_id", "=", $provider_transaction_id],
		 		["gte.game_transaction_type", "=", $game_transaction_type],
		 	]);
		}
		if ($type == 'round_id') {
			$transaction_db->where([
		 		["gte.round_id", "=", $provider_transaction_id],
		 		["gte.game_transaction_type", "=", $game_transaction_type],
		 	]);
		}  
		$result= $transaction_db->first();
		return $result ? $result : 'false';
	}



	/* ISOLATION METHODDS FOR TESTING PERFORMANCE OPTIMAZTION */


	/* PROVIDER HELPERS */

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


	/* PROVIDER HELPERS */

	/**
	 * Create Game Extension Logs bet/Win/Refund
	 * @param [int] $[gametransaction_id] [<ID of the game transaction>]
	 * @param [json array] $[provider_request] [<Incoming Call>]
	 * @param [json array] $[mw_request] [<Outgoing Call>]
	 * @param [json array] $[mw_response] [<Incoming Response Call>]
	 * @param [json array] $[client_response] [<Incoming Response Call>]
	 * 
	 */
	public function creteTGGtransaction($gametransaction_id,$provider_request,$mw_request,$mw_response,$client_response, $transaction_detail,$game_transaction_type, $amount=null, $provider_trans_id=null, $round_id=null){
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
		return $gametransactionext;
	}


	public  function saveLog($method, $provider_id = 0, $request_data, $response_data) {
		$data = [
				"method_name" => $method,
				"provider_id" => $provider_id,
				"request_data" => json_encode(json_decode($request_data)),
				"response_data" => json_encode($response_data)
			];
		// return DB::table('seamless_request_logs')->insertGetId($data);
		return DB::table('debug')->insertGetId($data);
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
	public function updateBetTransaction($round_id, $pay_amount, $income, $win, $entry_id) {
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

	public static function createGameTransExt($game_trans_id, $provider_trans_id, $round_id, $amount, $game_type, $provider_request, $mw_response, $mw_request, $client_response, $transaction_detail){
		$gametransactionext = array(
			"game_trans_id" => $game_trans_id,
			"provider_trans_id" => $provider_trans_id,
			"round_id" => $round_id,
			"amount" => $amount,
			"game_transaction_type"=>$game_type,
			"provider_request" => json_encode($provider_request),
			"mw_response" =>json_encode($mw_response),
			"mw_request"=>json_encode($mw_request),
			"client_response" =>json_encode($client_response),
			"transaction_detail" =>json_encode($transaction_detail)
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gamestransaction_ext_ID;
	}

	public static function updateGameTransactionExt($gametransextid,$mw_request,$mw_response,$client_response){
		$gametransactionext = array(
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		DB::table('game_transaction_ext')->where("game_trans_ext_id",$gametransextid)->update($gametransactionext);
	}

}
