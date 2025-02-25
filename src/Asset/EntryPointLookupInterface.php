<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Asset;

use SixtyEightPublishers\WebpackEncoreBundle\Exception\EntryPointNotFoundException;

interface EntryPointLookupInterface
{
    /**
     * @throws EntryPointNotFoundException
     *
     * @return list<string>
     */
    public function getJavaScriptFiles(string $entryName): array;

    /**
     * @throws EntryPointNotFoundException
     *
     * @return list<string>
     */
    public function getCssFiles(string $entryName): array;

    public function entryExists(string $entryName): bool;

    public function reset(): void;
}
