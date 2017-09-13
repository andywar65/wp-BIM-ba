<?php
class Bimba3dElement{
	
	/**
	 * Start up
	 */
	public function __construct()
	{
		add_action( 'init', array( $this, 'bimba_init_3d_element_cpt' ) );
		add_action( 'admin_menu', array( $this, 'bimba_3d_element_taxonomy_submenu' ) );
		add_action( 'cmb2_admin_init', array( $this, 'bimba_register_3d_element_wall_metabox' ) );
	}
	
	/**
	 * Register 3D Element Custom Post Type and Element Category Taxonomy
	 */
	public function bimba_init_3d_element_cpt(){
		$labels = array(
				'name' => esc_html__( '3D Elements', 'bim-ba' ),
				'singular_name' => esc_html__( '3D Element', 'bim-ba' ),
				'add_new' => esc_html__( 'Add New', 'bim-ba' ),
				'add_new_item' => esc_html__( 'Add 3D Element', 'bim-ba' ),
				'edit_item' => esc_html__( 'Modify 3D Element', 'bim-ba' ),
				'new_item' => esc_html__( 'New 3D Element', 'bim-ba' ),
				'all_items' => esc_html__( 'All 3D Elements', 'bim-ba' ),
				'view_item' => esc_html__( 'Wiev 3D Element', 'bim-ba' ),
				'search_items' => esc_html__( 'Search 3D Element', 'bim-ba' ),
				'not_found' =>  esc_html__( 'No 3D Element', 'bim-ba' ),
				'not_found_in_trash' => esc_html__( 'No 3D Element in Trash', 'bim-ba' ),
				//'menu_name' => __( 'BIM-ba', 'bim-ba' )
		);
		
		$args = array(
				'hierarchical' => true,
				'labels' => $labels,
				'public' => true,
				'publicly_queryable' => true,
				'show_ui' => true,
				'show_in_menu' => 'edit.php?post_type=bimba_3d_ambient',
				'query_var' => true,
				'rewrite' => array('slug' => _x('3d-element', 'URL slug', 'bim-ba')),
				'capability_type' => 'page',
				'has_archive' => true,
				'menu_position' => 100,
				'supports' => array( 'title', 'editor', 'content', 'thumbnail', 'page-attributes', 'comments', 'author' ),
				'taxonomies' => array( 'element_category' , ),
				//'menu_icon'   => 'dashicons-layout'
		);
		
		register_post_type( 'bimba_3d_element', $args );
		
		register_taxonomy('element_category' , 'bimba_3d_element', array ( 'hierarchical' => true, 'label' => __('Element Category','bim-ba'),
		'query-var' => true,'rewrite' => true));
	}
	
	/**
	 * Register Submenu for Element Category Taxonomy
	 */
	public function bimba_3d_element_taxonomy_submenu() {
		add_submenu_page(
		'edit.php?post_type=bimba_3d_ambient',
		'Element Categories',
		esc_html__('Element Categories', 'bim-ba'),
		'manage_options',
		'edit-tags.php?taxonomy=element_category&post_type=bimba_3d_element'
				//, array( $this, 'create_admin_page' )
		);
	}
	
	/**
	 * Hook in and add a metabox for elements
	 */
	public function bimba_register_3d_element_wall_metabox(){
	
		$prefix_group = '_3d_element_';
		/**
		 * Repeatable Field Groups
		 */
		$cmb_group = new_cmb2_box( array(
				'id'           => $prefix_group . 'wall_metabox',
				'title'        => esc_html__( 'Wall Elements', 'bim-ba' ),
				'object_types' => array( 'bimba_3d_element', ),
		) );
		// $group_field_id is the field id string, so in this case: $prefix . 'objects'
		$group_field_id = $cmb_group->add_field( array(
				'id'          => $prefix_group . 'layers',
				'type'        => 'group',
				'description' => esc_html__( 'Start from Upper/Outer Layer', 'bim-ba' ),
				'options'     => array(
						'group_title'   => esc_html__( 'Layer {#}', 'bim-ba' ), // {#} gets replaced by row number
						'add_button'    => esc_html__( 'Add Another Layer', 'bim-ba' ),
						'remove_button' => esc_html__( 'Remove Layer', 'bim-ba' ),
						'sortable'      => true, // beta
						'closed'     => true, // true to have the groups closed by default
				),
		) );
		/**
		 * Group fields works the same, except ids only need
		 * to be unique to the group. Prefix is not needed.
		 *
		 * The parent field's id needs to be passed as the first argument.
		*/
		$cmb_group->add_group_field( $group_field_id, array(
				'name'        => esc_html__( 'Name', 'bim-ba' ),
				'id'          => 'name',
				'type'        => 'text',
		) );
		
		$cmb_group->add_group_field( $group_field_id, array(
				'name'        => esc_html__( 'Thickness', 'bim-ba' ),
				'description' => esc_html__( 'In millimeters', 'bim-ba' ),
				'id'          => 'thickness',
				'type'        => 'text_number',
		) );
		
		$cmb_group->add_group_field( $group_field_id, array(
				'name'        => esc_html__( 'Lambda', 'bim-ba' ),
				'description' => esc_html__( 'In W/mK', 'bim-ba' ),
				'id'          => 'lambda',
				'type'        => 'text_number',
		) );
		
		$cmb_group->add_group_field( $group_field_id, array(
				'name'        => esc_html__( 'Mass', 'bim-ba' ),
				'description' => esc_html__( 'In kN/m3', 'bim-ba' ),
				'id'          => 'mass',
				'type'        => 'text_number',
		) );
	}
	
}

if ( is_admin() ) {
	$bimba_elm = new Bimba3dElement();
}


