<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Tests\Asset;

use Mockery;
use Tester\Assert;
use Tester\TestCase;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\EntryPointLookupInterface;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\EntryPointLookupCollection;
use SixtyEightPublishers\WebpackEncoreBundle\Exception\UndefinedBuildException;

require __DIR__ . '/../bootstrap.php';

final class EntryPointLookupCollectionTest extends TestCase
{
	public function testExceptionShouldBeThrownOnMissingEntry(): void
	{
		Assert::exception(
			static function () {
				$collection = new EntryPointLookupCollection([]);
				$collection->getEntrypointLookup('test');
			},
			UndefinedBuildException::class,
			'The build "test" is not configured'
		);
	}

	public function testExceptionShouldBeThrownOnMissingDefaultBuildEntry(): void
	{
		Assert::exception(
			static function () {
				$collection = new EntryPointLookupCollection([]);
				$collection->getEntrypointLookup();
			},
			UndefinedBuildException::class,
			'There is no default build configured: please pass an argument to getEntrypointLookup().'
		);
	}

	public function testDefaultBuildIsReturned(): void
	{
		$lookup = Mockery::mock(EntryPointLookupInterface::class);
		$collection = new EntrypointLookupCollection(['_default' => $lookup], '_default');

		Assert::same($lookup, $collection->getEntrypointLookup());
		Assert::same($lookup, $collection->getEntrypointLookup('_default'));
	}

	public function testNamedBuildIsReturned(): void
	{
		$lookup = Mockery::mock(EntryPointLookupInterface::class);
		$collection = new EntrypointLookupCollection(['test' => $lookup]);

		Assert::same($lookup, $collection->getEntrypointLookup('test'));
	}

	protected function tearDown(): void
	{
		Mockery::close();
	}
}

(new EntryPointLookupCollectionTest())->run();
