<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SimpleXMLElement;
use App\Helpers\PNGHelper;
class PNGController extends Controller
{
    //
    public function authenticate(Request $request){
        $data = $request->getContent();
        $xmlparser = new SimpleXMLElement($data);
        $array_data = array(
            "externalId" => 5436,
            "statusCode" => 0
        );
        return PNGHelper::arrayToXml($array_data,"<authenticate/>");
    }
}
