<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use App\Helpers\AWSHelper;
use App\Helpers\GameLobby;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;
use DB;

class SpadeController extends Controller
{

	public $api_url, $merchant_id, $merchant_key = '';
	public $provider_db_id = 21;
	public $prefix = 'TIGERG';
	public $merchantCode = 'M888';
	public $serialNo = '20120722224255982841';
	public $siteId = 'SITE_USD1';

    public function __construct(){
    	$this->api_url = config('providerlinks.aws.api_url');
    	$this->merchant_id = config('providerlinks.aws.merchant_id');
    	$this->merchant_key = config('providerlinks.aws.merchant_key');
    }


    public function index(Request $request){
    	// var_dump(apache_request_headers()); die();
    	$data = file_get_contents("php://input");
		$details = json_decode($data);

		$acctId =  ProviderHelper::explodeUsername('_', $details->acctId);
		$client_details = Providerhelper::getClientDetails('player_id', $acctId);
		if($client_details == null){
			$response = [
				"acctInfo" => [],
				"merchantCode" => $this->merchantCode,"msg" => "Acct Not Found","code" => 50100,"serialNo" => $this->serialNo
			];
			return $response;
		}
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
		$response = [
			"acctInfo" => [
				"acctId" => $this->prefix.'_'.$acctId,
				"balance" => floatval(number_format((float)$player_details->playerdetailsresponse->balance, 2, '.', '')),
				"userName" => $this->prefix.$acctId,
				"currency" => $client_details->default_currency,
				"siteId" => $this->siteId
			],
			"merchantCode" => $this->merchantCode,"msg" => "success","code" => 0,"serialNo" => $this->serialNo
		];
		return $response;
    }

    public function getBalance(Request $request){
    	$data = file_get_contents("php://input");
		$details = json_decode($data);
		$acctId =  ProviderHelper::explodeUsername('_', $details->acctId);
		$client_details = Providerhelper::getClientDetails('player_id', $acctId);
		if($client_details == null){
			$response = [
				"acctInfo" => [],
				"merchantCode" => $this->merchantCode,"msg" => "Acct Not Found","code" => 50100,"serialNo" => $this->serialNo
			];
			return $response;
		}
		$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
    	$response = [
			"acctInfo" => [
				"acctId" => $this->prefix.'_'.$acctId,
				"balance" => floatval(number_format((float)$player_details->playerdetailsresponse->balance, 2, '.', '')),
				"userName" => $this->prefix.$acctId,
				"currency" => $client_details->default_currency
			],
			"merchantCode" => $this->merchantCode,"msg" => "success","code" => 0,"serialNo" => $this->serialNo
		];
		return $response;
    }

 	// 1 = place bet
	// 2 = cancel bet
	// 4 = payout
	// 7 = Bonus
    public function makeTransfer(Request $request){
    	$data = file_get_contents("php://input");
		$details = json_decode($data);
		if($details->type == 1){
			return $this->placeBet($details);
		}else if($details->type == 2){
			return $this->cancelBet($details);
		}else if($details->type == 3){
			return $this->makePayout($details);
		}else if($details->type == 4){
			return $this->spadeBunos($details);
		}
    }

	public function placeBet($details){
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
		    $client_response = $this->fundTransferRequest(
		    	$client_details->client_access_token,
		    	$client_details->client_api_key, 
		    	$game_details->game_code, 
		    	$game_details->game_name, 
		    	$client_details->client_player_id, 
		    	$client_details->player_token, 
		    	abs($amount),
		    	$client_details->fund_transfer_url, 
		    	"debit",
		    	$client_details->default_currency, 
		    	false
		    );
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
				    	"after_balance"=> ProviderHelper::amountToFloat($client_response['client_response']->fundtransferresponse->balance),
				    	"player_prefixed"=> $account,
				    	"player_id"=> $user_id
					]
				];
				$mw_response = [
		    		"data" => [
		    			"balance" => ProviderHelper::amountToFloat($client_response['client_response']->fundtransferresponse->balance),
		    			"currency" => $client_details->default_currency,
		    		],
		    		"status" => ["code" => "0","message" => 'Success',"datetime" => date(DATE_RFC3339)]
		    	];
				$gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_id, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $roundid);
			    $game_transextension = ProviderHelper::createGameTransExt($gamerecord,$provider_trans_id, $roundid, $amount, $game_transaction_type, $provider_request, $mw_response, $client_response['requesttosend'], $client_response['client_response'], $mw_response,$general_details);
			}else{
				$mw_response = ["data" => [],"status" => ["code" => "1100","message" => 'Server error.',"datetime" => date(DATE_RFC3339)]];
				Helper::saveLog('CQ9 playerBet Failed', $this->provider_db_id, json_encode($request->all()), $mw_response);
			}
			return $mw_response;
	}

	public function cancelBet($details){
		return 'cancelBet';
	}

	public function makePayout($details){
		return 'makePayout';
	}


	public function spadeBunos($details){
		return $details->type;
		return 'spadeBunos';
	}


}
