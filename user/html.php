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

// Ensure script is not run directly, avoid path disclosure
include_once("login_check.php");
if (isset($_POST['uid'])) { $postuserid=sanitize_int($_POST['uid']); }
if (isset($_POST['ugid'])) { $postusergroupid=sanitize_int($_POST['ugid']); }

// Show selected survey
if (isset($surveyid) && $surveyid && $action=='') {
	if(bHasSurveyPermission($surveyid,'survey','read'))	{
		$baselang = GetBaseLanguageFromSurveyID($surveyid);
		
		// Getting a count of questions for this survey
		$sumquery3 = "SELECT * FROM ".db_table_name('questions')." WHERE sid={$surveyid} AND parent_qid=0 AND language='".$baselang."'";
		$sumresult3 = $connect->Execute($sumquery3);
		$sumcount3 = $sumresult3->RecordCount();
		// Getting a count of conditions for this survey
		$sumquery6 = "SELECT count(*) FROM ".db_table_name('conditions')." as c, ".db_table_name('questions')." as q WHERE c.qid = q.qid AND q.sid=$surveyid";
		$sumcount6 = $connect->GetOne($sumquery6);
		// Getting a count of groups for this survey
		$sumquery2 = "SELECT * FROM ".db_table_name('groups')." WHERE sid={$surveyid} AND language='".$baselang."'";
		$sumresult2 = $connect->Execute($sumquery2);
		$sumcount2 = $sumresult2->RecordCount();
		// Getting data for this survey
		$sumquery1 = "SELECT * FROM ".db_table_name('surveys')." inner join ".db_table_name('surveys_languagesettings')." on (surveyls_survey_id=sid and surveyls_language=language) WHERE sid=$surveyid";
		$sumresult1 = db_select_limit_assoc($sumquery1, 1);

		// If surveyid is invalid then die to prevent errors at a later time
		if ($sumresult1->RecordCount()==0){
			die('Invalid survey id');
		}
	
		$surveyinfo = $sumresult1->FetchRow();
		$surveyinfo = array_map('FlattenText', $surveyinfo);
		
		if (!$surveyinfo['language']) {
			$language = getLanguageNameFromCode($currentadminlang, false);
		} else {
			$language = getLanguageNameFromCode($surveyinfo['language'], false);
		}
		
		// Output starts here...
		$surveysummary = '<div style="background-color: lightgray; width: 100%; margin: -1px 0px; padding-bottom: 6px;">';
		
		$surveysummary .= '<div id="surveyPeciStepContainer">'
			. '<div class="surveyPeciStep">Evaluate usefulness</div>'
			. '<div class="surveyPeciStepArrow">&nbsp;</div>'
			. '<div class="surveyPeciStep">Select questions</div>'
			. '<div class="surveyPeciStepArrow">&nbsp;</div>'
			. '<div class="surveyPeciStep currentPeciStep">Modify the questionnaire</div>'
			. '<div class="surveyPeciStepArrow">&nbsp;</div>'
			. '<div class="surveyPeciStep">Start survey</div>'
			. '<div class="surveyPeciStepArrow">&nbsp;</div>'
			. '<div class="surveyPeciStep">Analyze the data</div>'
			. '<div class="surveyPeciStepArrow">&nbsp;</div>'
			. '<div class="surveyPeciStep">Create the report</div>'
			. '</div>';
		
		$surveysummary .= '<div id="surveyContainer">'
			. '<button id="showDetailsBtn" style="float: right;" onclick="$(\'#surveyDetails\').show(); $(\'#showDetailsBtn\').hide();">(show survey details)</button>'
			. '<h1 class="peciStep">Modify the questionnaire</h1>'
			. '<div id="surveyDetails">'
			. '<button style="float: right;" onclick="$(\'#surveyDetails\').hide();  $(\'#showDetailsBtn\').show();">(hide)</button>'
			. '<h2>Survey details</h2>'
			. '<p><u>Title:</u>' . $surveyinfo['surveyls_title'] . '<br />'
			. '<u>' . $clang->gT("Base language:") . '</u> ' . $language
			. '<br /><u>' . $clang->gT("Welcome:") . '</u> ' . $surveyinfo['surveyls_welcometext'] . '</p>'	
			. '</div>';
		
		// Hide the survey details
		$surveysummary .= '<script type="text/javascript">$("#surveyDetails").hide();</script>';
		
		$surveysummary .= '<div class="questionGroup"><h1 class="questionGroupName">A: Group 1</h1>'
			. '<div class="question"><div class="questionHeader">Question 1</div><h1 class="questionTitle">How old are you?</h1></div>'
			. '<div class="question"><div class="questionHeader">Question 1</div><h1 class="questionTitle">How old are you?</h1></div>'
			. '</div>';
		
		$surveysummary .= '<div class="questionGroup"><h1 class="questionGroupName">B: Group 2</h1>'
			. '<div class="question"><div class="questionHeader">Question 1</div><h1 class="questionTitle">How old are you?</h1></div>'
			. '<div class="question"><div class="questionHeader">Question 1</div><h1 class="questionTitle">How old are you?</h1></div>'
			. '</div>';
		
		$surveysummary .= '</div></div>';
	}
}

/**
 * Returns html text for the registered user menu bar.
 * A simplication of "admin/html.php:showadminmenu()".
 */
function showUserMenu() {
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
	}

	$adminmenu .= "</div>";

	// Move the menu bar to its definitive position
	$adminmenu .= "<script type=\"text/javascript\">$('#mainTitleMenu').append($('.menubar'));</script>";

	return $adminmenu;
}

/**
 * Returns html text for the "first steps help" which should have
 * originally be returned by "showUserMenu()" if we had kept the
 * code in "admin/html.php:showadminmenu()".
 */
function showUserFirstStepsHelp() {
	global $surveyid, $imageurl, $clang, $action;

	$firstStepsHelp = "";

	//  $adminmenu .= "<p style='margin:0;font-size:1px;line-height:1px;height:1px;'>&nbsp;</p>"; //CSS Firefox 2 transition fix
	if (!isset($action) && !isset($surveyid) && count(getsurveylist(true))==0)
	{
		$firstStepsHelp = '<div style="width:500px;margin:0 auto;">'
		.'<h2>'.sprintf($clang->gT("Welcome to %s!"),'LimeSurvey').'</h2>'
		.'<p>'.$clang->gT("Some piece-of-cake steps to create your very own first survey:").'<br/>'
		.'<ol>'
		.'<li>'.sprintf($clang->gT('Create a new survey clicking on the %s icon in the upper right.'),"<img src='$imageurl/add_20.png' name='ShowHelp' title='' alt='". $clang->gT("Add survey")."'/>").'</li>'
		.'<li>'.$clang->gT('Create a new question group inside your survey.').'</li>'
		.'<li>'.$clang->gT('Create one or more questions inside the new question group.').'</li>'
		.'<li>'.sprintf($clang->gT('Done. Test your survey using the %s icon.'),"<img src='$imageurl/do_20.png' name='ShowHelp' title='' alt='". $clang->gT("Test survey")."'/>").'</li>'
		.'</ol></p><br />&nbsp;</div>';
	}

	return $firstStepsHelp;
}