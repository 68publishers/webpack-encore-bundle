<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Tests\Cases\Latte;

use Tester;
use Mockery;
use Symfony;
use SixtyEightPublishers;

require __DIR__ . '/../../bootstrap.php';

final class TagRendererTest extends Tester\TestCase
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
	public function testRenderScriptTagsWithDefaultAttributes(): void
	{
		$entryPointLookup = Mockery::mock(SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookup::class);
		$entryPointLookupProvider = Mockery::mock(SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookupProvider::class);
		$packages = Mockery::mock(Symfony\Component\Asset\Packages::class);

		$entryPointLookup->shouldReceive('getJsFiles')->with('my_entry')->once()->andReturn([ '/build/file1.js', '/build/file2.js' ]);

		$entryPointLookupProvider->shouldReceive('getEntryPointLookup')->with(NULL)->once()->andReturn($entryPointLookup);

		$packages->shouldReceive('getUrl')->with('/build/file1.js', 'custom_package')->once()->andReturn('http://localhost:8080/build/file1.js');
		$packages->shouldReceive('getUrl')->with('/build/file2.js', 'custom_package')->once()->andReturn('http://localhost:8080/build/file2.js');

		$renderer = new SixtyEightPublishers\WebpackEncoreBundle\Latte\TagRenderer($entryPointLookupProvider, $packages);
		$tags = $renderer->renderJsTags('my_entry', 'custom_package');

		Tester\Assert::contains('<script src="http://localhost:8080/build/file1.js"></script>', $tags);
		Tester\Assert::contains('<script src="http://localhost:8080/build/file2.js"></script>', $tags);
	}

	/**
	 * @return void
	 */
	public function testRenderScriptTagsWithinAnEntryPointCollection(): void
	{
		$firstEntryPointLookup = Mockery::mock(SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookup::class);
		$secondEntryPointLookup = Mockery::mock(SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookup::class);
		$thirdEntryPointLookup = Mockery::mock(SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookup::class);

		$entryPointLookupProvider = Mockery::mock(SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookupProvider::class);
		$packages = Mockery::mock(Symfony\Component\Asset\Packages::class);

		$firstEntryPointLookup->shouldReceive('getJsFiles')->once()->andReturn([ '/build/file1.js' ]);
		$secondEntryPointLookup->shouldReceive('getJsFiles')->once()->andReturn([ '/build/file2.js' ]);
		$thirdEntryPointLookup->shouldReceive('getJsFiles')->once()->andReturn([ '/build/file3.js' ]);

		$entryPointLookupProvider->shouldReceive('getEntryPointLookup')->with(NULL)->once()->andReturn($firstEntryPointLookup);
		$entryPointLookupProvider->shouldReceive('getEntryPointLookup')->with('second')->once()->andReturn($secondEntryPointLookup);
		$entryPointLookupProvider->shouldReceive('getEntryPointLookup')->with('third')->once()->andReturn($thirdEntryPointLookup);

		$packages->shouldReceive('getUrl')->with('/build/file1.js', 'custom_package')->once()->andReturn('http://localhost:8080/build/file1.js');
		$packages->shouldReceive('getUrl')->with('/build/file2.js', NULL)->once()->andReturn('http://localhost:8080/build/file2.js');
		$packages->shouldReceive('getUrl')->with('/build/file3.js', 'specific_package')->once()->andReturn('http://localhost:8080/build/file3.js');


		$renderer = new SixtyEightPublishers\WebpackEncoreBundle\Latte\TagRenderer($entryPointLookupProvider, $packages, [ 'crossorigin' => 'anonymous' ]);


		Tester\Assert::contains(
			'<script crossorigin="anonymous" src="http://localhost:8080/build/file1.js"></script>',
			$renderer->renderJsTags('my_entry', 'custom_package')
		);

		Tester\Assert::contains(
			'<script crossorigin="anonymous" src="http://localhost:8080/build/file2.js"></script>',
			$renderer->renderJsTags('my_entry', NULL, 'second')
		);

		Tester\Assert::contains(
			'<script crossorigin="anonymous" src="http://localhost:8080/build/file3.js"></script>',
			$renderer->renderJsTags('my_entry', 'specific_package', 'third')
		);
	}

	/**
	 * @return void
	 */
	public function testRenderScriptTagsWithHashes(): void
	{
		$entryPointLookup = Mockery::mock(
			SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookup::class,
			SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IIntegrityDataProvider::class
		);
		$entryPointLookupProvider = Mockery::mock(SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookupProvider::class);
		$packages = Mockery::mock(Symfony\Component\Asset\Packages::class);

		$entryPointLookup->shouldReceive('getJsFiles')->once()->andReturn([
			'/build/file1.js', '/build/file2.js',
		]);

		$entryPointLookup->shouldReceive('getIntegrityData')->once()->andReturn([
			'/build/file1.js' => 'sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc',
			'/build/file2.js' => 'sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J',
		]);

		$entryPointLookupProvider->shouldReceive('getEntryPointLookup')->with(NULL)->once()->andReturn($entryPointLookup);
		$packages->shouldReceive('getUrl')->with('/build/file1.js', 'custom_package')->once()->andReturn('http://localhost:8080/build/file1.js');
		$packages->shouldReceive('getUrl')->with('/build/file2.js', 'custom_package')->once()->andReturn('http://localhost:8080/build/file2.js');

		$renderer = new SixtyEightPublishers\WebpackEncoreBundle\Latte\TagRenderer($entryPointLookupProvider, $packages, [ 'crossorigin' => 'anonymous' ]);
		$output = $renderer->renderJsTags('my_entry', 'custom_package');

		Tester\Assert::contains(
			'<script crossorigin="anonymous" src="http://localhost:8080/build/file1.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script>',
			$output
		);

		Tester\Assert::contains(
			'<script crossorigin="anonymous" src="http://localhost:8080/build/file2.js" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J"></script>',
			$output
		);
	}
}

(new TagRendererTest())->run();
