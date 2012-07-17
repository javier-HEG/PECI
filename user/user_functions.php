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
	$userCreateEditScript = "<script type=\"text/javascript\" src=\"{$rooturl}/user/scripts/create_edit.js\"></script>";
	$adminHeader = str_replace($adminCoreScript, $adminCoreScript . "\n" . $userTabsScript . "\n" . $userCreateEditScript, $adminHeader);
	
	// Add our custom made JavaScript scripts
	$jQueryScript = "<script type=\"text/javascript\" src=\"{$rooturl}/scripts/jquery/jquery.js\"></script>";
	$jQueryCookieScript = "<script type=\"text/javascript\" src=\"{$rooturl}/user/scripts/jquery.cookie.js\"></script>";
	$jQuerySimpleModalScript = "<script type=\"text/javascript\" src=\"{$rooturl}/user/scripts/jquery.simplemodal.js\"></script>";
	$adminHeader = str_replace($jQueryScript, $jQueryScript . "\n" . $jQueryCookieScript . "\n" . $jQuerySimpleModalScript, $adminHeader);
	
	return $adminHeader;
}

/**
 * Creates the menu holding the project selector and other buttons needed
 * for this menu.
 */
function getProjectSelectorMenu() {
	global $imageurl;
	
	$output = '<div id="projectMenu">';
	$output .= '<span id="menuOptionOpen" class="menuOption">' . getUserSurveySelect() . " Open <img src=\"$imageurl/user/silk/page_go.png\" title=\"Open\" /></span>";
	$output .= "<span id=\"menuOptionCreate\" class=\"menuOption\">Create new <img src=\"$imageurl/user/silk/page_add.png\" title=\"Create new survey\" /></span>";
	
	// Add hover style
	$output .= "<script>
		// Add hover style to menu items
		$('span[class=menuOption]').hover(
    	    function(){ $(this).addClass(\"menuOptionHover\"); },
        	function(){ $(this).removeClass(\"menuOptionHover\"); }
    	);
		
    	// Manage the click on the 'Open' option of the menu
		$('#menuOptionOpen').unbind('click').click(
			function(){
				if ($('#userMenuSurveySelect').val() != 'none')
					window.open('user.php?sid=' + $('#userMenuSurveySelect').val(), '_self', false);
				else {
					alert('Please choose a survey from the drop-down menu inside the button.');
				}
			}
		);
		$('#userMenuSurveySelect').click(
			function(event){ event.stopPropagation(); }
		);
		
		// Set the click on the 'Create new' option of the menu
		$('#menuOptionCreate').unbind('click').click(
			function() {
				openPeciPopup('newsurvey', '');
			}
		);
	</script>";
	
	$output .= '</div>';
	
	return $output;
}

/**
 * Creates an option menu with the surveys belonging to the current user.
 * <strong>NB.</strong> Code based on <code>admin/surveylist.php</code>.
 */
function getUserSurveySelect() {
	$query = " SELECT a.*, c.*, u.users_name FROM " . db_table_name('surveys')." as a "
		. " INNER JOIN " . db_table_name('surveys_languagesettings')
			. " as c ON ( surveyls_survey_id = a.sid AND surveyls_language = a.language )"
			. " AND surveyls_survey_id=a.sid and surveyls_language=a.language "
		. " INNER JOIN ".db_table_name('users')." as u ON (u.uid=a.owner_id) "
		. " WHERE a.owner_id={$_SESSION['loginID']}";
	
	$query .= " ORDER BY surveyls_title";
	
	$result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());
	
	$output = '<select name="menu1" disabled="true"><option selected="true">(No surveys yet)</option></select>';
	
	if($result->RecordCount() > 0) {
		$output = '<select id="userMenuSurveySelect">';
		$output .= "<option value=\"none\" selected=\"true\">Choose a survey ...</option>";
		
		while($rows = $result->FetchRow()) {
			$output .= "<option value=\"{$rows['sid']}\">{$rows['surveyls_title']}</option>";
		}
	
		$output .= '</select>';
	}
	
	return $output;
}

/**
 * Function returning html text for the interface's footer.
 * <strong>NB.</strong> Code based on <code>common_functions.php</code>'s
 * <code>getAdminFooter()</code> function but returning a much simpler footer.
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