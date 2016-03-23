<?php

if ( ! class_exists( 'WP_List_Table' ) )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

class Larry_Bird_Posts extends WP_List_Table {
	public $post_type = 'post',
	    $post_type_object;

	function __construct( $post_type = 'post' ) {
		$this->post_type = $post_type;
		add_filter( 'larry-bird_results', array( $this, 'search'), 10, 2 );

		// Push 'post_type_obj' to accepted fields for WP_List_Table (since WP 4.2)
		global $wp_version;
		if ( version_compare( $wp_version, '4.2-z', '>=' ) && $this->compat_fields && is_array( $this->compat_fields ) ) {
			array_push( $this->compat_fields, 'post_type_obj', 'posts' );
		}
	}

	function search( $results, $search_term ) {
		if ( ! post_type_exists( $this->post_type ) )
			return $results;

		parent::__construct();

		$this->post_type_obj = get_post_type_object( $this->post_type );

		$search_url = esc_url( admin_url( sprintf( 'edit.php?post_type=%s&s=%s', urlencode( $this->post_type_obj->name ), urlencode( $search_term ) ) ) );
		$search_link = sprintf( ' <a href="%s" class="add-new-h2">%s</a>', $search_url, esc_html( $this->post_type_obj->labels->search_items ) );
		$html = '<h2>' . esc_html( $this->post_type_obj->labels->name ) . $search_link .'</h2>';

		/** This action is documented in modules/larry-bird/larry-bird-core.php */
		$num_results = apply_filters( 'larry_bird_num_results', 5 );

		$this->posts = get_posts( array(
			's'                => $search_term,
			'post_type'        => $this->post_type,
			'posts_per_page'   => $num_results,
			'post_status'      => 'any',
			'suppress_filters' => false,
		) );

		$this->prepare_items();

		ob_start();
		$this->display();
		$html .= ob_get_clean();

		$results[ $this->post_type_obj->labels->name ] = $html;
		return $results;
	}

	function get_columns() {
		$columns = array(
		#	'id' => __('ID', 'larry-bird'),
			'post_title' => __('Title', 'larry-bird'),
			'snippet' => __('Snippet', 'larry-bird'),
			'date' => __('Date', 'larry-bird'),
		);
		return $columns;
	}

	function prepare_items() {
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items = $this->posts;
	}

	function column_post_title( $post ) {
		$actions = array();
		if ( current_user_can( $this->post_type_obj->cap->edit_post, $post ) ) {
			$post_title = sprintf( '<a href="%s">%s</a>', esc_url( get_edit_post_link( $post->ID ) ), wptexturize( $post->post_title ) );
			$actions['edit'] = sprintf( '<a href="%s">%s</a>', esc_url( get_edit_post_link( $post->ID ) ), esc_html( $this->post_type_obj->labels->edit_item ) );
		} else {
			$post_title = wptexturize( $post->post_title );
		}
		if ( current_user_can( $this->post_type_obj->cap->delete_post, $post ) ) {
			$actions['delete'] = sprintf( '<a href="%s">%s</a>', esc_url( get_delete_post_link( $post->ID ) ), esc_html__('Trash', 'larry-bird') );
		}
		$actions['view'] = sprintf( '<a href="%s">%s</a>', esc_url( get_permalink( $post->ID ) ), esc_html( $this->post_type_obj->labels->view_item ) );
		return $post_title . $this->row_actions( $actions );
	}

	function column_date( $post ) {
		$html = '';

		if ( '0000-00-00 00:00:00' == $post->post_date ) {
			$t_time = $h_time = __('Unpublished', 'larry-bird');
			$time_diff = 0;
		} else {
			$t_time = date( __('Y/m/d g:i:s A', 'larry-bird'), mysql2date( 'G', $post->post_date ) );
			$m_time = $post->post_date;
			$time = get_post_time( 'G', true, $post );

			$time_diff = time() - $time;

			if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS )
				$h_time = sprintf( __('%s ago', 'larry-bird'), human_time_diff( $time ) );
			else
				$h_time = mysql2date( __('Y/m/d', 'larry-bird'), $m_time );
		}

		$html .= '<abbr title="' . esc_attr( $t_time ) . '">' . esc_html( $h_time ) . '</abbr>';
		$html .= '<br />';
		if ( 'publish' == $post->post_status ) {
			$html .= esc_html__('Published', 'larry-bird');
		} elseif ( 'future' == $post->post_status ) {
			if ( $time_diff > 0 )
				$html .= '<strong class="attention">' . esc_html__('Missed schedule', 'larry-bird') . '</strong>';
			else
				$html .= esc_html__('Scheduled', 'larry-bird');
		} else {
			$html .= esc_html__('Last Modified', 'larry-bird');
		}
		return $html;
	}

	function column_default( $post, $column_name ) {
		switch ( $column_name ) {
			case 'id':
				return $post->ID;
			case 'post_title': // Will never happen, class method overrides.
				return $post->post_title;
			case 'snippet':
				return wp_trim_words( $post->post_content, 55 );
			case 'date': // Will never happen, class method overrides.
				$d = get_option('date_format');
				$t = get_option('time_format');
				return get_post_modified_time( $d, 0, $post, 1 ) . ' @ ' . get_post_modified_time( $t, 0, $post, 1 );
			default:
				return print_r( $post, true );
		}
	}
}