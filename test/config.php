<?php
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$conn = new mysqli($dbhost, $dbuser, $dbpass);
$conn -> select_db($db);
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$hubVerifyToken = 'BHandy_TEST';
if($_REQUEST['hub_verify_token'] === $hubVerifyToken) {
	echo $_REQUEST['hub_challenge'];
}
$accessToken = "EAAERBnEHU4YBABX3FQ1tDQiTW9ZCHZAUzEZA63MPZAwoC92GJRSRZCnB75liDxQQp02zsgruRZAn6e58EeOEFEwrU82wgPnZCkqvZCka4GUHiZBsBXZBmMStvKE7QJoV2C4ZBvnoaaoSZBZAfcqlhfbLIIpnNUffes65sYc3uqUHj7TK0hOZAGUffTdZCWE";
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$input = json_decode(file_get_contents('php://input'), true);
$senderID = $input['entry'][0]['messaging'][0]['sender']['id'];
$recipientID = $input['entry'][0]['messaging'][0]['recipient']['id'];
$messageText = $input['entry'][0]['messaging'][0]['message']['text'];
$payload = $input['entry'][0]['messaging'][0]['postback']['payload'];
$payloadQR = $input['entry'][0]['messaging'][0]['message']['quick_reply']['payload'];

///////////////////////////////////////////////////
$pageServiceID = "126362538147191";
///////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$thisYear = date("Y");
$inputTime = date("Y-m-d H:i:s",time());
// timestamp type
$now = strtotime($inputTime);
// date type (ex. 2018-01-01)
$today = date("Y-m-d");
//
//$now = mktime(0,0,0,3,2,2018);
//$today = date("Y-m-d", $now);
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 올 해 일정 정보
$yearsSchedule = YearsSchedule();

$semesterW = $yearsSchedule['bachelor']['season']['W'];
$semesterS = $yearsSchedule['bachelor']['season']['S'];
$semester1 = $yearsSchedule['bachelor']['regular'][1];
$semester2 = $yearsSchedule['bachelor']['regular'][2];

// 정규학기 기간
//// 1학기
$semesterRegular1 = ($today >= $semester1['start'] && $today <= $semester1['end']);
//// 2학기
$semesterRegular2 = ($today >= $semester2['start'] && $today <= $semester2['end']);

// 계절학기 기간
//// 여름
$semesterSeasonS = ($today >= $semesterS['start'] && $today <= $semesterS['end']);
//// 겨울
$semesterSeasonW = ($today >= $semesterW['start'] && $today <= $semesterW['end']);
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// DB 선택
$thisCourse = getCourse($yearsSchedule, $today);
//
//$thisCourse = 'course201801';
//
// 해당 학기
$thisSemester = str_replace('course'.$thisYear, '', $thisCourse);
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if(isset($senderID) && (isset($messageText ) || isset($payload) || isset($payloadQR))) {
	if($senderID != $pageServiceID) {
		if($messageText) {
			$query = "INSERT INTO message (userkey, year, semester, inputMsg, inputTime) VALUE ('$senderID', '$thisYear', '$thisSemester', '$messageText', '$inputTime')";
			$conn->query($query);
		}
		else if($payload) {
			$query = "INSERT INTO message (userkey, year, semester, inputMsg, inputTime) VALUE ('$senderID', '$thisYear', '$thisSemester', '$payload', '$inputTime')";
			$conn->query($query);
		}
		else if($payloadQR) {
			$query = "INSERT INTO message (userkey, year, semester, inputMsg, inputTime) VALUE ('$senderID', '$thisYear', '$thisSemester', '$payloadQR', '$inputTime')";
			$conn->query($query);
		}
	}
	//
	// 수정필요
	//
	else if($senderID == $pageServiceID) {
		$query = "UPDATE message SET outputMsg='$messageText' WHERE userkey='$recipientID' AND year='$thisYear' AND semester='$thisSemester' ORDER BY inputTime DESC LIMIT 1";
		$conn->query($query);
	}
}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////