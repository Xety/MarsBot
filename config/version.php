<?php
$versionFile = file(ROOT . DS .'VERSION.txt');
$config['Noze.version'] = trim(array_pop($versionFile));
return $config;
