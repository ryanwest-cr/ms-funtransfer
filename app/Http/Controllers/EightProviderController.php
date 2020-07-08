<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\CallParameters;
use App\Helpers\ProviderHelper;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;

use DB;


/**
 * 8Provider (API Version 2 POST DATA METHODS)
 *
 * @version 1.0
 * @method register
 *
 */
class EightProviderController extends Controller
{

	public $api_url = 'http://api.8provider.com';
	public $secret_key = 'c270d53d4d83d69358056dbca870c0ce';
	public $project_id = '1042';

	// return md5('1042*1*c270d53d4d83d69358056dbca870c0ce');
	// 97e464b9b6e4d2de3b5d36facffa9556

    /**
     * GetSignature 
     * @return string
     *
     */
	public function getSignature(array $args, $system_key){
	    $md5 = array();
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
	    return $md5;
	}

	/**
	 * @author's note single method that will handle 4 API Calls
	 * @param name = bet, win, refund,
	 * 
	 */
	public function index(Request $request){
		Helper::saveLog('8P index '.$request->name, 19, json_encode($request->all()), 'ENDPOINT HIT');
		if($request->name == 'init'){

			$game_init = $this->gameInitialize($request->all());
			return json_encode($game_init);

		}elseif($request->name == 'bet'){
			$bet_handler = $this->gameBet($request->all());
			return json_encode($bet_handler);

		}elseif($request->name == 'win'){

			$win_handler = $this->gameWin($request->all());
			return json_encode($win_handler);

		}elseif($request->name == 'refund'){

			$refund_handler = $this->gameRefund($request->all());
			return json_encode($refund_handler);
		}
	}


	/**
	 * @param data [array]
	 * 
	 */
	public function gameInitialize($data){
		$player_details = ProviderHelper::playerDetailsCall($data['token']);
		$client_details = ProviderHelper::getClientDetails('token', $data['token']);
		$response = array(
			'status' => 'ok',
			'data' => [
				'balance' => $player_details->playerdetailsresponse->balance,
				'currency' => $client_details->default_currency,
			],
	 	 );
	  	return $response;
	}


	/**
	 * @param data [array]
	 * NOTE APPLY FILTER BALANCE
	 * 
	 */
	public function gameBet($data){
		    // return $details = $data;
		    // $newStr = str_replace("\\", '', $details);
		    // $fist_encode = json_encode($newStr, false);
		    // return gettype($fourth_encode);

		    $client_details = ProviderHelper::getClientDetails('token', $data['token']);
		    // $player_details = ProviderHelper::playerDetailsCall($data['token']);
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
					"token" => $data['token'],
				],
				"fundinfo" => [
				      "gamesessionid" => "",
				      "transactiontype" => 'debit',
				      "rollback" => "false",
				      "currencycode" => $client_details->default_currency,
				      "amount" => $data['data']['amount']
				]
			  ]
			];
			try {
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
						'balance' => $client_response->fundtransferresponse->balance,
						'currency' => $client_details->default_currency,
					],
			 	 );
		 		$payout_reason = 'Bet';
		 		$win_or_lost = 0; // 0 Lost, 1 win, 3 draw, 4 refund, 5 processing
		 		$method = 1; // 1 bet, 2 win
		 	    $token_id = $client_details->token_id;
		 	    $bet_payout = 0; // Bet always 0 payout!
		 	    $income = $data['data']['amount'];
		 	    $provider_trans_id = $data['callback_id'];
		 	    $round_id = $data['data']['round_id'];
				$game_trans = Helper::saveGame_transaction($token_id, 1, $data['data']['amount'],  $data['data']['amount'], $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $round_id);
		   		$trans_ext = $this->create8PTransactionExt($game_trans, $data, $requesttosend, $client_response, $client_response,$data, 1, $data['data']['amount'], $provider_trans_id ,$round_id);
			  	return $response;
			}catch(\Exception $e){
				$msg = array(
					"status" => 'failed',
					"message" => $e->getMessage(),
				);
				Helper::saveLog('8P ERROR BET', 19, json_encode($msg), 'error bet');
				return $msg;
			}
	}

	public function gameWin($data){
		$existing_bet = $this->findGameTransaction($data['data']['round_id'], 'round_id', 1);
		if($existing_bet != 'false'): // Bet is existing
			$client_details = ProviderHelper::getClientDetails('token', $data['token']);
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
						"token" => $data['token'],
					],
					"fundinfo" => [
					      "gamesessionid" => "",
					      "transactiontype" => 'credit',
					      "rollback" => "false",
					      "currencycode" => $client_details->default_currency,
					      "amount" => $data['data']['amount']
					]
				  ]
			];
				try {
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
							'balance' => $client_response->fundtransferresponse->balance,
							'currency' => $client_details->default_currency,
						],
				 	 );

					$amount = $data['data']['amount'];
			 	    $round_id = $data['data']['round_id'];
			 	    if($existing_bet->bet_amount > $amount):
	 	  				$win = 0; // lost
	 	  				$entry_id = 1; //lost
	 	  				$income = $existing_bet->bet_amount - $amount;
	 	  			else:
	 	  				$win = 1; //win
	 	  				$entry_id = 2; //win
	 	  				$income = $existing_bet->bet_amount - $amount;
	 	  			endif;
					$this->updateBetToWin($round_id, $amount, $income, $win, $entry_id);
					$this->create8PTransactionExt($existing_bet->game_trans_id, $data, $requesttosend, $client_response, $client_response,$data, 2, $data['data']['amount'], $data['callback_id'] ,$round_id);

				  	return $response;

				}catch(\Exception $e){
					return array(
						"status" => 'failed',
						"message" => $e->getMessage(),
					);
				}
		else:
			return 'false';
		endif;
	}

	
	public function gameRefund($data){
		    $client_details = ProviderHelper::getClientDetails('token', $data['token']);
		    // $player_details = ProviderHelper::playerDetailsCall($data['token']);
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
					"token" => $data['token'],
				],
				"fundinfo" => [
				      "gamesessionid" => "",
				      "transactiontype" => 'credit',
				      "rollback" => "true",
				      "currencycode" => $client_details->default_currency,
				      "amount" => $data['data']['amount']
				]
			  ]
			];
			try {
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
						'balance' => $client_response->fundtransferresponse->balance,
						'currency' => $client_details->default_currency,
					],
			 	 );
			  	return $response;

			}catch(\Exception $e){
				return array(
					"status" => 'failed',
					"message" => $e->getMessage(),
				);
			}
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
		return $gametransactionext;
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
	public  function updateBetToWin($round_id, $pay_amount, $income, $win, $entry_id) {
   	    $update = DB::table('game_transactions')
                ->where('round_id', $round_id)
                ->update(['pay_amount' => $pay_amount, 
	        		  'income' => $income, 
	        		  'win' => $win, 
	        		  'entry_id' => $entry_id,
	        		  'transaction_reason' => 'Bet updated to win'
	    		]);
		return ($update ? true : false);
	}

	/**
	 * Find bet and update to win 
	 * @param [identifier] $[round_id] [<ID of the game transaction>]
	 * @param [type] $[pay_amount] [<amount to change>]
	 * @param [int] $[entry_type] [<1 bet, 2 win>]
	 * 
	 */
    public  function findGameTransaction($identifier, $type, $entry_type=1) {
    		$transaction_db = DB::table('game_transactions as gt')
					    	->select('gt.*', 'gte.transaction_detail')
						    ->leftJoin("game_transaction_ext AS gte", "gte.game_trans_id", "=", "gt.game_trans_id");
		 				   
		    if ($type == 'transaction_id') {
				$transaction_db->where([
			 		["gt.provider_trans_id", "=", $identifier],
			 		["gt.entry_id", "=", $entry_type],
			 	]);
			}
			if ($type == 'round_id') {
				$transaction_db->where([
			 		["gt.round_id", "=", $identifier],
			 		["gt.entry_id", "=", $entry_type],
			 	]);
			}
			if ($type == 'refundbet') { // TEST
				$transaction_db->where([
			 		["gt.round_id", "=", $identifier],
			 		["gt.entry_id", "=", $entry_type],
			 	]);
			}
			$result= $transaction_db
	 			->first();

			if($result){
				return $result;
			}else{
				return 'false';
			}
	}

	
}
