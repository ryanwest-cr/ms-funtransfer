<?php

namespace App\Http\Controllers\Iframe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Helpers\Helper;
use App\Helpers\SessionWalletHelper;
use App\Helpers\TransferWalletHelper;

class AuthenticationController extends Controller
{
    //
    public function __construct(){

		$this->middleware('oauth', ['except' => []]);
		/*$this->middleware('authorize:' . __CLASS__, ['except' => ['index', 'store']]);*/
	}
    public function checkTokenExist(Request $request){
        if($request->has("token")){
            $token_data = DB::table('player_session_tokens')->where("player_token",$request->token)->first();
            if($token_data){
                $response = array(
                    "status" => "ok",
                    "message" => "Token Exist",
                    "exist" => true,
                );

                $token = SessionWalletHelper::checkIfExistWalletSession($request->token);
                if($token == false){
                    $token_identity = TransferWalletHelper::getClientDetails('token', $request->token);
                    $session_count = SessionWalletHelper::isMultipleSession($token_identity->player_id, $request->token);
                    if ($session_count) {
                        $response = array(
                            "status" => "error",
                            "message" => "Multiple Session Detected!",
                            "exist" => true,
                        );
                    }else{
                        SessionWalletHelper::createWalletSession($request->token, $request->all());
                    }
                }
                return response($response,200)
                ->header('Content-Type', 'application/json');
            }
            else{
                $response = array(
                    "status" => "error",
                    "message" => "Token Does not Exist",
                    "exist" => false,
                );
                return response($response,200)
                ->header('Content-Type', 'application/json');
            }
        }
        else{
            $response = array(
                "status" => "error",
                "message" => "Token is needed to continue.",
                "exist" => false,
            );
            return response($response,200)
            ->header('Content-Type', 'application/json');        }
    }
    public function iframeClosed(Request $request){
        Helper::saveLog('IFRAME CLOSE CALL', 0, json_encode($request->all()), 'IframeClose');
        return "success";
    }
}
