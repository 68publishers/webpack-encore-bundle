<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Bridge\Latte;

use Latte\Extension;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\EntryPointLookupCollectionInterface;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\TagRenderer;
use SixtyEightPublishers\WebpackEncoreBundle\Bridge\Latte\Node\EncoreCssNode;
use SixtyEightPublishers\WebpackEncoreBundle\Bridge\Latte\Node\EncoreJsNode;

final class WebpackEncoreLatteExtension extends Extension
{
    private EntryPointLookupCollectionInterface $entryPointLookupCollection;

    private TagRenderer $tagRenderer;

    public function __construct(EntryPointLookupCollectionInterface $entryPointLookupCollection, TagRenderer $tagRenderer)
    {
        $this->entryPointLookupCollection = $entryPointLookupCollection;
        $this->tagRenderer = $tagRenderer;
    }

    public function getProviders(): array
    {
        return [
            'webpackEncoreTagRenderer' => $this->tagRenderer,
        ];
    }

    public function getFunctions(): array
    {
        return [
            'encore_js_files' => fn (string $entryName, ?string $entrypointName = null): array => $this->entryPointLookupCollection
                ->getEntrypointLookup($entrypointName)
                ->getJavaScriptFiles($entryName),

            'encore_css_files' => fn (string $entryName, ?string $entrypointName = null): array => $this->entryPointLookupCollection
                ->getEntrypointLookup($entrypointName)
                ->getCssFiles($entryName),

            'encore_entry_exists' => fn (string $entryName, ?string $entrypointName = null): bool => $this->entryPointLookupCollection
                ->getEntrypointLookup($entrypointName)
                ->entryExists($entryName),
        ];
    }

    public function getTags(): array
    {
        return [
            'encore_js' => [EncoreJsNode::class, 'create'],
            'encore_css' => [EncoreCssNode::class, 'create'],
        ];
    }
}
