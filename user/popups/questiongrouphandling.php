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

//Ensure script is not run directly, avoid path disclosure
include_once("login_check.php");

if ($action == "addgroup") {
	$surveyid = $_REQUEST["surveyid"];
	
	$baselang = GetBaseLanguageFromSurveyID($surveyid);
	
	$toSubmit = array("group_name_$baselang", "description_$baselang", 'action', 'sid', 'checksessionbypost');
	$onSubmit = "if (newquestiongroup.group_name_$baselang.value.length > 0) { javascript: parent.submitAsParent({";
	foreach ($toSubmit as $toSubmitKey) {
		$onSubmit .= $toSubmitKey . ': newquestiongroup.' . $toSubmitKey . '.value, ';
	}
	$onSubmit .= '}); } else { alert("You have to enter a group title!"); }';
	
	$newgroupoutput = "<div class='header ui-widget-header'>".$clang->gT("Add question group")."</div>\n";
	$newgroupoutput .= "<div id='tabs'>\n";
 	$newgroupoutput .= "<form class='form30' id='newquestiongroup' onsubmit='return false;' name='newquestiongroup'>";
	
	$newgroupoutput .= '<div id="' . $baselang . '">';
	$newgroupoutput .= "<ul>"
		. "<li>"
		. "<label for='group_name_$baselang'>".$clang->gT("Title").":</label>\n"
		. "<input type='text' size='80' maxlength='100' name='group_name_$baselang' id='group_name_$baselang' /><font color='red' face='verdana' size='1'> ".$clang->gT("Required")."</font></li>\n"
		. "\t<li><label for='description_$baselang'>".$clang->gT("Description:")."</label>\n"
		. "<textarea cols='80' rows='8' id='description_$baselang' name='description_$baselang'></textarea>"
		. "</li>\n"
		. "</ul>"
		. "\t<p><input type='button' onClick='$onSubmit' value='".$clang->gT("Save question group")."' />\n"
		. "</div>\n";

	$newgroupoutput.= "<input type='hidden' name='action' value='insertquestiongroup' />\n"
	. "<input type='hidden' name='checksessionbypost' value='{$_SESSION['checksessionpost']}' />"
	. "<input type='hidden' name='sid' value='$surveyid' />"
	. "</form>\n";

	$newgroupoutput.= "</div>";
}


if ($action == "editgroup") {
	$surveyid = $_REQUEST["surveyid"];
	$gid = $_REQUEST["gid"];
	
	$baselang = GetBaseLanguageFromSurveyID($surveyid);

	$egquery = "SELECT * FROM ".db_table_name('groups')." WHERE sid=$surveyid AND gid=$gid AND (language='$baselang' OR language='0')";
	$egresult = db_execute_assoc($egquery);
	$esrow = $egresult->FetchRow();
	
	$editgroup = "";
	$basesettings = array('group_name' => $esrow['group_name'],'description' => $esrow['description'],'group_order' => $esrow['group_order']);
	
	$esrow = array_map('htmlspecialchars', $esrow);
	$tab_content = "<div class='settingrowpeci'><span class='settingcaptionpeci'><label for='group_name_$baselang'>".$clang->gT("Title").":</label></span>\n"
	. "<span class='settingentrypeci'><input type='text' maxlength='100' size='80' name='group_name_$baselang' id='group_name_$baselang' value=\"{$esrow['group_name']}\" />\n"
	. "\t</span></div>\n"
	. "<div class='settingrowpeci'><span class='settingcaptionpeci'><label for='description_$baselang'>".$clang->gT("Description:")."</label>\n"
	. "</span><span class='settingentrypeci'><textarea cols='70' rows='8' id='description_$baselang' name='description_$baselang'>{$esrow['description']}</textarea>\n"
	. "\t</span></div><div style='clear:both'></div>";
	
	
	$toSubmit = array("group_name_$baselang", "description_$baselang", 'action', 'sid', 'gid', 'checksessionbypost', 'language');
	$onSubmit = 'javascript: parent.submitAsParent({';
	foreach ($toSubmit as $toSubmitKey) {
		$onSubmit .= $toSubmitKey . ': frmeditgroup.' . $toSubmitKey . '.value, ';
	}
	$onSubmit .= '});';
	
	$editgroup .= "<div class='header ui-widget-header'>".$clang->gT("Edit Group")."</div>\n"
	. "<form name='frmeditgroup' id='frmeditgroup' action='' class='form30' method='post'>\n<div id='tabs'>\n";

	$editgroup .= "\n<div id='editgrp'>$tab_content</div>";

	$editgroup .= "</div>\n\t<p><input type='button' onclick='$onSubmit' class='standardbtn' value='".$clang->gT("Update Group")."' />\n"
	. "\t<input type='hidden' name='action' value='updategroup' />\n"
	. "\t<input type='hidden' name='sid' value=\"{$surveyid}\" />\n"
	. "\t<input type='hidden' name='gid' value='{$gid}' />\n"
	. "\t<input type='hidden' name='checksessionbypost' value='{$_SESSION['checksessionpost']}' />"
	. "\t<input type='hidden' name='language' value=\"{$esrow['language']}\" />\n"
	. "\t</p>\n"
	. "</form>\n";

}


?>
