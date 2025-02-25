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
use function str_replace;

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

    public function testHeadersShouldBeSentWithoutCrossOrigin(): void
    {
        $container = ContainerFactory::create(__DIR__ . '/config.preloadWithoutCrossOrigin.neon');
        $application = $container->getByType(Application::class);
        $response = $container->getByType(IResponse::class);
        assert($application instanceof Application && $response instanceof HttpResponse);

        $application->run();

        $expected = str_replace("\n", '', <<<XX
</file1.abc.js>; rel="preload"; as="script",
</file2.abc.js>; rel="preload"; as="script",
</file3.abc.js>; rel="preload"; as="script",
</second_build/file1.abc.js>; rel="preload"; as="script",
</second_build/file2.abc.js>; rel="preload"; as="script",
</styles.abc.css>; rel="preload"; as="style",
</styles2.abc.css>; rel="preload"; as="style",
</second_build/styles.abc.css>; rel="preload"; as="style"
XX);

        Assert::same($expected, $response->getHeader('Link'));
    }

    public function testHeadersShouldBeSentWithCrossOrigin(): void
    {
        $container = ContainerFactory::create(__DIR__ . '/config.preloadWithCrossOrigin.neon');
        $application = $container->getByType(Application::class);
        $response = $container->getByType(IResponse::class);
        assert($application instanceof Application && $response instanceof HttpResponse);

        $application->run();

        $expected = str_replace("\n", '', <<<XX
</file1.abc.js>; rel="preload"; as="script"; crossorigin="use-credentials",
</file2.abc.js>; rel="preload"; as="script"; crossorigin="use-credentials",
</file3.abc.js>; rel="preload"; as="script"; crossorigin="use-credentials",
</second_build/file1.abc.js>; rel="preload"; as="script"; crossorigin="use-credentials",
</second_build/file2.abc.js>; rel="preload"; as="script"; crossorigin="use-credentials",
</styles.abc.css>; rel="preload"; as="style"; crossorigin="use-credentials",
</styles2.abc.css>; rel="preload"; as="style"; crossorigin="use-credentials",
</second_build/styles.abc.css>; rel="preload"; as="style"; crossorigin="use-credentials"
XX);

        Assert::same($expected, $response->getHeader('Link'));
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
