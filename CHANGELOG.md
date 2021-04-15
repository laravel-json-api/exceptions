# Change Log

All notable changes to this project will be documented in this file. This project adheres to
[Semantic Versioning](http://semver.org/) and [this changelog format](http://keepachangelog.com/).

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
