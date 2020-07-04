<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\CallParameters;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;

use DB;


/**
 * 8Provider
 *
 * @version 1.0
 * @method register
 *
 */
class EightProviderController extends Controller
{

	public $secret_key = 'c270d53d4d83d69358056dbca870c0ce';
	public $project_id = '1042';

    /**
     * GetSignature 
     * @return string
     *
     */
    public function getSignature($system_id, $callback_version, array $args, $system_key){
	    $md5 = array();
	    $md5[] = $system_id;
	    $md5[] = $callback_version;
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

	//http://api.8provider.com/game/getlist?project=1&version=1&signature=5a4174196eb3b134f23c56deac02ac53 
	public function gameInit(Request $request){
		Helper::saveLog('8P Game Init', 14, 14, 'ENDPOINT HIT');

		$response = array(
			'status' => 'ok',
			'data' => [
				'balance' => 456455.66,
				'currency' => 'USD',
			],
		);
	
		return $response;
	}

	public function gameDeposit(Request $request){
		Helper::saveLog('8P Deposit', 14, 14, 'ENDPOINT HIT');
		$response = array(
			'status' => 'ok',
			'data' => [
				'balance' => 456455.66,
				'currency' => 'USD',
			],
		);
	
		return $response;
	}

	public function gameWithdrawal(Request $request){
		Helper::saveLog('8P Withdrawal', 14, 14, 'ENDPOINT HIT');
		$response = array(
			'status' => 'ok',
			'data' => [
				'balance' => 456455.66,
				'currency' => 'USD',
			],
		);
		return $response;
	}

	public function gameBet(Request $request){
		Helper::saveLog('8P gameBet', 14, 14, 'ENDPOINT HIT');
		$response = array(
			'status' => 'ok',
			'data' => [
				'balance' => 456455.66,
				'currency' => 'USD',
			],
		);
		return $response;
	}

	public function gameWin(Request $request){
		Helper::saveLog('8P gameWin', 14, 14, 'ENDPOINT HIT');
		$response = array(
			'status' => 'ok',
			'data' => [
				'balance' => 456455.66,
				'currency' => 'USD',
			],
		);
		return $response;
	}
	
	public function gameRefund(Request $request){
		Helper::saveLog('8P gameRefund', 14, 14, 'ENDPOINT HIT');
		$response = array(
			'status' => 'ok',
			'data' => [
				'balance' => 456455.66,
				'currency' => 'USD',
			],
		);
		return $response;
	}
	

	
}
