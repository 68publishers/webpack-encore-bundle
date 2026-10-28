# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [3.4.0] - 2026-10-28
### Added
- Added support for `symfony/asset` and `symfony/cache` v8.
- Added PHP 8.5 into the CI matrix.

### Changed
- Minimal PHP version is 8.3 (unchanged since 3.2.0).
- Updated PHPStan to v2.
- Updated GitHub actions (`actions/checkout@v4`).
- Dev dependencies `symfony/cache-contracts` and `symfony/event-dispatcher-contracts` allow v3, so the dev dependencies can be resolved again.

### Deprecated
- The option `preload` is deprecated and has no effect. The `Link` preload header is no longer sent and the option triggers `E_USER_DEPRECATED` (deprecation of the configuration schema) while the container is being compiled, whatever its value. The option will be removed in 4.0.
- The class `ApplicationResponseHandler` is deprecated and is no longer registered by the extension. It will be removed in 4.0.

### Fixed
- The entrypoint lookups are reset when the application shuts down, not only after an error. Previously they were reset after a response only with `preload` enabled, so a long-running process did not render the tags of already rendered entries again.
- Enabled `preload` no longer crashes applications with `nette/application` >= 3.2.10, where `TextResponse::$source` is readonly.
- README: the default value of `crossorigin`, the path of the entrypoints cache file and the return type of `getSubscribedEvents()` in the example.

## [3.3.0] - 2025-02-25
### Added
- Added support for PHP `8.4`.
- Added test coverage for PHP `8.4`.

### Changed
- Changed minimal required version for `symfony/asset:^7.0.3`.
- Changed minimal required version for `symfony/cache:^7.0.3`.
- Improved coding style and static analysis.

## [3.2.0] - 2025-01-10
### Changed
- Changed minimal PHP version to `8.3`.
- Dropped support for Latte `v2`.
- Dropped support for symfony components `v5` and `v6`.

## [3.1.0] - 2024-08-06
### Added
- Added support for `nette/utils:^4.0`.
- Added support for `symfony/asset:^7.0` and `symfony/cache:^7.0`.

## [3.0.1] - 2022-11-29
### Fixed
- Fixed assets preloading via HTTP2 header `Link`.
- Fixed the header of the `README`.

### Changed
- `Makefile` is excluded from an export.
- Added more tests in `ApplicationResponseHandlerTest`.

## [3.0.0] - 2022-11-29
### Added
- Added support for PHP versions between 7.4 and 8.2.
- Added support for Latte versions between `2.5` and `3.x`.
- Added Dockerfile and docker-compose.yml with containers for all supported PHP versions, Makefile, GitHub actions, PHPStan and more tests.

### Changed
- The package is completely refactored and a lot of features have been added. There are not many changes in the configuration, please follow the instructions in the [README](https://github.com/68publishers/webpack-encore-bundle/blob/v3.0.0/README.md).
- Updated version of Symfony dependencies to `^5.4 || ^6.0`.

### Removed
- Dropped support for PHP < 7.4.

## [2.0] - 2020-10-27
### Changed
- Upgrade to Nette `3.x`.

## [1.1] - 2020-02-03
### Added
- Supported versions `^4.2` and `^5.0` of Symfony Asset Component.

### Changed
- Improved coding style and CI configuration.

### Removed
- Dropped support for PHP 7.1.

## [1.0] - 2019-06-03
### Added
- Initial release for Nette `^2.4`.

[Unreleased]: https://github.com/68publishers/webpack-encore-bundle/compare/v3.4.0...HEAD
[3.4.0]: https://github.com/68publishers/webpack-encore-bundle/compare/v3.3.0...v3.4.0
[3.3.0]: https://github.com/68publishers/webpack-encore-bundle/compare/v3.2.0...v3.3.0
[3.2.0]: https://github.com/68publishers/webpack-encore-bundle/compare/v3.1.0...v3.2.0
[3.1.0]: https://github.com/68publishers/webpack-encore-bundle/compare/v3.0.1...v3.1.0
[3.0.1]: https://github.com/68publishers/webpack-encore-bundle/compare/v3.0.0...v3.0.1
[3.0.0]: https://github.com/68publishers/webpack-encore-bundle/compare/v2.0...v3.0.0
[2.0]: https://github.com/68publishers/webpack-encore-bundle/compare/v1.1...v2.0
[1.1]: https://github.com/68publishers/webpack-encore-bundle/compare/v1.0...v1.1
[1.0]: https://github.com/68publishers/webpack-encore-bundle/releases/tag/v1.0
