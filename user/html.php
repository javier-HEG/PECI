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
		$adminmenu .= $clang->gT("Logged in as:") . " <a href=\"#\" onclick=\"window.open('{$scriptname}?action=personalsettings', '_top')\""
			. " title=\"Edit your personal preferences\">" . $_SESSION['full_name'] . "</a>";
    	$adminmenu .= " | <a href=\"#\" onclick=\"window.open('../admin/admin.php?action=logout', '_top')\""
    		. " title=\"".$clang->gTview("Logout")."\" >" . $clang->gT("Logout") . "</a>";
	}
	
    $adminmenu .= "</div>";
    
    // Move the menu bar to its definitive position
    $adminmenu .= "<script type=\"text/javascript\">$('#mainTitleMenu').append($('.menubar'));</script>";
    
    return $adminmenu;
}

/**
* Returns html text for the "first steps help" which should have
* originally be returned by "showUserMenu()" if we had kept the
* code in "admin/html.php:showadminmenu()".
*/
function showUserFirstStepsHelp() {
	global $surveyid, $imageurl, $clang, $action;
		
	$firstStepsHelp = "";
	
	//  $adminmenu .= "<p style='margin:0;font-size:1px;line-height:1px;height:1px;'>&nbsp;</p>"; //CSS Firefox 2 transition fix
	if (!isset($action) && !isset($surveyid) && count(getsurveylist(true))==0)
	{
		$firstStepsHelp = '<div style="width:500px;margin:0 auto;">'
		.'<h2>'.sprintf($clang->gT("Welcome to %s!"),'LimeSurvey').'</h2>'
		.'<p>'.$clang->gT("Some piece-of-cake steps to create your very own first survey:").'<br/>'
		.'<ol>'
		.'<li>'.sprintf($clang->gT('Create a new survey clicking on the %s icon in the upper right.'),"<img src='$imageurl/add_20.png' name='ShowHelp' title='' alt='". $clang->gT("Add survey")."'/>").'</li>'
		.'<li>'.$clang->gT('Create a new question group inside your survey.').'</li>'
		.'<li>'.$clang->gT('Create one or more questions inside the new question group.').'</li>'
		.'<li>'.sprintf($clang->gT('Done. Test your survey using the %s icon.'),"<img src='$imageurl/do_20.png' name='ShowHelp' title='' alt='". $clang->gT("Test survey")."'/>").'</li>'
		.'</ol></p><br />&nbsp;</div>';
	}
	
	return $firstStepsHelp;
}