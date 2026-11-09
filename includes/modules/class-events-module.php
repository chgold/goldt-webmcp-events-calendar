<?php
/**
 * The Events Calendar module — events, venues and organizers as AI tools.
 *
 * @package GoldtWebMCP\Events
 */

namespace GoldtWebMCP\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Events Calendar tools module (`events.*`).
 */
class Events_Module extends Module_Base {

	/**
	 * Module name — the tool-name prefix in the manifest.
	 *
	 * @var string
	 */
	protected $module_name = 'events';

	/**
	 * Register this module's tools.
	 *
	 * @return void
	 */
	protected function register_tools() {
		// ── Reads ──
		$this->register_tool(
			'listEvents',
			array(
				'description'    => 'List events, optionally within a date range.',
				'input_schema'   => array(
					'type'       => 'object',
					'properties' => array(
						'start_date' => array(
							'type'        => 'string',
							'description' => 'YYYY-MM-DD lower bound',
						),
						'end_date'   => array(
							'type'        => 'string',
							'description' => 'YYYY-MM-DD upper bound',
						),
						'limit'      => array( 'type' => 'integer' ),
					),
				),
				'required_scope' => 'read',
			)
		);
		$this->register_tool(
			'getUpcomingEvents',
			array(
				'description'    => 'List upcoming (future) events, soonest first.',
				'input_schema'   => array(
					'type'       => 'object',
					'properties' => array( 'limit' => array( 'type' => 'integer' ) ),
				),
				'required_scope' => 'read',
			)
		);
		$this->register_tool(
			'getEvent',
			array(
				'description'    => 'Get a single event with start/end dates, venue, organizer and cost.',
				'input_schema'   => array(
					'type'       => 'object',
					'required'   => array( 'event_id' ),
					'properties' => array( 'event_id' => array( 'type' => 'integer' ) ),
				),
				'required_scope' => 'read',
			)
		);
		$this->register_tool(
			'searchEvents',
			array(
				'description'    => 'Search events by keyword.',
				'input_schema'   => array(
					'type'       => 'object',
					'required'   => array( 'search' ),
					'properties' => array(
						'search' => array( 'type' => 'string' ),
						'limit'  => array( 'type' => 'integer' ),
					),
				),
				'required_scope' => 'read',
			)
		);
		$this->register_tool(
			'listVenues',
			array(
				'description'    => 'List event venues.',
				'input_schema'   => array(
					'type'       => 'object',
					'properties' => array( 'limit' => array( 'type' => 'integer' ) ),
				),
				'required_scope' => 'read',
			)
		);
		$this->register_tool(
			'getVenue',
			array(
				'description'    => 'Get a venue with its address.',
				'input_schema'   => array(
					'type'       => 'object',
					'required'   => array( 'venue_id' ),
					'properties' => array( 'venue_id' => array( 'type' => 'integer' ) ),
				),
				'required_scope' => 'read',
			)
		);
		$this->register_tool(
			'listOrganizers',
			array(
				'description'    => 'List event organizers.',
				'input_schema'   => array(
					'type'       => 'object',
					'properties' => array( 'limit' => array( 'type' => 'integer' ) ),
				),
				'required_scope' => 'read',
			)
		);

		$this->register_planned_read_tools();

		// ── Writes ──
		$this->register_tool(
			'createEvent',
			array(
				'description'    => 'Create a new event.',
				'input_schema'   => array(
					'type'       => 'object',
					'required'   => array( 'title', 'start_date', 'end_date' ),
					'properties' => array(
						'title'       => array( 'type' => 'string' ),
						'start_date'  => array(
							'type'        => 'string',
							'description' => 'YYYY-MM-DD HH:MM:SS',
						),
						'end_date'    => array(
							'type'        => 'string',
							'description' => 'YYYY-MM-DD HH:MM:SS',
						),
						'description' => array( 'type' => 'string' ),
						'venue_id'    => array( 'type' => 'integer' ),
						'cost'        => array( 'type' => 'string' ),
					),
				),
				'required_scope' => 'write',
			)
		);
		$this->register_tool(
			'updateEvent',
			array(
				'description'    => 'Update an event title / dates / cost.',
				'input_schema'   => array(
					'type'       => 'object',
					'required'   => array( 'event_id' ),
					'properties' => array(
						'event_id'   => array( 'type' => 'integer' ),
						'title'      => array( 'type' => 'string' ),
						'start_date' => array( 'type' => 'string' ),
						'end_date'   => array( 'type' => 'string' ),
						'cost'       => array( 'type' => 'string' ),
					),
				),
				'required_scope' => 'write',
			)
		);
		$this->register_tool(
			'deleteEvent',
			array(
				'description'    => 'Delete an event.',
				'input_schema'   => array(
					'type'       => 'object',
					'required'   => array( 'event_id' ),
					'properties' => array(
						'event_id' => array( 'type' => 'integer' ),
						'force'    => array( 'type' => 'boolean' ),
					),
				),
				'required_scope' => 'write',
			)
		);
		$this->register_tool(
			'createVenue',
			array(
				'description'    => 'Create an event venue.',
				'input_schema'   => array(
					'type'       => 'object',
					'required'   => array( 'name' ),
					'properties' => array(
						'name'    => array( 'type' => 'string' ),
						'address' => array( 'type' => 'string' ),
						'city'    => array( 'type' => 'string' ),
						'country' => array( 'type' => 'string' ),
					),
				),
				'required_scope' => 'write',
			)
		);
	}

	/**
	 * Read the optional `limit` parameter (absint), falling back to a default when empty.
	 *
	 * @param array $params   Validated tool input.
	 * @param int   $fallback Limit used when the parameter is missing or 0.
	 * @return int
	 */
	private function limit_param( $params, $fallback ) {
		$limit = \absint( $params['limit'] ?? $fallback );
		return $limit ? $limit : $fallback;
	}

	/**
	 * Deny unless the current user has a capability.
	 *
	 * @param string $cap Capability name.
	 * @return \WP_Error|null
	 */
	private function require_cap( $cap ) {
		if ( ! \current_user_can( $cap ) ) {
			return new \WP_Error( 'forbidden', "{$cap} capability required", array( 'status' => 403 ) );
		}
		return null;
	}

	/**
	 * Split a "Y-m-d H:i:s" (or "Y-m-d") datetime into the separate
	 * date / hour / minute args The Events Calendar's create/update API needs.
	 *
	 * @param string $prefix EventStart | EventEnd.
	 * @param string $value  Datetime string.
	 * @return array<string,string>
	 */
	private function split_datetime( $prefix, $value ) {
		$ts = \strtotime( $value );
		if ( ! $ts ) {
			return array();
		}
		return array(
			$prefix . 'Date'   => \gmdate( 'Y-m-d', $ts ),
			$prefix . 'Hour'   => \gmdate( 'H', $ts ),
			$prefix . 'Minute' => \gmdate( 'i', $ts ),
		);
	}

	/**
	 * Per-object permission check (SEC-00) using the plugin's denial envelope.
	 *
	 * @param string $cap Meta capability (edit_post / delete_post).
	 * @param int    $id  Post ID.
	 * @return \WP_Error|null
	 */
	private function require_post_cap( $cap, $id ) {
		if ( ! \current_user_can( $cap, $id ) ) {
			return new \WP_Error( 'forbidden', "{$cap} capability required for this item", array( 'status' => 403 ) );
		}
		return null;
	}

	/**
	 * Creating a published item of a TEC post type requires that type's own
	 * create_posts + publish_posts capabilities (SEC-00 — native WP rule).
	 *
	 * @param string $post_type tribe_events | tribe_venue.
	 * @return \WP_Error|null
	 */
	private function require_create_published( $post_type ) {
		$obj = \get_post_type_object( $post_type );
		if ( ! $obj ) {
			return new \WP_Error( 'not_available', 'Events API unavailable', array( 'status' => 501 ) );
		}
		foreach ( array( $obj->cap->create_posts, $obj->cap->publish_posts ) as $cap ) {
			$deny = $this->require_cap( $cap );
			if ( $deny ) {
				return $deny;
			}
		}
		return null;
	}

	/**
	 * Shape an event post for tool output.
	 *
	 * @param \WP_Post $post Event post.
	 * @return array
	 */
	private function format_event( $post ) {
		$id    = $post->ID;
		$start = function_exists( '\tribe_get_start_date' ) ? \tribe_get_start_date( $id, false, 'Y-m-d H:i:s' ) : \get_post_meta( $id, '_EventStartDate', true );
		$end   = function_exists( '\tribe_get_end_date' ) ? \tribe_get_end_date( $id, false, 'Y-m-d H:i:s' ) : \get_post_meta( $id, '_EventEndDate', true );
		return array(
			'event_id'     => (int) $id,
			'title'        => \get_the_title( $id ),
			'start_date'   => $start,
			'end_date'     => $end,
			'all_day'      => \get_post_meta( $id, '_EventAllDay', true ) === 'yes',
			'venue_id'     => (int) \get_post_meta( $id, '_EventVenueID', true ),
			'organizer_id' => (int) \get_post_meta( $id, '_EventOrganizerID', true ),
			'cost'         => \get_post_meta( $id, '_EventCost', true ),
			'url'          => \get_permalink( $id ),
		);
	}

	/**
	 * Run a paged event query and keep only events the token's user can read
	 * natively (SEC-00): published events for everyone, drafts / private /
	 * pending only when `read_post` on that id passes.
	 *
	 * @param callable $fetch Receives the page number, returns WP_Post[].
	 * @param int      $limit Max results.
	 * @return array Formatted events.
	 */
	private function readable_events( $fetch, $limit ) {
		$out  = array();
		$page = 1;
		do {
			$batch = (array) $fetch( $page );
			foreach ( $batch as $e ) {
				if ( ! \current_user_can( 'read_post', $e->ID ) ) {
					continue;
				}
				$out[] = $this->format_event( $e );
				if ( count( $out ) >= $limit ) {
					return $out;
				}
			}
			++$page;
			$fetched = count( $batch );
		} while ( $fetched === $limit && $page <= 20 );
		return $out;
	}

	/**
	 * Tool handler for `events.listEvents`.
	 *
	 * @param array $params Validated tool input.
	 * @return array|\WP_Error
	 */
	public function execute_listEvents( $params ) {
		$deny = $this->require_cap( 'edit_posts' );
		if ( $deny ) {
			return $deny;
		}
		$limit = $this->limit_param( $params, 25 );
		$args  = array(
			'posts_per_page' => $limit,
			'post_status'    => 'any',
		);
		if ( ! empty( $params['start_date'] ) ) {
			$args['start_date'] = \sanitize_text_field( $params['start_date'] );
		}
		if ( ! empty( $params['end_date'] ) ) {
			$args['end_date'] = \sanitize_text_field( $params['end_date'] );
		}
		$fetch = function ( $page ) use ( $args ) {
			return \tribe_get_events( array_merge( $args, array( 'paged' => $page ) ) );
		};
		$out   = $this->readable_events( $fetch, $limit );
		return $this->success_response(
			array(
				'events' => $out,
				'count'  => count( $out ),
			)
		);
	}

	/**
	 * Tool handler for `events.getUpcomingEvents`.
	 *
	 * @param array $params Validated tool input.
	 * @return array|\WP_Error
	 */
	public function execute_getUpcomingEvents( $params ) {
		$deny = $this->require_cap( 'edit_posts' );
		if ( $deny ) {
			return $deny;
		}
		$events = \tribe_get_events(
			array(
				'posts_per_page' => $this->limit_param( $params, 10 ),
				'start_date'     => \current_time( 'Y-m-d H:i:s' ),
				'orderby'        => 'event_date',
				'order'          => 'ASC',
				'post_status'    => 'publish',
			)
		);
		$out    = array();
		foreach ( (array) $events as $e ) {
			$out[] = $this->format_event( $e );
		}
		return $this->success_response(
			array(
				'events' => $out,
				'count'  => count( $out ),
			)
		);
	}

	/**
	 * Tool handler for `events.getEvent`.
	 *
	 * @param array $params Validated tool input.
	 * @return array|\WP_Error
	 */
	public function execute_getEvent( $params ) {
		$deny = $this->require_cap( 'edit_posts' );
		if ( $deny ) {
			return $deny;
		}
		$id   = \absint( $params['event_id'] ?? 0 );
		$post = $id ? \get_post( $id ) : null;
		if ( ! $post || 'tribe_events' !== $post->post_type ) {
			return new \WP_Error( 'event_not_found', 'Event not found', array( 'status' => 404 ) );
		}
		if ( ! \current_user_can( 'read_post', $id ) ) {
			return new \WP_Error( 'forbidden', 'read_post capability required for this event', array( 'status' => 403 ) );
		}
		$data                = $this->format_event( $post );
		$data['description'] = \wp_strip_all_tags( $post->post_content );
		if ( $data['venue_id'] ) {
			$data['venue'] = \get_the_title( $data['venue_id'] );
		}
		if ( $data['organizer_id'] ) {
			$data['organizer'] = \get_the_title( $data['organizer_id'] );
		}
		return $this->success_response( $data );
	}

	/**
	 * Tool handler for `events.searchEvents`.
	 *
	 * @param array $params Validated tool input.
	 * @return array|\WP_Error
	 */
	public function execute_searchEvents( $params ) {
		$deny = $this->require_cap( 'edit_posts' );
		if ( $deny ) {
			return $deny;
		}
		$limit  = $this->limit_param( $params, 20 );
		$search = \sanitize_text_field( $params['search'] ?? '' );
		$fetch  = function ( $page ) use ( $search, $limit ) {
			return \get_posts(
				array(
					'post_type'      => 'tribe_events',
					's'              => $search,
					'posts_per_page' => $limit,
					'paged'          => $page,
					'post_status'    => 'any',
				)
			);
		};
		$out    = $this->readable_events( $fetch, $limit );
		return $this->success_response(
			array(
				'events' => $out,
				'count'  => count( $out ),
			)
		);
	}

	/**
	 * Tool handler for `events.listVenues`.
	 *
	 * @param array $params Validated tool input.
	 * @return array|\WP_Error
	 */
	public function execute_listVenues( $params ) {
		$deny = $this->require_cap( 'edit_posts' );
		if ( $deny ) {
			return $deny;
		}
		$posts = \get_posts(
			array(
				'post_type'      => 'tribe_venue',
				'posts_per_page' => $this->limit_param( $params, 50 ),
				'post_status'    => 'any',
			)
		);
		$out   = array();
		foreach ( $posts as $v ) {
			if ( ! \current_user_can( 'read_post', $v->ID ) ) {
				continue;
			}
			$out[] = array(
				'venue_id' => (int) $v->ID,
				'name'     => \get_the_title( $v ),
				'city'     => \get_post_meta( $v->ID, '_VenueCity', true ),
			);
		}
		return $this->success_response(
			array(
				'venues' => $out,
				'count'  => count( $out ),
			)
		);
	}

	/**
	 * Tool handler for `events.getVenue`.
	 *
	 * @param array $params Validated tool input.
	 * @return array|\WP_Error
	 */
	public function execute_getVenue( $params ) {
		$deny = $this->require_cap( 'edit_posts' );
		if ( $deny ) {
			return $deny;
		}
		$id   = \absint( $params['venue_id'] ?? 0 );
		$post = $id ? \get_post( $id ) : null;
		if ( ! $post || 'tribe_venue' !== $post->post_type ) {
			return new \WP_Error( 'venue_not_found', 'Venue not found', array( 'status' => 404 ) );
		}
		if ( ! \current_user_can( 'read_post', $id ) ) {
			return new \WP_Error( 'forbidden', 'read_post capability required for this venue', array( 'status' => 403 ) );
		}
		return $this->success_response(
			array(
				'venue_id' => $id,
				'name'     => \get_the_title( $id ),
				'address'  => \get_post_meta( $id, '_VenueAddress', true ),
				'city'     => \get_post_meta( $id, '_VenueCity', true ),
				'country'  => \get_post_meta( $id, '_VenueCountry', true ),
				'zip'      => \get_post_meta( $id, '_VenueZip', true ),
				'phone'    => \get_post_meta( $id, '_VenuePhone', true ),
			)
		);
	}

	/**
	 * Tool handler for `events.listOrganizers`.
	 *
	 * @param array $params Validated tool input.
	 * @return array|\WP_Error
	 */
	public function execute_listOrganizers( $params ) {
		$deny = $this->require_cap( 'edit_posts' );
		if ( $deny ) {
			return $deny;
		}
		$posts = \get_posts(
			array(
				'post_type'      => 'tribe_organizer',
				'posts_per_page' => $this->limit_param( $params, 50 ),
				'post_status'    => 'any',
			)
		);
		$out   = array();
		foreach ( $posts as $o ) {
			if ( ! \current_user_can( 'read_post', $o->ID ) ) {
				continue;
			}
			$out[] = array(
				'organizer_id' => (int) $o->ID,
				'name'         => \get_the_title( $o ),
				'email'        => \get_post_meta( $o->ID, '_OrganizerEmail', true ),
			);
		}
		return $this->success_response(
			array(
				'organizers' => $out,
				'count'      => count( $out ),
			)
		);
	}

	/**
	 * Tool handler for `events.createEvent`.
	 *
	 * @param array $params Validated tool input.
	 * @return array|\WP_Error
	 */
	public function execute_createEvent( $params ) {
		$deny = $this->require_create_published( 'tribe_events' );
		if ( $deny ) {
			return $deny;
		}
		if ( ! function_exists( 'tribe_create_event' ) ) {
			return new \WP_Error( 'not_available', 'Events API unavailable', array( 'status' => 501 ) );
		}
		$args = array_merge(
			array(
				'post_title'   => \sanitize_text_field( $params['title'] ?? '' ),
				'post_status'  => 'publish',
				'post_content' => \wp_kses_post( $params['description'] ?? '' ),
			),
			$this->split_datetime( 'EventStart', $params['start_date'] ?? '' ),
			$this->split_datetime( 'EventEnd', $params['end_date'] ?? '' )
		);
		if ( isset( $params['venue_id'] ) ) {
			$args['EventVenueID'] = \absint( $params['venue_id'] );
		}
		if ( isset( $params['cost'] ) ) {
			$args['EventCost'] = \sanitize_text_field( $params['cost'] );
		}
		$id = \tribe_create_event( $args );
		if ( ! $id ) {
			return new \WP_Error( 'create_failed', 'Could not create event', array( 'status' => 500 ) );
		}
		return $this->success_response(
			array(
				'event_id' => (int) $id,
				'title'    => $args['post_title'],
			)
		);
	}

	/**
	 * Tool handler for `events.updateEvent`.
	 *
	 * @param array $params Validated tool input.
	 * @return array|\WP_Error
	 */
	public function execute_updateEvent( $params ) {
		$id   = \absint( $params['event_id'] ?? 0 );
		$post = $id ? \get_post( $id ) : null;
		if ( ! $post || 'tribe_events' !== $post->post_type ) {
			return new \WP_Error( 'event_not_found', 'Event not found', array( 'status' => 404 ) );
		}
		$deny = $this->require_post_cap( 'edit_post', $id );
		if ( $deny ) {
			return $deny;
		}
		$args = array();
		if ( isset( $params['title'] ) ) {
			$args['post_title'] = \sanitize_text_field( $params['title'] );
		}
		if ( isset( $params['start_date'] ) ) {
			$args = array_merge( $args, $this->split_datetime( 'EventStart', $params['start_date'] ) );
		}
		if ( isset( $params['end_date'] ) ) {
			$args = array_merge( $args, $this->split_datetime( 'EventEnd', $params['end_date'] ) );
		}
		if ( isset( $params['cost'] ) ) {
			$args['EventCost'] = \sanitize_text_field( $params['cost'] );
		}
		if ( function_exists( 'tribe_update_event' ) ) {
			\tribe_update_event( $id, $args );
		} elseif ( isset( $args['post_title'] ) ) {
				\wp_update_post(
					array(
						'ID'         => $id,
						'post_title' => $args['post_title'],
					)
				);
		}
		return $this->success_response(
			array(
				'event_id' => $id,
				'updated'  => true,
			)
		);
	}

	/**
	 * Tool handler for `events.deleteEvent`.
	 *
	 * @param array $params Validated tool input.
	 * @return array|\WP_Error
	 */
	public function execute_deleteEvent( $params ) {
		$id   = \absint( $params['event_id'] ?? 0 );
		$post = $id ? \get_post( $id ) : null;
		if ( ! $post || 'tribe_events' !== $post->post_type ) {
			return new \WP_Error( 'event_not_found', 'Event not found', array( 'status' => 404 ) );
		}
		$deny = $this->require_post_cap( 'delete_post', $id );
		if ( $deny ) {
			return $deny;
		}
		$force  = ! empty( $params['force'] );
		$result = $force ? \wp_delete_post( $id, true ) : \wp_trash_post( $id );
		return $this->success_response(
			array(
				'event_id' => $id,
				'deleted'  => (bool) $result,
				'force'    => $force,
			)
		);
	}

	/**
	 * Tool handler for `events.createVenue`.
	 *
	 * @param array $params Validated tool input.
	 * @return array|\WP_Error
	 */
	public function execute_createVenue( $params ) {
		$deny = $this->require_create_published( 'tribe_venue' );
		if ( $deny ) {
			return $deny;
		}
		if ( ! function_exists( 'tribe_create_venue' ) ) {
			return new \WP_Error( 'not_available', 'Events API unavailable', array( 'status' => 501 ) );
		}
		$id = \tribe_create_venue(
			array(
				'Venue'   => \sanitize_text_field( $params['name'] ?? '' ),
				'Address' => \sanitize_text_field( $params['address'] ?? '' ),
				'City'    => \sanitize_text_field( $params['city'] ?? '' ),
				'Country' => \sanitize_text_field( $params['country'] ?? '' ),
			)
		);
		if ( ! $id ) {
			return new \WP_Error( 'create_failed', 'Could not create venue', array( 'status' => 500 ) );
		}
		return $this->success_response(
			array(
				'venue_id' => (int) $id,
				'name'     => $params['name'] ?? '',
			)
		);
	}

	/**
	 * Shared input-schema fragments for the event-filter read tools.
	 *
	 * @return array<string,array>
	 */
	private function event_filter_props() {
		return array(
			'start_date' => array(
				'type'        => 'string',
				'description' => 'YYYY-MM-DD — only events that end on/after this date',
			),
			'end_date'   => array(
				'type'        => 'string',
				'description' => 'YYYY-MM-DD — only events that start on/before this date',
			),
			'status'     => array(
				'type'        => 'string',
				'description' => 'publish | future | draft | pending | private | any (default any; non-readable events are always omitted)',
			),
			'limit'      => array( 'type' => 'integer' ),
		);
	}

	/**
	 * Register the 1.1.0 read tools (Deep Research round #2).
	 *
	 * @return void
	 */
	private function register_planned_read_tools() {
		$filters = $this->event_filter_props();
		$this->register_tool(
			'listPastEvents',
			array(
				'description'    => 'List past events (ended before now), most recent first. Optional filters: start_date lower bound, category, venue_id, organizer_id.',
				'input_schema'   => array(
					'type'       => 'object',
					'properties' => array(
						'start_date'   => $filters['start_date'],
						'category'     => array(
							'type'        => 'string',
							'description' => 'Event category ID or slug',
						),
						'venue_id'     => array( 'type' => 'integer' ),
						'organizer_id' => array( 'type' => 'integer' ),
						'limit'        => array( 'type' => 'integer' ),
					),
				),
				'required_scope' => 'read',
			)
		);
		$this->register_tool(
			'getEventsByVenue',
			array(
				'description'    => 'List events at a venue, soonest first, with optional date range and status filters.',
				'input_schema'   => array(
					'type'       => 'object',
					'required'   => array( 'venue_id' ),
					'properties' => array_merge( array( 'venue_id' => array( 'type' => 'integer' ) ), $filters ),
				),
				'required_scope' => 'read',
			)
		);
		$this->register_tool(
			'getEventsByOrganizer',
			array(
				'description'    => 'List events run by an organizer, soonest first, with optional date range and status filters.',
				'input_schema'   => array(
					'type'       => 'object',
					'required'   => array( 'organizer_id' ),
					'properties' => array_merge( array( 'organizer_id' => array( 'type' => 'integer' ) ), $filters ),
				),
				'required_scope' => 'read',
			)
		);
		$this->register_tool(
			'getFeaturedEvents',
			array(
				'description'    => 'List events marked as featured, soonest first, with optional date range.',
				'input_schema'   => array(
					'type'       => 'object',
					'properties' => array(
						'start_date' => $filters['start_date'],
						'end_date'   => $filters['end_date'],
						'limit'      => array( 'type' => 'integer' ),
					),
				),
				'required_scope' => 'read',
			)
		);
		$this->register_tool(
			'getOrganizer',
			array(
				'description'    => 'Get an organizer with name, phone, email, website and description.',
				'input_schema'   => array(
					'type'       => 'object',
					'required'   => array( 'organizer_id' ),
					'properties' => array( 'organizer_id' => array( 'type' => 'integer' ) ),
				),
				'required_scope' => 'read',
			)
		);
		$this->register_tool(
			'listEventCategories',
			array(
				'description'    => 'List event categories (tribe_events_cat) with ID, name, slug, description, parent and event count.',
				'input_schema'   => array(
					'type'       => 'object',
					'properties' => array(
						'parent'     => array(
							'type'        => 'integer',
							'description' => 'Only direct children of this category ID (0 = top level)',
						),
						'search'     => array( 'type' => 'string' ),
						'hide_empty' => array( 'type' => 'boolean' ),
						'limit'      => array( 'type' => 'integer' ),
					),
				),
				'required_scope' => 'read',
			)
		);
		$this->register_tool(
			'listEventTags',
			array(
				'description'    => 'List tags (post_tag, shared with posts) with ID, name, slug, total count and published-event count.',
				'input_schema'   => array(
					'type'       => 'object',
					'properties' => array(
						'search'      => array( 'type' => 'string' ),
						'events_only' => array(
							'type'        => 'boolean',
							'description' => 'Only tags used by at least one published event',
						),
						'limit'       => array( 'type' => 'integer' ),
					),
				),
				'required_scope' => 'read',
			)
		);
		$this->register_tool(
			'searchVenues',
			array(
				'description'    => 'Search venues by name, address, city, state, zip or country. Optional near_lat/near_lng/radius_km filter for venues that have coordinates (adds distance_km).',
				'input_schema'   => array(
					'type'       => 'object',
					'properties' => array(
						'search'    => array( 'type' => 'string' ),
						'near_lat'  => array( 'type' => 'number' ),
						'near_lng'  => array( 'type' => 'number' ),
						'radius_km' => array(
							'type'        => 'number',
							'description' => 'Default 25 when near_lat/near_lng are given',
						),
						'limit'     => array( 'type' => 'integer' ),
					),
				),
				'required_scope' => 'read',
			)
		);
		$this->register_tool(
			'searchOrganizers',
			array(
				'description'    => 'Search organizers by name, email, phone or website. Returns contact details and published-event count.',
				'input_schema'   => array(
					'type'       => 'object',
					'required'   => array( 'search' ),
					'properties' => array(
						'search' => array( 'type' => 'string' ),
						'limit'  => array( 'type' => 'integer' ),
					),
				),
				'required_scope' => 'read',
			)
		);
	}

	/**
	 * Run a TEC ORM event query, keeping only events the user can read (SEC-00).
	 *
	 * @param array  $where ORM filters (key => value).
	 * @param string $order ASC | DESC by event start date.
	 * @param int    $limit Max results.
	 * @param string $status Post status filter (any = every non-trash status).
	 * @return array Formatted events.
	 */
	private function query_events( $where, $order, $limit, $status = 'any' ) {
		$fetch = function ( $page ) use ( $where, $order, $limit, $status ) {
			$q = \tribe_events()->where( 'status', $status );
			foreach ( $where as $key => $value ) {
				$q = $q->where( $key, $value );
			}
			return $q->order_by( 'event_date', $order )->per_page( $limit )->page( $page )->all();
		};
		return $this->readable_events( $fetch, $limit );
	}

	/**
	 * Validate the optional `status` parameter.
	 *
	 * @param array $params Tool input.
	 * @return string|\WP_Error
	 */
	private function status_param( $params ) {
		$status = \sanitize_key( $params['status'] ?? 'any' );
		$status = '' === $status ? 'any' : $status;
		if ( ! in_array( $status, array( 'any', 'publish', 'future', 'draft', 'pending', 'private' ), true ) ) {
			return new \WP_Error( 'invalid_status', 'status must be one of publish, future, draft, pending, private, any', array( 'status' => 400 ) );
		}
		return $status;
	}

	/**
	 * Translate start_date / end_date into overlap filters (event ends after start, starts before end).
	 *
	 * @param array $params Tool input.
	 * @return array ORM filters.
	 */
	private function date_filters( $params ) {
		$where = array();
		if ( ! empty( $params['start_date'] ) ) {
			$where['ends_after'] = \tribe_beginning_of_day( \sanitize_text_field( $params['start_date'] ) );
		}
		if ( ! empty( $params['end_date'] ) ) {
			$where['starts_before'] = \tribe_end_of_day( \sanitize_text_field( $params['end_date'] ) );
		}
		return $where;
	}

	/**
	 * Load a TEC linked post (venue / organizer) and enforce native read_post (SEC-00).
	 *
	 * @param int    $id        Post ID.
	 * @param string $post_type tribe_venue | tribe_organizer.
	 * @param string $label     venue | organizer.
	 * @return \WP_Post|\WP_Error
	 */
	private function readable_linked_post( $id, $post_type, $label ) {
		$post = $id ? \get_post( $id ) : null;
		if ( ! $post || $post_type !== $post->post_type ) {
			return new \WP_Error( $label . '_not_found', ucfirst( $label ) . ' not found', array( 'status' => 404 ) );
		}
		if ( ! \current_user_can( 'read_post', $id ) ) {
			return new \WP_Error( 'forbidden', 'read_post capability required for this ' . $label, array( 'status' => 403 ) );
		}
		return $post;
	}

	/**
	 * Count published events linked to a venue / organizer.
	 *
	 * @param string $key venue | organizer.
	 * @param int    $id  Linked post ID.
	 * @return int
	 */
	private function published_event_count( $key, $id ) {
		return (int) \tribe_events()->where( 'status', 'publish' )->where( $key, $id )->count();
	}

	/**
	 * Shape an organizer post for tool output.
	 *
	 * @param \WP_Post $post Organizer post.
	 * @return array
	 */
	private function format_organizer( $post ) {
		$id = $post->ID;
		return array(
			'organizer_id' => (int) $id,
			'name'         => \get_the_title( $id ),
			'phone'        => \get_post_meta( $id, '_OrganizerPhone', true ),
			'email'        => \get_post_meta( $id, '_OrganizerEmail', true ),
			'website'      => \get_post_meta( $id, '_OrganizerWebsite', true ),
			'status'       => $post->post_status,
			'url'          => \get_permalink( $id ),
		);
	}

	/**
	 * Shape a venue post for tool output.
	 *
	 * @param \WP_Post $post Venue post.
	 * @return array
	 */
	private function format_venue( $post ) {
		$id  = $post->ID;
		$lat = \get_post_meta( $id, '_VenueLat', true );
		$lng = \get_post_meta( $id, '_VenueLng', true );
		return array(
			'venue_id' => (int) $id,
			'name'     => \get_the_title( $id ),
			'address'  => \get_post_meta( $id, '_VenueAddress', true ),
			'city'     => \get_post_meta( $id, '_VenueCity', true ),
			'state'    => \get_post_meta( $id, '_VenueStateProvince', true ),
			'zip'      => \get_post_meta( $id, '_VenueZip', true ),
			'country'  => \get_post_meta( $id, '_VenueCountry', true ),
			'phone'    => \get_post_meta( $id, '_VenuePhone', true ),
			'lat'      => '' === $lat ? null : (float) $lat,
			'lng'      => '' === $lng ? null : (float) $lng,
		);
	}

	/**
	 * Search a TEC linked post type by title/content (`s`) plus meta fields, readable items only.
	 *
	 * @param string $post_type tribe_venue | tribe_organizer.
	 * @param string $search    Keyword ('' = all).
	 * @param array  $meta_keys Meta keys searched with LIKE.
	 * @param int    $cap       Max IDs per sub-query.
	 * @return \WP_Post[]
	 */
	private function search_linked_posts( $post_type, $search, $meta_keys, $cap ) {
		$base = array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'posts_per_page' => $cap,
			'fields'         => 'ids',
			'orderby'        => 'title',
			'order'          => 'ASC',
		);
		if ( '' === $search ) {
			$ids = \get_posts( $base );
		} else {
			$meta = array( 'relation' => 'OR' );
			foreach ( $meta_keys as $key ) {
				$meta[] = array(
					'key'     => $key,
					'value'   => $search,
					'compare' => 'LIKE',
				);
			}
			$ids = array_unique(
				array_merge(
					\get_posts( array_merge( $base, array( 's' => $search ) ) ),
					\get_posts( array_merge( $base, array( 'meta_query' => $meta ) ) ) // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- bounded by posts_per_page; WP API, no raw SQL.
				)
			);
		}
		$out = array();
		foreach ( $ids as $id ) {
			if ( \current_user_can( 'read_post', $id ) ) {
				$out[] = \get_post( $id );
			}
		}
		return $out;
	}

	/**
	 * Tool handler for `events.listPastEvents`.
	 *
	 * @param array $params Validated tool input.
	 * @return array|\WP_Error
	 */
	public function execute_listPastEvents( $params ) {
		$deny = $this->require_cap( 'edit_posts' );
		if ( $deny ) {
			return $deny;
		}
		$where = array( 'ends_before' => \current_time( 'Y-m-d H:i:s' ) );
		if ( ! empty( $params['start_date'] ) ) {
			$where['ends_after'] = \tribe_beginning_of_day( \sanitize_text_field( $params['start_date'] ) );
		}
		if ( ! empty( $params['category'] ) ) {
			$cat               = \sanitize_text_field( (string) $params['category'] );
			$where['category'] = is_numeric( $cat ) ? (int) $cat : $cat;
		}
		if ( ! empty( $params['venue_id'] ) ) {
			$where['venue'] = \absint( $params['venue_id'] );
		}
		if ( ! empty( $params['organizer_id'] ) ) {
			$where['organizer'] = \absint( $params['organizer_id'] );
		}
		$out = $this->query_events( $where, 'DESC', $this->limit_param( $params, 25 ) );
		return $this->success_response(
			array(
				'events' => $out,
				'count'  => count( $out ),
			)
		);
	}

	/**
	 * Shared handler for getEventsByVenue / getEventsByOrganizer.
	 *
	 * @param array  $params    Validated tool input.
	 * @param string $key       venue | organizer.
	 * @param string $post_type tribe_venue | tribe_organizer.
	 * @return array|\WP_Error
	 */
	private function events_by_linked( $params, $key, $post_type ) {
		$deny = $this->require_cap( 'edit_posts' );
		if ( $deny ) {
			return $deny;
		}
		$id     = \absint( $params[ $key . '_id' ] ?? 0 );
		$linked = $this->readable_linked_post( $id, $post_type, $key );
		if ( \is_wp_error( $linked ) ) {
			return $linked;
		}
		$status = $this->status_param( $params );
		if ( \is_wp_error( $status ) ) {
			return $status;
		}
		$where = array_merge( array( $key => $id ), $this->date_filters( $params ) );
		$out   = $this->query_events( $where, 'ASC', $this->limit_param( $params, 25 ), $status );
		return $this->success_response(
			array(
				$key . '_id' => $id,
				$key         => \get_the_title( $id ),
				'events'     => $out,
				'count'      => count( $out ),
			)
		);
	}

	/**
	 * Tool handler for `events.getEventsByVenue`.
	 *
	 * @param array $params Validated tool input.
	 * @return array|\WP_Error
	 */
	public function execute_getEventsByVenue( $params ) {
		return $this->events_by_linked( $params, 'venue', 'tribe_venue' );
	}

	/**
	 * Tool handler for `events.getEventsByOrganizer`.
	 *
	 * @param array $params Validated tool input.
	 * @return array|\WP_Error
	 */
	public function execute_getEventsByOrganizer( $params ) {
		return $this->events_by_linked( $params, 'organizer', 'tribe_organizer' );
	}

	/**
	 * Tool handler for `events.getFeaturedEvents`.
	 *
	 * @param array $params Validated tool input.
	 * @return array|\WP_Error
	 */
	public function execute_getFeaturedEvents( $params ) {
		$deny = $this->require_cap( 'edit_posts' );
		if ( $deny ) {
			return $deny;
		}
		$where = array_merge( array( 'featured' => true ), $this->date_filters( $params ) );
		$out   = $this->query_events( $where, 'ASC', $this->limit_param( $params, 25 ) );
		return $this->success_response(
			array(
				'events' => $out,
				'count'  => count( $out ),
			)
		);
	}

	/**
	 * Tool handler for `events.getOrganizer`.
	 *
	 * @param array $params Validated tool input.
	 * @return array|\WP_Error
	 */
	public function execute_getOrganizer( $params ) {
		$deny = $this->require_cap( 'edit_posts' );
		if ( $deny ) {
			return $deny;
		}
		$post = $this->readable_linked_post( \absint( $params['organizer_id'] ?? 0 ), 'tribe_organizer', 'organizer' );
		if ( \is_wp_error( $post ) ) {
			return $post;
		}
		$data                     = $this->format_organizer( $post );
		$data['description']      = \wp_strip_all_tags( $post->post_content );
		$data['published_events'] = $this->published_event_count( 'organizer', $post->ID );
		return $this->success_response( $data );
	}

	/**
	 * Tool handler for `events.listEventCategories`.
	 *
	 * @param array $params Validated tool input.
	 * @return array|\WP_Error
	 */
	public function execute_listEventCategories( $params ) {
		$deny = $this->require_cap( 'edit_posts' );
		if ( $deny ) {
			return $deny;
		}
		$args = array(
			'taxonomy'   => \Tribe__Events__Main::TAXONOMY,
			'hide_empty' => ! empty( $params['hide_empty'] ),
			'number'     => $this->limit_param( $params, 100 ),
			'orderby'    => 'name',
		);
		if ( isset( $params['parent'] ) ) {
			$args['parent'] = \absint( $params['parent'] );
		}
		if ( ! empty( $params['search'] ) ) {
			$args['search'] = \sanitize_text_field( $params['search'] );
		}
		$terms = \get_terms( $args );
		if ( \is_wp_error( $terms ) ) {
			return $terms;
		}
		$out = array();
		foreach ( $terms as $t ) {
			$out[] = array(
				'category_id' => (int) $t->term_id,
				'name'        => $t->name,
				'slug'        => $t->slug,
				'description' => $t->description,
				'parent'      => (int) $t->parent,
				'event_count' => (int) $t->count,
			);
		}
		return $this->success_response(
			array(
				'categories' => $out,
				'count'      => count( $out ),
			)
		);
	}

	/**
	 * Tool handler for `events.listEventTags`.
	 *
	 * @param array $params Validated tool input.
	 * @return array|\WP_Error
	 */
	public function execute_listEventTags( $params ) {
		$deny = $this->require_cap( 'edit_posts' );
		if ( $deny ) {
			return $deny;
		}
		$args = array(
			'taxonomy'   => 'post_tag',
			'hide_empty' => false,
			'number'     => $this->limit_param( $params, 100 ),
			'orderby'    => 'name',
		);
		if ( ! empty( $params['search'] ) ) {
			$args['search'] = \sanitize_text_field( $params['search'] );
		}
		$terms = \get_terms( $args );
		if ( \is_wp_error( $terms ) ) {
			return $terms;
		}
		$events_only = ! empty( $params['events_only'] );
		$out         = array();
		foreach ( $terms as $t ) {
			$event_count = (int) \tribe_events()->where( 'status', 'publish' )->where( 'tag', (int) $t->term_id )->count();
			if ( $events_only && ! $event_count ) {
				continue;
			}
			$out[] = array(
				'tag_id'      => (int) $t->term_id,
				'name'        => $t->name,
				'slug'        => $t->slug,
				'total_count' => (int) $t->count,
				'event_count' => $event_count,
			);
		}
		return $this->success_response(
			array(
				'tags'  => $out,
				'count' => count( $out ),
			)
		);
	}

	/**
	 * Tool handler for `events.searchVenues`.
	 *
	 * @param array $params Validated tool input.
	 * @return array|\WP_Error
	 */
	public function execute_searchVenues( $params ) {
		$deny = $this->require_cap( 'edit_posts' );
		if ( $deny ) {
			return $deny;
		}
		$limit  = $this->limit_param( $params, 25 );
		$search = \sanitize_text_field( $params['search'] ?? '' );
		$geo    = isset( $params['near_lat'], $params['near_lng'] );
		if ( '' === $search && ! $geo ) {
			return new \WP_Error( 'missing_parameter', 'Provide search and/or near_lat + near_lng', array( 'status' => 400 ) );
		}
		$lat    = $geo ? (float) $params['near_lat'] : 0.0;
		$lng    = $geo ? (float) $params['near_lng'] : 0.0;
		$radius = ! empty( $params['radius_km'] ) ? (float) $params['radius_km'] : 25.0;
		if ( $geo && ( abs( $lat ) > 90 || abs( $lng ) > 180 || $radius <= 0 ) ) {
			return new \WP_Error( 'invalid_coordinates', 'near_lat must be -90..90, near_lng -180..180, radius_km > 0', array( 'status' => 400 ) );
		}
		$keys  = array( '_VenueAddress', '_VenueCity', '_VenueStateProvince', '_VenueZip', '_VenueCountry' );
		$posts = $this->search_linked_posts( 'tribe_venue', $search, $keys, $geo ? 500 : $limit * 2 );
		$out   = array();
		foreach ( $posts as $p ) {
			$row = $this->format_venue( $p );
			if ( $geo ) {
				if ( null === $row['lat'] || null === $row['lng'] ) {
					continue;
				}
				$row['distance_km'] = $this->haversine_km( $lat, $lng, $row['lat'], $row['lng'] );
				if ( $row['distance_km'] > $radius ) {
					continue;
				}
			}
			$out[] = $row;
		}
		if ( $geo ) {
			usort(
				$out,
				function ( $a, $b ) {
					return $a['distance_km'] <=> $b['distance_km'];
				}
			);
		}
		$out = array_slice( $out, 0, $limit );
		return $this->success_response(
			array(
				'venues' => $out,
				'count'  => count( $out ),
			)
		);
	}

	/**
	 * Great-circle distance in kilometres.
	 *
	 * @param float $lat1 Latitude 1.
	 * @param float $lng1 Longitude 1.
	 * @param float $lat2 Latitude 2.
	 * @param float $lng2 Longitude 2.
	 * @return float
	 */
	private function haversine_km( $lat1, $lng1, $lat2, $lng2 ) {
		$d_lat = deg2rad( $lat2 - $lat1 );
		$d_lng = deg2rad( $lng2 - $lng1 );
		$a     = sin( $d_lat / 2 ) ** 2 + cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * sin( $d_lng / 2 ) ** 2;
		return round( 6371 * 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) ), 2 );
	}

	/**
	 * Tool handler for `events.searchOrganizers`.
	 *
	 * @param array $params Validated tool input.
	 * @return array|\WP_Error
	 */
	public function execute_searchOrganizers( $params ) {
		$deny = $this->require_cap( 'edit_posts' );
		if ( $deny ) {
			return $deny;
		}
		$limit  = $this->limit_param( $params, 25 );
		$search = \sanitize_text_field( $params['search'] ?? '' );
		if ( '' === $search ) {
			return new \WP_Error( 'missing_parameter', 'search must not be empty', array( 'status' => 400 ) );
		}
		$keys  = array( '_OrganizerEmail', '_OrganizerPhone', '_OrganizerWebsite' );
		$posts = array_slice( $this->search_linked_posts( 'tribe_organizer', $search, $keys, $limit * 2 ), 0, $limit );
		$out   = array();
		foreach ( $posts as $p ) {
			$row                     = $this->format_organizer( $p );
			$row['published_events'] = $this->published_event_count( 'organizer', $p->ID );
			$out[]                   = $row;
		}
		return $this->success_response(
			array(
				'organizers' => $out,
				'count'      => count( $out ),
			)
		);
	}
}
