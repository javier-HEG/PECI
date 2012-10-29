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

include_once("login_check.php");  //Login Check dies also if the script is started directly

$js_admin_includes[]='../admin/scripts/answers.js';
$js_admin_includes[]='../scripts/jquery/jquery.blockUI.js';
$js_admin_includes[]='../scripts/jquery/jquery.selectboxes.min.js';

$surveyid = $_REQUEST["surveyid"];
$gid = $_REQUEST["gid"];
$qid = $_REQUEST["qid"];

$_SESSION['FileManagerContext'] = "edit:answer:$surveyid";

$baselang = GetBaseLanguageFromSurveyID($surveyid);

// The following line decides if the assessment input fields are visible or not
// for some question types the assessment values is set in the label set instead of the answers
$qtypes = getqtypelist('','array');

$qquery = "SELECT type FROM ".db_table_name('questions')." WHERE qid=$qid AND language='".$baselang."'";
$qrow = $connect->GetRow($qquery);
$qtype = $qrow['type'];
$scalecount = $qtypes[$qtype]['answerscales'];

$sumquery1 = "SELECT * FROM ".db_table_name('surveys')." inner join ".db_table_name('surveys_languagesettings')." on (surveyls_survey_id=sid and surveyls_language=language) WHERE sid=$surveyid"; //Getting data for this survey
$sumresult1 = db_select_limit_assoc($sumquery1, 1) ;
if ($sumresult1->RecordCount() == 0) {
	die('Invalid survey id');
}
$surveyinfo = $sumresult1->FetchRow();
$surveyinfo = array_map('FlattenText', $surveyinfo);

$assessmentvisible = ($surveyinfo['assessments']=='Y' && $qtypes[$qtype]['assessable']==1);

// Insert some Javascript variables
// Language count is set to 1 because only the baselanguage is used
$vasummary = "\n<script type='text/javascript'>
		var languagecount=1;\n
		var scalecount=$scalecount;
		var assessmentvisible=".($assessmentvisible?'true':'false').";
		var newansweroption_text='".$clang->gT('New answer option','js')."';
		var strcode='".$clang->gT('Code','js')."';
		var strlabel='".$clang->gT('Label','js')."';
		var strCantDeleteLastAnswer='".$clang->gT('You cannot delete the last answer option.','js')."';
		var lsbrowsertitle='".$clang->gT('Label set browser','js')."';
		var quickaddtitle='".$clang->gT('Quick-add answers','js')."';
		var sAssessmentValue='".$clang->gT('Assessment value','js')."';
		var duplicateanswercode='".$clang->gT('Error: You are trying to use duplicate answer codes.','js')."';
		var langs='$baselang';
	</script>\n";

//Check if there is at least one answer
for ($i = 0; $i < $scalecount; $i++) {
	$qquery = "SELECT count(*) as num_ans  FROM ".db_table_name('answers')." WHERE qid=$qid AND scale_id=$i AND language='".$baselang."'";
	$qresult = $connect->GetOne($qquery); //Checked
	if ($qresult==0)
	{
		$query="INSERT into ".db_table_name('answers')." (qid,code,answer,language,sortorder,scale_id) VALUES ($qid,'A1',".db_quoteall($clang->gT("Some example answer option")).",'$baselang',0,$i)";
		$connect->execute($query);
	}
}

if (!isset($_POST['ansaction'])) {
	//check if any nulls exist. If they do, redo the sortorders
	$caquery="SELECT * FROM ".db_table_name('answers')." WHERE qid=$qid AND sortorder is null AND language='".$baselang."'";
	$caresult=$connect->Execute($caquery); //Checked
	$cacount=$caresult->RecordCount();
	if ($cacount)
	{
		fixsortorderAnswers($qid);
	}
}

$query = "SELECT sortorder FROM ".db_table_name('answers')." WHERE qid='{$qid}' AND language='".GetBaseLanguageFromSurveyID($surveyid)."' ORDER BY sortorder desc";
$result = db_execute_assoc($query) or safe_die($connect->ErrorMsg()); //Checked
$anscount = $result->RecordCount();
$row=$result->FetchRow();
$maxsortorder=$row['sortorder']+1;

$vasummary .= "<div class='header ui-widget-header'>\n"
	.$clang->gT("Edit answer options")
	."</div>\n"
	."<form id='editanswersform' name='editanswersform' method='post' action='$scriptname'>\n"
	. "<input type='hidden' name='sid' value='$surveyid' />\n"
	. "<input type='hidden' name='gid' value='$gid' />\n"
	. "<input type='hidden' name='qid' value='$qid' />\n"
	. "<input type='hidden' name='action' value='updateansweroptions' />\n"
	. "<input type='hidden' name='checksessionbypost' value='{$_SESSION['checksessionpost']}' />\n"
	. "<input type='hidden' name='sortorder' value='' />\n";
	$vasummary .= "<div class='tab-pane' id='tab-pane-answers-$surveyid'>";

	$first = true;

$vasummary .= "<div id='xToolbar'></div>\n";

$vasummary .= "<div class='tab-page' id='tabpage_$baselang'>"
	. "<h2 class='tab' style='display:none;'>".getLanguageNameFromCode($baselang, false)
	. "</h2>";

for ($scale_id = 0; $scale_id < $scalecount; $scale_id++) {
	$position=0;
	if ($scalecount>1) {
		$vasummary.="<div class='header ui-widget-header' style='margin-top:5px;'>".sprintf($clang->gT("Answer scale %s"),$scale_id+1)."</div>";
	}


	$vasummary .= "<table class='answertable' id='answers_{$baselang}_$scale_id' align='center' >\n"
	."<thead>"
	."<tr>\n"
	."<th align='right'>&nbsp;</th>\n"
	."<th align='center'>".$clang->gT("Code")."</th>\n";
	if ($assessmentvisible)
	{
		$vasummary .="<th align='center'>".$clang->gT("Assessment value");
	}
	else
	{
		$vasummary .="<th style='display:none;'>&nbsp;";
	}

	$vasummary .= "</th>\n"
	."<th align='center'>".$clang->gT("Answer option")."</th>\n"
	."<th align='center'>".$clang->gT("Actions")."</th>\n"
	."</tr></thead>"
	."<tbody align='center'>";
	$alternate=true;

	$query = "SELECT * FROM ".db_table_name('answers')." WHERE qid='{$qid}' AND language='{$baselang}' and scale_id=$scale_id ORDER BY sortorder, code";
	$result = db_execute_assoc($query) or safe_die($connect->ErrorMsg()); //Checked
	$anscount = $result->RecordCount();
	while ($row=$result->FetchRow())
	{
		$row['code'] = htmlspecialchars($row['code']);
		$row['answer']=htmlspecialchars($row['answer']);

		$vasummary .= "<tr class='row_$position ";
		if ($alternate==true)
		{
			$vasummary.='highlight';
		}
		$alternate=!$alternate;

		$vasummary .=" '><td align='right'>\n";

		if ($first)
		{
			$vasummary .= "<img class='handle' src='$imageurl/handle.png' /></td><td><input type='hidden' class='oldcode' id='oldcode_{$position}_{$scale_id}' name='oldcode_{$position}_{$scale_id}' value=\"{$row['code']}\" /><input type='text' class='code' id='code_{$position}_{$scale_id}' name='code_{$position}_{$scale_id}' value=\"{$row['code']}\" maxlength='5' size='5'"
			." onkeypress=\"return goodchars(event,'1234567890abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWZYZ_')\""
			." />";
		}
		else
		{
			$vasummary .= "&nbsp;</td><td>{$row['code']}";

		}

		$vasummary .= "</td>\n"
		."<td\n";

		if ($assessmentvisible && $first)
		{
			$vasummary .= "><input type='text' class='assessment' id='assessment_{$position}_{$scale_id}' name='assessment_{$position}_{$scale_id}' value=\"{$row['assessment_value']}\" maxlength='5' size='5'"
			." onkeypress=\"return goodchars(event,'-1234567890')\""
			." />";
		}
		elseif ( $first)
		{
			$vasummary .= " style='display:none;'><input type='hidden' class='assessment' id='assessment_{$position}_{$scale_id}' name='assessment_{$position}_{$scale_id}' value=\"{$row['assessment_value']}\" maxlength='5' size='5'"
			." onkeypress=\"return goodchars(event,'-1234567890')\""
			." />";
		}
		elseif ($assessmentvisible)
		{
			$vasummary .= '>'.$row['assessment_value'];
		}
		else
		{
			$vasummary .= " style='display:none;'>";
		}

		$vasummary .= "</td><td>\n"
			."<input type='text' class='answer' id='answer_{$row['language']}_{$row['sortorder']}_{$scale_id}' name='answer_{$row['language']}_{$row['sortorder']}_{$scale_id}' size='75' value=\"{$row['answer']}\" />\n";
// 		. getEditor("editanswer","answer_".$row['language']."_{$row['sortorder']}_{$scale_id}", "[".$clang->gT("Answer:", "js")."](".$row['language'].")",$surveyid,$gid,$qid,'editanswer');

		// Deactivate delete button for active surveys
		$vasummary .= "</td><td><img src='$imageurl/addanswer.png' class='btnaddanswer' />";
		$vasummary .= "<img src='$imageurl/deleteanswer.png' class='btndelanswer' />";

		$vasummary .= "</td></tr>\n";
		$position++;
	}
	$vasummary .='</table><br />';
	if ($first) {
		$vasummary .=  "<input type='hidden' id='answercount_{$scale_id}' name='answercount_{$scale_id}' value='$anscount' />\n";
	}
	
// 	$vasummary .= "<button id='btnlsbrowser_{$baselang}_{$scale_id}' class='btnlsbrowser' type='button'>".$clang->gT('Predefined label sets...')."</button>";
// 	$vasummary .= "<button id='btnquickadd_{$baselang}_{$scale_id}' class='btnquickadd' type='button'>".$clang->gT('Quick add...')."</button>";

}

$position=sprintf("%05d", $position);

$first=false;
$vasummary .= "</div>";

// Label set browser
//                      <br/><input type='checkbox' checked='checked' id='languagefilter' /><label for='languagefilter'>".$clang->gT('Match language')."</label>
// $vasummary .= "<div id='labelsetbrowser' style='display:none;'><div style='float:left;width:260px;'>
//                       <label for='labelsets'>".$clang->gT('Available label sets:')."</label>
//                       <br /><select id='labelsets' size='10' style='width:250px;'><option>&nbsp;</option></select>
//                       <br /><button id='btnlsreplace' type='button'>".$clang->gT('Replace')."</button>
//                       <button id='btnlsinsert' type='button'>".$clang->gT('Add')."</button>
//                       <button id='btncancel' type='button'>".$clang->gT('Cancel')."</button></div>

//                    <div id='labelsetpreview' style='float:right;width:500px;'></div></div> ";
// $vasummary .= "<div id='quickadd' style='display:none;'><div style='float:left;'>
//                       <label for='quickadd'>".$clang->gT('Enter your answers:')."</label>
//                       <br /><textarea id='quickaddarea' class='tipme' title='".$clang->gT('Enter one answer per line. You can provide a code by separating code and answer text with a semikolon or tab. For multilingual surveys you add the translation(s) on the same line separated with a semikolon/tab.')."' rows='30' style='width:570px;'></textarea>
//                       <br /><button id='btnqareplace' type='button'>".$clang->gT('Replace')."</button>
//                       <button id='btnqainsert' type='button'>".$clang->gT('Add')."</button>
//                       <button id='btnqacancel' type='button'>".$clang->gT('Cancel')."</button></div>
//                    </div> ";

// Save button

$vasummary .= "<p>"
	."<input type='button' id='saveallbtn_$baselang' name='method' value='".$clang->gT("Save changes")."' "
	. "onClick='submitFormAsParent(editanswersform);'"
	. " />\n";
$vasummary .= "</div></form>";

?>
