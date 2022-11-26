<?php

declare(strict_types=1);

use Composer\Autoload\ClassLoader;

$loader = include __DIR__ . '/bootstrap.php';
assert($loader instanceof ClassLoader);

# remove symfony console classes from the composer loader
call_user_func(Closure::bind(static function () use ($loader) {
	$needle = 'Symfony\\Component\\Console';
	$needleLength = strlen($needle);

	foreach (array_keys($loader->classMap) as $className) {
		if (0 === strncmp($className, $needle, $needleLength)) {
			unset($loader->classMap[$className]);
			$loader->missingClasses[$className] = TRUE;
		}
	}
}, NULL, ClassLoader::class));

return $loader;
