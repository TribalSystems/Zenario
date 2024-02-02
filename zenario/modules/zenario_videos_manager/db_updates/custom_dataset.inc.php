<?php
/*
 * Copyright (c) 2024, Tribal Limited
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


if (ze\dbAdm::needRevision(10)) {
	$datasetId = ze\datasetAdm::register(
		'Videos',
		ZENARIO_VIDEOS_MANAGER_PREFIX. 'videos_custom_data',
		ZENARIO_VIDEOS_MANAGER_PREFIX. 'videos',
		'zenario_videos_manager__video',
		'zenario_videos_manager/panels/videos'
	);
	
	ze\datasetAdm::registerSystemField($datasetId, 'text', 'details', 'url', 'url');
	ze\datasetAdm::registerSystemField($datasetId, 'text', 'details', 'title', 'title');
	ze\datasetAdm::registerSystemField($datasetId, 'textarea', 'details', 'short_description', 'short_description');
	ze\datasetAdm::registerSystemField($datasetId, 'text', 'details', 'description', 'description');
	ze\datasetAdm::registerSystemField($datasetId, 'text', 'details', 'date', 'date');
	ze\datasetAdm::registerSystemField($datasetId, 'checkboxes', 'details', 'categories');
	
	ze\dbAdm::revision(10);
}