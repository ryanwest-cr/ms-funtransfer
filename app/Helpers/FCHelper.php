<?php
namespace App\Helpers;
use DB;
use App\Services\AES;
class FCHelper
{
	public static function AESEncode($data){
        $aes = new AES('8t4A17537S1d5rwz');
        return $aes->AESEncode($data);
    }
    public static function AESDecode($data){
        $aes = new AES('8t4A17537S1d5rwz');
        return $aes->AESdecode($data);
    }
}