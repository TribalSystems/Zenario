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


require 'basicheader.inc.php';

//Load site settings
ze\db::loadSiteConfig();

if (!ze::setting('sitemap_enabled')) {
	header('HTTP/1.0 403 Forbidden');
	exit;
}

$sql = "
	SELECT c.id, c.type, c.alias, v.published_datetime
	FROM ". DB_NAME_PREFIX. "content_items AS c
	INNER JOIN ". DB_NAME_PREFIX. "translation_chains AS tc
	   ON c.equiv_id = tc.equiv_id
	  AND c.type = tc.type
	INNER JOIN ". DB_NAME_PREFIX. "content_item_versions AS v
	   ON c.id = v.id
	  AND c.type = v.type
	  AND c.visitor_version = v.version
	LEFT JOIN ". DB_NAME_PREFIX. "special_pages AS sp
	   ON c.equiv_id = sp.equiv_id
	  AND c.type = sp.content_type
	  AND sp.page_type IN ('zenario_not_found', 'zenario_no_access')
	WHERE c.status IN ('published_with_draft','published')
	  AND v.in_sitemap = 1
	  AND tc.privacy = 'public'
	  AND sp.equiv_id IS NULL
	ORDER BY c.tag_id";

$result = ze\sql::select($sql);




header('Content-Type: text/xml; charset=UTF-8');

$xml = new XMLWriter();
$xml->openURI('php://output');
$xml->startDocument('1.0', 'UTF-8');
	$xml->setIndent(4);
	$xml->startElement('urlset');
		$xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

		while ($item = ze\sql::fetchAssoc($result)) {
			$xml->startElement('url');
			$xml->writeElement('lastmod', substr($item['published_datetime'], 0, 10));
			$xml->writeElement('loc', ze\link::toItem($item['id'], $item['type'], true, '', $item['alias'], false, true));
			$xml->endElement();
		}
	
//send signal
$returnedPages = ze\module::sendSignal('pagesToAddToSitemap', []);

//write xml
foreach($returnedPages as $pages) {
	foreach ($pages as $page) {
		$xml->startElement('url');
		$xml->writeElement('lastmod', substr($page['published_datetime'], 0, 10));
		$xml->writeElement('loc', ze\link::toItem($page['id'], $page['type'], true, $page['requests'], $page['alias'], false, true));
		$xml->endElement();
	}
}
	$xml->endElement();
$xml->endDocument();
$xml->flush();