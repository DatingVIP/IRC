<?php
namespace DatingVIP\IRC;

interface Listener {
/**
 * Receives Messages from Connection
 * @param Connection irc
 * @param Message msg
 * @return Task
 */
	public function onReceive(Connection $irc, Message $msg);
}
?>
