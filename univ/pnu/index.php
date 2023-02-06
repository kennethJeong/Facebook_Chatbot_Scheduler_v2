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
			background: url(htmls/bg.jpg) no-repeat center center fixed;
			-webkit-background-size: cover;
			-moz-background-size: cover;
			-o-background-size: cover;
			background-size: cover;
		}
		
		.main_text {
			font-family: Comic Sans MS;
			position: absolute;
			top: 30%;
			left: 55%;
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
		
		.fb-login-button {
			font-family: Comic Sans MS;
			position: absolute;
			top: 60%;
			left: 60%;			
		}
		
		#status {
			font-family: Comic Sans MS;
			position: absolute;
			top: 65%;
			left: 60%;
		}
	</style>
	
	<!-- login btn -->
	<div class="fb-login-button"
		data-max-rows="1"
		data-size="large"
		data-button-type="continue_with"
		data-use-continue-as="true"
		data-scope="public_profile"
		onLogin="checkLoginState();"
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
			
			// Check whether the user already logged in
		    FB.getLoginStatus(function(response) {
		        if (response.status === 'connected') {
		            window.location.replace('./htmls/fbMain.php');
		        }
		    });
		};
		
		(function(d, s, id) {
			var js, fjs = d.getElementsByTagName(s)[0];
			if (d.getElementById(id)) return;
			js = d.createElement(s); js.id = id;
			js.src = "//connect.facebook.net/ko_KR/sdk.js";
			fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));

		function checkLoginState() {
			FB.login(function(response) {
				if(response.authResponse) {
					window.location.replace('./htmls/fbMain.php');
				} else {
		            document.getElementById('status').innerHTML = 'User cancelled login or did not fully authorize.';
		        }
			}, {scope: 'read_page_mailboxes'});
		}			
	</script>
	
	<div class="main_text">
		<p class="main_text_1">BHandy</p>
		<p class="main_text_2">- To Be Handy With Your Life -</p>
	</div>
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
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
include_once $_SERVER["DOCUMENT_ROOT"] . '/scheduler/univ/pnu/dbInfo.php';
include_once $_SERVER["DOCUMENT_ROOT"] . '/scheduler/univ/pnu/lib.php';
foreach(glob($_SERVER["DOCUMENT_ROOT"] . '/scheduler/univ/pnu/function/*.php') as $functionFiles)
{
    include_once $functionFiles;
}
include_once $_SERVER["DOCUMENT_ROOT"] . '/scheduler/univ/pnu/config.php';
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////// 부산대학교 /////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
include_once $_SERVER["DOCUMENT_ROOT"] . '/scheduler/univ/pnu/scheduler_PNU.php';
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
