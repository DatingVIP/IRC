<?php
namespace DatingVIP\IRC;

abstract class Task extends \Threaded {
/**
 * Executed asynchronously by Pool to be implemented by programmer
 * @returns void
 */
	abstract public function __invoke();

/**
 * Executed by Pool, ensures garbage is set after execution
 */
	final public function run() { 
		$this();
		$this->setGarbage();
	}

/**
 * Tells the Pool this object is ready for collection
 * @returns boolean
 */
	final public function isGarbage()  { 
		return $this->garbage; 
	}

/**
 * Marks this object as garbage
 * @access protected
 */
	final protected function setGarbage() { 
		$this->garbage = true; 
	}

	protected $garbage = false;
}
?>
