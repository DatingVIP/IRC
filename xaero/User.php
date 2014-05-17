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
	private $working_on = '';

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
			$status = (isset($this->name) ? $this->name : $this->nick) . ' is ';
			if($this->status == OFF) {
				$status .= 'off since ' . $this->secondsToString((time() - $this->sign_out)) . ' ago.';
			} else {
				$status .= 'working since ' . $this->secondsToString((time() - $this->sign_in)) . ' ago';
				if(strlen($this->working_on)) {
					$status .= ', working on \'' . $this->working_on . '\'';
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

	public function signIn($working_on = '') {

		switch($this->status) {
			case ON:
				return 'You are already on.';
				break;
			case AWAY:
				return 'You are already on (and away actually).';
				break;
		}

		$this->status = ON;
		$this->sign_in = time();
		if(strlen($working_on)) {
			$this->working_on = $working_on;
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