<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\DI;

use Latte;
use Nette;
use Symfony;
use RuntimeException;
use SixtyEightPublishers;

final class WebpackEncoreBundleExtension extends Nette\DI\CompilerExtension
{
	private const ENTRYPOINTS_FILE_NAME = 'entrypoints.json';

	private const ENTRYPOINT_DEFAULT_NAME = '_default';

	private const CROSSORIGIN_ALLOWED_VALUES = [NULL, 'anonymous', 'use-credentials'];

	public function getConfigSchema(): Nette\Schema\Schema
	{
		return Nette\Schema\Expect::structure([
			'output_path' => Nette\Schema\Expect::string()->nullable(), # The path where Encore is building the assets - i.e. Encore.setOutputPath()
			'builds' => Nette\Schema\Expect::array()->items('string')->assert(function (array $value): bool {
				if (isset($value[self::ENTRYPOINT_DEFAULT_NAME])) {
					throw new Nette\Utils\AssertionException(sprintf('Key "%s" can\'t be used as build name.', self::ENTRYPOINT_DEFAULT_NAME));
				}

				return TRUE;
			}),
			'crossorigin' => Nette\Schema\Expect::string()->nullable()->assert(function (?string $value): bool {
				if (!in_array($value, self::CROSSORIGIN_ALLOWED_VALUES, TRUE)) {
					throw new Nette\Utils\AssertionException(sprintf('Value "%s" for setting "crossorigin" is not allowed', $value));
				}

				return TRUE;
			}), # crossorigin value when Encore.enableIntegrityHashes() is used, can be NULL (default), anonymous or use-credentials
			'cache' => Nette\Schema\Expect::structure([
				'enabled' => Nette\Schema\Expect::bool(FALSE),
				'storage' => Nette\Schema\Expect::string('@' . Nette\Caching\IStorage::class)->dynamic(),
			]),
			'latte' => Nette\Schema\Expect::structure([
				'js_assets_macro_name' => Nette\Schema\Expect::string('encore_js'),
				'css_assets_macro_name' => Nette\Schema\Expect::string('encore_css'),
			]),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$cache = $this->registerCache($this->getConfig()->cache->enabled, $this->getConfig()->cache->storage);
		$lookups = [];

		if (NULL !== $this->getConfig()->output_path) {
			$lookups[] = $this->createEntryPointLookupStatement(self::ENTRYPOINT_DEFAULT_NAME, $this->getConfig()->output_path, $cache);
		}

		foreach ($this->getConfig()->builds as $name => $path) {
			$lookups[] = $this->createEntryPointLookupStatement($name, $path, $cache);
		}

		$builder->addDefinition($this->prefix('entryPointLookupProvider'))
			->setType(SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookupProvider::class)
			->setFactory(SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\EntryPointLookupProvider::class, [
				'lookups' => $lookups,
				'defaultName' => NULL !== $this->getConfig()->output_path ? self::ENTRYPOINT_DEFAULT_NAME : NULL,
			]);

		$defaultAttributes = [];

		if (NULL !== $this->getConfig()->crossorigin) {
			$defaultAttributes['crossorigin'] = $this->getConfig()->crossorigin;
		}

		$builder->addDefinition($this->prefix('tagRenderer'))
			->setType(SixtyEightPublishers\WebpackEncoreBundle\Latte\TagRenderer::class)
			->setAutowired(FALSE)
			->setArguments([
				'defaultAttributes' => $defaultAttributes,
			]);
	}

	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();

		if (NULL === $builder->getByType(Symfony\Component\Asset\Packages::class, FALSE)) {
			throw new RuntimeException('Missing service of type Symfony\Component\Asset\Packages that is required by this package. You can configure and register it manually or you can use package 68publishers/asset (recommended way).');
		}

		$latteFactory = $builder->getDefinition($builder->getByType(Latte\Engine::class) ?? 'nette.latteFactory');

		$latteFactory->getResultDefinition()->addSetup('addProvider', [
			'name' => 'webpackEncoreTagRenderer',
			'value' => $this->prefix('@tagRenderer'),
		]);

		$latteFactory->getResultDefinition()->addSetup('?->onCompile[] = function ($engine) { ?::install(?, ?, $engine->getCompiler()); }', [
			'@self',
			new Nette\PhpGenerator\PhpLiteral(SixtyEightPublishers\WebpackEncoreBundle\Latte\WebpackEncoreMacros::class),
			$this->getConfig()->latte->js_assets_macro_name,
			$this->getConfig()->latte->css_assets_macro_name,
		]);
	}

	/**
	 * @param string                                      $name
	 * @param string                                      $path
	 * @param Nette\DI\Definitions\ServiceDefinition|NULL $cache
	 *
	 * @return Nette\DI\Definitions\Statement
	 */
	private function createEntryPointLookupStatement(string $name, string $path, ?Nette\DI\Definitions\ServiceDefinition $cache): Nette\DI\Definitions\Statement
	{
		return new Nette\DI\Definitions\Statement(SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\EntryPointLookup::class, [
			'buildName' => $name,
			'entryPointJsonPath' => $path . '/' . self::ENTRYPOINTS_FILE_NAME,
			'cache' => $cache,
		]);
	}

	/**
	 * @param bool  $enabled
	 * @param mixed $storage
	 *
	 * @return Nette\DI\Definitions\ServiceDefinition|NULL
	 */
	private function registerCache(bool $enabled, $storage): ?Nette\DI\Definitions\ServiceDefinition
	{
		if (FALSE === $enabled) {
			return NULL;
		}

		$builder = $this->getContainerBuilder();

		if (!is_string($storage) || !Nette\Utils\Strings::startsWith($storage, '@')) {
			$storage = $builder->addDefinition($this->prefix('cache.storage'))
				->setType(Nette\Caching\IStorage::class)
				->setFactory($storage)
				->addTag(Nette\DI\Extensions\InjectExtension::TAG_INJECT);
		}

		return $builder->addDefinition($this->prefix('cache.cache'))
			->setType(Nette\Caching\Cache::class)
			->setArguments([
				'storage' => $storage,
				'namespace' => str_replace('\\', '.', SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookup::class),
			])
			->addTag(Nette\DI\Extensions\InjectExtension::TAG_INJECT);
	}
}
