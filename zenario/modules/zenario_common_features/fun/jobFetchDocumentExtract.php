<?php
/*
 * Copyright (c) 2025, Tribal Limited
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



$filesWaiting = 0;
$extractsUpdated = false;
$cS3 = $cTextract = $s3BucketName = null;


//Look for anything in the file extracts table that is from Textract and still flagged as "processing"
$sql = "
	SELECT file_id, extract_job_id
	FROM ". DB_PREFIX. "file_extracts
	WHERE extract_source = 'Textract'
	  AND extract_status = 'processing'
	  AND extract_job_id IS NOT NULL";

foreach (ze\sql::select($sql) as $extract) {
	
	++$filesWaiting;
	
	//Connect to Textract is we haven't already
	if (is_null($cS3)) {
		ze\fileAdm::textractConnection($cS3, $cTextract, $s3BucketName);
	}
	
	//Get information on this job
	if ($textract = $cTextract->getDocumentTextDetection([
		'JobId' => $extract['extract_job_id'],
	])) {
		//If it's finished, start loading in the extract
		if ($textract['JobStatus'] == 'SUCCEEDED') {
			$chunk = $chunks = $pageCount = null;
			ze\ring::parseExtractStart($chunks, $chunk);
			
			if (!empty($textract['DocumentMetadata']['Pages'])) {
				$pageCount = (int) $textract['DocumentMetadata']['Pages'];
			}
			
			do {
				//Loop through the blocks, reading out just the text
				foreach ($textract['Blocks'] as $block) {
					if ($block['BlockType'] == 'LINE') {
						$text = $block['Text'];
						ze\ring::parseExtractChunk($chunks, $chunk, $text);
					}
				}
				
			//For large documents we may only get some of the parts at one time, and
			//will need to page through them.
			} while (!empty($textract['NextToken']) && (
				$textract = $cTextract->getDocumentTextDetection([
					'JobId' => $extract['extract_job_id'],
					'NextToken' => $textract['NextToken']
				])
			));
			
			ze\ring::parseExtractEnd($chunks, $chunk);
			$textExtract = implode("\n\n", $chunks);
			
			$wordCount = \ze\fileAdm::unicodeWordCount($textExtract);
			
			//Update the file_extracts table
			ze\row::update('file_extracts', [
				'extract_status' => 'completed', 
				'requested_on' => null,
				'extract_job_id' => null,
				'extract' => $textExtract,
				'extract_wordcount' => $wordCount,
				'extract_pagecount' => $pageCount
			], $extract['file_id']);
			
			//Update the content_items_searchable_cache table, anywhere it was linked to.
			$sql = "
				UPDATE ". DB_PREFIX. "content_item_versions AS v
				INNER JOIN ". DB_PREFIX. "content_items_searchable_cache AS cc
				   ON cc.content_id = v.id
				  AND cc.content_type = v.type
				  AND cc.content_version = v.version
				SET cc.file_extract = '". ze\escape::sql($textExtract). "',
					cc.file_extract_wordcount = ". (int) $wordCount;
			
			if (is_null($pageCount)) {
				$sql .= ",
					cc.file_extract_pagecount = NULL";
			} else {
				$sql .= ",
					cc.file_extract_pagecount = ". (int) $pageCount;
			}
			
			$sql .= "
				WHERE v.file_id = ". (int) $extract['file_id'];
			ze\sql::update($sql);
			
			\ze\module::sendSignal('eventDocumentExtractUpdated', ['fileId' => $extract['file_id'], 'chunks' => $chunks, 'textExtract' => $textExtract, 'wordCount' => $wordCount]);
			
			
			//Hierarchical documents are not currently implemented, but if they were,
			//we should have a similar query to update those here too.
			
			
			$file = \ze\row::get('files', ['usage', 'short_checksum', 'filename', 'mime_type'], $extract['file_id']);
			
			//Get the file's extension
			$parts = explode('.', $file['filename']);
			$type = $parts[count($parts) - 1];
			
			//Make a filename out of the id, usage, checksum and extension
			$remoteFileName = 'textract-target-'. $file['usage']. '-'. $extract['file_id']. '-'. $file['short_checksum']. '.'. $type;
			
			//Delete the file from AWS
			$result = $cS3->deleteObject([
				'Bucket' => $s3BucketName,
				'Key' => $remoteFileName
			]);
			
			$extractsUpdated = true;
			echo ze\admin::phrase('Updated the document extract for [[filename]].', $file), "\n";

		}
	}
}

if (!$extractsUpdated) {
	echo ze\admin::nzPhrase('No document extracts are awaiting processing.',
		'1 document extract is awaiting processing but not yet ready.',
		'[[count]] document extracts are awaiting processing but not yet ready.',
		$filesWaiting
	), "\n";
	return false;

} else {
	return true;
}
