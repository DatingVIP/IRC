<?php
namespace DatingVIP\IRC;

class Message {
	public function __construct($line) {
		if (preg_match(MESSAGE::REGEXP, $line, $match)) {
			switch ($match[2]) {
				case "!":
					/* user message */
					$this->nick    = $match[1];
					$this->host    = $match[3];
					$this->type    = $match[4];
					$this->chan    = $match[5];
					$this->text    = $match[6];
				break;
				
				default:
					/* server message */
					$this->host    = $match[1];
					$this->type    = $match[3];
					$this->nick    = $match[4];
					$this->chan    = $match[5];
					$this->text    = $match[6];
			}
		}
		$this->line = $line;
	}
	
	public function getLine()    { return $this->line; }
	public function getHost()    { return $this->host; }
	public function getType()    { return $this->type; }
	public function getNick()    { return $this->nick; }
	public function getChannel() { return $this->chan; }
	public function getText()    { return $this->text; }

	protected $line;
	protected $host;
	protected $type;
	protected $nick;
	protected $chan;
	protected $text;
	
	const REGEXP = "~^:?([^ ]+)([!| ])([^ ]+) ([^ ]+) ([^ ]+) :?(.*)$~";
}
?>
