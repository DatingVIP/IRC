<?php
namespace DatingVIP\IRC;

interface Listener {
/**
 * Receives Messages from Connection
 * @param Connection connection
 * @param Message msg
 * @return boolean true if no more listeners are to be invoked
 */
	public function onReceive(Connection $connection, Message $msg);
}
?>
