<?php

// Migrate old phrase codes
if (needRevision(36)) {
	$codes = array(
		'Your profile' => '_VIEW_PROFILE_TITLE',
		'Edit your profile' => '_EDIT_PROFILE_TITLE',
		'Your profile has been updated.' => '_PROFILE_UPDATED',
		'Edit profile' => '_EDIT_PROFILE',
		'Cancel' => '_CANCEL'
	);
	
	foreach ($codes as $phrase => $code) {
		$visitorPhrasesResult = getRows('visitor_phrases', true, array('code' => $code, 'module_class_name' => 'zenario_extranet_profile_edit'));
		while ($visitorPhrase = sqlFetchAssoc($visitorPhrasesResult)) {
			updateRow('visitor_phrases', array('code' => $phrase), array('id' => $visitorPhrase['id']));
		}
	}
	
	revision(36);
}