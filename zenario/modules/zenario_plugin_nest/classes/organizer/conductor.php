<?php
/*
 * Copyright (c) 2017, Tribal Limited
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
includeModuleSubclass('zenario_plugin_nest', 'organizer', 'nested_plugins');

class zenario_plugin_nest__organizer__conductor extends zenario_plugin_nest__organizer__nested_plugins {
	
	protected static $colourNo = 1.0;
	
	protected static $stateNames = array(
		'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
		'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ',
		'BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ','BK','BL'
	);
	
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
		$existingStatesOnSlides = getRowsArray('nested_plugins', 'states', array('instance_id' => $instanceId, 'is_slide' => 1));
		
		//The above will give us an array of CSV, which is no use to us.
		//Convert to flat CSV, then to an array, to flatten it out and make it usable
		$existingStates = arrayValuesToKeys(explodeAndTrim(implode(',', $existingStatesOnSlides)));
		
		//Look for the first unused state
		foreach (self::$stateNames as $stateName) {
			if (!isset($existingStates[$stateName])) {
				
				if ($slideId) {
					if (empty($existingStatesOnSlides[$slideId])) {
						$stateNames = $stateName;
					} else {
						$stateNames = $existingStatesOnSlides[$slideId]. ','. $stateName;
					}
				
					updateRow('nested_plugins',
						array('states' => $stateNames),
						array('instance_id' => $instanceId, 'is_slide' => 1, 'id' => $slideId));
				}
				
				return $stateName;
			}
		}
		
		
		echo adminPhrase('You have reached the maximum number of states');
		exit;
	}
	
	protected static function removeStateFromSlide($instanceId, $state) {
		
		//Check which slides have this state on them, and get an array of ids => states
		//There should only be one per nest, but I'll write a loop anyway just in case there's bad data
		$slides = getRowsArray('nested_plugins',
			'states',
			array('instance_id' => $instanceId, 'is_slide' => 1, 'states' => array($state)));
		
		//N.b. if you have a SET column in MySQL, from Zenario 7.4 onwards you can use code of the form:
			//getRow('table', 'col', array('set_column' => array('value')))
			//getRow('table', 'col', array('set_column' => array('value1', 'value2')))
		//to use FIND_IN_SET() in MySQL. (An "OR" is used in the second case.)
		
		//If you want to look for an exact set of values, then
			//getRow('table', 'col', array('set_column' => 'value'))
			//getRow('table', 'col', array('set_column' => 'value1,value2'))
		//will still work as it did before 7.4.
		
		
		foreach ($slides as $slideId => $states) {
			//Convert the CSV to an array
			$states = arrayValuesToKeys(explodeAndTrim($states));
		
			//Remove the specified state
			unset($states[$state]);
		
			//As long as there will be at least one state left on each slide, do the deletion
			if (!empty($states)) {
				updateRow('nested_plugins', array('states' => implode(',', array_keys($states))), $slideId);
				
				//Also delete any paths going from or to that state
				self::deletePath($instanceId, $state);
			}
		}
	}
	
	protected static function ensureEachSlideHasAtLeastOneState($instanceId) {
		//Look for slides with no states created, and make sure that they have at least one state each
		foreach (getRowsArray('nested_plugins', 'id', array('instance_id' => $instanceId, 'is_slide' => 1, 'states' => ''), 'slide_num') as $slideId) {
			static::addStateToSlide($instanceId, $slideId);
		}
	}
	
	protected static function addPath($instanceId, $from, $to) {
		if ($from != $to) {
			setRow('nested_paths', array(), array('instance_id' => $instanceId, 'from_state' => $from, 'to_state' => $to));
		}
	}
	
	protected static function redirectPath(
		$instanceId, $fromState,
		$oldToState, $oldEquivId = 0, $oldContentType = '',
		$newToState, $newEquivId = 0, $newContentType = ''
	) {
		updateRow('nested_paths',
			array('to_state' => $newToState, 'equiv_id' => $newEquivId, 'content_type' => $newContentType),
			array('instance_id' => $instanceId, 'from_state' => $fromState, 'to_state' => $oldToState, 'equiv_id' => $oldEquivId, 'content_type' => $oldContentType),
			$ignore = true);
	}
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		//...
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		$instance = getPluginInstanceDetails($_GET['refiner__nest'] ?? false);
		$c = $instance['class_name'];
		$this->setTitleAndCheckPermissions($path, $panel, $refinerName, $refinerId, $mode, $instance);
		
		//Look for slides with no states created, and make sure that they have at least one state each
		static::ensureEachSlideHasAtLeastOneState($instance['instance_id']);
		
		
		//Get all of the existing slides and states
		$coloursForStates = array();
		$slides = getRowsArray('nested_plugins', array('id', 'slide_num', 'name_or_title', 'states'), array('instance_id' => $instance['instance_id'], 'is_slide' => 1));
		
		if (count($slides) < 1) {
			$panel['no_items_message'] = adminPhrase('Please add at least one slide to this nest to use the nest conductor.');
		
		} else {
			
			$statesToSlideIds = [];
			foreach ($slides as $slideId => $slide) {
				$states = explodeAndTrim($slide['states']);
				foreach ($states as $state) {
					$statesToSlideIds[$state] = $slideId;
				}
			}
			
			
			//Start adding elements for each slide, state and path
			$ord = 100;
			foreach ($slides as $slide) {
			
				$states = explodeAndTrim($slide['states']);
				$multipleStates = count($states) > 1;
				
				$id = 'slide_'. $slide['id'];
				$panel['items'][$id] = array(
					'id' => $id,
					'type' => 'slide',
					'label' => $slide['name_or_title'],
					'key' => [
						'state' => $multipleStates? '' : $states[0],
						'slideId' => $slide['id']
					]
				);
				
				if ($multipleStates) {
					$panel['items'][$id]['selected_label'] = adminPhrase('Slide [[slide_num]]', $slide);
				} else {
					//If there's only one state, make the slide (grey square) unselectable
					$panel['items'][$id]['unselectable'] = true;
				}
			
				foreach ($states as $state) {
					
					$stateId = 'state_'. $state;
					$panel['items'][$stateId] = array(
						'id' => $stateId,
						'type' => 'state',
						'label' => $multipleStates? $slide['slide_num']. $state : $slide['slide_num'],
						'parent' => $id,
						'color' => $coloursForStates[$state] = self::getAColour(),
						'can_delete' => $multipleStates,
						'key' => [
							'state' => $state,
							'slideId' => $slide['id']
						]
					);
					
					$slide['state'] = $state;
					if ($multipleStates) {
						$panel['items'][$stateId]['selected_label'] = adminPhrase('Slide [[slide_num]], state [[state]]', $slide);
					} else {
						$panel['items'][$stateId]['selected_label'] = adminPhrase('Slide [[slide_num]]', $slide);
					}
					
					
					//Add item buttons for adding and moving paths to each state
					if ($multipleStates) {
						$label = adminPhrase('[[slide_num]][[state]]. [[name_or_title]]', $slide);
					} else {
						$label = adminPhrase('[[slide_num]]. [[name_or_title]]', $slide);
					}
					
					$panel['item_buttons']['add_path_'. $state] = array(
						'ord' => ++$ord,
						'only_show_on_refiner' => 'nest',
						'parent' => 'add_path',
						'label' => $label,
						'visible_if' => '
	                        item.key
	                     && item.key.state
						 && item.key.state != '. json_encode($state). '
						 && !tuix.items["path_" + item.key.state + "_" + '. json_encode($state). ']',
						'admin_box' => array(
							'path' => 'zenario_path',
							'key' => array(
								'to_state' => $state
							)
						)
					);
					$panel['item_buttons']['redirect_path_'. $state] = array(
						'ord' => ++$ord,
						'only_show_on_refiner' => 'nest',
						'parent' => 'redirect_path',
						'label' => $label,
						'visible_if' => '
							item.type == "path"
						 && item.source != '. json_encode($stateId). '
						 && !tuix.items["path_" + item.from_state + "_" + '. json_encode($state). ']',
						'ajax' => array(
							'class_name' => $c,
							'request' => array(
								'redirect_path' => $state
							)
						)
					);
					
					
					//Get all of the existing paths from this state
					$paths = getRowsArray('nested_paths', true, ['instance_id' => $instance['instance_id'], 'from_state' => $state]);
					
					foreach ($paths as $edge) {
						$pathId = 'path_'. $edge['from_state']. '_'. $edge['to_state'];
						
						if ($multipleStates) {
							$selected_label = adminPhrase('path from [[slide_num]][[state]]', $slide);
						} else {
							$selected_label = adminPhrase('path from [[slide_num]]', $slide);
						}
						
						//Check if this is a link to another content item
						if ($edge['equiv_id']) {
							$tagId = $edge['equiv_id']. '_'. $edge['content_type'];
							$pathId .= '_'. $tagId;
							$citemId = 'slide_'. $tagId;
							$citemStateId = 'state_'. $edge['to_state']. '_'. $tagId;
							
							$formatedTag = formatTag($edge['equiv_id'], $edge['content_type'], -1, false, true);
							
							//Add a box for that content item, if it doesn't already have one
							if (!isset($panel['items'][$citemId])) {
								$panel['items'][$citemId] = array(
									'id' => $citemId,
									'type' => 'slide',
									'label' => $formatedTag,
									'unselectable' => true
								);
							}
							//Add this state into the box, if it's not already there
							if (!isset($panel['items'][$citemStateId])) {
								$panel['items'][$citemStateId] = array(
									'id' => $citemStateId,
									'type' => 'state',
									'label' => $edge['to_state'],
									'parent' => $citemId,
									'color' => $coloursForStates[$citemStateId] = self::getAColour(),
									'can_delete' => false,
									'unselectable' => true
								);
							}
							
							$target = $citemStateId;
							$colour = $coloursForStates[$citemStateId];
							
							$selected_label .= adminPhrase(' to [[tag]]', ['tag' => $formatedTag]);
						
						} else {
							$target = 'state_'. $edge['to_state'];
							$colour = $coloursForStates[$edge['from_state']];
							
							if (isset($statesToSlideIds[$edge['to_state']])
							 && isset($slides[$statesToSlideIds[$edge['to_state']]])) {
								$targetSlide = $slides[$statesToSlideIds[$edge['to_state']]];
								
								if (false !== strpos($targetSlide['states'], ',')) {
									$targetSlide['state'] = $edge['to_state'];
									$selected_label .= adminPhrase(' to [[slide_num]][[state]]', $targetSlide);
								} else {
									$selected_label .= adminPhrase(' to [[slide_num]]', $targetSlide);
								}
							} else {
								$selected_label .= adminPhrase('');
							}
						}
				
						$cssClasses = '';
						$commands = explodeAndTrim($edge['commands']);
						$commands = implode(', ', $commands);
						
						if ($commands == '') {
							$cssClasses = 'dashed';
						}
			
						$panel['items'][$pathId] = array(
							'id' => $pathId,
							'type' => 'path',
							'label' => $commands,
							'classes' => $cssClasses,
							'from_state' => $edge['from_state'],
							'to_state' => $edge['to_state'],
							'source' => 'state_'. $edge['from_state'],
							'target' => $target,
							'color' => $colour,
							'selected_label' => $selected_label
						);
					}
				}
			}
			
			//Attempt to load the preset positions, if they were set previously
			if ($positions = getRow('plugin_instance_cache', 'cache', ['instance_id' => $instance['instance_id'], 'method_name' => '#conductor_positions#'])) {
				$panel['positions'] = json_decode($positions, true);
			}
		}
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		$instance = getPluginInstanceDetails($_REQUEST['refiner__nest'] ?? false);
		$this->exitIfNoEditPermsOnNest($instance);
		
		
		if (($_POST['add_state'] ?? false) && checkPriv()) {
			static::addStateToSlide($instance['instance_id'], ($_POST['slideId'] ?? false));
		
		} elseif (($_POST['delete_state'] ?? false) && checkPriv()) {
			static::removeStateFromSlide($instance['instance_id'], ($_POST['state'] ?? false));
		
		} elseif (($_POST['add_path'] ?? false) && checkPriv()) {
			static::addPath($instance['instance_id'], ($_POST['state'] ?? false), ($_POST['add_path'] ?? false));
		
		} elseif (($_POST['redirect_path'] ?? false) && checkPriv()) {
			$fromTo = explode('_', $ids);
			if (!empty($fromTo[1]) && !empty($fromTo[2])) {
				static::redirectPath($instance['instance_id'], $fromTo[1], $fromTo[2], ($fromTo[3] ?? false), ($fromTo[4] ?? false), ($_POST['redirect_path'] ?? false));
				
				return 'path_'. $fromTo[1]. '_'. ($_POST['redirect_path'] ?? false);
			}
		
		} elseif (($_POST['delete_path'] ?? false) && checkPriv()) {
			
			$fromTo = explode('_', $ids);
			if (!empty($fromTo[1]) && !empty($fromTo[2])) {
				static::deletePath($instance['instance_id'], $fromTo[1], $fromTo[2], ($fromTo[3] ?? false), ($fromTo[4] ?? false));
			}
			
		} elseif (($_POST['save_positions'] ?? false) && checkPriv()) {
			setRow('plugin_instance_cache',
				['cache' => $_POST['positions'], 'last_updated' => now()],
				['instance_id' => $instance['instance_id'], 'method_name' => '#conductor_positions#']);
		
		//} elseif (($_POST['reset_positions'] ?? false) && checkPriv()) {
		//	deleteRow('plugin_instance_cache',
		//		['instance_id' => $instance['instance_id'], 'method_name' => '#conductor_positions#']);
		}
		
		
		
	}
	
	public function organizerPanelDownload($path, $ids, $refinerName, $refinerId) {
		
	}
}