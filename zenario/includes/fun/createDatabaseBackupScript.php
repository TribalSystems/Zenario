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

//Loop through every table beginning with the DB_NAME_PREFIX
foreach(lookupExistingCMSTables() as $table) {
	
	//Remove the DB_NAME_PREFIX, and add [['DB_NAME_PREFIX']] in its place.
		//This pattern should never occur in a normal mysql statement, so it will be safe to
		//do a search and replace on this later!
	$importTable = "[['". $table['prefix']. "']]". $table['name'];

	
	//Attempt to get the create table script for the current table
	$sql = 'SHOW CREATE TABLE `'. $table['actual_name'] . '`';
	
	//This may fail if for some reason we don't have the correct permisions to look at a
	//create statement for the table/view.
	//(For example, this is a view and the current user has no rights to see views)
	//Ignore it if so!
	if (!($result = @sqlSelect($sql))) {
		continue;
	}
	
	$createTable = sqlFetchAssoc($result);
	
	//Check if this is a view and not a table - ignore it if so!
	if ($table['view'] || !$createTable['Create Table']) {
		continue;
	}
	
	//Ignore tables that are not from the CMS, or any tables from older versions of the CMS that were left after an upgrade
	if (!$table['in_use']) {
		continue;
	}
	
	$createTable = str_replace('`'. $table['actual_name']. '`', '`'. $importTable. '`', $createTable['Create Table']);
	
	//Get details on each column in the current table
	$sql = 'SHOW COLUMNS FROM `'. $table['actual_name']. '`';
	$result = sqlQuery($sql);
	$columns = array();
	
	//Loop through each of them, getting their name and type
	while($column = sqlFetchAssoc($result)) {
		$columns[$column['Field']] = $column;
	}
	
	//Generate a list of the columns
	$columnList = '';
	foreach($columns as $column => $details) {
		$columnList .= ($columnList? ', ' :null). '`'. $column. '`';
	}
	
	//Prepare the start of an insert statement for that table
	$insertInto = 'INSERT INTO `'. $importTable. '` ('. $columnList. ') VALUES';

	//Start generating the backup script for this table. Add a drop table if exists statement
	$inserts = 'DROP TABLE IF EXISTS `'. $importTable. '`;'.
		
		"\n\n".
		
		//Add the create table statement
		$createTable. ';'.
		
		"\n\n".
		
		//Disable indexs for faster inserting
		'ALTER TABLE `'. $importTable. '` DISABLE KEYS;'.
		
		"\n";
	
	gzwrite($gzFile, $inserts);
	
	//Run a SQL statement to get all of the information out of the table
	$sql = "
		SELECT ". $columnList. "
		FROM `". $table['actual_name']. "`
		ORDER BY 1";
	$result = sqlQuery($sql);
	
	//Start building insert statements
	$inserts = '';
	//Loop through each row
	while($row = sqlFetchAssoc($result)) {
		
		//Group the insert statement for this row together with the previous statements if possible.
		//Otherwise start a new insert statement
		if ($inserts == '') {
			$inserts = "\n". $insertInto. "\n(";
		} else {
			$inserts .= ",\n(";
		}
		
		//List the values for this row
		$comma = false;
		foreach($columns as $column => $details) {
			
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
			} elseif (is_numeric($value) && $value != '' && (stripos($value, 'e') === false)) {
				$inserts .= $value;
			
			//Otherwise, write the value quoted and escaped
			} else {
				$inserts .= "'". sqlEscape($value). "'";
			}
			
			$comma = true;
		}
		
		//If the length of the insert statement looks like it's getting even remotely close to  our maximum read size,
		//don't add any more and write the insert statement
		//Otherwise allow the next statement to be added onto this one
		if (strlen($inserts) < MYSQL_CHUNK_SIZE) {
			$inserts .= ')';
		} else {
			$inserts .= ');';
			gzwrite($gzFile, $inserts);
			$inserts = '';
		}
	}
	
	//After finishing with the tables, write any insert statements not yet written
	if ($inserts != '') {
		$inserts .= ';';
		gzwrite($gzFile, $inserts);
		$inserts = '';
	}
	
	
	$inserts = "\n\n".
		
		//Enable keys again
		'ALTER TABLE `'. $importTable. '` ENABLE KEYS;'.
		
		"\n\n\n";
	
	gzwrite($gzFile, $inserts);
}
	
$inserts = "\n\n\n\n\n;\nEND OF SQL;\n";

gzwrite($gzFile, $inserts);

//$docpath = setting('docstore_dir');
//if (!is_readable($docpath) || !is_writeable($docpath)) {
	//$docpath = false;
//}
	//writeDocstoreDirectory($gzFile);
//}
