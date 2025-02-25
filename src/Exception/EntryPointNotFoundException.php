<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Exception;

use InvalidArgumentException;
use function sprintf;

final class EntryPointNotFoundException extends InvalidArgumentException
{
    /**
     * @param list<string> $existingEntries
     */
    public static function missingEntry(string $entryName, string $filePath, array $existingEntries): self
    {
        return new self(sprintf(
            'Could not find the entry "%s" in "%s". Found: %s.',
            $entryName,
            $filePath,
            implode(', ', $existingEntries),
        ));
    }

    public static function missingEntryWithSuggestion(string $entryName, string $suggestedEntryName): self
    {
        return new self(sprintf(
            'Could not find the entry "%s". Try "%s" instead (without the extension).',
            $entryName,
            $suggestedEntryName,
        ));
    }
}
