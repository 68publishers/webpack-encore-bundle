<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Tests\Bridge\Nette\DI;

use Tester\Assert;
use Tester\TestCase;
use Nette\DI\Container;
use Nette\DI\MissingServiceException;
use Nette\DI\InvalidConfigurationException;
use function assert;

require __DIR__ . '/../../../bootstrap.withoutSymfonyConsole.php';

final class WebpackEncoreBundleExtensionWithoutConsoleTest extends TestCase
{
	public function testExceptionShouldBeThrownIfCacheOptionEnabledWithoutSymfonyConsoleInstalled(): void
	{
		Assert::exception(
			static function () {
				ContainerFactory::create(__DIR__ . '/config.cacheEnabled.neon');
			},
			InvalidConfigurationException::class,
			"Failed assertion 'You can't create cached entrypoints without symfony/console.' for item 'encore\u{a0}›\u{a0}cache' with value true."
		);
	}

	public function testExtensionShouldBeIntegratedWithoutSymfonyConsole(): void
	{
		$container = NULL;

		Assert::noError(static function () use (&$container) {
			$container = ContainerFactory::create(__DIR__ . '/config.neon');
		});

		assert($container instanceof Container);

		Assert::exception(
			static function () use ($container) {
				$container->getService('encore.console.command.warmup_cache');
			},
			MissingServiceException::class,
			"Service 'encore.console.command.warmup_cache' not found."
		);
	}
}

(new WebpackEncoreBundleExtensionWithoutConsoleTest())->run();
