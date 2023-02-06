<!DOCTYPE html>
<html>
<head>
<title>출첵 결과!! 뚜둔!!</title>
<meta name="viewport" content="initial-scale=1.0, user-scalable=no, width=device-width" />
<style type="text/css">
	@-ms-viewport { width: device-width; }
	@-o-viewport { width: device-width; }
	@viewport { width: device-width; }
	#gifticon {
		max-width: 35%;
		height: auto;
		display: block;
		margin-left: auto;
		margin-right: auto;
	}
	#losingIcon, #noIcon, #waitingIcon {
		max-width: 65%;
		height: auto;
		display: block;
		margin-left: auto;
		margin-right: auto;
	}
	#text {
		text-align: center;
		margin-left: auto;
		margin-right: auto;
	}
</style>
</head>
<body>
</body>
</html>

<?php
header("Content-Type:text/html; charset=UTF-8");
header("Pragma: no-cache");
header("Cache-Control: no-store, no-cache, must-revalidate");
date_default_timezone_set('Asia/Seoul');
ini_set('allow_url_fopen', 'On');
ini_set('allow_url_include', 'On');
ini_set("display_errors", 1);
ini_set('memory_limit','-1');
error_reporting(E_ERROR | E_WARNING | E_PARSE);

function geoDistance($lat1, $lon1, $lat2, $lon2) { 
	$theta = $lon1 - $lon2; 
	$dist = sin($lat1 * M_PI/180.0) * sin($lat2 * M_PI/180.0) + cos($lat1 * M_PI/180.0) * cos($lat2 * M_PI/180.0) * cos($theta * M_PI/180.0); 
	$dist = acos($dist); 
	$dist = rad2deg($dist); 
	$meter = $dist * 60 * 1.1515 * 1000 * 1.609344; 

	return round($meter);
}

include_once $_SERVER["DOCUMENT_ROOT"] . '/scheduler/univ/pnu/dbInfo.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/scheduler/univ/pnu/lib.php';
foreach(glob($_SERVER["DOCUMENT_ROOT"] . '/scheduler/univ/pnu/function/*.php') as $functionFiles)
{
    include_once $functionFiles;
}
include_once $_SERVER["DOCUMENT_ROOT"] . '/scheduler/univ/pnu/config.php';

// user's live coordinates (latitude,longitude)
$attendance = $_POST['attendance'];
$senderID = $_POST['psid'];
$latitude = $_POST['latitude'];
$longitude = $_POST['longitude'];

// 가장 최근에 버튼을 눌러서 DB에 입력된 값들
$query = "SELECT * FROM attendance WHERE userkey='$senderID' AND year='$thisYear' AND semester='$thisSemester'
																	 AND inputTime>CURRENT_DATE() ORDER BY inputTime DESC LIMIT 1";
$attend = $conn->query($query)->fetch_assoc();

// 아직 출첵 버튼을 누르지 않았을 경우
if(empty($attend['attend'])) {
	$timeLimit = 15*60;
	if($now >= strtotime($attend['time']) && $now <= (strtotime($attend['time'])+$timeLimit)) {
		// 해당 수업의 강의실 위도, 경도
		$classroom = explode("-", $attend['classroom']);
		$classroomBuilding = $classroom[0];
		$query = "SELECT latitude,longitude FROM coordinates WHERE building='$classroomBuilding'";
		$coordinates = $conn->query($query)->fetch_assoc();
		$coordinatesLatitude = $coordinates['latitude'];
		$coordinatesLongitude = $coordinates['longitude'];
		$geoDistance = geoDistance($latitude, $longitude, $coordinatesLatitude, $coordinatesLongitude);
		// 유저의 현재 위치와 강의실 건물의 위치 간의 거리 <= 100m 일때
		if($geoDistance <= 100) {
			// YES or NO
			if($attendance == "Y" || $attendance == "N") {
				if($attendance == "N") {
					$query = "UPDATE attendance SET attend='YES', inputTime='$inputTime' 
										WHERE userkey='$senderID' AND year='$thisYear' AND semester='$thisSemester'
											AND title='{$attend['title']}' AND class='{$attend['class']}' AND prof='{$attend['prof']}'
											AND day='{$attend['day']}' AND time='{$attend['time']}' AND inputTime>CURRENT_DATE() ORDER BY inputTime LIMIT 1";					
				}
				else if($attendance == "N") {
					$query = "UPDATE attendance SET attend='NO', inputTime='$inputTime' 
										WHERE userkey='$senderID' AND year='$thisYear' AND semester='$thisSemester'
											AND title='{$attend['title']}' AND class='{$attend['class']}' AND prof='{$attend['prof']}'
											AND day='{$attend['day']}' AND time='{$attend['time']}' AND inputTime>CURRENT_DATE() ORDER BY inputTime LIMIT 1";								
				}
				$conn->query($query);		
				
				// 기프티콘 당첨 확률
				$randomValue = mt_rand(1, 2);
				$randomWinningValue = 1;
				if($randomValue == $randomWinningValue) {
					// 당첨시, 여러 기프티콘들 중 랜덤 추첨
					$gifticonDir = $_SERVER["DOCUMENT_ROOT"] . '/scheduler/univ/gifticon';
					$handle = opendir($gifticonDir);
					$gifticons = array();
					while (false !== ($filename = readdir($handle))) {
					    if($filename == "." || $filename == ".."){
					        continue;
					    }
					    if(is_file($gifticonDir . "/" . $filename)){
					        $gifticons[] = $filename;
					    }
					}
					closedir($handle);
					
					// 기프티콘 여분 존재O
					if(count($gifticons) > 0 ) {
						$selectedGifticonMain = "CUgifticon5000Main.jpg";
						$selectedGifticonURL = 'https://bhandy.kr/scheduler/univ/pnu/webview/attendance/icon/' . $selectedGifticonMain;
						echo '<img id="gifticon" src="'.$selectedGifticonURL.'">';
						echo "<p id='text'>I'm testing... The above picture is fake.<br>축하드립니다!🎉<br>위 기프티콘은 [내가 등록한 정보 보기]->[기프티콘]에서 다시 볼 수 있습니다.👍</p>";
						
						// 당첨된 기프티콘을 유저아이디로 된 폴더로 이동
						sort($gifticons);
						$selectedGifticon = $gifticons[0];
						$selectedGifticonDir = $_SERVER["DOCUMENT_ROOT"] . '/scheduler/univ/gifticon/' . $selectedGifticon;
						$usersGifticonDir = $_SERVER["DOCUMENT_ROOT"] . '/scheduler/univ/pnu/usersGifticon/'.$senderID;
						//// 유저아이디로 된 폴더 존재X
						if(!is_dir($usersGifticonDir)) {
							if(@mkdir($usersGifticonDir, 0777, true)) {
								if(is_dir($usersGifticonDir)) {
									@chmod($usersGifticonDir, 0777);
									rename($selectedGifticonDir, $usersGifticonDir."/1.jpg");
								}
							}
						}
						//// 유저아이디로 된 폴더 존재O
						else {
							$handle = opendir($usersGifticonDir);
							$usersGifticons = array();
							while (false !== ($filename = readdir($handle))) {
							    if($filename == "." || $filename == ".."){
							        continue;
							    }
							    if(is_file($usersGifticonDir . "/" . $filename)){
							        $usersGifticons[] = $filename;
							    }
							}
							closedir($handle);
							rsort($usersGifticons);
							$lastNumberOfGifticons = preg_replace("/[^0-9]*/s", "", $usersGifticons[0]); 
							$numberOfNewGifticon = $lastNumberOfGifticons+1;
							rename($selectedGifticonDir, $usersGifticonDir."/". $numberOfNewGifticon .".jpg");	
						}						
					}
					// 기프티콘 여분 존재X
					else {
						$losingIconURL = 'https://bhandy.kr/scheduler/univ/pnu/webview/attendance/icon/losingIcon.gif';
						echo '<img id="losingIcon" src="'.$losingIconURL.'">';
						echo "<p id='text'>아쉽네요..😭 다음 기회를 노려주세요!<br>그 대신 50 포인트 적립해드릴게요.😍</p>";
						
						// 마일리지 -> 이벤트 등록
						mileageChange("attendance");						
					}
				}
				// 기프티콘 당첨X
				else {
					$losingIconURL = 'https://bhandy.kr/scheduler/univ/pnu/webview/attendance/icon/losingIcon.gif';
					echo '<img id="losingIcon" src="'.$losingIconURL.'">';
					echo "<p id='text'>아쉽네요..😭 다음 기회를 노려주세요!<br>그 대신 50 포인트 적립해드릴게요.😍</p>";
					
					// 마일리지 -> 이벤트 등록
					mileageChange("attendance");
				}
			}
			// NOT YET
			else if($attendance == "NY") {
				$waitingIconURL = 'https://bhandy.kr/scheduler/univ/pnu/webview/attendance/icon/waitingIcon.jpg';
				echo '<img id="waitingIcon" src="'.$waitingIconURL.'">';
				echo "<p id='text'>아직 출첵했는지 모르시는군요!<br>그럼 잠시 후 다시 알림드릴게요.😍</p>";				
			}
		} else {
			// 예외: 유저가 강의실의 반경 100m 안에 없을 때 
			$noIconURL = 'https://bhandy.kr/scheduler/univ/pnu/webview/attendance/icon/noIcon.gif';
			echo '<img id="noIcon" src="'.$noIconURL.'">';
			echo "<p id='text'>강의실 근처에 계시지 않네요..😭<br>다음 출석체크는 수업 들어가서 완료해주세요.😍</p>";		
		}
	} else {
		// 예외: 같은 출첵 버튼을 두 번 눌렀을 때
		$noIconURL = 'https://bhandy.kr/scheduler/univ/pnu/webview/attendance/icon/noIcon.gif';
		echo '<img id="noIcon" src="'.$noIconURL.'">';
		echo "<p id='text'>이미 만료된 출석체크입니다.😭</p>";
	}
} else {
	// 예외: 비활성화된 버튼을 눌렀을 때
	$noIconURL = 'https://bhandy.kr/scheduler/univ/pnu/webview/attendance/icon/noIcon.gif';
	echo '<img id="noIcon" src="'.$noIconURL.'">';
	echo "<p id='text'>이미 출석체크를 완료했습니다.😍</p>";	
}

$query = queryInsert('logging', 'START');
$conn->query($query);
$send['text'] = "🎩: 초기화면으로 돌아가려면 아래 버튼을 눌러주세요.";
$send['payload'] = $send['title'] = array('초기화면', '마일리지', '기프티콘');
messageQR($send);