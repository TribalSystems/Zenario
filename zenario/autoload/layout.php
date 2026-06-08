<?php
/*
 * Copyright (c) 2026, Tribal Limited
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

class layout {



	public static function details($layoutId, $showUsage = true, $checkIfDefault = false) {
		$sql = "
			SELECT
				l.layout_id,
				CONCAT('L', IF (l.layout_id < 10, LPAD(CAST(l.layout_id AS CHAR), 2, '0'), CAST(l.layout_id AS CHAR))) AS code_name,
				l.name,
				l.content_type,
				l.status,
				l.header_and_footer,
				l.skin_id,
				l.css_class,
				l.json_data_hash";
		
		if ($checkIfDefault) {
			$sql .= ",
				ct.content_type_name_en AS default_layout_for_ctype";
		}
		
		if ($showUsage) {
			$sql .= ",
				(
					SELECT count(DISTINCT tag_id)
					FROM " . DB_PREFIX . "content_item_versions
					WHERE layout_id = " . (int) $layoutId . "
				) AS content_item_count
			";
		}
		
		$sql .= "
			FROM ". DB_PREFIX. "layouts l";
		
		if ($checkIfDefault) {
			$sql .= "
			LEFT JOIN " . DB_PREFIX . "content_types ct
			   ON ct.default_layout_id = l.layout_id";
		}
		
		$sql .= "
			WHERE l.layout_id = ". (int) $layoutId;
		
		if ($layout = \ze\sql::fetchAssoc($sql)) {
			$layout['id_and_name'] = $layout['code_name']. ' '. $layout['name'];
		}
		
		return $layout;
	}


	//Allow access to some of the layout parameters from Twig
	const isResponsiveFromTwig = true;
	public static function isResponsive() {
		return \ze::$responsive;
	}
	const minWidthFromTwig = true;
	public static function minWidth() {
		return \ze::$minWidth;
	}
	const maxWidthFromTwig = true;
	public static function maxWidth() {
		return \ze::$maxWidth;
	}
	
	public static function htmlPath($layoutId, $reportErrors = false) {
		return self::generateFiles($layoutId, true, false, $reportErrors);
	}
	public static function cssPath($layoutId, $reportErrors = false) {
		return self::generateFiles($layoutId, false, true, $reportErrors);
	}
	
	private static function generateFiles($layoutId, $generateHTML, $generateCSS, $reportErrors = false) {
		if ($layout = \ze\layout::details($layoutId, $showUsage = false, $checkIfDefault = false)) {
			$codeName = $layout['code_name'];
			if ($layoutDir = \ze\cache::createDir($codeName. '_'. $layout['json_data_hash'], 'cache/layouts')) {
				
				$data = null;
				$tplFile = $layoutDir. $codeName. '.tpl.php';
				$cssFile = $layoutDir. $codeName. '.css';
				$minFile = $layoutDir. $codeName. '.min.css';
				
				if ($generateHTML && !file_exists(CMS_ROOT. $tplFile)) {
					if (is_writable(CMS_ROOT. $layoutDir)) {
						$html = '';
						$slots = [];
						$data = \ze\row::get('layouts', 'json_data', $layoutId);
						\ze\gridAdm::checkData($data);
					
						\ze\gridAdm::generateHTML($html, $data, $slots);
					
						if (file_put_contents(CMS_ROOT. $tplFile, $html)) {
							\ze\cache::chmod(CMS_ROOT. $tplFile);
						} elseif ($reportErrors) {
							\ze\contentAdm::debugAndReportLayoutError($tplFile);
						} else {
							return false;
						}
					} elseif ($reportErrors) {
						\ze\contentAdm::debugAndReportLayoutError($tplFile);
					} else {
						return false;
					}
				}
				
				if ($generateCSS && !file_exists(CMS_ROOT. $minFile)) {
					if (is_writable(CMS_ROOT. $layoutDir)) {
						$html = '';
						if ($data === null) {
							$data = \ze\row::get('layouts', 'json_data', $layoutId);
							\ze\gridAdm::checkData($data);
						}
					
						\ze\gridAdm::generateCSS($css, $data);
					
						if (file_put_contents(CMS_ROOT. $cssFile, $css)) {
							\ze\cache::chmod(CMS_ROOT. $cssFile);
							
							$minifier = new \MatthiasMullie\Minify\CSS($css);
							$minifier->minify(CMS_ROOT. $minFile);
							\ze\cache::chmod(CMS_ROOT. $minFile);
							
						} elseif ($reportErrors) {
							\ze\contentAdm::debugAndReportLayoutError($cssFile);
						} else {
							return false;
						}
					} elseif ($reportErrors) {
						\ze\contentAdm::debugAndReportLayoutError($cssFile);
					} else {
						return false;
					}
				}
				
				if ($generateHTML) {
					if ($generateCSS) {
						return [$tplFile, $cssFile];
					} else {
						return $tplFile;
					}
				} else {
					if ($generateCSS) {
						return $minFile;
					}
				}
			
			} elseif ($reportErrors) {
				\ze\contentAdm::debugAndReportLayoutError();
			}
		}
		
		return false;
	}
	
	
	//	Output site-wide HTML  //
	
	public static function sitewideHTML($setting, $twigSupport = false) {
		
		$string = \ze::setting($setting);
		echo "\n", $string, "\n";
		
		if ($twigSupport
		 && \ze::setting($setting. '.run_twig')
		 && ($twigPath = \ze\plugin::twigSnippetPath(\ze::setting($setting. '.twig_snippet'), true))) {
				
			//Read any variables defined in the site settings
			if ($yaml = \ze::setting($setting. '.twig_vars')) {
				$yaml = trim($yaml);
				if (!empty($yaml)) {
					try {
						$twigVars = \Spyc::YAMLLoadString($yaml);
					} catch (\Exception $e) {
						$twigVars = [];
					}
					
					if (empty($twigVars) || !is_array($twigVars)) {
						$twigVars = [];
					}
				}
			}
			
			//Add the CMS' environment variables
			$twigVars['equivId'] = \ze::$equivId;
			$twigVars['cID'] = \ze::$cID;
			$twigVars['cType'] = \ze::$cType;
			$twigVars['cVersion'] = \ze::$cVersion;
			$twigVars['isDraft'] = \ze::$isDraft;
			$twigVars['alias'] = \ze::$alias;
			$twigVars['langId'] = \ze::$langId;
			$twigVars['adminId'] = \ze::$adminId;
			$twigVars['userId'] = \ze::$userId;
			$twigVars['vars'] = \ze::$vars;
			
			\ze::ignoreErrors();
				$string = \ze\twig::render($twigPath, $twigVars);
			\ze::noteErrors();
			
			echo "\n", $string, "\n";
			
		}
	}



}