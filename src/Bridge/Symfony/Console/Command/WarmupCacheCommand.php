<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Bridge\Symfony\Console\Command;

use Psr\Cache\InvalidArgumentException;
use SixtyEightPublishers\WebpackEncoreBundle\Asset\EntryPointLookup;
use SixtyEightPublishers\WebpackEncoreBundle\Exception\EntryPointNotFoundException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use function array_map;
use function file_exists;
use function sprintf;
use function unserialize;

#[AsCommand(
    name: 'encore:warmup-cache',
    description: 'Dumps entrypoints data into PHP file for faster loading in production environment.',
)]
final class WarmupCacheCommand extends Command
{
    /** @var array<string, string> */
    private array $cacheKeys;

    private string $cacheFile;

    /**
     * @param array<string, string> $cacheKeys
     */
    public function __construct(array $cacheKeys, string $cacheFile)
    {
        parent::__construct();

        $this->cacheKeys = $cacheKeys;
        $this->cacheFile = $cacheFile;
    }

    /**
     * @throws Throwable|InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $arrayAdapter = new ArrayAdapter();

        foreach ($this->cacheKeys as $cacheKey => $path) {
            if (!file_exists($path)) {
                continue;
            }

            $entryPointLookup = new EntryPointLookup($path, $arrayAdapter, $cacheKey);

            try {
                $entryPointLookup->getJavaScriptFiles('dummy');
            } catch (EntryPointNotFoundException $e) {
                # ignore exception
            }
        }

        $values = array_map(static fn ($val) => null !== $val ? unserialize($val, ['allowed_classes' => false]) : null, $arrayAdapter->getValues());
        $phpArrayAdapter = new PhpArrayAdapter($this->cacheFile, new NullAdapter());

        $phpArrayAdapter->warmUp($values);
        $style->success(sprintf(
            'Entrypoints successfully dumped into "%s".',
            $this->cacheFile,
        ));

        return Command::SUCCESS;
    }
}
