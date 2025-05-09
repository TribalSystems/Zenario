<?php
/*
 * Copyright (c) 2025, Tribal Limited
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


class zenario_feed_reader extends ze\moduleBaseClass {

	protected $xmlParser;
	protected $content;
	protected $newsDates;
	protected $newsTitles;
	protected $itemCount;
	protected $inChannel = false;
	protected $insideItem = false;
	protected $tag = '';
	protected $title = '';
	protected $description = '';
	protected $date = '';
	protected $link = '';
	protected $attributes = '';
	protected $linkTarget = '';
	protected $feedTitle = '';
	protected $field = '';
	protected $pattern = '';
	
	public function init() {
		$this->allowCaching(
			$atAll = true, $ifUserLoggedIn = true, $ifGetOrPostVarIsSet = true, $ifSessionVarOrCookieIsSet = true);
		$this->clearCacheBy(
			$clearByContent = false, $clearByMenu = false, $clearByFile = false, $clearByModuleData = false);
		
		if (!ze::get('getFeedContent')) {
			$this->callScript('zenario_feed_reader', 'getFeedContent', $this->containerId, $this->pluginAJAXLink('getFeedContent=1'));
		}
		return true;
	}
	
	public function showSlot() {
		//Do nothing: the plugin is initially supposed to load blank in order not to slow down
		//the page load. The content is loaded later via AJAX request. 
	}
	
	public function handlePluginAJAX() {
		if (isset($_GET['getFeedContent'])) {
			$feedSource = $this->setting('feed_source');
			$this->itemCount = $this->setting('number_feeds_to_show');
			
			$items = $this->getRssFeed();
			
			switch ($this->setting('title')) {
				case 'use_custom_title':
					$title = $this->setting('feed_title');
					break;
				case 'use_feed_title':
					$title = $this->feedTitle;
					break;
				default: 
					$title = '';
					break;
			}
			
			$pageMergeFields = [
				'Title' => $title,
				'Source' => '<p>Feed source: <a href="' . $feedSource . '">' . $feedSource . '</a></p>',
				'Content_Section' => true,
				'Feed_Reader' => true
			];
			
			$dateFormat = '';
	
			if ($this->setting('show_date_time') == "dont_show") {
				$pageMergeFields['Date_Section'] = false; 
			} else {
				$pageMergeFields['Date_Section'] = true; 
				if ($this->setting('date_format') == '_SHORT') {
					$dateFormat = ze::setting('vis_date_format_short');
				} elseif ($this->setting('date_format') == '_LONG') {
					$dateFormat = ze::setting('vis_date_format_long');
				} elseif ($this->setting('date_format') == '_MEDIUM') {
					$dateFormat = ze::setting('vis_date_format_med');
				}
			}
			
			$pageMergeFields['Feeds'] = [];
			$itemCount = 0;
			foreach ($items as $feedContent) {
				if (++$itemCount > $this->itemCount) {
					break;
				}
				if ($this->setting('rss_date_format') == 'backslashed_american') {
					$dateFeedContent = $feedContent['date'];
				} elseif ($this->setting('rss_date_format') == 'backslashed_european') {
					$dateFeedContent = explode('/', $feedContent['date']);
					if (count($dateFeedContent) == 3) {
						$dateFeedContent=$dateFeedContent[1] . '/' . $dateFeedContent[0] . '/' . $dateFeedContent[2] ;
					} else {
						$dateFeedContent=$feedContent['date'];
					}
				} elseif ($this->setting('rss_date_format') == 'autodetect') {
					$dateFeedContent = $feedContent['date'];
				} else {
					$dateFeedContent = $feedContent['date'];
				}
	
				if ($this->setting('show_date_time') == "date_only") {
					$feedContent['date'] = ze\date::format( date( 'Y-m-d', strtotime($dateFeedContent)), $dateFormat);
				} elseif ($this->setting('show_date_time') == "date_and_time") {
					$feedContent['date'] = ze\date::formatDateTime(date('Y-m-d H:i:s', strtotime($dateFeedContent)), $dateFormat);
				}
				
				if (!$this->setting( 'size' ) > 0) {
					$feedContent['description'] = '';
				} else {
					$feedContent['description'] = $this->truncateNicely(trim(strip_tags(strtr($feedContent['description'], ["\n" => '<br> ', "\r\n" => '<br> ']))), $this->setting('size'));
				}
				$feedMergeFields = [ 
					'Feed_Title' => $feedContent['title'], 
					'Feed_Description' => $feedContent['description'], 
					'Date' => $feedContent['date'] 
				];
				$pageMergeFields['Feeds'][] = $feedMergeFields;
			}
			
			$pageMergeFields['title_tags'] = $this->setting('title_tags');
			$pageMergeFields['feed_title_tags'] = $this->setting('feed_title_tags');
			
			$this->twigFramework($pageMergeFields);
		}
	}
	
	public function __construct() {
		$this->xmlParser = xml_parser_create();
		xml_parser_set_option($this->xmlParser, XML_OPTION_CASE_FOLDING, 1);
		xml_parser_set_option($this->xmlParser, XML_OPTION_SKIP_WHITE, 1);
		xml_set_object($this->xmlParser, $this);
		xml_set_element_handler($this->xmlParser, 'startElementHandler', 'endElementHandler');
		xml_set_character_data_handler($this->xmlParser, 'characterDataHandler');
	}
	
	function startElementHandler($xmlParser, $tagName, $attributes) {
		if ($this->insideItem)  {
			$this->tag = $tagName;
			$this->attributes = $attributes;
		} elseif ('ITEM' == $tagName || 'ENTRY' == $tagName)  {
			$this->insideItem = true;
			$this->inChannel = false;
		} elseif ($this->inChannel) {
			$this->tag = $tagName;
			$this->attributes = $attributes;
		} elseif ('CHANNEL' == $tagName) {
			$this->inChannel = true;
		}
	}

	function endElementHandler($xmlParser, $tagName) {
		if ($this->insideItem && ('ITEM' == $tagName || 'ENTRY' == $tagName)) {
			
			$this->field = $this->setting('regexp_field');
			$this->pattern = '/' . $this->setting('regexp') . '/';
			
			if (( $this->field == 'title' && !preg_match($this->pattern, $this->title)) 
				|| ($this->field == 'description' && !preg_match($this->pattern, $this->description))
				|| ($this->field == 'date' && !preg_match($this->pattern, $this->date))
				|| ($this->field == 'title_or_description' && !preg_match($this->pattern, $this->title . $this->description)) 
			) {
				//$this->itemCount++;
			} else {
				$this->link = trim($this->link);
				if (empty($this->link)) {	
					array_push( $this->content, [ 
						'title' => htmlspecialchars(trim($this->title)),
						'description' => (trim($this->description)),
						'date' => trim($this->date)]); 
				} else {
					if ($this->setting('target') == 'new_window') {
						$this->linkTarget = ' target="_blank"';
					}
					
					array_push($this->content,
						[
							'title' => '<a href="' . trim($this->link) . '"' . $this->linkTarget . '>'. htmlspecialchars(trim($this->title)) . '</a>',
							'description' => (trim($this->description)),
							'date' => trim($this->date)
						]
					); 
				}

				if ($this->setting('rss_date_format') == 'backslashed_american') {
					$dateFeedContent = trim($this->date);
				} elseif ($this->setting('rss_date_format') == 'backslashed_european') {
					$dateFeedContent = explode('/', trim($this->date));
					if (count($dateFeedContent) == 3) {
						$dateFeedContent = $dateFeedContent[1] . '/' . $dateFeedContent[0] . '/' . $dateFeedContent[2] ;
					} else {
						$dateFeedContent = $dateFeedContent[0];
					}
				} elseif ($this->setting('rss_date_format') == 'autodetect') {
					$dateFeedContent = trim($this->date);
				} else {
					$dateFeedContent = trim($this->date);
				}
				$this->newsDates[] = strtotime($dateFeedContent);
				$this->newsTitles[] = $this->title;
			}

			$this->title = '';
			$this->description = '';
			$this->link = '';
			$this->date = '';
			$this->attributes = [];
			$this->insideItem = false;
		}
	}

	function characterDataHandler($xmlParser, $data) {
		if ($this->insideItem) {
			switch ($this->tag) {
				case 'TITLE':
					$this->title .= $data;
					break;
				case 'DESCRIPTION':
				case 'SUMMARY':
				case 'CONTENT':
					$this->description .= $data;
					break;
				case 'ADMIN_MESSAGE':
					if (ze\priv::check()) {
						$this->description .= ' ' . $data;
					}
					break;
				case 'LINK':
					if (array_key_exists('HREF', $this->attributes)) {
						$this->link .= $this->attributes['HREF'];
					}
					$this->link .= $data;
					break;
				case 'PUBDATE':
				case 'UPDATED':
					$this->date .= $data;
					break;
			}
		} elseif ($this->inChannel) {
			switch ($this->tag) {
				case 'TITLE':
					$this->feedTitle .= $data;
					break;
			}
		}
	}
	
	protected function getLiveFeed() {
		$feedSource = $this->setting('feed_source');
		$feed = '';
		
		//Set the timeout for getting the feed
		$context = stream_context_create(
			[
				'http' => [
					'timeout' => 3	//Timeout in seconds
				]
			]
		);

		if ($feedSource) {
			//Attempt to use the cached value first.
			//If the source is invalid, or offline, check if the reader is still within the tolerance period.
			$timestampNow = strtotime(ze\date::now());
			$useByTimestamp = $lastTolerableTimestamp = 0;
			
			$sql = "
				SELECT store, last_updated, IFNULL(use_by_time, '') AS use_by_time, IFNULL(last_tolerable_time, '') AS last_tolerable_time
				FROM ". DB_PREFIX. "plugin_instance_store
				WHERE method_name = '". ze\escape::sql('getLiveFeed'). "'
				  AND request = '". ze\escape::sql($feedSource). "'
				  AND instance_id = ". (int) $this->instanceId;
			$result = ze\sql::select($sql);
			
			$cachedFeed = ze\sql::fetchAssoc($result);
			
			//First, check if the feed was cached.
			if (!empty($cachedFeed) && is_array($cachedFeed)) {
				$dateTime = ze\date::new($cachedFeed['use_by_time']);
				$useByTimestamp = $dateTime->getTimestamp();
				
				$dateTime = ze\date::new($cachedFeed['last_tolerable_time']);
				$lastTolerableTimestamp = $dateTime->getTimestamp();
				
				//If there is a cached feed, and it has not expired, use that.
				if ($useByTimestamp >= $timestampNow) {
					//If the reader is still within the tolerance period of the feed going offline,
					//just return the last cached value.
					$feed = $cachedFeed['store'];
				}
			}
			
			if (!$feed) {
				//If the cached feed is past its use by date, or there was no cached feed,
				//try to get the live data.
				$result = ze\curl::fetch($feedSource);
				
				if ($result) {
					$feed = file_get_contents($feedSource, 0, $context);
					
					if ($feed) {
						$sdateTimeObj = ze\date::new(ze\date::now());
						$dateTimeNow = $sdateTimeObj->format("Y-m-d H:i:s");
						
						if (($keepContentCachedFor = $this->setting('cache'))) {
							$sdateTimeObj->modify('+' . (int) $keepContentCachedFor . " minutes");
							$useByTime = $sdateTimeObj->format("Y-m-d H:i:s");
						}
						
						if (($feedGoingOfflineTolerance = $this->setting('feed_going_offline_tolerance'))) {
							$sdateTimeObj->modify('+' . (int) $feedGoingOfflineTolerance . " minutes");
							$lastTolerableTime = $sdateTimeObj->format("Y-m-d H:i:s");
						}
						
						$sql = "
							REPLACE INTO ". DB_PREFIX. "plugin_instance_store SET
								store = '". \ze\escape::sql($feed). "',
								last_updated = '" . ze\escape::sql($dateTimeNow) . "',
								use_by_time = '" . ze\escape::sql($useByTime) . "',
								last_tolerable_time = '" . ze\escape::sql($lastTolerableTime) . "',
								is_cache = 1,
								method_name = '". ze\escape::sql('getLiveFeed'). "',
								request = '". ze\escape::sql($feedSource). "',
								instance_id = ". (int) $this->instanceId;
						ze\sql::update($sql);
					}
				}
			}
			
			if (!$feed) {
				//If there still is no feed to show, but there is a cached version
				//which is within the offline tolerance period, try displaying it.
				if (!empty($cachedFeed) && $lastTolerableTimestamp && ($lastTolerableTimestamp >= $timestampNow)) {
					$feed = $cachedFeed['store'];
				}
			}
		}


		return mb_convert_encoding($feed, "UTF-8");
	}

	protected function getRssFeed() {
		$this->content = [];
		$xml = $this->getLiveFeed();
		
		if (!$xml) {
			$feedSource = $this->setting('feed_source');
			$xml = '<?xml version="1.0" encoding="UTF-8"			<error>
				<item>
					<title>Feed read error</title>
					<link>' . htmlentities($feedSource) . '</link>
					<description>Error reading feed data</description>
					<admin_message>from ' . htmlentities($feedSource) . '.</admin_message>
					<updated>' . date('Y-m-d H:i:s') . '</updated>
				</item>
			</error>';
		}
		
		xml_parse($this->xmlParser, $xml);
		xml_parser_free($this->xmlParser);
		if (!is_array($this->newsDates) || count($this->newsDates) == 0) {
			return [];
		} elseif ($this->setting('news_order') == 'asc') {
			array_multisort($this->newsDates, SORT_ASC, $this->content);
		} elseif ($this->setting('news_order') == 'desc') {
			array_multisort($this->newsDates, SORT_DESC, $this->content);
		} elseif ($this->setting('news_order') == 'title_alpha') {
			array_multisort($this->newsTitles, SORT_ASC, $this->content);
		}
		
		return $this->content;
	}
	
	protected function truncateNicely($string, $max) {
		$string = trim( $string );
		if (strlen($string) > $max) {
			$breakpoint2 = strpos($string, ' ', $max); // find last ' '
			$breakpoint1 = strrpos(substr($string, 0, $breakpoint2), ' '); // find new last ' '
    		if (false ===  $breakpoint1) { 
				$string = ''; 
			} else {
				$string = substr($string, 0, $breakpoint1) . '...'; 
			}
		}
		return $string;
	}
}