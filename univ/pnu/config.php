<?php
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$conn = new mysqli($dbhost, $dbuser, $dbpass);
$conn -> select_db($db);
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$hubVerifyToken = 'BHandy_Scheduler_PNU';
//$accessToken = "EAAdyBMCey5QBANXofZAcTTlKHINAkpbZAt8PZBDcOngSoTH173YRWXhZBNOSDtUzXbVk7A7ULeyCbTj4KYVOkIGKH6mnf7QYiOABqxZARqikdLrZBL8XYdws3khGd0DmP8UbmCamPnZAkZAzE8RUsEkRZBxLdci0uLFwW7u2nAtTQpnQr29pR2qHQ";
$accessToken = "EAAdyBMCey5QBAHZBZAlW2HrTo3NG9yDYn7rTRBrSftD8eQSzWmeWOZBzgNJakUHUbu5boQCkkNWiQj0oFxwK4Q2MEk5Wi384461TU5A5ofLcTXcckQCe7B1HWpIuySiw7ZCYDuIeHOC5V8DlUptsSjKsa36tuDhBGPcwIFDSU6JT4MJnqvxd";
if($_REQUEST['hub_verify_token'] === $hubVerifyToken) {
	echo $_REQUEST['hub_challenge'];
}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$input = json_decode(file_get_contents('php://input'), true);
$senderID = $input['entry'][0]['messaging'][0]['sender']['id'];
$recipientID = $input['entry'][0]['messaging'][0]['recipient']['id'];
$messageText = $input['entry'][0]['messaging'][0]['message']['text'];
$payload = $input['entry'][0]['messaging'][0]['postback']['payload'];
$payloadQR = $input['entry'][0]['messaging'][0]['message']['quick_reply']['payload'];

///////////////////////////////////////////////////
$pageServiceID = "170646096902999";
$appServiceID = "1615856678527988";
///////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$thisYear = date("Y");
$inputTime = date("Y-m-d H:i:s",time());
// timestamp type
$now = strtotime($inputTime);
//$now = mktime(8,0,0,3,7,2018);
// date type (ex. 2018-01-01)
$today = date("Y-m-d");
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
// 게시글에 댓글이 달렸을 때 => 푸시 알림
$pageCommentField = $input['entry'][0]['changes'][0]['field'];
$pageCommentPostUserName = $input['entry'][0]['changes'][0]['value']['from']['name'];
$pageCommentPostUserID = $input['entry'][0]['changes'][0]['value']['from']['id'];
$pageCommentPostUserText = $input['entry'][0]['changes'][0]['value']['message'];
$pageCommentPostID = $input['entry'][0]['changes'][0]['value']['post_id'];
$pageCommentID = $input['entry'][0]['changes'][0]['value']['comment_id'];

if($pageCommentField == 'feed' && $pageCommentPostUserID) {
	$query = "SELECT * FROM pageComments WHERE field='$pageCommentField' AND postID='$pageCommentPostID' 
																				AND postUserID='$pageCommentPostUserID' AND name='$pageCommentPostUserName'";
	$res = $conn->query($query)->fetch_assoc();
	if(count($res) == 0) {
		$message = array
					(
						'message'=> "🎩: [부산대, 출첵했나?]에서 인사드립니다.\n\n\n바로 시작해보고싶죠?\n\n그럼 얼른 [시작하기]라고 입력해주세요 ❤\n\n\n(소곤소곤) 사실.. [ㄱ]만 입력해도 돼요.. 😜"
					);
			
		$url = "https://graph.facebook.com/v2.12/".$pageCommentID."/private_replies?access_token=".$accessToken;
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($ch);
		curl_close($ch);
		
		$query = "INSERT INTO pageComments (field, postID, postUserID, commentID, name, inputMesg, outputMesg, inputTIme)
																	VALUE('$pageCommentField', '$pageCommentPostUserID', '$pageCommentPostID', '$pageCommentID',
																				'$pageCommentPostUserName', '$pageCommentPostUserText', '{$message['message']}', '$inputTime')";
		$conn->query($query);
	}
}

