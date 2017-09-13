<?php
class GestudioRapporti{
	//properties
	public $operatore;
	public $lavoro;
	public $array_opr_no_cmt;
	public $array_lav;
	public $array_ruoli_opr;
	
	/**
	 * Start up
	 */
	public function __construct()
	{
		add_action( 'init', array( $this, 'gestudio_rpp_init' ) );
		add_action( 'add_meta_boxes', array( $this, 'gestudio_rpp_register_meta_box' ) );//Action hook to register the meta box
		add_action( 'save_post', array( $this, 'gestudio_rpp_save_meta_box' ) );
		add_action( 'admin_menu', array( $this, 'gestudio_rpp_src_submenu' ) );
		add_action( 'admin_menu', array( $this, 'gestudio_rpp_tax_submenu' ) );
		add_action( 'admin_notices', array( $this, 'gestudio_rpp_incongruenza' ) );
	}
	
	//Initialize the contabilità APP
	public function gestudio_rpp_init() {
	
		//register the Rapporti custom post type
		$labels = array(
			'name' => __( 'Reports', 'bim-ba' ),
			'singular_name' => __( 'Report', 'bim-ba' ),
			'add_new' => __( 'Add', 'bim-ba' ),
			'add_new_item' => __( 'Add Report', 'bim-ba' ),
			'edit_item' => __( 'Modify Report', 'bim-ba' ),
			'new_item' => __( 'Nuovo Report', 'bim-ba' ),
			'all_items' => __( '-List of Reports', 'bim-ba' ),
			'view_item' => __( 'View Report', 'bim-ba' ),
			'search_items' => __( 'Search Reports', 'bim-ba' ),
			'not_found' =>  __( 'No Report found', 'bim-ba' ),
			'not_found_in_trash' => __( 'No Report found in Trash', 'bim-ba' ),
			'menu_name' => __( 'Reports', 'bim-ba' )
		  );
		
		  $args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true, 
			'show_in_menu' => 'gestudio_rpp_src_page', 
			'query_var' => true,
			'rewrite' => true,
			'capability_type' => 'post',
			'has_archive' => true, 
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => array( 'title', 'editor', 'content' ),
		  	'menu_icon'   => 'dashicons-chart-bar'
		  ); 
		  
		  register_post_type( 'gstu-rapporti', $args );
		  
		  register_taxonomy('gstu-tipi' , 'gstu-rapporti', array ( 'hierarchical' => true, 'label' => __('Type of Report', 'bim-ba' ),
		  'query-var' => true,'rewrite' => true));
		  
		  $this->array_opr_no_cmt = Gestudio::array_lista_opr_no_cmt();
		  $this->array_lav = Gestudio::array_lista_lav();
		  $this->array_ruoli_opr = Gestudio::array_lista_ruoli_opr();
	
	}
	
	/**
	 * Add settings submenu
	 */
	
	public function gestudio_rpp_src_submenu()
	{
		add_submenu_page(
		'gestudio-settings-page',
		'Inspect Reports',
		__('Inspect Reports', 'bim-ba'),
		'manage_options',
		'gestudio_rpp_src_page',
		array( $this, 'gestudio_rpp_ricerca_page' )
		);
	}
	
	public function gestudio_rpp_tax_submenu()
	{
		add_submenu_page(
		'gestudio-settings-page',
		'Tipi',
		__('-Types of Report', 'bim-ba'),
		'manage_options',
		'edit-tags.php?taxonomy=gstu-tipi&post_type=gstu-rapporti'
		);
	}
	
	public function gestudio_rpp_register_meta_box() {
		
		
		// create our custom meta box
		add_meta_box( 'gestudio_rpp_meta', __( 'Report Data','bim-ba' ), array ( $this, 'gestudio_rpp_meta_box'), 'gstu-rapporti', 'side', 'default' );
	
	}
	
	public function gestudio_rpp_meta_box( $post ) {
		$this->operatore = get_post_meta( $post->ID, '_gstu_rpp_meta_opr', true );
		$this->lavoro = get_post_meta( $post->ID, '_gstu_rpp_meta_lav', true );
		$gstu_rpp_meta = get_post_meta( $post->ID, '_gstu_rpp_meta', true );
		if ($gstu_rpp_meta){
			$imponibile = 	$gstu_rpp_meta ['imponibile'];
			$iva = 			$gstu_rpp_meta ['iva'];
			$ritenuta = 	$gstu_rpp_meta ['ritenuta'];
			$contributi = 	$gstu_rpp_meta ['contributi'];
		} else {
			$iva = 			22;
			$ritenuta = 	20;
			$contributi = 	5*.4+4*.6;
		}
		
		echo '<form>';// display meta box form
		echo '<label for="operatore">'.__('Operator', 'bim-ba' ).'</label>';
		echo '<br>';
		echo '<select id="operatore" name="operatore" >';
		echo '<option value="">' . __('-Select Operator-','bim-ba') . '</option>';
		
			foreach ( $this->array_opr_no_cmt as $opr){
				echo '<option value="' . $opr . '"' . selected($this->operatore, $opr, false) . '>' . $opr . '</option>';
			}
		
		echo '</select>';
		echo '<br>';
		
		echo '<label for="lavoro">'.__('Project', 'bim-ba' ).'</label>';
		echo '<br>';
		echo '<select id="lavoro" name="lavoro" >';
		echo '<option value="">' . __('-Select Project-','bim-ba') . '</option>';
		
		foreach ( $this->array_lav as $lav ){
			echo '<option value="' . $lav . '"' . selected($this->lavoro, $lav, false) . '>' . $lav . '</option>';
		}
		
		echo '</select><hr>';
		
		echo '<label for="imponibile">'.__('Taxable', 'bim-ba' ).'</label>';
		echo '<br>';
		echo '<input id="imponibile" name="imponibile" type="number" step="0.01" value="'.$imponibile.'"/>';
		echo '<br>';
		echo '<label for="contributi">'.__('Superannuations (%)', 'bim-ba' ).'</label>';
		echo '<br>';
		echo '<input id="contributi" name="contributi" type="number" step="0.01" value="'.$contributi.'"/>';
		echo '<br>';
		echo '<label for="iva">'.__('VAT (%)', 'bim-ba' ).'</label>';
		echo '<br>';
		echo '<input id="iva" name="iva" type="number" value="'.$iva.'"/>';
		echo '<br>';
		echo '<label for="ritenuta">'.__("Withholding (%)", 'bim-ba' ).'</label>';
		echo '<br>';
		echo '<input id="ritenuta" name="ritenuta" type="number" value="'.$ritenuta.'"/>';
		//nonce field for security
		wp_nonce_field( 'gstu-rpp-meta-box', 'gstu-rpp-nonce' );
		echo '</form>';
		
	}
	
	public function gestudio_rpp_save_meta_box( $post_id ) {
		if ( ! isset( $_POST['gstu-rpp-nonce'] ) ) {
			return $post_id;
		}
		
		if ( ! wp_verify_nonce( $_POST['gstu-rpp-nonce'], 'gstu-rpp-meta-box' ) ) {
			return $post_id;
		}
		
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}
		
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}
		
		if ( ! isset( $_POST['operatore'] ) OR ! isset( $_POST['lavoro'] ) ) {
			return $post_id;
		}
		
		if ( ! is_numeric( $_POST['imponibile'] ) OR ! is_numeric( $_POST['contributi'] ) 
			OR ! is_numeric( $_POST['iva'] ) OR ! is_numeric( $_POST['ritenuta'] )) {
			return $post_id;
		}
		
		$this->operatore = sanitize_text_field( $_POST['operatore'] );
		$this->lavoro = sanitize_text_field( $_POST['lavoro'] );
		$errore = sanitize_text_field($this->controllo_ruolo_operatore());
		$gstu_rpp_meta = array (
				'imponibile' 	=> $_POST['imponibile'],
				'contributi' 	=> $_POST['contributi'],
				'iva' 			=> $_POST['iva'],
				'ritenuta' 		=> $_POST['ritenuta'],
				'errore' 		=> $errore
		);
			
		update_post_meta( $post_id, '_gstu_rpp_meta_opr', $this->operatore );
		update_post_meta( $post_id, '_gstu_rpp_meta_lav', $this->lavoro );
		update_post_meta( $post_id, '_gstu_rpp_meta', $gstu_rpp_meta );
		
	}
	
	public function gestudio_rpp_ricerca_page(){
		
		echo '<h2>' . __('Inspect Reports.', 'bim-ba') . '</h2>';
		
		$args_rpp = array(
				'post_type' => array( 'gstu-rapporti' ),
				'posts_per_page' => -1,
		);
		
		$loop_rpp = new WP_Query( $args_rpp );
		
		if ( $loop_rpp->have_posts() ){
			
			$showbalance = 1;//ci sono lavori, stampa il bilancio
			
			echo '<br><table class="wp-list-table widefat fixed striped posts">';
			echo '<tr><th>' . __('Report' , 'bim-ba' ) . '</th><th>'
					. __('Type' , 'bim-ba' ) . '</th><th>'
					. __('Date' , 'bim-ba' ) . '</th><th>'
					. __('Operator' , 'bim-ba' ) . '</th><th>'
					. __('Project' , 'bim-ba' ) . '</th><th style="text-align : right">'
					. __('Amount' , 'bim-ba' ) . '</th></tr>';
				
			while ( $loop_rpp->have_posts() ) : $loop_rpp->the_post();
			$this->operatore = get_post_meta( $loop_rpp->post->ID, '_gstu_rpp_meta_opr', true );
			$this->lavoro = get_post_meta( $loop_rpp->post->ID, '_gstu_rpp_meta_lav', true );
			$gstu_rpp_meta = get_post_meta( $loop_rpp->post->ID, '_gstu_rpp_meta', true );
				
			$importo = Gestudio::calcola_importo_rpp($gstu_rpp_meta);
				
			$terms = get_the_terms(  $loop_rpp->post->ID , 'gstu-tipi');
			
			$categoria = Gestudio::cerca_termine( $terms );
				
			$importo = Gestudio::is_invoice($categoria, $importo);
				
			echo '<tr><td><a href="' . get_edit_post_link() .
			'" title="' . __('Modify Report','bim-ba') . '">'
					. get_the_title() . '</td><td>' 
					. $categoria . '</td><td>' 
					. get_the_date() . '</td><td>'
					. $this->operatore . '</td><td>'
					. $this->lavoro . '</td><td style="text-align : right">'
					. number_format($importo, 2) . '</td></tr>';
			$tot = $tot + $importo;
				
			endwhile;
				
		} else {
			$showbalance = 0;
			echo __('At the moment, no Reports to Inspect.', 'bim-ba');
		}
		
		wp_reset_postdata();
		
		if ($showbalance){
			echo '<tr><td></td><td></td><td></td><td></td>'
					 . Gestudio::footer_table_bilancio($tot) . '</tr>';
			echo '</table>';
		}
		
	}
	
	public function controllo_ruolo_operatore(){
		
		$terms = get_the_terms(  $post_id , 'gstu-tipi');
		
		$tipo = Gestudio::cerca_termine( $terms );
		
		$errore = '';
		
		if ($tipo AND $tipo == __('Invoice', 'bim-ba')){
			
			$ruolo = $this->array_ruoli_opr [ $this->operatore ];
				
			if ($ruolo == __('Studio', 'bim-ba') OR $ruolo == __('Collaboratore', 'bim-ba')){
				$errore = __('Warning: if Invoice belongs to the countancy of the Studio, use Blotter Entry, not a Report.', 'bim-ba');
			}
		}
		return $errore;
	}
	
	public function gestudio_rpp_incongruenza() {
	
		global $post;
	
		$gstu_rpp_meta = get_post_meta( $post->ID, '_gstu_rpp_meta', true );//bisogna vedere se prende tutto l'array
	
		if ($gstu_rpp_meta){
	
			$err = $gstu_rpp_meta['errore'];
				
			if ($err <> ''){
				?>
			    <div class="error">
			        <p><?php echo $err; ?></p>
			    </div>
			    <?php
		    }
		}
	}
	
}

if( is_admin() )
	$gstu_rpp = new GestudioRapporti();



