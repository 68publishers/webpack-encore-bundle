<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Bridge\Nette\DI;

use Latte\Engine;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\DI\ContainerBuilder;
use Nette\DI\CompilerExtension;
use Nette\Application\Application;
use Nette\DI\Definitions\Reference;
use Nette\DI\Definitions\Statement;
use Nette\Http\IResponse as HttpResponse;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\DI\Definitions\ServiceDefinition;
use Symfony\Component\Console\Command\Command;
use Nette\Bridges\ApplicationDI\ApplicationExtension;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\EntryPointLookup;
use SixtyEightPublishers\WebpackEncoreBundle\Bridge\Latte\WebpackEncoreLatte2Extension;
use SixtyEightPublishers\WebpackEncoreBundle\Bridge\Latte\WebpackEncoreLatte3Extension;
use SixtyEightPublishers\WebpackEncoreBundle\Bridge\Nette\Application\ApplicationErrorHandler;
use SixtyEightPublishers\WebpackEncoreBundle\Bridge\Nette\Application\ApplicationResponseHandler;
use function assert;
use function array_keys;
use function class_exists;
use function rawurlencode;
use function version_compare;
use function array_key_exists;

final class WebpackEncoreBundleExtension extends CompilerExtension
{
	private const DEFAULT_BUILD_NAME = '_default';
	private const ENTRYPOINTS_FILE_NAME = 'entrypoints.json';

	/** @var array<string> */
	private array $buildNames;

	/**
	 * {@inheritdoc}
	 */
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'output_path' => Expect::string()->nullable(),
			'crossorigin' => Expect::anyOf(FALSE, 'anonymous', 'use-credentials')->default(FALSE),
			'preload' => Expect::bool(FALSE),
			'cache' => Expect::bool(FALSE)
				->assert(static fn (bool $value) => !$value || class_exists(Command::class), 'You can\'t create cached entrypoints without symfony/console.'),
			'strict_mode' => Expect::bool(TRUE),
			'builds' => Expect::arrayOf('string', 'string')
				->assert(static fn (array $builds): bool => !array_key_exists(self::DEFAULT_BUILD_NAME, $builds), 'Key \'_default\' can\'t be used as build name.'),
			'script_attributes' => Expect::arrayOf(
				Expect::anyOf(Expect::string(), TRUE),
				'string'
			),
			'link_attributes' => Expect::arrayOf(
				Expect::anyOf(Expect::string(), TRUE),
				'string'
			),
		])->assert(static fn (object $config): bool => !(!isset($config->output_path) && empty($config->builds)), 'No build is defined.')
			->castTo(WebpackEncoreConfig::class);
	}

	/**
	 * {@inheritdoc}
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig();
		assert($config instanceof WebpackEncoreConfig);

		$cacheFilename = $builder->parameters['tempDir'] . '/cache/webpack_encore.cache.php';
		$defaultAttributes = FALSE !== $config->crossorigin ? ['crossorigin' => $config->crossorigin] : [];

		$this->loadDefinitionsFromConfig($this->loadFromFile(__DIR__ . '/services.neon'));

		$this->getServiceDefinition('cache.default')
			->setArgument('file', $cacheFilename);

		$this->getServiceDefinition('tag_renderer')
			->setArgument('defaultAttributes', $defaultAttributes)
			->setArgument('defaultScriptAttributes', $config->script_attributes)
			->setArgument('defaultLinkAttributes', $config->link_attributes);

		$entryPointLookupCollection = $this->getServiceDefinition('entrypoint_lookup_collection.default');
		$entryPointLookups = [];
		$cacheKeys = [];

		if (NULL !== $config->output_path) {
			$path = $config->output_path . '/' . self::ENTRYPOINTS_FILE_NAME;
			$entryPointLookups['_default'] = $this->createEntrypoint('_default', $config->output_path, $config->cache, $config->strict_mode);
			$cacheKeys['_default'] = $path;

			$entryPointLookupCollection->setArgument('defaultBuildName', '_default');
		}

		foreach ($config->builds as $name => $path) {
			$path .= '/' . self::ENTRYPOINTS_FILE_NAME;
			$entryPointLookups[$name] = $this->createEntrypoint($name, $path, $config->cache, $config->strict_mode);
			$cacheKeys[rawurlencode($name)] = $path;
		}

		$this->buildNames = array_keys($entryPointLookups);

		$entryPointLookupCollection->setArgument('entryPointLookups', $entryPointLookups);

		if (class_exists(Command::class)) {
			$this->loadDefinitionsFromConfig($this->loadFromFile(__DIR__ . '/console.neon'));

			$this->getServiceDefinition('console.command.warmup_cache')
				->setArgument('cacheKeys', $cacheKeys)
				->setArgument('cacheFile', $cacheFilename);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function beforeCompile(): void
	{
		if ($this->compiler->getExtensions(ApplicationExtension::class)) {
			$this->beforeCompileApplicationHandlers($this->buildNames);
		}

		$this->beforeCompileLatte();
	}

	/**
	 * @param array<string> $buildNames
	 */
	private function beforeCompileApplicationHandlers(array $buildNames): void
	{
		$applicationDefinition = $this->getContainerBuilder()->getDefinitionByType(Application::class);
		assert($applicationDefinition instanceof ServiceDefinition);

		$applicationDefinition->addSetup('?::register(?, ?, ?)', [
			ContainerBuilder::literal(ApplicationErrorHandler::class),
			new Reference('self'),
			new Reference('entrypoint_lookup_collection'),
			$buildNames,
		]);

		$applicationDefinition->addSetup('?::register(?, ?, ?, ?)', [
			ContainerBuilder::literal(ApplicationResponseHandler::class),
			new Reference(HttpResponse::class),
			new Reference('tag_renderer'),
			new Reference('entrypoint_lookup_collection'),
			$buildNames,
		]);
	}

	private function beforeCompileLatte(): void
	{
		$builder = $this->getContainerBuilder();
		$latteFactory = $builder->getDefinition($builder->getByType(Engine::class) ?? 'nette.latteFactory');
		assert($latteFactory instanceof FactoryDefinition);
		$resultDefinition = $latteFactory->getResultDefinition();

		if (version_compare(Engine::VERSION, '3', '<')) {
			$resultDefinition->addSetup('?::extend(?, ?, ?)', [
				ContainerBuilder::literal(WebpackEncoreLatte2Extension::class),
				new Reference('self'),
				new Reference('entrypoint_lookup_collection'),
				new Reference('tag_renderer'),
			]);

			return;
		}

		$resultDefinition->addSetup('addExtension', [
			new Statement(WebpackEncoreLatte3Extension::class, [
				new Reference('entrypoint_lookup_collection'),
				new Reference('tag_renderer'),
			]),
		]);
	}

	private function getServiceDefinition(string $name): ServiceDefinition
	{
		$definition = $this->getContainerBuilder()->getDefinition($this->prefix($name));
		assert($definition instanceof ServiceDefinition);

		return $definition;
	}

	private function createEntrypoint(string $name, string $path, bool $cacheEnabled, bool $strictMode): Reference
	{
		$serviceName = $this->prefix('entrypoint_lookup.' . $name);

		$this->getContainerBuilder()
			->addDefinition($serviceName)
			->setAutowired(FALSE)
			->setFactory(new Statement(EntryPointLookup::class, [
				'entrypointJsonPath' => $path,
				'cacheItemPool' => $cacheEnabled ? new Reference($this->prefix('cache')) : NULL,
				'cacheKey' => $name,
				'strictMode' => $strictMode,
			]));

		return new Reference($serviceName);
	}
}
