<?php
namespace xaero;

class Chat {

	public $time;
	public $nick;
	public $text;

	public function __construct($nick, $text) {
		$this->time = time();
		$this->nick = $nick;
		$this->text = $text;
	}
}
?>