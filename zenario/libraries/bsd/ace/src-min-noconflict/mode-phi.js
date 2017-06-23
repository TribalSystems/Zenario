
(function() {
    "use strict";
	
	var phiConstants = {
			'null': {},
			'none': {},
			'true': {},
			'false': {},
			e: {},
			pi: {},
			euler: {},
			Inf: {},
			'Infinity': {}
		},
		phiOperators = {
			//Basic operators
			'b-and': {},
			'b-xor': {},
			'b-or': {},
			'in': {},
			'is': {},
			and: {},
			or: {},
			xor: {},
			not: {},
			
			//Twig tests
			'defined': {},
			'empty': {},
			'even': {},
			'iterable': {},
			'odd': {}
		},
		phiKeywords = {
			'for': {},
			'if': {},
			'elseif': {},
			'else': {},
			'break': {},
			'continue': {},
			'return': {}
		},
		phiFunctions = {
			//PHP maths functions
			'abs': {input: 'number', returns: 'number'},
			'acos': {input: 'number', returns: 'number'},
			'acosh': {input: 'number', returns: 'number'},
			'asin': {input: 'number', returns: 'number'},
			'asinh': {input: 'number', returns: 'number'},
			'atan': {input: 'number', returns: 'number'},
			'atan2': {input: 'number', returns: 'number'},
			'atanh': {input: 'number', returns: 'number'},
			'base_convert': {input: 'number, frombase, tobase ', returns: 'string'},
			'bindec': {input: 'binary-string ', returns: 'number'},
			'ceil': {input: 'number', returns: 'number'},
			'cos': {input: 'number', returns: 'number'},
			'cosh': {input: 'number', returns: 'number'},
			'decbin': {input: 'number', returns: 'binary-string'},
			'dechex': {input: 'number', returns: 'hexadecimal-string'},
			'decoct': {input: 'number', returns: 'octal-string'},
			'deg2rad': {input: 'degrees', returns: 'radians'},
			'exp': {input: 'number', returns: 'number'},
			'expm1': {input: 'number', returns: 'number'},
			'floor': {input: 'number', returns: 'number'},
			'fmod': {input: 'dividend, divisor', returns: 'number'},
			//'getrandmax': {},
			'hexdec': {input: 'hexadecimal-string', returns: 'number'},
			'hypot': {input: 'lengthAdj, lengthOpp', returns: 'lengthHyp'},
			'intdiv': {input: 'dividend, divisor', returns: 'number'},		//n.b. php7 only
			'is_finite': {input: 'number', returns: 'boolean'},
			'is_infinite': {input: 'number', returns: 'boolean'},
			'is_nan': {input: 'number', returns: 'boolean'},
			'log': {input: 'number, base', returns: 'number'},
			'log10': {input: 'number', returns: 'number'},
			'log1p': {input: 'number', returns: 'number'},
			'max': {},
			'min': {},
			//'mt_getrandmax': {},
			//'mt_rand': {},
			//'mt_srand': {},
			'octdec': {input: 'octal-string', returns: 'number'},
			//'pi': {},
			//'pow': {},
			'rad2deg': {input: 'radians', returns: 'degrees'},
			//'rand': {},
			'round': {input: 'number, precision', returns: 'number'},
			'sin': {input: 'number', returns: 'number'},
			'sinh': {input: 'number', returns: 'number'},
			'sqrt': {input: 'number', returns: 'number'},
			//'srand': {},
			'sum': {input: 'array', returns: 'number'},
			'tan': {input: 'number', returns: 'number'},
			'tanh': {input: 'number', returns: 'number'},
			//'bcadd': {input: 'number, number, scale=0', returns: 'number'},
			//'bccomp': {input: 'number, number, scale=0', returns: 'number'},
			//'bcdiv': {input: 'number, number, scale=0', returns: 'number'},
			//'bcmod': {input: 'number, modulus', returns: 'number'},
			//'bcmul': {input: 'number, number, scale=0', returns: 'number'},
			//'bcpow': {input: 'number, number, scale=0', returns: 'number'},
			//'bcpowmod': {input: 'number, number, modulus, scale=0', returns: 'number'},
			//'bcscale': {input: 'scale', returns: 'number'},
			//'bcsqrt': {input: 'number, scale=0', returns: 'number'},
			//'bcsub': {input: 'number, number, scale=0', returns: 'number'},
			
			//Numbers.php basic maths functions
			'subtraction': {input: 'array', returns: 'number'},
			'product': {input: 'array', returns: 'number'},
			'square': {input: 'number', returns: 'number'},
			//'binomial': {input: 'numchoices, numchosen', returns: 'number'},
			'factorial': {input: 'number', returns: 'number'},
			'gcd': {input: 'number, number', returns: 'number'},
			'lcm': {input: 'number, number', returns: 'number'},
			'shuffle': {input: 'array', returns: 'array'},
			'isInt': {input: 'number', returns: 'boolean'},
			//'divMod': {input: 'number, number', returns: 'array'},
			//'powerMod': {input: 'number, number', returns: 'number'},
			//'egcd': {input: 'number, number', returns: 'array'},
			//'modInverse': {input: 'number, number', returns: 'number'},
			//'numbersEqual': {input: 'number, number', returns: 'boolean'},
			//'fallingFactorial': {input: 'number, number', returns: 'number'},
			
			//Numbers.php statistics functions
			'mean': {input: 'array', returns: 'number'},
			'median': {input: 'array', returns: 'number'},
			'mode': {input: 'array', returns: 'number'},
			//'quantile': {},
			//'report': {},
			//'randomSample': {},
			'standardDev': {input: 'array', returns: 'number'},
			//'correlation': {},
			//'rSquared': {},
			//'exponentialRegression': {},
			//'linearRegression': {},
			//'covariance': {},
			
			//Returns a Numbers.php complex number
			'complex': {input: 'number, number', returns: 'complex number'},
			//Twig functions
			'date': {input: 'date-string, timezone', returns: 'date'},
			'random': {input: 'array/number/string', returns: 'element/number/character'},
			//N.b. I removed a few such as attribute and cycle from the autocomplete but they're still usable
			
			//Assetwolf's functions
			'getHistoricValue': {input: 'key, timestamp', returns: 'value'},
			'getTimestamp': {input: 'description[, timestamp]', returns: 'number'},
			
			//Misc functions
			'length': {input: 'array/string', returns: 'number'},
			'sort': {input: 'array', returns: 'array'},
			'paste': {input: 'strings', returns: 'string'},
			'print': {input: 'string', returns: 'null'},
			'var_dump': {input: 'string', returns: 'null'},
			'count': {input: 'array', returns: 'number'},
			'c': {input: 'numbers/strings', returns: 'array'},
			'list': {input: 'numbers/strings', returns: 'array'},
			'rev': {input: 'array', returns: 'array'},
			'trim': {input: 'string', returns: 'string'},
			'setValue': {input: 'key, value', returns: 'null'}
			
		};
			//N.b. left out from Twig was "|block|constant|divisibleby|include|parent|sameas|source|template_from_string"



	//Definitions for Phi highlighting rules, works by extending some of the logic from the JavaScript highlighting rules
	ace.define("ace/mode/phi_highlight_rules", [ "require", "exports", "module", "ace/lib/oop", "ace/mode/doc_comment_highlight_rules", "ace/mode/text_highlight_rules" ], function(require, exports, module) {
		var oop = require("../lib/oop"),
			DocCommentHighlightRules = require("./doc_comment_highlight_rules").DocCommentHighlightRules,
			TextHighlightRules = require("./text_highlight_rules").TextHighlightRules,
			PhiHighlightRules = function() {
			
				
				var keywordMapper = this.createKeywordMapper(
					{
						//"variable.language": "this",
						"keyword": _.keys(phiKeywords).join('|'),
						"keyword.operator":  _.keys(phiOperators).join('|'),
						"constant.language": _.keys(phiConstants).join('|'),
						"support.function": _.keys(phiFunctions).join('|')
					},
					"identifier"
				);
			
				this.$rules = {
					start: [
						{
	//						token : "comment", // multi line comment
	//						regex : /\/\*/,
	//						next: [
	//							DocCommentHighlightRules.getTagRule(),
	//							{token : "comment", regex : "\\*\\/", next : "start"},
	//							{defaultToken : "comment", caseInsensitive: true}
	//						]
	//					}, {
	//						token : "comment",
	//						regex : "\\/\\/",
	//						next: [
	//							DocCommentHighlightRules.getTagRule(),
	//							{token : "comment", regex : "$|^", next : "start"},
	//							{defaultToken : "comment", caseInsensitive: true}
	//						]
	//					}, {
	//						token : "comment",
	//						regex : "\\#",
	//						next: [
	//							DocCommentHighlightRules.getTagRule(),
	//							{token : "comment", regex : "$|^", next : "start"},
	//							{defaultToken : "comment", caseInsensitive: true}
	//						]
	//					}, {
							token: "comment",
							regex: "\\/\\/.*$"
						}, {
							token: "comment",
							regex: "\\#.*$"
						}, {
							token: "comment",
							regex: "\\/\\*",
							next: "comment"
						}, {
							token: "string",
							regex: '["](?:(?:\\\\.)|(?:[^"\\\\]))*?["]'
						}, {
							token: "string",
							regex: "['](?:(?:\\\\.)|(?:[^'\\\\]))*?[']"
						}, {
							token: "constant.numeric",
							regex: /0(?:[xX][0-9a-fA-F][0-9a-fA-F_]*|[bB][01][01_]*)[LlSsDdFfYy]?\b/
						}, {
							token: "constant.numeric",
							regex: /[+-]?\d[\d_]*(?:(?:\.[\d_]*)?(?:[eE][+-]?[\d_]+)?)?[LlSsDdFfYy]?\b/
						}, {
							token: "constant.language.boolean",
							regex: "(?:true|false)\\b"
						}, {
							token: keywordMapper,
							regex: "[a-zA-Z_$][a-zA-Z0-9_$]*\\b"
						}, {
	//						token: "keyword.operator",
	//						regex: "!|\\$|%|&|\\*|\\-\\-|\\-|\\+\\+|\\+|~|===|==|=|!=|!==|<=|>=|<<=|>>=|>>>=|<>|<|>|!|&&|\\|\\||\\?\\:|\\*=|%=|\\+=|\\-=|&=|\\^=|\\b(?:in|instanceof|new|delete|typeof|void)"
	//					}, {
							token: "lparen",
							regex: "[[({]"
						}, {
							token: "rparen",
							regex: "[\\])}]"
						}, {
							token: "text",
							regex: "\\s+"
						}
					],
					comment: [
						{
							token: "comment",
							regex: ".*?\\*\\/",
							next: "start"
						}, {
							token: "comment",
							regex: ".+"
						}
					]
				};
				this.embedRules(DocCommentHighlightRules, "doc-", [ DocCommentHighlightRules.getEndRule("start") ]);
			};
   
		oop.inherits(PhiHighlightRules, TextHighlightRules);
		exports.PhiHighlightRules = PhiHighlightRules;
	});


	//Definitions for Phi, works by extending some of the logic from the JavaScript definition
	ace.define("ace/mode/phi", [ "require", "exports", "module", "ace/lib/oop", "ace/mode/javascript", "ace/mode/phi_highlight_rules" ], function(require, exports, module) {
		"use strict";
	
		var oop = require("../lib/oop"),
			JavaScriptMode = require("./javascript").Mode,
			PhiHighlightRules = require("./phi_highlight_rules").PhiHighlightRules,
			Mode = function() {
				JavaScriptMode.call(this);
				this.HighlightRules = PhiHighlightRules;
			};
	
		oop.inherits(Mode, JavaScriptMode);
	
		(function() {
		
			this.lineCommentStart = ["//", "#"];
			this.blockComment = {start: "/*", end: "*/"};
		
			this.createWorker = function(e) {
				return null;
			};
			this.$id = "ace/mode/phi";
		

			this.getCompletions = function(state, session, pos, prefix) {
				var keywords = this.$keywordList || this.$createKeywordList();
				//console.log(keywords);
				return keywords.map(function(word) {
				
					var details,
						meta,
						name = word,
						value = word,
						caption = word;
					
					if (phiConstants[word]) {
						meta = 'constant';
					
					} else if (phiOperators[word]) {
						meta = 'operator';
					
					} else if (phiKeywords[word]) {
						meta = 'keyword';
					
					} else if (details = phiFunctions[word]) {
						if (details.returns) {
							meta = 'returns ' + details.returns;
						} else {
							meta = 'function';
						}
						
						if (details.input) {
							caption = word + '(' + details.input + ')';
						} else {
							caption = word + '()';
						}
						value = word + '(';
					}
				
					return {
						name: name,
						value: value,
						caption: caption,
						score: 0,
						meta: meta
					};
				});
			};
		
		
		}).call(Mode.prototype);
	
		exports.Mode = Mode;
	});


})();
