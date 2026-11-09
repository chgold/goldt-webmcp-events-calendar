=== Goldnat for The Events Calendar ===
Contributors: chgold
Tags: events, calendar, mcp, ai, agents
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.2.0
License: Proprietary
License URI: https://goldnat.ai/terms

The Events Calendar integration for Goldnat — exposes events, venues and organizers as read-only AI-agent tools via the Goldnat WebMCP Bridge.

== Description ==

Adds The Events Calendar tools (`events.*`) to the Goldnat WebMCP Bridge manifest so AI agents can list, search and inspect events, venues, organizers, categories and tags through the Servio protocol. Every read returns only items the connected user can read natively in WordPress.

This free edition is read-only. Creating, editing and deleting events, venues and organizers is in Goldnat for The Events Calendar — Pro.

Requires the free Goldnat WebMCP Bridge plugin plus the free The Events Calendar plugin.

== Changelog ==

= 1.2.0 =
* Breaking: write tools (createEvent, updateEvent, deleteEvent, createVenue) moved to Goldnat for The Events Calendar — Pro. Install Pro to keep them, with the same tool names.

= 1.1.0 =
* Nine new read tools: listPastEvents, getEventsByVenue, getEventsByOrganizer, getFeaturedEvents, getOrganizer, listEventCategories, listEventTags, searchVenues, searchOrganizers.

= 1.0.1 =
* Security: permission ceiling (SEC-00) for event, venue and organizer reads and per-post event writes.

= 1.0.0 =
* Initial release.
