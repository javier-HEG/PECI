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

// Get the JSON element
$groupIds = $_POST['groupIds'];
$groupQuestions = $_POST['groupQuestions'];

$importIntoSid = $_POST['sid'];
$templateSurveyId = $_POST['templatequeryid'];
$language = $_POST['language']; 

// Iterate over the list of groups
for ($i = 0; $i < count($groupIds); $i++) {
	// Load the group information from template
	$gidquery = "SELECT * FROM " . db_table_name('groups')
		. " WHERE gid='{$groupIds[$i]}' AND sid='$templateSurveyId' AND language='" . $language . "'";
	$gidresult = db_execute_assoc($gidquery);
	
	$gv = $gidresult->FetchRow();
	
	// Find the current maximum group_order
	// NB. The existing function getMaxgrouporder seems not to work
	$maxGroupOrderQuery = "SELECT max(group_order) AS max FROM " . db_table_name('groups') . " WHERE sid='$importIntoSid' AND language='$language'" ;
	$newGroupOrder = $connect->GetOne($maxGroupOrderQuery);
	$newGroupOrder = is_null($newGroupOrder) ? 0 : $newGroupOrder['max'] + 1;

	// Create new group in current survey
	$newGroupQuery = "INSERT INTO " . db_table_name('groups') . " (sid, group_name, description, group_order, language) "
		. "VALUES ('" . db_quote($importIntoSid) . "', '" . db_quote($gv['group_name']) . "', '" . db_quote($gv['description']) . "',"
		. $newGroupOrder . ",'$language')";
  	$newGroupResult = $connect->Execute($newGroupQuery) or safe_die($connect->ErrorMsg());
  	$newGroupId = $connect->Insert_Id(db_table_name_nq('groups'),"gid");
 	
	foreach ($groupQuestions[$i] as $questionId) {
		// copy question
		$tmpQuestionQuery = "SELECT * FROM " . db_table_name('questions')
			. " WHERE qid='$questionId' AND gid='{$gv['gid']}' AND sid='$templateSurveyId' AND language='$language'";
		$tmpQuestionResult = db_execute_assoc($tmpQuestionQuery);
		
		$tmpQuestion = $tmpQuestionResult->FetchRow();
		$tmpQuestion  = array_map('db_quote', $tmpQuestion);

		// Find the current maximum question_order
		$maxQuestionOrderQuery = "SELECT max(question_order) AS max FROM " . db_table_name('questions') . " WHERE gid=$newGroupId";
		$newQuestionOrder = $connect->GetOne($maxQuestionOrderQuery);
		$newQuestionOrder = is_null($newQuestionOrder) ? 0 : $newQuestionOrder['max'] + 1;
		
		$newQuestionQuery = "INSERT INTO " . db_table_name('questions')
			. " (sid, gid, type, title, question, preg, help, other, mandatory, question_order, language) "
			. "VALUES ($importIntoSid, $newGroupId, '{$tmpQuestion['type']}', '{$tmpQuestion['title']}', "
			. "'{$tmpQuestion['question']}', '{$tmpQuestion['preg']}', '{$tmpQuestion['help']}', "
			. "'{$tmpQuestion['other']}', '{$tmpQuestion['mandatory']}', $newQuestionOrder, '$language')";
 		$newQuestionResult = $connect->Execute($newQuestionQuery) or safe_die($connect->ErrorMsg());
 		$newQuestionId = $connect->Insert_ID("{$dbprefix}questions","qid");

		// copy sub questions
		copySubquestions($questionId, $importIntoSid, $newGroupId, $newQuestionId, $language);
		
		// copy answers
		copyAnswers($questionId, $newQuestionId, $language);
	}
}

function copySubquestions($fromId, $toSurveyId, $toGroupId, $toId, $language) {
	global $connect;
	
	$tablename = db_table_name('questions');
	
	$aSQIDMappings = array();
	$q1 = "SELECT * FROM $tablename WHERE parent_qid=$fromId AND language='$language' ORDER BY question_order";
	$r1 = db_execute_assoc($q1);
	
	while ($qr1 = $r1->FetchRow()) {
		unset($qr1['qid']);

		$qr1['parent_qid'] = $toId;
		$qr1['sid'] = $toSurveyId;
		$qr1['gid'] = $toGroupId;
		
		$newSQIValues = array();
		$newSQIKeys = array_keys($qr1);
		foreach ($newSQIKeys as $key) {
			$newSQIValues[] = $qr1[$key];
		}
		
		$newSQQuery = "INSERT INTO $tablename (" . implode(',', $newSQIKeys) . ') VALUES (\''
			. implode("','", $newSQIValues) . '\')' ;
		
		$connect->Execute($newSQQuery);
	}
}

function copyAnswers($fromId, $toId, $language) {
	global $connect;
	
	$tablename = db_table_name('answers');
	
	$q1 = "SELECT * FROM $tablename WHERE qid=$fromId AND language='$language' ORDER BY code";
	$r1 = db_execute_assoc($q1);
	
	while ($qr1 = $r1->FetchRow()) {
		$qr1 = array_map('db_quote', $qr1);
		$i1 = "INSERT INTO $tablename (qid, code, answer, sortorder, language, scale_id) "
			. "VALUES ('$toId', '{$qr1['code']}', '{$qr1['answer']}', "
			. "'{$qr1['sortorder']}', '{$qr1['language']}', '{$qr1['scale_id']}')";
	    
		$connect->Execute($i1);
	}
}
