<?php

// render numbers
add_action( 'cmb2_render_bimba_number', 'bimba_cmb_render_bimba_number', 10, 5 );
function bimba_cmb_render_bimba_number( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {
	echo $field_type_object->input( array( 'class' => 'cmb2-text-small', 'type' => 'number',  ) );//'step' => '0.01'
}

// sanitize the field
add_filter( 'cmb2_sanitize_bimba_number', 'bimba_cmb2_sanitize_bimba_number', 10, 2 );
function bimba_cmb2_sanitize_bimba_number( $null, $new ) {
	//$new = preg_replace( "/[^0-9]/", "", $new );
	if( !is_numeric( $new ) ){
		$new = '';
	}
	return abs( $new );
}

// render numbers that can be negative
add_action( 'cmb2_render_bimba_number_neg', 'bimba_cmb_render_bimba_number_neg', 10, 5 );
function bimba_cmb_render_bimba_number_neg( $field, $escaped_value, $object_id, $object_type, $field_type_object ) {
	echo $field_type_object->input( array( 'class' => 'cmb2-text-small', 'type' => 'number',  ) );//'step' => '0.01'
}

// sanitize the field
add_filter( 'cmb2_sanitize_bimba_number_neg', 'bimba_cmb2_sanitize_bimba_number_neg', 10, 2 );
function bimba_cmb2_sanitize_bimba_number_neg( $null, $new ) {
	if( !is_numeric( $new ) ){
		$new = '';
	}

	return $new;
}

add_action( 'cmb2_admin_init', 'bimba_3d_ambient_register_room_general_metabox' );
/**
 * Hook in and add a metabox for room
 */
function bimba_3d_ambient_register_room_general_metabox(){
	$prefix = '_3d_ambient_general_';
	
	$cmb_group = new_cmb2_box( array(
			'id'           => $prefix . 'metabox',
			'title'        => esc_html__( 'Room General Settings', 'bim-ba' ),
			'object_types' => array( 'bimba_3d_ambient', ),
			'closed'	=> true,
	) );
	
	$group_field_id = $cmb_group->add_field( array(
			'id'          => $prefix . 'settings',
			'type'        => 'group',
			'options'     => array(
					'group_title'   => esc_html__( 'Dimensions and materials', 'bim-ba' ),
			),
			'repeatable'=>false,//this one is to avoid repeatable groups
	) );
	
	$cmb_group->add_group_field( $group_field_id, array(
			'name'       => __( 'Room height', 'bim-ba' ),
			'description' => esc_html__( 'In centimeters', 'bim-ba' ),
			'id'         => 'height',
			'type'       => 'bimba_number',
	) );
	
	$cmb_group->add_group_field( $group_field_id, array(
			'name'       => __( 'Wall Material', 'bim-ba' ),
			'description' => esc_html__( 'Picks only from Wall Material Category', 'bim-ba' ),
			'id'         => 'wall_material',
			'type'     	=> 'select',
			'show_option_none' => true,
			'options_cb' => 'bimba_get_3d_material_wall_options',
	) );
	
	$cmb_group->add_group_field( $group_field_id, array(
			'name'       => __( 'Tiling Material', 'bim-ba' ),
			'description' => esc_html__( 'Picks only from Tiling Material Category', 'bim-ba' ),
			'id'         => 'tiling_material',
			'type'     	=> 'select',
			'show_option_none' => true,
			'options_cb' => 'bimba_get_3d_material_tiling_options',
	) );
	
	$cmb_group->add_group_field( $group_field_id, array(
			'name'       => __( 'Floor Material', 'bim-ba' ),
			'description' => esc_html__( 'Picks only from Pavement Material Category', 'bim-ba' ),
			'id'         => 'floor_material',
			'type'     	=> 'select',
			'show_option_none' => true,
			'options_cb' => 'bimba_get_3d_material_pav_options',
	) );
	
	$cmb_group->add_group_field( $group_field_id, array(
			'name' => esc_html__( 'Floor Image', 'bim-ba' ),
			'description' => esc_html__( 'Hides Floor Material', 'bim-ba' ),
			'id'   => 'floor_image',
			'type' => 'file',
	) );
	
	$cmb_group->add_group_field( $group_field_id, array(
			'name'       => __( 'Skirting height', 'bim-ba' ),
			'description' => esc_html__( 'In centimeters (max 30). Leave null if no skirting', 'bim-ba' ),
			'id'         =>  'skirting_height',
			'type'       => 'bimba_number',
	) );
	
	$cmb_group->add_group_field( $group_field_id, array(
			'name'       => __( 'Skirting Material', 'bim-ba' ),
			'description' => esc_html__( 'Picks only from Pavement and Tiling Material Category', 'bim-ba' ),
			'id'         => 'skirting_material',
			'type'     	=> 'select',
			'show_option_none' => true,
			'options_cb' => 'bimba_get_3d_material_pav_tiling_options',
	) );
	
	$cmb_group->add_group_field( $group_field_id, array(
			'name'       => __( 'Ceiling Material', 'bim-ba' ),
			'description' => esc_html__( 'Picks only from Wall Material Category', 'bim-ba' ),
			'id'         => 'ceiling_material',
			'type'     	=> 'select',
			'show_option_none' => true,
			'options_cb' => 'bimba_get_3d_material_wall_options',
	) );
	
	$cmb_group->add_group_field( $group_field_id, array(
			'name' => esc_html__( 'Ceiling Image', 'bim-ba' ),
			'description' => esc_html__( 'Hides Ceiling Material', 'bim-ba' ),
			'id'   => 'ceiling_image',
			'type' => 'file',
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Render Children', 'bim-ba' ),
			'description' => esc_html__( 'Check children 3D Ambients to be rendered with this one', 'bim-ba' ),
			'id'          => 'render_children',
			'type'        => 'multicheck',
			//'show_option_none' => true,
			'options_cb' => 'bimba_get_children_options',
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Is Camera allowed to fly?', 'bim-ba' ),
			'description' => esc_html__( 'Parent setting prevails', 'bim-ba' ),
			'id'          => 'camera_fly',
			'type'             => 'radio_inline',
			'default' => 'false',
			'options'          => array(
					'false' => esc_html__( 'No', 'bim-ba' ),
					'true'   => esc_html__( 'Yes', 'bim-ba' ),
			),
	) );
}

add_action( 'cmb2_admin_init', 'bimba_3d_ambient_register_wall_metabox' );
/**
 * Hook in and add a metabox for walls
*/
function bimba_3d_ambient_register_wall_metabox() {
	$prefix_group = '_3d_ambient_group_';
	/**
	 * Repeatable Field Groups
	 */
	$cmb_group = new_cmb2_box( array(
			'id'           => $prefix_group . 'wall_metabox',
			'title'        => esc_html__( 'Walls', 'bim-ba' ),
			'object_types' => array( 'bimba_3d_ambient', ),
	) );
	// $group_field_id is the field id string, so in this case: $prefix . 'walls'
	$group_field_id = $cmb_group->add_field( array(
			'id'          => $prefix_group . 'walls',
			'type'        => 'group',
			//'description' => esc_html__( 'Generates reusable form entries', 'cmb2' ),
			'options'     => array(
					'group_title'   => esc_html__( 'Wall {#}', 'bim-ba' ), // {#} gets replaced by row number
					'add_button'    => esc_html__( 'Add Another Wall', 'bim-ba' ),
					'remove_button' => esc_html__( 'Remove Wall', 'bim-ba' ),
					'sortable'      => true, // beta
					'closed'     => true, // true to have the groups closed by default
			),
			//'repeatable'=>false,//this one is to avoid repeatable groups
	) );
	/**
	 * Group fields works the same, except ids only need
	 * to be unique to the group. Prefix is not needed.
	 *
	 * The parent field's id needs to be passed as the first argument.
	*/
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Name', 'bim-ba' ),
			'description' => esc_html__( 'Computer generated', 'bim-ba' ),
			'id'          => 'name',
			'type'        => 'text',
			'sanitization_cb' => 'bimba_3d_ambient_assign_wall_name',
			'attributes' => array(
				'readonly'    => true,
			),
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'       => esc_html__( 'Direction', 'bim-ba' ),
			'id'         => 'direction',
			'type'     	=> 'select',
			//'show_option_none' => true,
			'options'          => array(
				'0' => esc_html__( 'To the right', 'bim-ba' ),
				'1'   => esc_html__( 'To the back', 'bim-ba' ),
				'2'     => esc_html__( 'To the left', 'bim-ba' ),
				'3'     => esc_html__( 'To the front', 'bim-ba' ),
			),
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Length', 'bim-ba' ),
			'description' => esc_html__( 'In centimeters', 'bim-ba' ),
			'id'          => 'length',
			'type'        => 'bimba_number',
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Wall Material', 'bim-ba' ),
			'description' => esc_html__( 'Overrides Wall Material selected in Room General Settings', 'bim-ba' ),
			'id'          => 'wall_material',
			'type'        => 'select',
			'show_option_none' => true,
			'options_cb' => 'bimba_get_3d_material_wall_options',
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name' => esc_html__( 'Wall Image', 'bim-ba' ),
			'description' => esc_html__( 'Hides materials and openings', 'bim-ba' ),
			'id'   => 'image',
			'type' => 'file',
	) );
	
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Tiling Height', 'bim-ba' ),
			'description' => esc_html__( 'In centimeters. Leave null if no tiling', 'bim-ba' ),
			'id'          => 'tiling_height',
			'type'        => 'bimba_number',
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Tiling Material', 'bim-ba' ),
			'description' => esc_html__( 'Overrides Tiling Material selected in Room General Settings', 'bim-ba' ),
			'id'          => 'tiling_material',
			'type'        => 'select',
			'show_option_none' => true,
			'options_cb' => 'bimba_get_3d_material_tiling_options',
	) );
	
	$cmb_group->add_group_field( $group_field_id, array(
			'name'       => esc_html__( 'Openings', 'bim-ba' ),
			//'description' => esc_html__( 'Overrides wall image', 'bim-ba' ),
			'id'         => 'openings',
			'type'     	=> 'select',
			'show_option_none' => true,
			'options'          => array(
					//'0' => esc_html__( 'None', 'bim-ba' ),
					'1'   => esc_html__( 'Door', 'bim-ba' ),
					'2'     => esc_html__( 'Window', 'bim-ba' ),
			),
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Frame material', 'bim-ba' ),
			'description' => esc_html__( 'Picks only from Frames Material Category. Leave null if no frame', 'bim-ba' ),
			'id'          => 'op_material',
			'type'        => 'select',
			'show_option_none' => true,
			'options_cb' => 'bimba_get_3d_material_frames_options',
			'attributes' => array(
					'data-conditional-id'    => wp_json_encode( array( $group_field_id, 'openings' ) ),
					'data-conditional-value' => wp_json_encode( array( '1', '2' ) ),
			),
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Outer Frame material', 'bim-ba' ),
			'description' => esc_html__( 'Picks from Frames and Objects Material Category.', 'bim-ba' ),
			'id'          => 'frame_material',
			'type'        => 'select',
			'show_option_none' => true,
			'options_cb' => 'bimba_get_3d_material_frames_objects_options',
			'attributes' => array(
					'data-conditional-id'    => wp_json_encode( array( $group_field_id, 'openings' ) ),
					'data-conditional-value' => wp_json_encode( array( '1', '2' ) ),
			),
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Opening Movement', 'bim-ba' ),
			'id'          => 'animation',
			'type'        => 'select',
			'show_option_none' => true,
			'options'          => array(
					//'0' => esc_html__( 'None', 'bim-ba' ),
					'1'  => esc_html__( 'Left hinge', 'bim-ba' ),
					'2'  => esc_html__( 'Right hinge', 'bim-ba' ),
					'5'  => esc_html__( 'Double hinges', 'bim-ba' ),
					'3'  => esc_html__( 'Slides left', 'bim-ba' ),
					'4'  => esc_html__( 'Slides right', 'bim-ba' ),
					'6'  => esc_html__( 'Double slides', 'bim-ba' ),
			),
			'attributes' => array(
					'data-conditional-id'    => wp_json_encode( array( $group_field_id, 'openings' ) ),
					'data-conditional-value' => wp_json_encode( array( '1', '2' ) ),
			),
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Opening Offset', 'bim-ba' ),
			'description' => esc_html__( 'Distance in centimeters between left side of opening and wall origin. If negative opening will be centered.', 'bim-ba' ),
			'id'          => 'op_offset',
			'type'        => 'bimba_number_neg',
			'attributes' => array(
					'data-conditional-id'    => wp_json_encode( array( $group_field_id, 'openings' ) ),
					'data-conditional-value' => wp_json_encode( array( '1', '2' ) ),
			),
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Opening Width', 'bim-ba' ),
			'description' => esc_html__( 'In centimeters', 'bim-ba' ),
			'id'          => 'op_width',
			'type'        => 'bimba_number',
			'attributes' => array(
					'data-conditional-id'    => wp_json_encode( array( $group_field_id, 'openings' ) ),
					'data-conditional-value' => wp_json_encode( array( '1', '2' ) ),
			),
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Opening Height', 'bim-ba' ),
			'description' => esc_html__( 'In centimeters', 'bim-ba' ),
			'id'          => 'op_height',
			'type'        => 'bimba_number',
			'attributes' => array(
					'data-conditional-id'    => wp_json_encode( array( $group_field_id, 'openings' ) ),
					'data-conditional-value' => wp_json_encode( array( '1', '2' ) ),
			),
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Wall Depth', 'bim-ba' ),
			'description' => esc_html__( 'In centimeters', 'bim-ba' ),
			'id'          => 'wall_depth',
			'type'        => 'bimba_number',
			'attributes' => array(
					'data-conditional-id'    => wp_json_encode( array( $group_field_id, 'openings' ) ),
					'data-conditional-value' => wp_json_encode( array( '1', '2' ) ),
			),
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Delete side wall', 'bim-ba' ),
			'description' => esc_html__( 'Use when you have consecutive openings', 'bim-ba' ),
			'id'          => 'delete_side_wall',
			'type'             => 'radio_inline',
			'show_option_none' => true,
			'options'          => array(
					'1' => esc_html__( 'Left', 'bim-ba' ),
					'2'   => esc_html__( 'Right', 'bim-ba' ),
					'3'     => esc_html__( 'Both', 'bim-ba' ),
			),
			'attributes' => array(
					'data-conditional-id'    => wp_json_encode( array( $group_field_id, 'openings' ) ),
					'data-conditional-value' => wp_json_encode( array( '1', '2' ) ),
			),
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Windowsill Height', 'bim-ba' ),
			'description' => esc_html__( 'In centimeters', 'bim-ba' ),
			'id'          => 'sill_height',
			'type'        => 'bimba_number',
			'attributes' => array(
					'data-conditional-id'    => wp_json_encode( array( $group_field_id, 'openings' ) ),
					'data-conditional-value' => wp_json_encode( array( '1', '2' ) ),
			),
			//'default'	=> '100',
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Windowsill Depht', 'bim-ba' ),
			'description' => esc_html__( 'In centimeters', 'bim-ba' ),
			'id'          => 'sill_depth',
			'type'        => 'bimba_number',
			'attributes' => array(
					'data-conditional-id'    => wp_json_encode( array( $group_field_id, 'openings' ) ),
					'data-conditional-value' => wp_json_encode( array( '1', '2' ) ),
			),
	) );
	
}

function bimba_3d_ambient_assign_wall_name( $value, $field_args, $field ) {
	if ( ! $value ) {
		global $post;
		$record = get_post_meta( $post->ID, '_3d_ambient_wall_name_record', true );
		$record ++;
		$value = esc_html__( 'Wall', 'bim-ba' ) . '-' . $record;
		update_post_meta( $post->ID, '_3d_ambient_wall_name_record', $record );
	}
	return $value;
}

add_action( 'cmb2_admin_init', 'bimba_3d_ambient_register_object_metabox' );
/**
 * Hook in and add a metabox for objects
*/
function bimba_3d_ambient_register_object_metabox() {
	$prefix_group = '_3d_ambient_group_';
	/**
	 * Repeatable Field Groups
	 */
	$cmb_group = new_cmb2_box( array(
			'id'           => $prefix_group . 'object_metabox',
			'title'        => esc_html__( 'Objects', 'bim-ba' ),
			'object_types' => array( 'bimba_3d_ambient', ),
	) );
	// $group_field_id is the field id string, so in this case: $prefix . 'objects'
	$group_field_id = $cmb_group->add_field( array(
			'id'          => $prefix_group . 'objects',
			'type'        => 'group',
			//'description' => esc_html__( 'Generates reusable form entries', 'cmb2' ),
			'options'     => array(
					'group_title'   => esc_html__( 'Object {#}', 'bim-ba' ), // {#} gets replaced by row number
					'add_button'    => esc_html__( 'Add Another Object', 'bim-ba' ),
					'remove_button' => esc_html__( 'Remove Object', 'bim-ba' ),
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
			'name'       => esc_html__( 'Object', 'bim-ba' ),
			'description' => esc_html__( 'Parametric dimensions in brackets', 'bim-ba' ),
			'id'         => 'type',
			'type'     	=> 'select',
			'show_option_none' => true,
			'options'          => array(
					'3'     => esc_html__( 'Module (WxHxD)', 'bim-ba' ),
					'8'     => esc_html__( 'Cylinder (WxHxD)', 'bim-ba' ),
					'1' => esc_html__( 'Bed (WxD)', 'bim-ba' ),
					'9' => esc_html__( 'Sofa (WxD)', 'bim-ba' ),
					'2'   => esc_html__( 'Table & Chair (WxD)', 'bim-ba' ),
					//'4'     => esc_html__( 'Sideboard Bontempi', 'bim-ba' ),//dismissed items
					//'5'     => esc_html__( 'Cupboard', 'bim-ba' ),
					'6'     => esc_html__( 'Center Table + 4 chairs (WxD)', 'bim-ba' ),
					'7'     => esc_html__( 'Note', 'bim-ba' ),
			),
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Parametric Width', 'bim-ba' ),
			'description' => esc_html__( 'Width of parametric objects. In case of Module, if null equals wall length. In centimeters', 'bim-ba' ),
			'id'          => 'width',
			'type'        => 'bimba_number',
			'attributes' => array(
					'data-conditional-id'    => wp_json_encode( array( $group_field_id, 'type' ) ),
					'data-conditional-value' => wp_json_encode( array( '1', '2', '3', '6', '8', '9' ) ),
			),
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Module Width', 'bim-ba' ),
			'description' => esc_html__( 'In centimeters. If null equals Parametric Width', 'bim-ba' ),
			'id'          => 'module_width',
			'type'        => 'bimba_number',
			'attributes' => array(
					'data-conditional-id'    => wp_json_encode( array( $group_field_id, 'type' ) ),
					'data-conditional-value' => wp_json_encode( array( '3' ) ),
			),
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Parametric Height', 'bim-ba' ),
			'description' => esc_html__( 'Height of parametric objects. In case of Module, if null equals room height. In centimeters', 'bim-ba' ),
			'id'          => 'height',
			'type'        => 'bimba_number',
			'attributes' => array(
					'data-conditional-id'    => wp_json_encode( array( $group_field_id, 'type' ) ),
					'data-conditional-value' => wp_json_encode( array( '3', '8' ) ),
			),
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Parametric Depth', 'bim-ba' ),
			'description' => esc_html__( 'Depth of parametric objects. In centimeters', 'bim-ba' ),
			'id'          => 'depth',
			'type'        => 'bimba_number',
			'attributes' => array(
					'data-conditional-id'    => wp_json_encode( array( $group_field_id, 'type' ) ),
					'data-conditional-value' => wp_json_encode( array( '1', '2', '3', '6', '8', '9' ) ),
			),
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Material', 'bim-ba' ),
			//'description' => esc_html__( 'In centimeters', 'bim-ba' ),
			'id'          => 'material',
			'type'        => 'select',
			'show_option_none' => true,
			'options_cb' => 'bimba_get_3d_material_object_options',
			'attributes' => array(
					'data-conditional-id'    => wp_json_encode( array( $group_field_id, 'type' ) ),
					'data-conditional-value' => wp_json_encode( array( '1', '2', '3', '6', '8', '9' ) ),
			),
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Text', 'bim-ba' ),
			'description' => esc_html__( 'WARNING: problems with accents', 'bim-ba' ),
			'id'          => 'text',
			'type'        => 'text',
			//'attributes' => array(
					//'data-conditional-id'    => wp_json_encode( array( $group_field_id, 'type' ) ),
					//'data-conditional-value' => wp_json_encode( array( '1', '3', '7' ) ),
			//),
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Wall Name', 'bim-ba' ),
			'description' => esc_html__( 'Wall you want to relate the object to', 'bim-ba' ),
			'id'          => 'wall-id',
			'type'        => 'select',
			'show_option_none' => true,
			'options_cb' => 'bimba_get_3d_ambient_wall_name_options',
	) );
	
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Offset', 'bim-ba' ),
			'description' => esc_html__( 'Distance in centimeters between left side of object and wall origin. If negative object will be centered.', 'bim-ba' ),
			'id'          => 'offset',
			'type'        => 'bimba_number_neg',
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Distance', 'bim-ba' ),
			'description' => esc_html__( 'Distance from the wall in centimeters', 'bim-ba' ),
			'id'          => 'distance',
			'type'        => 'bimba_number',
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Altitude', 'bim-ba' ),
			'description' => esc_html__( 'Distance from the floor in centimeters', 'bim-ba' ),
			'id'          => 'altitude',
			'type'        => 'bimba_number',
			'attributes' => array(
					'data-conditional-id'    => wp_json_encode( array( $group_field_id, 'type' ) ),
					'data-conditional-value' => wp_json_encode( array( '3', '7', '8' ) ),
			),
	) );
	
	
}

function bimba_get_3d_ambient_wall_name_options() {
	global $post;
	$walls = get_post_meta( $post->ID, '_3d_ambient_group_walls', true );
	
	$post_options = array();
	if ( $walls ) {
		foreach ( $walls as $wall ) {
			$post_options[ $wall ['name'] ] = $wall ['name'];
		}
	}
	
	return $post_options;
}

add_action( 'cmb2_admin_init', 'bimba_3d_ambient_register_global_metabox' );
/**
 * Hook in and add a metabox for room global positioning
*/
function bimba_3d_ambient_register_global_metabox(){
	$global_prefix = '_3d_ambient_global_';

	$cmb_group = new_cmb2_box( array(
			'id'           => $global_prefix . 'metabox',
			'title'        => esc_html__( 'Room Global Positioning', 'bim-ba' ),
			'object_types' => array( 'bimba_3d_ambient', ),
			'closed'	=> true,
	) );
	
	$group_field_id = $cmb_group->add_field( array(
			'id'          => $global_prefix . 'diff',
			'type'        => 'group',
			'options'     => array(
					'group_title'   => esc_html__( 'Orientation and displacements', 'bim-ba' ),
			),
			'repeatable'=>false,//this one is to avoid repeatable groups
	) );
	
	$cmb_group->add_group_field( $group_field_id, array(
			'name'       => esc_html__( 'Orientation', 'bim-ba' ),
			'description' => esc_html__( 'Front wall orientation in the real world', 'bim-ba' ),
			'id'         => 'rot',
			'type'     	=> 'select',
			//'show_option_none' => true,
			'options'          => array(
					'0' => esc_html__( 'North', 'bim-ba' ),
					'1'   => esc_html__( 'West', 'bim-ba' ),
					'2'     => esc_html__( 'South', 'bim-ba' ),
					'3'     => esc_html__( 'East', 'bim-ba' ),
			),
	) );

	$cmb_group->add_group_field( $group_field_id, array(
			'name'       => __( 'East (X) Displacement', 'bim-ba' ),
			'description' => esc_html__( 'Room displacement with respect to a fixed origin in the East (X) / West (-X) axis, in centimeters', 'bim-ba' ),
			'id'         => 'x',
			'type'       => 'bimba_number_neg',
	) );
	
	$cmb_group->add_group_field( $group_field_id, array(
			'name'       => __( 'South (Z) Displacement', 'bim-ba' ),
			'description' => esc_html__( 'Room displacement with respect to a fixed origin in the North (-Z) / South (Z) axis, in centimeters', 'bim-ba' ),
			'id'         => 'z',
			'type'       => 'bimba_number_neg',
	) );
	
	$cmb_group->add_group_field( $group_field_id, array(
			'name'       => __( 'Vertical (Y) Displacement', 'bim-ba' ),
			'description' => esc_html__( 'Room displacement with respect to a fixed origin in the Up (Y) / Down (-Y) axis, in centimeters', 'bim-ba' ),
			'id'         => 'y',
			'type'       => 'bimba_number_neg',
	) );
	
}




/**
 * Gets a number of 3D Materials and displays them as options
 * @param  array or string $terms Optional.
 * @return array             An array of options that matches the CMB2 options array
 */

function bimba_get_material_options( $terms ) {

	$args = array( 
			'post_type' 	=> 'bimba_3d_material', 
			'numberposts' 	=> -1,
			'tax_query'		=> array( array(
				'taxonomy'			=> 'material_category',
				'field'				=> 'slug',
				'terms'				=> $terms,
				'include_children'	=> true,
			) ),
	 );

	$posts = get_posts( $args );

	$post_options = array();
	if ( $posts ) {
		foreach ( $posts as $post ) {
			//$object_material = get_post_meta( $post->ID, '_3d_material_render', true );
			//if ( $object_material [0] ['image'] ) {
				//$object_image = '<img src="' . $object_material [0] ['image'] . '" height="50" width="50">';
			//} else {
				$object_image = '';
			//}
			
			$post_options[ $post->ID ] = $post->post_title . $object_image ;
		}
	}

	return $post_options;
}

function bimba_get_children_options() {
	global $post;
	$args = array(
			'post_parent' => $post->ID,
			'post_type'   => 'bimba_3d_ambient',
			'numberposts' => -1,
	);
	
	$children = get_children( $args );//get eventual children
	
	$post_options = array();
	if ( $children ) {
		foreach ( $children as $child ) {
			$post_options[ $child->ID ] = $child->post_title;
		}
	}
	
	return $post_options;
}

/**
 * Gives function bimba_get_material_options() an argument
 * @return array or string as term
 */

function bimba_get_3d_material_wall_options() {
	return bimba_get_material_options( 'walls' );
}

function bimba_get_3d_material_object_options() {
	return bimba_get_material_options( 'objects' );
}

function bimba_get_3d_material_pav_options() {
	return bimba_get_material_options( 'pavements' );
}

function bimba_get_3d_material_tiling_options() {
	return bimba_get_material_options( 'tiling' );
}

function bimba_get_3d_material_pav_tiling_options() {
	return bimba_get_material_options( array ('pavements', 'tiling',) );
}

function bimba_get_3d_material_frames_options() {
	return bimba_get_material_options( 'frames' );
}

function bimba_get_3d_material_frames_objects_options() {
	return bimba_get_material_options( array ('frames', 'objects',) );
}

add_action( 'cmb2_admin_init', 'bimba_register_3d_plan_axis_metabox' );

/**
 * Hook in and add a metabox for axis
 */
function bimba_register_3d_plan_axis_metabox(){

	$prefix_group = '_3d_plan_';

	$cmb_group = new_cmb2_box( array(
			'id'           => $prefix_group . 'h_axis_metabox',
			'title'        => esc_html__( 'Horizontal Axis', 'bim-ba' ),
			'object_types' => array( 'bimba_3d_plan', ),
			'closed'     => true,
	) );

	$group_field_id = $cmb_group->add_field( array(
			'id'          => $prefix_group . 'h_axis',
			'type'        => 'group',
			'options'     => array(
					'group_title'   => esc_html__( 'Horizontal Axis {#}', 'bim-ba' ), // {#} gets replaced by row number
					'add_button'    => esc_html__( 'Add Another Horizontal Axis', 'bim-ba' ),
					'remove_button' => esc_html__( 'Remove Horizontal Axis', 'bim-ba' ),
					'sortable'      => true, // beta
					'closed'     => true, // true to have the groups closed by default
			),
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Name', 'bim-ba' ),
			'description' => esc_html__( 'Computer generated', 'bim-ba' ),
			'id'          => 'name',
			'type'        => 'text',
			'sanitization_cb' => 'bimba_3d_plan_assign_h_axis_name',
			'attributes' => array(
					'readonly'    => true,
			),
	) );

	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Distance', 'bim-ba' ),
			'description' => esc_html__( 'Centimeters from origin in South (Z) direction', 'bim-ba' ),
			'id'          => 'distance',
			'type'        => 'bimba_number_neg',
	) );

	$cmb_group2 = new_cmb2_box( array(
			'id'           => $prefix_group . 'v_axis_metabox',
			'title'        => esc_html__( 'Vertical Axis', 'bim-ba' ),
			'object_types' => array( 'bimba_3d_plan', ),
			'closed'     => true,
	) );

	$group_field_id2 = $cmb_group2->add_field( array(
			'id'          => $prefix_group . 'v_axis',
			'type'        => 'group',
			'options'     => array(
					'group_title'   => esc_html__( 'Vertical Axis {#}', 'bim-ba' ), // {#} gets replaced by row number
					'add_button'    => esc_html__( 'Add Another Vertical Axis', 'bim-ba' ),
					'remove_button' => esc_html__( 'Remove Vertical Axis', 'bim-ba' ),
					'sortable'      => true, // beta
					'closed'     => true, // true to have the groups closed by default
			),
	) );
	$cmb_group2->add_group_field( $group_field_id2, array(
			'name'        => esc_html__( 'Name', 'bim-ba' ),
			'description' => esc_html__( 'Computer generated', 'bim-ba' ),
			'id'          => 'name',
			'type'        => 'text',
			'sanitization_cb' => 'bimba_3d_plan_assign_v_axis_name',
			'attributes' => array(
					'readonly'    => true,
			),
	) );

	$cmb_group2->add_group_field( $group_field_id2, array(
			'name'        => esc_html__( 'Distance', 'bim-ba' ),
			'description' => esc_html__( 'Centimeters from origin in East (X) direction', 'bim-ba' ),
			'id'          => 'distance',
			'type'        => 'bimba_number_neg',
	) );

}

function bimba_3d_plan_assign_h_axis_name( $value, $field_args, $field ) {
	if ( ! $value ) {
		global $post;
		$record = get_post_meta( $post->ID, '_3d_plan_name_records', true );
		$record['h'] ++;
		$value = esc_html__( 'H', 'bim-ba' ) . '-' . $record['h'];
		update_post_meta( $post->ID, '_3d_plan_name_records', $record );
	}
	return $value;
}

function bimba_3d_plan_assign_v_axis_name( $value, $field_args, $field ) {
	if ( ! $value ) {
		global $post;
		$record = get_post_meta( $post->ID, '_3d_plan_name_records', true );
		$record['v'] ++;
		$value = esc_html__( 'V', 'bim-ba' ) . '-' . $record['v'];
		update_post_meta( $post->ID, '_3d_plan_name_records', $record );
	}
	return $value;
}

add_action( 'cmb2_admin_init', 'bimba_register_3d_plan_wall_metabox' );

/**
 * Hook in and add a metabox for walls
 */
function bimba_register_3d_plan_wall_metabox(){

	$prefix_group = '_3d_plan_';

	$cmb_group = new_cmb2_box( array(
			'id'           => $prefix_group . 'wall_metabox',
			'title'        => esc_html__( 'Wall Elements', 'bim-ba' ),
			'object_types' => array( 'bimba_3d_plan', ),
	) );

	$group_field_id = $cmb_group->add_field( array(
			'id'          => $prefix_group . 'walls',
			'type'        => 'group',
			'options'     => array(
					'group_title'   => esc_html__( 'Wall Element {#}', 'bim-ba' ), // {#} gets replaced by row number
					'add_button'    => esc_html__( 'Add Another Wall Element', 'bim-ba' ),
					'remove_button' => esc_html__( 'Remove Wall Element', 'bim-ba' ),
					'sortable'      => true, // beta
					'closed'     => true, // true to have the groups closed by default
			),
	) );
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Name', 'bim-ba' ),
			'description' => esc_html__( 'Computer generated', 'bim-ba' ),
			'id'          => 'name',
			'type'        => 'text',
			'sanitization_cb' => 'bimba_3d_plan_assign_wall_name',
			'attributes' => array(
					'readonly'    => true,
			),
	) );

	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Wall Type', 'bim-ba' ),
			'id'          => 'type',
			'type'     	=> 'select',
			'show_option_none' => true,
			'options_cb' => 'bimba_get_3d_element_wall_options',
	) );

	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'On Axis', 'bim-ba' ),
			'id'          => 'on',
			'type'     	=> 'select',
			'show_option_none' => true,
			'options_cb' => 'bimba_get_3d_plan_axis_name_options',
	) );
	
	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'Position', 'bim-ba' ),
			'desc'        => esc_html__( 'With respect to the Axis the Wall element is on', 'bim-ba' ),
			'id'          => 'position',
			'type'        => 'select',
			'options'          => array(
					'0' => esc_html__( 'Left / Over', 'bim-ba' ),
					'1'   => esc_html__( 'Right / Under', 'bim-ba' ),
			),
	) );

	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'From Axis', 'bim-ba' ),
			'desc'        => esc_html__( 'Perpendicular to the Axis the Wall element is on', 'bim-ba' ),
			'id'          => 'from',
			'type'     	=> 'select',
			'show_option_none' => true,
			'options_cb' => 'bimba_get_3d_plan_axis_name_options',
	) );

	$cmb_group->add_group_field( $group_field_id, array(
			'name'        => esc_html__( 'To Axis', 'bim-ba' ),
			'desc'        => esc_html__( 'Perpendicular to the Axis the Wall element is on', 'bim-ba' ),
			'id'          => 'to',
			'type'     	=> 'select',
			'show_option_none' => true,
			'options_cb' => 'bimba_get_3d_plan_axis_name_options',
	) );

}

function bimba_3d_plan_assign_wall_name( $value, $field_args, $field ) {
	if ( ! $value ) {
		global $post;
		$record = get_post_meta( $post->ID, '_3d_plan_name_records', true );
		$record['wall'] ++;
		$value = esc_html__( 'Wall', 'bim-ba' ) . '-' . $record['wall'];
		update_post_meta( $post->ID, '_3d_plan_name_records', $record );
	}
	return $value;
}

function bimba_get_3d_plan_axis_name_options() {
	global $post;
	$h_axis = get_post_meta( $post->ID, '_3d_plan_h_axis', true );
	$v_axis = get_post_meta( $post->ID, '_3d_plan_v_axis', true );

	$post_options = array();
	if ( $h_axis ) {
		foreach ( $h_axis as $h ) {
			$post_options[ $h ['name'] ] = $h ['name'];
		}
	}
	if ( $v_axis ) {
		foreach ( $v_axis as $v ) {
			$post_options[ $v ['name'] ] = $v ['name'];
		}
	}

	return $post_options;
}

function bimba_get_element_options( $terms ) {

	$args = array(
			'post_type' 	=> 'bimba_3d_element',
			'numberposts' 	=> -1,
			'tax_query'		=> array( array(
					'taxonomy'			=> 'element_category',
					'field'				=> 'slug',
					'terms'				=> $terms,
					'include_children'	=> true,
			) ),
	);

	$posts = get_posts( $args );

	$post_options = array();
	if ( $posts ) {
		foreach ( $posts as $post ) {
			$post_options[ $post->ID ] = $post->post_title;
		}
	}

	return $post_options;
}

function bimba_get_3d_element_wall_options() {
	return bimba_get_element_options( array( 'wall', 'walls', ) );
}