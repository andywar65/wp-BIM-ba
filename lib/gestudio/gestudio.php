<?php
class Gestudio{
	
	public function select_operatori(){
		echo '<tr><td style="text-align : left"><label for="Operatore">' . __('Operator:','bim-ba') . '</label></td>
		<td><select id="Operatore" name="Operatore" >
		<option value="0">' . __('-Select Operator-','bim-ba') . '</option>';
	}
	
	public function select_lavori(){
		echo '<tr><td style="text-align : left"><label for="Lavoro">' . __('Project:','bim-ba') . '</label></td>
		<td><select id="Lavoro" name="Lavoro" >
		<option value="0">' . __('-Select Project-','bim-ba') . '</option>';
	}
	
	public function select_lista_a_tendina( $cpt ){

		//lista tutti gli operatori
		$args = array(
				'post_type' => array( $cpt ),
				'posts_per_page' => -1,
		);
	
		$loop = new WP_Query( $args );
	
		if ( $loop->have_posts() ){
			while ( $loop->have_posts() ) : $loop->the_post();
				
			echo '<option value="' . get_the_title() . '">' . get_the_title() . '</option>';
				
			endwhile;
		}
	
		wp_reset_postdata();
		echo '</select></td></tr>';
	}
	
	public function inspect_button($inspect, $input_name){
		echo '<input type="submit" value="' . $inspect . '" name="' . $input_name . '" />
		<input type="reset" value="' . __('Reset','bim-ba') . '" /><p>';
	}
	
	public function cerca_termine($terms){
		if ($terms){
			foreach ($terms as $term) {
				$output = $term->name;
			}
		}
		return $output;
	}
	
	public function args_lav_dato_cmt($operatore){
		$args = array(
				'post_type' => array( 'gstu-lavori' ),
				'posts_per_page' => -1,
				'meta_query' => array(
						array(
								'key'     => '_gstu_lav_meta_cmt',
								'value'   => $operatore,
								'compare' => '='
						)
				)
		);
		return $args;
	}
	
	public function args_opr_no_cmt(){
		$args = array(
					'post_type' => array( 'gstu-operatori' ),
					'posts_per_page' => -1,
					'tax_query' => array(
							array(
									'taxonomy' => 'gstu-ruoli',
									'field' => 'name',
									'terms' => __('Client', 'bim-ba'),
									'operator' => 'NOT IN'
							)
					)
			);
		return $args;
	}
	
	public function args_lav(){
		$args = array(
				'post_type' => array( 'gstu-lavori' ),
				'posts_per_page' => -1
		);
		return $args;
	}
	
	public function args_rpp_dati_lav_opr( $lavoro, $operatore ){
		$args = array (
				'post_type' => array( 'gstu-rapporti' ),
				'order_by'	=> 'date',
				'order'		=> 'ASC',
				'meta_query' => array(
						'relation' => 'AND',
						array(
								'key'     => '_gstu_rpp_meta_lav',
								'value'   => $lavoro,
								'compare' => '='
						),
						array(
								'key'     => '_gstu_rpp_meta_opr',
								'value'   => $operatore,
								'compare' => '='
						)
				)
		);
		return $args;
	}
	
	public function args_pn_dati_lav_opr($lavoro, $operatore){
		$args = array (
				'post_type' => array( 'prime-note' ),
				'meta_query' => array(
						'relation' => 'AND',
						array(
								'key'     => '_gstu_pn_meta_lav',
								'value'   => $lavoro,
								'compare' => '='
						),
						array(
								'key'     => '_gstu_pn_meta_opr',
								'value'   => $operatore,
								'compare' => '='
						)
				)
		);
		return $args;
	}
	
	public function args_pn_dati_lav_or_opr( $lavoro, $operatore ){
		$args = array (
				'post_type' => array( 'prime-note' ),
				'meta_query' => array(
						'relation' => 'OR',
						array(
								'key'     => '_gstu_pn_meta_lav',
								'value'   => $lavoro,
								'compare' => '='
						),
						array(
								'key'     => '_gstu_pn_meta_opr',
								'value'   => $operatore,
								'compare' => '='
						)
				)
		);
		
		return $args;
	}
	
	public function args_pn_dati_txt_date( $testo, $dopo_di, $prima_di ){
		$args = array(
				'post_type' => array( 'prime-note' ),
				'posts_per_page' => -1,
				's' => $testo,
				'date_query' => array(
						array(
								'after' => $dopo_di,
								'before' => $prima_di
						)
				)
		);
		
		return $args;
	}
	
	public function args_pn_dati_cat_txt_date( $category, $testo, $dopo_di, $prima_di ){
		$args = array(
				'post_type' => array( 'prime-note' ),
				'posts_per_page' => -1,
				'tax_query' => array(
						array(
								'taxonomy' => 'categoria-contabile',
								'field' => 'id',
								'terms' => $category
						)
				),
				's' => $testo,
				'date_query' => array(
						array(
								'after' => $dopo_di,
								'before' => $prima_di
						)
				)
		);
		
		return $args;
	}
	
	public function args_opr_dato_stu(){
		$args = array(
				'post_type' => array( 'gstu-operatori' ),
				'posts_per_page' => -1,
				'tax_query' => array(
						array(
								'taxonomy' => 'gstu-ruoli',
								'field' => 'name',
								'terms' => __( 'Studio', 'bim-ba' )
						)
				)
		);
		
		return $args;
	}
	
	public function calcola_importo_pn( $immissione, $importo ){
		if ($immissione == 2 OR $immissione == 4){
			$importo = -$importo;
		}
		if ($immissione == 5 OR $immissione == 6){
			$importo = 0;
		}
		return $importo;
	}
	
	public function calcola_importo_rpp($gstu_rpp_meta){
		$imponibile = 	$gstu_rpp_meta ['imponibile'];
		$iva = 			$gstu_rpp_meta ['iva'];
		$ritenuta = 	$gstu_rpp_meta ['ritenuta'];
		$contributi = 	$gstu_rpp_meta ['contributi'];
		
		$importo = $imponibile + ( $imponibile + $imponibile * $contributi / 100 ) * $iva / 100 - $imponibile * $contributi / 100;
		return $importo;
	}
	
	public function head_opr_table_rpp_pn(){
		echo '<br><table class="wp-list-table widefat fixed striped posts">';
		echo '<tr><th>' . __('Project' , 'bim-ba' ) . '</th><th>'
				. __('Report/Blotter Entry' , 'bim-ba' ) . '</th><th>'
						. __('Type/Count. Cat.' , 'bim-ba' ) . '</th><th>'
								. __('Date' , 'bim-ba' ) . '</th><th style="text-align : right">'
										. __('Amount' , 'bim-ba' ) . '</th></tr>';
	}
	
	public function head_lav_table_rpp_pn(){
		echo '<br><table class="wp-list-table widefat fixed striped posts">';
		echo '<tr><th>' . __('Operator' , 'bim-ba' ) . '</th><th>'
				. __('Role' , 'bim-ba' ) . '</th><th>'
						. __('Report/Blotter Entry' , 'bim-ba' ) . '</th><th>'
								. __('Type/Count. Cat.' , 'bim-ba' ) . '</th><th>'
										. __('Date' , 'bim-ba' ) . '</th><th style="text-align : right">'
												. __('Amount' , 'bim-ba' ) . '</th></tr>';
	}
	
	public function footer_table_bilancio($tot){
		$output = '<th style="text-align : right">'
							. __('Balance' , 'bim-ba' ) . '</th><td style="text-align : right">'
							. number_format($tot, 2) . '</td>';
		return $output;
	}
	
	public function footer_table_previsione($operatore, $super_tot){
		$output = '<th style="text-align : right">'
					. __('Forecast Incomes ' , 'bim-ba' ) . $operatore . '</th><td style="text-align : right">'
					. number_format($super_tot, 2) . '</td>';
		return $output;
	}
	
	public function is_invoice($categoria, $importo){
		if ($categoria == __('Invoice', 'bim-ba') ){
			$importo = -$importo;
		}
		return $importo;
	}
	
	public function admin_login_msg(){
		if (!current_user_can( 'manage_options' )){//are you administrator?
			echo __('You must login as Administrator.','bim-ba');
			return;
		}
	}
	
	public function security_issue_msg(){
		echo __('Sorry.There was a security issue.','bim-ba');
	}
	
	public function tempistica_anno_mese( $tempistica ){
		if ($tempistica){
			$anno = $tempistica ['anno'];
			$mese = $tempistica ['mese'];
			if ( $mese > 9 ) {
				$mese = '-' . $mese;
			} else {
				$mese = '-0' . $mese;
			}
		}
		$annomese = $anno . $mese;
		return $annomese;
	}
	
	public function array_mesi(){
		$mesi = array(//questa è una ripetizione, bisognerebbe eliminarla
				__('January','bim-ba'),
				__('February','bim-ba'),
				__('March','bim-ba'),
				__('April','bim-ba'),
				__('May','bim-ba'),
				__('June','bim-ba'),
				__('July','bim-ba'),
				__('August','bim-ba'),
				__('September','bim-ba'),
				__('October','bim-ba'),
				__('November','bim-ba'),
				__('December','bim-ba')
		);
		return $mesi;
	}
	
	public function array_lista_cmt(){
			
		$args_cmt = array(
				'post_type' => array( 'gstu-operatori' ),
				'posts_per_page' => -1,
				'tax_query' => array(
						array(
								'taxonomy' => 'gstu-ruoli',
								'field' => 'name',
								'terms' => __('Client', 'bim-ba')
						)
				)
		);
	
		$loop_cmt = new WP_Query( $args_cmt );
	
		if ( $loop_cmt->have_posts() ){
			$output = array();
			while ( $loop_cmt->have_posts() ) : $loop_cmt->the_post();
			$output [] = $loop_cmt->post->post_title;
			endwhile;
		}
		wp_reset_postdata();
		return $output;
	}
	
	public function array_lista_opr_no_cmt(){
		
		$args_opr = self::args_opr_no_cmt();
	
		$loop_opr = new WP_Query( $args_opr );
	
		if ( $loop_opr->have_posts() ){
			$output = array();
			while ( $loop_opr->have_posts() ) : $loop_opr->the_post();
			$output [] = $loop_opr->post->post_title;
			endwhile;
		}
		wp_reset_postdata();
		return $output;
	}
	
	public function array_lista_opr(){
		$args_opr = array(
				'post_type' => array( 'gstu-operatori' ),
				'posts_per_page' => -1,
		);
	
		$loop_opr = new WP_Query( $args_opr );
	
		if ( $loop_opr->have_posts() ){
			$output = array();
			while ( $loop_opr->have_posts() ) : $loop_opr->the_post();
			$output [] = $loop_opr->post->post_title;
			endwhile;
		}
		wp_reset_postdata();
		return $output;
	}
	
	public function array_lista_lav(){
		
		$args_lav = self::args_lav();
	
		$loop_lav = new WP_Query( $args_lav );
	
		if ( $loop_lav->have_posts() ){
			$output = array();
			while ( $loop_lav->have_posts() ) : $loop_lav->the_post();
			$output [] = $loop_lav->post->post_title;
			endwhile;
		}
		wp_reset_postdata();
		return $output;
	}
	
	public function array_lista_ruoli_opr(){
		
		$args_ruo = self::args_opr_no_cmt();
	
		$loop_ruo = new WP_Query( $args_ruo );
	
		if ( $loop_ruo->have_posts() ){
			$output = array();
			while ( $loop_ruo->have_posts() ) : $loop_ruo->the_post();
			$terms = get_the_terms(  $loop_ruo->post->ID , 'gstu-ruoli');
			foreach ($terms as $term) {
				$output [ $loop_ruo->post->post_title ] = $term->name;
			}
			endwhile;
		}
		wp_reset_postdata();
		return $output;
	}
	
	public function categorie_figlie_uscite(){
		$parent_term_id = term_exists(__('Expenses','bim-ba'), 'categoria-contabile' );
		$args = array(
				'orderby'           => 'id',
				'order'             => 'ASC',
				'hide_empty'        => false,
				'parent'            => $parent_term_id['term_id']
		);
		$terms = get_terms ('categoria-contabile', $args);
		return $terms;
	}
	
	public function data_ultima_prima_nota(){
		$loop = new WP_Query ('post_type=prime-note&limit=1');
		if ($loop->have_posts()){
			while ($loop->have_posts()): $loop->the_post();
			$data_ultima = $loop->post->post_date;
			endwhile;
		}
		wp_reset_postdata();
		return $data_ultima;
	}

}

if( is_admin() )
	$gstu = new Gestudio();



