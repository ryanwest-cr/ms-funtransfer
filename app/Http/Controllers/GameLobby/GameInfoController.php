<?php

namespace App\Http\Controllers\GameLobby;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Helpers\Helper;
use Session;
use DB;

class GameInfoController extends Controller
{
	public function getDemoGame(Request $request){
	 	$games = DB::table('games as g')
                ->select('g.game_demo')
                ->leftJoin('providers as p', "g.provider_id", "=", "p.provider_id")
                ->where('g.game_code', $request->game_code)
                ->where('p.provider_name', $request->game_provider)
                ->first();
        // return $games ? $games : false;        
        return json_encode($games);        
	}

	public function getNewestGames(Request $request){
	
	 	$games = DB::table('games as g')
				->select('g.game_name', 'gt.game_type_name', 'g.icon as game_icon', 'g.game_code', 'p.provider_name', 'p.icon as provider_icon')
                ->leftJoin('providers as p', "g.provider_id", "=", "p.provider_id")
                ->leftJoin('game_types as gt', "gt.game_type_id", "=", "g.game_type_id")
                ->latest('g.created_at')
				->limit(20)
                ->get();
        return $games;        
	}

	public function getMostPlayed(Request $request){
		// $games = DB::table('games as g')
		// 		->select('g.game_name', 'gt.game_type_name', 'g.game_code', 'p.provider_name', 'p.icon as provider_icon', DB::raw('COUNT(g.game_id) as mostplayed'))
 		//         ->leftJoin('providers as p', "g.provider_id", "=", "p.provider_id")
  		//		   ->leftJoin('game_types as gt', "gt.game_type_id", "=", "g.game_type_id")
  		//		   ->leftJoin('game_transactions as gtrans', "gtrans.game_id", "=", "g.game_id")
  		//		   ->groupBy('gtrans.game_id')
  		//		   ->orderBy('mostplayed','DESC')
		//         ->take(5)
		//         ->get();    


	    // $games = DB::select(DB::raw('
	    //   		select provider_id, game_type_id, game_name, count(game_id) as total
		// 	from game_transactions
		// 	inner join games using (game_id)
		// 	group by game_id
		// 	order by total desc limit 20'));

	   $games = DB::table('game_transactions as gt')
	  			 ->select('g.provider_id', 'gts.game_type_name', 'g.icon as game_icon', 'g.game_type_id', 'g.game_name', 'g.game_code', 'p.provider_name', 'p.icon as provider_icon', DB::raw('COUNT(gt.game_id) as total'))
	  			 ->join('games as g', "gt.game_id", "=", "g.game_id")
	  			 ->leftJoin('providers as p', "g.provider_id", "=", "p.provider_id")
	  			 ->leftJoin('game_types as gts', "gts.game_type_id", "=", "g.game_type_id")
	  			 ->groupBy('gt.game_id')
	  			 ->orderBy('total', 'DESC')
	  			 ->limit(20)
	  			 ->get();

        return $games;        
	}

	public function getTopGames(Request $request){
		
	   			// $games = DB::select(DB::raw('
	   			// select 
				// provider_id, game_type_id, game_name, sum(pay_amount) as total
				// from game_transactions
				// inner join games using (game_id)
				// group by game_id
				// order by total desc limit 20'));

	   $games = DB::table('game_transactions as gt')
	  			 ->select('g.provider_id', 'gts.game_type_name', 'g.icon as game_icon', 'g.game_type_id', 'g.game_name', 'g.game_code', 'p.provider_name', 'p.icon as provider_icon', DB::raw('sum(gt.pay_amount) as total'))
	  			 ->join('games as g', "gt.game_id", "=", "g.game_id")
	  			 ->leftJoin('providers as p', "g.provider_id", "=", "p.provider_id")
	  			 ->leftJoin('game_types as gts', "gts.game_type_id", "=", "g.game_type_id")
	  			 ->groupBy('gt.game_id')
	  			 ->orderBy('total', 'DESC')
	  			 ->limit(20)
	  			 ->get();

        return $games;        
	}

	public function getBetList(Request $request){
		// return $request->sample;

		// $gg = DB::table('games as g')
		// 	->where('provider_id', 11)
		// 	->get();

		// $array = array();  
		// foreach($gg as $g){
		// 	DB::table('games')
		//            ->where('provider_id', 11)
		//            ->where('game_id', $g->game_id)
		//            ->update(['icon' => 'https://asset-dev.betrnk.games/images/games/casino/bole/eng/388x218/'.$g->game_code.'.jpg']);
					
		// }	  
		// return '11';	
	}

	public function getTopProvider(Request $request){
	    $data = array();
	    $provider = DB::table('game_transactions as gt')
	  			 ->select('g.provider_id', 'p.provider_name', 'p.icon as provider_icon', DB::raw('sum(gt.pay_amount) as total'))
	  			 ->join('games as g', "gt.game_id", "=", "g.game_id")
	  			 ->leftJoin('providers as p', "g.provider_id", "=", "p.provider_id")
	  			 ->leftJoin('game_types as gts', "gts.game_type_id", "=", "g.game_type_id")
	  			 ->groupBy('gt.game_id')
	  			 ->orderBy('total', 'DESC')
	  			 ->limit(1)
	  			 ->first();
	  	if($provider != null){
	  		// foreach ($provider as $pro) {
		  		$data['provider'] = $provider;
		  	// }
		  	$games = DB::table('games as g')
					->select('g.game_name', 'gt.game_type_name', 'g.icon as game_icon', 'g.game_code', 'p.provider_name')
	                ->leftJoin('providers as p', "g.provider_id", "=", "p.provider_id")
	                ->leftJoin('game_types as gt', "gt.game_type_id", "=", "g.game_type_id")
	                ->where('p.provider_id', $provider->provider_id)
	                ->get();
	        $data['games'] = $games;  
	        return $data;    
	  	}else{
	  		return 'false';
	  	}   
         
	}

	
	/*
	 * DEPRECATED
	 *
	 */
	public function getGameSuggestions(Request $request){			
		 $player_details = $this->_getClientDetails('token', $request->token);
         $query = DB::table("game_suggestions AS gs")
                 ->select('gs.game_id', 'gs.client_id', 'gs.classification', 'gt.game_type_name','sp.sub_provider_name', 'p.provider_name', 'p.icon as provider_icon', 'g.game_name', 'g.game_code', 'g.icon')
                 ->leftJoin("games AS g", "gs.game_id", "=", "g.game_id")
                 ->leftJoin("providers AS p", "p.provider_id", "=", "g.provider_id")
                 ->leftJoin("game_types AS gt", "gt.game_type_id", "=", "g.game_type_id")
                 ->leftJoin("sub_providers AS sp", "g.sub_provider_id", "=", "sp.sub_provider_id")
                 ->where('gs.client_id', $player_details->client_id)
                 ->limit(20);
                 $result = $query->get()->toArray();

        $game_suggestions = ['spotlight' => [], 'choice' => []];
        foreach ($result as $key => $value) {
            array_push($game_suggestions[$value->classification], $value);
        }

        $player_data = [
            'player_details' => $player_details,
            'game_suggestions' => $game_suggestions,
            'client_code' => $client_details['client_code']
        ];

        return $game_suggestions;
	}



	/**
	 *	@return client player details 
	 *	@param accept player_id, token
	 */
	public function getClientPlayerDetails(Request $request){
			if($request->has('player_id')){
				$client_details = $this->_getClientDetails('player_id', $request->player_id);
			}else if($request->has('token')){
				$client_details = $this->_getClientDetails('token', $request->token);
			}else{
				return ['status' => 'failed'];
			}

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
				"gameid" => "",
				"clientid" => $client_details->client_id,
				"playerdetailsrequest" => [
					"client_player_id" => $client_details->client_player_id,
					"token" => $client_details->player_token ? $client_details->player_token : '',
					"username" => $client_details->username ? $client_details->username : '',
					"gamelaunch" => false,
					"refreshtoken" => $request->has('refreshtoken') ? true : false,
				]
			];

			$guzzle_response = $client->post($client_details->player_details_url,
				['body' => json_encode($datatosend)]
			);

			$client_response = json_decode($guzzle_response->getBody()->getContents());
			return json_encode($client_response);
	}


	/**
	 *	@return player details
	 */
	public function _getClientDetails($type = "", $value = "", $client_id="") 
	{
		$query = DB::table("clients AS c")
				 ->select('p.client_id', 'p.player_id', 'p.client_player_id','p.username', 'p.email', 'p.language', 'p.currency', 'pst.token_id', 'pst.player_token' , 'c.client_url', 'pst.status_id', 'p.display_name', 'c.client_api_key', 'cat.client_token AS client_access_token', 'ce.player_details_url', 'ce.fund_transfer_url')
				 ->leftJoin("players AS p", "c.client_id", "=", "p.client_id")
				 ->leftJoin("player_session_tokens AS pst", "p.player_id", "=", "pst.player_id")
				 ->leftJoin("client_endpoints AS ce", "c.client_id", "=", "ce.client_id")
				 ->leftJoin("client_access_tokens AS cat", "c.client_id", "=", "cat.client_id");
					if ($type == 'token') {
						$query->where([
					 		["pst.player_token", "=", $value],
					 		// ["pst.status_id", "=", 1]
					 	]);
					}
					if ($type == 'player_id') {
						$query->where([
					 		["p.player_id", "=", $value],
					 		// ["pst.status_id", "=", 1]
					 	]);
					}
					if ($type == 'site_url') {
						$query->where([
					 		["c.client_url", "=", $value],
					 	]);
					}
					if ($type == 'username') {
						$query->where([
					 		["p.username", $value],
					 	]);
					}
					if ($type == 'username_and_cid') {
						$query->where([
					 		["p.username", $value],
					 		["p.client_id", $client_id],
					 	]);
					}
					$result= $query
					 			->latest('token_id')
					 			->first();
			return $result;
	}

}