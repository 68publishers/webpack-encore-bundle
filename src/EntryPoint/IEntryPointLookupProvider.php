<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\EntryPoint;

interface IEntryPointLookupProvider
{
	/**
	 * @param string|NULL $buildName
	 *
	 * @return \SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookup
	 * @throws \SixtyEightPublishers\WebpackEncoreBundle\Exception\EntryPointNotFoundException
	 */
	public function getEntryPointLookup(?string $buildName = NULL): IEntryPointLookup;
}
