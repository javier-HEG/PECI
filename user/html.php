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
    
	$cssummary .= "<option value='auto'";
    if ($sSavedLanguage == 'auto') {
		$cssummary .= " selected='selected'";
	}
    $cssummary .= ">".$clang->gT("(Autodetect)")."</option>\n";
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
					$("#surveyPeciStepContainer .surveyPeciStep").removeClass("currentPeciStep");
					$("#" + stepId).addClass("currentPeciStep");
					
					if ($("#" + stepId + "Content").length > 0) {
						// hide other peci step container
						$(".peciStepContainer").hide();
						$("#" + stepId + "Content").show();
					}
				}
				
				function disablePeciSteps(stepIdsArray) {
					for (var i = 0; i < stepIdsArray.length; i++) {
					    $("#" + stepIdsArray[i]).addClass("disabledPeciStep");
					    $("#" + stepIdsArray[i]).attr("onclick", "");
					}
				}
			</script>';
		
		$surveysummary .= '<div style="background-color: lightgray; width: 100%; margin: -1px 0px; padding-bottom: 6px;">';
		
		// Create the default buttons for the peci steps
		$surveysummary .= '<div id="surveyPeciStepContainer">
				<div id="evaluatePeciStep" onClick="setCurrentPeciStep(\'evaluatePeciStep\');" class="surveyPeciStep">About evaluating usefulness</div>
				<div class="surveyPeciStepArrow" style="background-image: none;">&nbsp;</div>
				<div class="surveyPeciStepArrow" style="background-image: none;">&nbsp;</div>
				<div id="selectQuestionPeciStep" onClick="setCurrentPeciStep(\'selectQuestionPeciStep\');" class="surveyPeciStep">Select questions</div>
				<div class="surveyPeciStepArrow">&nbsp;</div>
				<div id="modifySurveyPeciStep" onClick="setCurrentPeciStep(\'modifySurveyPeciStep\');" class="surveyPeciStep">Modify the questionnaire</div>
				<div class="surveyPeciStepArrow">&nbsp;</div>
				<div id="startSurveyPeciStep" onClick="setCurrentPeciStep(\'startSurveyPeciStep\');" class="surveyPeciStep">Start survey</div>
				<div class="surveyPeciStepArrow">&nbsp;</div>
				<div id="analyzeDataPeciStep" onClick="setCurrentPeciStep(\'analyzeDataPeciStep\');" class="surveyPeciStep">Analyze the data</div>
			</div>';
		
		$surveysummary .= "<div id=\"evaluatePeciStepContent\" class=\"peciStepContainer\" >
				<p>About using surveys to evaluate usability</p>
			</div>";

		// Load information about the survey
		$sumquery = "SELECT * FROM ".db_table_name('surveys')." inner join ".db_table_name('surveys_languagesettings')." on (surveyls_survey_id=sid and surveyls_language=language) WHERE sid=$surveyid";
		$sumresult = db_select_limit_assoc($sumquery, 1);

		// If surveyid is invalid then die to prevent errors at a later time
		if ($sumresult->RecordCount()==0){
			die('Invalid survey id');
		}

		$thissurvey = $sumresult->FetchRow();
		$thissurvey = array_map('FlattenText', $thissurvey);
				
		// It is already possible to detect if the PECI state is the last one
		// - If the survey is active and the end-date is in the past, the "ANALYZE THE SURVEY"
		if ($thissurvey['active'] == 'Y') { // && time() > strtotime($thissurvey['expires']) ) {
			include('states/analyze_survey.php');
		} else {
			// At first it seemed enought to check if the survey had at least one
			// question group to chose between the "select questions" and the "modify
			// survey" steps. However, I finally decided to use the "faxto" property
			// to tell if the survey has already gone through import.
			
			if ($thissurvey['faxto'] != '') {
				include('states/select_questions.php');
			} else {
				// faxto is to be set to empty to continue
				include('states/modify_survey.php');
			}
		}

		$surveysummary .= '</div>';
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
