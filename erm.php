<?php
/**
 * Plugin Name: Easy Restaurant Menus
 * Description: Easily create and edit restaurant menus
 * Version:     0.2
 * Plugin URI:  https://wordpress.org/plugins/easy-restaurant-menus/
 * Author:      Shivanand Sharma
 * Author URI:  https://www.converticacommerce.com
 * Text Domain: erm
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 * Tags: restaurant, food, menus
 */

/*
Copyright 2018 Shivanand Sharma

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/



define( 'ERM_URI', trailingslashit(plugin_dir_url( __FILE__ )));
define( 'ERM_DIR', trailingslashit(plugin_dir_url( __FILE__ )));

final class Easy_Restaurant_Menus {

    public $dir = '';
    public $uri = '';

    static function get_instance() {
		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self;
			$instance->setup();
			$instance->setup_actions();
		}
		return $instance;
    }

    function __construct() {}

    function setup() {
        // Main plugin directory path and URI.
        $this->dir = trailingslashit( plugin_dir_path( __FILE__ ) );
        $this->uri  = trailingslashit( plugin_dir_url(  __FILE__ ) );
    }

    function includes() {
    }
    
    function setup_actions() {
        add_action( 'init', array( $this, 'register_post_types' ));
        add_action( 'init', array( $this, 'shortcodes_init' ));
        add_action( 'add_meta_boxes', array( $this,'restaurantmenu_meta_boxes' ) );
        add_action( 'save_post', array($this, 'save_restaurantmenu_meta_box_data' ));

        add_action( 'admin_print_scripts-post-new.php', array( $this, 'restaurantmenu_admin_script' ), 11 );
        add_action( 'admin_print_scripts-post.php', array( $this, 'restaurantmenu_admin_script' ), 11 );

        register_activation_hook( __FILE__, array( $this, 'activation' ) );
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links'), 10, 5 );
        add_action( 'admin_head', array( $this, 'admin_style' ) );
    }

    function admin_style(){
        ?>
        <style type="text/css">#restaurantmenu-menu .hndle, #restaurantmenu-shortcode .hndle { border-left: 5px solid #ffbf00; }</style>
        <?php
    }

    function shortcodes_init(){
        add_shortcode('easy-menu', array($this,'easy_menu_shortcode'));
    }
    
    function easy_menu_shortcode( $atts = [] ) {
        $atts = shortcode_atts( array(
            'id' => '',
            'format' => 'list'
        ), $atts );

        $menu_id = $atts['id'];
        $menu = get_post_meta( $menu_id, 'erm_items', true );
        
        ob_start();
        if(current_user_can('edit_others_posts')){
            echo '<a class="erm-edit" href="'.get_edit_post_link( $menu_id ).'">Edit Menu</a>';
        }
        echo apply_filters('erm_show_menu_title', '<h2>'.get_the_title($menu_id).'</h2>');
        if( empty($atts->format) || $atts->format == 'list' ) {
            echo '<dl>';
            foreach($menu as $arr_items) {
                echo '<dt>'.$arr_items['item'].'</dt>';
                echo '<dd><span class="erm-item-description">'.$arr_items['description']. '</span> <span class="erm-item-price">' .$arr_items['price'].'</span>/<span class="erm-item-unit">'.$arr_items['unit'].'</dd>';
            }
            echo '</dl>';
            return ob_get_clean();
        }
        if( $atts->format == 'table' ) {
            echo '<table id="erm-'.$menu_id.'"><thead class="erm_menu_header"><tr><th class="erm_name_header">'.$this->get_name_header().'</th><th class="erm_description_header">'.$this->get_description_header().'</th><th class="erm_price_header">'.$this->get_price_header().'</th><th class="erm_unit_header">'.$this->get_unit_header().'</th></tr></thead>';
            foreach($menu as $arr_items) {
                echo '<tr>';
                foreach($arr_items as $item) {
                    echo '<td>'.$item.'</td>';
                }
                echo '</tr>';
            }
            echo '</table>';
            return ob_get_clean();
        }
    }

    function get_name_header(){
        return apply_filters('erm_get_name_header', 'Name');
    }
    function get_description_header(){
        return apply_filters('erm_get_description_header', 'Description');
    }
    function get_price_header(){
        return apply_filters('erm_get_price_header', 'Price');
    }
    function get_unit_header(){
        return apply_filters('erm_get_unit_header', 'Unit');
    }

    function restaurantmenu_admin_script(){
        global $post_type;
        
        if($post_type == 'restaurantmenu') {
            wp_enqueue_media();
            //add_action( 'admin_enqueue_scripts', 'wp_enqueue_media' );
            wp_enqueue_script( 'erm', ERM_URI .'admin/script.js', array(), null, true);
        }
    }

        
    function get_setting($setting) {
        $defaults = defaults();
        $settings = wp_parse_args( get_option( 'epv', $defaults ), $defaults );
        return isset( $settings[ $setting ] ) ? $settings[ $setting ] : false;
    }

    function defaults(){
        $defaults = array(
            'background' => EPV_URI . 'assets/background.png',
        );
        return $defaults;
    }

    function save_restaurantmenu_meta_box_data($post_id){
        
        if ( ! isset( $_POST['erm_repeatableitems_meta_box_nonce'] ) ||
            ! wp_verify_nonce( $_POST['erm_repeatableitems_meta_box_nonce'], 'erm_repeatableitems_meta_box_nonce' ) ) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $old = get_post_meta($post_id, 'erm_items', true);
        $new = array();
        
        $items = $_POST['item'];
        $descriptions = $_POST['description'];
        $prices = $_POST['price'];
        $units = $_POST['unit'];
        
        $count = count( $items );
        
        for ( $i = 0; $i < $count; $i++ ) {
            if ( $items[$i] != '' ) {
                $new[$i]['item'] = stripslashes( strip_tags( $items[$i] ) );
                $new[$i]['description'] = stripslashes( strip_tags( $descriptions[$i] ) );
                $new[$i]['price'] = stripslashes( strip_tags( $prices[$i] ) );
                $new[$i]['unit'] = stripslashes( strip_tags( $units[$i] ) );
            }
        }
        if ( !empty( $new ) && $new != $old ) {
            update_post_meta( $post_id, 'erm_items', $new );
        }
        elseif ( empty($new) && $old ) {
            delete_post_meta( $post_id, 'erm_items', $old );
        }
    }

    function restaurantmenu_meta_boxes(){
        //add_meta_box( 'restaurantmenu-background', __( 'Print Size', 'erm' ), array($this, 'restaurantmenu_size_meta_box_callback'), 'restaurantmenu');
        add_meta_box( 'restaurantmenu-menu', __( 'Edit Easy Restaurant Menu', 'erm' ), array($this, 'restaurantmenu_value_meta_box_callback'), 'restaurantmenu', 'normal', 'high');
        add_meta_box( 'restaurantmenu-shortcode', __( 'Embed Easy Restaurant Menu Shortcode', 'erm' ), array($this, 'restaurantmenu_shortcode_meta_box_callback'), 'restaurantmenu', 'normal', 'high');
        
    }

    function restaurantmenu_shortcode_meta_box_callback(){
        global $post;
        echo '<p><strong>Insert the following shortcode in any page or post where you want the menu to show:</strong></p>';
        echo '<pre>[easy-menu id="'.$post->ID.'"]</pre>';
        echo '<p><strong>Additional Parameters:</strong></p>';
        echo '1. Show as a list: <pre>[easy-menu id="'.$post->ID.'"]</pre>';
        echo '2. Show as a table: <pre>[easy-menu id="'.$post->ID.'" format="table"]</pre>';
    }

    function restaurantmenu_value_meta_box_callback() {
        global $post;
        $erm_items = get_post_meta($post->ID, 'erm_items', true);
        //$options = $this->erm_get_sample_options();
        wp_nonce_field( 'erm_repeatableitems_meta_box_nonce', 'erm_repeatableitems_meta_box_nonce' );
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function( $ ){
            $( '#add-row' ).on('click', function() {
                var row = $( '.empty-row.screen-reader-text' ).clone(true);
                row.removeClass( 'empty-row screen-reader-text' );
                row.insertBefore( '#repeatableitems-fieldset-one tbody>tr:last' );
                return false;
            });
          
            $( '.remove-row' ).on('click', function() {
                $(this).parents('tr').remove();
                return false;
            });
        });
        </script>
        <table id="repeatableitems-fieldset-one" width="100%">
        <thead>
            <tr>
                <th width="40%">Item Name</th>
                <th width="40%">Description</th>
                <th width="12%">Price</th>
                <th width="40%">Unit</th>
                <th width="8%"></th>
            </tr>
        </thead>
        <tbody>
        <?php
        if ( $erm_items ) {
            foreach ( $erm_items as $field ) {
                ?>
                <tr>
                    <td><input type="text" class="widefat" name="item[]" value="<?php if($field['item'] != '') echo esc_attr( $field['item'] ); ?>" /></td>
                    <td><input type="text" class="widefat" name="description[]" value="<?php if($field['description'] != '') echo esc_attr( $field['description'] ); ?>" /></td>
                    <td><input type="text" class="widefat" name="price[]" value="<?php if($field['price'] != '') echo esc_attr( $field['price'] ); ?>" /></td>
                    <td><input type="text" class="widefat" name="unit[]" value="<?php if ($field['unit'] != '') echo esc_attr( $field['unit'] );  ?>" /></td>
                    <td><a class="button remove-row" href="#">Remove</a></td>
                </tr>
                <?php
            }
        }
        else { ?>
                <tr>
                    <td><input type="text" class="widefat" name="item[]" /></td>
                    <td><input type="text" class="widefat" name="description[]" /></td>
                    <td><input type="text" class="widefat" name="price[]" /></td>
                    <td><input type="text" class="widefat" name="unit[]" /></td>
                    <td><a class="button remove-row" href="#">Remove</a></td>
                </tr>
                <?php 
        }
        ?>
        
        <!-- empty hidden one for jQuery -->
        <tr class="empty-row screen-reader-text">
            <td><input type="text" class="widefat" name="item[]" /></td>
            <td><input type="text" class="widefat" name="description[]" /></td>
            <td><input type="text" class="widefat" name="price[]" /></td>
            <td><input type="text" class="widefat" name="unit[]" /></td>
            <td><a class="button remove-row" href="#">Remove</a></td>
        </tr>
        </tbody>
        </table>
        <p><a id="add-row" class="button" href="#">Add another</a></p>
        <?php
    }
    
    function llog($str){
        echo '<pre>';
        print_r($str);
        echo '</pre>';
    }

    function register_post_types(){

        $labels = array(
            'name'                  => __( 'Restaurant Menus',                   'erm' ),
            'singular_name'         => __( 'Restaurant Menu',                    'erm' ),
            'menu_name'             => __( 'Restaurant Menus',                   'erm' ),
            'name_admin_bar'        => __( 'Restaurant Menu',                    'erm' ),
            'add_new'               => __( 'Add New',                        'erm' ),
            'add_new_item'          => __( 'Add New Restaurant Menu',            'erm' ),
            'edit_item'             => __( 'Edit Restaurant Menu',               'erm' ),
            'new_item'              => __( 'New Restaurant Menu',                'erm' ),
            'view_item'             => __( 'View Restaurant Menu',               'erm' ),
            'view_items'            => __( 'View Restaurant Menus',              'erm' ),
            'search_items'          => __( 'Search Restaurant Menus',            'erm' ),
            'not_found'             => __( 'No Restaurant Menus found',          'erm' ),
            'not_found_in_trash'    => __( 'No Restaurant Menus found in trash', 'erm' ),
            'all_items'             => __( 'Restaurant Menus',                   'erm' ),
            'featured_image'        => __( 'Author Image',                   'erm' ),
            'set_featured_image'    => __( 'Set author image',               'erm' ),
            'remove_featured_image' => __( 'Remove author image',            'erm' ),
            'use_featured_image'    => __( 'Use as author image',            'erm' ),
            'insert_into_item'      => __( 'Insert into Restaurant Menu',        'erm' ),
            'uploaded_to_this_item' => __( 'Uploaded to this Restaurant Menu',   'erm' ),
            'filter_items_list'     => __( 'Filter Restaurant Menus list',       'erm' ),
            'items_list_navigation' => __( 'Restaurant Menus list navigation',   'erm' ),
            'items_list'            => __( 'Restaurant Menus list',              'erm' ),
        );

        $cpt_args = array(
            'description'         => 'Restaurant Menus',
            'public'              => false,
            'show_ui'               => true,
            'show_in_admin_bar'   => true,
            'show_in_rest'        => true,
            'menu_position'       => null,
            'menu_icon'           => 'dashicons-list-view',
            'can_export'          => true,
            'delete_with_user'    => false,
            'hierarchical'        => false,
            'has_archive'         => false,
            'labels'              => $labels,
            'template_lock' => true,
    
            // What features the post type supports.
            'supports' => array(
                'title',
                //'editor',
                //'thumbnail',
                // Theme/Plugin feature support.
                //'custom-background', // Custom Background Extended
                //'custom-header',     // Custom Header Extended
                //'wpcom-markdown',    // Jetpack Markdown
            )
        );

        register_post_type( 'restaurantmenu', apply_filters( 'restaurantmenus_post_type_args', $cpt_args ) );
    }

    function activation() {}
    
    function plugin_action_links($links) {
        $links[] = '<a href="https://www.converticacommerce.com?item_name=Donation%20for%20Easy%20Restaurant%20Menus&cmd=_xclick&currency_code=USD&business=shivanand@converticacommerce.com">Donate</a>';
        return $links;
    }    

}

// Let's roll!
$Easy_Restaurant_Menus = Easy_Restaurant_Menus::get_instance();
