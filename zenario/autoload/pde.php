<?php

namespace ze;


//Zenario Encryption Wrapper Library
//These are wrapper functions for the https://github.com/defuse/php-encryption library
//N.b. mcrypt.so and openssl.so should be running
class pde {
	const ZEWL_APPLY_BASE64 = true;
	protected static $key;
	
	private static $loaded = null;
	
	public static function init() {
		if (self::$loaded === null) {
			if (\ze\pde::loadClientKey()) {
				return self::$loaded = true;
			}
			
			self::$loaded = false;
		}
		return self::$loaded;
	}
	
	
	public static function loadKey($ascii) {
		if (\ze\pde::ZEWL_APPLY_BASE64) {
			$ascii = \ze\ring::base64To16($ascii);
		}
		self::$key = \Defuse\Crypto\Key::loadFromAsciiSafeString($ascii);
	}
	
	public static function loadClientKey() {
		if (!file_exists($dir = CMS_ROOT. 'zenario_custom/key/client.php')
		 || !($ascii = require $dir)) {
			return false;
		}
		
		if (\ze\pde::ZEWL_APPLY_BASE64) {
			$ascii = \ze\ring::base64To16($ascii);
		}
		
		try {
			$lockedKey = \Defuse\Crypto\KeyProtectedByPassword::loadFromAsciiSafeString($ascii);
		
			try {
				if ($company = \ze\pde::company()) {
					self::$key = $lockedKey->unlockKey($company);
				} else {
	                throw new \Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException('No company specified.');
				}
			} catch (\Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException $e) {
				if ($company = \ze\pde::company('.old')) {
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
		 && file_exists($file = \COMPANY_CONF_PATH. 'company'. $suffix)
		 && ($contents = file_get_contents($file))) {
			return trim($contents);
		}
	}
	
	public static function checkCompanyKeyIsSet() {
		return defined('COMPANY_CONF_PATH');
	}
	
	public static function checkClientKeyExists() {
		return file_exists(CMS_ROOT. 'zenario_custom/key/client.php');
	}
	
	public static function checkConfIsOkay($suffix = '') {
		return (bool) \ze\pde::company($suffix);
	}
	
	public static function checkForSetupError($suffix = '') {
		if (\ze::$dbL->tableHasEncryptedColumns('users')
		 || \ze::$dbL->tableHasEncryptedColumns('users_custom_data')) {
			if (!\ze\pde::init($suffix)) {
				return true;
			}
		}
		return false;
	}
	
	public static function encrypt($message, $rawBinary) {
		if (!self::$key) {
			return null;
		}
		if ($rawBinary) {
			return '%'. \Defuse\Crypto\Crypto::encrypt((string) $message, self::$key, true);
		} else {
			return \ze::base16To64(\Defuse\Crypto\Crypto::encrypt((string) $message, self::$key, false));
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
