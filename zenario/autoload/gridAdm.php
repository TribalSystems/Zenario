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

namespace ze;

  class gridAdm {




public static function round($n) {
	return round($n, 3, PHP_ROUND_HALF_DOWN);
}

//Given some grid data, return a nested array with all of the possible
//size-permutations of groupings that have been used
protected static function checkGroupings(&$data, &$groupings, $parentWidth, $maxDepth = 0) {
	
	$nextDepth = $maxDepth + 1;
	
	//Loop through the current grouping, looking for more cells and possibly more groupings
	foreach ($data['cells'] as &$cell) {
		
		if (empty($cell['width'])) {
			$cell['width'] = 1;
		}
		
		//If the new grouping is smaller than the previous...
		if ($cell['width'] != $parentWidth
		//...(Bug fix: *or* the previous was the top level, with margins set on the top)..
		 || !empty($data['gutterLeftEdgeFlu'])
		 || !empty($data['gutterRightEdgeFlu'])
		) {
			//...note down that it exists...
			if (empty($groupings[$cell['width']])) {
				
				//For a smaller filesize, we'll want as few rules as possible, however
				//we need to add sub-rules for one level more than we think, to allow any possible arrangement of minigrid.
				$extraLevelOfRules = [];
				$j = $cell['width'];
				for ($i = 1; $i < $j; ++$i) {
					$extraLevelOfRules[$i] = [];
				}
				
				$groupings[$cell['width']] = $extraLevelOfRules;
			}
			//...then keep checking recursively if this is a grouping
			if (!empty($cell['cells'])) {
				//...then keep checking recursively
				$maxDepth = max(
					$maxDepth,
					self::checkGroupings($cell, $groupings[$cell['width']], $cell['width'], $nextDepth)
				);
			}
		} else {
			//If it's the same size, keep checking recursively, but add the results to this level
			if (!empty($cell['cells'])) {
				$maxDepth = max(
					$maxDepth,
					self::checkGroupings($cell, $groupings, $cell['width'], $nextDepth)
				);
			}
		}
	}
	
	return $maxDepth;
}

protected static function generateCSSR(
	&$css, &$data, &$groupings, $parentWidth,
	$parentSelector = '', $parentOverallPercent = 100, $level = 0
) {
	
	//Calculate the relative % that a margin should take up
	$marginPercent = 0;
	if ($parentWidth > 1) {
		$marginPercent = self::round(100 * $data['gGutter'] / 2 / $parentOverallPercent);
	}
	
	$css .= "\n". $parentSelector. ".span {\n\tmargin-left: ". $marginPercent. "%;\n\tmargin-right: ". $marginPercent. "%;\n}\n";
	
	foreach ($groupings as $width => $moreGroupings) {
		
		$selector = $parentSelector. '.span'. $width. ' ';
		
		//Calculate the % of the screen that this cell should take up
		$cellOverallPercent = $width * $data['gColWidth'] + ($width - 1) * $data['gGutter'];
		
		//Compare to the parent to calculate a relative %
		$cellPercent = self::round(100 * $cellOverallPercent / $parentOverallPercent);
		
		$css .= "\n". $selector. " {\n\twidth: ". $cellPercent. "%;\n}\n";
		
		if (!empty($moreGroupings)) {
			self::generateCSSR(
				$css, $data, $moreGroupings, $width,
				$selector, $cellOverallPercent, $level + 1);
		}
	}
}

//Generate the CSS needed for a grid
//This is based on 960.gs, but with a few features removed (such as push, pull prefixes and suffixes),
//and a more few more features added (such as a fluid option, a responsive option and customised left/right gutters)
public static function generateCSS(&$css, $data) {
	$css = '/*
 * ';
	
	if ($data['fluid']) {
		$data['gColWidth'] = (100 - ($data['cols'] - 1) * $data['gGutter'] - $data['gGutterLeftEdge'] - $data['gGutterRightEdge']) / $data['cols'];
		$gGutterLeft = $data['gGutter'] / 2;
		$gGutterRight = $data['gGutter'] / 2;
		
		$css .= $data['minWidth']. ' - '. $data['maxWidth']. ' fluid'. ($data['responsive']? ' responsive' : ''). ($data['mirror']? ' left-to-right' : ''). ' Grid ('. $data['cols']. ' col'. ($data['cols'] > 1? 's' : ''). ')';
	} else {
		$gGutterLeft = ceil($data['gGutter'] / 2);
		$gGutterRight = floor($data['gGutter'] / 2);
		
		$css .= $data['minWidth']. ' fixed'. ($data['responsive']? ' responsive' : ''). ($data['mirror']? ' left-to-right' : ''). ' Grid ('. $data['cols']. ' col'. ($data['cols'] > 1? 's' : ''). ')';
	}
	
	$css .= '
 * This file was created by the Zenario Gridmaker system, DO NOT EDIT
 * Based on the 960 Grid System, see zenario/libs/manually_maintained/mit/960gs/README.md for more info
*/';
	
	$container_cols = '.container_'. $data['cols'];
	
	
	
	//Calculate an array with all of the possible size-permutations of groupings that have been used
	$groupings = [];
	
	//Always include the rules for the top-level parent slots. This fixes a few bugs, including:
		//We always add the rule for a slot of size 1 for the "Show Grid" button on the Admin Toolbar to work
		//We need to add rules for all of the basic top-level sizes, otherwise the "width " option on the slot dropdowns won't work on fluid grids
	$j = $data['cols'];
	for ($i = 1; $i < $j; ++$i) {
		$groupings[$i] = [];
	}
	
	$maxDepth = 0;
	if (!empty($data['cells'])) {
		$maxDepth = self::checkGroupings($data, $groupings, $data['cols']);
	}
	
	//Nests that use minigrids can add an extra level of depth to the nested grid layouts.
	//Always take the maximum possible depth we think we have and add one to it, to account
	//for any minigrids on the page.
	++$maxDepth;
	
	//For fixed grids, there's actually no need to generate rules further than a depth of 1.
	//It's only when you use %s that you need to pay attention to your parent's width when
	//specifying your own.
	if (!$data['fluid'] && $maxDepth > 1) {
		$maxDepth = 1;
	}
	
	if ($data['responsive']) {
		//Hide grid-specific elements and responsive slots when not wide enough
		$css .= '


 @media all and (max-width: '. ($data['minWidth'] - 1). 'px) {
	.grid_clear,
	.grid_space,
	.pad_slot,
	.responsive {
		display: none;
}}';
	
	//Show the grid when wide enough
	$css .= '


 @media all and (min-width: '. $data['minWidth']. 'px) {';
	}
	
	$css .= '


body {
	min-width: '. $data['minWidth']. 'px;
}

'. $container_cols. ' {';
	
	if ($data['mirror']) {
		$css .= '
	direction: rtl;';
	}
	
	if ($data['fluid']) {
		$css .= '
	min-width: '. $data['minWidth']. 'px;
	max-width: '. $data['maxWidth']. 'px;';
	} else {
		$css .= '
	width: '. $data['minWidth']. 'px;';
	}
	
	$css .= '
	margin: 0 auto;
	padding: 0;
}

'. $container_cols. ' .span {';
	
	//Grid cells and gutters
	if ($data['cols'] > 1) {
		$css .= '
	display: inline;
	float: left;';
	
		if ($data['fluid']) {
			$css .= '
	margin-left: '. $gGutterLeft. '%;
	margin-right: '. $gGutterRight. '%;';
		
		} else {
			$css .= '
	margin-left: '. $gGutterLeft. 'px;
	margin-right: '. $gGutterRight. 'px;';
		}
	} else {
		$css .= '
	display: block;';
	}
	
	$css .= '
}';
	
	if ($data['fluid']) {
		$css .= '
';
		//Fluid widths and margins for cells (and any nested cells)
		self::generateCSSR($css, $data, $groupings, $data['cols'], ''. $container_cols. ' ');
		
		//Full width cells
		$css .= '
'. $container_cols. ' .span1_1 {
	width: '. (100 - $data['gGutterLeftEdge'] - $data['gGutterRightEdge']). '%;
}
';
		
		$prefix = ''. $container_cols. ' ';
		for ($i = 0; $i < $maxDepth; ++$i) {
			$prefix .= '.span ';
			$css .= ($i? ',' : ''). "\n". $prefix. '.span1_1';
		}
		
		$css .= ' {
	width: 100%;
}';
	
	} else {
		//Widths for cells
		for ($i = 1; $i <= $data['cols']; ++$i) {
			$css .=
				"\n\n".
				$container_cols. ' .span'. $i.
				 " {\n\twidth: ". ($i * $data['gColWidth'] + ($i - 1) * $data['gGutter']). "px;\n}";
		}
	}
	
	if ($data['fluid']) {
		//The outermost gutters of the page
		$css .= '

'. $container_cols. ' .alpha {
	margin-left: '. $data['gGutterLeftEdge']. '%;
}

'. $container_cols. ' .omega {
	margin-right: '. $data['gGutterRightEdge']. '%;
}';
	
	} else {
		//The outermost gutters of the page
		$css .= '

'. $container_cols. ' .alpha {
	margin-left: '. $data['gGutterLeftEdge']. 'px;
}

'. $container_cols. ' .omega {
	margin-right: '. $data['gGutterRightEdge']. 'px;
}';
	}
	
	
	//Remove the outermost gutters from nested cells.
	//N.b. we used to skip doing this if there were no groupings, however
	//minigrids also need this rule, so we'll always need at least one level
	//of it.
	if ($maxDepth < 1) {
		$maxDepth = 1;
	}
	
	$css .= '
';
	
	$prefix = '.container ';
	for ($i = 0; $i < $maxDepth; ++$i) {
		$prefix .= '.span ';
		$css .= ($i? ',' : ''). "\n". $prefix. '.alpha';
	}
	
	$css .= ' {
	margin-left: 0;
}
';
	
	$prefix = '.container ';
	for ($i = 0; $i < $maxDepth; ++$i) {
		$prefix .= '.span ';
		$css .= ($i? ',' : ''). "\n". $prefix. '.omega';
	}
	
	$css .= ' {
	margin-right: 0;
	margin-left: -50px;
}';
	
	
	if ($data['cols'] > 1) {
		//Right-float the right-most cell - this hack is needed as old browsers will have rounding errors in the total width
		$css .= '

.container .omega {
	margin-left: -50px;
	float: right;
}';
	
	//Make <div>s clear properly, and stop empty slots collapsing the grid.
	//Code used from http://sonspring.com/journal/clearing-floats
	//and http://www.yuiblog.com/blog/2010/09/27/clearfix-reloaded-overflowhidden-demystified
	$css .= "

.container:before,
.container:after {
	content: '.';
}

.container:before,
.container:after,
.grid_clear,
.pad_slot {
	width: 0;
	height: 0;
	font-size: 0;
	line-height: 0;
	display: block;
	overflow: hidden;
	visibility: hidden;
}

.pad_slot  {
	height: 1px;
}

.container:after,
.grid_clear {
	clear: both;
}";
	
	//Hack to fix a bug in IE 6/7.
	$css .= '

body.ie6 .container,
body.ie7 .container {
	zoom: 1;
}';
	
	} else {
		//Make sure the <div>s clear properly
		$css .= '


'. $container_cols. ':after,
.grid_clear {
	clear: both;
}';
	}

	if ($data['responsive']) {
		//Only show certain responsive slots when not displaying the grid		
		$css .= '
 
	.responsive_only,
	.container .responsive_only {
		display: none;
	}

 }';
	}
	
	
	if ($data['mirror']) {
		$css = str_replace('lezt', 'right', str_replace('right', 'left', str_replace('left', 'lezt', $css)));
	}
	
	return true;
}


//Generate the template HTML needed for a grid.
public static function generateHTML(&$html, &$data, &$slots) {
	
	$meta = [];
	foreach (['cols', 'minWidth', 'maxWidth', 'fluid', 'responsive'] as $var) {
		if (isset($data[$var])) {
			$meta[$var] = $data[$var];
		} else {
			$meta[$var] = 0;
		}
	}
	
	$html .= '<'. '?php if (!defined(\'NOT_ACCESSED_DIRECTLY\')) exit(\'This file may not be directly accessed\');

	/*
	 * DO NOT EDIT THIS FILE!
	 *
	 * This file was created by the Zenario Gridmaker system.
	 * Any manual edits will be lost when the system next changes this file.
	 */
?'. '>

<script type="text/javascript">
	zenarioL.init('. json_encode($meta). ');
</script>
';
	
	if (!empty($data['cells']) && is_array($data['cells'])) {
		$ord = 0;
		$lines = [];
		self::generateHTMLR($html, $lines, $data, $data, $slots, $ord, $data['cols'], 0);
		unset($lines);
	}
		
	$html .= '

';
	
	$trimmedData = $data;
	\ze\gridAdm::trimData($trimmedData);
	
	
	$html .= "\n";
}

public static function generateHTMLR(&$html, &$lines, &$data, &$grouping, &$slots, &$ord, $cols, $level) {
	$gridOpen = false;
	$firstLine = true;
	$gridCSSClass = 'Gridbreak_A';
	$totalHeight = 0;
	
	$nl = "\n". str_pad('', $level + 2, "\t");
	
	
	//Loop through the cells for this container, keeping track of their widths
	$lines = [];
	$lines[$lineNum = 0] = ['height' => 1, 'line' => []];
	$widthSoFarThisLine = 0;
	foreach ($grouping['cells'] as $i => $cell) {
		
		//Clear up any possible bad data
		if (!empty($cell['addCell'])
		 || (empty($cell['cells'])
		  && empty($cell['grid_break'])
		  && empty($cell['slot'])
		  && empty($cell['space']))) {
			
			//Catch a case when migrating from an older version of Gridmaker
			if (!empty($cell['name'])
			 && !empty($cell['width'])) {
				$cell['slot'] = true;
			} else {
				continue;
			}
		}
		
		if (!empty($cell['grid_break']) && $level == 0) {
			$cell['width'] = $cols;
			
			$cell['class'] = '';
			if (isset($cell['css_class'])) {
				$cell['class'] = $cell['css_class'];
			}
						
		} else {
			if (empty($cell['width'])) {
				$cell['width'] = 1;
			}
		
			$cell['class'] = \ze\content::rationalNumberGridClass($cell['width'] = min($cols, $cell['width']), $cols);
			if (isset($cell['css_class'])) {
				$cell['class'] .= ' '. $cell['css_class'];
			}
		}
		
		
		//Keep track of how much width we've used, versus how wide a row is. If we've gone over, start a new line.
		if ($widthSoFarThisLine + $cell['width'] > $cols) {
			//Also add a space if needed, if the grids didn't quite match the line-length
			if ($cols > $widthSoFarThisLine) {
				$lines[$lineNum]['line'][] = ['space' => true, 'width' => $cols - $widthSoFarThisLine, 'class' => \ze\content::rationalNumberGridClass($cols - $widthSoFarThisLine, $cols)];
			}
			$lines[++$lineNum] = ['height' => 1, 'line' => []];
			$widthSoFarThisLine = 0;
		}
		
		$widthSoFarThisLine += $cell['width'];
		
		//Keep track on which cells are on which line.
		$lines[$lineNum]['line'][] = $cell;
	}
	
	//Add an empty space onto the end if we didn't finish one line exactly
	if ($cols > $widthSoFarThisLine) {
		$lines[$lineNum]['line'][] = ['space' => true, 'width' => $cols - $widthSoFarThisLine, 'class' => \ze\content::rationalNumberGridClass($cols - $widthSoFarThisLine, $cols)];
	}
	
	foreach ($lines as &$line) {
		
		//Handle breaks in the grid - these can either be just spaces, or can have slots
		if (!empty($line['line'][0]['grid_break'])) {
			if ($level != 0) {
				continue;
			}
			
			//Close the grid if it is open
			//(Note that if two breaks next ot each other, we won't write an empty grid)
			if ($gridOpen) {
				$html .= "\n\t". '</div>';
				$html .= "\n". '</div>';
			}
			$gridOpen = false;
			$firstLine = true;
			
			
			//Draw a slot if this break has a slot in it
			if (!empty($line['line'][0]['slot']) && !empty($line['line'][0]['name'])) {
				
				$html .= "\n". '<div class="slot '. \ze\ring::HTMLId($line['line'][0]['name']);
				
				if (!empty($line['line'][0]['class'])) {
					$html .= ' '. htmlspecialchars($line['line'][0]['class']);
				}
			
				if ($data['responsive'] && !empty($line['line'][0]['small'])) {
					switch ($line['line'][0]['small']) {
						//Hide responsive slots on small screen sizes
						case 'hide':
							$html .= ' responsive';
							break;
						
						//Only show responsive slots on small screen sizes
						case 'only':
							$html .= ' responsive_only';
							break;
					}
				}
				
				$html .= '">';
				
				$html .= "\n\t<". "?php \ze\\plugin::slot('". \ze\ring::HTMLId($line['line'][0]['name']). "', 'outside_of_grid'); ?". ">";
				$slots[$line['line'][0]['name']] = $line['line'][0];
				$slots[$line['line'][0]['name']]['ord'] = ++$ord;
			
				$html .= "\n". '</div>';
			
			//A break with no slot
			} else {
				//Add any custom html
				
				//We used to allow custom HTML in a grid-break, but this ability has been
				//removed as it wasn't being used.
				#if (!empty($line['line'][0]['html'])) {
				#	$html .= "\n". $line['line'][0]['html'];
				#}
			}
			
			//Note down the CSS class for the next grid
			if (!empty($line['line'][0]['grid_css_class'])) {
				$gridCSSClass = $line['line'][0]['grid_css_class'];
			}
		
		//Handle normal rows in the grid
		} else {
			//Loop through each line, replacing double-spaces with single spaces where we can
			$c = count($line['line']) - 1;
			for ($i = $c; $i >= 1; --$i) {
				if (!empty($line['line'][$i]['space'])
				 && empty($line['line'][$i]['space']['html'])
				 && empty($line['line'][$i]['space']['css_class'])
				 && !empty($line['line'][$i - 1]['space'])
				 && empty($line['line'][$i - 1]['space']['html'])
				 && empty($line['line'][$i - 1]['space']['css_class'])) {
					//Add the space on the second space to the first space 
					$line['line'][$i - 1]['width'] += $line['line'][$i]['width'];
					$line['line'][$i - 1]['class'] = \ze\content::rationalNumberGridClass($line['line'][$i - 1]['width'], $cols);
					//Then delete the second space
					array_splice($line['line'], $i, 1);
				}
			}
		
			//Loop through each line, drawing out each of the contents
			if (!empty($line['line'])) {
				$offset = 0;
				$c = count($line['line']) - 1;
				$array_keys = array_keys($line['line']);
			
				//The right-most grid element if always floated right, so doesn't actually need to be written
				//last in the HTML. We can use this to put it first on the page if we wish
				if ($data['responsive'] && $c > 0 && ($line['line'][$c]['small'] ?? false) == 'first') {
					$last = array_pop($array_keys);
					array_splice($array_keys, 0, 0, [$last]);
				}
			
				if ($level == 0 && !$gridOpen) {
					//Open the grid if it is not already open
					$html .= "\n". '<div class="'. htmlspecialchars($gridCSSClass). '">';
					$html .= "\n\t". '<div class="container container_'. $cols. '">';
					$gridCSSClass = '';
			
				} elseif (!$firstLine) {
					//Add a line-break for each new line in this container (note the ends of containers don't need line breaks)
					$html .= $nl. '<div class="grid_clear"></div>';
				}
				$gridOpen = true;
				$firstLine = false;
			
				foreach ($array_keys as $i) {
				
					//The first cell needs the alpha CSS class, and the last needs the omega CSS class
					if ($alpha = $i == 0) {
						$line['line'][$i]['class'] .= ' alpha';
					}
					if ($omega = $i == $c) {
						$line['line'][$i]['class'] .= ' omega';
					}
				
					if ($data['responsive'] && !empty($line['line'][$i]['small'])) {
						switch ($line['line'][$i]['small']) {
							//Hide responsive slots on small screen sizes
							case 'hide':
								$line['line'][$i]['class'] .= ' responsive';
								break;
							
							//Only show responsive slots on small screen sizes
							//Note: only allow this for full width slots
							case 'only':
								if ($alpha && $omega) {
									$line['line'][$i]['class'] .= ' responsive_only';
								}
								break;
						}
					}
				
					//Work out the HTML for this cell
					$html .= $nl. '<div class="'. $line['line'][$i]['class'];
									
					//Draw nested cells recursively
					if (!empty($line['line'][$i]['cells'])) {
						$html .= '">';
					
						$gColsNested = $line['line'][$i]['width'];
						$line['line'][$i]['lines'] = [];
						
						$line['height'] = max(
							$line['height'],
							self::generateHTMLR($html, $line['line'][$i]['lines'], $data, $line['line'][$i], $slots, $ord, $gColsNested, $level + 1)
						);
				
					//Draw a slot
					} elseif (!empty($line['line'][$i]['slot']) && !empty($line['line'][$i]['name'])) {
						$html .= ' slot ';
						
						//N.b. the ability to set custom heights for slots has been removed in version 9.3.
						//Everything now works as if you chose the "small" option.
						$height = 1;
						
						$line['height'] = max(
							$line['height'],
							$height
						);
						
						$html .= ' '. \ze\ring::HTMLId($line['line'][$i]['name']). '">';
				
						$html .= $nl. "\t<". "?php \ze\\plugin::slot('". \ze\ring::HTMLId($line['line'][$i]['name']). "', 'grid'); ?". ">";
						$slots[$line['line'][$i]['name']] = $line['line'][$i];
						$slots[$line['line'][$i]['name']]['ord'] = ++$ord;
					
					//Draw a space
					} else {
						$html .= ' grid_space">';
				
						//Add any custom html
						if (!empty($line['line'][$i]['html'])) {
							$html .= $nl. $line['line'][$i]['html'];
						}
						
						$html .= $nl. "\t". '<span class="pad_slot">&nbsp;</span>';
					}
				
					$html .= $nl. '</div>';
				}
			}
		}
		
		$totalHeight += $line['height'];
	}
	
	
	if ($level == 0) {
		if ($gridOpen) {
			//Close the grid if it is open
			$html .= "\n\t". '</div>';
			$html .= "\n". '</div>';
		}
	}
	
	//echo 'totalHeight:', $totalHeight, "\n";
	return $totalHeight;
}

private static function updateMetaInfoInDBR(&$data, &$slots) {
	if (!empty($data['cells'])) {
		foreach ($data['cells'] as &$cell) {
			if (!empty($cell['slot'])) {
				$slots[$cell['name']] = $cell;
			}
			self::updateMetaInfoInDBR($cell, $slots);
		}
	}
}

public static function updateHeaderMetaInfoInDB($data, $details = []) {
	
	$details['head_json_data'] = $data;
	
	$details['cols'] = $data['cols'] ?? false;
	$details['min_width'] = $data['minWidth'] ?? false;
	$details['max_width'] = $data['maxWidth'] ?? false;
	$details['fluid'] = $data['fluid'] ?? false;
	$details['responsive'] = $data['responsive'] ?? false;
					
	\ze\row::set('layout_head_and_foot', $details, ['for' => 'sitewide']);
}

public static function updateMetaInfoInDB(&$data, $layoutId) {
					
	//Update the information on the grid in the layouts table
	\ze\row::update('layouts',
		[
			'cols' => ($data['cols'] ?? false),
			'min_width' => ($data['minWidth'] ?? false),
			'max_width' => ($data['maxWidth'] ?? false),
			'fluid' => ($data['fluid'] ?? false),
			'responsive' => ($data['responsive'] ?? false),
			'header_and_footer' => ($data['headerAndFooter'] ?? false)
		],
		$layoutId
	);
	
	$slots = [];
	self::updateMetaInfoInDBR($data, $slots);
	
	//Remove any deleted slots from the database
	$sql = "
		DELETE FROM ". DB_PREFIX. "layout_slot_link
		WHERE layout_id = ". (int) $layoutId;

	if (!empty($slots)) {
		$sql .= "
		  AND slot_name NOT IN (". \ze\escape::in(array_keys($slots), 'asciiInSQL'). ")";
	}

	\ze\sql::update($sql);
	$ord = 0; 
	foreach ($slots as &$cell) {
		$ord++;
		
		\ze\row::set(
		'layout_slot_link',
		[
			'ord' => $ord,
			'cols' => $cell['width'],
			'small_screens' => ($cell['small'] ?? false) ?: 'show',
			'is_header' => !empty($cell['isHeader']),
			'is_footer' => !empty($cell['isFooter'])
		],
		[
			'layout_id' => $layoutId,
			'slot_name' => $cell['name']]
		);
	}

}




public static function getLayoutData($layoutId) {
	return \ze\row::get('layouts', 'json_data', $layoutId);
}


public static function checkData(&$data) {
	if ($data && is_array($data)) {
		
		//Disallow editing of grids made using bootstrap compatability mode, we don't support this option any more.
		if (!empty($data['bootstrap'])) {
			return false;
		}
		
		//Automatically remove some old now-unused settings
		unset(
			$data['bp1'],
			$data['bp2'],
			$data['break1'],
			$data['break2']
		);
		
		$data['fluid'] = !empty($data['fluid']);
		$data['mirror'] = !empty($data['mirror']);
		$data['responsive'] = !empty($data['responsive']);
		
		$data['cols'] = (int) ($data['cols'] ?? 0);
		
		//Calculate some redundant variables needed to generate the grid
		if ($data['fluid']) {
			$data['gColWidth'] = 0;
			$data['gGutter'] = (float) ($data['gutterFlu'] ?? 0.0);
			$data['gGutterLeftEdge'] = (float) ($data['gutterLeftEdgeFlu'] ?? 0.0);
			$data['gGutterRightEdge'] = (float) ($data['gutterRightEdgeFlu'] ?? 0.0);
			$data['minWidth'] = (int) ($data['minWidth'] ?? 0);
			$data['maxWidth'] = (int) ($data['maxWidth'] ?? 0);
		
		} else {
			$data['gColWidth'] = (int) ($data['colWidth'] ?? 0);
			$data['gGutter'] = (int) ($data['gutter'] ?? 0);
			$data['gGutterLeftEdge'] = (int) ($data['gutterLeftEdge'] ?? 0);
			$data['gGutterRightEdge'] = (int) ($data['gutterRightEdge'] ?? 0);
			$data['minWidth'] =
			$data['maxWidth'] =
				$data['cols'] * $data['gColWidth']
			  + $data['gGutterLeftEdge']
			  + $data['gGutterRightEdge']
			  + ($data['cols'] - 1) * $data['gGutter'];
		}
		
		//Require at least one column, and widths to be set
		if ($data['cols'] < 1
		 || $data['minWidth'] < 1
		 || $data['maxWidth'] < 1
		 || (!$data['fluid'] && ! $data['gColWidth'])) {
			return false;
		}
		
		return true;
	}
	
	return false;
}


//If this is from an existing Layout, check what the slot names originally were and what they are now.
//N.b. relies on the oName property being set on the renamed slots
public static function checkForRenamedSlots($data, &$oldToNewNames) {
	
	//Loop through the current grouping, looking for more cells and possibly more groupings
	foreach ($data['cells'] as &$cell) {
		//Keep checking groupings recursively
		if (!empty($cell['cells'])) {
			self::checkForRenamedSlots($cell, $oldToNewNames);
		
		//Check for renamed slots
		} else
		if (!empty($cell['name'])) {
			if (!empty($cell['oName']) && $cell['name'] != $cell['oName']) {
				$oldToNewNames[$cell['oName']] = $cell['name'];
			}
		}
	}
}

//Clean up some junk from the slot data that doesn't need to be saved
public static function trimData(&$data, $top = true) {
	
	//Loop through the current grouping, looking for more cells and possibly more groupings
	if (!empty($data['cells'])) {
		foreach ($data['cells'] as &$cell) {
			//Keep checking groupings recursively
			self::trimData($cell, false);
		
			//Remove the original names as there is no need to save them
			unset($cell['oName']);
		
			//Remove a deprecated property
			unset($cell['type']);
		}
	}
	
	//Remove specific settings for fixed/fluid grids, if the other choice was chosen
	if (!empty($data['fluid'])) {
		unset($data['gutter']);
		unset($data['gutterLeftEdge']);
		unset($data['gutterRightEdge']);
		unset($data['colWidth']);
	} else {
		unset($data['gutterFlu']);
		unset($data['gutterLeftEdgeFlu']);
		unset($data['gutterRightEdgeFlu']);
	}
	
	//Remove the redundant calculated values that were added to help draw the grid
	unset($data['gColWidth']);
	unset($data['gGutter']);
	unset($data['gGutterLeftEdge']);
	unset($data['gGutterRightEdge']);
}

//Strip the header and footer definition from a layout, for when we want to edit it.
public static function trimHeadAndFootSlots(&$body) {
	$cells = [];
	
	if (!empty($body['cells'])) {
		foreach ($body['cells'] as $cell) {
			if (empty($cell['isHeader'])
			 && empty($cell['isFooter'])) {
				$cells[] = $cell;
			}
		}
	}
	
	$body['cells'] = $cells;
}

//Add the header and footer definition to a layout, for when we want to save it.
public static function addHeadAndFootSlots(&$body, $head, $foot) {
	$cells = [];
	
	if (!empty($head['cells'])) {
		foreach ($head['cells'] as $cell) {
			self::addPropertyR($cell, 'isHeader');
			$cells[] = $cell;
		}
	}
	
	if (!empty($body['cells'])) {
		foreach ($body['cells'] as $cell) {
			if (empty($cell['isHeader'])
			 && empty($cell['isFooter'])) {
				$cells[] = $cell;
			}
		}
	}
	
	if (!empty($foot['cells'])) {
		foreach ($foot['cells'] as $cell) {
			self::addPropertyR($cell, 'isFooter');
			$cells[] = $cell;
		}
	}
	
	$body['cells'] = $cells;
}


protected static function addPropertyR(&$data, $prop) {
	$data[$prop] = true;
	
	if (!empty($data['cells'])) {
		foreach ($data['cells'] as &$cell) {
			self::addPropertyR($cell, $prop);
		}
	}
}


public static function updateHeadAndFootInAllLayouts($oldToNewNames) {
	$hf = \ze\row::get('layout_head_and_foot', ['head_json_data', 'foot_json_data'], ['for' => 'sitewide']);
	$head = $hf['head_json_data'];
	$foot = $hf['foot_json_data'];
	
	foreach (\ze\sql::select('
		SELECT layout_id, json_data
		FROM '. DB_PREFIX. 'layouts
		WHERE header_and_footer = 1
	') as $layout) {
		$body = $layout['json_data'];
		$layoutId = $layout['layout_id'];
		
		if (empty($body) || !is_array($body)) {
			$body = [];
		}
		
		\ze\gridAdm::updateHeadAndFootInLayout($body, $head, $foot);
		\ze\gridAdm::saveLayoutData($layoutId, $body, $oldToNewNames);
	}
	
	
	//Look for any renamed slots
	if (!empty($oldToNewNames)) {
	
		foreach ($oldToNewNames as $oldName => $newName) {
			//Try to catch the case where two slots have their names switched.
			//Don't change the data in the database if this has happened.
			if (empty($oldToNewNames[$newName])
			 && !\ze\row::exists('plugin_sitewide_link', ['slot_name' => $newName])) {
				$sql = "
					UPDATE IGNORE ".  DB_PREFIX. "plugin_sitewide_link
					SET slot_name = '". \ze\escape::asciiInSQL($newName). "'
					WHERE slot_name = '". \ze\escape::asciiInSQL($oldName). "'";
				\ze\sql::update($sql);
			}
		}
	}
}


public static function updateHeadAndFootInLayout(&$body, $head = null, $foot = null) {
	
	if (is_null($head)
	 || is_null($foot)) {
		$hf = \ze\row::get('layout_head_and_foot', ['head_json_data', 'foot_json_data'], ['for' => 'sitewide']);
		$head = $hf['head_json_data'];
		$foot = $hf['foot_json_data'];
	}
	
	\ze\gridAdm::trimData($body);
	\ze\gridAdm::trimHeadAndFootSlots($body);
	\ze\gridAdm::addHeadAndFootSlots($body, $head, $foot);
	
	foreach ($head as $prop => $value) {
		if ($prop != 'cells') {
			$body[$prop] = $value;
		}
	}
}


public static function updateHeadAndFootAndSaveLayoutData($layoutId, $data, $oldToNewNames = []) {
	
	if (!empty($data['headerAndFooter'])) {
		\ze\gridAdm::updateHeadAndFootInLayout($data);
	} else {
		\ze\gridAdm::trimHeadAndFootSlots($data);
	}
	
	\ze\gridAdm::trimData($data);
	\ze\gridAdm::saveLayoutData($layoutId, $data, $oldToNewNames);
}


public static function saveLayoutData($layoutId, $body, $oldToNewNames = []) {
	
	$layoutDetails = [];
	$layoutDetails['json_data'] = $body;
	$layoutDetails['json_data_hash'] = \ze::hash64(json_encode($body), 8);
	\ze\row::update('layouts', $layoutDetails, $layoutId);
	
	
	//Look for any renamed slots
	if (!empty($oldToNewNames)) {
	
		foreach ($oldToNewNames as $oldName => $newName) {
			//Try to catch the case where two slots have their names switched.
			//Don't change the data in the database if this has happened.
			if (empty($oldToNewNames[$newName])
			 && !\ze\row::exists(
					'layout_slot_link',
					[
						'layout_id' => $layoutId,
						'slot_name' => $newName]
			)) {
				//Switch the slot names in the system
				$sql = "
					UPDATE IGNORE ".  DB_PREFIX. "plugin_layout_link
					SET slot_name = '". \ze\escape::asciiInSQL($newName). "'
					WHERE slot_name = '". \ze\escape::asciiInSQL($oldName). "'
					  AND layout_id = ". (int) $layoutId;
				\ze\sql::update($sql);
			
				$sql = "
					UPDATE IGNORE ".  DB_PREFIX. "layout_slot_link
					SET slot_name = '". \ze\escape::asciiInSQL($newName). "'
					WHERE slot_name = '". \ze\escape::asciiInSQL($oldName). "'
					  AND layout_id = ". (int) $layoutId;
				\ze\sql::update($sql);
			
				$sql = "
					UPDATE IGNORE ".  DB_PREFIX. "content_item_versions AS v
					INNER JOIN ".  DB_PREFIX. "plugin_instances AS pi
					   ON pi.content_id = v.id
					  AND pi.content_type = v.type
					  AND pi.content_version = v.version
					SET pi.slot_name = '". \ze\escape::asciiInSQL($newName). "'
					WHERE pi.slot_name = '". \ze\escape::asciiInSQL($oldName). "'
					  AND v.layout_id = ". (int) $layoutId;
				\ze\sql::update($sql);
			
				$sql = "
					UPDATE IGNORE ".  DB_PREFIX. "content_item_versions AS v
					INNER JOIN ".  DB_PREFIX. "plugin_item_link AS pil
					   ON pil.content_id = v.id
					  AND pil.content_type = v.type
					  AND pil.content_version = v.version
					SET pil.slot_name = '". \ze\escape::asciiInSQL($newName). "'
					WHERE pil.slot_name = '". \ze\escape::asciiInSQL($oldName). "'
					  AND v.layout_id = ". (int) $layoutId;
				\ze\sql::update($sql);
			}
		}
	}

	//Update the new slots in the DB
	\ze\gridAdm::updateMetaInfoInDB($body, $layoutId);

}


public static function sensibleDefault() {
	//Just get some options that are a sensible default to start with
	return [
		'cols' => 12,
		'fluid' => true,
		"responsive" => true,
		'mirror' => false,
		'maxWidth' => 1240,
		'minWidth' => 769,
		"gutterFlu" => 1,
		"gutterLeftEdgeFlu" => 1,
		"gutterRightEdgeFlu" => 1
	];
}

//
//	Source Code and Checksums for Grid Layouts (Deprecated)
//
//When writing layout files to the disk, we used to add their source-code and a checksum to them.
//There's no point in doing this now we store them by ID in the database, however when migrating
//from old versions you might still see files with these, and it's useful to be able to read
//them for migration purposes.

public static function readCode(&$html, $justCheck = false, $quickCheck = false) {
	$parts = explode('<?'. 'php //data:', $html, 2);
	
	if (!empty($parts[1])) {
		$parts = explode('//', $parts[1], 3);
		
		//Don't allow the quick-check option for old versions of Gridmaker
		if (($parts[1] ?? false) != 'v2') {
			$quickCheck = false;
		}
		
		if ($quickCheck && $parts[0]) {
			return true;
		}
		
		if (($parts = strtr($parts[0], '~-_,', ' +/='))
		 && ($parts = base64_decode($parts))
		 && ($parts = gzuncompress($parts))
		 && ($parts = json_decode($parts, true))
		 && (!empty($parts))
			//Disallow editing of grids made using bootstrap compatability mode
		 && (empty($parts['bootstrap']))) {
			if ($justCheck) {
				return true;
			} else {
				return $parts;
			}
		}
	}
	
	return false;
}


	}