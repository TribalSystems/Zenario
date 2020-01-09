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

namespace ze;

class site {

	



	//Formerly "setSetting()"
	public static function setSetting($settingName, $value, $updateDB = true, $encrypt = false, $clearCache = true) {
		\ze::$siteConfig[$settingName] = $value;
	
		if ($updateDB && \ze::$dbL) {
		
			$encryptedColExists = \ze::$dbL->checkTableDef(DB_PREFIX. 'site_settings', 'encrypted', $useCache = true);
		
			$encrypted = 0;
			if ($encryptedColExists && $encrypt && \ze\zewl::init()) {
				$encrypted = 1;
				$value = \ze\zewl::encrypt($value, false);
			}
		
			$sql = "
				INSERT INTO ". DB_PREFIX. "site_settings SET
					`name` = '". \ze\escape::sql($settingName). "',
					`value` = '". \ze\escape::sql($value). "'";
		
			if ($encryptedColExists) {
				$sql .= ",
					encrypted = ". (int) $encrypted;
			}
		
			$sql .= "
				ON DUPLICATE KEY UPDATE
					`value` = VALUES(`value`)";
		
			if ($encryptedColExists) {
				$sql .= ",
					encrypted = ". (int) $encrypted;
			}
		
			\ze\sql::update($sql, false, $clearCache);
		}
	}

	//Get the CMS version number from latest_revision_no.inc.php
	//Also attempt to guess whether this is a build or an on-demand site
	//Formerly "getCMSVersionNumber()"
	public static function versionNumber($revision = false, $addNoteAboutSVN = true) {
	
		$versionNumber = ZENARIO_VERSION;
	
		if ($revision) {
			$versionNumber .= '.'. $revision;
	
		} elseif ($revision === false && ZENARIO_IS_BUILD) {
			$versionNumber .= '.'. ZENARIO_REVISION;
		}
	
		if ($addNoteAboutSVN && !ZENARIO_IS_BUILD) {
			$versionNumber .= ZENARIO_IS_HEAD? ' (svn HEAD)' : ' (svn branch)';
		}
	
		return $versionNumber;
	}



	//Formerly "siteDescription()"
	public static function description($settingName = false) {
		//Load the site description if it's not already loaded
		if (empty(\ze::$siteDesc)) {
			//Look for a customised site description file:
			if (is_file($path = CMS_ROOT. 'zenario_custom/site_description.yaml')) {
				\ze::$siteDesc = \ze\tuix::readFile($path);
			}
		
			//If we didn't find one, try to load one of the templates
			//(Check to see which modules are in the system to try and work out which!)
			if (empty(\ze::$siteDesc)) {
				$path = CMS_ROOT. 'zenario/api/sample_site_descriptions/';
			
				if (!\ze::moduleDir('zenario_pro_features', '', true)) {
					\ze::$siteDesc = \ze\tuix::readFile($path. 'community/site_description.yaml');
			
				} elseif (!\ze::moduleDir('zenario_scheduled_task_manager', '', true) || !\ze::moduleDir('zenario_user_documents', '', true)) {
					\ze::$siteDesc = \ze\tuix::readFile($path. 'pro/site_description.yaml');
			
				} elseif (!\ze::moduleDir('zenario_geo_landing_pages', '', true) || !\ze::moduleDir('zenario_user_timers', '', true)) {
					\ze::$siteDesc = \ze\tuix::readFile($path. 'probusiness/site_description.yaml');
			
				} else {
					\ze::$siteDesc = \ze\tuix::readFile($path. 'enterprise/site_description.yaml');
				}
		
			} else {
				//Some backwards compatability checks for some old names
				foreach ([
					'enable_two_factor_security_for_admin_logins' => 'enable_two_factor_authentication_for_admin_logins',
					'require_security_code_on_admin_login' => 'enable_two_factor_authentication_for_admin_logins',
					'apply_two_factor_security_by_ip' => 'apply_two_factor_authentication_by_ip',
					'security_code_by_ip' => 'apply_two_factor_authentication_by_ip',
					'two_factor_security_timeout' => 'two_factor_authentication_timeout',
					'security_code_timeout' => 'two_factor_authentication_timeout'
				] as $old => $new) {
					if (isset(\ze::$siteDesc[$old])
					 && !isset(\ze::$siteDesc[$new])) {
						\ze::$siteDesc[$new] = \ze::$siteDesc[$old];
					}
				}
			}
		}
	
		if ($settingName) {
			if (isset(\ze::$siteDesc[$settingName])) {
				return \ze::$siteDesc[$settingName];
			} else {
				return false;
			}
		} else {
			return \ze::$siteDesc;
		}
	}
	
	
	public static function inDevMode() {
		if (!$devModeSetting = \ze::setting('site_in_dev_mode')) {
			return false;
		}
		
		if ($devModeSetting == 'head') {
			if (ZENARIO_IS_HEAD) {
				return true;
			}
		
		} elseif (is_numeric($devModeSetting)) {
			if (time() < (int) $devModeSetting) {
				return true;
			}
		}
		
		\ze\site::setSetting('site_in_dev_mode', '');
		return false;
	}
	
	
	
	
	

}