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

	/** @var array  */
	private $defaultAttributes;

	/**
	 * @param \SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookupProvider $entryPointLookupProvider
	 * @param \Symfony\Component\Asset\Packages                                              $packages
	 * @param array                                                                          $defaultAttributes
	 */
	public function __construct(
		SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookupProvider $entryPointLookupProvider,
		Symfony\Component\Asset\Packages $packages,
		array $defaultAttributes = []
	) {
		$this->entryPointLookupProvider = $entryPointLookupProvider;
		$this->packages = $packages;
		$this->defaultAttributes = $defaultAttributes;
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
		$entryPointLookup = $this->entryPointLookupProvider->getEntryPointLookup($buildName);
		$integrityHashes = ($entryPointLookup instanceof SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IIntegrityDataProvider) ? $entryPointLookup->getIntegrityData() : [];
		$htmlTag = Nette\Utils\Html::el('script')->addAttributes($this->defaultAttributes);

		foreach (($tags = $entryPointLookup->getJsFiles($entryName)) as $i => $file) {
			$tags[$i] = (clone $htmlTag)
				->addAttributes([
					'src' => $this->packages->getUrl($file, $packageName),
					'integrity' => $integrityHashes[$file] ?? NULL,
				])
				->render();
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
		$entryPointLookup = $this->entryPointLookupProvider->getEntryPointLookup($buildName);
		$integrityHashes = ($entryPointLookup instanceof SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IIntegrityDataProvider) ? $entryPointLookup->getIntegrityData() : [];

		$htmlTag = Nette\Utils\Html::el('link')
			->addAttributes($this->defaultAttributes)
			->setAttribute('rel', 'stylesheet');

		foreach (($tags = $entryPointLookup->getCssFiles($entryName)) as $i => $file) {
			$tags[$i] = (clone $htmlTag)
				->addAttributes([
					'href' => $this->packages->getUrl($file, $packageName),
					'integrity' => $integrityHashes[$file] ?? NULL,
				])
				->render();
		}

		return implode("\n", $tags);
	}
}
