<?php

namespace ze;


//Zenario Encryption Wrapper Library
//These are wrapper functions for the https://github.com/defuse/php-encryption library
//N.b. mcrypt.so and openssl.so should be running
class zewl {
	const ZEWL_APPLY_BASE64 = true;
	protected static $key;
	
	private static $loaded = null;
	
	//Formerly "loadZewl()"
	public static function init() {
		if (self::$loaded === null) {
			if (\ze\zewl::loadClientKey()) {
				return self::$loaded = true;
			}
			
			self::$loaded = false;
		}
		return self::$loaded;
	}
	
	
	public static function loadKey($ascii) {
		if (\ze\zewl::ZEWL_APPLY_BASE64) {
			$ascii = \ze\ring::base64To16($ascii);
		}
		self::$key = \Defuse\Crypto\Key::loadFromAsciiSafeString($ascii);
	}
	
	public static function loadClientKey() {
		if (!file_exists($dir = CMS_ROOT. 'zenario_custom/key/client.php')
		 || !($ascii = require $dir)) {
			return false;
		}
		
		if (\ze\zewl::ZEWL_APPLY_BASE64) {
			$ascii = \ze\ring::base64To16($ascii);
		}
		
		try {
			$lockedKey = \Defuse\Crypto\KeyProtectedByPassword::loadFromAsciiSafeString($ascii);
		
			try {
				if ($company = \ze\zewl::company()) {
					self::$key = $lockedKey->unlockKey($company);
				} else {
	                throw new \Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException('No company specified.');
				}
			} catch (\Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $e) {
				if ($company = \ze\zewl::company('.old')) {
					self::$key = $lockedKey->unlockKey($company);
				}
			}
		} catch (\Exception $e) {
			self::$key = false;
		}
		
		return (bool) self::$key;
	}
	
	protected static function company($suffix = '') {
		if (defined('COMPANY_CONF_PATH')
		 && file_exists($file = COMPANY_CONF_PATH. 'company'. $suffix)
		 && ($contents = file_get_contents($file))) {
			return trim($contents);
		}
	}
	
	public static function checkConfIsOkay($suffix = '') {
		return (bool) \ze\zewl::company();
	}
	
	public static function encrypt($message, $rawBinary) {
		if (!self::$key) {
			return null;
		}
		if ($rawBinary) {
			return '%'. \Defuse\Crypto\Crypto::encrypt($message, self::$key, true);
		} else {
			return \ze::base16To64(\Defuse\Crypto\Crypto::encrypt($message, self::$key, false));
		}
	}
	
	public static function decrypt($message) {
		if (!self::$key || !is_string($message) || $message === '') {
			return null;
		}
		try {
			if ($message[0] === '%') {
				return \Defuse\Crypto\Crypto::decrypt(substr($message, 1), self::$key, true);
			} else {
				return \Defuse\Crypto\Crypto::decrypt(\ze\ring::base64To16($message), self::$key, false);
			}
		} catch (\Exception $e) {
			return null;
		}
	}
	
	public static function encryptFile($inputFilename, $outputFilename) {
		if (!self::$key) {
			return null;
		}
		\Defuse\Crypto\File::encryptFile($inputFilename, $outputFilename, self::$key);
		return true;
	}
	
	public static function decryptFile($inputFilename, $outputFilename) {
		if (!self::$key) {
			return false;
		}
		\Defuse\Crypto\File::decryptFile($inputFilename, $outputFilename, self::$key);
		return true;
	}
}
