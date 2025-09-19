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


namespace ze;

class document {




	public static function upload($filepath, $filename, $folderId = false, $privacy = 'offline') {
		if ($fileId = \ze\fileAdm::addToDatabase('hierarchical_file', $filepath, $filename, false,false,true)) {
			return \ze\document::create($fileId, $filename, $folderId, $privacy);
		}
	}
	
	public static function create($fileId, $filename, $folderId, $privacy = 'offline') {
		
		//Get last ordinal within folder
		$sql = '
			SELECT MAX(ordinal) + 1
			FROM ' . DB_PREFIX . 'documents
			WHERE folder_id = ' . (int)($folderId ? $folderId : 0);
		$result = \ze\sql::select($sql);
		$row = \ze\sql::fetchRow($result);
		$ordinal = $row[0] ? $row[0] : 1;
		
		$documentProperties = [
			'type' => 'file',
			'file_id' => $fileId,
			'folder_id' => 0,
			'filename' => $filename,
			'file_datetime' => date("Y-m-d H:i:s"),
			'ordinal' => $ordinal,
			'privacy' => $privacy];
		
		//Delete any redirects that redirect the document to a different document
		$hasRedirect = false;
		$result = \ze\row::query('document_public_redirects', ['path'], ['file_id' => $fileId]);
		while ($redirect = \ze\sql::fetchAssoc($result)) {
			$parts = explode('/', $redirect['path']);
			\ze\cache::deleteDir(CMS_ROOT . 'public/downloads/' . $parts[0]);
			$hasRedirect = true;
		}
		\ze\row::delete('document_public_redirects', ['file_id' => $fileId]);
		
		
		$extraProperties = \ze\document::addExtract($fileId);
		$documentProperties = array_merge($documentProperties, $extraProperties);

		if ($folderId) {
			$documentProperties['folder_id'] = $folderId;
		}
		
		if ($documentId = \ze\row::insert('documents', $documentProperties)) {
			\ze\document::processRules($documentId);
			
			//If there was a redirect, this document should be public
			if ($hasRedirect || $privacy == 'public') {
				\ze\document::generatePublicLink($documentId);
			}
			
			
		}
		return $documentId;
	}
	
	public static function createFolder($name, $parentId = false, $makeNameUnqiue = false) {
		$name = mb_substr(trim($name), 0, 250, 'UTF-8');
		$nameExists = \ze\row::exists('documents', ['folder_name' => $name, 'type' => 'folder', 'folder_id' => $parentId]);
		if ($nameExists) {
			//Ensure this folder has a unique name on it's level by adding a (1) to the end
			if ($makeNameUnqiue) {
				if (!preg_match('/\s\(\d+\)$/', $name)) {
					$name .= ' (0)';
				}
				while (true) {
					//Increment number
					$name = preg_replace_callback('/\((\d+)\)$/', function($matches) {
						return '(' . ++$matches[1] . ')';
					}, $name);
					//Check again
					if (!\ze\row::exists('documents', ['folder_name' => $name, 'type' => 'folder', 'folder_id' => $parentId])) {
						break;
					}
				}
			} else {
				return false;
			}
		}
		$sql = '
			UPDATE ' . DB_PREFIX . 'documents
			SET ordinal = ordinal + 1
			WHERE folder_id = ' . (int)$parentId;
		\ze\sql::update($sql);
		
		return \ze\row::insert(
			'documents',
			[
				'type' => 'folder',
				'folder_name' => $name,
				'folder_id' => $parentId,
				'privacy' => 'public',
				'ordinal' => 0
			]
		);
	}
	
	public static function processRules($documentIds) {
		return require \ze::funIncPath(__FILE__, __FUNCTION__);
	}
	
	public static function deletePubliclink($documentId, $documentDeleted = false, $privacy = false) {
		$document = \ze\row::get('documents', ['id', 'file_id', 'filename'], $documentId);
		$file = \ze\row::get('files', ['short_checksum'], $document['file_id']);
		
		//Check if there are any other doucments using this file
		if (!empty($file)) {
			$duplicatesExist = \ze\row::exists('documents', ['file_id' => $document['file_id'], 'id' => ['!' => $documentId]]);
		
			$filePublicDir = CMS_ROOT . 'public/downloads/' . $file['short_checksum'];
			$docPublicLink = $filePublicDir. '/' . $document['filename'];
		
			//If other documents use this file, just delete this documents from the file's directory
			if ($duplicatesExist) {
				if (is_file($docPublicLink)) {
					unlink($docPublicLink);
				}
		
			//If no other documents use this file, we can delete the whole directory
			} else {
				\ze\cache::deleteDir($filePublicDir);
			}
		}
		
		//Update current item's privacy
		if ($privacy == false) {
			//Make current document offline
			\ze\row::update('documents', ['privacy' => 'offline'], ['id' => $documentId]);
		} else {
			//If "make private" was selected, make current document private instead of offline
			\ze\row::update('documents', ['privacy' => $privacy], ['id' => $documentId]);
		}
		
		//Delete any redirect
		$result = \ze\row::query('document_public_redirects', ['path'], ['document_id' => $documentId]);
		while ($redirect = \ze\sql::fetchAssoc($result)) {
			$parts = explode('/', $redirect['path']);
			\ze\cache::deleteDir(CMS_ROOT . 'public/downloads/' . $parts[0]);
		}
		\ze\row::delete('document_public_redirects', ['document_id' => $documentId]);
		
		return true;
	}
	
	
	//Remove a document from a chain.
	//(N.b. this is safe to call on a doucment that isn't currently in a chain; nothing will happen in this case.)
	public static function removeFromChain($id) {
		$chainId = \ze\row::get('documents', 'chain_id', $id);
		
		if ($chainId) {
			\ze\row::update('documents', ['chain_id' => NULL], $id);
			\ze\document::updateChain([$chainId], false);
		}
	}
	
	
	//Chain two documents together, or add a document into an existing chain, or
	//merge two chains together.
	//(N.b. this is safe to call on doucments that are already chained together in the same chain; nothing will happen in this case.)
	public static function updateChain($ids, $adding) {
		
		//Get all of the document ids that should be in the current chain
		$sql = "
			SELECT id
			FROM ". DB_PREFIX. "documents
			WHERE chain_id IN (". \ze\escape::in($ids, true). ")
			   OR chain_id IN (
				SELECT chain_id
				FROM ". DB_PREFIX. "documents
				WHERE id IN (". \ze\escape::in($ids, true). ")
			)";
		
		if ($adding) {
			$sql .= "
			   OR id IN (". \ze\escape::in($ids, true). ")";
		}
		
		$ids = \ze\sql::fetchValues($sql);
		$count = count($ids);
		
		//If we only have 1 left in a chain, remove the chain ID.
		if ($count === 1) {
			\ze\row::update('documents', ['chain_id' => NULL], ['id' => $ids]);
		
		//As long as there are more than 1 doucments in a chain, update the chain
		//ID to be the smallest document ID in the chain.
		} elseif ($count > 1) {
			$newChainId = min($ids);
			\ze\row::update('documents', ['chain_id' => $newChainId], ['id' => $ids]);
		}
		
	}

	public static function delete($documentId) {
		$details = \ze\row::get('documents', ['type', 'file_id', 'thumbnail_id'], $documentId);
		\ze\module::sendSignal('eventDocumentDeleted', [$documentId]);
		
		if ($details && $details['type'] == 'folder') {
			\ze\row::delete('documents', ['id' => $documentId]);
			$children = \ze\row::query('documents', ['id', 'type'], ['folder_id' => $documentId]);
			while ($row = \ze\sql::fetchAssoc($children)) {
				\ze\document::delete($row['id']);
			}
		} elseif ($details && $details['type'] == 'file') {
			
			$fileDetails = \ze\row::get('files', ['path', 'filename', 'location'], $details['file_id']);
			$document = \ze\row::get('documents', ['file_id', 'filename'], ['id'=>$documentId]);
			$fileIdsInDocument = \ze\row::getAssocs('documents', ['file_id', 'filename'], ['file_id' => $document['file_id']]);
			$numberFileIds = count($fileIdsInDocument);
			
			$file = \ze\row::get('files', ['id', 'filename', 'path', 'created_datetime', 'checksum'], ['id' => $document['file_id'], 'usage' => 'hierarchical_file']);

			\ze\document::deletePubliclink($documentId, true);
			
			if ($file['filename']) {
				//Please note: before 10.2, this logic would also check if any document content item uses the same file.
				//As of 10.2, that logic is removed, as docstore is now split into folders named after the `usage` column.
				//Doc content items and hierarchical documents are in separate pools.
				if (($numberFileIds == 1)) {
					\ze\row::delete('files', ['id' => $details['file_id']]);
					
					//Delete the linked row from the file_extracts table as well if it exists
					\ze\row::delete('file_extracts', ['file_id' => $details['file_id']]);
					
					if ($details['thumbnail_id']) {
						\ze\row::delete('files', ['id' => $details['thumbnail_id']]);
					}
					if ($fileDetails['location'] == 'docstore' &&  $fileDetails['path']) {
						
						$f = \ze::setting('docstore_dir') . '/'. $fileDetails['path'] . '/' . $fileDetails['filename'];
						if(is_file($f)){
							unlink($f);
						}

						$dir = \ze::setting('docstore_dir') . '/'. $fileDetails['path'];
						
						$emptyFolder=\ze\document::isDirEmpty($dir);
						if(is_dir($dir) && $emptyFolder){
							rmdir($dir);
						}
					}
				}
			}
			
			\ze\document::removeMetadata($documentId);
			\ze\document::removeFromChain($documentId);
			
			\ze\row::delete('documents', ['id' => $documentId]);
		}
	}
	
	public static function removeMetadata($documentId, $dataset = []) {
		if (!$dataset) {
			$dataset = \ze\dataset::details('documents');
		}
		
		\ze\row::delete('documents_custom_data', $documentId);
		\ze\row::delete('custom_dataset_values_link', ['dataset_id' => $dataset['id'], 'linking_id' => $documentId]);
	}
	
	public static function isDirEmpty($dir) {
		if (!is_readable($dir)){
			return false; 
		}
		
		return (count(scandir($dir)) == 2);
	}
	
	public static function deleteTag($tagId) {
		\ze\row::delete('document_tags', ['id' => $tagId]);
		
	}
	
	public static function addExtract($fileId, $reScan = false) {
		$documentProperties = [];
		$extract = [];
		$thumbnailId = false;
		\ze\fileAdm::updateHierarchicalDocumentExtract($fileId, $extract, $thumbnailId, $reScan);
		
		if ($extract['file_extract']) {
			$documentProperties['extract'] = $extract['file_extract'];
			$documentProperties['extract_wordcount'] = $extract['file_extract_wordcount'];
		}
		if ($thumbnailId) {
			$documentProperties['thumbnail_id'] = $thumbnailId;
		}
		return $documentProperties;
	}
	
	public static function generatePublicLink($document, $file = false) {
		$error = new \ze\error();
		
		if (!is_array($document)) {
			$document = \ze\row::get('documents', ['file_id', 'id', 'filename'], $document);
		}
		if (!is_array($file)) {
			$file = \ze\row::get(
				'files', 
				['id', 'filename', 'path', 'created_datetime', 'short_checksum'],
				['id' => $document['file_id']]
			);
		}
		if($file['filename']) {
			$dirPath = false;
			if (\ze\cache::cleanDirs()) {
				$dirPath = \ze\cache::createDir($file['short_checksum'], 'public/downloads', false);
			}
			if (!$dirPath) {
				$error->add('message', 'Could not generate public link because public file structure is incorrect');
				return $error;
			}
			
			$symFolder =  CMS_ROOT . $dirPath;
			$safeFilename = \ze\file::safeName($document['filename']);
			$symPath = $symFolder . $safeFilename;
			$frontLink = $dirPath . $safeFilename;
			$publicLinkHtAccessFile = $symFolder . '.htaccess';
			
			if (!\ze\server::isWindows() && ($path = \ze\file::docstorePath($file['id'], false))) {
				if (!file_exists($symPath)) {
					if(!file_exists($symFolder)) {
						mkdir($symFolder);
					}

					//If the folder exists, and there is a .htaccess file in there, delete it to prevent a redirect loop.
					if (file_exists($publicLinkHtAccessFile)) {
						unlink($publicLinkHtAccessFile);
					}

					symlink($path, $symPath);
				} 
				
				//Check if there are other documents with the same file
				$docsWithSameFile = \ze\row::getArray('documents', ['id', 'privacy'], ['file_id' => $document['file_id']]);
				foreach ($docsWithSameFile as $docWithSameFile) {
					if ($docWithSameFile['id'] == $document['id']) {
						//Make current document public
						\ze\row::update('documents', ['privacy' => 'public'], ['id' => $docWithSameFile['id']]);
					} else {
						//Preserve privacy settings of other documents with the same file
						\ze\row::update('documents', ['privacy' => $docWithSameFile['privacy']], ['id' => $docWithSameFile['id']]);
					}
				}
				//\ze\row::update('documents', ['privacy' => 'public'], ['file_id' => $document['file_id']]);
				
				return $frontLink;
				
			} else {
				if (\ze\server::isWindows()) {
					$error->add('message', 'Could not generate public link because the CMS is installed on a windows server.');
				} else {
					$error->add('message', 'Could not generate public link because this document is not stored in the Docstore.</br>Make sure the Docstore directory is correctly setup and re-upload this document.');
				}
			}
		} else {
			$error->add('message', 'Could not generate public link because no file exists');
		}
		return $error;
	}
	
	public static function remakeRedirectHtaccessFiles($documentId) {
		$sql = '
			SELECT d.filename, f.short_checksum
			FROM ' . DB_PREFIX . 'documents d
			INNER JOIN ' . DB_PREFIX . 'files f
				ON d.file_id = f.id
			WHERE d.id = ' . (int)$documentId;
		$result = \ze\sql::select($sql);
		$newFile = \ze\sql::fetchAssoc($result);
		
		//Delete any existing htaccess file in this documents public directory, which may happen if a file is reuploaded
		$path = CMS_ROOT . 'public/downloads/' . $newFile['short_checksum'] . '/.htaccess';
		if (file_exists($path)) {
			unlink($path);
		}
		
		//Make redirects for public links that point to this document
		$result = \ze\row::query('document_public_redirects', ['path'], ['document_id' => $documentId]);
		while ($redirect = \ze\sql::fetchAssoc($result)) {
			$parts = explode('/', $redirect['path']);
			\ze\cache::deleteDir(CMS_ROOT . 'public/downloads/' . $parts[0]);
			
			$path = \ze\cache::createDir($parts[0], 'public/downloads', false);
			\ze\document::makeRedirectHtaccessFile($path, $parts[1], $newFile['filename'], $newFile['short_checksum']);
		}
	}
	
	public static function makeRedirectHtaccessFile($htaccessFilePath, $redirectFromFileName, $redirectToFileName, $redirectToChecksum){
		$f = fopen($htaccessFilePath . "/.htaccess", "w");		
		$content = "options -Indexes "."\n";
		$content .= "<IfModule mod_rewrite.c> "."\n";
		$content .= "	RewriteEngine On "."\n";
		$redirectFromFileName = str_replace(' ', '\ ', $redirectFromFileName );
		$redirectToFileName = str_replace(' ', '\ ', \ze\file::safeName($redirectToFileName));
		$content .= "	RewriteRule ^.*$ " . SUBDIRECTORY . "public/downloads/".$redirectToChecksum."/". $redirectToFileName ." [R=301] "."\n";
		$content .= "</IfModule>";
		fwrite($f, $content);
		fclose($f);
	}
	
	public static function checkAllPublicLinks($forceRemake, &$errors, &$exampleFile) {
		
		$errors = 0;
		$exampleFile = null;

		//Get files that should have public links and their redirects
		$sql = "
			SELECT d.id, d.file_id, f.filename, f.location, f.path, f.short_checksum
			FROM " . DB_PREFIX . "documents d
			INNER JOIN " . DB_PREFIX . "files f
				ON d.file_id = f.id
			WHERE d.type = 'file' 
			AND d.privacy = 'public'";
		$result = \ze\sql::select($sql);
		while($doc = \ze\sql::fetchAssoc($result)) {
			
			if ($forceRemake || !file_exists(CMS_ROOT. 'public/downloads/'. $doc['short_checksum']. '/'. \ze\file::safeName($doc['filename']))) {
				//Make public link
				$publicLink = \ze\document::generatePublicLink($doc['id']);
			
				//Re-make any redirects
				if (!\ze::isError($publicLink)) {
					\ze\document::remakeRedirectHtaccessFiles($doc['id']);
				
				} else {
					++$errors;
					
					if (is_null($exampleFile)) {
						$exampleFile = $doc['filename'];
					}
				}
			}
		}
	}
}