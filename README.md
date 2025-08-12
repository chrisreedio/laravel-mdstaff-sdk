# MDStaff SDK for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/chrisreedio/laravel-mdstaff-sdk.svg?style=flat-square)](https://packagist.org/packages/chrisreedio/laravel-mdstaff-sdk)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/chrisreedio/laravel-mdstaff-sdk/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/chrisreedio/laravel-mdstaff-sdk/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/chrisreedio/laravel-mdstaff-sdk/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/chrisreedio/laravel-mdstaff-sdk/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/chrisreedio/laravel-mdstaff-sdk.svg?style=flat-square)](https://packagist.org/packages/chrisreedio/laravel-mdstaff-sdk)

A Laravel SDK for interacting with the MDStaff API. This package provides a simple and elegant way to perform queries and batch queries against the MDStaff API endpoints.

## Installation

You can install the package via composer:

```bash
composer require chrisreedio/laravel-mdstaff-sdk
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-mdstaff-sdk-config"
```

Configure your MDStaff API credentials in the published config file or via environment variables:

```env
MDSTAFF_API_URL=https://api.mdstaff.com
MDSTAFF_API_KEY=your-api-key-here
```

## Usage

### Basic Query

```php
// TODO: Add example
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Chris Reed](https://github.com/chrisreedio)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
