<?php
namespace xaero;

require_once('Chat.php');
require_once('User.php');

use DatingVIP\IRC\Message;
use DatingVIP\IRC\Robot;

class Xaero extends Robot {

	private $users = array();
	private $chats = array();

	private function command($message) {

		$nick = $message->getNick();
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
					'@last [size]=10 [nick] - Retrieves latest chats, optionally filtered by nick',
					'@name <name> - Sets your name',
					'@off - Signs you off',
					'@on [task] - Signs you in and sets your current task',
					'@task <task> - Sets your task',
					'@who [nick] - Lists users and their statuses, optionally filtered by nick'
				);
				break;

			case 'last':
				$size = 10;
				$nick = '';
				if(count($arguments)) {
					if(preg_match('/^[0-9]+$/', $arguments[0])) {
						$size = array_shift($arguments);
					}
					if(count($arguments)) {
						$nick = array_shift($arguments);
					}
				}
				if(strlen($nick)) {
					$response = $this->getLast($size, $nick);
				} else {
					$response = $this->getLast($size);
				}
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

			case 'task':
				if(count($arguments)) {
					$task = implode(' ', $arguments);
					$response = $this->users[$nick]->setTask($task);
				}
				break;

			case 'who':
				if(count($arguments)) {
					$nick = array_shift($arguments);
					if(isset($this->users[$nick])) {
						$response = $this->users[$nick]->getStatus();
					}
				}
				else {
					$response = array();
					foreach($this->users as $user) {
						$status = $user->getStatus();
						if($status) {
							$response[] = $status;
						}
					}
				}
				break;
		}

		if(is_array($response) || strlen($response)) {
			return $response;
		}
	}

	private function getLast($size, $nick = '') {

		if($size <= 0) { return; }

		if(strlen($nick) && !isset($this->users[$nick])) { return; }

		$last = array();
		foreach(array_reverse($this->chats) as $chat) {
			if(strlen($nick) && strcmp($chat->nick, $nick)) {
				continue;
			}
			$last[] = $chat->nick . ': ' . $chat->text . ' [' . $chat->getTime() . ' ago]';
			if(!--$size) {
				break;
			}
		}

		return array_reverse($last);
	}

	public function loop($main = true) {

		while(($line = $this->connection->recv())) {

			if(preg_match('~^ping :(.*)?~i', $line, $pong)) {
				if(!$this->connection->send("PONG {$pong[1]}")) {
					throw new \RuntimeException("failed to send PONG to {$this->server}");
				}
			} else {
				$message = new Message($line);
				$nick = $message->getNick();
				$text = trim($message->getText());
				$where = strpos($message->getChannel(), '#') !== false ? $message->getChannel() : $message->getNick();
				if($message->getType() == 'JOIN') {
					if(isset($this->users[$nick])) {
						$this->users[$nick]->setOffTime();
					}
				}
				elseif($message->getType() == 'PART' || $message->getType() == 'QUIT') {
					if(isset($this->users[$nick])) {
						$this->users[$nick]->setOffTime(600);
					}
				}
				elseif($message->getType() == 'NICK') {
					if(isset($this->users[$nick])) {
						$this->users[$text] = $this->users[$nick];
						$this->users[$text]->setNick($text);
						unset($this->users[$nick]);
					}
				}
				elseif($message->getType() == 'PRIVMSG') {
					if(!isset($this->users[$nick])) {
						$this->users[$nick] = new User($nick);
					}
					if(!strcmp($text[0], '@')) {
						$response = $this->command($message);
						if(isset($response)) {
							if(is_array($response)) {
								foreach($response as $text) {
									$this->connection->msg($where, $text);
								}
							} else {
								$this->connection->msg($where, $response);
							}
						}
					} elseif(strpos($where, '#') !== false) {
						$chat = new Chat($message->getNick(), $text);
						$this->chats[] = $chat;
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