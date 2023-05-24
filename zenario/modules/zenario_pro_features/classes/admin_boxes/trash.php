<?php
/*
 * Copyright (c) 2023, Tribal Limited
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


class zenario_pro_features__admin_boxes__trash extends ze\moduleBaseClass {
    public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		$ids = ze\ray::explodeAndTrim($box['key']['id']);

        $changes = [];
        $totalRowNum = count($ids);
		ze\tuix::setupMultipleRows(
			$box, $fields, $values, $changes, $filling = true,
			$box['tabs']['trash']['pro_features_trash_template_fields'],
			$totalRowNum,
			$minNumRows = 0,
			$tabName = 'trash',
            '', '', '',
            $firstN = 10001
		);

        $i = 10000;
        foreach ($ids as $tagId) {
            $i++;
            $cID = $cType = false;
            ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
            $alias = ze\row::get('content_items', 'alias', ['id' => $cID, 'type' => $cType]);
            $box['tabs']['trash']['fields']['alias__' . $i]['value'] = $alias;
            ze\lang::applyMergeFields($box['tabs']['trash']['fields']['create_spare_alias__' . $i]['label'], ['content_item' => ze\content::formatTagFromTagId($tagId)]);
        }
	}

    public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
        $ids = ze\ray::explodeAndTrim($box['key']['id']);

        $suffix = ze::setting('mod_rewrite_suffix');

        $i = 10000;
        foreach ($ids as $tagId) {
            $i++;
            $fields['trash/alias__' . $i]['hidden'] =
            $fields['trash/preview__' . $i]['hidden'] =
            $fields['trash/target_loc__' . $i]['hidden'] = !$values['trash/create_spare_alias__' . $i];

            $fields['trash/hyperlink_target__' . $i]['hidden'] = !$values['trash/create_spare_alias__' . $i] || $values['trash/target_loc__' . $i] != 'int';
            $fields['trash/ext_url__' . $i]['hidden'] = !$values['trash/create_spare_alias__' . $i] || $values['trash/target_loc__' . $i] != 'ext';
            
            //Remember redirect target
            if (!empty($fields['trash/target_loc__' . $i]['hidden'])) {
                if ($values['trash/target_loc__' . $i] == 'int') {
                    $targetTagId = $values['trash/hyperlink_target__' . $i];
                    if ($targetTagId) {
                        $cID = $cType = false;
                        ze\content::getCIDAndCTypeFromTagId($cID, $cType, $tagId);
                        $values['trash/redirect_target_url__' . $i] = ze\link::toItem($targetTagId, $cType, true, '', false, false, $forceAliasInAdminMode = true);
                    }
                } elseif ($values['trash/target_loc__' . $i] == 'ext') {
                    $target = $values['trash/ext_url__' . $i];
                    if (!preg_match("/^((http|https|ftp):\/\/)/", $target)) {
                        $target = 'http://' . $target;
                    }
                    $values['trash/redirect_target_url__' . $i] = $target;
                }
            }

            //Show preview
            $alias = $values['trash/alias__' . $i];
            if ($alias !== "") {
                if ($suffix && strpos($alias, $suffix) === false) {
                    $alias .= $suffix;
                }
            }

            $fields['trash/preview__' . $i]['snippet']['html'] = '<a id="spare_alias_preview__' . htmlspecialchars($i) . '" data-base="' . ze\link::absolute() . '" data-suffix="' . $suffix . '" href="' . ze\link::absolute() . $alias . '" target="spare_alias_preview">' . ze\link::absolute() . $alias . '</a>';
        }
	}

    public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		ze\priv::exitIfNot('_PRIV_PUBLISH_CONTENT_ITEM');

        $ids = ze\ray::explodeAndTrim($box['key']['id']);
		
        $i = 10000;
        foreach ($ids as $tagId) {
            $i++;
            if ($values['trash/create_spare_alias__' . $i]) {
                $alias = $values['trash/alias__' . $i];
                
                $row = [
                    'ext_url' => '',
                    'content_id' => 0,
                    'content_type' => ''
                ];
                
                if ($values['trash/target_loc__' . $i] == 'int') {
                    $row['target_loc'] = 'int';
                    ze\content::getCIDAndCTypeFromTagId($row['content_id'], $row['content_type'], $values['trash/hyperlink_target__' . $i]);
                
                } elseif ($values['trash/target_loc__' . $i] == 'ext') {
                    $row['target_loc'] = 'ext';
                    $row['ext_url'] = $values['trash/ext_url__' . $i];
                
                }
                
                if (!$box['key']['id']) {
                    $row['created_datetime'] = ze\date::now();
                }
                
                ze\row::set('spare_aliases', $row, ['alias' => $alias]);
            }
        }
	}
}