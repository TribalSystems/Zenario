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


class zenario_common_features__admin_boxes__site_settings extends module_base_class {

	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if (is_array(arrayKey($box,'tabs'))) {
			foreach ($box['tabs'] as $tabName => &$tab) {
				if (!empty($tab['fields'])
				 && is_array($tab['fields'])) {
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

		if (isset($fields['email/email_address_system'])) {
			$fields['email/email_address_system']['value'] = EMAIL_ADDRESS_GLOBAL_SUPPORT;
		}

		if (isset($fields['filesizes/apache_max_filesize'])) {
			$fields['filesizes/apache_max_filesize']['value'] = apacheMaxFilesize();
		}

		if (isset($fields['filesizes/max_allowed_packet'])) {
			$fields['filesizes/max_allowed_packet']['value'] = '?';
	
			if ($result = @sqlSelect("SHOW VARIABLES LIKE 'max_allowed_packet'")) {
				$settings = array();
				if ($row = sqlFetchRow($result)) {
					$fields['filesizes/max_allowed_packet']['value'] = $row[1];
				}
			}
		}

		if (isset($fields['default_language/default_language'])) {
			getLanguageSelectListOptions($fields['default_language/default_language']);
		}

		if (isset($fields['urls/mod_rewrite_suffix'])) {
			$fields['urls/mod_rewrite_suffix']['values'] = array('.htm' => '.htm', '.html' => '.html');
	
			//Hide/show different options and notes, depending on whether language specific domains are
			//used, and whether every language has a language specific URL
			if (getNumLanguages() > 1) {
		
				$langSpecificDomainsUsed = checkRowExists('languages', array('domain' => array('!' => '')));
				$langSpecificDomainsNotUsed = checkRowExists('languages', array('domain' => ''));
		
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
			formatDateFormatSelectList($fields['dates/vis_date_format_short'], true);
			formatDateFormatSelectList($fields['dates/vis_date_format_med']);
			formatDateFormatSelectList($fields['dates/vis_date_format_long']);
			formatDateFormatSelectList($fields['dates/vis_date_format_datepicker'], true, true);
			formatDateFormatSelectList($fields['dates/organizer_date_format'], true, true);
		}

		foreach (array(
			'admin_domain' => 'Use [[domain]] as the admin domain; redirect all administrators to [[domain]]',
			'primary_domain' => 'Use [[domain]] as the primary domain; redirect all visitors to [[domain]]'
		) as $domainSetting => $phrase) {
			if (isset($box['tabs'][$domainSetting]['fields'][$domainSetting])) {
				if (setting($domainSetting)) {
					$box['tabs'][$domainSetting]['fields'][$domainSetting]['values'][setting($domainSetting)] =
						array('ord' => 2, 'label' => adminPhrase($phrase, array('domain' => setting($domainSetting))));
				}
				if ($_SERVER['HTTP_HOST'] != setting($domainSetting)) {
					$box['tabs'][$domainSetting]['fields'][$domainSetting]['values'][$_SERVER['HTTP_HOST']] =
						array('ord' => 3, 'label' => adminPhrase($phrase, array('domain' => $_SERVER['HTTP_HOST'])));
				}
			}
		}
		
		$path = 'zenario/has_database_changed_and_is_cache_out_of_date.php';
		$post = true;
		if (isset($fields['primary_domain/new']) && !(checkCURLEnabled() && ($thisDomainCheck = curl(absCMSDirURL(). $path, $post)))) {
			unset($fields['primary_domain/new']['note_below']);
		}
		if (isset($fields['cookie_free_domain/cookie_free_domain']) && !(checkCURLEnabled() && ($thisDomainCheck = curl(absCMSDirURL(). $path, $post)))) {
			unset($fields['cookie_free_domain/cookie_free_domain']['note_below']);
		}

		//Check whether compression is enabled on the server
		
		
		if (isset($fields['speed/compress_web_pages'])) {
			if (in_array('mod_deflate', apache_get_modules())) {
				$values['speed/compress_web_pages'] = 1;
				$fields['speed/compress_web_pages']['read_only'] = true;
				$fields['speed/compress_web_pages']['note_below'] .=
					'<br/>'.
					adminPhrase('Compression is enabled on this server (<code>mod_deflate</code> is enabled in Apache).');
			
			} else
			if (extension_loaded('zlib')
			 && checkFunctionEnabled('ini_get')
			 && engToBoolean(ini_get('zlib.output_compression'))
			) {
				$values['speed/compress_web_pages'] = 1;
				$fields['speed/compress_web_pages']['read_only'] = true;
				$fields['speed/compress_web_pages']['note_below'] .=
					'<br/>'.
					adminPhrase('Compression is enabled on this server (<code>zlib.output_compression</code> is set in your <code>php.ini</code> and/or <code>.htaccess</code> file).');
			
			} else {
				$values['speed/compress_web_pages'] = '';
				$fields['speed/compress_web_pages']['read_only'] = true;
				$fields['speed/compress_web_pages']['note_below'] .=
					'<br/>'.
					adminPhrase('Compression is not enabled on this server. To enable, do one of the following: <br/> &nbsp; &bull; Enable <code>mod_deflate</code> in Apache<br/> &nbsp; &bull; Enable <code>zlib.output_compression</code> in your <code>php.ini</code><br/> &nbsp; &bull; Enable <code>zlib.output_compression</code> in your <code>.htaccess</code> file');
			}
		}
		
		if (isset($fields['speed/have_query_cache'])) {
			if ($result = @sqlSelect("SHOW VARIABLES LIKE '%query_cache%'")) {
				$settings = array();
				while ($row = sqlFetchRow($result)) {
					$settings[$row[0]] = $row[1];
				}
		
				if (!$fields['speed/query_cache_size']['hidden'] = !(
					$fields['speed/have_query_cache']['value'] =
					$fields['speed/have_query_cache']['current_value'] =
						engToBooleanArray($settings, 'have_query_cache') && engToBooleanArray($settings, 'query_cache_type')
				)) {
					$fields['speed/query_cache_size']['value'] =
					$fields['speed/query_cache_size']['current_value'] = formatFilesizeNicely((int) arrayKey($settings, 'query_cache_size'), $precision = 1, $adminMode = true);
				}
	
			} else {
				$fields['speed/have_query_cache']['post_field_html'] = ' '. adminPhrase('(Could not check)');
				$fields['speed/query_cache_size']['hidden'] = true;
			}
		}

		if (isset($fields['test/test_send_email_address'])) {
			$adminDetails = getAdminDetails(adminId());
			$fields['test/test_send_email_address']['value'] = $adminDetails['admin_email'];
		}

		//Working copy images store a number for enabled. But the UI is a checkbox for enabled, and then a number if enabled.
		//Convert the format when displaying the fields
		if (isset($fields['image_sizes/thumbnail_wc'])) {
			if (setting('thumbnail_wc_image_size')) {
				$fields['image_sizes/thumbnail_wc']['value'] = 1;
			} else {
				$fields['image_sizes/thumbnail_wc']['value'] = '';
				$fields['image_sizes/thumbnail_wc_image_size']['value'] = 300;
			}
		}
		if (isset($fields['image_sizes/working_copy_image'])) {
			if (setting('working_copy_image_size')) {
				$fields['image_sizes/working_copy_image']['value'] = 1;
			} else {
				$fields['image_sizes/working_copy_image']['value'] = '';
				$fields['image_sizes/working_copy_image_size']['value'] = 1000;
			}
		}

		if (isset($fields['security/require_security_code_on_admin_login'])) {
			
			if (engToBoolean(siteDescription('require_security_code_on_admin_login'))) {
				$values['security/require_security_code_on_admin_login'] = 1;
				$values['security/security_code_by_ip'] = siteDescription('security_code_by_ip');
				$values['security/security_code_timeout'] = siteDescription('security_code_timeout');
			}
		}

		if (isset($fields['styles/email_style_formats'])) {
	
			$yaml = array('email_style_formats' => siteDescription('email_style_formats'));
	
			if (empty($yaml['email_style_formats'])) {
				$yaml['email_style_formats'] = array();
			}
	
			require_once CMS_ROOT. 'zenario/libraries/mit/spyc/Spyc.php';
			$values['email_style_formats'] = Spyc::YAMLDump($yaml, 4);
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
		if (isset($fields['og/organizer_favicon'])) {
			if ($values['og/organizer_favicon'] != 'custom') {
				$fields['og/custom_organizer_favicon']['_was_hidden_before'] = true;
			}
		}
		
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		if (isset($fields['debug/debug_override_email_address'])) {
			$fields['debug/debug_override_email_address']['hidden'] = !$values['debug/debug_override_enable'];
		}

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
			$fields['cookie_timeouts/cookie_timeout']['current_value'] = secondsToAdminPhrase(COOKIE_TIMEOUT);
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
				formatDateNicely(now(), $values['dates/vis_date_format_short'], true);
	
			$fields['dates/vis_date_format_med__preview']['current_value'] =
				formatDateNicely(now(), $values['dates/vis_date_format_med'], true);
	
			$fields['dates/vis_date_format_long__preview']['current_value'] =
				formatDateNicely(now(), $values['dates/vis_date_format_long'], true);
		}

		if (isset($fields['sitemap/sitemap_url'])) {
			if (!$fields['sitemap/sitemap_url']['hidden'] = !$values['sitemap/sitemap_enabled']) {
				if (setting('mod_rewrite_enabled')) {
					$fields['sitemap/sitemap_url']['value'] =
					$fields['sitemap/sitemap_url']['current_value'] = httpOrhttps() . primaryDomain(). SUBDIRECTORY. 'sitemap.xml';
				} else {
					$fields['sitemap/sitemap_url']['value'] =
					$fields['sitemap/sitemap_url']['current_value'] = httpOrhttps() . primaryDomain(). SUBDIRECTORY. DIRECTORY_INDEX_FILENAME. '?method_call=showSitemap';
				}
			}
		}
		
		if (isset($box['tabs']['caching'])) {
			
			cleanDownloads();
			if (is_writable(CMS_ROOT. 'public/images')
			 && is_writable(CMS_ROOT. 'private/images')) {
				$values['caching/cache_images'] = 1;
				$fields['caching/cache_images']['note_below'] =
					adminPhrase('The <code>public/images/</code> and <code>private/images/</code> directories are writable, the CMS will create cached copies of images and thumbnails in these directories.');
				$box['tabs']['caching']['notices']['img_dir_writable']['show'] = true;
				$box['tabs']['caching']['notices']['img_dir_not_writable']['show'] = false;
			} else {
				$values['caching/cache_images'] = '';
				$fields['caching/cache_images']['note_below'] =
					adminPhrase('The <code>public/images/</code> and <code>private/images/</code> directories are not writable, images and thumbnails will not be cached.');
			}
	
			if (isset($box['tabs']['clear_cache']) && checkPriv('_PRIV_EDIT_SITE_SETTING')) {
				//Manually clear the cache
				if (!empty($box['tabs']['clear_cache']['fields']['clear_cache']['pressed'])) {
					
					zenarioClearCache();
	
					$box['tabs']['clear_cache']['notices']['notice']['show'] = true;
				} else {
					$box['tabs']['clear_cache']['notices']['notice']['show'] = false;
				}
			}
		}

		if (isset($box['tabs']['antiword'])) {
			$box['tabs']['antiword']['notices']['error']['show'] =
			$box['tabs']['antiword']['notices']['success']['show'] = false;
	
			if (!empty($fields['antiword/test']['pressed'])) {
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
	
			if (!empty($fields['pdftotext/test']['pressed'])) {
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

			if (!empty($fields['ghostscript/test']['pressed'])) {
				$extract = '';
				setSetting('ghostscript_path', $values['ghostscript/ghostscript_path'], $updateDB = false);

				if (createPpdfFirstPageScreenshotPng(moduleDir('zenario_common_features', 'fun/test_files/test.pdf'))) {
					$box['tabs']['ghostscript']['notices']['success']['show'] = true;
				} else {
					$box['tabs']['ghostscript']['notices']['error']['show'] = true;
				}
			}
		}

		if (isset($fields['test/test_send_button'])) {
	
			$box['tabs']['test']['notices']['test_send_error']['show'] = false;
			$box['tabs']['test']['notices']['test_send_sucesses']['show'] = false;
	
			if (engToBooleanArray($fields['test/test_send_button'], 'pressed')) {
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
		if (isset($fields['admin_domain/admin_domain'])) {
			if ($values['admin_domain/admin_domain'] != 'none'
			 && $values['admin_domain/admin_domain'] != $_SERVER['HTTP_HOST']
			 && $values['admin_domain/admin_domain'] != setting('admin_domain')) {
				$box['tabs']['admin_domain']['errors'][] = adminPhrase('Please select a domain name.');
			}
		}

		$path = 'zenario/has_database_changed_and_is_cache_out_of_date.php';
		$post = true;
		if (isset($fields['primary_domain/primary_domain'])) {
			if ($values['primary_domain/primary_domain'] == 'new') {
				if ($values['primary_domain/new']) {
					if ($thisDomainCheck = curl(absCMSDirURL(). $path, $post)) {
						if ($specifiedDomainCheck = curl(($cookieFreeDomain = httpOrHttps(). $values['primary_domain/new']. SUBDIRECTORY). $path, $post)) {
							if ($thisDomainCheck == $specifiedDomainCheck) {
								//Success, looks correct
							} else {
								$box['tabs']['primary_domain']['errors'][] = adminPhrase('We tried to check "[[domain]]", but it does not point to this site. Please check your domain and try again.', array('domain' => $cookieFreeDomain));
							}
						} else {
							$box['tabs']['primary_domain']['errors'][] = adminPhrase('We tried to check "[[domain]]", but it does not point to this site. Please check your domain and try again.', array('domain' => $cookieFreeDomain));
						}
					}
				} else {
					$box['tabs']['primary_domain']['errors'][] = adminPhrase('Please enter a primary domain.');
				}
			}
		}

		if (isset($fields['cookie_free_domain/cookie_free_domain'])) {
			if ($values['cookie_free_domain/use_cookie_free_domain']) {
				if ($values['cookie_free_domain/cookie_free_domain']) {
					if ($values['cookie_free_domain/cookie_free_domain'] != adminDomain()
					 && $values['cookie_free_domain/cookie_free_domain'] != primaryDomain()) {
						if ($thisDomainCheck = curl(absCMSDirURL(). $path, $post)) {
							if ($specifiedDomainCheck = curl(($cookieFreeDomain = httpOrHttps(). $values['cookie_free_domain/cookie_free_domain']. SUBDIRECTORY). $path, $post)) {
								if ($thisDomainCheck == $specifiedDomainCheck) {
									//Success, looks correct
								} else {
									$box['tabs']['cookie_free_domain']['errors'][] = adminPhrase('We tried to check "[[domain]]", but it does not point to this site. Please check your domain and try again.', array('domain' => $cookieFreeDomain));
								}
							} else {
								$box['tabs']['cookie_free_domain']['errors'][] = adminPhrase('We tried to check "[[domain]]", but it does not point to this site. Please check your domain and try again.', array('domain' => $cookieFreeDomain));
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

		if (isset($fields['image_sizes/jpeg_quality'])) {
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

		if (isset($box['tabs']['automated_backups']['fields']['check_automated_backups'])
		 && engToBooleanArray($box['tabs']['automated_backups'], 'edit_mode', 'on')
		 && $values['automated_backups/check_automated_backups']) {
			if (!$values['automated_backups/automated_backup_log_path']) {
				//Allow no path to be entered; this is actually the default state.
				//This will cause a warning on the diagnostics screen.
				//$box['tabs']['automated_backups']['errors'][] = adminPhrase('Please enter a file path.');
	
			} elseif (!is_file($values['automated_backups/automated_backup_log_path'])) {
				$box['tabs']['automated_backups']['errors'][] = adminPhrase('This file does not exist.');
	
			} elseif (!is_readable($values['automated_backups/automated_backup_log_path'])) {
				$box['tabs']['automated_backups']['errors'][] = adminPhrase('This file is not readable.');
	
			} elseif (false !== chopPrefixOffOfString(realpath($values['automated_backups/automated_backup_log_path']), realpath(CMS_ROOT))) {
				$box['tabs']['automated_backups']['errors'][] = adminPhrase('Zenario is installed in this directory. Please choose a different path.');
			}
		}

		foreach (array('backup_dir', 'docstore_dir') as $dir) {
			if ($saving
			 && isset($box['tabs'][$dir]['fields'][$dir])
			 && engToBooleanArray($box['tabs'][$dir], 'edit_mode', 'on')) {
				if (!$values[$dir. '/'. $dir]) {
					$box['tabs'][$dir]['errors'][] = adminPhrase('Please enter a directory.');
		
				} elseif (!is_dir($values[$dir. '/'. $dir])) {
					$box['tabs'][$dir]['errors'][] = adminPhrase('This directory does not exist.');
		
				} elseif (false !== chopPrefixOffOfString(realpath($values[$dir. '/'. $dir]), realpath(CMS_ROOT))) {
					$box['tabs'][$dir]['errors'][] = adminPhrase('Zenario is installed in this directory. Please choose a different directory.');
		
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
