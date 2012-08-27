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

////////////////////////////////
//BEGIN Sanitizing POSTed data
////////////////////////////////
if (!isset($surveyid)) {
	$surveyid=returnglobal('sid');
}
if (!isset($qid)) {
	$qid=returnglobal('qid');
}
if (!isset($gid)) {
	$gid=returnglobal('gid');
}
if (!isset($p_cqid))
{
	$p_cqid=returnglobal('cqid');
	if ($p_cqid == '') $p_cqid=0; // we are not using another question as source of condition
}
if (!isset($p_cid)) {
	$p_cid=returnglobal('cid');
}
if (!isset($p_scenario)) {
	$p_scenario=returnglobal('scenario');
}
if (!isset($p_subaction)) {
	$p_subaction = returnglobal('subaction');
}
if (!isset($subaction)) {
	$subaction = returnglobal('subaction');
}

$extraGetParams ="";
if (isset($qid) && isset($gid))
{
	$extraGetParams="&amp;gid=$gid&amp;qid=$qid";
}

// this array will be used soon,
// to explain wich conditions is used to evaluate the question
$method = array(
	"<"  => $clang->gT("Less than"),
	"<=" => $clang->gT("Less than or equal to"),
	"==" => $clang->gT("equals"),
	"!=" => $clang->gT("Not equal to"),
	">=" => $clang->gT("Greater than or equal to"),
	">"  => $clang->gT("Greater than"),
	"RX" => $clang->gT("Regular expression")
);

$conditionsoutput_header = "<table width='100%' border='0' cellpadding='0' cellspacing='0'><tr><td>\n";

$conditionsoutput_menubar = ""; // will be defined later when we have enough information about the quhestions
$conditionsoutput_action_error = ""; // defined during the actions
$conditionsoutput_main_content = ""; // everything after the menubar

$markcidarray=Array();
if (isset($_GET['markcid']))
{
	$markcidarray=explode("-",$_GET['markcid']);
}


//BEGIN: GATHER INFORMATION
// 1: Get information for this question
$cquestions = Array();
$canswers = Array();
if (!isset($qid)) {
	$qid=returnglobal('qid');
}
if (!isset($surveyid)) {
	$surveyid=returnglobal('sid');
}
$thissurvey=getSurveyInfo($surveyid);

$query = "SELECT * "
."FROM {$dbprefix}questions, "
."{$dbprefix}groups "
."WHERE {$dbprefix}questions.gid={$dbprefix}groups.gid "
."AND qid=$qid "
."AND parent_qid=0 "
."AND {$dbprefix}questions.language='".GetBaseLanguageFromSurveyID($surveyid)."'" ;
$result = db_execute_assoc($query) or safe_die ("Couldn't get information for question $qid<br />$query<br />".$connect->ErrorMsg());
while ($rows=$result->FetchRow())
{
	$questiongroupname=$rows['group_name'];
	$questiontitle=$rows['title'];
	$questiontext=$rows['question'];
	$questiontype=$rows['type'];
}

// 2: Get all other questions that occur before this question that are pre-determined answer types

// To avoid natural sort order issues,
// first get all questions in natural sort order
// , and find out which number in that order this question is
$qquery = "SELECT * "
."FROM {$dbprefix}questions, "
."{$dbprefix}groups "
."WHERE {$dbprefix}questions.gid={$dbprefix}groups.gid "
."AND parent_qid=0 "
."AND {$dbprefix}questions.sid=$surveyid "
."AND {$dbprefix}questions.language='".GetBaseLanguageFromSurveyID($surveyid)."' "
."AND {$dbprefix}groups.language='".GetBaseLanguageFromSurveyID($surveyid)."' " ;

$qresult = db_execute_assoc($qquery) or safe_die ("$qquery<br />".$connect->ErrorMsg());
$qrows = $qresult->GetRows();
// Perform a case insensitive natural sort on group name then question title (known as "code" in the form) of a multidimensional array
usort($qrows, 'GroupOrderThenQuestionOrder');

$position="before";
// Go through each question until we reach the current one
foreach ($qrows as $qrow)
{
	if ($qrow["qid"] != $qid && $position=="before")
	{
		// remember all previous questions
		// all question types are supported.
		$questionlist[]=$qrow["qid"];
	}
	elseif ($qrow["qid"] == $qid)
	{
		break;
	}
}

// Now, using the same array which is now properly sorted by group then question
// Create an array of all the questions that appear AFTER the current one
$position = "before";
foreach ($qrows as $qrow) //Go through each question until we reach the current one
{
	if ($qrow["qid"] == $qid)
	{
		$position="after";
		//break;
	}
	elseif ($qrow["qid"] != $qid && $position=="after")
	{
		$postquestionlist[]=$qrow['qid'];
	}
}

$theserows=array();
$postrows=array();

if (isset($questionlist) && is_array($questionlist))
{
	foreach ($questionlist as $ql)
	{
		$query = "SELECT {$dbprefix}questions.qid, "
		."{$dbprefix}questions.sid, "
		."{$dbprefix}questions.gid, "
		."{$dbprefix}questions.question, "
		."{$dbprefix}questions.type, "
		."{$dbprefix}questions.title, "
		."{$dbprefix}questions.other, "
		."{$dbprefix}questions.mandatory "
		."FROM {$dbprefix}questions, "
		."{$dbprefix}groups "
		."WHERE {$dbprefix}questions.gid={$dbprefix}groups.gid "
		."AND parent_qid=0 "
		."AND {$dbprefix}questions.qid=$ql "
		."AND {$dbprefix}questions.language='".GetBaseLanguageFromSurveyID($surveyid)."' "
		."AND {$dbprefix}groups.language='".GetBaseLanguageFromSurveyID($surveyid)."'" ;

		$result=db_execute_assoc($query) or die("Couldn't get question $qid");

		$thiscount=$result->RecordCount();

		// And store again these questions in this array...
		while ($myrows=$result->FetchRow())
		{
			//key => value
			$theserows[]=array("qid"=>$myrows['qid'],
					"sid"=>$myrows['sid'],
					"gid"=>$myrows['gid'],
					"question"=>$myrows['question'],
					"type"=>$myrows['type'],
        	"mandatory"=>$myrows['mandatory'],
					"other"=>$myrows['other'],
					"title"=>$myrows['title']);
		}
	}
}

if (isset($postquestionlist) && is_array($postquestionlist))
{
	foreach ($postquestionlist as $pq)
	{
		$query = "SELECT q.qid, "
		."q.sid, "
		."q.gid, "
		."q.question, "
		."q.type, "
		."q.title, "
		."q.other, "
		."q.mandatory "
		."FROM {$dbprefix}questions q, "
		."{$dbprefix}groups g "
		."WHERE q.gid=g.gid AND "
		."q.parent_qid=0 AND "
		."q.qid=$pq AND "
		."q.language='".GetBaseLanguageFromSurveyID($surveyid)."' AND "
		."g.language='".GetBaseLanguageFromSurveyID($surveyid)."'";


		$result = db_execute_assoc($query) or safe_die("Couldn't get postquestions $qid<br />$query<br />".$connect->ErrorMsg());

		$postcount=$result->RecordCount();

		while($myrows=$result->FetchRow())
		{
			$postrows[]=array("qid"=>$myrows['qid'],
        		"sid"=>$myrows['sid'],
        		"gid"=>$myrows['gid'],
        			"question"=>$myrows['question'],
        		"type"=>$myrows['type'],
        		"mandatory"=>$myrows['mandatory'],
        		"other"=>$myrows['other'],
        		"title"=>$myrows['title']);
		} // while
	}
	$postquestionscount=count($postrows);
}

$questionscount=count($theserows);

if (isset($postquestionscount) && $postquestionscount > 0)
{
	//Build the array used for the questionNav and copyTo select boxes
	foreach ($postrows as $pr)
	{
		$pquestions[]=array("text"=>$pr['title'].": ".substr(strip_tags($pr['question']), 0, 80),
        		"fieldname"=>$pr['sid']."X".$pr['gid']."X".$pr['qid']);
	}
}

// Previous question parsing ==> building cquestions[] and canswers[]
if ($questionscount > 0)
{
	$X="X";

	foreach($theserows as $rows)
	{
		$shortquestion=$rows['title'].": ".strip_tags($rows['question']);

		if ($rows['type'] == "A" ||
		$rows['type'] == "B" ||
		$rows['type'] == "C" ||
		$rows['type'] == "E" ||
		$rows['type'] == "F" ||
		$rows['type'] == "H" )
		{
			$aquery="SELECT * "
			."FROM {$dbprefix}questions "
			."WHERE parent_qid={$rows['qid']} "
			."AND language='".GetBaseLanguageFromSurveyID($surveyid)."' "
			."ORDER BY question_order";

			$aresult=db_execute_assoc($aquery) or safe_die ("Couldn't get answers to Array questions<br />$aquery<br />".$connect->ErrorMsg());

			while ($arows = $aresult->FetchRow())
			{
				$shortanswer = "{$arows['title']}: [" . FlattenText($arows['question']) . "]";
				$shortquestion=$rows['title'].":$shortanswer ".FlattenText($rows['question']);
				$cquestions[]=array($shortquestion, $rows['qid'], $rows['type'], $rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title']);

				switch ($rows['type'])
				{
					case "A": //Array 5 buttons
						for ($i=1; $i<=5; $i++)
						{
							$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], $i, $i);
						}
						break;
					case "B": //Array 10 buttons
						for ($i=1; $i<=10; $i++)
						{
							$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], $i, $i);
						}
						break;
					case "C": //Array Y/N/NA
						$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], "Y", $clang->gT("Yes"));
						$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], "U", $clang->gT("Uncertain"));
						$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], "N", $clang->gT("No"));
						break;
					case "E": //Array >/=/<
						$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], "I", $clang->gT("Increase"));
						$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], "S", $clang->gT("Same"));
						$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], "D", $clang->gT("Decrease"));
						break;
					case "F": //Array Flexible Row
					case "H": //Array Flexible Column
						$fquery = "SELECT * "
						."FROM {$dbprefix}answers "
						."WHERE qid={$rows['qid']} "
						."AND language='".GetBaseLanguageFromSurveyID($surveyid)."' "
						."AND scale_id=0 "
						."ORDER BY sortorder, code ";
						$fresult = db_execute_assoc($fquery);
						while ($frow=$fresult->FetchRow())
						{
							$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], $frow['code'], $frow['answer']);
						}
						break;
				}
				// Only Show No-Answer if question is not mandatory
				if ($rows['mandatory'] != 'Y')
				{
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], "", $clang->gT("No answer"));
				}

			} //while
		}
		elseif ($rows['type'] == ":" || $rows['type'] == ";")
		{
			// Multiflexi

			//Get question attribute for $canswers
			$qidattributes=getQuestionAttributes($rows['qid'], $rows['type']);
			if (isset($qidattributes['multiflexible_max']) && trim($qidattributes['multiflexible_max'])!='') {
				$maxvalue=$qidattributes['multiflexible_max'];
			} else {
				$maxvalue=10;
			}
			if (isset($qidattributes['multiflexible_min']) && trim($qidattributes['multiflexible_min'])!='') {
				$minvalue=$qidattributes['multiflexible_min'];
			} else {
				$minvalue=1;
			}
			if (isset($qidattributes['multiflexible_step']) && trim($qidattributes['multiflexible_step'])!='') {
				$stepvalue=$qidattributes['multiflexible_step'];
			} else {
				$stepvalue=1;
			}

			if (isset($qidattributes['multiflexible_checkbox']) && $qidattributes['multiflexible_checkbox']!=0) {
				$minvalue=0;
				$maxvalue=1;
				$stepvalue=1;
			}
			// Get the Y-Axis

			$fquery = "SELECT sq.*, q.other"
			." FROM ".db_table_name('questions')." sq, ".db_table_name('questions')." q"
			." WHERE sq.sid=$surveyid AND sq.parent_qid=q.qid "
			. "AND q.language='".GetBaseLanguageFromSurveyID($surveyid)."'"
			." AND sq.language='".GetBaseLanguageFromSurveyID($surveyid)."'"
			." AND q.qid={$rows['qid']}
        		AND sq.scale_id=0
               ORDER BY sq.question_order";

			$y_axis_db = db_execute_assoc($fquery);

			// Get the X-Axis
			$aquery = "SELECT sq.*
                         FROM ".db_table_name('questions')." q, ".db_table_name('questions')." sq
                         WHERE q.sid=$surveyid
                         AND sq.parent_qid=q.qid
                         AND q.language='".GetBaseLanguageFromSurveyID($surveyid)."'
        		AND sq.language='".GetBaseLanguageFromSurveyID($surveyid)."'
        		AND q.qid=".$rows['qid']."
                         AND sq.scale_id=1
                         ORDER BY sq.question_order";

			$x_axis_db=db_execute_assoc($aquery) or safe_die ("Couldn't get answers to Array questions<br />$aquery<br />".$connect->ErrorMsg());

			while ($frow=$x_axis_db->FetchRow())
			{
				$x_axis[$frow['title']]=$frow['question'];
			}

			while ($arows = $y_axis_db->FetchRow())
			{
				foreach($x_axis as $key=>$val)
				{
					$shortquestion=$rows['title'].":{$arows['title']}:$key: [".strip_tags($arows['question']). "][" .strip_tags($val). "] " . FlattenText($rows['question']);
					$cquestions[]=array($shortquestion, $rows['qid'], $rows['type'], $rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title']."_".$key);

					if ($rows['type'] == ":")
					{
						for($ii=$minvalue; $ii<=$maxvalue; $ii+=$stepvalue)
						{
							$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], $ii, $ii);
						}
					}
				}
			}
			unset($x_axis);
		} //if A,B,C,E,F,H
		elseif ($rows['type'] == "1") //Multi Scale
		{
			$aquery="SELECT * "
			."FROM {$dbprefix}questions "
			."WHERE parent_qid={$rows['qid']} "
			."AND language='".GetBaseLanguageFromSurveyID($surveyid)."' "
			."ORDER BY question_order";
			$aresult=db_execute_assoc($aquery) or safe_die ("Couldn't get answers to Array questions<br />$aquery<br />".$connect->ErrorMsg());

			while ($arows = $aresult->FetchRow())
			{
				$attr = getQuestionAttributes($rows['qid']);
				$label1 = isset($attr['dualscale_headerA']) ? $attr['dualscale_headerA'] : 'Label1';
				$label2 = isset($attr['dualscale_headerB']) ? $attr['dualscale_headerB'] : 'Label2';
				$shortanswer = "{$arows['title']}: [" . strip_tags($arows['question']) . "][$label1]";
				$shortquestion=$rows['title'].":$shortanswer ".strip_tags($rows['question']);
				$cquestions[]=array($shortquestion, $rows['qid'], $rows['type'], $rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title']."#0");

				$shortanswer = "{$arows['title']}: [" . strip_tags($arows['question']) . "][$label2]";
				$shortquestion=$rows['title'].":$shortanswer ".strip_tags($rows['question']);
				$cquestions[]=array($shortquestion, $rows['qid'], $rows['type'], $rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title']."#1");

				// first label
				$lquery="SELECT * "
				."FROM {$dbprefix}answers "
				."WHERE qid={$rows['qid']} "
				."AND scale_id=0 "
				."AND language='".GetBaseLanguageFromSurveyID($surveyid)."' "
				."ORDER BY sortorder, answer";
				$lresult=db_execute_assoc($lquery) or safe_die ("Couldn't get labels to Array <br />$lquery<br />".$connect->ErrorMsg());
				while ($lrows = $lresult->FetchRow())
				{
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title']."#0", "{$lrows['code']}", "{$lrows['code']}");
				}

				// second label
				$lquery="SELECT * "
				."FROM {$dbprefix}answers "
				."WHERE qid={$rows['qid']} "
				."AND scale_id=1 "
				."AND language='".GetBaseLanguageFromSurveyID($surveyid)."' "
				."ORDER BY sortorder, answer";
				$lresult=db_execute_assoc($lquery) or safe_die ("Couldn't get labels to Array <br />$lquery<br />".$connect->ErrorMsg());
				while ($lrows = $lresult->FetchRow())
				{
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title']."#1", "{$lrows['code']}", "{$lrows['code']}");
				}

				// Only Show No-Answer if question is not mandatory
				if ($rows['mandatory'] != 'Y')
				{
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title']."#0", "", $clang->gT("No answer"));
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title']."#1", "", $clang->gT("No answer"));
				}
			} //while
		}
		elseif ($rows['type'] == "K" ||$rows['type'] == "Q") //Multi shorttext/numerical
		{
			$aquery="SELECT * "
			."FROM {$dbprefix}questions "
			."WHERE parent_qid={$rows['qid']} "
			."AND language='".GetBaseLanguageFromSurveyID($surveyid)."' "
			."ORDER BY question_order";
			$aresult=db_execute_assoc($aquery) or safe_die ("Couldn't get answers to Array questions<br />$aquery<br />".$connect->ErrorMsg());

			while ($arows = $aresult->FetchRow())
			{
				$shortanswer = "{$arows['title']}: [" . strip_tags($arows['question']) . "]";
				$shortquestion=$rows['title'].":$shortanswer ".strip_tags($rows['question']);
				$cquestions[]=array($shortquestion, $rows['qid'], $rows['type'], $rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title']);

				// Only Show No-Answer if question is not mandatory
				if ($rows['mandatory'] != 'Y')
				{
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], "", $clang->gT("No answer"));
				}

			} //while
		}
		elseif ($rows['type'] == "R") //Answer Ranking
		{
			$aquery="SELECT * "
			."FROM {$dbprefix}answers "
			."WHERE qid={$rows['qid']} "
			."AND ".db_table_name('answers').".language='".GetBaseLanguageFromSurveyID($surveyid)."' "
			."AND scale_id=0 "
			."ORDER BY sortorder, answer";
			$aresult=db_execute_assoc($aquery) or safe_die ("Couldn't get answers to Ranking question<br />$aquery<br />".$connect->ErrorMsg());
			$acount=$aresult->RecordCount();
			while ($arow=$aresult->FetchRow())
			{
				$theanswer = addcslashes($arow['answer'], "'");
				$quicky[]=array($arow['code'], $theanswer);
			}
			for ($i=1; $i<=$acount; $i++)
			{
				$cquestions[]=array("{$rows['title']}: [RANK $i] ".strip_tags($rows['question']), $rows['qid'], $rows['type'], $rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$i);
				foreach ($quicky as $qck)
				{
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$i, $qck[0], $qck[1]);
				}
				// Only Show No-Answer if question is not mandatory
				if ($rows['mandatory'] != 'Y')
				{
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$i, " ", $clang->gT("No answer"));
				}
			}
			unset($quicky);
		} // End if type R
		elseif($rows['type'] == "M" || $rows['type'] == "P")
		{
			$shortanswer = " [".$clang->gT("Group of checkboxes")."]";
			$shortquestion=$rows['title'].":$shortanswer ".strip_tags($rows['question']);
			$cquestions[]=array($shortquestion, $rows['qid'], $rows['type'], $rows['sid'].$X.$rows['gid'].$X.$rows['qid']);
			$aquery="SELECT * "
			."FROM {$dbprefix}questions "
			."WHERE parent_qid={$rows['qid']} "
			."AND language='".GetBaseLanguageFromSurveyID($surveyid)."' "
			."ORDER BY question_order";
			$aresult=db_execute_assoc($aquery) or safe_die ("Couldn't get answers to this question<br />$aquery<br />".$connect->ErrorMsg());

			while ($arows=$aresult->FetchRow())
			{
				$theanswer = addcslashes($arows['question'], "'");
				$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], $arows['title'], $theanswer);

				$shortanswer = "{$arows['title']}: [" . strip_tags($arows['question']) . "]";
				$shortanswer .= "[".$clang->gT("Single checkbox")."]";
				$shortquestion=$rows['title'].":$shortanswer ".strip_tags($rows['question']);
				$cquestions[]=array($shortquestion, $rows['qid'], $rows['type'], "+".$rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title']);
				$canswers[]=array("+".$rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], 'Y', $clang->gT("checked"));
				$canswers[]=array("+".$rows['sid'].$X.$rows['gid'].$X.$rows['qid'].$arows['title'], '', $clang->gT("not checked"));
			}
		}
		elseif($rows['type'] == "X") //Boilerplate question
		{
			//Just ignore this questiontype
		}
		else
		{
			$cquestions[]=array($shortquestion, $rows['qid'], $rows['type'], $rows['sid'].$X.$rows['gid'].$X.$rows['qid']);
			switch ($rows['type'])
			{
				case "Y": // Y/N/NA
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], "Y", $clang->gT("Yes"));
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], "N", $clang->gT("No"));
					// Only Show No-Answer if question is not mandatory
					if ($rows['mandatory'] != 'Y')
					{
						$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], " ", $clang->gT("No answer"));
					}
					break;
				case "G": //Gender
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], "F", $clang->gT("Female"));
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], "M", $clang->gT("Male"));
					// Only Show No-Answer if question is not mandatory
					if ($rows['mandatory'] != 'Y')
					{
						$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], " ", $clang->gT("No answer"));
					}
					break;
				case "5": // 5 choice
					for ($i=1; $i<=5; $i++)
					{
						$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], $i, $i);
					}
					// Only Show No-Answer if question is not mandatory
					if ($rows['mandatory'] != 'Y')
					{
						$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], " ", $clang->gT("No answer"));
					}
					break;

				case "N": // Simple Numerical questions

					// Only Show No-Answer if question is not mandatory
					if ($rows['mandatory'] != 'Y')
					{
						$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], " ", $clang->gT("No answer"));
					}
					break;

				default:
					$aquery="SELECT * "
				."FROM {$dbprefix}answers "
				."WHERE qid={$rows['qid']} "
				."AND language='".GetBaseLanguageFromSurveyID($surveyid)."' "
				."AND scale_id=0 "
				."ORDER BY sortorder, "
				."answer";
				// Ranking question? Replacing "Ranking" by "this"
				$aresult=db_execute_assoc($aquery) or safe_die ("Couldn't get answers to this question<br />$aquery<br />".$connect->ErrorMsg());

				while ($arows=$aresult->FetchRow())
				{
					$theanswer = addcslashes($arows['answer'], "'");
					$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], $arows['code'], $theanswer);
				}
				if ($rows['type'] == "D")
				{
					// Only Show No-Answer if question is not mandatory
					if ($rows['mandatory'] != 'Y')
					{
						$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], " ", $clang->gT("No answer"));
					}
				}
				elseif ($rows['type'] != "M" &&
				$rows['type'] != "P" &&
				$rows['type'] != "J" &&
				$rows['type'] != "I" )
				{
					// For dropdown questions
					// optinnaly add the 'Other' answer
					if ( ($rows['type'] == "L" ||
					$rows['type'] == "!") &&
					$rows['other'] == "Y")
					{
						$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], "-oth-", $clang->gT("Other"));
					}

					// Only Show No-Answer if question is not mandatory
					if ($rows['mandatory'] != 'Y')
					{
						$canswers[]=array($rows['sid'].$X.$rows['gid'].$X.$rows['qid'], " ", $clang->gT("No answer"));
					}
				}
				break;
			}//switch row type
		} //else
	} //foreach theserows
} //if questionscount > 0
//END Gather Information for this question

$conditionsoutput_main_content .= "\t<tr>\n"
."<td align='center'>\n";

// Now we have enough information, we can create the menubar and question Navigator
$conditionsoutput_menubar .= "\t<div class='menubar'>"
."<div class='menubar-title ui-widget-header'>"
."<strong>".$clang->gT("Conditions designer").":</strong> "
."</div>\n";
$conditionsoutput_menubar .= "\t<div class='menubar-main'>\n"
."<div class='menubar-left'>\n"
."<a href=\"#\" onclick=\"window.open('$scriptname?sid=$surveyid$extraGetParams', '_top')\" title='".$clang->gTview("Return to survey administration")."'>"
."<img name='HomeButton' src='$imageurl/home.png' alt='".$clang->gT("Return to survey administration")."' /></a>\n"
."<img src='$imageurl/blank.gif' alt='' width='11' />\n"
."<img src='$imageurl/seperator.gif' alt='' />\n"
."<a href=\"#\" onclick=\"window.open('$scriptname?action=conditions&amp;sid=$surveyid&amp;gid=$gid&amp;qid=$qid', '_top')\" title='".$clang->gTview("Show conditions for this question")."' >"
."<img name='SummaryButton' src='$imageurl/summary.png' alt='".$clang->gT("Show conditions for this question")."' /></a>\n"
."<img src='$imageurl/seperator.gif' alt='' />\n"
."<a href=\"#\" onclick=\"window.open('$scriptname?action=conditions&amp;sid=$surveyid&amp;gid=$gid&amp;qid=$qid&amp;subaction=editconditionsform', '_top')\" title='".$clang->gTview("Add and edit conditions")."' >"
."<img name='ConditionAddButton' src='$imageurl/conditions_add.png' alt='".$clang->gT("Add and edit conditions")."' /></a>\n"
."<a href=\"#\" onclick=\"window.open('$scriptname?action=conditions&amp;sid=$surveyid&amp;gid=$gid&amp;qid=$qid&amp;subaction=copyconditionsform', '_top')\" title='".$clang->gTview("Copy conditions")."' >"
."<img name='ConditionCopyButton' src='$imageurl/conditions_copy.png' alt='".$clang->gT("Copy conditions")."' /></a>\n";



$quesitonNavOptions = "<optgroup class='activesurveyselect' label='".$clang->gT("Before","js")."'>";
foreach ($theserows as $row)
{
	$question=$row['question'];
	$question=strip_tags($question);
	if (strlen($question)<35)
	{
		$questionselecter = $question;
	}
	else
	{
		//$questionselecter = substr($question, 0, 35)."..";
		$questionselecter = htmlspecialchars(mb_strcut(html_entity_decode($question,ENT_QUOTES,'UTF-8'), 0, 35, 'UTF-8'))."...";
	}
	$quesitonNavOptions .= "<option value='$scriptname?sid=$surveyid&amp;gid={$row['gid']}&amp;qid={$row['qid']}&amp;action=conditions&amp;subaction=editconditionsform'>{$row['title']}: ".$questionselecter."</option>";
}
$quesitonNavOptions .= "</optgroup>\n";
$quesitonNavOptions .= "<optgroup class='activesurveyselect' label='".$clang->gT("Current","js")."'>\n";
$question=strip_tags($questiontext);
if (strlen($question)<35)
{
	$questiontextshort = $question;
}
else
{
	//$questiontextshort = substr($question, 0, 35)."..";
	$questiontextshort = htmlspecialchars(mb_strcut(html_entity_decode($question,ENT_QUOTES,'UTF-8'), 0, 35, 'UTF-8'))."...";
}
$quesitonNavOptions .= "<option value='$scriptname?sid=$surveyid&amp;gid=$gid&amp;qid=$qid&amp;action=conditions&amp;subaction=editconditionsform' selected='selected'>$questiontitle: $questiontextshort</option>";
$quesitonNavOptions .= "</optgroup>\n";
$quesitonNavOptions .= "<optgroup class='activesurveyselect' label='".$clang->gT("After","js")."'>\n";
foreach ($postrows as $row)
{
	$question=$row['question'];
	$question=strip_tags($question);
	if (strlen($question)<35)
	{
		$questionselecter = $question;
	}
	else
	{
		//$questionselecter = substr($question, 0, 35)."..";
		$questionselecter = htmlspecialchars(mb_strcut(html_entity_decode($question,ENT_QUOTES,'UTF-8'), 0, 35, 'UTF-8'))."...";
	}
	$quesitonNavOptions .=  "<option value='$scriptname?sid=$surveyid&amp;gid={$row['gid']}&amp;qid={$row['qid']}&amp;action=conditions&amp;subaction=editconditionsform'>{$row['title']}: ".$questionselecter."</option>";
}
$quesitonNavOptions .= "</optgroup>\n";

$conditionsoutput_menubar .="\t</div><div class='menubar-right'>\n"
."<img width=\"11\" alt=\"\" src=\"$imageurl/blank.gif\"/>\n"
."<font class=\"boxcaption\">".$clang->gT("Questions").":</font>\n"
."<select id='questionNav' onchange=\"window.open(this.options[this.selectedIndex].value,'_top')\">$quesitonNavOptions</select>\n"
."<img hspace=\"0\" border=\"0\" alt=\"\" src=\"$imageurl/seperator.gif\"/>\n"
."<a href=\"http://docs.limesurvey.org\" target='_blank' title=\"".$clang->gTview("LimeSurvey online manual")."\">"
."<img src='$imageurl/showhelp.png' name='ShowHelp' title=''"
."alt='". $clang->gT("LimeSurvey online manual")."' /></a>";


$conditionsoutput_menubar .= "\t</div></div></div>\n"
."<p style='margin: 0pt; font-size: 1px; line-height: 1px; height: 1px;'> </p>"
."</td></tr>\n";

//Now display the information and forms
//BEGIN: PREPARE JAVASCRIPT TO SHOW MATCHING ANSWERS TO SELECTED QUESTION
$conditionsoutput_main_content .= "<script type='text/javascript'>\n"
."<!--\n"
."\tvar Fieldnames = new Array();\n"
."\tvar Codes = new Array();\n"
."\tvar Answers = new Array();\n"
."\tvar QFieldnames = new Array();\n"
."\tvar Qcqids = new Array();\n"
."\tvar Qtypes = new Array();\n";
$jn=0;
if (isset($canswers))
{
	foreach($canswers as $can)
	{
		$an=json_encode(FlattenText($can[2]));
		$conditionsoutput_main_content .= "Fieldnames[$jn]='$can[0]';\n"
		."Codes[$jn]='$can[1]';\n"
		."Answers[$jn]={$an};\n";
		$jn++;
	}
}
$jn=0;

if (isset($cquestions))
{
	foreach ($cquestions as $cqn)
	{
		$conditionsoutput_main_content .= "QFieldnames[$jn]='$cqn[3]';\n"
		."Qcqids[$jn]='$cqn[1]';\n"
		."Qtypes[$jn]='$cqn[2]';\n";
		$jn++;
	}
}

//  record a JS variable to let jQuery know if survey is Anonymous
if ($thissurvey['anonymized'] == 'Y')
{
	$conditionsoutput_main_content .= "isAnonymousSurvey = true;";
}
else
{
	$conditionsoutput_main_content .= "isAnonymousSurvey = false;";
}

$conditionsoutput_main_content .= "//-->\n"
."</script>\n";

$conditionsoutput_main_content .= "</td></tr>\n";
//END: PREPARE JAVASCRIPT TO SHOW MATCHING ANSWERS TO SELECTED QUESTION

// Create form for conditions removal
$conditionsoutput_main_content .= "<form id='deleteallconditions' action='$scriptname?action=conditions' method='post' name='deleteallconditions' style='margin-bottom:0;'>\n"
	. "<input type='hidden' name='checksessionbypost' value='{$_SESSION['checksessionpost']}' />\n"
	. "<input type='hidden' name='action' value='conditions' />\n"
	. "<input type='hidden' name='qid' value='$qid' />\n"
	. "<input type='hidden' name='gid' value='$gid' />\n"
	. "<input type='hidden' name='sid' value='$surveyid' />\n"
	. "<input type='hidden' id='toplevelsubaction' name='subaction' value='deleteallconditions' />\n"
	. "</form>";

// Separator
$conditionsoutput_main_content .= "\t<tr bgcolor='#555555'><td colspan='3'></td></tr>\n";



if ( isset($cquestions) )
{
	if ( count($cquestions) > 0 && count($cquestions) <=10)
	{
		$qcount = count($cquestions);
	}
	else
	{
		$qcount = 9;
	}
}
else
{
	$qcount = 0;
}


//BEGIN: DISPLAY THE ADD or EDIT CONDITION FORM
if ($subaction == "editconditionsform" || $subaction == "insertcondition" ||
$subaction == "updatecondition" || $subaction == "deletescenario" ||
$subaction == "renumberscenarios" || $subaction == "deleteallconditions" ||
$subaction == "updatescenario" ||
$subaction == "editthiscondition" || $subaction == "delete")
{
	$conditionsoutput_main_content .= "<tr><td colspan='3'>\n";
	$conditionsoutput_main_content .= "<form action='$scriptname?action=conditions' name='editconditions' id='editconditions' method='post'>\n";
	if ($subaction == "editthiscondition" &&  isset($p_cid))
	{
		$mytitle = $clang->gT("Edit condition");
	}
	else
	{
		$mytitle = $clang->gT("Add condition");
	}
	$conditionsoutput_main_content .= "<div class='header ui-widget-header'>".$mytitle."</div>\n";

	///////////////////////////////////////////////////////////////////////////////////////////

	$scenarioInputStyle = "style = 'display: none;'";
	$conditionsoutput_main_content .= "<input type='text' name='scenario' id='scenario' value='1' size='2' $scenarioInputStyle/>";
	
	$conditionsoutput_main_content .= "<div class='condition-tbl-row'>\n";

	// Begin "Question" row
	$conditionsoutput_main_content .="<div class='condition-tbl-row'>\n"
	."<div class='condition-tbl-left'>".$clang->gT("Question")."</div>\n"
	."<div class='condition-tbl-right'>\n"
	."\t<div id=\"conditionsource\" class=\"tabs-nav\">\n"
	."\t<ul>\n"
	."\t<li><a href=\"#SRCPREVQUEST\"><span>".$clang->gT("Previous questions")."</span></a></li>\n"
 	."\t<li style=\"display: none;\"><a href=\"#SRCTOKENATTRS\"><span>".$clang->gT("Token fields")."</span></a></li>\n"
	."\t</ul>\n";

	// Previous question tab
	$conditionsoutput_main_content .= "<div id='SRCPREVQUEST'><select name='cquestions' id='cquestions' size='".($qcount+1)."' >\n";
	if (isset($cquestions))
	{
		$js_getAnswers_onload = "";
		foreach ($cquestions as $cqn)
		{
			$conditionsoutput_main_content .= "<option value='$cqn[3]' title=\"".htmlspecialchars($cqn[0])."\"";
			if (isset($p_cquestions) && $cqn[3] == $p_cquestions) {
				$conditionsoutput_main_content .= " selected";
				if (isset($p_canswers))
				{
					$canswersToSelect = "";
					foreach ($p_canswers as $checkval)
					{
						$canswersToSelect .= ";$checkval";
					}
					$canswersToSelect = substr($canswersToSelect,1);
					$js_getAnswers_onload .= "$('#canswersToSelect').val('$canswersToSelect');\n";
				}
			}
			$conditionsoutput_main_content .= ">$cqn[0]</option>\n";
		}
	}

	$conditionsoutput_main_content .= "</select>\n"
	."</div>\n";

	// Source token Tab
	$conditionsoutput_main_content .= "<div  style=\"display: none;\" id='SRCTOKENATTRS'><select name='csrctoken' id='csrctoken' size='".($qcount+1)."' >\n";
	foreach (GetTokenFieldsAndNames($surveyid) as $tokenattr => $tokenattrName)
	{
		// Check to select
		if (isset($p_csrctoken) && $p_csrctoken == '{TOKEN:'.strtoupper($tokenattr).'}')
		{
			$selectThisSrcTokenAttr = "selected=\"selected\"";
		}
		else
		{
			$selectThisSrcTokenAttr = "";
		}
		$conditionsoutput_main_content .= "<option value='{TOKEN:".strtoupper($tokenattr)."}' $selectThisSrcTokenAttr>".html_escape($tokenattrName)."</option>\n";
	}

	$conditionsoutput_main_content .= "</select>\n"
	."</div>\n\n";

	$conditionsoutput_main_content .= "\t</div>\n"; // end conditionsource div

	$conditionsoutput_main_content .= "</div>\n"
	."</div>\n";

	// Begin "Comparison operator" row
	$conditionsoutput_main_content .="<div class='condition-tbl-row'>\n"
	."<div class='condition-tbl-left'>".$clang->gT("Comparison operator")."</div>\n"
	."<div class='condition-tbl-right'>\n"
	."<select name='method' id='method' style='font-family:verdana; font-size:10' >\n";
	foreach ($method as $methodCode => $methodTxt)
	{
		$selected=$methodCode=="==" ? " selected='selected'" : "";
		$conditionsoutput_main_content .= "\t<option value='".$methodCode."'$selected>".$methodTxt."</option>\n";
	}
	
	$conditionsoutput_main_content .="</select>\n"
	."</div>\n"
	."</div>\n";

	// Begin "Answer" row
	$conditionsoutput_main_content .="<div class='condition-tbl-row'>\n"
	."<div class='condition-tbl-left'>".$clang->gT("Answer")."</div>\n";

	if ($subaction == "editthiscondition")
	{
		$multipletext = "";
		if (isset($_GET['EDITConditionConst']) && $_GET['EDITConditionConst'] != '')
		{
			$EDITConditionConst=html_escape($_GET['EDITConditionConst']);
		}
		else
		{
			$EDITConditionConst="";
		}
		if (isset($_GET['EDITConditionRegexp']) && $_GET['EDITConditionRegexp'] != '')
		{
			$EDITConditionRegexp=html_escape($_GET['EDITConditionRegexp']);
		}
		else
		{
			$EDITConditionRegexp="";
		}
	}
	else
	{
		$multipletext = "multiple";
		if (isset($_GET['ConditionConst']) && $_GET['ConditionConst'] != '')
		{
			$EDITConditionConst=html_escape($_GET['ConditionConst']);
		}
		else
		{
			$EDITConditionConst="";
		}
		if (isset($_GET['ConditionRegexp']) && $_GET['ConditionRegexp'] != '')
		{
			$EDITConditionRegexp=html_escape($_GET['ConditionRegexp']);
		}
		else
		{
			$EDITConditionRegexp="";
		}
	}


	$conditionsoutput_main_content .= ""
	."<div class='condition-tbl-right'>\n"
	."<div id=\"conditiontarget\" class=\"tabs-nav\">\n"
	."\t<ul>\n"
	."\t\t<li><a href=\"#CANSWERSTAB\"><span>".$clang->gT("Predefined")."</span></a></li>\n"
	."\t\t<li><a href=\"#CONST\"><span>".$clang->gT("Constant")."</span></a></li>\n"
	."\t\t<li><a href=\"#PREVQUESTIONS\"><span>".$clang->gT("Questions")."</span></a></li>\n"
 	."\t\t<li style=\"display: none;\"><a href=\"#TOKENATTRS\"><span>".$clang->gT("Token fields")."</span></a></li>\n"
	."\t\t<li><a href=\"#REGEXP\"><span>".$clang->gT("RegExp")."</span></a></li>\n"
	."\t</ul>\n";

	// Predefined answers tab
	$conditionsoutput_main_content .= "\t<div id='CANSWERSTAB'>\n"
	."\t\t<select  name='canswers[]' $multipletext id='canswers' size='7'>\n"
	."\t\t</select>\n"
	."\t\t<br /><span id='canswersLabel'>".$clang->gT("Predefined answer options for this question")."</span>\n"
	."\t</div>\n";

	// Constant tab
	$conditionsoutput_main_content .= "\t<div id='CONST' style='display:' >\n"
	."\t\t<textarea name='ConditionConst' id='ConditionConst' rows='5' cols='113'>$EDITConditionConst</textarea>\n"
	."\t\t<br /><div id='ConditionConstLabel'>".$clang->gT("Constant value")."</div>\n"
	."\t</div>\n";
	// Previous answers tab @SGQA@ placeholders
	$conditionsoutput_main_content .= "\t<div id='PREVQUESTIONS'>\n"
	."\t\t<select name='prevQuestionSGQA' id='prevQuestionSGQA' size='7'>\n";
	foreach ($cquestions as $cqn)
	{
		// building the @SGQA@ placeholders options
		if ($cqn[2] != 'M' && $cqn[2] != 'P')
		{
			// Type M or P aren't real fieldnames and thus can't be used in @SGQA@ placehodlers
			$conditionsoutput_main_content .= "\t\t<option value='@$cqn[3]@' title=\"".htmlspecialchars($cqn[0])."\"";
			if (isset($p_prevquestionsgqa) && $p_prevquestionsgqa == "@".$cqn[3]."@")
			{
				$conditionsoutput_main_content .= " selected='selected'";
			}
			$conditionsoutput_main_content .= ">$cqn[0]</option>\n";
		}
	}
	$conditionsoutput_main_content .= "\t\t</select>\n"
	."\t\t<br /><span id='prevQuestionSGQALabel'>".$clang->gT("Answers from previous questions")."</span>\n"
	."\t</div>\n";

	// Token tab
	$conditionsoutput_main_content .= "\t<div style=\"display: none;\" id='TOKENATTRS'>\n"
	."\t\t<select name='tokenAttr' id='tokenAttr' size='7'>\n";
	foreach (GetTokenFieldsAndNames($surveyid) as $tokenattr => $tokenattrName)
	{
		$conditionsoutput_main_content .= "\t\t<option value='{TOKEN:".strtoupper($tokenattr)."}'>".html_escape($tokenattrName)."</option>\n";
	}

	$conditionsoutput_main_content .= "\t\t</select>\n"
	."\t\t<br /><span id='tokenAttrLabel'>".$clang->gT("Attributes values from the participant's token")."</span>\n"
	."\t</div>\n";

	// Regexp Tab
	$conditionsoutput_main_content .= "\t<div id='REGEXP' style='display:'>\n"
	."\t\t<textarea name='ConditionRegexp' id='ConditionRegexp' rows='5' cols='113'>$EDITConditionRegexp</textarea>\n"
	."\t\t<br /><div id='ConditionRegexpLabel'><a href=\"http://docs.limesurvey.org/tiki-index.php?page=Using+Regular+Expressions\" target=\"_blank\">".$clang->gT("Regular expression")."</a></div>\n"
	."\t</div>\n";

	$conditionsoutput_main_content .= "</div>\n"; // end conditiontarget div


	$js_admin_includes[]= $homeurl.'/scripts/conditions.js';
	$js_admin_includes[]= $rooturl.'/scripts/jquery/lime-conditions-tabs.js';

	if ($subaction == "editthiscondition" && isset($p_cid))
	{
		$submitLabel = $clang->gT("Update condition");
		$submitSubaction = "updatecondition";
		$submitcid = sanitize_int($p_cid);
	}
	else
	{
		$submitLabel = $clang->gT("Add condition");
		$submitSubaction = "insertcondition";
		$submitcid = "";
	}

	$conditionsoutput_main_content .= "</div>\n"
	."</div>\n";

	// Begin buttons row
	if ($subaction == 'editthiscondition') {
		// show the Delete all conditions for this question button
		$deleteConditionButton = "<button"
		. " onclick=\"if ( confirm('" . $clang->gT("Are you sure you want to delete this condition?","js") . "')) {"
		. "submitFormAsParent(deleteallconditions);}\">" . $clang->gT("Delete this condition") . "</button>";
	}
	
	$conditionsoutput_main_content .= "<div class='condition-tbl-full'>\n"
// 	."\t<input type='reset' id='resetForm' value='".$clang->gT("Clear")."' />\n"
	. (isset($deleteConditionButton) ? $deleteConditionButton : '')
	. "\t<input type='button' value='".$submitLabel."' onClick='submitConditionFormAsParent(editconditions);' />\n"
	. "<input type='hidden' name='checksessionbypost' value='{$_SESSION['checksessionpost']}' />\n"
	."<input type='hidden' name='action' value='conditions' />\n"
	."<input type='hidden' name='sid' value='$surveyid' />\n"
	."<input type='hidden' name='gid' value='$gid' />\n"
	."<input type='hidden' name='qid' value='$qid' />\n"
	."<input type='hidden' name='subaction' value='$submitSubaction' />\n"
	."<input type='hidden' name='cqid' id='cqid' value='' />\n"
	."<input type='hidden' name='cid' id='cid' value='".$submitcid."' />\n"
	."<input type='hidden' name='editTargetTab' id='editTargetTab' value='' />\n" // auto-select tab by jQuery when editing a condition
	."<input type='hidden' name='editSourceTab' id='editSourceTab' value='' />\n" // auto-select tab by jQuery when editing a condition
	."<input type='hidden' name='canswersToSelect' id='canswersToSelect' value='' />\n" // auto-select target answers by jQuery when editing a condition
	."</div>\n"
	."</form>\n";
	
	if (!isset($js_getAnswers_onload))
	{
		$js_getAnswers_onload = '';
	}

	$conditionsoutput_main_content .= "<script type='text/javascript'>\n"
	. "<!--\n"
	. "\t".$js_getAnswers_onload."\n";
	if (isset($p_method))
	{
		$conditionsoutput_main_content .= "\tdocument.getElementById('method').value='".$p_method."';\n";
	}

	if ($subaction == "editthiscondition")
	{
		// in edit mode we read previous values in order to dusplay them in the corresponding inputs
		if (isset($_GET['EDITConditionConst']) && $_GET['EDITConditionConst'] != '')
		{
			// In order to avoid issues with backslash escaping, I don't use javascript to set the value
			// Thus the value is directly set when creating the Textarea element
			//$conditionsoutput_main_content .= "\tdocument.getElementById('ConditionConst').value='".html_escape($_GET['EDITConditionConst'])."';\n";
			$conditionsoutput_main_content .= "\tdocument.getElementById('editTargetTab').value='#CONST';\n";
		}
		elseif (isset($_GET['EDITprevQuestionSGQA']) && $_GET['EDITprevQuestionSGQA'] != '')
		{
			$conditionsoutput_main_content .= "\tdocument.getElementById('prevQuestionSGQA').value='".html_escape($_GET['EDITprevQuestionSGQA'])."';\n";
			$conditionsoutput_main_content .= "\tdocument.getElementById('editTargetTab').value='#PREVQUESTIONS';\n";
		}
		elseif (isset($_GET['EDITtokenAttr']) && $_GET['EDITtokenAttr'] != '')
		{
			$conditionsoutput_main_content .= "\tdocument.getElementById('tokenAttr').value='".html_escape($_GET['EDITtokenAttr'])."';\n";
			$conditionsoutput_main_content .= "\tdocument.getElementById('editTargetTab').value='#TOKENATTRS';\n";
		}
		elseif (isset($_GET['EDITConditionRegexp']) && $_GET['EDITConditionRegexp'] != '')
		{
			// In order to avoid issues with backslash escaping, I don't use javascript to set the value
			// Thus the value is directly set when creating the Textarea element
			//$conditionsoutput_main_content .= "\tdocument.getElementById('ConditionRegexp').value='".html_escape($_GET['EDITConditionRegexp'])."';\n";
			$conditionsoutput_main_content .= "\tdocument.getElementById('editTargetTab').value='#REGEXP';\n";
		}
		elseif (isset($_GET['EDITcanswers']) && is_array($$_GET['EDITcanswers']))
		{
			// was a predefined answers post
			$conditionsoutput_main_content .= "\tdocument.getElementById('editTargetTab').value='#CANSWERSTAB';\n";
			$conditionsoutput_main_content .= "\t$('#canswersToSelect').val('".$_GET['EDITcanswers'][0]."');\n";
		}

		if (isset($_GET['csrctoken']) && $_GET['csrctoken'] != '')
		{
			$conditionsoutput_main_content .= "\tdocument.getElementById('csrctoken').value='".html_escape($_GET['csrctoken'])."';\n";
			$conditionsoutput_main_content .= "\tdocument.getElementById('editSourceTab').value='#SRCTOKENATTRS';\n";
		}
		else
		{
			$conditionsoutput_main_content .= "\tdocument.getElementById('cquestions').value='".html_escape($_GET['cquestions'])."';\n";
			$conditionsoutput_main_content .= "\tdocument.getElementById('editSourceTab').value='#SRCPREVQUEST';\n";
		}
	}
	else
	{ // in other modes, for the moment we do the same as for edit mode
		if (isset($_GET['ConditionConst']) && $_GET['ConditionConst'] != '')
		{
			// In order to avoid issues with backslash escaping, I don't use javascript to set the value
			// Thus the value is directly set when creating the Textarea element
			//$conditionsoutput_main_content .= "\tdocument.getElementById('ConditionConst').value='".html_escape($_GET['ConditionConst'])."';\n";
			$conditionsoutput_main_content .= "\tdocument.getElementById('editTargetTab').value='#CONST';\n";
		}
		elseif (isset($_GET['prevQuestionSGQA']) && $_GET['prevQuestionSGQA'] != '')
		{
			$conditionsoutput_main_content .= "\tdocument.getElementById('prevQuestionSGQA').value='".html_escape($_GET['prevQuestionSGQA'])."';\n";
			$conditionsoutput_main_content .= "\tdocument.getElementById('editTargetTab').value='#PREVQUESTIONS';\n";
		}
		elseif (isset($_GET['tokenAttr']) && $_GET['tokenAttr'] != '')
		{
			$conditionsoutput_main_content .= "\tdocument.getElementById('tokenAttr').value='".html_escape($_GET['tokenAttr'])."';\n";
			$conditionsoutput_main_content .= "\tdocument.getElementById('editTargetTab').value='#TOKENATTRS';\n";
		}
		elseif (isset($_GET['ConditionRegexp']) && $_GET['ConditionRegexp'] != '')
		{
			// In order to avoid issues with backslash escaping, I don't use javascript to set the value
			// Thus the value is directly set when creating the Textarea element
			//$conditionsoutput_main_content .= "\tdocument.getElementById('ConditionRegexp').value='".html_escape($_GET['ConditionRegexp'])."';\n";
			$conditionsoutput_main_content .= "\tdocument.getElementById('editTargetTab').value='#REGEXP';\n";
		}
		else
		{ // was a predefined answers post
			if (isset($_GET['cquestions']))
			{
				$conditionsoutput_main_content .= "\tdocument.getElementById('cquestions').value='".html_escape($_GET['cquestions'])."';\n";
			}
			$conditionsoutput_main_content .= "\tdocument.getElementById('editTargetTab').value='#CANSWERSTAB';\n";
		}

		if (isset($_GET['csrctoken']) && $_GET['csrctoken'] != '')
		{
			$conditionsoutput_main_content .= "\tdocument.getElementById('csrctoken').value='".html_escape($_GET['csrctoken'])."';\n";
			$conditionsoutput_main_content .= "\tdocument.getElementById('editSourceTab').value='#SRCTOKENATTRS';\n";
		}
		else
		{
			if (isset($_GET['cquestions'])) $conditionsoutput_main_content .= "\tdocument.getElementById('cquestions').value='".javascript_escape($_GET['cquestions'])."';\n";
			$conditionsoutput_main_content .= "\tdocument.getElementById('editSourceTab').value='#SRCPREVQUEST';\n";
		}
	}

	if (isset($p_scenario))
	{
		$conditionsoutput_main_content .= "\tdocument.getElementById('scenario').value='".$p_scenario."';\n";
	}
	$conditionsoutput_main_content .= "-->\n"
	. "</script>\n";
	$conditionsoutput_main_content .= "</td></tr>\n";
}
//END: DISPLAY THE ADD or EDIT CONDITION FORM


$conditionsoutput_main_content .= "</table>\n";

$conditionsoutput = $conditionsoutput_header
. $conditionsoutput_menubar
. $conditionsoutput_action_error
. $conditionsoutput_main_content;


////////////// FUNCTIONS /////////////////////////////

function showSpeaker($hinttext)
{
	global $clang, $imageurl, $max;

	if(!isset($max))
	{
		$max = 20;
	}
	$htmlhinttext=str_replace("'",'&#039;',$hinttext);  //the string is already HTML except for single quotes so we just replace these only
	$jshinttext=javascript_escape($hinttext,true,true);

	if(strlen(html_entity_decode($hinttext,ENT_QUOTES,'UTF-8')) > ($max+3))
	{
		$shortstring = FlattenText($hinttext);

		$shortstring = htmlspecialchars(mb_strcut(html_entity_decode($shortstring,ENT_QUOTES,'UTF-8'), 0, $max, 'UTF-8'));

		//output with hoover effect
		$reshtml= "<span style='cursor: hand' alt='".$htmlhinttext."' title='".$htmlhinttext."' "
		." onclick=\"alert('".$clang->gT("Question","js").": $jshinttext')\" />"
		." \"$shortstring...\" </span>"
		."<img style='cursor: hand' src='$imageurl/speaker.png' align='bottom' alt='$htmlhinttext' title='$htmlhinttext' "
		." onclick=\"alert('".$clang->gT("Question","js").": $jshinttext')\" />";
	}
	else
	{
		$shortstring = FlattenText($hinttext);

		$reshtml= "<span title='".$shortstring."'> \"$shortstring\"</span>";
	}

	return $reshtml;

}
