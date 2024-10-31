<?php
/**
 * Shortcode registrar
 *
 * @package redeem-code
 */

namespace Solidie_Redeem\Setup;

/**
 * Shortcode class
 */
class Shortcode {

	const SHORT_CODE = 'redeem_code_apply_form';

	/**
	 * Register shortcode
	 */
	public function __construct() {
		add_shortcode( self::SHORT_CODE, array( $this, 'renderForm' ) );
	}

	/**
	 * Render contents for gallery shortcode
	 *
	 * @param array $attrs
	 * @return string
	 */
	public function renderForm( $attrs ) {

		/* if ( ! ( $attrs['_internal_call_'] ?? false ) ) {
			
			$page_id = get_the_ID();

			if ( $page_id !== AdminSetting::getGalleryPageId() ) {
				return '<div style="text-align-center; color:#aa0000;">
					' . sprintf(
						__( '[%s] shortcode will work only if you set this page as Gallery in %sSettings%s.' ), 
						self::SHORT_CODE, 
						'<a href="' . add_query_arg( array( 'page' => AdminPage::SETTINGS_SLUG ), admin_url( 'admin.php' ) ) . '#/settings/general/gallery/">', 
						'</a>'
					) . '
				</div>';
			}
		} */
		
		return '<div 
			id="redeem_code_apply_form" 
			data-login_url="' . esc_url( wp_login_url( get_permalink() ) ) . '"
		>
			<div style="text-align: center;">
				Loading...
			</div>
		</div>';
	}
}
