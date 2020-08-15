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
    	// dd($data);
		$details = json_decode($data);
		dd($details);
		if($details->type == 1){
			return $this->placeBet();
		}else if($details->type == 2){
			return $this->cancelBet();
		}else if($details->type == 3){
			return $this->makePayout();
		}else if($details->type == 4){
			return $this->spadeBunos();
		}
    }

	public function placeBet(){
		return 'placeBet';
	}

	public function cancelBet(){
		return 'cancelBet';
	}

	public function makePayout(){
		return 'makePayout';
	}


	public function spadeBunos(){
		return 'spadeBunos';
	}
}
