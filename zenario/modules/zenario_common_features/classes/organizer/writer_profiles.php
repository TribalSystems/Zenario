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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');



class zenario_common_features__organizer__writer_profiles extends ze\moduleBaseClass {
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		//Process panel items
        if (!empty($panel['items'])) {
            foreach ($panel['items'] as $id => &$item) {
                $item['name'] = $item['first_name'] . ' ' . $item['last_name'];

                switch($item['type']) {
                    case 'administrator':
                        $item['type'] = ze\admin::phrase('Administrator');
                        break;
                    case 'external_writer':
                        $item['type'] = ze\admin::phrase('External writer');
                        break;
                }

                if (!empty($item['photo'])) {
                    $imageChecksum = ze\row::get('files', 'checksum', ['id' => (int) $item['photo']]);
                    $item['has_image'] = true;
                    
                    $img = '&usage=image&c='. $imageChecksum;
        
                    $item['image'] = 'zenario/file.php?og=1'. $img;
                }

                $sql = "
                    SELECT COUNT(DISTINCT ci.tag_id)
                    FROM " . DB_PREFIX . "content_item_versions v
                    INNER JOIN " . DB_PREFIX . "content_items ci
                        ON ci.id = v.id
                        AND ci.type = v.type
                    WHERE v.writer_id = " . (int) $item['id'] . "
                    AND v.published_datetime IS NOT NULL
                    AND v.version = ci.visitor_version";
                $result = ze\sql::select($sql);
                $item['post_count'] = ze\sql::fetchValue($result);
            }
        }

        //Process enabled content types: check which ones use writer profiles.
        $contentItemsUsingWriterProfiles = [];
        foreach (ze\content::getContentTypes(true, true) as $cType) {
            if (ze\row::get('content_types', 'writer_field', ['content_type_id' => $cType['content_type_id']]) != 'hidden') {
                $contentItemsUsingWriterProfiles[$cType['content_type_id']] = $cType['content_type_name_en'];
            }
        }

        //Build the link to content types panel
        $siteSettingsLink = "<a href='organizer.php#zenario__content/panels/content_types' target='_blank'>site settings</a>";

        if (count($contentItemsUsingWriterProfiles) > 0) {
            $panel['notice']['type'] = 'information';
            $panel['notice']['message'] = $this->phrase(
                "Writer profiles are used by content types: [[content_types]], see [[site_settings_link]].",
                ['site_settings_link' => $siteSettingsLink, 'content_types' => implode(', ', $contentItemsUsingWriterProfiles)]
            );
        } else {
            $panel['notice']['type'] = 'warning';
            $panel['notice']['message'] = $this->phrase(
                "Writer profiles are not used by any content types, see [[site_settings_link]].",
                ['site_settings_link' => $siteSettingsLink]
            );
        }
	}
}