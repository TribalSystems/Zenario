<?php
/*
 * Copyright (c) 2023, Tribal Limited
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

//\ze\dbAdm::createBackupScript($backupPath, $gzip = true, $encrypt = false)


//If encryption is asked for, we'll create a plain-text backup in the temp directory
//that only we can read, and then later use that to create an encrypted file
//in the requested location
if ($encrypt) {
	if (!ze\zewl::loadClientKey()) {
		echo \ze\admin::phrase('Could not load the encryption library.');
		exit;
	}
	$encryptedBackupPath = $backupPath;
	$backupPath = tempnam(sys_get_temp_dir(), 'ptb');
	chmod($backupPath, 0600);
}



//If Assetwolf is running, we'll need to stop the background threads running before we do the backup
if ($awRunning = ze\module::inc('assetwolf_2')) {
	\ze\assetwolf::includeLocks();
	
	//Grab locks and pause the threads as usual
	\ze\assetwolf\locks::getLocksOnAllThreads();
	
	//We'll need to keep the threads paused, however we need to release the locks otherwise the backup
	//will not be able to actually run
	\ze\assetwolf\locks::releaseLocksOnAllThreads($unpause = false);
}



//Attempt to call mysqldump directly for speed
if (\ze\dbAdm::testMySQL(true)
 && strpos(DBHOST, '"') === false
 && strpos(DBUSER, '"') === false
 && strpos(DBPASS, '"') === false) {
	
	set_time_limit(60 * 10);
	
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
	
	$postProcessing = '';
	
	//When we make the backup, attempt to insert the *\prefix:'zenario_':prefix\*/ flag that the CMS uses to check the table names in the backup script.
	//However only try to do this if the DB_PREFIX only contains word-characters
	if (preg_replace('@[a-zA-Z_]@', '', DB_PREFIX) === '') {
		//Remove any "/*!40000 */" comments around the alter-table statements as we can't have comments within comments
		$postProcessing .= ' | sed -E \'s@^/\\*\\!4[0-9]+ (ALTER TABLE.*)\\*/;$@\\1;@\'';
		$postProcessing .= ' | sed -E \'s@^(DROP TABLE|DROP TABLE IF EXISTS|CREATE TABLE|CREATE TABLE IF NOT EXISTS|INSERT INTO|REPLACE INTO|UPDATE|ALTER TABLE) `('. DB_PREFIX. ')@\\1 /\\*\\\\prefix:\'"\'"\'\2\'"\'"\':prefix\\\\\\*/`\2@\'';
	}
	
	//This line would attempt to force UTF8.
	//Unfortunately it also limits the filesize of the backups to about 250k and trims them after that,
	//so we can't use it.
	//$postProcessing .= ' | iconv -f utf8 -t utf8';
	
	if ($gzip) {
		$postProcessing .= ' | gzip -f9';
	}
	
	//Call mysqldump to make the backup file
	\ze\dbAdm::callMySQL(true,
		' --defaults-extra-file='. escapeshellarg($passwordFile). ' --add-drop-table --default-character-set=utf8 --skip-set-charset --skip-add-locks --order-by-primary '.
		escapeshellarg(DBNAME).
		$postProcessing. ' > '.
		escapeshellarg($backupPath));

	unlink($passwordFile);
	
	//Paranoia check! Only assume the backup was successfully completed if the file was created,
	//and doesn't look too small!
	if (file_exists($backupPath)) {
		if (filesize($backupPath) > 5000) {
			
			//Encrypt the backup file if requested
			if ($encrypt) {
				ze\zewl::encryptFile($backupPath, $encryptedBackupPath);
				unlink($backupPath);
			}
			
			//Resume Assetwolf's background threads, if we paused them earlier.
			if ($awRunning) {
				\ze\assetwolf\locks::releaseLocksOnAllThreads();
			}
			return;
		
		} else {
			//If this isn't the case, delete the bad file and continue on to use the fallback option instead.
			unlink($backupPath);
		}
	}
}



//Here's a PHP implementation of mysqldump, as a fallback in case we can't actually call mysqldump
set_time_limit(60 * 10);

//Create a new file in the backup directory, and write the backup into it
if ($gzip) {
	$open = 'gzopen';
	$read = 'gzread';
	$close = 'gzclose';
} else {
	$open = 'fopen';
	$read = 'fread';
	$close = 'fclose';
}
$g = $open($backupPath, 'wb');

//Loop through every table beginning with the DB_PREFIX
foreach(\ze\dbAdm::lookupExistingCMSTables() as $table) {

	//Attempt to get the create table script for the current table
	$sql = 'SHOW CREATE TABLE `'. $table['actual_name'] . '`';

	//This may fail if for some reason we don't have the correct permisions to look at a
	//create statement for the table/view.
	//(For example, this is a view and the current user has no rights to see views)
	//Ignore it if so!
	if (!($result = @\ze\sql::select($sql))
	 || !($createTable = \ze\sql::fetchAssoc($result))
	 || !($createTable['Create Table'])) {
		continue;
	}

	//Old logic: replace the table prefix with a pattern
		//Remove the DB_PREFIX, and add [['DB_PREFIX']] in its place.
		//$importTable = "[['". $table['prefix']. "']]". $table['name'];
		//$createTable = str_replace('`'. $table['actual_name']. '`', '`'. $importTable. '`', $createTable['Create Table']);

	//New logic (T10131, A couple of small improvements to the CMS backup system):
		//We're no longer replacing the table prefix, so we're compatabile with phpMyAdmin, mysql and mysqldump
		//$importTable = $table['actual_name'];
		//$createTable = $createTable['Create Table'];

	//Third attempt:
		//This tries to come up with something that will still work with mysql/phpMyAdmin, but will also allow for
		//restores to databases with different table prefixes
		$importTable = '/*\\prefix:\''. DB_PREFIX. '\':prefix\\*/`'. $table['actual_name']. '`';
		$createTable = str_replace('`'. $table['actual_name']. '`', $importTable, $createTable['Create Table']);

	//Get details on each column in the current table
	$sql = 'SHOW COLUMNS FROM `'. $table['actual_name']. '`';
	$result = \ze\sql::select($sql);
	$columns = [];

	//Loop through each of them, getting their name and type
	while($column = \ze\sql::fetchAssoc($result)) {
		$columns[$column['Field']] = $column;
	}

	//Generate a list of the columns
	$pkCols = [];
	$pkColList = '';
	$columnList = '';
	$orderBy = '';
	foreach($columns as $column => &$details) {
		$columnList .= ($columnList? ', ' :null). '`'. \ze\escape::sql($column). '`';
	
		if ($details['Key'] == 'PRI') {
			$pkCols[] = $column;
			$pkColList .= ($pkColList? ', ' :null). '`'. \ze\escape::sql($column). '`';
		
			if ($orderBy) {
				$orderBy .= ", ". count($pkCols);
			} else {
				$orderBy = "ORDER BY 1";
			}
		}
	}

	//Prepare the start of an insert statement for that table
	$insertInto = 'INSERT INTO '. $importTable. ' ('. $columnList. ') VALUES';

	//Start generating the backup script for this table. Add a drop table if exists statement
	$inserts = 'DROP TABLE IF EXISTS '. $importTable. ';'.
	
		"\n\n".
	
		//Add the create table statement
		$createTable. ';'.
	
		"\n\n".
	
		//Disable indexs for faster inserting
		'ALTER TABLE '. $importTable. ' DISABLE KEYS;'.
	
		"\n";

	gzwrite($g, $inserts);

	//Attempt to get a list of the existing primary keys in each table.
	\ze::$dbL->checkTableDef($table['actual_name']);
	if ($pkCol = \ze\ray::value(\ze::$dbL->pks, $table['actual_name'])) {
		$pkColIsInt = \ze::$dbL->cols[$table['actual_name']][$pkCol]->isInt;
	
		$sql = "
			SELECT `". \ze\escape::sql($pkCol). "`
			FROM `". $table['actual_name']. "`
			ORDER BY 1";
		$ids = \ze\sql::fetchValues($sql);

	} elseif ($pkColList) {
		$sql = "
			SELECT ". $pkColList. "
			FROM `". $table['actual_name']. "`
			". $orderBy;
		$ids = \ze\sql::fetchAssocs($sql);

	} else {
		$ids = [false];
	}

	//Start building insert statements
	$inserts = '';
	foreach ($ids as &$id) {
	
		//Run a SQL statement to get information out of the table
		//If we successfully got a list of ids above, to avoid large memory useage in MySQL it should be just one row.
		//Otherwise we'll have to load the entire table.
		$sql = "
			SELECT ". $columnList. "
			FROM `". $table['actual_name']. "`";
	
		$first = true;
		if (is_array($id)) {
			foreach($id as $col => &$val) {
				\ze\row::writeCol($sql, $table['actual_name'], '', $col, $val, $first, true);
			}
	
		} elseif ($id !== false) {
			\ze\row::writeCol($sql, $table['actual_name'], '', $pkCol, $id, $first, true);
		}
		$result = \ze\sql::select($sql);

		//Loop through each row
		while($row = \ze\sql::fetchAssoc($result)) {
	
			//Group the insert statement for this row together with the previous statements if possible.
			//Otherwise start a new insert statement
			if ($inserts == '') {
				$inserts = "\n". $insertInto. "\n(";
			} else {
				$inserts .= ",\n(";
			}
	
			//List the values for this row
			$comma = false;
			foreach($columns as $column => &$details) {
		
				$value = $row[$column];
				$isBinary = strrpos($details['Type'], 'blob') !== false;
	
				$inserts .= $comma? ',' :null;
		
				//Attempt to fix some MySQL strict problems
				if (($value === NULL && $details['Null'] == 'NO')
				 || ($value === '' && substr($details['Type'], 0, 4) == 'enum' && strpos($details['Type'], "''") === false)) {
					$value = $details['Default'];
				}
		
				//Write the value carfully.
				//If it is null, just write NULL
				if ($value === NULL) {
					$inserts .= 'NULL';
		
				//Otherwise if it is an empty string, write an empty string.
				} elseif ($value === '') {
					$inserts .= "''";
		
				//If the two if statements above didn't trigger, we know our value is not empty
				//If this is a binary column, convert it to hexadecimal to write it down
				//} elseif ($isBinary) {
					//$inserts .= '0x' . bin2hex($value);
		
				//If this is a number, we can write it as it is without quotes or escaping
				} elseif (is_numeric($value) && $value != '' && false === strpbrk($value, 'exEX')) {
					$inserts .= $value;
		
				//Catch JSON-type columns
				} elseif (is_array($value)) {
					$inserts .= \ze\escape::json($value);
		
				//Otherwise, write the value quoted and escaped
				} else {
					$inserts .= "'". \ze\escape::sql($value). "'";
				}
		
				$comma = true;
			}
	
			//If the length of the insert statement looks like it's getting even remotely close to  our maximum read size,
			//don't add any more and write the insert statement
			//Otherwise allow the next statement to be added onto this one
			if (strlen($inserts) < ze\dbAdm::CHUNK_SIZE) {
				$inserts .= ')';
			} else {
				$inserts .= ');';
				gzwrite($g, $inserts);
				$inserts = '';
			}
		}
	}

	//After finishing with the tables, write any insert statements not yet written
	if ($inserts != '') {
		$inserts .= ';';
		gzwrite($g, $inserts);
		$inserts = '';
	}


	$inserts = "\n\n".
	
		//Enable keys again
		'ALTER TABLE '. $importTable. ' ENABLE KEYS;'.
	
		"\n\n\n";

	gzwrite($g, $inserts);
}

$close($g);




//Resume Assetwolf's background threads, if we paused them earlier.
if ($awRunning) {
	\ze\assetwolf\locks::releaseLocksOnAllThreads();
}



//Encrypt the backup file if requested
if ($encrypt) {
	ze\zewl::encryptFile($backupPath, $encryptedBackupPath);
	unlink($backupPath);
}