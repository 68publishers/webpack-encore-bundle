<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Tests\Helper;

use Nette;
use Tester;
use SixtyEightPublishers;

final class ContainerFactory
{
	/**
	 * @param string       $name
	 * @param string|array $config
	 *
	 * @return \Nette\DI\Container
	 */
	public static function createContainer(string $name, $config): Nette\DI\Container
	{
		if (!defined('TEMP_PATH')) {
			define('TEMP_PATH', __DIR__ . '/../temp');
		}

		$loader = new Nette\DI\ContainerLoader(TEMP_PATH . '/Nette.Configurator_' . md5($name), TRUE);
		$class = $loader->load(static function (Nette\DI\Compiler $compiler) use ($config): void {
			$compiler->addExtension('latte', new Nette\Bridges\ApplicationDI\LatteExtension(TEMP_PATH . '/latte', TRUE));
			$compiler->addExtension('asset', new SixtyEightPublishers\Asset\DI\AssetExtension());
			$compiler->addExtension('encore', new SixtyEightPublishers\WebpackEncoreBundle\DI\WebpackEncoreBundleExtension());

			$compiler->addConfig([
				'parameters' => [
					'filesDir' => dirname(__DIR__) . '/files',
				],
			]);

			if (is_array($config)) {
				$compiler->addConfig($config);
			} elseif (is_file($config)) {
				$compiler->loadConfig($config);
			} else {
				$compiler->loadConfig(Tester\FileMock::create((string) $config, 'neon'));
			}
		}, $name);

		return new $class();
	}
}
