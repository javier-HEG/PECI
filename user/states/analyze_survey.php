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

$surveysummary .= "<div id=\"analyzeDataPeciStepContent\" class=\"peciStepContainer\">";

$surveysummary .= '<script type="text/javascript">
		disablePeciSteps(["selectQuestionPeciStep", "modifySurveyPeciStep", "startSurveyPeciStep"]);
		setCurrentPeciStep("analyzeDataPeciStep");
		
		function stopSurvey() {
			$.post("user.php", {sid: "' . $surveyid . '", action: "stopSurvey",
				checksessionbypost: "'. $_SESSION['checksessionpost'] .'"}, function() {
				location.reload();
			});
		}
	</script>';

include("export_results.php");

$surveysummary .= $exportoutput;
$exportoutput = '';

$hideExportOptions = false;

// The cache should be shown if there is no expiracy date, or
// if the expiracy date is in the future
if ($thissurvey['expires'] == '') {
	$hideExportOptions = true;
} else {
	$surveyExpiracy = DateTime::createFromFormat('Y-m-d H:i:s', $thissurvey['expires']);
	if ($surveyExpiracy->getTimestamp() > time()) {
		$hideExportOptions = true;
	}
}

if ($hideExportOptions) {
	$surveysummary .= '<script type="text/javascript">
		// show message
		$("#surveystop").show();
		// cache export
		$(document).ready(function() {
			var eltHeight = $("#exportresultswrapper").height();
			var eltWidth = $("#exportresultswrapper").width();
			var eltOffset = $("#exportresultswrapper").offset();
			
			$("#wrap2columnscache").height(eltHeight + "px");
			$("#wrap2columnscache").width(eltWidth + "px");
			$("#wrap2columnscache").offset({ top: eltOffset.top, left: eltOffset.left });
		});
	</script>';
}

$surveysummary .= "</div>";
