<?php
function checkOverlap($sql4courses)
{
	global $senderID, $conn, $inProgress, $userInfo;
	
	$numOfDays = 6;
	while($row4courses = $sql4courses->fetch_assoc()) {
		$dbResult[] = $row4courses;
		$dbFields[] = $row4courses['fields'];
		$dbDivs[] = $row4courses['divs'];
		$dbCode[] = $row4courses['code'];
		$dbMajor[] = $row4courses['major'];
		$dbTitle[] = $row4courses['title'];
		$dbClass[] = $row4courses['class'];
		$dbProf[] = $row4courses['prof'];
		for($i=1; $i<=$numOfDays; $i++) {
			${dbDay.$i}[] = $row4courses["day".$i];
			${dbMin.$i}[] = $row4courses["min".$i];
			${dbTime.$i}[] = $row4courses["time".$i];
			${dbClassroom.$i}[] = $row4courses["classroom".$i];
		}
		$dbDepartment[] = $row4courses['department'];
	}
	if(!empty($dbResult)) {
		if(count($dbResult) == 1) {
			$dbResult = $dbResult[0];
			
			//시작시간 ~ 마치는시간
			for($i=0; $i<$numOfDays; $i++) {
				${dbTimeExp_.$i} = explode(":", $dbResult["time".($i+1)]);
				${dbConvertMinInit_.$i} = ${dbTimeExp_.$i}[0]*60 + ${dbTimeExp_.$i}[1];
				${dbConvertMinFin_.$i} = ${dbConvertMinInit_.$i} + $dbResult["min".($i+1)];
				$dbArr[] = ['init'=>${dbConvertMinInit_.$i}, 'fin'=>${dbConvertMinFin_.$i}];
			}
			
			for($i=0; $i<count($userInfo); $i++) {
				for($j=0; $j<$numOfDays; $j++) {
					${userTimeExp_.$j} = explode(":", $userInfo[$i]["time".($j+1)]);
					${userConvertMinInit_.$j} = ${userTimeExp_.$j}[0]*60 + ${userTimeExp_.$j}[1];
					${userConvertMinFin_.$j} = ${userConvertMinInit_.$j} + $userInfo[$i]["min".($j+1)];
					$userArr[$i][] = ['init'=>${userConvertMinInit_.$j}, 'fin'=>${userConvertMinFin_.$j}];
				}
			}
			
			for($i=0; $i<count($userInfo); $i++) {
				for($j=0; $j<$numOfDays; $j++) {
					if($dbArr[$j]['init'] != 0 && $userArr[$i][$j]['init'] != 0 && $dbArr[$j]['fin'] != 0 && $userArr[$i][$j]['fin'] != 0) {	
						${compareTime_.$j} = (($dbArr[$j]['init'] > $userArr[$i][$j]['init']) && ($dbArr[$j]['init'] > $userArr[$i][$j]['fin']) && ($dbArr[$j]['fin'] > $userArr[$i][$j]['init']) && ($dbArr[$j]['fin'] > $userArr[$i][$j]['fin'])) || (($dbArr[$j]['init'] < $userArr[$i][$j]['init']) && ($dbArr[$j]['init'] < $userArr[$i][$j]['fin']) && ($dbArr[$j]['fin'] < $userArr[$i][$j]['init']) && ($dbArr[$j]['fin'] < $userArr[$i][$j]['fin']));
						
						// 교과목명 중복 체크
						if($dbResult['title'] != $userInfo[$i]['title']) {
							// 시간 중복 체크
							if(${compareTime_.$j}) {
								// 요일 중복 체크
								$checkResults[$i][$j] = TRUE;
							}
							else if(!${compareTime_.$j}) {
								if($dbResult["day".($j+1)] != $userInfo[$i]["day".($j+1)]) {
									$checkResults[$i][$j] = TRUE;
								} else {
									$checkResults[$i][$j] = FALSE;
								}
							} else {
								$checkResults[$i][$j] = FALSE;
							}
						} else {
							$checkResults[$i][$j] = FALSE;
						}
					}
				}
				if(in_array(FALSE, $checkResults[$i])) {
					$checkResult[] = FALSE;
				} else {
					$checkResult[] = TRUE;
				}
			}
		
			if(in_array(FALSE, $checkResult)) {
				foreach($checkResult as $key=>$val) {
					if($val == FALSE) {
						$overlapKey[] = $key;
					}
				}
				$result['condition'] = TRUE;
				$result['count'] = "single";
				$result['overlap'] = TRUE;
				$result['text'][0] = "<".$dbResult['title'].">은\n\n<" . $userInfo[$overlapKey[0]]['title'] . ">와 교과목명 혹은 시간이 중복됩니다.\n\n확인 후 다시 선택해주세요.";
				$result['text'][1] = "<".$dbResult['title'].">은\n\n<" . $userInfo[$overlapKey[0]]['title'] . ">와 교과목명 혹은 시간이 중복됩니다.\n\n다시 한번 상세히 입력해주세요.";		
			}
			else if(!in_array(FALSE, $checkResult)) {
				$result['condition'] = TRUE;
				$result['count'] = "single";
				$result['overlap'] = FALSE;
				$result['dbInfo'] = array('divs' => $dbResult['divs'], 'fields' => $dbResult['fields'], 'major' => $dbResult['major'], 'title' => $dbResult['title'], 'code' => $dbResult['code'], 'class' => $dbResult['class'], 'prof' => $dbResult['prof'], 'department' => $dbResult['department'],
														'day1' => $dbResult['day1'], 'day2' => $dbResult['day2'], 'day3' => $dbResult['day3'], 'day4' => $dbResult['day4'], 'day5' => $dbResult['day5'], 'day6' => $dbResult['day6'],
														'time1' => $dbResult['time1'], 'time2' => $dbResult['time2'], 'time3' => $dbResult['time3'], 'time4' => $dbResult['time4'], 'time5' => $dbResult['time5'], 'time6' => $dbResult['time6'],
														'min1' => $dbResult['min1'], 'min2' => $dbResult['min2'], 'min3' => $dbResult['min3'], 'min4' => $dbResult['min4'], 'min5' => $dbResult['min5'], 'min6' => $dbResult['min6'],
														'classroom1' => $dbResult['classroom1'], 'classroom2' => $dbResult['classroom2'], 'classroom3' => $dbResult['classroom3'], 'classroom4' => $dbResult['classroom4'], 'classroom5' => $dbResult['classroom5'], 'classroom6' => $dbResult['classroom6']
														);
				$result['text'] = "<".$dbResult['title'].">은\n\n".$dbResult['department']."\n".$dbResult['class']."분반\n".$dbResult['prof']."교수님\n수업이 맞나요?";		
			}
		}
		else if(count($dbResult) > 1 && count($dbResult) < 31) {
			for($i=0; $i<count($dbTitle); $i++) {
				// 교과목명 중복
				if($dbTitle != array_unique($dbTitle)) {
					// 교수명 중복
					if($dbProf != array_unique($dbProf)) {
						// 분반 중복
						if($dbClass != array_unique($dbClass)) {
							// 요일 및 시간 중복
							if($dbDay1 != array_unique($dbDay1)) {
								// 학부 중복
								if($dbDepartment != array_unique($dbDepartment)) {
									//학과 중복
									if($dbMajor != array_unique($dbMajor)) {
										$dbTitleResult = FALSE;
									}
								} else {
									for($j=0; $j<count($dbTitle); $j++) {
										if(empty($dbDepartment[$j])) {
											$dbDepartment[$j] = "전대학";
										}
										$dbTitleResult[] = $dbTitle[$j] . "(" . $dbDepartment[$j] . ")";
									}
									break;
								}
							} else {
								for($j=0; $j<count($dbTitle); $j++) {
									if(empty($dbDay2[$j])) {
										$dbTitleResult[] = $dbTitle[$j] . "(" . $dbDay1[$j]  . "-" . $dbTime1[$j] . ")";
									} else {
										$dbTitleResult[] = $dbTitle[$j] . "(" . $dbDay1[$j] . ", " . $dbDay2[$j] . "-" . $dbTime1[$j] . ")";
									}
								}
								break;
							}
						} else {
							for($j=0; $j<count($dbTitle); $j++) {
								$dbTitleResult[] = $dbTitle[$j] . "(" . $dbClass[$j] . ")";
							}
							break;
						}
					} else {
						for($j=0; $j<count($dbTitle); $j++) {
							$dbTitleResult[] = $dbTitle[$j] . "(" . $dbProf[$j] . "교수님)";
						}
						break;
					}
				} else {
					for($j=0; $j<count($dbTitle); $j++) {
						$dbTitleResult[] = $dbTitle[$j];
					}
					break;
				}
			}
			if($dbTitleResult != FALSE) {
				$result['condition'] = TRUE;
				$result['count'] = "multiple";
				$result['overcount'] = TRUE;
				$result['dbInfo'] = $dbTitleResult;
			}
			else if($dbTitleResult == FALSE) {
				$result['condition'] = TRUE;
				$result['count'] = "multipleSort";
				$result['overcount'] = TRUE;
				$result['dbInfo'] = array_keys(array_flip($dbTitle));
			}
		} else {
			if(count($dbResult) >= 31) {
				$result['condition'] = FALSE;
				$result['overcount'] = FALSE;
				$result['dbInfo'] = array_keys(array_flip($dbTitle));
			} else {
				$result['condition'] = FALSE;
				$result['text'] = "ERROR : " . $inProgress;			
			}
		}
	} else {
		$result['condition'] = FALSE;
		$result['text'] = "ERROR : " . $inProgress;			
	}

	return $result;
}

function checkOverlapReturn($sql4courses)
{
	global $course, $senderID, $conn, $inProgress, $userInfo, $selectedDiv, $searchFields, $searchTitle, $searchGrade, $searchWord;
	
	$numOfDays = 6;
	while($row4courses = $sql4courses->fetch_assoc()) {
		$dbResult[] = $row4courses;
		$dbFields[] = $row4courses['fields'];
		$dbDivs[] = $row4courses['divs'];
		$dbCode[] = $row4courses['code'];
		$dbMajor[] = $row4courses['major'];
		$dbTitle[] = $row4courses['title'];
		$dbClass[] = $row4courses['class'];
		$dbProf[] = $row4courses['prof'];
		for($i=1; $i<=$numOfDays; $i++) {
			${dbDay.$i}[] = $row4courses["day".$i];
			${dbMin.$i}[] = $row4courses["min".$i];
			${dbTime.$i}[] = $row4courses["time".$i];
			${dbClassroom.$i}[] = $row4courses["classroom".$i];
		}
		$dbDepartment[] = $row4courses['department'];
	}
	if(!empty($dbResult)) {
		$dbResult = $dbResult[0];
		
		//시작시간 ~ 마치는시간
		for($i=0; $i<$numOfDays; $i++) {
			${dbTimeExp_.$i} = explode(":", $dbResult["time".($i+1)]);
			${dbConvertMinInit_.$i} = ${dbTimeExp_.$i}[0]*60 + ${dbTimeExp_.$i}[1];
			${dbConvertMinFin_.$i} = ${dbConvertMinInit_.$i} + $dbResult["min".($i+1)];
			$dbArr[] = ['init'=>${dbConvertMinInit_.$i}, 'fin'=>${dbConvertMinFin_.$i}];
		}
		
		for($i=0; $i<count($userInfo); $i++) {
			for($j=0; $j<$numOfDays; $j++) {
				${userTimeExp_.$j} = explode(":", $userInfo[$i]["time".($j+1)]);
				${userConvertMinInit_.$j} = ${userTimeExp_.$j}[0]*60 + ${userTimeExp_.$j}[1];
				${userConvertMinFin_.$j} = ${userConvertMinInit_.$j} + $userInfo[$i]["min".($j+1)];
				$userArr[$i][] = ['init'=>${userConvertMinInit_.$j}, 'fin'=>${userConvertMinFin_.$j}];
			}
		}
		
		for($i=0; $i<count($userInfo); $i++) {
			for($j=0; $j<$numOfDays; $j++) {
				if($dbArr[$j]['init'] != 0 && $userArr[$i][$j]['init'] != 0 && $dbArr[$j]['fin'] != 0 && $userArr[$i][$j]['fin'] != 0) {	
					${compareTime_.$j} = (($dbArr[$j]['init'] > $userArr[$i][$j]['init']) && ($dbArr[$j]['init'] > $userArr[$i][$j]['fin']) && ($dbArr[$j]['fin'] > $userArr[$i][$j]['init']) && ($dbArr[$j]['fin'] > $userArr[$i][$j]['fin'])) || (($dbArr[$j]['init'] < $userArr[$i][$j]['init']) && ($dbArr[$j]['init'] < $userArr[$i][$j]['fin']) && ($dbArr[$j]['fin'] < $userArr[$i][$j]['init']) && ($dbArr[$j]['fin'] < $userArr[$i][$j]['fin']));
					
					// 교과목명 중복 체크
					if($dbResult['title'] != $userInfo[$i]['title']) {
						// 시간 중복 체크
						if(${compareTime_.$j}) {
							// 요일 중복 체크
							$checkResults[$i][$j] = TRUE;
						}
						else if(!${compareTime_.$j}) {
							if($dbResult["day".($j+1)] != $userInfo[$i]["day".($j+1)]) {
								$checkResults[$i][$j] = TRUE;
							} else {
								$checkResults[$i][$j] = FALSE;
							}
						} else {
							$checkResults[$i][$j] = FALSE;
						}
					} else {
						$checkResults[$i][$j] = FALSE;
					}
				}
			}
			if(in_array(FALSE, $checkResults[$i])) {
				$checkResult[] = FALSE;
			} else {
				$checkResult[] = TRUE;
			}
		}
	
		if(in_array(FALSE, $checkResult)) {
			foreach($checkResult as $key=>$val) {
				if($val == FALSE) {
					$overlapKey[] = $key;
				}
			}
			
			if(isset($searchFields) && isset($searchTitle)) {
				$query = "SELECT * FROM $course WHERE divs='$selectedDiv' AND fields='$searchFields' AND title='$searchTitle'";
			}
			else if(!isset($searchFields) && isset($searchMajor) && isset($searchTitle)) {
				$query = "SELECT * FROM $course WHERE major='$searchMajor' AND title='$searchTitle'";
			}	
			else if(!isset($searchFields) && !isset($searchMajor) && isset($searchTitle)) {
				$query = "SELECT * FROM $course WHERE title='$searchTitle'";
			}
			else if(!isset($searchFields) && isset($searchMajor) && isset($searchTitle) && isset($searchGrade)) {
				$query = "SELECT * FROM $course WHERE divs='$selectedDiv' AND major='$searchMajor' AND title='$searchTitle' AND grade='$searchGrade'";	
			}
			else if(!isset($searchFields) && isset($searchMajor) && isset($searchTitle) && !isset($searchGrade)) {
				$query = "SELECT * FROM $course WHERE divs='$selectedDiv' AND major='$searchMajor' AND title='$searchTitle'";
			}
			
			$sql4coursesReturn = $conn->query($query);
			while($row4coursesReturn = $sql4coursesReturn->fetch_assoc()) {
				$dbResultReturn[] = $row4coursesReturn;
				$dbFieldsReturn[] = $row4coursesReturn['fields'];
				$dbDivsReturn[] = $row4coursesReturn['divs'];
				$dbCodeReturn[] = $row4coursesReturn['code'];
				$dbMajorReturn[] = $row4coursesReturn['major'];
				$dbTitleReturn[] = $row4coursesReturn['title'];
				$dbClassReturn[] = $row4coursesReturn['class'];
				$dbProfReturn[] = $row4coursesReturn['prof'];
				for($i=1; $i<=$numOfDays; $i++) {
					${dbDayReturn.$i}[] = $row4coursesReturn["day".$i];
					${dbMinReturn.$i}[] = $row4coursesReturn["min".$i];
					${dbTimeReturn.$i}[] = $row4coursesReturn["time".$i];
					${dbClassroomReturn.$i}[] = $row4coursesReturn["classroom".$i];
				}
				$dbDepartmentReturn[] = $row4coursesReturn['department'];
			}
			
			for($i=0; $i<count($dbTitleReturn); $i++) {
				// 교과목명 중복
				if($dbTitleReturn != array_unique($dbTitleReturn)) {
					// 교수명 중복
					if($dbProfReturn != array_unique($dbProfReturn)) {
						// 분반 중복
						if($dbClassReturn != array_unique($dbClassReturn)) {
							// 요일 및 시간 중복
							if($dbDayReturn1 != array_unique($dbDayReturn1)) {
								// 학부 중복
								if($dbDepartmentReturn != array_unique($dbDepartmentReturn)) {
									//학과 중복
									if($dbMajorReturn != array_unique($dbMajorReturn)) {
										$dbTitleResultReturn = FALSE;
									}
								} else {
									for($j=0; $j<count($dbTitleReturn); $j++) {
										if(empty($dbDepartmentReturn[$j])) {
											$dbDepartmentReturn[$j] = "전대학";
										}
										$dbTitleResultReturn[] = $dbTitleReturn[$j] . "(" . $dbDepartmentReturn[$j] . ")";
									}
									break;
								}
							} else {
								for($j=0; $j<count($dbTitleReturn); $j++) {
									if(empty($dbDayReturn2[$j])) {
										$dbTitleResultReturn[] = $dbTitleReturn[$j] . "(" . $dbDayReturn1[$j]  . "-" . $dbTimeReturn1[$j] . ")";
									} else {
										$dbTitleResultReturn[] = $dbTitleReturn[$j] . "(" . $dbDayReturn1[$j] . ", " . $dbDayReturn2[$j] . "-" . $dbTimeReturn1[$j] . ")";
									}
								}
								break;
							}
						} else {
							for($j=0; $j<count($dbTitleReturn); $j++) {
								$dbTitleResultReturn[] = $dbTitleReturn[$j] . "(" . $dbClassReturn[$j] . ")";
							}
							break;
						}
					} else {
						for($j=0; $j<count($dbTitleReturn); $j++) {
							$dbTitleResultReturn[] = $dbTitleReturn[$j] . "(" . $dbProfReturn[$j] . "교수님)";
						}
						break;
					}
				} else {
					for($j=0; $j<count($dbTitleReturn); $j++) {
						$dbTitleResultReturn[] = $dbTitleReturn[$j];
					}
					break;
				}
			}
			$result['condition'] = TRUE;
			$result['count'] = "single";
			$result['overlap'] = TRUE;
			$result['text'][0] = "<".$dbResult['title'].">은\n\n<" . $userInfo[$overlapKey[0]]['title'] . ">와 교과목명 혹은 시간이 중복됩니다.\n\n확인 후 교과목을 다시 선택해주세요.";
			$result['text'][1] = "<".$dbResult['title'].">은\n\n<" . $userInfo[$overlapKey[0]]['title'] . ">와 교과목명 혹은 시간이 중복됩니다.\n\n다시 한번 상세히 입력해주세요.";			
		}
		else if(!in_array(FALSE, $checkResult)) {
			$result['condition'] = TRUE;
			$result['count'] = "single";
			$result['overlap'] = FALSE;
			$result['dbInfo'] = array('divs' => $dbResult['divs'], 'fields' => $dbResult['fields'], 'major' => $dbResult['major'], 'title' => $dbResult['title'], 'code' => $dbResult['code'], 'class' => $dbResult['class'], 'prof' => $dbResult['prof'], 'department' => $dbResult['department'],
													'day1' => $dbResult['day1'], 'day2' => $dbResult['day2'], 'day3' => $dbResult['day3'], 'day4' => $dbResult['day4'], 'day5' => $dbResult['day5'], 'day6' => $dbResult['day6'],
													'time1' => $dbResult['time1'], 'time2' => $dbResult['time2'], 'time3' => $dbResult['time3'], 'time4' => $dbResult['time4'], 'time5' => $dbResult['time5'], 'time6' => $dbResult['time6'],
													'min1' => $dbResult['min1'], 'min2' => $dbResult['min2'], 'min3' => $dbResult['min3'], 'min4' => $dbResult['min4'], 'min5' => $dbResult['min5'], 'min6' => $dbResult['min6'],
													'classroom1' => $dbResult['classroom1'], 'classroom2' => $dbResult['classroom2'], 'classroom3' => $dbResult['classroom3'], 'classroom4' => $dbResult['classroom4'], 'classroom5' => $dbResult['classroom5'], 'classroom6' => $dbResult['classroom6']
													);
			$result['text'] = "<".$dbResult['title'].">은\n\n".$dbResult['department']."\n".$dbResult['class']."분반\n".$dbResult['prof']."교수님\n수업이 맞나요?";		
		} else {
			$result['condition'] = FALSE;
			$result['text'] = "ERROR : " . $inProgress;			
		}
	} else {
		$result['condition'] = FALSE;
		$result['text'] = "ERROR : " . $inProgress;				
	}

	return $result;
}

function optTitle($type)
{	
	global $senderID, $thisYear, $thisSemester, $conn, $inputTime, $userInfo, $inProgress;
	
	// 교과목 등록
	$query = "INSERT IGNORE INTO user (year, semester, userkey, divs, fields, major, title, code, class, prof, department, day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, inputTime)
							SELECT year, semester, userkey, divs, fields, major, title, code, class, prof, department, day1, day2, day3, day4, day5, day6, time1, time2, time3, time4, time5, time6, min1, min2, min3, min4, min5, min6, classroom1, classroom2, classroom3, classroom4, classroom5, classroom6, '$inputTime'
								FROM logging
								WHERE userkey='$senderID'
								ORDER BY inputTime DESC
								LIMIT 1";
	$conn->query($query);

	// 교과목 등록 processing 초기화
	$query = resetProcessing();
	$conn->query($query);
		
	// 몇 번째 등록인지 체크
	$query = "SELECT * FROM logging WHERE userkey='$senderID' AND year='$thisYear' AND semester='$thisSemester' AND inProgress='$inProgress' ORDER BY inputTime DESC LIMIT 1";
	$sql4logging = $conn->query($query)->fetch_assoc();
	
	// 마일리지
	mileageChange("courseRegister");
	
	$dbEmptyNum = count($userInfo) + 1;
	if($dbEmptyNum == 1) {
		$text = "<" . $sql4logging['title'] . ">이(가)\n" . $dbEmptyNum . "번째 항목에 추가되었습니다.\n\n교과목 등록에 관한 튜토리얼이 완료되었습니다.\n그리고 교과목 등록으로 인해 100포인트의 마일리지가 적립되었습니다.🤗\n\n버튼을 눌러 계속 진행해주세요.";
	} else {
		$text = "<" . $sql4logging['title'] . ">이(가)\n" . $dbEmptyNum . "번째 항목에 추가되었습니다.\n그리고 교과목 등록으로 인해 100포인트의 마일리지가 적립되었습니다.🤗\n\n버튼을 눌러 계속 진행해주세요";
	}
	
	return $text;
}

function mileageChange($type, $mileageChange=NULL, $mileageNote=NULL)
{
	global $senderID, $conn, $inputTime;
	
	$typeArr = array("courseRegister", "courseDelete", "eventRegister", "eventDelete", "attendance", "gifticon");
	
	if(in_array($type, $typeArr)) {
		$query = "SELECT sum FROM mileage WHERE userkey='$senderID' ORDER BY inputTime DESC LIMIT 1";
		$mileage = $conn->query($query)->fetch_assoc();	

		// 교과목 등록
		if($type == "courseRegister") {
			// 처음 등록
			if(!$mileage) {
				$mileage['sum'] = 0;
			}
			$mileageChange = 100;
			$mileageNote = 'registration of course';
		}
		// 교과목 삭제
		else if($type == "courseDelete") {
			$mileageChange = -100;
			$mileageNote = 'deletion of course';
		}
		// 이벤트 등록
		else if($type == "eventRegister") {
			$mileageChange = 20;
			$mileageNote = 'registration of event';				
		}
		// 이벤트 삭제
		else if($type == "eventDelete") {
			$mileageChange = -20;
			$mileageNote = 'deletion of event';			
		}
		// 출첵
		else if($type == "attendance") {
			$mileageChange = 50;
			$mileageNote = 'attendance';
		}
		else if($type == "gifticon") {
			if($mileageChange && $mileageNote) {
				$mileageChange = $mileageChange;
				$mileageNote = $type."_".$mileageNote;
			}
		}
		$mileageSum = $mileage['sum'] + $mileageChange;
		$query = "INSERT INTO mileage (userkey, changes, sum, note, inputTime)
													VALUE('$senderID', '$mileageChange', '$mileageSum', '$mileageNote', '$inputTime')";	
		$conn->query($query);
	}
}
