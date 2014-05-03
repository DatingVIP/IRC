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

use DatingVIP\IRC\Connection;
use DatingVIP\IRC\Listener;
use DatingVIP\IRC\Logger;
use DatingVIP\IRC\Message;
use DatingVIP\IRC\Responder;
use DatingVIP\IRC\Robot;

class Log implements Logger {
	public function onSend($line)    { printf("> %s\n", $line); }
	public function onReceive($line) { printf("< %s\n", $line); }
}

class Respond extends Responder {
	public function __construct(Connection $irc, Message $msg) {
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
	
	public function onReceive(Connection $irc, Message $msg) {
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

/* open connection to server */
$connection = new Connection("irc.efnet.org", 6667);

/* make sure we see all input/output */
$connection->setLogger(new Log());

/* create robot with default pool */
$robot = new Robot($connection, new Pool(4));

/* add listeners */
$robot->addListener(
	new Listen("test-user2"));

/* login, join channels and enter main loop */
$robot->login("bot")
	->join("#devs")
	->loop();
?>
```
