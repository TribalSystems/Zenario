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


  class zenario_grid_maker {




public static function round($n) {
	if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
		return round($n, 3, PHP_ROUND_HALF_DOWN);
	} else {
		return round($n, 3);
	}
}


public static function checkForRenamedSlots(&$data, &$newNames, &$oldToNewNames) {
	
	//Loop through the current grouping, looking for more cells and possibly more groupings
	foreach ($data['cells'] as &$cell) {
		//Keep checking groupings recursively
		if (!empty($cell['cells'])) {
			zenario_grid_maker::checkForRenamedSlots($cell, $newNames, $oldToNewNames);
		
		//Check for renamed slots
		} else
		if (!empty($cell['name'])) {
			$newNames[$cell['name']] = $cell['name'];
			
			if (!empty($cell['oName']) && $cell['name'] != $cell['oName']) {
				$oldToNewNames[$cell['oName']] = $cell['name'];
			}
		}
	}
}

//Reduce the size of the data string
public static function trimData(&$data) {
	
	//Loop through the current grouping, looking for more cells and possibly more groupings
	foreach ($data['cells'] as &$cell) {
		//Keep checking groupings recursively
		if (!empty($cell['cells'])) {
			zenario_grid_maker::trimData($cell);
		}
		
		//Remove the original names as there is no need to save them
		unset($cell['oName']);
		
		//Remove a deprecated property
		unset($cell['type']);
		
		//"height = small" is the default, there's no need to include it as it just pads the data out
		if (isset($cell['height']) && !in($cell['height'], 'xxlarge', 'xlarge', 'large', 'medium')) {
			unset($cell['height']);
		}
	}
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
				$extraLevelOfRules = array();
				$j = $cell['width'];
				for ($i = 1; $i < $j; ++$i) {
					$extraLevelOfRules[$i] = array();
				}
				
				$groupings[$cell['width']] = $extraLevelOfRules;
			}
			//...then keep checking recursively if this is a grouping
			if (!empty($cell['cells'])) {
				//...then keep checking recursively
				$maxDepth = max(
					$maxDepth,
					zenario_grid_maker::checkGroupings($cell, $groupings[$cell['width']], $cell['width'], $nextDepth)
				);
			}
		} else {
			//If it's the same size, keep checking recursively, but add the results to this level
			if (!empty($cell['cells'])) {
				$maxDepth = max(
					$maxDepth,
					zenario_grid_maker::checkGroupings($cell, $groupings, $cell['width'], $nextDepth)
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
		$marginPercent = zenario_grid_maker::round(100 * $data['gGutter'] / 2 / $parentOverallPercent);
	}
	
	$css .= "\n". $parentSelector. ".span {\n\tmargin-left: ". $marginPercent. "%;\n\tmargin-right: ". $marginPercent. "%;\n}\n";
	
	foreach ($groupings as $width => $moreGroupings) {
		
		$selector = $parentSelector. '.span'. $width. ' ';
		
		//Calculate the % of the screen that this cell should take up
		$cellOverallPercent = $width * $data['gColWidth'] + ($width - 1) * $data['gGutter'];
		
		//Compare to the parent to calculate a relative %
		$cellPercent = zenario_grid_maker::round(100 * $cellOverallPercent / $parentOverallPercent);
		
		$css .= "\n". $selector. " {\n\twidth: ". $cellPercent. "%;\n}\n";
		
		if (!empty($moreGroupings)) {
			zenario_grid_maker::generateCSSR(
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
		$data['gColWidth'] = (100 - ($data['gCols'] - 1) * $data['gGutter'] - $data['gGutterLeftEdge'] - $data['gGutterRightEdge']) / $data['gCols'];
		$gGutterLeft = $data['gGutter'] / 2;
		$gGutterRight = $data['gGutter'] / 2;
		
		$css .= $data['minWidth']. ' - '. $data['maxWidth']. ' fluid'. ($data['responsive']? ' responsive' : ''). ($data['mirror']? ' left-to-right' : ''). ' Grid ('. $data['gCols']. ' col'. ($data['gCols'] > 1? 's' : ''). ')';
	} else {
		$gGutterLeft = ceil($data['gGutter'] / 2);
		$gGutterRight = floor($data['gGutter'] / 2);
		
		$css .= $data['minWidth']. ' fixed'. ($data['responsive']? ' responsive' : ''). ($data['mirror']? ' left-to-right' : ''). ' Grid ('. $data['gCols']. ' col'. ($data['gCols'] > 1? 's' : ''). ')';
	}
	
	$css .= '
 * This file was created by the Zenario Gridmaker system, DO NOT EDIT
 * Based on the 960 Grid System, see zenario/libraries/mit/960gs/README.md for more info
*/';
	
	
	//Calculate an array with all of the possible size-permutations of groupings that have been used
	$groupings = array();
	
	//Always include the rules for the top-level parent slots. This fixes a few bugs, including:
		//We always add the rule for a slot of size 1 for the "Show Grid" button on the Admin Toolbar to work
		//We need to add rules for all of the basic top-level sizes, otherwise the "width " option on the slot dropdowns won't work on fluid grids
	$j = $data['gCols'];
	for ($i = 1; $i < $j; ++$i) {
		$groupings[$i] = array();
	}
	
	$maxDepth = 0;
	if (!empty($data['cells'])) {
		$maxDepth = zenario_grid_maker::checkGroupings($data, $groupings, $data['gCols']);
	}
	
	
	//For fixed grids, there's no need to generate rules further than a depth of 1
	if (!$data['fluid'] && $maxDepth > 1) {
		$maxDepth = 1;
	}
	
	if ($data['responsive']) {
		//Hide grid-specific elements and responsive slots when not wide enough
		$css .= '


.grid_clear,
.grid_space,
.pad_slot,
.responsive {
	display: none;
}';
	
	//Show the grid when wide enough
	$css .= '


 @media all and (min-width: '. $data['minWidth']. 'px) {';
	}
	
	$css .= '


body {
	min-width: '. $data['minWidth']. 'px;
}

.container_'. $data['gCols']. ' {';
	
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

.container_'. $data['gCols']. ' .span {';
	
	//Grid cells and gutters
	if ($data['gCols'] > 1) {
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
		zenario_grid_maker::generateCSSR($css, $data, $groupings, $data['gCols'], '.container_'. $data['gCols']. ' ');
		
		//Full width cells
		$css .= '
.container_'. $data['gCols']. ' .span1_1 {
	width: '. (100 - $data['gGutterLeftEdge'] - $data['gGutterRightEdge']). '%;
}
';
		
		$maxI  = (int) $maxDepth + 1;
	
		$prefix = '';
		for ($i = 0; $i < $maxI; ++$i) {
			$prefix .= '.span ';
			$css .= ($i? ',' : ''). "\n". $prefix. '.span1_1';
		}
		
		$css .= ' {
	width: 100%;
}';
	
	} else {
		//Widths for cells
		for ($i = 1; $i <= $data['gCols']; ++$i) {
			$css .= "\n\n.container_". $data['gCols']. " .span". $i. " {\n\twidth: ". ($i * $data['gColWidth'] + ($i - 1) * $data['gGutter']). "px;\n}";
		}
	}
	
	if ($data['fluid']) {
		//The outermost gutters of the page
		$css .= '

.container_'. $data['gCols']. ' .alpha {
	margin-left: '. $data['gGutterLeftEdge']. '%;
}

.container_'. $data['gCols']. ' .omega {
	margin-right: '. $data['gGutterRightEdge']. '%;
}';
	
	} else {
		//The outermost gutters of the page
		$css .= '

.container_'. $data['gCols']. ' .alpha {
	margin-left: '. $data['gGutterLeftEdge']. 'px;
}

.container_'. $data['gCols']. ' .omega {
	margin-right: '. $data['gGutterRightEdge']. 'px;
}';
	}
	
	if ($maxDepth) {
		//Remove the outermost gutters from nested cells
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
	}
	
	if ($data['gCols'] > 1) {
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


.container_'. $data['gCols']. ':after,
.grid_clear {
	clear: both;
}';
	}

	if ($data['responsive']) {
		//Only show certain responsive slots when not displaying the grid
		$css .= '

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
	
	$meta = array();
	foreach (array('cols', 'minWidth', 'maxWidth', 'fluid', 'responsive') as $var) {
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
	zenarioSGS('. json_encode($meta). ');
</script>

<'. '?php if (file_exists(CMS_ROOT. cms_core::$templatePath. \'/includes/header.inc.php\')) {
	require CMS_ROOT. cms_core::$templatePath. \'/includes/header.inc.php\';
}?'. '>
';
	
	if (!empty($data['cells']) && is_array($data['cells'])) {
		$ord = 0;
		$lines = array();
		zenario_grid_maker::generateHTMLR($html, $lines, $data, $data, $slots, $ord, $data['gCols'], 0);
		unset($lines);
	}
		
	$html .= '

<'. '?php if (file_exists(CMS_ROOT. cms_core::$templatePath. \'/includes/footer.inc.php\')) {
	require CMS_ROOT. cms_core::$templatePath. \'/includes/footer.inc.php\';
}?'. '>

';
	
	$trimmedData = $data;
	zenario_grid_maker::trimData($trimmedData);
	
	
	zenario_grid_maker::addCode($html, $trimmedData);
	$html .= "\n";
	zenario_grid_maker::addChecksum($html);
}

public static function generateHTMLR(&$html, &$lines, &$data, &$grouping, &$slots, &$ord, $gCols, $level) {
	$gridOpen = false;
	$firstLine = true;
	$gridCSSClass = 'Grid_A';
	$totalHeight = 0;
	
	$nl = "\n". str_pad('', $level + 2, "\t");
	
	
	//Loop through the cells for this container, keeping track of their widths
	$lines = array();
	$lines[$lineNum = 0] = array('height' => 1, 'line' => array());
	$widthSoFarThisLine = 0;
	foreach ($grouping['cells'] as $i => $cell) {
		
		//Clear up any possible bad data
		if (!empty($cell['addCell'])
		 || (empty($cell['cells'])
		  && empty($cell['grid_break'])
		  && empty($cell['slot'])
		  && empty($cell['space']))) {
			
			//Catch a case when migrating from an older version of Grid Maker
			if (!empty($cell['name'])
			 && !empty($cell['width'])) {
				$cell['slot'] = true;
			} else {
				continue;
			}
		}
		
		if (!empty($cell['grid_break']) && $level == 0) {
			$cell['width'] = $gCols;
			
			$cell['class'] = '';
			if (isset($cell['css_class'])) {
				$cell['class'] = $cell['css_class'];
			}
						
		} else {
			if (empty($cell['width'])) {
				$cell['width'] = 1;
			}
		
			$cell['class'] = rationalNumberGridClass($cell['width'] = min($gCols, $cell['width']), $gCols);
			if (isset($cell['css_class'])) {
				$cell['class'] .= ' '. $cell['css_class'];
			}
		}
		
		
		//Keep track of how much width we've used, versus how wide a row is. If we've gone over, start a new line.
		if ($widthSoFarThisLine + $cell['width'] > $gCols) {
			//Also add a space if needed, if the grids didn't quite match the line-length
			if ($gCols > $widthSoFarThisLine) {
				$lines[$lineNum]['line'][] = array('space' => true, 'width' => $gCols - $widthSoFarThisLine, 'class' => rationalNumberGridClass($gCols - $widthSoFarThisLine, $gCols));
			}
			$lines[++$lineNum] = array('height' => 1, 'line' => array());
			$widthSoFarThisLine = 0;
		}
		
		$widthSoFarThisLine += $cell['width'];
		
		//Keep track on which cells are on which line.
		$lines[$lineNum]['line'][] = $cell;
	}
	
	//Add an empty space onto the end if we didn't finish one line exactly
	if ($gCols > $widthSoFarThisLine) {
		$lines[$lineNum]['line'][] = array('space' => true, 'width' => $gCols - $widthSoFarThisLine, 'class' => rationalNumberGridClass($gCols - $widthSoFarThisLine, $gCols));
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
				
				$html .= "\n". '<div class="slot '. HTMLId($line['line'][0]['name']);
				
				if (!empty($line['line'][0]['class'])) {
					$html .= ' '. htmlspecialchars($line['line'][0]['class']);
				}
				
				$html .= '">';
				
				$html .= "\n\t<". "?php slot('". HTMLId($line['line'][0]['name']). "', 'outside_of_grid'); ?". ">";
				$slots[$line['line'][0]['name']] = $line['line'][0];
				$slots[$line['line'][0]['name']]['ord'] = ++$ord;
			
				$html .= "\n". '</div>';
			
			//A break with no slot
			} else {
				//Add any custom html
				if (!empty($line['line'][0]['html'])) {
					$html .= "\n". $line['line'][0]['html'];
				}
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
					$line['line'][$i - 1]['class'] = rationalNumberGridClass($line['line'][$i - 1]['width'], $gCols);
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
					array_splice($array_keys, 0, 0, array($last));
				}
			
				if ($level == 0 && !$gridOpen) {
					//Open the grid if it is not already open
					$html .= "\n". '<div class="'. htmlspecialchars($gridCSSClass). '">';
					$html .= "\n\t". '<div class="container container_'. $gCols. '">';
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
						$line['line'][$i]['lines'] = array();
						
						$line['height'] = max(
							$line['height'],
							zenario_grid_maker::generateHTMLR($html, $line['line'][$i]['lines'], $data, $line['line'][$i], $slots, $ord, $gColsNested, $level + 1)
						);
				
					//Draw a slot
					} elseif (!empty($line['line'][$i]['slot']) && !empty($line['line'][$i]['name'])) {
						$html .= ' slot ';
						
						switch ($line['line'][$i]['height'] ?? false) {
							case 'xxlarge':
								$height = 5;
								$html .= 'xxlarge_slot';
								break;
							case 'xlarge':
								$height = 4;
								$html .= 'xlarge_slot';
								break;
							case 'large':
								$height = 3;
								$html .= 'large_slot';
								break;
							case 'medium':
								$height = 2;
								$html .= 'medium_slot';
								break;
							default:
								$height = 1;
								$html .= 'small_slot';
						}
						
						$line['height'] = max(
							$line['height'],
							$height
						);
						
						$html .= ' '. HTMLId($line['line'][$i]['name']). '">';
				
						$html .= $nl. "\t<". "?php slot('". HTMLId($line['line'][$i]['name']). "', 'grid'); ?". ">";
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

public static function updateMetaInfoInDB(&$data, &$slots, &$layout) {
					
	//Update the information on the grid in the layouts table
	updateRow('layouts',
		array(
			'cols' => ($data['cols'] ?? false),
			'min_width' => ($data['minWidth'] ?? false),
			'max_width' => ($data['maxWidth'] ?? false),
			'fluid' => ($data['fluid'] ?? false),
			'responsive' => ($data['responsive'] ?? false)
		),
		$layout['layout_id']);

	//Remove any deleted slots from the database
	$sql = "
		DELETE FROM ". DB_NAME_PREFIX. "template_slot_link
		WHERE family_name = '". sqlEscape($layout['family_name']). "'
		  AND file_base_name = '". sqlEscape($layout['file_base_name']). "'";
	
	if (!empty($slots)) {
		$sql .= "
		  AND slot_name NOT IN (". inEscape(array_keys($slots)). ")";
	}
	sqlUpdate($sql);
	
	//Make sure all of the slots are recorded in the database
	foreach ($slots as $slotName => $slot) {
		setRow(
			'template_slot_link',
			array(
				'ord' => $slot['ord'],
				'cols' => $slot['width'],
				'small_screens' => ifNull($slot['small'] ?? false, 'show')
			),
			array(
				'family_name' => $layout['family_name'],
				'file_base_name' => $layout['file_base_name'],
				'slot_name' => $slotName)
		);
	}
}

public static function generateThumbnail(&$data, $highlightSlot, $requestedWidth, $requestedHeight) {
	require_once CMS_ROOT. 'zenario/libraries/lgpl/wideimage/WideImage.php';
	
	$html = '';
	$lines = array();
	$slots = array();
	$rows = zenario_grid_maker::generateHTMLR($html, $lines, $data, $data, $slots, $ord, $data['gCols'], 0);
	unset($html);
	
	
	$cols = (int) $data['gCols'];
	$colSize = 8;
	$marginSize = 2;
	$outerMarginSize = 2;
	$rowSize = 3;
	$vMarginSize = 2;

	$width = $cols * $colSize + ($cols-1) * $marginSize + 2 * $outerMarginSize;
	$height = $rows * $rowSize + ($rows-1) * $vMarginSize + 2 * $outerMarginSize;
	
	$startX = $outerMarginSize;
	$startY = $outerMarginSize;

	$img = WideImage::createTrueColorImage($width, $height);
	
	$bgColour = $img->getExactColor(0xe4, 0xaa, 0xb0);
	
	if ($highlightSlot) {
		$slotColour = $img->getExactColor(0xe8, 0xdc, 0xdd);
		$highlightColour = $img->getExactColor(0xff, 0xff, 0x80);
	} else {
		$slotColour = $img->getExactColor(0xf7, 0xef, 0xef);
		$highlightColour = false;
	}
	
	imagefilledrectangle($img->getHandle(), 0, 0, $width, $height, $bgColour);
	
	
	
	if (!empty($data['cells']) && is_array($data['cells'])) {
		zenario_grid_maker::generateThumbnailR(
			$img, $lines,
			$startX, $startY,
			$colSize, $marginSize, $rowSize, $vMarginSize,
			$slotColour, $highlightSlot, $highlightColour
		);
	}
	
	if (($requestedWidth = (int) $requestedWidth)
	 && ($requestedHeight = (int) $requestedHeight)) {
		$new = $img->resize($requestedWidth, $requestedHeight, 'fill');
		$new->output('png');
	} else {
		$img->output('png');
	}
}


public static function generateThumbnailR(
	&$img, &$lines,
	$startX, $startY,
	$colSize, $marginSize, $rowSize, $vMarginSize,
	$slotColour, $highlightSlot, $highlightColour
) {
	
	$y = $startY;
	
	if (!empty($lines)) {
		foreach ($lines as &$line) {
			
			$x = $startX;
			$height = (int) $line['height'];
			$y2 = $y + $height * $rowSize + ($height - 1) * $vMarginSize;
			
			if (!empty($line['line'])) {
				foreach ($line['line'] as &$cell) {
					$x2 = $x + $colSize * $cell['width'] + $marginSize * ($cell['width'] - 1);
					
					//print_r(array($x, $y, $x2, $y2));
					
					if (!empty($cell['name'])) {
						
						switch ($cell['height'] ?? false) {
							case 'xxlarge':
								$height = 5;
								break;
							case 'xlarge':
								$height = 4;
								break;
							case 'large':
								$height = 3;
								break;
							case 'medium':
								$height = 2;
								break;
							default:
								$height = 1;
						}
						
						$y3 = $y + $height * $rowSize + ($height - 1) * $vMarginSize;
						
						if ($highlightSlot == $cell['name']) {
							imagefilledrectangle($img->getHandle(), $x, $y, $x2-1, $y3-1, $highlightColour);
						} else {
							imagefilledrectangle($img->getHandle(), $x, $y, $x2-1, $y3-1, $slotColour);
						}
					
					} elseif (!empty($cell['lines'])) {
						zenario_grid_maker::generateThumbnailR(
							$img, $cell['lines'],
							$x, $y,
							$colSize, $marginSize, $rowSize, $vMarginSize,
							$slotColour, $highlightSlot, $highlightColour
						);
					}
					
					$x = $x2 + $marginSize;
				}
			}
			
			$y = $y2 + $vMarginSize;
		}
	}
}



public static function addCode(&$html, &$data) {
	$html .=
		'<'. '?php //data:'.
			strtr(base64_encode(
				gzcompress(json_encode($data))
			), ' +/=', '~-_,').
		'//v2// ?'. '>';
}

public static function readLayoutCode($layoutId, $justCheck = false, $quickCheck = false) {

	if (($layout = getTemplateDetails($layoutId))
	 && (is_file($path = CMS_ROOT. zenarioTemplatePath($layout['family_name'], $layout['file_base_name'])))
	 && ($html = @file_get_contents($path))
	 && ($data = zenario_grid_maker::readCode($html, $justCheck, $quickCheck))) {
		return $data;
	}
	
	return false;
}

public static function readCode(&$html, $justCheck = false, $quickCheck = false) {
	$parts = explode('<?'. 'php //data:', $html, 2);
	
	if (!empty($parts[1])) {
		$parts = explode('//', $parts[1], 3);
		
		//Don't allow the quick-check option for old versions of Grid Maker
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

//This old version of hash64, before we changed it.
//I'm keeping it here because I don't want to break everyone's images.
public static function oldHash64($text, $len = 28) {
	return substr(strtr(base64_encode(sha1($text, true)), ' +/=', '~-_,'), 0, $len);
}

public static function addChecksum(&$html) {
	$html .= '<?'. 'php //checksum:'. zenario_grid_maker::oldHash64(trim($html)). '// ?'. '>';
}

public static function checkChecksum(&$html) {
	$parts = explode('<?'. 'php //checksum:', $html, 2);
	
	if (empty($parts[1])) {
		return false;
	}
	
	$parts[1] = str_replace(array('//', '?'. '>', ' '), '', $parts[1]);
	
	return zenario_grid_maker::oldHash64(trim($parts[0])) == $parts[1];
}


public static function validateData(&$data) {
	if ($data && is_array($data)) {
		
		if (!empty($data['bootstrap'])) {
			return false;
		}
		
		if ((($data['fluid'] = !empty($data['fluid'])) || true)
		 && (($data['mirror'] = !empty($data['mirror'])) || true)
		 && (($data['responsive'] = !empty($data['responsive'])) || true)
		 && (($data['gCols'] = (int) ($data['cols'] ?? false)))
		 && (($data['gColWidth'] = (int) ($data['colWidth'] ?? false)) || $data['fluid'])
		 && (($data['gGutter'] = $data['fluid']? (float) ($data['gutterFlu'] ?? false) : (int) ($data['gutter'] ?? false)) || true)
		 && (($data['gGutterLeftEdge'] = $data['fluid']? (float) ($data['gutterLeftEdgeFlu'] ?? false) : (int) ($data['gutterLeftEdge'] ?? false)) || true)
		 && (($data['gGutterRightEdge'] = $data['fluid']? (float) ($data['gutterRightEdgeFlu'] ?? false) : (int) ($data['gutterRightEdge'] ?? false)) || true)
		 && ($data['minWidth'] = $data['fluid']? (int) ($data['minWidth'] ?? false) : $data['gCols'] * $data['gColWidth'] + $data['gGutterLeftEdge'] + $data['gGutterRightEdge'] + ($data['gCols'] - 1) * $data['gGutter'])
		 && ($data['maxWidth'] = $data['fluid']? (int) ($data['maxWidth'] ?? false) : $data['minWidth'])
		 && ($data['gCols'] >= 1)
		) {
			return true;
		}
	}
	
	return false;
}


public static function calcTemplateFamilyName($data) {
	
	//New logic
	return 'grid_templates';
	
	//Old logic
	return $data['gCols']. '-col-grid';
}

//Given a template file, strip out anything but the slots and return the size.
//Comparing sizes before and after can be used as a rough estimate to whether slots have been added or removed.
public static function compactedSize($html) {
	//Strip out all spaces, comments, PHP code, and any HTML that's not a <div> tag, then return the string's length.
		//Also remove the word "responsive" to prevent a bug where if you check/uncheck the "responsive" keyword on
		//enough slots that could trigger the warning about slots being added/removed.
	return strlen(preg_replace('/\s+/', '', str_replace(' responsive ', ' ', strip_tags($html, '<div>'))));
}

public static function generateDirectory(&$data, &$slots, $writeToFS = false, $preview = false, $tFileBaseName = 'layout') {
	$html = $css = '';
	
	if ($writeToFS) {
		zenario_grid_maker::$mode = 'fs';
	} else {
		zenario_grid_maker::$mode = 'zip';
	}


	$status = array();
	
	
	$tFamilyName = zenario_grid_maker::calcTemplateFamilyName($data);
	$tFilePath = $tFamilyName. '/'. $tFileBaseName. '.tpl.php';
	$cssFilePath = $tFamilyName. '/'. $tFileBaseName. '.css';
	
	if (zenario_grid_maker::$mode == 'fs') {
		zenario_grid_maker::$tempDir = CMS_ROOT. zenarioTemplatePath();
		zenario_grid_maker::$tempDirR = zenarioTemplatePath();
		if (!is_writable(zenario_grid_maker::$tempDir. $tFamilyName)) {
			return new zenario_error('_ZENARIO_GRID_MAKER_ERROR_001');
		}
		
		$status['template_file_modified'] = false;
		$status['template_file_smaller'] = false;
		$status['template_file_larger'] = false;
		$status['template_file_identical'] = false;
		$status['template_file_path'] = zenario_grid_maker::$tempDirR. $tFilePath;
		$status['template_file_exists'] = is_file($status['template_file_path']);
		
		$status['template_css_file_identical'] = false;
		$status['template_css_file_path'] = zenario_grid_maker::$tempDirR. $cssFilePath;
		$status['template_css_file_exists'] = is_file($status['template_css_file_path']);
		
		$status['family_name'] = $tFamilyName;
		$status['file_base_name'] = $tFileBaseName;
	
	} elseif (zenario_grid_maker::$mode == 'zip') {
		if (!class_exists('ZipArchive')) {
			return new zenario_error('_ZENARIO_GRID_MAKER_ERROR_004');
		}
		
		zenario_grid_maker::$out = new ZipArchive();

		//Make a new directory to construct the output in
		if (($dirOut = createRandomDir(15, 'downloads', $onlyForCurrentVisitor = setting('restrict_downloads_by_ip')))
		 && (is_writable($dirOut))
		 && (true === zenario_grid_maker::$out->open($filepath = $dirOut. $tFamilyName. '.zip', ZIPARCHIVE::CREATE))) {
		} else {
			return new zenario_error('_ZENARIO_GRID_MAKER_ERROR_005');
		}
	
	} else {
		return false;
	}
	
	if (!empty($status['template_file_exists'])) {
		zenario_grid_maker::generateHTML($html, $data, $slots);
		$oldHtml = file_get_contents(zenario_grid_maker::$tempDir. $tFilePath);
		
		$status['template_file_identical'] = trim($html) == trim($oldHtml);
		
		$diff =
			zenario_grid_maker::compactedSize($html)
		  - zenario_grid_maker::compactedSize($oldHtml);
		
		$status['template_file_smaller'] = $diff > 35;
		$status['template_file_larger'] = $diff < -35;
		
		if (!$status['template_file_identical']) {
			$status['template_file_modified'] = !zenario_grid_maker::checkChecksum($oldHtml);
		}
		
		zenario_grid_maker::generateCSS($css, $data);
		$oldCSS = file_get_contents(zenario_grid_maker::$tempDir. $cssFilePath);
		
		$status['template_css_file_identical'] = trim($css) == trim($oldCSS);
	}
	
	if (!$preview) {
		
		//If grid maker is writing to the directory first,
		//check permissions and come up with a friendlier error
		//message if they are not correct
		if (zenario_grid_maker::$mode == 'fs') {
			
			$tFileIsWritable = 
				!file_exists(zenario_grid_maker::$tempDir. $tFilePath)
			 || is_writable(zenario_grid_maker::$tempDir. $tFilePath);
			$cssFileIsWritable = 
				!file_exists(zenario_grid_maker::$tempDir. $cssFilePath)
			 || is_writable(zenario_grid_maker::$tempDir. $cssFilePath);
			
			if (!$tFileIsWritable || !$cssFileIsWritable) {
				$tDir = dirname(zenario_grid_maker::$tempDir. $tFilePath);
				$tFilename = basename(zenario_grid_maker::$tempDir. $tFilePath);
				$cssFilename = basename(zenario_grid_maker::$tempDir. $cssFilePath);
			
				$msg = adminPhrase('Sorry, the CMS could not write to the following files in the [[dir]] directory:', array('dir' => htmlspecialchars($tDir)));
				
				$msg .= '<br/><ul><li>'. htmlspecialchars($tFilename). '</li><li>'. htmlspecialchars($cssFilename). '</li></ul>';
				
				$msg .= adminPhrase('Please ensure that the files are writeable by the web server and try again.');
				
				return new zenario_error($msg);
			}
		}
		
		
		try {
			zenario_grid_maker::mkdir($tFamilyName);
			
			//Always add or overwrite the Template file, unless it was identical
			if (empty($status['template_file_identical'])) {
				if (!$html) {
					zenario_grid_maker::generateHTML($html, $data, $slots);
				}
				
				zenario_grid_maker::put($tFilePath, $html, true);
			}
			unset($html);
			
			//Always add or overwrite the Grid CSS, overwriting any changes
			if (!$css) {
				zenario_grid_maker::generateCSS($css, $data);
			}
			
			zenario_grid_maker::put($cssFilePath, $css, false);
			unset($css);
		
		} catch (Exception $e) {
			if (zenario_grid_maker::$mode == 'zip') {
				zenario_grid_maker::$out->close();
			}
			
			return new zenario_error($e->getMessage());
		}
	}
	
	
	if (zenario_grid_maker::$mode == 'fs') {
		return $status;
	
	} elseif (zenario_grid_maker::$mode == 'zip') {
		zenario_grid_maker::$out->close();
		
		//Return the path to the zip, so it can be offered for download
		return $filepath;
	}
}

protected static $mode;
protected static $out;
protected static $tempDir;
protected static $tempDirR;



//I don't think the function below is used...
////Copy a directory and its contents
////Note that $from needs to be the full path, but $to is relative to the source/target directory
//protected static function copyDirR($from, $to, $excludeFilter = false, $limit = 9) {
//	
//	if (!--$limit) {
//		return;
//	}
//	
//	zenario_grid_maker::mkdir($to);
//	
//	foreach (scandir($from) as $file) {
//		if ($excludeFilter && strpos($file, $excludeFilter) !== false) {
//			continue;
//		}
//		
//		if (substr($file, 0, 1) != '.') {
//			if (is_dir($from. '/'. $file)) {
//				zenario_grid_maker::copyDirR($from. '/'. $file, $to. '/'. $file, $limit);
//			} else {
//				zenario_grid_maker::copy($from. '/'. $file, $to. '/'. $file, $limit);
//			}
//		}
//	}
//}

//Make a new directory in the target directory
protected static function mkdir($path) {
	if (zenario_grid_maker::$mode == 'fs') {
		if (!is_dir($dir = zenario_grid_maker::$tempDir. $path)) {
			if (!@mkdir($dir)) {
				throw new Exception('_ZENARIO_GRID_MAKER_ERROR_002');
			} else {
				@chmod($dir, 0777);
			}
		}
		updateDataRevisionNumber();
	
	} elseif (zenario_grid_maker::$mode == 'zip') {
		zenario_grid_maker::$out->addEmptyDir($path);
	}
}

//Create a new file in the target directory
protected static function put($path, $contents, $isExecutable) {
	if (zenario_grid_maker::$mode == 'fs') {
		if (!@file_put_contents(zenario_grid_maker::$tempDir. $path, $contents)) {
			throw new Exception('_ZENARIO_GRID_MAKER_ERROR_003');
		}
		@chmod(zenario_grid_maker::$tempDir. $path, $isExecutable? 0664 : 0666);
		updateDataRevisionNumber();
	
	} elseif (zenario_grid_maker::$mode == 'zip') {
		zenario_grid_maker::$out->addFromString($path, $contents);
	}
}

//Copy a file
//Note that $from needs to be the full path, but $to is relative to the source/target directory
protected static function copy($from, $to, $isExecutable) {
	if (zenario_grid_maker::$mode == 'fs') {
		copy($from, zenario_grid_maker::$tempDir. $to);
		@chmod(zenario_grid_maker::$tempDir. $to, $isExecutable? 0664 : 0666);
		updateDataRevisionNumber();
	
	} elseif (zenario_grid_maker::$mode == 'zip') {
		zenario_grid_maker::$out->addFile($from, $to);
	}
}


  }