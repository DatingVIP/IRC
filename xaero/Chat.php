<?php
namespace xaero;

class Chat {

	public $nick;
	public $text;
	public $time;

	public function __construct($nick, $text) {
		$this->nick = $nick;
		$this->text = $text;
		$this->time = time();
	}

	public function getTime() {

		$seconds = time() - $this->time;

		$days = floor($seconds / 86400);
		$seconds -= $days * 86400;
		$hours = floor($seconds / 3600);
		$seconds -= $hours * 3600;
		$minutes = floor($seconds / 60);
		return ($days ? $days . 'd ' : '') . ($hours ? $hours . 'h ' : '') . $minutes . 'm';
	}
}
?>