<?php

namespace ze;


//Zenario Encryption Wrapper Library
//These are wrapper functions for the https://github.com/defuse/php-encryption library
//N.b. mcrypt.so and openssl.so should be running
class pdeAdm extends pde {
	
	public static function generateKey() {
		$key = \Defuse\Crypto\Key::createNewRandomKey();
		$ascii = $key->saveToAsciiSafeString();
	
		if (\ze\pde::ZEWL_APPLY_BASE64) {
			$ascii = \ze::base16To64($ascii);
		}
	
		return $ascii;
	}
	
	public static function generateClientKey() {
		if ($company = \ze\pde::company()) {
			$lockedKey = \Defuse\Crypto\KeyProtectedByPassword::createRandomPasswordProtectedKey($company);
			$ascii = $lockedKey->saveToAsciiSafeString();
			
			if ($ascii) {
				if (\ze\pde::ZEWL_APPLY_BASE64) {
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
					hash(\Defuse\Crypto\Core::HASH_FUNCTION_NAME, \ze\pde::company(), true),
					true
				)
			);
		
		if ($ascii) {
			if (\ze\pde::ZEWL_APPLY_BASE64) {
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
		if (\ze\pdeAdm::canEncryptColumn($table, $column)
		 && \ze::$dbL->columnIsEncrypted($table, $column) === false
		 && \ze\pde::loadClientKey()
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
					SET ". $encryptedCol. " = '". \ze\escape::sql(\ze\pde::encrypt($row[0], true)). "',
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
		if (\ze\pde::loadClientKey()
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
					SET ". $plainCol. " = '". \ze\escape::sql(\ze\pde::decrypt($row[0])). "',
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
	
	public static function setupErrorMessage($suffix = '') {
		
		$message = \ze\admin::phrase('User data in this database is protected using Personal Data Encryption.'). ' ';
		
		if (!\ze\pde::checkCompanyKeyIsSet()) {
			$message .= \ze\admin::phrase("However, the location of the company encryption key is not specified in the <code>zenario_siteconfig.php</code> file, so you will not be able to view or edit any user's details.");
		
		} else if (!\ze\pde::checkConfIsOkay($suffix)) {
			$message .= \ze\admin::phrase("However, the company encryption key (as specified in the <code>zenario_siteconfig.php</code> file) is missing, so you will not be able to view or edit any user's details.");
		
		} else if (!\ze\pde::checkClientKeyExists()) {
			$message .= \ze\admin::phrase("However, the site encryption key is missing from the <code>zenario_custom/key/</code> directory, so you will not be able to view or edit any user's details.");
		
		} else {
			$message .= \ze\admin::phrase("However, the site encryption key does not correspond with the company encryption key on this server, so you will not be able to view or edit any user's details.");
		}
		
		return $message;
	}
	
	
	//If it looks like a site is supposed to be using encryption, but it's not set up properly,
	//show an error message on an Organizer Panel.
	public static function showNoticeOnPanelIfConfIsBad(&$panel, $suffix = '') {

		if (\ze\pde::checkForSetupError($suffix)) {
			$panel['notice'] = [
				'show' => true,
				'type' => 'error',
				'html' => true,
				'message' => \ze\pdeAdm::setupErrorMessage($suffix)
			];
			
			if (!empty($panel['columns'])) {
				foreach ($panel['columns'] as &$column) {
					if (!empty($column['encrypted'])) {
						$column['searchable'] = false;
					}
				}
			}
		}
	}
	
	
	//If it looks like a site is supposed to be using encryption, but it's not set up properly,
	//show an error message on a FAB.
	public static function showNoticeOnFABIfConfIsBad(&$box, $suffix = '') {
		if (\ze\pde::checkForSetupError($suffix)) {
			if (!empty($box['tabs'])) {
				foreach ($box['tabs'] as &$tab) {
				
					unset($tab['edit_mode']);
				
					if (!isset($tab['notices'])) {
						$tab['notices'] = [];
					}
				
					$tab['notices']['pde_setup_error'] = [
						'ord' => -1,
						'show' => true,
						'type' => 'error',
						'html' => true,
						'message' => \ze\pdeAdm::setupErrorMessage($suffix)
					];
				}
			}
		}
	}
}
