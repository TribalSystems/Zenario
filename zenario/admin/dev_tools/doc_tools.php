<?php

require '../../visitorheader.inc.php';




echo
'<!DOCTYPE HTML>
<html>
<head>
	<title>';
	
		switch ($_GET['mode'] ?? false) {
			case 'admin_box_schema';
				$schema = 'admin_box_schema.yaml';
				echo ze\admin::phrase('Schema for Admin Box');
				break;
			case 'admin_toolbar_schema';
				$schema = 'admin_toolbar_schema.yaml';
				echo ze\admin::phrase('Schema for Admin Toolbar');
				break;
			case 'module_schema';
				$schema = 'module_schema.yaml';
				echo ze\admin::phrase('Schema for Module Meta-data');
				break;
			case 'organizer_schema';
				$schema = 'organizer_schema.yaml';
				echo ze\admin::phrase('Schema for Organizer');
				break;
			default:
				exit;
		}
		
		//Prefer to document HEAD if possible
		$apiDir = '/var/www/zenario-source/HEAD/zenario/reference/';
		if (!is_dir($apiDir)) {
			$apiDir = CMS_ROOT. 'zenario/reference/';
		}
		
		$schema = ze\tuix::readFile($apiDir. $schema);
		unset($schema['common_definitions']);
		
		$docsonBaseUrl = '../../libs/manually_maintained/apache/docson';

echo '
	</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="', $docsonBaseUrl, '/css/docson.css">
    <script src="', $docsonBaseUrl, '/lib/require.js"></script></head>
    <script src="doc_tools.js?v=6"></script>';
    
    if ($_GET['linkToRef'] ?? false) {
    	echo "
    		<style>
    			html, body {
    				margin: 0;
    				padding: 0;
    				overflow: hidden;
    			}
    			div.version_no {
    				text-align: right;
    				font-style: italic;
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
					background: #f5f5f5 url('", ze\link::absolute(), "zenario/admin/images/icon-warning.svg') no-repeat 3px 3px / 33px 29px;
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
<body>';

//Try and work out which version this is for
foreach (scandir($apiDir. '../admin/db_updates/copy_over_top_check/') as $file) {
	if ($file[0] != '.') {
		echo '
<div class="version_no">Documentation is for version ', htmlspecialchars(str_replace('.txt', '', $file)), '</div>';
		break;
	}
}


echo '
<div id="doc"></div>
<script charset="utf-8">
    require.config({ baseUrl: "', $docsonBaseUrl, '"});
    require(["docson", "lib/jquery"], function(docson) {
        $(function() {
        	var schema = docTools.parseSchema(
        		', json_encode($_GET['mode'] ?? false), ',
        		', json_encode($schema), ',
        		', json_encode($_GET['tag'] ?? false), ',
        		', ze\ring::engToBoolean($_GET['linkToRef'] ?? false), ');
            docson.templateBaseUrl= "', $docsonBaseUrl, '/templates";
            docson.doc("doc", schema);
        });
    });
</script>
</body>
</html>';