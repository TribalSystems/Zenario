<?php
/*
 * Copyright (c) 2016, Tribal Limited
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

class zenario_recently_viewed extends module_base_class {
	function init() {
		$this->allowCaching(
			$atAll = false, $ifUserLoggedIn = false, $ifGetSet = true, $ifPostSet = true, $ifSessionSet = true, $ifCookieSet = true); 
		$this->clearCacheBy(
			$clearByContent = true, $clearByMenu = false, $clearByUser = false, $clearByFile = true, $clearByModuleData = true);
		
		if (!get("mode")) {
			if ($this->setting("action")=="record_and_display" || $this->setting("action")=="record_only") {
				$this->registerItemView();
				$this->checkNumberOfRecentItems();
	   		}
	   	}

		if (post('recently_viewed_pages_submit')) {
			$recentlyViewedItems = array();
		
			foreach ($_POST as $key => $value) {
				if (substr($key,0,5)=="item_") {
					$itemArray = explode("_",$value);
					$recentlyViewedItems[] = array("cID" => $itemArray[0],"cType" => $itemArray[1]);
				}
			}
		
			sendSignal('eventRecentlyViewedItemsSubmitted', array("items" => $recentlyViewedItems, 'slotName' => $this->slotName));
		}

    	if ($this->setting("action")=="record_and_display" || $this->setting("action")=="display_only") {
    		return true;
    	} else {
    		return false;
    	}
	}
	
    function showSlot() {
		$frameworkArray = array();

		if (get("mode")=="delete_recent_item") {
			$this->deleteRecentlyViewedItem(get("key"));
		} 
    
    	if ($this->setting("action")=="record_and_display" || $this->setting("action")=="display_only") {
			if ($recentlyViewItems = $this->getRecentlyViewedItems()) {
	
	
				foreach ($recentlyViewItems as $instanceKey => $instance) {
					if ($instanceKey==(int)$this->setting('pot')) {
						foreach ($instance as $cTypeKey => $cType) {
							$showContentType = false;
							
							if ($cTypeKey==$this->setting("contenttype") || $this->setting("contenttype")=="") {
								$showContentType = true;
							}
							
							if ($showContentType) {
								foreach ($cType as $categoryKey => $category) {
									$i = 0;
								
									$showCategoryType = false;
								
									if ($categoryKey==$this->setting("category") || !$this->setting("category")) {
										$showCategoryType = true;
									}
									
									if ($showCategoryType) {
										foreach ($category as $recentlyViewItem) {
											if (!$this->setting('exclude_special_pages') || !isSpecialPage($recentlyViewItem['cID'], $recentlyViewItem['cType'])) {
												$i++;
												
												if ($i<=$this->setting("number_to_record")) {
													$frameworkArray[] = array(
																				"Recently_Viewed_Item_Title" => getItemTitle($recentlyViewItem['cID'],$recentlyViewItem['cType']),
																				"Recently_Viewed_Item_Url" => $this->linkToItem($recentlyViewItem['cID'],$recentlyViewItem['cType']),
																				"Recently_Viewed_Item_Key" => $recentlyViewItem['cID'] . "_" . $recentlyViewItem['cType'],
																				"Recently_Viewed_Item_Href_And_Onclick" => $this->refreshPluginSlotAnchor("&mode=delete_recent_item&no_cache=1&key=" . $recentlyViewItem['cID'] . "_" . $recentlyViewItem['cType']),
																				"Ordinal" => $i
																		);
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}
    	
    	if (post('recently_viewed_pages_submit')) {
	  		$this->framework("Outer",array(),array("Items_Submitted" => true));
    	} elseif ($frameworkArray) {
				
			if ($this->setting('order')=='most_recent_first'){
				$frameworkArray=array_reverse($frameworkArray,true);
			} 
    		
    		if ($this->setting("action")=="record_and_display" || $this->setting("action")=="display_only") {
				$mergefields = array();
			
				$mergeFields['Open_Form']=$this->openForm();
				$mergeFields['Close_Form']=$this->closeForm();
	
				$this->framework("Outer",$mergeFields,array("Recently_Viewed_Items" => $frameworkArray,"Items_Not_Submitted" => true));
			}
	  	} else {
			if ($this->setting("action")=="record_and_display" || $this->setting("action")=="display_only") {
				$this->framework("Outer",array(),array("No_Items_Viewed" => true));
			}
	  	}
    }

	function checkNumberOfRecentItems () {
     	if (!isset($_SESSION['recently_viewed_items'])) {
    		$_SESSION['recently_viewed_items'] = array();
    	} else {
    		foreach ($_SESSION['recently_viewed_items'] as $instanceKey => &$instance) {
    			if ($instanceKey==(int)$this->setting('pot')) {
					foreach ($instance as &$cType) {
						foreach ($cType as &$category) {
							$removeItem = false;
							if ($contentCategories = getContentItemCategories($this->cID, $this->cType)) {
								foreach ($contentCategories as $contentCategory) {
									if ($contentCategory['id']==$this->setting("category")) {
										$removeItem = true;
									}
								}
							} elseif (!$this->setting("category")) {
								$removeItem = true;
							}
							
							if ($removeItem && (sizeof($category)>$this->setting("number_to_record"))) {
								array_shift($category);
							}
						}
					}
				}
    		}
    	}
	}
    
    function registerItemView () {
		$recordItem = true;
		
		if ($this->setting("excluded_content")) {
			$excludedContentItems = explode(",",$this->setting("excluded_content"));
			foreach ($excludedContentItems as $excludedContentItem) {
				$excludedContentItemArray = explode("_",$excludedContentItem);
			
				if ($this->cID==$excludedContentItemArray[1] && $this->cType==$excludedContentItemArray[0]) {
					$recordItem = false;
				}
			}
		}
		
		if ($this->setting('exclude_spacial_pages') && isSpecialPage($this->cID, $this->cType)) {
			$recordItem = false;
		}

    	if ($this->setting("action")=="record_and_display" || $this->setting("action")=="record_only") {
    		if ($this->setting("contenttype")=="" || $this->setting("contenttype")==$this->cType) {
    			if ($this->setting("category")) {
					if ($contentCategories = getContentItemCategories($this->cID, $this->cType)) {
						foreach ($contentCategories as $contentCategory) {
							if ($contentCategory['id']==$this->setting("category")) {
								if ($recordItem) {
			  						$_SESSION['recently_viewed_items'][(int)$this->setting('pot')][$this->cType][$contentCategory['id']][$this->cID . "_" . $this->cType] = array("cID" => $this->cID, "cType" => $this->cType);
			  						break;
			  					}
							}
						}
					}
		  		} else {
					if ($recordItem) {
		  				$_SESSION['recently_viewed_items'][(int)$this->setting('pot')][$this->cType][''][$this->cID . "_" . $this->cType] = array("cID" => $this->cID, "cType" => $this->cType);    
		  			}
    			}
	  		}
    	}
    }
    
    function getRecentlyViewedItems () {
    	if (session('recently_viewed_items') && is_array(session('recently_viewed_items'))) {
    		return session('recently_viewed_items');
    	} else {
    		return false;
    	}
    }
    
    function deleteRecentlyViewedItem ($keyIn) {
    	if (session('recently_viewed_items') && is_array(session('recently_viewed_items'))) {
    		foreach ($_SESSION['recently_viewed_items'] as $instanceKey => &$instance) {
    			if ($instanceKey==(int)$this->setting('pot')) {
					foreach ($instance as &$cType) {
						foreach ($cType as &$category) {
							foreach ($category as $key => $value) {
								if ($key==$keyIn) {
									unset($category[$key]);
								}
							}
						}
					}
				}
			}
    	}
    }
    
    
    public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path){
			case 'plugin_settings':
				$box['tabs']['first_tab']['fields']['number_to_record']['hidden'] = $values['first_tab/action']=='display_only';
				break;
		}
	}
    
    
}

?>