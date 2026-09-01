<?php
/**
 * Plugin Name:          Donations by State Report for Give
 * Plugin URI:           https://salesbystate.com/
 * Description:          See a yearly breakdown of GiveWP donations by state / county / province for a given country, filterable by donation status.
 * Version:              1.0.0
 * Author:               Rodolfo Melogli
 * Author URI:           https://salesbystate.com/
 * Developer:            Rodolfo Melogli
 * Developer URI:        https://salesbystate.com/
 * Text Domain:          donations-by-state-report-for-give
 * Domain Path:          /languages
 * Requires at least:    6.6
 * Tested up to:         7.1
 * Requires PHP:         8.0
 * Requires Plugins:     give
 * Give requires at least: 3.0
 * Give tested up to:    4.16.7
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package DonationsByStateReportForGive
 * @copyright 2026 Rodolfo Melogli
 */

defined( 'ABSPATH' ) || exit;

define( 'DBSGIVE_VERSION', '1.0.0' );
define( 'DBSGIVE_FILE', __FILE__ );
define( 'DBSGIVE_DIR', plugin_dir_path( __FILE__ ) );
define( 'DBSGIVE_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register(
	function ( $class_name ) {
		$prefix = 'DBSGIVE\\';

		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}

		$relative = substr( $class_name, strlen( $prefix ) );
		$path     = DBSGIVE_DIR . 'src/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
);

add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'GIVE_VERSION' ) && ! function_exists( 'Give' ) ) {
			add_action(
				'admin_notices',
				function () {
					if ( ! current_user_can( 'activate_plugins' ) ) {
						return;
					}

					printf(
						'<div class="notice notice-error"><p>%s</p></div>',
						esc_html__( 'Donations by State Report for Give requires GiveWP to be installed and active.', 'donations-by-state-report-for-give' )
					);
				}
			);

			return;
		}

		DBSGIVE\Plugin::instance()->init();
	},
	20
);

register_activation_hook(
	DBSGIVE_FILE,
	function () {
		require_once DBSGIVE_DIR . 'src/Install/Schema.php';
		DBSGIVE\Install\Schema::install();
	}
);

register_deactivation_hook(
	DBSGIVE_FILE,
	function () {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( 'dbsgive_backfill_batch', array(), 'donations-by-state-report-for-give' );
		}
	}
);
