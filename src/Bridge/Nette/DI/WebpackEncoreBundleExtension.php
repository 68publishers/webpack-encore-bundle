<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Bridge\Nette\DI;

use Latte\Engine;
use Nette\Application\Application;
use Nette\Bridges\ApplicationDI\ApplicationExtension;
use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\DI\Definitions\Reference;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\Http\IResponse as HttpResponse;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use RuntimeException;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\EntryPointLookup;
use SixtyEightPublishers\WebpackEncoreBundle\Bridge\Latte\WebpackEncoreLatteExtension;
use SixtyEightPublishers\WebpackEncoreBundle\Bridge\Nette\Application\ApplicationErrorHandler;
use SixtyEightPublishers\WebpackEncoreBundle\Bridge\Nette\Application\ApplicationResponseHandler;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Console\Command\Command;
use function array_key_exists;
use function array_keys;
use function assert;
use function class_exists;
use function rawurlencode;

final class WebpackEncoreBundleExtension extends CompilerExtension
{
    private const string DefaultBuildName = '_default';
    private const string EntrypointsFilename = 'entrypoints.json';

    /** @var list<string> */
    private array $buildNames;

    public function getConfigSchema(): Schema
    {
        return Expect::structure([
            'output_path' => Expect::string()->nullable(),
            'crossorigin' => Expect::anyOf(false, 'anonymous', 'use-credentials')->default(false),
            'preload' => Expect::bool(false),
            'cache' => Expect::bool(false)
                ->assert(static fn (bool $value) => !$value || class_exists(Command::class), 'You can\'t create cached entrypoints without symfony/console.'),
            'strict_mode' => Expect::bool(true),
            'builds' => Expect::arrayOf('string', 'string')
                ->assert(static fn (array $builds): bool => !array_key_exists(self::DefaultBuildName, $builds), 'Key \'_default\' can\'t be used as build name.'),
            'script_attributes' => Expect::arrayOf(
                Expect::anyOf(Expect::string(), true),
                'string',
            ),
            'link_attributes' => Expect::arrayOf(
                Expect::anyOf(Expect::string(), true),
                'string',
            ),
        ])->castTo(WebpackEncoreConfig::class)
            ->assert(static fn (object $config): bool => !(!isset($config->output_path) && empty($config->builds)), 'No build is defined.');
    }

    public function loadConfiguration(): void
    {
        $builder = $this->getContainerBuilder();
        $config = $this->getConfig();
        assert($config instanceof WebpackEncoreConfig);

        $cacheFilename = $builder->parameters['tempDir'] . '/cache/webpack_encore.cache.php';
        $defaultAttributes = false !== $config->crossorigin ? ['crossorigin' => $config->crossorigin] : [];

        $this->loadDefinitionsFromConfig($this->loadFromFile(__DIR__ . '/services.neon')['services']);

        $this->getServiceDefinition('cache.default')
            ->setArgument('file', $cacheFilename);

        $this->getServiceDefinition('tag_renderer')
            ->setArgument('defaultAttributes', $defaultAttributes)
            ->setArgument('defaultScriptAttributes', $config->script_attributes)
            ->setArgument('defaultLinkAttributes', $config->link_attributes);

        $entryPointLookupCollection = $this->getServiceDefinition('entrypoint_lookup_collection.default');
        $entryPointLookups = [];
        $cacheKeys = [];

        if (null !== $config->output_path) {
            $path = $config->output_path . '/' . self::EntrypointsFilename;
            $entryPointLookups['_default'] = $this->createEntrypoint('_default', $path, $config->cache, $config->strict_mode);
            $cacheKeys['_default'] = $path;

            $entryPointLookupCollection->setArgument('defaultBuildName', '_default');
        }

        foreach ($config->builds as $name => $path) {
            $path .= '/' . self::EntrypointsFilename;
            $entryPointLookups[$name] = $this->createEntrypoint($name, $path, $config->cache, $config->strict_mode);
            $cacheKeys[rawurlencode($name)] = $path;
        }

        $this->buildNames = array_keys($entryPointLookups);

        $entryPointLookupCollection->setArgument('entryPointLookups', $entryPointLookups);

        if (class_exists(Command::class)) {
            $this->loadDefinitionsFromConfig($this->loadFromFile(__DIR__ . '/console.neon')['services']);

            $this->getServiceDefinition('console.command.warmup_cache')
                ->setArgument('cacheKeys', $cacheKeys)
                ->setArgument('cacheFile', $cacheFilename);
        }
    }

    public function beforeCompile(): void
    {
        if (null === $this->getContainerBuilder()->getByType(Packages::class, false)) {
            throw new RuntimeException('Symfony Asset component is not integrated with your application. Please use 68publishers/asset or another integration solution.');
        }

        $config = $this->getConfig();
        assert($config instanceof WebpackEncoreConfig);

        if ($this->compiler->getExtensions(ApplicationExtension::class)) {
            $this->beforeCompileApplicationHandlers($this->buildNames, $config->preload);
        }

        $this->beforeCompileLatte();
    }

    /**
     * @param list<string> $buildNames
     */
    private function beforeCompileApplicationHandlers(array $buildNames, bool $preload): void
    {
        $applicationDefinition = $this->getContainerBuilder()->getDefinitionByType(Application::class);
        assert($applicationDefinition instanceof ServiceDefinition);

        $applicationDefinition->addSetup('?::register(?, ?, ?)', [
            ContainerBuilder::literal(ApplicationErrorHandler::class),
            new Reference('self'),
            new Reference($this->prefix('entrypoint_lookup_collection')),
            $buildNames,
        ]);

        if ($preload) {
            $applicationDefinition->addSetup('?::register(?, ?, ?, ?, ?)', [
                ContainerBuilder::literal(ApplicationResponseHandler::class),
                new Reference('self'),
                new Reference(HttpResponse::class),
                new Reference($this->prefix('tag_renderer')),
                new Reference($this->prefix('entrypoint_lookup_collection')),
                $buildNames,
            ]);
        }
    }

    private function beforeCompileLatte(): void
    {
        $builder = $this->getContainerBuilder();
        $latteFactory = $builder->getDefinition($builder->getByType(Engine::class) ?? 'nette.latteFactory');
        assert($latteFactory instanceof FactoryDefinition);
        $resultDefinition = $latteFactory->getResultDefinition();

        $resultDefinition->addSetup('addExtension', [
            new Statement(WebpackEncoreLatteExtension::class, [
                new Reference($this->prefix('entrypoint_lookup_collection')),
                new Reference($this->prefix('tag_renderer')),
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
            ->setAutowired(false)
            ->setFactory(new Statement(EntryPointLookup::class, [
                'entrypointJsonPath' => $path,
                'cacheItemPool' => $cacheEnabled ? new Reference($this->prefix('cache')) : null,
                'cacheKey' => $name,
                'strictMode' => $strictMode,
            ]));

        return new Reference($serviceName);
    }
}
