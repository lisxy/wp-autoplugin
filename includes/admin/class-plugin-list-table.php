<?php
/**
 * Autoplugin List Table.
 *
 * @package WP-Autoplugin
 * @since 1.0.0
 * @version 1.0.5
 * @link https://wp-autoplugin.com
 * @license GPL-2.0+
 * @license https://www.gnu.org/licenses/gpl-2.0.html
 */

namespace WP_Autoplugin\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin List Table class.
 */
class Plugin_List_Table extends \WP_List_Table {

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct(
			[
				'singular' => 'plugin',
				'plural'   => 'plugins',
				'ajax'     => false,
			]
		);
	}

	/**
	 * Set the columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return [
			'plugin' => __( 'Plugin', 'wp-autoplugin' ),
		];
	}

	/**
	 * Do not render unknown columns.
	 *
	 * @param array  $item        The current item.
	 * @param string $column_name The current column name.
	 *
	 * @return array
	 */
	public function column_default( $item, $column_name ) {
		return '';
	}

	/**
	 * Render the checkbox column.
	 *
	 * @param array $item The current item.
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return '<input type="checkbox" />';
	}

	/**
	 * Render the primary column.
	 *
	 * @param array $item The current item.
	 *
	 * @return string
	 */
	public function column_plugin( $item ) {
		$actions = [];
		if ( $item['is_active'] ) {
			$url                   = wp_nonce_url( admin_url( 'admin.php?page=wp-autoplugin&action=deactivate&plugin=' . $item['plugin_path'] ), 'wp-autoplugin-activate-plugin', 'nonce' );
			$actions['deactivate'] = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Deactivate', 'wp-autoplugin' ) );
		} else {
			$url                 = wp_nonce_url( admin_url( 'admin.php?page=wp-autoplugin&action=activate&plugin=' . $item['plugin_path'] ), 'wp-autoplugin-activate-plugin', 'nonce' );
			$actions['activate'] = sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Activate', 'wp-autoplugin' ) );
		}

		$actions['fix']     = sprintf( '<a href="%s">%s</a>', esc_url( wp_nonce_url( admin_url( 'admin.php?page=wp-autoplugin-fix&plugin=' . $item['plugin_path'] ), 'wp-autoplugin-fix-plugin', 'nonce' ) ), esc_html__( 'Fix', 'wp-autoplugin' ) );
		$actions['extend']  = sprintf( '<a href="%s">%s</a>', esc_url( wp_nonce_url( admin_url( 'admin.php?page=wp-autoplugin-extend&plugin=' . $item['plugin_path'] ), 'wp-autoplugin-extend-plugin', 'nonce' ) ), esc_html__( 'Extend', 'wp-autoplugin' ) );
		$actions['explain'] = sprintf( '<a href="%s">%s</a>', esc_url( wp_nonce_url( admin_url( 'admin.php?page=wp-autoplugin-explain&plugin=' . $item['plugin_path'] ), 'wp-autoplugin-explain-plugin', 'nonce' ) ), esc_html__( 'Explain', 'wp-autoplugin' ) );
		$actions['delete']  = sprintf( '<a href="%s">%s</a>', esc_url( wp_nonce_url( admin_url( 'admin.php?page=wp-autoplugin&action=delete&plugin=' . $item['plugin_path'] ), 'wp-autoplugin-activate-plugin', 'nonce' ) ), esc_html__( 'Delete', 'wp-autoplugin' ) );

		// Replicate the default Plugin List Table column rendering.
		return sprintf( '<strong>%1$s</strong> v%2$s %3$s %4$s', esc_html( $item['Name'] ), esc_html( $item['Version'] ), '<p>' . wp_kses_post( $item['Description'] ) . '</p>', $this->row_actions( $actions ) );
	}

	/**
	 * Prepare the list items (the plugins).
	 *
	 * @return void
	 */
	public function prepare_items() {

		// Set the columns.
		$columns               = $this->get_columns();
		$hidden                = [];
		$sortable              = [];
		$this->_column_headers = [ $columns, $hidden, $sortable ];

		$this->items = $this->get_plugins( false );
	}

	/**
	 * Get the list and details of installed plugins that were generated by WP-Autoplugin.
	 *
	 * @param bool $all Whether to get all plugins or just the active ones.
	 *
	 * @return array
	 */
	public function get_plugins( $all = true ) {
		$plugins           = [];
		$autoplugins       = get_option( 'wp_autoplugins', [] );
		$autoplugins_clean = [];
		foreach ( $autoplugins as $plugin_path ) {
			if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_path ) ) {
				continue;
			}

			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_path );
			if ( empty( $plugin_data['Name'] ) ) {
				continue;
			}

			$plugin_data['plugin_path'] = $plugin_path;
			$plugin_data['is_active']   = is_plugin_active( $plugin_path );
			$autoplugins_clean[]        = $plugin_path;

			if ( ! $all ) {
				$status = isset( $_REQUEST['status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification is not needed here.
				if ( 'active' === $status && ! $plugin_data['is_active'] ) {
					continue;
				} elseif ( 'inactive' === $status && $plugin_data['is_active'] ) {
					continue;
				}
			}

			$plugins[] = $plugin_data;
		}

		// Update the option with the clean list of plugins.
		if ( $autoplugins_clean !== $autoplugins ) {
			update_option( 'wp_autoplugins', $autoplugins_clean );
		}

		return $plugins;
	}

	/**
	 * Add custom classes to some rows.
	 * This is used to highlight active plugins.
	 *
	 * @param array $item The current item.
	 * @return void
	 */
	public function single_row( $item ) {
		$class = $item['is_active'] ? 'active-plugin' : '';
		echo '<tr class="' . esc_attr( $class ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Empty table message.
	 *
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'No plugins found.', 'wp-autoplugin' );
	}

	/**
	 * Search plugins.
	 *
	 * @param string $text     The search box text.
	 * @param string $input_id The search box input ID.
	 * @return void
	 */
	public function search_box( $text, $input_id ) {
		$input_id = $input_id . '-search-input';
		parent::search_box( $text, $input_id );
	}

	/**
	 * Display the table navigation.
	 *
	 * @param string $which The location of the navigation.
	 * @return void
	 */
	public function display_tablenav( $which ) {
		// Display the filter links above the table (Active/Inactive).
		if ( 'top' === $which ) {
			echo '<ul class="subsubsub">';
			$plugins        = $this->get_plugins();
			$active_count   = 0;
			$inactive_count = 0;
			foreach ( $plugins as $plugin ) {
				if ( $plugin['is_active'] ) {
					++$active_count;
				} else {
					++$inactive_count;
				}
			}

			$active_url   = add_query_arg( 'status', 'active' );
			$inactive_url = add_query_arg( 'status', 'inactive' );
			$all_url      = remove_query_arg( 'status' );

			$status = isset( $_REQUEST['status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification is not needed here.

			echo '<li class="all"><a href="' . esc_url( $all_url ) . '" class="' . ( empty( $status ) ? 'current' : '' ) . '">' . esc_html__( 'All', 'wp-autoplugin' ) . ' <span class="count">(' . esc_html( count( $plugins ) ) . ')</span></a> |</li>';
			echo '<li class="active"><a href="' . esc_url( $active_url ) . '" class="' . ( 'active' === $status ? 'current' : '' ) . '">' . esc_html__( 'Active', 'wp-autoplugin' ) . ' <span class="count">(' . esc_html( $active_count ) . ')</span></a> |</li>';
			echo '<li class="inactive"><a href="' . esc_url( $inactive_url ) . '" class="' . ( 'inactive' === $status ? 'current' : '' ) . '">' . esc_html__( 'Inactive', 'wp-autoplugin' ) . ' <span class="count">(' . esc_html( $inactive_count ) . ')</span></a></li>';
			echo '</ul>';
		}
	}
}
