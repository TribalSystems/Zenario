<?php
/*
 * Copyright (c) 2016, Tribal Limited
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

set_time_limit(60 * 10);

//Use file functions for uncompressed files, and gz functions for compressed files
//If the user has not tampered with a backup it should still be compressed though.
if ($plainSql) {
	$open = 'fopen';
	$read = 'fread';
	$close = 'fclose';
} else {
	$open = 'gzopen';
	$read = 'gzread';
	$close = 'gzclose';
}

//Start out handling statements as SQL
//Eventually we will move on to (re)creating the documents folder, as which point
//this variable will be set to false
$runningSQL = true;
$chunks = '';
$statementNo = 0;
$reading = true;
$state = NEXTPLEASE;

//$docpath = setting('docstore_dir');
//if (!is_readable($docpath) || !is_writeable($docpath)) {
	//$docpath = false;
//}

//Open the file
$g = $open($filename, 'rb');
$f;

if (!$g = $open($filename, 'rb')) {
	$failures[] = adminPhrase('Could not open the file [[file]].', array('file' => $filename));
	return false;
}


$needed_packet = 0;
$max_allowed_packet = 999999999;
if (($result = @sqlSelect("SHOW VARIABLES LIKE 'max_allowed_packet'"))
 && ($row = sqlFetchRow($result))
 && !empty($row[1])) {
	$max_allowed_packet = $row[1];
}


//Read from the file, a little at a time.
//Loop until we've read the file
while($reading) {
	//Grab a little bit from the file, and add it to whatever we already had.
	$reading = ($chunk = $read($g, READ_BACKUP_CHUNK_SIZE)) !== '';
	$chunks .= $chunk;
	
	//Split what we have into individual SQL statements
	$statements = explode(";\n", str_replace("\r", '', $chunks));
		//There should be no carriage returns in the backup; unless the user has tampered with the
		//file on a windows machine. But if that's the case, try to strip them out, rather than have their restore fail.
	$count = count($statements)-1;
	
	//If we have any complete statements, execute them
	for ($i = 0; $i < $count; ++$i) {
		
		//Stop running SQL statements when we see a statement called 'END OF SQL'
		if ($runningSQL && $statements[$i] == 'END OF SQL') {
			$runningSQL = false;
		}
		
		//If we are still running SQL statements then handle SQL statements
		//If we have moved on, work differently
		if ($runningSQL) {
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
			
			
			//Check the SQL statement, looking for the table prefix it is using.
			
			//DB_NAME_PREFIX is for tables in the local database; i.e. most tables. They are always restored.
			} elseif (strpos($statements[$i], "[['DB_NAME_PREFIX']]") !== false) {
				$prefixInFile = "[['DB_NAME_PREFIX']]";
				$actualPrefix = DB_NAME_PREFIX;
			
			//DB_NAME_PREFIX_GLOBAL is for tables that would be in the global database in the old multisite system. Ignore these.
			} elseif (strpos($statements[$i], "[['DB_NAME_PREFIX_GLOBAL']]") !== false) {
				continue;
			
			//GLOBAL_NOT_NORMALLY_RESTORED is for tables that would be in the global database in the old multisite system. Ignore these.
			} elseif (strpos($statements[$i], "[['GLOBAL_NOT_NORMALLY_RESTORED']]") !== false) {
				continue;
			
			//If we didn't see any of those prefixes, report it
			} else {
				$failures[] = 'Statement '. $statementNo. ":\n". $statements[$i]. "\n\nNo Prefix or Prefix not in the correct format\n\n\n";
				continue;
			}

			
			//Attempt to run the SQL. Note there are no further checks done on it, so of course it may not be valid.
			//I'll also check whether it failed or succeeded
			$success = @sqlSelect(str_replace($prefixInFile, $actualPrefix. 'i_m_p_', $statements[$i]));
						
			//Even if it failed, we'll keep running the SQL statements. But we'll log the failures and report at the end.
			if (!$success) {
				$failures[] = 'Statement '. $statementNo. ":\n". $statements[$i]. "\n\nDatabase query error ". sqlErrno(). ": ". sqlError()."\n\n\n";
			}
			
		//If we have moved on from SQL statements, move on to the DocStore creation statements
		//} elseif ($docpath && empty($failures) && !$needed_packet) {
			//Watch out for DIR and FILE labels, that tell us if the next statement is the path for a directory or a file
			//if ($state == NEXTPLEASE) {
				//if ($statements[$i] == 'DIR') {
					//$state = CREATEDIR;
				//} elseif ($statements[$i] == 'FILE') {
					//$state = FILENAME;
				//}
				
			//Create the specified directory, if it doesn't already exist
			//} elseif ($state == CREATEDIR) {
				//if (!file_exists(docstoreDirectoryPath($statements[$i]))) {
					//mkdir(docstoreDirectoryPath($statements[$i]));
				//}
				//The next statement should be the start of a new directory or file
				//$state = NEXTPLEASE;
				
			//Open the specified file for writing
			//} elseif ($state == FILENAME) {
				//$f = fopen(docstoreDirectoryPath($statements[$i]), 'wb');
				
				//The next statement should be the contents of the file
				//$state = FILECONTENTS;
				
			//Write the contents of the file, then close it.
			//} elseif ($state == FILECONTENTS) {
				//Write the (remaining) data to the file
				//fwrite($f, hex2bin($statements[$i]));
				
				//Close the file
				//fclose($f);
				
				//We're done; the next statement should be the start of a new directory or file
				//$state = NEXTPLEASE;
			//}
		}
	}

	//Remember any incomplete statements for the next read
	if ($state != FILECONTENTS) {
		$chunks = $statements[$count];
	} else {
		//Have an exception for writing files - we can stream the data straight into the file
		//without needing to hold it back.
		if (strlen($statements[$i]) % 2 == 0) {
			fwrite($f, hex2bin($statements[$i]));
			$chunks = '';
		} else {
			//The hexadecimal values need to be in pairs; if there is an odd number then remove the
			//last digit and save it for next time.
			fwrite($f, hex2bin(substr($statements[$i], 0, -1)));
			$chunks = substr($statements[$i], -1);
		}
	}

}
$close($g);
//Close the last file, if it was left open
if ($state == FILECONTENTS) {
	//Close the file
	fclose($f);
}


if ($needed_packet) {
	$failures[] =
		"This backup contains large files, and the 'max_allowed_packet' setting on your server is too small to restore them.\n".
		"The 'max_allowed_packet' setting is currently set to '". configFileSize($max_allowed_packet). "';\n".
		"please ask your server administrator increase it to at least '". configFileSize($needed_packet * 1.05). "'.\n".
		"Please see http://dev.mysql.com/doc/refman/5.0/en/packet-too-large.html for details.\n\n\n";
}

//Check for any failures
if (!empty($failures)) {
	//If we failed at any point, don't write over the actual tables with the temp values!
	//We'll need to delete the temporary tables though.
	foreach(lookupImportedTables(DB_NAME_PREFIX) as $importTable) {
		@sqlSelect('DROP TABLE IF EXISTS `'. $importTable. '`');
	}
	
	return false;

} else {
	//Finally, we'll need to remove the existing tables and copy over 
	
	//Drop the modules/plugin table from the existing installation
	$error = false;
	require_once CMS_ROOT. 'zenario/includes/welcome.inc.php';
	runSQL(false, 'local-DROP.sql', $error);
	runSQL(false, 'local-admin-DROP.sql', $error);
	
	//Copy over the new tables
	foreach(lookupImportedTables(DB_NAME_PREFIX) as $importTable) {
		
		//Work out the name of the actual table by removing the 'i_m_p_' prefix
		$actualTable = str_replace(DB_NAME_PREFIX. 'i_m_p_', DB_NAME_PREFIX, $importTable);
		
		//Remove the currently existing table
		sqlUpdate('DROP TABLE IF EXISTS `'. $actualTable. '`', false);
		
		//Copy over the actual table
		sqlUpdate('RENAME TABLE `'. $importTable. '` TO `'. $actualTable. '`', false);
	}
	
	restoreLocationalSiteSettings();
	
	return true;
}