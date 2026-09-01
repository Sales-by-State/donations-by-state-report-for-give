<?php
/**
 * Site Health and Donations → Tools entries for the report table.
 *
 * @package DonationsByStateReportForGive
 */

namespace DBSGIVE\Admin;

use DBSGIVE\Data\Backfill;
use DBSGIVE\Install\Schema;
use DBSGIVE\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Adds a Site Health section and a GiveWP Tools tab.
 */
class Tools {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'debug_information', array( $this, 'debug_information' ) );
		add_filter( 'give-tools_get_settings_pages', array( $this, 'register_settings_page' ), 20 );
		add_action( 'give_dbsgive_backfill', array( $this, 'handle_backfill' ) );
		add_action( 'give_dbsgive_rebuild', array( $this, 'handle_rebuild' ) );
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
	}

	/**
	 * Register the GiveWP Tools tab.
	 *
	 * @param array $pages Existing settings pages.
	 * @return array
	 */
	public function register_settings_page( $pages ) {
		if ( ! class_exists( 'Give_Settings_Page' ) || ! Plugin::can_manage() ) {
			return $pages;
		}

		if ( ! class_exists( ToolsPage::class ) ) {
			require_once DBSGIVE_DIR . 'src/Admin/ToolsPage.php';
		}

		$pages[] = new ToolsPage();

		return $pages;
	}

	/**
	 * Render the GiveWP Tools tab.
	 *
	 * @return void
	 */
	public function render_tools_tab() {
		if ( ! Plugin::can_manage() ) {
			return;
		}

		$counts    = Schema::counts();
		$remaining = Backfill::remaining();
		$url       = $this->tools_url();
		?>
		<div class="postbox">
			<h3><span><?php esc_html_e( 'Build report table', 'donations-by-state-report-for-give' ); ?></span></h3>
			<div class="inside">
				<p>
					<?php
					printf(
						/* translators: 1: rows in the report table, 2: total donations. */
						esc_html__( 'Reads existing GiveWP donations into the report table. %1$s of %2$s done.', 'donations-by-state-report-for-give' ),
						esc_html( number_format_i18n( $counts['rows'] ) ),
						esc_html( number_format_i18n( $counts['orders'] ) )
					);
					?>
				</p>
				<form method="post" action="<?php echo esc_url( $url ); ?>">
					<input type="hidden" name="give_action" value="dbsgive_backfill" />
					<?php wp_nonce_field( 'dbsgive_tools', 'dbsgive_tools_nonce' ); ?>
					<?php
					submit_button(
						$remaining > 0
							/* translators: %s: number of donations left. */
							? sprintf( __( 'Process %s remaining', 'donations-by-state-report-for-give' ), number_format_i18n( $remaining ) )
							: __( 'Run', 'donations-by-state-report-for-give' ),
						'secondary',
						'submit',
						false
					);
					?>
				</form>
			</div>
		</div>
		<div class="postbox">
			<h3><span><?php esc_html_e( 'Rebuild from scratch', 'donations-by-state-report-for-give' ); ?></span></h3>
			<div class="inside">
				<p><?php esc_html_e( 'Empties the report table and starts again from the first donation. Use this if the figures look wrong rather than merely incomplete.', 'donations-by-state-report-for-give' ); ?></p>
				<form method="post" action="<?php echo esc_url( $url ); ?>">
					<input type="hidden" name="give_action" value="dbsgive_rebuild" />
					<?php wp_nonce_field( 'dbsgive_tools', 'dbsgive_tools_nonce' ); ?>
					<?php submit_button( __( 'Rebuild', 'donations-by-state-report-for-give' ), 'secondary', 'submit', false ); ?>
				</form>
			</div>
		</div>
		<div class="postbox">
			<h3><span><?php esc_html_e( 'Data check', 'donations-by-state-report-for-give' ); ?></span></h3>
			<div class="inside">
				<p><?php echo esc_html( $this->data_check_text() ); ?></p>
			</div>
		</div>
		<?php
	}

	/**
	 * Process one backfill batch from the Tools tab.
	 *
	 * @return void
	 */
	public function handle_backfill() {
		if ( ! $this->verify_tools_request() ) {
			return;
		}

		$result = Backfill::run_batch( 1000 );
		set_transient( 'dbsgive_tools_notice', $this->backfill_message( $result ), 30 );
		wp_safe_redirect( $this->tools_url() );
		exit;
	}

	/**
	 * Empty the table and process the first batch.
	 *
	 * @return void
	 */
	public function handle_rebuild() {
		if ( ! $this->verify_tools_request() ) {
			return;
		}

		Backfill::reset();
		$result = Backfill::run_batch( 1000 );
		set_transient(
			'dbsgive_tools_notice',
			sprintf(
				/* translators: 1: donations processed, 2: donations remaining. */
				__( 'Table emptied. Processed %1$s donations, %2$s remaining.', 'donations-by-state-report-for-give' ),
				number_format_i18n( $result['processed'] ),
				number_format_i18n( $result['remaining'] )
			),
			30
		);
		wp_safe_redirect( $this->tools_url() );
		exit;
	}

	/**
	 * Show a notice after a Tools action.
	 *
	 * @return void
	 */
	public function admin_notice() {
		$notice = get_transient( 'dbsgive_tools_notice' );

		if ( ! $notice ) {
			return;
		}

		delete_transient( 'dbsgive_tools_notice' );

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html( $notice )
		);
	}

	/**
	 * Add a Site Health section.
	 *
	 * @param array $info Existing debug information.
	 * @return array
	 */
	public function debug_information( $info ) {
		if ( ! Plugin::can_view() ) {
			return $info;
		}

		$info['dbsgive-donations-by-state'] = array(
			'label'  => __( 'Donations by State Report for Give', 'donations-by-state-report-for-give' ),
			'fields' => $this->fields(),
		);

		return $info;
	}

	/**
	 * Field list for Site Health.
	 *
	 * @return array
	 */
	private function fields() {
		global $wpdb;

		if ( ! Schema::table_exists() ) {
			return array(
				'table' => array(
					'label' => __( 'Report table', 'donations-by-state-report-for-give' ),
					'value' => __( 'Missing', 'donations-by-state-report-for-give' ),
				),
			);
		}

		$counts    = Schema::counts();
		$remaining = Backfill::remaining();

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$by_status = $wpdb->get_results( "SELECT status, COUNT(*) AS total FROM {$wpdb->prefix}dbsgive_order_state GROUP BY status ORDER BY total DESC", ARRAY_A );
		$countries = $wpdb->get_results( "SELECT billing_country AS country, COUNT(*) AS total FROM {$wpdb->prefix}dbsgive_order_state GROUP BY billing_country ORDER BY total DESC", ARRAY_A );
		$no_state  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}dbsgive_order_state WHERE billing_state = ''" );
		// phpcs:enable

		$fields = array(
			'rows'      => array(
				'label' => __( 'Rows in report table', 'donations-by-state-report-for-give' ),
				'value' => number_format_i18n( $counts['rows'] ),
			),
			'orders'    => array(
				'label' => __( 'Donations in GiveWP', 'donations-by-state-report-for-give' ),
				'value' => number_format_i18n( $counts['orders'] ),
			),
			'remaining' => array(
				'label' => __( 'Donations still to import', 'donations-by-state-report-for-give' ),
				'value' => number_format_i18n( $remaining ),
			),
		);

		if ( $no_state > 0 ) {
			$fields['no_state'] = array(
				'label' => __( 'Rows with no state', 'donations-by-state-report-for-give' ),
				'value' => number_format_i18n( $no_state ),
			);
		}

		$bits = array();

		foreach ( (array) $by_status as $row ) {
			$bits[] = $row['status'] . ': ' . number_format_i18n( (int) $row['total'] );
		}

		if ( $bits ) {
			$fields['by_status'] = array(
				'label' => __( 'By status', 'donations-by-state-report-for-give' ),
				'value' => implode( ', ', $bits ),
			);
		}

		$bits = array();

		foreach ( (array) $countries as $row ) {
			$code   = '' === $row['country'] ? __( '(blank)', 'donations-by-state-report-for-give' ) : $row['country'];
			$bits[] = $code . ': ' . number_format_i18n( (int) $row['total'] );
		}

		if ( $bits ) {
			$fields['countries'] = array(
				'label' => __( 'Countries', 'donations-by-state-report-for-give' ),
				'value' => implode( ', ', $bits ),
			);
		}

		return $fields;
	}

	/**
	 * Read-only summary for the Tools tab.
	 *
	 * @return string
	 */
	private function data_check_text() {
		$fields = $this->fields();
		$bits   = array();

		foreach ( $fields as $field ) {
			$bits[] = $field['label'] . ': ' . $field['value'];
		}

		return implode( ' — ', $bits );
	}

	/**
	 * Message after a backfill batch.
	 *
	 * @param array $result Batch result.
	 * @return string
	 */
	private function backfill_message( array $result ) {
		if ( empty( $result['complete'] ) ) {
			return sprintf(
				/* translators: 1: donations processed, 2: donations remaining. */
				__( 'Processed %1$s donations. %2$s still to go — run the tool again, or leave Donations → Donations by State open.', 'donations-by-state-report-for-give' ),
				number_format_i18n( $result['processed'] ),
				number_format_i18n( $result['remaining'] )
			);
		}

		return sprintf(
			/* translators: %s: donations processed. */
			__( 'Processed %s donations. The report table is complete.', 'donations-by-state-report-for-give' ),
			number_format_i18n( $result['processed'] )
		);
	}

	/**
	 * Capability and nonce check for Tools POSTs.
	 *
	 * @return bool
	 */
	private function verify_tools_request() {
		if ( ! Plugin::can_manage() ) {
			return false;
		}

		check_admin_referer( 'dbsgive_tools', 'dbsgive_tools_nonce' );

		return true;
	}

	/**
	 * Tools tab URL.
	 *
	 * @return string
	 */
	private function tools_url() {
		return admin_url( 'edit.php?post_type=give_forms&page=give-tools&tab=dbsgive' );
	}
}
