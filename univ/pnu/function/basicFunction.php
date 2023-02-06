<?php
function resetProcessing()
{
	global $senderID, $thisYear, $thisSemester;
	
	$query = "UPDATE processing SET rgstInsert=0, rgstGeneralSelc=0, rgstMajor=0, rgstMajorBasic=0, rgstLiberal=0, rgstLiberalEssn=0 WHERE userkey='$senderID' AND year='$thisYear' AND semester='$thisSemester'";
	
	return $query;
}

function insertProcessing($field=NULL)
{
	global $senderID, $thisYear, $thisSemester;
	
	if(isset($field)) {
		if($field == 'insert') {
			$query = "INSERT INTO processing (year, semester, userkey, rgstInsert, rgstGeneralSelc, rgstMajor, rgstMajorBasic, rgstLiberal, rgstLiberalEssn) VALUE ('$thisYear', '$thisSemester', '$senderID', 1, 0, 0, 0, 0, 0)";
		}
		else if($field == 'generalSelc') {
			$query = "INSERT INTO processing (year, semester, userkey, rgstInsert, rgstGeneralSelc, rgstMajor, rgstMajorBasic, rgstLiberal, rgstLiberalEssn) VALUE ('$thisYear', '$thisSemester', '$senderID', 0, 1, 0, 0, 0, 0)";
		}
		else if($field == 'major') {
			$query = "INSERT INTO processing (year, semester, userkey, rgstInsert, rgstGeneralSelc, rgstMajor, rgstMajorBasic, rgstLiberal, rgstLiberalEssn) VALUE ('$thisYear', '$thisSemester', '$senderID', 0, 0, 1, 0, 0, 0)";
		}
		else if($field == 'majorBasic') {
			$query = "INSERT INTO processing (year, semester, userkey, rgstInsert, rgstGeneralSelc, rgstMajor, rgstMajorBasic, rgstLiberal, rgstLiberalEssn) VALUE ('$thisYear', '$thisSemester', '$senderID', 0, 0, 0, 1, 0, 0)";
		}
		else if($field == 'liberal') {
			$query = "INSERT INTO processing (year, semester, userkey, rgstInsert, rgstGeneralSelc, rgstMajor, rgstMajorBasic, rgstLiberal, rgstLiberalEssn) VALUE ('$thisYear', '$thisSemester', '$senderID', 0, 0, 0, 0, 1, 0)";
		}
		else if($field == 'liberalEssn') {
			$query = "INSERT INTO processing (year, semester, userkey, rgstInsert, rgstGeneralSelc, rgstMajor, rgstMajorBasic, rgstLiberal, rgstLiberalEssn) VALUE ('$thisYear', '$thisSemester', '$senderID', 0, 0, 0, 0, 0, 1)";	
		}
	} else {
		$query = "INSERT INTO processing (year, semester, userkey, rgstInsert, rgstGeneralSelc, rgstMajor, rgstMajorBasic, rgstLiberal, rgstLiberalEssn) VALUE ('$thisYear', '$thisSemester', '$senderID', 0, 0, 0, 0, 0, 0)";
	}
	
	return $query;
}

function updateProcessing($field)
{
	global $senderID, $thisYear, $thisSemester;
	
	if($field) {
		if($field == 'insert') {
			$query = "UPDATE processing SET rgstInsert=1,  rgstGeneralSelc=0, rgstMajor=0, rgstMajorBasic=0, rgstLiberal=0, rgstLiberalEssn=0 WHERE userkey = '$senderID' AND year='$thisYear' AND semester='$thisSemester'";
		}
		else if($field == 'generalSelc') {
			$query = "UPDATE processing SET rgstInsert=0,  rgstGeneralSelc=1, rgstMajor=0, rgstMajorBasic=0, rgstLiberal=0, rgstLiberalEssn=0 WHERE userkey = '$senderID' AND year='$thisYear' AND semester='$thisSemester'";
		}
		else if($field == 'major') {
			$query = "UPDATE processing SET rgstInsert=0,  rgstGeneralSelc=0, rgstMajor=1, rgstMajorBasic=0, rgstLiberal=0, rgstLiberalEssn=0 WHERE userkey = '$senderID' AND year='$thisYear' AND semester='$thisSemester'";
		}
		else if($field == 'majorBasic') {
			$query = "UPDATE processing SET rgstInsert=0,  rgstGeneralSelc=0, rgstMajor=0, rgstMajorBasic=1, rgstLiberal=0, rgstLiberalEssn=0 WHERE userkey = '$senderID' AND year='$thisYear' AND semester='$thisSemester'";
		}
		else if($field == 'liberal') {
			$query = "UPDATE processing SET rgstInsert=0,  rgstGeneralSelc=0, rgstMajor=0, rgstMajorBasic=0, rgstLiberal=1, rgstLiberalEssn=0 WHERE userkey = '$senderID' AND year='$thisYear' AND semester='$thisSemester'";
		}
		else if($field == 'liberalEssn') {
			$query = "UPDATE processing SET rgstInsert=0,  rgstGeneralSelc=0, rgstMajor=0, rgstMajorBasic=0, rgstLiberal=0, rgstLiberalEssn=1 WHERE userkey = '$senderID' AND year='$thisYear' AND semester='$thisSemester'";
		}
	}
	
	return $query;
}

function queryInsert($dbTable, $content1, $content2=NULL)
{
	global $senderID, $thisYear, $thisSemester, $inputTime;	
	
	if(!$content2 && !is_array($content2)) {
		$query = "INSERT INTO $dbTable (year, semester, userkey, inProgress, inputTime) VALUE ('$thisYear', '$thisSemester', '$senderID', '$content1', '$inputTime')";
	}
	else 	if($content2 && is_array($content2)) {
		foreach($content2 as $key=>$value) {
			$keys[] = $key;
			$values[] = "'" . $value. "'";
		}
		count($keys) == 1 ? $keys = $keys[0] : $keys = implode(", ", $keys);
		count($values) == 1 ? $values = $values[0] : $values = implode(", ", $values);

		$query = "INSERT INTO $dbTable (year, semester, userkey, inProgress, $keys, inputTime) VALUE ('$thisYear', '$thisSemester', '$senderID', '$content1', $values, '$inputTime')";		
	}
	return $query;
}

function ReturningQR($content1=NULL, $content2=NULL)
{
	$send['text'] = "🎩: 이전 단계로 돌아가려면 아래 버튼을 눌러주세요.";
	$payloadNtitle = array('이전으로', '초기화면');
	
	if($content1 == "등록된 교과목 정보 보기" && !isset($content2)) {
		array_unshift($payloadNtitle, $content1);
	}	
	else if($content1 == "등록된 교과목 정보 보기" && $content2 == "등록된 과제∙휴강∙시험 정보 보기") {
		array_unshift($payloadNtitle, $content1);
		array_unshift($payloadNtitle, $content2);
	}
	$send['payload'] = $send['title'] = $payloadNtitle;

	messageQR($send);
}

function findUserName($userkey)
{
	$getSenderFullName = json_decode(curlGet("https://graph.facebook.com/v2.6/" . $userkey . "?fields=first_name,last_name,profile_pic,locale,timezone,gender&access_token="), true);
	$senderFullName = $getSenderFullName['last_name'] . $getSenderFullName['first_name'];
	
	return $senderFullName;
}

function getCourseColumnData($thisCourse, $column)
{
	global $conn;
	
	if($column == 'divs' || $column == 'title' || $column == 'major' || $column == 'fields') {
		if($column == 'fields') {
			$query = "SELECT DISTINCT $column FROM $thisCourse WHERE $column LIKE '%영역'";
		} else {
			$query = "SELECT DISTINCT $column FROM $thisCourse";
		}
		$sql = $conn->query($query);
		while($row = $sql->fetch_assoc()) {
			$result[] = $row[$column];
		}
		
		return $result;
	}
}

function YearsSchedule()
{
	global $conn, $thisYear;
	
	// 올해 일정
	$query = "SELECT * FROM schedule WHERE year=".$thisYear;
	$sql4schedule = $conn->query($query);
	while($row4schedule = $sql4schedule->fetch_assoc()) {
		$res[] = $row4schedule;
		$type = $row4schedule['type'];
		$schedule = $row4schedule['schedule'];
		$start = $row4schedule['date1'];
		$end = $row4schedule['date2'];
		
		// 휴일
		if($type == "dayoff") {
			if($start == $end) {
				$result['dayoff'][] = $start;
			} else {
				$result['dayoff'][] = $start;
				$diff = (strtotime($end) - strtotime($start)) / (60*60*24) - 1;
				for($i=1; $i<=$diff; $i++) {
					$result['dayoff'][] = date("Y-m-d", strtotime("+$i day", strtotime($start)));
				}
				$result['dayoff'][] = $end;
			}
		}
		
		// 학사일정
		else if($type == "bachelor") {
			$semester = 2;
			$season = array('','S', 'W');
			$seasonKR = array('','여름', '겨울');
			for($i=1; $i<=$semester; $i++) {
				// 정규학기
				//// 개강~종강
				if($schedule == $i."학기 개강") {
					$result['bachelor']['regular'][$i]['start'] = $start;
				}
				else if($schedule == $i."학기 기말고사") {
					$result['bachelor']['regular'][$i]['end'] = $end;
				}
				//// 수강신청 기간
				if($schedule == $i."학기 수강신청") {
					$result['bachelor']['regular'][$i]['sign_up']['start'] = $start;
					$result['bachelor']['regular'][$i]['sign_up']['end'] = $end;
				}
				//// 중간고사 기간
				if($schedule == $i."학기 중간고사") {
					$result['bachelor']['regular'][$i]['term']['mid']['start'] = $start;
					$result['bachelor']['regular'][$i]['term']['mid']['end'] = $end;
				}
				//// 기말고사 기간
				if($schedule == $i."학기 기말고사") {
					$result['bachelor']['regular'][$i]['term']['final']['start'] = $start;
					$result['bachelor']['regular'][$i]['term']['final']['end'] = $end;
				}
				
				// 계절학기
				//// 수강신청 기간
				if($schedule == $seasonKR[$i]."계절 수강신청") {
					$result['bachelor']['season'][$season[$i]]['sign_up']['start'] = $start;
					$result['bachelor']['season'][$season[$i]]['sign_up']['end'] = $end;
				}
				//// 개강~종강
				if($schedule == $seasonKR[$i]."계절 수업") {
					$result['bachelor']['season'][$season[$i]]['start'] = $start;
					$result['bachelor']['season'][$season[$i]]['end'] = $end;
				}
			}
		}
	}
	return $result;
}

function getCourse($yearsSchedule, $today)
{
	global $thisYear;

	$semesterW = $yearsSchedule['bachelor']['season']['W'];
	$semesterS = $yearsSchedule['bachelor']['season']['S'];
	$semester1 = $yearsSchedule['bachelor']['regular'][1];
	$semester2 = $yearsSchedule['bachelor']['regular'][2];
	// 계절학기 전체기간
	//// 겨울계절
	$termOfSeasonW = ($today >= $semesterW['start'] && $today <= $semesterW['end']);
	//// 여름계절
	$termOfSeasonS = ($today >= $semesterS['start'] && $today <= $semesterS['end']);
	// 정규학기 전체기간
	//// 1학기
	$termOfRegular01 = ($today >= $semester1['start'] && $today <= $semester1['end']);
	//// 2학기
	$termOfRegular02 = ($today >= $semester2['start'] && $today <= $semester2['end']);
	if($termOfSeasonW) {
		$course = 'course'.$thisYear.'W';
	} 
	else if($termOfSeasonS) {
		$course = 'course'.$thisYear.'S';
	}
	else if($termOfRegular01) {
		$course = 'course'.$thisYear.'01';
	}
	else if($termOfRegular02) {
		$course = 'course'.$thisYear.'02';
	}
	return $course;
}