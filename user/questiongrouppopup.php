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

// include_once("login_check.php");
include('questiongrouphandling.php');

?>

<html>
<head>
<link rel="stylesheet" type="text/css" href="../admin/styles/default/adminstyle.css" />
<link rel="stylesheet" type="text/css" href="styles/default/userstyle.css" />
<style type=" text/css">
	.groupDescription {
		background-color: gray;
	}
</style>
</head>
<body>

<?php 
	if (isset($newgroupoutput)) print $newgroupoutput;
	if (isset($editgroup)) print $editgroup;
?>

</body>
</html>