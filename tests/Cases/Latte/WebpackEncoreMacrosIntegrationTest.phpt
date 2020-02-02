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

		$container = SixtyEightPublishers\WebpackEncoreBundle\Tests\Helper\ContainerFactory::createContainer(
			__METHOD__,
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
		$dom = Tester\DomQuery::fromHtml($this->latte->renderToString(__DIR__ . '/../../files/templates/first.latte'));

		# default, my_entry
		Tester\Assert::true($dom->has('script[src="/build/file1.js"][integrity="sha384-Q86c+opr0lBUPWN28BLJFqmLhho+9ZcJpXHorQvX6mYDWJ24RQcdDarXFQYN8HLc"]'));
		Tester\Assert::true($dom->has('script[src="/build/file2.js"][integrity="sha384-ymG7OyjISWrOpH9jsGvajKMDEOP/mKJq8bHC0XdjQA6P8sg2nu+2RLQxcNNwE/3J"]'));
		Tester\Assert::true($dom->has('link[rel="stylesheet"][href="/build/styles.css"][integrity="sha384-4g+Zv0iELStVvA4/B27g4TQHUMwZttA5TEojjUyB8Gl5p7sarU4y+VTSGMrNab8n"]'));
		Tester\Assert::true($dom->has('link[rel="stylesheet"][href="/build/styles2.css"][integrity="sha384-hfZmq9+2oI5Cst4/F4YyS2tJAAYdGz7vqSMP8cJoa8bVOr2kxNRLxSw6P8UZjwUn"]'));

		# different_build, third_entry
		Tester\Assert::true($dom->has('script[src="/build/other3.js"]'));
		Tester\Assert::true($dom->has('link[rel="stylesheet"][href="/build/styles3.css"]'));
		Tester\Assert::true($dom->has('link[rel="stylesheet"][href="/build/styles4.css"]'));

		$dom = Tester\DomQuery::fromHtml($this->latte->renderToString(__DIR__ . '/../../files/templates/second.latte'));

		# default, other_entry
		Tester\Assert::true($dom->has('script[src="/build/file3.js"]'));

		# different_build, next_entry
		Tester\Assert::true($dom->has('script[src="/build/other4.js"]'));
	}

	/**
	 * {@inheritdoc}
	 */
	public function testEntriesAreNotDuplicatedWhenAlreadyRenderedIntegration(): void
	{
		$this->latte->renderToString(__DIR__ . '/../../files/templates/first.latte');
		$dom = Tester\DomQuery::fromHtml($this->latte->renderToString(__DIR__ . '/../../files/templates/second.latte'));

		Tester\Assert::true($dom->has('script[src="/build/file3.js"]'));
		Tester\Assert::true($dom->has('script[src="/build/other4.js"]'));
		Tester\Assert::false($dom->has('script[src="/build/file1.js"]'));
		Tester\Assert::false($dom->has('link[rel="stylesheet"][src="/build/styles3.css"]'));
		Tester\Assert::false($dom->has('link[rel="stylesheet"][src="/build/styles4.css"]'));
	}
}

(new WebpackEncoreMacrosIntegrationTest())->run();
