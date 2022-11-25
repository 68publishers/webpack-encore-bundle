<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Tests\Asset;

use Mockery;
use Tester\Assert;
use Tester\TestCase;
use Symfony\Component\Asset\Packages;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\TagRenderer;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use SixtyEightPublishers\WebpackEncoreBundle\Event\RenderAssetTagEvent;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\EntryPointLookupInterface;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\IntegrityDataProviderInterface;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\EntryPointLookupCollectionInterface;

require __DIR__ . '/../bootstrap.php';

final class TagRendererTest extends TestCase
{
	public function testRenderScriptTagsWithDefaultAttributes(): void
	{
		$entrypointLookup = Mockery::mock(EntryPointLookupInterface::class);
		$entrypointCollection = Mockery::mock(EntryPointLookupCollectionInterface::class);
		$packages = Mockery::mock(Packages::class);

		$entrypointLookup->shouldReceive('getJavaScriptFiles')
			->once()
			->andReturn(['/build/file1.js', '/build/file2.js']);

		$entrypointCollection->shouldReceive('getEntrypointLookup')
			->once()
			->with(NULL)
			->andReturn($entrypointLookup);

		$packages->shouldReceive('getUrl')
			->once()
			->with('/build/file1.js', 'custom_package')
			->andReturnUsing(static fn (string $path): string => 'http://localhost:8080' . $path);

		$packages->shouldReceive('getUrl')
			->once()
			->with('/build/file2.js', 'custom_package')
			->andReturnUsing(static fn (string $path): string => 'http://localhost:8080' . $path);

		$renderer = new TagRenderer($entrypointCollection, $packages, ['defer' => TRUE]);
		$output = $renderer->renderScriptTags('my_entry', 'custom_package');

		Assert::contains('<script src="http://localhost:8080/build/file1.js" defer></script>', $output);
		Assert::contains('<script src="http://localhost:8080/build/file2.js" defer></script>', $output);
	}

	public function testRenderScriptTagsWithExtraAttributes(): void
	{
		$entrypointLookup = Mockery::mock(EntryPointLookupInterface::class);
		$entrypointCollection = Mockery::mock(EntryPointLookupCollectionInterface::class);
		$packages = Mockery::mock(Packages::class);

		$entrypointLookup->shouldReceive('getJavaScriptFiles')
			->once()
			->andReturn(['/build/file1.js']);

		$entrypointCollection->shouldReceive('getEntrypointLookup')
			->once()
			->with(NULL)
			->andReturn($entrypointLookup);

		$packages->shouldReceive('getUrl')
			->once()
			->with('/build/file1.js', NULL)
			->andReturn('http://localhost:8080/build/file1.js');

		$renderer = new TagRenderer(
			$entrypointCollection,
			$packages,
			[
				'defer' => TRUE,
				'nonce' => 'abc123',
			],
			[
				'referrerpolicy' => 'origin',
			]
		);

		$output = $renderer->renderScriptTags('my_entry', NULL, NULL, [
			'nonce' => '12345',
		]);

		Assert::contains('<script src="http://localhost:8080/build/file1.js" defer nonce="12345" referrerpolicy="origin"></script>', $output);
	}

	public function testRenderScriptTagsDispatchesAnEvent(): void
	{
		$entrypointLookup = Mockery::mock(EntryPointLookupInterface::class);
		$entrypointCollection = Mockery::mock(EntryPointLookupCollectionInterface::class);
		$packages = Mockery::mock(Packages::class);
		$dispatcher = Mockery::mock(EventDispatcherInterface::class);

		$entrypointLookup->shouldReceive('getJavaScriptFiles')
			->once()
			->andReturn(['/build/file1.js']);

		$entrypointCollection->shouldReceive('getEntrypointLookup')
			->once()
			->with(NULL)
			->andReturn($entrypointLookup);

		$packages->shouldReceive('getUrl')
			->once()
			->with('/build/file1.js', NULL)
			->andReturn('http://localhost:8080/build/file1.js');

		$dispatcher->shouldReceive('dispatch')
			->once()
			->withArgs(static fn ($arg): bool => $arg instanceof RenderAssetTagEvent)
			->andReturnUsing(static function (RenderAssetTagEvent $event): RenderAssetTagEvent {
				$event->setAttribute('nonce', 'some_nonce_here');

				return $event;
			});

		$renderer = new TagRenderer($entrypointCollection, $packages, [], [], [], $dispatcher);
		$output = $renderer->renderScriptTags('my_entry');

		Assert::contains('<script src="http://localhost:8080/build/file1.js" nonce="some_nonce_here"></script>', $output);
	}

	public function testRenderScriptTagsWithBadFilename(): void
	{
		$entrypointLookup = Mockery::mock(EntryPointLookupInterface::class);
		$entrypointCollection = Mockery::mock(EntryPointLookupCollectionInterface::class);
		$packages = Mockery::mock(Packages::class);

		$entrypointLookup->shouldReceive('getJavaScriptFiles')
			->once()
			->andReturn(['/build/file<"bad_chars.js']);

		$entrypointCollection->shouldReceive('getEntrypointLookup')
			->once()
			->with(NULL)
			->andReturn($entrypointLookup);

		$packages->shouldReceive('getUrl')
			->once()
			->with('/build/file<"bad_chars.js', 'custom_package')
			->andReturnUsing(static fn (string $path): string => 'http://localhost:8080' . $path);

		$renderer = new TagRenderer($entrypointCollection, $packages, ['crossorigin' => 'anonymous']);
		$output = $renderer->renderScriptTags('my_entry', 'custom_package');

		Assert::contains('<script src=\'http://localhost:8080/build/file<"bad_chars.js\' crossorigin="anonymous"></script>', $output);
	}

	public function testRenderScriptTagsWithinAnEntryPointCollection(): void
	{
		$entrypointLookup1 = Mockery::mock(EntryPointLookupInterface::class);
		$entrypointLookup2 = Mockery::mock(EntryPointLookupInterface::class);
		$entrypointLookup3 = Mockery::mock(EntryPointLookupInterface::class);
		$entrypointCollection = Mockery::mock(EntryPointLookupCollectionInterface::class);
		$packages = Mockery::mock(Packages::class);

		$entrypointLookup1->shouldReceive('getJavaScriptFiles')
			->once()
			->andReturn(['/build/file1.js']);

		$entrypointLookup2->shouldReceive('getJavaScriptFiles')
			->once()
			->andReturn(['/build/file2.js']);

		$entrypointLookup3->shouldReceive('getJavaScriptFiles')
			->once()
			->andReturn(['/build/file3.js']);

		$entrypointCollection->shouldReceive('getEntrypointLookup')
			->once()
			->with(NULL)
			->andReturn($entrypointLookup1);

		$entrypointCollection->shouldReceive('getEntrypointLookup')
			->once()
			->with('second')
			->andReturn($entrypointLookup2);

		$entrypointCollection->shouldReceive('getEntrypointLookup')
			->once()
			->with('third')
			->andReturn($entrypointLookup3);

		$packages->shouldReceive('getUrl')
			->once()
			->with('/build/file1.js', 'custom_package')
			->andReturnUsing(static fn (string $path): string => 'http://localhost:8080' . $path);

		$packages->shouldReceive('getUrl')
			->once()
			->with('/build/file2.js', NULL)
			->andReturnUsing(static fn (string $path): string => 'http://localhost:8080' . $path);

		$packages->shouldReceive('getUrl')
			->once()
			->with('/build/file3.js', 'specific_package')
			->andReturnUsing(static fn (string $path): string => 'http://localhost:8080' . $path);

		$renderer = new TagRenderer($entrypointCollection, $packages, ['crossorigin' => 'anonymous']);

		$output = $renderer->renderScriptTags('my_entry', 'custom_package');

		Assert::contains('<script src="http://localhost:8080/build/file1.js" crossorigin="anonymous"></script>', $output);

		$output = $renderer->renderScriptTags('my_entry', NULL, 'second');

		Assert::contains('<script src="http://localhost:8080/build/file2.js" crossorigin="anonymous"></script>', $output);

		$output = $renderer->renderScriptTags('my_entry', 'specific_package', 'third');

		Assert::contains('<script src="http://localhost:8080/build/file3.js" crossorigin="anonymous"></script>', $output);
	}

	public function testRenderScriptTagsWithHashes(): void
	{
		$entrypointLookup = Mockery::mock(EntryPointLookupInterface::class, IntegrityDataProviderInterface::class);
		$entrypointCollection = Mockery::mock(EntryPointLookupCollectionInterface::class);
		$packages = Mockery::mock(Packages::class);

		$entrypointLookup->shouldReceive('getJavaScriptFiles')
			->once()
			->andReturn(['/build/file1.js', '/build/file2.js']);

		$entrypointLookup->shouldReceive('getIntegrityData')
			->once()
			->andReturn([
				'/build/file1.js' => 'sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc',
				'/build/file2.js' => 'sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J',
			]);

		$entrypointCollection->shouldReceive('getEntrypointLookup')
			->once()
			->with(NULL)
			->andReturn($entrypointLookup);

		$packages->shouldReceive('getUrl')
			->once()
			->with('/build/file1.js', 'custom_package')
			->andReturnUsing(static fn (string $path): string => 'http://localhost:8080' . $path);

		$packages->shouldReceive('getUrl')
			->once()
			->with('/build/file2.js', 'custom_package')
			->andReturnUsing(static fn (string $path): string => 'http://localhost:8080' . $path);

		$renderer = new TagRenderer($entrypointCollection, $packages, ['crossorigin' => 'anonymous']);
		$output = $renderer->renderScriptTags('my_entry', 'custom_package');

		Assert::contains('<script src="http://localhost:8080/build/file1.js" crossorigin="anonymous" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script>', $output);
		Assert::contains('<script src="http://localhost:8080/build/file2.js" crossorigin="anonymous" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J"></script>', $output);
	}

	public function testGetRenderedFilesAndReset(): void
	{
		$entrypointLookup = Mockery::mock(EntryPointLookupInterface::class);
		$entrypointCollection = Mockery::mock(EntryPointLookupCollectionInterface::class);
		$packages = Mockery::mock(Packages::class);

		$entrypointLookup->shouldReceive('getJavaScriptFiles')
			->once()
			->andReturn(['/build/file1.js', '/build/file2.js']);

		$entrypointLookup->shouldReceive('getCssFiles')
			->once()
			->andReturn(['/build/file1.css']);

		$entrypointCollection->shouldReceive('getEntrypointLookup')
			->with(NULL)
			->andReturn($entrypointLookup);

		$packages->shouldReceive('getUrl')
			->andReturnUsing(static fn (string $path): string => 'http://localhost:8080' . $path);

		$renderer = new TagRenderer($entrypointCollection, $packages);

		$renderer->renderScriptTags('my_entry');
		$renderer->renderLinkTags('my_entry');

		Assert::same(['http://localhost:8080/build/file1.js', 'http://localhost:8080/build/file2.js'], $renderer->getRenderedScripts());
		Assert::same(['http://localhost:8080/build/file1.css'], $renderer->getRenderedStyles());

		$renderer->reset();

		Assert::same([], $renderer->getRenderedScripts());
		Assert::same([], $renderer->getRenderedStyles());
	}

	protected function tearDown(): void
	{
		Mockery::close();
	}
}

(new TagRendererTest())->run();
