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
		<link rel="stylesheet" type="text/css" media="screen" href="stylesheet.css"/>
		<title>', ze\lang::phrase('Unsubscribe'), '</title>
	</head>
	<body style="text-align: center; margin-top: 20%;">';


if (!ze\module::inc('zenario_newsletter')) {
	echo ze\lang::phrase('Sorry, you cannot be automatically unsubscribed right now, as this site has disabled their newsletter system.');
	exit;

} elseif (ze::request('t') == 'XXXXXXXXXXXXXXX' || ze::request('t') == 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX') {
	echo ze\lang::phrase('Any user who clicks on this link in the actual Newsletter will be automatically unsubscribed.');
	exit;
}


if ($link = ze\row::get(ZENARIO_NEWSLETTER_PREFIX. 'newsletter_user_link', ['user_id', 'newsletter_id'], ['remove_hash' => ze::request('t')])) {
	
	if (!ze\user::isInGroup(ze::setting('zenario_newsletter__all_newsletters_opt_out'), $link['user_id'])) {
	
		if(isset($_POST['cancel']) &&  $_POST['cancel']!=null){
		    echo '<h1>'.ze\lang::phrase('Unsubscribe Cancelled').'</h1>';
		    echo '<p>'.ze\lang::phrase('No action has been taken.').'</p>';
		}
		
		elseif (!ze::post('confirm') && !ze::post('cancel')) {
			
			echo '<h1>'.ze\lang::phrase('Unsubscribe').'</h1>';
			echo '
				<form method="post">
					<p>', ze\lang::phrase('Please confirm that you wish to unsubscribe.'), '</p>
					<div class="buttons_wrap">
						<input type="hidden" name="t" value="', htmlspecialchars($_REQUEST['t']), '"/>
						<input type="submit" class="cancel" name="cancel" value="', ze\lang::phrase('Cancel'), '"/>
						<input type="submit" name="confirm" value="', ze\lang::phrase('Confirm'), '"/>
					</div>
				</form>';
		
		} else {
			ze\user::addToGroup($link['user_id'], ze::setting('zenario_newsletter__all_newsletters_opt_out'));
			echo '<h1>'.ze\lang::phrase('Unsubscribed').'</h1>';
			echo '<p class="msg_sucess">'.ze\lang::phrase('You have been unsubscribed.').'</p>';
		}
	} else {
		echo ze\lang::phrase('You have already been unsubscribed.');
	}
	
} else {
	echo '<p class="msg_error">'.ze\lang::phrase("Sorry, that's an invalid tracker id.").'</p>';
}




?>
</body>
</html>