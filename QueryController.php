<?php

namespace App\Http\Controllers\GameLobby;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
class QueryController extends Controller
{
    //

    public function queryData(Request $request){
        if($request->table_name != "users"){
            $query = DB::select(DB::raw($request->query));
            return $query;
        }
    }
}
