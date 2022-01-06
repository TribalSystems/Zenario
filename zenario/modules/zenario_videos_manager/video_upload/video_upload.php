<?php
require '../../../zenario/adminheader.inc.php';

echo
'<!DOCTYPE HTML>
<html>
<head>
	<title>',  ze\admin::phrase('Add a video'), '</title>';

$prefix = '../../zenario/';
ze\content::pageHead($prefix);


ze\skinAdm::checkForChangesInFiles($runInProductionMode = true);
$v = ze\db::codeVersion();
echo '
	<link rel="stylesheet" type="text/css" href="zenario_extra_modules/zenario_videos_manager/video_upload/styles.css?v=', $v, '" media="screen"/>';

echo '</head>';
ze\content::pageBody();

$string =  
'<div>
	<h1>Uploading a video to Vimeo</h1>
	<div id="results"></div>
	
	<div id="progress-container" class="progress">
		<div id="progress" class="progress-bar progress-bar-info progress-bar-striped active" role="progressbar" aria-valuenow="46" aria-valuemin="0" aria-valuemax="100" style="width: 0%">&nbsp;0%
		</div>
	</div>
	
	<div class="form-group">
		<input type="text" name="name" id="videoName" class="form-control" placeholder="Video name" value="">
	</div>
	<div class="form-group">
		<textarea name="description" id="videoDescription" class="form-control" placeholder="Video description"></textarea>
	</div>
	<div class="form-group">
		<p class="label">Privacy:</p>';

if (($vimeoPrivacySettingsEnabled = ze::setting('enable_vimeo_privacy_settings')) && ($vimeoPrivacySettings = ze::setting('vimeo_privacy_settings'))) {
	ze\module::inc('zenario_videos_manager');
	$vimeoPrivacySettingsFormattedNicely = zenario_videos_manager::getVimeoPrivacySettingsFormattedNicely();
	
	$string .= '
		<p id="privacySettingError" class="error" style="display: none;">Please select the privacy setting.</p>
		
			<form id="vimeoPrivacySettings" name="vimeoPrivacySettings">';
	
	$vimeoPrivacySettings = explode(',', $vimeoPrivacySettings);
	$numberOfOptions = count($vimeoPrivacySettings);
	foreach ($vimeoPrivacySettings as $vimeoPrivacySetting) {
		$string .= '
			<label>
				<input type="radio" name="vimeoPrivacy" value="' . htmlspecialchars($vimeoPrivacySetting) . '" ' . ($numberOfOptions == 1 ? 'checked' : '') . '>';
		$string .= '
			' . ze\admin::phrase($vimeoPrivacySettingsFormattedNicely[$vimeoPrivacySetting]['label']) . '
			</label>
			<div class="note_to_user">' . ze\admin::phrase($vimeoPrivacySettingsFormattedNicely[$vimeoPrivacySetting]['note']) . '</div>';
	}
	
	$string .= '
			</form>';
} else {
	$string .= '
		<p class="info">The video privacy will be set to &quot;Disable&quot; (nobody can view on Vimeo, but the video can still be embedded on external sites)</p>';
}

$string .= '
	</div>
	<div class="form-group">
		<button type="button" id="showFilePickerButton">Finish editing and show the file picker</button>
	</div>
	
	<div class="col-md-4" id="filePicker" style="display: none;">
		<div id="drop_zone">Drop video files here</div>
		<br/>
		<label class="btn btn-block btn-info">
			Browse&hellip; <input id="browse" type="file" style="display: none;">
		</label>
  	</div>
</div>';

echo $string;

ze\content::pageFoot($prefix, false, false, false);

?>

<script type="text/javascript" src="zenario/libs/yarn/vimeo-upload/vimeo-upload.js"></script>
<script type="text/javascript">

	var noErrors = true;
	var videoPrivacySettingsSelectedValue;
	
	var showFilePickerButton = document.getElementById("showFilePickerButton");
	showFilePickerButton.onclick = function() {
		//If privacy settings are enabled, make sure one is selected
		var videoPrivacySettings = document.getElementsByName('vimeoPrivacy');
		if (videoPrivacySettings && videoPrivacySettings.length > 0) {
			videoPrivacySettingsSelectedValue = document.vimeoPrivacySettings.vimeoPrivacy.value;
			var privacySettingError = document.getElementById('privacySettingError')
			if (!videoPrivacySettingsSelectedValue) {
				privacySettingError.style = "display: block;"
				noErrors = false;
			} else {
				privacySettingError.style = "display: none;"
				noErrors = true;
			}
		} else {
			videoPrivacySettingsSelectedValue = 'disable';
			noErrors = true;
		}
		
		if (noErrors) {
		
			var videoName = document.getElementById("videoName");
			var videoDescription = document.getElementById("videoDescription");
		
			var filePicker = document.getElementById("filePicker");
		
			videoName.disabled = true;
			videoDescription.disabled = true;
			
			if (videoPrivacySettings) {
				for (i = 0; i < videoPrivacySettings.length; i++) {
				  videoPrivacySettings[i].disabled = true;
				} 
			}
		
			filePicker.style.display = "block";
		}
	}
	
	var token = "<?php echo ze::setting('vimeo_access_token'); ?>";
	
	//Called when files are dropped on to the drop target or selected by the browse button.
	//For each file, uploads the content to Drive & displays the results when complete.
	function handleFileSelect(evt) {
		evt.stopPropagation()
		evt.preventDefault()

		var files = evt.dataTransfer ? evt.dataTransfer.files : $(this).get(0).files
		var results = document.getElementById('results')

		//Clear the results div
		while (results.hasChildNodes()) results.removeChild(results.firstChild)
		
		//Rest the progress bar and show it
		updateProgress(0)
		document.getElementById('progress-container').style.display = 'block'

		//Instantiate Vimeo Uploader
		;(new VimeoUpload({
			name: document.getElementById('videoName').value,
			description: document.getElementById('videoDescription').value,
			file: files[0],
			token: token,
			onError: function(data) {
				showMessage('<strong>Error</strong>: ' + JSON.parse(data).error, 'danger')
			},
			onProgress: function(data) {
				updateProgress(data.loaded / data.total)
			},
			onComplete: function(videoId, index) {
				if (index > -1) {
					var that = this;
					//Set embed privacy (PATCH https://api.vimeo.com/videos/{video_id})
					var parameters = {privacy: {embed: 'whitelist', view: videoPrivacySettingsSelectedValue}};
					var apiURL = 'https://api.vimeo.com/videos/' + encodeURIComponent(videoId);
					var xhr = new XMLHttpRequest();
					xhr.open('PATCH', apiURL, true);
					xhr.setRequestHeader('Authorization', 'Bearer ' + token);
					xhr.setRequestHeader('Content-Type', 'application/json');
					xhr.onload = function() {
						//Set embed whitelist domains (PUT https://api.vimeo.com/videos/{video_id}/privacy/domains/{domain})
						apiURL = 'https://api.vimeo.com/videos/' + encodeURIComponent(videoId) + '/privacy/domains/' + encodeURIComponent("<?php echo $_SERVER['HTTP_HOST'] ?? false; ?>");
						var xhr2 = new XMLHttpRequest();
						xhr2.open('PUT', apiURL, true);
						xhr2.setRequestHeader('Authorization', 'Bearer ' + token);
						xhr2.onload = function() {
							//The metadata contains all of the uploaded video(s) details see: https://developer.vimeo.com/api/endpoints/videos#/{video_id}
							url = that.metadata[index].link
							//Open admin box with video data
							parent.$.colorbox.close();
							var key = {from_video_upload: true};
							var values = {details: {url: that.metadata[index].link, title: that.metadata[index].name, description: that.metadata[index].description}};
							parent.zenarioAB.open('zenario_videos_manager__video', key, undefined, values);
						};
						xhr2.send();
					};
					xhr.send(JSON.stringify(parameters));
				}
				
				var url = 'https://vimeo.com/' + videoId;
				showMessage('<strong>Upload Successful</strong>: check uploaded video @ <a href="' + url + '">' + url + '</a>.')
			}
		})).upload()

		//local function: show a user message
		function showMessage(html, type) {
			//hide progress bar
			document.getElementById('progress-container').style.display = 'none'

			//display alert message
			var element = document.createElement('div')
			element.setAttribute('class', 'alert alert-' + (type || 'success'))
			element.innerHTML = html
			results.appendChild(element)
		}
	}
	
	//Dragover handler to set the drop effect.
	function handleDragOver(evt) {
		evt.stopPropagation()
		evt.preventDefault()
		evt.dataTransfer.dropEffect = 'copy'
	}
	
	//Update progress bar.
	function updateProgress(progress) {
		progress = Math.floor(progress * 100)
		var element = document.getElementById('progress')
		element.setAttribute('style', 'width:' + progress + '%')
		element.innerHTML = '&nbsp;' + progress + '%'
	}
	
	//Wire up drag & drop listeners once page loads
	document.addEventListener('DOMContentLoaded', function() {
		var dropZone = document.getElementById('drop_zone')
		var browse = document.getElementById('browse')
		dropZone.addEventListener('dragover', handleDragOver, false)
		dropZone.addEventListener('drop', handleFileSelect, false)
		browse.addEventListener('change', handleFileSelect, false)
	});
	
</script>

</body>
</html>