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




/**
 * @author's note  STATIC = payoff, amend, amends $gamecode = 'AB1';
 */
class CQ9Controller extends Controller
{

	public $api_url, $api_token, $provider_db_id, $prefix;
	public $game_static_code = 'BPUP2019';

	// /gameboy/player/logout
	// /gameboy/game/list/cq9
	// /gameboy/game/halls
	// NOTE $gamecode = 'AB1';

	public function __construct(){
    	$this->api_url = config('providerlinks.cqgames.api_url');
    	$this->api_token = config('providerlinks.cqgames.api_token');
    	$this->provider_db_id = config('providerlinks.cqgames.pdbid');
    	$this->prefix = config('providerlinks.cqgames.prefix');
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
	    	Helper::saveLog('CQ9 playerBet', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	if(!$this->validRFCDade($request->eventTime)){
    		$mw_response = ["data" => null,"status" => ["code" => "1004","message" => 'Time Format error.',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerBet', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	$check_string_user = ProviderHelper::checkIfHasUnderscore($request->account);
    	if(!$check_string_user){
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerBet', $this->provider_db_id, json_encode($provider_request), $mw_response);
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
	    	Helper::saveLog('CQ9 playerBet', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}
    	if($amount < 0){
   			$mw_response = [
	    		"data" => null,
	    		"status" => ["code" => "1003","message" => 'Amount cannot be negative and must be positive!',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerBet', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
   		}
   		if($player_details->playerdetailsresponse->balance < $amount){
   			$mw_response = [
	    		"data" => null,
	    		"status" => ["code" => "1005","message" => 'Insufficient Balance',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerBet', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
   		}
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
		if($game_details == null){
			$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerBet', $this->provider_db_id, json_encode($request->all()), $mw_response);
			return $mw_response;
		}
		$game_ext_check = ProviderHelper::findGameExt($mtcode, 1, 'transaction_id');
		if($game_ext_check != 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "2009","message" => 'Transaction duplicate',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerBet', $this->provider_db_id, json_encode($provider_request), $mw_response);
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

		    try {
				 $client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$gamerecord, 'debit');
				 Helper::saveLog('CQ9 playerBet CRID = '.$gamerecord, $this->provider_db_id, json_encode($provider_request), $client_response);
			} catch (\Exception $e) {
			    $mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $mw_response, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
				Helper::saveLog('CQ9 playerBet - FATAL ERROR', $this->provider_db_id, $mw_response, Helper::datesent());
				return $mw_response;
			}

		    if(isset($client_response->fundtransferresponse->status->code) 
             && $client_response->fundtransferresponse->status->code == "200"){

		    	$general_details = [
					"provider" => [
						"createtime" => $createtime,  // The Transaction Created!
						"endtime" => date(DATE_RFC3339),
						"eventtime" => $eventime,
						"action" => $action
					],
					"client" => [
						"before_balance" => $this->amountToFloat4DG($player_details->playerdetailsresponse->balance),
				    	"after_balance"=> $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
				    	"player_prefixed"=> $account,
				    	"player_id"=> $user_id
					]
				];
				$mw_response = [
		    		"data" => [
		    			"balance" => $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
		    	];

		    	ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);

			}elseif(isset($client_response->fundtransferresponse->status->code) 
			            && $client_response->fundtransferresponse->status->code == "402"){
				$mw_response = [
	    		"data" => null,
		    		"status" => ["code" => "1005","message" => 'Insufficient Balance',"datetime" => date(DATE_RFC3339)]
		    	];
		    	ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED',$general_details);
		    	Helper::saveLog('CQ9 playerBet', $this->provider_db_id, json_encode($provider_request), $mw_response);

			}else{ // Unknown Response Code

				$mw_response = ["data" => [],"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				Helper::saveLog('CQ9 playerBet - FATA ERROR', $this->provider_db_id, json_encode($request->all()), $mw_response);
				ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED',$general_details);
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
    	$check_wtoken = $this->checkAuth($header);
    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	if(!$request->has('account') || !$request->has('createTime') || !$request->has('gamehall') || !$request->has('gamecode') || !$request->has('roundid') || !$request->has('data')){
    		$mw_response = ["data" => null,"status" => ["code" => "1003","message" => 'Parameter error.',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 Endround', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	if(!$this->validRFCDade($request->createTime)){
    		$mw_response = ["data" => null,"status" => ["code" => "1004","message" => 'Time Format error.',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 Endround', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}

		$data_details = $this->rawToObj($request->data, true);
    	$account = $request->account;
    	$gamecode = $request->gamecode;
    	$gamehall = $request->gamehall;
    	$roundid = $request->roundid;
    	$eventime = $request->createTime; // created
		$createtime = date(DATE_RFC3339);
		$action = 'endround';

 	    $check_string_user = ProviderHelper::checkIfHasUnderscore($account);
    	if(!$check_string_user){
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Endround', $this->provider_db_id, json_encode($provider_request), $mw_response);
		   return $mw_response;
    	}
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details == null){
    		$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 Endround', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
		// $total_amount = array();
    	foreach($data_details as $data){
    		if($data->amount < 0){
	   			$mw_response = [
		    		"data" => null,
		    		"status" => ["code" => "1003","message" => 'Amount cannot be negative!',"datetime" => date(DATE_RFC3339)]
		    	];
		    	Helper::saveLog('CQ9 Endround', $this->provider_db_id, json_encode($provider_request), $mw_response);
				return $mw_response;
	   		}

	   		if(!$this->validRFCDade($data->eventtime)){
	    		$mw_response = ["data" => null,"status" => ["code" => "1004","message" => 'Time Format error.',"datetime" => date(DATE_RFC3339)]
		    	];
		    	Helper::saveLog('CQ9 Endround', $this->provider_db_id, json_encode($provider_request), $mw_response);
				return $mw_response;
	    	}
    		// array_push($total_amount, $data->amount);
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
		// $game_ext_exist = ProviderHelper::findGameExt($roundid, 2, 'round_id');
		$game_ext_exist = ProviderHelper::findGameExt($data->mtcode, 2, 'transaction_id');
		if($game_ext_exist != 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "2009","message" => 'Duplicate Transaction',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playrEndround ALready Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}		
		$game_transaction = ProviderHelper::findGameTransaction($game_ext_check->game_trans_id, 'game_transaction');

		try {

		$multi_event_bag = ["events"=>[]];
    	$multi_event = false;
    	$gametrans_ext_bag_id = [];
    	if(count($data_details) > 1){
    		$multi_event = true;
    		$multi_event_bag['before_balance'] = $this->amountToFloat4DG($player_details->playerdetailsresponse->balance);
    		$multi_event_bag['me_createtime'] = $createtime;
		   	$multi_event_bag['action'] = $action;
    	}

	    foreach($data_details as $data){

    		// $total_amount = array_sum($total_amount); // single loop (DEPRECATED)
    		$total_amount =  $data->amount;
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
			array_push($gametrans_ext_bag_id, $game_transextension);
		   
		    try {
				 $client_response = ClientRequestHelper::fundTransfer($client_details,$data->amount,$game_details->game_code,$game_details->game_name,$game_transextension,$game_transaction->game_trans_id, 'credit');
				 Helper::saveLog('CQ9 playrEndround CRID = '.$game_transaction->game_trans_id, $this->provider_db_id, json_encode($provider_request), $client_response);
			} catch (\Exception $e) {
			    $mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $mw_response, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
				Helper::saveLog('CQ9 playrEndround - FATAL ERROR', $this->provider_db_id, $mw_response, Helper::datesent());
				return $mw_response;
			}

		    if(isset($client_response->fundtransferresponse->status->code) 
			             && $client_response->fundtransferresponse->status->code == "200"){
		    	// $multi_event_amount = $this->amountToFloat4DG($this->amountToFloat4DG($client_response->fundtransferresponse->balance) - $this->amountToFloat4DG($player_details->playerdetailsresponse->balance));

		    	$multi_event_amount = $this->amountToFloat4DG($this->amountToFloat4DG($player_details->playerdetailsresponse->balance) - $this->amountToFloat4DG($client_response->fundtransferresponse->balance));

		    	$multi_event_array = [
		    		"mtcode" => $provider_trans_id,
	                "amount" => $data->amount,
	                // "amount" => $multi_event_amount,
	                "eventtime" => $data->eventtime
		    	];
		    	array_push($multi_event_bag['events'], $multi_event_array);
		    	$general_details = [
		    		"multi_event" => $multi_event,
					"provider" => [
						"createtime" => $createtime,  // The Transaction Created!
						"endtime" => date(DATE_RFC3339),
						"eventtime" => $data->eventtime,
						"action" => $action
					],
					"client" => [
						"before_balance" => $this->amountToFloat4DG($player_details->playerdetailsresponse->balance),
				    	"after_balance"=> $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
				    	"player_prefixed"=> $account,
				    	"player_id"=> $user_id
					]
				];
				$mw_response = [
		    		"data" => [
		    			"balance" => $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
		    	];
			    ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response, $general_details);

			}else{ // Unknown Response Code
				$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				Helper::saveLog('CQ9 playrEndround Failed', $this->provider_db_id, json_encode($request->all()), $mw_response);
				ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED', $general_details);
				return $mw_response;
			}   
		}
	    	$multi_event_bag['after_balance'] = $this->amountToFloat4DG($client_response->fundtransferresponse->balance);
	    	$multi_event_bag['me_endtime'] = date(DATE_RFC3339);

	    	if($multi_event == true){
	    		foreach ($gametrans_ext_bag_id as $key) {
	    			$transaction_record = $this->findGameExtByID($key);
	    			$game_ext_details = $transaction_record->general_details;
	    	        $general_details_bag = json_decode($game_ext_details);
	    			$general_details_bag->multi_events = $multi_event_bag;
	    			$this->updatecreateGameTransExtGD($key, $general_details_bag);
	    		}
	    	}

			$mw_response = [
		    		"data" => [
		    			"balance" => $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)],
		    		// "test" => $multi_event_bag,
		    		// "test2" => $gametrans_ext_bag_id
	    	];
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
    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	if(!$request->has('account') || !$request->has('eventTime') || !$request->has('gamehall') || !$request->has('gamecode')  || !$request->has('roundid') || !$request->has('amount') || !$request->has('mtcode')){
    		$mw_response = ["data" => null,"status" => ["code" => "1003","message" => 'Parameter error.',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerCredit', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	if(!$this->validRFCDade($request->eventTime)){
    		$mw_response = ["data" => null,"status" => ["code" => "1004","message" => 'Time Format error.',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerCredit', $this->provider_db_id, json_encode($provider_request), $mw_response);
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
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerCredit', $this->provider_db_id, json_encode($provider_request), $mw_response);
		   return $mw_response;
		}
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details == null){
    		$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerCredit', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
		if($player_details == 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerCredit', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}
		$game_ext_check_refunded = ProviderHelper::findGameExt($mtcode, 3, 'transaction_id');
		if($game_ext_check_refunded != 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "2009","message" => 'Transaction Duplicate',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerCredit ALready Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}	
    	if($amount < 0){
   			$mw_response = [
	    		"data" => null,
	    		"status" => ["code" => "1003","message" => 'Amount cannot be negative!',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerCredit', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
   		}
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
		if($game_details == null){
			$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerCredit', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}
		$game_ext_check = ProviderHelper::findGameExt($roundid, 1, 'round_id');
		if($game_ext_check == 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1014","message" => 'Transaction record not found',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerCredit Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}		

		$game_transaction = ProviderHelper::findGameTransaction($game_ext_check->game_trans_id, 'game_transaction');
		// return 'yaw sa';
		try {
			$token_id = $client_details->token_id;
			$bet_amount = $amount;
			$pay_amount= $game_transaction->pay_amount + $amount;
			$win_or_lost = $game_transaction->win;
			$entry_id = $game_transaction->entry_id;
			$payout_reason = 'Credit Correction';
			$income =  $game_transaction->bet_amount - $pay_amount;
			$provider_trans_id = $mtcode;
			$game_transaction_type = 2;
			
		
			$game_transextension = ProviderHelper::createGameTransExtV2($game_ext_check->game_trans_id,$provider_trans_id, $roundid, $amount, 3);
			ProviderHelper::updateBetTransaction($game_transaction->round_id, $pay_amount, $income, $win_or_lost, $entry_id);

		    try {
				 $client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$game_ext_check->game_trans_id, 'credit', true);
				 Helper::saveLog('CQ9 playerCredit CRID = '.$game_ext_check->game_trans_id, $this->provider_db_id, json_encode($provider_request), $client_response);
			} catch (\Exception $e) {
			    $mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $mw_response, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
				Helper::saveLog('CQ9 playerCredit - FATAL ERROR', $this->provider_db_id, $mw_response, Helper::datesent());
				return $mw_response;
			}

		    if(isset($client_response->fundtransferresponse->status->code) 
			             && $client_response->fundtransferresponse->status->code == "200"){
		    	$general_details = [
					"provider" => [
						"createtime" => $createtime,  // The Transaction Created!
						"endtime" => date(DATE_RFC3339),
						"eventtime" => $eventime,
						"action" => $action
					],
					"client" => [
						"before_balance" => $this->amountToFloat4DG($player_details->playerdetailsresponse->balance),
				    	"after_balance"=> $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
				    	"player_prefixed"=> $account,
				    	"player_id"=> $user_id
					]
				];
		    	$mw_response = [
		    		"data" => [
		    			"balance" => $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
		    	];

				ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);

			}else{ // Unknown Response Code
				$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED' ,$general_details);
				return $mw_response;
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
    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	if(!$request->has('account') || !$request->has('eventTime') || !$request->has('gamehall') || !$request->has('gamecode') || !$request->has('roundid') || !$request->has('amount') || !$request->has('mtcode')){
    		$mw_response = ["data" => null,"status" => ["code" => "1003","message" => 'Parameter error.',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 Debit', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	if(!$this->validRFCDade($request->eventTime)){
    		$mw_response = ["data" => null,"status" => ["code" => "1004","message" => 'Time Format error.',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 Debit', $this->provider_db_id, json_encode($provider_request), $mw_response);
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
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 T ALready Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
		   return $mw_response;
		}
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details == null){
    		$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 Debit', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
		if($player_details == 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 Debit', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}
    	if($amount < 0){
   			$mw_response = [
	    		"data" => null,"status" => ["code" => "1003","message" => 'Amount cannot be negative!',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 Debit', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
   		}
   		if($player_details->playerdetailsresponse->balance < $amount){
   			$mw_response = [
	    		"data" => null,"status" => ["code" => "1005","message" => 'Insufficient Balance',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 Debit', $this->provider_db_id, json_encode($provider_request), $mw_response);
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
		$game_ext_check_refunded = ProviderHelper::findGameExt($mtcode, 3, 'transaction_id');
		if($game_ext_check_refunded != 'false'){
			// $mw_response = ["data" => null,"status" => ["code" => "1015","message" => 'Transaction record is already refunded',"datetime" => date(DATE_RFC3339)]];
			$mw_response = ["data" => null,"status" => ["code" => "2009","message" => 'Transaction Duplicate',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerDebit Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}
		   
		$game_transaction = ProviderHelper::findGameTransaction($game_ext_check->game_trans_id, 'game_transaction');

		try {
			$token_id = $client_details->token_id;
			$bet_amount = $amount;
			$pay_amount= $game_transaction->pay_amount - $amount;
			$win_or_lost = $game_transaction->win;
			$entry_id = $game_transaction->entry_id;
			$payout_reason = 'Debit Correction';
			$income = $game_transaction->bet_amount - $pay_amount;
			$provider_trans_id = $mtcode;
			$game_transaction_type = 2;


			$game_transextension = ProviderHelper::createGameTransExtV2($game_ext_check->game_trans_id,$provider_trans_id, $roundid, $amount, 3);
			ProviderHelper::updateBetTransaction($game_transaction->round_id, $pay_amount, $income, $win_or_lost, $entry_id);

		    try {
			    $client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$game_ext_check->game_trans_id, 'debit', true);
				 Helper::saveLog('CQ9 playerDebit CRID = '.$game_ext_check->game_trans_id, $this->provider_db_id, json_encode($provider_request), $client_response);
			} catch (\Exception $e) {
			    $mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $mw_response, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
				Helper::saveLog('CQ9 playerDebit - FATAL ERROR', $this->provider_db_id, $mw_response, Helper::datesent());
				return $mw_response;
			}

		    if(isset($client_response->fundtransferresponse->status->code) 
             && $client_response->fundtransferresponse->status->code == "200"){
	    		$general_details = [
					"provider" => [
						"createtime" => $createtime,  // The Transaction Created!
						"endtime" => date(DATE_RFC3339),
						"eventtime" => $eventime,
						"action" => $action
					],
					"client" => [
						"before_balance" => $this->amountToFloat4DG($player_details->playerdetailsresponse->balance),
				    	"after_balance"=> $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
				    	"player_prefixed"=> $account,
				    	"player_id"=> $user_id
					]
				];
		    	$mw_response = [
		    		"data" => [
		    			"balance" => $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
		    	];

		    	ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);

			}elseif(isset($client_response->fundtransferresponse->status->code) 
			            && $client_response->fundtransferresponse->status->code == "402"){
				$mw_response = [
				"data" => null,
					"status" => ["code" => "1005","message" => 'Insufficient Balance',"datetime" => date(DATE_RFC3339)]
				];
				ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED',$general_details);
			}else{ // Unknown Response Code
				$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED',$general_details);
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
	    	Helper::saveLog('CQ9 playerRollout', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	if(!$this->validRFCDade($request->eventTime)){
    		$mw_response = ["data" => null,"status" => ["code" => "1004","message" => 'Time Format error.',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerRollout', $this->provider_db_id, json_encode($provider_request), $mw_response);
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
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerRollout', $this->provider_db_id, json_encode($provider_request), $mw_response);
		   return $mw_response;
		}
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details == null){
    		$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerRollout', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
		if($player_details == 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerRollout', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}
    	if($amount < 0){
   			$mw_response = [
	    		"data" => null,"status" => ["code" => "1003","message" => 'Amount cannot be negative!',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerRollout', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
   		}
   		if($player_details->playerdetailsresponse->balance < $amount){
   			$mw_response = [
	    		"data" => null,"status" => ["code" => "1005","message" => 'Insufficient Balance',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerRollout', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
   		}
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
		if($game_details == null){
			$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerRollout', $this->provider_db_id, json_encode($request->all()), $mw_response);
			return $mw_response;
		}
		$game_ext_check = ProviderHelper::findGameExt($mtcode, 1, 'transaction_id');
		if($game_ext_check != 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "2009","message" => 'Transaction duplicate',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerRollout', $this->provider_db_id, json_encode($provider_request), $mw_response);
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

		   
		    try {
				 $client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$gamerecord, 'debit');
				 Helper::saveLog('CQ9 playerRollout CRID = '.$gamerecord, $this->provider_db_id, json_encode($provider_request), $client_response);
			} catch (\Exception $e) {
			    $mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $mw_response, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
				Helper::saveLog('CQ9 playerRollout - FATAL ERROR', $this->provider_db_id, $mw_response, Helper::datesent());
				return $mw_response;
			}

			if(isset($client_response->fundtransferresponse->status->code) 
			             && $client_response->fundtransferresponse->status->code == "200"){

				$general_details = [
					"provider" => [
						"createtime" => $createtime,  // The Transaction Created!
						"endtime" => date(DATE_RFC3339),
						"eventtime" => $eventime,
						"action" => $action
					],
		    		"client" => [
		    			"before_balance" => $this->amountToFloat4DG($player_details->playerdetailsresponse->balance),
				    	"after_balance"=> $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
				    	"player_prefixed"=> $account,
				    	"player_id"=> $user_id
		    		]
		    	];
				$mw_response = [
		    		"data" => [
		    			"balance" => $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
		    	];
				
		    	ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);

			}elseif(isset($client_response->fundtransferresponse->status->code) 
			            && $client_response->fundtransferresponse->status->code == "402"){
				$mw_response = [
				"data" => null,
					"status" => ["code" => "1005","message" => 'Insufficient Balance',"datetime" => date(DATE_RFC3339)]
				];
				ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED',$general_details);
			}else{ // Unknown Response Code
				$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED',$general_details);
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
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerTakeall', $this->provider_db_id, json_encode($provider_request), $mw_response);
		   return $mw_response;
		}
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details == null){
    		$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerTakeall', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
    	if($player_details == 'false'){
    		$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerTakeall', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	$game_ext_check = ProviderHelper::findGameExt($mtcode, 1, 'transaction_id');
		if($game_ext_check != 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "2009","message" => 'Transaction duplicate',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerTakeall', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}	
    	$amount = $player_details->playerdetailsresponse->balance;
    	if($amount == 0 || $amount < 0){
    		$mw_response = [
		    		"data" => null,"status" => ["code" => "1005","message" => 'Insufficient Balance',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerTakeall', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
		if($game_details == null){
			$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerTakeall', $this->provider_db_id, json_encode($request->all()), $mw_response);
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

			try {
				 $client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$gamerecord, 'debit');
				 Helper::saveLog('CQ9 playerTakeall CRID = '.$gamerecord, $this->provider_db_id, json_encode($provider_request), $client_response);
			} catch (\Exception $e) {
			    $mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $mw_response, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
				Helper::saveLog('CQ9 playerTakeall - FATAL ERROR', $this->provider_db_id, $mw_response, Helper::datesent());
				return $mw_response;
			}

			if(isset($client_response->fundtransferresponse->status->code) 
			             && $client_response->fundtransferresponse->status->code == "200"){
				$general_details = [
					"provider" => [
						"createtime" => $createtime,  // The Transaction Created!
						"endtime" => date(DATE_RFC3339),
						"eventtime" => $eventime,
						"action" => $action
					],
					"client" => [
						"before_balance" => $this->amountToFloat4DG($player_details->playerdetailsresponse->balance),
				    	"after_balance"=> (int)$client_response->fundtransferresponse->balance,
				    	"player_prefixed"=> $account,
				    	"player_id"=> $user_id
					]
				];
		    	$mw_response = [
	    		"data" => [
	    			"balance" => (int)$client_response->fundtransferresponse->balance,
	    			"currency" => $client_details->default_currency,
	    		],
	    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
	    	];

			ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);

			}elseif(isset($client_response->fundtransferresponse->status->code) 
			            && $client_response->fundtransferresponse->status->code == "402"){
				$mw_response = [
				"data" => null,
					"status" => ["code" => "1005","message" => 'Insufficient Balance',"datetime" => date(DATE_RFC3339)]
				];
				ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED',$general_details);

			}else{ // Unknown Response Code
				$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED',$general_details);
			}

			return $mw_response;
		} catch (\Exception $e) {
			$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerTakeall Failed', $this->provider_db_id, json_encode($request->all()), $e->getMessage());
			return $mw_response;
		}
    }

    public function playerRollin(Request $request){
    	// Helper::saveLog('CQ9 playerRollin Player HIT 1', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	$provider_request = $request->all();
    	Helper::saveLog('CQ9 playerRollin Player HIT 2', $this->provider_db_id, json_encode($request->all()), $header);
    	$check_wtoken = $this->checkAuth($header);
    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	if(!$request->has('account') || !$request->has('eventTime') || !$request->has('createTime') || !$request->has('gamehall') || !$request->has('gamecode') || !$request->has('roundid') || !$request->has('bet') || !$request->has('win') || !$request->has('amount') || !$request->has('mtcode') || !$request->has('gametype')){
    		$mw_response = ["data" => null,"status" => ["code" => "1003","message" => 'Parameter error.',"datetime" => date(DATE_RFC3339)]];
    		Helper::saveLog('CQ9 playerRollin Player', $this->provider_db_id, json_encode($request->all()), $mw_response);
			return $mw_response;
    	}
    	if($request->gametype == 'table'){
    		if(!$request->has('rake')){
    			$mw_response = ["data" => null,"status" => ["code" => "1003","message" => 'Parameter error.',"datetime" => date(DATE_RFC3339)]];
    			Helper::saveLog('CQ9 playerRollin Player', $this->provider_db_id, json_encode($request->all()), $mw_response);
				return $mw_response;
    		}
    	}
    	if(!$this->validRFCDade($request->eventTime) || !$this->validRFCDade($request->createTime)){
    		$mw_response = ["data" => null,"status" => ["code" => "1004","message" => 'Time Format error.',"datetime" => date(DATE_RFC3339)]];
    		Helper::saveLog('CQ9 playerRollin Player', $this->provider_db_id, json_encode($request->all()), $mw_response);
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
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 T ALready Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
		   return $mw_response;
		}
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details == null){
    		$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerRollin Player', $this->provider_db_id, json_encode($request->all()), $mw_response);
			return $mw_response;
    	}
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
		if($player_details == 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerRollin Player', $this->provider_db_id, json_encode($request->all()), $mw_response);
			return $mw_response;
		}
    	if($amount < 0){
   			$mw_response = [
	    		"data" => null,
	    		"status" => ["code" => "1003","message" => 'Amount cannot be negative!',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerRollin Player', $this->provider_db_id, json_encode($request->all()), $mw_response);
			return $mw_response;
   		}
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
		if($game_details == null){
			$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerRollin', $this->provider_db_id, json_encode($request->all()), $mw_response);
			return $mw_response;
		}
		$check_duplicate = ProviderHelper::findGameExt($mtcode, 2, 'transaction_id');
		if($check_duplicate != 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "2009","message" => 'Transaction duplicate',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerRollin', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}	
		$game_ext_check = ProviderHelper::findGameExt($roundid, 1, 'round_id');
		if($game_ext_check == 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1014","message" => 'Transaction record not found',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerRollin ALready Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
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

		    try {
				 $client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$game_ext_check->game_trans_id, 'credit');
				 Helper::saveLog('CQ9 playerRollin CRID = '.$game_ext_check->game_trans_id, $this->provider_db_id, json_encode($provider_request), $client_response);
			} catch (\Exception $e) {
			    $mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $mw_response, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
				Helper::saveLog('CQ9 playerRollin - FATAL ERROR', $this->provider_db_id, $mw_response, Helper::datesent());
				return $mw_response;
			}

		    if(isset($client_response->fundtransferresponse->status->code) 
			             && $client_response->fundtransferresponse->status->code == "200"){
		    	$general_details = [
					"provider" => [
						"createtime" => $createtime,  // The Transaction Created!
						"endtime" => date(DATE_RFC3339),
						"eventtime" => $eventime,
						"action" => $action
					],
					"client" => [
						"before_balance" => $this->amountToFloat4DG($player_details->playerdetailsresponse->balance),
				    	"after_balance"=> $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
				    	"player_prefixed"=> $account,
				    	"player_id"=> $user_id
					]
				];
		    	$mw_response = [
		    		"data" => [
	    				"balance" => $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
		    	];
			
				ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);
				Helper::saveLog('CQ9 playerRollin Success', $this->provider_db_id, json_encode($request->all()), $mw_response);

			}else{ // Unknown Response Code
				$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED',$general_details);
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
    	Helper::saveLog('CQ9 playerBonus', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT 1');
    	$transaction_history = ["provider" =>[], "aggregator" => [], "client"=>[], "general_details"=>[]];
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
	    	Helper::saveLog('CQ9 playerBonus', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	if(!$this->validRFCDade($request->eventTime)){
    		$mw_response = ["data" => null,"status" => ["code" => "1004","message" => 'Time Format error.',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerBonus', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	$check_string_user = ProviderHelper::checkIfHasUnderscore($request->account);
    	if(!$check_string_user){
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerBonus', $this->provider_db_id, json_encode($provider_request), $mw_response);
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
		$action = 'bonus';
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
	    	Helper::saveLog('CQ9 Bet', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}
    	if($amount < 0){
   			$mw_response = [
	    		"data" => null,
	    		"status" => ["code" => "1003","message" => 'Amount cannot be negative and must be positive!',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 Bet', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
   		}
   // 		if($player_details->playerdetailsresponse->balance < $amount){
   // 			$mw_response = [
	  //   		"data" => null,
	  //   		"status" => ["code" => "1005","message" => 'Insufficient Balance',"datetime" => date(DATE_RFC3339)]
	  //   	];
	  //   	Helper::saveLog('CQ9 Bet', $this->provider_db_id, json_encode($provider_request), $mw_response);
			// return $mw_response;
   // 		}
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
		if($game_details == null){
			$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerBonus', $this->provider_db_id, json_encode($request->all()), $mw_response);
			return $mw_response;
		}
		$game_ext_check = ProviderHelper::findGameExt($mtcode, 2, 'transaction_id');
		if($game_ext_check != 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "2009","message" => 'Transaction duplicate',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerBonus', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}	

		try {
			$token_id = $client_details->token_id;
			$bet_amount = 0;
			$pay_amount= $amount;
			$method = 1;
			$win_or_lost = 1;
			$payout_reason = 'BUNOS GAME';
			$income = 0-$amount;
			$provider_trans_id = $mtcode;
			$game_transaction_type = 2;
			$game_id = $game_details->game_id;
	
			$gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_id, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $roundid);
			$game_transextension = ProviderHelper::createGameTransExtV2($gamerecord,$provider_trans_id, $roundid, $amount, $game_transaction_type);

		    try {
				$client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$gamerecord, 'credit');
				 Helper::saveLog('CQ9 playerBonus CRID = '.$gamerecord, $this->provider_db_id, json_encode($provider_request), $client_response);
			} catch (\Exception $e) {
			    $mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $mw_response, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
				Helper::saveLog('CQ9 playerBonus - FATAL ERROR', $this->provider_db_id, $mw_response, Helper::datesent());
				return $mw_response;
			}

		    if(isset($client_response->fundtransferresponse->status->code) 
			             && $client_response->fundtransferresponse->status->code == "200"){
		    	$general_details = [
					"provider" => [
						"createtime" => $createtime,  // The Transaction Created!
						"endtime" => date(DATE_RFC3339),
						"eventtime" => $eventime,
						"action" => $action
					],
					"client" => [
						"before_balance" => $this->amountToFloat4DG($player_details->playerdetailsresponse->balance),
				    	"after_balance"=> $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
				    	"player_prefixed"=> $account,
				    	"player_id"=> $user_id
					]
				];
				$mw_response = [
		    		"data" => [
		    			"balance" => $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
		    	];

		    	ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);

			}else{ // Unknown Response Code
				$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED',$general_details);
			}    

			return $mw_response;
		} catch (\Exception $e) {
			$mw_response = ["data" => [],"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			$transaction_history['general_details']['error'] = $e->getMessage();
			Helper::saveLog('CQ9 playerBonus Failed', $this->provider_db_id, json_encode($transaction_history), $e->getMessage());
			return $mw_response;
		}
    	
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
    	if(!$request->has('account') || !$request->has('eventTime') || !$request->has('amount') || !$request->has('mtcode')){
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
    	$gamecode = $this->game_static_code; // $request->gamecode;
    	// $gamehall = $request->gamehall;
    	$roundid = $request->mtcode; // $request->roundid;
    	$amount = $request->amount;
    	$mtcode = $request->mtcode;
    	$eventime = $request->eventTime; // created
		$createtime = date(DATE_RFC3339);
		$action = 'payoff';

    	$check_string_user = ProviderHelper::checkIfHasUnderscore($request->account);
    	if(!$check_string_user){
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerPayoff', $this->provider_db_id, json_encode($request->all()), $mw_response);
		    return $mw_response;
		}
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details == null){
    		$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerPayoff', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
   		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
   		if($player_details == 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerPayoff', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}
		$game_ext_check = ProviderHelper::findGameExt($mtcode, 1, 'transaction_id');
		if($game_ext_check != 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "2009","message" => 'Transaction duplicate',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerPayoff', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}
    	if($amount < 0){
   			$mw_response = [
	    		"data" => null,
	    		"status" => ["code" => "1003","message" => 'Amount cannot be negative!',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerPayoff', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
   		}
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
		if($game_details == null){
			$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerPayoff', $this->provider_db_id, json_encode($request->all()), $mw_response);
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

		    try {
				$client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$gamerecord, 'credit');
				 Helper::saveLog('CQ9 playerPayoff CRID = '.$gamerecord, $this->provider_db_id, json_encode($provider_request), $client_response);
			} catch (\Exception $e) {
			    $mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $mw_response, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
				Helper::saveLog('CQ9 playerPayoff - FATAL ERROR', $this->provider_db_id, $mw_response, Helper::datesent());
				return $mw_response;
			}

		    if(isset($client_response->fundtransferresponse->status->code) 
			             && $client_response->fundtransferresponse->status->code == "200"){
		    	$general_details = [
					"provider" => [
						"createtime" => $createtime,  // The Transaction Created!
						"endtime" => date(DATE_RFC3339),
						"eventtime" => $eventime,
						"action" => $action
					],
					"client" => [
						"before_balance" => $this->amountToFloat4DG($player_details->playerdetailsresponse->balance),
				    	"after_balance"=> $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
				    	"player_prefixed"=> $account,
				    	"player_id"=> $user_id
					]
				];
				$mw_response = [
		    		"data" => [
		    			"balance" => $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
		    	];
				
				ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);

			}else{ // Unknown Response Code
				$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED',$general_details);
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
		$find_mtcode = $this->findTranPID($mtcode);
		// dd($find_mtcode);
  		if($find_mtcode == 'false'){
  			$mw_response = ["data" => null,"status" => ["code" => "1014","message" => 'Transaction record not found',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerRefund ALready Exist', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
  		}
  		$game_ext_check = ProviderHelper::findGameExt($mtcode, 3, 'round_id');
		if($game_ext_check != 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1015","message" => 'The mtcode record is already refunded',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerRefund = '.$mtcode, $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}	
  		$game_transaction = ProviderHelper::findGameTransaction($find_mtcode->game_trans_id, 'game_transaction');
  		$game_details = ProviderHelper::findGameID($game_transaction->game_id);
  		$user_id = Providerhelper::findTokenID($game_transaction->token_id)->player_id;
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details == null){
    		$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerRefund = '.$mtcode, $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
		if($player_details == 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerRefund', $this->provider_db_id, json_encode($provider_request), $mw_response);
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
			
			$game_transextension = ProviderHelper::createGameTransExtV2($find_mtcode->game_trans_id,$provider_trans_id, $provider_trans_id, $amount, $game_transaction_type);
		    ProviderHelper::updateBetTransaction($game_transaction->round_id, $pay_amount, $income, $win_or_lost, $entry_id);
		   
		    try {
				$client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$find_mtcode->game_trans_id, $transaction_type, true);
				Helper::saveLog('CQ9 playerRefund CRID = '.$find_mtcode->game_trans_id, $this->provider_db_id, json_encode($provider_request), $client_response);
			} catch (\Exception $e) {
			    $mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $mw_response, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
				Helper::saveLog('CQ9 playerRefund - FATAL ERROR', $this->provider_db_id, $mw_response, Helper::datesent());
				return $mw_response;
			}

	   		if(isset($client_response->fundtransferresponse->status->code) 
			             && $client_response->fundtransferresponse->status->code == "200"){
	   			$mw_response = [
		    		"data" => [
		    			"balance" => $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
		    	];

		    	ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response);
		    	// Update The General Details to refund transaction status
    			$game_ext_details = $find_mtcode->general_details;
		        $general_details_bag = json_decode($game_ext_details);
				$general_details_bag->transaction_status = 'refund';
	    	    $general_details = json_decode($game_ext_details);
  
				// Actions along the way
				$addition_action = ['bet', 'debit', 'rollout', 'takeall', 'amends', 'amend'];
				$subtraction_action = ['endround', 'credit', 'rollin', 'bonus', 'payoff', 'wins'];

				$general_details_bag->client->before_balance = $this->amountToFloat4DG($player_details->playerdetailsresponse->balance);
			    $general_details_bag->client->after_balance = $this->amountToFloat4DG($client_response->fundtransferresponse->balance);
				$this->updatecreateGameTransExtGD($find_mtcode->game_trans_ext_id, $general_details_bag);

			}elseif(isset($client_response->fundtransferresponse->status->code) 
			            && $client_response->fundtransferresponse->status->code == "402"){
				$mw_response = [
				"data" => null,
					"status" => ["code" => "1005","message" => 'Insufficient Balance',"datetime" => date(DATE_RFC3339)]
				];

				ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED');

			}else{ // Unknown Response Code
				$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED');
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

    public function noRouteParamPassed(Request $request){
    	$mw_response = ["data" => null,"status" => ["code" => "1003","message" => 'Parameter error.',"datetime" => date(DATE_RFC3339)]
    	];
    	Helper::saveLog('CQ9 No PARAM PASSED', $this->provider_db_id, json_encode($request->all()), $mw_response);
		return $mw_response;
    }


    public function playerRecord(Request $request, $mtcode){
    	$transaction_record = $this->findTranPID($mtcode);

    	// dd($transaction_record);

    	if($transaction_record != 'false'){
    		$game_ext_details = $transaction_record->general_details;
	    	$general_details = json_decode($game_ext_details);
	    	$client_details = Providerhelper::getClientDetails('player_id', $general_details->client->player_id);
			if(isset($general_details->multi_event) && $general_details->multi_event == true){
				$record = [
			    		"data"=>[
				    		"_id" => $transaction_record->game_trans_ext_id,
						    "action" => $general_details->provider->action,
						    "target" => [
						      "account" => $general_details->client->player_prefixed
						    ],
						    "status" => [
						      "createtime" => $general_details->multi_events->me_createtime,
						      "endtime" => $general_details->multi_events->me_endtime,
						      "status" => isset($general_details->transaction_status) ? $general_details->transaction_status : "success",
						      "message" => "success"
						    ],
						    "before" => $this->amountToFloat4DG($general_details->multi_events->before_balance),
						    "balance" => $this->amountToFloat4DG($general_details->multi_events->after_balance),
						    "currency" => $client_details->default_currency,
						    "event" => []
					  ],
					  "status" => [
					    "code" => "0",
					    "message" => "Success",
					    "datetime" => date(DATE_RFC3339)
					  ]
		    	];

		    	foreach ($general_details->multi_events->events as $key) {
		    		array_push($record['data']['event'], $key);
		    	}
			}else{
				$amount = $this->amountToFloat4DG($this->amountToFloat4DG($general_details->client->before_balance)-$this->amountToFloat4DG($general_details->client->after_balance));
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
					      "status" => isset($general_details->transaction_status) ? $general_details->transaction_status : "success",
					      "message" => "success"
					    ],
					    "before" => $this->amountToFloat4DG($general_details->client->before_balance),
					    "balance" => $this->amountToFloat4DG($general_details->client->after_balance),
					    "currency" => $client_details->default_currency,
					    "event" => [
					      [
					        "mtcode" => $mtcode,
					        // "amount" => $amount, // old $transaction_record->amount
					        "amount" => $this->amountToFloat4DG($transaction_record->amount), // old $transaction_record->amount
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
			}
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
    	Helper::saveLog('CQ9 playerRecord Player', $this->provider_db_id, json_encode($record), $mtcode);
    	return $record;
    }
    
    public function playerBets(Request $request){
    	Helper::saveLog('CQ9 playerBets Player', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	$check_wtoken = $this->checkAuth($header);
    	$provider_request = json_decode(file_get_contents("php://input"));
    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}

    	if($provider_request == null){
    		$response = [
	    		"data"=>null,
				"status" => ["code" => "1003","message" => "Parameter error","datetime" => date(DATE_RFC3339)]
	    	];
	    	return $response;
    	}
    	if(!isset($provider_request->account) || !isset($provider_request->gamehall) || !isset($provider_request->gamecode) || !isset($provider_request->data) || !isset($provider_request->createTime)){
    		$response = [
	    		"data"=>null,
				"status" => ["code" => "1003","message" => "Parameter error","datetime" => date(DATE_RFC3339)]
	    	];
	    	return $response;
    	}
    	if(!$this->validRFCDade($provider_request->createTime)){
    		$mw_response = ["data" => null,"status" => ["code" => "1004","message" => 'Time Format error.',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerBets', $this->provider_db_id, file_get_contents("php://input"), $mw_response);
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
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerBets', $this->provider_db_id, json_encode($provider_request), $mw_response);
		   return $mw_response;
		}
    	$user_id = Providerhelper::explodeUsername('_', $account);
    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
    	if($client_details == null){
    		$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerBets', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
		if($player_details == 'false'){
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerBets', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
		}
		$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
		if($game_details == null){
			$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerBets', $this->provider_db_id, json_encode($request->all()), $mw_response);
			return $mw_response;
		}
		try {
			// # MULTI EVENT
			$multi_event_bag = ["events"=>[]];
	    	$multi_event = false;
	    	$gametrans_ext_bag_id = [];
	    	if(count($data_details) > 1){
	    		$multi_event = true;
	    		$multi_event_bag['before_balance'] = $this->amountToFloat4DG($player_details->playerdetailsresponse->balance);
	    		$multi_event_bag['me_createtime'] = $createtime;
			   	$multi_event_bag['action'] = $action;
	    	}
	    	// # END MULTI EVENT
	    	$total_amount = array();
	    	foreach($data_details as $data){
	    		if(!isset($data->mtcode) || !isset($data->amount) || !isset($data->roundid) || !isset($data->eventtime)){
	    			$response = [
			    		"data"=>null,
						"status" => ["code" => "1003","message" => "Parameter error LOOP","datetime" => date(DATE_RFC3339)]
			    	];
			    	return $response;
	    		}
	    		if(!$this->validRFCDade($data->eventtime)){
		    		$mw_response = ["data" => null,"status" => ["code" => "1004","message" => 'Time Format error.',"datetime" => date(DATE_RFC3339)]
			    	];
					return $mw_response;
		    	}
	    		if($data->amount < 0){
		   			$mw_response = [
			    		"data" => null,
			    		"status" => ["code" => "1003","message" => 'Amount cannot be negative!',"datetime" => date(DATE_RFC3339)]
			    	];
					return $mw_response;
		   		}
		   		if($player_details->playerdetailsresponse->balance < $data->amount){
		   			$mw_response = [
			    		"data" => null,"status" => ["code" => "1005","message" => 'Insufficient Balance',"datetime" => date(DATE_RFC3339)]
			    	];
			    	Helper::saveLog('CQ9 playerBets', $this->provider_db_id, json_encode($provider_request), $mw_response);
					return $mw_response;
		   		}
		   		$game_ext_check = ProviderHelper::findGameExt($data->mtcode, 1, 'transaction_id');
				if($game_ext_check != 'false'){
					$mw_response = ["data" => [],"status" => ["code" => "2009","message" => 'Transaction duplicate',"datetime" => date(DATE_RFC3339)]];
					Helper::saveLog('CQ9 PlayerBets', $this->provider_db_id, json_encode($provider_request), $mw_response);
					return $mw_response;
				}	
	    		array_push($total_amount, $data->amount);
	    	}	
			$total_amount = array_sum($total_amount);
	    	if($player_details->playerdetailsresponse->balance < $total_amount){
	   			$mw_response = [
		    		"data" => null,"status" => ["code" => "1005","message" => 'Insufficient Balance',"datetime" => date(DATE_RFC3339)]
		    	];
		    	Helper::saveLog('CQ9 playerBets', $this->provider_db_id, json_encode($provider_request), $mw_response);
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
	    		array_push($gametrans_ext_bag_id, $game_transextension); // # MULTI EVENT

	    		 try {
					 $client_response = ClientRequestHelper::fundTransfer($client_details,$data->amount,$game_details->game_code,$game_details->game_name,$game_transextension,$gamerecord, 'debit');
					 Helper::saveLog('CQ9 playerBets CRID = '.$gamerecord, $this->provider_db_id, json_encode($provider_request), $client_response);
				} catch (\Exception $e) {
				    $mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
					ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $mw_response, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
					Helper::saveLog('CQ9 playerBets - FATAL ERROR', $this->provider_db_id, $mw_response, Helper::datesent());
					return $mw_response;
				}
			    

			    if(isset($client_response->fundtransferresponse->status->code) 
				             && $client_response->fundtransferresponse->status->code == "200"){
	    			$multi_event_bag['after_balance'] = $this->amountToFloat4DG($client_response->fundtransferresponse->balance);
			    	// # MULTI EVENT
			    	$multi_event_array = [
			    		"mtcode" => $provider_trans_id,
		                "amount" => $data->amount,
		                "eventtime" => $data->eventtime
			    	];
			    	array_push($multi_event_bag['events'], $multi_event_array);
			    	// # END MULTI EVENT
			    	
			    	$general_details = [
			    		"multi_event" => $multi_event, // MULTI EVENT
						"provider" => [
							"createtime" => $createtime,  // The Transaction Created!
							"endtime" => date(DATE_RFC3339),
							"eventtime" => $eventime,
							"action" => $action
						],
						"client" => [
							"before_balance" => $this->amountToFloat4DG($player_details->playerdetailsresponse->balance),
					    	"after_balance"=> $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
					    	"player_prefixed"=> $account,
					    	"player_id"=> $user_id
						]
					];
					$mw_response = [
			    		"data" => [
			    			"balance" => $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
			    			"currency" => $client_details->default_currency,
			    		],
			    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
			    	];
			 	   
			 	    ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);

				}elseif(isset($client_response->fundtransferresponse->status->code) 
				            && $client_response->fundtransferresponse->status->code == "402"){
					$multi_event_bag['after_balance'] = $this->amountToFloat4DG($client_response->fundtransferresponse->balance);
					$mw_response = [
			    		"data" => null,"status" => ["code" => "1005","message" => 'Insufficient Balance',"datetime" => date(DATE_RFC3339)]
			    	];
			    	ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED',$general_details);
			    	Helper::saveLog('CQ9 playerBets', $this->provider_db_id, json_encode($provider_request), $mw_response);
					return $mw_response;
				}else{ // Unknown Response Code
					$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
					ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED',$general_details);
					return $mw_response;
				}    

	    	}	

	    	// # MULTI EVENT
	    	$multi_event_bag['me_endtime'] = date(DATE_RFC3339);
	    	if($multi_event == true){
	    		foreach ($gametrans_ext_bag_id as $key) {
	    			$transaction_record = $this->findGameExtByID($key);
	    			$game_ext_details = $transaction_record->general_details;
	    	        $general_details_bag = json_decode($game_ext_details);
	    			$general_details_bag->multi_events = $multi_event_bag;
	    			$this->updatecreateGameTransExtGD($key, $general_details_bag);
	    		}
	    	}
	    	// # END MULTI EVENT

			$mw_response = [ // LAST LOOP RESPONSE
	    		"data" => [
	    			"balance" => $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
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
    	Helper::saveLog('CQ9 playerRefunds - EH', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	$provider_request = json_decode(file_get_contents("php://input"));
    	$check_wtoken = $this->checkAuth($header);
    	$createtime = date(DATE_RFC3339);
		$action = 'refunds';

    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	if($provider_request == null){
    		$response = [
	    		"data"=>null,
				"status" => ["code" => "1003","message" => "Parameter error","datetime" => date(DATE_RFC3339)]
	    	];
	    	return $response;
    	}
    	if(!isset($provider_request->mtcode)){
    		$response = [
	    		"data"=>null,
				"status" => ["code" => "1003","message" => "Parameter error","datetime" => date(DATE_RFC3339)]
	    	];
	    	return $response;
    	}
	    if(count($provider_request->mtcode) == 0){
	    	$response = [
	    		"data"=>null,
				"status" => ["code" => "1003","message" => "Parameter error","datetime" => date(DATE_RFC3339)]
	    	];
	    	return $response;
	    }

    	// # MULTI EVENT
		$multi_event_bag = ["events"=>[]];
    	$multi_event = false;
    	$gametrans_ext_bag_id = [];
		$multi_event = true;
		$multi_event_bag['me_createtime'] = $createtime;
	   	$multi_event_bag['action'] = $action;
    	// # END MULTI EVENT
    	$mtcodes = $provider_request;
    	$mtcode_request_list = array();

		$existing_multi_event = array();
		$existing_multi_event_set = false;  // Flag 1

    	foreach($mtcodes->mtcode as $mt){
	  		$game_ext_check = ProviderHelper::findGameExt($mt, 3, 'transaction_id');
			if($game_ext_check != 'false'){
				$mw_response = ["data" => null,"status" => ["code" => "1015","message" => $game_ext_check->provider_trans_id.' is already refunded',"datetime" => date(DATE_RFC3339)]
		    	];
		    	Helper::saveLog('CQ9 playerRefunds', $this->provider_db_id, json_encode($provider_request), $mw_response);
				return $mw_response;
			}
			$find_mtcode = $this->findTranPID($mt);
	  		if($find_mtcode == 'false'){
	  			$mw_response = ["data" => null,"status" => ["code" => "1014","message" => 'Transaction record not found',"datetime" => date(DATE_RFC3339)]];
				Helper::saveLog('CQ9 playerRefunds', $this->provider_db_id, json_encode($provider_request), $mw_response);
				return $mw_response;
	  		}
    		$general_details = json_decode($find_mtcode->general_details);
			if($existing_multi_event_set == false){ // Flag 1
			  array_push($existing_multi_event, $general_details->multi_events->events);
			  $existing_multi_event_set = true;
			}
			array_push($mtcode_request_list, $mt);
    	}

    	// return 1;
    	// AND AUTOMATIC THE EVENT THAT NOT IN THE REFUNDLIST
    	foreach ($existing_multi_event[0] as $key => $value) {
    		$find_mtcode_bags = $this->findTranPID($value->mtcode); // QRRY 1
    		if($find_mtcode_bags != 'false'){
    			array_push($gametrans_ext_bag_id, $find_mtcode_bags->game_trans_ext_id); // QRRY 2
    		}
			foreach ($mtcodes->mtcode as $mt) {
				if(!in_array($value->mtcode, $mtcode_request_list)){
					$multi_event_array = [
			    		"mtcode" => $value->mtcode,
		                "amount" => $value->amount,
		                "eventtime" => $value->eventtime,
			    	];
			    	if(isset($value->status)){
			    		$multi_event_array['status'] = 'refund';
			    	}
					array_push($multi_event_bag['events'], $multi_event_array);
				}
			}
		}
		// END AND AUTOMATIC THE EVENT THAT NOT IN THE REFUNDLIST

    	$multi_event_before_balance_set = false; // Multi Event flag 2
    	foreach($mtcodes->mtcode as $mt){
    		$find_mtcode = $this->findTranPID($mt);
    		$game_transaction = ProviderHelper::findGameTransaction($find_mtcode->game_trans_id, 'game_transaction');
			
	  		$game_details = ProviderHelper::findGameID($game_transaction->game_id);
	  		$user_id = Providerhelper::findTokenID($game_transaction->token_id)->player_id;
	    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
	    	$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
			if($player_details == 'false'){
				$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
		    	];
		    	Helper::saveLog('CQ9 playerBets', $this->provider_db_id, json_encode($provider_request), $mw_response);
				return $mw_response;
			}

			if ($multi_event_before_balance_set == false) { // flag 2
				$multi_event_bag['before_balance'] = $this->amountToFloat4DG($player_details->playerdetailsresponse->balance);
				$multi_event_before_balance_set = true;
			}

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
	 	    array_push($gametrans_ext_bag_id, $game_transextension); // # MULTI EVENT

	 	    try {
				$client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$find_mtcode->game_trans_id, $transaction_type, true);
				 Helper::saveLog('CQ9 playerRefunds CRID = '.$find_mtcode->game_trans_id, $this->provider_db_id, json_encode($provider_request), $client_response);
			} catch (\Exception $e) {
			    $mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $mw_response, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
				Helper::saveLog('CQ9 playerRefunds - FATAL ERROR', $this->provider_db_id, $mw_response, Helper::datesent());
				return $mw_response;
			}


	  	    if(isset($client_response->fundtransferresponse->status->code) 
			             && $client_response->fundtransferresponse->status->code == "200"){
	  	    	$multi_event_bag['after_balance'] = $this->amountToFloat4DG($client_response->fundtransferresponse->balance);
	  	    	# MULTI EVENT
		    	foreach ($existing_multi_event[0] as $key => $value) {
	    			if($value->mtcode == $mt){
	    				$multi_event_array = [
				    		"mtcode" => $value->mtcode,
			                "amount" => $value->amount,
			                "eventtime" => $value->eventtime,
			                "status" => 'refund'
				    	];
						array_push($multi_event_bag['events'], $multi_event_array);
	    			}
	    		}
		    	// # END MULTI EVENT

  	    		$game_ext_details = $find_mtcode->general_details;
		        $general_details_bag = json_decode($game_ext_details);
				$general_details_bag->transaction_status = 'refund';
	    	    $general_details = json_decode($game_ext_details);
				$general_details_bag->client->before_balance = $this->amountToFloat4DG($player_details->playerdetailsresponse->balance);
			    $general_details_bag->client->after_balance = $this->amountToFloat4DG($client_response->fundtransferresponse->balance);
				$this->updatecreateGameTransExtGD($find_mtcode->game_trans_ext_id, $general_details_bag);

	  	    	$general_details = [
	  	    		"multi_event" => true,
	  	    		"transaction_status" =>  'success', // refund
					"provider" => [
						"description" => 'Refunded Bets',
						"refund_type" => 'refund_bets',
						"createtime" => $createtime,  // The Transaction Created!
						"endtime" => date(DATE_RFC3339),
						"action" => $action
					],
					"client" => [
						"description" => 'SENDED DATA TO CLIENT!',
						"transaction_type" => $transaction_type,
						"before_balance" => $this->amountToFloat4DG($player_details->playerdetailsresponse->balance),
		    	        "after_balance"=> $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
						"amount" => $amount,
						"player_prefixed"=> $this->prefix.'_'.$user_id,
						"player_id"=> $user_id
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
		    			"balance" => $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
		    	];
			   
			   	ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);

			}elseif(isset($client_response->fundtransferresponse->status->code) 
			            && $client_response->fundtransferresponse->status->code == "402"){
				$multi_event_bag['after_balance'] = $this->amountToFloat4DG($client_response->fundtransferresponse->balance);
				$mw_response = [
		    		"data" => null,"status" => ["code" => "1005","message" => 'Insufficient Balance',"datetime" => date(DATE_RFC3339)]
		    	];
		    	ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED' ,$general_details);
		    	return $mw_response;
			}else{ // Unknown Response Code
				$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED' ,$general_details);
				return $mw_response;
			}    
		}

		// # MULTI EVENT
    	
    	$multi_event_bag['me_endtime'] = date(DATE_RFC3339);
    	if($multi_event == true){
    		foreach ($gametrans_ext_bag_id as $key) {
    			$transaction_record = $this->findGameExtByID($key);
    			$game_ext_details = $transaction_record->general_details;
    	        $general_details_bag = json_decode($game_ext_details);
    			$general_details_bag->multi_events = $multi_event_bag;
    			$this->updatecreateGameTransExtGD($key, $general_details_bag);
    		}
    	}
    	// # END MULTI EVENT

		$mw_response = [ // LAST LOOP RESPONSE
    		"data" => [
    			"balance" => $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
    			"currency" => $client_details->default_currency,
    		],
    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
    	];
		return $mw_response;
    }

    public function playerCancel(Request $request){
    	Helper::saveLog('CQ9 playerCancel - EH', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	$provider_request = json_decode(file_get_contents("php://input"));
    	$check_wtoken = $this->checkAuth($header);
    	$createtime = date(DATE_RFC3339);
		$action = 'cancel';

    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	if($provider_request == null){
    		$response = [
	    		"data"=>null,
				"status" => ["code" => "1003","message" => "Parameter error","datetime" => date(DATE_RFC3339)]
	    	];
	    	return $response;
    	}
    	if(!isset($provider_request->mtcode)){
    		$response = [
	    		"data"=>null,
				"status" => ["code" => "1003","message" => "Parameter error","datetime" => date(DATE_RFC3339)]
	    	];
	    	return $response;
    	}
    	if(count($provider_request->mtcode) == 0){
	    	$response = [
	    		"data"=>null,
				"status" => ["code" => "1003","message" => "Parameter error","datetime" => date(DATE_RFC3339)]
	    	];
	    	return $response;
	    }


    	// # MULTI EVENT
		$multi_event_bag = ["events"=>[]];
    	$multi_event = false;
    	$gametrans_ext_bag_id = [];
		$multi_event = true;
		$multi_event_bag['me_createtime'] = $createtime;
	   	$multi_event_bag['action'] = $action;
    	// # END MULTI EVENT
    	$mtcodes = $provider_request;
    	$mtcode_request_list = array();

		$existing_multi_event = array();
		$existing_multi_event_set = false;  // Flag 1
    	foreach($mtcodes->mtcode as $mt){
    		$find_mtcode = $this->findTranPID($mt);
	  		if($find_mtcode == 'false'){
	  			$mw_response = ["data" => null,"status" => ["code" => "1014","message" => 'Transaction record not found',"datetime" => date(DATE_RFC3339)]];
				Helper::saveLog('CQ9 playerCancel', $this->provider_db_id, json_encode($provider_request), $mw_response);
				return $mw_response;
	  		}
    		$general_details = json_decode($find_mtcode->general_details);

	  // 		$game_ext_check = ProviderHelper::findGameExt($mt, 3, 'transaction_id');
			// if($game_ext_check != 'false'){
			// 	$mw_response = ["data" => null,"status" => ["code" => "1015","message" => $find_mtcode->provider_trans_id.'is already refunded',"datetime" => date(DATE_RFC3339)]
		 //    	];
		 //    	Helper::saveLog('CQ9 playerCancel', $this->provider_db_id, json_encode($provider_request), $mw_response);
			// 	return $mw_response;
			// }
			if($existing_multi_event_set == false){ // Flag 1
			  array_push($existing_multi_event, $general_details->multi_events->events);
			  $existing_multi_event_set = true;
			}
			array_push($mtcode_request_list, $mt);
    	}


    	// AND AUTOMATIC THE EVENT THAT NOT IN THE REFUNDLIST
    	foreach ($existing_multi_event[0] as $key => $value) {
    		$find_mtcode_bags = $this->findTranPID($value->mtcode); // QRRY 1
    		if($find_mtcode_bags != 'false'){
    			array_push($gametrans_ext_bag_id, $find_mtcode_bags->game_trans_ext_id); // QRRY 2
    		}
			foreach ($mtcodes->mtcode as $mt) {
				if(!in_array($value->mtcode, $mtcode_request_list)){
					$multi_event_array = [
			    		"mtcode" => $value->mtcode,
		                "amount" => $value->amount,
		                "eventtime" => $value->eventtime,
			    	];
			    	if(isset($value->status)){
			    		$multi_event_array['status'] = 'cancel';
			    	}
					array_push($multi_event_bag['events'], $multi_event_array);
				}
			}
		}
		// END AND AUTOMATIC THE EVENT THAT NOT IN THE REFUNDLIST

    	$multi_event_before_balance_set = false; // Multi Event flag 2
    	foreach($mtcodes->mtcode as $mt){
    		$find_mtcode = $this->findTranPID($mt);
    		$game_transaction = ProviderHelper::findGameTransaction($find_mtcode->game_trans_id, 'game_transaction');
			
	  		$game_details = ProviderHelper::findGameID($game_transaction->game_id);
	  		$user_id = Providerhelper::findTokenID($game_transaction->token_id)->player_id;
	    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
	    	$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
			if($player_details == 'false'){
				$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]
		    	];
		    	Helper::saveLog('CQ9 playerBets', $this->provider_db_id, json_encode($provider_request), $mw_response);
				return $mw_response;
			}

			if ($multi_event_before_balance_set == false) { // flag 2
				$multi_event_bag['before_balance'] = $this->amountToFloat4DG($player_details->playerdetailsresponse->balance);
				$multi_event_before_balance_set = true;
			}

			// CANCEL
			$game_ext_details = $find_mtcode->general_details;
	    	$general_details = json_decode($game_ext_details);
	    	if($general_details->provider->refund_type == 'cancel_refund'){
	    		// $mw_response = ["data" => null,"status" => ["code" => "2009","message" => 'Transaction duplicate',"datetime" => date(DATE_RFC3339)]];
	    		$mw_response = ["data" => null,"status" => ["code" => "1015","message" => 'Transaction Already Cancelled',"datetime" => date(DATE_RFC3339)]];
				Helper::saveLog('CQ9 playerCancel Refund = '.$mt, $this->provider_db_id, json_encode($provider_request), $mw_response);
				return $mw_response;
	    	}
			if($general_details->client->transaction_type == 'credit'){ // WIN SHOULD BE REFUNDED AS CREDIT
				$transaction_type = 'debit';
			}else if($general_details->client->transaction_type == 'debit'){ // BET SHOULD BE REFUNDED AS DEBIT
				$transaction_type = 'credit';
			}
			//CANCEL
			if($general_details->client->transaction_type == 'credit'){ // WIN SHOULD BE REFUNDED AS CREDIT
				$transaction_type = 'debit';
			}else if($general_details->client->transaction_type == 'debit'){ // BET SHOULD BE REFUNDED AS DEBIT
				$transaction_type = 'credit';
			}

			$game_ext_check = ProviderHelper::findGameExt($mt, 3, 'transaction_id');
			
			$amount = $game_ext_check->amount; //$general_details->client->amount;
			$pay_amount = $general_details->old_transaction->pay_amount;
			$income = $general_details->old_transaction->income;
			$win_or_lost = 4;
			$entry_id = $general_details->old_transaction->entry_id;
	    	$token_id = $client_details->token_id;
			$payout_reason = 'CANCEL REFUND ROUND';
			$provider_trans_id = $mt;
			$game_transaction_type = 3;

			// dd($game_ext_check);
			// return 1;
			ProviderHelper::updateBetTransaction($game_transaction->round_id, $pay_amount, $income, $win_or_lost, $entry_id);
	 	    $game_transextension = ProviderHelper::createGameTransExtV2($find_mtcode->game_trans_id,$provider_trans_id, $provider_trans_id, $amount, $game_transaction_type);
	 	    array_push($gametrans_ext_bag_id, $game_transextension); // # MULTI EVENT

	 	    try {
				$client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$find_mtcode->game_trans_id, $transaction_type, true);
				 Helper::saveLog('CQ9 playerCancel CRID = '.$find_mtcode->game_trans_id, $this->provider_db_id, json_encode($provider_request), $client_response);
			} catch (\Exception $e) {
			    $mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $mw_response, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
				Helper::saveLog('CQ9 playerCancel - FATAL ERROR', $this->provider_db_id, $mw_response, Helper::datesent());
				return $mw_response;
			}

	  	    if(isset($client_response->fundtransferresponse->status->code) 
			             && $client_response->fundtransferresponse->status->code == "200"){
	  	    	$multi_event_bag['after_balance'] = $this->amountToFloat4DG($client_response->fundtransferresponse->balance);
	  	    	# MULTI EVENT
		    	foreach ($existing_multi_event[0] as $key => $value) {
	    			if($value->mtcode == $mt){
	    				$multi_event_array = [
				    		"mtcode" => $value->mtcode,
			                "amount" => $value->amount,
			                "eventtime" => $value->eventtime,
			                "status" => 'cancel'
				    	];
						array_push($multi_event_bag['events'], $multi_event_array);
	    			}
	    		}
		    	// # END MULTI EVENT

  	    		$game_ext_details = $find_mtcode->general_details;
		        $general_details_bag = json_decode($game_ext_details);
				$general_details_bag->transaction_status = 'cancel';
	    	    $general_details = json_decode($game_ext_details);
				$general_details_bag->client->before_balance = $this->amountToFloat4DG($player_details->playerdetailsresponse->balance);
			    $general_details_bag->client->after_balance = $this->amountToFloat4DG($client_response->fundtransferresponse->balance);
				$this->updatecreateGameTransExtGD($find_mtcode->game_trans_ext_id, $general_details_bag);

	  	    	$general_details = [
	  	    		"multi_event" => true,
	  	    		"transaction_status" =>  'success', // refund
					"provider" => [
						"description" => 'Cancel Refund',
						"refund_type" => 'cancel_refund',
						"createtime" => $createtime,  // The Transaction Created!
						"endtime" => date(DATE_RFC3339),
						"action" => $action
					],
					"client" => [
						"description" => 'SENDED DATA TO CLIENT!',
						"transaction_type" => $transaction_type,
						"before_balance" => $this->amountToFloat4DG($player_details->playerdetailsresponse->balance),
		    	        "after_balance"=> $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
						"amount" => $amount,
						"player_prefixed"=> $this->prefix.'_'.$user_id,
						"player_id"=> $user_id
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
		    			"balance" => $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
		    	];
			   
			   	ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);

			}elseif(isset($client_response->fundtransferresponse->status->code) 
			            && $client_response->fundtransferresponse->status->code == "402"){
				$multi_event_bag['after_balance'] = $this->amountToFloat4DG($client_response->fundtransferresponse->balance);
				$mw_response = [
		    		"data" => null,"status" => ["code" => "1005","message" => 'Insufficient Balance',"datetime" => date(DATE_RFC3339)]
		    	];
		    	ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED' ,$general_details);
		    	return $mw_response;
			}else{ // Unknown Response Code
				$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED' ,$general_details);
				return $mw_response;
			}    
		}

		// # MULTI EVENT
    	
    	$multi_event_bag['me_endtime'] = date(DATE_RFC3339);
    	if($multi_event == true){
    		foreach ($gametrans_ext_bag_id as $key) {
    			$transaction_record = $this->findGameExtByID($key);
    			$game_ext_details = $transaction_record->general_details;
    	        $general_details_bag = json_decode($game_ext_details);
    			$general_details_bag->multi_events = $multi_event_bag;
    			$this->updatecreateGameTransExtGD($key, $general_details_bag);
    		}
    	}
    	// # END MULTI EVENT

		$mw_response = [ // LAST LOOP RESPONSE
    		"data" => [
    			"balance" => $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
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
    	// $data_details = $this->rawToObj($request->event, true);
    	$provider_request = json_decode(file_get_contents("php://input"));
    	$check_wtoken = $this->checkAuth($header);
    	$createtime = date(DATE_RFC3339);
		$action = 'wins';

    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 playerWins', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}

    	if($provider_request == null){
    		$response = [
	    		"data"=>null,
				"status" => ["code" => "1003","message" => "Parameter error","datetime" => date(DATE_RFC3339)]
	    	];
	    	return $response;
    	}

    	if(!isset($provider_request->list)){
    		$response = [
	    		"data"=>null,
				"status" => ["code" => "1003","message" => "Parameter error","datetime" => date(DATE_RFC3339)]
	    	];
	    	return $response;
    	}

    	$data_details = $provider_request->list;
    	
		try {

			// # MULTI EVENT
			$multi_event_bag = ["events"=>[]];
	    	$multi_event = false;
	    	$gametrans_ext_bag_id = [];
	    
    		$multi_event_bag['me_createtime'] = $createtime;
		   	$multi_event_bag['action'] = $action;

	    	// # END MULTI EVENT

			$response = ["data" => ["success" => [],"failed" => [],],"status" =>  ["code" =>  "0","message" =>  "Success","datetime" => ""]];
	    	foreach($data_details as $key => $data){

	    		if(!isset($data->account) || !isset($data->event) || !isset($data->eventtime) || !isset($data->ucode)){
			    	$failed = [
			    		"account" => isset($data->account) ? $data->account : '',
			    		"code" => "1003",
			    		"message" =>"Parameter error",
			    		"ucode" => isset($data->ucode) ? $data->ucode : ''
			    	];
		    		array_push($response['data']['failed'], $failed);
		    		$response['status']['datetime'] = date(DATE_RFC3339);
	    		    return $response;
		    	}

		    	if(!$this->validRFCDade($data->eventtime)){
		    		$failed = [
			    		"account" => isset($data->account) ? $data->account : '',
			    		"code" => "1004",
			    		"message" =>"Wrong Time Format",
			    		"ucode" => isset($data->ucode) ? $data->ucode : ''
			    	];
		    		array_push($response['data']['failed'], $failed);
		    		$response['status']['datetime'] = date(DATE_RFC3339);
	    		    return $response;
		    	}

	    		$ucode = $data->ucode;
	    		$account = $data->account;
	    		$action = 'wins';
	    		$eventtime = $data->eventtime;
				$createtime = date(DATE_RFC3339);
				$check_string_user = ProviderHelper::checkIfHasUnderscore($data->account);
		    	if(!$check_string_user){
		    	    $failed = [
			    		"account" => isset($data->account) ? $data->account : '',
			    		"code" => "1006",
			    		"message" =>"Player not found",
			    		"ucode" => isset($data->ucode) ? $data->ucode : ''
			    	];
		    	   array_push($response['data']['failed'], $failed);
				   $response['status']['datetime'] = date(DATE_RFC3339);
	    		   return $response;
		    	}
	    		$user_id = Providerhelper::explodeUsername('_', $data->account);
		    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
				if($client_details == null){
					$failed = [
			    		"account" => isset($data->account) ? $data->account : '',
			    		"code" => "1006",
			    		"message" =>"Player not found",
			    		"ucode" => isset($data->ucode) ? $data->ucode : ''
			    	];
		    		array_push($response['data']['failed'], $failed);
		    		$response['status']['datetime'] = date(DATE_RFC3339);
	    		    return $response;
				}
				$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
				if($player_details == 'false'){
					$failed = [
			    		"account" => isset($data->account) ? $data->account : '',
			    		"code" => "1006",
			    		"message" =>"Player not found",
			    		"ucode" => isset($data->ucode) ? $data->ucode : ''
			    	];
		    		array_push($response['data']['failed'], $failed);
		    		$response['status']['datetime'] = date(DATE_RFC3339);
	    		    return $response;
				}
				// MULTI EVENT
				$multi_event_bag['before_balance'] = $this->amountToFloat4DG($player_details->playerdetailsresponse->balance);
				// END MULTI EVENT
				if(count($data->event) > 1){
    					$multi_event = true;
				}
				foreach ($data->event as $key => $value) {
					$da_error = 0;

					if(!isset($value->mtcode) || !isset($value->amount) || !isset($value->validbet) || !isset($value->roundid) || !isset($value->eventtime) || !isset($value->gamecode) || !isset($value->gamehall)){
				    	$failed = ["account" => $account,"code" =>"1003","message" =>"Parameter error.","ucode" => $ucode];
						array_push($response['data']['failed'], $failed);
			    		$da_error = 1;
			    		continue;
			    	}
			    	if(!$this->validRFCDade($value->eventtime)){
				    	$failed = ["account" => $account,"code" =>"1004","message" =>"Time Format error.","ucode" => $ucode];
						array_push($response['data']['failed'], $failed);
			    		$da_error = 1;
			    		continue;
			    	}
					$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $value->gamecode);
		    		if($value->amount < 0 AND $da_error == 0){
		    			$failed = ["account" => $account,"code" =>"1003","message" =>"Amount cannot be negative","ucode" => $ucode];
			    		array_push($response['data']['failed'], $failed);
			    		$da_error = 1;
			    		continue;
			   		}
			   		// $game_ext_check = ProviderHelper::findGameExt($value->mtcode, 1, 'transaction_id');
					$game_ext_check_win = ProviderHelper::findGameExt($value->mtcode, 2, 'transaction_id');
					if($game_ext_check_win != 'false' AND $da_error == 0){
						$failed = ["account" => $account,"code" =>"2009","message" =>"Transaction duplicate","ucode" => $ucode];
			    		Helper::saveLog('CQ9 T ALready Exist', $this->provider_db_id, json_encode($provider_request), $failed);
			    		array_push($response['data']['failed'], $failed);
			    		$da_error = 1;
			    		continue;
					}
					$game_ext_check = ProviderHelper::findGameExt($value->roundid, 1, 'round_id');
					if($game_ext_check == 'false' AND $da_error == 0){
						$failed = ["account" => $account,"code" =>"1014","message" =>"Transaction record not found","ucode" => $ucode];
			    		Helper::saveLog('CQ9 T ALready Exist', $this->provider_db_id, json_encode($provider_request), $failed);
			    		array_push($response['data']['failed'], $failed);
			    		$da_error = 1;
			    		continue;
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
						array_push($gametrans_ext_bag_id, $game_transextension); // # MULTI EVENT
					    
					    try {
							$client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$game_ext_check->game_trans_id, 'credit');
							 Helper::saveLog('CQ9 playerWins CRID = '.$game_ext_check->game_trans_id, $this->provider_db_id, json_encode($provider_request), $client_response);
						} catch (\Exception $e) {
							ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $response, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
							$failed = ["account" => $account,"code" =>"1100","message" =>"Server error","ucode" => $ucode];
							array_push($response['data']['failed'], $failed);
							Helper::saveLog('CQ9 playerWins - FATAL ERROR', $this->provider_db_id, $response, Helper::datesent());
				    		continue;
						}


					     if(isset($client_response->fundtransferresponse->status->code) 
						             && $client_response->fundtransferresponse->status->code == "200"){

					     	// # MULTI EVENT
					    	$multi_event_array = [
					    		"mtcode" => $value->mtcode,
				                "amount" => $value->amount,
				                "eventtime" => $value->eventtime
					    	];
					    	array_push($multi_event_bag['events'], $multi_event_array);
					    	// # END MULTI EVENT

					     	$general_details = [
					     		"multi_event" => $multi_event, // MULTI EVENT
								"provider" => [
									"createtime" => $createtime,  // The Transaction Created!
									"endtime" => date(DATE_RFC3339),
									"eventtime" => $eventtime,
									"action" => $action
								],
								"client" => [
									"description" => 'SENDED DATA TO CLIENT!',
									"before_balance" => $this->amountToFloat4DG($player_details->playerdetailsresponse->balance),
							    	"after_balance"=> $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
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
				    				"balance" => $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
					    			"currency" => $client_details->default_currency,
					    		],
					    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
					    	];
						
						    ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);
					 	    $success = ["account" => $account,"balance" =>$this->amountToFloat4DG($client_response->fundtransferresponse->balance),"currency" => $client_details->default_currency,"ucode" => $ucode];
			    		    array_push($response['data']['success'], $success);

						}else{ // Unknown Response Code
							$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
							roviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED',$general_details);
							return $mw_response;
						}    
					}
				}
	    	}	

	    	// # MULTI EVENT
	    	$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
	    	$multi_event_bag['after_balance'] = $player_details->playerdetailsresponse->balance;
	    	$multi_event_bag['me_endtime'] = date(DATE_RFC3339);
	    	if($multi_event == true){
	    		foreach ($gametrans_ext_bag_id as $key) {
	    			$transaction_record = $this->findGameExtByID($key);
	    			$game_ext_details = $transaction_record->general_details;
	    	        $general_details_bag = json_decode($game_ext_details);
	    			$general_details_bag->multi_events = $multi_event_bag;
	    			$this->updatecreateGameTransExtGD($key, $general_details_bag);
	    		}
	    	}
	    	// # MULTI EVENT
	
			$success_to_return = count($response['data']['success']);
	    	if($success_to_return > 1){
	    		$response['data']['success'] = [$response['data']['success'][$success_to_return-1]];
	    	}
	    	$failed_to_return = count($response['data']['failed']);
	    	if($failed_to_return > 1){
	    		$response['data']['failed'] = [$response['data']['failed'][$failed_to_return-1]];
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
    	$provider_request = json_decode(file_get_contents("php://input"));
    	$createtime = date(DATE_RFC3339);
		$action = 'amends';
    	$response = ["data" => ["success" => [],"failed" => [],],"status" =>  ["code" =>  "0","message" =>  "Success","datetime" => date(DATE_RFC3339)]];

    	$check_wtoken = $this->checkAuth($header);
    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			Helper::saveLog('CQ9 Error Token', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
    	}
    	if($provider_request == null){
    // 		$response = [
	   //  		"data"=>null,
				// "status" => ["code" => "1003","message" => "Parameter error","datetime" => date(DATE_RFC3339)]
	   //  	];
	    	$failed = ["account" => isset($data->account) ? $data->account : '',"code" =>"1003","message" =>"Parameter error (Body Cannot Be Empty","ucode" => isset($data->ucode) ? $data->ucode : ''];
	    	array_push($response['data']['failed'], $failed);
	    	return $response;
    	}

    	$data_details = $provider_request->list;
		$mtcodes = $provider_request;

    	// # MULTI EVENT
		$multi_event_bag = ["events"=>[]];
    	$multi_event = false;
    	$gametrans_ext_bag_id = [];
		$multi_event = true;
		$multi_event_bag['me_createtime'] = $createtime;
	   	$multi_event_bag['action'] = $action;

    	// # END MULTI EVENT
    	$mtcode_request_list = array();
		$existing_multi_event = array();
		$existing_multi_event_set = false;
		$set_before_balance = false;

    	// FIRST LOOP CHECKER/SETTER
    	foreach($data_details as $key => $data){
			if(!isset($data->account) || !isset($data->event) || !isset($data->eventtime) || !isset($data->amount) || !isset($data->action) || !isset($data->ucode)){
		   //  	$response = [
		   //  		"data"=>null,
					// "status" => ["code" => "1003","message" => "Parameter error","datetime" => date(DATE_RFC3339)]
		   //  	];
		   //  	return $response;
		    	$failed = ["account" => isset($data->account) ? $data->account : '',"code" =>"1003","message" =>"Parameter error","ucode" => isset($data->ucode) ? $data->ucode : ''];
	    		array_push($response['data']['failed'], $failed);
	    		return $response;

	    	}
	    	$check_string_user = ProviderHelper::checkIfHasUnderscore($data->account);
	    	if(!$check_string_user){
				// $mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]];
			 //   return $mw_response;
			    $failed = ["account" => isset($data->account) ? $data->account : '',"code" =>"1006","message" =>"Player Not Found","ucode" => isset($data->ucode) ? $data->ucode : ''];
	    	    array_push($response['data']['failed'], $failed);
	    	    return $response;
	    	}
	    	$user_id = Providerhelper::explodeUsername('_', $data->account);
	    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
			if($client_details == null){
				// $mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]];
			 //   return $mw_response;
			   $failed = ["account" => isset($data->account) ? $data->account : '',"code" =>"1006","message" =>"Player Not Found","ucode" => isset($data->ucode) ? $data->ucode : ''];
	    	   array_push($response['data']['failed'], $failed);
	    	   return $response;
			}
			$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
			if($player_details == 'false'){
				// $mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]];
			 //   return $mw_response;
			   $failed = ["account" => isset($data->account) ? $data->account : '',"code" =>"1006","message" =>"Player Not Found","ucode" => isset($data->ucode) ? $data->ucode : ''];
	    	   array_push($response['data']['failed'], $failed);
	    	   return $response;
			}
			if(!$this->validRFCDade($data->eventtime, 3)){
		    	$failed = ["account" => isset($data->account) ? $data->account : '',"code" =>"1004","message" =>"Time Format error","ucode" => isset($data->ucode) ? $data->ucode : ''];
		    	array_push($response['data']['failed'], $failed);
				return $response;
	    	}
			if($data->amount < 0){
				// $mw_response = ["data" => null,"status" => ["code" => "1003","message" => 'Amount cannot be negative',"datetime" => date(DATE_RFC3339)]];
			 //   return $mw_response;
			   $failed = ["account" => $data->account,"code" =>"1003","message" =>"Amount cannot be negative","ucode" => $data->ucode];
	    	    array_push($response['data']['failed'], $failed);
	    		return $response;
			}
    		if($set_before_balance == false){
				$multi_event_bag['before_balance'] = $this->amountToFloat4DG($player_details->playerdetailsresponse->balance);
				$set_before_balance = true;
			}
    		foreach ($data->event as $key => $value) {
			if(!isset($value->mtcode) || !isset($value->amount) || !isset($value->validbet) || !isset($value->action) || !isset($value->roundid) || !isset($value->eventtime) || !isset($value->gamecode)){
			    	$failed = ["account" => $data->account,"code" =>"1003","message" =>"Parameter error","ucode" => $data->ucode];
		    		array_push($response['data']['failed'], $failed);
		    		return $response;
		    	}
		    	if($value->amount < 0){
		    		$failed = ["account" => $data->account,"code" =>"1003","message" =>"Amount Cannot be negative","ucode" => $data->ucode];
		    		array_push($response['data']['failed'], $failed);
		    		return $response;
		    	}
		    	$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $value->gamecode);
		    		if($value->amount < 0 AND $da_error == 0){
	    			$failed = ["account" => $data->account,"code" =>"1003","message" =>"Error Game Code","ucode" => $data->ucode];
		    		array_push($response['data']['failed'], $failed);
		    		return $response;
		   		}
		   		if(!$this->validRFCDade($value->eventtime, 3)){
			    	$failed = ["account" => isset($data->account) ? $data->account : '',"code" =>"1004","message" =>"Time Format error","ucode" => isset($data->ucode) ? $data->ucode : ''];
			    	array_push($response['data']['failed'], $failed);
					return $response;
		    	}
    			// $find_mtcode = $this->findTranPID($value->mtcode);
    			$find_mtcode = ProviderHelper::findGameExt($value->roundid, 2, 'round_id'); // WIN
    			if($find_mtcode == 'false'){
    				$failed = ["account" => $data->account,"code" =>"1014","message" =>"Transaction record not found","ucode" => $data->ucode];
		    		array_push($response['data']['failed'], $failed);
		    		return $response;
    			}else{
    				$general_details = json_decode($find_mtcode->general_details);
		    		if($existing_multi_event_set == false){ // Flag 1
					  array_push($existing_multi_event, $general_details->multi_events->events);
					  $existing_multi_event_set = true;
					}
					array_push($mtcode_request_list, $value->mtcode); // # 1 List of all mtcode request
    			}
    		}
    		
    	}// END FIRST LOOP CHECKER/SETTER

    	// AND AUTOMATIC ADD THE EVENT THAT NOT IN THE REFUNDLIST
		if($find_mtcode != 'false'){
			foreach ($data_details[0]->event as $key => $evetlist) {
				foreach ($existing_multi_event[0] as $key => $value) { 
					$find_mtcode = $this->findTranPID($value->mtcode);
					if($find_mtcode != 'false'){
					   array_push($gametrans_ext_bag_id, $find_mtcode->game_trans_ext_id); // List of all mtcode tobe updated
					}
					if(!in_array($value->mtcode, $mtcode_request_list)){ // # 1 List of all mtcode request
						$multi_event_array = [
				    		"mtcode" => $value->mtcode,
			                "amount" => $value->amount,
			                "eventtime" => $value->eventtime,
				    	];
				    	if(isset($value->status)){
				    		$multi_event_array['status'] = 'success';
				    	}
				    	if(isset($value->action)){
				    		$multi_event_array['action'] = $value->action;
				    	}
						array_push($multi_event_bag['events'], $multi_event_array);
					}
				}
			}
		}

    	try {
			
	    	foreach($data_details as $key => $data){
	    		$ucode = $data->ucode;
	    		$account = $data->account;
	    		$action = 'amends';
	    		$eventtime = $data->eventtime;
				$createtime = date(DATE_RFC3339);
	    		$user_id = Providerhelper::explodeUsername('_', $data->account);
		    	$client_details = Providerhelper::getClientDetails('player_id', $user_id);
				$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
				foreach ($data->event as $key => $value) {
					$da_error = 0;
				
					// $game_ext_check = ProviderHelper::findGameExt($value->mtcode, 2, 'transaction_id');
			   		$game_ext_check = ProviderHelper::findGameExt($value->roundid, 2, 'round_id');
					if($game_ext_check == 'false' AND $da_error == 0){
						$failed = ["account" => $account,"code" =>"1014","message" =>"Transaction record not found","ucode" => $ucode];
			    		array_push($response['data']['failed'], $failed);
			    		$da_error = 1;
			    		continue;
					}	

					$game_ext_check_win = ProviderHelper::findGameExt($value->mtcode, 3, 'transaction_id');
					if($game_ext_check_win != 'false' AND $da_error == 0){
						$failed = ["account" => $account,"code" =>"2009","message" =>"Transaction duplicate","ucode" => $ucode];
			    		Helper::saveLog('CQ9 T ALready Exist', $this->provider_db_id, json_encode($provider_request), $failed);
			    		array_push($response['data']['failed'], $failed);
			    		$da_error = 1;
			    		continue;
					}

					if($da_error == 0){
						$game_transaction = ProviderHelper::findGameTransaction($game_ext_check->game_trans_id, 'game_transaction');
						$amount = $value->amount;
						if($value->amount == 0){
							$transactiontype = 'credit';
						}else{
							$transactiontype = $value->action;
						}
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
				 	     array_push($gametrans_ext_bag_id, $game_transextension); // # MULTI EVENT

					    try {
							  $client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$game_ext_check->game_trans_id, $transactiontype, true);
							 Helper::saveLog('CQ9 playerAmends CRID = '.$game_ext_check->game_trans_id, $this->provider_db_id, json_encode($provider_request), $client_response);
						} catch (\Exception $e) {
						    $mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
							ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $mw_response, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
							Helper::saveLog('CQ9 playerBet - FATAL ERROR', $this->provider_db_id, $mw_response, Helper::datesent());
							return $mw_response;
						}

					     if(isset($client_response->fundtransferresponse->status->code) 
						             && $client_response->fundtransferresponse->status->code == "200"){
	    					$multi_event_bag['after_balance'] = $this->amountToFloat4DG($client_response->fundtransferresponse->balance);

					     	# MULTI EVENT
					    	foreach ($existing_multi_event[0] as $key => $exist) {
				    			if($exist->mtcode == $value->mtcode){
				    				$multi_event_array = [
							    		"mtcode" => $value->mtcode,
						                "amount" => $value->amount,
						                "eventtime" => $value->eventtime,
						                "action" => $value->action
							    	];
									array_push($multi_event_bag['events'], $multi_event_array);
				    			}
				    		}
					    	// # END MULTI EVENT

					     	$general_details = [
					     		"multi_event" => $multi_event,
	  	    					"transaction_status" =>  'success', // refund
								"provider" => [
									"createtime" => $createtime,  // The Transaction Created!
									"endtime" => date(DATE_RFC3339),
									"eventtime" => $eventtime,
									"action" => $action,
									"refund_type" => 'amends_win'
								],
								"client" => [
									"before_balance" => $this->amountToFloat4DG($player_details->playerdetailsresponse->balance),
							    	"after_balance"=> $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
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
				    				"balance" => $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
					    			"currency" => $client_details->default_currency,
					    		],
					    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
					    	];
					 	    ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);

					 	    $success = ["account" => $account,"balance" =>$this->amountToFloat4DG($client_response->fundtransferresponse->balance),"currency" => $client_details->default_currency,"ucode" => $ucode];
			    		    array_push($response['data']['success'], $success);

						}elseif(isset($client_response->fundtransferresponse->status->code) 
						            && $client_response->fundtransferresponse->status->code == "402"){
							$multi_event_bag['after_balance'] = $this->amountToFloat4DG($client_response->fundtransferresponse->balance);
							$mw_response = [
							"data" => null,
								"status" => ["code" => "1005","message" => 'Insufficient Balance',"datetime" => date(DATE_RFC3339)]
							];
							ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED',$general_details);
							return $mw_response;

						}else{ // Unknown Response Code
							$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
							ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED',$general_details);
							return $mw_response;
						}    

					}
				}
	    	}	

	    	// # MULTI EVENT
	    	$multi_event_bag['me_endtime'] = date(DATE_RFC3339);
	    	if($multi_event == true){
	    		foreach ($gametrans_ext_bag_id as $key) {
	    			$transaction_record = $this->findGameExtByID($key);
	    			$game_ext_details = $transaction_record->general_details;
	    	        $general_details_bag = json_decode($game_ext_details);
	    			$general_details_bag->multi_events = $multi_event_bag;
	    			$this->updatecreateGameTransExtGD($key, $general_details_bag);
	    		}
	    	}
    		// # END MULTI EVENT
	    	// ONLY SHOW 1 ITEM
	    	$success_to_return = count($response['data']['success']);
	    	if($success_to_return > 1){
	    		$response['data']['success'] = [$response['data']['success'][$success_to_return-1]];
	    	}
	    	$failed_to_return = count($response['data']['failed']);
	    	if($failed_to_return > 1){
	    		$response['data']['failed'] = [$response['data']['failed'][$failed_to_return-1]];
	    	}
	    	$response['status']['datetime'] = date(DATE_RFC3339);
	    	return $response;
		} catch (\Exception $e) {
			$mw_response = [
	    		"data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]
	    	];
			Helper::saveLog('CQ9 playerAmends Failed', $this->provider_db_id, json_encode($request->all()), $e->getMessage());
			return $mw_response;
		}
    }

    public function playerAmend(Request $request){
    	Helper::saveLog('CQ9 playerAmend - EH', $this->provider_db_id, file_get_contents("php://input"), 'ENDPOINT 1');
    	$header = $request->header('wtoken');
    	$provider_request = json_decode(file_get_contents("php://input"));
    	$check_wtoken = $this->checkAuth($header);
    	if(!$check_wtoken){
    		$mw_response = ["status" => ["code" => "9999","message" => 'Error Token',"datetime" => date(DATE_RFC3339)]];
			return $mw_response;
    	}
    	if($provider_request == null){
    		$response = [
	    		"data"=>null,
				"status" => ["code" => "1003","message" => "Parameter error","datetime" => date(DATE_RFC3339)]
	    	];
	    	return $response;
    	}
    	if(!isset($provider_request->account) || !isset($provider_request->action) || !isset($provider_request->amount) || !isset($provider_request->data) || !isset($provider_request->gamecode)|| !isset($provider_request->createTime) || !isset($provider_request->gamehall)){
    		$response = [
	    		"data"=>null,
				"status" => ["code" => "1003","message" => "Parameter error","datetime" => date(DATE_RFC3339)]
	    	];
	    	return $response;
    	}

    	$data_details = $provider_request->data;
    	$account = $provider_request->account;
    	$gamecode = $provider_request->gamecode;
    	// $gamecode = $this->game_static_code; // $request->gamecode;
    	$eventime = $provider_request->createTime; // created
		$createtime = date(DATE_RFC3339);
		$action = 'amend';
    	
    	$check_string_user = ProviderHelper::checkIfHasUnderscore($request->account);
    	if(!$check_string_user){
			$mw_response = ["data" => null,"status" => ["code" => "1006","message" => 'Player Not Found',"datetime" => date(DATE_RFC3339)]];
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
		if($request->amount < 0){
   			$mw_response = [
	    		"data" => null,
	    		"status" => ["code" => "1003","message" => 'Amount cannot be negative!',"datetime" => date(DATE_RFC3339)]
	    	];
			return $mw_response;
   		}
   		if($player_details->playerdetailsresponse->balance < $request->amount){
   			$mw_response = [
	    		"data" => null,"status" => ["code" => "1005","message" => 'Insufficient Balance',"datetime" => date(DATE_RFC3339)]
	    	];
	    	Helper::saveLog('CQ9 playerBets', $this->provider_db_id, json_encode($provider_request), $mw_response);
			return $mw_response;
   		}

    	try {
    		// # MULTI EVENT
			$multi_event_bag = ["events"=>[]];
	    	$multi_event = false;
	    	$gametrans_ext_bag_id = [];
	    	if(count($data_details) > 1){
	    		$multi_event = true;
	    		$multi_event_bag['before_balance'] = $this->amountToFloat4DG($player_details->playerdetailsresponse->balance);
	    		$multi_event_bag['me_createtime'] = $createtime;
			   	$multi_event_bag['action'] = $action;
	    	}
	    	// # END MULTI EVENT
			$game_details = Helper::findGameDetails('game_code', $this->provider_db_id, $gamecode);
			// # FIRST LOOPER CHECKER
			$total_amount = array();
	    	foreach ($data_details as $value) {

	    		if(!isset($value->mtcode) || !isset($value->amount) || !isset($value->validbet) || !isset($value->action) || !isset($value->roundid) || !isset($value->eventtime)){
	    			$response = [
			    		"data"=>null,
						"status" => ["code" => "1003","message" => "Parameter error","datetime" => date(DATE_RFC3339)]
			    	];
			    	return $response;
	    		}
	    		$roundid = $value->roundid;
		    	$amount = $value->amount;
		    	$mtcode = $value->mtcode;
		    	if($value->amount < 0){
		   			$mw_response = [
			    		"data" => null,
			    		"status" => ["code" => "1003","message" => 'Amount cannot be negative!',"datetime" => date(DATE_RFC3339)]
			    	];
					return $mw_response;
		   		}
		   		if($player_details->playerdetailsresponse->balance < $value->amount){
		   			$mw_response = [
			    		"data" => null,"status" => ["code" => "1005","message" => 'Insufficient Balance',"datetime" => date(DATE_RFC3339)]
			    	];
			    	Helper::saveLog('CQ9 playerBets', $this->provider_db_id, json_encode($provider_request), $mw_response);
					return $mw_response;
		   		}
		   		if(!$this->validRFCDade($value->eventtime, 3)){
		    		$mw_response = ["data" => null,"status" => ["code" => "1004","message" => 'Time Format error.',"datetime" => date(DATE_RFC3339)]
			    	];
					return $mw_response;
		    	}
		    	$game_ext_check = ProviderHelper::findGameExt($mtcode, 3, 'transaction_id');
				if($game_ext_check != 'false'){
		    		$mw_response = [
			    		"data" =>null,
			    		"status" => ["code" => "2009","message" => 'Transaction duplicate',"datetime" => date(DATE_RFC3339)]
			    	];
					return $mw_response;
				}
	    		$game_ext_check = ProviderHelper::findGameExt($roundid, 2, 'round_id');
				if($game_ext_check == 'false'){
		    		$mw_response = [
			    		"data" => null,
			    		"status" => ["code" => "1014","message" => 'Transaction record not found',"datetime" => date(DATE_RFC3339)]
			    	];
					return $mw_response;
				}	
				// if($general_details->provider->refund_type == 'amend_amends'){
		        //   		$mw_response = [
			    //    		"data" =>null,
			    //    		"status" => ["code" => "2009","message" => 'Transaction duplicate',"datetime" => date(DATE_RFC3339)]
			    //    	];
				// 	return $mw_response;
				// }
				
		   		array_push($total_amount, $value->amount);
	    	}

	    	if(!$this->validRFCDade($provider_request->createTime, 3)){
	    		$mw_response = ["data" => null,"status" => ["code" => "1004","message" => 'Time Format error.',"datetime" => date(DATE_RFC3339)]
		    	];
				return $mw_response;
	    	}

	    	$total_amount = array_sum($total_amount);
	    	if($player_details->playerdetailsresponse->balance < $total_amount){
	   			$mw_response = [
		    		"data" => null,"status" => ["code" => "1005","message" => 'Insufficient Balance',"datetime" => date(DATE_RFC3339)]
		    	];
		    	Helper::saveLog('CQ9 playerBets', $this->provider_db_id, json_encode($provider_request), $mw_response);
				return $mw_response;
	   		}
	   		// # END FIRST LOOPER CHECKER
	   	

	    	foreach ($data_details as $key) {
	    		// $find_mtcode = $this->findTranPID($key->mtcode);
	    		$find_mtcode = ProviderHelper::findGameExt($value->roundid, 2, 'round_id'); // WIN
	    		// $find_mtcode = Providerhelper::findGameExt('');
	    		$roundid = $key->roundid;
		    	$amount = $key->amount;
		    	$mtcode = $key->mtcode;
		    	// $game_ext_check = ProviderHelper::findGameExt($roundid, 3, 'round_id');
		    	// $general_details_db = json_decode($game_ext_check->general_details);
		    	$game_transaction = ProviderHelper::findGameTransaction($find_mtcode->game_trans_id, 'game_transaction');
		    	if($amount == 0){
		    		$transaction_type = 'credit';
		    	}else{
		    		$transaction_type = $key->action;
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
		 	    array_push($gametrans_ext_bag_id, $game_transextension); // # MULTI EVENT

		 	    try {
					$client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_details->game_code,$game_details->game_name,$game_transextension,$game_transaction->game_trans_id, $transaction_type, true);
					 Helper::saveLog('CQ9 playerAmend CRID = '.$game_transaction->game_trans_id, $this->provider_db_id, json_encode($provider_request), $client_response);
				} catch (\Exception $e) {
				    $mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
					ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $mw_response, 'FAILED', $e->getMessage(), 'FAILED', 'FAILED');
					Helper::saveLog('CQ9 playerAmend - FATAL ERROR', $this->provider_db_id, $mw_response, Helper::datesent());
					return $mw_response;
				}
		  	    
			    if(isset($client_response->fundtransferresponse->status->code) 
				             && $client_response->fundtransferresponse->status->code == "200"){
	    			$multi_event_bag['after_balance'] = $this->amountToFloat4DG($client_response->fundtransferresponse->balance);

			    	// # MULTI EVENT
			    	$multi_event_array = [
			    		"mtcode" => $key->mtcode,
		                "amount" => $key->amount,
		                "action" => $key->action,
		                "eventtime" => $key->eventtime
			    	];
			    	array_push($multi_event_bag['events'], $multi_event_array);
			    	// # END MULTI EVENT
			    	$game_ext_details = $find_mtcode->general_details;
			        $general_details_bag = json_decode($game_ext_details);
					// $general_details_bag->transaction_status = 'amend'; // refund
		    	    $general_details = json_decode($game_ext_details);
					$general_details_bag->client->before_balance = $this->amountToFloat4DG($player_details->playerdetailsresponse->balance);
				    $general_details_bag->client->after_balance = $this->amountToFloat4DG($client_response->fundtransferresponse->balance);
					$this->updatecreateGameTransExtGD($find_mtcode->game_trans_ext_id, $general_details_bag);
			    	// # END MULTI EVENT

			    	$general_details = [
						"transaction_status" => 'success',
			    		"multi_event" => $multi_event, // MULTI EVENT
						"provider" => [
							"description" => 'Refunded Bets',
							"refund_type" => 'amend_amends',
							"action" => $action
						],
						"client" => [
							"description" => 'SENDED DATA TO CLIENT!',
							"transaction_type" => $transaction_type,
							"player_prefixed" => $account,
							"amount" => $amount,
							"player_id"=> $user_id
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
			    			"balance" => $this->amountToFloat4DG($client_response->fundtransferresponse->balance),
			    			"currency" => $client_details->default_currency,
			    		],
			    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
			    	];
				
			 	    ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, $mw_response,$general_details);

				}elseif(isset($client_response->fundtransferresponse->status->code) 
				            && $client_response->fundtransferresponse->status->code == "402"){
	    			$multi_event_bag['after_balance'] = $this->amountToFloat4DG($client_response->fundtransferresponse->balance);
					$mw_response = [
					"data" => null,
						"status" => ["code" => "1005","message" => 'Insufficient Balance',"datetime" => date(DATE_RFC3339)]
					];
				    ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED',$general_details);
					return $mw_response;

				}else{ // Unknown Response Code
					$mw_response = ["data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				    ProviderHelper::updatecreateGameTransExt($game_transextension, $provider_request, $mw_response, $client_response->requestoclient, $client_response, 'FAILED',$general_details);
					return $mw_response;
				}    

			}

			// # MULTI EVENT
	    	$multi_event_bag['me_endtime'] = date(DATE_RFC3339);
	    	if($multi_event == true){
	    		foreach ($gametrans_ext_bag_id as $key) {
	    			$transaction_record = $this->findGameExtByID($key);
	    			$game_ext_details = $transaction_record->general_details;
	    	        $general_details_bag = json_decode($game_ext_details);
	    			$general_details_bag->multi_events = $multi_event_bag;
	    			$this->updatecreateGameTransExtGD($key, $general_details_bag);
	    		}
	    	}
	    	// # END MULTI EVENT

			return $mw_response;
		} catch (\Exception $e) {
			$mw_response = [
	    		"data" => null,"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]
	    	];
			Helper::saveLog('CQ9 playerAmend Failed', $this->provider_db_id, json_encode($request->all()), $e->getMessage());
			return $mw_response;
		}
    }
  
    public function CheckBalance(Request $request, $account){
    	
   		$check_string_user = ProviderHelper::checkIfHasUnderscore($account);
    	if(!$check_string_user){
			$data = ["data" => null,"status" => ["code" => "1006","message" => 'Playerdoesnotexist not found',"datetime" => date(DATE_RFC3339)]];
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

    public function validateDate($date, $format = 'Y-m-d\TH:i:s.uP')
    // public function validateDate($date, $format = \DateTime::createFromFormat("Y-m-d\TH:i:s.uP"))
	{
	    $d = \DateTime::createFromFormat($format, $date);
	    // $d = \DateTime::createFromFormat("Y-m-d\TH:i:s.uP", $date);
	    return $d && $d->format($format) == $date;
	}

    public function validRFCDade($date, $type=1) {

    	// 2020-01-16T00:00:00-04:00
		// 2020-01-16T00:00:00.1-04:00
		// 2020-01-16T00:00:00.12-04:00
		// 2020-01-16T00:00:00.123-04:00
		// 2020-01-16T00:00:00.1234-04:00
		// 2020-01-16T00:00:00.12345-04:00
		// 2020-01-16T00:00:00.123456-04:00
		// 2020-01-16T00:00:00.1234567-04:00
		// 2020-01-16T00:00:00.12345678-04:00
		// 2020-01-16T00:00:00.123456789-04:00
		// 2020-01-16T00:00:00.001Z
		// 
		// 2017-07-25T13:47:12.000+00:00
		// 2020-08-20T02:20:15.000+00:00


	    // ALL ERROR TEST IS LESS THAN 25
	    // 2020-06-05T08:59:45-04
	    // 2020-07-19T22:54:48-04:0
	    // 2020-08-18T06:51:01-04:00
	    // 2020-06-05T08:59:45-04


		$gg = preg_match('/^([0-9]+)-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])[Tt]([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9]|60)(\.[0-9]+)?(([Zz])|([\+|\-]([01][0-9]|2[0-3]):[0-5][0-9]))$/', $date);
		if($gg == 0){
			return false;
		}else{
			return true;
		}

   //  	$length = strlen($date);

   //  	if($type == 1){ // FISHING GAMES
   //  		if($length < 23 || $length > 35){
	  //   		return false;
	  //   	}else{
	  //   		return true;
	  //   	}
	  //   }elseif($type==3){ 
			// $gg = preg_match('/^([0-9]+)-(0[1-9]|1[012])-(0[1-9]|[12][0-9]|3[01])[Tt]([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9]|60)(\.[0-9]+)?(([Zz])|([\+|\-]([01][0-9]|2[0-3]):[0-5][0-9]))$/', $date);
			// if($gg == 0){
			// 	return false;
			// }else{
			// 	return true;
			// }
   //  	}else{
   //  		if($length < 28 || $length > 35){
	  //   		return false;
	  //   	}else{
	  //   		return true;
	  //   	}
   //  	} 


		// if (\DateTime::createFromFormat(\DateTime::RFC3339_EXTENDED, $date) === TRUE) {
		//     return true;
	 //    } else {
	 //        return false;
	 //    }
    
    	// $d->format(DateTime::RFC3339_EXTENDED)
    
	    // if (\DateTime::createFromFormat(\DateTime::RFC3339_EXTENDED, $date) === FALSE) {
	    // if (\DateTime::createFromFormat(\DateTime::RFC3339_EXTENDED, $date) === FALSE) {
	   	// if (\DateTime::createFromFormat("Y-m-d\TH:i:s.uP", "2017-07-25T15:25:16.123456+02:00") {
	   	// if (\DateTime::createFromFormat("Y-m-d\TH:i:s.uP", "2017-07-25T15:25:16.123456+02:00") {
	    // if (\DateTime::createFromFormat(\DateTime::RFC3339, $date) === FALSE) {
	    //     return false;
	    // } else {
	    //     return true;
	    // }

		// var_dump($this->validRFCDade('2020-01-16T00:00:00.123456789-04:00'));
		// var_dump($this->validRFCDade('2020-08-19T19:05:40.839-04:00'));
		// var_dump($this->validRFCDade('2011-01-01T15:03:01.012345Z'));
		// var_dump($this->validRFCDade('2020-01-16T00:00:00.001Z'));
		// var_dump($this->validRFCDade('2020-01-16T00:00:00.123456789-04:00'));
		// var_dump($this->validRFCDade('2020-08-18T06:51:03-04:00')); // (TRUE)
		// // var_dump($this->validateDate('2020-08-19T19:05:40.839-04:00', 'Y-m-d\TH-i:sP'));
		// var_dump($this->validateDate('2020-01-16T00:00:00.123456789-04:00', 'Y-m-d U:i:sP')); // (TRUE)
		// return date(\DateTime::RFC3339_EXTENDED);
		
		// dd(\DateTime::createFromFormat(\DateTime::RFC3339_EXTENDED, '2020-08-20T02:20:15.000+00:00'));


		// return \DateTime::createFromFormat(\DateTime::RFC3339_EXTENDED, date(\DateTime::RFC3339_EXTENDED));
		// var_dump(\DateTime::RFC3339);
		// var_dump($this->validateDate('2020-01-16T00:00:00.1234-04:00', 'Y-m-d\TH:i:sP')); // (TRUE)
		// var_dump($this->validateDate('2020-08-18T06:51:03-04:00', 'Y-m-d\TH:i:sP')); // (TRUE)

		// var_dump($this->validateDate('2020-01-16T00:00:00.123456789-04:00', 'Y-m-d\th:i:sP'));
		// Helper::saveLog('CQ9 Balance Player', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');
	}

	//ProviderHelper::amountToFloat
	public function amountToFloat4DG($amount){
		 // $amount = 104010.6136;
		$float = floatval(number_format((float)$amount, 2, '.', ''));
		// $float = floatval(number_format((float)$amount, 4, '.', ''));
    	return $float;
	}

    public function findTranPID($provider_identifier) {
		$transaction_db = DB::table('game_transaction_ext as gte');
		$transaction_db->where([
	 		["gte.provider_trans_id", "=", $provider_identifier],
	 	]);
	 	// $result= $transaction_db->first();
	 	$result= $transaction_db->latest()->first();
		return $result ? $result : 'false';
	}

	public function findGameExtByID($game_trans_ext_id) {
		$transaction_db = DB::table('game_transaction_ext as gte');
		$transaction_db->where([
	 		["gte.game_trans_ext_id", "=", $game_trans_ext_id],
	 	]);
	 	$result= $transaction_db->first();
		return $result ? $result : 'false';
	}


	public  function updatecreateGameTransExtGD($game_trans_ext_id, $general_details) {
   	    $update = DB::table('game_transaction_ext')
                ->where('game_trans_ext_id', $game_trans_ext_id)
                ->update([
					"general_details" =>json_encode($general_details)
	    		]);
		return ($update ? true : false);
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

	public function rawToObj($data, $multiple=false){
    	$array = (array)$data;
	    $newStr = str_replace("\\", '', $array[0]);
	    $newStr2 = str_replace(",n", ",", $newStr); // Break Line (TEST CQ9)
	    $newStr2 = str_replace(';', '', $newStr2);
		$string_to_obj = json_decode($newStr2);
		if($multiple == false){
			return $string_to_obj[0];
		}else{
			return $string_to_obj;
		}
    }

}
