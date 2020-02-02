<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Tests\Cases\EntryPoint;

use Nette;
use Tester;
use SixtyEightPublishers;

require __DIR__ . '/../../bootstrap.php';

class WebpackEncoreBundleExtensionIntegrationTest extends Tester\TestCase
{
	/**
	 * @return void
	 */
	public function testRegisteredEntryPointLookupProviderService(): void
	{
		$container = SixtyEightPublishers\WebpackEncoreBundle\Tests\Helper\ContainerFactory::createContainer(
			__METHOD__,
			__DIR__ . '/../../files/encore.neon'
		);

		Tester\Assert::noError(static function () use ($container) {
			$container->getService('encore.entryPointLookupProvider');
		});

		/** @var \SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookupProvider $entryPointLookupProvider */
		$entryPointLookupProvider = $container->getService('encore.entryPointLookupProvider');

		Tester\Assert::type(SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookupProvider::class, $entryPointLookupProvider);

		Tester\Assert::noError(static function () use ($entryPointLookupProvider) {
			$entryPointLookupProvider->getEntryPointLookup();
			$entryPointLookupProvider->getEntryPointLookup('different_build');
		});
	}

	/**
	 * @return void
	 */
	public function testRegisteredCacheService(): void
	{
		$container = SixtyEightPublishers\WebpackEncoreBundle\Tests\Helper\ContainerFactory::createContainer(
			__METHOD__,
			__DIR__ . '/../../files/encore_cache_enabled.neon'
		);

		Tester\Assert::noError(static function () use ($container) {
			$container->getService('encore.cache.cache');
		});

		/** @var \Nette\Caching\Cache $cache */
		$cache = $container->getService('encore.cache.cache');

		Tester\Assert::type(Nette\Caching\Cache::class, $cache);
	}
}

(new WebpackEncoreBundleExtensionIntegrationTest())->run();
