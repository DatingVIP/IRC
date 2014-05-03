IRC
===
*IRC for kittehs, and also PHP programmers ...*

We are going to have some IRC based collaboration tools (from the late 90's), we required a bot.

This repository contains a multithreaded PHP IRC bot ... because apparently I'm 15 again ...

Codez
=====
*U can haz codez ...*

```php
<?php
require_once("vendor/autoload.php");

use DatingVIP\IRC\Connection as IRC;
use DatingVIP\IRC\Message;
use DatingVIP\IRC\Listener;
use DatingVIP\IRC\Responder;
use DatingVIP\IRC\Logger;

class IO implements Logger {
	public function onSend($line)    { printf("> %s\n", $line); }
	public function onReceive($line) { printf("< %s\n", $line); }
}

class Respond extends Responder {
	public function __construct(IRC $irc, Message $msg) {
		$this->irc = $irc;
		$this->msg = $msg;
	}
	
	public function onRespond() {
		/* we can do whatever we want here: 
			search docs, take as long as we want/need */
		$this->irc->msg(
			$this->msg->getNick(),
			$this->msg->getText());
		$this->irc->msg(
			$this->msg->getChannel(),
			sprintf(
				"%s said \"%s\" to %s",
				__CLASS__,
				$this->msg->getText(),
				$this->msg->getNick()));
	}
	
	protected $irc;
	protected $msg;
}

class Listen implements Listener {
	public function __construct($nick) {
		$this->nick = $nick;
	}
	
	public function onReceive(IRC $irc, Message $msg) {
		if ($msg->getType() == "PRIVMSG" &&
			$msg->getNick() == $this->nick) {
			/* returning a responder object 
				threads the response */
			return new Respond($irc, $msg);
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
$irc->addListener(
	new Listen("test-user"));

/* login, join channels and enter main loop */
$irc->login("bot")
	->join("#some-channel")
	->loop(true);
?>
```
