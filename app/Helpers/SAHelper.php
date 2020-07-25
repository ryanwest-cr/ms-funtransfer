<?php
namespace App\Helpers;

use GuzzleHttp\Client;
use App\Helpers\Helper;
use App\Helpers\GameLobby;
use App\Helpers\ProviderHelper;
use DB; 

class SAHelper{

    public static function altest(){
    	return config('providerlinks.sagaming.MD5Key');
    }

    public function __construct($key, $iv=0 ) {
        $this->key = $key;
        if( $iv == 0 ) {
            $this->iv = $key;
        } else {
            $this->iv = $iv;
        }
    }

    public static function encrypt($str) {
		return base64_encode(openssl_encrypt($str, 'DES-CBC', $this->key, OPENSSL_RAW_DATA, $this->iv  ) );
	}

    public static function decrypt($str) {
		$str = openssl_decrypt(base64_decode($str), 'DES-CBC', $this->key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $this->iv);
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