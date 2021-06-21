<?php
/*
 * Copyright (c) 2021, Tribal Limited
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

class zenario_common_features__admin_boxes__site_settings extends ze\moduleBaseClass {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if (is_array($box['tabs'] ?? false)) {
			foreach ($box['tabs'] as $tabName => &$tab) {
				if (!empty($tab['fields'])
				 && is_array($tab['fields'])) {
					foreach ($tab['fields'] as $fieldId => &$field) {
						$isSecret = !empty($field['site_setting']['secret']);
						
						if ($setting = $field['site_setting']['name'] ?? false) {
							
							
							if ($perm = ze\ring::chopPrefix('perm.', $setting)) {
								$field['value'] = ze\user::permSetting($perm);
							
							} elseif ($isSecret) {
								$field['value'] = ze::secretSetting($setting);
							
							} else {
								$field['value'] = ze::setting($setting);
							}
					
							if ($setting == 'default_template_family') {
								$field['values'] = [$field['value'] => $field['value']];
					
							} elseif ($setting == 'admin_domain' || $setting == 'primary_domain') {
								$field['value'] = ($field['value'] ?: 'none');
							}
						}
						
						if ($isSecret && ($field['type'] ?? '') == 'password') {
							$field['onmouseover'] = ($field['onmouseover'] ?? ''). ' this.type = "text";';
							$field['onmouseout'] = ($field['onmouseout'] ?? ''). ' this.type = "password";';
							$field['css_class'] = ($field['css_class'] ?? ''). ' zenario_secret_password';
							$field['pre_field_html'] = ($field['pre_field_html'] ?? ''). '<span
								class="zenario_secret_password_wrap'. (!empty($field['readonly']) || !empty($field['disabled'])? ' zenario_secret_password_wrap_readonly' : ''). '"
								onclick="zenarioAB.get(\''. ze\escape::js($fieldId). '\').focus();"
							>';
							$field['post_field_html'] = '</span>'. ($field['post_field_html'] ?? '');
						}
					}
				}
			}
		}

		if (isset($fields['admin_domain/admin_domain_is_public'])) {
			$fields['admin_domain/admin_domain_is_public']['value'] = !ze\link::adminDomainIsPrivate();
		}

		if (isset($fields['email/email_address_system'])) {
			$fields['email/email_address_system']['value'] = EMAIL_ADDRESS_GLOBAL_SUPPORT;
		}

		if (isset($fields['filesizes/apache_max_filesize'])) {
			$fields['filesizes/apache_max_filesize']['value'] = ze\file::fileSizeConvert(ze\dbAdm::apacheMaxFilesize());
		}
		if (isset($fields['filesizes/max_allowed_packet'])) {
			$fields['filesizes/max_allowed_packet']['value'] = '?';
	
			if ($result = @ze\sql::select("SHOW VARIABLES LIKE 'max_allowed_packet'")) {
				$settings = [];
				if ($row = ze\sql::fetchRow($result)) {
					$fields['filesizes/max_allowed_packet']['value'] = ze\file::fileSizeConvert($row[1]);
				}
			}
		}

		if (isset($fields['default_language/default_language'])) {
			ze\contentAdm::getLanguageSelectListOptions($fields['default_language/default_language']);
		}

		if (isset($fields['urls/mod_rewrite_suffix'])) {
			$fields['urls/mod_rewrite_suffix']['values'] = ['.htm' => '.htm', '.html' => '.html'];
	
			//Hide/show different options and notes, depending on whether language specific domains are
			//used, and whether every language has a language specific URL
			if (ze\lang::count() > 1) {
		
				$langSpecificDomainsUsed = ze\row::exists('languages', ['domain' => ['!' => '']]);
				$langSpecificDomainsNotUsed = ze\row::exists('languages', ['domain' => '']);
		
				if ($langSpecificDomainsUsed) {
					if ($langSpecificDomainsNotUsed) {
						$fields['urls/note_d']['hidden'] = true;
					} else {
						$fields['urls/translations_hide_language_code']['hidden'] =
						$fields['urls/note_a']['hidden'] =
						$fields['urls/note_b']['hidden'] =
						$fields['urls/note_c']['hidden'] = true;
					}
				} else {
					$fields['urls/note_c']['hidden'] =
					$fields['urls/note_d']['hidden'] = true;
				}
			} else {
				$fields['urls/note_c']['hidden'] =
				$fields['urls/note_d']['hidden'] = true;
			}
		}

		if (isset($fields['dates/vis_date_format_short'])) {
			ze\miscAdm::formatDateFormatSelectList($fields['dates/vis_date_format_short'], true);
			ze\miscAdm::formatDateFormatSelectList($fields['dates/vis_date_format_med']);
			ze\miscAdm::formatDateFormatSelectList($fields['dates/vis_date_format_long']);
			ze\miscAdm::formatDateFormatSelectList($fields['dates/vis_date_format_datepicker'], true, true);
			ze\miscAdm::formatDateFormatSelectList($fields['dates/organizer_date_format'], true, true);
		}

		foreach ([
			'admin_domain' => 'Use [[domain]] as the admin domain; redirect all administrators to [[domain]]',
			'primary_domain' => 'Use [[domain]] as the primary domain; redirect all visitors to [[domain]]'
		] as $domainSetting => $phrase) {
			if (isset($box['tabs'][$domainSetting]['fields'][$domainSetting])) {
				if (ze::setting($domainSetting)) {
					$box['tabs'][$domainSetting]['fields'][$domainSetting]['values'][ze::setting($domainSetting)] =
						['ord' => 2, 'label' => ze\admin::phrase($phrase, ['domain' => ze::setting($domainSetting)])];
				}
				if ($_SERVER['HTTP_HOST'] != ze::setting($domainSetting)) {
					$box['tabs'][$domainSetting]['fields'][$domainSetting]['values'][$_SERVER['HTTP_HOST']] =
						['ord' => 3, 'label' => ze\admin::phrase($phrase, ['domain' => $_SERVER['HTTP_HOST']])];
				}
			}
		}
		
		$path = 'zenario/has_database_changed_and_is_cache_out_of_date.php';
		$post = true;
		if (isset($fields['primary_domain/new']) && !(ze\curl::checkEnabled() && ($thisDomainCheck = ze\curl::fetch(ze\link::absolute(). $path, $post)))) {
			unset($fields['primary_domain/new']['note_below']);
		}
		if (isset($fields['cookie_free_domain/cookie_free_domain']) && !(ze\curl::checkEnabled() && ($thisDomainCheck = ze\curl::fetch(ze\link::absolute(). $path, $post)))) {
			unset($fields['cookie_free_domain/cookie_free_domain']['note_below']);
		}

		//Check whether compression is enabled on the server
		
		
		if (isset($fields['speed/compress_web_pages'])) {
			if (function_exists('apache_get_modules') && in_array('mod_deflate', apache_get_modules())) {
				$values['speed/compress_web_pages'] = 1;
				$fields['speed/compress_web_pages']['readonly'] = true;
				$fields['speed/compress_web_pages']['note_below'] .=
					'<br/>'.
					ze\admin::phrase('Compression is enabled on this server (<code>mod_deflate</code> is enabled in Apache).');
			
			} else
			if (extension_loaded('zlib')
			 && ze\server::checkFunctionEnabled('ini_get')
			 && ze\ring::engToBoolean(ini_get('zlib.output_compression'))
			) {
				$values['speed/compress_web_pages'] = 1;
				$fields['speed/compress_web_pages']['readonly'] = true;
				$fields['speed/compress_web_pages']['note_below'] .=
					'<br/>'.
					ze\admin::phrase('Compression is enabled on this server (<code>zlib.output_compression</code> is set in your <code>php.ini</code> and/or <code>.htaccess</code> file).');
			
			} else {
				$values['speed/compress_web_pages'] = '';
				$fields['speed/compress_web_pages']['readonly'] = true;
				$fields['speed/compress_web_pages']['note_below'] .=
					'<br/>'.
					ze\admin::phrase('Compression is not enabled on this server. To enable, do one of the following: <br/> &nbsp; &bull; Enable <code>mod_deflate</code> in Apache<br/> &nbsp; &bull; Enable <code>zlib.output_compression</code> in your <code>php.ini</code><br/> &nbsp; &bull; Enable <code>zlib.output_compression</code> in your <code>.htaccess</code> file');
			}
		}
		
		if (isset($fields['speed/have_query_cache'])) {
			if ($result = @ze\sql::select("SHOW VARIABLES LIKE '%query_cache%'")) {
				$settings = [];
				while ($row = ze\sql::fetchRow($result)) {
					$settings[$row[0]] = $row[1];
				}
		
				if (!$fields['speed/query_cache_size']['hidden'] = !(
					$fields['speed/have_query_cache']['value'] =
					$fields['speed/have_query_cache']['current_value'] =
						ze\ring::engToBoolean($settings['have_query_cache'] ?? false) && ze\ring::engToBoolean($settings['query_cache_type'] ?? false)
				)) {
					$fields['speed/query_cache_size']['value'] =
					$fields['speed/query_cache_size']['current_value'] = ze\lang::formatFilesizeNicely((int) ($settings['query_cache_size'] ?? false), $precision = 1, $adminMode = true);
				}
	
			} else {
				$fields['speed/have_query_cache']['post_field_html'] = ' '. ze\admin::phrase('(Could not check)');
				$fields['speed/query_cache_size']['hidden'] = true;
			}
		}

		if (isset($fields['test/test_send_email_address'])) {
			$adminDetails = ze\admin::details(ze\admin::id());
			$fields['test/test_send_email_address']['value'] = $adminDetails['admin_email'];
		}

		//Working copy images store a number for enabled. But the UI is a checkbox for enabled, and then a number if enabled.
		//Convert the format when displaying the fields
		if (isset($fields['image_sizes/custom_thumbnail_1'])) {
			if (ze::setting('custom_thumbnail_1_width')) {
				$fields['image_sizes/custom_thumbnail_1']['value'] = 1;
			} else {
				$fields['image_sizes/custom_thumbnail_1']['value'] = '';
				$fields['image_sizes/custom_thumbnail_1_width']['value'] =
				$fields['image_sizes/custom_thumbnail_1_height']['value'] = 300;
			}
		}
		if (isset($fields['image_sizes/custom_thumbnail_2'])) {
			if (ze::setting('custom_thumbnail_2_width')) {
				$fields['image_sizes/custom_thumbnail_2']['value'] = 1;
			} else {
				$fields['image_sizes/custom_thumbnail_2']['value'] = '';
				$fields['image_sizes/custom_thumbnail_2_width']['value'] =
				$fields['image_sizes/custom_thumbnail_2_height']['value'] = 200;
			}
		}
		if (isset($fields['image_resizing/thumbnail_threshold'])) {
			if (!$values['image_resizing/thumbnail_threshold']) {
				$values['image_resizing/thumbnail_threshold'] = 66;
			}
		}

		if (isset($fields['security/enable_two_factor_authentication_for_admin_logins'])) {
			
			if (ze\ring::engToBoolean(ze\site::description('enable_two_factor_authentication_for_admin_logins'))) {
				$values['security/enable_two_factor_authentication_for_admin_logins'] = 1;
				$values['security/apply_two_factor_authentication_by_ip'] = ze\site::description('apply_two_factor_authentication_by_ip');
				$values['security/two_factor_authentication_timeout'] = ze\site::description('two_factor_authentication_timeout');
			}
		}

		if (isset($fields['styles/email_style_formats'])) {
	
			$yaml = ['email_style_formats' => ze\site::description('email_style_formats')];
	
			if (empty($yaml['email_style_formats'])) {
				$yaml['email_style_formats'] = [];
			}
	
			$values['email_style_formats'] = Spyc::YAMLDump($yaml, 4);
		}

		//On multisite sites, don't allow local Admins to change the directory paths
		if (ze\db::hasGlobal() && !($_SESSION['admin_global_id'] ?? false)) {
			foreach (['backup_dir', 'docstore_dir'] as $dir) {
				if (isset($box['tabs'][$dir]['edit_mode'])) {
					$box['tabs'][$dir]['edit_mode']['enabled'] = false;
				}
			}
		}

		//Hack to stop a buggy error message from appearing if the admin never opens the second tab on the branding FAB,
		//as the _was_hidden_before property was never set by the JavaScript on the client side.
		//Fix this problem by setting the _was_hidden_before property for the field.
		if (isset($fields['og/organizer_favicon'])) {
			if ($values['og/organizer_favicon'] != 'custom') {
				$fields['og/custom_organizer_favicon']['_was_hidden_before'] = true;
			}
		}
		
		//Options for setting individual cookies only available with cookie consent module
		$fields['cookies/individual_cookie_consent']['hidden'] = !ze\module::inc('zenario_cookie_consent_status');
		
		//Load phrases for cookie messages
		if (isset($fields['cookies/cookie_require_consent'])) {
			$values['cookies/_COOKIE_CONSENT_IMPLIED_MESSAGE'] = $this->loadPhrase(true, '_COOKIE_CONSENT_IMPLIED_MESSAGE');
			$values['cookies/_COOKIE_CONSENT_CONTINUE'] = $this->loadPhrase(false, '_COOKIE_CONSENT_CONTINUE');
			
			$values['cookies/_COOKIE_CONSENT_MESSAGE'] = $this->loadPhrase(true, '_COOKIE_CONSENT_MESSAGE');
			$values['cookies/_COOKIE_CONSENT_ACCEPT'] = $this->loadPhrase(false, '_COOKIE_CONSENT_ACCEPT');
			$values['cookies/_COOKIE_CONSENT_MANAGE'] = $this->loadPhrase(false, '_COOKIE_CONSENT_MANAGE');
			$values['cookies/_COOKIE_CONSENT_REJECT'] = $this->loadPhrase(false, '_COOKIE_CONSENT_REJECT');
			$values['cookies/_COOKIE_CONSENT_CLOSE'] = $this->loadPhrase(false, '_COOKIE_CONSENT_CLOSE');
			
			//If multiple languages are enabled, make it clear you're just editing the phrases in the default language
			$numLanguages = ze\lang::count();
			if ($numLanguages > 1) {
				
				$mrg = ['lang' => ze\lang::name(ze::$defaultLang)];
				
				$fields['cookies/_COOKIE_CONSENT_MESSAGE']['label'] =
				$fields['cookies/_COOKIE_CONSENT_IMPLIED_MESSAGE']['label'] = ze\admin::phrase('Message in [[lang]]', $mrg);
				
				$fields['cookies/_COOKIE_CONSENT_CONTINUE']['label'] = ze\admin::phrase('"Continue" button text in [[lang]]', $mrg);
				$fields['cookies/_COOKIE_CONSENT_ACCEPT']['label'] = ze\admin::phrase('"Accept cookies" button text in [[lang]]', $mrg);
				$fields['cookies/_COOKIE_CONSENT_MANAGE']['label'] = ze\admin::phrase('"Manage cookies" button text in [[lang]]', $mrg);
				$fields['cookies/_COOKIE_CONSENT_REJECT']['label'] = ze\admin::phrase('"Reject cookies" button text in [[lang]]', $mrg);
				$fields['cookies/_COOKIE_CONSENT_CLOSE']['label'] = ze\admin::phrase('"Close" button text in [[lang]]', $mrg);
			}
		}
		
		//Add editor options to standard email template editor
		$styleFormats = ze\site::description('email_style_formats');
		if (!empty($styleFormats)) {
			$fields['template/standard_email_template']['editor_options']['style_formats'] = $styleFormats;
			$fields['template/standard_email_template']['editor_options']['toolbar'] =
				'undo redo | image link unlink | bold italic | removeformat | styleselect | fontsizeselect | formatselect | numlist bullist | outdent indent | code';
		}
		
		if ($settingGroup == 'data_protection') {
			//Show a warning if the scheduled task for deleting content is not running.
			if (!ze\module::inc('zenario_scheduled_task_manager') 
				|| !zenario_scheduled_task_manager::checkScheduledTaskRunning('jobDataProtectionCleanup')
			) {
				$box['tabs']['data_protection']['notices']['scheduled_task_not_running']['show'] = true;
			} else {
				$box['tabs']['data_protection']['notices']['scheduled_task_running']['show'] = true;
			}
			
			//Fill the backups tab
			if (file_exists($dirpath = ze::setting('backup_dir'))) {
				$backupsCount = 0;
				foreach (scandir($dirpath) as $i => $file) {
					if (is_file($dirpath. '/'. $file) && substr($file, 0, 1) != '.') {
						$backupsCount++;
					}
				}
				$fields['backup/manual_backups']['snippet']['html'] .= '<p>' . ze\admin::nphrase('There is 1 manual backup.', 'There are [[count]] manual backups.', $backupsCount) . '</p>';
			} else {
				$fields['backup/manual_backups']['snippet']['html'] .= '<p>' . ze\admin::phrase('The backups directory "[[path]]" could not be found.', ['path' => $dirpath]) . '</p>';
			}
			$link = ze\link::absolute() . 'zenario/admin/organizer.php#zenario__administration/panels/backups';
			$fields['backup/manual_backups']['snippet']['html'] .= '<p><a target="_blank" href="'.$link.'">Go to backups panel</a></p>';
			
			if (($path = ze::setting('automated_backup_log_path')) 
				&& is_file($path) 
				&& is_readable($path) 
				&& ($backup = ze\welcome::lastAutomatedBackup())
			) {
				$fields['backup/automated_backups']['snippet']['html'] .= '<p>' . ze\admin::phrase(
					'The database "[[DBNAME]]" was last backed up on [[date]] on the server "[[server]]"	.', 
					[
						'DBNAME' => DBNAME, 
						'date' => ze\admin::formatDate($backup[0]), 
						'server' => $backup[1]
					]
				) . '</p>';
			} else {
				$fields['backup/automated_backups']['snippet']['html'] = '<p>' . ze\admin::phrase('None found.') . '</p>';
			}
			
		} elseif ($settingGroup == 'dirs') {
            $warnings = ze\welcome::getBackupWarningsWithoutHtmlLinks();
            if (!empty($warnings) && isset($warnings['show_warning'])) {
                if (isset($warnings['snippet']['html'])) {
                    $box['tabs']['automated_backups']['notices']['show_warning_message']['message']= $warnings['snippet']['html'];
                }
            } else {
                $box['tabs']['automated_backups']['notices']['show_warning_message']['show'] = false;
            }
		}
		$link = ze\link::absolute() . '/zenario/admin/organizer.php#zenario__administration/panels/site_settings//data_protection~.site_settings~tdata_protection~k{"id"%3A"data_protection"}';
		$fields['email/data_protection_link']['snippet']['html'] = ze\admin::phrase('See the <a target="_blank" href="[[link]]">data protection</a> panel for settings on how long to store sent email logs.', ['link' => htmlspecialchars($link)]);
		
		if ($box['setting_group'] == 'email' && !ze\module::isRunning('zenario_newsletter')) {
			$box['title'] = 'Editing email settings';
		}
		
		
		
		
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		
		if (isset($fields['errors/show_notices'])) {
			$fields['errors/show_notices']['value'] =
			$fields['errors/show_notices']['current_value'] = (error_reporting() & E_NOTICE) == E_NOTICE;
		}

		if (isset($fields['errors/show_strict'])) {
			$fields['errors/show_strict']['value'] =
			$fields['errors/show_strict']['current_value'] = (error_reporting() & E_STRICT) == E_STRICT;
		}

		if (isset($fields['errors/show_all'])) {
			$fields['errors/show_all']['value'] =
			$fields['errors/show_all']['current_value'] = ((error_reporting() | E_NOTICE | E_STRICT) & (E_ALL | E_NOTICE | E_STRICT)) == (E_ALL | E_NOTICE | E_STRICT);
		}

		if (isset($fields['cookie_domain/cookie_domain'])) {
			$fields['cookie_domain/cookie_domain']['value'] =
			$fields['cookie_domain/cookie_domain']['current_value'] = COOKIE_DOMAIN;
		}

		if (isset($fields['cookie_timeouts/cookie_timeout'])) {
			$fields['cookie_timeouts/cookie_timeout']['value'] =
			$fields['cookie_timeouts/cookie_timeout']['current_value'] = ze\phraseAdm::seconds(COOKIE_TIMEOUT);
		}

		if (isset($fields['mysql/debug_use_strict_mode']) && defined('DEBUG_USE_STRICT_MODE')) {
			$fields['mysql/debug_use_strict_mode']['value'] =
			$fields['mysql/debug_use_strict_mode']['current_value'] = DEBUG_USE_STRICT_MODE;
		}

		if (isset($fields['mysql/debug_send_email']) && defined('DEBUG_SEND_EMAIL')) {
			$fields['mysql/debug_send_email']['value'] =
			$fields['mysql/debug_send_email']['current_value'] = DEBUG_SEND_EMAIL;
		}

		if (isset($fields['mysql/email_address_global_support']) && defined('EMAIL_ADDRESS_GLOBAL_SUPPORT')) {
			$fields['mysql/email_address_global_support']['value'] =
			$fields['mysql/email_address_global_support']['current_value'] = EMAIL_ADDRESS_GLOBAL_SUPPORT;
		}

		if (isset($fields['dates/vis_date_format_short'])) {
			$fields['dates/vis_date_format_short__preview']['current_value'] =
				ze\admin::formatDate(ze\date::now(), $values['dates/vis_date_format_short'], true);
	
			$fields['dates/vis_date_format_med__preview']['current_value'] =
				ze\admin::formatDate(ze\date::now(), $values['dates/vis_date_format_med'], true);
	
			$fields['dates/vis_date_format_long__preview']['current_value'] =
				ze\admin::formatDate(ze\date::now(), $values['dates/vis_date_format_long'], true);
		}

		if (isset($fields['urls/mod_rewrite_enabled']) && $values['urls/mod_rewrite_enabled']) {
			$fields['urls/friendly_urls_disabled_warning']['hidden'] = true;
			$fields['sitemap/sitemap_enabled']['hidden'] = false;
			$fields['sitemap/sitemap_disabled_warning']['hidden'] = true;
			if (isset($fields['sitemap/sitemap_url'])) {
				if (!$fields['sitemap/sitemap_url']['hidden'] = !$values['sitemap/sitemap_enabled']) {
					if (ze::setting('mod_rewrite_enabled')) {
						$fields['sitemap/sitemap_url']['value'] =
						$fields['sitemap/sitemap_url']['current_value'] = ze\link::protocol() . ze\link::primaryDomain(). SUBDIRECTORY. 'sitemap.xml';
					} else {
						$fields['sitemap/sitemap_url']['value'] =
						$fields['sitemap/sitemap_url']['current_value'] = ze\link::protocol() . ze\link::primaryDomain(). SUBDIRECTORY. DIRECTORY_INDEX_FILENAME. '?method_call=showSitemap';
					}
				}
			}
		} elseif (isset($fields['urls/mod_rewrite_enabled']) && !$values['urls/mod_rewrite_enabled']) {
			$fields['urls/friendly_urls_disabled_warning']['hidden'] = false;
			$fields['sitemap/sitemap_enabled']['hidden'] = true;
			$fields['sitemap/sitemap_url']['hidden'] = true;
			$values['sitemap/sitemap_url'] = false;
			$fields['sitemap/sitemap_disabled_warning']['hidden'] = false;
		}
		
		if (isset($box['tabs']['caching'])) {
			
			ze\cache::cleanDirs();
			if (is_writable(CMS_ROOT. 'public/images')
			 && is_writable(CMS_ROOT. 'private/images')) {
				$values['caching/cache_images'] = 1;
				$fields['caching/cache_images']['note_below'] =
					ze\admin::phrase('The <code>public/images/</code> and <code>private/images/</code> directories are writable, Zenario will create cached copies of images and thumbnails in these directories.');
				$box['tabs']['caching']['notices']['img_dir_writable']['show'] = true;
				$box['tabs']['caching']['notices']['img_dir_not_writable']['show'] = false;
			} else {
				$values['caching/cache_images'] = '';
				$fields['caching/cache_images']['note_below'] =
					ze\admin::phrase('The <code>public/images/</code> and <code>private/images/</code> directories are not writable, images and thumbnails will not be cached.');
			}
	
			if (isset($box['tabs']['clear_cache']) && ze\priv::check('_PRIV_EDIT_SITE_SETTING')) {
				//Manually clear the cache
				if (!empty($box['tabs']['clear_cache']['fields']['clear_cache']['pressed'])) {
					
					ze\skinAdm::clearCache();
	
					$box['tabs']['clear_cache']['notices']['notice']['show'] = true;
				} else {
					$box['tabs']['clear_cache']['notices']['notice']['show'] = false;
				}
			}
		}
		
		if (isset($box['tabs']['antivirus'])) {
			$box['tabs']['antivirus']['notices']['error']['show'] =
			$box['tabs']['antivirus']['notices']['daemon']['show'] =
			$box['tabs']['antivirus']['notices']['success']['show'] = false;
			
			
			$output = [];
			$returnValue = 0;
			
			if (!empty($box['tabs']['antivirus']['fields']['test']['pressed'])) {
				
				$programPath = ze\server::programPathForExec($values['antivirus/clamscan_tool_path'], 'clamdscan', true);
				
				
				
				$output = [];
				$returnValue = 3;
				
				if ($programPath) {
					$version = exec(escapeshellarg($programPath) .' --version', $output, $returnValue);
					
					if (strpos($version, 'ClamAV') !== false) {
						$filepath = CMS_ROOT. ze::moduleDir('zenario_common_features', 'fun/test_files/test.pdf');
						//$filepath = tempnam(sys_get_temp_dir(), 'testscan');
						//file_put_contents($filepath, 'Hello world!');
						//@chmod($filepath, 0644);
						
						exec(escapeshellarg($programPath) . ' --quiet ' . escapeshellarg($filepath), $output, $returnValue);
						
						//@chmod($filepath, 0600);					
					}
				}
				
				if ($returnValue == 0) {
					$box['tabs']['antivirus']['notices']['success']['show'] = true;
				
				} elseif ($returnValue == 2) {
					$box['tabs']['antivirus']['notices']['daemon']['show'] = true;
				
				} else {
					$box['tabs']['antivirus']['notices']['error']['show'] = true;
				}
			}
		}

		if (isset($box['tabs']['antiword'])) {
			$box['tabs']['antiword']['notices']['error']['show'] =
			$box['tabs']['antiword']['notices']['success']['show'] = false;
	
			if (!empty($fields['antiword/test']['pressed'])) {
				$extract = '';
				ze\site::setSetting('antiword_path', $values['antiword/antiword_path'], $updateDB = false);
		
				if ((ze\file::plainTextExtract(ze::moduleDir('zenario_common_features', 'fun/test_files/test.doc'), $extract))
				 && ($extract == 'Test')) {
					$box['tabs']['antiword']['notices']['success']['show'] = true;
				} else {
					$box['tabs']['antiword']['notices']['error']['show'] = true;
				}
			}
		}

		if (isset($box['tabs']['pdftotext'])) {
			$box['tabs']['pdftotext']['notices']['error']['show'] =
			$box['tabs']['pdftotext']['notices']['success']['show'] = false;
	
			if (!empty($fields['pdftotext/test']['pressed'])) {
				$extract = '';
				ze\site::setSetting('pdftotext_path', $values['pdftotext/pdftotext_path'], $updateDB = false);
		
				if ((ze\file::plainTextExtract(ze::moduleDir('zenario_common_features', 'fun/test_files/test.pdf'), $extract))
				 && ($extract == 'Test')) {
					$box['tabs']['pdftotext']['notices']['success']['show'] = true;
				} else {
					$box['tabs']['pdftotext']['notices']['error']['show'] = true;
				}
			}
		}

		if (isset($box['tabs']['ghostscript'])) {
			$box['tabs']['ghostscript']['notices']['error']['show'] =
			$box['tabs']['ghostscript']['notices']['success']['show'] = false;

			if (!empty($fields['ghostscript/test']['pressed'])) {
				$extract = '';
				ze\site::setSetting('ghostscript_path', $values['ghostscript/ghostscript_path'], $updateDB = false);

				if (ze\file::createPpdfFirstPageScreenshotPng(ze::moduleDir('zenario_common_features', 'fun/test_files/test.pdf'))) {
					$box['tabs']['ghostscript']['notices']['success']['show'] = true;
				} else {
					$box['tabs']['ghostscript']['notices']['error']['show'] = true;
				}
			}
		}

		if (isset($box['tabs']['mysql'])) {
			$box['tabs']['mysql']['notices']['error']['show'] =
			$box['tabs']['mysql']['notices']['success']['show'] = false;

			if (!empty($fields['mysql/test']['pressed'])) {
				ze\site::setSetting('mysql_path', $values['mysql/mysql_path'], $updateDB = false);
				
				if (ze\dbAdm::testMySQL(false)) {
					$box['tabs']['mysql']['notices']['success']['show'] = true;
				} else {
					$box['tabs']['mysql']['notices']['error']['show'] = true;
				}
			}
			
			$box['tabs']['mysql']['notices']['error2']['show'] =
			$box['tabs']['mysql']['notices']['success2']['show'] = false;

			if (!empty($fields['mysql/test2']['pressed'])) {
				ze\site::setSetting('mysqldump_path', $values['mysql/mysqldump_path'], $updateDB = false);
				
				if (ze\dbAdm::testMySQL(true)) {
					$box['tabs']['mysql']['notices']['success2']['show'] = true;
				} else {
					$box['tabs']['mysql']['notices']['error2']['show'] = true;
				}
			}
			
			if ($settingGroup == 'external_programs') {
				foreach ([
					'advpng' => 'png',
					'jpegoptim' => 'jpeg',
					'jpegtran' => 'jpeg',
					'optipng' => 'png',
					'pngcrush' => 'png',
					'pngquant' => 'png'
				] as $program => $tab) {
					$box['tabs'][$tab]['notices']['error_'. $program]['show'] =
					$box['tabs'][$tab]['notices']['success_'. $program]['show'] = false;
					if (!empty($fields[$tab. '/test_'. $program]['pressed'])) {
				
						if (ze\server::programPathForExec($values[$tab. '/'. $program. '_path'], $program, true)) {
							$box['tabs'][$tab]['notices']['success_'. $program]['show'] = true;
						} else {
							$box['tabs'][$tab]['notices']['error_'. $program]['show'] = true;
						}
					}
				}
			}
		}
		
		if (isset($box['tabs']['wkhtmltopdf'])) {
			$box['tabs']['wkhtmltopdf']['notices']['error']['show'] =
			$box['tabs']['wkhtmltopdf']['notices']['success']['show'] = false;

			if ($values['wkhtmltopdf/wkhtmltopdf_path'] && $values['wkhtmltopdf/wkhtmltopdf_path'] == 'PATH') {
				$fields['wkhtmltopdf/wkhtmltopdf_path']['note_below'] = ze\admin::phrase('Warning: If you use this program in a scheduled task, it may not be found because the PATH environment variable may be different when called via cron.');
			} else {
				unset($fields['wkhtmltopdf/wkhtmltopdf_path']['note_below']);
			}
			
			if (!empty($box['tabs']['wkhtmltopdf']['fields']['test']['pressed'])) {
				if (($programPath = ze\server::programPathForExec($values['wkhtmltopdf/wkhtmltopdf_path'], 'wkhtmltopdf'))
				 && ($rv = exec(escapeshellarg($programPath) .' --version'))) {
					$box['tabs']['wkhtmltopdf']['notices']['success']['show'] = true;
				} else {
					$box['tabs']['wkhtmltopdf']['notices']['error']['show'] = true;
				}
			}
		}
		
		if (isset($fields['automated_backups/test'])
		 && $values['automated_backups/check_automated_backups']
		 && (!isset($fields['automated_backups/test']['note_below'])
		  || !empty($fields['automated_backups/test']['pressed']))) {
			
			$fields['automated_backups/test']['note_below'] = '';
			
			
			
			if (($automated_backup_log_path = $values['automated_backups/automated_backup_log_path'])
			 && (is_file($automated_backup_log_path))
			 && (is_readable($automated_backup_log_path))) {
				
				$timestamp = ze\welcome::lastAutomatedBackupTimestamp($automated_backup_log_path);
				
				if (!$timestamp) {
					$mrg = [
						'DBNAME' => DBNAME,
						'path' => $automated_backup_log_path
					];
					$fields['automated_backups/test']['note_below'] = htmlspecialchars(
						ze\admin::phrase('The database "[[DBNAME]]" was not listed in [[path]]', $mrg)
					);
			
				} else {
					$mrg = [
						'DBNAME' => DBNAME,
						'datetime' => ze\admin::formatDateTime($timestamp, false, true)
					];
					$fields['automated_backups/test']['note_below'] = htmlspecialchars(
						ze\admin::phrase('The database "[[DBNAME]]" was last backed up on [[datetime]]', $mrg)
					);
				}
			}
		}

		if (isset($fields['test/test_send_button'])) {
	
			$box['tabs']['test']['notices']['test_send_error']['show'] = false;
			$box['tabs']['test']['notices']['test_send_sucesses']['show'] = false;
			
			$mrg = ['absCMSDirURL' => ze\link::absolute()];
			$values['test/test_send_message'] = ze\admin::phrase('<p>Your email appears to be working.</p><p>This is a test email sent by an administrator at [[absCMSDirURL]].</p>', $mrg);
			$values['test/test_send_from_address'] = $values['email/email_address_from'];
			$values['test/test_send_subject'] = ze\admin::phrase('A test email from [[absCMSDirURL]]', $mrg);
	
			if (ze\ray::engToBooleanArray($fields['test/test_send_button'], 'pressed')) {
				$box['tabs']['test']['notices']['test_send']['show'] = true;
		
				$error = '';
				$success = '';
				if (!$email = trim($values['test/test_send_email_address'])) {
					$error = ze\admin::phrase('Please enter an email address.');
		
				} elseif (!ze\ring::validateEmailAddress($email)) {
					$error = ze\admin::phrase('"[[email]]" is not a valid email address.', ['email' => $email]);
		
				} else {
					//Temporarily switch the site settings to the current values
					$settingVals = [];
					$settings = [
						'email_address_from' => 'email',
						'base64_encode_emails' => 'smtp',
						'smtp_host' => 'smtp',
						'smtp_port' => 'smtp',
						'smtp_security' => 'smtp',
						'smtp_specify_server' => 'smtp',
						'smtp_use_auth' => 'smtp',
						'smtp_username' => 'smtp'
					];
					foreach($settings as $name => $onTab) {
						$settingVals[$name] = ze::setting($name);
						ze\site::setSetting($name, $values[$onTab. '/'. $name], $updateDB = false);
					}
					
					$secretSettingVals = [];
					$settings = [
						'smtp_password' => 'smtp'
					];
					foreach($settings as $name => $onTab) {
						$secretSettingVals[$name] = ze::secretSetting($name);
						ze\site::setSecretSetting($name, $values[$onTab. '/'. $name], $updateDB = false);
					}
			
					try {
						$subject = $values['test/test_send_subject'];
						$body = $values['test/test_send_message'];
						$addressToOverriddenBy = false;
						
						if ($values['test/test_send_format'] == "standard_email_template") {
							ze\module::inc('zenario_email_template_manager');
							zenario_email_template_manager::putBodyInTemplate($body);
						}
						
						$result = ze\server::sendEmail(
							$subject, $body,
							$email,
							$addressToOverriddenBy,
							$nameTo = false,
							$addressFrom = false,
							$nameFrom = false,
							$attachments = false,
							$attachmentFilenameMappings = false,
							$precedence = false,
							$isHTML = true, 
							$exceptions = true);
				
						if ($result) {
							$success = ze\admin::phrase('Test email sent to "[[email]]".', ['email' => $email]);
						} else {
							$error = ze\admin::phrase('An email could not be sent to "[[email]]".', ['email' => $email]);
						}
					} catch (Exception $e) {
						$error = $e->getMessage();
					}
			
					//Switch the site settings back
					foreach($settingVals as $name => $value) {
						ze\site::setSetting($name, $value, $updateDB = false);
					}
					foreach($secretSettingVals as $name => $value) {
						ze\site::setSecretSetting($name, $value, $updateDB = false);
					}
				}
		
				if ($error) {
					$box['tabs']['test']['notices']['test_send_error']['show'] = true;
					$box['tabs']['test']['notices']['test_send_error']['message'] = $error;
				}
				if ($success) {
					$box['tabs']['test']['notices']['test_send_sucesses']['show'] = true;
					$box['tabs']['test']['notices']['test_send_sucesses']['message'] = $success;
				}
			}
		}
		
	}


	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		if (isset($fields['admin_domain/admin_domain'])) {
			if ($values['admin_domain/admin_domain'] != 'none'
			 && $values['admin_domain/admin_domain'] != $_SERVER['HTTP_HOST']
			 && $values['admin_domain/admin_domain'] != ze::setting('admin_domain')) {
				$box['tabs']['admin_domain']['errors'][] = ze\admin::phrase('Please select a domain name.');
			}
		}

		$path = 'zenario/has_database_changed_and_is_cache_out_of_date.php';
		$post = true;
		if (isset($fields['primary_domain/primary_domain'])) {
			if ($values['primary_domain/primary_domain'] == 'new') {
				if ($values['primary_domain/new']) {
					if ($thisDomainCheck = ze\curl::fetch(ze\link::absolute(). $path, $post)) {
						if ($specifiedDomainCheck = ze\curl::fetch(($cookieFreeDomain = ze\link::protocol(). $values['primary_domain/new']. SUBDIRECTORY). $path, $post)) {
							if ($thisDomainCheck == $specifiedDomainCheck) {
								//Success, looks correct
							} else {
								$box['tabs']['primary_domain']['errors'][] = ze\admin::phrase('We tried to check "[[domain]]", but it does not point to this site. Please check your domain and try again.', ['domain' => $cookieFreeDomain]);
							}
						} else {
							$box['tabs']['primary_domain']['errors'][] = ze\admin::phrase('We tried to check "[[domain]]", but it does not point to this site. Please check your domain and try again.', ['domain' => $cookieFreeDomain]);
						}
					}
				} else {
					$box['tabs']['primary_domain']['errors'][] = ze\admin::phrase('Please enter a primary domain.');
				}
			}
		}

		if (isset($fields['cookie_free_domain/cookie_free_domain'])) {
			if ($values['cookie_free_domain/use_cookie_free_domain']) {
				if ($values['cookie_free_domain/cookie_free_domain']) {
					if ($values['cookie_free_domain/cookie_free_domain'] != ze\link::adminDomain()
					 && $values['cookie_free_domain/cookie_free_domain'] != ze\link::primaryDomain()) {
						if ($thisDomainCheck = ze\curl::fetch(ze\link::absolute(). $path, $post)) {
							if ($specifiedDomainCheck = ze\curl::fetch(($cookieFreeDomain = ze\link::protocol(). $values['cookie_free_domain/cookie_free_domain']. SUBDIRECTORY). $path, $post)) {
								if ($thisDomainCheck == $specifiedDomainCheck) {
									//Success, looks correct
								} else {
									$box['tabs']['cookie_free_domain']['errors'][] = ze\admin::phrase('We tried to check "[[domain]]", but it does not point to this site. Please check your domain and try again.', ['domain' => $cookieFreeDomain]);
								}
							} else {
								$box['tabs']['cookie_free_domain']['errors'][] = ze\admin::phrase('We tried to check "[[domain]]", but it does not point to this site. Please check your domain and try again.', ['domain' => $cookieFreeDomain]);
							}
						}
					} else {
						$box['tabs']['cookie_free_domain']['errors'][] = ze\admin::phrase('The cookie-free domain must be a different domain to the primary domain.');
					}
				} else {
					$box['tabs']['cookie_free_domain']['errors'][] = ze\admin::phrase('Please enter a cookie-free domain.');
				}
			}
		}

		if (isset($fields['image_resizing/jpeg_quality_limit'])) {
			if (!$values['image_resizing/jpeg_quality_limit']) {
				$box['tabs']['image_sizes']['errors'][] = ze\admin::phrase('Please enter a JPEG quality.');
	
			} elseif (!is_numeric($values['image_resizing/jpeg_quality_limit'])) {
				$box['tabs']['image_sizes']['errors'][] = ze\admin::phrase('The JPEG quality must be a number.');
	
			} else
			if ((int) $values['image_resizing/jpeg_quality_limit'] < 80
			 || (int) $values['image_resizing/jpeg_quality_limit'] > 100) {
				$box['tabs']['image_sizes']['errors'][] = ze\admin::phrase('The JPEG quality must be a number between 80 and 100.');
			}
		}
		if (isset($values['filesizes/content_max_filesize']) && $values['filesizes/content_max_filesize'] && ze\file::fileSizeBasedOnUnit($values['filesizes/content_max_filesize'],$values['filesizes/content_max_filesize_unit']) > ze\dbAdm::apacheMaxFilesize()) {
			$box['tabs']['filesizes']['errors'][] = ze\admin::phrase('The Maximum Content File Size value should be not more than the Largest Possible Upload Size value.');
		}
		if (!empty($values['smtp/smtp_specify_server'])) {
			if (empty($values['smtp/smtp_host'])) {
				$box['tabs']['smtp']['errors'][] = ze\admin::phrase('Please enter a Server Name.');
			}
			if (empty($values['smtp/smtp_port'])) {
				$box['tabs']['smtp']['errors'][] = ze\admin::phrase('Please enter a Port number.');
			}
	
			if (!empty($values['smtp/smtp_use_auth'])) {
				if (empty($values['smtp/smtp_username'])) {
					$box['tabs']['smtp']['errors'][] = ze\admin::phrase('Please enter a Username.');
				}
				if (empty($values['smtp/smtp_password'])) {
					$box['tabs']['smtp']['errors'][] = ze\admin::phrase('Please enter a Password.');
				}
			}
		}

		if (isset($box['tabs']['automated_backups']['fields']['check_automated_backups'])
		 && ze\ring::engToBoolean($box['tabs']['automated_backups']['edit_mode']['on'] ?? false)
		 && $values['automated_backups/check_automated_backups']) {
			if (!$values['automated_backups/automated_backup_log_path']) {
				//Allow no path to be entered; this is actually the default state.
				//This will cause a warning on the diagnostics screen.
				//$box['tabs']['automated_backups']['errors'][] = ze\admin::phrase('Please enter a file path.');
	
			} elseif (!is_file($values['automated_backups/automated_backup_log_path'])) {
				$box['tabs']['automated_backups']['errors'][] = ze\admin::phrase('This file does not exist.');
	
			} elseif (!is_readable($values['automated_backups/automated_backup_log_path'])) {
				$box['tabs']['automated_backups']['errors'][] = ze\admin::phrase('This file is not readable.');
	
			} elseif (false !== ze\ring::chopPrefix(realpath(CMS_ROOT), realpath($values['automated_backups/automated_backup_log_path']))) {
				$box['tabs']['automated_backups']['errors'][] = ze\admin::phrase('Zenario is installed in this directory. Please choose a different path.');
			}
		}

		foreach (['backup_dir', 'docstore_dir'] as $dir) {
			if ($saving
			 && isset($box['tabs'][$dir]['fields'][$dir])
			 && ze\ring::engToBoolean($box['tabs'][$dir]['edit_mode']['on'] ?? false)) {
				if (!$values[$dir. '/'. $dir]) {
					$box['tabs'][$dir]['errors'][] = ze\admin::phrase('Please enter a directory.');
		
				} elseif (!is_dir($values[$dir. '/'. $dir])) {
					$box['tabs'][$dir]['errors'][] = ze\admin::phrase('This directory does not exist.');
		
				} elseif (false !== ze\ring::chopPrefix(realpath(CMS_ROOT), realpath($values[$dir. '/'. $dir]))) {
					$box['tabs'][$dir]['errors'][] = ze\admin::phrase('Zenario is installed in this directory. Please choose a different directory.');
		
				} else {
					//Strip any trailing slashes off of a directory path
					$box['tabs'][$dir]['fields'][$dir]['current_value'] = preg_replace('/[\\\\\\/]+$/', '', $box['tabs'][$dir]['fields'][$dir]['current_value']);
				}
			}
		}
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (!ze\priv::check('_PRIV_EDIT_SITE_SETTING')) {
			exit;
		}

		$changesToFiles = false;

		//Loop through each field that would be in the Admin Box, and has the <site_setting> tag set
		foreach ($box['tabs'] as $tabName => &$tab) {
	
			$recreateCustomThumbnailTwos = $recreateCustomThumbnailOnes = false;
			$jpegOnly = true;
	
			if (is_array($tab)
			 && !empty($tab['fields'])
			 && is_array($tab['fields'])
			 && ze\ring::engToBoolean($tab['edit_mode']['on'] ?? false)) {
				
				foreach ($tab['fields'] as $fieldName => &$field) {
					if (is_array($field)) {
						if (empty($field['readonly'])
						 && empty($field['read_only'])
						 && ($setting = $field['site_setting']['name'] ?? false)) {
							
							$isSecret = !empty($field['site_setting']['secret']);
							
							//Get the value of the setting. Hidden fields should count as being empty
							if (ze\ring::engToBoolean($field['hidden'] ?? false)
							 || ze\ring::engToBoolean($field['_was_hidden_before'] ?? false)) {
								$value = '';
							} else {
								$value = ze\ray::value($values, $tabName. '/'. $fieldName);
							}
					
							//Setting the primary or admin domain to "none" should count as being empty
							if ($setting == 'admin_domain' || $setting == 'primary_domain') {
								if ($value == 'none') {
									$value = '';
								} elseif ($value == 'new') {
									$value = $values['primary_domain/new'];
								}
							}
					
							//On multisite sites, don't allow local Admins to change the directory paths
							if (ze::in($setting, 'backup_dir', 'docstore_dir') && ze\db::hasGlobal() && !($_SESSION['admin_global_id'] ?? false)) {
								continue;
							}
					
							//Working copy images store a number for enabled. But the UI is a checkbox for enabled, and then a number if enabled.
							//Convert the format back when saving
							if (($setting == 'custom_thumbnail_1_width' && empty($values['image_sizes/custom_thumbnail_1']))
							 || ($setting == 'custom_thumbnail_1_height' && empty($values['image_sizes/custom_thumbnail_1']))
							 || ($setting == 'custom_thumbnail_2_width' && empty($values['image_sizes/custom_thumbnail_1']) && empty($values['image_sizes/custom_thumbnail_2']))
							 || ($setting == 'custom_thumbnail_2_height' && empty($values['image_sizes/custom_thumbnail_1']) && empty($values['image_sizes/custom_thumbnail_2']))) {
								$value = '';
							}
					
							//Handle file pickers
							if (!empty($field['upload'])) {
								if ($filepath = ze\file::getPathOfUploadInCacheDir($value)) {
									$value = ze\file::addToDatabase('site_setting', $filepath);
								}
								$changesToFiles = true;
							}
					
							if ($perm = ze\ring::chopPrefix('perm.', $setting)) {
								$settingChanged = $value != ze\user::permSetting($perm);
								
								if ($settingChanged) {
									ze\row::set('user_perm_settings', ['value' => $value], $perm);
								}
							
							} else {
								if ($isSecret) {
									$settingChanged = $value != ze::secretSetting($setting);
								} else {
									$settingChanged = $value != ze::setting($setting);
								}
					
								if ($settingChanged) {
									ze\site::setSetting($setting, $value,
										true, ze\ring::engToBoolean($field['site_setting']['encrypt'] ?? false),
										true, ze\ring::engToBoolean($field['site_setting']['secret'] ?? false));
								
									//Handle changing the default language of the site
									if ($setting == 'default_language') {
										ze::$defaultLang = $value;
									
										//Update the special pages, creating new ones if needed
										ze\contentAdm::addNeededSpecialPages();
							
										//Resync every content equivalence, trying to make sure that the pages for the new default language are used as the base
										$sql = "
											SELECT DISTINCT equiv_id, type
											FROM ". DB_PREFIX. "content_items
											WHERE status NOT IN ('trashed','deleted')";
										$equivResult = ze\sql::select($sql);
										while ($equiv = ze\sql::fetchAssoc($equivResult)) {
											ze\contentAdm::resyncEquivalence($equiv['equiv_id'], $equiv['type']);
										}
						
									} elseif ($setting == 'custom_thumbnail_1_width' || $setting == 'custom_thumbnail_1_height') {
										$recreateCustomThumbnailOnes = true;
										$jpegOnly = false;
						
									} elseif ($setting == 'custom_thumbnail_2_width' || $setting == 'custom_thumbnail_2_height') {
										$recreateCustomThumbnailTwos = true;
										$jpegOnly = false;
									}
								}
							}
						}
					}
				}
			}
	
			if ($recreateCustomThumbnailOnes || $recreateCustomThumbnailTwos) {
				ze\contentAdm::rerenderWorkingCopyImages($recreateCustomThumbnailOnes, $recreateCustomThumbnailTwos, true, $jpegOnly);
			}
		}

		//Tidy up any files in the database 
		if ($changesToFiles) {
			$sql = "
				SELECT f.id
				FROM ". DB_PREFIX. "files AS f
				LEFT JOIN ". DB_PREFIX. "site_settings AS s
				   ON s.value = f.id
				  AND s.value = CONCAT('', f.id)
				WHERE f.`usage` = 'site_setting'
				  AND s.name IS NULL";
	
			$result = ze\sql::select($sql);
			while ($file = ze\sql::fetchAssoc($result)) {
				ze\file::delete($file['id']);
			}
		}
		
		//Save phrases for cookie messages
		if (isset($fields['cookies/cookie_require_consent'])) {
			
			switch ($values['cookies/cookie_require_consent']) {
				case 'implied':
					$this->savePhrase(true, '_COOKIE_CONSENT_IMPLIED_MESSAGE', $values['cookies/_COOKIE_CONSENT_IMPLIED_MESSAGE']);
					$this->savePhrase(false, '_COOKIE_CONSENT_CONTINUE', $values['cookies/_COOKIE_CONSENT_CONTINUE']);
					break;
			
				case 'explicit':
					$this->savePhrase(true, '_COOKIE_CONSENT_MESSAGE', $values['cookies/_COOKIE_CONSENT_MESSAGE']);
					$this->savePhrase(false, '_COOKIE_CONSENT_ACCEPT', $values['cookies/_COOKIE_CONSENT_ACCEPT']);
					$this->savePhrase(false, '_COOKIE_CONSENT_CLOSE', $values['cookies/_COOKIE_CONSENT_CLOSE']);
					
					if ($values['cookies/cookie_consent_type__explicit'] == 'message_accept_reject') {
						$this->savePhrase(false, '_COOKIE_CONSENT_REJECT', $values['cookies/_COOKIE_CONSENT_REJECT']);
					}
					
					if ($values['cookies/individual_cookie_consent']) {
						$this->savePhrase(false, '_COOKIE_CONSENT_MANAGE', $values['cookies/_COOKIE_CONSENT_MANAGE']);
					}
					break;
			}
		}
	}
	
	
	
	protected function loadPhrase($keepHTML, $code) {
		
		$text = ze\row::get('visitor_phrases',
			'local_text',
			['module_class_name' => 'zenario_common_features', 'language_id' => ze::$defaultLang, 'code' => $code]
		);
		
		if (!$keepHTML) {
			$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);
		}
		
		return $text;
	}
	protected function savePhrase($isHTML, $code, $text) {
		
		if (!$isHTML) {
			$text = htmlspecialchars($text);
		}
		
		ze\row::set('visitor_phrases', 
			['local_text' => $text],
			['module_class_name' => 'zenario_common_features', 'language_id' => ze::$defaultLang, 'code' => $code]
		);
	}
}
