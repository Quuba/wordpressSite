<?php
/**
 * HivePress component.
 *
 * @package HiveTheme\Components
 */

namespace HiveTheme\Components;

use HiveTheme\Helpers as ht;
use HivePress\Helpers as hp;
use HivePress\Blocks;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * HivePress component class.
 *
 * @class HivePress
 */
final class HivePress extends Component {

	/**
	 * Class constructor.
	 *
	 * @param array $args Component arguments.
	 */
	public function __construct( $args = [] ) {

		// Check HivePress status.
		if ( ! ht\is_plugin_active( 'hivepress' ) ) {
			return;
		}

		if ( is_admin() ) {

			// Add admin notices.
			add_filter( 'hivepress/v1/admin_notices', [ $this, 'add_admin_notices' ] );
		} else {

			// Render site header.
			add_filter( 'hivetheme/v1/areas/site_header', [ $this, 'render_site_header' ] );

			if ( ht\is_plugin_active( 'woocommerce' ) ) {

				// Hide page header.
				add_filter( 'hivetheme/v1/areas/page_header', [ $this, 'hide_page_header' ] );

				// Render page title.
				add_action( 'woocommerce_account_content', [ $this, 'render_page_title' ], 1 );
			}

			// Alter templates.
			add_filter( 'hivepress/v1/templates/listing_view_block', [ $this, 'alter_listing_view_block' ] );
			add_filter( 'hivepress/v1/templates/listing_view_page', [ $this, 'alter_listing_view_page' ] );
			add_filter( 'hivepress/v1/templates/listing_category_view_block', [ $this, 'alter_listing_category_view_block' ] );
		}

		parent::__construct( $args );
	}

	/**
	 * Gets translation string.
	 *
	 * @param string $key String key.
	 * @return mixed
	 */
	public function get_string( $key ) {
		$string = '';

		if ( ht\is_plugin_active( 'hivepress' ) ) {
			$string = hivepress()->translator->get_string( $key );
		}

		return $string;
	}

	/**
	 * Adds admin notices.
	 *
	 * @param array $notices Notice arguments.
	 * @return array
	 */
	public function add_admin_notices( $notices ) {

		// Get listing count.
		$count = wp_count_posts( 'hp_listing' );

		// Add import notice.
		if ( isset( $count->publish ) && ! $count->publish ) {
			$notices['demo_import'] = [
				'type'        => 'info',
				'dismissible' => true,
				'text'        => sprintf(
					/* translators: 1: theme name, 2: link URL. */
					hp\sanitize_html( __( 'If you want to start with the %1$s demo content, please follow <a href="%2$s" target="_blank">this screencast</a> to import it.', 'listinghive' ) ),
					hivetheme()->get_name(),
					esc_url( 'https://hivepress.io/docs/themes/' . get_template() . '/#importing-demo-content' )
				),
			];
		}

		return $notices;
	}

	/**
	 * Renders site header.
	 *
	 * @param string $output HTML output.
	 * @return string
	 */
	public function render_site_header( $output ) {
		$output .= ( new \HivePress\Blocks\Template( [ 'template' => 'site_header_block' ] ) )->render();

		return $output;
	}

	/**
	 * Hides page header.
	 *
	 * @param string $output HTML output.
	 * @return string
	 */
	public function hide_page_header( $output ) {
		if ( is_wc_endpoint_url( 'orders' ) || is_wc_endpoint_url( 'view-order' ) ) {
			$output = '';
		}

		return $output;
	}

	/**
	 * Renders page title.
	 */
	public function render_page_title() {
		if ( is_wc_endpoint_url( 'orders' ) || is_wc_endpoint_url( 'view-order' ) ) {
			echo ( new Blocks\Part(
				[
					'path'    => 'page/page-title',

					'context' => [
						'page_title' => get_the_title(),
					],
				]
			) )->render();
		}
	}

	/**
	 * Alters listing view block.
	 *
	 * @param array $template Template arguments.
	 * @return array
	 */
	public function alter_listing_view_block( $template ) {
		$category = hp\search_array_value( $template, [ 'blocks', 'listing_category' ] );

		return hp\merge_trees(
			$template,
			[
				'blocks' => [
					'listing_content' => [
						'blocks' => [
							'listing_category' => array_merge(
								$category,
								[
									'_order' => 5,
								]
							),
						],
					],
				],
			]
		);
	}

	/**
	 * Alters listing view page.
	 *
	 * @param array $template Template arguments.
	 * @return array
	 */
	public function alter_listing_view_page( $template ) {
		$category = hp\search_array_value( $template, [ 'blocks', 'listing_category' ] );

		return hp\merge_trees(
			$template,
			[
				'blocks' => [
					'page_content' => [
						'blocks' => [
							'listing_category' => array_merge(
								$category,
								[
									'_order' => 5,
								]
							),
						],
					],
				],
			]
		);
	}

	/**
	 * Alters listing category view block.
	 *
	 * @param array $template Template arguments.
	 * @return array
	 */
	public function alter_listing_category_view_block( $template ) {
		$count = hp\search_array_value( $template, [ 'blocks', 'listing_category_count' ] );

		return hp\merge_trees(
			$template,
			[
				'blocks' => [
					'listing_category_header' => [
						'blocks' => [
							'listing_category_count' => $count,
						],
					],

					'listing_category_name'   => [
						'tag' => 'h3',
					],
				],
			]
		);
	}
}
