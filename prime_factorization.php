<?

namespace NumEval;

class prime_factorization {
	
	private $evaluation;
	private $binary_modulus;
	
	function __construct($evaluation) {
		$this->evaluation = $evaluation;
		$this->binary_modulus = new binary_modulus($this->evaluation, "0");	
	}
	
	private $prime_factors = array();
	private $limit = 0;
		
	function digit_sum_division($value) {
		$sum = $this->evaluation->digit_sum($value);
				$division = $this->evaluation->execute_divide($value, $sum);		
		
		$sum_division = $this->evaluation->execute_divide($sum, $value);	
		if($this->evaluation->fraction_values($sum_division['remainder'])[0] != 0) {
			return false;	
		}
		$division = $this->evaluation->execute_divide($division, $sum_division);
				if($this->evaluation->fraction_values($division['remainder'])[0] == 0) {
			return $this->evaluation->subtract($sum_division['value'], "1");	
		}
		return false;	
	}
	
	function increment_division($value) {
		$last_digit = $this->evaluation->get_digits($value)[0];
		$divider = $last_digit;
		if($last_digit == "1") {
			$divider = "2";	
		}
		$remainder = "0";
		while($this->evaluation->larger($value, $remainder)) {
			$division = $this->evaluation->execute_divide($value, $divider);	
			if($this->evaluation->fraction_values($division['remainder'])[0] == 0) {
				return $divider;	
			}
			$division_value = $this->evaluation->subtract($division['value'], "1");
			if($division_value == "1") {
				return false;	
			}
			$modulus = $this->evaluation->modulus($value, $division_value);
			if($modulus == 0) {
				return $division_value;	
			}
			
			$remainder = $this->evaluation->subtract($value, $this->evaluation->result($division_value, $divider));
			if($remainder == "1") {
				$remainder = "2";
				
			}
			$divider = $remainder;
					}
		return false;
	}
			
	function factor($value) {
		if($value == "0") {
			return false;	
		}
		if($this->evaluation->prime($value)) {
			$this->prime_factors[] = $value;
			return $this->prime_factors;	
		}
		$last_digit = $this->evaluation->get_digits($value)[0];
		$digit_sum = $this->evaluation->digit_sum($value);
		if($last_digit == "5" || $last_digit == "0") {
			$this->prime_factors[] = "5";
			$division = $this->evaluation->execute_divide($value, "5")['value'];	
			return $this->factor($division);		
		} else if($this->evaluation->even($value)) { 	
			$this->prime_factors[] = "2";
			$division = $this->evaluation->execute_divide($value, "2")['value'];
			return $this->factor($division);	
		} else if($this->evaluation->modulus($digit_sum, "3") == 0) {
			$this->prime_factors[] = "3";
			$division = $this->evaluation->execute_divide($value, "3")['value'];	
			return $this->factor($division);	
		} else {		
			$a['root'] = $this->evaluation->root($value, "2");
			if($a['root'] === false) {
				$a['root'] = $this->evaluation->root_closest_result;	
			}
			$a['value'] = $this->evaluation->result($a['root'], $a['root']);
			
			$parallel_factors = $this->parallel_factors($value, $a['root']);
			if($parallel_factors !== false) {
				$this->factor($parallel_factors[0]);
				return $this->factor($parallel_factors[1]);
			} else if($a['value'] == $value) {
				if($this->evaluation->prime($a['root'])) {
					$this->prime_factors[] = $a['root'];
					$this->prime_factors[] = $a['root'];
					return $this->prime_factors;
				} else {
					$this->factor($a['root']);
					return $this->factor($a['root']);	
				}
			} else {
				$value_valid = false;
				$x = $a['root'];
				
				$term_a = $this->factor_sub_binary($value);
				if($term_a === false) {
					$term_a = $this->prime_root_reduction_aux_alt($value);	
				}
				$term_b = $this->evaluation->execute_divide($value, $term_a)['value'];
				
								
				$this->factor($term_a);
				return $this->factor($term_b);
			}
		}
		return $this->prime_factors;
	}
	
	function find_midpoint_rational($value, $root) {
		$value_parity_modulus = $this->evaluation->modulus($value, "4");
		$odd = true;
		if($value_parity_modulus == "3") {
			$odd = false;
		}
		
		$x = $root;
				
		if($this->evaluation->even($x) && $odd) {
			$x = $this->evaluation->subtract($root, "1");	
		} else if(!$this->evaluation->even($x) && !$odd) {
			$x = $this->evaluation->subtract($root, "1");	
		}
		
		$square_value = "2";		$previous_square = $this->evaluation->result($square_value, $square_value);
		$square_value = $this->evaluation->add($square_value, "1");
		$last_square = $this->evaluation->result($square_value, $square_value);
		$square_value = $this->evaluation->add($square_value, "1");
		
		$value_subtracted = $this->evaluation->subtract($value, "4");
		while(true) {
			$x_squared = $this->evaluation->result($x, $x);
			$x4 = $this->evaluation->result("4", $x);
			$root_value = $this->evaluation->add($x_squared, $x4);
			$root_value = $this->evaluation->subtract($root_value, $value_subtracted);
			
						
			while($this->evaluation->larger($root_value, $last_square, false)) {
				$square = $this->evaluation->result("2", $last_square);
				$square = $this->evaluation->subtract($square, $previous_square);
				$square = $this->evaluation->add($square, "2");
				
				$previous_square = $last_square;
				$last_square = $square;
			}
			
			
			if($root_value == $last_square) {				
				$root_value_root = $this->evaluation->root($root_value, "2");
				if($root_value_root !== false) {
					$midpoint_value = $this->evaluation->subtract($x, $root_value_root);
					$midpoint_value = $this->evaluation->absolute($midpoint_value);
					if($this->evaluation->larger($x, $midpoint_value, false)) {
						$store = $midpoint_value;
						$midpoint_value = $x;
						$x = $store;	
					}
					$midpoint_value = $this->evaluation->add($midpoint_value, "2");
					$x = $this->evaluation->add($x, "2");
					$b = $x;
					$x = $this->evaluation->subtract($midpoint_value, $x);
					$x = $this->evaluation->absolute($x);
					return array($midpoint_value, $x, $b);	
				}
			}
			$x = $this->evaluation->add($x, "2");
								}
	}
	
	function find_midpoint_odd($value, $root) {
		$root_ceil = $this->evaluation->add($root, "1");
		$q = $this->evaluation->subtract($this->evaluation->result($root_ceil, $root_ceil), $value);
		
		$r1 = "1";
		$r2 = $this->evaluation->add($this->evaluation->result($root, "2"), "1");
		
		$last_q = NULL;
		do {
			$r2 = $this->evaluation->add($r2, "2");
			
			$q = $this->evaluation->subtract($q, $r1);
			$r1 = $this->evaluation->add($r1, "2");
		
			
			$last_q = $q;
			
			$q_set = $this->evaluation->subtract($r1, $q);
			if($this->evaluation->larger($r1, $q)) {
				$root = $this->evaluation->add($root, "1");
				$q = $this->evaluation->add($this->evaluation->result($root, "2"), "1");
				$q = $this->evaluation->add($q, $last_q);	
				
			}
		} while($q_set != "0");
		
				
		$b = $this->evaluation->subtract($this->evaluation->result($root, $root), $value);
		$b = $this->evaluation->root($b, "2");
		
		return array($root, $b);
	}
		
	function find_midpoint($value, $root) {
		$x = $root;
		if($this->evaluation->even($x)) {
			$x = $this->evaluation->subtract($root, "1");	
		}
		
		$value_subtracted = $this->evaluation->subtract($value, "4");
		while(true) {
			$x_squared = $this->evaluation->result($x, $x);
			$x4 = $this->evaluation->result("4", $x);
			$root_value = $this->evaluation->add($x_squared, $x4);
			$root_value = $this->evaluation->subtract($root_value, $value_subtracted);
			
			$root_value_modulus = $this->evaluation->modulus($root_value, "4");
			
			if($root_value_modulus == "0" || $root_value_modulus == "1") {
				$root_value_root = $this->evaluation->root($root_value, "2");
				if($root_value_root !== false) {
					$midpoint_value = $this->evaluation->subtract($x, $root_value_root);
					
					$midpoint_value = $this->evaluation->add($midpoint_value, "2");
					$x = $this->evaluation->add($x, "2");
					$b = $x;
					$x = $this->evaluation->subtract($midpoint_value, $x);
					return array($midpoint_value, $x, $b);	
				}
			}
			$x = $this->evaluation->add($x, "2");
		}
	}
	
	function find_value_iterative_reverse($value, $root) {
		$running_value = $value;
		$interval = $root;
		$stop = false;
		
		$last_coefficient = NULL;
		$coefficient_addition = NULL;
		
		$last_constant = NULL;
		$constant_addition = NULL;
		$constant;
		
		$last_running_value_addition = NULL;
		$running_value_addition = NULL;
		$running_value_subtraction = NULL;
		$last_depth = NULL;
		
		while(!$stop) {
			if($last_coefficient == NULL) {
				$coefficient = $this->evaluation->add($this->evaluation->result($interval, "2"), "2");
			} else {
				$coefficient = $this->evaluation->subtract($coefficient, "2");	
			}
			
			if($constant_addition == NULL) {
				$a_1 = $this->evaluation->add($interval, "3");
				$constant = $this->evaluation->result($a_1, $a_1);
				$constant = $this->evaluation->subtract($constant, "4");
				
				if($last_constant === NULL) {
					$last_constant = $constant;
				} else {
					$constant_addition = $this->evaluation->subtract($last_constant, $constant);
				}
			} else {
				$constant_addition = $this->evaluation->subtract($constant_addition, "2");
				$constant = $this->evaluation->subtract($constant, $constant_addition);	
			}
			$interval_1 = $this->evaluation->add($interval, "1");
			$depth = 0;
			
			if($running_value_addition === NULL) {
				$running_value = $value;
				$last_running_value = $running_value;
				while($this->evaluation->larger($running_value, $interval_1)) {
					$last_running_value = $running_value;
					$running_value = $this->evaluation->subtract($running_value, $constant);
					$depth = $this->evaluation->add($depth, "1");
				}
				$depth = $this->evaluation->subtract($depth, "1");
				$running_value = $last_running_value;
				$running_value_subtraction = "1";
				if($depth != 0) {
					$running_value_subtraction = $this->evaluation->result("2", $depth);
				}
				if($depth != 0 && $depth == $last_depth) {
					if($last_running_value_addition === NULL) {
						$last_running_value_addition = $running_value;
					} else {
						$running_value_addition = $this->evaluation->subtract($running_value, $last_running_value_addition);	
						$running_value_addition = $this->evaluation->subtract($running_value_addition, $running_value_subtraction);	
																	}
				}
				$last_depth = $depth;
			} else {
				$running_value = $this->evaluation->add($running_value, $running_value_addition);
				$running_value_addition = $this->evaluation->subtract($running_value_addition, $running_value_subtraction);	
							}
			
			
			if($this->evaluation->modulus($running_value, $interval_1) == "0") {
				$running_value_value = $this->evaluation->subtract($value, $constant);
				$result = $this->evaluation->execute_divide($running_value_value, $coefficient); 
				if($this->evaluation->fraction_values($result['remainder'])[0] == 0) {
					$result = $result['value'];
					$result = $this->evaluation->add($result, "2");
					return array('result' => $result, 'interval' => $interval);
				}
				$stop = true;	
			}
						$interval = $this->evaluation->subtract($interval, "1");
						
			if($this->evaluation->larger($running_value, $constant)) {
								$running_value_addition = NULL;	
				$running_value_subtraction = NULL;
				$last_running_value_addition = NULL;
			}
		}
		$interval = $this->evaluation->add($interval, "1");
		return $interval;
	}
		
	function find_b($value) {
		$n = $this->evaluation->subtract($value, "15");
		$x = $this->evaluation->execute_divide($n, "6")['value'];
		$x = $this->evaluation->add($x, "2");
		return $x;	
	}
	
	function find_interval_value($value, $b) {
		$root = $this->evaluation->add($this->evaluation->result($b, $b), $value);
		$root = $this->evaluation->root($root, "2");
		$root = $this->evaluation->subtract($root, $b);
		return $root;	
	}
	
	function find_value($value, $interval) {
		$stop = false;
		$coefficient = $this->evaluation->add($this->evaluation->result($interval, "2"), "2");
		
		$a_1 = $this->evaluation->add($interval, "3");
		$constant = $this->evaluation->result($a_1, $a_1);
		$constant = $this->evaluation->subtract($constant, "4");
		
	
		$result = $this->evaluation->subtract($value, $constant);
		$result = $this->evaluation->execute_divide($result, $coefficient);
		if($this->evaluation->fraction_values($result['remainder'])[0] == 0) {
			$stop = true;	
		} else {
			return false;	
		}
		$result = $result['value'];
		$result = $this->evaluation->add($result, "2");
		return array('result' => $result, 'interval' => $interval);
	}
	
	function find_a($value) {
		$value_2 = $this->evaluation->result($value, $value);
		$rational_roots = $this->evaluation->list_rational_roots($value, $value_2);
		$last_root = NULL; 	
		foreach($rational_roots as $rational_root) {
			if($last_root != NULL) {
				$difference = $this->evaluation->subtract($rational_root['value'], $value);
				$difference_root = $this->evaluation->root($difference, "2");
				if($difference_root !== false) {
					return array(
						'a' => $rational_root,
						'b' => array('value' => $difference, 'root' => $difference_root)
					);
				}
			}
			$last_root = $rational_root;	
		}
	}
	
	function parallel_factors($value, $root) {
		$root = $this->evaluation->add($root, "1");	
		$squared = $this->evaluation->result($root, $root);
		
		$difference = $this->evaluation->subtract($squared, $value);
		$difference = $this->evaluation->root($difference, "2");
		if($difference === false) {
			return false;	
		}
		
		$term_a = $this->evaluation->add($root, $difference);
		$term_b = $this->evaluation->subtract($root, $difference);
		
		$verification = $this->evaluation->result($term_a, $term_b);
		if($verification == $value) {
			return array($term_a, $term_b);	
		}
		return false;
	}
	
	function factor_sub_binary($value) {
		$binary_value = $this->evaluation->change_base($value, "2");
		$pollard_result = $this->binary_modulus->pollard_result($binary_value);
		if($pollard_result === false) {
			return false;	
		}
		$pollard_result = $this->evaluation->change_base($pollard_result, "10", "2");
		return $pollard_result;	
	}
	
	function factor_skip_modulus($value, $modulus_value="2") {
		if($modulus_value == $value) {
			return array("1", "1");	
		}
		$modulus = $this->evaluation->modulus($value, $modulus_value);
		if($modulus == "0") {
			return array($modulus, $modulus_value);	
		}
		$intermediate_value = $this->evaluation->add($modulus_value, $modulus);
		$intermediate_result = $this->factor_skip_modulus($value, $intermediate_value);
		if($intermediate_result[0] == "0") {
			return $intermediate_result;
		}
		if($modulus != "1" && $modulus != "2") {
			$modulus_value = $modulus;
			$modulus = $this->evaluation->modulus($value, $modulus_value);	
		}
		if($modulus == "0") {
			return array($modulus, $modulus_value);	
		}
		return array("1", "1");
	}
		
	function find_maximum_interval($value) {
		$root = $this->evaluation->root($value, "2");
		$root = $this->evaluation->root_closest_result;
		$root = $this->evaluation->add($root, "5");
		return $root;	
	}
	
	function find_interval_range($value) {
		$root_value = $this->evaluation->add($value, "4");
		$start_root = $this->evaluation->root($root_value, 2);
		$start_root = $this->evaluation->root_closest_result;
		
		$start_root = $this->evaluation->add($start_root, "1");
		$root_squared = $this->evaluation->result($start_root, $start_root);
		
		return $start_root;
	}
	
	private $marked_intervals = array();
	
	function find_interval($value) {
		$binary_modulus = new binary_modulus($this->evaluation, "0");
		
		$maximum_interval = $this->find_interval_range($value);
		
		$interval = "1";
		$modulus_value = "1";
		while(true) {
			$this->marked_intervals[$interval] = true;
			$modulus_value = $value;	
			
			$divider = $this->evaluation->add($interval, "1");
			
			$modulus_value = $this->evaluation->modulus($modulus_value, $divider);
			
			$modulus_value_sub = "1";
			if($modulus_value == "0") {
				return $interval;	
			}
			$multiplier = "2";
			$multiplied_value = $interval;
			if($this->evaluation->larger($interval, "1", false)) {
				while($this->evaluation->larger($maximum_interval, $multiplied_value)) {
					$multiplied_value = $this->evaluation->result($multiplier, $interval);
					$this->marked_intervals[$multiplied_value] = true;
					
					$multiplier = $this->evaluation->add($multiplier, "1");
				}
			}
			
			while(isset($this->marked_intervals[$interval])) {
				$interval = $this->evaluation->add($interval, "1");
			}
			
		}
		return $this->evaluation->subtract($interval, "1");
	}
	
	function find_interval_alt($value) {
		$interval = "1";
		$modulus_value = "1";
		$no_remainder = false;
		while($modulus_value != "0") {
			$modulus_value = $this->evaluation->add($value, "1");
			$modulus_value = $this->evaluation->subtract($modulus_value, $this->evaluation->result($interval, $interval));	
			
			$divider = $this->evaluation->add($this->evaluation->result("2", $interval), "2");
			
			$modulus_value = $this->evaluation->modulus($modulus_value, $divider);
			
			$interval = $this->evaluation->add($interval, "1");
		}
		return $this->evaluation->subtract($interval, "1");
	}
	
	
	
	
	function pollard_sub($value, $n) {
		$x = $this->evaluation->result($value, $value);
		$x = $this->evaluation->add($x, "1");
		
		$x = $this->evaluation->modulus($x, $n);
		return $x;
	}
	
		
	function factor_sub($n) {
		$x = "2";
		$y = "2";
		$d = "1";
		$stop = false;
		while($d == "1" || $d == $n) {
			$x = $this->pollard_sub($x, $n);
			$y = $this->pollard_sub($this->pollard_sub($y, $n), $n);
			$modulus_value = $this->evaluation->absolute($this->evaluation->subtract($x, $y));
			$d = $this->evaluation->gcd($modulus_value, $n);	
			
		}
		return $d;
	}
	
	private $factor_modulus_values = array();
	function factor_modulus($value, $modulus=NULL) {
		$value_squared = $this->evaluation->result($value, $value);
		$this->factor_modulus_values = array();
		$closest_root = $this->evaluation->root($value, "2");
		$closest_root = $this->evaluation->root_closest_result;
		
		$value_length = $this->evaluation->execute_divide(strlen($value), "2")['value'];
		
		$last_index = $closest_root;
		
		$last_modulus_value = $closest_root;
		$modulus = $this->evaluation->modulus($value, $closest_root);
		while($modulus != "0") {
			if($modulus == "1") {
				$modulus = $this->evaluation->subtract($last_modulus_value, "1");
			}
			if($modulus == "1") {
				$last_index = $this->evaluation->subtract($last_index, "1");
				
				$modulus = $last_index;	
			}
			while(isset($this->factor_modulus_values[$modulus])) {
				$last_index = $this->evaluation->subtract($last_index, "1"); 
				$modulus = $last_index;
			}			$last_modulus_value = $modulus;
			$this->factor_modulus_values[$modulus] = true;
			if($modulus != "1") {
				$modulus = $this->evaluation->modulus($value, $modulus);
				
				if($modulus == "0") {
					return $modulus;	
				}
			}
			if($modulus != "1") {
				$modulus = $this->evaluation->modulus($value, $modulus);
				if($modulus == "0") {
					return $modulus;	
				}
			}
			
			$modulus_sub = $this->evaluation->bit_shift($modulus, "1");
			$modulus_value = "1";
			while($this->evaluation->larger($value_length, strlen($modulus_value))) {
				$modulus_value = $this->evaluation->modulus($modulus_sub, $value);
				if($modulus_value != "0") {
					$sub_call = $this->factor_modulus_sub($value, $modulus_value, $value_squared);
					if($sub_call !== false) {
						return $sub_call;	
					}
				}
				
				$modulus_sub = $this->evaluation->bit_shift($modulus_sub, "1");
			}
		}
		return $last_modulus_value;
	}
	
	function factor_modulus_sub($value, $modulus, $value_squared) {
		$last_index = $modulus;
		if($modulus == "1") {
			$modulus = $this->evaluation->subtract($last_modulus_value, "1");
		}
		if($modulus == "1") {
			$last_index = $this->evaluation->subtract($last_index, "1");
			
			$modulus = $last_index;	
		}
		while(isset($this->factor_modulus_values[$modulus])) {
			$last_index = $this->evaluation->subtract($last_index, "1"); 
			$modulus = $last_index;
		}
		if($modulus == "0") {
						return false;
		}
		$last_modulus_value = $modulus;
		$this->factor_modulus_values[$modulus] = true;
				if($modulus != "1") {
			$last_modulus = $modulus;
			$modulus = $this->evaluation->modulus($value, $modulus);
			if($modulus == "0") {
				return $modulus;	
			}
		}
		
		if($modulus != "1") {
			$modulus = $this->evaluation->modulus($value, $modulus);
			if($modulus == "0") {
				return $modulus;	
			}
		}
		return false;
	}
	
	function find_factor($value, $root) {
		$root = $this->evaluation->add($root, "1");
		$z_squared = $this->evaluation->result($root, $root);
		$z_4 = $this->evaluation->result($z_squared, $z_squared);
		$abz_squared = $this->evaluation->result($value, $z_squared);
		
		$x = $this->evaluation->subtract($z_4, $abz_squared);
		
		$result = $this->evaluation->execute_divide($abz_squared, $x);
		if($this->evaluation->fraction_values($result['remainder'])[0] == 0) {
			$division = $this->evaluation->execute_divide($value, $result['value']);
			if($this->evaluation->fraction_values($division['remainder'])[0] == 0) {
				return $this->evaluation->add($result['value'], "0");	
			}
		}
		return false;
	}
	
	function prime_root_auxillary_fast($value) {
		$closest_root = $this->evaluation->root($value, "2");		
		$closest_root = $this->evaluation->root_closest_result;
		
				
		$squared = $this->evaluation->result($closest_root, $closest_root);		
		
		$remainder = $this->evaluation->subtract($value, $squared);
		$division = $this->evaluation->execute_divide($remainder, $closest_root);
		$sub_whole = $this->evaluation->result($division['value'], $closest_root);
		$sub_remainder = $this->evaluation->subtract($remainder, $sub_whole);
		
		if($sub_remainder == 0) {
			return false;	
		}
		
								
		$row_count = $this->evaluation->add($closest_root, $division['value']);
		$whole = $this->evaluation->result($row_count, $closest_root);
		
		$sub_remainders = array();
		
		$prime_third_root = $this->evaluation->root($closest_root, 3);
		if($prime_third_root === false) {
			$prime_third_root = $this->evaluation->root_closest_result;	
		}
		$prime_third_root = $this->evaluation->add($prime_third_root, 1);
		$max_keys = $this->evaluation->result(2, $prime_third_root);
		$sub_remainder_subtraction = 0;
		
		$sub_subtraction_value = NULL;
		$last_row_count_pseudo_value = NULL;
		$whole = $this->evaluation->result($row_count, $closest_root);
				
		
		while($this->evaluation->larger($closest_root, "2", false)) { 
			if($row_count == $sub_remainder) {
				return $row_count;	
			}
			if($sub_remainder == "0") {
				return $row_count;	
			}
			
			
			
			
			
			$addition = $row_count;			
			
			$sub_remainder_subtraction = $this->evaluation->subtract($closest_root, $sub_remainder);
			$sub_remainder_value = $sub_remainder_subtraction;
			
		
			$sub_remainder_subtraction = $this->evaluation->subtract($sub_remainder_subtraction, 1);
			$sub_remainder = $this->evaluation->subtract($addition, $sub_remainder_subtraction);
			
			$sub_remainder_unaltered = $sub_remainder;
			
			
			$closest_root = $this->evaluation->subtract($closest_root, 1);
			
			
			$row_count_addition = $this->evaluation->division->fast_floor_divide($sub_remainder, $closest_root);
			
			
			$sub_remainder_subtraction_value = $this->evaluation->result($closest_root, $row_count_addition);
			
			
			$sub_remainder = $this->evaluation->subtract($sub_remainder, $sub_remainder_subtraction_value);
			
			$row_count_addition = $this->evaluation->add($row_count_addition, 1);
			
			$row_count = $this->evaluation->add($row_count, $row_count_addition);
			
						
			$sub_subtraction_value = $sub_remainder_subtraction_value;
						
		}
		return false;
	}
	
	function prime_root_reduction($value, $root) {
		$stop = 3;
		$previous_e = NULL;
		$last_e = NULL;
		$last_y = NULL;
		$root_squared;
		while($stop !== 0) {
			$previous_e = $last_e;
			
			if($previous_e != NULL) {
				$stop--;	
				$root = $this->evaluation->subtract($root, "1");
			}
			
			$root_squared = $this->evaluation->result($root, $root);
			$y4 = $this->evaluation->result("4", $root);
			
			$e0 = $this->evaluation->subtract($value, $root_squared);
			if(!$this->evaluation->negative($e0)) {
				$last_e = $e0;	
				$last_y = $y4;
			}
			
		}
		$stop_count = 0;
		while($this->evaluation->larger($root, "2", false)) {
			$last_y = $this->evaluation->subtract($last_y, "4");
			$result = $this->evaluation->add($previous_e, "4");
			$previous_e = $last_e;			$last_e = $this->evaluation->add($result, $last_y);
			
						
			$root = $this->evaluation->subtract($root, "1");
			
			if($this->evaluation->even($result)) {
				$modulus = $this->evaluation->modulus($result, $root);
				if($modulus == 0 && $root != "1") {
									}
			}
			
			$root_squared = $this->evaluation->result($root, $root);
			$compare_value = $this->evaluation->add($root_squared, $last_y);
			if($this->evaluation->larger($result, $compare_value)) {
			}
			
		}
	}
	
	function prime_root_reduction_alt($value, $return_e0=false, $root_value_set=NULL) {
		$root_value = $this->evaluation->root($value, "2");
		$root_value = $this->evaluation->root_closest_result;
		$root = "3";
		if($root_value_set !== NULL) {
			$root = $root_value_set;	
		}
		$e0 = $value;
		$previous_e;
		$root_squared = $this->evaluation->result($root, $root);
		$y4 = $this->evaluation->result("4", $root);
				$stop = false;
		$limit = 0;
		$last_even_e = $value;
		while($this->evaluation->larger($e0, $root) && !$stop) {
			$previous_e = $e0;
			
			$e0 = $this->evaluation->subtract($e0, $root_squared);
			$e0 = $this->evaluation->subtract($e0, $y4);
						
			$division = $this->evaluation->execute_divide($e0, "2");
			if($this->evaluation->fraction_values($division['remainder'])[0] == 0) {
				$e0 = $division['value'];	
			} else {
				$stop = true;	
				$previouse_e = $e0;
			}
			
		}
								
		$e0 = $previous_e;
		if($return_e0) {
			return $e0;	
		}
		if($this->evaluation->negative($e0)) {
			$e0 = $value;
			if($this->evaluation->modulus($value, $root) == 0) {
				return $root;	
			}
		}
		if($value != $e0 && $this->evaluation->modulus($value, $e0) == 0) {
			return $e0;	
		}
		
		$next_e0 = $this->prime_root_reduction_alt($value, true, $this->evaluation->add($root, "2"));
		$next_e0_2 = $this->prime_root_reduction_alt($value, true, $this->evaluation->add($root, "4"));
		
		
		$step_a = $this->evaluation->subtract($e0, $next_e0);
		$step_b = $this->evaluation->subtract($next_e0, $next_e0_2);
		$step_size = $this->evaluation->subtract($step_b, $step_a);
		
		if($this->evaluation->negative($step_size)) {
			return $this->prime_root_reduction_alt($value, false, $this->evaluation->add($root, "2"));	
		}
		
		
		$last_y = $y4;
						
		
		
								
		$e0_start = $e0;
		
		$addition = $this->evaluation->result($root, $step_size);
						$negative_count = 0;
		while($this->evaluation->larger($root_value, $root)) {
			if($this->evaluation->negative($e0) || $this->evaluation->larger($root, $e0)) {
				return $this->prime_root_reduction_alt($value, false, $root);
			}
			
			$root = $this->evaluation->add($root, "2");
				
				
				
							
			
			$e0 = $this->evaluation->subtract($e0, $addition);
			$addition = $this->evaluation->add($addition, $step_size);
																			
				
				
			if($this->evaluation->modulus($e0, $root) == 0) {
				return $root;
			}
			
			if($this->evaluation->negative($e0)) {
				$negative_count = $this->evaluation->add($negative_count, "1");	
			} else {
				$negative_count = "0";	
			}
			
		}
		
	}
	
	function prime_root_reduction_sub($value, $last_y, $root, $depth=2) {
		$root_squared = $this->evaluation->result($root, $root);
				
		$addition_value = $this->evaluation->result("3", $depth);
				
		$value = $this->evaluation->subtract($value, $this->evaluation->add($root_squared, $last_y));
		$next_value = $value;
		while($this->evaluation->larger($next_value, "0", false)) {
			if($depth == 2) {
				$next_value = $this->evaluation->add($next_value, $last_y);
				$next_value = $this->evaluation->add($next_value, $addition_value);
			} else {
				$y_coefficient = "2"; 				$next_value = $this->evaluation->add($next_value, $this->evaluation->result($last_y, $y_coefficient));
				if(!$this->evaluation->even($depth)) {
					if($this->evaluation->larger($depth, "4")) {
						$next_value = $this->evaluation->add($next_value, $this->evaluation->execute_divide($last_y, "2")['value']);
					} else {
						$next_value = $this->evaluation->subtract($next_value, $this->evaluation->execute_divide($last_y, "2")['value']);
					}
				}
				$next_value = $this->evaluation->add($next_value, $addition_value);
			}
			$last_y = $this->evaluation->subtract($last_y, "4");
			$root = $this->evaluation->subtract($root, "1");
			$modulus = $this->evaluation->modulus($next_value, $root);
			if($modulus == 0 && $root != "1") {
				return $root;	
			}
									
			$root_squared = $this->evaluation->result($root, $root);
			$compare_value = $this->evaluation->add($root_squared, $last_y);
			if($this->evaluation->larger($next_value, $compare_value)) {
				if($depth < 5) {
					return $this->prime_root_reduction_sub($next_value, $last_y, $root, ++$depth);
				}		
			}
		}
		return $root;
	}
	
	function prime_root_reduction_sub_2($value, $last_y, $root, $depth=1) {
		$root_squared = $this->evaluation->result($root, $root);
				
		$addition_value = "3";
		$value = $this->evaluation->subtract($value, $this->evaluation->add($root_squared, $last_y));
		$next_value = $value;
		while($this->evaluation->larger($next_value, "0", false)) {
			$last_y_divided = $this->evaluation->execute_divide($last_y, "2")['value'];
			$next_value = $this->evaluation->add($next_value, $last_y_divided);
			$next_value = $this->evaluation->add($next_value, $addition_value);
		
			
			
			
			$last_y = $this->evaluation->subtract($last_y, "4");
			$root = $this->evaluation->subtract($root, "1");
			$modulus = $this->evaluation->modulus($next_value, $root);
			if($modulus == 0) {
				return $root;	
			}
		}
	}
	
	function prime_root_reduction_aux($value) {
		$root = $this->evaluation->root($value, "2");
		$root = $this->evaluation->root_closest_result;
		$n = $this->evaluation->modulus($value, $root);
		$n = $this->evaluation->add($n, $root);
		
		$second_root = $this->evaluation->root($n, "2");
		$second_root = $this->evaluation->root_closest_result;
				
				
		$delta = "0";
		$last_difference = NULL;
		$difference = NULL;
		$k = $root;
		$k = $this->evaluation->subtract($k, "1");
		
		$addition = "2";
		while(true) {
			$modulus = $this->evaluation->modulus($n, $k);
			if($modulus == 0) {
				return $k;	
			}
			$n = $this->evaluation->add($n, $addition);
			$addition = $this->evaluation->add($addition, "2");
			$k = $this->evaluation->subtract($k, "1");
		}		
	}
	
	function prime_root_reduction_consecutive($value, $addition_value, $k) {
		$modulus_value = $value;
		$counter = "0";
		
		$m_value = $modulus_value;		
		if($m_value == $k) {
			return $k;	
		}
		
		
		
		while($this->evaluation->larger($k, "3")) {
			$addition_value = $this->evaluation->add($addition_value, "4");
			$modulus_value = $this->evaluation->add($modulus_value, $addition_value);
			$k = $this->evaluation->subtract($k, "1");
												while($this->evaluation->larger($modulus_value, $k)) {
				$modulus_value = $this->evaluation->subtract($modulus_value, $k);
				$addition_value = $this->evaluation->add($addition_value, "1");	
			}
			if($modulus_value == "0") {
				return $k;
			}
			if($modulus_value == $k) {
				return $k;	
			}
			
			$counter = $this->evaluation->add($counter, "1");
		}
		return false;
	}
	
	function prime_root_reduction_aux_alt_sub($modulus_value, $addition, $k) {
		
		$consecutive_subtractions = "0";
		$consecutive_delta = "0";
		$last_modulus_value = NULL;
		$last_consecutive_subtractions = NULL;
		$next_modulus_count = "0";
		$next_modulus_value = NULL;
		while($this->evaluation->larger($k, "2")) {
			
			$modulus_value = $this->evaluation->add($modulus_value, $addition);
									
			$addition = $this->evaluation->add($addition, "2");
			$k = $this->evaluation->subtract($k, "1");	
			
						
			if($modulus_value == 0) {
				return $k;
			} else {
				if($modulus_value == $k) {
					return $k;	
				}
				if($this->evaluation->larger($modulus_value, $k)) {
					
					$division = $this->evaluation->execute_divide($modulus_value, $k)['value'];
					$subtraction_value = $this->evaluation->result($division, $k);
					
					$modulus_value = $this->evaluation->subtract($modulus_value, $subtraction_value);	
					$addition = $this->evaluation->add($addition, $division);
					$consecutive_subtractions = $this->evaluation->add($consecutive_subtractions, "1");
					if($last_modulus_value != NULL) {
						$consecutive_delta = $this->evaluation->subtract($modulus_value, $last_modulus_value);
					}
					if($next_modulus_value == $modulus_value) {
						$next_modulus_count = $this->evaluation->add($next_modulus_count, "1");
						if($next_modulus_count == "2") {
							$result = $this->prime_root_reduction_consecutive($modulus_value, $consecutive_delta, $k);		
							if($result !== false) {
								return $result;	
							}
						}
					} else {
						$next_modulus_count = "0";	
					}
											
					$next_modulus_value = $this->evaluation->add($modulus_value, $this->evaluation->add($consecutive_delta, "4"));
					$next_k = $this->evaluation->subtract($k, "1");
					
					$last_modulus_value = $modulus_value;
				} else {
					$last_consecutive_subtractions = $consecutive_subtractions;
					$consecutive_subtractions = "0";	
				}
			}
			if($modulus_value == 0) {
				return $k;	
			}
																	}
	}
	
	function prime_root_reduction_aux_alt($value) {
		$root = $this->evaluation->root($value, "2");
		$root = $this->evaluation->root_closest_result;
		$n = $this->evaluation->modulus($value, $root);
		$n = $this->evaluation->add($n, $root);
		
						
		$second_root = $this->evaluation->root($n, "2");
		$second_root = $this->evaluation->root_closest_result;
				
				
		$delta = "0";
		$last_difference = NULL;
		$difference = NULL;
		$k = $root;
		$k = $this->evaluation->subtract($k, "1");
		
		$addition = "2";
		$counter = "0";
		$last_modulus = NULL;
		$modulus = NULL;
		$addition = NULL;
		while($this->evaluation->larger("1", $counter)) {
			$modulus = $this->evaluation->modulus($value, $k);
			if($modulus == 0) {
				return $k;	
			}
			if($last_modulus == NULL) {
				$last_modulus = $modulus;	
			} else {
				$addition = $this->evaluation->subtract($modulus, $last_modulus);	
			}
			$counter = $this->evaluation->add($counter, "1");
			$k = $this->evaluation->subtract($k, "1");
		}
		$addition = $this->evaluation->add($addition, "2");
		
				
		$k = $this->evaluation->add($k, "1");
		$modulus_value = $modulus;
		
		$count = 0;
		$last_k = NULL;
		$stop = false;
		$last_modulus_value = NULL;
		$next_modulus_value = NULL;
		$solution = NULL;
		while($this->evaluation->larger($k, "2")) {
			if($modulus_value == 0) {
				return $k;
			} else {
				if($this->evaluation->larger($modulus_value, $k)) { 					
					$modulus_value_last = $modulus_value;
					if($count != "0") {
						$k = $this->evaluation->subtract($k, "1");
					}
					$division = $this->evaluation->execute_divide($modulus_value, $k)['value'];
					$subtraction_value = $this->evaluation->result($division, $k);
					$modulus_value = $this->evaluation->subtract($modulus_value, $subtraction_value);	
					$addition = $this->evaluation->add($addition, $division);
																									
					
					if($modulus_value == 0) {
						return $k;	
					}
					if($solution == "0") {
						return $this->prime_root_reduction_aux_alt_sub($modulus_value, $addition, $k);
					}
					
					$calculated_modulus_value;
					if(!$this->evaluation->even($addition) && !$stop) {
						$a = $this->evaluation->add($addition, "1");
						$a = $this->evaluation->execute_divide($a, "2")['value'];
						
						
						$s = $this->evaluation->add($k, $this->evaluation->result($a, $a));
						$s = $this->evaluation->subtract($s, $modulus_value);
						$s = $this->evaluation->root($s, "2");
						$s = $this->evaluation->root_closest_result;
						$subtraction_value = $this->evaluation->add("1", $this->evaluation->result("2", $a));
						$subtraction_value = $this->evaluation->execute_divide($subtraction_value, "2")['value'];
						$solution = $this->evaluation->subtract($s, $subtraction_value);
																		
																		
						$calculated_modulus_value = "0";
						$a_k = $this->evaluation->add($a, $solution);
						$a_1 = $this->evaluation->subtract($a, "1");
						$calculated_modulus_value = $this->evaluation->add($calculated_modulus_value, $this->evaluation->result($a_k, $a_k));
						$calculated_modulus_value = $this->evaluation->subtract($calculated_modulus_value, $this->evaluation->result($a_1, $a_1));
						
						
						$addition_sum = $this->evaluation->result($this->evaluation->add($solution, "1"), "2");
						$addition = $this->evaluation->add($addition, $addition_sum);
												
						$calculated_modulus_value = $this->evaluation->add($calculated_modulus_value, $modulus_value);
						$unaltered_modulus_value = $this->evaluation->subtract($calculated_modulus_value, $addition);
						$unaltered_modulus_value = $this->evaluation->add($unaltered_modulus_value, "2");
						$k = $this->evaluation->subtract($k, $solution);
						if($unaltered_modulus_value == 0 || $unaltered_modulus_value == $k) {
							return $k;	
						}
						
					} else if(!$stop) {
						$a = $this->evaluation->add("2", $addition);
						$a = $this->evaluation->execute_divide($a, "2")['value'];
						$a = $this->evaluation->subtract($a, "1");
						
						
						$s = $this->evaluation->subtract($k, $modulus_value);
						$s = $this->evaluation->add($s, $this->evaluation->result($a, $a));
						$s = $this->evaluation->add($s, "1");
						$s = $this->evaluation->root($s, "2");
						$s = $this->evaluation->root_closest_result;
						$s = $this->evaluation->add($s, "1");
						$s = $this->evaluation->subtract($s, $a);
						$solution = $this->evaluation->subtract($s, "1");
											
																		
						$calculated_modulus_value = "0";
						$a_k = $this->evaluation->add($a, $solution);
						$calculated_modulus_value = $this->evaluation->add($calculated_modulus_value, $this->evaluation->result($a_k, $this->evaluation->add($a_k, "1")));
						$calculated_modulus_value = $this->evaluation->subtract($calculated_modulus_value, $this->evaluation->result($a, $this->evaluation->subtract($a, "1")));
						
						$addition_sum = $this->evaluation->result($this->evaluation->add($solution, "1"), "2");	
						
						$addition = $this->evaluation->add($addition, $addition_sum);
												
						$calculated_modulus_value = $this->evaluation->add($calculated_modulus_value, $modulus_value);
						$unaltered_modulus_value = $this->evaluation->subtract($calculated_modulus_value, $addition);
						$unaltered_modulus_value = $this->evaluation->add($unaltered_modulus_value, "2");
																		$k = $this->evaluation->subtract($k, $solution);
						if($unaltered_modulus_value == 0 || $unaltered_modulus_value == $k) {
							return $k;	
						}
					}
						
					$count = "1";			
					
					$last_modulus_value = $modulus_value;
					$modulus_value = $calculated_modulus_value;
					if($modulus_value == 0) {
						return $k;	
					}
				} else {
					$modulus_value = $this->evaluation->add($modulus_value, $addition);
					$addition = $this->evaluation->add($addition, "2");
					$k = $this->evaluation->subtract($k, "1");
				}
			}
			if($modulus_value == 0) {
				return $k;	
			}
																	}
	}
	
	function prime_root_reduction_aux_alt_2($value) {
		$root = $this->evaluation->root($value, "2");
		$root = $this->evaluation->root_closest_result;
		$n = $this->evaluation->modulus($value, $root);
		$n = $this->evaluation->add($n, $root);
		
						
		$second_root = $this->evaluation->root($n, "2");
		$second_root = $this->evaluation->root_closest_result;
				
				
		$delta = "0";
		$last_difference = NULL;
		$difference = NULL;
		$k = $root;
		$k = $this->evaluation->subtract($k, "1");
		
		$addition = "2";
		$counter = "0";
		$last_modulus = NULL;
		$modulus = NULL;
		$addition = NULL;
		while($this->evaluation->larger("1", $counter)) {
			$modulus = $this->evaluation->modulus($value, $k);
			if($modulus == 0) {
				return $k;	
			}
			if($last_modulus == NULL) {
				$last_modulus = $modulus;	
			} else {
				$addition = $this->evaluation->subtract($modulus, $last_modulus);	
			}
			$counter = $this->evaluation->add($counter, "1");
			$k = $this->evaluation->subtract($k, "1");
		}
		$addition = $this->evaluation->add($addition, "2");
		
				
		$k = $this->evaluation->add($k, "1");
		$modulus_value = $modulus;
		$consecutive_subtractions = "0";
		$consecutive_delta = "0";
		$last_modulus_value = NULL;
		$last_consecutive_subtractions = NULL;
		$next_modulus_count = "0";
		$next_modulus_value = NULL;
		while($this->evaluation->larger($k, "2")) {
			
			$modulus_value = $this->evaluation->add($modulus_value, $addition);
									
			$addition = $this->evaluation->add($addition, "2");
			$k = $this->evaluation->subtract($k, "1");	
			
						
			if($modulus_value == 0) {
				return $k;
			} else {
				if($modulus_value == $k) {
					return $k;	
				}
				if($this->evaluation->larger($modulus_value, $k)) {
					
					$division = $this->evaluation->execute_divide($modulus_value, $k)['value'];
					$subtraction_value = $this->evaluation->result($division, $k);
					
					$modulus_value = $this->evaluation->subtract($modulus_value, $subtraction_value);	
					$addition = $this->evaluation->add($addition, $division);
					$consecutive_subtractions = $this->evaluation->add($consecutive_subtractions, "1");
					if($last_modulus_value != NULL) {
						$consecutive_delta = $this->evaluation->subtract($modulus_value, $last_modulus_value);
					}
					if($next_modulus_value == $modulus_value) {
						$next_modulus_count = $this->evaluation->add($next_modulus_count, "1");
						if($next_modulus_count == "2") {
							$result = $this->prime_root_reduction_consecutive($modulus_value, $consecutive_delta, $k);		
							if($result !== false) {
								return $result;	
							}
						}
					} else {
						$next_modulus_count = "0";	
					}
											
					$next_modulus_value = $this->evaluation->add($modulus_value, $this->evaluation->add($consecutive_delta, "4"));
					$next_k = $this->evaluation->subtract($k, "1");
						
					$last_modulus_value = $modulus_value;
				} else {
					$last_consecutive_subtractions = $consecutive_subtractions;
					$consecutive_subtractions = "0";	
				}
			}
			if($modulus_value == 0) {
				return $k;	
			}
		}
	}
	
	/*
		m > k
		(k*m^2) % [k^2*m) = km
		(m^2 % km) % m = 0
	*/
	
	function fast_iterative_power_search($n) {
		$root = $this->evaluation->root($n, 2);
		$root = $this->evaluation->root_closest_result;
		
		$squared = $this->evaluation->result($root, $root);
		if($this->evaluation->larger($n, $squared)) {
			$root = $this->evaluation->add($root, 1);	
			$squared = $this->evaluation->result($root, $root);
		}
		$modulus = $this->evaluation->modulus($squared, $n);
		if($modulus == $root) {
			return $root;	
		}
		
		$modulus_value = $modulus;
		$root = $this->evaluation->add($root, 1);	
		$squared = $this->evaluation->result($root, $root);	
		$modulus = $this->evaluation->modulus($squared, $n);
		
		$modulus_delta = $this->evaluation->subtract($modulus, $modulus_value);
		$modulus_value = $modulus;
		$modulus = $modulus_delta;
		while(true) {
			$root = $this->evaluation->add($root, 1);
			$modulus = $this->evaluation->add($modulus, "2");
			$modulus_value = $this->evaluation->add($modulus_value, $modulus);
			if($this->evaluation->larger($modulus_value, $n)) {
				$modulus_value = $this->evaluation->subtract($modulus_value, $n);	
			}
			if($this->evaluation->modulus($modulus_value, $root) == 0) {
				return $root;	
			}
		}
	}
}

?>