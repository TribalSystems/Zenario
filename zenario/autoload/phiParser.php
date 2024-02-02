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

namespace ze;




class phiToken {
	
	public $type;
	public $value;
	public $line;
	public $lSpace = true;
	public $rSpace = true;
	
	public $numB = 0;
	public $numC = 0;
	public $numS = 0;
	public $isOpen = false;
	public $isClose = false;
	public $inTuple = false;
	public $ignore = false;
	public $breaker = false;
	
	public $space = '';
	public $pre = '';
	public $suf = '';
	public $brack = '';
	public $outputValue = true;
	
	
	public function __construct($gotToken, $lastLine = 0, $isFirstEntry = false, $overideLineNumber = false) {
		
		//Check if the token_get_all() function detected what type of token this is
		if (is_array($gotToken)) {
			$this->type = token_name($gotToken[0]);
			$this->value = $gotToken[1];
			
			if ($overideLineNumber) {
				$this->line = $lastLine;
			} else {
				$this->line = $gotToken[2];
			}
			
			//The token_get_all() function detects specific operators, but for the most part
			//we only need them to be a generic "OPERATOR" type.
			switch ($this->type) {
				case 'T_COALESCE':					#	??	comparison operators (available since PHP 7.0.0)
				case 'T_IS_EQUAL':					#	==	comparison operators
				case 'T_IS_GREATER_OR_EQUAL':		#	>=	comparison operators
				case 'T_IS_IDENTICAL':				#	===	comparison operators
				case 'T_IS_NOT_EQUAL':				#	!= or <>	comparison operators
				case 'T_IS_NOT_IDENTICAL':			#	!==	comparison operators
				case 'T_IS_SMALLER_OR_EQUAL':		#	<=	comparison operators
				case 'T_LOGICAL_AND':				#	and	logical operators
				case 'T_LOGICAL_OR':				#	or	logical operators
				case 'T_POW':						#	**	arithmetic operators (available since PHP 5.6.0)
					$this->type = 'OPERATOR';
					break;
				
				//Some operators need to be translated for twig
				case 'T_BOOLEAN_AND':				#	&&	logical operators
					$this->type = 'OPERATOR';
					$this->value = 'and';
					break;
				case 'T_BOOLEAN_OR':				#	||	logical operators
					$this->type = 'OPERATOR';
					$this->value = 'or';
					break;
				case 'T_LOGICAL_XOR':				#	xor	logical operators
					$this->type = 'OPERATOR';
					$this->value = 'b-xor';
					break;
				
				case 'T_DNUMBER':					#	0.12, etc.	floating point numbers
					//Catch missing zeros before decimal places as Twig doesn't like these
					$matches = [];
					if (preg_match('@^\.\d+$@', $this->value, $matches)) {
						$this->value = '0'. $this->value;
					}
					
					//Also convert numbers in exponent form as Twig doesn't support them either
					$matches = [];
					if (preg_match('@^(\d*\.?\d*)e([\+\-]?)(\d+)$@i', $this->value, $matches)) {
						if ($matches[1][0] == '.') {
							$matches[1] = '0'. $matches[1];
						}
				
						$this->value = '('. $matches[1]. ' * (10 ** '. ($matches[2] === '-'? '-' : ''). $matches[3]. '))';
					}
					break;
					
				case 'T_LNUMBER':					#	123, 012, 0x1ac, etc.	integers
					//Convert hex to decimal as Twig doesn't support hex
					$matches = [];
					if (preg_match('@^0x([0-9a-f]+)$@i', $this->value, $matches)) {
						$this->value = (string) hexdec($matches[1]);
					}
					break;
				
				//Just treat a foreach as a for
				case 'T_FOREACH':					#	foreach
					$this->type = 'T_FOR';
					$this->value = 'for';
					break;
				
				//Also catch some misclassified things
				case 'T_PRINT':
					//"print" is a language construct in PHP, but is just a normal function in Phi
					$this->type = 'T_STRING';
					break;
				case 'T_STRING':					#	parent, self, etc.	identifiers, e.g. keywords like parent and self, function names, class names and more are matched. See also T_CONSTANT_ENCAPSED_STRING.
					switch (strtolower($this->value)) {
						case 'not':
							$this->type = 'OPERATOR';
							$this->value = 'not';
							break;
					}
			}

		//Some things the token_get_all() function doesn't detect as specific types.
		//Catch those here and assign types as needed.
		} else {
			$this->value = $gotToken;
			$this->line = $lastLine;
			
			switch ($gotToken) {
				case ';':
					$this->lSpace = false;
					$this->type = 'SEMICOLON';
					break;
				case '.':
					$this->lSpace = false;
					$this->rSpace = false;
				case '-':
				case '/':
				case '*':
				case '+':
				case '~':
				case '|':
					$this->type = 'OPERATOR';
					break;
				case '!':
					//Twig doesn't like using !s for nots. Write out the word specifically
					$this->type = 'OPERATOR';
					$this->value = 'not';
					break;
				case '^':
					//Convert the ^ operator to **
					$this->type = 'OPERATOR';
					$this->value = '**';
					break;
				case '.':
				case '(':
				case '[':
					$this->rSpace = false;
					$this->lSpace = false;
					$this->type = 'SYNTAX';
					break;
				case '}':
				case ')':
				case ']':
				case ',':
					$this->lSpace = false;
					$this->type = 'SYNTAX';
					break;
				case '{':
					$this->rSpace = false;
					$this->type = 'SYNTAX';
					break;
				case '=':
					$this->type = 'ASSIGN';
					break;
				default:
					$this->type = 'SYNTAX';
			}
		}
			
			
		//Only pay attention to certain types of token
		switch ($this->type) {
			//Handle
			case 'ASSIGN':
			case 'SYNTAX':
			case 'OPERATOR':
			case 'T_CONSTANT_ENCAPSED_STRING':	#	"foo" or 'bar'	string syntax
			case 'T_DNUMBER':					#	0.12, etc.	floating point numbers
			case 'T_LNUMBER':					#	123, 012, 0x1ac, etc.	integers
			case 'T_STRING':					#	parent, self, etc.	identifiers, e.g. keywords like parent and self, function names, class names and more are matched. See also T_CONSTANT_ENCAPSED_STRING.
				break;
			
			case 'T_BREAK':						#	break	break
			case 'T_CONTINUE':					#	continue	continue
			case 'T_ELSE':						#	else	else
			case 'T_ELSEIF':					#	elseif	elseif
			case 'T_FOR':						#	for	for
			case 'T_IF':						#	if	if
				//These things should trigger a statement break.
				$this->breaker = true;
				break;
			
			//From 9.4 onwards, stop ignoring semicolons, and complain if they're used
			case 'SEMICOLON':
				throw new \Twig\Error\Error('Semicolons are no longer used for line endings in Phi scripts, please remove them.', $this->line + 1, $source = null);
			
			//Ignore
			case 'T_COMMENT':					#	// or #, and /* */	comments
			case 'T_DOC_COMMENT':				#	/** */	PHPDoc style comments
			case 'T_WHITESPACE':				#	\t \r\n	 
				$this->ignore = true;
				break;
			
			//Ignore on the first entry only
			case 'T_OPEN_TAG':					#	<?php, <? or <%	escaping from HTML
				if ($isFirstEntry) {
					$this->ignore = true;
					break;
				}
			
			//Allow a few operators. Twig doesn't support these so we'll need to rewrite them later.
			case 'T_DEC':						#	--	incrementing/decrementing operators
			case 'T_DIV_EQUAL':					#	/=	assignment operators
			case 'T_INC':						#	++	incrementing/decrementing operators
			case 'T_MINUS_EQUAL':				#	-=	assignment operators
			case 'T_MOD_EQUAL':					#	%=	assignment operators
			case 'T_MUL_EQUAL':					#	*=	assignment operators
			case 'T_PLUS_EQUAL':				#	+=	assignment operators
				break;
			
			//Maybe add at some point, but for now raise an error
			case 'T_DEFAULT':					#	default	switch
			case 'T_CONCAT_EQUAL':				#	.=	assignment operators
			case 'T_OR_EQUAL':					#	|=	assignment operators
			case 'T_AND_EQUAL':					#	&=	assignment operators
			case 'T_POW_EQUAL':					#	**=	assignment operators (available since PHP 5.6.0)
			case 'T_SL':						#	<<	bitwise operators
			case 'T_SL_EQUAL':					#	<<=	assignment operators
			case 'T_SPACESHIP':					#	<=>	comparison operators (available since PHP 7.0.0)
			case 'T_SR':						#	>>	bitwise operators
			case 'T_SR_EQUAL':					#	>>=	assignment operators
			case 'T_XOR_EQUAL':					#	^=	assignment operators
			
			//Anything else, raise an error
			default:
				throw new \Twig\Error\Error('Unimplemented token '. $this->type. ' ('. $this->value. ')', $this->line + 1, $source = null);
		}
	}
	
	public function __toString() {
		return $this->space. $this->pre. ($this->outputValue? $this->value : ''). $this->brack. $this->suf;
	}
	
	public function level() {
		return $this->numB. ','. $this->numC. ','. $this->numS;
	}
	
	public function midBlockLevel() {
		return $this->numB. ','. $this->numS;
	}
	
	public function isCurly() {
		return $this->type == 'SYNTAX' && ($this->value == '{' || $this->value == '}');
	}
	
	public function isClosingCurly() {
		return $this->type == 'SYNTAX' && $this->value == '}';
	}
	
	public function isStartOfBlock() {
		switch ($this->type) {
			case 'T_FOR':
			case 'T_IF':
			case 'T_ELSEIF':
			case 'T_ELSE':
				return true;
		}
		
		return false;
	}
}







class phiParser {
	
	
	
	
	public static function runPhi($phiCode, &$outputs, $vars = []) {
		$twigCode = self::phiToTwig($phiCode);
		return \ze\phi::runTwig($twigCode, $outputs, $vars);
	}
	
	public static function carefullyRunPhi($phiCode, &$outputs, $vars = []) {
		$twigCode = self::phiToTwig($phiCode);
		return \ze\phi::carefullyRunTwig($twigCode, $outputs, $vars);
	}
	
	public static function testPhi($phiCode, &$outputs, $vars = []) {
	
		//Attempt to run the code
		$error = false;
		$twigCode = '';
		$result = null;
		
		try {
			\ze::$ers = [];
			$twigCode = self::phiToTwig($phiCode);
			$result = \ze\phi::runTwig($twigCode, $outputs, $vars, true);
	
		//Handle any errors
		} catch (\Twig\Error\Error $e) {
		
			//Get the basic error message
			$message = $e->getRawMessage();
			$i = false;
		
			//Restore the line breaks and run the code again, to get the actual line number of the error
			try {
				$twigCode = self::phiToTwig($phiCode);
				\ze\phi::runTwig($twigCode, $outputs, $vars, true);
			} catch (\Twig\Error\Error $er) {
				$i = $er->getTemplateLine();
			}
			
			if (is_numeric($i)) {
				\ze::$ers[] = 'Error at line '. ($i - 1). ', '. $message;
			} else {
				\ze::$ers[] = $message;
			}
			$error = true;
	
		//Twig misses some errors, so need to add some fallbacks, just so they don't cause the UI to break!
		} catch (\DivisionByZeroError | \Exception $e) {
		
			//Get the basic error message
			$message = $e->getMessage();
			\ze::$ers[] = $message;
			$error = true;
		}
	
		return ['result' => $result, 'error' => $error, 'errors' => \ze::$ers, 'varDumps' => \ze\phi::$varDumps, 'twig' => $twigCode];
	}
	
	
	
	//Use PHP's token_get_all() function to scan the input code and turn it into tokens
	private static function scanTokens($phiCode, $overideLineNumber = false, $lineNumber = 0) {
		$tokens = [];
		$lastLine = 0;
		$lastToken = null;
		foreach (token_get_all('<?php '. $phiCode) as $i => $phpToken) {
			$token = new \ze\phiToken($phpToken, $overideLineNumber? $lineNumber : $lastLine, $i == 0, $overideLineNumber);
			$lastLine = $token->line;
			
			//Watch out for a bug where the "?:" operator does not get parsed properly.
			//It gets recorded as two SYNTAX tokens.
			//Convert the first to an OPERATOR token, and throw the second one away.
			if ($lastToken
			 && $lastToken->type == 'SYNTAX'
			 && $lastToken->value == '?'
			 && $token->type == 'SYNTAX'
			 && $token->value == ':') {
			
				$lastToken->type = 'OPERATOR';
				$lastToken->value = '?:';
				$token->ignore = true;
			}
			
			if (!$token->ignore) {
				$tokens[] = $token;
				$lastToken = $token;
			}
		}
		
		return $tokens;
	}
	
	
	
	public static function phiToTwig($phiCode, $debug = false) {
		
		if (!function_exists('token_get_all')) {
			throw new \Twig\Error\Error('Tokenizer functions are not enabled in PHP, please enable tokenizer.so in your php.ini file.', $line = null, $source = null);
		}
		
		
		$scannedTokens = self::scanTokens($phiCode);
		
		//Look through the tokens that were just scanned
		//Do some basic rewriting on the tokens we have to fix a few issues and add a few features
		$tokens = [];
		$skipNext = false;
		foreach ($scannedTokens as $i => $token) {
			if ($skipNext) {
				$skipNext = false;
			} else {
				$nextToken = $scannedTokens[$i+1] ?? false;
				$replacements = null;
				$addOpenBracket = false;
				
				if ($nextToken) {
					
					//Add some basic support for operators like ++, +=, and so on.
					//Note: This code only adds support for them when used on variables, not object properties.
					//However, the old Phi Parser never supported these either, so this won't cause any issues with existing code
					//being migrated from the old version.
					if ($replacements === null) {
						switch ($token->type. '/'. $nextToken->type) {
							case 'T_STRING/T_DEC':
								$replacements = $token->value. ' = '. $token->value. ' - 1';
								break;
							case 'T_DEC/T_STRING':
								$replacements = $nextToken->value. ' = '. $nextToken->value. ' - 1';
								break;
							case 'T_STRING/T_INC':
								$replacements = $token->value. ' = '. $token->value. ' + 1';
								break;
							case 'T_INC/T_STRING':
								$replacements = $nextToken->value. ' = '. $nextToken->value. ' + 1';
								break;
							case 'T_STRING/T_MINUS_EQUAL':
								$replacements = $token->value. ' = '. $token->value. ' -';
								$addOpenBracket = true;
								break;
							case 'T_MINUS_EQUAL/T_STRING':
								$replacements = $nextToken->value. ' = '. $nextToken->value. ' -';
								$addOpenBracket = true;
								break;
							case 'T_STRING/T_PLUS_EQUAL':
								$replacements = $token->value. ' = '. $token->value. ' +';
								$addOpenBracket = true;
								break;
							case 'T_PLUS_EQUAL/T_STRING':
								$replacements = $nextToken->value. ' = '. $nextToken->value. ' +';
								$addOpenBracket = true;
								break;
							case 'T_STRING/T_DIV_EQUAL':
								$replacements = $token->value. ' = '. $token->value. ' /';
								$addOpenBracket = true;
								break;
							case 'T_DIV_EQUAL/T_STRING':
								$replacements = $nextToken->value. ' = '. $nextToken->value. ' /';
								$addOpenBracket = true;
								break;
							case 'T_STRING/T_MOD_EQUAL':
								$replacements = $token->value. ' = '. $token->value. ' %';
								$addOpenBracket = true;
								break;
							case 'T_MOD_EQUAL/T_STRING':
								$replacements = $nextToken->value. ' = '. $nextToken->value. ' %';
								$addOpenBracket = true;
								break;
							case 'T_STRING/T_MUL_EQUAL':
								$replacements = $token->value. ' = '. $token->value. ' *';
								$addOpenBracket = true;
								break;
							case 'T_MUL_EQUAL/T_STRING':
								$replacements = $nextToken->value. ' = '. $nextToken->value. ' *';
								$addOpenBracket = true;
								break;
							
							//Catch the case where we have "else if" instead of "elseif".
							//This parser doesn't handle "else if" as two tokens, so fix that by
							//just turning the two tokens into an "elseif"
							case 'T_ELSE/T_IF':
								$replacements = 'elseif';
								break;
						}
					}
				
				
					//Watch out for a mis-parse when someone enters ".." in a for-loop.
					//Annoyingly, the token_get_all() function does not correctly detect the two-dot ellipsis that
					//Phi and Twig use in for loops.
					//To work around this, we'll look through all of the tokens that were entered, watch out for two dots next to each other,
					//convert the two dots in a colon, then re-scan that bit of code.
					if ($replacements === null) {
						if (substr($token->value, -1) === '.') {
							if (substr($nextToken->value, 0, 1) === '.') {
								$replacements = substr($token->value, 0, -1). ' : '. substr($nextToken->value, 1);
					
							} elseif (substr($nextToken->value, 0, 2) === '0.') {
								$replacements = substr($token->value, 0, -1). ' : '. substr($nextToken->value, 2);
							}
						}
					}
				}
				
				if ($replacements !== null) {
					foreach (self::scanTokens($replacements, true, $token->line) as $replacmentToken) {
						$tokens[] = $replacmentToken;
					}
					
					//Some of these will need a bracket around them to ensure the correct logic.
					//But store this bracket as a string property on the token, rather than something we'll
					//feed through the parser.
					//We'll add an open bracket here, and add the closing bracket in the right place later in the logic
					//when we know when the statement is supposed to end.
					if ($addOpenBracket) {
						$replacmentToken->brack = '(';
					}
					$skipNext = true;
				
				} else {
					$tokens[] = $token;
				}
			}
		}
		
		//echo '<textarea>', htmlspecialchars(print_r($tokens, true)), '</textarea>';		
		
		//Loop through the tokens, setting how deep they are inside various brackets.
		//The bracket levels will be used to connect things together later.
		$numB = 0;
		$numC = 0;
		$numS = 0;
		foreach ($tokens as $i => $token) {
			if ($token->type == 'SYNTAX') {
				switch ($token->value) {
					case ')':
						$numB = max(0, $numB - 1);
						$token->isClose = true;
						break;
					case '}':
						$numC = max(0, $numC - 1);
						$token->isClose = true;
					case ']':
						$numS = max(0, $numS - 1);
						$token->isClose = true;
						break;
				}
			}
			$token->numB = $numB;
			$token->numC = $numC;
			$token->numS = $numS;
			if ($token->type == 'SYNTAX') {
				switch ($token->value) {
					case '(':
						++$numB;
						$token->isOpen = true;
						break;
					case '{':
						++$numC;
						$token->isOpen = true;
						break;
					case '[':
						++$numS;
						$token->isOpen = true;
						break;
				}
			}
		}
		
		
		
		//Attempt to group the tokens into statements.
		//For example, "a = 1 b = 2" should be parsed as two statements, "a = 1" and "b = 2".
		$statements = [];
		$statement = [];
		$statementStart = 0;
		$settingArrayKey = false;
		foreach ($tokens as $i => $token) {
			$prevToken = $tokens[$i-1] ?? false;
			$nextToken = $tokens[$i+1] ?? false;
			
			//Check if this token is the start of a new statement
			$isNewStatement = !$prevToken || empty($statement);
			
			//Keep adding tokens into each statement
			$statement[] = $token;
			
			//Try to catch the start and end of statements
			$isEndOfStatement = false;
			
			//The code at the end will always be the end of a statement by definition
			if (!$nextToken) {
				$isEndOfStatement = true;
			}
			
			//Curly brackets being used to indicate code blocks should not be wrapped onto
			//another statement, and kept separate
			if ($isNewStatement && $token->isCurly()) {
				$isEndOfStatement = true;
			}
			
			//Tokens like if, for, else, continue and break should trigger a break in statements
			if ($nextToken && $nextToken->breaker) {
				$isEndOfStatement = true;
			}
			
			//Some rules for starting a new statement.
			if (!$isNewStatement
			 && !$isEndOfStatement
			 && $prevToken
			 && $nextToken
				//Statements must be at least 3 tokens long (except for a couple of exceptions as noted above and below).
			 && $statementStart <= $i - 2
				//Statements must start with a word-character, so we can't end a statement until we see one coming up next.
				//One exception: statements should also be ended if a curly bracket is coming up.
			 && ($nextToken->type == 'T_STRING' || $nextToken->isCurly())
				//We can't break a statement on an operator.
			 && $token->type != 'ASSIGN'
			 && $token->type != 'OPERATOR'
				//Don't end a statement if we're just about to open or close brackets.
				//For example "a = b(c + d)" should not be parsed as "a = b" and "(c + d)".
				//Statements can only end if this bit of the statement is on the same level as the next bit.
			 && ($token->level() == $nextToken->level() || ($nextToken->isCurly() && $token->midBlockLevel() == $nextToken->midBlockLevel()))
				//If some brackets are currently open, we can't end a statement.
				//For example "a = b(c + d)" should not be parsed as "a = b(c".
				//Only allow a statement to finish when the bracket level is equal to whatever
				//the level was at the start of the statement for this code block.
			 && $token->level() == $statement[0]->level()
			 && ($nextToken->level() == $statement[0]->level() || ($nextToken->isCurly() && $token->midBlockLevel() == $nextToken->midBlockLevel()))
			 ) {
				$isEndOfStatement = true;
			}
		
			if ($isNewStatement) {
				//Add the Twig prefix needed for this statement, and handle some special logic for if, elseif and fors.
				switch ($token->type) {
					case 'T_FOR':
						$token->pre .= '{% for ';
						$token->outputValue = false;
						break;
					case 'T_IF':
						$token->pre .= '{% if ';
						$token->outputValue = false;
						break;
					case 'T_ELSEIF':
						$token->pre .= '{% elseif ';
						$token->outputValue = false;
						break;
					case 'T_ELSE':
						$token->pre .= '{% else ';
						$token->outputValue = false;
						$isEndOfStatement = true;
						break;
			
					//Add continue and break statements
					case 'T_BREAK':
						$token->pre .= '{% break ';
						$token->outputValue = false;
						$isEndOfStatement = true;
						break;
					case 'T_CONTINUE':
						$token->pre .= '{% continue ';
						$token->outputValue = false;
						$isEndOfStatement = true;
						break;
					
					default:
						if ($token->isCurly()) {
							//Don't output curly brackets, they're npot valid Twig code.
							//We'll need to convert them into something such as "{% endfor %}"
							// or "{% endif %}" a bit later.
							$token->outputValue = false;
						
						} else {
							//If this looks like a function call, e.g. to setValue(), then use the "do" command in Twig.
							//Otherwise assume it's a variable assignment and use "set".
							if ($nextToken
							 && $nextToken->type == 'SYNTAX'
							 && $nextToken->value == '(') {
			
								$token->pre .= '{% do ';
							} else {
								$token->pre .= '{% set ';
							}
						}
				}
				
				$statementStart = $i;
			}
			
			
			if ($isEndOfStatement) {
				//Watch out for the end of if and for tuples, and add the closing tag that Twig needs.
				if (!($isNewStatement && $token->isCurly())) {
					$token->suf .= ' %}';
				}
				$statements[] = $statement;
				$statement = [];
			}
		}
		
		if (!empty($statement)) {
			$statements[] = $statement;
			$statement = [];
		}
		
		
		//Watch out for people trying to assign array keys.
		//This isn't possible in Twig, but we have a hack here to make it work.
		$tokensNeedRebuilding = false;
		foreach ($statements as $si => $statement) {
			
			//First, watch out for a statement that starts with something of the form "variable[".
			if (isset($statement[2])
			 && $statement[0]->type == 'T_STRING'
			 && $statement[1]->type == 'SYNTAX'
			 && $statement[1]->value == '[') {
				
				//Next, scan forward a bit.
				//Pay special attention every time we see a "[", a "]", or a "=" at
				//the same level as the opening bracket.
				$cs = count($statement);
				$brackStart = $statement[1];
				
				$key = null;
				$keys = [];
				
				for ($ti = 2; $ti < $cs; ++$ti) {
					$brackEnd = $statement[$ti - 1];
					$assign = $statement[$ti];
					
					//We'll want to note down the fragments of code inbetween each square brackets.
					//These are the object properties/array keys being referenced.
					//If we see an empty set of square brackets (i.e. "[]"), then we'll store a null
					//to represent that.
					$isSquareOpen = 
						$brackEnd->type == 'SYNTAX'
					 && $brackEnd->value == '['
					 && $brackEnd->numB == $brackStart->numB
					 && $brackEnd->numC == $brackStart->numC
					 && $brackEnd->numS == $brackStart->numS;
					$isSquareClose = 
						$brackEnd->type == 'SYNTAX'
					 && $brackEnd->value == ']'
					 && $brackEnd->numB == $brackStart->numB
					 && $brackEnd->numC == $brackStart->numC
					 && $brackEnd->numS == $brackStart->numS;
					
					if ($isSquareOpen) {
						$key = '';
					
					} elseif ($isSquareClose) {
						if ($key === '') {
							$keys[] = 'null';
						
						} elseif ($key !== null) {
							$keys[] = $key;
						}
						$key = null;
					
					} elseif ($key !== null) {
						$key .= $brackEnd->value;
					}
					
					//If we see a "] =" in the code, that's the trigger to start using this hack.
					//Take the existing statement and rewrite it into a call to the _zPhiSAK_() function.
					if ($isSquareClose && $assign->type == 'ASSIGN') {
						
						$replacements = '_zPhiSAK_('. $statement[0]->value. ', '. implode(', ', $keys). ', ';
						
						for ($j = $ti + 1; $j < $cs; ++$j) {
							$replacements .= $statement[$j]->value;
						}
						$replacements .= ')';
						
						//Note that a little more than this will need to be done, as Twig doesn't support
						//functions with arguements that are passed by reference.
						//There is a preg_replace in the Zenario_Phi_Twig_Cache class to catch and fix this!
						
						//Replace the previous statement with this new one
						$statements[$si] = self::scanTokens($replacements, true, $brackStart->line);
						
						//This hack involves calling a function to do the logic, so
						//turn this into a "do" statement, rather than a "set" statement.
						$statements[$si][0]->pre .= '{% do ';
						$statements[$si][count($statements[$si]) - 1]->suf .= ' %}';
						
						$tokensNeedRebuilding = true;
						continue 2;
					}
					//Note - if the trigger in the if() statement above is never met,
					//then we don't actually have a match, so don't commit to any of the changes!
				}
			}
		}
		
		//Rebuild the $tokens array, if needed due to changes above
		if ($tokensNeedRebuilding) {
			$tokens = [];
			foreach ($statements as $statement) {
				foreach ($statement as $token) {
					$tokens[] = $token;
				}
			}
		}
		
		
		
		
		//Loop through the individual statements, looking for statements that needed surrounding in brackets
		foreach ($statements as $statement) {
			//We've already put the opening brackets on, so just watch out for those
			$hadOpen = false;
			foreach ($statement as $token) {
				if ($token->brack === '(') {
					$hadOpen = true;
				}
			}
			//If we saw an open bracket, put a closing bracket on the end
			if ($hadOpen) {
				$token->brack .= ')';
			}
		}
		
		
		//We'll need to do a bit or parsing, trying to work out how each statement fits into which blocks of code.
		//Then we'll need to go around matching the start and ends of for/if/else/elseif code blocks up.
		//Loop through all of the statements, paying special attention to the tokens at the start and the end.
		$openLevels = [];
		foreach ($statements as $i => $statement) {
			$firstToken = $statement[0];
			$secondToken = $statement[1] ?? false;
			$lastToken = $statement[count($statement) - 1];
			$nextFirstToken = $statements[$i+1][0] ?? false;
			
			if ($nextFirstToken) {
				$nextEndToken = $statements[$i+1][count($statements[$i+1]) - 1];
			} else {
				$nextEndToken = false;
			}
			
			
			//Watch out for syntax such as "for (a in 1:10)"
			//In Twig, the colon needs to be two dots. Convert the value of the token if we see a colon
			//at the base level inside the tuple of a for-statement.
			if ($firstToken->type == 'T_FOR') {
				foreach ($statement as $token) {
					if ($token->type == 'SYNTAX'
					 && $token->value == ':'
					 && $token->numB == $firstToken->numB + 1
					 && $token->numC == $firstToken->numC
					 && $token->numS == $firstToken->numS) {
						$token->value = '..';
					}
				}
			}
			
			//In Twig, you don't show the round brackets in the tuple of the for/if conditions.
			//Try to hide them so we don't get invalid syntax
			if ($firstToken->isStartOfBlock()) {
				if ($secondToken
				 && $secondToken->type == 'SYNTAX'
				 && $secondToken->value == '(') {
					$secondToken->outputValue = false;
				}
				if ($lastToken
				 && $lastToken->type == 'SYNTAX'
				 && $lastToken->value == ')') {
					$lastToken->outputValue = false;
				}
			}
			
			//Look for the opening statements of for/if/else/elseif code blocks
			if ($nextFirstToken) {
				if ($firstToken->isStartOfBlock()) {
					if ($nextFirstToken->isCurly()) {
						//For each we find, note down the bracket level,
						//and what type of block this was when it started.
						$openLevels[$firstToken->numC] = $firstToken->type;
					
					} else {
						//Catch the case where there is only one statement after a for/if/else/elseif.
						//(I.e. no { and }s, just one line of code after the for/if/else/elseif).
						//Handle this by cloasing them straight away, by adding an "{% endfor %}" or 
						//an "{% endif %}" straight after the next statement.
						//Note: I'm only supporting one level of nesting here. I've not implemented
						//support for something like "if (a) if (b) c()" where someone attempts to
						//chain these.
						if ($firstToken->type == 'T_FOR') {
							$nextEndToken->suf .= '{% endfor %}';
						
						//Note we shouldn't add an "{% endif %}" just before an "else"
						} elseif (!($nextFirstToken->type == 'T_ELSE' || $nextFirstToken->type == 'T_ELSEIF')) {
							$nextEndToken->suf .= '{% endif %}';
						}
					}
				}
				
				//Small optional fix. Catch the case where token_get_all() didn't give us the correct line number
				//for the closing curly of a block
				if ($nextFirstToken->isClosingCurly()
				 && $nextFirstToken->line == $lastToken->line) {
					++$nextFirstToken->line;
				}
			}
			
			//Look for the closing "}" of for/if/else/elseif code blocks.
			if ($firstToken->isClosingCurly()) {
				//Check it matches something that was just opened.
				//(Maybe to do: raise an error if we don't find one..?)
				if (isset($openLevels[$firstToken->numC])) {
					$openingTokenType = $openLevels[$firstToken->numC];
					
					//Add the Twig tag to close the if or the for.
					if ($openingTokenType == 'T_FOR') {
						$lastToken->suf .= '{% endfor %}';
					
					//Note we shouldn't add an "{% endif %}" just before an "else"
					} elseif (!($nextFirstToken && ($nextFirstToken->type == 'T_ELSE' || $nextFirstToken->type == 'T_ELSEIF'))) {
						$lastToken->suf .= '{% endif %}';
					}
					
					unset($openLevels[$firstToken->numC]);
				}
			}
		}
		
		
		//Restore some of the whitespace
		//Not all of this is technically needed, but it makes debugging much easier
		//if the line numbers in the Twig code match up to the original numbers in
		//the Phi code.
		$line = 0;
		foreach ($tokens as $i => $token) {
			$prevToken = $tokens[$i-1] ?? false;
			$nextToken = $tokens[$i+1] ?? false;
			
			//Keep the line number of the code we're writing in step with the output
			while ($line < $token->line) {
				$token->space = "\n". $token->space;
				++$line;
			}
			
			//Add a space between most tokens
			if ($prevToken
			 && $prevToken->rSpace
			 && $token->lSpace
			 && $token->space === '') {
				$token->space .= ' ';
			}
		}
		
		
		
		
		if ($debug) {
			echo '<textarea>', htmlspecialchars(print_r($tokens, true)), '</textarea>';
			echo '<textarea>', htmlspecialchars(print_r($statements, true)), '</textarea>';
		}

		
		
		$twig = '';
		foreach ($tokens as $i => $token) {
			$twig .= $token;
		}
		
		if ($debug) {
			echo '<textarea>', htmlspecialchars($twig), '</textarea>';
		}
		
		return $twig;
	}
	
	
	
	

}