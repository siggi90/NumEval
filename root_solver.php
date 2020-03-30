<?

namespace NumEval;

class root_solver {
	private $value;
	private $power;
	private $evaluation;
	
	function __construct($value, $power, $evaluation) {
		$this->value = $value;
		$this->power = $power;
		$this->evaluation = $evaluation;
	}
	
	private $previous_roots = array();
	
	
	function solve_r_square($value, $r_squared) {
		$v = $this->evaluation->root($value['value'], 2);
		$v = $this->evaluation->root_closest_result;
		$v = array('value' => $v, 'remainder' => '0/1');
		$v_squared = $this->evaluation->multiply_total($v, $v);
		
		$a = $this->evaluation->execute_divide($value, $v_squared);
		$b = $this->evaluation->subtract_total($value, $v_squared);
		
		$r_value = $this->evaluation->subtract_total($value, $r_squared);
		$r_value = $this->evaluation->execute_divide($r_value, $this->evaluation->multiply_total($v, array('value' => '2', 'remainder' => '0/1')));
		$r_value = $this->evaluation->subtract_total($r_value, $this->evaluation->execute_divide($v, 2));
		
		return $r_value;
	}
	
	
	private $original_value;
	public $continued_fraction = array();
	
	function calculate_e() {
			
	}
	
	function calculate_x($previous_e) {
		$_e = $this->evaluation->execute_divide(1, $previous_e);
		$x = $this->evaluation->subtract_total($_e, $this->calculate_e());
		return $x;	
	}
	
	function solve_root($value, $limit=30, $precision=array('value' => '0', 'remainder' => '1/100')) {
		$v = $value['value'];
		$v = $this->evaluation->root($v, $this->power);
		if(!$v) {
			$v = $this->evaluation->root_closest_result;	
		}
		$this->continued_fraction[] = $v;
		
		$v = array('value' => $v, 'remainder' => '0/1');
		
		$value = $this->evaluation->root_fraction($value, $this->power, $precision);		
		
		$counter = 0;
		while($counter < $limit) {
			$remainder = $this->evaluation->subtract_total($value, $v);
			if($remainder == array('value' => '0', 'remainder' => '0/1')) {
				return $this->continued_fraction;	
			}
			$remainder_inverse = $this->evaluation->execute_divide(1, $remainder);
			
			$this->continued_fraction[] = $remainder_inverse['value'];
			
			$period = $this->evaluation->detect_period_continued_fraction($this->continued_fraction);
			if($period) {
				return $period;	
			}
			
			$v = array('value' => $remainder_inverse['value'], 'remainder' => '0/1');
			$value = $remainder_inverse;			
			$counter++;
		}
		return $this->continued_fraction;
	}
	
	function factor_root() {
		$fraction_values = $this->evaluation->fraction_values($this->value);
		$numerator_factors = $this->evaluation->prime_factors($fraction_values[0]);
		$denominator_factors = $this->evaluation->prime_factors($fraction_values[1]);
		$factors = array();
		
		foreach($numerator_factors as $value) {
			if(!isset($factors[$value])) {
				$factors[$value] = 1;	
			} else {
				$factors[$value] = $this->evaluation->add($factors[$value], 1);	
			}
		}
		foreach($denominator_factors as $value) {
			if(!isset($factors[$value])) {
				$factors[$value] = "-1";	
			} else {
				$factors[$value] = $this->evaluation->subtract($factors[$value], 1);	
			}
		}
		$resulting_factors = array();
		foreach($factors as $key => $value) {
			if($value != 0) {
				$resulting_factors[$key] = $this->evaluation->execute_divide($value, $this->power);
			}
		}
		$result = array('value' => '1', 'remainder' => '0/1');
		foreach($resulting_factors as $key => $value) {
			$value = $this->evaluation->power(array('value' => $key, 'remainder' => '0/1'), $value);
			$result = $this->evaluation->multiply_total($result, $value);	
		}
		return $result;
	}
	
	function root_by_denominator($denominator_root) {
		$denominator = $denominator_root;
		$fraction_values = $this->evaluation->fraction_values($this->value);
		$division_value = $this->evaluation->execute_divide($fraction_values[0], $fraction_values[1]);
		$this->evaluation->root($division_value['value'], $this->power);
		$v = array('value' => $this->evaluation->root_closest_result, 'remainder' => '0/1');
		$v_squared = $this->evaluation->execute_power_whole($v, $this->power);		
		$remainder = $this->evaluation->subtract_total($division_value, $v_squared);
				
		$vd = $this->evaluation->multiply_total($v, $denominator);
		$vd_squared = $this->evaluation->multiply_total($vd, $vd);
				$denominator_squared = $this->evaluation->execute_power_whole($denominator, $this->power);		
		$rd_squared = $this->evaluation->multiply_total($division_value, $denominator_squared);
		$rd_root = $this->evaluation->execute_power($rd_squared, $this->power);
		
		$numerator = $this->evaluation->subtract_total($rd_root, $vd);
		$result_division = $this->evaluation->execute_divide($numerator, $denominator);
		$result = $this->evaluation->add_total($v, $result_division);
		return $result;
	}
	
	function square_root_by_denominator($denominator_root) {
		$denominator = $denominator_root;
		$fraction_values = $this->evaluation->fraction_values($this->value);
		$division_value = $this->evaluation->execute_divide($fraction_values[0], $fraction_values[1]);
		$this->evaluation->root($division_value['value'], 2);
		$v = array('value' => $this->evaluation->root_closest_result, 'remainder' => '0/1');
		$v_squared = $this->evaluation->multiply_total($v, $v);
		
		$remainder = $this->evaluation->subtract_total($division_value, $v_squared);
				
		$vd = $this->evaluation->multiply_total($v, $denominator);
		$vd_squared = $this->evaluation->multiply_total($vd, $vd);
				$denominator_squared = $this->evaluation->multiply_total($denominator, $denominator);
		$rd_squared = $this->evaluation->multiply_total($remainder, $denominator_squared);
				$rd_squared = $this->evaluation->add_total($rd_squared, $vd_squared);
		$rd_root = $this->evaluation->execute_power($rd_squared, 2);
				
		$numerator = $this->evaluation->subtract_total($rd_root, $vd);
		$result_division = $this->evaluation->execute_divide($numerator, $denominator);
		$result = $this->evaluation->add_total($v, $result_division);
		return $result;
	}
	
	function solve($known_root) {
		$rb = $known_root;
		$fraction_values = $this->evaluation->fraction_values($this->value);
		
		$k = $fraction_values[0];
		$m = $fraction_values[1];
		
		$k_unaltered = $k;
		$m_unaltered = $m;
		
		$k = $this->evaluation->result($k, $m);
		$m = $this->evaluation->result($m, $m);
		$km = array('value' => $this->evaluation->result($k, $m), 'remainder' => '0/1');
		
		
		$rb_squared = $this->evaluation->execute_power_whole($rb, 2);
		$z = $this->evaluation->multiply_total($rb_squared, array('value' => $k, 'remainder' => '0/1'));
		$round = $this->evaluation->round($z);
		$subtraction = $this->evaluation->subtract_total($z, array('value' => $round, 'remainder' => '0/1'));
		if($this->evaluation->larger_total($subtraction, array('value' => '0', 'remainder' => '1/100'))) {
			return false;	
		}
		$z = $round;
		$z = $this->evaluation->root($z, 2);
		if(!$z) {
			return false;	
		}
		$z = array('value' => $z, 'remainder' => '0/1');
		
		$x = $this->evaluation->execute_divide($k, $m);
		
		$m = array('value' => $m, 'remainder' => '0/1');
		$m_root = array('value' => $m_unaltered, 'remainder' => '0/1');
		
		$mb = $this->evaluation->multiply_total($m, $z);
		$mb = $this->evaluation->multiply_total($mb, $m_root);
		
		$kmb = $this->evaluation->multiply_total($km, $rb);
		
		return $this->evaluation->execute_divide($kmb, $mb);
	}
	
	
	function approximate_value() {
		$fraction_values = $this->evaluation->fraction_values($this->value);
		$k = $fraction_values[0];
		$m = $fraction_values[1];
		if($this->power > 2) {
			$_k = $this->evaluation->result_multiple(array($k, $this->evaluation->execute_power_whole($m, $this->power-1)['value']));
			$_m = $this->evaluation->result_multiple(array($m, $this->evaluation->execute_power_whole($m, $this->power-1)['value']));
			
			$k_root = $this->evaluation->root($_k, $this->power);
			if(!$k_root) {
				$k_root = $this->evaluation->root_closest_result;	
			}
			
			return $this->evaluation->execute_divide($k_root, $m);
			
		}
		
		$k_unaltered = $k;
		$m_unaltered = $m;
		
		$k = $this->evaluation->result($k, $m);
		$m = $this->evaluation->result($m, $m);
		
		$x = $this->evaluation->execute_divide($k, $m);
		
		$m_squared_1 = $this->evaluation->result($m, $m);
		
		$km_1 = $this->evaluation->result($k, $m);
		$km = $this->evaluation->result($km_1, $k);
		$km_root = $this->evaluation->result($m, $k_unaltered);
		
		$m_squared = $this->evaluation->execute_divide($this->evaluation->result($m, $km), $k);
		
		$k_squared = $this->evaluation->result($km, $km);
		$k_squared = $this->evaluation->execute_divide($k_squared, $m_squared);	
		
		
		$m_b = $km;		$m_root_b = $km_root;		$k_b = $k_squared;		$m_b_2 = $km;
		$m_root_b_2 = $km_root;
		$k_b_2 = $k_squared;
		
		$m_b_squared = $this->evaluation->result($m_b, $m_b);
		
		$rb_squared = $this->evaluation->execute_divide($m_b_squared, $m_squared);
		$ra_squared = $this->evaluation->execute_divide($m_squared, $m_squared_1);
		
		$rb_squared_rational_root = $this->evaluation->root($rb_squared['value'], 2);
		$rb = $this->evaluation->root_closest_result;
		
		
		
		
		$m_a = $this->evaluation->execute_divide($m_b, $rb);
		$k_di_m = $this->evaluation->execute_divide(array('value' => $km_root, 'remainder' => '0/1'), $m_a);
		
		
		
		return $k_di_m;		
	}
		
}


?>