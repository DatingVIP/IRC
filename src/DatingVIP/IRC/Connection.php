<?php
namespace DatingVIP\IRC;

class Connection {
/**
 * Constructs a connection to the specified server
 * @param string  server
 * @param integer port
 * @param boolean ssl
 * @param integer timeout
 * @throws \RuntimeException
 */
	public function __construct($server, $port, $ssl = false, $timeout = 5) {
		$this->server = $server;
		$this->port   = $port;
		
		if ($ssl) {
			$this->handle = stream_socket_client(
				"tls://{$server}:{$port}",
				$errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, 
				stream_context_create(array(
					"ssl" => array(
						"allow_self_signed" => true,
						"verify_peer_name"  => false
					)
			)));
		} else {
			$this->handle = stream_socket_client(
				"tcp://{$server}:{$port}", $errno, $errstr, $timeout);
		}
		
		if (!$this->handle) {
			throw new \RuntimeException(
				"failed to open server {$server}:{$port} {$errstr}");
		}
	}

/**
 * Login into server as nick with optional password
 * @param string nick
 * @param string password
 * @return Connection
 * @throws \RuntimeException
 */
	public function login($nick, $password = null) {
		if ($password) {
			if (!$this->send("PASS {$password}")) {
				throw new \RuntimeException(
					"failed to send password to {$this->server}");
			}
		} else {
			if (!$this->send("PASS NOPASS")) {
				throw new \RuntimeException(
					"failed to send nopass to {$this->server}");
			}
		}
		
		if (!$this->send("NICK {$nick}")) {
			throw new \RuntimeException(
				"failed to set nick {$nick} on {$this->server}");
		}
		
		$this->loop(-1);
		
		if (!$this->send("USER {$nick} AS IRC BOT")) {
			throw new \RuntimeException(
				"failed to set user {$nick} on {$this->server}");
		}
		
		$this->loop(-1);
		
		return $this;
	}

/**
 * Join a channel
 * @param string channel
 * @return Connection
 * @throws \RuntimeException
 */
	public function join($channel) {
		if (!$this->send("JOIN {$channel}")) {
			throw new \RuntimeException(
				"failed to join {$channel} on {$this->server}");
		}
		return $this;
	}
	
/**
 * Enter into IO loop
 * @param boolean main
 * @return Connection
 * @throws \RuntimeException
 */
	public function loop($main = false) {
		while (($line = fgets($this->handle)) && ($line = trim($line))) {
			if ($this->logger) {
				$this->logger
					->onReceive($line);
			}
			
			if (preg_match("~^ping :(.*)?~i", $line, $pong)) {
				if (!$this->send("PONG {$pong[1]}")) {
					throw new \RuntimeException(
						"failed to send PONG to {$this->server}");
				}
			} else {
				$message = new Message($line);
				foreach ($this->listeners as $listener) {
					if ($listener->onReceive($this, $message))
						break;
				}
			}
			
			if ($main < 0)
				return $this;
		}
		
		return $main ?
			$this->loop($main) : $this;
	}
	
/**
 * Set the logging object
 * @param Logger logger
 * @return Connection
 */
	public function setLogger(Logger $logger) { 
		$this->logger = 
			$logger;
		return $this; 
	}

/**
 * Add a Listener object
 * @param Listener listener
 * @return Connection
 */
	public function addListener(Listener $listener) {
		$this->listeners[] = 
			$listener;
		return $this;
	}
	
/**
 * Send a message to nick or channel
 * @param string to nick or channel
 * @param string message
 * @return Connection
 * @throws \RuntimeException
 */
	public function msg($to, $message) {
		if (!$this->send("PRIVMSG {$to} {$message}")) {
			throw new \RuntimeException(
				"failed to send message {$message} to {$to}");
		}
		
		return $this;
	}
	
/**
 * Send command to server
 * @access protected
 * @param string command
 * @return Connection
 * @throws \RuntimeException
 */
	protected function send($command) {
		if ($this->logger) {
			$this->logger
				->onSend($command);
		}

		$command = "{$command}\n";
		
		if (!fwrite($this->handle, $command, strlen($command))) {
			throw new \RuntimeException(
				"failed to send command {$command} to {$this->server}");
		}

		return true;
	}
	
	public $server;
	public $port;
	public $handle;
	public $logger;
	public $listeners;
}
?>
