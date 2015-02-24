<?php
/*
 * Copyright (c) 2015, Tribal Limited
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

				
class zenario_pro_features__admin_boxes__site_settings extends module_base_class {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values){
		if (isset($box['tabs']['security']['fields']['admin_use_ssl'])) {
			if (!setting('admin_use_ssl') && httpOrhttps() != 'https://') {
				$box['tabs']['security']['fields']['admin_use_ssl']['read_only'] = true;
			}
		}
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (isset($box['tabs']['security']['fields']['admin_use_ssl_port'])) {
			$box['tabs']['security']['fields']['admin_use_ssl_port']['hidden'] = !engToBooleanArray($values, 'security/admin_use_ssl');
		}
		
		if (isset($box['tabs']['zenario_pro_features__caching'])) {
				
			if (!$values['speed/compress_web_pages']) {
				$box['tabs']['zenario_pro_features__caching']['fields']['cache_web_pages']['value'] =
				$box['tabs']['zenario_pro_features__caching']['fields']['cache_web_pages']['current_value'] = '';
				$box['tabs']['zenario_pro_features__caching']['fields']['cache_web_pages']['read_only'] = true;
			} else {
				$box['tabs']['zenario_pro_features__caching']['fields']['cache_web_pages']['read_only'] = false;
			}
				
			if (!$values['speed/compress_web_pages'] || !$values['zenario_pro_features__caching/cache_web_pages']) {
				$box['tabs']['zenario_pro_features__caching']['fields']['cache_ajax']['value'] =
				$box['tabs']['zenario_pro_features__caching']['fields']['cache_ajax']['current_value'] =
				$box['tabs']['zenario_pro_features__caching']['fields']['cache_plugins']['value'] =
				$box['tabs']['zenario_pro_features__caching']['fields']['cache_plugins']['current_value'] = '';
				$box['tabs']['zenario_pro_features__caching']['fields']['caching_debug_info']['value'] =
				$box['tabs']['zenario_pro_features__caching']['fields']['caching_debug_info']['current_value'] = '';
				$box['tabs']['zenario_pro_features__caching']['fields']['cache_ajax']['read_only'] =
				$box['tabs']['zenario_pro_features__caching']['fields']['cache_plugins']['read_only'] =
				$box['tabs']['zenario_pro_features__caching']['fields']['caching_debug_info']['read_only'] = true;
			} else {
				$box['tabs']['zenario_pro_features__caching']['fields']['cache_ajax']['read_only'] =
				$box['tabs']['zenario_pro_features__caching']['fields']['cache_plugins']['read_only'] =
				$box['tabs']['zenario_pro_features__caching']['fields']['caching_debug_info']['read_only'] = false;
			}
				
			/* This is to allow clear cache without compress_web_pages & cache_web_pages
			 * if (!$values['speed/compress_web_pages'] || !$values['zenario_pro_features__caching/cache_web_pages']) {
				$box['tabs']['zenario_pro_features__clear_cache']['hidden'] = true;
					
			} else*/ {
				$box['tabs']['zenario_pro_features__clear_cache']['hidden'] = false;
		
				if (isset($box['tabs']['zenario_pro_features__clear_cache']) && checkPriv('_PRIV_EDIT_SITE_SETTING')) {
					//Manually clear the cache
					if (!empty($box['tabs']['zenario_pro_features__clear_cache']['fields']['clear_cache']['pressed'])) {
						$sql = '';
						$ids = $values = array();
						$table = 'site_settings';
						cms_core::reviewDatabaseQueryForChanges($sql, $ids, $values, $table);
						unset($box['tabs']['zenario_pro_features__clear_cache']['fields']['clear_cache']['pressed']);
		
						$box['tabs']['zenario_pro_features__clear_cache']['notices']['notice']['show'] = true;
					} else {
						$box['tabs']['zenario_pro_features__clear_cache']['notices']['notice']['show'] = false;
					}
				}
		
				if (isset($box['tabs']['zenario_pro_features__cache_stats']) && checkPriv('_PRIV_EDIT_SITE_SETTING')) {
						
					$reset = false;
					$dir = CMS_ROOT. 'cache/stats/page_caching/';
						
					//Reset the stats
					if (!empty($box['tabs']['zenario_pro_features__cache_stats']['fields']['clear_stats']['pressed'])) {
						$reset = true;
						foreach (array('total', 'hits', 'writes', 'partial_hits', 'partial_writes', 'misses', 'from', 'to') as $stat) {
							if (is_writable($dir. $stat)) {
								unlink($dir. $stat);
							} else {
								$reset = false;
							}
						}
						unset($box['tabs']['zenario_pro_features__cache_stats']['fields']['clear_stats']['pressed']);
					}
						
					if ($reset) {
						$box['tabs']['zenario_pro_features__cache_stats']['notices']['notice']['show'] = true;
					} else {
						$box['tabs']['zenario_pro_features__cache_stats']['notices']['notice']['show'] = false;
					}
						
					$stats = array();
					foreach (array('total', 'hits', 'writes', 'partial_hits', 'partial_writes', 'misses') as $stat) {
						$stats[$stat] = 0;
						if (file_exists($dir. $stat)) {
							$stats[$stat] = (int) trim(file_get_contents($dir. $stat));
						}
		
						unset($box['tabs']['zenario_pro_features__cache_stats']['fields'][$stat]['current_value']);
						$box['tabs']['zenario_pro_features__cache_stats']['fields'][$stat]['value'] = $stats[$stat];
					}
						
					foreach (array('from', 'to') as $stat) {
						$stats[$stat] = '';
						if (file_exists($dir. $stat)) {
							$stats[$stat] = filemtime($dir. $stat);
						}
		
						unset($box['tabs']['zenario_pro_features__cache_stats']['fields'][$stat]['current_value']);
						$box['tabs']['zenario_pro_features__cache_stats']['fields'][$stat]['value'] = $stats[$stat];
					}
						
					unset($box['tabs']['zenario_pro_features__cache_stats']['fields']['hits_pc']['current_value']);
					if ($stats['total'] == 0) {
						$box['tabs']['zenario_pro_features__cache_stats']['fields']['hits_pc']['value'] = '';
					} else {
						$box['tabs']['zenario_pro_features__cache_stats']['fields']['hits_pc']['value'] = round(100 * (float) $stats['hits'] / $stats['total'], 2);
					}
						
					if (file_exists($dir. 'from')) {
						$box['tabs']['zenario_pro_features__cache_stats']['fields']['clear_stats']['class'] = 'submit_selected';
					} else {
						$box['tabs']['zenario_pro_features__cache_stats']['fields']['clear_stats']['class'] = 'submit_disabled';
					}
				}
			}
		}
		
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		//Changes to the cache site settings..?
		if (checkPriv('_PRIV_EDIT_SITE_SETTING') && engToBooleanArray($box, 'tabs', 'zenario_pro_features__caching', 'edit_mode', 'on')) {
			//Empty the cache if so
			zenario_pro_features::clearCacheOnShutdown($clearAll = true);
		}
	}
}
