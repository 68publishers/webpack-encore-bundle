<h1 align="center">Nette integration with Webpack Encore</h1>

<p align="center">This package allows you to use the <code>splitEntryChunks()</code> feature from <a href="https://symfony.com/doc/current/frontend.html">Webpack Encore</a> by reading an <code>entrypoints.json</code> file and helping you render all of the dynamic <code>script</code> and <code>link</code> tags needed.</p>
<p align="center">Inspired by <a href="https://github.com/symfony/webpack-encore-bundle">symfony/webpack-encore-bundle</a></p>

<p align="center">
<a href="https://github.com/68publishers/webpack-encore-bundle/actions"><img alt="Checks" src="https://badgen.net/github/checks/68publishers/webpack-encore-bundle/master"></a>
<a href="https://coveralls.io/github/68publishers/webpack-encore-bundle?branch=master"><img alt="Coverage Status" src="https://coveralls.io/repos/github/68publishers/webpack-encore-bundle/badge.svg?branch=master"></a>
<a href="https://packagist.org/packages/68publishers/webpack-encore-bundle"><img alt="Total Downloads" src="https://badgen.net/packagist/dt/68publishers/webpack-encore-bundle"></a>
<a href="https://packagist.org/packages/68publishers/webpack-encore-bundle"><img alt="Latest Version" src="https://badgen.net/packagist/v/68publishers/webpack-encore-bundle"></a>
<a href="https://packagist.org/packages/68publishers/webpack-encore-bundle"><img alt="PHP Version" src="https://badgen.net/packagist/php/68publishers/webpack-encore-bundle"></a>
</p>

## Installation

The best way to install 68publishers/webpack-encore-bundle is using Composer:

```sh
$ composer require 68publishers/webpack-encore-bundle
```

the package requires integration with [symfony/asset](https://github.com/symfony/asset).
We recommend using of our package [68publishers/asset](https://github.com/68publishers/asset), but you can use your own integration.

## Configuration

First, register a compiler extension into DIC:

```neon
extensions:
	encore: SixtyEightPublishers\WebpackEncoreBundle\Bridge\Nette\DI\WebpackEncoreBundleExtension
```

### Minimal Configuration

```neon
encore:
	# The path where Encore is building the assets - i.e. Encore.setOutputPath()
	output_path: %wwwDir%/public/build
```

You must also set the manifest path for the Asset component.
If you are using [68publishers/asset](https://github.com/68publishers/asset), the configuration might look something like this:

```neon
asset:
	json_manifest_path: %wwwDir%/public/build/manifest.json
```

### Multiple builds

```neon
encore:
	output_path: null # or just omit this option
	builds:
		frontend: %wwwDir%/public/frontend/build
		another: %wwwDir%/public/another/build
```

### Default attributes

```neon
encore:
	# if using Encore.enableIntegrityHashes() and need the crossorigin attribute. Allowed values are NULL, 'anonymous' or 'use-credentials'. Default is NULL.
	crossorigin: anonymous

	# Set attributes that will be rendered on all script tags
	script_attributes:
		defer: yes
		referrerpolicy: origin

	# Set attributes that will be rendered on all link tags
	link_attributes:
		referrerpolicy: origin
```

### HTTP Preload

All scripts and styles will be preloaded via [HTTP2 header `Link`](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Link) if the option is enabled.

```neon
encore:
	preload: yes
```

### Entrypoints.json cache

The parsed content of the `entrypoints.json` file can be cached for faster loading.
It is necessary to have the application integrated with [`symfony/console`](https://github.com/symfony/console) for this feature to work, as the cache must be created manually using the console command.

```neon
encore:
	cache: yes # you can use %debugMode%
```

To generate the cache, run the following command:

```sh
$ bin/console encore:warmup-cache
```

The cache file will be generated as `%wwwDir%//cache/webpack_encore.cache.php`.

:exclamation: Cache must be regenerated when the entrypoints.json changes. Use the option in a production environment only and run the command within an application build.

### Strict mode

By default, if we want to render tags for an entry that is not defined in the `entrypoints.json`, the application throws an `EntryPointNotFoundException` exception.
You can disable this behaviour:

```neon
encore:
	strict_mode: no
```

## Usage in Latte templates

Script and Links tags should be rendered with macros `encore_js` and `encore_css`:

```latte
{*
    {encore_js string $entryName, ?string $packageName, ?string $entrypointName, array $extraAttributes = []}
    {encore_css string $entryName, ?string $packageName, ?string $entrypointName, array $extraAttributes = []}
*}

{block js}
    {include parent}

    {encore_js 'entry1'}
    {* if you are using multiple builds *}
    {encore_js, 'entry1', null, 'frontend'}
    {* if you want to pass extra attributes *}
    {encore_js 'entry1' null, null, ['async' => true]}
{/block}

{block stylesheets}
    {include parent}

    {encore_css 'entry1'}
    {* if you are using multiple builds *}
    {encore_css, 'entry1', null, 'frontend'}
    {* if you want to pass extra attributes *}
    {encore_css 'entry1' null, null, ['hreflang' => 'en']}
{/block}
```

If for some reason you need manual access to individual file names, you can use the following Latte functions:

```latte
{*
    {encore_js_files(string $entryName, ?string $entrypointName): array}
    {encore_css_files(string $entryName, ?string $entrypointName): array}
    {encore_entry_exists(string $entryName, ?string $entrypointName): bool}
*}

{foreach encore_js_files('entry1') as $file}
    {var $asset = asset($file)}
{/foreach}

{foreach encore_css_files('entry1') as $file}
    {var $asset = asset($file)}
{/foreach}

{* Render tags for entry `entry2` only if the entry exists (prevents an exception throwing in a strict mode) *}
{if encore_entry_exists('entry2')}
    {encore_js 'entry2'}
{/if}
```

## Events

If your application is integrated with [symfony/event-dispatcher](https://github.com/symfony/event-dispatcher), you can handle the event `RenderAssetTagEvent` that is called when a script or link tag is generated.

```php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use SixtyEightPublishers\WebpackEncoreBundle\Event\RenderAssetTagEvent;

final class ScriptNonceSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            RenderAssetTagEvent::class => 'onRenderAssetTag',
        ];
    }

    public function onRenderAssetTag(RenderAssetTagEvent $event): void
    {
        if ($event->isScriptTag()) {
            $event->setAttribute('nonce', 'lookup nonce');
        }
    }
}
```

## Contributing

Before opening a pull request, please check your changes using the following commands

```bash
$ make init # to pull and start all docker images

$ make cs.check
$ make stan
$ make tests.all
```
