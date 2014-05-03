<?php
namespace DatingVIP\IRC;

abstract class Responder extends \Threaded implements Collectable {
	abstract public function onRespond();

	final public function run() { 
		$this->onRespond(); 
		$this
			->setGarbage();
	}

	public function isGarbage()  { return $this->garbage; }
	public function setGarbage() { $this->garbage = true; }

	protected $garbage = false;
}
?>
