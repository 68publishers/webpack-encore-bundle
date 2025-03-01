<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Tests\Bridge\Nette\DI;

use Closure;
use Nette\Application\Application;
use Nette\DI\InvalidConfigurationException;
use Psr\Cache\CacheItemPoolInterface;
use RuntimeException;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\EntryPointLookup;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\EntryPointLookupCollection;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\EntryPointLookupCollectionInterface;
use SixtyEightPublishers\WebpackEncoreBundle\Bridge\Nette\Application\ApplicationErrorHandler;
use SixtyEightPublishers\WebpackEncoreBundle\Bridge\Nette\Application\ApplicationResponseHandler;
use SixtyEightPublishers\WebpackEncoreBundle\Bridge\Symfony\Console\Command\WarmupCacheCommand;
use Tester\Assert;
use Tester\CodeCoverage\Collector;
use Tester\TestCase;
use function array_filter;
use function array_keys;
use function array_values;
use function assert;
use function call_user_func;
use function realpath;

require __DIR__ . '/../../../bootstrap.php';

final class WebpackEncoreBundleExtensionTest extends TestCase
{
    public function testExceptionShouldBeThrownIfNoBuildsDefined(): void
    {
        Assert::exception(
            static function () {
                ContainerFactory::create(__DIR__ . '/config.error.noBuildsDefined.neon');
            },
            InvalidConfigurationException::class,
            "Failed assertion 'No build is defined.' for item 'encore' with value object SixtyEightPublishers\\WebpackEncoreBundle\\Bridge\\Nette\\DI\\WebpackEncoreConfig.",
        );
    }

    public function testExceptionShouldBeThrownIfSymfonyAssetComponentNotIntegrated(): void
    {
        Assert::exception(
            static function () {
                ContainerFactory::create(__DIR__ . '/config.error.missingSymfonyAssetPackages.neon');
            },
            RuntimeException::class,
            "Symfony Asset component is not integrated with your application. Please use 68publishers/asset or another integration solution.",
        );
    }

    public function testExceptionShouldBeThrownIfBuildWithDefaultNameDefined(): void
    {
        Assert::exception(
            static function () {
                ContainerFactory::create(__DIR__ . '/config.error.defaultKeyAsBuildName.neon');
            },
            InvalidConfigurationException::class,
            "Failed assertion 'Key '_default' can't be used as build name.' for item 'encore\u{a0}›\u{a0}builds' with value array.",
        );
    }

    public function testEntryPointsShouldBeConfigured(): void
    {
        $container = ContainerFactory::create(__DIR__ . '/config.neon');
        $entryPointLookupCollection = $container->getByType(EntryPointLookupCollectionInterface::class, true);
        assert($entryPointLookupCollection instanceof EntryPointLookupCollection);

        Assert::type(EntryPointLookupCollection::class, $entryPointLookupCollection);

        $this->assertEntryPointCollection($entryPointLookupCollection, [
            '_default' => [__DIR__ . '/../../common/public/entrypoints.json', false, '_default', true],
            'second' => [__DIR__ . '/../../common/public/second_build/entrypoints.json', false, 'second', true],
        ]);
    }

    public function testEntryPointsShouldBeConfiguredWithCacheEnabled(): void
    {
        $container = ContainerFactory::create(__DIR__ . '/config.cacheEnabled.neon');
        $entryPointLookupCollection = $container->getByType(EntryPointLookupCollectionInterface::class, true);
        assert($entryPointLookupCollection instanceof EntryPointLookupCollection);

        Assert::type(EntryPointLookupCollection::class, $entryPointLookupCollection);

        $this->assertEntryPointCollection($entryPointLookupCollection, [
            '_default' => [__DIR__ . '/../../common/public/entrypoints.json', true, '_default', true],
            'second' => [__DIR__ . '/../../common/public/second_build/entrypoints.json', true, 'second', true],
        ]);
    }

    public function testEntryPointsShouldBeConfiguredWithStrictModeDisabled(): void
    {
        $container = ContainerFactory::create(__DIR__ . '/config.strictModeDisabled.neon');
        $entryPointLookupCollection = $container->getByType(EntryPointLookupCollectionInterface::class, true);
        assert($entryPointLookupCollection instanceof EntryPointLookupCollection);

        Assert::type(EntryPointLookupCollection::class, $entryPointLookupCollection);

        $this->assertEntryPointCollection($entryPointLookupCollection, [
            '_default' => [__DIR__ . '/../../common/public/entrypoints.json', false, '_default', false],
            'second' => [__DIR__ . '/../../common/public/second_build/entrypoints.json', false, 'second', false],
        ]);
    }

    public function testApplicationErrorHandlerShouldBeRegistered(): void
    {
        $container = ContainerFactory::create(__DIR__ . '/config.neon');
        $application = $container->getByType(Application::class, true);
        assert($application instanceof Application);

        $handlers = array_values(array_filter($application->onError ?? [], static fn ($callback): bool => $callback instanceof ApplicationErrorHandler));

        Assert::count(1, $handlers);
        Assert::type(ApplicationErrorHandler::class, $handlers[0]);

        $handler = $handlers[0];
        assert($handler instanceof ApplicationErrorHandler);

        call_user_func(Closure::bind(static function () use ($handler) {
            Assert::same(['_default', 'second'], $handler->buildNames);
        }, null, ApplicationErrorHandler::class));
    }

    public function testApplicationResponseHandlerShouldNotBeRegisteredIfPreloadDisabled(): void
    {
        $container = ContainerFactory::create(__DIR__ . '/config.neon');
        $application = $container->getByType(Application::class, true);
        assert($application instanceof Application);

        $handlers = array_values(array_filter($application->onResponse ?? [], static fn ($callback): bool => $callback instanceof ApplicationResponseHandler));

        Assert::count(0, $handlers);
    }

    public function testApplicationResponseHandlerShouldBeRegisteredIfPreloadEnabled(): void
    {
        $container = ContainerFactory::create(__DIR__ . '/config.preloadEnabled.neon');
        $application = $container->getByType(Application::class, true);
        assert($application instanceof Application);

        $handlers = array_values(array_filter($application->onResponse ?? [], static fn ($callback): bool => $callback instanceof ApplicationResponseHandler));

        Assert::count(1, $handlers);
        Assert::type(ApplicationResponseHandler::class, $handlers[0]);

        $handler = $handlers[0];
        assert($handler instanceof ApplicationResponseHandler);

        call_user_func(Closure::bind(static function () use ($handler) {
            Assert::same(['_default', 'second'], $handler->buildNames);
        }, null, ApplicationResponseHandler::class));
    }

    public function testConsoleCommandsShouldBeRegistered(): void
    {
        $container = ContainerFactory::create(__DIR__ . '/config.neon');
        $command = $container->getService('encore.console.command.warmup_cache');
        assert($command instanceof WarmupCacheCommand);

        Assert::type(WarmupCacheCommand::class, $command);
    }

    private function assertEntryPointCollection(EntryPointLookupCollection $entryPointLookupCollection, array $expectedEntryPointsData, ?string $defaultBuildName = '_default'): void
    {
        $entryPointLookupChecker = fn (EntryPointLookup $entryPointLookup, array $expectedEntryPointsData) => $this->assertEntryPointLookup($entryPointLookup, ...$expectedEntryPointsData);

        call_user_func(Closure::bind(static function () use ($entryPointLookupCollection, $entryPointLookupChecker, $defaultBuildName, $expectedEntryPointsData) {
            $lookups = $entryPointLookupCollection->entryPointLookups;

            Assert::same($defaultBuildName, $entryPointLookupCollection->defaultBuildName);
            Assert::same(array_keys($expectedEntryPointsData), array_keys($lookups));

            foreach ($expectedEntryPointsData as $buildName => $expected) {
                Assert::type(EntryPointLookup::class, $lookups[$buildName]);
                $entryPointLookupChecker($lookups[$buildName], $expected);
            }
        }, null, EntryPointLookupCollection::class));
    }

    private function assertEntryPointLookup(EntryPointLookup $entryPointLookup, string $entrypointJsonPath, bool $cacheEnabled, ?string $cacheKey, bool $strictMode): void
    {
        call_user_func(Closure::bind(static function () use ($entryPointLookup, $entrypointJsonPath, $cacheEnabled, $cacheKey, $strictMode) {
            Assert::same(realpath($entrypointJsonPath), realpath($entryPointLookup->entrypointJsonPath));

            if ($cacheEnabled) {
                Assert::type(CacheItemPoolInterface::class, $entryPointLookup->cacheItemPool);
            } else {
                Assert::null($entryPointLookup->cacheItemPool);
            }

            Assert::same($cacheKey, $entryPointLookup->cacheKey);
            Assert::same($strictMode, $entryPointLookup->strictMode);
        }, null, EntryPointLookup::class));
    }

    protected function tearDown(): void
    {
        # save manually partial code coverage to free memory
        if (Collector::isStarted()) {
            Collector::save();
        }
    }
}

(new WebpackEncoreBundleExtensionTest())->run();
