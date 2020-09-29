<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\AlHelper;
use App\Helpers\Helper;
use App\Helpers\SAHelper;
use App\Helpers\ProviderHelper;
use GuzzleHttp\Client;
use App\Helpers\ClientRequestHelper;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Session;
use Auth;
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
        if(!$request->has('hashen')){
          return ['al' => 'OOPS RAINDROPS'];
        }
        if(!Hash::check($request->hashen,$this->hashen)){
          return ['al' => 'OOPS RAINDROPS'];
        }
        if($request->debugtype == 1){
          $client_details = Providerhelper::getClientDetails($request->type,  $request->identifier);
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
              $guzzle_response = $client->post($client_details->player_details_url,
                  ['body' => json_encode($datatosend)]
              );
              $client_response = json_decode($guzzle_response->getBody()->getContents());
              $client_response->request_body = $datatosend;
              return json_encode($client_response);
            }catch (\Exception $e){
               $message = [
                'request_body' => $datatosend,
                'al' => $e->getMessage(),
               ];
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
            return $result ? $result : 'false';
        }

    }




    public function tapulan(){

      // return response()
      //       ->json(['name' => 'Abigail', 'state' => 'CA'])
      //       ->Artisan::call('al:riandraft');
      //       // ->withCallback(Artisan::call('al:riandraft'));

      // Artisan::call('al:riandraft');

      return $this->callMe();
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



}
