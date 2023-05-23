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


header('Content-Type: text/javascript; charset=UTF-8');
require '../../../basicheader.inc.php';

ze\db::loadSiteConfig();

//Build the HTML
echo '
document.write(\'', ze\escape::js('
	<div class="zenario_sensitive_content">
		<div class="zenario_sensitive_content_wrap">
			<div class="zenario_sc_title">'. ze\lang::phrase(ze::setting('zenario_sensitive_content_message__heading') ?: false). '</div>
			<div class="zenario_sc_message">'. ze\lang::phrase(ze::setting('zenario_sensitive_content_message__text') ?: false). '</div>
			<div class="zenario_sc_buttons">
				<div class="zenario_cc_continue">
					<a href="zenario/modules/zenario_sensitive_content_message/fun/accept_sensitive_content_message.php?sensitive_content_message_accepted=1">'.
						ze\lang::phrase('Accept').
					'</a>
				</div>
			</div>
		</div>
	</div>
'), '\');
document.body.style.overflow = "hidden";'; //Disable page scrolling while the message is displayed