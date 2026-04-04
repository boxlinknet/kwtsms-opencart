# kwtSMS - SMS Gateway for OpenCart 4.x

Free OpenCart 4.x extension that integrates the [kwtSMS](https://www.kwtsms.com) SMS gateway for order notifications.

## Features

- **Order SMS Notifications**: Automatically send SMS to customers when order status changes
- **Admin Alerts**: Receive SMS for new paid orders and problem statuses (cancelled, refunded)
- **Gateway Management**: Login/logout, reload balance, test SMS sending
- **Message Templates**: Customizable EN + AR templates with placeholder support
- **SMS & Debug Logging**: Full audit trail of all SMS attempts with debug mode
- **Dashboard**: SMS analytics (sent/failed/skipped) and system status at a glance
- **Daily Sync**: Cron job to keep balance, sender IDs, and coverage up to date
- **Phone Normalization**: Handles local numbers, Arabic digits, country code prepending
- **Bulk SMS**: Auto-batching for 200+ recipients with ERR013 backoff

## Requirements

- OpenCart 4.0.2.x or later
- PHP 8.0 or later
- ext-curl, ext-json

## Installation

1. Download the extension package
2. Go to OpenCart Admin > Extensions > Installer > Upload
3. Go to Extensions > Extensions > Modules > find "kwtSMS - SMS Gateway" > Install
4. Click Edit to configure

## Configuration

1. **Gateway tab**: Enter your kwtSMS API credentials and click Login
2. **Settings tab**: Enable the extension, set admin phone numbers, configure order status triggers
3. **Templates tab**: Customize SMS message templates
4. **Test**: Use the Test Gateway feature to send a test SMS

## Placeholders

Use these in message templates:

| Placeholder | Description |
|---|---|
| `{order_id}` | Order number |
| `{customer_name}` | Customer full name |
| `{order_status}` | Current order status name |
| `{order_total}` | Order total with currency |
| `{store_name}` | Store name |
| `{date}` | Current date and time |

## API Endpoints Used

- `POST /API/send/` - Send SMS
- `POST /API/balance/` - Check balance
- `POST /API/senderid/` - List sender IDs
- `POST /API/coverage/` - List coverage

## Support

- [kwtSMS Support Center](https://www.kwtsms.com/support.html)
- [FAQ](https://www.kwtsms.com/faq_all.php)
- [API Documentation](https://www.kwtsms.com/doc/KwtSMS.com_API_Documentation_v41.pdf)
- [Sender ID Help](https://www.kwtsms.com/sender-id-help.html)

## License

GPL-3.0-or-later
