<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Asset;

use SixtyEightPublishers\WebpackEncoreBundle\Exception\UndefinedBuildException;

interface EntryPointLookupCollectionInterface
{
    /**
     * @throws UndefinedBuildException
     */
    public function getEntrypointLookup(?string $buildName = null): EntryPointLookupInterface;
}
