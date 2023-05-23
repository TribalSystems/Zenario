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


		
//For each image used, add its name to the slot controls, a link to the image properties FAB,
//and a link to the image in the image library.
$imgs = [];
foreach ($images as $imageId) {
	if ($image = ze\row::get('files', ['filename', 'usage'], $imageId)) {
		if ($image['usage'] == 'image') {
			$orgLink = ze\link::absolute() . 'organizer.php#zenario__content/panels/image_library//'. $imageId;
			
			$imgs[$image['filename']] = '
				<span
					class="zenario_slotControl_ImgInfo"
				>'. htmlspecialchars($image['filename']). '</span><a
					href="'. htmlspecialchars($orgLink). '"
					target="_blank"
					onclick="
						zenarioA.closeSlotControls();
						return zenarioA.imageProperties('. (int) $imageId. ', \''. ze\escape::jsOnClick($this->slotName). '\', '. (int) $this->instanceId. ', '. (int) $this->eggId. ');
					"
					class="zenario_imgProps"
				></a>';
		}
	}
}

if ($imgs !== []) {
	ksort($imgs);
	$controls['info']['plugin_images']['hidden'] = false;
	$controls['info']['plugin_images']['label'] = implode(', ', $imgs);
}