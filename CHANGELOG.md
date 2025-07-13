# Changelog

All notable changes to the Nbox Shipping module will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.0] - 2025-01-13

### Added
- Product SKU field included in all API requests for better product identification
- Enhanced product data formatting to include SKU in both shipping rate calculations and order processing
- Improved API integration with comprehensive product information

### Changed
- DataFormatter service now includes SKU in formatSingleProductFromQuoteItem method
- DataFormatter service now includes SKU in formatSingleProductFromOrderItem method

### Technical Details
- Updated Service/DataFormatter.php to add 'sku' field to product arrays
- SKU data is now available for both quote items (shipping calculations) and order items (checkout/fulfillment)

## [2.0.0] - Previous Release

### Added
- Payment status and method tracking to order API data
- Updated signup link for redirection after account registration
- PHP 8.4 compatibility improvements
- Comprehensive README documentation
- PHPStan error resolution and code standards compliance

### Fixed
- PHP 8.4 compatibility issue in ApiException constructor
- Code standards and deployment readiness improvements