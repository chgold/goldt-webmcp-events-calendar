# Changelog

## 1.0.1 — 2026-11-09

- Security: permission ceiling (SEC-00) — listEvents / searchEvents only return events the user can read natively (`read_post`: published for everyone, draft/private/pending only when permitted), and getEvent denies non-readable events (previously drafts and private events were returned to any `edit_posts` token).

## 1.0.0

- Initial release.
