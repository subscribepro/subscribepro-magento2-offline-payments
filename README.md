Subscribe Pro Magento 2 Offline Payments Support Extension
=============================================

This extension modifies the default Subscribe Pro Magento 2 extension by adding support for offline payment methods (e.g. "On Account").

To learn more about Subscribe Pro you can visit us at https://www.subscribepro.com/.

## Getting Started

Please visit our documentation website and start with our step by step integration guide for Magento 2: https://docs.subscribepro.com/display/spd/Install+Subscribe+Pro+for+Magento+2

## Installation

1. Create the following directory path in your Magento installation:

    app/code/Swarming/OfflinePayments

2. Copy the contents of this repository into that directory.
3. Run bin/magento module:enable Swarming_OfflinePayments
4. Run bin/magento setup:upgrade
5. Run bin/magento setup:di:compile
6. Run bin/magento cache:flush

## Setup

Navigate to Stores > Configuration > Swarming > Subscribe Pro and expand the Advanced section. Ensure your are on the correct store scope. Find and confirm the Allowed Offline Payment Methods setting is present.

## Usage

In the setting referenced during Setup, enter a comma-separated list of payment methods other than the Subscribe Pro payment method that should be available during subscription checkout. For example: checkmo

Run through checkout with a subscription order. In the Magento backend, under Sales > Orders, confirm that the order went through successfully with the selected payment method, and that a subscription ID is assigned to the subscription line item.

In the Subscribe Pro Merchant UI view the newly created subscription and confirm that the correct offline payment method code is stored on the subscription record.
