<?php

declare(strict_types=1);

namespace SixtyEightPublishers\Asset\Tests\Cases\Latte;

use Tester;
use SixtyEightPublishers;

require __DIR__ . '/../../bootstrap.php';

final class WebpackEncoreMacrosIntegrationTest extends Tester\TestCase
{
	/** @var NULL|\Latte\Engine */
	private $latte;

	/**
	 * {@inheritdoc}
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$container = SixtyEightPublishers\WebpackEncoreBundle\Helper\ContainerFactory::createContainer(
			static::class . __METHOD__,
			__DIR__ . '/../../files/encore.neon'
		);

		/** @var \Nette\Bridges\ApplicationLatte\ILatteFactory $latteFactory */
		$latteFactory = $container->getService('latte.latteFactory');

		$this->latte = $latteFactory->create();
	}

	/**
	 * {@inheritdoc}
	 */
	public function testMacroIntegration(): void
	{
		$output = $this->latte->renderToString(__DIR__ . '/../../files/templates/first.latte');

		Tester\Assert::contains('<script src="/build/file1.js"></script>', $output);
		Tester\Assert::contains('<script src="/build/file2.js"></script>', $output);
		Tester\Assert::contains('<link rel="stylesheet" href="/build/styles.css">', $output);
		Tester\Assert::contains('<link rel="stylesheet" href="/build/styles2.css">', $output);
		Tester\Assert::contains('<script src="/build/other3.js"></script>', $output);

		Tester\Assert::contains('<link rel="stylesheet" href="/build/styles3.css">', $output);
		Tester\Assert::contains('<link rel="stylesheet" href="/build/styles4.css">', $output);

		$output = $this->latte->renderToString(__DIR__ . '/../../files/templates/second.latte');

		Tester\Assert::contains('<script src="/build/file3.js"></script>', $output);
		Tester\Assert::contains('<script src="/build/other4.js"></script>', $output);
	}

	/**
	 * {@inheritdoc}
	 */
	public function testEntriesAreNotDuplicatedWhenAlreadyRenderedIntegration(): void
	{
		$this->latte->renderToString(__DIR__ . '/../../files/templates/first.latte');
		$output = $this->latte->renderToString(__DIR__ . '/../../files/templates/second.latte');

		Tester\Assert::contains('<script src="/build/file3.js"></script>', $output);
		Tester\Assert::notContains('<script src="/build/file1.js"></script>', $output);
		Tester\Assert::notContains('<link rel="stylesheet" href="/build/styles3.css">', $output);
		Tester\Assert::notContains('<link rel="stylesheet" href="/build/styles4.css">', $output);
	}
}

(new WebpackEncoreMacrosIntegrationTest())->run();
