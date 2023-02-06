<?php
function registedConditionSubject($userInfo)
{
	$numOfDays = 6;
	
	for($i=0; $i<count($userInfo); $i++) {
		for($j=0; $j<$numOfDays; $j++) {
			if($userInfo[$i]["day".$j]) {
				$dataOfDays[$i][$j-1] = $userInfo[$i]["day".$j] . "-" . $userInfo[$i]["time".$j];
			}			
		}
	}
	
	for($i=0; $i<count($dataOfDays); $i++) {
		if(count($dataOfDays[$i]) == 1) {
			$explode = explode("-",$dataOfDays[$i][0]);
			$day = $explode[0];
			$time = $explode[1];
			$rgstedInfo[] = $userInfo[$i]['title'] . "(" . $day . " / " . $time . ")";
		} else {
			$day = array();
			$time = array();
			for($j=0; $j<count($dataOfDays[$i]); $j++) {
				$explode = explode("-",$dataOfDays[$i][$j]);
				$day[] = $explode[0];
				$time[] = $explode[1];
			}

			if(count(array_unique($time)) == 1) {
				$time = $time[0];
				$implodeDays = implode("",$day);
				if(count($day) == 2) {
					$rgstedInfo[] = $userInfo[$i]['title'] . "(" . $day[0] . "," . $day[1] . " / " . $time . ")";
				}
				else if(count($day) == 3) {
					$rgstedInfo[] = $userInfo[$i]['title'] . "(" . $day[0] . "," . $day[1] . "," . $day[2] . " / " . $time . ")";
				}
				else if(count($day) == 4) {
					if($implodeDays == "월화수목" || $implodeDays == "화수목금" || $implodeDays == "수목금토") {
						$rgstedInfo[] = $userInfo[$i]['title'] . "(" . $day[0] . "-" . $day[3] . " / " . $time . ")";
					}
					else if($implodeDays == "월화목금" || $implodeDays == "월화금토" || $implodeDays == "화수금토") {
						$rgstedInfo[] = $userInfo[$i]['title'] . "(" . $day[0] . "-" . $day[1] . "," . $day[2] . "-" . $day[3] . " / " . $time . ")";
					} else {
						$rgstedInfo[] = $userInfo[$i]['title'] . "(" . $day[0] . "," . $day[1] . "," . $day[2] . "," . $day[3] . " / " . $time . ")";
					}
				}
				else if(count($day) == 5) {
					if($implodeDays == "월화수목금" || $implodeDays == "화수목금토") {
						$rgstedInfo[] = $userInfo[$i]['title'] . "(" . $day[0] . "-" . $day[4] . " / " . $time . ")";
					} else {
						$rgstedInfo[] = $userInfo[$i]['title'] . "(" . $day[0] . "," . $day[1] . "," . $day[2] . "," . $day[3] . "," . $day[4] . " / " . $time . ")";
					}		
				} else {
					$rgstedInfo[] = $userInfo[$i]['title'] . "(" . $day[0] . "-" . $day[5] . " / " . $time . ")";
				}
			} else {
				for($j=0; $j<count($time); $j++) {
					$daysNtimes .= $day[$j] . "-" . $time[$j] . " / ";
				}
				$daysNtimes = substr($daysNtimes , 0, -3);
				$rgstedInfo[] = $userInfo[$i]['title'] . "(" . $daysNtimes . ")";
			}
		}
	}
	
	return $rgstedInfo;
}

function registedConditionSubjectDetail($userInfo)
{
	$numOfDays = 6;
	
	for($i=0; $i<count($userInfo); $i++) {
		$result['titleName'][] = $userInfo[$i]['title'];
		$result['prof'][] = $userInfo[$i]['prof'];
		$result['class'][] = $userInfo[$i]['class'];
		for($j=0; $j<$numOfDays; $j++) {
			if(!empty($userInfo[$i]['day'.($j+1)])) {
				$mid[$i]['day'][$j] = $userInfo[$i]["day".($j+1)];
				$mid[$i]['time'][$j] = $userInfo[$i]["time".($j+1)];
				$mid[$i]['min'][$j] = $userInfo[$i]["min".($j+1)];
				$mid[$i]['classroom'][$j] = $userInfo[$i]["classroom".($j+1)];			
			}
		}
		
		//
		// 요일
		//
		$implodeDays = implode("", $mid[$i]['day']);
		if(count($mid[$i]['day']) == 1) {
			$day = $mid[$i]['day'][0];
		}
		else if(count($mid[$i]['day']) == 2) {
			$day = $mid[$i]['day'][0] . "," . $mid[$i]['day'][1];
		}
		else if(count($mid[$i]['day']) == 3) {
			$day = $mid[$i]['day'][0] . "," . $mid[$i]['day'][1] . "," . $mid[$i]['day'][2];
		}
		else if(count($mid[$i]['day']) == 4) {
			if($implodeDays == "월화수목" || $implodeDays == "화수목금" || $implodeDays == "수목금토") {
				$day = $mid[$i]['day'][0] . "-" . $mid[$i]['day'][3];
			}
			else if($implodeDays == "월화목금" || $implodeDays == "월화금토" || $implodeDays == "화수금토") {
				$day = $mid[$i]['day'][0] . "-" . $mid[$i]['day'][1] . "," . $mid[$i]['day'][2] . "-" . $mid[$i]['day'][3];
			} else {
				$day = $mid[$i]['day'][0] . "," . $mid[$i]['day'][1] . "," . $mid[$i]['day'][2] . "," . $mid[$i]['day'][3];
			}
		}
		else if(count($mid[$i]['day']) == 5) {
			if($implodeDays == "월화수목금" || $implodeDays == "화수목금토") {
				$day = $mid[$i]['day'][0] . "-" . $mid[$i]['day'][4];
			} else {
				$day= $mid[$i]['day'][0] . "," . $mid[$i]['day'][1] . "," . $mid[$i]['day'][2] . "," . $mid[$i]['day'][3] . "," . $mid[$i]['day'][4];
			}		
		} else {
			$day = $mid[$i]['day'][0] . "-" . $mid[$i]['day'][5];
		}
		
		//
		// 시작시간 + 러닝시간 + 강의실
		//
		if(count(array_unique($mid[$i]['time'])) == 1 && count(array_unique($mid[$i]['min'])) == 1 && count(array_unique($mid[$i]['classroom'])) == 1) {
			$time = array_unique($mid[$i]['time'])[0];
			$min = array_unique($mid[$i]['min'])[0];
			$classroom = array_unique($mid[$i]['classroom'])[0];
			$day_time_classroom = "{$day} / {$time}({$min}분, $classroom)";
		} else {
			$day_time_classroom = array();
			for($j=0; $j<count($mid[$i]['day']); $j++) {
				$day_time_classroom[] = $mid[$i]['day'][$j] . "-" . $mid[$i]['time'][$j] . "(" . $mid[$i]['min'][$j] . "분, ". $mid[$i]['classroom'][$j] . ")";
			}
			$day_time_classroom = implode(", ", $day_time_classroom);
		}
	
		if($userInfo[$i]['divs'] == "교양") {
			$result['title'][] = "<" . $userInfo[$i]['title'] . "> " . $userInfo[$i]['divs'] . " - " . $userInfo[$i]['fields'] . " " . $userInfo[$i]['department'];
		} else {
			$result['title'][] = "<" . $userInfo[$i]['title'] . "> " . $userInfo[$i]['major'] . " " . $userInfo[$i]['divs'];
		}
		$result['info'][] = "분반 및 교수명: " . $userInfo[$i]['class'] . "분반, " . $userInfo[$i]['prof'] . " 교수님\n" . $day_time_classroom;
	}

	return $result;
}
