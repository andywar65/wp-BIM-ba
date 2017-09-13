<?php
class GestudioOperatori{
	//properties
	public $inspect;//importante, serve a rendere il submit a prova di traduzione
	
	/**
	 * Start up
	 */
	public function __construct()
	{
		add_action( 'init', array( $this, 'gestudio_opr_init' ) );
		add_action( 'admin_menu', array( $this, 'gestudio_opr_src_submenu' ) );
		add_action( 'admin_menu', array( $this, 'gestudio_opr_tax_submenu' ) );
	}
	
	//Initialize the contabilità APP
	public function gestudio_opr_init() {
	
		//register the operatori custom post type
		$labels = array(
			'name' => __( 'Operators', 'bim-ba' ),
			'singular_name' => __( 'Operator', 'bim-ba' ),
			'add_new' => __( 'Add', 'bim-ba' ),
			'add_new_item' => __( 'Add Operator', 'bim-ba' ),
			'edit_item' => __( 'Modify Operator', 'bim-ba' ),
			'new_item' => __( 'New Operator', 'bim-ba' ),
			'all_items' => __( '-List of Operators', 'bim-ba' ),
			'view_item' => __( 'View Operator', 'bim-ba' ),
			'search_items' => __( 'Search Operators', 'bim-ba' ),
			'not_found' =>  __( 'No Operator found', 'bim-ba' ),
			'not_found_in_trash' => __( 'No Operator found in Trash', 'bim-ba' ),
			'menu_name' => __( 'Operators', 'bim-ba' )
		  );
		
		  $args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true, 
			'show_in_menu' => 'gestudio_opr_src_page', 
			'query_var' => true,
			'rewrite' => true,
			'capability_type' => 'post',
			'has_archive' => true, 
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => array( 'title', 'editor', 'content' ),
		  	'menu_icon'   => 'dashicons-chart-bar'
		  ); 
		  
		  register_post_type( 'gstu-operatori', $args );
		  
		  register_taxonomy('gstu-ruoli' , 'gstu-operatori', array ( 'hierarchical' => true, 'label' => __("Role of Operator", 'bim-ba' ),
		  'query-var' => true,'rewrite' => true));
	
	}
	
	/**
	 * Add settings submenu
	 */
	
	public function gestudio_opr_src_submenu()
	{
		add_submenu_page(
		'gestudio-settings-page',
		'Inspect Operators',
		__('Inspect Operator', 'bim-ba'),
		'manage_options',
		'gestudio_opr_src_page',
		array( $this, 'gestudio_opr_ricerca_page' )
		);
	}
	
	public function gestudio_opr_tax_submenu()
	{
		add_submenu_page(
		'gestudio-settings-page',
		'Ruoli',
		__("-Roles of Operator", 'bim-ba'),
		'manage_options',
		'edit-tags.php?taxonomy=gstu-ruoli&post_type=gstu-operatori'
		);
	}
	
	public function gestudio_opr_ricerca_page(){
		
		$this->gstu_options = get_option( 'gestudio_option_name' );
		$this->inspect = __('Inspect', 'bim-ba');
		
		echo '<h2>' . __('Inspect Operator','bim-ba') . '</h2>';
	
		$operatore = $this->gestudio_opr_controlli();//controllo sul submit
		
		echo '<hr><form action="" method="post" target="_self">';//form
		wp_nonce_field('gstu-opr-new-query-submit', 'gstu-opr-nonce-field');
		echo '<fieldset><table>';
				
		Gestudio::select_operatori();
		
		Gestudio::select_lista_a_tendina('gstu-operatori');
		
		echo '</table><p></p>';
		
		Gestudio::inspect_button($this->inspect, 'gstu-opr-query');

		echo '</fieldset></form><hr>';
		
		if ($operatore){
			
			$page = get_page_by_title( $operatore, 'OBJECT' , 'gstu-operatori' );
			$terms = get_the_terms(  $page->ID , 'gstu-ruoli');
	
			$ruolo = Gestudio::cerca_termine($terms);
			
			echo '<table class="wp-list-table widefat fixed striped posts"><tr><th>' 
					. __('Operator' , 'bim-ba' ) . '</th><th>'
					. __('Role' , 'bim-ba' ) . '</th></tr><tr><td>'
					. edit_post_link( __('Modify Operator','bim-ba'), '', '', $page->ID ) 
					. $operatore . '</td><td>' . $ruolo . '</td></tr></table>';
			
			if ($ruolo == __('Client' , 'bim-ba' ) ){
				
				$args_lav1 = Gestudio::args_lav_dato_cmt($operatore);

				$loop_lav1 = new WP_Query( $args_lav1 );
				if ( $loop_lav1->have_posts() ){
					
					echo '<br><table class="wp-list-table widefat fixed striped posts"><tr><th>'
							. __('Project' , 'bim-ba' ) . '</th><th>'
							. __('Scheduled Deadline' , 'bim-ba' ) . '</th></tr>';
						
					while ( $loop_lav1->have_posts() ) : $loop_lav1->the_post();
					$lavoro1 = get_the_title();
					
					$tempistica1 = get_post_meta( $loop_lav1->post->ID, '_gstu_lav_meta_tmp', true );
					
					$annomese = Gestudio::tempistica_anno_mese( $tempistica1 );
					
					echo '<tr><td>' . $lavoro1 . '</td><td>' . $annomese . '</td></tr>';
					
					endwhile;
					
					echo '</table>';
				}
				
				wp_reset_postdata();
					
			}	
			
			$super_tot = 0;
				
			$args_lav = Gestudio::args_lav();
				
			$loop_lav = new WP_Query( $args_lav );
				
			if ( $loop_lav->have_posts() ){
					
				while ( $loop_lav->have_posts() ) : $loop_lav->the_post();
				$lavoro = get_the_title();
		
				$flag = 0;
				$tot = 0;
		
				$args_rpp = Gestudio::args_rpp_dati_lav_opr($lavoro, $operatore);
		
				$loop_rpp = new WP_Query( $args_rpp );
		
				if ( $loop_rpp->have_posts() ){
					$flag = 1;
					
					Gestudio::head_opr_table_rpp_pn();
						
					while ( $loop_rpp->have_posts() ) : $loop_rpp->the_post();
					$gstu_rpp_meta = get_post_meta( $loop_rpp->post->ID, '_gstu_rpp_meta', true );
						
					$importo = Gestudio::calcola_importo_rpp($gstu_rpp_meta);
						
					$terms = get_the_terms(  $loop_rpp->post->ID , 'gstu-tipi');
					
					$categoria = Gestudio::cerca_termine($terms);
						
					Gestudio::is_invoice($categoria, $importo);
						
					echo '<tr><td>' . $lavoro . '</td><td><a href="' 
							. get_edit_post_link() . '" title="' 
							. __('Modify Report','bim-ba') . '">'
							. get_the_title() . '</td><td>' 
							. $categoria . '</td><td>' 
							. get_the_date() . '</td><td style="text-align : right">'
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
						
						Gestudio::head_opr_table_rpp_pn();
					}
		
					while ( $loop_pn->have_posts() ) : $loop_pn->the_post();
					$gstu_pn_meta = get_post_meta( $loop_pn->post->ID, '_gstu_pn_meta', true );
					$immissione = $gstu_pn_meta ['immissione'];
					$importo = $gstu_pn_meta ['importo'];
					
					if ($operatore == $this->gstu_options ['operatore']){
						$importo = Gestudio::calcola_importo_pn( $immissione, $importo );
					}
						
					$terms = get_the_terms(  $loop_pn->post->ID , 'categoria-contabile');
					
					$categoria = Gestudio::cerca_termine($terms);
					
					echo '<tr><td>' . $lavoro . '</td><td><a href="' . get_edit_post_link() . '" title="' 
							. __('Modify Blotter Entry','bim-ba') . '">'
							. get_the_title() . '</td><td>' 
							. $categoria . '</td><td>' 
							. get_the_date() . '</td><td style="text-align : right">'
							. number_format(-$importo, 2) . '</td></tr>';
					
					$tot = $tot - $importo;
					endwhile;
		
				}
				wp_reset_postdata();
		
				if ($flag == 1){
					echo '<tr><td></td><td></td><td></td>' . Gestudio::footer_table_bilancio($tot) . '</tr>';
				}
				echo '</table>';
				$super_tot = $super_tot + $tot;
				endwhile;
		
			}
			wp_reset_postdata();
			
			echo '<br><table class="wp-list-table widefat fixed striped posts"><tr><td></td><td></td><td></td>' 
					. Gestudio::footer_table_previsione($operatore, $super_tot) . '</tr></table>';
		}
	}
	
	public function gestudio_opr_controlli(){
	
		Gestudio::admin_login_msg();
	
		if ( !isset($_POST['gstu-opr-query']) OR $_POST['gstu-opr-query'] <> $this->inspect ){//hai fatto richiesta?
			echo __('To inspect an Operator, select from dropdown list and press','bim-ba') . ' "' . $this->inspect . '".';
			return;
		}
	
		if (!wp_verify_nonce( $_POST['gstu-opr-nonce-field'], 'gstu-opr-new-query-submit' )){
				Gestudio::security_issue_msg();
			return;
		}
	
		$operatore = $_POST['Operatore'];
	
		return $operatore;
	}

}

if( is_admin() )
	$gstu_opr = new GestudioOperatori();



