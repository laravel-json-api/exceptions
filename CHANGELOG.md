# Change Log

All notable changes to this project will be documented in this file. This project adheres to
[Semantic Versioning](http://semver.org/) and [this changelog format](http://keepachangelog.com/).

## [1.1.0] - 2022-02-09

### Added

- Package now supports Laravel 9.
- Added support for PHP 8.1.
- Package now supports v2 of the `laravel-json-api/core` and `laravel-json-api/validation` dependencies.

## [1.0.0] - 2021-07-31

Initial stable release, with no changes from `1.0.0-beta.4`.

## [1.0.0-beta.4] - 2021-07-10

### Added

- When converting an exception to the default JSON:API error, detailed exception information will now be added to the
  JSON:API error if the application is running in debug mode. The exception code will be added to the `code` member, and
  the actual exception message to the `detail` member. The `meta` member will contain the exception class, file, line
  number and stack trace.

### Removed

- This package no longer handles the `UnexpectedDocumentException` from the `laravel-json-api/spec` package. The
  exception has been removed from that package, and it instead throws a `JsonApiException` if it cannot decode a JSON
  string.

## [1.0.0-beta.3] - 2021-04-26

### Added

- Multiple `accept*` helpers can now be added to the exception parser. This is useful when there are multiple different
  circumstances where you want to force the parser to render JSON:API errors - for example, if the client accepts JSON
  *or* if the current route has the `api` middleware. When multiple accept helpers are used, the parser will render
  JSON:API errors if *any* of the helpers return `true`.

## [1.0.0-beta.2] - 2021-04-15

### Added

- [#1](https://github.com/laravel-json-api/exceptions/pull/1) The `ExceptionParser` now has the `acceptsAll()`
  and `acceptsMiddleware()` helper methods, for determining whether JSON:API errors should be rendered.

## [1.0.0-beta.1] - 2021-03-30

Initial beta release, no changes since `alpha.2`.

## [1.0.0-alpha.2] - 2021-02-09

### Added

- Allow the exception parser to render responses for `application/json` using the parser's `acceptsJson()` method.
- Allow a developer to provide a callback to determine if an exception should be rendered as JSON:API via the
  parser's `accept()` method.

### Changed

- **BREAKING** Made method signatures on the `ExceptionParser` class consistent, so that they now take the exception as
  the first argument and the request as the second. (Previously some had these the other way round.) This makes the
  signatures consistent with the order of arguments that Laravel uses when calling render callbacks.

## [1.0.0-alpha.1] - 2021-01-25

Initial release.
