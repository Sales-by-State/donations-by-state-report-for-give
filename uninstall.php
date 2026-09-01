<?php
/**
 * Removes the plugin's data when it is deleted.
 *
 * @package DonationsByStateReportForGive
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

$dbsgive_options = array(
	'dbsgive_db_version',
	'dbsgive_backfill_cursor',
	'dbsgive_year_start',
);

foreach ( $dbsgive_options as $dbsgive_option ) {
	delete_option( $dbsgive_option );
}

if ( is_multisite() ) {
	foreach ( $dbsgive_options as $dbsgive_option ) {
		delete_site_option( $dbsgive_option );
	}
}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}dbsgive_order_state" );

if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( 'dbsgive_backfill_batch', array(), 'donations-by-state-report-for-give' );
}
