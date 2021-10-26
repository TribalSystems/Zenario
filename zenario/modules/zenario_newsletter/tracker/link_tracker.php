<?php
/*
 * Copyright (c) 2021, Tribal Limited
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

$urlT = $urlNLink = $hyperlinkDetails = null;
$urlT = $_GET['t'] ?? null;
$urlNLink = $_GET['nlink'] ?? null;

if ($urlNLink
 && $urlT
 && $urlT != 'XXXXXXXXXXXXXXX'
 && $urlT != 'XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX'
 && ze\module::inc('zenario_newsletter')) {
	$newsletterId = ze\row::get(ZENARIO_NEWSLETTER_PREFIX. "newsletter_user_link", "newsletter_id", ['tracker_hash' => $urlT]);
	
	if ($hyperlinkDetails = ze\row::get(ZENARIO_NEWSLETTER_PREFIX. "newsletters_hyperlinks", ["id", "hyperlink", "link_ordinal", "clickthrough_count"], ['hyperlink_hash' => $urlNLink])) {
		$hyperlinkDetails["clickthrough_count"] = $hyperlinkDetails["clickthrough_count"] + 1;
		ze\row::update(ZENARIO_NEWSLETTER_PREFIX. "newsletters_hyperlinks", ['clickthrough_count' => $hyperlinkDetails["clickthrough_count"], 'last_clicked_date' => ze\date::now()], ['id' => $hyperlinkDetails["id"]]);
		$sql = "
			UPDATE ". DB_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletter_user_link SET
				time_clicked_through = NOW(),
				clicked_hyperlink_id = ". (int) $hyperlinkDetails['id']. "
			WHERE tracker_hash = '". ze\escape::asciiInSQL($urlT). "'
			  AND time_clicked_through IS NULL";
		ze\sql::update($sql);
		
		$sql = "
			UPDATE ". DB_PREFIX. ZENARIO_NEWSLETTER_PREFIX. "newsletter_user_link SET
				time_received = NOW()
			WHERE tracker_hash = '". ze\escape::asciiInSQL($urlT). "'";
		ze\sql::update($sql);
		
	} else {
		
		echo ze\lang::phrase('Page not found - there my be a problem with your email link');
		exit;
		
	}
} elseif ($urlNLink && ze\module::inc('zenario_newsletter')) {

$hyperlinkDetails = ze\row::get(ZENARIO_NEWSLETTER_PREFIX. "newsletters_hyperlinks", ["id", "hyperlink", "link_ordinal", "clickthrough_count"], ['hyperlink_hash' => $urlNLink]);
	
	$hyperlinkDetails["clickthrough_count"] = $hyperlinkDetails["clickthrough_count"] + 1;
	
	ze\row::update(ZENARIO_NEWSLETTER_PREFIX. "newsletters_hyperlinks", ['clickthrough_count' => $hyperlinkDetails["clickthrough_count"], 'last_clicked_date' => ze\date::now()], ['id' => $hyperlinkDetails["id"]]);

}
if($hyperlinkDetails) {
	header('Location: '. $hyperlinkDetails["hyperlink"], true, 307);
} else {

	echo ze\lang::phrase('Page not found - there my be a problem with your email link'); 

}
exit;