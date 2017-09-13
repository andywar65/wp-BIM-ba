<?php
class GestudioLavori{
	//properties
	private $gstu_options;
	public $array_cmt;
	public $inspect;//importante, serve a rendere il submit a prova di traduzione
	
	/**
	 * Start up
	 */
	public function __construct()
	{
		add_action( 'init', array( $this, 'gestudio_lav_init' ) );
		add_action( 'add_meta_boxes', array( $this, 'gestudio_lav_register_meta_box' ) );//Action hook to register the meta box
		add_action( 'save_post', array( $this, 'gestudio_lav_save_meta_box' ) );
		add_action( 'admin_menu', array( $this, 'gestudio_lav_src_submenu' ) );
		add_action( 'admin_menu', array( $this, 'gestudio_lav_tax_submenu' ) );
	}
	
	//Initialize the contabilità APP
	public function gestudio_lav_init() {
	
		//register the Lavori custom post type
		$labels = array(
			'name' => __( 'Projects', 'bim-ba' ),
			'singular_name' => __( 'Project', 'bim-ba' ),
			'add_new' => __( 'Add', 'bim-ba' ),
			'add_new_item' => __( 'Add Project', 'bim-ba' ),
			'edit_item' => __( 'Modify Project', 'bim-ba' ),
			'new_item' => __( 'New Project', 'bim-ba' ),
			'all_items' => __( '-List of Projects', 'bim-ba' ),
			'view_item' => __( 'View Project', 'bim-ba' ),
			'search_items' => __( 'Search Lavori', 'bim-ba' ),
			'not_found' =>  __( 'No Project found', 'bim-ba' ),
			'not_found_in_trash' => __( 'No Project found in Trash', 'bim-ba' ),
			'menu_name' => __( 'Projects', 'bim-ba' )
		  );
		
		  $args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true, 
			'show_in_menu' => 'gestudio_lav_src_page', 
			'query_var' => true,
			'rewrite' => true,
			'capability_type' => 'post',
			'has_archive' => true, 
			'hierarchical' => true,
			'menu_position' => null,
			'supports' => array( 'title', 'editor', 'content' ),
		  	'menu_icon'   => 'dashicons-chart-bar'
		  ); 
		  
		  register_post_type( 'gstu-lavori', $args );
		  
		  register_taxonomy('gstu-fasi' , 'gstu-lavori', array ( 'hierarchical' => true, 'label' => __('Project Phase', 'bim-ba' ),
		  'query-var' => true,'rewrite' => true));
		  
		  $this->array_cmt = Gestudio::array_lista_cmt();
	
	}
	
	/**
	 * Add settings submenu
	 */
	
	public function gestudio_lav_src_submenu()
	{
		add_submenu_page(
		'gestudio-settings-page',
		'Inspect Project',
		__('Inspect Project', 'bim-ba'),
		'manage_options',
		'gestudio_lav_src_page',
		array( $this, 'gestudio_lav_ricerca_page' )
		);
	}
	
	public function gestudio_lav_tax_submenu()
	{
		add_submenu_page(
		'gestudio-settings-page',
		'Fasi',
		__('-Project Phases', 'bim-ba'),
		'manage_options',
		'edit-tags.php?taxonomy=gstu-fasi&post_type=gstu-lavori'
		);
	}
	
	public function gestudio_lav_register_meta_box() {
	
		// create our custom meta box
		add_meta_box( 'gestudio_lav_meta', __( 'Client/Timing','bim-ba' ), array ( $this, 'gestudio_lav_meta_box'), 'gstu-lavori', 'side', 'default' );
	
	}
	
	public function gestudio_lav_meta_box( $post ) {
		
		$committente = get_post_meta( $post->ID, '_gstu_lav_meta_cmt', true );
		$tempistica = get_post_meta( $post->ID, '_gstu_lav_meta_tmp', true );
		if ($tempistica){
			$anno = $tempistica ['anno'];
			$mese = $tempistica ['mese'];
		}
		
		echo '<form>';// display meta box form
		echo '<label for="committente">'.__('Client', 'bim-ba' ).'</label>';
		echo '<br>';
		echo '<select id="committente" name="committente" >';
		echo '<option value="">' . __('-Select Client-','bim-ba') . '</option>';
		
		foreach ( $this->array_cmt as $cmt){
			echo '<option value="' . $cmt . '"' . selected($committente, $cmt, false) . '>' . $cmt . '</option>';
		}
		
		$nowyear = date('Y');
		$fiveyears = date('Y', strtotime('+5 years'));
		
		echo '</select><hr>';
		echo '<label for="anno">'.__('Scheduled Deadline', 'bim-ba' ).'</label>';
		echo '<br>';
		echo '<select id="anno" name="anno" >';
		echo '<option value="' . $nowyear . '">' . __('-Select Year-','bim-ba') . '</option>';
		for ($i = $nowyear; $i <= $fiveyears; $i++) {
			echo '<option value="' . $i . '"' . selected($anno, $i, false) . '>' . $i . '</option>';
		}
		echo '</select>';
		
		$mesi = Gestudio::array_mesi();//to do, spostare automaticamente la data di fine lavori?
		
		echo '<select id="mese" name="mese" >';
		echo '<option value="1">' . __('-Select Month-','bim-ba') . '</option>';
		for ($i = 1; $i <= 12; $i++) {
			echo '<option value="' . $i . '"' . selected($mese, $i, false) . '>' . $mesi [$i-1] . '</option>';
		}
		echo '</select>';
		//nonce field for security
		wp_nonce_field( 'gstu-lav-meta-box', 'gstu-lav-nonce' );
		echo '</form>';
	
	}
	
	public function gestudio_lav_save_meta_box( $post_id ) {
	
	
		if ( ! isset( $_POST['gstu-lav-nonce'] ) ) {
			return $post_id;
		}
	
		if ( ! wp_verify_nonce( $_POST['gstu-lav-nonce'], 'gstu-lav-meta-box' ) ) {
			return $post_id;
		}
	
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}
	
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}
	
		if ( ! isset( $_POST['committente'] ) ) {
			return $post_id;
		}
		
		if ( ! is_numeric( $_POST['anno'] ) OR ! is_numeric( $_POST['mese'] )) {
			return $post_id;
		}
	
		$committente = sanitize_text_field( $_POST['committente'] );
		$tempistica = array (
			'anno' => $_POST['anno'],
			'mese' => $_POST['mese']
		);
			
		update_post_meta( $post_id, '_gstu_lav_meta_cmt', $committente );
		update_post_meta( $post_id, '_gstu_lav_meta_tmp', $tempistica );
		
	}
	
	public function gestudio_lav_ricerca_page(){
		
		$this->gstu_options = get_option( 'gestudio_option_name' );
		$this->inspect = __('Inspect', 'bim-ba');
		
		echo '<h2>' . __('Inspect Project','bim-ba') . '</h2>';
		
		$lavoro = $this->gestudio_lav_controllo();
		
		echo '<hr><form action="" method="post" target="_self">';//form
		wp_nonce_field('gstu-lav-new-query-submit', 'gstu-lav-nonce-field');
		echo '<fieldset><table>';
		
		Gestudio::select_lavori();
		
		Gestudio::select_lista_a_tendina('gstu-lavori');
		
		echo '</table><p></p>';
		
		Gestudio::inspect_button($this->inspect, 'gstu-lav-query');
		
		echo '</fieldset></form><hr>';
		
		if ($lavoro){
			$page = get_page_by_title( $lavoro, 'OBJECT' , 'gstu-lavori' );
			$committente = get_post_meta( $page->ID, '_gstu_lav_meta_cmt', true );
			$tempistica = get_post_meta( $page->ID, '_gstu_lav_meta_tmp', true );
			
			$annomese = Gestudio::tempistica_anno_mese( $tempistica );
			
			echo '<table class="wp-list-table widefat fixed striped posts"><tr><th>'
					. __('Project' , 'bim-ba' ) . '</th><th>'
				 	. __('Client' , 'bim-ba' ) . '</th><th>'
				 	. __('Scheduled Deadline' , 'bim-ba' ) . '</th></tr><tr><td>'
				 	. edit_post_link( __('Modify Project','bim-ba'), '', '', $page->ID ) 
				 	. $lavoro . '</td><td>' . $committente . '</td><td>' . $annomese .	'</td></tr></table>';
			
			$super_tot = 0;
			
			$args_opr = Gestudio::args_opr_no_cmt();
			
			$loop_opr = new WP_Query( $args_opr );
			
			if ( $loop_opr->have_posts() ){
					
				while ( $loop_opr->have_posts() ) : $loop_opr->the_post();
				$operatore = get_the_title();
				$terms = get_the_terms(  $loop_opr->post->ID , 'gstu-ruoli');
				
				$ruolo = Gestudio::cerca_termine( $terms );
				
				$flag = 0;
				$tot = 0;
				
				$args_rpp = Gestudio::args_rpp_dati_lav_opr($lavoro, $operatore);
				
				$loop_rpp = new WP_Query( $args_rpp );
				
				if ( $loop_rpp->have_posts() ){
					$flag = 1;
					
					Gestudio::head_lav_table_rpp_pn();
					
					while ( $loop_rpp->have_posts() ) : $loop_rpp->the_post();
					$gstu_rpp_meta = get_post_meta( $loop_rpp->post->ID, '_gstu_rpp_meta', true );
					
					$importo = Gestudio::calcola_importo_rpp($gstu_rpp_meta);
					
					$terms = get_the_terms(  $loop_rpp->post->ID , 'gstu-tipi');
					
					$categoria = Gestudio::cerca_termine( $terms );
					
					$importo = Gestudio::is_invoice($categoria, $importo);
					
					echo '<tr><td>' . $operatore . '</td><td>' . $ruolo . '</td><td><a href="' . get_edit_post_link() . 
			'" title="' . __('Modify Report','bim-ba') . '">' 
								. get_the_title() . '</td><td>' . $categoria . '</td><td>' . get_the_date() . '</td><td style="text-align : right">' 
								. number_format($importo, 2) . '</td></tr>';
					$tot = $tot + $importo;
					
					endwhile;
					
				}
				wp_reset_postdata();
				
				$args_pn = Gestudio::args_pn_dati_lav_opr($lavoro, $operatore);
				
				$loop_pn = new WP_Query( $args_pn );
				
				if ( $loop_pn->have_posts() ){
					if ($flag == 0){
						$flag = 1;
						
						Gestudio::head_lav_table_rpp_pn();
					}
						
					while ( $loop_pn->have_posts() ) : $loop_pn->the_post();
					$gstu_pn_meta = get_post_meta( $loop_pn->post->ID, '_gstu_pn_meta', true );
					$immissione = $gstu_pn_meta ['immissione'];
					$importo = $gstu_pn_meta ['importo'];
					
					if ($operatore == $this->gstu_options ['operatore']){
						$importo = Gestudio::calcola_importo_pn( $immissione, $importo );
					}
					
					$terms = get_the_terms(  $loop_pn->post->ID , 'categoria-contabile');
					
					$categoria = Gestudio::cerca_termine( $terms );
					
					echo '<tr><td>' . $operatore . '</td><td>' . $ruolo . '</td><td><a href="' . get_edit_post_link() . 
			'" title="' . __('Modify Blotter Entry','bim-ba') . '">' 
								. get_the_title() . '</td><td>' . $categoria . '</td><td>' . get_the_date() . '</td><td style="text-align : right">' 
								. number_format(-$importo, 2) . '</td></tr>';
					$tot = $tot - $importo;
					endwhile;
						
				}
				wp_reset_postdata();
				
				if ($flag == 1){
					echo '<tr><td></td><td></td><td></td><td></td>' . Gestudio::footer_table_bilancio($tot) . '</tr>';
				}
				echo '</table>';
				if ($ruolo <> __('Contractor','bim-ba') ){
					if ($operatore == $this->gstu_options ['operatore']){
						$super_tot = $super_tot + $tot;
					} else {
						$super_tot = $super_tot - $tot;
					}
				}
				
				endwhile;
				
			}
			wp_reset_postdata();
			
			echo '<br><table class="wp-list-table widefat fixed striped posts"><tr><td></td><td></td><td></td><td></td>' 
					. Gestudio::footer_table_previsione($this->gstu_options ['operatore'], $super_tot) . '</tr></table>';
		}
	}
	
	public function gestudio_lav_controllo(){
	
		Gestudio::admin_login_msg();
	
		if ( !isset($_POST['gstu-lav-query']) OR $_POST['gstu-lav-query'] <> $this->inspect ) {//hai fatto richiesta?
			echo __('To inspect a Project, select from dropdown list and press','bim-ba') . ' "' . $this->inspect . '".';
			return;
		}
	
		if (!wp_verify_nonce( $_POST['gstu-lav-nonce-field'], 'gstu-lav-new-query-submit' )){
			Gestudio::security_issue_msg();
			return;
		}
	
		$lavoro = $_POST['Lavoro'];
	
		return $lavoro;
	}

}

if( is_admin() )
	$gstu_lav = new GestudioLavori();



