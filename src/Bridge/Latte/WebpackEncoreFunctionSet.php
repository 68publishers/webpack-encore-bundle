<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Bridge\Latte;

use SixtyEightPublishers\WebpackEncoreBundle\Asset\EntryPointLookupCollectionInterface;

/**
 * @internal
 */
final class WebpackEncoreFunctionSet
{
	private function __construct()
	{
	}

	/**
	 * @return array<string, callable>
	 */
	public static function functions(EntryPointLookupCollectionInterface $entryPointLookupCollection): array
	{
		return [
			'encore_js_files' => static fn (string $entryName, ?string $entrypointName = NULL): array => $entryPointLookupCollection
				->getEntrypointLookup($entrypointName)
				->getJavaScriptFiles($entryName),

			'encore_css_files' => static fn (string $entryName, ?string $entrypointName = NULL): array => $entryPointLookupCollection
				->getEntrypointLookup($entrypointName)
				->getCssFiles($entryName),

			'encore_entry_exists' => static fn (string $entryName, ?string $entrypointName = NULL): bool => $entryPointLookupCollection
				->getEntrypointLookup($entrypointName)
				->entryExists($entryName),
		];
	}
}
