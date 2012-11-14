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

// The "Modify survey" panel
$surveysummary .= "<div id=\"modifySurveyPeciStepContent\" class=\"peciStepContainer\">
<div class=\"selectQuestion\">

	<h3>" . $clang->gT('PECI: Modify the questionnaire') . "</h3><p>" . $clang->gT('PECI: Dans cette étape, vous pouvez modifier les questions que vous avez choisies dans l\'étape précedente. Vous pouvez également changer les types des réponses ainsi qu\'ajouter vos propres questions.') . "</p></div>
		<button id=\"showDetailsBtn\" style=\"float: right; margin: 4px 8px;\"
		onclick=\"$('#surveyDetails').show(); $('#showDetailsBtn').hide();\">" . $clang->gT("PECI: show survey details") . "</button>
	<div id=\"surveyDetails\">
	
		<h2>" . $clang->gT("PECI: Survey details") . "
			<div class=\"peciActionButtons\" style=\"float: right; margin-right: -6px;\">
				<button onClick=\"javascript:openPeciPopup('editsurveylocalesettings', 'surveyid=$surveyid');\">" . $clang->gT("Edit") . "</button>
				<button onClick=\"javascript:$('#surveyDetails').hide();  $('#showDetailsBtn').show();\">" . $clang->gT("PECI: Hide") . "</button>
			</div>
		</h2>
		<p><u>{$clang->gT("Title")}:</u> {$thissurvey['surveyls_title']} <br />
		<u>{$clang->gT("Base language:")}</u> $language <br />
		<u>{$clang->gT("Description:")}</u> {$thissurvey['surveyls_description']} <br />
		<u>{$clang->gT("Welcome message:")}</u> {$thissurvey['surveyls_welcometext']} <br />
		<u>{$clang->gT("End message:")}</u> {$thissurvey['surveyls_endtext']}</p>
	</div>";

// Hide the survey details
$surveysummary .= '<script type="text/javascript">$("#surveyDetails").show(); $("#showDetailsBtn").hide();</script>';

// Create buttons for adding new groups and questions
$surveysummary .= "<div class=\"peciActionButtons\" style=\"margin-left: 8px;\">
		<button onClick=\"javascript:openPeciPopup('addgroup', 'surveyid=$surveyid');\">{$clang->gT('Add Group')}</button>
		<!--button disabled=\"true\">Add a new question</button-->
	</div>
	";

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
						<button style=\"width: 2em;\" title=\"". $clang->gT("Hide")."\" id=\"groupCollapser{$gv['gid']}\">-</button>
						<button style=\"width: 2em; display: none;\" title=\"". $clang->gT("Show")."\"  id=\"groupExpander{$gv['gid']}\">+</button>
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
		            	} else {
		            		// check if the value matches the name of a possible answer
		            		$questionRows = array("qid"=>$cqrows['qid'],
		            			"sid"=>$cqrows['sid'], "gid"=>$cqrows['gid'],
		            			"question"=>$cqrows['question'], "type"=>$cqrows['type'],
		            			"mandatory"=>$cqrows['mandatory'], "other"=>$cqrows['other'],
		            			"title"=>$cqrows['title']);
		            		
		            		$canswers = getPossibleAnswersForQid($questionRows);
		            		
		            		foreach ($canswers as $can) {
		            			if ($can[0] == $rows['cfieldname'] && $can[1] == $rows['value']) {
		            				$shortconditionsoutput .= "\t\"{$can[2]}\"";
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
					. "<div class=\"questionHeader\">" . $clang->gT("Question") . " $questionIndex\n"
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
// Create buttons for adding new groups and questions
$surveysummary .= "<div class=\"peciActionButtons\" style=\"margin-left: 8px;\">
		<button onClick=\"javascript:openPeciPopup('addgroup', 'surveyid=$surveyid');\">{$clang->gT('Add Group')}</button>
		<!--button disabled=\"true\">Add a new question</button-->
	</div>
		<br/>
		<div style=\"text-align: right; \">
		<input type=\"button\" onClick=\"setCurrentPeciStep('startSurveyPeciStep');\" class=\"buttonPeci\" value='"
	. $clang->gT('PECI: Start survey') . "' />
		</div>
	";
	
$surveysummary .= "</div>\n";

/////////////////////////////////////
// The "Start survey" panel
include("start_survey.php");


///////////////////////////////////////////////
// Disabling survey modification and setting
// the cache over the right panels

// - Set the correct state
if ($thissurvey['active'] == 'Y') {
	$surveysummary .= '<script type="text/javascript">
		setCurrentPeciStep("startSurveyPeciStep");
		$("#toModifySurveyPeciStepButton").hide();
	</script>';
} else {
	$surveysummary .= '<script type="text/javascript">
		setCurrentPeciStep("modifySurveyPeciStep");
	</script>';
}

// - Display caches
if ($thissurvey['active'] == 'Y') {
	// If the survey is active show the cache over
	// the activation panel
	$surveysummary .= '<script type="text/javascript">
		function setCacheOnPanels() {
			var eltHeight = $("#surveyActivateWrapper").height();
			var eltWidth = $("#surveyActivateWrapper").width();
			var eltOffset = $("#surveyActivateWrapper").offset();

			$("#activateSurveyCache").height(eltHeight + "px");
			$("#activateSurveyCache").width(eltWidth + "px");
			$("#activateSurveyCache").offset({ top: eltOffset.top, left: eltOffset.left });
		}
	</script>';
} else {
	// Otherwise cache both panels about responses
	// made to the surveys
	$surveysummary .= '<script type="text/javascript">
		function setCacheOnPanels() {
			var respSumHeight = $("#resultsSummaryWrapper").height();
			var respSumWidth = $("#resultsSummaryWrapper").width();
			var respSumOffset = $("#resultsSummaryWrapper").offset();
			
			$("#responseSummaryCache").height(respSumHeight + "px");
			$("#responseSummaryCache").width(respSumWidth + "px");
			$("#responseSummaryCache").offset({ top: respSumOffset.top, left: respSumOffset.left });

			var expResHeight = $("#exportresultswrapper").height();
			var expResWidth = $("#exportresultswrapper").width();
			var expResOffset = $("#exportresultswrapper").offset();
			
			$("#exportDataCache").height(expResHeight + "px");
			$("#exportDataCache").width(expResWidth + "px");
			$("#exportDataCache").offset({ top: expResOffset.top, left: expResOffset.left });
		}
	</script>';
}

////////////////////////
// Utiliy functions
////////////////////////

/**
 * Parameter fields = "qid"=>$myrows['qid'], "sid"=>$myrows['sid'], "gid"=>$myrows['gid'], "question"=>$myrows['question'],
 * "type"=>$myrows['type'], "mandatory"=>$myrows['mandatory'], "other"=>$myrows['other'], "title"=>$myrows['title']
 * @param unknown_type $rows
 */
function getPossibleAnswersForQid($rows) {
	global $clang;
	$surveyid = $rows['sid'];
	
	$X="X";
	
	$shortquestion=$rows['title'].": ".strip_tags($rows['question']);

	if ($rows['type'] == "A" ||
	$rows['type'] == "B" ||
	$rows['type'] == "C" ||
	$rows['type'] == "E" ||
	$rows['type'] == "F" ||
	$rows['type'] == "H" )
	{
		$aquery="SELECT * "
		.'FROM ' . db_table_name('questions') . ' '
		."WHERE parent_qid={$rows['qid']} "
		."AND language='".GetBaseLanguageFromSurveyID($rows['sid'])."' "
		."ORDER BY question_order";

		$aresult=db_execute_assoc($aquery) or safe_die ("Couldn't get answers to Array questions<br />$aquery<br />".$connect->ErrorMsg());

		while ($arows = $aresult->FetchRow())
		{
			$shortanswer = "{$arows['title']}: [" . FlattenText($arows['question']) . "]";
			$shortquestion=$rows['title'].":$shortanswer ".FlattenText($rows['question']);
			$cquestions[]=array($shortquestion, $rows['qid'], $rows['type'], $rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title']);

			switch ($rows['type'])
			{
				case "A": //Array 5 buttons
					for ($i=1; $i<=5; $i++)
					{
						$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], $i, $i);
					}
					break;
				case "B": //Array 10 buttons
					for ($i=1; $i<=10; $i++)
					{
						$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], $i, $i);
					}
					break;
				case "C": //Array Y/N/NA
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], "Y", $clang->gT("Yes"));
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], "U", $clang->gT("Uncertain"));
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], "N", $clang->gT("No"));
					break;
				case "E": //Array >/=/<
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], "I", $clang->gT("Increase"));
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], "S", $clang->gT("Same"));
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], "D", $clang->gT("Decrease"));
					break;
				case "F": //Array Flexible Row
				case "H": //Array Flexible Column
					$fquery = "SELECT * "
					.'FROM ' . db_table_name('answers') . ' '
					."WHERE qid={$rows['qid']} "
					."AND language='".GetBaseLanguageFromSurveyID($rows['sid'])."' "
					."AND scale_id=0 "
					."ORDER BY sortorder, code ";
					$fresult = db_execute_assoc($fquery);
					while ($frow=$fresult->FetchRow())
					{
						$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], $frow['code'], $frow['answer']);
					}
					break;
			}
			// Only Show No-Answer if question is not mandatory
			if ($rows['mandatory'] != 'Y')
			{
				$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], "", $clang->gT("No answer"));
			}

		} //while
	}
	elseif ($rows['type'] == ":" || $rows['type'] == ";")
	{
		// Multiflexi

		//Get question attribute for $canswers
		$qidattributes=getQuestionAttributes($rows['qid'], $rows['type']);
		if (isset($qidattributes['multiflexible_max']) && trim($qidattributes['multiflexible_max'])!='') {
			$maxvalue=$qidattributes['multiflexible_max'];
		} else {
			$maxvalue=10;
		}
		if (isset($qidattributes['multiflexible_min']) && trim($qidattributes['multiflexible_min'])!='') {
			$minvalue=$qidattributes['multiflexible_min'];
		} else {
			$minvalue=1;
		}
		if (isset($qidattributes['multiflexible_step']) && trim($qidattributes['multiflexible_step'])!='') {
			$stepvalue=$qidattributes['multiflexible_step'];
		} else {
			$stepvalue=1;
		}

		if (isset($qidattributes['multiflexible_checkbox']) && $qidattributes['multiflexible_checkbox']!=0) {
			$minvalue=0;
			$maxvalue=1;
			$stepvalue=1;
		}
		// Get the Y-Axis

		$fquery = "SELECT sq.*, q.other"
		." FROM ".db_table_name('questions')." sq, ".db_table_name('questions')." q"
		." WHERE sq.sid=$surveyid AND sq.parent_qid=q.qid "
		. "AND q.language='".GetBaseLanguageFromSurveyID($surveyid)."'"
		." AND sq.language='".GetBaseLanguageFromSurveyID($surveyid)."'"
		." AND q.qid={$rows['qid']}
		AND sq.scale_id=0
	               ORDER BY sq.question_order";

		$y_axis_db = db_execute_assoc($fquery);

		// Get the X-Axis
		$aquery = "SELECT sq.*
			FROM ".db_table_name('questions')." q, ".db_table_name('questions')." sq
			WHERE q.sid=$surveyid
	                         AND sq.parent_qid=q.qid
			AND q.language='".GetBaseLanguageFromSurveyID($surveyid)."'
			AND sq.language='".GetBaseLanguageFromSurveyID($surveyid)."'
			AND q.qid=".$rows['qid']."
	                         AND sq.scale_id=1
	                         ORDER BY sq.question_order";

		$x_axis_db=db_execute_assoc($aquery) or safe_die ("Couldn't get answers to Array questions<br />$aquery<br />".$connect->ErrorMsg());

		while ($frow=$x_axis_db->FetchRow())
		{
			$x_axis[$frow['title']]=$frow['question'];
		}

		while ($arows = $y_axis_db->FetchRow())
		{
			foreach($x_axis as $key=>$val)
			{
				$shortquestion=$rows['title'].":{$arows['title']}:$key: [".strip_tags($arows['question']). "][" .strip_tags($val). "] " . FlattenText($rows['question']);
				$cquestions[]=array($shortquestion, $rows['qid'], $rows['type'], $rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title']."_".$key);

				if ($rows['type'] == ":")
				{
					for($ii=$minvalue; $ii<=$maxvalue; $ii+=$stepvalue)
					{
						$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], $ii, $ii);
					}
				}
			}
		}
		unset($x_axis);
	} //if A,B,C,E,F,H
	elseif ($rows['type'] == "1") //Multi Scale
	{
		$aquery="SELECT * "
		.'FROM ' . db_table_name('questions') . ' '
		."WHERE parent_qid={$rows['qid']} "
		."AND language='".GetBaseLanguageFromSurveyID($surveyid)."' "
		."ORDER BY question_order";
		$aresult=db_execute_assoc($aquery) or safe_die ("Couldn't get answers to Array questions<br />$aquery<br />".$connect->ErrorMsg());

		while ($arows = $aresult->FetchRow())
		{
			$attr = getQuestionAttributes($rows['qid']);
			$label1 = isset($attr['dualscale_headerA']) ? $attr['dualscale_headerA'] : 'Label1';
			$label2 = isset($attr['dualscale_headerB']) ? $attr['dualscale_headerB'] : 'Label2';
			$shortanswer = "{$arows['title']}: [" . strip_tags($arows['question']) . "][$label1]";
			$shortquestion=$rows['title'].":$shortanswer ".strip_tags($rows['question']);
			$cquestions[]=array($shortquestion, $rows['qid'], $rows['type'], $rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title']."#0");

			$shortanswer = "{$arows['title']}: [" . strip_tags($arows['question']) . "][$label2]";
			$shortquestion=$rows['title'].":$shortanswer ".strip_tags($rows['question']);
			$cquestions[]=array($shortquestion, $rows['qid'], $rows['type'], $rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title']."#1");

			// first label
			$lquery="SELECT * "
			.'FROM ' . db_table_name('answers') . ' '
			."WHERE qid={$rows['qid']} "
			."AND scale_id=0 "
			."AND language='".GetBaseLanguageFromSurveyID($surveyid)."' "
			."ORDER BY sortorder, answer";
			$lresult=db_execute_assoc($lquery) or safe_die ("Couldn't get labels to Array <br />$lquery<br />".$connect->ErrorMsg());
			while ($lrows = $lresult->FetchRow())
			{
				$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title']."#0", "{$lrows['code']}", "{$lrows['code']}");
			}

			// second label
			$lquery="SELECT * "
			.'FROM ' . db_table_name('answers') . ' '
			."WHERE qid={$rows['qid']} "
			."AND scale_id=1 "
			."AND language='".GetBaseLanguageFromSurveyID($surveyid)."' "
			."ORDER BY sortorder, answer";
			$lresult=db_execute_assoc($lquery) or safe_die ("Couldn't get labels to Array <br />$lquery<br />".$connect->ErrorMsg());
			while ($lrows = $lresult->FetchRow())
			{
				$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title']."#1", "{$lrows['code']}", "{$lrows['code']}");
			}

			// Only Show No-Answer if question is not mandatory
			if ($rows['mandatory'] != 'Y')
			{
				$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title']."#0", "", $clang->gT("No answer"));
				$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title']."#1", "", $clang->gT("No answer"));
			}
		} //while
	}
	elseif ($rows['type'] == "K" ||$rows['type'] == "Q") //Multi shorttext/numerical
	{
		$aquery="SELECT * "
		.'FROM ' . db_table_name('questions') . ' '
		."WHERE parent_qid={$rows['qid']} "
		."AND language='".GetBaseLanguageFromSurveyID($surveyid)."' "
		."ORDER BY question_order";
		$aresult=db_execute_assoc($aquery) or safe_die ("Couldn't get answers to Array questions<br />$aquery<br />".$connect->ErrorMsg());

		while ($arows = $aresult->FetchRow())
		{
			$shortanswer = "{$arows['title']}: [" . strip_tags($arows['question']) . "]";
			$shortquestion=$rows['title'].":$shortanswer ".strip_tags($rows['question']);
			$cquestions[]=array($shortquestion, $rows['qid'], $rows['type'], $rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title']);

			// Only Show No-Answer if question is not mandatory
			if ($rows['mandatory'] != 'Y')
			{
				$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], "", $clang->gT("No answer"));
			}

		} //while
	}
	elseif ($rows['type'] == "R") //Answer Ranking
	{
		$aquery="SELECT * "
		.'FROM ' . db_table_name('answers') . ' '
		."WHERE qid={$rows['qid']} "
		."AND ".db_table_name('answers').".language='".GetBaseLanguageFromSurveyID($surveyid)."' "
		."AND scale_id=0 "
		."ORDER BY sortorder, answer";
		$aresult=db_execute_assoc($aquery) or safe_die ("Couldn't get answers to Ranking question<br />$aquery<br />".$connect->ErrorMsg());
		$acount=$aresult->RecordCount();
		while ($arow=$aresult->FetchRow())
		{
			$theanswer = addcslashes($arow['answer'], "'");
			$quicky[]=array($arow['code'], $theanswer);
		}
		for ($i=1; $i<=$acount; $i++)
		{
			$cquestions[]=array("{$rows['title']}: [RANK $i] ".strip_tags($rows['question']), $rows['qid'], $rows['type'], $rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$i);
			foreach ($quicky as $qck)
			{
				$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$i, $qck[0], $qck[1]);
			}
			// Only Show No-Answer if question is not mandatory
			if ($rows['mandatory'] != 'Y')
			{
				$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$i, " ", $clang->gT("No answer"));
			}
		}
		unset($quicky);
	} // End if type R
	elseif($rows['type'] == "M" || $rows['type'] == "P")
	{
		$shortanswer = " [".$clang->gT("Group of checkboxes")."]";
		$shortquestion=$rows['title'].":$shortanswer ".strip_tags($rows['question']);
		$cquestions[]=array($shortquestion, $rows['qid'], $rows['type'], $rows['sid'].$X.$rows['gid'].$X.$rows['qid']);
		$aquery="SELECT * "
		.'FROM ' . db_table_name('questions') . ' '
		."WHERE parent_qid={$rows['qid']} "
		."AND language='".GetBaseLanguageFromSurveyID($rows['sid'])."' "
		."ORDER BY question_order";
		$aresult=db_execute_assoc($aquery) or safe_die ("Couldn't get answers to this question<br />$aquery<br />".$connect->ErrorMsg());

		while ($arows=$aresult->FetchRow())
		{
			$theanswer = addcslashes($arows['question'], "'");
			$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], $arows['title'], $theanswer);

			$shortanswer = "{$arows['title']}: [" . strip_tags($arows['question']) . "]";
			$shortanswer .= "[".$clang->gT("Single checkbox")."]";
			$shortquestion=$rows['title'].":$shortanswer ".strip_tags($rows['question']);
			$cquestions[]=array($shortquestion, $rows['qid'], $rows['type'], "+".$rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title']);
			$canswers[]=array("+".$rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], 'Y', $clang->gT("checked"));
			$canswers[]=array("+".$rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], '', $clang->gT("not checked"));
		}
	}
	elseif($rows['type'] == "X") //Boilerplate question
	{
		//Just ignore this questiontype
	}
	else
	{
		$cquestions[]=array($shortquestion, $rows['qid'], $rows['type'], $rows['sid'].$X.$rows['gid'].$X.$rows['qid']);
		switch ($rows['type'])
		{
			case "Y": // Y/N/NA
				$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], "Y", $clang->gT("Yes"));
				$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], "N", $clang->gT("No"));
				// Only Show No-Answer if question is not mandatory
				if ($rows['mandatory'] != 'Y')
				{
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], " ", $clang->gT("No answer"));
				}
				break;
			case "G": //Gender
				$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], "F", $clang->gT("Female"));
				$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], "M", $clang->gT("Male"));
				// Only Show No-Answer if question is not mandatory
				if ($rows['mandatory'] != 'Y')
				{
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], " ", $clang->gT("No answer"));
				}
				break;
			case "5": // 5 choice
				for ($i=1; $i<=5; $i++)
				{
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], $i, $i);
				}
				// Only Show No-Answer if question is not mandatory
				if ($rows['mandatory'] != 'Y')
				{
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], " ", $clang->gT("No answer"));
				}
				break;

			case "N": // Simple Numerical questions

				// Only Show No-Answer if question is not mandatory
				if ($rows['mandatory'] != 'Y')
				{
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], " ", $clang->gT("No answer"));
				}
				break;

			default:
				$aquery="SELECT * "
			.'FROM ' . db_table_name('answers') . ' '
			."WHERE qid={$rows['qid']} "
			."AND language='".GetBaseLanguageFromSurveyID($rows['sid'])."' "
			."AND scale_id=0 "
			."ORDER BY sortorder, "
			."answer";
			// Ranking question? Replacing "Ranking" by "this"
			$aresult=db_execute_assoc($aquery) or safe_die ("Couldn't get answers to this question<br />$aquery<br />".$connect->ErrorMsg());

			while ($arows=$aresult->FetchRow())
			{
				$theanswer = addcslashes($arows['answer'], "'");
				$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], $arows['code'], $theanswer);
			}
			if ($rows['type'] == "D")
			{
				// Only Show No-Answer if question is not mandatory
				if ($rows['mandatory'] != 'Y')
				{
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], " ", $clang->gT("No answer"));
				}
			}
			elseif ($rows['type'] != "M" &&
			$rows['type'] != "P" &&
			$rows['type'] != "J" &&
			$rows['type'] != "I" )
			{
				// For dropdown questions
				// optinnaly add the 'Other' answer
				if ( ($rows['type'] == "L" ||
				$rows['type'] == "!") &&
				$rows['other'] == "Y")
				{
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], "-oth-", $clang->gT("Other"));
				}

				// Only Show No-Answer if question is not mandatory
				if ($rows['mandatory'] != 'Y')
				{
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], " ", $clang->gT("No answer"));
				}
			}
			break;
		}//switch row type
	} //else
	
	return $canswers;
}
