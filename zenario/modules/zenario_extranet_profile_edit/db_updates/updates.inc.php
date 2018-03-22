<?php

// Migrate old phrase codes
if (ze\dbAdm::needRevision(36)) {
	$codes = [
		'Your profile' => '_VIEW_PROFILE_TITLE',
		'Edit your profile' => '_EDIT_PROFILE_TITLE',
		'Your profile has been updated.' => '_PROFILE_UPDATED',
		'Edit profile' => '_EDIT_PROFILE',
		'Cancel' => '_CANCEL'
	];
	
	foreach ($codes as $phrase => $code) {
		$visitorPhrasesResult = ze\row::query('visitor_phrases', true, ['code' => $code, 'module_class_name' => 'zenario_extranet_profile_edit']);
		while ($visitorPhrase = ze\sql::fetchAssoc($visitorPhrasesResult)) {
			ze\row::update('visitor_phrases', ['code' => $phrase], ['id' => $visitorPhrase['id']]);
		}
	}
	
	ze\dbAdm::revision(36);
}