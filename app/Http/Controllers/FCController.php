<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AES;
use App\Helpers\FCHelper;
class FCController extends Controller
{
    //


    public function SampleEncrypt(Request $request){
        $data = $request->getContent();

        return array("AESENCRYPT"=>FCHelper::AESEncode($data),"SIGN"=>md5($request->getContent()));
    }
    public function SampleDecrypt(){
        $data = '7Jhu1hCXPmisYLWVGIKhulHfbIWwss8oNfXCdmzP3VPIxJf7ZgYvHBfVPhcec5eo';
        return FCHelper::AESDecode($data);
    }

}
