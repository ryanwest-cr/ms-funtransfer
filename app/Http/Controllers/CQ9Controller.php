<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use App\Helpers\AWSHelper;
use App\Helpers\GameLobby;
use App\Helpers\ClientRequestHelper;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Input;
use DB;

class CQ9Controller extends Controller
{

	public $api_url, $api_token, $provider_db_id;

	// /gameboy/player/logout
	// /gameboy/game/list/cq9
	// /gameboy/game/halls

	public function __construct(){
    	$this->api_url = config('providerlinks.cqgames.api_url');
    	$this->api_token = config('providerlinks.cqgames.api_token');
    	$this->provider_db_id = config('providerlinks.cqgames.pdbid');
    }

    public function checkAuth($wtoken){
    	$wallet_token = config('providerlinks.cqgames.wallet_token');
		$access_granted = false;
		foreach ($wallet_token as $key){
			if($wtoken == $key){
				$access_granted = true;
			}
		}
		return $access_granted;
    }

    // public function checkAuth($wtoken){
    // 	if($wtoken == $this->api_token){
    // 		return true;
    // 	}else{
    // 		return false;
    // 	}
    // }

    // Adding Games!
	public function getGameList(){
		$client = new Client([
            'headers' => [ 
                'Authorization' => $this->api_token,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        ]);
        $response = $client->get($this->api_url.'/gameboy/game/halls');
        // $response = $client->get($this->api_url.'/gameboy/game/list/cq9');
        $game_list = json_decode((string)$response->getBody(), true);
        return $game_list;
  //       $data2 = array();
  //       foreach($game_list['data'] as $key){
  //         if($key['gametype'] == 'slot'){
  //         	$gametype = 1;
  //         }elseif($key['gametype'] == 'table'){
  //         	$gametype = 5;
  //     	  }elseif($key['gametype'] == 'fish'){
  //         	$gametype = 9;
  //         }else{
  //         	$gametype = 8; // arcade
  //         }
  //         $game = array(
  //             "game_type_id"=>$gametype,
  //             "provider_id"=> 30,
  //             "sub_provider_id"=> 54,
  //             "game_name"=> $key['gamename'],
  //             "game_code"=> $key["gamecode"],
  //             "icon"=> 'https://logopond.com/logos/a3134d028cc2ecd3b3f6cc4ff20947cd.png'
  //         );
  //         array_push($data2,$game);
  //       }
  //       DB::table('games')->insert($data2);
  //       return 'ok';
	}

	public function amountToFloat4DG($amount){
		// return str_pad($amount, 4, '0', STR_PAD_LEFT);
		// return substr(sprintf('%04d', $amount),0,4);
		return round($amount,4);
		// return str_pad($amount, 8, '0', STR_PAD_LEFT);
		// return sprintf("%08d", $amount);

		// $no_of_digit = 4;
		// $number = $amount;

		// $length = strlen((string)$number);
		// for($i = $length;$i<$no_of_digit;$i++)
		// {
		//     $number = '0'.$number;
		// }

		// return $number;
	}

	public function playerLogout(){
		$client = new Client([
            'headers' => [ 
                'Authorization' => $this->api_token,
                'Content-Type' => 'application/x-www-form-urlencoded',
            ]
        ]);
        $response = $client->post($this->$api_url.'/gameboy/player/logout', [
            'form_params' => [
                'account'=> 'player_id',
            ],
        ]);
        $logout = json_decode((string)$response->getBody(), true);
        return $logout;
	}

    public function CheckPlayer(Request $request, $account){
    	$header = $request->header('wtoken');
    	// Helper::saveLog('CQ9 Check Player', $this->provider_db_id, json_encode($request->all()), $header);
    	$check_wtoken = $this->checkAuth($header);
    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($request->all()), $mw_response);
			return $mw_response;
    	}
    	$check_string_user = ProviderHelper::checkIfHasUnderscore($account);
    	if(!$check_string_user){
			$data = ["data" => false,"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]];
		   return $data;
    	}
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details != null){
    		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
			$data = [
	    		"data" => true,"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
	    	];
    	}else{
    		$data = ["data" => false,"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]];
    	}
    	// Helper::saveLog('CQ9 Check Player', $this->provider_db_id, json_encode($request->all()), $data);
    	return $data;
    }

    public function CheckBalance(Request $request, $account){
    	// Helper::saveLog('CQ9 Balance Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');
    	$check_string_user = ProviderHelper::checkIfHasUnderscore($account);
    	if(!$check_string_user){
			$data = ["data" => false,"status" => ["code" => "1006","message" => 'Playerdoesnotexist not found',"datetime" => date(DATE_RFC3339)]];
		   return $data;
    	}
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details != null){
    		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
			$data = [
	    		"data" => [
	    			"balance" => $this->amountToFloat4DG($player_details->playerdetailsresponse->balance),
	    			"currency" => $client_details->default_currency,
	    		],
	    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
	    	];
    	}else{
    		$data = ["data" => null,"status" => ["code" => "1006","message" => 'Playerdoesnotexist not found',"datetime" => date(DATE_RFC3339)]];
    	}
    	// Helper::saveLog('CQ9 Balance Player', $this->provider_db_id, json_encode($request->all()), $data);
    	return $data;
    }

    public function CheckBalanceLotto(Request $request, $account){
    	// Helper::saveLog('CQ9 Balance Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');
    	$check_string_user = ProviderHelper::checkIfHasUnderscore($account);
    	if(!$check_string_user){
			$data = ["data" => false,"status" => ["code" => "1006","message" => 'Playerdoesnotexist not found',"datetime" => date(DATE_RFC3339)]];
		   return $data;
    	}
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details != null){
    		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
			$data = [
	    		"data" => [
	    			"balance" => $this->amountToFloat4DG($player_details->playerdetailsresponse->balance),
	    			"currency" => $client_details->default_currency,
	    		],
	    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
	    	];
    	}else{
    		$data = ["data" => null,"status" => ["code" => "1006","message" => 'Playerdoesnotexist not found',"datetime" => date(DATE_RFC3339)]];
    	}
    	// Helper::saveLog('CQ9 Balance Player', $this->provider_db_id, json_encode($request->all()), $data);
    	return $data;
    }


    public function playerBet(Request $request){
    	Helper::saveLog('CQ9 playerBet Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	$transaction_history = ["provider" =>[], "aggregator" => [], "client"=>[], "general_details"=>[]];
    	$header = $request->header('wtoken');
    	$provider_request = $request->all();
    	$transaction_history['provider']['request'] = json_encode($request->all());
    	$check_wtoken = $this->checkAuth($header);
    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	if(!$request->has('account') || !$request->has('eventTime') || !$request->has('gamehall') || !$request->has('gamecode') || !$request->has('roundid') || !$request->has('amount') || !$request->has('mtcode')){
    		$mw_response = ["data" => null,"status" => ["code" => "1003","message" => 'Parameter error.',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}
    	if(!$this->validRFCDade($request->eventTime)){
    		$mw_response = ["data" => null,"status" => ["code" => "1004","message" => 'Time Format error.',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}
    	$check_string_user = ProviderHelper::checkIfHasUnderscore($request->account);
    	if(!$check_string_user){
			$data = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]];
		   return $data;
    	}

		$account = $request->account;
    	$gamecode = $request->gamecode;
    	$gamehall = $request->gamehall;
    	$roundid = $request->roundid;
    	$amount = $request->amount;
    	$mtcode = $request->mtcode;
    	$eventime = $request->eventTime; // created
		$createtime = date(DATE_RFC3339);
		$action = 'bet';
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details == null){
    		$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
		if($player_details == 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
		}
    	if($amount < 0){
   			$mw_response = [
	    		"data" => [
	    			"balance" => ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance),
	    			"currency" => $client_details->default_currency,
	    		],
	    		"status" => ["code" => "1003","message" => 'Amount cannot be negative and must be positive!',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
   		}
   		if($player_details->playerdetailsresponse->balance < $amount){
   			$mw_response = [
	    		"data" => null,
	    		"status" => ["code" => "1005","message" => 'Insufficient Balance',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
   		}
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
		if($game_details == null){
			$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerTakeall', $this->provider_db_id, json_encode($request->all()), $mw_response);
			return $mw_response;
		}
		$game_ext_check = ProviderHelper::findGameExt($mtcode, 1, 'transaction_id');
		if($game_ext_check != 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "2009","message" => 'Transaction duplicate',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 T ALready Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}	

		try {
			$token_id = $client_details->token_id;
			$bet_amount = $amount;
			$pay_amount= 0;
			$method = 1;
			$win_or_lost = 5;
			$payout_reason = 'BET';
			$income = $amount;
			$provider_trans_id = $mtcode;
			$game_transaction_type = 1;
			$game_id = $game_details->game_id;
	
			$gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_id, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $roundid);
			$game_transextension = ProviderHelper::createGameTransExtV2($gamerecord,$provider_trans_id, $roundid, $amount, $game_transaction_type);
			$transaction_history['aggregator']['game_trans_id'] = $gamerecord;
			$transaction_history['aggregator']['game_trans_ext_id'] = $game_transextension;
		    $client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$gamerecord, 'debit');

		    if($client_response != 'false'){
		    	$general_details = [
					"provider" => [
						"createtime" => $createtime,  // The Transaction Created!
						"endtime" => date(DATE_RFC3339),
						"eventtime" => $eventime,
						"action" => $action
					],
					"client" => [
						"before_balance" => ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance),
				    	"after_balance"=> ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
				    	"player_prefixed"=> $account,
				    	"player_id"=> $user_id
					]
				];
				$mw_response = [
		    		"data" => [
		    			"balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
		    	];

		    	ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);

			}else{
				$mw_response = ["data" => [],"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				Helper::saveLog('CQ9 playerBet Failed', $this->provider_db_id, json_encode($request->all()), $mw_response);
			}
			return $mw_response;
		} catch (\Exception $e) {
			$mw_response = ["data" => [],"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			$transaction_history['general_details']['error'] = $e->getMessage();
			Helper::saveLog('CQ9 playerBet Failed', $this->provider_db_id, json_encode($transaction_history), $e->getMessage());
			return $mw_response;
		}
    }

    public function playrEndround(Request $request){
    	Helper::saveLog('CQ9 playrEndround Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	$provider_request = $request->all();
    	// $check_wtoken = $this->checkAuth($header);
   //  	if(!$check_wtoken){
   //  		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			// Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			// return $mw_response;
   //  	}
    	if(!$request->has('account') || !$request->has('createTime') || !$request->has('gamehall') || !$request->has('gamecode') || !$request->has('roundid') || !$request->has('data')){
    		$mw_response = ["data" => null,"status" => ["code" => "1003","message" => 'Parameter error.',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}
    	if(!$this->validRFCDade($request->createTime)){
    		$mw_response = ["data" => null,"status" => ["code" => "1004","message" => 'Time Format error.',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}

		$data_details = ProviderHelper::rawToObj($request->data, true);
    	$account = $request->account;
    	$gamecode = $request->gamecode;
    	$gamehall = $request->gamehall;
    	$roundid = $request->roundid;
    	$eventime = $request->createTime; // created
		$createtime = date(DATE_RFC3339);
		$action = 'endroud';

 	    $check_string_user = ProviderHelper::checkIfHasUnderscore($account);
    	if(!$check_string_user){
			$data = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]];
		   return $data;
    	}
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details == null){
    		$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}

		$total_amount = array();
    	foreach($data_details as $data){
    		if($data->amount < 0){
	   			$mw_response = [
		    		"data" => null,
		    		"status" => ["code" => "1003","message" => 'Amount cannot be negative!',"datetime" => date(DATE_RFC3339)]
		    	];
				return $mw_response;
	   		}
    		array_push($total_amount, $data->amount);
    	}	


		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
		if($game_details == null){
			$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerTakeall', $this->provider_db_id, json_encode($request->all()), $mw_response);
			return $mw_response;
		}
		$game_ext_check = ProviderHelper::findGameExt($roundid, 1, 'round_id');
		if($game_ext_check == 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1014","message" => 'Transaction record not found',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playrEndround ALready Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}
		$game_ext_exist = ProviderHelper::findGameExt($roundid, 2, 'round_id');
		if($game_ext_exist != 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "2009","message" => 'Duplicae Transaction',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playrEndround ALready Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}		
		$game_transaction = ProviderHelper::findGameTransaction($game_ext_check->game_trans_id, 'game_transaction');
		try {
	    
    		$total_amount = array_sum($total_amount);
	    	$token_id = $client_details->token_id;
			$pay_amount = $game_transaction->pay_amount + $total_amount;
			$payout_reason = 'ENDROUND WIN';
			$income = $game_transaction->bet_amount - $pay_amount;
			$provider_trans_id = $data->mtcode;
			if($total_amount > 0){
				$game_transaction_type = 2;
				$entry_id = 2;
				$win_or_lost = 1;
			}else{
				$game_transaction_type = 1;
				$entry_id = 1;
				$win_or_lost = 0;
			}

			ProviderHelper::updateBetTransaction($game_transaction->round_id, $pay_amount, $income, $win_or_lost, $entry_id);
			$game_transextension = ProviderHelper::createGameTransExtV2($game_transaction->game_trans_id,$provider_trans_id, $roundid, $total_amount, $game_transaction_type);

		    $client_response = ClientRequestHelper::fundTransfer($client_details,$data->amount,$game_details->game_code,$game_details->game_name,$game_transextension,$game_transaction->game_trans_id, 'credit');

		    if($client_response != 'false'){
		    	$general_details = [
					"provider" => [
						"createtime" => $createtime,  // The Transaction Created!
						"endtime" => date(DATE_RFC3339),
						"eventtime" => $eventime,
						"action" => $action
					],
					"client" => [
						"before_balance" => ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance),
				    	"after_balance"=> ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
				    	"player_prefixed"=> $account,
				    	"player_id"=> $user_id
					]
				];
				$mw_response = [
		    		"data" => [
		    			"balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
		    	];
			    ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response, $general_details);
			}else{
				$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				Helper::saveLog('CQ9 playrEndround Failed', $this->provider_db_id, json_encode($request->all()), $mw_response);
			}
			return $mw_response;
		} catch (\Exception $e) {
			$mw_response = [
	    		"data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]
	    	];
			Helper::saveLog('CQ9 playrEndround Failed', $this->provider_db_id, json_encode($request->all()), $e->getMessage());
			return $mw_response;
		}
    }

    public function playerCredit(Request $request){
    	Helper::saveLog('CQ9 playerCredit Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	$provider_request = $request->all();
    	$check_wtoken = $this->checkAuth($header);
   //  	if(!$check_wtoken){
   //  		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			// Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			// return $mw_response;
   //  	}
    	if(!$request->has('account') || !$request->has('eventTime') || !$request->has('gamehall') || !$request->has('gamecode')  || !$request->has('roundid') || !$request->has('amount') || !$request->has('mtcode')){
    		$mw_response = ["data" => null,"status" => ["code" => "1003","message" => 'Parameter error.',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}
    	if(!$this->validRFCDade($request->eventTime)){
    		$mw_response = ["data" => null,"status" => ["code" => "1004","message" => 'Time Format error.',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}

	  	$account = $request->account;
    	$gamecode = $request->gamecode;
    	$gamehall = $request->gamehall;
    	$roundid = $request->roundid;
    	$amount = $request->amount;
    	$mtcode = $request->mtcode;
    	$eventime = $request->eventTime; // created
		$createtime = date(DATE_RFC3339);
		$action = 'credit';

		$check_string_user = ProviderHelper::checkIfHasUnderscore($account);
    	if(!$check_string_user){
			$data = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 T ALready Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
		   return $data;
		}
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details == null){
    		$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
		if($player_details == 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
		}
    	if($amount < 0){
   			$mw_response = [
	    		"data" => null,
	    		"status" => ["code" => "1003","message" => 'Amount cannot be negative!',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
   		}
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
		if($game_details == null){
			$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerTakeall', $this->provider_db_id, json_encode($request->all()), $mw_response);
			return $mw_response;
		}
		$game_ext_check = ProviderHelper::findGameExt($roundid, 1, 'round_id');
		if($game_ext_check == 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1014","message" => 'Transaction record not found',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerCredit ALready Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}	
		$game_ext_check_refunded = ProviderHelper::findGameExt($roundid, 3, 'round_id');
		if($game_ext_check_refunded != 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1015","message" => 'Transaction record (bet) is already refunded',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerCredit ALready Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}	

		$game_transaction = ProviderHelper::findGameTransaction($game_ext_check->game_trans_id, 'game_transaction');
		// return 'yaw sa';
		try {
			$token_id = $client_details->token_id;
			$bet_amount = $amount;
			$pay_amount= $game_transaction->pay_amount + $amount;
			$win_or_lost = $game_ext_check->win;
			$entry_id = $game_ext_check->entry_id;
			$payout_reason = 'Credit Correction';
			$income =  $game_transaction->bet_amount - $pay_amount;
			$provider_trans_id = $mtcode;
			$game_transaction_type = 2;
			
		
			$game_transextension = ProviderHelper::createGameTransExtV2($game_ext_check->game_trans_id,$provider_trans_id, $roundid, $amount, 3);
			ProviderHelper::updateBetTransaction($game_transaction->round_id, $pay_amount, $income, $win_or_lost, $entry_id);

		    $client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$game_ext_check->game_trans_id, 'credit', true);

		    if($client_response != 'false'){
		    	$general_details = [
					"provider" => [
						"createtime" => $createtime,  // The Transaction Created!
						"endtime" => date(DATE_RFC3339),
						"eventtime" => $eventime,
						"action" => $action
					],
					"client" => [
						"before_balance" => ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance),
				    	"after_balance"=> ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
				    	"player_prefixed"=> $account,
				    	"player_id"=> $user_id
					]
				];
		    	$mw_response = [
		    		"data" => [
		    			"balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
		    	];

				ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);

			}else{
				$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				Helper::saveLog('CQ9 playerCredit Failed', $this->provider_db_id, json_encode($request->all()), $mw_response);
			}
			return $mw_response;
		} catch (\Exception $e) {
			$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerCredit Failed', $this->provider_db_id, json_encode($request->all()), $e->getMessage());
			return $mw_response;
		}
    }

    public function playerDebit(Request $request){
    	Helper::saveLog('CQ9 playerDebit Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	$provider_request = $request->all();
    	$check_wtoken = $this->checkAuth($header);
   //  	if(!$check_wtoken){
   //  		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			// Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			// return $mw_response;
   //  	}
    	if(!$request->has('account') || !$request->has('eventTime') || !$request->has('gamehall') || !$request->has('gamecode') || !$request->has('roundid') || !$request->has('amount') || !$request->has('mtcode')){
    		$mw_response = ["data" => null,"status" => ["code" => "1003","message" => 'Parameter error.',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}
    	if(!$this->validRFCDade($request->eventTime)){
    		$mw_response = ["data" => null,"status" => ["code" => "1004","message" => 'Time Format error.',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}

    	$account = $request->account;
    	$gamecode = $request->gamecode;
    	$gamehall = $request->gamehall;
    	$roundid = $request->roundid;
    	$amount = $request->amount;
    	$mtcode = $request->mtcode;
    	$eventime = $request->eventTime; // created
		$createtime = date(DATE_RFC3339);
		$action = 'debit';

		$check_string_user = ProviderHelper::checkIfHasUnderscore($account);
    	if(!$check_string_user){
			$data = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 T ALready Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
		   return $data;
		}
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details == null){
    		$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
		if($player_details == 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
		}
    	if($amount < 0){
   			$mw_response = [
	    		"data" => null,"status" => ["code" => "1003","message" => 'Amount cannot be negative!',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
   		}
   		if($player_details->playerdetailsresponse->balance < $amount){
   			$mw_response = [
	    		"data" => null,"status" => ["code" => "1005","message" => 'Insufficient Balance',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
   		}
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
		if($game_details == null){
			$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerTakeall', $this->provider_db_id, json_encode($request->all()), $mw_response);
			return $mw_response;
		}
		$game_ext_check = ProviderHelper::findGameExt($roundid, 1, 'round_id');
		if($game_ext_check == 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1014","message" => 'Transaction record not found',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerDebit ALready Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}	
		$game_ext_check_refunded = ProviderHelper::findGameExt($roundid, 3, 'round_id');
		if($game_ext_check_refunded != 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1015","message" => 'Transaction record is already refunded',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerDebit ALready Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}
		   
		$game_transaction = ProviderHelper::findGameTransaction($game_ext_check->game_trans_id, 'game_transaction');
		try {
			$token_id = $client_details->token_id;
			$bet_amount = $amount;
			$pay_amount= $game_transaction->pay_amount - $amount;
			$win_or_lost = $game_ext_check->win;
			$entry_id = $game_ext_check->entry_id;
			$payout_reason = 'Debit Correction';
			$income = $game_transaction->bet_amount - $pay_amount;
			$provider_trans_id = $mtcode;
			$game_transaction_type = 2;


			$game_transextension = ProviderHelper::createGameTransExtV2($game_ext_check->game_trans_id,$provider_trans_id, $roundid, $amount, 3);
			ProviderHelper::updateBetTransaction($game_transaction->round_id, $pay_amount, $income, $win_or_lost, $entry_id);

		    $client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$game_ext_check->game_trans_id, 'debit', true);

		    if($client_response != 'false'){
		    	$general_details = [
					"provider" => [
						"createtime" => $createtime,  // The Transaction Created!
						"endtime" => date(DATE_RFC3339),
						"eventtime" => $eventime,
						"action" => $action
					],
					"client" => [
						"before_balance" => ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance),
				    	"after_balance"=> ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
				    	"player_prefixed"=> $account,
				    	"player_id"=> $user_id
					]
				];
		    	$mw_response = [
		    		"data" => [
		    			"balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
		    	];

		    	ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);

			}else{
				$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				Helper::saveLog('CQ9 playerDebit Failed', $this->provider_db_id, json_encode($request->all()), $mw_response);
			}
			return $mw_response;
		} catch (\Exception $e) {
			$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerDebit Failed', $this->provider_db_id, json_encode($request->all()), $e->getMessage());
			return $mw_response;
		}
    }

    public function playerRollout(Request $request){
    	Helper::saveLog('CQ9 playerRollout Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	$provider_request = $request->all();
    	$check_wtoken = $this->checkAuth($header);
    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	if(!$request->has('account') || !$request->has('eventTime') || !$request->has('gamehall') || !$request->has('gamecode') || !$request->has('roundid') || !$request->has('amount') || !$request->has('mtcode')){
    		$mw_response = ["data" => null,"status" => ["code" => "1003","message" => 'Parameter error.',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}
    	if(!$this->validRFCDade($request->eventTime)){
    		$mw_response = ["data" => null,"status" => ["code" => "1004","message" => 'Time Format error.',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}
    	$account = $request->account;
    	$gamecode = $request->gamecode;
    	$gamehall = $request->gamehall;
    	$roundid = $request->roundid;
    	$amount = $request->amount;
    	$mtcode = $request->mtcode;
    	$eventime = $request->eventTime; // created
    	$createtime = date(DATE_RFC3339);
    	$action = 'rollout';

    	$check_string_user = ProviderHelper::checkIfHasUnderscore($account);
    	if(!$check_string_user){
			$data = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 T ALready Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
		   return $data;
		}
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details == null){
    		$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 Rollout', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
		if($player_details == 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 Rollout', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}
    	if($amount < 0){
   			$mw_response = [
	    		"data" => null,"status" => ["code" => "1003","message" => 'Amount cannot be negative!',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 Rollout', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
   		}
   		if($player_details->playerdetailsresponse->balance < $amount){
   			$mw_response = [
	    		"data" => null,"status" => ["code" => "1005","message" => 'Insufficient Balance',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 Rollout', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
   		}
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
		if($game_details == null){
			$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerTakeall', $this->provider_db_id, json_encode($request->all()), $mw_response);
			return $mw_response;
		}
		$game_ext_check = ProviderHelper::findGameExt($mtcode, 1, 'transaction_id');
		if($game_ext_check != 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "2009","message" => 'Transaction duplicate',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Rollout', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}	
		try {
			$token_id = $client_details->token_id;
			$bet_amount = $amount;
			$pay_amount= 0;
			$method = 1;
			$win_or_lost = 5;
			$payout_reason = 'BET';
			$income = $amount;
			$provider_trans_id = $mtcode;
			$game_transaction_type = 1;
			$game_id = $game_details->game_id;

			$gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_id, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $roundid);
			$game_transextension = ProviderHelper::createGameTransExtV2($gamerecord,$provider_trans_id, $roundid, $amount, $game_transaction_type);

		    $client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$gamerecord, 'debit');

			if($client_response != 'false'){
				$general_details = [
					"provider" => [
						"createtime" => $createtime,  // The Transaction Created!
						"endtime" => date(DATE_RFC3339),
						"eventtime" => $eventime,
						"action" => $action
					],
		    		"client" => [
		    			"before_balance" => ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance),
				    	"after_balance"=> ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
				    	"player_prefixed"=> $account,
				    	"player_id"=> $user_id
		    		]
		    	];
				$mw_response = [
		    		"data" => [
		    			"balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
		    	];
				
		    	ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);
			}else{
				$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				Helper::saveLog('CQ9 playerRollout Failed', $this->provider_db_id, json_encode($request->all()), $mw_response);
			}
			return $mw_response;
		} catch (\Exception $e) {
			$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerRollout Failed', $this->provider_db_id, json_encode($request->all()), $e->getMessage());
			return $mw_response;
		}
    }

    public function playerTakeall(Request $request){
    	Helper::saveLog('CQ9 playerTakeall Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	$provider_request = $request->all();
    	$check_wtoken = $this->checkAuth($header);
    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	if(!$request->has('account') || !$request->has('eventTime') || !$request->has('gamehall') || !$request->has('gamecode') || !$request->has('roundid') || !$request->has('mtcode')){
    		$mw_response = ["data" => null,"status" => ["code" => "1003","message" => 'Parameter error.',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}
    	if(!$this->validRFCDade($request->eventTime)){
    		$mw_response = ["data" => null,"status" => ["code" => "1004","message" => 'Time Format error.',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}

    	$account = $request->account;
    	$gamecode = $request->gamecode;
    	$gamehall = $request->gamehall;
    	$roundid = $request->roundid;
    	$mtcode = $request->mtcode;
    	$eventime = $request->eventTime; // created
		$createtime = date(DATE_RFC3339);
		$action = 'takeall';

		$check_string_user = ProviderHelper::checkIfHasUnderscore($account);
    	if(!$check_string_user){
			$data = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Takeall', $this->provider_db_id, json_encode($provider_request), $mw_response);
		   return $data;
		}
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details == null){
    		$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}
    	$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
    	if($player_details == 'false'){
    		$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}
    	$amount = $player_details->playerdetailsresponse->balance;
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
		if($game_details == null){
			$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerTakeall', $this->provider_db_id, json_encode($request->all()), $mw_response);
			return $mw_response;
		}
		$game_ext_check = ProviderHelper::findGameExt($mtcode, 1, 'transaction_id');
		if($game_ext_check != 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "2009","message" => 'Transaction duplicate',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 T ALready Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}	
		try {
			$token_id = $client_details->token_id;
			$bet_amount = $amount;
			$pay_amount= 0;
			$method = 1;
			$win_or_lost = 5;
			$payout_reason = 'TakeAll Players Money';
			$income = $bet_amount - $pay_amount;
			$provider_trans_id = $mtcode;
			$game_transaction_type = 1;
			$game_id = $game_details->game_id;
			
			$gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_id, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $roundid);

			$game_transextension = ProviderHelper::createGameTransExtV2($gamerecord,$provider_trans_id, $roundid, $amount, $game_transaction_type);

			$client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$gamerecord, 'debit');

		    if($client_response != 'false'){
		    	$general_details = [
					"provider" => [
						"createtime" => $createtime,  // The Transaction Created!
						"endtime" => date(DATE_RFC3339),
						"eventtime" => $eventime,
						"action" => $action
					],
					"client" => [
						"before_balance" => ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance),
				    	"after_balance"=> ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
				    	"player_prefixed"=> $account,
				    	"player_id"=> $user_id
					]
				];
		    	$mw_response = [
	    		"data" => [
	    			"balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
	    			"currency" => $client_details->default_currency,
	    		],
	    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
	    	];

			ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);

			}else{
				$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				Helper::saveLog('CQ9 playerTakeall Failed', $this->provider_db_id, json_encode($request->all()), $mw_response);
			}
			return $mw_response;
		} catch (\Exception $e) {
			$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerTakeall Failed', $this->provider_db_id, json_encode($request->all()), $e->getMessage());
			return $mw_response;
		}
    }

    public function playerRollin(Request $request){
    	Helper::saveLog('CQ9 playerRollin Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	$provider_request = $request->all();
    	$check_wtoken = $this->checkAuth($header);
    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	if(!$request->has('account') || !$request->has('eventTime') || !$request->has('createTime') || !$request->has('gamehall') || !$request->has('gamecode') || !$request->has('roundid') || !$request->has('bet') || !$request->has('win') || !$request->has('amount') || !$request->has('mtcode') || !$request->has('gametype')){
    		$mw_response = ["data" => null,"status" => ["code" => "1003","message" => 'Parameter error.',"datetime" => date(DATE_RFC3339)]];
			return $mw_response;
    	}
    	if($request->gametype == 'table'){
    		if(!$request->has('rake')){
    			$mw_response = ["data" => null,"status" => ["code" => "1003","message" => 'Parameter error.',"datetime" => date(DATE_RFC3339)]];
				return $mw_response;
    		}
    	}
    	if(!$this->validRFCDade($request->eventTime) || !$this->validRFCDade($request->createTime)){
    		$mw_response = ["data" => null,"status" => ["code" => "1004","message" => 'Time Format error.',"datetime" => date(DATE_RFC3339)]];
			return $mw_response;
    	}
    	$account = $request->account;
    	$gamecode = $request->gamecode;
    	$gamehall = $request->gamehall;
    	$roundid = $request->roundid;
    	$amount = $request->amount;
    	$bet = $request->bet;
    	$win = $request->win;
    	$mtcode = $request->mtcode;
    	$eventime = $request->eventTime; // created
    	// $rake = $request->rake; // created only for table gametype
		$event_creatTime = $request->createTime;
		$gametype = $request->gametype; // fish or table
		$createtime = date(DATE_RFC3339);
		$action = 'rollin';

		$check_string_user = ProviderHelper::checkIfHasUnderscore($account);
    	if(!$check_string_user){
			$data = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 T ALready Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
		   return $data;
		}
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details == null){
    		$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
		if($player_details == 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
		}
    	if($amount < 0){
   			$mw_response = [
	    		"data" => [
	    			"balance" => ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance),
	    			"currency" => $client_details->default_currency,
	    		],
	    		"status" => ["code" => "1003","message" => 'Amount cannot be negative!',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
   		}
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
		if($game_details == null){
			$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerTakeall', $this->provider_db_id, json_encode($request->all()), $mw_response);
			return $mw_response;
		}
		$game_ext_check = ProviderHelper::findGameExt($roundid, 1, 'round_id');
		if($game_ext_check == 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1014","message" => 'Transaction record not found',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerRollin ALready Exist', $this->provider_db_id, $provider_request, $mw_response);
			return $mw_response;
		}	
		$check_duplicate = ProviderHelper::findGameExt($mtcode, 2, 'transaction_id');
		if($check_duplicate != 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "2009","message" => 'Transaction duplicate',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 T ALready Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}	
		$game_transaction = ProviderHelper::findGameTransaction($game_ext_check->game_trans_id, 'game_transaction');
		try {
	    	$token_id = $client_details->token_id;
			$pay_amount =  $amount;
			$payout_reason = 'Roullout Fish Game';
			$income = $game_transaction->bet_amount - $pay_amount;
			$provider_trans_id = $mtcode;
			$game_transaction_type = 2;
			// if($amount > $game_transaction->bet_amount){
				$entry_id = 2;
				$win_or_lost = 1;
			// }else{
			// 	$entry_id = 1;
			// 	$win_or_lost = 0;
			// }
		
		    ProviderHelper::updateBetTransaction($game_transaction->round_id, $pay_amount, $income, $win_or_lost, $entry_id);
			$game_transextension = ProviderHelper::createGameTransExtV2($game_ext_check->game_trans_id,$provider_trans_id, $roundid, $amount, $game_transaction_type);

		    $client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$game_ext_check->game_trans_id, 'credit');

		    if($client_response != 'false'){
		    	$general_details = [
					"provider" => [
						"createtime" => $createtime,  // The Transaction Created!
						"endtime" => date(DATE_RFC3339),
						"eventtime" => $eventime,
						"action" => $action
					],
					"client" => [
						"before_balance" => ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance),
				    	"after_balance"=> ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
				    	"player_prefixed"=> $account,
				    	"player_id"=> $user_id
					]
				];
		    	$mw_response = [
		    		"data" => [
	    				"balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
		    	];
			
				ProviderHelper::updatecreateGameTransExt($game_ext_check->game_trans_id, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);

			}else{
				$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				Helper::saveLog('CQ9 playerRollin Failed', $this->provider_db_id, json_encode($request->all()), $mw_response);
			}
		    
			return $mw_response;
		} catch (\Exception $e) {
			$mw_response = [
	    		"data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]
	    	];
			Helper::saveLog('CQ9 playerRollin Failed', $this->provider_db_id, json_encode($request->all()), $e->getMessage());
			return $mw_response;
		}
    }

    /**
     * Player Bunos Not Implemented on both party!
     * @param  Request $request [description]
     * 
     */
    public function playerBonus(Request $request){
    	Helper::saveLog('CQ9 playerBonus Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	Helper::saveLog('CQ9 playerBonus Player', $this->provider_db_id, json_encode(file_get_contents("php://input")), 'ENDPOINT 2');
    }

    /**
     * Bonus/Free Spin (GAME CODE TOBE STATIC NEED REVISION)
     * @author 's note <[<No Round ID for this transaction>]>
     * 
     */
    public function playerPayoff(Request $request){
    	Helper::saveLog('CQ9 playerPayoff Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	$provider_request = $request->all();
		$check_wtoken = $this->checkAuth($header);
    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	if(!$request->has('account') || !$request->has('eventTime') || !$request->has('roundid') || !$request->has('amount') || !$request->has('mtcode')){
    		$mw_response = ["data" => null,"status" => ["code" => "1003","message" => 'Parameter error.',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}
    	if(!$this->validRFCDade($request->eventTime)){
    		$mw_response = ["data" => null,"status" => ["code" => "1004","message" => 'Time Format error.',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}

    	$account = $request->account;
    	$gamecode = 'AB1'; // $request->gamecode;
    	// $gamehall = $request->gamehall;
    	$roundid = $request->mtcode; // $request->roundid;
    	$amount = $request->amount;
    	$mtcode = $request->mtcode;
    	$eventime = $request->eventTime; // created
		$createtime = date(DATE_RFC3339);
		$action = 'payoff';

    	$check_string_user = ProviderHelper::checkIfHasUnderscore($request->account);
    	if(!$check_string_user){
			$data = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]];
		    return $data;
		}
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details == null){
    		$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}
   		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
   		if($player_details == 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
		}
    	if($amount < 0){
   			$mw_response = [
	    		"data" => null,
	    		//  [
	    		// 	"balance" => ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance),
	    		// 	"currency" => $client_details->default_currency,
	    		// ],
	    		"status" => ["code" => "1003","message" => 'Amount cannot be negative!',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
   		}
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
		if($game_details == null){
			$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerTakeall', $this->provider_db_id, json_encode($request->all()), $mw_response);
			return $mw_response;
		}
		$game_ext_check = ProviderHelper::findGameExt($mtcode, 1, 'transaction_id');
		if($game_ext_check != 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "2009","message" => 'Transaction duplicate',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 T ALready Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}	

		try {
			$token_id = $client_details->token_id;
			$bet_amount = 0;
			$pay_amount= $amount;
			$method = 2;
			$win_or_lost = 1;
			$payout_reason = 'PayOff Promotion';
			$income = 0 - $pay_amount;
			$provider_trans_id = $mtcode;
			$game_transaction_type = 1;
			$game_id = $game_details->game_id;
			
			$gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_id, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $roundid);
			$game_transextension = ProviderHelper::createGameTransExtV2($gamerecord,$provider_trans_id, $roundid, $amount, $game_transaction_type);
		    $client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$gamerecord, 'credit');

		    if($client_response != 'false'){
		    	$general_details = [
					"provider" => [
						"createtime" => $createtime,  // The Transaction Created!
						"endtime" => date(DATE_RFC3339),
						"eventtime" => $eventime,
						"action" => $action
					],
					"client" => [
						"before_balance" => ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance),
				    	"after_balance"=> ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
				    	"player_prefixed"=> $account,
				    	"player_id"=> $user_id
					]
				];
				$mw_response = [
		    		"data" => [
		    			"balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
		    	];
				
				ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);

			}else{
				$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				Helper::saveLog('CQ9 playerPayoff Failed', $this->provider_db_id, json_encode($request->all()), $mw_response, $general_details);
			}
			return $mw_response;
		} catch (\Exception $e) {
			$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerPayoff Failed', $this->provider_db_id, json_encode($request->all()), $e->getMessage());
			return $mw_response;
		}
    }

    public function playerRefund(Request $request){
    	Helper::saveLog('CQ9 playrEndround Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	$provider_request = $request->all();
    	$check_wtoken = $this->checkAuth($header);
    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
   		if(!$request->has('mtcode')){
    		$mw_response = ["data" => null,"status" => ["code" => "1003","message" => 'Parameter error.',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}
    	$mtcode = $request->mtcode;
    	if(!$this->validRFCDade($request->eventTime)){
    		$mw_response = ["data" => null,"status" => ["code" => "1004","message" => 'Time Format error.',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}

		$find_mtcode = $this->findTranPID($mtcode);
  		if($find_mtcode == 'false'){
  			$mw_response = ["data" => null,"status" => ["code" => "1014","message" => 'Transaction record not found',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerRefund ALready Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
  		}
  		$game_ext_check = ProviderHelper::findGameExt($mtcode, 3, 'transaction_id');
		if($game_ext_check != 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1015","message" => 'The mtcode record is already refunded',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Duplicate Refund = '.$mtcode, $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}	

  		$game_transaction = ProviderHelper::findGameTransaction($find_mtcode->game_trans_id, 'game_transaction');
  		$game_details = ProviderHelper::findGameID($game_transaction->game_id);
  		$user_id = Providerhelper::findTokenID($game_transaction->token_id)->player_id;
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details == null){
    		$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}

		try {
			$amount = $find_mtcode->amount;
			if($find_mtcode->game_transaction_type == 1){ // BET SHOULD BE REFUNDED AS CREDIT
				$pay_amount = 0;
				$income = $game_transaction->bet_amount - $amount;
				$transaction_type = 'credit';
			}else if($find_mtcode->game_transaction_type == 2){ // WIN SHOULD BE REFUNDED AS DEBIT
				$pay_amount = $game_transaction->pay_amount - $amount;
				$income = $game_transaction->bet_amount - $pay_amount;
				$transaction_type = 'debit';
			}
			$win_or_lost = 4;
			$entry_id = $game_transaction->entry_id;
	    	$token_id = $client_details->token_id;
			$payout_reason = 'REFUND ROUND';
			$provider_trans_id = $mtcode;
			$game_transaction_type = 3;
			
			$game_transextension = ProviderHelper::createGameTransExtV2($find_mtcode->game_trans_id,$provider_trans_id, $roundid, $amount, $game_transaction_type);
		    ProviderHelper::updateBetTransaction($game_transaction->round_id, $pay_amount, $income, $win_or_lost, $entry_id);
		    $client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$find_mtcode->game_trans_id, $transaction_type, true);

	   		if($client_response != 'false'){
				$mw_response = [
		    		"data" => [
		    			"balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
		    	];

		    	ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response);

			}else{
				$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				Helper::saveLog('CQ9 playerRefund Failed', $this->provider_db_id, json_encode($request->all()), $mw_response);
			}
			return $mw_response;
		} catch (\Exception $e) {
			$mw_response = [
	    		"data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]
	    	];
			Helper::saveLog('CQ9 playerRefund Failed', $this->provider_db_id, json_encode($request->all()), $e->getMessage());
			return $mw_response;
		}
    }

    public function playerRecord(Request $request, $mtcode){
    	Helper::saveLog('CQ9 playerRecord Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	$transaction_record = $this->findTranPID($mtcode);
    	if($transaction_record != 'false'){
    		$game_ext_details = $transaction_record->general_details;
	    	$general_details = json_decode($game_ext_details);
	    	$client_details = Providerhelper::getClientDetails('player_id', $general_details->client->player_id);
			$record = [
		    		"data"=>[
		    		"_id" => $transaction_record->game_trans_ext_id,
				    "action" => $general_details->provider->action,
				    "target" => [
				      "account" => $general_details->client->player_prefixed
				    ],
				    "status" => [
				      "createtime" => $general_details->provider->createtime,
				      "endtime" => $general_details->provider->endtime,
				      "status" => "success",
				      "message" => "success"
				    ],
				    "before" => $general_details->client->before_balance,
				    "balance" => $general_details->client->after_balance,
				    "currency" => $client_details->default_currency,
				    "event" => [
				      [
				        "mtcode" => $mtcode,
				        "amount" => $transaction_record->amount,
				        "eventtime" => $general_details->provider->eventtime
				      ]
				    ]
				  ],
				  "status" => [
				    "code" => "0",
				    "message" => "Success",
				    "datetime" => date(DATE_RFC3339)
				  ]
	    	];
    	}else{
    		$record = [
	    		"data"=>null,
				"status" => [
				    "code" => "1014",
				    "message" => "record not found",
				    "datetime" => date(DATE_RFC3339)
				]
	    	];
    	}
    	return $record;
    }
    
    public function playerBets(Request $request){
    	Helper::saveLog('CQ9 playerBets Player', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	// $provider_request = $request->all();
    	// $data_details = ProviderHelper::rawToObj($request->data, true);
    	$provider_request = json_decode(file_get_contents("php://input"));
    	$check_wtoken = $this->checkAuth($header);
    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	if(!$this->validRFCDade($provider_request->createTime)){
    		$mw_response = ["data" => null,"status" => ["code" => "1004","message" => 'Time Format error.',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}

    	$data_details = $provider_request->data;
    	$account = $provider_request->account;
    	$gamecode = $provider_request->gamecode;
    	$gamehall = $provider_request->gamehall;
    	$eventime = $provider_request->createTime; // created
		$createtime = date(DATE_RFC3339);
		$action = 'bets';

		$check_string_user = ProviderHelper::checkIfHasUnderscore($request->account);
    	if(!$check_string_user){
			$data = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]];
		   return $data;
		}
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details == null){
    		$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
		if($player_details == 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
		}
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
		if($game_details == null){
			$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerTakeall', $this->provider_db_id, json_encode($request->all()), $mw_response);
			return $mw_response;
		}
		try {
			$total_amount = array();
	    	foreach($data_details as $data){
	    		if($data->amount < 0){
		   			$mw_response = [
			    		"data" => [
				    			"balance" => ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance),
				    			"currency" => $client_details->default_currency,
			    		],
			    		"status" => ["code" => "1003","message" => 'Amount cannot be negative!',"datetime" => date(DATE_RFC3339)]
			    	];
					return $mw_response;
		   		}
		   		$game_ext_check = ProviderHelper::findGameExt($data->mtcode, 1, 'transaction_id');
				if($game_ext_check != 'false'){
					$mw_response = ["data" => [],"status" => ["code" => "2009","message" => 'Transaction duplicate',"datetime" => date(DATE_RFC3339)]];
					Helper::saveLog('CQ9 T ALready Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
					return $mw_response;
				}	
	    		array_push($total_amount, $data->amount);
	    	}	
			$total_amount = array_sum($total_amount);
	    	if($player_details->playerdetailsresponse->balance < $total_amount){
	   			$mw_response = [
		    		"data" => null,"status" => ["code" => "1005","message" => 'Insufficient Balance',"datetime" => date(DATE_RFC3339)]
		    	];
				return $mw_response;
	   		}

    	  	$token_id = $client_details->token_id;
			$pay_amount= 0;
			$method = 1;
			$win_or_lost = 5;
			$game_transaction_type = 1;
			$game_id = $game_details->game_id;

	    	foreach($data_details as $data){
			$bet_amount = $data->amount;
			$income = $data->amount;
			$payout_reason = 'BETS';
			$provider_trans_id = $data->mtcode;
			$roundid =  $data->roundid;

	    		$gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_id, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $roundid);
	    		$game_transextension = ProviderHelper::createGameTransExtV2($gamerecord,$provider_trans_id, $roundid,$data->amount, $game_transaction_type);
			    $client_response = ClientRequestHelper::fundTransfer($client_details,$data->amount,$game_details->game_code,$game_details->game_name,$game_transextension,$gamerecord, 'debit');


			    if($client_response != 'false'){
			    	$general_details = [
						"provider" => [
							"createtime" => $createtime,  // The Transaction Created!
							"endtime" => date(DATE_RFC3339),
							"eventtime" => $eventime,
							"action" => $action
						],
						"client" => [
							"before_balance" => ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance),
					    	"after_balance"=> ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
					    	"player_prefixed"=> $account,
					    	"player_id"=> $user_id
						]
					];
					$mw_response = [
			    		"data" => [
			    			"balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
			    			"currency" => $client_details->default_currency,
			    		],
			    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
			    	];
			 	   
			 	    ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);

				}else{
					$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
					Helper::saveLog('CQ9 playerBets Failed', $this->provider_db_id, json_encode($request->all()), $mw_response);
				}
	    	}	
			$mw_response = [ // LAST LOOP RESPONSE
	    		"data" => [
	    			"balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
	    			"currency" => $client_details->default_currency,
	    		],
	    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;

		} catch (\Exception $e) {
			$mw_response = [
	    		"data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]
	    	];
			Helper::saveLog('CQ9 playerBets Failed', $this->provider_db_id, json_encode($request->all()), $e->getMessage());
			return $mw_response;
		}
    }

     public function playerRefunds(Request $request){
    	Helper::saveLog('CQ9 playerRefunds Player', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	// $provider_request = $request->all();
    	// $mtcodes = ProviderHelper::rawToObj($request->mtcode, true);
    	$provider_request = json_decode(file_get_contents("php://input"));
    	$mtcodes = $provider_request;


    	$check_wtoken = $this->checkAuth($header);
    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
  
    	foreach($mtcodes->mtcode as $mt){
    		$find_mtcode = $this->findTranPID($mt);
	  		if($find_mtcode == 'false'){
	  			$mw_response = ["data" => [],"status" => ["code" => "1014","message" => 'Transaction record not found',"datetime" => date(DATE_RFC3339)]];
				Helper::saveLog('CQ9 playerRefund ALready Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
				return $mw_response;
	  		}
	  		$game_ext_check = ProviderHelper::findGameExt($mt, 3, 'transaction_id');
			if($game_ext_check != 'false'){
				$mw_response = ["data" => [],"status" => ["code" => "2009","message" => 'Transaction duplicate',"datetime" => date(DATE_RFC3339)]];
				Helper::saveLog('CQ9 Duplicate Refund = '.$mt, $this->provider_db_id, json_encode($provider_request), $mw_response);
				return $mw_response;
			}	
    	}

    	foreach($mtcodes->mtcode as $mt){
    		$find_mtcode = $this->findTranPID($mt);
    		$game_ext_check = ProviderHelper::findGameExt($mt, 3, 'transaction_id');
    		$game_transaction = ProviderHelper::findGameTransaction($find_mtcode->game_trans_id, 'game_transaction');
	  		$game_details = ProviderHelper::findGameID($game_transaction->game_id);
	  		$user_id = Providerhelper::findTokenID($game_transaction->token_id)->player_id;
	    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
			$amount = $find_mtcode->amount;
			if($find_mtcode->game_transaction_type == 1){ // BET SHOULD BE REFUNDED AS CREDIT
				$pay_amount = 0;
				$income = $game_transaction->bet_amount - $amount;
				$transaction_type = 'credit';
			}else if($find_mtcode->game_transaction_type == 2){ // WIN SHOULD BE REFUNDED AS DEBIT
				$pay_amount = $game_transaction->pay_amount - $amount;
				$income = $game_transaction->bet_amount - $pay_amount;
				$transaction_type = 'debit';
			}

			$win_or_lost = 4;
			$entry_id = $game_transaction->entry_id;
	    	$token_id = $client_details->token_id;
			$payout_reason = 'REFUND ROUND';
			$provider_trans_id = $mt;
			$game_transaction_type = 3;

			ProviderHelper::updateBetTransaction($game_transaction->round_id, $pay_amount, $income, $win_or_lost, $entry_id);
	 	    $game_transextension = ProviderHelper::createGameTransExtV2($find_mtcode->game_trans_id,$provider_trans_id, $provider_trans_id, $amount, $game_transaction_type);
			$client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$find_mtcode->game_trans_id, $transaction_type, true);

	  	    if($client_response != 'false'){
  	    		$general_details = [
					"provider" => [
						"description" => 'Refunded Bets',
						"refund_type" => 'refund_bets'
					],
					"client" => [
						"description" => 'SENDED DATA TO CLIENT!',
						"transaction_type" => $transaction_type,
						"amount" => $amount
					],
					"old_transaction" => [
						"description" => 'OLD GAME TRANSACTION BEFORE THE REFUND!',
						"amount" => $amount,
				    	"player_id"=> $user_id,
				    	"bet_amount" => $game_transaction->bet_amount,
				    	"win" => $game_transaction->win,
				    	"pay_amount" => $game_transaction->pay_amount,
				    	"income" => $game_transaction->income,
				    	"entry_id" => $game_transaction->entry_id,
						]
				];
				$mw_response = [
		    		"data" => [
		    			"balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
		    	];
			   
			   	ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);

			}else{
				$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				Helper::saveLog('CQ9 playerRefunds Failed', $this->provider_db_id, json_encode($request->all()), $mw_response);
			}
		}

		$mw_response = [ // LAST LOOP RESPONSE
    		"data" => [
    			"balance" => ProviderHelper::amountToFloat($client_response['client_response']->fundtransferresponse->balance),
    			"currency" => $client_details->default_currency,
    		],
    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
    	];
		return $mw_response;
    }

    public function playerCancel(Request $request){
    	Helper::saveLog('CQ9 playerCancel Player', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	// $provider_request = $request->all();
    	// $mtcodes = ProviderHelper::rawToObj($request->mtcode, true);
    	$provider_request = json_decode(file_get_contents("php://input"));
    	$mtcodes = $provider_request;

    	$check_wtoken = $this->checkAuth($header);
    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
  
    	foreach($mtcodes->mtcode as $mt){
    		$game_ext_check = ProviderHelper::findGameExt($mt, 3, 'transaction_id');
	  		if($game_ext_check == 'false'){
	  			$mw_response = ["data" => [],"status" => ["code" => "1014","message" => 'Transaction record not found',"datetime" => date(DATE_RFC3339)]];
				Helper::saveLog('CQ9 playerRefund ALready Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
				return $mw_response;
	  		}
    	}

    	foreach($mtcodes->mtcode as $mt){
    		$find_mtcode = ProviderHelper::findGameExt($mt, 3, 'transaction_id');
    		$game_transaction = ProviderHelper::findGameTransaction($find_mtcode->game_trans_id, 'game_transaction');
	  		$game_details = ProviderHelper::findGameID($game_transaction->game_id);
	  		$user_id = Providerhelper::findTokenID($game_transaction->token_id)->player_id;
	    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
	    	if($client_details == null){
	    		$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
		    	];
				return $mw_response;
	    	}
			$game_ext_details = $find_mtcode->general_details;
	    	$general_details = json_decode($game_ext_details);
	    	if($general_details->provider->refund_type == 'cancel_refund'){
	    		$mw_response = ["data" => null,"status" => ["code" => "2009","message" => 'Transaction duplicate',"datetime" => date(DATE_RFC3339)]];
				Helper::saveLog('CQ9 Duplicate Refund = '.$mt, $this->provider_db_id, json_encode($provider_request), $mw_response);
				return $mw_response;
	    	}
			if($general_details->client->transaction_type == 'credit'){ // BET SHOULD BE REFUNDED AS CREDIT
				$transaction_type = 'debit';
			}else if($general_details->game_transaction_type == 'debit'){ // WIN SHOULD BE REFUNDED AS DEBIT
				$transaction_type = 'credit';
			}
			$amount = $general_details->client->amount;
			$pay_amount = $general_details->old_transaction->pay_amount;
			$income = $general_details->old_transaction->income;
			$win_or_lost = 4;
			$entry_id = $general_details->old_transaction->entry_id;
	    	$token_id = $client_details->token_id;
			$payout_reason = 'CANCEL REFUND ROUND';
			$provider_trans_id = $mt;
			$game_transaction_type = 3;

			$this->updateBetTransaction($find_mtcode->game_trans_id, $pay_amount, $income, $win_or_lost, $entry_id);
			$game_transextension = ProviderHelper::createGameTransExtV2($find_mtcode->game_trans_id,$provider_trans_id, $provider_trans_id, $amount, $game_transaction_type);
		    $client_response = ClientRequestHelper::fundTransfer($client_details,$amount,$game_details->game_code,$game_details->game_name,$game_transextension,$find_mtcode->game_trans_id, 'credit', true);

	  	    if($client_response != 'false'){
	  	    	$general_details = [
					"provider" => [
						"description" => 'Cancel Refund',
						"refund_type" => 'cancel_refund'
					],
					"client" => [
						"description" => 'SENDED DATA TO CLIENT!',
						"transaction_type" => $transaction_type,
						"amount" => $general_details->client->amount
					],
					"old_transaction" => [
						"description" => 'OLD GAME TRANSACTION BEFORE THE CANCEL REFUND!',
						"amount" => $general_details->client->amount,
				    	"player_id"=> $user_id,
				    	"bet_amount" => $game_transaction->bet_amount,
				    	"win" => $game_transaction->win,
				    	"pay_amount" => $game_transaction->pay_amount,
				    	"income" => $game_transaction->income,
				    	"entry_id" => $game_transaction->entry_id,
					]
				];
			
				$mw_response = [
		    		"data" => [
		    			"balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
		    	];
			    
			    ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);

			}else{
				$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				Helper::saveLog('CQ9 playerCancel Failed', $this->provider_db_id, json_encode($request->all()), $mw_response);
			}
		}

		$mw_response = [ // LAST LOOP RESPONSE
    		"data" => [
    			"balance" => ProviderHelper::amountToFloat($client_response['client_response']->fundtransferresponse->balance),
    			"currency" => $client_details->default_currency,
    		],
    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
    	];
		return $mw_response;
    }


 	public function playerWins(Request $request){
 		Helper::saveLog('CQ9 playerWins Player', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	// $provider_request = $request->all();
    	// $data_details = ProviderHelper::rawToObj($request->event, true);
    	$provider_request = json_decode(file_get_contents("php://input"));
    	$data_details = $provider_request->list;

    	$check_wtoken = $this->checkAuth($header);
    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	
		try {
			$response = ["data" => ["success" => [],"failed" => [],],"status" =>  ["code" =>  "0","message" =>  "Success","datetime" => ""]];
	    	foreach($data_details as $key => $data){
	    		$ucode = $data->ucode;
	    		$account = $data->account;
	    		$action = 'wins';
	    		$eventtime = $data->eventtime;
				$createtime = date(DATE_RFC3339);
	    		$user_id = Providerhelper::explodeUsername('_', $data->account);
		    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
				if($client_details == null){
					$failed = ["account" => $account,"code" =>"1006","message" =>"Player not found","ucode" => $ucode];
		    		array_push($response['data']['failed'], $failed);
		    		continue;
				}
				$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
				foreach ($data->event as $key => $value) {
					$da_error = 0;
					$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $value->gamecode);
		    		if($value->amount < 0 AND $da_error == 0){
		    			$failed = ["account" => $account,"code" =>"1003".$value->roundid,"message" =>"Amount cannot be negative","ucode" => $ucode];
			    		array_push($response['data']['failed'], $failed);
			    		$da_error = 1;
			   		}
			   		// $game_ext_check = ProviderHelper::findGameExt($value->mtcode, 1, 'transaction_id');
			   		$game_ext_check = ProviderHelper::findGameExt($value->roundid, 1, 'round_id');
					if($game_ext_check == 'false' AND $da_error == 0){
						$failed = ["account" => $account,"code" =>"1014".$value->roundid,"message" =>"Transaction record not found","ucode" => $ucode];
			    		Helper::saveLog('CQ9 T ALready Exist', $this->provider_db_id, json_encode($provider_request), $failed);
			    		array_push($response['data']['failed'], $failed);
			    		$da_error = 1;
					}	
					$game_ext_check_win = ProviderHelper::findGameExt($value->mtcode, 2, 'transaction_id');
					if($game_ext_check_win != 'false' AND $da_error == 0){
						$failed = ["account" => $account,"code" =>"2009".$value->roundid,"message" =>"Transaction duplicate","ucode" => $ucode];
			    		Helper::saveLog('CQ9 T ALready Exist', $this->provider_db_id, json_encode($provider_request), $failed);
			    		array_push($response['data']['failed'], $failed);
			    		$da_error = 1;
					}

					if($da_error == 0){
						$game_transaction = ProviderHelper::findGameTransaction($game_ext_check->game_trans_id, 'game_transaction');
						$amount = $value->amount;
						$token_id = $client_details->token_id;
						$pay_amount =  $amount;
						$payout_reason = 'Wins';
						$income = $game_transaction->bet_amount - $pay_amount;
						$provider_trans_id = $value->mtcode;
						$roundid = $value->roundid;
						$game_transaction_type = 2;
						$entry_id = 2;
						$win_or_lost = 1;
				
						ProviderHelper::updateBetTransaction($game_transaction->round_id, $pay_amount, $income, $win_or_lost, $entry_id);
						$game_transextension = ProviderHelper::createGameTransExtV2($game_ext_check->game_trans_id,$provider_trans_id, $roundid, $amount, $game_transaction_type);

					    $client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$game_ext_check->game_trans_id, 'credit');

					     if($client_response != 'false'){
					    	$general_details = [
								"provider" => [
									"createtime" => $createtime,  // The Transaction Created!
									"endtime" => date(DATE_RFC3339),
									"eventtime" => $eventtime,
									"action" => $action
								],
								"client" => [
									"before_balance" => ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance),
							    	"after_balance"=> ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
							    	"player_prefixed"=> $account,
							    	"player_id"=> $user_id
								],
								"old_transaction" => [
									"description" => 'OLD GAME TRANSACTION BEFORE THE Win Call!',
							    	"player_id"=> $user_id,
							    	"bet_amount" => $game_transaction->bet_amount,
							    	"win" => $game_transaction->win,
							    	"pay_amount" => $game_transaction->pay_amount,
							    	"income" => $game_transaction->income,
							    	"entry_id" => $game_transaction->entry_id,
								]
							];
					    	$mw_response = [
					    		"data" => [
				    				"balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
					    			"currency" => $client_details->default_currency,
					    		],
					    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
					    	];
						
						    ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);
					 	    $success = ["account" => $account,"balance" =>ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),"currency" => $client_details->default_currency,"ucode" => $ucode];
			    		    array_push($response['data']['success'], $success);
						}else{
							$failed = ["account" => $account,"code" =>"1100","message" =>"Server error.","ucode" => $ucode];
			    			array_push($response['data']['failed'], $failed);
						}
					}
				}
	    	}	

	    	$response['status']['datetime'] = date(DATE_RFC3339);
	    	return $response;
		} catch (\Exception $e) {
			$mw_response = [
	    		"data" => [],"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]
	    	];
			Helper::saveLog('CQ9 playerWins Failed', $this->provider_db_id, json_encode($request->all()), $e->getMessage());
			return $mw_response;
		}

 	}

    public function playerAmends(Request $request){
    	Helper::saveLog('CQ9 playerAmends Player', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	// $provider_request = $request->all();
    	// $data_details = ProviderHelper::rawToObj($request->event, true);
    	$provider_request = json_decode(file_get_contents("php://input"));
    	$data_details = $provider_request->list;

    	// dd($data_details);
    	$check_wtoken = $this->checkAuth($header);
    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}

    	try {
			$response = ["data" => ["success" => [],"failed" => [],],"status" =>  ["code" =>  "0","message" =>  "Success","datetime" => ""]];
	    	foreach($data_details as $key => $data){
	    		$ucode = $data->ucode;
	    		$account = $data->account;
	    		$action = 'amends';
	    		$eventtime = $data->eventtime;
				$createtime = date(DATE_RFC3339);
	    		$user_id = Providerhelper::explodeUsername('_', $data->account);
		    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
				if($client_details == null){
					$failed = ["account" => $account,"code" =>"1006","message" =>"Player not found","ucode" => $ucode];
		    		array_push($response['data']['failed'], $failed);
		    		continue;
				}
				$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
				foreach ($data->event as $key => $value) {
					$da_error = 0;
					$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $value->gamecode);
		    		if($value->amount < 0 AND $da_error == 0){
		    			$failed = ["account" => $account,"code" =>"1003".$value->roundid,"message" =>"Amount cannot be negative","ucode" => $ucode];
			    		array_push($response['data']['failed'], $failed);
			    		$da_error = 1;
			   		}
			   		// $game_ext_check = ProviderHelper::findGameExt($value->mtcode, 2, 'transaction_id');
			   		$game_ext_check = ProviderHelper::findGameExt($value->roundid, 2, 'round_id');
					if($game_ext_check == 'false' AND $da_error == 0){
						$failed = ["account" => $account,"code" =>"1014".$value->roundid,"message" =>"Transaction record not found","ucode" => $ucode];
			    		Helper::saveLog('CQ9 T ALready Exist', $this->provider_db_id, json_encode($provider_request), $failed);
			    		array_push($response['data']['failed'], $failed);
			    		$da_error = 1;
					}	
					$game_ext_check_win = ProviderHelper::findGameExt($value->mtcode, 3, 'transaction_id');
					if($game_ext_check_win != 'false' AND $da_error == 0){
						$failed = ["account" => $account,"code" =>"2009".$value->roundid,"message" =>"Transaction duplicate","ucode" => $ucode];
			    		Helper::saveLog('CQ9 T ALready Exist', $this->provider_db_id, json_encode($provider_request), $failed);
			    		array_push($response['data']['failed'], $failed);
			    		$da_error = 1;
					}

					if($da_error == 0){
						$game_transaction = ProviderHelper::findGameTransaction($game_ext_check->game_trans_id, 'game_transaction');
						$amount = $value->amount;
						$transactiontype = $value->action;
						$token_id = $client_details->token_id;
						$pay_amount =  $game_transaction->pay_amount;
						$payout_reason = 'Amends Win';
						$income = $game_transaction->bet_amount - $pay_amount;
						$provider_trans_id = $value->mtcode;
						$roundid = $value->roundid;
						$game_transaction_type = 3;
						$entry_id = $game_transaction->entry_id;
						$win_or_lost = 4;
						
						ProviderHelper::updateBetTransaction($game_transaction->round_id, $pay_amount, $income, $win_or_lost, $entry_id);
				 	    $game_transextension = ProviderHelper::createGameTransExtV2($game_ext_check->game_trans_id,$provider_trans_id, $roundid, $amount, $game_transaction_type);
					    $client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$game_ext_check->game_trans_id, $transactiontype, true);

					     if($client_response != 'false'){
					    	$general_details = [
								"provider" => [
									"createtime" => $createtime,  // The Transaction Created!
									"endtime" => date(DATE_RFC3339),
									"eventtime" => $eventtime,
									"action" => $action,
									"refund_type" => 'amends_win'
								],
								"client" => [
									"before_balance" => ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance),
							    	"after_balance"=> ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
							    	"player_prefixed"=> $account,
							    	"player_id"=> $user_id
								],
								"old_transaction" => [
									"description" => 'OLD GAME TRANSACTION BEFORE THE WINS AMENDED!',
							    	"player_id"=> $user_id,
							    	"bet_amount" => $game_transaction->bet_amount,
							    	"win" => $game_transaction->win,
							    	"pay_amount" => $game_transaction->pay_amount,
							    	"income" => $game_transaction->income,
							    	"entry_id" => $game_transaction->entry_id,
								]
							];
					    	$mw_response = [
					    		"data" => [
				    				"balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
					    			"currency" => $client_details->default_currency,
					    		],
					    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
					    	];
					 	    ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);

					 	    $success = ["account" => $account,"balance" =>ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),"currency" => $client_details->default_currency,"ucode" => $ucode];
			    		    array_push($response['data']['success'], $success);
						}else{
							$failed = ["account" => $account,"code" =>"1100","message" =>"Server error.","ucode" => $ucode];
			    			array_push($response['data']['failed'], $failed);
						}
					}
				}
	    	}	

	    	$response['status']['datetime'] = date(DATE_RFC3339);
	    	return $response;
		} catch (\Exception $e) {
			$mw_response = [
	    		"data" => [],"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]
	    	];
			Helper::saveLog('CQ9 playerAmends Failed', $this->provider_db_id, json_encode($request->all()), $e->getMessage());
			return $mw_response;
		}
    }

    public function playerAmend(Request $request){
    	Helper::saveLog('CQ9 playerAmend Player', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	// $provider_request = $request->all();
    	// $data_details = ProviderHelper::rawToObj($request->event, true);
    	$provider_request = json_decode(file_get_contents("php://input"));
    	$data_details = $provider_request->data;
    	$account = $provider_request->account;
    	$gamecode = $provider_request->gamecode;
    	$gamehall = $provider_request->gamehall;
    	$eventime = $provider_request->createTime; // created
		$createtime = date(DATE_RFC3339);
		$action = 'amend';
    	$check_wtoken = $this->checkAuth($header);
    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
		$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details == null){
    		$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
    	}
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
		if($player_details == 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
		}

    	try {
			$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
	    	foreach ($data_details as $value) {
	    		$roundid = $value->roundid;
		    	$amount = $value->amount;
		    	$mtcode = $value->mtcode;
	    		$game_ext_check = ProviderHelper::findGameExt($roundid, 1, 'round_id');
				if($game_ext_check == 'false'){
		    		$mw_response = [
			    		"data" => [
			    			"balance" => ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance),
			    			"currency" => $client_details->default_currency,
			    		],
			    		"status" => ["code" => "1014","message" => 'Transaction record not found',"datetime" => date(DATE_RFC3339)]
			    	];
					return $mw_response;
				}	
				$game_ext_check = ProviderHelper::findGameExt($roundid, 3, 'round_id');
	    	    $general_details = json_decode($game_ext_check->general_details);
				if($general_details->provider->refund_type == 'amend_amends'){
		    		$mw_response = [
			    		"data" => [
			    			"balance" => ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance),
			    			"currency" => $client_details->default_currency,
			    		],
			    		"status" => ["code" => "2009","message" => 'Transaction duplicate',"datetime" => date(DATE_RFC3339)]
			    	];
					return $mw_response;
				}

	    	}
	    	foreach ($data_details as $key) {
	    		$roundid = $key->roundid;
		    	$amount = $key->amount;
		    	$mtcode = $key->mtcode;
		    	$game_ext_check = ProviderHelper::findGameExt($roundid, 3, 'round_id');
		    	$general_details_db = json_decode($game_ext_check->general_details);
		    	$game_transaction = ProviderHelper::findGameTransaction($game_ext_check->game_trans_id, 'game_transaction');
		    	if($amount == 0 || $provider_request->action == 'credit'){
		    		$transaction_type = 'credit';
		    	}else{
		    		$transaction_type = 'debit';
		    	}
		    	$pay_amount = $game_transaction->pay_amount - $amount;
				$income = $game_transaction->bet_amount - $pay_amount;
				$win_or_lost = 4;
				$entry_id = $game_transaction->entry_id;
		    	$token_id = $client_details->token_id;
				$payout_reason = 'Amend Amends';
				$provider_trans_id = $mtcode;
				$game_transaction_type = 3;
			
				ProviderHelper::updateBetTransaction($game_transaction->round_id, $pay_amount, $income, $win_or_lost, $entry_id);
		 	    $game_transextension = ProviderHelper::createGameTransExtV2($game_transaction->game_trans_id,$provider_trans_id, $roundid, $amount, $game_transaction_type);
			    $client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$game_transaction->game_trans_id, $transaction_type, true);
		  	    if($client_response != 'false'){
		  	    	$general_details = [
						"provider" => [
							"description" => 'Refunded Bets',
							"refund_type" => 'amend_amends'
						],
						"client" => [
							"description" => 'SENDED DATA TO CLIENT!',
							"transaction_type" => $transaction_type,
							"amount" => $amount
						],
						"old_transaction" => [
							"description" => 'OLD GAME TRANSACTION BEFORE AMEND THE AMENDS!',
							"amount" => $amount,
					    	"player_id"=> $user_id,
					    	"bet_amount" => $game_transaction->bet_amount,
					    	"win" => $game_transaction->win,
					    	"pay_amount" => $game_transaction->pay_amount,
					    	"income" => $game_transaction->income,
					    	"entry_id" => $game_transaction->entry_id,
							]
					];
					$mw_response = [
			    		"data" => [
			    			"balance" => ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance),
			    			"currency" => $client_details->default_currency,
			    		],
			    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
			    	];
				
			 	    ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);

				}else{
					$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
					Helper::saveLog('CQ9 playerRefunds Failed', $this->provider_db_id, json_encode($request->all()), $mw_response);
				}
			}

			return $mw_response;
		} catch (\Exception $e) {
			$mw_response = [
	    		"data" => [],"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]
	    	];
			Helper::saveLog('CQ9 playerAmend Failed', $this->provider_db_id, json_encode($request->all()), $e->getMessage());
			return $mw_response;
		}
    }

    public function validRFCDade($date) {

    	$length = strlen($date);

    	if($length != 25){
    		return false;
    	}else{
    		return true;
    	}

	    // if (\DateTime::createFromFormat(\DateTime::RFC3339, $date) === FALSE) {
	    // 	// return \DateTime::createFromFormat(\DateTime::RFC3339, $date);
	    //     return false;
	    // } else {
	    //     return true;
	    // }
	}

    public function findTranPID($provider_identifier) {
		$transaction_db = DB::table('game_transaction_ext as gte');
		$transaction_db->where([
	 		["gte.provider_trans_id", "=", $provider_identifier],
	 	]);
	 	$result= $transaction_db->first();
		return $result ? $result : 'false';
	}

	public  function updateBetTransaction($game_trans_id, $pay_amount, $income, $win, $entry_id) {
   	    $update = DB::table('game_transactions')
                ->where('game_trans_id', $game_trans_id)
                ->update(['pay_amount' => $pay_amount, 
	        		  'income' => $income, 
	        		  'win' => $win, 
	        		  'entry_id' => $entry_id,
	        		  'transaction_reason' => ProviderHelper::updateReason($win),
	    		]);
		return ($update ? true : false);
	}

    public function fundTransferRequest($client_access_token,$client_api_key,$game_code,$game_name,$client_player_id,$player_token,$amount,$fund_transfer_url,$transtype,$currency,$rollback=false){
    	try {
    		$client = new Client([
			    'headers' => [ 
			    	'Content-Type' => 'application/json',
			    	'Authorization' => 'Bearer '.$client_access_token
			    ]
			]);
	        $requesttosend = [
		            "access_token" => $client_access_token,
		            "hashkey" => md5($client_api_key.$client_access_token),
		            "type" => "fundtransferrequest",
		            "datesent" => Helper::datesent(),
			            "gamedetails" => [
			            "gameid" => $game_code, // $game_code
			            "gamename" => $game_name
		            ],
	            	"fundtransferrequest" => [
		                "playerinfo" => [
		                "client_player_id" => $client_player_id,
		                "token" => $player_token,
	                ],
	                "fundinfo" => [
	                        "gamesessionid" => "",
	                        "transactiontype" => $transtype,
	                        "transferid" => "",
	                        "rollback" => $rollback,
	                        "currencycode" => $currency,
	                        "amount" => $amount
	                ],
	            ],
	        ];
	        // return $requesttosend;
	        $guzzle_response = $client->post($fund_transfer_url,
	            ['body' => json_encode($requesttosend)]
	        );
	        $client_response = json_decode($guzzle_response->getBody()->getContents());
	        $data = [
	            'requesttosend' => $requesttosend,
	            'client_response' => $client_response,
	        ];
	        return $data;
    		//
    	} catch (\Exception $e) {
    		Helper::saveLog('Called Failed!', $this->provider_db_id, json_encode($requesttosend), $e->getMessage());
    		return 'false';
    	}
    }
}
