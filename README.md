# Nbox Shipping Module for Magento 2

Ultimate Shipping Solution: Compare Rates, Ship, and Deliver Seamlessly with Ease.

## Description

Simplify your shipping logistics! The NBOX Now Shipping Method module delivers an all-in-one solution for comparing rates across trusted carriers, managing shipments effortlessly, and providing transparency to your customers.

With live rate comparisons based on weight and dimensions, this module integrates smoothly into your Magento store, so your customers enjoy accurate shipping costs at checkout. Once orders are confirmed, we handle delivery coordination, letting you focus on sales while we ensure reliable, on-time shipments.

## Key Features

- Save up to 50% or more on your shipping costs with NBOX Now
- **End-to-End Shipping Control**: From comparing rates to coordinating delivery, everything is streamlined for you
- **Transparent, Real-Time Pricing**: Show customers accurate shipping costs based on product weight and dimensions—no hidden fees
- **Reliable Carrier Partnerships**: Enjoy peace of mind with trusted carriers ensuring timely deliveries
- **Seamless Integration**: Quick setup for immediate optimization
- **Customizable Margins**: Store owners can add custom margins for flexibility and profitability

## Requirements

- **Magento**: 2.4.x
- **PHP**: 8.1, 8.2, or 8.3
- **License**: OSL-3.0, AFL-3.0

## Installation

1. Download and install the `NBOX Now Shipping Method` module through Composer:
   ```bash
   composer require nbox/shipping
   ```

2. Enable the module:
   ```bash
   bin/magento module:enable Nbox_Shipping
   bin/magento setup:upgrade
   bin/magento setup:di:compile
   bin/magento cache:flush
   ```

3. Create an account with NBOX Now (if you don't have one)

4. Log in to the NBOX Now account from the Magento admin

5. Ensure your products have proper weight and dimensions configured

6. Activate the shipping method from the NBOX Now settings page in the admin panel

7. When an order is ready for shipping, go to the order details and select "Notify NBOX Now to ship order" from the Order actions section

## Configuration

1. Navigate to **Stores > Configuration > Sales > Shipping Methods**
2. Find the **NBOX Shipping** section
3. Configure your API credentials and shipping preferences
4. Set up custom margins if desired
5. Enable the shipping method for your store

## External Services

This module connects to the NBOX Now API to provide comprehensive shipping solutions, including shipping rate calculations, order processing, account activation, pickup and delivery service management, and order cancellation.

### NBOX Now API

**Service Purpose**:
- Account activation with NBOX Now
- Calculate shipping rates based on package details and destination address
- Process order details for shipping and fulfillment
- Manage pickup and delivery services
- Update the status of cancelled orders

**Data Transmitted**:
1. **Account activation**: Shop domain, user credentials
2. **Shipping rate calculation**: Package dimensions, weight, destination address
3. **Order processing**: Order details, product information, shipping address
4. **Pickup and delivery management**: Shop location, package details
5. **Order fulfillment**: Fulfillment data, order status updates, tracking information
6. **Cancelled orders**: Order ID, shop domain, cancellation reason

**External Links**:
- [NBOX Now Terms of Service](https://nbox.now/terms)
- [NBOX Now Privacy Policy](https://nbox.now/privacy)

## Support

For support, please visit: [https://nbox.qa/support/](https://nbox.qa/support/)

## Changelog

### 2.0.0

Major version release with enhanced production readiness:

- Migrated from development to production API endpoints
- Added comprehensive account management with login/logout functionality
- Improved security with automatic deactivation on logout
- Enhanced UI with conditional activation button states
- Fixed CORS issues by moving authentication server-side
- Streamlined order fulfillment process
- Enhanced shipping rate customization and display options
- Added ability to customize shipping rate margins and pricing flexibility
- Implemented professional logging system replacing error_log()
- Enhanced security with proper output escaping and data sanitization
- Added structured logging with context data (order IDs, carrier IDs)
- Logs now viewable in Magento admin with 'nbox-now' source
- Eliminated coding standard warnings for production readiness
- Better error handling and user feedback
- Code optimization and stability improvements

### 1.0.0

- Initial release
- Added real-time shipping rate calculation based on weight and dimensions
- Supports integration with multiple carriers for accurate shipping

## License

This module is licensed under the Open Software License (OSL-3.0) and Academic Free License (AFL-3.0).