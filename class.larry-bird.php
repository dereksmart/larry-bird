<?php

// Include this here so that other plugins can extend it if they like.
require_once( dirname(__FILE__) . '/class.larry-bird-posts.php' );

class Larry_Bird {
	static $instance;
	static $num_results = 5;

	function __construct() {
		self::$instance = $this;
		add_action( 'wp_loaded',          array( $this, 'wp_loaded' ) );
		add_action( 'admin_init',         array( $this, 'add_providers' ) );
		add_action( 'larry-bird_admin_menu', array( $this, 'larry-bird_admin_menu' ) );
		add_action( 'admin_menu',         array( $this, 'admin_menu' ), 20 );
		if ( ! wp_is_mobile() ) {
			add_action( 'admin_bar_menu', array( $this, 'admin_bar_search' ), 4 );
		}
		add_filter( 'larry_bird_num_results', array( $this, 'larry_bird_num_results' ) );
	}

	static function add_providers() {
		// larry-bird-posts.php is included above, so that other plugins can more easily extend it.
		new Larry_Bird_Posts;
		new Larry_Bird_Posts( 'page' );

		require_once( dirname(__FILE__) . '/class.larry-bird-comments.php' );
		new Larry_Bird_Comments;

		if ( current_user_can( 'upload_files' ) ) {
			require_once( dirname(__FILE__) . '/class.larry-bird-media.php' );
			new Larry_Bird_Media;
		}

		if ( current_user_can( 'install_plugins' ) ) {
			require_once( dirname(__FILE__) . '/class.larry-bird-plugins.php' );
			new Larry_Bird_Plugins;
		}

		/**
		 * Fires after each default larry-bird provider has been required.
		 * Can be used to add your own Larry Bird provider.
		 *
		 * @since 0.1
		 */
		do_action( 'larry-bird_add_providers' );
	}

	static function larry_bird_num_results( $num ) {
		return self::$num_results;
	}

	function wp_loaded() {
		$deps = null;
		if ( wp_style_is( 'genericons', 'registered' ) ) {
			$deps = array( 'genericons' );
		}
		if ( is_rtl() ) {
			wp_register_style( 'larry-bird-admin', plugins_url( 'css/larry-bird-rtl.css', __FILE__ ), $deps );
		} else {
			wp_register_style( 'larry-bird-admin', plugins_url( 'css/larry-bird.css', __FILE__ ), $deps );
		}

	}

	function larry_bird_admin_menu() {
		remove_submenu_page( 'index.php', 'larry-bird' );
		$this->slug = add_submenu_page( 'larry-bird', __( 'Larry Bird', 'larry-bird' ), __( 'Larry Bird', 'larry-bird' ), 'edit_posts', 'larry-bird', array( $this, 'larry_bird_page' ) );
		add_action( "admin_print_styles-{$this->slug}", array( $this, 'admin_print_styles_larry-bird' ) );
	}

	function admin_menu() {
		$this->slug = add_dashboard_page( __( 'Larry Bird', 'larry-bird' ), __( 'Larry Bird', 'larry-bird' ), 'edit_posts', 'larry-bird', array( $this, 'larry_bird_page' ) );
		add_action( "admin_print_styles-{$this->slug}", array( $this, 'admin_print_styles' ) );
	}

	function admin_print_styles() {
		wp_enqueue_style( 'larry-bird-admin' );
	}

	function admin_print_styles_larry_bird() {
		wp_enqueue_style( 'larry-bird-admin' );
		wp_enqueue_style( 'larry-bird-larry-bird' );
	}

	function larry_bird_page() {
		$results = array();
		$s = isset( $_GET['s'] ) ? $_GET['s'] : '';
		if ( $s ) {
			/**
			 * Filter the results returned for a given Larry Bird search query.
			 *
			 * @param array $results Array of Larry Bird results.
			 * @param string $s Search parameter.
			 */
			$results = apply_filters( 'larry-bird_results', $results, $s );
		}
		/**
		 * Filter the number of results displayed for each Larry Bird searched section.
		 *
		 * @module minileven
		 *
		 * @since 2.3.0
		 *
		 * @param int 5 Number of results displayed for each Larry Bird searched section.
		 */
		$num_results = intval( apply_filters( 'larry_bird_num_results', 5 ) );
		?>
		<div class="wrap">
			<h2 class="page-title"><?php esc_html_e( 'Larry Bird', 'larry-bird' ); ?> <small><?php esc_html_e( 'search everything', 'larry-bird' ); ?></small></h2>
			<br class="clear" />
			<?php echo self::get_larry_bird_form( array(
				'form_class'         => 'larry-bird-form',
				'search_class'       => 'larry-bird',
				'search_placeholder' => '',
				'submit_class'       => 'larry-bird-submit',
				'alternate_submit'   => true,
			) ); ?>
			<?php if ( ! empty( $results ) ): ?>
				<h3 id="results-title"><?php esc_html_e( 'Results:', 'larry-bird' ); ?></h3>
				<div class="jump-to"><strong><?php esc_html_e( 'Jump to:', 'larry-bird' ); ?></strong>
					<?php foreach( $results as $label => $result ) : ?>
						<a href="#result-<?php echo sanitize_title( $label ); ?>"><?php echo esc_html( $label ); ?></a>
					<?php endforeach; ?>
				</div>
				<br class="clear" />
				<script>var search_term = '<?php echo esc_js( $s ); ?>', num_results = <?php echo $num_results; ?>;</script>
				<ul class="larry-bird-results">
					<?php foreach( $results as $label => $result ) : ?>
						<li id="result-<?php echo sanitize_title( $label ); ?>" data-label="<?php echo esc_attr( $label ); ?>">
							<?php echo $result; ?>
							<a class="back-to-top" href="#results-title"><?php esc_html_e( 'Back to Top &uarr;', 'larry-bird' ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div><!-- /wrap -->
		<?php
	}

	function admin_bar_search( $wp_admin_bar ) {
		if (
			! is_admin() ||
			! current_user_can( 'edit_posts' ) ||
			(
				function_exists( 'wpcom_use_wpadmin_flows' ) &&
				! wpcom_use_wpadmin_flows()
			)
		)
			return;

		$form = self::get_larry_bird_form( array(
			'form_id'      => 'adminbarsearch',
			'search_id'    => 'adminbar-search',
			'search_class' => 'adminbar-input',
			'submit_class' => 'adminbar-button',
		) );

		$form .= "<style>
				#adminbar-search::-webkit-input-placeholder,
				#adminbar-search:-moz-placeholder,
				#adminbar-search::-moz-placeholder,
				#adminbar-search:-ms-input-placeholder {
					text-shadow: none;
				}
			</style>";

		$wp_admin_bar->add_menu( array(
			'parent' => 'top-secondary',
			'id'     => 'search',
			'title'  => $form,
			'meta'   => array(
				'class'    => 'admin-bar-search',
				'tabindex' => -1,
			)
		) );
	}

	static function get_larry_bird_form( $args = array() ) {
		$defaults = array(
			'form_id'            => null,
			'form_class'         => null,
			'search_class'       => null,
			'search_id'          => null,
			'search_value'       => isset( $_REQUEST['s'] ) ? $_REQUEST['s'] : null,
			'search_placeholder' => __( 'Search Everything', 'larry-bird' ),
			'submit_class'       => 'button',
			'submit_value'       => __( 'Search', 'larry-bird' ),
			'alternate_submit'   => false,
		);
		extract( array_map( 'esc_attr', wp_parse_args( $args, $defaults ) ) );

		$rand = rand();
		if ( empty( $form_id ) )
			$form_id = "larry-bird_form_$rand";
		if ( empty( $search_id ) )
			$search_id = "larry-bird_search_$rand";

		ob_start();
		?>

		<form action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" method="get" class="<?php echo $form_class; ?>" id="<?php echo $form_id; ?>">
			<input type="hidden" name="page" value="larry-bird" />
			<input name="s" type="search" class="<?php echo $search_class; ?>" id="<?php echo $search_id; ?>" value="<?php echo $search_value; ?>" placeholder="<?php echo $search_placeholder; ?>" />
			<?php if ( $alternate_submit ) : ?>
				<button type="submit" class="<?php echo $submit_class; ?>"><span><?php echo $submit_value; ?></span></button>
			<?php else : ?>
				<input type="submit" class="<?php echo $submit_class; ?>" value="<?php echo $submit_value; ?>" />
			<?php endif; ?>
		</form>

		<?php
		/**
		 * Filters the Larry Bird search form output.
		 *
		 * @param string ob_get_clean() Larry Bird search form output.
		 * @param array $args Array of arguments to pass to the form to overwrite the default form parameters.
		 * @param array $defaults Array of default form parameters.
		 */
		return apply_filters( 'get_larry-bird_form', ob_get_clean(), $args, $defaults );
	}

}
new Larry_Bird;
