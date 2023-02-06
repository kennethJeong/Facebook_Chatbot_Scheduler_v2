<?php
// 등록 진행 과정
$query = "SELECT * FROM processing WHERE userkey='$senderID' AND year='$thisYear' AND semester='$thisSemester'";
$registerProcessing = $conn->query($query)->fetch_assoc();
$rgstInsert = $registerProcessing['rgstInsert'];
$rgstGeneralSelc = $registerProcessing['rgstGeneralSelc'];
$rgstMajor = $registerProcessing['rgstMajor'];
$rgstMajorBasic = $registerProcessing['rgstMajorBasic'];
$rgstLiberal = $registerProcessing['rgstLiberal'];
$rgstLiberalEssn = $registerProcessing['rgstLiberalEssn'];
// 등록 진행 과정 - 합계
$processingAllCount = $rgstInsert + $rgstGeneralSelc + $rgstMajor + $rgstMajorBasic + $rgstLiberal + $rgstLiberalEssn;

// inProgress for latest access time
$query = "SELECT inputTime FROM logging WHERE userkey='$senderID' AND year='$thisYear' AND semester='$thisSemester' ORDER BY inputTime DESC LIMIT 1";
$sql4loggingInputTime = $conn->query($query)->fetch_assoc();
$latestInputTime = $sql4loggingInputTime['inputTime'];
$latestAccessTime = (strtotime($inputTime) - strtotime($latestInputTime)) / 3600;

// inProgress
$query = "SELECT inProgress FROM logging WHERE userkey='$senderID' AND year='$thisYear' AND semester='$thisSemester' ORDER BY inputTime DESC LIMIT 1";
$sql4loggingInProgress = $conn->query($query)->fetch_assoc();
$inProgress = $sql4loggingInProgress['inProgress'];

// inProgress for Read
$query = "SELECT inProgress FROM loggingRead WHERE userkey='$senderID' AND year='$thisYear' AND semester='$thisSemester' ORDER BY inputTime DESC LIMIT 1";
$sql4loggingInProgressRead = $conn->query($query)->fetch_assoc();
$inProgressRead = $sql4loggingInProgressRead['inProgress'];

// 유저 이름
$getSenderFullName = json_decode(curlGet("https://graph.facebook.com/v2.6/" . $senderID . "?fields=first_name,last_name,profile_pic,locale,timezone,gender&access_token="), true);
if(isset($getSenderFullName['last_name']) && isset($getSenderFullName['first_name'])) {
	$senderFullName = $getSenderFullName['last_name'] . $getSenderFullName['first_name'];
}

// 등록된 유저 정보
$query = "SELECT * FROM user WHERE userkey='$senderID' AND year='$thisYear' AND semester='$thisSemester'";
$sql4user = $conn->query($query);
while($row4user = $sql4user->fetch_assoc()) {
	$userInfo[] = $row4user;
}
// 등록된 이벤트 정보
$query = "SELECT * FROM event WHERE userkey='$senderID' AND year='$thisYear' AND semester='$thisSemester'";
$sql4event = $conn->query($query);
while($row4event = $sql4event->fetch_assoc()) {
	$eventDate1 = date("Y-m-d", mktime(0, 0, 0, (int)substr($row4event['date1'], 0, 2), (int)substr($row4event['date1'], 2, 4), date("Y")));
	$eventDate2 = date("Y-m-d", mktime(0, 0, 0, (int)substr($row4event['date2'], 0, 2), (int)substr($row4event['date2'], 2, 4), date("Y")));
	$nowDate = date("Y-m-d", strtotime($inputTime));
	if((empty($row4event['date2']) && $eventDate1 >= $nowDate) || (!empty($row4event['date2']) && $eventDate2 >= $nowDate)) {
		$eventInfo[] = $row4event;
	}
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if($payload || $payloadQR || $messageText) {
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
	
	if($semesterRegular1 === FALSE && $semesterRegular2 === FALSE && $semesterSeasonS === FALSE && $semesterSeasonW === FALSE) {
		//
		// Freezing
		//
		$datesOfStartPerSemester = array($semester1['start'], $semesterS['start'], $semester2['start'], $semesterW['start']);
		for($i=0; $i<count($datesOfStartPerSemester); $i++) {
			$datesDiff = (strtotime($datesOfStartPerSemester[$i]) - strtotime($today));
			if($datesDiff > 0) {
				$datesDiffs[] = $datesDiff / (60*60*24);
			} else {
				$datesDiffs[] = 999;
			}
		}
		foreach($datesDiffs as $k=>$v) {
			if($v == min($datesDiffs)) {
				if($k == 0) {
					$semesterKR = "1학기";
				}
				else if($k == 1) {
					$semesterKR = "여름계절학기";
				}
				else if($k == 2) {
					$semesterKR = "2학기";
				}
				else if($k == 3) {
					$semesterKR = "겨울계절학기";
				}
				$semesterKR .= "(" . date("m", strtotime($datesOfStartPerSemester[$k])) . "월 " . date("d", strtotime($datesOfStartPerSemester[$k])). "일)";
			}
		}
		
		$send['text'] = "🎩: {$senderFullName}님 반갑습니다.\n\n지금은 다음 학기 서비스를 위한 프로그램 준비 기간입니다.\n" . $semesterKR . "에 서비스가 시작됩니다.";
		message($send);

	} else {
		if($payload == "시작하기" || $payload == "초기화면" || $payloadQR == "초기화면" || preg_match("/^시작/", $messageText) || preg_match("/^ㄱ/", $messageText)) {
			if(!isset($userInfo)) {
				if(!isset($registerProcessing)) {
					$query = insertProcessing();
					$conn->query($query);

					$query = queryInsert('logging', 'START');
					$conn->query($query);
										
					$send['text'] = "🎩: 새로운 유저시군요!\n튜토리얼을 통해 {$senderFullName}님의 교과목을 등록해볼까요?";
				} else {
					$send['text'] = "🎩: 교과목을 하나 이상 등록하면 추가 기능이 활성화됩니다.\n튜토리얼을 통해 {$senderFullName}님의 교과목을 등록해볼까요?";
				}
				message($send);
				
				$send['title'] = $send['buttonsTitle'] = $send['payload'] = array("튜토리얼 시작하기");
				$imagePath = 'https://bhandy.kr/scheduler/univ/pnu/images/';
				$send['imageURL'] = array($imagePath.'tutorial.jpg');
				messageTemplateLeftSlideWithImage($send);
			}
			else if(isset($userInfo)) {
				// 초기화
				$query = resetProcessing();
				$conn->query($query);	
			
				$query = queryInsert('logging', 'START');
				$conn->query($query);
				
				$send['text'] = "🎩: {$senderFullName}님 반갑습니다.";
				message($send);
				
				$send['title'] = $send['buttonsTitle'] = $send['payload'] = array("내가 등록한 정보 보기", "교과목 등록하기");
				$imagePath = 'https://bhandy.kr/scheduler/univ/pnu/images/';
				$send['imageURL'] = array($imagePath.'notepad.jpg', $imagePath.'register.jpg');
				messageTemplateLeftSlideWithImage($send);
			}
		}
		else if(preg_match("/^튜토리얼/", $payload) || preg_match("/^교과목(.*)등록/", $payload) || preg_match("/^교과목(.*)추가(.*)등록/", $payloadQR) || preg_match("/등록한(.*)정보(.*)보기/", $payload)) {
			// 초기화
			$query = resetProcessing();
			$conn->query($query);
			
			if(preg_match("/^튜토리얼/", $payload)) {
				if($thisCourse[strlen($thisCourse)-1] == "W" || $a[strlen($thisCourse)-1] == "S") {
					$query = updateProcessing('insert');
					$conn->query($query);
					$query = queryInsert('logging', 'REGISTER_INSERT');
					$conn->query($query);
					
					$send['text'] = "🎩: 교과목 등록을 시작합니다.\n{$senderFullName}님이 수강 중인 교과목명을 입력해주세요.";
					message($send);
				} else {
					$query = queryInsert('logging', 'REGISTER');
					$conn->query($query);
					
					$send['text'] = "🎩: 교과목 등록을 시작합니다.\n{$senderFullName}님이 수강 중인 교과목의 과목 구분을 선택해주세요.";
					message($send);
						
					$send['elementsTitle'] = "과목 구분";
					$send['elementsButtonsTitle'] = $dbAllDivs = getCourseColumnData($thisCourse, 'divs');
					array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
					messageTemplate($send);
				}
			}
			else if(preg_match("/^교과목(.*)등록/", $payload) || preg_match("/^교과목(.*)추가(.*)등록/", $payloadQR)) {
				if($thisCourse[strlen($thisCourse)-1] == "W" || $a[strlen($thisCourse)-1] == "S") {
					$query = updateProcessing('insert');
					$conn->query($query);
					$query = queryInsert('logging', 'REGISTER_INSERT');
					$conn->query($query);
					
					if(isset($userInfo)) {
						$rgstedInfo = registedConditionSubject($userInfo);
						isset($rgstedInfo) ? $rgstedInfo = implode("\n", $rgstedInfo) : "";
			
						$send['text'] = "<교과목 등록 현황>\n" . $rgstedInfo . "\n\n총 " . count($userInfo) . "과목";
						message($send);
					}
					$send['text'] = "🎩: 교과목 등록을 시작합니다.\n검색하고자하는 교과목명을 입력해주세요.";
					message($send);
				} else {
					$query = queryInsert('logging', 'REGISTER');
					$conn->query($query);
				
					if(!isset($userInfo)) {
						$send['text'] = "🎩: 교과목 등록을 시작합니다.\n과목 구분을 선택해주세요.";
						message($send);
							
						$send['elementsTitle'] = "과목 구분";
						$send['elementsButtonsTitle'] =  $dbAllDivs = getCourseColumnData($thisCourse, 'divs');
						array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
						messageTemplate($send);
					}
					else if(isset($userInfo)) {
						$rgstedInfo = registedConditionSubject($userInfo);
						isset($rgstedInfo) ? $rgstedInfo = implode("\n", $rgstedInfo) : "";
			
						$send['text'] = "<교과목 등록 현황>\n" . $rgstedInfo . "\n\n총 " . count($userInfo) . "과목";
						message($send);
									
						$send['elementsTitle'] = "과목 구분";
						$send['elementsButtonsTitle'] =  $dbAllDivs = getCourseColumnData($thisCourse, 'divs');
						array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
						messageTemplate($send);
					}			
				}
				$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
				$send['payload'] = $send['title'] = array('초기화면');
				messageQR($send);
			}
			else if(preg_match("/등록한(.*)정보(.*)보기/", $payload)) {
				$query = queryInsert('logging', 'READ');
				$conn->query($query);
				
				// check -> inProgress='READ_TUTORIAL_FIN'
				$query = "SELECT inProgress FROM loggingRead WHERE userkey='$senderID' AND inProgress='READ_TUTORIAL_FIN'";
				$readTutorialFin = $conn->query($query)->fetch_assoc();
				
				if(!$readTutorialFin) {
					$query = queryInsert('loggingRead', 'READ_TUTORIAL');
					$conn->query($query);
					
					$send['text'] = "🎩: 교과목을 등록하셨군요!\n그럼 이번에는 JeongEunhu님이 등록하신 교과목을 살펴볼까요?\n\n(경고! ❌를 선택하면 [내가 등록한 정보 보기]에 관한 튜토리얼이 생략되고, 다시는 튜토리얼을 진행할 수 없습니다.)";
					$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
					messageQR($send);
				} else {
					$query = queryInsert('loggingRead', 'READ');
					$conn->query($query);
					
					$rgstedInfoDetail = registedConditionSubjectDetail($userInfo);
					for($i=0; $i<count($rgstedInfoDetail['title']); $i++) {
						$title = $rgstedInfoDetail['titleName'][$i];
						$class = $rgstedInfoDetail['class'][$i];
						$prof = $rgstedInfoDetail['prof'][$i];
						$send['title'][] = $rgstedInfoDetail['title'][$i];
						$send['subtitle'][] = $rgstedInfoDetail['info'][$i];
						$send['payload'][] = array("assignment_{$title}_{$class}_{$prof}", "cancel_{$title}_{$class}_{$prof}", "exam_{$title}_{$class}_{$prof}");
						
						$eventInfoTypes[$i] = array();
						for($j=0; $j<count($eventInfo); $j++) {
							if($eventInfo[$j]['title'] == $title) {
								$eventInfoTypes[$i][$j] = $eventInfo[$j]['type'];
							}
						}
						$countTypes = array_count_values($eventInfoTypes[$i]);
						$send['buttonsTitle'][$i] = array();
						is_array($countTypes) && $countTypes['assignment'] > 0 ? array_push($send['buttonsTitle'][$i], "과제({$countTypes['assignment']}개)") : array_push($send['buttonsTitle'][$i], "과제");
						is_array($countTypes) && $countTypes['cancel'] > 0 ? array_push($send['buttonsTitle'][$i], "휴강({$countTypes['cancel']}개)") : array_push($send['buttonsTitle'][$i], "휴강");
						is_array($countTypes) && $countTypes['exam'] > 0 ? array_push($send['buttonsTitle'][$i], "시험({$countTypes['exam']}개)") : array_push($send['buttonsTitle'][$i], "시험");
					}
					messageTemplateLeftSlide($send);
					
					$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
					$send['payload'] = $send['title'] = array('초기화면', '시간표 보기', '교과목 삭제하기');
					messageQR($send);
				}
			}
		}
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////////////////// 시간표 보기 ///////////////////////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		else if($payloadQR == "시간표 보기") {
			$send['text'] = "🎩: 이미지를 생성 중입니다. 잠시만 기다려주세요.";
			message($send);
			
			// 시간표 이미지 생성 경로
			$mkTTpath = $_SERVER["DOCUMENT_ROOT"] . '/scheduler/univ/pnu/timetable';
			// 시간표 이미지 생성
			mkTT($senderID, $mkTTpath);
		
			$ttImagePath = 'https://bhandy.kr/scheduler/univ/pnu/timetable/image/tt_'.$thisYear.$thisSemester.'_'.$senderID.'.jpg';
			
			$send['img']['url'] = $ttImagePath;
			messageImage($send);
			
			if($inProgress == "START") {
				$send['text'] = "🎩: 계속해서 진행해주세요.";
				$send['payload'] = $send['title'] = array("교과목 추가 등록", "시간표 보기", "초기화면");
				messageQR($send);
			}
			else if($inProgress == "READ") {
				$rgstedInfoDetail = registedConditionSubjectDetail($userInfo);
				for($i=0; $i<count($rgstedInfoDetail['title']); $i++) {
					$title = $rgstedInfoDetail['titleName'][$i];
					$class = $rgstedInfoDetail['class'][$i];
					$prof = $rgstedInfoDetail['prof'][$i];
					$send['title'][] = $rgstedInfoDetail['title'][$i];
					$send['subtitle'][] = $rgstedInfoDetail['info'][$i];
					$send['payload'][] = array("assignment_{$title}_{$class}_{$prof}", "cancel_{$title}_{$class}_{$prof}", "exam_{$title}_{$class}_{$prof}");
					
					$eventInfoTypes[$i] = array();
					for($j=0; $j<count($eventInfo); $j++) {
						if($eventInfo[$j]['title'] == $title) {
							$eventInfoTypes[$i][$j] = $eventInfo[$j]['type'];
						}
					}
					$countTypes = array_count_values($eventInfoTypes[$i]);
					$send['buttonsTitle'][$i] = array();
					is_array($countTypes) && $countTypes['assignment'] > 0 ? array_push($send['buttonsTitle'][$i], "과제({$countTypes['assignment']}개)") : array_push($send['buttonsTitle'][$i], "과제");
					is_array($countTypes) && $countTypes['cancel'] > 0 ? array_push($send['buttonsTitle'][$i], "휴강({$countTypes['cancel']}개)") : array_push($send['buttonsTitle'][$i], "휴강");
					is_array($countTypes) && $countTypes['exam'] > 0 ? array_push($send['buttonsTitle'][$i], "시험({$countTypes['exam']}개)") : array_push($send['buttonsTitle'][$i], "시험");
				}
				messageTemplateLeftSlide($send);
				
				$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
				$send['payload'] = $send['title'] = array('초기화면', '시간표 보기', '교과목 삭제하기');
				messageQR($send);
			}
		}
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		/////////////////////////////////////////////////////////////////////////////////////////// 푸시 알림 ///////////////////////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		
		/////////////////////////////////////////////////////////////////////////////////////////// 출첵 확인 ///////////////////////////////////////////////////////////////////////////////////////////
		else if(preg_match("/^Attendance/", $payloadQR)) {
			$payloadInfos = explode("_",$payloadQR);
			$payloadAttend = $payloadInfos[1];
			$payloadTitle = $payloadInfos[2];
			$payloadClass = $payloadInfos[3];
			$payloadProf = $payloadInfos[4];
			$payloadDay = $payloadInfos[5];
			$payloadTime = $payloadInfos[6];
			
			$query = "INSERT INTO attendance (userkey, year, semester, attend, title, class, prof, day, time, inputTime)
														VALUE ('$senderID', '$thisYear', '$thisSemester', '$payloadAttend', '$payloadTitle', '$payloadClass', '$payloadProf', '$payloadDay', '$payloadTime', '$inputTime')";
			$conn->query($query);
			
			if($payloadAttend == "YES") {
				$textArr = array("아..?", "개망..", "아 망했네..", "쉣", "ㅠㅠ", "헐ㅠㅠ", "");	
			}
			else if($payloadAttend == "NOTYET" || $payloadAttend == "IDONTKNOW") {
				$textArr = array("어키", "어키여", "오키", "알게씀ㅇㅇ", "ㅇㅋ", "알게따ㅎㅎ");			
			}
			shuffle($textArr);
			$send['text'] = "🎩: " . $textArr[0];
			$send['payload'] = $send['title'] = array('초기화면');
			messageQR($send);
		}
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		/////////////////////////////////////////////////////////////////////////////////////////// 이전으로 ////////////////////////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		else if($payloadQR == "이전으로" || preg_match("/^취소/", $messageText) || preg_match("/^이전/", $messageText)) {
			if(preg_match("/^START$/", $inProgress) || preg_match("/^REGISTER$/", $inProgress) || (preg_match("/^READ$/", $inProgress) && preg_match("/^READ$/", $inProgressRead))) {
				$query = resetProcessing();
				$conn->query($query);
			
				if(!isset($userInfo)) {
					$query = queryInsert('logging', 'START');
					$conn->query($query);
					
					if(!isset($registerProcessing)) {
						$query = insertProcessing();
						$conn->query($query);
						
						$send['text'] = "🎩: 새로운 유저시군요!\n튜토리얼을 통해 {$senderFullName}님의 교과목을 등록해볼까요?";
					} else {
						$send['text'] = "🎩: 교과목을 하나 이상 등록하면 추가 기능이 활성화됩니다.\n튜토리얼을 통해 {$senderFullName}님의 교과목을 등록해볼까요?";
					}
					message($send);
					
					$send['title'] = $send['buttonsTitle'] = $send['payload'] = array("튜토리얼 시작하기");
					$imagePath = 'https://bhandy.kr/scheduler/univ/pnu/images/';
					$send['imageURL'] = array($imagePath.'tutorial.jpg');
					messageTemplateLeftSlideWithImage($send);
				}
				else if(isset($userInfo)) {
					$query = queryInsert('logging', 'START');
					$conn->query($query);
					
					$send['text'] = "🎩: {$senderFullName}님 반갑습니다.";
					message($send);
					
					$send['title'] = $send['buttonsTitle'] = $send['payload'] = array("내가 등록한 정보 보기", "교과목 등록하기");
					$imagePath = 'https://bhandy.kr/scheduler/univ/pnu/images/';
					$send['imageURL'] = array($imagePath.'notepad.jpg', $imagePath.'register.jpg');
					messageTemplateLeftSlideWithImage($send);
				}
			}
			else if(preg_match("/^REGISTER/", $inProgress)) {
				// values for searching	
				$query = "SELECT * FROM logging WHERE userkey='$senderID' AND year='$thisYear' AND semester='$thisSemester' ORDER BY inputTime DESC LIMIT 1";
				$sql4loggingSearch = $conn->query($query);
				while($row4loggingSearch = $sql4loggingSearch->fetch_assoc()) {
					$searchWord = $row4loggingSearch['searchWord'];
					$searchTitle = $row4loggingSearch['searchTitle'];
					$searchMajor = $row4loggingSearch['searchMajor'];
					$searchGrade = $row4loggingSearch['searchGrade'];
					$searchFields = $row4loggingSearch['searchFields'];
				}
				
				// 이전 검색 정보
				$query = "SELECT DISTINCT searchMajor FROM logging WHERE userkey='$senderID' AND year='$thisYear' AND semester='$thisSemester' ORDER BY inputTime DESC";
				$sql4loggingSearchMajor = $conn->query($query);
				while($row4loggingSearchMajor = $sql4loggingSearchMajor->fetch_assoc()) {
					if(!empty($row4loggingSearchMajor["searchMajor"]) && $row4loggingSearchMajor["searchMajor"] != "") {
						$previousSearchMajor[] = $row4loggingSearchMajor["searchMajor"];
					}
				}
				
				if(preg_match("/INSERT/", $inProgress) && $processingAllCount == 1 && $rgstInsert == 1) {
					if(preg_match("/INSERT$/", $inProgress)) {
						// 초기화
						$query = resetProcessing();
						$conn->query($query);
						if($thisCourse[strlen($thisCourse)-1] == "W" || $a[strlen($thisCourse)-1] == "S") {
							$query = updateProcessing('insert');
							$conn->query($query);
							$query = queryInsert('logging', 'REGISTER_INSERT');
							$conn->query($query);
							
							if(isset($userInfo)) {
								$rgstedInfo = registedConditionSubject($userInfo);
								isset($rgstedInfo) ? $rgstedInfo = implode("\n", $rgstedInfo) : "";
					
								$send['text'] = "<교과목 등록 현황>\n" . $rgstedInfo . "\n\n총 " . count($userInfo) . "과목";
								message($send);
							}
							$send['text'] = "🎩: 교과목 등록을 시작합니다.\n검색하고자하는 교과목명을 입력해주세요.";
							message($send);
						} else {
							$query = queryInsert('logging', 'REGISTER');
							$conn->query($query);
							
							if(!isset($userInfo)) {			
								$send['text'] = "🎩: 교과목 등록을 시작합니다.\n과목 구분을 선택해 주세요.";
								message($send);
									
								$send['elementsTitle'] = "과목 구분";
								$send['elementsButtonsTitle'] = $dbAllDivs = getCourseColumnData($thisCourse, 'divs');
								array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
								messageTemplate($send);
							}
							else if(isset($userInfo)) {
								$rgstedInfo = registedConditionSubject($userInfo);
								isset($rgstedInfo) ? $rgstedInfo = implode("\n", $rgstedInfo) : "";
					
								$send['text'] = "<교과목 등록 현황>\n" . $rgstedInfo . "\n\n총 " . count($userInfo) . "과목";
								message($send);
											
								$send['elementsTitle'] = "과목 구분";
								$send['elementsButtonsTitle'] = $dbAllDivs = getCourseColumnData($thisCourse, 'divs');
								array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
								messageTemplate($send);
							}
						}
						$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
						$send['payload'] = $send['title'] = array('초기화면');
						messageQR($send);
					}
					else if(preg_match("/[1]$/", $inProgress)) {
						$query = queryInsert('logging', 'REGISTER_INSERT');
						$conn->query($query);
						
						$send['text'] = "🎩: 교과목명을 입력해주세요.";
						message($send);
						
						ReturningQR();
					}
					else if(preg_match("/[2]$/", $inProgress)) {
						$query = "SELECT * FROM $thisCourse WHERE (title LIKE '%$searchWord') OR (title LIKE '$searchWord%') OR (title LIKE '%$searchWord%')";
						$sql4courses = $conn->query($query);
						$checkOut = checkOverlap($sql4courses);
						if($checkOut['condition'] == TRUE) {
							if(preg_match("/multiple/", $checkOut['count'])) {
								if(preg_match("/multipleSort$/", $checkOut['count'])) {
									$query = queryInsert('logging', 'REGISTER_INSERT_1', array('searchWord'=>$searchWord));
								}
								$conn->query($query);
								$send['elementsTitle'] = "교과목";
								$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
								messageTemplate($send);
									
								ReturningQR();
							}
						}
						else if($checkOut['condition'] == FALSE) {
							if($checkOut['overcount'] == FALSE) {
								$query = queryInsert('logging', 'REGISTER_INSERT_1', array('searchWord'=>$searchWord));
								$conn->query($query);
								
								$resultArrChunk = array_chunk($checkOut['dbInfo'], 30);
								for($i=0; $i<count($resultArrChunk); $i++) {
									$send['elementsTitle'] = "교과목";
									$send['elementsButtonsTitle'] = $resultArrChunk[$i];
									messageTemplate($send);
								}
								ReturningQR();
							}
						}
					}
					else if(preg_match("/[3]$/", $inProgress)) {
						if($searchWord && !$searchTitle && !$searchMajor) {
							$query = queryInsert('logging', 'REGISTER_INSERT');
							$conn->query($query);
							
							$send['text'] = "🎩: 교과목명을 입력해주세요.";
							message($send);
							
							ReturningQR();
						}
						else if($searchWord && $searchTitle && !$searchMajor) {
							$query = "SELECT * FROM $thisCourse WHERE (title LIKE '%$searchWord') OR (title LIKE '$searchWord%') OR (title LIKE '%$searchWord%')";
							$sql4courses = $conn->query($query);
							$checkOut = checkOverlap($sql4courses);
							if($checkOut['condition'] == TRUE) {
								if(preg_match("/multiple/", $checkOut['count'])) {
									if(preg_match("/multipleSort$/", $checkOut['count'])) {
										$query = queryInsert('logging', 'REGISTER_INSERT_1', array('searchWord'=>$searchWord));
									}
									$conn->query($query);
									$send['elementsTitle'] = "교과목";
									$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
									messageTemplate($send);
										
									ReturningQR();
								}
							}
							else if($checkOut['condition'] == FALSE) {
								if($checkOut['overcount'] == FALSE) {
									$query = queryInsert('logging', 'REGISTER_INSERT_1', array('searchWord'=>$searchWord));
									$conn->query($query);
									
									$resultArrChunk = array_chunk($checkOut['dbInfo'], 30);
									for($i=0; $i<count($resultArrChunk); $i++) {
										$send['elementsTitle'] = "교과목";
										$send['elementsButtonsTitle'] = $resultArrChunk[$i];
										messageTemplate($send);
									}
								}
							}							
						}
						else if($searchWord && $searchTitle && $searchMajor) {
							$query = "SELECT * FROM $thisCourse WHERE title='$searchTitle'";
							$sql4courses = $conn->query($query);
							$checkOut = checkOverlap($sql4courses);
							if($checkOut['condition'] == TRUE) {
								if(preg_match("/multiple/", $checkOut['count'])) {
									if(preg_match("/multipleSort$/", $checkOut['count'])) {
										$query = queryInsert('logging', 'REGISTER_INSERT_2', array('searchWord'=>$searchWord, 'searchTitle'=>$searchTitle));
										$conn->query($query);
									
										$query = "SELECT DISTINCT major FROM $thisCourse WHERE title='$searchTitle'";
										$sql4courses = $conn->query($query);		
										while($row4courses = $sql4courses->fetch_assoc()) {
											if($row4courses['major'] != "") {
												$dbMajor[] = $row4courses['major'];
											}
										}
										$send['elementsTitle'] = "학과 구분";
										$send['elementsButtonsTitle'] = $dbMajor;
										messageTemplate($send);
										
										ReturningQR();										
									}
								}
							}
							else if($checkOut['condition'] == FALSE) {
								if($checkOut['overcount'] == FALSE) {
									$query = queryInsert('logging', 'REGISTER_INSERT_2', array('searchWord'=>$searchWord, 'searchTitle'=>$searchTitle));
									$conn->query($query);
									
									$query = "SELECT DISTINCT major FROM $thisCourse WHERE title='$searchTitle'";
									$sql4courses = $conn->query($query);		
									while($row4courses = $sql4courses->fetch_assoc()) {
										$dbMajor[] = $row4courses['major'];
									}
									
									$send['elementsTitle'] = "학과 구분";
									$send['elementsButtonsTitle'] = $dbMajor;
									messageTemplate($send);
									
									ReturningQR();
								}
							}
						}
					}
				}
				else if(preg_match("/GeneralSelc/", $inProgress) && $processingAllCount == 1 && $rgstGeneralSelc == 1) {
					$selectedDiv = "일반선택";
					
					if(preg_match("/GeneralSelc$/", $inProgress)) {
						// 초기화
						$query = resetProcessing();
						$conn->query($query);
						$query = queryInsert('logging', 'REGISTER');
						$conn->query($query);
						
						if(!isset($userInfo)) {			
							$send['text'] = "🎩: 교과목 등록을 시작합니다.\n과목 구분을 선택해 주세요.";
							message($send);
								
							$send['elementsTitle'] = "과목 구분";
							$send['elementsButtonsTitle'] = $dbAllDivs = getCourseColumnData($thisCourse, 'divs');
							array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
							messageTemplate($send);
						}
						else if(isset($userInfo)) {
							$rgstedInfo = registedConditionSubject($userInfo);
							isset($rgstedInfo) ? $rgstedInfo = implode("\n", $rgstedInfo) : "";
				
							$send['text'] = "<교과목 등록 현황>\n" . $rgstedInfo . "\n\n총 " . count($userInfo) . "과목";
							message($send);
										
							$send['elementsTitle'] = "과목 구분";
							$send['elementsButtonsTitle'] = $dbAllDivs = getCourseColumnData($thisCourse, 'divs');
							array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
							messageTemplate($send);
						}
						$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
						$send['payload'] = $send['title'] = array('초기화면');
						messageQR($send);
					}
					else if(preg_match("/[1]$/", $inProgress)) {
						$query = queryInsert('logging', 'REGISTER_GeneralSelc');
						$conn->query($query);
						
						$send['text'] = "🎩: 교과목명을 입력해주세요.";
						message($send);
						
						ReturningQR();
					}
					else if(preg_match("/[2]$/", $inProgress)) {
						if($searchWord && !$searchTitle) {
							$query = queryInsert('logging', 'REGISTER_GeneralSelc');
							$conn->query($query);
							
							$send['text'] = "🎩: 교과목명을 입력해주세요.";
							message($send);
							
							ReturningQR();						
						}
						else if($searchWord && $searchTitle) {
							$query = "SELECT * FROM $thisCourse WHERE divs='$selectedDiv' AND ((title LIKE '%$searchWord') OR (title LIKE '%$searchWord%') OR (title LIKE '%$searchWord%'))";
							$sql4courses = $conn->query($query);
							$checkOut = checkOverlap($sql4courses);
							
							if($checkOut['condition'] == TRUE) {
								if(preg_match("/multiple/", $checkOut['count'])) {
									if(preg_match("/multipleSort$/", $checkOut['count'])) {
										$query = queryInsert('logging', 'REGISTER_GeneralSelc_1', array('searchWord'=>$searchWord));
										$conn->query($query);
										
										$send['elementsTitle'] = "교과목";
										$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
										messageTemplate($send);
											
										ReturningQR();	
									}				
								}
							}
						}
					}
				}
				else if(preg_match("/LIBERAL/", $inProgress) && $processingAllCount == 1 && $rgstLiberal == 1) {
					$selectedDiv = "교양";
					
					if(preg_match("/LIBERAL$/", $inProgress)) {
						// 초기화
						$query = resetProcessing();
						$conn->query($query);
						$query = queryInsert('logging', 'REGISTER');
						$conn->query($query);
						
						if(!isset($userInfo)) {			
							$send['text'] = "🎩: 교과목 등록을 시작합니다.\n과목 구분을 선택해주세요.";
							message($send);
								
							$send['elementsTitle'] = "과목 구분";
							$send['elementsButtonsTitle'] = $dbAllDivs = getCourseColumnData($thisCourse, 'divs');
							array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
							messageTemplate($send);
						}
						else if(isset($userInfo)) {
							$rgstedInfo = registedConditionSubject($userInfo);
							isset($rgstedInfo) ? $rgstedInfo = implode("\n", $rgstedInfo) : "";
				
							$send['text'] = "<교과목 등록 현황>\n" . $rgstedInfo . "\n\n총 " . count($userInfo) . "과목";
							message($send);
										
							$send['elementsTitle'] = "과목 구분";
							$send['elementsButtonsTitle'] = $dbAllDivs = getCourseColumnData($thisCourse, 'divs');
							array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
							messageTemplate($send);
						}
						$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
						$send['payload'] = $send['title'] = array('초기화면');
						messageQR($send);
					}
					else if(preg_match("/[1]$/", $inProgress)) {
						$query = queryInsert('logging', 'REGISTER_LIBERAL');
						$conn->query($query);
						
						$send['text'] = "🎩: 세부 구분을 선택해주세요.";
						message($send);
						
						$send['elementsTitle'] = "세부 구분";
						$send['elementsButtonsTitle'] = $dbAllFields=getCourseColumnData($thisCourse, 'fields');
						messageTemplate($send);
					
						ReturningQR();		
					}
					else if(preg_match("/[2]$/", $inProgress)) {
						$query = queryInsert('logging', 'REGISTER_LIBERAL_1', array('searchFields'=>$searchFields));
						$conn->query($query);
						
						$send["text"] = "🎩: 교과목을 선택해 주세요.";
						message($send);				
								
						$query = "SELECT DISTINCT title FROM $thisCourse WHERE divs='$selectedDiv' AND fields='$searchFields'";
						$sql4courses = $conn->query($query);
						while($row4courses = $sql4courses->fetch_assoc()) {
							$dbTitle[] = $row4courses['title'];
						}
						$dbTitleArrChunk = array_chunk($dbTitle, 30);
						for($i=0; $i<count($dbTitleArrChunk); $i++) {
							$send['elementsTitle'] = "교과목";
							$send['elementsButtonsTitle'] = $dbTitleArrChunk[$i];
							messageTemplate($send);								
						}						

						ReturningQR();
					}
				}
				else if(((preg_match("/MAJOR/", $inProgress) && $rgstMajor == 1) || (preg_match("/MajorBASIC/", $inProgress) && $rgstMajorBasic== 1) || (preg_match("/LiberalESSN/", $inProgress) && $rgstLiberalEssn == 1)) && $processingAllCount == 1) {
					if(preg_match("/MAJOR/", $inProgress)) {
						$selectedDiv = "전공";
					}
					else if(preg_match("/MajorBASIC/", $inProgress)) {
						$selectedDiv = "전공기초";
					}
					else if(preg_match("/LiberalESSN/", $inProgress)) {
						$selectedDiv = "교양필수";
					}
						
					if(preg_match("/MAJOR$/", $inProgress) || preg_match("/MajorBASIC$/", $inProgress) || preg_match("/LiberalESSN$/", $inProgress)) {
						// 초기화
						$query = resetProcessing();
						$conn->query($query);
						$query = queryInsert('logging', 'REGISTER');
						$conn->query($query);
						
						if(!isset($userInfo)) {			
							$send['text'] = "🎩: 교과목 등록을 시작합니다.\n과목 구분을 선택해 주세요.";
							message($send);
								
							$send['elementsTitle'] = "과목 구분";
							$send['elementsButtonsTitle'] = $dbAllDivs = getCourseColumnData($thisCourse, 'divs');
							array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
							messageTemplate($send);
						}
						else if(isset($userInfo)) {
							$rgstedInfo = registedConditionSubject($userInfo);
							isset($rgstedInfo) ? $rgstedInfo = implode("\n", $rgstedInfo) : "";
				
							$send['text'] = "<교과목 등록 현황>\n" . $rgstedInfo . "\n\n총 " . count($userInfo) . "과목";
							message($send);
										
							$send['elementsTitle'] = "과목 구분";
							$send['elementsButtonsTitle'] = $dbAllDivs = getCourseColumnData($thisCourse, 'divs');
							array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
							messageTemplate($send);
						}
						$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
						$send['payload'] = $send['title'] = array('초기화면');
						messageQR($send);
					}
					else if(preg_match("/[1]$/", $inProgress) || preg_match("/[2]$/", $inProgress) || preg_match("/[3]$/", $inProgress)) {
						if(preg_match("/MAJOR/", $inProgress)) {
							$query = queryInsert('logging', 'REGISTER_MAJOR');
						}
						else if(preg_match("/MajorBASIC/", $inProgress)) {
							$query = queryInsert('logging', 'REGISTER_MajorBASIC');
						}
						else if(preg_match("/LiberalESSN/", $inProgress)) {
							$query = queryInsert('logging', 'REGISTER_LiberalESSN');
						}
						$conn->query($query);
						
						if(!isset($previousSearchMajor)) {
							$send['text'] = "🎩: 학과명을 입력해주세요.";
							message($send);
						} else {
							$send['text'] = "🎩: 이전에 검색한 학과를 선택 또는 새로 검색할 학과명을 입력해주세요.";
							message($send);
							
							$send['elementsTitle'] = "세부 구분";
							$send['elementsButtonsTitle'] = $previousSearchMajor;
							messageTemplate($send);		
						}
						
						ReturningQR();			
					}
					else if(preg_match("/[4]$/", $inProgress)) {
						$query = "SELECT * FROM $thisCourse WHERE divs='$selectedDiv' AND major='$searchMajor'";
						$sql4courses = $conn->query($query);
						while($row4courses = $sql4courses->fetch_assoc()) {
							$dbGrade[] = $row4courses['grade'] . "학년";
							$dbGrade = array_keys(array_flip($dbGrade));
							$dbTitle[] = $row4courses['title'];	
							$dbTitle = array_keys(array_flip($dbTitle));
						}
						
						if(count($dbTitle) > 30) {
							if(preg_match("/MAJOR$/", $inProgress)) {
								$query = queryInsert('logging', 'REGISTER_MAJOR_2', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
							}
							else if(preg_match("/MajorBASIC$/", $inProgress)) {
								$query = queryInsert('logging', 'REGISTER_MajorBASIC_2', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
							}	
							else if(preg_match("/LiberalESSN$/", $inProgress)) {
								$query = queryInsert('logging', 'REGISTER_LiberalESSN_2', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
							}
							$conn->query($query);
							
							$send['text'] = "🎩: 검색된 교과목이 너무 많습니다.\n찾으시는 교과목은 몇 학년 수업인가요?";
							message($send);
									
							$send['elementsTitle'] = "학년 구분";
							$send['elementsButtonsTitle'] = $dbGrade;
							messageTemplate($send);
							 
							ReturningQR();
						}
						else if(count($dbTitle) <= 30) {
							if(preg_match("/MAJOR$/", $inProgress)) {
								$query = queryInsert('logging', 'REGISTER_MAJOR_3', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
							}
							else if(preg_match("/MajorBASIC$/", $inProgress)) {
								$query = queryInsert('logging', 'REGISTER_MajorBASIC_3', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
							}	
							else if(preg_match("/LiberalESSN$/", $inProgress)) {
								$query = queryInsert('logging', 'REGISTER_LiberalESSN_3', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
							}
							$conn->query($query);		
			
							$send["text"] = "🎩: 교과목을 선택해 주세요.";
							message($send);
			
							$send['elementsTitle'] = "교과목";
							$send['elementsButtonsTitle'] = $dbTitle;
							messageTemplate($send);
							
							ReturningQR();
						}
					}
				}
			}
			else if(preg_match("/^READ/", $inProgress)) {
				// values for searching	
				$query = "SELECT * FROM loggingRead WHERE userkey='$senderID' AND year='$thisYear' AND semester='$thisSemester' ORDER BY inputTime DESC LIMIT 1";
				$sql4loggingRead = $conn->query($query);
				while($row4loggingRead = $sql4loggingRead->fetch_assoc()) {
					$readType = $row4loggingRead['type'];
					$readTitle = $row4loggingRead['title'];
					$readClass = $row4loggingRead['class'];
					$readProf = $row4loggingRead['prof'];
					$readContent = $row4loggingRead['content'];
					$readDate1 = $row4loggingRead['date1'];
					$readDate2 = $row4loggingRead['date2'];
					$readTime1 = $row4loggingRead['time1'];
					$readTime2 = $row4loggingRead['time2'];
				}
				
				if($inProgressRead == "READ_EVENT" || $inProgressRead == "READ_DELETE") {
					$query = queryInsert('loggingRead', 'READ');
					$conn->query($query);
					
					$rgstedInfoDetail = registedConditionSubjectDetail($userInfo);
					for($i=0; $i<count($rgstedInfoDetail['title']); $i++) {
						$title = $rgstedInfoDetail['titleName'][$i];
						$class = $rgstedInfoDetail['class'][$i];
						$prof = $rgstedInfoDetail['prof'][$i];
						$send['title'][] = $rgstedInfoDetail['title'][$i];
						$send['subtitle'][] = $rgstedInfoDetail['info'][$i];
						$send['payload'][] = array("assignment_{$title}_{$class}_{$prof}", "cancel_{$title}_{$class}_{$prof}", "exam_{$title}_{$class}_{$prof}");
						
						$eventInfoTypes[$i] = array();
						for($j=0; $j<count($eventInfo); $j++) {
							if($eventInfo[$j]['title'] == $title) {
								$eventInfoTypes[$i][$j] = $eventInfo[$j]['type'];
							}
						}
						$countTypes = array_count_values($eventInfoTypes[$i]);
						$send['buttonsTitle'][$i] = array();
						is_array($countTypes) && $countTypes['assignment'] > 0 ? array_push($send['buttonsTitle'][$i], "과제({$countTypes['assignment']}개)") : array_push($send['buttonsTitle'][$i], "과제");
						is_array($countTypes) && $countTypes['cancel'] > 0 ? array_push($send['buttonsTitle'][$i], "휴강({$countTypes['cancel']}개)") : array_push($send['buttonsTitle'][$i], "휴강");
						is_array($countTypes) && $countTypes['exam'] > 0 ? array_push($send['buttonsTitle'][$i], "시험({$countTypes['exam']}개)") : array_push($send['buttonsTitle'][$i], "시험");
					}
					messageTemplateLeftSlide($send);
					
					$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
					$send['payload'] = $send['title'] = array('초기화면', '시간표 보기', '교과목 삭제하기');
					messageQR($send);
				}
				else if($inProgressRead == "READ_EVENT_WRITE_1" || $inProgressRead == "READ_EVENT_WRITE_2" || $inProgressRead == "READ_EVENT_OTHERS") {
					$query = queryInsert('loggingRead', 'READ_EVENT', array("type"=>$readType, "title"=>$readTitle, "class"=>$readClass, "prof"=>$readProf));
					$conn->query($query);
					
					$query = "SELECT * FROM event WHERE userkey='{$senderID}' AND year='{$thisYear}' AND semester='{$thisSemester}' AND type='{$readType}' AND title='{$readTitle}' AND class='{$readClass}' AND prof='{$readProf}'";
					$sql4event = $conn->query($query);
					while($row4event = $sql4event->fetch_assoc()) {
						$eventDate1 = date("Y-m-d", mktime(0, 0, 0, (int)substr($row4event['date1'], 0, 2), (int)substr($row4event['date1'], 2, 4), date("Y")));
						$eventDate2 = date("Y-m-d", mktime(0, 0, 0, (int)substr($row4event['date2'], 0, 2), (int)substr($row4event['date2'], 2, 4), date("Y")));
						$nowDate = date("Y-m-d", strtotime($inputTime));
						if((empty($row4event['date2']) && $eventDate1 >= $nowDate) || (!empty($row4event['date2']) && $eventDate2 >= $nowDate)) {
							$events[] = $row4event;
						}
					}
					
					if(count($events) > 0) {
						$j=0;
						for($i=0; $i<count($events); $i++) {
							if($events[$i]['type'] == "assignment") {
								$typeKR = "과제";
								$send['title'][] = "<과제 - " . $events[$i]['title'] . "> - 기한: " . substr($events[$i]['date1'], 0, 2) . "월 " . substr($events[$i]['date1'], 2, 2) . "일";
								$send['subtitle'][] = "과제 내용: " . $events[$i]['content'] . "\n입력시간: " . $events[$i]['inputTime'];
							}
							else if($events[$i]['type'] == "cancel") {
								$typeKR = "휴강";
								$readDateMonth1 = substr($events[$i]['date1'], 0, 2);
								$readDateDay1 = substr($events[$i]['date1'], 2, 2);
								$readDateMonth2 = substr($events[$i]['date2'], 0, 2);
								$readDateDay2 = substr($events[$i]['date2'], 2, 2);
								
								if(empty($events[$i]['date2'])) {
									$send['title'][] = "<휴강 - " . $eventInfo[$i]['title'] . ">";
									$send['subtitle'][] = "휴강 날짜: " . $readDateMonth1 . "월 " . $readDateDay1 . "일\n입력시간: " . $events[$i]['inputTime'];
								}
								else if(!empty($events[$i]['date2'])) {
									$send['title'][] = "<휴강 - " . $eventInfo[$i]['title'] . ">";
									$send['subtitle'][] = "휴강 날짜: " . $readDateMonth1 . "월 " . $readDateDay1 . "일부터 " . $readDateMonth2 . "월 " . $readDateDay2 . "일 까지\n입력시간: " . $events[$i]['inputTime'];
								}
							}
							else if($events[$i]['type'] == "exam") {
								$typeKR = "시험";
								$readDateMonth = substr($events[$i]['date1'], 0, 2);
								$readDateDay = substr($events[$i]['date1'], 2, 2);
								$readDateHour = substr($events[$i]['time1'], 0, 2);
								$readDateMin = substr($events[$i]['time1'], 2, 2);
							
								$send['title'][] = "<시험 - " . $events[$i]['title'] . ">";
								$send['subtitle'][] = "시험 일정: " . $readDateMonth . "월 " . $readDateDay . "일 / ". $readDateHour . "시 " . $readDateMin . "분\n입력시간: " . $events[$i]['inputTime'];
							}
							$send['payload'][] = array("OTHERS_{$readType}_{$readTitle}_{$readClass}_{$readProf}", "DELETE_{$readType}_{$readTitle}_{$readClass}_{$readProf}_{$j}");
							$send['buttonsTitle'][] = array("다른 사람 {$typeKR} 정보 보기", "이 {$typeKR} 정보 삭제하기");
							$j++;
						}
						messageTemplateLeftSlide($send);
						
						$send['text'] = "🎩: 아래 버튼을 눌러 계속 진행해주세요.";
						$send['title'] = array('새로 등록하기', '이전으로', '초기화면');
						$send['payload'] = array("WRITE_{$readType}_{$readTitle}_{$readClass}_{$readProf}", '이전으로', '초기화면');
					} else {
						if($readType == "assignment") {
							$typeKR = "과제";
						}
						else if($readType == "cancel") {
							$typeKR = "휴강";
						}
						else if($readType == "exam") {
							$typeKR = "시험";
						}
						$send['text'] = "🎩: {$readTitle}에 등록된 {$typeKR} 정보가 없습니다.\n아래 버튼을 눌러 계속 진행해주세요.";
						$send['title'] = array('새로 등록하기', "다른 사람 {$typeKR} 정보 보기", '이전으로', '초기화면');
						$send['payload'] = array("WRITE_{$readType}_{$readTitle}_{$readClass}_{$readProf}", "OTHERS_{$readType}_{$readTitle}_{$readClass}_{$readProf}", '이전으로', '초기화면');
					}
					messageQR($send);
				}			
			}
		} else {
		////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		//////////////////////////////////////////////////////////////////////// START //////////////////////////////////////////////////////////////////////////////////
		////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			if(preg_match("/^START$/", $inProgress)) {
				// 초기화
				$query = resetProcessing();
				$conn->query($query);
		
				if(preg_match("/^교과목(.*)등록/", $payload) || preg_match("/^교과목(.*)추가(.*)등록/", $payloadQR)) {
					if($thisCourse[strlen($thisCourse)-1] == "W" || $a[strlen($thisCourse)-1] == "S") {
						$query = updateProcessing('insert');
						$conn->query($query);
						$query = queryInsert('logging', 'REGISTER_INSERT');
						$conn->query($query);
						
						if(isset($userInfo)) {
							$rgstedInfo = registedConditionSubject($userInfo);
							isset($rgstedInfo) ? $rgstedInfo = implode("\n", $rgstedInfo) : "";
				
							$send['text'] = "<교과목 등록 현황>\n" . $rgstedInfo . "\n\n총 " . count($userInfo) . "과목";
							message($send);
						}
						$send['text'] = "🎩: 교과목 등록을 시작합니다.\n검색하고자하는 교과목명을 입력해주세요.";
						message($send);
					} else {
						$query = queryInsert('logging', 'REGISTER');
						$conn->query($query);
						
						if(!isset($userInfo)) {
							$send['text'] = "🎩: 교과목 등록을 시작합니다.\n과목 구분을 선택해 주세요.";
							message($send);
								
							$send['elementsTitle'] = "과목 구분";
							$send['elementsButtonsTitle'] = $dbAllDivs = getCourseColumnData($thisCourse, 'divs');
							array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
							messageTemplate($send);
						}
						else if(isset($userInfo)) {
							$rgstedInfo = registedConditionSubject($userInfo);
							isset($rgstedInfo) ? $rgstedInfo = implode("\n", $rgstedInfo) : "";
				
							$send['text'] = "<교과목 등록 현황>\n" . $rgstedInfo . "\n\n총 " . count($userInfo) . "과목";
							message($send);
										
							$send['elementsTitle'] = "과목 구분";
							$send['elementsButtonsTitle'] = $dbAllDivs = getCourseColumnData($thisCourse, 'divs');
							array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
							messageTemplate($send);
						}
					}
					$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
					$send['payload'] = $send['title'] = array('초기화면');
					messageQR($send);
				}
				else if(preg_match("/등록한(.*)정보(.*)보기/", $payload)) {
					$query = queryInsert('logging', 'READ');
					$conn->query($query);
					$query = queryInsert('loggingRead', 'READ');
					$conn->query($query);
					
					$rgstedInfoDetail = registedConditionSubjectDetail($userInfo);
					for($i=0; $i<count($rgstedInfoDetail['title']); $i++) {
						$title = $rgstedInfoDetail['titleName'][$i];
						$class = $rgstedInfoDetail['class'][$i];
						$prof = $rgstedInfoDetail['prof'][$i];
						$send['title'][] = $rgstedInfoDetail['title'][$i];
						$send['subtitle'][] = $rgstedInfoDetail['info'][$i];
						$send['payload'][] = array("assignment_{$title}_{$class}_{$prof}", "cancel_{$title}_{$class}_{$prof}", "exam_{$title}_{$class}_{$prof}");
						
						$eventInfoTypes[$i] = array();
						for($j=0; $j<count($eventInfo); $j++) {
							if($eventInfo[$j]['title'] == $title) {
								$eventInfoTypes[$i][$j] = $eventInfo[$j]['type'];
							}
						}
						$countTypes = array_count_values($eventInfoTypes[$i]);
						$send['buttonsTitle'][$i] = array();
						is_array($countTypes) && $countTypes['assignment'] > 0 ? array_push($send['buttonsTitle'][$i], "과제({$countTypes['assignment']}개)") : array_push($send['buttonsTitle'][$i], "과제");
						is_array($countTypes) && $countTypes['cancel'] > 0 ? array_push($send['buttonsTitle'][$i], "휴강({$countTypes['cancel']}개)") : array_push($send['buttonsTitle'][$i], "휴강");
						is_array($countTypes) && $countTypes['exam'] > 0 ? array_push($send['buttonsTitle'][$i], "시험({$countTypes['exam']}개)") : array_push($send['buttonsTitle'][$i], "시험");
					}
					messageTemplateLeftSlide($send);
					
					$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
					$send['payload'] = $send['title'] = array('초기화면', '시간표 보기', '교과목 삭제하기');
					messageQR($send);
				} else {
					$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
					if(!isset($userInfo)) {						
						$send['title'] = $send['buttonsTitle'] = $send['payload'] = array("튜토리얼 시작하기");
						$imagePath = 'https://bhandy.kr/scheduler/univ/pnu/images/';
						$send['imageURL'] = array($imagePath.'tutorial.jpg');
						messageTemplateLeftSlideWithImage($send);
					}
					else if(isset($userInfo)) {
						$send['title'] = $send['buttonsTitle'] = $send['payload'] = array("내가 등록한 정보 보기", "교과목 등록하기");
						$imagePath = 'https://bhandy.kr/scheduler/univ/pnu/images/';
						$send['imageURL'] = array($imagePath.'notepad.jpg', $imagePath.'register.jpg');
						messageTemplateLeftSlideWithImage($send);
					}
				}
			}
		//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		////////////////////////////////////////////////////////////////////////// REGISTER //////////////////////////////////////////////////////////////////////////
		//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			else if(preg_match("/^REGISTER/", $inProgress)) {
				// values for searching	
				$query = "SELECT * FROM logging WHERE userkey='$senderID' AND year='$thisYear' AND semester='$thisSemester' ORDER BY inputTime DESC LIMIT 1";
				$sql4loggingSearch = $conn->query($query);
				while($row4loggingSearch = $sql4loggingSearch->fetch_assoc()) {
					$searchWord = $row4loggingSearch['searchWord'];
					$searchTitle = $row4loggingSearch['searchTitle'];
					$searchMajor = $row4loggingSearch['searchMajor'];
					$searchGrade = $row4loggingSearch['searchGrade'];
					$searchFields = $row4loggingSearch['searchFields'];
				}
				
				// 이전 검색 정보
				$query = "SELECT DISTINCT searchMajor FROM logging WHERE userkey='$senderID' AND year='$thisYear' AND semester='$thisSemester' ORDER BY inputTime DESC";
				$sql4loggingSearchMajor = $conn->query($query);
				while($row4loggingSearchMajor = $sql4loggingSearchMajor->fetch_assoc()) {
					if(!empty($row4loggingSearchMajor["searchMajor"]) && $row4loggingSearchMajor["searchMajor"] != "") {
						$previousSearchMajor[] = $row4loggingSearchMajor["searchMajor"];
					}
				}
				
				if(preg_match("/^REGISTER$/", $inProgress)) {
					if($payload) {
						if(preg_match("/^교과목명(.*)입력$/", $payload) || preg_match("/^일반선택$/", $payload)) {
							if(preg_match("/^교과목명(.*)입력$/", $payload)) {
								$query = updateProcessing('insert');
								$conn->query($query);
								$query = queryInsert('logging', 'REGISTER_INSERT');
								$conn->query($query);					
							}
							else if(preg_match("/^일반선택$/", $payload)) {
								$query = updateProcessing('generalSelc');
								$conn->query($query);
								$query = queryInsert('logging', 'REGISTER_GeneralSelc');
								$conn->query($query);	
							}
							$send['text'] = "🎩: 교과목명을 입력해주세요.";
							message($send);
							
							ReturningQR();
						}
						else if(preg_match("/^전공$/", $payload) || preg_match("/^전공기초$/", $payload) || preg_match("/^교양필수$/", $payload)) {
							if(preg_match("/^전공$/", $payload)) {
								$query = updateProcessing('major');
								$conn->query($query);
								$query = queryInsert('logging', 'REGISTER_MAJOR');
								$conn->query($query);				
							}
							else if(preg_match("/^전공기초$/", $payload)) {
								$query = updateProcessing('majorBasic');
								$conn->query($query);
								$query = queryInsert('logging', 'REGISTER_MajorBASIC');
								$conn->query($query);
							}
							else if(preg_match("/^교양필수$/", $payload)) {
								$query = updateProcessing('liberalEssn');
								$conn->query($query);
								$query = queryInsert('logging', 'REGISTER_LiberalESSN');
								$conn->query($query);				
							}
				
							if(!isset($previousSearchMajor)) {
								$send['text'] = "🎩: 학과명을 입력해주세요.";
								message($send);
							} else {
								$send['text'] = "🎩: 이전에 검색한 학과를 선택 또는 새로 검색할 학과명을 입력해주세요.";
								message($send);
								
								$send['elementsTitle'] = "세부 구분";
								$send['elementsButtonsTitle'] = $previousSearchMajor;
								messageTemplate($send);		
							}
							
							ReturningQR();
						}
						else if(preg_match("/^교양$/", $payload)) {
							$query = updateProcessing('liberal');
							$conn->query($query);
							$query = queryInsert('logging', 'REGISTER_LIBERAL');
							$conn->query($query);
							
							$send['text'] = "🎩: 세부 구분을 선택해주세요.";
							message($send);
							
							$send['elementsTitle'] = "세부 구분";
							$send['elementsButtonsTitle'] = $dbAllFields=getCourseColumnData($thisCourse, 'fields');
							messageTemplate($send);
						
							ReturningQR();
						}
					} else {
						$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
						message($send);
						if(!isset($userInfo)) {
							$send['elementsTitle'] = "과목 구분";
							$send['elementsButtonsTitle'] =  $dbAllDivs = getCourseColumnData($thisCourse, 'divs');
							array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
							messageTemplate($send);
						}
						else if(isset($userInfo)) {
							$rgstedInfo = registedConditionSubject($userInfo);
							isset($rgstedInfo) ? $rgstedInfo = implode("\n", $rgstedInfo) : "";
				
							$send['text'] = "<교과목 등록 현황>\n" . $rgstedInfo . "\n\n총 " . count($userInfo) . "과목";
							message($send);
										
							$send['elementsTitle'] = "과목 구분";
							$send['elementsButtonsTitle'] =  $dbAllDivs = getCourseColumnData($thisCourse, 'divs');
							array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
							messageTemplate($send);
						}
						$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
						$send['payload'] = $send['title'] = array('초기화면');
						messageQR($send);
					}
				}
				else if(preg_match("/INSERT/", $inProgress) && $processingAllCount == 1 && $rgstInsert == 1) {
					if(preg_match("/INSERT$/", $inProgress)) {
						if($messageText) {
							$searchWord = $messageText;
					
							$query = "SELECT * FROM $thisCourse WHERE (title LIKE '%$searchWord') OR (title LIKE '$searchWord%') OR (title LIKE '%$searchWord%')";
							$sql4courses = $conn->query($query);
							$checkOut = checkOverlap($sql4courses);
							
							if($checkOut['condition'] == TRUE) {
								if($checkOut['count'] == "single") {
									if($checkOut['overlap'] == TRUE) {
										$send['text'] = "🎩: ".$checkOut['text'][1];
										message($send);
										
										ReturningQR();
									}
									else if($checkOut['overlap'] == FALSE) {
										$send['text'] = "🎩: ".$checkOut['text'];
										$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
										messageQR($send);
										
										$checkOutInfo = $checkOut['dbInfo'];
										$query = "INSERT INTO logging (year, semester, userkey, inProgress, searchWord, divs, fields, major, title, code, class, prof, department,
																							day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																								VALUE('$thisYear', '$thisSemester', '$senderID', 'REGISTER_INSERT_OPT', '$searchWord', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
																											'{$checkOutInfo['title']}', '{$checkOutInfo['code']}', '{$checkOutInfo['class']}', '{$checkOutInfo['prof']}', '{$checkOutInfo['department']}',
																											'{$checkOutInfo['day1']}', '{$checkOutInfo['day2']}', '{$checkOutInfo['day3']}', '{$checkOutInfo['day4']}', '{$checkOutInfo['day5']}', '{$checkOutInfo['day6']}',
																											'{$checkOutInfo['time1']}', '{$checkOutInfo['time2']}', '{$checkOutInfo['time3']}', '{$checkOutInfo['time4']}', '{$checkOutInfo['time5']}', '{$checkOutInfo['time6']}',
																											'{$checkOutInfo['min1']}', '{$checkOutInfo['min2']}', '{$checkOutInfo['min3']}', '{$checkOutInfo['min4']}', '{$checkOutInfo['min5']}', '{$checkOutInfo['min6']}',
																											'{$checkOutInfo['classroom1']}', '{$checkOutInfo['classroom2']}', '{$checkOutInfo['classroom3']}', '{$checkOutInfo['classroom4']}', '{$checkOutInfo['classroom5']}', '{$checkOutInfo['classroom6']}',
																											'$inputTime')";
										$conn->query($query);
									}
								}
								else if(preg_match("/multiple/", $checkOut['count'])) {
									if(preg_match("/multiple$/", $checkOut['count'])) {
										$query = queryInsert('logging', 'REGISTER_INSERT_3', array('searchWord'=>$searchWord));
									}
									else if(preg_match("/multipleSort$/", $checkOut['count'])) {
										$query = queryInsert('logging', 'REGISTER_INSERT_1', array('searchWord'=>$searchWord));
									}
									$conn->query($query);
									
									$send['elementsTitle'] = "교과목";
									$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
									messageTemplate($send);
										
									ReturningQR();					
								}
							}
							else if($checkOut['condition'] == FALSE) {
								if($checkOut['dbInfo']) {
									if($checkOut['overcount'] == FALSE) {
										$query = queryInsert('logging', 'REGISTER_INSERT_1', array('searchWord'=>$searchWord));
										$conn->query($query);
										
										$resultArrChunk = array_chunk($checkOut['dbInfo'], 30);
										for($i=0; $i<count($resultArrChunk); $i++) {
											$send['elementsTitle'] = "교과목";
											$send['elementsButtonsTitle'] = $resultArrChunk[$i];
											messageTemplate($send);
										}
										ReturningQR();
									}									
								} else {
									$send['text'] = "🎩: 검색된 교과목이 없습니다.\n다시 한번 상세히 입력해주세요.";
									message($send);	
									ReturningQR();
								}
							}
						} else {
							if(preg_match("/^교과목명(.*)입력$/", $payload) || preg_match("/^일반선택$/", $payload)) {
								if(preg_match("/^교과목명(.*)입력$/", $payload)) {
									$query = updateProcessing('insert');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_INSERT');
									$conn->query($query);					
								}
								else if(preg_match("/^일반선택$/", $payload)) {
									$query = updateProcessing('generalSelc');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_GeneralSelc');
									$conn->query($query);	
								}
								$send['text'] = "🎩: 교과목명을 입력해주세요.";
								message($send);
								
								ReturningQR();
							}
							else if(preg_match("/^전공$/", $payload) || preg_match("/^전공기초$/", $payload) || preg_match("/^교양필수$/", $payload)) {
								if(preg_match("/^전공$/", $payload)) {
									$query = updateProcessing('major');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_MAJOR');
									$conn->query($query);				
								}
								else if(preg_match("/^전공기초$/", $payload)) {
									$query = updateProcessing('majorBasic');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_MajorBASIC');
									$conn->query($query);
								}
								else if(preg_match("/^교양필수$/", $payload)) {
									$query = updateProcessing('liberalEssn');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_LiberalESSN');
									$conn->query($query);				
								}
					
								if(!isset($previousSearchMajor)) {
									$send['text'] = "🎩: 학과명을 입력해주세요.";
									message($send);
								} else {
									$send['text'] = "🎩: 이전에 검색한 학과를 선택 또는 새로 검색할 학과명을 입력해주세요.";
									message($send);
									
									$send['elementsTitle'] = "세부 구분";
									$send['elementsButtonsTitle'] = $previousSearchMajor;
									messageTemplate($send);		
								}
								
								ReturningQR();
							}
							else if(preg_match("/^교양$/", $payload)) {
								$query = updateProcessing('liberal');
								$conn->query($query);
								$query = queryInsert('logging', 'REGISTER_LIBERAL');
								$conn->query($query);
								
								$send['text'] = "🎩: 세부 구분을 선택해주세요.";
								message($send);
								
								$send['elementsTitle'] = "세부 구분";
								$send['elementsButtonsTitle'] = $dbAllFields=getCourseColumnData($thisCourse, 'fields');
								messageTemplate($send);
							
								ReturningQR();
							} else {
								$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 입력해주세요.";
								message($send);
								ReturningQR();								
							}
						}
					}
					else if(preg_match("/[1]$/", $inProgress)) {
						if($payload) {
							$searchTitle = $payload;
							
							$query = "SELECT * FROM $thisCourse WHERE title='$searchTitle'";
							$sql4courses = $conn->query($query);
							$checkOut = checkOverlap($sql4courses);
							
							if($checkOut['condition'] == TRUE) {
								if($checkOut['count'] == "single") {
									if($checkOut['overlap'] == TRUE) {
										$send['text'] = "🎩: ".$checkOut['text'][0];
										message($send);
									
										$query = "SELECT DISTINCT title FROM $thisCourse WHERE (title LIKE '%$searchWord') OR (title LIKE '$searchWord%') OR (title LIKE '%$searchWord%')";
										$sql4courses = $conn->query($query);
										while($row4courses = $sql4courses->fetch_assoc()) {
											$dbTitle[] = $row4courses['title'];
										}
										
										$send['elementsTitle'] = "교과목";
										$send['elementsButtonsTitle'] = $dbTitle;
										messageTemplate($send);
										
										ReturningQR();
									}
									else if($checkOut['overlap'] == FALSE) {
										$send['text'] = "🎩: ".$checkOut['text'];
										$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
										messageQR($send);
										
										$checkOutInfo = $checkOut['dbInfo'];
										$query = "INSERT INTO logging (year, semester, userkey, inProgress, searchWord, searchTitle, divs, fields, major, title, code, class, prof, department,
																							day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																								VALUE('$thisYear', '$thisSemester', '$senderID', 'REGISTER_INSERT_OPT', '$searchWord', '$searchTitle', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
																											'{$checkOutInfo['title']}', '{$checkOutInfo['code']}', '{$checkOutInfo['class']}', '{$checkOutInfo['prof']}', '{$checkOutInfo['department']}',
																											'{$checkOutInfo['day1']}', '{$checkOutInfo['day2']}', '{$checkOutInfo['day3']}', '{$checkOutInfo['day4']}', '{$checkOutInfo['day5']}', '{$checkOutInfo['day6']}',
																											'{$checkOutInfo['time1']}', '{$checkOutInfo['time2']}', '{$checkOutInfo['time3']}', '{$checkOutInfo['time4']}', '{$checkOutInfo['time5']}', '{$checkOutInfo['time6']}',
																											'{$checkOutInfo['min1']}', '{$checkOutInfo['min2']}', '{$checkOutInfo['min3']}', '{$checkOutInfo['min4']}', '{$checkOutInfo['min5']}', '{$checkOutInfo['min6']}',
																											'{$checkOutInfo['classroom1']}', '{$checkOutInfo['classroom2']}', '{$checkOutInfo['classroom3']}', '{$checkOutInfo['classroom4']}', '{$checkOutInfo['classroom5']}', '{$checkOutInfo['classroom6']}',
																											'$inputTime')";
										$conn->query($query);
									}
								}
								else if(preg_match("/multiple/", $checkOut['count'])) {
									if(preg_match("/multiple$/", $checkOut['count'])) {
										$query = queryInsert('logging', 'REGISTER_INSERT_3', array('searchWord'=>$searchWord, 'searchTitle'=>$searchTitle));								
										$conn->query($query);
										
										$send['elementsTitle'] = "교과목";
										$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
										messageTemplate($send);
											
										ReturningQR();		
									}
									else if(preg_match("/multipleSort$/", $checkOut['count'])) {
										$query = queryInsert('logging', 'REGISTER_INSERT_2', array('searchWord'=>$searchWord, 'searchTitle'=>$searchTitle));
										$conn->query($query);
									
										$query = "SELECT DISTINCT major FROM $thisCourse WHERE title='$searchTitle'";
										$sql4courses = $conn->query($query);		
										while($row4courses = $sql4courses->fetch_assoc()) {
											if($row4courses['major'] != "") {
												$dbMajor[] = $row4courses['major'];
											}
										}
										
										$send['text'] = "🎩: 검색된 교과목이 너무 많습니다.\n\n'{$searchTitle}'은(는) 어떤 학과 수업인지 알려줄 수 있나요?";
										message($send);
										
										$send['elementsTitle'] = "학과 구분";
										$send['elementsButtonsTitle'] = $dbMajor;
										messageTemplate($send);
										
										ReturningQR();
									}
								}
							} 
							else if($checkOut['condition'] == FALSE) {
								if($checkOut['overcount'] == FALSE) {
									$query = queryInsert('logging', 'REGISTER_INSERT_2', array('searchWord'=>$searchWord, 'searchTitle'=>$searchTitle));
									$conn->query($query);
									
									$query = "SELECT DISTINCT major FROM $thisCourse WHERE title='$searchTitle'";
									$sql4courses = $conn->query($query);		
									while($row4courses = $sql4courses->fetch_assoc()) {
										$dbMajor[] = $row4courses['major'];
									}
									
									$send['text'] = "🎩: 검색된 교과목이 너무 많습니다.\n\n'{$searchTitle}'은(는) 어떤 학과 수업인지 알려줄 수 있나요?";
									message($send);
									
									$send['elementsTitle'] = "학과 구분";
									$send['elementsButtonsTitle'] = $dbMajor;
									messageTemplate($send);
									
									ReturningQR();
								}
								else if(!in_array($searchTitle, $dbAllTitle=getCourseColumnData($thisCourse, 'tite'))) {
									$send['text'] = "🎩: 올바른 교과목명이 아닌 것 같아요. 다시 똑.디. 선택해주세요.";
									message($send);
									ReturningQR();
								} else {
									$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
									message($send);							
			
									$query = "SELECT DISTINCT title FROM $thisCourse WHERE (title LIKE '%$searchWord') OR (title LIKE '$searchWord%') OR (title LIKE '%$searchWord%')";
									$sql4courses = $conn->query($query);
									while($row4courses = $sql4courses->fetch_assoc()) {
										$dbTitle[] = $row4courses['title'];
									}
									
									$send['elementsTitle'] = "교과목";
									$send['elementsButtonsTitle'] = $dbTitle;
									messageTemplate($send);
									
									ReturningQR();										
								}
							}
						} else {
							$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
							message($send);							
	
							$query = "SELECT DISTINCT title FROM $thisCourse WHERE (title LIKE '%$searchWord') OR (title LIKE '$searchWord%') OR (title LIKE '%$searchWord%')";
							$sql4courses = $conn->query($query);
							while($row4courses = $sql4courses->fetch_assoc()) {
								$dbTitle[] = $row4courses['title'];
							}
							
							$send['elementsTitle'] = "교과목";
							$send['elementsButtonsTitle'] = $dbTitle;
							messageTemplate($send);
							
							ReturningQR();						
						}
					}
					else if(preg_match("/[2]$/", $inProgress)) {
						if($payload) {
							$searchMajor = $payload;
							
							$query = "SELECT * FROM $thisCourse WHERE major='$searchMajor' AND title='$searchTitle'";
							$sql4courses = $conn->query($query);
							$checkOut = checkOverlap($sql4courses);
							
							if($checkOut['condition'] == TRUE) {
								if($checkOut['count'] == "single") {
									if($checkOut['overlap'] == TRUE) {
										$send['text'] = "🎩: ".$checkOut['text'][0];
										message($send);
									
										$query = "SELECT DISTINCT title FROM $thisCourse WHERE major='$searchMajor' AND title='$searchTitle'";
										$sql4courses = $conn->query($query);
										while($row4courses = $sql4courses->fetch_assoc()) {
											$dbTitle[] = $row4courses['title'];
										}
										
										$send['elementsTitle'] = "교과목";
										$send['elementsButtonsTitle'] = $dbTitle;
										messageTemplate($send);
										
										ReturningQR();
									}
									else if($checkOut['overlap'] == FALSE) {
										$send['text'] = "🎩: ".$checkOut['text'];
										$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
										messageQR($send);
										
										$checkOutInfo = $checkOut['dbInfo'];
										$query = "INSERT INTO logging (year, semester, userkey, inProgress, searchWord, searchMajor, searchTitle,divs, fields, major, title, code, class, prof, department,
																							day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																								VALUE('$thisYear', '$thisSemester', '$senderID', 'REGISTER_INSERT_OPT', '$searchWord', '$searchMajor', '$searchTitle', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
																											'{$checkOutInfo['title']}', '{$checkOutInfo['code']}', '{$checkOutInfo['class']}', '{$checkOutInfo['prof']}', '{$checkOutInfo['department']}',
																											'{$checkOutInfo['day1']}', '{$checkOutInfo['day2']}', '{$checkOutInfo['day3']}', '{$checkOutInfo['day4']}', '{$checkOutInfo['day5']}', '{$checkOutInfo['day6']}',
																											'{$checkOutInfo['time1']}', '{$checkOutInfo['time2']}', '{$checkOutInfo['time3']}', '{$checkOutInfo['time4']}', '{$checkOutInfo['time5']}', '{$checkOutInfo['time6']}',
																											'{$checkOutInfo['min1']}', '{$checkOutInfo['min2']}', '{$checkOutInfo['min3']}', '{$checkOutInfo['min4']}', '{$checkOutInfo['min5']}', '{$checkOutInfo['min6']}',
																											'{$checkOutInfo['classroom1']}', '{$checkOutInfo['classroom2']}', '{$checkOutInfo['classroom3']}', '{$checkOutInfo['classroom4']}', '{$checkOutInfo['classroom5']}', '{$checkOutInfo['classroom6']}',
																											'$inputTime')";
										$conn->query($query);
									}
								}
								else if($checkOut['count'] == "multiple") {
									$query = queryInsert('logging', 'REGISTER_INSERT_3', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor, 'searchTitle'=>$searchTitle));
									$conn->query($query);
									
									$send['elementsTitle'] = "교과목";
									$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
									messageTemplate($send);
										
									ReturningQR();
								}
							} 
							else if($checkOut['condition'] == FALSE) {
								if(!in_array($searchMajor, $dbAllMajor=getCourseColumnData($thisCourse, 'major'))) {
									$send['text'] = "🎩: 올바른 학과명이 아닌 것 같아요. 다시 똑.디. 선택해주세요.";
									message($send);
									
									$query = "SELECT DISTINCT title FROM $thisCourse WHERE major='$searchMajor' AND title='$searchTitle'";
									$sql4courses = $conn->query($query);
									while($row4courses = $sql4courses->fetch_assoc()) {
										$dbTitle[] = $row4courses['title'];
									}
									
									$send['elementsTitle'] = "교과목";
									$send['elementsButtonsTitle'] = $dbTitle;
									messageTemplate($send);
									
									ReturningQR();
								} else {
									$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
									meesage($send);
									
									$query = "SELECT DISTINCT title FROM $thisCourse WHERE major='$searchMajor' AND title='$searchTitle'";
									$sql4courses = $conn->query($query);
									while($row4courses = $sql4courses->fetch_assoc()) {
										$dbTitle[] = $row4courses['title'];
									}
									
									$send['elementsTitle'] = "교과목";
									$send['elementsButtonsTitle'] = $dbTitle;
									messageTemplate($send);
									
									ReturningQR();
								}
							}
						} else {
							$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
							meesage($send);
							
							$query = "SELECT DISTINCT title FROM $thisCourse WHERE major='$searchMajor' AND title='$searchTitle'";
							$sql4courses = $conn->query($query);
							while($row4courses = $sql4courses->fetch_assoc()) {
								$dbTitle[] = $row4courses['title'];
							}
							
							$send['elementsTitle'] = "교과목";
							$send['elementsButtonsTitle'] = $dbTitle;
							messageTemplate($send);
							
							ReturningQR();							
						}
					}
					else if(preg_match("/[3]$/", $inProgress)) {
						if($payload) {
							if(strpos($payload, "(") !== FALSE) {
								$payloadExp = explode("(", (str_replace(")", "", $payload)));
								// 단일 분류
								if(substr_count($payload, "(") >= 2) {
									$payloadTitle = $payloadExp[0] . "(" . $payloadExp[1] . ")";
									$payloadInfo = $payloadExp[2];	
								}
								else if(substr_count($payload, "(") == 1) {
									$payloadTitle = $payloadExp[0];
									$payloadInfo = $payloadExp[1];
								}
								
								// 복수 분류
								//// 분반 분류
								if(strlen($payloadInfo) == 3 && preg_match("/[0-9]/", $payloadInfo)) {
									$payloadInfoClass = $payloadInfo;
								}
								//// 교수명 분류
								else if(strpos($payloadInfo, "교수님") !== FALSE) {
									$payloadInfoProf = str_replace("교수님", "", $payloadInfo);
								} else {
								//// 학부 분류
									if(mb_strlen($payloadInfo, UTF8) > 1 && preg_match("/[\xA1-\xFE\xA1-\xFE]/",$payloadInfo)) {
										$payloadInfoDepartment = $payloadInfo;
									} else {
										////// 괄호 안 문자가 분류대상이 아닐 경우
										$payloadTitle = $payload;
										unset($payloadInfo);
									}
								}
								if($payloadInfo) {
									if(empty($searchMajor)) {
										$query = "SELECT * FROM $thisCourse WHERE title='$payloadTitle' AND 
																					(
																						class='$payloadInfoClass' OR department='$payloadInfoDepartment' OR (prof!='' AND prof='$payloadInfoProf')
																					)";
									}
									else if(!empty($searchMajor)) {
										$query = "SELECT * FROM $thisCourse WHERE title='$payloadTitle' AND major='$searchMajor' AND
																					(
																						class='$payloadInfoClass' OR department='$payloadInfoDepartment' OR (prof!='' AND prof='$payloadInfoProf')
																					)";
									}
								} else {
									$query = "SELECT * FROM $thisCourse WHERE title='$payloadTitle'";								
								}
							} else {
								$query = "SELECT * FROM $thisCourse WHERE title='$payload'";
							}
							$sql4courses = $conn->query($query);
							$checkOut = checkOverlapReturn($sql4courses);
							if($checkOut['condition'] == TRUE) {
								if($checkOut['overlap'] == TRUE) {
									$send['text'] = "🎩: ".$checkOut['text'][0];
									message($send);
									
									$send['elementsTitle'] = "교과목";
									$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
									messageTemplate($send);
									
									ReturningQR();
								}
								else if($checkOut['overlap'] == FALSE) {
									$send['text'] = "🎩: ".$checkOut['text'];
									$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
									messageQR($send);
									
									$checkOutInfo = $checkOut['dbInfo'];
									$query = "INSERT INTO logging (year, semester, userkey, inProgress, searchWord, searchMajor, searchTitle, divs, fields, major, title, code, class, prof, department,
																						day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																							VALUE('$thisYear', '$thisSemester', '$senderID', 'REGISTER_INSERT_OPT', '$searchWord', '$searchMajor', '$searchTitle', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
																										'{$checkOutInfo['title']}', '{$checkOutInfo['code']}', '{$checkOutInfo['class']}', '{$checkOutInfo['prof']}', '{$checkOutInfo['department']}',
																										'{$checkOutInfo['day1']}', '{$checkOutInfo['day2']}', '{$checkOutInfo['day3']}', '{$checkOutInfo['day4']}', '{$checkOutInfo['day5']}', '{$checkOutInfo['day6']}',
																										'{$checkOutInfo['time1']}', '{$checkOutInfo['time2']}', '{$checkOutInfo['time3']}', '{$checkOutInfo['time4']}', '{$checkOutInfo['time5']}', '{$checkOutInfo['time6']}',
																										'{$checkOutInfo['min1']}', '{$checkOutInfo['min2']}', '{$checkOutInfo['min3']}', '{$checkOutInfo['min4']}', '{$checkOutInfo['min5']}', '{$checkOutInfo['min6']}',
																										'{$checkOutInfo['classroom1']}', '{$checkOutInfo['classroom2']}', '{$checkOutInfo['classroom3']}', '{$checkOutInfo['classroom4']}', '{$checkOutInfo['classroom5']}', '{$checkOutInfo['classroom6']}',
																										'$inputTime')";
									$conn->query($query);
								}
							}
							else if($checkOut['condition'] == FALSE) {
								if($searchTitle && $searchMajor) {
									$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
									$query = "SELECT * FROM $thisCourse WHERE major='$searchMajor' AND title='$searchTitle'";
								}
								else if($searchTitle && !$searchMajor) {
									$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
									$query = "SELECT * FROM $thisCourse WHERE title='$searchTitle'";
								} else {
									$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
									$query = "SELECT * FROM $thisCourse WHERE (title LIKE '%$searchWord') OR (title LIKE '$searchWord%') OR (title LIKE '%$searchWord%')";
								}
								message($send);
								$sql4courses = $conn->query($query);
								$checkOut = checkOverlap($sql4courses);	
								
								$send['elementsTitle'] = "교과목";
								$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
								messageTemplate($send);
								
								ReturningQR();
							} 
						} else {
							if($searchTitle && $searchMajor) {
								$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
								$query = "SELECT * FROM $thisCourse WHERE major='$searchMajor' AND title='$searchTitle'";
							}
							else if($searchTitle && !$searchMajor) {
								$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
								$query = "SELECT * FROM $thisCourse WHERE title='$searchTitle'";
							} else {
								$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
								$query = "SELECT * FROM $thisCourse WHERE (title LIKE '%$searchWord') OR (title LIKE '$searchWord%') OR (title LIKE '%$searchWord%')";
							}
							message($send);
							$sql4courses = $conn->query($query);
							$checkOut = checkOverlap($sql4courses);	
							
							$send['elementsTitle'] = "교과목";
							$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
							messageTemplate($send);
							
							ReturningQR();
						}
					}
					else if(preg_match("/OPT$/", $inProgress)) {
						if($payloadQR) {
							if($payloadQR == "⭕") {
								$optTitle = optTitle();
								
								$query = queryInsert('logging', 'START');
								$conn->query($query);			
											
								$send['text'] = "🎩: ".$optTitle;
								$send['payload'] = $send['title'] = array("교과목 추가 등록", "시간표 보기", "초기화면");
								messageQR($send);
							}
							else if($payloadQR == "❌") {
								if($searchWord && !$searchTitle && !$searchMajor) {
									$query = queryInsert('logging', 'REGISTER_INSERT');
									$conn->query($query);
									
									$send['text'] = "🎩: 교과목명을 입력해주세요.";
									message($send);
									
									ReturningQR();
								}
								else if($searchWord && $searchTitle && !$searchMajor) {
									$query = "SELECT * FROM $thisCourse WHERE (title LIKE '%$searchWord') OR (title LIKE '$searchWord%') OR (title LIKE '%$searchWord%')";
									$sql4courses = $conn->query($query);
									$checkOut = checkOverlap($sql4courses);
									if($checkOut['condition'] == TRUE) {
										if(preg_match("/multiple/", $checkOut['count'])) {
											if(preg_match("/multipleSort$/", $checkOut['count'])) {
												$query = queryInsert('logging', 'REGISTER_INSERT_1', array('searchWord'=>$searchWord));
												$conn->query($query);
												$send['elementsTitle'] = "교과목";
												$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
												messageTemplate($send);
													
												ReturningQR();
											}
										}
									}
									else if($checkOut['condition'] == FALSE) {
										if($checkOut['overcount'] == FALSE) {
											$query = queryInsert('logging', 'REGISTER_INSERT_1', array('searchWord'=>$searchWord));
											$conn->query($query);
											
											$resultArrChunk = array_chunk($checkOut['dbInfo'], 30);
											for($i=0; $i<count($resultArrChunk); $i++) {
												$send['elementsTitle'] = "교과목";
												$send['elementsButtonsTitle'] = $resultArrChunk[$i];
												messageTemplate($send);
											}
											ReturningQR();
										}
									}							
								}
								else if($searchWord && $searchTitle && $searchMajor) {
									$query = "SELECT * FROM $thisCourse WHERE title='$searchTitle'";
									$sql4courses = $conn->query($query);
									$checkOut = checkOverlap($sql4courses);
									if($checkOut['condition'] == TRUE) {
										if(preg_match("/multiple/", $checkOut['count'])) {
											if(preg_match("/multipleSort$/", $checkOut['count'])) {
												$query = queryInsert('logging', 'REGISTER_INSERT_2', array('searchWord'=>$searchWord, 'searchTitle'=>$searchTitle));
												$conn->query($query);
											
												$query = "SELECT DISTINCT major FROM $thisCourse WHERE title='$searchTitle'";
												$sql4courses = $conn->query($query);		
												while($row4courses = $sql4courses->fetch_assoc()) {
													if($row4courses['major'] != "") {
														$dbMajor[] = $row4courses['major'];
													}
												}
												$send['elementsTitle'] = "학과 구분";
												$send['elementsButtonsTitle'] = $dbMajor;
												messageTemplate($send);
												
												ReturningQR();										
											}
										}
									}
									else if($checkOut['condition'] == FALSE) {
										if($checkOut['overcount'] == FALSE) {
											$query = queryInsert('logging', 'REGISTER_INSERT_2', array('searchWord'=>$searchWord, 'searchTitle'=>$searchTitle));
											$conn->query($query);
											
											$query = "SELECT DISTINCT major FROM $thisCourse WHERE title='$searchTitle'";
											$sql4courses = $conn->query($query);		
											while($row4courses = $sql4courses->fetch_assoc()) {
												$dbMajor[] = $row4courses['major'];
											}
											
											$send['elementsTitle'] = "학과 구분";
											$send['elementsButtonsTitle'] = $dbMajor;
											messageTemplate($send);
											
											ReturningQR();
										}
									}
								}
							}			
						} else {
							$send['text'] = "🎩: 잘못된 접근입니다. 위의 <{$searchTitle}>을 확실히 등록하겠습니까?";
							$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
							messageQR($send);							
						}
					}
				}
				else if(preg_match("/GeneralSelc/", $inProgress) && $processingAllCount == 1 && $rgstGeneralSelc == 1) {
					$selectedDiv = "일반선택";
					if(preg_match("/GeneralSelc$/", $inProgress)) {
						if($messageText) {
							$searchWord = $messageText;
						
							$query = "SELECT * FROM $thisCourse WHERE divs='$selectedDiv' AND ((title LIKE '%$searchWord') OR (title LIKE '$searchWord%') OR (title LIKE '%$searchWord%'))";
							$sql4courses = $conn->query($query);
							$checkOut = checkOverlap($sql4courses);
							
							if($checkOut['condition'] == TRUE) {
								if($checkOut['count'] == "single") {
									if($checkOut['overlap'] == TRUE) {
										$send['text'] = "🎩: ".$checkOut['text'][1];
										message($send);
										
										ReturningQR();
									}
									else if($checkOut['overlap'] == FALSE) {
										$send['text'] = "🎩: ".$checkOut['text'];
										$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
										messageQR($send);
										
										$checkOutInfo = $checkOut['dbInfo'];
										$query = "INSERT INTO logging (year, semester, userkey, inProgress, searchWord, divs, fields, major, title, code, class, prof, department,
																							day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																								VALUE('$thisYear', '$thisSemester', '$senderID', 'REGISTER_GeneralSelc_OPT', '$searchWord', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
																											'{$checkOutInfo['title']}', '{$checkOutInfo['code']}', '{$checkOutInfo['class']}', '{$checkOutInfo['prof']}', '{$checkOutInfo['department']}',
																											'{$checkOutInfo['day1']}', '{$checkOutInfo['day2']}', '{$checkOutInfo['day3']}', '{$checkOutInfo['day4']}', '{$checkOutInfo['day5']}', '{$checkOutInfo['day6']}',
																											'{$checkOutInfo['time1']}', '{$checkOutInfo['time2']}', '{$checkOutInfo['time3']}', '{$checkOutInfo['time4']}', '{$checkOutInfo['time5']}', '{$checkOutInfo['time6']}',
																											'{$checkOutInfo['min1']}', '{$checkOutInfo['min2']}', '{$checkOutInfo['min3']}', '{$checkOutInfo['min4']}', '{$checkOutInfo['min5']}', '{$checkOutInfo['min6']}',
																											'{$checkOutInfo['classroom1']}', '{$checkOutInfo['classroom2']}', '{$checkOutInfo['classroom3']}', '{$checkOutInfo['classroom4']}', '{$checkOutInfo['classroom5']}', '{$checkOutInfo['classroom6']}',
																											'$inputTime')";
										$conn->query($query);
									}
								}
								else if(preg_match("/multiple/", $checkOut['count'])) {
									if(preg_match("/multiple$/", $checkOut['count'])) {
										$query = queryInsert('logging', 'REGISTER_GeneralSelc_2', array('searchWord'=>$searchWord));
									}
									else if(preg_match("/multipleSort$/", $checkOut['count'])) {
										$query = queryInsert('logging', 'REGISTER_GeneralSelc_1', array('searchWord'=>$searchWord));
									}
									$conn->query($query);
									$send['elementsTitle'] = "교과목";
									$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
									messageTemplate($send);
										
									ReturningQR();					
								}
							}
							else if($checkOut['condition'] == FALSE) {
								if($checkOut['dbInfo']) {
									if($checkOut['overcount'] == FALSE) {
										$send['text'] = "🎩: 검색된 교과목이 너무 많습니다.\n보다 더 상세하게 입력해주세요.";	
									}
								} else {
									$send['text'] = "🎩: 검색된 교과목이 없습니다.\n다시 한번 상세히 입력해주세요.";
								}
								message($send);
								ReturningQR();
							}
						} else {
							if(preg_match("/^교과목명(.*)입력$/", $payload) || preg_match("/^일반선택$/", $payload)) {
								if(preg_match("/^교과목명(.*)입력$/", $payload)) {
									$query = updateProcessing('insert');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_INSERT');
									$conn->query($query);					
								}
								else if(preg_match("/^일반선택$/", $payload)) {
									$query = updateProcessing('generalSelc');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_GeneralSelc');
									$conn->query($query);	
								}
								$send['text'] = "🎩: 교과목명을 입력해주세요.";
								message($send);
								
								ReturningQR();
							}
							else if(preg_match("/^전공$/", $payload) || preg_match("/^전공기초$/", $payload) || preg_match("/^교양필수$/", $payload)) {
								if(preg_match("/^전공$/", $payload)) {
									$query = updateProcessing('major');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_MAJOR');
									$conn->query($query);				
								}
								else if(preg_match("/^전공기초$/", $payload)) {
									$query = updateProcessing('majorBasic');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_MajorBASIC');
									$conn->query($query);
								}
								else if(preg_match("/^교양필수$/", $payload)) {
									$query = updateProcessing('liberalEssn');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_LiberalESSN');
									$conn->query($query);				
								}
					
								if(!isset($previousSearchMajor)) {
									$send['text'] = "🎩: 학과명을 입력해주세요.";
									message($send);
								} else {
									$send['text'] = "🎩: 이전에 검색한 학과를 선택 또는 새로 검색할 학과명을 입력해주세요.";
									message($send);
									
									$send['elementsTitle'] = "세부 구분";
									$send['elementsButtonsTitle'] = $previousSearchMajor;
									messageTemplate($send);		
								}
								
								ReturningQR();
							}
							else if(preg_match("/^교양$/", $payload)) {
								$query = updateProcessing('liberal');
								$conn->query($query);
								$query = queryInsert('logging', 'REGISTER_LIBERAL');
								$conn->query($query);
								
								$send['text'] = "🎩: 세부 구분을 선택해주세요.";
								message($send);
								
								$send['elementsTitle'] = "세부 구분";
								$send['elementsButtonsTitle'] = $dbAllFields=getCourseColumnData($thisCourse, 'fields');
								messageTemplate($send);
							
								ReturningQR();
							} else {
								$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 입력해주세요.";
								message($send);
								ReturningQR();
							}
						}
					}
					else if(preg_match("/[1]$/", $inProgress)) {
						if($payload) {
							$searchTitle = $payload;
							
							$query = "SELECT * FROM $thisCourse WHERE divs='$selectedDiv' AND title='$searchTitle'";
							$sql4courses = $conn->query($query);
							$checkOut = checkOverlap($sql4courses);
							
							if($checkOut['condition'] == TRUE) {
								if($checkOut['count'] == "single") {
									if($checkOut['overlap'] == TRUE) {
										$send['text'] = "🎩: ".$checkOut['text'][0];
										message($send);
									
										$query = "SELECT DISTINCT title FROM $thisCourse WHERE divs='$selectedDiv' AND ((title LIKE '%$searchWord') OR (title LIKE '$searchWord%') OR (title LIKE '%$searchWord%'))";
										$sql4courses = $conn->query($query);
										while($row4courses = $sql4courses->fetch_assoc()) {
											$dbTitle[] = $row4courses['title'];
										}
										
										$send['elementsTitle'] = "교과목";
										$send['elementsButtonsTitle'] = $dbTitle;
										messageTemplate($send);
										
										ReturningQR();
									}
									else if($checkOut['overlap'] == FALSE) {
										$send['text'] = "🎩: ".$checkOut['text'];
										$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
										messageQR($send);
										
										$checkOutInfo = $checkOut['dbInfo'];
										$query = "INSERT INTO logging (year, semester, userkey, inProgress, searchWord, searchTitle, divs, fields, major, title, code, class, prof, department,
																							day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																								VALUE('$thisYear', '$thisSemester', '$senderID', 'REGISTER_GeneralSelc_OPT', '$searchWord', '$searchTitle', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
																											'{$checkOutInfo['title']}', '{$checkOutInfo['code']}', '{$checkOutInfo['class']}', '{$checkOutInfo['prof']}', '{$checkOutInfo['department']}',
																											'{$checkOutInfo['day1']}', '{$checkOutInfo['day2']}', '{$checkOutInfo['day3']}', '{$checkOutInfo['day4']}', '{$checkOutInfo['day5']}', '{$checkOutInfo['day6']}',
																											'{$checkOutInfo['time1']}', '{$checkOutInfo['time2']}', '{$checkOutInfo['time3']}', '{$checkOutInfo['time4']}', '{$checkOutInfo['time5']}', '{$checkOutInfo['time6']}',
																											'{$checkOutInfo['min1']}', '{$checkOutInfo['min2']}', '{$checkOutInfo['min3']}', '{$checkOutInfo['min4']}', '{$checkOutInfo['min5']}', '{$checkOutInfo['min6']}',
																											'{$checkOutInfo['classroom1']}', '{$checkOutInfo['classroom2']}', '{$checkOutInfo['classroom3']}', '{$checkOutInfo['classroom4']}', '{$checkOutInfo['classroom5']}', '{$checkOutInfo['classroom6']}',
																											'$inputTime')";
										$conn->query($query);
									}
								}
								else if($checkOut['count'] == "multiple") {
									$query = queryInsert('logging', 'REGISTER_GeneralSelc_2', array('searchWord'=>$searchWord, 'searchTitle'=>$searchTitle));
									$conn->query($query);
									
									$send['elementsTitle'] = "교과목";
									$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
									messageTemplate($send);
										
									ReturningQR();								
								}
							} 
							else if($checkOut['condition'] == FALSE) {
								if($checkOut['overcount'] == FALSE) {
									
									// 일반선택은 추가적으로 구분할 항목(ex. grade or major)이 더 없다고 판단
											
								}
								else if(!in_array($searchTitle, $dbAllTitle=getCourseColumnData($thisCourse, 'tite'))) {
									$send['text'] = "🎩: 올바른 교과목명이 아닌 것 같아요. 다시 똑.디. 선택해주세요.";
									message($send);
									
									$query = "SELECT DISTINCT title FROM $thisCourse WHERE divs='$selectedDiv' AND ((title LIKE '%$searchWord') OR (title LIKE '$searchWord%') OR (title LIKE '%$searchWord%'))";
									$sql4courses = $conn->query($query);
									while($row4courses = $sql4courses->fetch_assoc()) {
										$dbTitle[] = $row4courses['title'];
									}
									
									$send['elementsTitle'] = "교과목";
									$send['elementsButtonsTitle'] = $dbTitle;
									messageTemplate($send);
									
									ReturningQR();
								}
							}
						} else {
							$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
							message($send);				
							
							$query = "SELECT DISTINCT title FROM $thisCourse WHERE divs='$selectedDiv' AND ((title LIKE '%$searchWord') OR (title LIKE '$searchWord%') OR (title LIKE '%$searchWord%'))";
							$sql4courses = $conn->query($query);
							while($row4courses = $sql4courses->fetch_assoc()) {
								$dbTitle[] = $row4courses['title'];
							}
							
							$send['elementsTitle'] = "교과목";
							$send['elementsButtonsTitle'] = $dbTitle;
							messageTemplate($send);
							
							ReturningQR();		
						}
					}
					else if(preg_match("/[2]$/", $inProgress)) {
						if($payload) {
							if(strpos($payload, "(") !== FALSE) {
								$payloadExp = explode("(", (str_replace(")", "", $payload)));
								// 단일 분류
								if(substr_count($payload, "(") >= 2) {
									$payloadTitle = $payloadExp[0] . "(" . $payloadExp[1] . ")";
									$payloadInfo = $payloadExp[2];	
								}
								else if(substr_count($payload, "(") == 1) {
									$payloadTitle = $payloadExp[0];
									$payloadInfo = $payloadExp[1];
								}
								
								// 복수 분류
								//// 분반 분류
								if(strlen($payloadInfo) == 3 && preg_match("/[0-9]/", $payloadInfo)) {
									$payloadInfoClass = $payloadInfo;
								}
								//// 교수명 분류
								else if(strpos($payloadInfo, "교수님") !== FALSE) {
									$payloadInfoProf = str_replace("교수님", "", $payloadInfo);
								} else {
								//// 학부 분류
									if(mb_strlen($payloadInfo, UTF8) > 1 && preg_match("/[\xA1-\xFE\xA1-\xFE]/",$payloadInfo)) {
										$payloadInfoDepartment = $payloadInfo;
									} else {
										////// 괄호 안 문자가 분류대상이 아닐 경우
										$payloadTitle = $payload;
										unset($payloadInfo);
									}
								}
								if($payloadInfo) {
									$query = "SELECT * FROM $thisCourse WHERE divs='$selectedDiv' AND title='$payloadTitle' AND 
																					(
																						class='$payloadInfoClass' OR department='$payloadInfoDepartment' OR (prof!='' AND prof='$payloadInfoProf')
																					)";											
								} else {
									$query = "SELECT * FROM $thisCourse WHERE divs='$selectedDiv' AND title='$payloadTitle'";								
								}
							} else {
								$query = "SELECT * FROM $thisCourse WHERE divs='$selectedDiv' AND title='$payload'";								
							}
							$sql4courses = $conn->query($query);	
							$checkOut = checkOverlapReturn($sql4courses);
							if($checkOut['condition'] == TRUE) {
								if($checkOut['overlap'] == TRUE) {
									$send['text'] = "🎩: ".$checkOut['text'][0];
									message($send);
									
									$send['elementsTitle'] = "교과목";
									$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
									messageTemplate($send);
									
									ReturningQR();
								}
								else if($checkOut['overlap'] == FALSE) {
									$send['text'] = "🎩: ".$checkOut['text'];
									$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
									messageQR($send);
									
									$checkOutInfo = $checkOut['dbInfo'];
									$query = "INSERT INTO logging (year, semester, userkey, inProgress, searchWord, searchTitle, divs, fields, major, title, code, class, prof, department,
																						day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																							VALUE('$thisYear', '$thisSemester', '$senderID', 'REGISTER_GeneralSelc_OPT', '$searchWord', '$searchTitle', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
																										'{$checkOutInfo['title']}', '{$checkOutInfo['code']}', '{$checkOutInfo['class']}', '{$checkOutInfo['prof']}', '{$checkOutInfo['department']}',
																										'{$checkOutInfo['day1']}', '{$checkOutInfo['day2']}', '{$checkOutInfo['day3']}', '{$checkOutInfo['day4']}', '{$checkOutInfo['day5']}', '{$checkOutInfo['day6']}',
																										'{$checkOutInfo['time1']}', '{$checkOutInfo['time2']}', '{$checkOutInfo['time3']}', '{$checkOutInfo['time4']}', '{$checkOutInfo['time5']}', '{$checkOutInfo['time6']}',
																										'{$checkOutInfo['min1']}', '{$checkOutInfo['min2']}', '{$checkOutInfo['min3']}', '{$checkOutInfo['min4']}', '{$checkOutInfo['min5']}', '{$checkOutInfo['min6']}',
																										'{$checkOutInfo['classroom1']}', '{$checkOutInfo['classroom2']}', '{$checkOutInfo['classroom3']}', '{$checkOutInfo['classroom4']}', '{$checkOutInfo['classroom5']}', '{$checkOutInfo['classroom6']}',
																										'$inputTime')";
									$conn->query($query);
								}
							}
							else if($checkOut['condition'] == FALSE) {
								$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
								message($send);
								
								if($searchTitle) {
									$query = "SELECT DISTINCT title FROM $thisCourse WHERE divs='$selectedDiv' AND title='$searchTitle'";
								} else {
									$query = "SELECT DISTINCT title FROM $thisCourse WHERE divs='$selectedDiv' AND ((title LIKE '%$searchWord') OR (title LIKE '$searchWord%') OR (title LIKE '%$searchWord%'))";						
								}
								$sql4courses = $conn->query($query);
								while($row4courses = $sql4courses->fetch_assoc()) {
									$dbTitle[] = $row4courses['title'];
								}
								
								$send['elementsTitle'] = "교과목";
								$send['elementsButtonsTitle'] = $dbTitle;
								messageTemplate($send);
								
								ReturningQR();	
							}	
						} else {
							if(preg_match("/^교과목명(.*)입력$/", $payload) || preg_match("/^일반선택$/", $payload)) {
								if(preg_match("/^교과목명(.*)입력$/", $payload)) {
									$query = updateProcessing('insert');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_INSERT');
									$conn->query($query);					
								}
								else if(preg_match("/^일반선택$/", $payload)) {
									$query = updateProcessing('generalSelc');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_GeneralSelc');
									$conn->query($query);	
								}
								$send['text'] = "🎩: 교과목명을 입력해주세요.";
								message($send);
								
								ReturningQR();
							}
							else if(preg_match("/^전공$/", $payload) || preg_match("/^전공기초$/", $payload) || preg_match("/^교양필수$/", $payload)) {
								if(preg_match("/^전공$/", $payload)) {
									$query = updateProcessing('major');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_MAJOR');
									$conn->query($query);				
								}
								else if(preg_match("/^전공기초$/", $payload)) {
									$query = updateProcessing('majorBasic');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_MajorBASIC');
									$conn->query($query);
								}
								else if(preg_match("/^교양필수$/", $payload)) {
									$query = updateProcessing('liberalEssn');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_LiberalESSN');
									$conn->query($query);				
								}
					
								if(!isset($previousSearchMajor)) {
									$send['text'] = "🎩: 학과명을 입력해주세요.";
									message($send);
								} else {
									$send['text'] = "🎩: 이전에 검색한 학과를 선택 또는 새로 검색할 학과명을 입력해주세요.";
									message($send);
									
									$send['elementsTitle'] = "세부 구분";
									$send['elementsButtonsTitle'] = $previousSearchMajor;
									messageTemplate($send);		
								}
								
								ReturningQR();
							}
							else if(preg_match("/^교양$/", $payload)) {
								$query = updateProcessing('liberal');
								$conn->query($query);
								$query = queryInsert('logging', 'REGISTER_LIBERAL');
								$conn->query($query);
								
								$send['text'] = "🎩: 세부 구분을 선택해주세요.";
								message($send);
								
								$send['elementsTitle'] = "세부 구분";
								$send['elementsButtonsTitle'] = $dbAllFields=getCourseColumnData($thisCourse, 'fields');
								messageTemplate($send);
							
								ReturningQR();
							} else {
								$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
								message($send);
								
								if($searchTitle) {
									$query = "SELECT DISTINCT title FROM $thisCourse WHERE divs='$selectedDiv' AND title='$searchTitle'";
								} else {
									$query = "SELECT DISTINCT title FROM $thisCourse WHERE divs='$selectedDiv' AND ((title LIKE '%$searchWord') OR (title LIKE '$searchWord%') OR (title LIKE '%$searchWord%'))";						
								}
								$sql4courses = $conn->query($query);
								while($row4courses = $sql4courses->fetch_assoc()) {
									$dbTitle[] = $row4courses['title'];
								}
								
								$send['elementsTitle'] = "교과목";
								$send['elementsButtonsTitle'] = $dbTitle;
								messageTemplate($send);
								
								ReturningQR();	
							}					
						}
					}
					else if(preg_match("/OPT$/", $inProgress)) {
						if($payloadQR) {
							if($payloadQR == "⭕") {
								$optTitle = optTitle();
								
								$query = queryInsert('logging', 'START');
								$conn->query($query);			
											
								$send['text'] = "🎩: ".$optTitle;
								$send['payload'] = $send['title'] = array("교과목 추가 등록", "시간표 보기", "초기화면");
								messageQR($send);
							}
							else if($payloadQR == "❌") {
								$query = "SELECT * FROM $thisCourse WHERE divs='$selectedDivs' AND ((title LIKE '%$searchWord') OR (title LIKE '$searchWord%') OR (title LIKE '%$searchWord%'))";
								$sql4courses = $conn->query($query);
								$checkOut = checkOverlap($sql4courses);
								
								if($checkOut['condition'] == TRUE) {
									if(count($checkOut['dbInfo']) > 1 && count($checkOut['dbInfo']) < 31) {
										$query = queryInsert('logging', 'REGISTER_GeneralSelc_1', array('searchWord'=>$searchWord));
										$conn->query($query);
										
										$send['elementsTitle'] = "교과목";
										$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
										messageTemplate($send);
											
										ReturningQR();
									} else {
										$query = queryInsert('logging', 'REGISTER_GeneralSelc');
										$conn->query($query);
										
										$send['text'] = "🎩: 교과목명을 입력해주세요.";
										message($send);
							
										ReturningQR();
									}
								}
								else if($checkOut['condition'] == FALSE) {
									$query = queryInsert('logging', 'REGISTER_GeneralSelc');
									$conn->query($query);
									
									$send['text'] = "🎩: 교과목명을 입력해주세요.";
									message($send);
						
									ReturningQR();
								}
							}			
						} else {
							$send['text'] = "🎩: 잘못된 접근입니다. 위의 <{$searchTitle}>을 확실히 등록하겠습니까?";
							$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
							messageQR($send);									
						}
					}
				}
				else if(preg_match("/LIBERAL/", $inProgress) && $processingAllCount == 1 && $rgstLiberal == 1) {
					$selectedDiv = "교양";
					if(preg_match("/LIBERAL$/", $inProgress)) {
						if($payload) {
							$searchFields = $payload;
						
							if(in_array($searchFields, $dbAllFields=getCourseColumnData($thisCourse, 'fields'))) {
								$query = queryInsert('logging', 'REGISTER_LIBERAL_1', array('searchFields'=>$searchFields));
								$conn->query($query);
								
								$send["text"] = "🎩: 교과목을 선택해 주세요.";
								message($send);
								
								$query = "SELECT DISTINCT title FROM $thisCourse WHERE divs='$selectedDiv' AND fields='$searchFields'";
								$sql4courses = $conn->query($query);
								while($row4courses = $sql4courses->fetch_assoc()) {
									$dbTitle[] = $row4courses['title'];
								}
								$dbTitleArrChunk = array_chunk($dbTitle, 30);
								for($i=0; $i<count($dbTitleArrChunk); $i++) {
									$send['elementsTitle'] = "교과목";
									$send['elementsButtonsTitle'] = $dbTitleArrChunk[$i];
									messageTemplate($send);								
								}

								ReturningQR();
							}
							else if(!in_array($searchFields, $dbAllFields=getCourseColumnData($thisCourse, 'fields'))) {
								$send['text'] = "🎩: 올바른 영역이 아닌 것 같아요. 다시 똑.디. 선택해주세요.";
								message($send);
								
								$send['elementsTitle'] = "세부 구분";
								$send['elementsButtonsTitle'] = $dbAllFields=getCourseColumnData($thisCourse, 'fields');
								messageTemplate($send);
								
								ReturningQR();
							}
						} else {
							if(preg_match("/^교과목명(.*)입력$/", $payload) || preg_match("/^일반선택$/", $payload)) {
								if(preg_match("/^교과목명(.*)입력$/", $payload)) {
									$query = updateProcessing('insert');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_INSERT');
									$conn->query($query);					
								}
								else if(preg_match("/^일반선택$/", $payload)) {
									$query = updateProcessing('generalSelc');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_GeneralSelc');
									$conn->query($query);	
								}
								$send['text'] = "🎩: 교과목명을 입력해주세요.";
								message($send);
								
								ReturningQR();
							}
							else if(preg_match("/^전공$/", $payload) || preg_match("/^전공기초$/", $payload) || preg_match("/^교양필수$/", $payload)) {
								if(preg_match("/^전공$/", $payload)) {
									$query = updateProcessing('major');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_MAJOR');
									$conn->query($query);				
								}
								else if(preg_match("/^전공기초$/", $payload)) {
									$query = updateProcessing('majorBasic');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_MajorBASIC');
									$conn->query($query);
								}
								else if(preg_match("/^교양필수$/", $payload)) {
									$query = updateProcessing('liberalEssn');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_LiberalESSN');
									$conn->query($query);				
								}
					
								if(!isset($previousSearchMajor)) {
									$send['text'] = "🎩: 학과명을 입력해주세요.";
									message($send);
								} else {
									$send['text'] = "🎩: 이전에 검색한 학과를 선택 또는 새로 검색할 학과명을 입력해주세요.";
									message($send);
									
									$send['elementsTitle'] = "세부 구분";
									$send['elementsButtonsTitle'] = $previousSearchMajor;
									messageTemplate($send);		
								}
								
								ReturningQR();
							}
							else if(preg_match("/^교양$/", $payload)) {
								$query = updateProcessing('liberal');
								$conn->query($query);
								$query = queryInsert('logging', 'REGISTER_LIBERAL');
								$conn->query($query);
								
								$send['text'] = "🎩: 세부 구분을 선택해주세요.";
								message($send);
								
								$send['elementsTitle'] = "세부 구분";
								$send['elementsButtonsTitle'] = $dbAllFields=getCourseColumnData($thisCourse, 'fields');
								messageTemplate($send);
							
								ReturningQR();
							} else {
								$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
								message($send);
									
								$send['elementsTitle'] = "세부 구분";
								$send['elementsButtonsTitle'] = $dbAllFields=getCourseColumnData($thisCourse, 'fields');
								messageTemplate($send);
								
								ReturningQR();			
							}				
						}
					}
					else if(preg_match("/[1]$/", $inProgress)) {
						if($payload) {
							$searchTitle = $payload;
							
							$query = "SELECT * FROM $thisCourse WHERE divs='$selectedDiv' AND fields='$searchFields' AND title='$searchTitle'";
							$sql4courses = $conn->query($query);
							$checkOut = checkOverlap($sql4courses);
							
							if($checkOut['condition'] == TRUE) {
								if($checkOut['count'] == "single") {
									if($checkOut['overlap'] == TRUE) {
										$send['text'] = "🎩: ".$checkOut['text'][0];
										message($send);
										
										$query = "SELECT DISTINCT title FROM $thisCourse WHERE divs='$selectedDiv' AND fields='$searchFields'";
										$sql4courses = $conn->query($query);
										while($row4courses = $sql4courses->fetch_assoc()) {
											$dbTitle[] = $row4courses['title'];
										}
										$dbTitleArrChunk = array_chunk($dbTitle, 30);
										for($i=0; $i<count($dbTitleArrChunk); $i++) {
											$send['elementsTitle'] = "교과목";
											$send['elementsButtonsTitle'] = $dbTitleArrChunk[$i];
											messageTemplate($send);								
										}
							
										ReturningQR();	
									}
									else if($checkOut['overlap'] == FALSE) {
										$send['text'] = "🎩: ".$checkOut['text'];
										$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
										messageQR($send);
										
										$checkOutInfo = $checkOut['dbInfo'];
										$query = "INSERT INTO logging (year, semester, userkey, inProgress, searchFields, searchTitle, divs, fields, major, title, code, class, prof, department,
																							day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																								VALUE('$thisYear', '$thisSemester', '$senderID', 'REGISTER_LIBERAL_OPT', '$searchFields', '$searchTitle', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
																											'{$checkOutInfo['title']}', '{$checkOutInfo['code']}', '{$checkOutInfo['class']}', '{$checkOutInfo['prof']}', '{$checkOutInfo['department']}',
																											'{$checkOutInfo['day1']}', '{$checkOutInfo['day2']}', '{$checkOutInfo['day3']}', '{$checkOutInfo['day4']}', '{$checkOutInfo['day5']}', '{$checkOutInfo['day6']}',
																											'{$checkOutInfo['time1']}', '{$checkOutInfo['time2']}', '{$checkOutInfo['time3']}', '{$checkOutInfo['time4']}', '{$checkOutInfo['time5']}', '{$checkOutInfo['time6']}',
																											'{$checkOutInfo['min1']}', '{$checkOutInfo['min2']}', '{$checkOutInfo['min3']}', '{$checkOutInfo['min4']}', '{$checkOutInfo['min5']}', '{$checkOutInfo['min6']}',
																											'{$checkOutInfo['classroom1']}', '{$checkOutInfo['classroom2']}', '{$checkOutInfo['classroom3']}', '{$checkOutInfo['classroom4']}', '{$checkOutInfo['classroom5']}', '{$checkOutInfo['classroom6']}',
																											'$inputTime')";
										$conn->query($query);
									}
								}
								else if($checkOut['count'] == "multiple") {
									$query = queryInsert('logging', 'REGISTER_LIBERAL_2', array('searchFields'=>$searchFields, 'searchTitle'=>$searchTitle));
									$conn->query($query);
									
									$send['elementsTitle'] = "교과목";
									$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
									messageTemplate($send);
										
									ReturningQR();
								}
							}
							else if($checkOut['condition'] == FALSE) {
								if(!in_array($searchTitle, $dbAllTitle=getCourseColumnData($thisCourse, 'tite'))) {
									$send['text'] = "🎩: 올바른 교과목명이 아닌 것 같아요. 다시 똑.디. 선택해주세요.";
									message($send);
									
									$query = "SELECT DISTINCT title FROM $thisCourse WHERE divs='$selectedDiv' AND fields='$searchFields'";
									$sql4courses = $conn->query($query);
									while($row4courses = $sql4courses->fetch_assoc()) {
										$dbTitle[] = $row4courses['title'];
									}
									$dbTitleArrChunk = array_chunk($dbTitle, 30);
									for($i=0; $i<count($dbTitleArrChunk); $i++) {
										$send['elementsTitle'] = "교과목";
										$send['elementsButtonsTitle'] = $dbTitleArrChunk[$i];
										messageTemplate($send);								
									}
	
									ReturningQR();
								} else {
									$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
									message($send);
									
									$query = "SELECT DISTINCT title FROM $thisCourse WHERE divs='$selectedDiv' AND fields='$searchFields'";
									$sql4courses = $conn->query($query);
									while($row4courses = $sql4courses->fetch_assoc()) {
										$dbTitle[] = $row4courses['title'];
									}
									$dbTitleArrChunk = array_chunk($dbTitle, 30);
									for($i=0; $i<count($dbTitleArrChunk); $i++) {
										$send['elementsTitle'] = "교과목";
										$send['elementsButtonsTitle'] = $dbTitleArrChunk[$i];
										messageTemplate($send);								
									}
	
									ReturningQR();
								}
							} 
						} else {
							$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
							message($send);
							
							$query = "SELECT DISTINCT title FROM $thisCourse WHERE divs='$selectedDiv' AND fields='$searchFields'";
							$sql4courses = $conn->query($query);
							while($row4courses = $sql4courses->fetch_assoc()) {
								$dbTitle[] = $row4courses['title'];
							}
							$dbTitleArrChunk = array_chunk($dbTitle, 30);
							for($i=0; $i<count($dbTitleArrChunk); $i++) {
								$send['elementsTitle'] = "교과목";
								$send['elementsButtonsTitle'] = $dbTitleArrChunk[$i];
								messageTemplate($send);								
							}

							ReturningQR();							
						}		
					}
					else if(preg_match("/[2]$/", $inProgress)) {
						if($payload) {
							if(strpos($payload, "(") !== FALSE) {
								$payloadExp = explode("(", (str_replace(")", "", $payload)));
								// 단일 분류
								if(substr_count($payload, "(") >= 2) {
									$payloadTitle = $payloadExp[0] . "(" . $payloadExp[1] . ")";
									$payloadInfo = $payloadExp[2];	
								}
								else if(substr_count($payload, "(") == 1) {
									$payloadTitle = $payloadExp[0];
									$payloadInfo = $payloadExp[1];
								}
								
								// 복수 분류
								//// 분반 분류
								if(strlen($payloadInfo) == 3 && preg_match("/[0-9]/", $payloadInfo)) {
									$payloadInfoClass = $payloadInfo;
								}
								//// 교수명 분류
								else if(strpos($payloadInfo, "교수님") !== FALSE) {
									$payloadInfoProf = str_replace("교수님", "", $payloadInfo);
								} else {
								//// 학부 분류
									if(mb_strlen($payloadInfo, UTF8) > 1 && preg_match("/[\xA1-\xFE\xA1-\xFE]/",$payloadInfo)) {
										$payloadInfoDepartment = $payloadInfo;
									} else {
										////// 괄호 안 문자가 분류대상이 아닐 경우
										$payloadTitle = $payload;
										unset($payloadInfo);
									}
								}
								if($payloadInfo) {
									$query = "SELECT * FROM $thisCourse WHERE divs='$selectedDiv' AND fields='$searchFields' AND title='$payloadTitle' AND
																					(
																						class='$payloadInfoClass' OR department='$payloadInfoDepartment' OR (prof!='' AND prof='$payloadInfoProf')
																					)";
								} else {
									$query = "SELECT * FROM $thisCourse WHERE divs='$selectedDiv' AND title='$payloadTitle'";			
								}
							} else {
								$query = "SELECT * FROM $thisCourse WHERE divs='$selectedDiv' AND title='$payload'";
							}
							$sql4courses = $conn->query($query);
							$checkOut = checkOverlapReturn($sql4courses);
							if($checkOut['condition'] == TRUE) {
								if($checkOut['overlap'] == TRUE) {
									$send['text'] = "🎩: ".$checkOut['text'][0];
									message($send);
									
									$send['elementsTitle'] = "교과목";
									$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
									messageTemplate($send);
									
									ReturningQR();
								}
								else if($checkOut['overlap'] == FALSE) {
									$send['text'] = "🎩: ".$checkOut['text'];
									$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
									messageQR($send);
									
									$checkOutInfo = $checkOut['dbInfo'];
									
									$query = "INSERT INTO logging (year, semester, userkey, inProgress, searchFields, searchTitle, divs, fields, major, title, code, class, prof, department,
																						day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																							VALUE('$thisYear', '$thisSemester', '$senderID', 'REGISTER_LIBERAL_OPT', '$searchFields', '$searchTitle', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
																										'{$checkOutInfo['title']}', '{$checkOutInfo['code']}', '{$checkOutInfo['class']}', '{$checkOutInfo['prof']}', '{$checkOutInfo['department']}',
																										'{$checkOutInfo['day1']}', '{$checkOutInfo['day2']}', '{$checkOutInfo['day3']}', '{$checkOutInfo['day4']}', '{$checkOutInfo['day5']}', '{$checkOutInfo['day6']}',
																										'{$checkOutInfo['time1']}', '{$checkOutInfo['time2']}', '{$checkOutInfo['time3']}', '{$checkOutInfo['time4']}', '{$checkOutInfo['time5']}', '{$checkOutInfo['time6']}',
																										'{$checkOutInfo['min1']}', '{$checkOutInfo['min2']}', '{$checkOutInfo['min3']}', '{$checkOutInfo['min4']}', '{$checkOutInfo['min5']}', '{$checkOutInfo['min6']}',
																										'{$checkOutInfo['classroom1']}', '{$checkOutInfo['classroom2']}', '{$checkOutInfo['classroom3']}', '{$checkOutInfo['classroom4']}', '{$checkOutInfo['classroom5']}', '{$checkOutInfo['classroom6']}',
																										'$inputTime')";
									$conn->query($query);
								}
							}
							else if($checkOut['condition'] == FALSE) {
								$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
								message($send);			
								
								$query = "SELECT * FROM $thisCourse WHERE divs='$selectedDiv' AND fields='$searchFields' AND title='$searchTitle'";
								$sql4courses = $conn->query($query);
								$checkOut = checkOverlap($sql4courses);			
																	
								$send['elementsTitle'] = "교과목";
								$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
								messageTemplate($send);
								
								ReturningQR();
							}	
						} else {
							$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
							message($send);			
							
							$query = "SELECT * FROM $thisCourse WHERE divs='$selectedDiv' AND fields='$searchFields' AND title='$searchTitle'";
							$sql4courses = $conn->query($query);
							$checkOut = checkOverlap($sql4courses);			
																
							$send['elementsTitle'] = "교과목";
							$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
							messageTemplate($send);
							
							ReturningQR();						
						}
					}
					else if(preg_match("/OPT$/", $inProgress)) {
						if($payloadQR) {
							if($payloadQR == "⭕") {
								$optTitle = optTitle();
			
								$query = queryInsert('logging', 'START');
								$conn->query($query);		
												
								$send['text'] = "🎩: ".$optTitle;
								$send['payload'] = $send['title'] = array("교과목 추가 등록", "시간표 보기", "초기화면");
								messageQR($send);
							}
							else 	if($payloadQR == "❌") {
								$query = queryInsert('logging', 'REGISTER_LIBERAL_1', array('searchFields'=>$searchFields));
								$conn->query($query);
								
								$send["text"] = "🎩: 교과목을 선택해 주세요.";
								message($send);	
								
								$query = "SELECT DISTINCT title FROM $thisCourse WHERE divs='$selectedDiv' AND fields='$searchFields'";
								$sql4courses = $conn->query($query);
								while($row4courses = $sql4courses->fetch_assoc()) {
									$dbTitle[] = $row4courses['title'];
								}
								$dbTitleArrChunk = array_chunk($dbTitle, 30);
								for($i=0; $i<count($dbTitleArrChunk); $i++) {
									$send['elementsTitle'] = "교과목";
									$send['elementsButtonsTitle'] = $dbTitleArrChunk[$i];
									messageTemplate($send);								
								}

								ReturningQR();
							}
						} else {
							$send['text'] = "🎩: 잘못된 접근입니다. 위의 <{$searchTitle}>을 확실히 등록하겠습니까?";
							$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
							messageQR($send);
						}
					}
				}
				else if(((preg_match("/MAJOR/", $inProgress) && $rgstMajor == 1) || (preg_match("/MajorBASIC/", $inProgress) && $rgstMajorBasic== 1) || (preg_match("/LiberalESSN/", $inProgress) && $rgstLiberalEssn == 1)) && $processingAllCount == 1) {
					if(preg_match("/MAJOR/", $inProgress)) {
						$selectedDiv = "전공";
					}
					else if(preg_match("/MajorBASIC/", $inProgress)) {
						$selectedDiv = "전공기초";
					}
					else if(preg_match("/LiberalESSN/", $inProgress)) {
						$selectedDiv = "교양필수";
					}
					
					if(preg_match("/MAJOR$/", $inProgress) || preg_match("/MajorBASIC$/", $inProgress) || preg_match("/LiberalESSN$/", $inProgress)) {
						if($messageText) {
							$searchWord = $messageText;
							
							$query = "SELECT DISTINCT major FROM $thisCourse WHERE divs='$selectedDiv' AND ((major='$searchWord') OR (major LIKE '%$searchWord') OR (major LIKE '$searchWord%') OR (major LIKE '%$searchWord%'))";
							$sql4coursesMajor = $conn->query($query);
							while($row4coursesMajor = $sql4coursesMajor->fetch_assoc()) {
								$dbResultMajor[] = $row4coursesMajor['major'];
							}
					
							if(!empty($dbResultMajor) && count($dbResultMajor) > 1) {
								if(preg_match("/MAJOR$/", $inProgress)) {
									$query = queryInsert('logging', 'REGISTER_MAJOR_1', array('searchWord'=>$searchWord));
								}
								else if(preg_match("/MajorBASIC$/", $inProgress)) {
									$query = queryInsert('logging', 'REGISTER_MajorBASIC_1', array('searchWord'=>$searchWord));	
								}
								else if(preg_match("/LiberalESSN$/", $inProgress)) {
									$query = queryInsert('logging', 'REGISTER_LiberalESSN_1', array('searchWord'=>$searchWord));
								}
								$conn->query($query);
								
								$send['text'] = "🎩: 본인의 학과명을 선택해주세요.";
								message($send);
										
								$send['elementsTitle'] = "학과 구분";
								$send['elementsButtonsTitle'] = $dbResultMajor;
								messageTemplate($send);
								
								ReturningQR();
							}
							else if(!empty($dbResultMajor) && count($dbResultMajor) == 1) {
								$searchMajor = $dbResultMajor[0];
								
								if(preg_match("/MAJOR$/", $inProgress)) {
									$query = queryInsert('logging', 'REGISTER_MAJOR_OPT_1st', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
								}
								else if(preg_match("/MajorBASIC$/", $inProgress)) {
									$query = queryInsert('logging', 'REGISTER_MajorBASIC_OPT_1st', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
								}	
								else if(preg_match("/LiberalESSN$/", $inProgress)) {
									$query = queryInsert('logging', 'REGISTER_LiberalESSN_OPT_1st', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
								}
								$conn->query($query);			
								
								$send['text'] = "🎩: 입력하신 학과가 <" . $searchMajor . "> 맞나요?";
								$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
								messageQR($send);
							}
							else if(empty($dbResultMajor)) {
								$send['text'] = "🎩: 그런 학과는 없는 것 같아요.\n학과명을 다시 입력해주세요.";
								message($send);
								
								ReturningQR();
							}
						}
						else if($payload) {
							$searchMajor = $payload;	
							
							$query = "SELECT * FROM $thisCourse WHERE divs='$selectedDiv' AND major='$searchMajor'";
							$sql4courses = $conn->query($query);
							while($row4courses = $sql4courses->fetch_assoc()) {
								$dbGrade[] = $row4courses['grade'] . "학년";
								$dbGrade = array_keys(array_flip($dbGrade));
								$dbTitle[] = $row4courses['title'];
								$dbTitle = array_keys(array_flip($dbTitle));
							}
							
							if(in_array($searchMajor, $dbAllMajor=getCourseColumnData($thisCourse, 'major'))) {
								if(count($dbTitle) > 30) {
									if(preg_match("/MAJOR$/", $inProgress)) {
										$query = queryInsert('logging', 'REGISTER_MAJOR_2', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
									}
									else if(preg_match("/MajorBASIC$/", $inProgress)) {
										$query = queryInsert('logging', 'REGISTER_MajorBASIC_2', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
									}	
									else if(preg_match("/LiberalESSN$/", $inProgress)) {
										$query = queryInsert('logging', 'REGISTER_LiberalESSN_2', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
									}
									$conn->query($query);
									
									$send['text'] = "🎩: 검색된 교과목이 너무 많습니다.\n찾으시는 교과목은 몇 학년 수업인가요?";
									message($send);
											
									$send['elementsTitle'] = "학년 구분";
									$send['elementsButtonsTitle'] = $dbGrade;
									messageTemplate($send);
									 
									ReturningQR();
								}
								else if(count($dbTitle) <= 30) {
									if(preg_match("/MAJOR$/", $inProgress)) {
										$query = queryInsert('logging', 'REGISTER_MAJOR_3', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
									}
									else if(preg_match("/MajorBASIC$/", $inProgress)) {
										$query = queryInsert('logging', 'REGISTER_MajorBASIC_3', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
									}	
									else if(preg_match("/LiberalESSN$/", $inProgress)) {
										$query = queryInsert('logging', 'REGISTER_LiberalESSN_3', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
									}
									$conn->query($query);		
					
									$send["text"] = "🎩: 교과목을 선택해 주세요.";
									message($send);
					
									$send['elementsTitle'] = "교과목";
									$send['elementsButtonsTitle'] = $dbTitle;
									messageTemplate($send);
									
									ReturningQR();
								}								
							}
							else if (!in_array($searchMajor, $dbAllMajor=getCourseColumnData($thisCourse, 'major'))) {
								$send['text'] = "🎩: 올바른 학과명이 아닌 것 같아요. 다시 똑.디. 선택해주세요.";
								message($send);
								
								$send['elementsTitle'] = "세부 구분";
								$send['elementsButtonsTitle'] = $previousSearchMajor;
								messageTemplate($send);				
														
								ReturningQR();
							}
						} else {
							if(preg_match("/^교과목명(.*)입력$/", $payload) || preg_match("/^일반선택$/", $payload)) {
								if(preg_match("/^교과목명(.*)입력$/", $payload)) {
									$query = updateProcessing('insert');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_INSERT');
									$conn->query($query);					
								}
								else if(preg_match("/^일반선택$/", $payload)) {
									$query = updateProcessing('generalSelc');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_GeneralSelc');
									$conn->query($query);	
								}
								$send['text'] = "🎩: 교과목명을 입력해주세요.";
								message($send);
								
								ReturningQR();
							}
							else if(preg_match("/^전공$/", $payload) || preg_match("/^전공기초$/", $payload) || preg_match("/^교양필수$/", $payload)) {
								if(preg_match("/^전공$/", $payload)) {
									$query = updateProcessing('major');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_MAJOR');
									$conn->query($query);				
								}
								else if(preg_match("/^전공기초$/", $payload)) {
									$query = updateProcessing('majorBasic');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_MajorBASIC');
									$conn->query($query);
								}
								else if(preg_match("/^교양필수$/", $payload)) {
									$query = updateProcessing('liberalEssn');
									$conn->query($query);
									$query = queryInsert('logging', 'REGISTER_LiberalESSN');
									$conn->query($query);				
								}
					
								if(!isset($previousSearchMajor)) {
									$send['text'] = "🎩: 학과명을 입력해주세요.";
									message($send);
								} else {
									$send['text'] = "🎩: 이전에 검색한 학과를 선택 또는 새로 검색할 학과명을 입력해주세요.";
									message($send);
									
									$send['elementsTitle'] = "세부 구분";
									$send['elementsButtonsTitle'] = $previousSearchMajor;
									messageTemplate($send);		
								}
								
								ReturningQR();
							}
							else if(preg_match("/^교양$/", $payload)) {
								$query = updateProcessing('liberal');
								$conn->query($query);
								$query = queryInsert('logging', 'REGISTER_LIBERAL');
								$conn->query($query);
								
								$send['text'] = "🎩: 세부 구분을 선택해주세요.";
								message($send);
								
								$send['elementsTitle'] = "세부 구분";
								$send['elementsButtonsTitle'] = $dbAllFields=getCourseColumnData($thisCourse, 'fields');
								messageTemplate($send);
							
								ReturningQR();
							} else {
								$send['text'] = "🎩: 잘못된 접근입니다.";
								message($send);
								
								$query = queryInsert('logging', 'REGISTER');
								$conn->query($query);
							
								if(!isset($userInfo)) {
									$send['elementsTitle'] = "과목 구분";
									$send['elementsButtonsTitle'] =  $dbAllDivs = getCourseColumnData($thisCourse, 'divs');
									array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
									messageTemplate($send);
								}
								else if(isset($userInfo)) {
									$rgstedInfo = registedConditionSubject($userInfo);
									isset($rgstedInfo) ? $rgstedInfo = implode("\n", $rgstedInfo) : "";
						
									$send['text'] = "<교과목 등록 현황>\n" . $rgstedInfo . "\n\n총 " . count($userInfo) . "과목";
									message($send);
												
									$send['elementsTitle'] = "과목 구분";
									$send['elementsButtonsTitle'] =  $dbAllDivs = getCourseColumnData($thisCourse, 'divs');
									array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
									messageTemplate($send);
								}			
								$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
								$send['payload'] = $send['title'] = array('초기화면');
								messageQR($send);				
							}			
						}
					}
					else if(preg_match("/[1]$/", $inProgress)) {
						if($payload) {
							$searchMajor = $payload;
						
							$query = "SELECT * FROM $thisCourse WHERE divs='$selectedDiv' AND major='$searchMajor'";
							$sql4courses = $conn->query($query);
							while($row4courses = $sql4courses->fetch_assoc()) {
								$dbGrade[] = $row4courses['grade'] . "학년";
								$dbGrade = array_keys(array_flip($dbGrade));
								$dbTitle[] = $row4courses['title'];
								$dbTitle = array_keys(array_flip($dbTitle));
							}
								
							if(count($dbTitle) > 30) {
								if(preg_match("/MAJOR/", $inProgress)) {
									$query = queryInsert('logging', 'REGISTER_MAJOR_2', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
								}
								else if(preg_match("/MajorBASIC/", $inProgress)) {
									$query = queryInsert('logging', 'REGISTER_MajorBASIC_2', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
								}	
								else if(preg_match("/LiberalESSN/", $inProgress)) {
									$query = queryInsert('logging', 'REGISTER_LiberalESSN_2', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
								}
								$conn->query($query);	
										
								$send['text'] = "🎩: 검색된 교과목이 너무 많습니다.\n찾으시는 교과목은 몇 학년 수업인가요?";
								message($send);
										
								$send['elementsTitle'] = "학년 구분";
								$send['elementsButtonsTitle'] = $dbGrade;
								messageTemplate($send);
								 
								ReturningQR();
							}
							else if(count($dbTitle) <= 30) {
								if(preg_match("/MAJOR/", $inProgress)) {
									$query = queryInsert('logging', 'REGISTER_MAJOR_3', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
								}
								else if(preg_match("/MajorBASIC/", $inProgress)) {
									$query = queryInsert('logging', 'REGISTER_MajorBASIC_3', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
								}	
								else if(preg_match("/LiberalESSN/", $inProgress)) {
									$query = queryInsert('logging', 'REGISTER_LiberalESSN_3', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
								}
								$conn->query($query);	
								
								$send["text"] = "🎩: 교과목을 선택해 주세요.";
								message($send);
						
								$send['elementsTitle'] = "교과목";
								$send['elementsButtonsTitle'] = $dbTitle;
								messageTemplate($send);
								 
								ReturningQR();
							}
							else if (!in_array($searchMajor, $dbAllMajor=getCourseColumnData($thisCourse, 'major'))) {
								$send['text'] = "🎩: 올바른 학과명이 아닌 것 같아요. 다시 똑.디. 선택해주세요.";
								message($send);
								
								$query = "SELECT DISTINCT major FROM $thisCourse WHERE divs='$selectedDiv' AND ((major='$searchWord') OR (major LIKE '%$searchWord') OR (major LIKE '$searchWord%') OR (major LIKE '%$searchWord%'))";
								$sql4coursesMajor = $conn->query($query);
								while($row4coursesMajor = $sql4coursesMajor->fetch_assoc()) {
									$dbResultMajor[] = $row4coursesMajor['major'];
								}
								
								$send['elementsTitle'] = "학과 구분";
								$send['elementsButtonsTitle'] = $dbResultMajor;
								messageTemplate($send);
								
								ReturningQR();
							}	
						} else {
							$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
							message($send);
							
							$query = "SELECT DISTINCT major FROM $thisCourse WHERE divs='$selectedDiv' AND ((major='$searchWord') OR (major LIKE '%$searchWord') OR (major LIKE '$searchWord%') OR (major LIKE '%$searchWord%'))";
							$sql4coursesMajor = $conn->query($query);
							while($row4coursesMajor = $sql4coursesMajor->fetch_assoc()) {
								$dbResultMajor[] = $row4coursesMajor['major'];
							}		
							
							$send['elementsTitle'] = "학과 구분";
							$send['elementsButtonsTitle'] = $dbResultMajor;
							messageTemplate($send);
							
							ReturningQR();							
						}
					}
					else if(preg_match("/[2]$/", $inProgress)) {
						if($payload) {
							$searchGrade = preg_replace("/[^0-9]*/s", "", $payload);
					
							$query = "SELECT title FROM $thisCourse WHERE divs='$selectedDiv' AND major='$searchMajor' AND grade='$searchGrade'";
							$sql4courses = $conn->query($query);
							$checkOut = checkOverlap($sql4courses);
							
							if($checkOut['condition'] == TRUE) {
								if($checkOut['count'] == "single") {
									if($checkOut['overlap'] == TRUE) {
										$send['text'] = "🎩: ".$checkOut['text'][0];
										message($send);
														
										$query = "SELECT DISTINCT grade FROM $thisCourse WHERE divs='$selectedDiv' AND major='$searchMajor'";
										$sql4courses = $conn->query($query);
										while($row4courses = $sql4courses->fetch_assoc()) {
											$dbGrade[] = $row4courses['grade'] . "학년";
										}
										
										$send['elementsTitle'] = "학년 구분";
										$send['elementsButtonsTitle'] = $dbGrade;
										messageTemplate($send);
										
										ReturningQR();
									}
									else if($checkOut['overlap'] == FALSE) {
										$send['text'] = "🎩: ".$checkOut['text'];
										$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
										messageQR($send);
										
										$checkOutInfo = $checkOut['dbInfo'];
										if(preg_match("/MAJOR/", $inProgress)) {
											$queryInProgress = "REGISTER_MAJOR_OPT_2nd";
										}
										else if(preg_match("/MajorBASIC/", $inProgress)) {
											$queryInProgress = "REGISTER_MajorBASIC_OPT_2nd";
										}	
										else if(preg_match("/LiberalESSN/", $inProgress)) {
											$queryInProgress = "REGISTER_LiberalESSN_OPT_2nd";			
										}
										$query = "INSERT INTO logging (year, semester, userkey, inProgress, searchWord, searchMajor, searchTitle, divs, fields, major, title, code, class, prof, department,
																							day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																								VALUE('$thisYear', '$thisSemester', '$senderID', '$queryInProgress', '$searchWord', '$searchMajor', '$searchGrade', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
																											'{$checkOutInfo['title']}', '{$checkOutInfo['code']}', '{$checkOutInfo['class']}', '{$checkOutInfo['prof']}', '{$checkOutInfo['department']}',
																											'{$checkOutInfo['day1']}', '{$checkOutInfo['day2']}', '{$checkOutInfo['day3']}', '{$checkOutInfo['day4']}', '{$checkOutInfo['day5']}', '{$checkOutInfo['day6']}',
																											'{$checkOutInfo['time1']}', '{$checkOutInfo['time2']}', '{$checkOutInfo['time3']}', '{$checkOutInfo['time4']}', '{$checkOutInfo['time5']}', '{$checkOutInfo['time6']}',
																											'{$checkOutInfo['min1']}', '{$checkOutInfo['min2']}', '{$checkOutInfo['min3']}', '{$checkOutInfo['min4']}', '{$checkOutInfo['min5']}', '{$checkOutInfo['min6']}',
																											'{$checkOutInfo['classroom1']}', '{$checkOutInfo['classroom2']}', '{$checkOutInfo['classroom3']}', '{$checkOutInfo['classroom4']}', '{$checkOutInfo['classroom5']}', '{$checkOutInfo['classroom6']}',
																											'$inputTime')";
										$conn->query($query);
									}
								}
								else if(preg_match("/multiple$/", $checkOut['count'])) {
									if(preg_match("/MAJOR/", $inProgress)) {
										$query = queryInsert('logging', 'REGISTER_MAJOR_3', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor, 'searchGrade'=>$searchGrade));
									}
									else if(preg_match("/MajorBASIC/", $inProgress)) {
										$query = queryInsert('logging', 'REGISTER_MajorBASIC_3', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor, 'searchGrade'=>$searchGrade));
									}	
									else if(preg_match("/LiberalESSN/", $inProgress)) {
										$query = queryInsert('logging', 'REGISTER_LiberalESSN_3', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor, 'searchGrade'=>$searchGrade));
									}
									$conn->query($query);	
									
									$send['elementsTitle'] = "교과목";
									$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
									messageTemplate($send);
									
									ReturningQR();
								}
							}
							else if($checkOut['condition'] == FALSE) {
								if(!isset($checkOut['dbInfo'])) {
									$send['text'] = "🎩: 검색된 교과목이 없습니다.\n찾으시는 교과목의 학년 구분을 다시 선택해주세요.";
									message($send);
									
									$query = "SELECT DISTINCT grade FROM $thisCourse WHERE divs='$selectedDiv' AND major='$searchMajor'";
									$sql4courses = $conn->query($query);
									while($row4courses = $sql4courses->fetch_assoc()) {
										$dbGrade[] = $row4courses['grade'] . "학년";
									}
									
									$send['elementsTitle'] = "학년 구분";
									$send['elementsButtonsTitle'] = $dbGrade;
									messageTemplate($send);
									
									ReturningQR();
								} 
								else if(!preg_match("/학년$/", $payload) || !is_numeric($searchGrade)) {
									$send['text'] = "🎩: 올바른 학년 구분이 아닌 것 같아요. 다시 똑.디. 선택해주세요.";
									message($send);
									
									$query = "SELECT DISTINCT grade FROM $thisCourse WHERE divs='$selectedDiv' AND major='$searchMajor'";
									$sql4courses = $conn->query($query);
									while($row4courses = $sql4courses->fetch_assoc()) {
										$dbGrade[] = $row4courses['grade'] . "학년";
									}
									
									$send['elementsTitle'] = "학년 구분";
									$send['elementsButtonsTitle'] = $dbGrade;
									messageTemplate($send);
									
									ReturningQR();
								}
							}
						} else {
							$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
							message($send);
							
							$query = "SELECT DISTINCT grade FROM $thisCourse WHERE divs='$selectedDiv' AND major='$searchMajor'";
							$sql4courses = $conn->query($query);
							while($row4courses = $sql4courses->fetch_assoc()) {
								$dbGrade[] = $row4courses['grade'] . "학년";
							}
							
							$send['elementsTitle'] = "학년 구분";
							$send['elementsButtonsTitle'] = $dbGrade;
							messageTemplate($send);
							
							ReturningQR();							
						}
					}
					else if(preg_match("/[3]$/", $inProgress)) {
						if($payload) {
							$searchTitle = $payload;
							
							if(!empty($searchGrade)) {
								$query = "SELECT * FROM $thisCourse WHERE (divs='$selectedDiv' AND major='$searchMajor' AND title='$searchTitle' AND grade='$searchGrade')";	
							}
							else if(empty($searchGrade)) {
								$query = "SELECT * FROM $thisCourse WHERE (divs='$selectedDiv' AND major='$searchMajor' AND title='$searchTitle')";
							}
							$sql4courses = $conn->query($query);
							$checkOut = checkOverlap($sql4courses);
							
							if($checkOut['condition'] == TRUE) {
								if($checkOut['count'] == "single") {
									if($checkOut['overlap'] == TRUE) {
										$send['text'] = "🎩: ".$checkOut['text'][0];
										message($send);
										
										if(empty($searchGrade)) {
											$query = "SELECT DISTINCT title FROM $thisCourse WHERE divs='$selectedDiv' AND major='$searchMajor'";
										}
										else if(!empty($searchGrade)) {
											$query = "SELECT DISTINCT title FROM $thisCourse WHERE divs='$selectedDiv' AND major='$searchMajor' AND grade='$searchGrade'";
										}
										$sql4courses = $conn->query($query);
										while($row4courses = $sql4courses->fetch_assoc()) {
											$dbTitle[] = $row4courses['title'];
										}
										
										$send['elementsTitle'] = "교과목";
										$send['elementsButtonsTitle'] = $dbTitle;
										messageTemplate($send);
										
										ReturningQR();
									}
									else if($checkOut['overlap'] == FALSE) {
										$send['text'] = "🎩: ".$checkOut['text'];
										$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
										messageQR($send);
										
										$checkOutInfo = $checkOut['dbInfo'];
										if(preg_match("/MAJOR/", $inProgress)) {
											$queryInProgress = "REGISTER_MAJOR_OPT_2nd";
										}
										else if(preg_match("/MajorBASIC/", $inProgress)) {
											$queryInProgress = "REGISTER_MajorBASIC_OPT_2nd";
										}	
										else if(preg_match("/LiberalESSN/", $inProgress)) {
											$queryInProgress = "REGISTER_LiberalESSN_OPT_2nd";			
										}
										$query = "INSERT INTO logging (year, semester, userkey, inProgress, searchWord, searchMajor, searchGrade, searchTitle, divs, fields, major, title, code, class, prof, department,
																							day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																								VALUE('$thisYear', '$thisSemester', '$senderID', '$queryInProgress', '$searchWord', '$searchMajor', '$searchGrade', '$searchTitle', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
																											'{$checkOutInfo['title']}', '{$checkOutInfo['code']}', '{$checkOutInfo['class']}', '{$checkOutInfo['prof']}', '{$checkOutInfo['department']}',
																											'{$checkOutInfo['day1']}', '{$checkOutInfo['day2']}', '{$checkOutInfo['day3']}', '{$checkOutInfo['day4']}', '{$checkOutInfo['day5']}', '{$checkOutInfo['day6']}',
																											'{$checkOutInfo['time1']}', '{$checkOutInfo['time2']}', '{$checkOutInfo['time3']}', '{$checkOutInfo['time4']}', '{$checkOutInfo['time5']}', '{$checkOutInfo['time6']}',
																											'{$checkOutInfo['min1']}', '{$checkOutInfo['min2']}', '{$checkOutInfo['min3']}', '{$checkOutInfo['min4']}', '{$checkOutInfo['min5']}', '{$checkOutInfo['min6']}',
																											'{$checkOutInfo['classroom1']}', '{$checkOutInfo['classroom2']}', '{$checkOutInfo['classroom3']}', '{$checkOutInfo['classroom4']}', '{$checkOutInfo['classroom5']}', '{$checkOutInfo['classroom6']}',
																											'$inputTime')";
										$conn->query($query);
									}
								}
								else if($checkOut['count'] == "multiple") {
									if(preg_match("/MAJOR/", $inProgress)) {
										$query = queryInsert('logging', 'REGISTER_MAJOR_4', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor, 'searchGrade'=>$searchGrade, 'searchTitle'=>$searchTitle));
									}
									else if(preg_match("/MajorBASIC/", $inProgress)) {
										$query = queryInsert('logging', 'REGISTER_MajorBASIC_4', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor, 'searchGrade'=>$searchGrade, 'searchTitle'=>$searchTitle));
									}	
									else if(preg_match("/LiberalESSN/", $inProgress)) {
										$query = queryInsert('logging', 'REGISTER_LiberalESSN_4', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor, 'searchGrade'=>$searchGrade, 'searchTitle'=>$searchTitle));
									}
									$conn->query($query);	
									
									$send['elementsTitle'] = "교과목";
									$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
									messageTemplate($send);
									
									ReturningQR();
								}
							}
							else if($checkOut['condition'] == FALSE) {
								if(!in_array($searchMajor, $dbAllMajor=getCourseColumnData($thisCourse, 'major'))) {
									$send['text'] = "🎩: 올바른 학과명이 아닌 것 같아요.";
									message($send);
									
									if(empty($searchGrade)) {
										$query = "SELECT DISTINCT title FROM $thisCourse WHERE divs='$selectedDiv' AND major='$searchMajor'";
									}
									else if(!empty($searchGrade)) {
										$query = "SELECT DISTINCT title FROM $thisCourse WHERE divs='$selectedDiv' AND major='$searchMajor' AND grade='$searchGrade'";
									}
									$sql4courses = $conn->query($query);
									while($row4courses = $sql4courses->fetch_assoc()) {
										$dbTitle[] = $row4courses['title'];
									}
									
									$send['elementsTitle'] = "교과목";
									$send['elementsButtonsTitle'] = $dbTitle;
									messageTemplate($send);
									
									ReturningQR();
								}
								else if(preg_match("/(.*)학년$/", $payload) || !is_numeric($searchGrade)) {
									$send['text'] = "🎩: 올바른 학년 구분이 아닌 것 같아요. 다시 똑.디. 선택해주세요.";
									message($send);
								
									if(empty($searchGrade)) {
										$query = "SELECT DISTINCT title FROM $thisCourse WHERE divs='$selectedDiv' AND major='$searchMajor'";
									}
									else if(!empty($searchGrade)) {
										$query = "SELECT DISTINCT title FROM $thisCourse WHERE divs='$selectedDiv' AND major='$searchMajor' AND grade='$searchGrade'";
									}
									$sql4courses = $conn->query($query);
									while($row4courses = $sql4courses->fetch_assoc()) {
										$dbTitle[] = $row4courses['title'];
									}
									
									$send['elementsTitle'] = "교과목";
									$send['elementsButtonsTitle'] = $dbTitle;
									messageTemplate($send);
									
									ReturningQR();
								}
							}
						} else {
							$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
							message($send);
							
							if(empty($searchGrade)) {
								$query = "SELECT DISTINCT title FROM $thisCourse WHERE divs='$selectedDiv' AND major='$searchMajor'";
							}
							else if(!empty($searchGrade)) {
								$query = "SELECT DISTINCT title FROM $thisCourse WHERE divs='$selectedDiv' AND major='$searchMajor' AND grade='$searchGrade'";
							}
							$sql4courses = $conn->query($query);
							while($row4courses = $sql4courses->fetch_assoc()) {
								$dbTitle[] = $row4courses['title'];
							}
							
							$send['elementsTitle'] = "교과목";
							$send['elementsButtonsTitle'] = $dbTitle;
							messageTemplate($send);
							
							ReturningQR();						
						}
					}
					else if(preg_match("/[4]$/", $inProgress)) {
						if($payload) {
							if(strpos($payload, "(") !== FALSE) {
								$payloadExp = explode("(", (str_replace(")", "", $payload)));
								// 단일 분류
								if(substr_count($payload, "(") >= 2) {
									$payloadTitle = $payloadExp[0] . "(" . $payloadExp[1] . ")";
									$payloadInfo = $payloadExp[2];	
								}
								else if(substr_count($payload, "(") == 1) {
									$payloadTitle = $payloadExp[0];
									$payloadInfo = $payloadExp[1];
								}
								
								// 복수 분류
								//// 분반 분류
								if(strlen($payloadInfo) == 3 && preg_match("/[0-9]/", $payloadInfo)) {
									$payloadInfoClass = $payloadInfo;
								}
								//// 교수명 분류
								else if(strpos($payloadInfo, "교수님") !== FALSE) {
									$payloadInfoProf = str_replace("교수님", "", $payloadInfo);
								} else {
								//// 학부 분류
									if(mb_strlen($payloadInfo, UTF8) > 1 && preg_match("/[\xA1-\xFE\xA1-\xFE]/",$payloadInfo)) {
										$payloadInfoDepartment = $payloadInfo;
									} else {
										////// 괄호 안 문자가 분류대상이 아닐 경우
										$payloadTitle = $payload;
										unset($payloadInfo);
									}
								}
								if($payloadInfo) {
									$query = "SELECT * FROM $thisCourse WHERE divs='$selectedDiv' AND 
							 	 													(
								 	 													(title='$payloadTitle' AND major='$searchMajor') OR 
								 	 													(title='$payloadTitle' AND major='$searchMajor' AND grade='$searchGrade')
							 	 													)
							 	 													AND
																					(
																						class='$payloadInfoClass' OR department='$payloadInfoDepartment' OR (prof!='' AND prof='$payloadInfoProf')
																					)";
								} else{
									$query = "SELECT * FROM $thisCourse WHERE divs='$selectedDiv' AND title='$payloadTitle'";
								}
							} else{
								$query = "SELECT * FROM $thisCourse WHERE divs='$selectedDiv' AND title='$payload'";
							}
							$sql4courses = $conn->query($query);
							$checkOut = checkOverlapReturn($sql4courses);
							if($checkOut['condition'] == TRUE) {
								if($checkOut['overlap'] == TRUE) {
									$send['text'] = "🎩: ".$checkOut['text'][0];
									message($send);
									
									$send['elementsTitle'] = "교과목";
									$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
									messageTemplate($send);
						
									ReturningQR();
								}
								else if($checkOut['overlap'] == FALSE) {
									$send['text'] = "🎩: ".$checkOut['text'];
									$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
									messageQR($send);
									
									$checkOutInfo = $checkOut['dbInfo'];
									
									if(preg_match("/MAJOR/", $inProgress)) {
										$queryInProgress = "REGISTER_MAJOR_OPT_2nd";
									}
									else if(preg_match("/MajorBASIC/", $inProgress)) {
										$queryInProgress = "REGISTER_MajorBASIC_OPT_2nd";
									}	
									else if(preg_match("/LiberalESSN/", $inProgress)) {
										$queryInProgress = "REGISTER_LiberalESSN_OPT_2nd";			
									}
									$query = "INSERT INTO logging (year, semester, userkey, inProgress, searchWord, searchMajor, searchGrade, searchTitle, divs, fields, major, title, code, class, prof, department,
																						day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																							VALUE('$thisYear', '$thisSemester', '$senderID', '$queryInProgress', '$searchWord', '$searchMajor', '$searchGrade', '$searchTitle', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
																										'{$checkOutInfo['title']}', '{$checkOutInfo['code']}', '{$checkOutInfo['class']}', '{$checkOutInfo['prof']}', '{$checkOutInfo['department']}',
																										'{$checkOutInfo['day1']}', '{$checkOutInfo['day2']}', '{$checkOutInfo['day3']}', '{$checkOutInfo['day4']}', '{$checkOutInfo['day5']}', '{$checkOutInfo['day6']}',
																										'{$checkOutInfo['time1']}', '{$checkOutInfo['time2']}', '{$checkOutInfo['time3']}', '{$checkOutInfo['time4']}', '{$checkOutInfo['time5']}', '{$checkOutInfo['time6']}',
																										'{$checkOutInfo['min1']}', '{$checkOutInfo['min2']}', '{$checkOutInfo['min3']}', '{$checkOutInfo['min4']}', '{$checkOutInfo['min5']}', '{$checkOutInfo['min6']}',
																										'{$checkOutInfo['classroom1']}', '{$checkOutInfo['classroom2']}', '{$checkOutInfo['classroom3']}', '{$checkOutInfo['classroom4']}', '{$checkOutInfo['classroom5']}', '{$checkOutInfo['classroom6']}',
																										'$inputTime')";
									$conn->query($query);
								}
							}
							else if($checkOut['condition'] == FALSE) {
								$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
								message($send);				
												
								if(!empty($searchGrade)) {
									$query = "SELECT * FROM $thisCourse WHERE (divs='$selectedDiv' AND major='$searchMajor' AND title='$searchTitle' AND grade='$searchGrade')";	
								}
								else if(empty($searchGrade)) {
									$query = "SELECT * FROM $thisCourse WHERE (divs='$selectedDiv' AND major='$searchMajor' AND title='$searchTitle')";
								}
								$sql4courses = $conn->query($query);
								$checkOut = checkOverlap($sql4courses);
								
								$send['elementsTitle'] = "교과목";
								$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
								messageTemplate($send);
								
								ReturningQR();
							}
						} else {
							$send['text'] = "🎩: 잘못된 접근입니다. 다시 똑.디. 선택해주세요.";
							message($send);				
											
							if(!empty($searchGrade)) {
								$query = "SELECT * FROM $thisCourse WHERE (divs='$selectedDiv' AND major='$searchMajor' AND title='$searchTitle' AND grade='$searchGrade')";	
							}
							else if(empty($searchGrade)) {
								$query = "SELECT * FROM $thisCourse WHERE (divs='$selectedDiv' AND major='$searchMajor' AND title='$searchTitle')";
							}
							$sql4courses = $conn->query($query);
							$checkOut = checkOverlap($sql4courses);
							
							$send['elementsTitle'] = "교과목";
							$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
							messageTemplate($send);
							
							ReturningQR();							
						}
					}
					else if(preg_match("/OPT/", $inProgress)) {
						if(preg_match("/1st$/", $inProgress)) {
							if($payloadQR) {
								if($payloadQR == "⭕") {
									$query = "SELECT * FROM $thisCourse WHERE divs='$selectedDiv' AND major='$searchMajor'";
									$sql4courses = $conn->query($query);
									while($row4courses = $sql4courses->fetch_assoc()) {
										$dbGrade[] = $row4courses['grade'] . "학년";
										$dbGrade = array_keys(array_flip($dbGrade));
										$dbTitle[] = $row4courses['title'];
										$dbTitle = array_keys(array_flip($dbTitle));
									}
										
									if(count($dbTitle) > 30) {
										if(preg_match("/MAJOR/", $inProgress)) {
											$query = queryInsert('logging', 'REGISTER_MAJOR_2', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
										}
										else if(preg_match("/MajorBASIC/", $inProgress)) {
											$query = queryInsert('logging', 'REGISTER_MajorBASIC_2', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
										}	
										else if(preg_match("/LiberalESSN/", $inProgress)) {
											$query = queryInsert('logging', 'REGISTER_LiberalESSN_2', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
										}
										$conn->query($query);
										
										$send['text'] = "🎩: 검색된 교과목이 너무 많습니다.\n찾으시는 교과목은 몇 학년 수업인가요?";
										message($send);
												
										$send['elementsTitle'] = "학년 구분";
										$send['elementsButtonsTitle'] = $dbGrade;
										messageTemplate($send);
										 
										ReturningQR();
									}
									else if(count($dbTitle) > 1 && count($dbTitle) <= 30) {
										if(preg_match("/MAJO/", $inProgress)) {
											$query = queryInsert('logging', 'REGISTER_MAJOR_3', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
										}
										else if(preg_match("/MajorBASIC/", $inProgress)) {
											$query = queryInsert('logging', 'REGISTER_MajorBASIC_3', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
										}	
										else if(preg_match("/LiberalESSN/", $inProgress)) {
											$query = queryInsert('logging', 'REGISTER_LiberalESSN_3', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
										}
										$conn->query($query);		
										
										$send["text"] = "🎩: 교과목을 선택해 주세요.";
										message($send);
								
										$send['elementsTitle'] = "교과목";
										$send['elementsButtonsTitle'] = $dbTitle;
										messageTemplate($send);
							
										ReturningQR();
									}
									else if(count($dbTitle) == 1)  {
										
										// 학과명 선택 후 과목이 1개밖에 없을 때
										
									}
								}
								else if($payloadQR == "❌") {
									if(preg_match("/MAJOR/", $inProgress)) {
										$query = queryInsert('logging', 'REGISTER_MAJOR');
									}
									else if(preg_match("/MajorBASIC/", $inProgress)) {
										$query = queryInsert('logging', 'REGISTER_MajorBASIC');
									}
									else if(preg_match("/LiberalESSN/", $inProgress)) {
										$query = queryInsert('logging', 'REGISTER_LiberalESSN');
									}	
									
									if(!isset($previousSearchMajor)) {
										$send['text'] = "🎩: 학과명을 다시입력해주세요.";
										message($send);
									} else {
										$send['text'] = "🎩: 이전에 검색한 학과를 재선택 또는 새로 검색할 학과명을 다시 입력해주세요.";
										message($send);
										
										$send['elementsTitle'] = "세부 구분";
										$send['elementsButtonsTitle'] = $previousSearchMajor;
										messageTemplate($send);		
									}
									
									ReturningQR();
								}
							} else{
								$send['text'] = "🎩: 잘못된 접근입니다. 검색할 학과가 <{$searchMajor}>가 확실합니까?";
								$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
								messageQR($send);									
							}
						}
						else if(preg_match("/2nd$/", $inProgress)) {
							if($payloadQR) {
								if($payloadQR == "⭕") {
									$optTitle = optTitle();
									
									$query = queryInsert('logging', 'START');
									$conn->query($query);
			
									$send['text'] = "🎩: ".$optTitle;
									$send['payload'] = $send['title'] = array("교과목 추가 등록", "시간표 보기", "초기화면");
									messageQR($send);
								}
								else if($payloadQR == "❌") {
									$query = "SELECT * FROM $thisCourse WHERE divs='$selectedDiv' AND major='$searchMajor'";
									$sql4courses = $conn->query($query);
									while($row4courses = $sql4courses->fetch_assoc()) {
										$dbGrade[] = $row4courses['grade'] . "학년";
										$dbGrade = array_keys(array_flip($dbGrade));
										$dbTitle[] = $row4courses['title'];
										$dbTitle = array_keys(array_flip($dbTitle));
									}
										
									if(count($dbTitle) > 30) {
										if(preg_match("/MAJOR/", $inProgress)) {
											$query = queryInsert('logging', 'REGISTER_MAJOR_2', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
										}
										else if(preg_match("/MajorBASIC/", $inProgress)) {
											$query = queryInsert('logging', 'REGISTER_MajorBASIC_2', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
										}	
										else if(preg_match("/LiberalESSN/", $inProgress)) {
											$query = queryInsert('logging', 'REGISTER_LiberalESSN_2', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
										}
										$conn->query($query);	
												
										$send['text'] = "🎩: 검색된 교과목이 너무 많습니다.\n찾으시는 교과목은 몇 학년 수업인가요?";
										message($send);
												
										$send['elementsTitle'] = "학년 구분";
										$send['elementsButtonsTitle'] = $dbGrade;
										messageTemplate($send);
										 
										ReturningQR();
									}
									else if(count($dbTitle) <= 30) {
										if(preg_match("/MAJOR/", $inProgress)) {
											$query = queryInsert('logging', 'REGISTER_MAJOR_3', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
										}
										else if(preg_match("/MajorBASIC/", $inProgress)) {
											$query = queryInsert('logging', 'REGISTER_MajorBASIC_3', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
										}	
										else if(preg_match("/LiberalESSN/", $inProgress)) {
											$query = queryInsert('logging', 'REGISTER_LiberalESSN_3', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
										}
										$conn->query($query);	
										
										$send["text"] = "🎩: 교과목을 선택해 주세요.";
										message($send);
								
										$send['elementsTitle'] = "교과목";
										$send['elementsButtonsTitle'] = $dbTitle;
										messageTemplate($send);
										 
										ReturningQR();
									}
								}	
							} else {
								$send['text'] = "🎩: 잘못된 접근입니다. 위의 <{$searchTitle}>을 확실히 등록하겠습니까?";
								$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
								messageQR($send);
							}		
						}
					}
				}
			}
		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////// READ ///////////////////////////////////////////////////////////////////////////////
		/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
			else if(preg_match("/^READ/", $inProgress)) {
				// values for searching	
				$query = "SELECT * FROM loggingRead WHERE userkey='$senderID' AND year='$thisYear' AND semester='$thisSemester' ORDER BY inputTime DESC LIMIT 1";
				$sql4loggingRead = $conn->query($query);
				while($row4loggingRead = $sql4loggingRead->fetch_assoc()) {
					$readType = $row4loggingRead['type'];
					$readTitle = $row4loggingRead['title'];
					$readClass = $row4loggingRead['class'];
					$readProf = $row4loggingRead['prof'];
					$readContent = $row4loggingRead['content'];
					$readDate1 = $row4loggingRead['date1'];
					$readDate2 = $row4loggingRead['date2'];
					$readTime1 = $row4loggingRead['time1'];
					$readTime2 = $row4loggingRead['time2'];
				}
				//
				// 과제, 휴강, 시험 정보 화면
				//
				if(preg_match("/TUTORIAL/", $inProgressRead)) {
					if($payloadQR == "튜토리얼 생략하기") {
						$query = queryInsert('loggingRead', 'READ_TUTORIAL_SKIP');
						$conn->query($query);
						
						$send['text'] = "🎩: 정말로 [내가 등록한 정보 보기] 튜토리얼을 생략하시겠습니까?\n\n(경고! ⭕를 선택하면 [내가 등록한 정보 보기]에 관한 튜토리얼이 생략되고, 다시는 튜토리얼을 진행할 수 없습니다.)";
						$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
						messageQR($send);
					} else {
						if(preg_match("/TUTORIAL$/", $inProgressRead)) {
							if($payloadQR) {
								if($payloadQR == "⭕") {
									$query = queryInsert('loggingRead', 'READ_TUTORIAL_1');
									$conn->query($query);
									
									$send['text'] = "🎩: [내가 등록한 정보 보기] 튜토리얼을 시작합니다.";
									message($send);
									
									$rgstedInfoDetail = registedConditionSubjectDetail($userInfo);
									for($i=0; $i<count($rgstedInfoDetail['title']); $i++) {
										$title = $rgstedInfoDetail['titleName'][$i];
										$class = $rgstedInfoDetail['class'][$i];
										$prof = $rgstedInfoDetail['prof'][$i];
										$send['title'][] = $rgstedInfoDetail['title'][$i];
										$send['subtitle'][] = $rgstedInfoDetail['info'][$i];
										$send['payload'][] = array("assignment_{$title}_{$class}_{$prof}", "cancel_{$title}_{$class}_{$prof}", "exam_{$title}_{$class}_{$prof}");
										
										$eventInfoTypes[$i] = array();
										for($j=0; $j<count($eventInfo); $j++) {
											if($eventInfo[$j]['title'] == $title) {
												$eventInfoTypes[$i][$j] = $eventInfo[$j]['type'];
											}
										}
										$countTypes = array_count_values($eventInfoTypes[$i]);
										$send['buttonsTitle'][$i] = array();
										is_array($countTypes) && $countTypes['assignment'] > 0 ? array_push($send['buttonsTitle'][$i], "과제({$countTypes['assignment']}개)") : array_push($send['buttonsTitle'][$i], "과제");
										is_array($countTypes) && $countTypes['cancel'] > 0 ? array_push($send['buttonsTitle'][$i], "휴강({$countTypes['cancel']}개)") : array_push($send['buttonsTitle'][$i], "휴강");
										is_array($countTypes) && $countTypes['exam'] > 0 ? array_push($send['buttonsTitle'][$i], "시험({$countTypes['exam']}개)") : array_push($send['buttonsTitle'][$i], "시험");
									}
									messageTemplateLeftSlide($send);
									
									$send['text'] = "🎩: JeongEunhu님이 등록하신 교과목 정보가 보이나요?\n\n그렇다면 위의 교과목에서 [과제] 버튼을 눌러보세요!";
									message($send);
									
									$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
									$send['payload'] = $send['title'] = array('초기화면', '튜토리얼 생략하기');
									messageQR($send);
								}
								else if($payloadQR == "❌") {
									$send['text'] = "🎩: [내가 등록한 정보 보기] 튜토리얼이 생략되었습니다.";
									message($send);			
													
									$query = queryInsert('loggingRead', 'READ_TUTORIAL_FIN');
									$conn->query($query);
									$query = queryInsert('loggingRead', 'READ');
									$conn->query($query);
									
									$rgstedInfoDetail = registedConditionSubjectDetail($userInfo);
									for($i=0; $i<count($rgstedInfoDetail['title']); $i++) {
										$title = $rgstedInfoDetail['titleName'][$i];
										$class = $rgstedInfoDetail['class'][$i];
										$prof = $rgstedInfoDetail['prof'][$i];
										$send['title'][] = $rgstedInfoDetail['title'][$i];
										$send['subtitle'][] = $rgstedInfoDetail['info'][$i];
										$send['payload'][] = array("assignment_{$title}_{$class}_{$prof}", "cancel_{$title}_{$class}_{$prof}", "exam_{$title}_{$class}_{$prof}");
										
										$eventInfoTypes[$i] = array();
										for($j=0; $j<count($eventInfo); $j++) {
											if($eventInfo[$j]['title'] == $title) {
												$eventInfoTypes[$i][$j] = $eventInfo[$j]['type'];
											}
										}
										$countTypes = array_count_values($eventInfoTypes[$i]);
										$send['buttonsTitle'][$i] = array();
										is_array($countTypes) && $countTypes['assignment'] > 0 ? array_push($send['buttonsTitle'][$i], "과제({$countTypes['assignment']}개)") : array_push($send['buttonsTitle'][$i], "과제");
										is_array($countTypes) && $countTypes['cancel'] > 0 ? array_push($send['buttonsTitle'][$i], "휴강({$countTypes['cancel']}개)") : array_push($send['buttonsTitle'][$i], "휴강");
										is_array($countTypes) && $countTypes['exam'] > 0 ? array_push($send['buttonsTitle'][$i], "시험({$countTypes['exam']}개)") : array_push($send['buttonsTitle'][$i], "시험");
									}
									messageTemplateLeftSlide($send);
									
									$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
									$send['payload'] = $send['title'] = array('초기화면', '시간표 보기', '교과목 삭제하기');
									messageQR($send);
								}
							} else {
								$send['text'] = "🎩: 잘못된 접근입니다.\n\n[내가 등록한 정보 보기] 튜토리얼을 진행하시겠습니까?\n\n(경고! ❌를 선택하면 [내가 등록한 정보 보기]에 관한 튜토리얼이 생략되고, 다시는 튜토리얼을 진행할 수 없습니다.)";
								$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
								messageQR($send);
							}
						}
						else 	if(preg_match("/[1]$/", $inProgressRead)) {
							if($payload) {
								$payloadExplode = explode("_", $payload);
								$payloadType = $payloadExplode[0];
								$payloadTitle = $payloadExplode[1];
								$payloadClass = $payloadExplode[2];
								$payloadProf = $payloadExplode[3];					
								
								if($payloadType == "assignment") {
									$query = queryInsert('loggingRead', 'READ_TUTORIAL_2', array("type"=>$payloadType, "title"=>$payloadTitle, "class"=>$payloadClass, "prof"=>$payloadProf));
									$conn->query($query);
									
									$send['text'] = "🎩: 당연히 아직 {$payloadTitle}에 등록된 과제 정보가 없겠죠?\n아래에 [새로 등록하기] 버튼을 눌러주세요!";
									$send['payload'] = $send['title'] = array('새로 등록하기', '초기화면', '튜토리얼 생략하기');
									messageQR($send);
								}
							} else {
								$send['text'] = "🎩: 다른거 말고 [과제] 버튼을 눌러주시겠어요..?";
								message($send);
								
								$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
								$send['payload'] = $send['title'] = array('초기화면', '튜토리얼 생략하기');
								messageQR($send);							
							}	
						}
						else 	if(preg_match("/[2]$/", $inProgressRead)) {
							if($payloadQR) {
								$query = queryInsert('loggingRead', 'READ_TUTORIAL_3', array("type"=>$readType, "title"=>$readTitle, "class"=>$readClass, "prof"=>$readProf));
								$conn->query($query);
												
								$send['text'] = "🎩: 여기서는 {$readTitle}에 입력할 과제에 대한 내용과 기한을 입력해요.\n\n하지만 과제가 없을 수 있으니 이번만은 제가 몰래 입력해버릴거에요.🕵‍♀\n뭐라고 입력했는지 확인해보세요!";
								$send['payload'] = $send['title'] = array('확인하기', '초기화면', '튜토리얼 생략하기');
								messageQR($send);
							} else {
								$send['text'] = "🎩: 아래에 [새로 등록하기] 버튼을 눌러주세요!";
								$send['payload'] = $send['title'] = array('새로 등록하기', '초기화면', '튜토리얼 생략하기');
								messageQR($send);
							}
						}
						else 	if(preg_match("/[3]$/", $inProgressRead)) {
							if($payloadQR) {
								$query = queryInsert('loggingRead', 'READ_TUTORIAL_FIN', array("type"=>$readType, "title"=>$readTitle, "class"=>$readClass, "prof"=>$readProf));
								$conn->query($query);	
								
								$tutorialDate = '12월 31일';
								$tutorialContent = '행복하기♥';
								$send['title'] = array("<과제 - ".$readTitle."> - 기한: ".$tutorialDate);
								$send['subtitle'] = array("과제내용: ".$tutorialContent."\n입력시간: ".$inputTime);
								messageTemplateLeftSlide($send);
								
								$send['text'] = "🎩: 위와 같이 새로운 과제 정보가 등록되었어요.🎉\n\n따로 메모할 필요없이 이렇게 등록만 하면 등록된 기한까지 매일 아침에 제가 알려드릴게요.\n그리고 같은 과목을 듣는 다른 수강생들이 등록한 과제・휴강・시험 정보도 알 수 있답니다.👍\n\n2018년도 항상 행복하세요. 뿅❗\n(예시로 등록된 정보는 자동 삭제됩니다.)";
								$send['payload'] = $send['title'] = array('초기화면');
								messageQR($send);
							} else {
								$send['text'] = "🎩: 한번만 확인해주시면 안될까요..?💦💦";
								$send['payload'] = $send['title'] = array('이번만 확인해주기', '초기화면', '튜토리얼 생략하기');
								messageQR($send);
							}
						}
						else 	if(preg_match("SKIP$/", $inProgressRead)) {
							$send['text'] = "🎩: [내가 등록한 정보 보기] 튜토리얼이 생략되었습니다.";
							message($send);			
											
							$query = queryInsert('loggingRead', 'READ_TUTORIAL_FIN');
							$conn->query($query);
							$query = queryInsert('loggingRead', 'READ');
							$conn->query($query);
							
							$rgstedInfoDetail = registedConditionSubjectDetail($userInfo);
							for($i=0; $i<count($rgstedInfoDetail['title']); $i++) {
								$title = $rgstedInfoDetail['titleName'][$i];
								$class = $rgstedInfoDetail['class'][$i];
								$prof = $rgstedInfoDetail['prof'][$i];
								$send['title'][] = $rgstedInfoDetail['title'][$i];
								$send['subtitle'][] = $rgstedInfoDetail['info'][$i];
								$send['payload'][] = array("assignment_{$title}_{$class}_{$prof}", "cancel_{$title}_{$class}_{$prof}", "exam_{$title}_{$class}_{$prof}");
								
								$eventInfoTypes[$i] = array();
								for($j=0; $j<count($eventInfo); $j++) {
									if($eventInfo[$j]['title'] == $title) {
										$eventInfoTypes[$i][$j] = $eventInfo[$j]['type'];
									}
								}
								$countTypes = array_count_values($eventInfoTypes[$i]);
								$send['buttonsTitle'][$i] = array();
								is_array($countTypes) && $countTypes['assignment'] > 0 ? array_push($send['buttonsTitle'][$i], "과제({$countTypes['assignment']}개)") : array_push($send['buttonsTitle'][$i], "과제");
								is_array($countTypes) && $countTypes['cancel'] > 0 ? array_push($send['buttonsTitle'][$i], "휴강({$countTypes['cancel']}개)") : array_push($send['buttonsTitle'][$i], "휴강");
								is_array($countTypes) && $countTypes['exam'] > 0 ? array_push($send['buttonsTitle'][$i], "시험({$countTypes['exam']}개)") : array_push($send['buttonsTitle'][$i], "시험");
							}
							messageTemplateLeftSlide($send);
							
							$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
							$send['payload'] = $send['title'] = array('초기화면', '시간표 보기', '교과목 삭제하기');
							messageQR($send);							
						}						
					}
				}
				else if(preg_match("/^READ$/", $inProgressRead)) {
					if($payload || $payloadQR) {
						// check -> inProgress='READ_TUTORIAL_FIN'
						$query = "SELECT inProgress FROM loggingRead WHERE userkey='$senderID' AND inProgress='READ_TUTORIAL_FIN'";
						$readTutorialFin = $conn->query($query)->fetch_assoc();
						
						if(!$readTutorialFin) {
							$query = queryInsert('loggingRead', 'READ_TUTORIAL');
							$conn->query($query);
							
							$send['text'] = "🎩: 처음이신거같은데.. 제가 조금 도와드려도될까요?\n\n(경고! ❌를 선택하면 [내가 등록한 정보 보기]에 관한 튜토리얼이 생략되고, 다시는 튜토리얼을 진행할 수 없습니다.)";
							$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
							messageQR($send);
						} else {
							if($payload) {
								$payloadExplode = explode("_", $payload);
								$payloadType = $payloadExplode[0];
								$payloadTitle = $payloadExplode[1];
								$payloadClass = $payloadExplode[2];
								$payloadProf = $payloadExplode[3];
								
								if($payloadType == "assignment" || $payloadType == "cancel" || $payloadType == "exam") {
									$query = queryInsert('loggingRead', 'READ_EVENT', array("type"=>$payloadType, "title"=>$payloadTitle, "class"=>$payloadClass, "prof"=>$payloadProf));
									$conn->query($query);
									
									$query = "SELECT * FROM event WHERE userkey='{$senderID}' AND year='$thisYear' AND semester='$thisSemester' AND type='{$payloadType}' AND title='{$payloadTitle}' AND class='{$payloadClass}' AND prof='{$payloadProf}'";
									$sql4event = $conn->query($query);
									while($row4event = $sql4event->fetch_assoc()) {
										$eventDate1 = date("Y-m-d", mktime(0, 0, 0, (int)substr($row4event['date1'], 0, 2), (int)substr($row4event['date1'], 2, 4), date("Y")));
										$eventDate2 = date("Y-m-d", mktime(0, 0, 0, (int)substr($row4event['date2'], 0, 2), (int)substr($row4event['date2'], 2, 4), date("Y")));
										$nowDate = date("Y-m-d", strtotime($inputTime));
										if((empty($row4event['date2']) && $eventDate1 >= $nowDate) || (!empty($row4event['date2']) && $eventDate2 >= $nowDate)) {
											$events[] = $row4event;
										}
									}
									
									if(count($events) > 0) {
										$j=0;
										for($i=0; $i<count($events); $i++) {
											if($events[$i]['type'] == "assignment") {
												$typeKR = "과제";
												$send['title'][] = "<과제 - " . $events[$i]['title'] . "> - 기한: " . substr($events[$i]['date1'], 0, 2) . "월 " . substr($events[$i]['date1'], 2, 2) . "일";
												$send['subtitle'][] = "과제 내용: " . $events[$i]['content'] . "\n입력시간: " . $events[$i]['inputTime'];
											}
											else if($events[$i]['type'] == "cancel") {
												$typeKR = "휴강";
												$readDateMonth1 = substr($events[$i]['date1'], 0, 2);
												$readDateDay1 = substr($events[$i]['date1'], 2, 2);
												$readDateMonth2 = substr($events[$i]['date2'], 0, 2);
												$readDateDay2 = substr($events[$i]['date2'], 2, 2);
												
												if(empty($events[$i]['date2'])) {
													$send['title'][] = "<휴강 - " . $eventInfo[$i]['title'] . ">";
													$send['subtitle'][] = "휴강 날짜: " . $readDateMonth1 . "월 " . $readDateDay1 . "일\n입력시간: " . $events[$i]['inputTime'];
												}
												else if(!empty($events[$i]['date2'])) {
													$send['title'][] = "<휴강 - " . $eventInfo[$i]['title'] . ">";
													$send['subtitle'][] = "휴강 날짜: " . $readDateMonth1 . "월 " . $readDateDay1 . "일부터 " . $readDateMonth2 . "월 " . $readDateDay2 . "일 까지\n입력시간: " . $events[$i]['inputTime'];
												}
											}
											else if($events[$i]['type'] == "exam") {
												$typeKR = "시험";
												$readDateMonth = substr($events[$i]['date1'], 0, 2);
												$readDateDay = substr($events[$i]['date1'], 2, 2);
												$readDateHour = substr($events[$i]['time1'], 0, 2);
												$readDateMin = substr($events[$i]['time1'], 2, 2);
											
												$send['title'][] = "<시험 - " . $events[$i]['title'] . ">";
												$send['subtitle'][] = "시험 일정: " . $readDateMonth . "월 " . $readDateDay . "일 / ". $readDateHour . "시 " . $readDateMin . "분\n입력시간: " . $events[$i]['inputTime'];
											}
											$send['payload'][] = array("OTHERS_{$payloadType}_{$payloadTitle}_{$payloadClass}_{$payloadProf}", "DELETE_{$payloadType}_{$payloadTitle}_{$payloadClass}_{$payloadProf}_{$j}");
											$send['buttonsTitle'][] = array("다른 사람 {$typeKR} 정보 보기", "이 {$typeKR} 정보 삭제하기");
											$j++;
										}
										messageTemplateLeftSlide($send);
										
										$send['text'] = "🎩: 아래 버튼을 눌러 계속 진행해주세요.";
										$send['title'] = array('새로 등록하기', '이전으로', '초기화면');
										$send['payload'] = array("WRITE_{$payloadType}_{$payloadTitle}_{$payloadClass}_{$payloadProf}", '이전으로', '초기화면');
									} else {
										if($payloadType == "assignment") {
											$typeKR = "과제";
										}
										else if($payloadType == "cancel") {
											$typeKR = "휴강";
										}
										else if($payloadType == "exam") {
											$typeKR = "시험";
										}
										$send['text'] = "🎩: {$payloadTitle}에 등록된 {$typeKR} 정보가 없습니다.\n아래 버튼을 눌러 계속 진행해주세요.";
										$send['title'] = array('새로 등록하기', "다른 사람 {$typeKR} 정보 보기", '이전으로', '초기화면');
										$send['payload'] = array("WRITE_{$payloadType}_{$payloadTitle}_{$payloadClass}_{$payloadProf}", "OTHERS_{$payloadType}_{$payloadTitle}_{$payloadClass}_{$payloadProf}", '이전으로', '초기화면');
									}
									messageQR($send);
								}
							}
							//
							// 교과목 삭제
							//
							else if($payloadQR) {
								if($payloadQR == "교과목 삭제하기") {
									$query = queryInsert('loggingRead', 'READ_DELETE');
									$conn->query($query);
									
									$rgstedInfoDetail = registedConditionSubjectDetail($userInfo);
									for($i=0; $i<count($rgstedInfoDetail['title']); $i++) {
										$title = $rgstedInfoDetail['titleName'][$i];
										$class = $rgstedInfoDetail['class'][$i];
										$prof = $rgstedInfoDetail['prof'][$i];
										$send['title'][] = $rgstedInfoDetail['title'][$i];
										$send['subtitle'][] = $rgstedInfoDetail['info'][$i];
										$send['payload'][] = "DELETE_{$title}_{$class}_{$prof}";
									}
									$send['buttonsTitle'] = array("교과목 삭제하기");
									messageTemplateLeftSlide($send);
									ReturningQR();	
								}
							}							
						}
					} else {
						$send['text'] = "🎩: 잘못된 접근입니다.";
						message($send);
						
						$rgstedInfoDetail = registedConditionSubjectDetail($userInfo);
						for($i=0; $i<count($rgstedInfoDetail['title']); $i++) {
							$title = $rgstedInfoDetail['titleName'][$i];
							$class = $rgstedInfoDetail['class'][$i];
							$prof = $rgstedInfoDetail['prof'][$i];
							$send['title'][] = $rgstedInfoDetail['title'][$i];
							$send['subtitle'][] = $rgstedInfoDetail['info'][$i];
							$send['payload'][] = array("assignment_{$title}_{$class}_{$prof}", "cancel_{$title}_{$class}_{$prof}", "exam_{$title}_{$class}_{$prof}");
							
							$eventInfoTypes[$i] = array();
							for($j=0; $j<count($eventInfo); $j++) {
								if($eventInfo[$j]['title'] == $title) {
									$eventInfoTypes[$i][$j] = $eventInfo[$j]['type'];
								}
							}
							$countTypes = array_count_values($eventInfoTypes[$i]);
							$send['buttonsTitle'][$i] = array();
							is_array($countTypes) && $countTypes['assignment'] > 0 ? array_push($send['buttonsTitle'][$i], "과제({$countTypes['assignment']}개)") : array_push($send['buttonsTitle'][$i], "과제");
							is_array($countTypes) && $countTypes['cancel'] > 0 ? array_push($send['buttonsTitle'][$i], "휴강({$countTypes['cancel']}개)") : array_push($send['buttonsTitle'][$i], "휴강");
							is_array($countTypes) && $countTypes['exam'] > 0 ? array_push($send['buttonsTitle'][$i], "시험({$countTypes['exam']}개)") : array_push($send['buttonsTitle'][$i], "시험");
						}
						messageTemplateLeftSlide($send);
						
						$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
						$send['payload'] = $send['title'] = array('초기화면', '시간표 보기', '교과목 삭제하기');
						messageQR($send);
					}
				}
				else if(preg_match("/EVENT/", $inProgressRead)) {
					if(preg_match("/EVENT$/", $inProgressRead)) {
						if($payload || $payloadQR) {
							if($payload) {
								$payloadExplode = explode("_", $payload);
							}
							else if($payloadQR) {
								$payloadExplode = explode("_", $payloadQR);
							}
							$payloadSort = $payloadExplode[0];
							$payloadType = $payloadExplode[1];
							$payloadTitle = $payloadExplode[2];
							$payloadClass = $payloadExplode[3];
							$payloadProf = $payloadExplode[4];
							$payloadNum = $payloadExplode[5];
							//
							// 새로 등록하기
							//
							if($payloadSort == "WRITE") {
								if($payloadType == "assignment") {
									$send['text'] = "🎩: <" . $payloadTitle .">에 등록할 과제 내용를 상세히 입력해주세요.";
									$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_1', array("type"=>$payloadType, "title"=>$payloadTitle, "class"=>$payloadClass, "prof"=>$payloadProf));
								}
								else 	if($payloadType == "cancel") {
									$send['text'] = "🎩: <" . $payloadTitle .">에 등록할 휴강 날짜를 <숫자 4자리>로 입력해주세요.\n예) 10월 16일 -> 1016\n\n휴강이 단일이 아닌 복수일(기간)이라면,\n첫날과 마지막날을 슬래쉬(/)를 포함한 <숫자 8자리>로 입력해주세요.\n예) 10월16일부터 10월 23일 -> 1016/1023";			
									$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_2', array("type"=>$payloadType, "title"=>$payloadTitle, "class"=>$payloadClass, "prof"=>$payloadProf));
								}
								else 	if($payloadType == "exam") {
									$send['text'] = "🎩: <" . $payloadTitle .">에 등록할 시험 날짜와 시간을\n슬래쉬(/)를 포함한 <숫자 8자리>로 입력해주세요.\n예) 10월 16일 오후 1시반 -> 1016/1330";
									$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_2', array("type"=>$payloadType, "title"=>$payloadTitle, "class"=>$payloadClass, "prof"=>$payloadProf));
								}
								$conn->query($query);
								$send['payload'] = $send['title'] = array('이전으로', '초기화면');
								messageQR($send);
							}
							//
							// 다른 사람 정보 보기
							//
							else if($payloadSort == "OTHERS") {
								$query = "SELECT * FROM event WHERE userkey!='$senderID' AND year='$thisYear' AND semester='$thisSemester' AND type='$payloadType' AND title='$payloadTitle' AND class='$payloadClass' AND prof='$payloadProf'";
								$sql4eventOther = $conn->query($query);	
								while($row4eventOther = $sql4eventOther->fetch_assoc()) {
									$eventDate1 = date("Y-m-d", mktime(0, 0, 0, (int)substr($row4eventOther['date1'], 0, 2), (int)substr($row4eventOther['date1'], 2, 4), date("Y")));
									$eventDate2 = date("Y-m-d", mktime(0, 0, 0, (int)substr($row4eventOther['date2'], 0, 2), (int)substr($row4eventOther['date2'], 2, 4), date("Y")));
									$nowDate = date("Y-m-d", strtotime($inputTime));
									if((empty($row4eventOther['date2']) && $eventDate1 >= $nowDate) || (!empty($row4eventOther['date2']) && $eventDate2 >= $nowDate)) {
										$eventInfoOther[] = $row4eventOther;
									}
								}
								if($payloadType == "assignment") {
									$readTypeKR = "과제";
								}
								else if($payloadType == "cancel") {
									$readTypeKR = "휴강";
								}
								else if($payloadType == "exam") {
									$readTypeKR = "시험";
								}
								
								if(count($eventInfoOther) > 0) {
									// 전체가 10개 이하 => 그대로 제공
									if(count($eventInfoOther) < 11) {
										$readEventInfo = readEventInfo($eventInfoOther, $payloadType);
									}
									// 전체가 11개 이상 => 랜덤으로 추출 후 제공
									else if(count($eventInfoOther) >= 11) {
										$randomKeys = array_rand($eventInfoOther, 10);
										for($i=0; $i<count($randomKeys); $i++) {
											$eventInfoOtherRandom[] = $eventInfoOther[$randomKeys[$i]];
										}
										$readEventInfo = readEventInfo($eventInfoOtherRandom, $payloadType);
									}
									
									$send['title'] = $readEventInfo['title'];
									$send['subtitle'] = $readEventInfo['info'];
									$send['buttonsTitle'] = array("나의 " . $readTypeKR . " 목록으로 가져오기");
									$generalPayload = $readEventInfo['payload'];
									for($i=0; $i<count($generalPayload); $i++) {
										$generalPayloadExp = explode("_", $generalPayload[$i]);
										$send['payload'][] = "{$payloadType}_{$payloadTitle}_{$payloadClass}_{$payloadProf}_" . $generalPayloadExp[3];
									}
									messageTemplateLeftSlide($send);
									
									$query = queryInsert('loggingRead', 'READ_EVENT_OTHERS', array("type"=>$payloadType, "title"=>$payloadTitle, "class"=>$payloadClass, "prof"=>$payloadProf));
									$conn->query($query);
								} else {
									$send['text'] = "🎩: <".$payloadTitle.">에 다른 수강생들이 입력한 " . $readTypeKR . " 정보가 없습니다.";
									message($send);
								}
								ReturningQR();
							}
							//
							// 등록된 이벤트 삭제
							//
							else if($payloadSort == "DELETE") {
								$query = "SELECT * FROM event WHERE userkey='{$senderID}' AND year='$thisYear' AND semester='$thisSemester' AND type='{$payloadType}' AND title='{$payloadTitle}' AND class='{$payloadClass}' AND prof='{$payloadProf}'";
								$sql4event = $conn->query($query);
								while($row4event = $sql4event->fetch_assoc()) {
									$eventDate1 = date("Y-m-d", mktime(0, 0, 0, (int)substr($row4event['date1'], 0, 2), (int)substr($row4event['date1'], 2, 4), date("Y")));
									$eventDate2 = date("Y-m-d", mktime(0, 0, 0, (int)substr($row4event['date2'], 0, 2), (int)substr($row4event['date2'], 2, 4), date("Y")));
									$nowDate = date("Y-m-d", strtotime($inputTime));
									if((empty($row4event['date2']) && $eventDate1 >= $nowDate) || (!empty($row4event['date2']) && $eventDate2 >= $nowDate)) {
										$events[] = $row4event;
									}
								}
								$eventDeleteInfo = $events[$payloadNum];
								if(preg_match("/DELETE$/", $inProgressRead)) {
									if($payloadType == "assignment") {
										$send['text'] = "🎩: <" . $eventDeleteInfo['title'] . ">\n과제내용: " . $eventDeleteInfo['content'] . "\n기한: " . substr($eventDeleteInfo['date1'], 0, 2) . "월 " . substr($eventDeleteInfo['date1'], 2, 2) . "일\n\n위 과제 내용을 삭제하는 것이 맞나요?";
										$query = queryInsert('loggingRead', 'READ_EVENT_DELETE_FIN', array('type'=>$eventDeleteInfo['type'], 'title'=>$eventDeleteInfo['title'], 'class'=>$eventDeleteInfo['class'], 'prof'=>$eventDeleteInfo['prof'], 'content'=>$eventDeleteInfo['content'], 'inputTime'=>$inputTime));
									}
									else if($payloadType == "cancel") {
										if(empty($eventDeleteInfo['date2'])) {
											$send['text'] = "🎩: <" . $eventDeleteInfo['title'] . ">\n날짜: " . substr($eventDeleteInfo['date1'], 0, 2) . "월 " . substr($eventDeleteInfo['date1'], 2, 2) . "일\n\n위 휴강 내용을 삭제하는 것이 맞나요?";
											$query = queryInsert('loggingRead', 'READ_EVENT_DELETE_FIN', array('type'=>$eventDeleteInfo['type'], 'title'=>$eventDeleteInfo['title'], 'class'=>$eventDeleteInfo['class'], 'prof'=>$eventDeleteInfo['prof'], 'date1'=>$eventDeleteInfo['date1'], 'inputTime'=>$inputTime));
										}
										else if(!empty($eventDeleteInfo['date2'])) {
											$send['text'] = "🎩: <" . $eventDeleteInfo['title'] . ">\n날짜: " . substr($eventDeleteInfo['date1'], 0, 2) . "월 " . substr($eventDeleteInfo['date1'], 2, 2) . "일부터 " . substr($eventDeleteInfo['date2'], 0, 2) . "월 " . substr($eventDeleteInfo['date2'], 2, 2) . "일 까지\n\n위 휴강 내용을 삭제하는 것이 맞나요?";
											$query = queryInsert('loggingRead', 'READ_EVENT_DELETE_FIN', array('type'=>$eventDeleteInfo['type'], 'title'=>$eventDeleteInfo['title'], 'class'=>$eventDeleteInfo['class'], 'prof'=>$eventDeleteInfo['prof'], 'date1'=>$eventDeleteInfo['date1'], 'date2'=>$eventDeleteInfo['date2'], 'inputTime'=>$inputTime));			
										}
									}
									else if($payloadType == "exam") {
										$send['text'] = "🎩: <" . $eventDeleteInfo['title'] . ">\n날짜: " . substr($eventDeleteInfo['date1'], 0, 2) . "월 " . substr($eventDeleteInfo['date1'], 2, 2) . "일 / ". substr($eventDeleteInfo['time1'], 0, 2) . "시 " . substr($eventDeleteInfo['time1'], 2, 2) . "분\n\n위 시험 내용을 삭제하는 것이 맞나요?";
										$query = queryInsert('loggingRead', 'READ_EVENT_DELETE_FIN', array('type'=>$eventDeleteInfo['type'], 'title'=>$eventDeleteInfo['title'], 'class'=>$eventDeleteInfo['class'], 'prof'=>$eventDeleteInfo['prof'], 'date1'=>$eventDeleteInfo['date1'], 'time1'=>$eventDeleteInfo['time1'], 'inputTime'=>$inputTime));			
									}
									$conn->query($query);
									
									$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
									messageQR($send);						
								}
								else if(preg_match("/FIN$/", $inProgressRead)) {
									if($payloadQR == "⭕") {
										$query = "SELECT * FROM loggingRead WHERE inProgress='READ_EVENT_DELETE_FIN' AND userkey='$senderID' AND year='$thisYear' AND semester='$thisSemester' ORDER BY inputTime DESC LIMIT 1";
										$sql4loggingRead = $conn->query($query)->fetch_assoc();
										
										if($sql4loggingRead['type'] == "assignment") {
											$send['text'] = "🎩: <" . $sql4loggingRead['title'] . ">\n과제내용: " . $sql4loggingRead['content'] . "\n기한: " . substr($sql4loggingRead['date1'], 0, 2) . "월 " . substr($sql4loggingRead['date1'], 2, 2) . "일\n\n위 과제 항목이 삭제되었습니다.";
											$query = "DELETE FROM event WHERE type='{$sql4loggingRead['type']}' AND title='{$sql4loggingRead['title']}' AND class='{$sql4loggingRead['class']}' AND prof='{$sql4loggingRead['prof']}' AND content='{$sql4loggingRead['content']}' AND userkey='$senderID' ORDER BY inputTime DESC LIMIT 1";
										}
										else if($sql4loggingRead['type'] == "cancel") {
											if(empty($sql4loggingRead['date2'])) {
												$send['text'] = "🎩: <" . $sql4loggingRead['title'] . ">\n날짜: " . substr($sql4loggingRead['date1'], 0, 2) . "월 " . substr($sql4loggingRead['date1'], 2, 2) . "일\n\n위 휴강 항목이 삭제되었습니다.";
												$query = "DELETE FROM event WHERE type='{$sql4loggingRead['type']}' AND title='{$sql4loggingRead['title']}' AND class='{$sql4loggingRead['class']}' AND prof='{$sql4loggingRead['prof']}' AND date1='{$sql4loggingRead['date1']}' AND userkey='$senderID' ORDER BY inputTime DESC LIMIT 1";
											}
											else if(!empty($sql4loggingRead['date2'])) {
												$send['text'] = "🎩: <" . $sql4loggingRead['title'] . ">\n날짜: " . substr($sql4loggingRead['date1'], 0, 2) . "월 " . substr($sql4loggingRead['date1'], 2, 2) . "일부터 " . substr($sql4loggingRead['date2'], 0, 2) . "월 " . substr($sql4loggingRead['date2'], 2, 2) . "일 까지\n\n위 휴강 항목이 삭제되었습니다.";
												$query = "DELETE FROM event WHERE type='{$sql4loggingRead['type']}' AND title='{$sql4loggingRead['title']}' AND class='{$sql4loggingRead['class']}' AND prof='{$sql4loggingRead['prof']}' AND date1='{$sql4loggingRead['date1']}' AND date1='{$sql4loggingRead['date2']}' AND userkey='$senderID' ORDER BY inputTime DESC LIMIT 1";
											}
										}
										else if($sql4loggingRead['type'] == "exam") {
											$send['text'] = "<" . $sql4loggingRead['title'] . ">\n날짜: " . substr($sql4loggingRead['date1'], 0, 2) . "월 " . substr($sql4loggingRead['date1'], 2, 2) . "일 / ". substr($sql4loggingRead['time1'], 0, 2) . "시 " . substr($sql4loggingRead['time1'], 2, 2) . "분\n\n위 시험 항목이 삭제되었습니다.";
											$query = "DELETE FROM event WHERE type='{$sql4loggingRead['type']}' AND title='{$sql4loggingRead['title']}' AND class='{$sql4loggingRead['class']}' AND prof='{$sql4loggingRead['prof']}' AND date1='{$sql4loggingRead['date1']}' AND time1='{$sql4loggingRead['time1']}' AND userkey='$senderID' ORDER BY inputTime DESC LIMIT 1";		
										}
										$conn->query($query);	
										message($send);					
									}	
									
									$query = queryInsert('loggingRead', 'READ');
									$conn->query($query);
			
									$rgstedInfoDetail = registedConditionSubjectDetail($userInfo);
									for($i=0; $i<count($rgstedInfoDetail['title']); $i++) {
										$title = $rgstedInfoDetail['titleName'][$i];
										$class = $rgstedInfoDetail['class'][$i];
										$prof = $rgstedInfoDetail['prof'][$i];
										$send['title'][] = $rgstedInfoDetail['title'][$i];
										$send['subtitle'][] = $rgstedInfoDetail['info'][$i];
										$send['payload'][] = array("assignment_{$title}_{$class}_{$prof}", "cancel_{$title}_{$class}_{$prof}", "exam_{$title}_{$class}_{$prof}");
										
										$eventInfoTypes[$i] = array();
										for($j=0; $j<count($eventInfo); $j++) {
											if($eventInfo[$j]['title'] == $title) {
												$eventInfoTypes[$i][$j] = $eventInfo[$j]['type'];
											}
										}
										$countTypes = array_count_values($eventInfoTypes[$i]);
										$send['buttonsTitle'][$i] = array();
										is_array($countTypes) && $countTypes['assignment'] > 0 ? array_push($send['buttonsTitle'][$i], "과제({$countTypes['assignment']}개)") : array_push($send['buttonsTitle'][$i], "과제");
										is_array($countTypes) && $countTypes['cancel'] > 0 ? array_push($send['buttonsTitle'][$i], "휴강({$countTypes['cancel']}개)") : array_push($send['buttonsTitle'][$i], "휴강");
										is_array($countTypes) && $countTypes['exam'] > 0 ? array_push($send['buttonsTitle'][$i], "시험({$countTypes['exam']}개)") : array_push($send['buttonsTitle'][$i], "시험");
									}
									messageTemplateLeftSlide($send);
									
									$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
									$send['payload'] = $send['title'] = array('초기화면', '시간표 보기', '교과목 삭제하기');
									messageQR($send);
								}
							}
						} else {
							$send['text'] = "🎩: 잘못된 접근입니다.";
							message($send);
							
							$query = queryInsert('loggingRead', 'READ');
							$conn->query($query);	
													
							$rgstedInfoDetail = registedConditionSubjectDetail($userInfo);
							for($i=0; $i<count($rgstedInfoDetail['title']); $i++) {
								$title = $rgstedInfoDetail['titleName'][$i];
								$class = $rgstedInfoDetail['class'][$i];
								$prof = $rgstedInfoDetail['prof'][$i];
								$send['title'][] = $rgstedInfoDetail['title'][$i];
								$send['subtitle'][] = $rgstedInfoDetail['info'][$i];
								$send['payload'][] = array("assignment_{$title}_{$class}_{$prof}", "cancel_{$title}_{$class}_{$prof}", "exam_{$title}_{$class}_{$prof}");
								
								$eventInfoTypes[$i] = array();
								for($j=0; $j<count($eventInfo); $j++) {
									if($eventInfo[$j]['title'] == $title) {
										$eventInfoTypes[$i][$j] = $eventInfo[$j]['type'];
									}
								}
								$countTypes = array_count_values($eventInfoTypes[$i]);
								$send['buttonsTitle'][$i] = array();
								is_array($countTypes) && $countTypes['assignment'] > 0 ? array_push($send['buttonsTitle'][$i], "과제({$countTypes['assignment']}개)") : array_push($send['buttonsTitle'][$i], "과제");
								is_array($countTypes) && $countTypes['cancel'] > 0 ? array_push($send['buttonsTitle'][$i], "휴강({$countTypes['cancel']}개)") : array_push($send['buttonsTitle'][$i], "휴강");
								is_array($countTypes) && $countTypes['exam'] > 0 ? array_push($send['buttonsTitle'][$i], "시험({$countTypes['exam']}개)") : array_push($send['buttonsTitle'][$i], "시험");
							}
							messageTemplateLeftSlide($send);
							
							$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
							$send['payload'] = $send['title'] = array('초기화면', '시간표 보기', '교과목 삭제하기');
							messageQR($send);							
						}
					}
					//
					// 과제,휴강,시험 새로 등록하기
					//
					else if(preg_match("/WRITE/", $inProgressRead)) {
						if(preg_match("/[1]$/", $inProgressRead)) {
							if($messageText) {
								$readContent = $messageText;
								
								$send['text'] = "<" . $readTitle . ">\n과제내용: " . $readContent;
								message($send);
								
								$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_2', array('type'=>$readType, 'title'=>$readTitle, 'class'=>$readClass, 'prof'=>$readProf, 'content'=>$readContent));
								$conn->query($query);	
															
								$send['text'] = "🎩: 위 과제의 기한을 <숫자 4자리>로 입력해주세요.\n예) 10월 16일 -> 1016";
								$send['payload'] = $send['title'] = array('이전으로', '초기화면');
								messageQR($send);
							} else {
								$send['text'] = "🎩: 잘못된 접근입니다. <{$readTitle}>에 등록할 과제 내용를 다시 똑.디. 입력해주세요.";
								$send['payload'] = $send['title'] = array('이전으로', '초기화면');
								messageQR($send);
							}
						}
						else if(preg_match("/[2]$/", $inProgressRead)) {
							if($messageText) {
								$readDate = $messageText;
								$writeEvent = writeEvent($readDate, $readType);
								
								if($readType == "assignment") {
									if($writeEvent['condition'] == TRUE) {
										$send['text'] = "🎩: ".$writeEvent['text'];
										$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
										messageQR($send);
										
										$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_FIN', array('type'=>$readType, 'title'=>$readTitle, 'class'=>$readClass, 'prof'=>$readProf, 'content'=>$readContent, 'date1'=>$writeEvent['date1']));
										$conn->query($query);
									}					
								}
								else if($readType == "cancel" || $readType == "exam") {
									if($writeEvent['condition'] == TRUE) {
										$send['text'] = "🎩: ".$writeEvent['text'];
										$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
										messageQR($send);
										
										if($readType == "cancel") {
											if(empty($writeEvent['date2'])) {
												$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_FIN', array('type'=>$readType, 'title'=>$readTitle, 'class'=>$readClass, 'prof'=>$readProf, 'date1'=>$writeEvent['date1']));
											}
											else if(!empty($writeEvent['date2'])) {
												$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_FIN', array('type'=>$readType, 'title'=>$readTitle, 'class'=>$readClass, 'prof'=>$readProf, 'date1'=>$writeEvent['date1'], 'date2'=>$writeEvent['date2']));
											}
										}
										else if($readType == "exam") {
											$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_FIN', array('type'=>$readType, 'title'=>$readTitle, 'class'=>$readClass, 'prof'=>$readProf, 'date1'=>$writeEvent['date1'], 'time1'=>$writeEvent['time1']));
										}
										$conn->query($query);
									}
									else if($writeEvent['condition'] == FALSE) {
										$send['text'] = "🎩: ".$writeEvent['text'];
										message($send);
									
										ReturningQR();
									}
								}		
							} else {
								if($readType == "cancel") {
									$send['text'] = "🎩: 잘못된 접근입니다. <" . $payloadTitle .">에 등록할 휴강 날짜를 <숫자 4자리>로 입력해주세요.\n예) 10월 16일 -> 1016\n\n휴강이 단일이 아닌 복수일(기간)이라면,\n첫날과 마지막날을 슬래쉬(/)를 포함한 <숫자 8자리>로 입력해주세요.\n예) 10월16일부터 10월 23일 -> 1016/1023";			
								}
								else 	if($readType == "exam") {
									$send['text'] = "🎩: 잘못된 접근입니다. <" . $payloadTitle .">에 등록할 시험 날짜와 시간을\n슬래쉬(/)를 포함한 <숫자 8자리>로 입력해주세요.\n예) 10월 16일 오후 1시반 -> 1016/1330";
								}								
								$send['payload'] = $send['title'] = array('이전으로', '초기화면');
								messageQR($send);
							}	
						}
						else if(preg_match("/FIN$/", $inProgressRead)) {
							if($payloadQR) {
								if($payloadQR == "⭕") {
									if($readType == "assignment") {
										$readDateMonth = substr($readDate1, 0, 2);
										$readDateDay = substr($readDate1, 2, 2);
										
										$send['text'] = "🎩: <" . $readTitle . ">\n과제내용: " . $readContent . "\n기한: " .  $readDateMonth . "월 " . $readDateDay . "일\n\n위 내용이 과제에 등록되었습니다.";
										message($send);
										
										$query = "INSERT IGNORE INTO event (year, semester, userkey, type, title, class, prof, content, date1, inputTime)
															SELECT year, semester, userkey, type, title, class, prof, content, date1, '$inputTime'
																FROM loggingRead
																WHERE userkey='$senderID'
																ORDER BY inputTime DESC
																LIMIT 1";
										$conn->query($query);
									}
									else 	if($readType == "cancel") {
										$readDateMonth1 = substr($readDate1, 0, 2);
										$readDateDay1 = substr($readDate1, 2, 2);
										$readDateMonth2 = substr($readDate2, 0, 2);
										$readDateDay2 = substr($readDate2, 2, 2);
										
										if(empty($sql4loggingRead['date2'])) {
											$send['text'] = "🎩: <" . $readTitle . ">\n날짜: " . $readDateMonth1 . "월 " . $readDateDay1 . "일\n\n위 내용이 휴강에 등록되었습니다.";
								
											$query = "INSERT IGNORE INTO event (year, semester, userkey, type, title, class, prof, date1, inputTime)
																SELECT year, semester, userkey, type, title, class, prof, date1, '$inputTime'
																	FROM loggingRead
																	WHERE userkey='$senderID'
																	ORDER BY inputTime DESC
																	LIMIT 1";
											$conn->query($query);
										}
										else if(!empty($sql4loggingRead['date2'])) {
											$send['text'] = "🎩: <" . $readTitle . ">\n날짜: " . $readDateMonth1 . "월 " . $readDateDay1 . "일부터 " . $readDateMonth2 . "월 " . $readDateDay2 . "일 까지\n\n위 내용이 휴강에 등록되었습니다.";
							
											$query = "INSERT IGNORE INTO event (year, semester, userkey, type, title, class, prof, date1, date2, inputTime)
																SELECT year, semester, userkey, type, title, class, prof, date1, date2, '$inputTime'
																	FROM loggingRead
																	WHERE userkey='$senderID'
																	ORDER BY inputTime DESC
																	LIMIT 1";
											$conn->query($query);
										}
										message($send);
									}
									else 	if($readType == "exam") {
										$readDateMonth = substr($readDate1, 0, 2);
										$readDateDay = substr($readDate1, 2, 2);
										$readDateHour = substr($readTime1, 0, 2);
										$readDateMin = substr($readTime1, 2, 2);
									
										$send['text'] = "🎩: <" . $readTitle . ">\n날짜: " . $readDateMonth . "월 " . $readDateDay . "일 / ". $readDateHour . "시 " . $readDateMin . "분\n\n위 내용이 시험에 등록되었습니다.";
										message($send);
										
										$query = "INSERT IGNORE INTO event (year, semester, userkey, type, title, class, prof, date1, time1, inputTime)
															SELECT year, semester, userkey, type, title, class, prof, date1, time1, '$inputTime'
																FROM loggingRead
																WHERE userkey='$senderID'
																ORDER BY inputTime DESC
																LIMIT 1";
										$conn->query($query);
									}
									
									$query = queryInsert('loggingRead', 'READ');
									$conn->query($query);
									
									$rgstedInfoDetail = registedConditionSubjectDetail($userInfo);
									for($i=0; $i<count($rgstedInfoDetail['title']); $i++) {
										$title = $rgstedInfoDetail['titleName'][$i];
										$class = $rgstedInfoDetail['class'][$i];
										$prof = $rgstedInfoDetail['prof'][$i];
										$send['title'][] = $rgstedInfoDetail['title'][$i];
										$send['subtitle'][] = $rgstedInfoDetail['info'][$i];
										$send['payload'][] = array("assignment_{$title}_{$class}_{$prof}", "cancel_{$title}_{$class}_{$prof}", "exam_{$title}_{$class}_{$prof}");
										
										$eventInfoTypes[$i] = array();
										for($j=0; $j<count($eventInfo); $j++) {
											if($eventInfo[$j]['title'] == $title) {
												$eventInfoTypes[$i][$j] = $eventInfo[$j]['type'];
											}
										}
										$countTypes = array_count_values($eventInfoTypes[$i]);
										$send['buttonsTitle'][$i] = array();
										is_array($countTypes) && $countTypes['assignment'] > 0 ? array_push($send['buttonsTitle'][$i], "과제({$countTypes['assignment']}개)") : array_push($send['buttonsTitle'][$i], "과제");
										is_array($countTypes) && $countTypes['cancel'] > 0 ? array_push($send['buttonsTitle'][$i], "휴강({$countTypes['cancel']}개)") : array_push($send['buttonsTitle'][$i], "휴강");
										is_array($countTypes) && $countTypes['exam'] > 0 ? array_push($send['buttonsTitle'][$i], "시험({$countTypes['exam']}개)") : array_push($send['buttonsTitle'][$i], "시험");
									}
									messageTemplateLeftSlide($send);
									
									$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
									$send['payload'] = $send['title'] = array('초기화면', '시간표 보기', '교과목 삭제하기');
									messageQR($send);
								}
								else if($payloadQR == "❌") {
									if($payloadType == "assignment") {
										$send['text'] = "🎩: <" . $payloadTitle .">에 등록할 과제 내용를 상세히 입력해주세요.";
										$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_1', array("type"=>$payloadType, "title"=>$payloadTitle, "class"=>$payloadClass, "prof"=>$payloadProf));
									}
									else 	if($payloadType == "cancel") {
										$send['text'] = "🎩: <" . $payloadTitle .">에 등록할 휴강 날짜를 <숫자 4자리>로 입력해주세요.\n예) 10월 16일 -> 1016\n\n휴강이 단일이 아닌 복수일(기간)이라면,\n첫날과 마지막날을 슬래쉬(/)를 포함한 <숫자 8자리>로 입력해주세요.\n예) 10월16일부터 10월 23일 -> 1016/1023";			
										$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_2', array("type"=>$payloadType, "title"=>$payloadTitle, "class"=>$payloadClass, "prof"=>$payloadProf));
									}
									else 	if($payloadType == "exam") {
										$send['text'] = "🎩: <" . $payloadTitle .">에 등록할 시험 날짜와 시간을\n슬래쉬(/)를 포함한 <숫자 8자리>로 입력해주세요.\n예) 10월 16일 오후 1시반 -> 1016/1330";
										$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_2', array("type"=>$payloadType, "title"=>$payloadTitle, "class"=>$payloadClass, "prof"=>$payloadProf));
									}
									$conn->query($query);
									message($send);
								
									ReturningQR();
								}
							} else {
								$writeEvent = writeEvent($readDate, $readType);
								$send['text'] = "🎩: 잘못된 접근입니다. ".$writeEvent['text'];
								$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
								messageQR($send);								
							}
						} 
					}
					//
					// 다른 사람 정보 보기
					//
					else if(preg_match("/OTHERS/", $inProgressRead)) {
						if($payload) {
							$payloadExplode = explode("_", $payload);
							$payloadType = $payloadExplode[0];
							$payloadTitle = $payloadExplode[1];
							$payloadClass = $payloadExplode[2];
							$payloadProf = $payloadExplode[3];
							$payloadInputTime = $payloadExplode[4];
							
							$query = "SELECT * FROM event WHERE userkey!='$senderID' AND year='$thisYear' AND semester='$thisSemester' AND type='$payloadType' AND title='$payloadTitle' AND class='$payloadClass' AND prof='$payloadProf' AND inputTime='$payloadInputTime'";
							$sql4eventBringMe = $conn->query($query)->fetch_assoc();
							$eventInfoBringMe = $sql4eventBringMe;
							if($eventInfoBringMe) {
								if($payloadType == "assignment") {
									$readTypeKR = "과제";
									$readContent = $eventInfoBringMe['content'];
									$readDateMonth = substr($eventInfoBringMe['date1'], 0, 2);
									$readDateDay = substr($eventInfoBringMe['date1'], 2, 2);
									
									$query = "INSERT INTO event (year, semester, userkey, type, title, class, prof, content, date1, inputTime) VALUE ('$thisYear', '$thisSemester', '$senderID', '$payloadType', '$payloadTitle', '$payloadClass', '$payloadProf', '{$eventInfoBringMe['content']}', '{$eventInfoBringMe['date1']}', '$inputTime')";		
									$send['text'] = "🎩: <" . $payloadTitle . ">\n과제내용: " . $readContent . "\n기한: " .  $readDateMonth . "월 " . $readDateDay . "일\n\n위 내용이 과제에 등록되었습니다.";
								}
								else 	if($payloadType == "cancel") {
									$readTypeKR = "휴강";
									$readDateMonth1 = substr($eventInfoBringMe['date1'], 0, 2);
									$readDateDay1 = substr($eventInfoBringMe['date1'], 2, 2);
									$readDateMonth2 = substr($eventInfoBringMe['date2'], 0, 2);
									$readDateDay2 = substr($eventInfoBringMe['date2'], 2, 2);
			
									if(empty($eventInfoBringMe['date2'])) {
										$query = "INSERT INTO event (year, semester, userkey, type, title, class, prof, date1, inputTime) VALUE ('$thisYear', '$thisSemester', '$senderID', '$payloadType', '$payloadTitle', '$payloadClass', '$payloadProf', '{$eventInfoBringMe['date1']}', '$inputTime')";						
										$send['text'] = "🎩: <" . $payloadTitle . ">\n날짜: " . $readDateMonth1 . "월 " . $readDateDay1 . "일\n\n위 내용이 휴강에 등록되었습니다.";
									} else {
										$query = "INSERT INTO event (year, semester, userkey, type, title, class, prof, date1, date2, inputTime) VALUE ('$thisYear', '$thisSemester', '$senderID', '$payloadType', '$payloadTitle', '$payloadClass', '$payloadProf', '{$eventInfoBringMe['date1']}', '{$eventInfoBringMe['date2']}', '$inputTime')";
										$send['text'] = "🎩: <" . $payloadTitle . ">\n날짜: " . $readDateMonth1 . "월 " . $readDateDay1 . "일부터 " . $readDateMonth2 . "월 " . $readDateDay2 . "일 까지\n\n위 내용이 휴강에 등록되었습니다.";
									}
								}
								else 	if($payloadType == "exam") {
									$readTypeKR = "시험";
									$readDateMonth = substr($eventInfoBringMe['date1'], 0, 2);
									$readDateDay = substr($eventInfoBringMe['date1'], 2, 2);
									$readDateHour = substr($eventInfoBringMe['time1'], 0, 2);
									$readDateMin = substr($eventInfoBringMe['time1'], 2, 2);
									
									$query = "INSERT INTO event (year, semester, userkey, type, title, class, prof, date1, time1, inputTime) VALUE ('$thisYear', '$thisSemester', '$senderID', '$payloadType', '$payloadTitle', '$payloadClass', '$payloadProf', '{$eventInfoBringMe['date1']}', '{$eventInfoBringMe['time1']}', '$inputTime')";
									$send['text'] = "🎩: <" . $payloadTitle . ">\n날짜: " . $readDateMonth1 . "월 " . $readDateDay1 . "일부터 " . $readDateMonth2 . "월 " . $readDateDay2 . "일 까지\n\n위 내용이 휴강에 등록되었습니다.";
								}		
								$conn->query($query);
								message($send);
							}
			
							$query = "SELECT * FROM event WHERE userkey!='$senderID' AND year='$thisYear' AND semester='$thisSemester' AND type='$payloadType' AND title='$payloadTitle' AND class='$payloadClass' AND prof='$payloadProf'";
							$sql4eventOther = $conn->query($query);	
							while($row4eventOther = $sql4eventOther->fetch_assoc()) {
								$eventDate1 = date("Y-m-d", mktime(0, 0, 0, (int)substr($row4eventOther['date1'], 0, 2), (int)substr($row4eventOther['date1'], 2, 4), date("Y")));
								$eventDate2 = date("Y-m-d", mktime(0, 0, 0, (int)substr($row4eventOther['date2'], 0, 2), (int)substr($row4eventOther['date2'], 2, 4), date("Y")));
								$nowDate = date("Y-m-d", strtotime($inputTime));
								if((empty($row4eventOther['date2']) && $eventDate1 >= $nowDate) || (!empty($row4eventOther['date2']) && $eventDate2 >= $nowDate)) {
									$eventInfoOther[] = $row4eventOther;
								}
							}
							
							if(count($eventInfoOther) > 0) {
								// 전체가 10개 이하 => 그대로 제공
								if(count($eventInfoOther) < 11) {
									$readEventInfo = readEventInfo($eventInfoOther, $payloadType);
								}
								// 전체가 11개 이상 => 랜덤으로 추출 후 제공
								else if(count($eventInfoOther) >= 11) {
									$randomKeys = array_rand($eventInfoOther, 10);
									for($i=0; $i<count($randomKeys); $i++) {
										$eventInfoOtherRandom[] = $eventInfoOther[$randomKeys[$i]];
									}
									$readEventInfo = readEventInfo($eventInfoOtherRandom, $payloadType);
								}
								
								$send['title'] = $readEventInfo['title'];
								$send['subtitle'] = $readEventInfo['info'];
								$send['buttonsTitle'] = array("나의 " . $readTypeKR . " 목록으로 가져오기");
								$generalPayload = $readEventInfo['payload'];
								for($i=0; $i<count($generalPayload); $i++) {
									$generalPayloadExp = explode("_", $generalPayload[$i]);
									$send['payload'][] = "{$payloadType}_{$payloadTitle}_{$payloadClass}_{$payloadProf}_" . $generalPayloadExp[3];
								}
								messageTemplateLeftSlide($send);
								
								$query = queryInsert('loggingRead', 'READ_EVENT_OTHERS', array("type"=>$payloadType, "title"=>$payloadTitle, "class"=>$payloadClass, "prof"=>$payloadProf));
								$conn->query($query);
							}
							ReturningQR();
						} else {
							$query = "SELECT * FROM event WHERE userkey!='$senderID' AND year='$thisYear' AND semester='$thisSemester' AND type='$readType' AND title='$readTitle' AND class='$readClass' AND prof='$readProf'";
							$sql4eventOther = $conn->query($query);	
							while($row4eventOther = $sql4eventOther->fetch_assoc()) {
								$eventDate1 = date("Y-m-d", mktime(0, 0, 0, (int)substr($row4eventOther['date1'], 0, 2), (int)substr($row4eventOther['date1'], 2, 4), date("Y")));
								$eventDate2 = date("Y-m-d", mktime(0, 0, 0, (int)substr($row4eventOther['date2'], 0, 2), (int)substr($row4eventOther['date2'], 2, 4), date("Y")));
								$nowDate = date("Y-m-d", strtotime($inputTime));
								if((empty($row4eventOther['date2']) && $eventDate1 >= $nowDate) || (!empty($row4eventOther['date2']) && $eventDate2 >= $nowDate)) {
									$eventInfoOther[] = $row4eventOther;
								}
							}
							if($readType == "assignment") {
								$readTypeKR = "과제";
							}
							else if($readType == "cancel") {
								$readTypeKR = "휴강";
							}
							else if($readType == "exam") {
								$readTypeKR = "시험";
							}
							
							if(count($eventInfoOther) > 0) {
								// 전체가 10개 이하 => 그대로 제공
								if(count($eventInfoOther) < 11) {
									$readEventInfo = readEventInfo($eventInfoOther, $readType);
								}
								// 전체가 11개 이상 => 랜덤으로 추출 후 제공
								else if(count($eventInfoOther) >= 11) {
									$randomKeys = array_rand($eventInfoOther, 10);
									for($i=0; $i<count($randomKeys); $i++) {
										$eventInfoOtherRandom[] = $eventInfoOther[$randomKeys[$i]];
									}
									$readEventInfo = readEventInfo($eventInfoOtherRandom, $readType);
								}
								
								$send['title'] = $readEventInfo['title'];
								$send['subtitle'] = $readEventInfo['info'];
								$send['buttonsTitle'] = array("나의 " . $readTypeKR . " 목록으로 가져오기");
								$generalPayload = $readEventInfo['payload'];
								for($i=0; $i<count($generalPayload); $i++) {
									$generalPayloadExp = explode("_", $generalPayload[$i]);
									$send['payload'][] = "{$readType}_{$readTitle}_{$readClass}_{$readProf}_" . $generalPayloadExp[3];
								}
								messageTemplateLeftSlide($send);
							}
							ReturningQR();				
						}
					}
				}
				//
				// 등록된 교과목 삭제
				//
				else if(preg_match("/DELETE/", $inProgressRead)) {
					if(preg_match("/DELETE$/", $inProgressRead)) {
						if($payload) {
							$payloadExplode = explode("_", $payload);
							$payloadType = $payloadExplode[0];
							$payloadTitle = $payloadExplode[1];
							$payloadClass = $payloadExplode[2];
							$payloadProf = $payloadExplode[3];
							
							if($payloadType == "DELETE") {
								$query = queryInsert('loggingRead', 'READ_DELETE_SUBJECT', array("title"=>$payloadTitle, "class"=>$payloadClass, "prof"=>$payloadProf));
								$conn->query($query);
					
								$send['text'] = "🎩: <" . $payloadTitle . ">을(를) 정말 삭제하시겠습니까?";
								$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
								messageQR($send);								
							} else {
								$send['text'] = "🎩: 잘못된 접근입니다.";
								message($send);
								
								$rgstedInfoDetail = registedConditionSubjectDetail($userInfo);
								for($i=0; $i<count($rgstedInfoDetail['title']); $i++) {
									$title = $rgstedInfoDetail['titleName'][$i];
									$class = $rgstedInfoDetail['class'][$i];
									$prof = $rgstedInfoDetail['prof'][$i];
									$send['title'][] = $rgstedInfoDetail['title'][$i];
									$send['subtitle'][] = $rgstedInfoDetail['info'][$i];
									$send['payload'][] = "DELETE_{$title}_{$class}_{$prof}";
								}
								$send['buttonsTitle'] = array("교과목 삭제하기");
								messageTemplateLeftSlide($send);
								ReturningQR();									
							}
						} else {
							$send['text'] = "🎩: 잘못된 접근입니다.";
							message($send);
							
							$rgstedInfoDetail = registedConditionSubjectDetail($userInfo);
							for($i=0; $i<count($rgstedInfoDetail['title']); $i++) {
								$title = $rgstedInfoDetail['titleName'][$i];
								$class = $rgstedInfoDetail['class'][$i];
								$prof = $rgstedInfoDetail['prof'][$i];
								$send['title'][] = $rgstedInfoDetail['title'][$i];
								$send['subtitle'][] = $rgstedInfoDetail['info'][$i];
								$send['payload'][] = "DELETE_{$title}_{$class}_{$prof}";
							}
							$send['buttonsTitle'] = array("교과목 삭제하기");
							messageTemplateLeftSlide($send);
							ReturningQR();								
						}					
					}
					else if(preg_match("/SUBJECT$/", $inProgressRead)) {
						if($payloadQR) {
							if($payloadQR == "⭕") {
								$query = "DELETE FROM user WHERE userkey='$senderID' AND year='$thisYear' AND semester='$thisSemester' AND title='$readTitle' AND class='$readClass' AND prof='$readProf'";
								$conn->query($query);
								
								$query = queryInsert('loggingRead', 'READ');
								$conn->query($query);
								
								$send['text'] = "🎩: <" . $readTitle . ">이(가) 삭제되었습니다";
								message($send);
							}
							else if($payloadQR == "❌") {
								$send['text'] = "🎩: <" . $readTitle . ">의 삭제가 취소되었습니다";
								message($send);
							}
							$rgstedInfoDetail = registedConditionSubjectDetail($userInfo);
							for($i=0; $i<count($rgstedInfoDetail['title']); $i++) {
								$title = $rgstedInfoDetail['titleName'][$i];
								$class = $rgstedInfoDetail['class'][$i];
								$prof = $rgstedInfoDetail['prof'][$i];
								$send['title'][] = $rgstedInfoDetail['title'][$i];
								$send['subtitle'][] = $rgstedInfoDetail['info'][$i];
								$send['payload'][] = array("assignment_{$title}_{$class}_{$prof}", "cancel_{$title}_{$class}_{$prof}", "exam_{$title}_{$class}_{$prof}");
								
								$eventInfoTypes[$i] = array();
								for($j=0; $j<count($eventInfo); $j++) {
									if($eventInfo[$j]['title'] == $title) {
										$eventInfoTypes[$i][$j] = $eventInfo[$j]['type'];
									}
								}
								$countTypes = array_count_values($eventInfoTypes[$i]);
								$send['buttonsTitle'][$i] = array();
								is_array($countTypes) && $countTypes['assignment'] > 0 ? array_push($send['buttonsTitle'][$i], "과제({$countTypes['assignment']}개)") : array_push($send['buttonsTitle'][$i], "과제");
								is_array($countTypes) && $countTypes['cancel'] > 0 ? array_push($send['buttonsTitle'][$i], "휴강({$countTypes['cancel']}개)") : array_push($send['buttonsTitle'][$i], "휴강");
								is_array($countTypes) && $countTypes['exam'] > 0 ? array_push($send['buttonsTitle'][$i], "시험({$countTypes['exam']}개)") : array_push($send['buttonsTitle'][$i], "시험");
							}
							messageTemplateLeftSlide($send);
						} else {
							$send['text'] = "🎩: 잘못된 접근입니다. <" . $readTitle . ">을(를) 정말 삭제하시겠습니까?";
							$send['payload'] = $send['title'] = array('⭕', '초기화면', '❌');
							messageQR($send);							
						}
					}
				} 
			} else {
				// defense // 보완 필요
				if(!isset($userInfo)) {
					if(!isset($registerProcessing)) {
						$query = insertProcessing();
						$conn->query($query);
						
						$send['text'] = "🎩: 새로운 유저시군요!\n튜토리얼을 통해 {$senderFullName}님의 교과목을 등록해볼까요?";
					} else {
						$send['text'] = "🎩: 교과목을 하나 이상 등록하면 추가 기능이 활성화됩니다.\n튜토리얼을 통해 {$senderFullName}님의 교과목을 등록해볼까요?";
					}
					message($send);
					
					$send['title'] = $send['buttonsTitle'] = $send['payload'] = array("튜토리얼 시작하기");
					$imagePath = 'https://bhandy.kr/scheduler/univ/pnu/images/';
					$send['imageURL'] = array($imagePath.'tutorial.jpg');
					messageTemplateLeftSlideWithImage($send);
				} else {
					// 초기화
					$query = resetProcessing();
					$conn->query($query);
					
					if($messageText) {
						$send['text'] = "🎩: 마! 버튼눌러라 버튼!";
					} else {
						$send['text'] = "🎩: 잘못된 접근입니다.";
					}
					$send['payload'] = $send['title'] = array('초기화면');
					messageQR($send);					
				}
			}
		}
	}
}