<?php
//교과목 DataBase
//$course = "courseTest";
$course = "course2017W";
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// 데이터베이스에 존재하는 모든 정보
$query = "SELECT * FROM $course";
$sql4coursesAll = $conn->query($query);
while($row4coursesAll = $sql4coursesAll->fetch_assoc()) {
	$dbAllDivs[] = $row4coursesAll['divs'];
	$dbAllTitle[] = $row4coursesAll['title'];
	$dbAllMajor[] = $row4coursesAll['major'];
	$dbAllFields[] = $row4coursesAll['fields'];
	if(!empty($row4coursesAll['fields'])) {
		$dbAllFields[] = $row4coursesAll['fields'];
	}
}

// 등록 진행 과정
$query = "SELECT * FROM processing WHERE userkey = '$senderID'";
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
$query = "SELECT inputTime FROM logging WHERE userkey = '$senderID' ORDER BY inputTime DESC LIMIT 1";
$sql4loggingInputTime = $conn->query($query)->fetch_assoc();
$latestInputTime = $sql4loggingInputTime['inputTime'];
$latestAccessTime = (strtotime($inputTime) - strtotime($latestInputTime)) / 3600;

// inProgress
$query = "SELECT inProgress FROM logging WHERE userkey = '$senderID' ORDER BY inputTime DESC LIMIT 1";
$sql4loggingInProgress = $conn->query($query)->fetch_assoc();
$inProgress = $sql4loggingInProgress['inProgress'];

// inProgress for Read
$query = "SELECT inProgress FROM loggingRead WHERE userkey = '$senderID' ORDER BY inputTime DESC LIMIT 1";
$sql4loggingInProgressRead = $conn->query($query)->fetch_assoc();
$inProgressRead = $sql4loggingInProgressRead['inProgress'];

// 유저 이름
$getSenderFullName = json_decode(curlGet("https://graph.facebook.com/v2.6/" . $senderID . "?fields=first_name,last_name,profile_pic,locale,timezone,gender&access_token="), true);
if(isset($getSenderFullName['last_name']) && isset($getSenderFullName['first_name'])) {
	$senderFullName = $getSenderFullName['last_name'] . $getSenderFullName['first_name'];
}

// 등록된 유저 정보
$query = "SELECT * FROM user WHERE userkey='$senderID'";
$sql4user = $conn->query($query);
while($row4user = $sql4user->fetch_assoc()) {
	$userInfo[] = $row4user;
}
// 등록된 이벤트 정보
$query = "SELECT * FROM event WHERE userkey='$senderID'";
$sql4event = $conn->query($query);	
while($row4event = $sql4event->fetch_assoc()) {
	$eventInfo[] = $row4event;
}

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
if($payload == "시작하기" || $payload == "초기화면" || $payloadQR == "초기화면" || preg_match("/^시작/", $messageText) || preg_match("/^ㄱ/", $messageText)) {
	if(!isset($userInfo)) {
		if(!isset($registerProcessing)) {
			$query = insertProcessing();
			$conn->query($query);
			
			$send['text'] = "🎩: 새로운 유저시군요!\n	튜토리얼을 통해 교과목을 하나 이상 등록하시면 BHandy의 추가 기능이 활성화됩니다.";
		} else {
			$send['text'] = "🎩: 교과목을 하나 이상 등록하시면 BHandy의 추가 기능이 활성화됩니다.\n	튜토리얼을 통해 {$senderFullName}님의 교과목을 등록해볼까요?";
		}
		message($send);
		
		$send['title'] = $send['buttonsTitle'] = $send['payload'] = array("튜토리얼 시작하기");
		$imagePath = 'https://bhandy.kr/scheduler/univ/pnu/images/';
		$send['imageURL'] = array($imagePath.'tutorial.jpg');
		messageTemplateLeftSlideWithImage($send);
		/*
		$send['elementsTitle'] = "신규";
		$send['elementsButtonsTitle'] = array("튜토리얼 시작하기");
		messageTemplate($send);
		*/			
	}
	else if(isset($userInfo)) {
		// 초기화
		$query = resetProcessing();
		$conn->query($query);	
	
		$query = queryInsert('logging', 'START');
		$conn->query($query);
		
		$send['text'] = "🎩: {$senderFullName}님 반갑습니다.";
		message($send);
		
		$send['title'] = $send['buttonsTitle'] = $send['payload'] = array("등록된 과제∙휴강∙시험 정보 보기", "등록된 교과목 정보 보기", "교과목 등록하기");
		$imagePath = 'https://bhandy.kr/scheduler/univ/pnu/images/';
		$send['imageURL'] = array($imagePath.'information.jpg', $imagePath.'notepad.jpg', $imagePath.'register.jpg');
		messageTemplateLeftSlideWithImage($send);
		/*
		$send['elementsTitle'] = "기존";
		$send['elementsButtonsTitle'] = array("교과목 등록하기", "등록된 교과목 정보 보기", "등록된 과제∙휴강∙시험 정보 보기");
		messageTemplate($send);
		*/
	}
}
else if(preg_match("/^튜토리얼/", $payload) || preg_match("/^교과목(.*)등록/", $payload) || preg_match("/^등록된(.*)정보(.*)보기/", $payload)) {
	// 초기화
	$query = resetProcessing();
	$conn->query($query);
	
	if(preg_match("/^튜토리얼/", $payload)) {
		if($course[strlen($course)-1] == "W" || $a[strlen($course)-1] == "S") {
			$query = updateProcessing('insert');
			$conn->query($query);
			$query = queryInsert('logging', 'REGISTER_INSERT');
			$conn->query($query);
			
			$send['text'] = "🎩: 교과목 등록을 시작합니다.\n	{$senderFullName}님이 수강 중인 교과목명을 입력해주세요.";
			message($send);
		} else {
			$query = queryInsert('logging', 'REGISTER');
			$conn->query($query);
			
			$send['text'] = "🎩: 교과목 등록을 시작합니다.\n	{$senderFullName}님이 수강 중인 교과목의 과목 구분을 선택해주세요.";
			message($send);
				
			$send['elementsTitle'] = "과목 구분";
			$send['elementsButtonsTitle'] = array_keys(array_flip($dbAllDivs));
			array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
			messageTemplate($send);
		}
	}
	else if(preg_match("/^교과목(.*)등록/", $payload) || preg_match("/^교과목(.*)추가(.*)등록/", $payloadQR)) {
		if($course[strlen($course)-1] == "W" || $a[strlen($course)-1] == "S") {
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
			$send['text'] = "🎩: 교과목 등록을 시작합니다.\n	검색하고자하는 교과목명을 입력해주세요.";
			message($send);
		} else {
			$query = queryInsert('logging', 'REGISTER');
			$conn->query($query);
		
			if(!isset($userInfo)) {
				$send['text'] = "🎩: 교과목 등록을 시작합니다.\n	과목 구분을 선택해주세요.";
				message($send);
					
				$send['elementsTitle'] = "과목 구분";
				$send['elementsButtonsTitle'] = array_keys(array_flip($dbAllDivs));
				array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
				messageTemplate($send);
			}
			else if(isset($userInfo)) {
				$rgstedInfo = registedConditionSubject($userInfo);
				isset($rgstedInfo) ? $rgstedInfo = implode("\n", $rgstedInfo) : "";
	
				$send['text'] = "<교과목 등록 현황>\n" . $rgstedInfo . "\n\n총 " . count($userInfo) . "과목";
				message($send);
							
				$send['elementsTitle'] = "과목 구분";
				$send['elementsButtonsTitle'] = array_keys(array_flip($dbAllDivs));
				array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
				messageTemplate($send);
			}			
		}
		$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
		$send['payload'] = $send['title'] = array('초기화면');
		messageQR($send);
	}
	else if(preg_match("/^등록된(.*)정보(.*)보기/", $payload)) {
		if(preg_match("/교과목/", $payload)) {
			$query = queryInsert('logging', 'READ_SUBJECT');
			$conn->query($query);
			
			$send['text'] = "<교과목 등록 현황>\n총 " . count($userInfo) . "과목 등록 완료";
			message($send);
			
			$rgstedInfoDetail = registedConditionSubjectDetail($userInfo);
			for($i=0; $i<count($rgstedInfoDetail['title']); $i++) {
				$titleName = $rgstedInfoDetail['titleName'][$i];
				$send['title'][] = $rgstedInfoDetail['title'][$i];
				$send['subtitle'][] = $rgstedInfoDetail['info'][$i];
				$send['payload'][] = array("READ-".$titleName, "REGISTER-".$titleName, "DELETE-".$titleName);
				
			}
			$send['buttonsTitle'] = array("등록된 과제∙휴강∙시험 정보 보기", "과제∙휴강∙시험 정보 등록하기", "교과목 삭제하기");
			messageTemplateLeftSlide($send);
			
			$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
			$send['payload'] = $send['title'] = array('시간표 보기', '초기화면');
			messageQR($send);
		}
		else if(preg_match("/과제(.*)휴강(.*)시험/", $payload)) {
			$query = queryInsert('logging', 'READ_EVENT');
			$conn->query($query);
			$query = queryInsert('loggingRead', 'READ_EVENT');
			$conn->query($query);
			
			if(count($eventInfo) == 0) {
				$send['text'] = "🎩: {$senderFullName}님은 등록한 항목이 없습니다.\n\n하나 이상의 항목을 작성하시면\n수강 중인 교과목의 과제∙휴강∙시험 정보를 열람할 수 있습니다.\n\n새로운 내용을 입력하거나, 그렇지 않으면 초기화면으로 돌아가주세요.";
				$send['payload'] = $send['title'] = array('새로 등록하기', '초기화면');
			} else {
				for($i=0; $i<count($eventInfo); $i++) {
					$eventInfoTypes[] = $eventInfo[$i]['type'];
				}
				$countTypes = array_count_values($eventInfoTypes);
				
				if(empty($countTypes['assignment']) || empty($countTypes['cancel']) || empty($countTypes['exam'])) {
					if(empty($countTypes['assignment'])) {
						$countTypes['assignment'] = 0;
					}
					if(empty($countTypes['cancel'])) {
						$countTypes['cancel'] = 0;
					}
					if(empty($countTypes['exam'])) {
						$countTypes['exam'] = 0;
					}
				}					
				
				$send['text'] = "<등록된 과제∙휴강∙시험 현황>\n ∙과제: " . $countTypes['assignment'] . "개\n ∙휴강: " . $countTypes['cancel'] . "개\n ∙시험: " . $countTypes['exam'] . "개";
				message($send);
				
				$typeArr = array("assignment", "cancel", "exam");
				for($i=0; $i<count($typeArr); $i++) {
					$readEventInfo = readEventInfo($eventInfo, $typeArr[$i]);
					if($readEventInfo) {
						$send['title'] = $readEventInfo['title'];
						$send['subtitle'] = $readEventInfo['info'];
						$send['buttonsTitle'] = array("다른 수강생들이 등록한 정보 보기");
						$send['payload'] = $readEventInfo['payload'];
						messageTemplateLeftSlide($send);							
					}
				}

				$send['text'] = "🎩: 다른 수강생들이 등록한 정보를 열람하거나,\n	새로운 내용을 입력 또는 수정할 수 있습니다.";
				$send['payload'] = $send['title'] = array('새로 등록하기', '등록된 정보 수정하기', '초기화면');
			}
			messageQR($send);
		}
	}
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////// 시간표 보기 ///////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
else if($payloadQR == "시간표 보기") {
	// 시간표 이미지 생성 경로
	$mkTTpath = $_SERVER["DOCUMENT_ROOT"] . '/scheduler/univ/timetable';
	// 시간표 이미지 생성
	mkTT($senderID, $mkTTpath);

	$ttImagePath = 'https://bhandy.kr/scheduler/univ/timetable/image/tt_'.$senderID.'.jpg';
	
	$send['img']['url'] = $ttImagePath;
	messageImage($send);
	
	if($inProgress == "START") {
		$send['text'] = "🎩: 계속해서 진행해주세요.";
		$send['payload'] = $send['title'] = array("교과목 추가 등록", "시간표 보기", "초기화면");
		messageQR($send);
	}
	else if($inProgress == "READ_SUBJECT") {
		$send['text'] = "<교과목 등록 현황>\n총 " . count($userInfo) . "과목 등록 완료";
		message($send);
		
		$rgstedInfoDetail = registedConditionSubjectDetail($userInfo);
		for($i=0; $i<count($rgstedInfoDetail['title']); $i++) {
			$titleName = $rgstedInfoDetail['titleName'][$i];
			$send['title'][] = $rgstedInfoDetail['title'][$i];
			$send['subtitle'][] = $rgstedInfoDetail['info'][$i];
			$send['payload'][] = array("READ-".$titleName, "REGISTER-".$titleName, "DELETE-".$titleName);
		}
		$send['buttonsTitle'] = array("등록된 과제∙휴강∙시험 정보 보기", "과제∙휴강∙시험 정보 등록하기", "교과목 삭제하기");
		messageTemplateLeftSlide($send);
		
		$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
		$send['payload'] = $send['title'] = array('시간표 보기', '초기화면');
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
	
	$query = "INSERT INTO attendance (userkey, attend, title, class, prof, day, time, inputTime)
												VALUE ('$senderID', '$payloadAttend', '$payloadTitle', '$payloadClass', '$payloadProf', '$payloadDay', '$payloadTime', '$inputTime')";
	$conn->query($query);
	
	if($payloadAttend == "YES") {
		$textArr = array("아..?", "개망..", "아 망했네", "쉣", "ㅠㅠ", "헐ㅠㅠ", "");	
	}
	else if($payloadAttend == "NOTYET" || $payloadAttend == "IDONTKNOW") {
		$textArr = array("어키", "어키여", "오키", "알게씀ㅇㅇ", "ㅇㅋ", "알게따ㅎㅎ");			
	}
	else if($payloadAttend == "NO") {
		$textArr = array("땡큐ㅋㅋ", "감사여ㅋㅋ", "ㄱㅅ", "Thanks U", "어키ㅋㅋ", "오키ㅋㅋ");		
	}
	shuffle($textArr);
	$send['text'] = "🎩: " . $textArr[0];
	$send['payload'] = $send['title'] = array('초기화면');
	messageQR($send);
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////// 이전으로 ////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
else if($payloadQR == "이전으로") {
	if(preg_match("/^START$/", $inProgress) || preg_match("/^REGISTER$/", $inProgress) || preg_match("/^READ(.*)SUBJECT$/", $inProgress) || (preg_match("/^READ(.*)EVENT$/", $inProgress) && preg_match("/^READ(.*)EVENT$/", $inProgressRead))) {
		$query = resetProcessing();
		$conn->query($query);
	
		if(!isset($userInfo)) {
			if(!isset($registerProcessing)) {
				$query = insertProcessing();
				$conn->query($query);
				
				$send['text'] = "🎩: 새로운 유저시군요!\n		튜토리얼을 통해 교과목을 하나 이상 등록하시면 BHandy의 추가 기능이 활성화됩니다.";
			} else {
				$send['text'] = "🎩: 교과목을 하나 이상 등록하시면 BHandy의 추가 기능이 활성화됩니다.\n		튜토리얼을 통해 {$senderFullName}님의 교과목을 등록해볼까요?";
			}
			message($send);
			
			$send['title'] = $send['buttonsTitle'] = $send['payload'] = array("튜토리얼 시작하기");
			$imagePath = 'https://bhandy.kr/scheduler/univ/pnu/images/';
			$send['imageURL'] = array($imagePath.'tutorial.jpg');
			messageTemplateLeftSlideWithImage($send);
			/*
			$send['elementsTitle'] = "신규";
			$send['elementsButtonsTitle'] = array("튜토리얼 시작하기");
			messageTemplate($send);
			*/			
		}
		else if(isset($userInfo)) {
			$query = queryInsert('logging', 'START');
			$conn->query($query);
			
			$send['text'] = "🎩: {$senderFullName}님 반갑습니다.";
			message($send);
			
			$send['title'] = $send['buttonsTitle'] = $send['payload'] = array("등록된 과제∙휴강∙시험 정보 보기", "등록된 교과목 정보 보기", "교과목 등록하기");
			$imagePath = 'https://bhandy.kr/scheduler/univ/pnu/images/';
			$send['imageURL'] = array($imagePath.'information.jpg', $imagePath.'notepad.jpg', $imagePath.'register.jpg');
			messageTemplateLeftSlideWithImage($send);
			/*
			$send['elementsTitle'] = "기존";
			$send['elementsButtonsTitle'] = array("교과목 등록하기", "등록된 교과목 정보 보기", "등록된 과제∙휴강∙시험 정보 보기");
			messageTemplate($send);
			*/
		}
	}
	else if(preg_match("/^REGISTER(.*)/", $inProgress)) {
		// values for searching	
		$query = "SELECT * FROM logging WHERE userkey = '$senderID' ORDER BY inputTime DESC LIMIT 1";
		$sql4loggingSearch = $conn->query($query);
		while($row4loggingSearch = $sql4loggingSearch->fetch_assoc()) {
			$searchWord = $row4loggingSearch['searchWord'];
			$searchTitle = $row4loggingSearch['searchTitle'];
			$searchMajor = $row4loggingSearch['searchMajor'];
			$searchGrade = $row4loggingSearch['searchGrade'];
			$searchFields = $row4loggingSearch['searchFields'];
		}
		// 이전 검색 정보
		$query = "SELECT searchMajor FROM logging WHERE userkey='$senderID' ORDER BY inputTime DESC";
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
				if($course[strlen($course)-1] == "W" || $a[strlen($course)-1] == "S") {
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
					$send['text'] = "🎩: 교과목 등록을 시작합니다.\n	검색하고자하는 교과목명을 입력해주세요.";
					message($send);
				} else {
					$query = queryInsert('logging', 'REGISTER');
					$conn->query($query);
					
					if(!isset($userInfo)) {			
						$send['text'] = "🎩: 교과목 등록을 시작합니다.\n	과목 구분을 선택해 주세요.";
						message($send);
							
						$send['elementsTitle'] = "과목 구분";
						$send['elementsButtonsTitle'] = array_keys(array_flip($dbAllDivs));
						array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
						messageTemplate($send);
					}
					else if(isset($userInfo)) {
						$rgstedInfo = registedConditionSubject($userInfo);
						isset($rgstedInfo) ? $rgstedInfo = implode("\n", $rgstedInfo) : "";
			
						$send['text'] = "<교과목 등록 현황>\n" . $rgstedInfo . "\n\n총 " . count($userInfo) . "과목";
						message($send);
									
						$send['elementsTitle'] = "과목 구분";
						$send['elementsButtonsTitle'] = array_keys(array_flip($dbAllDivs));
						array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
						messageTemplate($send);
					}
				}
				$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
				$send['payload'] = $send['title'] = array('초기화면');
				messageQR($send);
			}
			else if(preg_match("/[1]$/", $inProgress) || preg_match("/[2]$/", $inProgress)) {
				$query = updateProcessing('insert');
				$conn->query($query);
				$query = queryInsert('logging', 'REGISTER_INSERT');
				$conn->query($query);
				
				$send['text'] = "🎩: 교과목명을 입력해주세요.";
				message($send);
				
				ReturningQR();
			}
			else if(preg_match("/[3]$/", $inProgress) || preg_match("/OPT$/", $inProgress)) {
				$query = "SELECT * FROM $course WHERE (title LIKE '%$searchWord') OR (title LIKE '%$searchWord%') OR (title LIKE '%$searchWord%')";
				$sql4courses = $conn->query($query);
				$checkOut = checkOverlap($sql4courses);
				
				if($checkOut['condition'] == TRUE) {
					if($checkOut['count'] == "single") {
						if($checkOut['overlap'] == TRUE) {
							$query = queryInsert('logging', 'REGISTER_INSERT');
							$conn->query($query);
							
							$send['text'] = "🎩: ".$checkOut['text'][1];
							message($send);
							
							ReturningQR();
						}
						else if($checkOut['overlap'] == FALSE) {
							$send['text'] = "🎩: ".$checkOut['text'];
							$send['payload'] = $send['title'] = array('마쟈요', '초기화면', '아니얌');
							messageQR($send);
							
							$checkOutInfo = $checkOut['dbInfo'];
							$query = "INSERT INTO logging (userkey, inProgress, searchWord, divs, fields, major, title, code, class, prof, department,
																				day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																					VALUE('$senderID', 'REGISTER_INSERT_OPT', '$searchWord', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
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
					if($checkOut['overcount'] == FALSE) {
						$query = queryInsert('logging', 'REGISTER_INSERT');
						$conn->query($query);
						
						$send['text'] = "🎩: 검색된 교과목이 너무 많습니다.\n	보다 더 상세하게 입력해주세요.";
						message($send);
						
						ReturningQR();					
					}
					else if(empty($checkOut['dbInfo']['title'])) {
						$query = queryInsert('logging', 'REGISTER_INSERT');
						$conn->query($query);
						
						$send['text'] = "🎩: 검색된 교과목이 없습니다.\n	다시 한번 상세히 입력해주세요.";
						message($send);
						
						ReturningQR();
					} else {
						ReturningError();
					}
				} else {
					ReturningError();
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
					$send['text'] = "🎩: 교과목 등록을 시작합니다.\n	과목 구분을 선택해 주세요.";
					message($send);
						
					$send['elementsTitle'] = "과목 구분";
					$send['elementsButtonsTitle'] = array_keys(array_flip($dbAllDivs));
					array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
					messageTemplate($send);
				}
				else if(isset($userInfo)) {
					$rgstedInfo = registedConditionSubject($userInfo);
					isset($rgstedInfo) ? $rgstedInfo = implode("\n", $rgstedInfo) : "";
		
					$send['text'] = "<교과목 등록 현황>\n" . $rgstedInfo . "\n\n총 " . count($userInfo) . "과목";
					message($send);
								
					$send['elementsTitle'] = "과목 구분";
					$send['elementsButtonsTitle'] = array_keys(array_flip($dbAllDivs));
					array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
					messageTemplate($send);
				}
				$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
				$send['payload'] = $send['title'] = array('초기화면');
				messageQR($send);
			}
			else if(preg_match("/[1]$/", $inProgress)) {
				$query = updateProcessing('generalSelc');
				$conn->query($query);
				$query = queryInsert('logging', 'REGISTER_GeneralSelc');
				$conn->query($query);
				
				$send['text'] = "🎩: 교과목명을 입력해주세요.";
				message($send);
				
				ReturningQR();
			}
			else if(preg_match("/[2]$/", $inProgress)) {
				$query = "SELECT * FROM $course WHERE divs='$selectedDiv' AND ((title LIKE '%$searchWord') OR (title LIKE '%$searchWord%') OR (title LIKE '%$searchWord%'))";
				$sql4courses = $conn->query($query);
				$checkOut = checkOverlap($sql4courses);
				
				if($checkOut['condition'] == TRUE) {
					if($checkOut['count'] == "single") {
						if($checkOut['overlap'] == TRUE) {
							$query = queryInsert('logging', 'REGISTER_GeneralSelc');
							$conn->query($query);
							
							$send['text'] = "🎩: ".$checkOut['text'][1];
							message($send);
							
							ReturningQR();
						}
						else if($checkOut['overlap'] == FALSE) {
							$send['text'] = "🎩: ".$checkOut['text'];
							$send['payload'] = $send['title'] = array('마쟈요', '초기화면', '아니얌');
							messageQR($send);
							
							$checkOutInfo = $checkOut['dbInfo'];
							$query = "INSERT INTO logging (userkey, inProgress, searchWord, divs, fields, major, title, code, class, prof, department,
																				day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																					VALUE('$senderID', 'REGISTER_GeneralSelc_OPT', '$searchWord', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
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
					if($checkOut['overcount'] == FALSE) {
						$query = queryInsert('logging', 'REGISTER_GeneralSelc');
						$conn->query($query);
						
						$send['text'] = "🎩: 검색된 교과목이 너무 많습니다.\n	보다 더 상세하게 입력해주세요.";
						message($send);
						
						ReturningQR();					
					}
					else if(empty($checkOut['dbInfo']['title'])) {
						$query = queryInsert('logging', 'REGISTER_GeneralSelc');
						$conn->query($query);
						
						$send['text'] = "🎩: 검색된 교과목이 없습니다.\n	다시 한번 상세히 입력해주세요.";
						message($send);
						
						ReturningQR();
					} else {
						ReturningError();
					}
				} else {
					ReturningError();
				}				
			}
		}
		else if(preg_match("/LIBERAL/", $inProgress) && $processingAllCount == 1 && $rgstLiberal == 1) {
			$selectedDiv = "교양";
			
			if(preg_match("/INSERT$/", $inProgress)) {
				// 초기화
				$query = resetProcessing();
				$conn->query($query);
				$query = queryInsert('logging', 'REGISTER');
				$conn->query($query);
				
				if(!isset($userInfo)) {			
					$send['text'] = "🎩: 교과목 등록을 시작합니다.\n	과목 구분을 선택해주세요.";
					message($send);
						
					$send['elementsTitle'] = "과목 구분";
					$send['elementsButtonsTitle'] = array_keys(array_flip($dbAllDivs));
					array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
					messageTemplate($send);
				}
				else if(isset($userInfo)) {
					$rgstedInfo = registedConditionSubject($userInfo);
					isset($rgstedInfo) ? $rgstedInfo = implode("\n", $rgstedInfo) : "";
		
					$send['text'] = "<교과목 등록 현황>\n" . $rgstedInfo . "\n\n총 " . count($userInfo) . "과목";
					message($send);
								
					$send['elementsTitle'] = "과목 구분";
					$send['elementsButtonsTitle'] = array_keys(array_flip($dbAllDivs));
					array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
					messageTemplate($send);
				}
				$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
				$send['payload'] = $send['title'] = array('초기화면');
				messageQR($send);
			}
			else if(preg_match("/[1]$/", $inProgress)) {
				$query = updateProcessing('liberal');
				$conn->query($query);
				$query = queryInsert('logging', 'REGISTER_LIBERAL');
				$conn->query($query);
				
				$send['text'] = "🎩: 세부 구분을 선택해주세요.";
				message($send);
				
				$send['elementsTitle'] = "세부 구분";
				$send['elementsButtonsTitle'] = array_keys(array_flip($dbAllFields));
				messageTemplate($send);
			
				ReturningQR();		
			}
			else if(preg_match("/[2]$/", $inProgress)) {
				$query = queryInsert('logging', 'REGISTER_LIBERAL_1', array('searchFields'=>$searchFields));
				$conn->query($query);
				
				$query = "SELECT title FROM $course WHERE divs='$selectedDiv' AND fields='$searchFields'";
				$sql4courses = $conn->query($query);
				while($row4courses = $sql4courses->fetch_assoc()) {
					$dbTitle[] = $row4courses['title'];
				}
				
				$send["text"] = "🎩: 교과목을 선택해 주세요.";
				message($send);
				
				$send['elementsTitle'] = "교과목";
				$send['elementsButtonsTitle'] = array_keys(array_flip($dbTitle));
				messageTemplate($send);
		
				ReturningQR();
			} else {
				ReturningError();
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
					$send['text'] = "🎩: 교과목 등록을 시작합니다.\n	과목 구분을 선택해 주세요.";
					message($send);
						
					$send['elementsTitle'] = "과목 구분";
					$send['elementsButtonsTitle'] = array_keys(array_flip($dbAllDivs));
					array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
					messageTemplate($send);
				}
				else if(isset($userInfo)) {
					$rgstedInfo = registedConditionSubject($userInfo);
					isset($rgstedInfo) ? $rgstedInfo = implode("\n", $rgstedInfo) : "";
		
					$send['text'] = "<교과목 등록 현황>\n" . $rgstedInfo . "\n\n총 " . count($userInfo) . "과목";
					message($send);
								
					$send['elementsTitle'] = "과목 구분";
					$send['elementsButtonsTitle'] = array_keys(array_flip($dbAllDivs));
					array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
					messageTemplate($send);
				}
				$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
				$send['payload'] = $send['title'] = array('초기화면');
				messageQR($send);
			}
			else if(preg_match("/[1]$/", $inProgress) || preg_match("/[2]$/", $inProgress) || preg_match("/[3]$/", $inProgress)) {
				if(preg_match("/MAJOR/", $inProgress)) {
					$query = updateProcessing('major');
					$conn->query($query);
					$query = queryInsert('logging', 'REGISTER_MAJOR');
					$conn->query($query);				
				}
				else if(preg_match("/MajorBASIC/", $inProgress)) {
					$query = updateProcessing('majorBasic');
					$conn->query($query);
					$query = queryInsert('logging', 'REGISTER_MajorBASIC');
					$conn->query($query);
				}
				else if(preg_match("/LiberalESSN/", $inProgress)) {
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
					$send['elementsButtonsTitle'] = array_keys(array_flip(array_unique($previousSearchMajor)));
					messageTemplate($send);		
				}
				
				ReturningQR();			
			}
			else if(preg_match("/[4]$/", $inProgress)) {
				$query = "SELECT * FROM $course WHERE divs='$selectedDiv' AND major='$searchMajor'";
				$sql4courses = $conn->query($query);
				while($row4courses = $sql4courses->fetch_assoc()) {
					$dbGrade[] = $row4courses['grade'] . "학년";
					$dbTitle[] = $row4courses['title'];
				}
				$dbTitle = array_keys(array_flip($dbTitle));
					
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
					
					$send['text'] = "🎩: 검색된 교과목이 너무 많습니다.\n	해당 교과목의 학년 구분을 선택해주세요.";
					message($send);
							
					$send['elementsTitle'] = "학년 구분";
					$send['elementsButtonsTitle'] = array_keys(array_flip($dbGrade));
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
			} else {
				ReturningError();			
			}
		}
	}
	else if(preg_match("/^READ(.*)/", $inProgress)) {
		// values for searching	
		$query = "SELECT * FROM loggingRead WHERE userkey = '$senderID' ORDER BY inputTime DESC LIMIT 1";
		$sql4loggingRead = $conn->query($query);
		while($row4loggingRead = $sql4loggingRead->fetch_assoc()) {
			$readType = $row4loggingRead['type'];
			$readTitle = $row4loggingRead['title'];
			$readContent = $row4loggingRead['content'];
			$readDate1 = $row4loggingRead['date1'];
			$readDate2 = $row4loggingRead['date2'];
			$readTime1 = $row4loggingRead['time1'];
			$readTime2 = $row4loggingRead['time2'];
		}

		if(preg_match("/SUBJECT/", $inProgress)) {
			$query = queryInsert('logging', 'READ_SUBJECT');
			$conn->query($query);
			
			$send['text'] = "<교과목 등록 현황>\n총 " . count($userInfo) . "과목 등록 완료";
			message($send);
		
			$rgstedInfoDetail = registedConditionSubjectDetail($userInfo);
			for($i=0; $i<count($rgstedInfoDetail['title']); $i++) {
				$titleName = $rgstedInfoDetail['titleName'][$i];
				$send['title'][] = $rgstedInfoDetail['title'][$i];
				$send['subtitle'][] = $rgstedInfoDetail['info'][$i];
				$send['payload'][] = array("READ-".$titleName, "REGISTER-".$titleName, "DELETE-".$titleName);
			}
			$send['buttonsTitle'] = array("등록된 과제∙휴강∙시험 정보 보기", "과제∙휴강∙시험 정보 등록하기", "교과목 삭제하기");
			messageTemplateLeftSlide($send);
			
			$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
			$send['payload'] = $send['title'] = array('시간표 보기', '초기화면');
			messageQR($send);				
		}
		else if(preg_match("/EVENT/", $inProgress)) {
			if((preg_match("/EVENT$/", $inProgressRead) && isset($readTitle)) || (preg_match("/WRITE(.*)[1]$/", $inProgressRead) && isset($readTitle))) {
				$query = queryInsert('logging', 'READ_SUBJECT');
				$conn->query($query);
				
				$send['text'] = "<교과목 등록 현황>\n총 " . count($userInfo) . "과목 등록 완료";
				message($send);
				
				$rgstedInfoDetail = registedConditionSubjectDetail($userInfo);
				for($i=0; $i<count($rgstedInfoDetail['title']); $i++) {
					$titleName = $rgstedInfoDetail['titleName'][$i];
					$send['title'][] = $rgstedInfoDetail['title'][$i];
					$send['subtitle'][] = $rgstedInfoDetail['info'][$i];
					$send['payload'][] = array("READ-".$titleName, "REGISTER-".$titleName, "DELETE-".$titleName);
				}
				$send['buttonsTitle'] = array("등록된 과제∙휴강∙시험 정보 보기", "과제∙휴강∙시험 정보 등록하기", "교과목 삭제하기");
				messageTemplateLeftSlide($send);
				
				$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
				$send['payload'] = $send['title'] = array('시간표 보기', '초기화면');
				messageQR($send);
			}
			else if((preg_match("/EVENT$/", $inProgressRead) && !isset($readTitle)) || (preg_match("/INFO$/", $inProgressRead)) || (preg_match("/WRITE(.*)[1]$/", $inProgressRead) && !isset($readTitle)) || preg_match("/DELETE$/", $inProgressRead) || preg_match("/OTHER$/", $inProgressRead)) {
				$query = queryInsert('logging', 'READ_EVENT');
				$conn->query($query);
				$query = queryInsert('loggingRead', 'READ_EVENT');
				$conn->query($query);
				
				if(count($eventInfo) == 0) {
					$send['text'] = "🎩: {$senderFullName}님은 등록한 항목이 없습니다.	\n\n하나 이상의 항목을 등록하시면\n수강 중인 교과목의 과제∙휴강∙시험 정보를 열람할 수 있습니다.\n\n새로운 내용을 등록하거나, 그렇지 않으면 초기화면으로 돌아가주세요.";
					$send['payload'] = $send['title'] = array('새로 등록하기', '초기화면');
				} else {
					for($i=0; $i<count($eventInfo); $i++) {
						$eventInfoTypes[] = $eventInfo[$i]['type'];
					}
					$countTypes = array_count_values($eventInfoTypes);
					
					if(empty($countTypes['assignment']) || empty($countTypes['cancel']) || empty($countTypes['exam'])) {
						if(empty($countTypes['assignment'])) {
							$countTypes['assignment'] = 0;
						}
						if(empty($countTypes['cancel'])) {
							$countTypes['cancel'] = 0;
						}
						if(empty($countTypes['exam'])) {
							$countTypes['exam'] = 0;
						}
					}
						
					$send['text'] = "<등록된 과제∙휴강∙시험 현황>\n ∙과제: " . $countTypes['assignment'] . "개\n ∙휴강: " . $countTypes['cancel'] . "개\n ∙시험: " . $countTypes['exam'] . "개";
					message($send);
					
					$typeArr = array("assignment", "cancel", "exam");
					for($i=0; $i<count($typeArr); $i++) {
						$readEventInfo = readEventInfo($eventInfo, $typeArr[$i]);
						if($readEventInfo) {
							$send['title'] = $readEventInfo['title'];
							$send['subtitle'] = $readEventInfo['info'];
							$send['buttonsTitle'] = array("다른 수강생들이 등록한 정보 보기");
							$send['payload'] = $readEventInfo['payload'];
							messageTemplateLeftSlide($send);							
						}
					}
	
					$send['text'] = "🎩: 다른 수강생들이 등록한 정보를 열람하거나,\n새로운 내용을 입력 또는 수정할 수 있습니다.";
					$send['payload'] = $send['title'] = array('새로 등록하기', '등록된 정보 수정하기', '초기화면');
				}
				messageQR($send);
			}
			else if(preg_match("/WRITE(.*)[2]$/", $inProgressRead)) {
				if($readTitle) {
					$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_1', array('title'=>$readTitle));
					$send['text'] = "🎩: <" . $readTitle . ">에 등록할 항목을 선택해주세요.";					
				} else {
					$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_1');
					$send['text'] = "🎩: 등록할 항목을 선택해주세요.";
				}
				$conn->query($query);

				$send['payload'] = $send['title'] = array('과제', '휴강', '시험', '이전으로', '초기화면');
				messageQR($send);
			}
			else if(preg_match("/WRITE(.*)[3-4]$/", $inProgressRead)) {
				$query = "SELECT * FROM user WHERE userkey='$senderID'";
				$sql4user = $conn->query($query);
				while($row4user = $sql4user->fetch_assoc()) {
					$userInfoTitles[] = $row4user['title'];
				}
				
				if($readType == "assignment") {
					$readTypeKR = "과제";
					$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_2', array('type'=>'assignment'));
				}
				else if($readType == "cancel") {
					$readTypeKR = "휴강";
					$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_2', array('type'=>'cancel'));
				}
				else if($readType == "exam") {
					$readTypeKR = "시험";
					$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_2', array('type'=>'exam'));
				}
				$conn->query($query);
				
				$send['text'] = "🎩: <". $readTypeKR . ">을(를) 등록할 교과목을 선택해주세요.";
				$send['payload'] = $send['title'] = $userInfoTitles;
				array_push($send['title'], "이전으로", "초기화면");
				array_push($send['payload'], "이전으로", "초기화면");
				messageQR($send);
			}
			else if(preg_match("/WRITE(.*)FIN$/", $inProgressRead)) {
				if($readType == "assignment") {
					$send['text'] = "🎩: <" . $readTitle .">에 등록할 과제 내용를 상세히 입력해주세요.";
					$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_3', array('type'=>$readType, 'title'=>$readTitle));
				}
				else 	if($readType == "cancel") {
					$send['text'] = "🎩: <" . $readTitle .">에 등록할 휴강 날짜를 <숫자 4자리>로 입력해주세요.\n예) 10월 16일 -> 1016\n\n휴강이 단일이 아닌 복수일(기간)이라면,\n첫날과 마지막날을 슬래쉬(/)를 포함한 <숫자 8자리>로 입력해주세요.\n예) 10월16일부터 10월 23일 -> 1016/1023";			
					$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_4', array('type'=>$readType, 'title'=>$readTitle));
				}
				else 	if($readType == "exam") {
					$send['text'] = "🎩: <" . $readTitle .">에 등록할 시험 날짜와 시간을\n슬래쉬(/)를 포함한 <숫자 8자리>로 입력해주세요.\n예) 10월 16일 오후 1시반 -> 1016/1330";
					$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_4', array('type'=>$readType, 'title'=>$readTitle));
				}
				message($send);
				$conn->query($query);
				
				ReturningQR();
			}
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

		if($payloadQR == "YES") {
			$query = queryInsert('logging', 'READ_EVENT');
			$conn->query($query);
			$query = queryInsert('loggingRead', 'READ_EVENT');
			$conn->query($query);
			
			$typeArr = array("assignment", "cancel", "exam");
			for($i=0; $i<count($typeArr); $i++) {
				$readEventInfo = readEventInfo($eventInfo, $typeArr[$i]);
				if($readEventInfo) {
					$send['title'] = $readEventInfo['title'];
					$send['subtitle'] = $readEventInfo['info'];
					$send['buttonsTitle'] = array("다른 수강생들이 등록한 정보 보기");
					$send['payload'] = $readEventInfo['payload'];
					messageTemplateLeftSlide($send);							
				}
			}

			$send['text'] = "🎩: {$senderFullName}님 반갑습니다.\n\n다른 수강생들이 등록한 정보를 열람하거나,\n새로운 내용을 입력 또는 수정할 수 있습니다.";
			$send['payload'] = $send['title'] = array('새로 등록하기', '등록된 정보 수정하기', '초기화면');
			messageQR($send);				
		}
		else if($payloadQR == "NO") {
			$send['text'] = "🎩: {$senderFullName}님 반갑습니다.";
			message($send);
			
			$send['title'] = $send['buttonsTitle'] = $send['payload'] = array("등록된 과제∙휴강∙시험 정보 보기", "등록된 교과목 정보 보기", "교과목 등록하기");
			$imagePath = 'https://bhandy.kr/scheduler/univ/pnu/images/';
			$send['imageURL'] = array($imagePath.'information.jpg', $imagePath.'notepad.jpg', $imagePath.'register.jpg');
			messageTemplateLeftSlideWithImage($send);
			/*
			$send['elementsTitle'] = "기존";
			$send['elementsButtonsTitle'] = array("교과목 등록하기", "등록된 교과목 정보 보기", "등록된 과제∙휴강∙시험 정보 보기");
			messageTemplate($send);
			*/			
		} else {
			if(preg_match("/^교과목(.*)등록/", $payload) || preg_match("/^교과목(.*)추가(.*)등록/", $payloadQR)) {
				if($course[strlen($course)-1] == "W" || $a[strlen($course)-1] == "S") {
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
					$send['text'] = "🎩: 교과목 등록을 시작합니다.\n	검색하고자하는 교과목명을 입력해주세요.";
					message($send);
				} else {
					$query = queryInsert('logging', 'REGISTER');
					$conn->query($query);
					
					if(!isset($userInfo)) {
						$send['text'] = "🎩: 교과목 등록을 시작합니다.\n	과목 구분을 선택해 주세요.";
						message($send);
							
						$send['elementsTitle'] = "과목 구분";
						$send['elementsButtonsTitle'] = array_keys(array_flip($dbAllDivs));
						array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
						messageTemplate($send);
					}
					else if(isset($userInfo)) {
						$rgstedInfo = registedConditionSubject($userInfo);
						isset($rgstedInfo) ? $rgstedInfo = implode("\n", $rgstedInfo) : "";
			
						$send['text'] = "<교과목 등록 현황>\n" . $rgstedInfo . "\n\n총 " . count($userInfo) . "과목";
						message($send);
									
						$send['elementsTitle'] = "과목 구분";
						$send['elementsButtonsTitle'] = array_keys(array_flip($dbAllDivs));
						array_unshift($send['elementsButtonsTitle'], "교과목명 입력");
						messageTemplate($send);
					}
				}
				$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
				$send['payload'] = $send['title'] = array('초기화면');
				messageQR($send);
			}
			else if(preg_match("/^등록된(.*)정보(.*)보기/", $payload)) {
				if(preg_match("/교과목/", $payload)) {
					$query = queryInsert('logging', 'READ_SUBJECT');
					$conn->query($query);
					
					$send['text'] = "<교과목 등록 현황>\n총 " . count($userInfo) . "과목 등록 완료";
					message($send);
					
					$rgstedInfoDetail = registedConditionSubjectDetail($userInfo);
					for($i=0; $i<count($rgstedInfoDetail['title']); $i++) {
						$titleName = $rgstedInfoDetail['titleName'][$i];
						$send['title'][] = $rgstedInfoDetail['title'][$i];
						$send['subtitle'][] = $rgstedInfoDetail['info'][$i];
						$send['payload'][] = array("READ-".$titleName, "REGISTER-".$titleName, "DELETE-".$titleName);
					}
					$send['buttonsTitle'] = array("등록된 과제∙휴강∙시험 정보 보기", "과제∙휴강∙시험 정보 등록하기", "교과목 삭제하기");
					messageTemplateLeftSlide($send);
					
					$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
					$send['payload'] = $send['title'] = array('시간표 보기', '초기화면');
					messageQR($send);
				}
				else if(preg_match("/과제(.*)휴강(.*)시험/", $payload)) {
					$query = queryInsert('logging', 'READ_EVENT');
					$conn->query($query);
					$query = queryInsert('loggingRead', 'READ_EVENT');
					$conn->query($query);
					
					if(count($eventInfo) == 0) {
						$send['text'] = "🎩: {$senderFullName}님은 등록한 항목이 없습니다.\n\n하나 이상의 항목을 작성하시면\n수강 중인 교과목의 과제∙휴강∙시험 정보를 열람할 수 있습니다.\n\n새로운 내용을 입력하거나, 그렇지 않으면 초기화면으로 돌아가주세요.";
						$send['payload'] = $send['title'] = array('새로 등록하기', '초기화면');
					} else {
						for($i=0; $i<count($eventInfo); $i++) {
							$eventInfoTypes[] = $eventInfo[$i]['type'];
						}
						$countTypes = array_count_values($eventInfoTypes);
						
						if(empty($countTypes['assignment']) || empty($countTypes['cancel']) || empty($countTypes['exam'])) {
							if(empty($countTypes['assignment'])) {
								$countTypes['assignment'] = 0;
							}
							if(empty($countTypes['cancel'])) {
								$countTypes['cancel'] = 0;
							}
							if(empty($countTypes['exam'])) {
								$countTypes['exam'] = 0;
							}
						}
						
						$send['text'] = "<등록된 과제∙휴강∙시험 현황>\n ∙과제: " . $countTypes['assignment'] . "개\n ∙휴강: " . $countTypes['cancel'] . "개\n ∙시험: " . $countTypes['exam'] . "개";
						message($send);
						
						$typeArr = array("assignment", "cancel", "exam");
						for($i=0; $i<count($typeArr); $i++) {
							$readEventInfo = readEventInfo($eventInfo, $typeArr[$i]);
							if($readEventInfo) {
								$send['title'] = $readEventInfo['title'];
								$send['subtitle'] = $readEventInfo['info'];
								$send['buttonsTitle'] = array("다른 수강생들이 등록한 정보 보기");
								$send['payload'] = $readEventInfo['payload'];
								messageTemplateLeftSlide($send);							
							}
						}
		
						$send['text'] = "🎩: 다른 수강생들이 등록한 정보를 열람하거나,\n	새로운 내용을 입력 또는 수정할 수 있습니다.";
						$send['payload'] = $send['title'] = array('새로 등록하기', '등록된 정보 수정하기', '초기화면');
					}
					messageQR($send);
				}
			} else {
				WrongAccessQR();
			}
		}
	}
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////// REGISTER //////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	else if(preg_match("/^REGISTER/", $inProgress)) {
		// values for searching	
		$query = "SELECT * FROM logging WHERE userkey = '$senderID' ORDER BY inputTime DESC LIMIT 1";
		$sql4loggingSearch = $conn->query($query);
		while($row4loggingSearch = $sql4loggingSearch->fetch_assoc()) {
			$searchWord = $row4loggingSearch['searchWord'];
			$searchTitle = $row4loggingSearch['searchTitle'];
			$searchMajor = $row4loggingSearch['searchMajor'];
			$searchGrade = $row4loggingSearch['searchGrade'];
			$searchFields = $row4loggingSearch['searchFields'];
		}
		
		// 이전 검색 정보
		$query = "SELECT searchMajor FROM logging WHERE userkey='$senderID' ORDER BY inputTime DESC";
		$sql4loggingSearchMajor = $conn->query($query);
		while($row4loggingSearchMajor = $sql4loggingSearchMajor->fetch_assoc()) {
			if(!empty($row4loggingSearchMajor["searchMajor"]) && $row4loggingSearchMajor["searchMajor"] != "") {
				$previousSearchMajor[] = $row4loggingSearchMajor["searchMajor"];
			}
		}
		
		if(preg_match("/^REGISTER$/", $inProgress)) {
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
					$send['elementsButtonsTitle'] = array_keys(array_flip(array_unique($previousSearchMajor)));
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
				$send['elementsButtonsTitle'] = array_keys(array_flip($dbAllFields));
				messageTemplate($send);
			
				ReturningQR();
			} else {
				WrongAccessQR();
			}
		}
		else if(preg_match("/INSERT/", $inProgress) && $processingAllCount == 1 && $rgstInsert == 1) {
			if(preg_match("/INSERT$/", $inProgress) && $messageText) {
				$searchWord = $messageText;
		
				$query = "SELECT * FROM $course WHERE (title LIKE '%$searchWord') OR (title LIKE '$searchWord%') OR (title LIKE '%$searchWord%')";
				$sql4courses = $conn->query($query);
				$checkOut = checkOverlap($sql4courses);
				
				if($checkOut['condition'] == TRUE) {
					if($checkOut['count'] == "single") {
						if($checkOut['overlap'] == TRUE) {
							$query = queryInsert('logging', 'REGISTER_INSERT');
							$conn->query($query);
							$send['text'] = "🎩: ".$checkOut['text'][1];
							message($send);
							
							ReturningQR();
						}
						else if($checkOut['overlap'] == FALSE) {
							$send['text'] = "🎩: ".$checkOut['text'];
							$send['payload'] = $send['title'] = array('마쟈요', '초기화면', '아니얌');
							messageQR($send);
							
							$checkOutInfo = $checkOut['dbInfo'];
							$query = "INSERT INTO logging (userkey, inProgress, searchWord, divs, fields, major, title, code, class, prof, department,
																				day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																					VALUE('$senderID', 'REGISTER_INSERT_OPT', '$searchWord', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
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
					if($checkOut['overcount'] == FALSE) {
						$query = queryInsert('logging', 'REGISTER_INSERT');
						$conn->query($query);
						
						$send['text'] = "🎩: 검색된 교과목이 너무 많습니다.\n\n보다 더 상세하게 입력해주세요.";
						message($send);
						
						ReturningQR();					
					}
					else if(empty($checkOut['dbInfo']['title'])) {
						$query = queryInsert('logging', 'REGISTER_INSERT');
						$conn->query($query);
						
						$send['text'] = "🎩: 검색된 교과목이 없습니다.\n\n다시 한번 상세히 입력해주세요.";
						message($send);
						
						ReturningQR();
					} else {
						ReturningError();
					}
				} else {
					ReturningError();
				} 
			}
			else if(preg_match("/[1]$/", $inProgress) && $payload) {
				$searchTitle = $payload;
				
				$query = "SELECT * FROM $course WHERE title='$searchTitle'";
				$sql4courses = $conn->query($query);
				$checkOut = checkOverlap($sql4courses);
				
				if($checkOut['condition'] == TRUE) {
					if($checkOut['count'] == "single") {
						if($checkOut['overlap'] == TRUE) {
							$query = queryInsert('logging', 'REGISTER_INSERT_1', array('searchWord'=>$searchWord));
							$conn->query($query);
							
							$send['text'] = "🎩: ".$checkOut['text'][0];
							message($send);
						
							$query = "SELECT * FROM $course WHERE (title LIKE '%$searchWord') OR (title LIKE '$searchWord%') OR (title LIKE '%$searchWord%')";
							$sql4coursesReturn = $conn->query($query);
							while($row4coursesReturn = $sql4coursesReturn->fetch_assoc()) {
								$dbTitleReturn[] = $row4coursesReturn['title'];
							}
							
							$send['elementsTitle'] = "교과목";
							$send['elementsButtonsTitle'] = 	array_keys(array_flip($dbTitleReturn));
							messageTemplate($send);
							
							ReturningQR();
						}
						else if($checkOut['overlap'] == FALSE) {
							$send['text'] = "🎩: ".$checkOut['text'];
							$send['payload'] = $send['title'] = array('마쟈요', '초기화면', '아니얌');
							messageQR($send);
							
							$checkOutInfo = $checkOut['dbInfo'];
							$query = "INSERT INTO logging (userkey, inProgress, searchWord, searchTitle, divs, fields, major, title, code, class, prof, department,
																				day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																					VALUE('$senderID', 'REGISTER_INSERT_OPT', '$searchWord', '$searchTitle', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
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
						$query = queryInsert('logging', 'REGISTER_INSERT_3', array('searchWord'=>$searchWord, 'searchTitle'=>$searchTitle));
						$conn->query($query);
						
						$send['elementsTitle'] = "교과목";
						$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
						messageTemplate($send);
							
						ReturningQR();								
					}
				} 
				else if($checkOut['condition'] == FALSE) {
					if($checkOut['overcount'] == FALSE) {
						$query = queryInsert('logging', 'REGISTER_INSERT_2', array('searchWord'=>$searchWord, 'searchTitle'=>$searchTitle));
						$conn->query($query);
						
						$send['text'] = "🎩: 검색된 교과목이 너무 많습니다.\n\n해당 교과목의 학과 구분을 선택해주세요.";
						message($send);
						
						$send['elementsTitle'] = "학과 구분";
						$send['elementsButtonsTitle'] = array_keys(array_flip($dbMajor));
						messageTemplate($send);
						
						ReturningQR();		
					}
					else if(!in_array($searchTitle, $dbAllTitle)) {
						$send['text'] = "🎩: 올바른 교과목명이 아닌 것 같아요.";
						message($send);
						ReturningQR();
					} else {
						ReturningError();
					}
				} else {
					ReturningError();
				}			
			}
			else if(preg_match("/[2]$/", $inProgress) && $payload) {
				$searchMajor = $payload;
				
				$query = "SELECT * FROM $course WHERE major='$searchMajor' AND title='$searchTitle'";
				$sql4courses = $conn->query($query);
				$checkOut = checkOverlap($sql4courses);
				
				if($checkOut['condition'] == TRUE) {
					if($checkOut['count'] == "single") {
						if($checkOut['overlap'] == TRUE) {
							$query = queryInsert('logging', 'REGISTER_INSERT_1', array('searchWord'=>$searchWord));
							$conn->query($query);
							
							$send['text'] = "🎩: ".$checkOut['text'][0];
							message($send);
						
							$query = "SELECT * FROM $course WHERE (title LIKE '%$searchWord') OR (title LIKE '$searchWord%') OR (title LIKE '%$searchWord%')";
							$sql4coursesReturn = $conn->query($query);
							while($row4coursesReturn = $sql4coursesReturn->fetch_assoc()) {
								$dbTitleReturn[] = $row4coursesReturn['title'];
							}
							
							$send['elementsTitle'] = "교과목";
							$send['elementsButtonsTitle'] = 	array_keys(array_flip($dbTitleReturn));
							messageTemplate($send);
							
							ReturningQR();
						}
						else if($checkOut['overlap'] == FALSE) {
							$send['text'] = "🎩: ".$checkOut['text'];
							$send['payload'] = $send['title'] = array('마쟈요', '초기화면', '아니얌');
							messageQR($send);
							
							$checkOutInfo = $checkOut['dbInfo'];
							$query = "INSERT INTO logging (userkey, inProgress, searchWord, searchMajor, searchTitle,divs, fields, major, title, code, class, prof, department,
																				day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																					VALUE('$senderID', 'REGISTER_INSERT_OPT', '$searchWord', '$searchMajor', '$searchTitle', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
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
					if(!in_array($searchMajor, $dbAllMajor)) {
						$send['text'] = "🎩: 올바른 학과명이 아닌 것 같아요.";
						message($send);
						ReturningQR();
					} else {
						$send['text'] = "🎩: ".$checkOut['text'] . "\n	오류가 발생했습니다.";
						meesage($send);
						ReturningQR();
					}
				} else {
					ReturningError();
				}			
			}
			else if(preg_match("/[3]$/", $inProgress) && $payload) {
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
					// 교수명 분류
					if(strpos($payloadInfo, "교수님") !== FALSE) {
						$payloadInfoProf = str_replace("교수님", "", $payloadInfo);
					}
					
					// 시간 분류
					else if(strpos($payloadInfo, "-") !== FALSE) {
						$payloadInfo = explode("-", $payloadInfo);
						$payloadInfoTime1 = $payloadInfo[1];
						$payloadInfoDay = $payloadInfo[0];
						
						// 시간 분류 + Day2 없음
						if(strpos($payloadInfoDay, ",") !== FALSE) {
							$payloadInfoDay = explode(",",$payloadInfoDay);
							$payloadInfoDay1 = $payloadInfoDay[0];
							$payloadInfoDay2 = $payloadInfoDay[1];
						}
					}
					
					if(empty($searchMajor)) {
						$query = "SELECT * FROM $course WHERE title='$payloadTitle' AND 
																		(
																			class='$payloadInfo'
																			OR department='$payloadInfo'
																			OR prof='$payloadInfoProf'
																			OR day1='$payloadInfoDay1'
																			OR day2='$payloadInfoDay2'
																			OR time1='$payloadInfoTime1'
																		)";
						$sql4courses = $conn->query($query);
					}
					else if(!empty($searchMajor)) {
						$query = "SELECT * FROM $course WHERE (title='$payloadTitle' AND major='$searchMajor') AND
																	(
																		class='$payloadInfo'
																		OR department='$payloadInfo'
																		OR prof='$payloadInfoProf'
																		OR day1='$payloadInfoDay1'
																		OR day2='$payloadInfoDay2'
																		OR time1='$payloadInfoTime1'
																	)";
						$sql4courses = $conn->query($query);
					}
				} else {
					$query = "SELECT * FROM $course WHERE title='$payload'";
					$sql4courses = $conn->query($query);
				}
				$checkOut = checkOverlap($sql4courses);
				//$checkOut = checkOverlapReturn($sql4courses);
				if($checkOut['condition'] == TRUE) {
					if($checkOut['overlap'] == TRUE) {
						if($searchTitle && $searchMajor) {
							$query = queryInsert('logging', 'REGISTER_INSERT_3', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor, 'searchTitle'=>$searchTitle));
						}
						else if(!$searchTitle && $searchMajor) {
							$query = queryInsert('logging', 'REGISTER_INSERT_3', array('searchWord'=>$searchWord, 'searchMajor'=>$searchMajor));
						} 
						else if($searchTitle && !$searchMajor) {
							$query = queryInsert('logging', 'REGISTER_INSERT_3', array('searchWord'=>$searchWord, 'searchTitle'=>$searchTitle));
						} else {
							$query = queryInsert('logging', 'REGISTER_INSERT_3', array('searchWord'=>$searchWord));
						}
						$conn->query($query);
						
						$send['text'] = "🎩: ".$checkOut['text'][0];
						message($send);
						
						$send['elementsTitle'] = "교과목";
						$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
						messageTemplate($send);
						
						ReturningQR();
					}
					else if($checkOut['overlap'] == FALSE) {
						$send['text'] = "🎩: ".$checkOut['text'];
						$send['payload'] = $send['title'] = array('마쟈요', '초기화면', '아니얌');
						messageQR($send);
						
						$checkOutInfo = $checkOut['dbInfo'];
						$query = "INSERT INTO logging (userkey, inProgress, searchWord, searchMajor, searchTitle, divs, fields, major, title, code, class, prof, department,
																			day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																				VALUE('$senderID', 'REGISTER_INSERT_OPT', '$searchWord', '$searchMajor', '$searchTitle', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
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
					ReturningError();
				} else {
					ReturningError();
				}			
			}
			else if(preg_match("/OPT$/", $inProgress) && $payloadQR) {
				if($payloadQR == "마쟈요") {
					$optTitle = optTitle();
					
					$query = queryInsert('logging', 'START');
					$conn->query($query);			
								
					$send['text'] = "🎩: ".$optTitle;
					$send['payload'] = $send['title'] = array("교과목 추가 등록", "시간표 보기", "초기화면");
					messageQR($send);
				}
				else if($payloadQR == "아니얌") {
					$query = "SELECT * FROM $course WHERE (title LIKE '%$searchWord') OR (title LIKE '$searchWord%') OR (title LIKE '%$searchWord%')";
					$sql4courses = $conn->query($query);
					$checkOut = checkOverlap($sql4courses);
					
					if($checkOut['condition'] == TRUE) {
						if(count($checkOut['dbInfo']) > 1 && count($checkOut['dbInfo']) < 31) {
							$query = queryInsert('logging', 'REGISTER_INSERT_3', array('searchWord'=>$searchWord));
							$conn->query($query);
							
							$send['elementsTitle'] = "교과목";
							$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
							messageTemplate($send);
								
							ReturningQR();
						} else {
							$query = queryInsert('logging', 'REGISTER_INSERT');
							$conn->query($query);
							
							$send['text'] = "🎩: 교과목명을 입력해주세요.";
							message($send);
				
							ReturningQR();
						}
					}
					else if($checkOut['condition'] == FALSE) {
						ReturningError();
					}
				}			
			} else {
				// 초기화
				$query = resetProcessing();
				$conn->query($query);
				
				$send['text'] = "🎩: 오류가 발생했습니다.\n	ERROR : rgstInsert_ALL";
				$send['payload'] = $send['title'] = array('초기화면');
				messageQR($send);
			}
		}
		else if(preg_match("/GeneralSelc/", $inProgress) && $processingAllCount == 1 && $rgstGeneralSelc == 1) {
			$selectedDiv = "일반선택";
			if(preg_match("/GeneralSelc$/", $inProgress) && $messageText) {
				$searchWord = $messageText;
			
				$query = "SELECT * FROM $course WHERE divs='$selectedDiv' AND ((title LIKE '%$searchWord') OR (title LIKE '$searchWord%') OR (title LIKE '%$searchWord%'))";
				$sql4courses = $conn->query($query);
				$checkOut = checkOverlap($sql4courses);
				
				if($checkOut['condition'] == TRUE) {
					if($checkOut['count'] == "single") {
						if($checkOut['overlap'] == TRUE) {
							$query = queryInsert('logging', 'REGISTER_GeneralSelc');
							$conn->query($query);
							
							$send['text'] = "🎩: ".$checkOut['text'][1];
							message($send);
							
							ReturningQR();
						}
						else if($checkOut['overlap'] == FALSE) {
							$send['text'] = "🎩: ".$checkOut['text'];
							$send['payload'] = $send['title'] = array('마쟈요', '초기화면', '아니얌');
							messageQR($send);
							
							$checkOutInfo = $checkOut['dbInfo'];
							$query = "INSERT INTO logging (userkey, inProgress, searchWord, divs, fields, major, title, code, class, prof, department,
																				day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																					VALUE('$senderID', 'REGISTER_GeneralSelc_OPT', '$searchWord', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
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
					if($checkOut['overcount'] == FALSE) {
						$query = queryInsert('logging', 'REGISTER_GeneralSelc');
						$conn->query($query);
						
						$send['text'] = "🎩: 검색된 교과목이 너무 많습니다.\n\n보다 더 상세하게 입력해주세요.";
						message($send);
						
						ReturningQR();					
					}
					else if(empty($checkOut['dbInfo']['title'])) {
						$query = queryInsert('logging', 'REGISTER_GeneralSelc');
						$conn->query($query);
						
						$send['text'] = "🎩: 검색된 교과목이 없습니다.\n\n다시 한번 상세히 입력해주세요.";
						message($send);
						
						ReturningQR();
					} else {
						ReturningError();
					}
				} else {
					ReturningError();
				} 
			}
			else if(preg_match("/[1]$/", $inProgress) && $payload) {
				$searchTitle = $payload;
				
				$query = "SELECT * FROM $course WHERE divs='$selectedDiv' AND title='$searchTitle'";
				$sql4courses = $conn->query($query);
				$checkOut = checkOverlap($sql4courses);
				
				if($checkOut['condition'] == TRUE) {
					if($checkOut['count'] == "single") {
						if($checkOut['overlap'] == TRUE) {
							$query = queryInsert('logging', 'REGISTER_GeneralSelc_1', array('searchWord'=>$searchWord));
							$conn->query($query);
							
							$send['text'] = "🎩: ".$checkOut['text'][0];
							message($send);
						
							$query = "SELECT * FROM $course WHERE divs='$selectedDiv' AND ((title LIKE '%$searchWord') OR (title LIKE '$searchWord%') OR (title LIKE '%$searchWord%'))";
							$sql4coursesReturn = $conn->query($query);
							while($row4coursesReturn = $sql4coursesReturn->fetch_assoc()) {
								$dbTitleReturn[] = $row4coursesReturn['title'];
							}
							
							$send['elementsTitle'] = "교과목";
							$send['elementsButtonsTitle'] = 	array_keys(array_flip($dbTitleReturn));
							messageTemplate($send);
							
							ReturningQR();
						}
						else if($checkOut['overlap'] == FALSE) {
							$send['text'] = "🎩: ".$checkOut['text'];
							$send['payload'] = $send['title'] = array('마쟈요', '초기화면', '아니얌');
							messageQR($send);
							
							$checkOutInfo = $checkOut['dbInfo'];
							$query = "INSERT INTO logging (userkey, inProgress, searchWord, searchTitle, divs, fields, major, title, code, class, prof, department,
																				day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																					VALUE('$senderID', 'REGISTER_GeneralSelc_OPT', '$searchWord', '$searchTitle', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
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
					else if(!in_array($searchTitle, $dbAllTitle)) {
						$send['text'] = "🎩: 올바른 교과목명이 아닌 것 같아요.";
						message($send);
						ReturningQR();
					} else {
						ReturningError();
					}
				} else {
					ReturningError();
				}			
			}
			else if(preg_match("/[2]$/", $inProgress) && $payload) {
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
					// 교수명 분류
					if(strpos($payloadInfo, "교수님") !== FALSE) {
						$payloadInfoProf = str_replace("교수님", "", $payloadInfo);
					}
					
					// 시간 분류
					else if(strpos($payloadInfo, "-") !== FALSE) {
						$payloadInfo = explode("-", $payloadInfo);
						$payloadInfoTime1 = $payloadInfo[1];
						$payloadInfoDay = $payloadInfo[0];
						
						// 시간 분류 + Day2 없음
						if(strpos($payloadInfoDay, ",") !== FALSE) {
							$payloadInfoDay = explode(",",$payloadInfoDay);
							$payloadInfoDay1 = $payloadInfoDay[0];
							$payloadInfoDay2 = $payloadInfoDay[1];
						}
					}
				}
				$query = "SELECT * FROM $course WHERE (divs='$selectedDiv' AND title='$payloadTitle') AND 
																(
																	class='$payloadInfo'
																	OR department='$payloadInfo'
																	OR prof='$payloadInfoProf'
																	OR day1='$payloadInfoDay1'
																	OR day2='$payloadInfoDay2'
																	OR time1='$payloadInfoTime1'
																)";
				$sql4courses = $conn->query($query);
				$checkOut = checkOverlapReturn($sql4courses);
				if($checkOut['condition'] == TRUE) {
					if($checkOut['overlap'] == TRUE) {
						$query = queryInsert('logging', 'REGISTER_GeneralSelc_2', array('searchWord'=>$searchWord, 'searchTitle'=>$searchTitle));
						$conn->query($query);
						
						$send['text'] = "🎩: ".$checkOut['text'][0];
						message($send);
						
						$send['elementsTitle'] = "교과목";
						$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
						messageTemplate($send);
						
						ReturningQR();
					}
					else if($checkOut['overlap'] == FALSE) {
						$send['text'] = "🎩: ".$checkOut['text'];
						$send['payload'] = $send['title'] = array('마쟈요', '초기화면', '아니얌');
						messageQR($send);
						
						$checkOutInfo = $checkOut['dbInfo'];
						$query = "INSERT INTO logging (userkey, inProgress, searchWord, searchTitle, divs, fields, major, title, code, class, prof, department,
																			day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																				VALUE('$senderID', 'REGISTER_GeneralSelc_OPT', '$searchWord', '$searchTitle', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
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
					ReturningError();
				} else {
					ReturningError();
				}				
			}
			else if(preg_match("/OPT$/", $inProgress) && $payloadQR) {
				if($payloadQR == "마쟈요") {
					$optTitle = optTitle();
					
					$query = queryInsert('logging', 'START');
					$conn->query($query);			
								
					$send['text'] = "🎩: ".$optTitle;
					$send['payload'] = $send['title'] = array("교과목 추가 등록", "시간표 보기", "초기화면");
					messageQR($send);
				}
				else if($payloadQR == "아니얌") {
					$query = "SELECT * FROM $course WHERE divs='$selectedDivs' AND ((title LIKE '%$searchWord') OR (title LIKE '$searchWord%') OR (title LIKE '%$searchWord%'))";
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
						ReturningError();
					}
				}			
			} else {
				// 초기화
				$query = resetProcessing();
				$conn->query($query);
				
				$send['text'] = "🎩: 오류가 발생했습니다.\n	ERROR : rgstInsert_ALL";
				$send['payload'] = $send['title'] = array('초기화면');
				messageQR($send);
			}
		}
		else if(preg_match("/LIBERAL/", $inProgress) && $processingAllCount == 1 && $rgstLiberal == 1) {
			$selectedDiv = "교양";
			
			if(preg_match("/LIBERAL$/", $inProgress) && $payload) {
				$searchFields = $payload;
			
				if(in_array($searchFields, $dbAllFields)) {
					$query = queryInsert('logging', 'REGISTER_LIBERAL_1', array('searchFields'=>$searchFields));
					$conn->query($query);
					
					$query = "SELECT title FROM $course WHERE divs='$selectedDiv' AND fields='$searchFields'";
					$sql4courses = $conn->query($query);
					while($row4courses = $sql4courses->fetch_assoc()) {
						$dbTitle[] = $row4courses['title'];
					}
					
					$send["text"] = "🎩: 교과목을 선택해 주세요.";
					message($send);
					
					$send['elementsTitle'] = "교과목";
					$send['elementsButtonsTitle'] = array_keys(array_flip($dbTitle));
					messageTemplate($send);
			
					ReturningQR();
				}
				else if(!in_array($searchFields, $dbAllFields)) {
					$send['text'] = "🎩: 올바른 영역이 아닌 것 같아요.";
					message($send);
					ReturningQR();
				}
			}
			else if(preg_match("/[1]$/", $inProgress) && $payload) {
				$searchTitle = $payload;
				
				$query = "SELECT * FROM $course WHERE divs='$selectedDiv' AND fields='$searchFields' AND title='$searchTitle'";
				$sql4courses = $conn->query($query);
				$checkOut = checkOverlap($sql4courses);
				
				if($checkOut['condition'] == TRUE) {
					if($checkOut['count'] == "single") {
						if($checkOut['overlap'] == TRUE) {
							$query = queryInsert('logging', 'REGISTER_LIBERAL_1', array('searchFields'=>$searchFields));
							$conn->query($query);
							
							$send['text'] = "🎩: ".$checkOut['text'][0];
							message($send);
							
							$query = "SELECT title FROM $course WHERE divs='$selectedDiv' AND fields='$searchFields'";
							$sql4coursesReturn = $conn->query($query);
							while($row4coursesReturn = $sql4coursesReturn->fetch_assoc()) {
								$dbTitleReturn[] = $row4coursesReturn['title'];
							}
							
							$send['elementsTitle'] = "교과목";
							$send['elementsButtonsTitle'] = array_keys(array_flip($dbTitleReturn));
							messageTemplate($send);
				
							ReturningQR();	
						}
						else if($checkOut['overlap'] == FALSE) {
							$send['text'] = "🎩: ".$checkOut['text'];
							$send['payload'] = $send['title'] = array('마쟈요', '초기화면', '아니얌');
							messageQR($send);
							
							$checkOutInfo = $checkOut['dbInfo'];
							$query = "INSERT INTO logging (userkey, inProgress, searchFields, searchTitle, divs, fields, major, title, code, class, prof, department,
																				day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																					VALUE('$senderID', 'REGISTER_LIBERAL_OPT', '$searchFields', '$searchTitle', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
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
					if(!in_array($searchTitle, $dbAllTitle)) {
						$send['text'] = "🎩: 올바른 교과목명이 아닌 것 같아요.";
						message($send);
						ReturningQR();	
					} else {
						ReturningError();
					}
				} else {
					ReturningError();
				}		
			}
			else if(preg_match("/[2]$/", $inProgress) && $payload) {
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
					// 교수명 분류
					if(strpos($payloadInfo, "교수님") !== FALSE) {
						$payloadInfoProf = str_replace("교수님", "", $payloadInfo);
					}
						
					// 시간 분류
					else if(strpos($payloadInfo, "-") !== FALSE) {
						$payloadInfo = explode("-", $payloadInfo);
						$payloadInfoTime1 = $payloadInfo[1];
						$payloadInfoDay = $payloadInfo[0];
							
						// 시간 분류 + Day2 없음
						if(strpos($payloadInfoDay, ",") !== FALSE) {
							$payloadInfoDay = explode(",",$payloadInfoDay);
							$payloadInfoDay1 = $payloadInfoDay[0];
							$payloadInfoDay2 = $payloadInfoDay[1];
						}
					}
				}
				$query = "SELECT * FROM $course WHERE 
			 													(
			 														divs='$selectedDiv' AND fields='$searchFields' AND title='$payloadTitle'
			 													)
			 													AND
																(
																	class='$payloadInfo'
																	OR department='$payloadInfo'
																	OR prof='$payloadInfoProf'
																	OR day1='$payloadInfoDay1'
																	OR day2='$payloadInfoDay2'
																	OR time1='$payloadInfoTime1'
																)";
				$sql4courses = $conn->query($query);
				$checkOut = checkOverlapReturn($sql4courses);
				
				if($checkOut['condition'] == TRUE) {
					if($checkOut['overlap'] == TRUE) {
						$query = queryInsert('logging', 'REGISTER_LIBERAL_2', array('searchFields'=>$searchFields, 'searchTitle'=>$searchTitle));
						$conn->query($query);
						
						$send['text'] = "🎩: ".$checkOut['text'][0];
						message($send);
						
						$send['elementsTitle'] = "교과목";
						$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
						messageTemplate($send);
						
						ReturningQR();
					}
					else if($checkOut['overlap'] == FALSE) {
						$send['text'] = "🎩: ".$checkOut['text'];
						$send['payload'] = $send['title'] = array('마쟈요', '초기화면', '아니얌');
						messageQR($send);
						
						$checkOutInfo = $checkOut['dbInfo'];
						
						$query = "INSERT INTO logging (userkey, inProgress, searchFields, searchTitle, divs, fields, major, title, code, class, prof, department,
																			day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																				VALUE('$senderID', 'REGISTER_LIBERAL_OPT', '$searchFields', '$searchTitle', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
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
					ReturningError();
				} else {
					ReturningError();
				}		
			}
			else if(preg_match("/OPT$/", $inProgress) && $payloadQR) {
				if($payloadQR == "마쟈요") {
					$optTitle = optTitle();

					$query = queryInsert('logging', 'START');
					$conn->query($query);		
									
					$send['text'] = "🎩: ".$optTitle;
					$send['payload'] = $send['title'] = array("교과목 추가 등록", "시간표 보기", "초기화면");
					messageQR($send);
				}
				else 	if($payloadQR == "아니얌") {
					$query = queryInsert('logging', 'REGISTER_LIBERAL_1', array('searchFields'=>$searchFields));
					$conn->query($query);
					
					$query = "SELECT title FROM $course WHERE divs='$selectedDiv' AND fields='$searchFields'";
					$sql4courses = $conn->query($query);
					while($row4courses = $sql4courses->fetch_assoc()) {
						$dbTitle[] = $row4courses['title'];
					}
					
					$send["text"] = "🎩: 교과목을 선택해 주세요.";
					message($send);
					
					$send['elementsTitle'] = "교과목";
					$send['elementsButtonsTitle'] = array_keys(array_flip($dbTitle));
					messageTemplate($send);
			
					ReturningQR();
				} else {
					ReturningError();
				}		
			} else {
				// 초기화
				$query = resetProcessing();
				$conn->query($query);
				
				$send['text'] = "🎩: 오류가 발생했습니다.\n	ERROR : rgstLiberal_ALL";
				$send['payload'] = $send['title'] = array('초기화면');
				messageQR($send);
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
					
					$query = "SELECT major FROM $course WHERE (major='$searchWord') OR (major LIKE '%$searchWord') OR (major LIKE '$searchWord%') OR (major LIKE '%$searchWord%')";
					$sql4coursesMajor = $conn->query($query);
				
					while($row4coursesMajor = $sql4coursesMajor->fetch_assoc()) {
						$dbResultMajor[] = $row4coursesMajor['major'];
					}
					$dbResultMajor = array_keys(array_flip($dbResultMajor));
			
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
						$send['payload'] = $send['title'] = array('마쟈요', '초기화면', '아니얌');
						messageQR($send);
					}
					else if(empty($dbResultMajor)) {
						$send['text'] = "🎩: 그런 학과는 없는 것 같아요.\n	학과명을 다시 입력해주세요.";
						message($send);
						
						ReturningQR();
					} else {
						ReturningError();
					}
				}
				else if($payload && preg_match("/학과$/", $payload)) {
					$searchMajor = $payload;	
					
					$query = "SELECT * FROM $course WHERE divs='$selectedDiv' AND major='$searchMajor'";
					$sql4courses = $conn->query($query);
					while($row4courses = $sql4courses->fetch_assoc()) {
						$dbGrade[] = $row4courses['grade'] . "학년";
						$dbTitle[] = $row4courses['title'];
					}
					$dbTitle = array_keys(array_flip($dbTitle));
						
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
						
						$send['text'] = "🎩: 검색된 교과목이 너무 많습니다.\n	해당 교과목의 학년 구분을 선택해주세요.";
						message($send);
								
						$send['elementsTitle'] = "학년 구분";
						$send['elementsButtonsTitle'] = array_keys(array_flip($dbGrade));
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
					else if (!in_array($searchMajor, $dbAllMajor)) {
						$send['text'] = "🎩: 올바른 학과명이 아닌 것 같아요.";
						message($send);
						
						ReturningQR();
					}
				} else {
					ReturningError();
				}
			}
			else if(preg_match("/[1]$/", $inProgress) && $payload) {
				$searchMajor = $payload;
			
				$query = "SELECT * FROM $course WHERE divs='$selectedDiv' AND major='$searchMajor'";
				$sql4courses = $conn->query($query);
				while($row4courses = $sql4courses->fetch_assoc()) {
					$dbGrade[] = $row4courses['grade'] . "학년";
					$dbTitle[] = $row4courses['title'];
				}
				$dbTitle = array_keys(array_flip($dbTitle));
					
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
							
					$send['text'] = "🎩: 검색된 교과목이 너무 많습니다.\n	해당 교과목의 학년 구분을 선택해주세요.";
					message($send);
							
					$send['elementsTitle'] = "학년 구분";
					$send['elementsButtonsTitle'] = array_keys(array_flip($dbGrade));
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
				else if (!in_array($searchMajor, $dbAllMajor)) {
					$send['text'] = "🎩: 올바른 학과명이 아닌 것 같아요.";
					message($send);
					
					ReturningQR();
				} else {
					ReturningError();
				}		
			}
			else if(preg_match("/[2]$/", $inProgress) && $payload) {
				$searchGrade = preg_replace("/[^0-9]*/s", "", $payload);
		
				$query = "SELECT title FROM $course WHERE divs='$selectedDiv' AND major='$searchMajor' AND grade='$searchGrade'";
				$sql4courses = $conn->query($query);
				$checkOut = checkOverlap($sql4courses);
				
				if($checkOut['condition'] == TRUE) {
					if($checkOut['count'] == "single") {
						if($checkOut['overlap'] == TRUE) {
							if(preg_match("/MAJOR/", $inProgress)) {
								$query = queryInsert('logging', 'REGISTER_MAJOR');
							}
							else if(preg_match("/MajorBASIC/", $inProgress)) {
								$query = queryInsert('logging', 'REGISTER_MajorBASIC');
							}
							else if(preg_match("/LiberalESSN/", $inProgress)) {
								$query = queryInsert('logging', 'REGISTER_LiberalESSN');
							}	
							
							$send['text'] = "🎩: ".$checkOut['text'][0];
							message($send);
											
							if(!isset($previousSearchMajor)) {
								$send['text'] = "🎩: 학과명을 새로 입력해주세요.";
								message($send);
							} else {
								$send['text'] = "🎩: 이전에 검색한 학과를 선택 또는 새로 검색할 학과명을 입력해주세요.";
								message($send);
								
								$send['elementsTitle'] = "세부 구분";
								$send['elementsButtonsTitle'] = array_keys(array_flip(array_unique($previousSearchMajor)));
								messageTemplate($send);
							}
				
							ReturningQR();
						}
						else if($checkOut['overlap'] == FALSE) {
							$send['text'] = "🎩: ".$checkOut['text'];
							$send['payload'] = $send['title'] = array('마쟈요', '초기화면', '아니얌');
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
							$query = "INSERT INTO logging (userkey, inProgress, searchWord, searchMajor, searchTitle, divs, fields, major, title, code, class, prof, department,
																				day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																					VALUE('$senderID', '$queryInProgress', '$searchWord', '$searchMajor', '$searchGrade', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
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
						$query = "SELECT * FROM $course WHERE divs='$selectedDiv' AND major='$searchMajor'";
						$sql4courses = $conn->query($query);
						while($row4courses = $sql4courses->fetch_assoc()) {
							$dbGrade[] = $row4courses['grade'] . "학년";
						}
						
						$send['text'] = "🎩: 검색된 교과목이 없습니다.\n	해당 교과목의 학년 구분을 다시 선택해주세요.";
						message($send);
								
						$send['elementsTitle'] = "학년 구분";
						$send['elementsButtonsTitle'] = array_keys(array_flip($dbGrade));
						messageTemplate($send);
						
						ReturningQR();
					} 
					else if(!preg_match("/학년$/", $payload) || !is_numeric($searchGrade)) {
						$send['text'] = "🎩: 올바른 학년 구분이 아닌 것 같아요.";
						message($send);
						ReturningQR();
					} else {
						ReturningError();
					}
				} else {
					ReturningError();
				}
			}
			else if(preg_match("/[3]$/", $inProgress) && $payload) {
				$searchTitle = $payload;
				
				if(!empty($searchGrade)) {
					$query = "SELECT * FROM $course WHERE (divs='$selectedDiv' AND major='$searchMajor' AND title='$searchTitle' AND grade='$searchGrade')";	
				}
				else if(empty($searchGrade)) {
					$query = "SELECT * FROM $course WHERE (divs='$selectedDiv' AND major='$searchMajor' AND title='$searchTitle')";
				}
				$sql4courses = $conn->query($query);
				$checkOut = checkOverlap($sql4courses);
				
				if($checkOut['condition'] == TRUE) {
					if($checkOut['count'] == "single") {
						if($checkOut['overlap'] == TRUE) {
							$send['text'] = "🎩: ".$checkOut['text'][0];
							message($send);
							
							if(empty($searchGrade)) {
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
								
								$query = "SELECT * FROM $course WHERE divs='$selectedDiv' AND major='$searchMajor'";
								$sql4coursesReturn = $conn->query($query);
								while($row4coursesReturn = $sql4coursesReturn->fetch_assoc()) {
									$dbTitleReturn[] = $row4coursesReturn['title'];
								}
								
								$send['elementsTitle'] = "교과목";
								$send['elementsButtonsTitle'] = array_keys(array_flip($dbTitleReturn));
								messageTemplate($send);
								
								ReturningQR();
							}
							else if(!empty($searchGrade)) {
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
								
								$query = "SELECT * FROM $course WHERE divs='$selectedDiv' AND major='$searchMajor' AND grade='$searchGrade'";
								$sql4coursesReturn = $conn->query($query);
								while($row4coursesReturn = $sql4coursesReturn->fetch_assoc()) {
									$dbTitleReturn[] = $row4coursesReturn['title'];
								}
								
								$send['elementsTitle'] = "교과목";
								$send['elementsButtonsTitle'] = array_keys(array_flip($dbTitleReturn));
								messageTemplate($send);
								
								ReturningQR();
							}
						}
						else if($checkOut['overlap'] == FALSE) {
							$send['text'] = "🎩: ".$checkOut['text'];
							$send['payload'] = $send['title'] = array('마쟈요', '초기화면', '아니얌');
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
							$query = "INSERT INTO logging (userkey, inProgress, searchWord, searchMajor, searchGrade, searchTitle, divs, fields, major, title, code, class, prof, department,
																				day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																					VALUE('$senderID', '$queryInProgress', '$searchWord', '$searchMajor', '$searchGrade', '$searchTitle', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
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
					if(!in_array($searchMajor, $dbAllMajor)) {
						$send['text'] = "🎩: 올바른 학과명이 아닌 것 같아요.";
						message($send);
						ReturningQR();
					}
					else if(preg_match("/(.*)학년$/", $payload) || !is_numeric($searchGrade)) {
						$send['text'] = "🎩: 올바른 학년 구분이 아닌 것 같아요.";
						message($send);
						ReturningQR();
					} else {
						ReturningError();
					}
				} else {
					ReturningError();
				}
			}
			else if(preg_match("/[4]$/", $inProgress) && $payload) {
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
					// 교수명 분류
					if(strpos($payloadInfo, "교수님") !== FALSE) {
						$payloadInfoProf = str_replace("교수님", "", $payloadInfo);
					}
						
					// 시간 분류
					else if(strpos($payloadInfo, "-") !== FALSE) {
						$payloadInfo = explode("-", $payloadInfo);
						$payloadInfoTime1 = $payloadInfo[1];
						$payloadInfoDay = $payloadInfo[0];
							
						// 시간 분류 + Day2 없음
						if(strpos($payloadInfoDay, ",") !== FALSE) {
							$payloadInfoDay = explode(",",$payloadInfoDay);
							$payloadInfoDay1 = $payloadInfoDay[0];
							$payloadInfoDay2 = $payloadInfoDay[1];
						}
					}
					$query = "SELECT * FROM $course WHERE 
			 	 													(
				 	 													(title='$payloadTitle' AND major='$searchMajor') OR 
				 	 													(title='$payloadTitle' AND major='$searchMajor' AND grade='$searchGrade')
			 	 													) AND
																		(
																			class='$payloadInfo'
																			OR department='$payloadInfo'
																			OR prof='$payloadInfoProf'
																			OR day1='$payloadInfoDay1'
																			OR day2='$payloadInfoDay2'
																			OR time1='$payloadInfoTime1'
																		)";
					$sql4courses = $conn->query($query);
				}
				$checkOut = checkOverlapReturn($sql4courses);
				if($checkOut['condition'] == TRUE) {
					if($checkOut['overlap'] == TRUE) {
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
						
						$send['text'] = "🎩: ".$checkOut['text'][0];
						message($send);
						
						$send['elementsTitle'] = "교과목";
						$send['elementsButtonsTitle'] = $checkOut['dbInfo'];
						messageTemplate($send);
			
						ReturningQR();
					}
					else if($checkOut['overlap'] == FALSE) {
						$send['text'] = "🎩: ".$checkOut['text'];
						$send['payload'] = $send['title'] = array('마쟈요', '초기화면', '아니얌');
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
						$query = "INSERT INTO logging (userkey, inProgress, searchWord, searchMajor, searchGrade, searchTitle, divs, fields, major, title, code, class, prof, department,
																			day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
																				VALUE('$senderID', '$queryInProgress', '$searchWord', '$searchMajor', '$searchGrade', '$searchTitle', '{$checkOutInfo['divs']}', '{$checkOutInfo['fields']}', '{$checkOutInfo['major']}',
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
					ReturningError();
				} else {
					ReturningError();
				}	
			}
			else if(preg_match("/OPT/", $inProgress)) {
				if(preg_match("/1st$/", $inProgress)) {
					if($payloadQR == "마쟈요") {
						$query = "SELECT * FROM $course WHERE divs='$selectedDiv' AND major='$searchMajor'";
						$sql4courses = $conn->query($query);
						while($row4courses = $sql4courses->fetch_assoc()) {
							$dbGrade[] = $row4courses['grade'] . "학년";
							$dbTitle[] = $row4courses['title'];
						}
						$dbTitle = array_keys(array_flip($dbTitle));
							
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
							
							$send['text'] = "🎩: 검색된 교과목이 너무 많습니다.\n	해당 교과목의 학년 구분을 선택해주세요.";
							message($send);
									
							$send['elementsTitle'] = "학년 구분";
							$send['elementsButtonsTitle'] = array_keys(array_flip($dbGrade));
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
							
						} else {
							ReturningError();
						}	
					}
					else if($payloadQR == "아니얌") {
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
							$send['elementsButtonsTitle'] = array_keys(array_flip(array_unique($previousSearchMajor)));
							messageTemplate($send);		
						}
						
						ReturningQR();
					}
				}
				else if(preg_match("/2nd$/", $inProgress)) {
					if($payloadQR == "마쟈요") {
						$optTitle = optTitle();
						
						$query = queryInsert('logging', 'START');
						$conn->query($query);

						$send['text'] = "🎩: ".$optTitle;
						$send['payload'] = $send['title'] = array("교과목 추가 등록", "시간표 보기", "초기화면");
						messageQR($send);
					}
					else if($payloadQR == "아니얌") {
						$query = "SELECT * FROM $course WHERE divs='$selectedDiv' AND major='$searchMajor'";
						$sql4courses = $conn->query($query);
						while($row4courses = $sql4courses->fetch_assoc()) {
							$dbGrade[] = $row4courses['grade'] . "학년";
							$dbTitle[] = $row4courses['title'];
						}
						$dbTitle = array_keys(array_flip($dbTitle));
							
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
									
							$send['text'] = "🎩: 검색된 교과목이 너무 많습니다.\n	해당 교과목의 학년 구분을 선택해주세요.";
							message($send);
									
							$send['elementsTitle'] = "학년 구분";
							$send['elementsButtonsTitle'] = array_keys(array_flip($dbGrade));
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
					} else {
						ReturningError();
					}			
				} else {
					ReturningError();
				}		
			} else {
				// 초기화
				$query = resetProcessing();
				$conn->query($query);
				
				if(preg_match("/MAJOR/", $inProgress)) {
					$send['text'] = "🎩: 오류가 발생했습니다.\n	ERROR : rgstMajor_ALL";			
				}
				else if(preg_match("/MajorBASIC/", $inProgress)) {
					$send['text'] = "🎩: 오류가 발생했습니다.\n	ERROR : rgstMajorBasic_ALL";			
				}
				else if(preg_match("/LiberalESSN/", $inProgress)) {
					$send['text'] = "🎩: 오류가 발생했습니다.\n	ERROR : rgstLiberalEssn_ALL";			
				}		
				$send['payload'] = $send['title'] = array('초기화면');
				messageQR($send);
			}
		}
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////// READ ///////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	else if(preg_match("/^READ/", $inProgress)) {
		// values for searching	
		$query = "SELECT * FROM loggingRead WHERE userkey = '$senderID' ORDER BY inputTime DESC LIMIT 1";
		$sql4loggingRead = $conn->query($query);
		while($row4loggingRead = $sql4loggingRead->fetch_assoc()) {
			$readType = $row4loggingRead['type'];
			$readTitle = $row4loggingRead['title'];
			$readContent = $row4loggingRead['content'];
			$readDate1 = $row4loggingRead['date1'];
			$readDate2 = $row4loggingRead['date2'];
			$readTime1 = $row4loggingRead['time1'];
			$readTime2 = $row4loggingRead['time2'];
		}
		
///////////////////////////////////////////////////////////////////////// SUBJECT ///////////////////////////////////////////////////////////////////////////////		

		if(preg_match("/SUBJECT/", $inProgress)) {
			if(preg_match("/^READ/", $payload) || preg_match("/^REGISTER/", $payload) || preg_match("/^DELETE/", $payload)) {
				$payloadExplode = explode("-", $payload);
				$payloadType = $payloadExplode[0];
				$payloadTitle = $payloadExplode[1];
				
				if($payloadType == "READ") {
					$query = queryInsert('logging', 'READ_EVENT');
					$conn->query($query);
					$query = queryInsert('loggingRead', 'READ_EVENT', array("title"=>$payloadTitle));
					$conn->query($query);
					
					if(count($eventInfo) == 0) {
						$send['text'] = "🎩: {$senderFullName}님은 등록한 항목이 없습니다.\n\n하나 이상의 항목을 등록하시면\n수강 중인 교과목의 과제∙휴강∙시험 정보를 열람할 수 있습니다.\n\n새로운 내용을 등록하거나, 그렇지 않으면 초기화면으로 돌아가주세요.";
						$send['payload'] = $send['title'] = array('새로 등록하기', '초기화면');
					} else {
						for($i=0; $i<count($eventInfo); $i++) {
							$eventInfoTypes[] = $eventInfo[$i]['type'];
						}
						$countTypes = array_count_values($eventInfoTypes);
						
						if(empty($countTypes['assignment']) || empty($countTypes['cancel']) || empty($countTypes['exam'])) {
							if(empty($countTypes['assignment'])) {
								$countTypes['assignment'] = 0;
							}
							if(empty($countTypes['cancel'])) {
								$countTypes['cancel'] = 0;
							}
							if(empty($countTypes['exam'])) {
								$countTypes['exam'] = 0;
							}
						}					
						
						$send['text'] = "<등록된 과제∙휴강∙시험 현황>\n ∙과제: " . $countTypes['assignment'] . "개\n ∙휴강: " . $countTypes['cancel'] . "개\n ∙시험: " . $countTypes['exam'] . "개";
						message($send);
						
						$typeArr = array("assignment", "cancel", "exam");
						for($i=0; $i<count($typeArr); $i++) {
							$readEventInfo = readEventInfo($eventInfo, $typeArr[$i]);
							if($readEventInfo) {
								$send['title'] = $readEventInfo['title'];
								$send['subtitle'] = $readEventInfo['info'];
								$send['buttonsTitle'] = array("다른 수강생들이 등록한 정보 보기");
								$send['payload'] = $readEventInfo['payload'];
								messageTemplateLeftSlide($send);							
							}
						}
		
						$send['text'] = "🎩: 다른 수강생들이 등록한 정보를 열람하거나,\n	새로운 내용을 입력 또는 수정할 수 있습니다.";
						$send['payload'] = $send['title'] = array('새로 등록하기', '등록된 정보 수정하기', '이전으로', '초기화면');
					}
					messageQR($send);
				}
				else if($payloadType == "REGISTER") {
					$query = queryInsert('logging', 'READ_EVENT');
					$conn->query($query);
					$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_1', array("title"=>$payloadTitle));
					$conn->query($query);
					
					$send['text'] = "🎩: 등록할 항목을 선택해주세요.";
					$send['payload'] = $send['title'] = array('과제', '휴강', '시험', '이전으로', '초기화면');
					messageQR($send);
				}
				else if($payloadType == "DELETE") {
					$query = queryInsert('loggingRead', 'READ_SUBJECT_DELETE', array("title"=>$payloadTitle));
					$conn->query($query);
	
					$send['text'] = "🎩: <" . $payloadTitle . ">을(를) 정말 삭제하시겠습니까?";
					$send['payload'] = $send['title'] = array('마쟈요', '초기화면', '아니얌');
					messageQR($send);
				} else {
					WrongAccessQR();
				}
			}
			else if(preg_match("/DELETE$/", $inProgressRead)) {
				if($payloadQR) {
					$query = queryInsert('loggingRead', 'READ_SUBJECT_DELETE');
					$conn->query($query);
					
					if($payloadQR == "마쟈요") {
						$query = "DELETE FROM user WHERE title='$readTitle' AND userkey='$senderID' ORDER BY inputTime DESC LIMIT 1";
						$conn->query($query);
									
						$send['text'] = "<" . $readTitle . ">이(가) 삭제되었습니다";
					}
					else if($payloadQR == "아니얌") {
						$send['text'] = "<" . $readTitle . ">의 삭제가 취소되었습니다";
					}
					message($send);
					
					$send['text'] = "<교과목 등록 현황>\n총 " . count($userInfo) . "과목 등록 완료";
					message($send);
					
					$rgstedInfoDetail = registedConditionSubjectDetail($userInfo);
					for($i=0; $i<count($rgstedInfoDetail['title']); $i++) {
						$titleName = $rgstedInfoDetail['titleName'][$i];
						$send['title'][] = $rgstedInfoDetail['title'][$i];
						$send['subtitle'][] = $rgstedInfoDetail['info'][$i];
						$send['payload'][] = array("READ-".$titleName, "REGISTER-".$titleName, "DELETE-".$titleName);
					}
					$send['buttonsTitle'] = array("등록된 과제∙휴강∙시험 정보 보기", "과제∙휴강∙시험 정보 등록하기", "교과목 삭제하기");
					messageTemplateLeftSlide($send);
					
					$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
					$send['payload'] = $send['title'] = array('시간표 보기', '초기화면');
					messageQR($send);			
				} else {
					WrongAccessQR();
				}
			}
		}

///////////////////////////////////////////////////////////////////////// EVENT ///////////////////////////////////////////////////////////////////////////////

		else if(preg_match("/EVENT/", $inProgress)) {
			if(preg_match("/EVENT$/", $inProgressRead)) {
				if($payload) {
					$payloadExplode = explode("_", $payload);
					$readType = $payloadExplode[0];
					$readNumber = $payloadExplode[1];
					$readTitle = $payloadExplode[2];
					
					if($readType == "assignment") {
						$readTypeKR = "과제";
					}
					else if($readType == "cancel") {
						$readTypeKR = "휴강";
					}
					else if($readType == "exam") {
						$readTypeKR = "시험";
					}				
	
					$query = "SELECT * FROM event WHERE type='$readType' AND title='$readTitle' AND userkey!='$senderID'";
					$sql4eventOther = $conn->query($query);	
					while($row4eventOther = $sql4eventOther->fetch_assoc()) {
						$eventInfoOther[] = $row4eventOther;
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
						$send['payload'] = $readEventInfo['payload'];
						messageTemplateLeftSlide($send);
						
						$query = queryInsert('loggingRead', 'READ_EVENT_INFO_OTHER', array('type'=>$readType));
						$conn->query($query);
					} else {
						$send['text'] = "🎩: <".$readTitle.">에 다른 수강생들이 입력한 " . $readTypeKR . " 정보가 없습니다.";
						message($send);
					}
					ReturningQR();
				}
				else if($payloadQR) {
					if(preg_match("/^attendance/", $payloadQR)) {
						$payloadExplode = explode("_", $payloadQR);
						$readType = 'cancel';
						$readTypeKR = '휴강';
						$readTitle = $payloadExplode[1];
						
						$query = "SELECT * FROM event WHERE type='$readType' AND title='$readTitle' AND userkey!='$senderID'";
						$sql4eventOther = $conn->query($query);	
						while($row4eventOther = $sql4eventOther->fetch_assoc()) {
							$cancelDate1 = mktime(0,0,0,substr($row4eventOther['date1'],0,2),substr($row4eventOther['date1'],2,4),date("Y"));
							if(!empty($row4eventOther['date2'])) {
								$cancelDate2 = mktime(0,0,0,substr($row4eventOther['date2'],0,2),substr($row4eventOther['date2'],2,4),date("Y"));
							}
							
							if($cancelDate1 >= $now || (!empty($cancelDate2) && $cancelDate2 >= $now)) {
								$eventInfoOther[] = $row4eventOther;
							}
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
							messageTemplateLeftSlide($send);
						} else {
							$send['text'] = "🎩: <".$readTitle.">에 다른 수강생들이 입력한 " . $readTypeKR . " 정보가 없노..💦";
							message($send);
						}
						$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
						$send['payload'] = $send['title'] = array('초기화면');
						messageQR($send);
					}
					else if(preg_match("/새로(.*)등록/", $payloadQR) || preg_match("/추가(.*)등록/", $payloadQR)) {
						if($readTitle) {
							$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_1', array('title'=>$readTitle));
							$send['text'] = "🎩: <" . $readTitle . ">에 등록할 항목을 선택해주세요.";					
						} else {
							$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_1');
							$send['text'] = "🎩: 등록할 항목을 선택해주세요.";
						}
						$conn->query($query);
						
						$send['payload'] = $send['title'] = array('과제', '휴강', '시험', '이전으로', '초기화면');
						messageQR($send);
					}
					else if(preg_match("/등록(.*)정보(.*)수정/", $payloadQR) || preg_match("/추가(.*)수정/", $payloadQR)) {
						$query = queryInsert('loggingRead', 'READ_EVENT_DELETE');
						$conn->query($query);
						
						$deleteEvent = deleteEvent($eventInfo);
			
						$send['text'] = "🎩: 등록된 정보 중 삭제할 내용을 선택해주세요.";
						message($send);				
						
						$send['title'] = $deleteEvent['title'];
						$send['subtitle'] = $deleteEvent['info'];
						$send['buttonsTitle'] = array("삭제");
						$send['payload'] =$deleteEvent['payload'];
						messageTemplateLeftSlide($send);
						
						ReturningQR();
					} else {
						WrongAccessQR();
					}
				} else {
					WrongAccessQR();
				}
			}
			else if(preg_match("/INFO/", $inProgressRead)) {
				if(preg_match("/OTHER$/", $inProgressRead) && $payload) {
					$payloadExplode = explode("_", $payload);
					$readType = $payloadExplode[0];
					$readNumber = $payloadExplode[1];
					$readTitle = $payloadExplode[2];
					$readInputTime = $payloadExplode[3];
		
					$query = "SELECT * FROM event WHERE type='$readType' AND title='$readTitle' AND inputTime='$readInputTime' AND userkey!='$senderID'";
					$sql4eventBringMe = $conn->query($query)->fetch_assoc();
					$eventInfoBringMe = $sql4eventBringMe;
					if($eventInfoBringMe) {
						if($readType == "assignment") {
							$readTypeKR = "과제";
							$readContent = $eventInfoBringMe['content'];
							$readDateMonth = substr($eventInfoBringMe['date1'], 0, 2);
							$readDateDay = substr($eventInfoBringMe['date1'], 2, 2);
							
							$query = "INSERT INTO event (userkey, type, title, content, date1, inputTime) VALUE ('$senderID', '$readType', '$readTitle', '{$eventInfoBringMe['content']}', '{$eventInfoBringMe['date1']}', '$inputTime')";		
							$send['text'] = "🎩: <" . $readTitle . ">\n과제내용: " . $readContent . "\n기한: " .  $readDateMonth . "월 " . $readDateDay . "일\n\n위 내용이 과제에 등록되었습니다.";
						}
						else 	if($readType == "cancel") {
							$readTypeKR = "휴강";
							$readDateMonth1 = substr($eventInfoBringMe['date1'], 0, 2);
							$readDateDay1 = substr($eventInfoBringMe['date1'], 2, 2);
							$readDateMonth2 = substr($eventInfoBringMe['date2'], 0, 2);
							$readDateDay2 = substr($eventInfoBringMe['date2'], 2, 2);
	
							if(empty($eventInfoBringMe['date2'])) {
								$query = "INSERT INTO event (userkey, type, title, date1, inputTime) VALUE ('$senderID', '$readType', '$readTitle', '{$eventInfoBringMe['date1']}', '$inputTime')";						
								$send['text'] = "🎩: <" . $readTitle . ">\n날짜: " . $readDateMonth1 . "월 " . $readDateDay1 . "일\n\n위 내용이 휴강에 등록되었습니다.";
							} else {
								$query = "INSERT INTO event (userkey, type, title, date1, date2, inputTime) VALUE ('$senderID', '$readType', '$readTitle', '{$eventInfoBringMe['date1']}', '{$eventInfoBringMe['date2']}', '$inputTime')";
								$send['text'] = "🎩: <" . $readTitle . ">\n날짜: " . $readDateMonth1 . "월 " . $readDateDay1 . "일부터 " . $readDateMonth2 . "월 " . $readDateDay2 . "일 까지\n\n위 내용이 휴강에 등록되었습니다.";
							}
						}
						else 	if($readType == "exam") {
							$readTypeKR = "시험";
							$readDateMonth = substr($eventInfoBringMe['date1'], 0, 2);
							$readDateDay = substr($eventInfoBringMe['date1'], 2, 2);
							$readDateHour = substr($eventInfoBringMe['time1'], 0, 2);
							$readDateMin = substr($eventInfoBringMe['time1'], 2, 2);
							
							$query = "INSERT INTO event (userkey, type, title, date1, time1, inputTime) VALUE ('$senderID', '$readType', '$readTitle', '{$eventInfoBringMe['date1']}', '{$eventInfoBringMe['time1']}', '$inputTime')";
							$send['text'] = "🎩: <" . $readTitle . ">\n날짜: " . $readDateMonth1 . "월 " . $readDateDay1 . "일부터 " . $readDateMonth2 . "월 " . $readDateDay2 . "일 까지\n\n위 내용이 휴강에 등록되었습니다.";
						}		
						$conn->query($query);
						message($send);
					}
						
					$query = "SELECT * FROM event WHERE title='$readTitle' AND userKey!='$senderID'";
					$sql4eventOther = $conn->query($query);	
					while($row4eventOther = $sql4eventOther->fetch_assoc()) {
						$eventInfoOther[] = $row4eventOther;
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
						$send['payload'] = $readEventInfo['payload'];
						messageTemplateLeftSlide($send);
						
						$query = queryInsert('loggingRead', 'READ_EVENT_INFO_OTHER', array('type'=>$readType));
						$conn->query($query);
					} else {
						$send['text'] = "🎩: <".$readTitle.">에 다른 수강생들이 입력한 " . $readTypeKR . " 정보가 없습니다.";
						message($send);
					}
					ReturningQR();
				} else {
					WrongAccessQR();
				}
			}
			else if(preg_match("/WRITE/", $inProgressRead)) {
				if(preg_match("/[1]$/", $inProgressRead) && !isset($readTitle)) {
					$query = "SELECT * FROM user WHERE userkey='$senderID'";
					$sql4user = $conn->query($query);
					while($row4user = $sql4user->fetch_assoc()) {
						$userInfoTitles[] = $row4user['title'];
					}
					
					if($payloadQR == "과제") {
						$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_2', array('type'=>'assignment'));
					}
					else if($payloadQR == "휴강") {
						$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_2', array('type'=>'cancel'));
					}
					else if($payloadQR == "시험") {
						$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_2', array('type'=>'exam'));
					}
					$conn->query($query);
					
					$send['text'] = "🎩: <". $payloadQR . ">를 등록할 교과목을 선택해주세요.";
					$send['payload'] = $send['title'] = $userInfoTitles;
					array_push($send['title'], "이전으로", "초기화면");
					array_push($send['payload'], "이전으로", "초기화면");
					messageQR($send);
				}
				else if(preg_match("/[2]$/", $inProgressRead) || (preg_match("/[1]$/", $inProgressRead) && isset($readTitle))) {
					if(preg_match("/[2]$/", $inProgressRead)) {
						$readTitle = $payloadQR;
						if($readType == "assignment") {
							$send['text'] = "🎩: <" . $readTitle .">에 등록할 과제 내용를 상세히 입력해주세요.";
							$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_3', array('type'=>$readType, 'title'=>$readTitle));
						}
						else 	if($readType == "cancel") {
							$send['text'] = "🎩: <" . $readTitle .">에 등록할 휴강 날짜를 <숫자 4자리>로 입력해주세요.\n	예) 10월 16일 -> 1016\n\n휴강이 단일이 아닌 복수일(기간)이라면,\n첫날과 마지막날을 슬래쉬(/)를 포함한 <숫자 8자리>로 입력해주세요.\n예) 10월16일부터 10월 23일 -> 1016/1023";			
							$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_4', array('type'=>$readType, 'title'=>$readTitle));
						}
						else 	if($readType == "exam") {
							$send['text'] = "🎩: <" . $readTitle .">에 등록할 시험 날짜와 시간을\n	슬래쉬(/)를 포함한 <숫자 8자리>로 입력해주세요.\n예) 10월 16일 오후 1시반 -> 1016/1330";
							$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_4', array('type'=>$readType, 'title'=>$readTitle));
						}			
					}
					else if(preg_match("/[1]$/", $inProgressRead) && isset($readTitle)) {
						if($payloadQR == "과제") {
							$send['text'] = "🎩: <" . $readTitle .">에 등록할 과제 내용를 상세히 입력해주세요.";
							$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_3', array('type'=>'assignment', 'title'=>$readTitle));
						}
						else 	if($payloadQR == "휴강") {
							$send['text'] = "🎩: <" . $readTitle .">에 등록할 휴강 날짜를 <숫자 4자리>로 입력해주세요.\n	예) 10월 16일 -> 1016\n\n휴강이 단일이 아닌 복수일(기간)이라면,\n첫날과 마지막날을 슬래쉬(/)를 포함한 <숫자 8자리>로 입력해주세요.\n예) 10월16일부터 10월 23일 -> 1016/1023";			
							$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_4', array('type'=>'cancel', 'title'=>$readTitle));
						}
						else 	if($payloadQR == "시험") {
							$send['text'] = "🎩: <" . $readTitle .">에 등록할 시험 날짜와 시간을\n	슬래쉬(/)를 포함한 <숫자 8자리>로 입력해주세요.\n예) 10월 16일 오후 1시반 -> 1016/1330";
							$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_4', array('type'=>'exam', 'title'=>$readTitle));
						}					
					}
					message($send);
					$conn->query($query);
					
					ReturningQR();		
				}
				else if(preg_match("/[3]$/", $inProgressRead) && $messageText) {
					$readContent = $messageText;
					
					$send['text'] = "<" . $readTitle . ">\n과제내용: " . $readContent;
					message($send);
					
					$send['text'] = "🎩: 위 과제의 기한을 <숫자 4자리>로 입력해주세요.\n예) 10월 16일 -> 1016";
					message($send);
						
					$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_4', array('type'=>$readType, 'title'=>$readTitle, 'content'=>$readContent));
					$conn->query($query);				
				}
				else if(preg_match("/[4]$/", $inProgressRead) && $messageText) {
					$readDate = $messageText;
					$writeEvent = writeEvent($readDate, $readType);
					
					if($readType == "assignment") {
						if($writeEvent['condition'] == TRUE) {
							$send['text'] = "🎩: ".$writeEvent['text'];
							$send['payload'] = $send['title'] = array('마쟈요', '초기화면', '아니얌');
							messageQR($send);
							
							$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_FIN', array('type'=>$readType, 'title'=>$readTitle, 'content'=>$readContent, 'date1'=>$writeEvent['date1']));
							$conn->query($query);
						}					
					}
					else if($readType == "cancel" || $readType == "exam") {
						if($writeEvent['condition'] == TRUE) {
							$send['text'] = "🎩: ".$writeEvent['text'];
							$send['payload'] = $send['title'] = array('마쟈요', '초기화면', '아니얌');
							messageQR($send);
							
							if($readType == "cancel") {
								if(empty($writeEvent['date2'])) {
									$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_FIN', array('type'=>$readType, 'title'=>$readTitle, 'date1'=>$writeEvent['date1']));
								}
								else if(!empty($writeEvent['date2'])) {
									$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_FIN', array('type'=>$readType, 'title'=>$readTitle, 'date1'=>$writeEvent['date1'], 'date2'=>$writeEvent['date2']));
								}
							}
							else if($readType == "exam") {
								$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_FIN', array('type'=>$readType, 'title'=>$readTitle, 'date1'=>$writeEvent['date1'], 'time1'=>$writeEvent['time1']));
							}
							$conn->query($query);
						}
						else if($writeEvent['condition'] == FALSE) {
							$send['text'] = "🎩: ".$writeEvent['text'];
							message($send);
						
							ReturningQR();
						}
					}
				}
				else if(preg_match("/FIN$/", $inProgressRead)) {
					if($payloadQR == "마쟈요") {
						if($readType == "assignment") {
							$query = "SELECT * FROM loggingRead WHERE userkey='$senderID' ORDER BY inputTime DESC LIMIT 1";
							$sql4loggingRead = $conn->query($query)->fetch_assoc();
							
							$readDateMonth = substr($sql4loggingRead['date1'], 0, 2);
							$readDateDay = substr($sql4loggingRead['date1'], 2, 2);
							
							$send['text'] = "🎩: <" . $readTitle . ">\n과제내용: " . $readContent . "\n기한: " .  $readDateMonth . "월 " . $readDateDay . "일\n\n위 내용이 과제에 등록되었습니다.";
							$send['payload'] = $send['title'] = array('과제∙휴강∙시험 추가 등록', '초기화면');
							messageQR($send);
							
							$query = "INSERT IGNORE INTO event (userkey, type, title, content, date1, inputTime)
												SELECT userkey, type, title, content, date1, '$inputTime'
													FROM loggingRead
													WHERE userkey='$senderID'
													ORDER BY inputTime DESC
													LIMIT 1";
							$conn->query($query);
						}
						else 	if($readType == "cancel") {
							$query = "SELECT * FROM loggingRead WHERE userkey='$senderID' ORDER BY inputTime DESC LIMIT 1";
							$sql4loggingRead = $conn->query($query)->fetch_assoc();
							
							$readDateMonth1 = substr($sql4loggingRead['date1'], 0, 2);
							$readDateDay1 = substr($sql4loggingRead['date1'], 2, 2);
							$readDateMonth2 = substr($sql4loggingRead['date2'], 0, 2);
							$readDateDay2 = substr($sql4loggingRead['date2'], 2, 2);
							
							if(empty($sql4loggingRead['date2'])) {
								$send['text'] = "🎩: <" . $readTitle . ">\n날짜: " . $readDateMonth1 . "월 " . $readDateDay1 . "일\n\n위 내용이 휴강에 등록되었습니다.";
					
								$query = "INSERT IGNORE INTO event (userkey, type, title, date1, inputTime)
													SELECT userkey, type, title, date1, '$inputTime'
														FROM loggingRead
														WHERE userkey='$senderID'
														ORDER BY inputTime DESC
														LIMIT 1";
								$conn->query($query);
							}
							else if(!empty($sql4loggingRead['date2'])) {
								$send['text'] = "🎩: <" . $readTitle . ">\n날짜: " . $readDateMonth1 . "월 " . $readDateDay1 . "일부터 " . $readDateMonth2 . "월 " . $readDateDay2 . "일 까지\n\n위 내용이 휴강에 등록되었습니다.";
				
								$query = "INSERT IGNORE INTO event (userkey, type, title, date1, date2, inputTime)
													SELECT userkey, type, title, date1, date2, '$inputTime'
														FROM loggingRead
														WHERE userkey='$senderID'
														ORDER BY inputTime DESC
														LIMIT 1";
								$conn->query($query);
							}
							$send['payload'] = $send['title'] = array('과제∙휴강∙시험 추가 등록', '초기화면');
							messageQR($send);
						}
						else 	if($readType == "exam") {
							$query = "SELECT * FROM loggingRead WHERE userkey='$senderID' ORDER BY inputTime DESC LIMIT 1";
							$sql4loggingRead = $conn->query($query)->fetch_assoc();
							
							$readDateMonth = substr($sql4loggingRead['date1'], 0, 2);
							$readDateDay = substr($sql4loggingRead['date1'], 2, 2);
							$readDateHour = substr($sql4loggingRead['time1'], 0, 2);
							$readDateMin = substr($sql4loggingRead['time1'], 2, 2);
						
							$send['text'] = "🎩: <" . $readTitle . ">\n날짜: " . $readDateMonth . "월 " . $readDateDay . "일 / ". $readDateHour . "시 " . $readDateMin . "분\n\n위 내용이 시험에 등록되었습니다.";
							$send['payload'] = $send['title'] = array('과제∙휴강∙시험 추가 등록', '초기화면');
							messageQR($send);
							
							$query = "INSERT IGNORE INTO event (userkey, type, title, date1, time1, inputTime)
												SELECT userkey, type, title, date1, time1, '$inputTime'
													FROM loggingRead
													WHERE userkey='$senderID'
													ORDER BY inputTime DESC
													LIMIT 1";
							$conn->query($query);
						}					
						$query = queryInsert('loggingRead', 'READ_EVENT');
						$conn->query($query);
					}
					else if($payloadQR == "아니얌") {
						if($readType == "assignment") {
							$send['text'] = "🎩: <" . $readTitle .">에 등록할 과제 내용를 상세히 입력해주세요.";
							$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_3', array('type'=>$readType, 'title'=>$readTitle));
						}
						else 	if($readType == "cancel") {
							$send['text'] = "🎩: <" . $readTitle .">에 등록할 휴강 날짜를 <숫자 4자리>로 입력해주세요.\n예) 10월 16일 -> 1016\n\n휴강이 단일이 아닌 복수일(기간)이라면,\n첫날과 마지막날을 슬래쉬(/)를 포함한 <숫자 8자리>로 입력해주세요.\n예) 10월16일부터 10월 23일 -> 1016/1023";			
							$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_4', array('type'=>$readType, 'title'=>$readTitle));
						}
						else 	if($readType == "exam") {
							$send['text'] = "🎩: <" . $readTitle .">에 등록할 시험 날짜와 시간을\n슬래쉬(/)를 포함한 <숫자 8자리>로 입력해주세요.\n예) 10월 16일 오후 1시반 -> 1016/1330";
							$query = queryInsert('loggingRead', 'READ_EVENT_WRITE_4', array('type'=>$readType, 'title'=>$readTitle));
						}
						message($send);
						$conn->query($query);
					
						ReturningQR();
					}
				} else {
					WrongAccessQR();
				}
			}
			else if(preg_match("/DELETE/", $inProgressRead)) {
				if(preg_match("/DELETE$/", $inProgressRead)) {
					$explodedPayload = explode("_", $payload);
					$readDeleteType = $explodedPayload[1];
					$readDeleteNumber = $explodedPayload[2];
					$readDeleteInfo = $eventInfo[$readDeleteNumber];
					
					if($readDeleteType == "assignment") {
						$send['text'] = "🎩: <" . $readDeleteInfo['title'] . ">\n과제내용: " . $readDeleteInfo['content'] . "\n기한: " . substr($readDeleteInfo['date1'], 0, 2) . "월 " . substr($readDeleteInfo['date1'], 2, 2) . "일\n\n위 과제 내용을 삭제하는 것이 맞나요?";
						$query = queryInsert('loggingRead', 'READ_EVENT_DELETE_FIN', array('type'=>$readDeleteInfo['type'], 'title'=>$readDeleteInfo['title'], 'content'=>$readDeleteInfo['content']));
					}
					else if($readDeleteType == "cancel") {
						if(empty($readDeleteInfo['date2'])) {
							$send['text'] = "🎩: <" . $readDeleteInfo['title'] . ">\n날짜: " . substr($readDeleteInfo['date1'], 0, 2) . "월 " . substr($readDeleteInfo['date1'], 2, 2) . "일\n\n위 휴강 내용을 삭제하는 것이 맞나요?";
							$query = queryInsert('loggingRead', 'READ_EVENT_DELETE_FIN', array('type'=>$readDeleteInfo['type'], 'title'=>$readDeleteInfo['title'], 'date1'=>$readDeleteInfo['date1']));
						}
						else if(!empty($readDeleteInfo['date2'])) {
							$send['text'] = "🎩: <" . $readDeleteInfo['title'] . ">\n날짜: " . substr($readDeleteInfo['date1'], 0, 2) . "월 " . substr($readDeleteInfo['date1'], 2, 2) . "일부터 " . substr($readDeleteInfo['date2'], 0, 2) . "월 " . substr($readDeleteInfo['date2'], 2, 2) . "일 까지\n\n위 휴강 내용을 삭제하는 것이 맞나요?";
							$query = queryInsert('loggingRead', 'READ_EVENT_DELETE_FIN', array('type'=>$readDeleteInfo['type'], 'title'=>$readDeleteInfo['title'], 'date1'=>$readDeleteInfo['date1'], 'date2'=>$readDeleteInfo['date2']));			
						}
					}
					else if($readDeleteType == "exam") {
						$send['text'] = "🎩: <" . $readDeleteInfo['title'] . ">\n날짜: " . substr($readDeleteInfo['date1'], 0, 2) . "월 " . substr($readDeleteInfo['date1'], 2, 2) . "일 / ". substr($readDeleteInfo['time1'], 0, 2) . "시 " . substr($readDeleteInfo['time1'], 2, 2) . "분\n\n위 시험 내용을 삭제하는 것이 맞나요?";
						$query = queryInsert('loggingRead', 'READ_EVENT_DELETE_FIN', array('type'=>$readDeleteInfo['type'], 'title'=>$readDeleteInfo['title'], 'date1'=>$readDeleteInfo['date1'], 'time1'=>$readDeleteInfo['time1']));			
					}
					$conn->query($query);
					
					$send['payload'] = $send['title'] = array('마쟈요', '초기화면', '아니얌');
					messageQR($send);
				}
				else if(preg_match("/FIN$/", $inProgressRead)) {
					if($payloadQR == "마쟈요") {
						$query = "SELECT * FROM loggingRead WHERE inProgress='READ_EVENT_DELETE_FIN' AND userkey='$senderID' ORDER BY inputTime DESC LIMIT 1";
						$sql4loggingRead = $conn->query($query)->fetch_assoc();
						
						if($sql4loggingRead['type'] == "assignment") {
							$send['text'] = "🎩: <" . $sql4loggingRead['title'] . ">\n과제내용: " . $sql4loggingRead['content'] . "\n기한: " . substr($sql4loggingRead['date1'], 0, 2) . "월 " . substr($sql4loggingRead['date1'], 2, 2) . "일\n\n위 과제 항목이 삭제되었습니다.";
							$query = "DELETE FROM event WHERE type='{$sql4loggingRead['type']}' AND title='{$sql4loggingRead['title']}' AND content='{$sql4loggingRead['content']}' AND userkey='$senderID' ORDER BY inputTime DESC LIMIT 1";
						}
						else if($sql4loggingRead['type'] == "cancel") {
							if(empty($sql4loggingRead['date2'])) {
								$send['text'] = "🎩: <" . $sql4loggingRead['title'] . ">\n날짜: " . substr($sql4loggingRead['date1'], 0, 2) . "월 " . substr($sql4loggingRead['date1'], 2, 2) . "일\n\n위 휴강 항목이 삭제되었습니다.";
								$query = "DELETE FROM event WHERE type='{$sql4loggingRead['type']}' AND title='{$sql4loggingRead['title']}' AND date1='{$sql4loggingRead['date1']}' AND userkey='$senderID' ORDER BY inputTime DESC LIMIT 1";
							}
							else if(!empty($sql4loggingRead['date2'])) {
								$send['text'] = "🎩: <" . $sql4loggingRead['title'] . ">\n날짜: " . substr($sql4loggingRead['date1'], 0, 2) . "월 " . substr($sql4loggingRead['date1'], 2, 2) . "일부터 " . substr($sql4loggingRead['date2'], 0, 2) . "월 " . substr($sql4loggingRead['date2'], 2, 2) . "일 까지\n\n위 휴강 항목이 삭제되었습니다.";
								$query = "DELETE FROM event WHERE type='{$sql4loggingRead['type']}' AND title='{$sql4loggingRead['title']}' AND date1='{$sql4loggingRead['date1']}' AND date1='{$sql4loggingRead['date2']}' AND userkey='$senderID' ORDER BY inputTime DESC LIMIT 1";
							}
						}
						else if($sql4loggingRead['type'] == "exam") {
							$send['text'] = "<" . $sql4loggingRead['title'] . ">\n날짜: " . substr($sql4loggingRead['date1'], 0, 2) . "월 " . substr($sql4loggingRead['date1'], 2, 2) . "일 / ". substr($sql4loggingRead['time1'], 0, 2) . "시 " . substr($sql4loggingRead['time1'], 2, 2) . "분\n\n위 시험 항목이 삭제되었습니다.";
							$query = "DELETE FROM event WHERE type='{$sql4loggingRead['type']}' AND title='{$sql4loggingRead['title']}' AND date1='{$sql4loggingRead['date1']}' AND time1='{$sql4loggingRead['time1']}' AND userkey='$senderID' ORDER BY inputTime DESC LIMIT 1";		
						}
						$conn->query($query);
						
						$query = queryInsert('loggingRead', 'READ_EVENT');
						$conn->query($query);
				
						$send['payload'] = $send['title'] = array('등록된 정보 추가 수정', '초기화면');
						messageQR($send);
					}
					else if($payloadQR == "아니얌") {
						$query = queryInsert('loggingRead', 'READ_EVENT_DELETE');
						$conn->query($query);
						
						$deleteEvent = deleteEvent($eventInfo);
			
						$send['text'] = "🎩: 등록된 정보 중 삭제할 내용을 선택해주세요.";
						message($send);				
						
						$send['title'] = $deleteEvent['title'];
						$send['subtitle'] = $deleteEvent['info'];
						$send['buttonsTitle'] = array("삭제");
						$send['payload'] = $deleteEvent['payload'];
						messageTemplateLeftSlide($send);
						
						ReturningQR();
					}
				}
			} else {
				WrongAccessQR();
			}
		}
	} else {
		// defense // 보완 필요
		
		// 초기화
		$query = resetProcessing();
		$conn->query($query);
		
		if($messageText) {
			$textArr = array("?", "??", "???", "????", "?????", "??????", "???????", "????????", "????????????????????????????????????????");
			shuffle($textArr);
			$send['text'] = "🎩: ".$textArr[0];
		} else {
			$send['text'] = "ERROR : ALL";
		}
		
		$send['payload'] = $send['title'] = array('초기화면');
		messageQR($send);
	}
}

exit;

?>