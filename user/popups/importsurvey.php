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

// Login Check dies also if the script is started directly
include_once("login_check.php");

if (!isset($surveyid)) {
	$surveyid = returnglobal('sid');
}

// Allow only if user has enough rights
if(!bHasSurveyPermission($surveyid,'surveysettings','read') && !bHasGlobalPermission('USER_RIGHT_CREATE_SURVEY')) {
	include("access_denied.php");
} else {
	// The import form is implemented using Malsup's jQuery Form Plugin
	$js_admin_includes[] = 'scripts/jquery.form.js';

	// Section header
	$editsurvey .= "<div class='header ui-widget-header'>" . $clang->gT("PECI: Import survey") . " ($surveyid)</div>\n";
	
	$editsurvey .= "<form enctype='multipart/form-data' class='form30' id='importsurvey' name='importsurvey'>\n";

	$editsurvey .= "<input type='hidden' name='checksessionbypost' value='{$_SESSION['checksessionpost']}' />"
		. "<input type='hidden' name='action' value='importsurvey' />";

	$editsurvey .= "<ul>\n"
		. "<li><label for='the_file'>" . $clang->gT("Select survey structure file (*.lss, *.csv):") . "</label>\n"
		. "<input id='the_file' name=\"the_file\" type=\"file\" size=\"50\" /></li>\n"
		. "<li><label for='translinksfields'>" . $clang->gT("Convert resource links and INSERTANS fields?") . "</label>\n"
		. "<input id='translinksfields' name=\"translinksfields\" type=\"checkbox\" checked='checked'/></li></ul>"
		. "<div id='uploadOutput' style='clear: both; padding-right: 32px; text-align: right;'></div>\n";

	$editsurvey .= "<p>"
		. "<input type='submit' id='importSurveyButton' name='method' value='".$clang->gT("Import survey")."'/>"
		. "</p>\n";

	$editsurvey .= "</form>\n";

	$editsurvey .= "<script>
		$(document).ready(function() {
			var options = { 
				url: '$scriptname',
				beforeSubmit:  function() {
					if ($('#the_file').val() == '') {
						alert(\"" . $clang->gT('Please select a file to import!', 'js') . "\");
						return false;
					} else {
						$('#uploadOutput').html('Importing ...');
					}
				},
				success: function(responseText, statusText, xhr, form) {
					if (responseText != 'Error') {
						parent.location.href = 'user.php?sid=' + responseText;
					} else {
						$('#uploadOutput').html('There was an error while importing');
					}
				},
				type: 'post'
			}; 
		 
			$('#importsurvey').ajaxForm(options); 
		});
	</script>";

	$editsurvey .= "</div>\n";
}

?>
