<?php
namespace App\Helpers;

use GuzzleHttp\Client;
use App\Helpers\Helper;
use App\Helpers\GameLobby;
use App\Helpers\ProviderHelper;
use DB; 

class SAHelper{

    public static function userManagement($username, $method){
        $user_id = Providerhelper::explodeUsername(config('providerlinks.sagaming.prefix'), $username);
        $client_details = Providerhelper::getClientDetails('player_id', $user_id);
        $time = date('YmdHms'); //20140101123456
        $querystring = [
            "method" => $method,
            "Key" => config('providerlinks.sagaming.SecretKey'),
            "Time" => $time,
            "Username" => config('providerlinks.sagaming.prefix').$client_details->player_id,
        ];
        $method == 'RegUserInfo' || $method == 'LoginRequest' ? $querystring['CurrencyType'] = $client_details->default_currency : '';
        $data = http_build_query($querystring); // QS
        $encrpyted_data = SAHelper::encrypt($data);
        $md5Signature = md5($data.config('providerlinks.sagaming.MD5Key').$time.config('providerlinks.sagaming.SecretKey'));
        $http = new Client();
        $response = $http->post(config('providerlinks.sagaming.API_URL'), [
            'form_params' => [
                'q' => $encrpyted_data, 
                's' => $md5Signature
            ],
        ]);
        $resp = simplexml_load_string($response->getBody()->getContents());
        $json_encode = json_encode($resp);
        Helper::saveLog('SA UserManagement '.$method, config('providerlinks.sagaming.pdbid'), json_encode($querystring), json_decode($resp));
        Helper::saveLog('SA UserManagement '.$method, config('providerlinks.sagaming.pdbid'), json_encode($querystring), json_decode($json_encode));
        return json_decode($json_encode);
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