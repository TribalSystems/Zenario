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




class zenario_feed_reader extends module_base_class {

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
	
	function init() {
		
		if ( 'new_window' == $this->setting( 'target' ) ) {
			$this->linkTarget = ' target="_blank"';
		}
		
		$this->field = $this->setting( 'regexp_field' );
		$this->pattern = '/'. $this->setting( 'regexp' ) . '/';
		
		return true;
	}


	public function __construct() {
		$this->xmlParser = xml_parser_create();
		xml_parser_set_option( $this->xmlParser, XML_OPTION_CASE_FOLDING, 1 );
		xml_parser_set_option( $this->xmlParser, XML_OPTION_SKIP_WHITE, 1 );
		xml_set_object( $this->xmlParser, $this );
		xml_set_element_handler( $this->xmlParser, 'startElementHandler', 'endElementHandler' );
		xml_set_character_data_handler( $this->xmlParser, 'characterDataHandler' );
	}
	
	function startElementHandler( $xmlParser, $tagName, $attributes ) {
		if ( $this->insideItem )  {
			$this->tag = $tagName;
			$this->attributes = $attributes;
		} elseif  ( 'ITEM' == $tagName || 'ENTRY' == $tagName )  {
			$this->insideItem = true;
			$this->inChannel = false;
		} elseif ( $this->inChannel ) {
			$this->tag = $tagName;
			$this->attributes = $attributes;
		} elseif ( 'CHANNEL' == $tagName ) {
			$this->inChannel = true;
		}
	}

	function endElementHandler( $xmlParser, $tagName ) {
		if ( $this->insideItem && ( 'ITEM' == $tagName || 'ENTRY' == $tagName ) ) {
			if ( ( $this->field == 'title' && ! preg_match( $this->pattern, $this->title ) ) 
				|| ( $this->field == 'description' && ! preg_match( $this->pattern, $this->description ) )
				|| ( $this->field == 'date' && ! preg_match( $this->pattern, $this->date ) )
				|| ( $this->field == 'title_or_description' && ! preg_match( $this->pattern, $this->title . $this->description ) ) 
				) {
				//$this->itemCount++;
			} else {
				$this->link = trim( $this->link );
				if (  empty( $this->link ) ) {	
					array_push( $this->content, array( 
						'title' => htmlspecialchars( trim( $this->title ) ),
						'description' => ( trim( $this->description) ),
						'date' =>  trim( $this->date ) ) ); 
				} else {
					array_push( $this->content, array( 
						'title' => '<a href="' . trim( $this->link ) . '"' . $this->linkTarget . '>'. htmlspecialchars( trim( $this->title ) ) . '</a>',
						'description' => ( trim( $this->description) ),
						'date' =>  trim( $this->date ) ) ); 
				}

				if ($this->setting('rss_date_format')=='backslashed_american'){
					$dateFeedContent = trim($this->date);
				}elseif($this->setting('rss_date_format')=='backslashed_european'){
					$dateFeedContent = explode('/',trim($this->date));
					if (count($dateFeedContent)==3){
						$dateFeedContent=$dateFeedContent[1] . '/' . $dateFeedContent[0] . '/' . $dateFeedContent[2] ;
					} else{
						$dateFeedContent=$feedContent['date'];
					}
				}elseif($this->setting('rss_date_format')=='autodetect'){
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
			$this->attributes = array();
			$this->insideItem = false;
		}
	}



	function characterDataHandler( $xmlParser, $data ) {
		if ( $this->insideItem ) {
			switch ( $this->tag ) {
				case 'TITLE':
					$this->title .= $data;
				break;
				case 'DESCRIPTION':
				case 'SUMMARY':
				case 'CONTENT':
					$this->description .= $data;
				break;
				case 'ADMIN_MESSAGE':
					if ( checkPriv() ) {
						$this->description .= ' ' . $data;
					}
				break;
				case 'LINK':
					if ( array_key_exists( 'HREF', $this->attributes ) ) {
						$this->link .= $this->attributes['HREF'];
					}
					$this->link .= $data;
				break;
				case 'PUBDATE':
				case 'UPDATED':
					$this->date .= $data;
				break;
			}
		} elseif ( $this->inChannel ) {
			switch ( $this->tag ) {
				case 'TITLE':
					$this->feedTitle .= $data;
					break;
			}
		}
	}
	
	function showSlot() {
		$this->itemCount = $this->setting( 'number_feeds_to_show' );
		
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
		
		$pageMergeFields = array(
			'Title' => $title,
			'Source' => '<p>Feed source: <a href="' . $this->setting( 'feed_source' ) . '">' . $this->setting( 'feed_source' ) . '</a></p>'
		);

		$subSections = array( 'Content_Section' => true );
		$dateFormat = '';

		if ( "dont_show" == $this->setting( 'show_date_time' ) ) {
			$subSections['Date_Section'] = false; 
		} else {
			$subSections['Date_Section'] = true; 
			if ( $this->setting('date_format') == '_SHORT' ) {
				$dateFormat = setting('vis_date_format_short');
			} elseif ( $this->setting('date_format') == '_LONG' ) {
				$dateFormat = setting('vis_date_format_long');
			} elseif ( $this->setting('date_format') == '_MEDIUM' ) {
				$dateFormat = setting('vis_date_format_med');
			}
		}

		$this->frameworkHead('Feed_Reader', 'Feeds', $pageMergeFields, $subSections );
			$itemCount =0;
			foreach ( $items as $feedContent ) {
				if (++$itemCount>$this->itemCount){
					break;
				}
				if ($this->setting('rss_date_format')=='backslashed_american'){
					$dateFeedContent = $feedContent['date'];
				}elseif($this->setting('rss_date_format')=='backslashed_european'){
					$dateFeedContent = explode('/',$feedContent['date']);
					if (count($dateFeedContent)==3){
						$dateFeedContent=$dateFeedContent[1] . '/' . $dateFeedContent[0] . '/' . $dateFeedContent[2] ;
					} else{
						$dateFeedContent=$feedContent['date'];
					}
				}elseif($this->setting('rss_date_format')=='autodetect'){
					$dateFeedContent = $feedContent['date'];
				} else {
					$dateFeedContent = $feedContent['date'];
				}

				if ( "date_only" == $this->setting( 'show_date_time' ) ) {
					$feedContent['date'] = formatDateNicely( date( 'Y-m-d', strtotime( $dateFeedContent ) ), $dateFormat );
				} elseif ( "date_and_time" == $this->setting( 'show_date_time' ) ) {
					$feedContent['date'] = formatDateTimeNicely( date( 'Y-m-d H:i:s', strtotime( $dateFeedContent ) ), $dateFormat );
				}
				
				if ( ! $this->setting( 'size' ) > 0 ) {
					$feedContent['description'] = '';
				} else {
					$feedContent['description'] = $this->truncateNicely( trim ( strip_tags( strtr( $feedContent['description'], array( "\n" => '<br> ', "\r\n" =>'<br> ' ) ) ) ) , $this->setting( 'size' ) );
				}
				$feedMergeFields = array( 
					'Feed_Title' => $feedContent['title'], 
					'Feed_Description' => $feedContent['description'], 
					'Date' => $feedContent['date'] 
				);
				$this->framework( 'Feeds', $feedMergeFields, $subSections );
			}
		$this->frameworkFoot('Feed_Reader', 'Feeds', $pageMergeFields, $subSections );
	
	}
	
	protected function getLiveFeed() {
		$feed = @file_get_contents( $this->setting( 'feed_source' ) );
		if ( ! $feed  ) { 
			$feed = '<?xml version="1.0" encoding="UTF-8"			<error>
				<item>
					<title>Feed read error</title>
					<link>' . htmlentities( $this->setting( 'feed_source' ) ) . '</link>
					<description>Error reading feed data</description>
					<admin_message>from ' . htmlentities( $this->setting( 'feed_source' ) ) . '.</admin_message>
					<updated>' . date( 'Y-m-d H:i:s' ) . '</updated>
				</item>
			</error>';
		}
		return mb_convert_encoding($feed,"UTF-8");
	}

	protected function getRssFeed() {
		$this->content = array();
		$xml = $this->cache('getLiveFeed', 60 * (int) $this->setting('cache'), $this->setting('feed_source'));
		xml_parse( $this->xmlParser, $xml );
		xml_parser_free( $this->xmlParser );
		if (count($this->newsDates)==0) {
			return array();
		} elseif ($this->setting('news_order')=='asc'){
			array_multisort($this->newsDates, SORT_ASC, $this->content);
		} elseif($this->setting('news_order')=='desc') {
			array_multisort($this->newsDates, SORT_DESC, $this->content);
		} elseif ($this->setting('news_order')=='title_alpha') {
			array_multisort($this->newsTitles, SORT_ASC, $this->content);
		}
		
		return $this->content;
	}
	
	protected function truncateNicely( $string, $max ) {
		$string = trim( $string );
		if ( strlen( $string ) > $max ) {
			$breakpoint2 = strpos( $string, ' ', $max ); // find last ' '
			$breakpoint1 = strrpos( substr( $string, 0, $breakpoint2 ), ' ' ); // find new last ' '
    		if ( false ===  $breakpoint1 ) { 
				$string = ''; 
			} else {
				$string = substr( $string, 0, $breakpoint1 ) . $this->phrase( '_EXCERPT_CUTOFF' ); 
			}
		}
		return $string;
	}

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path){
			case 'plugin_settings':
				$box['tabs']['display']['fields']['date_format']['hidden'] = $values['display/show_date_time']=='dont_show';
				$box['tabs']['display']['fields']['feed_title']['hidden'] = $values['display/title']!='use_custom_title';
				$box['tabs']['filtering']['fields']['regexp']['hidden'] = $values['filtering/regexp_field']=='do_no_filter';
				break;
				
		}
	}
}

?>