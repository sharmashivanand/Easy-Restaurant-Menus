<?php

namespace epv;

final class Settings_Page {

    public $settings_page = '';

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'admin_enqueue_scripts', 'wp_enqueue_media' );
	}

    function enqueue_scripts($hook) {
        if($hook == 'settings_page_epv') {
            wp_enqueue_script( 'wprtsp-fp', EPV_URI .'admin/script.js', array(), null, true);
        }
    }

    public function admin_menu() {

		// Create the settings page.
		$this->settings_page = add_options_page(
			esc_html__( 'Vouchers', 'epv' ),
			esc_html__( 'Vouchers', 'epv' ),
			'manage_options',
			'epv',
			array( $this, 'settings_page' )
		);

		if ( $this->settings_page ) {

			// Register settings.
			add_action( 'admin_init', array( $this, 'register_settings' ) );
		}
	}

	function register_settings() {

		// Register the setting.
		register_setting( 'series_settings', 'series_settings', array( $this, 'validate_settings' ) );

		/* === Settings Sections === */

		add_settings_section( 'background', 'Background', array( $this, 'background_section_text' ), $this->settings_page );
		

		/* === Settings Fields === */

		// Reading section fields.
		add_settings_field( 'background_image', 'Background Image', array( $this, 'background_image_html' ), $this->settings_page, 'background' );
		//add_settings_field( 'posts_orderby',  esc_html__( 'Sort By',         'series' ), array( $this, 'field_posts_orderby'  ), $this->settings_page, 'reading' );
		//add_settings_field( 'posts_order',    esc_html__( 'Order',           'series' ), array( $this, 'field_posts_order'    ), $this->settings_page, 'reading' );

		// Permalinks section fields.
		//add_settings_field( 'series_rewrite_base',  esc_html__( 'Series Slug', 'series' ), array( $this, 'field_series_rewrite_base' ), $this->settings_page, 'permalinks' );
    }
    
    function background_section_text(){
        ?>
        Background Section Text
        <?php
    }
    function background_image_html(){
        ?>
        <div class="upload">
            <img data-src="" src="" width="" height="" />
            <div>
                <input type="hidden" name="epv[background]" value="<?php echo get_setting('background'); ?>" />
                <button type="submit" class="upload_image_button button">Upload</button>
                <button type="submit" class="remove_image_button button">&times;</button>
            </div>
        </div>
    
    <?php
    }

    function validate_settings( $settings ) {

		// Text boxes.
		//$settings['series_rewrite_base'] = $settings['series_rewrite_base'] ? trim( strip_tags( $settings['series_rewrite_base'] ), '/' ) : '';

		// Numbers.
		//$posts_per_page = intval( $settings['posts_per_page'] );
		//$settings['posts_per_page'] = -2 < $posts_per_page ? $posts_per_page : 10;

		// Select boxes.
		//$settings['posts_orderby'] = isset( $settings['posts_orderby'] ) ? strip_tags( $settings['posts_orderby'] ) : 'date';
		//$settings['posts_order']   = isset( $settings['posts_order'] )   ? strip_tags( $settings['posts_order']   ) : 'DESC';

		/* === Handle Permalink Conflicts ===*/

		// Return the validated/sanitized settings.
		return $settings;
	}

	public function section_reading() { ?>
		<p class="description">
			<?php esc_html_e( 'Reading settings for the front end of your site.', 'series' ); ?>
		</p>
	<?php }


	public function settings_page() { 
        if ( isset( $_GET['settings-updated'] ) ) {
            flush_rewrite_rules();
        }
        ?>

		<div class="wrap">
			<h1>Settings</h1>

			<form method="post" action="options.php">
				<?php settings_fields( 'epv_settings' ); ?>
				<?php do_settings_sections( $this->settings_page ); ?>
				<?php submit_button(); ?>
			</form>

		</div><!-- wrap -->
	<?php }

	public static function get_instance() {

		static $instance = null;

		if ( is_null( $instance ) )
			$instance = new self;

		return $instance;
	}
}

Settings_Page::get_instance();