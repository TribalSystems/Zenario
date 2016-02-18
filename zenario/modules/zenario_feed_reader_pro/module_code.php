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




class zenario_feed_reader_pro extends zenario_feed_reader {

	protected $field = '';
	protected $pattern = '';

	
	function init() {
		zenario_feed_reader::init();
	    $this->field = $this->setting( 'regexp_field' );
		$this->pattern = '/'. $this->setting( 'regexp' ) . '/';
		return true;
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

	public function formatAdminBox($path, $settingGroup, &$box, &$fields, &$values, $changes) {
		switch ($path){
			case 'plugin_settings':
				$box['tabs']['filtering']['fields']['regexp']['hidden'] = $values['filtering/regexp_field']=='do_no_filter';
				break;
				
		}
	}
	

}

?>