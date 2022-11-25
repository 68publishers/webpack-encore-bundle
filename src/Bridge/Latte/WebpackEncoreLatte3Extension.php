<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Bridge\Latte;

use Latte\Extension;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\TagRenderer;
use SixtyEightPublishers\WebpackEncoreBundle\Bridge\Latte\Nodes\EncoreJsNode;
use SixtyEightPublishers\WebpackEncoreBundle\Bridge\Latte\Nodes\EncoreCssNode;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\EntryPointLookupCollectionInterface;

final class WebpackEncoreLatte3Extension extends Extension
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
		return WebpackEncoreProviderSet::providers($this->tagRenderer);
	}

	public function getFunctions(): array
	{
		return WebpackEncoreFunctionSet::functions($this->entryPointLookupCollection);
	}

	public function getTags(): array
	{
		return [
			'encore_js' => [EncoreJsNode::class, 'create'],
			'encore_css' => [EncoreCssNode::class, 'create'],
		];
	}
}
