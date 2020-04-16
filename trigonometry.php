<?

namespace NumEval;

class trigonometry {
	
	private $evaluation;
	private $vector;
	
	function __construct($evaluation=NULL) {
		if($evaluation != NULL) {
			$this->evaluation = $evaluation;		
		} else {
			$this->evaluation = new evaluation();	
		}
		$this->vector = new _vector($this->evaluation);
	}
	
	private $accuracy;
	private $point;
	private $radius;
	
	private $cos;
	private $sin;
	private $cot;
	private $tan;
	private $csc;
	private $sec;
	private $excsc;
	private $cvs;
	private $versine;
		
	function point($slope) {
		$slope_vector = $slope;
		$this->point = $slope;
		
		if($this->evaluation->negative($this->point[0])) {
			$this->negative_values['cos'] = true;
			$this->negative_values['sec'] = true;
		}
		if($this->evaluation->negative($this->point[1])) {
			$this->negative_values['sin'] = true;
			$this->negative_values['csc'] = true;
		}
		if(!$this->evaluation->negative($this->point[0]) && $this->evaluation->negative($this->point[1])) {
			$this->negative_values['cot'] = true;
			$this->negative_values['tan'] = true;
		} else if($this->evaluation->negative($this->point[0]) && !$this->evaluation->negative($this->point[1])) {
			$this->negative_values['cot'] = true;
			$this->negative_values['tan'] = true;
		}
		$slope[0] = $this->evaluation->absolute($slope[0]);
		$slope[1] = $this->evaluation->absolute($slope[1]);
		$slope_vector = $slope;
		$this->point = $slope;
		if($slope_vector[0]['value'] == 0 && $this->evaluation->fraction_values($slope_vector[0]['remainder'])[0] == 0) {
			$result = array(
				'cos' => array('value' => 0, 'remainder' => '0/1'),
				'sin' => array('value' => 1, 'remainder' => '0/1'),
				'cot' => array('value' => 0, 'remainder' => '0/1'),
				'tan' => 'NULL',
				'csc' => array('value' => 1, 'remainder' => '0/1'),
				'sec' => 'NULL',
				'versine' => array('value' => '1', 'remainder' => '0/1'),
				'crd' => 'NULL',
				'excsc' => 'NULL',
				'cvs' => 'NULL',
				'exsec' => 'NULL'
			);
			foreach($this->negative_values as $negative_index => $negative_value) {
				$result[$negative_index] = $this->evaluation->negative_value($result[$negative_index]);	
			}
			return $result;
		} else if($slope_vector[1]['value'] == 0 && $this->evaluation->fraction_values($slope_vector[1]['remainder'])[0] == 0) {
			$result = array(
				'cos' => array('value' => 1, 'remainder' => '0/1'),
				'sin' => array('value' => 0, 'remainder' => '0/1'),
				'cot' => 'NULL',
				'tan' => array('value' => 0, 'remainder' => '0/1'),
				'csc' => 'NULL',
				'sec' => array('value' => 1, 'remainder' => '0/1'),
				'versine' => array('value' => '0', 'remainder' => '0/1'),
				'crd' => 'NULL',
				'excsc' => 'NULL',
				'cvs' => 'NULL',
				'exsec' => 'NULL'
			);
			foreach($this->negative_values as $negative_index => $negative_value) {
				$result[$negative_index] = $this->evaluation->negative_value($result[$negative_index]);	
			}
			return $result;
		} else {
			$power = array('value' => '2', 'remainder' => '0/1');
			
			
			$denominator = $this->evaluation->add_total($this->evaluation->execute_power_whole($slope_vector[0], $power), $this->evaluation->execute_power_whole($slope_vector[1], $power));
			
			$length = $denominator;
			
			$length = $this->evaluation->execute_power($length, 2, true);			
			$length = $this->evaluation->execute_divide(array('value' => '1', 'remainder' => '0/1'), $length);
			$point = $this->vector->stretch_vector($slope_vector, $length);
			
			$this->point = $point;
			
			
			if($this->evaluation->truncate_fractions_length > 0) {
				$this->point[0]['remainder'] = $this->evaluation->execute_shorten_fraction($this->point[0]['remainder']);
				$this->point[1]['remainder'] = $this->evaluation->execute_shorten_fraction($this->point[1]['remainder']);	
			}
			return $this->outer_transformation();
		}
	}
	
	private $negative_values = array();
	
	function polar_input($value) {
		$middle_value = (pi()/4);
		if($value > $middle_value) {
			$value = $value - $middle_value;
			$proportion = $value / $middle_value;
		} else {
			$proportion = $value / $middle_value;
		}
	}
			
	function polar($value) {
		$radian = atan($value[1]/$value[0]);
		return $radian;
	}
	
	function radian($value) {
		
		$x = $this->cosine($value);
		$y = $this->sine($value);
		
		return $this->point(array($x, $y));
	}
	
	function slope($value) {
		$split = explode("/", $value);
		return $this->point(array($split[0], $split[1]));	
	}
	
	public $cot_precise = false;
	public $crd_precise = false;
	
	function outer_transformation() {
		$vectors = array(
			$this->point,
			array($this->point[0], array('value' => 0, 'remainder' => '0/1')),
			array(array('value' => 0, 'remainder' => '0/1'), $this->point[1])
		);
		$rotated_vectors = array();
		foreach($vectors as $vector) {
			$rotated_vector = $this->vector->rotate($vector);
			$rotated_vectors[] = $rotated_vector;	
		}
		
		
		$top_placement = $rotated_vectors[0];
		$translation = array('value' => 1, 'remainder' => '0/1');
		if($top_placement[1] == $this->point[1]) {
			
		} else if($this->evaluation->larger_total($this->point[1], $top_placement[1])) {
			$translation = $this->evaluation->execute_divide($this->point[1], $top_placement[1], false, false, false, true);		
		} else if($this->evaluation->larger_total($top_placement[1], $this->point[1])) {
			$translation = $this->evaluation->execute_divide($this->point[1], $top_placement[1], false, false, false, true);		
		}
		
		$top_placement = $this->vector->stretch_vector($top_placement, $this->evaluation->absolute($translation));
		
		$tan = $top_placement;
		
		$this->coordinates_print[] = array($top_placement, 'blue');
		$baseline = $this->vector->reverse_vector($top_placement);
		
		$baseline = $this->vector->add_vector($this->point, $baseline);			
	
		$left_end = $this->vector->subtract_vector($this->point, $baseline);		
		$start_left_end = $this->vector->add_vector($this->point, $left_end);
		$start_point = $this->vector->subtract_vector($start_left_end, $this->point);
		
		
		
		
		$translation = 1;
		$translation_method = 2;
		
		$translation_value = $this->point[0];
			
		$absolute_value = $this->evaluation->absolute($start_point[0]);
		$translation = $this->evaluation->execute_divide($absolute_value, $translation_value, false, false, false, true);		
	
		$translation_method = 1;
				
		$translation = $this->evaluation->execute_divide(1, $translation, false, false, false, true);
		$start_point_unaltered = $start_point;
		$start_point = $this->vector->stretch_vector($start_point, $this->evaluation->absolute($translation));
		
		$start_point_csc = $start_point;
		$start_point_csc[0] = $this->evaluation->absolute($start_point_csc[0]);
		$start_point_csc[1] = $this->evaluation->absolute($start_point_csc[1]);		
		$cot_pos_csc = $this->vector->add_vector($this->point, $start_point_csc);
		
		
		$cot_pos = $this->vector->add_vector($this->point, $start_point);
		
		
		$this->coordinates_print[] = array($cot_pos, 'blue');
		$this->coordinates_print[] = array($this->point, 'red');
		
		$cot = $this->vector->subtract_vector($cot_pos, $this->point);
		
		
		$cot[0] = $this->evaluation->absolute($cot[0]);
		$cot[1] = $this->evaluation->absolute($cot[1]);
				
		if($this->evaluation->truncate_fractions_length > 0) {
			$cot[0]['remainder'] = $this->evaluation->execute_shorten_fraction($cot[0]['remainder']);
			$cot[1]['remainder'] = $this->evaluation->execute_shorten_fraction($cot[1]['remainder']);
		}
		$this->cot = $this->vector->length($cot, $this->cot_precise);
		
		
		$sec = $this->vector->add_vector($this->point, $tan);
		
		$this->coordinates_print[] = array($tan, 'green');
		$this->coordinates_print[] = array($sec, 'green');
		$this->sec = $sec[0];
		
		
		
		$this->csc = $cot_pos_csc[1];		
		$this->cos = $this->point[0];
		$this->sin = $this->point[1];
		$this->tan = $this->evaluation->execute_divide($this->sin, $this->cos, false, false, false, true);
		$this->versine = $this->evaluation->subtract_total(array('value' => '1', 'remainder' => '0/1'), $this->cos);
		
		$crd_baseline = array($this->evaluation->add_total($this->cos, $this->versine), array('value' => '0', 'remainder' => '0/1'));
		$crd = $this->vector->subtract_vector($crd_baseline, $this->point);
		if($this->evaluation->truncate_fractions_length > 0) {
			$crd[0]['remainder'] = $this->evaluation->execute_shorten_fraction($crd[0]['remainder']);
			$crd[1]['remainder'] = $this->evaluation->execute_shorten_fraction($crd[1]['remainder']);
		}
		$crd = $this->vector->length($crd, $this->crd_precise);
		
		$excsc = $this->evaluation->subtract_total($this->csc, array('value' => '1', 'remainder' => '0/1'));
		$cvs = $this->evaluation->subtract_total(array('value' => '1', 'remainder' => '0/1'), $this->sin);
		$this->excsc = $excsc;
		$this->cvs = $cvs;
		
		$exsec = $this->evaluation->subtract_total($this->sec, $this->evaluation->add_total($this->cos, $this->versine));
		
		$result = array(
			'cos' => $this->cos,
			'sin' => $this->sin,
			'cot' => $this->cot,
			'tan' => $this->tan,
			'csc' => $this->csc,
			'sec' => $this->sec,
			'versine' => $this->versine,
			'crd' => $crd,
			'excsc' => $this->excsc,
			'cvs' => $cvs,
			'exsec' => $exsec
		);
		foreach($result as $index => $value) {
			$result[$index] = $this->evaluation->absolute($result[$index]);	
		}
		foreach($this->negative_values as $negative_index => $negative_value) {
			$result[$negative_index] = $this->evaluation->negative_value($result[$negative_index]);	
		}
		return $result;
	}
	
	private $coordinates_print = array();
	private $colors = array(
		'red',
		'blue',
		'green',
		'brown',
		'black',
		'red',
		'blue',
		'green'
	);
	
	
	function print_coordinates() {
		$multiplier = 150;
		foreach($this->coordinates_print as $key => $coordinate) {
			$color = $coordinate[1];			
			$cord = array();
			$cord[0] = $this->evaluation->quick_numeric($coordinate[0][0]);
			$cord[1] = $this->evaluation->quick_numeric($coordinate[0][1]);
			$left = $multiplier*$cord[0];
			$bottom = $multiplier*$cord[1];
		}
	}
	
	function set_sine_precision($sine_precision) {
		$this->sine_precision = $sine_precision;	
	}
	
	public $sine_precision = 10;
	
	function sine($x, $precision=NULL) {
		if($precision === NULL) {
			$precision = $this->sine_precision;	
		}
		$result = $x;		
		$counter = 3;
		while($counter < $precision) {
			$power_term = $this->evaluation->execute_power_whole($x, $counter);
			$factorial_term = $this->evaluation->factorial($counter);	
			$addition = $this->evaluation->execute_divide($power_term, $factorial_term);
			$result = $this->evaluation->subtract_total($result, $addition);
			$counter += 2;
			$power_term = $this->evaluation->execute_power_whole($x, $counter);
			$factorial_term = $this->evaluation->factorial($counter);	
			$addition = $this->evaluation->execute_divide($power_term, $factorial_term);
			$result = $this->evaluation->add_total($result, $addition);
			$counter += 2;
			if($this->evaluation->truncate_fractions_length > 0) {
				$result['remainder'] = $this->evaluation->execute_shorten_fraction($result['remainder']);	
			}
		}
		return $result;
	}
	
	function cosine($x, $precision=NULL) {
		if($precision === NULL) {
			$precision = $this->sine_precision;	
		}
		$result = array('value' => 1, 'remainder' => '0/1');
		$counter = 2;
		while($counter < $precision) {
			$power_term = $this->evaluation->execute_power_whole($x, $counter);
			$factorial_term = $this->evaluation->factorial($counter);	
			$addition = $this->evaluation->execute_divide($power_term, $factorial_term);
			$result = $this->evaluation->subtract_total($result, $addition);
			$counter += 2;
			$power_term = $this->evaluation->execute_power_whole($x, $counter);
			$factorial_term = $this->evaluation->factorial($counter);	
			$addition = $this->evaluation->execute_divide($power_term, $factorial_term);
			$result = $this->evaluation->add_total($result, $addition);
			$counter += 2;
			if($this->evaluation->truncate_fractions_length > 0) {
				$result['remainder'] = $this->evaluation->execute_shorten_fraction($result['remainder']);	
			}
		}
		return $result;
	}
	
	function angle($a, $b) {
		$dot_product = $this->vector->dot_product($a, $b);
		$length_a = $this->vector->length($a, true);
		$length_b = $this->vector->length($b, true);
		$denominator = $this->evaluation->multiply_total($length_a, $length_b);
		$result = $this->evaluation->execute_divide($dot_product, $denominator, true);
		return $result;
	}
	
	function cosine_vector($a) {
		return $this->angle($a, array(array('value' => '1', 'remainder' => '0/1'), array('value' => '0', 'remainder' => '0/1')));	
	}
	
	/*function sine_vector($a, $precision=NULL) {
		$a_store = $a[0];
		$a[0] = $a[1];
		$a[1] = $a_store;
		//$a = $this->vector->rotate($a);
		return $this->cosine_vector($a, $precision);
	}*/
	
	function arctan($x, $precision=NULL) {
		if($precision === NULL) {
			$precision = $this->sine_precision;	
		}
		$result = $x;		
		$counter = 3;
		while($counter < $precision) {
			$power_term = $this->evaluation->execute_power_whole($x, $counter);
			$factorial_term = $counter;			
			$addition = $this->evaluation->execute_divide($power_term, $factorial_term);
			$result = $this->evaluation->subtract_total($result, $addition);
			$counter += 2;
			$power_term = $this->evaluation->execute_power_whole($x, $counter);
			$factorial_term = $counter;			
			$addition = $this->evaluation->execute_divide($power_term, $factorial_term);
			$result = $this->evaluation->add_total($result, $addition);
			$counter += 2;
			if($this->evaluation->truncate_fractions_length > 0) {
				$result['remainder'] = $this->evaluation->execute_shorten_fraction($result['remainder']);	
			}
		}
		return $result;
	}
	
	function compute_pi($precision) {
		$arctan_value_a = $this->arctan(array('value' => '0', 'remainder' => '1/7'), $precision);
		$arctan_value_a = $this->evaluation->multiply_total($arctan_value_a, array('value' => '20', 'remainder' => '0/1'));
		$arctan_value_b = $this->arctan(array('value' => '0', 'remainder' => '3/79'), $precision);	
		$arctan_value_b = $this->evaluation->multiply_total($arctan_value_b, array('value' => '8', 'remainder' => '0/1'));
		$result = $this->evaluation->add_total($arctan_value_a, $arctan_value_b);
		return $result;
	}
	
	function arccot($x, $precision=NULL) {
		if($precision === NULL) {
			$precision = $this->sine_precision;	
		}
		$arctan = $this->arctan($x, $precision);
		$pi_value = $this->evaluation->execute_divide($this->evaluation->pi(), 2);
		$subtraction = $this->evaluation->subtract_total($pi_value, $arctan);
		return $subtraction;
	}
	
	function arccos($x, $precision=NULL) {
		if($precision === NULL) {
			$precision = $this->sine_precision;	
		}
		$arcsin = $this->arcsin($x, $precision);
		$pi_value = $this->evaluation->execute_divide($this->evaluation->pi(), 2);
		$subtraction = $this->evaluation->subtract_total($pi_value, $arcsin);
		return $subtraction;
	}
	
	function arcsin($x, $precision=NULL) {
		if($this->evaluation->larger_total($x, array('value' => '1', 'remainder' => '0/1'), false)) {
			return false;	
		}
		if($precision === NULL) {
			$precision = $this->sine_precision;	
		}
		$denominator_value_a = $this->evaluation->subtract_total(array('value' => '1', 'remainder' => '0/1'), $x);
		$denominator_value_b = $this->evaluation->add_total(array('value' => '1', 'remainder' => '0/1'), $x);
		$denominator_value_a = $this->evaluation->execute_power($denominator_value_a, 2, true);
		$denominator_value_b = $this->evaluation->execute_power($denominator_value_b, 2, true);
		$denominator = $this->evaluation->multiply_total($denominator_value_a, $denominator_value_b);
		if($this->evaluation->equals_zero($denominator)) {
			return $this->evaluation->execute_divide($this->evaluation->pi(), "2");	
		}
		$input_value = $this->evaluation->execute_divide($x, $denominator);
		if($this->evaluation->truncate_fractions_length > 0) {
			$input_value['remainder'] = $this->evaluation->execute_shorten_fraction($input_value['remainder']);	
		}
		$atan = $this->arctan($input_value, $precision);
		return $atan;	
	}
	
	function compute_pi_alt_sub($precision=12, $inner_value=NULL) {
		if($inner_value === NULL) {
			$inner_value = array('value' => '2', 'remainder' => '0/1');	
		} else {
			$inner_value = $this->evaluation->add_total(array('value' => '2', 'remainder' => '0/1'), $inner_value);	
		}
		$root = $this->evaluation->execute_power($inner_value, "2", true);
		
		$value = $this->evaluation->execute_divide($root, "2");
		if($precision == "0") {
			return $value;
		}
		if($this->evaluation->truncate_fractions_length > 0) {
			$value['remainder'] = $this->evaluation->execute_shorten_fraction($value['remainder']);	
		}
		return $this->evaluation->multiply_total($value, $this->compute_pi_alt_sub($this->evaluation->subtract($precision, "1"), $root));	
	}
	
	function compute_pi_alt($precision=12) {
		$result = $this->compute_pi_alt_sub($precision, NULL);
		$result = $this->evaluation->execute_divide($result, array('value' => '2', 'remainder' => '0/1'));
		$result = $this->evaluation->execute_divide("1", $result);
		return $result;	
	}
	
	/*function calculate_pi($precision=NULL) {
		if($precision === NULL) {
			$precision = $this->sine_precision;	
		}
		
		$x = array('value' => '1', 'remainder' => '0/1');
		$arcsin_b = $this->arcsin($x, $precision);
		$value_b = $this->evaluation->execute_divide($arcsin_b, "2");
		$result = $value_b;
		$result = $this->evaluation->multiply_total($result, array('value' => '4', 'remainder' => '0/1'));
		return $result;
	}*/
}

?>