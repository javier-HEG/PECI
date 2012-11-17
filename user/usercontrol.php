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

// Security Checked: POST, GET, SESSION, REQUEST, returnglobal, DB

if (isset($_REQUEST['homedir'])) {die('You cannot start this script directly');}
include_once("login_check.php");  //Login Check dies also if the script is started directly
require_once($homedir."/classes/core/sha256.php");

// Originally this variable is that set to "admin.php" in config.php,
// we however need to send calls to our script.
$scriptname = "user.php";

if (isset($_POST['user'])) {$postuser=sanitize_user($_POST['user']);}
if (isset($_POST['email'])) {$postemail=sanitize_email($_POST['email']);}
if (isset($_POST['loginlang'])) {$postloginlang=sanitize_languagecode($_POST['loginlang']);}
if (isset($_POST['new_user'])) {$postnew_user=sanitize_user($_POST['new_user']);}
if (isset($_POST['new_email'])) {$postnew_email=sanitize_email($_POST['new_email']);}
if (isset($_POST['new_full_name'])) {$postnew_full_name=sanitize_userfullname($_POST['new_full_name']);}
if (isset($_POST['uid'])) {$postuserid=sanitize_int($_POST['uid']);}
if (isset($_POST['full_name'])) {$postfull_name=sanitize_userfullname($_POST['full_name']);}
// When user is added online
if (isset($_POST['new_password'])) {$postnew_password=sanitize_userfullname($_POST['new_password']);}
if (isset($_POST['new_password_check'])) {$postnew_password_check=sanitize_userfullname($_POST['new_password_check']);}

if (!isset($_SESSION['loginID'])) {
	// Normal login
	if($action == "login" && $useWebserverAuth === false) {
		$loginsummary = '';

		if (isset($postuser) && isset($_POST['password']))
		{
			include("../admin/database.php");

			$sIp=   $_SERVER['REMOTE_ADDR'];
			$query = "SELECT * FROM ".db_table_name('failed_login_attempts'). " WHERE ip='$sIp';";
			$ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
			$result = $connect->query($query);
			$bLoginAttempted = false;
			$bCannotLogin = false;

			$intNthAttempt = 0;
			if ($result!==false && $result->RecordCount() >= 1)
			{
				$bLoginAttempted = true;
				$field = $result->FetchRow();
				$intNthAttempt = $field['number_attempts'];
				if ($intNthAttempt>=$maxLoginAttempt){
					$bCannotLogin = true;
				}

				$iLastAttempt = strtotime($field['last_attempt']);

				if (time() > $iLastAttempt + $timeOutTime){
					$bCannotLogin = false;
					$query = "DELETE FROM ".db_table_name('failed_login_attempts'). " WHERE ip='$sIp';";
					$result = $connect->query($query) or safe_die ($query."<br />".$connect->ErrorMsg());

				}

			}
			if(!$bCannotLogin){
				$query = "SELECT * FROM ".db_table_name('users')." WHERE users_name=".$connect->qstr($postuser);

				$result = $connect->SelectLimit($query, 1) or safe_die ($query."<br />".$connect->ErrorMsg());
				if ($result->RecordCount() < 1)
				{
					$query = fGetLoginAttemptUpdateQry($bLoginAttempted,$sIp);

					$result = $connect->Execute($query) or safe_die ($query."<br />".$connect->ErrorMsg());;
					if ($result)
					{
						// wrong or unknown username
						$loginsummary .= "<p>".$clang->gT("Incorrect username and/or password!")."<br />";
						if ($intNthAttempt+1>=$maxLoginAttempt)
						$loginsummary .= sprintf($clang->gT("You have exceeded you maximum login attempts. Please wait %d minutes before trying again"),($timeOutTime/60))."<br />";
						$loginsummary .= "<br /><a href='$scriptname'>".$clang->gT("Continue")."</a><br />&nbsp;\n";
					}


				}
				else
				{
					$fields = $result->FetchRow();
					if (SHA256::hashing($_POST['password']) == $fields['password'])
					{
						// Anmeldung ERFOLGREICH
						if (strtolower($_POST['password'])=='password')
						{
							$_SESSION['pw_notify']=true;
							$_SESSION['flashmessage']=$clang->gT("Warning: You are still using the default password ('password'). Please change your password and re-login again.");
						}
						else
						{
							$_SESSION['pw_notify']=false;
						} // Check if the user has changed his default password

						if ($sessionhandler=='db')
						{
							adodb_session_regenerate_id();
						}
						else
						{
							session_regenerate_id();

						}
						$_SESSION['loginID'] = intval($fields['uid']);
						$_SESSION['user'] = $fields['users_name'];
						$_SESSION['full_name'] = $fields['full_name'];
						$_SESSION['htmleditormode'] = $fields['htmleditormode'];
						$_SESSION['dateformat'] = $fields['dateformat'];
						// Compute a checksession random number to test POSTs
						$_SESSION['checksessionpost'] = sRandomChars(10);
						if (isset($postloginlang) && $postloginlang!='default')
						{
							$_SESSION['adminlang'] = $postloginlang;
							$clang = new limesurvey_lang($postloginlang);
							$uquery = "UPDATE {$dbprefix}users "
							. "SET lang='{$postloginlang}' "
							. "WHERE uid={$_SESSION['loginID']}";
							$uresult = $connect->Execute($uquery);  // Checked
						}
						else
						{

							if ( $fields['lang']=='auto' && isset( $_SERVER["HTTP_ACCEPT_LANGUAGE"] ) )
							{
								$browlang=strtolower( $_SERVER["HTTP_ACCEPT_LANGUAGE"] );
								$browlang=str_replace(' ', '', $browlang);
								$browlang=explode( ",", $browlang);
								$browlang=$browlang[0];
								$browlang=explode( ";", $browlang);
								$browlang=$browlang[0];
								$check=0;
								$value=26;
								if ($browlang!="zh-hk" && $browlang!="zh-tw" && $browlang!="es-mx" && $browlang!="pt-br")
								{
									$browlang=explode( "-",$browlang);
									$browlang=$browlang[0];
								}
								$_SESSION['adminlang']=$browlang;
							}
							else
							{
								$_SESSION['adminlang'] = $fields['lang'];
							}
							$clang = new limesurvey_lang($_SESSION['adminlang']);
						}
						$login = true;

						$loginTryNotification = sprintf($clang->gT("Welcome %s!"),$_SESSION['full_name']);
						
						if (isset($_POST['refererargs']) && $_POST['refererargs'] &&
						strpos($_POST['refererargs'], "action=logout") === FALSE)
						{
							require_once("../classes/inputfilter/class.inputfilter_clean.php");
							$myFilter = new InputFilter('','',1,1,1);
							// Prevent XSS attacks
							$sRefererArg=$myFilter->process($_POST['refererargs']);
							$_SESSION['metaHeader']="<meta http-equiv=\"refresh\""
							. " content=\"1;URL={$scriptname}?".$sRefererArg."\" />";
							$loginsummary .= "<p><font size='1'><i>".$clang->gT("Reloading screen. Please wait.")."</i></font>\n";
						}
						
						GetSessionUserRights($_SESSION['loginID']);
					}
					else
					{
						$query = fGetLoginAttemptUpdateQry($bLoginAttempted,$sIp);

						$result = $connect->Execute($query) or safe_die ($query."<br />".$connect->ErrorMsg());;
						if ($result)
						{
							// wrong or unknown username
							$loginsummary .= "<p>".$clang->gT("Incorrect username and/or password!")."<br />";
							if ($intNthAttempt+1>=$maxLoginAttempt)
							$loginsummary .= sprintf($clang->gT("You have exceeded you maximum login attempts. Please wait %d minutes before trying again"),($timeOutTime/60))."<br />";
							$loginsummary .= "<br /><a href='$scriptname'>".$clang->gT("Continue")."</a><br />&nbsp;\n";
						}
					}
				}

			}
			else{
				$loginsummary .= "<p>".sprintf($clang->gT("You have exceeded you maximum login attempts. Please wait %d minutes before trying again"),($timeOutTime/60))."<br />";
				$loginsummary .= "<br /><a href='$scriptname'>".$clang->gT("Continue")."</a><br />&nbsp;\n";
			}
		}
	}

	// If a user filled the create user online
	else if ($action == "addonlineuser") {
		$userCreationMessage = "";
		$failedToAddUser = "<p><strong>".$clang->gT("Failed to add user")."</strong></p>";

		$new_user = FlattenText($postnew_user, true);
		$new_email = FlattenText($postnew_email, true);
		$new_full_name = FlattenText($postnew_full_name, true);
		$new_loginlang = FlattenText($postloginlang, true);

		$valid_email = true;
		if (!validate_email($new_email)) {
			$valid_email = false;
			$userCreationMessage .= $failedToAddUser;
			$userCreationMessage .= $clang->gT("The email address is not valid.")."<br />";
		}

		$valid_user = true;
		if (empty($new_user)) {
			if ($valid_email)
				$userCreationMessage .= $failedToAddUser;
			$userCreationMessage .= $clang->gT("A username was not supplied or the username is invalid.")."<br />";
			
			$valid_user = false;
		}

		$valid_captcha = true;
		require_once('recaptchalib.php');
		$privatekey = "";
		$resp = recaptcha_check_answer($privatekey, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"],
			$_POST["recaptcha_response_field"]);
		if (!$resp->is_valid) {
			if ($valid_email && $valid_user)
				$userCreationMessage .= $failedToAddUser;
			$userCreationMessage .= $clang->gT("PECI: The reCaptcha failed");
			$valid_captcha = false;
		}
		
		if ($valid_email && $valid_user && $valid_captcha) {
			$uquery = "INSERT INTO {$dbprefix}users (users_name, password,full_name,parent_id,lang,email,"
			. "create_survey,create_user,delete_user,superadmin,configurator,manage_template,manage_label,htmleditormode) "
			. "VALUES ('".db_quote($new_user)."', '".SHA256::hashing($postnew_password)."', '".db_quote($postnew_full_name)
			. "', '1', '".db_quote($new_loginlang)."', '".db_quote($postnew_email)."',1,0,0,0,0,0,0,'none')";

			$uresult = $connect->Execute($uquery); //Checked

			if($uresult) {
				$newqid = $connect->Insert_ID("{$dbprefix}users","uid");

				// add default template to template rights for user
				$template_query = "INSERT INTO {$dbprefix}templates_rights VALUES('$newqid','default','1')";
				$connect->Execute($template_query); //Checked
				$userCreationMessage .= $clang->gT("PECI: New user has been added") . "<br />";		
			} else {
				$userCreationMessage .= $failedToAddUser;
				$userCreationMessage .= $clang->gT("The user name already exists.") . "<br />";
			}
		}		
	}
} elseif ($action == "logout") {
    killSession();
    $userCreationMessage = '<p>' . $clang->gT("Logout successful.") . '</p>';
}

if (isset($loginTryNotification) && $loginTryNotification != '') {
	$_SESSION['userNotification'] = $loginTryNotification;
}

if (isset($userCreationMessage) && $userCreationMessage != '') {
	$_SESSION['userNotification'] = $userCreationMessage;
}

function fGetLoginAttemptUpdateQry($la,$sIp)
{
	$timestamp = date("Y-m-d H:i:s");
	if ($la)
	$query = "UPDATE ".db_table_name('failed_login_attempts')
	." SET number_attempts=number_attempts+1, last_attempt = '$timestamp' WHERE ip='$sIp'";
    else
	$query = "INSERT INTO ".db_table_name('failed_login_attempts') . "(ip, number_attempts,last_attempt)"
                 ." VALUES('$sIp',1,'$timestamp')";

    return $query;
}

