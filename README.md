# WebpackEncoreBundle: Nette integration with Webpack Encore!

This package is inspired by [symfony/webpack-encore-bundle](https://github.com/symfony/webpack-encore-bundle).
This package allows you to use the `splitEntryChunks()` feature from [Webpack Encore](https://symfony.com/doc/current/frontend.html) by reading an `entrypoints.json` file and helping you render all of the dynamic `script` and `link` tags needed.

## Installation

The best way to install 68publishers/webpack-encore-bundle is using Composer:

```bash
composer require 68publishers/webpack-encore-bundle
```

This package requires integration with [symfony/asset](https://github.com/symfony/asset).
We recommend using of our package [68publishers/asset](https://github.com/68publishers/asset) but you can use your own integration.

then you can register extension into DIC:

```yaml
extensions:
    encore: SixtyEightPublishers\WebpackEncoreBundle\DI\WebpackEncoreBundleExtension
```

## Confiugration

```yaml
encore:
    # The path where Encore is building the assets - i.e. Encore.setOutputPath()
    output_path: %wwwDir%/public/build
    # If multiple builds are defined (as shown below), you can disable the default build with value NULL or omit this setting
    
    # if you have multiple builds:
    builds:
        # pass "frontend" as the 3rg arg to the Latte Macros
        # {encore_js 'entry1', null, 'frontend'}
        frontend: %wwwDir%/public/frontend/build
    
    # if using Encore.enableIntegrityHashes() and need the crossorigin attribute (default: NULL, or use 'anonymous' or 'use-credentials')
    crossorigin: 'anonymous'
    
    # if you want to cache entrypoints.json
    cache:
        enabled: yes # default is 'no'
        storage: @myStroage # default is @Nette\Caching\IStorage
    
    # if you want to change macro's names
    latte:
    jsAssetsMacroName: encore_js # default
    cssAssetsMacroName: encore_css # default
```

## Usage in Latte templates

```latte
{block javascripts }
    {include parent}

    {encore_js 'entry1'}
{/block}

{block stylesheets}
    {include parent}

    {encore_css 'entry1'}
{/block}
```

## Contributing

Before committing any changes, don't forget to run

```bash
vendor/bin/php-cs-fixer fix --config=.php_cs.dist -v --dry-run
```

and

```bash
vendor/bin/tester ./tests
```
