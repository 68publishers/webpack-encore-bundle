<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Tests\Bridge\Nette\DI;

use Tester\Helpers;
use Nette\DI\Container;
use Nette\Bootstrap\Configurator;
use function uniqid;
use function dirname;
use function debug_backtrace;
use function sys_get_temp_dir;

final class ContainerFactory
{
	private function __construct()
	{
	}

	/**
	 * @param string|array<string> $configFiles
	 */
	public static function create($configFiles): Container
	{
		$tempDir = sys_get_temp_dir() . '/' . uniqid('68publishers:WebpackEncoreBundle', TRUE);
		$backtrace = debug_backtrace();

		Helpers::purge($tempDir);

		$configurator = new Configurator();
		$configurator->setTempDirectory($tempDir);
		$configurator->setDebugMode(TRUE);

		$configurator->addParameters([
			'cwd' => dirname($backtrace[0]['file']),
			'commonDir' => __DIR__ . '/../../common',
		]);

		foreach ((array) $configFiles as $configFile) {
			$configurator->addConfig($configFile);
		}

		return $configurator->createContainer();
	}
}
