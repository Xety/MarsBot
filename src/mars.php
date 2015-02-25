<?php

require dirname(__DIR__) . '/config/bootstrap.php';

use Mars\Network\Server;

$server = (new Server())->startup();
