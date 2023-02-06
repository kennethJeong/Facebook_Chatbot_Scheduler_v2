<?php
if(!in_array($today, $yearsSchedule['dayoff'])) {
	// 계절학기 전체기간
	$termOfSeason = (($today >= $semesterW['start'] && $today <= $semesterW['end']) || ($today >= $semesterS['start'] && $today <= $semesterS['end']));
	// 정규학기 전체기간
	$termOfRegular = (($today >= $semester1['start'] && $today <= $semester1['end']) || ($today >= $semester2['start'] && $today <= $semester2['end']));
	// 정규학기 ((개강일 ~ 중간고사 시작일) && (중간고사 종료일 ~ 기말고사 시작일))
	$termOfRegularFromStartToEnd = ((($today >= $semester1['start'] && $today < $semester1['term']['mid']['start']) || ($today > $semester1['term']['mid']['end'] && $today < $semester1['term']['final']['start'])) || (($today >= $semester2['start'] && $today < $semester2['term']['mid']['start']) || ($today > $semester2['term']['mid']['end'] && $today < $semester2['term']['final']['start'])));
	
	if($termOfSeason || ($termOfRegular && $termOfRegularFromStartToEnd)) {
		///////////////////////////////////////////////////////////////////////////////// 출첵 알림 //////////////////////////////////////////////////////////////////////////////////////
		//
		// 첫 응답(5분) 후, 5분때에 (NOTYET)를 선택한 사람들에게 10분 뒤 다시 물어봄
		// 두번째 응답(10분) 후, 15분 뒤 모든 사람들에게 결과만 알려줌
		//
		for($i=0; $i<count($userInfo); $i++) {
			// 이벤트 목록에서 휴강으로 등록한 목록이 있는지 체크
			$query = "SELECT * FROM event WHERE userkey='".$userInfo[$i]['userkey']."' AND year='$thisYear' AND semester='$thisSemester' AND type='cancel' AND title='".$userInfo[$i]['title']."'";
			$sql4event = $conn->query($query);
			while($row4event = $sql4event->fetch_assoc()) {
				$eventCancel[] = $row4event;
			}
			if(!empty($eventCancel)) {
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
			} else {
				$eventCancelResult[] = TRUE;
			}

			if(in_array(FALSE, $eventCancelResult)) {
				continue;
			} else {
				$daily = array('일', '월', '화', '수', '목', '금', '토');
				$numOfDays = count($daily)-1;
				$date = date('w');
				$todayDaily = $daily[$date];
				
				for($j=1; $j<=$numOfDays; $j++) {
					${startTime.$j} = strtotime($userInfo[$i]['time'.$j]);
					${endTime.$j} = strtotime($userInfo[$i]['time'.$j]) + ($userInfo[$i]['min'.$j]*60);
					$after5minFromStart = ${startTime.$j}+5*60;
					$after10minFromStart = ${startTime.$j}+10*60;
					$after15minFromStart = ${startTime.$j}+15*60;
					$end = ${endTime.$j};
					$after5minFromEnd = ${endTime.$j}+5*60;
					
					// 같은 요일의 날짜 중,
					if($userInfo[$i]['day'.$j] == $todayDaily) {
						$textAttendanceArr = array("출첵함?", "출첵했나?", "출첵했낭", "출첵했어..?", "ㅊㅊ함?", "출첵해써?", "ㅊㅊㅎ?", "출첵했니..?");
						shuffle($textAttendanceArr);
						$textYesArr = array("ㅇㅇ", "ㅇㅇ했음", "함ㅋㅋ", "ㅇㅇ함", "했다ㅋㅋ", "했음ㅋㅋ", "했지ㅋㅋ", "함 ㅂ2", "해씀ㅋㅋ", "당연", "벌써함ㅋㅋ", "ㅋㅋ빠염");
						shuffle($textYesArr);
						$textNotYetArr = array("ㄴㄴ아직", "아직ㄴㄴ", "아직 안함ㅋㅋ", "ㄴㄴ아직인듯?");
						shuffle($textNotYetArr);
						$textIdontknowArr = array("멀라?", "나도 몰라ㅋㅋ", "나도몰라?", "나도 모름ㅋㅋ", "멀라ㅋㅋ");
						shuffle($textIdontknowArr);
						$textNoArr = array("ㄴㄴ", "ㄴㄴ안함", "ㄴㄴ안해씀", "안해씀", "안함", "안함ㅋㅋ", "안해씀ㅋㅋ", "안했음ㅋㅋ", "안해따ㅋㅋ", "안했음", "안함요");
						shuffle($textNoArr);

						// 5분 후, 출첵 여부 확인 푸시 전송
						if($now == $after5minFromStart) {
							$send['text'] = "🎩: " . $userInfo[$i]['title'] . " " . $textAttendanceArr[0];
							$send['title'] = array('⭕'.$textYesArr[0], '✋'.$textNotYetArr[0], '❓'.$textIdontknowArr[0]);
					
							$payloadInfos = $userInfo[$i]['title'] . "_" . $userInfo[$i]['class'] . "_" . $userInfo[$i]['prof'] . "_" . $todayDaily . "_" . $userInfo[$i]['time'.$j];
							$send['payload'] = array("Attendance_YES_".$payloadInfos, "Attendance_NOTYET_".$payloadInfos, "Attendance_IDONTKNOW_".$payloadInfos);
							
							messageQR($send, $userInfo[$i]['userkey']);
						}
						// 첫 응답(5분) 후, 5분 뒤에 (NOTYET)를 선택한 사람들에게 10분 뒤 다시 물어봄
						else if($now == $after10minFromStart) {
							$query = "SELECT userkey FROM user WHERE year='$thisYear' AND semester='$thisSemester' AND userkey NOT IN
												(
													SELECT userkey FROM attendance WHERE (attend='YES' OR attend='IDONTKNOW') 
														AND UNIX_TIMESTAMP(inputTime) >= ". ($now-10*60) . " AND UNIX_TIMESTAMP(inputTime) <= " . $now . "
												)";
							$sql4attendance = $conn->query($query);
							while($row4attendance = $sql4attendance->fetch_assoc()) {
								$attendanceUserkeys[] = $row4attendance['userkey'];		//  (YES, IDONTKNOW)를 선택한 사람을 제외한 모두
							}
							
							if(!in_array($userInfo[$i]['userkey'], $attendanceUserkeys)) {
								continue;
							} else {
								$send['text'] = "🎩: " . $userInfo[$i]['title'] . " " . $textAttendanceArr[0];
								$send['title'] = array('⭕'.$textYesArr[0], '✋'.$textNotYetArr[0], '❓'.$textIdontknowArr[0]);
						
								$payloadInfos = $userInfo[$i]['title'] . "_" . $userInfo[$i]['class'] . "_" . $userInfo[$i]['prof'] . "_" . $todayDaily . "_" . $userInfo[$i]['time'.$j];
								$send['payload'] = array("Attendance_YES_".$payloadInfos, "Attendance_NOTYET_".$payloadInfos, "Attendance_IDONTKNOW_".$payloadInfos, "Attendance_NO_".$payloadInfos);
								
								messageQR($send, $userInfo[$i]['userkey']);
							}
						}
						// 두번째 응답(10분) 후, 15분 뒤에 모든 사람들에게 결과만 알려줌
						else if($now == $after15minFromStart) {
							$query = "SELECT userkey FROM user WHERE year='$thisYear' AND semester='$thisSemester' AND userkey IN
												(
													SELECT userkey FROM attendance WHERE UNIX_TIMESTAMP(inputTime) >= ". ($now-15*60) . " AND UNIX_TIMESTAMP(inputTime) <= " . $now . "
												)";
							$sql4attendance = $conn->query($query);
							while($row4attendance = $sql4attendance->fetch_assoc()) {
								$attendanceUserkeys[] = $row4attendance['userkey'];		// 모두
							}
							
							if(!in_array($userInfo[$i]['userkey'], $attendanceUserkeys)) {
								continue;
							} else {
								// 같은 수업 듣는 사람들의 총 인원 수
								$query = "SELECT * FROM user WHERE year='$thisYear' AND semester='$thisSemester'
																						AND title='{$userInfo[$i]['title']}' AND class='{$userInfo[$i]['class']}'
																						AND prof='{$userInfo[$i]['prof']}' AND day" . $j . "='{$todayDaily}' AND time" . $j . "='{$userInfo[$i]['time'.$j]}'";
								$sql4user = $conn->query($query);
								while($row4user = $sql4user->fetch_assoc()) {
									$wholeUserkeys[] = $row4user;
								}	
								$numOfUserkeys = count($wholeUserkeys);
								
								// YES 라고 답한 사람들 수
								$query = "SELECT * FROM user WHERE year='$thisYear' AND semester='$thisSemester'
																						AND title='{$userInfo[$i]['title']}' AND class='{$userInfo[$i]['class']}'
																						AND prof='{$userInfo[$i]['prof']}' AND day" . $j . "='{$todayDaily}' AND time" . $j . "='{$userInfo[$i]['time'.$j]}'
																						AND userkey IN
																						(
																							SELECT userkey FROM attendance WHERE attend='YES'
																								AND UNIX_TIMESTAMP(inputTime) >= ". ($now-15*60) . " AND UNIX_TIMESTAMP(inputTime) <= " . $now . "
																						)";
								$sql4attendanceYes = $conn->query($query);
								while($row4attendanceYes = $sql4attendanceYes->fetch_assoc()) {
									$attendanceYesUserkeys[] = $row4attendanceYes;		//  (YES)를 선택한 사람 모두
								}
								$numOfAttendanceYes = count($attendanceYesUserkeys);
								
								if($numOfAttendanceYes != 0) {
									$send['text'] = "🎩: " . $userInfo[$i]['title'] . " 듣는 사람이 " . $numOfUserkeys . "명 인데,\n그 중에 " . $numOfAttendanceYes . "명이 출첵했다카는데?";
									$send['payload'] = $send['title'] = array('초기화면');
									messageQR($send, $userInfo[$i]['userkey']);
								} else {
									// 휴강인지 체크해보도록 유도
									$query = "INSERT INTO logging (userkey, year, semester, inProgress, inputTime) VALUE ('{$userInfo[$i]['userkey']}', '$thisYear', '$thisSemester', 'READ', '$inputTime')";
									$conn->query($query);
									$query = "INSERT INTO loggingRead (userkey, year, semester, inProgress, title, inputTime) VALUE ('{$userInfo[$i]['userkey']}', '$thisYear', '$thisSemester', 'READ', '{$userInfo[$i]['title']}', '$inputTime')";
									$conn->query($query);
									
									$send['text'] = "🎩: " . $userInfo[$i]['title'] . " 듣는 사람들 중에 출첵했다카는 사람이 없는데..\n다른 사람들이 휴강이라고 했는지 확인해볼래?";
									message($send, $userInfo[$i]['userkey']);
									
									$query = "SELECT * FROM user WHERE userkey=".$userInfo[$i]['userkey']." AND year='$thisYear' AND semester='$thisSemester'";
									$sql4user = $conn->query($query);
									while($row4user = $sql4user->fetch_assoc()) {
										$userInfos[] = $row4user;
									}	
									
									$rgstedInfoDetail = registedConditionSubjectDetail($userInfos);
									for($i=0; $i<count($rgstedInfoDetail['title']); $i++) {
										$title = $rgstedInfoDetail['titleName'][$i];
										$class = $rgstedInfoDetail['class'][$i];
										$prof = $rgstedInfoDetail['prof'][$i];
										$send['title'][] = $rgstedInfoDetail['title'][$i];
										$send['subtitle'][] = $rgstedInfoDetail['info'][$i];
										$send['payload'][] = array("assignment_{$title}_{$class}_{$prof}", "cancel_{$title}_{$class}_{$prof}", "exam_{$title}_{$class}_{$prof}");
										
										for($j=0; $j<count($eventInfo); $j++) {
											if($eventInfo[$j]['title'] == $title) {
												$eventInfoTypes[$i][$j] = $eventInfo[$j]['type'];
											}
										}
										$countTypes = array_count_values($eventInfoTypes[$i]);
										$send['buttonsTitle'][$i] = array();
										$countTypes['assignment'] > 0 ? array_push($send['buttonsTitle'][$i], "과제({$countTypes['assignment']}개)") : array_push($send['buttonsTitle'][$i], "과제");
										$countTypes['cancel'] > 0 ? array_push($send['buttonsTitle'][$i], "휴강({$countTypes['cancel']}개)") : array_push($send['buttonsTitle'][$i], "휴강");
										$countTypes['exam'] > 0 ? array_push($send['buttonsTitle'][$i], "시험({$countTypes['exam']}개)") : array_push($send['buttonsTitle'][$i], "시험");
									}
									messageTemplateLeftSlide($send, $userInfo[$i]['userkey']);
								}
							}
						}
						/*
						// 수업 마쳤을 때, (NOT YET)이라고 답한 사람들에게 물어봄
						else if($now == $end) {
							$query = "SELECT userkey FROM user WHERE userkey IN
												(
													SELECT userkey FROM attendance WHERE attend='NOTYET'
														AND UNIX_TIMESTAMP(inputTime) >= ". ($now-$userInfo[$i]['min'.$j]*60) . " AND UNIX_TIMESTAMP(inputTime) <= " . $now . "
												)";
							$sql4attendance = $conn->query($query);
							while($row4attendance = $sql4attendance->fetch_assoc()) {
								$attendanceUserkeys[] = $row4attendance['userkey'];		//  (NOTYET)을 선택한 사람 모두
							}
							
							if(!in_array($userInfo[$i]['userkey'], $attendanceUserkeys)) {
								continue;
							} else {
								$send['text'] = "🎩: " . $userInfo[$i]['title'] . " " . $textAttendanceArr[0];
								$send['title'] = array('⭕'.$textYesArr[0], '❌'.$textNoArr[0]);
						
								$payloadInfos = $userInfo[$i]['title'] . "_" . $userInfo[$i]['class'] . "_" . $userInfo[$i]['prof'] . "_" . $todayDaily . "_" . $userInfo[$i]['time'.$j];
								$send['payload'] = array("Attendance_YES_".$payloadInfos, "Attendance_NO_".$payloadInfos);
								
								messageQR($send, $userInfo[$i]['userkey']);					
							}		
						}
						// 수업 마치고 5분 후, (IDONTKNOW || 응답X)를 선택한 사람들에게 결과를 알려줌
						else if($now == $after5minFromEnd) {
							$query = "SELECT userkey FROM user WHERE userkey NOT IN 
												(
													SELECT userkey FROM attendance WHERE attend='YES' OR attend='NOTYET'
														AND UNIX_TIMESTAMP(inputTime) >= ". ($now-$userInfo[$i]['min'.$j]*60 ) . " AND UNIX_TIMESTAMP(inputTime) <= " . $now . "
												)";
							$sql4attendance = $conn->query($query);
							while($row4attendance = $sql4attendance->fetch_assoc()) {
								$attendanceUserkeys[] = $row4attendance['userkey'];		//  (YES or NOTYET)을 선택한 사람을 제외한 모두
							}
							
							if(!in_array($userInfo[$i]['userkey'], $attendanceUserkeys)) {
								continue;
							} else {
								// 같은 수업 듣는 사람들의 총 인원 수
								$query = "SELECT * FROM user WHERE title='{$userInfo[$i]['title']}' AND class='{$userInfo[$i]['class']}'
																						AND prof='{$userInfo[$i]['prof']}' AND day" . $j . "='{$todayDaily}' AND time" . $j . "='{$userInfo[$i]['time'.$j]}'";
								$sql4user = $conn->query($query);
								while($row4user = $sql4user->fetch_assoc()) {
									$wholeUserkeys[] = $row4user;
								}	
								$numOfUserkeys = count($wholeUserkeys);
								
								// YES 라고 답한 사람들 수
								$query = "SELECT * FROM user WHERE title='{$userInfo[$i]['title']}' AND class='{$userInfo[$i]['class']}'
																						AND prof='{$userInfo[$i]['prof']}' AND day" . $j . "='{$todayDaily}' AND time" . $j . "='{$userInfo[$i]['time'.$j]}'
																						AND userkey IN
																						(
																							SELECT userkey FROM attendance WHERE attend='YES'
																								AND UNIX_TIMESTAMP(inputTime) >= ". ($now-$userInfo[$i]['min'.$j]*60) . " AND UNIX_TIMESTAMP(inputTime) <= " . $now . "
																						)";
								$sql4attendanceYes = $conn->query($query);
								while($row4attendanceYes = $sql4attendanceYes->fetch_assoc()) {
									$attendanceYesUserkeys[] = $row4attendanceYes;		//  (YES)를 선택한 사람 모두
								}
								$numOfAttendanceYes = count($attendanceYesUserkeys);
								
								if($numOfAttendanceYes != 0) {
									$send['text'] = "🎩: " . $userInfo[$i]['title'] . " 듣는 사람이 " . $numOfUserkeys . "명 인데,\n그 중에" . $numOfAttendanceYes . "명이 출첵했다카는데?";
									$send['payload'] = $send['title'] = array('초기화면');
									messageQR($send, $userInfo[$i]['userkey']);						
								} else {
									// 휴강인지 체크해보도록 유도
									$query = "INSERT INTO logging (userkey, inProgress, inputTime) VALUE ('{$userInfo[$i]['userkey']}', 'READ_EVENT', '$inputTime')";
									$conn->query($query);
									$query = "INSERT INTO loggingRead (userkey, inProgress, title, inputTime) VALUE ('{$userInfo[$i]['userkey']}', 'READ_EVENT', '{$userInfo[$i]['title']}', '$inputTime')";
									$conn->query($query);
									
									$send['text'] = "🎩: " . $userInfo[$i]['title'] . " 듣는 사람들 중에 출첵했다카는 사람이 없는데..\n다른 사람들이 휴강이라고 했는지 확인해볼래?";
									$send['payload'] = array('attendance_'.$userInfo[$i]['title'] ,'초기화면');
									$send['title'] = array($userInfo[$i]['title'].' 휴강인지 확인하기', '초기화면');
									messageQR($send, $userInfo[$i]['userkey']);
								}
							}				
						}*/
					}
				}
			}	
		}		
	}
}

// 만약 3번 수업 연속 "출첵을 안했다"를 
