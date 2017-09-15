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

header('Content-Type: text/javascript; charset=UTF-8');
require '../basicheader.inc.php';

useCache('zenario-inc-organizer-js-'. LATEST_REVISION_NO);
useGZIP();


//Run pre-load actions
require CMS_ROOT. 'zenario/api/cache_functions.inc.php';
require editionInclude('wrapper.pre_load');


//Include all of the standard JavaScript Admin libraries for the CMS
incJS('zenario/libraries/mit/jquery/jquery.nestable');
incJS('zenario/js/admin_organizer');

//Include every panel-type
//N.b. these need to be included in dependency order
incJS('zenario/api/panel_type_base_class');
incJS('zenario/js/panel_type_grid');
incJS('zenario/js/panel_type_list');
incJS('zenario/js/panel_type_list_or_grid');
incJS('zenario/js/panel_type_list_with_totals');
incJS('zenario/js/panel_type_list_with_totals_on_refiners');
incJS('zenario/js/panel_type_list_with_subheadings');
incJS('zenario/js/panel_type_multi_line_list');
incJS('zenario/js/panel_type_list_or_grid_or_multi_line_list');
incJS('zenario/js/panel_type_network_graph');
incJS('zenario/js/panel_type_hierarchy');
incJS('zenario/js/panel_type_hierarchy_with_lazy_load');
incJS('zenario/js/panel_type_hierarchy_documents');
incJS('zenario/js/panel_type_slot_reload_on_change');

incJS('zenario/js/panel_type_images_with_tags');
incJS('zenario/js/panel_type_images_with_tags_or_grid');
incJS('zenario/js/panel_type_grid_or_images_with_tags');
incJS('zenario/js/panel_type_images_with_tags_or_grid_or_multi_line_list');

incJS('zenario/js/panel_type_calendar');
incJS('zenario/js/panel_type_calendar_user_timers');

incJS('zenario/js/panel_type_google_map');
incJS('zenario/js/panel_type_list_or_grid_or_google_map');

incJS('zenario/js/panel_type_form_builder_base_class');
incJS('zenario/js/panel_type_form_builder');
incJS('zenario/js/panel_type_admin_box_builder');

incJS('zenario/js/panel_type_schematic_builder');


//Run post-display actions
require editionInclude('wrapper.post_display');
