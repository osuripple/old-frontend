<?php

class Schiavo {
	/**
	 * Sends a message to the channel #bunker.
	 *
	 * @param string $message
	 */
	static function Bunk($message) {
		//if ($_SERVER["REMOTE_ADDR"] != "127.0.0.1")
			return file_get_contents("http://zxq.co:31155/bunk?message=" . urlencode("**old-frontend** " . $message));
	}
}
