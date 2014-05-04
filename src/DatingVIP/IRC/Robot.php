<?php
namespace DatingVIP\IRC;

class Robot {
/**
 * Constructs a robot using the specified connection and thread pool
 * @param Connection connection
 * @param Pool       pool
 */
	public function __construct(Connection $connection, \Pool $pool) {
		$this->connection = $connection;
		$this->pool       = $pool;
		$this->listeners  = [];
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
 * Login into server as nick with optional password
 * @param string nick
 * @param string password
 * @return Connection
 * @throws \RuntimeException
 */
	public function login($nick, $password = null) {
		if ($password) {
			if (!$this->connection->send("PASS {$password}")) {
				throw new \RuntimeException(
					"failed to send password to {$this->server}");
			}
		} else {
			if (!$this->connection->send("PASS NOPASS")) {
				throw new \RuntimeException(
					"failed to send nopass to {$this->server}");
			}
		}
		
		if (!$this->connection->send("NICK {$nick}")) {
			throw new \RuntimeException(
				"failed to set nick {$nick} on {$this->server}");
		}
		
		$this->loop(false);
		
		if (!$this->connection->send("USER {$nick} AS IRC BOT")) {
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
		if (!$this->connection->send("JOIN {$channel}")) {
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
		while (($line = $this->connection->recv())) {
			if (preg_match("~^ping :(.*)?~i", $line, $pong)) {
				if (!$this->connection->send("PONG {$pong[1]}")) {
					throw new \RuntimeException(
						"failed to send PONG to {$this->server}");
				}
			} else {
				$message = new Message($line);

				foreach ($this->listeners as $listener) {
					$response = $listener
						->onReceive($this->connection, $message);
					if ($response) {
						if (($response instanceof Responder)) {
							$this->pool
								->submit($response);
							continue;
						}
						
						throw new \RuntimeException(sprintf(
							"%s returned an invalid response, ".
							"expected Responder object",
							get_class($listener)));
					}
				}
			}
			
			if (!$main)
				break;

			$this->pool->collect(function(Responder $responder) {
				return $responder->isGarbage();
			});
		}

		return $main ? 
			$this->loop($main) : $this;
	}

	protected $connection;
	protected $pool;
	protected $listeners;
}
?>
