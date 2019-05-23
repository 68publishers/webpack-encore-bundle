<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\EntryPoint;

use Nette;
use SixtyEightPublishers;

final class EntryPointLookup implements IEntryPointLookup, IIntegrityDataProvider
{
	use Nette\SmartObject;

	/** @var string  */
	private $buildName;

	/** @var string  */
	private $entryPointJsonPath;

	/** @var \Nette\Caching\Cache|NULL  */
	private $cache;

	/** @var NULL|array */
	private $entriesData;

	/** @var array  */
	private $returnedFiles = [];

	/**
	 * @param string                    $buildName
	 * @param string                    $entryPointJsonPath
	 * @param \Nette\Caching\Cache|NULL $cache
	 */
	public function __construct(string $buildName, string $entryPointJsonPath, ?Nette\Caching\Cache $cache = NULL)
	{
		$this->buildName = $buildName;
		$this->entryPointJsonPath = $entryPointJsonPath;
		$this->cache = $cache;
	}

	/**
	 * @param string $entryName
	 * @param string $key
	 *
	 * @return array
	 */
	private function getEntryFiles(string $entryName, string $key): array
	{
		$entryData = $this->getEntryData($entryName);

		if (!isset($entryData[$key])) {
			return [];
		}

		$newFiles = array_values(array_diff($entryData[$key], $this->returnedFiles));
		$this->returnedFiles = array_merge($this->returnedFiles, $newFiles);

		return $newFiles;
	}

	/**
	 * @param string $entryName
	 *
	 * @return array
	 * @throws \SixtyEightPublishers\WebpackEncoreBundle\Exception\EntryPointNotFoundException
	 */
	private function getEntryData(string $entryName): array
	{
		$entriesData = $this->getEntriesData();

		if (isset($entriesData['entrypoints'][$entryName])) {
			return $entriesData['entrypoints'][$entryName];
		}

		$withoutExtension = substr($entryName, 0, (int) strrpos($entryName, '.'));

		if (!empty($withoutExtension) && isset($entriesData['entrypoints'][$withoutExtension])) {
			throw new SixtyEightPublishers\WebpackEncoreBundle\Exception\EntryPointNotFoundException(sprintf(
				'Could not find the entry "%s". Try "%s" instead (without the extension).',
				$entryName,
				$withoutExtension
			));
		}

		throw new SixtyEightPublishers\WebpackEncoreBundle\Exception\EntryPointNotFoundException(sprintf(
			'Could not find the entry "%s" in "%s". Found: %s.',
			$entryName,
			$this->entryPointJsonPath,
			implode(', ', array_keys($entriesData['entrypoints']))
		));
	}

	/**
	 * @return array
	 * @throws \SixtyEightPublishers\WebpackEncoreBundle\Exception\InvalidStateException
	 */
	private function getEntriesData(): array
	{
		if (NULL !== $this->entriesData) {
			return $this->entriesData;
		}

		if (NULL !== $this->cache && is_array($entriesData = $this->cache->load($this->getBuildName()))) {
			return $this->entriesData = $entriesData;
		}

		if (!file_exists($this->entryPointJsonPath)) {
			throw new SixtyEightPublishers\WebpackEncoreBundle\Exception\InvalidStateException(sprintf(
				'Could not find the entrypoints file from Webpack: the file "%s" does not exist.',
				$this->entryPointJsonPath
			));
		}

		$this->entriesData = Nette\Utils\Json::decode(file_get_contents($this->entryPointJsonPath), Nette\Utils\Json::FORCE_ARRAY);

		if (!isset($this->entriesData['entrypoints'])) {
			throw new SixtyEightPublishers\WebpackEncoreBundle\Exception\InvalidStateException(sprintf(
				'Could not find an "entrypoints" key in the "%s" file',
				$this->entryPointJsonPath
			));
		}

		if (NULL !== $this->cache) {
			$this->cache->save($this->getBuildName(), $this->entriesData);
		}

		return $this->entriesData;
	}

	/*********************** interface \SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IEntryPointLookup ***********************/

	/**
	 * {@inheritdoc}
	 */
	public function getBuildName(): string
	{
		return $this->buildName;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getJsFiles(string $entryName): array
	{
		return $this->getEntryFiles($entryName, 'js');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getCssFiles(string $entryName): array
	{
		return $this->getEntryFiles($entryName, 'css');
	}

	/**
	 * {@inheritdoc}
	 */
	public function reset(): void
	{
		$this->returnedFiles = [];
	}

	/*********************** interface \SixtyEightPublishers\WebpackEncoreBundle\EntryPoint\IIntegrityDataProvider ***********************/

	/**
	 * {@inheritdoc}
	 */
	public function getIntegrityData(): array
	{
		$integrity = $this->getEntriesData()['integrity'] ?? [];

		return is_array($integrity) ? $integrity : [];
	}
}
