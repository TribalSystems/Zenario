<?php
/*
 * Copyright (c) 2014, Tribal Limited
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


if (request('delete_phrase') && checkPriv('_PRIV_MANAGE_LANGUAGE_PHRASE')) {
	//Handle translated and/or customised phrases that are linked to the current phrase
	if (request('delete_translated_phrases')) {
		$sql = "
			FROM ". DB_NAME_PREFIX. "visitor_phrases AS t
			INNER JOIN ". DB_NAME_PREFIX. "visitor_phrases AS l
			   ON l.module_class_name = t.module_class_name
			  AND l.code = t.code
			WHERE t.id IN (". inEscape($ids, 'numeric'). ")
			  AND t.language_id != l.language_id";
	
		if (get('delete_phrase')) {
			$result = sqlSelect("SELECT COUNT(DISTINCT l.id) AS cnt". $sql);
			$mrg = sqlFetchAssoc($result);
			
			if (is_numeric($ids)) {
				$mrg['code'] = getRow('visitor_phrases', 'code', array('id' => $ids));
				echo '<p>', adminPhrase('Are you sure you wish to delete the Phrase &quot;[[code]]&quot;?', $mrg), '</p>';
			} else {
				echo '<p>', adminPhrase('Are you sure you wish to delete the selected Phrases?'), '</p>';
			}
			
			if ($mrg['cnt']) {
				if ($mrg['cnt'] == 1) {
					echo '<p>', adminPhrase('1 translated Phrase will also be deleted.', $mrg), '</p>';
				
				} else {
					echo '<p>', adminPhrase('[[cnt]] translated Phrases will also be deleted.', $mrg), '</p>';
				}
			}
		
		} elseif (post('delete_phrase')) {
			$result = sqlSelect("SELECT l.id". $sql);
			while ($row = sqlFetchAssoc($result)) {
				deleteRow('visitor_phrases', array('id' => $row['id']));
			}
		}
	}
	
	if (post('delete_phrase') && checkPriv('_PRIV_MANAGE_LANGUAGE_PHRASE')) {
		foreach (explode(',', $ids) as $id) {
			deleteRow('visitor_phrases', array('id' => $id));
		}
	}

} elseif (request('merge_phrases') && checkPriv('_PRIV_MANAGE_LANGUAGE_PHRASE')) {
	//Merge phrases together
	$className = false;
	$newCode = false;
	$codes = array();
	$idsToKeep = array();
	$returnId = false;
	
	//Look through the phrases that have been collected and:
		//Check if none are phrase codes
		//Check that they are all from the same module
		//Find the newest code (which will probably be the correct one)
		//Get a list of codes to merge
	$sql = "
		SELECT id, code, module_class_name, SUBSTR(code, 1, 1) = '_' AS is_code
		FROM ". DB_NAME_PREFIX. "visitor_phrases
		WHERE id IN (". inEscape($ids, 'numeric'). ")
		ORDER BY id DESC";
	$result = sqlQuery($sql);
	while ($row = sqlFetchAssoc($result)) {
		if ($row['is_code']) {
			echo adminPhrase('You can only merge phrases that are not phrase codes');
			exit;
		
		} elseif ($newCode === false) {
			$newCode = $row['code'];
			$className = $row['module_class_name'];
		
		} else {
			if ($className != $row['module_class_name']) {
				echo adminPhrase('You can only merge phrases if they are all for the same Module');
				exit;
			}
		}
		$codes[] = $row['code'];
	}
	
	if ($newCode === false) {
		echo adminPhrase('Could not merge these phrases');
		exit;
	}
	
	//Get a list of the newest ids in each language (which will probably have the most up to date translations)
	$sql = "
		SELECT MAX(id), language_id
		FROM ". DB_NAME_PREFIX. "visitor_phrases
		WHERE module_class_name = '". sqlEscape($className). "'
		  AND code IN (". inEscape($codes). ")
		GROUP BY language_id";
	$result = sqlQuery($sql);
	while ($row = sqlFetchRow($result)) {
		$idsToKeep[] = $row[0];
		
		if ($row[1] == setting('default_language')) {
			$returnId = $row[0];
		}
	}
	
	//Delete the oldest phrases that would clash with the primary key after a merge
	$sql = "
		DELETE FROM ". DB_NAME_PREFIX. "visitor_phrases
		WHERE module_class_name = '". sqlEscape($className). "'
		  AND code IN (". inEscape($codes). ")
		  AND id NOT IN (". inEscape($idsToKeep, 'numeric'). ")";
	sqlQuery($sql);
	
	//Update the remaining phrases to use the correct code
	$sql = "
		UPDATE ". DB_NAME_PREFIX. "visitor_phrases
		SET code = '". sqlEscape($newCode). "'
		WHERE module_class_name = '". sqlEscape($className). "'
		  AND id IN (". inEscape($idsToKeep, 'numeric'). ")";
	sqlQuery($sql);
	
	return $returnId;
}

