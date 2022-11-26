<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Tests\Bridge\Nette\Application;

use Tester\Assert;
use Tester\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use SixtyEightPublishers\WebpackEncoreBundle\Bridge\Symfony\Console\Command\WarmupCacheCommand;

require __DIR__ . '/../../../../bootstrap.php';

final class WarmupCacheCommandTest extends TestCase
{
	public function testCommand(): void
	{
		$cacheFile = sys_get_temp_dir() . '/' . uniqid('68publishers:AssetExtensionTest', TRUE) . '/entrypoints-cache.php';

		$command = new WarmupCacheCommand([
			'_default' => __DIR__ . '/entrypoints.default.json',
			'other_build' => __DIR__ . '/entrypoints.otherBuild.json',
		], $cacheFile);

		$application = new Application();

		$application->add($command);

		$command = $application->find('encore:warmup-cache');
		$tester = new CommandTester($command);

		$tester->execute([]);

		Assert::true(file_exists($cacheFile));

		$output = include $cacheFile;
		$expected = include __DIR__ . '/expectedCache.php';

		Assert::same($expected, $output);
	}
}

(new WarmupCacheCommandTest())->run();
