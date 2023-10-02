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
if (!defined('NOT_ACCESSED_DIRECTLY')) exit('This file may not be directly accessed');


$width = $tab . '/' . $fieldPrefix . 'width';
$height = $tab . '/' . $fieldPrefix . 'height';
$retina = $tab . '/' . $fieldPrefix . 'retina';

if ($hasCanvas) {
	$canvas = $tab . '/' . $fieldPrefix . 'canvas';
	$fields[$canvas]['hidden'] = $hidden;
}

if (!$hidden && empty($values[$canvas])) {
	//Watch our for an issue where the canvas value is set to a blank value when it was previous hidden and has just been revealed.
	//The browser would normally deal with this by simply selecting the first option in the list, however we need to apply this
	//logic earlier to not mess up the formatting logic below!
	if (!empty($fields[$canvas]['values'])) {
		$values[$canvas] = array_keys($fields[$canvas]['values'])[0];
	}
}


$fields[$width]['hidden'] = $hidden
	|| ($hasCanvas && !\ze::in($values[$canvas], 'fixed_width', 'fixed_width_and_height', 'crop_and_zoom', 'resize_and_crop'));
$fields[$height]['hidden'] = $hidden
	|| ($hasCanvas && !\ze::in($values[$canvas], 'fixed_height', 'fixed_width_and_height', 'crop_and_zoom', 'resize_and_crop'));

if (isset($fields[$retina])) {
	$fields[$retina]['hidden'] = $hidden || !$fields[$width]['hidden'] || !$fields[$height]['hidden'];
}

$offset = $tab . '/' . $fieldPrefix . 'offset';
if (isset($fields[$offset])) {
	$fields[$offset]['hidden'] = $hidden
		|| ($hasCanvas && ($values[$canvas] != 'resize_and_crop'));
}

if (!$fields[$width]['hidden'] && !$fields[$height]['hidden']) {
	$fields[$width]['label'] = \ze\admin::phrase($sameLineLabel);
	$fields[$width]['placeholder'] = \ze\admin::phrase('width');
	$fields[$width]['post_field_html'] = '&nbsp;&nbsp;×&nbsp;';
	
	$fields[$height]['label'] = '';
	$fields[$height]['placeholder'] = \ze\admin::phrase('height');
	$fields[$height]['post_field_html'] = '&nbsp;pixels';
	$fields[$height]['same_row'] = true;
} else {
	$fields[$width]['label'] = \ze\admin::phrase('Width:');
	$fields[$width]['placeholder'] = '';
	$fields[$width]['post_field_html'] = '&nbsp;pixels';
	
	$fields[$height]['label'] = 'Height:';
	$fields[$height]['placeholder'] = '';
	$fields[$height]['post_field_html'] = '&nbsp;pixels';
	$fields[$height]['same_row'] = false;
}