window.onload = function() {
	$('#language_id').trigger('change');
}

showFilePickerButtonOnclick = function() {
	
	//If privacy settings are enabled, make sure one is selected
	var videoPrivacySettings = document.getElementsByName('vimeoPrivacy');
	if (videoPrivacySettings && videoPrivacySettings.length > 0) {
		vimeoPrivacyRadios = document.querySelector('input[name="vimeoPrivacy"]:checked');
		if (vimeoPrivacyRadios) {
			videoPrivacySettingsSelectedValue = vimeoPrivacyRadios.value;
		} else {
			videoPrivacySettingsSelectedValue = null;
		}
	
		var privacySettingError = document.getElementById('privacySettingError');
		if (!videoPrivacySettingsSelectedValue) {
			if (privacySettingError) {
				privacySettingError.style = "display: block;"
			}
			noPrivacyErrors = false;
		} else {
			if (privacySettingError) {
				privacySettingError.style = "display: none;"
			}
			noPrivacyErrors = true;
		}
	} else {
		videoPrivacySettingsSelectedValue = 'disable';
		noPrivacyErrors = true;
	}
	
	//If language support is enabled, check if choosing a language is mandatory
	noLanguageErrors = true;
	var language = document.getElementById('language_id');
	if (language) {
		var languageIsMandatory = document.getElementById('languageIsMandatory');
		if (languageIsMandatory) {
			var languageIsMandatoryError = document.getElementById('languageIsMandatoryError');
			var selectedLanguage = language.value;
			if (!selectedLanguage || selectedLanguage == 0) {
				languageIsMandatoryError.style = "display: block;"
				noLanguageErrors = false;
			} else {
				languageIsMandatoryError.style = "display: none;"
				noLanguageErrors = true;
			}
		}
	}
	
	if (noPrivacyErrors && noLanguageErrors) {
		document.getElementById("title").readOnly = true;
		document.getElementById("short_description").readOnly = true;
		
		var date = document.getElementById("date");
		date.readOnly = true;
		date.disabled = true;
	
		if (videoPrivacySettings) {
			for (i = 0; i < videoPrivacySettings.length; i++) {
			  videoPrivacySettings[i].disabled = true;
			} 
		}
		
		if (language) {
			language.disabled = true;
		}

		document.getElementById("filePicker").style = "display: block;";
		document.getElementById('results').style = "display: block;";
		document.getElementById('progress_container').style = "display: block;";
		
		document.getElementById('showFilePickerButton').style = "display: none;";
	}
}

//Called when files are dropped on to the drop target or selected by the browse button.
//For each file, uploads the content to Drive & displays the results when complete.
function handleFileSelect(evt) {
	evt.stopPropagation();
	evt.preventDefault();

	var title = document.getElementById('title');
	title.readOnly = true;
	var description = document.getElementById('short_description');
	description.readOnly = true;
	document.getElementById('cancelButtonDiv').style = "display: none;";
	
	var files = evt.dataTransfer ? evt.dataTransfer.files : $(this).get(0).files;
	var results = document.getElementById('results');

	//Clear the results div
	while (results.hasChildNodes()) results.removeChild(results.firstChild)

	//Rest the progress bar and show it
	updateProgress(0)
	document.getElementById('progress_container').style.display = 'block'

	//Instantiate Vimeo Uploader
	;(new VimeoUpload({
		name: document.getElementById('title').value,
		description: document.getElementById('short_description').value,
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
				xhr.send(JSON.stringify(parameters));
			}
		
			//Set the value of the "URL" form field
			var url = 'https://vimeo.com/' + videoId;
			var video_url_field = document.getElementById('url');
			video_url_field.value = url;
			video_url_field.readOnly = true;
			
			//Once the video upload finishes, save it to Zenario DB
			document.getElementById("submit").click();
		}
	})).upload()

	//local function: show a user message
	function showMessage(html, type) {
		//hide progress bar
		document.getElementById('progress_container').style.display = 'none'

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
	var dropZone = document.getElementById('drop_zone');
	var browse = document.getElementById('browse');
	var showFilePickerButton = document.getElementById('showFilePickerButton');
	
	if (dropZone) {
		dropZone.addEventListener('dragover', handleDragOver, false);
		dropZone.addEventListener('drop', handleFileSelect, false);
	}
	
	if (browse) {
		browse.addEventListener('change', handleFileSelect, false);
	}
});

function selectionButtonsOnclick (event) {
	document.getElementById('add_video').style = "display: block;";
	document.getElementById('selection_buttons').style = "display: none;";
	document.getElementById('cancelButtonDiv').style = "display: block;";
	
	document.getElementById("filePicker").style = "display: none;";
	document.getElementById('results').style = "display: none;";
	document.getElementById('progress_container').style = "display: none;";
	
	var uploadSuccessfulEl = document.getElementById('uploadSuccessfulContainer');
	if (uploadSuccessfulEl) {
		uploadSuccessfulEl.style = "display: none;";
	}
	
	if (event.target.id == 'add_video_by_url') {
		document.getElementById('url_div').style = "display: block;";
		document.getElementById('submit').style = "display: block;";
		
		var vimeoPrivacySettingsContainer = document.getElementById('vimeoPrivacySettingsContainer');
		if (vimeoPrivacySettingsContainer) {
			vimeoPrivacySettingsContainer.style = "display: none;";
		}
		
		document.getElementById('showFilePickerButton').style = "display: none;";

		document.getElementById('uploaded_or_added_by_url').value = "add_video_by_url";
	} else if (event.target.id == 'upload_a_video') {
		var vimeoPrivacySettingsContainer = document.getElementById('vimeoPrivacySettingsContainer');
		if (vimeoPrivacySettingsContainer) {
			vimeoPrivacySettingsContainer.style = "display: block;";
		}
		
		document.getElementById('showFilePickerButton').style = "display: block;";
		
		document.getElementById('submit').style = "display: none;";
		document.getElementById('url_div').style = "display: none;";

		document.getElementById('uploaded_or_added_by_url').value = "upload_a_video";
	}
}

function cancelButtonOnclick () {
	document.getElementById('add_video').style = "display: none;";
	document.getElementById('selection_buttons').style = "display: block;";
	document.getElementById('cancelButtonDiv').style = "display: none;";
	
	var uploadSuccessfulEl = document.getElementById('uploadSuccessfulContainer');
	if (uploadSuccessfulEl) {
		uploadSuccessfulEl.style = "display: none;";
	}
	
	var vimeoPrivacySettingsContainer = document.getElementById('vimeoPrivacySettingsContainer');
	if (vimeoPrivacySettingsContainer) {
		vimeoPrivacySettingsContainer.style = "display: none;";
	}
	
	document.getElementById("title").removeAttribute('readOnly');
	document.getElementById("short_description").removeAttribute('readOnly');
	
	videoPrivacySettings = document.getElementsByName('vimeoPrivacy');
	if (videoPrivacySettings) {
		for (i = 0; i < videoPrivacySettings.length; i++) {
		  videoPrivacySettings[i].removeAttribute('disabled');
		}
		
		var privacySettingError = document.getElementById('privacySettingError');
		if (privacySettingError) {
			privacySettingError.style = "display: none;";
		}
	}
	
	//Clear the URL
	document.getElementById('url').value = "";
}

//"View video" visibility + copying logic for the shareable link
function showOrHideElement(elementId, action) {
	var El = document.getElementById(elementId);
	if (El) {
		if (action == 'show') {
			El.style = "display: inline-block;";
		} else if (action == 'hide') {
			El.style = "display: none;";
		}
	}
}

function copyLinkButtonOnclick (event) {
	var linkEl = document.getElementById('videoLink');
	if (linkEl) {
		zenario.copy(linkEl, true);
	}
	
	showOrHideElement('noteCopyLink', 'hide');
	showOrHideElement('noteCopied', 'show');
}

function languagePickerOnChange () {
	var languagePicker = document.getElementById('language_id');
	var languageHiddenInput = document.getElementById('language_id_value');
	
	languageHiddenInput.value = languagePicker.value;
}

function videoLinkOnMouseover () {
	showOrHideElement('copyLinkButton', 'show');
}

function videoLinkOnMouseout () {
	showOrHideElement('copyLinkButton', 'hide');
}

function videoLinkOnFocus () {
	var videoLinkEl = document.getElementById('videoLink');
	if (videoLinkEl) {
		videoLinkEl.select();
	}
}

function copyLinkButtonOnMouseover () {
	showOrHideElement('noteCopyLink', 'show');
	showOrHideElement('noteCopied', 'hide');
}

function copyLinkButtonOnMouseout () {
	showOrHideElement('noteCopyLink', 'hide');
	showOrHideElement('noteCopied', 'hide');
}