<?php
/*
 * Copyright (c) 2018, Tribal Limited
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

switch ($path) {
	
	
	case 'zenario__menu/nav/default_language/panel/tree_explorer':
		$panel['html'] = '
			<iframe
				class="zenario_tree_explorer_iframe"
				style="width: 100%; height: 100%;"
				src="'. htmlspecialchars(
					absCMSDirURL(). 'zenario/admin/tree_explorer/index.php'.
						'?language='. urlencode(FOCUSED_LANGUAGE_ID__NO_QUOTES).
						'&type='. urlencode($refinerName).
						'&id='. urlencode($refinerId).
						'&og=1'
			). '"></iframe>';
		
		break;
		
	
	case 'zenario__modules/panels/modules/hidden_nav/view_frameworks/panel':
		
		if ($refinerName == 'module' && ($module = getModuleDetails($_GET['refiner__module'] ?? false))) {
			$panel['title'] =
				adminPhrase('Frameworks for the Module "[[name]]"', array('name' => $module['display_name']));
			
			$panel['items'] = array();
			foreach (listModuleFrameworks($module['class_name']) as $dir => $framework) {
				$panel['items'][encodeItemIdForOrganizer($dir)] = $framework;
			}
		}
		
		break;

}

return false;
