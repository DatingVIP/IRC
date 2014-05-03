<?php
namespace DatingVIP\IRC;

class Connection extends \Threaded {

/**
 * Create a connection to the speicifed IRC server
 * @param string    server
 * @param integer   port
 * @param boolean   ssl
 * @param integer   timeout
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
				"tcp://{$server}:{$port}", 
				$errno, $errstr, $timeout);
		}
		
		if (!$this->handle) {
			throw new \RuntimeException(
				"failed to open server {$server}:{$port} {$errstr}");
		}
	}
	
/**
 * Send command to server
 * @param string command
 * @return Connection
 * @throws \RuntimeException
 * @access synchronized
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
 * Recv response from server
 * @return string
 * @throws \RuntimeException
 * @access synchronized
 */
	protected function recv() {
		if (($line = fgets($this->handle)) && 
			($line = trim($line))) {
			if ($this->logger) {
				$this->logger
					->onReceive($line);
			}
			
			return $line;
		}
		
		throw new \RuntimeException(
			"failed to receive data from {$this->server}");
	}
	
/**
 * Send a message to nick or channel
 * @param string to nick or channel
 * @param string message
 * @return Connection
 * @throws \RuntimeException
 * @access synchronized
 */
	protected function msg($to, $message) {
		if (!$this->send("PRIVMSG {$to} {$message}")) {
			throw new \RuntimeException(
				"failed to send message {$message} to {$to}");
		}
		
		return $this;
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
 * Get server hostname
 * @return string
 */
	public function getServer()  { return $this->server; }

/**
 * Get service port
 * @return integer
 */
	public function getPort()    { return $this->port; }

	protected $server;
	protected $port;
	protected $logger;
	protected $handle;
}
