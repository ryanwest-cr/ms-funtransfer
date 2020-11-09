<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

class BackOfficeController extends Controller
{
    public function setProvider(Request $request){
    	if($request->type == 'add'){
    		DB::table('providers')->insert(["provider_name" => $request->provider_name, "icon" => $request->icon]);
    	}
    	if($request->type == 'update'){
    		if($request->has('icon')){
    			$provider = DB::table('providers')->where('provider_id','=',$request->id)->update(['provider_name' => $request->provider_name, 'icon' => $request->icon]);
    		}else{
    			$provider = DB::table('providers')->where('provider_id','=',$request->id)->update(['provider_name' => $request->provider_name]);
    		}
    	}
        if($request->type == 'maintenance'){
            $turn_on =  DB::table('providers')->where('provider_id','=',$request->id)->update(['on_maintenance' => $request->on_maintenance ]);
        }
        if($request->type == 'delete'){
            $delete =  DB::table('providers')->where('provider_id','=',$request->id)->delete();
        }
    }

    public  function setSubProvider(Request $request){
        if($request->type == 'add'){
            DB::table('sub_providers')->insert(["sub_provider_id" => $request->sub_provider_id, "sub_provider_name" => $request->sub_provider_name, 'provider_id' => $request->provider_id, "icon" => $request->icon]);
        }
        if($request->type == 'update'){
            if($request->has('icon')){
                $provider = DB::table('sub_providers')->where('sub_provider_id','=',$request->sub_provider_id)->update(['sub_provider_name' => $request->sub_provider_name, 'icon' => $request->icon, 'provider_id' => $request->provider_id ]);
            }else{
                $provider = DB::table('sub_providers')->where('sub_provider_id','=',$request->sub_provider_id)->update(['sub_provider_name' => $request->sub_provider_name, 'provider_id' => $request->provider_id ]);
            }
        }
        if($request->type == 'maintenance'){
            $turn_on =  DB::table('sub_providers')->where('sub_provider_id','=',$request->sub_provider_id)->update(['on_maintenance' => $request->on_maintenance ]);
        }
        if($request->type == 'delete'){
            $delete =  DB::table('sub_providers')->where('sub_provider_id','=',$request->sub_provider_id)->delete();
        }
    }

    public function setGames(Request $request){
        if($request->type == 'add'){
            $add = DB::table('games')->insert(['game_id' => $request->game_id,'game_type_id' => $request->game_type_id,'provider_id' => $request->provider_id,'sub_provider_id' => $request->sub_provider_id,'game_name' => $request->game_name,'icon' => $request->icon,'game_code' => $request->game_code,'min_bet' => $request->min_bet,'max_bet' => $request->max_bet,'pay_lines' => $request->pay_lines,'info' => $request->info,]);
        }
        if($request->type == 'update'){
            if($request->has('icon')){
                 $update = DB::table('games')->where('game_id','=',$request->game_id)->update(['game_type_id' => $request->game_type_id,'provider_id' => $request->provider_id,'sub_provider_id' => $request->sub_provider_id,'game_name' => $request->game_name,'icon' => $request->icon,'game_code' => $request->game_code,'min_bet' => $request->min_bet,'max_bet' => $request->max_bet,'pay_lines' => $request->pay_lines,'info' => $request->info,]);
            }else{
                 $update = DB::table('games')->where('game_id','=',$request->game_id)->update(['game_type_id' => $request->game_type_id,'provider_id' => $request->provider_id,'sub_provider_id' => $request->sub_provider_id,'game_name' => $request->game_name, 'game_code' => $request->game_code,'min_bet' => $request->min_bet,'max_bet' => $request->max_bet,'pay_lines' => $request->pay_lines,'info' => $request->info,]);
            }
        }
        if($request->type == 'maintenance'){
            $maintenance =  DB::table('games')->where('game_id','=',$request->game_id)->update(['on_maintenance' => $request->on_maintenance]);
        }
        if($request->type == 'delete'){
            $delete =  DB::table('games')->where('game_id','=',$request->game_id)->delete();
        }
    }

    public function setGameType(Request $request){
        if($request->type == 'add'){
            $add = DB::table('game_types')->insert(['game_type_id' => $request->game_type_id, 'game_type_name' => $request->game_type_name]);        }
        if($request->type == 'update'){
            $update = DB::table('game_types')->where('game_type_id','=',$request->game_type_id)->update(['game_type_name' => $request->game_type_name]);
        }
        if($request->type == 'delete'){
            $delete =  DB::table('game_types')->where('game_type_id','=',$request->game_type_id)->delete();
        }
    }

    public function setGameSuggestion(Request $request){
        
    }
    public function setOperator(Request $request){
        // return $request->all();
        DB::table('seamless_request_logs')->insert([
                    "method_name" => "api",
                    "provider_id" => "1",
                    "request_data" => json_encode($request->all()),
                    "response_data" => "a"
                ]);
        if($request->type == 'add'){
            $add_operator = DB::table("operator")->insert(["operator_id" => $request->operator['operator_id'], "client_name" => $request->operator['client_name'], "client_code" => $request->operator['client_code'], "client_api_key" => $request->operator['client_api_key'], "client_access_token" => $request->operator['client_access_token'], "status_id" => $request->operator['status_id'] ]);
            $add_user = DB::table("users")->insert(["id" => $request->users['id'], "name" => $request->users['name'], "email" => $request->users['email'], "password" => $request->users['password'], "username" => $request->users['username'], "is_admin" => $request->users['is_admin'], "user_type" => $request->users['user_type'], "image" => $request->users['image'] ]);
            $add_clients = DB::table("clients")->insert(["client_id" => $request->clients['client_id'], "operator_id" => $request->clients['operator_id'], "client_name" => $request->clients['client_name'], "client_code" => $request->clients['client_code'], "client_pass" => $request->clients['client_pass'], "default_currency" => $request->clients['default_currency'], "default_language" => $request->clients['default_language'], "icon" => $request->clients['icon'], "status_id" => $request->clients['status_id']]);
            $add_oauth_clients = DB::table("oauth_clients")->insert(["id" => $request->oauth_clients['id'], "secret" => $request->oauth_clients['secret'], "name" => $request->oauth_clients['name'] ]);
            $add_client_endpoints = DB::table("client_endpoints")->insert([ "id" => $request->client_endpoints['id'], "client_id" => $request->client_endpoints['client_id'], "player_details_url" => $request->client_endpoints['player_details_url'], "fund_transfer_url" => $request->client_endpoints['fund_transfer_url'] ]);
        }
        if($request->type == 'update'){
            $add_operator = DB::table("operator")->where("operator_id","=",$request->operator['operator_id'])->update([ "client_api_key" => $request->operator['client_api_key'], "client_access_token" => $request->operator['client_access_token'] ]);
            if($request->has('password')){
                $add_user = DB::table("users")->where("id","=",$request->users["id"])->update([ "email" => $request->users['email'], "password" => $request->users['password'], "username" => $request->users['username'] ]);
            }else{
                $add_user = DB::table("users")->where("id","=",$request->users["id"])->update([ "email" => $request->users['email'], "username" => $request->users['username'] ]);
            }
            $add_clients = DB::table("clients")->where("client_id","=",$request->clients['client_id'])->update([ "client_name" => $request->clients['client_name'], "client_pass" => $request->clients['client_pass'] ]);
            $add_oauth_clients = DB::table("oauth_clients")->where("id","=",$request->oauth_clients['id'])->update([ "secret" => $request->oauth_clients['secret'], "name" => $request->oauth_clients['name'] ]);
            
        }
    }

    public function setClients(Request $request){
        DB::table('seamless_request_logs')->insert([
            "method_name" => "clients",
            "provider_id" => "1",
            "request_data" => json_encode($request->all()),
            "response_data" => "a"
        ]);
        if($request->type == 'add'){
            $add_clients = DB::table("clients")->insert(["client_id" => $request->clients['client_id'], "operator_id" => $request->clients['operator_id'], "client_name" => $request->clients['client_name'], "default_currency" => $request->clients['default_currency'], "default_language" => $request->clients['default_language'], "icon" => $request->clients['icon'], "status_id" => $request->clients['status_id']]);
            $add_client_endpoints = DB::table("client_endpoints")->insert([ "id" => $request->client_endpoints['id'], "client_id" => $request->client_endpoints['client_id'], "player_details_url" => $request->client_endpoints['player_details_url'], "fund_transfer_url" => $request->client_endpoints['fund_transfer_url'] ]);
        }
        if($request->type == 'update'){
            $add_clients = DB::table("clients")->where("client_id","=",$request->clients['client_id'])->update([ "icon" => $request->clients['icon'] ]);
            $update_client_endpoints = DB::table("client_endpoints")->where("id","=",$request->client_endpoints['id'])->update(["player_details_url" => $request->client_endpoints['player_details_url'], "fund_transfer_url" => $request->client_endpoints['fund_transfer_url'] ]);
        }
        if($request->type == 'status'){
            $update_status = DB::table("clients")->where("client_id","=",$request->clients['client_id'])->update([ "status_id" => $request->clients['status_id'] ]);
        }
        if($request->type == 'delete'){
            $delete_clietns = DB::table('clients')->where('client_id','=',$request->clients['client_id'])->delete();
            $delete_client_endpoints = DB::table('client_endpoints')->where('client_id','=',$request->clients['client_id'])->delete();
        }
    }

    public function setPlayers(Request $request){
        $update = DB::table('players')->where('player_id','=',$request->player_id)->update(['player_status' => $request->status]);
    }

    public function subscribeUpdate(Request $request){
        DB::table('seamless_request_logs')->insert([
            "method_name" => "subscribe",
            "provider_id" => "1",
            "request_data" => json_encode($request->all()),
            "response_data" => "a"
        ]);
        $cgs = DB::table("client_game_subscribe")->where("client_id","=",$request->client_id)->get();
        if(count($cgs) > 0){
            $esp = DB::table("excluded_sub_provider")->where("cgs_id","=",$cgs[0]->cgs_id)->get();

            if($request->has('spid')){

                foreach($request->spid as $spid){
                    $chck_esp = DB::table("excluded_sub_provider")->where("cgs_id","=",$cgs[0]->cgs_id)->where("sub_provider_id","=",$spid)->get();
                    if(!count($chck_esp) > 0){
                        $insert = DB::table("excluded_sub_provider")->insert([ 'cgs_id' => $cgs[0]->cgs_id, 'sub_provider_id' => $spid ]);
                    }
                }
            }
            if($request->has('del_spid')){
                foreach($request->del_spid as $del_spid){
                    $chck_esp = DB::table("excluded_sub_provider")->where("cgs_id","=",$cgs[0]->cgs_id)->where("sub_provider_id","=",$del_spid)->get();
                    if(count($chck_esp) > 0){
                        $del = DB::table("excluded_sub_provider")->where("cgs_id","=",$cgs[0]->cgs_id)->where("sub_provider_id","=",$del_spid)->delete();
                    }
                }
            }
            if($request->has('gid')){
                foreach($request->gid as $gid){
                    $check_ge = DB::table("game_exclude")->where("cgs_id","=",$cgs[0]->cgs_id)->where("game_id","=",$gid)->get();
                    if(!count($check_ge) > 0){
                        $insert = DB::table("game_exclude")->insert([ 'cgs_id' => $cgs[0]->cgs_id, 'game_id' => $gid ]);
                    }
                }
            }
            if($request->has('del_gid')){
                foreach($request->del_gid as $del_gid){
                    $check_ge = DB::table("game_exclude")->where("cgs_id","=",$cgs[0]->cgs_id)->where("game_id","=",$del_gid)->get();
                    if(count($check_ge) > 0){

                       $del = DB::table("game_exclude")->where("cgs_id","=",$cgs[0]->cgs_id)->where("game_id","=",$del_gid)->delete();
                    }
                }
            }


       }else{

            $add_cgs = DB::table("client_game_subscribe")->insert(['client_id' => $request->client_id, 'provider_selection_type' => 'all', 'status_id' => '1']);

            $cgs = DB::table("client_game_subscribe")->where("client_id","=",$request->client_id)->get();
            $esp = DB::table("excluded_sub_provider")->where("cgs_id","=",$cgs[0]->cgs_id)->get();

            if($request->has('spid')){
                foreach($request->spid as $spid){
                    $chck_esp = DB::table("excluded_sub_provider")->where("cgs_id","=",$cgs[0]->cgs_id)->where("sub_provider_id","=",$spid)->get();
                    if(!count($chck_esp) > 0){
                        $insert = DB::table("excluded_sub_provider")->insert([ 'cgs_id' => $cgs[0]->cgs_id, 'sub_provider_id' => $spid ]);
                    }
                }
            }
            if($request->has('del_spid')){
                foreach($request->del_spid as $del_spid){
                    $chck_esp = DB::table("excluded_sub_provider")->where("cgs_id","=",$cgs[0]->cgs_id)->where("sub_provider_id","=",$del_spid)->get();
                    if(count($chck_esp) > 0){
                        $del = DB::table("excluded_sub_provider")->where("cgs_id","=",$cgs[0]->cgs_id)->where("sub_provider_id","=",$del_spid)->delete();
                    }
                }
            }
       }
    }
}
