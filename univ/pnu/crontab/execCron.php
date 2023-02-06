<?php

// 조건: 공휴일이 아닌 날!
// 정규학기 => (수강신청 시작일 ~ 개강일) -> 해당 년도 db 생성(course + (해당년도)) 후, course INSERT+UPDATE 크론탭 작동(새벽4시마다)
//				  => (개강일 ~ 중간고사 시작일) && (중간고사 종료일 ~ 기말고사 시작일) -> 출첵 크론탭 작동(08~18시 동안 매일 5분마다)
//				  => 												''														  -> 수업 종료 후 푸시 크론탭 작동(08~18시 동안 매일 15분마다)
//				  => 												''														  -> 등록된 과제,휴강,시험 이벤트 푸시 크론탭 작동(매일 오전 8시에 한번만)
//
// 계절학기 => (계절학기 수강신청 기간 동안) -> (해당 년도 + 계절) db 생성(course + (해당년도) + (계절 S or W)) 후, course INSERT+UPDATE 크론탭 작동(새벽4시마다)
//				  => (계절학기 시작일 ~ 계절학기 종료일) -> 출첵 크론탭 작동(08~18시 동안 매일 5분마다)
//				  =>							''							   -> 수업 종료 후 푸시 크론탭 작동(08~18시 동안 매일 15분마다)
//				  =>							''							   -> 등록된 과제,휴강,시험 이벤트 푸시 크론탭 작동(매일 오전 8시에 한번만)
//
// 정규,계절학기 동일하게 => user에 year(해당년도(+계절(S or W))) 데이터 칼럼 추가 -> ((기말고사 종료일 or 계절학기 종료일) ~ 다음학기 시작일)까지
//																																				"다음 학기를 준비중입니다" 코멘트를 주고 모든 기능 중지
//																																  -> 이벤트  or 수강후기작성 등 컨텐츠 개발 필요
//
// 추가 사항 => 모든 eventInfo 읽는 과정 중, 해당 날짜가 오늘보다 이전인 event는 빼고 send 하도록
//				  => 						''						다른 수강생들이 등록한 목록을 열람할 때, 100% 동일한 내용일 경우 한 데 모아서 "동일 내용 X N개" 식의 코멘트만 subttle에 추가


header("Content-Type:text/html; charset=UTF-8");
header("Pragma: no-cache");
header("Cache-Control: no-store, no-cache, must-revalidate");
date_default_timezone_set('Asia/Seoul');
ini_set('allow_url_fopen', 'On');
ini_set('allow_url_include', 'On');
ini_set("display_errors", 1);
ini_set('memory_limit','-1');
error_reporting(E_ERROR | E_WARNING | E_PARSE);
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$docRoot = '/usr/share/nginx/html/scheduler';

include_once $docRoot.'/univ/pnu/dbInfo.php';
include_once $docRoot.'/univ/pnu/lib.php';
foreach(glob($docRoot.'/univ/pnu/function/*.php') as $functionFiles)
{
    include_once $functionFiles;
}
include_once $docRoot.'/univ/pnu/config.php';
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
include_once $docRoot.'/univ/pnu/crontab/cronLib.php';
include_once $docRoot.'/univ/pnu/crontab/cronConfig.php';
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//// Executing list of Crontabs
//// 해당 년도 db 생성(course + (해당년도)) 후, course INSERT+UPDATE 크론탭
include_once $docRoot.'/univ/pnu/crontab/parsingCourses.php';
//
//// 과제∙휴강∙시험 등록 유도 푸시 크론탭 (매일 오후 6시에 한번만)
include_once $docRoot.'/univ/pnu/crontab/pushAfterClass.php';
//
//// 출첵 크론탭
include_once $docRoot.'/univ/pnu/crontab/pushAttendance.php';
//
//// 등록된 과제,휴강,시험 푸시 크론탭 (매일 오전 8시에 한번만)
include_once $docRoot.'/univ/pnu/crontab/pushEvents.php';
//
