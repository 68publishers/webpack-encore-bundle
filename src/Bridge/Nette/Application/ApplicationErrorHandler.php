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
        $handler = new self($entrypointLookupCollection, $buildNames);

        # the error presenter renders the entries again, a long-running process renders them in the next request
        $application->onError[] = $handler;
        $application->onShutdown[] = $handler;
    }

    public function __invoke(): void
    {
        foreach ($this->buildNames as $buildName) {
            $this->entrypointLookupCollection->getEntrypointLookup($buildName)->reset();
        }
    }
}
