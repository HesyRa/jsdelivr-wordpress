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
		return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', JsDelivrCdn::SOURCE_LIST, $item['id'] );
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
			/*case 'original_url':
				// @TODO Fix action link, its broken
				$show_action = 'activate';

				$actions = [
					'deactivate' => sprintf( '<span class="deactivate"><a href="?page=%s&action=%s&source_list[]=%s">%s</a></span>', 'jsdelivrcdn', 'deactivate', $item['id'], __( 'Deactivate' ) ),
					'activate'   => sprintf( '<span class="activate"><a href="?page=%s&action=%s&id=%s">%s</a></span>', 'jsdelivrcdn', 'activate', $item['id'], __( 'Activate' ) ),
				];

				if ( $item['active'] ) {
					$show_action = 'deactivate';
				}

				return sprintf( '<strong>%s</strong><div class="row-actions visible">%s</div>', $item[ $column_name ], $actions[ $show_action ] );*/
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
			'activate'   => __( 'Activate' ),
			'deactivate' => __( 'Deactivate' ),
		);
		if ( JsDelivrCdn::is_advance_mode() ) {
			$actions['clear']  = __( 'Clear' );
			$actions['delete'] = __( 'Delete' );
		}
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
		$handle_arr = [];
		if ( isset( $_REQUEST[ JsDelivrCdn::SOURCE_LIST ] ) && ! empty( $_REQUEST[ JsDelivrCdn::SOURCE_LIST ] ) ) {
			$handle_arr =  array_map( 'sanitize_text_field', array_map( 'wp_unslash', $_REQUEST[ JsDelivrCdn::SOURCE_LIST ] ) );
		}

		if ( $action && ! empty( $handle_arr ) ) {
			switch ( $action ) {
				case 'clear':
					JsDelivrCdn::clear_sources( $handle_arr );
					break;
				case 'delete':
					JsDelivrCdn::remove_sources( $handle_arr );
					break;
				case 'activate':
					JsDelivrCdn::activate_sources( $handle_arr );
					break;
				case 'deactivate':
					JsDelivrCdn::deactivate_sources( $handle_arr );
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
		$per_page = 5;
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

		if ( isset( $_REQUEST['_wpnonce'] ) ) {
			wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'bulk-' . $this->_args['plural'] );
		}

		$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'original_url';

		$order = ( ! empty( $_REQUEST['order'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) : 'asc';

		$filter = ( ! empty( $_REQUEST['filter'] ) ) ? sanitize_text_field( wp_unslash( $_REQUEST['filter'] ) ) : '';

		/**
		 * Get Items From Plugin
		 */
		$data = JsDelivrCdn::get_source( $filter );

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
				'filter'      => $filter,
			)
		);
	}

	/**
	 * Get views
	 *
	 * @return array
	 */
	protected function get_views() {
		return [
			'all'      => '<a href="/wp-admin/admin.php?page=jsdelivrcdn&filter=">' . __( 'All' ) . '</a>',
			// translators: %s count of active.
			'active'   => '<a href="/wp-admin/admin.php?page=jsdelivrcdn&filter=active">' . sprintf( __( 'Active <span class="count">(%s)</span>' ), count( JsDelivrCdn::get_source( 'active' ) ) ) . '</a>',
			// translators: %s count of inactive.
			'inactive' => '<a href="/wp-admin/admin.php?page=jsdelivrcdn&filter=inactive">' . sprintf( __( 'Inactive <span class="count">(%s)</span>' ), count( JsDelivrCdn::get_source( 'inactive' ) ) ) . '</a>',
		];
	}
}
