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

$surveysummary .= '<script type="text/javascript">
		disablePeciSteps(["startSurveyPeciStep", "analyzeDataPeciStep", "modifySurveyPeciStep"]);
		setCurrentPeciStep("evaluatePeciStep");
		
		function setSurveyFaxto(value) {
			$.post("user.php", {sid: "' . $surveyid . '", faxto: value, action: "updateFaxTo",
				checksessionbypost: "'. $_SESSION['checksessionpost'] .'"}, function() {
				location.reload();
			});
		}
	</script>';

$surveysummary .= "<div id=\"selectQuestionPeciStepContent\" class=\"peciStepContainer\">"
	. "<input type=\"button\" onclick=\"setSurveyFaxto('');\" value='Next step' />"
	. "</div>";
