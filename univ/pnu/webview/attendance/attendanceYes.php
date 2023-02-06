<!DOCTYPE html>
<html>
<head>
<title>확인 중입니다..</title>
<meta charset="UTF-8">
<meta name="viewport" content="initial-scale=1.0, user-scalable=no, width=device-width" />
<style type="text/css">
	@-ms-viewport { width: device-width; }
	@-o-viewport { width: device-width; }
	@viewport { width: device-width; }
</style>
<script type="text/javascript" src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
</head>
<body>

	<script>	
	(function(d, s, id){
		var js, fjs = d.getElementsByTagName(s)[0];
		if (d.getElementById(id)) {return;}
		js = d.createElement(s); js.id = id;
		js.src = "//connect.facebook.com/ko_KR/messenger.Extensions.js";
		fjs.parentNode.insertBefore(js, fjs);
	}(document, 'script', 'Messenger'));
	
	window.extAsyncInit = function() {
		// 위치정보
		getLocation();
	
		// 유저키
		MessengerExtensions.getContext('2095689574042516', 
		function success(thread_context){
			document.getElementById('psid').value = thread_context.psid;

			document.forms["geo"].action = "https://bhandy.kr/scheduler/univ/pnu/webview/attendance/attendance.php";
			document.forms["geo"].target = "_Self";
			document.forms["geo"].method = "post";
			document.forms["geo"].submit();	
		});
	};	
	
	function getLocation() {
		navigator.geolocation.getCurrentPosition(function(position) {
			document.getElementById('latitude').value = position.coords.latitude;
			document.getElementById('longitude').value = position.coords.longitude;

		}, function(error) {
			// error
		}, {
			enableHighAccuracy: true,
			maximumAge: 0,
			timeout: Infinity
		});
	}
	</script>
	
	<div id="status"></div>
	
	<form name="geo" id="geo" method="post" action="https://bhandy.kr/scheduler/univ/pnu/webview/attendance/attendance.php">
		<!-- 출석체크 -->
		<input type="hidden" name="attendance" id="attendance" value="Y">
		<input type="hidden" name="psid" id="psid">
		<input type="hidden" name="latitude" id="latitude">
		<input type="hidden" name="longitude" id="longitude">
	</form>
</body>
</html>
