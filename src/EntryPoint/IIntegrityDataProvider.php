<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\EntryPoint;

interface IIntegrityDataProvider
{
	/**
	 * @return string[]
	 */
	public function getIntegrityData(): array;
}
