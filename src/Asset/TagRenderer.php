<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Asset;

use Nette\Utils\Html;
use Psr\Cache\InvalidArgumentException as PsrCacheInvalidArgumentException;
use SixtyEightPublishers\WebpackEncoreBundle\Event\RenderAssetTagEvent;
use Symfony\Component\Asset\Packages;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Throwable;
use function array_merge;
use function implode;

final class TagRenderer
{
    private EntryPointLookupCollectionInterface $entrypointLookupCollection;

    private Packages $packages;

    /** @var array<string, string|true> */
    private array $defaultAttributes;

    /** @var array<string, string|true> */
    private array $defaultScriptAttributes;

    /** @var array<string, string|true> */
    private array $defaultLinkAttributes;

    private ?EventDispatcherInterface $eventDispatcher;

    /** @var array{scripts: list<string>, styles: list<string>} */
    private array $renderedFiles;

    /**
     * @param array<string, string|true> $defaultAttributes
     * @param array<string, string|true> $defaultScriptAttributes
     * @param array<string, string|true> $defaultLinkAttributes
     */
    public function __construct(
        EntryPointLookupCollectionInterface $entrypointLookupCollection,
        Packages $packages,
        array $defaultAttributes = [],
        array $defaultScriptAttributes = [],
        array $defaultLinkAttributes = [],
        ?EventDispatcherInterface $eventDispatcher = null,
    ) {
        $this->entrypointLookupCollection = $entrypointLookupCollection;
        $this->packages = $packages;
        $this->defaultAttributes = $defaultAttributes;
        $this->defaultScriptAttributes = $defaultScriptAttributes;
        $this->defaultLinkAttributes = $defaultLinkAttributes;
        $this->eventDispatcher = $eventDispatcher;

        $this->reset();
    }

    /**
     * @param array<string, string|true|null> $extraAttributes
     *
     * @throws Throwable|PsrCacheInvalidArgumentException
     */
    public function renderScriptTags(string $entryName, ?string $packageName = null, ?string $entrypointName = null, array $extraAttributes = []): string
    {
        $entryPointLookup = $this->entrypointLookupCollection->getEntrypointLookup($entrypointName);
        $integrityHashes = $entryPointLookup instanceof IntegrityDataProviderInterface ? $entryPointLookup->getIntegrityData() : [];
        $defaultAttributes = array_merge($this->defaultAttributes, $this->defaultScriptAttributes, $extraAttributes);
        $scriptTags = [];

        foreach ($entryPointLookup->getJavaScriptFiles($entryName) as $filename) {
            $attributes = ['src' => $this->packages->getUrl($filename, $packageName)];
            $attributes += $defaultAttributes;

            if (isset($integrityHashes[$filename])) {
                $attributes['integrity'] = $integrityHashes[$filename];
            }

            $event = new RenderAssetTagEvent(
                RenderAssetTagEvent::TypeScript,
                (string) $attributes['src'],
                $attributes,
            );

            if (null !== $this->eventDispatcher) {
                /** @var RenderAssetTagEvent $event */
                $event = $this->eventDispatcher->dispatch($event);
            }

            $attributes = $event->getAttributes();
            $scriptTags[] = Html::el('script')->addAttributes($attributes);
            $this->renderedFiles['scripts'][] = $attributes['src'];
        }

        return implode("\n", $scriptTags);
    }

    /**
     * @param array<string, string|true|null> $extraAttributes
     *
     * @throws Throwable|PsrCacheInvalidArgumentException
     */
    public function renderLinkTags(string $entryName, ?string $packageName = null, ?string $entrypointName = null, array $extraAttributes = []): string
    {
        $entryPointLookup = $this->entrypointLookupCollection->getEntrypointLookup($entrypointName);
        $integrityHashes = $entryPointLookup instanceof IntegrityDataProviderInterface ? $entryPointLookup->getIntegrityData() : [];
        $defaultAttributes = array_merge($this->defaultAttributes, $this->defaultLinkAttributes, $extraAttributes);
        $linkTags = [];

        foreach ($entryPointLookup->getCssFiles($entryName) as $filename) {
            $attributes = [
                'rel' => 'stylesheet',
                'href' => $this->packages->getUrl($filename, $packageName),
            ];
            $attributes += $defaultAttributes;

            if (isset($integrityHashes[$filename])) {
                $attributes['integrity'] = $integrityHashes[$filename];
            }

            $event = new RenderAssetTagEvent(
                RenderAssetTagEvent::TypeLink,
                (string) $attributes['href'],
                $attributes,
            );

            if (null !== $this->eventDispatcher) {
                /** @var RenderAssetTagEvent $event */
                $event = $this->eventDispatcher->dispatch($event);
            }

            $attributes = $event->getAttributes();
            $linkTags[] = Html::el('link')->addAttributes($attributes);
            $this->renderedFiles['styles'][] = $attributes['href'];
        }

        return implode("\n", $linkTags);
    }

    /**
     * @return list<string>
     */
    public function getRenderedScripts(): array
    {
        return $this->renderedFiles['scripts'];
    }

    /**
     * @return list<string>
     */
    public function getRenderedStyles(): array
    {
        return $this->renderedFiles['styles'];
    }

    /**
     * @return array<string, string|true>
     */
    public function getDefaultAttributes(): array
    {
        return $this->defaultAttributes;
    }

    public function reset(): void
    {
        $this->renderedFiles = [
            'scripts' => [],
            'styles' => [],
        ];
    }
}
