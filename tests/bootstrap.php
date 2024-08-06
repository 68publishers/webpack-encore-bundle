<?php

declare(strict_types=1);

use Tester\Environment;

$loader = @include __DIR__ . '/../vendor/autoload.php';

if (!$loader) {
	echo 'Install Nette Tester using `composer install`';
	exit(1);
}

Environment::setup();
Environment::bypassFinals();
date_default_timezone_set('Europe/Prague');

if (PHP_VERSION_ID < 80000) {
	error_reporting(~E_USER_DEPRECATED);
}

if (PHP_VERSION_ID >= 80200) {
	error_reporting(~E_DEPRECATED);
}

return $loader;
