<?php
/*
 * Copyright (c) 2015, Tribal Limited
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


//Preload Sprites by adding them as tiny divs at the top right
echo '
<div class="zenario_preload_sprites">
	<div class="zenario_ps1"></div>
	<div class="zenario_ps2"></div>
	<div class="zenario_ps3"></div>
	<div class="zenario_ps4"></div>
	<div class="zenario_ps5"></div>
	<div class="zenario_ps6"></div>
	<div class="zenario_ps7"></div>
	<div class="zenario_ps8"></div>
	<div class="zenario_ps9"></div>
	<div class="zenario_ps10"></div>
</div>';


echo '
<form style="display: none;" target="zenario_iframe" method="post" id="zenario_iframe_form"></form>
<iframe style="display: none;" name="zenario_iframe" id="zenario_iframe"></iframe>
<div id="zenario_now_loading" class="zenario_now" style="display: none;">
	<h1 style="text-align: center;">', adminPhrase('Loading'), '
		<div class="bounce1"></div>
  		<div class="bounce2"></div>
  		<div class="bounce3"></div>
	</h1>
</div>
<div id="zenario_now_saving" class="zenario_now" style="display: none;">
	<h1 style="text-align: center;">', adminPhrase('Saving'), '
		<div class="bounce1"></div>
  		<div class="bounce2"></div>
  		<div class="bounce3"></div>
  	</h1>
</div>
<div id="zenario_notification" class="zenario_now" style="display: none;"></div>
<div class="floating_box" id="zenario_afb_container">';

	setupAdminSlotControls(cms_core::$slotContents, false);

echo '
	<div id="zenario_fbAdminOrganizer" style="display: none;"></div>
</div>

<div id="zenario_progress_wrap">
	<div id="zenario_progress" class="ui-progressbar ui-widget ui-widget-content ui-corner-all">
	   <div id="zenario_progress_name"></div>
	   <div id="zenario_progress_stop"></div>
	   <div id="zenario_progressbar" class="ui-progressbar-value ui-widget-header ui-corner-left" style="width: 0%;"></div>
	</div>
</div>';
