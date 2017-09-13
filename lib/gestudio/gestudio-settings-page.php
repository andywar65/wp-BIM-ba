<?php
class GestudioSettingsPage
{
	/**
	 * Holds the values to be used in the fields callbacks
	 */
	private $gstu_options;
	
	public $data_prima;
	public $data_ultima;
	public $term_ids;
	public $spese_tot;
	public $lavoro;
	public $operatore;
    
    /**
     * Start up
     */
    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'gestudio_main_menu' ), 9 );
        add_action( 'admin_menu', array( $this, 'gestudio_main_submenu' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }
    
    /**
     * Add main menu
     */
    
    public function gestudio_main_menu()
    {
    	add_menu_page(
    	'Gestudio Settings Page',
    	__('Manage Studio', 'bim-ba'),
    	'manage_options',
    	'gestudio-settings-page',
    	array( $this, 'gestudio_settings_page' ),
    	'dashicons-building'
    			);
    }
    
    /**
     * Add settings submenu
     */
    
    public function gestudio_main_submenu()
    {
    	add_submenu_page(
    	'gestudio-settings-page',
    	'Impostazioni',
    	__('-Studio Settings', 'bim-ba'),
    	'manage_options',
    	'gestudio-impostazioni-page',
    	array( $this, 'gestudio_impostazioni_page' )
    	);
    }

    /**
     * Options page callback
     */
    public function gestudio_settings_page()
    {
    	echo '<h2>' . __('Gestione Studio.', 'bim-ba') . '</h2><br>';
    	
    	$this->gstu_options = get_option( 'gestudio_option_name' );
    	
    	if (! $this->gstu_options['operatore'] ){
    		echo __("Warning: you didn't select the Operator the accountancy is related to.", 'bim-ba');
    		echo '<p><a href="' . admin_url( 'admin.php?page=gestudio-impostazioni-page' ) . '">' . __('Studio Settings', 'bim-ba') . '</a></p>';
    	} else {
    		$this->data_ultima = Gestudio::data_ultima_prima_nota();
    		 
    		$this->data_prima = date("Y-m-d", strtotime( $data_ultima . ' -1 year') );
    		 
    		$this->term_ids = $this->tax_spese_fisse();
    		 
    		$this->spese_tot = $this->spese_fisse_mensili();
    		 
    		echo __("Fixed Monthly Expenses in the last Year: ", 'bim-ba') . number_format( $this->spese_tot / 12 , 2) . '<p></p>';
    		 
    		$this->gestudio_lav_generali();
    	}
    	
    }
    
    public function gestudio_impostazioni_page()
    {
    	$this->gstu_options = get_option( 'gestudio_option_name' );
    	
    	echo '<form method="post" action="options.php">';
    	settings_fields( 'gestudio_option_group' );
    	do_settings_sections( 'gestudio-settings-page' );
    	submit_button(__('Save Settings', 'bim-ba'));
    	echo '</form><hr>';
    	
    	echo '<ul>';
    	echo '<li><a href="' . admin_url( 'edit.php?post_type=gstu-lavori' ) . '">' . __('List of all Projects', 'bim-ba') . '</a></li>';
    	echo '<li><a href="' . admin_url( 'edit.php?post_type=gstu-operatori' ) . '">' . __('List of all Operators', 'bim-ba') . '</a></li>';
    	echo '<li><a href="' . admin_url( 'edit.php?post_type=gstu-rapporti' ) . '">' . __('List of all Reports', 'bim-ba') . '</a></li>';
    	echo '<li><a href="' . admin_url( 'edit.php?post_type=prime-note' ) . '">' . __('List of all Blotter Entries', 'bim-ba') . '</a></li>';
    	echo '</ul>';
    }
    
    public function page_init()
    {
    	register_setting(
    	'gestudio_option_group', // Option group
    	'gestudio_option_name', // Option name
    	array( $this, 'gestudio_sanitize' ) // Sanitize
    	);
    
    	add_settings_section(
    	'setting_section_id', // ID
    	__('Studio Settings', 'bim-ba'), // Title
    	array( $this, 'print_section_info' ), // Callback
    	'gestudio-settings-page' // Page
    	);
    	 
    	add_settings_field(
    	'operatore', // ID
    	__('Operator', 'bim-ba'), // Title
    	array( $this, 'operatore_callback' ), // Callback
    	'gestudio-settings-page', // Page
    	'setting_section_id' // Section
    	);
    	
    	add_settings_field(
    	'spese-fisse', // ID
    	__('Fixed Expenses', 'bim-ba'), // Title
    	array( $this, 'spese_fisse_callback' ), // Callback
    	'gestudio-settings-page', // Page
    	'setting_section_id' // Section
    	);
    
    }
    
    public function gestudio_sanitize( $input )
    {
    	$this->gstu_options = get_option( 'gestudio_option_name' );//can't get rid of this second query...
    	 
    	$new_input = array();
    
    	$new_input['operatore'] = ( isset( $input['operatore'] ) ) ? 
    	sanitize_text_field( $input['operatore'] ) : sanitize_text_field( $this->gstu_options['operatore'] );
    	
    	$terms = Gestudio::categorie_figlie_uscite();
    	
    	$i = 0;
    	foreach ($terms as $term){
    		$i++;
    		$new_input[ $i ] = ( isset( $input[ $i ] ) ) ?
    		absint( $input[ $i ] ) : 0;
    	}
    
    	return $new_input;
    }
    
    public function print_section_info()
    {
    	print __("Select an Operator with Role 'Studio' the accountancy is related to (create it if necessary), then check the le Counting Categories that build up Fixed Expenses:", 'bim-ba');
    }
    
    public function operatore_callback()
    {
    	//lista tutti gli operatori
    	$args = Gestudio::args_opr_dato_stu();
    		
    	$loop = new WP_Query( $args );
    		
    	if ( $loop->have_posts() ){
    		echo '<select id="operatore" name="gestudio_option_name[operatore]">';
    		echo '<option value="">' . __('-Select Operator-', 'bim-ba') . '</option>';
    		while ( $loop->have_posts() ) : $loop->the_post();
    			$title = $loop->post->post_title;
    			
    			echo '<option value="' . $title . '"'
    					. selected( $this->gstu_options[ 'operatore' ] , $title, false ) . '>' . $title . '</option>';
    			
    		endwhile;
    		echo '</select>';
    	}
    		
    	wp_reset_postdata();
    	
    }
    
    public function spese_fisse_callback()
    {
    	$terms = Gestudio::categorie_figlie_uscite();
    	
    	$i = 0;
    	foreach ($terms as $term){
    		$i++;
    		echo '<input type="checkbox" id="spese-fisse" name="gestudio_option_name[' . $i . ']"
    				value="' . $term->term_id . '"' . checked( $this->gstu_options[ $i ] , $term->term_id, false ) . '>' . $term->name . '<br>';
    	}
    	
    	 
    }
    
    public function tax_spese_fisse(){
    	//$this->gstu_options = get_option( 'gestudio_option_name' );
    	$term_ids = array();
    	foreach ($this->gstu_options as $key=>$val){
    		if (is_numeric($key) AND $val){
    			$term_ids [] = $val;
    		}
    	}
    	return $term_ids ;
    }
    
    public function spese_fisse_mensili(){
    	
    	$args = Gestudio::args_pn_dati_cat_txt_date( $this->term_ids, '', $this->data_prima, $this->data_ultima );
    	
    	//$args = array(
    			//'post_type' => array( 'prime-note' ),
    			//'posts_per_page' => -1,
    			//'tax_query' => array(
    					//array(
    							//'taxonomy' => 'categoria-contabile',
    							//'field' => 'id',
    							//'terms' => $this->term_ids
    					//)
    			//),
    			//'date_query' => array(
    					//array(
    							//'after' => $this->data_prima,
    							//'before' => $this->data_ultima
    					//)
    			//)
    	//);
    	 
    	$loop = new WP_Query ($args);
    	if ($loop->have_posts()){
    		while ($loop->have_posts()): $loop->the_post();
    		$gstu_pn_meta = get_post_meta( $loop->post->ID, '_gstu_pn_meta', true );
    		if (!isset ( $gstu_pn_meta['importo'] ) ){
    			$importo = '';
    		} else {
    			$importo = $gstu_pn_meta['importo'];
    		}
    		 
    		$tot = $tot + $importo;
    		 
    		endwhile;
    	}
    	wp_reset_postdata();
    	return $tot;
    }
    
    public function gestudio_lav_generali(){
    
    	$this->gstu_options = get_option( 'gestudio_option_name' );
    	
    	$super_super_tot = 0 ;
    	$super_finale = '1900-01-01' ;
    
    	$loop_lav = new WP_Query( 'post_type=gstu-lavori&posts_per_page=-1' );
    	
    	if ( $loop_lav->have_posts() ){
    		
    		$showbalance = 1;//ci sono lavori, stampa il bilancio
    		
    		echo '<table class="wp-list-table widefat fixed striped posts"><tr><th>'
    				. __('Project' , 'bim-ba' ) . '</th><th>'
    				. __('Client' , 'bim-ba' ) . '</th><th>'
    				. __('Scheduled Deadline' , 'bim-ba' ) . '</th><th style="text-align : right">'
    				. __('Forecast Incomes ' , 'bim-ba' ) . $this->gstu_options ['operatore'] . '</th></tr>';
    		
    		while ( $loop_lav->have_posts() ) : $loop_lav->the_post();
    			
	    		$this->lavoro = $loop_lav->post->post_title;
	    		$committente = get_post_meta( $loop_lav->post->ID, '_gstu_lav_meta_cmt', true );
	    		$tempistica = get_post_meta( $loop_lav->post->ID, '_gstu_lav_meta_tmp', true );
	    		
	    		$annomese = Gestudio::tempistica_anno_mese( $tempistica );
	    		
	    		$fine_lavori = $annomese . '-01';
	    		
	    		if ( strtotime ($fine_lavori) > strtotime ($super_finale) ){
	    			$super_finale = $fine_lavori;
	    		}
	    		
	    		$super_tot = 0 ;
	    			
	    		$args_opr = Gestudio::args_opr_no_cmt();
	    			
	    		$loop_opr = new WP_Query( $args_opr );
	    			
	    		if ( $loop_opr->have_posts() ){
	    				
	    			while ( $loop_opr->have_posts() ) : $loop_opr->the_post();
	    			$this->operatore = $loop_opr->post->post_title;
	    			$terms = get_the_terms(  $loop_opr->post->ID , 'gstu-ruoli');
	    			
	    			$ruolo = Gestudio::cerca_termine( $terms );
	    
	    			$tot = 0;
	    
	    			$args_rpp = Gestudio::args_rpp_dati_lav_opr( $this->lavoro, $this->operatore );
	    
	    			$loop_rpp = new WP_Query( $args_rpp );
	    			
	    			if ( $loop_rpp->have_posts() ){	
	    					
	    				while ( $loop_rpp->have_posts() ) : $loop_rpp->the_post();
	    				$gstu_rpp_meta = get_post_meta( $loop_rpp->post->ID, '_gstu_rpp_meta', true );
	    				
	    				$importo = Gestudio::calcola_importo_rpp($gstu_rpp_meta);
	    			
	    				$terms = get_the_terms(  $loop_rpp->post->ID , 'gstu-tipi');
	    				
	    				$categoria = Gestudio::cerca_termine( $terms );
	    				
	    				$importo = Gestudio::is_invoice($categoria, $importo);
	    				
	    				$tot = $tot + $importo;
	    				
	    				endwhile;
	    				
	    			}
	    			wp_reset_postdata();
	    
	    			$args_pn = Gestudio::args_pn_dati_lav_opr( $this->lavoro, $this->operatore );
	    
	    			$loop_pn = new WP_Query( $args_pn );
	    
	    			if ( $loop_pn->have_posts() ){
	    
	    				while ( $loop_pn->have_posts() ) : $loop_pn->the_post();
	    				$gstu_pn_meta = get_post_meta( $loop_pn->post->ID, '_gstu_pn_meta', true );
	    				$immissione = $gstu_pn_meta ['immissione'];
	    				$importo = $gstu_pn_meta ['importo'];
	    					
	    				if ($this->operatore == $this->gstu_options ['operatore']){
	    					$importo = Gestudio::calcola_importo_pn( $immissione, $importo );
	    				}
	    					
	    				$terms = get_the_terms(  $loop_pn->post->ID , 'categoria-contabile');
	    				
	    				$categoria = Gestudio::cerca_termine( $terms );
	    				
	    				$tot = $tot - $importo;
	    				endwhile;
	    
	    			}
	    			wp_reset_postdata();
	    			
	    			if ($ruolo <> __('Contractor','bim-ba') ){
	    				if ($this->operatore == $this->gstu_options ['operatore']){
	    					$super_tot = $super_tot + $tot;
	    				} else {
	    					$super_tot = $super_tot - $tot;
	    				}
	    			}
	    
	    			endwhile;
	    
	    		}
	    		wp_reset_postdata();
	    		
	    		echo '<tr><td>' . $this->lavoro . '</td><td>' 
	    						. $committente . '</td><td>' 
	    						. $fine_lavori . '</td><td style="text-align : right">'
	    						. number_format($super_tot, 2) . '</td></tr>';
	    		
	    		$super_super_tot = $super_super_tot + $super_tot;
	    	
    		endwhile;
    	} else {
    		$showbalance = 0;
    		echo __('At the moment, no Balance may be forecast (not enough data).', 'bim-ba');
    	}
    	
    	wp_reset_postdata();
    	
    	if ($showbalance){
    		
    		$date1 = strtotime( $this->data_ultima );
    		$date2 = strtotime( $super_finale );
    		$months = 0;
    		 
    		while (($date1 = strtotime('+1 MONTH', $date1)) <= $date2)
    			$months++;
    		 
    		echo '<tr><td></td><td></td><th style="text-align : right">'
    				. __('Total Forecast Incomes ' , 'bim-ba' ) . $this->gstu_options ['operatore'] . '</th><td style="text-align : right">'
    						. number_format($super_super_tot, 2) . '</td></tr>';
    		echo '<tr><td></td><th>'
    				. __('Deadline of all Projects ' , 'bim-ba' ) . '</th><td>'
    						. $super_finale . '</td><td></td></tr>';
    		echo '<tr><td></td><td></td><th style="text-align : right">'
    				. __('Fixed Expenses at that date ' , 'bim-ba' ) . '</th><td style="text-align : right">'
    						. number_format($months * $this->spese_tot / 12, 2) . '</td></tr>';
    		echo '<tr><td></td><td></td>' . Gestudio::footer_table_bilancio($super_super_tot - $months * $this->spese_tot /12) . '</tr>';
    		
    		echo '</table>';
    	}
 
    }
    
}

if( is_admin() )
    $gstu_sp = new GestudioSettingsPage();
