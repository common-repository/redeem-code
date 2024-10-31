<?php
/**
 * Redeem code
 *
 * @package redeem-code
 */

namespace Solidie_Redeem\Models;

use Solidie_Redeem\Helpers\Utilities;
use SolidieLib\_Array;
use SolidieLib\_String;

/**
 * Redeem code CRUD
 */
class RedeemCode {

	/**
	 * Save new redeem codes
	 *
	 * @param int   $product_id   The ID of the product
	 * @param int   $variation_id The ID of the product variation
	 * @param array $codes        An array of redeem codes to save
	 * @return void
	 */
	public static function saveCodes( $product_id, $variation_id, $codes ) {
		
		global $wpdb;

		foreach ( $codes as $code ) {

			if ( ! empty( self::getCodeStatus( $code ) ) ) {
				continue;
			}
			
			$wpdb->insert(
				$wpdb->redeem_codes,
				array(
					'redeem_code'  => $code,	
					'product_id'   => $product_id,
					'variation_id' => $variation_id,
					'product_type' => 'wc',
					'created_at'   => Utilities::gmDate()	
				)
			);
		}
	}

	/**
	 * Get redeem row by code
	 *
	 * @param string $code The redeem code to look up
	 * @return array|null The code status as an array, or null if not found
	 */
	public static function getCodeStatus( $code ) {
		
		global $wpdb;

		$redeem = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT 
					* 
				FROM 
					{$wpdb->redeem_codes}
				WHERE 
					redeem_code=%s
				LIMIT 1",
				$code
			),
			ARRAY_A
		);

		return ! empty( $redeem ) ? _Array::castRecursive( $redeem ) : null;
	}

	/**
	 * Get redeem codes based on specified arguments
	 *
	 * @param array $args         An array of arguments to filter the codes
	 * @param bool  $segmentation Whether to return segmentation data instead of codes
	 * @return array              An array of codes or segmentation data
	 */
	public static function getCodes( array $args, $segmentation = false ) {
		
		global $wpdb;

		$where_clause = '';
		$limit_offset = '';

		if ( ! empty( $args['limit'] ) ) {
			$limit        = $args['limit'];
			$page         = max( 1, ( $args['page'] ?? 1 ) );
			$offset       = ( $page - 1 ) * $limit;
			$limit_offset = $wpdb->prepare( 'LIMIT %d OFFSET %d', $limit, $offset );
		}

		// Get only applied codes
		if ( ( $args['status'] ?? false ) === 'used' ) {
			$where_clause .= ' AND _code.order_id IS NOT NULL';
		}

		// Get only unused codes
		if ( ( $args['status'] ?? false ) === 'unused' ) {
			$where_clause .= ' AND _code.order_id IS NULL';
		}

		// Filter product ID
		if ( ! empty( $args['product_id'] ) ) {
			$where_clause .= $wpdb->prepare( ' AND _code.product_id=%d', $args['product_id'] );
		}

		// Filter variation ID
		if ( ! empty( $args['variation_id'] ) ) {
			$where_clause .= $wpdb->prepare( ' AND _code.variation_id=%d', $args['variation_id'] );
		}

		// Filter codes by prefix
		if ( ! empty( $args['prefix'] ) ) {
			$where_clause .= ' AND _code.redeem_code LIKE \'' . sanitize_text_field( $args['prefix'] ) . '%\'';
		}

		// Get codes only if it s export mode
		if ( 'export' === ( $args['mode'] ?? null ) ) {
			return $wpdb->get_col(
				"SELECT 
					_code.redeem_code
				FROM 
					{$wpdb->redeem_codes} _code
					LEFT JOIN {$wpdb->users} _user ON _code.customer_id=_user.ID
				WHERE 1=1 {$where_clause}
				ORDER BY _code.code_id DESC"
			);
		}

		// If segmentation, then get pagination factors
		if ( $segmentation ) {
			$total_count = (int) $wpdb->get_var(
				"SELECT 
					COUNT(_code.code_id)
				FROM
					{$wpdb->redeem_codes} _code
					LEFT JOIN {$wpdb->users} _user ON _code.customer_id=_user.ID
				WHERE
					1=1 {$where_clause}"
			);

			$page_count = ceil( $total_count / $limit );

			return array(
				'total_count' => $total_count,
				'page_count'  => $page_count,
				'page'        => $page,
				'limit'       => $limit,
			);
		}

		// If neither export nor segmentation, get paginated codes
		$codes = $wpdb->get_results(
			"SELECT 
				_code.*,
				_user.display_name
			FROM
				{$wpdb->redeem_codes} _code
				LEFT JOIN {$wpdb->users} _user ON _code.customer_id=_user.ID
			WHERE
				1=1 {$where_clause}
			ORDER BY _code.code_id DESC
			{$limit_offset}",
			ARRAY_A
		);

		$codes = _Array::castRecursive( $codes );

		foreach ( $codes as $index => $code ) {
			$codes[ $index ]['avatar_url'] = ! empty( $code['customer_id'] ) ? get_avatar_url( $code['customer_id'] ) : null;
		}

		return $codes;
	}

	/**
	 * Apply redeem code for a user
	 *
	 * @param int    $user_id The ID of the user applying the code
	 * @param string $code    The redeem code to apply
	 * @return bool           True if the code was successfully applied, false otherwise
	 */
	public static function applyCode( $user_id, $code ) {

		global $wpdb;

		$redeem = self::getCodeStatus( $code );

		if ( empty( $redeem ) || ! empty( $redeem['order_id'] ) ) {
			return false;
		}

		// Load WooCommerce Order Object
		$order = wc_create_order();
		
		// Get the variation product object
		$the_product = wc_get_product( ! empty( $redeem['variation_id'] ) ? $redeem['variation_id'] : $redeem['product_id'] );
		
		if ( $the_product ) {

			// Add the variation to the order
			$order->add_product( $the_product, 1, array(
				'subtotal' => 0, // Set subtotal as 0
				'total' => 0     // Set total as 0
			));

			// Set customer data if needed
			$order->set_customer_id( $user_id );

			// Set the status of the order (e.g., processing or completed)
			$order->set_status( 'completed' );
			
			// Calculate totals and save the order
			$order->calculate_totals();
			$order->save();
			$order->add_order_note( 'Redeem Code: ' . $code );
			$order->update_meta_data( 'redeem_code_applied_code', $code );
			$order->update_meta_data( 'redeem_code_code_id', $redeem['code_id'] );

			// Remove the reedeem code as it is used
			$wpdb->update(
				$wpdb->redeem_codes,
				array(
					'order_id'     => $order->get_id(),
					'customer_id'  => $user_id,
					'applied_time' => Utilities::gmDate()
				),
				array(
					'code_id' => $redeem['code_id']
				)
			);

			return true;
		}
		
		return false;
	}

	/**
	 * Delete redeem codes by their IDs
	 *
	 * @param array $ids An array of code IDs to delete
	 * @return void
	 */
	public static function deleteRedeemCodes( array $ids ) {

		if ( empty( $ids ) ) {
			return;
		}
		
		global $wpdb;

		$ids = _String::getSQLImplodesPrepared( $ids );

		$wpdb->query(
			"DELETE FROM {$wpdb->redeem_codes} WHERE code_id IN ({$ids})"
		);
	}
}
