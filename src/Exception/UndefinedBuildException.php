<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Exception;

use InvalidArgumentException;
use function sprintf;

final class UndefinedBuildException extends InvalidArgumentException
{
	public static function defaultBuildNotConfigured(): self
	{
		return new self('There is no default build configured: please pass an argument to getEntrypointLookup().');
	}

	public static function buildNotConfigured(string $buildName): self
	{
		return new self(sprintf(
			'The build "%s" is not configured',
			$buildName
		));
	}
}
