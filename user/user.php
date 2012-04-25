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

require_once(dirname(__FILE__).'/../classes/core/startup.php');

if (version_compare(PHP_VERSION,'5','>=')&& !(function_exists('domxml_new_doc')))
{
    require_once(dirname(__FILE__).'/../admin/classes/core/domxml-php4-to-php5.php');
}
require_once(dirname(__FILE__).'/../config-defaults.php');
require_once(dirname(__FILE__).'/../common.php');

require_once('../admin/htmleditor-functions.php');

require_once('user_functions.php');

$_SESSION['FileManagerContext']='';

if (!isset($surveyid)) {$surveyid=returnglobal('sid');} //SurveyID
if (!isset($ugid)) {$ugid=returnglobal('ugid');} //Usergroup-ID
if (!isset($gid)) {$gid=returnglobal('gid');} //GroupID
if (!isset($qid)) {$qid=returnglobal('qid');} //QuestionID
if (!isset($lid)) {$lid=returnglobal('lid');} //LabelID
if (!isset($code)) {$code=returnglobal('code');} // ??
if (!isset($action)) {$action=returnglobal('action');} //Desired action
if (!isset($subaction)) {$subaction=returnglobal('subaction');} //Desired subaction
if (!isset($editedaction)) {$editedaction=returnglobal('editedaction');} // for html editor integration

if ($action != 'showprintablesurvey' && substr($action,0,4)!= 'ajax') {
	$adminoutput = "<div id='wrapper'>";
	
	// Add the user menu for choose among existing surveys
	$adminoutput .= getProjectSelectorMenu();
} else {
    $adminoutput='';
}

include_once('login_check.php');

if ( $action == 'CSRFwarn')
{
    include('../admin/access_denied.php');
}

if ( $action == 'FakeGET')
{
    include('../admin/access_denied.php');
}

if (isset($_SESSION['loginID']))
{
	// Analogous to what we do in 'admin/admin.php', should the
	// user have super-admin rights we send him to the interface
	// in '/admin'.
	if ($_SESSION['USER_RIGHT_SUPERADMIN'])
	{
		print ('<script type="text/javascript">window.open(\'../admin\', \'_top\');</script>');
	}
	
    //VARIOUS DATABASE OPTIONS/ACTIONS PERFORMED HERE
    if (in_array($action, array('updateemailtemplates','delsurvey','delgroup','delquestion','insertsurvey','updatesubquestions','copynewquestion','insertquestiongroup','insertCSV','insertquestion','updatesurveysettings','updatesurveysettingsandeditlocalesettings','updatesurveylocalesettings','updategroup','deactivate','savepersonalsettings','updatequestion','updateansweroptions','renumberquestions','updatedefaultvalues')))
    {
        include('../admin/database.php');
    }

    sendcacheheaders();
    
    if ($action == 'importsurvey' || $action == 'copysurvey') {
        if ($_SESSION['USER_RIGHT_CREATE_SURVEY']==1)	{
        	include('../admin/http_importsurvey.php');
        } else {
        	include('../admin/access_denied.php');
        }
    }
    
    if ($action == 'activate') {
        if (bHasSurveyPermission($surveyid,'surveyactivation','update'))    {include('../admin/activate.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'conditions')
    {
        if(bHasSurveyPermission($surveyid,'surveycontent','read'))    {include('../admin/conditionshandling.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'importsurveyresources')
    {
        if (bHasSurveyPermission($surveyid,'surveycontent','import'))	{$_SESSION['FileManagerContext']="edit:survey:$surveyid";include('../admin/import_resources_zip.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'exportstructureLsrcCsv')
    {
        if(bHasSurveyPermission($surveyid,'surveycontent','export'))    {include('../admin/export_structure_lsrc.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'exportstructurequexml')
    {
        if(bHasSurveyPermission($surveyid,'surveycontent','export'))    {include('../admin/export_structure_quexml.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'exportstructurexml')
    {
        if(bHasSurveyPermission($surveyid,'surveycontent','export'))    {include('../admin/export_structure_xml.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'exportstructurecsvGroup')
    {
        if(bHasSurveyPermission($surveyid,'surveycontent','export'))    {include('../admin/dumpgroup.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'exportstructureLsrcCsvGroup')
    {
        if(bHasSurveyPermission($surveyid,'surveycontent','export'))    {include('../admin/dumpgroup.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'exportstructurecsvQuestion')
    {
        if(bHasSurveyPermission($surveyid,'surveycontent','export'))    {include('../admin/dumpquestion.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'exportstructureLsrcCsvQuestion')
    {
        if(bHasSurveyPermission($surveyid,'surveycontent','export'))    {include('../admin/dumpquestion.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'exportsurvresources')
    {
        if(bHasSurveyPermission($surveyid,'surveycontent','export'))    {$_SESSION['FileManagerContext']="edit:survey:$surveyid";include('../admin/export_resources_zip.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'deactivate')
    {
        if(bHasSurveyPermission($surveyid,'surveyactivation','update'))    {include('../admin/deactivate.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'deletesurvey')
    {
        if(bHasSurveyPermission($surveyid,'survey','delete'))    {include('../admin/deletesurvey.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'resetsurveylogic')
    {
        if(bHasSurveyPermission($surveyid,'surveycontent','update'))    {include('../admin/resetsurveylogic.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'importgroup')
    {
        if(bHasSurveyPermission($surveyid,'surveycontent','import'))    {include('../admin/importgroup.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'importquestion')
    {
        if(bHasSurveyPermission($surveyid,'surveycontent','import'))    {include('../admin/importquestion.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'listcolumn')
    {
        if(bHasSurveyPermission($surveyid,'statistics','read'))    {include('../admin/listcolumn.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'previewquestion')
    {
        if(bHasSurveyPermission($surveyid,'surveycontent','read'))    {include('../admin/preview.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'previewgroup')
    {
        require_once('../index.php');
        exit;

    }
    elseif ($action=='addgroup' || $action=='editgroup' || $action=='ordergroups')
    {
        if(bHasSurveyPermission($surveyid,'surveycontent','read'))    {$_SESSION['FileManagerContext']="edit:group:$surveyid"; include('../admin/questiongrouphandling.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'saved')
    {
        if(bHasSurveyPermission($surveyid,'responses','read'))    {include('../admin/saved.php');}
        else { include('../admin/access_denied.php');}
    }
//<AdV>
    elseif ($action == 'translate')
    {
        if(bHasSurveyPermission($surveyid,'translations','read'))    {$_SESSION['FileManagerContext']="edit:translate:$surveyid"; include('../admin/translate.php');}
        else { include('../admin/access_denied.php'); }
    }
//</AdV>
    elseif ($action == 'tokens')
    {
        if(bHasSurveyPermission($surveyid,'tokens','read'))
        {
            $_SESSION['FileManagerContext']="edit:emailsettings:$surveyid";
            include('../admin/tokens.php');
        }
        else { include('../admin/access_denied.php'); }
    }
    elseif ($action == 'emailtemplates')
    {
        $_SESSION['FileManagerContext']="edit:emailsettings:$surveyid";
    }
    elseif ($action == 'iteratesurvey')
    {
        if(bHasSurveyPermission($surveyid,'surveyactivation','update'))    {include('../admin/iterate_survey.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action=='showquexmlsurvey')
    {
        include('../admin/quexmlsurvey.php'); //Same rights as printable
    }
    elseif ($action=='showprintablesurvey')
    {
        include('../admin/printablesurvey.php'); //No special right needed to show the printable survey
    }
    elseif ($action=='listcolumn')
    {
		include('../admin/listcolumn.php');
    }
    elseif ($action=='update')
    {
    	include('../admin/access_denied.php');
    }
    elseif ($action=='assessments' || $action=='assessmentdelete' || $action=='assessmentedit' || $action=='assessmentadd' || $action=='assessmentupdate')
    {
        if(bHasSurveyPermission($surveyid,'assessments','read'))    {
            $_SESSION['FileManagerContext']="edit:assessments:$surveyid";
            include('../admin/assessments.php');
        }
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'replacementfields')
    {
        switch ($editedaction)
        {
            case 'labels':
                	include('../admin/access_denied.php');
                break;
            case 'newsurvey':
                if ($_SESSION['USER_RIGHT_SUPERADMIN'] == 1 || $_SESSION['USER_RIGHT_CREATE_SURVEY'] == 1)
                {
                    include('../admin/fck_LimeReplacementFields.php');exit;
                }
                else
                {
                    include('../admin/access_denied.php');
                }
                break;
            case 'editsurveylocalesettings':
            case 'updatesurveysettingsandeditlocalesettings':
            case 'translatetitle':
            case 'translatedescription':
            case 'translatewelcome':
            case 'translateend':
                if (bHasSurveyPermission($surveyid,'surveysettings','update') && bHasSurveyPermission($surveyid,'surveylocale','read'))
                {
                    $_SESSION['FileManagerContext']="edit:survey:$surveyid";
                    include('../admin/fck_LimeReplacementFields.php');exit;
                }
                else
                {
                    include('../admin/access_denied.php');
                }
                break;
            case 'tokens': // email
            case 'emailtemplates': // email
                if (bHasSurveyPermission($surveyid,'tokens','update'))
                {
                    $_SESSION['FileManagerContext']="edit:emailsettings:$surveyid";
                    include('../admin/fck_LimeReplacementFields.php');exit;
                }
                else
                {
                    include('../admin/access_denied.php');
                }
                break;
            case 'editquestion':
            case 'copyquestion':
            case 'addquestion':
            case 'translatequestion':
            case 'translatequestion_help':
                if (bHasSurveyPermission($surveyid,'surveycontent','read'))
                {
                    $_SESSION['FileManagerContext']="edit:question:$surveyid";
                    include('../admin/fck_LimeReplacementFields.php');exit;
                }
                else
                {
                    include('../admin/access_denied.php');
                }
                break;
            case 'editgroup':
            case 'addgroup':
            case 'translategroup':
            case 'translategroup_desc':
                if (bHasSurveyPermission($surveyid,'surveycontent','read'))
                {
                    $_SESSION['FileManagerContext']="edit:group:$surveyid";
                    include('../admin/fck_LimeReplacementFields.php');exit;
                }
                else
                {
                    include('../admin/access_denied.php');
                }
                break;
            case 'editanswer':
            case 'translateanswer':
                if (bHasSurveyPermission($surveyid,'surveycontent','read'))
                {
                    $_SESSION['FileManagerContext']="edit:answer:$surveyid";
                    include('../admin/fck_LimeReplacementFields.php');exit;
                }
                else
                {
                    include('../admin/access_denied.php');
                }
                break;
            case 'assessments':
            case 'assessmentedit':
                if(bHasSurveyPermission($surveyid,'assessments','read'))    {
                    $_SESSION['FileManagerContext']="edit:assessments:$surveyid";
                    include('../admin/fck_LimeReplacementFields.php');
                }
                else { include('../admin/access_denied.php');}
                break;
            default:
                break;
        }
    }
    elseif ($action == 'ajaxtranslategoogleapi')
    {
        if(bHasSurveyPermission($surveyid,'translations','read'))
        {
            include('../admin/translate_google_api.php');
        }
        else
        {
            include('../admin/access_denied.php');
        }
    }
    elseif ($action=='ajaxowneredit' || $action == 'ajaxgetusers'){

        include('../admin/surveylist.php');
    }
    if (!isset($assessmentsoutput) && !isset($statisticsoutput) && !isset($browseoutput) &&
        !isset($savedsurveyoutput) && !isset($listcolumnoutput) && !isset($conditionsoutput) &&
        !isset($importoldresponsesoutput) && !isset($exportroutput) && !isset($vvoutput) &&
        !isset($tokenoutput) && !isset($exportoutput) && !isset($templatesoutput) && !isset($translateoutput) && //<AdV>
        !isset($iteratesurveyoutput) && (substr($action,0,4)!= 'ajax') && ($action!='update') &&
        (isset($surveyid) || $action == "" || preg_match('/^(personalsettings|statistics|copysurvey|importsurvey|editsurveysettings|editsurveylocalesettings|updatesurveysettings|updatesurveysettingsandeditlocalesettings|updatedefaultvalues|ordergroups|dataentry|newsurvey|globalsettings|editusergroups|editusergroup|exportspss|surveyrights|quotas|editusers|login|browse|vvimport|vvexport|setuserrights|modifyuser|setusertemplates|deluser|adduser|userrights|usertemplates|moduser|addusertogroup|deleteuserfromgroup|globalsettingssave|savepersonalsettings|addusergroup|editusergroupindb|usergroupindb|finaldeluser|delusergroup|mailusergroup|mailsendusergroup)$/',$action)))
    {
        if ($action=='editsurveysettings' || $action=='editsurveylocalesettings')
        {
            $_SESSION['FileManagerContext']="edit:survey:$surveyid";
        }
        include('../admin/html_functions.php');
        include('../admin/html.php');
        include('html.php');
    }

    if ($action == "listsurveys"){
        include('../admin/html_functions.php');
        include('../admin/html.php');
        include('html.php');
        include('../admin/surveylist.php');
    }

    if ($action == 'dataentry')
    {
        if (bHasSurveyPermission($surveyid, 'responses','read') || bHasSurveyPermission($surveyid, 'responses','create')  || bHasSurveyPermission($surveyid, 'responses','update'))
        {
            include('../admin/dataentry.php');
        }
        else
        {
            include('../admin/access_denied.php');
        }
    }
    elseif ($action == 'exportresults')
    {
        if(bHasSurveyPermission($surveyid,'responses','export'))    {include('../admin/exportresults.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'statistics')
    {
        if(bHasSurveyPermission($surveyid,'statistics','read'))    {include('../admin/statistics.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'importoldresponses')
    {
        if(bHasSurveyPermission($surveyid,'responses','create'))    {include('../admin/importoldresponses.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'exportspss')
    {
        if(bHasSurveyPermission($surveyid,'responses','export'))
        {
            include('../admin/export_data_spss.php');
        }
        else
        {
            include('../admin/access_denied.php');
        }
    }
    elseif ($action == 'browse')
    {
        if(bHasSurveyPermission($surveyid,'responses','read') || bHasSurveyPermission($surveyid,'statistics','read') || bHasSurveyPermission($surveyid,'responses','export'))
        {
            include('../admin/browse.php');
        }
        else
        {
            include('../admin/access_denied.php');
        }
    }
    elseif ($action == 'exportr')
    {
        if(bHasSurveyPermission($surveyid,'responses','export'))    {include('../admin/export_data_r.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'vvexport')
    {
        if(bHasSurveyPermission($surveyid,'responses','export'))    {include('../admin/vvexport.php');}
        else { include('../admin/access_denied.php');}
    }
    elseif ($action == 'vvimport')
    {
        if(bHasSurveyPermission($surveyid,'responses','create'))    {include('../admin/vvimport.php');}
        else { include('../admin/access_denied.php');}
    }
    if ($action=='addquestion'    || $action=='copyquestion' || $action=='editquestion' || $action=='editdefaultvalues' ||
        $action=='orderquestions' || $action=='ajaxquestionattributes' || $action=='ajaxlabelsetpicker' || $action=='ajaxlabelsetdetails')
    {
        if(bHasSurveyPermission($surveyid,'surveycontent','read'))
        {
            $_SESSION['FileManagerContext']="edit:question:$surveyid";
            include('../admin/questionhandling.php');
        }
        else
        {
            include('../admin/access_denied.php');
        }
    }

    if ($action=='adduser' || $action=='deluser'|| $action=='finaldeluser' || $action=='moduser' || $action=='setusertemplates' || $action=='usertemplates' ||                                        //Still to check
    $action=='userrights' || $action=='modifyuser' || $action=='editusers' ||
    $action=='addusergroup' || $action=='editusergroup' || $action=='mailusergroup' ||
    $action=='delusergroup' || $action=='usergroupindb' || $action=='mailsendusergroup' ||
    $action=='editusergroupindb' || $action=='editusergroups' || $action=='deleteuserfromgroup' ||
    $action=='addusertogroup' || $action=='setuserrights' || $action=='setasadminchild')
    {
        include ('userrighthandling.php');
    }


    // For some output we dont want to have the standard admin menu bar
    if (!isset($labelsoutput)  && !isset($templatesoutput) && !isset($printablesurveyoutput) &&
    !isset($assessmentsoutput) && !isset($tokenoutput) && !isset($browseoutput) && !isset($exportspssoutput) &&  !isset($exportroutput) &&
    !isset($dataentryoutput) && !isset($statisticsoutput)&& !isset($savedsurveyoutput)  && !isset($translateoutput) && //<AdV>
    !isset($exportoutput) && !isset($importoldresponsesoutput) && !isset($conditionsoutput) &&
    !isset($vvoutput) && !isset($listcolumnoutput) && !isset($importlabelresources) && !isset($iteratesurveyoutput) &&
    (substr($action,0,4)!= 'ajax') && $action!='update' && $action!='showphpinfo')
    {
        $adminoutput .= showUserMenu();
        $adminoutput .= showUserFirstStepsHelp();
    }

    if (isset($databaseoutput))  {$adminoutput.= $databaseoutput;}
    if (isset($templatesoutput)) {$adminoutput.= $templatesoutput;}
    if (isset($accesssummary  )) {$adminoutput.= $accesssummary;}
    if (isset($surveysummary  )) {$adminoutput.= $surveysummary;}
    if (isset($usergroupsummary)){$adminoutput.= $usergroupsummary;}
    if (isset($usersummary    )) {$adminoutput.= $usersummary;}
    if (isset($groupsummary   )) {$adminoutput.= $groupsummary;}
    if (isset($questionsummary)) {$adminoutput.= $questionsummary;}
    if (isset($vasummary      )) {$adminoutput.= $vasummary;}
    if (isset($addsummary     )) {$adminoutput.= $addsummary;}
    if (isset($answersummary  )) {$adminoutput.= $answersummary;}
    if (isset($cssummary      )) {$adminoutput.= $cssummary;}
    if (isset($listcolumnoutput)) {$adminoutput.= $listcolumnoutput;}
    if (isset($ajaxoutput)) {$adminoutput.= $ajaxoutput;}


    if (isset($editgroup)) {$adminoutput.= $editgroup;}
    if (isset($editquestion)) {$adminoutput.= $editquestion;}
    if (isset($editdefvalues)) {$adminoutput.= $editdefvalues;}
    if (isset($editsurvey)) {$adminoutput.= $editsurvey;}
    if (isset($translateoutput)) {$adminoutput.= $translateoutput;}  //<AdV>
    if (isset($quotasoutput)) {$adminoutput.= $quotasoutput;}
    if (isset($labelsoutput)) {$adminoutput.= $labelsoutput;}
    if (isset($listsurveys)) {$adminoutput.= $listsurveys; }
    if (isset($integritycheck)) {$adminoutput.= $integritycheck;}
    if (isset($ordergroups)){$adminoutput.= $ordergroups;}
    if (isset($orderquestions)) {$adminoutput.= $orderquestions;}
    if (isset($surveysecurity)) {$adminoutput.= $surveysecurity;}
    if (isset($exportstructure)) {$adminoutput.= $exportstructure;}
    if (isset($newsurvey)) {$adminoutput.= $newsurvey;}
    if (isset($newgroupoutput)) {$adminoutput.= $newgroupoutput;}
    if (isset($newquestionoutput)) {$adminoutput.= $newquestionoutput;}
    if (isset($newanswer)) {$adminoutput.= $newanswer;}
    if (isset($editanswer)) {$adminoutput.= $editanswer;}
    if (isset($assessmentsoutput)) {$adminoutput.= $assessmentsoutput;}
    if (isset($sHTMLOutput))     {$adminoutput.= $sHTMLOutput;}


    if (isset($importsurvey)) {$adminoutput.= $importsurvey;}
    if (isset($importsurveyresourcesoutput)) {$adminoutput.= $importsurveyresourcesoutput;}
    if (isset($importgroup)) {$adminoutput.= $importgroup;}
    if (isset($importquestion)) {$adminoutput.= $importquestion;}
    if (isset($printablesurveyoutput)) {$adminoutput.= $printablesurveyoutput;}
    if (isset($activateoutput)) {$adminoutput.= $activateoutput;}
    if (isset($deactivateoutput)) {$adminoutput.= $deactivateoutput;}
    if (isset($tokenoutput)) {$adminoutput.= $tokenoutput;}
    if (isset($browseoutput)) {$adminoutput.= $browseoutput;}
    if (isset($iteratesurveyoutput)) {$adminoutput.= $iteratesurveyoutput;}
    if (isset($dataentryoutput)) {$adminoutput.= $dataentryoutput;}
    if (isset($statisticsoutput)) {$adminoutput.= $statisticsoutput;}
    if (isset($exportoutput)) {$adminoutput.= $exportoutput;}
    if (isset($savedsurveyoutput)) {$adminoutput.= $savedsurveyoutput;}
    if (isset($importoldresponsesoutput)) {$adminoutput.= $importoldresponsesoutput;}
    if (isset($conditionsoutput)) {$adminoutput.= $conditionsoutput;}
    if (isset($deletesurveyoutput)) {$adminoutput.= $deletesurveyoutput;}
    if (isset($resetsurveylogicoutput)) {$adminoutput.= $resetsurveylogicoutput;}
    if (isset($vvoutput)) {$adminoutput.= $vvoutput;}
    if (isset($dumpdboutput)) {$adminoutput.= $dumpdboutput;}
    if (isset($exportspssoutput)) {$adminoutput.= $exportspssoutput;}
    if (isset($exportroutput)) {$adminoutput.= $exportroutput;}
    if (isset($loginsummary)) {$adminoutput.= $loginsummary;}


    if (!isset($printablesurveyoutput) && $subaction!='export' && (substr($action,0,4)!= 'ajax'))
    {
        if (!isset($_SESSION['metaHeader'])) {$_SESSION['metaHeader']='';}
        
        // Add the user default stylesheet after the admin styleseet on the
        // automatically generated header
        $adminHeader = getUserHeader($_SESSION['metaHeader']);
        
        // Include header before the already generated code
        // NB. All future output is written into this and then outputted at the end of file
        $adminoutput = $adminHeader.$adminoutput;

        unset($_SESSION['metaHeader']);
        
        $adminoutput .= "</div>\n";
        
        if(!isset($_SESSION['checksessionpost'])) {
            $_SESSION['checksessionpost'] = '';
        }
        
        $adminoutput .= "<script type=\"text/javascript\">\n"
        . "<!--\n"
        . "\tfor(i=0; i<document.forms.length; i++)\n"
        . "\t{\n"
        . "var el = document.createElement('input');\n"
        . "el.type = 'hidden';\n"
        . "el.name = 'checksessionbypost';\n"
        . "el.value = '".$_SESSION['checksessionpost']."';\n"
        . "document.forms[i].appendChild(el);\n"
        . "\t}\n"
        . "\n"
        . "\tfunction addHiddenElement(theform,thename,thevalue)\n"
        . "\t{\n"
        . "var myel = document.createElement('input');\n"
        . "myel.type = 'hidden';\n"
        . "myel.name = thename;\n"
        . "theform.appendChild(myel);\n"
        . "myel.value = thevalue;\n"
        . "return myel;\n"
        . "\t}\n"
        . "\n"
        . "\tfunction sendPost(myaction,checkcode,arrayparam,arrayval)\n"
        . "\t{\n"
        . "var myform = document.createElement('form');\n"
        . "document.body.appendChild(myform);\n"
        . "myform.action =myaction;\n"
        . "myform.method = 'POST';\n"
        . "for (i=0;i<arrayparam.length;i++)\n"
        . "{\n"
        . "\taddHiddenElement(myform,arrayparam[i],arrayval[i])\n"
        . "}\n"
        . "addHiddenElement(myform,'checksessionbypost',checkcode)\n"
        . "myform.submit();\n"
        . "\t}\n"
        . "\n"
        . "//-->\n"
        . "</script>\n";
    }
} else { //not logged in
	if ($action == 'addonlineuser') {
        include ('userrighthandling.php');
        $adminoutput .= $addsummary;
	}
	
	sendcacheheaders();
	if (!isset($_SESSION['metaHeader'])) {
		$_SESSION['metaHeader']='';
	}
	
	$adminoutput = getUserHeader($_SESSION['metaHeader']).$adminoutput.$loginsummary;  // All future output is written into this and then outputted at the end of file

	unset($_SESSION['metaHeader']);
	
	$adminoutput .= "</div>\n";
}

// Regardless of the user being logged in or not
$adminFooter = getUserFooter("http://www.hesge.ch/heg/", "© 2012 Haute École de Gestion de Genève");

$adminoutput .= <<<EOF
	<div id="mainTitleMenu"></div>
	<div id="usabilityTabNameBar"></div>
	<div id="usabilityTabContainer"></div>
	
	<script type="text/javascript">
		createAllUsabilityTabNames();
	</script>

	$adminFooter
EOF;

if (($action=='showphpinfo') && ($_SESSION['USER_RIGHT_CONFIGURATOR'] == 1)) {
	phpinfo();
} else {
	echo $adminoutput;
}

