<?php

namespace ze;


//Zenario Encryption Wrapper Library
//These are wrapper functions for the https://github.com/defuse/php-encryption library
//N.b. mcrypt.so and openssl.so should be running
class zewlAdm extends zewl {
	
	public static function generateKey() {
		$key = \Defuse\Crypto\Key::createNewRandomKey();
		$ascii = $key->saveToAsciiSafeString();
	
		if (\ze\zewl::ZEWL_APPLY_BASE64) {
			$ascii = \ze::base16To64($ascii);
		}
	
		return $ascii;
	}
	
	public static function generateClientKey() {
		if ($company = \ze\zewl::company()) {
			$lockedKey = \Defuse\Crypto\KeyProtectedByPassword::createRandomPasswordProtectedKey($company);
			$ascii = $lockedKey->saveToAsciiSafeString();
			
			if ($ascii) {
				if (\ze\zewl::ZEWL_APPLY_BASE64) {
					$ascii = \ze::base16To64($ascii);
				}
				return '<'. '?php //Do not edit this by hand!
return \''. $ascii . '\';';
			}
		}
	}
	
	public static function convertKeyToNewCompany() {
		
		//Unfortunately there's no method built into the php-encryption library for this,
		//so we have to copy and slightly adjust the following lines from
		//defuse/php-encryption/src/KeyProtectedByPassword.php:
		$ascii =
			\Defuse\Crypto\Encoding::saveBytesToChecksummedAsciiSafeString(
				\Defuse\Crypto\KeyProtectedByPassword::PASSWORD_KEY_CURRENT_VERSION,
				\Defuse\Crypto\Crypto::encryptWithPassword(
					self::$key->saveToAsciiSafeString(),
					hash(\Defuse\Crypto\Core::HASH_FUNCTION_NAME, \ze\zewl::company(), true),
					true
				)
			);
		
		if ($ascii) {
			if (\ze\zewl::ZEWL_APPLY_BASE64) {
				$ascii = \ze::base16To64($ascii);
			}
			return '<'. '?php //Do not edit this by hand!
return \''. $ascii . '\';';
		}
	}
	
	//Only allow encryption on columns that:
		//are not in keys
		//have no default value
		//have no auto-increment
		//do not auto-update
		//are in tables with a single-column primary key
	public static function canEncryptColumn($table, $column) {
		\ze::$dbL->checkTableDef(DB_PREFIX. $table);
		
		return isset(\ze::$dbL->cols[DB_PREFIX. $table][$column])
			&& !empty(\ze::$dbL->pks[DB_PREFIX. $table])
			&& (bool) \ze\sql::fetchRow("
				SHOW COLUMNS
				FROM ". DB_PREFIX. $table. "
				WHERE `Field` = '". \ze\escape::sql($column). "'
				  AND `Type` != 'tinyint(1)'
				  AND `Key` != 'PRI'
				  AND (`Default` = '' OR `Default` = '0' OR `Default` IS NULL)
				  AND `Extra` = ''");
	}
	
	public static function encryptColumn($table, $column, $hash = false) {
		
		//Check if we can encrypt this column, and that it's not already encrypted
		if (\ze\zewlAdm::canEncryptColumn($table, $column)
		 && \ze::$dbL->columnIsEncrypted($table, $column) === false
		 && \ze\zewl::loadClientKey()
		 && ($col = \ze\sql::fetchRow("
				SHOW COLUMNS
				FROM ". DB_PREFIX. $table. "
				WHERE `Field` = '". \ze\escape::sql($column). "'"
		))) {
			
			//Work out some settings for the encrypted column
			$nullable = $col[2] == 'YES';
			$pkCol = \ze::$dbL->pks[DB_PREFIX. $table];
			$colDef = \ze::$dbL->cols[DB_PREFIX. $table][$column];
			
			if ($colDef->isInt
			 || $colDef->isFloat
			 || $colDef->isTime) {
				$coltype = 'varbinary(255)';
			} else {
				$coltype = 'mediumblob';
			}
			
			$tableName = "`". DB_PREFIX. $table. "`";
			$plainCol = "`". \ze\escape::sql($column). "`";
			$hashedCol = "`". \ze\escape::sql('#'. $column). "`";
			$encryptedCol = "`". \ze\escape::sql('%'. $column). "`";
			
			$sql = "
				ALTER TABLE ". $tableName. "
				ADD COLUMN ". $encryptedCol. " ". $coltype. "
				AFTER ". $plainCol;
			\ze\sql::update($sql, false, false);
			
			if ($hash) {
				$sql = "
					ALTER TABLE ". $tableName. "
					ADD COLUMN ". $hashedCol. " binary(32) NULL default NULL
					AFTER ". $plainCol;
				\ze\sql::update($sql, false, false);
				
				$sql = "
					ALTER TABLE ". $tableName. "
					ADD KEY (". $hashedCol. ")";
				\ze\sql::update($sql, false, false);
			}
			
			$sql = "
				SELECT ". $plainCol. ", `". \ze\escape::sql($pkCol). "`
				FROM ". $tableName;
			
			if ($nullable) {
				$sql .= "
				WHERE ". $plainCol. " IS NOT NULL";
			}
			
			$result = \ze\sql::select($sql);
			while ($row = \ze\sql::fetchRow($result)) {
				$sql = "
					UPDATE ". $tableName. "
					SET ". $encryptedCol. " = '". \ze\escape::sql(\ze\zewl::encrypt($row[0], true)). "',
						". $plainCol. " = ";
				
				if ($nullable) {
					$sql .= "NULL";
				
				} elseif ($colDef->isInt || $colDef->isFloat) {
					$sql .= "0";
				
				} else {
					$sql .= "''";
				}
				
				if ($hash) {
					$sql .= ",
						". $hashedCol. " = '". \ze\escape::sql(\ze\db::hashDBColumn($row[0])). "'";
				}
			
				$sql .= "
					WHERE `". \ze\escape::sql($pkCol). "` = ". \ze\escape::stringToIntOrFloat($row[1], true);
				\ze\sql::update($sql, false, false);
			}
			
			return true;
		}
		return false;
	}
	
	public static function decryptColumn($table, $column) {
		
		//Check if we can encrypt this column, and that it's not already encrypted
		if (\ze\zewl::loadClientKey()
		 && \ze::$dbL->columnIsEncrypted($table, $column) === true
		 && ($col = \ze\sql::fetchRow("
				SHOW COLUMNS
				FROM ". DB_PREFIX. $table. "
				WHERE `Field` = '". \ze\escape::sql($column). "'"
		))) {
			
			//Work out some settings for the encrypted column
			$nullable = $col[2] == 'YES';
			$pkCol = \ze::$dbL->pks[DB_PREFIX. $table];
			
			$tableName = "`". DB_PREFIX. $table. "`";
			$plainCol = "`". \ze\escape::sql($column). "`";
			$hashedCol = "`". \ze\escape::sql('#'. $column). "`";
			$encryptedCol = "`". \ze\escape::sql('%'. $column). "`";
			
			$sql = "
				SELECT ". $encryptedCol. ", `". \ze\escape::sql($pkCol). "`
				FROM ". $tableName. "
				WHERE ". $encryptedCol. " IS NOT NULL";
			
			$result = \ze\sql::select($sql);
			while ($row = \ze\sql::fetchRow($result)) {
				$sql = "
					UPDATE ". $tableName. "
					SET ". $plainCol. " = '". \ze\escape::sql(\ze\zewl::decrypt($row[0])). "',
						". $encryptedCol. " = NULL
					WHERE `". \ze\escape::sql($pkCol). "` = ". \ze\escape::stringToIntOrFloat($row[1], true);
				\ze\sql::update($sql, false, false);
			}
			
			$sql = "
				ALTER TABLE ". $tableName. "
				DROP COLUMN ". $encryptedCol;
			\ze\sql::update($sql, false, false);
			
			if (\ze::$dbL->columnIsHashed($table, $column)) {
				$sql = "
					ALTER TABLE ". $tableName. "
					DROP COLUMN ". $hashedCol;
				\ze\sql::update($sql, false, false);
			}
			
			return true;
		}
		return false;
	}
}
