<?php

class Schiavo {
	const BaseURL = "http://zxq.co:31155/";

	/**
	 * Sends a message to the channel #bunker.
	 *
	 * @param string $message
	 */
	static function Bunk($message) {
		return __schiavoCall("bunk", $message);		
	}
	
	static function CM($message) {
		return __schiavoCall("cm", $message);
	}
}

// Can a static function call a private function in the same class?
// I don't remember. And don't wanna test to figure it out. So fuck it.
function __schiavoCall($c, $message) {
	//if ($_SERVER["REMOTE_ADDR"] != "127.0.0.1")
		return file_get_contents(Schiavo::BaseURL . $c . "?message=" . urlencode("**old-frontend** " . $message));

}
