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



$html = '';
$name = $attributes['name'] ?? '';
$type = $attributes['type'] ?? 'text';
$readonly = \ze\ring::engToBoolean($attributes['readonly'] ?? false);


if (!$i) {
	//Check for a list of values, if one has been set
	$lov = false;
	$this->zAPIFrameworkLOV($type, $attributes, $lov);

	//Check that this is a supported type of field
	switch ($type) {
		case 'select':
		case 'checkbox':
		case 'date':
		case 'email':
		case 'file':
		case 'hidden':
		case 'password':
		case 'select':
		case 'submit':
		case 'text':
		case 'textarea':
		case 'toggle':
			break;
		default:
			return $html;
	}
}


//Attempt to get the value of this field
$value = '';

$getValue = null;
$postValue = null;

if ($name) {
	$getValue = $_GET[$name] ?? null;
	
	if (isset($_POST[$name])
	 && $this->checkPostIsMine()) {
		$postValue = $_POST[$name];
	}
}

//Check the POST
if ($type != 'submit' && $postValue !== null) {
	$value = $postValue;

//Check the GET
} elseif ($type != 'submit' && $getValue !== null) {
	$value = $getValue;

//Get the value from a source module
} elseif (!empty($attributes['source_module']) && !empty($attributes['source_method']) && \ze\module::inc($attributes['source_module'])
	   && !($type == 'checkbox' || $type == 'radio' || $type == 'select' || $type == 'toggle')) {
	$value = call_user_func([$attributes['source_module'], $attributes['source_method']], $this->currentTwigVars, $attributes);

	//Disallow caching for programatically generated values
	ze::$slotContents[$this->slotName]->disallowCaching();

//Check for a value set as an attribute
} elseif (isset($attributes['value']) && ($type == 'button' || $type == 'submit' || $postValue === null)) {
	$value = ($type == 'checkbox' || $type == 'toggle')? $attributes['value'] : $this->phrase($attributes['value']);

//Check for a value in the merge fields
} else
if (isset($this->currentTwigVars[$name])
 && !is_array($this->currentTwigVars[$name])
 && !is_bool($this->currentTwigVars[$name])
 && !is_object($this->currentTwigVars[$name])) {
	$value = $this->currentTwigVars[$name];
}




//There's no need to re-upload a file that's already been uploaded
if ($type == 'file') {
	if ($value && preg_match('@^private/uploads/[\w\-]+/[\w\.-]+\.upload$@', $value) && file_exists(CMS_ROOT. $value)) {
		$html .= htmlspecialchars(substr(basename($value), 0, -7));
		$html .= '
			<input type="hidden" name="'. htmlspecialchars($name). '" value="'. htmlspecialchars($value). '">'; 
		return;
	} else {
		$value = null;
	}
}

//Checkboxes/Radiogroups
if ($type == 'checkbox' || $type == 'radio') {
	if ($lov) {
		$value = $value == $saveVal;

	} elseif ($type == 'checkbox') {
		$value = ze\ring::engToBoolean($value);
	}
	
	if ($value) {
		$attributes['checked'] = 'checked';
	} else {
		unset($attributes['checked']);
	}

} elseif ($type == 'date' && !$name) {
	$type = 'text';

} elseif ($type == 'date' && !$readonly) {

	$attributes['id'] = (($attributes['id'] ?? false) ?: $name);

	$values = explode('-', $value, 3);
	if (!(int) ($values[0] ?? false) || !(int) ($values[1] ?? false) || !(int) ($values[2] ?? false)) {
		if ($value === 'TODAY') {
			$value = date('Y-m-d');
			$values = explode('-', $value, 3);
		} else {
			$value = '';
			$values = ['', '', ''];
		}
	}

	$j = 0;
	foreach ([31 => 1, 12 => 1, (int) date('Y') + 10 => (int) date('Y') - 10] as $to => $from) {
		$v = (int) $values[3 - ++$j];
		$html .= '
			<select class="jquery_datepicker" name="'. htmlspecialchars($name). '__'. $j. '" id="'. htmlspecialchars($attributes['id']). '__'. $j. '">
				<option></option>';
	
		for ($k = $from; $k <= $to; ++$k) {
			$html .= '
				<option'. ($v == $k? ' selected="selected"' : ''). '>'. str_pad($k, 2, '0', STR_PAD_LEFT). '</option>';
		}
	
		$html .= '
			</select>';
	}

	$attributes['class'] = 'jquery_datepicker '. ($attributes['class'] ?? false);
	$attributes['onkeyup'] = 'zenario.dateFieldKeyUp(this, event); '. ($attributes['onkeyup'] ?? false);
}


if (!$readonly && (!$lov || $i || $type == 'select' || $type == 'text' || $type == 'email')) {
				
	//Most attributes that are part of the HTML spec we'll pass on directly
	$allowedAtt = [
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
		'title' => true,
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
		'oninput' => true,
	
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
		'step' => true
	];
	
		//We need to remove the 'required' and 'pattern' HTML 5 attributes from our flexible forms for now,
		//as they are causing conflicts with our own tech.
		//At some point we'll need to add them back in, ans adjust our tech to use attributes with different names.


	//Open the field's tag
	if ($type == 'date') {
		$html .= '<input type="hidden" name="'. htmlspecialchars($name). '" id="'. htmlspecialchars($attributes['id']). '__0" value="'. htmlspecialchars($value). '"/>
			<input type="text" readonly="readonly"';
	
		unset($allowedAtt['name']);
		unset($allowedAtt['type']);

	} elseif ($type == 'select') {
		$html .= '<select';

		unset($allowedAtt['type']);

	} elseif ($type == 'textarea') {
		$html .= '<textarea';

		unset($allowedAtt['type']);

	} elseif ($type == 'toggle') {
		if ($i) {
			//Toggles need their value/class name set differently depending on whether they are open, to try and mirror radiogroups as close as is possible
			$html .= '<input type="submit" value="';
			$attributes['class'] = ($attributes['class'] ?? false). ' toggle';
		
			if ($value == $saveVal) {
				$html .= '&bull;';
				$attributes['class'] .= ' toggle_selected';
			}
		
			$html .= '"';
		
		} else {
			$html .= '<input type="hidden"';
		}
		unset($allowedAtt['type']);

	} else {
		$html .= '<input';
	}
	
	if ($type == 'text' && $lov) {
		$html .= ' data-autocomplete_list="' . htmlspecialchars(json_encode(array_values($lov))) . '"';
		$attributes['class'] = ($attributes['class'] ?? false). ' autocomplete';
	}
	
	foreach ($attributes as $att => $attVal) {
		if (isset($allowedAtt[$att]) || substr($att, 0, 5) == 'data-') {
			$html .= ' '. $att. '="'. htmlspecialchars($attVal). '"';
		}
	}
	

	//Add the value (which happens slightly differently for select lists and textareas, and has already been done for toggles)
	if ($type == 'date' || ($type == 'toggle' && $i)) {
		$html .= '/>';

	} elseif ($type == 'select') {
		$html .= '>';
		
		if (!empty($attributes['null'])) {
			$html .= '
				<option value="">'.
					htmlspecialchars($this->phrase($attributes['null'])).
				'</option>'; 
		}

	} elseif ($type == 'textarea') {
		$html .= '>'. htmlspecialchars($value). '</textarea>';
	
	} elseif ($type == 'checkbox' || $type == 'radio') {
		if ($i) {
			$html .= ' value="'. htmlspecialchars($saveVal). '"/>';
		
		} else {
			//Ensure single-checkboxes always have a value of 1
			$html .= ' value="1"/>';
		}

	} else {
		$html .= ' value="'. htmlspecialchars($value). '"/>';
	}
}

//Draw each value from the LOV
if (!$i && is_array($lov)) {
	$i = 0;
	foreach ($lov as $saveVal => $dispVal) {
		if ($type == 'select' && !$readonly) {
			$html .= '
				<option value="'. htmlspecialchars($saveVal). '"'. ($saveVal == $value? ' selected="selected"' : ''). '>'.
					htmlspecialchars($dispVal).
				'</option>'; 
		} elseif ($type != 'text') {
			
			$thisAttributes = $attributes;

			//LOVs only: Add the index to the name and id as needed to avoid clashes
			if ($type == 'checkbox' || $type == 'toggle') {
				$thisAttributes['name'] = $name. '__'. $i;
			}
			if (isset($attributes['id'])) {
				$thisAttributes['id'] = $thisAttributes['id']. '__'. $i;
			}
			
			$html .= $this->zAPIFrameworkField($thisAttributes, ++$i, $lov, $readonly, $saveVal, $dispVal);
		}
	}
	$i = false;

	//Checkboxes/Toggles will need a hidden field to could how many there were
	if ($lov && ($type == 'checkbox' || $type == 'toggle')) {
		$html .= '
			<input type="hidden" name="'. htmlspecialchars($name). '__n" value="'. $i. '"/>'; 
	}
}

//Close the select tag
if ($type == 'select') {
	$html .= '
		</select>';
}


$showLabel = false;
$addHiddenField = $readonly;

//LOVs need labels displaying for each value.
//In read only mode, these should only be displayed if they are selected
if ($i) {
	switch ($type) {
		case 'checkbox':
		case 'radio':
			$showLabel = !$readonly || \ze\ring::engToBoolean($attributes['checked'] ?? false);
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
			$showLabel = \ze\ring::engToBoolean($attributes['checked'] ?? false);
			break;
		case 'date':
		case 'email':
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
	$html .= '
		<label'. (!empty($attributes['id'])? ' for="'. htmlspecialchars($attributes['id'] ?? false). '"' : ''). '>';

	if ($type == 'password') {
		$html .= preg_replace('/./', '*', $value);
	} elseif ($type == 'textarea') {
		$html .= nl2br(htmlspecialchars($value));
	} elseif ($type == 'date' && $value && !preg_replace('/\d\d\d\d-\d\d-\d\d/', '', $value)) {
		$html .= htmlspecialchars(\ze\date::format($value));
	} else {
		$html .= htmlspecialchars(($dispVal ?: ($saveVal ?: $value)));
	}
	$html .= '</label>';
}

if ($addHiddenField) {
	$html .= '
		<input type="hidden" name="'. htmlspecialchars($name). '" value="'. htmlspecialchars($value). '">';
}

if (!$i && $name) {
	$this->frameworkFields[$name] = $attributes;
}


return $html;