<?php
//$now = mktime(8,0,0,3,4,2018);
//$today = date("Y-m-d", $now);

//if(!in_array($today, $yearsSchedule['dayoff'])) {
	// 계절학기 전체기간
	$termOfSeason = (($today >= $semesterW['start'] && $today <= $semesterW['end']) || ($today >= $semesterS['start'] && $today <= $semesterS['end']));
	// 정규학기 전체기간
	$termOfRegular = (($today >= $semester1['start'] && $today <= $semester1['end']) || ($today >= $semester2['start'] && $today <= $semester2['end']));
	// 정규학기 ((개강일 ~ 중간고사 시작일) && (중간고사 종료일 ~ 기말고사 시작일))
	$termOfRegularFromStartToEnd = ((($today >= $semester1['start'] && $today < $semester1['term']['mid']['start']) || ($today > $semester1['term']['mid']['end'] && $today < $semester1['term']['final']['start'])) || (($today >= $semester2['start'] && $today < $semester2['term']['mid']['start']) || ($today > $semester2['term']['mid']['end'] && $today < $semester2['term']['final']['start'])));
	
	if($termOfSeason || ($termOfRegular && $termOfRegularFromStartToEnd)) {
		///////////////////////////////////////////////////////////////////////////////// 이벤트 알림 //////////////////////////////////////////////////////////////////////////////////////
		//
		// 매일 오전 8시마다 알림
		//
		
		if(date("H:i", $now) == '08:00') {
			$eventList = array('userkey' => array());
			for($i=0; $i<count($eventInfo); $i++) {
				$date1 = mktime(0,0,0,(int)substr($eventInfo[$i]['date1'],0,2),(int)substr($eventInfo[$i]['date1'],2,4),date("Y"));
				
				if($eventInfo[$i]['type'] == 'assignment') {
					if($date1 >= $now) {
						if($eventList[$eventInfo[$i]['userkey']]) {
							$eventList[$eventInfo[$i]['userkey']]['assignment'][] = array('title'=>$eventInfo[$i]['title'], 'content'=>$eventInfo[$i]['content'], 'date'=>$eventInfo[$i]['date1']);
						} else {
							$eventList[$eventInfo[$i]['userkey']] = array();
							$eventList[$eventInfo[$i]['userkey']]['assignment'][] = array('title'=>$eventInfo[$i]['title'], 'content'=>$eventInfo[$i]['content'], 'date'=>$eventInfo[$i]['date1']);
						}
					}
				}
				else if($eventInfo[$i]['type'] == 'cancel') {
					if($date1 >= $now) {
						if(!isset($eventInfo[$i]['date2'])) {
							if($eventList[$eventInfo[$i]['userkey']]) {
								$eventList[$eventInfo[$i]['userkey']]['cancel'][] = array('title'=>$eventInfo[$i]['title'], 'date1'=>$eventInfo[$i]['date1']);
							} else {
								$eventList[$eventInfo[$i]['userkey']] = array();
								$eventList[$eventInfo[$i]['userkey']]['cancel'][] = array('title'=>$eventInfo[$i]['title'], 'date1'=>$eventInfo[$i]['date1']);
							}				
						} else {
							if($eventList[$eventInfo[$i]['userkey']]) {
								$eventList[$eventInfo[$i]['userkey']]['cancel'][] = array('title'=>$eventInfo[$i]['title'], 'date1'=>$eventInfo[$i]['date1'], 'date2'=>$eventInfo[$i]['date2']);
							} else {
								$eventList[$eventInfo[$i]['userkey']] = array();
								$eventList[$eventInfo[$i]['userkey']]['cancel'][] = array('title'=>$eventInfo[$i]['title'], 'date1'=>$eventInfo[$i]['date1'], 'date2'=>$eventInfo[$i]['date2']);
							}					
						}
					}
				}
				else if($eventInfo[$i]['type'] == 'exam') {
					if($date1 >= $now) {
						if($eventList[$eventInfo[$i]['userkey']]) {
							$eventList[$eventInfo[$i]['userkey']]['exam'][] = array('title'=>$eventInfo[$i]['title'], 'date1'=>$eventInfo[$i]['date1'], 'time'=>$eventInfo[$i]['time1']);
						} else {
							$eventList[$eventInfo[$i]['userkey']] = array();
							$eventList[$eventInfo[$i]['userkey']]['exam'][] = array('title'=>$eventInfo[$i]['title'], 'date1'=>$eventInfo[$i]['date1'], 'time'=>$eventInfo[$i]['time1']);
						}
					}
				}
				
				if(in_array($eventInfo[$i]['userkey'], $eventList['userkey'])) {
					continue;
				} else {
					$eventList['userkey'][] = $eventInfo[$i]['userkey'];
			 	}
			}
			
			for($i=0; $i<count($eventList['userkey']); $i++) {
				$numOfEventAssignment = count($eventList[$eventList['userkey'][$i]]['assignment']);
				$numOfEventCancel = count($eventList[$eventList['userkey'][$i]]['cancel']);
				$numOfEventExam = count($eventList[$eventList['userkey'][$i]]['exam']);
				$numOfEventAll = $numOfEventAssignment + $numOfEventCancel + $numOfEventExam;
				
				if($numOfEventAll > 0) {
					$event[$eventList['userkey'][$i]]['text'] = "🎩: ". findUserName($eventList['userkey'][$i]) . "님!\n오늘 이후로 예정된 총 " . $numOfEventAll . "개(과제 ".$numOfEventAssignment."개, 휴강 ". $numOfEventCancel ."개, 시험 ". $numOfEventExam ."개)의\n일정을 알려드립니다.";
					for($j=0; $j<$numOfEventAssignment; $j++) {
						$event[$eventList['userkey'][$i]]['title'][] = "<".$eventList[$eventList['userkey'][$i]]['assignment'][$j]['title']."> - 과제";
						$event[$eventList['userkey'][$i]]['subtitle'][] = "과제 기한 - ".substr($eventList[$eventList['userkey'][$i]]['assignment'][$j]['date'],0,2)."월 ".substr($eventList[$eventList['userkey'][$i]]['assignment'][$j]['date'],2,4)."일\n과제내용: ".$eventList[$eventList['userkey'][$i]]['assignment'][$j]['content'];
					}
					for($j=0; $j<$numOfEventCancel; $j++) {
						$event[$eventList['userkey'][$i]]['title'][] = "<".$eventList[$eventList['userkey'][$i]]['cancel'][$j]['title']."> - 휴강";
						if(!empty($eventList[$eventList['userkey'][$i]]['cancel'][$j]['date2'])) {
							$event[$eventList['userkey'][$i]]['subtitle'][] = "휴강 날짜: ".substr($eventList[$eventList['userkey'][$i]]['cancel'][$j]['date1'],0,2)."월 ".substr($eventList[$eventList['userkey'][$i]]['cancel'][$j]['date1'],2,4)."일부터 ".substr($eventList[$eventList['userkey'][$i]]['cancel'][$j]['date2'],0,2)."월 ".substr($eventList[$eventList['userkey'][$i]]['cancel'][$j]['date2'],2,4)."일까지";
						} else {
							$event[$eventList['userkey'][$i]]['subtitle'][] = "휴강 날짜: ".substr($eventList[$eventList['userkey'][$i]]['cancel'][$j]['date1'],0,2)."월 ".substr($eventList[$eventList['userkey'][$i]]['cancel'][$j]['date1'],2,4)."일";			
						}
					}
					for($j=0; $j<$numOfEventExam; $j++) {
						$event[$eventList['userkey'][$i]]['title'][] = "<".$eventList[$eventList['userkey'][$i]]['exam'][$j]['title']."> - 시험";
						$event[$eventList['userkey'][$i]]['subtitle'][] = "시험 일정: ".substr($eventList[$eventList['userkey'][$i]]['exam'][$j]['date1'],0,2)."월 ".substr($eventList[$eventList['userkey'][$i]]['exam'][$j]['date1'],2,4)."일 ".substr($eventList[$eventList['userkey'][$i]]['exam'][$j]['time'],0,2)."시 ".substr($eventList[$eventList['userkey'][$i]]['exam'][$j]['time'],2,4)."분";
					}
					$send['text'] = $event[$eventList['userkey'][$i]]['text'];
					message($send, $eventList['userkey'][$i], 'UPDATE');
					
					$send['title'] = $event[$eventList['userkey'][$i]]['title'];
					$send['subtitle'] = $event[$eventList['userkey'][$i]]['subtitle'];
					messageTemplateLeftSlide($send, $eventList['userkey'][$i], 'UPDATE');
					
					$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
					$send['payload'] = $send['title'] = array('초기화면');
					messageQR($send, $eventList['userkey'][$i], 'UPDATE');
				}
			}
		}
	}
//}


/*
for($i=0; $i<count($eventInfo); $i++) {
	$eventArr[$i]['userkey'] = $eventInfo[$i]['userkey'];
	if($eventInfo[$i]['type'] == 'assignment') {
		$eventArr[$i]['text'] = "<" . $eventInfo[$i]['title'] . ">\n과제 내용: " . $eventInfo[$i]['content'] . "\n기한: " . substr($eventInfo[$i]['date1'], 0, 2) . "월 " . substr($eventInfo[$i]['date1'], 2, 2) . "일\n\n입력 시간: " . $eventInfo[$i]['inputTime'];
	}
	else if($eventInfo[$i]['type'] == 'cancel') {
		if($eventInfo[$i]['date2']) {
			$eventArr[$i]['text'] = "<" . $eventInfo[$i]['title'] . ">\n휴강 날짜: " . substr($eventInfo[$i]['date1'], 0, 2) . "월 " . substr($eventInfo[$i]['date1'], 2, 2) . "일부터 " . substr($eventInfo[$i]['date2'], 0, 2) . "월 " . substr($eventInfo[$i]['date2'], 2, 2) . "일까지\n\n입력 시간: " . $eventInfo[$i]['inputTime'];			
		} else {
			$eventArr[$i]['text'] = "<" . $eventInfo[$i]['title'] . ">\n휴강 날짜: " . substr($eventInfo[$i]['date1'], 0, 2) . "월 " . substr($eventInfo[$i]['date1'], 2, 2) . "일\n\n입력 시간: " . $eventInfo[$i]['inputTime'];			
		}
	}
	else if($eventInfo[$i]['type'] == 'exam') {
		$eventArr[$i]['text'] = "<" . $eventInfo[$i]['title'] . ">\n시험 날짜: " . substr($eventInfo[$i]['date1'], 0, 2) . "월 " . substr($eventInfo[$i]['date1'], 2, 2) . "일 / " . substr($eventInfo[$i]['time1'], 0, 2) . "시 " . substr($eventInfo[$i]['time1'], 2, 2) . "분\n\n입력 시간: " . $eventInfo[$i]['inputTime'];			
	}
	$eventArr[$i]['inputTime'] = $eventInfo[$i]['inputTime'];
}

for($i=0; $i<count($eventArr); $i++) {
	$send['text'] = "🎩: " . $eventArr[$i]['text'];
	
	$numOfEventDates = 2;
	for($j=1; $j<=$numOfEventDates; $j++) {
		${eventDate.$j} = mktime(8, 0, 0, (int) substr($eventInfo[$i]['date'.$j], 0, 2), (int) substr($eventInfo[$i]['date'.$j], 2, 2), date("Y"));
		${check1day.$j} = ${eventDate.$j}-$now >= 86400 && ${eventDate.$j}-$now < 86400+60*15;
		${check3day.$j} = ${eventDate.$j}-$now >= 86400 && ${eventDate.$j}-$now < 86400+60*15;
		${check7day.$j} = ${eventDate.$j}-$now >= 86400 && ${eventDate.$j}-$now < 86400+60*15;
		if(${check1day.$j} || ${check3day.$j} || ${check7day.$j}) {
			message($send, $eventArr[$i]['userkey']);
		}
	}
}
*/