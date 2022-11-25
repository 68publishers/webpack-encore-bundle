<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Asset;

interface IntegrityDataProviderInterface
{
	/**
	 * @return array<string>
	 */
	public function getIntegrityData(): array;
}
