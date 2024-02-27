<?php
/*
 * Copyright (c) 2024, Tribal Limited
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

use ZxcvbnPhp\Zxcvbn;

class welcome {


	public static function directoryIsWritable($dir) {
	
		//Check to see if the directory is flagged as writable
		if (!@is_writable($dir)) {
			return false;
		}
	
		//Check to see if the directory is flagged as listable/executable
		if (substr($dir, -1) == '/') {
			$dir .= '.';
		} else {
			$dir .= '/.';
		}
	
		return @file_exists($dir);
	}



	public static function passwordMessageSnippet($password, $isInstaller = false) {
		$passwordMessageSnippet = '';

		$passwordValidation = \ze\user::checkPasswordStrength($password, $checkIfEasilyGuessable = false);
		$minScore = (int) (\ze::setting('min_extranet_user_password_score') ?: 2);

		if ($password) {
			if ($passwordValidation['min_length']) {
				$zxcvbn = new \ZxcvbnPhp\Zxcvbn();
				$result = $zxcvbn->passwordStrength($password);

				if ($result && isset($result['score'])) {
					switch ($result['score']) {
						case 4: //is very unguessable (guesses >= 10^10) and provides strong protection from offline slow-hash scenario
							if ($minScore < 4) {
								$phrase = 'Password is very strong and exceeds requirements (score 4, max)';
							} elseif ($minScore == 4) {
								$phrase = 'Password matches the requirements (score 4)';
							}

							$passwordMessageSnippet = 
								'<div>
									<span id="snippet_password_message" class="title_green">' . \ze\admin::phrase($phrase) . '</span>
								</div>';
							break;
						case 3: //is safely unguessable (guesses < 10^10), offers moderate protection from offline slow-hash scenario
							if ($minScore == 4) {
								$passwordMessageSnippet = 
								'<div>
									<span id="snippet_password_message" class="title_red">' . \ze\admin::phrase('Password is too easy to guess (score [[score]])', ['score' => (int) $result['score']]) . '</span>
								</div>';
							} elseif ($minScore < 4) {
								$passwordMessageSnippet = 
									'<div>
										<span id="snippet_password_message" class="title_green">' . \ze\admin::phrase('Password matches the requirements (score 3)') . '</span>
									</div>';
							}
							break;
						case 2: //is somewhat guessable (guesses < 10^8), provides some protection from unthrottled online attacks
							if ($minScore == 2) {
								if ($isInstaller) {
									$phrase = \ze\admin::phrase('Password is easy to guess. Make your password stronger if this will be a production site.');
								} else {
									$phrase = \ze\admin::phrase('Password is too easy to guess (score [[score]])', ['score' => (int) $result['score']]);
								}
								$passwordMessageSnippet = 
									'<div>
										<span id="snippet_password_message" class="title_orange">' . $phrase . '</span>
									</div>';
							} elseif ($minScore > 2) {
								$passwordMessageSnippet = 
									'<div>
										<span id="snippet_password_message" class="title_red">' . \ze\admin::phrase('Password is too easy to guess (score [[score]])', ['score' => (int) $result['score']]) . '</span>
									</div>';
							}
							break;
						case 1: //is still very guessable (guesses < 10^6)
						case 0: //s extremely guessable (within 10^3 guesses)
						default:
							$passwordMessageSnippet = 
								'<div>
									<span id="snippet_password_message" class="title_red">' . \ze\admin::phrase('Password is too easy to guess (score [[score]])', ['score' => (int) $result['score']]) . '</span>
								</div>';
							break;
					}
				}
			} else {
				$passwordMessageSnippet = 
					'<div>
						<span id="snippet_password_message" class="title_red">' . \ze\admin::phrase('Password does not match the requirements') . '</span>
					</div>';
			}
		} else {
			$passwordMessageSnippet = 
				'<div>
					<span id="snippet_password_message" class="title_orange">' . \ze\admin::phrase('Please enter a password') . '</span>
				</div>';
		}
		
		return $passwordMessageSnippet;
	}
	
	public static function quickValidateWelcomePage(&$values, &$rowClasses, &$snippets, $tab) {
		if ($tab == 5 || $tab == 'change_password' || $tab == 'new_admin') {
			
			//Validate password
			$snippets['password_message'] = self::passwordMessageSnippet($values['password']);
	
		} elseif ($tab == 6) {
			if (is_file($values['path'])) {
				if (substr($values['path'], -4) != '.sql'
				 && substr($values['path'], -7) != '.sql.gz'
				 && substr($values['path'], -14) != '.sql.encrypted'
				 && substr($values['path'], -17) != '.sql.gz.encrypted') {
					$snippets['path_status'] = \ze\admin::phrase('Please select a backup file.');
				} elseif (is_readable($values['path'])) {
					$snippets['path_status'] = \ze\admin::phrase('File exists.');
				} else {
					$snippets['path_status'] = \ze\admin::phrase('File exists but cannot be read.');
				}
			} elseif (@is_dir($values['path'])) {
				$snippets['path_status'] = \ze\admin::phrase('Directory exists, please enter a filename.');
			} else {
				$snippets['path_status'] = \ze\admin::phrase('No file or directory with that name.');
			}
		}

	}

	//This file includes common functionality for running SQL scripts

	public static function runSQL($prefix, $file, &$error, $patterns = false, $replacements = false) {
		
		\ze\dbAdm::getTableEngine();
		$error = false;
	
		//Attempt to work out the location of the installer scripts, if not provided
		if (!$prefix) {
			$prefix = CMS_ROOT. 'zenario/admin/db_install/';
		}
	
		if (!file_exists($prefix. $file)) {
			$error = 'SQL file '. $file. ' does not exist.';
			return false;
		}
	
		//Build up a list of pattern replacements
		$from = ["\r"];
		$to = [''];
		if (!$patterns) {
			//If no patterns have been set, go in with a few default patterns. Note I am assuming that the CMS
			//is running here...
			$from[] = '[[DB_PREFIX]]';
			$to[]   =    DB_PREFIX;
			
			$from[] = '[[LATEST_REVISION_NO]]';
			$to[]   =    LATEST_REVISION_NO;
			
			$from[] = '[[INSTALLER_REVISION_NO]]';
			$to[]   =    INSTALLER_REVISION_NO;
			
			$from[] = '[[ZENARIO_TABLE_ENGINE]]';
			$to[]   =    ZENARIO_TABLE_ENGINE;
			
			$from[] = '[[ZENARIO_TABLE_CHARSET]]';
			$to[]   =    ZENARIO_TABLE_CHARSET;
			
			$from[] = '[[ZENARIO_TABLE_COLLATION]]';
			$to[]   =    ZENARIO_TABLE_COLLATION;
			
			$from[] = '[[THEME]]';
			$to[]   =    INSTALLER_DEFAULT_THEME;
			
		} else {
			foreach($patterns as $pattern => $replacement) {
			
				//Accept $patterns and $replacements in two different arrays with numeric keys
				if (is_array($replacements)) {
					$pattern = $replacement;
					$replacement = $replacements[$pattern];
				}
			
				$from[] = '[['. $pattern. ']]';
				if ($pattern == 'DB_PREFIX') {
					$to[] = $replacement;
				} else {
					$to[] = \ze\escape::sql($replacement);
				}
			}
		}
	
		//Get the contents of the script, do the replacements, and split up into statements
		$sqls = explode(";\n", str_replace($from, $to, file_get_contents($prefix. $file)));


		//Get the number of sql statements, check if the last one is empty and exclude it if so
		$count = count($sqls);
		trim($sqls[$count-1])? null: --$count;
	
		//Loop through and execute each statement
		for ($i = 0; $i < $count; ++$i) {
			$query = $sqls[$i];
		
			if (!$result = \ze\sql::cacheFriendlyUpdate($query)) {
				$errno = \ze\sql::errno();
				$error = \ze\sql::error();
				$error = '(Error '. $errno. ': '. $error. "). \n\n". $query. "\nFile: ". $file;
				return false;
			}
		}
	
		return true;
	}


	public static function readSampleConfigFile($patterns, $multiDBValue) {
		$searches = $replaces = [];
		foreach ($patterns as $pattern => $value) {
			$searches[] = '[['. $pattern. ']]';
			$replaces[] = $value;
		}

		if ($multiDBValue == 'zenario_multisite') {
			return str_replace($searches, $replaces, file_get_contents(CMS_ROOT. 'zenario/admin/db_install/zenario_siteconfig.local.sample.php'));
		} else {
			return str_replace($searches, $replaces, file_get_contents(CMS_ROOT. 'zenario/admin/db_install/zenario_siteconfig.standalone.sample.php'));
		}
	}

	public static function compareVersionNumber($actual, $required) {
		return version_compare(preg_replace('@[^\d\.]@', '', $actual), $required, '>=');
	}

	public static function svnInfo() {
		if (!\ze\server::isWindows() && \ze\server::execEnabled()) {
			$output = [];
			$svninfo = [];
			$realDir = realpath($logicalDir = CMS_ROOT. 'zenario');
		
			if (is_dir($realDir. '/.svn')) {
				@exec('svn info '. escapeshellcmd($realDir. '/'), $output);
	
			} elseif (is_dir(dirname($realDir). '/.svn')) {
				@exec('svn info '. escapeshellcmd(dirname($realDir). '/'), $output);
	
			} elseif (is_dir('.svn')) {
				@exec('svn info .', $output);
			}
		
			if (!empty($output)) {
				foreach ($output as $line) {
					$line = explode(': ', $line, 2);
		
					if (!empty($line[1])) {
						$svninfo[$line[0]] = $line[1];
					}
				}
			
				if (!empty($svninfo)
				 && !empty($svninfo['Revision'])) {
					return $svninfo;
				}
			}
		}
	
		return false;
	}

	public static function installerReportError() {
		return "\n". \ze\admin::phrase('(Error [[errno]]: [[error]])', ['errno' => \ze\sql::errno(), 'error' => \ze\sql::error()]);
	}
	
	public static function refreshAdminSession() {
		if (\ze\admin::id()) {
			\ze\admin::setSession(\ze\admin::id(), ($_SESSION['admin_global_id'] ?? false));
		}
	}

	public static function prepareAdminWelcomeScreen($path, &$source, &$tags, &$fields, &$values, &$changes) {
	
		$resetErrors = true;
		$clientPath = \ze\tuix::deTilde($tags['path'] ?? '');
	
		//If this is the first time we're displaying something,
		//or we were displaying something different before and have now just switched paths,
		//then wipe any previous client tags and initialise every tag as they are defined in the .yaml files.
		if ($clientPath != $path) {
			$filling = true;
		
			$tags = $source[$path];
			$tags['path'] = $path;
	
		//If we're re-displaying the same form, merge the tags on the client with the definitions from the .yaml files.
		} else {
			$filling = false;
		
			$clientTags = $tags;
			$tags = $source[$path];
			\ze\tuix::syncFromClientToServer($tags, $clientTags);
			$tags['path'] = $path;
		}
	

		$fields = [];
		$values = [];
		$changes = [];
		\ze\tuix::readValues($tags, $fields, $values, $changes, $filling, $resetErrors);
	}


	//Old code for sample sites, commented out as we don't currently use them
	//function listSampleSites() {
	//	$sampleSites = [];
	//	
	//	foreach (\ze::moduleDirs('sample_installations/') as $mdir) {
	//		foreach (scandir($path = CMS_ROOT. $mdir) as $dir) {
	//			if (substr($dir, 0, 1) != '.'
	//			 && is_file($path. $dir. '/description.txt')
	//			 && is_file($path. $dir. '/backup.sql.gz')) {
	//				$sampleSites[$dir] = $path. $dir. '/';
	//			}
	//		}
	//	}
	//	
	//	return $sampleSites;
	//}

	public static function listSampleThemes() {
		$themes = [];
		$sDir = 'zenario_custom/skins/';
	
		if (is_dir($path = CMS_ROOT. $sDir)) {
			foreach (scandir($path) as $dir) {
				if (substr($dir, 0, 1) != '.'
				 && is_file($path. $dir. '/installer/thumbnail.png')) {
					$themes[$dir] = $sDir. $dir. '/installer/thumbnail.png';
				}
			}
		}
	
		if (empty($themes)) {
			$themes[INSTALLER_DEFAULT_THEME] = $sDir. INSTALLER_DEFAULT_THEME. '/installer/thumbnail.png';
		}
	
		return $themes;
	}
	
	//A list of some common mime types, including images. Used in the installer where we don't have database access
	public static function commonMimeType($type) {
		$mimeTypes = [
			'gif' => 'image/gif',
			'jpe' => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'jpg' => 'image/jpeg',
			'png' => 'image/png',
			'svg' => 'image/svg+xml'
		];
	
		return $mimeTypes[$type] ?? 'application/octet-stream';
	}

	public static function systemRequirementsAJAX(&$source, &$tags, &$fields, &$values, $changes, $isDiagnosticsPage = false) {
	
		if ($isDiagnosticsPage) {
			$valid = 'sub_valid';
			$warning = 'sub_warning';
			$invalid = 'sub_invalid';
			$section_valid = 'sub_section_valid';
			$section_warning = 'sub_section_warning';
			$section_invalid = 'sub_section_invalid';
		} else {
			$valid = 'valid';
			$warning = 'warning';
			$invalid = 'invalid';
			$section_valid = 'section_valid';
			$section_warning = 'section_warning';
			$section_invalid = 'section_invalid';
		}
	
		//Check if the server meets the requirements
		//Get the server phpinfo
		//(Note this section of code may not work properly if compression has been enabled)
		$search = [];
		$replace = [];
	
		$search[] = '~';
		$replace[] = '--';
	
		$search[] = '`';
		$replace[] = '---';
	
		$search[] = '<h2';
		$replace[] = '~<h2';
	
		$search[] = '</h2>';
		$replace[] = '</h2>~';
	
		$search[] = '<td';
		$replace[] = '`<td';
	
		ob_start();
		phpinfo();
		$info = strip_tags(str_replace($search, $replace, ob_get_contents()));
		ob_end_clean();
	
		$phpinfo = [];
		$sectionName = 'PHP';
		foreach (explode('~', $info) as $i => $section) {
			if ($i % 2) {
				$phpinfo[$sectionName = $section] = [];
			} else {
				foreach (explode("\n", $section) as $line) {
					foreach (explode('`', $line) as $j => $cell) {
						if ($j == 1) {
							$setting = trim($cell);
						} else if ($j == 2) {
							$phpinfo[$sectionName][$setting] = trim($cell);
						}
					}
				}
			}
		}
		unset($info, $search, $replace);
	
	
		//Check whether we're using Apache, and if so, which version?
		$apacheVer = [];
		$isApache = preg_match('@Apache(|/?[\d\.]+)@i', $_SERVER['SERVER_SOFTWARE'], $apacheVer);
		
		//PHP doesn't always get given the version number, try calling apache2 directly as a fallback
		if ($isApache && empty($apacheVer[1]) && \ze\server::execEnabled()) {
			exec('apache2 -v', $apacheVer[1]);
			
			if (is_array($apacheVer[1])) {
				$apacheVer[1] = implode(' ', $apacheVer[1]);
			}
		}
		
		//Show a warning if not running Apache.
		//If we could get the version number above, also check it's 2.4.7 or later
		if (!$isApache
		 || ($apacheVer[1] && !\ze\welcome::compareVersionNumber($apacheVer[1], '2.4.7'))) {
			$fields['0/server_1']['row_class'] = $warning;
			$apacheRecommendationMet = false;
	
		} else {
			$fields['0/server_1']['row_class'] = $valid;
			$apacheRecommendationMet = true;
		}
		
		if (empty($apacheVer[1])) {
			$fields['0/server_1']['post_field_html'] =
				\ze\admin::phrase('&nbsp;(<em>you have [[software]]</em>)', ['software' => htmlspecialchars($_SERVER['SERVER_SOFTWARE'])]);
		} else {
			$fields['0/server_1']['post_field_html'] =
				\ze\admin::phrase('&nbsp;(<em>you have version [[version]]</em>)', ['version' => htmlspecialchars($apacheVer[1])]);
		}
	
		if (!$fields['0/directory_indexing_warning']['hidden'] = !(
			file_exists(CMS_ROOT. 'private/')
		 && \ze\curl::checkEnabled()
		 && ($headers = \ze\curl::fetchHeaders(\ze\link::absolute(). 'private/'))
		 && (isset($headers['http_code']))
		 && (strpos($headers['http_code'], '403') === false)
		)) {
			$fields['0/directory_indexing_warning']['row_class'] = $warning;
			$apacheRecommendationMet = false;
		}
	
		$phpInvalid = false;
		$phpWarning = false;
		$phpVersion = phpversion();
		$fields['0/php_1']['post_field_html'] =
			\ze\admin::phrase('&nbsp;(<em>you have version [[version]]</em>)', ['version' => htmlspecialchars($phpVersion)]);
	
		if (!\ze\welcome::compareVersionNumber($phpVersion, '7.2.0')) {
			$fields['0/php_1']['row_class'] = $invalid;
			$phpInvalid = true;
	
		} elseif (!\ze\welcome::compareVersionNumber($phpVersion, '8.0.0')) {
			$fields['0/php_1']['row_class'] = $warning;
			$phpWarning = true;
	
		} else {
			$fields['0/php_1']['row_class'] = $valid;
		}
	
		if (!$fields['0/opcache_misconfigured']['hidden'] =
			\ze\welcome::compareVersionNumber(phpversion(), '7.2.0')
		 || !\ze\server::checkFunctionEnabled('ini_get')
		 || !\ze\ring::engToBoolean(ini_get('opcache.enable'))
		 || \ze\ring::engToBoolean(ini_get('opcache.dups_fix'))
		) {
			$fields['0/opcache_misconfigured']['row_class'] = $warning;
			$phpWarning = true;
		}
	
		//Some fields below will use an "OK" or "Failed, please enable" phrase.
		//Set them here.
		$okPhrase = 'OK';
		$failedPhrase = 'Failed, please enable';
		
		$mysqlRequirementsMet = false;
		if (!extension_loaded('mysqli')) {
			$fields['0/mysql_1']['row_class'] = $invalid;
			$fields['0/mysql_2']['row_class'] = $invalid;
			$fields['0/mysql_2']['post_field_html'] = '';
			
			\ze\lang::applyMergeFields($fields['0/mysql_1']['snippet']['html'], ['ok_or_failed' => $failedPhrase]);
			\ze\lang::applyMergeFields($fields['0/mysql_2']['snippet']['html'], ['ok_or_failed' => $failedPhrase]);
	
		} else {
			$fields['0/mysql_1']['row_class'] = $valid;
			\ze\lang::applyMergeFields($fields['0/mysql_1']['snippet']['html'], ['ok_or_failed' => $okPhrase]);
			
			//Attempt to check the MySQL version.
			//There's a simple test for this in PHP, but unfortunately it returns the version
			//of PHP's library functions, and not the actual version of the MySQL server, so
			//it would be better if we could check the MySQL server's version directly.
			
			//If the CMS is already installed, we can do a database connection and read the right version off of the metadata.
			if ($isDiagnosticsPage) {
				$dbL = new \ze\db(DB_PREFIX, DBHOST, DBNAME, DBUSER, DBPASS, DBPORT, false);
				$mysqlServerVersion = (isset($dbL->con->server_info)) ? $dbL->con->server_info : false;
				$fields['0/mysql_2']['post_field_html'] =
						\ze\admin::phrase('&nbsp;(<em>your server is version [[version]]</em>)', ['version' => htmlspecialchars($mysqlServerVersion)]);
				if (!$mysqlServerVersion
				 || !\ze\welcome::compareVersionNumber($mysqlServerVersion, '5.7')) {
					$fields['0/mysql_2']['row_class'] = $invalid;
				} else {
					$fields['0/mysql_2']['row_class'] = $valid;
					$mysqlRequirementsMet = true;
				}
			
			} else {
				//Check the MySQL version on the PHP the client.
				$mysqlVersion = \ze::ifNull(
					\ze\ray::value($phpinfo, 'mysql', 'Client API version'),
					\ze\ray::value($phpinfo, 'mysqli', 'Client API library version'));
		
				//Try and check the MySQL version on this server
				if (\ze\server::programPathForExec('/usr/bin/', 'mysql', $checkExecutable = true)) {
					\ze\site::setSetting('mysql_path', '/usr/bin/', false, false, false);

				} elseif (\ze\server::programPathForExec('/usr/local/bin/', 'mysql', $checkExecutable = true)) {
					\ze\site::setSetting('mysql_path', '/usr/local/bin/', false, false, false);
				}
				if (\ze::setting('mysql_path') && ($mysqlServerVersion = \ze\dbAdm::callMySQL(false, ' --version'))) {
					$mysqlServerVersion = \ze\ring::chopPrefix(\ze::setting('mysql_path'), $mysqlServerVersion, true);
					$matches = [];
					
					if (
						//Pre MySQL 8
						($matches = preg_split('@Distrib ([\d\.]+)@', $mysqlServerVersion, 2, PREG_SPLIT_DELIM_CAPTURE))
						//MySQL 8+
					 || ($matches = preg_split('@Ver ([\d\.]+)@', $mysqlServerVersion, 2, PREG_SPLIT_DELIM_CAPTURE))
					) {
						if (!empty($matches[1])) {
							if (!\ze\welcome::compareVersionNumber($mysqlVersion, $matches[1])) {
								$mysqlVersion = $mysqlServerVersion;
							}
						}
					}
				}
		
				if ($mysqlVersion) {
					$fields['0/mysql_2']['post_field_html'] =
						\ze\admin::phrase('&nbsp;(<em>your client is version [[version]]</em>)', ['version' => htmlspecialchars($mysqlVersion)]);
				}
		
				if (!$mysqlVersion
				 || !\ze\welcome::compareVersionNumber($mysqlVersion, '5.7')) {
					$fields['0/mysql_2']['row_class'] = $invalid;
				} else {
					$fields['0/mysql_2']['row_class'] = $valid;
					$mysqlRequirementsMet = true;
				}
			}
		}
	
		$mbRequirementsMet = true;
		if (!extension_loaded('ctype')) {
			$mbRequirementsMet = false;
			$fields['0/mb_1']['row_class'] = $invalid;
			\ze\lang::applyMergeFields($fields['0/mb_1']['snippet']['html'], ['ok_or_failed' => $failedPhrase]);
		} else {
			$fields['0/mb_1']['row_class'] = $valid;
			\ze\lang::applyMergeFields($fields['0/mb_1']['snippet']['html'], ['ok_or_failed' => $okPhrase]);
		}
		
		if (!extension_loaded('mbstring')) {
			$mbRequirementsMet = false;
			$fields['0/mb_2']['row_class'] = $invalid;
			\ze\lang::applyMergeFields($fields['0/mb_2']['snippet']['html'], ['ok_or_failed' => $failedPhrase]);
		} else {
			$fields['0/mb_2']['row_class'] = $valid;
			\ze\lang::applyMergeFields($fields['0/mb_2']['snippet']['html'], ['ok_or_failed' => $okPhrase]);
		}
		
		if ($isDiagnosticsPage) {
			$osInvalid = false;
			$osWarning = false;

			//This variable will be used multiple times.
			$otherServerProgramsSiteSettingLink = '<br />Please go to [[link_start]]<em>Other server programs</em>[[link_end]] in Configuration->Site Settings to change this.</small>';
			
			//Check if Antivirus (ClamAV) is set up
			if (!\ze::setting('clamscan_tool_path')) {
				$href = 'organizer.php#zenario__administration/panels/site_settings//external_programs~.site_settings~tantivirus~k{"id"%3A"external_programs"}';
				$linkStart = '<a href="' . htmlspecialchars($href) . '" target="_blank">';
				$linkEnd = '</a>';
				$otherServerProgramsString = 'Antivirus<br><small>Antivirus scanning is not enabled.';
				
				$osInvalid = false;
				$fields['0/os_av']['row_class'] = $valid;
				$fields['0/os_av']['snippet']['html'] = $fields['0/os_av']['snippet']['html'] = \ze\admin::phrase(
					$otherServerProgramsString . $otherServerProgramsSiteSettingLink,
					['link_start' => $linkStart, 'link_end' => $linkEnd]
				);
		
			} else {
				$filepath = CMS_ROOT. \ze::moduleDir('zenario_common_features', 'fun/test_files/test.pdf');
				$programPath = \ze\server::programPathForExec(\ze::setting('clamscan_tool_path'), 'clamdscan', true);
			
				$fields['0/os_av']['row_class'] = $valid;
			
				if (!$programPath) {
					$href = 'organizer.php#zenario__administration/panels/site_settings//external_programs~.site_settings~tantivirus~k{"id"%3A"external_programs"}';
					$linkStart = '<a href="' . htmlspecialchars($href) . '" target="_blank">';
					$linkEnd = '</a>';
					$otherServerProgramsString = 'Antivirus<br><small>Antivirus scanning is not correctly set up.';
					
					$osInvalid = true;
					$fields['0/os_av']['row_class'] = $invalid;
					$fields['0/os_av']['snippet']['html'] = \ze\admin::phrase(
						$otherServerProgramsString . $otherServerProgramsSiteSettingLink,
						['link_start' => $linkStart, 'link_end' => $linkEnd]
					);
			
				} else {
					$avScan = \ze\server::antiVirusScan($filepath);
				
					if ($avScan === null) {
						$osInvalid = true;
						$fields['0/os_av']['row_class'] = $invalid;
						$fields['0/os_av']['snippet']['html'] = \ze\admin::phrase('Antivirus<br><small>ClamAV is installed, but files cannot be scanned. Please check that the <code>clamd</code> daemon is running, and that AppArmor is not blocking ClamAV.</small>');
					}
				}
			}
			
			//Check if MySQL timezone is set up correctly.
			if (\ze\dbAdm::testMySQLTimezoneHandling()) {
				$fields['0/mysql_timezone_set']['row_class'] = $valid;
			} else {
				$osInvalid = true;
				$fields['0/mysql_timezone_set']['row_class'] = $invalid;
				
				$href = 'organizer.php#zenario__administration/panels/site_settings//external_programs~.site_settings~tmysql~k{"id"%3A"external_programs"}';
				$linkStart = '<a href="' . htmlspecialchars($href) . '" target="_blank">';
				$linkEnd = '</a>';
				
				$fields['0/mysql_timezone_set']['snippet']['html'] = \ze\admin::phrase(
					'MySQL timezone handling<br><small>MySQL timezone handling is not correctly set up. [[link_start]]<em>Click for more info</em>[[link_end]].</small>',
					['link_start' => $linkStart, 'link_end' => $linkEnd]
				);
			}
			
			$extract = '';
			if (!\ze\file::plainTextExtract(\ze::moduleDir('zenario_common_features', 'fun/test_files/test.doc'), $extract)) {
				$href = 'organizer.php#zenario__administration/panels/site_settings//external_programs~.site_settings~tantiword~k{"id"%3A"external_programs"}';
				$linkStart = '<a href="' . htmlspecialchars($href) . '" target="_blank">';
				$linkEnd = '</a>';
				$otherServerProgramsString = 'Antiword<br><small>The program antiword is not correctly set up. This is needed to read a text extract from Word documents that you upload.';
				
				$osWarning = true;
				$fields['0/os_1']['row_class'] = $warning;
				$fields['0/os_1']['snippet']['html'] = \ze\admin::phrase(
					$otherServerProgramsString . $otherServerProgramsSiteSettingLink,
					['link_start' => $linkStart, 'link_end' => $linkEnd]
				);
	
			} else {
				$fields['0/os_1']['row_class'] = $valid;
			}

			if (!\ze\file::createPpdfFirstPageScreenshotPng(\ze::moduleDir('zenario_common_features', 'fun/test_files/test.pdf'))) {
				$href = 'organizer.php#zenario__administration/panels/site_settings//external_programs~.site_settings~tghostscript~k{"id"%3A"external_programs"}';
				$linkStart = '<a href="' . htmlspecialchars($href) . '" target="_blank">';
				$linkEnd = '</a>';
				$otherServerProgramsString = 'Ghostscript<br><small>The program ghostscript is not correctly set up. This is needed to extract an image from PDFs that you upload.';
				
				$osWarning = true;
				$fields['0/os_2']['row_class'] = $warning;
				$fields['0/os_2']['snippet']['html'] = \ze\admin::phrase(
					$otherServerProgramsString . $otherServerProgramsSiteSettingLink,
					['link_start' => $linkStart, 'link_end' => $linkEnd]
				);
			} else {
				$fields['0/os_2']['row_class'] = $valid;
			}

			$jpegtran=\ze\server::programPathForExec(\ze::setting('jpegtran_path'), 'jpegtran', true);
			$jpegoptim= \ze\server::programPathForExec(\ze::setting('jpegoptim_path'), 'jpegoptim', true);
			if ($jpegtran==NULL || $jpegoptim==NULL) {
				
				$fields['0/os_3']['row_class'] = $warning;
				if (\ze::setting('jpegtran_path') && \ze::setting('jpegoptim_path') && $jpegtran== NULL && $jpegoptim!= NULL) {
					$osWarning = true;
					$jpegtranMsg = 'jpegtran is not correctly set up.';
					$jpegoptimMsg = 'jpegoptim is correctly set up.';
				} elseif (\ze::setting('jpegtran_path') && \ze::setting('jpegoptim_path') && $jpegoptim== NULL && $jpegtran!= NULL) {
					$osWarning = true;
					$jpegtranMsg = 'jpegtran is correctly set up.';
					$jpegoptimMsg = 'jpegoptim is not correctly set up.';
				} else {
					if (\ze::setting('jpegtran_path') && $jpegtran == NULL) {
						$warningFlag = true;
						$jpegtranMsg = 'jpegtran is not correctly set up.';
					} elseif (!\ze::setting('jpegtran_path')) {
						$warningFlag = false;
						$jpegtranMsg = 'jpegtran is not enabled.';
					} else {
						$warningFlag = false;
						$jpegtranMsg = 'jpegtran is correctly set up.';
					}
					
					if (\ze::setting('jpegoptim_path') && $jpegoptim == NULL) {
						$warningoptionFlag = true;
						$jpegoptimMsg = 'jpegoptim is not correctly set up.';
					} elseif (!\ze::setting('jpegoptim_path')) {
						$warningoptionFlag = false;
						$jpegoptimMsg = 'jpegoptim is not enabled.';
					} else {
						$warningoptionFlag = false;
						$jpegoptimMsg = 'jpegoptim is correctly set up.';
					}
					
					if ($warningFlag || $warningoptionFlag) {
						$osWarning = true;
						$fields['0/os_3']['row_class'] = $warning;
					}else{
						$fields['0/os_3']['row_class'] = $valid;
					}
				}

				$href = 'organizer.php#zenario__administration/panels/site_settings//external_programs~.site_settings~tjpeg~k{"id"%3A"external_programs"}';
				$linkStart = '<a href="' . htmlspecialchars($href) . '" target="_blank">';
				$linkEnd = '</a>';
				$otherServerProgramsString = 'Compress JPEGs<br><small>' . $jpegtranMsg . '</small><br><small>' . $jpegoptimMsg;

				$fields['0/os_3']['snippet']['html'] = \ze\admin::phrase(
					$otherServerProgramsString . $otherServerProgramsSiteSettingLink,
					['link_start' => $linkStart, 'link_end' => $linkEnd]
				);
			} else {
				$fields['0/os_3']['row_class'] = $valid;
			}
			$mysqlPath = \ze\dbAdm::testMySQL(false);
			$mysqldumpPath = \ze\dbAdm::testMySQL(true);
			if ($mysqlPath == false || $mysqldumpPath == false ) {
				$osWarning = true;
				$fields['0/os_4']['row_class'] = $warning;
				if ($mysqlPath== false && $mysqldumpPath!= false) {
					$MysqlMsg = 'mysql is not correctly set up.';
					$MysqlDumpMsg = 'mysqldump is working successfully.';
				} elseif ($mysqlPath!= false && $mysqldumpPath== false) {
					$MysqlMsg = 'mysql is working successfully.';
					$MysqlDumpMsg = 'mysqldump is not correctly set up.';
				} else {
					$MysqlMsg = 'mysql is not correctly set up.';
					$MysqlDumpMsg = 'mysqldump is not correctly set up.';
					$fields['0/os_4']['row_class'] = $warning;
				}

				$href = 'organizer.php#zenario__administration/panels/site_settings//external_programs~.site_settings~tmysql~k{"id"%3A"external_programs"}';
				$linkStart = '<a href="' . htmlspecialchars($href) . '" target="_blank">';
				$linkEnd = '</a>';
				$otherServerProgramsString = 'Backup/restore<br><small>' . $MysqlMsg . '</small><br><small>' . $MysqlDumpMsg;

				$fields['0/os_4']['snippet']['html'] = \ze\admin::phrase(
					$otherServerProgramsString . $otherServerProgramsSiteSettingLink,
					['link_start' => $linkStart, 'link_end' => $linkEnd]
				);
			} else {
				$fields['0/os_4']['row_class'] = $valid;
			}	
		
			$extract = '';
			if (!(\ze\file::plainTextExtract(\ze::moduleDir('zenario_common_features', 'fun/test_files/test.pdf'), $extract))) {
				$href = 'organizer.php#zenario__administration/panels/site_settings//external_programs~.site_settings~tpdftotext~k{"id"%3A"external_programs"}';
				$linkStart = '<a href="' . htmlspecialchars($href) . '" target="_blank">';
				$linkEnd = '</a>';
				$otherServerProgramsString = 'PDF-To-Text<br><small>The program pdftotext is not correctly set up. This is needed to read a text extract from PDFs that you upload.';
				
				$osWarning = true;
				$fields['0/os_5']['row_class'] = $warning;
				$fields['0/os_5']['snippet']['html'] = \ze\admin::phrase(
					$otherServerProgramsString . $otherServerProgramsSiteSettingLink,
					['link_start' => $linkStart, 'link_end' => $linkEnd]
				);

			} else {
				$fields['0/os_5']['row_class'] = $valid;
			}
			$optipng = \ze\server::programPathForExec(\ze::setting('optipng_path'), 'optipng', true);
			$advpng = \ze\server::programPathForExec(\ze::setting('advpng_path'), 'advpng', true);
			if ($optipng == NULL || $advpng ==NULL) {
				
				$fields['0/os_6']['row_class'] = $warning;
				if (\ze::setting('optipng_path') && \ze::setting('advpng_path') && $optipng== NULL && $advpng!= NULL) {
					$osWarning = true;
					$optipngMsg = 'optipng is not correctly set up.';
					$advpngMsg = 'advpng is correctly set up.';
				} elseif (\ze::setting('optipng_path') && \ze::setting('advpng_path') && $advpng== NULL && $optipng!= NULL) {
					$osWarning = true;
					$optipngMsg = 'optipng is correctly set up.';
					$advpngMsg = 'advpng is not correctly set up.';
				} else {
					if (\ze::setting('optipng_path') && $optipng == NULL) {
						$warningFlag = true;
						$optipngMsg = 'optipng is not correctly set up.';
					} elseif (!\ze::setting('optipng_path')) {
						$warningFlag = false;
						$optipngMsg = 'optipng is not enabled.';
					} else {
						$warningFlag = false;
						$optipngMsg = 'optipng is correctly set up.';
					}
					
					if (\ze::setting('advpng_path') && $advpng == NULL) {
						$warningoptionFlag = true;
						$advpngMsg = 'advpng is not correctly set up.';
					} elseif (!\ze::setting('advpng_path')) {
						$warningoptionFlag = false;
						$advpngMsg = 'advpng is not enabled.';
					} else {
						$warningoptionFlag = false;
						$advpngMsg = 'advpng is correctly set up.';
					}
							
					if ($warningFlag || $warningoptionFlag) {
						$osWarning = true;
						$fields['0/os_6']['row_class'] = $warning;
					} else {
						$fields['0/os_6']['row_class'] = $valid;
					}
				}

				$href = 'organizer.php#zenario__administration/panels/site_settings//external_programs~.site_settings~tpng~k{"id"%3A"external_programs"}';
				$linkStart = '<a href="' . htmlspecialchars($href) . '" target="_blank">';
				$linkEnd = '</a>';
				$otherServerProgramsString = 'Compress PNGs<br><small>' . $optipngMsg . '</small><br><small>' . $advpngMsg;

				$fields['0/os_6']['snippet']['html'] = \ze\admin::phrase(
					$otherServerProgramsString . $otherServerProgramsSiteSettingLink,
					['link_start' => $linkStart, 'link_end' => $linkEnd]
				);
			} else {
				$fields['0/os_6']['row_class'] = $valid;
			}
		
		
			//wkhtmltopdf is an optional program.
			//Enabled and set up correctly:
			if (($programPath = \ze\server::programPathForExec(\ze::setting('wkhtmltopdf_path'), 'wkhtmltopdf'))
					 && ($rv = exec(escapeshellarg($programPath) .' --version'))) {
				$fields['0/os_7']['row_class'] = $valid;
			// Enabled but set up incorrectly:
			} elseif ($programPath && isset($rv) && !$rv) {
				$href = 'organizer.php#zenario__administration/panels/site_settings//external_programs~.site_settings~twkhtmltopdf~k{"id"%3A"external_programs"}';
				$linkStart = '<a href="' . htmlspecialchars($href) . '" target="_blank">';
				$linkEnd = '</a>';
				$otherServerProgramsString = 'wkhtmltopdf<br><small>wkhtmltopdf is not correctly set up.';
				
				$osWarning = true;
				$fields['0/os_7']['row_class'] = $warning;
				$fields['0/os_7']['snippet']['html'] = \ze\admin::phrase(
					$otherServerProgramsString . $otherServerProgramsSiteSettingLink,
					['link_start' => $linkStart, 'link_end' => $linkEnd]
				);
			//Disabled:
			} else {
				$fields['0/os_7']['hidden'] = true;
			}
		}
		
		$gdRequirementsMet = true;
		if (\ze\ray::value($phpinfo, 'gd', 'GD Support') != 'enabled') {
			$gdRequirementsMet = false;
			$fields['0/gd_1']['row_class'] = $invalid;
			$fields['0/gd_2']['row_class'] = $invalid;
			$fields['0/gd_3']['row_class'] = $invalid;
			$fields['0/gd_4']['row_class'] = $invalid;
			
			\ze\lang::applyMergeFields($fields['0/mb_1']['snippet']['html'], ['ok_or_failed' => $failedPhrase]);
			\ze\lang::applyMergeFields($fields['0/gd_2']['snippet']['html'], ['ok_or_failed' => $failedPhrase]);
			\ze\lang::applyMergeFields($fields['0/mb_3']['snippet']['html'], ['ok_or_failed' => $failedPhrase]);
			\ze\lang::applyMergeFields($fields['0/mb_4']['snippet']['html'], ['ok_or_failed' => $failedPhrase]);
		} else {
			$fields['0/gd_1']['row_class'] = $valid;
			\ze\lang::applyMergeFields($fields['0/gd_1']['snippet']['html'], ['ok_or_failed' => $okPhrase]);
		
			if (\ze\ray::value($phpinfo, 'gd', 'GIF Read Support') != 'enabled') {
				$gdRequirementsMet = false;
				$fields['0/gd_2']['row_class'] = $invalid;
				\ze\lang::applyMergeFields($fields['0/gd_2']['snippet']['html'], ['ok_or_failed' => $failedPhrase]);
			} else {
				$fields['0/gd_2']['row_class'] = $valid;
				\ze\lang::applyMergeFields($fields['0/gd_2']['snippet']['html'], ['ok_or_failed' => $okPhrase]);
			}
		
			if (\ze\ray::value($phpinfo, 'gd', 'JPG Support') != 'enabled' && \ze\ray::value($phpinfo, 'gd', 'JPEG Support') != 'enabled') {
				$gdRequirementsMet = false;
				$fields['0/gd_3']['row_class'] = $invalid;
				\ze\lang::applyMergeFields($fields['0/gd_3']['snippet']['html'], ['ok_or_failed' => $failedPhrase]);
			} else {
				$fields['0/gd_3']['row_class'] = $valid;
				\ze\lang::applyMergeFields($fields['0/gd_3']['snippet']['html'], ['ok_or_failed' => $okPhrase]);
			}
		
			if (\ze\ray::value($phpinfo, 'gd', 'PNG Support') != 'enabled') {
				$gdRequirementsMet = false;
				$fields['0/gd_4']['row_class'] = $invalid;
				\ze\lang::applyMergeFields($fields['0/gd_4']['snippet']['html'], ['ok_or_failed' => $failedPhrase]);
			} else {
				$fields['0/gd_4']['row_class'] = $valid;
				\ze\lang::applyMergeFields($fields['0/gd_4']['snippet']['html'], ['ok_or_failed' => $okPhrase]);
			}
		}
	
		$optionalRequirementsMet = true;
		
		$fields['0/optional_mod_deflate']['row_class'] = $valid;
		$fields['0/optional_mod_expires']['row_class'] = $valid;
		$fields['0/optional_mod_rewrite']['row_class'] = $valid;
		
		if (!function_exists('apache_get_modules')) {
			$fields['0/optional_mod_deflate']['hidden'] = true;
			$fields['0/optional_mod_expires']['hidden'] = true;
			$fields['0/optional_mod_rewrite']['hidden'] = true;
		
		} else {
			$apacheModules = apache_get_modules();
		
			if (!in_array('mod_deflate', $apacheModules) && \ze::setting('mod_deflate_check') == 'warn_if_not_available') {
				$optionalRequirementsMet = false;
				$fields['0/optional_mod_deflate']['row_class'] = $warning;
			}
			if (!in_array('mod_expires', $apacheModules)) {
				$optionalRequirementsMet = false;
				$fields['0/optional_mod_expires']['row_class'] = $warning;
			}
			if (!in_array('mod_rewrite', $apacheModules)) {
				$optionalRequirementsMet = false;
				$fields['0/optional_mod_rewrite']['row_class'] = $warning;
			}
		}
		
		if (!extension_loaded('curl')) {
			$optionalRequirementsMet = false;
			$fields['0/optional_curl']['row_class'] = $warning;
			\ze\lang::applyMergeFields($fields['0/optional_curl']['snippet']['html'], ['ok_or_failed' => $failedPhrase]);
		} else {
			$fields['0/optional_curl']['row_class'] = $valid;
			\ze\lang::applyMergeFields($fields['0/optional_curl']['snippet']['html'], ['ok_or_failed' => $okPhrase]);
		}
		
		if (!extension_loaded('zip')) {
			$optionalRequirementsMet = false;
			$fields['0/optional_zip']['row_class'] = $warning;
			\ze\lang::applyMergeFields($fields['0/optional_zip']['snippet']['html'], ['ok_or_failed' => $failedPhrase]);
		} else {
			$fields['0/optional_zip']['row_class'] = $valid;
			\ze\lang::applyMergeFields($fields['0/optional_zip']['snippet']['html'], ['ok_or_failed' => $okPhrase]);
		}
		
		if (!function_exists('xml_parse')) {
			$optionalRequirementsMet = false;
			$fields['0/optional_xml']['row_class'] = $warning;
			\ze\lang::applyMergeFields($fields['0/optional_xml']['snippet']['html'], ['ok_or_failed' => $failedPhrase]);
		} else {
			$fields['0/optional_xml']['row_class'] = $valid;
			\ze\lang::applyMergeFields($fields['0/optional_xml']['snippet']['html'], ['ok_or_failed' => $okPhrase]);
		}
	
		$overall = 0;
	
		if (!$apacheRecommendationMet) {
			$overall = max($overall, 1);
			$fields['0/show_server']['pressed'] = true;
			$fields['0/server']['row_class'] = $section_warning;
		} else {
			$fields['0/server']['row_class'] = $section_valid;
		}
	
		if (!$optionalRequirementsMet) {
			$overall = max($overall, 1);
			$fields['0/show_optional']['pressed'] = true;
			$fields['0/optional']['row_class'] = $section_warning;
		} else {
			$fields['0/optional']['row_class'] = $section_valid;
		}
	
		if ($phpInvalid) {
			$overall = max($overall, 2);
			$fields['0/show_php']['pressed'] = true;
			$fields['0/php']['row_class'] = $section_invalid;
		} elseif ($phpWarning) {
			$overall = max($overall, 1);
			$fields['0/show_php']['pressed'] = true;
			$fields['0/php']['row_class'] = $section_warning;
		} else {
			$fields['0/php']['row_class'] = $section_valid;
		}
	
		if (!$mysqlRequirementsMet) {
			$overall = max($overall, 2);
			$fields['0/show_mysql']['pressed'] = true;
			$fields['0/mysql']['row_class'] = $section_invalid;
		} else {
			$fields['0/mysql']['row_class'] = $section_valid;
		}
	
		if (!$mbRequirementsMet) {
			$overall = max($overall, 2);
			$fields['0/show_mb']['pressed'] = true;
			$fields['0/mb']['row_class'] = $section_invalid;
		} else {
			$fields['0/mb']['row_class'] = $section_valid;
		}
		
		if (!$gdRequirementsMet) {
			$overall = max($overall, 2);
			$fields['0/show_gd']['pressed'] = true;
			$fields['0/gd']['row_class'] = $section_invalid;
		} else {
			$fields['0/gd']['row_class'] = $section_valid;
		}
		
		if ($isDiagnosticsPage) {
			if ($osInvalid) {
				$overall = max($overall, 2);
				$fields['0/show_os']['pressed'] = true;
				$fields['0/os']['row_class'] = $section_invalid;
			
			} elseif ($osWarning) {
				$overall = max($overall, 1);
				$fields['0/show_os']['pressed'] = true;
				$fields['0/os']['row_class'] = $section_warning;
			
			} else {
				$fields['0/os']['row_class'] = $section_valid;
			}
			
			switch ($overall) {
				case 1:
					$fields['0/show_system_requirements']['pressed'] = true;
					$fields['0/system_requirements']['row_class'] = 'section_warning';
					break;
				case 2:
					$fields['0/show_system_requirements']['pressed'] = true;
					$fields['0/system_requirements']['row_class'] = 'section_invalid';
					break;
				default:
					$fields['0/system_requirements']['row_class'] = 'section_valid';
					break;
			}
	
		} else {
			$wasFirstViewing = $tags['key']['first_viewing'];
			$tags['key']['first_viewing'] = false;
	    
			//Did the Server meets the requirements?
			if ($phpInvalid || !$mysqlRequirementsMet || !$mbRequirementsMet || !$gdRequirementsMet) {
				$fields['0/continue']['hidden'] = true;
				return false;
	
			} else {
				$fields['0/continue']['hidden'] = false;
				return $wasFirstViewing || !empty($fields['0/continue']['pressed']);
			}
		}
	}

	public static function installerAJAX(&$source, &$tags, &$fields, &$values, $changes, &$task, $installStatus, &$adminId) {
		$tags['key']['min_extranet_user_password_length'] = \ze::setting('min_extranet_user_password_length');
		$tags['key']['min_extranet_user_password_score'] = \ze::setting('min_extranet_user_password_score');
		
		$merge = [];

		$merge['SUBDIRECTORY'] = SUBDIRECTORY;
		//If the database prefix is already set in the config file, look it up and default the field to it.
		if (!isset($fields['3/prefix']['current_value'])) {
			if (defined('DB_PREFIX') && DB_PREFIX && strpos(DB_PREFIX, '[') === false) {
				$values['3/prefix'] = DB_PREFIX;
			} else {
				$values['3/prefix'] = 'z_';
			}
		}

		if (!isset($fields['3/multi_db_prefix']['current_value'])) {
			if (defined('DB_PREFIX_GLOBAL') && DB_PREFIX_GLOBAL && strpos(DB_PREFIX_GLOBAL, '[') === false) {
				$values['3/multi_db_prefix'] = DB_PREFIX_GLOBAL;
			} else {
				$values['3/multi_db_prefix'] = 'z_';
			}
		}

		// See if there are clues for the database name, username and password
		if (file_exists("../zenario-clues.txt")) {
			$cluesHandle = $clues = null;
			$cluesArr = [];
			$cluesHandle = fopen("../zenario-clues.txt", "r");
			$clues = fread($cluesHandle,filesize("../zenario-clues.txt"));
			$cluesArr = explode (" ", $clues);
			fclose($cluesHandle);
			$fields['3/description']['snippet']['html'] .= 'Note! Database has been created for Zenario, so the fields below have been pre-populated with known working values. Do not change these!';
		}

		//Get current version of Zenario so as to name the database, if no clues present
		if (!isset($fields['3/name']['current_value'])) {
			$values['3/name'] = 'zenario_client1_'. ZENARIO_MAJOR_VERSION. ZENARIO_MINOR_VERSION;
		}
		
		if (isset($cluesArr)) {
			if (!isset($fields['3/user']['current_value']) && $cluesArr[1]) {
				$values['3/user'] = $cluesArr[1];
			}

			if (!isset($fields['3/password']['current_value']) && $cluesArr[2]) {
				$values['3/password'] = $cluesArr[2];
			}
		}
		
		//Validation for Step 1: Check if the Admin has accepted the license
		$licenseAccepted = !empty($values['1/i_agree']);
	
		if (!empty($fields['1/fresh_install']['pressed'])) {
			$task = 'install';
		} elseif (!empty($fields['1/restore']['pressed'])) {
			$task = 'restore';
		}
	
		//Validation for Step 3: Validate the Database Connection
		if ($tags['tab'] > 3 || ($tags['tab'] == 3 && !empty($fields['3/next']['pressed']))) {
			$tags['tabs'][3]['errors'] = [];
		
			$merge['DBHOST'] = $values['3/host'];
			$merge['DBNAME'] = $values['3/name'];
			$merge['DBUSER'] = $values['3/user'];
			$merge['DBPASS'] = $values['3/password'];
			$merge['DBPORT'] = $values['3/port'];
			$merge['DB_PREFIX'] = $values['3/prefix'];
			
		//Validation for Step 3: Validate the Multisite Database Connection
			$merge['DBHOST_GLOBAL'] = $values['3/multi_db_host'];
			$merge['DBNAME_GLOBAL'] = $values['3/multi_db_name'];
			$merge['DBUSER_GLOBAL'] = $values['3/multi_db_user'];
			$merge['DBPASS_GLOBAL'] = $values['3/multi_db_password'];
			$merge['DBPORT_GLOBAL'] = $values['3/multi_db_port'];
			$merge['DB_PREFIX_GLOBAL'] = $values['3/multi_db_prefix'];
		
			if (!$merge['DBHOST']) {
				$tags['tabs'][3]['errors'][] = \ze\admin::phrase('Please enter your hostname.');
			}
			if (!$merge['DBHOST_GLOBAL'] && $values['3/multi_db'] == 'zenario_multisite') {
				$tags['tabs'][3]['errors'][] = \ze\admin::phrase('Please enter your multisite hostname.');
			}
		
			if (!$merge['DBNAME']) {
				$tags['tabs'][3]['errors'][] = \ze\admin::phrase('Please enter your database name.');
			} elseif (preg_match('/[^a-zA-Z0-9_-]/', $merge['DBNAME'])) {
				$tags['tabs'][3]['errors'][] = \ze\admin::phrase('The database name should only contain [a-z, A-Z, 0-9, _ and -].');
			}
			if (!$merge['DBNAME_GLOBAL'] && $values['3/multi_db'] == 'zenario_multisite') {
				$tags['tabs'][3]['errors'][] = \ze\admin::phrase('Please enter the name of the global database for multisite authentication.');
			} elseif (preg_match('/[^a-zA-Z0-9_-]/', $merge['DBNAME_GLOBAL'])) {
				$tags['tabs'][3]['errors'][] = \ze\admin::phrase('The database name should only contain [a-z, A-Z, 0-9, _ and -].');
			}
		
			if (!$merge['DBUSER']) {
				$tags['tabs'][3]['errors'][] = \ze\admin::phrase('Please enter the database username.');
			} elseif (preg_match('/[^a-zA-Z0-9_-]/', $merge['DBUSER'])) {
				$tags['tabs'][3]['errors'][] = \ze\admin::phrase('The database username should only contain [a-z, A-Z, 0-9, _ and -].');
			}
			if (!$merge['DBUSER_GLOBAL'] && $values['3/multi_db'] == 'zenario_multisite') {
				$tags['tabs'][3]['errors'][] = \ze\admin::phrase('Please enter the username of the global database for multisite authentication.');
			} elseif (preg_match('/[^a-zA-Z0-9_-]/', $merge['DBUSER_GLOBAL'])) {
				$tags['tabs'][3]['errors'][] = \ze\admin::phrase('The database username should only contain [a-z, A-Z, 0-9, _ and -].');
			}
		
			if ($merge['DBPORT'] !== '' && !is_numeric($merge['DBPORT'])) {
				$tags['tabs'][3]['errors'][] = \ze\admin::phrase('The database port must be a number.');
			}
			if ($merge['DBPORT_GLOBAL'] !== '' && !is_numeric($merge['DBPORT_GLOBAL']) && $values['3/multi_db'] == 'zenario_multisite') {
				$tags['tabs'][3]['errors'][] = \ze\admin::phrase('The database port must be a number.');
			}
		
			if (preg_match('/[^a-zA-Z0-9_]/', $merge['DB_PREFIX'])) {
				$tags['tabs'][3]['errors'][] = \ze\admin::phrase('The table prefix should only contain the characters [a-z, A-Z, 0-9 and _].');
			}
			if (preg_match('/[^a-zA-Z0-9_]/', $merge['DB_PREFIX_GLOBAL']) && $values['3/multi_db'] == 'zenario_multisite') {
				$tags['tabs'][3]['errors'][] = \ze\admin::phrase('The table prefix should only contain the characters [a-z, A-Z, 0-9 and _].');
			}
		
			if (empty($tags['tabs'][3]['errors'])) {
			
				\ze::$dbL = new \ze\db($merge['DB_PREFIX'], $merge['DBHOST'], $merge['DBNAME'], $merge['DBUSER'], $merge['DBPASS'], $merge['DBPORT'], $reportErrors = false);
				
				if (\ze::$dbL && \ze::$dbL->con) {
					// Check for MySQL max_packet_size being too small
					if (($result = @\ze\sql::select("SHOW VARIABLES LIKE 'max_allowed_packet'"))
					 && ($row = \ze\sql::fetchRow($result))
					 && !empty($row[1])) {
						
						$max_allowed_packet = $row[1];
						if ($max_allowed_packet < 4000000) {
							$tags['tabs'][3]['errors'][] =
								\ze\admin::phrase('Your MySQL server\'s max_packet_size variable is set to [[max_allowed_packet]], we recommend you increase this to 16MB (must be at least 4MB). Without this, you may experience problems when uploading large images or other files.',
									['max_allowed_packet' => $max_allowed_packet]);
						}
					}
				
					if (!($result = @\ze\sql::select("SELECT VERSION()"))
						   || !($version = @\ze\sql::fetchRow($result))
						   || !($result = @\ze\sql::select("SHOW TABLES"))) {
						$tags['tabs'][3]['errors'][] = 
							\ze\admin::phrase('You do not have access rights to the database [[DBNAME]].', $merge);
			
					} elseif (!\ze\welcome::compareVersionNumber($version[0], '5.7')) {
					    
						$tags['tabs'][3]['errors'][] = 
							\ze\admin::phrase('Sorry, your MySQL server is "[[version]]". Version 5.7 or later is required.', ['version' => $version[0]]);
			
					} elseif (!(@\ze\sql::update("CREATE TABLE IF NOT EXISTS `zenario_priv_test` (`id` TINYINT(1) NOT NULL )", false, false))
						   || !(@\ze\sql::update("DROP TABLE `zenario_priv_test`", false, false))) {
						$tags['tabs'][3]['errors'][] = 
							\ze\admin::phrase('Cannot verify database privileges. Please ensure the MySQL user [[DBUSER]] has CREATE TABLE and DROP TABLE privileges. You may need to contact your MySQL administrator to have these privileges enabled.',
								$merge['DBUSER']).
							\ze\welcome::installerReportError();
			
					} elseif (@\ze\sql::numRows("SHOW TABLES") > 0) {
						$tags['tabs'][3]['errors'][] = 
							\ze\admin::phrase('Zenario can only be installed on an empty database, but it looks like the stated database already contains tables. Please specify another database that is empty.');
			
					} else {
						while ($tables = \ze\sql::fetchRow($result)) {
							if ($merge['DB_PREFIX'] == '' || substr($tables[0], 0, strlen($merge['DB_PREFIX'])) == $merge['DB_PREFIX']) {
								$tags['tabs'][3]['errors'][] = \ze\admin::phrase('There are already tables in this database that match your chosen table prefix. Please choose a different table prefix, or a different database.');
								break;
							}
						}
					}
					
					if ($values['3/multi_db'] == 'zenario_multisite') {
						\ze::$dbG = new \ze\db($merge['DB_PREFIX_GLOBAL'], $merge['DBHOST_GLOBAL'], $merge['DBNAME_GLOBAL'], $merge['DBUSER_GLOBAL'], $merge['DBPASS_GLOBAL'], $merge['DBPORT_GLOBAL'],$reportErrors = false);
					
						if (\ze::$dbG && \ze::$dbG->con) {
							if (!\ze::$dbG->checkTableDef($merge['DB_PREFIX_GLOBAL']. 'admins', true)) {
								$tags['tabs'][3]['errors'][] =
									\ze\admin::phrase('Connection to the global database succeeded, but the "admins" table (prefixed with your given prefix) does not appear to exist.');
							}
						} else {
							\ze::$dbG = null;
							$tags['tabs'][3]['errors'][] = 
								\ze\admin::phrase('The database name, username and/or password are invalid for the global database.');
						}
					}
				} else {
					\ze::$dbL = null;
					$tags['tabs'][3]['errors'][] = 
						\ze\admin::phrase('Cannot connect to MySQL. It looks like the hostname, database name, username or password are invalid.');
				} 
			}
			
		}
		// To set default timezone
		$fields['4/vis_timezone_settings']['values'] = \ze\dataset::getTimezonesLOV();
		
		//No validation for Step 4, but remember the theme and language chosen
		if ($tags['tab'] > 4 || ($tags['tab'] == 4 && !empty($fields['4/next']['pressed']))) {
			$merge['LANGUAGE_ID'] = $values['4/language_id'];
			$merge['VIS_DATE_FORMAT_SHORT'] = $values['4/vis_date_format_short'];
			$merge['VIS_DATE_FORMAT_MED'] = $values['4/vis_date_format_med'];
			$merge['VIS_DATE_FORMAT_LONG'] = $values['4/vis_date_format_long'];
			$merge['VIS_TIMEZONE_SETTINGS'] = $values['4/vis_timezone_settings'];
			$merge['THEME'] = preg_replace('/\W/', '', $values['4/theme']);
		}
	
		if (empty($fields['1/restore']['pressed'])) {
			//Validation for Step 5: Validate new Admin's details
			if (($tags['tab'] > 5 || ($tags['tab'] == 5 && !empty($fields['5/next']['pressed'])))) {
				$tags['tabs'][5]['errors'] = [];
			
				if (!$merge['admin_first_name'] = $values['5/first_name']) {
					$tags['tabs'][5]['errors'][] = \ze\admin::phrase('Please enter your first name.');
				}
	
				if (!$merge['admin_last_name'] = $values['5/last_name']) {
					$tags['tabs'][5]['errors'][] = \ze\admin::phrase('Please enter your last name.');
				}
	
				if (!$merge['EMAIL_ADDRESS_GLOBAL_SUPPORT'] = $values['5/email']) {
					$tags['tabs'][5]['errors'][] = \ze\admin::phrase('Please enter your email address.');
			
				} elseif (!\ze\ring::validateEmailAddress($merge['EMAIL_ADDRESS_GLOBAL_SUPPORT'])) {
					$tags['tabs'][5]['errors'][] = \ze\admin::phrase('Please enter a valid email address.');
				}
	
				if (!$merge['USERNAME'] = $values['5/username']) {
					$tags['tabs'][5]['errors'][] = \ze\admin::phrase('Please choose an administrator username.');
			
				} elseif (!\ze\ring::validateScreenName($merge['USERNAME'])) {
					$tags['tabs'][5]['errors'][] = \ze\admin::phrase('Your username may not contain any special characters.');
			
				} elseif (preg_replace('/\S/', '', $merge['USERNAME'])) {
					$tags['tabs'][5]['errors'][] = \ze\admin::phrase('Your username may not contain any spaces.');
				}
	
				if (!$merge['PASSWORD'] = $values['5/password']) {
					$tags['tabs'][5]['errors'][] = \ze\admin::phrase('Please choose a password.');
			
				} elseif (!\ze\user::checkPasswordStrength($values['5/password'], $checkIfEasilyGuessable = true)['password_matches_requirements']) {
					$tags['tabs'][5]['errors'][] = \ze\admin::phrase('The password provided does not match the requirements.');
				} elseif ($merge['PASSWORD'] != $values['5/re_password']) {
					$tags['tabs'][5]['errors'][] = \ze\admin::phrase('The password fields do not match.');
				}
			}
		} else {
			//Validation for Step 4/5: Check a backup file exists
			if ((($tags['tab'] != 6 && $tags['tab'] > 5)
			  || ($tags['tab'] == 6 && !empty($fields['6/next']['pressed'])))) {
				$tags['tabs'][6]['errors'] = [];
			
				if (!is_file($values['6/path'])) {
					$tags['tabs'][6]['errors'][] = \ze\admin::phrase('Please enter a path to a file.');
			
				} else
				if (substr($values['6/path'], -4) != '.sql'
				 && substr($values['6/path'], -7) != '.sql.gz'
				 && substr($values['6/path'], -14) != '.sql.encrypted'
				 && substr($values['6/path'], -17) != '.sql.gz.encrypted') {
					$tags['tabs'][6]['errors'][] = \ze\admin::phrase('Please select a backup file.');
			
				} elseif (!is_readable($values['6/path'])) {
					$tags['tabs'][6]['errors'][] = \ze\admin::phrase('Please enter a path to a file with read-access set.');
				}
	
				if (!$merge['EMAIL_ADDRESS_GLOBAL_SUPPORT'] = $values['6/email']) {
					$tags['tabs'][6]['errors'][] = \ze\admin::phrase('Please enter a support email address.');
			
				} elseif (!\ze\ring::validateEmailAddress($merge['EMAIL_ADDRESS_GLOBAL_SUPPORT'])) {
					$tags['tabs'][6]['errors'][] = \ze\admin::phrase('Please enter a valid email address.');
				}
			}
		}
	
		//Validation for Step 6: Attempt to create (if requested) and then validate the siteconfig files
		if ($tags['tab'] == 7 && (!empty($fields['7/ive_done_it']['pressed']) || !empty($fields['7/do_it_for_me']['pressed']))) {
			$tags['tabs'][7]['errors'] = [];
		
			$checkConfigFileExists = \ze\site::checkConfigFileExists();
			if (!empty($fields['7/do_it_for_me']['pressed'])) {
				$permErrors = false;
				if (!$checkConfigFileExists) {
					if (!file_exists(CMS_ROOT. 'zenario_siteconfig.php')) {
						$tags['tabs'][7]['errors'][] =
							\ze\admin::phrase('Please create a file called zenario_siteconfig.php. If you want this installer to populate it, it can be empty but writable.');
				
					} elseif (!@file_put_contents(CMS_ROOT. 'zenario_siteconfig.php', \ze\welcome::readSampleConfigFile($merge,$values['3/multi_db']))) {
						$tags['tabs'][7]['errors'][] =
							\ze\admin::phrase('Could not write to file zenario_siteconfig.php');
						$permErrors = true;
					}
				}
			
				if ($permErrors && !stristr(php_uname('s'), 'win')) {
					$tags['tabs'][7]['errors'][] = \ze\admin::phrase('To correct the file permissions: chmod 666 zenario_siteconfig.php');
				}
		
			} else {
				if ($checkConfigFileExists === false) {
					$tags['tabs'][7]['errors'][] = \ze\admin::phrase('Please create a file named zenario_siteconfig.php in the location shown below.');
			
				} elseif ($checkConfigFileExists === 0) {
					$tags['tabs'][7]['errors'][] = \ze\admin::phrase('Please please enter the text as shown into your zenario_siteconfig.php file.');
				}
			}
		
			if ($checkConfigFileExists) {
				if (!@include_once CMS_ROOT. 'zenario_siteconfig.php') {
					$tags['tabs'][7]['errors'][] = \ze\admin::phrase('There is a syntax error in zenario_siteconfig.php');
				} else {
					foreach (['DBHOST', 'DBNAME', 'DBUSER', 'DBPASS', 'DBPORT', 'DB_PREFIX'] as $constant) {
						if (!defined($constant) || constant($constant) !== $merge[$constant]) {
							$tags['tabs'][7]['errors'][] = \ze\admin::phrase('The constants in zenario_siteconfig.php are not set as below.');
							break;
						}
					}
				}
			}
		
			if (!empty($tags['tabs'][7]['errors'])) {
				$fields['7/ive_done_it']['pressed'] = false;
				$fields['7/do_it_for_me']['pressed'] = false;
			}
		}
	
		//Handle navigation
		switch ($tags['tab']) {
			case 0:
				if (!empty($fields['0/next']['pressed'])) {
					$tags['tab'] = 1;
				}
			
				break;
		
			case 1:
				if (!empty($fields['1/restore']['pressed']) || !empty($fields['1/fresh_install']['pressed'])) {
					$tags['tab'] = 3;
				}
			
				break;
		
			case 3:
				if (!empty($fields['3/previous']['pressed'])) {
					unset($tags['tabs'][3]['errors']);
					$tags['tab'] = 1;
			
				} elseif (!empty($fields['3/next']['pressed'])) {
					if (!empty($fields['1/restore']['pressed'])) {
						$tags['tab'] = 6;
					} else {
						$tags['tab'] = 4;
					}
				}
			
				break;
		
			case 4:
				if (!empty($fields['4/previous']['pressed'])) {
					unset($tags['tabs'][4]['errors']);
					$tags['tab'] = 3;
			
				} elseif (!empty($fields['4/next']['pressed'])) {
					$tags['tab'] = 5;
				}
			
				break;
		
			case 5:
				if (!empty($fields['5/previous']['pressed'])) {
					unset($tags['tabs'][5]['errors']);
					$tags['tab'] = 4;
			
				} elseif (!empty($fields['5/next']['pressed'])) {
					$tags['tab'] = 7;
				}
			
				break;
			
			case 6:
				if (!empty($fields['6/previous']['pressed'])) {
					unset($tags['tabs'][6]['errors']);
					$tags['tab'] = 3;
			
				} elseif (!empty($fields['6/next']['pressed'])) {
					$tags['tab'] = 7;
				}
			
				break;
		
			case 7:
				if (!empty($fields['7/previous']['pressed'])) {
					unset($tags['tabs'][7]['errors']);
				
					if (!empty($fields['1/restore']['pressed'])) {
						$tags['tab'] = 6;
					} else {
						$tags['tab'] = 5;
					}
			
				} elseif (!empty($fields['7/do_it_for_me']['pressed']) || !empty($fields['7/ive_done_it']['pressed'])) {
					$tags['tab'] = 8;
				}
			
				break;
			
			case 8:
				if (!empty($fields['8/previous']['pressed'])) {
					unset($tags['tabs'][8]['errors']);
					$tags['tab'] = 7;
				}
			
				break;
		}
	
		//Don't let the Admin proceed from Step 1 without accepting the license
		if ($tags['tab'] > 0 && !$licenseAccepted) {
			$tags['tab'] = 1;
		}
	
		//Don't let the Admin proceed from Step 3 without a valid database connection
		if ($tags['tab'] > 3 && !empty($tags['tabs'][3]['errors'])) {
			$tags['tab'] = 3;
		}
	
		if (empty($fields['1/restore']['pressed'])) {
			//Don't let the Admin proceed from Step 5 without entering their admin details
			if ($tags['tab'] > 5 && !empty($tags['tabs'][5]['errors'])) {
				$tags['tab'] = 5;
			}
		} else {
			//Don't let the Admin proceed from Step 4/5 without selecting a backup
			if ($tags['tab'] > 6 && !empty($tags['tabs'][6]['errors'])) {
				$tags['tab'] = 6;
			}
		}
	
		//Don't let the Admin proceed from Step 6 without setting the siteconfig up correctly
		if ($tags['tab'] > 7 && !empty($tags['tabs'][7]['errors'])) {
			$tags['tab'] = 7;
		}
	
		//Display the current step
		switch ($tags['tab']) {
			case 0:
			
				if ($installStatus == 0) {
					$fields['0/reason']['snippet']['html'] = 
						\ze\admin::phrase('The Zenario configuration file [[file]] is empty or missing.',
							['file' => '<code>zenario_siteconfig.php</code>']);
			
				} elseif ($installStatus == 1) {
					$fields['0/reason']['snippet']['html'] = 
						\ze\admin::phrase('A database connection could not be established.');
			
				} elseif ($installStatus == 2) {
					$fields['0/reason']['snippet']['html'] = 
						\ze\admin::phrase('The database is empty, or contains no tables with the specified prefix.');
				}
			
				break;
		
			case 1:
				if (is_file(CMS_ROOT. 'license.txt')) {
					$fields['1/license']['snippet']['url'] = 'license.txt';
			
				} else {
					echo \ze\admin::phrase('The license file "license.txt" could not be found. Please add the license file, so this installer can proceed.');
					exit;
				}
			
				break;
		
			case 3:
				//Nothing doing for step 3
			
				break;
		
			case 4:
			
				//Old code for sample sites, commented out as we don't currently use them
				//$fields['4/sample']['values'] = [
				//	0 => [
				//		'label' => \ze\admin::phrase('Blank Site:'),
				//		'note_below' => \ze\admin::phrase('An empty site, ready for you to choose a language.')]];
				//
				//foreach (listSampleSites() as $dir => $path) {
				//	$fields['4/sample']['values'][$dir] = [
				//		'label' => $dir. ': ',
				//		'note_below' => file_get_contents($path. 'description.txt')];
				//}
			
				//Format the labels for the date format picker fields
				if (empty($fields['4/language_id']['values'])) {
					$fields['4/language_id']['values'] = [];
				
					$ord = 0;
					$languages = require CMS_ROOT. 'zenario/includes/language_names.inc.php';
					foreach ($languages as $langId => $phrases) {
						$fields['4/language_id']['values'][$langId] = $phrases[0]. ' ('. $langId. ')';
					}
				
					\ze\miscAdm::formatDateFormatSelectList($fields['4/vis_date_format_short'], true);
					\ze\miscAdm::formatDateFormatSelectList($fields['4/vis_date_format_med']);
					\ze\miscAdm::formatDateFormatSelectList($fields['4/vis_date_format_long']);
				}
			
				//Add logic if English is not chosen..?
				//if (!empty($values['4/language_id'])
				// && !\ze\ring::chopPrefix('en-', $values['4/language_id'])) {
				//	
				//}
			
				if (empty($values['4/theme'])) {
					$values['4/theme'] = INSTALLER_DEFAULT_THEME;
				}
				
				$skinThumbnails = [];
				foreach (\ze\welcome::listSampleThemes() as $dir => $imageSrc) {
					$skinThumbnails[$dir] = '<img src="'. htmlspecialchars($imageSrc). '"/>';
				}
				\ze\welcome::setupRadioSelectorValues('theme', $fields['4/theme'], $skinThumbnails, $values['4/theme']);
			
				break;
		
			case 5:
			
				//Quick hack - this function doesn't seem to work with a partial db-connection, so just clear it quickly
				$db = \ze::$dbL;
				\ze::$dbL = null;
				
					$fields['5/password_message']['snippet']['html'] = self::passwordMessageSnippet(($fields['5/password']['current_value'] ?? false), $isInstaller = true);
				
				//Restore the connection
				\ze::$dbL = $db;
			
				break;
		
			case 6:
				$fields['6/path']['value'] = CMS_ROOT;
				$fields['6/path_status']['snippet']['html'] = '&nbsp;';
			
			
				break;
		
			case 7:
				$fields['7/zenario_siteconfig']['pre_field_html'] =
					'<pre>'. CMS_ROOT. 'zenario_siteconfig.php'. ':</pre>';
				$fields['7/zenario_siteconfig']['value'] = \ze\welcome::readSampleConfigFile($merge,$values['3/multi_db']);
				unset($values['7/zenario_siteconfig']);
			
			
				break;
		
			case 8:
				$tags['tabs'][8]['errors'] = [];
			
				if (!defined('SHOW_SQL_ERRORS_TO_VISITORS')) {
					define('SHOW_SQL_ERRORS_TO_VISITORS', true);
				}
			
				$merge['URL'] = \ze\link::protocol(). $_SERVER['HTTP_HOST'];
				$merge['HTTP_HOST'] = $_SERVER['HTTP_HOST'];
				$merge['SUBDIRECTORY'] = SUBDIRECTORY;
			
				//Set the latest revision number
				$merge['LATEST_REVISION_NO'] = LATEST_REVISION_NO;
				$merge['INSTALLER_REVISION_NO'] = INSTALLER_REVISION_NO;
				
				\ze\dbAdm::getTableEngine();
				$merge['ZENARIO_TABLE_ENGINE'] = ZENARIO_TABLE_ENGINE;
				$merge['ZENARIO_TABLE_CHARSET'] = ZENARIO_TABLE_CHARSET;
				$merge['ZENARIO_TABLE_COLLATION'] = ZENARIO_TABLE_COLLATION;
			
			
				//Install to the database
				\ze\sql::cacheFriendlyUpdate('SET NAMES "utf8mb4"');
				\ze\sql::cacheFriendlyUpdate("SET collation_connection='utf8mb4_unicode_ci'");
				\ze\sql::cacheFriendlyUpdate("SET collation_server='utf8mb4_unicode_ci'");
				\ze\sql::cacheFriendlyUpdate("SET character_set_client='utf8mb4'");
				\ze\sql::cacheFriendlyUpdate("SET character_set_connection='utf8mb4'");
				\ze\sql::cacheFriendlyUpdate("SET character_set_results='utf8mb4'");
				\ze\sql::cacheFriendlyUpdate("SET character_set_server='utf8mb4'");
				
				//Define the DB connection info, if it wasn't previously
				\ze::define('DBHOST', $merge['DBHOST']);
				\ze::define('DBNAME', $merge['DBNAME']);
				\ze::define('DBUSER', $merge['DBUSER']);
				\ze::define('DBPASS', $merge['DBPASS']);
				\ze::define('DBPORT', $merge['DBPORT']);
				\ze::define('DB_PREFIX', $merge['DB_PREFIX']);
				
				//Previously the "from" address was the same as the admin support email address.
				//The default "from" address now should contain the server user string (e.g. "www-data" for Apache),
				//and the server name.
				$merge['EMAIL_ADDRESS_FROM'] = getenv('APACHE_RUN_USER') . '@' . $_SERVER['SERVER_NAME'];
			
				//Here we restore a backup, and/or do a fresh install, depending on the choices in the installer.
				//(Installing a sample site is done by restoring a backup, then immediately doing a fresh install.)
			
				$doFreshInstall = !empty($fields['1/fresh_install']['pressed']);
				$restoreBackup = !$doFreshInstall && !empty($fields['1/restore']['pressed']);
			
				//Old code for sample sites, commented out as we don't currently use them
				//$installSampleSite = $doFreshInstall && !empty($values['4/sample']);
			
				//Try to restore a backup file
				if ($restoreBackup) {
					//Set the primary_domain to the current domain, reguardless of what it is in the backup
					\ze::$siteConfig[0]['primary_domain'] = $_SERVER['HTTP_HOST'];
				
					//Restore a backup, using either the specified path or the sample install path
				
					//Old code for sample sites, commented out as we don't currently use them
					//if ($installSampleSite) {
					//	$backupPath = '';
					//	foreach (listSampleSites() as $dir => $path) {
					//		if ($dir == $values['4/sample']) {
					//			$backupPath = $path. 'backup.sql.gz';
					//		}
					//	}
					//} else {
						$backupPath = $values['6/path'];
					//}
				
					//Little hack - the mysql_path and mysqldump_path site settings won't be set,
					//so temporarily set  them to their default values just before we attempt the restore.
					\ze\site::setSetting('mysqldump_path', 'PATH', $updateDB = false);
					\ze\site::setSetting('mysql_path', 'PATH', $updateDB = false);
				
				
					$failures = [];
					if (\ze\dbAdm::restoreFromBackup($backupPath, $failures, false)) {
						//If the restore was successful:
							//Clear any session that this admin may have had from a previous installation.
						\ze\admin::unsetSession();
					
					} else {
						//If the restore was unsuccessful:
							//Show any error messages we got.
						foreach ($failures as $text) {
							$tags['tabs'][8]['errors'][] = $text;
						}
					}
				}
			
				//Set up tables in a fresh installation
				if ($doFreshInstall && empty($tags['tabs'][8]['errors'])) {
					//Old code for sample sites, commented out as we don't currently use them
					//if ($installSampleSite) {
					//	$files = ['local-admin-CREATE.sql', 'local-sample-INSERT.sql'];
					//} else {
						set_time_limit(60 * 10);
						$files = ['local-CREATE.sql', 'local-admin-CREATE.sql', 'local-INSERT.sql'];
					//}
				
					$error = false;
					foreach ($files as $file) {
						if (!\ze\welcome::runSQL('zenario/admin/db_install/', $file, $error, $merge)) {
							$tags['tabs'][8]['errors'][] = $error;
							break;
						}
					}
					
					//Insert the starter images from the starter_images folder
					\ze\welcome::addStarterImagesToDB();
					
					
					//Was the install successful?
					if (empty($tags['tabs'][8]['errors'])) {
					
						//Define the main email address for the remaining run of this script
						//(This is just needed this once; the next time a script run this should
						// be defined by the config file.)
						if (!defined('EMAIL_ADDRESS_GLOBAL_SUPPORT')) {
							define('EMAIL_ADDRESS_GLOBAL_SUPPORT', $merge['EMAIL_ADDRESS_GLOBAL_SUPPORT']);
						}
					
						//Populate the Modules table with all of the Modules in the system,
						//and install and run any Modules that should running by default.
						\ze\moduleAdm::addNew($skipIfFilesystemHasNotChanged = false, $runModulesOnInstall = true, $dbUpdateSafeMode = true);
					
						//If a language is picked, enable that language by default,
						//import any phrase packs and create special pages such as the home page.
						if ($merge['LANGUAGE_ID']
						 && ($languages = require CMS_ROOT. 'zenario/includes/language_names.inc.php')
						 && (!empty($languages[$merge['LANGUAGE_ID']]))) {
						
							$translatePhrases = 1;
							$searchType = 'full_text';
							switch ($merge['LANGUAGE_ID']) {
								case 'en':
								case 'en-gb':
								case 'en-us':
									$translatePhrases = 0;
									break;
								case 'zh-hans':
								case 'zh-hant':
								case 'ko':
								case 'ja':
								case 'vi':
									$searchType = 'simple';
									break;
							}
						
							\ze\row::set('languages', [
								'detect' => 0,
								'detect_lang_codes' => '',
								'translate_phrases' => $translatePhrases,
								'search_type'=> $searchType
								], 
								$merge['LANGUAGE_ID']);
						
							//Set this language as the default language
							\ze\site::setSetting('default_language', \ze::$defaultLang = $merge['LANGUAGE_ID']);

							//Import any phrases for Modules that use phrases
							\ze\contentAdm::importPhrasesForModules($merge['LANGUAGE_ID']);
						}
					
						\ze\site::setSetting('vis_date_format_short', $merge['VIS_DATE_FORMAT_SHORT']);
						\ze\site::setSetting('vis_date_format_med', $merge['VIS_DATE_FORMAT_MED']);
						\ze\site::setSetting('vis_date_format_long', $merge['VIS_DATE_FORMAT_LONG']);
						\ze\site::setSetting('zenario_timezones__default_timezone', $merge['VIS_TIMEZONE_SETTINGS']);
						\ze\site::setSetting('vis_date_format_datepicker', \ze\miscAdm::convertMySQLToJqueryDateFormat($merge['VIS_DATE_FORMAT_SHORT']));
						\ze\site::setSetting('organizer_date_format', \ze\miscAdm::convertMySQLToJqueryDateFormat($merge['VIS_DATE_FORMAT_MED']));
					
						//Set a random key that is linked to the site.
						\ze\site::setSetting('site_id', \ze\dbAdm::generateRandomSiteIdentifierKey());
		
						//Set a random first data-revision number
						//(If someone is doing repeated fresh installs, this stops data from an old one getting cached and used in another)
						\ze\row::set('local_revision_numbers', ['revision_no' => rand(1, 32767)], ['path' => 'data_rev', 'patchfile' => 'data_rev']);
					
						//Create an Admin, and give them all of the core permissions.
						//Also set the login IP address and browser information.
						
						require_once CMS_ROOT. 'zenario/libs/manually_maintained/mit/browser/lib/browser.php';
						$browser = new \Browser();
						
						$details = [
							'username' => $merge['USERNAME'],
							'first_name' => $merge['admin_first_name'],
							'last_name' => $merge['admin_last_name'],
							'email' => $merge['EMAIL_ADDRESS_GLOBAL_SUPPORT'],
							'created_date' => \ze\date::now(),
							'status' => 'active',
							'last_login' => \ze\date::now(),
							'last_login_ip' => \ze\escape::sql(\ze\user::ip()),
							'last_browser' => \ze\escape::sql($browser->getBrowser()),
							'last_browser_version' => \ze\escape::sql($browser->getVersion()),
							'last_platform' => \ze\escape::sql($browser->getPlatform())
							
						];
					
						$adminId = \ze\row::insert('admins', $details);
						\ze\adminAdm::setPassword($adminId, $merge['PASSWORD']);
						\ze\adminAdm::savePerms($adminId, 'all_permissions');
						
						\ze\admin::setSession($adminId);
						$_SESSION['admin_ip_at_login'] = \ze\user::ip();
					
						//Prepare email to the installing person
						$message = $source['email_templates']['installed_cms']['body'];
					
						$subject = $source['email_templates']['installed_cms']['subject'];
					
						foreach ($merge as $pattern => $replacement) {
							$message = str_replace('[['. $pattern. ']]', $replacement, $message);
						}
						
						\ze\server::sendEmailSimple(
							$subject, $message, $isHTML = true,
							//CMS welcome emails should always be sent to the intended recipient even if debug mode is on.
							$ignoreDebugMode = true,
							$addressTo = $merge['EMAIL_ADDRESS_GLOBAL_SUPPORT'], $nameTo = false,
							$addressFrom = false, $nameFrom = $source['email_templates']['installed_cms']['from']
						);
					
					
						//Apply database updates
						$moduleErrors = '';
						\ze\dbAdm::checkIfUpdatesAreNeeded($moduleErrors, $andDoUpdates = true);
						
						//Reset the cached table details, in case any of the definitions are out of date
						\ze\dbAdm::resetTableDefs();
					
						//Fix a bug where sample sites might not have a default language set, by setting a default language if any content has been created
						if (!\ze::$defaultLang && ($langId = \ze\row::get('content_items', 'language_id', []))) {
							\ze\site::setSetting('default_language', \ze::$defaultLang = $langId);
						}
						
						//Set the "Email from" and "Organizer title" setting from the Organisation name field, if that was provided
						if ($values['4/organisation_name']) {
							\ze\site::setSetting('email_name_from', (string)$values['4/organisation_name']);
							\ze\site::setSetting('organizer_title', \ze\admin::phrase('Organizer for [[organisation_name]]', $values));
						}

						if ($values['7/site_enabled'] == 'enabled') {
							\ze\site::setSetting('site_enabled', 1);
						} else {
							\ze\site::setSetting('site_enabled', '');
						}
					
						\ze\welcome::postInstallTasks($values['4/theme'], $values['4/logo']);
					}
				}
			
				if (!empty($tags['tabs'][8]['errors'])) {
					//Did something go wrong? Remove any tables that were created.
					\ze\welcome::runSQL('zenario/admin/db_install/', 'local-DROP.sql', $error, $merge);
					\ze\welcome::runSQL('zenario/admin/db_install/', 'local-admin-DROP.sql', $error, $merge);
					\ze\welcome::runSQL('zenario/admin/db_install/', 'local-old-DROP.sql', $error, $merge);
				
					foreach (\ze\dbAdm::lookupExistingCMSTables($dbUpdateSafeMode = true) as $table) {
						if ($table['reset'] == 'drop') {
							\ze\sql::cacheFriendlyUpdate("DROP TABLE `". \ze\escape::sql($table['actual_name']). "`");
						}
					}
				} else {
					return true;
				}
			
				break;
		}
	
		return false;
	}
	
	//Setup a radio-selector box
	public static function setupRadioSelectorValues($fieldCodeName, &$field, $lov, $currentValue = '') {
		
		$ord = 0;
		$field['values'] = [];
		
		foreach ($lov as $val => $html) {
			$field['values'][$val] = [
				'ord' => ++$ord,
				'label' => '',
				'post_field_html' =>
					'<label
						for="'. htmlspecialchars($fieldCodeName). '___'. htmlspecialchars($val). '"
						id="radio_selector_box___'. htmlspecialchars($val). '"
						class="radio_selector_box '. ($val == $currentValue? 'radio_selector_box_selected' : ''). '"
					>
						'. $html. '
					</label>',
				'onchange' => '
					$(".radio_selector_box").removeClass("radio_selector_box_selected");
					$("#radio_selector_box___'. htmlspecialchars(\ze\escape::js($val)). '").addClass("radio_selector_box_selected");
				'
			];
		}
	}
	
	//Insert the starter images from the starter_images/ directory (also using the yaml file for their metadata).
	public static function addStarterImagesToDB() {
		$tags = \ze\tuix::readFile('zenario/admin/db_install/starter_image_list.yaml', false);
		if ($tags) {
			foreach ($tags['imagelist'] as $image) {
				$imagepath = 'zenario/admin/db_install/starter_images/'. $image['name'];
				$imageId = \ze\welcome::addImageToDatabase('image', $imagepath, $image['name'], $image['alt_tag'], $image['mime_type']);
				
				#//For the starter images, we'll make sure they're initially public images, not auto or private.
				#if ($imageId) {
				#	\ze\row::update('files', ['privacy' => 'public'], $imageId);
				#	\ze\file::addPublicImage($imageId);
				#}
				
				//N.b. the above code was taking a long time to run and caused "Maximum execution time" timeouts in the installer.
				//I've removed it for now.
			}
		}
	}
	
	//
	public static function addImageToDatabase($usage, $imagePath, $imageName = false, $imageAltTag = false, $imageMimeType = false) {
		return \ze\file::addToDatabase($usage, $imagePath, $imageName, true, false, false, $imageAltTag, false, false, $imageMimeType);
	}
	
	//Some tasks that should be run immediately after a fresh install or site reset.
	public static function postInstallTasks($skin = '', $uploadedLogo = '') {
		
		//Zenario has lots of metadata tables, that might not have been correctly populated
		//by the hand-written installer SQL after a fresh install or site reset.
		//We'll automatically populate them now
		
		
		//Check a skin was selected during the install (or default to the default skin if not set).
		if (empty($skin) || !is_dir(CMS_ROOT. 'zenario_custom/skins/'. $skin)) {
			$skin = INSTALLER_DEFAULT_THEME;
		}
		
		$logoName = $logoAltTag = $logoPath = false;
		
		//Check if this skin has the starter_images.yaml file inside
		if (is_file($yamlPath = ($dir = CMS_ROOT. 'zenario_custom/skins/'. $skin. '/installer/'). 'starter_images.yaml')) {
			if ($tags = \ze\tuix::readFile($yamlPath, false)) {
				
				//If a default favicon is defined in the file, set it in the site settings
				if ($image = $tags['favicon'] ?? null) {
					$imagePath = $dir. 'starter_images/'. $image['name'];
					if ($imageId = \ze\welcome::addImageToDatabase('site_setting', $imagePath, $image['name'], $image['alt_tag'])) {
						\ze\site::setSetting('favicon', $imageId);
					}
				}
				
				//Also note if a default logo is selected (though this can be changed).
				if ($image = $tags['logo'] ?? null) {
					$logoName = $image['name'];
					$logoAltTag = $image['alt_tag'];
					$logoPath = $dir. 'starter_images/'. $image['name'];
				}
			}
		}
		
		//If a logo was uploaded during the installer, this should be used instead of the default one.
		if ($uploadedLogo
		 && ($path = \ze\file::getPathOfUploadInCacheDir($uploadedLogo))) {
			$logoName =
			$logoAltTag = false;
			$logoPath = $path;
		}
		
		//If we have a logo from either of the above, use it in the site
		if ($logoPath
		 && ($imageId = \ze\welcome::addImageToDatabase('image', $logoPath, $logoName, $logoAltTag))) {
			
			//Change the image in the banner created on the site-wide header to use this image
			\ze\row::update('plugin_settings',
				[
					'value' => $imageId,
					'foreign_key_id' => $imageId
				],
				['name' => 'image', 'instance_id' => 1, 'egg_id' => 0]
			);
			
			//Also set the image as the image on the login screen
			$imageId = \ze\welcome::addImageToDatabase('site_setting', $logoPath, $logoName, $logoAltTag);
			
			\ze\site::setSetting('brand_logo', 'custom');
			\ze\site::setSetting('custom_logo', $imageId);
			
			//Unless the image was a SVG, also set it as the default og:image
			if (\ze\file::isImage(\ze\file::mimeType($logoPath))) {
				\ze\site::setSetting('default_icon', $imageId);
			}
		}
		
		
		//Populate the menu_hierarchy and the menu_positions tables
		\ze\menuAdm::recalcAllHierarchy();
		
		//If any content items were pre-created in the installer SQL,
		//run the syncInlineFileContentLink() function to make sure their
		//metadata is up to date.
		foreach (\ze\sql::select('
			SELECT id, type, admin_version
			FROM '. DB_PREFIX. 'content_items'
		) as $content) {
			\ze\contentAdm::syncInlineFileContentLink($content['id'], $content['type'], $content['admin_version']);
		}
		
		//And the same for plugins
		foreach (\ze\sql::select('
			SELECT id, content_id, content_type, content_version, is_nest, is_slideshow
			FROM '. DB_PREFIX. 'plugin_instances
			WHERE content_id = 0'
		) as $instance) {
			\ze\contentAdm::resyncLibraryPluginFiles($instance['id'], $instance);
		}
	
		//Update the special pages, creating new ones if needed
		\ze\contentAdm::addNeededSpecialPages();
	}

	public static function enableCaptchaForAdminLogins() {
		//Admins will be required to enter CAPTCHA if the setting is turned on,
		//the API keys are correctly set up,
		//and any of:
		//	-the admin hasn't logged in before
		//	-their cookie has expired
		//	-they haven't entered CAPTCHA before
		//	-the cookie value doesn't match the value stored in the DB
		//	(e.g. someone is trying to hack the site).

		//Please note: before Captcha can be used, the JS library needs to be loaded.
		//For the admin login screen, this is done in zenario/admin/welcome.php.
		
		$acsn = \ze\welcome::adminCaptchaSettingName();
		$time = false;
		$cookieTimeout = -(COOKIE_TIMEOUT / 86400);
		
		return \ze::setting('google_recaptcha_site_key')
			&& \ze::setting('google_recaptcha_secret_key')
			&& \ze\site::description('enable_captcha_for_admin_logins')
			&& (
				(empty($_COOKIE['COOKIE_LAST_ADMIN_USER']))
				|| (empty($_COOKIE[\ze\welcome::adminCaptchaCookieName()]))
				|| (!empty($acsn)
					&& !empty($time = \ze\admin::setting($acsn))
					//The securityCodeTime() function was written for 2FA security codes,
					//but can be used for CAPTCHA time calculations without any modifications.
			 		&& $time > \ze\welcome::securityCodeTime($cookieTimeout)
				)
				|| (!empty($_COOKIE['COOKIE_LAST_ADMIN_USER'])
					&& !empty($_COOKIE[\ze\welcome::adminCaptchaCookieName()])
					&& !empty($lastAdminUserValue = preg_replace('@[^-_=\w]@', '', $_COOKIE['COOKIE_LAST_ADMIN_USER']))
					&& !empty($acsn)
					//Can't use ze\admin::setting because there is no admin ID set in the session yet.
					&& !\ze\row::exists('admin_settings', ['name' => $acsn])
				)
			);
	}

	public static function loginAJAX(&$source, &$tags, &$fields, &$values, $changes, $getRequest) {
		$passwordReset = false;
		$tags['tabs']['login']['errors'] = [];
		$tags['tabs']['forgot']['errors'] = [];

		//Secure/not secure connection
		if (\ze\link::isHttps()) {
			$fields['login/not_secure_connection']['hidden'] = true;
		} else {
			$fields['login/secure_connection']['hidden'] = true;
		}
		\ze\lang::applyMergeFields($fields['login/admin_link']['snippet']['html'], [
			'site_url' => htmlspecialchars(\ze\link::protocol(). \ze\link::adminDomain(). SUBDIRECTORY)
		]);
	
		if ($tags['tab'] == 'login' && !empty($fields['login/previous']['pressed'])) {
			$tags['go_to_url'] = \ze\welcome::redirectAdmin($getRequest);
			return false;
	
		//Go between the login and the forgot password screens
		} elseif ($tags['tab'] == 'login' && !empty($fields['login/forgot']['pressed'])) {
			$tags['tab'] = 'forgot';
	
		} elseif ($tags['tab'] == 'forgot' && !empty($fields['forgot/previous']['pressed'])) {
			$tags['tab'] = 'login';
	
		//Check a login attempt
		} elseif ($tags['tab'] == 'login' && !empty($fields['login/login']['pressed'])) {
			//0.5 second delay between login attempts if there are no errors
			//Please note: usleep uses microseconds. There is no PHP function that accepts milliseconds.
			usleep(500000);

			if (empty($_SESSION['failed_login_count_since_last_successful_login'])) {
				$_SESSION['failed_login_count_since_last_successful_login'] = 0;
			}

			$errorsExist = false;

			//Call the standard TUIX validation, as there's a captcha on this screen that needs validating,
			//and this function has the logic to handle it.
			\ze\tuix::applyValidation($tags['tabs']['login'], true);
			
			if (!$values['login/username']) {
				$tags['tabs']['login']['errors'][] = \ze\admin::phrase('Please enter your administrator username or registered email.');
				
				if (!$errorsExist) {
					$errorsExist = true;
				}
			}
			
			if (!$values['login/password']) {
				$tags['tabs']['login']['errors'][] = \ze\admin::phrase('Please enter your administrator password.');
				
				if (!$errorsExist) {
					$errorsExist = true;
				}
			}

			if (\ze\welcome::enableCaptchaForAdminLogins() && !$values['login/admin_login_captcha']) {
				$tags['tabs']['login']['errors'][] = \ze\admin::phrase('Please complete the CAPTCHA.');
			}
			
			if (empty($tags['tabs']['login']['errors'])) {
				$details = [];
			
				$adminIdL = \ze\admin::checkPassword($values['login/username'], $details, $values['login/password'], $checkViaEmail = false, $checkBoth = true);
			
				if (!$adminIdL || \ze::isError($adminIdL)) {
					//Further 2.5 second delay between login attempts if there are errors
					usleep(2500000);

					$tags['tabs']['login']['errors']['details_wrong'] = \ze\admin::phrase('_ERROR_ADMIN_LOGIN_USERNAME');
					
					if (!$errorsExist) {
						$_SESSION['failed_login_count_since_last_successful_login']++;
						$errorsExist = true;
					}
					
					//Be nasty and reset the captcha if they got the username or password wrong!
					//Also invalidate the "Captcha passed" cookie and admin setting.
					\ze\welcome::clearLastLoggedInAdminCookieAfter3FailedLogins();

					if (\ze\welcome::enableCaptchaForAdminLogins()) {
						$values['login/admin_login_captcha'] = '';
						$fields['login/admin_login_captcha']['hidden'] = false;
					}

					return false;
				} else {
					\ze\admin::logIn($adminIdL, $values['login/remember_me']);
					
					if (\ze\welcome::enableCaptchaForAdminLogins() && !empty($_COOKIE['COOKIE_LAST_ADMIN_USER'])) {
						
						//If the CAPTCHA is correct, save a cookie...
						\ze\cookie::set(
							\ze\welcome::adminCaptchaCookieName(),
							hash('sha256', $_COOKIE['COOKIE_LAST_ADMIN_USER'] . \ze::setting('site_id'))
						);
				
						//...and an admin setting to remember it next time!
						//The securityCodeTime() function was written for 2FA security codes,
						//but can be used for CAPTCHA time calculations without any modifications.
						$scsn = \ze\welcome::adminCaptchaSettingName();
						$time = \ze\welcome::securityCodeTime();
						\ze\admin::setSetting($scsn, $time);
					}
					
					return true;
				}
			} else {
				\ze\welcome::clearLastLoggedInAdminCookieAfter3FailedLogins();
				$fields['login/admin_login_captcha']['hidden'] = !\ze\welcome::enableCaptchaForAdminLogins();
				
				//Further 2.5 second delay between login attempts if there are errors
				usleep(2500000);
				return false;
			}
	
		//Reset a password
		} elseif ($tags['tab'] == 'forgot' && !empty($fields['forgot/reset']['pressed'])) {
		
			if (!$values['forgot/email']) {
				$tags['tabs']['forgot']['errors'][] = \ze\admin::phrase('Please enter your email address.');
		
			} elseif (!\ze\ring::validateEmailAddress($values['forgot/email'])) {
				$tags['tabs']['forgot']['errors'][] = \ze\admin::phrase('Please enter a valid email address.');
		
			//Check for the user by email address, without specifying their password.
			//This will return 0 if the user wasn't found, otherwise it should return
			//false (for user found but password didn't match)
			} elseif (!$admin = \ze\row::get('admins', ['id', 'authtype', 'username', 'email', 'first_name', 'last_name', 'status'], ['email' => $values['forgot/email']])) {
				//If email address doesn't exist, don't actually tell the user!
				$passwordReset = true;
				$tags['tab'] = 'login';
		
			} else {
				$isMultisiteAdmin = isset($admin['authtype']) && $admin['authtype'] != 'local';
				$isTrashedAdmin = !$isMultisiteAdmin && $admin['status'] == 'deleted';
				//Trashed admins should not be able to trigger password resets, just as if the account didn't exist
				if ($isTrashedAdmin) {
					$passwordReset = true;
					$tags['tab'] = 'login';
		
				} else {
			
					//Prepare email to the mail with the reset password
					$merge = [];
					$merge['NAME'] = \ze::ifNull(trim($admin['first_name']. ' '. $admin['last_name']), $admin['username']);
					$merge['USERNAME'] = $admin['username'];
					$merge['URL'] = \ze\link::protocol(). $_SERVER['HTTP_HOST'];
					$merge['SUBDIRECTORY'] = SUBDIRECTORY;
					$merge['IP'] = preg_replace('[^W\.\:]', '', \ze\user::ip());
				
					//Multisite admins shouldn't be trying to change their passwords on a local site
					if (isset($admin['authtype']) && $admin['authtype'] != 'local') {
						$emailTemplate = 'reset_password_multisite_admin';
					} else {
						$merge['PASSWORD'] = \ze\admin::resetPassword($admin['id']);
						$emailTemplate = 'reset_password_local_admin';
					}
				
					$message = $source['email_templates'][$emailTemplate]['body'];
					$message = nl2br($message);
				
					if (\ze\module::inc('zenario_email_template_manager')) {
						\zenario_email_template_manager::putBodyInTemplate($message);
					}
			
					$subject = $source['email_templates'][$emailTemplate]['subject'];
			
					foreach ($merge as $pattern => $replacement) {
						$message = str_replace('[['. $pattern. ']]', $replacement, $message);
					};
					
					\ze\server::sendEmailSimple(
						$subject, $message, $isHTML = true,
						//Admin password reset emails should always be sent to the intended recipient even if debug mode is on.
						$ignoreDebugMode = true,
						$addressTo = $admin['email'], $nameTo = $merge['NAME'],
						$addressFrom = false, $nameFrom = $source['email_templates'][$emailTemplate]['from']
					);
			
					$passwordReset = true;
					$tags['tab'] = 'login';
				}
			}
		}
	
		//Format the login screen
		if ($tags['tab'] == 'login') {
			if (!empty($_COOKIE['COOKIE_LAST_ADMIN_USER'])) {
				$fields['login/username']['value'] = $_COOKIE['COOKIE_LAST_ADMIN_USER'];
			}

			//As of 10 Jun 2021, the "remember me" default value will always be true.
			//Commented out the old logic.
			//$fields['login/remember_me']['value'] = empty($_COOKIE['COOKIE_DONT_REMEMBER_LAST_ADMIN_USER']);
			$fields['login/remember_me']['value'] = true;
		
			//Don't show the note about the admin login link if it is turned off in Site Settings
			if (\ze\link::adminDomainIsPrivate()) {
				$fields['login/remember_me']['note_below'] = '';
			}
		
			if ($passwordReset) {
				$fields['login/reset']['hidden'] = false;
				$fields['login/description']['hidden'] = true;
			} else {
				$fields['login/reset']['hidden'] = true;
				$fields['login/description']['hidden'] = false;
			}
			
			$fields['login/admin_login_captcha']['hidden'] = !\ze\welcome::enableCaptchaForAdminLogins();
		}
	
		return false;
	}

	public static function updateNoPermissionsAJAX(&$source, &$tags, &$fields, &$values, $changes, &$task, $getRequest) {
		//Handle the "back to site" button
		if (!empty($fields['0/previous']['pressed'])) {
			\ze\welcome::logoutAdminAJAX($tags, $getRequest);
			return;
	
		} else {
		       $tags['tabs'][0]['errors'][0] = \ze\admin::phrase("Sorry, you do not have permission to apply a database update to this site. An email has been sent to the Zenario system administrator to ask them to do this.");
		       
				if(!isset($_SESSION["mailSent"])){//send mail once to global support.
				    
				    $subject = \ze\admin::phrase("Admin has no permission to apply db updates") ;
		            $message = \ze\admin::phrase('Dear Admin,')."\n\n". \ze\admin::phrase('The admin "').$_SESSION['admin_username'].\ze\admin::phrase('" has no permission to apply db updates for the site ').\ze\link::absolute()."admin. \n\n".\ze\admin::phrase("Thanks.");
					
					\ze\server::sendEmailSimple(
						$subject, $message, $isHTML = false,
						//No permissions warnings should always be sent to the intended recipient even if debug mode is on.
						$ignoreDebugMode = true
					);

					$_SESSION["mailSent"]= $mailSent ;  
				}			
			
		  
							    
			$admins = \ze\row::getAssocs(
				'admins',
				['first_name', 'last_name', 'username', 'authtype'],
				[
					'status' => 'active',
					'id' => \ze\row::getAssocs(
						'action_admin_link',
						'admin_id',
						['action_name' => ['_ALL', '_PRIV_APPLY_DATABASE_UPDATES']])],
				['first_name', 'last_name', 'username', 'authtype']
			);
		
			if (!empty($admins)) {
			    
				$html =
					'<p>'. \ze\admin::phrase('The following administrators are able to apply updates:'). '</p><ul>';
			   
				foreach ($admins as $admin) {
					$html .= '<li>'. htmlspecialchars(\ze\admin::formatName($admin)). '</li>';
				}
			
				$html .= '</ul>';
			
				$fields['0/admins_who_can_do_updates']['hidden'] = false;
				$fields['0/admins_who_can_do_updates']['snippet']['html'] = $html;
			}
		}
	}

	public static function updateAJAX(&$source, &$tags, &$fields, &$values, $changes, &$task) {
		$tags['tab'] = 1;
	
		//Apply updates without prompting when installing, restoring backups or doing site resets
		if ($task == 'install' || $task == 'restore' || $task == 'site_reset' || !empty($fields['1/apply']['pressed'])) {
		
			//Update to the latest version
			set_time_limit(60 * 10);
			$moduleErrors = '';
			$dbUpToDate = \ze\dbAdm::checkIfUpdatesAreNeeded($moduleErrors, $andDoUpdates = true);
		
			if ($dbUpToDate) {
				return true;
			}
		}
	
		$fields['1/description']['snippet']['html'] =
			\ze\admin::phrase('We need to update your database (<code>[[DBNAME]]</code> on <code>[[DBHOST]]</code>) to match.', [
				'DBNAME' => htmlspecialchars(DBNAME),
				'DBHOST' => htmlspecialchars(DBHOST)
			]);
	
	
		if ($tags['tab'] == 1 && !empty($fields['1/why']['pressed'])) {
			$modules = [];
			$revisions = [];
			$currentRevisionNumber = false;
			$latestRevisionNumber = LATEST_REVISION_NO;
	
			//Get details on any database updates needed
			$moduleErrors = '';
			if ($updates = \ze\dbAdm::checkIfUpdatesAreNeeded($moduleErrors, false, false, false)) {
				list($currentRevisionNumber, $modules) = $updates;
			}
		
			if (empty($modules)
			 && empty($currentRevisionNumber)) {
			
				$html =
					'<table class="revisions"><tr class="even"><td>'.
						\ze\admin::phrase('A major Zenario software update needs make changes to your database.').
					'</td></tr></table>';
		
			} else {
				if ($currentRevisionNumber === false) {
					$currentRevisionNumber = $latestRevisionNumber;
				}
		
				if ($currentRevisionNumber != $latestRevisionNumber) {
					$revisions['core'] = ['name' => ' '. \ze\admin::phrase('Core files'), 'from' => $currentRevisionNumber, 'to' => $latestRevisionNumber];
				}
		
				if (!empty($modules)) {
					foreach($modules as $module => $pluginRevisions) {
						$sql = "
							SELECT display_name
							FROM ". DB_PREFIX. "modules
							WHERE class_name = '". \ze\escape::asciiInSQL($module). "'";
				
						if (($result = @\ze\sql::select($sql)) && ($row = \ze\sql::fetchAssoc($result))) {
							$moduleName = ($row['display_name'] ?: $module);
						} else {
							$moduleName = $module;
						}
				
						$revisions[$module] = ['name' => $moduleName, 'from' => $pluginRevisions[0] + 1, 'to' => $pluginRevisions[1]];
					}
				}
		
				\ze\ray::sqlSort($revisions);
		
				$html = '<table class="revisions"><tr><th>'. \ze\admin::phrase('Module'). '</th><th>'. \ze\admin::phrase('Revision Numbers to Apply'). '</th></tr>';
		
				$oddOrEven = 'even';
				foreach ($revisions as $revision) {
					$oddOrEven = $oddOrEven == 'even'? 'odd' : 'even';
					$html .= '<tr class="'. $oddOrEven. '"><td>'. htmlspecialchars($revision['name']). '</td><td>';
			
					if ($revision['from'] == $revision['to']) {
						$html .= \ze\admin::phrase('Revision #[[to]]', $revision);
			
					} elseif ($revision['from'] == 0) {
						$revision['version'] = \ze\site::versionNumber();
						$html .= \ze\admin::phrase('Updates from [[version]]', $revision);
			
					} else {
						$html .= \ze\admin::phrase('Revision #[[from]] through to Revision #[[to]]', $revision);
					}
			
					$html .= '</tr>';
				}
		
				$html .= '</table>';
			}
		
			$fields['1/updates']['snippet']['html'] = $html;
			$fields['1/updates']['hidden'] = false;
		} else {
			$fields['1/updates']['hidden'] = true;
		}
	
		return false;
	}

	//Log the current admin out
	public static function logoutAdminAJAX(&$tags, $getRequest) {
		\ze\admin::unsetSession();
		$tags['_clear_local_storage'] = true;
		$tags['go_to_url'] = \ze\welcome::redirectAdmin($getRequest, true);
	}

	//Return the UNIX time, $offset ago, converted to a string
	public static function securityCodeTime($offset = 0) {
		return str_pad(time() - (int) $offset * 86400, 16, '0', STR_PAD_LEFT);
	}

	//This returns the name that the cookie for the security code should have.
	//This is in the form "COOKIE_ADMIN_SECURITY_CODE_[[ADMIN_ID]]"
	public static function securityCodeCookieName() {
		return 'COOKIE_ADMIN_SECURITY_CODE_'. \ze::session('admin_userid');
	}

	//Get the value of the cookie above
	public static function securityCodeCookieValue() {
		if (isset($_COOKIE[\ze\welcome::securityCodeCookieName()])) {
			return preg_replace('@[^-_=\w]@', '', $_COOKIE[\ze\welcome::securityCodeCookieName()]);
		} else {
			return '';
		}
	}

	//Looks for a security code cookie with the above name,
	//then returns the corresponding name that a site setting should have.
	//This is in the form "COOKIE_ADMIN_SECURITY_CODE_[[COOKIE_VALUE]]", or
	//"COOKIE_ADMIN_SECURITY_CODE_[[COOKIE_VALUE]]_[[IP_ADDRESS]]", depending on
	//whether the apply_two_factor_authentication_by_ip option is set in the site_description.yaml file.
	public static function securityCodeSettingName() {
	
		if ('' == ($sccn = \ze\welcome::securityCodeCookieValue())) {
			 return false;
	
		} elseif (\ze\site::description('apply_two_factor_authentication_by_ip')) {
			return 'COOKIE_ADMIN_SECURITY_CODE_'. $sccn. '_'. \ze\user::ip();

		} else {
			return 'COOKIE_ADMIN_SECURITY_CODE_'. $sccn;
		}
	}

	//Remove very old security codes
	//(But keep ones that have recently expired, so we can show a different message to the admin on the login screen.)
	public static function tidySecurityCodes() {
		$sql = "
			DELETE FROM ". DB_PREFIX. "admin_settings
			WHERE name LIKE 'COOKIE_ADMIN_SECURITY_CODE_%'
			  AND value < '". \ze\escape::sql(\ze\welcome::securityCodeTime(2 * \ze\site::description('two_factor_authentication_timeout'))). "'";
		\ze\sql::update($sql, false, false);
	}

	public static function clearLastLoggedInAdminCookieAfter3FailedLogins() {
		if ($_SESSION['failed_login_count_since_last_successful_login'] >= 3) {
			$cookieName = \ze\welcome::adminCaptchaCookieName();
			\ze\cookie::clear($cookieName);
			$acsn = \ze\welcome::adminCaptchaSettingName();
			\ze\row::delete('admin_settings', ['name' => $acsn]);
		}
	}

	public static function securityCodeAJAX(&$source, &$tags, &$fields, &$values, $changes, &$task, $getRequest, $time) {
	
		$firstTimeHere = empty($_SESSION['COOKIE_ADMIN_SECURITY_CODE']);
		$resend = !empty($fields['security_code/resend']['pressed']);
		$tags['tabs']['security_code']['notices']['email_resent']['show'] = false;
		$tags['tabs']['security_code']['errors'] = [];
		
	
		//Handle the "back to site" button
		if (!empty($fields['security_code/previous']['pressed'])) {
			\ze\welcome::logoutAdminAJAX($tags, $getRequest);
			return false;
	
		//If we've not sent the email yet, or the user presses the retry button, then send the email
		} elseif ($firstTimeHere || $resend) {
		
			if ($firstTimeHere) {
				//Make sure the generated code does not end up being a swear word.
				$code = \ze\ring::randomFromSetNoProfanities();
				$_SESSION['COOKIE_ADMIN_SECURITY_CODE'] = $code;
			}
		
			$admin = \ze\row::get('admins', ['id', 'authtype', 'username', 'email', 'first_name', 'last_name'], $_SESSION['admin_userid'] ?? false);
		
			//Prepare email to the mail with the code
			$merge = [];
			
			//The URL parameter will be displayed without the http or https protocol.
			//The protocol is passed as a separate merge field for the "This is an auto-generated email" string.
			$merge['PROTOCOL'] = \ze\link::protocol();
			$merge['URL'] = $_SERVER['HTTP_HOST'];
			$merge['SUBDIRECTORY'] = SUBDIRECTORY;
			//Remove the trailing slash in the subdirectory.
			$lastCharacter = strlen($merge['SUBDIRECTORY']) - 1;
			if (substr($merge['SUBDIRECTORY'], $lastCharacter) == '/') {
				$merge['SUBDIRECTORY'] = substr($merge['SUBDIRECTORY'], 0, $lastCharacter);
			}
			
			$merge['NAME'] = \ze::ifNull(trim($admin['first_name']. ' '. $admin['last_name']), $admin['username']);
			$merge['USERNAME'] = $admin['username'];
			
			$merge['IP'] = preg_replace('[^W\.\:]', '', \ze\user::ip());
			$merge['2FA_TIMEOUT'] = (int) \ze\site::description('two_factor_authentication_timeout');
			
			//2FA verification code:
			//Display the plain text code to copy-paste into the box...
			$merge['CODE'] = htmlspecialchars($_SESSION['COOKIE_ADMIN_SECURITY_CODE']);
			
			//... and also build a clickable link with the code.
			$getRequestsMerged = '';
			if (!empty($getRequest)) {
				if (isset($getRequest['verification_code'])) {
					unset($getRequest['verification_code']);
				}
				$getRequestsMerged = '&' . http_build_query($getRequest);
			}
			$merge['VERIFICATION_LINK'] = 
				htmlspecialchars(
					\ze\link::protocol()
					. \ze\link::adminDomain() . SUBDIRECTORY
					. 'admin.php?verification_code=' . $_SESSION['COOKIE_ADMIN_SECURITY_CODE']
					. $getRequestsMerged
				);
			
			$emailTemplate = 'security_code';
			$message = $source['email_templates'][$emailTemplate]['body'];
		
			$subject = $source['email_templates'][$emailTemplate]['subject'];
		
			foreach ($merge as $pattern => $replacement) {
				$subject = str_replace('[['. $pattern. ']]', $replacement, $subject);
				$message = str_replace('[['. $pattern. ']]', $replacement, $message);
			}
			
			$message = nl2br($message);
			
			if (\ze\module::inc('zenario_email_template_manager')) {
				\zenario_email_template_manager::putBodyInTemplate($message);
			}
			
			$emailSent = \ze\server::sendEmailSimple(
				$subject, $message, $isHTML = true,
				//Security codes should always be sent to the intended recipient even if debug mode is on.
				$ignoreDebugMode = true,
				$addressTo = $admin['email'], $nameTo = $merge['NAME'],
				$addressFrom = false, $nameFrom = $source['email_templates'][$emailTemplate]['from']
			);
		
			if (!$emailSent) {
				$tags['tabs']['security_code']['errors'][] =
					\ze\admin::phrase('Error! Zenario could not send an email. Please contact your server administrator. 2FA can be disabled if you have access to your zenario_custom/site_description.yaml file.');
			
			} elseif ($resend) {
				$tags['tabs']['security_code']['notices']['email_resent']['show'] = true;
			}
	
		//Check the code if someone is trying to submit it
		} else
		if ((!empty($_SESSION['COOKIE_ADMIN_SECURITY_CODE']) && !empty($fields['security_code/submit']['pressed']) && ($code = $values['security_code/code']))
		 || (isset($getRequest['verification_code']) && ($code = $getRequest['verification_code']))) {
		
			if ($code == '') {
				$tags['tabs']['security_code']['errors'][] =
					\ze\admin::phrase('Please enter the code that was sent to your email address.');
		
			} elseif (trim($code) !== $_SESSION['COOKIE_ADMIN_SECURITY_CODE']) {
				$tags['tabs']['security_code']['errors'][] =
					\ze\admin::phrase('The code you have entered is not correct.');
		
			} else {
			
				//If the code is correct, save a cookie...
				\ze\cookie::set(
					\ze\welcome::securityCodeCookieName(),
					\ze\ring::random(),
					(int) \ze\site::description('two_factor_authentication_timeout') * 86400);
			
				//...and an admin setting to remember it next time!
				$scsn = \ze\welcome::securityCodeSettingName();
				$time = \ze\welcome::securityCodeTime();
				\ze\admin::setSetting($scsn, $time);
			
				return true;
			}
		}
		
		$fields['security_code/not_seen_you']['hidden'] =
		$fields['security_code/not_seen_you_ip']['hidden'] =
		$fields['security_code/timeout']['hidden'] = 
		$fields['security_code/timeout_ip']['hidden'] = true;
		
		if (\ze\site::description('apply_two_factor_authentication_by_ip')) {
			if ($time) {
				$fields['security_code/timeout_ip']['hidden'] = false;
			} else {
				$fields['security_code/not_seen_you_ip']['hidden'] = false;
			}
		} else {
			if ($time) {
				$fields['security_code/timeout']['hidden'] = false;
			} else {
				$fields['security_code/not_seen_you']['hidden'] = false;
			}
		}
	
		return false;
	}
	
	//This returns the name that the cookie for the CAPTCHA should have.
	//This is in the form "COOKIE_LAST_ADMIN_CAPTCHA_COMPLETED"
	public static function adminCaptchaCookieName() {
		return 'COOKIE_LAST_ADMIN_CAPTCHA_COMPLETED';
	}

	//Get the value of the cookie above
	public static function adminCaptchaCookieValue() {
		if (isset($_COOKIE[\ze\welcome::adminCaptchaCookieName()])) {
			return preg_replace('@[^-_=\w]@', '', $_COOKIE[\ze\welcome::adminCaptchaCookieName()]);
		} else {
			return '';
		}
	}

	//Looks for a security code cookie with the above name,
	//then returns the corresponding name that a site setting should have.
	//This is in the form "COOKIE_LAST_ADMIN_CAPTCHA_COMPLETED_[[COOKIE_VALUE]]"
	public static function adminCaptchaSettingName() {
	
		if ('' == ($sccn = \ze\welcome::adminCaptchaCookieValue())) {
			 return false;
	
		} else {
			return 'COOKIE_LAST_ADMIN_CAPTCHA_COMPLETED_'. $sccn;
		}
	}
	
	//Remove very old CAPTCHAs
	public static function tidyCaptchas() {
		$sql = "
			DELETE FROM ". DB_PREFIX. "admin_settings
			WHERE name LIKE 'COOKIE_LAST_ADMIN_CAPTCHA_COMPLETED_%'
			  AND value < '". \ze\escape::sql(\ze\welcome::securityCodeTime(COOKIE_TIMEOUT)). "'";
		\ze\sql::update($sql, false, false);
	}

	public static function changePasswordAJAX(&$source, &$tags, &$fields, &$values, $changes, &$task) {
		$tags['key']['min_extranet_user_password_length'] = \ze::setting('min_extranet_user_password_length');
		$tags['key']['min_extranet_user_password_score'] = \ze::setting('min_extranet_user_password_score');
		
		//Skip this screen if the Admin presses the "Skip" button
		if (!empty($fields['change_password/skip']['pressed'])) {
			\ze\admin::cancelPasswordChange($_SESSION['admin_userid'] ?? false);
		
			if ($task == 'change_password') {
				$task = 'password_changed';
			}
		
			return true;
	
		//Change the password if the Admin presses the change password
		} elseif (!empty($fields['change_password/change_password']['pressed'])) {
			$details = [];
			$tags['tabs']['change_password']['errors'] = [];
			$currentPassword = $values['change_password/current_password']; 
			$newPassword = $values['change_password/password']; 
			$newPasswordConfirm = $values['change_password/re_password']; 
		
			if (!$currentPassword) {
				$tags['tabs']['change_password']['errors'][] = \ze\admin::phrase('Please enter your current password.');
		
			} elseif (!\ze\ring::engToBoolean(\ze\admin::checkPassword($_SESSION['admin_username'] ?? false, $details, $currentPassword))) {
				$tags['tabs']['change_password']['errors'][] = \ze\admin::phrase('_MSG_PASS_WRONG');
			}
		
			if (!$newPassword) {
				$tags['tabs']['change_password']['errors'][] = \ze\admin::phrase('Please enter your new password.');
		
			} elseif ($newPassword == $currentPassword) {
				$tags['tabs']['change_password']['errors'][] = \ze\admin::phrase('_MSG_PASS_NOT_CHANGED');
		
			} elseif (!\ze\user::checkPasswordStrength($newPassword, $checkIfEasilyGuessable = true)['password_matches_requirements']) {
				$tags['tabs']['change_password']['errors'][] = \ze\admin::phrase('The password provided does not match the requirements.');
		
			} elseif (!$newPasswordConfirm) {
				$tags['tabs']['change_password']['errors'][] = \ze\admin::phrase('Please repeat your New Password.');
		
			} elseif ($newPassword != $newPasswordConfirm) {
				$tags['tabs']['change_password']['errors'][] = \ze\admin::phrase('_MSG_PASS_2');
			}
		
			//If no errors with validation, then save new password
			if (empty($tags['tabs']['change_password']['errors'])) {
				\ze\adminAdm::setPassword($_SESSION['admin_userid'] ?? false, $newPassword, 0);

				//Prepare password change email confirmation
				$admin = \ze\row::get('admins', ['username', 'email', 'first_name', 'last_name'], $_SESSION['admin_userid'] ?? false);
				$merge = [];
				$merge['NAME'] = \ze::ifNull(trim($admin['first_name']. ' '. $admin['last_name']), $admin['username']);
				$merge['URL'] = \ze\link::protocol(). $_SERVER['HTTP_HOST'];
				$merge['SUBDIRECTORY'] = SUBDIRECTORY;
			
				$emailTemplate = 'change_password_complete';
			
				$message = $source['email_templates'][$emailTemplate]['body'];
				$message = nl2br($message);
			
				if (\ze\module::inc('zenario_email_template_manager')) {
					\zenario_email_template_manager::putBodyInTemplate($message);
				}
		
				$subject = $source['email_templates'][$emailTemplate]['subject'];
		
				foreach ($merge as $pattern => $replacement) {
					$message = str_replace('[['. $pattern. ']]', $replacement, $message);
				};
				
				\ze\server::sendEmailSimple(
					$subject, $message, $isHTML = true,
					//Admin password change emails should always be sent to the intended recipient even if debug mode is on.
					$ignoreDebugMode = true,
					$addressTo = $admin['email'], $nameTo = $merge['NAME'],
					$addressFrom = false, $nameFrom = $source['email_templates'][$emailTemplate]['from']
				);
			
				if ($task == 'change_password') {
					$task = 'password_changed';
				}
			
				return true;
			}
		}
	
		$fields['change_password/password_message']['snippet']['html'] = self::passwordMessageSnippet($values['password']);
	
		return false;
	}
	
	public static function newAdminAJAX(&$source, &$tags, &$fields, &$values, $changes, $task, $adminId) {
		$tags['key']['min_extranet_user_password_length'] = \ze::setting('min_extranet_user_password_length');
		$tags['key']['min_extranet_user_password_score'] = \ze::setting('min_extranet_user_password_score');
		
		//Set password if the Admin presses the save and login button
		if (!empty($fields['new_admin/save_password_and_login']['pressed'])) {
			$password = $values['new_admin/password'];
			$passwordConfirm = $values['new_admin/re_password'];
			$accept_box = $values['new_admin/accept_box'];
			
			if (!$password) {
				$tags['tabs']['new_admin']['errors'][] = \ze\admin::phrase('Please enter a password.');
				
			} elseif (!\ze\user::checkPasswordStrength($password, $checkIfEasilyGuessable = true)['password_matches_requirements']) {
				$tags['tabs']['new_admin']['errors'][] = \ze\admin::phrase('The password provided does not match the requirements.');
				
			} elseif (!$passwordConfirm) {
				$tags['tabs']['new_admin']['errors'][] = \ze\admin::phrase('Please enter your password again.');
				
			} elseif ($password != $passwordConfirm) {
				$tags['tabs']['new_admin']['errors'][] = \ze\admin::phrase('_MSG_PASS_2');
				
			} elseif (!$accept_box) {
				$tags['tabs']['new_admin']['errors'][] = \ze\admin::phrase('Please confirm that you accept your data will be stored as described below by checking the checkbox.');
				
			}
			//If no errors with validation, then save password and login
			if (empty($tags['tabs']['new_admin']['errors'])) {
				\ze\adminAdm::setPassword($adminId, $password, 0);
				\ze\admin::logIn($adminId, $values['new_admin/remember_me']);
				return true;
			}
		}
		
		$fields['new_admin/password_message']['snippet']['html'] = self::passwordMessageSnippet($fields['new_admin/password']['current_value'] ?? false);
		
		return false;
	}

	public static function diagnosticsAJAX(&$source, &$tags, &$fields, &$values, $changes, $task, $getRequest, &$continueTo) {
		
		//Run a check on the last modified times of the skin CSS files, even in production mode, so that we can
		//be sure that the css_skin_version variable is up to date. This is used when calculating whether the
		//skins need minifying.
		\ze\skinAdm::checkForChangesInFiles($runInProductionMode = true);
		
		
		$showContinueButton = true;
		$showCheckAgainButton = false;
		$showCheckAgainButtonIfDirsAreEditable = false;
	
		//If the directories aren't set at all, set them now so at least they're set to something
		if (!\ze::setting('backup_dir')) {
			\ze\site::setSetting('backup_dir', \ze\dbAdm::suggestDir('backup'));
		}
		if (!\ze::setting('docstore_dir')) {
			\ze\site::setSetting('docstore_dir', \ze\dbAdm::suggestDir('docstore'));
		}
		
		//Display last admin login time
		$sql = '
			SELECT id, username, last_login, last_login_ip, created_date';
		
		if (\ze::$dbL->checkTableDef(DB_PREFIX. 'admins', 'failed_login_count_since_last_successful_login')) {
			$sql .= ', failed_login_count_since_last_successful_login';
		}
		
		$sql .= '
			FROM ' . DB_PREFIX . 'admins
			WHERE id = '. (int) \ze\admin::id(). '
			  AND `status` = \'active\'
			ORDER BY last_login';
		
		$adminhtml = '';
		if (($row = \ze\sql::fetchAssoc($sql)) && !\ze\site::description('hide_admin_ip_address_on_login')) {
			if (\ze::request('task') == 'diagnostics' && $row['last_login']) {
				$adminhtml .= '<h2>This login</h2>';
				$adminhtml .= '<p>You logged in '. htmlspecialchars(\ze\admin::formatDateTime($row['last_login'], '_MEDIUM'));
			
			} elseif (!empty($_SESSION['admin_last_login'])) {
				$adminhtml .= '<h2>Your last login</h2>';
				$adminhtml .= '<p>You last logged in '. htmlspecialchars(\ze\admin::formatDateTime($_SESSION['admin_last_login'], '_MEDIUM'));
			}		
			
			if ($adminhtml && $row['last_login_ip']) {	
				if (!empty($_SESSION['admin_last_login']) && !empty($_SESSION['admin_last_login_ip'])) {
					$lastLoginIp = $_SESSION['admin_last_login_ip'];
				} else {
					$lastLoginIp = $row['last_login_ip'];
				}

				$adminhtml .= ' from IP address '. htmlspecialchars($lastLoginIp);
				
				if (\ze\module::inc("zenario_geoip_lookup")) {
					if ($userCountry = \zenario_geoip_lookup::getCountryISOCodeForIp($row['last_login_ip'])) {
						if (\ze\module::inc("zenario_country_manager")
							&& ($userCountryName = \zenario_country_manager::getCountryName($userCountry))
						) {
							$adminhtml .= ' ('. htmlspecialchars($userCountryName). ')';
						} else {
							$adminhtml .= ' ('. htmlspecialchars($userCountry). ')';
						}
					}
				}
				$adminhtml .= '.';

				$currentIp = \ze\user::ip();
				if ($currentIp != $lastLoginIp) {
					$adminhtml .= '</p>';

					$lastIpString = $lastLoginIp;
					$currentIpString = $currentIp;

					if (\ze\module::inc("zenario_geoip_lookup")) {
						if ($lastLoginCountry = \zenario_geoip_lookup::getCountryISOCodeForIp($lastLoginIp)) {
							if (\ze\module::inc("zenario_country_manager")
								&& ($lastLoginUserCountryName = \zenario_country_manager::getCountryName($lastLoginCountry))
							) {
								$lastIpString .= ' ('. htmlspecialchars($lastLoginUserCountryName). ')';
							} else {
								$lastIpString .= ' ('. htmlspecialchars($lastLoginCountry). ')';
							}
						}

						if ($currentLoginCountry = \zenario_geoip_lookup::getCountryISOCodeForIp($currentIp)) {
							if (\ze\module::inc("zenario_country_manager")
								&& ($currentLoginUserCountryName = \zenario_country_manager::getCountryName($currentLoginCountry))
							) {
								$currentIpString .= ' ('. htmlspecialchars($currentLoginUserCountryName). ')';
							} else {
								$currentIpString .= ' ('. htmlspecialchars($currentLoginCountry). ')';
							}
						}
					}

					$adminhtml .= '<p class="warning">' .
						\ze\admin::phrase(
							'Your last login from [[last_ip]] differs from your current IP address [[current_ip]].',
							['last_ip' => htmlspecialchars($lastIpString), 'current_ip' => htmlspecialchars($currentIpString)]
						) .
						'</p>';
				}
			}

			if ($adminhtml) {
				$adminhtml .= '</p>';
			}
		}
		
		if (!empty($row['failed_login_count_since_last_successful_login']) && $row['failed_login_count_since_last_successful_login'] >= 1) {
			if (!$adminhtml) {
				if (\ze::request('task') == 'diagnostics' && $row['last_login']) {
					$adminhtml .= '<h2>This login</h2>';
			
				} elseif (!empty($_SESSION['admin_last_login'])) {
					$adminhtml .= '<h2>Your last login</h2>';
				}	
			} else {
				$adminhtml .= '</p>';
			}
			
			$adminhtml .= '<p class="warning">' .
				\ze\admin::nPhrase(
					'Warning: since your last login, there was [[count]] failed login attempt on your administrator account.',
					'Warning: since your last login, there were [[count]] failed login attempts on your administrator account.',
					$row['failed_login_count_since_last_successful_login'],
					['count' => $row['failed_login_count_since_last_successful_login']]
				) .
				'</p>';
		}

		$fields['0/show_administrators_logins']['hidden'] = empty($adminhtml);
		$fields['0/show_administrators_logins']['snippet']['html'] = $adminhtml;
		$fields['0/show_administrators_logins']['row_class'] = 'section_valid';
		
		
		$fields['0/cache_dir']['value']	= CMS_ROOT. 'cache';
		$fields['0/private_dir']['value']	= CMS_ROOT. 'private';
		$fields['0/public_dir']['value']	= CMS_ROOT. 'public';
	    $fields['0/custom_dir']['value']	= CMS_ROOT. 'zenario_custom';
	    
		if (!$values['0/backup_dir']) {
			$values['0/backup_dir'] = (string) \ze::setting('backup_dir');
		}
	
		if (!$values['0/docstore_dir']) {
			$values['0/docstore_dir'] = (string) \ze::setting('docstore_dir');
		}
	
	
		$fields['0/dirs']['row_class'] = 'section_valid';
	
	
		$mrg = [
			'dir' => $dir = $values['0/docstore_dir'],
			'basename' => $dir? htmlspecialchars(basename($dir)) : ''];
	
		if (!$dir) {
			$fields['0/docstore_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/docstore_dir_status']['snippet']['html'] = \ze\admin::phrase('Please enter a directory.');
	
		} elseif (!@is_dir($dir)) {
			$fields['0/docstore_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/docstore_dir_status']['snippet']['html'] = \ze\admin::phrase('The directory <code>[[basename]]</code> does not exist.', $mrg);
	
		} elseif (false !== \ze\ring::chopPrefix(realpath(CMS_ROOT), realpath($dir))) {
			$fields['0/docstore_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/docstore_dir_status']['snippet']['html'] = \ze\admin::phrase('Zenario is installed this directory. Please choose a different directory.', $mrg);
	
		} elseif (!\ze\welcome::directoryIsWritable($dir)) {
			$fields['0/docstore_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/docstore_dir_status']['snippet']['html'] = \ze\admin::phrase('The directory <code>[[basename]]</code> is not writable.', $mrg);
	
		} else {
			$fields['0/dir_1']['row_class'] = 'sub_section_valid';
			$fields['0/docstore_dir_status']['row_class'] = 'sub_valid';
			$fields['0/docstore_dir_status']['snippet']['html'] = \ze\admin::phrase('The directory <code>[[basename]]</code> exists and is writable.', $mrg);
		}
		
		
		$mrg = [
			'dir' => $dir = $values['0/backup_dir'],
			'basename' => $dir? htmlspecialchars(basename($dir)) : ''];
	
		if (!$dir) {
			$fields['0/backup_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/backup_dir_status']['snippet']['html'] = \ze\admin::phrase('Please enter a directory.');
	
		} elseif (!@is_dir($dir)) {
			$fields['0/backup_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/backup_dir_status']['snippet']['html'] = \ze\admin::phrase('The directory <code>[[basename]]</code> does not exist.', $mrg);
	
		} elseif (false !== \ze\ring::chopPrefix(realpath(CMS_ROOT), realpath($dir))) {
			$fields['0/backup_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/backup_dir_status']['snippet']['html'] = \ze\admin::phrase('Zenario is installed this directory. Please choose a different directory.', $mrg);
	
		} elseif (!\ze\welcome::directoryIsWritable($dir)) {
			$fields['0/backup_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/backup_dir_status']['snippet']['html'] = \ze\admin::phrase('The directory <code>[[basename]]</code> is not writable.', $mrg);
	
		} else {
			$fields['0/dir_2']['row_class'] = 'sub_section_valid';
			$fields['0/backup_dir_status']['row_class'] = 'sub_valid';
			$fields['0/backup_dir_status']['snippet']['html'] = \ze\admin::phrase('The directory <code>[[basename]]</code> exists and is writable.', $mrg);
		}
	
		//Loop through all of the skins in the system (max 9) and check their editable_css directories
		$i = 0;
		$maxI = 9;
		$skinDirsValid = true;
		foreach (\ze\row::getAssocs(
			'skins',
			['name', 'display_name'],
			['missing' => 0, 'enable_editable_css' => 1]
		) as $skin) {
			if ($i == $maxI) {
				break;
			} else {
				++$i;
			}
		
			$skinWritableDir = CMS_ROOT. \ze\content::skinPath($skin['name']). 'editable_css/';
		
			$tags['tabs'][0]['fields']['skin_dir_'. $i]['value'] =
			$tags['tabs'][0]['fields']['skin_dir_'. $i]['current_value'] = $skinWritableDir;
		
			$mrg = [
				'basename' => $dir? htmlspecialchars(basename($skinWritableDir)) : '',
				'2dir' => $dir? htmlspecialchars($skin['name']. '/editable_css') : ''
			];
		
			if (!is_dir($skinWritableDir)) {
				$skinDirsValid = false;
				$tags['tabs'][0]['fields']['skin_dir_status_'. $i]['row_class'] = 'sub_warning';
				$tags['tabs'][0]['fields']['skin_dir_status_'. $i]['snippet']['html'] = \ze\admin::phrase('The directory <code>[[2dir]]</code> does not exist.', $mrg);
	
			} elseif (!\ze\welcome::directoryIsWritable($skinWritableDir)) {
				$skinDirsValid = false;
				$tags['tabs'][0]['fields']['skin_dir_status_'. $i]['row_class'] = 'sub_warning';
				$tags['tabs'][0]['fields']['skin_dir_status_'. $i]['snippet']['html'] = \ze\admin::phrase('The directory <code>[[2dir]]</code> is not writable.', $mrg);

			} else {
				$fileWritable = false;
				$fileNotWritable = false;
				foreach (scandir($skinWritableDir) as $sdir) {
					if (is_file($skinWritableDir. '/'. $sdir)) {
						if (is_writable($skinWritableDir. '/'. $sdir)) {
							$fileWritable = true;
						} else {
							if ($fileNotWritable === false) {
								$fileNotWritable = $sdir;
							} else {
								$fileNotWritable = true;
							}
						}
					}
				}
		
				if ($fileNotWritable === true) {
					if ($fileWritable) {
						$skinDirsValid = false;
						$tags['tabs'][0]['fields']['skin_dir_status_'. $i]['row_class'] = 'sub_warning';
						$tags['tabs'][0]['fields']['skin_dir_status_'. $i]['snippet']['html'] = \ze\admin::phrase('Some of the files in directory <code>[[2dir]]</code> are not writable by the web server. If you have file system access, go to the directory where Zenario is installed, then run <code>./zenario/scripts/fix_cache_and_perms.sh</code> to make them writeable.', $mrg);
					} else {
						$skinDirsValid = false;
						$tags['tabs'][0]['fields']['skin_dir_status_'. $i]['row_class'] = 'sub_warning';
						$tags['tabs'][0]['fields']['skin_dir_status_'. $i]['snippet']['html'] = \ze\admin::phrase('The files in the directory <code>[[2dir]]</code> are not writable by the web server. If you have file system access, go to the directory where Zenario is installed, then run <code>./zenario/scripts/fix_cache_and_perms.sh</code> to make them writeable.', $mrg);
					}
		
				} elseif ($fileNotWritable !== false) {
					$skinDirsValid = false;
					$tags['tabs'][0]['fields']['skin_dir_status_'. $i]['row_class'] = 'sub_warning';
					$tags['tabs'][0]['fields']['skin_dir_status_'. $i]['snippet']['html'] =
						\ze\admin::phrase('<code>[[short_path]]</code> is not writable by the web server. If you have file system access, go to the directory where Zenario is installed, then run <code>./zenario/scripts/fix_cache_and_perms.sh</code> to make it writeable.',
							['short_path' => htmlspecialchars('editable_css/'. $fileNotWritable), 'file' => htmlspecialchars($fileNotWritable)]);
		
				} else {
					$tags['tabs'][0]['fields']['skin_dir_status_'. $i]['row_class'] = 'sub_valid';
					$tags['tabs'][0]['fields']['skin_dir_status_'. $i]['snippet']['html'] = \ze\admin::phrase('The directory <code>[[2dir]]</code> exists and is writable.', $mrg);
				}
			}
		}
	
		$fields['0/dir_4']['hidden'] =
		$fields['0/show_dir_4']['hidden'] =
		$fields['0/dir_4_blurb']['hidden'] = $i == 0;
		
		$fields['0/cache_dir']['hidden'] =
		$fields['0/private_dir']['hidden'] =
		$fields['0/public_dir']['hidden'] = false;
	
		for ($j = 0; $j <= $maxI; ++$j) {
			$tags['tabs'][0]['fields']['skin_dir_'. $j]['hidden'] =
			$tags['tabs'][0]['fields']['skin_dir_status_'. $j]['hidden'] = $j > $i;
		}
	
		if ($i == 1) {
			$fields['0/dir_4_blurb']['snippet']['html'] =
				\ze\admin::phrase('CSS for plugins may be edited by an administrator, and Zenario writes CSS files to the following directory. Please ensure it exists and is writable by the web server:');
		} elseif ($i > 1) {
			$fields['0/dir_4_blurb']['snippet']['html'] =
				\ze\admin::phrase('CSS for plugins may be edited by an administrator, and Zenario writes CSS files to the following directories. Please ensure they exist and are writable by the web server:');
		}
	
		if (!is_dir(CMS_ROOT. 'cache')) {
			$fields['0/cache_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/cache_dir_status']['snippet']['html'] =
				\ze\admin::phrase('The &quot;cache&quot; directory does not exist.');
	
		} elseif (!\ze\welcome::directoryIsWritable(CMS_ROOT. 'cache')) {
			$fields['0/cache_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/cache_dir_status']['snippet']['html'] =
				\ze\admin::phrase('The &quot;cache&quot; directory is not writable.');
	
		} else {
			$fields['0/dir_5']['row_class'] = 'sub_section_valid';
			$fields['0/cache_dir_status']['row_class'] = 'sub_valid';
			$fields['0/cache_dir_status']['snippet']['html'] =
				\ze\admin::phrase('The &quot;cache&quot; directory exists and is writable.');
		}
	
		if (!is_dir(CMS_ROOT. 'private')) {
			$fields['0/private_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/private_dir_status']['snippet']['html'] =
				\ze\admin::phrase('The &quot;private&quot; directory does not exist.');
	
		} elseif (!\ze\welcome::directoryIsWritable(CMS_ROOT. 'private')) {
			$fields['0/private_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/private_dir_status']['snippet']['html'] =
				\ze\admin::phrase('The &quot;private&quot; directory is not writable.');
	
		} else {
			$fields['0/dir_6']['row_class'] = 'sub_section_valid';
			$fields['0/private_dir_status']['row_class'] = 'sub_valid';
			$fields['0/private_dir_status']['snippet']['html'] =
				\ze\admin::phrase('The &quot;private&quot; directory exists and is writable.');
		}
	
		if (!is_dir(CMS_ROOT. 'public')) {
			$fields['0/public_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/public_dir_status']['snippet']['html'] =
				\ze\admin::phrase('The &quot;public&quot; directory does not exist.');
	
		} elseif (!\ze\welcome::directoryIsWritable(CMS_ROOT. 'public')) {
			$fields['0/public_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/public_dir_status']['snippet']['html'] =
				\ze\admin::phrase('The &quot;public&quot; directory is not writable.');
	
		} else {
			$fields['0/dir_7']['row_class'] = 'sub_section_valid';
			$fields['0/public_dir_status']['row_class'] = 'sub_valid';
			$fields['0/public_dir_status']['snippet']['html'] =
				\ze\admin::phrase('The &quot;public&quot; directory exists and is writable.');
		}

		$statusLines = [];
	    if (!is_dir(CMS_ROOT. 'zenario_custom')) {
			$fields['0/custom_dir_status']['row_class'] = 'sub_invalid';
			$fields['0/custom_dir_status']['snippet']['html'] =
				\ze\admin::phrase('The &quot;zenario_custom&quot; directory does not exist.');
	
		} elseif (exec('svn status '. escapeshellarg(CMS_ROOT. 'zenario_custom'), $statusLines) && !empty($statusLines)) {
            $skinCustomDirsValid = false;
            $fields['0/custom_dir_status']['row_class'] = 'sub_warning';
            $fields['0/custom_dir_status']['snippet']['html'] =
                \ze\admin::phrase('The &quot;zenario_custom&quot; directory has uncommitted changes in svn.');
        } else {
            $skinCustomDirsValid = true;
            $fields['0/custom_dir_status']['row_class'] = 'sub_valid';
            $fields['0/custom_dir_status']['snippet']['html'] =
                \ze\admin::phrase('The &quot;zenario_custom&quot; directory exists and all the files are committed in svn.');
        }
		
		if ($fields['0/docstore_dir_status']['row_class'] == 'sub_invalid') {
			$showCheckAgainButtonIfDirsAreEditable =
			$fields['0/show_dirs']['pressed'] =
			$fields['0/show_dir_1']['pressed'] = true;
			$fields['0/dirs']['row_class'] = 'section_invalid';
			$fields['0/dir_1']['row_class'] = 'sub_section_invalid';
		}
		
		if ($fields['0/backup_dir_status']['row_class'] == 'sub_invalid') {
			$showCheckAgainButtonIfDirsAreEditable =
			$fields['0/show_dirs']['pressed'] =
			$fields['0/show_dir_2']['pressed'] = true;
			$fields['0/dirs']['row_class'] = 'section_invalid';
			$fields['0/dir_2']['row_class'] = 'sub_section_invalid';
		}
	
		if ($fields['0/cache_dir_status']['row_class'] == 'sub_invalid') {
			$showCheckAgainButton =
			$fields['0/show_dirs']['pressed'] =
			$fields['0/show_dir_5']['pressed'] = true;
			$fields['0/dirs']['row_class'] = 'section_invalid';
			$fields['0/dir_5']['row_class'] = 'sub_section_invalid';
		}
	
		if ($fields['0/private_dir_status']['row_class'] == 'sub_invalid') {
			$showCheckAgainButton =
			$fields['0/show_dirs']['pressed'] =
			$fields['0/show_dir_6']['pressed'] = true;
			$fields['0/dirs']['row_class'] = 'section_invalid';
			$fields['0/dir_6']['row_class'] = 'sub_section_invalid';
		}
	
		if ($fields['0/public_dir_status']['row_class'] == 'sub_invalid') {
			$showCheckAgainButton =
			$fields['0/show_dirs']['pressed'] =
			$fields['0/show_dir_7']['pressed'] = true;
			$fields['0/dirs']['row_class'] = 'section_invalid';
			$fields['0/dir_7']['row_class'] = 'sub_section_invalid';
		}
	    
		if (!$skinDirsValid) {
			$showCheckAgainButton =
			$fields['0/show_dirs']['pressed'] =
			$fields['0/show_dir_4']['pressed'] = true;
			if ($fields['0/dirs']['row_class'] != 'section_invalid') {
				$fields['0/dirs']['row_class'] = 'section_warning';
			}
			$fields['0/dir_4']['row_class'] = 'sub_section_warning';
		} else {
			$fields['0/dir_4']['row_class'] = 'sub_section_valid';
		}
		
		//skins for custom directory warnings
		if ($fields['0/custom_dir_status']['row_class'] == 'sub_invalid') {
			$showCheckAgainButton =
			$fields['0/show_dirs']['pressed'] =
			$fields['0/show_dir_8']['pressed'] = true;
			$fields['0/dirs']['row_class'] = 'section_invalid';
			$fields['0/dir_8']['row_class'] = 'sub_section_invalid';
		}
	    else if (!$skinCustomDirsValid) {
			$showCheckAgainButton =
			$fields['0/show_dirs']['pressed'] =
			$fields['0/show_dir_8']['pressed'] = true;
			if ($fields['0/dirs']['row_class'] != 'section_invalid') {
				$fields['0/dirs']['row_class'] = 'section_warning';
			}
			$fields['0/dir_8']['row_class'] = 'sub_section_warning';
		} else {
		    $fields['0/dirs']['row_class'] = 'section_valid';
			$fields['0/dir_8']['row_class'] = 'sub_section_valid';
		}
		
		$fields['0/site']['row_class'] = 'section_valid';
		$fields['0/content']['row_class'] = 'section_valid';
		$fields['0/administrators']['row_class'] = 'section_valid';
	
		//Don't show the "site" section yet if we've just finished an install
		if ($task == 'install'
		 || !\ze\row::exists('languages', [])) {
			foreach ($tags['tabs'][0]['fields'] as $fieldName => &$field) {
				if (!empty($field['hide_on_install'])) {
					$field['hidden'] = true;
				}
			}
	
		} else {
			
			//
			// Go through all of the checks in the site configuration section
			//
			
			$show_warning = false;
			$show_error = false;
		
			if (!\ze::setting('site_enabled')) {
				
				$mrg = ['link' => htmlspecialchars('organizer.php#zenario__administration/panels/site_settings//site_disabled~.zenario_enable_site~tsite~k{"id"%3A"site_disabled"}')];
				
				$show_warning = true;
				$fields['0/site_disabled']['row_class'] = 'warning';
				$fields['0/site_disabled']['snippet']['html'] =
					\ze\admin::phrase('Your site is not enabled, so visitors will not be able to see it. To enable the site, go to Configuration->Site Settings and open the <a href="[[link]]" target="_blank"><em>Site status</em></a> interface.', $mrg);
			} else {
				$fields['0/site_disabled']['row_class'] = 'valid';
				$fields['0/site_disabled']['snippet']['html'] = \ze\admin::phrase('Your site is enabled.');
			}	
			
			$errors = $exampleFile = false;
			\ze\document::checkAllPublicLinks($forceRemake = false, $errors, $exampleFile);
			if ($errors) {
				$show_warning = true;
				$mrg = [
					'exampleFile' => $exampleFile,
					'manageDocumentsLink' => htmlspecialchars('organizer.php#zenario__library/panels/documents')
				];
				
				$fields['0/public_documents']['row_class'] = 'warning';
				$fields['0/public_documents']['snippet']['html'] =
					\ze\admin::nPhrase('There is a problem with the public link for [[exampleFile]] and 1 other document. Please check your docstore and public/downloads directory for possible permission problems. <a href="[[manageDocumentsLink]]" target="_blank">Manage documents</a>',
						'There is a problem with the public link for [[exampleFile]] and [[count]] other documents. Please check your docstore and public/downloads directory for possible permission problems. <a href="[[manageDocumentsLink]]" target="_blank">Manage documents</a>',
						abs($errors - 1), $mrg,
						'There is a problem with the public link for the document [[exampleFile]]. Please check your docstore and public/downloads directory for possible permission problems. <a href="[[manageDocumentsLink]]" target="_blank">Manage documents</a>'
					);
		
			} else {
				$fields['0/public_documents']['row_class'] = 'valid';
				$fields['0/public_documents']['hidden'] = true;
			}
			
			
			if (!empty($fields['0/repair_public_images']['pressed'])) {
				set_time_limit(60 * 10);
				\ze\fileAdm::checkAllImagePublicLinks($check = false);
				\ze\skinAdm::emptyPageCache();
			}
			
			$mrg = \ze\fileAdm::checkAllImagePublicLinks($check = true);
			if ($mrg && $mrg['numMissing']) {
				$show_warning = true;
				$fields['0/public_images']['row_class'] = 'warning';
				$fields['0/public_images']['hidden'] = false;
				$fields['0/public_images']['snippet']['html'] =
					\ze\admin::nPhrase('There is a problem with the public link for &quot;[[exampleFile]]&quot; and 1 other image. Please repair public images. If that does not help, check your public/images/ directory for possible permission problems.',
						'There is a problem with the public link for &quot;[[exampleFile]]&quot; and [[count]] other images. Please repair public images. If that does not help, check your public/images/ directory for possible permission problems.',
						abs($mrg['numMissing'] - 1), $mrg,
						'There is a problem with the public link for the document &quot;[[exampleFile]]&quot;. Please repair public images. If that does not help, check your public/images/ directory for possible permission problems.'
					);
				
				$fields['0/repair_public_images']['hidden'] = false;
			} else {
				$fields['0/public_images']['row_class'] = 'valid';
				$fields['0/public_images']['hidden'] = true;
				$fields['0/repair_public_images']['hidden'] = true;
			}
			
			
			$fields['0/print_css']['snippet']['html'] = '';
			$fields['0/print_css']['row_class'] = 'valid';
			$fields['0/print_css']['hidden'] = true;
			$fields['0/unminified_skins']['row_class'] = 'valid';
			$fields['0/unminified_skins']['hidden'] = true;
			
			if ($sv = \ze::setting('css_skin_version')) {
				foreach (\ze\row::getValues('skins', ['id', 'name', 'display_name'], ['missing' => 0]) as $skin) {
					if (!file_exists(CMS_ROOT. ($skinPath = 'public/css/skin_'. $skin['id']. '/'. $sv. '.min.css'))) {
						$show_warning = true;
						$fields['0/unminified_skins']['row_class'] = 'information';
						$fields['0/unminified_skins']['hidden'] = false;
						$fields['0/unminified_skins']['snippet']['html'] =
							\ze\admin::phrase("Zenario serves CSS files to visitors in &ldquo;minified&rdquo; form, which are smaller and download faster. Since these files were last minified, files in either the skin CSS, or in Zenario's software, have been modified ([[last_modified]]). Zenario will safely re-minify the skin CSS files when you click Continue.",
								['last_modified' => \ze\admin::formatDateTime(base_convert($sv, 36, 10))]
							);
					}
					
					$printFile = \ze\content::skinPath($skin['name']). 'editable_css/print.css';
					
					if (file_exists(CMS_ROOT. $printFile)) {
						if ($css = file_get_contents(CMS_ROOT. $printFile)) {
							if (!preg_match('/@media\s+print\b/i', $css)) {
								$fields['0/print_css']['row_class'] = 'warning';
								$fields['0/print_css']['hidden'] = false;
								$fields['0/print_css']['snippet']['html'] .=
									' '.
									\ze\admin::phrase('The print-stylesheet at <code>[[printFile]]</code> is missing its &quot;<code>@media print { ... }</code>&quot; rule.',
										['printFile' => htmlspecialchars($printFile)]);
							}
						}
					}
				}
			}
			
			if (!empty($fields['0/print_css']['snippet']['html'])) {
				$fields['0/print_css']['snippet']['html'] .=
					' '.
					\ze\admin::phrase('This will likely render the front-end of your site unusable.');
			}
			
			
			
		
			$warnings = \ze\welcome::getBackupWarnings();
			$fields['0/site_automated_backups']['row_class'] = $warnings['row_class'];
			$fields['0/site_automated_backups']['hidden'] = isset($warnings['hidden']) ? $warnings['hidden'] : false;
			if (isset($warnings['snippet']['html'])) {
				$fields['0/site_automated_backups']['snippet']['html'] = $warnings['snippet']['html'];
			}
			
			if (!empty($warnings['show_warning'])) {
				$show_warning = true;
			}
			
			if (!defined('RESTORE_POLICY')) {
				$show_warning = true;
				
				$fields['0/restore_policy_not_set']['row_class'] = 'warning';
				$fields['0/restore_policy_not_set']['snippet']['html'] =
					\ze\admin::phrase('The <code>RESTORE_POLICY</code> constant is not set in the <code>zenario_siteconfig.php</code> file.');
			
			} else
			if (RESTORE_POLICY !== 'always'
			 && RESTORE_POLICY !== 'never'
			 && !preg_match('@\d\d\d\d-\d\d-\d\d@', RESTORE_POLICY)) {
				$show_warning = true;
				
				$mrg['date'] = date('Y-m-d');
				$fields['0/restore_policy_not_set']['row_class'] = 'warning';
				$fields['0/restore_policy_not_set']['snippet']['html'] =
					\ze\admin::phrase("The <code>RESTORE_POLICY</code> constant in the <code>zenario_siteconfig.php</code> file is set to an invalid value. It should be set to <code>'always'</code>, <code>'never'</code> or a date in the format <code>'[[date]]'</code>.", $mrg);
			
			} else {
				$fields['0/restore_policy_not_set']['row_class'] = 'valid';
				$fields['0/restore_policy_not_set']['hidden'] = true;
			}
			
			$mrg = [
				'manageJobsLink' => htmlspecialchars('organizer.php#zenario__administration/panels/zenario_scheduled_task_manager__scheduled_tasks')];
			
			//Check if the scheduled task manager is running
			if (!\ze\module::inc('zenario_scheduled_task_manager')) {
				
				//If this is a basic version of the CMS, allow the scheduled task manager not to be running.
				//However if running ProBusiness or Enterprise (which will be most people, as the public release
				//is the ProBusiness version) it should be running, so nag about it.
				switch (\ze\site::description('edition')) {
					case 'ProBusiness':
					case 'Enterprise':
					case 'Enterprise (checked out from SVN)':
						$show_warning = true;
						$fields['0/scheduled_task_manager']['row_class'] = 'warning';
						$fields['0/scheduled_task_manager']['snippet']['html'] =
							\ze\admin::phrase('The Scheduled Tasks Manager is not installed. Please run this module so the site can benefit from background tasks.', $mrg);
						break;
					
					default:
						$fields['0/scheduled_task_manager']['row_class'] = 'valid';
						$fields['0/scheduled_task_manager']['hidden'] = true;
				}
		
			} elseif (!\zenario_scheduled_task_manager::checkScheduledTaskRunning($jobName = false, $checkPulse = false)) {
				$show_warning = true;
				$fields['0/scheduled_task_manager']['row_class'] = 'warning';
				$fields['0/scheduled_task_manager']['snippet']['html'] =
					\ze\admin::phrase('The Scheduled Tasks Manager is installed, but the master switch is not enabled. <a href="[[manageJobsLink]]" target="_blank">Manage scheduled tasks</a>', $mrg);
		
			} elseif (!\zenario_scheduled_task_manager::checkScheduledTaskRunning($jobName = false, $checkPulse = true)) {
				$show_warning = true;
				$fields['0/scheduled_task_manager']['row_class'] = 'warning';
				$fields['0/scheduled_task_manager']['snippet']['html'] =
					\ze\admin::phrase('The Scheduled Tasks Manager is installed, but not correctly configured in your crontab. <a href="[[manageJobsLink]]" target="_blank">Manage scheduled tasks</a>', $mrg);
		
			} elseif (!\zenario_scheduled_task_manager::checkScheduledTaskRunning($jobName = 'jobCleanDirectories')) {
				$show_warning = true;
				$fields['0/scheduled_task_manager']['row_class'] = 'warning';
				$fields['0/scheduled_task_manager']['snippet']['html'] =
					\ze\admin::phrase('The Scheduled Tasks Manager is installed, but the <code>jobCleanDirectories</code> task is not enabled. Please enable this to improve page speed. <a href="[[manageJobsLink]]" target="_blank">Manage scheduled tasks</a>', $mrg);
			
			} else {
				$fields['0/scheduled_task_manager']['row_class'] = 'valid';
				$fields['0/scheduled_task_manager']['snippet']['html'] =
					\ze\admin::phrase('The Scheduled Tasks Manager is running. <a href="[[manageJobsLink]]" target="_blank">Manage scheduled tasks</a>', $mrg);
			}
			
			//Check for a missing site description file.
			if (!is_file(CMS_ROOT. 'zenario_custom/site_description.yaml')) {
				$show_error = true;
				$showCheckAgainButton = true;
				$fields['0/site_description_missing']['row_class'] = 'invalid';
			} else {
				//Note: only show this message if it's in the error state; hide it otherwise
				$fields['0/site_description_missing']['hidden'] = true;
			}
			
			//Check for a missing or unreadable .htaccess file
			if (is_readable(CMS_ROOT . '.htaccess')) {
				$fields['0/htaccess_unavailable']['hidden'] = true;
				if (!\ze::setting('mod_rewrite_enabled')) {
					$fields['0/friendly_urls_disabled']['row_class'] = 'warning';
				} else {
					$fields['0/friendly_urls_disabled']['hidden'] = true;
				}
			} else {
				$fields['0/htaccess_unavailable']['row_class'] = 'warning';
				$fields['0/htaccess_unavailable']['snippet']['html'] = \ze\admin::phrase('The .htaccess file cannot be read or is missing.');
				
				$fields['0/friendly_urls_disabled']['hidden'] = true;
			}
		
			//Check IP forwarding is either off, or on and working correctly
			if (defined('USE_FORWARDED_IP')
			 && constant('USE_FORWARDED_IP')
			 && empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$show_warning = true;
				$fields['0/forwarded_ip_misconfigured']['row_class'] = 'warning';
			} else {
				//Note: only show this message if it's in the error state; hide it otherwise
				$fields['0/forwarded_ip_misconfigured']['hidden'] = true;
			}

			//Check if the recommended caching settings are enabled.
			$href = 'organizer.php#zenario__administration/panels/site_settings//optimisation~.site_settings~tcaching~k{"id"%3A"optimisation"}';
			$linkStart = '<a href="' . htmlspecialchars($href) . '" target="_blank">';
			$linkEnd = '</a>';
			$cacheSiteSettingString = 'Please go to [[link_start]]<em>Cache</em>[[link_end]] in Configuration->Site Settings to change this.';

			if (\ze::setting('caching_enabled')) {
				$fields['0/web_pages_not_cached']['hidden'] = true;
			} else {
				$show_warning = true;
				$fields['0/web_pages_not_cached']['row_class'] = 'warning';
				$fields['0/web_pages_not_cached']['snippet']['html'] =
					\ze\admin::phrase(
						'Web pages or other text files generated by Zenario are not being cached. This will cause pages to load more slowly. ' . $cacheSiteSettingString,
						['link_start' => $linkStart, 'link_end' => $linkEnd]
					);
			}

			$bundle_skins = \ze::setting('bundle_skins');
			if ($bundle_skins == 'visitors_only') {
				$fields['0/css_file_wrappers_not_on_for_visitors']['hidden'] = true;
			} else {
				$show_warning = true;
				$fields['0/css_file_wrappers_not_on_for_visitors']['row_class'] = 'warning';

				if ($bundle_skins == 'on') {
					$string = 'CSS file bundle is always on. This will make the website load faster, but designers may want to turn this off for easier debugging.';
				} elseif ($bundle_skins == 'off') {
					$string = 'CSS file bundle is always off. This will cause the website to load more slowly.';
				}
				$string .= ' ' . $cacheSiteSettingString;

				$fields['0/css_file_wrappers_not_on_for_visitors']['snippet']['html'] =
					\ze\admin::phrase(
						$string,
						['link_start' => $linkStart, 'link_end' => $linkEnd]
					);
			}
		
			//Check to see if this is a developer installation...
			$fields['0/notices_shown']['hidden'] = true;
			$fields['0/errors_not_shown']['hidden'] = true;
		
			if (\ze\site::inDevMode()) {
			
				//Check to see if any developers are not showing errors/warnings
				//Note: only show this message if it's in the error state; hide it otherwise
				if (!(ERROR_REPORTING_LEVEL & E_ALL)
				 || !(ERROR_REPORTING_LEVEL & E_NOTICE)
				 || !(ERROR_REPORTING_LEVEL & E_STRICT)) {
					$show_warning = true;
					$fields['0/errors_not_shown']['hidden'] = false;
					$fields['0/errors_not_shown']['row_class'] = 'warning';
				}
			
				////Check to see if any developers are missing the debug_override_enable setting
				//if (!\ze::setting('debug_override_enable')) {
				//	$show_warning = true;
				//	$fields['0/email_addresses_not_overridden']['row_class'] = 'warning';
				//} else {
				//	//Note: only show this message if it's in the error state; hide it otherwise
				//	$fields['0/email_addresses_not_overridden']['hidden'] = true;
				//}
			} else {
				//$fields['0/email_addresses_not_overridden']['hidden'] = true;
			
				//Reverse of the above;
				//Warn production sites that level a high level of error reporting on
				if ((ERROR_REPORTING_LEVEL & E_NOTICE)
				 || (ERROR_REPORTING_LEVEL & E_STRICT)) {
					$show_warning = true;
					$fields['0/notices_shown']['hidden'] = false;
					$fields['0/notices_shown']['row_class'] = 'warning';
				}
			
			}

			//Check to see if cache debug is enabled
			if (\ze::setting('caching_debug_info')) {
				$show_warning = true;
				$fields['0/cache_debug_enabled']['row_class'] = 'warning';

				$effectiveIP = \ze::setting('limit_caching_debug_info_by_ip');

				$mrg = [
					'link' => htmlspecialchars('organizer.php#zenario__administration/panels/site_settings//optimisation~.site_settings~tcaching~k{"id"%3A"optimisation"}'),
					'effective_ip_address' => $effectiveIP
				];
				$cacheDebugString = 'This site is set to show caching debug information to <em>[[effective_ip_address]]</em> (you can see this by using an &ldquo;incognito&rdquo; browser session that has no administrator cookie). See Configuration->Site Settings, <a href="[[link]]" target="_blank"><em>Cache</em></a> panel.';
				$fields['0/cache_debug_enabled']['snippet']['html'] = \ze\admin::phrase($cacheDebugString, $mrg);
			} else {
				$fields['0/cache_debug_enabled']['hidden'] = true;
			}
		
			//Check to see if production sites have the debug_override_enable setting enabled
			if (\ze::setting('debug_override_enable')) {
				$sendToDebugAddressOrDontSentAtAll = \ze::setting('send_to_debug_address_or_dont_send_at_all');
				$show_warning = true;
				$fields['0/email_addresses_overridden']['row_class'] = 'warning';

				$mrg = ['link' => htmlspecialchars('organizer.php#zenario__administration/panels/site_settings//email~.site_settings~tdebug~k{"id"%3A"email"}')];
				
				$emailDebugString = 'You have &ldquo;Email debug mode&rdquo; enabled in <a href="[[link]]" target="_blank"><em>Email</em></a> (see Configuration->Site Settings). ';
				
				if ($sendToDebugAddressOrDontSentAtAll == 'send_to_debug_email_address') {
					$emailDebugString .= 'Email sent by this site will be redirected to &quot;[[email]]&quot;.';
					$mrg['email'] = htmlspecialchars(\ze::setting('debug_override_email_address'));
				} elseif ($sendToDebugAddressOrDontSentAtAll == 'dont_send_at_all') {
					$emailDebugString .= 'Email sent by this site will not be sent at all.';
				}

				$fields['0/email_addresses_overridden']['snippet']['html'] = \ze\admin::phrase($emailDebugString, $mrg);
			} else {
				$fields['0/email_addresses_overridden']['hidden'] = true;
			}
			
			//Check for missing modules
			$missingModules = [];
			foreach(\ze\row::getAssocs('modules', ['class_name', 'display_name'], ['status' => 'module_running'], 'class_name') as $module) {
				if (!\ze::moduleDir($module['class_name'], 'module_code.php', true)) {
					$missingModules[$module['class_name']] = \ze\admin::phrase('[[display_name|escape]] (<code>[[class_name|escape]]</code>)', $module);
				}
			}
			
			if (!$fields['0/missing_modules']['hidden'] = empty($missingModules)) {
				$linkToModulesPanel = htmlspecialchars('organizer.php#zenario__modules/panels/modules');

				$show_error = true;
				$fields['0/missing_modules']['row_class'] = 'invalid';
				
				$href = 'organizer.php#zenario__modules/panels/modules';
				$linkStart = '<a href="' . htmlspecialchars($href) . '" target="_blank">';
				$linkEnd = '</a>';
				
				$missingModulesSnippet =
					\ze\admin::phrase(
						'This site makes use of some [[link_start]]modules[[link_end]] that were not found in the file system. (Zenario checked inside zenario/modules, zenario_extra_modules, and zenario_custom/modules, and could not find the expected sub-folders). Either the sub-folders are missing, or it may be because you have upgraded Zenario and they are no longer supported:',
						['link_start' => $linkStart, 'link_end' => $linkEnd]
					).
					'<ul><li>'.
						implode('</li><li>', $missingModules).
					'</li></ul>';
				$missingModulesSnippet .= \ze\admin::phrase('<br /><a href="[[modules_panel]]" target="_blank">Manage modules</a>', ['modules_panel' => $linkToModulesPanel]);

				$fields['0/missing_modules']['snippet']['html'] = $missingModulesSnippet;
			}
		
			$badSymlinks = [];
			if (is_dir($dir = CMS_ROOT. 'zenario_extra_modules/')) {
				
				$currentInstall = rtrim(dirname(realpath(CMS_ROOT. 'zenario/')), '/');
				
				foreach (scandir($dir) as $mDir) {
					if ($mDir != '.'
					 && $mDir != '..'
					 && ($rp = realpath($dir. $mDir))
					 && (rtrim($dir. $mDir, '/') != rtrim($rp, '/'))
					 && ($rp = dirname($rp))
					 && (is_dir($rp. '/zenario') || ($rp = dirname($rp)))
					 && (is_dir($rp. '/zenario') || ($rp = dirname($rp)))
					 ) {
						if ($rp != $currentInstall) {
							$badSymlinks[] = $mDir;
						}
					}
				}
			}
			
			if (!$fields['0/bad_extra_module_symlinks']['hidden'] = empty($badSymlinks)) {
				$fields['0/bad_extra_module_symlinks']['row_class'] = 'invalid';
				$show_error = true;
				$showContinueButton = false;
			
				$mrg = ['module' => array_pop($badSymlinks), 'version' => ZENARIO_MAJOR_VERSION. '.'. ZENARIO_MINOR_VERSION];
				if (empty($badSymlinks)) {
					
					$fields['0/bad_extra_module_symlinks']['snippet']['html'] =
						\ze\admin::phrase('The <code>[[module]]</code> symlink in the <code>zenario_extra_modules/</code> directory is linked to the wrong version of Zenario. It should be linked to version [[version]].', $mrg);
				} else {
					
					$mrg['modules'] = implode('</code>, <code>', $badSymlinks);
					$fields['0/bad_extra_module_symlinks']['snippet']['html'] =
						\ze\admin::phrase('The <code>[[modules]]</code> and <code>[[module]]</code> symlinks in the <code>zenario_extra_modules/</code> directory are linked to the wrong version of Zenario. They should be linked to version [[version]].', $mrg);
				}
			}
		
			$moduleErrors = '';
			\ze\dbAdm::checkIfUpdatesAreNeeded($moduleErrors, $andDoUpdates = false);
		
			if (!$fields['0/module_errors']['hidden'] = !$moduleErrors) {
				$show_error = true;
				$fields['0/module_errors']['row_class'] = 'invalid';
				$fields['0/module_errors']['snippet']['html'] = nl2br(htmlspecialchars($moduleErrors));
			}
			
			$storesUserData = \ze\row::exists(
				'modules', [
					'status' => ['module_running', 'module_suspended'],
					'class_name' => ['zenario_extranet', 'zenario_users', 'zenario_user_forms']
				]
			);
			
			//Check if extranet sites have two-factor authentication enabled
			$warnAboutThis = 
				!\ze\site::description('enable_two_factor_authentication_for_admin_logins')
				&& $storesUserData;
			
			if (!$fields['0/two_factor_security']['hidden'] = !$warnAboutThis) {
				$show_warning = true;
				$fields['0/two_factor_security']['row_class'] = 'warning';
			}
			
			
			//Check a site accepts password without SSL
			$warnAboutThis = 
				!\ze\link::isHttps()
			 && \ze\row::exists('modules', [
					'status' => ['module_running', 'module_suspended'],
					'class_name' => 'zenario_extranet'
				]);
			
			if (!$fields['0/no_ssl_for_login']['hidden'] = !$warnAboutThis) {
				$show_warning = true;
				$fields['0/no_ssl_for_login']['row_class'] = 'warning';
			}
			
			//Check if extranet sites use SSL
			$warnAboutThis = 
				!\ze\site::description('enable_two_factor_authentication_for_admin_logins')
				&& $storesUserData;
			
			if (!$fields['0/two_factor_security']['hidden'] = !$warnAboutThis) {
				$show_warning = true;
				$fields['0/two_factor_security']['row_class'] = 'warning';
			}
			
			//Get any public pages which have plugins that must be on a private page (e.g. change password)
			if ($privatePagesWithPluginsThatMustBeOnPublicPage = \ze\contentAdm::getContentItemsWithPluginsThatMustBeOnPublicOrPrivatePage('private_page')) {
				$show_warning = true;
				$fields['0/plugin_must_be_on_private_page_error']['hidden'] = false;
				$fields['0/plugin_must_be_on_private_page_error']['row_class'] = 'warning';
				$fields['0/plugin_must_be_on_private_page_error']['snippet']['html'] = 
						\ze\admin::nPhrase('This content item is public, but contains one or more plugins which must be on a private page:[[listOfContentItems]]',
							'These content items are public, but contain one or more plugins which must be on a private page:[[listOfContentItems]]',
							count($privatePagesWithPluginsThatMustBeOnPublicPage),
							['listOfContentItems' => implode('<br>', $privatePagesWithPluginsThatMustBeOnPublicPage)]);
			} else {
				$fields['0/plugin_must_be_on_private_page_error']['hidden'] = true;
			}
			
			//Get any private pages which have plugins that must be on a public page (e.g. change password)
			if ($publicPagesWithPluginsThatMustBeOnPrivatePage = \ze\contentAdm::getContentItemsWithPluginsThatMustBeOnPublicOrPrivatePage('public_page')) {
				$show_warning = true;
				$fields['0/plugin_must_be_on_public_page_error']['hidden'] = false;
				$fields['0/plugin_must_be_on_public_page_error']['row_class'] = 'warning';
				$fields['0/plugin_must_be_on_public_page_error']['snippet']['html'] = 
						\ze\admin::nPhrase('This content item is private, but contains one or more plugins which must be on a public page:[[listOfContentItems]]',
							'These content items are private, but contain one or more plugins which must be on a public page:[[listOfContentItems]]',
							count($publicPagesWithPluginsThatMustBeOnPrivatePage),
							['listOfContentItems' => implode('<br>', $publicPagesWithPluginsThatMustBeOnPrivatePage)]);
			} else {
				$fields['0/plugin_must_be_on_public_page_error']['hidden'] = true;
			}
			
			//If it looks like a site is supposed to be using encryption, but it's not set up properly,
			//show an error message.
			if (\ze\pde::checkForSetupError()) {
			
			//If company key exists, and users module is running, show a warning if the consent table is not encrypted
			$warnAboutThis = \ze\pde::checkConfIsOkay() && $storesUserData;
			} elseif ($warnAboutThis) {
				
				$encryptedColumns = ['ip_address', 'email', 'first_name', 'last_name'];
				$unencryptedColumns = [];
				foreach ($encryptedColumns as $column) {
					if (!\ze::$dbL->columnIsHashed('consents', $column)) {
						$unencryptedColumns[] = '<code>' . $column . '</code>';
					}
				}
				
				if ($unencryptedColumns) {
					$show_warning = true;
					$fields['0/consent_table_encrypted']['row_class'] = 'warning';
					
					$fields['0/consent_table_encrypted']['snippet']['html'] = 
						\ze\admin::phrase('The following columns should be encrypted and hashed in the table <code>consents</code>: [[columns]]. Put the site into developer mode, cd to <code>public_html</code>, and run <code>php zenario/scripts/pde/encrypt_and_hash_column.php consents [field name]</code>.', ['columns' => implode(', ', $unencryptedColumns)]);
				}
			} else {
				//Otherwise, hide the warning.
				$fields['0/consent_table_encrypted']['hidden'] = true;
			}
			
			//Check if site contains user/contact data unencrypted
			$warnAboutThis =(bool)(!\ze::$dbL->columnIsEncrypted('users', 'first_name') && !\ze::$dbL->columnIsEncrypted('users', 'last_name') && !\ze::$dbL->columnIsEncrypted('users', 'email') && !\ze::$dbL->columnIsEncrypted('users', 'identifier'));
			
			if (!$fields['0/unencrypted_data']['hidden'] = !$warnAboutThis) {
				
				$sql = "
					SELECT count(*) as numberOfRecordsUnencrypted
					FROM ". DB_PREFIX. "users";
				$numberOfRecordsUnencrypted = \ze\sql::fetchAssoc($sql);
				if($numberOfRecordsUnencrypted['numberOfRecordsUnencrypted'] > 4){
				    $textForNumber = '';
				    if($numberOfRecordsUnencrypted['numberOfRecordsUnencrypted'] == 1){
				        $textForNumber = "user/contact";
				      
				    }else {
				        $textForNumber = "users/contacts";
				    }    

				   
				    $numberOfRecordsUnencrypted["textForNumber"] = $textForNumber;
				    \ze\lang::applyMergeFields($fields['0/unencrypted_data']["snippet"]["html"], $numberOfRecordsUnencrypted);
				    $show_warning = true;
				    $fields['0/unencrypted_data']['row_class'] = 'warning';
				} else {
					//If a site contains no user records, don't show the warning.
					$fields['0/unencrypted_data']['hidden'] = true;
				}
			} 
			
			
			//Check if a site is using encryption and has any encrypted columns on the users table
			//If it is, show a warning if the encryption keys are not correctly set
			$fields['0/encryption_key_issues']['hidden'] = true;
			if (\ze\pde::checkForSetupError()) {
				$show_warning = true;
				$fields['0/encryption_key_issues']['hidden'] = false;
				$fields['0/encryption_key_issues']['row_class'] = 'warning';
				$fields['0/encryption_key_issues']['snippet']['html'] = \ze\pdeAdm::setupErrorMessage();
			}
			
			//Check if site contains user/contact data encrypted and corresponding plain text column does not exist
			$userColumns = [];
			$fields['0/column_not_found']['hidden'] = true;
			if(\ze::$dbL->columnIsEncrypted('users', 'first_name')|| \ze::$dbL->columnIsHashed('users', 'first_name')){
			    $q= \ze\sql::select("SHOW COLUMNS FROM ".DB_PREFIX."users LIKE '%first_name'" );
	            if($q){
                    $columns = [];
	                while($row =\ze\sql::fetchAssoc($q)){
	                   if($row['Field'] == '%first_name' || $row['Field'] == 'first_name'){
	                        $columns[]= $row;
	                    }
	                }
	                if(count($columns) == 1){
	                
	                    $userColumns[] =  '<code>'.'first_name'.'</code>';
	                }
	            }
			}
			
			if(\ze::$dbL->columnIsEncrypted('users', 'last_name') || \ze::$dbL->columnIsHashed('users', 'last_name')){
			    $q= \ze\sql::select("SHOW COLUMNS FROM ".DB_PREFIX."users LIKE '%last_name'" );
	            if($q){
                    $columns = [];
	                while($row =\ze\sql::fetchAssoc($q)){
	                   if($row['Field'] == '%last_name' || $row['Field'] == 'last_name'){
	                        $columns[]= $row;
	                    }
	                }
	                
	                if(count($columns) == 1){
	                
	                    $userColumns[] =  '<code>'.'last_name'.'</code>';
	                }
	            }
			}
			
			if(\ze::$dbL->columnIsEncrypted('users', 'email') || (\ze::$dbL->columnIsHashed('users', 'email'))){
			    $q= \ze\sql::select("SHOW COLUMNS FROM ".DB_PREFIX."users LIKE '%email'" );
	            if($q){
                    $columns = [];
	                while($row =\ze\sql::fetchAssoc($q)){
	                   if($row['Field'] == '%email' || $row['Field'] == 'email'){
	                        $columns[]= $row;
	                    }
	                }
	                
	                if(count($columns) == 1){
	                
	                    $userColumns[] =  '<code>'.'email'.'</code>';
	                }
	            }
	            
			}
			
			if(\ze::$dbL->columnIsEncrypted('users', 'identifier') || \ze::$dbL->columnIsHashed('users', 'identifier')){
			    $q= \ze\sql::select("SHOW COLUMNS FROM ".DB_PREFIX."users LIKE '%identifier'" );
	            if($q){
                    $columns = [];
	                while($row =\ze\sql::fetchAssoc($q)){
	                   if($row['Field'] == '%identifier' || $row['Field'] == 'identifier'){
	                        $columns[]= $row;
	                    }
	                }
	                if(count($columns) == 1){
	                
	                    $userColumns[] =  '<code>'.'identifier'.'</code>';
	                }
	            }
	            
			}
			
			if(count($userColumns)>=1){
	            $fields['0/column_not_found']['row_class'] = 'warning';
	            $show_warning = true;
	            $fields['0/column_not_found']['hidden'] = false;
                $fields['0/column_not_found']['snippet']['html'] = \ze\admin::phrase('The following columns are encrypted/ hashed but their corresponding plain text columns are missing: [[columns]].', ['columns' => implode(', ', $userColumns)]);
	        }
	        
			//Do some basic checks on the robots.txt file
			$robotsDotTextError = false;
			$robotsTxtSiteSettingPath = 'organizer.php#zenario__administration/panels/site_settings//search_engine_optimisation~.site_settings~trobots_txt~k{"id"%3A"search_engine_optimisation"}';
			$linkStart = '<a href="' . htmlspecialchars($robotsTxtSiteSettingPath) . '" target="_blank">';
			$linkEnd = '</a>';
			if (file_exists(CMS_ROOT. 'robots.txt')) {
				$robotsDotTextError =
					\ze\admin::phrase(
						'A static <code>robots.txt</code> file exists and is no longer supported. Please delete the file and use the site setting in the [[link_start]]Search engine optimisation[[link_end]] section.',
						['link_start' => $linkStart, 'link_end' => $linkEnd]
					);
			
			} else {
				$robotsDotTextContents = \ze::setting('robots_txt_file_contents');
				
				if (!self::trimContents($robotsDotTextContents)) {
					$robotsDotTextError =
						\ze\admin::phrase(
							"The <code>robots.txt</code> file is blank. Please check the setting in the [[link_start]]Search engine optimisation[[link_end]] section.",
							['link_start' => $linkStart, 'link_end' => $linkEnd]
						);
				} else {
					$robotsFileLink = 'robots.txt';
					
					$currentFileRules = [];
					foreach (explode("\n", $robotsDotTextContents) as $line) {
						$parts = explode(':', $line, 2);
						if (!empty($parts[1])) {
							$command = trim(strtolower($parts[0]));
							$param = trim($parts[1]);
						
							if (!isset($currentFileRules[$command])) {
								$currentFileRules[$command] = [];
							}
						
							$currentFileRules[$command][$param] = true;
						}
					}
					
					if (isset($currentFileRules['user-agent']['*']) && isset($currentFileRules['disallow']['/'])) {
						$robotsDotTextError =
							\ze\admin::phrase(
								"This site has a <code>robots.txt</code> that is blocking search engine indexing. Please check the setting in the [[link_start]]Search engine optimisation[[link_end]] section.",
								['link_start' => $linkStart, 'link_end' => $linkEnd]
							);
				
					} else {
						//Check if the current robots file has non-standard modifications.
						//Get the contents of the default file...
						$defaultRobotsDotTextContents = @file_get_contents(CMS_ROOT. 'zenario/includes/test_files/default_robots.txt');
						if ($defaultRobotsDotTextContents) {
							//... and split them into a multi-dimensional array.
							$defaultFileRules = [];
							foreach (explode("\n", $defaultRobotsDotTextContents) as $line) {
								$parts = explode(':', $line, 2);
								if (!empty($parts[1])) {
									$command = trim(strtolower($parts[0]));
									$param = trim($parts[1]);
						
									if (!isset($defaultFileRules[$command])) {
										$defaultFileRules[$command] = [];
									}
						
									$defaultFileRules[$command][$param] = true;
								}
							}
							
							//Ignore the sitemap line if the current file references it.
							if (isset($currentFileRules['sitemap'])) {
								unset($currentFileRules['sitemap']);
							}
							
							//Compare the contents. Remove everything from the current file array that is a default setting.
							foreach ($defaultFileRules as $command => $rules) {
								foreach ($rules as $rule => $value) {
									if (isset($currentFileRules[$command][$rule])) {
										unset($currentFileRules[$command][$rule]);
									}
								}
								
								if (empty($currentFileRules[$command])) {
									unset($currentFileRules[$command]);
								}
							}
							
							//If the current file has no non-standard modifications, the array should be empty.
							if (!empty($currentFileRules)) {
								$robotsDotTextError =
									\ze\admin::phrase(
										"This site has a <code>robots.txt</code> that has non-standard modifications. Please check the setting in the [[link_start]]Search engine optimisation[[link_end]] section.",
										['link_start' => $linkStart, 'link_end' => $linkEnd]
									);
							}
						}
					}
				}
			}
			
			if (!$fields['0/robots_txt']['hidden'] = !$robotsDotTextError) {
				$show_warning = true;
				$fields['0/robots_txt']['row_class'] = 'warning';
				$fields['0/robots_txt']['snippet']['html'] = $robotsDotTextError;
			}
			
			/**Check if there aren't any unknown files in the public html directory.
			This is to let us know when, for example, someone has left a script or sql backup in the public_html dir and forgot to remove it.
			Checks for the following types:
				.7z
				.csv
				.gtar
				.gz
				.sql
				.tar
				.tgz
				.zip
				any executables
				any unrecognised files
			*/
		
			$publicHtmlFolderContents = scandir(CMS_ROOT);
			$unknownFiles = [];
			foreach ($publicHtmlFolderContents as $file) {
				if (is_file($file)) {
					$fileParts = pathinfo($file);
					
					//Watch out for files created without an extension.
					//They would cause a PHP error in the logic below, but we do want to warn
					//that they are there, so handle this by listing them and then moving on.
					if (!isset($fileParts['extension'])) {
						$unknownFiles[] = $file;
						continue;
					}
				
					//Ignore hidden files that start with a . (like .htaccess)
					if ($fileParts['filename'] === ''
						//Ignore .php files
					 || $fileParts['extension'] === 'php'
						//Ignore .lock files (e.g. generated from a package builder)
					 || $fileParts['extension'] === 'lock'
						//Ignore the alternate .htaccess files
					 || $fileParts['extension'] === 'htaccess') {
						continue;
					}
				
					if (!\ze\file::isAllowed($file) || \ze\file::isArchive($fileParts['extension'])) {
						$unknownFiles[] = $file;
					}
				}
			}
			
			if ($count = count($unknownFiles)) {
				$unknownFiles = implode(', ', $unknownFiles);
				$fields['0/unknown_files_in_zenario_root_directory']['row_class'] = 'warning';
				$fields['0/unknown_files_in_zenario_root_directory']['snippet']['html'] =
					\ze\admin::nphrase('There is an unknown file in the Zenario root directory: [[files]]. Please remove this if possible.', 'There are [[count]] unknown files in the Zenario root directory: [[files]]. Please remove them if possible.', $count, ['files' => $unknownFiles]);
			} else {
				//If there are no unknown files, hide the warning.
				$fields['0/unknown_files_in_zenario_root_directory']['hidden'] = true;
			}
			
			
			$fields['0/zenario_max_upload_size_exceeds_site_wide_size']['hidden'] = true;
			$apacheMaxFilesize = \ze\dbAdm::apacheMaxFilesize();
			
			$zenarioMaxFilesizeValue = \ze::setting('content_max_filesize');
			$zenarioMaxFilesizeUnit = \ze::setting('content_max_filesize_unit');
			$zenarioMaxFilesize = \ze\file::fileSizeBasedOnUnit($zenarioMaxFilesizeValue, $zenarioMaxFilesizeUnit);
			
			if ($zenarioMaxFilesize > $apacheMaxFilesize) {
				$fields['0/zenario_max_upload_size_exceeds_site_wide_size']['row_class'] = 'warning';
                $fields['0/zenario_max_upload_size_exceeds_site_wide_size']['hidden'] = false;
                $mrg = ['link' => htmlspecialchars('organizer.php#zenario__administration/panels/site_settings//files_and_images~.site_settings~tfilesizes~k{"id"%3A"files_and_images"}')];
				
				$show_warning = true; 
				$fields['0/zenario_max_upload_size_exceeds_site_wide_size']['snippet']['html'] =
					\ze\admin::phrase('The Zenario maximum file size value exceeds the server-wide maximum uploadable file size value. Please go to <a href="[[link]]" target="_blank"><em>Documents, images and file handling</em></a> in Configuration->Site Settings to change this.', $mrg);
			}
			
			$fields['0/default_timezone_not_set']['hidden'] = true;
			if (\ze\module::inc('zenario_timezones')) {
                if(\ze::setting('zenario_timezones__default_timezone') == ""){
                    $fields['0/default_timezone_not_set']['row_class'] = 'warning';
                    $fields['0/default_timezone_not_set']['hidden'] = false;
                    $mrg = ['link' => htmlspecialchars('organizer.php#zenario__administration/panels/site_settings//date_and_time~.site_settings~ttimezone_settings~k{"id"%3A"date_and_time"}')];
				
				    $show_warning = true; 
				    $fields['0/default_timezone_not_set']['snippet']['html'] =
						\ze\admin::phrase('The default timezone is not set. Please go to <a href="[[link]]" target="_blank"><em>Date and Time</em></a> in Configuration->Site Settings to set a timezone.', $mrg);
			    }
			}
			
			if (!defined('SESSION_TIMEOUT') || SESSION_TIMEOUT < 120 || SESSION_TIMEOUT > 21600) {
				$fields['0/admin_timeout_not_set_up_correctly']['row_class'] = 'warning';
				$fields['0/admin_timeout_not_set_up_correctly']['hidden'] = false;
				$show_warning = true;
				
				$fields['0/admin_timeout_not_set_up_correctly']['snippet']['html'] = \ze\admin::phrase('The session timeout should preferably be between 120 (i.e. 2 minutes) and 21600 (6 hours). Please edit the zenario_siteconfig.php file and set the "SESSION_TIMEOUT" constant to be in this range.');
			} else {
				$fields['0/admin_timeout_not_set_up_correctly']['hidden'] = true;
			}
			
			
			//Check the tables that are in the database are all present and correct
			$knownTables = [];
			$missingTables = [];
			$unknownTables = [];
			$unknownModuleTables = [];
			
			foreach ([
				CMS_ROOT. 'zenario/admin/db_install/local-DROP.sql',
				CMS_ROOT. 'zenario/admin/db_install/local-admin-DROP.sql'
			] as $sqlFileWithListOfTables) {
				foreach (\ze\ray::explodeAndTrim(file_get_contents($sqlFileWithListOfTables), false, "\n") as $knownTable) {
					$knownTable = explode('`', str_replace(']', '`', $knownTable));
					if (isset($knownTable[3])) {
						$knownTable = $knownTable[3];
						$knownTables[$knownTable] = 
						$missingTables[$knownTable] = $knownTable;
					}
				}
			}
			
			foreach (\ze\sql::fetchValues("SHOW TABLES LIKE '". \ze\escape::like(DB_PREFIX). "%'") as $existingTable) {
				$existingTable = \ze\ring::chopPrefix(DB_PREFIX, $existingTable);
	
				if ($existingTable[0] == 'm'
				 && $existingTable[1] == 'o'
				 && $existingTable[2] == 'd'
				 && is_numeric($existingTable[3])) {
					//Module tables, check they actually belong to a module that exists
					$moduleId = explode('_', \ze\ring::chopPrefix('mod', $existingTable))[0];
					if (!\ze\row::exists('modules', ['id' => $moduleId, 'status' => ['!' => 'module_not_initialized']])) {
						$unknownModuleTables[] = $existingTable;
					}
				} else {
					//Core tables, check they are in the list.
					if (isset($knownTables[$existingTable])) {
						unset($missingTables[$existingTable]);
					} else {
						$unknownTables[] = $existingTable;
					}
				}
			}
			
			if (!empty($missingTables)) {
				$fields['0/missing_tables']['row_class'] = 'invalid';
				$fields['0/missing_tables']['hidden'] = false;
				$show_error = true;
				
				foreach ($missingTables as &$table) {
					$table = '<code>'. htmlspecialchars(DB_PREFIX. $table). '</code>';
				}
				unset($table);
				
				$mrg = ['tables' => implode(', ', $missingTables)];
				if (count($missingTables) == 1) {
					$fields['0/missing_tables']['snippet']['html'] = \ze\admin::phrase('The following core table is missing from the database: [[tables]]', $mrg);
				} else {
					$fields['0/missing_tables']['snippet']['html'] = \ze\admin::phrase('The following core tables are missing from the database: [[tables]]', $mrg);
				}
			} else {
				$fields['0/missing_tables']['hidden'] = true;
			}
			
			if (!empty($unknownTables)) {
				$fields['0/unknown_tables']['row_class'] = 'warning';
				$fields['0/unknown_tables']['hidden'] = false;
				$show_warning = true;
				
				foreach ($unknownTables as &$table) {
					$table = '<code>'. htmlspecialchars(DB_PREFIX. $table). '</code>';
				}
				unset($table);
				
				$mrg = ['tables' => implode(', ', $unknownTables)];
				if (count($unknownTables) == 1) {
					$fields['0/unknown_tables']['snippet']['html'] = \ze\admin::phrase('The following table in the database is unrecognised: [[tables]]', $mrg);
				} else {
					$fields['0/unknown_tables']['snippet']['html'] = \ze\admin::phrase('The following tables in the database are unrecognised: [[tables]]', $mrg);
				}
			} else {
				$fields['0/unknown_tables']['hidden'] = true;
			}
			
			if (!empty($unknownModuleTables)) {
				$fields['0/unknown_module_tables']['row_class'] = 'warning';
				$fields['0/unknown_module_tables']['hidden'] = false;
				$show_warning = true;
				
				foreach ($unknownModuleTables as &$table) {
					$table = '<code>'. htmlspecialchars(DB_PREFIX. $table). '</code>';
				}
				unset($table);
				
				$mrg = ['tables' => implode(', ', $unknownModuleTables)];
				if (count($unknownTables) == 1) {
					$fields['0/unknown_module_tables']['snippet']['html'] = \ze\admin::phrase('The following module table in the database is not for any module on the site: [[tables]]', $mrg);
				} else {
					$fields['0/unknown_module_tables']['snippet']['html'] = \ze\admin::phrase('The following module tables in the database are not for any module on the site: [[tables]]', $mrg);
				}
			} else {
				$fields['0/unknown_module_tables']['hidden'] = true;
			}
			
			
			
			if ($show_error) {
				$fields['0/show_site']['pressed'] = true;
				$fields['0/site']['row_class'] = 'section_invalid';
		
			} elseif ($show_warning) {
				$fields['0/show_site']['pressed'] = true;
				$fields['0/site']['row_class'] = 'section_warning';
			}
			
			//
			// Go through all of the checks in the site content section
			//
			
			$show_warning = false;
			$show_error = false;
			
			$fields['0/content_unpublished_1']['row_class'] =
			$fields['0/content_unpublished_2']['row_class'] =
			$fields['0/content_unpublished_3']['row_class'] =
			$fields['0/content_unpublished_4']['row_class'] =
			$fields['0/content_unpublished_5']['row_class'] =
			$fields['0/content_more_unpublished']['row_class'] =
			$fields['0/content_nothing_unpublished']['row_class'] = 'valid';
			
			$fields['0/content_unpublished_1']['hidden'] =
			$fields['0/content_unpublished_2']['hidden'] =
			$fields['0/content_unpublished_3']['hidden'] =
			$fields['0/content_unpublished_4']['hidden'] =
			$fields['0/content_unpublished_5']['hidden'] =
			$fields['0/content_more_unpublished']['hidden'] = true;
			$fields['0/content_nothing_unpublished']['hidden'] = false;
		
			$sql = "
				SELECT c.id, c.type, c.alias, c.language_id, c.status, v.creating_author_id AS creator, v.last_author_id AS last_author, v.created_datetime, v.last_modified_datetime
				FROM ". DB_PREFIX. "content_items AS c
				INNER JOIN ". DB_PREFIX. "content_item_versions AS v
				   ON c.id = v.id
				  AND c.type = v.type
				  AND c.admin_version = v.version
				WHERE c.status IN ('first_draft','published_with_draft','unlisted_with_draft','hidden_with_draft','trashed_with_draft')
				ORDER BY last_modified_datetime DESC";
			
			$result = \ze\sql::select($sql);
			
			if ($rowCount = \ze\sql::numRows($result)) {
				$fields['0/content_nothing_unpublished']['hidden'] = true;
				$show_warning = true;
				$i = 0;
				while ($row = \ze\sql::fetchAssoc($result)) {
					++$i;
					$row['tag'] = htmlspecialchars(\ze\content::formatTag($row['id'], $row['type'], $row['alias'], $row['language_id']));
					$row['link'] = htmlspecialchars(\ze\link::toItem($row['id'], $row['type'], true));
					$row['class'] = 'organizer_item_image '. \ze\contentAdm::getItemIconClass($row['id'], $row['type'], true, $row['status']);
		
					//If a content item has ever been edited, show last modified date and admin who modified it, but not created date...
					if ($row['last_modified_datetime']) {
						$item['unpublished_content_info'] =
							\ze\admin::phrase('Last edit [[time]] by [[admin]].', [
								'time' => \ze\admin::formatRelativeDateTime($row['last_modified_datetime']),
								'admin' => \ze\admin::formatName($row['last_author'])
							]);
					} else {
						//... otherwise, show created date and admin who created it.
						$item['unpublished_content_info'] =
							\ze\admin::phrase('Created [[time]] by [[admin]].', [
								'time' => \ze\admin::formatRelativeDateTime($row['created_datetime']),
								'admin' => \ze\admin::formatName($row['creator'])
							]);
					}
			
					$specialPageUnpublishedMessage = (\ze\content::isSpecialPage($row['id'], $row['type'])) ? \ze\admin::phrase('<br />This page is a special page and it should be published.') : "";
					$fields['0/content_unpublished']['hidden'] = false;
					$fields['0/content_unpublished']['row_class'] = 'content_unpublished_wrap'; //Don't display warning triangle icons for unpublished items anymore. Deleting this line will show a green tick icon.
					$fields['0/content_unpublished']['snippet']['html'] .=
						'<div id="row__content_unpublished_'. $i.'" style="" class=" zenario_ab_row__content_unpublished_'. $i.'    zenario_row_for_snippet ">'.
							\ze\admin::phrase('<a target="blank" href="[[link]]"><span class="[[class]]"></span>[[tag]]</a> is in draft mode. ', $row).
							$specialPageUnpublishedMessage.
							'<br/>'.
							htmlspecialchars($item['unpublished_content_info']).
						'</div>';
				
				}
			}
			
			if ($show_error) {
				$fields['0/show_content']['pressed'] = true;
				$fields['0/content']['row_class'] = 'section_invalid';
		
			} elseif ($show_warning) {
				$fields['0/show_content']['pressed'] = true;
				$fields['0/content']['row_class'] = 'section_warning';
			}
			
			//
			// Go through all of the checks in the administrators section
			//
			
			//Admin must have this permission to view administrator activity warnings
			if (\ze\priv::check('_PRIV_VIEW_ADMIN')) {
			
				$show_warning = false;
				$show_error = false;
			
				$fields['0/administrator_inactive_1']['row_class'] = 
				$fields['0/administrator_inactive_2']['row_class'] = 
				$fields['0/administrator_inactive_3']['row_class'] = 
				$fields['0/administrator_inactive_4']['row_class'] = 
				$fields['0/administrator_inactive_5']['row_class'] = 
				$fields['0/administrator_more_inactive']['row_class'] = 
				$fields['0/administrators_active']['row_class'] =
				$fields['0/administrator_with_3_or_more_failed_logins_1']['row_class'] = 
				$fields['0/administrator_with_3_or_more_failed_logins_2']['row_class'] = 
				$fields['0/administrator_with_3_or_more_failed_logins_3']['row_class'] = 
				$fields['0/administrator_with_3_or_more_failed_logins_4']['row_class'] = 
				$fields['0/administrator_with_3_or_more_failed_logins_5']['row_class'] = 
				$fields['0/administrator_with_3_or_more_failed_logins_more']['row_class'] = 'valid';
				
				$fields['0/administrator_inactive_1']['hidden'] = 
				$fields['0/administrator_inactive_2']['hidden'] = 
				$fields['0/administrator_inactive_3']['hidden'] = 
				$fields['0/administrator_inactive_4']['hidden'] = 
				$fields['0/administrator_inactive_5']['hidden'] = 
				$fields['0/administrator_more_inactive']['hidden'] =
				$fields['0/administrator_with_3_or_more_failed_logins_1']['hidden'] = 
				$fields['0/administrator_with_3_or_more_failed_logins_2']['hidden'] = 
				$fields['0/administrator_with_3_or_more_failed_logins_3']['hidden'] = 
				$fields['0/administrator_with_3_or_more_failed_logins_4']['hidden'] = 
				$fields['0/administrator_with_3_or_more_failed_logins_5']['hidden'] = 
				$fields['0/administrator_with_3_or_more_failed_logins_more']['hidden'] = true;
				
				$fields['0/administrators_active']['hidden'] = false;
				
				$days = \ze\admin::getDaysBeforeAdminsAreInactive();
				$fields['0/administrators_active']['snippet']['html'] = \ze\admin::phrase('No administrator has been inactive for over [[count]] days.', ['count' => $days]);
		
				//Inactive admin count logic
				$inactiveAdminCount = 0;
				$sql = '
					SELECT id, username, first_name, last_name, last_login, created_date, authtype
					FROM ' . DB_PREFIX . 'admins
					WHERE authtype = \'local\'
					  AND `status` = \'active\'
					ORDER BY last_login';
				$result = \ze\sql::select($sql);
				
				while ($row = \ze\sql::fetchAssoc($result)) {
					$row['username'] = \ze\admin::formatName($row);
					if (\ze\admin::isInactive($row['id'])) {
						if (!$show_warning) {
							$show_warning = true;
							$fields['0/administrators_active']['hidden'] = true;
						}
						
						if (++$inactiveAdminCount <= 5) {
							$row['link'] = 'organizer.php#zenario__administration/panels/administrators//' . $row['id'];

							$fields['0/administrator_inactive_'. $inactiveAdminCount]['hidden'] = false;
							$fields['0/administrator_inactive_'. $inactiveAdminCount]['row_class'] = 'warning';
							
							if ($row['last_login']) {
								$row['days'] = floor((strtotime('now') - strtotime($row['last_login'])) / 60 / 60 / 24);
								$row['last_login_date'] = \ze\admin::formatDate($row['last_login'], '_MEDIUM');
								
								$fields['0/administrator_inactive_'. $inactiveAdminCount]['snippet']['html'] =
									\ze\admin::phrase(
										"Administrator <a target='blank' href='[[link]]'>[[username]]</a> hasn't logged in since [[last_login_date]], [[days]] days ago, consider whether this person's account should be trashed.",
										$row
									);
							} else {
								$row['created_date'] = \ze\admin::formatDate($row['created_date'], '_MEDIUM');
								
								$fields['0/administrator_inactive_'. $inactiveAdminCount]['snippet']['html'] =
									\ze\admin::phrase(
										"Administrator account <a target='blank' href='[[link]]'>[[username]]</a> was created on [[created_date]] but has never logged in, consider whether this person's account should be trashed.",
										$row
									);
							}
						}
					}
				}
				
				if ($inactiveAdminCount > 5) {
					$merge = ['link' => 'organizer.php#zenario__administration/panels/administrators'];
				
					$fields['0/administrator_more_inactive']['hidden'] = false;
					$fields['0/administrator_more_inactive']['row_class'] = 'warning';
					$fields['0/administrator_more_inactive']['snippet']['html'] =
						\ze\admin::nPhrase('1 other administrator is inactive. <a target="blank" href="[[link]]">View...</a>',
							'[[count]] other administrators are inactive. <a target="blank" href="[[link]]">View...</a>',
							abs($inactiveAdminCount - 5), $merge);
				}
				
				//Failed login attempt count logic
				if (\ze::$dbL->checkTableDef(DB_PREFIX. 'admins', 'failed_login_count_since_last_successful_login')) {
					$adminsWith3OrMoreFailedLoginsCount = 0;
					$sql = '
						SELECT id, username, first_name, last_name, authtype, failed_login_count_since_last_successful_login
						FROM ' . DB_PREFIX . 'admins
						WHERE `status` = \'active\'
						AND `id` != ' . \ze\admin::id() . '
						ORDER BY last_login';
					$result = \ze\sql::select($sql);
					
					while ($row = \ze\sql::fetchAssoc($result)) {
						if ($row['failed_login_count_since_last_successful_login'] >= 1) {
							
							if (!$show_warning) {
								$show_warning = true;
							}
						
							if (++$adminsWith3OrMoreFailedLoginsCount <= 5) {
								$linkStart = "<a target='blank' href='organizer.php#zenario__administration/panels/administrators//" . $row['id'] . "'>";
								$linkEnd = "</a>";

								$fields['0/administrator_with_3_or_more_failed_logins_'. $adminsWith3OrMoreFailedLoginsCount]['hidden'] = false;
								$fields['0/administrator_with_3_or_more_failed_logins_'. $adminsWith3OrMoreFailedLoginsCount]['row_class'] = 'warning';
							
								$fields['0/administrator_with_3_or_more_failed_logins_'. $adminsWith3OrMoreFailedLoginsCount]['snippet']['html'] =
									\ze\admin::nPhrase(
										"Warning: administrator [[link_start]][[admin_name]][[link_end]] has had [[failed_login_count_since_last_successful_login]] failed login attempt since last login.",
										"Warning: administrator [[link_start]][[admin_name]][[link_end]] has had [[failed_login_count_since_last_successful_login]] failed login attempts since last login.",
										$row['failed_login_count_since_last_successful_login'],
										['link_start' => $linkStart, 'link_end' => $linkEnd, 'admin_name' => \ze\admin::formatName($row), 'failed_login_count_since_last_successful_login' => $row['failed_login_count_since_last_successful_login']]
									);
							}
						}
					}
					
					if ($adminsWith3OrMoreFailedLoginsCount > 5) {
						$linkStart = "<a target='blank' href='organizer.php#zenario__administration/panels/administrators'>";
						$linkEnd = "</a>";
				
						$fields['0/administrator_with_3_or_more_failed_logins_more']['hidden'] = false;
						$fields['0/administrator_with_3_or_more_failed_logins_more']['row_class'] = 'warning';
						$fields['0/administrator_with_3_or_more_failed_logins_more']['snippet']['html'] =
							\ze\admin::nPhrase('1 other administrator has had 3 or more failed login attempts. [[link_start]]View...[[link_end]]',
								'[[count]] other administrators have had 3 or more failed login attempts. [[link_start]]View...[[link_end]]',
								abs($adminsWith3OrMoreFailedLoginsCount - 5),
								['link_start' => $linkStart, 'link_end' => $linkEnd]
							);
					}
				}
			
				if ($show_error) {
					$fields['0/show_administrators']['pressed'] = true;
					$fields['0/administrators']['row_class'] = 'section_invalid';
		
				} elseif ($show_warning) {
					$fields['0/show_administrators']['pressed'] = true;
					$fields['0/administrators']['row_class'] = 'section_warning';
				}
			} else {
				$fields['0/administrators']['hidden'] = 
				$fields['0/show_administrators']['hidden'] = 
				$fields['0/administrator_inactive_1']['hidden'] = 
				$fields['0/administrator_inactive_2']['hidden'] = 
				$fields['0/administrator_inactive_3']['hidden'] = 
				$fields['0/administrator_inactive_4']['hidden'] = 
				$fields['0/administrator_inactive_5']['hidden'] = 
				$fields['0/administrator_more_inactive']['hidden'] =
				$fields['0/administrators_active']['hidden'] =
				$fields['0/administrator_with_3_or_more_failed_logins_1']['hidden'] = 
				$fields['0/administrator_with_3_or_more_failed_logins_2']['hidden'] = 
				$fields['0/administrator_with_3_or_more_failed_logins_3']['hidden'] = 
				$fields['0/administrator_with_3_or_more_failed_logins_4']['hidden'] = 
				$fields['0/administrator_with_3_or_more_failed_logins_5']['hidden'] = 
				$fields['0/administrator_with_3_or_more_failed_logins_more']['hidden'] = true;
			}
		}
		
		//Strip any trailing slashes off of a directory path
		$values['0/backup_dir'] = preg_replace('/[\\\\\\/]+$/', '', $values['0/backup_dir']);
		$values['0/docstore_dir'] = preg_replace('/[\\\\\\/]+$/', '', $values['0/docstore_dir']);
	
		//On multisite sites, only allow changing the directory paths if:
		//this is a global admin,
		//or this is a local admin with the "Change site settings" permission.
		if (\ze\db::hasGlobal() && !($_SESSION['admin_global_id'] ?? false) && !\ze\priv::check('_PRIV_EDIT_SITE_SETTING')) {
			$_SESSION['zenario_installer_disallow_changes_to_dirs'] = true;
		//Only allow changes to the directories if they were not correctly set to start with
		} elseif (!isset($_SESSION['zenario_installer_disallow_changes_to_dirs'])) {
			$_SESSION['zenario_installer_disallow_changes_to_dirs'] =
				$fields['0/backup_dir_status']['row_class'] == 'sub_valid'
			 && $fields['0/docstore_dir_status']['row_class'] == 'sub_valid';
		}
	
		if ($_SESSION['zenario_installer_disallow_changes_to_dirs'] && $task != 'install') {
			$fields['0/backup_dir']['readonly'] = true;
			$fields['0/docstore_dir']['readonly'] = true;
	
		} else {
			if ($fields['0/backup_dir_status']['row_class'] == 'sub_valid') {
				\ze\site::setSetting('backup_dir', $values['0/backup_dir']);
			}
		
			if ($fields['0/docstore_dir_status']['row_class'] == 'sub_valid') {
				\ze\site::setSetting('docstore_dir', $values['0/docstore_dir']);
			}
		}
	
		\ze\welcome::systemRequirementsAJAX($source, $tags, $fields, $values, $changes, $isDiagnosticsPage = true);
	
		$everythingIsOkay =
			$fields['0/dirs']['row_class'] == 'section_valid'
		 && $fields['0/site']['row_class'] == 'section_valid'
		 && $fields['0/content']['row_class'] == 'section_valid'
		 && $fields['0/administrators']['row_class'] == 'section_valid'
		 && $fields['0/system_requirements']['row_class'] == 'section_valid';
	
		if (!$everythingIsOkay) {
			$showCheckAgainButton = true;
		}
		
	
		//In some cases, if something is so bad, hide the continue button.
		$fields['0/continue']['hidden'] =
			!$showContinueButton;
		
		//If all of the directory info valid (or uneditable due to not being a super-admin),
		//only show one button as there is nothing to save or recheck
		$fields['0/check_again']['hidden'] =
			$showContinueButton
		 && !$showCheckAgainButton
		 && (!$showCheckAgainButtonIfDirsAreEditable || $_SESSION['zenario_installer_disallow_changes_to_dirs']);
		
		$wasFirstViewing = $tags['key']['first_viewing'];
		$tags['key']['first_viewing'] = false;
	
		//If everything is valid, do not show this screen unless it was shown previously,
		//or the admin specificly requested it by clicking on the stethoscope button
		if ($everythingIsOkay && $wasFirstViewing) {
			if ($task != 'diagnostics') {
				unset($_SESSION['zenario_installer_disallow_changes_to_dirs']);
				return true;
			} else {
				//Don't show all of the toggles closed to start with, if everything is green
				//open the "Site configuration" and "Site content" sections by default in this case.
				$fields['0/show_site']['pressed'] = true;
				$fields['0/show_content']['pressed'] = true;
			}
		}
	
		//Continue on from this step if the "Continue" button is pressed
		if (!empty($fields['0/continue']['pressed'])) {
			unset($_SESSION['zenario_installer_disallow_changes_to_dirs']);
			
			# T12576, Admin login page should have explicit buttons for where it will send you after login
			$continueTo = $values['0/continue_to'] ?: 'default';
			
			return true;
	
		} else {
			
			# T12576, Admin login page should have explicit buttons for where it will send you after login
			$initialValue = '';
				
			$cID = $cType = false;
			$destStatus = \ze\welcome::redirectAdmin($getRequest, false, 'default', $returnChoice = true);
			
			$destThumbnails = [];
			$destOptions = [];
		
			//Don't show the choice in a few specific situations.
			if ($destStatus === false) {
				$fields['0/continue_to']['hidden'] = true;
				$initialValue = 'default';
		
			//Case where there is an Organizer path in the URL
			} elseif ($destStatus == 'organizer') {
				//Don't show the "go to content item" option in this case
				$destOptions['home'] = '';
				$destOptions['organizer'] = '';
				$initialValue = 'organizer';
				
				//Tony wants labels to say "Continue" if a deep link, or "Go" where they are standard things.
				$fields['0/continue_to']['values']['organizer']['label'] = \ze\admin::phrase('Continue to Organizer');
		
			//Case where there is a content item in the URL
			} elseif (\ze\content::getCIDAndCTypeFromTagId($cID, $cType, $destStatus)) {
			
				//Catch the case where the admin just came from the home page
				if (\ze\content::isSpecialPage($cID, $cType) == 'zenario_home') {
					//Don't show the "go to content item" option in this case
					$destOptions['home'] = '';
					$destOptions['organizer'] = '';
					$initialValue = 'home';
			
				} else {
					//Show all three options
					$destOptions['citem'] = '';
					$destOptions['home'] = '';
					$destOptions['organizer'] = '';
					$initialValue = 'citem';
				
					//Change the label on the "citem" option to mention the actual name of the page
					$fields['0/continue_to']['values']['citem']['label'] =
						\ze\admin::phrase('Continue to [[tag]]', ['tag' => \ze\content::formatTag($cID, $cType)]);
					
					//If the content item has a featured image, show that as an icon in the select list.
					$width = $height = $url = false;
					$widthLimit = $heightLimit = 80;
					if (($featuredImageId = \ze\file::itemStickyImageId($cID, $cType))
					 && (\ze\file::imageLink($width, $height, $url, $featuredImageId, $widthLimit, $heightLimit, 'resize_and_crop', 0, false, $fullPath = true))) {
						
						$destThumbnails['citem'] = $url;
					}
				}
		
			//Case where there is nothing in the URL
			} else {
				//Just offer the "home" and "organizer" options
				$destOptions['home'] = '';
				$destOptions['organizer'] = '';
				$initialValue = 'home';
			}
			
			//If the site has a favicon, show that as an icon in the select list.
			if (empty($fields['0/continue_to']['hidden'])
			 && ($faviconId = \ze::setting('favicon'))
			 && ($url = \ze\file::link($faviconId, false, 'public/images'))) {
				
				$destThumbnails['home'] = \ze\link::absolute(). $url;
			}
			
			
			//Convert the options here into radio selector boxes
			foreach ($destOptions as $val => &$html) {
				$html .= '<span class="continue_to_block">';
				
				if (isset($destThumbnails[$val])) {
					$html .=
						'<span
							class="continue_to_icon continue_to_thumbnail"
							style="background-image: url('. htmlspecialchars($destThumbnails[$val]). ');"
						></span>';
				} else {
					$html .=
						'<span
							class="continue_to_icon continue_to_icon__'. htmlspecialchars($val). '"
						></span>';
				}
				
				$html .=
					'<span class="continue_to_label">'. htmlspecialchars($fields['0/continue_to']['values'][$val]['label']). '</span>';
				
				$html .= '</span>';
			}
			unset($html);
			
			if (empty($values['0/continue_to'])) {
				$values['0/continue_to'] = $initialValue;
			} else {
				$initialValue = $values['0/continue_to'];
			}
			
			\ze\welcome::setupRadioSelectorValues('continue_to', $fields['0/continue_to'], $destOptions, $initialValue);
			
			
			
			//If the Admin has pressed the "Save and Continue" button...
			if (!empty($fields['0/check_again']['pressed'])) {
				//If the directory section is valid, also don't show this screen unless it was shown previously
				if ($fields['0/dirs']['row_class'] == 'section_valid'
				 && empty($fields['0/check_again']['pressed'])) {
					unset($_SESSION['zenario_installer_disallow_changes_to_dirs']);
					return true;
			
				//Otherwise shake the screen
				} else {
					$tags['shake'] = true;
				}
			}
			return false;
		}
	}
	
	public static function getTrimmedFileContents($path) {
		
		if ($contents = @file_get_contents($path)) {
			return preg_replace('@[\n\r]+@', ' ', preg_replace('@[^\S\n\r]@', '', "\n". $contents. "\n"));
		} else {
			return false;
		}
	}
	
	public static function trimContents($string) {
		if ($string) {
			return preg_replace('@[\n\r]+@', ' ', preg_replace('@[^\S\n\r]@', '', "\n". $string. "\n"));
		} else {
			return false;
		}
	}

	public static function protectBackupAndDocstoreDirsIfPossible() {
		foreach ([\ze::setting('backup_dir'), \ze::setting('docstore_dir')] as $dir) {
			if ($dir
			 && is_dir($dir)
			 && is_writeable($dir)
			 && !file_exists($file = $dir. '/.htaccess')) {
				@file_put_contents($file, 
'Options -Indexes

<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteRule .* - [F,NC]
</IfModule>'
				);
			}
		}
	}

	public static function licenseExpiryDate($file) {
		$return = false;
	
		if ($f = fopen($file, 'r')) {
			if (($expires = trim(fgets($f)))
			 && ($expires = explode('Expires: ', $expires, 2))
			 && (!empty($expires[1]))) {
				$return = $expires[1];
			}
			fclose($f);
		}
	
		return $return;
	}

	public static function congratulationsAJAX(&$source, &$tags, &$fields, &$values, $changes) {
		\ze\lang::applyMergeFields($fields['0/blurb2']['snippet']['html'], ['site_url' => \ze\link::protocol(). $_SERVER['HTTP_HOST']. SUBDIRECTORY]);
	}

	public static function redirectAdmin($getRequest, $forceAliasInAdminMode = false, $continueTo = 'default', $returnChoice = false) {
		
		//If the visitor's original request was from a content item, try to use that as the destination.
		$cID = $cType = $redirectNeeded = $aliasInURL = $langIdInURL = false;
		if (!empty($getRequest)
		 && !(empty($getRequest['cID']) && empty($getRequest['cType']) && empty($getRequest['langId']))) {
			\ze\content::resolveFromRequest($cID, $cType, $redirectNeeded, $aliasInURL, $langIdInURL, $getRequest, $getRequest, []);
		}
		
		$isAdmin = \ze\priv::check();
		$hasOrgPath = !empty($getRequest['og']);
		
		$domain = ($forceAliasInAdminMode || !$isAdmin)? \ze\link::primaryDomain() : \ze\link::adminDomain();
		
		//A language needs to be enabled, when an admin logs in, the admin should always be taken to
		//the languages panel in Organizer to enable it.
		if (!\ze\row::exists('languages', []) && $isAdmin) {
			if ($returnChoice) {
				return false;
			} else {
				return
					'organizer.php'.
					'#zenario__languages/panels/languages';
			}
		
		
		//Handle the case where we want to go to Organizer
		} elseif ($isAdmin && ($continueTo == 'organizer' || ($continueTo == 'default' && $hasOrgPath))) {
			if ($returnChoice) {
				return 'organizer';
			} else {
				return
					'organizer.php'.
					(isset($getRequest['fromCID']) && isset($getRequest['fromCType'])? '?fromCID='. $getRequest['fromCID']. '&fromCType='. $getRequest['fromCType'] : '').
					($hasOrgPath? '#'. $getRequest['og'] : '');
			}
		
		
		//Handle the case where we have a custom desturl to go back to
		} elseif ($continueTo == 'default' && !empty($getRequest['desturl']) && $isAdmin) {
			if ($returnChoice) {
				return false;
			} else {
				return \ze\link::protocol(). $domain. $getRequest['desturl'];
			}
		}
		
		
		//Handle the case where we want to go to a content item in the front-end
		if (!$cID && !empty($_SESSION['destCID'])) {
			$cID = $_SESSION['destCID'];
			$cType = $_SESSION['destCType'] ?? 'html';
		}
		
		if ($cID && ($continueTo == 'citem' || $continueTo == 'default') && \ze\content::checkPerm($cID, $cType)) {
			if ($returnChoice) {
				return $cType. '_'. $cID;
			} else {
				unset($getRequest['task'], $getRequest['cID'], $getRequest['cType'], $getRequest['cVersion'], $getRequest['verification_code'], $getRequest['change_email_code']);
		
				return \ze\link::toItem($cID, $cType, true, http_build_query($getRequest), false, false, $forceAliasInAdminMode);
			}
		
		//Return to the homepage
		} else {
			if ($returnChoice) {
				return 'home';
			} else {
				return (DIRECTORY_INDEX_FILENAME ?: SUBDIRECTORY);
			}
		}
	}


	public static function lastAutomatedBackupTimestamp($automated_backup_log_path = false) {
		$backup = \ze\welcome::lastAutomatedBackup($automated_backup_log_path);
		if ($backup) {
			$datetime = new \DateTime($backup[0]);
			return $datetime->getTimestamp();
		}
		return 0;
	}

	public static function lastAutomatedBackup($automated_backup_log_path = false) {
		if ($automated_backup_log_path === false) {
			$automated_backup_log_path = \ze::setting('automated_backup_log_path');
		}
	
		//Attempt to get the date of the last backup from
		$timestamp = 0;
		$latestLineValues = false;
		ini_set('auto_detect_line_endings', true);
		if ($f = fopen($automated_backup_log_path, 'r')) {
			while ($line = fgets($f)) {
				if (trim($line) != ''
				 && ($lineValues = str_getcsv($line))
				 && ($lineValues[0])
				 && ($lineValues[2]? DBNAME == $lineValues[2] : DBNAME == $lineValues[1])
				 && ($time = new \DateTime($lineValues[0]))
				 && ($timestamp < $time->getTimestamp())) {
					$timestamp = $time->getTimestamp();
					$latestLineValues = $lineValues;
				}
			}
		}
		
		return $latestLineValues;
	}

    //Used on Diagnostics screen
    public static function getBackupWarnings() {
        $mrg = [
				'DBNAME' => DBNAME,
				'path' => \ze::setting('automated_backup_log_path'),
				'link' => htmlspecialchars('organizer.php#zenario__administration/panels/site_settings//dirs~.site_settings~tautomated_backups~k{"id"%3A"dirs"}'),
				'manageAutomatedBackupLink' => htmlspecialchars('organizer.php#zenario__administration/panels/site_settings//dirs')];
        $warnings = [];
        if (!\ze::setting('check_automated_backups')) {
            $warnings['row_class'] = 'valid';
            $warnings['hidden'] = true;

        } elseif (!\ze::setting('automated_backup_log_path')) {
            $warnings['show_warning'] = true;
            $warnings['row_class'] = 'warning';
            $warnings['snippet']['html'] =
                \ze\admin::phrase('Automated backup monitoring is not set up. Please go to <a href="[[link]]" target="_blank"><em>Backups and document storage</em></a> in Configuration->Site Settings to change this. <a href="[[manageAutomatedBackupLink]]" target="_blank">Manage automated backups</a>', $mrg);

        } elseif (!is_file(\ze::setting('automated_backup_log_path'))) {
            $warnings['show_warning'] = true;
            $warnings['row_class'] = 'warning';
             $warnings['snippet']['html'] =
                \ze\admin::phrase('Automated backup monitoring is not running properly: the log file ([[path]]) could not be found. <a href="[[manageAutomatedBackupLink]]" target="_blank">Manage automated backups</a>', $mrg);

        } elseif (!is_readable(\ze::setting('automated_backup_log_path'))) {
            $warnings['show_warning'] = true;
            $warnings['row_class'] = 'warning';
            $warnings['snippet']['html']=
                \ze\admin::phrase('Automated backup monitoring is not running properly: the log file ([[path]]) exists but could not be read. <a href="[[manageAutomatedBackupLink]]" target="_blank">Manage automated backups</a>', $mrg);

        } else {
            //Attempt to get the date of the last backup from
            $timestamp = \ze\welcome::lastAutomatedBackupTimestamp();
    
            if (!$timestamp) {
                $warnings['show_warning'] = true;
                $warnings['row_class']= 'warning';
                $warnings['snippet']['html'] =
                    \ze\admin::phrase('This site is not being backed-up using the automated process: database [[DBNAME]] was not listed in [[path]]. <a href="[[manageAutomatedBackupLink]]" target="_blank">Manage automated backups</a>', $mrg);
    
            } else {
                $days = (int) floor((time() - (int) $timestamp) / 86400);
        
                if ($days >= (int) \ze::setting('automated_backup_days')) {
                    $warnings['show_warning'] = true;
                    $warnings['row_class']= 'warning';
                    $warnings['snippet']['html'] =
                        \ze\admin::nPhrase('Automated backups have not been run in the last day. <a href="[[manageAutomatedBackupLink]]" target="_blank">Manage automated backups</a>',
                            'Automated backups have not been run in [[days]] days. <a href="[[manageAutomatedBackupLink]]" target="_blank">Manage automated backups</a>',
                            $days, ['days' => $days, 'manageAutomatedBackupLink' => $mrg['manageAutomatedBackupLink']]);

                } else {
                    $warnings['row_class'] = 'valid';
                    $warnings['snippet']['html']=
                        \ze\admin::phrase("Automated backups are running.");
                }
            }
        }
		return $warnings;
    }
    
    //Used in "Backups and document storage" Site Setting FAB.
    public static function getBackupWarningsWithoutHtmlLinks() {
        $mrg = [
				'DBNAME' => DBNAME,
				'path' => \ze::setting('automated_backup_log_path')];
        $warnings = [];
        if (!\ze::setting('check_automated_backups')) {
            $warnings['row_class'] = 'valid';
            $warnings['hidden'] = true;

        } elseif (!\ze::setting('automated_backup_log_path')) {
            $warnings['show_warning'] = true;
            $warnings['row_class'] = 'warning';
            $warnings['snippet']['html'] =
                \ze\admin::phrase('Automated backup monitoring is not set up.', $mrg);

        } elseif (!is_file(\ze::setting('automated_backup_log_path'))) {
            $warnings['show_warning'] = true;
            $warnings['row_class'] = 'warning';
             $warnings['snippet']['html'] =
                \ze\admin::phrase('Automated backup monitoring is not running properly: the log file ([[path]]) could not be found.', $mrg);

        } elseif (!is_readable(\ze::setting('automated_backup_log_path'))) {
            $warnings['show_warning'] = true;
            $warnings['row_class'] = 'warning';
            $warnings['snippet']['html']=
                \ze\admin::phrase('Automated backup monitoring is not running properly: the log file ([[path]]) exists but could not be read.', $mrg);

        } else {
            //Attempt to get the date of the last backup from
            $timestamp = \ze\welcome::lastAutomatedBackupTimestamp();
    
            if (!$timestamp) {
                $warnings['show_warning'] = true;
                $warnings['row_class']= 'warning';
                $warnings['snippet']['html'] =
                    \ze\admin::phrase('This site is not being backed-up using the automated process: database [[DBNAME]] was not listed in [[path]].', $mrg);
    
            } else {
                $days = (int) floor((time() - (int) $timestamp) / 86400);
        
                if ($days >= (int) \ze::setting('automated_backup_days')) {
                    $warnings['show_warning'] = true;
                    $warnings['row_class']= 'warning';
                    $warnings['snippet']['html'] =
                        \ze\admin::nPhrase('Automated backups have not been run in the last day.',
                            'Automated backups have not been run in [[days]] days.',
                            $days, ['days' => $days]);

                } else {
                    $warnings['row_class'] = 'valid';
                    $warnings['snippet']['html']=
                        \ze\admin::phrase("Automated backups are running.");
                }
            }
        }
		return $warnings;
    }

	public static function deleteNamedPluginSetting($moduleClassName, $settingName) {
	
		$sql = "
			DELETE ps.*
			FROM `". DB_PREFIX. "modules` AS m
			INNER JOIN `". DB_PREFIX. "plugin_instances` AS pi
			   ON m.id = pi.module_id
			INNER JOIN `". DB_PREFIX. "plugin_settings` AS ps
			   ON pi.id = ps.instance_id
			  AND ps.egg_id = 0
			  AND ps.name = '". \ze\escape::sql($settingName). "'
			WHERE m.class_name = '". \ze\escape::asciiInSQL($moduleClassName). "'";
		\ze\sql::update($sql);

		$sql = "
			DELETE ps.*
			FROM `". DB_PREFIX. "modules` AS m
			INNER JOIN `". DB_PREFIX. "nested_plugins` AS np
			   ON m.id = np.module_id
			INNER JOIN `". DB_PREFIX. "plugin_settings` AS ps
			   ON ps.egg_id = np.id
			  AND ps.name = '". \ze\escape::sql($settingName). "'
			WHERE m.class_name = '". \ze\escape::asciiInSQL($moduleClassName). "'";
		\ze\sql::update($sql);
	}
}
