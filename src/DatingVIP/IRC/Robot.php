<?php
namespace DatingVIP\IRC;

class Robot {
/**
 * Constructs a robot using the specified connection, pool and manager
 * @param Connection connection
 * @param Pool       pool
 * @param Manager    manager
 */
	public function __construct(Connection $connection, \Pool $pool, Manager $manager = null) {
		$this->connection = $connection;
		$this->pool       = $pool;
		$this->manager    = $manager;
		$this->listeners  = [];
	}

/**
 * Add a Listener object
 * @param Listener listener
 * @return Connection
 * @note must be executed synchronously (in the context that created the Robot)
 */
	public function addListener(Listener $listener) {
		$this->listeners[] = $listener;
		return $this;
	}
	
/**
 * Remove a Listener Object
 * @param Listener listener
 * @note must be executed synchronously (in the context that created the Robot)
 */
	public function removeListener(Listener $listener) {
		foreach ($this->listeners as $id => $listening) {
			if ($listener == $listening) {
				unset($this->listeners[$id]);
				break;
			}
		}
	}
	
/**
 * Sets the Manager object, may be used at runtime
 * @param Manager manager
 * @note must be executed synchronously (in the context that created the Robot)
 */
	public function setManager(Manager $manager) {
		$this->manager = $manager;
	}
	
/**
 * Gets the Manager object, may be used at runtime
 * @param Manager manager
 * @note must be executed synchronously (in the context that created the Robot)
 */
	public function getManager() {
		return $this->manager;
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
		if ($main && $this->manager) {
			$this->manager->onStartup($this);
		}
		
		while (($line = $this->connection->recv())) {
			if (preg_match("~^ping :(.*)?~i", $line, $pong)) {
				if (!$this->connection->send("PONG {$pong[1]}")) {
					throw new \RuntimeException(
						"failed to send PONG to {$this->server}");
				}
			} else {
				$message = new Message($line);
				
				/* management interface invokation */
				if ($main && $this->manager) {
					switch ($message->getType()) {
						case Message::join: $this->manager->onJoin($this, $message); break;
						case Message::part: $this->manager->onPart($this, $message); break;
						case Message::nick: $this->manager->onNick($this, $message); break;
						case Message::priv: $this->manager->onPriv($this, $message); break;
						/* and so on ... */
					}
				}
				
				/* listener interface invokation */
				foreach ($this->listeners as $listener) {
					$response = $listener
						->onReceive($this->connection, $message);
					if ($response) {
						if (($response instanceof Task)) {
							$this->pool
								->submit($response);
							continue;
						}
						
						throw new \RuntimeException(sprintf(
							"%s returned an invalid response, ".
							"expected Task object or nothing",
							get_class($listener)));
					}
				}
			}
			
			if (!$main)
				break;

			$this->pool->collect(function($responder) {
				if ($responder instanceof Task) {
					return $responder->isGarbage();
				} else return true;
			});
		}
		
		if ($main && $this->manager) {
			$this->manager->onShutdown($this);
		}

		return $main ? 
			$this->loop($main) : $this;
	}

	protected $connection;
	protected $pool;
	protected $listeners;
}
?>
