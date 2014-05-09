<?php
namespace xaero;

require_once('Respond.php');

use DatingVIP\IRC\Connection;
use DatingVIP\IRC\Listener;
use DatingVIP\IRC\Message;

class Listen implements Listener {

	protected $nick;

	public function __construct($nick) {
		$this->nick = $nick;
	}

	public function onReceive(Connection $irc, Message $msg) {
		if($msg->getType() == 'PRIVMSG' && $msg->getNick() == $this->nick) {
			return new Respond($irc, $msg);
		}
	}
}
?>