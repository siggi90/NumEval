<?

namespace NumEval;

class power {
	private $evaluation;
	private $logarithm_10 = NULL;
	
	function __construct($evaluation, $logarithm_10_value=NULL) {
		$this->evaluation = $evaluation;	
		if($logarithm_10_value === NULL) {
			$this->logarithm_10 = $this->evaluation->natural_logarithm(array('value' => '10', 'remainder' => '0/1'));
		} else {
			$this->logarithm_10 = $logarithm_10_value;	
		}
	}
	
	function whole_value($value, $power) {
		$y = "10";
		$y = array('value' => $y, 'remainder' => '0/1');
		
		$value_logarithm = $this->evaluation->natural_logarithm($value);
		$y_logarithm = $this->logarithm_10;
		
		$power = $this->evaluation->multiply_total($power, $value_logarithm);
		$power = $this->evaluation->execute_divide($power, $y_logarithm);
		$power['remainder'] = $this->evaluation->execute_shorten_fraction($power['remainder']);
		return $power;
	}
	
	function fast_power($value, $power) { //$approximate=false, $approximation_precision=10
		$whole_value = $this->whole_value($value, $power);
		
		$value_value = array('value' => $this->evaluation->pad_zeros("1", $whole_value['value']), 'remainder' => '0/1');
				
		$whole_value['value'] = '0';
		$exp_power = $this->evaluation->multiply_total($this->logarithm_10, $whole_value);
		
		$e = new e($this->evaluation);
		$e_value = $e->infinite_series($exp_power);
		
		return $this->evaluation->multiply_total($value_value, $e_value);
	}
}

?>