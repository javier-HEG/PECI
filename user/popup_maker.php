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

require_once(dirname(__FILE__).'/../config-defaults.php');
require_once(dirname(__FILE__).'/../common.php');

require_once('user_functions.php');

// include_once("login_check.php");
$action = $_REQUEST["action"];

switch ($action) {
	case 'addgroup':
	case 'editgroup':
		include('popups/questiongrouphandling.php');
		break;
	case 'newsurvey':
		include('popups/editsurveysettings.php');
		break;
	case 'editsurveylocalesettings':
		include('popups/editsurveytextelements.php');
		break;
	case 'addquestion':
	case 'editquestion':
		include('popups/questionhandling.php');
		break;
	case 'editsubquestions':
		include('popups/editsubquestions.php');
		break;
	case 'editansweroptions':
		include('popups/editansweroptions.php');
		break;
	case 'activatesurvey':
		$surveyid = $_GET['sid'];
		include('popups/activate.php');
		break;
	case 'conditions':
		include('popups/editconditions.php');
		break;
}

$scriptsToLoad = '';
$js_admin_includes = array_unique($js_admin_includes);
foreach ($js_admin_includes as $jsinclude) {
	$scriptsToLoad .= "<script type=\"text/javascript\" src=\"".$jsinclude."\"></script>\n";
}

?>

<html>
<head>
<script type="text/javascript" src="<?php echo $rooturl; ?>/admin/scripts/tabpane/js/tabpane.js"></script>
<script type="text/javascript" src="<?php echo $rooturl; ?>/scripts/jquery/jquery.js"></script>
<script type="text/javascript" src="<?php echo $rooturl; ?>/scripts/jquery/jquery.dd.js"></script>
<!-- <script type="text/javascript" src="<?php echo $rooturl; ?>/user/scripts/jquery.cookie.js"></script> -->
<!-- <script type="text/javascript" src="<?php echo $rooturl; ?>/user/scripts/jquery.simplemodal.js"></script> -->
<script type="text/javascript" src="<?php echo $rooturl; ?>/scripts/jquery/jquery-ui.js"></script>
<script type="text/javascript" src="<?php echo $rooturl; ?>/scripts/jquery/jquery.qtip.js"></script>
<script type="text/javascript" src="<?php echo $rooturl; ?>/scripts/jquery/jquery.notify.js"></script>
<script type="text/javascript" src="<?php echo $rooturl; ?>/admin/scripts/admin_core.js"></script>
<!-- <script type="text/javascript" src="<?php echo $rooturl; ?>/user/scripts/tabs.js"></script> -->
<script type="text/javascript" src="<?php echo $rooturl; ?>/user/scripts/create_edit.js"></script>
<?php echo $scriptsToLoad; ?>
<title>Usefulness.ch</title>
<link rel="stylesheet" type="text/css" media="all" href="<?php echo $rooturl; ?>/admin/styles/default/tab.webfx.css " />
<link rel="stylesheet" type="text/css" media="all" href="<?php echo $rooturl; ?>/scripts/jquery/css/start/jquery-ui.css" />
<!-- <link rel="stylesheet" type="text/css" href="<?php echo $rooturl; ?>/admin/styles/default/printablestyle.css" media="print" /> -->
<link rel="stylesheet" type="text/css" href="<?php echo $rooturl; ?>/admin/styles/default/adminstyle.css" />
<link rel="stylesheet" type="text/css" href="<?php echo $rooturl; ?>/user/styles/default/userstyle.css" />
<link rel="shortcut icon" href="<?php echo $rooturl; ?>/admin/favicon.ico" type="image/x-icon" />
<link rel="icon" href="<?php echo $rooturl; ?>/admin/favicon.ico" type="image/x-icon" />
<link rel="stylesheet" type="text/css" media="all" href="../scripts/jquery/dd.css" />
<style type=" text/css">
	.groupDescription {
		background-color: gray;
	}
</style>
</head>
<body>

<?php 
	if (isset($newgroupoutput)) {
		print $newgroupoutput;
	}
	if (isset($editgroup)) {
		print $editgroup;
	}
	if (isset($editquestion)) {
		print $editquestion;
	}
	if (isset($editsurvey)) {
		print $editsurvey;
	}
	if (isset($vasummary)) {
		print $vasummary;
	}
	if (isset($activateoutput)) {
		print $activateoutput;
	}
	if (isset($conditionsoutput_main_content)) {
// 		print $conditionsoutput_header;
// 		print $conditionsoutput_menubar;
		print $conditionsoutput_action_error;
		print $conditionsoutput_main_content;
	}
?>

</body>
</html>