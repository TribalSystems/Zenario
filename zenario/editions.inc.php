<?php
/*
 * Copyright (c) 2022, Tribal Limited
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


/*  
 *  An "edition" module is a module that can add some extra functionality into Zenario.
 *  
 *  If you wish to write your own edition module, you will need to add its name to the list below in order
 *  to hook it up with Zenario.
 *  
 *  There are hooks in Zenario for the active edition to tweak certain behaviour, including when a visitor
 *  accesses a page, directing a visitor to the homepage, how the menu is generated and what happens when
 *  a content item is Published.
 *  
 *  When Zenario runs into a hook, it calls the first edition module in the list that handles the hook.
 *  If two edition modules both handle a hook, normally only the first will be called. However each handling
 *  module has the ability to continue running the handlers, in which case the next module in the list that
 *  handles the hook will also be called.
 */


ze::$editions = [
	//A list of "edition" modules
	'zenario_pro_features',
	'zenario_common_features',
	
	//A list other modules that aren't "edition" modules, but use some of their hooks/features
	'assetwolf_2'
];