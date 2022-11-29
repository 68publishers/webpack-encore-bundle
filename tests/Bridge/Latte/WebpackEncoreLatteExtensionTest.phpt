<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Tests\Bridge\Nette\Application;

use Latte\Engine;
use Tester\Assert;
use Tester\TestCase;
use Latte\Loaders\StringLoader;
use Tester\CodeCoverage\Collector;
use Nette\Bridges\ApplicationLatte\LatteFactory;
use SixtyEightPublishers\WebpackEncoreBundle\Tests\Bridge\Nette\DI\ContainerFactory;

require __DIR__ . '/../../bootstrap.php';

final class WebpackEncoreLatteExtensionTest extends TestCase
{
	public function testEncoreJsMacroOnBaseConfig(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.neon');
		$output = $latte->renderToString('{encore_js "my_entry"}');

		$this->assertNumberOfOccurrences($output, '<script ', 2);
		Assert::contains('<script src="/file1.abc.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script>', $output);
		Assert::contains('<script src="/file2.abc.js"></script>', $output);
	}

	public function testEncoreJsMacroOnConfigWithDefaultAttributes(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.withDefaultAttributes.neon');
		$output = $latte->renderToString('{encore_js "my_entry"}');

		$this->assertNumberOfOccurrences($output, '<script ', 2);
		Assert::contains('<script src="/file1.abc.js" crossorigin="anonymous" referrerpolicy="same-origin" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script>', $output);
		Assert::contains('<script src="/file2.abc.js" crossorigin="anonymous" referrerpolicy="same-origin"></script>', $output);
	}

	public function testEncoreJsMacroWithPackageNameOnBaseConfig(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.neon');
		$output = $latte->renderToString('{encore_js "my_entry", "second"}');

		$this->assertNumberOfOccurrences($output, '<script ', 2);
		Assert::contains('<script src="/second_build/file1.abc.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script>', $output);
		Assert::contains('<script src="/second_build/file2.abc.js"></script>', $output);
	}

	public function testEncoreJsMacroWithPackageNameOnConfigWithDefaultAttributes(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.withDefaultAttributes.neon');
		$output = $latte->renderToString('{encore_js "my_entry", "second"}');

		$this->assertNumberOfOccurrences($output, '<script ', 2);
		Assert::contains('<script src="/second_build/file1.abc.js" crossorigin="anonymous" referrerpolicy="same-origin" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script>', $output);
		Assert::contains('<script src="/second_build/file2.abc.js" crossorigin="anonymous" referrerpolicy="same-origin"></script>', $output);
	}

	public function testEncoreJsMacroWithPackageNameAndEntryPointNameOnBaseConfig(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.neon');
		$output = $latte->renderToString('{encore_js "my_entry", "second", "second"}');

		$this->assertNumberOfOccurrences($output, '<script ', 2);
		Assert::contains('<script src="/second_build/file1.abc.js"></script>', $output);
		Assert::contains('<script src="/second_build/file2.abc.js"></script>', $output);
	}

	public function testEncoreJsMacroWithPackageNameAndEntryPointNameOnConfigWithDefaultAttributes(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.withDefaultAttributes.neon');
		$output = $latte->renderToString('{encore_js "my_entry", "second", "second"}');

		$this->assertNumberOfOccurrences($output, '<script ', 2);
		Assert::contains('<script src="/second_build/file1.abc.js" crossorigin="anonymous" referrerpolicy="same-origin"></script>', $output);
		Assert::contains('<script src="/second_build/file2.abc.js" crossorigin="anonymous" referrerpolicy="same-origin"></script>', $output);
	}

	public function testEncoreJsMacroWithExtraAttributeOnBaseConfig(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.neon');
		$output = $latte->renderToString('{encore_js "my_entry", null, null, [defer => true, data-script => "foo"]}');

		$this->assertNumberOfOccurrences($output, '<script ', 2);
		Assert::contains('<script src="/file1.abc.js" defer data-script="foo" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script>', $output);
		Assert::contains('<script src="/file2.abc.js" defer data-script="foo"></script>', $output);
	}

	public function testEncoreJsMacroWithExtraAttributeOnConfigWithDefaultAttributes(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.withDefaultAttributes.neon');
		$output = $latte->renderToString('{encore_js "my_entry", null, null, [defer => true, data-script => "foo"]}');

		$this->assertNumberOfOccurrences($output, '<script ', 2);
		Assert::contains('<script src="/file1.abc.js" crossorigin="anonymous" referrerpolicy="same-origin" defer data-script="foo" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script>', $output);
		Assert::contains('<script src="/file2.abc.js" crossorigin="anonymous" referrerpolicy="same-origin" defer data-script="foo"></script>', $output);
	}

	public function testEncoreJsMacroWithMultipleEntryPoints(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.neon');
		$output = $latte->renderToString(<<< LATTE
'{encore_js "my_entry"}'
'{encore_js "other_entry", null, null, [defer => true]}'
'{encore_js "my_entry", "second", "second"}'
LATTE);

		$this->assertNumberOfOccurrences($output, '<script ', 5);
		Assert::contains('<script src="/file1.abc.js" integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script>', $output);
		Assert::contains('<script src="/file2.abc.js"></script>', $output);
		Assert::contains('<script src="/file3.abc.js" defer></script>', $output);
		Assert::contains('<script src="/second_build/file1.abc.js"></script>', $output);
		Assert::contains('<script src="/second_build/file2.abc.js"></script>', $output);
	}

	public function testEncoreJsMacroWithMultipleCalls(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.neon');
		$output = $latte->renderToString('{encore_js "my_entry", null, null, [async => true]}');

		$this->assertNumberOfOccurrences($output, '<script ', 2);
		Assert::contains('<script src="/file1.abc.js" async integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"></script>', $output);
		Assert::contains('<script src="/file2.abc.js" async></script>', $output);

		$output = $latte->renderToString('{encore_js "my_entry"}');

		$this->assertNumberOfOccurrences($output, '<script ', 0);
	}

	public function testEncoreCssMacroOnBaseConfig(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.neon');
		$output = $latte->renderToString('{encore_css "my_entry"}');

		$this->assertNumberOfOccurrences($output, '<link ', 2);
		Assert::contains('<link rel="stylesheet" href="/styles.abc.css" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J">', $output);
		Assert::contains('<link rel="stylesheet" href="/styles2.abc.css">', $output);
	}

	public function testEncoreCssMacroOnConfigWithDefaultAttributes(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.withDefaultAttributes.neon');
		$output = $latte->renderToString('{encore_css "my_entry"}');

		$this->assertNumberOfOccurrences($output, '<link ', 2);
		Assert::contains('<link rel="stylesheet" href="/styles.abc.css" crossorigin="anonymous" hreflang="en" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J">', $output);
		Assert::contains('<link rel="stylesheet" href="/styles2.abc.css" crossorigin="anonymous" hreflang="en">', $output);
	}

	public function testEncoreCssMacroWithPackageNameAndEntryPointNameOnBaseConfig(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.neon');
		$output = $latte->renderToString('{encore_css "my_entry", "second", "second"}');

		$this->assertNumberOfOccurrences($output, '<link ', 1);
		Assert::contains('<link rel="stylesheet" href="/second_build/styles.abc.css">', $output);
	}

	public function testEncoreCssMacroWithPackageNameAndEntryPointNameOnConfigWithDefaultAttributes(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.withDefaultAttributes.neon');
		$output = $latte->renderToString('{encore_css "my_entry", "second", "second"}');

		$this->assertNumberOfOccurrences($output, '<link ', 1);
		Assert::contains('<link rel="stylesheet" href="/second_build/styles.abc.css" crossorigin="anonymous" hreflang="en">', $output);
	}

	public function testEncoreCssMacroWithExtraAttributeOnBaseConfig(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.neon');
		$output = $latte->renderToString('{encore_css "my_entry", null, null, [type => "text/css", data-styles => "foo"]}');

		$this->assertNumberOfOccurrences($output, '<link ', 2);
		Assert::contains('<link rel="stylesheet" href="/styles.abc.css" type="text/css" data-styles="foo" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J">', $output);
		Assert::contains('<link rel="stylesheet" href="/styles2.abc.css" type="text/css" data-styles="foo">', $output);
	}

	public function testEncoreCssMacroWithExtraAttributeOnConfigWithDefaultAttributes(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.withDefaultAttributes.neon');
		$output = $latte->renderToString('{encore_css "my_entry", null, null, [type => "text/css", data-styles => "foo"]}');

		$this->assertNumberOfOccurrences($output, '<link ', 2);
		Assert::contains('<link rel="stylesheet" href="/styles.abc.css" crossorigin="anonymous" hreflang="en" type="text/css" data-styles="foo" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J">', $output);
		Assert::contains('<link rel="stylesheet" href="/styles2.abc.css" crossorigin="anonymous" hreflang="en" type="text/css" data-styles="foo">', $output);
	}

	public function testEncoreCssMacroWithMultipleEntryPoints(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.neon');
		$output = $latte->renderToString(<<< LATTE
'{encore_css "my_entry"}'
'{encore_css "other_entry"}'
'{encore_css "my_entry", "second", "second"}'
LATTE);

		$this->assertNumberOfOccurrences($output, '<link ', 3);
		Assert::contains('<link rel="stylesheet" href="/styles.abc.css" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J">', $output);
		Assert::contains('<link rel="stylesheet" href="/styles2.abc.css">', $output);
		Assert::contains('<link rel="stylesheet" href="/second_build/styles.abc.css">', $output);
	}

	public function testEncoreCssMacroWithMultipleCalls(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.neon');
		$output = $latte->renderToString('{encore_css "my_entry", null, null, [hreflang => "cs"]}');

		$this->assertNumberOfOccurrences($output, '<link ', 2);
		Assert::contains('<link rel="stylesheet" href="/styles.abc.css" hreflang="cs" integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J">', $output);
		Assert::contains('<link rel="stylesheet" href="/styles2.abc.css" hreflang="cs">', $output);

		$output = $latte->renderToString('{encore_css "my_entry"}');

		$this->assertNumberOfOccurrences($output, '<link ', 0);
	}

	public function testEncoreJsFilesFunction(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.neon');
		$output = $latte->renderToString('{implode(", ", encore_js_files("my_entry"))}');

		Assert::same('file1.js, file2.js', $output);
	}

	public function testEncoreJsFilesFunctionWithEntryPointName(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.neon');
		$output = $latte->renderToString('{implode(", ", encore_js_files("my_entry", "second"))}');

		Assert::same('file1.js, file2.js', $output);
	}

	public function testEncoreCssFilesFunction(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.neon');
		$output = $latte->renderToString('{implode(", ", encore_css_files("my_entry"))}');

		Assert::same('styles.css, styles2.css', $output);
	}

	public function testEncoreCssFilesFunctionWithEntryPointName(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.neon');
		$output = $latte->renderToString('{implode(", ", encore_css_files("my_entry", "second"))}');

		Assert::same('styles.css', $output);
	}

	public function testEncoreEntryExistsFunction(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.neon');
		$output = $latte->renderToString('{encore_entry_exists("my_entry") ? "yes" : "no"}');

		Assert::same('yes', $output);
	}

	public function testEncoreEntryExistsFunctionWithMissingEntry(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.neon');
		$output = $latte->renderToString('{encore_entry_exists("missing_entry") ? "yes" : "no"}');

		Assert::same('no', $output);
	}

	public function testEncoreEntryExistsFunctionWithEntryPointName(): void
	{
		$latte = $this->createLatte(__DIR__ . '/config.neon');
		$output = $latte->renderToString('{encore_entry_exists("my_entry", "second") ? "yes" : "no"}');

		Assert::same('yes', $output);
	}

	private function createLatte(string $config): Engine
	{
		$container = ContainerFactory::create($config);
		$latteFactory = $container->getByType(LatteFactory::class);
		assert($latteFactory instanceof LatteFactory);
		$engine = $latteFactory->create();

		$engine->setLoader(new StringLoader());

		return $engine;
	}

	private function assertNumberOfOccurrences(string $s, string $needle, int $number): void
	{
		Assert::same($number, substr_count($s, $needle));
	}

	protected function tearDown(): void
	{
		# save manually partial code coverage to free memory
		if (Collector::isStarted()) {
			Collector::save();
		}
	}
}

(new WebpackEncoreLatteExtensionTest())->run();
