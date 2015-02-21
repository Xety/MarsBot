<?php
$versionFile = file(ROOT . DS .'VERSION.txt');
$config['Mars.version'] = trim(array_pop($versionFile));
return $config;
