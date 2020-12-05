<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use App\Helpers\AlHelper;
use App\Helpers\Helper;
// use App\Helpers\SAHelper;
// use App\Helpers\GoldenFHelper;
// use App\Helpers\SessionWalletHelper;
use App\Helpers\ProviderHelper;
use GuzzleHttp\Client;
use App\Helpers\ClientRequestHelper;
// use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
// use Session;
// use Auth;
use DB;

/**
 *  DEBUGGING! CALLS! -RiAN ONLY! 10:21:51
 */
class AlController extends Controller
{

    public $hashen = '$2y$10$37VKbBiaJzWh7swxTpy6OOlldjjO9zdoSJSMvMM0.Xi2ToOv1LcSi';

    public function index(Request $request){
      // $token = Helper::tokenCheck('n58ec5e159f769ae0b7b3a0774fdbf80');
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
    }


    public function checkCLientPlayer(Request $request){
        // DB::enableQueryLog();

        $start_method = microtime(true);

        if(!$request->header('hashen')){
          return ['al' => 'OOPS RAINDROPS'];
        }
        if(!Hash::check($request->header('hashen'),$this->hashen)){
          return ['al' => 'OOPS RAINDROPS'];
        }
        // if(!Hash::check($request->hashkey,$this->hashkey)){
        //   return ['al' => 'OOPS RAINDROPS'];
        // }

        if($request->debugtype == 1){
          $start_qry = microtime(true);
          $client_details = $this->getClientDetails($request->type,  $request->identifier);
          $end_qry = microtime(true);
          if($client_details == 'false'){
            return ['al' => 'NO PLAYER FOUND'];
          }else{
            $client = new Client([
                'headers' => [ 
                  'Content-Type' => 'application/json',
                  'Authorization' => 'Bearer '.$client_details->client_access_token
                ]
            ]);
            $datatosend = ["access_token" => $client_details->client_access_token,
              "hashkey" => md5($client_details->client_api_key.$client_details->client_access_token),
              "type" => "playerdetailsrequest",
              "datesent" => Helper::datesent(),
              "clientid" => $client_details->client_id,
              "playerdetailsrequest" => [
                "player_username"=>$client_details->username,
                "client_player_id" => $client_details->client_player_id,
                "token" => $client_details->player_token,
                "gamelaunch" => true,
                "refreshtoken" => $request->has('refreshtoken') ? true : false,
              ]
            ];
            try{  
              $request_time = microtime(true);
              $guzzle_response = $client->post($client_details->player_details_url,
                  ['body' => json_encode($datatosend)]
              );
              $receive_time = microtime(true);

              $client_response = json_decode($guzzle_response->getBody()->getContents());
              $client_response->request_body = $datatosend;
              // Helper::saveLog('PLAYER DETAILS LOG', 999, json_encode(DB::getQueryLog()), "TIME PLAYERDETAILS");
              // return json_encode($client_response);
              $client_response->lumen_boot_start = ($start_method - LARAVEL_START) * 1000;
              $client_response->qry_player = ($end_qry - $start_qry) * 1000;
              $client_response->player_api = ($receive_time - $request_time) * 1000;
              return json_encode($client_response);
              // return response(json_encode($client_response), 200)
              //   ->header('Content-Type', 'application/json');
            }catch (\Exception $e){
               $message = [
                'request_body' => $datatosend,
                'al' => $e->getMessage(),
               ];
              //  Helper::saveLog('PLAYER DETAILS LOG', 999, json_encode(DB::getQueryLog()), "TIME PLAYERDETAILS");
               return $message;
            } 
          }
        }elseif($request->debugtype == 2){
            $gg = DB::table('seamless_request_logs');
            if ($request->type == 'provider_id') {
              $gg->where([
                ["provider_id", "=", $request->identifier],
              ]);
            } 
            if ($request->type == 'method_name') {
              $gg->where([
                ["method_name", "LIKE", "%$request->identifier%"],
              ]);
            } 
            $result = $gg->limit($request->has('limit') ? $request->limit : 1);
            $result = $gg->latest()->get(); // Added Latest (CQ9) 08-12-20 - Al
            // Helper::saveLog('PLAYER DETAILS LOG', 999, json_encode(DB::getQueryLog()), "TIME PLAYERDETAILS");
            return $result ? $result : 'false';
        }elseif($request->debugtype == 3){
            $gg = DB::table('game_transaction_ext');
            if ($request->type == 'game_trans_id') {
              $gg->where([
                ["game_trans_id", "=", $request->identifier],
              ]);
            } 
            if ($request->type == 'game_trans_ext_id') {
              $gg->where([
                ["game_trans_ext_id", "=", $request->identifier],
              ]);
            } 
            if ($request->type == 'round_id') {
              $gg->where([
                ["round_id", "=", $request->identifier],
              ]);
            } 
            if ($request->type == 'provider_trans_id') {
              $gg->where([
                ["provider_trans_id", "=", $request->identifier],
              ]);
            } 
            $result = $gg->limit($request->has('limit') ? $request->limit : 1);
            $result = $gg->latest()->get(); // Added Latest (CQ9) 08-12-20 - Al
            // Helper::saveLog('PLAYER DETAILS LOG', 999, json_encode(DB::getQueryLog()), "TIME PLAYERDETAILS");
            return $result ? $result : 'false';
        }
        
        // elseif($request->debugtype == 4){
        //       $query = DB::select(DB::raw($request->identifier));
        //       return $query;
        // }

    }


    public function resendTransaction(Request $request){
        $general_details = ["aggregator" => [], "provider" => [], "client" => []];
        $data = $request->all();
        
        if(!$request->header('hashen')){
          return ['status'=>'failed', ['msg'=>'ACCESS DENIED']];
        }
        if(!Hash::check($request->header('hashen'),$this->hashen)){
          return ['status'=>'failed', ['msg'=>'ACCESS DENIED']];
        }
       
        if(!$request->has('round_id') || !$request->has('player_id') || !$request->has('win_type') || !$request->has('game_ext_type') || !$request->has('transaction_type') || !$request->has('amount')){
          return ['status'=>'failed', ['msg'=>'Missing Required Parameters']];
        }

        $client_details = Providerhelper::getClientDetails('player_id',  $request->player_id);
        if($client_details == 'false'){
           return ['status'=>'failed', ['msg'=>'Player Not Found']];
        }
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
        if($player_details == 'false'){
           return  $response = ["status" => "failed", "msg" =>  'Server Timeout'];
        }

        $round_id = ProviderHelper::findGameTransaction($request->round_id, 'game_transaction');
        $provider_trans_id = $round_id->provider_trans_id;
        $provider_round_id = $round_id->round_id;

        $general_details['client']['before_balance'] = ProviderHelper::amountToFloat($player_details->playerdetailsresponse->balance);

        $amount = $request->amount;
        $bet_amount = $request->has('bet_amount') ? $request->bet_amount : $round_id->bet_amount;

        $win_type = $request->win_type;  // 0 Lost, 1 win, 2 failed, 3 draw, 4 refund, 5 processing
        $entry_type = $win_type == 1 ? 2 : 1;
        $game_ext_type = $request->game_ext_type; // 1 ber, 2 win, 3 refund

        $transaction_type = $request->has('transaction_type') ? $request->transaction_type : 'credit';
        $rollback = $request->has('rollback') ? $request->rollback : false;

        $game_information = DB::table('games')->where('game_id', $round_id->game_id)->first();
        if($game_information == null){ 
            return  $response = ["status" => "failed", "msg" =>  'game not found'];
        }

        $pay_amount = $round_id->pay_amount + $amount;
        $income = $bet_amount - $pay_amount;
        $identifier_type = 'game_trans_id';
        $update_bet = false;
        $update_bet_amount = $round_id->bet_amount;
      
        if($win_type == 4){
          if(!$request->has('rollback') || !$request->has('rollback_type')){
            return  $response = ["status" => "failed", "msg" =>  'When type 4 it should have rollback parameter and rollback type parameter [round,bet,win])'];
          }
          if($request->rollback == 'false' || $request->rollback == false){
            return  $response = ["status" => "failed", "msg" =>  'rollback parameter must be true'];
          }
          if($request->game_ext_type != 3){
            return  $response = ["status" => "failed", "msg" =>  'Game Extension type should be 3'];
          }
          if($request->rollback_type == 'round'){ // Whole round (including bet and wins)
             $pay_amount = 0;
             $income = 0;
             if($amount != abs($round_id->bet_amount-$round_id->pay_amount)){
                if($round_id->bet_amount-$round_id->pay_amount > 0){
                  $transaction_type = 'credit';
                }else{
                  $transaction_type = 'debit';
                }
                return  $response = ["status" => "failed", "msg" =>  'Rollback all round the amount dont match the bet and win amounts it should be '.abs($round_id->bet_amount-$round_id->pay_amount).' and transaction type is '.$transaction_type];
             }
          }
          if($request->rollback_type == 'bet'){
            if($transaction_type == 'debit'){
               return  $response = ["status" => "failed", "msg" =>  'refunding bet should be credit transaction type'];
            }
            $update_bet = true;
            $update_bet_amount = $update_bet_amount - $amount;
            $pay_amount = $round_id->pay_amount;
            $income = $update_bet_amount - $pay_amount ;
          }
          if($request->rollback_type == 'win'){
            if($transaction_type == 'credit'){
               return  $response = ["status" => "failed", "msg" => "refunding win should be debit transaction type"];
            }
            $pay_amount = $round_id->pay_amount - $amount;
            $income = $update_bet_amount - $pay_amount ;
          }
        }

        $game_transextension = ProviderHelper::createGameTransExtV2($round_id->game_trans_id,$provider_trans_id, $provider_round_id, $amount, $game_ext_type);

        try {
          $client_response = ClientRequestHelper::fundTransfer($client_details,abs($amount),$game_information->game_code,$game_information->game_name,$game_transextension, $round_id->game_trans_id, $transaction_type, $rollback);
          Helper::saveLog('RESEND CRID '.$round_id->game_trans_id, 999,json_encode($request->all()), $client_response);
        } catch (\Exception $e) {
          $response = ["status" => "failed", "msg" => $e->getMessage()];
          ProviderHelper::updatecreateGameTransExt($game_transextension, 'FAILED', $response, 'FAILED', $e->getMessage(), 'FAILED', $general_details);
          Helper::saveLog('RESEND - FATAL ERROR', 999, $response, Helper::datesent());
          return $response;
        }

        if(isset($client_response->fundtransferresponse->status->code) 
            && $client_response->fundtransferresponse->status->code == "200"){
            $general_details['client']['after_balance'] = ProviderHelper::amountToFloat($client_response->fundtransferresponse->balance);
            $response = ["status" => "success", "msg" => 'transaction success', 'general_details' => $general_details,'data' => $client_response];      

           ProviderHelper::updateGameTransaction($round_id->game_trans_id, $pay_amount, $income, $win_type, $entry_type,$identifier_type,$update_bet_amount,$update_bet);
           ProviderHelper::updatecreateGameTransExt($game_transextension, $data, $response, $client_response->requestoclient, $client_response, 'SUCCESS',$general_details);
        }elseif(isset($client_response->fundtransferresponse->status->code) 
            && $client_response->fundtransferresponse->status->code == "402"){
            $response = ["status" => "failed", "msg" => 'transaction failed', 'general_details' => $general_details, "data" => $client_response];
            ProviderHelper::updatecreateGameTransExt($game_transextension, $data, $response, $client_response->requestoclient, $client_response, 'FAILED',$general_details);
        }else{ // Unknown Response Code
            $response = ["status" => "failed", "msg" => 'Unknown Status Code', 'general_details' => $general_details, "data" => $client_response];
            ProviderHelper::updatecreateGameTransExt($game_transextension, $data, $response, $client_response->requestoclient, $client_response, 'FAILED',$general_details);
        }  

        Helper::saveLog('RESEND TRIGGERED', 999, json_encode($response), Helper::datesent());
        return $response;
    }

    public  function getClientDetails($type = "", $value = "", $gg = 1, $providerfilter = 'all')
    {
      // DB::enableQueryLog();
      if ($type == 'token') {
        $where = 'where pst.player_token = "' . $value . '"';
      }
      if ($providerfilter == 'fachai') {
        if ($type == 'player_id') {
          $where = 'where ' . $type . ' = "' . $value . '" AND pst.status_id = 1 ORDER BY pst.token_id desc';
        }
      } else {
        if ($type == 'player_id') {
          $where = 'where ' . $type . ' = "' . $value . '"';
        }
      }
      if ($type == 'username') {
        $where = 'where p.username = "' . $value . '"';
      }
      if ($type == 'token_id') {
        $where = 'where pst.token_id = "' . $value . '"';
      }
      if ($providerfilter == 'fachai') {
        $filter = 'LIMIT 1';
      } else {
        // $result= $query->latest('token_id')->first();
        $filter = 'order by token_id desc LIMIT 1';
      }

      $query = DB::select('select `p`.`client_id`, `p`.`player_id`, `p`.`email`, `p`.`client_player_id`,`p`.`language`, `p`.`currency`, `p`.`test_player`, `p`.`username`,`p`.`created_at`,`pst`.`token_id`,`pst`.`player_token`,`c`.`client_url`,`c`.`default_currency`,`pst`.`status_id`,`p`.`display_name`,`op`.`client_api_key`,`op`.`client_code`,`op`.`client_access_token`,`ce`.`player_details_url`,`ce`.`fund_transfer_url`,`p`.`created_at` from player_session_tokens pst inner join players as p using(player_id) inner join clients as c using (client_id) inner join client_endpoints as ce using (client_id) inner join operator as op using (operator_id) ' . $where . ' ' . $filter . '');

      $client_details = count($query);
      // Helper::saveLog('GET CLIENT LOG', 999, json_encode(DB::getQueryLog()), "TIME GET CLIENT");
      return $client_details > 0 ? $query[0] : null;
    }

    public function tapulan(Request $request){


      return [
    'response_time' => microtime(true) - LARAVEL_START
];

      $client = new Client();
      $returnURL = urlencode(urlencode("http://daddy.betrnk.games"));
      $guzzle_response = $client->get('https://edemo.endorphina.com/api/link/accountId/1002/hash/' . md5("endorphina_4OfAKing@ENDORPHINA") . '/returnURL/' . $returnURL);
      // $guzzle_response = $client->get('http://edemo.endorphina.com/api/link/accountId/1002/hash/' . md5("endorphina2_SugarGliderDice@ENDORPHINA"));
      // $guzzle_response = $client->get('https://edemo.endorphina.com/api/link/accountId/1002/hash/5bb33a3ec5107d46fe5b02d77ba674d6');

      dd($guzzle_response->getBody()->getContents());


    // return 'OOPS RAINDROPS';

    // https://edemo.endorphina.com/session/open/sid/18e2994c39681b301f91d927821f210f

      // $game_code = 'endorphina2_DurgaDD@ENDORPHINA';
      // $game_name = explode('_', $game_code);
      // $game_code = explode('@', $game_name[1]);
      // $game_gg = $game_code[0];
      // $arr = preg_replace("([A-Z])", " $0", $game_gg);
      // $arr = explode(" ", trim($arr));
      // if(count($arr) == 1){
      //   $url = 'https://endorphina.com/games/'. strtolower($arr[0]).'/play';
      // }else{
      //   $url = 'https://endorphina.com/games/' . strtolower($arr[0]).'-'. strtolower($arr[1]) . '/play';
      // }
      // return $url;

    // $demoLink = file_get_contents('https://edemo.endorphina.com/api/link/accountId/EDEMO /hash/'. md5("endorphina_4OfAKing@ENDORPHINA"). '/returnURL/' . $returnURL);
    // dd($demoLink);
    // return json_encode($demoLink);

    

    // $demoLink = file_get_contents('https://edemo.endorphina.com/api/link/accountId/1002/hash/5bb33a3ec5107d46fe5b02d77ba674d6');
    // return json_encode($demoLink);
      

      // return ;

    //  dd(SessionWalletHelper::isMultipleSession(10210, 'qa64d864b89525573dd3ad78d16d6df5'));

      // $player_token = $this->getInfoPlayerGameRound('mbbb58340803493e5f29dfeddf105e47');
      // dd($player_token->sub_provider_id);
      // if($player_token == false){
      //   return 'nawal';
      // }


      // return 1;
      // $client_key = DB::table('clients')->where('client_id', $client_id)->first();
      // if(!$client_key){ return false; }
      // $operator_id =  $client_key->operator_id;
      // $aws_config = config('providerlinks.aws');

      // if(array_key_exists(($operator_id.$client_key->default_currency), $aws_config)){
      //   return $aws_config[$operator_id];
      // }else{
      //   return false;
      // }

      // $merchant_key = AWSHelper::findMerchantIdByClientId(1)['merchant_key'];
      // $merchant_key = AWSHelper::findMerchantIdByClientId(1);

      // return $merchant_key;

      // $client_details = Providerhelper::getClientDetails('player_id',  98);
      // $player= DB::table('players')->where('client_id', $client_details->client_id)
      //     ->where('player_id', $client_details->player_id)->first();
      // if(isset($player->player_status)){
      //   if($player != '' || $player != null){
      //     if($player->player_status == 2|| $player->player_status == 3){
      //      return 'false';
      //     }
      //   }
      // }


      // return 1;

      // if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
      //   if($_SERVER["HTTP_X_FORWARDED_FOR"] == '119.92.151.236'){
      //     $msg = 'your whilisted';
      //   }else{
      //     $msg = 'your blocked';
      //   }
      // }else{
      //   $msg = 'not set';
      // }



      // return $this->getUserIpAddr();
      // Helper::saveLog('IP LOG', 999, json_encode($request->ip()), $_SERVER["REMOTE_ADDR"].' '.$request->ip().' '.$this->getUserIpAddr());

      //  if(isset($_SERVER['HTTP_CLIENT_IP'])):
      //       Helper::saveLog('IP LOG2', 999, json_encode($request->ip()), $_SERVER["HTTP_CLIENT_IP"]);
      //  elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR'])):
      //      Helper::saveLog('IP LOG3', 999, json_encode($request->ip()), $_SERVER["HTTP_X_FORWARDED_FOR"]);
      //  elseif(isset($_SERVER['HTTP_X_FORWARDED'])):
      //      Helper::saveLog('IP LOG4', 999, json_encode($request->ip()), $_SERVER["HTTP_X_FORWARDED"]);
      //  elseif(isset($_SERVER['HTTP_FORWARDED_FOR'])):
      //      Helper::saveLog('IP LOG5', 999, json_encode($request->ip()), $_SERVER["HTTP_FORWARDED_FOR"]);
      //  elseif(isset($_SERVER['HTTP_FORWARDED'])):
      //       Helper::saveLog('IP LOG6', 999, json_encode($request->ip()), $_SERVER["HTTP_FORWARDED"]);
      //  elseif(isset($_SERVER['REMOTE_ADDR'])):
      //       Helper::saveLog('IP LOG7', 999, json_encode($request->ip()), $_SERVER["REMOTE_ADDR"]);
      //  else:

      //  endif;

      // return  $msg;
      
      // $client_details = Providerhelper::getClientDetails('player_id',  98);
      // dd($client_details);

      // return response()
      //       ->json(['name' => 'Abigail', 'state' => 'CA'])
      //       ->Artisan::call('al:riandraft');
      //       // ->withCallback(Artisan::call('al:riandraft'));

      // Artisan::call('al:riandraft');

      // return $this->callMe();
      // return self::callMe();
    }


    private static function callMe(){
      return 'HAHAHAHHAaaaaaaaaaaaaaa';
    }

    public function testTransaction(){
      return ClientRequestHelper::getTransactionId("43210","87654321");
    }


    public function debugMe(){
        // SAGAMING
        $client_details = Providerhelper::getClientDetails('player_id', 98);
        $time = date('YmdHms'); //20140101123456
        $method = 'VerifyUsername';
        $querystring = [
            "method" => $method,
            "Key" => config('providerlinks.sagaming.SecretKey'),
            "Time" => $time,
            "Username" => config('providerlinks.sagaming.prefix').$client_details->player_id,
        ];
        $method == 'RegUserInfo' || $method == 'LoginRequest' ? $querystring['CurrencyType'] = $client_details->default_currency : '';
        $data = http_build_query($querystring); // QS
        $encrpyted_data = SAHelper::encrypt($data);
        $md5Signature = md5($data.config('providerlinks.sagaming.MD5Key').$time.config('providerlinks.sagaming.SecretKey'));
        $http = new Client();
        $response = $http->post(config('providerlinks.sagaming.API_URL'), [
            'form_params' => [
                'q' => $encrpyted_data, 
                's' => $md5Signature
            ],
        ]);
        Helper::saveLog('ALDEBUG '.$method, config('providerlinks.sagaming.pdbid'), json_encode(['for_params' => ['q'=>$encrpyted_data, 's'=>$md5Signature]]), $querystring);
        return $response->getBody()->getContents();
    }

    public function currency(){
      return ClientRequestHelper::currencyRateConverter("USD",12829967);
    }

    public function uploadImgApi(Request $request){
        if ($request->hasFile('image')) {
            /** Make sure you will properly validate your file before uploading here */
            /** Get the file data here */
            $file = $request->file('image');
           

            
            // return $file->getClientOriginalExtension();  
            /** Generate random unique_name for file */
            $fileName = "valz-example.".$file->getClientOriginalExtension();
            // $path = '/var/www/betrnk.games/asset-dev.betrnk.games/images/games/casino/';
            $path = "D:/valz/Middleware Backoffice/October/api_oct31/";
            // // File::makeDirectory($path, $mode = 0777, true, true);
            $file->move($path,$fileName);
            // $file->move(public_path().'/uploads/test', $fileName);
            /** Return data */
            return response()->json([
                'status'    => 'success',
                'message'   => 'Your success message',
                'data'      => [
                    'uploadedFileName' => $fileName
                ]
            ], 200);   
        }
    }

}
