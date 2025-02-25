<?php

declare(strict_types=1);

namespace SixtyEightPublishers\WebpackEncoreBundle\Bridge\Nette\DI;

final class WebpackEncoreConfig
{
    public ?string $output_path;

    public string|false $crossorigin;

    public bool $preload;

    public bool $cache;

    public bool $strict_mode;

    /** @var array<string, string> */
    public array $builds;

    /** @var array<string, string|true> */
    public array $script_attributes;

    /** @var array<string, string|true> */
    public array $link_attributes;
}
