<?php
require_once('vendor/autoload.php');
require_once('xaero/Xaero.php');
require_once('xaero/Log.php');

use DatingVIP\IRC\Connection;
use xaero\Xaero;
use xaero\Log;

set_time_limit(0);
$connection = new Connection('irc.datingvip.com', 9867, true);
$connection->setLogger(new Log());
$xaero = new Xaero($connection, new Pool(4));
$xaero->login('xaero')->join('#test')->loop();
?>