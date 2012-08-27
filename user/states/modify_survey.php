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

// table used for conditions
// this array will be used soon,
// to explain wich conditions is used to evaluate the question
$method = array(
	"<"  => $clang->gT("Less than"),
	"<=" => $clang->gT("Less than or equal to"),
	"==" => $clang->gT("PECI: equals"),
	"!=" => $clang->gT("Not equal to"),
	">=" => $clang->gT("Greater than or equal to"),
	">"  => $clang->gT("Greater than"),
	"RX" => $clang->gT("Regular expression")
);


$surveysummary .= '<script type="text/javascript">
		disablePeciSteps(["selectQuestionPeciStep", "analyzeDataPeciStep"]);
		setCurrentPeciStep("modifySurveyPeciStep");
	</script>';

// The "Start survey window"
include("start_survey.php");

// The "Modify survey window"
$surveysummary .= "<div id=\"modifySurveyPeciStepContent\" class=\"peciStepContainer\">
	<button id=\"showDetailsBtn\" style=\"float: right; margin: 4px 8px;\"
		onclick=\"$('#surveyDetails').show(); $('#showDetailsBtn').hide();\">(show survey details)</button>
	<h1 class=\"peciStep\">" . $clang->gT('PECI: Modify the questionnaire') . "</h1>
	<div id=\"surveyDetails\">
		<h2>Survey details
			<div class=\"peciActionButtons\" style=\"float: right; margin-right: -6px;\">
				<button onClick=\"javascript:openPeciPopup('editsurveylocalesettings', 'surveyid=$surveyid');\">Edit</button>
				<button onClick=\"javascript:$('#surveyDetails').hide();  $('#showDetailsBtn').show();\">Hide</button>
			</div>
		</h2>
		<p><u>{$clang->gT("Title")}:</u> {$thissurvey['surveyls_title']} <br />
		<u>{$clang->gT("Base language:")}</u> $language <br />
		<u>{$clang->gT("Description:")}</u> {$thissurvey['surveyls_description']} <br />
		<u>{$clang->gT("Welcome text:")}</u> {$thissurvey['surveyls_welcometext']} <br />
		<u>{$clang->gT("End text:")}</u> {$thissurvey['surveyls_endtext']}</p>
	</div>";

// Hide the survey details
$surveysummary .= '<script type="text/javascript">$("#surveyDetails").hide();</script>';

// Create buttons for adding new groups and questions
$surveysummary .= "<div class=\"peciActionButtons\" style=\"margin-left: 8px;\">
		<button onClick=\"javascript:openPeciPopup('addgroup', 'surveyid=$surveyid');\">{$clang->gT('Add Group')}</button>
		<!--button disabled=\"true\">Add a new question</button-->
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

		// Question group index
		$surveysummary .= "<div class=\"peciQuestionGroupName\">$groupIndex: ";
		$groupIndex++;
		
		// Question group name
		$groupName = trim($gv['group_name']);
		if (strip_tags($groupName)) {
			$groupName = trim(strip_tags($gv['group_name']));
		}
		if ($groupName == '') {
			$groupName = '(No group name has been given)';
		}
		
		$surveysummary .= htmlspecialchars($groupName);

		// Control buttons
		$surveysummary .= "<div class=\"peciActionButtons\" style=\"float: right; display: inline; margin: 0px -3px;\">
						<button style=\"width: 2em;\" id=\"groupCollapser{$gv['gid']}\">-</button>
						<button style=\"width: 2em; display: none;\" id=\"groupExpander{$gv['gid']}\">+</button>
					</div>";

		$deleteGroupData = '{action:\'delgroup\', sid:\'' . $surveyid . '\', gid: \'' . $gv['gid'] . '\', checksessionbypost:\'' . $_SESSION['checksessionpost'] . '\'}';
		$surveysummary .= "<div class=\"peciActionButtons\" style=\"position: relative; left: 50px; top: -2px; display: inline;\">\n"
			. "<button onclick=\"javascript:openPeciPopup('editgroup', 'surveyid=$surveyid&gid={$gv['gid']}');\">{$clang->gT('Edit')}</button>\n"
			. "<button onclick=\"if (confirm('"
			. $clang->gT("Deleting this group will also delete any questions and answers it contains. Are you sure you want to continue?", "js")
			. "')) {submitAsParent($deleteGroupData); }\">{$clang->gT('Delete')}</button>"
			. "</div>";

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

				// Check if the question has a condition and generate a short description
				$conditionExist = false;
				$shortconditionsoutput = '';
				
				$scenarioquery = "SELECT DISTINCT {$dbprefix}conditions.scenario "
					."FROM {$dbprefix}conditions "
					."WHERE {$dbprefix}conditions.qid = {$qrows['qid']} "
				    ."ORDER BY {$dbprefix}conditions.scenario";
				$scenarioresult = db_execute_assoc($scenarioquery) or safe_die ("Couldn't get other (scenario) conditions for question $qid<br />$query<br />".$connect->Error);
				$scenariocount = $scenarioresult->RecordCount();
				
				if ($scenariocount > 0) {
				    $scenarionr = $scenarioresult->FetchRow();

				    // Get conditions in scenario
				    $query = "SELECT {$dbprefix}conditions.cid, "
					    ."{$dbprefix}conditions.scenario, "
					    ."{$dbprefix}conditions.cqid, "
					    ."{$dbprefix}conditions.cfieldname, "
					    ."{$dbprefix}conditions.method, "
					    ."{$dbprefix}conditions.value, "
					    ."{$dbprefix}questions.type "
					    ."FROM {$dbprefix}conditions, "
					    ."{$dbprefix}questions, "
					    ."{$dbprefix}groups "
					    ."WHERE {$dbprefix}conditions.cqid={$dbprefix}questions.qid "
					    ."AND {$dbprefix}questions.gid={$dbprefix}groups.gid "
					    ."AND {$dbprefix}questions.parent_qid=0 "
					    ."AND {$dbprefix}questions.language='" . $thissurvey['language'] . "' "
					    ."AND {$dbprefix}groups.language='". $thissurvey['language'] . "' "
					    ."AND {$dbprefix}conditions.qid={$qrows['qid']} "
					    ."AND {$dbprefix}conditions.scenario={$scenarionr['scenario']}\n"
					    ."AND {$dbprefix}conditions.cfieldname NOT LIKE '{%' \n" // avoid catching SRCtokenAttr conditions
					    ."ORDER BY {$dbprefix}groups.group_order,{$dbprefix}questions.question_order";
				    $result = db_execute_assoc($query) or safe_die ("Couldn't get other conditions for question $qid<br />$query<br />".$connect->ErrorMsg());
				    $conditionscount = $result->RecordCount();

				    if ($conditionscount > 0) {
				    	// In PECI we care only for the first condition
					    $conditionExist = true;
			            $rows = $result->FetchRow();
				    	
		            	$leftOperandType = 'unknown';
	            		$leftOperandType = 'prevquestion';
	            		
	            		// get condition-questions text
	            		$cqquery = 'SELECT * FROM ' . db_table_name('questions')
	            			. " WHERE qid={$rows['cqid']} AND language='{$thissurvey['language']}'";
	            		$cqresult = db_execute_assoc($cqquery);
	            		$cqrows = $cqresult->FetchRow();
	            		
	            		$shortconditionsoutput .= $clang->gT('PECI: Triggered if') . "\t\"{$cqrows['question']}\"";

	            		$shortconditionsoutput .= "\t" . $method[$rows['method']];

		            	// let's read the condition's right operand
		            	// determine its type and display it
		            	$rightOperandType = 'unknown';
		            	
		            	if ($rows['method'] == 'RX') {
		            		$rightOperandType = 'regexp';
		            		$shortconditionsoutput .= "\t\"" . html_escape($rows['value'])."\"\n";
		            	} elseif (preg_match('/^@[0-9]+X[0-9]+X([^@]*)@$/', $rows['value'], $matchedSGQA) > 0) {
		            		// Another questions answer
		            		$aqquery = 'SELECT * FROM ' . db_table_name('questions')
		            			. " WHERE qid={$matchedSGQA[1]} AND language='{$thissurvey['language']}'";
		            		$aqresult = db_execute_assoc($aqquery);
		            		
		            		$matchedSGQAText = $clang->gT("Not found");
		            		if ($aqrows = $aqresult->FetchRow()) {
		            			$matchedSGQAText = $aqrows['question'];
		            			$rightOperandType = 'prevQsgqa';
		            		}
		            		
		            		$shortconditionsoutput .= "\t\"" . html_escape($matchedSGQAText)."\"\n";
		            	} elseif (isset($canswers)) {
		            		foreach ($canswers as $can) {
		            			if ($can[0] == $rows['cfieldname'] && $can[1] == $rows['value']) {
		            				$shortconditionsoutput .= "\t $can[2]";
		            				$rightOperandType = 'predefinedAnsw';
		            			}
		            		}
		            	}
		            	
		            	// if $rightOperandType is still unkown then it is a simple constant
		            	if ($rightOperandType == 'unknown') {
		            		$rightOperandType = 'constantVal';
		            		if ($rows['value'] == ' ' || $rows['value'] == '') {
		            			$shortconditionsoutput .= "\t\"" . $clang->gT("No answer")."\"\n";
		            		} else {
		            			$shortconditionsoutput .= "\t\"" . html_escape($rows['value'])."\"\n";
		            		}
		            	}
		            	
		            	// This is used when Editting a condition
		            	if ($rightOperandType == 'predefinedAnsw') {
		            		$rightOperandType = "EDITcanswers[]=" . html_escape($rows['value']);
		            	} elseif ($rightOperandType == 'prevQsgqa') {
		            		$rightOperandType = "EDITprevQuestionSGQA=" . html_escape($rows['value']);
		            	} elseif ($rightOperandType == 'tokenAttr') {
		            		$rightOperandType = "EDITtokenAttr=" . html_escape($rows['value']);
		            	} elseif ($rightOperandType == 'regexp') {
		            		$rightOperandType = "EDITConditionRegexp=" . html_escape($rows['value']);
		            	} else {
		            		$rightOperandType = "EDITConditionConst=" . html_escape($rows['value']);
		            	}
				    }
				}				
				
				// Check if the question has subquestions or answer options
				$subquestions = '';
				$answeroptions = '';
				$qtypes = getqtypelist('','array');

				if ($qtypes[$qrows['type']]['subquestions'] > 0) {
					$subquestions = "<button onClick=\"javascript:openPeciPopup('editsubquestions', 'surveyid=$surveyid&gid={$gv['gid']}&qid={$qrows['qid']}');\">{$clang->gT('Edit subquestions')}</button>&nbsp;";
				}

				if ($qtypes[$qrows['type']]['answerscales'] >0) {
					$answeroptions = "<button onClick=\"javascript:openPeciPopup('editansweroptions', 'surveyid=$surveyid&gid={$gv['gid']}&qid={$qrows['qid']}');\">{$clang->gT('Edit answer options')}</button>&nbsp;";
				}

				// Code for question delete button
				$deleteQuestionData = '{action:\'delquestion\', sid:\'' . $surveyid . '\', gid: \'' . $gv['gid'] . '\', qid: \'' . $qrows['qid'] . '\', checksessionbypost:\'' . $_SESSION['checksessionpost'] . '\'}';

				// Buttons
				$getParams = "subaction=editconditionsform&sid=$surveyid&gid={$gv['gid']}&qid={$qrows['qid']}&checksessionbypost={$_SESSION['checksessionpost']}";
				$conditionButtonCode = "<button onClick=\"javascript:openPeciPopup('conditions', '$getParams');\">" . $clang->gT('Add condition') . "</button>&nbsp;\n";
				if ($conditionExist) {
					$getParams = "subaction=editthiscondition&cid={$rows['cid']}&scenario={$scenarionr['scenario']}&method={$rows['method']}&"
						. "sid=$surveyid&gid={$gv['gid']}&qid={$qrows['qid']}&cquestions={$rows['cfieldname']}&$rightOperandType&checksessionbypost={$_SESSION['checksessionpost']}";
					
					$conditionButtonCode = "<button onClick=\"javascript:openPeciPopup('conditions', '$getParams');\">" . $clang->gT('PECI: Edit condition') . "</button>&nbsp;\n";
				}
				
				$surveysummary .= "<div class=\"peciQuestion\">\n"
					. "<div class=\"questionHeader\">Question $questionIndex\n"
					. "<div class=\"peciActionButtons\" style=\"float: right;\">\n"
					. "<button onClick=\"javascript:openPeciPopup('editquestion', 'surveyid=$surveyid&gid={$gv['gid']}&qid={$qrows['qid']}');\">{$clang->gT('Edit')}</button>&nbsp;\n"
					. $subquestions . $answeroptions
// 					. "<button disabled=\"true\">{$clang->gT('PECI: Move')}</button>\n"
					. ($conditionExist || $questionIndex > 1 ? $conditionButtonCode : '')
					. "<button onclick=\"if (confirm('"
					. $clang->gT("Deleting this question will also delete any answer options and subquestions it includes. Are you sure you want to continue?","js")
					. "')) {submitAsParent($deleteQuestionData); }\">{$clang->gT('Delete')}</button>\n"
					. "</div></div>\n"
					. "<h1 class=\"questionTitle\">{$qrows['question']}</h1>\n"
					. ($conditionExist ? "<p class=\"questionCondition\"><img src='$imageurl/user/silk/error.png' style='vertical-align: bottom; margin-right: 6px;' />$shortconditionsoutput</p>\n" : '')
					. $plus_qanda[1] . "</div>\n";

				$questionIndex++;
			}
		}

		$surveysummary .= "<div class=\"peciActionButtons\" style=\"margin: 6px;\">
				<button onClick=\"javascript:openPeciPopup('addquestion', 'surveyid=$surveyid&gid={$gv['gid']}&activated={$thissurvey['active']}');\">{$clang->gT('Add New Question to Group')}</button>
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
