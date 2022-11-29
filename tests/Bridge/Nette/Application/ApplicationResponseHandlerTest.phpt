<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Tests\Bridge\Nette\Application;

use Mockery;
use Tester\Assert;
use Tester\TestCase;
use Nette\Application\Application;
use Nette\Http\IResponse as HttpResponse;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\Responses\TextResponse;
use Nette\Application\Response as ApplicationResponse;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\TagRenderer;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\EntryPointLookupInterface;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\EntryPointLookupCollectionInterface;
use SixtyEightPublishers\WebpackEncoreBundle\Bridge\Nette\Application\ApplicationResponseHandler;

require __DIR__ . '/../../../bootstrap.php';

final class ApplicationResponseHandlerTest extends TestCase
{
	public function testHandlerInstallation(): void
	{
		$application = Mockery::mock(Application::class);
		$request = Mockery::mock(HttpResponse::class);
		$tagRenderer = Mockery::mock(TagRenderer::class);
		$entrypointCollection = Mockery::mock(EntryPointLookupCollectionInterface::class);

		ApplicationResponseHandler::register($application, $request, $tagRenderer, $entrypointCollection, []);

		Assert::count(1, $application->onResponse ?? []);
		Assert::type(ApplicationResponseHandler::class, $application->onResponse[0]);
	}

	public function testInvokingHandlerOnTextResponseWithoutPreviousHeader(): void
	{
		$this->assertInvokingHandler(
			NULL,
			'<http://localhost:8080/build/file1.js>; rel="preload"; as="script",<http://localhost:8080/build/file2.js>; rel="preload"; as="script",<http://localhost:8080/build/file1.css>; rel="preload"; as="style",<http://localhost:8080/build/file2.css>; rel="preload"; as="style"',
			NULL,
			$this->createTextApplicationResponse()
		);
	}

	public function testInvokingHandlerOnJsonResponseWithoutPreviousHeader(): void
	{
		$this->assertInvokingHandler(
			NULL,
			'<http://localhost:8080/build/file1.js>; rel="preload"; as="script",<http://localhost:8080/build/file2.js>; rel="preload"; as="script",<http://localhost:8080/build/file1.css>; rel="preload"; as="style",<http://localhost:8080/build/file2.css>; rel="preload"; as="style"',
			NULL,
			new JsonResponse(['status' => 'ok'])
		);
	}

	public function testInvokingHandlerOnTextResponseWithPreviousHeaderAndCrossOrigin(): void
	{
		$this->assertInvokingHandler(
			'<http://localhost:8080/static.js>; rel="preload"; as="script",<http://localhost:8080/static.css>; rel="preload"; as="style"',
			'<http://localhost:8080/static.js>; rel="preload"; as="script",<http://localhost:8080/static.css>; rel="preload"; as="style",<http://localhost:8080/build/file1.js>; rel="preload"; as="script"; crossorigin="anonymous",<http://localhost:8080/build/file2.js>; rel="preload"; as="script"; crossorigin="anonymous",<http://localhost:8080/build/file1.css>; rel="preload"; as="style"; crossorigin="anonymous",<http://localhost:8080/build/file2.css>; rel="preload"; as="style"; crossorigin="anonymous"',
			'anonymous',
			$this->createTextApplicationResponse()
		);
	}

	public function testInvokingHandlerOnJsonResponseWithPreviousHeader(): void
	{
		$this->assertInvokingHandler(
			'<http://localhost:8080/static.js>; rel="preload"; as="script",<http://localhost:8080/static.css>; rel="preload"; as="style"',
			'<http://localhost:8080/static.js>; rel="preload"; as="script",<http://localhost:8080/static.css>; rel="preload"; as="style",<http://localhost:8080/build/file1.js>; rel="preload"; as="script",<http://localhost:8080/build/file2.js>; rel="preload"; as="script",<http://localhost:8080/build/file1.css>; rel="preload"; as="style",<http://localhost:8080/build/file2.css>; rel="preload"; as="style"',
			NULL,
			new JsonResponse(['status' => 'ok'])
		);
	}

	protected function tearDown(): void
	{
		Mockery::close();
	}

	private function assertInvokingHandler(?string $previousHeader, string $expectedHeader, ?string $crossOrigin, ApplicationResponse $applicationResponse): void
	{
		$entrypointLookup1 = Mockery::mock(EntryPointLookupInterface::class);
		$entrypointLookup2 = Mockery::mock(EntryPointLookupInterface::class);
		$entrypointCollection = Mockery::mock(EntryPointLookupCollectionInterface::class);
		$tagRenderer = Mockery::mock(TagRenderer::class);
		$response = Mockery::mock(HttpResponse::class);

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

		$tagRenderer->shouldReceive('getRenderedScripts')
			->once()
			->andReturn(['http://localhost:8080/build/file1.js', 'http://localhost:8080/build/file2.js']);

		$tagRenderer->shouldReceive('getRenderedStyles')
			->once()
			->andReturn(['http://localhost:8080/build/file1.css', 'http://localhost:8080/build/file2.css']);

		$tagRenderer->shouldReceive('getDefaultAttributes')
			->once()
			->andReturn($crossOrigin ? ['crossorigin' => $crossOrigin, 'nonce' => 'some_nonce'] : ['nonce' => 'some_nonce']);

		$response->shouldReceive('getHeader')
			->once()
			->with('Link')
			->andReturn($previousHeader);

		$response->shouldReceive('setHeader')
			->once()
			->with('Link', $expectedHeader)
			->andReturnSelf();

		$handler = new ApplicationResponseHandler($response, $tagRenderer, $entrypointCollection, ['_default', 'other_build']);

		$handler(Mockery::mock(Application::class), $applicationResponse);
	}

	private function createTextApplicationResponse(): ApplicationResponse
	{
		$template = Mockery::mock(Template::class);

		$template->shouldReceive('render')
			->once()
			->andReturnUndefined();

		return new TextResponse($template);
	}
}

(new ApplicationResponseHandlerTest())->run();
