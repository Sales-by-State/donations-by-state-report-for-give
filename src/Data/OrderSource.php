<?php
/**
 * Locates GiveWP donations.
 *
 * @package DonationsByStateReportForGive
 */

namespace DBSGIVE\Data;

defined( 'ABSPATH' ) || exit;

/**
 * Reads donation IDs from WordPress posts of type give_payment.
 *
 * Trash and auto-draft records are skipped. Table names are written as
 * literals so every identifier in the SQL is fixed.
 */
class OrderSource {

	/**
	 * Total number of donations on the site.
	 *
	 * @return int
	 */
	public static function count() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}posts
			 WHERE post_type = 'give_payment'
			   AND post_status NOT IN ( 'trash', 'auto-draft' )"
		);
	}

	/**
	 * Number of donations above a cursor.
	 *
	 * @param int $cursor Highest donation ID already processed.
	 * @return int
	 */
	public static function count_after( $cursor ) {
		global $wpdb;

		$cursor = max( 0, (int) $cursor );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}posts
				 WHERE post_type = 'give_payment'
				   AND post_status NOT IN ( 'trash', 'auto-draft' )
				   AND ID > %d",
				$cursor
			)
		);
	}

	/**
	 * The next batch of donation IDs after a cursor.
	 *
	 * @param int $cursor Highest donation ID already processed.
	 * @param int $limit  Batch size.
	 * @return int[]
	 */
	public static function ids_after( $cursor, $limit ) {
		global $wpdb;

		$cursor = max( 0, (int) $cursor );
		$limit  = max( 1, (int) $limit );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->prefix}posts
				 WHERE post_type = 'give_payment'
				   AND post_status NOT IN ( 'trash', 'auto-draft' )
				   AND ID > %d
				 ORDER BY ID ASC
				 LIMIT %d",
				$cursor,
				$limit
			)
		);

		return array_map( 'intval', (array) $ids );
	}
}
