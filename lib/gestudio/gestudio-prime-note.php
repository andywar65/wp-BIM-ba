<?php
class GestudioPrimeNote{
	//properties
	public $array_lav;
	public $array_opr;
	public $inspect;//importante, serve a rendere il submit a prova di traduzione
	
	/**
	 * Start up
	 */
	public function __construct()
	{
		add_action( 'init', array( $this, 'gestudio_pn_init' ) );
		add_action( 'add_meta_boxes', array( $this, 'gestudio_pn_register_meta_box' ) );//Action hook to register the meta box
		add_action( 'save_post', array( $this, 'gestudio_pn_save_meta_box' ) );// Action hook to save the meta box data when the post is saved
		add_action( 'admin_notices', array( $this, 'gestudio_pn_incongruenza' ) );
		add_action( 'admin_menu', array( $this, 'gestudio_pn_src_submenu' ) );
		add_action( 'admin_menu', array( $this, 'gestudio_pn_tax_submenu' ) );
	}
	
	//Initialize the contabilità APP
	public function gestudio_pn_init() {
	
		//register the products custom post type
		$labels = array(
			'name' => __( 'Blotter Entries', 'bim-ba' ),
			'singular_name' => __( 'Blotter Entry', 'bim-ba' ),
			'add_new' => __( 'Add', 'bim-ba' ),
			'add_new_item' => __( 'Add Blotter Entry', 'bim-ba' ),
			'edit_item' => __( 'Modify Blotter Entry', 'bim-ba' ),
			'new_item' => __( 'New Blotter Entry', 'bim-ba' ),
			'all_items' => __( '-List Blotter Entries', 'bim-ba' ),
			'view_item' => __( 'View Blotter Entry', 'bim-ba' ),
			'search_items' => __( 'Search Blotter Entries', 'bim-ba' ),
			'not_found' =>  __( 'No Blotter Entry found', 'bim-ba' ),
			'not_found_in_trash' => __( 'No Blotter Entry found in Trash', 'bim-ba' ),
			'menu_name' => __( 'Blotter Entries', 'bim-ba' )
		  );
		
		  $args = array(
			'labels' => $labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true, 
			'show_in_menu' => 'gestudio_pn_src_page', 
			'query_var' => true,
			'rewrite' => true,
			'capability_type' => 'post',
			'has_archive' => true, 
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => array( 'title', 'editor', 'content' ),
		  	'menu_icon'   => 'dashicons-chart-bar'
		  ); 
		  
		  register_post_type( 'prime-note', $args );
		  
		  register_taxonomy('categoria-contabile' , 'prime-note', array ( 'hierarchical' => true, 'label' => __('Counting Category', 'bim-ba' ),
		  'query-var' => true,'rewrite' => true));
		  
		  $this->array_opr = Gestudio::array_lista_opr();
		  $this->array_lav = Gestudio::array_lista_lav();
	
	}
	
	/**
	 * Add settings submenu
	 */
	
	public function gestudio_pn_src_submenu()
	{
		add_submenu_page(
		'gestudio-settings-page',
		'Search Blotter Entries',
		__('Search Blotter Entries', 'bim-ba'),
		'manage_options',
		'gestudio_pn_src_page',
		array( $this, 'gestudio_pn_ricerca_page' )
		);
	}
	
	public function gestudio_pn_tax_submenu()
	{
		add_submenu_page(
		'gestudio-settings-page',
		'Counting Categories',
		__('-Counting Categories', 'bim-ba'),
		'manage_options',
		'edit-tags.php?taxonomy=categoria-contabile&post_type=prime-note'
				//, array( $this, 'create_admin_page' )
		);
	}
	
	public function gestudio_pn_register_meta_box() {
		
		add_meta_box( 'gestudio_pn_meta', __( 'Movement','bim-ba' ), array ( $this, 'gestudio_pn_meta_box'), 'prime-note', 'side', 'default' );
		
	}
	
	//build prima nota meta box
	public function gestudio_pn_meta_box( $post ) {
	
	    // retrieve our custom meta box values
		$gstu_pn_meta = get_post_meta( $post->ID, '_gstu_pn_meta', true );
		$lavoro = get_post_meta( $post->ID, '_gstu_pn_meta_lav', true );
		$operatore = get_post_meta( $post->ID, '_gstu_pn_meta_opr', true );
		
		if ($gstu_pn_meta){
	    
		    $importo = $gstu_pn_meta['importo'];
		    $immissione = $gstu_pn_meta['immissione'];
	    
		}
		
		$array_movimenti = array(
				__('Cash Income', 'bim-ba' ),
				__('Cash Expense', 'bim-ba' ),
				__('Bank Income', 'bim-ba' ),
				__('Bank Expense', 'bim-ba' ),
				__('Clearance Bank=>Cash', 'bim-ba' ),
				__('Clearance Cash=>Bank', 'bim-ba' )
		);
		
		
	    // display meta box form
		echo '<form>';
		
		for ($i=1; $i<=6; $i++){
			echo '<input type="radio" name="immissione" value="' 
					. $i . '" '. checked($immissione, $i, false). '>' . $array_movimenti [$i-1];
			if ($i % 2 == 0) {
				echo '<hr>';
			} else {
				echo '<br>';
			}
		}
		
		echo '<label for="importo">'.__('Amount', 'bim-ba' ).'</label>';
		echo '<br>';
		echo '<input id="importo" name="importo" type="number" step="0.01" value="'.$importo.'"/>';
		echo '<hr>';
		
		echo '<label for="lavoro">'.__('Project', 'bim-ba' ).'</label>';
		echo '<br>';
		echo '<select id="lavoro" name="lavoro" >';
		echo '<option value="">' . __('-Select Project-','bim-ba') . '</option>';
		
		foreach ( $this->array_lav as $lav ){
			echo '<option value="' . $lav . '"' . selected($lavoro, $lav, false) . '>' . $lav . '</option>';
		}
		
		echo '</select><br>';
		
		echo '<label for="operatore">'.__('Operator', 'bim-ba' ).'</label>';
		echo '<br>';
		echo '<select id="operatore" name="operatore" >';
		echo '<option value="">' . __('-Select Operator-','bim-ba') . '</option>';
		
		foreach ( $this->array_opr as $opr){
			echo '<option value="' . $opr . '"' . selected($operatore, $opr, false) . '>' . $opr . '</option>';
		}
		
		echo '</select>';
		//nonce field for security
		wp_nonce_field( 'gstu-pn-meta-box', 'gstu-pn-nonce' );
		echo '</form>';
		
	}
	
	
	//save meta box data
	public function gestudio_pn_save_meta_box( $post_id ) {
		
		
		if ( ! isset( $_POST['gstu-pn-nonce'] ) ) {
			return $post_id;
		}
		
		if ( ! wp_verify_nonce( $_POST['gstu-pn-nonce'], 'gstu-pn-meta-box' ) ) {
			return $post_id;
		}
		
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}
		
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}
		
		if ( ! isset( $_POST['importo'] ) ) {
			return $post_id;
		}
		
		if ( ! is_numeric( $_POST['importo'] ) OR ! is_numeric( $_POST['immissione'] ) ) {
			return $post_id;	
		}
		
		$gstu_pn_meta = get_post_meta( $post->ID, '_gstu_pn_meta', true );//bisogna vedere se prende tutto l'array
		
		$terms = wp_get_post_terms( $post_id, 'categoria-contabile' );
		
		foreach ($terms as $term){
			if ( $term->parent ){	
				$parent = get_term_by('id', $term->parent , 'categoria-contabile');
				$term_name = $parent->name;
			} else {
				$term_name = $term->name;
			}
		}
		
		$immissione = $_POST['immissione'];
		$importo = $_POST['importo'];
		$lavoro = sanitize_text_field( $_POST['lavoro'] );
		$operatore = sanitize_text_field( $_POST['operatore'] );
		
		if (!isset($term_name)){
			$err = __('Warning: Counting Category not defined.' , 'bim-ba' );//attento: il carattere 'è' non fa funzionare update_post_meta
		} else {
			$err = $this->ciclo_immissione($term_name, $immissione);	
		}
			
		update_post_meta( $post_id, '_gstu_pn_meta', array(
		'immissione' => $immissione ,
		'importo' => $importo ,
		'errore' => sanitize_text_field( $err )
				));
		
		update_post_meta( $post_id, '_gstu_pn_meta_lav', $lavoro );
		
		update_post_meta( $post_id, '_gstu_pn_meta_opr', $operatore );
			
	}
	
	public function gestudio_pn_ricerca_page(){
		
		$this->inspect = __('Search', 'bim-ba');
		
		echo '<h2>' . __('Search Blotter Entries','bim-ba') . '</h2>';
		
		$search = $this->gestudio_pn_controllo();//controlla e passa gli argomenti della ricerca
		
		$this->gestudio_pn_query_form();
		
		if ($search){
			$args = $this->gestudio_pn_query( $search );
		}
		
		$loop = new WP_Query( $args );
		
		if ( $loop->have_posts() ):
		
		echo '<hr><table class="wp-list-table widefat fixed striped posts">';
		echo '<tr><th>'.__('Blotter Entry','bim-ba').'</th>
				<th>'.__('Date','bim-ba').'</th>
				<th>'.__('Description','bim-ba').'</th>
				<th>'.__('Counting Category','bim-ba').'</th>
				<th>'.__('Project','bim-ba').'</th>
				<th>'.__('Operator','bim-ba').'</th>
				<th style="text-align : right">'.__('Movement','bim-ba').'</th></tr>';
		
		while ( $loop->have_posts() ) : $loop->the_post();
		
		echo '<tr><td><a href="' . get_edit_post_link() . 
			'" title="' . __('Modify Blotter Entry','bim-ba') . '">' . get_the_title() . '</a></td>';
		echo '<td>' . get_the_date() . '</td>';
		echo '<td>' . get_the_content() . '</td>';
		
		echo '<td>';
		$terms = get_the_terms(  $post->ID , 'categoria-contabile');
		foreach ($terms as $term) {
			echo $term->name . '</td>';
		}
		
		$gstu_pn_meta = get_post_meta( $loop->post->ID, '_gstu_pn_meta', true );
		$gstu_pn_meta_lav = get_post_meta( $loop->post->ID, '_gstu_pn_meta_lav', true );
		$gstu_pn_meta_opr = get_post_meta( $loop->post->ID, '_gstu_pn_meta_opr', true );
		
		if (!isset ( $gstu_pn_meta_lav ) ){
			$lavoro = '';
		} else {
			$lavoro = $gstu_pn_meta_lav;
		}
		if (!isset ( $gstu_pn_meta_opr ) ){
			$operatore = '';
		} else {
			$operatore = $gstu_pn_meta_opr;
		}
		
		echo '<td>' . $lavoro . '</td>';
		echo '<td>' . $operatore . '</td>'; 
		
		if (!isset ( $gstu_pn_meta['importo'] ) ){
			$importo = '';
		} else {
			$importo = $gstu_pn_meta['importo'];
		}
		
		if (!isset ( $gstu_pn_meta['immissione'] ) ){
			echo '<td></td>';
		} else {
			switch( $gstu_pn_meta['immissione'] ){
			
				case '1':
					$temp = $importo;
					echo '<td style="text-align : right">' . number_format($temp, 2) . '</td>';
					break;
			
				case '2':
					$temp = -$importo;
					echo '<td style="text-align : right">' . number_format($temp, 2) . '</td>';
					break;
					 
				case '3':
					$temp = $importo;
					echo '<td style="text-align : right">' . number_format($temp, 2) . '</td>';
					break;
			
				case '4':
					$temp = -$importo;
					echo '<td style="text-align : right">' . number_format($temp, 2) . '</td>';
					break;
					 
				case '5':
					$temp = 0;
					echo '<td style="text-align : right">' . number_format($importo, 2) . ' <p> ' . number_format(-$importo, 2) . '</p></td>';
					break;
			
				case '6':
					$temp = 0;
					echo '<td style="text-align : right">' . number_format(-$importo, 2) . ' <p> ' . number_format($importo, 2) . '</p></td>';
					break;
			
			}
		}
		
		$tot = $tot + $temp;
		
		echo '</tr>';
		endwhile;
		
		wp_reset_postdata();
		
		echo '<tr><td></td><td></td><td></td><td></td><td></td><th style="text-align : right">'.__('Balance','bim-ba').'</th>
					<th style="text-align : right">' . number_format($tot, 2) . '</th>
					</tr>';
		
		echo '</table>';
		endif;
	}
	
	public function gestudio_pn_controllo(){
		
		Gestudio::admin_login_msg();
		
		if ( !isset($_POST['gstu-pn-query']) OR $_POST['gstu-pn-query'] <> $this->inspect) {//hai fatto richiesta?
			echo __('To search a Blotter Entry, select Project and/or Operator OR time range and/or Counting Category and/or keyword, then press','bim-ba'). ' "' . $this->inspect . '".';
			return;
		}
		
		if (!wp_verify_nonce( $_POST['gstu-pn-nonce-field'], 'gstu-pn-new-query-submit' )){
			Gestudio::security_issue_msg();
			return;
		}
		
		$search = array(
				'Lavoro'		=>$_POST['Lavoro'],
				'Operatore'		=>$_POST['Operatore'],
				'Categoria'		=>$_POST['Categoria'],
				'Testo'			=>urlencode( sanitize_text_field( $_POST['Testo'] ) ),
				'Prima_di_anno'	=>$_POST['Prima_di_anno'],
				'Prima_di_mese'	=>$_POST['Prima_di_mese'],
				'Dopo_di_anno'	=>$_POST['Dopo_di_anno'],
				'Dopo_di_mese'	=>$_POST['Dopo_di_mese']
		);
		
		return $search;
		
	}
	
	public function gestudio_pn_query( $search ){
		$lavoro = $search['Lavoro'];
		$operatore = $search['Operatore'];
		
		if ($lavoro AND $operatore){
		
			$args = Gestudio::args_pn_dati_lav_opr( $lavoro, $operatore );
			return $args;
		}
		
		if ($lavoro OR $operatore){
		
			$args = Gestudio::args_pn_dati_lav_or_opr( $lavoro, $operatore );
			return $args;
		}
		
		$category = $search['Categoria'];
		$testo = $search['Testo'];
		
		
		if($search['Prima_di_anno']){
			$prima_di = $search['Prima_di_anno'] . "-" . $search['Prima_di_mese'] . "-01";
		} else {
			$prima_di = '2036-12-31';
		}
		if($search['Dopo_di_anno']){
			$dopo_di = $search['Dopo_di_anno'] . "-" . $search['Dopo_di_mese'] . "-01";
		} else {
			$dopo_di = '1900-01-01';
		}
		
		If (strtotime($dopo_di) > strtotime($prima_di)){
			$temp_data = $dopo_di;
			$dopo_di = $prima_di;
			$prima_di = $temp_data;
		}
		
		
		if ($category == 0){//se non è definita la categoria non funziona la query
			
			$args = Gestudio::args_pn_dati_txt_date($testo, $dopo_di, $prima_di);
			return $args;
		} else {
			
			$args = Gestudio::args_pn_dati_cat_txt_date( $category, $testo, $dopo_di, $prima_di );
			return $args;
		}
	}
	
	public function gestudio_pn_query_form(){
		
			echo '<hr><form action="" method="post" target="_self">';
			wp_nonce_field('gstu-pn-new-query-submit', 'gstu-pn-nonce-field');
			echo '<fieldset><table>';
					
			Gestudio::select_lavori();
			
			Gestudio::select_lista_a_tendina('gstu-lavori');
			
			Gestudio::select_operatori();
		
			Gestudio::select_lista_a_tendina('gstu-operatori');
			
			echo '</table><hr>';
			
			echo '<table>';
			
			echo '<tr><td style="text-align : left"><label for="Dopo_di">' . __(' Bottom Limit:','bim-ba') . '</label></td>
			<td><select id="Dopo_di_anno" name="Dopo_di_anno" >
			<option value="1900">' . __('-Select Year-','bim-ba') . '</option>';
			
			$this->lista_anni();
			
			echo '</select></td><td><select id="Dopo_di_mese" name="Dopo_di_mese" >';
			
			$this->lista_mesi();
			
			echo'</td></tr>';
			
			echo '<tr><td style="text-align : left"><label for="Prima_di">' . __('Upper Limit:','bim-ba') . '</label></td>
			<td><select id="Prima_di_anno" name="Prima_di_anno" >
			<option value="2036">' . __('-Select Year-','bim-ba') . '</option>';
			
			$this->lista_anni();
			
			echo '</select></td><td><select id="Prima_di_mese" name="Prima_di_mese" >';
		
			$this->lista_mesi();
			
			echo'</td></tr>';
			
			$taxonomy = 'categoria-contabile'; 
			$args = array(
				'orderby'           => 'id',
				'fields'            => 'all',
				'hide_empty'		=> 0
			);
			
			$terms= get_terms($taxonomy, $args);
			
			echo '<tr><td style="text-align : left"><label for="Categoria">' . __('Counting Category:','bim-ba') . '</label></td>
			<td><select id="Categoria" name="Categoria" >
			<option value="0">' . __('-Select Category-','bim-ba') . '</option>';
			
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				foreach($terms as $term){			
					echo '<option value="' . $term->term_id . '">' . $term->name . '</option>';
				}
			}
		
			echo '</select></td><td></td></tr>';
			
			echo '<tr><td style="text-align : left"><label for="Testo">' . __('Search Text:','bim-ba') . '</label></td>
			<td colspan="2"><input id="Testo" name="Testo" type="text" size="35"/></td></tr></table><hr>';
			
			echo '<p></p>';
			
			Gestudio::inspect_button($this->inspect, 'gstu-pn-query');
	
			echo '</fieldset></form>';
			
	}
	
	public function lista_anni(){
		$oneyear = date('Y', strtotime('+1 year'));
			for ($i = 2009; $i <= $oneyear; $i++) {
				echo '<option value="' . $i . '">' . $i . '</option>';
			}
	}
	
	public function lista_mesi(){
		
		$mesi = Gestudio::array_mesi();
		
		echo '<option value="1">' . __('-Select Month-','bim-ba') . '</option>';
		for ($i = 1; $i <= 12; $i++) {
			echo '<option value="' . $i . '">' . $mesi [$i-1] . '</option>';
		}
		echo '</select>';
	}
	
	public function ciclo_immissione( $term_name, $immissione ){
		$in = 		__( 'Incomes' , 'bim-ba' );
		$out = 		__( 'Expenses' , 'bim-ba' );
		$clear = 	__( 'Clearances' , 'bim-ba' );
		$ciclo = array ($in, $out, $in, $out, $clear, $clear);
		if ( $term_name == $ciclo [$immissione-1] ){
			$err = '';
		} else {
			$err = __('Warning: Counting Category and Movement are in conflict.' , 'bim-ba' );
		}
		return $err;
	}
	
	public function gestudio_pn_incongruenza() {

		global $post;
		
		$gstu_pn_meta = get_post_meta( $post->ID, '_gstu_pn_meta', true );
		
		if ($gstu_pn_meta){
		
			$err = $gstu_pn_meta['errore'];
			
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
	$gstu_pn = new GestudioPrimeNote();



