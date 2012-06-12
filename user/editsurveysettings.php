<?php
/*
* PECI - A simplified interface for non-superadmin LimeSurvey users.
* Copyright (C) 2012 Haute École de Gestion de Genève
* Javier Belmonte <javier.belmonte@hesge.ch>
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* any later version.
*
* This program is distributed in the hope that it will be useful, but
* WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
* General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// Login Check dies also if the script is started directly
include_once("login_check.php");

if (!isset($surveyid)) {
	$surveyid = returnglobal('sid');
}

// Allow only if user has enough rights
if(!bHasSurveyPermission($surveyid,'surveysettings','read') && !bHasGlobalPermission('USER_RIGHT_CREATE_SURVEY')) {
	include("access_denied.php");
} else {
	$js_admin_includes[] = '../admin/scripts/surveysettings.js';

	if ($action == "newsurvey") {
		//New survey, set the defaults
		$esrow = array();
		$esrow['active'] = 'N';
		$esrow['allowjumps'] = 'N';
		$esrow['format'] = 'G'; //Group-by-group mode
		$esrow['template'] = $defaulttemplate;
		$esrow['allowsave'] = 'Y';
		$esrow['allowprev'] = 'N';
		$esrow['nokeyboard'] = 'N';
		$esrow['printanswers'] = 'N';
		$esrow['publicstatistics'] = 'N';
		$esrow['publicgraphs'] = 'N';
		$esrow['public'] = 'Y';
		$esrow['autoredirect'] = 'N';
		$esrow['tokenlength'] = 15;
		$esrow['allowregister'] = 'N';
		$esrow['usecookie'] = 'N';
		$esrow['usecaptcha'] = 'D';
		$esrow['htmlemail'] = 'Y';
		$esrow['emailnotificationto'] = '';
		$esrow['anonymized'] = 'N';
		$esrow['datestamp'] = 'N';
		$esrow['ipaddr'] = 'N';
		$esrow['refurl'] = 'N';
		$esrow['tokenanswerspersistence'] = 'N';
		$esrow['alloweditaftercompletion'] = 'N';
		$esrow['assesments'] = 'N';
		$esrow['startdate'] = '';
		$esrow['savetimings'] = 'N';
		$esrow['expires'] = '';
		$esrow['showqnumcode'] = 'X';
		$esrow['showwelcome'] = 'Y';
		$esrow['emailresponseto'] = '';
		$esrow['assessments'] = 'N';

		$dateformatdetails = getDateFormatData($_SESSION['dateformat']);

		$editsurvey ="<script type=\"text/javascript\">
                        standardtemplaterooturl='$standardtemplaterooturl';
                        templaterooturl='$usertemplaterooturl'; \n";
		$editsurvey .= "</script>\n";

		// header
		$editsurvey .= "<div class='header ui-widget-header'>" . $clang->gT("Create, import, or copy survey") . " ($surveyid)</div>\n";

		$editsurvey .= "<form class='form30' name='addnewsurvey' id='addnewsurvey' onSubmit='return false;'>\n";

		// Survey Language
		$editsurvey .= "<ul><li><label for='language' title='" . $clang->gT("This is the base language of your survey and it can't be changed later. You can add more languages after you have created the survey.") . "'><span class='annotationasterisk'>*</span>" . $clang->gT("Base language:") . "</label>\n"
		. "<select id='language' name='language'>\n";

		foreach (getLanguageData () as $langkey2 => $langname) {
			$editsurvey .= "<option value='" . $langkey2 . "'";
			if ($defaultlang == $langkey2) {
				$editsurvey .= " selected='selected'";
			}
			$editsurvey .= ">" . $langname['description'] . "</option>\n";
		}
		$editsurvey .= "</select>\n";

		//Use the current user details for the default administrator name and email for this survey
		$query = "SELECT full_name, email FROM " . db_table_name('users') . " WHERE users_name = " . db_quoteall($_SESSION['user']);
		$result = db_execute_assoc($query) or safe_die($connect->ErrorMsg());
		$owner = $result->FetchRow();
		//Degrade gracefully to $siteadmin details if anything is missing.
		if (empty($owner['full_name']))
		$owner['full_name'] = $siteadminname;
		if (empty($owner['email']))
		$owner['email'] = $siteadminemail;
		//Bounce setting by default to global if it set globally
		if (getGlobalSetting('bounceaccounttype')!='off'){
			$owner['bounce_email']         = getGlobalSetting('siteadminbounce');
		} else {
			$owner['bounce_email']        = $owner['email'];
		}
		$editsurvey .= "<span class='annotation'> " . $clang->gT("*This setting cannot be changed later!") . "</span></li>\n";

		$editsurvey .= ""
		. "<li><label for='surveyls_title'><span class='annotationasterisk'>*</span>" . $clang->gT("Title") . ":</label>\n"
		. "<input type='text' size='82' maxlength='200' id='surveyls_title' name='surveyls_title' /> <span class='annotation'>" . $clang->gT("*Required") . "</span></li>\n"
		. "<li><label for='description'>" . $clang->gT("Description:") . "</label>\n"
		. "<textarea cols='80' rows='10' id='description' name='description'></textarea>"
		//                     . getEditor("survey-desc", "description", "[" . $clang->gT("Description:", "js") . "]", '', '', '', $action)
		. "</li>\n"
		. "<li><label for='welcome'>" . $clang->gT("Welcome message:") . "</label>\n"
		. "<textarea cols='80' rows='10' id='welcome' name='welcome'></textarea>"
		//                     . getEditor("survey-welc", "welcome", "[" . $clang->gT("Welcome message:", "js") . "]", '', '', '', $action)
		. "</li>\n"
		. "<li><label for='endtext'>" . $clang->gT("End message:") . "</label>\n"
		. "<textarea cols='80' id='endtext' rows='10' name='endtext'></textarea>"
		//                     . getEditor("survey-endtext", "endtext", "[" . $clang->gT("End message:", "js") . "]", '', '', '', $action)
		. "</li>\n";
		
		$editsurvey.= "</ul>";
		
		// Other hidden fields
		$hiddenFieldNames = array('format', 'template', 'showwelcome', 'allowprev', 'allowjumps',
			'nokeyboard', 'printanswers', 'publicstatistics', 'publicgraphs', 'autoredirect', 'public',
			'showqnumcode', 'startdate', 'expires', 'usecookie', 'usecaptcha', 'emailnotificationto',
			'emailresponseto', 'datestamp', 'ipaddr', 'refurl', 'assesments', 'savetimings', 'allowsave',
			'anonymized', 'alloweditaftercompletion', 'tokenanswerspersistence', 'allowregister',
			'htmlemail', 'tokenlength', 'assessments');

		foreach ($hiddenFieldNames as $fieldName) {
			$editsurvey .= "<input type='hidden' id='$fieldName' name='$fieldName' value='{$esrow[$fieldName]}' />\n";
		}

		// Hidden fields with default values not set in $esrow
		$hiddenFieldsWithDefaults = array('navigationdelay' => 0, 'showprogress' => 'Y', 'showXquestions' => 'Y',
			'showgroupinfo' => 'C', 'shownoanswer' => 'Y', 'faxto' => '', 'bounce_email' => $owner['bounce_email'],
			'adminemail' => $owner['email'], 'admin' => $owner['full_name'], 'url' => 'http://', 'urldescrip' => '',
			'dateformat' => getDateFormatData($_SESSION["dateformat"]));

		foreach ($hiddenFieldsWithDefaults as $fieldName => $defaultValue) {
			$editsurvey .= "<input type='hidden' id='$fieldName' name='$fieldName' value='$defaultValue' />\n";
		}

		$editsurvey .= "<input type='hidden' id='surveysettingsaction' name='action' value='insertsurvey' />\n";
		$editsurvey .= "<input type='hidden' name='checksessionbypost' value='{$_SESSION['checksessionpost']}' />";

		$toSubmit = array('action', 'admin', 'adminemail', 'alloweditaftercompletion', 'allowjumps', 'allowprev',
      	    'allowregister', 'allowsave', 'anonymized', 'assessments', 'autoredirect', 'bounce_email', 'dateformat', 'datestamp',
       	    'description', 'emailnotificationto', 'emailresponseto', 'endtext', 'expires', 'faxto', 'format', 'htmlemail', 'ipaddr',
        	'language', 'navigationdelay', 'nokeyboard', 'printanswers', 'public', 'publicgraphs', 'publicstatistics', 'refurl',
        	'savetimings', 'showXquestions', 'showgroupinfo', 'shownoanswer', 'showprogress', 'showqnumcode', 'showwelcome',
        	'startdate', 'surveyls_title', 'template', 'tokenanswerspersistence', 'tokenlength', 'url', 'urldescrip',
        	'usecaptcha', 'usecookie', 'welcome', 'checksessionbypost');
		$onSubmit = 'javascript: if (addnewsurvey.surveyls_title.value.length > 0) { parent.submitAsParent({';
		foreach ($toSubmit as $toSubmitKey) {
			$onSubmit .= $toSubmitKey . ': addnewsurvey.' . $toSubmitKey . '.value, ';
		}
		$onSubmit .= '}); } else { alert("'.$clang->gT("Error: You have to enter a title for this survey.","js").'"); }';

		$editsurvey .= "<p><input type='button' onClick='$onSubmit' value='". $clang->gT("Save") . "' />\n";
	}
}

?>
