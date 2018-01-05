<?php
/*
 * Copyright (c) 2018, Tribal Limited
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


class zenario_common_features__organizer__skin_files extends ze\moduleBaseClass {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__layouts/panels/skin_files') return;
		
		if (ze::in($mode, 'full', 'quick', 'select')) {
			ze\skinAdm::checkForChangesInFiles($runInProductionMode = true);
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		if ($path != 'zenario__layouts/panels/skin_files') return;
		
		
		//Copy the contents of the readme to the help button
		require_once CMS_ROOT. 'zenario/libs/manually_maintained/mit/parsedown/Parsedown.php';
		$markdown = file_get_contents(CMS_ROOT. 'zenario/api/sample_skin_readme/README.txt');
		$markdownToHTML = new Parsedown();
		$panel['collection_buttons']['help']['help']['message'] = $markdownToHTML->text($markdown);
		
		
		if ($skin = ze\content::skinDetails($_GET['refiner__skin'] ?? false)) {
			
			$dir = ze\content::skinPath($skin['family_name'], $skin['name']);
			$skin['subpath'] = '';
			
			if (($skin['subpath'] = $_GET['refiner__subpath'] ?? false) && ($skin['subpath'] = ze\ring::decodeIdForOrganizer($skin['subpath'])) && (strpos($skin['subpath'], '..') === false)) {
				$panel['title'] = ze\admin::phrase('Files for the skin "[[display_name]]" in the template directory "[[family_name]]" in the sub-directory "[[subpath]]"', $skin);
				$skin['subpath'] .= '/';
				$dir .= $skin['subpath'];
			
			} else {
				$skin['subpath'] = '';
				$panel['title'] = ze\admin::phrase('Files for the skin "[[display_name]]" in the template directory "[[family_name]]"', $skin);
			}
			
			
			$panel['items'] = array();
			if (is_dir(CMS_ROOT. $dir)) {
				foreach (scandir(CMS_ROOT. $dir) as $file) {
					if (substr($file, 0, 1) != '.') {
						$item = array(
							'name' => $file,
							'href' => $dir. $file,
							'path' => CMS_ROOT. $dir. $file,
							'filesize' => filesize(CMS_ROOT. $dir. $file));
						
						if (is_file(CMS_ROOT. $dir. $file)) {
							if (substr($file, -4) == '.gif'
							  || substr($file, -4) == '.jpg'
							  || substr($file, -5) == '.jpeg'
							  || substr($file, -4) == '.png') {
								if ($item['filesize'] < 15000) {
									$item['list_image'] = $dir. $file;
								} else {
									$item['css_class'] = 'media_image';
								}
							}
						}
						
						if (is_dir(CMS_ROOT. $dir. $file)) {
							$item['traits']['subdir'] = true;
							$item['css_class'] = 'dropbox_files';
						} else {
							$item['link'] = false;
						}
						
						$panel['items'][ze\ring::encodeIdForOrganizer($skin['subpath']. $file)] = $item;
					}
				}
			}
		}
		
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}