<?php

/**
 * Function returning html text for the interface's footer, it's
 * based on "common_functions.php:getAdminFooter()" but returns a
 * much simpler footer.
 */
function getUserFooter($url, $label) {
	global $js_admin_includes, $homeurl;
	global $versionnumber, $buildnumber, $setfont, $imageurl, $clang;

	
	//If user is not logged in, don't print the version number information in the footer.
	if(!isset($_SESSION['loginID'])) {
		$footerInfoTitle = "Powered by LimeSurvey";
	} else {
		$versiontitle = $clang->gT('Version');
		$footerInfoTitle = "Powered by LimeSurvey $versiontitle $versionnumber";
	}

	$strHTMLFooter = <<<HTML
		<div class='footer'>
	    	<div style="float:right; margin-right: 6px;">
	    		<img src="$imageurl/limecursor-handle.png" title="$footerInfoTitle"
	    			onclick="window.open('http://www.limesurvey.org')" />
	    	</div>
    		<div class="subtitle">
    			<a onclick="window.open('$url')">$label</a>    			
    		</div>
    	</div>
HTML;

	$js_admin_includes = array_unique($js_admin_includes);
	foreach ($js_admin_includes as $jsinclude)
	{
	$strHTMLFooter .= "<script type=\"text/javascript\" src=\"".$jsinclude."\"></script>\n";
    }

    $strHTMLFooter.="</body>\n</html>";
    return $strHTMLFooter;
}