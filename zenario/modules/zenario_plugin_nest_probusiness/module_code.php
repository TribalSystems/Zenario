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

class zenario_plugin_nest_probusiness extends zenario_plugin_nest {
	
	public static function alwaysReturnTrue() {
		return true;
	}
	public static function alwaysReturnFalse() {
		return false;
	}
	
	var $allowCaching = true;
	
	public function init() {
		if (zenario_plugin_nest::init()) {
			$this->allowCaching(
				$atAll = $this->allowCaching, $ifUserLoggedIn = false, $ifGetSet = false, $ifPostSet = false, $ifSessionSet = true, $ifCookieSet = true);
			$this->clearCacheBy(
				$clearByContent = false, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);
		}
		
		return $this->tabNum !== false;
	}
	
	public static function removeHiddenTabs(&$tabs, $cID, $cType, $cVersion, $instanceId, &$allowCaching) {
		$unsets = array();
		foreach ($tabs as $tabNum => $tab) {
			if (!checkPriv()
			 && ($perms = getRow(
							ZENARIO_PLUGIN_NEST_PROBUSINESS_PREFIX. 'tabs',
							array('visibility', 'smart_group_id', 'field_id', 'field_value', 'module_class_name', 'method_name', 'param_1', 'param_2'),
							array('tab_id' => $tab['id'])))
			) {
				//Remove tabs based on the settings chosen
				if ($perms['visibility'] == 'call_static_method' ) {
					
					$allowCaching = false;
					
					if (!(inc($perms['module_class_name']))
					 || !(method_exists($perms['module_class_name'], $perms['method_name']))
					 || !(call_user_func(
							array($perms['module_class_name'], $perms['method_name']),
								$perms['param_1'], $perms['param_2'])
					)) {
						$unsets[] = $tabNum;
					}
					
				} elseif ($userId = userId()) {
					switch ($perms['visibility']) {
						case 'in_smart_group':
							if (!checkUserIsInSmartGroup($perms['smart_group_id'], $userId)) {
								$unsets[] = $tabNum;
							}
							break;
							
						case 'logged_in_with_field':
						case 'logged_in_without_field':
						case 'without_field':
							$fieldValue = datasetFieldValue('users', $perms['field_id'], $userId);
							$fieldValueMatches = (bool) $fieldValue;
							
							if ($perms['field_value'] && $perms['field_value'] != $fieldValue) {
								$fieldValueMatches = false;
							}
							
							if ($perms['visibility'] != 'logged_in_with_field') {
								$fieldValueMatches = !$fieldValueMatches;
							}
							
							if (!$fieldValueMatches) {
								$unsets[] = $tabNum;
							}
							break;
							
						case 'logged_out':
							$unsets[] = $tabNum;
					}
				} else {
					switch ($perms['visibility']) {
						case 'in_smart_group':
						case 'logged_in_with_field':
						case 'logged_in_without_field':
						case 'logged_in':
							$unsets[] = $tabNum;
					}
				}
			}
		}
		
		foreach ($unsets as $unset) {
			unset($tabs[$unset]);
		}
	}
	
	
	protected function loadTabs() {
		if (zenario_plugin_nest::loadTabs()) {
			zenario_plugin_nest_probusiness::removeHiddenTabs($this->tabs, $this->cID, $this->cType, $this->cVersion, $this->instanceId, $this->allowCaching);
			
			return !empty($this->tabs);
		} else {
			return false;
		}
	}
	
	
	static protected function resyncNest($instanceId, $mode = '') {
		zenario_plugin_nest::resyncNest($instanceId, $mode);
	}


	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	
	public static function eventPluginInstanceDuplicated($oldInstanceId, $newInstanceId) {
		require funIncPath(__FILE__, __FUNCTION__);
	}

}

?>