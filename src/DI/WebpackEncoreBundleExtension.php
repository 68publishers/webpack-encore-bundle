<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\DI;

use Latte;
use Nette;
use Symfony;
use SixtyEightPublishers;

final class WebpackEncoreBundleExtension extends Nette\DI\CompilerExtension
{
	private const ENTRYPOINTS_FILE_NAME = 'entrypoints.json';

	private const ENTRYPOINT_DEFAULT_NAME = '_default';

	private const CROSSORIGIN_ALLOWED_VALUES = [NULL, 'anonymous', 'use-credentials'];

	/** @var array  */
	private $defaults = [
		'output_path' => NULL, # The path where Encore is building the assets - i.e. Encore.setOutputPath()
		'builds' => [],
		'crossorigin' => NULL, # crossorigin value when Encore.enableIntegrityHashes() is used, can be NULL (default), anonymous or use-credentials
		'cache' => [
			'enabled' => FALSE,
			'storage' => '@' . Nette\Caching\IStorage::class,
		],
		'latte' => [
			'js_assets_macro_name' => 'encore_js',
			'css_assets_macro_name' => 'encore_css',
		],
	];

	/**
	 * {@inheritdoc}
	 *
	 * @throws \Nette\Utils\AssertionException
	 */
	public function loadConfiguration(): void
	{
		$config = $this->getValidConfig();
		$builder = $this->getContainerBuilder();
		$cache = $this->registerCache($config['cache']['enabled'], $config['cache']['storage']);
		$lookups = [];

		if (NULL !== $config['output_path']) {
			$lookups[] = $this->createEntryPointLookupStatement(self::ENTRYPOINT_DEFAULT_NAME, $config['output_path'], $cache);
		}

		foreach ($config['builds'] as $name => $path) {
			$lookups[] = $this->createEntryPointLookupStatement($name, $path, $cache);
		}

		$builder->addDefinition($this->prefix('entryPointLookupProvider'))
			->setType(SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookupProvider::class)
			->setFactory(SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\EntryPointLookupProvider::class, [
				'lookups' => $lookups,
				'defaultName' => NULL !== $config['output_path'] ? self::ENTRYPOINT_DEFAULT_NAME : NULL,
			]);

		$defaultAttributes = [];

		if (NULL !== $config['crossorigin']) {
			$defaultAttributes['crossorigin'] = $config['crossorigin'];
		}

		$builder->addDefinition($this->prefix('tagRenderer'))
			->setType(SixtyEightPublishers\WebpackEncoreBundle\Latte\TagRenderer::class)
			->setAutowired(FALSE)
			->setArguments([
				'defaultAttributes' => $defaultAttributes,
			]);
	}

	/**
	 * {@inheritdoc}
	 *
	 * @throws \Nette\Utils\AssertionException
	 */
	public function beforeCompile(): void
	{
		$config = $this->getValidConfig();
		$builder = $this->getContainerBuilder();

		if (NULL === $builder->getByType(Symfony\Component\Asset\Packages::class, FALSE)) {
			throw new \RuntimeException('Missing service of type Symfony\Component\Asset\Packages that is required by this package. You can configure and register it manually or you can use package 68publishers/asset (recommended way).');
		}

		$latteFactory = $builder->getDefinition($builder->getByType(Latte\Engine::class) ?? 'nette.latteFactory');

		$latteFactory->addSetup('addProvider', [
			'name' => 'webpackEncoreTagRenderer',
			'value' => $this->prefix('@tagRenderer'),
		]);

		$latteFactory->addSetup('?->onCompile[] = function ($engine) { ?::install(?, ?, $engine->getCompiler()); }', [
			'@self',
			new Nette\PhpGenerator\PhpLiteral(SixtyEightPublishers\WebpackEncoreBundle\Latte\WebpackEncoreMacros::class),
			$config['latte']['js_assets_macro_name'],
			$config['latte']['css_assets_macro_name'],
		]);
	}

	/**
	 * @param string                           $name
	 * @param string                           $path
	 * @param \Nette\DI\ServiceDefinition|NULL $cache
	 *
	 * @return \Nette\DI\Statement
	 */
	private function createEntryPointLookupStatement(string $name, string $path, ?Nette\DI\ServiceDefinition $cache): Nette\DI\Statement
	{
		return new Nette\DI\Statement(SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\EntryPointLookup::class, [
			'buildName' => $name,
			'entryPointJsonPath' => $path . '/' . self::ENTRYPOINTS_FILE_NAME,
			'cache' => $cache,
		]);
	}

	/**
	 * @param bool  $enabled
	 * @param mixed $storage
	 *
	 * @return \Nette\DI\ServiceDefinition|NULL
	 */
	private function registerCache(bool $enabled, $storage): ?Nette\DI\ServiceDefinition
	{
		if (FALSE === $enabled) {
			return NULL;
		}

		$builder = $this->getContainerBuilder();

		if (!is_string($storage) || !Nette\Utils\Strings::startsWith($storage, '@')) {
			$storage = $builder->addDefinition($this->prefix('cache.storage'))
				->setType(Nette\Caching\IStorage::class)
				->setFactory($storage)
				->setInject(FALSE);
		}

		return $builder->addDefinition($this->prefix('cache.cache'))
			->setType(Nette\Caching\Cache::class)
			->setArguments([
				'storage' => $storage,
				'namespace' => str_replace('\\', '.', SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookup::class),
			])
			->setInject(FALSE);
	}

	/**
	 * @return array
	 * @throws \Nette\Utils\AssertionException
	 */
	private function getValidConfig(): array
	{
		/** @noinspection PhpInternalEntityUsedInspection */
		$config = $this->validateConfig(Nette\DI\Helpers::expand($this->defaults, $this->getContainerBuilder()->parameters));

		Nette\Utils\Validators::assertField($config, 'output_path', 'null|string');
		Nette\Utils\Validators::assertField($config['cache'], 'enabled', 'bool');
		Nette\Utils\Validators::assertField($config['cache'], 'storage', 'string|' . Nette\DI\Statement::class);
		Nette\Utils\Validators::assertField($config, 'builds', 'string[]');
		Nette\Utils\Validators::assertField($config['latte'], 'js_assets_macro_name', 'string');
		Nette\Utils\Validators::assertField($config['latte'], 'css_assets_macro_name', 'string');
		Nette\Utils\Validators::assertField($config, 'crossorigin', 'null|string');

		if (isset($config['builds'][self::ENTRYPOINT_DEFAULT_NAME])) {
			throw new Nette\Utils\AssertionException(sprintf('Key "%s" can\'t be used as build name.', self::ENTRYPOINT_DEFAULT_NAME));
		}

		if (!in_array($config['crossorigin'], self::CROSSORIGIN_ALLOWED_VALUES, TRUE)) {
			throw new Nette\Utils\AssertionException(sprintf('Value "%s" for setting "crossorigin" is not allowed', $config['crossorigin']));
		}

		return $config;
	}
}
