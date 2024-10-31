<?php
/**
 * Admin page setup
 *
 * @package redeem-code
 */

namespace Solidie_Redeem\Setup;

use Solidie_Redeem\Main;
use Solidie_Redeem\Models\WooCommerce;

/**
 * Admin page setup handlers
 */
class AdminPage {

	const MENU_SLUG = 'redeem-codes';

	/**
	 * Admin page setup hooks register
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'registerMenu' ) );
		add_filter( 'plugin_action_links_' . Main::$configs->basename, array( $this, 'pageLink' ) );
	}

	public function pageLink(array $actions ) {

		$actions['redeem_codes_page_link'] = '<a href="' . esc_url( add_query_arg( array( 'page' => self::MENU_SLUG ), admin_url( 'admin.php' ) ) ) . '">
			<span style="color: #00aa00; font-weight: bold;">' .
				__( 'Dashboard', 'redeem-code' ) .
			'</span>
		</a>';

		return $actions;
	}

	/**
	 * Register admin menu pages
	 *
	 * @return void
	 */
	public function registerMenu() {

		// Register redeem-code dashboard home
		add_submenu_page(
			'woocommerce',
			esc_html__( 'Redeem Codes', 'redeem-code' ),
			esc_html__( 'Redeem Codes', 'redeem-code' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'redeemPage' )
		);
	}

	/**
	 * Main page content
	 *
	 * @return void
	 */
	public function redeemPage() {
		$products = WooCommerce::getProductsVariations();
		echo '<div id="redeem_codes_dashboard" data-products="' . esc_attr( wp_json_encode( $products ) ) . '"></div>';
	}
}
