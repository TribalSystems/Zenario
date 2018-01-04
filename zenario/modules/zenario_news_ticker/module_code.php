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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');





//This Plugin is used to create an information ticker.

class zenario_news_ticker extends module_base_class {

	var $numberOfItems;
	var $result;
	var $mergeFields;
		
	function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = false, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true);
		$this->clearCacheBy(
			$clearByContent = true, $clearByMenu = false, $clearByUser = false, $clearByFile = false, $clearByModuleData = false);

		$sql = "
			SELECT 
				v.id,
				v.type,
				v.version,
				v.title,
				v.description,
				v.content_summary
			". sqlToSearchContentTable(true, false, $this->getCategoryFilterSQL());
		
		if ($this->setting('content_type')) {
			$sql .= "
			  AND v.type = '". sqlEscape($this->setting('content_type')). "'";
		}
		if ($this->setting('filter_by_release_date') && $this->setting('result_count')) {
			$sql .= '
				ORDER BY v.publication_date DESC
				LIMIT '. (int)$this->setting('result_count');
		}
		
		$i = 0;
		
		$rv = sqlQuery($sql);
		while ($row = sqlFetchAssoc($rv)){
			switch ($this->setting('data_field')){
				case 'title':
					$text = $row['title'] ?? false;
					break;				
				case 'description':
					$text = $row['description'] ?? false;
					break;				
				case 'content_summary':
					$text = $row['content_summary'] ?? false;
					break;
				default:
					break;
			}
			$text = $this->truncateNicely( trim ( html_entity_decode( strip_tags( strtr( $text, array( "\n" => '<br> ', "\r\n" =>'<br> ' ) ) ), ENT_COMPAT, 'UTF-8' ) ) , $this->setting( 'size' ) );
			
			$this->mergeFields = array();
			
			if ($this->setting('suppress_link_to_content_item')){
				$linkToContentItem = '';
			}else{
				$linkToContentItem = linkToItem($row['id'] ?? false,($row['type'] ?? false));
				$this->mergeFields['has_link_class'] = "has_link";
			}
			
			if ($text) {
				$this->callScript("zenario_news_ticker","add",$text,$linkToContentItem);
				$i++;
			}
		}
		if ($i){
			$this->callScript("zenario_news_ticker","start");
		}
		return true;

	}


	function getCategoryFilterSQL(){
		if ($this->setting('filter_by_category') && $this->setting('category') && checkRowExists('categories', array('id' => $this->setting('category')))) {
			return "
			INNER JOIN ". DB_NAME_PREFIX. "category_item_link AS cil
			   ON cil.equiv_id = c.id
			  AND cil.content_type = c.type
			  AND cil.category_id = ". (int) $this->setting('category');
		}
	}


	function showSlot() {
		$this->framework("Ticker_Form", $this->mergeFields);
	}
	
	private function truncateNicely( $string, $max ) {
		$string = trim( $string );
		if ( strlen( $string ) > $max ) {
			$breakpoint2 = strpos( $string, ' ', $max );
			$breakpoint1 = strrpos( substr( $string, 0, $breakpoint2  ), ' ' );
    		if ( false ===  $breakpoint1 ) { 
				$string = ''; 
			} else {
				if ( $breakpoint < strlen( $string ) - 1 ) { 
					$string = substr( $string, 0, $breakpoint1 ); 
				} else {
					$string = substr( $string, 0, $max ); 
				}
			}
		}
		return $string;
	}
	

	
	
	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch($path) {
			case 'plugin_settings':
				$fields['first_tab/category']['hidden'] = !$values['first_tab/filter_by_category'];
				$fields['first_tab/result_count']['hidden'] = !$values['first_tab/filter_by_release_date'];
				break;
		}
	}
	
}
?>