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


//The nested_plugins and conductor panels share some logic, so I'll make the conductor extend the other
//panel so they can use some common functions
ze\module::incSubclass('zenario_plugin_nest', 'organizer', 'nested_plugins');

class zenario_plugin_nest__organizer__conductor extends zenario_plugin_nest__organizer__nested_plugins {
	
	protected static $colourNo = 1.0;
	
	protected static $stateNames = [
		'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z',
		'aa','ab','ac','ad','ae','af','ag','ah','ai','aj','ak','al','am','an','ao','ap','aq','ar','as','at','au','av','aw','ax','ay','az',
		'ba','bb','bc','bd','be','bf','bg','bh','bi','bj','bk','bl'
	];
	
	protected static function getColourPart($colourNo, $offset) {
		return (int) 127.5 * (1.0 + cos($colourNo + $offset * M_PI * 2.0 / 3.0));
	}
	protected static function getAColour() {
		$colour = 'rgb('. self::getColourPart(self::$colourNo, 0). ', '. self::getColourPart(self::$colourNo, 1). ', '. self::getColourPart(self::$colourNo, 2). ')';
		++self::$colourNo;
		return $colour;
	}
	
	protected static function addStateToSlide($instanceId, $slideId = false) {
		
		//Get all of the existing states
		$existingStatesOnSlides = ze\row::getValues('nested_plugins', 'states', ['instance_id' => $instanceId, 'is_slide' => 1]);
		
		//The above will give us an array of CSV, which is no use to us.
		//Convert to flat CSV, then to an array, to flatten it out and make it usable
		$existingStates = ze\ray::valuesToKeys(ze\ray::explodeAndTrim(implode(',', $existingStatesOnSlides)));
		
		//Look for the first unused state
		foreach (self::$stateNames as $stateName) {
			if (!isset($existingStates[$stateName])) {
				
				if ($slideId) {
					if (empty($existingStatesOnSlides[$slideId])) {
						$stateNames = $stateName;
					} else {
						$stateNames = $existingStatesOnSlides[$slideId]. ','. $stateName;
					}
				
					ze\row::update('nested_plugins',
						['states' => $stateNames],
						['instance_id' => $instanceId, 'is_slide' => 1, 'id' => $slideId]);
				}
				
				return $stateName;
			}
		}
		
		
		echo ze\admin::phrase('You have reached the maximum number of states');
		exit;
	}
	
	protected static function removeStateFromSlide($instanceId, $state) {
		
		//Check which slides have this state on them, and get an array of ids => states
		//There should only be one per nest, but I'll write a loop anyway just in case there's bad data
		$slides = ze\row::getAssocs('nested_plugins',
			'states',
			['instance_id' => $instanceId, 'is_slide' => 1, 'states' => [$state]]);
		
		//N.b. if you have a SET column in MySQL, from Zenario 7.4 onwards you can use code of the form:
			//ze\row::get('table', 'col', ['set_column' => ['value']])
			//ze\row::get('table', 'col', ['set_column' => ['value1', 'value2']])
		//to use FIND_IN_SET() in MySQL. (An "OR" is used in the second case.)
		
		//If you want to look for an exact set of values, then
			//ze\row::get('table', 'col', ['set_column' => 'value'])
			//ze\row::get('table', 'col', ['set_column' => 'value1,value2'])
		//will still work as it did before 7.4.
		
		
		foreach ($slides as $slideId => $states) {
			//Convert the CSV to an array
			$states = ze\ray::valuesToKeys(ze\ray::explodeAndTrim($states));
		
			//Remove the specified state
			unset($states[$state]);
		
			//As long as there will be at least one state left on each slide, do the deletion
			if (!empty($states)) {
				ze\row::update('nested_plugins', ['states' => implode(',', array_keys($states))], $slideId);
				
				//Also delete any paths going from or to that state
				self::deletePath($instanceId, $state);
			}
		}
	}
	
	protected static function ensureEachSlideHasAtLeastOneState($instanceId) {
		//Look for slides with no states created, and make sure that they have at least one state each
		foreach (ze\row::getValues('nested_plugins', 'id', ['instance_id' => $instanceId, 'is_slide' => 1, 'states' => ''], 'slide_num') as $slideId) {
			static::addStateToSlide($instanceId, $slideId);
		}
	}
	
	protected static function addPath($instanceId, $from, $to) {
		if ($from != $to) {
			ze\row::set('nested_paths', [], ['instance_id' => $instanceId, 'from_state' => $from, 'to_state' => $to]);
		}
	}
	
	protected static function redirectPath(
		$instanceId, $fromState,
		$oldToState, $oldEquivId = 0, $oldContentType = '',
		$newToState, $newEquivId = 0, $newContentType = ''
	) {
		ze\row::update('nested_paths',
			['to_state' => $newToState, 'equiv_id' => $newEquivId, 'content_type' => $newContentType],
			['instance_id' => $instanceId, 'from_state' => $fromState, 'to_state' => $oldToState, 'equiv_id' => $oldEquivId, 'content_type' => $oldContentType],
			$ignore = true);
	}
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		//...
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		$instance = ze\plugin::details($_GET['refiner__nest'] ?? false);
		$c = $instance['class_name'];
		$this->setTitleAndCheckPermissions($path, $panel, $refinerName, $refinerId, $mode, $instance);
		
		$showVars = zenario_organizer::filterValue('dummy_column_for_filter') == 'show_vars';
		
		//Look for slides with no states created, and make sure that they have at least one state each
		static::ensureEachSlideHasAtLeastOneState($instance['instance_id']);
		
		
		//Get all of the existing slides and states
		$coloursForStates = [];
		$slides = ze\row::getAssocs('nested_plugins', ['id', 'slide_num', 'name_or_title', 'states', 'request_vars'], ['instance_id' => $instance['instance_id'], 'is_slide' => 1]);
		
		if (count($slides) < 1) {
			$panel['no_items_message'] = ze\admin::phrase('Please add at least one slide to this nest to use the nest conductor.');
		
		} else {
			
			$statesToSlideIds = [];
			foreach ($slides as $slideId => $slide) {
				$states = ze\ray::explodeAndTrim($slide['states']);
				foreach ($states as $state) {
					$statesToSlideIds[$state] = $slideId;
				}
			}
			
			
			//Start adding elements for each slide, state and path
			$ord = 100;
			foreach ($slides as $slide) {
			
				$states = ze\ray::explodeAndTrim($slide['states']);
				$multipleStates = count($states) > 1;
				
				$id = 'slide_'. $slide['id'];
				$panel['items'][$id] = [
					'id' => $id,
					'type' => 'slide',
					'key' => [
						'state' => $multipleStates? '' : $states[0],
						'slideId' => $slide['id']
					]
				];
				
				if ($showVars) {
					$panel['items'][$id]['label'] = $slide['request_vars'];
				} else {
					$panel['items'][$id]['label'] = $slide['name_or_title'];
				}
				
				if ($multipleStates) {
					$panel['items'][$id]['selected_label'] = ze\admin::phrase('Slide [[slide_num]]', $slide);
				} else {
					//If there's only one state, make the slide (grey square) unselectable
					$panel['items'][$id]['unselectable'] = true;
				}
			
				foreach ($states as $state) {
					
					$stateId = 'state_'. $state;
					$panel['items'][$stateId] = [
						'id' => $stateId,
						'type' => 'state',
						'label' => $slide['slide_num']. $state,
						'parent' => $id,
						'color' => $coloursForStates[$state] = self::getAColour(),
						'can_delete' => $multipleStates,
						'key' => [
							'state' => $state,
							'slideId' => $slide['id']
						]
					];
					
					$slide['state'] = $state;
					if ($multipleStates) {
						$panel['items'][$stateId]['selected_label'] = ze\admin::phrase('Slide [[slide_num]], state [[state]]', $slide);
					} else {
						$panel['items'][$stateId]['selected_label'] = ze\admin::phrase('Slide [[slide_num]]', $slide);
					}
					
					
					//Add item buttons for adding and moving paths to each state
					if ($multipleStates) {
						$label = ze\admin::phrase('[[slide_num]][[state]]. [[name_or_title]]', $slide);
					} else {
						$label = ze\admin::phrase('[[slide_num]]. [[name_or_title]]', $slide);
					}
					
					$panel['item_buttons']['add_path_'. $state] = [
						'ord' => ++$ord,
						'only_show_on_refiner' => 'from_nested_plugins',
						'parent' => 'add_path',
						'label' => $label,
						'visible_if' => '
	                        item.key
	                     && item.key.state
						 && item.key.state != '. json_encode($state). '
						 && !tuix.items["path_" + item.key.state + "_" + '. json_encode($state). ']',
						'admin_box' => [
							'path' => 'zenario_path',
							'key' => [
								'to_state' => $state
							]
						]
					];
					$panel['item_buttons']['redirect_path_'. $state] = [
						'ord' => ++$ord,
						'only_show_on_refiner' => 'from_nested_plugins',
						'parent' => 'redirect_path',
						'label' => $label,
						'visible_if' => '
							item.type == "path"
						 && item.source != '. json_encode($stateId). '
						 && !tuix.items["path_" + item.from_state + "_" + '. json_encode($state). ']',
						'ajax' => [
							'class_name' => $c,
							'request' => [
								'redirect_path' => $state
							]
						]
					];
					
					
					//Get all of the existing paths from this state
					$paths = ze\row::getAssocs('nested_paths', true, ['instance_id' => $instance['instance_id'], 'from_state' => $state]);
					
					foreach ($paths as $edge) {
						$pathId = 'path_'. $edge['from_state']. '_'. $edge['to_state'];
						
						if ($multipleStates) {
							$selected_label = ze\admin::phrase('path from [[slide_num]][[state]]', $slide);
						} else {
							$selected_label = ze\admin::phrase('path from [[slide_num]]', $slide);
						}
						
						//Check if this is a link to another content item
						if ($edge['equiv_id']) {
							$tagId = $edge['equiv_id']. '_'. $edge['content_type'];
							$pathId .= '_'. $tagId;
							$citemId = 'slide_'. $tagId;
							$citemStateId = 'state_'. $edge['to_state']. '_'. $tagId;
							
							$formatedTag = ze\content::formatTag($edge['equiv_id'], $edge['content_type'], -1, false, true);
							
							//Add a box for that content item, if it doesn't already have one
							if (!isset($panel['items'][$citemId])) {
								$panel['items'][$citemId] = [
									'id' => $citemId,
									'type' => 'slide',
									'label' => $formatedTag,
									'unselectable' => true
								];
							}
							//Add this state into the box, if it's not already there
							if (!isset($panel['items'][$citemStateId])) {
								$panel['items'][$citemStateId] = [
									'id' => $citemStateId,
									'type' => 'state',
									'label' => $edge['to_state'],
									'parent' => $citemId,
									'color' => $coloursForStates[$citemStateId] = self::getAColour(),
									'can_delete' => false,
									'unselectable' => true
								];
							}
							
							$target = $citemStateId;
							$colour = $coloursForStates[$citemStateId];
							
							$selected_label .= ze\admin::phrase(' to [[tag]]', ['tag' => $formatedTag]);
						
						} else {
							$target = 'state_'. $edge['to_state'];
							$colour = $coloursForStates[$edge['from_state']];
							
							if (isset($statesToSlideIds[$edge['to_state']])
							 && isset($slides[$statesToSlideIds[$edge['to_state']]])) {
								$targetSlide = $slides[$statesToSlideIds[$edge['to_state']]];
								
								if (false !== strpos($targetSlide['states'], ',')) {
									$targetSlide['state'] = $edge['to_state'];
									$selected_label .= ze\admin::phrase(' to [[slide_num]][[state]]', $targetSlide);
								} else {
									$selected_label .= ze\admin::phrase(' to [[slide_num]]', $targetSlide);
								}
							} else {
								$selected_label .= ze\admin::phrase('');
							}
						}
				
						$cssClasses = 'dotted';
						if ($edge['command'] == 'back') {
							$cssClasses = 'dashed';
						
						} elseif ($edge['is_forwards']) {
							$cssClasses = '';
						}
			
						$panel['items'][$pathId] = [
							'id' => $pathId,
							'type' => 'path',
							'label' => $edge['command'],
							'classes' => $cssClasses,
							'from_state' => $edge['from_state'],
							'to_state' => $edge['to_state'],
							'source' => 'state_'. $edge['from_state'],
							'target' => $target,
							'color' => $colour,
							'selected_label' => $selected_label
						];
				
						if ($showVars) {
							$panel['items'][$pathId]['label'] = $edge['request_vars'];
						} else {
							$panel['items'][$pathId]['label'] = $edge['command'];
						}
					}
				}
			}
			
			//Attempt to load the preset positions, if they were set previously
			if ($positions = ze\row::get('plugin_instance_store', 'store', ['instance_id' => $instance['instance_id'], 'method_name' => '#conductor_positions#'])) {
				$panel['positions'] = json_decode($positions, true);
			}
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		$instance = ze\plugin::details($_REQUEST['refiner__nest'] ?? false);
		$this->exitIfNoEditPermsOnNest($instance);
		
		
		if (ze::post('save_positions') && ze\priv::check()) {
			ze\row::set('plugin_instance_store',
				['store' => $_POST['positions'], 'last_updated' => ze\date::now(), 'is_cache' => 0],
				['instance_id' => $instance['instance_id'], 'method_name' => '#conductor_positions#']);
		
		//} elseif (ze::post('reset_positions') && ze\priv::check()) {
		//	ze\row::delete('plugin_instance_store',
		//		['instance_id' => $instance['instance_id'], 'method_name' => '#conductor_positions#']);
		
		} else {
		
			if (ze::post('add_state') && ze\priv::check()) {
				static::addStateToSlide($instance['instance_id'], ze::post('slideId'));
		
			} elseif (ze::post('delete_state') && ze\priv::check()) {
				static::removeStateFromSlide($instance['instance_id'], ze::post('state'));
		
			} elseif (ze::post('add_path') && ze\priv::check()) {
				static::addPath($instance['instance_id'], ze::post('state'), ze::post('add_path'));
		
			} elseif (ze::post('redirect_path') && ze\priv::check()) {
				$fromTo = explode('_', $ids);
				if (!empty($fromTo[1]) && !empty($fromTo[2])) {
					static::redirectPath($instance['instance_id'], $fromTo[1], $fromTo[2], ($fromTo[3] ?? false), ($fromTo[4] ?? false), ze::post('redirect_path'));
				
					return 'path_'. $fromTo[1]. '_'. ze::post('redirect_path');
				}
		
			} elseif (ze::post('delete_path') && ze\priv::check()) {
			
				$fromTo = explode('_', $ids);
				if (!empty($fromTo[1]) && !empty($fromTo[2])) {
					static::deletePath($instance['instance_id'], $fromTo[1], $fromTo[2], ($fromTo[3] ?? false), ($fromTo[4] ?? false));
				}
			}
			
			ze\pluginAdm::calcConductorHierarchy($instance['instance_id']);
		}
		
		
		
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}