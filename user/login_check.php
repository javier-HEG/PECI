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

if (!isset($dbprefix) || isset($_REQUEST['dbprefix'])) {die("Cannot run this script directly");}
if (!isset($action)) {$action=returnglobal('action');}

// check data for login
if( isset($_POST['user']) && isset($_POST['password']) ||
($action == "forgotpass") || ($action == "login") ||
($action == "logout") ||
($useWebserverAuth === true && !isset($_SESSION['loginID'])) )
{
    include("usercontrol.php");
}

// Originally this variable is that set to "admin.php" in config.php,
// we however need to send calls to our user script.
$scriptname = "user.php";

// login form
if(!isset($_SESSION['loginID']) && $action != "forgotpass" && ($action != "logout" || ($action == "logout" && !isset($_SESSION['loginID'])))) {
    if($action == "forgotpassword") {
        $loginsummary = '
			<form class="form44" name="forgotpassword" id="forgotpassword" method="post" action="'.$scriptname.'" >
				<p><strong>'.$clang->gT('You have to enter user name and email.').'</strong></p>
				<ul>
					<li><label for="user">'.$clang->gT('Username').'</label><input name="user" id="user" type="text" size="60" maxlength="60" value="" /></li>
					<li><label for="email">'.$clang->gT('Email').'</label><input name="email" id="email" type="text" size="60" maxlength="60" value="" /></li>
					<p><input type="hidden" name="action" value="forgotpass" />
					<input class="action" type="submit" value="'.$clang->gT('Check Data').'" /></p>
					<p><a href="'.$scriptname.'">'.$clang->gT('Main Admin Screen').'</a></p>
				</ul>
			</form>
            <br />';
    } else { //if (!isset($loginsummary)) {
    	// Could be at login or after logout
        $refererargs=''; // If this is a direct access to user.php, no args are given
        
        //include("database.php");
        $sIp = $_SERVER['REMOTE_ADDR'];
        $query = "SELECT * FROM ".db_table_name('failed_login_attempts'). " WHERE ip='$sIp';";
        $ADODB_FETCH_MODE = ADODB_FETCH_ASSOC;
        $result = $connect->query($query) or safe_die ($query."<br />".$connect->ErrorMsg());
        $bCannotLogin = false;
        $intNthAttempt = 0;
        if ($result!==false && $result->RecordCount() >= 1) {
            $field = $result->FetchRow();
            $intNthAttempt = $field['number_attempts'];
            if ($intNthAttempt>=$maxLoginAttempt){
                $bCannotLogin = true;
            }

            $iLastAttempt = strtotime($field['last_attempt']);

            if (time() > $iLastAttempt + $timeOutTime) {
                $bCannotLogin = false;
                $query = "DELETE FROM ".db_table_name('failed_login_attempts'). " WHERE ip='$sIp';";
                $result = $connect->query($query) or safe_die ($query."<br />".$connect->ErrorMsg());
            }
        }
        
        if (!isset($loginsummary)) {
        	$loginsummary = "";
        }
        
        if (!$bCannotLogin) {
            if (!isset($logoutsummary)) {
                $loginsummary = "<form name='loginform' id='loginform' method='post' action='$scriptname' >"
                	. "<p><strong>".$clang->gT("You have to login first.")."</strong></p><br />";
            } else {
                $loginsummary = "<form name='loginform' id='loginform' method='post' action='$scriptname' >"
                	. "<p><strong>".$logoutsummary."</strong></p><br />";
            }

            $loginsummary .= "<ul>
								<li><label for='user'>".$clang->gT("Username")."</label>
                                <input name='user' id='user' type='text' size='40' maxlength='40' value='' /></li>
                                <li><label for='password'>".$clang->gT("Password")."</label>
                                <input name='password' id='password' type='password' size='40' maxlength='40' /></li>\n";
            
            $loginsummary .= "</ul>
                		<p><input type='hidden' name='action' value='login' />
                        <input type='hidden' name='refererargs' value='".$refererargs."' />
                        <input type='hidden' name='loginlang' value='default' />
                        <input class='action' type='submit' value='".$clang->gT("Login")."' /><br />&nbsp;\n<br/>";
            
            $loginsummary .= '<p><a href="#" onclick="$(\'#loginform\').hide(); $(\'#newuserform\').show();">' . $clang->gT("PECI: Create new user") . '</a></p>';
        } else {
        	$loginsummary = "<form name='loginform' id='loginform' method='post' action='$scriptname' >";
            $loginsummary .= "<p>".sprintf($clang->gT("You have exceeded you maximum login attempts. Please wait %d minutes before trying again"),($timeOutTime/60))."<br /></p>";
        }
        
        if ($display_user_password_in_email === true) {
            $loginsummary .= "<p><a href='$scriptname?action=forgotpassword'>".$clang->gT("Forgot Your Password?")."</a></p>";
        }

        $loginsummary .= "</form>";
        
        // Create new user
        $loginsummary .= "<form name='newuserform' id='newuserform' method='post' action='$rooturl/user/user.php' style='display:none;'>";
        $loginsummary .= '<p><strong>' . $clang->gT('PECI: Create new user') . '</strong></p><br />';
        $loginsummary .= "<ul>
	        <li><label for='new_full_name'>".$clang->gT("Full name")."</label>
	        <input name='new_full_name' id='new_full_name' type='text' size='40' maxlength='40' value='' /></li>
	        <li><label for='new_user'>".$clang->gT("Username")."</label>
	        <input name='new_user' id='new_user' type='text' size='40' maxlength='40' value='' /></li>
	        <li><label for='new_email'>".$clang->gT("Email address")."</label>
	        <input name='new_email' id='new_email' type='text' size='40' maxlength='40' value='' /></li>
	        <li><label for='password'>".$clang->gT("Password")."</label>
	        <input name='new_password' id='new_password' type='password' size='40' maxlength='40' value='' /></li>
	        <li><label for='password-check'>".$clang->gT("Repeat Password")."</label>
	        <input name='new_password_check' id='new_password_check' type='password' size='40' maxlength='40' /></li>
	        <li><label for='loginlang'>".$clang->gT("Interface language")."</label>
	        <select id='loginlang' name='loginlang' style='width:216px;'>";

        $currentLanguage = (isset($_SESSION['adminlang']) ? $_SESSION['adminlang'] : $clang->getlangcode());
        foreach (getlanguagedata(true) as $langkey=>$languagekind) {
    		$loginsummary .= "<option value='$langkey'"
    			. ($langkey == $currentLanguage ? " selected='selected'>" : ">")
    			. $languagekind['nativedescription'] . " - " . $languagekind['description'] . "</option>\n";
        }

        $loginsummary .= "</select></li></ul>";

        require_once('recaptchalib.php');
		$publickey = "6Ld6H9kSAAAAABPEFxHMtWb2K4cmtIyS4KKNKofP";
        $loginsummary .= '<p>' . recaptcha_get_html($publickey) . '</p>';

		$loginsummary .= "<p>
				<input type='hidden' value='addonlineuser' name='action'>
                <input class='action' type='submit' value='".$clang->gT("PECI: Create new user")."' /><br /><br />
			</p>";
        
        $loginsummary .= '<p><a href="#" onclick="$(\'#newuserform\').hide(); $(\'#loginform\').show();">' . $clang->gT("PECI: Back to login form") . '</a></p>';

        $loginsummary .= "</form>";
        
        $loginsummary .= "<script type='text/javascript'>\n";
        $loginsummary .= "document.getElementById('user').focus();\n";
        $loginsummary .= "</script>\n";
    }
}
