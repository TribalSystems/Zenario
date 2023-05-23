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


class zenario_slideshow_simple__organizer__images_in_slideshow extends zenario_slideshow_simple {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		$instance = ze\plugin::details(ze::get('refiner__nest'));
		
		$panel['key']['cID'] = $_REQUEST['parent__cID'] ?? ze::request('cID');
		$panel['key']['cType'] = $_REQUEST['parent__cType'] ?? ze::request('cType');
		$panel['key']['cVersion'] = $_REQUEST['parent__cVersion'] ?? ze::request('cVersion');
		
		$panel['title'] = ze\admin::phrase('Editing the slideshow [[instance_name]] ([[name]])', $instance);
		
		//Don't show the "paste" button if there are no banners flagged to copy
		if (empty($_SESSION['zenario_copy_plugin']['all_banners'])) {
			unset($panel['collection_buttons']['paste']);
			unset($panel['item_buttons']['insert']);
		
		} elseif (isset($panel['item_buttons']['insert'])) {
			$panel['collection_buttons']['paste']['label'] = ze\admin::nphrase('Paste plugin', 'Paste [[count]] plugins', count($_SESSION['zenario_copy_plugin']['ids']));
			$panel['item_buttons']['insert']['label'] = ze\admin::nphrase('Insert/paste plugin', 'Insert/paste [[count]] plugins', count($_SESSION['zenario_copy_plugin']['ids']));
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		switch (ze\plugin::setting('nest_type', $refinerId)) {
			case 'tabs':
			case 'tabs_and_buttons':
				$showTabs = true;
				break;
			default:
				$showTabs = false;
		}
		$panel['columns']['slide_title']['hidden'] = !$showTabs;
		
		foreach ($panel['items'] as $eggId => &$item) {
			
			if ($item['checksum']) {
				$img = '&c='. $item['checksum'];
				$item['image'] = 'zenario/file.php?og=1'. $img;
			}
			
			if (isset($item['privacy'])) {
				if ($item['privacy'] == 'auto') {
					$item['tooltip'] = ze\admin::phrase('[[name]] is hidden. (It will become public when placed on a public content item, or private when placed on a private content item.)', ['name' => htmlspecialchars($item['filename'])]);
				} elseif ($item['privacy'] == 'private') {
					$item['tooltip'] = ze\admin::phrase('[[name]] is private. (Only a logged-in extranet user can access this image via an internal link; URL will change from time to time.)', ['name' => htmlspecialchars($item['filename'])]);
				} elseif ($item['privacy'] == 'public') {
					$item['tooltip'] = ze\admin::phrase('[[name]] is public. (Any visitor who knows the public link can access it.)', ['name' => htmlspecialchars($item['filename'])]);
				}
			}
			
			$classes = [];
			if (!empty($item['privacy'])) {
				switch ($item['privacy']) {
					case 'auto':
						$classes[] = 'zenario_image_privacy_auto';
						break;
					case 'public':
						$classes[] = 'zenario_image_privacy_public';
						break;
					case 'private':
						$classes[] = 'zenario_image_privacy_private';
						break;
				}
			}
			if (!empty($classes)) {
				$item['row_class'] = implode(' ', $classes);
			}
			
			$item['links_to'] = $item['link_type'];
			switch ($item['link_type']) {
				case '_CONTENT_ITEM':
					if ($tagId = ze\plugin::setting('hyperlink_target', $refinerId, $eggId)) {
						if (false !== ($tag = ze\content::formatTagFromTagId($tagId, true))) {
							$item['links_to'] = ze\admin::phrase('Links to [[link]]', ['link' => $tag]);
						}
					}
					break;
				case '_DOCUMENT':
					if ($url = ze\plugin::setting('url', $refinerId, $eggId)) {
						if ($document = ze\row::get('documents', ['filename'], ['id' => $documentId])) {
							$item['links_to'] = ze\admin::phrase('Links to the document [[filename]]', $document);
						}
					}
					break;
				case '_EXTERNAL_URL':
					if ($url = ze\plugin::setting('url', $refinerId, $eggId)) {
						$item['links_to'] = ze\admin::phrase('Links to [[link]]', ['link' => $url]);
					}
					break;
				case '_EMAIL':
					if ($email = ze\plugin::setting('email_address', $refinerId, $eggId)) {
						$email = str_replace('mailto:', '', $email);
						$item['links_to'] = ze\admin::phrase('Links to [[link]]', ['link' => $email]);
					}
					break;
			}
		}
		
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
		if (!($instanceId = (int) ze::request('refiner__nest'))
		 || !($instance = ze\plugin::details($instanceId))) {
			exit;
		}
		
		ze\priv::exitIfNot('_PRIV_MANAGE_REUSABLE_PLUGIN');
		
		
		
		//Add a new plugin or banner
		if (ze::post('upload_banner')) {
			ze\fileAdm::exitIfUploadError(true, false, true, 'Filedata');
			
			if ($imageId = ze\file::addToDatabase('image', $_FILES['Filedata']['tmp_name'], rawurldecode($_FILES['Filedata']['name']), true)) {
				static::addSlide($instanceId);
				return static::addBanner($imageId, $instanceId);
			} else {
				return false;
			}
		
		} elseif (ze::post('add_from_library')) {
			foreach (ze\ray::explodeAndTrim($ids, true) as $i => $imageId) {
				if (ze\row::exists('files', ['usage' => 'image', 'id' => $imageId])) {
					static::addSlide($instanceId);
					$eggId = static::addBanner($imageId, $instanceId);
				}
			}
			
			return $eggId;
		
		} elseif (ze::get('duplicate_plugin_and_add_tab')) {
			echo $this->duplicatePluginConfirm($ids);
			
		} elseif (ze::post('duplicate_plugin_and_add_tab')) {
			
			$slideNum = 1 + (int) static::maxTab($instanceId);
			static::addSlide($instanceId, false, $slideNum);
			
			$newEggId = static::duplicatePlugin($ids, $instanceId);
			
			ze\row::update('nested_plugins', [
				'ord' => 1,
				'slide_num' => $slideNum
			], $newEggId);
			
			return $newEggId;
		
		} elseif (ze::get('remove_banner')) {
			
			$eggIds = ze\ray::explodeAndTrim($ids, true);
			
			if (1 == ($instance['item_count'] = count($eggIds))) {
				$imageId = ze\plugin::setting('image', $instanceId, $eggIds[0]);
				$instance['filename'] = ze\row::get('files', 'filename', $imageId);
				$message =
					'<p>'. ze\admin::phrase('Are you sure you wish to remove [[filename]] from the slideshow &ldquo;[[name|escape]]&rdquo;?', $instance). '</p>'.
					'<p>'. ze\admin::phrase('The image will be removed from the slideshow, but will be kept in the image library.'). '</p>';
			} else {
				$message =
					'<p>'. ze\admin::phrase('Are you sure you wish to remove [[item_count]] images from the slideshow &ldquo;[[name|escape]]&rdquo;?', $instance). '</p>'.
					'<p>'. ze\admin::phrase('The images will be removed from the slideshow, but will be kept in the image library.'). '</p>';
			}
			

			$usage = ze\pluginAdm::usage($instanceId, false);
			$usagePublished = ze\pluginAdm::usage($instanceId, true);

			if ($usage > 1 || $usagePublished > 0) {
				$message .=
					'<p>'. ze\admin::phrase(
						'This will affect <span class="zenario_x_published_items">[[published]] published content item(s)</span> <span class="zenario_y_items">(<a href="[[link]]" target="_blank">[[pages]] content item(s) in total</a>).</span>',
						[
							'pages' => (int) $usage,
							'published' => (int) $usagePublished,
							'link' => htmlspecialchars(ze\pluginAdm::usageOrganizerLink($instanceId))]
					). '</p>';
			}

			echo $message;

			
		} elseif (ze::post('remove_banner')) {
			//Loop through each id and remove it. Make sure to also set the resync option on the last one!
			foreach (array_reverse(ze\ray::explodeAndTrim($ids, true), true) as $notLast => $id) {
				$slideNum = ze\row::get('nested_plugins', 'slide_num', $id);
				$slideId = ze\row::get('nested_plugins', 'id', [
					'instance_id' => $instanceId,
					'slide_num' => $slideNum,
					'is_slide' => 1
				]);
				static::removeSlide($slideId, $instanceId, !$notLast);
			}
			
		} elseif (ze::post('reorder')) {
			//Each specific Nest may have it's own rules for ordering, so be sure to call the correct reorder method for this Nest
			self::reorderNest(ze::post('refiner__nest'), explode(',', $ids), $_POST['ordinals'] ?? null, $_POST['parent_ids'] ?? null, $instance);
			self::resyncNest($instanceId, $instance);
		
		
		//If the admin selects one or more nested plugins to copy, put their IDs along with a
		//little bit of info on what was copied into a session variable to remember it.
		} elseif (ze::post('copy')) {
			$eggIds = [];
			$allBanners = true;
			$bannerId = ze\module::id('zenario_banner');
			foreach (ze\ray::explodeAndTrim($ids, true) as $eggId) {
				if ($egg = ze\row::get('nested_plugins', ['module_id'], ['id' => $eggId, 'is_slide' => 0])) {
					$eggIds[] = $eggId;
					
					if ($egg['module_id'] != $bannerId) {
						$allBanners = false;
					}
				}
			}
			
			if (!empty($eggIds)) {
				$_SESSION['zenario_copy_plugin'] = [];
				$_SESSION['zenario_copy_plugin']['ids'] = $eggIds;
				$_SESSION['zenario_copy_plugin']['eggs'] = true;
				$_SESSION['zenario_copy_plugin']['all_banners'] = $allBanners;
				
				echo '<!--Toast_Type:success-->';
				echo '<!--Toast_Message:'. ze\escape::hyp(ze\admin::nphrase('Plugin copied', '[[count]] plugins copied', count($eggIds))). '-->';
			}
			
		
		//Handle pasting what was just copied
		} elseif (ze::post('paste') && !empty($_SESSION['zenario_copy_plugin']['ids']) && !empty($_SESSION['zenario_copy_plugin']['all_banners'])) {
			$newEggIds = [];
			
			if (!empty($_SESSION['zenario_copy_plugin']['ids'])) {
				foreach ($_SESSION['zenario_copy_plugin']['ids'] as $sourceId) {
					
					//Add a new slide for each banner we're creating.
					//If we're using the insert option, this needs to be in the middle of the slideshow, bumping everything else forwards.
					//Otherwise just put it at the end.
					if (ze::post('insert') && ($dest = ze\pluginAdm::getNestDetails($ids))) {
						ze\sql::update("
							UPDATE ". DB_PREFIX. "nested_plugins
							  SET slide_num = slide_num + 1
							WHERE slide_num >= ". (int) $dest['slide_num']. "
							  AND instance_id = ". (int) $instanceId. "
							ORDER BY slide_num DESC"
						);
						$destId = static::addSlide($instanceId, false, $dest['slide_num']);
						
					} else {
						$slideNum = 1 + (int) static::maxTab($instanceId);
						$destId = static::addSlide($instanceId, false, $slideNum);
					}
					
					if ($newEggId = static::copyPastePlugin(
						$sourceId,
						!empty($_SESSION['zenario_copy_plugin']['eggs']),
						$instanceId,
						$destId,
						$mustBeBanner = true
					)) {
						$newEggIds[] = $newEggId;
					}
				}
			}
			
			unset($_SESSION['zenario_copy_plugin']);
			return implode(',', $newEggIds);
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}