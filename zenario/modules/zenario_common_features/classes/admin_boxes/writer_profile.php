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

class zenario_common_features__admin_boxes__writer_profile extends ze\moduleBaseClass {

    public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
        if ($box['key']['id']) {
            $details = ze\row::get('writer_profiles', true, $box['key']['id']);

            $box['title'] = ze\admin::phrase(
                'Editing writer profile for [[first_name]] [[last_name]]',
                ['first_name' => $details['first_name'], 'last_name' => $details['last_name']]
            );
            
            //Only admins are allowed to manage writer profiles.
            //Set the required parameters to null to comply with the ze\admin::formatLastUpdated() function.
            $details['created_user_id'] =
            $details['created_username'] =
            $details['last_edited_user_id'] =
            $details['last_edited_username'] = null;

            $values['details/first_name'] = $details['first_name'];
            $values['details/last_name'] = $details['last_name'];
            $values['details/photo'] = $details['photo'];
            $values['details/email'] = $details['email'];
            $values['details/type'] = $details['type'];
            $values['details/admin_id'] = $details['admin_id'];
            $values['details/profile'] = $details['profile'];

            //Hide the type selector and admin picker in "Edit" mode...
            $fields['details/type']['hidden'] = $fields['details/admin_id']['hidden'] = true;
            $fields['details/type_span']['hidden'] = false;

            if ($values['details/type'] == 'administrator') {
                $fields['details/type_span']['label'] = ze\admin::phrase('Created from administrator account:');
                $adminDetails = ze\admin::details($values['details/admin_id']);
                if ($adminDetails) {
                    //... and show the admin string as span...
                    $values['details/type_span'] = ze\admin::formatName($adminDetails);

                    if ($adminDetails['admin_status'] == 'deleted') {
                        $box['tabs']['details']['notices']['admin_acc_trashed']['show'] = true;
                    }
                }
            } elseif ($values['details/type'] == 'external_writer') {
                //... or just show "External writer".
                $values['details/type_span'] = ze\admin::phrase('External writer');
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
            $fields['details/post_count']['hidden'] = false;

            $box['last_updated'] = ze\admin::formatLastUpdated($details);
        } else {
            $box['key']['currentlySelectedAdmin'] = 0;

            if (!ze\priv::check('_PRIV_VIEW_ADMIN')) {
                $fields['details/type']['values']['administrator']['disabled'] = true;
                $fields['details/type']['note_below'] = ze\admin::phrase("Administrator type is disabled, as you do not have permission to view another admin's details.");
            }
        }
    }
    

    public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
        //When an admin is selected in the picker, the first name/last name/email values
        //will be populated with that admin's details.
        if (!$box['key']['id'] && $values['details/type'] == 'administrator' && $values['details/admin_id'] && $box['key']['currentlySelectedAdmin'] != $values['details/admin_id']) {
            $box['key']['currentlySelectedAdmin'] = $values['details/admin_id'];

            $adminDetails = ze\admin::details($values['details/admin_id']);
            if ($adminDetails) {
                $values['details/first_name'] = $adminDetails['first_name'];
                $values['details/last_name'] = $adminDetails['last_name'];
                $values['details/email'] = $adminDetails['email'];
            }
        }
    }


    public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
        if ($values['details/email'] && !\ze\ring::validateEmailAddress($values['details/email'])) {
            $fields['details/email']['error'] = ze\admin::phrase('Please enter a valid email address.');
        }

        //Check that an admin picker is not blank...
        if ($values['details/type'] == 'administrator') {
            if (!$values['details/admin_id']) {
                $fields['details/admin_id']['error'] = ze\admin::phrase('Please select an administrator.');
            } else {
                //... and that an admin account is only in use for one writer profile.
                //Only do this when creating.
                if (!$box['key']['id'] && ze\row::get('writer_profiles', true, ['admin_id' => $values['details/admin_id']])) {
                    $fields['details/admin_id']['error'] = ze\admin::phrase('This administrator already has a writer profile. Please select a different account.');
                }
            }
        }
    }
    
    
    public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
        if (!ze\priv::check('_PRIV_PUBLISH_CONTENT_ITEM')) {
        	exit;
        }

        $details = [
            'first_name' => $values['details/first_name'],
            'last_name' => $values['details/last_name'],
            'email' => $values['details/email'],
            'profile' => ze\ring::sanitiseWYSIWYGEditorHTML($values['details/profile'])
        ];

        if (!empty($values['details/photo'])) {
			if (is_numeric($values['details/photo'])) {
				$details['photo'] = $values['details/photo'];
			} else {
				$thumbnailFilePath = ze\file::getPathOfUploadInCacheDir($values['details/photo']);
				$thumbnailFileFilename = basename(ze\file::getPathOfUploadInCacheDir($values['details/photo']));
				$details['photo'] = ze\file::addToDatabase('image', $thumbnailFilePath, $thumbnailFileFilename);
			}
		} else {
			$details['photo'] = null;
		}

        if (!$box['key']['id']) {
            $details['admin_id'] = $values['details/admin_id'];
            $details['type'] = $values['details/type'];
        }

        $lastUpdated = [];
        ze\admin::setLastUpdated($lastUpdated, !$box['key']['id']);

        if ($box['key']['id']) {
            $details['last_edited'] = $lastUpdated['last_edited'];
            $details['last_edited_admin_id'] = $lastUpdated['last_edited_admin_id'];
        } else {
            $details['created'] = $lastUpdated['created'];
            $details['created_admin_id'] = $lastUpdated['created_admin_id'];
        }

        ze\row::set('writer_profiles', $details, $box['key']['id']);
    }
    
    public function adminBoxSaveCompleted($path, $settingGroup, &$box, &$fields, &$values, $changes) {
        //...
    }
}
