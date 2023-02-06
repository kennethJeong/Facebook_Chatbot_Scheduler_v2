<?php
function curlPost($url, $message)
{
	global $senderID, $accessToken;
	
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	$result = curl_exec($ch);
}

function curlGet($url)
{
	$urlGet = $url . $GLOBALS['accessToken'];
	
	$ch = curl_init($urlGet);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                                                                 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);                                                                                               
	$result = curl_exec($ch);
	return $result;
}
/*
function curlDelete($url, $message)	
{
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
	$result = curl_exec($ch);
	return $result;
}

function TypingOff()
{
	global $senderID, $accessToken;
	
	if($send['userkey']) {
		$senderID = $send['userkey'];	
	} else {
		$senderID = $senderID;
	}
	
	$message = array
	(
		"recipient" => array
		(
			"id" => $senderID
		),
		"sender_action" =>	"typing_off"
	);
	
	$url = "https://graph.facebook.com/v2.6/me/messages?access_token=".$accessToken;
	
	curlPost($url, $message);
}
*/

function message($send, $userKey=NULL) 
{
	global $senderID, $accessToken;
	
	isset($userKey) ? $senderID = $userKey : $senderID = $senderID;
	
	if($send['text']) {
		$message = array
		(
			"messaging_type" => "RESPONSE",
			"recipient" => array
			(
				"id" => $senderID
			),
			"message" =>	array
			(
				"text" => $send['text']
			)
		);
		
		$url = "https://graph.facebook.com/v2.6/me/messages?access_token=".$accessToken;
	
		curlPost($url, $message);
	}
}

function messageImage($send, $userKey=NULL) 
{
	global $senderID, $accessToken;
	
	isset($userKey) ? $senderID = $userKey : $senderID = $senderID;
	
	if($send['img']['url']) {
		$message = array
		(
			"messaging_type" => "RESPONSE",
			"recipient" => array
			(
				"id" => $senderID
			),
			"message" =>	array
			(
				"attachment" => array
				(
					"type" => "image",
					"payload" => array
					(
						"url" => $send['img']['url']
					)
				)
			)
		);
		
		$url = "https://graph.facebook.com/v2.6/me/messages?access_token=".$accessToken;
	
		curlPost($url, $message);
	}
}

function messageQR($send, $userKey=NULL)
{	
	global $senderID, $accessToken;
	
	isset($userKey) ? $senderID = $userKey : $senderID = $senderID;
	
	if($send['text'] && $send['title'] && $send['payload']) {
		$message = array
		(
			"messaging_type" => "RESPONSE",
			"recipient" => array
			(
				"id" => $senderID
			),
			"message" =>	array
			(
				"text" => $send['text'],
				"quick_replies" => array()
			)
		);
		
		if(count($send['title']) <= 11) {
			for($i=0; $i<count($send['title']); $i++) {
				$quickReplies = array
				(
					"content_type" => "text",
					"title" => $send['title'][$i],
					"payload" => $send['payload'][$i]
				);
				array_push($message['message']['quick_replies'], $quickReplies);
			}
		}
		else if(count($send['title']) > 11) {
			unset($message['message']['text']);
			unset($message['message']['quick_replies']);
			
			$errorText = array
			(
				"text" => "ERROR : COUNT OVER(11)"
			);
			array_push($message['message'], $errorText);
		}
		
		$url = "https://graph.facebook.com/v2.6/me/messages?access_token=".$accessToken;
		
		curlPost($url, $message);		
	}
}

function messageTemplate($send, $userKey=NULL)
{
	global $senderID, $accessToken;

	isset($userKey) ? $senderID = $userKey : $senderID = $senderID;
	
	if($send['elementsTitle'] && $send['elementsButtonsTitle']) {
		$message = array
		(
			"messaging_type" => "RESPONSE",
			"recipient" => array
			(
				"id" => $senderID
			),
			"message" =>	array
			(
				"attachment" => array
				(
					"type" => "template",
					"payload" => array
					(
						"template_type" => "generic",
						"elements" => array()
					)
				)
			)
		);
		
		$count = count($send['elementsButtonsTitle']) / 3;
		$arrayChunk = array_chunk($send['elementsButtonsTitle'], 3);
		if(is_int($count)) {
			for($i=0; $i<$count; $i++) {
				$elementsArrayPush = array
				(
					"title" => $send["elementsTitle"],
					"buttons" => array()
				);
				array_push($message["message"]["attachment"]["payload"]["elements"], $elementsArrayPush);
				
				for($j=0; $j<3; $j++) {
					$elementsButtonsArrayPush = array
					(
						"type" => "postback",
						"title" => $arrayChunk[$i][$j],
						"payload" => $arrayChunk[$i][$j]
					);
					array_push($message["message"]["attachment"]["payload"]["elements"][$i]["buttons"], $elementsButtonsArrayPush);
				}	
			}
		}
		else if(!is_int($count) && is_float($count)) {
			if(count($send['elementsButtonsTitle']) < 3) {
				$elementsArrayPush = array
				(
					"title" => $send["elementsTitle"],
					"buttons" => array()
				);
				array_push($message["message"]["attachment"]["payload"]["elements"], $elementsArrayPush);
				
				for($i=0; $i<count($send['elementsButtonsTitle']); $i++) {
					$elementsButtonsArrayPush = array
					(
						"type" => "postback",
						"title" => $send['elementsButtonsTitle'][$i],
						"payload" =>$send['elementsButtonsTitle'][$i]
					);
					array_push($message["message"]["attachment"]["payload"]["elements"][0]["buttons"], $elementsButtonsArrayPush);
				}
			}
			else if(count($send['elementsButtonsTitle']) > 3) {
				$countCeil = ceil($count);
				
				$numberOfDivision = count($arrayChunk);
				$arrayLastPreviousKeys = $arrayChunk[$numberOfDivision-2];
				$arrayLastKey = $arrayChunk[$numberOfDivision-1];
				
				if(count($arrayLastKey) == 1) {
					$arrayMergeLastnLastPreviousKeys = array_chunk(array_merge($arrayLastPreviousKeys, $arrayLastKey), 2);
					
					if(count($send['elementsButtonsTitle']) == 4){
						$arrayChunk = $arrayMergeLastnLastPreviousKeys;
					} else {
						array_splice($arrayChunk, $numberOfDivision-2, $numberOfDivision-1, $arrayMergeLastnLastPreviousKeys);
					}
	
					for($i=0; $i<$countCeil; $i++) {
						$elementsArrayPush = array
						(
							"title" => $send["elementsTitle"],
							"buttons" => array()
						);
						array_push($message["message"]["attachment"]["payload"]["elements"], $elementsArrayPush);
						
						for($j=0; $j<3; $j++) {
							$elementsButtonsArrayPush = array
							(
								"type" => "postback",
								"title" => $arrayChunk[$i][$j],
								"payload" => $arrayChunk[$i][$j]
							);
							
							if($arrayChunk[$i][$j] != "") {
								array_push($message["message"]["attachment"]["payload"]["elements"][$i]["buttons"], $elementsButtonsArrayPush);
							} else {
								end;
							}
						}	
					}												
				}
				else if (count($arrayLastKey) == 2) {
					for($i=0; $i<$countCeil; $i++) {
						$elementsArrayPush = array
						(
							"title" => $send["elementsTitle"],
							"buttons" => array()
						);
						array_push($message["message"]["attachment"]["payload"]["elements"], $elementsArrayPush);
						
						for($j=0; $j<3; $j++) {
							$elementsButtonsArrayPush = array
							(
								"type" => "postback",
								"title" => $arrayChunk[$i][$j],
								"payload" => $arrayChunk[$i][$j]
							);
							
							if($arrayChunk[$i][$j] != "") {
								array_push($message["message"]["attachment"]["payload"]["elements"][$i]["buttons"], $elementsButtonsArrayPush);
							} else {
								end;
							}
						}	
					}
				}
			}
		}
		else if(count($send['elementsButtonsTitle']) == 0) {
			unset($message["message"]["attachment"]);
			$message["message"] = array("text" => "서비스 오류");
		} 
		else if(count($send['elementsButtonsTitle']) > 30) {
			unset($message["message"]["attachment"]);
			$message["message"] = array("text" => "서비스 오류");		
		}
		else {
			unset($message["message"]["attachment"]);
			$message["message"] = array("text" => "서비스 오류");
		}
		
		$url = "https://graph.facebook.com/v2.6/me/messages?access_token=".$accessToken;
	
		curlPost($url, $message);		
	}
}

function messageTemplateLeftSlide($send, $userKey=NULL)
{
	global $senderID, $accessToken;
	
	isset($userKey) ? $senderID = $userKey : $senderID = $senderID;
	
	if($send['title']) {
		$message = array
		(
			"messaging_type" => "RESPONSE",
			"recipient" => array
			(
				"id" => $senderID
			),
			"message" =>	array
			(
				"attachment" => array
				(
					"type" => "template",
					"payload" => array
					(
						"template_type" => "generic",
						"elements" => array()
					)
				)
			)
		);
	
		$countTitle = count($send['title']);
		$countSubTitle = count($send['subtitle']);
		$countPayload = count($send['payload']);
		$countButtonsTitle = count($send['buttonsTitle']);
		
		for($i=0; $i<$countTitle; $i++) {
			$elementsArrayPush = array
			(
				"title" => $send['title'][$i],
				"subtitle" => $send['subtitle'][$i],
				"buttons" => array()
			);
			array_push($message["message"]["attachment"]["payload"]["elements"], $elementsArrayPush);
			
			if($send['buttonsTitle'] && $send['payload']) {
				for($j=0; $j<$countButtonsTitle; $j++) {
					// readEvent -> deleteEvent 의 경우
					if($countButtonsTitle == 1) {
						$elementsButtonsArrayPush = array
						(
							"type" => "postback",
							"title" => $send['buttonsTitle'][0],
							"payload" => $send['payload'][$i]
						);
					} else {
						if(!is_array($send['buttonsTitle'][$i])) {
							// "등록된 교과목 정보 보기" 의 경우
							$elementsButtonsArrayPush = array
							(
								"type" => "postback",
								"title" => $send['buttonsTitle'][$j],
								"payload" => $send['payload'][$i][$j]
							);
						}
						else if(is_array($send['buttonsTitle'][$i])) {
							$countButtonsTitle = count($send['buttonsTitle'][$i]);
							$elementsButtonsArrayPush = array
							(
								"type" => "postback",
								"title" => $send['buttonsTitle'][$i][$j],
								"payload" => $send['payload'][$i][$j]
							);	
						} else {
							// "등록된 과제,휴강,시험 정보 보기"의 경우
							$elementsButtonsArrayPush = array
							(
								"type" => "postback",
								"title" => $send['buttonsTitle'][$j],
								"payload" => $send['payload'][$j]
							);								
						}	
					}
					array_push($message["message"]["attachment"]["payload"]["elements"][$i]["buttons"], $elementsButtonsArrayPush);			
				}
			}
			else if(empty($send['buttonsTitle']) || empty($send['payload'])) {
				unset($message["message"]["attachment"]["payload"]["elements"][$i]["buttons"]);
			}
		}
	
		$url = "https://graph.facebook.com/v2.6/me/messages?access_token=".$accessToken;
	
		curlPost($url, $message);		
	}
}

function messageTemplateLeftSlideWithImage($send, $userKey=NULL)
{
	global $senderID, $accessToken;
	
	isset($userKey) ? $senderID = $userKey : $senderID = $senderID;
	
	if($send['title']) {
		$message = array
		(
			"messaging_type" => "RESPONSE",
			"recipient" => array
			(
				"id" => $senderID
			),
			"message" =>	array
			(
				"attachment" => array
				(
					"type" => "template",
					"payload" => array
					(
						"template_type" => "generic",
						"elements" => array()
					)
				)
			)
		);
		
		$countTitle = count($send['title']);
		$countImageUrl = count($send['imageURL']);
		$countSubTitle = count($send['subtitle']);
		$countPayload = count($send['payload']);
		$countButtonsTitle = count($send['buttonsTitle']);
		
		for($i=0; $i<$countTitle; $i++) {
			$elementsArrayPush = array
			(
				"title" => $send['title'][$i],
				"image_url" => $send['imageURL'][$i],
				"subtitle" => $send['subtitle'][$i],
				"buttons" => array()
			);
			if($countImageUrl == 0 || ($countTitle != $countImageUrl)) {
				unset($elementsArrayPush['image_url']);
			}
			if($countSubTitle == 0 || ($countTitle != $countSubTitle)) {
				unset($elementsArrayPush['subtitle']);
			}
			array_push($message["message"]["attachment"]["payload"]["elements"], $elementsArrayPush);
			
			if($countButtonsTitle > 0 && $countPayload > 0) {
				$elementsButtonsArrayPush = array
				(
					"type" => "postback",
					"title" => $send['buttonsTitle'][$i],
					"payload" => $send['payload'][$i]
				);
				array_push($message["message"]["attachment"]["payload"]["elements"][$i]["buttons"], $elementsButtonsArrayPush);
			}
		}
	}
	
	$url = "https://graph.facebook.com/v2.6/me/messages?access_token=".$accessToken;

	curlPost($url, $message);				
}


/*
function setPersistentMenu()
{
	global $accessToken;
	
	$message = array
	(
		"persistent_menu" => array
		(
			array
			(
				"locale" => "default",
				"composer_input_disabled" => FALSE,
				"call_to_actions" => array
				(
					array
					(
						"title" => "초기화면",
						"type" => "postback",
						"payload" => "초기화면"
					)
				)
			)
		)
	);
	
	$url = "https://graph.facebook.com/v2.6/me/messenger_profile?access_token=".$accessToken;
	
	curlPost($url, $message);
}*/
/*
function setStartMessage()
{
	global $accessToken;
	
	$message = array
	(
		"setting_type" => "greeting",
		"greeting" => array
		(
			"text" => "Hello {{user_full_name}} ! We're NONAME !"
		)
	);
	
	$url = "https://graph.facebook.com/v2.6/me/thread_settings?access_token=".$accessToken;
	
	curlPost($url, $message);
}

function setStartButton()
{
	global $accessToken;
	
	$button = array
	(	
		"get_started" => array
		(
			"payload" => "NONAME_START"
		)
	);
	
	$url = "https://graph.facebook.com/v2.6/me/messenger_profile?access_token=".$accessToken;
	
	curlPost($url, $button);
}

function setStartDelete($command)
{
	global $accessToken;
		
	if($command == "인사말") {
		$fields = "greeting";
	}
	else if($command == "시작버튼") {
		$fields = "get_started";
	}
	
	$message = array
	(
		"setting_type" => $fields
	);
	
	$url = "https://graph.facebook.com/v2.6/me/thread_settings?access_token=".$accessToken;
	
	curlDelete($url, $message);
}
*/

