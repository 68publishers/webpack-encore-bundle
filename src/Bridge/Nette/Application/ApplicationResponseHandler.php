<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Bridge\Nette\Application;

use Throwable;
use ReflectionClass;
use Nette\Utils\Helpers;
use Nette\Application\Application;
use Nette\Application\UI\Template;
use Nette\Http\IResponse as HttpResponse;
use Nette\Application\Responses\TextResponse;
use Nette\Application\Response as ApplicationResponse;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\TagRenderer;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\EntryPointLookupCollectionInterface;
use function assert;
use function explode;
use function implode;
use function sprintf;
use function is_string;
use function array_merge;

final class ApplicationResponseHandler
{
	private HttpResponse $response;

	private TagRenderer $tagRenderer;

	private EntryPointLookupCollectionInterface $entrypointLookupCollection;

	/** @var array<string> */
	private array $buildNames;

	/**
	 * @param array<string> $buildNames
	 */
	public function __construct(HttpResponse $response, TagRenderer $tagRenderer, EntryPointLookupCollectionInterface $entrypointLookupCollection, array $buildNames)
	{
		$this->response = $response;
		$this->tagRenderer = $tagRenderer;
		$this->entrypointLookupCollection = $entrypointLookupCollection;
		$this->buildNames = $buildNames;
	}

	/**
	 * @param array<string> $buildNames
	 */
	public static function register(Application $application, HttpResponse $response, TagRenderer $tagRenderer, EntryPointLookupCollectionInterface $entryPointLookupCollection, array $buildNames): void
	{
		$application->onResponse[] = new self($response, $tagRenderer, $entryPointLookupCollection, $buildNames);
	}

	/**
	 * @throws Throwable
	 */
	public function __invoke(Application $application, ApplicationResponse $response): void
	{
		if (!$response instanceof TextResponse || !$response->getSource() instanceof Template) {
			$this->processLinks();

			return;
		}

		$source = $response->getSource();
		assert($source instanceof Template);
		$rendered = Helpers::capture(static fn () => $source->render());

		$reflection = new ReflectionClass($response);
		$property = $reflection->getProperty('source');

		$property->setAccessible(TRUE);
		$property->setValue($response, $rendered);

		$this->processLinks();
	}

	private function processLinks(): void
	{
		$defaultAttributes = $this->tagRenderer->getDefaultAttributes();
		$crossOrigin = $defaultAttributes['crossorigin'] ?? NULL;
		assert(is_string($crossOrigin) || NULL === $crossOrigin);
		$links = [];

		foreach ($this->tagRenderer->getRenderedScripts() as $src) {
			$links[] = $this->createLink($src, 'script', $crossOrigin);
		}

		foreach ($this->tagRenderer->getRenderedStyles() as $href) {
			$links[] = $this->createLink($href, 'style', $crossOrigin);
		}

		if (empty($links)) {
			return;
		}

		$header = $this->response->getHeader('Link') ?? '';
		$links = array_merge(!empty($header) ? explode(',', $header) : [], $links);

		$this->response->setHeader('Link', implode(',', $links));

		foreach ($this->buildNames as $buildName) {
			$this->entrypointLookupCollection->getEntrypointLookup($buildName)->reset();
		}
	}

	private function createLink(string $link, string $as, ?string $crossOrigin): string
	{
		$attributes = [
			'',
			'rel="preload"',
			sprintf('as="%s"', $as),
		];

		if (NULL !== $crossOrigin) {
			$attributes[] = sprintf('crossorigin="%s"', $crossOrigin);
		}

		return sprintf('<%s>%s', $link, implode('; ', $attributes));
	}
}
