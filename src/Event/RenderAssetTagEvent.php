<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Event;

final class RenderAssetTagEvent
{
	public const TYPE_SCRIPT = 'script';
	public const TYPE_LINK = 'link';

	private string $type;

	private string $url;

	/** @var array<string, mixed> */
	private array $attributes;

	/**
	 * @param array<string, mixed> $attributes
	 */
	public function __construct(string $type, string $url, array $attributes)
	{
		$this->type = $type;
		$this->url = $url;
		$this->attributes = $attributes;
	}

	public function isScriptTag(): bool
	{
		return self::TYPE_SCRIPT === $this->type;
	}

	public function isLinkTag(): bool
	{
		return self::TYPE_LINK === $this->type;
	}

	public function getUrl(): string
	{
		return $this->url;
	}

	/**
	 * @return array<string, mixed>
	 */
	public function getAttributes(): array
	{
		return $this->attributes;
	}

	/**
	 * @param mixed $value
	 */
	public function setAttribute(string $name, $value): void
	{
		$this->attributes[$name] = $value;
	}

	public function removeAttribute(string $name): void
	{
		unset($this->attributes[$name]);
	}
}
