<?
namespace NumEval;

class prime_detection {
	
	private $value;
	private $evaluation;
	
	function __construct($value, $evaluation) {
		$this->value = $value;
		$this->evaluation = $evaluation;
		
		$this->maximum_divisor = $this->evaluation->execute_divide($this->value, 2)['value'];
	}
	
	function pollard_check($n) {
		$x = "2";
		$y = "2";
		$d = "1";
		$counter = 1;
		while($d == "1") {
			$x = $this->pollard_sub($x, $n);
			$y = $this->pollard_sub($this->pollard_sub($y, $n), $n);
			$d = $this->evaluation->gcd($this->evaluation->absolute($this->evaluation->subtract($x, $y)), $n);
		}
		if($d != $n) {
			return false;	
		}
		return true;	
	}
	
	function pollard_sub($value, $n) {
		$x = $this->evaluation->result($value, $value);
		$x = $this->evaluation->add($x, "1");
		$x = $this->evaluation->modulus($x, $n);
		
		return $x;
	}
	
	private $prime_factors = array();
	
	function factor($n) {
		$this->prime_factors = array();
		$this->factor_sub($n);
		return $this->prime_factors;	
	}
	
	function factor_sub($n) {
		$x = "2";
		$y = "2";
		$d = "1";
		while($d == "1") {
			$x = $this->pollard_sub($x, $n);
			$y = $this->pollard_sub($this->pollard_sub($y, $n), $n);
			$d = $this->evaluation->gcd($this->evaluation->absolute($this->evaluation->subtract($x, $y)), $n);	
		}
		$this->prime_factors[] = $d;
		$division = $this->evaluation->execute_divide($n, $d)['value'];
		if($division != "1") {
			$this->factor_sub($division);	
		}
	}
	
	
	function fermat_quotient() {
		$n = $this->evaluation->execute_power_whole(array('value' => '2', 'remainder' => '0/1'), $this->evaluation->subtract($this->value, 1))['value'];
		$n = $this->evaluation->subtract($n, 1);
		
		$division = $this->evaluation->execute_divide($n, $this->value, false, true);
		
		if($this->evaluation->fraction_values($division['remainder'])[0] == 0) {
			return true;	
		}
		return false;
	}
		
	function prime_division_verification($value) {
		$value_added = $this->evaluation->add($value, "1");
		$value_subtracted = $this->evaluation->subtract($value, "1");
		
		$value_added_divided = $this->evaluation->verified_divisible($value_added, "6");
		$value_subtracted_divided = $this->evaluation->verified_divisible($value_subtracted, "6");
		
		if($value_added_divided || $value_subtracted_divided) {
			return true;	
		}
		return false;
	}
	
	function digit_sum_subtraction() {
		$digit_sum = $this->evaluation->digit_sum($this->value);
		$subtracted_value = $this->evaluation->subtract($this->value, $digit_sum);
		if($this->evaluation->prime($subtracted_value)) {
			return false;	
		}
		return true;
	}
	
	function inspect_modulus() {
		$value = $this->value;
		$value = $this->evaluation->digit_sum($value);
		$n = $this->evaluation->subtract($value, 1);
		$factorial = $this->evaluation->factorial($n);
		$modulus = $this->evaluation->modulus($factorial, $this->value);
		if($modulus == $this->value) {
			return true;	
		}
		return false;
	}
	
	function wilson_quotient() {
		$p = $this->value;
		$factorial_value = $this->evaluation->subtract($p, 1);
		$factorial_value = $this->evaluation->factorial($factorial_value);
		$factorial_value = $this->evaluation->add($factorial_value, 1);
		$division = $this->evaluation->execute_divide($factorial_value, $p);
		return $division;
		
		if($this->evaluation->fraction_values($division['remainder'])[0] == 0) {
			return true;	
		}
		return false;
	}
	
	function verify_prime($n) {
		$root = $this->evaluation->root($n, 2);
		$root = $this->evaluation->root_closest_result;
		
		$factorial = $this->evaluation->factorial($root);
		
		$x_di_n = $this->evaluation->execute_divide($factorial, $n);
		
		$numerator = $this->evaluation->fracction_values($x_di_n);
		
		if($this->evaluation->prime($numerator)) {
			return true;	
		}
		return false;
	}

	function prime_sub_count() {
		$digits = $this->evaluation->get_digits($this->value);
		$result = array();
		$value = "";
		$counter = 0;
		while($counter < count($digits)) {
			$value = $digits[$counter].$value;
			if($this->evaluation->prime($value)) {
				$result[] = $value;	
			}
			$counter++;	
		}
		return count($result);
	}
	
	function is_palindrome() {
		$length = strlen($this->value);
		$value = $this->value;
		if($this->evaluation->even($length)) {
			$split_length = floor($length/2);	
			$part_a = substr($value, 0, $split_length);
			$part_b = substr($value, $split_length);
			if($part_a == strrev($part_b)) {
				return true;	
			}
		} else {
			$split_length = floor(strlen($value)/2);	
			$part_a = substr($value, 0, $split_length);
			$part_b = substr($value, $split_length+1);
			if($part_a == strrev($part_b)) {
				return true;
			}	
		}
		return false;	
	}
	
	function reverse_value() {
		$last_digit = $this->evaluation->get_digits($this->value)[0];
		$last_digit_prime = $this->evaluation->prime($last_digit);
		$digit_sum = $this->evaluation->digit_sum($this->value);
		if(($this->evaluation->even($digit_sum) && !$last_digit_prime) || ($last_digit_prime && $this->evaluation->prime($digit_sum))) { 			
			if($this->is_palindrome()) {
				return false;	
			}
			$reverse_value = strrev($this->value);
			if($this->evaluation->prime($reverse_value)) {
				return true;	
			}
		}
		return false;
	}
	
	function reverse_value_b() {
		$digit_sum = $this->evaluation->digit_sum($this->value);
		if(!$this->evaluation->even($digit_sum) && !$this->evaluation->prime($digit_sum)) {
			if($this->is_palindrome()) {
				return false;	
			}
			$reverse_value = strrev($this->value);
			if($this->evaluation->prime($reverse_value)) {
				return 1;	
			} else {
				return 	0;
			}
		}
		return false;
	}
	
	public $base_values = array();
	private $base_digit_sums = array();
	
	function inspect_bases() {
		foreach($this->bases as $base) {
			if($base != -1) {
				$base_value = $this->value;
				if($base != 10) {
					$base_value = $this->evaluation->change_base($this->value, $base);
				}
				
				
				$base_digit_sum = $this->evaluation->digit_sum($base_value);
				$this->base_digit_sums[] = $base_digit_sum;
				$this->base_values[$base] = $base_value;
				/*$base_digits = array_reverse(str_split($base_value));
				$base_digit_value = 1;
				$accumulated_value = 0;
				foreach($base_digits as $Key => $digit) {
					$digit_value = $this->evaluation->result($base_digit_value, $digit);
					$accumulated_value = $this->evaluation->add($digit_value, $accumulated_value);
					$divisible = $this->evaluation->verified_divisible($accumulated_value, $base);
					if($divisible) {
						return true;	
					}
					$base_digit_value = $this->evaluation->result($base_digit_value, $base);
				}*/
			}
		}
		return false;
	}
	
	/*function s_primality_test($value) {
		$numerator = "-1";
		$x_root = $this->evaluation->root($value, "2");
		//if($x_root === false) {
		$x_root = $this->evaluation->root_closest_result;	
		//}
		$denominator = $this->evaluation->execute_divide($x_root, "6")['value'];
		$denominator = $this->evaluation->add($denominator, "1");
		
		echo "denominator: ".$denominator."\n";
		
		//$multiplier = $this->evaluation->execute_divide($numerator, $denominator);
		
		//$sum_value = array('value' => '0', 'remainder' => '0/1');
		//$sum_value_b = array('value' => '0', 'remainder' => '0/1');
		
		$sum_value = "0";
		$sum_value_b = "0";
		
		$counter = "1";
		while($this->evaluation->larger($denominator, $counter)) {
			$sum_divider = $this->evaluation->result($counter, "6");
			$sum_divider_a = $this->evaluation->subtract($sum_divider, "1");
			$sum_divider_b = $this->evaluation->add($sum_divider, "1");
			
			$a_term = $this->evaluation->execute_divide($value, $sum_divider_a);
			//var_dump($a_term);
			$a_term_subtraction = $a_term;
			//$a_term['remainder'] = "0/1";
			$a_term = array('value' => $this->evaluation->floor($a_term), 'remainder' => '0/1');
			
			$a_term_value = $this->evaluation->subtract_total($a_term, $a_term_subtraction);
			//$a_term_value['remainder'] = "0/1";
			//$a_term_value = $a_term_value['value'];
			$a_term_value = $this->evaluation->floor($a_term_value);
			
			//$sum_value = $this->evaluation->add($sum_value, $a_term_value);
			
			
			$b_term = $this->evaluation->execute_divide($value, $sum_divider_a);
			$b_term_subtraction = $b_term;
			//$b_term['remainder'] = "0/1";
			$b_term = array('value' => $this->evaluation->floor($b_term), 'remainder' => '0/1');
			
			
			$b_term_value = $this->evaluation->subtract_total($b_term, $b_term_subtraction);
			//var_dump($b_term_value);
			//$b_term_value = $b_term_value['value'];
			$b_term_value = $this->evaluation->floor($b_term_value);
			
			//$sum_value_b = $this->evaluation->add($sum_value_b, $b_term_value);
			//$sum_value = $this->evaluation->subtract($sum_value, "1");
			//$sum_value_b = $this->evaluation->add($sum_value_b, "1");
			
			//$sum_value = $this->evaluation->add_total($a_term_value, $sum_value);			
			//$sum_value_b = $this->evaluation->add_total($b_term_value, $sum_value_b);
			$sum_value = $this->evaluation->add($sum_value, $a_term_value);
			$sum_value_b = $this->evaluation->add($sum_value, $b_term_value);
			
			$counter = $this->evaluation->add($counter, "1");
			//$a_term = $this->evaluation->subtract($a_term, 
		}
		//$sum_value = $sum_value['value'];
		//$sum_value_b = $sum_value_b['value'];
		var_dump($sum_value);
		var_dump($denominator);
		$s_a = $this->evaluation->execute_divide($sum_value, $denominator);
		$s_b = $this->evaluation->execute_divide($sum_value_b, $denominator);
		
		$s_a = $this->evaluation->negative_value($s_a);
		$s_b = $this->evaluation->negative_value($s_b);
		
		var_dump($s_a);
		var_dump($s_b);
	}*/
	
	function inspect_digit_sums() {
		$this->base_digit_sums = array_unique($this->base_digit_sums);
		
		$combinations = $this->evaluation->combinations($this->base_digit_sums);
		foreach($combinations as $combination) {
			$combination_value = 0;
			foreach($combination as $value) {
				$combination_value = $this->evaluation->add($combination_value, $value);
			}
			if($combination_value != $this->value && $combination_value != 1) {
				$divisible = $this->evaluation->verified_divisible($this->value, $combination_value);
				if($divisible) {
					return true;	
				}
			}
		}
		return false;
	}
	
	function inspect_base_values() {
		$inspection_value = "0";
		foreach($this->base_values as $base => $base_value) {
			$digits = $this->evaluation->get_digits($base_value);
			$first_digit = $this->evaluation->result($digits[0], 1);
			$inspection_value = $this->evaluation->add($first_digit, $inspection_value);	
		}
		$digits = $this->evaluation->get_digits($this->value);
		$first_digit = $this->evaluation->result($digits[0], 1);
		$inspection_value = $this->evaluation->add($first_digit, $inspection_value);	
		if(!$this->evaluation->prime($inspection_value)) {
			return true;	
		}
		return false;
	}
	
	
	
	private $columns = array();
	private $occupied_values = array();
	private $previous_occupied_values = NULL;
	
	private $bases = array(-1, 2, 3, 4, 5, 6, 7, 8, 9);
	
	private $base_conversions = array(10, 2, 3, 4, 5, 6, 7, 8, 9);
	
	function palindrome() {
		foreach($this->base_conversions as $base) {
			$value = $this->value;
			$divider_value = "11";
			if($base != "10") {
				$value = $this->base_values[$base];
				$divider_value = $this->evaluation->change_base("11", "10", $base);	
			}
			
			if($value == "11") {
				return false;	
			}
			$length = strlen($value);
			if($this->evaluation->even($length)) {
				$split_length = floor($length/2);	
				$part_a = substr($value, 0, $split_length);
				$part_b = substr($value, $split_length);
				if($part_a == strrev($part_b)) {
					if($this->evaluation->verified_divisible($this->value, $divider_value)) {
						return true;	
					}
				}
			} else {
				$split_length = floor(strlen($value)/2);	
				$part_a = substr($value, 0, $split_length);
				$part_b = substr($value, $split_length+1);
				if($part_a == strrev($part_b)) {
					if($this->evaluation->verified_divisible($this->value, $divider_value)) {
						return true;	
					}
				}	
			}
		}
		return false;
	}
	
	
	function repeat_value() {
		foreach($this->base_conversions as $base) {
			$value = $this->value;
				
			if($base != "10") {
				$value = $this->base_values[$base];
			}
			
			$length = strlen($value);
			if($this->evaluation->even($length) && $length > 1 && $value != 11) {
				$split_length = floor($length/2);	
				$part_a = substr($value, 0, $split_length);
				$part_b = substr($value, $split_length);
				if($part_a == $part_b) {
					$division_value = $this->evaluation->pad_zeros("1", $split_length-1)."1";
					if($base != "10") {
						$division_value = $this->evaluation->change_base($division_value, "10", $base);	
					}
					if($this->evaluation->verified_divisible($this->value, $division_value)) {
						return true;	
					}
				}	
			}
		}
		return false;
	}
	
	public $previous_values = array();
	private $maximum_divisor;
	private $result_finished = false;
	
	function mark_multiplicants($value) {
		$counter = 1;
		$result = $this->evaluation->result($value, $counter);
		$this->previous_values[$result] = true;
		
			
	}
	
	
	function find_divisors_by_digit_sum($digit_sum) {
		$resulting_values = array();
		$digit_count = 1;
		
		$counter = 1;
		while($this->evaluation->larger($this->maximum_divisor, $counter)) { 			
			$counter_digit_sum = $this->evaluation->digit_sum($counter);
			if($counter_digit_sum == $digit_sum) {
				$divisible = $this->evaluation->verified_divisible($this->value, $counter);
				if($divisible) {
					return false;	
				}
				$resulting_values[] = $counter;	
			}
			$counter = $this->evaluation->add($counter, 1);	
		}
		return true;
	}
	
}

?>