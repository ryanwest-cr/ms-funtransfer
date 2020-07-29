<?php
namespace App\Services;

class AES{
	public $key;

	public function __construct($_key = '')
    {
		$this->key = $_key;
	}
	
	/**
	 * >= PHP 7.1 AES-128-ECB
	 */
	/**
     * [encrypt AES加密]
     * @param    [type]                   $input [要加密的数据]
     * @param    [type]                   $key   [加密key]
     * @return   [type]                          [加密后的数据]
     */
    public function AESencode($input)
    {
		try{
			$data = openssl_encrypt($input, 'AES-128-ECB', $this->key, OPENSSL_RAW_DATA);
			$data = base64_encode($data);
		}
		catch(\Exception $e){
			// 捕捉 Exception 詳細資訊
            logger(__METHOD__.' ERROR: '.$e->getMessage().' LINE:'.$e->getLine());
			//Laravel try catch 要使用這種catch(\Exception $e)
		}
        return $data;
    }
    /**
     * [decrypt AES解密]
     * @param    [type]                   $sStr [要解密的数据]
     * @param    [type]                   $sKey [加密key]
     * @return   [type]                         [解密后的数据]
     */
    public function AESdecode($sStr)
    {
		try{
			$decrypted = openssl_decrypt(base64_decode($sStr), 'AES-128-ECB', $this->key, OPENSSL_RAW_DATA);
		}
		catch(\Exception $e){
			// 捕捉 Exception 詳細資訊
            logger(__METHOD__.' ERROR: '.$e->getMessage().' LINE:'.$e->getLine());
			//Laravel try catch 要使用這種catch(\Exception $e)
		}
        return $decrypted;
    }
}
