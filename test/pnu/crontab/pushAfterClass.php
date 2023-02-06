<?php
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
}