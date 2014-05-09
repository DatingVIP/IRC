<?php
require_once('vendor/autoload.php');
require_once('xaero/Listen.php');
require_once('xaero/Log.php');

use DatingVIP\IRC\Connection;
use DatingVIP\IRC\Robot;
use xaero\Listen;
use xaero\Log;

set_time_limit(0);
$connection = new Connection('irc.datingvip.com', 9867, true);
$connection->setLogger(new Log());
$robot = new Robot($connection, new Pool(4));
$robot->addListener(new Listen('jhufgty'));
$robot->login('xaero')->join('#test')->loop();
?>