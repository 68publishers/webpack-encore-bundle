<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\EntryPoint;

interface IEntryPointLookup
{
	/**
	 * @return string
	 */
	public function getBuildName(): string;

	/**
	 * @param string $entryName
	 *
	 * @return array
	 * @throws \SixtyEightPublishers\WebpackEncoreBundle\Exception\EntryPointNotFoundException
	 */
	public function getJsFiles(string $entryName): array;

	/**
	 * @param string $entryName
	 *
	 * @return array
	 * @throws \SixtyEightPublishers\WebpackEncoreBundle\Exception\EntryPointNotFoundException
	 */
	public function getCssFiles(string $entryName): array;

	/**
	 * @return void
	 */
	public function reset(): void;
}
