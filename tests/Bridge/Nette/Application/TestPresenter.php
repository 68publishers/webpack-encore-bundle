<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Tests\Bridge\Nette\Application;

use Nette\Application\Request;
use Nette\Application\Response;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Application\Responses\VoidResponse;
use function assert;

final class TestPresenter extends Presenter
{
	public function run(Request $request): Response
	{
		$template = $this->getTemplate()->setFile(__DIR__ . '/testPresenter.latte');
		assert($template instanceof Template);

		$template->renderToString();

		return new VoidResponse();
	}
}
