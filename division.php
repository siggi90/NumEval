<?

namespace NumEval;

class division {

	private $value;
	private $divider;
	private $evaluation;

	function __construct($evaluation) {
		$this->evaluation = $evaluation;
	}
		
	private $divider_unaltered = NULL;
	private $final_call = false;
	private $value_unaltered = NULL;
	function fast_floor_divide($value, $divider, $sub_call=false) {
		$value_unaltered = $value;
		if($sub_call == false) {
			$this->divider_unaltered = $divider;	
		}
		if($sub_call == false && $this->evaluation->larger($divider, $value, false)) {
			return 0;	
		}
		if($value == $divider) {
			return 1;	
		}
		
		
		$digit_length = strlen($value);
		$divider_digit_length = strlen($divider);
		
		$value_digits = str_split($value);
		
		
		
		
		
		$value_first_digit = $value_digits[0];
		
		$divider_difference = $this->evaluation->subtract($digit_length, $divider_digit_length);
		if($this->evaluation->larger($divider_difference, 2)) {
			$divider_difference = $this->evaluation->subtract($divider_difference, 1);
			$divider_difference = $this->evaluation->pad_zeros($value_first_digit, $divider_difference);
			$subtraction = $this->evaluation->result($divider_difference, $divider);
			$value = $this->evaluation->subtract($value, $subtraction);
			$sub_call = $this->fast_floor_divide($value, $subtraction, true);
			if(!$this->final_call) {
				return $this->evaluation->add($divider_difference, $this->evaluation->result($divider_difference, $sub_call));
			} else {
				return $this->evaluation->add($divider_difference, $sub_call);
			}
		}
		
		$addition = $this->evaluation->execute_divide_sub($value_unaltered, $this->divider_unaltered);
		$this->final_call = true;
		return $addition;
	}
	

}

?>