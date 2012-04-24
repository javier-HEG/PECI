<?php
/**
 * Function slightly modifying the admin header so that the user
 * scripts are included and our own stylesheet applied. It decorates
 * over the results returned by admin_functions.php:getAdminHeader($meta).
 */
function getUserHeader($meta=false) {
	$adminHeader = getAdminHeader($meta);
	
	global $admintheme, $rooturl, $homeurl;
	
	// Override the admin stylesheet with our stylesheet
	$adminStyle = "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$homeurl}/styles/$admintheme/adminstyle.css\" />";
	$userStyle = "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$rooturl}/user/styles/default/userstyle.css\" />";
	$adminHeader = str_replace($adminStyle, $adminStyle . "\n" . $userStyle, $adminHeader);
	
	// Add our custom made JavaScript scripts
	$adminCoreScript = "<script type=\"text/javascript\" src=\"{$homeurl}/scripts/admin_core.js\"></script>";
	$userTabsScript = "<script type=\"text/javascript\" src=\"{$rooturl}/user/scripts/tabs.js\"></script>";
	$adminHeader = str_replace($adminCoreScript, $adminCoreScript . "\n" . $userTabsScript, $adminHeader);
	
	// Add our custom made JavaScript scripts
	$jQueryScript = "<script type=\"text/javascript\" src=\"{$rooturl}/scripts/jquery/jquery.js\"></script>";
	$jQueryCookieScript = "<script type=\"text/javascript\" src=\"{$rooturl}/user/scripts/jquery.cookie.js\"></script>";
	$adminHeader = str_replace($jQueryScript, $jQueryScript . "\n" . $jQueryCookieScript, $adminHeader);
	
	return $adminHeader;
}

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