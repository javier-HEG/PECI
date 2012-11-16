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
if (isset($_POST['uid'])) {
	$postuserid=sanitize_int($_POST['uid']);
}
if (isset($_POST['ugid'])) {
	$postusergroupid=sanitize_int($_POST['ugid']);
}

// If the user want to change its personal settings
if ($action == "personalsettings") {
	$cssummary = "<div class='formheader'>"
		. "<strong>".$clang->gT("Your personal settings")."</strong>\n"
		. "</div>\n"
		. "<div>\n"
		. "<form action='{$scriptname}' id='personalsettings' method='post'>"
		. "<ul>\n";

	$sSavedLanguage = $connect->GetOne("select lang from " . db_table_name('users') . " where uid={$_SESSION['loginID']}");

    // Language selector
	$cssummary .=  "<li>\n"
    	. "<label for='lang'>".$clang->gT("Interface language").":</label>\n"
    	. "<select id='lang' name='lang'>\n";
    
    foreach (getlanguagedata(true) as $langkey=>$languagekind) {
		$cssummary .= "<option value='$langkey'";
        
        if ($langkey == $sSavedLanguage) {
			$cssummary .= " selected='selected'";
		}
        
		$cssummary .= ">".$languagekind['nativedescription']." - ".$languagekind['description']."</option>\n";
    }
    $cssummary .= "</select>\n</li>\n";

    // Html editor is disabled in this interface
    $cssummary .= "<input type='hidden' name='htmleditormode' value='none' />\n";

    // Date format
    $cssummary .=  "<li>\n"
		. "<label for='dateformat'>".$clang->gT("Date format").":</label>\n"
		. "<select name='dateformat' id='dateformat'>\n";
    
    foreach (getDateFormatData() as $index=>$dateformatdata) {
		$cssummary.= "<option value='{$index}'";
		
		if ($index==$_SESSION['dateformat']) {
			$cssummary.= "selected='selected'";
        }

		$cssummary.= ">".$dateformatdata['dateformat'].'</option>';
	}
	
	$cssummary .= "</select>\n"
		. "</li>\n"
    	. "</ul>\n"
    	. "<p><input type='hidden' name='action' value='savepersonalsettings' /><input class='submit' type='submit' value='".$clang->gT("Save settings")
    	."' /></p></form></div>";
}

// Show selected survey
else if (isset($surveyid) && $surveyid && $action=='') {
	if(bHasSurveyPermission($surveyid,'survey','read'))	{

		// Output starts here...
		$surveysummary = '<script type="text/javascript">
				function setCurrentPeciStep(stepId) {
					$("#surveyPeciStepContainer .surveyPeciStep").removeClass("active");
					$("#" + stepId).addClass("active");
					
					if ($("#" + stepId + "Content").length > 0) {
						// hide other peci step container
						$(".peciStepContainer").hide();
						$("#" + stepId + "Content").show();
					}

					// 2nd and 3rd states have caches that need activation
					if (stepId == "startSurveyPeciStep" || stepId == "modifySurveyPeciStep") {
						$("#" + stepId).ready(function () {	setCacheOnPanels();	});
					}
				}
			</script>';
		
		$surveysummary .= '<div style="background-color: #dcdcdc; width: 100%; margin: -1px 0px; padding-bottom: 6px;">';
		
		// Create the default buttons for the peci steps
		$surveysummary .= '<div id="surveyPeciStepContainer">
			<div id="process">
				<p id="selectQuestionPeciStep" class="surveyPeciStep">' . $clang->gT('Peci: 1. Select questions') . '</p>
				<p class="arrow">&gt;&gt;</p>
				<p id="modifySurveyPeciStep" class="surveyPeciStep">' . $clang->gT('Peci: 2. Modify selected questions') . '</p>
				<p class="arrow">&gt;&gt;</p>
				<p id="startSurveyPeciStep" class="surveyPeciStep">' . $clang->gT('Peci: 3. Conduct survey') . '</p>
			</div>
		</div>
		<br />';
		
		// Load information about the survey
		$sumquery = "SELECT * FROM ".db_table_name('surveys')." inner join ".db_table_name('surveys_languagesettings')." on (surveyls_survey_id=sid and surveyls_language=language) WHERE sid=$surveyid";
		$sumresult = db_select_limit_assoc($sumquery, 1);

		// If surveyid is invalid then die to prevent errors at a later time
		if ($sumresult->RecordCount()==0){
			die('Invalid survey id');
		}

		$thissurvey = $sumresult->FetchRow();
		$thissurvey = array_map('FlattenText', $thissurvey);
		
		// 2nd (modify_survey) and 3rd(start_survey) states are both
		// rendered when in 2nd state. Toggling from one to the other
		// is possible by clicking a button.
		if ($thissurvey['faxto'] != '') {
			include('states/select_questions.php');
		} else {
			// faxto is to be set to empty to continue
			include('states/modify_survey.php');
		}

		$surveysummary .= '</div>';
	}
}

