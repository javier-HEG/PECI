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

require_once(dirname(__FILE__).'/../../qanda.php');

$surveysummary .= '<!-- JAVASCRIPT FOR CONDITIONAL QUESTIONS -->
		<script type="text/javascript">
        /* <![CDATA[ */
            function checkconditions(value, name, type) { }
			function noop_checkconditions(value, name, type) { }
        /* ]]> */
		</script>';

if (!$thissurvey['language']) {
	$language = getLanguageNameFromCode($currentadminlang, false);
} else {
	$language = getLanguageNameFromCode($thissurvey['language'], false);
}

$surveysummary .= '<script type="text/javascript">
		disablePeciSteps(["selectQuestionPeciStep", "analyzeDataPeciStep"]);
		setCurrentPeciStep("modifySurveyPeciStep");
	</script>';

// The "Start survey window"
$surveysummary .= "<div id=\"startSurveyPeciStepContent\" class=\"peciStepContainer\">";
$surveysummary .= "<p>This is a container for the instructions when starting a survey.</p>";
$surveysummary .= "</div>";

// The "Modify survey window"
$surveysummary .= "<div id=\"modifySurveyPeciStepContent\" class=\"peciStepContainer\">
	<button id=\"showDetailsBtn\" style=\"float: right; margin: 4px 8px;\"
		onclick=\"$('#surveyDetails').show(); $('#showDetailsBtn').hide();\">(show survey details)</button>
	<h1 class=\"peciStep\">Modify the questionnaire</h1>
	<div id=\"surveyDetails\">
		<h2>Survey details
			<div class=\"peciActionButtons\" style=\"float: right; margin-right: -6px;\">
				<button onClick=\"javascript:openGroupPopup('editsurveylocalesettings', 'surveyid=$surveyid');\">Edit</button>
				<button onClick=\"javascript:$('#surveyDetails').hide();  $('#showDetailsBtn').show();\">Hide</button>
			</div>
		</h2>
		<p><u>Title:</u> {$thissurvey['surveyls_title']} <br />
		<u>{$clang->gT("Base language:")}</u> $language <br />
		<u>{$clang->gT("Description:")}</u> {$thissurvey['surveyls_description']} <br />
		<u>{$clang->gT("Welcome text:")}</u> {$thissurvey['surveyls_welcometext']} <br />
		<u>{$clang->gT("End text:")}</u> {$thissurvey['surveyls_endtext']}</p>
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

				// Check if the question has subquestions or answer options
				$subquestions = '';
				$answeroptions = '';
				$qtypes = getqtypelist('','array');

				if ($qtypes[$qrows['type']]['subquestions'] > 0) {
					$subquestions = "<button onClick=\"javascript:openGroupPopup('editsubquestions', 'surveyid=$surveyid&gid={$gv['gid']}&qid={$qrows['qid']}');\">Edit subquestions</button>&nbsp;";
				}

				if ($qtypes[$qrows['type']]['answerscales'] >0) {
					$answeroptions = "<button onClick=\"javascript:openGroupPopup('editansweroptions', 'surveyid=$surveyid&gid={$gv['gid']}&qid={$qrows['qid']}');\">Edit possible answers</button>&nbsp;";
				}

				// Code for question delete button
				$deleteQuestionData = '{action:\'delquestion\', sid:\'' . $surveyid . '\', gid: \'' . $gv['gid'] . '\', qid: \'' . $qrows['qid'] . '\', checksessionbypost:\'' . $_SESSION['checksessionpost'] . '\'}';

				// Buttons
				$surveysummary .= "<div class=\"peciQuestion\">
						<div class=\"questionHeader\">Question $questionIndex
						<div class=\"peciActionButtons\" style=\"float: right;\">"
					. "<button onClick=\"javascript:openGroupPopup('editquestion', 'surveyid=$surveyid&gid={$gv['gid']}&qid={$qrows['qid']}');\">Edit</button>&nbsp;"
					. $subquestions . $answeroptions
					. "<button disabled=\"true\">Move</button>
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

$surveysummary .= "</div>\n";
