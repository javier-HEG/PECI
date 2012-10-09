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

//Login Check dies also if the script is started directly
include_once("login_check.php");

$js_admin_includes[]='../admin/scripts/subquestions.js';
$js_admin_includes[]='../scripts/jquery/jquery.blockUI.js';
$js_admin_includes[]='../scripts/jquery/jquery.selectboxes.min.js';

$surveyid = $_REQUEST["surveyid"];
$gid = $_REQUEST["gid"];
$qid = $_REQUEST["qid"];

$_SESSION['FileManagerContext']="edit:answer:{$surveyid}";

// Get base language for survey
$baselang = GetBaseLanguageFromSurveyID($surveyid);

$sQuery = "SELECT type FROM ".db_table_name('questions')." WHERE qid={$qid} AND language='{$baselang}'";
$sQuestiontype=$connect->GetOne($sQuery);
$aQuestiontypeInfo=getqtypelist($sQuestiontype,'array');
$iScaleCount=$aQuestiontypeInfo[$sQuestiontype]['subquestions'];

for ($iScale = 0; $iScale < $iScaleCount; $iScale++) {
	$sQuery = "SELECT * FROM ".db_table_name('questions')." WHERE parent_qid={$qid} AND language='{$baselang}' and scale_id={$iScale}";
	$subquestiondata=$connect->GetArray($sQuery);

	if (count($subquestiondata) == 0)	{
		$sQuery = "INSERT INTO ".db_table_name('questions')." (sid,gid,parent_qid,title,question,question_order,language,scale_id)
                       VALUES($surveyid,$gid,$qid,'SQ001',".db_quoteall($clang->gT('Some example subquestion')).",1,".db_quoteall($baselang).",{$iScale})";
		$connect->Execute($sQuery); //Checked
		$sQuery = "SELECT * FROM ".db_table_name('questions')." WHERE parent_qid={$qid} AND language='{$baselang}' and scale_id={$iScale}";
		$subquestiondata=$connect->GetArray($sQuery);
	}
}

// Language count is set to 1 because only the baselanguage is used
$vasummary = "\n<script type='text/javascript'>
	var languagecount=1;\n
	var newansweroption_text='" . $clang->gT('New answer option','js') . "';
	var strcode='" . $clang->gT('Code','js') . "';
	var strlabel='" . $clang->gT('Label','js') . "';
	var strCantDeleteLastAnswer='" . $clang->gT('You cannot delete the last subquestion.','js') . "';
	var lsbrowsertitle='" . $clang->gT('Label set browser','js') . "';
	var quickaddtitle='" . $clang->gT('Quick-add subquestions','js') . "';
	var duplicateanswercode='" . $clang->gT('Error: You are trying to use duplicate subquestion codes.','js') . "';
	var langs='$baselang';</script>\n";

// Get question type
$qquery = "SELECT type FROM ".db_table_name('questions')." WHERE qid=$qid AND language='".$baselang."'";
$qresult = db_execute_assoc($qquery); //Checked
while ($qrow=$qresult->FetchRow()) {
	$qtype = $qrow['type'];
}

// Header, form and hidden input values for the form
$query = "SELECT question_order FROM ".db_table_name('questions')." WHERE parent_qid='{$qid}' AND language='".$baselang."' ORDER BY question_order desc";
$result = db_execute_assoc($query) or safe_die($connect->ErrorMsg()); //Checked
$anscount = $result->RecordCount();
$row = $result->FetchRow();
$maxsortorder = $row['question_order']+1;
$vasummary .= "<div class='header ui-widget-header'>\n"
	.$clang->gT("Edit subquestions")
	."</div>\n"
	."<form id='editsubquestionsform' name='editsubquestionsform' onSubmit=\"return false;\">\n"
	. "<input type='hidden' name='sid' value='$surveyid' />\n"
	. "<input type='hidden' name='gid' value='$gid' />\n"
	. "<input type='hidden' name='qid' value='$qid' />\n"
	. "<input type='hidden' id='action' name='action' value='updatesubquestions' />\n"
	. "<input type='hidden' name='checksessionbypost' value='{$_SESSION['checksessionpost']}' />\n"
	. "<input type='hidden' id='sortorder' name='sortorder' value='' />\n"
	. "<input type='hidden' id='deletedqids' name='deletedqids' value='' />\n";

$onSubmit = "if (codeCheck('code_',$maxsortorder,'"
	. $clang->gT("Error: You are trying to use duplicate answer codes.",'js') . "','"
	. $clang->gT("Error: 'other' is a reserved keyword.",'js')."');";

$sortorderids = '';
$codeids = '';

// The following line decides if the assessment input fields are visible or not
// for some question types the assessment values is set in the label set instead of the answers
$qtypes = getqtypelist('','array');

$scalecount = $qtypes[$qtype]['subquestions'];

for ($scale_id = 0; $scale_id < $scalecount; $scale_id++) {
	$position=0;
	
	if ($scalecount > 1) {
		if ($scale_id == 0) {
			$vasummary .= "<div class='header ui-widget-header'>\n" . $clang->gT("Y-Scale") . "</div>";
		} else {
			$vasummary .= "<div class='header ui-widget-header'>\n" . $clang->gT("X-Scale") . "</div>";
		}
	}
	
	$query = "SELECT * FROM ".db_table_name('questions')." WHERE parent_qid='{$qid}' AND language='{$baselang}' AND scale_id={$scale_id} ORDER BY question_order, title";
	$result = db_execute_assoc($query) or safe_die($connect->ErrorMsg()); //Checked
	$anscount = $result->RecordCount();
	
	$vasummary .= "<table class='answertable' id='answertable_{$baselang}_{$scale_id}' align='center'>\n"
		."<thead>"
		."<tr><th>&nbsp;</th>\n"
		."<th align='right'>".$clang->gT("Code")."</th>\n"
		."<th align='center'>".$clang->gT("Subquestion")."</th>\n";
	
	$vasummary .="<th align='center'>".$clang->gT("Action")."</th>\n";
		
	$vasummary .= "</tr></thead>"
		."<tbody align='center'>";
	
	$alternate = false;
	
	while ($row = $result->FetchRow()) {
		$row['title'] = htmlspecialchars($row['title']);
		$row['question']=htmlspecialchars($row['question']);

		$codeids = $codeids . ' ' . $row['question_order'];

		// Alternate colors
		$vasummary .= "<tr id='row_{$row['language']}_{$row['qid']}_{$row['scale_id']}'";
		if ($alternate == true) {
			$vasummary.=' class="highlight" ';
			$alternate = false;
		} else {
			$alternate = true;
		}
		$vasummary .=" ><td align='right'>\n";

		$vasummary .= "<img class='handle' src='$imageurl/handle.png' /></td>"
			. "<td><input type='hidden' class='oldcode' id='oldcode_{$row['qid']}_{$row['scale_id']}' name='oldcode_{$row['qid']}_{$row['scale_id']}' "
			. "value=\"{$row['title']}\" /><input type='text' id='code_{$row['qid']}_{$row['scale_id']}' class='code' "
			. "name='code_{$row['qid']}_{$row['scale_id']}' value=\"{$row['title']}\" maxlength='5' size='5'"
// 			." onkeypress=\" if(event.keyCode==13) {if (event && event.preventDefault) event.preventDefault(); document.getElementById('saveallbtn_$baselang').click(); return false;} return goodchars(event,'1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWZYZ_')\""
			." />";

		$vasummary .= "</td><td>\n"
			. "<input type='text' size='75' id='answer_{$row['language']}_{$row['qid']}_{$row['scale_id']}' "
			. "name='answer_{$row['language']}_{$row['qid']}_{$row['scale_id']}' value=\"{$row['question']}\" "
// 			. "onkeypress=\" if(event.keyCode==13) {if (event && event.preventDefault) event.preventDefault(); document.getElementById('saveallbtn_$baselang').click(); return false;}\" "
			. "/>\n</td>\n"
			. "<td>\n";

		$vasummary.="<img src='$imageurl/addanswer.png' class='btnaddanswer' />";
		$vasummary.="<img src='$imageurl/deleteanswer.png' class='btndelanswer' />";

		$vasummary .= "</td></tr>\n";
		$position++;
	}
	
	$vasummary .= "</tbody></table>\n";

	++$anscount;

	// 		$vasummary .= "<button class='btnlsbrowser' id='btnlsbrowser_{$scale_id}' type='button'>".$clang->gT('Predefined label sets...')."</button>";
	// 		$vasummary .= "<button class='btnquickadd' id='btnquickadd_{$scale_id}' type='button'>".$clang->gT('Quick add...')."</button>";
}

$vasummary .= "</div>";

// Label set browser
// <br/><input type='checkbox' checked='checked' id='languagefilter' /><label for='languagefilter'>".$clang->gT('Match language')."</label>
// $vasummary .= "<div id='labelsetbrowser' style='display:none;'><div style='float:left; width:260px;'>
//                       <label for='labelsets'>".$clang->gT('Available label sets:')."</label>
//                       <br /><select id='labelsets' size='10' style='width:250px;'><option>&nbsp;</option></select>
//                       <br /><button id='btnlsreplace' type='button'>".$clang->gT('Replace')."</button>
//                       <button id='btnlsinsert' type='button'>".$clang->gT('Add')."</button>
//                       <button id='btncancel' type='button'>".$clang->gT('Cancel')."</button></div>
//                    <div id='labelsetpreview' style='float:right;width:500px;'></div></div> ";
// $vasummary .= "<div id='quickadd' style='display:none;'><div style='float:left;'>
//                       <label for='quickadd'>".$clang->gT('Enter your subquestions:')."</label>
//                       <br /><textarea id='quickaddarea' class='tipme' title='".$clang->gT('Enter one subquestion per line. You can provide a code by separating code and subquestion text with a semikolon or tab. For multilingual surveys you add the translation(s) on the same line separated with a semikolon/tab.')."' rows='30' style='width:570px;'></textarea>
//                       <br /><button id='btnqareplace' type='button'>".$clang->gT('Replace')."</button>
//                       <button id='btnqainsert' type='button'>".$clang->gT('Add')."</button>
//                       <button id='btnqacancel' type='button'>".$clang->gT('Cancel')."</button></div>
//                    </div> ";

$vasummary .= "<p>"
	."<input type='button' id='saveallbtn_$baselang' name='method' value='".$clang->gT("Save changes")."' "
	. "onClick='submitFormAsParent(editsubquestionsform);'"
	. " />\n";

$position = sprintf("%05d", $position);

$vasummary .= "</div></form>";


?>
