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

$surveysummary .= "<div id=\"startSurveyPeciStepContent\" class=\"peciStepContainer\">";

// Start Survey
$surveysummary .= "<div class='header ui-widget-header'>"
	. $clang->gT("Peci: Activate Survey") . "\n</div>";
$surveysummary .= "<div id='surveyActivateWrapper' class='wrap2columns'>\n";
$surveysummary .= $clang->gT('Peci: Text activate survey') . "<br />";
	
///////////////////////////////////////////
// Load query info
// - First way
$esquery = "SELECT * FROM {$dbprefix}surveys WHERE sid=$surveyid";
$esresult = db_execute_assoc($esquery); //Checked
if ($esrow = $esresult->FetchRow()) {
	$esrow = array_map('htmlspecialchars', $esrow);
}
// - Second way
$sumquery1 = "SELECT * FROM ".db_table_name('surveys')." inner join ".db_table_name('surveys_languagesettings')." on (surveyls_survey_id=sid and surveyls_language=language) WHERE sid=$surveyid"; //Getting data for this survey
$sumresult1 = db_select_limit_assoc($sumquery1, 1) ; //Checked
if ($sumresult1->RecordCount()==0){
	die('Invalid survey id');
} //  if surveyid is invalid then die to prevent errors at a later time
$surveyinfo = $sumresult1->FetchRow();
$surveyinfo = array_map('FlattenText', $surveyinfo);


	
///////////////////////////////////////////
// Dates
$dateformatdetails=getDateFormatData($_SESSION['dateformat']);
// - Start date set to now
$startdate = date($dateformatdetails['phpdate'] . ' H:i', time());
$surveysummary .= "<input type='hidden' id='startdate' name='startdate' value=\"{$startdate}\" />\n";
// - Expiracy date to be set or left empty
$expires='';
if (trim($esrow['expires']) != '') {
	$datetimeobj = new Date_Time_Converter($esrow['expires'] , "Y-m-d H:i:s");
	$expires=$datetimeobj->convert($dateformatdetails['phpdate'].' H:i');
}

$surveysummary .= "<input type='hidden' name='sid' value='$surveyid' />\n";
$surveysummary .="<ul><li><label for='expires'>".$clang->gT("Expiry date/time:")."</label>\n"
. "<input type='text' class='popupdatetime' id='expires' size='20' name='expires' value=\"{$expires}\" /><a class=\"tooltip\" href=\"#\">
<img src=\"$imageurl/user/silk/help.png\"/><span class=\"classic\">" . $clang->gT('Peci: Tooltip Date') . "</span></a></li>\n";


///////////////////////////////////////////
// Cookie
$surveysummary .= "<li><label for=''>".$clang->gT("Set cookie to prevent repeated participation?")."</label>\n"
	. "<select name='usecookie'>\n"
	. "<option value='Y'";

if ($esrow['usecookie'] == "Y") {
	$surveysummary .= " selected='selected'";
}

$surveysummary .= ">".$clang->gT("Yes")."</option>\n"
	. "<option value='N'";

if ($esrow['usecookie'] != "Y") {
	$surveysummary .= " selected='selected'";
}

$surveysummary .= ">".$clang->gT("No")."</option>\n"
	. "</select>\n"
	. "<a class=\"tooltip\" href=\"#\">"
	. "<img src=\"$imageurl/user/silk/help.png\"/><span class=\"classic\">"
	. $clang->gT('Peci: Tooltip Cookie') . "</span></a></li></ul>\n";

///////////////////////////////////////////
// Activate button
$surveysummary .= "<p>\n"
	. "<input type='button' value='". $clang->gT('Peci: Activate') . "' onClick='openPeciPopup(\"activatesurvey\", \"sid=$surveyid\");' />\n"
	. "</div>";

// Export Survey questions
$surveysummary .= "<form id='exportstructure' name='exportstructure' action='$scriptname' method='post'>\n"
	. "<div class='header ui-widget-header'>"
	. $clang->gT("Peci: Export Survey Structure") . "\n</div>\n"
	. "<ul>\n"
	. "<li><input type='radio' class='radiobtn' name='action' value='exportstructurexml' checked='checked' id='surveyxml'"
	. "<label for='surveycsv'>"
	. $clang->gT("LimeSurvey XML survey file (*.lss)") . "</label></li>\n";

$surveysummary .= "<li><input type='radio' class='radiobtn' name='action' value='exportstructurequexml'  id='queXML'"
	. "<label for='queXML'>"
	. str_replace('queXML','<a href="http://quexml.sourceforge.net/" target="_blank">queXML</a>',$clang->gT("queXML Survey XML Format (*.xml)"))." "
	. "</label></li>\n";

$surveysummary .= "</ul>\n";

$surveysummary .= "<p>\n"
	. "<input type='submit' value='"
	. $clang->gT("Export To File") . "' />\n"
	. "<input type='hidden' name='sid' value='$surveyid' />\n";

$surveysummary .= "</form>\n";

///////////////////////////////////////////
// Results summary
$surveysummary .= "<div class='header ui-widget-header'>"
	. $clang->gT("Response summary") . "\n</div>"
	. "<div id='resultsSummaryWrapper' class='wrap2columns'>\n";

// Get responses count
$num_total_answers = 0;
$num_completed_answers = 0;

if ($thissurvey['active'] == 'Y') {
    $surveytable = db_table_name("survey_" . $thissurvey['sid']);
    $gnquery = "SELECT count(id) FROM $surveytable";
    $gnquery2 = "SELECT count(id) FROM $surveytable WHERE submitdate IS NOT NULL";
    $gnresult = db_execute_num($gnquery);
    $gnresult2 = db_execute_num($gnquery2);
    while ($gnrow = $gnresult->FetchRow()) {
    	$num_total_answers = $gnrow[0];
    }
    while ($gnrow2 = $gnresult2->FetchRow()) {
    	$num_completed_answers = $gnrow2[0];
	}

	// Survey URL
    $tmp_url = $GLOBALS['publicurl'] . '/index.php?sid=' . $thissurvey['sid'];
    $surveyurl = "<a href='$tmp_url' target='_blank'>$tmp_url</a>";
	$surveyUrlText = "<b>" . $clang->gT("This survey is currently active.") . "</b><br />"
		. $clang->gT("Survey URL") . ": $surveyurl"
		. "<a class=\"tooltip\" href=\"#\">"
		. "<img src=\"$imageurl/user/silk/help.png\"/><span class=\"classic\">"
		. $clang->gT('Peci: Tooltip URL') . "</span></a><br />\n";

	// Stop survey
    $stopSurvey = '<script type="text/javascript">
		function stopSurvey() {
			$.post("user.php", {sid: "' . $thissurvey['sid'] . '", action: "stopSurvey",
				checksessionbypost: "'. $_SESSION['checksessionpost'] .'"}, function() {
				location.reload();
			});
		}
	</script>';

    if ($thissurvey['expires'] != '') {
    	$datetimeobj = new Date_Time_Converter($thissurvey['expires'] , "Y-m-d H:i:s");
    	$dateformatdetails = getDateFormatData($_SESSION['dateformat']);
    	$expires = $datetimeobj->convert($dateformatdetails['phpdate'] . ' H:i');
    	
    	$stopSurvey .= '<p>' . sprintf($clang->gT("PECI: Active until %s"), $expires) . '</p>';
    } else {
    	$stopSurvey .= '<p>' . $clang->gT("PECI: Active without expiracy date") . '</p>';
    }
    
    $stopSurvey .= '<p><input type="button" onclick="if (confirm(\''
    	. $clang->gT('PECI: Stop survey warning', 'js')
    	. '\')) { stopSurvey(); }" value="'
    	. $clang->gT('Deactivate Survey')
    	. '" /></p>';
}

$surveysummary .= (isset($surveyUrlText) ? $surveyUrlText : '<b>' . $clang->gT("PECI: This survey is currently inactive") . '</b>')
    . "<p><table class='statisticssummary'>\n"
    . "<tfoot><tr><th>".$clang->gT("Total responses:")."</th><td>".$num_total_answers."</td></tr></tfoot>"
    . "\t<tbody>"
    . "<tr><th>".$clang->gT("Full responses:")."</th><td>".$num_completed_answers."</td></tr></tbody>"
    . "</table></p>"
    . (isset($stopSurvey) ? $stopSurvey : '');

// Close wrapper and add cache div
$surveysummary .= "</div>"
	. "<div id='responseSummaryCache' class='panelCache'></div>";

/////////////////////////////////////////
// Export results/data
include("export_results.php");

$surveysummary .= $exportoutput;
$exportoutput = '';

/////////////////////////////////////////
// Return to survey editing button
$surveysummary .= 	"<div style=\"text-align: right; \">"
	. "<input id=\"toModifySurveyPeciStepButton\" type=\"button\" onClick=\"setCurrentPeciStep('modifySurveyPeciStep');\" class=\"buttonPeci\" value='"
	. $clang->gT('PECI: Return to the questionnaire') . "' />"
	. "</div>"
    . "<div id='activateSurveyCache' class='panelCache'></div>"
	. "</div>";
