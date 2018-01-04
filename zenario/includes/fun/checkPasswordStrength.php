<?php
/*
 * Copyright (c) 2018, Tribal Limited
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


if (!$passwordStrengthRequired
  //As above, $passwordStrengthRequired defaults to the site setting if not specified
  && !($passwordStrengthRequired = setting('min_admin_password_strength'))) {
	//Default to '_MEDIUM' if the site setting is not there
	$passwordStrengthRequired = '_MEDIUM';
}

//Count the number of lower case, upper case, numeric and non-alphanumeric
//characters to work out the complexity of a password.

//E.g., a password using only lower case letters is not very complex, and will
//probably be cracked faster than one that mixes upper case, lower case and numbers in.

//Work out a measure of the complexity by checking if it uses one of each different type
//of character. Bonus points are also given for each type used twice.
$lower = strlen(preg_replace('#[^a-z]#', '', $pass));
$lower = calculateBonusFromUsage($lower);

$upper = strlen(preg_replace('#[^A-Z]#', '', $pass));
$upper = calculateBonusFromUsage($upper);

$numeric = strlen(preg_replace('#[^0-9]#', '', $pass));
$numeric = calculateBonusFromUsage($numeric);

$nonalpha = strlen(preg_replace('#[a-z0-9]#i', '', $pass));
$nonalpha = calculateBonusFromUsage($nonalpha);

$weighting = $lower + $upper + $numeric + $nonalpha;
//Multiply by 1.5.
//(There's no reason for doing this, other than to make the power bar a bit wide that it
// would otherwise be.)
$weighting *= 1.5;

//Score the password by multiplying the length by our estimate of the complexity.
$score = strlen($pass) * $weighting;
//E.g. a password of "p_441ngs" is stronger than a password of "puddings"
	//They are the same length, but "p_441ngs" is more complex
//Or e.g. a password of "ireallyliketoeatpuddings" is stronger than a password of "p_441ngs"
	//"p_441ngs" is more complex, but "ireallyliketoeatpuddings" is a lot longer

if ($passwordStrengthRequired === true) {
	//Return the actual score if $passwordStrengthRequired was set to true
	return $score;
} else {
	//Otherwise return whether the score was good enough
	return $score >= passwordStrengthsToValues($passwordStrengthRequired);
}

?>