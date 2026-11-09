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

class Events_Module extends Module_Base {

	protected $module_name = 'events';

	protected function register_tools() {
		// ── Reads ──
		$this->register_tool( 'listEvents', array(
			'description'  => 'List events, optionally within a date range.',
			'input_schema' => array( 'type' => 'object', 'properties' => array(
				'start_date' => array( 'type' => 'string', 'description' => 'YYYY-MM-DD lower bound' ),
				'end_date'   => array( 'type' => 'string', 'description' => 'YYYY-MM-DD upper bound' ),
				'limit'      => array( 'type' => 'integer' ),
			) ),
			'required_scope' => 'read',
		) );
		$this->register_tool( 'getUpcomingEvents', array(
			'description'  => 'List upcoming (future) events, soonest first.',
			'input_schema' => array( 'type' => 'object', 'properties' => array( 'limit' => array( 'type' => 'integer' ) ) ),
			'required_scope' => 'read',
		) );
		$this->register_tool( 'getEvent', array(
			'description'  => 'Get a single event with start/end dates, venue, organizer and cost.',
			'input_schema' => array( 'type' => 'object', 'required' => array( 'event_id' ), 'properties' => array( 'event_id' => array( 'type' => 'integer' ) ) ),
			'required_scope' => 'read',
		) );
		$this->register_tool( 'searchEvents', array(
			'description'  => 'Search events by keyword.',
			'input_schema' => array( 'type' => 'object', 'required' => array( 'search' ), 'properties' => array(
				'search' => array( 'type' => 'string' ), 'limit' => array( 'type' => 'integer' ),
			) ),
			'required_scope' => 'read',
		) );
		$this->register_tool( 'listVenues', array(
			'description'  => 'List event venues.',
			'input_schema' => array( 'type' => 'object', 'properties' => array( 'limit' => array( 'type' => 'integer' ) ) ),
			'required_scope' => 'read',
		) );
		$this->register_tool( 'getVenue', array(
			'description'  => 'Get a venue with its address.',
			'input_schema' => array( 'type' => 'object', 'required' => array( 'venue_id' ), 'properties' => array( 'venue_id' => array( 'type' => 'integer' ) ) ),
			'required_scope' => 'read',
		) );
		$this->register_tool( 'listOrganizers', array(
			'description'  => 'List event organizers.',
			'input_schema' => array( 'type' => 'object', 'properties' => array( 'limit' => array( 'type' => 'integer' ) ) ),
			'required_scope' => 'read',
		) );

		// ── Writes ──
		$this->register_tool( 'createEvent', array(
			'description'  => 'Create a new event.',
			'input_schema' => array( 'type' => 'object', 'required' => array( 'title', 'start_date', 'end_date' ), 'properties' => array(
				'title'      => array( 'type' => 'string' ),
				'start_date' => array( 'type' => 'string', 'description' => 'YYYY-MM-DD HH:MM:SS' ),
				'end_date'   => array( 'type' => 'string', 'description' => 'YYYY-MM-DD HH:MM:SS' ),
				'description' => array( 'type' => 'string' ),
				'venue_id'   => array( 'type' => 'integer' ),
				'cost'       => array( 'type' => 'string' ),
			) ),
			'required_scope' => 'write',
		) );
		$this->register_tool( 'updateEvent', array(
			'description'  => 'Update an event title / dates / cost.',
			'input_schema' => array( 'type' => 'object', 'required' => array( 'event_id' ), 'properties' => array(
				'event_id'   => array( 'type' => 'integer' ),
				'title'      => array( 'type' => 'string' ),
				'start_date' => array( 'type' => 'string' ),
				'end_date'   => array( 'type' => 'string' ),
				'cost'       => array( 'type' => 'string' ),
			) ),
			'required_scope' => 'write',
		) );
		$this->register_tool( 'deleteEvent', array(
			'description'  => 'Delete an event.',
			'input_schema' => array( 'type' => 'object', 'required' => array( 'event_id' ), 'properties' => array(
				'event_id' => array( 'type' => 'integer' ), 'force' => array( 'type' => 'boolean' ),
			) ),
			'required_scope' => 'write',
		) );
		$this->register_tool( 'createVenue', array(
			'description'  => 'Create an event venue.',
			'input_schema' => array( 'type' => 'object', 'required' => array( 'name' ), 'properties' => array(
				'name'    => array( 'type' => 'string' ),
				'address' => array( 'type' => 'string' ),
				'city'    => array( 'type' => 'string' ),
				'country' => array( 'type' => 'string' ),
			) ),
			'required_scope' => 'write',
		) );
	}

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
	 * @param string $prefix EventStart | EventEnd
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

	private function format_event( $post ) {
		$id = $post->ID;
		$start = function_exists( '\tribe_get_start_date' ) ? \tribe_get_start_date( $id, false, 'Y-m-d H:i:s' ) : \get_post_meta( $id, '_EventStartDate', true );
		$end   = function_exists( '\tribe_get_end_date' ) ? \tribe_get_end_date( $id, false, 'Y-m-d H:i:s' ) : \get_post_meta( $id, '_EventEndDate', true );
		return array(
			'event_id'   => (int) $id,
			'title'      => \get_the_title( $id ),
			'start_date' => $start,
			'end_date'   => $end,
			'all_day'    => \get_post_meta( $id, '_EventAllDay', true ) === 'yes',
			'venue_id'   => (int) \get_post_meta( $id, '_EventVenueID', true ),
			'organizer_id' => (int) \get_post_meta( $id, '_EventOrganizerID', true ),
			'cost'       => \get_post_meta( $id, '_EventCost', true ),
			'url'        => \get_permalink( $id ),
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

	public function execute_listEvents( $params ) {
		$deny = $this->require_cap( 'edit_posts' ); if ( $deny ) return $deny;
		$limit = \absint( $params['limit'] ?? 25 ) ?: 25;
		$args = array( 'posts_per_page' => $limit, 'post_status' => 'any' );
		if ( ! empty( $params['start_date'] ) ) $args['start_date'] = \sanitize_text_field( $params['start_date'] );
		if ( ! empty( $params['end_date'] ) ) $args['end_date'] = \sanitize_text_field( $params['end_date'] );
		$fetch = function ( $page ) use ( $args ) {
			return \tribe_get_events( array_merge( $args, array( 'paged' => $page ) ) );
		};
		$out   = $this->readable_events( $fetch, $limit );
		return $this->success_response( array( 'events' => $out, 'count' => count( $out ) ) );
	}

	public function execute_getUpcomingEvents( $params ) {
		$deny = $this->require_cap( 'edit_posts' ); if ( $deny ) return $deny;
		$events = \tribe_get_events( array(
			'posts_per_page' => \absint( $params['limit'] ?? 10 ) ?: 10,
			'start_date'     => \current_time( 'Y-m-d H:i:s' ),
			'orderby'        => 'event_date', 'order' => 'ASC', 'post_status' => 'publish',
		) );
		$out = array();
		foreach ( (array) $events as $e ) $out[] = $this->format_event( $e );
		return $this->success_response( array( 'events' => $out, 'count' => count( $out ) ) );
	}

	public function execute_getEvent( $params ) {
		$deny = $this->require_cap( 'edit_posts' ); if ( $deny ) return $deny;
		$id = \absint( $params['event_id'] ?? 0 );
		$post = $id ? \get_post( $id ) : null;
		if ( ! $post || $post->post_type !== 'tribe_events' ) return new \WP_Error( 'event_not_found', 'Event not found', array( 'status' => 404 ) );
		if ( ! \current_user_can( 'read_post', $id ) ) {
			return new \WP_Error( 'forbidden', 'read_post capability required for this event', array( 'status' => 403 ) );
		}
		$data = $this->format_event( $post );
		$data['description'] = \wp_strip_all_tags( $post->post_content );
		if ( $data['venue_id'] ) $data['venue'] = \get_the_title( $data['venue_id'] );
		if ( $data['organizer_id'] ) $data['organizer'] = \get_the_title( $data['organizer_id'] );
		return $this->success_response( $data );
	}

	public function execute_searchEvents( $params ) {
		$deny = $this->require_cap( 'edit_posts' ); if ( $deny ) return $deny;
		$limit  = \absint( $params['limit'] ?? 20 ) ?: 20;
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
		return $this->success_response( array( 'events' => $out, 'count' => count( $out ) ) );
	}

	public function execute_listVenues( $params ) {
		$deny = $this->require_cap( 'edit_posts' ); if ( $deny ) return $deny;
		$posts = \get_posts( array( 'post_type' => 'tribe_venue', 'posts_per_page' => \absint( $params['limit'] ?? 50 ) ?: 50, 'post_status' => 'any' ) );
		$out = array();
		foreach ( $posts as $v ) {
			if ( ! \current_user_can( 'read_post', $v->ID ) ) {
				continue;
			}
			$out[] = array( 'venue_id' => (int) $v->ID, 'name' => \get_the_title( $v ), 'city' => \get_post_meta( $v->ID, '_VenueCity', true ) );
		}
		return $this->success_response( array( 'venues' => $out, 'count' => count( $out ) ) );
	}

	public function execute_getVenue( $params ) {
		$deny = $this->require_cap( 'edit_posts' ); if ( $deny ) return $deny;
		$id = \absint( $params['venue_id'] ?? 0 );
		$post = $id ? \get_post( $id ) : null;
		if ( ! $post || $post->post_type !== 'tribe_venue' ) return new \WP_Error( 'venue_not_found', 'Venue not found', array( 'status' => 404 ) );
		if ( ! \current_user_can( 'read_post', $id ) ) {
			return new \WP_Error( 'forbidden', 'read_post capability required for this venue', array( 'status' => 403 ) );
		}
		return $this->success_response( array(
			'venue_id' => $id, 'name' => \get_the_title( $id ),
			'address'  => \get_post_meta( $id, '_VenueAddress', true ),
			'city'     => \get_post_meta( $id, '_VenueCity', true ),
			'country'  => \get_post_meta( $id, '_VenueCountry', true ),
			'zip'      => \get_post_meta( $id, '_VenueZip', true ),
			'phone'    => \get_post_meta( $id, '_VenuePhone', true ),
		) );
	}

	public function execute_listOrganizers( $params ) {
		$deny = $this->require_cap( 'edit_posts' ); if ( $deny ) return $deny;
		$posts = \get_posts( array( 'post_type' => 'tribe_organizer', 'posts_per_page' => \absint( $params['limit'] ?? 50 ) ?: 50, 'post_status' => 'any' ) );
		$out = array();
		foreach ( $posts as $o ) {
			if ( ! \current_user_can( 'read_post', $o->ID ) ) {
				continue;
			}
			$out[] = array( 'organizer_id' => (int) $o->ID, 'name' => \get_the_title( $o ), 'email' => \get_post_meta( $o->ID, '_OrganizerEmail', true ) );
		}
		return $this->success_response( array( 'organizers' => $out, 'count' => count( $out ) ) );
	}

	public function execute_createEvent( $params ) {
		$deny = $this->require_create_published( 'tribe_events' ); if ( $deny ) return $deny;
		if ( ! function_exists( 'tribe_create_event' ) ) return new \WP_Error( 'not_available', 'Events API unavailable', array( 'status' => 501 ) );
		$args = array_merge(
			array(
				'post_title'   => \sanitize_text_field( $params['title'] ?? '' ),
				'post_status'  => 'publish',
				'post_content' => \wp_kses_post( $params['description'] ?? '' ),
			),
			$this->split_datetime( 'EventStart', $params['start_date'] ?? '' ),
			$this->split_datetime( 'EventEnd', $params['end_date'] ?? '' )
		);
		if ( isset( $params['venue_id'] ) ) $args['EventVenueID'] = \absint( $params['venue_id'] );
		if ( isset( $params['cost'] ) ) $args['EventCost'] = \sanitize_text_field( $params['cost'] );
		$id = \tribe_create_event( $args );
		if ( ! $id ) return new \WP_Error( 'create_failed', 'Could not create event', array( 'status' => 500 ) );
		return $this->success_response( array( 'event_id' => (int) $id, 'title' => $args['post_title'] ) );
	}

	public function execute_updateEvent( $params ) {
		$id = \absint( $params['event_id'] ?? 0 );
		$post = $id ? \get_post( $id ) : null;
		if ( ! $post || $post->post_type !== 'tribe_events' ) return new \WP_Error( 'event_not_found', 'Event not found', array( 'status' => 404 ) );
		$deny = $this->require_post_cap( 'edit_post', $id );
		if ( $deny ) {
			return $deny;
		}
		$args = array();
		if ( isset( $params['title'] ) ) $args['post_title'] = \sanitize_text_field( $params['title'] );
		if ( isset( $params['start_date'] ) ) $args = array_merge( $args, $this->split_datetime( 'EventStart', $params['start_date'] ) );
		if ( isset( $params['end_date'] ) ) $args = array_merge( $args, $this->split_datetime( 'EventEnd', $params['end_date'] ) );
		if ( isset( $params['cost'] ) ) $args['EventCost'] = \sanitize_text_field( $params['cost'] );
		if ( function_exists( 'tribe_update_event' ) ) {
			\tribe_update_event( $id, $args );
		} else {
			if ( isset( $args['post_title'] ) ) \wp_update_post( array( 'ID' => $id, 'post_title' => $args['post_title'] ) );
		}
		return $this->success_response( array( 'event_id' => $id, 'updated' => true ) );
	}

	public function execute_deleteEvent( $params ) {
		$id = \absint( $params['event_id'] ?? 0 );
		$post = $id ? \get_post( $id ) : null;
		if ( ! $post || $post->post_type !== 'tribe_events' ) return new \WP_Error( 'event_not_found', 'Event not found', array( 'status' => 404 ) );
		$deny = $this->require_post_cap( 'delete_post', $id );
		if ( $deny ) {
			return $deny;
		}
		$force = ! empty( $params['force'] );
		$result = $force ? \wp_delete_post( $id, true ) : \wp_trash_post( $id );
		return $this->success_response( array( 'event_id' => $id, 'deleted' => (bool) $result, 'force' => $force ) );
	}

	public function execute_createVenue( $params ) {
		$deny = $this->require_create_published( 'tribe_venue' ); if ( $deny ) return $deny;
		if ( ! function_exists( 'tribe_create_venue' ) ) return new \WP_Error( 'not_available', 'Events API unavailable', array( 'status' => 501 ) );
		$id = \tribe_create_venue( array(
			'Venue'   => \sanitize_text_field( $params['name'] ?? '' ),
			'Address' => \sanitize_text_field( $params['address'] ?? '' ),
			'City'    => \sanitize_text_field( $params['city'] ?? '' ),
			'Country' => \sanitize_text_field( $params['country'] ?? '' ),
		) );
		if ( ! $id ) return new \WP_Error( 'create_failed', 'Could not create venue', array( 'status' => 500 ) );
		return $this->success_response( array( 'venue_id' => (int) $id, 'name' => $params['name'] ?? '' ) );
	}
}
