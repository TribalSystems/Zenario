<?php
/*
 * Copyright (c) 2024, Tribal Limited
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of Zenario, Tribal Limited nor the
 *       names of its contributors may be used to endorse or promote products
 *       derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL TRIBAL LTD BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


//\ze\dbAdm::restoreFromBackup($backupPath, &$failures, $restoringOverExistingSite)

if ($restoringOverExistingSite && !\ze\dbAdm::restoreEnabled()) {
	$failures[] = \ze\dbAdm::restoreEnabledMsg();
	return false;
}


//Attempt to check whether gzip compression has been used, based on the filename
$gzipped =
	strtolower(substr($backupPath, -3)) == '.gz'
 || strtolower(substr($backupPath, -13)) == '.gz.encrypted';
$encrypted =
	strtolower(substr($backupPath, -10)) == '.encrypted'
 || strtolower(substr($backupPath, -13)) == '.encrypted.gz';


//If the back was encrypted, decrypt it first!
if ($encrypted) {
	if (!\ze\pde::loadClientKey()) {
		echo \ze\admin::phrase('Could not load the encryption library.');
		exit;
	}
	$encryptedBackupPath = $backupPath;
	$backupPath = tempnam(sys_get_temp_dir(), 'ptb');
	chmod($backupPath, 0600);
	
	try {
		\ze\pde::decryptFile($encryptedBackupPath, $backupPath);
	} catch (\Exception $e) {
		echo \ze\admin::phrase('Could not decrypt this backup. It may be corrupted, or may have been created using a different encryption key.');
		exit;
	}
}

//Get a list of existing tables from modules in advance.
//(Note: I'm doing this now in advance, rather than during the critical point when we are restoring the tables!)
$moduleTables = \ze\sql::fetchValues("SHOW TABLES LIKE '". DB_PREFIX. "mod%'");




set_time_limit(60 * 10);

//Use file functions for uncompressed files, and gz functions for compressed files
//If the user has not tampered with a backup it should still be compressed though.
if ($gzipped) {
	$open = 'gzopen';
	$read = 'gzread';
	$close = 'gzclose';
} else {
	$open = 'fopen';
	$read = 'fread';
	$close = 'fclose';
}

//Start out handling statements as SQL
//Eventually we will move on to (re)creating the documents folder, as which point
//this variable will be set to false
$attemptToUseMySQL = true;
$chunks = '';
$statementNo = 0;
$reading = true;

//Try to open the file
if (!$g = $open($backupPath, 'rb')) {
	
	//If we were restoring up from an encrypted file, delete the plain-text copy
	if ($encrypted) {
		unlink($backupPath);
		$failures[] = \ze\admin::phrase('Could not open the file [[file]].', ['file' => $encryptedBackupPath]);
	} else {
		$failures[] = \ze\admin::phrase('Could not open the file [[file]].', ['file' => $backupPath]);
	}
	
	return false;
}



//If Assetwolf is running, we'll need to stop the background threads running before we do the backup
if ($awRunning = $restoringOverExistingSite && ze\module::inc('assetwolf_2')) {
	\ze\assetwolf::includeLocks();
	
	//Grab locks and pause the threads as usual
	\ze\assetwolf\locks::getLocksOnAllThreads();
	
	//We'll need to keep the threads paused, however we need to release the locks otherwise the backup
	//will not be able to actually run
	\ze\assetwolf\locks::releaseLocksOnAllThreads($unpause = false);
}



$needed_packet = 0;
$max_allowed_packet = 999999999;
if (($result = @\ze\sql::select("SHOW VARIABLES LIKE 'max_allowed_packet'"))
 && ($row = \ze\sql::fetchRow($result))
 && !empty($row[1])) {
	$max_allowed_packet = $row[1];
}


//Read from the file, a little at a time.
//Loop until we've read the file
while($reading) {
	//Grab a little bit from the file, and add it to whatever we already had.
	$reading = ($chunk = $read($g, ze\dbAdm::READ_CHUNK_SIZE)) !== '';
	$chunks .= $chunk;
	
	//Split what we have into individual SQL statements
	$statements = explode(";\n", str_replace("\r", '', $chunks));
		//There should be no carriage returns in the backup; unless the user has tampered with the
		//file on a windows machine. But if that's the case, try to strip them out, rather than have their restore fail.
	$count = count($statements)-1;
	
	//If we have any complete statements, execute them
	for ($i = 0; $i < $count; ++$i) {
		
		//Catch the case where phpMyAdmin shoves a "COMMIT" statement onto the end without a linebreak
		if (substr($statements[$i], -7) == ';COMMIT') {
			$statements[$i] = substr($statements[$i], 0, -7);
		}
	
		//If the length is smaller than 20 chars, this is probably just some whitespace and can be ignored
		if (($strlen = strlen($statements[$i])) < 20) {
			continue;
		}
		
		//Keep count of the statements for debugging purposes
		++$statementNo;
		
		
		//Check to see if this 
		if ($strlen > $max_allowed_packet) {
			if ($needed_packet < $strlen) {
				$needed_packet = $strlen;
			}
			continue;
		
		
		
		//Very old backups sometimes had global tables in them with special prefixes; ignore these
		} elseif (strpos($statements[$i], "[['DB_PREFIX_GLOBAL']]") !== false) {
			continue;
		
		} elseif (strpos($statements[$i], "[['GLOBAL_NOT_NORMALLY_RESTORED']]") !== false) {
			continue;
		
		//Handle backups created by the CMS in the previous format before "T10131, A couple of small improvements to the CMS backup system"
		//There will be no COMMIT/CREATE DATABASE/LOCK/SET/START/UNLOCK/USE statements, and the will be a merge field for the database prefix
		} elseif (strpos($statements[$i], "[['DB_PREFIX']]") !== false) {
			$searchInFile = "[['DB_PREFIX']]";
			$replaceInFile = ($replacePrefix = DB_PREFIX. 'i_m_p_');
			$searchPrefix = false;
			$attemptToUseMySQL = false;
		
		//If we didn't see any of those prefixes, attempt to look for the table names as the first name in the statement
		} else {
			//Remove some of MySQL's or phpMyAdmin's comments from the start, which can cause us problems in the logic below
			$statements[$i] = preg_replace('@^[ \t]*--[^"\']*?$@m', '', $statements[$i]);
			
			//Try to work out what type of statement this is
			$matches = [];
			if (!preg_match('@^[^`\'\"]*\b(ALTER|COMMIT|CREATE DATABASE|CREATE TABLE|DROP|INSERT|LOCK|REPLACE|SELECT|SET|SHOW|START|TRUNCATE|UNLOCK|UPDATE|USE)\b@m', $statements[$i], $matches)) {
				//Throw out anything that doesn't conform to the norm!
				$failures[] = 'Statement '. $statementNo. ":\n". $statements[$i]. "\n\nThis does not look like a backup file made by Zenario, mysqldump or phpMyAdmin. Only those formats are supported.\n\n\n";
				break 2;
			
			} elseif ($matches[1] == 'SET') {
				//Ignore any SET commands we see, but don't raise an error about them either
				continue;
			
			//Check for things such as COMMIT/CREATE DATABASE/LOCK/SET/START/UNLOCK/USE statements that phpMyAdmin likes to add
			} else
			if ($matches[1] == 'COMMIT'
			 || $matches[1] == 'CREATE DATABASE'
			 || $matches[1] == 'LOCK'
			 || $matches[1] == 'START'
			 || $matches[1] == 'UNLOCK'
			 || $matches[1] == 'USE') {
				//Don't attempt to pipe this SQL file directly to MySQL if we see something like a CREATE DATABASE or a USE at the top
				$attemptToUseMySQL = false;
				
				//Otherwise if we're using PHP to restore the backup, just ignore these statements but keep trying to restore the tables in the backup
				continue;
			
			//Look for the first two `s in the statement, and get the text inbetween
			} else
			if ((false !== ($tick1 = strpos($statements[$i], '`')))
			 && (false !== ($tick2 = strpos($statements[$i], '`', $tick1 + 1)))
			 && ($tick1 < 100)
			 && ($tick2 < 350)
			 && ($tableName = substr($statements[$i], $tick1 + 1, $tick2 - $tick1 - 1))) {
			
				//If this is a recent backup created by the CMS (and not mysqldump),
				//then each table name should have a note in front if it saying what the prefix was
				if ((false !== ($tick1 = strpos($statements[$i], '/*\\prefix:\'')))
				 && (false !== ($tick2 = strpos($statements[$i], '\':prefix\\*/', $tick1 + 11)))
				 && ($tick2 < 100)) {
					$dbNamePrefixInFile = substr($statements[$i], $tick1 + 11, $tick2 - $tick1 - 11);
					
					if ($tableName = \ze\ring::chopPrefix($dbNamePrefixInFile, $tableName)) {
						$searchInFile = '`'. ($searchPrefix = $dbNamePrefixInFile). $tableName. '`';
						$replaceInFile = '`'. ($replacePrefix = DB_PREFIX. 'i_m_p_'). $tableName. '`';
					
					} else {
						$failures[] = 'Statement '. $statementNo. ":\n". $statements[$i]. "\n\nCould not read the table-prefix in this backup file\n\n\n";
						break 2;
					}
				
				//Check that the table's name begins with the DB_PREFIX,
				//and then get just the table name without the prefix
				} elseif ($tableName = \ze\ring::chopPrefix(DB_PREFIX, $tableName)) {
					$searchInFile = '`'. ($searchPrefix = DB_PREFIX). $tableName. '`';
					$replaceInFile = '`'. ($replacePrefix = DB_PREFIX. 'i_m_p_'). $tableName. '`';
				
				} else {
					$failures[] = 'Statement '. $statementNo. ":\n". $statements[$i]. "\n\nThe table-prefix in this backup file does not match the table-prefix for this site\n\n\n";
					break 2;
				}
				
			} else {
				$failures[] = 'Statement '. $statementNo. ":\n". $statements[$i]. "\n\nTable name was not found, or backquotes were not used. Please make sure the 'Enclose table and column names with backquotes' option is checked when creating a backup.\n\n\n";
				break 2;
			}
		}
		
		
		
		//If we got this far, the backup is likely to be good.
		//Attempt to use MySQL to restore the backup from this point, rather than using PHP.
		//Note that because we need to do a preg-replace the database prefix, only try to do this if both prefixes contain only word characters
		if ($attemptToUseMySQL
		 && $searchPrefix !== false
		 && preg_replace('@[a-zA-Z_]@', '', $searchPrefix) === ''
		 && preg_replace('@[a-zA-Z_]@', '', $replacePrefix) === ''
		 && \ze\dbAdm::testMySQL(false)
		 && strpos(DBHOST, '"') === false
		 && strpos(DBUSER, '"') === false
		 && strpos(DBPASS, '"') === false) {
			
			//Create a password file to avoid writing the password in the CLI (which would be bad because this is visible to process lists)
			$connectionDetails = '[client]
host="'. DBHOST. '"
user="'. DBUSER. '"
password="'. DBPASS. '"';

			if (defined('DBPORT') && (int) DBPORT) {
				$connectionDetails .= "\nport=". (int) DBPORT;
			}

			$passwordFile = tempnam(sys_get_temp_dir(), 'pwf');
			chmod($passwordFile, 0600);
			file_put_contents($passwordFile, $connectionDetails);
			unset($connectionDetails);
			
			
			$input = 'cat '. escapeshellarg($backupPath);
			
			//Decompress if this is a gzipped backup
			if ($gzipped) {
				$input .= ' | gzip -d';
			}
			
			//Change the database prefix from the backup to the temporary prefix using sed
			$input .= ' | sed -E \'s@^(DROP TABLE|DROP TABLE IF EXISTS|CREATE TABLE|CREATE TABLE IF NOT EXISTS|INSERT INTO|REPLACE INTO|UPDATE|ALTER TABLE|/\\*\\![0-9]+ ALTER TABLE)( | /\\*\\\\prefix:\'"\'"\'[a-zA-Z_]+\'"\'"\':prefix\\\\\\*/)`'. $searchPrefix. '@\\1 `'. $replacePrefix. '@\'';
			
			//Call mysql restore the tables
			$execResult = \ze\dbAdm::callMySQL(false,
				' --default-character-set=utf8 --defaults-extra-file='. escapeshellarg($passwordFile). ' -D '. escapeshellarg(DBNAME),
				$input. ' | ');
			
			if ($execResult !== false) {
				//If all looks okay, skip down to after this loop where we tidy up the code
				break 2;
			}
		}
		
		//If we couldn't call MySQL above, or calling MySQL didn't work, continue trying to restore the backup using PHP.
		$attemptToUseMySQL = false;
		
		
		
		
		
		//Attempt to run the SQL. Note there are no further checks done on it, so of course it may not be valid.
		//I'll also check whether it failed or succeeded
		$success = @\ze::$dbL->con->query(str_replace($searchInFile, $replaceInFile, $statements[$i]));
					
		//Even if it failed, we'll keep running the SQL statements. But we'll log the failures and report at the end.
		if (!$success) {
			$failures[] = 'Statement '. $statementNo. ":\n". $statements[$i]. "\n\nDatabase query error ". \ze\sql::errno(). ": ". \ze\sql::error()."\n\n\n";
			break 2;
		}
	}

	//Remember any incomplete statements for the next read
	$chunks = $statements[$count];
}
$close($g);

//If we were restoring up from an encrypted file, delete the plain-text copy
if ($encrypted) {
	unlink($backupPath);
}




if ($needed_packet) {
	$failures[] =
		"This backup contains large files, and the 'max_allowed_packet' setting on your server is too small to restore them.\n".
		"The 'max_allowed_packet' setting is currently set to '". \ze\dbAdm::configFileSize($max_allowed_packet). "';\n".
		"please ask your server administrator increase it to at least '". \ze\dbAdm::configFileSize($needed_packet * 1.05). "'.\n".
		"Please see http://dev.mysql.com/doc/refman/5.0/en/packet-too-large.html for details.\n\n\n";
}

if (empty($failures)) {
	if (!isset($replacePrefix)
	 || \ze\sql::numRows("SHOW TABLES LIKE '". \ze\escape::like($replacePrefix). "%'") < 70
	 || !\ze\sql::numRows("SHOW TABLES LIKE '". \ze\escape::like($replacePrefix). "action_admin_link'")
	 || !\ze\sql::numRows("SHOW TABLES LIKE '". \ze\escape::like($replacePrefix). "admins'")
	 || !\ze\sql::numRows("SHOW TABLES LIKE '". \ze\escape::like($replacePrefix). "local_revision_numbers'")
	 || !\ze\sql::numRows("SHOW TABLES LIKE '". \ze\escape::like($replacePrefix). "users'")
	 || !\ze\sql::numRows("SHOW TABLES LIKE '". \ze\escape::like($replacePrefix). "visitor_phrases'")) {
		$failures[] = \ze\admin::phrase('This backup file is missing tables, or was not a backup of a Zenario site. It cannot be restored.');
	
	} elseif (!\ze\sql::numRows("SELECT 1 FROM `". \ze\escape::sql($replacePrefix). "local_revision_numbers` LIMIT 1")) {
		$failures[] = \ze\admin::phrase('This backup file contained table definitions but no data. It cannot be restored.');
	
	} else {
		//As of version 9.5 of Zenario, we'll only support updating from version 8.8 onwards.
		//Check for versions of Tribiq CMS/Zenario before 8.8
		$sql = "
			SELECT 1
			FROM `". \ze\escape::sql($replacePrefix). "local_revision_numbers`
			WHERE path IN ('admin/db_updates/step_1_update_the_updater_itself', 'admin/db_updates/step_2_update_the_database_schema', 'admin/db_updates/step_4_migrate_the_data')
			  AND patchfile IN ('updater_tables.inc.php', 'admin_tables.inc.php', 'content_tables.inc.php', 'user_tables.inc.php')
			  AND revision_no < ". (int) EARLIEST_SUPPORTED_MIGRATION. "
			LIMIT 1";

		if (\ze\sql::numRows($sql)) {
			//If this looks like a very old version of Zenario, direct people to update to at least 8.8 first
			$failures[] = \ze\admin::phrase('This backup file contains an installation of Zenario running from before version 8.8. Only backups created from version 8.8 or later can be restored here.');
		}
	}
}


//Check for any failures
if (!empty($failures)) {
	//If we failed at any point, don't write over the actual tables with the temp values!
	//We'll need to delete the temporary tables though.
	foreach(\ze\dbAdm::lookupImportedTables(DB_PREFIX) as $importTable) {
		@\ze\sql::cacheFriendlyUpdate('DROP TABLE IF EXISTS `'. $importTable. '`');
	}
	
	//Resume Assetwolf's background threads, if we paused them earlier.
	if ($awRunning) {
		\ze\assetwolf\locks::releaseLocksOnAllThreads();
	}
	
	return false;

} else {
	//Finally, we'll need to remove the existing tables and copy over 
	
	\ze\dbAdm::rememberLocationalSiteSettings();
	
	//Drop the tables from the existing installation
	$error = false;
	\ze\welcome::runSQL(false, 'local-DROP.sql', $error);
	\ze\welcome::runSQL(false, 'local-admin-DROP.sql', $error);
	\ze\welcome::runSQL(false, 'local-old-DROP.sql', $error);
	
	foreach ($moduleTables as $moduleTable) {
		\ze\sql::update("DROP TABLE IF EXISTS `". \ze\escape::sql($moduleTable). "`", false, false);
	}
	
	//Copy over the new tables
	foreach(\ze\dbAdm::lookupImportedTables(DB_PREFIX) as $importTable) {
		
		//Work out the name of the actual table by removing the 'i_m_p_' prefix
		$actualTable = str_replace(DB_PREFIX. 'i_m_p_', DB_PREFIX, $importTable);
		
		//Remove the currently existing table
		\ze\sql::update('DROP TABLE IF EXISTS `'. $actualTable. '`', false, false);
		
		//Copy over the actual table
		\ze\sql::update('RENAME TABLE `'. $importTable. '` TO `'. $actualTable. '`', false, false);
	}
	
	\ze\dbAdm::restoreLocationalSiteSettings();
	
	//Note that Assetwolf's background threads will likely still be paused, even though we've
	//just replaced everything in the database, as they get paused just before a backup is created.
	//I could call the releaseLocksOnAllThreads() function to unpause them here, however
	//I'm not going to do that, an intentionally make an admin or superuser manually restart them.
	
	return true;
}