<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Bridge\Latte;

use SixtyEightPublishers\WebpackEncoreBundle\Asset\TagRenderer;

/**
 * @internal
 */
final class WebpackEncoreProviderSet
{
	private function __construct()
	{
	}

	/**
	 * @return array{webpackEncoreTagRenderer: TagRenderer}
	 */
	public static function providers(TagRenderer $tagRenderer): array
	{
		return [
			'webpackEncoreTagRenderer' => $tagRenderer,
		];
	}
}
