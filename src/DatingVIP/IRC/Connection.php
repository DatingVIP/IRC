<?php
namespace DatingVIP\IRC;

class Connection extends \Threaded {
/**
 * Constructs a connection to the specified server
 * @param string  server
 * @param integer port
 * @param boolean ssl
 * @param integer threads
 * @throws \RuntimeException
 */
	public function __construct($server, $port, $ssl = false, $threads = 4) {
		$this->server = $server;
		$this->port   = $port;
		
		if ($ssl) {
			$this->handle = stream_socket_client(
				"tls://{$server}:{$port}",
				$errno, $errstr, self::getTimeout(), STREAM_CLIENT_CONNECT,
				stream_context_create(array(
					"ssl" => array(
						"allow_self_signed" => true,
						"verify_peer_name"  => false
					)
			)));
		} else {
			$this->handle = stream_socket_client(
				"tcp://{$server}:{$port}", 
				$errno, $errstr, self::getTimeout());
		}
		
		if (!$this->handle) {
			throw new \RuntimeException(
				"failed to open server {$server}:{$port} {$errstr}");
		}
		
		$this->listeners = new \Threaded();
		$this->pool      = new \Pool($threads);
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
		
		$this->loop(false);
		
		if (!$this->send("USER {$nick} AS IRC BOT")) {
			throw new \RuntimeException(
				"failed to set user {$nick} on {$this->server}");
		}
		
		$this->loop(false);
		
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
	public function loop($main = true) {
		while (($line = $this->recv())) {
			if ($main) {
				$this->pool->collect(function(Responder $responder) {
					return $responder->isGarbage();
				});
			}
			
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
					$response = $listener
						->onReceive($this, $message);
					if ($response) {
						if (($response instanceof Responder)) {
							$this->pool
								->submit($response);
							continue;
						}
						
						throw new \RuntimeException(sprintf(
							"%s returned an invalid response, ".
							"expected Collectable object",
							get_class($listener)));
					}
				}
			}
			
			if (!$main)
				break;
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
	
/**
 * Recv from stream
 * @return string
 * @throws \RuntimeException
 */
	protected function recv() {
		if (($line = fgets($this->handle)) && 
			($line = trim($line))) {
			return $line;
		}
		
		throw new \RuntimeException(
			"failed to receive data from {$this->server}");
	}
	
/**
 * Set global connect timeout in seconds
 * @param int timeout
 * @throws \RuntimeException
 */
	public static function setTimeout($timeout) { self::$timeout = $timeout; }

/**
 * Get global connect timeout in seconds
 * @param int timeout
 * @throws \RuntimeException
 */
	public static function getTimeout() { return self::$timeout; }

	public $server;
	public $port;
	public $handle;
	public $logger;
	public $listeners;
	public static $timeout = 5;
}
?>
