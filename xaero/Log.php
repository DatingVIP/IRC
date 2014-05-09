<?php
namespace xaero;

use DatingVIP\IRC\Logger;

class Log implements Logger {

	public function onSend($line) {
		printf("> %s\n", $line);
	}

	public function onReceive($line) {
		printf("< %s\n", $line);
	}
}
?>