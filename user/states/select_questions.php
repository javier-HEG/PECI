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

$templatequeryid = "51417";

$surveysummary .= '<script type="text/javascript">
		setCurrentPeciStep("selectQuestionPeciStep");
		
		function confirmAndImport() {
			if (importingAtLeastOne()) {
				if (confirm("' . $clang->gT('PECI: Save selected questions? You won\'t be able to return to this section anymore.') . '")) {
					$.post("questionimport.php", whatToImportInJson(), function() {
	  		 			setSurveyFaxto("");
					});
				}
			} else {
				if (confirm("' . $clang->gT('PECI: Import nothing?') . '")) {
					setSurveyFaxto("");
				}
			}
		}
		
		function setSurveyFaxto(value) {
			$.post("user.php", {sid: "' . $surveyid . '", faxto: value, action: "updateFaxTo",
				checksessionbypost: "'. $_SESSION['checksessionpost'] .'"}, function() {
					location.href = "user.php?sid=' . $surveyid . '";
			});
		}
		
		var groupIds = [];
		var groupQuestions = [];

		function whatToImportInJson() {
			var jsonObject = {
				sid : "' . $surveyid . '",
				checksessionbypost : "' . $_SESSION['checksessionpost'] . '",
				templatequeryid : "' . $templatequeryid . '",
				language : "' . $thissurvey['language'] . '",
				groupIds : [],
				groupQuestions : []
			};
			
			for (var i = 0; i < groupIds.length; i++) {
				var questions = [];
				
 				for (var j = 0; j < groupQuestions[i].length; j++) {
 					var checkboxId = "questionCheck-" + groupIds[i] + "-" + groupQuestions[i][j];
 					
 					if ($("#" + checkboxId).is(":checked")) {
 						questions.push(groupQuestions[i][j]);
 					}
 				}
 				
 				if (questions.length > 0) {
 					jsonObject.groupIds.push(groupIds[i]);
 					jsonObject.groupQuestions.push(questions);
				}
			}
			
			return jsonObject;
		}
		
		function importingAtLeastOne() {
			var atLeastOne = false;
			
			for (var i = 0; i < groupIds.length; i++) {
				for (var j = 0; j < groupQuestions[i].length; j++) {
					var checkboxId = "questionCheck-" + groupIds[i] + "-" + groupQuestions[i][j];
 					
 					if ($("#" + checkboxId).is(":checked")) {
 						atLeastOne = true;
 					}
				}
			}
			
			return atLeastOne;
		}
	</script>';

// Fill in $surveysummary the list of questions in template survey
$templatecontent = '';
// - Load information about the template survey
$templatequery = "SELECT * FROM ".db_table_name('surveys')
	. " inner join " . db_table_name('surveys_languagesettings')
	. " on (surveyls_survey_id=sid and surveyls_language=language) WHERE sid=$templatequeryid";
$templateresult = db_select_limit_assoc($templatequery, 1);
$templatesurvey = $templateresult->FetchRow();
$templatesurvey = array_map('FlattenText', $templatesurvey);

// - Load language from the current survey language
$languageCode = $thissurvey['language'];
$language = getLanguageNameFromCode($languageCode, false);

// - Print groups and questions titles
$gidquery = "SELECT gid, group_name FROM " . db_table_name('groups')
	. " WHERE sid=$templatequeryid AND language='" . $languageCode . "' ORDER BY group_order";
$gidresult = db_execute_assoc($gidquery);

if ($gidresult->RecordCount() > 0) {
	while($gv = $gidresult->FetchRow()) {
		$templatecontent .= "<div class=\"peciQuestionGroup\">\n";
		// Add group to list
		$templatecontent .= "<script>
				groupIds[groupIds.length] = '{$gv['gid']}';
				groupQuestions[groupQuestions.length] = [];
			</script>";
		
		// Group title bar
		$templatecontent .= "<div class=\"peciQuestionGroupName\">";

		$groupName = trim($gv['group_name']);
		if (strip_tags($groupName)) {
			$groupName = trim(strip_tags($gv['group_name']));
		}
		if ($groupName == '') {
			$groupName = '(No group name has been given)';
		}
		
		$templatecontent .= htmlspecialchars($groupName);
		$templatecontent .= "<div class=\"peciActionButtons\" style=\"float: right; display: inline; margin: 0px -3px;\">
				<button style=\"width: 2em;\" id=\"groupCollapser{$gv['gid']}\">-</button>
				<button style=\"width: 2em; display: none;\" id=\"groupExpander{$gv['gid']}\">+</button>
			</div>";

		$templatecontent .= "<input type=\"hidden\" id=\"groupExpansionState{$gv['gid']}\" value=\"shown\"/>";
		$templatecontent .= '</div>';

		// Question group questions
		$templatecontent .= '<div id="peciQuestionGroup' . $gv['gid'] . '">';
		$qquery = 'SELECT * FROM ' . db_table_name('questions')
			. " WHERE sid=$templatequeryid AND gid={$gv['gid']} AND language='$languageCode' and parent_qid=0 order by question_order";
		$qresult = db_execute_assoc($qquery);

		if ($qresult->RecordCount() > 0) {
			while($qrows = $qresult->FetchRow()) {
				// Add question list in group
				$templatecontent .= "<script>
						groupQuestions[groupQuestions.length - 1][groupQuestions[groupQuestions.length - 1].length] = {$qrows['qid']};
					</script>";
				
				$ia = array(0 => $qrows['qid'],
					1 => $surveyid.'X'.$qrows['gid'].'X'.$qrows['qid'],
					2 => $qrows['title'],
					3 => $qrows['question'],
					4 => $qrows['type'],
					5 => $qrows['gid'],
					6 => $qrows['mandatory'],
					// 7 => $qrows['other']); // ia[7] is conditionsexist not other
					7 => 'N',
					8 => 'N' ); // ia[8] is usedinconditions

				// Check if the question has subquestions or answer options
				$subquestions = '';
				$answeroptions = '';
				$qtypes = getqtypelist('','array');

				$templatecontent .= "<div class=\"peciQuestion\">
					<div class=\"questionHeader\">
						<div class=\"peciActionButtons\" style=\"float: right; border: 1px solid lightgray; border-radius: 6px; padding: 1px 4px; margin-left: 8px;\">" . 
							$clang->gT("PECI: Import question") .
							"<input type=\"checkbox\" id=\"questionCheck-{$gv['gid']}-{$qrows['qid']}\"/>
						</div>
					</div>
					<h1 class=\"questionTitle\">{$qrows['question']}</h1>
				</div>";
			}
		}

		$templatecontent .= "</div>\n";
		$templatecontent .= "<script type=\"text/javascript\">
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
		$templatecontent .= "</div>\n";
	}
	
	// Disable input fields 
	$templatecontent .= "<script>
		$('.questionAnswers input').attr('disabled', 'disabled');
		$('.questionAnswers textarea').attr('value', '" . $clang->gT('PECI: Text area', 'js') . "');
		$('.questionAnswers textarea').attr('disabled', 'disabled');
	</script>";
}


$surveysummary .= "<div id=\"selectQuestionPeciStepContent\" class=\"peciStepContainer\">
	<div class=\"selectQuestion\"><h3>" . $clang->gT('PECI: Select questions') . "</h3>
	<p>" . $clang->gT('Peci: Dans cette étape de création de questionnaire, vous pouvez choisir les questions que vous souhaitez intégrer dans votre propre questionnaire. Cochez la case à droite de la question à copier. Une fois que vous avez fini votre sélection, vous cliquez sur le bouton xy tout en bas pour aller à l\'étape suivante.') . "</p> </div>"
	. $templatecontent
	. "<div style=\"text-align: right; \">"
	. "<input type=\"button\" onclick=\"confirmAndImport();\" class=\"buttonPeci\" value='"
	. $clang->gT('PECI: Make a copy of selection and continue') . "' />"
	. "</div>"
	. "</div>";
