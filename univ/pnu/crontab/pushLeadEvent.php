<?php
////////////////////////////////////////////////////////////////////////////// 등록O && 이벤트X ////////////////////////////////////////////////////////////////////////////////
/*
$identicalUserkey = array();
for($i=0; $i<count($userInfo); $i++) {
	if(!in_array($userInfo[$i]['userkey'], $identicalUserkey)) {
		$identicalUserkey[] = $userInfo[$i]['userkey'];
	}
	
	if((!is_null($indenticalUserkey) && !in_array($userInfo[$i]['userkey'], $indenticalUserkey)) && (!is_null($eventInfoUserkey) && !in_array($userInfo[$i]['userkey'], $eventInfoUserkey))) {
		$identicalUserkey[] = $userInfo[$i]['userkey'];
		$userInfoInputTime = strtotime($userInfo[$i]['inputTime']);
		$gapOfInputTime = $now-$userInfoInputTime;
		
		$oneMonth = 60*60*24*30;
		
		$twoMonth = 60*60*24*30*2;
		$oneWeeks = 60*60*24*7;
		$twoWeeks = 60*60*24*14;
		$threeWeeks = 60*60*24*21;
		$after15min = 60*15; 

		$userName = findUserName($userInfo[$i]['userkey']);
		
		// 등록 1개월 이전
		if($gapOfInputTime < $oneMonth) {
			$send['text'] = "과제∙휴강∙시험을 등록하시면 " . $userName . "님의 스케쥴러가 되어드릴게요 :D";
			// 1주 뒤
			if($gapOfInputTime >= $oneWeeks && $gapOfInputTime < ($oneWeeks + $after15min)) {
				message($send, $userInfo[$i]['userkey']);
				ForAlarm($userInfo[$i]['userkey']);
			}
			// 2주 뒤
			else if($gapOfInputTime >= $twoWeeks && $gapOfInputTime < ($twoWeeks + $after15min)) {
				message($send, $userInfo[$i]['userkey']);
				ForAlarm($userInfo[$i]['userkey']);
			}
			// 3주 뒤
			else if($gapOfInputTime >= $threeWeeks && $gapOfInputTime < ($threeWeeks + $after15min)) {
				message($send, $userInfo[$i]['userkey']);
				ForAlarm($userInfo[$i]['userkey']);
			}
		}
		// 등록 1개월 이후
		else if($gapOfInputTime >= $oneMonth) {
			$send['text'] = "과제∙휴강∙시험을 등록하시면 " . $userName . "님의 스케쥴러가 되어드릴게요 :D";
			// 1월 째
			if($gapOfInputTime >= $oneMonth && $gapOfInputTime < ($oneMonth + $after15min)) {
				message($send, $userInfo[$i]['userkey']);
				ForAlarm($userInfo[$i]['userkey']);
			}
			// 2주 뒤
			else if($gapOfInputTime >= ($oneMonth + $twoWeeks) && $gapOfInputTime < ($oneMonth + $twoWeeks + $after15min)) {
				message($send, $userInfo[$i]['userkey']);
				ForAlarm($userInfo[$i]['userkey']);
			}
			// 2개월 뒤
			else if($gapOfInputTime >= ($twoMonth) && $gapOfInputTime < ($twoMonth + $after15min)) {
				message($send, $userInfo[$i]['userkey']);
				ForAlarm($userInfo[$i]['userkey']);
			}
		}
	}
}
*/
