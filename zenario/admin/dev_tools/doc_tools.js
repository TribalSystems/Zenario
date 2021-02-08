/*
 * Copyright (c) 2021, Tribal Limited
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

window.docTools = function() {};

(function(
	window, docTools,
	undefined) {
		"use strict";

docTools.parseSchema = function(mode, schema, specificTag, linkToRef) {
	//console.log(mode, schema, specificTag, linkToRef);
	if (schema) {
		var panel;
		
		schema = JSON.parse(JSON.stringify(schema).replace(/\"html_description\"/g, '"description"'));
		
		//Create a few copies of the panel definition everywhere
			//I could have done this in YAML or PHP but that would cause a large page load with lots of repeated information.
			//Also, the definition is recursive, but the JSON schema parser crashes if you use infinite recursion. I'm solving
			//this problem by only using three recursions.
		if (mode == 'organizer_schema') {
			panel = schema.additionalProperties.properties.panels.additionalProperties;
			
			//Merge in a few things
			$.extend(true, panel.properties, panel.merge);
			
			//schema.additionalProperties.properties.nav.additionalProperties.properties.panel = panel;
		
		} else if (mode == 'admin_box_schema') {
			//Workaround for a few examples where docson crashes if it tries to read them
			schema.additionalProperties.properties.tabs.additionalProperties.properties.fields.additionalProperties.properties.multiple_edit.properties.select_list.type = 'object';
			
			schema.additionalProperties.properties.tabs.additionalProperties.properties.fields.additionalProperties.properties.values.additionalProperties.type = 'object';
			
		}
		
		var pages = schema.pages;
		
		if (specificTag) {
			if (mode == 'organizer_schema' && specificTag == 'panel') {
				schema = panel;
			} else {
				schema = docTools.narrowSchema(mode, schema, specificTag);
			}
		}
		
		if (pages) {
			for (var p in pages) {
				docTools.filterSchema(mode, schema, specificTag, linkToRef, pages[p], pages[p]);
				
				if (pages[p] == 'panel') {
					docTools.filterSchema(mode, schema, specificTag, linkToRef, 'panels', 'panel');
				}
			}
		}
		
		if (schema.additionalProperties && !schema.properties) {
			schema = schema.additionalProperties;
		}
		
		delete schema.description;
		
		
		//Hack to fix a crash in handlebars
		try {
			schema.properties.insert_image_button.type = 'object';
			schema.properties.insert_link_button.type = 'object';
		} catch (e) {
		}
		
		
		var p, prop;
		if (schema.properties) {
			for (p in schema.properties) {
				prop = schema.properties[p];
				
				if (prop.preFillOrganizerPanel) {
					if (prop.description === undefined) {
						prop.description = '';
					}
					
					prop.description = '<span class="static_warning prefill_warning">You can only set this property in your <code>.yaml</code> files and your <code>preFillOrganizerPanel()</code> method.<br/>Nothing will happen if you change it anywhere else in your PHP code.</span><br/>'
						+ prop.description;
					
				} else if (prop['static']) {
					if (prop.description === undefined) {
						prop.description = '';
					}
					
					prop.description = '<span class="static_warning">You must write this property in your <code>.yaml</code> files. Nothing will happen if you change it in your PHP code.</span><br/>'
						+ prop.description;
				}
			
			}
		}
		
		
		return schema;
	} else {
		return false;
	}
};

docTools.filterSchema = function(mode, schema, specificTag, linkToRef, tag, replacement) {
	
	if (tag == specificTag) {
		return;
	}
	
	for (var key in schema) {
		if (typeof schema[key] == 'object') {
			if (key == tag) {
				delete schema[key].properties;
				
				if (schema[key].additionalProperties) {
					delete schema[key].additionalProperties.properties;
				}
				
				if (linkToRef) {
					schema[key].topurl = '../../../ref-' + mode + '-' + replacement;
				} else {
					schema[key].url = 'doc_tools.php?mode=' + mode + '&tag=' + replacement;
				}
			
			} else {
				docTools.filterSchema(mode, schema[key], specificTag, linkToRef, tag, replacement);
			}
		}
	}
};

docTools.narrowSchema = function(mode, schema, specificTag) {
	var key, narrowedSchema;
	
	for (key in schema) {
		if (typeof schema[key] == 'object'
		 && key != 'forbidden_if_true') {
			if (key == specificTag/* && (schema[key].properties || schema[key].additionalProperties)*/) {
				return schema[key];
			} else if (narrowedSchema = docTools.narrowSchema(mode, schema[key], specificTag)) {
				return narrowedSchema;
			}
		}
	}
	
	return false;
};

if (window.parent
 && window.parent.document.getElementById('tuix_documentation')) {
	setInterval(function() {
		if (window.$) {
			$(window.parent.document.getElementById('tuix_documentation')).height($('#doc').height() + 25);
		}
	}, 50);
}


})(
	window, docTools);