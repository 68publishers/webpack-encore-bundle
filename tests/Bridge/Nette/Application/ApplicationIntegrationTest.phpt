<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Tests\Bridge\Nette\Application;

use Nette\Application\Application;
use Nette\Http\IResponse;
use SixtyEightPublishers\WebpackEncoreBundle\Tests\Bridge\Nette\DI\ContainerFactory;
use Tester\Assert;
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

    /**
     * @return list<array{string}>
     */
    public function getDeprecatedPreloadConfigs(): array
    {
        return [
            [__DIR__ . '/config.preloadWithCrossOrigin.neon'],
            [__DIR__ . '/config.preloadDisabled.neon'],
        ];
    }

    /**
     * @dataProvider getDeprecatedPreloadConfigs
     */
    public function testHeadersShouldNotBeSentWithDeprecatedPreloadOption(string $config): void
    {
        $container = null;

        Assert::error(
            static function () use (&$container, $config): void {
                $container = ContainerFactory::create($config);
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
}

(new ApplicationIntegrationTest())->run();
