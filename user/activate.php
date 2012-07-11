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

include_once("../admin/activate_functions.php");

$postsid = returnglobal('sid');

$activateoutput = isset($_POST['ok']) ? "{$_POST['ok']}<br />" : "OK NOT SET<br />";

$qtypes=getqtypelist('','array');

if (!isset($_POST['ok']) || !$_POST['ok'])
{
	if (isset($_GET['fixnumbering']) && $_GET['fixnumbering'])
	{
		fixNumbering($_GET['fixnumbering']);
	}

	// Check consistency for groups and questions
	$failedgroupcheck = checkGroup($postsid);
	$failedcheck = checkQuestions($postsid, $surveyid, $qtypes);

	//IF ANY OF THE CHECKS FAILED, PRESENT THIS SCREEN
	if ((isset($failedcheck) && $failedcheck) || (isset($failedgroupcheck) && $failedgroupcheck))
	{
		$activateoutput .= "<br />\n<div class='messagebox ui-corner-all'>\n";
		$activateoutput .= "<div class='header ui-widget-header'>".$clang->gT("Activate Survey")." ($surveyid)</div>\n";
		$activateoutput .= "<div class='warningheader'>\n".$clang->gT("Error")."<br />\n";
		$activateoutput .= $clang->gT("Survey does not pass consistency check")."</div>\n";
		$activateoutput .= "<p>\n";
		$activateoutput .= "<strong>".$clang->gT("The following problems have been found:")."</strong><br />\n";
		$activateoutput .= "<ul>\n";
		if (isset($failedcheck) && $failedcheck)
		{
			foreach ($failedcheck as $fc)
			{
				$activateoutput .= "<li> Question qid-{$fc[0]} (\"<a href='$scriptname?sid=$surveyid&amp;gid=$fc[3]&amp;qid=$fc[0]'>{$fc[1]}</a>\"){$fc[2]}</li>\n";
			}
		}
		if (isset($failedgroupcheck) && $failedgroupcheck)
		{
			foreach ($failedgroupcheck as $fg)
			{
				$activateoutput .= "\t\t\t\t<li> Group gid-{$fg[0]} (\"<a href='$scriptname?sid=$surveyid&amp;gid=$fg[0]'>{$fg[1]}</a>\"){$fg[2]}</li>\n";
			}
		}
		$activateoutput .= "</ul>\n";
		$activateoutput .= $clang->gT("The survey cannot be activated until these problems have been resolved.")."\n";
		$activateoutput .= "</div><br />&nbsp;\n";

		return;
	}

	// On submit
	$toSubmitNames = array("action", "ok", "sid", "checksessionbypost");
	$toSubmitInfo = array("activate", "Y", $surveyid, $_SESSION['checksessionpost']);
	$onSubmit = 'javascript: parent.submitAsParent({';
	for ($i = 0; $i<count($toSubmitNames); $i++) {
		$onSubmit .= $toSubmitNames[$i] . ': \'' . $toSubmitInfo[$i] . '\', ';
	}
	$onSubmit .= '});';
	
	$activateoutput .= "<br />\n<div class='messagebox ui-corner-all'>\n";
	$activateoutput .= "<div class='header ui-widget-header'>".$clang->gT("Activate Survey")." ($surveyid)</div>\n";
	$activateoutput .= "<div class='warningheader'>\n";
	$activateoutput .= $clang->gT("Warning")."<br />\n";
	$activateoutput .= $clang->gT("READ THIS CAREFULLY BEFORE PROCEEDING")."\n";
	$activateoutput .= "\t</div>\n";
	$activateoutput .= $clang->gT("You should only activate a survey when you are absolutely certain that your survey setup is finished and will not need changing.")."<br /><br />\n";
	$activateoutput .= $clang->gT("Once a survey is activated you can no longer:")."<ul><li>".$clang->gT("Add or delete groups")."</li><li>".$clang->gT("Add or delete questions")."</li><li>".$clang->gT("Add or delete subquestions or change their codes")."</li></ul>\n";
	$activateoutput .= $clang->gT("However you can still:")."<ul><li>".$clang->gT("Edit your questions code/title/text and advanced options")."</li><li>".$clang->gT("Edit your group names or descriptions")."</li><li>".$clang->gT("Add, remove or edit answer options")."</li><li>".$clang->gT("Change survey name or description")."</li></ul>\n";
	$activateoutput .= $clang->gT("Once data has been entered into this survey, if you want to add or remove groups or questions, you will need to deactivate this survey, which will move all data that has already been entered into a separate archived table.")."<br /><br />\n";
	$activateoutput .= "\t<input type='submit' value=\"".$clang->gT("Activate Survey")."\" onclick=\"".$onSubmit."\" />\n";
	$activateoutput .= "</div><br />&nbsp;\n";

}
else
{
	$activateoutput = activateSurvey($postsid,$surveyid);
}

