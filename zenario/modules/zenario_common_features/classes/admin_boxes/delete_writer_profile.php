<?php
/*
 * Copyright (c) 2022, Tribal Limited
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

class zenario_common_features__admin_boxes__delete_writer_profile extends ze\moduleBaseClass {

    public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
        if ($box['key']['id']) {
            $details = ze\row::get('writer_profiles', true, $box['key']['id']);
            $name = $details['first_name'];
            if ($details['last_name']) {
                $name .= ' ' . $details['last_name'];
            }

            $sql = "
                SELECT COUNT(DISTINCT ci.tag_id)
                FROM " . DB_PREFIX . "content_item_versions v
                INNER JOIN " . DB_PREFIX . "content_items ci
                    ON ci.id = v.id
                    AND ci.type = v.type
                WHERE v.writer_id = " . (int) $box['key']['id'] . "
                AND v.published_datetime IS NOT NULL
                AND v.version = ci.visitor_version";
            $result = ze\sql::select($sql);
            $values['details/post_count'] = ze\sql::fetchValue($result);

            if ($values['details/post_count']) {
                $sql = "
                    SELECT v.published_datetime
                    FROM " . DB_PREFIX . "content_item_versions v
                    INNER JOIN " . DB_PREFIX . "content_items ci
                        ON ci.id = v.id
                        AND ci.type = v.type
                    WHERE writer_id = " . (int) $box['key']['id'] . "
                    AND v.published_datetime IS NOT NULL
                    AND v.version = ci.visitor_version
                    ORDER BY v.published_datetime DESC
                    LIMIT 1";
                $result = ze\sql::select($sql);
                $lastPublished = ze\sql::fetchValue($result);
                $lastPublished = ze\date::format($lastPublished);

                if ($values['details/post_count'] == 1) {
                    $box['tabs']['details']['notices']['delete_post_count_1']['show'] = true;
                    ze\lang::applyMergeFields(
                        $box['tabs']['details']['notices']['delete_post_count_1']['message'],
                        ['name' => $name, 'last_published' => $lastPublished]
                    );
                } elseif ($values['details/post_count'] > 1) {
                    $box['tabs']['details']['notices']['delete']['show'] = true;
                    ze\lang::applyMergeFields(
                        $box['tabs']['details']['notices']['delete']['message'],
                        ['name' => $name, 'post_count' => (int) $values['details/post_count'], 'last_published' => $lastPublished]
                    );
                }
            } else {
                $box['tabs']['details']['notices']['delete_no_post_count']['show'] = true;
                ze\lang::applyMergeFields(
                    $box['tabs']['details']['notices']['delete_no_post_count']['message'],
                    ['name' => $name]
                );
            }
        } else {
            exit;
        }
    }
    
    
    public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
        if (!$box['key']['id'] || !ze\priv::check('_PRIV_PUBLISH_CONTENT_ITEM')) {
        	exit;
        }

        ze\row::delete('writer_profiles', $box['key']['id']);
    }
}
