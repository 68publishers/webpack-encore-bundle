<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Tests\Bridge\Nette\Application;

use SixtyEightPublishers\WebpackEncoreBundle\Bridge\Symfony\Console\Command\WarmupCacheCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tester\Assert;
use Tester\TestCase;

require __DIR__ . '/../../../../bootstrap.php';

final class WarmupCacheCommandTest extends TestCase
{
    public function testCommand(): void
    {
        $cacheFile = sys_get_temp_dir() . '/' . uniqid('68publishers:AssetExtensionTest', true) . '/entrypoints-cache.php';

        $command = new WarmupCacheCommand([
            '_default' => __DIR__ . '/../../../common/public/entrypoints.json',
            'second_build' => __DIR__ . '/../../../common/public/second_build/entrypoints.json',
        ], $cacheFile);

        $application = new Application();

        $application->add($command);

        $command = $application->find('encore:warmup-cache');
        $tester = new CommandTester($command);

        $tester->execute([]);

        Assert::true(file_exists($cacheFile));

        try {
            $output = include $cacheFile;
            $expected = include __DIR__ . '/expectedCache.php';

            Assert::same($expected, $output);
        } finally {
            unlink($cacheFile);
        }
    }
}

(new WarmupCacheCommandTest())->run();
