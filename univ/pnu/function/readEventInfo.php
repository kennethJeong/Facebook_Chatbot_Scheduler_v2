<?php
function readEventInfo($eventInfo, $type = NULL)
{
	for($i=0; $i<count($eventInfo); $i++) {
		$eventInfoTitle = $eventInfo[$i]['title'];
		$eventInfoInputTime = $eventInfo[$i]['inputTime'];
		if($type == "assignment") {
			if($eventInfo[$i]['type'] == "assignment") {
				$result['title'][] = "<과제 - " . $eventInfo[$i]['title'] . "> - 기한: " . substr($eventInfo[$i]['date1'], 0, 2) . "월 " . substr($eventInfo[$i]['date1'], 2, 2) . "일";
				$result['info'][] = "과제 내용: " . $eventInfo[$i]['content'] . "\n입력시간: " . $eventInfo[$i]['inputTime'];
				$result['payload'][] = $eventInfo[$i]['type'] . "_" . $i . "_" . $eventInfoTitle . "_" . $eventInfoInputTime;
			}
		}
		else if($type == "cancel") {
			if($eventInfo[$i]['type'] == "cancel") {
				$readDateMonth1 = substr($eventInfo[$i]['date1'], 0, 2);
				$readDateDay1 = substr($eventInfo[$i]['date1'], 2, 2);
				$readDateMonth2 = substr($eventInfo[$i]['date2'], 0, 2);
				$readDateDay2 = substr($eventInfo[$i]['date2'], 2, 2);
				
				if(empty($eventInfo[$i]['date2'])) {
					$result['title'][] = "<휴강 - " . $eventInfo[$i]['title'] . ">";
					$result['info'][] = "휴강 날짜: " . $readDateMonth1 . "월 " . $readDateDay1 . "일\n입력시간: " . $eventInfo[$i]['inputTime'];
					$result['payload'][] = $eventInfo[$i]['type'] . "_" . $i . "_" . $eventInfoTitle . "_" . $eventInfoInputTime;
				}
				else if(!empty($eventInfo[$i]['date2'])) {
					$result['title'][] = "<휴강 - " . $eventInfo[$i]['title'] . ">";
					$result['info'][] = "휴강 날짜: " . $readDateMonth1 . "월 " . $readDateDay1 . "일부터 " . $readDateMonth2 . "월 " . $readDateDay2 . "일 까지\n입력시간: " . $eventInfo[$i]['inputTime'];
					$result['payload'][] = $eventInfo[$i]['type'] . "_" . $i . "_" . $eventInfoTitle . "_" . $eventInfoInputTime;
				}
			}
		}
		else if($type == "exam") {
			if($eventInfo[$i]['type'] == "exam") {
				$readDateMonth = substr($eventInfo[$i]['date1'], 0, 2);
				$readDateDay = substr($eventInfo[$i]['date1'], 2, 2);
				$readDateHour = substr($eventInfo[$i]['time1'], 0, 2);
				$readDateMin = substr($eventInfo[$i]['time1'], 2, 2);
			
				$result['title'][] = "<시험 - " . $eventInfo[$i]['title'] . ">";
				$result['info'][] = "시험 일정: " . $readDateMonth . "월 " . $readDateDay . "일 / ". $readDateHour . "시 " . $readDateMin . "분\n입력시간: " . $eventInfo[$i]['inputTime'];
				$result['payload'][] = $eventInfo[$i]['type'] . "_" . $i . "_" . $eventInfoTitle . "_" . $eventInfoInputTime;
			}
		}
	}

	return $result;
}


function writeEvent($readDate, $type)
{
	global $readTitle, $readContent;
	
	if($type == "assignment") {
		$readDateMonth = substr($readDate, 0, 2);
		$readDateDay = substr($readDate, 2, 2);	
		
		if(preg_match("/[0-9]/", $readDate) && (strlen($readDate) == 4) && ($readDateMonth != 00) && ($readDateMonth < 13) && ($readDateDay) != 00 && ($readDateDay < 32)) {
			$writeEvent['text'] = "<" . $readTitle . ">\n기한: " . $readDateMonth . "월 " . $readDateDay . "일\n\n위 내용을 과제 이벤트로 등록하는 것이 맞나요?";
			$writeEvent['date1'] = $readDateMonth . $readDateDay;
			$writeEvent['condition'] = TRUE;
		} else {
			$writeEvent['text'] = "잘못된 형식입니다.\n\n<" . $readTitle .">에 등록할 과제의 기한을 <숫자 4자리>로 다시 입력해주세요.\n예) 10월 16일 -> 1016";
			$writeEvent['condition'] = FALSE;
		}
	}
	else if($type == "cancel") {
		$readDateMonth1 = substr($readDate, 0, 2);
		$readDateDay1 = substr($readDate, 2, 2);
		$readDateMonth2 = substr($readDate, 5, 2);
		$readDateDay2 = substr($readDate, 7, 2);
		
		if(preg_match("/[0-9]/", $readDate) && (strlen($readDate) == 4) && ($readDateMonth1 != 00) && ($readDateMonth1 < 13) && ($readDateDay1) != 00 && ($readDateDay1 < 32)) {
			$writeEvent['text'] = "<" . $readTitle . ">\n날짜: " . $readDateMonth1 . "월 " . $readDateDay1 . "일\n\n위 내용을 휴강 이벤트로 등록하는 것이 맞나요?";
			$writeEvent['date1'] = $readDateMonth1 . $readDateDay1;
			$writeEvent['condition'] = TRUE;
		}
		else if(preg_match("/[0-9](.*)[\/]/", $readDate) && strlen($readDate) == 9 && ($readDateMonth1 != 00) && ($readDateMonth1 < 13) && ($readDateDay1) != 00 && ($readDateDay1 < 32) && ($readDateMonth2 != 00) && ($readDateMonth2 < 13) && ($readDateDay2) != 00 && ($readDateDay2 < 32)) {
			$writeEvent['text'] = "<" . $readTitle . ">\n날짜: " . $readDateMonth1 . "월 " . $readDateDay1 . "일부터 " . $readDateMonth2 . "월 " . $readDateDay2 . "일까지\n\n위 내용을 휴강 이벤트로 등록하는 것이 맞나요?";
			$writeEvent['date1'] = $readDateMonth1 . $readDateDay1;
			$writeEvent['date2'] = $readDateMonth2 . $readDateDay2;
			$writeEvent['condition'] = TRUE;
		} else {
			$writeEvent['text'] = "잘못된 형식입니다.\n\n<" . $readTitle .">에 등록할 휴강 날짜를 <숫자 4자리>로 다시 입력해주세요.\n예) 10월 16일 -> 1016\n\n휴강이 단일이 아닌 복수일(기간)이라면,\n첫날과 마지막날을 슬래쉬(/)를 포함한 <숫자 8자리>로 다시 입력해주세요.\n예) 10월16일부터 10월 23일 -> 1016/1023";
			$writeEvent['condition'] = FALSE;
		}
	}
	else 	if($type == "exam") {
		$readDateMonth = substr($readDate, 0, 2);
		$readDateDay = substr($readDate, 2, 2);
		$readDateHour = substr($readDate, 5, 2);
		$readDateMin = substr($readDate, 7, 2);
		
		if((preg_match("/[0-9](.*)[\/]/", $readDate) && strlen($readDate) == 9) && ($readDateMonth != 00) && ($readDateMonth < 13) && ($readDateDay) != 00 && ($readDateDay < 32) && ($readDateHour < 25) && ($readDateMin < 61)) {
			$writeEvent['text'] = "<" . $readTitle . ">\n날짜: " . $readDateMonth . "월 " . $readDateDay . "일 / ". $readDateHour . "시 " . $readDateMin . "분\n\n위 내용을 시험 이벤트로 등록하는 것이 맞나요?";
			$writeEvent['date1'] = $readDateMonth . $readDateDay;
			$writeEvent['time1'] = $readDateHour . $readDateMin;
			$writeEvent['condition'] = TRUE;
		} else {
			$writeEvent['text']= "잘못된 형식입니다.\n\n<" . $readTitle .">에 등록할 시험 날짜와 시간을\n슬래쉬(/)를 포함한 <숫자 8자리>로 다시 입력해주세요.\n예) 10월 16일 오후 1시반 -> 1016/1330";
			$writeEvent['condition'] = FALSE;
		}				
	}
	
	return $writeEvent;
}

function deleteEvent($eventInfo)
{
	global $senderID;
	
	for($i=0; $i<count($eventInfo); $i++) {
		if($eventInfo[$i]['type'] == "assignment") {
			$deleteEvent['title'][] = "<과제 - " . $eventInfo[$i]['title'] . "> - 기한: " . substr($eventInfo[$i]['date1'], 0, 2) . "월 " . substr($eventInfo[$i]['date1'], 2, 2) . "일";
			$deleteEvent['info'][] =  $eventInfo[$i]['content'];
			$deleteEvent['payload'][] =  "readDelete_" . $eventInfo[$i]['type'] . "_" . $i;
		}
		else	if($eventInfo[$i]['type'] == "cancel") {
			if(empty($eventInfo[$i]['date2'])) {
				$deleteEvent['title'][] = "<휴강 - " . $eventInfo[$i]['title'] . ">";
				$deleteEvent['info'][] =  substr($eventInfo[$i]['date1'], 0, 2) . "월 " . substr($eventInfo[$i]['date1'], 2, 2) . "일";
			}
			else if(!empty($eventInfo[$i]['date2'])) {
				$deleteEvent['title'][] = "<휴강 - " . $eventInfo[$i]['title'] . ">";
				$deleteEvent['info'][] =  substr($eventInfo[$i]['date1'], 0, 2) . "월 " . substr($eventInfo[$i]['date1'], 2, 2) . "일부터 " . substr($eventInfo[$i]['date2'], 0, 2) . "월 " . substr($eventInfo[$i]['date2'], 2, 2) . "일까지";
			}
			$deleteEvent['payload'][] =  "readDelete_" . $eventInfo[$i]['type'] . "_" . $i;
		}
		else 	if($eventInfo[$i]['type'] == "exam") {
			$deleteEvent['title'][] = "<시험 - " . $eventInfo[$i]['title'] . ">";
			$deleteEvent['info'][] =  substr($eventInfo[$i]['date1'], 0, 2) . "월 " . substr($eventInfo[$i]['date1'], 2, 2) . "일 / " . substr($eventInfo[$i]['time1'], 0, 2) . "시 " . substr($eventInfo[$i]['time1'], 2, 2) . "분";
			$deleteEvent['payload'][] =  "readDelete_" . $eventInfo[$i]['type'] . "_" . $i;
		}
	}

	return $deleteEvent;
}