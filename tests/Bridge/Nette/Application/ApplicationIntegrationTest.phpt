<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Tests\Bridge\Nette\Application;

use Nette\Application\Application;
use Nette\Http\IResponse;
use SixtyEightPublishers\WebpackEncoreBundle\Tests\Bridge\Nette\DI\ContainerFactory;
use Tester\Assert;
use Tester\CodeCoverage\Collector;
use Tester\TestCase;
use function assert;

require __DIR__ . '/../../../bootstrap.php';

final class ApplicationIntegrationTest extends TestCase
{
    public function testHeadersShouldNotBeSentWithPreloadOptionDisabled(): void
    {
        $container = ContainerFactory::create(__DIR__ . '/config.base.neon');
        $application = $container->getByType(Application::class);
        $response = $container->getByType(IResponse::class);
        assert($application instanceof Application && $response instanceof HttpResponse);

        $application->run();

        Assert::null($response->getHeader('Link'));
    }

    public function testHeadersShouldNotBeSentWithDeprecatedPreloadOptionEnabled(): void
    {
        $container = null;

        Assert::error(
            static function () use (&$container): void {
                $container = ContainerFactory::create(__DIR__ . '/config.preloadWithCrossOrigin.neon');
            },
            E_USER_DEPRECATED,
            'The option %a%preload%a% is deprecated since 68publishers/webpack-encore-bundle 3.4.0 and has no effect, the Link preload header is no longer sent. The option will be removed in 4.0, remove it from your configuration.',
        );

        $application = $container->getByType(Application::class);
        $response = $container->getByType(IResponse::class);
        assert($application instanceof Application && $response instanceof HttpResponse);

        $application->run();

        Assert::null($response->getHeader('Link'));
    }

    protected function tearDown(): void
    {
        # save manually partial code coverage to free memory
        if (Collector::isStarted()) {
            Collector::save();
        }
    }
}

(new ApplicationIntegrationTest())->run();
