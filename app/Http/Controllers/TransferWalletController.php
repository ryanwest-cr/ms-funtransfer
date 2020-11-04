<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\ProviderHelper;
use App\Helpers\Helper;
use App\Helpers\SessionWalletHelper;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Carbon\Carbon;
use DB;


/**
 * CronTab will run every 30seconds and update all session_time deduct it with 30seconds
 * Playbetrnk - FrontEnd will try to renew its session every 30 seconds, using JavaScipt
 * 
 */
class TransferWalletController extends Controller
{

	/**
	 * [updateSession - update set session to default $session_time]
	 * 
	 */
    public function createWalletSession(Request $request){

    	$data = $request->all();
    	if($request->has('token')){

    		$token = SessionWalletHelper::checkIfExistWalletSession($request->token);
            if($token == false){
                SessionWalletHelper::createWalletSession($request->token, $request->all());
            }else{
            	SessionWalletHelper::updateSessionTime($request->token);
            }

    	}
    	$response = ["status" => "success", 'message' => 'Session Updated!'];
    	SessionWalletHelper::saveLog('TW updateSession', 1223, json_encode($data), 1223);
    	return $response;
    }


	/**
	 * [updateSession - update set session to default $session_time]
	 * 
	 */
    public function renewSession(Request $request){

    	$data = $request->all();
    	if($request->has('token')){
    		SessionWalletHelper::updateSessionTime($request->token);
    	}
    	$response = ["status" => "error", 'message' => 'Success Renew!'];
    	SessionWalletHelper::saveLog('TW updateSession', 1223, json_encode($data), 1223);
    	return $response;
    }


	/**
	 * [updateSession - deduct all session time with $time_deduction]
	 * 
	 */
    public function updateSession(){
    	try {
    		SessionWalletHelper::deductSession();
    		$this->withdrawAllExpiredWallet();
    		SessionWalletHelper::saveLog('TW updateSession', 1223, json_encode(['msg'=>'success']), 1223);
    	} catch (\Exception $e) {
    		SessionWalletHelper::saveLog('TW updateSession Failed', 1223, json_encode(['msg'=>$e->getMesage()]), 1223);
    	}
    }


    /**
	 * [withdrawAllExpiredWallet - withdraw all 0 or negative session_time]
	 * Frondend Player Failed to renew it session considered expired
	 * 
	 */
    public function withdrawAllExpiredWallet(){
    	try {
    		$wallet_session = DB::select('SELECT * FROM wallet_session WHERE session_time <= 0');
    		if(count($wallet_session) > 0){
				foreach ($wallet_session as $key) {
    				$metadata = json_decode($key->metadata);
	    			try {
	    				$http = new Client();
				        $response = $http->post($metadata->callback_transfer_out, [
				            'form_params' => [
				                'token' => $metadata->token,
				                'player_id'=> $metadata->player_id,
				            ],
				            'headers' =>[
				                'Authorization' => 'Bearer '.SessionWalletHelper::tokenizer(),
				                'Accept'     => 'application/json'
				            ]
				        ]);
				        SessionWalletHelper::deleteSession($metadata->token);
				        SessionWalletHelper::saveLog('TW withdrawAllExpiredWallet Success', 1223, json_encode([count($wallet_session)]), 'WITHDRAW EXPIRED SUCCESS');
	    			} catch (Exception $e) {
	    				continue;
	    			}
				}
    		}
    	} catch (\Exception $e) {
    		SessionWalletHelper::saveLog('TW withdrawAllExpiredWallet Failed', 1223, json_encode([count($wallet_session)]), $e->getMessage());
    	}
    }


}
