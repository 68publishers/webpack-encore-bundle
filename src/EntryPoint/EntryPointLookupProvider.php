<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\EntryPoint;

use Nette;
use SixtyEightPublishers;

final class EntryPointLookupProvider implements IEntryPointLookupProvider
{
	use Nette\SmartObject;

	/** @var \SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookup[] */
	private $lookups = [];

	/** @var string|NULL  */
	private $defaultName;

	/**
	 * @param \SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookup[] $lookups
	 * @param string|null                                                              $defaultName
	 */
	public function __construct(array $lookups, ?string $defaultName = NULL)
	{
		foreach ($lookups as $lookup) {
			$this->add($lookup);
		}

		$this->defaultName = $defaultName;
	}

	/**
	 * @param \SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookup $lookup
	 *
	 * @return void
	 */
	private function add(IEntryPointLookup $lookup): void
	{
		$this->lookups[$lookup->getBuildName()] = $lookup;
	}

	/************** interface \SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookupProvider **************/

	/**
	 * {@inheritdoc}
	 */
	public function getEntryPointLookup(string $buildName = NULL): IEntryPointLookup
	{
		$buildName = $buildName ?? $this->defaultName;

		if (NULL === $buildName) {
			throw new SixtyEightPublishers\WebpackEncoreBundle\Exception\EntryPointNotFoundException('There is no default build configured: please pass an argument to getEntryPointLookup().');
		}

		if (!isset($this->lookups[$buildName])) {
			throw new SixtyEightPublishers\WebpackEncoreBundle\Exception\EntryPointNotFoundException(sprintf(
				'The build "%s" is not configured.',
				$buildName
			));
		}

		return $this->lookups[$buildName];
	}
}
