<?

namespace NumEval;

class factorial {
	
	private $value;
	private $evaluation;
	
	function __construct($value, $evaluation) {
		$this->value = $value;
		$this->evaluation = $evaluation;	
	}
	
	private $factored_values = array();
	
	function generate() {
		$value = $this->value;
		while($this->evaluation->larger($value, 1, false)) {
			$this->factored_values[] = $value;
			$value = $this->evaluation->subtract($value, 1);	
		}
	}
	
	function unify_opposites() {
		$result = array();
		$lookup_value = 2;
		$multiplicants = array();
		
		if($this->evaluation->larger($this->value, 2)) {
			$multiplicants[] = 2;
		}
		
		$prefix_value = "1";
		$postfix_value = "9";
		$multiplicant_value = $prefix_value.$postfix_value;
		if($this->evaluation->larger($this->value, $multiplicant_value)) {
			$multiplicants[] = $multiplicant_value;
		}
		while($this->evaluation->larger($this->value, $multiplicant_value)) {
			if($postfix_value == "9") {
				$result_addition_value = $prefix_value."0";
				if($this->evaluation->larger($this->value, $result_addition_value)) {
					$result[] = $result_addition_value;
				}
				$result_addition_value = $prefix_value."1";
				if($this->evaluation->larger($this->value, $result_addition_value)) {
					$result[] = $result_addition_value;
				}
				$prefix_value = $this->evaluation->add($prefix_value, 1);
				$postfix_value = "2";	
			} else {
				$result_addition_value = $prefix_value."0";
				if($this->evaluation->larger($this->value, $result_addition_value)) {
					$result[] = $result_addition_value;
				}
				$result_addition_value = $prefix_value."1";
				if($this->evaluation->larger($this->value, $result_addition_value)) {
					$result[] = $result_addition_value;
				}
				$prefix_value = $this->evaluation->add($prefix_value, 1);
				$postfix_value = "9";	
			}
			$multiplicant_value = $prefix_value.$postfix_value;
			if($this->evaluation->larger($this->value, $multiplicant_value)) {
				$multiplicants[] = $multiplicant_value;
			}
		}
		
		
		$index = 0;
		$next_index = 1;
		$last_value = 2;
		while($next_index < count($multiplicants)) {
			if(isset($multiplicants[$next_index])) {
				$value = $multiplicants[$index];
				$next_value = $multiplicants[$next_index];
				$multiplication = $this->evaluation->result($value, $next_value);	
				$result[] = $multiplication;
				$addition = 16;
				
				while($addition >= 4) {
					$multiplication = $this->evaluation->add($multiplication, $addition);
					$result[] = $multiplication;
					$addition = $this->evaluation->subtract($addition, 2);	
				}
				$last_value = $next_value;
			}
			$index = $this->evaluation->add($index, 2);
			$next_index = $this->evaluation->add($index, 1);
		}
		$multiplicant_interlope = $this->evaluation->even(count($multiplicants));
		$counter = 2;
		if($last_value != 2) {
			if(!$multiplicant_interlope) {
	 			$counter = $last_value+3;
			} else {
				$counter = $last_value+1;	
			}
		}
		while($this->evaluation->larger($this->value, $counter)) {
			$result[] = $counter;
			$counter = $this->evaluation->add($counter, 1);	
		}
		
		$this->factored_values = $result;
	}
	
	function unify_decimals() {
		$total_decimal_count = 0;
		foreach($this->factored_values as $key => $value) {
			$decimal_value = $this->split_decimal($value);
			if($decimal_value !== false) {
				$this->factored_values[$key] = $decimal_value[0];
				$total_decimal_count = $this->evaluation->add($total_decimal_count, $decimal_value[1]);	
			}
		}
		return $total_decimal_count;
	}
	
	private $index_values = array();
	function index_values() {
		$this->index_values = array();
		foreach($this->factored_values as $value) {
			$this->index_values[$value] = true;
		}
	}
	
	function unify_powers() {
		$this->index_values();
		$result = 1;
		
		$counter = 2;
		$value = $counter;
		unset($this->index_values[$value]);
		while($this->evaluation->larger($this->value, $value)) {	
			$value = $this->evaluation->result($value, $counter);
			unset($this->index_values[$value]);
		}
	}
	
	function split_decimal($value) {
		$digits = $this->evaluation->get_digits($value);
		if($digits[0] != 0) {
			return false;	
		}
		$index = 0;
		$decimal_count = 0;
		while($digits[$index] == 0) {
			$decimal_count = $this->evaluation->add($decimal_count, 1);
			$index = $this->evaluation->add($index, 1);;	
		}
		$decimal_prefix = substr($value, 0, strlen($value)-$decimal_count);
		return array($decimal_prefix, $decimal_count);
	}
	
	function resolve() {
		$value = 1;
		$this->unify_opposites();
		$decimal_count = $this->unify_decimals();
		foreach($this->factored_values as $factored_value) {
			$value = $this->evaluation->result($value, $factored_value);	
		}
		$value = $this->evaluation->pad_zeros($value, $decimal_count);
		return $value;
	}
		
}

?>