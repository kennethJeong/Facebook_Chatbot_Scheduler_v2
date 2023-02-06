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
		
		#status {
			position: absolute;
			top: 40%;
			left: 60%;
		}
	</style>

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
					var uid = response.authResponse.userID;
    				var accessToken = response.authResponse.accessToken;
				    FB.api('/me', function(response) {
						document.getElementById('status').innerHTML = 'Thanks for logging in, ' + response.name + '! <br/><br/>';
						document.getElementById('status').innerHTML += 'Please wait until <b>Message Box</b> appears !';
				    });
				    
			    	var form = document.createElement("form");
					form.setAttribute("method", "post");
					form.setAttribute("action", "./fbMessageBox.php");
					
					var hiddenField = document.createElement("input");
				    hiddenField.setAttribute("type", "hidden");
				    hiddenField.setAttribute("name", "uid");
				    hiddenField.setAttribute("value", uid);
				    form.appendChild(hiddenField);
				    
				    var hiddenField = document.createElement("input");
				    hiddenField.setAttribute("type", "hidden");
				    hiddenField.setAttribute("name", "accessToken");
				    hiddenField.setAttribute("value", accessToken);
				    form.appendChild(hiddenField);
				    
			        document.body.appendChild(form);
					form.submit();
				} else {
					window.location.replace('./fbLogin.php');
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
					
	</script>
	<a href="./fbMain.php" onMouseOver="this.style.color='#999999'" onMouseOut="this.style.color='#000000'" border="0">
		<div class="main_text">
			<p class="main_text_1">BHandy</p>
			<p class="main_text_2">- To Be Handy With Your Life -</p>
		</div>
	</a>
</body>
</html>

