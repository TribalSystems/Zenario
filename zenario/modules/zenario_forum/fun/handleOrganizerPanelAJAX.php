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


switch ($path) {
	case 'zenario__social/nav/forums/panel':
		if (($_POST['remove_forum'] ?? false) && ze\priv::check('_PRIV_MANAGE_ITEM_SLOT')) {
			$sql = "
				DELETE FROM ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "forums
				WHERE post_count = 0
				  AND thread_count = 0
				  AND id = ". (int) $ids;
			ze\sql::update($sql);
		
		} elseif (($_POST['remove_thread_page'] ?? false) && ze\priv::check('_PRIV_MANAGE_ITEM_SLOT')) {
			$sql = "
				UPDATE ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "forums SET
					thread_content_id = 0,
					thread_content_type = ''
				WHERE id = ". (int) $ids;
			ze\sql::update($sql);
		
		} elseif (($_POST['remove_new_thread_page'] ?? false) && ze\priv::check('_PRIV_MANAGE_ITEM_SLOT')) {
			$sql = "
				UPDATE ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "forums SET
					new_thread_content_id = 0,
					new_thread_content_type = ''
				WHERE id = ". (int) $ids;
			ze\sql::update($sql);
		
		} elseif (($_POST['lock_forum'] ?? false) && ze\priv::check('_PRIV_MODERATE_USER_COMMENTS')) {
			$sql = "
				UPDATE ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "forums SET
					locked = 1
				WHERE id = ". (int) $ids;
			ze\sql::update($sql);
		
		} elseif (($_POST['unlock_forum'] ?? false) && ze\priv::check('_PRIV_MODERATE_USER_COMMENTS')) {
			$sql = "
				UPDATE ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "forums SET
					locked = 0
				WHERE id = ". (int) $ids;
			ze\sql::update($sql);
			
		} elseif (($_POST['reorder'] ?? false) && ze\priv::check('_PRIV_REORDER_MENU_ITEM')) {
			foreach (explode(',', $ids) as $id) {
				if (!empty($_POST['ordinals'][$id])) {
					$sql = "
						UPDATE ". DB_PREFIX. ZENARIO_FORUM_PREFIX. "forums SET
							ordinal = ". (int) $_POST['ordinals'][$id]. "
						WHERE id = ". (int) $id;
					ze\sql::update($sql);
				}
			}
		}
		
		break;
}


?>