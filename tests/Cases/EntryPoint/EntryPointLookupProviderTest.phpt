<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Tests\Cases\EntryPoint;

use Tester;
use Mockery;
use SixtyEightPublishers;

require __DIR__ . '/../../bootstrap.php';

final class EntryPointLookupProviderTest extends Tester\TestCase
{
	/**
	 * {@inheritdoc}
	 */
	protected function tearDown(): void
	{
		parent::tearDown();

		Mockery::close();
	}

	/**
	 * @return void
	 */
	public function testExceptionOnMissingBuildEntry(): void
	{
		$provider = new SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\EntryPointLookupProvider([]);

		Tester\Assert::exception(
			function () use ($provider) {
				$provider->getEntryPointLookup('foo');
			},
			SixtyEightPublishers\WebpackEncoreBundle\Exception\EntryPointNotFoundException::class,
			'The build "foo" is not configured.'
		);
	}

	/**
	 * @return void
	 */
	public function testExceptionOnMissingDefaultBuildEntry(): void
	{
		$provider = new SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\EntryPointLookupProvider([]);

		Tester\Assert::exception(
			function () use ($provider) {
				$provider->getEntryPointLookup();
			},
			SixtyEightPublishers\WebpackEncoreBundle\Exception\EntryPointNotFoundException::class,
			'There is no default build configured: please pass an argument to getEntryPointLookup().'
		);
	}

	/**
	 * @return void
	 */
	public function testBuildIsReturned(): void
	{
		$lookup = Mockery::mock(SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookup::class);

		$lookup->shouldReceive('getBuildName')
			->andReturn('foo');

		$provider = new SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\EntryPointLookupProvider([ $lookup ]);

		Tester\Assert::same($lookup, $provider->getEntryPointLookup('foo'));
	}

	/**
	 * @return void
	 */
	public function testDefaultBuildIsReturned(): void
	{
		$lookup = Mockery::mock(SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookup::class);

		$lookup->shouldReceive('getBuildName')
			->andReturn('_default');

		$provider = new SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\EntryPointLookupProvider(
			[ $lookup ],
			'_default'
		);

		Tester\Assert::same($lookup, $provider->getEntryPointLookup());
	}
}

(new EntryPointLookupProviderTest())->run();
