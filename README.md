# kwtSMS - SMS Gateway for OpenCart 4.x

[![OpenCart](https://img.shields.io/badge/OpenCart-4.x-blue?logo=opencart)](https://www.opencart.com)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)](https://php.net)
[![License](https://img.shields.io/badge/License-GPL--3.0-green)](LICENSE)
[![kwtSMS](https://img.shields.io/badge/Gateway-kwtSMS-FFA200)](https://www.kwtsms.com)
[![Arabic](https://img.shields.io/badge/Language-EN%20%7C%20AR-orange)](https://www.kwtsms.com)
[![Free](https://img.shields.io/badge/Price-Free-brightgreen)]()

Free OpenCart 4.x extension that integrates the [kwtSMS](https://www.kwtsms.com) SMS gateway for order notifications, OTP verification, abandoned cart recovery, and admin alerts.

## Features

### Order Notifications
- **Customer SMS on status change**: configurable per-status, with per-status template overrides
- **Admin paid order alerts**: SMS when a new paid order arrives
- **Admin problem order alerts**: SMS on cancelled, refunded, or other problem statuses
- **Per-status custom templates**: different message for Shipped vs Processing vs Cancelled

### COD OTP Verification
- **Phone verification at checkout** for Cash on Delivery orders
- Prevents fake COD orders with one-time code verification
- Configurable code length, expiry, resend cooldown
- Rate limiting per phone and per IP
- Bootstrap 5 modal injected into checkout page

### Abandoned Cart Recovery
- **Cron-based detection** of abandoned carts
- Sends SMS reminder after configurable delay (default: 1 hour)
- One SMS per cart (dedup via cart hash, no spam)
- Configurable max sends per cron run

### Event Alerts
- **Customer welcome SMS** on new registration
- **Admin new customer alert**
- **Admin low stock alert** with global threshold, once-per-product logic
- **Admin new product review alert**
- **Admin return request alert**

### Core
- **6-tab admin UI**: Dashboard, Settings, Gateway, Templates, Logs, Help
- **Gateway login/logout/reload** with live API connection
- **Test Gateway**: send test SMS through the full pipeline
- **24 message templates**: EN + AR, editable with reset-to-default
- **SMS log**: full audit trail with filters, pagination, clear
- **Debug log**: internal flow tracing (normalize, verify, clean, send)
- **Dashboard**: SMS analytics (sent/failed/skipped) and system status
- **Daily cron sync**: balance, sender IDs, coverage
- **Phone normalization**: local numbers, Arabic digits, +/00 prefix stripping, default country code
- **Bulk SMS**: auto-batching for 200+ recipients with ERR013 backoff
- **Coverage check**: skips countries not in account coverage

## Requirements

- OpenCart 4.0.2.x or later
- PHP 8.0 or later
- ext-curl, ext-json

## Installation

1. Download the extension package
2. Go to OpenCart Admin > Extensions > Installer > Upload
3. Go to Extensions > Extensions > Modules > find "kwtSMS - SMS Gateway" > Install
4. Click Edit to configure

## Quick Start

1. **Gateway tab**: Enter your kwtSMS API credentials and click Login
2. **Settings tab**: Enable the extension, set admin phone numbers, configure triggers
3. **Templates tab**: Customize SMS message templates (EN + AR)
4. **Test**: Use the Test Gateway feature to verify everything works

## Placeholders

| Placeholder | Available In | Description |
|---|---|---|
| `{order_id}` | Order, Admin | Order number |
| `{customer_name}` | All | Customer full name |
| `{order_status}` | Order | Current order status |
| `{order_total}` | Order, Admin, Cart | Total with currency |
| `{store_name}` | All | Store name |
| `{date}` | All | Current date and time |
| `{customer_email}` | Customer, Admin | Customer email |
| `{customer_phone}` | Admin | Customer phone |
| `{product_name}` | Stock, Review, Return | Product name |
| `{product_model}` | Stock | Product model/SKU |
| `{product_quantity}` | Stock | Current stock level |
| `{stock_threshold}` | Stock | Configured threshold |
| `{rating}` | Review | Review rating (1-5) |
| `{return_reason}` | Return | Return reason |
| `{otp_code}` | OTP | Verification code |
| `{expiry_minutes}` | OTP | Code validity |
| `{products_summary}` | Cart | Product names in cart |

## API Endpoints Used

- `POST /API/send/` - Send SMS
- `POST /API/balance/` - Check balance
- `POST /API/senderid/` - List sender IDs
- `POST /API/coverage/` - List coverage

## Technical Details

- 18 PHP files + 2 Twig templates
- 7 database tables
- 7 OpenCart events
- 2 cron jobs (daily sync + abandoned cart)
- 24 SMS templates (EN + AR)

## Support

- [kwtSMS Support Center](https://www.kwtsms.com/support.html)
- [FAQ](https://www.kwtsms.com/faq/)
- [Sender ID Help](https://www.kwtsms.com/sender-id-help.html)
- [Developers](https://www.kwtsms.com/developers.html)

## License

GPL-3.0-or-later
