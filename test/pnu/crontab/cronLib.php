<?php
/*
function ForAlarm($userkey)
{
	global $userInfo, $inputTime, $conn;
	
	$query = "INSERT INTO logging (userkey, inProgress, inputTime) VALUE ('$userkey', 'READ_SUBJECT', '$inputTime')";
	$conn->query($query);
	
	$query = "SELECT * FROM user WHERE userkey='$userkey'";
	$sql4user = $conn->query($query);
	while($row4user = $sql4user->fetch_assoc()) {
		$user[] = $row4user;
	}
	
	$rgstedInfoDetail = registedConditionSubjectDetail($user);
	for($i=0; $i<count($rgstedInfoDetail['title']); $i++) {
		$titleName = $rgstedInfoDetail['titleName'][$i];
		$send['title'][] = $rgstedInfoDetail['title'][$i];
		$send['subtitle'][] = $rgstedInfoDetail['info'][$i];
		$send['payload'][] = "REGISTER-".$titleName;
	}
	$send['buttonsTitle'] = array("과제∙휴강∙시험 정보 등록하기");
	messageTemplateLeftSlide($send, $userkey);
	
	$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
	$send['payload'] = $send['title'] = array('시간표 보기', '초기화면');
	messageQR($send, $userkey);
}
*/

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////// course parsing //////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function createNewCourseTable($course)
{
	global $conn;
	
	// Create new course table
	$query = "CREATE TABLE $course (
						`index` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
						grade VARCHAR(255),
						department VARCHAR(255),
						major VARCHAR(255),
						majorCode VARCHAR(255),
						divs VARCHAR(255),
						fields VARCHAR(255),
						code VARCHAR(255),
						title VARCHAR(255),
						class VARCHAR(255),
						credit VARCHAR(255),
						prof VARCHAR(255),
						day1 VARCHAR(255), time1 VARCHAR(255), min1 VARCHAR(255), classroom1 VARCHAR(255),
						day2 VARCHAR(255), time2 VARCHAR(255), min2 VARCHAR(255), classroom2 VARCHAR(255),						
						day3 VARCHAR(255), time3 VARCHAR(255), min3 VARCHAR(255), classroom3 VARCHAR(255),						
						day4 VARCHAR(255), time4 VARCHAR(255), min4 VARCHAR(255), classroom4 VARCHAR(255),
						day5 VARCHAR(255), time5 VARCHAR(255), min5 VARCHAR(255), classroom5 VARCHAR(255),						
						day6 VARCHAR(255), time6 VARCHAR(255), min6 VARCHAR(255), classroom6 VARCHAR(255)
					)";
	$conn->query($query);
	/*
	$courseNameExp = explode(date("Y"), $course);
	$majorList = 'majorList' . date("Y") . $courseNameExp[1];
	
	$query = "SELECT * FROM $majorList";
	$sql4majorList = $conn->query($query);
	if($sql4majorList === FALSE) {
		// create new majorList table 
		$query = "CREATE TABLE $majorList (
							`index` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
							semester VARCHAR(255),
							year VARCHAR(255),
							major VARCHAR(255),
							majorCode VARCHAR(255)
						)";
		$conn->query($query);
		
		// insert date to majorList
		$userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.108 Safari/537.36';
		$cookieValue = getCookie($majorParsingUrl);
		$parsingHeaderRegular = array(
			"Content-Type: application/json",
		    "Referer: https://e-onestop.pusan.ac.kr/menu/class/C03/C03001?menuId=2000030301&rMenu=03",
			"Cookie: _ga=GA1.3.291618409.1513603678; WMONID={$cookieValue['WMONID']}; JSESSIONID={$cookieValue['JSESSIONID']}; _gid=GA1.3.1486345455.1517376601; _gat=1"	
		);
		$parsingHeaderSeason = array(
			"Content-Type: application/json",
		    "Referer: https://e-onestop.pusan.ac.kr/menu/class/C03/C03005?menuId=2000030305&rMenu=03",
			"Cookie: _ga=GA1.3.291618409.1513603678; WMONID={$cookieValue['WMONID']}; JSESSIONID={$cookieValue['JSESSIONID']}; _gid=GA1.3.1486345455.1517376601;  _gat=1"	
		);
		$majorParsingUrl = "https://e-onestop.pusan.ac.kr/middleware/curriculum/college/curriculumCollegeDetailListSearch";
		$majorParsingData = '{"pName":["YEAR","TERM","SUBJECT"],"pValue":["' . date("Y") . '","' . $division  . '","' . $section  .'"]}';
		$result = DatasetCurl($majorParsingUrl, $parsingHeaderRegular, $userAgent, $majorParsingData);
		
		for($i=0; $i<count($result); $i++) {
			$query = "INSERT INTO $majorList (
																		year, semester, major, majorCode
																	) 
														VALUE (
																		" . date("Y") . ", {$courseNameExp[1]}, {$result[$i]['학과명']}, {$result[$i]['학과코드']}
																	)";
			$conn->query($query);									
		}
	}*/
}

function getCookie($url)
{
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	$result = curl_exec($ch);
	preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);
	$cookies = array();
	foreach($matches[1] as $item) {
	    parse_str($item, $cookie);
	    $cookies = array_merge($cookies, $cookie);
	}
	curl_close($ch);
	return $cookies;
}

function getHeader($semester, $url)
{
	$cookieValue = getCookie($url);
	if($semester == 'regular') {
		$header = array(
			"Content-Type: application/json",
		    "Referer: https://e-onestop.pusan.ac.kr/menu/class/C03/C03001?menuId=2000030301&rMenu=03",
			"Cookie: _ga=GA1.3.291618409.1513603678; WMONID={$cookieValue['WMONID']}; JSESSIONID={$cookieValue['JSESSIONID']}; _gid=GA1.3.1486345455.1517376601; _gat=1"	
		);		
	}
	else if($semester == 'season') {
		$header = array(
			"Content-Type: application/json",
		    "Referer: https://e-onestop.pusan.ac.kr/menu/class/C03/C03005?menuId=2000030305&rMenu=03",
			"Cookie: _ga=GA1.3.291618409.1513603678; WMONID={$cookieValue['WMONID']}; JSESSIONID={$cookieValue['JSESSIONID']}; _gid=GA1.3.1486345455.1517376601;  _gat=1"	
		);		
	}
	return $header;
}

function DatasetCurl($url, $header=array(), $data=array()) {
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	//curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	$result = curl_exec($ch);
	$resultJson =  json_decode($result, TRUE);
	curl_close($ch);
	return $resultJson['dataset1'];
}

function DatasetParsing($semester, $division, $section=NULL)
{
	
	// $semester													// $division 																									// $section
																																																//// 정규 == 'regular'
	//// 정규 == 'regular', 계절 == 'season'			//// 1학기 == '10', 2학기 == '20', 여름계절 == '11', 겨울계절 == '21'			////// 전공,교직과목(2,3,4학년) == '1'	=> 학과조회
																																																////// 전공기초 및 기타 1학년 == '2' 	=> 학과조회
																																																////// 교양선택및일반선택 == '3' 	=> data 필요X
																																																////// 교양필수(정보화소양) == '4' => 학과조회
																																																////// 일반선택 == '5' => 바로 검색
																																																
																																																//// 계절 == 'season'
																																																////// $section => 필요X, 모든과목 한꺼번에 조회
														
	global $thisYear, $now;											
																																																					
	if($semester == "regular") {
		if($section == '1' || $section == '2' || $section == '4') {
			// 학과조회
			$majorParsingUrl = "https://e-onestop.pusan.ac.kr/middleware/curriculum/college/curriculumCollegeDetailListSearch";
			$majorParsingHeader = getHeader($semester, $majorParsingUrl);
			$majorParsingData = '{"pName":["YEAR","TERM","SUBJECT"],"pValue":["' . $thisYear . '","' . $division  . '","' . $section  .'"]}';
			$result = DatasetCurl($majorParsingUrl, $majorParsingHeader, $majorParsingData);

			$resultCount = count($result);
			if($resultCount > 200) {
				$resultChunk = array_chunk($result, ($resultCount / 10));
				// chunk 후 나머지가 있을 때, 나머지 값 => $resultChunk[10]
				if($resultChunk[10]) {
					foreach($resultChunk[9] as $key=>$value) {
						$keys[] = $key;
					}
					$lastKey = end($keys);
					for($i=0; $i<count($resultChunk[10]); $i++) {
						// chunk 후 마지막 배열에 나머지 값 모두 넣음
						$resultChunk[9][($lastKey+($i+1))] = $resultChunk[10][$i];
					}
					// 나머지 값(배열) unset
					unset($resultChunk[10]);
				}
			
				$eachMin = substr(date("i", $now),1,2);
				if($eachMin == 0) {
					$initV = 0;
					$finV = count($resultChunk[0]);
				}
				else if($eachMin == 1) {
					$initV = count($resultChunk[0]);
					$finV = count($resultChunk[0]) + count($resultChunk[1]);					
				}
				else if($eachMin == 2) {
					$initV = count($resultChunk[0]) + count($resultChunk[1]);
					$finV = count($resultChunk[0]) + count($resultChunk[1]) + count($resultChunk[2]);
				}
				else if($eachMin == 3) {
					$initV = count($resultChunk[0]) + count($resultChunk[1]) + count($resultChunk[2]);
					$finV = count($resultChunk[0]) + count($resultChunk[1]) + count($resultChunk[2]) + count($resultChunk[3]);
				}
				else if($eachMin == 4) {
					$initV = count($resultChunk[0]) + count($resultChunk[1]) + count($resultChunk[2]) + count($resultChunk[3]);
					$finV = count($resultChunk[0]) + count($resultChunk[1]) + count($resultChunk[2]) + count($resultChunk[3]) + count($resultChunk[4]);
				}
				else if($eachMin == 5) {
					$initV = count($resultChunk[0]) + count($resultChunk[1]) + count($resultChunk[2]) + count($resultChunk[3]) + count($resultChunk[4]);
					$finV = count($resultChunk[0]) + count($resultChunk[1]) + count($resultChunk[2]) + count($resultChunk[3]) + count($resultChunk[4]) + count($resultChunk[5]);
				}
				else if($eachMin == 6) {
					$initV = count($resultChunk[0]) + count($resultChunk[1]) + count($resultChunk[2]) + count($resultChunk[3]) + count($resultChunk[4]) + count($resultChunk[5]);
					$finV = count($resultChunk[0]) + count($resultChunk[1]) + count($resultChunk[2]) + count($resultChunk[3]) + count($resultChunk[4]) + count($resultChunk[5]) + count($resultChunk[6]);
				}
				else if($eachMin == 7) {
					$initV = count($resultChunk[0]) + count($resultChunk[1]) + count($resultChunk[2]) + count($resultChunk[3]) + count($resultChunk[4]) + count($resultChunk[5]) + count($resultChunk[6]);
					$finV = count($resultChunk[0]) + count($resultChunk[1]) + count($resultChunk[2]) + count($resultChunk[3]) + count($resultChunk[4]) + count($resultChunk[5]) + count($resultChunk[6]) + count($resultChunk[7]);
				}
				else if($eachMin == 8) {
					$initV = count($resultChunk[0]) + count($resultChunk[1]) + count($resultChunk[2]) + count($resultChunk[3]) + count($resultChunk[4]) + count($resultChunk[5]) + count($resultChunk[6]) + count($resultChunk[7]);
					$finV = count($resultChunk[0]) + count($resultChunk[1]) + count($resultChunk[2]) + count($resultChunk[3]) + count($resultChunk[4]) + count($resultChunk[5]) + count($resultChunk[6]) + count($resultChunk[7]) + count($resultChunk[8]);
				}
				else if($eachMin == 9) {
					$initV = count($resultChunk[0]) + count($resultChunk[1]) + count($resultChunk[2]) + count($resultChunk[3]) + count($resultChunk[4]) + count($resultChunk[5]) + count($resultChunk[6]) + count($resultChunk[7]) + count($resultChunk[8]);
					$finV = count($resultChunk[0]) + count($resultChunk[1]) + count($resultChunk[2]) + count($resultChunk[3]) + count($resultChunk[4]) + count($resultChunk[5]) + count($resultChunk[6]) + count($resultChunk[7]) + count($resultChunk[8]) + count($resultChunk[9]);
				}
			} else {
				$initV = 0;
				$finV = $resultCount;
			}
			for($i=$initV; $i<$finV; $i++) {
				// 해당학과 과목조회
				$titleParsingUrl = "https://e-onestop.pusan.ac.kr/middleware/curriculum/college/CollegeAssignInfoSearch";
				$titleParsingHeader = getHeader($semester, $titleParsingUrl);
				$titleParsingData = '{"pName":["YEAR","TERM","DEPTCD","CULTCD","GUBUN"],"pValue":["' . $thisYear . '","' . $division  . '","' . $result[$i]['학과코드'] . '","","' . $section  .'"]}';
				$titleParsingResult = DatasetCurl($titleParsingUrl, $titleParsingHeader, $titleParsingData);
				$result[$i]['교과목'] = $titleParsingResult;
			}
		}
		else if($section == '3') {
			// 교양 영역 목록 조회
			$divParsingUrl = "https://e-onestop.pusan.ac.kr/middleware/curriculum/lliveralArts/lliveralArtsAreaSearch";
			$divParsingHeader = getHeader($semester, $divParsingUrl);
			$divParsingData = '{"pName":[],"pValue":[]}';
			$divParsingResult = DatasetCurl($divParsingUrl, $divParsingHeader, $divParsingData);
			$divParsingResultCount = count($divParsingResult);
			for($i=0; $i<$divParsingResultCount; $i++) {
				if($divParsingResult[$i]['년도'] >= '2013') {
					$result[] = $divParsingResult[$i];
				}
			}
			
			$resultCount = count($result);
			for($i=0; $i<$resultCount; $i++) {
				// 해당학과 과목조회
				$divsParsingUrl = "https://e-onestop.pusan.ac.kr/middleware/curriculum/college/CollegeAssignInfoSearch";
				$divsParsingHeader = getHeader($semester, $divsParsingUrl);
				$divsParsingData = '{"pName":["YEAR","TERM","DEPTCD","CULTCD","GUBUN"],"pValue":["' . $thisYear . '","' . $division . '","","' . $result[$i]['교양영역코드'] . '","' . $section  .'"]}';
				$divsParsingResult = DatasetCurl($divsParsingUrl, $divsParsingHeader, $divsParsingData);
				$result[$i]['교과목'] = $divsParsingResult;
			}
		}
		else if($section == '5') {
			// 일반선택 조회
			$generalCode = '11665';		// fixed value ?
			$generalParsingUrl = "https://e-onestop.pusan.ac.kr/middleware/curriculum/college/CollegeAssignInfoSearch";
			$generalParsingHeader = getHeader($semester, $generalParsingUrl);
			$generalParsingData = '{"pName":["YEAR","TERM","DEPTCD","CULTCD","GUBUN"],"pValue":["' . $thisYear . '","' . $division  . '","' . $generalCode . '","","' . $section  .'"]}';
			$result = DatasetCurl($generalParsingUrl, $generalParsingHeader, $generalParsingData);		
		}
	}
	else if($semester == "season") {
		// 계절학기 과목 조회
		$seasonParsingUrl = "https://e-onestop.pusan.ac.kr/middleware/study/sessionTermManual/sessionTermManualCheck";
		$seasonParsingHeader = getHeader($semester, $seasonParsingUrl);
		$seasonParsingData = '{"pName":["YEAR","TERM"],"pValue":["' . $thisYear . '","' . $division  . '"]}';
		$result = DatasetCurl($seasonParsingUrl, $seasonParsingHeader, $seasonParsingData);			
	}
	
	return $result;
}

function DatasetSort($dataset, $semester)
{
	if($semester == 'regular') {
		for($i=0; $i<count($dataset); $i++) {
			if(!empty($dataset[$i]['교과목']) && count($dataset[$i]['교과목']) > 0) {
				// 교양X => 학부명(=NULL) && 학과명 && 학과코드
				if($dataset[$i]['학과명']) {
					$majorNameExp = explode(" ", $dataset[$i]['학과명']);
					if(strpos($dataset[$i]['학과명'], "-") !== FALSE) {
						$data[$i]['department'] = $majorNameExp[0];
						$data[$i]['major'] = $majorNameExp[2];
						$data[$i]['majorCode'] = preg_replace("/[^0-9]*/s", "", $majorNameExp[3]); 
					} else {
						$data[$i]['department'] = NULL;
						$data[$i]['major'] = $majorNameExp[0];
						$data[$i]['majorCode'] = preg_replace("/[^0-9]*/s", "", $majorNameExp[1]); 	
					}			
				}
				// 교양O => 영역분류
				else if($dataset[$i]['교양영역명']) {
					$liberalNameExp = explode(" : ", $dataset[$i]['교양영역명']);
					$data[$i]['fields'] = $liberalNameExp[0];
				}
		
				// 교과목 정보
				$subject = $dataset[$i]['교과목'];
				for($j=0; $j<count($subject); $j++) {
					//교과목명
					$data[$i]['subject'][$j]['title'] = $subject[$j]['교과목명'];
					
					// 교양과목일 경우, 수강대상대학존재 => 대학명==department
					if($subject[$j]['대학명']) {
						$data[$i]['subject'][$j]['department'] = $subject[$j]['대학명'];
					}
					
					// 학점
					if($subject[$j]['학점'] == 0) {
						$data[$i]['subject'][$j]['credit'] = NULL;
					} else {
						$data[$i]['subject'][$j]['credit'] = $subject[$j]['학점'];
					}
					
					// 학년
					if($subject[$j]['학년'] == 0) {
						$data[$i]['subject'][$j]['grade'] = NULL;
					} else {
						$data[$i]['subject'][$j]['grade'] = $subject[$j]['학년'];
					}
					
					// 과목코드
					$data[$i]['subject'][$j]['code'] = $subject[$j]['교과목번호'];
					
					// 교수명
					$data[$i]['subject'][$j]['prof'] = $subject[$j]['교수명'];
					
					// 분반
					$data[$i]['subject'][$j]['class'] = $subject[$j]['분반'];
					
					// 수업시간
					if($subject[$j]['강의시간']) {
						$lectureTimeExp1st = explode(",", $subject[$j]['강의시간']);
						for($k=0; $k<count($lectureTimeExp1st); $k++) {
							$lectureTimeExp2nd = explode(" ", $lectureTimeExp1st[$k]);
							// 요일
							$data[$i]['subject'][$j]['day'.($k+1)] = $lectureTimeExp2nd[0];
							
							// 시작시간 && 러닝시간
							//// ex1) 화 09:00(50) Y04-301,화 11:00(50) Y04-301
							if(substr_count($lectureTimeExp1st[$k], "(") > 0) {
								$lectureTimeExp3rd = explode("(", $lectureTimeExp2nd[1]);
								$data[$i]['subject'][$j]['time'.($k+1)] = $lectureTimeExp3rd[0];
								$data[$i]['subject'][$j]['min'.($k+1)] = preg_replace("/[^0-9]*/s", "", $lectureTimeExp3rd[1]);
							}
							//// ex2) 화 08:00-11:00 Y04-102,수 08:00-11:00 Y04-201,수 11:00-14:00 Y04-201,수 14:00-17:00 Y04-201	
							else if(substr_count($lectureTimeExp1st[$k], "-") > 1) {
								$lectureTimeExp3rd = explode("-", $lectureTimeExp2nd[1]);
								$data[$i]['subject'][$j]['time'.($k+1)] = $lectureTimeExp3rd[0];
								$data[$i]['subject'][$j]['min'.($k+1)] = (strtotime($lectureTimeExp3rd[1]) - strtotime($lectureTimeExp3rd[0])) / 60;
							}
							
							// 강의실
							$data[$i]['subject'][$j]['classroom'.($k+1)] = $lectureTimeExp2nd[2];
						}
					}
			
					// 분류
					if($subject[$j]['구분']) {
						$divsMajor = array('전공필수', '전공선택', '교직과목');
						$divsMajorBasic = array('전공기초');
						$divsLiberal = array('교양선택');
						$divsLiberalEssn = array('교양필수');
						$divsGeneral = array('일반선택');
						if(in_array($subject[$j]['구분'], $divsMajor)) {
							$data[$i]['subject'][$j]['divs'] = "전공";
						}
						else if(in_array($subject[$j]['구분'], $divsMajorBasic)) {
							$data[$i]['subject'][$j]['divs'] = "전공기초";
						}
						else if(in_array($subject[$j]['구분'], $divsLiberal)) {
							$data[$i]['subject'][$j]['divs'] = "교양";
						}
						else if(in_array($subject[$j]['구분'], $divsLiberalEssn)) {
							$data[$i]['subject'][$j]['divs'] = "교양필수";
						}
						else if(in_array($subject[$j]['구분'], $divsGeneral)) {
							$data[$i]['subject'][$j]['divs'] = "일반선택";
						}
					}
					if(preg_match("/미확정/", $data[$i]['subject'][$j]['title']) || preg_match("/미지정/", $data[$i]['subject'][$j]['title']) || preg_match("/미지정/", $data[$i]['subject'][$j]['prof'])) {
						unset($data[$i]['subject'][$j]);
						$data[$i]['subject'][$j] = NULL;
					}
				}
			}
			// 일반선택의 경우,
			else if(!isset($dataset[$i]['교과목']) && $dataset[$i]['구분'] == '일반선택') {
				// 분류
				$data[$i]['subject'][0]['divs'] = $dataset[$i]['구분'] = "일반선택";				

				//교과목명
				$data[$i]['subject'][0]['title'] = $dataset[$i]['교과목명'];
				
				// 학점
				if($dataset[$i]['학점'] == 0) {
					$data[$i]['subject'][0]['credit'] = NULL;
				} else {
					$data[$i]['subject'][0]['credit'] = $dataset[$i]['학점'];
				}
				
				// 학년
				if($dataset[$i]['학년'] == 0) {
					$data[$i]['subject'][0]['grade'] = NULL;
				} else {
					$data[$i]['subject'][0]['grade'] = $dataset[$i]['학년'];
				}
				
				// 과목코드
				$data[$i]['subject'][0]['code'] = $dataset[$i]['교과목번호'];
				
				// 교수명
				$data[$i]['subject'][0]['prof'] = $dataset[$i]['교수명'];
				
				// 분반
				$data[$i]['subject'][0]['class'] = $dataset[$i]['분반'];
	
				// 수업시간
				if($dataset[$i]['강의시간']) {
					$lectureTimeExp1st = explode(",", $dataset[$i]['강의시간']);
					for($k=0; $k<count($lectureTimeExp1st); $k++) {
						$lectureTimeExp2nd = explode(" ", $lectureTimeExp1st[$k]);
					
						if(count($lectureTimeExp2nd) == 3) {
							// 요일
							$data[$i]['subject'][0]['day'.($k+1)] = $lectureTimeExp2nd[0];
								
							// 시작시간 && 러닝시간
							//// ex1) 화 09:00(50) Y04-301,화 11:00(50) Y04-301
							if(substr_count($lectureTimeExp1st[$k], "(") > 0) {
								$lectureTimeExp3rd = explode("(", $lectureTimeExp2nd[1]);
								$data[$i]['subject'][0]['time'.($k+1)] = $lectureTimeExp3rd[0];
								$data[$i]['subject'][0]['min'.($k+1)] = preg_replace("/[^0-9]*/s", "", $lectureTimeExp3rd[1]);
							}
							//// ex2) 화 08:00-11:00 Y04-102,수 08:00-11:00 Y04-201,수 11:00-14:00 Y04-201,수 14:00-17:00 Y04-201	
							else if(substr_count($lectureTimeExp1st[$k], "-") > 1) {
								$lectureTimeExp3rd = explode("-", $lectureTimeExp2nd[1]);
								$data[$i]['subject'][0]['time'.($k+1)] = $lectureTimeExp3rd[0];
								$data[$i]['subject'][0]['min'.($k+1)] = (strtotime($lectureTimeExp3rd[1]) - strtotime($lectureTimeExp3rd[0])) / 60;
							}
							
							// 강의실
							$data[$i]['subject'][0]['classroom'.($k+1)] = $lectureTimeExp2nd[2];
						}
						else if(count($lectureTimeExp2nd) < 3) {
							
							// ex3) 토 사이버수업
												
							// 강의실
							// $data[$i]['subject'][0]['classroom'.($k+1)] = $lectureTimeExp2nd[1];
						}
					}
				}
				if(preg_match("/미확정/", $data[$i]['subject'][0]['title']) || preg_match("/미지정/", $data[$i]['subject'][0]['title']) || preg_match("/미지정/", $data[$i]['subject'][0]['prof'])) {
					unset($data[$i]['subject'][0]);
					$data[$i]['subject'][0] = NULL;
				}
			} else {
				$data[$i] = NULL;
			}
		}
	}
	else if($semester == 'season') {
		for($i=0; $i<count($dataset); $i++) {
			//교과목명
			$titleName = $dataset[$i]['교과목명'];
			if(preg_match("/[0-9]영역/", $titleName)) {
				$data[$i]['subject'][0]['fields'] = preg_replace("/[^0-9]*/s", "", $titleName) . "영역";
				if(substr_count($titleName, "(") == 1) {
					$titleNameExp = explode("(", $titleName);
					$data[$i]['subject'][0]['title'] = $titleNameExp[0];
				}
				else if(substr_count($titleName, "(") == 2) {
					$titleNameExp = explode("(", str_replace(")", "", $titleName));
					$data[$i]['subject'][0]['title'] = $titleNameExp[0] . "(" . $titleNameExp[1] . ")";
				}
			} else {
				$data[$i]['subject'][0]['title'] = $titleName;
			}
			
			// 학점
			if($dataset[$i]['학점'] == 0) {
				$data[$i]['subject'][0]['credit'] = NULL;
			} else {
				$data[$i]['subject'][0]['credit'] = $dataset[$i]['학점'];
			}
			
			// 학년
			if($dataset[$i]['학년'] == 0) {
				$data[$i]['subject'][0]['grade'] = NULL;
			} else {
				$data[$i]['subject'][0]['grade'] = $dataset[$i]['학년'];
			}
			
			// 과목코드
			$data[$i]['subject'][0]['code'] = $dataset[$i]['교과목번호'];
			
			// 교수명
			$data[$i]['subject'][0]['prof'] = $dataset[$i]['교수명'];
			
			// 분반
			$data[$i]['subject'][0]['class'] = $dataset[$i]['분반'];
			
			// 수업시간
			if($dataset[$i]['강의시간']) {
				$lectureTimeExp1st = explode(",", $dataset[$i]['강의시간']);
				for($k=0; $k<count($lectureTimeExp1st); $k++) {
					$lectureTimeExp2nd = explode(" ", $lectureTimeExp1st[$k]);

					if(count($lectureTimeExp2nd) == 3) {
						// 요일
						$data[$i]['subject'][0]['day'.($k+1)] = $lectureTimeExp2nd[0];
									
						// 시작시간 && 러닝시간
						//// ex1) 화 09:00(50) Y04-301,화 11:00(50) Y04-301
						if(substr_count($lectureTimeExp1st[$k], "(") > 0) {
							$lectureTimeExp3rd = explode("(", $lectureTimeExp2nd[1]);
							$data[$i]['subject'][0]['time'.($k+1)] = $lectureTimeExp3rd[0];
							$data[$i]['subject'][0]['min'.($k+1)] = preg_replace("/[^0-9]*/s", "", $lectureTimeExp3rd[1]);
						}
						//// ex2) 화 08:00-11:00 Y04-102,수 08:00-11:00 Y04-201,수 11:00-14:00 Y04-201,수 14:00-17:00 Y04-201	
						else if(substr_count($lectureTimeExp1st[$k], "-") > 1) {
							$lectureTimeExp3rd = explode("-", $lectureTimeExp2nd[1]);
							$data[$i]['subject'][0]['time'.($k+1)] = $lectureTimeExp3rd[0];
							$data[$i]['subject'][0]['min'.($k+1)] = (strtotime($lectureTimeExp3rd[1]) - strtotime($lectureTimeExp3rd[0])) / 60;
						}
						
						// 강의실
						$data[$i]['subject'][0]['classroom'.($k+1)] = $lectureTimeExp2nd[2];
					}
					else if(count($lectureTimeExp2nd) < 3) {
						
						// ex3) 토 사이버수업
											
						// 강의실
						// $data[$i]['subject'][0]['classroom'.($k+1)] = $lectureTimeExp2nd[1];
					}
				}
			}
	
			// 분류
			if($dataset[$i]['구분']) {
				$divsMajor = array('전공필수', '전공선택', '교직과목');
				$divsMajorBasic = array('전공기초');
				$divsLiberal = array('교양선택');
				$divsLiberalEssn = array('교양필수');
				$divsGeneral = array('일반선택');
				if(in_array($dataset[$i]['구분'], $divsMajor)) {
					$data[$i]['subject'][0]['divs'] = "전공";
				}
				else if(in_array($dataset[$i]['구분'], $divsMajorBasic)) {
					$data[$i]['subject'][0]['divs'] = "전공기초";
				}
				else if(in_array($dataset[$i]['구분'], $divsLiberal)) {
					$data[$i]['subject'][0]['divs'] = "교양";
				}
				else if(in_array($dataset[$i]['구분'], $divsLiberalEssn)) {
					$data[$i]['subject'][0]['divs'] = "교양필수";
				}
				else if(in_array($dataset[$i]['구분'], $divsGeneral)) {
					$data[$i]['subject'][0]['divs'] = "일반선택";
				}
			}
			if(preg_match("/미확정/", $data[$i]['subject'][0]['title']) || preg_match("/미지정/", $data[$i]['subject'][0]['title']) || preg_match("/미지정/", $data[$i]['subject'][0]['prof'])) {
				unset($data[$i]['subject'][0]);
				$data[$i]['subject'][0] = NULL;
			}
		}
	}

	return $data;
}

function DatasetInsert($course, $data)
{
	global $conn;
	
	$insertLimit = 0;
	for($i=0; $i<count($data); $i++) {
		if(!is_array($data[$i])) {
			continue;
		} else {
			$department = $data[$i]['department'];
			$major = $data[$i]['major'];
			$majorCode = $data[$i]['majorCode'];
			$fields = $data[$i]['fields'];
			$subject = $data[$i]['subject'];
			for($j=0; $j<count($subject); $j++) {
				if($insertLimit < 150) {
					$title = $subject[$j]['title'];
					$credit = $subject[$j]['credit'];
					$grade = $subject[$j]['grade'];
					$code = $subject[$j]['code'];
					$prof = $subject[$j]['prof'];
					$class = $subject[$j]['class'];
					$divs = $subject[$j]['divs'];
					for($k=1; $k<=6; $k++) {
						${'day'.$k} = $subject[$j]['day'.$k];
						${'time'.$k} = $subject[$j]['time'.$k];
						${'min'.$k} = $subject[$j]['min'.$k];
						${'classroom'.$k} = $subject[$j]['classroom'.$k];
					}
					if(!empty($subject[$j]['department'])) {
						$department = $subject[$j]['department'];
					}
					
					if(!is_array($subject[$j])) {
						continue;
					} else {
						// title, class, code, divs가 같으면 동일한 수업으로 판단
						if(empty($major)) {
							$query = "SELECT * FROM $course WHERE title='$title' AND code='$code' AND class='$class' AND	divs='$divs' LIMIT 1";
						} else {
							// 교양필수의 경우, 다른 학과지만 같은 과목 존재
							$query = "SELECT * FROM $course WHERE title='$title' AND code='$code' AND class='$class' AND	divs='$divs' AND major='$major' LIMIT 1";
						}
						$result = $conn->query($query)->fetch_assoc();
						if(!$result) {
							// INSERT
							$query = "INSERT INTO $course (
																					major, majorCode, title, credit, grade, code, prof, class, divs, fields, department, 
																					day1, time1, min1, classroom1, day2, time2, min2, classroom2, day3, time3, min3, classroom3,
																					day4, time4, min4, classroom4, day5, time5, min5, classroom5, day6, time6, min6, classroom6
																				)
																	VALUE (
																					'$major', '$majorCode', '$title', '$credit', '$grade', '$code', '$prof', '$class', '$divs', '$fields', '$department',
																					'$day1', '$time1', '$min1', '$classroom1', '$day2', '$time2', '$min2', '$classroom2', '$day3', '$time3', '$min3', '$classroom3',
																					'$day4', '$time4', '$min4', '$classroom4', '$day5', '$time5', '$min5', '$classroom5', '$day6', '$time6', '$min6', '$classroom6'
																				)
											";
							$conn->query($query);
							++$insertLimit;
						} else {
							/*
							// 교수명 업데이트
							if(empty($prof) || $prof = '') {
								if(empty($major) || $major = '') {
									$query = "UPDATE $course SET prof='$prof' WHERE title='$title' AND code='$code' AND class='$class' AND divs='$divs'";
								} else {
									$query = "UPDATE $course SET prof='$prof' WHERE title='$title' AND code='$code' AND class='$class' AND divs='$divs' AND major='$major'";
								}
								$conn->query($query);
								++$insertLimit;
							}*/
						}
						
						
						/*
						$overlapCheck = array();
						// Check overlap from All DB data
						for($m=0; $m<$resCount; $m++) {
							$resTitle = $res[$m]['title'];
							$resCode = $res[$m]['code'];
							$resClass = $res[$m]['class'];
							$resDivs = $res[$m]['divs'];
							$resMajor = $res[$m]['major'];
							
							// title, class, code, divs가 같으면 동일한 수업으로 판단
							if(empty($major)) {
								if($resTitle == $title && $resCode == $code && $resClass == $class && $resDivs == $divs) {
									$overlapCheck[] = FALSE;
									break;
								}
							} else {
								// 교양필수의 경우, 다른 학과지만 같은 과목 존재
								if($resTitle == $title && $resCode == $code && $resClass == $class && $resDivs == $divs && ($resMajor == $major && !empty($resMajor))) {
									$overlapCheck[] = FALSE;
									break;
								}
							}
						}
						if(!in_array(FALSE, $overlapCheck)) {
							// Overlap X => INSERT
							$query = "INSERT INTO $course (
																					major, majorCode, title, credit, grade, code, prof, class, divs, fields, department, 
																					day1, time1, min1, classroom1, day2, time2, min2, classroom2, day3, time3, min3, classroom3,
																					day4, time4, min4, classroom4, day5, time5, min5, classroom5, day6, time6, min6, classroom6
																				)
																	VALUE (
																					'$major', '$majorCode', '$title', '$credit', '$grade', '$code', '$prof', '$class', '$divs', '$fields', '$department',
																					'$day1', '$time1', '$min1', '$classroom1', '$day2', '$time2', '$min2', '$classroom2', '$day3', '$time3', '$min3', '$classroom3',
																					'$day4', '$time4', '$min4', '$classroom4', '$day5', '$time5', '$min5', '$classroom5', '$day6', '$time6', '$min6', '$classroom6'
																				)
											";
							$conn->query($query);
							++$insertLimit;						
						} else {
							// Overlap O => UPDATE
							//// 교수명 업데이트
							
							if(empty($prof) || $prof = '') {
								if(empty($major) || $major = '') {
									$query = "UPDATE $course SET prof='$prof' WHERE title='$title' AND code='$code' AND class='$class' AND divs='$divs'";
								} else {
									$query = "UPDATE $course SET prof='$prof' WHERE title='$title' AND code='$code' AND class='$class' AND divs='$divs' AND major='$major'";
								}
								$conn->query($query);
								++$insertLimit;
							}
						}*/
						
						
						
					}
				}
			}
		}		
	}
}

function DatasetMajorUpdate($course, $division, $semester)
{
	//
	// 만약 전공코드는 존재하는데 학과명이 비어있을 경우, 각 전공코드의 해당 학과명을 찾아 UPDATE.
	//
	global $conn, $thisYear;

	$query = "SELECT * FROM $course WHERE majorCode!='' AND major=''";
	$sql4course = $conn->query($query);
	while($row4course = $sql4course->fetch_assoc()) {
		$emptyMajorCode[] = $row4course['majorCode'];
		$emptyMajorCode = array_keys(array_flip($emptyMajorCode));
	}
	
	if($semester == "regular") {
		// 학과조회
		$sectionArr = array('1', '2', '4');
		for($s=0; $s<count($sectionArr); $s++) {
			$majorParsingUrl = "https://e-onestop.pusan.ac.kr/middleware/curriculum/college/curriculumCollegeDetailListSearch";
			$majorParsingHeader = getHeader($semester, $majorParsingUrl);
			$majorParsingData = '{"pName":["YEAR","TERM","SUBJECT"],"pValue":["' . $thisYear . '","' . $division  . '","' . $sectionArr[$s]  .'"]}';
			$parsingResult = DatasetCurl($majorParsingUrl, $majorParsingHeader, $majorParsingData);
			
			for($i=0; $i<count($emptyMajorCode); $i++) {
				for($j=0; $j<count($parsingResult); $j++) {
					if($parsingResult[$j]['학과코드'] == $emptyMajorCode[$i]) {
						$majorNameExp = explode(" ", $parsingResult[$j]['학과명']);
						if(strpos($parsingResult[$j]['학과명'], "-") !== FALSE) {
							$emptyDepartment = $majorNameExp[0];
							$emptyMajor = $majorNameExp[2];
						} else {
							$emptyDepartment = NULL;
							$emptyMajor = $majorNameExp[0];
						}
						
						$query = "UPDATE $course SET department='$emptyDapartment', major='$emptyMajor' WHERE majorCode='{$emptyMajorCode[$i]}'";
						$conn->query($query);
					}
				}
			}			
		}
	 }
	else if($semester == "season") {
		// 계절학기 과목 조회
		$seasonParsingUrl = "https://e-onestop.pusan.ac.kr/middleware/study/sessionTermManual/sessionTermManualCheck";
		$seasonParsingHeader = getHeader($semester, $seasonParsingUrl);
		$seasonParsingData = '{"pName":["YEAR","TERM"],"pValue":["' . $thisYear . '","' . $division  . '"]}';
		$parsingResult = DatasetCurl($seasonParsingUrl, $seasonParsingHeader, $seasonParsingData);	
		for($i=0; $i<count($emptyMajorCode); $i++) {
			for($j=0; $j<count($parsingResult); $j++) {
				if($parsingResult[$j]['학과코드'] == $emptyMajorCode[$i]) {
					$majorNameExp = explode(" ", $parsingResult[$j]['학과명']);
					if(strpos($parsingResult[$j]['학과명'], "-") !== FALSE) {
						$emptyDepartment = $majorNameExp[0];
						$emptyMajor = $majorNameExp[2];
					} else {
						$emptyDepartment = NULL;
						$emptyMajor = $majorNameExp[0];
					}
					
					$query = "UPDATE $course SET department='$emptyDapartment', major='$emptyMajor' WHERE majorCode='{$emptyMajorCode[$i]}'";
					$conn->query($query);
				}
			}
		}	
	}
}

function DatasetProfUpdate($course, $division, $semester)
{
	global $conn, $thisYear;

	$query = "SELECT * FROM $course WHERE prof=''";
	$sql = $conn->query($query);
	while($row = $sql->fetch_assoc()) {
		$emptyProfResult[] = $row;
	}
	$emptyProfResultCount = count($emptyProfResult);
	for($i=0; $i<$emptyProfResultCount; $i++) {
		if($semester == 'regular') {
			if($emptyProfResult[$i]['divs'] == '전공' || $emptyProfResult[$i]['divs'] == '전공기초') {
				$sectionArr = array(1, 2);
				for($j=0; $j<count($sectionArr); $j++) {
					$titleParsingUrl = "https://e-onestop.pusan.ac.kr/middleware/curriculum/college/CollegeAssignInfoSearch";
					$titleParsingHeader = getHeader($semester, $titleParsingUrl);
					$titleParsingData = '{"pName":["YEAR","TERM","DEPTCD","CULTCD","GUBUN"],"pValue":["' . $thisYear . '","' . $division  . '","' . $emptyProfResult[$i]['majorCode'] . '","","' . $sectionArr[$j]  .'"]}';
					$parsingResult = DatasetCurl($titleParsingUrl, $titleParsingHeader, $titleParsingData);
					$parsingResultCount = count($parsingResult);
					for($k=0; $k<$parsingResultCount; $k++) {
						if($parsingResult[$k]['교과목명'] == $emptyProfResult[$i]['title'] && $parsingResult[$k]['분반'] == $emptyProfResult[$i]['class'] && $parsingResult[$k]['교과목번호'] == $emptyProfResult[$i]['code']) {
							prr($parsingResult[$k]);
							$existingTitle = $emptyProfResult[$i]['title'];
							$existingClass = $emptyProfResult[$i]['class'];
							$existingCode = $emptyProfResult[$i]['code'];
							$existingDivs = $emptyProfResult[$i]['divs'];
							$updatedProf = $parsingResult[$k]['교수명'];
							if(empty($updatedProf)) {
								continue;
							} else {
								$query = "UPDATE $course SET prof='$updatedProf' WHERE title='$existingTitle' AND code='$existingCode' AND class='$existingClass' AND divs='$existingDivs'";
								$conn->query($query);
							}
						} else {
							continue;
						}
					}
				}
			}
			else if($emptyProfResult[$i]['divs'] == '교양') {
				$section = 3;
				$dataset = DatasetParsing($semester, $division, $section);
				$datasetCount = count($dataset);
				for($j=0; $j<$datasetCount; $j++) {
					$subject = $dataset[$j]['교과목'];
					$subjectCount = count($subject);
					for($k=0; $k<$subjectCount; $k++) {
						if($subject[$k]['교과목명'] == $emptyProfResult[$i]['title'] && $subject[$k]['분반'] == $emptyProfResult[$i]['class'] && $subject[$k]['교과목번호'] == $emptyProfResult[$i]['code']) {
							$existingTitle = $emptyProfResult[$i]['title'];
							$existingClass = $emptyProfResult[$i]['class'];
							$existingCode = $emptyProfResult[$i]['code'];
							$existingDivs = $emptyProfResult[$i]['divs'];
							$updatedProf = $subject[$k]['교수명'];
							if(empty($updatedProf)) {
								continue;
							} else {
								$query = "UPDATE $course SET prof='$updatedProf' WHERE title='$existingTitle' AND code='$existingCode' AND class='$existingClass' AND divs='$existingDivs'";
								$conn->query($query);
							}
						}
					}
				}
			}
			else if($emptyProfResult[$i]['divs'] == '교양필수') {
				$section = 4;
				$titleParsingUrl = "https://e-onestop.pusan.ac.kr/middleware/curriculum/college/CollegeAssignInfoSearch";
				$titleParsingHeader = getHeader($semester, $titleParsingUrl);
				$titleParsingData = '{"pName":["YEAR","TERM","DEPTCD","CULTCD","GUBUN"],"pValue":["' . $thisYear . '","' . $division  . '","' . $emptyProfResult[$i]['majorCode'] . '","","' . $section  .'"]}';
				$parsingResult = DatasetCurl($titleParsingUrl, $titleParsingHeader, $titleParsingData);
				$parsingResultCount = count($parsingResult);
				for($j=0; $j<$parsingResultCount; $j++) {
					if($parsingResult[$j]['교과목명'] == $emptyProfResult[$i]['title'] && $parsingResult[$j]['분반'] == $emptyProfResult[$i]['class'] && $parsingResult[$j]['교과목번호'] == $emptyProfResult[$i]['code']) {
						$existingTitle = $emptyProfResult[$i]['title'];
						$existingClass = $emptyProfResult[$i]['class'];
						$existingCode = $emptyProfResult[$i]['code'];
						$existingDivs = $emptyProfResult[$i]['divs'];
						$updatedProf = $parsingResult[$j]['교수명'];
						if(empty($updatedProf)) {
							continue;
						} else {
							$query = "UPDATE $course SET prof='$updatedProf' WHERE title='$existingTitle' AND code='$existingCode' AND class='$existingClass' AND divs='$existingDivs'";
							$conn->query($query);
						}
					}
				}
			}
			else if($emptyProfResult[$i]['divs'] == '일반선택') {
				$section = 5;
				$generalCode = '11665';		// fixed value ?
				$generalParsingUrl = "https://e-onestop.pusan.ac.kr/middleware/curriculum/college/CollegeAssignInfoSearch";
				$generalParsingHeader = getHeader($semester, $generalParsingUrl);
				$generalParsingData = '{"pName":["YEAR","TERM","DEPTCD","CULTCD","GUBUN"],"pValue":["' . $thisYear . '","' . $division  . '","' . $generalCode . '","","' . $section  .'"]}';
				$parsingResult = DatasetCurl($generalParsingUrl, $generalParsingHeader, $generalParsingData);
				$parsingResultCount = count($parsingResult);
				for($j=0; $j<$parsingResultCount; $j++) {
					if($parsingResult[$j]['교과목명'] == $emptyProfResult[$i]['title'] && $parsingResult[$j]['분반'] == $emptyProfResult[$i]['class'] && $parsingResult[$j]['교과목번호'] == $emptyProfResult[$i]['code']) {
						$existingTitle = $emptyProfResult[$i]['title'];
						$existingClass = $emptyProfResult[$i]['class'];
						$existingCode = $emptyProfResult[$i]['code'];
						$existingDivs = $emptyProfResult[$i]['divs'];
						$updatedProf = $parsingResult[$j]['교수명'];
						if(empty($updatedProf)) {
							continue;
						} else {
							$query = "UPDATE $course SET prof='$updatedProf' WHERE title='$existingTitle' AND code='$existingCode' AND class='$existingClass' AND divs='$existingDivs'";
							$conn->query($query);
						}
					}
				}
			}			
		}
		else if($semester == 'season') {
			$seasonParsingUrl = "https://e-onestop.pusan.ac.kr/middleware/study/sessionTermManual/sessionTermManualCheck";
			$seasonParsingHeader = getHeader($semester, $seasonParsingUrl);
			$seasonParsingData = '{"pName":["YEAR","TERM"],"pValue":["' . $thisYear . '","' . $division  . '"]}';
			$parsingResult = DatasetCurl($seasonParsingUrl, $seasonParsingHeader, $seasonParsingData);	
			$parsingResultCount = count($parsingResult);
			for($j=0; $j<$parsingResultCount; $j++) {
				if($parsingResult[$j]['교과목명'] == $emptyProfResult[$i]['title'] && $parsingResult[$j]['분반'] == $emptyProfResult[$i]['class'] && $parsingResult[$j]['교과목번호'] == $emptyProfResult[$i]['code']) {
					$existingTitle = $emptyProfResult[$i]['title'];
					$existingClass = $emptyProfResult[$i]['class'];
					$existingCode = $emptyProfResult[$i]['code'];
					$existingDivs = $emptyProfResult[$i]['divs'];
					$updatedProf = $parsingResult[$j]['교수명'];
					if(empty($updatedProf)) {
						continue;
					} else {
						$query = "UPDATE $course SET prof='$updatedProf' WHERE title='$existingTitle' AND code='$existingCode' AND class='$existingClass' AND divs='$existingDivs'";
						$conn->query($query);
					}
				}
			}
		}
	}
}
//
// (과목코드, 교과목구분, 교과목명) 비었을 경우, 삭제
//
/*
$query = "SELECT * FROM $course WHERE code='' AND divs='' AND title=''";
$sql4course = $conn->query($query);
while($row4course = $sql4course->fetch_assoc()) {
	$abbd[] = $row4course;
}

for($i=0; $i<count($abbd); $i++) {
	$query = "DELETE FROM $course WHERE major='{$abbd[$i]['major']}' AND majorCode='{$abbd[$i]['majorCode']}' AND code='' AND divs='' AND title=''";
	$conn->query($query);
}
*/
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
