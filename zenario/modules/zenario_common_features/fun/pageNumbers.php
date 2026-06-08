<?php
/*
 * Copyright (c) 2026, Tribal Limited
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



$html = '
	<div class="pag_pagination">';

//Find the total number of pages
$count = count($pages);

//Don't output anything if there is only one page!
if ($count > 1) {
	//Pages might not be numeric, so get something that is to work with
	$pagesPos = array_keys($pages);
	$currentPos = (int) array_search($currentPage, $pagesPos);
	
	//Work out which pages should be marked as "previous" and "next"
	$prevPage = false;
	$nextPage = false;
	if (($currentPos > 0) && (isset($pagesPos[$currentPos-1]))) {
		$prevPage = $pagesPos[$currentPos-1];
	}
	
	if (($currentPos < $count - 1) && (isset($pagesPos[$currentPos+1]))) {
		$nextPage = $pagesPos[$currentPos+1];
	}
	
	if ($prevPage !== false) {
		$html .= $this->drawPageLink($this->phrase('Prev'), $pages[$prevPage], $prevPage, $currentPage, $prevPage, $nextPage, 'pag_prev', $links, $extraAttributes);
	} else {
		$html .= $this->drawPageLink($this->phrase('Prev'), '', '', $currentPage, $prevPage, $nextPage, 'pag_prev', $links, $extraAttributes);
	}
	
	
	//Check if each is there, and include it if so
	$first = array_key_first($pagesPos);
	$last = array_key_last($pagesPos);
	
	$page = $pagesPos[$first];
	$html .= $this->drawPageLink($page, $pages[$page], $page, $currentPage, $prevPage, $nextPage, 'pag_page', $links, $extraAttributes);
	
	//Is is possible to display "..." to indicate there are pages that are not being displayed
	//before or after the current item and its nearest neighbours.
	//Example: page 5 is current
	//Prev 1 ... 3 4 [5] 6 7 ... 15 Next
	$dotsBeforeAdded = false;
	for ($pos = $currentPos - 2; $pos <= $currentPos + 2; ++$pos) {
		if (isset($pagesPos[$pos])) {
			if ($pos == $first || $pos == $last) {
				continue;
			}
			
			if (($pos > $currentPos - 3) && ($currentPos - 3 >= 1) && !$dotsBeforeAdded) {
				$html .= '<span>...</span>';
				$dotsBeforeAdded = true;
			}
			
			$page = $pagesPos[$pos];
			$html .= $this->drawPageLink($page, $pages[$page], $page, $currentPage, $prevPage, $nextPage, 'pag_page', $links, $extraAttributes);
		}
	}
	
	if (isset($pagesPos[$currentPos + 3]) && ($currentPos + 3 != $last)) {
		$html .= '<span>...</span>';
	}
	
	$page = $pagesPos[$last];
	$html .= $this->drawPageLink($page, $pages[$page], $page, $currentPage, $prevPage, $nextPage, 'pag_page', $links, $extraAttributes);
	
	
	if ($nextPage !== false) {
		$html .= $this->drawPageLink($this->phrase('Next'), $pages[$nextPage], $nextPage, $currentPage, $prevPage, $nextPage, 'pag_next', $links, $extraAttributes);
	} else {
		$html .= $this->drawPageLink($this->phrase('Next'), '', '', $currentPage, $prevPage, $nextPage, 'pag_next', $links, $extraAttributes);
	}
}

$html .= '
		</div>';