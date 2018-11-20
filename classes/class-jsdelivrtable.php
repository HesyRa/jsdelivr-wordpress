<?php
/**
 * JsdelivrTable class file
 *
 * @package JsDelivrCdn
 */

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class JsdelivrTable
 */
class JsdelivrTable extends WP_List_Table {
	/**
	 * JsdelivrTable constructor.
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => JsDelivrCdn::SOURCE_LIST,
			'ajax'     => true,
		) );
	}

	/**
	 * Get table classes
	 *
	 * @return string
	 */
	public function get_table_classes() {
		$classes = parent::get_table_classes();
		$classes = str_replace( 'fixed', '', $classes );
		return $classes;
	}
	/**
	 * Get table columns
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns                 = [];
		$columns['cb']           = '<input type="checkbox" />';
		$columns['original_url'] = 'Origin URL';
		if ( JsDelivrCdn::is_advance_mode() ) {
			$columns['handle'] = __( 'Name' );
			$columns['ver']    = __( 'Version' );
			$columns['type']   = __( 'Type' );
		}
		$columns['jsdelivr_url'] = 'JsDelivr URL';
		return $columns;
	}

	/**
	 * Checkbox column
	 *
	 * @param object $item Item.
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item['id'] );
	}

	/**
	 * Method column_default let at your choice the rendering of everyone of column
	 *
	 * @param object $item Item.
	 * @param string $column_name Column name.
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {

		switch ( $column_name ) {
			case 'original_url':
				$show_action = 'activate';

				$actions = [
					'deactivate' => sprintf( '<span class="deactivate"><a href="?page=%s&action=%s&source_list[]=%s">%s</a></span>', 'jsdelivrcdn', 'deactivate', $item['id'], __( 'Deactivate' ) ),
					'activate'   => sprintf( '<span class="activate"><a href="?page=%s&action=%s&id=%s">%s</a></span>', 'jsdelivrcdn', 'activate', $item['id'], __( 'Activate' ) ),
				];

				if ( $item['active'] ) {
					$show_action = 'deactivate';
				}

				return sprintf( '<strong>%s</strong><div class="row-actions visible">%s</div>', $item[ $column_name ], $actions[ $show_action ] );
			default:
				return $item[ $column_name ];
		}
	}

	/**
	 * Array contains slug columns that you want hidden
	 *
	 * @var array
	 */
	private $hidden_columns = array( 'id' );
	/**
	 * The array is associative :
	 * keys are slug columns
	 * values are array of slug and a boolean that indicates if is sorted yet
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'handle'       => array( 'handle', false ),
			'active'       => array( 'active', false ),
			'original_url' => array( 'original_url', true ),
			'jsdelivr_url' => array( 'jsdelivr_url', false ),
		);
	}

	/**
	 * Get action list
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		$actions = array(
			'delete'     => __( 'Delete' ),
			'clear'      => __( 'Clear' ),
			'activate'   => __( 'Activate' ),
			'deactivate' => __( 'Deactivate' ),
		);
		return $actions;
	}

	/**
	 * Proccess actions
	 */
	public function process_bulk_action() {

		$action = $this->current_action();

		if ( isset( $_REQUEST['_wpnonce'] ) ) {
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'bulk-' . $this->_args['plural'] );
		}
		if ( $action ) {
			switch ( $action ) {
				case 'clear':
					if ( isset( $_REQUEST[ JsDelivrCdn::SOURCE_LIST ] ) && ! empty( $_REQUEST[ JsDelivrCdn::SOURCE_LIST ] ) ) {
						JsDelivrCdn::clear_sources( array_map( 'sanitize_text_field', array_map( 'wp_unslash', $_REQUEST[ JsDelivrCdn::SOURCE_LIST ] ) ) );
					}
					break;
				default:
					wp_die( esc_attr( $action ) );
			}
		}

	}
	/**
	 * Set table items
	 */
	public function prepare_items() {
		/**
		 * How many records for page do you want to show?
		 */
		$per_page = 10;
		/**
		 * Define of column_headers. It's an array that contains:
		 * columns of List Table
		 * hiddens columns of table
		 * sortable columns of table
		 * optionally primary column of table
		 */
		$columns = $this->get_columns();

		$hidden = $this->hidden_columns;

		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		/**
		 * Process bulk actions
		 */
		$this->process_bulk_action();

		/**
		 * Get Items From Plugin
		 */
		$data = JsDelivrCdn::get_source();

		if ( isset( $_REQUEST['_wpnonce'] ) ) {
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'bulk-' . $this->_args['plural'] );
		}

		/**
		 * Sort items
		 *
		 * @param array $a first.
		 * @param array $b second.
		 * @return int
		 */
		function usort_reorder( $a, $b ) {
			$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'original_url';

			$order = ( ! empty( $_REQUEST['order'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'asc';

			$result = strcmp( $a[ $orderby ], $b[ $orderby ] );

			return ( 'asc' === $order ) ? $result : -$result;
		}
		usort( $data, 'usort_reorder' );
		/**
		 * Get current page calling get_pagenum method
		 */
		$current_page = $this->get_pagenum();

		$total_items = count( $data );

		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

		$this->items = $data;

		$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'original_url';

		$order = ( ! empty( $_REQUEST['order'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'asc';
		/**
		 * Call to _set_pagination_args method for informations about
		 * total items, items for page, total pages and ordering
		 */
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
				'orderby'     => $orderby,
				'order'       => $order,
			)
		);
	}

	/**
	 * Ajax response
	 */
	/*public function ajax_response() {
		check_ajax_referer( 'ajax-custom-list-nonce', '_ajax_custom_list_nonce' );
		$this->prepare_items();

		extract( $this->_args );
		extract( $this->_pagination_args, EXTR_SKIP );

		ob_start();
		if ( ! empty( $_REQUEST['no_placeholder'] ) ) {
			$this->display_rows();
		} else {
			$this->display_rows_or_placeholder();
		}
		$rows = ob_get_clean();

		ob_start();
		$this->print_column_headers();
		$headers = ob_get_clean();

		ob_start();
		$this->pagination( 'top' );
		$pagination_top = ob_get_clean();

		ob_start();
		$this->pagination( 'bottom' );
		$pagination_bottom = ob_get_clean();

		$response = array( 'rows' => $rows );

		$response['pagination']['top'] = $pagination_top;

		$response['pagination']['bottom'] = $pagination_bottom;

		$response['column_headers'] = $headers;

		if ( isset( $total_items ) ) {
			$response['total_items_i18n'] = sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) );
		}

		if ( isset( $total_pages ) ) {
			$response['total_pages'] = $total_pages;

			$response['total_pages_i18n'] = number_format_i18n( $total_pages );
		}

		die( wp_json_encode( $response ) );
	} */
}
