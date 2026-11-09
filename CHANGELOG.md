# Changelog

## 1.0.1 — 2026-11-09

- Security: permission ceiling (SEC-00) — listEvents / searchEvents only return events the user can read natively (`read_post`: published for everyone, draft/private/pending only when permitted), and getEvent denies non-readable events (previously drafts and private events were returned to any `edit_posts` token).
- Security: permission ceiling (SEC-00) — listVenues / listOrganizers omit non-readable items and getVenue denies them; updateEvent / deleteEvent require per-post `edit_post` / `delete_post`; createEvent / createVenue require the post type's own create + publish capabilities (replacing global `edit_others_posts` / `delete_others_posts`).

## 1.0.0

- Initial release.
