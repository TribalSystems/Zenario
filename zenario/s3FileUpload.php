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

//Allow a plugin to show on a page on its own.

use Aws\S3\S3Client;
require 'adminheader.inc.php';
ze\cookie::startSession();

//Run pre-load actions
echo '<link rel="stylesheet" type="text/css" media="screen" href="'. ze\link::protocol(). \ze\link::host(). SUBDIRECTORY .'zenario/styles/admin_s3_upload_field.css"/>';

	$version = [];
	$removeFlag = false;
	$draftMsg = false;
if ($_GET['cId'] && $_GET['cType'] && $_GET['cVersion']) {
	
	$version = ze\row::get('content_item_versions',
				['s3_file_id'],
				['id' => $_GET['cId'], 'type' => $_GET['cType'], 'version' => $_GET['cVersion']]);
	
}

	if(isset($version['s3_file_id']) && $version['s3_file_id'] && isset($_GET['remove']) && $_GET['remove']){
		$status = ze\content::status($_GET['cId'], $_GET['cType']);
		if (!ze\content::isDraft($status)) {
			$removeFlag = false;
			$draftMsg = true;
		} else {
			$removeFlag = true;
			$draftMsg = false;
		}
	}
	
	if ($_SERVER['REQUEST_METHOD'] == "POST" && $_FILES['file']['name'] ) { 

		$s3Filename = $_FILES['file']['name'];
		$size = $_FILES['file']['size'];
		$s3CachePath = $_FILES['file']['tmp_name'];
		$s3MimeType = '';

		if (!empty($_GET['mime_type'])) {
			$s3MimeType = $_GET['mime_type'];
			unset($_GET['mime_type']);
		}

		$s3file	= [];
		if (ze\module::inc('zenario_ctype_document')) {
			$s3file = zenario_ctype_document::uploadS3File('content', $s3CachePath, $s3Filename, $s3MimeType);
		}	
			echo '<div class = "s3_container"><div class = "s3_container_inner">';
			echo '<div class = "s3_details">
					 '. $s3file['filename'] .'
				
				</div>';
			echo '<script type="text/javascript" > 
			parent.document.getElementById("s3_file_id").value = "'. ze\escape::js($s3file['fid']). '";
			parent.document.getElementById("s3_file_name").value = "'. ze\escape::js($s3file['filename']).'";				
			</script>';
		
			echo '<div class = "remove_s3">
					<a href= "'.ze\link::protocol(). \ze\link::host(). SUBDIRECTORY.'zenario/s3FileUpload.php?cId='. $_GET['cId'] .'&cType='. $_GET['cType']. '&cVersion='. $_GET['cVersion'].'&remove=1" >Delete</a>
							
				 </div>';
			
			echo '</div></div>';
			echo '<div class = "s3_success">
					<p>File uploaded</p>
				 </div>';
		
		
	}
	
	elseif (isset($version['s3_file_id']) && $version['s3_file_id'] && !$removeFlag) {
	
		$filesdetails = ze\row::get('files',
							true,
							['id' => $version['s3_file_id']]);
		$size = '';					
		if (ze\file::isImage($filesdetails['mime_type'])) {
			$size = '['. $filesdetails['width'] .' x '. $filesdetails['height'] .']';
		} else {
			$size = '['. ze\file::formatSizeUnits($filesdetails['size']) .']';
		}		
		
		//if(isset($_GET['Download']) && $_GET['Download']){
			
			if ($filesdetails['path']) {
					$fileName = $filesdetails['path'].'/'.$filesdetails['filename'];
				} else {
					$fileName = $filesdetails['filename'];
				}
				
			
		//}
		
		
		echo '<div class = "s3_container"><div class = "s3_container_inner">';
		echo '<div class = "s3_details edit_s3_file">'
		.$filesdetails['filename'].' '. $size.'
		
		</div>';
		if ($fileName) {
					if (ze\module::inc('zenario_ctype_document')) {
						$presignedUrl = zenario_ctype_document::getS3FilePresignedUrl($fileName);
					}
					if($presignedUrl)
					{
						echo '<div class = "download_s3">
						<a href= "'.$presignedUrl.'"  Download>Download</a>
								
						</div>';
						
					}
				}
		
		
		echo '<div class = "remove_s3">
				<a href= "'.ze\link::protocol(). \ze\link::host(). SUBDIRECTORY.'zenario/s3FileUpload.php?cId='. $_GET['cId'] .'&cType='. $_GET['cType']. '&cVersion='. $_GET['cVersion'].'&remove=1" >Delete</a>
						
			 </div>';
		echo '</div></div>';
		
		if($draftMsg)
		{
			$msg = ze\admin::phrase('You are editing a content item that\'s already ' .$status. '. ');
			$msg .= ze\admin::phrase('To upload new S3 file please make content item as a draft press "Save" then upload new.');
			echo '<div id = "draftMsg_Id" class = "draftMsg_class">'.$msg.'</div>';
		}
		
	} else {
		if($removeFlag){
			echo '<script type="text/javascript" > 
			parent.document.getElementById("s3_file_id").value = "";
			parent.document.getElementById("s3_file_name").value = "";
			parent.document.getElementById("s3_file_remove").value = "true";			
			</script>';
		}
		?>
		<form action="" method='post' enctype="multipart/form-data" onsubmit="return validateForm();" >
			
				
				
				<div class="upload_file_container">
					<div class="upload_file_container_inner">
						<input type='file' name='file' id="UploadFileId" class = "upload_fileId" onchange="return validateFormMessage();"/> 
					</div>
				</div>
				
				<div class="upload_file_msg_container">
					<input id = 'submit_button_id' type='submit' value='Upload' disabled = 'true'/>
				
					<div id = "UploadFile_Down" class = "upload_file_down"></div> 
					<div id = "UploadFile_Message" class = "upload_file_message"></div>
					<div id = "UploadFile_UP" class = "upload_file_up"></div> 
				</div>
			
		</form>


		<?php
	}
	//Run post-display actions
	if (ze::$canCache) require CMS_ROOT. 'zenario/includes/index.post_display.inc.php';
	?>

<script type="text/javascript" >
	function validateFormMessage(){
		var filedata = document.getElementById('UploadFileId'); 
		var filename = filedata.files.item(0).name;
		if (filename) {
			document.getElementById('submit_button_id').disabled = false;
			var msg = document.getElementById('UploadFile_Message');
			msg.innerHTML = 'File ' +filename+ ' selected. Select the MIME type and click "Upload" to upload.';
			var down = document.getElementById('UploadFile_Down'); 
			down.innerHTML = "";
		}
	}
	function validateForm(){
		var up = document.getElementById('UploadFile_UP'); 
		var down = document.getElementById('UploadFile_Down'); 
		var msg = document.getElementById('UploadFile_Message');
		msg.innerHTML = "";
		if( document.getElementById("UploadFileId").files.length == 0 ){
			 down.innerHTML = "No files selected"; 
			 up.innerHTML = "";
			 return false;
		}
		else
		{
			down.innerHTML = "";
			up.innerHTML = "Please wait, file uploading...";
			return true;
		}
		
	}

		  //if (parent.document.getElementById("s3_file_id").value!="") {
			//	address += parent.document.getElementById("postcode").value.replace(" ","") + ',';
			//}
</script>