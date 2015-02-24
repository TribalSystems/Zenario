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


if (isset($box['tabs']['urls']['fields']['mod_rewrite_suffix'])) {
	$box['tabs']['urls']['fields']['mod_rewrite_suffix']['hidden'] = !engToBooleanArray($values, 'urls/mod_rewrite_enabled');
}

if (isset($box['tabs']['debug']['fields']['debug_override_email_address'])) {
	$box['tabs']['debug']['fields']['debug_override_email_address']['hidden'] = !$values['debug/debug_override_enable'];
}

if (isset($box['tabs']['speed']['fields']['cookie_free_domain'])) {
	$box['tabs']['speed']['fields']['cookie_free_domain']['hidden'] = !$values['speed/use_cookie_free_domain'];
	$box['tabs']['speed']['fields']['cookie_free_domain']['post_field_html'] = htmlspecialchars(SUBDIRECTORY);
}

if (isset($box['tabs']['errors']['fields']['show_notices'])) {
	$box['tabs']['errors']['fields']['show_notices']['value'] =
	$box['tabs']['errors']['fields']['show_notices']['current_value'] = (error_reporting() & E_NOTICE) == E_NOTICE;
}

if (isset($box['tabs']['errors']['fields']['show_strict'])) {
	$box['tabs']['errors']['fields']['show_strict']['value'] =
	$box['tabs']['errors']['fields']['show_strict']['current_value'] = (error_reporting() & E_STRICT) == E_STRICT;
}

if (isset($box['tabs']['dates']['fields']['vis_date_format_short'])) {
	$box['tabs']['dates']['fields']['vis_date_format_short__preview']['current_value'] =
		formatDateNicely(now(), $values['dates/vis_date_format_short'], true);
	
	$box['tabs']['dates']['fields']['vis_date_format_med__preview']['current_value'] =
		formatDateNicely(now(), $values['dates/vis_date_format_med'], true);
	
	$box['tabs']['dates']['fields']['vis_date_format_long__preview']['current_value'] =
		formatDateNicely(now(), $values['dates/vis_date_format_long'], true);
}

if (isset($box['tabs']['errors']['fields']['show_all'])) {
	$box['tabs']['errors']['fields']['show_all']['value'] =
	$box['tabs']['errors']['fields']['show_all']['current_value'] = ((error_reporting() | E_NOTICE | E_STRICT) & (E_ALL | E_NOTICE | E_STRICT)) == (E_ALL | E_NOTICE | E_STRICT);
}

if (isset($box['tabs']['sitemap']['fields']['sitemap_url'])) {
	if (!$box['tabs']['sitemap']['fields']['sitemap_url']['hidden'] = !$values['sitemap/sitemap_enabled']) {
		if (setting('mod_rewrite_enabled')) {
			$box['tabs']['sitemap']['fields']['sitemap_url']['value'] =
			$box['tabs']['sitemap']['fields']['sitemap_url']['current_value'] = 'http://'. primaryDomain(). SUBDIRECTORY. 'sitemap.xml';
		} else {
			$box['tabs']['sitemap']['fields']['sitemap_url']['value'] =
			$box['tabs']['sitemap']['fields']['sitemap_url']['current_value'] = 'http://'. primaryDomain(). SUBDIRECTORY. indexDotPHP(). '?method_call=showSitemap';
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


return false;