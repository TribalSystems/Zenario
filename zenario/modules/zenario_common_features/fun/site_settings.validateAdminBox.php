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


if (isset($box['tabs']['primary_domain']['fields']['primary_domain'])) {
	if ($values['primary_domain/primary_domain'] != 'none'
	 && $values['primary_domain/primary_domain'] != $_SERVER['HTTP_HOST']
	 && $values['primary_domain/primary_domain'] != setting('primary_domain')) {
		$box['tabs']['primary_domain']['errors'][] = adminPhrase('Please select a domain name.');
	}
}

if (isset($box['tabs']['speed']['fields']['cookie_free_domain'])) {
	if ($values['speed/use_cookie_free_domain']) {
		if ($values['speed/cookie_free_domain']) {
			if ($values['speed/cookie_free_domain'] != primaryDomain()) {
				$path = 'zenario/quick_ajax.php';
				$post = array('_get_data_revision' => 1, 'admin' => 1);
				if ($thisDomainCheck = curl(absCMSDirURL(). $path, $post)) {
					if ($cookieFreeDomainCheck = curl(($cookieFreeDomain = 'http://'. $values['speed/cookie_free_domain']. SUBDIRECTORY). $path, $post)) {
						if ($thisDomainCheck == $cookieFreeDomainCheck) {
							//Success, looks correct
						} else {
							$box['tabs']['speed']['errors'][] = adminPhrase('[[domain]] is pointing to a different site, or possibly an out-of-date copy of this site.', array('domain' => $cookieFreeDomain));
						}
					} else {
						$box['tabs']['speed']['errors'][] = adminPhrase('A CURL request to [[domain]] failed. Either this is an invalid URL or Zenario is not at this location.', array('domain' => $cookieFreeDomain));
					}
				} else {
					$box['tabs']['speed']['errors'][] = adminPhrase('The CMS could not use CURL to check that the domain is working properly. Please enable CURL on your server to continue.');
				}
			} else {
				$box['tabs']['speed']['errors'][] = adminPhrase('The cookie-free domain must be a different domain to the primary domain.');
			}
		} else {
			$box['tabs']['speed']['errors'][] = adminPhrase('Please enter a cookie-free domain.');
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

return false;