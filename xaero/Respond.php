<?php
namespace xaero;

use DatingVIP\IRC\Connection;
use DatingVIP\IRC\Message;
use DatingVIP\IRC\Responder;

class Respond extends Responder {

	protected $irc;
	protected $msg;

	public function __construct(Connection $irc, Message $msg) {
		$this->irc = $irc;
		$this->msg = $msg;
	}

	public function onRespond() {
		$this->irc->msg($this->msg->getNick(), $this->msg->getText());
		// $this->irc->msg($this->msg->getChannel(), sprintf('%s said "%s" to %s', __CLASS__, $this->msg->getText(), $this->msg->getNick()));
	}
}
?>