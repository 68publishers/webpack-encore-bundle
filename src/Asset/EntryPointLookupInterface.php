<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Asset;

use SixtyEightPublishers\WebpackEncoreBundle\Exception\EntryPointNotFoundException;

interface EntryPointLookupInterface
{
	/**
	 * @throws EntrypointNotFoundException
	 *
	 * @return array<string>
	 */
	public function getJavaScriptFiles(string $entryName): array;

	/**
	 * @throws EntrypointNotFoundException
	 *
	 * @return array<string>
	 */
	public function getCssFiles(string $entryName): array;

	public function entryExists(string $entryName): bool;

	public function reset(): void;
}
