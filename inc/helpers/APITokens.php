<?php

class APITokens {
	static function GetToken() {
		/*if (isset($_SESSION["APIToken"])) {
			return $_SESSION["APIToken"];
		}
		$key = randomString(32, '0123456789abcdef');
		$GLOBALS["db"]->execute("INSERT INTO tokens(user, privileges, description, token, private) VALUES (?, '0', ?, ?, '1')", array($userID, getIP(), md5($key)));
		$_SESSION["APIToken"] = $key;
		return $key;*/
	}
	static function PrintScript($additionalVars = '') {
		if ($additionalVars == "") {
			return;
		}
		echo '<script>
		' . $additionalVars . '
		</script>';
	}
}
