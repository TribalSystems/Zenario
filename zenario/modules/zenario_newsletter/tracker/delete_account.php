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

if (file_exists('../../../zenario/visitorheader.inc.php')) {
	require '../../../zenario/visitorheader.inc.php';
} elseif (file_exists('../../../visitorheader.inc.php')) {
	require '../../../visitorheader.inc.php';
} else {
	exit;
}

echo '
	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
		<title>', phrase('Delete Account', false, 'zenario_newsletter'), '</title>
	</head>
	<body style="text-align: center; margin-top: 20%;">';


if (!($newsletters = activateModule('zenario_newsletter'))) {
	phrase('Sorry, you cannot be automatically removed right now, as this site has disabled their newsletter system.', false, 'zenario_newsletter');
	exit;
} elseif (($_REQUEST['t'] ?? false) == 'XXXXXXXXXXXXXXX' || ($_REQUEST['t'] ?? false) == 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX') {
	echo phrase('Any user who clicks on this link in the actual Newsletter will have their account deleted.', false, 'zenario_newsletter');
	exit;
}

$sql = "
	SELECT user_id, newsletter_id
	FROM ". DB_NAME_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletter_user_link
	WHERE delete_account_hash = '". sqlEscape($_REQUEST['t'] ?? false). "'";

$result = sqlQuery($sql);
if ($row = sqlFetchAssoc($result)) {
	$sql = "
		SELECT 1
		FROM ". DB_NAME_PREFIX. "users
		WHERE id = ". (int) $row['user_id'];
	$result = sqlQuery($sql);
	
	if (sqlNumRows($result)) {
		if ($_POST['confirm'] ?? false) {
			deleteUser($row['user_id']);
			
			echo phrase('Your account has been deleted.', false, 'zenario_newsletter');
		
		} else {
			echo '
				<form method="post">
					', phrase('Please confirm that you wish to delete your account.', false, 'zenario_newsletter'), '
					<br/>
					<input type="hidden" name="t" value="', htmlspecialchars($_REQUEST['t']), '"/>
					<input type="submit" name="confirm" value="', phrase('Confirm', false, 'zenario_newsletter'), '"/>
				</form>';
		}
	} else {
		echo phrase('Your account has already been deleted.', false, 'zenario_newsletter');
	}
	
} else {
	echo phrase("Sorry, that's an invalid tracker id.", false, 'zenario_newsletter');
}




?>
</body>
</html>