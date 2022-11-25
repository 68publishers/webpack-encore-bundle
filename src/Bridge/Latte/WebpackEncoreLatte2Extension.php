<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Bridge\Latte;

use Latte\Engine;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\TagRenderer;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\EntryPointLookupCollectionInterface;

final class WebpackEncoreLatte2Extension
{
	private function __construct()
	{
	}

	public static function extend(Engine $engine, EntryPointLookupCollectionInterface $entryPointLookupCollection, TagRenderer $tagRenderer): void
	{
		foreach (WebpackEncoreProviderSet::providers($tagRenderer) as $providerName => $provider) {
			$engine->addProvider($providerName, $provider);
		}

		foreach (WebpackEncoreFunctionSet::functions($entryPointLookupCollection) as $functionName => $functionCallback) {
			$engine->addFunction($functionName, $functionCallback);
		}

		$engine->onCompile[] = static function (Engine $engine): void {
			WebpackEncoreMacroSet::install($engine->getCompiler());
		};
	}
}
