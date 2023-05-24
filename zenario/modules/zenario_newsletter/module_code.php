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

class zenario_newsletter extends ze\moduleBaseClass {


	public static function getTrackerURL() {
		return 'http://'. ze\link::primaryDomain(). SUBDIRECTORY. ze::moduleDir('zenario_newsletter', 'tracker/');
	}
	

	protected static function newsletterRecipients($id, $mode) {
		if (!ze\row::exists(ZENARIO_NEWSLETTER_PREFIX. 'newsletters', $id) || 
			!ze\row::exists(ZENARIO_NEWSLETTER_PREFIX. 'newsletter_smart_group_link', ['newsletter_id' => $id]) ) {
			return false;
		} 
		
		$parts = [];
		foreach ( ze\row::getValues(ZENARIO_NEWSLETTER_PREFIX. 'newsletter_smart_group_link', 'smart_group_id', ['newsletter_id' => $id]) as $smartGroupId )  {
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

					$result = ze\sql::select($sql);
					$row = ze\sql::fetchRow($result);
					return $row[0];
					break;
				case 'populate': 
					$sql = "
						INSERT IGNORE INTO ". DB_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletter_user_link
							(newsletter_id, user_id, tracker_hash, remove_hash, delete_account_hash, `email`) "
						. implode(" UNION ", $parts);
					ze\sql::update($sql, false, false);
					return ze\sql::affectedRows();
				case 'get_sql':
					return [
								'table_join' =>  "(" . implode(" UNION ", $parts) . ") r ON r.id = u.id",
								'where_statement' => " r.id IS NOT NULL "
								];
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
						u.email";
				break;
			
			case 'get_sql':
				$sql = "SELECT u.id";
				break;
			default:
				return false;
		}
		
		$sql .= "
			FROM ". DB_PREFIX. "users AS u
			LEFT JOIN ". DB_PREFIX. "users_custom_data AS ucd
			   ON ucd.user_id = u.id";
		
		$whereStatement = "
			WHERE TRUE " . ze\row::whereCol('users', 'u', 'email', '!=', "");
		
		if (!ze\smartGroup::sql($whereStatement, $sql, $smartGroupId)) {
			return false;
		}
		
		foreach (ze\row::getAssocs(
			ZENARIO_NEWSLETTER_PREFIX. 'newsletter_sent_newsletter_link',
			'sent_newsletter_id',
			['newsletter_id' => $id]
		) as $sentNewsletterId) {
			$sql .= "
				LEFT JOIN ". DB_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletter_user_link AS nul_". (int) $sentNewsletterId. "
				   ON nul_". (int) $sentNewsletterId. ".newsletter_id = ". (int) $sentNewsletterId. "
				  AND nul_". (int) $sentNewsletterId. ".user_id = u.id";
		
			$whereStatement .= "
				AND nul_". (int) $sentNewsletterId. ".user_id IS NULL";
		}
		
		//Exclude users who have opted out of newsletters
		$optOutFlag = false;
		if (($optOutFlagId = ze::setting('zenario_newsletter__all_newsletters_opt_out'))
		 && ($optOutFlag = ze\dataset::fieldDetails($optOutFlagId))) {
			$sql .= "
				LEFT JOIN ". DB_PREFIX. "users_custom_data AS noo
				   ON noo.user_id = u.id
				  AND noo.`". ze\escape::sql($optOutFlag['db_column']). "` = 1";
			$whereStatement .= "
				AND noo.user_id IS NULL";
		}
		
		//Exclude users who have not given consent to receive newsletters
		$consentFlag = false;
		if (ze::setting('zenario_newsletter__newsletter_consent_policy') == 'consent_required'
		 && ($consentFlagId = ze::setting('zenario_newsletter__newsletter_consent_flag'))
		 && ($consentFlagId != 'no_consent_required')
		 && ($consentFlag = ze\dataset::fieldDetails($consentFlagId))) {
		 	
		 	if ($consentFlag['is_system_field']) {
				$whereStatement .= "
					AND u.`". ze\escape::sql($consentFlag['db_column']). "` = 1";
		 	} else {
				$whereStatement .= "
					AND ucd.`" . ze\escape::sql($consentFlag['db_column']) . "` = 1";
		 	}
		}

		$sql .= "
			". $whereStatement;
		
		return $sql;
	}
	


	
	//Load the details of a/for a Newsletter or Newsletter Design
	//Or alternately create a new Newsletter/Newsletter Design using another as a template
	//Or if there are no details to load, attempt to set the fields to some default values
	public static function details($id) {
		$sql = "
			SELECT 
				newsletter_name,
				subject,
				status,
				email_address_from,
				email_name_from,
				head,
				body,
				unsubscribe_text,
				delete_account_text
			FROM ". DB_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletters
			WHERE id = ". (int) $id;
		$result = ze\sql::select($sql);
		return ze\sql::fetchAssoc($result);
	}
	
	//Old non-static version of the above function
	public function loadDetails($id) {
		return self::details($id);
	}
	
	
	public static function checkIfNewsletterIsADraft($id) {
		return ze\row::get(ZENARIO_NEWSLETTER_PREFIX. 'newsletters', 'status', $id) == '_DRAFT';
	}
	
	function checkIfNewsletterIsInProgress($id) {
		return ze\row::get(ZENARIO_NEWSLETTER_PREFIX. 'newsletters', 'status', $id) == '_IN_PROGRESS';
	}
	
	
	
	

	//Send a test email
	function testSendNewsletter($head, $body, $adminDetails, $email, $subject, $emailAddresFrom, $emailNameFrom, $newsletterId) {
		
		$newsletterArray = ["id" => $newsletterId,"body" => $body];
		$body = zenario_newsletter::createTrackerHyperlinks($newsletterArray);
		//Check if there is a User set up with this email address
		if (!$user = self::getUserDetails(['email' => $email])) {
			//Send the test email with the current Admin's details, rather than a User.
			$user['first_name'] = $adminDetails['admin_first_name'];
			$user['last_name'] = $adminDetails['admin_last_name'];
			$user['admin_account'] = true;
			
			//The admins table doesn't have a title field.
			$user['title'] = ze\admin::phrase('(Title)');
		}
		
		$newsletterURL = zenario_newsletter::getTrackerURL();
		$newsletterSubject = zenario_newsletter::applyNewsletterMergeFields($subject, $user, $newsletterURL);
		$newsletterBody = zenario_newsletter::applyNewsletterMergeFields($body, $user, $newsletterURL);
		
		$newsletterSubject .= ' | TEST SEND';
		self::putHeadOnBody($head, $newsletterBody);

		//Attempt to send the email
		$emailOverriddenBy = false;
		return ze\server::sendEmail(
			$newsletterSubject,
			$newsletterBody,
			$email,
			$emailOverriddenBy,
			$user['first_name']. ' '. $user['last_name'],
			$emailAddresFrom, $emailNameFrom
		);
	}
	
	//Attempt to get details of a user from their email address
	protected static function getUserDetails($key) {
		if ($row = ze\row::get('users', ['id', 'salutation', 'first_name', 'last_name', 'email'], $key)) {
			$row['title'] = $row['salutation'];
			return $row;
		}
	}
	
	
	//Code to send every newsletter that needs sending
	//Ideally should be run as a job
	public static function jobSendNewsletters($serverTime) {
		$action = false;
		
		$sql = "
			SELECT id, newsletter_name
			FROM ". DB_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletters
			WHERE status = '_IN_PROGRESS' AND (scheduled_send_datetime IS NULL OR scheduled_send_datetime <= STR_TO_DATE('". ze\escape::sql($serverTime). "', '%Y-%m-%d %H:%i:%s'))";
		$result = ze\sql::select($sql);
		
		while ($newsletter = ze\sql::fetchAssoc($result)) {
			zenario_newsletter::sendNewsletter($newsletter['id']);
			echo ze\admin::phrase('Newsletter "[[newsletter_name]]" was sent.', $newsletter), "\n";
			$action = true;
		}
		
		return $action;
	}
	
	// Send the newsletter to all admins
	protected static function sendNewsletterToAdmins($id, $mode = 'myself') {
		
		if ($mode == 'none') {
			return false;
		}
		
		// Get admins
		$admins = [];
		$sql = '
			SELECT
				id,
				"" AS title,
				"" AS salutation,
				first_name,
				last_name,
				email,
				1 AS admin_account
			FROM '.DB_PREFIX.'admins
			WHERE status = \'active\'';
		
		if ($mode == 'myself') {
			$sql .= 'AND id = '.(int)ze\admin::id();
		}
		
		$result = ze\sql::select($sql);
		while ($row = ze\sql::fetchAssoc($result)) {
			$admins[] = $row;
		}
		// Get newsletter
		$newsletter = self::getNewsletter($id);
		
		// Apply merge fields and send emails
		foreach ($admins as $id => $admin) {
			
			$newsletterSubject = zenario_newsletter::applyNewsletterMergeFields($newsletter['subject'], $admin, $newsletter['url']);
			$newsletterBody = zenario_newsletter::applyNewsletterMergeFields($newsletter['body'], $admin, $newsletter['url']);
			
			self::putHeadOnBody($newsletter['head'], $newsletterBody);
			$newsletterSubject .= ' - ADMIN COPY';
			
			$emailOverriddenBy = false;
			ze\server::sendEmail(
				$newsletterSubject,
				$newsletterBody,
				$admin['email'],
				$emailOverriddenBy,
				$admin['first_name']. ' '. $admin['last_name'],
				$newsletter['email_address_from'],
				$newsletter['email_name_from'],
				[],
				[],
				'bulk');
		}
	}
	
	public static function getNewsletter($id) {
		$sql = "
			SELECT 
				id,
				subject,
				email_address_from,
				email_name_from,
				url,
				head,
				body,
				unsubscribe_text,
				delete_account_text
			FROM "
				. DB_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletters
			WHERE 
				id = ". (int) $id;
		$newsletter = ze\sql::fetchAssoc($sql);
		
		return $newsletter;
	}
	
	//If this newsletter has HTML in the <head>, we'll need to send the email as a full webpage
	public static function putHeadOnBody(&$head, &$body) {
		
		if ($head && trim($head)) {
			$body =
'<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
'. $head. '
</head>
<body>
'. $body. '
</body>
</html>';
		}
	}
	
	//Code to send a newsletter
	//Ideally should be run as a job
	public static function sendNewsletter($id) {
		$newsletter = self::getNewsletter($id);
		
		//Add unsubscribe/delete account links at the bottom if enabled
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
				. DB_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletter_user_link
			WHERE 
					email_sent = 0
			  AND 	newsletter_id = ". (int) $newsletter['id'];
		$result = ze\sql::select($sql);
		
		while ($hashes = ze\sql::fetchAssoc($result)) {
			$userNewsletter = $newsletter;
			$userNewsletter['body'] = zenario_newsletter::createTrackerHyperlinks($userNewsletter, $hashes['user_id'], $hashes);
			//send newsletter email 
			
			zenario_newsletter::sendNewsletterToUser($userNewsletter, $hashes['user_id'], $hashes);
		}
		
		ze\row::update(ZENARIO_NEWSLETTER_PREFIX. 'newsletters', ['status' => '_ARCHIVED'], $id);
		ze\row::update('inline_images', ['archived' => 1], ['foreign_key_to' => 'newsletter', 'foreign_key_id' => $id]);
	}
	
	
	protected static function sendNewsletterToUser(&$newsletter, $userId, $hashes) {
		
		$user = self::getUserDetails($userId);

		$sql = "
			UPDATE ". DB_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletter_user_link SET
				email_sent = 1,
				time_sent = NOW()
			WHERE newsletter_id = ". (int) $newsletter['id']. "
			  AND email_sent = 0
			  AND user_id = ". (int) $userId;
		
		ze\sql::update($sql, false, false);
		$affected_rows = ze\sql::affectedRows();
		
		if (!ze\sql::affectedRows()) {
			return;
		}
		
		$newsletterSubject = zenario_newsletter::applyNewsletterMergeFields($newsletter['subject'], $user, $newsletter['url'], $hashes['remove_hash'], $hashes['delete_account_hash']);
		$newsletterBody = zenario_newsletter::applyNewsletterMergeFields($newsletter['body'], $user, $newsletter['url'], $hashes['remove_hash'], $hashes['delete_account_hash']);
		
		if (ze::setting('zenario_newsletter__enable_opened_emails')) {
			$newsletterBody .= ' <img alt="&nbsp;" height="1" width="1" src="'. htmlspecialchars($newsletter['url']). 'tracker.php?t='. htmlspecialchars($hashes['tracker_hash']). '"/>';
		}
		self::putHeadOnBody($newsletter['head'], $newsletterBody);
		
		$emailOverriddenBy = false;
		
		
		if (ze\server::sendEmail(
			$newsletterSubject,
			$newsletterBody,
			$user['email'],
			$emailOverriddenBy,
			$user['first_name']. ' '. $user['last_name'],
			$newsletter['email_address_from'],
			$newsletter['email_name_from'],
			[],
			[],
			'bulk'
		)) {
			
			$sql = "
				UPDATE ". DB_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletter_user_link SET
					email_sent = 2,
					email_overridden_by = '". ze\escape::sql($emailOverriddenBy). "'
				WHERE newsletter_id = ". (int) $newsletter['id']. "
				  AND email_sent = 1
				  AND user_id = ". (int) $user['id'];
			
			ze\sql::update($sql);
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
						$href = htmlspecialchars_decode($str2);
						if($href == '#') {
							$href = "not-found";
						}
						if($href == '[[REMOVE_FROM_GROUPS_LINK]]' || $href =='[[DELETE_ACCOUNT_LINK]]') {
							$isDeleteOrSubLink = true;
						}
															
						if(!preg_match('@\w+:\/\/@', $href) && strpos($href, 'mailto:') === false) {
							$href = 'http://'. ze\link::primaryDomain() . SUBDIRECTORY . $href; //if hyper does not contain :// treat hyperlink as content item alis
						}
						if(!$isDeleteOrSubLink) {
							preg_match('@>(.+)<@', $str, $linktextArray);//Finds link text
							$hyperlinkId = ze\row::set(ZENARIO_NEWSLETTER_PREFIX. "newsletters_hyperlinks", ["newsletter_id" => $newsletter["id"], "link_ordinal"=> $linkCount, "hyperlink"=>$href, "link_text" => $linktextArray[1], "clickthrough_count" => 0, "last_clicked_date" => NULL], ["newsletter_id" => $newsletter["id"], "link_ordinal"=> $linkCount]); // create hyperlink record
							$sql = "UPDATE " . DB_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletters_hyperlinks SET
									hyperlink_hash = SHA('" . $hyperlinkId . "_" . $newsletter['id'] . "')
									WHERE id = " . $hyperlinkId;
							ze\sql::update($sql);
							$hyperlinkHash = ze\row::get(ZENARIO_NEWSLETTER_PREFIX. "newsletters_hyperlinks", "hyperlink_hash", ['id' => $hyperlinkId]); //get hyper_hash code 
							if ($userId && $hashes) {
								$trackerHyperlink = zenario_newsletter::getTrackerURL() . 'link_tracker.php?' . 't=' . $hashes['tracker_hash'] . '&' . 'nlink=' . $hyperlinkHash;
								$outputHTML .= 'href="'. htmlspecialchars($trackerHyperlink) . '"';
								$linkCount = $linkCount + 1;
							} else {
								$trackerHyperlink = zenario_newsletter::getTrackerURL() . 'link_tracker.php?' . 'nlink=' . $hyperlinkHash;
								$outputHTML .= 'href="'. htmlspecialchars($trackerHyperlink) . '"';
								$linkCount = $linkCount + 1;
							}
						} else {
							$outputHTML .= 'href="'. $str2. '"';
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
		$search = [];
		$replace = [];
		if (isset($user['admin_account'])) {
			if(isset($user['salutation'])){
				$search[] = '[[SALUTATION]]';
				$replace[] = htmlspecialchars($user['salutation']);
			}
			
			if(isset($user['first_name'])){
				$search[] = '[[FIRST_NAME]]';
				$replace[] = htmlspecialchars($user['first_name']);
			}
			if(isset($user['last_name'])){
				$search[] = '[[LAST_NAME]]';
				$replace[] = htmlspecialchars($user['last_name']);
			}
			
		} else {
			$userDetails = ze\user::details($user['id']);
			if (is_array($userDetails)) {
				foreach($userDetails as $dbColumn => $value) {
					$search[] = '[['.$dbColumn.']]';
					$replace[] = htmlspecialchars($value ?: '');
				}
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
			
			ze\module::sendSignal('eventNewsletterDeleted', [$id]);
			
			ze\row::delete(ZENARIO_NEWSLETTER_PREFIX . 'newsletters', $id);
			ze\row::delete(ZENARIO_NEWSLETTER_PREFIX . 'newsletter_sent_newsletter_link', ['newsletter_id' => $id]);
			ze\row::delete(ZENARIO_NEWSLETTER_PREFIX . 'newsletter_smart_group_link', ['newsletter_id' => $id]);
			ze\row::delete('inline_images', ['foreign_key_to' => 'newsletter', 'foreign_key_id' => $id]);
		}
	}

	public static function deleteNewsletterTemplate($id) {
		ze\row::delete(ZENARIO_NEWSLETTER_PREFIX. 'newsletter_templates', $id);
		
		$key = ['foreign_key_to' => 'newsletter_template', 'foreign_key_id' => $id];
		ze\row::delete('inline_images', $key);
	}
	

}
