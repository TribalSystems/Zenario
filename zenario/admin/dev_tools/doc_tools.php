<?php

require '../../visitorheader.inc.php';
require CMS_ROOT. 'zenario/includes/admin.inc.php';




echo
'<!DOCTYPE HTML>
<html>
<head>
	<title>';
	
		switch (get('mode')) {
			case 'admin_box_schema';
				$schema = 'admin_box_schema.yaml';
				echo adminPhrase('Schema for Admin Box');
				break;
			case 'admin_toolbar_schema';
				$schema = 'admin_toolbar_schema.yaml';
				echo adminPhrase('Schema for Admin Toolbar');
				break;
			case 'module_schema';
				$schema = 'module_schema.yaml';
				echo adminPhrase('Schema for Module Meta-data');
				break;
			case 'organizer_schema';
				$schema = 'organizer_schema.yaml';
				echo adminPhrase('Schema for Organizer');
				break;
			default:
				exit;
		}
		
		$schema = zenarioReadTUIXFile(CMS_ROOT. 'zenario/api/'. $schema);
		unset($schema['common_definitions']);
		
		$docsonBaseUrl = '../../libraries/apache/docson';

echo '
	</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="', $docsonBaseUrl, '/css/docson.css">
    <script src="', $docsonBaseUrl, '/lib/require.js"></script></head>
    <script src="doc_tools.js"></script>';
    
    if (get('linkToRef')) {
    	echo "
    		<style>
    			html, body {
    				margin: 0;
    				padding: 0;
    				overflow: hidden;
    			}
    			
    			.docson > .box {
    				width: 100%;
    			}
    			
    			.signature-description {
    				clear: left;
    				max-width: none !important;
    			}
    			
    			a span:last-child::after {
    				content: ' ...';
    			}
    			
    			.docson .required {
    				font-weight: normal;
    			}
    			
    			.docson .required {
    				font-weight: normal;
    			}
    			
				.docson .required:after {
					content: '*';
					color: #e00000;
				}
				
				.signature-type a,
				.docson .signature-type a .signature-button {
					text-decoration: none;
					cursor: default;
				}
				
				.static_warning {
					display: inline-block;
					border: 1px solid black;
					background: #f5f5f5 url('", absCMSDirURL(), "zenario/admin/images/img_msg_warning_orange.png') no-repeat 3px 3px;
					height: auto!important;
					height: 51px;
					padding: 10px 10px 10px 45px;
					margin: 0px 5px 3px 10px;
    			}
    			
    			.prefill_warning {
    				background-position: 3px 11px;
    			}
    		</style>";
    
    }
echo '
</head>
<body>
<div id="doc"></div>
<script charset="utf-8">
    require.config({ baseUrl: "', $docsonBaseUrl, '"});
    require(["docson", "lib/jquery"], function(docson) {
        $(function() {
        	var schema = docTools.parseSchema(
        		', json_encode(get('mode')), ',
        		', json_encode($schema), ',
        		', json_encode(get('tag')), ',
        		', engToBoolean(get('linkToRef')), ');
            docson.templateBaseUrl= "', $docsonBaseUrl, '/templates";
            docson.doc("doc", schema);
        });
    });
</script>
</body>
</html>';