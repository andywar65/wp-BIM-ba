<?php

class Bimba3dAmbient{
	
	public $room_set = array();//room general settings
	public $walls_total = array();//wall data
	public $room_warnings = array();//here we store room related warnings
	public $wall_warnings = array();//here we store wall related warnings
	public $material_record = array();//we record all materials used in the room
	public $room_data = array();//stores room data
	public $parent_coord = array();//stores parent coordinates
	public $centers = array();//stores centers of walls
	public $floor_dim = array();//stores floor envelopes
	public $global_flag = '';//parent or child?

	/**
	 * Start up
	 */
	public function __construct()
	{
		add_action( 'init', array( $this, 'bimba_register_3d_ambient_cpt' ) );
		add_filter( 'the_content', array( $this, 'bimba_3d_ambient_render_in_the_content_filter' ) );
	}

	public function bimba_register_3d_ambient_cpt(){
		$labels = array(
				'name' => esc_html__( '3D Ambients', 'bim-ba' ),
				'singular_name' => esc_html__( '3D Ambient', 'bim-ba' ),
				'add_new' => esc_html__( 'Add New', 'bim-ba' ),
				'add_new_item' => esc_html__( 'Add 3D Ambient', 'bim-ba' ),
				'edit_item' => esc_html__( 'Modify 3D Ambient', 'bim-ba' ),
				'new_item' => esc_html__( 'New 3D Ambient', 'bim-ba' ),
				'all_items' => esc_html__( 'All 3D Ambients', 'bim-ba' ),
				'view_item' => esc_html__( 'Wiev 3D Ambient', 'bim-ba' ),
				'search_items' => esc_html__( 'Search 3D Ambient', 'bim-ba' ),
				'not_found' =>  esc_html__( 'No 3D Ambient', 'bim-ba' ),
				'not_found_in_trash' => esc_html__( 'No 3D Ambient in Trash', 'bim-ba' ),
				'menu_name' => 'BIM-ba'
		);
	
		$args = array(
				'hierarchical' => true,
				'labels' => $labels,
				'public' => true,
				'publicly_queryable' => true,
				'show_ui' => true,
				'show_in_menu' => true,
				'query_var' => true,
				'rewrite' => array('slug' => _x('3d-ambient', 'URL slug', 'bim-ba')),
				'capability_type' => 'page',
				'has_archive' => true,
				'menu_position' => 100,
				'supports' => array( 'title', 'editor', 'content', 'thumbnail', 'page-attributes', 'comments', 'author' ),
				'menu_icon'   => 'dashicons-layout'
		);
	
		register_post_type( 'bimba_3d_ambient', $args );
	
	}
	
	/**
	 * This filter sets up the rendering for VR
	 */
	
	public function bimba_3d_ambient_render_in_the_content_filter( $content ){
		
		if ( get_post_type() == 'bimba_3d_ambient' AND ! post_password_required() ){//it works only for bimba_3d_ambient cpt AND post_password_required()
			//starts rendering scene and assets
			$content .= '<a-scene style="width: 100%; height: 500px" embedded>';
			
			$id = get_the_ID();//for retreiving post meta
			
			$temp = get_post_meta( $id, '_3d_ambient_general_settings', true );
			$this->room_set [$id] = $temp [0];//room general settings by ID
			
			$this->room_set [$id] ['name']= basename( get_permalink( $id ) );//room name for id attributes
			
			$this->walls_total [$id] = get_post_meta( $id, '_3d_ambient_group_walls', true );//all the wall general settings
			
			$content .= '
					<a-assets>';//starts assets
			
			$content .= $this->bimba_3d_ambient_register_assets( $id );
			
			$children = $this->room_set [$id] ['render_children'];//eventual children to be rendered
			
			if ( $children ) {
				$this->global_flag = 1;
				foreach ($children as $child ){
					
					$temp = get_post_meta( $child, '_3d_ambient_general_settings', true );
					$this->room_set [$child] = $temp [0];//child room general settings by ID
					
					$this->room_set [$child] ['name']= basename( get_permalink( $child ) );//child room name for id attributes
					
					$this->walls_total [$child] = get_post_meta( $child, '_3d_ambient_group_walls', true );//all the wall general settings
					
					$content .= $this->bimba_3d_ambient_register_assets( $child );
					
				}
			}
			
			$content .= $this->bimba_3d_ambient_register_material_assets();
			
			$content .= '
					</a-assets>';//here end the assets
			
			$this->room_data [$id] = array();//flush any room data previously stored
			
			$coords = $this->bimba_3d_ambient_extract_polygon_coords( $id );
			
			$this->centers[$id] = $this->bimba_3d_ambient_extract_wall_centers( $coords );
			
			$this->room_data[$id]['floor_area'] = $this->bimba_3d_ambient_extract_floor_area( $coords );
			
			$this->floor_dim[$id] = $this->bimba_3d_ambient_extract_floor_dimensions( $this->centers[$id] );
			
			$content .= $this->bimba_3d_ambient_render_room( $id );
			
			if ( $children ) {
				//gets the parent global coordinates
				$this->parent_coord = $this->bimba_3d_ambient_parent_coords( $id );
				
				foreach ($children as $child ){
					
					$this->room_data [$child] = array();//flush any room data previously stored
			
					$child_coords = $this->bimba_3d_ambient_extract_polygon_coords( $child );
					
					$this->centers[$child] = $this->bimba_3d_ambient_extract_wall_centers( $child_coords );
					
					$this->room_data[$child]['floor_area'] = $this->bimba_3d_ambient_extract_floor_area( $child_coords );
					
					$this->floor_dim[$child] = $this->bimba_3d_ambient_extract_floor_dimensions( $this->centers[$child] );
					
					$content .= $this->bimba_3d_ambient_render_room( $child );
			
				}
			}
			//entities are all set up, let's add background and camera
			$content .= '<a-sky color="black"></a-sky>
						<a-entity id="bimba-camera-ent">
	        				<a-camera id="bimba-camera" wasd-controls="fly: ' . $this->room_set [$id] ['camera_fly'] . '">
								<a-light type="point"></a-light>    
	        					<a-entity position="0 -1.6 0" id="camera-foot"></a-entity>
								<a-cursor color="#2E3A87"></a-cursor>
	        				</a-camera>
	      				</a-entity>
					</a-scene>';//close a-scene tag 
			
			//here we display room data...
			
			$content .= $this->bimba_3d_ambient_display_data( $id );
			
			//...and eventual child data
			if ( $children ) {
				
				foreach ($children as $child ){
					$content .= $this->bimba_3d_ambient_display_data( $child );
				}
				//and total data
				$content .= $this->bimba_3d_ambient_display_total_data();
			}
			
		}
		
		return $content;
	}
	
	/**
	 * Returns the parent's coordinates
	 */
	
	public function bimba_3d_ambient_parent_coords( $id ){
		$parent_diff = get_post_meta( $id, '_3d_ambient_global_diff', true );
		$parent_coord = array(
				'parent_x'	=> $parent_diff [0] ['x'],
				'parent_y'	=> $parent_diff [0] ['y'],
				'parent_z'	=> $parent_diff [0] ['z'],
				'parent_rot'	=> $parent_diff [0] ['rot'],
		);
		return $parent_coord;
	}
	
	/**
	 * Returns the material assets
	 */
	
	public function bimba_3d_ambient_register_material_assets(){
		foreach ($this->material_record as $key => $material_id) {
			$material_render = get_post_meta( $material_id, '_3d_material_render', true );
			$return .= '
					<img id="bimba-material-' . $material_id . '" src="' . $material_render [0] [ 'image' ] . '">';
			//if ($material_render [0] [ 'frame_image' ]) {
				//$return .= '
					//<img id="bimba-frame-material-' . $material_id . '" src="' . $material_render [0] [ 'frame_image' ] . '">';
			//}
		}
		return $return;
	}
	
	/**
	 * Returns the polyline coordinates from wall info
	 */
	
	public function bimba_3d_ambient_extract_polygon_coords( $id ){
		
		$walls = $this->walls_total [$id];
		
		$i = 1;
		foreach ($walls as $wall){
			
			switch ( $wall['direction'] ){
				case 0:
					$coords [$i]['x'] = $coords [$i-1]['x'] + $wall['length']/100;
					$coords [$i]['z'] = $coords [$i-1]['z'];
					break;
				case 1:
					$coords [$i]['x'] = $coords [$i-1]['x'];
					$coords [$i]['z'] = $coords [$i-1]['z'] + $wall['length']/100;
					break;
				case 2:
					$coords [$i]['x'] = $coords [$i-1]['x'] - $wall['length']/100;
					$coords [$i]['z'] = $coords [$i-1]['z'];
					break;
				case 3:
					$coords [$i]['x'] = $coords [$i-1]['x'];
					$coords [$i]['z'] = $coords [$i-1]['z'] - $wall['length']/100;
					break;
			}
			if ( ! $wall['length'] ) {
				$this->wall_warnings [ $id ][$i] = $i;
			}
			
			$i ++ ;
		}
		
		$this->room_warnings [ $id ]['x'] = $coords [$i-1]['x'] * 100;
		$this->room_warnings [ $id ]['z'] = $coords [$i-1]['z'] * 100;
		
		return $coords;
	}
	
	/**
	 * Returns wall centers from polyline coordinates
	 */
	
	public function bimba_3d_ambient_extract_wall_centers( $coords ){
		
		$centers = array();
		$i = 0;
			foreach ($coords as $coord){
				$centers [$i]['x'] = ( $coords [$i+1]['x'] - $coords [$i]['x'] ) / 2 + $coords [$i]['x'];
				$centers [$i]['z'] = ( $coords [$i+1]['z'] - $coords [$i]['z'] ) / 2 + $coords [$i]['z'];
				$i ++ ;
			}
			
		return $centers;
	}
	
	/**
	 * Returns floor area
	 */
	
	public function bimba_3d_ambient_extract_floor_area( $coords ){
		
		$i = 1;
		foreach ($coords as $coord){
			$tot1 = $tot1 + $coords [$i]['x'] * $coords [$i+1]['z'];
			$tot2 = $tot2 + $coords [$i+1]['x'] * $coords [$i]['z'];
			$i ++ ;
		}
		
		$floor_area = ( $tot1 - $tot2 ) / 2;
		return $floor_area;
	}
	
	/**
	 * Returns the envelope of the wall polyline
	 */
	
	public function bimba_3d_ambient_extract_floor_dimensions( $centers ){
		$floor_dim = array();
		foreach ($centers as $center){
			if( $xmin > $center ['x'] ){
				$xmin = $center ['x'];
			}
			if( $xmax < $center ['x'] ){
				$xmax = $center ['x'];
			}
			if( $zmin > $center ['z'] ){
				$zmin = $center ['z'];
			}
			if( $zmax < $center ['z'] ){
				$zmax = $center ['z'];
			}
		}
		$floor_dim ['width'] = $xmax - $xmin ;
		$floor_dim ['height'] = $zmax - $zmin ;
		
		return $floor_dim;
	}
	
	/**
	 * Starts the a-scene tag and prepares the assets
	 */
	
	function bimba_3d_ambient_register_assets( $id ){
		
		//global images
		$room_name = $this->room_set[ $id ]['name'] ;
		$floor_image = $this->room_set[ $id ]['floor_image'] ;
		$ceiling_image = $this->room_set[ $id ]['ceiling_image'] ;
		
		$this->bimba_3d_ambient_feed_material_record( $id, 'floor_material' );
		
		if( $floor_image ){
			$return .= '
					<img id="' . $room_name . '-floor-image" src="' . $floor_image . '">';
		} 
		
		$this->bimba_3d_ambient_feed_material_record( $id, 'wall_material' );
		$this->bimba_3d_ambient_feed_material_record( $id, 'tiling_material' );
		$this->bimba_3d_ambient_feed_material_record( $id, 'skirting_material' );
		$this->bimba_3d_ambient_feed_material_record( $id, 'ceiling_material' );
		
		if( $ceiling_image ){
			$return .= '
					<img id="' . $room_name . '-ceiling-image" src="' . $ceiling_image . '">';
		}
		//local wall images
		$walls = $this->walls_total [$id];
		$i = 0;
		foreach ($walls as $wall){
			$j = $i + 1;
			if( $wall [ 'image' ]){
				$return .= '
						<img id="' . $room_name . '-wall-image-' . $j . '" src="' . $wall [ 'image' ] . '">';
			}
			if( $wall [ 'wall_material' ]){
				$this->material_record [$wall [ 'wall_material' ]]= $wall [ 'wall_material' ];
			}
			if( $wall [ 'tiling_material' ]){
				$this->material_record [$wall [ 'tiling_material' ]]= $wall [ 'tiling_material' ];
			}
			if( $wall [ 'op_material' ]){
				$this->material_record [$wall [ 'op_material' ]]= $wall [ 'op_material' ];
			}
			if( $wall [ 'frame_material' ]){
				$this->material_record [$wall [ 'frame_material' ]]= $wall [ 'frame_material' ];
			}
			$i ++;
		}
		
		//object images
		$objects = get_post_meta( $id, '_3d_ambient_group_objects', true );
		$i = 0;
		foreach ($objects as $object){
			if( $object [ 'material' ]){
				$this->material_record [$object [ 'material' ]]= $object [ 'material' ];
			}
			$i ++;
		}
		
		return $return;
	}
	
	/**
	 * Records material ID as key AND value
	 */
	
	public function bimba_3d_ambient_feed_material_record( $id, $material ){
		if ( $this->room_set[ $id ][ $material ] ) {
			$this->material_record [$this->room_set[ $id ][ $material ]] = $this->room_set[ $id ][ $material ];
		}
	}
	
	/**
	 * Renders Room
	 */
	
	function bimba_3d_ambient_render_room( $id ){
		
		//set the room variables
		
		if ( ! $this->room_set [$id] ['height'] ) {//if room has no height it's not worth rendering it!
			return $return;
		}
		
		$room_name = $this->room_set [$id] ['name'];
		
		//calculations first
		
		$this->room_data[$id] ['paint_surf'] = $this->room_data[$id]['floor_area'];
		
		//Here we start rendering. The room is a whole entity, so you can move it around
		
		$return .= $this->bimba_start_room_entity( $id );
		
		//next is for floor rendering
		
		if ( $this->room_set [$id] ['floor_image'] ) {
			$this->room_set [$id] ['floor_render'] = 'src: #' . $room_name . '-floor-image ; color: #ffffff';
			$return .= $this->bimba_plane_entity_render_1_1( 'floor', $room_name, '0', $this->floor_dim [$id]['width'], $this->floor_dim [$id]['height'], 
					'0', '0', '0', '-90', '0', '0', $this->room_set [$id] ['floor_render'] );
		} else {
			$this->room_set [$id] ['floor_render'] = $this->bimba_3d_ambient_render_by_material( $this->room_set [$id] ['floor_material'] );
			$return .= $this->bimba_plane_entity_render( 'floor', $room_name, '0', $this->floor_dim [$id]['width'], $this->floor_dim [$id]['height'],
					'0', '0', '0', '-90', '0', '0', $this->room_set [$id] ['floor_render'] );
		}
		
		//next is for ceiling rendering
		
		if ( $this->room_set [$id] ['ceiling_image'] ) {
			$this->room_set [$id] ['ceiling_render'] = 'src: #' . $room_name . '-ceiling-image ; color: #ffffff';
			$return .= $this->bimba_plane_entity_render_1_1( 'ceiling', $room_name, '0', $this->floor_dim [$id]['width'], $this->floor_dim [$id]['height'],
					'0', ( $this->room_set [$id] ['height'] / 100 ), '0', '90', '0', '0', $this->room_set [$id] ['ceiling_render'] );
		} else {
			$this->room_set [$id] ['ceiling_render'] = $this->bimba_3d_ambient_render_by_material( $this->room_set [$id] ['ceiling_material'] );
			$return .= $this->bimba_plane_entity_render( 'ceiling', $room_name, '0', $this->floor_dim [$id]['width'], $this->floor_dim [$id]['height'],
					'0', ( $this->room_set [$id] ['height'] / 100 ), '0', '90', '0', '0', $this->room_set [$id] ['ceiling_render'] );
		}
		
		//the wall general settings
		
		$this->room_set [$id] ['wall_render'] = $this->bimba_3d_ambient_render_by_material( $this->room_set [$id] ['wall_material'] );
		
		$this->room_set [$id] ['tiling_render'] = $this->bimba_3d_ambient_render_by_material( $this->room_set [$id] ['tiling_material'] );
		
		//skirting render and control
		
		if ( $this->room_set [$id] ['skirting_height'] ){
			if ( $this->room_set [$id] ['skirting_height'] > 30 ){//we don't want skirtings higher than 30cm
				$this->room_set [$id] ['skirting_height'] = 30;
			}
			$this->room_set [$id] ['skirting_render'] = $this->bimba_3d_ambient_render_by_material( $this->room_set [$id] ['skirting_material'] );
		}
		
		//let's do the wall controls
		
		$this->bimba_3d_ambient_secondary_wall_controls( $id );
		
		//then we calculate and render the walls
		
		$return .= $this->bimba_3d_ambient_render_walls( $id );
		
		$return .= '
				</a-entity><!-- End '.$room_name.' Room Entity -->';//end of room entity
		
		$this->bimba_3d_ambient_update_general_post_meta( $id );
		
		return $return;
	}
	
	/**
	 * Renders single wall
	 */
	
	public function bimba_3d_ambient_render_walls( $id ){
		
		$room_name = $this->room_set [$id] ['name'];
		
		$height = $this->room_set [$id] ['height'] / 100;
		
		$sk_height = $this->room_set [$id] ['skirting_height'] / 100;
		
		$objects = get_post_meta( $id, '_3d_ambient_group_objects', true );//we will need objects later
		
		$walls = $this->walls_total [$id];
		
		$i = 0;
		foreach ($walls as $wall){
			
			$wall_length = $wall ['length'] / 100;//this is first and most important wall control
			if ( $wall_length ) {//we process the wall only if length is not null
				
				//All the calculations
				$this->bimba_3d_ambient_calculations( $id, $i );
				
				//let's start rendering!
				
				//entity that holds the wall element
				$centers = $this->centers [$id];
				$j = $i + 1;
				$return .= '
						<a-entity id="' . $room_name . '-wall-ent-' . $j . '" 
						position="' . ( $centers [$i] ['x'] - $this->floor_dim [$id]['width'] / 2 ) . ' 0 ' . ( $centers [$i] ['z'] - $this->floor_dim [$id]['height'] / 2 ) . '" 
						rotation="0 ' . ( $wall ['direction'] * ( -90 ) ) . ' 0">';
				$return .= '
						<a-entity id="' . $room_name . '-wall-' . $j . '-label" scale="2 2 2" position="0 0.1 0.01" text="value: ' . $wall ['name'] . ';color: black; align: center;"></a-entity>';
				
				//wall element 
				
				if ( $wall [ 'image' ] ) {
					$wall_render = 'src: #' . $room_name . '-wall-image-' . $j . '; color: #ffffff';
							$return .= $this->bimba_plane_entity_render_1_1( 'wall', $room_name, $j, $wall_length, $height,
									'0', $height / 2, '0', '0', '0', '0', $wall_render );
				} else {
					
					//see if we have local colors, if not just get global colors
					
					if( $wall ['wall_material'] ){
						$wall_material = get_post_meta( $wall ['wall_material'], '_3d_material_render', true );
						$wall_render = 'src: #bimba-material-' . $wall ['wall_material'] . '; color:' . $wall_material [0] ['color'];
					} else {
						$wall_render = $this->room_set [$id] ['wall_render'];
					}
					if( $wall ['tiling_material'] ){
						$tiling_material = get_post_meta( $wall ['tiling_material'], '_3d_material_render', true );
						$tiling_render = 'src: #bimba-material-' . $wall ['tiling_material'] . '; color:' . $tiling_material [0] ['color'];
					} else {
						$tiling_render = $this->room_set [$id] ['tiling_render'];
					}
					
					$tiling_height = $wall ['tiling_height'] / 100;
					
					switch ( $wall ['openings'] ){
						
						case 0://no opening
							
							$wall_height = $height;
							$wall_y = $height / 2;
							if ( $tiling_height ){
								$wall_height = $height - $tiling_height;
								$wall_y = ( $height - $tiling_height ) / 2 + $tiling_height;
								$return .= $this->bimba_plane_entity_render( 'tiling', $room_name, $j, $wall_length, $tiling_height,
										'0', $tiling_height / 2, '0', '0', '0', '0', $tiling_render );
							} elseif ( $sk_height ) {
								$wall_height = $height - $sk_height;
								$wall_y = ( $height - $sk_height ) / 2 + $sk_height;
								$return .= $this->bimba_plane_entity_render_1_1( 'skirting', $room_name, $j, $wall_length, $sk_height,
										'0', $sk_height / 2, '0', '0', '0', '0', $this->room_set [$id] ['skirting_render'] );
							}
								
							$return .= $this->bimba_plane_entity_render( 'wall', $room_name, $j, $wall_length, $wall_height,
									'0', $wall_y, '0', '0', '0', '0', $wall_render );
						break;
						
						case 1 OR 2://door or window
							//retrieve data
							$op_width = $wall [ 'op_width' ] / 100;
							$op_height = $wall [ 'op_height' ] / 100;
							$op_offset = $wall [ 'op_offset' ] / 100;
							$wall_depth = $wall [ 'wall_depth' ] / 100;
							$sill_height = $wall [ 'sill_height' ] / 100;
							$sill_depth = $wall [ 'sill_depth' ] / 100;
							
							//left shoulder
							if ( $op_offset ) {
								$wall_height = $height;
								$wall_y = $height / 2;
								if ( $tiling_height ) {
									$wall_height = $height - $tiling_height;
									$wall_y = ( $height - $tiling_height ) / 2 + $tiling_height;
									$return .= $this->bimba_plane_entity_render( 'tiling', $room_name, ( $j . '-ls' ) , $op_offset, $tiling_height,
											- ( $wall_length - $op_offset ) / 2 , $tiling_height / 2 , '0', '0', '0', '0', $tiling_render );
								} elseif ( $sk_height ) {
									$wall_height = $height - $sk_height;
									$wall_y = ( $height - $sk_height ) / 2 + $sk_height;
									$return .= $this->bimba_plane_entity_render_1_1( 'skirting', $room_name, ( $j . '-ls' ) , $op_offset, $sk_height,
											- ( $wall_length - $op_offset ) / 2 , $sk_height / 2 , '0', '0', '0', '0', $this->room_set [$id] ['skirting_render'] );
								}
								$return .= $this->bimba_plane_entity_render( 'wall', $room_name, ( $j . '-ls' ) , $op_offset, $wall_height,
										- ( $wall_length - $op_offset ) / 2 , $wall_y , '0', '0', '0', '0', $wall_render );
							}
							//right shoulder
							$op_offset_right = $wall_length - $op_offset - $op_width;
							if ( $op_offset_right ) {
								$wall_height = $height;
								$wall_y = $height / 2;
								if ( $tiling_height ) {
									$wall_height = $height - $tiling_height;
									$wall_y = ( $height - $tiling_height ) / 2 + $tiling_height;
									$return .= $this->bimba_plane_entity_render( 'tiling', $room_name, ( $j . '-rs' ) , $op_offset_right, $tiling_height,
											( $wall_length - $op_offset_right ) / 2 , $tiling_height / 2 , '0', '0', '0', '0', $tiling_render );
										
								} elseif ( $sk_height ) {
									$wall_height = $height - $sk_height;
									$wall_y = ( $height - $sk_height ) / 2 + $sk_height;
									$return .= $this->bimba_plane_entity_render_1_1( 'skirting', $room_name, ( $j . '-rs' ) , $op_offset_right, $sk_height,
											( $wall_length - $op_offset_right ) / 2 , $sk_height / 2 , '0', '0', '0', '0', $this->room_set [$id] ['skirting_render'] );
								}
								$return .= $this->bimba_plane_entity_render( 'wall', $room_name, ( $j . '-rs' ) , $op_offset_right, $wall_height,
										( $wall_length - $op_offset_right ) / 2 , $wall_y , '0', '0', '0', '0', $wall_render );
							}
							//lintel
							if ( $height - $op_height ) {
								$wall_height = $height - $op_height;
								$wall_y = ( $height - $op_height ) / 2 + $op_height;
								if ( $tiling_height > $op_height ) {
									$wall_height = $height - $tiling_height;
									$wall_y = ( $height - $tiling_height ) / 2 + $tiling_height;
									$return .= $this->bimba_plane_entity_render( 'tiling', $room_name, ( $j . '-lint' ) , $op_width , $tiling_height - $op_height,
											( $op_offset + $op_width / 2 ) - $wall_length / 2 , ( $tiling_height - $op_height ) / 2 + $op_height , '0',
											'0', '0', '0', $tiling_render );
								}
								$return .= $this->bimba_plane_entity_render( 'wall', $room_name, ( $j . '-lint' ) , $op_width , $wall_height,
										( $op_offset + $op_width / 2 ) - $wall_length / 2 , $wall_y , '0', '0', '0', '0', $wall_render );
							}
							//plafond
							if ( $wall_depth ) {
								if ( $tiling_height > $op_height ) {
									$return .= $this->bimba_plane_entity_render( 'tiling', $room_name, ( $j . '-pla' ) , $op_width , $wall_depth,
											( $op_offset + $op_width / 2 ) - $wall_length / 2 , $op_height , - $wall_depth / 2, '90', '0', '0', $tiling_render );
								} else {
									$return .= $this->bimba_plane_entity_render( 'wall', $room_name, ( $j . '-pla' ) , $op_width , $wall_depth,
											( $op_offset + $op_width / 2 ) - $wall_length / 2 , $op_height , - $wall_depth / 2, '90', '0', '0', $wall_render );
								}
							}
							//left depth
							if ( $wall_depth AND ! ( $wall['delete_side_wall'] == 1 OR $wall['delete_side_wall'] == 3 ) ) {
								if ( $sill_height ){
									//upper part
									$tiling_wall_height = $tiling_height;
									$wall_height = $op_height - $sill_height;
									$wall_y = ( $op_height - $sill_height ) / 2 + $sill_height;
									if ( $tiling_wall_height ) {
										if ( $tiling_wall_height > $op_height ) {
											$tiling_wall_height = $op_height;
										}
										if ( $tiling_wall_height > $sill_height ) {
											$wall_height = $op_height - $tiling_wall_height;
											$wall_y = ( $op_height - $tiling_wall_height ) / 2 + $tiling_wall_height;
											$return .= $this->bimba_plane_entity_render( 'tiling', $room_name, ( $j . '-li' ) , $wall_depth, ( $tiling_wall_height - $sill_height ),
													- $wall_length / 2 + $op_offset , ( $tiling_wall_height - $sill_height ) / 2 + $sill_height, - $wall_depth / 2, '0', '90', '0', $tiling_render );
										} 
									}
									$return .= $this->bimba_plane_entity_render( 'wall', $room_name, ( $j . '-li' ) , $wall_depth , $wall_height ,
											- $wall_length / 2 + $op_offset, $wall_y, - $wall_depth / 2, '0', '90', '0', $wall_render );
									//lower part
									$tiling_wall_height = $tiling_height;
									$wall_height = $sill_height;
									$wall_y = $sill_height / 2;
									if ( $tiling_wall_height ) {
										if ( $tiling_height > $wall_height ) {
											$tiling_wall_height = $wall_height;
										}
										$wall_height = $sill_height - $tiling_wall_height;
										$wall_y = ( $sill_height - $tiling_wall_height ) / 2 + $tiling_wall_height;
										$return .= $this->bimba_plane_entity_render( 'tiling', $room_name, ( $j . '-li2' ) , ( $wall_depth - $sill_depth ), $tiling_wall_height,
												- $wall_length / 2 + $op_offset , $tiling_wall_height / 2 , - ( $wall_depth - $sill_depth ) / 2, '0', '90', '0', $tiling_render );
									} elseif ( $sk_height ) {
										$wall_height = $sill_height - $sk_height;
										$wall_y = ( $sill_height - $sk_height ) / 2 + $sk_height;
										$return .= $this->bimba_plane_entity_render_1_1( 'skirting', $room_name, ( $j . '-li2' ) , ( $wall_depth - $sill_depth ), $sk_height,
												- $wall_length / 2 + $op_offset , $sk_height / 2 , - ( $wall_depth - $sill_depth ) / 2, '0', '90', '0', $this->room_set [$id] ['skirting_render'] );
									}
									$return .= $this->bimba_plane_entity_render( 'wall', $room_name, ( $j . '-li2' ) , ( $wall_depth - $sill_depth ) , $wall_height ,
											- $wall_length / 2 + $op_offset, $wall_y, - ( $wall_depth - $sill_depth ) / 2, '0', '90', '0', $wall_render );
								} else {
									$tiling_wall_height = $tiling_height;
									$wall_height = $op_height;
									$wall_y = $op_height / 2;
									if ( $tiling_height ) {
										if ( $tiling_height > $wall_height ) {
											$tiling_wall_height = $wall_height;
										}
										$wall_height = $op_height - $tiling_wall_height;
										$wall_y = ( $op_height - $tiling_wall_height ) / 2 + $tiling_wall_height;
										$return .= $this->bimba_plane_entity_render( 'tiling', $room_name, ( $j . '-li' ) , $wall_depth, $tiling_wall_height,
												- $wall_length / 2 + $op_offset , $tiling_wall_height / 2 , - $wall_depth / 2, '0', '90', '0', $tiling_render );
									} elseif ( $sk_height ) {
										$wall_height = $op_height - $sk_height;
										$wall_y = ( $op_height - $sk_height ) / 2 + $sk_height;
										$return .= $this->bimba_plane_entity_render_1_1( 'skirting', $room_name, ( $j . '-li' ) , $wall_depth, $sk_height,
												- $wall_length / 2 + $op_offset , $sk_height / 2 , - $wall_depth / 2, '0', '90', '0', $this->room_set [$id] ['skirting_render'] );
									}
									$return .= $this->bimba_plane_entity_render( 'wall', $room_name, ( $j . '-li' ) , $wall_depth , $wall_height ,
											- $wall_length / 2 + $op_offset, $wall_y, - $wall_depth / 2, '0', '90', '0', $wall_render );
								}
								
							}
							//right depth
							if ( $wall_depth AND ! ( $wall['delete_side_wall'] == 2 OR $wall['delete_side_wall'] == 3 ) ) {
								if ( $sill_height ){
									//upper part
									$tiling_wall_height = $tiling_height;
									$wall_height = $op_height - $sill_height;
									$wall_y = ( $op_height - $sill_height ) / 2 + $sill_height;
									if ( $tiling_wall_height ) {
										if ( $tiling_wall_height > $op_height ) {
											$tiling_wall_height = $op_height;
										}
										if ( $tiling_wall_height > $sill_height ) {
											$wall_height = $op_height - $tiling_wall_height;
											$wall_y = ( $op_height - $tiling_wall_height ) / 2 + $tiling_wall_height;
											$return .= $this->bimba_plane_entity_render( 'tiling', $room_name, ( $j . '-ri' ) , $wall_depth, ( $tiling_wall_height - $sill_height ),
													$wall_length / 2 - $op_offset_right , ( $tiling_wall_height - $sill_height ) / 2 + $sill_height, - $wall_depth / 2, '0', '-90', '0', $tiling_render );
										}
									}
									$return .= $this->bimba_plane_entity_render( 'wall', $room_name, ( $j . '-ri' ) , $wall_depth , $wall_height ,
											$wall_length / 2 - $op_offset_right, $wall_y, - $wall_depth / 2, '0', '-90', '0', $wall_render );
									//lower part
									$tiling_wall_height = $tiling_height;
									$wall_height = $sill_height;
									$wall_y = $sill_height / 2;
									if ( $tiling_wall_height ) {
										if ( $tiling_wall_height > $wall_height ) {
											$tiling_wall_height = $wall_height;
										}
										$wall_height = $sill_height - $tiling_wall_height;
										$wall_y = ( $sill_height - $tiling_wall_height ) / 2 + $tiling_wall_height;
										$return .= $this->bimba_plane_entity_render( 'tiling', $room_name, ( $j . '-ri2' ) , ( $wall_depth - $sill_depth ), $tiling_wall_height,
												$wall_length / 2 - $op_offset_right , $tiling_wall_height / 2 , - ( $wall_depth - $sill_depth ) / 2, '0', '-90', '0', $tiling_render );
									} elseif ( $sk_height ) {
										$wall_height = $sill_height - $sk_height;
										$wall_y = ( $sill_height - $sk_height ) / 2 + $sk_height;
										$return .= $this->bimba_plane_entity_render_1_1( 'skirting', $room_name, ( $j . '-ri2' ) , ( $wall_depth - $sill_depth ), $sk_height,
												$wall_length / 2 - $op_offset_right , $sk_height / 2 , - ( $wall_depth - $sill_depth ) / 2, '0', '-90', '0', $this->room_set [$id] ['skirting_render'] );
									}
									$return .= $this->bimba_plane_entity_render( 'wall', $room_name, ( $j . '-ri2' ) , ( $wall_depth - $sill_depth ) , $wall_height ,
											$wall_length / 2 - $op_offset_right, $wall_y, - ( $wall_depth - $sill_depth ) / 2, '0', '-90', '0', $wall_render );
								} else {
									$tiling_wall_height = $tiling_height;
									$wall_height = $op_height;
									$wall_y = $op_height / 2;
									if ( $tiling_wall_height ) {
										if ( $tiling_wall_height > $wall_height ) {
											$tiling_wall_height = $wall_height;
										}
										$wall_height = $op_height - $tiling_wall_height;
										$wall_y = ( $op_height - $tiling_wall_height ) / 2 + $tiling_wall_height;
										$return .= $this->bimba_plane_entity_render( 'tiling', $room_name, ( $j . '-ri' ) , $wall_depth, $tiling_wall_height,
												$wall_length / 2 - $op_offset_right , $tiling_wall_height / 2 , - $wall_depth / 2, '0', '-90', '0', $tiling_render );
									} elseif ( $sk_height ) {
										$wall_height = $op_height - $sk_height;
										$wall_y = ( $op_height - $sk_height ) / 2 + $sk_height;
										$return .= $this->bimba_plane_entity_render_1_1( 'skirting', $room_name, ( $j . '-ri' ) , $wall_depth, $sk_height,
												$wall_length / 2 - $op_offset_right , $sk_height / 2 , - $wall_depth / 2, '0', '-90', '0', $this->room_set [$id] ['skirting_render'] );
									}
									$return .= $this->bimba_plane_entity_render( 'wall', $room_name, ( $j . '-ri' ) , $wall_depth , $wall_height ,
											$wall_length / 2 - $op_offset_right, $wall_y, - $wall_depth / 2, '0', '-90', '0', $wall_render );
								}
								
							}
							
							//davanzale
							if ( $sill_height AND $sill_depth ) {
								$return .= '
										<a-entity id="'.$room_name.'-dav-'.$j.'" 
										geometry="primitive: box; width: ' . $op_width . '; height: 0.03; depth: ' . ( $sill_depth + 0.03 ) . '"
										position="' . ( ( $op_offset + $op_width / 2 ) - $wall_length / 2 ) . ' ' . ( $sill_height - 0.015 ) . ' ' . ( - $wall_depth + $sill_depth / 2 ) . '" 
												material="color: #ffffff"></a-entity>';
							}
							
							//windowsill
							if ( $sill_height ) {
								$wall_height = $sill_height;
								$wall_y = $sill_height / 2;
								if ( $tiling_height ) {
									$tiling_sill_height = $tiling_height;
									if ( $tiling_sill_height > $sill_height ) {
										$tiling_sill_height = $sill_height;
									}
									$wall_height = $sill_height - $tiling_sill_height;
									$wall_y = ( $sill_height - $tiling_sill_height ) / 2 + $tiling_sill_height;
									$return .= $this->bimba_plane_entity_render( 'tiling', $room_name, ( $j . '-sill' ) , $op_width , $tiling_sill_height,
											( $op_offset + $op_width / 2 ) - $wall_length / 2 , $tiling_sill_height / 2, - ( $wall_depth - $sill_depth ) ,
											'0', '0', '0', $tiling_render );
								} elseif ( $sk_height ) {
									$sk_sill_height = $sk_height;
									if ( $sk_sill_height > $sill_height ) {
										$sk_sill_height = $sill_height;
									}
									$wall_height = $sill_height - $sk_sill_height;
									$wall_y = ( $sill_height - $sk_sill_height ) / 2 + $sk_sill_height;
									$return .= $this->bimba_plane_entity_render_1_1( 'skirting', $room_name, ( $j . '-sill' ) , $op_width , $sk_sill_height,
											( $op_offset + $op_width / 2 ) - $wall_length / 2 , $sk_sill_height / 2, - ( $wall_depth - $sill_depth ) , '0', '0', '0', $this->room_set [$id] ['skirting_render'] );
								}
								$return .= $this->bimba_plane_entity_render( 'wall', $room_name, ( $j . '-sill' ) , $op_width , $wall_height ,
										( $op_offset + $op_width / 2 ) - $wall_length / 2, $wall_y , - ( $wall_depth - $sill_depth ), '0', '0', '0', $wall_render );
							}
							//windowsill capping if side wall is deleted
							if ( $sill_height AND $sill_depth ) {
								$cap_height = $sill_height - 0.03;
								if ( $wall['delete_side_wall'] == 1 OR $wall['delete_side_wall'] == 3 ) {
									$wall_height = $cap_height ;
									$wall_y = ( $cap_height  ) / 2;
									if ( $tiling_height ) {
										$tiling_sill_height = $tiling_height;
										if ( $tiling_sill_height > $cap_height ) {
											$tiling_sill_height = $cap_height ;
										}
										$wall_height = $cap_height - $tiling_sill_height;
										$wall_y = ( $cap_height - $tiling_sill_height ) / 2 + $tiling_sill_height;
										$return .= $this->bimba_plane_entity_render( 'tiling', $room_name, ( $j . '-sill-lcap' ) , $sill_depth , $tiling_sill_height,
												- $wall_length / 2 + $op_offset , $tiling_sill_height / 2, - ( $wall_depth - $sill_depth / 2 ) ,
												'0', '-90', '0', $tiling_render );
									} elseif ( $sk_height ) {
										$sk_sill_height = $sk_height;
										if ( $sk_sill_height > $cap_height ) {
											$sk_sill_height = $cap_height ;
										}
										$wall_height = $cap_height - $sk_sill_height;
										$wall_y = ( $cap_height - $sk_sill_height ) / 2 + $sk_sill_height;
										$return .= $this->bimba_plane_entity_render_1_1( 'skirting', $room_name, ( $j . '-sill-lcap' ) , $sill_depth , $sk_sill_height,
												- $wall_length / 2 + $op_offset , $sk_sill_height / 2, - ( $wall_depth - $sill_depth / 2) , '0', '-90', '0', $this->room_set [$id] ['skirting_render'] );
									}
									$return .= $this->bimba_plane_entity_render( 'wall', $room_name, ( $j . '-sill-lcap' ) , $sill_depth , $wall_height ,
											- $wall_length / 2 + $op_offset , $wall_y , - ( $wall_depth - $sill_depth / 2), '0', '-90', '0', $wall_render );
								}
								if ( $wall['delete_side_wall'] == 2 OR $wall['delete_side_wall'] == 3 ) {
									$wall_height = $cap_height;
									$wall_y = $cap_height / 2;
									if ( $tiling_height ) {
										$tiling_sill_height = $tiling_height;
										if ( $tiling_sill_height > $cap_height ) {
											$tiling_sill_height = $cap_height;
										}
										$wall_height = $cap_height - $tiling_sill_height;
										$wall_y = ( $cap_height - $tiling_sill_height ) / 2 + $tiling_sill_height;
										$return .= $this->bimba_plane_entity_render( 'tiling', $room_name, ( $j . '-sill-rcap' ) , $sill_depth , $tiling_sill_height,
												$wall_length / 2 - $op_offset_right , $tiling_sill_height / 2, - ( $wall_depth - $sill_depth / 2 ) ,
												'0', '90', '0', $tiling_render );
									} elseif ( $sk_height ) {
										$sk_sill_height = $sk_height;
										if ( $sk_sill_height > $cap_height ) {
											$sk_sill_height = $cap_height;
										}
										$wall_height = $cap_height - $sk_sill_height;
										$wall_y = ( $cap_height - $sk_sill_height ) / 2 + $sk_sill_height;
										$return .= $this->bimba_plane_entity_render_1_1( 'skirting', $room_name, ( $j . '-sill-rcap' ) , $sill_depth , $sk_sill_height,
												$wall_length / 2 - $op_offset_right , $sk_sill_height / 2, - ( $wall_depth - $sill_depth / 2) , '0', '90', '0', $this->room_set [$id] ['skirting_render'] );
									}
									$return .= $this->bimba_plane_entity_render( 'wall', $room_name, ( $j . '-sill-rcap' ) , $sill_depth , $wall_height ,
											$wall_length / 2 - $op_offset_right , $wall_y , - ( $wall_depth - $sill_depth / 2), '0', '90', '0', $wall_render );
								}
							}
							//frame
							if ( $wall [ 'op_material' ] AND $op_width AND $op_height > $sill_height ) {
								
								$op_material = get_post_meta( $wall ['op_material'], '_3d_material_render', true );
								$op_render = 'src: #bimba-material-' . $wall [ 'op_material' ] . '; color: ' . $op_material [0] ['color'];
								$door_height = $op_height - $sill_height;
								$door_y = $door_height / 2 + $sill_height;
								
								switch ( $wall ['openings'] ){
									
									case 1://door
							
										$op_name = 'door';
										$frame_material = get_post_meta( $wall ['frame_material'], '_3d_material_render', true );
										$frame_render = 'src: #bimba-material-' . $wall [ 'frame_material' ] . '; color: ' . $frame_material [0] ['color'];
										$return .='<a-entity id="' . $room_name . '-door-frame-ent-' . $j . '" position="' . ( - $wall_length / 2 + $op_offset ) . ' 0 ' . ( -$wall_depth ) . '">';
										$return .= $this->bimba_3d_ambient_render_door_frame($room_name, $j, $door_height, $door_y, $frame_render, $op_width );
										$return .='</a-entity>';
							
									break;
											
									case 2://window
											
										$op_name = 'window';
										
									break;
								}//end switch opening
								
								switch ($wall ['animation']) {//door animation
										
									case 0://no movement
										$return .='<a-entity id="' . $room_name . '-' . $op_name . '-ent-' . $j . '" position="' . ( - $wall_length / 2 + $op_offset ) . ' 0 ' . ( -$wall_depth ) . '">';
										$return .='<a-entity id="'.$room_name.'-' . $op_name . '-'.$j.'" geometry="primitive: box; width: '.$op_width.'; height: '.$door_height.'; depth: 0.05"
												position="' . ( $op_width/2 ) . ' ' . $door_y . ' -0.025" material="' . $op_render . '"></a-entity></a-entity>';
										break;
								
									case 1://left hinge
										$return .='<a-entity id="' . $room_name . '-' . $op_name . '-ent-' . $j . '" position="' . ( - $wall_length / 2 + $op_offset ) . ' 0 ' . ( -$wall_depth ) . '">
											<a-animation attribute="rotation" from="0 0 0" to="0 -90 0" begin="click" repeat="1" direction="alternate"></a-animation>
											<a-entity id="'.$room_name.'-' . $op_name . '-'.$j.'" geometry="primitive: box; width: '.$op_width.'; height: '.$door_height.'; depth: 0.05"
												position="' . ( $op_width/2 ) . ' ' . $door_y . ' -0.025" material="' . $op_render . '; repeat:-1 1;"></a-entity></a-entity>';
										break;
								
									case 2://right hinge
										$return .='<a-entity id="' . $room_name . '-' . $op_name . '-ent-' . $j . '" position="' . ( -$wall_length / 2 + $op_offset + $op_width ) . ' 0 ' . ( -$wall_depth ) . '">
											<a-animation attribute="rotation" from="0 0 0" to="0 90 0" begin="click" repeat="1" direction="alternate"></a-animation>
											<a-entity id="'.$room_name.'-' . $op_name . '-'.$j.'" geometry="primitive: box; width: '. $op_width .'; height: '. $door_height .'; depth: 0.05"
												position="' . ( - $op_width/2 ) . ' ' . $door_y . ' -0.025" material="' . $op_render . '"></a-entity></a-entity>';
										break;
											
									case 5://double hinges
										$return .='<a-entity id="' . $room_name . '-' . $op_name . '-ent-' . $j . '" position="' . ( - $wall_length / 2 + $op_offset ) . ' 0 ' . ( -$wall_depth ) . '">
														<a-entity id="' . $room_name . '-' . $op_name . '-l-ent-' . $j . '" position="0 0 0">
											<a-animation attribute="rotation" from="0 0 0" to="0 -90 0" begin="click" repeat="1" direction="alternate"></a-animation>
											<a-entity id="'.$room_name.'-' . $op_name . '-l-'.$j.'" geometry="primitive: box; width: '. ($op_width / 2) .'; height: '.$door_height.'; depth: 0.05"
												position="' . ( $op_width/4 ) . ' ' . $door_y . ' -0.025" material="' . $op_render . '"></a-entity></a-entity>
														<a-entity id="' . $room_name . '-' . $op_name . '-r-ent-' . $j . '" position="' . $op_width . ' 0 0">
											<a-animation attribute="rotation" from="0 0 0" to="0 90 0" begin="click" repeat="1" direction="alternate"></a-animation>
											<a-entity id="'.$room_name.'-' . $op_name . '-r-'.$j.'" geometry="primitive: box; width: '. ($op_width / 2) .'; height: '. $door_height .'; depth: 0.05"
												position="' . ( - $op_width/4 ) . ' ' . $door_y . ' -0.025" material="' . $op_render . '; repeat:-1 1;"></a-entity></a-entity></a-entity>';
										break;
								
									case 3://slides left
										$return .='<a-entity id="' . $room_name . '-' . $op_name . '-ent-' . $j . '" position="' . ( - $wall_length / 2 + $op_offset ) . ' 0 ' . ( -$wall_depth ) . '">
											<a-animation attribute="position" from="' . ( - $wall_length / 2 + $op_offset ) . ' 0 ' . ( -$wall_depth ) . '"
													to="' . ( - $wall_length / 2 + $op_offset - $op_width ) . ' 0 ' . ( -$wall_depth ) . '" begin="click" repeat="1" direction="alternate"></a-animation>
											<a-entity id="'.$room_name.'-' . $op_name . '-'.$j.'" geometry="primitive: box; width: '.$op_width.'; height: '.$door_height.'; depth: 0.05"
												position="' . ( $op_width/2 ) . ' ' . $door_y . ' -0.05" material="' . $op_render . '; repeat:-1 1;"></a-entity></a-entity>';
										break;
								
									case 4://slides right
										$return .='<a-entity id="' . $room_name . '-' . $op_name . '-ent-' . $j . '" position="' . ( - $wall_length / 2 + $op_offset ) . ' 0 ' . ( -$wall_depth ) . '">
											<a-animation attribute="position" from="' . ( - $wall_length / 2 + $op_offset ) . ' 0 ' . ( -$wall_depth ) . '"
													to="' . ( - $wall_length / 2 + $op_offset + $op_width ) . ' 0 ' . ( -$wall_depth ) . '" begin="click" repeat="1" direction="alternate"></a-animation>
											<a-entity id="'.$room_name.'-' . $op_name . '-'.$j.'" geometry="primitive: box; width: '.$op_width.'; height: '.$door_height.'; depth: 0.05"
												position="' . ( $op_width/2 ) . ' ' . $door_y . ' -0.05" material="' . $op_render . '"></a-entity></a-entity>';
										break;
											
									case 6://double slides
										$return .='<a-entity id="' . $room_name . '-' . $op_name . '-ent-' . $j . '" position="' . ( - $wall_length / 2 + $op_offset ) . ' 0 ' . ( -$wall_depth ) . '">
													<a-entity id="' . $room_name . '-' . $op_name . '-l-ent-' . $j . '" position="0 0 0">
													<a-animation attribute="position" from="0 0 0" to="'. -($op_width / 2) .' 0 0" begin="click" repeat="1" direction="alternate"></a-animation>
													<a-entity id="'.$room_name.'-' . $op_name . '-l-'.$j.'" geometry="primitive: box; width: '. ($op_width / 2) .'; height: '.$door_height.'; depth: 0.05"
													position="' . ( $op_width/4 ) . ' ' . $door_y . ' -0.05" material="' . $op_render . '"></a-entity></a-entity>
													<a-entity id="' . $room_name . '-' . $op_name . '-r-ent-' . $j . '" position="' . $op_width . ' 0 0">
													<a-animation attribute="position" from="'. $op_width .' 0 0" to="'. ($op_width * 1.5) .' 0 0" begin="click" repeat="1" direction="alternate"></a-animation>
													<a-entity id="'.$room_name.'-' . $op_name . '-r-'.$j.'" geometry="primitive: box; width: '. ($op_width / 2) .'; height: '. $door_height .'; depth: 0.05"
													position="' . ( - $op_width/4 ) . ' ' . $door_y . ' -0.05" material="' . $op_render . '; repeat:-1 1;"></a-entity></a-entity></a-entity>';
										break;
								}//end switch opening animation
									
							}//end if opening material
							//floor
							if ( $wall_depth ) {
								$op_floor_render = $this->bimba_3d_ambient_render_by_material( $this->room_set [$id] ['floor_material'] );
								$return .= $this->bimba_plane_entity_render( 'floor', $room_name, $j , $op_width , ( $wall_depth - $sill_depth ),
										( $op_offset + $op_width / 2 ) - $wall_length / 2, '0' , - ( $wall_depth - $sill_depth ) / 2, '-90', '0', '0',
										$op_floor_render );
							}
							
						break;
					}//end if opening
					
				}//end if no wall image
				
				//now objects
				$ii = 0;
				foreach ($objects as $object) {
					$jj = $ii + 1;
					if ( $object [ 'wall-id' ] == $wall ['name'] ) {//OR is for retrocompatibility
						
						$return .= $this->bimba_3d_ambient_render_object( $object, $jj, $room_name, $wall_length, $height );
									
					}
					$ii ++;
				}
				
				$return .='
						</a-entity><!-- End '.$room_name.' Wall-'.$j.' Entity -->';//end of wall entity
			}//end if wall length
			$i ++;
		}//end foreach wall
		
		return $return;
	}
	
	/**
	 * Wall controls with feedback
	 */
	
	public function bimba_3d_ambient_secondary_wall_controls( $id ){
		
		$walls = $this->walls_total [$id];
		
		$i = 0;
		foreach ($walls as $wall){
			$height = $this->room_set [$id] ['height'];
			$wall_length = $wall ['length'];
			$tiling_height = $wall ['tiling_height'];
			if ( $tiling_height > $height ){//control if tiles are higher than room!
				$tiling_height = $height ;
				$this->walls_total [$id] [ $i ] [ 'tiling_height' ] = $tiling_height;// feedback changed data
			}
			if ( $wall ['openings'] ) {
					
				//retrieve data
				$op_width = $wall [ 'op_width' ];
				$op_height = $wall [ 'op_height' ];
				$op_offset = $wall [ 'op_offset' ];
				$wall_depth = $wall [ 'wall_depth' ];
				$sill_height = $wall [ 'sill_height' ];
				$sill_depth = $wall [ 'sill_depth' ];
					
				//controls
				if( $op_width > $wall_length ){
					$op_width = $wall_length;
				}
				if( ( $op_offset + op_width ) > $wall_length ){
					$op_offset = $wall_length - op_width;
				}
				if ($op_offset < 0) {
					$op_offset = ( $wall_length - $op_width ) / 2;
				}
				if( $op_height > $height ){
					$op_height = $height;
				}
				if ( $sill_height ) {
					if( $sill_height > $op_height ){
						$sill_height = $op_height;
						//$wall_depth = $sill_depth;
					}
					if( $sill_depth > $wall_depth ){
						$sill_depth = $wall_depth;
					}
				} else {//there is no windowsill
					$sill_depth = 0;
				}
	
				//feedback eventual changed data
				$this->walls_total [$id]  [ $i ] [ 'op_width' ] = $op_width;
				$this->walls_total [$id]  [ $i ] [ 'op_height' ] = $op_height;
				$this->walls_total [$id]  [ $i ] [ 'op_offset' ] = round( $op_offset );
				$this->walls_total [$id]  [ $i ] [ 'wall_depth' ] = $wall_depth;
				$this->walls_total [$id]  [ $i ] [ 'sill_height' ] = $sill_height;
				$this->walls_total [$id]  [ $i ] [ 'sill_depth' ] = $sill_depth;
			
			}
			$i ++;
		}
		
		
	}
	
	/**
	 * Wall calculations
	 */
	
	public function bimba_3d_ambient_calculations( $id, $i ){
		
		//retrieve data
		$wall = $this->walls_total [$id] [$i];
		$height = $this->room_set [$id] ['height'] / 100;
		$wall_length = $wall ['length'] / 100;
		$tiling_height = $wall ['tiling_height'] / 100;
		$sk_height = $this->room_set [$id] ['skirting_height'];
		$op_width = $wall [ 'op_width' ] / 100;
		$op_height = $wall [ 'op_height' ] / 100;
		$op_offset = $wall [ 'op_offset' ] / 100;
		$wall_depth = $wall [ 'wall_depth' ] / 100;
		$sill_height = $wall [ 'sill_height' ] / 100;
		$sill_depth = $wall [ 'sill_depth' ] / 100;
		
		switch ( $wall ['openings'] ){
				
			case 0://no opening
				if ( $tiling_height ){//do we have tiling?
					$this->room_data[$id] ['tiling_surf'] = $this->room_data[$id] ['tiling_surf'] + ( $wall_length * $tiling_height );
				} elseif ( $sk_height ){
					$this->room_data[$id] ['skirt_length'] = $this->room_data[$id] ['skirt_length'] + $wall_length;
				}
				$this->room_data[$id] ['paint_surf'] = $this->room_data[$id] ['paint_surf'] + ( $wall_length * ( $height - $tiling_height ) );
				break;
					
			case 1 OR 2://door or window
				//opening shoulders
				$this->room_data[$id] ['tiling_surf'] = $this->room_data[$id] ['tiling_surf'] + ( $wall_length - $op_width) * $tiling_height;
				$this->room_data[$id] ['paint_surf'] = $this->room_data[$id] ['paint_surf'] + ( $wall_length - $op_width) * ( $height - $tiling_height );
				//opening center
				$total_op_surf = $op_width * ( $height - $op_height ) + $op_width * $wall_depth + 2 * $wall_depth * $op_height -
				2 * $sill_depth * $sill_height + $op_width * $sill_depth +  $op_width * $sill_height;
				$tiling_op_surf = 0;
				if ( $tiling_height ){//do we have tiling?
					if ( $tiling_height > $op_height ){//tiling above window height
						$tiling_op_surf = $op_width * ( $tiling_height - $op_height ) + $op_width * $wall_depth + 2 * $wall_depth * $op_height -
						2 * $sill_depth * $sill_height + $op_width * $sill_depth +  $op_width * $sill_height;
					} elseif ( $tiling_height > $sill_height ){//tiling above windowsill height
						$tiling_op_surf = 2 * $wall_depth * $tiling_height -
						2 * $sill_depth * $sill_height + $op_width * $sill_depth +  $op_width * $sill_height;
					} else {//tiling under windowsill height
						$tiling_op_surf = 2 * ($wall_depth - $sill_depth) * $tiling_height + $op_width * $tiling_height;
					}
					$this->room_data[$id] ['tiling_surf'] = $this->room_data[$id] ['tiling_surf'] + $tiling_op_surf;
				} elseif ( $sk_height ){//skirting
					$this->room_data[$id] ['skirt_length'] = $this->room_data[$id] ['skirt_length'] + $wall_length - $op_width ;
					if ( $sill_height ){
						$this->room_data[$id] ['skirt_length'] = $this->room_data[$id] ['skirt_length'] + $op_width + ( $wall_depth - $sill_depth ) * 2;
					} else {
						$this->room_data[$id] ['skirt_length'] = $this->room_data[$id] ['skirt_length'] + ( $wall_depth * 2 ) ;
					}
				}
				$this->room_data[$id] ['paint_surf'] = $this->room_data[$id] ['paint_surf'] + $total_op_surf - $tiling_op_surf;
		
				//floor and framing
				$this->room_data[$id]['floor_area_op'] = $this->room_data[$id]['floor_area_op'] + $op_width * ( $wall_depth - $sill_depth );
				if ( $wall [ 'op_material' ] AND $op_width AND $op_height > $sill_height ){
					switch ( $wall ['openings'] ){
						case 1://door
							$this->room_data[$id] ['door_number'] ++;
							$this->room_data[$id] ['door_surf'] = $this->room_data[$id] ['door_surf'] + $op_width * ( $op_height - $sill_height );
							break;
		
						case 2://window
							$this->room_data[$id] ['window_number'] ++;
							$this->room_data[$id] ['window_surf'] = $this->room_data[$id] ['window_surf'] + $op_width * ( $op_height - $sill_height );
							break;
					}
				}
				break;
		}
	}
	
	/**
	 * Records 3D Ambient data and feedbacks post metas
	 */
	
	public function bimba_3d_ambient_update_general_post_meta( $id ){
		
		$args = array(
				'height'			=> $this->room_set[$id]['height']/100,
				'floor_area'		=> $this->room_data[$id]['floor_area'],
				'volume'			=> $this->room_set[$id]['height'] * $this->room_data[$id]['floor_area'],
				'floor_area_op'		=> $this->room_data[$id]['floor_area_op'],
				'paint_surf'		=> $this->room_data[$id]['paint_surf'],
				'tiling_surf'		=> $this->room_data[$id]['tiling_surf'],
				'skirt_length'		=> $this->room_data[$id]['skirt_length'],
				'door_number'		=> $this->room_data[$id]['door_number'],
				'door_surf'			=> $this->room_data[$id]['door_surf'],
				'window_number'		=> $this->room_data[$id]['window_number'],
				'window_surf'		=> $this->room_data[$id]['window_surf'],
		);
		
		//here we store room data that can be used elsewhere
		update_post_meta( $id, '_3d_ambient_data', $args );
		
		//these are feedback, if settings were changed
		$temp [0] = $this->room_set [$id];
		update_post_meta( $id, '_3d_ambient_general_settings', $temp );
		update_post_meta( $id, '_3d_ambient_group_walls', $this->walls_total [$id] );
		
	}
	
	/**
	 * Renders object
	 */
	
	public function bimba_3d_ambient_render_object( $object, $jj, $room_name, $wall_length, $height ) {
		
		$object_offset = $object ['offset'] / 100;
		if ( $object ['material'] ){
			$object_material = get_post_meta( $object ['material'], '_3d_material_render', true );
			$object_color = $object_material [0] ['color'];
			$object_render = 'src: #bimba-material-' . $object ['material'] . '; color: ' . $object_color;
		}
		switch ( $object [ 'type' ] ) {
		
			case 0://none
				break;
		
			case 1://bed
				if ( $object ['width'] ){
					$object_width = $object ['width'] / 200;
				} else {
					$object_width = 0.8/2;
				}
				if ( $object ['depth'] ){
					$object_depth = $object ['depth'] / 100;
				} else {
					$object_depth = 1.9;
				}
				if ( ! $object ['material'] ){
					$object_color = '#ff0000';
					$object_render = 'color: ' . $object_color;
				}
				
				if ( $object_offset < 0 ) {
					$object_offset = ( $wall_length ) / 2 - $object_width;
				}
				
				$return .='
										<a-entity id="' . $room_name . '-bed-' . $jj . '" position="' . ( - $wall_length / 2 + $object_offset + $object_width) . ' 0 ' . $object ['distance'] / 100 . '">';
				$return .='<a-entity geometry="primitive: box; width: 0.05; height: 0.1; depth: 0.05" position="-' . ( $object_width - 0.025 ) . ' 0.05 0.025" material="color: white;"></a-entity>';
				$return .='<a-entity geometry="primitive: box; width: 0.05; height: 0.1; depth: 0.05" position="' . ( $object_width - 0.025 ) . ' 0.05 0.025" material="color: white;"></a-entity>';
				$return .='<a-entity geometry="primitive: box; width: 0.05; height: 0.1; depth: 0.05" position="-' . ( $object_width - 0.025 ) . ' 0.05 ' . ( $object_depth - 0.025 ) . '" material="color: white;"></a-entity>';
				$return .='<a-entity geometry="primitive: box; width: 0.05; height: 0.1; depth: 0.05" position="' . ( $object_width - 0.025 ) . ' 0.05 ' . ( $object_depth - 0.025 ) . '" material="color: white;"></a-entity>';
				$return .='<a-entity geometry="primitive: box; width: ' . ( $object_width * 2 ) . '; height: 0.35; depth: ' . $object_depth . '" position="0 0.275 ' . ( $object_depth / 2 ) . '" material="' . $object_render . ';"></a-entity>';
				$return .='<a-entity geometry="primitive: box; width: ' . ( $object_width * 2 - 0.1 ) . '; height: 0.1; depth: 0.35" position="0 0.5 0.175" material="color: white;"></a-entity>';
				$return .= '<a-entity id="' . $room_name . '-bed-' . $jj . '-label" scale="2 2 2" position="0 0.275 ' . $object_depth . '" text="value: ' . $object ['text'] . '; align: center; color: ' . $this->bimba_complementary_color( $object_color ) . ';"></a-entity>';
				$return .= '
										</a-entity>';
				break;
					
			case 2://chair and table
				if ( $object ['width'] ){
					$object_width = $object ['width'] / 200;
				} else {
					$object_width = 1.5/2;
				}
				if ( $object ['depth'] ){
					$object_depth = $object ['depth'] / 100;
				} else {
					$object_depth = 0.75;
				}
				if ( ! $object ['material'] ){
					$object_color = '#8b877b';
					$object_render = 'color: ' . $object_color;
				}
				if ( $object_offset < 0 ) {
					$object_offset = ( $wall_length ) / 2 - $object_width;
				}
				
				$return .='
										<a-entity id="' . $room_name . '-table-' . $jj . '" position="' . ( - $wall_length / 2 + $object_offset + $object_width) . ' 0 ' . $object ['distance'] / 100 . '">';
				$return .='<a-entity geometry="primitive: box; width: 0.05; height: 0.7; depth: 0.05" position="-' . ($object_width - 0.025 ) . ' 0.35 0.025" material="color: white;"></a-entity>';
				$return .='<a-entity geometry="primitive: box; width: 0.05; height: 0.7; depth: 0.05" position="' . ($object_width - 0.025 ) . ' 0.35 0.025" material="color: white;"></a-entity>';
				$return .='<a-entity geometry="primitive: box; width: 0.05; height: 0.7; depth: 0.05" position="-' . ($object_width - 0.025 ) . ' 0.35 ' . ($object_depth - 0.025 ) . '" material="color: white;"></a-entity>';
				$return .='<a-entity geometry="primitive: box; width: 0.05; height: 0.7; depth: 0.05" position="' . ($object_width - 0.025 ) . ' 0.35 ' . ($object_depth - 0.025 ) . '" material="color: white;"></a-entity>';
				$return .='<a-entity geometry="primitive: box; width: ' . $object_width * 2 . '; height: 0.05; depth: ' . $object_depth . '" position="0 0.725 ' . ($object_depth / 2 ) . '" material="' . $object_render . ';"></a-entity>';
				$return .= '<a-entity id="' . $room_name . '-table-' . $jj . '-label" scale="2 2 2" position="0 0.8 ' . $object_depth / 2 . '" text="value: ' . $object ['text'] . '; align: center; color: ' . $this->bimba_complementary_color( $object_color ) . '; side: double;"></a-entity>';
				$return .= '<a-entity id="' . $room_name . '-chair-' . $jj . '" position="0 0 ' . $object_depth . '" rotation="0 0 0">';
				$return .= $this->bimba_3d_ambient_render_chair();
				$return .= '</a-entity>';
					
				$return .= '
										</a-entity>';
				break;
		
			case 3://module
				
				$object_width = $object ['width'] / 100;
				if ( ! $object_width OR $object_width > $wall_length ) {
					$object_width = $wall_length;
				}
				if ( ( $object_width + $object_offset ) > $wall_length ) {
					$object_offset = $wall_length - $object_width;
				}
				$object_module = $object ['module_width'] / 100;
				if ( ! $object_module OR $object_module > $object_width ) {
					$object_module = $object_width;
				}
				$object_width = intval( $object_width / $object_module ) * $object_module;
				
				$object_height = $object ['height'] / 100;
				if ( ! $object_height OR $object_height > $height ) {
					$object_height = $height;
				}
				$object_depth = $object ['depth'] / 100;
				if ( ! $object_depth) {
					$object_depth = 0.6;
				}
				$object_altitude = $object ['altitude'] / 100;
				if ( $object_altitude + $object_height > $height ) {
					$object_altitude = $height - $object_height;
				}
					
				if ( $object_offset < 0 ) {
					$object_offset = ( $wall_length - $object_width ) / 2;
				}
				if ( ! $object ['material'] ){
					$object_color = '#8b877b';
					$object_render = 'color: ' . $object_color;
				}
				
				$return .='
										<a-entity id="' . $room_name . '-module-' . $jj . '" position="' . ( - $wall_length / 2 + $object_offset ) . ' 0 ' . $object ['distance'] / 100 . '">';
				if ( $object_width / $object_module == 1 ) {
					$return .='<a-entity geometry="primitive: box; width: ' . ( $object_module ) . '; height: ' . $object_height . '; depth: ' . $object_depth . '"
												position="' . ( $object_module / 2 ) . ' ' . ( $object_height / 2  + $object_altitude ) . ' ' . $object_depth / 2 . '" material="' . $object_render . ';"></a-entity>';
				} else {
					for ($k = 0; $k < ( $object_width / $object_module ); $k++) {
						$return .='<a-entity geometry="primitive: box; width: ' . ( $object_module - 0.01 ) . '; height: ' . $object_height . '; depth: ' . $object_depth . '"
												position="' . ( $object_module / 2 + $object_module * $k ) . ' ' . ( $object_height / 2  + $object_altitude ) . ' ' . $object_depth / 2 . '" material="' . $object_render . ';"></a-entity>';
					}
				}
				
				$return .= '<a-entity id="' . $room_name . '-module-' . $jj . '-label" scale="2 2 2" position="' . $object_width / 2 . ' ' . ( $object_height / 2  + $object_altitude ) . ' ' . $object_depth . '" text="value: ' . $object ['text'] . '; align: center; color: ' . $this->bimba_complementary_color( $object ['color'] ) . ';"></a-entity>';
				$return .= '
										</a-entity>';
				break;
		
			case 4://sideboard (dismissed)
				$object_width = 1.8/2;
				if ( $object_offset == 0 ) {
					$object_offset = ( $wall_length ) / 2 - $object_width;
				} elseif ( $object_offset < 0 ) {
					$object_offset = 0;
				}
				$return .='
										<a-entity id="' . $room_name . '-sideboard-' . $jj . '" position="' . ( - $wall_length / 2 + $object_offset + $object_width) . ' 0 ' . $object ['distance'] / 100 . '">';
				$return .='<a-entity geometry="primitive: box; width: 1.8; height: 0.52; depth: 0.52" position="0 0.46 0.26" material="color: #8b877b;"></a-entity>';
				$return .= '<a-entity id="' . $room_name . '-sideboard-' . $jj . '-label" scale="2 2 2" position="0 0.46 0.53" text="value: Bontempi Aly Glass; align: center;"></a-entity>';
				$return .= '
										</a-entity>';
				break;
		
			case 5://cupboard (dismissed)
				$object_width = 1.1/2;
				if ( $object_offset == 0 ) {
					$object_offset = ( $wall_length ) / 2 - $object_width;
				} elseif ( $object_offset < 0 ) {
					$object_offset = 0;
				}
				$return .='
										<a-entity id="' . $room_name . '-cupboard-' . $jj . '" position="' . ( - $wall_length / 2 + $object_offset + $object_width) . ' 0 ' . $object ['distance'] / 100 . '">';
				$return .='<a-entity geometry="primitive: box; width: 1.1; height: 1.8; depth: 0.4" position="0 0.9 0.2" material="color: #ad7c44;"></a-entity>';
				$return .= '
										</a-entity>';
				break;
					
			case 6://center table with 4 chairs
				if ( $object ['width'] ){
					$object_width = $object ['width'] / 200;
				} else {
					$object_width = 0.9/2;
				}
				if ( $object ['depth'] ){
					$object_depth = $object ['depth'] / 100;
				} else {
					$object_depth = 1.4;
				}
				if ( ! $object ['material'] ){
					$object_color = '#8b877b';
					$object_render = 'color: ' . $object_color;
				}
				if ( $object_offset < 0 ) {
					$object_offset = ( $wall_length ) / 2 - $object_width;
				}
				
				$return .='
										<a-entity id="' . $room_name . '-center-table-' . $jj . '" position="' . ( - $wall_length / 2 + $object_offset + $object_width) . ' 0 ' . $object ['distance'] / 100 . '">';
				$return .='<a-entity geometry="primitive: box; width: 0.05; height: 0.7; depth: 0.02" position="-' . ($object_width - 0.025 ) . ' 0.35 0.025" rotation="0 -45 0" material="color: white;"></a-entity>';//table legs
				$return .='<a-entity geometry="primitive: box; width: 0.05; height: 0.7; depth: 0.02" position="' . ($object_width - 0.025 ) . ' 0.35 0.025" rotation="0 45 0" material="color: white;"></a-entity>';
				$return .='<a-entity geometry="primitive: box; width: 0.05; height: 0.7; depth: 0.02" position="-' . ($object_width - 0.025 ) . ' 0.35 ' . ($object_depth - 0.025 ) . '" rotation="0 45 0" material="color: white;"></a-entity>';
				$return .='<a-entity geometry="primitive: box; width: 0.05; height: 0.7; depth: 0.02" position="' . ($object_width - 0.025 ) . ' 0.35 ' . ($object_depth - 0.025 ) . '" rotation="0 -45 0" material="color: white;"></a-entity>';
				$return .='<a-entity geometry="primitive: box; width: ' . $object_width * 2 . '; height: 0.05; depth: ' . $object_depth . '" position="0 0.725 ' . ($object_depth / 2 ) . '" material="' . $object_render . ';"></a-entity>';//table plane
				$return .= '<a-entity id="' . $room_name . '-center-table-' . $jj . '-label" scale="2 2 2" position="0 0.8 ' . $object_depth / 2 . '" text="value: ' . $object ['text'] . '; align: center; color: ' . $this->bimba_complementary_color( $object_color ) . '; side: double;"></a-entity>';
				$return .= '
										<a-entity id="' . $room_name . '-chair-' . $jj . '-a" position="0 0 0" rotation="0 180 0">';
				$return .= $this->bimba_3d_ambient_render_chair();
				$return .= '
										</a-entity>';
				$return .= '
										<a-entity id="' . $room_name . '-chair-' . $jj . '-b" position="0 0 ' . $object_depth . '" rotation="0 0 0">';
				$return .= $this->bimba_3d_ambient_render_chair();
				$return .= '
										</a-entity>';
				$return .= '
										<a-entity id="' . $room_name . '-chair-' . $jj . '-c" position="-' . $object_width . ' 0 ' . $object_depth / 2 . '" rotation="0 270 0">';
				$return .= $this->bimba_3d_ambient_render_chair();
				$return .= '
										</a-entity>';
				$return .= '
										<a-entity id="' . $room_name . '-chair-' . $jj . '-d" position="' . $object_width . ' 0 ' . $object_depth / 2 . '" rotation="0 90 0">';
				$return .= $this->bimba_3d_ambient_render_chair();
				$return .= '
										</a-entity>';
				$return .= '
										</a-entity>';
				break;
					
				//case 7://architect look-at component not
				//$return .= '
				//<a-entity id="' . $room_name . '-architect-ent-' . $jj . '" position="' . $object_offset . ' 0 ' . $object ['distance'] / 100 . '" look-at="#camera-foot">
				//<a-entity id="' . $room_name . '-architect-' . $jj . '" geometry="primitive: plane; width: 0.56; height: 1.70;" position="0 0.85 0" material="src: #bimba-architect">
				//</a-entity></a-entity>';
				//break;
		
			case 7://note
				if ( $object_offset < 0 ) {
					$object_offset = ( $wall_length ) / 2 - 0.3 / 2;
				}
				$object_altitude = $object ['altitude'] / 100;
				if ( ! $object_altitude ) {
					$object_altitude = 1.6;
				}
				$return .= '
								<a-entity id="' . $room_name . '-note-ent-' . $jj . '" position="' . ( - $wall_length / 2 + $object_offset + 0.3) . ' ' . $object_altitude . ' ' . $object ['distance'] / 100 . '">
								<a-entity id="' . $room_name . '-note-' . $jj . '" scale="2 2 2" geometry="primitive: plane; width: 0.3; height: auto;" position="0 0 0.01" material="color: #e7e662; side: double;"
								text="value: ' . $object ['text'] . '; color: black; align: center; width: 0.3;">
								</a-entity></a-entity>';
				break;
				
			case 8://cylinder
				$object_width = $object ['width'] / 200;
				if ( ! $object_width) {
					$object_width = 0.5;
				}
				$object_height = $object ['height'] / 100;
				if ( ! $object_height OR $object_height > $height ) {
					$object_height = $height;
				}
				$object_depth = $object ['depth'] / 200;
				if ( ! $object_depth) {
					$object_depth = $object_width;
				}
				$object_altitude = $object ['altitude'] / 100;
				if ( $object_altitude + $object_height > $height ) {
					$object_height = $height - $object_altitude;
				}
				if ( ! $object ['material'] ){
					$object_color = '#8b877b';
					$object_render = 'color: ' . $object_color;
				}
				if ( $object_offset < 0 ) {
					$object_offset = ( $wall_length ) / 2 - $object_width;
				}
				
				$return .='
										<a-entity id="' . $room_name . '-cylinder-ent-' . $jj . '" position="' . ( - $wall_length / 2 + $object_offset + $object_width) . ' 0 ' . $object ['distance'] / 100 . '">';
				$return .='<a-entity id="' . $room_name . '-cylinder-' . $jj . '" geometry="primitive: cylinder; radius: ' . $object_width . '; height: ' . $object_height . ';" 
						position="0 ' . ( $object_height/2 + $object_altitude ) . ' ' . $object_depth . '" material="' . $object_render . ';" scale="1 1 ' . $object_depth / $object_width . '"></a-entity>';
				$return .= '
										</a-entity>';
			break;
			
			case 9://sofa
				if ( $object ['width'] ){
					$object_width = $object ['width'] / 200;
				} else {
					$object_width = 0.9/2;
				}
				if ( $object ['depth'] ){
					$object_depth = $object ['depth'] / 100;
				} else {
					$object_depth = 0.9;
				}
				if ( ! $object ['material'] ){
					$object_color = '#ff0000';
					$object_render = 'color: ' . $object_color;
				}
			
				if ( $object_offset < 0 ) {
					$object_offset = ( $wall_length ) / 2 - $object_width;
				}
			
				$return .='
										<a-entity id="' . $room_name . '-sofa-' . $jj . '" position="' . ( - $wall_length / 2 + $object_offset + $object_width) . ' 0 ' . $object ['distance'] / 100 . '">';
				$return .='<a-entity geometry="primitive: box; width: 0.05; height: 0.1; depth: 0.05" position="-' . ( $object_width - 0.025 ) . ' 0.05 0.025" material="color: white;"></a-entity>';
				$return .='<a-entity geometry="primitive: box; width: 0.05; height: 0.1; depth: 0.05" position="' . ( $object_width - 0.025 ) . ' 0.05 0.025" material="color: white;"></a-entity>';
				$return .='<a-entity geometry="primitive: box; width: 0.05; height: 0.1; depth: 0.05" position="-' . ( $object_width - 0.025 ) . ' 0.05 ' . ( $object_depth - 0.025 ) . '" material="color: white;"></a-entity>';
				$return .='<a-entity geometry="primitive: box; width: 0.05; height: 0.1; depth: 0.05" position="' . ( $object_width - 0.025 ) . ' 0.05 ' . ( $object_depth - 0.025 ) . '" material="color: white;"></a-entity>';
				$return .='<a-entity geometry="primitive: box; width: ' . ( $object_width * 2 ) . '; height: 0.3; depth: ' . $object_depth . '" position="0 0.25 ' . ( $object_depth / 2 ) . '" material="' . $object_render . ';"></a-entity>';
				$return .='<a-entity geometry="primitive: box; width: ' . ( $object_width * 2 ) . '; height: 0.5; depth: 0.3" position="0 0.65 0.15" material="' . $object_render . ';"></a-entity>';
				$return .= '<a-entity id="' . $room_name . '-sofa-' . $jj . '-label" scale="2 2 2" position="0 0.25 ' . $object_depth . '" text="value: ' . $object ['text'] . '; align: center; color: ' . $this->bimba_complementary_color( $object_color ) . ';"></a-entity>';
				$return .= '
										</a-entity>';
				break;
		
		}//end object switch
		return $return;
	}
	
	/**
	 * Renders door frame
	 */
	
	public function bimba_3d_ambient_render_door_frame($room_name, $j, $door_height, $door_y, $frame_render, $op_width ) {
		
		$return = '<a-entity id="' . $room_name . '-door-l-frame-' . $j . '" 
					geometry="primitive: box; width: 0.08; height: '.($door_height+0.08).'; depth: 0.12"
					position="-0.03 ' . ($door_y+0.04) . ' -0.05" material="' . $frame_render . '"></a-entity>
					<a-entity id="' . $room_name . '-door-r-frame-' . $j . '" 
					geometry="primitive: box; width: 0.08; height: '.($door_height+0.08).'; depth: 0.12"
					position="'. ( $op_width + 0.03 ) . ' ' . ($door_y+0.04) . ' -0.05" material="' . $frame_render . '"></a-entity>
					<a-entity id="' . $room_name . '-door-u-frame-' . $j . '" 
					geometry="primitive: box; width: 0.08 ; height: ' . ( $op_width ) . '; depth: 0.12"
					position="'. ( $op_width / 2 ) . ' ' . ( $door_height + 0.04) . ' -0.05" rotation="0 0 -90" material="' . $frame_render . '"></a-entity>';
		
		return $return;										
	}
	
	/**
	 * This function sets the room entity parameters
	 */
	
	public function bimba_start_room_entity( $id ){
		
		$room_name = $this->room_set [$id] ['name'];
		
		if ( $this->global_flag ) {//if global flag exists you have to consider global positioning
		
			$child_diff = get_post_meta( $id, '_3d_ambient_global_diff', true );
			$child_rot = ( $child_diff[0]['rot'] - $this->parent_coord [ 'parent_rot' ] ) * 90;
			$child_y = ( $child_diff[0]['y'] - $this->parent_coord [ 'parent_y' ] ) / 100;
		
			switch ( $this->parent_coord [ 'parent_rot' ] ){//transform coordinates depend on parent rotation
				case 0:
					$child_x = ( $child_diff[0]['x'] - $this->parent_coord [ 'parent_x' ] ) / 100;
					$child_z = ( $child_diff[0]['z'] - $this->parent_coord [ 'parent_z' ] ) / 100;
					break;
						
				case 1:
					$child_x = - ( $child_diff[0]['z'] - $this->parent_coord [ 'parent_z' ] ) / 100;
					$child_z = ( $child_diff[0]['x'] - $this->parent_coord [ 'parent_x' ] ) / 100;
					break;
		
				case 2:
					$child_x = - ( $child_diff[0]['x'] - $this->parent_coord [ 'parent_x' ] ) / 100;
					$child_z = - ( $child_diff[0]['z'] - $this->parent_coord [ 'parent_z' ] ) / 100;
					break;
						
				case 3:
					$child_x = ( $child_diff[0]['z'] - $this->parent_coord [ 'parent_z' ] ) / 100;
					$child_z = - ( $child_diff[0]['x'] - $this->parent_coord [ 'parent_x' ] ) / 100;
					break;
			}
			$return .= '
					<a-entity id="' . $room_name . '-room" position="' . $child_x . ' ' . $child_y . ' ' . $child_z . '" rotation="0 ' . $child_rot . ' 0">';
		} else {
			$return .= '
					<a-entity id="' . $room_name . '-room">';
		}
		$return .= '
				<a-entity id="' . $room_name . '-label" scale="2 2 2" side="double" position="0 0.1 0" text="value: ' . get_the_title($id) . ';color: black; align: center; side: double;"></a-entity>';
		
		return $return;
	}
	
	public function bimba_3d_ambient_render_by_material( $material_id ) {
		$material_array = get_post_meta( $material_id, '_3d_material_render', true );
		if ( $material_array ) {
			$material_render = 'src: #bimba-material-' . $material_id . '; color:' . $material_array [0] ['color'];
		} else {
			$material_render = 'color: #ffffff';
		}
		return $material_render;
	}
	
	function bimba_3d_ambient_render_chair(){
		$return = '
				<a-entity position="0 0 -0.45">';
		$return .='<a-entity geometry="primitive: cylinder; radius: 0.015; height: 0.4;" position="-0.2 0.2 0.025" material="color: white;"></a-entity>';
		$return .='<a-entity geometry="primitive: cylinder; radius: 0.015; height: 0.4;" position="0.2 0.2 0.025" material="color: white;"></a-entity>';
		$return .='<a-entity geometry="primitive: cylinder; radius: 0.015; height: 0.75;" position="-0.2 0.375 0.475" material="color: white;"></a-entity>';
		$return .='<a-entity geometry="primitive: cylinder; radius: 0.015; height: 0.75;" position="0.2 0.375 0.475" material="color: white;"></a-entity>';
		$return .='<a-entity geometry="primitive: box; width: 0.45; height: 0.05; depth: 0.45" position="0 0.425 0.225" material="color: #8b877b;"></a-entity>';
		$return .='<a-entity geometry="primitive: box; width: 0.45; height: 0.25; depth: 0.03" position="0 0.875 0.475" material="color: #8b877b;"></a-entity>';
		$return .='<a-animation attribute="position" from="0 0 -0.45" to="0 0 0" begin="click" repeat="1" direction="alternate"></a-animation>';
		$return .='
				</a-entity>';
		return $return;
	}
	
	/**
	 * Display 3d ambient data
	 */
	
	public function bimba_3d_ambient_display_data( $id ){
		
		$display_data = array(
			array( 'string' => esc_html__('Room area is %s m2', 'bim-ba' ), 'data' => $this->room_data [$id] [ 'floor_area' ] ),
			array( 'string' => esc_html__('Room height is %s m', 'bim-ba' ), 'data' => $this->room_set [$id] [ 'height' ]/100 ),
			array( 'string' => esc_html__('Room volume is %s m3', 'bim-ba' ), 'data' => $this->room_data [$id] [ 'volume' ] ),
			array( 'string' => esc_html__('Floor area is %s m2', 'bim-ba' ), 'data' => ( $this->room_data [$id] [ 'floor_area' ] + $this->room_data [$id] [ 'floor_area_op' ] ) ),
			array( 'string' => esc_html__('Room wall surface is %s m2', 'bim-ba' ), 'data' => ( $this->room_data [$id] [ 'paint_surf' ] - $this->room_data [$id] [ 'floor_area' ] + $this->room_data [$id] [ 'tiling_surf' ] ) ),
			array( 'string' => esc_html__('Room painted surface is %s m2', 'bim-ba' ), 'data' => $this->room_data [$id] [ 'paint_surf' ] ),
			array( 'string' => esc_html__('Room tiling surface is %s m2', 'bim-ba' ), 'data' => $this->room_data [$id] [ 'tiling_surf' ] ),
			array( 'string' => esc_html__('Room skirting length is %s m', 'bim-ba' ), 'data' => $this->room_data [$id] [ 'skirt_length' ] ),
			array( 'string' => esc_html( _n('Room has %s door', 'Room has %s doors', $this->room_data [$id] [ 'door_number' ], 'bim-ba' ) ), 'data' => $this->room_data [$id] [ 'door_number' ] ),
			array( 'string' => esc_html__('Door surface is %s m2', 'bim-ba' ), 'data' => $this->room_data [$id] [ 'door_surf' ] ),
			array( 'string' => esc_html( _n('Room has %s window', 'Room has %s windows', $this->room_data [$id] [ 'window_number' ], 'bim-ba' ) ), 'data' => $this->room_data [$id] [ 'window_number' ] ),
			array( 'string' => esc_html__('Window surface is %s m2', 'bim-ba' ), 'data' => $this->room_data [$id] [ 'window_surf' ] ),
		);
		
		$return .= '<h5>' . sprintf( esc_html__('Data for room "%s"', 'bim-ba' ), get_the_title($id) ) . '</h5>';
		//warnings
		if ( ! $this->room_set [$id] [ 'height' ] ) {
			$return .= '<p>' . esc_html__('Room height is null, so it was not rendered.', 'bim-ba' ) . '</p>';
		} else {
			if ( $this->wall_warnings[ $id ] ) {
				foreach ( $this->wall_warnings[ $id ] as $wall_warning ) {
					$return .= '<p>' . sprintf( esc_html__('Wall %s has no length, so it was not rendered.', 'bim-ba' ), $wall_warning ) . '</p>';
				}
			}
			
			if ( $this->room_warnings [ $id ]['x'] OR $this->room_warnings [ $id ]['z'] ) {
				$return .= '<p>' . sprintf( esc_html__('There is a gap in the room polygon, x=%s cm, z=%s cm.', 'bim-ba' ), round( $this->room_warnings [ $id ]['x'] ), round( $this->room_warnings [ $id ]['z'] ) ) . '</p>';
			}
			
			if ( $this->room_data [$id] [ 'floor_area' ] + $this->room_data [$id] [ 'floor_area_op' ] < 9 ) {
				$return .= '<p>' . esc_html__('WARNING! Room surface is less than 9 m2', 'bim-ba' ) . '</p>';
			}
			if ( $this->room_data [$id] [ 'window_surf' ] AND ( $this->room_data [$id] [ 'floor_area' ] + $this->room_data [$id] [ 'floor_area_op' ] ) / $this->room_data [$id] [ 'window_surf' ] > 8 ) {
				$return .= '<p>' . esc_html__('WARNING! Window surface is less than 1/8th of floor area', 'bim-ba' ) . '</p>';
			}
			
			$return .= '<ul>';
			foreach ($display_data as $display) {
				if ( $display [ 'data' ] ) {
					$return .= '<li>' . sprintf( $display [ 'string' ], round( $display [ 'data' ], 2 ) ) . '</li>';
				}
			}
			$return .= '</ul>';
			
		}
		
		return $return;
	}
	
	public function bimba_3d_ambient_display_total_data(){
		
		$total_data = array(
				'height'		=> '',
				'floor_area'		=> '',
				'volume'			=> '',
				'floor_area_op'		=> '',
				'paint_surf'		=> '',
				'tiling_surf'		=> '',
				'skirt_length'		=> '',
				'door_number'		=> '',
				'door_surf'			=> '',
				'window_number'		=> '',
				'window_surf'		=> '',
		);
		
		foreach ($this->room_data as $data_array) {
			$total_data = $this->bimba_sum_arrays($total_data, $data_array);
		}
		
		$display_data = array(
				array( 'string' => esc_html__('Total area is %s m2', 'bim-ba' ), 'data' => $total_data [ 'floor_area' ] ),
				array( 'string' => esc_html__('Total volume is %s m3', 'bim-ba' ), 'data' => $total_data [ 'volume' ] ),
				array( 'string' => esc_html__('Total floor area is %s m2', 'bim-ba' ), 'data' => ( $total_data [ 'floor_area' ] + $total_data [ 'floor_area_op' ] ) ),
				array( 'string' => esc_html__('Total wall surface is %s m2', 'bim-ba' ), 'data' => ( $total_data [ 'paint_surf' ] - $total_data [ 'floor_area' ] + $total_data [ 'tiling_surf' ] ) ),
				array( 'string' => esc_html__('Total painted surface is %s m2', 'bim-ba' ), 'data' => $total_data [ 'paint_surf' ] ),
				array( 'string' => esc_html__('Total tiling surface is %s m2', 'bim-ba' ), 'data' => $total_data [ 'tiling_surf' ] ),
				array( 'string' => esc_html__('Total skirting length is %s m', 'bim-ba' ), 'data' => $total_data [ 'skirt_length' ] ),
				array( 'string' => esc_html( _n('In total there is %s door', 'In total there are %s doors', $total_data [ 'door_number' ], 'bim-ba' ) ), 'data' => $total_data [ 'door_number' ] ),
				array( 'string' => esc_html__('Total door surface is %s m2', 'bim-ba' ), 'data' => $total_data [ 'door_surf' ] ),
				array( 'string' => esc_html( _n('In total there is %s window', 'In total there are %s windows', $total_data [ 'window_number' ], 'bim-ba' ) ), 'data' => $total_data [ 'window_number' ] ),
				array( 'string' => esc_html__('Total window surface is %s m2', 'bim-ba' ), 'data' => $total_data [ 'window_surf' ] ),
		);
		$return .= '<h5>' . esc_html__('Total Data', 'bim-ba' ) . '</h5><ul>';
		
		foreach ($display_data as $display) {
			if ( $display [ 'data' ] ) {
				$return .= '<li>' . sprintf( $display [ 'string' ], round( $display [ 'data' ], 2 ) ) . '</li>';
			}
		}
		$return .= '</ul>';
		
		return $return;
	}
	
	public function bimba_plane_entity_render( $type, $room_name, $i, $width, $height, $px, $py, $pz, $rx, $ry, $rz, $render ){
		$return = '
				<a-entity id="' . $room_name . '-' . $type . '-' . $i . '" 
					geometry="primitive: plane; width: ' . $width . '; height: ' . $height . '" 
					position="' . $px . ' ' . $py . ' ' . $pz . '" 
					rotation="' . $rx . ' ' . $ry . ' ' . $rz . '" 
					material="' . $render . '; repeat: ' . $width . ' ' . $height . '">
				</a-entity>';
		return $return;
	}
	
	public function bimba_plane_entity_render_1_1( $type, $room_name, $i, $width, $height, $px, $py, $pz, $rx, $ry, $rz, $render ){
		$return = '
				<a-entity id="' . $room_name . '-' . $type . '-' . $i . '"
					geometry="primitive: plane; width: ' . $width . '; height: ' . $height . '"
					position="' . $px . ' ' . $py . ' ' . $pz . '"
					rotation="' . $rx . ' ' . $ry . ' ' . $rz . '"
					material="' . $render . '; repeat: 1 1 ">
				</a-entity>';
		return $return;
	}
	
	public function bimba_sum_arrays($array1, $array2) {//thanks to user1997620 on stackoverflow
		$array = array();
		foreach($array1 as $index => $value) {
			$array[$index] = isset($array2[$index]) ? $array2[$index] + $value : $value;
		}
		return $array;
	}
	
	public function bimba_complementary_color( $color ){
		// $hexcode is the six digit hex colour code we want to convert thanks to Jonas John
	
	    $color = str_replace('#', '', $color);
	    if (strlen($color) != 6){ return '#000000'; }
	    $rgb = '';
	    for ($x=0;$x<3;$x++){
	    	$c = hexdec(substr($color,(2*$x),2));
	    	$rgb .= ($c > 127) ? '00' : 'ff';//here I emphasize contrast
	    }
	    return '#'.$rgb;
	    
	}

}

$bimba_amb = new Bimba3dAmbient();