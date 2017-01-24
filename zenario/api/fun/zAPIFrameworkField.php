<?php
/*
 * Copyright (c) 2017, Tribal Limited
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

//Attempt to get the value of this field
$value = '';

$postValue = null;
if (isset($_POST[arrayKey($attributes, 'name')]) && $this->checkPostIsMine()) {
	$postValue = $_POST[arrayKey($attributes, 'name')];
}

//Ensure single-checkboxes always have a value of 1
if (!$i && $type == 'checkbox') {
	$value = 1;

//Check the POST
} elseif ($type != 'submit' && $type != 'checkbox' && $type != 'radio' && $postValue !== null) {
	$value = $postValue;

//Check the GET
} elseif ($type != 'submit' && $type != 'checkbox' && $type != 'radio' && isset($_GET[arrayKey($attributes, 'name')])) {
	$value = $_GET[arrayKey($attributes, 'name')];

//Check for a value from a LOV
} elseif ($i && $type != 'toggle') {
	$value = $saveVal;

//Get the value from a source module
} elseif (!empty($attributes['source_module']) && !empty($attributes['source_method']) && inc($attributes['source_module'])
	   && !($type == 'checkbox' || $type == 'radio' || $type == 'select' || $type == 'toggle')) {
	$value = call_user_func(array($attributes['source_module'], $attributes['source_method']), $mergeFieldsRow, $attributes);
	
	//Disallow caching for programatically generated values
	cms_core::$slotContents[$this->slotName]['disallow_caching'] = true;

//Check for a value set as an attribute
} elseif (isset($attributes['value']) && ($type == 'button' || $type == 'submit' || $postValue === null)) {
	$value = ($type == 'checkbox' || $type == 'toggle')? $attributes['value'] : $this->phrase($attributes['value']);

//Check for a value in the merge fields
} elseif (isset($mergeFieldsRow[arrayKey($attributes, 'name')])) {
	$value = $mergeFieldsRow[arrayKey($attributes, 'name')];
}


//If the type wasn't set, just look up the value and do nothing else
if ($type === false) {
	return;
}


//There's no need to re-upload a file that's already been uploaded
if ($type == 'file') {
	if ($value && substr($value, 0, 14) == 'cache/uploads/' && file_exists(CMS_ROOT. $value)) {
		echo htmlspecialchars(substr(basename($value), 0, -7));
		echo '
			<input type="hidden" name="', htmlspecialchars(arrayKey($attributes, 'name')), '" value="', htmlspecialchars($value), '">'; 
		return;
	} else {
		$value = null;
	}
}

//LOVs only: Add the $rowNum to the name and id as needed to avoid clashes
if ($i) {
	if ($type == 'checkbox' || $type == 'toggle') {
		$attributes['name'] = arrayKey($attributes, 'name'). '__'. $i;
	}
	if (isset($attributes['id'])) {
		$attributes['id'] = $attributes['id']. '__'. $i;
	}
}

//Checkboxes/Radiogroups only: If the form has already been submitted, overwrite the "checked" attribute depending on whether the checkbox/radiogroup was chosen
if ($type == 'checkbox' && $this->checkPostIsMine()) {
	if (isset($_POST[arrayKey($attributes, 'name')])) {
		$attributes['checked'] = 'checked';
	} else {
		unset($attributes['checked']);
	}

} elseif ($type == 'radio' && $this->checkPostIsMine()) {
	if ($postValue == $saveVal) {
		$attributes['checked'] = 'checked';
	} else {
		unset($attributes['checked']);
	}

} elseif ($type == 'date' && !arrayKey($attributes, 'name')) {
	$type = 'text';

} elseif ($type == 'date' && !$readonly) {
	
	$attributes['id'] = ifNull(arrayKey($attributes, 'id'), $attributes['name']);
	
	$values = explode('-', $value, 3);
	if (!(int) arrayKey($values, 0) || !(int) arrayKey($values, 1) || !(int) arrayKey($values, 2)) {
		if ($value === 'TODAY') {
			$value = date('Y-m-d');
			$values = explode('-', $value, 3);
		} else {
			$value = '';
			$values = array('', '', '');
		}
	}
	
	$j = 0;
	foreach (array(31 => 1, 12 => 1, (int) date('Y') + 10 => (int) date('Y') - 10) as $to => $from) {
		$v = (int) $values[3 - ++$j];
		echo '
			<select class="jquery_datepicker" name="', htmlspecialchars($attributes['name']), '__', $j, '" id="', htmlspecialchars($attributes['id']), '__', $j, '">
				<option></option>';
		
		for ($k = $from; $k <= $to; ++$k) {
			echo '
				<option', $v == $k? ' selected="selected"' : '', '>', str_pad($k, 2, '0', STR_PAD_LEFT), '</option>';
		}
		
		echo '
			</select>';
	}
	
	$attributes['class'] = 'jquery_datepicker '. arrayKey($attributes, 'class');
	$attributes['onkeyup'] = 'zenario.dateFieldKeyUp(this, event); '. arrayKey($attributes, 'onkeyup');
}


if (!$readonly) {
					
	//Most attributes that are part of the HTML spec we'll pass on directly
	$allowedAtt = array(
		'id' => true,
		'name' => true,
		'size' => true,
		'type' => true,
		'maxlength' => true,
		'accesskey' => true,
		'checked' => true,
		'class' => true,
		'cols' => true,
		'dir' => true,
		'rows' => true,
		'style' => true,
		'tabindex' => true,
		'title', true.
		'onblur' => true,
		'onchange' => true,
		'onclick' => true,
		'ondblclick' => true,
		'onfocus' => true,
		'onmousedown' => true,
		'onmousemove' => true,
		'onmouseout' => true,
		'onmouseover' => true,
		'onmouseup' => true,
		'onkeydown' => true,
		'onkeypress' => true,
		'onkeyup' => true,
		'onselect' => true,
		
		//New HTML 5 attributes
		'autocomplete' => true,
		'autofocus' => true,
		'list' => true,
		'max' => true,
		'min' => true,
		'multiple' => true,
		//'pattern' => true,
		'placeholder' => true,
		//'required' => true,
		'step' => true);
		
		//We need to remove the 'required' and 'pattern' HTML 5 attributes from our flexible forms for now,
		//as they are causing conflicts with our own tech.
		//At some point we'll need to add them back in, ans adjust our tech to use attributes with different names.
	
	
	//Open the field's tag
	if ($type == 'date') {
		echo '<input type="hidden" name="', htmlspecialchars($attributes['name']), '" id="', htmlspecialchars($attributes['id']), '__0" value="', htmlspecialchars($value), '"/>
			<input type="text" readonly="readonly"';
		
		unset($allowedAtt['name']);
		unset($allowedAtt['type']);
	
	} elseif ($type == 'select') {
		echo '<select';
	
		unset($allowedAtt['type']);
	
	} elseif ($type == 'textarea') {
		echo '<textarea';
	
		unset($allowedAtt['type']);
	
	} elseif ($type == 'toggle') {
		if ($i) {
			//Toggles need their value/class name set differently depending on whether they are open, to try and mirror radiogroups as close as is possible
			echo '<input type="submit" value="';
			$attributes['class'] = arrayKey($attributes, 'class'). ' toggle';
			
			if ($value == $saveVal) {
				echo '&bull;';
				$attributes['class'] .= ' toggle_selected';
			}
			
			echo '"';
			
		} else {
			echo '<input type="hidden"';
		}
		unset($allowedAtt['type']);
	
	} else {
		echo '<input';
	}
	
	foreach ($attributes as $att => $attVal) {
		if (isset($allowedAtt[$att])) {
			echo ' ', $att, '="', htmlspecialchars($attVal), '"';
		}
	}
	
	//Add the value (which happens slightly differently for select lists and textareas, and has already been done for toggles)
	if ($type == 'date' || ($type == 'toggle' && $i)) {
		echo '/>';
	
	} elseif ($type == 'select') {
		echo '>';
	
	} elseif ($type == 'textarea') {
		echo '>', htmlspecialchars($value), '</textarea>';
	
	} else {
		echo ' value="', htmlspecialchars($value), '"/>';
	}
}


$showLabel = false;
$addHiddenField = $readonly;

//LOVs need labels displaying for each value.
//In read only mode, these should only be displayed if they are selected
if ($i) {
	switch ($type) {
		case 'checkbox':
		case 'radio':
			$showLabel = !$readonly || engToBooleanArray($attributes, 'checked');
			break;
		case 'select':
			$showLabel = $readonly && $saveVal == $value;
			break;
		case 'toggle':
			$showLabel = !$readonly || $saveVal == $value;
			$addHiddenField = false;
			break;
	}

//In read-only mode, most fields need their value displaying.
} elseif ($readonly) {
	switch ($type) {
		case 'checkbox':
			$showLabel = engToBooleanArray($attributes, 'checked');
			break;
		case 'date':
		case 'file':
		case 'hidden':
		case 'password':
		case 'submit':
		case 'text':
		case 'textarea':
			$showLabel = true;
			break;
	}
}
		
if ($showLabel) {
	//Display the field's value
	echo '
		<label', !empty($attributes['id'])? ' for="'. htmlspecialchars(arrayKey($attributes, 'id')). '"' : '', '>';
	
	if ($type == 'password') {
		echo preg_replace('/./', '*', $value);
	} elseif ($type == 'textarea') {
		echo nl2br(htmlspecialchars($value));
	} elseif ($type == 'date' && $value && !preg_replace('/\d\d\d\d-\d\d-\d\d/', '', $value)) {
		echo htmlspecialchars(formatDateNicely($value, '_MEDIUM'));
	} else {
		echo htmlspecialchars(ifNull($dispVal, $saveVal, $value));
	}
	echo '</label>';
}

if ($addHiddenField) {
	echo '
		<input type="hidden" name="', htmlspecialchars(arrayKey($attributes, 'name')), '" value="', htmlspecialchars($value), '">';
}


return;