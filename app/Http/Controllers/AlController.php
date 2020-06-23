<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\AlHelper;
use GuzzleHttp\Client;
use Session;
use Auth;
use DB;

/**
 *  DEBUGGING!
 */
class AlController extends Controller
{
    public function index(Request $request){

        // https://asset-dev.betrnk.games/images/games/casino/habanero/Habanero_12Zodiacs_384x216.png

        $gg = DB::table('games as g')
            ->where('provider_id', $request->provider_id)
            ->where('sub_provider_id', $request->subprovider)
            ->get();

        $array = array();  
        foreach($gg as $g){
            DB::table('games')
                   ->where('provider_id',$request->provider_id)
                   ->where('sub_provider_id',$request->subprovider)
                   ->where('game_id', $g->game_id)
                   ->update(['icon' => 'https://asset-dev.betrnk.games/images/games/casino/'.$request->prefix.'/'.$g->game_code.'.'.$request->extension.'']);
                   // ->update(['icon' => 'https://asset-dev.betrnk.games/images/casino/'.$request->prefix.'/eng/388x218/'.$g->game_code.'.jpg']);
                    
        }     
        return 'ok';    



        // public function getDemoGame(Request $request){
            // $games = DB::table('games as g')
            //         ->select('g.game_demo')
            //         ->leftJoin('providers as p', "g.provider_id", "=", "p.provider_id")
            //         ->where('g.game_code', 'rbwar')
            //         ->where('p.provider_name', 'Bole Gaming')
            //         ->first();
            // dd($games);        
        // }

        // dd(Auth()->user()->id);
        // if($payment_method == "paymongo"){

        //         if($request->input("cardnumber_pm")&&$request->input("currency_pm")&&$request->input("exp_month")&&$request->input("exp_year")&&$request->input("amount_pm")&&$request->input("cvc")){

        //             $trans_update_url = 'http://demo.freebetrnk.com/depositupdate';
        //             $http = new Client();
        //             $response = $http->post('https://api-mw.betrnk.games/payment', [
        //             // $response = $http->post('http://middleware.freebetrnk.com/public/payment', [

        //                 'form_params' => [
        //                     'payment_method' => $request->payment_method,
        //                     'currency' => $request->currency_pm,
        //                     'amount' => $request->amount_pm,
        //                     'cardnumber' => $request->cardnumber_pm,
        //                     'exp_month' => $request->exp_month,
        //                     'exp_year' => $request->exp_year,
        //                     'cvc' => $request->cvc,
        //                     'player_token' => session()->get('player_token'),
        //                     'site_url' => $_SERVER['SERVER_NAME'],
        //                     // 'site_url' => 'demo.freebetrnk.com',
        //                     'merchant_user'=> getUsername(),
        //                     'merchant_user_id' => getUserId(),
        //                     'merchant_user_email' => getUserEmail(),
        //                     'merchant_user_display_name' => getUserDisplayName(),
        //                     'merchant_user_balance'=> checkBal(),
        //                     'trans_update_url' => $trans_update_url             
        //                 ],
        //                'headers' =>[
        //                   'Authorization' => 'Bearer '.$this->connectTo(),
        //                   'Accept'     => 'application/json' 
        //                ]
        //             ]);

        //             // return json_decode($response->getBody(),TRUE);
        //             // return redirect()->route('e.deposit');
        //             // dd($res = json_decode($response->getBody(),TRUE));
        //             $res = json_decode($response->getBody(),true);
        //             if(isset($res['error'])){
        //                  // return redirect()->route('pay.depositpage')->with('error','Transaction Failed!');
        //                  return ['message' => $res['error']];
        //             } 
        //             if(isset($res['id'])){
        //                  $id = getUserId();
        //                  $deposit = Deposit::create([
        //                         'user_id'       =>  $id,
        //                         'transaction_id' => $res['id'],
        //                         'amount'        => $res['amount'],
        //                         'status_id'     =>  1
        //                  ]);
        //                  // return redirect()->route('pay.depositpage')->with('success','Transaction Success!');
        //                  return ['message' => 'Transaction Success!'];
        //             }
        //         }
        //         else{
        //             // dd('mali!');
        //             return ['message' => 'Transaction Unknown!'];
        //         }
        // }


         // $data = array(
         //          "_token" => "2qMRruoGUCyZQ8C00xN5suRhmVK2JJMJfFAdQqbR",
         //          "token" => "ge941ffbf5c6ddff3884f124a3666ec4",
         //          "user_id" => "6",
         //          "email" => "riandraft@gmail.com",
         //          "displayname" => "draft rian",
         //          "username" => "riandraft",
         //          "exitUrl" => "https://demo.freebetrnk.com/casino",
         //          "_previous" => "",
         //          "_flash" => ""
         //        );
         //    session($data);
         // dd(isset(Auth::user()->username) ? Auth::user()->username : Auth::user()->email);
         // 
         // dd($_SERVER['SERVER_NAME']);
    	// return view('al');
    }


}
