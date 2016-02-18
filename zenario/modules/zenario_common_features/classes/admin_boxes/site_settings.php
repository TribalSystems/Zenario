<?php
/*
 * Copyright (c) 2016, Tribal Limited
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


class zenario_common_features__admin_boxes__site_settings extends module_base_class {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if (is_array(arrayKey($box,'tabs'))) {
			foreach ($box['tabs'] as $tabName => &$tab) {
				if (is_array($tab)) {
					foreach ($tab['fields'] as &$field) {
						if ($setting = arrayKey($field, 'site_setting', 'name')) {
							$field['value'] = setting($setting);
					
							if ($setting == 'default_template_family') {
								$field['values'] = array($field['value'] => $field['value']);
					
							} elseif ($setting == 'admin_domain' || $setting == 'primary_domain') {
								$field['value'] = ifNull($field['value'], 'none');
							}
						}
					}
				}
			}
		}

		if (isset($box['tabs']['email']['fields']['email_address_system'])) {
			$box['tabs']['email']['fields']['email_address_system']['value'] = EMAIL_ADDRESS_GLOBAL_SUPPORT;
		}

		if (isset($box['tabs']['filesizes']['fields']['apache_max_filesize'])) {
			$box['tabs']['filesizes']['fields']['apache_max_filesize']['value'] = apacheMaxFilesize();
		}

		if (isset($box['tabs']['filesizes']['fields']['max_allowed_packet'])) {
			$box['tabs']['filesizes']['fields']['max_allowed_packet']['value'] = '?';
	
			if ($result = @sqlSelect("SHOW VARIABLES LIKE 'max_allowed_packet'")) {
				$settings = array();
				if ($row = sqlFetchRow($result)) {
					$box['tabs']['filesizes']['fields']['max_allowed_packet']['value'] = $row[1];
				}
			}
		}

		if (isset($box['tabs']['default_language']['fields']['default_language'])) {
			getLanguageSelectListOptions($box['tabs']['default_language']['fields']['default_language']);
		}

		if (isset($box['tabs']['urls']['fields']['mod_rewrite_suffix'])) {
			$box['tabs']['urls']['fields']['mod_rewrite_suffix']['values'] = array('.htm' => '.htm', '.html' => '.html');
	
			//Hide/show different options and notes, depending on whether language specific domains are
			//used, and whether every language has a language specific URL
			if (getNumLanguages() > 1) {
		
				$langSpecificDomainsUsed = checkRowExists('languages', array('domain' => array('!' => '')));
				$langSpecificDomainsNotUsed = checkRowExists('languages', array('domain' => ''));
		
				if ($langSpecificDomainsUsed) {
					if ($langSpecificDomainsNotUsed) {
						$box['tabs']['urls']['fields']['note_d']['hidden'] = true;
					} else {
						$box['tabs']['urls']['fields']['translations_hide_language_code']['hidden'] =
						$box['tabs']['urls']['fields']['note_a']['hidden'] =
						$box['tabs']['urls']['fields']['note_b']['hidden'] =
						$box['tabs']['urls']['fields']['note_c']['hidden'] = true;
					}
				} else {
					$box['tabs']['urls']['fields']['note_c']['hidden'] =
					$box['tabs']['urls']['fields']['note_d']['hidden'] = true;
				}
			} else {
				$box['tabs']['urls']['fields']['note_c']['hidden'] =
				$box['tabs']['urls']['fields']['note_d']['hidden'] = true;
			}
		}

		if (isset($box['tabs']['dates']['fields']['vis_date_format_short'])) {
			formatDateFormatSelectList($box['tabs']['dates']['fields']['vis_date_format_short'], true);
			formatDateFormatSelectList($box['tabs']['dates']['fields']['vis_date_format_med']);
			formatDateFormatSelectList($box['tabs']['dates']['fields']['vis_date_format_long']);
			formatDateFormatSelectList($box['tabs']['dates']['fields']['vis_date_format_datepicker'], true, true);
			formatDateFormatSelectList($box['tabs']['dates']['fields']['organizer_date_format'], true, true);
		}

		foreach (array('admin_domain', 'primary_domain') as $domainSetting) {
			if (isset($box['tabs'][$domainSetting]['fields'][$domainSetting])) {
				if (setting($domainSetting)) {
					$box['tabs'][$domainSetting]['fields'][$domainSetting]['values'][setting($domainSetting)] = array('ord' => 2, 'label' => 'http:// or https://'. setting($domainSetting));
				}
				if ($_SERVER['HTTP_HOST'] != setting($domainSetting)) {
					$box['tabs'][$domainSetting]['fields'][$domainSetting]['values'][$_SERVER['HTTP_HOST']] = array('ord' => 3, 'label' => 'http:// or https://'. $_SERVER['HTTP_HOST']);
				}
			}
		}


		if (isset($box['tabs']['speed']['fields']['have_query_cache'])) {
			if ($result = @sqlSelect("SHOW VARIABLES LIKE '%query_cache%'")) {
				$settings = array();
				while ($row = sqlFetchRow($result)) {
					$settings[$row[0]] = $row[1];
				}
		
				if (!$box['tabs']['speed']['fields']['query_cache_size']['hidden'] = !(
					$box['tabs']['speed']['fields']['have_query_cache']['value'] =
					$box['tabs']['speed']['fields']['have_query_cache']['current_value'] =
						engToBooleanArray($settings, 'have_query_cache') && engToBooleanArray($settings, 'query_cache_type')
				)) {
					$box['tabs']['speed']['fields']['query_cache_size']['value'] =
					$box['tabs']['speed']['fields']['query_cache_size']['current_value'] = formatFilesizeNicely((int) arrayKey($settings, 'query_cache_size'), $precision = 1, $adminMode = true);
				}
	
			} else {
				$box['tabs']['speed']['fields']['have_query_cache']['post_field_html'] = ' '. adminPhrase('(Could not check)');
				$box['tabs']['speed']['fields']['query_cache_size']['hidden'] = true;
			}
		}

		if (isset($box['tabs']['test']['fields']['test_send_email_address'])) {
			$adminDetails = getAdminDetails(adminId());
			$box['tabs']['test']['fields']['test_send_email_address']['value'] = $adminDetails['admin_email'];
		}

		//Working copy images store a number for enabled. But the UI is a checkbox for enabled, and then a number if enabled.
		//Convert the format when displaying the fields
		if (isset($box['tabs']['image_sizes']['fields']['thumbnail_wc'])) {
			if (setting('thumbnail_wc_image_size')) {
				$box['tabs']['image_sizes']['fields']['thumbnail_wc']['value'] = 1;
			} else {
				$box['tabs']['image_sizes']['fields']['thumbnail_wc']['value'] = '';
				$box['tabs']['image_sizes']['fields']['thumbnail_wc_image_size']['value'] = 300;
			}
		}
		if (isset($box['tabs']['image_sizes']['fields']['working_copy_image'])) {
			if (setting('working_copy_image_size')) {
				$box['tabs']['image_sizes']['fields']['working_copy_image']['value'] = 1;
			} else {
				$box['tabs']['image_sizes']['fields']['working_copy_image']['value'] = '';
				$box['tabs']['image_sizes']['fields']['working_copy_image_size']['value'] = 1000;
			}
		}

		if (isset($box['tabs']['security']['fields']['require_security_code_on_admin_login'])) {
			
			if (engToBoolean(siteDescription('require_security_code_on_admin_login'))) {
				$values['security/require_security_code_on_admin_login'] = 1;
				$values['security/security_code_by_ip'] = siteDescription('security_code_by_ip');
				$values['security/security_code_timeout'] = siteDescription('security_code_timeout');
			}
		}

		if (isset($box['tabs']['styles']['fields']['email_style_formats'])) {
	
			$yaml = array('email_style_formats' => siteDescription('email_style_formats'));
	
			if (empty($yaml['email_style_formats'])) {
				$yaml['email_style_formats'] = array();
			}
	
			require_once CMS_ROOT. 'zenario/libraries/mit/spyc/Spyc.php';
			$values['email_style_formats'] = Spyc::YAMLDump($yaml, 4);
		}

		//Set the value of the template directory
		if (isset($box['tabs']['template_dir']['fields']['template_dir'])) {
			$box['tabs']['template_dir']['fields']['template_dir']['value'] = CMS_ROOT. 'zenario_custom/templates/grid_templates';
		}

		//On multisite sites, don't allow local Admins to change the directory paths
		if (globalDBDefined() && !session('admin_global_id')) {
			foreach (array('backup_dir', 'docstore_dir') as $dir) {
				if (isset($box['tabs'][$dir]['edit_mode'])) {
					$box['tabs'][$dir]['edit_mode']['enabled'] = false;
				}
			}
		}

		//Hack to stop a buggy error message from appearing if the admin never opens the second tab on the branding FAB,
		//as the _was_hidden_before property was never set by the JavaScript on the client side.
		//Fix this problem by setting the _was_hidden_before property for the field.
		if (isset($box['tabs']['og']['fields']['organizer_favicon'])) {
			if ($values['og/organizer_favicon'] != 'custom') {
				$fields['og/custom_organizer_favicon']['_was_hidden_before'] = true;
			}
		}
		
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (isset($box['tabs']['debug']['fields']['debug_override_email_address'])) {
			$box['tabs']['debug']['fields']['debug_override_email_address']['hidden'] = !$values['debug/debug_override_enable'];
		}

		if (isset($box['tabs']['errors']['fields']['show_notices'])) {
			$box['tabs']['errors']['fields']['show_notices']['value'] =
			$box['tabs']['errors']['fields']['show_notices']['current_value'] = (error_reporting() & E_NOTICE) == E_NOTICE;
		}

		if (isset($box['tabs']['errors']['fields']['show_strict'])) {
			$box['tabs']['errors']['fields']['show_strict']['value'] =
			$box['tabs']['errors']['fields']['show_strict']['current_value'] = (error_reporting() & E_STRICT) == E_STRICT;
		}

		if (isset($box['tabs']['errors']['fields']['show_all'])) {
			$box['tabs']['errors']['fields']['show_all']['value'] =
			$box['tabs']['errors']['fields']['show_all']['current_value'] = ((error_reporting() | E_NOTICE | E_STRICT) & (E_ALL | E_NOTICE | E_STRICT)) == (E_ALL | E_NOTICE | E_STRICT);
		}

		if (isset($box['tabs']['cookie_domain']['fields']['cookie_domain'])) {
			$box['tabs']['cookie_domain']['fields']['cookie_domain']['value'] =
			$box['tabs']['cookie_domain']['fields']['cookie_domain']['current_value'] = COOKIE_DOMAIN;
		}

		if (isset($box['tabs']['cookie_timeouts']['fields']['cookie_timeout'])) {
			$box['tabs']['cookie_timeouts']['fields']['cookie_timeout']['value'] =
			$box['tabs']['cookie_timeouts']['fields']['cookie_timeout']['current_value'] = secondsToAdminPhrase(COOKIE_TIMEOUT);
		}

		if (isset($box['tabs']['mysql']['fields']['debug_use_strict_mode']) && defined('DEBUG_USE_STRICT_MODE')) {
			$box['tabs']['mysql']['fields']['debug_use_strict_mode']['value'] =
			$box['tabs']['mysql']['fields']['debug_use_strict_mode']['current_value'] = DEBUG_USE_STRICT_MODE;
		}

		if (isset($box['tabs']['mysql']['fields']['debug_send_email']) && defined('DEBUG_SEND_EMAIL')) {
			$box['tabs']['mysql']['fields']['debug_send_email']['value'] =
			$box['tabs']['mysql']['fields']['debug_send_email']['current_value'] = DEBUG_SEND_EMAIL;
		}

		if (isset($box['tabs']['mysql']['fields']['email_address_global_support']) && defined('EMAIL_ADDRESS_GLOBAL_SUPPORT')) {
			$box['tabs']['mysql']['fields']['email_address_global_support']['value'] =
			$box['tabs']['mysql']['fields']['email_address_global_support']['current_value'] = EMAIL_ADDRESS_GLOBAL_SUPPORT;
		}

		if (isset($box['tabs']['dates']['fields']['vis_date_format_short'])) {
			$box['tabs']['dates']['fields']['vis_date_format_short__preview']['current_value'] =
				formatDateNicely(now(), $values['dates/vis_date_format_short'], true);
	
			$box['tabs']['dates']['fields']['vis_date_format_med__preview']['current_value'] =
				formatDateNicely(now(), $values['dates/vis_date_format_med'], true);
	
			$box['tabs']['dates']['fields']['vis_date_format_long__preview']['current_value'] =
				formatDateNicely(now(), $values['dates/vis_date_format_long'], true);
		}

		if (isset($box['tabs']['sitemap']['fields']['sitemap_url'])) {
			if (!$box['tabs']['sitemap']['fields']['sitemap_url']['hidden'] = !$values['sitemap/sitemap_enabled']) {
				if (setting('mod_rewrite_enabled')) {
					$box['tabs']['sitemap']['fields']['sitemap_url']['value'] =
					$box['tabs']['sitemap']['fields']['sitemap_url']['current_value'] = httpOrhttps() . primaryDomain(). SUBDIRECTORY. 'sitemap.xml';
				} else {
					$box['tabs']['sitemap']['fields']['sitemap_url']['value'] =
					$box['tabs']['sitemap']['fields']['sitemap_url']['current_value'] = httpOrhttps() . primaryDomain(). SUBDIRECTORY. DIRECTORY_INDEX_FILENAME. '?method_call=showSitemap';
				}
			}
		}

		if (isset($box['tabs']['antiword'])) {
			$box['tabs']['antiword']['notices']['error']['show'] =
			$box['tabs']['antiword']['notices']['success']['show'] = false;
	
			if (!empty($box['tabs']['antiword']['fields']['test']['pressed'])) {
				$extract = '';
				setSetting('antiword_path', $values['antiword/antiword_path'], $updateDB = false);
		
				if ((plainTextExtract(moduleDir('zenario_common_features', 'fun/test_files/test.doc'), $extract))
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
	
			if (!empty($box['tabs']['pdftotext']['fields']['test']['pressed'])) {
				$extract = '';
				setSetting('pdftotext_path', $values['pdftotext/pdftotext_path'], $updateDB = false);
		
				if ((plainTextExtract(moduleDir('zenario_common_features', 'fun/test_files/test.pdf'), $extract))
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

			if (!empty($box['tabs']['ghostscript']['fields']['test']['pressed'])) {
				$extract = '';
				setSetting('ghostscript_path', $values['ghostscript/ghostscript_path'], $updateDB = false);

				if (createPpdfFirstPageScreenshotPng(moduleDir('zenario_common_features', 'fun/test_files/test.pdf'))) {
					$box['tabs']['ghostscript']['notices']['success']['show'] = true;
				} else {
					$box['tabs']['ghostscript']['notices']['error']['show'] = true;
				}
			}
		}

		if (isset($box['tabs']['test']['fields']['test_send_button'])) {
	
			$box['tabs']['test']['notices']['test_send_error']['show'] = false;
			$box['tabs']['test']['notices']['test_send_sucesses']['show'] = false;
	
			if (engToBooleanArray($box['tabs']['test']['fields']['test_send_button'], 'pressed')) {
				$box['tabs']['test']['notices']['test_send']['show'] = true;
		
				$error = '';
				$success = '';
				if (!$email = trim($values['test/test_send_email_address'])) {
					$error = adminPhrase('Please enter an email address.');
		
				} elseif (!validateEmailAddress($email)) {
					$error = adminPhrase('"[[email]]" is not a valid email address.', array('email' => $email));
		
				} else {
			
					$settings = array(
						'smtp_host' => '',
						'smtp_password' => '',
						'smtp_port' => '',
						'smtp_security' => '',
						'smtp_specify_server' => '',
						'smtp_use_auth' => '',
						'smtp_username' => '');
			
					//Temporarily switch the site settings to the current values
					foreach($settings as $name => &$value) {
						$setting[$name] = setting($name);
						setSetting($name, $values['smtp'. '/'. $name], $updateDB = false);
					}
			
					try {
						$subject = adminPhrase('A test email from [[HTTP_HOST]]', $_SERVER);
						$body = adminPhrase('<p>Your email appears to be working.</p><p>This is a test email sent by an administrator at [[HTTP_HOST]].</p>', $_SERVER);
						$addressToOverriddenBy = false;
						$result = sendEmail(
							$subject, $body,
							$email,
							$addressToOverriddenBy,
							$nameTo = false,
							$addressFrom = false,
							$nameFrom = false,
							false, false, false,
							$isHTML = true, 
							$exceptions = true);
				
						if ($result) {
							$success = adminPhrase('Test email sent to "[[email]]".', array('email' => $email));
						} else {
							$error = adminPhrase('An email could not be sent to "[[email]]".', array('email' => $email));
						}
					} catch (Exception $e) {
						$error = $e->getMessage();
					}
			
					//Switch the site settings back
					foreach($settings as $name => &$value) {
						setSetting($name, $setting[$name], $updateDB = false);
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
		if (isset($box['tabs']['admin_domain']['fields']['admin_domain'])) {
			if ($values['admin_domain/admin_domain'] != 'none'
			 && $values['admin_domain/admin_domain'] != $_SERVER['HTTP_HOST']
			 && $values['admin_domain/admin_domain'] != setting('admin_domain')) {
				$box['tabs']['admin_domain']['errors'][] = adminPhrase('Please select a domain name.');
			}
		}

		if (isset($box['tabs']['primary_domain']['fields']['primary_domain'])) {
			if ($values['primary_domain/primary_domain'] == 'new') {
				if ($values['primary_domain/new']) {
					$path = 'zenario/has_database_changed_and_is_cache_out_of_date.php';
					$post = true;
					if ($thisDomainCheck = curl(absCMSDirURL(). $path, $post)) {
						if ($specifiedDomainCheck = curl(($cookieFreeDomain = httpOrHttps(). $values['primary_domain/new']. SUBDIRECTORY). $path, $post)) {
							if ($thisDomainCheck == $specifiedDomainCheck) {
								//Success, looks correct
							} else {
								$box['tabs']['primary_domain']['errors'][] = adminPhrase('[[domain]] is pointing to a different site, or possibly an out-of-date copy of this site.', array('domain' => $cookieFreeDomain));
							}
						} else {
							$box['tabs']['primary_domain']['errors'][] = adminPhrase('A CURL request to [[domain]] failed. Either this is an invalid URL or Zenario is not at this location.', array('domain' => $cookieFreeDomain));
						}
					}
				} else {
					$box['tabs']['primary_domain']['errors'][] = adminPhrase('Please enter a primary domain.');
				}
			}
		}

		if (isset($box['tabs']['cookie_free_domain']['fields']['cookie_free_domain'])) {
			if ($values['cookie_free_domain/use_cookie_free_domain']) {
				if ($values['cookie_free_domain/cookie_free_domain']) {
					if ($values['cookie_free_domain/cookie_free_domain'] != adminDomain()
					 && $values['cookie_free_domain/cookie_free_domain'] != primaryDomain()) {
						$path = 'zenario/has_database_changed_and_is_cache_out_of_date.php';
						$post = true;
						if ($thisDomainCheck = curl(absCMSDirURL(). $path, $post)) {
							if ($specifiedDomainCheck = curl(($cookieFreeDomain = httpOrHttps(). $values['cookie_free_domain/cookie_free_domain']. SUBDIRECTORY). $path, $post)) {
								if ($thisDomainCheck == $specifiedDomainCheck) {
									//Success, looks correct
								} else {
									$box['tabs']['cookie_free_domain']['errors'][] = adminPhrase('[[domain]] is pointing to a different site, or possibly an out-of-date copy of this site.', array('domain' => $cookieFreeDomain));
								}
							} else {
								$box['tabs']['cookie_free_domain']['errors'][] = adminPhrase('A CURL request to [[domain]] failed. Either this is an invalid URL or Zenario is not at this location.', array('domain' => $cookieFreeDomain));
							}
						}
					} else {
						$box['tabs']['cookie_free_domain']['errors'][] = adminPhrase('The cookie-free domain must be a different domain to the primary domain.');
					}
				} else {
					$box['tabs']['cookie_free_domain']['errors'][] = adminPhrase('Please enter a cookie-free domain.');
				}
			}
		}

		if (isset($box['tabs']['image_sizes']['fields']['jpeg_quality'])) {
			if (!$values['image_sizes/jpeg_quality']) {
				$box['tabs']['image_sizes']['errors'][] = adminPhrase('Please enter a JPEG Quality.');
	
			} elseif (!is_numeric($values['image_sizes/jpeg_quality'])) {
				$box['tabs']['image_sizes']['errors'][] = adminPhrase('The JPEG Quality must be a number.');
	
			} else
			if ((int) $values['image_sizes/jpeg_quality'] < 1
			 || (int) $values['image_sizes/jpeg_quality'] > 100) {
				$box['tabs']['image_sizes']['errors'][] = adminPhrase('The JPEG Quality must be a number between 1 and 100.');
			}
		}

		if (!empty($values['smtp/smtp_specify_server'])) {
			if (empty($values['smtp/smtp_host'])) {
				$box['tabs']['smtp']['errors'][] = adminPhrase('Please enter a Server Name.');
			}
			if (empty($values['smtp/smtp_port'])) {
				$box['tabs']['smtp']['errors'][] = adminPhrase('Please enter a Port number.');
			}
	
			if (!empty($values['smtp/smtp_use_auth'])) {
				if (empty($values['smtp/smtp_username'])) {
					$box['tabs']['smtp']['errors'][] = adminPhrase('Please enter a Username.');
				}
				if (empty($values['smtp/smtp_password'])) {
					$box['tabs']['smtp']['errors'][] = adminPhrase('Please enter a Password.');
				}
			}
		}

		foreach (array('backup_dir', 'docstore_dir', 'template_dir') as $dir) {
			if ($saving
			 && isset($box['tabs'][$dir]['fields'][$dir])
			 && engToBooleanArray($box['tabs'][$dir], 'edit_mode', 'on')) {
				if (!$values[$dir. '/'. $dir]) {
					$box['tabs'][$dir]['errors'][] = adminPhrase('Please enter a directory.');
		
				} elseif (!is_dir($values[$dir. '/'. $dir])) {
					$box['tabs'][$dir]['errors'][] = adminPhrase('This directory does not exist.');
		
				} elseif (realpath($values[$dir. '/'. $dir]) == realpath(CMS_ROOT)) {
					$box['tabs'][$dir]['errors'][] = adminPhrase('The CMS is installed in this directory. Please choose a different directory.');
		
				} elseif (empty($values[$dir. '/template_dir_can_be_readonly']) && !is_writable($values[$dir. '/'. $dir])) {
					$box['tabs'][$dir]['errors'][] = adminPhrase('This directory is not writable.');
		
				} else {
					//Strip any trailing slashes off of a directory path
					$box['tabs'][$dir]['fields'][$dir]['current_value'] = preg_replace('/[\\\\\\/]+$/', '', $box['tabs'][$dir]['fields'][$dir]['current_value']);
				}
			}
		}
		
	}
	
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (!checkPriv('_PRIV_EDIT_SITE_SETTING')) {
			exit;
		}

		$changesToFiles = false;

		//Loop through each field that would be in the Admin Box, and has the <site_setting> tag set
		foreach ($box['tabs'] as $tabName => &$tab) {
	
			$workingCopyImages = $thumbnailWorkingCopyImages = false;
			$jpegOnly = true;
	
			if (is_array($tab) && engToBooleanArray($tab, 'edit_mode', 'on')) {
				foreach ($tab['fields'] as $fieldName => &$field) {
					if (is_array($field)) {
						if (!arrayKey($field, 'read_only') && $setting = arrayKey($field, 'site_setting', 'name')) {
					
							//Get the value of the setting. Hidden fields should count as being empty
							if (engToBooleanArray($field, 'hidden')
							 || engToBooleanArray($field, '_was_hidden_before')) {
								$value = '';
							} else {
								$value = arrayKey($values, $tabName. '/'. $fieldName);
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
							if (in($setting, 'backup_dir', 'docstore_dir') && globalDBDefined() && !session('admin_global_id')) {
								continue;
							}
					
							//Working copy images store a number for enabled. But the UI is a checkbox for enabled, and then a number if enabled.
							//Convert the format back when saving
							if (($setting == 'working_copy_image_size' && empty($values['image_sizes/working_copy_image']))
							 || ($setting == 'thumbnail_wc_image_size' && empty($values['image_sizes/thumbnail_wc']))) {
								$value = '';
							}
					
							//Handle file pickers
							if (!empty($field['upload'])) {
								if ($filepath = getPathOfUploadedFileInCacheDir($value)) {
									$value = addFileToDatabase('site_setting', $filepath);
								}
								$changesToFiles = true;
							}
					
							$settingChanged = $value != setting($setting);
							setSetting($setting, $value);
					
							if ($settingChanged) {
								//Handle changing the default language of the site
								if ($setting == 'default_language') {
									//Update the special pages, creating new ones if needed
									addNeededSpecialPages();
							
									//Resync every content equivalence, trying to make sure that the pages for the new default language are used as the base
									$sql = "
										SELECT DISTINCT equiv_id, type
										FROM ". DB_NAME_PREFIX. "content_items
										WHERE status NOT IN ('trashed','deleted')";
									$equivResult = sqlQuery($sql);
									while ($equiv = sqlFetchAssoc($equivResult)) {
										resyncEquivalence($equiv['equiv_id'], $equiv['type']);
									}
						
								} elseif ($setting == 'jpeg_quality') {
									$workingCopyImages = $thumbnailWorkingCopyImages = true;
						
								} elseif ($setting == 'thumbnail_wc_image_size') {
									$thumbnailWorkingCopyImages = true;
									$jpegOnly = false;
						
								} elseif ($setting == 'working_copy_image_size') {
									$workingCopyImages = true;
									$jpegOnly = false;
								}
							}
						}
					}
				}
			}
	
			if ($workingCopyImages || $thumbnailWorkingCopyImages) {
				rerenderWorkingCopyImages($workingCopyImages, $thumbnailWorkingCopyImages, true, $jpegOnly);
			}
		}

		//Tidy up any files in the database 
		if ($changesToFiles) {
			$sql = "
				SELECT f.id
				FROM ". DB_NAME_PREFIX. "files AS f
				LEFT JOIN ". DB_NAME_PREFIX. "site_settings AS s
				   ON s.value = f.id
				  AND s.value = CONCAT('', f.id)
				WHERE f.`usage` = 'site_setting'
				  AND s.name IS NULL";
	
			$result = sqlSelect($sql);
			while ($file = sqlFetchAssoc($result)) {
				deleteFile($file['id']);
			}
		}
		
	}
}
