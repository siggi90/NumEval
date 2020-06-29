<?
/*
	This class is only useful if you know the values km and log(k)*log(m) where k and m are non-trivial integer factors.
	This class uses php's built in pow function instead of NumEval's power function because NumEval is slow when numerators and denominators of fractional powers are large.
	Recommended configuration for this class is something like:
	$this->evaluation->set_configuration(10, "12", array('value' => '0', 'remainder' => '1/100'), false, "12", "12", true);
*/
class prime_factorization_n {
	
	private $evaluation;
	private $km;
	private $log_km;
	private $log_km_2;
	private $log_k_log_m;
	private $ab;
	
	function __construct($evaluation, $value, $log_k_log_m) {
		$this->evaluation = $evaluation;
		$this->km = $value;
		$this->log_km = $this->evaluation->whole_common(log($this->km));
		
		$this->log_km_2 = $this->evaluation->multiply_total($this->log_km, $this->log_km);
		$this->log_k_log_m = $log_k_log_m;
		$this->ab = $this->evaluation->execute_divide($this->log_km_2, $this->log_k_log_m);
		
		if($this->evaluation->truncate_fractions_length > 0) {
			$this->ab['remainder'] = $this->evaluation->execute_shorten_fraction($this->ab['remainder']);
		}
		if($this->evaluation->truncate_fractions_length > 0) {
			$this->log_km_2['remainder'] = $this->evaluation->execute_shorten_fraction($this->log_km_2['remainder']);
		}
		if($this->evaluation->truncate_fractions_length > 0) {
			$this->log_k_log_m['remainder'] = $this->evaluation->execute_shorten_fraction($this->log_k_log_m['remainder']);
		}
		if($this->evaluation->truncate_fractions_length > 0) {
			$this->log_km['remainder'] = $this->evaluation->execute_shorten_fraction($this->log_km['remainder']);
		}
	}
	
	function find_k() {
		$ab_value = $this->evaluation->subtract_total($this->ab, array('value' => '2', 'remainder' => '0/1'));
		$ab_value = $this->evaluation->multiply_total($ab_value, $this->log_km);
		$e_value = $this->evaluation->whole_common(exp($this->evaluation->quick_numeric($ab_value)));
		
		
		$inverse_value = $this->evaluation->whole_common(pow($this->evaluation->quick_numeric($e_value), $this->evaluation->quick_numeric($this->evaluation->execute_divide("1", $this->ab))));
		
		$inverse_km = $this->evaluation->whole_common(pow($this->km,  $this->evaluation->quick_numeric($this->evaluation->execute_divide("1", $this->ab))));
		
		
		$value = $this->evaluation->multiply_total($inverse_value, $inverse_km);
		$value = $this->evaluation->whole_common(pow($this->evaluation->quick_numeric($value), $this->evaluation->quick_numeric($this->ab)));
		
		$value = $this->evaluation->execute_divide($value, $this->evaluation->result($this->km, $this->km));
		$value = $this->evaluation->execute_divide($value, $this->km);
		
		
		if($this->evaluation->truncate_fractions_length > 0) {
			$value['remainder'] = $this->evaluation->execute_shorten_fraction($value['remainder']);
		}
		
		$log_2_k_log_2_m = $this->evaluation->subtract_total($this->log_km_2, $this->evaluation->multiply_total($this->log_k_log_m, array('value' => '2', 'remainder' => '0/1')));
		
		$log_k_log_m_squared = $this->evaluation->multiply_total($this->log_k_log_m, $this->log_k_log_m);
		
		$a_squared_b_squared = $this->evaluation->execute_divide($log_2_k_log_2_m, $log_k_log_m_squared);
		$a_squared_b_squared = $this->evaluation->multiply_total($a_squared_b_squared, $this->log_km_2);
		
		if($this->evaluation->truncate_fractions_length > 0) {
			$a_squared_b_squared['remainder'] = $this->evaluation->execute_shorten_fraction($a_squared_b_squared['remainder']);
		}
		
		$a_subtract_b = $this->evaluation->subtract_total($a_squared_b_squared, $this->evaluation->multiply_total($this->ab, array('value' => '2', 'remainder' => '0/1')));
		
		
		
		$a_subtract_b = $this->evaluation->execute_power($a_subtract_b, 2);
		
		
		$value = $this->evaluation->whole_common(pow($this->evaluation->quick_numeric($value), $this->evaluation->quick_numeric($this->evaluation->execute_divide("1", $a_subtract_b))));
		
		if($this->evaluation->truncate_fractions_length > 0) {
			$value['remainder'] = $this->evaluation->execute_shorten_fraction($value['remainder']);
		}
		
		
		
		$k_squared = $this->evaluation->multiply_total($value, $this->evaluation->whole_common($this->km));
		$k = $this->evaluation->execute_power($k_squared, 2);
		
		
		$km = $this->km;
		
		$k_guess = $this->evaluation->round($k);
		$k_guess_add = $this->evaluation->add($k_guess, "1");
		$division = array('value' => '0', 'remainder' => '1/2');
		while($this->evaluation->fraction_values($division['remainder'])[0] != 0) {
			$division = $this->evaluation->execute_divide($km, $k_guess);
			if($this->evaluation->fraction_values($division['remainder'])[0] == 0) {
				return array($k_guess, $division['value']);	
			}
			$division = $this->evaluation->execute_divide($km, $k_guess_add);
			if($this->evaluation->fraction_values($division['remainder'])[0] == 0) {
				return array($k_guess_add, $division['value']);	
			}
			$k_guess_add = $this->evaluation->add($k_guess, "1");
			$k_guess = $this->evaluation->subtract($k_guess, "1");
		}
		
		return NULL;
	}
	
}

?>