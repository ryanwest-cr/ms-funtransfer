<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use GuzzleHttp\Client;
use Carbon\Carbon;
use DB;


class PGSoftController extends Controller
{
    // 
    public $provider_db_id = 31;

    public function __construct(){
    	$this->operator_token = config('providerlinks.pgsoft.operator_token');
    	$this->secret_key = config('providerlinks.pgsoft.secret_key');
    	$this->api_url = config('providerlinks.pgsoft.api_url');
    }

    public function verifySession(Request $request){
        Helper::saveLog('PGSoft Authorization', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');
		//$data = json_decode(file_get_contents("php://input")); // INCASE RAW JSON / CHANGE IF NOT ARRAY
		$enc_body = file_get_contents("php://input");
        parse_str($enc_body, $data);
        $json_encode = json_encode($data, true);
        $data = json_decode($json_encode);
        
        if($data->operator_token != $this->operator_token && $data->secret_key != $this->secret_key):
            $errormessage = array(
				'error_code' 	=> '3005',
				'error_msg'  	=> 'Player wallet doesnt exist'
            );
            Helper::saveLog('PGSoft ENDPOINT error wallet', $this->provider_db_id,  json_encode($request->all()), $errormessage);
            return $errormessage; 
        endif;

        $client_details = ProviderHelper::getClientDetails('token',$data->operator_player_session);
       
        if($client_details != null){
			$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
				//$balance = number_format($player_details->playerdetailsresponse->balance, 2); 
				$currency = $client_details->default_currency;
				$num = $player_details->playerdetailsresponse->balance;
                $balance = (double)$num;
				$data =  [
                    "data" => [
                        "player_name" => $currency,
                        "nickname" => $balance,
                        "currency" => $this->getMilliseconds(),
                        "reminder_time" => $this->getMilliseconds()
                    ],
                    "error" => null
                ];
				Helper::saveLog('PGSoft ENDPOINT Process', $this->provider_db_id, json_encode($request->all()), $data);
				return $data;
		}else{
            $errormessage = array(
				'error_code' 	=> '1034',
				'error_msg'  	=> 'Internal server error'
            );
            Helper::saveLog('PGSoft ENDPOINT error session', $this->provider_db_id, json_encode($request->all()),  $errormessage);
			return $errormessage;
		}
    }




    public function playerWallet(Request $request){
        Helper::saveLog('PGSoft ENDPOINT wallet', $this->provider_db_id, json_encode($request->all()), 'ENDPOINT HIT');
		//$data = json_decode(file_get_contents("php://input")); // INCASE RAW JSON / CHANGE IF NOT ARRAY
		$enc_body = file_get_contents("php://input");
        parse_str($enc_body, $data);
        $json_encode = json_encode($data, true);
        $data = json_decode($json_encode);
        
        if($data->operator_token != $this->operator_token && $data->secret_key != $this->secret_key):
            $errormessage = array(
				'error_code' 	=> '3005',
				'error_msg'  	=> 'Player wallet doesnt exist'
            );
            Helper::saveLog('PGSoft ENDPOINT error wallet', $this->provider_db_id,  json_encode($request->all()), $errormessage);
            return $errormessage; 
        endif;

        $client_details = ProviderHelper::getClientDetails('token',$data->operator_player_session);
       
        if($client_details != null){
			$player_details = Providerhelper::playerDetailsCall($client_details->player_token);
				//$balance = number_format($player_details->playerdetailsresponse->balance, 2); 
				$currency = $client_details->default_currency;
				$num = $player_details->playerdetailsresponse->balance;
                $balance = (double)$num;
				$data =  [
                    "data" => [
                        "currency_code" => $currency,
                        "balance_amount" => $balance,
                        "updated_time" => $this->getMilliseconds()
                    ],
                    "error" => null
                ];
				Helper::saveLog('PGSoft ENDPOINT Process', $this->provider_db_id, json_encode($request->all()), $data);
				return $data;
		}else{
            $errormessage = array(
				'error_code' 	=> '3005',
				'error_msg'  	=> 'Player wallet doesnt exist'
            );
            Helper::saveLog('PGSoft ENDPOINT error wallet', $this->provider_db_id, json_encode($request->all()),  $errormessage);
			return $errormessage;
		}
        
    }

    public function getMilliseconds(){
        return $milliseconds = round(microtime(true) * 1000);
    }
    
}
