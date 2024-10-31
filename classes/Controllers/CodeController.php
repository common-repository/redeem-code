<?php
/**
 * Code handler methods
 *
 * @package redeem-code
 */

namespace Solidie_Redeem\Controllers;

use Solidie_Redeem\Models\RedeemCode;

/**
 * Code controller class
 */
class CodeController {

	const PREREQUISITES = array(
		'saveRedeemCodes'   => array(
			'role' => 'administrator',
		),
		'fetchRedeemCodes'  => array(
			'role' => 'administrator',
		),
		'deleteRedeemCodes' => array(
			'role' => 'administrator',
		),
		'applyRedeemCode'   => array(),
	);

	/**
	 * Create or update redeem codes for a product
	 *
	 * @param int   $product_id   The ID of the product.
	 * @param array $codes        Array of redeem codes to save.
	 * @param int   $variation_id Optional. The ID of the product variation. Default 0.
	 * @return void
	 */
	public static function saveRedeemCodes( int $product_id, array $codes, int $variation_id = 0 ) {
		
		$codes = array_map( 'trim', $codes );
		$codes = array_filter( $codes, function ( $code ) {
			return ! empty( $code );
		});

		// Check if the product is simple, variable or variable subscription
		$product = wc_get_product( $product_id );
		if ( ! $product || ! in_array( $product->get_type(), array( 'simple', 'variable', 'variable-subscription' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Supports simple, variable and variable subscription products only', 'redeem-code' ) ) );
		}

		if ( $product->get_type() !== 'simple' && empty( $variation_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Please select a variation first', 'redeem-code' ) ) );
		}

		RedeemCode::saveCodes( $product_id, $variation_id, $codes );

		wp_send_json_success();
	}

	/**
	 * Fetch redeem codes for the list table
	 *
	 * @param int $product_id   The ID of the product.
	 * @param int $page         The current page number for pagination.
	 * @param int $variation_id Optional. The ID of the product variation. Default 0.
	 * @return void
	 */
	public static function fetchRedeemCodes( int $product_id, int $page, int $variation_id = 0, string $status = 'all', string $prefix = '', string $mode = 'view' ) {
		
		$is_export    = $mode === 'export';
		$limit        = $is_export ? null : 30;
		$args         = compact( 'product_id', 'variation_id', 'page', 'status', 'prefix', 'limit', 'mode' );
		$codes        = RedeemCode::getCodes( $args );

		wp_send_json_success(
			array(
				'codes'        => $codes,
				'segmentation' => ! $is_export ? RedeemCode::getCodes( $args, true ) : null,
			)
		);
	}

	/**
	 * Apply a redeem code for the current user
	 *
	 * @param string $code The redeem code to apply.
	 * @return void
	 */
	public static function applyRedeemCode( string $code ) {

		$uid = get_current_user_id();
		
		// Limit brute force attempt
		$check_key       = 'redeem_code_applied_last_time';
		$last_checked    = get_user_meta( $uid, $check_key, true );
		$allowed_minutes = 2;
		if ( ! empty( $last_checked ) && $last_checked > ( time() - ( 60 * $allowed_minutes ) ) ) {
			wp_send_json_error( array( 'message' => sprintf( __( 'You can try once per %d minute', 'redeem-code' ), $allowed_minutes ) ) );
		}
		update_user_meta( $uid, $check_key, time() );
		
		$applied = RedeemCode::applyCode( $uid, $code );

		if ( $applied ) {
			wp_send_json_success( array( 'redirect_to' => wc_get_endpoint_url( 'orders', '', wc_get_page_permalink( 'myaccount' ) ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'The code is invalid or used already!', 'redeem-code' ) ) );
		}
	}

	/**
	 * Delete specified redeem codes
	 *
	 * @param array $code_ids Array of redeem code IDs to delete.
	 * @return void
	 */
	public static function deleteRedeemCodes( array $code_ids ) {
		
		$code_ids = array_filter( $code_ids, 'is_numeric' );
		
		RedeemCode::deleteRedeemCodes( $code_ids );

		wp_send_json_success();
	}
}
