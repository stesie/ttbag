<?php
		if (SINGLE_USER_MODE) {
			login_sequence();
		}

		header('Content-Type: text/html; charset=utf-8');
		print "<html><head><title>Tiny Tiny RSS</title>
		<link rel=\"shortcut icon\" type=\"image/png\" href=\"images/favicon.png\">
		<link rel=\"icon\" type=\"image/png\" sizes=\"72x72\" href=\"images/favicon-72px.png\">";

		echo stylesheet_tag("css/utility.css");
		echo stylesheet_tag("css/dijit.css");
		echo javascript_tag("lib/prototype.js");
		echo javascript_tag("lib/scriptaculous/scriptaculous.js?load=effects,controls");
		print "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"/>
			</head><body id='sharepopup'>";

?>
