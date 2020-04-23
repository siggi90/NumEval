<?

namespace NumEval;

class binary_modulus {
	
	private $value;
	
	private $binary_value;
	private $evaluation;
	private $binary_division;
	private $binary_pattern;
	
	function __construct($evaluation, $value="0") {
		$this->evaluation = $evaluation;
		$this->value = $value;
		$this->division_value = array();
		
		$this->binary_division = new binary_division($this->evaluation);
	}
	
	function modulus_reduction($value, $divider) {
		$divider_squared = $this->evaluation->binary_multiplication($divider, $divider);
		$divider_4 = $this->evaluation->binary_multiplication("100", $divider);
		$last_value = $value;
		$stop = false;
		while($this->evaluation->larger($value, $divider) && !$stop) {
			$last_value = $value;
			if($this->evaluation->larger($value, $divider_squared)) {
				$value = $this->evaluation->binary_subtraction($value, $divider_squared);
				if($this->evaluation->larger($value, $divider_4)) {
					$value = $this->evaluation->binary_subtraction($value, $divider_4);
					$digit = $this->evaluation->get_digits($value)[0];
					if($digit == "0") {
						$value = $this->evaluation->bit_shift_right($value, "0", false, false);	
						if($value == "0") {
							$stop = true;	
						}
					}
				} else {
					$stop = true;	
				}
			} else {
				$stop = true;	
			}
		}
		return $last_value;
	}
	
	private $auxillary_verification_strength = "1000";
	
	function set_strength($strength) {
		$this->auxillary_verification_strength = $this->evaluation->change_base($strength, "2");
	}
	
	function prime_verification_auxillary($value, $n_value=NULL, $n_value_count=NULL) {
		if($n_value === NULL) {
			$n_1 = "1";
			$n_2 = "10";
			$n_value = $this->evaluation->binary_multiplication($n_1, $n_2);	
			$n_value_count = $n_2;
		} else {
			$n_value_count = $this->evaluation->binary_addition($n_value_count, "1");
			$n_value = $this->evaluation->binary_multiplication($n_value, $n_value_count);
			if($this->evaluation->larger($n_value, $value)) {
				return true;	
			}
		}
		$fermat_count = $this->evaluation->binary_subtraction($value, "10");
				
		$inverse = $this->mod_inverse_fermat($n_value, $fermat_count, $value);
		
		$inverse_verification = $this->evaluation->binary_multiplication($inverse, $n_value);
		$modulus = $this->execute_modulus($inverse_verification, $value);
		$modulus = $this->evaluation->remove_leading_zeros($modulus);
		if($modulus == "1") {
			if($this->evaluation->larger($n_value_count, $this->auxillary_verification_strength)) {
				return true;	
			}
			return $this->prime_verification_auxillary($value, $n_value, $n_value_count);
		}
		return false;
	}
	
	function gcd($a, $b) {
		if($a == "0") {
			return $b;
		}
		return $this->gcd($this->execute_modulus($b, $a), $a);
	}
	
	function absolute_binary_subtraction($a, $b) {
		if($this->evaluation->larger($a, $b)) {
			return $this->evaluation->binary_subtraction($a, $b);	
		}
		return $this->evaluation->binary_subtraction($b, $a);
	}
	
	function pollard_check($n) {
		$x = "10";
		$y = "10";
		$d = "1";
		$counter = 1;
		while($d == "1") {
			$x = $this->pollard_sub($x, $n);
			$y = $this->pollard_sub($this->pollard_sub($y, $n), $n);
			
			$subtraction = $this->absolute_binary_subtraction($x, $y);
			$d = $this->gcd($subtraction, $n);
			
			$d = $this->evaluation->remove_leading_zeros($d);
		}
		if($d != $n) {
			return false;	
		}
		return true;	
	}
	
	function pollard_result($n) {
		$x = "10";
		$y = "10";
		$d = "1";
		$max_length = $this->evaluation->result(strlen($n), "2");
		while($d == "1") {
			$x = $this->pollard_sub($x, $n);
			$y = $this->pollard_sub($this->pollard_sub($y, $n), $n);
			
			$subtraction = $this->absolute_binary_subtraction($x, $y);
			if($this->evaluation->larger(strlen($subtraction), $max_length)) {
				return false;	
			}
			$d = $this->gcd($subtraction, $n);
			
			$d = $this->evaluation->remove_leading_zeros($d);
		}
		return $d;
	}
	
	function pollard_sub($value, $n) {
		$x = $this->evaluation->binary_multiplication($value, $value);
		$x = $this->evaluation->binary_addition($x, "1");
		$x = $this->execute_modulus($x, $n);
		return $x;
	}
	
	
	private $modular_exponentiation_exponent_translation;
	
	private $total_modular_exponentiation_result = NULL;
	
	private $value_added_exponentiation = false;
	function fast_modular_exponentiation($value, $divider, $exponent, $change_base=true, $start_value=NULL) {
		$exponent_binary = $exponent;
		if($change_base) {
			$exponent_binary = $this->evaluation->change_base($exponent, "2");
			$exponent_binary = $this->evaluation->remove_leading_zeros($exponent_binary);
			$exponent_binary = $exponent_binary;
			$this->total_modular_exponentiation_result = NULL;	
		}
		
		$digit = substr($exponent_binary, 0, 1);
		$result = "1";
		
		$remainder = substr($exponent_binary, 1);
		if($remainder === "") {
			$result = $value;	
			if($start_value !== NULL) {
				
				$result = $value;
			}
			if($digit == "1") {
				$this->total_modular_exponentiation_result = $result;	
			}
			
			return $value;
		}
		
		$result = $this->fast_modular_exponentiation($value, $divider, $remainder, false);
		$multiplier = $result;
		
		$result = $this->evaluation->binary_multiplication($result, $multiplier);
		$result = $this->execute_modulus($result, $divider);
		
		
		if($digit === "1") {
			if($this->total_modular_exponentiation_result === NULL) {
				$this->total_modular_exponentiation_result = $result;	
			} else {
				$this->total_modular_exponentiation_result = $this->evaluation->binary_multiplication($this->total_modular_exponentiation_result, $result);
				$this->total_modular_exponentiation_result = $this->execute_modulus($this->total_modular_exponentiation_result, $divider);	
			}
		}
		
		if($change_base) {
			$this->total_modular_exponentiation_result = $this->execute_modulus($this->total_modular_exponentiation_result, $divider);
			$this->total_modular_exponentiation_result = $this->evaluation->remove_leading_zeros($this->total_modular_exponentiation_result);	
			$final_result = $this->total_modular_exponentiation_result;			
			return $final_result;
		}
		
		return $result;
	}
	
	
	
	function binary_division($value, $divider, $precise=false) {
		$value = $this->evaluation->change_base($value, "10", "2");
		$divider = $this->evaluation->change_base($divider, "10", "2");
		$division = $this->evaluation->execute_divide($value, $divider);
		////var_dump($division);
		if($precise) {
			$numerator = $this->evaluation->fraction_values($division['remainder'])[0];
			if($numerator != "0") {
				return false;	
			}
		}
		return $this->evaluation->change_base($division['value'], "2");	
	}
	
	function set_closest_known_prime($value) {
		$value_subtracted = $this->evaluation->binary_subtraction($value, "1");
		$this->last_factorial_zero_value = array('factorial_value' => $value_subtracted, 'modulus_value' => $value_subtracted, 'divider' => $value);
	}
		
	function get_factorial_inverse($n, $factorial_value, $m) {
		$m_subtracted = $this->evaluation->binary_subtraction($m, "1");
		
		$fermat_count = $this->evaluation->binary_subtraction($m, "10");
		$inverse_value = $this->mod_inverse_fermat($m_subtracted, $fermat_count, $m);
		$inverse_value = $this->evaluation->remove_leading_zeros($inverse_value);
		$modulus_value = $inverse_value;
		
		$mn = $this->evaluation->binary_multiplication($m, $n);
				
		$factorial = $m_subtracted;
		while($this->evaluation->larger($factorial, $factorial_value, false)) {
			$modulus_value = $this->evaluation->binary_multiplication($modulus_value, $factorial);
			$modulus_value = $this->execute_modulus($modulus_value, $m);
			$factorial = $this->evaluation->binary_subtraction($factorial, "1");
		}	
		
		if($modulus_value == $n) {
			return "undetermined";	
		}
		$inverse_value = $this->mod_inverse_fermat($modulus_value, $fermat_count, $m);
		$inverse_value = $this->evaluation->remove_leading_zeros($inverse_value);
		
		return $inverse_value;
	}
	
	private $unconfirmed_factorial_inverse = false;
	private $previous_factorial_zero_value = NULL;
	private $last_factorial_zero_value = NULL;
	function factorial_zero_modulus($factorial_value, $divider) {
		$modulus_value = "1";
		$factorial_value_max = $factorial_value;
		$factorial_value = "1";	
		
		$factorial_inverse_value = "-1";
		if($this->last_factorial_zero_value !== NULL && $this->evaluation->larger($factorial_value_max, $this->last_factorial_zero_value['factorial_value'])) {
			$factorial_value = $this->last_factorial_zero_value['factorial_value'];
			$modulus_value = $this->last_factorial_zero_value['modulus_value'];	
			$last_divider = $this->last_factorial_zero_value['divider'];
			
						
			$modulus_value = $this->get_factorial_inverse($last_divider, $factorial_value, $divider);
			
			
			if($modulus_value == "undetermined") {
				return "undetermined";	
			}
			if($modulus_value == "0") {
				return true;	
			}
			$factorial_value = $this->evaluation->binary_addition($factorial_value, "1");
			$factorial_inverse_value = $modulus_value;
		}
		
		while($this->evaluation->larger($factorial_value_max, $factorial_value)) {
			$modulus_value = $this->evaluation->binary_multiplication($modulus_value, $factorial_value);
			$modulus_value_store = $modulus_value;
			$modulus_value = $this->execute_modulus($modulus_value, $divider);
			$modulus_value = $this->evaluation->remove_leading_zeros($modulus_value);
			
			if($modulus_value == "0" || $modulus_value == "") {
				return true;	
			}
			$factorial_value = $this->evaluation->binary_addition($factorial_value, "1");	
		}
		
		if($modulus_value == $this->evaluation->binary_subtraction($divider, "1")) {
			$this->previous_factorial_zero_value = $this->last_factorial_zero_value;
			$this->last_factorial_zero_value = array('factorial_value' => $this->evaluation->binary_subtraction($factorial_value, "1"), 'modulus_value' => $modulus_value, 'divider' => $divider);
			return false;
		}
		return true;
	}
	
	function prime_validation($value_binary) {
		$value_binary_added = $this->evaluation->binary_addition($value_binary, "11");
		$fermat_count = $this->evaluation->binary_subtraction($value_binary, "10");
		$inverse_value = $this->mod_inverse_fermat($value_binary_added, $fermat_count, $value_binary);
		$inverse_value = $this->evaluation->remove_leading_zeros($inverse_value);
		
				
		$inverse_verification = $this->evaluation->binary_multiplication($inverse_value, $value_binary_added);
		$inverse_verification = $this->execute_modulus($inverse_verification, $value_binary);
		$inverse_verification = $this->evaluation->remove_leading_zeros($inverse_verification);
		if($inverse_verification != "1") {
			return false;	
		}
		
		
		$divider_value = $this->evaluation->binary_addition($value_binary, "11");
		$divider_value = $this->evaluation->binary_multiplication($divider_value, $value_binary);
		
		
		$result_n = $this->evaluation->binary_multiplication($value_binary, $value_binary);
						
		$result_n = $this->execute_modulus($result_n, $divider_value);
						
		$s = $this->evaluation->binary_subtraction($value_binary, "1");
		$result_n = $this->evaluation->binary_multiplication($result_n, $s);
						
		$result_n = $this->execute_modulus($result_n, $divider_value);
						
		$s = $this->evaluation->binary_subtraction($value_binary, "10");
		$result_n = $this->evaluation->binary_multiplication($result_n, $s);
						
		$result_n = $this->execute_modulus($result_n, $divider_value);
						
		$result_n = $this->evaluation->binary_multiplication($result_n, $inverse_value);
						
		$result_n = $this->execute_modulus($result_n, $divider_value);
		$result_n = $this->evaluation->remove_leading_zeros($result_n);
				
		if($result_n == "0") { 			
			return false;
		}
		return true;
	}
	
	function iterative_subtraction($value, $value_decimal) {
		$inverse_value = $this->evaluation->bit_shift_right($value, "0", false, false);	
		$inverse_value_start = $inverse_value;
		return $this->factorial_modulus_verification($this->evaluation->binary_subtraction($value, "11"), $value, $inverse_value_start, $value_decimal);
	}
	
	function iterative_subtraction_alt($value) {
		$inverse_value = $this->evaluation->bit_shift_right($value, "0", false, false);	
		$inverse_value_start = $inverse_value;
				
				
		$count = $this->evaluation->binary_subtraction($value, "1");
		$factorial_zero_modulus = $this->factorial_zero_modulus($count, $value);		
		if($factorial_zero_modulus == "undetermined") {
			return "undetermined";	
		}
		if($factorial_zero_modulus) {
			return false;	
		}
		return true;
	}
	
	function factorial_modulus_verification($count, $value, $result, $value_decimal) {
		$fermat_count = $this->evaluation->binary_subtraction($value, "10");
		$factorial_value = $count;
		$marked_values = array();
		$total = "1";
		$value_subtracted = $this->evaluation->binary_subtraction($value, "11");
		$a = $this->evaluation->binary_subtraction($value, "1");
		$b = $this->evaluation->binary_subtraction($value, "10");
		$a = $this->evaluation->remove_leading_zeros($a);
		$inverse_value_a = $this->mod_inverse_fermat($a, $fermat_count, $value);
		$inverse_value_a = $this->evaluation->remove_leading_zeros($inverse_value_a);
		$inverse_value_b = $this->mod_inverse_fermat($b, $fermat_count, $value);
		$inverse_value_b = $this->evaluation->remove_leading_zeros($inverse_value_b);
		if($inverse_value_a !== $a) {
			return false;	
		}
		if($inverse_value_b !== false) {
			if($inverse_value_b !== $result) {
				return false;
			}
		}
		return true;
	}
	
	
	function iterative_subtraction_decimal($value_decimal, $value_binary) {
		$value = $value_binary;
		$inverse_value = $this->evaluation->bit_shift_right($value, "0", false, false);	
		$inverse_value_start = $inverse_value;
		$addition = "0";
		$inverse_value_addition = $inverse_value;
		while($this->evaluation->larger($inverse_value_addition, "1", false)) {
			$inverse_value_addition = $this->evaluation->bit_shift_right($inverse_value_addition, "1", false, false);	
						$addition = $this->evaluation->binary_addition($addition, $inverse_value_addition);
		}
		$inverse_value = $this->evaluation->binary_addition($inverse_value, $addition);
		
		$count = $this->evaluation->binary_subtraction($value, $inverse_value);
		
		$value = $value_decimal;
		$negation_value = $value;
		$factorial_value = $this->evaluation->change_base($count, "10", "2");		
		while($this->evaluation->larger($factorial_value, "1", false) && $this->evaluation->larger($negation_value, "1", false)) {
			$division = $this->evaluation->execute_divide($negation_value, $factorial_value);
			if($this->evaluation->fraction_values($division['remainder'])[0] == 0) {
				$negation_value = $division['value'];	
			}
			$factorial_value = $this->evaluation->subtract($factorial_value, "1");	
		}
		if($negation_value == "1") {
			return false;	
		}
		return true;
	}
	
	function prime_validation_secondary($value) {
		$value_subtracted = $this->evaluation->binary_subtraction($value, "11");
		$value_subtracted_2 = $this->evaluation->binary_subtraction($value, "11");
		$value_added = $this->evaluation->binary_addition($value, "11");
		
		$n_value_result_a = $value;
		
		$partial_factorial = "1";
		
		$divider = $this->evaluation->binary_multiplication($value, $value_added);
		
		$factorial_value = $value;
		while($n_value_result_a != "0" && $partial_factorial != "0" && $this->evaluation->larger($factorial_value, "0")) {
			$n_value_result_a = $this->evaluation->binary_multiplication($n_value_result_a, $factorial_value);
			$n_value_result_a = $this->execute_modulus($n_value_result_a, $divider);
			$n_value_result_a = $this->evaluation->remove_leading_zeros($n_value_result_a);
			if($this->evaluation->larger($value_subtracted_2, $factorial_value, false)) {
				$partial_factorial = $this->evaluation->binary_multiplication($partial_factorial, $factorial_value);
				$partial_factorial = $this->execute_modulus($partial_factorial, $divider);
				$partial_factorial = $this->evaluation->remove_leading_zeros($partial_factorial);
			}
			$factorial_value = $this->evaluation->binary_subtraction($factorial_value, "1");
			
			
		}
		while($n_value_result_b != "0" && $this->evaluation->larger($factorial_value, "0")) {
			$n_value_result_b = $this->evaluation->binary_multiplication($partial_factorial, $value_added);
			$n_value_result_b = $this->execute_modulus($n_value_result_b, $divider);
			$n_value_result_b = $this->evaluation->remove_leading_zeros($n_value_result_b);
			$factorial_value = $this->evaluation->binary_subtraction($factorial_value, "1");
			
		}
		if($n_value_result_b == "0") {
			return false;	
		}
		return true;
	}
	
	function mod_inverse_fermat($x, $y, $m) {
		if($this->evaluation->larger("1", $y, false)) {
			return "1";	
		}
		
		$p = $this->execute_modulus($this->mod_inverse_fermat($x, $this->evaluation->bit_shift_right($y, 0, false), $m), $m);
		$p = $this->execute_modulus($this->evaluation->binary_multiplication($p, $p), $m);
		if($this->evaluation->remove_leading_zeros($this->execute_modulus($y, "10")) != "0") {
			$p = $this->evaluation->binary_multiplication($p, $x);
			$p = $this->execute_modulus($p, $m);
		}
				
		$p = $this->evaluation->remove_leading_zeros($p);
		return $p;
	}
	
	function maximum_division_value($value) {
		$counter = "0";
		$digits = $this->evaluation->get_digits($value);
		foreach($digits as $digit) {
						if($digit == "0") {
				$counter = $this->evaluation->add($counter, "1");
			} else {
				return $counter;
			}
		}
		return $counter;
	}
	
	function prime($value, $value_decimal=NULL) {
		$value_length = strlen($value);
		if(!$this->evaluation->even($value_length)) {
			$value_length = $this->evaluation->add($value_length, "1");
		}
		
		$value_length_divided = $this->evaluation->execute_divide($value_length, "2")['value'];
		
		$binary_power_value = $this->binary_power_value($value_length);
		$binary_power_value_divided = $this->binary_power_value($value_length_divided);
		
		$numerator = $binary_power_value;
		$modulus = $this->execute_modulus($numerator, $value);
						
		
		$c = $this->evaluation->change_base($modulus, "10", "2");
		
		$z = $modulus;
		
		$gcd = $this->evaluation->gcd($c, $value_decimal);
		if($gcd != "1") {
			return false;
		}
		
		$fermat_count = $this->evaluation->binary_subtraction($value, "10");
		$z_inverse = $this->mod_inverse_fermat($z, $fermat_count, $value);		
		$z_inverse = $this->evaluation->remove_leading_zeros($z_inverse);
		
		
		$z_modulus_divided = $this->execute_modulus($binary_power_value_divided, $value);
		
		$c_divided = $this->evaluation->change_base($z_modulus_divided, "10", "2");
						
		$gcd = $this->evaluation->gcd($c_divided, $value_decimal);
		if($gcd != "1") {
			return false;
		}
		
		$z_inverse_divided = $this->mod_inverse_fermat($z_modulus_divided, $fermat_count, $value);
		$z_inverse_divided = $this->evaluation->remove_leading_zeros($z_inverse_divided);
		
						
						
						
		$confirm_inverse = $this->evaluation->binary_multiplication($z_inverse, $z);
		$confirm_inverse = $this->execute_modulus($confirm_inverse, $value);
		$confirm_inverse = $this->evaluation->remove_leading_zeros($confirm_inverse);
		if($confirm_inverse != "1") {
			return false;	
		}
		
				
		$x = $value_decimal;
		if($value_decimal === NULL) {
			$x = $this->evaluation->change_base($value, "10", "2");
		}
		$s = $this->evaluation->execute_divide($x, $value_length);
				
		
		$s = $s['value'];
		
				
		$sx = $this->evaluation->result($s, $value_length);
		
		$n = $this->evaluation->subtract($x, "1");
		$n = $this->evaluation->subtract($n, $sx);
		$value_length_max = $this->evaluation->result($value_length, "2");
		while($this->evaluation->larger($value_length_max, $n)) {
			$s = $this->evaluation->subtract($s, "1");
			$sx = $this->evaluation->result($s, $value_length);
			$n = $this->evaluation->subtract($x, "1");
			$n = $this->evaluation->subtract($n, $sx);
		}
		$n_divided = $this->evaluation->execute_divide($n, "2")['value'];
		
		$n_value = $this->binary_power_value($n);
		
		$n_value_divided = $this->binary_power_value($n_divided);
		
		$n_value_modulus = $this->execute_modulus($n_value, $value);
										
		$binary_x_inverse = $z_inverse;		
		
		$n_value_modulus_divided = $this->execute_modulus($n_value_divided, $value);
		
		$gz = $this->fast_modular_exponentiation($binary_x_inverse, $value, $this->evaluation->subtract($s, "1"), true, true);
		
		$gz = $this->evaluation->binary_multiplication($gz, $z_inverse);
		$gz = $this->execute_modulus($gz, $value);
		
				
		$gz_divided = $this->fast_modular_exponentiation($z_inverse_divided, $value, $this->evaluation->subtract($s, "1"), true, true);
		
		$gz_divided = $this->evaluation->binary_multiplication($gz_divided, $z_inverse_divided); 		
		$gz_divided = $this->execute_modulus($gz_divided, $value);
		$gz_divided_added = $this->evaluation->binary_multiplication($gz_divided, $this->evaluation->binary_addition($value, "1")); 		
		$gz_divided_added = $this->execute_modulus($gz_divided_added, $value);
		$gz_divided_added = $this->evaluation->remove_leading_zeros($gz_divided_added);

		$gz_divided_subtracted = $this->evaluation->binary_multiplication($gz_divided, $this->evaluation->binary_subtraction($value, "1")); 		
		$gz_divided_subtracted = $this->execute_modulus($gz_divided_subtracted, $value);
		$gz_divided_subtracted = $this->evaluation->remove_leading_zeros($gz_divided_subtracted);
		
			
		$gz = $this->evaluation->remove_leading_zeros($gz);
		
		if($gz == $n_value_modulus && ($n_value_modulus_divided == $gz_divided_added || $n_value_modulus_divided == $gz_divided_subtracted)) {
			return true;	
		}
		return false;
	}
	
	
	private $transform_modulus_divider = NULL;
	function transform_modulus($value, $divider) {
		$value = $this->evaluation->remove_leading_zeros($value);
		$divider = $this->evaluation->remove_leading_zeros($divider);
		$value_length = strlen($value);
		$divider_length = strlen($divider);
		if($value === $divider) {
			return "0";	
		}
		if($this->evaluation->larger($divider, $value, false)) {
			return $value;
		}	
		if($this->evaluation->larger($this->evaluation->result($divider_length, 2), $value_length)) {
			$result = $this->binary_division->divide($value, $divider);	
			return $result;
		}
		
		if($this->transform_modulus_divider !== $divider) {
			$this->transform_modulus_divider = $divider;
			$palindrome_modulus = $this->binary_power_modulus_sub_palindrome($value, $divider);
			if($palindrome_modulus !== false) {
				return $this->execute_modulus($palindrome_modulus, $divider);	
			}	
		}
		
		$binary_power_value = $this->binary_power_value($divider_length);
		
		$binary_and_value = $this->get_binary_and_value($divider_length);
		
		$x_modulus = $this->evaluation->binary_and($value, $binary_and_value, false, false);
		$unaltered_value = $x_modulus;
		
		$x_modulus = $this->evaluation->binary_multiplication($x_modulus, $divider); 		
		$t = $this->evaluation->binary_subtraction($binary_power_value, $divider);
		$xt = $this->evaluation->binary_multiplication($value, $t);
		
		$tx_mod_2n = $this->evaluation->binary_multiplication($unaltered_value, $t);
		$comparison_value = $this->evaluation->binary_subtraction($tx_mod_2n, $divider);
		$comparison_value = $this->evaluation->remove_leading_zeros($comparison_value);
		
		$u2n = $this->evaluation->bit_shift($divider, $divider_length, false);
		
		$divider_length_subtracted = $this->evaluation->subtract($divider_length, "1");
		$numerator_subtracted = false;
		if($this->evaluation->larger($divider_length_subtracted, 0, false)) {
			$binary_power_x = $this->binary_power_value($value_length);
			
			$k_addition_remainder_value;
			$k_addition_remainder_value = $this->evaluation->bit_shift_right($binary_power_x, $divider_length_subtracted, false);
			if($k_addition_remainder_value !== false && $k_addition_remainder_value != "" && $k_addition_remainder_value != "0") {
				$k_addition_remainder_value = $this->execute_modulus($k_addition_remainder_value, $divider);
				$k_addition_remainder_value = $this->evaluation->bit_shift($k_addition_remainder_value, $divider_length, false);	
				if($this->trim_zeros($k_addition_remainder_value, true) == "") {
					$numerator_subtracted = true;	
				} else {
					$k_addition_remainder_value = $this->evaluation->binary_subtraction($k_addition_remainder_value, "1");				
					$k_addition_remainder_value = $this->evaluation->binary_multiplication($k_addition_remainder_value, $t);
				}
			} else {
				$k_addition_remainder_value = "0";	
			}
			
			if($numerator_subtracted) {
				$result = $this->evaluation->binary_subtraction($x_modulus, $t);
				$result = $this->evaluation->bit_shift_right($result, $divider_length_subtracted, false);
				$result = $this->execute_modulus($result, $divider);
				return $result;
			} else {
				$numerator = $k_addition_remainder_value;
				$numerator = $this->execute_modulus($numerator, $u2n);
								
				$result = $this->evaluation->binary_addition($x_modulus, $numerator);
				$result = $this->evaluation->bit_shift_right($result, $divider_length_subtracted, false);
				$result = $this->execute_modulus($result, $divider);
				return $result;
			}
		} else {
			$result = $this->binary_division->divide($value, $divider);	
			return $result;
		}
	}
		
	private $mask_division_depth = 0;
	private $mask_division_precision = 100;
	function mask_division($value, $divider, $precision=NULL) {
		if($precision != NULL) {
			$this->mask_division_depth = 0;
			$this->mask_division_precision = $precision;	
		}
		$this->mask_division_depth++;
		if($this->mask_division_depth > $this->mask_division_precision) {
			$this->binary_division->divide($value, $divider);
			$binary_division = $this->binary_division->get_quotient();
			return $binary_division;	
		}
		if($value == $divider) {
			return "0";	
		}
		if(strlen($value) == strlen($divider)) {
			return $this->evaluation->binary_subtraction($value, $divider);	
		}
		$xor_value = "";
		$value_digits = str_split($value);		
		foreach($value_digits as $power => $digit) {
			$p = $power;
			$power = strlen($value) - $power;
			$and_value = $this->get_binary_and_value($power);
			$masked_value = $this->binary_mask($and_value, $divider);
			$masked_value = strrev($masked_value);
			$masked_value = $this->evaluation->bit_shift_right($masked_value, $p, false);
			$xor_value = $this->evaluation->binary_xor($xor_value, $masked_value, false, false);
		}
		$xor_value = $this->evaluation->bit_shift_right($xor_value, strlen($divider)-4, false);
		$xor_value = $this->evaluation->remove_leading_zeros($xor_value);
		$multiplication = $this->evaluation->binary_multiplication($xor_value, $divider);
		$multiplication = $this->evaluation->remove_leading_zeros($multiplication);
		$addition = "0";
		$subtraction_value = "0";
		if($this->evaluation->larger($multiplication, $value)) {
			$subtraction = $this->evaluation->binary_subtraction($multiplication, $value);
			$addition = $this->mask_division($subtraction, $divider);
		} else {
			$subtraction = $this->evaluation->binary_subtraction($value, $multiplication);	
			$subtraction_value = $this->mask_division($subtraction, $divider);
		}
		
		$xor_value = $this->evaluation->binary_subtraction($xor_value, $addition);
		$xor_value = $this->evaluation->binary_addition($xor_value, $subtraction_value);
		
		return $xor_value;
	}
	
	function binary_power_modulus_sub_palindrome($value, $divider) {
		$binary_value = $value;
		$binary_value_subtraction = $divider;
		
		
		$zero_length_repeat = $this->zero_length_repeat_palindrome($binary_value_subtraction);
		if($this->zero_repeat_palindrome($binary_value_subtraction) || $zero_length_repeat !== false) {
			$length = strlen($binary_value_subtraction);
			$length = $this->evaluation->add($length, 1);
			
			$numerator_length = strlen($binary_value);
			
			$modulus = $this->evaluation->modulus($numerator_length, $length);
			
			if($modulus != 0) {
				$remainder_value = substr($value, 0, $modulus);
				return $remainder_value;
			}
			return "0";	
		}
		
		
		$subtraction_fraction_numerator = $binary_value;
		if($this->zero_palindrome($binary_value_subtraction)) {
			$length = strlen($binary_value_subtraction);
			$length = $this->evaluation->subtract($this->evaluation->add($length, $length), 2);
			
			$numerator_length = strlen($subtraction_fraction_numerator);
			
			$division = $this->evaluation->execute_divide($numerator_length, $length);
			$modulus = $this->evaluation->fraction_values($division['remainder'])[0];
			if($modulus == 0) {
				return "0";	
			} else {
				$remainder_value = substr($value, 0, $modulus);
				
				return $remainder_value;
							}
		} else if($this->no_zeros($binary_value_subtraction)) {
			$binary_value_subtraction_digit_count = strlen($binary_value_subtraction);
			$value_digit_count = strlen($subtraction_fraction_numerator);
			
			$modulus = $this->evaluation->modulus($value_digit_count, $binary_value_subtraction_digit_count);
			if($modulus == 0) {
				return "0";	
			}
			return substr($value, 0, $modulus);
		}
		return false;
	}
	
	function binary_power_modulus_sub($value, $divider) {
		$value_length = strlen($value);
		$divider_translation = $value_length - strlen($divider);
		
		$m_min = $this->binary_power_value($this->evaluation->subtract($divider_translation, "1"));
		$m_max = $this->binary_power_value($divider_translation, "1");
		
		
		
		$subtraction_value = $this->evaluation->binary_multiplication($m_min, $divider);
		
		$subtracted_value = $this->evaluation->binary_subtraction($value, $subtraction_value);
		$subtracted_value = $this->evaluation->remove_leading_zeros($subtracted_value);
		if($this->evaluation->larger($subtracted_value, $divider)) {
			return $this->execute_modulus($subtracted_value, $divider);
		}
		return $subtracted_value;
		
	}
	
	function binary_power_modulus_sub_inverse($value, $divider) {
		$divider_unaltered = $divider;
		
		$value_length = strlen($value);
		$divider_length = strlen($divider);
		if($value == $divider) {
			return "0";	
		}
		if($this->evaluation->larger($divider, $value, false)) {
			return $value;	
		}
		if($value_length == $divider_length || $this->evaluation->subtract($value_length, 1) == $divider_length) {
			$subtraction = $this->evaluation->binary_subtraction($value, $divider);
			return $subtraction;	
		}
		
		
		$divider_translation = $this->evaluation->subtract($value_length, $divider_length);
		
		$divider_translation_decimal = "0";
		$numerator = $divider;
		while($this->evaluation->larger($value, $numerator)) {
			$numerator = $this->bit_shift_by_decimal($numerator, "1");
			$divider_translation_decimal = $this->evaluation->add($divider_translation_decimal, "1");
		}
		$divider_translation_decimal = $this->evaluation->subtract($divider_translation_decimal, "1");
		
						
		$divider_translation = $this->evaluation->add($divider_translation, "1");
				
		$divider = $this->bit_shift_by_decimal($divider, $divider_translation);
		
		
		
		
		$division_value = $this->evaluation->bit_shift_right($divider, $value_length-2, false);
		
		
		
		$numerator = "1";
		
		
		while($this->evaluation->larger($division_value, $numerator)) {
			$numerator = $this->bit_shift_by_decimal($numerator, "1");
					}
		$counter = "0";
		while($this->evaluation->larger($divider_translation_decimal, $counter, false)) {
			$numerator = $this->bit_shift_by_decimal($numerator, "1");
			$counter = $this->evaluation->add($counter, "1");
		}
				
		$multiplier_value = $this->evaluation->bit_shift_right($numerator, strlen($division_value)-1, false);
						
		
		
		$subtraction_value = $this->evaluation->binary_multiplication($divider_unaltered, $multiplier_value);
		
		$subtracted_value = $this->evaluation->binary_subtraction($value, $subtraction_value);
		
		if($this->evaluation->larger($subtracted_value, $divider)) {
			return $this->execute_modulus($subtracted_value, $divider);
		}
		return $subtracted_value;
	}
	
	function bit_shift_by_decimal($value, $places) {
		$counter = "0";
		$result;
		while($this->evaluation->larger($places, $counter, false)) {
			$result = $this->evaluation->binary_addition($this->evaluation->bit_shift($value, 3, false), $this->evaluation->bit_shift($value, 1, false));
			$value = $result;
			$counter = $this->evaluation->add($counter, "1");
		}
		return $result;	
	}
	
	
	function execute_modulus($value, $divider) {
		$value = $this->evaluation->remove_leading_zeros($value);
		$divider = $this->evaluation->remove_leading_zeros($divider);
		
		$value = $this->evaluation->absolute($value);
		if($divider === $value) {
			return "0";	
		}
		if($this->evaluation->larger($divider, $value, false)) {
			return $value;	
		}
				
		$value_length = strlen($value);
		$divider_length = strlen($divider);
		
		if($this->is_binary_power($divider, false)) {
			$binary_and_value = $this->get_binary_and_value($this->evaluation->subtract($divider_length, "1"));
			return $this->evaluation->binary_and($value, $binary_and_value, false, false);	
		}
		if($this->is_binary_power($value, false)) {
												
			
			$m2_subtracted = $this->get_binary_and_value($this->evaluation->subtract($value_length, 1));
			if($this->evaluation->larger($value_length, $this->evaluation->result($divider_length, 3))) {
				$this->modulus_split_remainder_value = NULL;
				$m2 = $this->modulus_split($m2_subtracted, $divider, true);
			} else {
				$m2 = $this->transform_modulus($m2_subtracted, $divider);
			}
			$m2 = $this->evaluation->binary_addition($m2, "1");
			$m2 = $this->evaluation->remove_leading_zeros($m2);
			if($m2 == $divider) {
				$m2 = "0";	
			}
			return $m2;
		}
		
		$x = $value;	
		
		
				
		$binary_value_subtraction = $divider;	
		$normalized_values = $this->normalize_divider_binary($x, $binary_value_subtraction, true);

		$fraction_values = $this->evaluation->fraction_values($normalized_values['fraction']);
			
			
		$max_digit = strlen($x)-1;	
		$counter = 0;
		
		$binary_and_value = "";
		while($this->evaluation->larger($max_digit, $counter, false)) {
			$binary_and_value .= "1";
			$counter = $this->evaluation->add($counter, 1);
		}
		
		$remainders = array();
		
				
		$stop = false;
		$m2 = $x;
		$binary_multiplier_value = $counter;
		$binary_power_value = $this->evaluation->subtract($counter, 1);
		$x_binary_modulus_value = $this->evaluation->binary_and($x, $binary_and_value, false, false);
		$x_binary_modulus_value = $this->evaluation->remove_leading_zeros($x_binary_modulus_value);
		if($x_binary_modulus_value == "0") {
			$stop = true;	
		} else {
			$remainders[] = $x_binary_modulus_value;				
			$x_subtracted = $this->evaluation->binary_subtraction($x, $x_binary_modulus_value);
			$m2 = $this->evaluation->bit_shift_right($x_subtracted, $binary_power_value, false);
							
			$x = $m2;
							
		}
		$m2 = $this->evaluation->bit_shift($m2, $binary_multiplier_value, false);								
		$remainder_sum = "0";
		foreach($remainders as $remainder) {
			$remainder_sum = $this->evaluation->binary_addition($remainder_sum, $remainder);	
		}
		
		
		$modulus;
		if($this->evaluation->larger($divider, $m2)) {
			$modulus = $m2;
		} else {	
			$m2_length = strlen($m2);
						
			$m2_subtracted = $this->get_binary_and_value($this->evaluation->subtract($m2_length, 1));
									
			if($this->evaluation->larger($divider, $m2_subtracted, false)) {
				$modulus = $m2;	
			} else {
				if($this->evaluation->larger($m2_length, $this->evaluation->result($divider_length, 3))) {
					$this->modulus_split_remainder_value = NULL;
					$m2 = $this->modulus_split($m2_subtracted, $divider, true);
				} else {
					$m2 = $this->transform_modulus($m2_subtracted, $divider);
				}
				$m2 = $this->evaluation->binary_addition($m2, "1");
				$m2 = $this->evaluation->remove_leading_zeros($m2);
				if($m2 == $divider) {
					$m2 = "0";
				}
				$modulus = $m2;
			}	
		}
		
				
		$remainder_sum = $this->evaluation->binary_addition($modulus, $remainder_sum);
				
		if($this->evaluation->larger($divider, $remainder_sum, false)) {
			return $remainder_sum;	
		}
		$modulus = $this->binary_division->divide($remainder_sum, $divider);
		
		
		return $this->evaluation->remove_leading_zeros($modulus);
	}
	
	function no_remainder($value, $divider) {
		if($value == "0" || $value == "") {
			return true;	
		}
		if($divider == $value) {
			return true;	
		}
		if($this->evaluation->larger($divider, $value, false)) {
			return false;	
		}
		$value = $this->evaluation->remove_leading_zeros($value);
		
		$value_length = strlen($value);
		$divider_length = strlen($divider);
		
		$x = $value;	
		
		
				
		$binary_value_subtraction = $divider;	
		$normalized_values = $this->normalize_divider_binary($x, $binary_value_subtraction, true);
		
		$fraction_values = $this->evaluation->fraction_values($normalized_values['fraction']);
		
		
		$max_digit = strlen($fraction_values[1])-1;
	
		$counter = 0;
		
		$binary_and_value = "";
		while($this->evaluation->larger($max_digit, $counter, false)) {
			$binary_and_value .= "1";
			$counter = $this->evaluation->add($counter, 1);
		}
		
				
		$last_x = $x;
		$stop = false;
		while($this->evaluation->larger($x, $binary_value_subtraction) && !$stop) { 			
			/*if($this->no_zeros($x)) {
				$x = $this->binary_mask($x, $binary_value_subtraction);
			}*/
									
			$periodicity_offset = $this->find_periodicity_offset($x, $binary_value_subtraction, $binary_and_value);
			if($periodicity_offset === false) {
				return false;	
			}
			$divider_translation = $periodicity_offset['divider_translation'];
			
			$maximized_offset = $this->maximize_offset($x, $periodicity_offset, $binary_value_subtraction);
			
			$x = $maximized_offset['x_value'];
			
						
			if(strpos($x, "-") !== false) {
				return false;	
			}
			
			$reduced_numerator = $this->reduce_normalized_numerator($x, $maximized_offset['divider_translation']);
			$x = $reduced_numerator;
			
			
			$x = $this->trim_zeros($x, true);
			if($x == "0" || $x == "") {
				return true;	
			}
			
			$last_x = $x;
		}
		return false;
	}
	
	
	
	
				
	function is_binary_power($value, $change_base=true) {
		$binary_value = $value;
		if($change_base) {
			$binary_value = $this->evaluation->change_base($value, 2);
		}
		$binary_value_digits = str_split($binary_value);
		$one_count = 0;
		foreach($binary_value_digits as $key => $digit) {
			if($digit == "1") {
				$one_count++;	
			}
			if($one_count > 1) {
				return false;	
			}
		}
		return true;	
	}
	
	function shorten_normalized_subtraction($subtraction_fraction, $divider=NULL) {
		$fraction_values = $this->evaluation->fraction_values($subtraction_fraction);
		$binary_numerator = $fraction_values[0];
		$binary_denominator = $fraction_values[1];
		
		$binary_numerator_digits = $this->evaluation->get_digits($binary_numerator);
		$binary_denominator_digits = $this->evaluation->get_digits($binary_denominator);	
		$cutoff_point = 0;
		foreach($binary_numerator_digits as $key => $digit) {
			if($digit == "0" && $binary_denominator_digits[$key] == "0") {
				$cutoff_point = $key+1;	
			} else {
				break;	
			}
		}
						
		$binary_numerator = substr($binary_numerator, 0, strlen($binary_numerator)-$cutoff_point);
		$binary_denominator = substr($binary_denominator, 0, strlen($binary_denominator)-$cutoff_point);
		
						
		
		return $binary_numerator."/".$binary_denominator;
		
		
	}	
	
	public function normalize_divider_binary($value, $divider, $return_as_array=false) {
		$unaltered_divider = $divider;
		$counter = 0;
		
		$divider = $this->binary_power_value(strlen($divider)-1);
		$fraction = $value."/".$divider;
		
		
		$divider_translation = $this->evaluation->binary_subtraction($unaltered_divider, $divider);						
						
		if($return_as_array) {
			return array(
				'fraction' => $fraction,
				'divider_translation' => $this->trim_zeros($divider_translation, true),
							);	
		}
		return $result;
	}
	
	function truncate_values($value, $truncate_max) {
		$truncate_max_digits = $this->evaluation->get_digits($truncate_max);
		
		$one_found = -1;
		foreach($truncate_max_digits as $key => $digit) {
			if($digit == 1) {
				$one_found = $key;	
				break;
			}
		}
		$value = substr($value, 0, strlen($value)-$one_found);
		$truncate_max = substr($truncate_max, 0, strlen($truncate_max)-$one_found);
		
		return array(
			'value' => $value,
			'truncate_max' => $truncate_max
		);
	}
	
	function find_modular_inverse($value, $mod_value, $remainder_value=1, $all_values=false) {
		if($remainder_value == 1 && !$all_values) {
			$value_binary = $this->evaluation->change_base($value, "2");
			$mod_binary = $this->evaluation->change_base($mod_value, "2");
			$fermat_count = $this->evaluation->binary_subtraction($mod_binary, "10");
			$result = $this->mod_inverse_fermat($value_binary, $fermat_count, $mod_binary);
			return $this->evaluation->change_base($result, "10", "2");
		}
		
		$counter = 1;
		$return_values = array();
		while($this->evaluation->larger($mod_value, $counter, false)) {
			$result = $this->evaluation->result($value, $counter);
			$modulus = $this->evaluation->modulus($result, $mod_value);
			if($modulus == $remainder_value) {
				if(!$all_values) {
					return $counter;	
				} else {
					$return_values[] = $counter;	
				}
			}
			
			$counter = $this->evaluation->add($counter, 1);	
		}
		if($all_values) {
			return $return_values;	
		}
		return false;
	}
	
	function find_modular_value($mod_value, $remainder_value=1) {
		$counter = $mod_value;
		
		while($this->evaluation->larger($this->evaluation->result($mod_value, $mod_value), $counter, false)) {
			$result = $counter;						
			$modulus = $this->evaluation->modulus($result, $mod_value);
			if($modulus == $remainder_value) {
				return $counter;	
			}
			
			$counter = $this->evaluation->add($counter, 1);	
		}
		return false;
	}

	function find_modular_negation($value, $mod_value) {
		$counter = 1;
		
		while($this->evaluation->larger($mod_value, $counter, false)) {
			$result = $this->evaluation->result($value, $counter);
			$modulus = $this->evaluation->modulus($result, $mod_value);
			if($modulus == "0") {
				return $counter;	
			}
			
			$counter = $this->evaluation->add($counter, 1);	
		}
		return false;
	}

	
	function find_modular_inverse_binary_general($value, $modular_value) {
		$counter = "1";		
		while($this->evaluation->larger($modular_value, $counter, false)) {
			$result = $this->evaluation->binary_multiplication($value, $counter);
			$modulus = $this->execute_modulus($result, $modular_value);
			$modulus = $this->evaluation->remove_leading_zeros($modulus);
			if($modulus == "1") {
				return $counter;	
			}
			$counter = $this->evaluation->binary_addition($counter, "1");	
		}
		return false;
	}
	
	function find_modular_inverse_binary($value, $binary_and_value) {
		$counter = "1";
		while($this->evaluation->larger($binary_and_value, $counter, false)) {
			$result = $this->evaluation->binary_multiplication($value, $counter);
			$modulus = $this->evaluation->binary_and($result, $binary_and_value, false, false);			
			$modulus = $this->trim_zeros($modulus, true);
			if($modulus == "1") {
				return $counter;	
			}
			$counter = $this->evaluation->binary_addition($counter, "1");	
		}
		return false;
	}
	
	function zero_palindrome($value) {
		$value_digits = str_split($value);
		$interlope = 1;
		foreach($value_digits as $key => $digit) {
			if($key == 0 && $digit != 1) {	
				return false;
			} else if($key == count($value_digits)-1 && $digit != 1) {
				return false;
			} else if($key != 0 && $key != count($value_digits)-1 && $digit == 1) {
				return false;	
			} else {
				return false;
			}
		}
		if($interlope == 1) {
			return true;
		}
	}
	
	function zero_repeat_palindrome($value) {
		$value_digits = str_split($value);
		$interlope = 0;
		foreach($value_digits as $key => $digit) {
			if($digit == 1 && $interlope == 0) {
				$interlope = 1;	
			} else if($digit == 0 && $interlope == 1) {
				$interlope = 0;
			} else {
				return false;	
			}
		}
		if($interlope == 1) {
			return true;	
		}
	}
	
	function zero_length_repeat_palindrome($value) {
		$value_digits = str_split($value);
		$interlope = 0;
		$zero_length = "0";
		$value_interlope = 0;
		foreach($value_digits as $key => $digit) {
			if($digit == 1 && $interlope == 0) {
				$interlope = 1;	
				$value_interlope = 1;
			} else if($digit == 0 && $interlope == 1) {
				if(isset($value_digits[$this->evaluation->add($key, "1")])) {
					if($value_digits[$this->evaluation->add($key, "1")] == "1") {
						$interlope = 0;
						if($zero_length == "0") {
							$zero_length = $this->evaluation->subtract($key, "1");	
						} else {
							$zero_length_comparison = $this->evaluation->subtract($key, "1");		
							if($zero_length != $zero_length_comparison) {
								return false;	
							}
						}
					} else {
						return false;	
					}
				} else {
					return false;	
				}
				$value_interlope = 0;
			} else {
				return false;	
			}
		}
		if($interlope == 1) {
			return $zero_length;	
		}	
	}
	
	function get_binary_and_value($max_digit) {
		$counter = "0";
		$binary_and_value = "";
		while($this->evaluation->larger($max_digit, $counter, false)) {
			$binary_and_value .= "1";
			$counter = $this->evaluation->add($counter, 1);
		}
		return $binary_and_value;	
	}
	
	function maximize_offset_general($x, $periodicity_offset_values, $binary_value_subtraction, $general=false) {
		$periodicity_offset = $periodicity_offset_values['offset'];
		
		$divider_power = $periodicity_offset_values['divider_translation'];	
		$divider_value = $this->binary_power_value($divider_power);
		$next_divider_power = $divider_power;
		$stop = false;
		
		
		$last_divider_value = $divider_power;
		$last_offset = $periodicity_offset;
				
		$last_x_value = $x;		
		$x_value = $last_x_value;
		while(!$stop) {
			$divider_value = $this->binary_power_value($this->evaluation->add($next_divider_power, "1"));
			$divider_value_multiplication = $divider_value;						
						
			$x_value = $this->evaluation->binary_subtraction($x_value, $periodicity_offset);			
		
			$binary_and_value = $this->get_binary_and_value($next_divider_power);
			$periodicity_remainder = $this->evaluation->binary_and($x_value, $binary_and_value, false, false);
			$periodicity_remainder = $this->trim_zeros($periodicity_remainder, true);
			
						
			if($this->evaluation->larger($x, $periodicity_offset) && $periodicity_remainder == "") {
				$last_x_value = $x_value;
				
				$last_divider_value = $next_divider_power;
				$last_offset = $periodicity_offset;			
			} else {
				$stop = true;
			}	
			if($periodicity_remainder != "" && !$stop) {
				return $this->maximize_offset($x_value, array(
					'offset' => $last_offset,
					'divider_translation' => $last_divider_value
				), $binary_value_subtraction);	
			}
			$next_divider_power = $this->evaluation->add($next_divider_power, 1);
		}
		
		return array(
			'offset' => $last_offset,
			'divider_translation' => $last_divider_value,
			'x_value' => $last_x_value
		);
	}
	
	function maximize_offset($x, $periodicity_offset_values, $binary_value_subtraction) {
		$periodicity_offset = $this->evaluation->binary_multiplication($periodicity_offset_values['offset'], $binary_value_subtraction);
		$divider_power = $periodicity_offset_values['divider_translation'];	
		$divider_value = $this->binary_power_value($divider_power);
		$next_divider_power = $divider_power;
		$stop = false;
		
		
		
		$last_divider_value = $divider_power;
		$last_offset = $periodicity_offset;
				
		$x_value = $this->evaluation->binary_subtraction($x, $periodicity_offset);
		
		while(!$stop) {
			$divider_value = $this->binary_power_value($this->evaluation->add($next_divider_power, "1"));
			$divider_value_multiplication = $divider_value;						
			$periodicity_offset = $this->evaluation->binary_multiplication($periodicity_offset, $divider_value_multiplication);
						
		
			$binary_and_value = $this->get_binary_and_value($next_divider_power);
			$periodicity_remainder = $this->evaluation->binary_and($x_value, $binary_and_value, false, false);
			$periodicity_remainder = $this->trim_zeros($periodicity_remainder, true);
			
						
			if($this->evaluation->larger($x, $periodicity_offset) && $periodicity_remainder == "") {
				$x_value = $this->evaluation->binary_subtraction($x_value, $periodicity_offset);
				$last_divider_value = $next_divider_power;
				$last_offset = $periodicity_offset;			
			} else {
				$stop = true;
			}	
			if($periodicity_remainder != "" && !$stop) {
				return $this->maximize_offset($x_value, array(
					'offset' => $last_offset,
					'divider_translation' => $last_divider_value
				), $binary_value_subtraction);	
			}
			$next_divider_power = $this->evaluation->add($next_divider_power, 1);
		}
		return array(
			'offset' => $last_offset,
			'divider_translation' => $last_divider_value,
			'x_value' => $x_value
		);
	}
	
	function find_periodicity_offset_general($x, $value, $binary_and_value) {
		$last_offset = "-1";
		$valid = true;
		while($valid) {
			$modulus_value = $this->evaluation->binary_and($x, $binary_and_value, false, false);	
			
									
			$found = false;
			$counter = "0";
			$offset = "-1";
			$interval = "0";
			$periodicity_value = "0";			
			$maximum_search_value = $this->evaluation->binary_multiplication($value, "111");
			while(!$found && $this->evaluation->larger($maximum_search_value, $counter)) { 				
				$periodicity_value = $this->evaluation->binary_addition($periodicity_value, $value);
				$modulus = $this->evaluation->binary_and($periodicity_value, $binary_and_value, false, false);		
				if($modulus == $modulus_value) {
					echo "--found--\n";
					if($offset == "-1") {
						$offset = $counter;
					} else {
						$interval = $this->evaluation->binary_subtraction($counter, $offset);
						$found = true;	
					}
				}
				$counter = $this->evaluation->binary_addition($counter, "1");			}
			
			if($offset == "-1") {
				$valid = false;	
			} else {
				$last_offset = $offset;	
				$binary_and_value .= "1";
			}
		}
		$divider_translation = strlen($binary_and_value);
		
		
		if($last_offset == "-1") {
			return false;	
		}
		
		return array(
			'offset' => $this->evaluation->binary_addition($last_offset, 1), 			
			'divider_translation' => '1',						'
			interval' => $interval
		);
	}
	
	function find_periodicity_offset($x, $value, $binary_and_value) {
		$last_offset = "-1";
		$valid = true;
		$modulus_value = $this->evaluation->binary_and($x, $binary_and_value, false, false);
		
								
		$found = false;
		$counter = "0";
		$offset = "-1";
		$interval = "0";
		$periodicity_value = "0";
		$maximum_search_value = $this->evaluation->binary_multiplication($value, "11");
		while(!$found && $this->evaluation->larger($maximum_search_value, $counter)) {
			$periodicity_value = $this->evaluation->binary_addition($periodicity_value, $value);
			$modulus = $this->evaluation->binary_and($periodicity_value, $binary_and_value, false, false);												
			if($modulus == $modulus_value) {
				$offset = $counter;
				$found = true;
			}
			$counter = $this->evaluation->binary_addition($counter, "1");			
		}
		
		
		$last_offset = $offset;	
				
		$divider_translation = strlen($binary_and_value);
		
		if($last_offset == "-1") {
			return false;	
		}
		
		return array(
			'offset' => $this->evaluation->binary_addition($last_offset, 1), 			
			'divider_translation' => $divider_translation,
		);
	}
		
	function binary_power_value($power) {
		$value = "1";
		$counter = 0;
		while($this->evaluation->larger($power, $counter, false)) {
			$value .= "0";
			$counter = $this->evaluation->add($counter, 1);	
		}
		return $value;
	}
	
	function reduce_normalized_numerator($subtraction_value, $divider_translation) {
		$subtraction_value = $this->evaluation->bit_shift_right($subtraction_value, $this->evaluation->subtract($divider_translation, 1), false, true);
		if($subtraction_value === false) {
			return false;	
		}
		return $subtraction_value;	
	}
	
	function reduce_normalized_numerator_alt($x, $value, $periodicty_values, $divider_translation) {
						
		$offset = $periodicty_values['offset'];
		
		$subtraction_value = $this->evaluation->binary_multiplication($offset, $value);
		
		
		$subtraction_value = $this->evaluation->binary_subtraction($x, $subtraction_value);
		
		
		
		$subtraction_value = $this->evaluation->bit_shift_right($subtraction_value, $this->evaluation->subtract($divider_translation, 1), false);
		
		
		return $subtraction_value;	
	}
	private $modulus_split_remainder_value = NULL;
		
	function modulus_split($x, $binary_value_subtraction, $return_remainder=false, $value_part_remainder=NULL) {
		$palindrome_modulus = $this->binary_power_modulus_sub_palindrome($x, $binary_value_subtraction);
		if($palindrome_modulus !== false) {
			return $this->execute_modulus($palindrome_modulus, $binary_value_subtraction);	
		}

		$value_length = strlen($x);
		$divider_length = strlen($binary_value_subtraction);
		$modulus_length;
		$modulus_length = 1+strlen($binary_value_subtraction);
		
		$min_remainder_length = $modulus_length;
		while(!$this->is_binary_power($modulus_length, true)) {
			$modulus_length = $this->evaluation->add($modulus_length, "1");	
		}
		$modulus_length_binary = $this->evaluation->change_base($modulus_length, "2");
		$maximum = $this->evaluation->change_base($value_length, "2");				
		
		$multiplication_count = $this->evaluation->subtract(strlen($maximum), strlen($modulus_length_binary));
		
		
		$subtraction_value = $this->evaluation->bit_shift($modulus_length_binary, $multiplication_count, false);
		$remainder_length = $this->evaluation->binary_subtraction($maximum, $subtraction_value);
		$remainder_length = $this->evaluation->change_base($remainder_length, "10", "2");
		$remainder_length_value = $remainder_length;
						
		$sub_call = false;
		$x_remainder = "0";
		$max_value = "0";
		
		$value_part = substr($x, 0, $modulus_length);
		
		$remainder = "0";
		if($value_part_remainder === NULL) {
			$no_remainder = false;
			$remainder = $this->execute_modulus($value_part, $binary_value_subtraction);											
		} else {
			$remainder = $value_part_remainder;
			$value_part_remainder = $remainder;
		}
		
		$remainder_modulus_value = $remainder;
		
		$modulus_sum = $remainder_modulus_value;
		
		$value_split_value = $modulus_length;	
		
		
		
		$binary_multiplier_modulus = $this->execute_modulus($this->binary_power_value($value_split_value), $binary_value_subtraction);
		
		$binary_multiplier_modulus_value = "1";
		
		$split_length = $min_remainder_length;
		
		$max_value = "0";
		$x_remainder = "0";
		
		
		if($this->evaluation->larger($remainder_length, $this->evaluation->result($modulus_length, 2))) {
			$x_remainder_substring = substr($x, 0, $remainder_length);
			$sub_call = $this->modulus_split($x_remainder_substring, $binary_value_subtraction, true, $remainder);
		} else {			
			if($this->evaluation->larger($remainder_length, $split_length)) { 
				$split_division = $this->evaluation->execute_divide($remainder_length, $split_length);
				$max_value = $remainder_length;
				$value_split_value = $split_length;								
				$remainder_length = $this->evaluation->fraction_values($split_division['remainder'])[0];
				if($this->evaluation->larger($min_remainder_length, $remainder_length)) {
					$remainder_length += $split_length;	
				}
		
				$max_value = $this->evaluation->subtract($remainder_length_value, $remainder_length);
			}
			if($remainder_length != "0") {
				$x_remainder = substr($x, 0, $remainder_length);
			}
		}
			
		$counter = "0";
		while($this->evaluation->larger($multiplication_count, $counter, false)) {
			$binary_multiplier_modulus_value = $this->evaluation->binary_multiplication($binary_multiplier_modulus, $binary_multiplier_modulus_value);
			$binary_multiplier_modulus_value = $this->execute_modulus($binary_multiplier_modulus_value, $binary_value_subtraction);
			
			$binary_multiplier_modulus = $binary_multiplier_modulus_value;
			
			$current_modulus_value = $this->evaluation->binary_multiplication($remainder_modulus_value, $binary_multiplier_modulus_value);
			$current_modulus_value = $this->execute_modulus($current_modulus_value, $binary_value_subtraction);
			
			$modulus_sum = $this->evaluation->binary_addition($modulus_sum, $current_modulus_value);
			$modulus_sum = $this->execute_modulus($modulus_sum, $binary_value_subtraction);
			$remainder_modulus_value = $modulus_sum;
			
			$counter = $this->evaluation->add($counter, "1");
		}
		if($sub_call === false) {
			$value_split_value = "0";
			if($max_value != "0") {		
				$binary_multiplier_modulus_value = $this->evaluation->binary_multiplication($binary_multiplier_modulus, $binary_multiplier_modulus_value);
				$binary_multiplier_modulus_value = $this->execute_modulus($binary_multiplier_modulus_value, $binary_value_subtraction);
				$value_part = substr($x, 0, $split_length);
				$remainder_modulus_value = $this->execute_modulus($value_part, $binary_value_subtraction);
				
												
				$binary_multiplier_modulus_addition = $this->execute_modulus($this->binary_power_value($split_length), $binary_value_subtraction);
				
				
				while($this->evaluation->larger($max_value, $value_split_value, false)) {
					if($value_split_value != "0") {
						$binary_multiplier_modulus_value = $this->evaluation->binary_multiplication($binary_multiplier_modulus_addition, $binary_multiplier_modulus_value);
						$binary_multiplier_modulus_value = $this->execute_modulus($binary_multiplier_modulus_value, $binary_value_subtraction);
					}
					$current_modulus_value = $this->evaluation->binary_multiplication($remainder_modulus_value, $binary_multiplier_modulus_value);
					$current_modulus_value = $this->execute_modulus($current_modulus_value, $binary_value_subtraction);
					$modulus_sum = $this->evaluation->binary_addition($modulus_sum, $current_modulus_value);
					$modulus_sum = $this->execute_modulus($modulus_sum, $binary_value_subtraction);
					$value_split_value = $this->evaluation->add($value_split_value, $split_length);	
				}
			} else {
				$binary_multiplier_modulus_addition = $binary_multiplier_modulus;	
			}
			
			if($x_remainder != "0") {		
				$x_remainder_value = $this->execute_modulus($x_remainder, $binary_value_subtraction);
				$binary_multiplier_modulus_value = $this->evaluation->binary_multiplication($binary_multiplier_modulus_addition, $binary_multiplier_modulus_value);
				$binary_multiplier_modulus_value = $this->execute_modulus($binary_multiplier_modulus_value, $binary_value_subtraction);
				$x_remainder_value = $this->evaluation->binary_multiplication($binary_multiplier_modulus_value, $x_remainder_value);
				$x_remainder_value = $this->execute_modulus($x_remainder_value, $binary_value_subtraction);
				
				$modulus_sum = $this->evaluation->binary_addition($x_remainder_value, $modulus_sum);
			}
		} else {
			$binary_multiplier_modulus_value = $this->evaluation->binary_multiplication($binary_multiplier_modulus, $binary_multiplier_modulus_value);
			$binary_multiplier_modulus_value = $this->execute_modulus($binary_multiplier_modulus_value, $binary_value_subtraction);
				
			$sub_call = $this->evaluation->binary_multiplication($sub_call, $binary_multiplier_modulus_value);
			$sub_call = $this->execute_modulus($sub_call, $binary_value_subtraction);
			$modulus_sum = $this->evaluation->binary_addition($modulus_sum, $sub_call);	
		}

		$result = $this->execute_modulus($modulus_sum, $binary_value_subtraction);
		return $result;
	}
	
	function execute_fermat_quotient_modulus($value_binary, $value_decimal) {
		
		$binary_value_subtraction = $value_binary;
		$counter = 0;
		
		$n = $this->evaluation->subtract($value_decimal, 1);
		
		
		if($this->zero_repeat_palindrome($binary_value_subtraction)) {
			$length = strlen($binary_value_subtraction);
			$length = $this->evaluation->add($length, 1);
			
			$numerator_length = $n;
			
			$modulus = $this->evaluation->modulus($numerator_length, $length);
			
						if($modulus != 0) {
				return false;	
			}
			return true;	
		}
		
		
		$subtraction_fraction_numerator = $binary_value_subtraction;
		if($this->no_zeros($subtraction_fraction_numerator)) {
			if($this->zero_palindrome($binary_value_subtraction)) {
				$length = $n;
				$length = $this->evaluation->subtract($this->evaluation->add($length, $length), 2);
				
				$numerator_length = strlen($subtraction_fraction_numerator);
				
				$division = $this->evaluation->execute_divide($numerator_length, $length);
				if($this->evaluation->fraction_values($division['remainder'])[0] == 0) {
					return true;	
				} else {
					return false;	
				}
			} else if($this->no_zeros($binary_value_subtraction)) {
				$binary_value_subtraction_digit_count = strlen($binary_value_subtraction);
				$value_digit_count = $n;
				
				if($this->evaluation->modulus($value_digit_count, $binary_value_subtraction_digit_count) == 0) {
					return true;	
				}
				return false;	
			}
		}
		return true;
		
	}
		
	function array_to_decimal($arr) {
		foreach($arr as $key => $value) {
			if($key != 'divider_translation') {
				$decimal = $this->evaluation->change_base($value, "10", "2");
				$arr[$key] = $decimal;	
			}
		}
		return $arr;
	}
	
	function no_zeros($value) {
		if(!is_array($value)) {
			return !strpos($value, "0");
		}
		foreach($value as $key => $digit) {
			if($digit == 0) {
				return false;	
			}
		}
		return true;
	}
	
	function just_zeros($value) {
		if(is_array($value)) {
			foreach($value as $digit) {
				if($digit == 1) {
					return false;	
				}
			}
			return true;
		} else {
			if(strpos($value, "1") === false) {
				return true;	
			}
			return false;
		}
	}
	
	function trim_zeros($value, $string=false, $reverse=false) {
		if($reverse) {
			$value = array_reverse($value);	
		}
		if(!is_array($value)) {
			$value = str_split($value);	
		}
		$one_found = false;
		$result = array();
		foreach($value as $key => $digit) {
			if($digit == 1) {
				$one_found = true;	
			}
			if($one_found) {
				$result[] = $digit;
			}
		}
		if($string) {
			return implode("", ($result));	
		}
				if($reverse) {
			$value = array_reverse($result);	
		}
		return $result;
	}
	
	function verify_binary_mask($value) {
		if($this->just_zeros($value)) {
			return true;	
		}
		$value = array_reverse($value);
		$one_found = false;
		foreach($value as $digit) {
			if($digit == -1 && $one_found == false) {
				return false;	
			}
			if($digit == 1) {
				$one_found = true;
				return true;	
			}
		}
		return true;
	}
	
	private $repeat_pattern_restart_length = 0;
	
	function verify_repeat_pattern($digits, $subtraction_digits) {
		$counter = 0;
		$reverse_counter = floor(count($digits)/2) + $counter;
		if(count($digits) < count($subtraction_digits)*2) {
			return false;	
		}
		
		while($counter < count($digits)/2) {
			if($digits[$counter] != $digits[$reverse_counter]) {
				return false;	
			}
			$counter++;
			$reverse_counter = floor(count($digits)/2) + $counter;
		}
		$this->repeat_pattern_restart_length = count($digits)/2;
		return true;
	}
	
	function maximum_coverage($value_digits, $subtraction_digits) {
		$repeat_pattern = array();
		
		foreach($value_digits as $key => $digit) {
			$repeat_pattern[] = $digit;
			if($this->verify_repeat_pattern($repeat_pattern, $subtraction_digits)) {
				break;	
			}
		}
		
		$coverage_count = array();
		$index = 0;
		while($index < count($repeat_pattern)/2+1) {
			foreach($subtraction_digits as $key => $digit) {
				$repeat_pattern_digit = $repeat_pattern[$index+$key];
				if($repeat_pattern_digit == $digit) {
					if(!isset($coverage_count[$index])) {
						$coverage_count[$index] = 0;	
					}
					$coverage_count[$index]++;
				}
			}
			$index++;
		}
		
		$maximum_offset = array_keys($coverage_count, max($coverage_count))[0];
		
		return $maximum_offset;
	}
	
	function subtract_maximum_coverage_pattern($value_digits, $subtraction_digits, $offset_value) {
		$offset_value += $this->repeat_pattern_restart_length;
		while($offset_value <= count($value_digits) - count($subtraction_digits)) {
			foreach($subtraction_digits as $key => $digit) {
				$key = $key + $offset_value;
				if($value_digits[$key] == "1" && $digit == "1") {
					$value_digits[$key] = "0";	
				} else if($value_digits[$key] == "0" && $digit == "1") {
					$value_digits[$key] = "-1";	
				} 
			}
			$offset_value += $this->repeat_pattern_restart_length;
		}
		return $value_digits;
	}
	
	
	
	private $binary_value_stored = NULL;
	public $division_value = array();
	
	private $stop_offset = 0;
	
	function binary_mask($value_digits, $subtraction_digits) {
		
		$value_digits_reverse = $value_digits;
		$value_digits = $this->evaluation->get_digits($value_digits);
		$this->binary_value_stored = $value_digits;
		$subtraction_digits = $this->evaluation->get_digits($subtraction_digits);
		$start_index = 0;
		foreach($value_digits as $key => $digit) {
			if($digit == "1") {
				$one_found = true;
				$start_index = $key;
				break;	
			}
		}
						
		$stop = false;
		$repeat_value = "";
		$result_value = "";
		
		$next_stop_index = 0;
		while($start_index <= count($value_digits) - count($subtraction_digits)) {			
			$last_position = -1;
			foreach($subtraction_digits as $key => $digit) {
				$key = $start_index + $key;
				if($key < count($value_digits)) {
					if($value_digits[$key] == "1" && $digit == "1") {
						$value_digits[$key] = "0";	
					} else if($value_digits[$key] == "0" && $digit == "1") {
						$value_digits[$key] = "-1";	
					} 
				} else {
					if($digit == "1") {
						$value_digits[] = "-1";
					} else if($digit == "0") {
						$value_digits[] = "0";
					}	
				}
				$last_position = $key;
			}
			$start_index = $last_position+1;
			$next_stop_index = $start_index;
		}
		
		return $this->trim_zeros(array_reverse($value_digits), true);
	}
	
	function binary_mask_arr($value_digits, $subtraction_digits) {
		
		$value_digits_reverse = $value_digits;
		$value_digits = array_reverse($value_digits);
		$this->binary_value_stored = $value_digits;
		$subtraction_digits = array_reverse($subtraction_digits);		
		$start_index = 0;
		foreach($value_digits as $key => $digit) {
			if($digit == "1") {
				$one_found = true;
				$start_index = $key;
				break;	
			}
		}
						
		$stop = false;
		$repeat_value = "";
		$result_value = "";
		
		$next_stop_index = 0;
		while($start_index <= count($value_digits) - count($subtraction_digits)) {			
			$last_position = -1;
			foreach($subtraction_digits as $key => $digit) {
				$key = $start_index + $key;
				if($key < count($value_digits)) {
					if($value_digits[$key] == "1" && $digit == "1") {
						$value_digits[$key] = "0";	
					} else if($value_digits[$key] == "0" && $digit == "1") {
						$value_digits[$key] = "-1";	
					} 
				} else {
					if($digit == "1") {
						$value_digits[] = "-1";
					} else if($digit == "0") {
						$value_digits[] = "0";
					}	
				}
				$last_position = $key;
			}
			$start_index = $last_position+1;
			$next_stop_index = $start_index;
		}
		
		return $this->trim_zeros(array_reverse($value_digits));
	}
	
	function invert_negatives($value, $string=false) {
		if($string) {
			$value = str_split($value);
		}
		$last_one = -1;
		$key = 0;
		foreach($value as $key => $digit) {
			$digit = $value[$key];
			if($digit == 1) {
				$last_one = $key;	
			}
			if($digit == "-1" && $last_one != -1) {
				$value[$last_one] = 0;
				$counter = $last_one+1;
				while($counter <= $key) {
					$value[$counter] = 1;
					$last_one = $counter;	
					$counter++;
				}
			}
			$key++;
		}
		if($string) {
			return implode("", ($value));	
		}
		return $this->trim_zeros($value);	
	}
	
	
	
	function binary_mask_alt($value_digits, $subtraction_digits) {
		$value_digits = array_reverse($value_digits);
		$subtraction_digits = array_reverse($subtraction_digits);
		$start_index = 0;
		foreach($value_digits as $key => $digit) {
			if($digit == 1) {
				$one_found = true;
				$start_index = $key;
				break;	
			}
		}
		
		$stop = false;
		while(!$stop) {
			if($start_index <= count($value_digits) - count($subtraction_digits)) {
								
				foreach($subtraction_digits as $key => $digit) {
					$key = $start_index + $key;
					if($value_digits[$key] == "1" && $digit == "1") {
						$value_digits[$key] = "0";	
					} else if($value_digits[$key] == "0" && $digit == "1") {
						$value_digits[$key] = "-1";	
					}
				}
			} else {
				$stop = true;	
			}
			$one_found = false;
			foreach($value_digits as $key => $digit) {
				if($digit == "1") {
					$one_found = true;
					$start_index = $key;
				}
				if($one_found && $digit == "-1") {
					$stop = true;
					$start_index = count($value_digits);	
				}
			}
			if(!$one_found) {
				$stop = true;	
			}
			
		}
		return $this->trim_zeros(array_reverse($value_digits));	
	}	
}


?>