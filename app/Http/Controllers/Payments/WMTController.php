<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Helpers\Helper;
use App\PayTransaction;
use DB;
use Carbon\Carbon;
class WMTController extends Controller
{
    //
    public function makeSettlement(Request $request){
        $requestfromclient = json_encode($request->getContent());
        Helper::saveLog("WMT LOGS TEST",15,$request,"test");
        return 0;
    }
}
