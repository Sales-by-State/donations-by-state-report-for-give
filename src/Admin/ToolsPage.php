<?php
/**
 * GiveWP Tools tab for the report table.
 *
 * @package DonationsByStateReportForGive
 */

namespace DBSGIVE\Admin;

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Give_Settings_Page' ) ) {
	return;
}

/**
 * Registers the Donations by State tab under Donations → Tools.
 */
class ToolsPage extends \Give_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id          = 'dbsgive';
		$this->label       = __( 'Donations by State', 'donations-by-state-report-for-give' );
		$this->enable_save = false;

		parent::__construct();

		if ( function_exists( 'give_get_current_setting_tab' ) && give_get_current_setting_tab() === $this->id ) {
			add_action( 'give-tools_open_form', '__return_empty_string' );
			add_action( 'give-tools_close_form', '__return_empty_string' );
		}
	}

	/**
	 * Render the tab instead of a settings field table.
	 *
	 * @return void
	 */
	public function output() {
		( new Tools() )->render_tools_tab();
	}
}
