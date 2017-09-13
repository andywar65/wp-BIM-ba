<?php
class Steel_Deck{
	
	/**
	 * Start up
	 */
	public function __construct()
	{
		add_shortcode('steel_deck', array( $this, 'steel_deck_shortcode' ) );
	}
	
	public function steel_deck_shortcode($atts, $content=null)
	{
		if (isset($_POST['deck-form']) AND $_POST['deck-form'] == 'Submit') {// control if submitted
			$error = 0; // here we control and sanitize input
			if (!wp_verify_nonce( $_POST['steel-deck-nonce-field'], 'steel-deck-submit' )){
				$error = 1;
			}
			$inputs = array('side-a', 'side-b', 'restraint', 'deck', 'steel', 'profile', 'criterion');
			foreach ($inputs as $input) {
				if (isset($_POST[$input]) AND is_numeric ($_POST[$input]) AND $_POST[$input] <> 0) {
					//input is ok, go ahead
				} else {
					$error = 1;
				}
			}
			if ($error == 0) { // if no error go ahead
				if ($_POST['side-a'] >= 2 AND $_POST['side-b'] >= 2) {// control if sides are smaller than 2m
					if ($_POST['side-a'] > $_POST['side-b']) {//here variables used for calculation
						$prime_span = $_POST['side-b'];
						$deck_width = $_POST['side-a'];
					} else {
						$prime_span = $_POST['side-a'];
						$deck_width = $_POST['side-b'];
					}
					$prime_lowering = $prime_span / 500 * 100;//maximum lowering of primary beam
					$momentum_div = $_POST['restraint'];
					$deck = $_POST['deck'];
					$steel = $_POST['steel'];
					$profile = $_POST['profile'];
					$criterion = $_POST['criterion'];
					$sections = $this->section_array( $criterion );
					$count = count( $sections );
					$E = 2100000;//should be global
					$second_dist = $prime_span / (int) $prime_span;
					$Fy = $steel * 10;
						
					switch ($momentum_div) {//kind of restraint
						case '8':
							$per_lowering = 5;
							break;
						case '10':
							$per_lowering = 3;
							break;
						default:
							$per_lowering = 1;
					}
						
					switch ($profile) {//exclude profile
						case '1':
							$excluded = 'none';
							break;
						case '2':
							$excluded = 'IPE';
							break;
						default:
							$excluded = 'HE';
					}
						
					switch ($steel) {//steel price
						case '235':
							$steel_price = 3.12;
							break;
						case '275':
							$steel_price = 3.35;
							break;
						default:
							$steel_price = 3.49;
					}
		
						
					echo '<p></p>';
					echo __('Maximum lowering of Primary Beams: ', 'bimba') . number_format($prime_lowering , 2) . __(' cm (1/500 of span).<br />', 'bimba');
					echo __('Distance of Secondary Beams: ', 'bimba') . number_format($second_dist , 2) . ' m.<br />';
					echo '<p></p>';
		
					$mem_sec_resist = '';//these memorize primary cycle
					$mem_sec_deform = '';
					$mem_prime_resist = '';
					$mem_prime_deform = '';
					$mem_weight_resist = '';
					$mem_weight_deform = '';
					$mem_dist_resist = '';
					$mem_dist_deform = '';
						
					for ($j = 2; $j <= $deck_width; $j++):// primary cycle
		
					$second_resist = '';// these memorize winning section
					$second_deform = '';
					$f_max = $deck_width / $j / 200 * 100;//secondary maximum lowering
		
					// SECONDARY ANALISYS
					for ($i = 0; $i < $count; $i++):
					if (($profile == 3 AND $sections [$i] ['type'] == 'IPE') OR ($profile == 2 AND $sections [$i] ['type'] == 'HE') OR $profile ==1) {
						$P = (($deck * 100 + 200) * $second_dist + ($sections [$i] ['kg'])) / 100;// ULS resistance
						$Md = (1.5 * $P * pow(($deck_width / $j * 100), 2)) / $momentum_div;
						$Mr = $Fy * $sections [$i] ['W'];
						if ($Mr > $Md AND $second_resist == '') {
							$second_resist = $i;
						}
		
						$lowering = ($per_lowering * $P * pow(($deck_width / $j *100), 4)) / (384 * $E * $sections [$i] ['J']);// ELS deformation
						if ($lowering < $f_max AND $second_deform == '') {
							$second_deform = $i;
						}
					}
					endfor;
		
					if ($second_resist <> '' AND $second_deform <> '') {//control, if no secondary then more primaries!
		
		
						$prime_resist = '';// these memorize winning section
						$prime_deform = '';
						$second_weight_resist = ($sections [$second_resist] ['kg']) * $deck_width / $j / $second_dist;
						$second_weight_deform = ($sections [$second_deform] ['kg']) * $deck_width / $j / $second_dist;
		
						// PRIMARY ANALISYS
						for ($i = 0; $i < $count; $i++):
						if (($profile == 3 AND $sections [$i] ['type'] == 'IPE') OR ($profile == 2 AND $sections [$i] ['type'] == 'HE') OR $profile ==1) {
							$P = (($deck * 100 + 200) * $deck_width / $j + ($sections [$i] ['kg']) + $second_weight_resist) / 100;// ULS resistance
							$Md = (1.5 * $P * pow(($prime_span * 100), 2)) / $momentum_div;
							$Mr = $Fy * $sections [$i] ['W'];
							if ($Mr > $Md AND $prime_resist == '') {
								$prime_resist = $i;
							}
		
							$P = (($deck * 100 + 200) * $deck_width / $j + ($sections [$i] ['kg']) + $second_weight_deform) / 100;
							$lowering = ($per_lowering * $P * pow(($prime_span * 100), 4)) / (384 * $E * $sections [$i] ['J']);// ELS deformation
							if ($lowering < $prime_lowering AND $prime_deform == '') {
								$prime_deform = $i;
							}
						}
						endfor;
		
		
						if ($prime_resist <> '' AND $prime_deform <> '') {//control, did not find primary, then thicken
							$tot_weight_resist = (($sections [$prime_resist] ['kg']) * $prime_span * ($j - 1))
							+ (($sections [$second_resist] ['kg']) * $deck_width * ((int) $prime_span - 1));
							$tot_weight_deform = (($sections [$prime_deform] ['kg']) * $prime_span * ($j - 1))
							+ (($sections [$second_deform] ['kg']) * $deck_width * ((int) $prime_span - 1));
		
							if ($mem_weight_resist == '' OR $mem_weight_resist > $tot_weight_resist) {//memorize results for this cycle
								$mem_weight_resist = $tot_weight_resist;
								$mem_sec_resist = $second_resist;
								$mem_prime_resist = $prime_resist;
								$mem_dist_resist = $j;
							}
		
							if ($mem_weight_deform == '' OR $mem_weight_deform > $tot_weight_deform) {//memorize results for this cycle
								$mem_weight_deform = $tot_weight_deform;
								$mem_sec_deform = $second_deform;
								$mem_prime_deform = $prime_deform;
								$mem_dist_deform = $j;
							}
						}
					}
					endfor;//end primary cycle
		
					//results
					if ($j > $deck_width AND $mem_prime_resist == '') { // no ULS results
						echo "<p>" . __('No solution for resistance criterion (ULS), maybe deck is too big for available sections.', 'bimba') . "</p>";
					}
					else {
						$string = __('<p>For resistance criterion (ULS) you can use %d primary beam(s) %s%s at distance %01.2f m and secondary %s%s at distance %01.2f m.<br />', 'bimba');
						printf($string, $mem_dist_resist - 1, $sections [$mem_prime_resist] ['type'], $sections [$mem_prime_resist] ['H'], $deck_width / $mem_dist_resist ,
								$sections [$mem_sec_resist] ['type'], $sections [$mem_sec_resist] ['H'], $second_dist );
						
						$string = __("Deck's total weight is %01.2f kg and costs around %01.2f Euro.</p>", 'bimba');
						printf($string, $mem_weight_resist , $mem_weight_resist * $steel_price );
						
					}
						
					if ($j > $deck_width AND $mem_prime_deform == '') { // if no results in elastic criterion
						echo "<p>" . __('No solution for deformation criterion (ELS), maybe deck is too big for available sections.', 'bimba') . "</p>";
					}
					else {
						$string = __('<p>For deformation criterion (ELS) you can use %d primary beam(s) %s%s at distance %01.2f m and secondary %s%s at distance %01.2f m.<br />', 'bimba');
						printf($string, $mem_dist_deform - 1, $sections [$mem_prime_deform] ['type'], $sections [$mem_prime_deform] ['H'], $deck_width / $mem_dist_deform ,
						$sections [$mem_sec_deform] ['type'], $sections [$mem_sec_deform] ['H'], $second_dist );
						
						$string = __("Deck's total weight is %01.2f kg and costs around %01.2f Euro.</p>", 'bimba');
						printf($string, $mem_weight_deform , $mem_weight_deform * $steel_price );
						
					}
					$this->deck_form_full($prime_span, $deck_width, $momentum_div, $deck, $steel, $profile, $criterion);
						
				} 
		
			} else { 
				echo "<p>" . __('Analisys not performed, maybe due to a security issue.', 'bimba') . "</p>";
				$this->deck_form_void();
			}
		
		} else {
			echo "<p>" . __ ("Notice that fields 'First Side' and 'Second Side' are required (*) and must be equal or bigger than 2.", "bimba" ) . "</p>";
			$this->deck_form_void();
		}
	}
	
	public function section_array( $criterion )
	{
		if (isset ($criterion) AND $criterion == 2) {
			$sections = array(
					array('id' => 0, 'type' => 'IPE', 'H' => '80', 'kg' => '6', 'A' => '7.6', 'J' => '80.1', 'W' => '20'),
					array('id' => 1, 'type' => 'IPE', 'H' => '100', 'kg' => '8.1', 'A' => '10.3', 'J' => '171', 'W' => '34.2'),
					array('id' => 5, 'type' => 'HE', 'H' => 'A100', 'kg' => '16.7', 'A' => '21.24', 'J' => '349.2', 'W' => '72.76'),
					array('id' => 9, 'type' => 'HE', 'H' => 'B100', 'kg' => '20.4', 'A' => '26.04', 'J' => '449.5', 'W' => '89.91'),
					array('id' => 2, 'type' => 'IPE', 'H' => '120', 'kg' => '10.4', 'A' => '13.2', 'J' => '317.8', 'W' => '53'),
					array('id' => 7, 'type' => 'HE', 'H' => 'A120', 'kg' => '19.9', 'A' => '25.34', 'J' => '606.2', 'W' => '106.3'),
					array('id' => 12, 'type' => 'HE', 'H' => 'B120', 'kg' => '26.7', 'A' => '34.01', 'J' => '864.4', 'W' => '144.1'),
					array('id' => 3, 'type' => 'IPE', 'H' => '140', 'kg' => '12.9', 'A' => '16.4', 'J' => '541.2', 'W' => '77.3'),
					array('id' => 10, 'type' => 'HE', 'H' => 'A140', 'kg' => '24.7', 'A' => '31.42', 'J' => '1033', 'W' => '155.4'),
					array('id' => 15, 'type' => 'HE', 'H' => 'B140', 'kg' => '33.7', 'A' => '42.96', 'J' => '1509', 'W' => '215.6'),
					array('id' => 4, 'type' => 'IPE', 'H' => '160', 'kg' => '15.8', 'A' => '20.1', 'J' => '869.3', 'W' => '108.7'),
					array('id' => 13, 'type' => 'HE', 'H' => 'A160', 'kg' => '30.4', 'A' => '38.77', 'J' => '1673', 'W' => '220.1'),
					array('id' => 20, 'type' => 'HE', 'H' => 'B160', 'kg' => '42.6', 'A' => '54.25', 'J' => '2492', 'W' => '311.5'),
					array('id' => 6, 'type' => 'IPE', 'H' => '180', 'kg' => '18.8', 'A' => '23.9', 'J' => '1317', 'W' => '146.3'),
					array('id' => 16, 'type' => 'HE', 'H' => 'A180', 'kg' => '35.5', 'A' => '45.25', 'J' => '2510', 'W' => '293.6'),
					array('id' => 23, 'type' => 'HE', 'H' => 'B180', 'kg' => '51.2', 'A' => '65.25', 'J' => '3831', 'W' => '425.7'),
					array('id' => 9, 'type' => 'IPE', 'H' => '200', 'kg' => '22.4', 'A' => '28.5', 'J' => '1943.2', 'W' => '194.3'),
					array('id' => 19, 'type' => 'HE', 'H' => 'A200', 'kg' => '42.3', 'A' => '53.83', 'J' => '3692', 'W' => '388.6'),
					array('id' => 26, 'type' => 'HE', 'H' => 'B200', 'kg' => '61.3', 'A' => '78.08', 'J' => '5696', 'W' => '569.6'),
					array('id' => 11, 'type' => 'IPE', 'H' => '220', 'kg' => '26.2', 'A' => '33.4', 'J' => '2771.8', 'W' => '252'),
					array('id' => 22, 'type' => 'HE', 'H' => 'A220', 'kg' => '50.5', 'A' => '64.34', 'J' => '5410', 'W' => '515.2'),
					array('id' => 29, 'type' => 'HE', 'H' => 'B220', 'kg' => '71.5', 'A' => '91.04', 'J' => '8091', 'W' => '735.5'),
					array('id' => 14, 'type' => 'IPE', 'H' => '240', 'kg' => '30.7', 'A' => '39.1', 'J' => '3891.6', 'W' => '324.3'),
					array('id' => 25, 'type' => 'HE', 'H' => 'A240', 'kg' => '60.3', 'A' => '76.84', 'J' => '7763', 'W' => '675.1'),
					array('id' => 32, 'type' => 'HE', 'H' => 'B240', 'kg' => '83.2', 'A' => '106', 'J' => '11260', 'W' => '938.3'),
					array('id' => 28, 'type' => 'HE', 'H' => 'A260', 'kg' => '68.2', 'A' => '86.82', 'J' => '10450', 'W' => '836.4'),
					array('id' => 35, 'type' => 'HE', 'H' => 'B260', 'kg' => '93', 'A' => '118.4', 'J' => '14920', 'W' => '1148'),
					array('id' => 17, 'type' => 'IPE', 'H' => '270', 'kg' => '36.1', 'A' => '45.9', 'J' => '5789.8', 'W' => '428.9'),
					array('id' => 30, 'type' => 'HE', 'H' => 'A280', 'kg' => '76.4', 'A' => '97.26', 'J' => '13670', 'W' => '1013'),
					array('id' => 37, 'type' => 'HE', 'H' => 'B280', 'kg' => '103', 'A' => '131.4', 'J' => '19270', 'W' => '1376'),
					array('id' => 18, 'type' => 'IPE', 'H' => '300', 'kg' => '42.2', 'A' => '53.8', 'J' => '8356.1', 'W' => '557.1'),
					array('id' => 33, 'type' => 'HE', 'H' => 'A300', 'kg' => '88.3', 'A' => '112.5', 'J' => '18260', 'W' => '1260'),
					array('id' => 41, 'type' => 'HE', 'H' => 'B300', 'kg' => '117', 'A' => '149.1', 'J' => '25170', 'W' => '1678'),
					array('id' => 36, 'type' => 'HE', 'H' => 'A320', 'kg' => '97.6', 'A' => '124.4', 'J' => '22930', 'W' => '1479'),
					array('id' => 44, 'type' => 'HE', 'H' => 'B320', 'kg' => '127', 'A' => '161.3', 'J' => '30820', 'W' => '1926'),
					array('id' => 21, 'type' => 'IPE', 'H' => '330', 'kg' => '49.1', 'A' => '62.6', 'J' => '11766.9', 'W' => '713.1'),
					array('id' => 38, 'type' => 'HE', 'H' => 'A340', 'kg' => '105', 'A' => '133.5', 'J' => '27690', 'W' => '1678'),
					array('id' => 45, 'type' => 'HE', 'H' => 'B340', 'kg' => '134', 'A' => '170.9', 'J' => '36660', 'W' => '2156'),
					array('id' => 24, 'type' => 'IPE', 'H' => '360', 'kg' => '57.1', 'A' => '72.7', 'J' => '16265.6', 'W' => '903.6'),
					array('id' => 40, 'type' => 'HE', 'H' => 'A360', 'kg' => '112', 'A' => '142.8', 'J' => '33090', 'W' => '1891'),
					array('id' => 47, 'type' => 'HE', 'H' => 'B360', 'kg' => '142', 'A' => '180.6', 'J' => '43190', 'W' => '2400'),
					array('id' => 27, 'type' => 'IPE', 'H' => '400', 'kg' => '66.3', 'A' => '84.5', 'J' => '23128.3', 'W' => '1156.4'),
					array('id' => 43, 'type' => 'HE', 'H' => 'A400', 'kg' => '125', 'A' => '159', 'J' => '45070', 'W' => '2311'),
					array('id' => 48, 'type' => 'HE', 'H' => 'B400', 'kg' => '155', 'A' => '197.8', 'J' => '57680', 'W' => '2884'),
					array('id' => 31, 'type' => 'IPE', 'H' => '450', 'kg' => '77.6', 'A' => '98.8', 'J' => '33742.9', 'W' => '1499.7'),
					array('id' => 46, 'type' => 'HE', 'H' => 'A450', 'kg' => '140', 'A' => '178', 'J' => '63720', 'W' => '2896'),
					array('id' => 51, 'type' => 'HE', 'H' => 'B450', 'kg' => '171', 'A' => '218', 'J' => '79890', 'W' => '3551'),
					array('id' => 34, 'type' => 'IPE', 'H' => '500', 'kg' => '90.7', 'A' => '115.5', 'J' => '48198.5', 'W' => '1927.9'),
					array('id' => 49, 'type' => 'HE', 'H' => 'A500', 'kg' => '155', 'A' => '197.5', 'J' => '86970', 'W' => '3550'),
					array('id' => 53, 'type' => 'HE', 'H' => 'B500', 'kg' => '187', 'A' => '238.6', 'J' => '107200', 'W' => '4287'),
					array('id' => 39, 'type' => 'IPE', 'H' => '550', 'kg' => '105.5', 'A' => '134.4', 'J' => '67116.5', 'W' => '2440.6'),
					array('id' => 50, 'type' => 'HE', 'H' => 'A550', 'kg' => '166', 'A' => '211.8', 'J' => '111900', 'W' => '4146'),
					array('id' => 55, 'type' => 'HE', 'H' => 'B550', 'kg' => '199', 'A' => '254.1', 'J' => '136700', 'W' => '4971'),
					array('id' => 42, 'type' => 'IPE', 'H' => '600', 'kg' => '122.4', 'A' => '156', 'J' => '92083.4', 'W' => '3069.4'),
					array('id' => 52, 'type' => 'HE', 'H' => 'A600', 'kg' => '178', 'A' => '226.5', 'J' => '141200', 'W' => '4787'),
					array('id' => 57, 'type' => 'HE', 'H' => 'B600', 'kg' => '212', 'A' => '270', 'J' => '171000', 'W' => '5701'),
					array('id' => 54, 'type' => 'HE', 'H' => 'A650', 'kg' => '190', 'A' => '241.6', 'J' => '175200', 'W' => '5474'),
					array('id' => 59, 'type' => 'HE', 'H' => 'B650', 'kg' => '225', 'A' => '286.3', 'J' => '210600', 'W' => '6480'),
					array('id' => 56, 'type' => 'HE', 'H' => 'A700', 'kg' => '204', 'A' => '260.5', 'J' => '215300', 'W' => '6241'),
					array('id' => 60, 'type' => 'HE', 'H' => 'B700', 'kg' => '241', 'A' => '306.4', 'J' => '256900', 'W' => '7340'),
					array('id' => 58, 'type' => 'HE', 'H' => 'A800', 'kg' => '224', 'A' => '285.8', 'J' => '303400', 'W' => '7682'),
					array('id' => 62, 'type' => 'HE', 'H' => 'B800', 'kg' => '262', 'A' => '334.2', 'J' => '359100', 'W' => '8977'),
					array('id' => 61, 'type' => 'HE', 'H' => 'A900', 'kg' => '252', 'A' => '320.5', 'J' => '422100', 'W' => '9485'),
					array('id' => 64, 'type' => 'HE', 'H' => 'B900', 'kg' => '291', 'A' => '371.3', 'J' => '494100', 'W' => '10980'),
					array('id' => 63, 'type' => 'HE', 'H' => 'A1000', 'kg' => '272', 'A' => '346.8', 'J' => '553800', 'W' => '11190'),
					array('id' => 65, 'type' => 'HE', 'H' => 'B1000', 'kg' => '314', 'A' => '400', 'J' => '644700', 'W' => '12890')
			);
		} else {
			$sections = array(
					array('id' => 0, 'type' => 'IPE', 'H' => '80', 'kg' => '6', 'A' => '7.6', 'J' => '80.1', 'W' => '20'),
					array('id' => 1, 'type' => 'IPE', 'H' => '100', 'kg' => '8.1', 'A' => '10.3', 'J' => '171', 'W' => '34.2'),
					array('id' => 2, 'type' => 'IPE', 'H' => '120', 'kg' => '10.4', 'A' => '13.2', 'J' => '317.8', 'W' => '53'),
					array('id' => 3, 'type' => 'IPE', 'H' => '140', 'kg' => '12.9', 'A' => '16.4', 'J' => '541.2', 'W' => '77.3'),
					array('id' => 4, 'type' => 'IPE', 'H' => '160', 'kg' => '15.8', 'A' => '20.1', 'J' => '869.3', 'W' => '108.7'),
					array('id' => 5, 'type' => 'HE', 'H' => 'A100', 'kg' => '16.7', 'A' => '21.24', 'J' => '349.2', 'W' => '72.76'),
					array('id' => 6, 'type' => 'IPE', 'H' => '180', 'kg' => '18.8', 'A' => '23.9', 'J' => '1317', 'W' => '146.3'),
					array('id' => 7, 'type' => 'HE', 'H' => 'A120', 'kg' => '19.9', 'A' => '25.34', 'J' => '606.2', 'W' => '106.3'),
					array('id' => 9, 'type' => 'HE', 'H' => 'B100', 'kg' => '20.4', 'A' => '26.04', 'J' => '449.5', 'W' => '89.91'),
					array('id' => 9, 'type' => 'IPE', 'H' => '200', 'kg' => '22.4', 'A' => '28.5', 'J' => '1943.2', 'W' => '194.3'),
					array('id' => 10, 'type' => 'HE', 'H' => 'A140', 'kg' => '24.7', 'A' => '31.42', 'J' => '1033', 'W' => '155.4'),
					array('id' => 11, 'type' => 'IPE', 'H' => '220', 'kg' => '26.2', 'A' => '33.4', 'J' => '2771.8', 'W' => '252'),
					array('id' => 12, 'type' => 'HE', 'H' => 'B120', 'kg' => '26.7', 'A' => '34.01', 'J' => '864.4', 'W' => '144.1'),
					array('id' => 13, 'type' => 'HE', 'H' => 'A160', 'kg' => '30.4', 'A' => '38.77', 'J' => '1673', 'W' => '220.1'),
					array('id' => 14, 'type' => 'IPE', 'H' => '240', 'kg' => '30.7', 'A' => '39.1', 'J' => '3891.6', 'W' => '324.3'),
					array('id' => 15, 'type' => 'HE', 'H' => 'B140', 'kg' => '33.7', 'A' => '42.96', 'J' => '1509', 'W' => '215.6'),
					array('id' => 16, 'type' => 'HE', 'H' => 'A180', 'kg' => '35.5', 'A' => '45.25', 'J' => '2510', 'W' => '293.6'),
					array('id' => 17, 'type' => 'IPE', 'H' => '270', 'kg' => '36.1', 'A' => '45.9', 'J' => '5789.8', 'W' => '428.9'),
					array('id' => 18, 'type' => 'IPE', 'H' => '300', 'kg' => '42.2', 'A' => '53.8', 'J' => '8356.1', 'W' => '557.1'),
					array('id' => 19, 'type' => 'HE', 'H' => 'A200', 'kg' => '42.3', 'A' => '53.83', 'J' => '3692', 'W' => '388.6'),
					array('id' => 20, 'type' => 'HE', 'H' => 'B160', 'kg' => '42.6', 'A' => '54.25', 'J' => '2492', 'W' => '311.5'),
					array('id' => 21, 'type' => 'IPE', 'H' => '330', 'kg' => '49.1', 'A' => '62.6', 'J' => '11766.9', 'W' => '713.1'),
					array('id' => 22, 'type' => 'HE', 'H' => 'A220', 'kg' => '50.5', 'A' => '64.34', 'J' => '5410', 'W' => '515.2'),
					array('id' => 23, 'type' => 'HE', 'H' => 'B180', 'kg' => '51.2', 'A' => '65.25', 'J' => '3831', 'W' => '425.7'),
					array('id' => 24, 'type' => 'IPE', 'H' => '360', 'kg' => '57.1', 'A' => '72.7', 'J' => '16265.6', 'W' => '903.6'),
					array('id' => 25, 'type' => 'HE', 'H' => 'A240', 'kg' => '60.3', 'A' => '76.84', 'J' => '7763', 'W' => '675.1'),
					array('id' => 26, 'type' => 'HE', 'H' => 'B200', 'kg' => '61.3', 'A' => '78.08', 'J' => '5696', 'W' => '569.6'),
					array('id' => 27, 'type' => 'IPE', 'H' => '400', 'kg' => '66.3', 'A' => '84.5', 'J' => '23128.3', 'W' => '1156.4'),
					array('id' => 28, 'type' => 'HE', 'H' => 'A260', 'kg' => '68.2', 'A' => '86.82', 'J' => '10450', 'W' => '836.4'),
					array('id' => 29, 'type' => 'HE', 'H' => 'B220', 'kg' => '71.5', 'A' => '91.04', 'J' => '8091', 'W' => '735.5'),
					array('id' => 30, 'type' => 'HE', 'H' => 'A280', 'kg' => '76.4', 'A' => '97.26', 'J' => '13670', 'W' => '1013'),
					array('id' => 31, 'type' => 'IPE', 'H' => '450', 'kg' => '77.6', 'A' => '98.8', 'J' => '33742.9', 'W' => '1499.7'),
					array('id' => 32, 'type' => 'HE', 'H' => 'B240', 'kg' => '83.2', 'A' => '106', 'J' => '11260', 'W' => '938.3'),
					array('id' => 33, 'type' => 'HE', 'H' => 'A300', 'kg' => '88.3', 'A' => '112.5', 'J' => '18260', 'W' => '1260'),
					array('id' => 34, 'type' => 'IPE', 'H' => '500', 'kg' => '90.7', 'A' => '115.5', 'J' => '48198.5', 'W' => '1927.9'),
					array('id' => 35, 'type' => 'HE', 'H' => 'B260', 'kg' => '93', 'A' => '118.4', 'J' => '14920', 'W' => '1148'),
					array('id' => 36, 'type' => 'HE', 'H' => 'A320', 'kg' => '97.6', 'A' => '124.4', 'J' => '22930', 'W' => '1479'),
					array('id' => 37, 'type' => 'HE', 'H' => 'B280', 'kg' => '103', 'A' => '131.4', 'J' => '19270', 'W' => '1376'),
					array('id' => 38, 'type' => 'HE', 'H' => 'A340', 'kg' => '105', 'A' => '133.5', 'J' => '27690', 'W' => '1678'),
					array('id' => 39, 'type' => 'IPE', 'H' => '550', 'kg' => '105.5', 'A' => '134.4', 'J' => '67116.5', 'W' => '2440.6'),
					array('id' => 40, 'type' => 'HE', 'H' => 'A360', 'kg' => '112', 'A' => '142.8', 'J' => '33090', 'W' => '1891'),
					array('id' => 41, 'type' => 'HE', 'H' => 'B300', 'kg' => '117', 'A' => '149.1', 'J' => '25170', 'W' => '1678'),
					array('id' => 42, 'type' => 'IPE', 'H' => '600', 'kg' => '122.4', 'A' => '156', 'J' => '92083.4', 'W' => '3069.4'),
					array('id' => 43, 'type' => 'HE', 'H' => 'A400', 'kg' => '125', 'A' => '159', 'J' => '45070', 'W' => '2311'),
					array('id' => 44, 'type' => 'HE', 'H' => 'B320', 'kg' => '127', 'A' => '161.3', 'J' => '30820', 'W' => '1926'),
					array('id' => 45, 'type' => 'HE', 'H' => 'B340', 'kg' => '134', 'A' => '170.9', 'J' => '36660', 'W' => '2156'),
					array('id' => 46, 'type' => 'HE', 'H' => 'A450', 'kg' => '140', 'A' => '178', 'J' => '63720', 'W' => '2896'),
					array('id' => 47, 'type' => 'HE', 'H' => 'B360', 'kg' => '142', 'A' => '180.6', 'J' => '43190', 'W' => '2400'),
					array('id' => 48, 'type' => 'HE', 'H' => 'B400', 'kg' => '155', 'A' => '197.8', 'J' => '57680', 'W' => '2884'),
					array('id' => 49, 'type' => 'HE', 'H' => 'A500', 'kg' => '155', 'A' => '197.5', 'J' => '86970', 'W' => '3550'),
					array('id' => 50, 'type' => 'HE', 'H' => 'A550', 'kg' => '166', 'A' => '211.8', 'J' => '111900', 'W' => '4146'),
					array('id' => 51, 'type' => 'HE', 'H' => 'B450', 'kg' => '171', 'A' => '218', 'J' => '79890', 'W' => '3551'),
					array('id' => 52, 'type' => 'HE', 'H' => 'A600', 'kg' => '178', 'A' => '226.5', 'J' => '141200', 'W' => '4787'),
					array('id' => 53, 'type' => 'HE', 'H' => 'B500', 'kg' => '187', 'A' => '238.6', 'J' => '107200', 'W' => '4287'),
					array('id' => 54, 'type' => 'HE', 'H' => 'A650', 'kg' => '190', 'A' => '241.6', 'J' => '175200', 'W' => '5474'),
					array('id' => 55, 'type' => 'HE', 'H' => 'B550', 'kg' => '199', 'A' => '254.1', 'J' => '136700', 'W' => '4971'),
					array('id' => 56, 'type' => 'HE', 'H' => 'A700', 'kg' => '204', 'A' => '260.5', 'J' => '215300', 'W' => '6241'),
					array('id' => 57, 'type' => 'HE', 'H' => 'B600', 'kg' => '212', 'A' => '270', 'J' => '171000', 'W' => '5701'),
					array('id' => 58, 'type' => 'HE', 'H' => 'A800', 'kg' => '224', 'A' => '285.8', 'J' => '303400', 'W' => '7682'),
					array('id' => 59, 'type' => 'HE', 'H' => 'B650', 'kg' => '225', 'A' => '286.3', 'J' => '210600', 'W' => '6480'),
					array('id' => 60, 'type' => 'HE', 'H' => 'B700', 'kg' => '241', 'A' => '306.4', 'J' => '256900', 'W' => '7340'),
					array('id' => 61, 'type' => 'HE', 'H' => 'A900', 'kg' => '252', 'A' => '320.5', 'J' => '422100', 'W' => '9485'),
					array('id' => 62, 'type' => 'HE', 'H' => 'B800', 'kg' => '262', 'A' => '334.2', 'J' => '359100', 'W' => '8977'),
					array('id' => 63, 'type' => 'HE', 'H' => 'A1000', 'kg' => '272', 'A' => '346.8', 'J' => '553800', 'W' => '11190'),
					array('id' => 64, 'type' => 'HE', 'H' => 'B900', 'kg' => '291', 'A' => '371.3', 'J' => '494100', 'W' => '10980'),
					array('id' => 65, 'type' => 'HE', 'H' => 'B1000', 'kg' => '314', 'A' => '400', 'J' => '644700', 'W' => '12890')
			);
		}
		
		return $sections;
	}
	
	public function deck_form_full($prime_span, $deck_width, $momentum_div, $deck, $steel, $profile, $criterion)
	{
		?>
		
			<form action="" method="post" target="_self">
			<?php wp_nonce_field('steel-deck-submit', 'steel-deck-nonce-field'); ?>
				<fieldset>
				<legend><?php _e('Steel Deck', 'bimba'); ?></legend>
				
					<ul>
					
						<li><label for="side-a"><?php _e('Primary Beam Span (m)', 'bimba'); ?></label><br />
						<input id="side-a" name="side-a" type="number" step="0.01" required value="<?php echo number_format($prime_span , 2); ?>"/></li>
						
						<li><label for="side-b"><?php _e('Deck Width (m)', 'bimba'); ?></label><br />
						<input id="side-b" name="side-b" type="number" step="0.01" required value="<?php echo number_format($deck_width , 2); ?>"/></li>
						
						<li>
						<fieldset>
						<legend><?php _e('Restraint Type', 'bimba'); ?></legend>
						<input type="radio" id="restraint1" name="restraint" value="8"
								<?php if ($momentum_div == 8):
									echo ' checked="checked"'; endif; ?>/>
								<label for="restraint1"><?php _e(' Hinge', 'bimba'); ?></label>
								<input type="radio" id="restraint2" name="restraint" value="10"
								<?php if ($momentum_div == 10):
									echo ' checked="checked"'; endif; ?>/>
								<label for="restraint2"><?php _e(' Half Joint', 'bimba'); ?></label>
								<input type="radio" id="restraint3" name="restraint" value="12"
								<?php if ($momentum_div == 12):
									echo ' checked="checked"'; endif; ?>/>
								<label for="restraint3"><?php _e(' Joint', 'bimba'); ?></label>
								</fieldset>
								</li>
						
						<li>
						<fieldset>
						<legend><?php _e('Deck Type', 'bimba'); ?></legend>
						<input type="radio" id="deck1" name="deck" value="1.9"
								<?php if ($deck == 1.9):
									echo ' checked="checked"'; endif; ?>/>
								<label for="deck1"><?php _e('Wood (1.9kN)', 'bimba'); ?></label>
								<input type="radio" id="deck4" name="deck" value="2.4"
								<?php if ($deck == 2.4):
									echo ' checked="checked"'; endif; ?>/>
								<label for="deck4"><?php _e('Steel (2.4kN)', 'bimba'); ?></label>
								<input type="radio" id="deck2" name="deck" value="3.2"
								<?php if ($deck == 3.2):
									echo ' checked="checked"'; endif; ?>/>
								<label for="deck2"><?php _e('Hollow Bricks (3.2kN)', 'bimba'); ?></label>
								<input type="radio" id="deck3" name="deck" value="3.8"
								<?php if ($deck == 3.8):
									echo ' checked="checked"'; endif; ?>/>
								<label for="deck3"><?php _e('Bricks (3.8kN)', 'bimba'); ?></label>
								</fieldset>
								</li>
						
						<li>
						<fieldset>
						<legend><?php _e('Steel Type', 'bimba'); ?></legend>
						<input type="radio" id="steel1" name="steel" value="235"
								<?php if ($steel == 235):
									echo ' checked="checked"'; endif; ?>/>
								<label for="steel1"><?php _e(' Mild (235 MPa)', 'bimba'); ?></label>
								<input type="radio" id="steel2" name="steel" value="275"
								<?php if ($steel == 275):
									echo ' checked="checked"'; endif; ?>/>
								<label for="steel2"><?php _e(' Medium (275 MPa)', 'bimba'); ?></label>
								<input type="radio" id="steel3" name="steel" value="355"
								<?php if ($steel == 355):
									echo ' checked="checked"'; endif; ?>/>
								<label for="steel3"><?php _e(' Fragile (355 MPa)', 'bimba'); ?></label>
								</fieldset>
								</li>
						
						<li>
						<fieldset>
						<legend><?php _e('Exclude a Profile', 'bimba'); ?></legend>
						<input type="radio" id="profile1" name="profile" value="1" 
						<?php if ($profile == 1):
							echo ' checked="checked"'; endif; ?>/>
						<label for="profile1"><?php _e(' None', 'bimba'); ?></label>
						<input type="radio" id="profile2" name="profile" value="2"
						<?php if ($profile == 2):
							echo ' checked="checked"'; endif; ?>/>
						<label for="profile2"> IPE</label>
						<input type="radio" id="profile3" name="profile" value="3"
						<?php if ($profile == 3):
							echo ' checked="checked"'; endif; ?>/>
						<label for="profile3"> HE</label>
						</fieldset>
						</li>
						
						<li>
						<fieldset>
						<legend><?php _e('Design Criterion', 'bimba'); ?></legend>
						<input type="radio" id="criterion1" name="criterion" value="1" 
						<?php if ($criterion == 1):
							echo ' checked="checked"'; endif; ?>/>
						<label for="criterion1"><?php _e(' Minimal Weight', 'bimba'); ?></label>
						<input type="radio" id="profile2" name="criterion" value="2"
						<?php if ($criterion == 2):
							echo ' checked="checked"'; endif; ?>/>
						<label for="criterion2"><?php _e(' Minimal Height', 'bimba'); ?></label>
						</fieldset>
						</li>
					
					</ul>
					
				<input type="submit" value="Submit" name="deck-form" />
				<input type="reset" value="Reset" />
				
				</fieldset>
			</form>
		
		<?php
	}
	
	public function deck_form_void()
	{
		?>
			
			<form action="" method="post" target="_self">
			<?php wp_nonce_field('steel-deck-submit', 'steel-deck-nonce-field'); ?>
				<fieldset>
				<legend><?php _e('Steel Deck', 'bimba'); ?></legend>
				
					<ul>
					
						<li><label for="side-a"><?php _e('First Side (>=2m)*', 'bimba'); ?></label><br />
						<input id="side-a" name="side-a" type="number" step="0.01" required /></li>
						
						<li><label for="side-b"><?php _e('Second Side (>=2m)*', 'bimba'); ?></label><br />
						<input id="side-b" name="side-b" type="number" step="0.01" required /></li>
						
						<li>
						<fieldset>
						<legend><?php _e('Restraint Type', 'bimba'); ?></legend>
						<input type="radio" id="restraint1" name="restraint" value="8"
								checked="checked"/>
								<label for="restraint1"><?php _e(' Hinge', 'bimba'); ?></label>
								<input type="radio" id="restraint2" name="restraint" value="10"/>
								<label for="restraint2"><?php _e(' Half Joint', 'bimba'); ?></label>
								<input type="radio" id="restraint3" name="restraint" value="12"/>
								<label for="restraint3"><?php _e(' Joint', 'bimba'); ?></label>
								</fieldset>
								</li>
						
						<li>
						<fieldset>
						<legend><?php _e('Deck Type', 'bimba'); ?></legend>
						<input type="radio" id="deck1" name="deck" value="1.9"
								checked="checked"/>
								<label for="deck1"><?php _e('Wood (1.9kN)', 'bimba'); ?></label>
								<input type="radio" id="deck4" name="deck" value="2.4"/>
								<label for="deck4"><?php _e('Steel (2.4kN)', 'bimba'); ?></label>
								<input type="radio" id="deck2" name="deck" value="3.2"/>
								<label for="deck2"><?php _e('Hollow Bricks (3.2kN)', 'bimba'); ?></label>
								<input type="radio" id="deck3" name="deck" value="3.8"/>
								<label for="deck3"><?php _e('Bricks (3.8kN)', 'bimba'); ?></label>
								</fieldset>
								</li>
						
						<li>
						<fieldset>
						<legend><?php _e('Steel Type', 'bimba'); ?></legend>
						<input type="radio" id="steel1" name="steel" value="235"
								checked="checked"/>
								<label for="steel1"><?php _e(' Mild (235 MPa)', 'bimba'); ?></label>
								<input type="radio" id="steel2" name="steel" value="275"/>
								<label for="steel2"><?php _e(' Medium (275 MPa)', 'bimba'); ?></label>
								<input type="radio" id="steel3" name="steel" value="355"/>
								<label for="steel3"><?php _e(' Fragile (355 MPa)', 'bimba'); ?></label>
								</fieldset>
								</li>
						
						<li>
						<fieldset>
						<legend><?php _e('Exclude a Profile', 'bimba'); ?></legend>
						<input type="radio" id="profile1" name="profile" value="1" checked="checked"/>
						<label for="profile1"><?php _e(' None', 'bimba'); ?></label>
						<input type="radio" id="profile2" name="profile" value="2"/>
						<label for="profile2"> IPE</label>
						<input type="radio" id="profile3" name="profile" value="3"/>
						<label for="profile3"> HE</label>
						</fieldset>
						</li>
												
						<li>
						<fieldset>
						<legend><?php _e('Design Criterion', 'bimba'); ?></legend>
						<input type="radio" id="criterion1" name="criterion" value="1" checked="checked"/>
						<label for="criterion1"><?php _e(' Minimal Weight', 'bimba'); ?></label>
						<input type="radio" id="profile2" name="criterion" value="2"/>
						<label for="criterion2"><?php _e(' Minimal Height', 'bimba'); ?></label>
						</fieldset>
						</li>
					
					</ul>
				
				<input type="submit" value="Submit" name="deck-form" />
				<input type="reset" value="Reset" />
				
				</fieldset>
			</form>	
			
		<?php
	}
	
}

$stldck = new Steel_Deck();