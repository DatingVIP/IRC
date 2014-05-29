<?php
namespace xaero;

define('OFF', 0);
define('ON', 1);
define('AWAY', 2);

class User {

	private $nick;
	private $name;
	private $status = OFF;

	private $sign_in = 0;
	private $sign_out = 0;
	private $task = '';

	private $away_until = 0;
	private $away_reason = '';

	public function __construct($nick) {
		$this->nick = $nick;
	}

	public function away($away_until = 0, $away_reason = '') {

		switch($this->status) {
			case OFF:
				return 'You can\'t set your status away while being off.';
				break;
			case AWAY:
				return 'You are already away.';
				break;
		}

		$this->status = AWAY;
		if($away_until > 0 && $away_until > time()) {
			$this->away_until = $away_until;
		}
		if(strlen($away_reason)) {
			$this->away_reason = $away_reason;
		}
	}

	public function back() {

		switch($this->status) {
			case OFF:
				return 'You are not away (and you are off actually).';
				break;
			case ON:
				return 'You are not away.';
				break;
		}

		$this->status = ON;
		$this->away_until = 0;
		$this->away_reason = '';
	}

	public function getStatus() {
		if($this->sign_in || $this->sign_out) {
			if($this->status == AWAY) {
				if($this->away_until > 0 && time() >= $this->away_until) {
					$this->back();
				}
			}
			if($this->status == OFF) {
				if(time() - $this->sign_out > 86400) {
					$this->sign_in = 0;
					$this->sign_out = 0;
					$this->task = '';
					$this->away_until = 0;
					$this->away_reason = '';
					return false;
				}
			}
			$status = (isset($this->name) ? $this->name : $this->nick) . ' is ';
			if($this->status == OFF) {
				$status .= 'off since ' . $this->secondsToString((time() - $this->sign_out)) . ' ago.';
			} else {
				$status .= 'working since ' . $this->secondsToString((time() - $this->sign_in)) . ' ago';
				if(strlen($this->task)) {
					$status .= ', working on \'' . $this->task . '\'';
				}
				if($this->status == AWAY) {
					$status .= ', away';
					if(strlen($this->away_reason)) {
						$status .= ' (reason: \'' . $this->away_reason . '\')';
					}
					if($this->away_until > 0) {
						$status .= ' and will be back in ' . $this->secondsToString(($this->away_until - time() + 60));
					}
				}
				$status .= '.';
			}
			return $status;
		}
	}

	private function secondsToString($seconds) {
		$days = floor($seconds / 86400);
		$seconds -= $days * 86400;
		$hours = floor($seconds / 3600);
		$seconds -= $hours * 3600;
		$minutes = floor($seconds / 60);
		return ($days ? $days . 'd ' : '') . ($hours ? $hours . 'h ' : '') . $minutes . 'm';
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function setNick($nick) {
		$this->nick = $nick;
	}

	public function setTask($task) {
		switch($this->status) {
			case ON:
				$this->task = $task;
				break;
			default:
				return 'You can\'t set your task while being off.';
				break;
		}
	}

	public function signIn($task = '') {

		switch($this->status) {
			case ON:
				return 'You are already working.';
				break;
			case AWAY:
				return 'You are already working (and away actually).';
				break;
		}

		$this->status = ON;
		$this->sign_in = time();
		if(strlen($task)) {
			$this->task = $task;
		}
	}

	public function signOut() {

		switch($this->status) {
			case OFF:
				return 'You are already off.';
				break;
			case AWAY:
				$this->back();
				break;
		}

		$this->status = OFF;
		$this->sign_out = time();
	}
}
?>