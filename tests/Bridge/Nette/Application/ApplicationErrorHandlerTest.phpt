<?php

declare(strict_types=1);

namespace SixtyEightPublishers\Asset\Tests\Bridge\Nette\Application;

use Mockery;
use Tester\Assert;
use Tester\TestCase;
use Nette\Application\Application;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\EntryPointLookupInterface;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\EntryPointLookupCollectionInterface;
use SixtyEightPublishers\WebpackEncoreBundle\Bridge\Nette\Application\ApplicationErrorHandler;

require __DIR__ . '/../../../bootstrap.php';

final class ApplicationErrorHandlerTest extends TestCase
{
	public function testHandlerInstallation(): void
	{
		$application = Mockery::mock(Application::class);
		$entrypointCollection = Mockery::mock(EntryPointLookupCollectionInterface::class);

		ApplicationErrorHandler::register($application, $entrypointCollection, []);

		Assert::count(1, $application->onError);
		Assert::type(ApplicationErrorHandler::class, $application->onError[0]);
	}

	public function testInvokingHandler(): void
	{
		$entrypointLookup1 = Mockery::mock(EntryPointLookupInterface::class);
		$entrypointLookup2 = Mockery::mock(EntryPointLookupInterface::class);
		$entrypointCollection = Mockery::mock(EntryPointLookupCollectionInterface::class);

		$entrypointLookup1->shouldReceive('reset')->once();
		$entrypointLookup2->shouldReceive('reset')->once();

		$entrypointCollection->shouldReceive('getEntrypointLookup')
			->once()
			->with('_default')
			->andReturn($entrypointLookup1);

		$entrypointCollection->shouldReceive('getEntrypointLookup')
			->once()
			->with('other_build')
			->andReturn($entrypointLookup2);

		$handler = new ApplicationErrorHandler($entrypointCollection, ['_default', 'other_build']);

		$handler();
	}

	protected function tearDown(): void
	{
		Mockery::close();
	}
}

(new ApplicationErrorHandlerTest())->run();
