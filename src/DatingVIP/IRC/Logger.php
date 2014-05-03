<?php
namespace DatingVIP\IRC;

interface Logger {
/**
 * Recieves data sent to server
 * @param string line
 */
	public function onSend($line);

/**
* Recieves data received from server
* @param string line
*/
	public function onReceive($line);
}
?>
