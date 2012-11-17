<?php

setlocale(LC_ALL, 'en_GB.UTF8');

define('PROJECT_NAME', 'mangler');

$t = microtime(true);
require('acorn/bootstrap.php');
$t = microtime(true) - $t;

printf('%s: %sms', $_SERVER['PATH_INFO'], number_format($t*1000));
