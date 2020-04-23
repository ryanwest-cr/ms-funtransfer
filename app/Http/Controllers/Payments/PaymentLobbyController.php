<?php

namespace App\Http\Controllers\Payments;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PaymentLobbyController extends Controller
{
    //
    private $payment_lobby_url = "http://localhost:8000";
    public function paymentLobbyLaunchUrl(Request $request){
        if($request->has("callBackUrl")
            &&$request->has("exitUrl")
            &&$request->has("client_id")
            &&$request->has("player_id")
            &&$request->has("player_username")
            &&$request->has("amount")
            &&$request->has("payment_method")){
                $token = substr("abcdefghijklmnopqrstuvwxyz1234567890", mt_rand(0, 25), 1).substr(md5(time()), 1);
                if($token = Helper::checkPlayerExist($request->client_id,$request->player_id,$request->player_username,$email=null,$request->player_username,$token)){
                    if($request->payment_method == "PAYMONGO")
                    {
                        $payment_method = "paymongo";
                    }
                    $response = array(
                        "url" => $this->payment_lobby_url."/".$payment_method."?payment_method=".$request->payment_method."&amount=".$request->amount."&token=".$token,
                        "status" => "OK"
                    ); 
                    return response($response,200)->header('Content-Type', 'application/json');
                }
        }
        else{
            $response = array(
                "error" => "INVALID_REQUEST",
                "message" => "Invalid input / missing input"
            );
            return response($response,401)->header('Content-Type', 'application/json');
        }
    }
}
