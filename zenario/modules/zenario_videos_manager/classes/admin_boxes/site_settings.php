<?php


class zenario_videos_manager__admin_boxes__site_settings extends zenario_videos_manager {
	
	
	public function fillAdminBox($path, $settingGroup, &$box, &$fields, &$values) {
		if ($settingGroup == 'videos') {
			$link = ze\link::absolute() . '/organizer.php#zenario__administration/panels/site_settings//api_keys~.site_settings~tzenario_videos_manager__vimeo~k{"id"%3A"api_keys"}';
			$fields['zenario_videos_manager__vimeo/vimeo_access_token_link']['snippet']['html'] =
				ze\admin::phrase('The API key setting is available on the <a target="_blank" href="[[link]]">API Keys</a> panel.', ['link' => htmlspecialchars($link)]);
			
			//Set labels for privacy settings
			$vimeoPrivacySettingsFormattedNicely = self::getVimeoPrivacySettingsFormattedNicely();
			foreach ($fields['zenario_videos_manager__vimeo/vimeo_privacy_settings']['values'] as $privacySettingKey => &$privacySetting) {
				$privacySetting['label'] = $this->phrase($vimeoPrivacySettingsFormattedNicely[$privacySettingKey]['label']);
				$privacySetting['note_below'] = $this->phrase($vimeoPrivacySettingsFormattedNicely[$privacySettingKey]['note']);
			}
			
			$documentEnvelopesModuleIsRunning = ze\module::inc('zenario_document_envelopes_fea');
			if ($documentEnvelopesModuleIsRunning) {
				unset($fields['zenario_videos_manager__vimeo/video_language_is_mandatory']['side_note']);
			} else {
				$fields['zenario_videos_manager__vimeo/video_language_is_mandatory']['disabled'] = true;
			}
		}
	}
}