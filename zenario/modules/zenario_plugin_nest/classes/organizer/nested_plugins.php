<?php
/*
 * Copyright (c) 2020, Tribal Limited
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


class zenario_plugin_nest__organizer__nested_plugins extends zenario_plugin_nest {
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		$instance = ze\plugin::details(ze::get('refiner__nest'));
		$c = 'zenario_plugin_nest';
		
		$panel['key']['skinId'] = ze::request('skinId');
		$panel['key']['cID'] = $_REQUEST['parent__cID'] ?? ze::request('cID');
		$panel['key']['cType'] = $_REQUEST['parent__cType'] ?? ze::request('cType');
		$panel['key']['cVersion'] = $_REQUEST['parent__cVersion'] ?? ze::request('cVersion');
		
		$this->setTitleAndCheckPermissions($path, $panel, $refinerName, $refinerId, $mode, $instance);
		
		
		//Get a list of types of plugins that can be put in this nest
		$key = ['status' => 'module_running', 'is_pluggable' => 1, 'nestable' => 1];
		if ($instance['content_id']) {
			$key['can_be_version_controlled'] = 1;
		}
		$modules = ze\row::getValues('modules', 'display_name', $key, 'display_name');
		$ord = 222;
		
		//$twigSnippets = [];
		//if (ze\module::canActivate('zenario_twig_snippet')) {
		//	foreach (ze::moduleDirs('twig/') as $moduleClassName => $dir) {
		//		if (ze\module::canActivate($moduleClassName)) {
		//			foreach (scandir(CMS_ROOT. $dir) as $file) {
		//				if (substr($file, -10) == '.twig.html') {
		//					$twigSnippets[] = [$moduleClassName, substr($file, 0, -10), $file];
		//				}
		//			}
		//		}
		//	}
		//}

		
		foreach (['collection_buttons', 'item_buttons'] as $buttonType) {
			if (!empty($panel[$buttonType])) {
				foreach ($panel[$buttonType] as &$button) {
					if (is_array($button)) {
						//Fix a problem in 7.0.6 where the buttons were missing their class names
						if (empty($button['class_name'])) {
							$button['class_name'] = 'zenario_plugin_nest';
						}
					
						//Plugin pickers need different paths for Wireframe modules
						if ($instance['content_id']) {
							if (isset($button['pick_items']['path_if_wireframe'])) {
								$button['pick_items']['path'] = $button['pick_items']['path_if_wireframe'];
							}
						}
					}
				}
				
				//Automatically create drop-down menus for quickly adding plugins
				if (!empty($panel[$buttonType]['add_plugin'])) {
			
					foreach ($modules as $moduleId => $name) {
						$panel[$buttonType]['add_plugin_'. $moduleId] =
							[
								'ord' => ++$ord,
								'parent' => 'add_plugin',
								'label' => $name,
								'ajax' => [
									'class_name' => $c,
									'request' => [
										'add_plugin' => 1,
										'moduleId' => $moduleId
							]]];
					}
				}
				
				////Automatically create drop-down menus for quickly adding twig-snippets
				//if (!empty($panel[$buttonType]['add_twig_snippet'])) {
				//
				//	foreach ($twigSnippets as $i => $twigSnippet) {
				//		$panel[$buttonType]['add_twig_snippet_'. $i] =
				//			[
				//				'ord' => ++$ord,
				//				'parent' => 'add_twig_snippet',
				//				'label' => $twigSnippet[0]. '/twig/'. $twigSnippet[2],
				//				'ajax' => [
				//					'class_name' => $c,
				//					'request' => [
				//						'add_twig_snippet' => 1,
				//						'moduleClassName' => $twigSnippet[0],
				//						'snippetName' => $twigSnippet[1]
				//			]]];
				//	}
				//}
			}
		}
		
		//Find out the largest number of columns used on a layout, or just guess at 12 if there are no layouts yet
		$maxCols = (int) ze\row::max('layouts', 'cols') ?: 12;
		for ($i = 2; $i < $maxCols; ++$i) {
			$label = ze\admin::phrase('[[cols]] cols', ['cols' => $i]);
			
			$panel['columns']['cols']['values'][$i] =
				[
					'ord' => ++$ord,
					'label' => $label];
			
			$panel['item_buttons'][$i] =
				[
					'parent' => 'cols',
					'ord' => ++$ord,
					'label' => $label,
					'ajax' =>[
						'class_name' => $c,
						'request' =>
							['set_cols' => 1, 'cols' => $i]]];
		}
	}
	
	protected function setTitleAndCheckPermissions($path, &$panel, $refinerName, $refinerId, $mode, $instance) {
		
		
		//Check permissions for Wireframe modules
		if ($instance['content_id'] && !ze\content::isDraft($instance['content_id'], $instance['content_type'], $instance['content_version'])) {
			$panel['collection_buttons'] = [];
			$panel['collection_buttons']['help'] = [
				'css_class' => 'help',
				'help' => [
					'message' =>
						ze\admin::phrase('This nest is on a published, hidden or archived content item and cannot be edited.<br /><br />Create a Draft to make changes.')]];
			
			$panel['item_buttons'] = [
				'view' => $panel['item_buttons']['view'],
				'plugin_settings' => $panel['item_buttons']['plugin_settings']];
			
			unset($panel['reorder']);
		
		} elseif ($instance['content_id'] && !ze\priv::check('_PRIV_EDIT_DRAFT', $instance['content_id'], $instance['content_type'], $instance['content_version'])) {
			$panel['collection_buttons'] = [];
			$panel['collection_buttons']['help'] = [
				'css_class' => 'help',
				'help' => [
					'message' =>
						ze\admin::phrase("This content item is locked by another administrator, or you don't have the permissions to modify it.")]];
			
			$panel['item_buttons'] = [
				'view' => $panel['item_buttons']['view'],
				'plugin_settings' => $panel['item_buttons']['plugin_settings']];
			
			unset($panel['reorder']);
		
		} elseif (!$instance['content_id'] && !ze\priv::check('_PRIV_VIEW_REUSABLE_PLUGIN')) {
			exit;
		}
		
		
		//Check permissions for Reusable modules
		if (!$instance['content_id'] && !ze\priv::check('_PRIV_MANAGE_REUSABLE_PLUGIN')) {
			$panel['collection_buttons'] = [];
			$panel['item_buttons'] = [
				'view' => $panel['collection_buttons']['view'],
				'plugin_settings' => $panel['collection_buttons']['plugin_settings']];
		
		}
		
		if (!$instance['content_id'] && !ze\priv::check('_PRIV_MANAGE_REUSABLE_PLUGIN')) {
			unset($panel['reorder']);
		}
		
		
		if ($panel['key']['isSlideshow'] = (strpos($instance['class_name'], 'slide') !== false)) {
			if ($instance['content_id']) {
				$panel['title'] = ze\admin::phrase('Editing the slideshow on [[slot_name]]', $instance);
			} else {
				$panel['title'] = ze\admin::phrase('Editing the slideshow [[instance_name]] ([[name]])', $instance);
			}
		} else {
			if ($instance['content_id']) {
				$panel['title'] = ze\admin::phrase('Editing the nest on [[slot_name]]', $instance);
			} else {
				$panel['title'] = ze\admin::phrase('Editing the nest [[instance_name]] ([[name]])', $instance);
			}
		}
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		
		$statesToSlides = [];
		if ($panel['key']['usesConductor'] = ze\pluginAdm::conductorEnabled(ze::get('refiner__nest'))) {
			foreach ($panel['items'] as $id => &$item) {
				if ($item['states']) {
					foreach (explode(',', $item['states']) as $state) {
						$statesToSlides[$state] = $item['ordinal'];
					}
				}
				if (!empty($item['is_slide'])) {
					$item['name_or_title'] = zenario_plugin_nest::formatTitleTextAdmin($item['name_or_title']);
				}
			}
		}
		
		require_once CMS_ROOT. 'zenario/libs/manually_maintained/public_domain/convert_to_roman/convert_to_roman.php';
		
		foreach ($panel['items'] as $id => &$item) {
			
			if ($item['is_slide']) {
				$item['css_class'] = 'zenario_nest_tab';
				$item['cols'] = ' ';
				$item['small_screens'] = ' ';
				$item['prefix'] = $item['ordinal']. '. ';
				
				//Get a list of slide numbers/states that this state can go to
				if ($panel['key']['usesConductor'] && $item['states']) {
					$toStates = ze\sql::fetchAssocs('
						SELECT path.to_state, slide.states, path.equiv_id, path.content_type, path.command
						FROM '. DB_PREFIX. 'nested_paths AS path
						INNER JOIN '. DB_PREFIX. 'nested_plugins AS slide
						   ON slide.is_slide = 1
						  AND FIND_IN_SET(path.to_state, slide.states)
						  AND slide.instance_id = '. (int) $refinerId. '
						WHERE path.instance_id = '. (int) $refinerId. '
						  AND path.from_state IN ('. ze\escape::in($item['states']). ')'
					);
					
					$toText = [];
					foreach ($toStates as $toState) {
						$label = $toState['command']. ' → ';
						
						if ($toState['equiv_id']) {
							$label = ze\content::formatTag($toState['equiv_id'], $toState['content_type'], -1, false, true). ', ';
							
							if (is_numeric($toState['to_state'])) {
								$label .= ze\admin::phrase('slide [[to_state]]');
							} else {
								$label .= ze\admin::phrase('state [[to_state]]');
							}
						
						} elseif (isset($statesToSlides[$toState['to_state']])) {
							$label .= $statesToSlides[$toState['to_state']]. $toState['to_state'];
						}
						
						$toText[] = $label;
					}
					
					if (!empty($toText)) {
						$item['name_or_title'] .= ' | '. implode(', ', $toText);
					}
				}
			
			} else {
				$item['prefix'] = strtolower(convertToRoman($item['ordinal'])). '. ';
				
				if ($item['checksum']) {
					$img = '&c='. $item['checksum'];
					$item['image'] = 'zenario/file.php?og=1'. $img;
				}
				
				//Add a warning if a plugin is flagged as "makes breadcrumbs",
				//but no breadcrumb links are set in the condcutor
				if ($item['makes_breadcrumbs'] > 1
				 && !ze\row::exists('nested_paths', ['instance_id' => $refinerId, 'slide_num' => $item['slide_num'], 'is_forwards' => 1])) {
					$item['makes_breadcrumbs'] += 10;
				}
			}
		}
	}
	
	
	//Check to see if the current Admin has the rights to change this nest, exit if not
	public function exitIfNoEditPermsOnNest($instance) {
		
		if (!ze\module::inc($instance['class_name'])) {
			exit;
		
		} elseif ($instance['content_id'] && !ze\content::isDraft($instance['content_id'], $instance['content_type'], $instance['content_version'])) {
			exit;
		
		} elseif ($instance['content_id'] && !ze\priv::check('_PRIV_EDIT_DRAFT', $instance['content_id'], $instance['content_type'], $instance['content_version'])) {
			exit;
		
		} elseif (!$instance['content_id'] && !(ze::post('reorder')) && !ze\priv::check('_PRIV_MANAGE_REUSABLE_PLUGIN')) {
			exit;
		
		} elseif (!$instance['content_id'] && (ze::post('reorder')) && !ze\priv::check('_PRIV_MANAGE_REUSABLE_PLUGIN')) {
			exit;
		}
		
		//If this is a Wireframe Plugin, and a submit is being made, update the latest modification date
		if ($instance['content_id'] && !empty($_POST)) {
			ze\contentAdm::updateVersion($instance['content_id'], $instance['content_type'], $instance['content_version']);
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		
		if (!($instanceId = (int) ze::request('refiner__nest'))
		 || !($instance = ze\plugin::details($instanceId))) {
			exit;
		}
		$this->exitIfNoEditPermsOnNest($instance);
		
		//Add a slide.
		//Also, if we're adding a new plugin, ensure that at least one slide has been made.
		if (ze::post('add_slide')
		 || ze::post('upload_banner')
		 || ze::post('add_plugin')
		 || ze::post('add_twig_snippet')
		 || ze::post('copy_plugin_instance')) {
			if (ze::post('add_slide')
			 || !ze\row::exists('nested_plugins', ['instance_id' => $instanceId, 'is_slide' => 1])) {
				static::addSlide($instanceId);
			}
		}
		
		//Add a new plugin or banner
		if (ze::post('add_plugin')) {
			return static::addPlugin(ze::post('moduleId'), $instanceId, $ids, false, true);
		
		} elseif (ze::post('copy_plugin_instance')) {
			if ($ids2) {
				return static::addPluginInstance($ids2, $instanceId, $ids, true);
			} else {
				return static::addPluginInstance($ids, $instanceId);
			}
		
		} elseif (ze::post('upload_banner')) {
			ze\fileAdm::exitIfUploadError(true, false, true, 'Filedata');
			
			if ($imageId = ze\file::addToDatabase('image', $_FILES['Filedata']['tmp_name'], rawurldecode($_FILES['Filedata']['name']), true)) {
				return static::addBanner($imageId, $instanceId, $ids, true);
			} else {
				return false;
			}
		
		//} elseif (ze::post('add_twig_snippet')) {
		//	return static::addTwigSnippet(ze::post('moduleClassName'), (ze::post('snippetName')), $instanceId, $ids, true);
		
		} elseif (ze::get('duplicate_plugin') || ze::get('duplicate_plugin_and_add_tab')) {
			echo $this->duplicatePluginConfirm($ids);
			
		} elseif (ze::post('duplicate_plugin')) {
			return static::duplicatePlugin($ids, $instanceId);
		
		} elseif (ze::post('duplicate_plugin_and_add_tab')) {
			static::addSlide($instanceId);
			return static::duplicatePlugin($ids, $instanceId);
		
		//Change the number of columns that a plugin takes up
		} elseif (ze::post('set_cols')) {
			
			$cols = (int) (ze::post('cols'));
			
			foreach (explode(',', $ids) as $id) {
				ze\row::update('nested_plugins',
					['cols' => (ze::post('cols'))],
					['instance_id' => $instanceId, 'is_slide' => 0, 'id' => $id]);
				
				//"only" is only a valid option for full width columns (0) or groupings (-1).
				//If this isn't a full width or a grouping, then change any "only"s to "show"s.
				if ($cols > 0) {
					ze\row::update('nested_plugins',
						['small_screens' => 'show'],
						['instance_id' => $instanceId, 'is_slide' => 0, 'id' => $id, 'small_screens' => 'only']);
				}
			}
		
		//Set a plugin to either show or hide on mobile view.
		} elseif (ze::in(ze::post('small_screens'), 'show', 'hide')) {
			foreach (explode(',', $ids) as $id) {
				ze\row::update('nested_plugins',
					['small_screens' => (ze::post('small_screens'))],
					['instance_id' => $instanceId, 'is_slide' => 0, 'id' => $id]);
			}
		
		//Set a plugin to only be shown on mobile view.
		//Note that this is only valid for full width columns (0) or groupings (-1).
		} elseif (ze::post('small_screens') == 'only') {
			foreach (explode(',', $ids) as $id) {
				ze\row::update('nested_plugins',
					['small_screens' => (ze::post('small_screens'))],
					['instance_id' => $instanceId, 'is_slide' => 0, 'cols' => [-1, 0], 'id' => $id]);
			}
		
		//Flag or unflag which plugin should be used for the breadcrumbs
		} elseif (isset($_POST['use_for_breadcrumbs'])) {
			if ($slideNum = ze\row::get('nested_plugins', 'slide_num', ['id' => $ids, 'instance_id' => $instanceId, 'is_slide' => 0])) {
					
				//Clear the flag from any other breadcrumb-enabled plugin on this slide
				ze\row::update('nested_plugins',
					['makes_breadcrumbs' => 1],
					['instance_id' => $instanceId, 'slide_num' => $slideNum, 'makes_breadcrumbs' => ['>' => 0]]
				);
				
				//Set the flag on this plugin
				ze\row::update('nested_plugins',
					['makes_breadcrumbs' => max(1, min(3, (int) $_POST['use_for_breadcrumbs']))],
					['id' => $ids, 'instance_id' => $instanceId, 'is_slide' => 0, 'makes_breadcrumbs' => ['>' => 0]]
				);
			}
		
		} elseif (ze::get('remove_plugin')) {
			echo $this->removePluginConfirm($ids, $instanceId);
			
		} elseif (ze::post('remove_plugin')) {
			//Loop through each id and remove it. Make sure to also set the resync option on the last one!
			foreach (array_reverse(ze\ray::explodeAndTrim($ids, true), true) as $notLast => $id) {
				static::removePlugin($id, $instanceId, !$notLast);
			}
		
		} elseif (ze::get('remove_tab')) {
			echo $this->removeSlideConfirm($ids, $instanceId);
			
		} elseif (ze::post('remove_tab')) {
			//Loop through each id and remove it. Make sure to also set the resync option on the last one!
			foreach (array_reverse(ze\ray::explodeAndTrim($ids, true), true) as $notLast => $id) {
				static::removeSlide($id, $instanceId, !$notLast);
			}
			
		} elseif (ze::post('reorder')) {
			//Each specific Nest may have it's own rules for ordering, so be sure to call the correct reorder method for this Nest
			self::reorderNest(ze::post('refiner__nest'), explode(',', $ids), $_POST['ordinals'], $_POST['parent_ids']);
			self::resyncNest($instanceId);
		}
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}