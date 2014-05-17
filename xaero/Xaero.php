<?php
namespace xaero;

// require_once('Chat.php');
require_once('User.php');

use DatingVIP\IRC\Message;
use DatingVIP\IRC\Robot;

class Xaero extends Robot {

	private $users = array();
	// private $chats = array();

	private function command($message) {

		$nick = $message->getNick();
		if(!isset($this->users[$nick])) {
			$this->users[$nick] = new User($nick);
		}

		$arguments = explode(' ', $message->getText());
		$command = substr(array_shift($arguments), 1);

		switch($command) {

			case 'away':
				$away_until = 0;
				$away_reason = '';
				if(count($arguments)) {
					if(preg_match('/^[0-9]+[hm]([0-9]+m)?$/i', $arguments[0])) {
						$away_until = strtotime('+' . str_replace(array('h', 'm'), array('hour', 'min'), array_shift($arguments)));
					}
					if(count($arguments)) {
						$away_reason = implode(' ', $arguments);
					}
				}
				$response = $this->users[$nick]->away($away_until, $away_reason);
				break;

			case 'back':
				$response = $this->users[$nick]->back();
				break;

			case 'help':
				$response = array(
					'Available commands:',
					'@away [time] [reason] - Sets your status away, if time is supplied (5m, 1h, 1h30m) there\'s no need to issue @back command',
					'@back - Removes away status (gets you back to work)',
					'@name - Sets your name',
					'@off - Signs you off',
					'@on [task] - Signs you in and sets your current task',
					'@who - Lists users and their statuses'
				);
				break;

			case 'name':
				if(count($arguments)) {
					$name = implode(' ', $arguments);
					$this->users[$nick]->setName($name);
				}
				break;

			case 'off':
				$response = $this->users[$nick]->signOut();
				break;

			case 'on':
				$working_on = '';
				if(count($arguments)) {
					$working_on = implode(' ', $arguments);
				}
				$response = $this->users[$nick]->signIn($working_on);
				break;

			case 'who':
				$response = array();
				foreach($this->users as $user) {
					$response[] = $user->getStatus();
				}
				break;
		}

		if(isset($response)) {
			return $response;
		}
	}

	public function loop($main = true) {

		while(($line = $this->connection->recv())) {

			if(preg_match('~^ping :(.*)?~i', $line, $pong)) {
				if(!$this->connection->send("PONG {$pong[1]}")) {
					throw new \RuntimeException("failed to send PONG to {$this->server}");
				}
			} else {
				$message = new Message($line);
				if($message->getType() == 'PRIVMSG') {
					$text = trim($message->getText());
					if(!strcmp($text[0], '@')) {
						$response = $this->command($message);
						if(isset($response)) {
							$where = strpos($message->getChannel(), '#') !== false ? $message->getChannel() : $message->getNick();
							if(is_array($response)) {
								foreach($response as $text) {
									$this->connection->msg($where, $text);
								}
							} else {
								$this->connection->msg($where, $response);
							}
						}
					} else {
						// $chat = new Chat($message->getNick(), $text);
						// $this->chats[] = $chat;
					}
				}
			}

			if(!$main) {
				break;
			}

			$this->pool->collect(function($responder) {
				if($responder instanceof Responder) {
					return $responder->isGarbage();
				} else {
					return true;
				}
			});
		}

		return $main ? $this->loop($main) : $this;
	}
}