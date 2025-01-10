<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Tests\Bridge\Nette\Application;

use Nette\Http\IResponse;

final class HttpResponse implements IResponse
{
	public string $cookieDomain = '';

	public string $cookiePath = '/';

	public bool $cookieSecure = FALSE;

	public bool $warnOnBuffer = TRUE;

	private int $code = self::S200_OK;

	private array $headers = [];

	public function setCode(int $code, ?string $reason = NULL): self
	{
		$this->code = $code;

		return $this;
	}

	public function getCode(): int
	{
		return $this->code;
	}

	public function setHeader(string $name, string $value): self
	{
		$this->headers[$name] = [$value];

		return $this;
	}

	public function addHeader(string $name, string $value): self
	{
		$this->headers[$name][] = $value;

		return $this;
	}

	public function setContentType(string $type, ?string $charset = NULL): self
	{
		return $this;
	}

	public function redirect(string $url, int $code = self::S302_FOUND): void
	{
	}

	public function setExpiration(?string $expire): self
	{
		return $this;
	}

	public function isSent(): bool
	{
		return FALSE;
	}

	public function getHeader(string $header): ?string
	{
		return isset($this->headers[$header]) ? $this->headers[$header][0] : NULL;
	}

	public function getHeaders(): array
	{
		return $this->headers;
	}

	public function setCookie(string $name, string $value, $expire, ?string $path = NULL, ?string $domain = NULL, ?bool $secure = NULL, ?bool $httpOnly = NULL): self
	{
		return $this;
	}

	public function deleteCookie(string $name, ?string $path = NULL, ?string $domain = NULL, ?bool $secure = NULL): void
	{
	}
}
