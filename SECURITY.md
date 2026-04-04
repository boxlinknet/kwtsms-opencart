# Security Policy

## Reporting a Vulnerability

If you discover a security vulnerability in this extension, please report it responsibly.

**Email**: support@kwtsms.com

Please include:
- Description of the vulnerability
- Steps to reproduce
- Impact assessment
- Suggested fix (if any)

We will acknowledge receipt within 48 hours and aim to release a fix within 7 days for critical issues.

## Security Practices

- API credentials are stored in OpenCart's settings database, never in files
- Credentials are never logged (masked in debug logs)
- All admin actions require permission checks and CSRF protection
- Input sanitization on all form fields
- SQL injection prevention via parameterized queries
- XSS prevention via Twig auto-escaping
- Phone numbers are stored unmasked in SMS logs for debugging but never exposed to the storefront
