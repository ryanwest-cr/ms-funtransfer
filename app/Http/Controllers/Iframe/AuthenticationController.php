<?php

namespace App\Http\Controllers\Iframe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
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
}
