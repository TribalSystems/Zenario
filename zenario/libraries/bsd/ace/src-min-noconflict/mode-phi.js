
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
			'floor': {input: 'number', returns: 'number'},
			'hexdec': {input: 'hexadecimal-string', returns: 'number'},
			'is_finite': {input: 'number', returns: 'boolean'},
			'is_infinite': {input: 'number', returns: 'boolean'},
			'is_nan': {input: 'number', returns: 'boolean'},
			'log': {input: 'number, base', returns: 'number'},
			'log10': {input: 'number', returns: 'number'},
			'octdec': {input: 'octal-string', returns: 'number'},
			'rad2deg': {input: 'radians', returns: 'degrees'},
			'round': {input: 'number, precision', returns: 'number'},
			'sin': {input: 'number', returns: 'number'},
			'sinh': {input: 'number', returns: 'number'},
			'sqrt': {input: 'number', returns: 'number'},
			'tan': {input: 'number', returns: 'number'},
			'tanh': {input: 'number', returns: 'number'},
			
			//Type conversion
			'int': {input: 'mixed', returns: 'number'},
			'float': {input: 'mixed', returns: 'number'},
			'string': {input: 'mixed', returns: 'string'},
			
			//Functions that take mixed inputs (numbers or arrays of numbers)
			//N.b. NumbersPHP\Statistic is used for some of the statistics
			'sort': {returns: 'array'},
			'shuffle': {returns: 'array'},
			'sum': {returns: 'number'},
			'max': {returns: 'number'},
			'min': {returns: 'number'},
			'mean': {returns: 'number'},
			'median': {returns: 'number'},
			'mode': {returns: 'number'},
			'lowerQuartile': {returns: 'number'},
			'firstQuartile': {returns: 'number'},
			'upperQuartile': {returns: 'number'},
			'thirdQuartile': {returns: 'number'},
			'standardDev': {returns: 'number'},
			'product': {returns: 'number'},

			//Numbers.php basic maths functions
			'factorial': {input: 'number', returns: 'number'},
			'gcd': {input: 'number, number', returns: 'number'},
			'lcm': {input: 'number, number', returns: 'number'},
			
			//Twig functions
			'date': {input: 'date-string[, timezone]', returns: 'date'},
			'cycle': {input: 'array, index', returns: 'element'},
			'random': {input: 'array/number/string', returns: 'element/number/character'},
			'range': {input: 'start, stop[, step]', returns: 'array'},
			//N.b. I removed a few such as attribute from the autocomplete/documentation but they're still usable
			
			//Assetwolf's functions
			'getHistoricValue': {input: 'key, timestamp', returns: 'value'},
			'getTimestamp': {input: 'description[, timestamp]', returns: 'number'},
			
			//Misc functions
			'length': {input: 'array/string', returns: 'number'},
			'paste': {input: 'strings', returns: 'string'},
			'print': {input: 'string', returns: 'null'},
			'dump': {input: 'string', returns: 'null'},
			'count': {input: 'array', returns: 'number'},
			'array': {input: 'numbers/strings', returns: 'array'},
			'c': {input: 'numbers/strings', returns: 'array'},
			'list': {input: 'numbers/strings', returns: 'array'},
			'array_merge': {input: 'arrays', returns: 'array'},
			'rev': {input: 'array', returns: 'array'},
			'reverse': {input: 'array', returns: 'array'},
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
				
				if (zenario.phiVariables !== undefined) {
					keywords = keywords.concat(zenario.phiVariables);
				}
				
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
					
					} else {
						meta = 'variable';
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
