<?php
/**
 * Keeps the report table in step with GiveWP donations.
 *
 * @package DonationsByStateReportForGive
 */

namespace DBSGIVE\Data;

use DBSGIVE\Install\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Writes one row per donation.
 */
class Sync {

	/**
	 * Register hooks.
	 *
	 * Modern GiveWP fires donation model actions. Legacy payment functions still
	 * run for older gateways and admin edits.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'givewp_donation_created', array( $this, 'on_donation' ), 20, 1 );
		add_action( 'givewp_donation_updated', array( $this, 'on_donation' ), 20, 1 );
		add_action( 'givewp_donation_untrashed', array( $this, 'on_donation' ), 20, 1 );
		add_action( 'givewp_donation_deleted', array( $this, 'on_delete' ), 20, 1 );
		add_action( 'givewp_donation_trashed', array( $this, 'on_delete' ), 20, 1 );
		add_action( 'give_insert_payment', array( $this, 'on_order_id' ), 20, 1 );
		add_action( 'give_update_payment_status', array( $this, 'on_order_id' ), 20, 1 );
	}

	/**
	 * Handle a donation model from a GiveWP hook.
	 *
	 * @param mixed $donation Donation model or ID.
	 * @return void
	 */
	public function on_donation( $donation ) {
		$this->upsert( $this->donation_id( $donation ) );
	}

	/**
	 * Handle a numeric donation ID from a legacy payment hook.
	 *
	 * @param mixed $donation_id Donation ID.
	 * @return void
	 */
	public function on_order_id( $donation_id ) {
		$this->upsert( (int) $donation_id );
	}

	/**
	 * Remove the row when a donation is deleted or trashed.
	 *
	 * @param mixed $donation Donation model or ID.
	 * @return void
	 */
	public function on_delete( $donation ) {
		$this->delete( $this->donation_id( $donation ) );
	}

	/**
	 * Insert or update the row for one donation.
	 *
	 * @param int $order_id Donation ID.
	 * @return bool
	 */
	public function upsert( $order_id ) {
		global $wpdb;

		$order_id = (int) $order_id;

		if ( ! $order_id ) {
			return false;
		}

		$row = self::build_row( $order_id );

		if ( ! $row ) {
			$this->delete( $order_id );
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return false !== $wpdb->replace(
			Schema::table(),
			$row,
			array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%f' )
		);
	}

	/**
	 * Remove the row for a donation.
	 *
	 * @param int $order_id Donation ID.
	 * @return void
	 */
	public function delete( $order_id ) {
		global $wpdb;

		$order_id = (int) $order_id;

		if ( ! $order_id ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( Schema::table(), array( 'order_id' => $order_id ), array( '%d' ) );
	}

	/**
	 * Build the row for a donation from posts and donation meta.
	 *
	 * GiveWP groups donations by billing region. There is no shipping address
	 * on a donation, so the shipping columns copy the billing values. Tax is
	 * not stored on donations in GiveWP core.
	 *
	 * @param int $order_id Donation ID.
	 * @return array<string,mixed>|false
	 */
	public static function build_row( $order_id ) {
		global $wpdb;

		$order_id = (int) $order_id;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$donation = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT p.ID, p.post_status AS status, p.post_date AS date_created,
				        completed.meta_value AS date_completed,
				        country.meta_value AS billing_country,
				        state.meta_value AS billing_state,
				        currency.meta_value AS currency,
				        total.meta_value AS total
				 FROM {$wpdb->prefix}posts p
				 LEFT JOIN {$wpdb->prefix}give_donationmeta country
				        ON country.donation_id = p.ID AND country.meta_key = %s
				 LEFT JOIN {$wpdb->prefix}give_donationmeta state
				        ON state.donation_id = p.ID AND state.meta_key = %s
				 LEFT JOIN {$wpdb->prefix}give_donationmeta currency
				        ON currency.donation_id = p.ID AND currency.meta_key = %s
				 LEFT JOIN {$wpdb->prefix}give_donationmeta total
				        ON total.donation_id = p.ID AND total.meta_key = %s
				 LEFT JOIN {$wpdb->prefix}give_donationmeta completed
				        ON completed.donation_id = p.ID AND completed.meta_key = %s
				 WHERE p.ID = %d
				   AND p.post_type = 'give_payment'",
				'_give_donor_billing_country',
				'_give_donor_billing_state',
				'_give_payment_currency',
				'_give_payment_total',
				'_give_completed_date',
				$order_id
			)
		);

		if ( ! $donation ) {
			return false;
		}

		if ( in_array( (string) $donation->status, array( 'trash', 'auto-draft' ), true ) ) {
			return false;
		}

		$billing_country = strtoupper( substr( (string) $donation->billing_country, 0, 2 ) );
		$billing_state   = (string) $donation->billing_state;
		$total           = round( (float) $donation->total, 2 );
		$created         = self::normalize_datetime( $donation->date_created );
		$paid            = self::normalize_datetime( $donation->date_completed );
		$currency        = strtoupper( substr( (string) $donation->currency, 0, 3 ) );

		if ( '' === $currency && function_exists( 'give_get_currency' ) ) {
			$currency = strtoupper( substr( (string) give_get_currency(), 0, 3 ) );
		}

		return array(
			'order_id'         => (int) $donation->ID,
			'status'           => substr( sanitize_key( (string) $donation->status ), 0, 32 ),
			'date_created'     => $created ? $created : '0000-00-00 00:00:00',
			'date_paid'        => $paid ? $paid : null,
			'billing_country'  => $billing_country,
			'billing_state'    => substr( $billing_state, 0, 50 ),
			'shipping_country' => $billing_country,
			'shipping_state'   => substr( $billing_state, 0, 50 ),
			'currency'         => $currency,
			'total_sales'      => $total,
			'tax_total'        => 0,
			'shipping_total'   => 0,
			'net_total'        => $total,
		);
	}

	/**
	 * Read a donation ID from a model or a scalar.
	 *
	 * @param mixed $donation Donation model or ID.
	 * @return int
	 */
	private function donation_id( $donation ) {
		if ( is_object( $donation ) ) {
			if ( isset( $donation->id ) ) {
				return (int) $donation->id;
			}

			if ( isset( $donation->ID ) ) {
				return (int) $donation->ID;
			}

			return 0;
		}

		return (int) $donation;
	}

	/**
	 * Normalise a datetime string.
	 *
	 * @param mixed $value Datetime.
	 * @return string|null
	 */
	private static function normalize_datetime( $value ) {
		$value = (string) $value;

		if ( '' === $value || '0000-00-00 00:00:00' === $value ) {
			return null;
		}

		$ts = strtotime( $value );

		return $ts ? gmdate( 'Y-m-d H:i:s', $ts ) : null;
	}
}
