(function(punymce) {
	punymce.plugins.BBCode = function(ed) {
		// Convert XML into BBCode
		ed.onGetContent.add(zenario_anonymous_comments.toBBCode = function(ed, o) {	//"zenario_anonymous_comments.toBBCode = " added by zenario so this can be used elsewhere
			if (o.format == 'bbcode' || o.save) {
				// example: <strong> to [b]
				punymce.each([
					//Added by zenario
					[/<(\/?)blockquote[^>]*>/gi, "[$1quote]"],
					[/<font.*?size=\"([^\"]+)\".*?>(.*?)<\/font>/gi,"[size=$1]$2[/size]"],
					[/<(\/?)ul[^>]*>/gi, "[$1list]"],
					[/<div>/gi, "\n"],
					[/<br\/><\/div>/gi, ""],
					
					//Removed by zenario
					//[/<(\/?)(span.*?class=\"quote\")[^>]*>(.*?)<\/span>/gi, "[$1quote]$3[/quote]"],
					
					[/<a href=\"(.*?)\".*?>(.*?)<\/a>/gi,"[url=$1]$2[/url]"],
					[/<font.*?color=\"([^\"]+)\".*?>(.*?)<\/font>/gi,"[color=$1]$2[/color]"],
					[/<img.*?src=\"([^\"]+)\".*?\/>/gi,"[img]$1[/img]"],
					[/<(br\s*\/)>/gi, "\n"],
					[/<(\/?)(strong|b)[^>]*>/gi, "[$1b]"],
					[/<(\/?)(em|i)[^>]*>/gi, "[$1i]"],
					[/<(\/?)u[^>]*>/gi, "[$1u]"],
					[/<(\/?)(code|pre)[^>]*>/gi, "[$1code]"],
					
					//Added by zenario
					//[/<(\/?)s[^>]*>/gi, "[$1s]"],
					[/<(\/?)ol[^>]*>/gi, "[$1olist]"],
					[/<(\/?)li[^>]*>/gi, "[$1*]"],
					
					[/<p>/gi, ""],
					[/<\/p>/gi, "\n"],
					[/<[^>]+>/gi, ""],	//Order changed by zenario to fix a bug
					[/&quot;/gi, "\""],
					[/&lt;/gi, "<"],
					[/&gt;/gi, ">"],
					[/&amp;/gi, "&"]
				], function (v) {
					o.content = o.content.replace(v[0], v[1]);
				});
			}
		});

		ed.onSetContent.add(function(ed, o) {
			if (o.format == 'bbcode' || o.load) {
				// example: [b] to <strong>
				punymce.each([
					//Added by zenario
					[/\[(\/?)quote\]/gi,"<$1blockquote>"],
					[/\[size=(.*?)\](.*?)\[\/size\]/gi,'<font size="$1">$2</font>'],
					[/\[(\/?)list\]/gi,"<$1ul>"],
					
					//Removed by zenario
					//[/\[quote.*?\](.*?)\[\/quote\]/gi,'<span class="quote">$1</span>']
					
					[/\n/gi,"<br />"],
					[/\[(\/?)b\]/gi,"<$1strong>"],
					[/\[(\/?)i\]/gi,"<$1em>"],
					[/\[(\/?)u\]/gi,"<$1u>"],
					[/\[(\/?)code\]/gi,"<$1pre>"],
					[/\[url\](.*?)\[\/url\]/gi,"<a href=\"$1\">$1</a>"],
					[/\[url=([^\]]+)\](.*?)\[\/url\]/gi,"<a href=\"$1\">$2</a>"],
					[/\[img\](.*?)\[\/img\]/gi,"<img src=\"$1\" />"],
					[/\[color=(.*?)\](.*?)\[\/color\]/gi,'<font color="$1">$2</font>'],
					
					//Added by zenario
					//[/\[(\/?)s\]/gi,"<$1s>"],
					[/\[(\/?)olist\]/gi,"<$1ol>"],
					[/\[(\/?)\*\]/gi,"<$1li>"]
				], function (v) {
					o.content = o.content.replace(v[0], v[1]);
				});
			}
		});
	};
})(punymce);
