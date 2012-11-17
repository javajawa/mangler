<?php

setlocale(LC_ALL, 'en_GB.UTF8');
date_default_timezone_set('UTC');

define('TOP_PATH', realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
define('PROJECT_PATH', TOP_PATH . 'mangler' . DIRECTORY_SEPARATOR);

require('acorn/bootstrap.php');
