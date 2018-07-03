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

namespace ze;

class phiParser {
	
	
	
	
	public static function runPhi($phiCode, &$outputs, $vars = [], $allowFunctions = false) {
		$twigCode = self::phiToTwig($phiCode, $allowFunctions);
		return \ze\phi::runTwig($twigCode, $outputs, $vars);
	}
	
	public static function carefullyRunPhi($phiCode, &$outputs, $vars = [], $allowFunctions = false) {
		$twigCode = self::phiToTwig($phiCode, $allowFunctions);
		return \ze\phi::carefullyRunTwig($twigCode, $outputs, $vars);
	}
	
	public static function testPhi($phiCode, &$outputs, $vars = [], $allowFunctions = false) {
	
		//Attempt to run the code
		$error = false;
		$twigCode = '';
		$result = null;
		
		try {
			$twigCode = self::phiToTwig($phiCode, $allowFunctions);
			$result = \ze\phi::runTwig($twigCode, $outputs, $vars, true);
	
		//Handle any errors
		} catch (\Twig_Error $e) {
		
			//Get the basic error message
			$message = $e->getRawMessage();
			$i = false;
		
			//Restore the line breaks and run the code again, to get the actual line number of the error
			try {
				$twigCode = self::phiToTwig($phiCode, $allowFunctions, $preserveLineBreaks = true);
				\ze\phi::runTwig($twigCode, $outputs, $vars, true);
			} catch (\Twig_Error $er) {
				$i = $er->getTemplateLine();
			}
			
			if (is_numeric($i) && isset(self::$lines[$i-1])) {
				$error = 'Error at line '. (self::$lines[$i-1]->lineNumber + 1). ', "'. self::$originalLines[self::$lines[$i-1]->lineNumber]. '": '. $message;
			} else {
				$error = $message;
			}
		}
	
		return ['result' => $result, 'error' => $error, 'varDumps' => \ze\phi::$varDumps, 'twig' => $twigCode];
	}
	
	
	
	public static function phiToTwig($phiCode, $allowFunctions = false, $preserveLineBreaks = false) {
		
		if (!function_exists('token_get_all')) {
			throw new \Twig_Error('Tokenizer functions are not enabled in PHP, please enable tokenizer.so in your php.ini file.', $line = null, $source = null);
		}
		
		//Clear any variables from last time
		self::$lines = [];
		self::$openControls = [];
		self::$knownFunctions = [];
		self::$allowFunctions = $allowFunctions;
		self::$final = false;
		
		//Convert the code to an array of lines
		self::$originalLines = preg_split('/\R/', $phiCode);


		//Strip any comments out
		if (strpos($phiCode, '#') !== false
		 || strpos($phiCode, '//') !== false
		 || strpos($phiCode, '/*') !== false) {
	
			//Add some dummy text at the start of each line to stop php_strip_whitespace() from removing line breaks
			$phiCode = implode("\n". '_zPhiFlagNewLine_();', self::$originalLines);
			
			//Call php_strip_whitespace()
			$tempfile = tempnam(sys_get_temp_dir(), 'cod');
			file_put_contents($tempfile, '<?php '. $phiCode. '/**/');
			$phiCode = substr(php_strip_whitespace($tempfile), 6);
			unlink(($tempfile));
			
			//Remove the dummy text and convert back to an array of lines
			self::$originalLines = explode('_zPhiFlagNewLine_();', $phiCode);
		}
		
		
		foreach (self::$originalLines as $i => $originalLines) {
			

			//tokenize the line
			$tokens = token_get_all('<?php '. $originalLines);
			array_shift($tokens);
			$start = 0;
			$curleyBracketsOpen = 0;
			$statementStarted = false;
		
			//Strip out any of the meta-data that token_get_all() gave us, we're only interested in the string-value
			foreach ($tokens as $ti => &$token) {
				if (!is_string($token)) {
					$token = $token[1];
				}
			}
			
			//Attempt to split the line up into multiple lines if semi-colons or curlies that are not part of statements are used
			foreach ($tokens as $ti => &$token) {
				
				switch (trim($token)) {
					case '{':
						if ($statementStarted) {
							//If we're in a statement, keep track of the number of curlies open
							++$curleyBracketsOpen;
							break;
						}
					
					case '}':
						if ($statementStarted && $curleyBracketsOpen > 0) {
							//Keep track of how many curlies close
							//They're still part of the statement while they match open curlies that were also part of the statement
							--$curleyBracketsOpen;
							break;
						}
					
					case ';':
						//Look for semi-colons, and curlies that are not part of statements, and add an artificial line-break if we see them
						new \ze\phiParser($i, implode('', array_slice($tokens, $start, $ti - $start)));
						$start = $ti;
						$statementStarted = false;
						break;
					
					//Ignore if/else/for loops should not be flagged as the start of a statement
					case '':
					case 'if':
					case 'elseif':
					case 'else':
					case 'for':
						break;
					
					//Look for things that are not line wrappers and flag that we've reached a statement
					default:
						$statementStarted = true;
				}
			}
			
			$lastBit = implode('', array_slice($tokens, $start));
			if (trim($lastBit) != '') {
				new \ze\phiParser($i, $lastBit);
			}
		}

		

		foreach (self::$lines as $i => &$line) {
			$line->parseExpression();
		}
		foreach (self::$lines as $i => &$line) {
			$line->parseExpression2();
			self::$parsedLines[$i] = $line->parsed;
		}
		
		return implode(self::$parsedLines, $preserveLineBreaks? "\n" : '');
	}
	
	
	
	
	
	private static $lines = [];
	private static $originalLines = [];
	private static $parsedLines = [];
	private static $openControls = [];
	private static $knownFunctions = [];
	private static $allowFunctions = false;
	private static $final = false;
	
	
	
	public $lineNumber = 0;
	public $i = 0;
	public $prev = false;
	public $next = false;
	
	
	public $isFun = false;
	public $isFor = false;
	public $isIf = false;
	public $isElse = false;
	public $isElseif = false;
	public $isExpression = false;
	public $isBreakOrContinue = false;
	public $isEmpty = false;
	
	public $openedCurliesRight = 0;
	public $closedCurliesRight = 0;
	
	public $parsed = '';
	
	public function isControlBlock() {
		return $this->isIf || $this->isFor || $this->isFun;
	}
	public function controlName() {
		return $this->isIf? 'if' : ($this->isFor? 'for' : ($this->isFun? 'macro' : ''));
	}
	
	//new \ze\phiParser
	protected function __construct($lineNumber, $line, $useBrackets = true) {
		
		$line = trim($line, $character_mask = " \t\n\r\0\x0B". ';');
		$matches = [];
		
		
		//Try to catch control statements with their next line of code immediately after them
		if (preg_match('@^([\{\}\s]*for\s*\(.*?\s*in\s*.*?\s*\:\s*.*?\)|for\s*\(.*?\s*in\s*.*?\)|(if|elseif|else if)\s*\(.*?\)|else|continue|next|break|function\s*\w+\s*\(.*?\)|\w+\s*(=|\<-)\s*function\s*\(.*?\))(\s*[\{\}]+\s*[^\{\}\s\;]+.*)$@i', $line, $matches)) {
			//Deal with this case by splitting the line into two lines
			new \ze\phiParser($lineNumber, trim($matches[1]));
			$line = trim($matches[4]);
		}
		
		//Try to catch statements such as "x = y = 0"
		if (preg_match('@^([\{\}\s]*)([\[\]"\'\.\w-]+)\s*(\=|\<\-)\s*([\[\]"\'\.\w-]+)\s*(\=|\<\-)(.*?)([\{\}\s]*)$@', $line, $matches)) {
			//Split them into a two-line version of whatever we just had
			new \ze\phiParser($lineNumber, ($useBrackets? '{' : ''). trim($matches[1]. $matches[4]. ' '. $matches[5]. $matches[6]), false);
			$line = trim($matches[2]. ' '. $matches[3]. ' '. $matches[4]. $matches[7]). ($useBrackets? '}' : '');
		}
		
		
		$this->lineNumber = $lineNumber;
		$this->i = $i = count(self::$lines);
		
		
		//tokenize the line
		$tokens = token_get_all('<?php '. $line);
		array_shift($tokens);
		$bracketsOpen = 0;
		$curleyBracketsOpen = 0;
		$functionOpenedAtBracket = [];
		$statementStarted = false;
		$fi = -1;
		$li = count($tokens);
		$code = '';
		
		//Strip out any of the meta-data that token_get_all() gave us, we're only interested in the string-value
		foreach ($tokens as $ti => &$token) {
			if (!is_string($token)) {
				$token = $token[1];
			}
			
			//Keep track of where the first and last non-empty tokens are on the line,
			//that are not curley brackets or semi-colons
			if (!\ze::in(trim($token), '', '{', '}', ';')) {
				$li = $ti;
				
				if ($fi === -1) {
					$fi = $ti;
				}
			}
			
			//Watch out for people trying to access _self or the _zPhi..._ functions
			if ($token == '_self'
			 || preg_match('@_zPhi\w+_@i', $token)) {
				self::$lines[$i] = &$this;
				throw new \Twig_Error('Reserved word "'. $token. '" was used.', $lineNumber = null, $source = null);
					//N.b. the $lineNumber of the error doesn't seem to be working properly here, it's often wrong or missing,
					//so I've removed it to not show something with the wrong value
			
			//Convert hex to decimal as Twig doesn't support hex
			} elseif (preg_match('@^0x([0-9a-f]+)$@i', $token, $matches)) {
				$token = (string) hexdec($matches[1]);
			
			//Catch missing zeros before decimal places as Twig doesn't like these
			} elseif (preg_match('@^\.\d+$@', $token, $matches)) {
				if (!isset($tokens[$ti-1]) || trim($tokens[$ti-1]) === '') {
					$token = '0'. $token;
				}
			
			//Also convert numbers in exponent form as Twig doesn't support them either
			} elseif (preg_match('@^(\d*\.?\d*)e([\+\-]?)(\d+)$@i', $token, $matches)) {
				if ($matches[1][0] == '.') {
					$matches[1] = '0'. $matches[1];
				}
				
				$token = '('. $matches[1]. ' * (10 ** '. ($matches[2] === '-'? '-' : ''). $matches[3]. '))';
			}
		}
		
		//loop through each token, checking it
		foreach ($tokens as $ti => &$token) {
			
			$nextToken = false;
			$nextNextToken = false;
			$nextNonEmptyToken = false;
			if (isset($tokens[$ti+1])) {
				$nextToken = $tokens[$ti+1];
			}
			if (isset($tokens[$ti+2])) {
				$nextNextToken = $tokens[$ti+2];
			}
			if ($nextToken !== false
			 && trim($nextToken) !== '') {
				$nextNonEmptyToken = $nextToken;
			} else {
				$nextNonEmptyToken = $nextNextToken;
			}
			
			//Catch the case where someone copies something like 'array[1, 2, 3]' or 'object{"a": "b"}'
			//from the debug window.
			//Remove the label at the start to make this a valid input
			if (($token == 'array' && $nextNonEmptyToken == '[')
			 || ($token == 'object' && $nextNonEmptyToken == '{')) {
				continue;
			}
			
			//As soon as we see something that's not a line wrapper, flag that we're started a statement
			if (!\ze::in(trim($token), '', '{', '}', ';')) {
				$statementStarted = true;
			}
			
			switch ($token) {
				case 'block':
				case 'constant':
				case 'include':
				case 'parent':
				case 'source':
				case 'template_from_string':
					if ($nextNonEmptyToken == '(') {
						self::$lines[$i] = &$this;
						throw new \Twig_Error('Reserved function "'. $token. '" was used.', $lineNumber = null, $source = null);
							//N.b. the $lineNumber of the error doesn't seem to be working properly here, it's often wrong or missing,
							//so I've removed it to not show something with the wrong value
					}
					break;
					
				case '=':
					//$statementStarted = true;
					break;

				case '<':
					//if ($nextToken == '-') {
					//	$statementStarted = true;
					//}
					break;

				//Convert && to and
				case '&&':
					$token = 'and';
					break;

				//Convert || to not
				case '||':
					$token = 'or';
					break;

				//Convert ! to not
				case '!':
					$token = 'not ';
					break;

				//Convert xor to b-xor
				case 'xor':
					$token = 'b-xor ';
					break;

				//Convert %% to %
				case '%':
					if ($nextToken == '%') {
						$token = '';
					}
					break;

				//Convert ^ to **
				case '^':
					$token = '**';
					break;
				
				//If we see curley brackets after an assignment, e.g. something like "a = {b: 'c'}", that's an object definition,
				//and we'll need to make sure we don't remove those.
				//Count the number of curlies that are currently open
				case '{':
					if ($statementStarted) {
						++$curleyBracketsOpen;
						break;
					}

				case '}':
					if ($statementStarted && $curleyBracketsOpen > 0) {
						//Add some non-whitespace after object definitions to ensure we don't remove their closing curley bracket
						$token .= '_zPhiFlagCurleyToKeep_';
						--$curleyBracketsOpen;
						break;
					}
				
				case ';':
					//Excluding for object definitions above, watch out for "{"s, "}"s and ";"s in the middle of a line
					if ($fi != -1
					 && $fi < $ti
					 && 	  $ti < $li
					) {
						throw new \Twig_Error('Multiple statements found on the same line.', $i + 1, $source = null);
					}
					break;

				//count the number of brackets that are currently open
				case '(':
					++$bracketsOpen;
					break;

				case ')':
					--$bracketsOpen;
				
					//If we see a custom function being closed, add a second closing bracket to account for it being wrapped in a call to _zPhiGetRV_()
					if (isset($functionOpenedAtBracket[$bracketsOpen])) {
						$code .= ')';
						unset($functionOpenedAtBracket[$bracketsOpen]);
					}
					break;
				
				default:
					if ($nextNonEmptyToken == '(') {
						if ($token == 'return') {
							$functionOpenedAtBracket[$bracketsOpen] = true;
							$token = '_zPhiR_(_zPhiRV_';
						
						} elseif (!empty(self::$knownFunctions[$token])) {
							$functionOpenedAtBracket[$bracketsOpen] = true;
							$code .= '_zPhiGetRV_(_self.';
						}
					}
			}

			$code .= $token;
		}

		$line = $code;
		
		
		
		
		//Look for curley brackets opened or closed at the start or end of this line
		$openedCurliesLeft =
		$closedCurliesLeft =
		$openedCurliesRight =
		$closedCurliesRight = 0;
		
		while (preg_match('@^([\{\}])\s*(.*?)$@i', $line, $matches)) {
			if ($matches[1] == '{') {
				++$openedCurliesLeft;
			} else {
				++$closedCurliesLeft;
			}
			
			$line = $matches[2];
		}
		
		while (preg_match('@^(.*?)\s*([\{\}])$@i', $line, $matches)) {
			$line = $matches[1];
			
			if ($matches[2] == '{') {
				++$openedCurliesRight;
			} else {
				++$closedCurliesRight;
			}
		}
		
		//Remove the _zPhiFlagCurleyToKeep_ flag if it was set
		$line = str_replace('_zPhiFlagCurleyToKeep_', '', $line);
		
		
		if ($line == '') {
			//empty lines
			$this->isEmpty = true;
		
		//Examples of for-loops in Twig:
			//{% for i in 0..10 %}
			//{% for user in users %}
			//{% for key, user in users %}
		
		//Look for for loops. Try to accept both Twig and PHP formats of loop
		} elseif (preg_match('@^for\s*\((.*?)\s+in\s+(.*?)\s*\:\s*(.*?)\)$@i', $line, $matches)) {
			$this->isFor = true;
			$line = '{% for '. $matches[1]. ' in '. $matches[2]. '..'. $matches[3]. ' %}';
	
		} elseif (preg_match('@^for\s*\((.*?)\s+in\s+(.*?)\)$@i', $line, $matches)) {
			$this->isFor = true;
			$line = '{% for '. $matches[1]. ' in '. $matches[2]. ' %}';
	
		} elseif (preg_match('@^foreach\s*\((.*?)\s+as\s+(.*?)\s*=>\s*(.*?)\)$@i', $line, $matches)) {
			$this->isFor = true;
			$line = '{% for '. $matches[2]. ', '. $matches[3]. ' in '. $matches[1]. ' %}';
	
		} elseif (preg_match('@^foreach\s*\((.*?)\s+as\s+(.*?)\)$@i', $line, $matches)) {
			$this->isFor = true;
			$line = '{% for '. $matches[2]. ' in '. $matches[1]. ' %}';
	
		} elseif (preg_match('@^(if|elseif|else if)\s*\((.*?)\)$@i', $line, $matches)) {
			$this->isIf = true;
			$this->isElse =
			$this->isElseif = $matches[1] !== 'if';
			$line = '{% '. ($this->isElseif? 'elseif' : 'if'). ' '. $matches[2]. ' %}';
	
		} elseif (preg_match('@^else$@i', $line, $matches)) {
			$this->isIf = true;
			$this->isElse = true;
			$line = '{% else %}';
	
		} elseif (preg_match('@^(continue|next)$@i', $line, $matches)) {
			$this->isBreakOrContinue = true;
			$line = '{% continue %}';
	
		} elseif (preg_match('@^break$@i', $line, $matches)) {
			$this->isBreakOrContinue = true;
			$line = '{% break %}';
	
		} else
		if ((preg_match('@^function\s*(\w+)(\s*)\((.*?)\)$@i', $line, $matches))
		 || (preg_match('@^(\w+)\s*(=|\<-)\s*function\s*\((.*?)\)$@i', $line, $matches))) {
			if (self::$allowFunctions) {
				$this->isFun = true;
				self::$knownFunctions[$matches[1]] = true;
				$line = '{% macro '. $matches[1]. ' ('. $matches[3]. ') %}';
			} else {
				throw new \Twig_Error('Function definitions are not allowed here.', $i + 1, $source = null);
			}
	
		} else {
			$this->isExpression = true;
		}
		
		self::$lines[$i] = &$this;
		
		//Set up the next/prev/final pointers
		//(N.b. these ignore non-empty lines)
		$j = $i;
		while (--$j >= 0) {
			if (!$this->isEmpty) {
				self::$lines[$j]->next = &$this;
			}
			if (!self::$lines[$j]->isEmpty) {
				$this->prev = &self::$lines[$j];
				break;
			}
		}
		if (!$this->isEmpty) {
			self::$final = &$this;
		}
		
		
		//Track curly brackets
		//echo "\n". $line, "\n". $openedCurliesLeft, '-', $closedCurliesLeft, '-', $openedCurliesRight, '-', $closedCurliesRight;
		$this->openedCurliesRight = $openedCurliesRight;
		$this->closedCurliesRight = $closedCurliesRight;
		
		//Put any curlies on the left on the right of the previous line
		if ($openedCurliesLeft) {
			if ($this->prev) {
				$this->prev->openedCurliesRight += $openedCurliesLeft;
			//} else {
			//	throw new \Twig_Error('Opening "{" found without any code before it.', $i + 1, $source = null);
			}
		}
		if ($closedCurliesLeft) {
			if ($this->prev) {
				$this->prev->closedCurliesRight += $closedCurliesLeft;
			} else {
				throw new \Twig_Error('Closing "{" found without an opening "{" before it.', $i + 1, $source = null);
			}
		}
		
		//Catch the case where there are ifs/fors without curley brackets, then an expression immediately afterwards
		//Deal with this by giving both them and the expression curlies
		if ($this->isExpression || $this->isBreakOrContinue) {
			$prev = &$this;
			while ($prev->prev && $prev->prev->isControlBlock() && !$prev->prev->openedCurliesRight) {
				$prev = &$prev->prev;
				$prev->openedCurliesRight = 1;
				++$this->closedCurliesRight;
			}
		}
		
		$this->parsed = $line;
	}
	
	public $p_end = '';
	public $p_isEndIf = false;
	public $p_isEndIfOrFor = false;
	public $p_inFunction = false;
	public $p_isLastLineOfFunction = false;
	public $p_set = false;
	public $p_setArrayKey = false;
	public $p_setNewArrayKey = false;
	public $p_expression = false;
	public $p_doReturn = false;
	public $p_noteResultForReturn = false;
	public static $p_firstLineOfFunction = false;
	
	public function parseExpression() {
		
		//Track how many curly brackets were just opened, and what opened them
		//N.b. you don't need to write them out in Twig, but you do still need to track them
		for ($c = 0; $c < $this->openedCurliesRight; ++$c) {
			$control = $this->controlName();
			
			if ($control == 'macro') {
				
				if (!empty(self::$openControls)) {
					foreach (self::$openControls as $otherControl) {
						switch ($otherControl) {
							case 'if':
								throw new \Twig_Error('Function definition found inside an if statement.', $this->i + 1, $source = null);
							case 'for':
								throw new \Twig_Error('Function definition found inside a for statement.', $this->i + 1, $source = null);
							case 'macro':
								throw new \Twig_Error('Function definition found inside another function.', $this->i + 1, $source = null);
						}
					}
				}
				
				self::$p_firstLineOfFunction = $this->i;
			}
			
			self::$openControls[] = $control;
			break;
		}
		
		$this->p_inFunction = self::$allowFunctions && in_array('macro', self::$openControls);
		
		//Track how many curly brackets were just closed, and which ones were currently open
		//Annoyingly in Twig we need to find the matching statement and check whether it's an if or a for.
		for ($c = 1; $c <= $this->closedCurliesRight; ++$c) {
			if ($control = array_pop(self::$openControls)) {
				
				if (!($control == 'if' && $c == $this->closedCurliesRight && $this->next && $this->next->isElse)) {
					$this->p_end .= '{% end'. $control. '%}';
				}
				
				if ($control == 'if') {
					$this->p_isEndIf = true;
					$this->p_isEndIfOrFor = true;
				
				} elseif ($control == 'for') {
					$this->p_isEndIfOrFor = true;
				
				} elseif ($control == 'macro') {
					$this->p_isEnd = true;
					$this->p_isLastLineOfFunction = true;
				}
			}
		}
		
		
		if ($this->isExpression) {
			
			//Operators such as +=, -=, and so on. Not supported in twig so we need to rewrite them as a = a + b;
			if (preg_match('@^([\[\]"\'\.\w-]+)\s*(\+|\-|\*|\/|\%|\^|\*\*)=\s*(.*)$@', $this->parsed, $matches)) {
				$this->p_set = $matches[1];
				$this->p_expression = $matches[1]. ' '. ($matches[2] == '^'? '**' : $matches[2]). ' ('. $matches[3]. ')';
		
			//Operators such as ++ and -- are also not supported so we need to rewrite them as well
			//(Note this means they will only work on one line, and not as part of another statement)
			} elseif (preg_match('@^(\w+)(\+\+|\-\-)$@', $this->parsed, $matches)) {
				$this->p_set = $matches[1];
				$this->p_expression = $matches[1]. ' '. $matches[2][0]. ' 1';
			
			} elseif (preg_match('@^(\+\+|\-\-)(\w+)$@', $this->parsed, $matches)) {
				$this->p_set = $matches[2];
				$this->p_expression = $matches[2]. ' '. $matches[1][0]. ' 1';
		
			//Assignment statement, e.g. a = b;
			} elseif (preg_match('@^([\[\]"\'\.\w-]+)\s*(=|\<-)\s*([^=].*)$@', $this->parsed, $matches)) {
				
				if (!preg_match('@^\w*$@', $matches[1])) {
					//Twig doesn't support setting array keys, so we'll need to use a hack to work around this
					if (substr($matches[1], -2) == '[]') {
						$this->p_setNewArrayKey = substr($matches[1], 0, -2);
					} else {
						$this->p_setArrayKey = $matches[1];
					}
				} else {
					$this->p_set = $matches[1];
				}
				$this->p_expression = $matches[3];
		
			//Expression to evaluate without assigning it to anything (usually these are the return statements at the end)
			} else {
				$this->p_set = false;
				$this->p_expression = $this->parsed;
			}
		
			//The very last line of the program should be the result that is returned
			if (!$this->next) {
				if (!$this->p_isEndIfOrFor) {
					$this->p_set = false;
					$this->p_doReturn = true;
					$this->p_noteResultForReturn = true;
				}
			
			//If functions are enabled, the very last line of a function the result that function returns
			} elseif ($this->p_inFunction) {
				if ($this->p_isLastLineOfFunction) {
					$this->p_set = false;
					$this->p_doReturn = true;
					$this->p_noteResultForReturn = true;
				}
		
			//Catch the case where the last line of the program is an end if and not an expression to return
			} elseif (self::$final->closedCurliesRight && $this->p_isEndIf) {
				//To handle this, note down the value of the expression at the end of each if-statement, and use the last one that is found as the return value
				$this->p_noteResultForReturn = true;
			}
		}
		
		//Catch the case where the last line of a function is an end if and not an expression to return
		if ($this->p_isLastLineOfFunction
		 && $this->p_isEndIf
		 && self::$p_firstLineOfFunction !== false
		 && self::$p_firstLineOfFunction < $this->i) {
			//To handle this, note down the value of all expressions in the function, and use the last one that is found as the return value
			for ($j = self::$p_firstLineOfFunction; $j < $this->i; ++$j) {
				self::$lines[$j]->p_noteResultForReturn = true;
			}
		}
	}
	
	public function parseExpression2() {
			
		if ($this->isExpression) {
			
			if ($this->p_doReturn) {
				$this->parsed = '{% do _zPhiR_(_zPhiRV_('. $this->p_expression. ')) %}';
			
			//Twig doesn't support setting array keys, so we'll need to use a hack to work around this
			} elseif ($this->p_setNewArrayKey !== false) {
				$this->parsed = '{% do _zPhiSNAK_('. $this->p_setNewArrayKey. ', '. $this->p_expression. ', _zPhiSAKEnd_()) %}';
			
			} elseif ($this->p_setArrayKey !== false) {
				if ($this->p_noteResultForReturn) {
					$this->parsed = '{% do _zPhiSAK_('. $this->p_setArrayKey. ', '. $this->p_expression. ', _zPhiSAKEnd_()) %}{% do _zPhiRV_('. $this->p_setArrayKey. ') %}';
				} else {
					$this->parsed = '{% do _zPhiSAK_('. $this->p_setArrayKey. ', '. $this->p_expression. ', _zPhiSAKEnd_()) %}';
				}
			
			} elseif ($this->p_set !== false) {
				if ($this->p_noteResultForReturn) {
					$this->parsed = '{% set '. $this->p_set. ' = '. $this->p_expression. ' %}{% do _zPhiRV_('. $this->p_set. ') %}';
				} else {
					$this->parsed = '{% set '. $this->p_set. ' = '. $this->p_expression. ' %}';
				}
			
			} else {
				if ($this->p_noteResultForReturn) {
					$this->parsed = '{% do _zPhiRV_('. $this->p_expression. ') %}';
				} else {
					$this->parsed = '{% do '. $this->p_expression. '%}';
				}
			}
		}
		
		//Catch the case where a return-statement on the last line of a function can cause invalid syntax
		if (substr($this->parsed, 0, 39) == '{% do _zPhiR_(_zPhiRV_(_zPhiR_(_zPhiRV_') {
			$this->parsed = '{% do _zPhiR_(_zPhiRV_(('. substr($this->parsed, 39);
		}
		if (substr($this->parsed, 0, 31) == '{% do _zPhiRV_(_zPhiR_(_zPhiRV_') {
			$this->parsed = '{% do _zPhiR_(_zPhiRV_('. substr($this->parsed, 31);
		}
		
		
		$this->parsed .= $this->p_end;
	}

}