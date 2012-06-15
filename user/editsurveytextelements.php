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

//Login Check dies also if the script is started directly
include_once("login_check.php");

$surveyid = $_REQUEST["surveyid"];

if(bHasSurveyPermission($surveyid,'surveylocale','read')) {
	$baselang = GetBaseLanguageFromSurveyID($surveyid);

	$editsurvey ="<div class='header ui-widget-header'>".$clang->gT("Edit survey text elements")."</div>\n";
	$editsurvey .= "<form id='addnewsurvey' class='form30' name='addnewsurvey' onSubmit='return false;' >\n";
	$i = 0;

	$esquery = "SELECT * FROM ".db_table_name("surveys_languagesettings")." WHERE surveyls_survey_id=$surveyid and surveyls_language='$baselang'";
	$esresult = db_execute_assoc($esquery); //Checked
	$esrow = $esresult->FetchRow();
	
	$esrow = array_map('htmlspecialchars', $esrow);
	$editsurvey .= "<ul>\n"
	. "<li><label for=''>".$clang->gT("Survey title").":</label>\n"
	. "<input type='text' size='80' name='short_title_".$esrow['surveyls_language']."' value=\"{$esrow['surveyls_title']}\" /></li>\n"
	. "<li><label for=''>".$clang->gT("Description:")."</label>\n"
	. "<textarea cols='80' rows='15' name='description_".$esrow['surveyls_language']."'>{$esrow['surveyls_description']}</textarea>\n"
	. "</li>\n"
	. "<li><label for=''>".$clang->gT("Welcome message:")."</label>\n"
	. "<textarea cols='80' rows='15' name='welcome_".$esrow['surveyls_language']."'>{$esrow['surveyls_welcometext']}</textarea>\n"
	. "</li>\n"
	. "<li><label for=''>".$clang->gT("End message:")."</label>\n"
	. "<textarea cols='80' rows='15' name='endtext_".$esrow['surveyls_language']."'>{$esrow['surveyls_endtext']}</textarea>\n"
	. "</li></ul>\n";
	
	// Hidden fields
	$editsurvey .= "<input type='hidden' name='url_".$esrow['surveyls_language']."' value=\"{$esrow['surveyls_url']}\" />\n"
	. "<input type='hidden' name='urldescrip_".$esrow['surveyls_language']."' value=\"{$esrow['surveyls_urldescription']}\" />\n"
	. "<input type='hidden' name='dateformat_".$esrow['surveyls_language']."' value=\"{$esrow['surveyls_dateformat']}\" />\n"
	. "<input type='hidden' name='numberformat_".$esrow['surveyls_language']."' value=\"{$esrow['surveyls_numberformat']}\" />\n";
	
	$toSubmit = array('action', 'sid', 'short_title_' . $esrow['surveyls_language'], 'description_' . $esrow['surveyls_language'],
		'welcome_' . $esrow['surveyls_language'], 'endtext_' . $esrow['surveyls_language'], 'url_' . $esrow['surveyls_language'],
		'urldescrip_' . $esrow['surveyls_language'], 'dateformat_' . $esrow['surveyls_language'], 'numberformat_' . $esrow['surveyls_language'],
		'checksessionbypost');
	$onSubmit = "if (addnewsurvey.short_title_$baselang.value.length > 0) { javascript: parent.submitAsParent({";
	foreach ($toSubmit as $toSubmitKey) {
		$onSubmit .= $toSubmitKey . ': addnewsurvey.' . $toSubmitKey . '.value, ';
	}
	$onSubmit .= '}); } else { alert("You have to enter a question title!"); }';
	
	if(bHasSurveyPermission($surveyid,'surveylocale','update')) {
		$editsurvey .= "<p><input type='button' onClick='$onSubmit' value='".$clang->gT("Save")."' />\n"
		. "<input type='hidden' name='action' value='updatesurveylocalesettings' />\n"
		. "<input type='hidden' name='checksessionbypost' value='{$_SESSION['checksessionpost']}' />\n"
		. "<input type='hidden' name='sid' value=\"{$surveyid}\" />\n"
		. "<input type='hidden' name='language' value=\"{$esrow['surveyls_language']}\" />\n"
		. "</p>\n"
		. "</form>\n";
	}

}
else
{
	include("access_denied.php");
}

?>
