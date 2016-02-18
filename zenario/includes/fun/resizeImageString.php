<?php
/*
 * Copyright (c) 2016, Tribal Limited
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

//Work out the new width/height of the image
$newWidth = $newHeight = $cropWidth = $cropHeight = $cropNewWidth = $cropNewHeight = false;
resizeImageByMode($mode, $width, $height, $maxWidth, $maxHeight, $newWidth, $newHeight, $cropWidth, $cropHeight, $cropNewWidth, $cropNewHeight, $mime_type);

//Check if the image needs to be resized
if ($width != $cropNewWidth || $height != $cropNewHeight) {
	if (isImage($mime_type)) {
		//Load the original image into a canvas
		if ($image = @imagecreatefromstring($image)) {
			//Make a new blank canvas
			$trans = -1;
			$resized_image = imagecreatetruecolor($cropNewWidth, $cropNewHeight);
		
			//Transparent gifs need a few fixes. Firstly, we need to fill the new image with the transparent colour.
			if ($mime_type == 'image/gif' && ($trans = imagecolortransparent($image)) >= 0) {
				$colour = imagecolorsforindex($image, $trans);
				$trans = imagecolorallocate($resized_image, $colour['red'], $colour['green'], $colour['blue']);				
			
				imagefill($resized_image, 0, 0, $trans);				
				imagecolortransparent($resized_image, $trans);
		
			//Transparent pngs should also be filled with the transparent colour initially.
			} elseif ($mime_type == 'image/png') {
				imagealphablending($resized_image, false); // setting alpha blending on
				imagesavealpha($resized_image, true); // save alphablending setting (important)
				$trans = imagecolorallocatealpha($resized_image, 255, 255, 255, 127);
				imagefilledrectangle($resized_image, 0, 0, $cropNewWidth, $cropNewHeight, $trans);
			}
		
			$xOffset = 0;
			$yOffset = 0;
			if ($newWidth != $cropNewWidth) {
				$xOffset = (int) (((10 - $offset) / 20) * ($width - $cropWidth));
		
			} elseif ($newHeight != $cropNewHeight) {
				$yOffset = (int) ((($offset + 10) / 20) * ($height - $cropHeight));
			}
		
			//Place a resized copy of the original image on the canvas of the new image
			imagecopyresampled($resized_image, $image, 0, 0, $xOffset, $yOffset, $cropNewWidth, $cropNewHeight, $cropWidth, $cropHeight);
		
			//The resize algorithm doesn't always respect the transparent colour nicely for gifs.
			//Solve this by resizing using a different algorithm which doesn't do any anti-aliasing, then using
			//this to create a transparent mask. Then use the mask to update the new image, ensuring that any pixels
			//that should be transparent actually are.
			if ($mime_type == 'image/gif') {
				if ($trans >= 0) {
					$mask = imagecreatetruecolor($cropNewWidth, $cropNewHeight);
					imagepalettecopy($image, $mask);
				
					imagefill($mask, 0, 0, $trans);				
					imagecolortransparent($mask, $trans);
				
					imagetruecolortopalette($mask, true, 256); 
					imagecopyresampled($mask, $image, 0, 0, $xOffset, $yOffset, $cropNewWidth, $cropNewHeight, $cropWidth, $cropHeight);
				
					$maskTrans = imagecolortransparent($mask);
					for ($y = 0; $y < $cropNewHeight; ++$y) {
						for ($x = 0; $x < $cropNewWidth; ++$x) {
							if (imagecolorat($mask, $x, $y) === $maskTrans) {
								imagesetpixel($resized_image, $x, $y, $trans);
							}
						}
					}
				}
			}
		
		
			$temp_file = tempnam(sys_get_temp_dir(), 'Img');
				if ($mime_type == 'image/gif') imagegif($resized_image, $temp_file);
				if ($mime_type == 'image/png') imagepng($resized_image, $temp_file);
				if ($mime_type == 'image/jpeg') imagejpeg($resized_image, $temp_file, ifNull((int) setting('jpeg_quality'), 99));
			
				imagedestroy($resized_image);
				unset($resized_image);
				$image = file_get_contents($temp_file);
			unlink($temp_file);

			$width = $cropNewWidth;
			$height = $cropNewHeight;
		} else {
			$image = null;
		}
	} else {
		$width = $cropNewWidth;
		$height = $cropNewHeight;
	}
}