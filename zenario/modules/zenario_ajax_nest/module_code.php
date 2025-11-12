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



//Define a class for holding information on conductor links
//(N.b. everything is public so it can be passed through json_encode() and sent to the client.)
class zenario_conductor__link {
	 public $command;
	 public $toState;
	 public $vars = [];
	 public $dRequests = [];
	 public $descendants = [];
	 public $cID;
	 public $cType;
	 
	 public function __construct($command, $toState, $vars = [], $descendants = '') {
		$this->command = $command;
		$this->toState = $toState;
		
		if ($vars !== []) {
			//For each variable requested by the destination slide, check if we have it set here,
			//and if so, add it to a list of default requests.
			foreach ($vars as $var) {
				if (isset(ze::$vars[$var])) {
					$this->dRequests[$var] = ze::$vars[$var];
				
				} elseif (isset(ze::$importantGetRequests[$var])) {
					if (isset($_REQUEST[$var])) {
						$this->dRequests[$var] = $_REQUEST[$var];
					}
				
				} else {
					$this->dRequests[$var] = '';
				}
			}
		}
		if ($descendants !== '') {
			$this->descendants = ze\ray::explodeAndTrim($descendants);
		}
	 }
	 
	 public function link($requests, $itemId = null) {
	 	
		//Handle links to other content items
	 	if ($this->cID) {
	 		//Clear any requests that point to this nest/slide/state
			unset($requests['state']);
			unset($requests['slideId']);
			unset($requests['slideNum']);
			
			//Set the state or slide that we're linking to
			if (is_numeric($this->toState)) {
				$requests['slideNum'] = $this->toState;
			} else {
				$requests['state'] = $this->toState;
			}
			
			return ze\link::toItem($this->cID, $this->cType, false, $requests);
		
		//Handle links to other states/slides
		} else {
			
			$dRequests = $this->dRequests;
			
			//Ignore any requests if this is a back link
			if (!empty($requests)
			 && $this->command != 'back') {
				//Look through the requests this slide takes, and override the defaults
				//with any specific values set here.
				foreach ($dRequests as $var => &$val) {
					if (isset($requests[$var])) {
						$val = $requests[$var];
					}
				}
				unset($val);
			}
			
			$dRequests['state'] = $this->toState;
			unset($dRequests['slideId']);
			unset($dRequests['slideNum']);
			
			//Automatically unset any empty requests
			foreach ($dRequests as $var => $val) {
				if (empty($val)) {
					unset($dRequests[$var]);
				}
			}
			
			return ze\link::toItemInVisitorsLanguage(ze::$cID, ze::$cType, false, $dRequests, ze::$alias);
		}
	}
}





class zenario_ajax_nest extends zenario_abstract_nest {
	
	protected $stopDisplay = false;
	protected $stopDisplayTitle;
	protected $stopDisplayMsg;
	
	public function stopDisplay($title, $message) {
		$this->stopDisplay = true;
		$this->stopDisplayTitle = $title;
		$this->stopDisplayMsg = $message;
	}
	
	public function init() {
		
		//Flag that this plugin is actually a nest
		ze::$slotContents[$this->slotName]->flagAsNest();
		
		$nestType = $this->setting('nest_type');
		$conductorEnabled = $nestType == 'conductor';
		$this->sameHeight = (bool) $this->setting('eggs_equal_height');
		
		switch ($nestType) {
			case 'tabs':
			case 'tabs_and_buttons':
				$this->showImagesOnTabs = (bool) $this->setting('show_images_on_slide_links');
				break;
			
			case 'conductor':
				$this->showImagesOnTabs = $this->setting('show_global_tabs') && $this->setting('show_images_on_slide_links');
		}
		
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = false, $ifGetOrPostVarIsSet = false, $ifSessionVarOrCookieIsSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByFile = $this->showImagesOnTabs, $clearByModuleData = false);
		
		if ($this->loadTabs()) {
			
			//Check to see if a slide or a state is requested in the URL
			$lookForState =
			$lookForSlideId =
			$lookForSlideNum = 
			$defaultState = false;
			
			if ($conductorEnabled
			 && !empty($_REQUEST['state'])
			 && preg_match('/^[ab]?[a-z]$/i', $_REQUEST['state'])) {
				$lookForState = strtolower($_REQUEST['state']);
			
			} elseif ($lookForSlideId = ze::$slotContents[$this->slotName]->slideId() ?? 0) {
			} elseif ($lookForSlideNum = ze::$slotContents[$this->slotName]->slideNum() ?? 0) {
			}
			
			
			$tabOrd = 0;
			foreach ($this->slides as $slide) {
				++$tabOrd;
				$this->lastTab = $slide['id'];
				
				//By default, show the first slide that the visitor can see...
				if ($tabOrd == 1) {
					$this->firstSlide = $slide['id'];
					$this->slideNum = $slide['slide_num'];
					$this->slideId = $slide['id'];
					$this->slideSetsTitle = $slide['set_page_title_with_conductor'];
					$this->state = $slide['states'][0];
					$defaultState = $slide['states'][0];
				}
				
				//...but change this to the one mentioned in the request, if we see it
				if ($lookForState && in_array($lookForState, $slide['states'])) {
					$this->slideNum = $slide['slide_num'];
					$this->slideId = $slide['id'];
					$this->slideSetsTitle = $slide['set_page_title_with_conductor'];
					$this->state = $lookForState;
				
				} elseif ($lookForSlideId == $slide['id']) {
					$this->slideNum = $slide['slide_num'];
					$this->slideId = $slide['id'];
					$this->slideSetsTitle = $slide['set_page_title_with_conductor'];
					$this->state = $slide['states'][0];
				
				} elseif ($lookForSlideNum == $slide['slide_num']) {
					$this->slideNum = $slide['slide_num'];
					$this->slideId = $slide['id'];
					$this->slideSetsTitle = $slide['set_page_title_with_conductor'];
					$this->state = $slide['states'][0];
				}
				
				$tabIds[$slide['slide_num']] = $slide['id'];
				
				
				if (!isset($this->sections['Tab'])) {
					$this->sections['Tab'] = [];
				}
				
				$tabMergeFields = [
					'TAB_ORDINAL' => $tabOrd];
				
				$tabMergeFields['Class'] = 'tab_'. $tabOrd. ' tab';
				$tabMergeFields['Tab_Link'] = $this->refreshPluginSlotTabAnchor('slideId='. $slide['id'], false);
				$tabMergeFields['Tab_Name'] = $this->formatTitleText(htmlspecialchars($slide['slide_label']), true);
				
				$this->addSlideImage($tabMergeFields, $slide, $tabOrd);
				
				if ($conductorEnabled
				 && $this->slideNum == $slide['slide_num']) {
					
					if ($slide['show_back']) {
						$tabMergeFields['Show_Back'] = true;
					}
					$tabMergeFields['Show_Refresh'] = (bool) $slide['show_refresh'];
					$tabMergeFields['Show_Auto_Refresh'] = (bool) $slide['show_auto_refresh'];
					$tabMergeFields['Auto_Refresh_Interval'] = (int) $slide['auto_refresh_interval'];
					$tabMergeFields['Last_Updated'] = ze\date::formatTime(time(), '%H:%i:%S');
				}
				
				$tabMergeFields['Visible'] = !$conductorEnabled || !$slide['is_inner_slide'];
				
				$tabMergeFields['Slide_Class'] = 'slide_'. $slide['slide_num']. ' '. $slide['css_class'];
				
				$this->sections['Tab'][$slide['slide_num']] = $tabMergeFields;
			}
			
			if (isset($this->sections['Tab'][$this->slideNum]['Class'])) {
				$this->sections['Tab'][$this->slideNum]['Class'] .= '_on';
			}
			
			
			$nextSlideId = false;
			if ($this->lastTab == $this->slideId) {
				if (!$this->setting('next_prev_buttons_loop')) {
					$this->mergeFields['Next_Disabled'] = '_disabled next';
				} else {
					$nextSlideId = $this->firstSlide;
				}
			} else {
				foreach ($this->slides as $slideNum => $slide) {
					if ($slideNum > $this->slideNum) {
						$nextSlideId = $slide['id'];
						break;
					}
				}
			}
			
			if ($nextSlideId) {
				$this->mergeFields['Next_Link'] = $this->refreshPluginSlotTabAnchor('slideId='. $nextSlideId, false);
			}
			
			
			$prevSlideId = false;
			if ($this->firstSlide == $this->slideId) {
				if (!$this->setting('next_prev_buttons_loop')) {
					$this->mergeFields['Prev_Disabled'] = '_disabled prev';
				} else {
					$prevSlideId = $this->lastTab;
				}
			} else {
				foreach ($this->slides as $slideNum => $slide) {
					if ($slideNum >= $this->slideNum) {
						break;
					} else {
						$prevSlideId = $slide['id'];
					}
				}
			}
			
			if ($prevSlideId) {
				$this->mergeFields['Prev_Link'] = $this->refreshPluginSlotTabAnchor('slideId='. $prevSlideId, false);
			}
			
			$this->registerGetRequest('slideId', $this->firstSlide);
			$this->registerGetRequest('state', $defaultState);
		}
		
		if (!empty($this->slides[$this->slideNum]['request_vars'])) {
			foreach ($this->slides[$this->slideNum]['request_vars'] as $var) {
				$this->registerGetRequest($var);
			}
		}
		
		
		//Load all of the paths from the current state
		if ($conductorEnabled && $this->state) {
			
			//Add a refresh command to the current state
			$this->commands['refresh'] = new zenario_conductor__link(
				'refresh', $this->state,
				$this->slides[$this->slideNum]['request_vars']
			);
			
			
			//Loop through each slide, checking if they have any states or global commands
			$hadCommands = [];
			foreach ($this->slides as $slideNum => $slide) {
				
				//If a global command is set on a slide, it should point to the first state on that slide.
				$first = true;
				foreach ($slide['states'] as $state) {
					if ($state) {
						if ($first) {
							$first = false;
						
							//If this slide has a global command set, note it down
							//N.b. if two slides have the same global command, then go to the slide with the lowest ordinal.
							if (!$slide['is_inner_slide']
							 && ($command = $slide['global_command'])
							 && !isset($hadCommands[$command])) {
								
								//Don't allow the link if we're already in that state...
								if ($state != $this->state) {
									
									$this->commands[$command] = new zenario_conductor__link(
										$command, $state, $slide['request_vars']
									);
									$this->usesConductor = true;
								}
								
								//...but do block it, so we get consistent logic if two slides have the same global command.
								$hadCommands[$command] = true;
							}
						}
					
					
						//Note down which states are on which slides
						$this->statesToSlides[$state] = $slideNum;
					}
				}
			}
			unset($hadCommands);
			
			//Look through the nested paths that lead from this slide, and note each down
			//as long as it leads to another slide that we can see.
			$sql = "
				SELECT to_state, equiv_id, `content_type`, command, descendants, is_custom, is_forwards
				FROM ". DB_PREFIX. "nested_paths
				WHERE instance_id = ". (int) $this->instanceId. "
				  AND from_state = '". ze\escape::sql($this->state). "'
				ORDER BY to_state";
			
			foreach (ze\sql::fetchAssocs($sql) as $path) {
				$state = $path['to_state'];
				
					
				//Handle links to other content items
				if ($path['equiv_id']) {
					
					$cID = $path['equiv_id'];
					$cType = $path['content_type'];
					ze\content::langEquivalentItem($cID, $cType);
					
					if (!ze\content::checkPerm($cID, $cType)) {
						continue;
					}
					
					foreach (ze\ray::explodeAndTrim($path['command']) as $command) {
						$this->commands[$command] = new zenario_conductor__link($command, $state);
						$this->commands[$command]->cID = $cID;
						$this->commands[$command]->cType = $cType;
					}
				
				//Handle links to other slides
				} elseif (isset($this->statesToSlides[$state])) {
					
					$slideNum = $this->statesToSlides[$state];
					
					foreach (ze\ray::explodeAndTrim($path['command']) as $command) {
					
						$this->commands[$command] = new zenario_conductor__link(
							$command,
							$state,
							$this->slides[$slideNum]['request_vars'],
							$path['descendants']
						);
					
						if ($path['is_forwards']) {
							$this->forwardCommand = $command;
						}
					}
				}
				$this->usesConductor = true;
			}
			
			if ($this->usesConductor) {
				
				$vars = array_filter(array_merge($_GET, ze::$vars));
				
				unset(
					//Clear any standard content item variables
					$vars['cID'], $vars['cType'], $vars['cVersion'], $vars['visLang'],
					
					//Clear any standard plugin variables
					$vars['slotName'], $vars['instanceId'], $vars['method_call'],
					
					//Clear any requests that point to this nest/slide/state
					$vars['state'], $vars['slideId'], $vars['slideNum'],
					
					//Clear some FEA variables
					$vars['mode'], $vars['path']
				);
				
				$this->callScript('zenario_conductor', 'setCommands', $this->slotName, $this->commands, $this->state, $vars);
				
				//Add the current title of the current conductor slide to the page title if enabled in the slide's properties.
				switch ($this->slideSetsTitle) {
					case 'append':
						$this->setPageTitle(ze::$pageTitle. ': '. $this->formatTitleText($this->slides[$this->slideNum]['slide_label']));
						break;
					case 'overwrite':
						$this->setPageTitle($this->formatTitleText($this->slides[$this->slideNum]['slide_label']));
						break;
				}
			}
		}
		
		
		//If the slide we're trying to display has at least one plugin on it, display it.
		if ($this->slideNum !== false && $this->loadSlide($this->slideNum)) {
			$this->show = true;
		}
		
		if ($this->show) {
			
			//If we're reloading via AJAX, our addToPageHead() method won't be called, and the addStylesOnAJAXReload() function will add the styles.
			//Otherwise the styles will be added using addToPageHead() and addStylesOnAJAXReload() as as normal.
			$this->addStylesOnAJAXReload($this->styles);
			
			return true;
		} else {
			$this->nestEmptyMessage($conductorEnabled);
			return false;
		}
	}
	
	//This function gets all of the breadcrumbs leading up to the current slide.
	//It also gets the smart breadcrumbs at each stage.
	protected $cachedBackLinks = null;
	public function getBackLinks() {
		
		if ($this->cachedBackLinks === null) {
			$this->cachedBackLinks = require ze::funIncPath(__FILE__, __FUNCTION__);
		}
		return $this->cachedBackLinks;
	}
	
	protected function getBackState() {
		if (!empty($this->commands['back'])
		 && empty($this->commands['back']->cID)) {
			return $this->commands['back']->toState;
		}
		return false;
	}
	
	
	
	public function showSlot() {
		
		if ($this->stopDisplay) {
			echo '
				<div class="zenario_conductor_error">
					<h1>', htmlspecialchars($this->stopDisplayTitle), '</h1>
					<p>', htmlspecialchars($this->stopDisplayMsg), '</p>
				</div>';
			return;
		}
		
		$this->mergeFields['TAB_ORDINAL'] = $this->slideNum;
		
		//Show all of the plugins on this slide
		$this->mergeFields['Tabs'] = $this->sections['Tab'] ?? null;
		
		if ($this->show) {
			$this->mergeFields['Tabs'][$this->slideNum]['Plugins'] = ze::$slotContents[$this->slotName]->eggsOnSlideNum($this->slideNum);
		}
		
		if ($this->setting('show_heading') && $this->setting('nest_heading_is_a_link')) {
			$this->mergeFields['Nest_Heading_Target_Blank'] = $this->setting('nest_heading_target_blank');
			
			$linkType = $this->setting('nest_heading_link_type');
			
			if ($linkType == 'content_item') {
				$cID = $cType = false;
				$contentItem = $this->setting('nest_heading_link_content_item');
				ze\content::getCIDAndCTypeFromTagId($cID, $cType, $contentItem);
				$href = ze\link::toItem($cID, $cType);
			} elseif ($linkType == 'external_url') {
				$href = $this->setting('nest_heading_link_url');
			}
			
			$this->mergeFields['Nest_Heading_Href'] = htmlspecialchars($href);
		}
		
		$this->twigFramework($this->mergeFields);
	}
	
	
	
}