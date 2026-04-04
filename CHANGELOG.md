# Changelog

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
