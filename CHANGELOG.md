# Changelog

All notable changes to `laravel-mdstaff-sdk` will be documented in this file.

## v1.1.0 - 2026-10-05

### What's Changed

* Bump stefanzweifel/git-auto-commit-action from 5 to 7 by @dependabot[bot] in https://github.com/chrisreedio/laravel-mdstaff-sdk/pull/3
* Added email to demographics query.

**Full Changelog**: https://github.com/chrisreedio/laravel-mdstaff-sdk/compare/v1.0.0...v1.1.0

## v1.0.0 - 2026-09-30

### What's Changed

* Bump dependabot/fetch-metadata from 2.4.0 to 2.5.0 by @dependabot[bot] in https://github.com/chrisreedio/laravel-mdstaff-sdk/pull/5

### New Contributors

* @dependabot[bot] made their first contribution in https://github.com/chrisreedio/laravel-mdstaff-sdk/pull/5

**Full Changelog**: https://github.com/chrisreedio/laravel-mdstaff-sdk/commits/v1.0.0

## Unreleased

- Extracted the MDStaff connector, query/facility/provider-file requests, and query paginator from the Springfield Clinic website backend.
- Facility-scoped access tokens are resolved lazily per request and cached until shortly before expiry.
- Added `providers()` (search, find, findByNpi, records, latestImageFile, downloadImage, image), `lookUps()`, and `facilities()` resources.
- Added `ProviderQueries` presets, `QuerySource::primaryKey()`, the `ProviderImage` DTO, `RequestBudget`, and the `mdstaff:test` command.
- Configurable request rate limit, background request budget, and query cache TTL.
- Supports Laravel 11–13 and Saloon 3.10+ or 4.x.
