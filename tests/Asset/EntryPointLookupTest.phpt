<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Tests\Asset;

use InvalidArgumentException;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\EntryPointLookup;
use SixtyEightPublishers\WebpackEncoreBundle\Exception\EntryPointNotFoundException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Tester\Assert;
use Tester\TestCase;
use function file_get_contents;
use function json_decode;

require __DIR__ . '/../bootstrap.php';

final class EntryPointLookupTest extends TestCase
{
    public function testJavascriptFilesShouldBeReturned(): void
    {
        $lookup = new EntryPointLookup(__DIR__ . '/entrypoints.full.json');

        Assert::same(['file1.js', 'file2.js'], $lookup->getJavaScriptFiles('my_entry'));
        Assert::same([], $lookup->getJavaScriptFiles('my_entry'));

        $lookup->reset();

        Assert::same(['file1.js', 'file2.js'], $lookup->getJavaScriptFiles('my_entry'));
    }

    public function testOnlyUniqueJavascriptFilesShouldBeReturned(): void
    {
        $lookup = new EntryPointLookup(__DIR__ . '/entrypoints.full.json');

        Assert::same(['file1.js', 'file2.js'], $lookup->getJavaScriptFiles('my_entry'));
        Assert::same(['file3.js'], $lookup->getJavaScriptFiles('other_entry'));
    }

    public function testCssFilesShouldBeReturned(): void
    {
        $lookup = new EntryPointLookup(__DIR__ . '/entrypoints.full.json');

        Assert::same(['styles.css', 'styles2.css'], $lookup->getCssFiles('my_entry'));
    }

    public function testNoCssFilesShouldByReturnedOnValidEntry(): void
    {
        $lookup = new EntryPointLookup(__DIR__ . '/entrypoints.full.json');

        Assert::same([], $lookup->getCssFiles('other_entry'));
    }

    public function testIntegrityDataShouldBeReturned(): void
    {
        $lookup = new EntryPointLookup(__DIR__ . '/entrypoints.full.json');

        Assert::same([
            'file1.js' => 'sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc',
            'styles.css' => 'sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J',
        ], $lookup->getIntegrityData());
    }

    public function testNoIntegrityDataShouldBeReturned(): void
    {
        $lookup = new EntryPointLookup(__DIR__ . '/entrypoints.noIntegrity.json');

        Assert::same([], $lookup->getIntegrityData());
    }

    public function testExceptionShouldByThrownOnInvalidJson(): void
    {
        Assert::exception(
            static function () {
                $lookup = new EntryPointLookup(__DIR__ . '/entrypoints.invalid.txt');

                $lookup->getJavaScriptFiles('my_entry');
            },
            InvalidArgumentException::class,
            'There was a problem JSON decoding the "%a%/entrypoints.invalid.txt" file.',
        );
    }

    public function testExceptionShouldBeThrownOnMissingEntrypointsKeyInJson(): void
    {
        Assert::exception(
            static function () {
                $lookup = new EntryPointLookup(__DIR__ . '/entrypoints.missingEntrypointsKey.json');

                $lookup->getJavaScriptFiles('my_entry');
            },
            InvalidArgumentException::class,
            'Could not find an "entrypoints" key in the "%a%/entrypoints.missingEntrypointsKey.json" file.',
        );
    }

    public function testExceptionShouldBeThrownOnBadFilename(): void
    {
        Assert::exception(
            static function () {
                $lookup = new EntryPointLookup(__DIR__ . '/entrypoints.nonExistent.json');

                $lookup->getJavaScriptFiles('my_entry');
            },
            InvalidArgumentException::class,
            'Could not find the entrypoints file from Webpack: the file "%a%/entrypoints.nonExistent.json" does not exist.',
        );
    }

    public function testExceptionShouldBeReturnedOnMissingEntry(): void
    {
        Assert::exception(
            static function () {
                $lookup = new EntryPointLookup(__DIR__ . '/entrypoints.full.json');

                $lookup->getJavaScriptFiles('missing_entry');
            },
            EntryPointNotFoundException::class,
            'Could not find the entry "missing_entry" in "%a%/entrypoints.full.json". Found: my_entry, other_entry.',
        );
    }

    public function testExceptionShouldBeReturnedOnMissingEntryWithExtension(): void
    {
        Assert::exception(
            static function () {
                $lookup = new EntryPointLookup(__DIR__ . '/entrypoints.full.json');

                $lookup->getJavaScriptFiles('my_entry.js');
            },
            EntryPointNotFoundException::class,
            'Could not find the entry "my_entry.js". Try "my_entry" instead (without the extension).',
        );
    }

    public function testFilesShouldBeReturnedOnCachingEntryWhenCacheMissed(): void
    {
        $cache = new ArrayAdapter();
        $lookup = new EntryPointLookup(__DIR__ . '/entrypoints.full.json', $cache, 'cacheKey');

        Assert::equal(['file1.js', 'file2.js'], $lookup->getJavaScriptFiles('my_entry'));

        $cached = $cache->getItem('cacheKey');

        Assert::true($cached->isHit());
        Assert::same(
            json_decode(file_get_contents(__DIR__ . '/entrypoints.full.json'), true, 512, JSON_THROW_ON_ERROR),
            $cached->get(),
        );
    }

    public function testFilesShouldBeReturnedOnCachingEntryWhenCacheHit(): void
    {
        $cache = new ArrayAdapter();
        $lookup = new EntryPointLookup(__DIR__ . '/entrypoints.full.json', $cache, 'cacheKey');

        $cached = $cache->getItem('cacheKey');
        $cached->set(json_decode(file_get_contents(__DIR__ . '/entrypoints.full.json'), true, 512, JSON_THROW_ON_ERROR));
        $cache->save($cached);

        Assert::equal(['file1.js', 'file2.js'], $lookup->getJavaScriptFiles('my_entry'));
    }

    public function testEntryShouldExists(): void
    {
        $lookup = new EntryPointLookup(__DIR__ . '/entrypoints.full.json');

        Assert::true($lookup->entryExists('my_entry'));
    }

    public function testEntryShouldNotExists(): void
    {
        $lookup = new EntryPointLookup(__DIR__ . '/entrypoints.full.json');

        Assert::false($lookup->entryExists('missing_entry'));
    }
}

(new EntryPointLookupTest())->run();
