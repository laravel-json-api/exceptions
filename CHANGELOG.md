# Change Log

All notable changes to this project will be documented in this file. This project adheres to
[Semantic Versioning](http://semver.org/) and [this changelog format](http://keepachangelog.com/).

## Unreleased

### Added

- Allow the exception parser to render responses for `application/json` using the parser's `acceptsJson()` method.
- Allow a developer to provide a callback to determine if an exception should be rendered as JSON:API via the
  parser's `accept()` method.

## [1.0.0-alpha.1] - 2021-01-25

Initial release.
