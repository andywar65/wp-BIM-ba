<?php
class Bimba3dMaterial{
	
	/**
	 * Start up
	 */
	public function __construct()
	{
		add_action( 'init', array( $this, 'bimba_init_3d_material_cpt' ) );
		add_action( 'admin_menu', array( $this, 'bimba_3d_material_taxonomy_submenu' ) );
		add_action( 'cmb2_admin_init', array( $this, 'bimba_register_3d_material_metabox' ) );
	}
	
	/**
	 * Register 3D Material Custom Post Type and Material Category Taxonomy
	 */
	public function bimba_init_3d_material_cpt(){
		$labels = array(
				'name' => esc_html__( '3D Materials', 'bim-ba' ),
				'singular_name' => esc_html__( '3D Material', 'bim-ba' ),
				'add_new' => esc_html__( 'Add New', 'bim-ba' ),
				'add_new_item' => esc_html__( 'Add 3D Material', 'bim-ba' ),
				'edit_item' => esc_html__( 'Modify 3D Material', 'bim-ba' ),
				'new_item' => esc_html__( 'New 3D Material', 'bim-ba' ),
				'all_items' => esc_html__( 'All 3D Materials', 'bim-ba' ),
				'view_item' => esc_html__( 'Wiev 3D Material', 'bim-ba' ),
				'search_items' => esc_html__( 'Search 3D Material', 'bim-ba' ),
				'not_found' =>  esc_html__( 'No 3D Material', 'bim-ba' ),
				'not_found_in_trash' => esc_html__( 'No 3D Material in Trash', 'bim-ba' ),
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
				'rewrite' => array('slug' => _x('3d-material', 'URL slug', 'bim-ba')),
				'capability_type' => 'page',
				'has_archive' => true,
				'menu_position' => 100,
				'supports' => array( 'title', 'editor', 'content', 'thumbnail', 'page-attributes', 'comments', 'author' ),
				'taxonomies' => array( 'material_category' , ),
				//'menu_icon'   => 'dashicons-layout'
		);
		
		register_post_type( 'bimba_3d_material', $args );
		
		register_taxonomy('material_category' , 'bimba_3d_material', array ( 'hierarchical' => true, 'label' => __('Material Category','bim-ba'),
		'query-var' => true,'rewrite' => true));
	}
	
	/**
	 * Register Submenu for Material Category Taxonomy
	 */
	public function bimba_3d_material_taxonomy_submenu() {
		add_submenu_page(
		'edit.php?post_type=bimba_3d_ambient',
		'Material Categories',
		esc_html__('Material Categories', 'bim-ba'),
		'manage_options',
		'edit-tags.php?taxonomy=material_category&post_type=bimba_3d_material'
				//, array( $this, 'create_admin_page' )
		);
	}
	
	/**
	 * Hook in and add a metabox for materials
	 */
	public function bimba_register_3d_material_metabox(){
		$material_prefix = '_3d_material_';
	
		$cmb_group = new_cmb2_box( array(
				'id'           => $material_prefix . 'metabox',
				'title'        => esc_html__( 'Material Description', 'bim-ba' ),
				'object_types' => array( 'bimba_3d_material', ),
				//'closed'	=> true,
		) );
	
		$group_field_id = $cmb_group->add_field( array(
				'id'          => $material_prefix . 'render',
				'type'        => 'group',
				'options'     => array(
						'group_title'   => esc_html__( 'Images and colors', 'bim-ba' ),
				),
				'repeatable'=>false,//this one is to avoid repeatable groups
		) );
	
		$cmb_group->add_group_field( $group_field_id, array(
				'name' => esc_html__( 'Image', 'bim-ba' ),
				'description' => esc_html__( 'Possibly a 1x1 m pattern', 'bim-ba' ),
				'id'   => 'image',
				'type' => 'file',
		) );
	
		$cmb_group->add_group_field( $group_field_id, array(
				'name'    => esc_html__( 'Material Color', 'bim-ba' ),
				'description' => esc_html__( 'Blends with Image except if white', 'bim-ba' ),
				'id'      => 'color',
				'type'    => 'colorpicker',
				'default' => '#ffffff',
		) );
		//$cmb_group->add_group_field( $group_field_id, array(
				//'name' => esc_html__( 'Door outer frame Image', 'bim-ba' ),
				//'description' => esc_html__( 'Works only for doors', 'bim-ba' ),
				//'id'   => 'frame_image',
				//'type' => 'file',
		//) );
	
		//$cmb_group->add_group_field( $group_field_id, array(
				//'name'    => esc_html__( 'Door outer frame Color', 'bim-ba' ),
				//'description' => esc_html__( 'Blends with Door outer frame Image except if white', 'bim-ba' ),
				//'id'      => 'frame_color',
				//'type'    => 'colorpicker',
				//'default' => '#ffffff',
		//) );
	}
	
}

if ( is_admin() ) {
	$bimba_mat = new Bimba3dMaterial();
}


