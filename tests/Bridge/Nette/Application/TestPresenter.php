<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Tests\Bridge\Nette\Application;

use Nette\Application\UI\Presenter;

final class TestPresenter extends Presenter
{
    protected function beforeRender(): void
    {
        $this->getTemplate()->setFile(__DIR__ . '/testPresenter.latte');
    }
}
