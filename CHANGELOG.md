# Changelog

## 1.2.0 — 2026-11-09

- **Breaking:** write tools moved to Goldnat for The Events Calendar — Pro (`goldt-webmcp-events-calendar-pro`):
  `events.createEvent`, `events.updateEvent`, `events.deleteEvent`, `events.createVenue`. The free edition is now
  read-only (16 read tools). Installing Pro restores the same tool names; Pro 1.0.0 also adds venue / organizer
  updates and deletes, category / tag assignment, featured image and featured flag.
- Removed the write-only helpers (`split_datetime`, `require_post_cap`, `require_create_published`); the date
  handling fix (site timezone instead of `gmdate()`) ships in Pro.

## 1.1.0 — 2026-11-09

- New read tools (scope `read`, Deep Research round #2): `events.listPastEvents` (ended before now, newest first; filters start_date / category / venue_id / organizer_id), `events.getEventsByVenue` and `events.getEventsByOrganizer` (date range + status filters), `events.getFeaturedEvents`, `events.getOrganizer`, `events.listEventCategories` (hierarchical `tribe_events_cat`), `events.listEventTags` (with published-event count), `events.searchVenues` (name / address / city / state / zip / country, optional near_lat + near_lng + radius_km for venues with coordinates) and `events.searchOrganizers` (name / email / phone / website).
- Permission ceiling (SEC-00) kept: every new list returns only events / venues / organizers the user can `read_post` natively; getEventsByVenue / getEventsByOrganizer / getOrganizer deny a non-readable venue or organizer (403).

## 1.0.1 — 2026-11-09

- Security: permission ceiling (SEC-00) — listEvents / searchEvents only return events the user can read natively (`read_post`: published for everyone, draft/private/pending only when permitted), and getEvent denies non-readable events (previously drafts and private events were returned to any `edit_posts` token).
- Security: permission ceiling (SEC-00) — listVenues / listOrganizers omit non-readable items and getVenue denies them; updateEvent / deleteEvent require per-post `edit_post` / `delete_post`; createEvent / createVenue require the post type's own create + publish capabilities (replacing global `edit_others_posts` / `delete_others_posts`).

## 1.0.0

- Initial release.
