<?php
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 등록된 유저 정보
$query = "SELECT * FROM user WHERE year='$thisYear' AND semester='$thisSemester'";
$sql4user = $conn->query($query);
while($row4user = $sql4user->fetch_assoc()) {
	$userInfo[] = $row4user;
	$userInfoUserkey[] = $row4user['userkey'];
}
// 등록된 이벤트 정보
$query = "SELECT * FROM event WHERE year='$thisYear' AND semester='$thisSemester'";
$sql4event = $conn->query($query);
while($row4event = $sql4event->fetch_assoc()) {
	$eventDate1 = date("Y-m-d", mktime(0, 0, 0, (int)substr($row4event['date1'], 0, 2), (int)substr($row4event['date1'], 2, 4), date("Y")));
	$eventDate2 = date("Y-m-d", mktime(0, 0, 0, (int)substr($row4event['date2'], 0, 2), (int)substr($row4event['date2'], 2, 4), date("Y")));
	$nowDate = date("Y-m-d", strtotime($inputTime));
	if((empty($row4event['date2']) && $eventDate1 >= $nowDate) || (!empty($row4event['date2']) && $eventDate2 >= $nowDate)) {
		$eventInfo[] = $row4event;
		$eventInfoUserkey[] = $row4event['userkey'];
	}
}
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/*
if(!in_array($today, $yearsSchedule['dayoff'])) {
	$semesterW = $yearsSchedule['bachelor']['season']['W'];
	$semesterS = $yearsSchedule['bachelor']['season']['S'];
	$semester1 = $yearsSchedule['bachelor']['regular'][1];
	$semester2 = $yearsSchedule['bachelor']['regular'][2];		

	// 계절학기
	if(($today >= $semesterW['start'] && $today <= $semesterW['end']) || ($today >= $semesterS['start'] && $today <= $semesterS['end'])) {
		//// (계절학기 시작일 ~ 계절학기 종료일) -> 출첵 크론탭 작동(08~18시 동안 매일 5분마다)
		//// 							''							 -> 수업 종료 후 푸시 크론탭 작동(08~18시 동안 매일 15분마다)
		//// 							''							 -> 등록된 과제,휴강,시험 이벤트 푸시 크론탭 작동(매일 오전 8시에 한번만)
		
	}
	
	// (계절학기 수강신청 기간 동안) -> (해당 년도 + 계절) db 생성(course + (해당년도) + (계절 S or W)) 후, course INSERT+UPDATE 크론탭 작동(새벽4시마다)
	if(($today >= $semesterW['sign_up']['start'] && $today <= $semesterW['sign_up']['end']) || ($today >= $semesterS['sign_up']['start'] && $today <= $semesterS['sign_up']['end'])) {
		
	}
	
	// 정규학기
	if(($today >= $semester1['start'] && $today <= $semester1['end']) || ($today >= $semester2['start'] && $today <= $semester2['end'])) {
		//// 수강신청 시작일 ~ 개강일 -> 해당 년도 db 생성(course + (해당년도)) 후, course INSERT+UPDATE 크론탭 작동(새벽4시마다)
		if(($today >= $semester1['sign_up']['start'] && $today <= $semester1['start']) || ($today >= $semester2['sign_up']['start'] && $today <= $semester2['start'])) {
			
		}
		//// (개강일 ~ 중간고사 시작일) && (중간고사 종료일 ~ 기말고사 시작일) -> 출첵 크론탭 작동(08~18시 동안 매일 5분마다)
		//// 												''														 -> 수업 종료 후 푸시 크론탭 작동(08~18시 동안 매일 15분마다)
		//// 												''														 -> 등록된 과제,휴강,시험 이벤트 푸시 크론탭 작동(매일 오전 8시에 한번만)
		if((($today >= $semester1['start'] && $today < $semester1['term']['mid']['start']) || ($today > $semester1['term']['mid']['end'] && $today < $semester1['term']['final']['start']))
			|| (($today >= $semester2['start'] && $today < $semester2['term']['mid']['start']) || ($today > $semester2['term']['mid']['end'] && $today < $semester2['term']['final']['start']))) {
			
		}
	}
}
*/