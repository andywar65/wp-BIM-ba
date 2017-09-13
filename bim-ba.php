<?php
/*
Plugin Name: BIM-ba
Plugin URI: http://www.bim-ba.net/
Description: A very basic BIM (Building Information Modeling). You can model an apartment and visit it in Virtual Reality.
Version: 2.2.4
Author: andywar65
Author URI: http://www.andywar.net/
License: GPLv2
Text Domain: bim-ba
*/

/*  Copyright 2015-2017  Andrea Guerra  (email : 65@andywar.net)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
 * ACTIVATION
*/

register_activation_hook(__FILE__, 'bimba_activate');//registers plugin activation

function bimba_activate() {//plugin activation
	bimba_textdomain();
	//activate next line if you really want to use Studio Management package
	//register_gestudio_cpt_tax();//registers cpt and taxonomy for studiomng package
	bimba_3d_ambient_register_taxonomies();
	flush_rewrite_rules();
	bimba_fix_old_version_meta();
	bimba_insert_generic_materials();
}

/*
 * INITIALIZE
*/


/*
 * CONSTANTS
*/

define('BIMBA_VERSION', '2.2.4');
define('BIMBA_SLUG', 'bim-ba');

define('BIMBA_DS', DIRECTORY_SEPARATOR);
define('BIMBA_DIR', dirname( __FILE__ ) );
define('BIMBA_LIB_DIR', BIMBA_DIR . BIMBA_DS . 'lib');

/*
 * INTERNATIONALIZATION
*/

add_action ( 'init' , 'bimba_textdomain' );

function bimba_textdomain(){
	if (!load_plugin_textdomain( 'bim-ba', false, dirname( plugin_basename(__FILE__) ).'/languages/') ){
		load_plugin_textdomain( 'bim-ba', false, dirname( plugin_basename(__FILE__) ).'/languages/');
	}
	
}

/*
 * LIBRARIES
*/

//activate next lines if you really want to use Studio Management package
//require_once BIMBA_LIB_DIR . BIMBA_DS . 'gestudio/gestudio.php';
//require_once BIMBA_LIB_DIR . BIMBA_DS . 'gestudio/gestudio-settings-page.php';//loads Studio Management package
//require_once BIMBA_LIB_DIR . BIMBA_DS . 'gestudio/gestudio-operatori.php';
//require_once BIMBA_LIB_DIR . BIMBA_DS . 'gestudio/gestudio-lavori.php';
//require_once BIMBA_LIB_DIR . BIMBA_DS . 'gestudio/gestudio-rapporti.php';
//require_once BIMBA_LIB_DIR . BIMBA_DS . 'gestudio/gestudio-prime-note.php';
require_once BIMBA_LIB_DIR . BIMBA_DS . 'bimba-3d-cpt-metaboxes.php';
require_once BIMBA_LIB_DIR . BIMBA_DS . 'bimba-3d-ambient.php';
require_once BIMBA_LIB_DIR . BIMBA_DS . 'bimba-3d-material.php';
require_once BIMBA_LIB_DIR . BIMBA_DS . 'bimba-3d-plan.php';
require_once BIMBA_LIB_DIR . BIMBA_DS . 'bimba-3d-element.php';
require_once BIMBA_LIB_DIR . BIMBA_DS . 'steel-deck.php';

add_action ( 'plugins_loaded' , 'check_if_cmb2_existing' );

function check_if_cmb2_existing(){
	if( !class_exists( 'CMB2' ) AND file_exists( BIMBA_LIB_DIR . BIMBA_DS . 'cmb2/init.php' ) ){
		require_once BIMBA_LIB_DIR . BIMBA_DS . 'cmb2/init.php';
	}
	if( !class_exists( 'CMB2_Conditionals' ) AND file_exists( BIMBA_LIB_DIR . BIMBA_DS . 'cmb2-conditionals/cmb2-conditionals.php' ) ){
		require_once BIMBA_LIB_DIR . BIMBA_DS . 'cmb2-conditionals/cmb2-conditionals.php';
		if ( is_admin() ) {
			wp_enqueue_script( 'bimba-cmb2-conditionals', $src = plugins_url( 'lib/cmb2-conditionals/cmb2-conditionals.js', __FILE__ ) );//file was not loaded by plugin itself
		}
	}
}

//if ( ! is_admin() ) {//
	wp_enqueue_script( 'bimba-aframe', $src = plugins_url( 'js/aframe.min.js', __FILE__ ) );//insert js libraries in head of document (version 0.5 .0)
	//wp_enqueue_script( 'bimba-look-at', $src = plugins_url( 'js/aframe-look-at-component.min.js', __FILE__ ) );
	//wp_enqueue_script( 'bimba-camera-fly', $src = plugins_url( 'js/bimba-camera-fly.js', __FILE__ ), $in_footer = true );//TODO
//}

/*
 * ACTIVATION PROCEDURES
*/

function register_gestudio_cpt_tax(){
	
	//registra taxonomy categoria contabile del cpt prime note
	register_taxonomy('categoria-contabile' , 'prime-note', array ( 'hierarchical' => true, 'label' => __('Counting Category','bim-ba'),
	'query-var' => true,'rewrite' => true));
	
	$terms = array (__('Incomes','bim-ba'),
					__('Expenses','bim-ba'),
					__('Clearances','bim-ba') );
	foreach ( $terms as $term ){
		if ( !term_exists( $term, 'categoria-contabile' ) ){
			wp_insert_term( $term, 'categoria-contabile' );
		}
	}
	
	$parent_term_id = term_exists(__('Incomes','bim-ba'), 'categoria-contabile' );
	
	if ( !term_exists( __('Anticipations','bim-ba'), 'categoria-contabile' ) ){
		wp_insert_term( 'Anticipations', 'categoria-contabile', array('slug' => 'anticipations', 'parent'=> $parent_term_id['term_id']));
	}
	
	$parent_term_id = term_exists(__('Expenses','bim-ba'), 'categoria-contabile' );
	
	$terms = array (
			array(__('01-Taxes','bim-ba'),__('taxes','bim-ba')),
			array(__('02-Profits','bim-ba'),__('profits','bim-ba')),
			array(__('03-Restitutions','bim-ba'),__('restitutions','bim-ba')),
			array(__('04-Social Security','bim-ba'),__('social-security','bim-ba')),
			array(__('05-Salaries','bim-ba'),__('salaries','bim-ba')),
			array(__('06-Internal Contributors','bim-ba'),__('internal-contributors','bim-ba')),
			array(__('07-External Contributors','bim-ba'),__('external-contributors','bim-ba')),
			array(__('08-Loans','bim-ba'),__('loans','bim-ba')),
			array(__('09-Energy Supplies','bim-ba'),__('energy-supplies','bim-ba')),
			array(__('10-Communications','bim-ba'),__('communications','bim-ba')),
			array(__('11-Assistence','bim-ba'),__('assistence','bim-ba')),
			array(__('12-Office Supplies','bim-ba'),__('office-supplies','bim-ba')),
			array(__('13-Fleet','bim-ba'),__('fleet','bim-ba')),
			array(__('14-Policies','bim-ba'),__('policies','bim-ba')),
			array(__('15-Other','bim-ba'),__('other','bim-ba'))
	);
	foreach ( $terms as $term ){
		if ( !term_exists( $term[0], 'categoria-contabile' ) ){
			wp_insert_term( $term[0], 'categoria-contabile', array('slug' => $term[1], 'parent'=> $parent_term_id['term_id']));
		}
	}
	
	//registra taxonomy qualifiche del cpt operatori
	
	register_taxonomy('gstu-ruoli' , 'gstu-operatori', array ( 'hierarchical' => true, 'label' => __("Role of Operator", 'bim-ba' ),
		  'query-var' => true,'rewrite' => true));
	
	$terms = array (__('Studio','bim-ba'),
			__('Contributor','bim-ba'),
			__('Client','bim-ba'),
			__('Contractor','bim-ba'),
			__('Bank','bim-ba') );
	foreach ( $terms as $term ){
		if ( !term_exists( $term, 'gstu-ruoli' ) ){
			wp_insert_term( $term, 'gstu-ruoli' );
		}
	}
	
	//registra taxonomy fase del cpt lavori
	
	register_taxonomy('gstu-fasi' , 'gstu-lavori', array ( 'hierarchical' => true, 'label' => __('Fase di Lavorazione', 'bim-ba' ),
		  'query-var' => true,'rewrite' => true));
	
	$terms = array (__('01-Inquest','bim-ba'),
			__('02-Draft','bim-ba'),
			__('03-Final Project','bim-ba'),
			__('04-Executive project','bim-ba'),
			__("05-Tender Invitation",'bim-ba'),
			__("06-Contract",'bim-ba'),
			__('07-Project Management','bim-ba'),
			__('08-Trials','bim-ba') );
	foreach ( $terms as $term ){
		if ( !term_exists( $term, 'gstu-fasi' ) ){
			wp_insert_term( $term, 'gstu-fasi' );
		}
	}
	
	//registra taxonomy fase del cpt rapporti
	
	register_taxonomy('gstu-tipi' , 'gstu-rapporti', array ( 'hierarchical' => true, 'label' => __('Type of Report', 'bim-ba' ),
		  'query-var' => true,'rewrite' => true));
	
	$terms = array (
			__('Draft Bill','bim-ba'),
			__('Final Bill','bim-ba'),
			__('Fee','bim-ba'),
			__('Invoice','bim-ba'),
			__('Credit','bim-ba') );
	foreach ( $terms as $term ){
		if ( !term_exists( $term, 'gstu-tipi' ) ){
			wp_insert_term( $term, 'gstu-tipi' );
		}
	}
}

/*
 * INITIALIZE
*/

function bimba_3d_ambient_register_taxonomies(){
	register_taxonomy('material_category' , 'bimba_3d_material', array ( 'hierarchical' => true, 'label' => __('Material Category','bim-ba'),
	'query-var' => true,'rewrite' => true));
	
	$terms = array ( 
			array ( 'name' => esc_html__('Pavements','bim-ba'), 'slug' => 'pavements', ),
			array ( 'name' => esc_html__('Walls','bim-ba'), 'slug' => 'walls', ),
			array ( 'name' => esc_html__('Tiling','bim-ba'), 'slug' => 'tiling', ),
			array ( 'name' => esc_html__('Frames','bim-ba'), 'slug' => 'frames', ),
			array ( 'name' => esc_html__('Objects','bim-ba'), 'slug' => 'objects', ),
	);
	foreach ( $terms as $term ){
		if ( !term_exists( $term [ 'name' ] , 'material_category' ) ){
			wp_insert_term( $term [ 'name' ], 'material_category', array( 'slug' => $term [ 'slug' ], ) );
		}
	}
	
	register_taxonomy('element_category' , 'bimba_3d_element', array ( 'hierarchical' => true, 'label' => __('Element Category','bim-ba'),
	'query-var' => true,'rewrite' => true));
	
	$terms = array (
			array ( 'name' => esc_html__('Slabs','bim-ba'), 'slug' => 'slabs', ),
			array ( 'name' => esc_html__('Walls','bim-ba'), 'slug' => 'walls', ),
	);
	foreach ( $terms as $term ){
		if ( !term_exists( $term [ 'name' ] , 'element_category' ) ){
			wp_insert_term( $term [ 'name' ], 'element_category', array( 'slug' => $term [ 'slug' ], ) );
		}
	}
}

function bimba_fix_old_version_meta(){//fixing meta from old versions
	
	$args = array(
			'post_type'   => 'bimba_3d_material', 
			'numberposts' => -1,
		);
	
	$posts = get_posts( $args );
	
	if ( $posts ) {
		foreach ( $posts as $post ) {
			//material image
			$old_post_meta = get_post_meta( $post->ID, '_3d_material_image', true );
			if ( $old_post_meta ) {
				update_post_meta( $post->ID, '_3d_material_render' , array( '0' => array( 'image' => $old_post_meta ) ) );
				delete_post_meta( $post->ID, '_3d_material_image' );
			}
		}
	}
	
	$args = array(
			'post_type'   => 'bimba_3d_ambient',
			'numberposts' => -1,
	);
	
	$posts = get_posts( $args );
	
	if ( $posts ) {
		foreach ( $posts as $post ) {
			//global positioning
			$old_post_meta_rot = get_post_meta( $post->ID, '_3d_ambient_global_rot', true );
			$old_post_meta_x = get_post_meta( $post->ID, '_3d_ambient_global_x', true );
			$old_post_meta_z = get_post_meta( $post->ID, '_3d_ambient_global_z', true );
			if ( $old_post_meta_rot OR $old_post_meta_x OR $old_post_meta_z ) {
				update_post_meta( $post->ID, '_3d_ambient_global_diff' , array( '0' => array(
					'rot' => $old_post_meta_rot,
					'x' => $old_post_meta_x,
					'z' => $old_post_meta_z,	
				 ) ) );
				delete_post_meta( $post->ID, '_3d_ambient_global_rot' );
				delete_post_meta( $post->ID, '_3d_ambient_global_x' );
				delete_post_meta( $post->ID, '_3d_ambient_global_z' );
			}
			
			//room general settings
			$height = get_post_meta( $post->ID, '_3d_ambient_room_height', true );
			$wall_material = get_post_meta( $post->ID, '_3d_ambient_wall_material', true );
			$tiling_material = get_post_meta( $post->ID, '_3d_ambient_tiling_material', true );
			$floor_image = get_post_meta( $post->ID, '_3d_ambient_floor_image', true );
			$floor_material = get_post_meta( $post->ID, '_3d_ambient_floor_material', true );
			$skirting_height = get_post_meta( $post->ID, '_3d_ambient_skirting_height', true );
			
			if ( $height OR $wall_material OR $tiling_material OR $floor_image OR $floor_material OR $skirting_height ) {
				update_post_meta( $post->ID, '_3d_ambient_general_settings' , array( '0' => array(
				'height' 			=> $height,
				'wall_material' 	=> $wall_material,
				'tiling_material' 	=> $tiling_material,
				'floor_image' 		=> $floor_image,
				'floor_material' 	=> $floor_material,
				'skirting_height' 	=> $skirting_height,
				) ) );
				
				delete_post_meta( $post->ID, '_3d_ambient_room_height' );
				delete_post_meta( $post->ID, '_3d_ambient_wall_material' );
				delete_post_meta( $post->ID, '_3d_ambient_tiling_material' );
				delete_post_meta( $post->ID, '_3d_ambient_floor_image' );
				delete_post_meta( $post->ID, '_3d_ambient_floor_material' );
				delete_post_meta( $post->ID, '_3d_ambient_skirting_height' );
				
				delete_post_meta( $post->ID, '_3d_ambient_room_color' );
				delete_post_meta( $post->ID, '_3d_ambient_floor_color' );
				delete_post_meta( $post->ID, '_3d_ambient_tiling_color' );
				delete_post_meta( $post->ID, '_3d_ambient_sk_color' );
				
			}
			
		}
	}
}

function bimba_insert_generic_materials() {
	
	$args = array(
			'post_type'   => 'bimba_3d_material',
			'name' => 'generic-door',
			'post_status' 	=> 'publish',
	);
	
	if ( ! get_posts( $args ) ) {
		$term = term_exists( esc_html__('Frames','bim-ba'), 'material_category' );//the material category is hierarchical, so we have to retreive the id of the term
		$args = array(
			'post_type' 	=> 'bimba_3d_material',
			'post_title' 	=> esc_html__('Generic Door', 'bim-ba'),
			'post_name' 	=> 'generic-door',
			'post_status' 	=> 'publish',
			'post_content' 	=> esc_html__('Just a generic door 3D Material to start with.', 'bim-ba'),
			'tax_input' 	=> array( 'material_category' => array( $term[ 'term_taxonomy_id' ] ), ),
			'meta_input' 	=> array( '_3d_material_render' => array( '0' => array( 'image' => plugins_url( 'images/door.png', __FILE__ ), 'color'=>'#ffffff' ) ), ),
		);
		wp_insert_post($args);
	}
	
	$args = array(
			'post_type'   => 'bimba_3d_material',
			'name' => 'generic-window',
			'post_status' 	=> 'publish',
	);
	
	if ( ! get_posts( $args ) ) {
		$term = term_exists( esc_html__('Frames','bim-ba'), 'material_category' );
		$args = array(
				'post_type' 	=> 'bimba_3d_material',
				'post_title' 	=> esc_html__('Generic Window', 'bim-ba'),
				'post_name' 	=> 'generic-window',
				'post_status' 	=> 'publish',
				'post_content' 	=> esc_html__('Just a generic window 3D Material to start with.', 'bim-ba'),
				'tax_input' 	=> array( 'material_category' => array( $term[ 'term_taxonomy_id' ] ), ),
				'meta_input' 	=> array( '_3d_material_render' => array( '0' => array( 'image' => plugins_url( 'images/window_standard.png', __FILE__ ), 'color'=>'#ffffff' ) ), ),
		);
		wp_insert_post($args);
	}
	
	$args = array(
			'post_type'   => 'bimba_3d_material',
			'name' => 'generic-tiling',
			'post_status' 	=> 'publish',
	);
	
	if ( ! get_posts( $args ) ) {
		$term = term_exists( esc_html__('Pavements','bim-ba'), 'material_category' );
		$term_2 = term_exists( esc_html__('Tiling','bim-ba'), 'material_category' );
		$args = array(
				'post_type' 	=> 'bimba_3d_material',
				'post_title' 	=> esc_html__('Generic Tiling', 'bim-ba'),
				'post_name' 	=> 'generic-tiling',
				'post_status' 	=> 'publish',
				'post_content' 	=> esc_html__('Just a generic tiling 3D Material to start with.', 'bim-ba'),
				'tax_input' 	=> array( 'material_category' => array( $term[ 'term_taxonomy_id' ], $term_2[ 'term_taxonomy_id' ] ), ),
				'meta_input' 	=> array( '_3d_material_render' => array( '0' => array( 'image' => plugins_url( 'images/tiling.png', __FILE__ ), 'color'=>'#ffffff' ) ), ),
		);
		wp_insert_post($args);
	}
	
	$args = array(
			'post_type'   => 'bimba_3d_material',
			'name' => 'generic-wall',
			'post_status' 	=> 'publish',
	);
	
	if ( ! get_posts( $args ) ) {
		$term = term_exists( esc_html__('Walls','bim-ba'), 'material_category' );
		$args = array(
				'post_type' 	=> 'bimba_3d_material',
				'post_title' 	=> esc_html__('Generic Wall', 'bim-ba'),
				'post_name' 	=> 'generic-wall',
				'post_status' 	=> 'publish',
				'post_content' 	=> esc_html__('Just a generic wall 3D Material to start with.', 'bim-ba'),
				'tax_input' 	=> array( 'material_category' => array( $term[ 'term_taxonomy_id' ], ), ),
				'meta_input' 	=> array( '_3d_material_render' => array( '0' => array( 'image' => plugins_url( 'images/wall.png', __FILE__ ), 'color'=>'#ffffff' ) ), ),
		);
		wp_insert_post($args);
	}
	
	$args = array(
			'post_type'   => 'bimba_3d_material',
			'name' => 'generic-skirting',
			'post_status' 	=> 'publish',
	);
	
	if ( ! get_posts( $args ) ) {
		$term = term_exists( esc_html__('Tiling','bim-ba'), 'material_category' );
		$args = array(
				'post_type' 	=> 'bimba_3d_material',
				'post_title' 	=> esc_html__('Generic Skirting', 'bim-ba'),
				'post_name' 	=> 'generic-skirting',
				'post_status' 	=> 'publish',
				'post_content' 	=> esc_html__('Just a generic skirting 3D Material to start with.', 'bim-ba'),
				'tax_input' 	=> array( 'material_category' => array( $term[ 'term_taxonomy_id' ], ), ),
				'meta_input' 	=> array( '_3d_material_render' => array( '0' => array( 'image' => plugins_url( 'images/skirting.jpg', __FILE__ ), 'color'=>'#ffffff' ) ), ),
		);
		wp_insert_post($args);
	}
	
}

/*
 * Function creates post duplicate as a draft and redirects then to the edit post screen thanks to Misha Rudrastyh
*/
function bimba_duplicate_post_as_draft(){
	global $wpdb;
	if (! ( isset( $_GET['post']) || isset( $_POST['post'])  || ( isset($_REQUEST['action']) && 'bimba_duplicate_post_as_draft' == $_REQUEST['action'] ) ) ) {
		wp_die( esc_html__( 'No post to duplicate has been supplied!', 'bim-ba' ) );
	}

	/*
	 * get the original post id
	*/
	$post_id = (isset($_GET['post']) ? absint( $_GET['post'] ) : absint( $_POST['post'] ) );
	/*
	 * and all the original post data then
	*/
	$post = get_post( $post_id );

	/*
	 * if you don't want current user to be the new post author,
	* then change next couple of lines to this: $new_post_author = $post->post_author;
	*/
	$current_user = wp_get_current_user();
	$new_post_author = $current_user->ID;

	/*
	 * if post data exists, create the post duplicate
	*/
	if (isset( $post ) && $post != null) {

		/*
		 * new post data array
		*/
		$args = array(
				'comment_status' => $post->comment_status,
				'ping_status'    => $post->ping_status,
				'post_author'    => $new_post_author,
				'post_content'   => $post->post_content,
				'post_excerpt'   => $post->post_excerpt,
				'post_name'      => $post->post_name,
				'post_parent'    => $post->post_parent,
				'post_password'  => $post->post_password,
				'post_status'    => 'draft',
				'post_title'     => $post->post_title,
				'post_type'      => $post->post_type,
				'to_ping'        => $post->to_ping,
				'menu_order'     => $post->menu_order
		);

		/*
		 * insert the post by wp_insert_post() function
		*/
		$new_post_id = wp_insert_post( $args );

		/*
		 * get all current post terms ad set them to the new post draft
		*/
		$taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
		foreach ($taxonomies as $taxonomy) {
			$post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
			wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
		}

		/*
		 * duplicate all post meta just in two SQL queries
		*/
		$post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
		if (count($post_meta_infos)!=0) {
			$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
			foreach ($post_meta_infos as $meta_info) {
				$meta_key = $meta_info->meta_key;
				$meta_value = addslashes($meta_info->meta_value);
				$sql_query_sel[]= "SELECT $new_post_id, '$meta_key', '$meta_value'";
			}
			$sql_query.= implode(" UNION ALL ", $sql_query_sel);
			$wpdb->query($sql_query);
		}


		/*
		 * finally, redirect to the edit post screen for the new draft
		*/
		wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
		exit;
	} else {
		wp_die( esc_html__( 'Post creation failed, could not find original post: ', 'bim-ba' ) . $post_id );
	}
}
add_action( 'admin_action_bimba_duplicate_post_as_draft', 'bimba_duplicate_post_as_draft' );

/*
 * Add the duplicate link to action list for post_row_actions
*/
function bimba_duplicate_post_link( $actions, $post ) {
	if ( ( $post->post_type == 'bimba_3d_ambient' OR $post->post_type == 'bimba_3d_material' OR $post->post_type == 'bimba_3d_element' OR $post->post_type == 'bimba_3d_plan' ) AND current_user_can('edit_posts')) {
		$actions['duplicate'] = '<a href="admin.php?action=bimba_duplicate_post_as_draft&amp;post=' . $post->ID . '" title="' . esc_html__( 'Duplicate this item', 'bim-ba' ) . '" rel="permalink">' . esc_html__( 'Duplicate', 'bim-ba' ) . '</a>';
	}
	return $actions;
}

add_filter( 'page_row_actions', 'bimba_duplicate_post_link', 10, 2 );