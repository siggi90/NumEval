<?
namespace NumEval;

class binary_division {
	
	private $evaluation;
		
	function __construct($evaluation) {
		$this->evaluation = $evaluation;
	}
	
	private $quotient;
	
	function get_quotient() {
		return $this->quotient;	
	}
	
	function execute_divide($value, $divider) {
		$value = $this->evaluation->remove_leading_zeros($value);
		$divider = $this->evaluation->remove_leading_zeros($divider);
		$value_length = strlen($value);
		$divider_length = strlen($divider);	
		$length_difference = $this->evaluation->subtract($value_length, $divider_length);
		
		
	}
	
	function divide($dividend, $divisor) {
		$dividend = $this->evaluation->change_base($dividend, "10", "2");
		$divisor = $this->evaluation->change_base($divisor, "10", "2");
		return $this->evaluation->change_base($this->evaluation->modulus($dividend, $divisor), "2");
		
		$remainder = "0";
		$this->quotient = "0";
		
		if($dividend === "0") {
			return "0";	
		}
		
		if($divisor === "0") {
			return "0";	
		}
		
		if($this->evaluation->larger($divisor, $dividend, false)) {
			$remainder = $dividend;
			return $remainder;	
		}
		
		if($divisor == $dividend) {
			$this->quotient = "1";
			return $remainder;
		}
		
		$dividend_length = strlen($dividend);
		
		$num_bits = $dividend_length;
		
		$d;
		$t = "0";
		
		$current_dividend_offset = 0;
		
		$counter = 0;
		while($this->evaluation->larger($divisor, $remainder, false) && $num_bits > 0) {
			$bit = substr($dividend, $dividend_length-$num_bits, 1);
			$remainder = $this->evaluation->bit_shift($remainder, 1, false);
			$remainder = $this->evaluation->binary_or($remainder, $bit, false, false);
			$d = $dividend;
			$dividend = $this->evaluation->bit_shift($dividend, 1, false);
			$num_bits--;
			$current_dividend_offset = $dividend_length-$num_bits;
		}
		
		$dividend = $d;
		
		
		$remainder = $this->evaluation->bit_shift_right($remainder, 0, false);
		$num_bits++;
		$i = 0;
		while($i < $num_bits) {
			$bit = substr($dividend, $current_dividend_offset+$i-1, 1); 
			if($bit === false) {
				$bit = "0";	
			}
			$remainder = $this->evaluation->bit_shift($remainder, 1, false);
			$remainder = $this->evaluation->binary_or($remainder, $bit, false, false);
			if($this->evaluation->larger($remainder, $divisor)) {
				$t = $this->evaluation->binary_subtraction($remainder, $divisor);
			} else {
				$t = $remainder;
			}
			$q = substr($t, 0, 1);
			if($q == "1") {
				$q = "0";	
			} else {
				$q = "1";
			}	
			$dividend = $this->evaluation->bit_shift($dividend, 1, false);
			$this->quotient = $this->evaluation->bit_shift($this->quotient, 1, false);
			$this->quotient = $this->evaluation->binary_or($this->quotient, $q, false, false);
			if($q == "1") {
				$remainder = $t;	
			}
			$i++;	
		}
		return $remainder;
		
	}
}


?>