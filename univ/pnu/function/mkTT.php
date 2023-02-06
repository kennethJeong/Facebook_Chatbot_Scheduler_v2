<?php
function mkTT($userKey, $mkTTpath)
{
	global $userInfo, $thisYear, $thisSemester;
	
	include_once $_SERVER["DOCUMENT_ROOT"] . '/scheduler/html2pdf/html2pdf.class.php';
	
	$startHour = 8; 
	$endHour = 22; 
	$firstTime = mktime($startHour, 0, 0); 
	$lastTime = mktime($endHour, 0, 0);
	$interval = 30;
	$colors = array('#CFF', '#FCF', '#FFC', '#CCF', '#CFC', '#FCC', '#AFF', '#FAF', '#FFA', '#AAF', '#AFA', '#FAA', 'none' => '#FFF'); 
	
	$days = array("시간", "월", "화", "수", "목", "금", "토");
	$numOfDays = count($days)-1;
	
	$diff = ($lastTime - $firstTime) / (60 * $interval);
	for($col=0; $col<count($days); $col++) {
		for($row=0; $row<=$diff; $row++) {
			$intervalTime = date('H:i', mktime(8, $interval * $row, 0));
			
			$time2check = explode(":", $intervalTime);
			$time2checkHour = $time2check[0];
			$time2checkMin = $time2check[1];
			$time2checkFin = mktime($time2checkHour, $time2checkMin, 0);
			
			if($col == 0) {
				$ttData[$col][$row] = $intervalTime;
			} else {
				for($i=0; $i<count($userInfo); $i++) {
					for($j=0; $j<$numOfDays; $j++) {
						if($userInfo[$i]['day'.$j] == $days[$col]) {
							$ttTime1 = explode(":", $userInfo[$i]['time'.$j]);
							$ttTime1Hour = $ttTime1[0];
							$ttTime1Min = $ttTime1[1];
							$ttMin1 = $userInfo[$i]['min'.$j];
							$ttTime1nMinStart = mktime($ttTime1Hour, $ttTime1Min, 0);
							$ttTime1nMinEnd = mktime($ttTime1Hour, $ttTime1Min+$ttMin1, 0);			
							
							if(($ttTime1nMinStart <= $time2checkFin && $ttTime1nMinEnd >= $time2checkFin)) {
								$ttData[$col][$row] = $userInfo[$i]['title'] . "<br><br>" . $userInfo[$i]['class'] . "분반(" . $userInfo[$i]['min1'] . "분)<br>" . $userInfo[$i]['prof'] . " 교수님<br>" . $userInfo[$i]['classroom'.$j];
							}
						}
					}
				}
			}
		}
	}
	
	$selectedColor = 0;
	$checkOverlap = array();
	
	for($col=1; $col<count($days); $col++) {
		if(is_array($ttData[$col])) {
			$overlap = array_count_values($ttData[$col]);
			$overlapValue = array_keys($overlap);			// Array ( [0] => 나노바이오재료공학 ) Array ( [0] => 동역학 [1] => 물리화학 ) Array ( [0] => 나노바이오재료공학 ) Array ( [0] => 창의적사고와글쓰기 [1] => 동역학 [2] => 물리화학 )
			$overlapRowspan = array_values($overlap);			// Array ( [0] => 16 ) Array ( [0] => 16 [1] => 16 ) Array ( [0] => 16 ) Array ( [0] => 21 [1] => 16 [2] => 16 )
			$overlapRow1st = array();			// Array ( [0] => 66 ) Array ( [0] => 66 [1] => 84 ) Array ( [0] => 66 ) Array ( [0] => 12 [1] => 66 [2] => 84 )
		
			
			for($i=0; $i<count($overlap); $i++) {
				for($row=0; $row<=count($ttData[0]); $row++) {
					if($ttData[$col][$row] == $overlapValue[$i] && $ttData[$col][$row-1] != $overlapValue[$i]) {
						$overlapRow1st[$i] = $row;
					}
				}
				
				$overlapInfoAdd = array
				(
					'value' => $overlapValue[$i],
					'rowspan' => $overlapRowspan[$i],
					'row1st' => $overlapRow1st[$i]
				);
				
				if(!in_array($overlapInfoAdd['value'], $checkOverlap)) {
					array_push($checkOverlap, $overlapInfoAdd['value']);
					$overlapInfoAdd['color'] = $colors[$selectedColor++];
				} else {
					foreach($checkOverlap as $key=>$value) {
						if($value == $overlapInfoAdd['value']) {
							$overlapInfoAdd['color'] = $colors[$key];
						}
					}
				}			
				$overlapInfo[$col][] = $overlapInfoAdd;
			}
		}
	}
	
	for($col=1; $col<=count($days); $col++) {
		for($i=0; $i<count($overlapInfo[$col]); $i++) {
			for($row1st=$overlapInfo[$col][$i]['row1st']; $row1st<($overlapInfo[$col][$i]['row1st']+$overlapInfo[$col][$i]['rowspan']); $row1st++) {
				if($row1st == $overlapInfo[$col][$i]['row1st']) {
					$ttData[$col][$row1st] = "<td width=135 rowspan=" . $overlapInfo[$col][$i]['rowspan'] . " style='background-color: " . $overlapInfo[$col][$i]['color'] . "; font-size: 100%; font-weight:600; margin-right:5%;'>" . $overlapInfo[$col][$i]['value'] . "</td>";
				} else {
					$ttData[$col][$row1st] = "";
				}
			}
		}
	}
	
	for($i=0; $i<1; $i++) {		// ($interval=5 기준) 08:00부터 08:55까지
		if(!isset($ttData[1][$i]) && !isset($ttData[2][$i]) && !isset($ttData[3][$i]) && !isset($ttData[4][$i]) && !isset($ttData[5][$i]) && !isset($ttData[6][$i])) {
			unset($ttData[0][$i]);
		}
	}
	for($i=21; $i<29; $i++) {		// ($interval=5 기준) 18:00부터 22:00까지
		if(!isset($ttData[1][$i]) && !isset($ttData[2][$i]) && !isset($ttData[3][$i]) && !isset($ttData[4][$i]) && !isset($ttData[5][$i]) && !isset($ttData[6][$i])) {
			unset($ttData[0][$i]);
		}
	}
	
	if(!isset($ttData[6])) {
		unset($days[6]);
	}
	
	/////////////////////////////////////////////////////////////////
	$mkTimeTableValue .= "<html><head><title>시간표</title></head><body>";
	$mkTimeTableValue .= "<table width=100%>";		//style='font-family:arial;'
	/////////////////////////////////////////////////////////////////
	$mkTimeTableValue .= "<tr align=center>";
	
	for($i=0; $i<count($days); $i++) {
	
		if($i == 0) {
			
			$mkTimeTableValue .= "<th style='font-size:150%;'>" . $days[$i] . "</th>";
			
		}
		else if($i != 0) {
			
			$mkTimeTableValue .= "<th width=135 style='font-size:150%;'>" . $days[$i] . "</th>";		
			
		}
		
	}
	
	$mkTimeTableValue .= "</tr>";
	/////////////////////////////////////////////////////////////////
	for($row=0; $row<count($ttData[0]); $row++) {
		
			$mkTimeTableValue .= "<tr align=center>";
			
			for($col=0; $col<count($days); $col++) {
				
				if($col == 0) {
	
					$mkTimeTableValue .= "<th width=60 height=30 style='font-size:120%; margin-right:5%;'>" . $ttData[$col][$row] . "</th>";
	
				} else {
					
					if(isset($ttData[$col][$row])) {
						
						$mkTimeTableValue .= $ttData[$col][$row];
						
					} else {
						
						$mkTimeTableValue .= "<td style='background-color: " . $colors['none'] . ";'></td>";
						
					}
					
				}
				
			}	
		
		$mkTimeTableValue .= "</tr>";
		
	}
	
	$mkTimeTableValue .= "</table></body></html>";
	
	// HTML -> PDF
	$pdfPath = $mkTTpath.'/pdf/';
	$pdfName = $pdfPath.'tt_'.$thisYear.$thisSemester.'_'.$userKey.'.pdf';
	$html2pdf = new HTML2PDF('P', 'A4');
	$html2pdf->setDefaultFont('malgun');
	$html2pdf->writeHTML($mkTimeTableValue);
	$html2pdfOutput = $html2pdf->Output($pdfName, 'F');
	
	// PDF -> JPG
	$imagePath = $mkTTpath.'/image/';
	$imageName = $imagePath.'tt_'.$thisYear.$thisSemester.'_'.$userKey.'.jpg';
	$im = new Imagick();
	$im->setResolution(150,150);
	$im->readImage($pdfName);
	$im->flattenImages();
	$im->cropImage(1240,1100,0,0);
	$im->thumbnailImage(620,550, 1, 1);
	$im->writeImage($imageName);
	$im->clear();
	$im->destroy();
	
		
	if($imageName) {
		unlink($pdfName);
	}
	
	return TRUE;
}
/*
function mkTT()
{
	global $userInfo;
	
	$startHour = 8; 
	$endHour = 22; 
	$firstTime = mktime($startHour, 0, 0); 
	$lastTime = mktime($endHour, 0, 0);
	$interval = 30;
	$colors = array('#CFF', '#FCF', '#FFC', '#CCF', '#CFC', '#FCC', '#AFF', '#FAF', '#FFA', '#AAF', '#AFA', '#FAA', 'none' => '#FFF'); 
	
	$days = array("시간", "월", "화", "수", "목", "금", "토");
	
	$diff = ($lastTime - $firstTime) / (60 * $interval);
	for($col=0; $col<count($days); $col++) {
		for($row=0; $row<=$diff; $row++) {
			$intervalTime = date('H:i', mktime(8, $interval * $row, 0));
			
			$time2check = explode(":", $intervalTime);
			$time2checkHour = $time2check[0];
			$time2checkMin = $time2check[1];
			$time2checkFin = mktime($time2checkHour, $time2checkMin, 0);
			
			if($col == 0) {
				$ttData[$col][$row] = $intervalTime;
			} else {
				for($i=0; $i<count($userInfo); $i++) {
					if($userInfo[$i]['day1'] == $days[$col]) {
						$ttTime1 = explode(":", $userInfo[$i]['time1']);
						$ttTime1Hour = $ttTime1[0];
						$ttTime1Min = $ttTime1[1];
						$ttMin1 = $userInfo[$i]['min1'];
						$ttTime1nMinStart = mktime($ttTime1Hour, $ttTime1Min, 0);
						$ttTime1nMinEnd = mktime($ttTime1Hour, $ttTime1Min+$ttMin1, 0);			
						
						if(($ttTime1nMinStart <= $time2checkFin && $ttTime1nMinEnd >= $time2checkFin)) {
							$ttData[$col][$row] = $userInfo[$i]['title'] . "<br><br>" . $userInfo[$i]['class'] . "분반(" . $userInfo[$i]['min1'] . "분)<br>" . $userInfo[$i]['prof'] . " 교수님<br>" . $userInfo[$i]['classroom1'];
						}
					}
					else if(!empty($userInfo[$i]['day2']) && $userInfo[$i]['day2'] == $days[$col]) {
						$ttTime2 = explode(":", $userInfo[$i]['time2']);
						$ttTime2Hour = $ttTime2[0];
						$ttTime2Min = $ttTime2[1];
						$ttMin2 = $userInfo[$i]['min2'];
						$ttTime2nMinStart = mktime($ttTime2Hour, $ttTime2Min, 0);
						$ttTime2nMinEnd = mktime($ttTime2Hour, $ttTime2Min+$ttMin2, 0);	
						
						if(($ttTime2nMinStart <= $time2checkFin && $ttTime2nMinEnd >= $time2checkFin)) {
							$ttData[$col][$row] = $userInfo[$i]['title'] . "<br><br>" . $userInfo[$i]['class'] . "분반(" . $userInfo[$i]['min1'] . "분)<br>" . $userInfo[$i]['prof'] . " 교수님<br>" . $userInfo[$i]['classroom2'];
						}
					}
				}
			}
		}
	}
	
	$selectedColor = 0;
	$selectedPrevColor=0;
	$checkOverlap = array();
	for($col=1; $col<count($days); $col++) {
		if(is_array($ttData[$col])) {
			$overlap = array_count_values($ttData[$col]);
			$overlapValue = array_keys($overlap);			// Array ( [0] => 나노바이오재료공학 ) Array ( [0] => 동역학 [1] => 물리화학 ) Array ( [0] => 나노바이오재료공학 ) Array ( [0] => 창의적사고와글쓰기 [1] => 동역학 [2] => 물리화학 )
			$overlapRowspan = array_values($overlap);			// Array ( [0] => 16 ) Array ( [0] => 16 [1] => 16 ) Array ( [0] => 16 ) Array ( [0] => 21 [1] => 16 [2] => 16 )
			$overlapRow1st = array();			// Array ( [0] => 66 ) Array ( [0] => 66 [1] => 84 ) Array ( [0] => 66 ) Array ( [0] => 12 [1] => 66 [2] => 84 )
		
			
			for($i=0; $i<count($overlap); $i++) {
				for($row=0; $row<=count($ttData[0]); $row++) {
					if($ttData[$col][$row] == $overlapValue[$i] && $ttData[$col][$row-1] != $overlapValue[$i]) {
						$overlapRow1st[$i] = $row;
					}
				}
				
				$overlapInfoAdd = array
				(
					'value' => $overlapValue[$i],
					'rowspan' => $overlapRowspan[$i],
					'row1st' => $overlapRow1st[$i],
				);
				
				if(!in_array($overlapInfoAdd['value'], $checkOverlap)) {
					array_push($checkOverlap, $overlapInfoAdd['value']);
					$overlapInfoAdd['color'] = $colors[$selectedColor++];
				} else {
					$overlapInfoAdd['color'] = $colors[$selectedPrevColor++];
				}
				$overlapInfo[$col][$i] = $overlapInfoAdd;
			}
		}
	}
	
	
	//echo "<pre>";
	//print_r($overlapInfo);
	//echo "</pre>";
	
	
	for($col=1; $col<=count($days); $col++) {
		for($i=0; $i<count($overlapInfo[$col]); $i++) {
			for($row1st=$overlapInfo[$col][$i]['row1st']; $row1st<($overlapInfo[$col][$i]['row1st']+$overlapInfo[$col][$i]['rowspan']); $row1st++) {
				if($row1st == $overlapInfo[$col][$i]['row1st']) {
					$ttData[$col][$row1st] = "<td width=135 rowspan=" . $overlapInfo[$col][$i]['rowspan'] . " style='background-color: " . $overlapInfo[$col][$i]['color'] . "; font-size: 100%; font-weight:600; margin-right:5%;'>" . $overlapInfo[$col][$i]['value'] . "</td>";
				} else {
					$ttData[$col][$row1st] = "";
				}
			}
		}
	}
	
	for($i=0; $i<1; $i++) {		// ($interval=5 기준) 08:00부터 08:55까지
		if(!isset($ttData[1][$i]) && !isset($ttData[2][$i]) && !isset($ttData[3][$i]) && !isset($ttData[4][$i]) && !isset($ttData[5][$i]) && !isset($ttData[6][$i])) {
			unset($ttData[0][$i]);
		}
	}
	for($i=21; $i<29; $i++) {		// ($interval=5 기준) 18:00부터 22:00까지
		if(!isset($ttData[1][$i]) && !isset($ttData[2][$i]) && !isset($ttData[3][$i]) && !isset($ttData[4][$i]) && !isset($ttData[5][$i]) && !isset($ttData[6][$i])) {
			unset($ttData[0][$i]);
		}
	}
	
	if(!isset($ttData[6])) {
		unset($days[6]);
	}
	
	//echo "<pre>";
	//print_r($ttData);
	//echo "</pre>";
	
	/////////////////////////////////////////////////////////////////
	$mkTimeTableValue .= "<html><head><title>시간표</title></head><body>";
	$mkTimeTableValue .= "<table width=100%>";		//style='font-family:arial;'
	/////////////////////////////////////////////////////////////////
	$mkTimeTableValue .= "<tr align=center>";
	
	for($i=0; $i<count($days); $i++) {
	
		if($i == 0) {
			
			$mkTimeTableValue .= "<th style='font-size:150%;'>" . $days[$i] . "</th>";
			
		}
		else if($i != 0) {
			
			$mkTimeTableValue .= "<th width=135 style='font-size:150%;'>" . $days[$i] . "</th>";		
			
		}
		
	}
	
	$mkTimeTableValue .= "</tr>";
	/////////////////////////////////////////////////////////////////
	for($row=0; $row<count($ttData[0]); $row++) {
		
			$mkTimeTableValue .= "<tr align=center>";
			
			for($col=0; $col<count($days); $col++) {
				
				if($col == 0) {
	
					$mkTimeTableValue .= "<th width=60 height=30 style='font-size:120%; margin-right:5%;'>" . $ttData[$col][$row] . "</th>";
	
				} else {
					
					if(isset($ttData[$col][$row])) {
						
						$mkTimeTableValue .= $ttData[$col][$row];
						
					} else {
						
						$mkTimeTableValue .= "<td style='background-color: " . $colors['none'] . ";'></td>";
						
					}
					
				}
				
			}	
		
		$mkTimeTableValue .= "</tr>";
		
	}
	
	$mkTimeTableValue .= "</table></body></html>";
	
	return $mkTimeTableValue;
}
*/