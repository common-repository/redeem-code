<?php
/**
 * Admin page setup
 *
 * @package redeem-code
 */

namespace Solidie_Redeem\Models;

use SolidieLib\_Array;

/**
 * Admin page setup handlers
 */
class WooCommerce {

	public static function getProductsVariations() {
		
		global $wpdb;

		// Get products
		$products = $wpdb->get_results(
			"SELECT 
				ID AS product_id, 
				post_title AS product_title
			FROM 
				{$wpdb->posts}
			WHERE
				post_type='product'
				AND post_status='publish'",
			ARRAY_A
		);
		$products = _Array::indexify( _Array::castRecursive( $products ), 'product_id' );
		$products = _Array::appendColumn( $products, 'variations', array() );

		// Get variations
		$variations = $wpdb->get_results(
			"SELECT
				ID AS variation_id,
				post_title AS variation_title,
				post_parent AS product_id
			FROM 
				{$wpdb->posts}
			WHERE
				post_type='product_variation'
				AND post_status='publish'",
			ARRAY_A
		);
		$variations = _Array::castRecursive( $variations );

		// Assign variation to corresponding products
		foreach ( $variations as $variation ) {
			
			$prod_id = $variation['product_id'];

			if ( isset( $products[ $prod_id ] ) ) {
				$products[ $prod_id ]['variations'][] = $variation;
			}
		}

		return array_values( $products );
	}
}

