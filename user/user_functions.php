<?php
/**
 * Function slightly modifying the admin header so that the user
 * scripts are included and our own stylesheet applied. It decorates
 * over the results returned by admin_functions.php:getAdminHeader($meta).
 */
function getUserHeader($meta=false) {
	$adminHeader = getAdminHeader($meta);
	
	global $admintheme, $rooturl, $homeurl;

	$userHomeUrl = $rooturl . '/user';
	
	// Override the admin stylesheet with our stylesheet
	$adminStyle = "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$homeurl}/styles/$admintheme/adminstyle.css\" />";
	$userStyle = "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$userHomeUrl}/styles/default/userstyle.css\" />";
	$adminHeader = str_replace($adminStyle, $adminStyle . "\n" . $userStyle, $adminHeader);
	
	// Add our custom made JavaScript scripts
	$adminCoreScript = "<script type=\"text/javascript\" src=\"{$homeurl}/scripts/admin_core.js\"></script>";
	$userTabsScript = "<script type=\"text/javascript\" src=\"{$userHomeUrl}/scripts/tabs.js\"></script>";
	$userCreateEditScript = "<script type=\"text/javascript\" src=\"{$userHomeUrl}/scripts/create_edit.js\"></script>";
	$adminHeader = str_replace($adminCoreScript, $adminCoreScript . "\n" . $userTabsScript . "\n" . $userCreateEditScript, $adminHeader);
	
	// Add our custom made JavaScript scripts
	$jQueryScript = "<script type=\"text/javascript\" src=\"{$rooturl}/scripts/jquery/jquery.js\"></script>";
	$jQueryCookieScript = "<script type=\"text/javascript\" src=\"{$userHomeUrl}/scripts/jquery.cookie.js\"></script>";
	$jQuerySimpleModalScript = "<script type=\"text/javascript\" src=\"{$userHomeUrl}/scripts/jquery.simplemodal.js\"></script>";
	$adminHeader = str_replace($jQueryScript, $jQueryScript . "\n" . $jQueryCookieScript . "\n" . $jQuerySimpleModalScript, $adminHeader);
	
	// Change path to user/favicon.ico
	$adminShortCutFavicon = "<link rel=\"shortcut icon\" href=\"{$homeurl}/favicon.ico\" type=\"image/x-icon\" />";
	$adminFavicon = "<link rel=\"icon\" href=\"{$homeurl}/favicon.ico\" type=\"image/x-icon\" />";
	$userShortCutFavicon = "<link rel=\"shortcut icon\" href=\"{$userHomeUrl}/favicon.ico\" type=\"image/x-icon\" />";
	$userFavicon = "<link rel=\"icon\" href=\"{$userHomeUrl}/favicon.ico\" type=\"image/x-icon\" />";
	$adminHeader = str_replace($adminShortCutFavicon, $userShortCutFavicon, $adminHeader);
	$adminHeader = str_replace($adminFavicon, $userFavicon, $adminHeader);

	return $adminHeader;
}

/**
 * Creates the menu holding the project selector and other buttons needed
 * for this menu.
 */
function getProjectSelectorMenu($openSid = '') {
	global $imageurl, $clang;
	
	$output = '<div id="projectMenu">';
	$output .= '<span id="menuOptionOpen" class="menuOption">' . getUserSurveySelect($openSid)  . $clang->gT("PECI: Open") . "<img src=\"$imageurl/user/silk/page_go.png\" title=\"Open\" /></span>";
	$output .= "<span id=\"menuOptionCreate\" class=\"menuOption\">" . $clang->gT("PECI: Create new survey") . "<img src=\"$imageurl/user/silk/page_add.png\" title=\"Create new survey\" /></span>";
	
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
function getUserSurveySelect($openSid = '') {
	global $clang;
	$query = " SELECT a.*, c.*, u.users_name FROM " . db_table_name('surveys')." as a "
		. " INNER JOIN " . db_table_name('surveys_languagesettings')
			. " as c ON ( surveyls_survey_id = a.sid AND surveyls_language = a.language )"
			. " AND surveyls_survey_id=a.sid and surveyls_language=a.language "
		. " INNER JOIN ".db_table_name('users')." as u ON (u.uid=a.owner_id) "
		. " WHERE a.owner_id={$_SESSION['loginID']}";
	
	$query .= " ORDER BY surveyls_title";
	
	$result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());
	
	$output = '<select name="menu1" disabled="true"><option selected="true">' . $clang->gT("No surveys yet") . '</option></select>';
	
	if($result->RecordCount() > 0) {
		$output = '<select id="userMenuSurveySelect">';
		
		if ($openSid == '') {
			$output .= "<option value=\"none\" selected=\"true\">" . $clang->gT("PECI: Choose a survey ...") . "</option>";
		}
		
		while($rows = $result->FetchRow()) {
			$selected = '';
			if ($openSid == $rows['sid']) {
				$selected = ' selected="true"';
			}
			
			$output .= "<option value=\"{$rows['sid']}\"$selected>{$rows['surveyls_title']}</option>";
		}
		
		$output .= '</select>';
	}
	
	return $output;
}

/**
 * Returns html text for the registered user menu bar.
 * A simplication of "admin/html.php:showadminmenu()".
 */
function getUserMenu() {
	global $clang;

	// Originally this variable is that set to "admin.php" in config.php,
	// we however need to send calls to our script.
	$scriptname = "user.php";

	$adminmenu = "<div class='menubar'>\n";

	if (isset($_SESSION['loginID'])) {
		$adminmenu .= $clang->gT("Logged in as:") . " <a onclick=\"window.open('{$scriptname}?action=personalsettings', '_top')\""
		. " title=\"Edit your personal preferences\">" . $_SESSION['full_name'] . "</a>";
		$adminmenu .= " | <a onclick=\"window.open('$scriptname?action=logout', '_top')\""
		. " title=\"".$clang->gTview("Logout")."\" >" . $clang->gT("Logout") . "</a>";
	} else {
		// TODO Make it so clicking calls user.php action=changelanguage "lang" is the
		// variable holding the value
		// Also, make the menu automatically from the language list, before removing
		// english as an option, check: html.php::51
		
		$allLanguages = array();

		if (isset($_SESSION['adminlang']))
			$currentLanguage = $_SESSION['adminlang'];
		else
			$currentLanguage = $clang->getlangcode();

		foreach (getlanguagedata(true) as $langkey=>$languagekind) {
			$changeLanguageScript = "$.ajax({
				url: '$scriptname?action=changelanguage&lang=$langkey',
				success: function(data) { location.reload(); }
			});";

			if ($langkey == $currentLanguage)
				$allLanguages[] = strtoupper($langkey);
			else {
				$languageLink = '<a title="Change language to ' . $languagekind['description'] . '" ' .
					'onclick="' . $changeLanguageScript . '">';
				$languageLink .= strtoupper($langkey);
				$languageLink .= '</a>';

				$allLanguages[] = $languageLink;
			}
		}

		$adminmenu .= implode('&nbsp;|&nbsp;', $allLanguages);
	}

	$adminmenu .= "</div>";

	// Move the menu bar to its definitive position
	$adminmenu .= "<script type=\"text/javascript\">$('#mainTitleMenu').append($('.menubar'));</script>";

	return $adminmenu;
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