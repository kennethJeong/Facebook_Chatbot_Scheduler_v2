<!DOCTYPE HTML>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="content-type" content="text/html" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BHandy - To Be Handy With Your Life</title>
</head>

<body>
	<style>
		html {
			background: url(bg.jpg) no-repeat center center fixed;
			-webkit-background-size: cover;
			-moz-background-size: cover;
			-o-background-size: cover;
			background-size: cover;
		}
		
		body {
			font-family: Comic Sans MS;
		}
		
		*:focus {
		    outline: none;
		}
		
		.fb-login-button {
			position: absolute;
			top: 12%;
			left: 83%;			
		}			
		
		.main_text {
			position: absolute;
			left: 5%;
		}
		.main_text_1 {
			font-size: 400%;
		}
		.main_text_2 {
			position: relative;
			margin-top: -15%;			
			left: 25%;
			font-size: 150%;
		}
		
		.main_text_sub {
			position: absolute;
			top: 10%;
			left: 63%;			
		}
		.main_text_sub_1 {		
			font-size: 150%;			
		}
		
		#status {
			position: absolute;
			top: 10%;
			left: 60%;
		}
		
		#mailbox {
			position: absolute;
			top: 25%;
			left: 50%;
		}
		.mailbox_response {
			text-align: right;
			margin-top: -5%;	
			margin-bottom: -2%;
			margin-right: -15%;
			font-size: 80%;
		}
		.mailbox_send {
			text-align: left;
			margin-top: -5%;	
			margin-bottom: -2%;
			margin-left: -15%;
			font-size: 80%;
		}
		#mailbox_response_time {
			margin-top: -1%;	
			margin-bottom: 5%;
			font-size: 20%;
		}
		.mailbox_send_nonexist {
			position: relative;
			left: 20%;
			margin-top: 40%;
			font-size: 200%;
		}
	</style>
	
	<div class="fb-login-button"
		data-max-rows="1"
		data-size="large"
		data-button-type="continue_with"
		data-use-continue-as="true"
		data-auto-logout-link="true"
		data-scope="public_profile"
		onlogin="checkLoginState();"
	></div>  
	
	<!-- facebook -->
	<div id="status"></div>
	
	<script>
		window.fbAsyncInit = function() {
			FB.init({
				appId   : '2095689574042516',
				cookie  : true,
				xfbml   : true,
				oauth: true,
				version : 'v2.8'
			});
			
			FB.getLoginStatus(function(response) {
				if (response.status === 'connected') {
				    FB.api('/me', function(response) {
						document.getElementById('status').innerHTML = 'Thanks for logging in, ' + response.name + '!<br/>';
				    });
				} else {
					window.location.replace('./fbLogin.php');
				}
			});
			
			FB.Event.subscribe('auth.logout', function(response) {
				window.location.replace('./fbLogin.php');
			});
		};
		
		(function(d, s, id) {
			var js, fjs = d.getElementsByTagName(s)[0];
			if (d.getElementById(id)) return;
			js = d.createElement(s); js.id = id;
			js.src = "//connect.facebook.net/ko_KR/sdk.js";
			fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));
					
	</script>
	<a href="./fbMain.php" onMouseOver="this.style.color='#999999'" onMouseOut="this.style.color='#000000'" border="0">
		<div class="main_text">
			<p class="main_text_1">BHandy</p>
			<p class="main_text_2">- To Be Handy With Your Life -</p>
		</div>
	</a>
	
	<div class="main_text_sub">
		<p class="main_text_sub_1">My Message Box</p>
	</div>
	
</body>
</html>

<?php
date_default_timezone_set("Asia/Seoul");

function curlGet($url) {
	
	global $pAccessToken;
	
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");                                                                 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);                                                                                               
	$result = curl_exec($ch);

	return json_decode($result, true);
}

$pid = "170646096902999";
$uid = $_POST['uid'];
$uAccessToken = $_POST['accessToken'];
$pAccessToken = 'EAAdyBMCey5QBAHZBZAlW2HrTo3NG9yDYn7rTRBrSftD8eQSzWmeWOZBzgNJakUHUbu5boQCkkNWiQj0oFxwK4Q2MEk5Wi384461TU5A5ofLcTXcckQCe7B1HWpIuySiw7ZCYDuIeHOC5V8DlUptsSjKsa36tuDhBGPcwIFDSU6JT4MJnqvxd';

// get conversations                                                                             
$resConversations = curlGet("https://graph.facebook.com/v2.12/" . $uid ."/conversations?access_token=" . $pAccessToken);

$mid = $resConversations['data'][0]['id'];                                                                                             
$resMessage = curlGet("https://graph.facebook.com/v2.12/" . $mid ."?fields=messages{message,created_time}&access_token=" . $pAccessToken);
$data = $resMessage['messages']['data'];

echo '<div id="mailbox">';
if($data[0]['id']) {
	for($i=0; $i<count($data); $i++) {
		if(mb_strlen($data[$i]['message'], 'UTF8') > 0) {
			$message = $data[$i]['message'];
			$createdTime = date("Y-m-d H:i:s", strtotime($data[$i]['created_time']));
			$eachmid = $data[$i]['id'];
			                                                                                       
			$resEachmid = curlGet("https://graph.facebook.com/v2.12/" . $eachmid ."?fields=to,from&access_token=" . $pAccessToken);
			$resEachmidToID = $resEachmid['to']['data'][0]['id'];
			$resEachmidFromID = $resEachmid['from']['id'];
			
			if($resEachmidFromID == $pid) {
				// 페이지 -> 유저
				$messageResponse = $message;
				echo "<p class=mailbox_response>";
				echo $messageResponse;
				echo "</p><br /><p id=mailbox_response_time style='text-align:right; margin-right:-15%;'>" . $createdTime;
				echo "</p>";
			} else {
				// 유저 -> 페이지
				$messageSend = $message;
				echo "<p class=mailbox_send>";
				echo $messageSend;
				echo "</p><br /><p id=mailbox_response_time style='text-align:left; margin-left:-15%;>" . $createdTime;
				echo "</p>";
			}
			echo "<br>";
		}	
	} 
} else {
	echo "<p class=mailbox_send_nonexist>";
	echo "Your Mailbox dosen't exist !";
	echo "</p>";
}

echo '</div>';