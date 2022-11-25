<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Asset;

use Throwable;
use JsonException;
use InvalidArgumentException;
use Psr\Cache\CacheItemPoolInterface;
use SixtyEightPublishers\WebpackEncoreBundle\Exception\EntryPointNotFoundException;
use function substr;
use function sprintf;
use function strrpos;
use function array_diff;
use function array_merge;
use function file_exists;
use function json_decode;
use function array_values;
use function array_key_exists;
use function file_get_contents;

final class EntryPointLookup implements EntryPointLookupInterface, IntegrityDataProviderInterface
{
	private string $entrypointJsonPath;

	private ?CacheItemPoolInterface $cacheItemPool;

	private ?string $cacheKey;

	private bool $strictMode;

	/** @var array<mixed>|null */
	private ?array $entriesData = NULL;

	/** @var array<string> */
	private array $returnedFiles = [];

	public function __construct(string $entrypointJsonPath, ?CacheItemPoolInterface $cacheItemPool = NULL, ?string $cacheKey = NULL, bool $strictMode = TRUE)
	{
		$this->entrypointJsonPath = $entrypointJsonPath;
		$this->cacheItemPool = $cacheItemPool;
		$this->cacheKey = $cacheKey;
		$this->strictMode = $strictMode;
	}

	/**
	 * @throws Throwable|\Psr\Cache\InvalidArgumentException
	 */
	public function getJavaScriptFiles(string $entryName): array
	{
		return $this->getEntryFiles($entryName, 'js');
	}

	/**
	 * @throws Throwable|\Psr\Cache\InvalidArgumentException
	 */
	public function getCssFiles(string $entryName): array
	{
		return $this->getEntryFiles($entryName, 'css');
	}

	/**
	 * @throws Throwable|\Psr\Cache\InvalidArgumentException
	 */
	public function entryExists(string $entryName): bool
	{
		$entriesData = $this->getEntriesData();

		return isset($entriesData['entrypoints'][$entryName]);
	}

	public function reset(): void
	{
		$this->returnedFiles = [];
	}

	/**
	 * @throws Throwable|\Psr\Cache\InvalidArgumentException
	 */
	public function getIntegrityData(): array
	{
		$entriesData = $this->getEntriesData();

		if (!array_key_exists('integrity', $entriesData)) {
			return [];
		}

		return $entriesData['integrity'];
	}

	/**
	 * @return array<string>
	 * @throws Throwable|\Psr\Cache\InvalidArgumentException
	 */
	private function getEntryFiles(string $entryName, string $key): array
	{
		$this->validateEntryName($entryName);

		$entryData = $this->getEntriesData()['entrypoints'][$entryName] ?? [];

		if (!isset($entryData[$key])) {
			return [];
		}

		$entryFiles = $entryData[$key];
		$newFiles = array_values(array_diff($entryFiles, $this->returnedFiles));
		$this->returnedFiles = array_merge($this->returnedFiles, $newFiles);

		return $newFiles;
	}

	/**
	 * @throws Throwable|\Psr\Cache\InvalidArgumentException
	 */
	private function validateEntryName(string $entryName): void
	{
		$entriesData = $this->getEntriesData();

		if (isset($entriesData['entrypoints'][$entryName]) || !$this->strictMode) {
			return;
		}

		$dotPosition = strrpos($entryName, '.');
		$withoutExtension = FALSE !== $dotPosition ? substr($entryName, 0, $dotPosition) : NULL;

		throw NULL !== $withoutExtension && isset($entriesData['entrypoints'][$withoutExtension])
			? EntryPointNotFoundException::missingEntryWithSuggestion($entryName, $withoutExtension)
			: EntryPointNotFoundException::missingEntry($entryName, $this->entrypointJsonPath, array_keys($entriesData['entrypoints']));
	}

	/**
	 * @return array<mixed>
	 * @throws Throwable|\Psr\Cache\InvalidArgumentException
	 */
	private function getEntriesData(): array
	{
		if (NULL !== $this->entriesData) {
			return $this->entriesData;
		}

		if ($this->cacheItemPool) {
			$cached = $this->cacheItemPool->getItem($this->cacheKey);

			if ($cached->isHit()) {
				return $this->entriesData = $cached->get();
			}
		}

		if (!file_exists($this->entrypointJsonPath)) {
			if (!$this->strictMode) {
				return [];
			}

			throw new InvalidArgumentException(sprintf(
				'Could not find the entrypoints file from Webpack: the file "%s" does not exist.',
				$this->entrypointJsonPath
			));
		}

		try {
			$this->entriesData = json_decode((string) file_get_contents($this->entrypointJsonPath), TRUE, 512, JSON_THROW_ON_ERROR);
		} catch (JsonException $e) {
		}

		if (isset($e) || NULL === $this->entriesData) {
			throw new InvalidArgumentException(sprintf(
				'There was a problem JSON decoding the "%s" file.',
				$this->entrypointJsonPath
			));
		}

		if (!isset($this->entriesData['entrypoints'])) {
			throw new InvalidArgumentException(sprintf(
				'Could not find an "entrypoints" key in the "%s" file.',
				$this->entrypointJsonPath
			));
		}

		if (isset($cached)) {
			$this->cacheItemPool->save($cached->set($this->entriesData));
		}

		return $this->entriesData;
	}
}
