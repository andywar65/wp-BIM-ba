<?php
class Bimba3dPlan{
	
	public $plan_h_axis = array();
	public $plan_v_axis = array();
	public $plan_walls = array();
	public $plan_bound = array();
	public $temp_wall_data = array();
	public $id;
	
	/**
	 * Start up
	 */
	public function __construct()
	{
		add_action( 'init', array( $this, 'bimba_init_3d_plan_cpt' ) );
		add_action( 'add_meta_boxes', array( $this, 'bimba_3d_plan_register_meta_box' ) );//Action hook to register the meta box
	}
	
	/**
	 * Register 3D Plan Custom Post Type
	 */
	public function bimba_init_3d_plan_cpt(){
		$labels = array(
				'name' => esc_html__( '3D Plans', 'bim-ba' ),
				'singular_name' => esc_html__( '3D Plan', 'bim-ba' ),
				'add_new' => esc_html__( 'Add New', 'bim-ba' ),
				'add_new_item' => esc_html__( 'Add 3D Plan', 'bim-ba' ),
				'edit_item' => esc_html__( 'Modify 3D Plan', 'bim-ba' ),
				'new_item' => esc_html__( 'New 3D Plan', 'bim-ba' ),
				'all_items' => esc_html__( 'All 3D Plans', 'bim-ba' ),
				'view_item' => esc_html__( 'Wiev 3D Plan', 'bim-ba' ),
				'search_items' => esc_html__( 'Search 3D Plan', 'bim-ba' ),
				'not_found' =>  esc_html__( 'No 3D Plan', 'bim-ba' ),
				'not_found_in_trash' => esc_html__( 'No 3D Plan in Trash', 'bim-ba' ),
				//'menu_name' => __( 'BIM-ba', 'bim-ba' )
		);
		
		$args = array(
				'hierarchical' => false,
				'labels' => $labels,
				'public' => true,
				'publicly_queryable' => true,
				'show_ui' => true,
				'show_in_menu' => 'edit.php?post_type=bimba_3d_ambient',
				'query_var' => true,
				'rewrite' => array('slug' => _x('3d-plan', 'URL slug', 'bim-ba')),
				'capability_type' => 'page',
				'has_archive' => true,
				'menu_position' => 100,
				'supports' => array( 'title', 'editor', 'content', 'thumbnail', 'page-attributes', 'author' ),
				'taxonomies' => array( 'plan_category' , ),
				//'menu_icon'   => 'dashicons-layout'
		);
		
		register_post_type( 'bimba_3d_plan', $args );
		
	}
	
	/**
	 * Register 3D Plan Metabox
	 */
	
	public function bimba_3d_plan_register_meta_box() {
	
		add_meta_box( 'bimba-3d-plan-metabox', __( 'Plan','bim-ba' ), array ( $this, 'bimba_3d_plan_render_meta_box'), 'bimba_3d_plan', 'advanced', 'high' );
	
	}
	
	/**
	 * Metabox where 3D Plan is rendered 
	 */
	
	public function bimba_3d_plan_render_meta_box($post) {
		
		$this->id = $post->ID;
		$this->plan_h_axis = get_post_meta( $this->id, '_3d_plan_h_axis', true );
		$this->plan_v_axis = get_post_meta( $this->id, '_3d_plan_v_axis', true );
		$this->plan_walls = get_post_meta( $this->id, '_3d_plan_walls', true );
		
		$this->bimba_3d_plan_sort_h_axis();
		$this->bimba_3d_plan_sort_v_axis();
		
		update_post_meta( $this->id, '_3d_plan_h_axis', $this->plan_h_axis );//feedback sorted axis
		update_post_meta( $this->id, '_3d_plan_v_axis', $this->plan_v_axis );
		
		$this->plan_bound = array('zmin' => 10000, 'xmin' => 10000, 'zmax' => -10000, 'xmax' => -10000 );//bounding box for dummies
		$this->bimba_3d_plan_find_bounding_box();
		
		echo '<a-scene style="width: 100%; height: 500px" embedded>';
		echo '
				<a-entity id="grid-holder" rotation="90 0 0" position="0 1.6 -' . ( $this->plan_bound['zmax'] - $this->plan_bound['zmin'] ) . '">';
		echo '
				<a-entity id="grid-base" rotation="-90 0 0" position="0 -0.01 0" 
				geometry="primitive: plane; width:' . ( $this->plan_bound['xmax'] - $this->plan_bound['xmin'] + 0.1 ) . '; height:' . ( $this->plan_bound['zmax'] - $this->plan_bound['zmin'] + 0.1 ) . ';"
				material="color: #999999;"></a-entity>';
		 
		$this->bimba_3d_plan_render_grid();
		
		$this->bimba_3d_plan_render_grid_text();
		
		$this->bimba_3d_plan_render_walls();
		
		echo '</a-entity>';//end grid-holder 
		
		echo '<a-sky color="white"></a-sky>
					<a-entity id="bimba-camera-ent">
        				<a-camera id="bimba-camera" wasd-controls="fly: true">
							<a-light type="point"></a-light>    
        					<a-entity position="0 -1.6 0" id="camera-foot"></a-entity>
							<a-cursor color="#2E3A87"></a-cursor>
        				</a-camera>
      				</a-entity>
			</a-scene>';
	}
	
	/**
	 * Sorts horizontal axis by distance from origin
	 */
	
	public function bimba_3d_plan_sort_h_axis(){
		$temp = array();
		foreach ($this->plan_h_axis as $h_axis) {
			$temp[ $h_axis['name'] ]= $h_axis['distance'];
		}
		asort( $temp, SORT_NUMERIC );
	
		$i = 0;
		foreach ( $temp as $key=>$value ) {
			$this->plan_h_axis[$i]['name']=$key;
			$this->plan_h_axis[$i]['distance']=$value;
			$i ++;
		}
	}
	
	/**
	 * Sorts vertical axis by distance from origin
	 */
	
	public function bimba_3d_plan_sort_v_axis(){
		$temp = array();
		foreach ($this->plan_v_axis as $v_axis) {
			$temp[ $v_axis['name'] ]= $v_axis['distance'];
		}
		asort( $temp, SORT_NUMERIC );
		
		$i = 0;
		foreach ( $temp as $key=>$value ) {
			$this->plan_v_axis[$i]['name']=$key;
			$this->plan_v_axis[$i]['distance']=$value;
			$i ++;
		}
	}
	
	/**
	 * Finds bounding box of the grid
	 */
	
	public function bimba_3d_plan_find_bounding_box(){
		
		foreach ( $this->plan_h_axis as $h_axis ){
			if ( $h_axis['distance']/100 < $this->plan_bound['zmin'] ) {
				$this->plan_bound['zmin'] = $h_axis['distance']/100;
			}
			if ( $h_axis['distance']/100 > $this->plan_bound['zmax'] ) {
				$this->plan_bound['zmax'] = $h_axis['distance']/100;
			}
		}
		foreach ( $this->plan_v_axis as $v_axis ){
			if ( $v_axis['distance']/100 < $this->plan_bound['xmin'] ) {
				$this->plan_bound['xmin'] = $v_axis['distance']/100;
			}
			if ( $v_axis['distance']/100 > $this->plan_bound['xmax'] ) {
				$this->plan_bound['xmax'] = $v_axis['distance']/100;
			}
		}
	}
	
	/**
	 * Renders the grid
	 */
	
	public function bimba_3d_plan_render_grid(){
		$i = 0;
		foreach ( $this->plan_h_axis as $h_axis ){
			$h_dist = $h_axis['distance']/100 - $this->plan_h_axis[0]['distance']/100;
			if ($h_dist) {
				$k = 0;
				foreach ( $this->plan_v_axis as $v_axis ){
					$v_dist = $v_axis['distance']/100 - $this->plan_v_axis[0]['distance']/100;
					if ($v_dist) {
						echo '
								<a-entity id="grid-'.$i.'-'.$k.'" geometry="primitive: plane; width:' . ( $v_dist - $v_dist_prev - 0.1 ) . '; height:' . ( $h_dist - $h_dist_prev - 0.1 ) . ';" rotation="-90 0 0" ';
						echo 'position="' . ( - ( $this->plan_bound['xmax'] - $this->plan_bound['xmin'] ) / 2 + $v_dist_prev + ( $v_dist - $v_dist_prev ) / 2 ) . ' 0 ' . -( ( $this->plan_bound['zmax'] - $this->plan_bound['zmin'] ) / 2 - $h_dist_prev - ( $h_dist - $h_dist_prev ) / 2 ) . '" ';
						if ( ( $i + $k ) % 2 == 0) {//odd - even numbers for sorting colors
							echo 'material="color: #ffffff">';
						} else {
							echo 'material="color: #dddddd">';//used to be #dddddd
						}
						echo '</a-entity>';
					}
					$v_dist_prev = $v_dist;
					$k ++;
				}
			}
			$h_dist_prev = $h_dist;
			$i ++;
		}
	}
	
	/**
	 * Renders the grid text
	 */
	
	public function bimba_3d_plan_render_grid_text(){
		$i = 1;
		foreach ( $this->plan_h_axis as $h_axis ){
			if ( $h_axis['distance'] ) {
				$axis_name = '- - -' . $h_axis['name'] . ' (' . $h_axis['distance'] . ')';
			} else {
				$axis_name = '- - -' . $h_axis['name'] . ' (000)';
			}
			echo '
					<a-entity id="h-axis-' . $i . '-text" text="value:' . $axis_name . '; anchor: left; color: black;" scale="10 10 10" rotation="-90 0 0" ';
			echo 'position="' . ( ( $this->plan_bound['xmax'] - $this->plan_bound['xmin'] ) / 2) . ' 0 ' . -( ( $this->plan_bound['zmax'] - $this->plan_bound['zmin'] ) / 2 - $h_axis['distance']/100 + $this->plan_h_axis[0]['distance']/100 ) . '">';
			echo '</a-entity>';
			$i ++;
		}
		$i = 1;
		foreach ( $this->plan_v_axis as $v_axis ){
			if ( $v_axis['distance'] ) {
				$axis_name = '- - -' . $v_axis['name'] . ' (' . $v_axis['distance'] . ')';
			} else {
				$axis_name = '- - -' . $v_axis['name'] . ' (000)';
			}
			echo '
					<a-entity id="v-axis-' . $i . '-text" text="value: ' . $axis_name . '; anchor: left; color: black;" scale="10 10 10" ';
			echo 'rotation="-90 0 90" ';
			echo 'position="' . ( - ($this->plan_bound['xmax'] - $this->plan_bound['xmin'] ) / 2 + $v_axis['distance']/100 - $this->plan_v_axis[0]['distance']/100 ) . ' 0 ' . -( ( $this->plan_bound['zmax'] - $this->plan_bound['zmin'] ) / 2 ) . '">';
			echo '</a-entity>';
			$i ++;
		}
	}
	
	/**
	 * Renders the wall elements
	 */
	
	public function bimba_3d_plan_render_walls() {
		if ( $this->plan_walls ) {
			foreach ( $this->plan_walls as $wall ){
				$depth = $this->bimba_3d_plan_get_depth_by_type( $wall['type'] );
				$this->temp_wall_data = $this->bimba_3d_plan_get_temp_wall_data( $wall['on'], $wall['from'], $wall['to'] );
				$width = abs( $this->temp_wall_data['from'] - $this->temp_wall_data['to'] ) / 100;
				$rot = $this->bimba_3d_plan_get_rotation_by_axis( $wall['on'] );
				$x = $this->bimba_3d_plan_get_x_by_axis( $wall['on'] ) - $this->plan_v_axis[0]['distance']/100;
				$z = $this->bimba_3d_plan_get_z_by_axis( $wall['on'] ) - $this->plan_h_axis[0]['distance']/100;
				echo '
						<a-entity id="' . $wall['name'] . '-holder" position="' . $x . ' 0 ' . $z . '" rotation="0 ' . $rot . ' 0">';
				echo '
						<a-entity id="' . $wall['name'] . '" geometry="primitive: box; width: ' . $width . ';height: 0.03; depth: ' . $depth . ';" position="0 0.015 ' . $depth * ( $wall['position'] -0.5 ) . '" material="color: red;">';
				echo '
						<a-entity id="' . $wall['name'] . '-label" text="value: ' . $wall['name'] . '; align: center; color: black;" scale="10 10 10" position="0 0.03 '. ( $depth / 2 + 0.25 ) .'" rotation="-90 0 0">';
				echo '</a-entity>';//end label
				echo '</a-entity>';//end wall
				echo '</a-entity>';//end wall holder
			}
		}
		
	}
	
	public function bimba_3d_plan_get_depth_by_type( $id ) {
		$layers = get_post_meta( $id, '_3d_element_layers', true );
		if ( $layers ) {
			foreach ($layers as $layer) {
				$depth = $depth + $layer['thickness']/1000 ;
			}
		} else {
			$depth = 0.1;
		}
		return $depth;
	}
	
	public function bimba_3d_plan_get_temp_wall_data( $on, $from, $to ) {
		$data = array();
		if ( strpos( $on, 'H' ) === 0 ) {
			foreach ( $this->plan_h_axis as $h_axis ){
				if ( $h_axis['name'] == $on ) {
					$data['on'] = $h_axis['distance'];
				} 
			}
			foreach ( $this->plan_v_axis as $v_axis ){
				if ( $v_axis['name'] == $from ) {
					$data['from'] = $v_axis['distance'];
				} elseif ( $v_axis['name'] == $to ) {
					$data['to'] = $v_axis['distance'];
				}
			}
		} elseif ( strpos( $on, 'V' ) === 0 ) {
			foreach ( $this->plan_v_axis as $v_axis ){
				if ( $v_axis['name'] == $on ) {
					$data['on'] = $v_axis['distance'];
				}
			}
			foreach ( $this->plan_h_axis as $h_axis ){
				if ( $h_axis['name'] == $from ) {
					$data['from'] = $h_axis['distance'];
				} elseif ( $h_axis['name'] == $to ) {
					$data['to'] = $h_axis['distance'];
				}
			}
		}
		return $data;
	}
	
	public function bimba_3d_plan_get_rotation_by_axis( $on ) {
		if ( strpos( $on, 'V' ) === 0 ) {
			$rot = 90;
		} else {
			$rot = 0;
		}
		return $rot;
	}
	
	public function bimba_3d_plan_get_x_by_axis( $on ) {
		if ( strpos( $on, 'H' ) === 0 ) {
			if ( $this->temp_wall_data['from'] > $this->temp_wall_data['to'] ) {
				$offset = $this->temp_wall_data['to'] /100;
			} else {
				$offset = $this->temp_wall_data['from'] /100;
			}
			$x = abs( $this->temp_wall_data['from'] - $this->temp_wall_data['to'] ) / 200 + $offset - ( $this->plan_bound['xmax'] - $this->plan_bound['xmin'] ) / 2;
		} elseif ( strpos( $on, 'V' ) === 0 ) {
			$x = $this->temp_wall_data['on'] / 100 - ( $this->plan_bound['xmax'] - $this->plan_bound['xmin'] ) / 2;
		}
		return $x;
	}
	
	public function bimba_3d_plan_get_z_by_axis( $on ) {
		if ( strpos( $on, 'V' ) === 0 ) {
			if ( $this->temp_wall_data['from'] > $this->temp_wall_data['to'] ) {
				$offset = $this->temp_wall_data['to'] /100;
			} else {
				$offset = $this->temp_wall_data['from'] /100;
			}
			$z = abs( $this->temp_wall_data['from'] - $this->temp_wall_data['to'] ) / 200 + $offset - ( $this->plan_bound['zmax'] - $this->plan_bound['zmin'] ) / 2;
		} elseif ( strpos( $on, 'H' ) === 0 ) {
			$z = $this->temp_wall_data['on'] / 100 - ( $this->plan_bound['zmax'] - $this->plan_bound['zmin'] ) / 2;
		}
		return $z;
	}
	
}

if ( is_admin() ) {
	$bimba_plan = new Bimba3dPlan();
}