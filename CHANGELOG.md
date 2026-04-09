# Changelog

## [1.1.0] - 2026-04-09

### Added
- Per-status custom SMS templates with fallback to default
- Dedicated templates database table (replaces oc_setting storage)
- Reset-to-default button per template
- Customer welcome SMS on new registration
- Admin new customer registration alert
- Admin low stock alert (global threshold, once-per-product, restock reset)
- Admin new product review alert
- Admin return request alert
- COD OTP verification at checkout (phone verification for Cash on Delivery)
- OTP rate limiting per phone and per IP
- OTP configurable: code length, expiry, max attempts, resend cooldown
- OTP on customer login (2FA via SMS after password check)
- OTP on customer registration (verify phone before account creation)
- Abandoned cart SMS recovery (cron-based detection with configurable delay)
- Bulk SMS campaigns tab (send to all customers, customer group, or custom numbers)
- Campaign preview with recipient count and credit estimate
- Campaign history log with pagination
- Settings: event toggles for customer and admin notifications
- Settings: low stock threshold, abandoned cart delay, OTP configuration
- Country code dropdown populated from coverage API
- Sensible defaults on fresh install (extension on, statuses pre-selected)

### Changed
- Test gateway now uses main send() flow (supports multiple numbers, full pipeline)
- Test gateway message auto-fills with timestamp
- Dashboard status indicators enlarged for better visibility
- Admin paid/problem status toggles show only relevant statuses
- Sender ID dropdown only shows API data when gateway is connected
- Tab order: Dashboard, Campaigns, Settings, Gateway, Templates, Logs, Help
- Renamed "Enabled" dashboard indicator to "SMS Module" showing Enabled/Disabled

### Fixed
- Twig URL escaping in JavaScript context (decodeUrl helper)
- OpenCart autoloader namespace convention (Kwtsms not KwtSMS)
- Local phone numbers misidentified as foreign country codes
- Cron addCron() signature (5 parameters in OpenCart 4.0.2.x)
- Sender ID dropdown not populating from gateway data on page load
- FAQ link updated to /faq/
- Removed API documentation PDF link

## [1.0.0] - 2026-04-05

### Added
- Initial release
- SMS notifications on order status changes (customer + admin)
- Gateway login/logout/reload with kwtSMS API
- Test gateway feature (send test SMS with test=1)
- Message templates with English and Arabic support
- Auto-select template language based on order language (AR/EN fallback)
- SMS log with full audit trail (recipient, status, msg-id, points, API response)
- Debug log with toggle (normalize, verify, clean, send flow)
- Dashboard with SMS analytics (today, 7 days, 30 days) and system status
- Admin phone numbers (comma-separated, multiple recipients)
- Order status toggles for customer notifications, paid orders, problem statuses
- Phone normalization: Arabic digits, local numbers, default country code (965)
- Coverage check: skip numbers for countries not in account coverage
- Message cleaning: strip emoji, hidden chars, HTML tags
- Deduplication for multi-number sends
- Bulk SMS batching (200 per request, 0.2s delay)
- ERR013 (queue full) retry with exponential backoff (30s, 60s, 120s)
- Balance tracking from API response (balance-after on every send)
- Daily cron sync for balance, sender IDs, and coverage
- Help tab with setup guide and external links
- OTP attempts table (schema only, for future phase)
