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
	. $clang->gT("Peci: Activate Survey") . "\n</div><br/>";
$surveysummary .= "<div><p>" . $clang->gT('Peci: Text activate survey') . "</p></div>";
	
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
<img src=\"http://localhost/limesurvey/images/user/silk/help.png\"/><span class=\"classic\">" . $clang->gT('Peci: Tooltip Date') . "</span></a></li>\n";



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
. "<a class=\"tooltip\" href=\"#\">
<img src=\"http://localhost/limesurvey/images/user/silk/help.png\"/><span class=\"classic\">" . $clang->gT('Peci: Tooltip Cookie') . "</span></a></li></ul>\n";

///////////////////////////////////////////
// URL
$surveysummary .= "<table><tr>"
	. "<td align='right' valign='top'><strong>"
	. $clang->gT("Survey URL") . ":</strong></td>\n";
$tmp_url = $GLOBALS['publicurl'] . '/index.php?sid=' . $surveyinfo['sid'];
$surveysummary .= "<td align='left'> <a href='$tmp_url' target='_blank'>$tmp_url</a>";
$surveysummary .= "<a class=\"tooltip\" href=\"#\">
<img src=\"http://localhost/limesurvey/images/user/silk/help.png\"/><span class=\"classic\">" . $clang->gT('Peci: Tooltip URL') . "</span></a></td></tr></table>\n";


///////////////////////////////////////////
// Activate button
$surveysummary .= "<p>\n"
	. "<input type='button' value='". $clang->gT('Peci: Activate') . "' onClick='openPeciPopup(\"activatesurvey\", \"sid=$surveyid\");' />\n";


// Export Survey questions
$surveysummary .= "<form id='exportstructure' name='exportstructure' action='$scriptname' method='post'>\n"
	. "<div class='header ui-widget-header'>"
	. $clang->gT("Peci: Export Survey Structure") . "\n</div><br />\n"
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


// Stop Survey
$surveysummary .= "<div class='header ui-widget-header'>"
	. $clang->gT("Response summary") . "\n</div><br/>";

// Export results/data
$surveysummary .= "<div class='header ui-widget-header'>"
	. $clang->gT("Export results") . "\n</div><br/>";

$surveysummary .= 	"<input type=\"button\" onClick=\"setCurrentPeciStep('modifySurveyPeciStep');\" class=\"buttonPeci\" value='"
	. $clang->gT('PECI: Return to the questionnaire') . "' />";


$surveysummary .= "</div>";