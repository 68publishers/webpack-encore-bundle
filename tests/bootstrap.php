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

return $loader;
