IRC
===
*IRC for kittehs, and also PHP programmers ...*

We are going to have some IRC based collaboration tools (from the late 90's), we required a bot.

This repository contains a simple PHP based bot to interact with IRC servers ...

Codez
=====
*U can haz codez ...*

```php
<?php
require_once("vendor/autoload.php");

use DatingVIP\IRC\Connection as IRC;
use DatingVIP\IRC\Message;
use DatingVIP\IRC\Listener;
use DatingVIP\IRC\Logger;

class IO implements Logger {
	public function onSend($line)    { printf("> %s\n", $line); }
	public function onReceive($line) { printf("< %s\n", $line); }
}

class Repeat implements Listener {
	public function __construct($nick) {
		$this->nick = $nick;
	}
	
	public function onReceive(IRC $irc, Message $msg) {
		if ($msg->getType() == "PRIVMSG" &&
			$msg->getNick() == $this->nick) {
			$irc->msg(
				$msg->getNick(),
				$msg->getText());
				
			$irc->msg(
				$msg->getChannel(),
				sprintf(
					"%s said \"%s\" to %s",
					__CLASS__, 
					$msg->getText(), 
					$msg->getNick()));
		}
	}
	
	protected $nick;
}

set_time_limit(0);

/* connect to server */
$irc = new IRC("irc.efnet.org", 6667);

/* set logger */
$irc->setLogger(new IO());

/* add listeners */
$irc->addListener(new Repeat("test-user"));

/* login, join channels and enter main loop */
$irc->login("bot")
	->join("#some-channel")
	->loop(true);
?>
```
