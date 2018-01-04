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

class zenario_event_slideshow extends module_base_class {
	
	private $data = array();
	
	public function init() {
		$this->data['maxHeight'] = $this->setting('slide_height');
		$this->data['maxWidth'] = $this->setting('slide_width');
		$this->data['title'] = $this->phrase($this->setting('title'));
		$sqlSelect = '
			SELECT
				c.id, c.type, c.visitor_version, v.title
			FROM '.DB_NAME_PREFIX.'content_items c';
		$sqlJoins = '
			INNER JOIN '.DB_NAME_PREFIX.'content_item_versions v
				ON (c.tag_id = v.tag_id) AND (c.visitor_version = v.version)
			INNER JOIN '.DB_NAME_PREFIX.'translation_chains tc
				ON (c.equiv_id = tc.equiv_id) AND (c.type = tc.type)
			INNER JOIN '.DB_NAME_PREFIX. ZENARIO_CTYPE_EVENT_PREFIX.'content_event cv
				ON (c.id = cv.id) AND (c.visitor_version = cv.version)';
		$sqlWhere = '
			WHERE c.type = "event"
			AND c.status = "published"
			AND c.language_id = "'.sqlEscape(cms_core::$langId).'"
			AND cv.end_date >= CURDATE()
			AND tc.privacy = "public"';
		if ($this->setting('filter_by_category') && $this->setting('content_category')) {
			$sqlJoins .= '
				INNER JOIN '.DB_NAME_PREFIX.'category_item_link cil
					ON (c.equiv_id = cil.equiv_id) AND (c.type = cil.content_type)';
			$sqlWhere .= '
				AND cil.category_id = '.(int)$this->setting('content_category');
		}
		$sql = $sqlSelect . $sqlJoins . $sqlWhere;
		$result = sqlQuery($sql);
		while ($row = sqlFetchAssoc($result)) {
			$width = $height = $url = false;
			if ($stickyImage = 
				Ze\File::itemStickyImageLink(
					$width, 
					$height, 
					$url, 
					$row['id'], 
					$row['type'], 
					$row['visitor_version'], 
					$this->setting('slide_width'), 
					$this->setting('slide_height'), 
					$this->setting('slide_canvas')))
			{
				$row['img'] = $url;
				$this->data['maxHeight'] = max($height, $this->data['maxHeight']);
				$this->data['maxWidth'] = max($width, $this->data['maxWidth']);
			} else {
				$row['img'] = $stickyImage;
				$cId = $cType = $cVersion = false;
			}
			$row['link'] = linkToItem($row['id'], $row['type'], true);
			$this->data['events'][] = $row;
		}
		if (!$this->data['maxWidth'] && !$this->data['maxHeight']) {
			$this->data['maxWidth'] = 200;
			$this->data['maxHeight'] = 100;
		}
		
		$this->callScript('zenario_event_slideshow', 'initiateSlideshow', $this->instanceId);
		return true;
	}
	
	public function showSlot() {
		$this->twigFramework($this->data);
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch($path) {
			case 'plugin_settings':
				$fields['first_tab/content_category']['hidden'] = !$values['first_tab/filter_by_category'];
				
				$this->showHideImageOptions($fields, $values, 'first_tab', false, 'slide_');
				break;
		}
		
	}
}