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

require_once(dirname(__FILE__).'/../qanda.php');

// Show selected survey
if (isset($surveyid) && $surveyid && $action=='') {
	if(bHasSurveyPermission($surveyid,'survey','read'))	{
		$baselang = GetBaseLanguageFromSurveyID($surveyid);
		
		// Getting data for this survey
		$sumquery = "SELECT * FROM ".db_table_name('surveys')." inner join ".db_table_name('surveys_languagesettings')." on (surveyls_survey_id=sid and surveyls_language=language) WHERE sid=$surveyid";
		$sumresult = db_select_limit_assoc($sumquery, 1);

		// If surveyid is invalid then die to prevent errors at a later time
		if ($sumresult->RecordCount()==0){
			die('Invalid survey id');
		}
	
		$thissurvey = $sumresult->FetchRow();
		$thissurvey = array_map('FlattenText', $thissurvey);
		
		if (!$thissurvey['language']) {
			$language = getLanguageNameFromCode($currentadminlang, false);
		} else {
			$language = getLanguageNameFromCode($thissurvey['language'], false);
		}
		
		// Output starts here...
		$surveysummary = '<!-- JAVASCRIPT FOR CONDITIONAL QUESTIONS -->
			<script type="text/javascript">
	        /* <![CDATA[ */
	            function checkconditions(value, name, type) { }
				function noop_checkconditions(value, name, type) { }
	        /* ]]> */
			</script>';
		
		$surveysummary .= '<div style="background-color: lightgray; width: 100%; margin: -1px 0px; padding-bottom: 6px;">';
		
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
			. '<p><u>Title:</u>' . $thissurvey['surveyls_title'] . '<br />'
			. '<u>' . $clang->gT("Base language:") . '</u> ' . $language
			. '<br /><u>' . $clang->gT("Welcome:") . '</u> ' . $thissurvey['surveyls_welcometext'] . '</p>'	
			. '</div>';
		
		// Hide the survey details
		$surveysummary .= '<script type="text/javascript">$("#surveyDetails").hide();</script>';
		
		// Find all question groups in this survey
		$gidquery = "SELECT gid, group_name FROM " . db_table_name('groups')
			. " WHERE sid=$surveyid AND language='" . $thissurvey['language'] . "' ORDER BY group_order";
		$gidresult = db_execute_assoc($gidquery);
		
		$groupIndex = 'A';
		$questionIndex = 1;
		if ($gidresult->RecordCount() > 0) {
			while($gv = $gidresult->FetchRow()) {
				$surveysummary .= '<div class="questionGroup"><h1 class="questionGroupName">' . $groupIndex++ . ': ';
	
				if (strip_tags($gv['group_name'])) {
					$surveysummary .= htmlspecialchars(strip_tags($gv['group_name']));
				} else {
					$surveysummary .= htmlspecialchars($gv['group_name']);
				}
				
				$surveysummary .= '</h1>';
				
				$qquery = 'SELECT * FROM ' . db_table_name('questions')
					. " WHERE sid=$surveyid AND gid={$gv['gid']} AND language='{$thissurvey['language']}' and parent_qid=0 order by question_order";
				$qresult = db_execute_assoc($qquery);
				
				if ($qresult->RecordCount() > 0) {
					while($qrows = $qresult->FetchRow()) {
						$ia = array(0 => $qrows['qid'],
							1 => $surveyid.'X'.$qrows['gid'].'X'.$qrows['qid'],
							2 => $qrows['title'],
							3 => $qrows['question'],
							4 => $qrows['type'],
							5 => $qrows['gid'],
							6 => $qrows['mandatory'],
							//7 => $qrows['other']); // ia[7] is conditionsexist not other
							7 => 'N',
							8 => 'N' ); // ia[8] is usedinconditions
						
						// Session values are needed to use qanda.php::retrieveAnswers()
						$_SESSION['s_lang'] = $thissurvey['language'];
						$_SESSION['dateformats'] = getDateFormatData($thissurvey['surveyls_dateformat']);
						list($plus_qanda, $plus_inputnames)=retrieveAnswers($ia);
						
						$surveysummary .= '<div class="question">'
							. '<div class="questionHeader">Question ' . $questionIndex++ . '</div>'
							. '<h1 class="questionTitle">' . $qrows['question'] . '</h1>'
							. $plus_qanda[1]
							. '</div>';	
					}
				}
				
				$surveysummary .= '</div>';	
			}
		}
		
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