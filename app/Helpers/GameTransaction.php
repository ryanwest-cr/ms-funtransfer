<?php
namespace App\Helpers;
use DB;

class GameTransaction
{
	public static function save($method, $request_data, $game_data, $client_data, $player_data) {
		/*var_dump($request_data); die();*/
		$trans_data = [
					"token_id" => $player_data->token_id,
					"game_id" => $game_data->game_id,
					"round_id" => $request_data["roundid"],
					"income" => $request_data["income"]
				];

		switch ($method) {
		    case "debit":
					$trans_data["provider_trans_id"] = $request_data["transid"];
					$trans_data["bet_amount"] = $request_data["amount"];
					$trans_data["win"] = 0;
					$trans_data["pay_amount"] = 0;
					$trans_data["entry_id"] = 1;
					// check if this is a free round
					if(array_key_exists('free_round_data', $request_data)) {
						$trans_data["payout_reason"] = json_encode($request_data["free_round_data"]);
					}
		        break;
		    case "credit":
			        $trans_data["provider_trans_id"] = $request_data["transid"];
			        $trans_data["bet_amount"] = 0;
			        $trans_data["win"] = 1;
			        $trans_data["pay_amount"] = abs($request_data["amount"]);
			        $trans_data["entry_id"] = 2;
			        $trans_data["payout_reason"] = $request_data["reason"];
		        break;
		    case "rollback":
		    		$trans_data["provider_trans_id"] = (array_key_exists('transid', $request_data) ? $request_data["transid"] : '');
					$trans_data["bet_amount"] = 0;
					$trans_data["win"] = 1;
					$trans_data["pay_amount"] = $game_data->bet_amount;
					$trans_data["entry_id"] = 3;
					$trans_data["payout_reason"] = "Rollback of transaction ID: ".$game_data->game_trans_id;
		        break;
		    case "cancelled":
		    		$trans_data["provider_trans_id"] = $request_data["transid"];
			        $trans_data["bet_amount"] = 0;
			        $trans_data["win"] = 0;
			        $trans_data["pay_amount"] = abs($request_data["amount"]);
			        $trans_data["entry_id"] = 3;
			        $trans_data["payout_reason"] = $request_data["reason"];
		        break;

		    default:
		}
		$id = DB::table('game_transactions')->insertGetId($trans_data);
		return $id; 
	}

	public static function update_rollback($method, $request_data, $game_data, $client_data, $player_data) {

		$game_details = DB::table("game_transactions AS g")
				 ->where("g.provider_trans_id", $request_data['transactionId'])
				 ->first();

		$income = 0; 
		$win = 0;
		$pay_amount = $game_details->bet_amount;
		$entry_id = 3;

        $update = DB::table('game_transactions')
                ->where('game_trans_id', $game_details->game_trans_id)
                ->update(['pay_amount' => $pay_amount, 'income' => $income, 'win' => $win, 'entry_id' => $entry_id]);
     
		return ($update ? $game_details->game_trans_id : false);
	}

	public static function update($method, $request_data, $game_data, $client_data, $player_data) {

		$game_details = DB::table("game_transactions AS g")
				 ->where("g.round_id", $request_data['roundid'])
				 ->first();
		
		$income = $game_details->income; 
		$win = $game_details->win;
		$pay_amount = $game_details->pay_amount;
		$entry_id = $game_details->entry_id;
		
		if($request_data["amount"] > 0.00) {
			$win = 1;
			$pay_amount = $game_details->pay_amount + $request_data["amount"];
			$income = $game_details->bet_amount - $pay_amount;
			$entry_id = 2;
		}

        $update = DB::table('game_transactions')
                ->where('game_trans_id', $game_details->game_trans_id)
                ->update(['pay_amount' => $pay_amount, 'income' => $income, 'win' => $win, 'entry_id' => $entry_id]);
                
		return ($game_details ? $game_details->game_trans_id : false);
	}

	public static function find($original_trans_id) {
		$transaction_id = DB::table('game_transactions')
								->where('provider_trans_id', $original_trans_id)
								->first();

		return ($transaction_id ? $transaction_id : false);
	}

	public static function find_refund($original_trans_id) {
		$transaction_result = DB::table('game_transactions')
								->where('provider_trans_id', $original_trans_id)
								->where('entry_id', 3)
								->first();

		return ($transaction_result ? $transaction_result->game_trans_id : false);
	}

	public static function rollback($original_trans_id) {
		$end_round_result = DB::table('game_transactions')
                ->where('provider_trans_id', $original_trans_id)
                ->update(['entry_id' => 3]);
                
		return ($end_round_result ? true : false);
	}

	public static function bulk_rollback($round_id) {
		$transactions = DB::table('game_transactions')
								->where('round_id', $round_id)
								->where('entry_id', 1)
								->get();

		/*$transactions_ids_to_roll_back = [];*/
		$transactions_to_roll_back = [];
		foreach ($transactions as $key => $value) {
			/*array_push($transactions_ids_to_roll_back, $value->game_trans_id);*/
			$transactions_to_roll_back[$value->game_trans_id] = $value;
		}

		/*$end_round_result = DB::table('game_transactions')
                ->where('round_id', $round_id)
                ->whereIn('game_trans_id', $transactions_ids_to_roll_back)
                ->update(['entry_id' => 3]);*/

		return (count($transactions_to_roll_back) > 0 ? $transactions_to_roll_back : false);
	}

}
