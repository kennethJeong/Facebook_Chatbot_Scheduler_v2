<?php
if(!in_array($today, $yearsSchedule['dayoff'])) {
	// 계절학기 전체기간
	$termOfSeason = (($today >= $semesterW['start'] && $today <= $semesterW['end']) || ($today >= $semesterS['start'] && $today <= $semesterS['end']));
	// 정규학기 전체기간
	$termOfRegular = (($today >= $semester1['start'] && $today <= $semester1['end']) || ($today >= $semester2['start'] && $today <= $semester2['end']));
	// 정규학기 ((개강일 ~ 중간고사 시작일) && (중간고사 종료일 ~ 기말고사 시작일))
	$termOfRegularFromStartToEnd = ((($today >= $semester1['start'] && $today < $semester1['term']['mid']['start']) || ($today > $semester1['term']['mid']['end'] && $today < $semester1['term']['final']['start'])) || (($today >= $semester2['start'] && $today < $semester2['term']['mid']['start']) || ($today > $semester2['term']['mid']['end'] && $today < $semester2['term']['final']['start'])));
	
	if($termOfSeason || ($termOfRegular && $termOfRegularFromStartToEnd)) {
		if(date("H:i", $now) == '12:00' || date("H:i", $now) == '18:00') {
			$daily = array('일', '월', '화', '수', '목', '금', '토');
			$numOfDays = count($daily)-1;
			$date = date('w');
			$todayDaily = $daily[$date];
			
			$todayDailysUserkey = array();
			for($i=0; $i<count($userInfo); $i++) {
				for($j=1; $j<=$numOfDays; $j++) {
					if($userInfo[$i]['day'.$j] == $todayDaily && !in_array($userInfo[$i]['userkey'], $todayDailysUserkey)) {
						$todayDailysUserkey[] = $userInfo[$i]['userkey'];
					}
				}
			}
			for($i=0; $i<count($todayDailysUserkey); $i++) {
				$query = "INSERT INTO logging (userkey, year, semester, inProgress, inputTime) VALUE ('{$todayDailysUserkey[$i]}', '$thisYear', '$thisSemester', 'READ', '$inputTime')";
				$conn->query($query);
				$query = "INSERT INTO loggingRead (userkey, year, semester, inProgress, inputTime) VALUE ('{$todayDailysUserkey[$i]}', '$thisYear', '$thisSemester', 'READ', '$inputTime')";
				$conn->query($query);
				
				$query = "SELECT * FROM user WHERE userkey=".$todayDailysUserkey[$i]." AND year='$thisYear' AND semester='$thisSemester'";
				$sql4user = $conn->query($query);
				while($row4user = $sql4user->fetch_assoc()) {
					$userInfo4User[] = $row4user;
				}
		
				$eachUserInfos = array();
				$c=0;
				for($j=0; $j<count($userInfo4User); $j++) {
					for($k=0; $k<=$numOfDays; $k++) {
						if($userInfo4User[$j]['day'.$k] == $todayDaily) {
							if(date("H:i", $now) == '12:00' && date("H", strtotime($userInfo4User[$j]['time'.$k])) < 12) {
								$eachUserInfos[$todayDailysUserkey[$i]]['info'][$c]['title'] = $userInfo4User[$j]['title'];
								$eachUserInfos[$todayDailysUserkey[$i]]['info'][$c]['class'] = $userInfo4User[$j]['class'];
								$eachUserInfos[$todayDailysUserkey[$i]]['info'][$c]['prof'] = $userInfo4User[$j]['prof'];
								$c++;
							}
							else if(date("H:i", $now) == '18:00' && date("H", strtotime($userInfo4User[$j]['time'.$k])) >= 12) {
								$eachUserInfos[$todayDailysUserkey[$i]]['info'][$c]['title'] = $userInfo4User[$j]['title'];
								$eachUserInfos[$todayDailysUserkey[$i]]['info'][$c]['class'] = $userInfo4User[$j]['class'];
								$eachUserInfos[$todayDailysUserkey[$i]]['info'][$c]['prof'] = $userInfo4User[$j]['prof'];
								$c++;
							}
						}
					}
				}
				$countTitles = count($eachUserInfos[$todayDailysUserkey[$i]]['info']);
				if($countTitles > 0) {
					$userName = findUserName($todayDailysUserkey[$i]);
					if(date("H:i", $now) == '12:00') {
						$send['text'] =  "🎩: " . $userName . "님!\n오전에 들은 ".$countTitles."개 수업에 과제∙휴강∙시험은 없었나요?\n등록해주시면 제가 관리해드릴게요!✨";
					}
					else if(date("H:i", $now) == '18:00') {
						$send['text'] =  "🎩: " . $userName . "님!\n오후에 들은 ".$countTitles."개 수업에 과제∙휴강∙시험은 없었나요?\n등록해주시면 제가 관리해드릴게요!✨";
					}	
					for($j=0; $j<$countTitles; $j++) {
						$title = $eachUserInfos[$todayDailysUserkey[$i]]['info'][$j]['title'];
						$class = $eachUserInfos[$todayDailysUserkey[$i]]['info'][$j]['class'];
						$prof = $eachUserInfos[$todayDailysUserkey[$i]]['info'][$j]['prof'];
						$send['title'][$j] = $title . "에 등록하기";
						$send['payload'][$j] = "AFTERCLASS_{$title}_{$class}_{$prof}";
					}
					$send['title'][$countTitles] = $send['payload'][$countTitles] = "초기화면";
					messageQR($send, $todayDailysUserkey[$i], 'UPDATE');
				}
				unset($send, $userInfo4User, $eachUserInfos);
			}
		}
	}
}
/*
if(!in_array($today, $yearsSchedule['dayoff'])) {
	// 계절학기 전체기간
	$termOfSeason = (($today >= $semesterW['start'] && $today <= $semesterW['end']) || ($today >= $semesterS['start'] && $today <= $semesterS['end']));
	// 정규학기 전체기간
	$termOfRegular = (($today >= $semester1['start'] && $today <= $semester1['end']) || ($today >= $semester2['start'] && $today <= $semester2['end']));
	// 정규학기 ((개강일 ~ 중간고사 시작일) && (중간고사 종료일 ~ 기말고사 시작일))
	$termOfRegularFromStartToEnd = ((($today >= $semester1['start'] && $today < $semester1['term']['mid']['start']) || ($today > $semester1['term']['mid']['end'] && $today < $semester1['term']['final']['start'])) || (($today >= $semester2['start'] && $today < $semester2['term']['mid']['start']) || ($today > $semester2['term']['mid']['end'] && $today < $semester2['term']['final']['start'])));
	
	if($termOfSeason || ($termOfRegular && $termOfRegularFromStartToEnd)) {
		////////////////////////////////////////////////////////////////////////////// 수업 종료 후 알림 //////////////////////////////////////////////////////////////////////////////////
		for($i=0; $i<count($userInfo); $i++) {
			// 이벤트 목록에서 휴강으로 등록한 목록이 있는지 체크
			$query = "SELECT * FROM event WHERE userkey='".$userInfo[$i]['userkey']."' AND type='cancel' AND title='".$userInfo[$i]['title']."'";
			$sql4event = $conn->query($query);
			while($row4event = $sql4event->fetch_assoc()) {
				$eventCancel[] = $row4event;
			}
			for($e=0; $e<count($eventCancel); $e++) {
				$eventCancel1 = date("Y-m-d", mktime(0,0,0, substr($eventCancel[$e]['date1'],0,2), substr($eventCancel[$e]['date1'],2,4), date("Y")));
				if($eventCancel[$e]['date2']) {
					$eventCancel2 = date("Y-m-d", mktime(0,0,0, substr($eventCancel[$e]['date2'],0,2), substr($eventCancel[$e]['date2'],2,4), date("Y")));
					if($today >= $eventCancel1 && $today <= $eventCancel2) {
						$eventCancelResult[] = FALSE;
					} else {
						$eventCancelResult[] = TRUE;
					}
				} else {
					if($today == $eventCancel1) {
						$eventCancelResult[] = FALSE;
					} else {
						$eventCancelResult[] = TRUE;
					}
				}
			}
			
			if(in_array(FALSE, $eventCancelResult)) {
				continue;
			} else {
				$daily = array('일', '월', '화', '수', '목', '금', '토');
				$numOfDays = count($daily)-1;
				$date = date('w');
				$todayDaily = $daily[$date];
				
				for($j=1; $j<=$numOfDays; $j++) {
					${finTime.$j} = strtotime($userInfo[$i]['time'.$j]) + ($userInfo[$i]['min'.$j] * 60);
					// 요일 체크
					if($userInfo[$i]['day'.$j] == $todayDaily) {
						// 푸시 시간 체크 (수업 종료 후 10분 후)
						if($now == ${finTime.$j}+(60*10)) {
							$userName = findUserName($userInfo[$i]['userkey']);
							
							$send['text'] = "🎩: " . $userName . "님!\n오늘 " . $userInfo[$i]['title'] . " 수업에 과제∙휴강∙시험은 없었나요?";
							message($send, $userInfo[$i]['userkey']);
							
							ForAlarm($userInfo[$i]['userkey']);
						}
					}
				}		
			}
		}
	}
}*/