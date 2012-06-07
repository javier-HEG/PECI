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
		
		$surveysummary .= "<div id=\"surveyPeciStepContainer\">
				<div class=\"surveyPeciStep\">Evaluate usefulness</div>
				<div class=\"surveyPeciStepArrow\">&nbsp;</div>
				<div class=\"surveyPeciStep\">Select questions</div>
				<div class=\"surveyPeciStepArrow\">&nbsp;</div>
				<div class=\"surveyPeciStep currentPeciStep\">Modify the questionnaire</div>
				<div class=\"surveyPeciStepArrow\">&nbsp;</div>
				<div class=\"surveyPeciStep\">Start survey</div>
				<div class=\"surveyPeciStepArrow\">&nbsp;</div>
				<div class=\"surveyPeciStep\">Analyze the data</div>
				<div class=\"surveyPeciStepArrow\">&nbsp;</div>
				<div class=\"surveyPeciStep\">Create the report</div>
			</div>";
		
		$surveysummary .= "<div id=\"surveyContainer\">
			<button id=\"showDetailsBtn\" style=\"float: right; margin: 4px 8px;\"
				onclick=\"$('#surveyDetails').show(); $('#showDetailsBtn').hide();\">(show survey details)</button>
			<h1 class=\"peciStep\">Modify the questionnaire</h1>
			<div id=\"surveyDetails\">
				<h2>Survey details
					<div class=\"peciActionButtons\" style=\"float: right; margin-right: -6px;\">
						<button>Edit</button>
						<button onclick=\"$('#surveyDetails').hide();  $('#showDetailsBtn').show();\">Hide</button>
					</div>
				</h2>
				<p><u>Title:</u> {$thissurvey['surveyls_title']} <br />
				<u>{$clang->gT("Base language:")}</u> $language <br />
				<u>{$clang->gT("Welcome:")}</u> {$thissurvey['surveyls_welcometext']}</p>	
			</div>";
		
		// Hide the survey details
		$surveysummary .= '<script type="text/javascript">$("#surveyDetails").hide();</script>';
		
		// Create buttons for adding new groups and questions
		$surveysummary .= "<div class=\"peciActionButtons\" style=\"margin-left: 8px;\">
			<button onClick=\"javascript:openGroupPopup('addgroup', 'surveyid=$surveyid');\">Add a new group</button>
			<button disabled=\"true\">Add a new question</button>
		</div>";
		
		// Find all question groups in this survey
		$gidquery = "SELECT gid, group_name FROM " . db_table_name('groups')
			. " WHERE sid=$surveyid AND language='" . $thissurvey['language'] . "' ORDER BY group_order";
		$gidresult = db_execute_assoc($gidquery);
		
		$groupIndex = 'A';
		$questionIndex = 1;
		if ($gidresult->RecordCount() > 0) {
			while($gv = $gidresult->FetchRow()) {
				$surveysummary .= "<div class=\"peciQuestionGroup\">\n";
				
				// Question group name
				$surveysummary .= "<div class=\"peciQuestionGroupName\">$groupIndex: ";
				$groupIndex++;
				
				if (strip_tags($gv['group_name'])) {
					$surveysummary .= htmlspecialchars(strip_tags($gv['group_name']));
				} else {
					$surveysummary .= htmlspecialchars($gv['group_name']);
				}
				
				$surveysummary .= "<div class=\"peciActionButtons\" style=\"float: right; display: inline; margin: 0px -3px;\">
					<button style=\"width: 2em;\" id=\"groupCollapser{$gv['gid']}\">-</button>
					<button style=\"width: 2em; display: none;\" id=\"groupExpander{$gv['gid']}\">+</button>
				</div>";
				
				$deleteGroupData = '{action:\'delgroup\', sid:\'' . $surveyid . '\', gid: \'' . $gv['gid'] . '\', checksessionbypost:\'' . $_SESSION['checksessionpost'] . '\'}';
				$surveysummary .= "<div class=\"peciActionButtons\" style=\"position: relative; left: 50px; top: -2px; display: inline;\">\n"
					. "<button onclick=\"javascript:openGroupPopup('editgroup', 'surveyid=$surveyid&gid={$gv['gid']}');\">Edit</button>\n"
					. "<button onclick=\"if (confirm('"
					. $clang->gT("Deleting this group will also delete any questions and answers it contains. Are you sure you want to continue?", "js")
					. "')) {submitAsParent($deleteGroupData); }\">Delete</button>
				</div>";
				
				$surveysummary .= "<input type=\"hidden\" id=\"groupExpansionState{$gv['gid']}\" value=\"shown\"/>";
				
				$surveysummary .= '</div>';
				
				// Question group questions
				$surveysummary .= '<div id="peciQuestionGroup' . $gv['gid'] . '">';
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
						
						$deleteQuestionData = '{action:\'delquestion\', sid:\'' . $surveyid . '\', gid: \'' . $gv['gid'] . '\', qid: \'' . $qrows['qid'] . '\', checksessionbypost:\'' . $_SESSION['checksessionpost'] . '\'}';
						$surveysummary .= "<div class=\"peciQuestion\">
								<div class=\"questionHeader\">Question $questionIndex
									<div class=\"peciActionButtons\" style=\"float: right;\">
										<button disabled=\"true\">Edit</button>
										<button disabled=\"true\">Move</button>
										<button disabled=\"true\">Add condition</button>&nbsp;"
							. "<button onclick=\"if (confirm('"
							. $clang->gT("Deleting this question will also delete any answer options and subquestions it includes. Are you sure you want to continue?","js")
							. "')) {submitAsParent($deleteQuestionData); }\">Delete</button>
									</div>
								</div>
								<h1 class=\"questionTitle\">{$qrows['question']}</h1>
								{$plus_qanda[1]}
							</div>";

						$questionIndex++;
					}
				}
				
				$surveysummary .= "<div class=\"peciActionButtons\" style=\"margin: 6px;\">
						<button onClick=\"javascript:openGroupPopup('addquestion', 'surveyid=$surveyid&gid={$gv['gid']}&activated={$thissurvey['active']}');\">Add a question</button>
					</div>";
				
				$surveysummary .= "</div>\n";
				$surveysummary .= "<script type=\"text/javascript\">
					$('#groupCollapser{$gv['gid']}').click(
						function() {
							$('#peciQuestionGroup{$gv['gid']}').hide();
							$('#groupCollapser{$gv['gid']}').hide();
							$('#groupExpander{$gv['gid']}').show();
					});
						
					$('#groupExpander{$gv['gid']}').click(
						function() {
							$('#peciQuestionGroup{$gv['gid']}').show();
							$('#groupCollapser{$gv['gid']}').show();
							$('#groupExpander{$gv['gid']}').hide();
					});
				</script>";				
				$surveysummary .= "</div>\n";
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
