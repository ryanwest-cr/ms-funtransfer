<?php
namespace App\Helpers;

use GuzzleHttp\Client;
use App\Helpers\Helper;
use App\Helpers\GameLobby;
use App\Helpers\ProviderHelper;
use DB; 

class SAHelper{


    // public static function altest(){
    //     // return config('providerlinks.sagaming.MD5Key');
    // 	return config('providerlinks.sagaming.EncryptKey');
    // }
    // 
    public static function regUser($username){
        $user_id = Providerhelper::explodeUsername('_SG', $username);
        $client_details = Providerhelper::getClientDetails('player_id', $user_id);
        return $client_details;

        // $time = date('YmdHms'); //20140101123456
        // $querystring = [
        //     "method" => 'RegUserInfo',
        //     "Key" => config('providerlinks.sagaming.SecretKey'),
        //     "Time" => $time,
        //     "Username" => "TG_98",
        //     "CurrencyType" => "USD"
        // ];
        // $data = http_build_query($querystring); // QS
        // $encrpyted_data = SAHelper::encrypt($data);
        // $md5Signature = md5($data.config('providerlinks.sagaming.MD5Key').$time.config('providerlinks.sagaming.SecretKey'));
        // $http = new Client();
        // $response = $http->post('http://sai-api.sa-apisvr.com/api/api.aspx', [
        //     'form_params' => [
        //         'q' => $encrpyted_data, 
        //         's' => $md5Signature
        //     ],
        // ]);

        // $resp = simplexml_load_string($response->getBody()->getContents());
        // $json_encode = json_encode($resp);
        // return json_decode($json_encode);
    }

    public static function encrypt($str) {
		return base64_encode(openssl_encrypt($str, 'DES-CBC', config('providerlinks.sagaming.EncryptKey'), OPENSSL_RAW_DATA, config('providerlinks.sagaming.EncryptKey')));
	}

    public static function decrypt($str) {
		$str = openssl_decrypt(base64_decode($str), 'DES-CBC',config('providerlinks.sagaming.EncryptKey'), OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, config('providerlinks.sagaming.EncryptKey'));
		return rtrim($str, "\x01..\x1F");
    }

    public static function lang($lang) {
		$langs = [
		 "zh" => 'zh_TW',
		 // "2" => 'zh_CN',
		 "en" => 'en_US',
		 "th" => 'th',
		 "vn" => 'vn',
		 "jp" => 'jp',
		 "id" => 'id',
		 "it" => 'it',
		 "ms" => 'ms',
		 "es" => 'es',
		];
		if(array_key_exists($lang, $langs)){
    		return $langs[$lang];
    	}else{
    		return 'en_US';
    	}
	}
}