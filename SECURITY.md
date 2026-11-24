# Security Policy

## Reporting a vulnerability

Please report security issues privately by email to **support@wsign.io** with the subject
"Security: wsign-php". Do not open a public GitHub issue, pull request or discussion for a vulnerability.

Include a description of the issue, the affected version, steps to reproduce and, if possible, a proof of
concept. We acknowledge reports within three business days and keep you informed until the issue is fixed.

## Supported versions

| Version | Supported |
|---|---|
| 1.x | Yes |

## Handling secrets

- Keep secret keys (`sk_…`) and webhook secrets (`whsec_…`) on your server, for example in environment
  variables. Never commit them or send them to a browser.
- Rotate a key immediately from the developer portal at <https://developers.wsign.io> if it may have leaked.
- Always verify webhook signatures with `Wsign\Webhook::constructEvent()` before trusting a payload.
