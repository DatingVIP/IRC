<?php
namespace DatingVIP\IRC;

interface Listener {
/**
 * Receives Messages from Connection
 * @param Connection robot
 * @param Message msg
 * @return Responder
 */
	public function onReceive(Connection $robot, Message $msg);
}
?>
