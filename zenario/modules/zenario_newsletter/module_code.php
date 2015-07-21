<?php
/*
 * Copyright (c) 2015, Tribal Limited
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




/**
 * This file is the main program for this "Hello world" plugin. 
 * The file itself must be called module_code.php. 
 */

/**
 * The name of the following class must match the Module's directory name!
 */
class zenario_newsletter extends module_base_class {


	public static function getTrackerURL() {
		return 'http://'. primaryDomain(). SUBDIRECTORY. moduleDir('zenario_newsletter', 'tracker/');
	}
	

	protected static function newsletterRecipients($id, $mode) {
		if (!checkRowExists(ZENARIO_NEWSLETTER_PREFIX. 'newsletters', $id) || 
			!checkRowExists(ZENARIO_NEWSLETTER_PREFIX. 'newsletter_smart_group_link', array('newsletter_id' => $id)) ) {
			return false;
		} 
		
		$parts = array();
		foreach ( getRowsArray(ZENARIO_NEWSLETTER_PREFIX. 'newsletter_smart_group_link', 'smart_group_id', array('newsletter_id' => $id)) as $smartGroupId )  {
			if ($rv = self::newsletterRecipientsPart($id, $mode, $smartGroupId)) {
				$parts[] = $rv;
			}
		}
		
		if (count($parts)) {
			switch ($mode) {
				case 'count':
					$sql = "SELECT 
								COUNT(DISTINCT id) 
							FROM (" . implode(" UNION ", $parts) . ") A";

					$result = sqlQuery($sql);
					$row = sqlFetchRow($result);
					return $row[0];
					break;
				case 'populate': 
					$sql = "
						INSERT IGNORE INTO ". DB_NAME_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletter_user_link
							(newsletter_id, user_id, tracker_hash, remove_hash, delete_account_hash, username, `email`) "
						. implode(" UNION ", $parts);
					sqlUpdate($sql, false);
					return sqlAffectedRows();
				case 'get_sql':
					return array(
								'table_join' =>  "(" . implode(" UNION ", $parts) . ") r ON r.id = u.id",
								'where_statement' => " r.id IS NOT NULL "
								);
			}
		} else {
			switch ($mode) {
				case 'count':
				case 'populate': 
					return 0;
				case 'get_sql':
					return false;
			}
		}
	}
	
	protected static function newsletterRecipientsPart($id, $mode, $smartGroupId) {
		switch ($mode) {
			case 'count':
				$sql = "
					SELECT DISTINCT u.id";
				break;
			
			case 'populate':
				$sql = "
					SELECT DISTINCT
						". (int) $id. " AS newsletter_id,
						u.id,
						SHA(CONCAT(u.id, '_', ". (int) $id. ", '_tracker')) AS tracker_hash,
						SHA(CONCAT(u.id, '_', ". (int) $id. ", '_unsubscribe')) AS remove_hash,
						SHA(CONCAT(u.id, '_', ". (int) $id. ", '_remove')) AS delete_account_hash,
						u.screen_name,
						u.email";
				break;
			
			case 'get_sql':
				$sql = "SELECT u.id";
				break;
			default:
				return false;
		}
		
		$sql .= "
			FROM ". DB_NAME_PREFIX. "users AS u
			LEFT JOIN ". DB_NAME_PREFIX. "users_custom_data AS ucd
			   ON ucd.user_id = u.id";
		
		$whereStatement = "
			WHERE u.email != ''";
		//AND u.status IN('active', 'contact')";
		
		foreach (getRowsArray(
			ZENARIO_NEWSLETTER_PREFIX. 'newsletter_sent_newsletter_link',
			'sent_newsletter_id',
			array('newsletter_id' => $id)
		) as $sentNewsletterId) {
			$sql .= "
				LEFT JOIN ". DB_NAME_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletter_user_link AS nul_". (int) $sentNewsletterId. "
				   ON nul_". (int) $sentNewsletterId. ".newsletter_id = ". (int) $sentNewsletterId. "
				  AND nul_". (int) $sentNewsletterId. ".user_id = u.id";
		
			$whereStatement .= "
				AND nul_". (int) $sentNewsletterId. ".user_id IS NULL";
		}
		
		if ($smartGroupId) {
			$whereStatement .= smartGroupSQL($smartGroupId);
		
		} else {
			return false;
		}
		
		$cField = false;
		if (($cFieldId = setting('zenario_newsletter__all_newsletters_opt_out'))
		 && ($cField = getDatasetFieldDetails($cFieldId))) {
			$sql .= "
				LEFT JOIN ". DB_NAME_PREFIX. "users_custom_data AS noo
				   ON noo.user_id = u.id
				  AND noo.`". sqlEscape($cField['db_column']). "` = 1";
		}

		$sql .= $whereStatement;
		
		if ($cField) {
			$sql .= "
			  AND noo.user_id IS NULL";
		}

		return $sql;
	}
	
	
	public function preFillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function fillOrganizerPanel($path, &$panel, $refinerName, $refinerId, $mode) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function handleOrganizerPanelAJAX($path, $ids, $ids2, $refinerName, $refinerId) {
		return require funIncPath(__FILE__, __FUNCTION__);
	}



	
	//Load the details of a/for a Newsletter or Newsletter Design
	//Or alternately create a new Newsletter/Newsletter Design using another as a template
	//Or if there are no details to load, attempt to set the fields to some default values
	function loadDetails($id) {
		$sql = "
			SELECT 
				newsletter_name, 
				subject, 
				status, 
				email_address_from, 
				email_name_from, 
				body, 
				unsubscribe_text, 
				delete_account_text
			FROM ". DB_NAME_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletters
			WHERE id = ". (int) $id;
		$result = sqlQuery($sql);
		return sqlFetchAssoc($result);
	}
	
	
	public static function checkIfNewsletterIsADraft($id) {
		return getRow(ZENARIO_NEWSLETTER_PREFIX. 'newsletters', 'status', $id) == '_DRAFT';
	}
	
	function checkIfNewsletterIsInProgress($id) {
		return getRow(ZENARIO_NEWSLETTER_PREFIX. 'newsletters', 'status', $id) == '_IN_PROGRESS';
	}
	
	//Inclusion/Exclusion admin box (new Admin Box toolkit)
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function validateAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes, $saving) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	public function saveAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		require funIncPath(__FILE__, __FUNCTION__);
	}
	
	
	
	

	//Send a test email
	function testSendNewsletter($body, $adminDetails, $email, $subject, $emailAddresFrom, $emailNameFrom, $newsletterId) {
		$newsletterArray = array("id" => $newsletterId,"body" => $body);
		$body = zenario_newsletter::createTrackerHyperlinks($newsletterArray);
		//Check if there is a User set up with this email address
		if (!$user = $this->getDetailsFromEmail($email)) {
			//Send the test email with the current Admin's details, rather than a User.
			$user['first_name'] = $adminDetails['admin_first_name'];
			$user['last_name'] = $adminDetails['admin_last_name'];
			$user['admin_account'] = true;
			
			//The admins table doesn't have a title field.
			$user['title'] = adminPhrase('(Title)');
		}
		
		$subject .= ' | TEST SEND';

		//Attempt to send the email
		$emailOverriddenBy = false;
		return sendEmail(
			zenario_newsletter::applyNewsletterMergeFields($subject, $user, zenario_newsletter::getTrackerURL()),
			zenario_newsletter::applyNewsletterMergeFields($body, $user, zenario_newsletter::getTrackerURL()),
			$email, 
			$emailOverriddenBy,
			$user['first_name']. ' '. $user['last_name'],
			$emailAddresFrom, $emailNameFrom
		);
	}
	
	//Attempt to get details of a user from their email address
	function getDetailsFromEmail($email) {
		$sql = "
			SELECT id, salutation, salutation as title, first_name, last_name
			FROM ". DB_NAME_PREFIX. "users
			WHERE email = '". sqlEscape($email). "'";
		
		$result = sqlQuery($sql);
		return sqlFetchAssoc($result);
	}
	
	
	//Code to send every newsletter that needs sending
	//Ideally should be run as a job
	public static function jobSendNewsletters() {
		$action = false;
		
		$sql = "
			SELECT id, newsletter_name
			FROM ". DB_NAME_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletters
			WHERE status = '_IN_PROGRESS'";
		$result = sqlQuery($sql);
		
		while ($newsletter = sqlFetchAssoc($result)) {
			zenario_newsletter::sendNewsletter($newsletter['id']);
			echo adminPhrase('Newsletter "[[newsletter_name]]" was sent.', $newsletter), "\n";
			$action = true;
		}
		
		return $action;
	}
	
	// Send the newsletter to all admins
	private static function sendNewsletterToAdmins($id, $mode = 'myself') {
		
		if ($mode == 'none') {
			return false;
		}
		
		// Get admins
		$admins = array();
		$sql = '
			SELECT
				id,
				"" AS title,
				first_name,
				last_name,
				email,
				1 AS admin_account
			FROM '.DB_NAME_PREFIX.'admins
			WHERE status = \'active\'';
		
		if ($mode == 'myself') {
			$sql .= 'AND id = '.(int)adminId();
		}
		
		$result = sqlSelect($sql);
		while ($row = sqlFetchAssoc($result)) {
			$admins[] = $row;
		}
		// Get newsletter
		$sql = "
			SELECT 
				id, 
				subject, 
				email_address_from, 
				email_name_from, 
				url, 
				body, 
				unsubscribe_text, 
				delete_account_text
			FROM "
				. DB_NAME_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletters
			WHERE 
				id = ". (int) $id;
		$result = sqlSelect($sql);
		$newsletter = sqlFetchAssoc($result);
		// Apply merge fields and send emails
		foreach ($admins as $id => $admin) {
			$newsletterSubject = zenario_newsletter::applyNewsletterMergeFields($newsletter['subject'], $admin, $newsletter['url']);
			$newsletterBody = zenario_newsletter::applyNewsletterMergeFields($newsletter['body'], $admin, $newsletter['url']);
			$emailOverriddenBy = false;
			sendEmail(
				$newsletterSubject. ' - ADMIN COPY',
				$newsletterBody,
				$admin['email'], 
				$emailOverriddenBy,
				$admin['first_name']. ' '. $admin['last_name'],
				$newsletter['email_address_from'], 
				$newsletter['email_name_from'], 
				array(),
				array(),
				'bulk');
		}
	}
	
	//Code to send a newsletter
	//Ideally should be run as a job
	public static function sendNewsletter($id) {
		$sql = "
			SELECT 
				id, 
				subject, 
				email_address_from, 
				email_name_from, 
				url, 
				body, 
				unsubscribe_text, 
				delete_account_text
			FROM "
				. DB_NAME_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletters
			WHERE 
				id = ". (int) $id;
		$result = sqlQuery($sql);
		$newsletter = sqlFetchAssoc($result);
		if ($newsletter['unsubscribe_text']) {
			$newsletter['body'] .= '<p style="font-size: 11px;">' . htmlspecialchars($newsletter['unsubscribe_text']) . ' <a href="[[REMOVE_FROM_GROUPS_LINK]]">[[REMOVE_FROM_GROUPS_LINK]]</a></p>';
		}

		if ($newsletter['delete_account_text']) {
			$newsletter['body'] .= '<p style="font-size: 11px;">' . htmlspecialchars($newsletter['delete_account_text']) . ' <a href="[[DELETE_ACCOUNT_LINK]]">[[DELETE_ACCOUNT_LINK]]</a></p>';
		}
		
		$sql = "
			SELECT 
				user_id, 
				tracker_hash, 
				remove_hash, 
				delete_account_hash
			FROM "
				. DB_NAME_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletter_user_link
			WHERE 
					email_sent = 0
			  AND 	newsletter_id = ". (int) $newsletter['id'];
		$result = sqlQuery($sql);
		
		while ($hashes = sqlFetchAssoc($result)) {
			$userNewsletter = $newsletter;
			$userNewsletter['body'] = zenario_newsletter::createTrackerHyperlinks($userNewsletter, $hashes['user_id'], $hashes);
			//send newsletter email 
			
			zenario_newsletter::sendNewsletterToUser($userNewsletter, $hashes['user_id'], $hashes);
		}
		
		updateRow(ZENARIO_NEWSLETTER_PREFIX. 'newsletters', array('status' => '_ARCHIVED'), $id);
		updateRow('inline_images', array('archived' => 1), array('foreign_key_to' => 'newsletter', 'foreign_key_id' => $id));
	}
	
	
	protected static function sendNewsletterToUser(&$newsletter, $userId, $hashes) {

		$sql = "
			SELECT 
				id, 
				email,
				salutation,
				salutation as title, 
				first_name, 
				last_name
			FROM "
				. DB_NAME_PREFIX. "users
			WHERE 
				id = ". (int) $userId. "
			LIMIT 1";
		$result = sqlQuery($sql);
		$user = sqlFetchAssoc($result);

		$sql = "
			UPDATE ". DB_NAME_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletter_user_link SET
				email_sent = 1,
				time_sent = NOW()
			WHERE newsletter_id = ". (int) $newsletter['id']. "
			  AND email_sent = 0
			  AND user_id = ". (int) $userId;
		
		sqlUpdate($sql, false);
		$affected_rows = sqlAffectedRows();
		
		if (!sqlAffectedRows()) {
			return;
		}
		
		$newsletterSubject = zenario_newsletter::applyNewsletterMergeFields($newsletter['subject'], $user, $newsletter['url'], $hashes['remove_hash'], $hashes['delete_account_hash']);
		$newsletterBody = zenario_newsletter::applyNewsletterMergeFields($newsletter['body'], $user, $newsletter['url'], $hashes['remove_hash'], $hashes['delete_account_hash']);
		
		$emailOverriddenBy = false;
		
		if (sendEmail(
			$newsletterSubject,
			$newsletterBody,
			$user['email'], 
			$emailOverriddenBy,
			$user['first_name']. ' '. $user['last_name'],
			$newsletter['email_address_from'], 
			$newsletter['email_name_from'], 
			array(),
			array(),
			'bulk'
		)) {
			
			$sql = "
				UPDATE ". DB_NAME_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletter_user_link SET
					email_sent = 2,
					email_overridden_by = '". sqlEscape($emailOverriddenBy). "'
				WHERE newsletter_id = ". (int) $newsletter['id']. "
				  AND email_sent = 1
				  AND user_id = ". (int) $user['id'];
			
			sqlQuery($sql);
		}
	}
	
	protected static function createTrackerHyperlinks($newsletter, $userId = false, $hashes = false) {
		$linkCount = 1;
		$outputHTML = '';
		$ATagResults = preg_split('@(<[aA].*?>.*?</[aA].*?>)@', $newsletter["body"], -1, PREG_SPLIT_DELIM_CAPTURE); //gets <a> tags
		foreach ($ATagResults as $i => $str) {
			if ($i % 2) {
				//every odd element will be an <a></a>
				$HrefResults = preg_split('@href=[\'"]([^\'"]+)[\'"]@', $str, -1, PREG_SPLIT_DELIM_CAPTURE); // gets href's
				foreach ($HrefResults as $i => $str2) {
					$matches = $href = '';
					$isDeleteOrSubLink = false;
					if ($i % 2) {
						//Every odd element will be a href
						$href = $str2;
						if($href == '#') {
							$href = "not-found";
						}
						if($href == '[[REMOVE_FROM_GROUPS_LINK]]' || $href =='[[DELETE_ACCOUNT_LINK]]') {
							$isDeleteOrSubLink = true;
						}
															
						if(!preg_match('@\w+:\/\/@', $href) && strpos($href, 'mailto:') === false) {
							$href = 'http://'. primaryDomain() . SUBDIRECTORY . $href; //if hyper does not contain :// treat hyperlink as content item alis
						}
						if(!$isDeleteOrSubLink) {
							preg_match('@>(.+)<@', $str, $linktextArray);//Finds link text
							$hyperlinkId = setRow(ZENARIO_NEWSLETTER_PREFIX. "newsletters_hyperlinks", array("newsletter_id" => $newsletter["id"], "link_ordinal"=> $linkCount, "hyperlink"=>$href, "link_text" => $linktextArray[1], "clickthrough_count" => 0, "last_clicked_date" => NULL), array("newsletter_id" => $newsletter["id"], "link_ordinal"=> $linkCount)); // create hyperlink record
							$sql = "UPDATE " . DB_NAME_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletters_hyperlinks SET
									hyperlink_hash = SHA('" . $hyperlinkId . "_" . $newsletter['id'] . "')
									WHERE id = " . $hyperlinkId;
							sqlQuery($sql);
							$hyperlinkHash = getRow(ZENARIO_NEWSLETTER_PREFIX. "newsletters_hyperlinks", "hyperlink_hash", array('id' => $hyperlinkId)); //get hyper_hash code 
							if ($userId && $hashes) {
								$trackerHyperlink = zenario_newsletter::getTrackerURL() . 'link_tracker.php?' . 't=' . $hashes['tracker_hash'] . '&' . 'nlink=' . $hyperlinkHash;
								$outputHTML .= 'href="'. htmlspecialchars($trackerHyperlink) . '"';
								$linkCount = $linkCount + 1;
							} else {
								$trackerHyperlink = zenario_newsletter::getTrackerURL() . 'link_tracker.php?' . 'nlink=' . $hyperlinkHash;
								$outputHTML .= 'href="'. htmlspecialchars($trackerHyperlink) . '"';
								$linkCount = $linkCount + 1;
							}
						}
						
					} else {
						//Every even element will be non href html
						$outputHTML .= $str2;
					}
				}
			} else {
				//every even element will be non <a> tag html
				$outputHTML .= $str;
			}
		}
		return $outputHTML;
	}
	
	protected static function applyNewsletterMergeFields(
		&$body, &$user, $url,
		$removeFromGroups = 'XXXXXXXXXXXXXXX',
		$deleteAccountHash = 'XXXXXXXXXXXXXXX'
	) {
		$search = array();
		$replace = array();
		if (isset($user['admin_account'])) {
			$search[] = '[[TITLE]]';
			$replace[] = htmlspecialchars($user['title']);
			
			$search[] = '[[FIRST_NAME]]';
			$replace[] = htmlspecialchars($user['first_name']);
			
			$search[] = '[[LAST_NAME]]';
			$replace[] = htmlspecialchars($user['last_name']);
			
		} else {
			$userDetails = getUserDetails($user['id']);
			foreach($userDetails as $dbColumn => $value) {
				$search[] = '[['.$dbColumn.']]';
				$replace[] = htmlspecialchars($value);
			}
		}
		
		$search[] = '[[REMOVE_FROM_GROUPS_LINK]]';
		$replace[] = $url. 'remove_from_groups.php?t='. $removeFromGroups;
		
		$search[] = '[[DELETE_ACCOUNT_LINK]]';
		$replace[] = $url. 'delete_account.php?t='. $deleteAccountHash;
		
		return str_ireplace($search, $replace, $body);
	}

	public static function eventSmartGroupDeleted($smartGroupId) {
		return true;			
	}

	public static function deleteNewsletter($id) {
		if (zenario_newsletter::checkIfNewsletterIsADraft($id)) {
			deleteRow(ZENARIO_NEWSLETTER_PREFIX . 'newsletters', $id);
			deleteRow(ZENARIO_NEWSLETTER_PREFIX . 'newsletter_sent_newsletter_link', array('newsletter_id' => $id));
			deleteRow(ZENARIO_NEWSLETTER_PREFIX . 'newsletter_smart_group_link', array('newsletter_id' => $id));
			
			$key = array('foreign_key_to' => 'newsletter', 'foreign_key_id' => $id);
			deleteRow('inline_images', $key);
		}
	}

	public static function deleteNewsletterTemplate($id) {
		deleteRow(ZENARIO_NEWSLETTER_PREFIX. 'newsletter_templates', $id);
		
		$key = array('foreign_key_to' => 'newsletter_template', 'foreign_key_id' => $id);
		deleteRow('inline_images', $key);
	}

}
