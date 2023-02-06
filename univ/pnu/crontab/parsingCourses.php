<?php
//$now = mktime(date("H"), 58, date("s"), 02, 28, $thisYear);
// 계절학기 (수강신청 시작일 ~ 수강신청 종료일 or 개강 하루 전)
// 겨울계절
$termOfSeasonSignUpW = ($today >= $semesterW['sign_up']['start'] && $today <= $semesterW['sign_up']['end']) || $today == date("Y-m-d", strtotime($semesterW['start']."-1 day"));
// 여름계절
$termOfSeasonSignUpS = ($today >= $semesterS['sign_up']['start'] && $today <= $semesterS['sign_up']['end']) || $today == date("Y-m-d", strtotime($semesterS['start']."-1 day"));;

// 정규학기 (수강신청 시작일 ~ 개강일)
// 1학기
$termOfRegularSignUp01 = ($today >= $semester1['sign_up']['start'] && $today < $semester1['start']);
// 2학기
$termOfRegularSignUp02 = ($today >= $semester2['sign_up']['start'] && $today < $semester2['start']);

if($termOfSeasonSignUpW || $termOfSeasonSignUpS || $termOfRegularSignUp01 || $termOfRegularSignUp02) {
	
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// $semester													// $division 																									// $section
																																																//// 정규 == 'regular'
	//// 정규 == 'regular', 계절 == 'season'			//// 1학기 == '10', 2학기 == '20', 여름계절 == '11', 겨울계절 == '21'			////// 전공,교직과목(2,3,4학년) == '1'	=> 학과조회
																																																////// 전공기초 및 기타 1학년 == '2' 	=> 학과조회
																																																////// 교양선택및일반선택 == '3' 	=> data 필요X
																																																////// 교양필수(정보화소양) == '4' => 학과조회
																																																////// 일반선택 == '5' => 바로 검색
																																																
																																																//// 계절 == 'season'
																																																////// $section => 필요X, 모든과목 한꺼번에 조회
	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
	$course = 'course'.$thisYear;
	if($termOfSeasonSignUpW || $termOfSeasonSignUpS) {
		$semester = 'season';
		if($termOfSeasonSignUpW) {
			$course .= 'W';
			$division = '21';
		}
		else if($termOfSeasonSignUpS) {
			$course .= 'S';
			$division = '11';
		}
	}
	else if($termOfRegularSignUp01 || $termOfRegularSignUp02) {
		$semester = 'regular';
		if($termOfRegularSignUp01) {
			$course .= '01';
			$division = '10';
		}
		else if($termOfRegularSignUp02) {
			$course .= '02';
			$division = '20';
		}
	}
	
	$query = "SHOW TABLES LIKE '$course'";
	$existCourse = $conn->query($query)->num_rows;
	if($existCourse == 0) {
		//
		// Create new table
		//
		createNewCourseTable($course);
		
	} else {
		//
		// Update datas
		//
		$nowMin = date("i", $now);
		if($termOfSeasonSignUpW || $termOfSeasonSignUpS) {
			$data = DatasetSort(DatasetParsing($semester, $division), $semester);
			DatasetInsert($course, $data);
			
			// 교수명 업데이트 => 매시각 58분마다
			if($nowMin == 58) {
				//DatasetProfUpdate($course, $division, $semester);
			}			
			// 전공명(major) 공란 채움 => 매시각 59분마다
			else if($nowMin == 59) {
				DatasetMajorUpdate($course, $division, $semester);
			}
		}
		else if($termOfRegularSignUp01 || $termOfRegularSignUp02) {
			// insert 실행 후, 3분간 pause
			// 전공,교직과목(2,3,4학년) => INSERT
			$forSection1 = ($nowMin >= 00 && $nowMin < 10);
			// 전공기초 및 기타 1학년 => INSERT
			$forSection2 = ($nowMin >= 10 && $nowMin < 20);
			// 교양선택및일반선택 => INSERT
			$forSection3 = ($nowMin >= 20 && $nowMin < 25);
			// 일반선택 => INSERT
			$forSection5 = ($nowMin >= 25 && $nowMin < 30);
			// 교양필수(정보화소양) => INSERT
			$forSection4 = (($nowMin >= 30 && $nowMin < 58) && 
										(
											$nowMin == 30 || $nowMin == 33 || $nowMin == 36 || $nowMin == 39
											|| $nowMin == 42 || $nowMin == 45 || $nowMin == 48
											|| $nowMin == 51 || $nowMin == 54 || $nowMin == 57
										)
									);
						
			for($i=1; $i<=5; $i++) {
				if(${'forSection'.$i}) {
					$section = $i;
					$dataset = DatasetParsing($semester, $division, $section);
					$data = DatasetSort($dataset, $semester);
					DatasetInsert($course, $data);
				}
			}
			
			// 교수명 업데이트 => 매시각 58분마다
			if($nowMin == 58) {
				DatasetProfUpdate($course, $division, $semester);
			}
			// 전공명(major) 공란 채움 => 매시각 59분마다
			else if($nowMin == 59) {
				DatasetMajorUpdate($course, $division, $semester);
			}
		}
	}
}