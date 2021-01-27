<?php

namespace App\Services;

use App\Traits\ConsumeExternalServices;

class GameTransactionServices{

    use ConsumeExternalServices;

    
    /**
     * baseUri
     * Set the baseuri in ConsumeExternalServices
     * @var string
     */
    public $baseUri;
    
    /**
     * __construct
     *
     * @return void
     */
    public function __construct(){
        $this->baseUri = config('services.game_transaction.base_uri');
    }
    
    /**
     * checkGameTransactionExt
     *
     * @param  string $type the column to search
     * @param  mixed $data to use for searching
     * @return void Response from the MicroServices
     */
    public function checkGameTransactionExt($type,$data){
        return json_decode($this->performRequest('GET',"/api/v1/gametransactionext/{$type}/{$data}"));
    }    
    /**
     * checkGameTransaction
     *
     * @param  string $type the column to search
     * @param  mixed $data to use for searching
     * @return void Response from the MicroServices
     */
    public function checkGameTransaction($type,$data){
        return json_decode($this->performRequest('GET',"/api/v1/gametransaction/{$type}/{$data}"));
    } 
    /**
     * createGameTransaction
     *
     * @param  string $type the type of transaction |in: 1(debit),2(credit)
     * @param  mixed $provider_data the data from provider 
     * @param  mixed $game_data the game data use for creating transaction
     * @param  mixed $client_details the data to use for client identifications 
     * @return Object gametransactionId
     */
    public function createGameTransaction($type,$provider_data,$game_data,$client_details){
        $data = array(
            'provider_trans_id' => $provider_data["transid"],
            'token_id' => $client_details->token_id,
            'game_id' => $game_data->game_id,
            'round_id' => $provider_data["roundid"],
            'bet_amount' => $provider_data["amount"],
            'win'=>5,
            'pay_amount' => 0,
            'income' => 0,
            'entry_id' => 1,
        );
        return json_decode($this->performRequest('POST',"/api/v1/gametransaction",$data));
    }
    /**
     * createGameTransaction
     *
     * @param  string $type the type of transaction |in: 1(debit),2(credit)
     * @param  mixed $provider_data the data from provider 
     * @param  mixed $game_data the game data use for creating transaction
     * @param  mixed $client_details the data to use for client identifications 
     * @return Object gametransactionId
     */
    public function updateGameTransaction($existing_data,$provider_data,$type){
        $data["updateby"]="gametransactionid";
        $data["value"]=$existing_data->game_trans_id;
        switch ($type) {
			case "debit":
					$data["win"] = 0;
					$data["pay_amount"] = 0;
					$data["income"]=$existing_data->bet_amount-$provider_data["amount"];
					$data["entry_id"] = 1;
				break;
			case "credit":
					$data["win"] = $provider_data["win"];
					$data["pay_amount"] = abs($provider_data["amount"]);
					$data["income"]=$existing_data->bet_amount-$provider_data["amount"];
					$data["entry_id"] = 2;
					$data["payout_reason"] = $provider_data["payout_reason"];
				break;
			case "refund":
					$data["win"] = 4;
					$data["pay_amount"] = $provider_data["amount"];
					$data["entry_id"] = 2;
					$data["income"]= $existing_data->bet_amount-$provider_data["amount"];
					$data["payout_reason"] = "Refund of this transaction ID: ".$provider_data["transid"]."of GameRound ".$provider_data["roundid"];
				break;
			case "fail":
				$data["win"] = 2;
				$data["pay_amount"] = $provider_data["amount"];
				$data["entry_id"] = 1;
				$data["income"]= 0;
				$data["payout_reason"] = "Fail  transaction ID: ".$provider_data["transid"]."of GameRound ".$provider_data["roundid"] .":Insuffecient Balance";
			break;
			default:
		}
        return json_decode($this->performRequest('PUT',"/api/v1/gametransaction",$data));
    }    
    /**
     * createGameTransactionExt
     *
     * @param  mixed $data array[] to save int the gametransaction ext table
     * @return Object gameTransactionExtId
     */
    public function createGameTransactionExt($data){
        return json_decode($this->performRequest('POST',"/api/v1/gametransactionext",$data));
    }    
    /**
     * updateGameTransactionExt
     *
     * @param  array[] $data
     * @return Object gameTransactionExt Status
     * 
     */
    public function updateGameTransactionExt($data){
        return json_decode($this->performRequest('PUT',"/api/v1/gametransactionext",$data));
    }

}