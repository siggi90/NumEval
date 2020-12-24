<?

namespace NumEval;

class e {
	
	private $evaluation;
	
	function __construct($evaluation) {
		$this->evaluation = $evaluation;	
	}
	
	private $e_precision = NULL;
	function set_precision($precision) {
		$this->e_precision = $precision;	
	}
	
	function infinite_series($input_value=array('value' => '1', 'remainder' => '0/1'), $precision=12) {
		$n = "1";
		if($this->e_precision !== NULL) {
			$precision = $this->e_precision;	
		}
		$numerator = array('value' => '1', 'remainder' => '0/1');
		$denominator = "1";
		$value = $input_value;
		while($this->evaluation->larger($precision, $n)) {
			$numerator = $this->evaluation->multiply_total($input_value, $numerator);
			if($this->evaluation->truncate_fractions_length > 0) {
				$numerator['remainder'] = $this->evaluation->execute_shorten_fraction($numerator['remainder']);	
			}
			$denominator = $this->evaluation->result($denominator, $n);
						
			$addition = $this->evaluation->execute_divide($numerator, $denominator);
			$value = $this->evaluation->add_total($value, $addition);
			if($this->evaluation->truncate_fractions_length > 0) {
				$value['remainder'] = $this->evaluation->execute_shorten_fraction($value['remainder']);	
			}
			$n = $this->evaluation->add($n, "1");	
		}
		return $value;
	}
}

?>