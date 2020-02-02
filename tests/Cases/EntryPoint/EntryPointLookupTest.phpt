<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Tests\Cases\EntryPoint;

use Nette;
use Tester;
use SixtyEightPublishers;

require __DIR__ . '/../../bootstrap.php';

final class EntryPointLookupTest extends Tester\TestCase
{
	private $json = <<<JSON
{
  "entrypoints": {
    "my_entry": {
      "js": [
        "file1.js",
        "file2.js"
      ],
      "css": [
        "styles.css",
        "styles2.css"
      ]
    },
    "other_entry": {
      "js": [
        "file1.js",
        "file3.js"
      ],
      "css": []
    }
  },
  "integrity": {
    "file1.js": "sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc",
    "styles.css": "sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J"
  }
}
JSON;

	/** @var NULL|string */
	private $entryPointFilename;

	/** @var NULL|\SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\EntryPointLookup */
	private $entryPointLookup;

	/**
	 * {@inheritdoc}
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->entryPointFilename = $this->createJsonFile($this->json);
		$this->entryPointLookup = $this->createEntryPointLookup($this->entryPointFilename);
	}

	/**
	 * @return void
	 */
	public function testGetJsFiles(): void
	{
		Tester\Assert::equal([ 'file1.js', 'file2.js' ], $this->entryPointLookup->getJsFiles('my_entry'));
		Tester\Assert::equal([], $this->entryPointLookup->getJsFiles('my_entry'));

		$this->entryPointLookup->reset();

		Tester\Assert::equal([ 'file1.js', 'file2.js' ], $this->entryPointLookup->getJsFiles('my_entry'));
	}

	/**
	 * @return void
	 */
	public function testGetJsFilesReturnsUniqueFilesOnly(): void
	{
		Tester\Assert::equal([ 'file1.js', 'file2.js' ], $this->entryPointLookup->getJsFiles('my_entry'));
		Tester\Assert::equal([ 'file3.js' ], $this->entryPointLookup->getJsFiles('other_entry'));
	}

	/**
	 * @return void
	 */
	public function testGetCssFiles(): void
	{
		Tester\Assert::equal([ 'styles.css', 'styles2.css' ], $this->entryPointLookup->getCssFiles('my_entry'));
	}

	/**
	 * @return void
	 */
	public function testEmptyReturnOnValidEntryNoJsOrCssFile(): void
	{
		Tester\Assert::equal([], $this->entryPointLookup->getCssFiles('other_entry'));
	}

	/**
	 * @return void
	 */
	public function testGetIntegrityData(): void
	{
		Tester\Assert::equal([
			'file1.js' => 'sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc',
			'styles.css' => 'sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J',
		], $this->entryPointLookup->getIntegrityData());
	}

	/**
	 * @return void
	 */
	public function testMissingIntegrityData(): void
	{
		$this->entryPointLookup = $this->createEntryPointLookup(
			$this->createJsonFile('{ "entrypoints": { "other_entry": { "js": { } } } }')
		);

		Tester\Assert::equal([], $this->entryPointLookup->getIntegrityData());
	}

	/**
	 * @return void
	 */
	public function testExceptionOnInvalidJson(): void
	{
		$filename = $this->createJsonFile('abcd');
		$this->entryPointLookup = $this->createEntryPointLookup($filename);

		Tester\Assert::exception(
			function () {
				$this->entryPointLookup->getCssFiles('an_entry');
			},
			SixtyEightPublishers\WebpackEncoreBundle\Exception\InvalidStateException::class,
			sprintf('The entrypoints file "%s" is not valid JSON.', $filename)
		);
	}

	/**
	 * @return void
	 */
	public function testExceptionOnMissingEntryPointsKeyInJson(): void
	{
		$filename = $this->createJsonFile('{}');
		$this->entryPointLookup = $this->createEntryPointLookup($filename);

		Tester\Assert::exception(
			function () {
				$this->entryPointLookup->getJsFiles('an_entry');
			},
			SixtyEightPublishers\WebpackEncoreBundle\Exception\InvalidStateException::class,
			sprintf('Could not find an "entrypoints" key in the "%s" file.', $filename)
		);
	}

	/**
	 * @return void
	 */
	public function testExceptionOnBadFilename(): void
	{
		$this->entryPointLookup = new SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\EntryPointLookup(
			'foo',
			'/invalid/path/to/manifest.json'
		);

		Tester\Assert::exception(
			function () {
				$this->entryPointLookup->getJsFiles('an_entry');
			},
			SixtyEightPublishers\WebpackEncoreBundle\Exception\InvalidStateException::class,
			'Could not find the entrypoints file from Webpack: the file "/invalid/path/to/manifest.json" does not exist.'
		);
	}

	/**
	 * @return void
	 */
	public function testExceptionOnMissingEntry(): void
	{
		Tester\Assert::exception(
			function () {
				$this->entryPointLookup->getCssFiles('fake_entry');
			},
			SixtyEightPublishers\WebpackEncoreBundle\Exception\EntryPointNotFoundException::class,
			sprintf('Could not find the entry "fake_entry" in "%s". Found: my_entry, other_entry.', $this->entryPointFilename)
		);
	}

	/**
	 * @return void
	 */
	public function testExceptionOnEntryWithExtension(): void
	{
		Tester\Assert::exception(
			function () {
				$this->entryPointLookup->getJsFiles('my_entry.js');
			},
			SixtyEightPublishers\WebpackEncoreBundle\Exception\EntryPointNotFoundException::class,
			'Could not find the entry "my_entry.js". Try "my_entry" instead (without the extension).'
		);
	}

	/**
	 * @return void
	 */
	public function testCachingEntryPointLookupCacheMissed(): void
	{
		$filename = $this->createJsonFile($this->json);
		$cache = new Nette\Caching\Cache(new Nette\Caching\Storages\MemoryStorage(), 'foo');

		$this->entryPointLookup = $this->createEntryPointLookup($filename, $cache, 'build_name');

		Tester\Assert::equal(
			['file1.js', 'file2.js'],
			$this->entryPointLookup->getJsFiles('my_entry')
		);

		Tester\Assert::equal(
			Nette\Utils\Json::decode($this->json, Nette\Utils\Json::FORCE_ARRAY),
			$cache->load('build_name')
		);
	}

	/**
	 * @return void
	 */
	public function testCachingEntryPointLookupCacheHit(): void
	{
		$filename = $this->createJsonFile($this->json);
		$cache = new Nette\Caching\Cache(new Nette\Caching\Storages\MemoryStorage(), 'foo');

		$this->entryPointLookup = $this->createEntryPointLookup($filename, $cache, 'build_name');

		$cache->save('build_name', Nette\Utils\Json::decode($this->json, Nette\Utils\Json::FORCE_ARRAY));

		Tester\Assert::equal(
			['file1.js', 'file2.js'],
			$this->entryPointLookup->getJsFiles('my_entry')
		);
	}

	/**
	 * @param string                    $file
	 * @param \Nette\Caching\Cache|NULL $cache
	 * @param string                    $name
	 *
	 * @return \SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\EntryPointLookup
	 */
	private function createEntryPointLookup(string $file, ?Nette\Caching\Cache $cache = NULL, string $name = 'foo'): SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\EntryPointLookup
	{
		return  new SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\EntryPointLookup($name, $file, $cache);
	}

	/**
	 * @param string $json
	 *
	 * @return string
	 */
	private function createJsonFile(string $json): string
	{
		return Tester\FileMock::create($json, 'json');
	}
}

(new EntryPointLookupTest())->run();
