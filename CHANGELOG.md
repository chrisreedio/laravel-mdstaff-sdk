# Changelog

All notable changes to `laravel-mdstaff-sdk` will be documented in this file.

## Unreleased

- Extracted the MDStaff connector, query/facility/provider-file requests, and query paginator from the Springfield Clinic website backend.
- Facility-scoped access tokens are resolved lazily per request and cached until shortly before expiry.
- Added `providers()` (search, find, findByNpi, records, latestImageFile, downloadImage, image), `lookUps()`, and `facilities()` resources.
- Added `ProviderQueries` presets, `QuerySource::primaryKey()`, the `ProviderImage` DTO, `RequestBudget`, and the `mdstaff:test` command.
- Configurable request rate limit, background request budget, and query cache TTL.
- Supports Laravel 11–13 and Saloon 3.10+ or 4.x.
