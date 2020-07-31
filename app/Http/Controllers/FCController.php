<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AES;
use App\Helpers\FCHelper;
use GuzzleHttp\Client;
use DB;
class FCController extends Controller
{
    //


    public function SampleEncrypt(Request $request){
        $data = $request->getContent();

        return array("AESENCRYPT"=>FCHelper::AESEncode($data),"SIGN"=>md5($request->getContent()));
    }
    public function SampleDecrypt(){
        $data = '7Jhu1hCXPmisYLWVGIKhulHfbIWwss8oNfXCdmzP3VPIxJf7ZgYvHBfVPhcec5eo';
        return FCHelper::AESDecode($data);
    }

    public function getBalance(Request $request){
        
        if($request->has("Params")){
            $datareq = FCHelper::AESDecode((string)$request->Params);
            $client_details = $this->_getClientDetails("player_id",json_decode($datareq,TRUE)["MemberAccount"]);
            if($client_details){
                $client = new Client([
                    'headers' => [ 
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer '.$client_details->client_access_token
                    ]
                ]);
                $guzzle_response = $client->post($client_details->player_details_url,
                    ['body' => json_encode(
                            [
                                "access_token" => $client_details->client_access_token,
                                "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
                                "type" => "playerdetailsrequest",
                                "datesent" => "",
                                "gameid" => "",
                                "clientid" => $client_details->client_id,
                                "playerdetailsrequest" => [
                                    "client_player_id"=>$client_details->client_player_id,
                                    "token" => $client_details->player_token,
                                    "gamelaunch" => "true"
                                ]]
                    )]
                );
                $client_response = json_decode($guzzle_response->getBody()->getContents());
                $msg = array(
                    "Result"=>0,
                    "MainPoints"=>(float)number_format($client_response->playerdetailsresponse->balance,2,'.', '')
                );
                return response($msg,200)->header('Content-Type', 'application/json');
            }
            else{
                $msg = array(
                    "Result"=>500,
                    "ErrorText"=>"Account does not exist.",
                );
                return response($msg,200)->header('Content-Type', 'application/json');
            }
        }
        else{
            $msg = array(
                "Result"=>500,
                "ErrorText"=>"Account does not exist.",
            );
            return response($msg,200)->header('Content-Type', 'application/json');
        }
    }
    private function _getClientDetails($type = "", $value = "") {

		$query = DB::table("clients AS c")
				 ->select('p.client_id', 'p.player_id', 'p.client_player_id','p.username', 'p.email', 'p.language', 'p.currency', 'pst.token_id', 'pst.player_token' , 'pst.status_id', 'p.display_name','c.default_currency', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
				 ->leftJoin("players AS p", "c.client_id", "=", "p.client_id")
				 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
				 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
				 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id");
				 
				if ($type == 'token') {
					$query->where([
				 		["pst.player_token", "=", $value],
				 		["pst.status_id", "=", 1]
				 	]);
				}

				if ($type == 'player_id') {
					$query->where([
				 		["p.player_id", "=", $value],
				 		["pst.status_id", "=", 1]
				 	])->orderBy('pst.token_id','desc')->limit(1);
				}

				 $result= $query->first();

		return $result;
    }

}
