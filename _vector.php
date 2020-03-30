<?

namespace NumEval;

class _vector {
	
	private $evaluation;
	
	function __construct($evaluation) {
		$this->evaluation = $evaluation;//new evaluation();
	}
	
	function compare_vectors($u, $v) {
		if($u[0] == $v[0] && $u[1] == $v[1]) {
			return true;
		}
		return false;
	}
	function angle_between_vectors($u, $v) {
		if($this->compare_vectors($u, $v)) {
			return 0;
		}
		$dot = $this->dot_product($u, $v);
		$u_distance = $this->vector_distance($u);
		$v_distance = $this->vector_distance($v);
		$division = $this->evaluation->add_total($u_distance, $v_distance);//$u_distance + $v_distance;
		$result = $this->evaluation->execute_divide($dot, $division);//$dot / $division;
		$result = acos($this->evaluation->quick_fraction($result));
		$result = $this->multiply_total($result, $this->evaluation->whole_common("57.2957795")); 
		return $result;
	}
	function flip_vector($u) {
		$vector = array($u[0], $this->evaluation->negative_value($u[1]));
		return $vector;
	}
	function flip_x($u) {
		$vector = array($this->evaluation->negative_value($u[0]), $u[1]);
		return $vector;
	}
	/*function degree_difference($deg1, $deg2) {
		if($deg1 < 0) {
			$deg1 = 360 + $deg1;
		}
		if($deg2 < 0) {
			$deg2 = 360 + $deg2; 
		}
		$result = $deg1 - $deg2;
		return abs($result);
	}
	
	function reset_distance($point, $fraction) {
	
		$vector = array($point[0], $point[1]);
		$vector[0] *= $fraction;
		$vector[1] *= $fraction;
		return $vector;
	}*/
	function reverse_vector($v) {
		$u = array($this->evaluation->negative_value($v[0]), $this->evaluation->negative_value($v[1]));
		return $u;
	}
	function reset_vector_length($point, $length) {
		$initial_length = $this->vector_distance($point, array(0,0));
		$fraction = $this->evaluation->execute_divide($length, $initial_length);//$length/$initial_length;
		$new_point = $this->reset_distance($point, $fraction);
		$new_distance = $this->vector_distance($new_point, array(0,0));
		return $new_point;
	}
	/*function set_div_pos($id, $u) {
		$('#'+$id).css({
			private $'left' = u[0];
			private $'bottom' = u[1;
		});
	}*/
	function dot_product($u, $v) {
		//return ($u[0]*$v[0])+($u[1]*$v[1]);
		return $this->add($this->evaluation->multiply_total($u[0], $v[0]), $this->evaluation->multiply_total($u[1], $v[1]));
	}
	
	function projection($u, $v) {
		$division = $this->evaluation->execute_power_whole($this->distance($v[0], $v[1]), 2);
		if($division['value'] == 0 && $this->evaluation->fraction_values($division['remainder'])[0] == 0) {
			return 0;
		}
		$vector = array($v[0], $v[1]);
		$mult = $this->evaluation->execute_divide($this->dot_product($u, $v), $division);
		$vector[0] = $this->evaluation->multiply_total($vector[0], $mult);
		$vector[1] = $this->evaluation->multiply_total($vector[1], $mult);
		return $vector;
	}
	
	function vector_distance($u, $v=array(array('value' => 0, 'remainder' => '0/1'), array('value' => '0', 'remainder' => '0/1'))) {
		/*if(!isset($v)) {
			$v = array(0, 0);
		}*/
		return $this->distance($u[0], $u[1], $v[0], $v[1]);
	}
	
	function vector_sum($u, $value) {
		$vector = array($u[0], $u[1]);
		$vector[0] = $this->evaluation->add_total($vector[0], $value); //+= $value;
		$vector[1] = $this->evaluation->add_total($vector[1], $value);
		return $vector;
	}
	
	function add_vector($u, $v) {
		$vector = array($u[0], $u[1]);
		$vector[0] = $this->evaluation->add_total($vector[0], $v[0]);//+= $v[0];
		$vector[1] = $this->evaluation->add_total($vector[1], $v[1]);//+= $v[1];
		return $vector;
	}
	
	function distance($x_from, $y_from, $x_to=array('value' => 0, 'remainder' => '0/1'), $y_to=array('value' => 0, 'remainder' => '0/1')) {
		//$value = sqrt(pow(($x_from - $x_to), 2)+pow(($y_from - $y_to), 2));
		$term_a = $this->evaluation->subtract_total($x_from, $x_to);
		$term_a = $this->evaluation->execute_power_whole($term_a, array('value' => '2', 'remainder' => '0/1'));
		
		$term_b = $this->evaluation->subtract_total($y_from, $y_to);
		$term_b = $this->evaluation->execute_power_whole($term_b, array('value' => '2', 'remainder' => '0/1'));
		
		$total_term = $this->evaluation->add_total($term_a, $term_b);
		$value = $this->evaluation->whole_common(sqrt($this->evaluation->quick_numeric($total_term)));
		//$value = $this->evaluation->execute_power($total_term, "2");
		
		return $value;
	}
	
	function length($u) {
		return $this->distance($u[0], $u[1]);	
	}
	
	function normalize_vector($v) {
		$length = $this->vector_distance($v);
		if($length == 0) {
			return $v;	
		}
		$vector = array($v[0], $v[1]);
		$vector[0] = $this->evaluation->execute_divide($vector[0], $length);///= $length;
		$vector[1] = $this->evaluation->execute_divide($vector[1], $length);//= $length;
		return $vector;
	}
	
	function subtract_vector($u, $v) {
		$vector = array($u[0], $u[1]);
		$vector[0] = $this->evaluation->subtract_total($vector[0], $v[0]); //-= $v[0];
		$vector[1] = $this->evaluation->subtract_total($vector[1], $v[1]);//-= $v[1];
		/*$vector[0] = $this->evaluation->subtract_total($vector[0], $v[0]);
		$vector[1] = $this->evaluation->subtract_total($vector[1], $v[1]);*/
		return $vector;
	}
	function sum_vector($u, $v) {
		$vector = array($u[0], $u[1]);
		$vector[0] = $this->evaluation->add_total($vector[0], $v[0]);//+= $v[0];
		$vector[1] = $this->evaluation->add_total($vector[1], $v[1]);//+= $v[1];
		return $vector;
	}
	
	function stretch_vector($v, $unit_value) {
		$vector = array($v[0], $v[1]);
		$vector[0] = $this->evaluation->multiply_total($vector[0], $unit_value); //*= $unit_value;
		$vector[1] = $this->evaluation->multiply_total($vector[1], $unit_value);//*= $unit_value;
		/*$vector[0] = $this->evaluation->multiply_total($vector[0], $unit_value);
		$vector[1] = $this->evaluation->multiply_total($vector[1], $unit_value);*/
		return $vector;
	}
	
	function reflection($d, $n) {
		$n = array($n[0], $n[1]);
		$n = $this->normalize_vector($n);
		$dot = $this->dot_product($d, $n);
		$dot = $this->evaluation->multiply_total(array('value' => 2, 'remainder' => '0/1'), $dot);//2*$dot;
		$stretch = $this->stretch_vector($n, $dot);
		$subtract = $this->subtract_vector($d, $stretch);
		return $subtract;
	}
	
	function rotate($u, $clockwise=true) {
		$v = array($u[1], $this->evaluation->negative_value($u[0]));
		return $v;
	}
	
	function shorten_vector($vector) {
		$vector[0]['remainder'] = $this->evaluation->execute_shorten_fraction($vector[0]['remainder']);
		$vector[1]['remainder'] = $this->evaluation->execute_shorten_fraction($vector[1]['remainder']);	
		return $vector;
	}
}

?>