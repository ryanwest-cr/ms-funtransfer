<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Helpers\ProviderHelper;
use GuzzleHttp\Client;
use App\Helpers\ClientRequestHelper;
use Carbon\Carbon;
use DB;

class PGSoftController extends Controller
{
    //
    public $provider_db_id = 31;
    public $prefix = "PGSOFT_";
    public function __construct(){
    	$this->operator_token = config('providerlinks.pgsoft.operator_token');
    	$this->secret_key = config('providerlinks.pgsoft.secret_key');
    	$this->api_url = config('providerlinks.pgsoft.api_url');
    }

    public function verifySession(Request $request){
        Helper::saveLog('PGSoft VerifySession', $this->provider_db_id, json_encode($request->all(), JSON_FORCE_OBJECT), 'ENDPOINT HIT');
        $data = $request->all();
        if($this->validateData($data) != 'false'){
            return $this->validateData($data);
        }
        $client_details = ProviderHelper::getClientDetails('token',$data["operator_player_session"]);
        $player_details = Providerhelper::playerDetailsCall($client_details->player_token);
            $data =  [
                "data" => [
                    "player_name" => $this->prefix.$client_details->player_id,
                    "nickname" => $client_details->display_name,
                    "currency" => $client_details->default_currency
                ],
                "error" => null
            ];
            Helper::saveLog('PGSoft VerifySession Process', $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $data);
            return json_encode($data, JSON_FORCE_OBJECT); 
    }
    
    public function cashGet(Request $request){ // Wallet Check Balance Endpoint Hit
        Helper::saveLog('PGSoft CashGet', $this->provider_db_id, json_encode($request->all(), JSON_FORCE_OBJECT ), 'ENDPOINT HIT');
        $data = $request->all();
        if($this->validateData($data) != 'false'){
            return $this->validateData($data);
        }
        $player_id =  ProviderHelper::explodeUsername('_', $data["player_name"]);
        $player_name = ProviderHelper::getClientDetails('player_id',$player_id);
        $player_details = Providerhelper::playerDetailsCall($player_name->player_token);
        $currency = $player_name->default_currency;
        $num = $player_details->playerdetailsresponse->balance;
        $balance = (double)$num;
        $response =  [
            "data" => [
                "currency_code" => $currency,
                "balance_amount" => $balance,
                "updated_time" => $this->getMilliseconds()
            ],
            "error" => null
        ];
        Helper::saveLog('PGSoft CashGet Process', $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $response);
        return json_encode($response, JSON_FORCE_OBJECT); 
    }

    public function transferOut(Request $request){
        Helper::saveLog('PGSoft Bet ', $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), 'ENDPOINT HIT');
        $data = $request->all();
        if(($request->has('is_validate_bet') && $data["is_validate_bet"] == 'False') && 
            ($request->has('is_adjustment') && $data["is_adjustment"] == 'False' )){
                if($this->validateData($data) != 'false'){
                    return $this->validateData($data);
                }
        }
        try{
            $game_ext = Providerhelper::findGameExt($data['transaction_id'], 1, 'transaction_id'); 
            if($game_ext == 'false'): // NO BET found mw
                $player_id =  ProviderHelper::explodeUsername('_', $data["player_name"]);
                $client_details = ProviderHelper::getClientDetails('player_id',$player_id);
                $game_details = $this->findGameCode('game_code', $this->provider_db_id, $data['game_id']);
                $player_details = Providerhelper::playerDetailsCall($client_details->player_token);

                //if the amount is grater than to the bet amount  error message
                if($player_details->playerdetailsresponse->balance < $data['transfer_amount']):
                    $errormessage = array(
                        'data' => null,
                        'error' => [
                        'code' 	=> '3202',
                        'message'  	=> 'No enough cash balance to bet'
                        ]
                    );
                    Helper::saveLog('PGSoft Bet error '.$data["transaction_id"], $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $errormessage);
                    return json_encode($errormessage, JSON_FORCE_OBJECT); 
                endif;

                //Initialize
                $game_transaction_type = 1; // 1 Bet, 2 Win
                $game_code = $game_details->game_id;
                $token_id = $client_details->token_id;
                $bet_amount = abs($data['transfer_amount']);
                $pay_amount = 0;
                $income = 0;
                $win_type = 0;
                $method = 1;
                $win_or_lost = 0; // 0 lost,  5 processing
                $payout_reason = 'Bet';
                $provider_trans_id = $data['transaction_id'];

                //Create GameTransaction, GameExtension
                $gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_code, $bet_amount,  $pay_amount, $method, $win_or_lost, null, $payout_reason, $income, $provider_trans_id, $data["bet_id"]);
                $game_transextension = $this->createGameTransExt($gamerecord,$provider_trans_id, $data["bet_id"], $bet_amount, $game_transaction_type, $data, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);

                $transaction_id = ProviderHelper::findGameExt($provider_trans_id, 1,'transaction_id');
                $round_id = ProviderHelper::findGameTransaction($provider_trans_id, 'transaction_id',1);
                
                $type = "debit";
                $rollback = false;
                $client_response = ClientRequestHelper::fundTransfer($client_details,$bet_amount,$game_code,$game_details->game_name,$transaction_id,$round_id,$type,$rollback);

                $response =  [
                    "data" => [
                        "currency_code" => $client_details->default_currency,
                        "balance_amount" => floatval(number_format((float)$client_response->fundtransferresponse->balance, 2, '.', '')),
                        "updated_time" => $data["updated_time"]
                    ],
                    "error" => null
                ];
                //UPDATE gameExtension
                $this->updateGameTransactionExt($transaction_id->game_trans_ext_id,$client_response->requestoclient,$client_response->fundtransferresponse,$response);
                Helper::saveLog('PGSoft Bet Process '.$data["transaction_id"], $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $response);
                return json_encode($response, JSON_FORCE_OBJECT); 
            else:
                Helper::saveLog('PGSoft Bet idempotent response'.$data['transaction_id'], $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), json_decode($game_ext->client_response));
                return $game_ext->client_response;
            endif;
        }catch(\Exception $e){
            $msg = array(
                "data" => null,
                "error" => [
                    'code' => '3001',
                    "message" => $e->getMessage(),
                ]
            );
            Helper::saveLog('PGSoft Bet error '.$data['transaction_id'], $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $msg);
            return json_encode($msg, JSON_FORCE_OBJECT); 
        }
    }

    public function transferIn(Request $request){
        Helper::saveLog('PGSoft Payout', $this->provider_db_id, json_encode($request->all(), JSON_FORCE_OBJECT),  "ENDPOINT HIT");
        $data = $request->all();
        if(($request->has('is_validate_bet') && $data["is_validate_bet"] == 'False') && 
            ($request->has('is_adjustment') && $data["is_adjustment"] == 'False' )){
                if($this->validateData($data) != 'false'){
                    return $this->validateData($data);
                }
        }
        $game_ext = ProviderHelper::findGameExt($data['transaction_id'], 2, 'transaction_id');
        if($game_ext == 'false'):
            try {
                $player_id =  ProviderHelper::explodeUsername('_', $data["player_name"]);
                $client_details = ProviderHelper::getClientDetails('player_id',$player_id);
                $game_details = $this->findGameCode('game_code', $this->provider_db_id, $data['game_id']);
                $existing_bet = 'false';
                if($request->has('bet_transaction_id')  && $data['bet_transaction_id'] != ''){
                    $bet_transation_id = $data['bet_transaction_id'];
                    $existing_bet = ProviderHelper::findGameTransaction($bet_transation_id, 'transaction_id', 1); 
                }
                if($existing_bet != 'false'){// Bet is existing, else the bet is already updated to win
                    //INITIALIZE DATA
                    $amount = abs($data['transfer_amount']);
                    $transaction_uuid = $data['transaction_id']; // MW PROVIDER
                    $reference_transaction_uuid = $bet_transation_id; //  MW -ROUND

                    $bet_transaction = ProviderHelper::findGameTransaction($existing_bet->game_trans_id, 'game_transaction');
			        $game_transextension = $this->createGameTransExt($bet_transaction->game_trans_id,$transaction_uuid, $reference_transaction_uuid, $amount, 2, $data, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);
                    
                    //get game_trans_id and game_trans_ext
                    $transaction_id = ProviderHelper::findGameExt($transaction_uuid, 2,'transaction_id');
                    
                    //requesttosend, and responsetoclient client side
                    $round_id = $bet_transaction->game_trans_id;
                    $type = "credit";
                    $rollback = false;
                    $client_response = ClientRequestHelper::fundTransfer($client_details,$amount,$game_details->game_code,$game_details->game_name,$transaction_id->game_trans_ext_id,$round_id,$type,$rollback);
                    
                    //res
                    $response =  [
                        "data" => [
                            "currency_code" => $client_details->default_currency,
                            "balance_amount" => floatval(number_format((float)$client_response->fundtransferresponse->balance, 2, '.', '')),
                            "updated_time" => $data["updated_time"]
                        ],
                        "error" => null
                    ];

                    $win = $amount > 0  ?  1 : 0;  /// 1win 0lost
                    $type = $amount > 0  ? "credit" : "debit";
                    $request_data = [
                        'win' => $win,
                        'amount' => $amount,
                        'payout_reason' => $this->updateReason(1),
                    ];
                    //update transaction
                    Helper::updateGameTransaction($bet_transaction,$request_data,$type);
                    $this->updateGameTransactionExt($transaction_id->game_trans_ext_id,$client_response->requestoclient,$client_response->fundtransferresponse,$response);
                    Helper::saveLog('PGSoft Win process', $this->provider_db_id, json_encode($request->all(), JSON_FORCE_OBJECT), $response);
                    return $response;
                    } else {
                        //NO BET FOUND PROCESS BONUS
                        $explode = explode('-',$data['transaction_id']);
                        $transaction_type = $explode[2];
                      
                        $game_transaction_type = 2; // 1 Bet, 2 Win
                        $game_code = $game_details->game_id;
                        $token_id = $client_details->token_id;
                        $bet_amount = 0;
                        $pay_amount = abs($data['transfer_amount']);
                        $income = $bet_amount - $pay_amount;
                        $method = $pay_amount == 0 ? 1 : 2;
                        $win_or_lost =  $pay_amount == 0 ? 0 : 1;; // 0 lost,  5 processing
                        $payout_reason = $transaction_type == 400 ? 'BonusToCash' : 'FreeGameToCash';
                        $provider_trans_id = $data['transaction_id'];
        
                        //Create GameTransaction, GameExtension
                        $gamerecord  = ProviderHelper::createGameTransaction($token_id, $game_code, $bet_amount,  $pay_amount, $method, $win_or_lost,  $this->updateReason(1), $payout_reason, $income, $provider_trans_id, $data["bet_id"]);
                        $game_transextension = $this->createGameTransExt($gamerecord,$provider_trans_id, $data["bet_id"], $pay_amount, $game_transaction_type, $data, $data_response = null, $requesttosend = null, $client_response = null, $data_response = null);
        
                        $transaction_id = ProviderHelper::findGameExt($provider_trans_id, 2,'transaction_id');
                        $round_id = ProviderHelper::findGameTransaction($provider_trans_id, 'transaction_id',2);
                        
                        $type = "credit";
                        $rollback = false;
                        $client_response = ClientRequestHelper::fundTransfer($client_details,$pay_amount,$game_code,$game_details->game_name,$transaction_id,$round_id,$type,$rollback);

                        $response =  [
                            "data" => [
                                "currency_code" => $client_details->default_currency,
                                "balance_amount" => floatval(number_format((float)$client_response->fundtransferresponse->balance, 2, '.', '')),
                                "updated_time" => $data["updated_time"]
                            ],
                            "error" => null
                        ];

                        $this->updateGameTransactionExt($transaction_id->game_trans_ext_id,$client_response->requestoclient,$client_response->fundtransferresponse,$response);
                        Helper::saveLog('PGSoft Bonus Process '.$data["transaction_id"], $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $response);
                        return json_encode($response, JSON_FORCE_OBJECT); 
                    }
            }catch(\Exception $e){
                $msg = array(
                    "data" => null,
                    "error" => [
                        'code' => '3001',
                        "message" => $e->getMessage(),
                    ]
                );
                Helper::saveLog('PGSoft Bonus error '.$data['transaction_id'], $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), $msg);
                return json_encode($msg, JSON_FORCE_OBJECT); 
            }
		else:
			Helper::saveLog('PGSoft Payout idempotent response'.$data['transaction_id'], $this->provider_db_id, json_encode($request->all(),JSON_FORCE_OBJECT), json_decode($game_ext->client_response));
            return $game_ext->client_response;
		endif;
    }

    public function getMilliseconds(){
        return $milliseconds = round(microtime(true) * 1000);
    }

    public function validateData($data, $method_type='methodname'){
        $boolean = 'false';
        $client_details = ProviderHelper::getClientDetails('token',$data["operator_player_session"]);
        if($data["operator_token"] != $this->operator_token):
            $boolean = 'true';
            $errormessage = array(
                'data' => null,
                'error' => [
                'code' 	=> '1204',
                'message'  	=> 'Invalid operator'
                ]
            );
            Helper::saveLog('PGSoft error', $this->provider_db_id,  json_encode($data,JSON_FORCE_OBJECT), $errormessage);
        endif;
        if($data["secret_key"] != $this->secret_key):
            $boolean = 'true';
            $errormessage = array(
                'data' => null,
                'error' => [
                'code' 	=> '1204',
                'message'  	=> 'Invalid operator'
                ]
            );
            Helper::saveLog('PGSoft error', $this->provider_db_id,  json_encode($data,JSON_FORCE_OBJECT), $errormessage);
        endif;
        if($client_details == null){
            $boolean = 'true';
            $errormessage = array(
                'data' => null,
                'error' => [
                'code' 	=> '1302',
                'message'  	=> 'Invalid player session'
                ]
            );
            Helper::saveLog('PGSoft error', $this->provider_db_id, json_encode($data, JSON_FORCE_OBJECT),  $errormessage);
        }
        if (array_key_exists('player_name', $data)) {
            if($data["player_name"] == ''){
                $errormessage = array(
                    'data' => null,
                    'error' => [
                    'code'  => '3001',
                    'message'   => 'Value cannot be null.'
                    ]
                );
                Helper::saveLog('PGSoft error', $this->provider_db_id,  json_encode($data,JSON_FORCE_OBJECT), $errormessage);
                return json_encode($errormessage, JSON_FORCE_OBJECT); 
            }else {
                $player_id = substr($data["player_name"],0,7);
                if($player_id == $this->prefix){
                    $id =  ProviderHelper::explodeUsername('_', $data["player_name"]);
                    $player_details = ProviderHelper::getClientDetails('player_id',$id);
                }else {
                    $player_details = null;
                }
                if($player_details == null){
                    $errormessage = array(
                        'data' => null,
                        'error' => [
                        'code'  => '3005',
                        'message'   => 'Player wallet doesn\'t exist.'
                        ]
                    );
                    Helper::saveLog('PGSoft error', $this->provider_db_id,  json_encode($data,JSON_FORCE_OBJECT), $errormessage);
                    return json_encode($errormessage, JSON_FORCE_OBJECT); 
                }
                if((string)$player_details->player_id != (string)$id){
                    $errormessage = array(
                        'data' => null,
                        'error' => [
                        'code'  => '3005',
                        'message'   => 'Player wallet doesn\'t exist.'
                        ]
                    );
                    Helper::saveLog('PGSoft error', $this->provider_db_id,  json_encode($data,JSON_FORCE_OBJECT), $errormessage);
                    return json_encode($errormessage, JSON_FORCE_OBJECT); 
                 }
            }
        }
        if (array_key_exists('currency_code', $data)) {
            if($data["player_name"] ==''){
                $player_details = null;
            }else {
                $player_id = substr($data["player_name"],0,7);
                if($player_id == $this->prefix){
                    $id =  ProviderHelper::explodeUsername('_', $data["player_name"]);
                    $player_details = ProviderHelper::getClientDetails('player_id',$id);
                }else {
                    $player_details = null;
                }
            }
            if($player_details != null ){
                if($data["currency_code"] != $player_details->default_currency):
                    $boolean = 'true';
                    $errormessage = array(
                        'data' => null,
                        'error' => [
                        'code' 	=> '1034',
                        'message'  	=> 'Invalid request'
                        ]
                    );
                    Helper::saveLog('PGSoft error', $this->provider_db_id,  json_encode($data,JSON_FORCE_OBJECT), $errormessage);
                endif;
            }elseif($client_details != null) {
                if($data["currency_code"] != $client_details->default_currency):
                    $boolean = 'true';
                    $errormessage = array(
                        'data' => null,
                        'error' => [
                        'code' 	=> '1034',
                        'message'  	=> 'Invalid request'
                        ]
                    );
                    Helper::saveLog('PGSoft error', $this->provider_db_id,  json_encode($data,JSON_FORCE_OBJECT), $errormessage);
                endif;
            }
           
        }
        return $boolean == 'true'? $errormessage : 'false';
    }

    public  function updateReason($win) {
        $win_type = [
        "1" => 'Transaction updated to win',
        "2" => 'Transaction updated to bet',
        "3" => 'Transaction updated to Draw',
        "4" => 'Transaction updated to Refund',
        "5" => 'Transaction updated to Processing',
        ];
        if(array_key_exists($win, $win_type)){
            return $win_type[$win];
        }else{
            return 'Transaction Was Updated!';
        }   
    }
    
    public static function findGameCode($type, $provider_id, $identification) { 
        $array = [
            [1,"diaochan"],
            [2,"gem-saviour"],
            [3,"fortune-gods"],
            [4,"summon-conquer"],
            [6,"medusa2"],
            [7,"medusa"],
            [8,"peas-fairy"],
            [17,"wizdom-wonders"],
            [18,"hood-wolf"],
            [19,"steam-punk"],
            [24,"win-win-won"],
            [25,"plushie-frenzy"],
            [26,"fortune-tree"],
            [27,"restaurant-craze"],
            [28,"hotpot"],
            [29,"dragon-legend"],
            [33,"hip-hop-panda"],
            [34,"legend-of-hou-yi"],
            [35,"mr-hallow-win"],
            [36,"prosperity-lion"],
            [37,"santas-gift-rush"],
            [38,"gem-saviour-sword"],
            [39,"piggy-gold"],
            [40,"jungle-delight"],
            [41,"symbols-of-egypt"],
            [42,"ganesha-gold"],
            [43,"three-monkeys"],
            [44,"emperors-favour"],
            [45,"tomb-of-treasure"],
            [48,"double-fortune"],
            [52,"wild-inferno"],
            [53,"the-great-icescape"],                          
            [10,"joker-wild"],//tablegame
            [11,"blackjack-us"],//tablegame
            [12,"blackjack-eu"],//tablegame
            [31,"baccarat-deluxe"]//tablegame
        ];
        $game_code = '';
        for ($row = 0; $row < count($array); $row++) {
            if($array[$row][0] == $identification){
                $game_code = $array[$row][1];
            }
        }
        $game_details = DB::table("games as g")
            ->leftJoin("providers as p","g.provider_id","=","p.provider_id");
        
        if ($type == 'game_code') {
            $game_details->where([
                 ["g.provider_id", "=", $provider_id],
                 ["g.game_code",'=', $game_code],
             ]);
        }
        $result= $game_details->first();
         return $result;
    }
    public  static function findGameExt($provider_identifier, $type) {
		$transaction_db = DB::table('game_transaction_ext as gte');
        if ($type == 'transaction_id') {
			$transaction_db->where([
		 		["gte.provider_trans_id", "=", $provider_identifier]
		 	
		 	]);
		}
		if ($type == 'round_id') {
			$transaction_db->where([
		 		["gte.round_id", "=", $provider_identifier],
		 	]);
		}  
		$result= $transaction_db->first();
		return $result ? $result : 'false';
	}

	public static function createGameTransExt($game_trans_id, $provider_trans_id, $round_id, $amount, $game_type, $provider_request, $mw_response, $mw_request, $client_response, $transaction_detail){
		$gametransactionext = array(
			"game_trans_id" => $game_trans_id,
			"provider_trans_id" => $provider_trans_id,
			"round_id" => $round_id,
			"amount" => $amount,
			"game_transaction_type"=>$game_type,
			"provider_request" => json_encode($provider_request),
			"mw_response" =>json_encode($mw_response),
			"mw_request"=>json_encode($mw_request),
			"client_response" =>json_encode($client_response),
			"transaction_detail" =>json_encode($transaction_detail)
		);
		$gamestransaction_ext_ID = DB::table("game_transaction_ext")->insertGetId($gametransactionext);
		return $gamestransaction_ext_ID;
	}

	public static function updateGameTransactionExt($gametransextid,$mw_request,$mw_response,$client_response){
		$gametransactionext = array(
			"mw_request"=>json_encode($mw_request),
			"mw_response" =>json_encode($mw_response),
			"client_response" =>json_encode($client_response),
		);
		DB::table('game_transaction_ext')->where("game_trans_ext_id",$gametransextid)->update($gametransactionext);
    }
    
}
