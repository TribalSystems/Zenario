zenario.createZenarioLibrary('P');

zenarioP.checkPasswordStrength = function(password, settings) {
	var min_pass_length,
		min_pass_score;
	
	if (!_.isString(password)) {
		password = '';
	}
	
	settings = settings || zenarioA.passVars;
	if (settings) {
		if (settings.min_extranet_user_password_length > 0) {
			min_pass_length = settings.min_extranet_user_password_length;
		} else {
			min_pass_length = 10;
		}

		if (settings.min_extranet_user_password_score >= 2) {
			min_pass_score = settings.min_extranet_user_password_score;
		} else {
			min_pass_score = 2;
		}
	} else {
		settings = {};
		min_pass_length = 10;
		min_pass_score = 2;
	}

	var validation = [],
		lower = password.match(/[a-z]/g) ? true : false,
		upper = password.match(/[A-Z]/g) ? true : false,
		numbers = password.match(/[0-9]/g) ? true : false,
		symbols = password.match(/[^a-zA-Z0-9]/g) ? true : false,
		password_requirement_match = true;

	if (password.length < min_pass_length) {
				password_requirement_match = false;
	}

	validation['password_length'] = password.length;
	validation['min_length'] = (password.length >= min_pass_length);
	validation['min_pass_score'] = min_pass_score;
	validation['lowercase'] = lower;
	validation['uppercase'] = upper;
	validation['numbers'] = numbers;
	validation['symbols'] = symbols;
	validation['password_matches_requirements'] = password_requirement_match;

	return validation;
};

zenarioP.updatePasswordNotifier = function(passwordField, settings, passwordMessageField, adminFacing, isInstaller) {
	var password = $(passwordField).val();
	var validation = zenarioP.checkPasswordStrength(password, settings);
	var phrases = adminFacing ? zenarioA.phrase : zenarioUserPasswordPhrase;
					
	$('#min_length').attr('class', validation['min_length'] ? 'pass' : 'fail');
	$('#lowercase').attr('class', validation['lowercase'] ? 'pass' : 'fail');
	$('#uppercase').attr('class', validation['uppercase'] ? 'pass' : 'fail');
	$('#numbers').attr('class', validation['numbers'] ? 'pass' : 'fail');
	$('#symbols').attr('class', validation['symbols'] ? 'pass' : 'fail');

	var result = zxcvbn(password);
	
	//Update the password message field if there is one...
	if (passwordMessageField) {
		passwordMessageField = $(passwordMessageField);

		passwordMessageField.removeClass('title_red');
		passwordMessageField.removeClass('title_orange');
		passwordMessageField.removeClass('title_green');
		
		if (validation['password_matches_requirements']) {
			if (result) {
				switch (result.score) {
					case 4: //is very unguessable (guesses >= 10^10) and provides strong protection from offline slow-hash scenario
						if (validation.min_pass_score < 4) {
							passwordMessageField.addClass('title_green');	
							passwordMessageField.text(phrases.password_score_4_exceeds_requirements);
						} else if (validation.min_pass_score == 4) {
							passwordMessageField.addClass('title_green');	
							passwordMessageField.text(phrases.password_score_4_matches_requirements);
						}
						break;
					case 3: //is safely unguessable (guesses < 10^10), offers moderate protection from offline slow-hash scenario
						if (validation.min_pass_score == 4) {
							passwordMessageField.addClass('title_red');
							passwordMessageField.text(phrases.password_score_3_too_easy_to_guess);
						} else if (validation.min_pass_score < 4) {
							passwordMessageField.addClass('title_green');	
							passwordMessageField.text(phrases.password_score_3_matches_requirements);
						}
						break;
					case 2: //is somewhat guessable (guesses < 10^8), provides some protection from unthrottled online attacks
						if (validation.min_pass_score == 2) {
							passwordMessageField.addClass('title_orange');
							if (isInstaller) {
								passwordMessageField.text(phrases.password_score_2_matches_requirements_but_easy_to_guess);
							} else {
								passwordMessageField.text(phrases.password_score_2_too_easy_to_guess);
							}
						} else if (validation.min_pass_score > 2) {
							passwordMessageField.addClass('title_red');
							passwordMessageField.text(phrases.password_score_2_too_easy_to_guess);
						}
						break;
					case 1: //is still very guessable (guesses < 10^6)
						passwordMessageField.addClass('title_red');
						passwordMessageField.text(phrases.password_score_1_too_easy_to_guess);
						break;
					case 0: //s extremely guessable (within 10^3 guesses)
					default:
						passwordMessageField.addClass('title_red');
						passwordMessageField.text(phrases.password_score_0_too_easy_to_guess);
				}
			}
		} else {
			if (validation['password_length'] > 0) {
				passwordMessageField.text(phrases.password_does_not_match_the_requirements);
				passwordMessageField.addClass('title_red');
			} else {
				passwordMessageField.text(phrases.enter_password);
				passwordMessageField.addClass('title_orange');
			}
		}
	} else {
		//...otherwise change the password input field border colour.
		passwordField = $(passwordField);

		passwordField.removeClass('border_red');
		passwordField.removeClass('border_orange');
		passwordField.removeClass('border_green');
		
		if (validation['password_matches_requirements']) {
			if (result) {
				switch (result.score) {
					case 4: //is very unguessable (guesses >= 10^10) and provides strong protection from offline slow-hash scenario
					case 3: //is safely unguessable (guesses < 10^10), offers moderate protection from offline slow-hash scenario
						passwordField.addClass('border_green');
						break;
					case 2: //is somewhat guessable (guesses < 10^8), provides some protection from unthrottled online attacks
						passwordField.addClass('title_orange');
						break;
					case 1: //is still very guessable (guesses < 10^6)
					case 0: //s extremely guessable (within 10^3 guesses)
					default:
						passwordField.addClass('title_red');
				}
			}
		} else {
			passwordField.addClass('border_red');
		}
	}
}

zenario.shrtNms(zenarioP);