<?php
namespace DatingVIP\IRC;

/**
* @NOTE
* The management interface is executed synchronously, regardless of listeners, such that it may
* add and remove listeners as it see's fit during execution
* This means it is partially disconnected from the threading model of the rest of the package
*/

/**
* Manager interface for Robot
*/
interface Manager {
	
/**
* Executed when entering main Robot loop
* @param Robot robot
**/
	public function onStartup(Robot $robot);

/**
* Executed when a JOIN message is recieved
* @param Message message
**/
	public function onJoin  (Robot $robot, Message $message);
	
/**
* Executed when a NICK message is recieved
* @param Message message
**/
	public function onNick  (Robot $robot, Message $message);
	
/**
* Executed when a PART message is recieved
* @param Message message

**/
	public function onPart  (Robot $robot, Message $message);
	
/**
* Executed when a PRIVMSG message is recieved
* @param Message message
**/
	public function onPriv  (Robot $robot, Message $message);

/**
* Executed when leaving main loop
* @param Robot robot
**/
	public function onShutdown(Robot $robot);
}
?>
