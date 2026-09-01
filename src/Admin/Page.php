<?php
/**
 * The report page and its assets.
 *
 * @package DonationsByStateReportForGive
 */

namespace DBSGIVE\Admin;

use DBSGIVE\Filters;
use DBSGIVE\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the report under Donations, matching other GiveWP extensions.
 */
class Page {

	/**
	 * Menu slug.
	 */
	const SLUG = 'dbsgive-donations-by-state';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'register_page' ), 99 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Add the report under Donations.
	 *
	 * @return void
	 */
	public function register_page() {
		add_submenu_page(
			'edit.php?post_type=give_forms',
			__( 'Donations by State', 'donations-by-state-report-for-give' ),
			__( 'Donations by State', 'donations-by-state-report-for-give' ),
			'view_give_reports',
			self::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Render the root element for the standalone page.
	 *
	 * @return void
	 */
	public function render() {
		if ( ! Plugin::can_view() ) {
			return;
		}

		printf(
			'<div class="wrap dbsgive-wrap">
				<div class="dbsgive-page-header"><h1 class="dbsgive-page-header__title">%s</h1></div>
				<div id="dbsgive-root"></div>
			</div>',
			esc_html__( 'Donations by State', 'donations-by-state-report-for-give' )
		);
	}

	/**
	 * Enqueue the report bundle on this screen only.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( ! $this->is_screen( $hook ) ) {
			return;
		}

		$script = DBSGIVE_DIR . 'assets/js/report.js';
		$style  = DBSGIVE_DIR . 'assets/css/report.css';

		wp_register_script(
			'dbsgive-report',
			DBSGIVE_URL . 'assets/js/report.js',
			array(
				'wp-hooks',
				'wp-element',
				'wp-i18n',
				'wp-api-fetch',
				'wp-url',
				'wp-components',
			),
			file_exists( $script ) ? (string) filemtime( $script ) : DBSGIVE_VERSION,
			true
		);

		wp_set_script_translations( 'dbsgive-report', 'donations-by-state-report-for-give', DBSGIVE_DIR . 'languages' );
		wp_localize_script( 'dbsgive-report', 'dbsgiveConfig', $this->config() );
		wp_enqueue_script( 'dbsgive-report' );

		wp_enqueue_style( 'wp-components' );

		wp_enqueue_style(
			'dbsgive-report',
			DBSGIVE_URL . 'assets/css/report.css',
			array( 'wp-components' ),
			file_exists( $style ) ? (string) filemtime( $style ) : DBSGIVE_VERSION
		);
	}

	/**
	 * Whether this screen is showing.
	 *
	 * @param string $hook Optional enqueue hook.
	 * @return bool
	 */
	private function is_screen( $hook = '' ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reading the current screen, not acting on it.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( self::SLUG === $page ) {
			return true;
		}

		return is_string( $hook ) && false !== strpos( $hook, self::SLUG );
	}

	/**
	 * Data the bundle needs to draw its controls.
	 *
	 * @return array
	 */
	private function config() {
		$measures = array();

		foreach ( Filters::measures() as $key => $measure ) {
			$measures[] = array(
				'key'   => $key,
				'label' => $measure['label'],
				'type'  => $measure['type'],
			);
		}

		$statuses = array();

		foreach ( Filters::order_statuses() as $key => $label ) {
			$statuses[] = array(
				'value' => $key,
				'label' => $label,
			);
		}

		$years = array();

		foreach ( Filters::years() as $year ) {
			$years[] = array(
				'value' => (string) $year,
				'label' => (string) $year,
			);
		}

		$countries = array();

		foreach ( Filters::countries_with_states() as $code => $label ) {
			$countries[] = array(
				'value' => $code,
				'label' => $label,
			);
		}

		return array(
			'measures'        => $measures,
			'statuses'        => $statuses,
			'years'           => $years,
			'countries'       => $countries,
			'defaultCountry'  => Filters::default_country(),
			'defaultYear'     => (string) Filters::default_year(),
			'defaultStatuses' => Filters::default_statuses(),
			'perPageOptions'  => array( 10, 25, 50, 100 ),
			'title'           => __( 'Donations by State', 'donations-by-state-report-for-give' ),
			'canBuild'        => Plugin::can_manage(),
			'mode'            => 'standalone',
		);
	}
}
