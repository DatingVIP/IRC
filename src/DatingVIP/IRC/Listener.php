<?php
namespace DatingVIP\IRC;

interface Listener {
/**
 * Receives Messages from Connection
 * @param Connection connection
 * @param Message msg
 * @return \Threaded
 */
	public function onReceive(Connection $connection, Message $msg);
}
?>
