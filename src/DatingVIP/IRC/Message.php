<?php
namespace DatingVIP\IRC;

class Message {
	public function __construct($line) {
		if (preg_match("~:([^:]+):?(.*)~", $line, $match)) {
			$match[1]      = preg_split("~ +~", $match[1]);
			$match[1][0]   = preg_split("~!~", $match[1][0], 2);
			
			switch (count($match[1][0])) {
				case 2: $this->nick = array_shift($match[1][0]);
				case 1: $this->host = array_shift($match[1][0]);
			}
			
			$this->type    = $match[1][1];
			$this->chan    = $match[1][2];
			$this->text    = $match[2];
			$this->line    = $line;
		}
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
}
?>
