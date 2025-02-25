<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Bridge\Nette\Application;

use Nette\Application\Application;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\EntryPointLookupCollectionInterface;

final class ApplicationErrorHandler
{
    private EntryPointLookupCollectionInterface $entrypointLookupCollection;

    /** @var list<string> */
    private array $buildNames;

    /**
     * @param list<string> $buildNames
     */
    public function __construct(EntryPointLookupCollectionInterface $entrypointLookupCollection, array $buildNames)
    {
        $this->entrypointLookupCollection = $entrypointLookupCollection;
        $this->buildNames = $buildNames;
    }

    /**
     * @param list<string> $buildNames
     */
    public static function register(Application $application, EntryPointLookupCollectionInterface $entrypointLookupCollection, array $buildNames): void
    {
        $application->onError[] = new self($entrypointLookupCollection, $buildNames);
    }

    public function __invoke(): void
    {
        foreach ($this->buildNames as $buildName) {
            $this->entrypointLookupCollection->getEntrypointLookup($buildName)->reset();
        }
    }
}
