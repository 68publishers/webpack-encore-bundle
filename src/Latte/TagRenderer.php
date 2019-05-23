<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Latte;

use Nette;
use Symfony;
use SixtyEightPublishers;

final class TagRenderer
{
	use Nette\SmartObject;

	/** @var \SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookupProvider  */
	private $entryPointLookupProvider;

	/** @var \Symfony\Component\Asset\Packages  */
	private $packages;

	/**
	 * @param \SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookupProvider $entryPointLookupProvider
	 * @param \Symfony\Component\Asset\Packages                                              $packages
	 */
	public function __construct(SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookupProvider $entryPointLookupProvider, Symfony\Component\Asset\Packages $packages)
	{
		$this->entryPointLookupProvider = $entryPointLookupProvider;
		$this->packages = $packages;
	}

	/**
	 * @param string      $entryName
	 * @param string|NULL $packageName
	 * @param string|NULL $buildName
	 *
	 * @return string
	 */
	public function renderJsTags(string $entryName, ?string $packageName = NULL, ?string $buildName = NULL): string
	{
		$tags = $this->entryPointLookupProvider
			->getEntryPointLookup($buildName)
			->getJsFiles($entryName);

		foreach ($tags as $i => $file) {
			$tags[$i] = sprintf(
				'<script src="%s"></script>',
				htmlentities($this->packages->getUrl($file, $packageName))
			);
		}

		return implode("\n", $tags);
	}

	/**
	 * @param string      $entryName
	 * @param string|NULL $packageName
	 * @param string|NULL $buildName
	 *
	 * @return string
	 */
	public function renderCssTags(string $entryName, ?string $packageName = NULL, ?string $buildName = NULL): string
	{
		$tags = $this->entryPointLookupProvider
			->getEntryPointLookup($buildName)
			->getCssFiles($entryName);

		foreach ($tags as $i => $file) {
			$tags[$i] = sprintf(
				'<link rel="stylesheet" href="%s">',
				htmlentities($this->packages->getUrl($file, $packageName))
			);
		}

		return implode("\n", $tags);
	}
}
