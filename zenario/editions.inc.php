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


/*  
 *  An "Edition" Module is a Module that can add some extra functionality into the CMS.
 *  
 *  All edition Modules can take actions when a Visitor navigates to a Content Item
 *  
 *  For efficiency reasons, we do not scan the entire Modules directory looking for edition Modules.
 *  If you wish to write your own Edition Module, you will need to add its name to the list below in order
 *  to hook it up with the CMS. The first in the list to be found will be marked as the active Module
 *  
 *  All edition Modules can take actions or run scripts when a Visitor navigates to a Content Item and a
 *  page is generated.
 *  
 *  There are hooks in the CMS for the active Edition to tweak certain behaviour, including directing
 *  a Visitor to the Homepage, how the Menu is generated and what happens when a Content Item is Published.
 *  
 *  The active Edition also provides the information in the Info Box when an Admin clicks on the gemstone at
 *  the top left of Storekeeper, and provides the "Powered By" text in the Footer Menu.
 *  
 *  Any subsequent Modules in the list will not be marked as the active Module, but will still be able to take
 *  actions and run scripts when a page is generated.
 */


cms_core::$editions = array(
	'zenario_pro_features' => 'zenario_pro_features',
	'zenario_common_features' => 'zenario_common_features'
);