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

if (!isset($action)) {
	$action=returnglobal('action');
}

if (get_magic_quotes_gpc()) {
	$_POST  = array_map('recursive_stripslashes', $_POST);
}

if(isset($surveyid)) {
    if ($action == "updateFaxTo" && bHasSurveyPermission($surveyid, 'surveycontent','create')) {
     	$ufquery = "UPDATE " . db_table_name('surveys') . " SET faxto='" . db_quote($_POST['faxto']) . "' WHERE sid=" . db_quote($surveyid);
     	$connect->Execute($ufquery);
    }
}
