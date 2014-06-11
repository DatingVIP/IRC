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
			
			switch (($this->type[0] = strtolower($match[1][1]))) {

				case "join":    $this->type[1] = Message::join; break;
				case "part":    $this->type[1] = Message::part; break;
				case "nick":    $this->type[1] = Message::nick; break;
				case "privmsg": $this->type[1] = Message::priv; break;

				default:
					$this->type[1] = Message::unknown;
			}
			
			$this->chan    = $match[1][2];
			$this->text    = $match[2];
			$this->line    = $line;
		}
	}
	
	public function getLine()       { return $this->line; }
	public function getHost()       { return $this->host; }
	public function getTypeString() { return $this->type[0]; }
	public function getType()       { return $this->type[1]; }
	public function getNick()       { return $this->nick; }
	public function getChannel()    { return $this->chan; }
	public function getText()       { return $this->text; }

	protected $line;
	protected $host;
	protected $type;
	protected $nick;
	protected $chan;
	protected $text;
	
	const unknown = 0;

	const join    = (1<<1);
	const part    = (1<<2);
	const nick    = (1<<3);
	const priv    = (1<<4);
}
?>
