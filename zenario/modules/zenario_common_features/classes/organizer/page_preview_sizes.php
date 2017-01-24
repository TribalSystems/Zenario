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


class zenario_common_features__organizer__page_preview_sizes extends module_base_class {
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		foreach ($panel['items'] as &$item) {
			$item['css_class'] = 'zenario_preview_icon_'.$item['type'];
		}
	}

	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		if (post('delete') && checkPriv('_PRIV_EDIT_SITE_SETTING')) {
			$sql = '
				DELETE FROM '.DB_NAME_PREFIX.'page_preview_sizes
				WHERE id IN ('.sqlEscape($ids).')';
			sqlQuery($sql);
			// Re-calculate ordinals
			$ord = 1;
			$pagePreviewSizes = getRowsArray('page_preview_sizes', 'id', array(), 'ordinal');
			foreach ($pagePreviewSizes as $pagePreviewSizeId) {
				updateRow('page_preview_sizes', array('ordinal' => $ord++), $pagePreviewSizeId);
			}
		
		} elseif (post('reorder') && checkPriv('_PRIV_EDIT_SITE_SETTING')) {
			foreach (explodeAndTrim($ids) as $id) {
				if (isset($_POST['ordinals'][$id])) {
					$sql = "
						UPDATE ". DB_NAME_PREFIX. "page_preview_sizes 
						SET ordinal = ".sqlEscape($_POST['ordinals'][$id])."
						WHERE id = ". (int)$id;
					sqlUpdate($sql);
				}
			}
		
		} elseif (post('set_default') && checkPriv('_PRIV_EDIT_SITE_SETTING')) {
			updateRow('page_preview_sizes', array('is_default' => 0), array('is_default' => 1));
			updateRow('page_preview_sizes', array('is_default' => 1), array('id' => $ids));
		}
	}
}