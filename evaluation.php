<?

namespace NumEval;

class evaluation {
	private $term_a;
	private $term_b;
	protected $sql;
	protected $user_id;
	protected $statement;
	
	public $trigonometry;
	
	private $super;
	private $karatsuba;
	private $binary_modulus;
		
	function __construct() {
		$this->trigonometry = new trigonometry($this);
		$this->division = new division($this);
		$this->binary_modulus = new binary_modulus($this, "0");
	}
	
	function set_configuration($truncate_fractions_length=0, $logarithm_iteration_count=12, $root_fraction_precision=array('value' => '0', 'remainder' => '1/100'), $disable_built_in_approximation=false, $sine_precision=10, $set_continued_fraction_resolution_level_setting=12, $disable_exact_root_results=false) {
		if($truncate_fractions_length != NULL) {
			$this->set_truncate_fractions($truncate_fractions_length);
		}
		if($logarithm_iteration_count != NULL) {
			$this->logarithm_iteration_count = $logarithm_iteration_count;	
		}
		if($root_fraction_precision != NULL) {
			$this->root_fraction_precision = $root_fraction_precision;	
		}
		if($disable_built_in_approximation != NULL) {
			$this->disable_built_in_approximation = $disable_built_in_approximation;	
		}
		if($sine_precision != NULL) {
			$this->trigonometry->sine_precision = $sine_precision;	
		}
		if($set_continued_fraction_resolution_level_setting != NULL) {
			$this->set_continued_fraction_resolution_level_setting = $set_continued_fraction_resolution_level_setting;	
		}
		if($disable_exact_root_results != NULL) {
			$this->disable_exact_root_results = $disable_exact_root_results;	
		}
	}
	
	private $var_dump_value = "";

	function var_dump($value) {
		$this->var_dump_value .= $value."\n";
	}

	function get_var_dump() {
		$value = $this->var_dump_value;
		$this->var_dump_value = "";
		return $value;

	}
	
	public function get_digits($term, $remove_decimal_point=true, $remove_negative=true) {
		if($remove_decimal_point) {
			$term = explode(".", $term);
			$term = join("", $term);	
		}
		
		$digits = str_split($term);
		$digits = array_reverse($digits);
		return $digits;	
	}
	
	private $start_exponent;
	private $exponent;
	private $intermediate_results;
	
	function result($term_a, $term_b) {
		
		$negative = false;
		if(($this->negative($term_a) && !$this->negative($term_b)) || ($this->negative($term_b) && !$this->negative($term_a))) {
			$negative = true;
		}
		$result = $this->result_sub($this->absolute($term_a), $this->absolute($term_b));
		if($negative) {
			$result = "-".$result;	
		}
		return $result;
	}
	
	private function result_sub($term_a, $term_b) {
		$this->intermediate_results = array();
		if($term_a == 0 || $term_b == 0) {
			return 0;	
		}
		
		$a_digits = $this->get_digits($term_a);
		$b_digits = $this->get_digits($term_b);
		$index = 0;
		$start_stop = false;
		foreach($b_digits as $exponent_b => $value_b) {
			
			if($value_b != 0) {
				foreach($a_digits as $exponent_a => $value_a) {
					if($value_a != 0) {
						$value = $value_a*$value_b;
						
						$exponent = $exponent_a+$exponent_b;
						$this->intermediate_results[] = array(
							'value' => $value,
							'exponent' => $exponent
						);
					}
				}
			}
		}
		$result = NULL;
		foreach($this->intermediate_results as $result_value) {
			$result_value = $this->numeric_value($result_value);
			if($result == NULL) {
				$result = $result_value;
			} else {
				$result = $this->add($result, $result_value);	
			}
		}
		return $result;
	}
	
	function result_multiple($values) {
		if(!is_array($values[0])) {
			$result = 1;
			foreach($values as $value) {
				$result = $this->result($result, $value);	
			}
			return $result;
		} else {
			$result = array('value' => '1', 'remainder' => '0/1');
			foreach($values as $value) {
				$result = $this->multiply_total($result, $value);	
			}
			return $result;
		}
	}
	
	function result_decimal($term_a, $term_b) {
		$synchronized = $this->synchronize_values($term_a, $term_b);
		$decimal_place = $this->result($synchronized['fraction_length'], 2);
		$term_a = join("", explode(".", $synchronized['a']));
		$term_b = join("", explode(".", $synchronized['b']));
		$term_a = $this->remove_leading_zeros($term_a);
		$term_b = $this->remove_leading_zeros($term_b);
		$result = $this->result($term_a, $term_b);
		
		$result = $this->place_decimal($result, $decimal_place, true, true);
		$result = $this->remove_leading_zeros($result, true);
		return $result;
	}
	
	public function numeric_value($exponent_pair) {
		if($exponent_pair['exponent'] == 0) {
			return $exponent_pair['value'];	
		}
		$exponent_pair_list = array();
		if(isset($exponent_pair['exponent'])) {
			$exponent_pair_list[] = $exponent_pair;
		} else {
			$exponent_pair_list = $exponent_pair;	
		}
		$result;
		foreach($exponent_pair_list as $exponent_pair) {
			$value = $exponent_pair['value'];
			$counter = 0;
			$zeros = "";
			if(strpos($value, ".") === false && $exponent_pair['exponent'] >= 0) {
				while($counter < $exponent_pair['exponent']) {
					$zeros .= "0";
					$counter++;	
				}
				$value .= $zeros;
			} else {
				$prefix = false;
				if($exponent_pair['exponent'] < 0) {
					$prefix = true;	
				}
				$value = $this->place_decimal($value, $exponent_pair['exponent'], true, $prefix);
			}
			
			$value = $this->clean_fraction($value);
			if(!isset($result)) {
				$result = $value;	
			} else {
				$result = $this->add($result, $value);	
			}
		}
		return $result;
	}
	
	private function clean_fraction($value) {
		$clean = true;
		if(strpos($value, ".") !== false) {
			$split = explode(".", $value);
			$fraction = str_split($split[1]);
			foreach($fraction as $digit) {
				if($digit != 0) {
					$clean = false;	
				}
			}
			if($clean) {
				return $split[0];
			}
		}
		return $value;
	}
	
	private function clean_remainder($value) {
		$fraction_values = $this->fraction_values($value['remainder']);
		if($fraction_values[0] == 0) {
			$value['remainder'] = "0/1";	
		}
		return $value;
	}
	
	public function add_place($term_a, $term_b, $place, $base=10, $limit_decimals=false) {
		$split_place = strlen($term_a) - $place;
		$term_a_remainder = substr($term_a, $split_place, strlen($term_a));
		$term_a_add = substr($term_a, 0, $split_place);
		$addition = $this->add($term_a_add, $term_b, $base, $limit_decimals);
		$result = $addition.$term_a_remainder;
		return $result;
	}
	
	
	public function synchronize_values($term_a, $term_b) {
		$fraction_length = 0;
		$a_split = explode(".", $term_a);
		$b_split = explode(".", $term_b);
		if(isset($a_split[1])) {
			$fraction_length = strlen($a_split[1]);	
		}
		if(isset($b_split[1]) && strlen($b_split[1]) > $fraction_length) {
			$fraction_length = strlen($b_split[1]);	
		}
		$diff = $fraction_length;
		if(!isset($a_split[1])) {
			$a_split[1] = "";
		}
		$diff = $fraction_length - strlen($a_split[1]);
		$counter = 0;
		while($counter < $diff) {
			$a_split[1] .= "0";	
			$counter++;
		}
		if(!isset($b_split[1])) {
			$b_split[1] = "";
		}
		$diff = $fraction_length - strlen($b_split[1]);
		$counter = 0;
		while($counter < $diff) {
			$b_split[1] .= "0";	
			$counter++;
		}
		
		$term_a = join(".", $a_split);
		$term_b = join(".", $b_split);
		$result = array(
			'a' => $term_a,
			'b' => $term_b,
			'fraction_length' => $fraction_length
		);
		return $result;
	}
	
	function add($term_a, $term_b) {
		if($this->negative($term_a) && $this->negative($term_b)) {
			return "-".$this->add_sub($this->absolute($term_a), $this->absolute($term_b));	
		} else if($this->negative($term_a) && !$this->negative($term_b)) {
			return $this->subtract($this->absolute($term_b), $this->absolute($term_a));
		} else if(!$this->negative($term_a) && $this->negative($term_b)) {
			return $this->subtract($this->absolute($term_a), $this->absolute($term_b));
		} else {
			return $this->add_sub($this->absolute($term_a), $this->absolute($term_b));	
		}
	}
	
	function add_multiple($values) {
		$result = "0";
		foreach($values as $value) {
			$result = $this->add($result, $value);	
		}
		return $result;
	}
	
	public function add_sub($term_a, $term_b, $base=10, $limit_decimals=false) {
		$term_a = $this->absolute($term_a);
		$term_b = $this->absolute($term_b);
		$decimal_point = -1;
		if(strpos($term_a, ".") !== false || strpos($term_b, ".") !== false) {
			$terms = $this->synchronize_values($term_a, $term_b);
			$term_a = $terms['a'];
			$term_b = $terms['b'];
			$decimal_point = $terms['fraction_length'];
		}
		$a_digits = $this->get_digits($term_a);
		$b_digits = $this->get_digits($term_b);
		if(count($b_digits) > count($a_digits)) {
			$switch = $a_digits;
			$a_digits = $b_digits;
			$b_digits = $switch;	
		}
		$return_digits = array();
		$carry_value = NULL;
		foreach($a_digits as $key_a => $a_digit) {
			$addition;
			if(isset($b_digits[$key_a])) {
				if($a_digit == "") {
					$a_digit = 0;	
				}
				$b_digit = $b_digits[$key_a];
				if($b_digit == "") {
					$b_digit = 0;	
				}
				$addition = $a_digit + $b_digit;
			} else {			
				$addition = $a_digit;	
			}
			if($carry_value != NULL && $carry_value != "") {
				$addition += $carry_value;
				$carry_value = NULL;	
			}
			if(!$limit_decimals) {
				if((($addition >= 10 && $key_a > 0) || ($base == 10 && $addition >= 10))) {
					$addition_digits = str_split($addition);
					$carry_value = $addition_digits[0];
					$addition = $addition_digits[1];	
				} else if($addition >= $base && ($key_a == 0)) {
					$carry_value = 1;
					$addition = $addition - $base;
				}
			} else {
				if($addition >= $base) {
					$carry_value = 1;
					$addition = $addition - $base;
				}
			}
			$return_digits[] = $addition;
		}
		if($carry_value != NULL) {
			$return_digits[] = $carry_value;	
		}
		$return_digits = array_reverse($return_digits);
		$value = implode("", $return_digits);
		if($decimal_point != -1) {
			$digits = $this->get_digits($value);
			$value = "";
			foreach($digits as $key => $digit) {
				if($key == $decimal_point) {
					$value = $digit.".".$value;	
				} else {
					$value = $digit.$value;	
				}
			}
		}
		if($limit_decimals) {
			$re_add = false;
			$digits = $this->get_digits($value);
			foreach($digits as $key => $digit) {
				if($digit >= $base) {
					$re_add = true;	
				}
			}
			if($re_add) {
				$value = $this->add_sub($value, "0", $base, true);	
			}
		}
		return $value;
	}
	
	private function remove_minus($value) {
		if(strpos($value, "-") !== false) {
			$split = explode("-", $value);
			$value = $split[1];	
		}
		return $value;
	}
	
	function subtract($term_a, $term_b) {
		if(strpos($term_a, "-") !== false && strpos($term_b, "-") !== false) {
			$term_a = explode("-", $term_a)[1];
			$term_b = explode("-", $term_b)[1];
			return $this->negative_value($this->subtract_sub($term_b, $term_a));
		} else if(strpos($term_a, "-") === false && strpos($term_b, "-") !== false) {
			$term_b = explode("-", $term_b)[1];
			return $this->add($term_a, $term_b);
		} else if(strpos($term_a, "-") !== false && strpos($term_b, "-") === false) {
			$term_a = explode("-", $term_a)[1];
			return "-".$this->add($term_a, $term_b);
		} else {
			return $this->subtract_sub($term_a, $term_b);	
		}
	}
	
	public function subtract_sub($term_a, $term_b, $base=10, $limit_decimals=false) {
		
		$decimal_point = -1;
		if(strpos($term_a, ".") !== false || strpos($term_b, ".") !== false) {
			$terms = $this->synchronize_values($term_a, $term_b);
			$term_a = $terms['a'];
			$term_b = $terms['b'];
			$decimal_point = $terms['fraction_length'];
		}
		
		$a_digits = $this->get_digits($term_a);
		$b_digits = $this->get_digits($term_b);
		$minus_sign = "";
		if($this->larger($term_b, $term_a) && $term_b != $term_a) { 			
			$switch = $a_digits;
			$a_digits = $b_digits;
			$b_digits = $switch;	
			$minus_sign = "-";
		}
		
		
		$return_digits = array();
		$carry_value = NULL;
		$carry_index = array();
		foreach($a_digits as $key_a => $a_digit) {
			$addition;
			if(isset($b_digits[$key_a])) {
				if($a_digit == "") {
					$a_digit = 0;	
				}
				$b_digit = $b_digits[$key_a];
				if($b_digit == "") {
					$b_digit = 0;	
				}
				$addition = $a_digit - $b_digit;
			} else {			
				$addition = $a_digit;	
			}
			if($carry_value != NULL) {
				$addition -= $carry_value;
				$carry_value = NULL;	
			}
			if((($addition < 0 && $key_a > 0) || ($base == 10 && $addition < 0))) {
				
				$carry_value = 1;
				$addition = $this->remove_minus($addition);
				$addition = 10-$addition;
				
				
			} 
			
			$return_digits[] = $addition;
		}
		
		
		$return_digits = array_reverse($return_digits);
		$result = implode("", $return_digits);
		$result = $this->remove_leading_zeros($result);
		if($decimal_point != -1) {
			$result = $this->place_decimal($result, $decimal_point, true, true);	
		}
		$result = $minus_sign.$result;
		if($result == "") {
			$result = 0;	
		}
		$result = $this->remove_leading_zeros($result);
		return $result;
	}
	
	
	
	public function lengthen_fraction($value, $length) {
		$fraction = explode("/", $value);
		$numerator = $this->result($length, $fraction[0]);		
		$denominator = $this->result($length, $fraction[1]);
		return $numerator."/".$denominator;	
	}
	
	public function lengthen_to($value, $length_to) {
		$fraction = $this->fraction_values($value);
		$denominator = $fraction[1];
		$fraction_translation = $this->execute_divide($length_to, $denominator);		
		$fraction_translation = $fraction_translation['value'];
		return $this->lengthen_fraction($this->fraction_string($fraction), $fraction_translation);	
	}
	
	private $unit_fraction_limit = 100;
	
	public function unit_fraction($value) {
		$values = $this->fraction_values($value);
		if($values[0] < 1) {
			$fraction_translation = 1 / $values[0];
			$numerator = 1;
			$denominator = $values[1] * $fraction_translation;
			if(!$this->fraction($denominator)) {
				$counter = 0;
				$denominator_change = $denominator;
				while(!$this->fraction($denominator_change) && $counter < $this->unit_fraction_limit) {
					$counter++;
					$denominator_change = $denominator*$counter;
				}
				$numerator = $counter*1;
				$denominator = $denominator_change;
			}
			return $numerator."/".$denominator;
		}
		return $value;
	}
	
	public function common($value, $shorten=false) {
		$decimal_point = strpos($value, ".");
		$assembly = $value;
		if($decimal_point != false) {
			$length = strlen($value);
			$split = explode(".", $value);
			$assembly = $this->remove_leading_zeros($split[0].$split[1]);
			$denominator_decimals = $length - $decimal_point;
			$denominator = $this->make_decimal_value($denominator_decimals);
			$assembly = $assembly."/".$denominator;
		}
		if($shorten) {
			$assembly = $this->execute_shorten_fraction($assembly);
		}
		return $assembly;
	}
	
	private $remove_zero_count;
	function remove_leading_zeros($value, $reverse=false) {
		$digits = str_split($value);
		if($reverse) {
			$digits = $this->get_digits($value, false);	
		}
		$counter = 0;
		$non_zero = false;
		
		$result = "";
		$zero_count = 0;
		foreach($digits as $counter => $digit) {
			if(!$non_zero) {
				if($digit != "0") {
					$non_zero = true;
					$result .= $digit;	
				} else {
					$zero_count++;	
				}
			} else {
				$result .= $digit;	
			}
		}
		
		$this->remove_zero_count = $zero_count;
		if($reverse) {
			$result = strrev($result);	
		}
		if(trim($result) == "") {
			$result = "0";	
		}
		return $result;
	}
	
	private function make_decimal_value($length) {
		$counter = 1;
		$return = "";
		while($counter < $length) {
			$return .= "0";
			$counter++;	
		}
		$return = "1".$return;
		return $return;
	}
	
	public function fraction($value) {
		if(strpos($value, ".") !== false) {
			return true;	
		}
		return false;
	}
	
	public function multiply_fraction($value_a, $value_b, $shorten=false) {
		if($value_a == "" || $value_b == "" || $value_a == NULL || $value_b == NULL) {
			return "0/1";	
		}
		
		$fraction_a = $this->fraction_values($value_a);
		$fraction_b;
		if(strpos($value_b, "/") !== false) {
			$fraction_b = $this->fraction_values($value_b);
		} else {
			$fraction_b = array(
				$value_b,
				$value_b
			);
		}
		if($fraction_a[0] == 0 || $fraction_b[0] == 0) {
			return "0/1";	
		}
		$numerator = $this->result($fraction_a[0], $fraction_b[0]);		
		$denominator = $this->result($fraction_a[1], $fraction_b[1]);		
		$result = $numerator."/".$denominator;
		if($shorten) {
		}
		return $result;
	}
	
	function multiply_total($value_a, $value_b, $shorten=false) {
		$negative_result = false;
		if($this->negative($value_a) && !$this->negative($value_b)) {
			$negative_result = true;	
		}
		if(!$this->negative($value_a) && $this->negative($value_b)) {
			$negative_result = true;	
		}
		$value_a = $this->absolute($value_a);
		$value_b = $this->absolute($value_b);
		$result = $this->multiply_total_sub($value_b, $value_a['value']);
		$multiplication = $this->multiply_total_sub(array('value' => '0', 'remainder' => $value_a['remainder']), $value_b['value']);
		$result = $this->add_total($result, $multiplication);
		$result = $this->add_total($result, array('value' => '0', 'remainder' => $this->multiply_fraction($value_a['remainder'], $value_b['remainder'])));
		if($shorten) {
			$result['remainder'] = $this->execute_shorten_fraction($result['remainder']);	
		}
		if($negative_result) {
			$result = $this->negative_value($result);	
		}
		$result = $this->clean_remainder($result);
		return $result;
	}
	
	private function multiply_total_sub($value_a, $value_b) {
		$result = $this->result($value_a['value'], $value_b);		
		$fraction = $this->multiply_fraction($value_a['remainder'], ($value_b."/1"));		
		$fraction_values = $this->fraction_values($fraction);
		$division = $this->execute_divide($fraction_values[0], $fraction_values[1]);
		if($this->larger($division['value'], 0)) {
			$result = $this->add($result, $division['value']);
			$fraction = $division['remainder'];
		}
		return array(
			'value' => $result,
			'remainder' => $fraction
		);	
	}
	
	public function minimize_fraction($value) {
		$fraction = $this->fraction_values($value);
		$numerator = $fraction[0];
		$denominator = $fraction[1];
		$numerator_digits = $this->get_digits($numerator);
		$denominator_digits = $this->get_digits($denominator);
		$numerator_non_zero_point = -1;
		$denominator_non_zero_point = -1;
		foreach($numerator_digits as $key => $value) {
			if($value != 0 && $numerator_non_zero_point == -1) {
				$numerator_non_zero_point = $key;	
			}
		}
		foreach($denominator_digits as $key => $value) {
			if($value != 0 && $denominator_non_zero_point == -1) {
				$denominator_non_zero_point = $key;	
			}
		}
		$cutoff;
		if($numerator_non_zero_point < $denominator_non_zero_point) {
			$cutoff = $numerator_non_zero_point;	
		} else {
			$cutoff = $denominator_non_zero_point;	
		}
		$numerator = "";
		$denominator = "";
		foreach($numerator_digits as $key => $value) {
			if($key >= $cutoff) {
				$numerator = $value.$numerator;
			}
		}
		foreach($denominator_digits as $key => $value) {
			if($key >= $cutoff) {
				$denominator = $value.$denominator;
			}
		}
		return $numerator."/".$denominator;
	}
		
	function ceil($value) {
		if($this->negative($value)) {
			return $this->negative_value($this->floor($this->absolute($value)));	
		}
		if($this->fraction_values($value['remainder'])[0] != 0) {
			return $this->add($value['value'], 1);	
		}
		return $value['value'];
	}
	
	function round($value) {
		if($this->fraction_values($value['remainder'])[0] == 0) {
			return $value['value'];
		}
		$fraction_values = $this->fraction_values($value['remainder']);
		$numerator = $fraction_values[0];
		$denominator = $fraction_values[1];
		
		$numerator = $this->result($numerator, 2);
		if($this->larger($numerator, $denominator)) {
			return $this->add($value['value'], 1);	
		}
		return $value['value'];
	}
	
	
	
	public $truncate_fractions = false;
	public $truncate_fractions_length = NULL;
	function set_truncate_fractions($length) {
		if($length != false && $length != NULL && $length > 0) {
			$this->truncate_fractions = true;
			$this->truncate_fractions_length = $length;	
		} else {
			$this->truncate_fractions = false;	
		}
	}
		
	function execute_shorten_fraction_alt($value, $bypass_truncation=false) {
		$value_unaltered = $value;
		$negative = false;
		if($this->negative($value)) {
			$negative = true;	
		}
		$value = $this->absolute($value);
		$fraction_values = $this->fraction_values($value);
		$shorten = true;
		if($fraction_values[0] == $fraction_values[1]) {
			$value = "1/1";
			$shorten = false;	
		}
		if($fraction_values[0] == 0) {
			return "0/1";	
		}
		if($this->truncate_fractions_length > 0 && $bypass_truncation == false) {	
			$value = $this->minimize_fraction($value);
			if(strlen($fraction_values[1]) > $this->truncate_fractions_length) {
				$real_fraction = $this->real_fraction($value, $this->truncate_fractions_length);				
				$whole_value = $this->whole_common($real_fraction);
				$whole_value = $this->whole_numerator($whole_value);
				if($negative) {
					$whole_value = $this->negative_value($whole_value);	
				}
				return $whole_value;	
			}
			return $value_unaltered;
		}
		$prime_0;
		$prime_1;
		if($shorten) {
			$prime_0 = $this->prime($fraction_values[0]);
			$prime_1 = $this->prime($fraction_values[1]);
		}
		if($shorten && (($prime_0 && $prime_1) || $fraction_values[0] == 1 || $fraction_values[1] == 1)) {
			$value = $value;	
			$shorten = false;
		}
		if($shorten && $prime_0) {
			if($this->verified_divisible($fraction_values[1], $fraction_values[0])) {
				$denominator = $this->execute_divide($fraction_values[1], $fraction_values[0])['value'];
				$value = "1/".$denominator;		
				$shorten = false;
			}
		}
		if($shorten && $prime_1) {
			if($this->verified_divisible($fraction_values[0], $fraction_values[1])) {
				$numerator = $this->execute_divide($fraction_values[0], $fraction_values[1])['value'];
				$value = $numerator."/1";		
				$shorten = false;
			}
		}
		if($shorten) {
			if(strlen($fraction_values[1]) > 2) {
				$value = $this->minimize_fraction($value);
			}
			$value = $this->reduce_common_factors($value);		
		}
		if($negative) {
			$value = "-".$value;
		}
		return $value;	
	}
		
	private function execute_shorten_fraction_sub($value, $continue=true) {
		$fraction_values = $this->fraction_values($value);
		
		if($this->prime($fraction_values[0]) && $this->prime($fraction_values[1]) || $fraction_values[0] == 1 || $fraction_values[1] == 1) {
			return $value;	
		}
		if($this->prime($fraction_values[0])) {
			if($this->divisible($fraction_values[1], $fraction_values[0])) {
				$denominator = $this->execute_divide($fraction_values[1], $fraction_values[0])['value'];
				return "1/".$denominator;	
			}
		}
		if($this->prime($fraction_values[1])) {
			if($this->divisible($fraction_values[0], $fraction_values[1])) {
				$numerator = $this->execute_divide($fraction_values[0], $fraction_values[1])['value'];
				return $numerator."/1";	
			}
		}
		
		$fraction_values = $this->fraction_values($value);
		$divisible_values_numerator = $this->execute_divisible_values($fraction_values[0]);
		$divisible_values_denominator = $this->execute_divisible_values($fraction_values[1]);
		
		$divisible_values_denominator = array_merge($divisible_values_numerator, $divisible_values_denominator);
		rsort($divisible_values_denominator);
		$divided = false;
		foreach($divisible_values_denominator as $divisor_value) {
			if(!$divided) {
				$division_numerator = $this->execute_divide($fraction_values[0], $divisor_value);
				$division_denominator = $this->execute_divide($fraction_values[1], $divisor_value);
				if($this->fraction_values($division_numerator['remainder'])[0] == 0 && $this->fraction_values($division_denominator['remainder'])[0] == 0) {
					$fraction_values[0] = $division_numerator['value'];
					$fraction_values[1] = $division_denominator['value'];	
					$divided = true;
				}
			}
		}
		
		$value = $fraction_values[0]."/".$fraction_values[1];
		$sub_value = $value;
		
		return $value;
	}
			
	function shorten_fraction_alt($value) {
		$unaltered_value = $value;
		$unaltered_fraction_values = $this->fraction_values($unaltered_value);
		if($unaltered_fraction_values[0] != 0 && $unaltered_fraction_values[1] != 0) {
			$value = $this->minimize_fraction($value);
			$fraction_values = $this->fraction_values($value);
			if($this->prime($fraction_values[0]) || $this->prime($fraction_values[1])) {
				return $value;
			}
			$minimal_a = $this->execute_minimal_value($fraction_values[0]);
			$this->shorten_depth = 0;
			$minimal_b = $this->execute_minimal_value($fraction_values[1]);			
			$smallest_divider = $minimal_b['division'];
			$a_smaller = false;
			if($this->larger($minimal_b['division']['value'], $minimal_a['division']['value'])) {
				$smallest_divider = $minimal_a['division'];
				$a_smaller = true;
			}
			$division_a = $this->execute_divide($fraction_values[0], $smallest_divider['value']);
			$division_b = $this->execute_divide($fraction_values[1], $smallest_divider['value']);
			$remainder = $division_a['remainder'];
			if($a_smaller) {
				$remainder = $division_b['remainder'];	
			}
			$remainder_values = $this->fraction_values($remainder);
			$incomplete_fraction = false;
			$result_divider;
			$multiplier = array('value' => $remainder_values[1], 'remainder' => '0/1');			
			if($remainder_values[0] != 0 && $remainder_values[0] != 1) {
				$remainder_division = $this->execute_divide($remainder_values[1], $remainder_values[0]);
				$this->print_division($remainder_division);
				if($this->fraction_values($remainder_division['remainder'])[0] == 0) {
					$multiplier = array('value' => $remainder_division['value'], 'remainder' => '0/1');
					$result_divider = $remainder_values[0];
				} else {
					$incomplete_fraction = true;	
				}
			}
			
			if(!$incomplete_fraction) {
				$multiplication_a = $this->multiply_total($division_a, $multiplier);
				$multiplication_b = $this->multiply_total($division_b, $multiplier);
				if(isset($result_divider)) {
					
					$res_a = $this->execute_divide($multiplication_a, $result_divider);
					$res_b = $this->execute_divide($multiplication_b, $result_divider);
					if($this->fraction_values($res_a['remainder'])[0] == 0 && $this->fraction_values($res_b['remainder'])[0] == 0) {
						$multiplication_a = $res_a;
						$multiplication_b = $res_b;	
					}
				}
				return $multiplication_a['value']."/".$multiplication_b['value'];
			} else {
				$minimal_a = $minimal_a['value'];
				$minimal_b = $minimal_b['value'];
				
				$divisor;
				if($this->verified_divisible($unaltered_fraction_values[0], $minimal_b) && $this->verified_divisible($unaltered_fraction_values[1], $minimal_b)) {
					$divisor = $minimal_b;
				} else if($this->verified_divisible($unaltered_fraction_values[0], $minimal_a) && $this->verified_divisible($unaltered_fraction_values[1], $minimal_a)) {
					$divisor = $minimal_a;
				} else {
					return $value;	
				}
				$divisible = true;
				$set_division_numerator = array('value' => $unaltered_fraction_values[0], 'remainder' => '0/1');
				$set_division_denominator = array('value' => $unaltered_fraction_values[1], 'remainder' => '0/1');
				
				while($divisible && $divisor != 1) {
					$division_numerator = $this->execute_divide($set_division_numerator, $divisor);
					$division_denominator = $this->execute_divide($set_division_denominator, $divisor);
					
					if($this->fraction_values($division_numerator['remainder'])[0] == 0 && $this->fraction_values($division_denominator['remainder'])[0] == 0) {
						$set_division_numerator = $division_numerator;
						$set_division_denominator = $division_denominator;
					} else {
						$divisible = false;
					}
				}
				$result_value = $set_division_numerator['value']."/".$set_division_denominator['value'];
				return $result_value;
			}
		}
		return $value;
	}
	
	
	
	private function string_prefix($depth) {
		$counter = 0;
		$return_string = "-";
		while($counter <= $depth) {
			$return_string .= "-";
			$counter++;
		}
		return $return_string;
	}
	
	private $minimal_divider_set = 7;
	private $mapped_minimal_dividers;
	private $untouch_value = NULL;
	
	private function execute_minimal_value($value, $untouch_value=NULL) {
		$this->untouch_value = $untouch_value;
		$this->mapped_minimal_dividers = array();
		return $this->minimal_value($value);	
	}
	
	private function minimal_value($value, $divider_set=NULL, $last_divider=NULL) {
		$this->shorten_depth++;
		$divider = $divider_set;
		if($this->prime($value)) {
			return array('value' => $value, 'division' => array('value' => $value, 'remainder' => '0/1'));	
		}
		if($divider_set == NULL) {
			$minimal_divider_set = $this->minimal_divider_set;	
			if(!$this->larger($value, $minimal_divider_set, false)) {
				$minimal_divider_set = 2;	
			}
			$divider_division = $this->execute_divide($value, $minimal_divider_set);
			$divider = $divider_division['value']; 		
		}
		$prefix_string = $this->string_prefix($this->shorten_depth);
		$division = $this->execute_divide($value, $divider);
		$next_divider = $this->fraction_values($division['remainder'])[0];
		$this->mapped_minimal_dividers[$divider] = true;
		if($next_divider == 0) {
			$result = $divider;
			if($this->larger($result, $division['value']) && $this->prime($result)) {
				$result = $division['value'];
				if(!isset($this->mapped_minimal_dividers[$result])) {
					$this->mapped_minimal_dividers[$result] = true;	
				}
			}
		}
		if(!isset($result) || !$this->prime($result)) { 				
			if($next_divider == 1 || $next_divider == 0) {
				$next_divider = $divider++;
				
			}
			while(isset($this->mapped_minimal_dividers[$next_divider])) {
				$next_divider++;
				if($next_divider == $value) {
					$next_divider = 2;	
				}
			}
			$sub_result = $this->minimal_value($value, $next_divider, (isset($result) ? $result : NULL));
			if(!isset($result) || ($this->larger($sub_result, 2, false) && $this->larger($result, $sub_result))) {
				$result = $sub_result;	
			}
		}
		$counter = 2;
		$max_divider = $this->execute_divide($result, 2)['value'];
		$smallest_value = $result;
		$break = false;
		if(!isset($result)) {
			$result = 1;	
		}
		if($divider_set == NULL) {
			if(!$this->prime($result)) {
				while($counter < $max_divider && !$break) {
					if($this->divisible($result, $counter)) {
						$result_division = $this->execute_divide($result, $counter)['value'];
						if($this->larger($result_division, 2, false) && $result_division != $this->untouch_value) {
							$result = $result_division;	
						}
					}
					$counter++;	
				}
			} else {
				$minimization_result = $result;
				$counter = $minimization_result;
				while($this->larger($counter, 1, false)) {
					if($this->divisible($value, $counter) && $counter != $this->untouch_value) {
						$minimization_result = $counter;	
					}
					$counter--;
				}
				$result = $minimization_result;
			}
			$division_result = $this->execute_divide($value, $result);
			return array(
				'value' => $result,
				'division' => $division_result
			);
		} else {
			return $result;	
		}
	}
	
	public $shorten_depth = 0;
	
	public function shorten_tuple($value_a, $value_b) {
		$fraction_a = $this->fraction_values($value_a);
		$fraction_b = $this->fraction_values($value_b);
		$above_unit = true;
		$min_divider = 1;
		$counter = 1;
		while(!$above_unit) {
			
			$numerator_a = $fraction_a[0]/$counter;
			$denominator_a = $fraction_a[1]/$counter;
			$numerator_b = $fraction_b[0]/$counter;
			$denominator_b = $fraction_b[1]/$counter;
			if($numerator_a < 1 || $denominator_a < 1 || $numerator_a < 1 || $denominator_b < 1) {
				$above_unit = false;	
			}
			if($this->divisible($numerator_a, $counter) && $this->divisible($denominator_a, $counter) && $this->divisible($numerator_b, $counter) && $this->divisible($denominator_b, $counter)) {
				$min_divider = $counter;	
			}
			$counter++;
		}
		return array(
			$this->divide($fraction_a[0], $min_divider)['value']."/".$this->divide($fraction_a[1], $min_divider)['value'],
			$this->divide($fraction_b[0], $min_divider)['value']."/".$this->divide($fraction_b[1], $min_divider)['value']
		);
	}
	
	public function common_denominator($value_a, $value_b) {
		$fraction_values_a = $this->fraction_values($value_a);
		$fraction_values_b = $this->fraction_values($value_b);
		if($fraction_values_a[0] == 0) {
			return array(
				'0/'.$fraction_values_b[1],
				$value_b
			);
		} else if($fraction_values_b[0] == 0) {
			return array(
				$value_a,
				'0/'.$fraction_values_a[1],
			);
		}
		
		
		$short = $this->multiply_fraction($value_a, $value_b, false);
		$common_short = $this->fraction_values($short)[1];
		$result_a = $this->lengthen_to($value_a, $common_short);
		$result_b = $this->lengthen_to($value_b, $common_short);
		
		$value_a = $this->fraction_values($result_a);
		$value_b = $this->fraction_values($result_b);
		
		
		
		return array(
			$result_a,
			$result_b
		);
	}
	
	function multiple_denominators($values) {
		$cur_common = NULL;
		foreach($values as $key => $value) {
			if($key > 0) {
				if($cur_common == NULL) {
					$cur_common = $this->common_denominator($value, $values[$key-1]);
				} else {
					$cur_common = $this->common_denominator($value, $cur_common[0]);
				}
			}
		}
		foreach($values as $key => $value) {
			$fraction = $this->fraction_values($value);
			$common_fraction = $this->fraction_values($cur_common[0]);
			$multiplier = $this->execute_divide($common_fraction[1], $fraction[1])['value']; 			
			$result = $this->result($fraction[0], $multiplier)."/".$this->result($fraction[1], $multiplier);
			$values[$key] = $result;	
		}
		return $values;
	}
	
	public function fraction_values($value) {
		return explode("/", $value);	
	}
	
	public function fraction_string($fraction) {
		return $fraction[0]."/".$fraction[1];	
	}
	
	private function collect_results($base=10) {
		$result = "0";
		$fractions = array();
		foreach($this->intermediate_results as $result_value) {
			$result_value = $this->numeric_value($result_value);
			$result = $this->add($result, $result_value, $base);	
		}
		
		return $result;
	}
	
	public function place_decimal($value, $length, $remove_decimal=false, $prefix=false) {
		$original_length_set = $length;
		if($remove_decimal && strpos($value, ".") !== false) {
			$split = explode(".", $value);	
			$value = $split[0].$split[1];
			
			$start_offset = strlen($split[0]);
			$length = (strlen($value) - ($start_offset+$length));
		} else if($length < 0) {
			$length = -$length;
		}
		if($prefix && ($length >= (strlen($value)-1))) {
			$prepend = $length - (strlen($value)-1)+1;
			
			$counter = 0;
			while($counter < $prepend) {
				$value = "0".$value;
				$counter++;	
			}
			
		} else if($length < 0 && $original_length_set > 0) {
			$append = -$length;			$counter = 0;
			while($counter < $append) {
				$value .= "0";
				$counter++;	
			}
		}
		$digits = $this->get_digits($value);
		$result = "";
		foreach($digits as $key => $digit) {
			if($key == ($length)) {
				$result = ".".$result;	
			}
			$result = $digit.$result;
		}
		
		if(strpos($result, ".") !== false) {
			$split = explode(".", $result);
			if(strlen($split[1]) == 0) {
				$result = $split[0];	
			}
		}
		if(strpos($result, ".") === false && strlen($result) > 1 && substr($result, 0, 1) == 0) {
			$result = substr($result, 1);	
		}
		$result = $this->trim($result);
		return $result;
	}
	
	public function place_decimal_alt($value, $length, $remove_decimal=false, $prefix=false) {
		$original_length_set = $length;
							
		$unaltered_length = $length;
		if($length < 0) {
		} else {
			
		}
		if($unaltered_length >= strlen($value)) {
			$prepend = $length - strlen($value);
			$counter = 0;
			while($counter <= $prepend) {
				$value = "0".$value;
				$counter++;	
			}
			
		} else if($length < 0) {
			$append = -$length;			$counter = 0;
			while($counter < $append) {
				$value .= "0";
				$counter++;	
			}
		}
		$length = $unaltered_length;
		if($length < 0) {
			$remove_decimal = true;
		}
		$digits = $this->get_digits($value);
		$result = "";
		foreach($digits as $key => $digit) {
			if($key == ($length)) {
				if(!$remove_decimal) {
					$result = ".".$result;	
				}
			}
			$result = $digit.$result;
		}
		if($length == strlen($result)) {
			$result = "0.".$result;		
		}
		
		
		if(strpos($result, ".") !== false) {
			$split = explode(".", $result);
			if(strlen($split[1]) == 0) {
				$result = $split[0];	
			}
		}
		if(strpos($result, ".") === false && strlen($result) > 1 && substr($result, 0, 1) == 0) {
			$result = substr($result, 1);	
		}
		$result = $this->trim($result);
		return $result;
	}
	
	
	
	function pad_zeros($value, $length, $reverse=false) {
		$counter = 0;
		while($counter < $length) {
			if(!$reverse) {
				$value = $value."0";
			} else {
				$value = "0".$value;
			}
			$counter++;	
		}
		return $value;
	}
	
	function minimize_value($value, $maximum_value=NULL) {
		$digits = $this->get_digits($value);
		$counter = 0;
		$non_zero = false;
		$cutoff = -1;
		$result = "";
		foreach($digits as $key => $digit) {
			if($digit != 0 && !$non_zero || ($maximum_value != NULL && $key == $maximum_value)) {
				$non_zero = true;
				$cutoff = $key;	
			}
			if($non_zero) {
				$result = $digit.$result;	
			}
		}
		return array(
			'value' => $result,
			'exponent' => $cutoff
		);
	}
	
	function unit_point($value) {
		$translation = 0;
		if(strpos($value, ".") !== false) {
			$split = explode(".", $value);
			$translation = -strlen($split[0]);
		} else {
			$translation = -strlen($value);	
		}
		$value = $this->place_decimal($value, $translation, true, true);
		return array(
			'value' => $value,
			'exponent' => $translation
		);
	}
	
	private $partition_size = 5;
	
	private $partition_depth = 0;
	
	private function partition_division($value, $divider) {
		$partition_size = $this->partition_size;
		
		
		$divider_minimized = $this->minimize_value($divider);
		$value_minimized = $this->minimize_value($value);
		$exponent = $this->subtract($value_minimized['exponent'], $divider_minimized['exponent']);
		
		$result = $this->sub_divide($divider_minimized['value'], $value_minimized['value']);
		
		
		
		$result_unit = $this->unit_point($result);
		
		
		$result_exponent = $result_unit['exponent'];
		
			
		$exponent = $this->add_all($exponent, $result_exponent);
		
		
		$result = $result_unit['value'];
		
		
		
		$flip = 1 / $result;
		
		$flip = $this->place_decimal($flip, $exponent, true, true);
		
		
		return $flip;
	}
	
	public function sub_divide($divider, $value, $change_base=false) {
		$base = 10;
		
		
		$test_sum = 0;
		
		
		
		$divider_length = strlen($divider);
		$divider = $this->remove_leading_zeros($divider, true);
		
		$divider_exponent_translation = $divider_length - strlen($divider);
		$divider_length = strlen($divider);
		
		$this->intermediate_results = array();
		$digits = $this->get_digits($value);
		foreach($digits as $exponent => $digit) {
			if($digit != 0) {
												
				
				$exponent_translation = 0;
				if(strlen($digit) <= $divider_length) {
					$digit = $this->pad_zeros($digit, $divider_length);	
					if($digit < $divider) {
						$divider_length += 1;
					}
					$exponent_translation = $divider_length;
				}
				
				$exponent_alteration = 0;
				
				$exponent = $exponent-$exponent_translation-$divider_exponent_translation;				
				$result;
				$result = $digit / $divider;
				
				$this->intermediate_results[] = array(
					'value' => $result,
					'exponent' => $exponent
				);
			}
		}
		
		
		$result = $this->collect_results($base);
		
		return $result;
	}
	
	function combinations($values) {
		$combinations = array($values);		
		foreach($values as $key => $value) {
			$sub_values = $values;
			unset($sub_values[$key]);
			
			$combinations[] = $sub_values;	
			if(count($sub_values) > 0) {
				$sub_combinations = $this->combinations($sub_values);
				$combinations = array_merge($combinations, $sub_combinations);	
			}
		}
		$result = array();
		foreach($combinations as $combination) {
			if(!in_array($combination, $result) && count($combination) > 0) {
				$result[] = $combination;	
			}
		}
		return $result;
	}
		
	public function _divisible($value, $divider) {
		$division = $this->execute_divide($value, $divider);
		if($division['remainder'] == 0) {
			return true;	
		}
		return false;
	}
	
	private function semi_rational($value) {
		$fraction = $this->fraction_values($value);
		$rational = false;
		$length = strlen($fraction[1]);
		$fraction[0] = $this->add_zeros($fraction[0], $length);
		$divisible = $this->_divisible($fraction[0], $fraction[1]);
		if($divisible) {
			return true;	
		} else {
		}
		return false;
	}
	
	private function logarithm_sub($value, $base) {
		return $this->logarithm_base($value, $base);		
	}
	
	function set_logarithm_precision($logarithm_precision) {
		$this->logarithm_iteration_count = $logarithm_precision;	
	}
	
	function logarithm($value, $base=array('value' => 2, 'remainder' => '0/1'), $iteration_count=NULL) {
		if($iteration_count === NULL) {
			$iteration_count = $this->logarithm_iteration_count;
		}
		
		
		if($base == 'e' || $this->fraction_values($base['remainder'])[0] != 0 || $base['value'] > 10) {
			return $this->logarithm_sub($value, $base);	
		}
		
		$altered_base;
		if($base['value'] != 10) {
			$altered_base = $this->change_base($value['value'], $base['value']);
		} else {
			$altered_base = $value['value'];	
		}
		$exponent = strlen($altered_base)-1;
		
		$divider = $this->execute_power_whole($base, $exponent);
		$division = $this->execute_divide($value, $divider);
		$fraction_values = $this->fraction_values($division['remainder']);
		$whole_part = array('value' => $division['value'], 'remainder' => '0/1');
		$fraction_whole = array('value' => $division['value'], 'remainder' => '0/1');
		$fraction_part = array('value' => 1, 'remainder' => $fraction_values[0]."/".($this->result($fraction_values[1], $fraction_whole['value'])));
		
		
		$fraction_set = $fraction_part;
		
		
		$logarithm_common = $this->logarithm_sub($fraction_part, $base);
		
		
		$result = array('value' => $exponent, 'remainder' => '0/1');		
		$log_whole_part = $this->logarithm_sub($fraction_whole, $base);
		
		
		$result = $this->add_total($result, $logarithm_common);
		$result = $this->add_total($result, $log_whole_part);
		return $result;
		
	}
	
	function natural_logarithm($value) {
		$base_numerator = $this->subtract_total($value, array('value' => 1, 'remainder' => '0/1'));	
		$base_denominator = $this->add_total($value, array('value' => 1, 'remainder' => '0/1'));
		$base = $this->execute_divide($base_numerator, $base_denominator);
		
		$total_sum = $base;
		$counter = 3;
		while($counter < $this->logarithm_iteration_count) {
			$added_value = $this->power($base, array('value' => $counter, 'remainder' => '0/1'));
			$added_value = $this->execute_divide($added_value, $counter);
			
			$total_sum = $this->add_total($total_sum, $added_value); 
			if($this->truncate_fractions_length > 0) {
				$total_sum['remainder'] = $this->execute_shorten_fraction($total_sum['remainder']);	
			}
			$counter += 2;	
		}
		
		$total_sum = $this->multiply_total($total_sum, array('value' => 2, 'remainder' => '0/1'));
		return $total_sum;
	}
	
	private $logarithm_iteration_count = 12;
	
	function logarithm_base($value, $base) {
		$natural_logarithm_value = $this->natural_logarithm($value);
		if($base == 'e') {
			return $natural_logarithm_value;	
		}
		$base_value = $this->natural_logarithm($base);
		$result = $this->execute_divide($natural_logarithm_value, $base_value);
		return $result;	
	}
	
	function execution_valid_value($value, $power=1) {
		$power = $this->ceil($power);
		$strlen = strlen($value['value']);
		$strlen = $this->result($strlen, $power);
		if($this->larger($strlen, $this->maximum_divider_exponent, false)) {
			return false;
		}
		$fraction_values = $this->fraction_values($value['remainder']);
		$strlen_numerator = strlen($fraction_values[0]);
		$strlen_numerator = $this->result($strlen_numerator, $power);
		$strlen_denominator = strlen($fraction_values[1]);
		$strlen_denominator = $this->result($strlen_denominator, $power);
		if($this->larger($strlen_numerator, $this->maximum_divider_exponent, false)) {
			return false;
		}
		if($this->larger($strlen_denominator, $this->maximum_divider_exponent, false)) {
			return false;
		}
		return true;
	}
	
	function quick_fraction($value) {
		$values = $this->fraction_values($value);
		$result = $this->real_fraction($value, 15);
		return $result;	
	}
	
	function absolute_fraction($value) {
		
		if(strpos($value, '-') !== false) {
			$split = explode("-", $value);
			return $split[1];
		}
		return $value;
	}
	
	private $power_depth = 0;
	private $maximum_power = 24;
	
	
	function whole_common($value) {
		$e_position = strpos($value, "E");
		if($e_position !== false) {
			$e_translation = (float)substr($value, $e_position+1);
			$value = substr($value, 0, $e_position);	
			$decimal_place = strpos($value, ".");
			$place = $decimal_place + $e_translation;
			$place = strlen($value)-1 - $place;
			
			$value = $this->place_decimal_alt($value, $place, false, true);
			
		}
		$negative = false;
		if(strpos($value, "-") !== false) {
			$negative = true;	
		}
		$value = $this->absolute($value);
		if(strpos($value, ".") !== false) {
			$split = explode(".", $value);
			$fraction = "0.".$split[1];
			$common = $this->common($fraction);
			$result = array(
				'value' => $this->absolute($split[0]),
				'remainder' => $this->absolute($common)
			);
		} else {
			if($value == "" || $value == NULL) {
				$value = "0";	
			}
			$result = array(
				'value' => $value,
				'remainder' => '0/1'
			);
		}
		if($negative) {
			$result = $this->negative_value($result);	
		}
		return $result;
	}
	
	private $errors = array();
	
	function pop_error() {
		if(count($this->errors) > 0) {
			return array_pop($this->errors);	
		}
		return NULL;
	}
		
	function common_rational_root($fraction_a, $fraction_b) {
		$fraction_values_a = $this->fraction_values($fraction_a);
		$fraction_values_b = $this->fraction_values($fraction_b);
		$multiplier_a = $fraction_values_a[1]."/".$fraction_values_a[1];
		$multiplier_b = $fraction_values_b[0]."/".$fraction_values_b[0];
		$multiplication_a = $fraction_a;
		$multiplication_b = $fraction_b;
		while($multiplication_a != $multiplication_b) {
			if(!$this->larger($multiplication_a, $multiplication_b)) {
				$multiplication_a = $this->multiply_fraction($multiplication_a, $multiplier_a);	
			} else {
				$multiplication_b = $this->multiply_fraction($multiplication_b, $multiplier_b);
			}
		}
		return $multiplication_a;
	}
	
	private function rational_division(&$dividers, $next_root, $value_a, $value_b) {
		$division_a;
		$division_b;
		if($this->divisible($next_root, $value_a)) {
			$division_a = $this->execute_divide($value_a, $next_root)['value'];
			if($division_a > 1) {
				if(!isset($dividers[$division_a])) {
					$dividers[$division_a] = 0;	
				} else {
					$dividers[$division_a]++;
					return $division_a;	
				}
			}
		}
		if($this->divisible($next_root, $value_b)) {
			$division_b = $this->execute_divide($value_b, $next_root)['value'];
			if($division_b  > 1) {
				if(!isset($dividers[$division_b])) {
					$dividers[$next_root] = 0;	
				} else {
					$dividers[$division_b]++;
					return $division_b;	
				}
			}
		}
		return false;
	}
	
	function whole_numerator($value) {
		$fraction_values = $this->fraction_values($value['remainder']);
		$numerator = $this->add($fraction_values[0], $this->result($fraction_values[1], $value['value']));
		
		return $numerator."/".$fraction_values[1];	
	}
	
		
	function factorial($value) {
		$factorial = new factorial($value, $this);
		$resolution = $factorial->resolve();
		return $resolution;
	}
	
	function partial_factorial($value, $stop=1) {
		if($value == 1 || $value == $stop) {
			return $value;	
		}
		return $this->result($value, $this->partial_factorial($this->subtract($value, 1), $stop));
	}
	
	private function gamma($n) {
		
		$sin_value = $this->multiply_total($this->pi(), $n);
		$sin_value = $this->quick_numeric($sin_value);
		$sin = sin($sin_value);
		$sin = $this->whole_common($sin);
		$result = $this->execute_divide($this->pi(), $sin);
		
		
		return $result;
	}
	
	private function next_rational_root_sub($value, $set_power, $same) {
		$root = pow($value, 1/$set_power);
		$root_floor = $this->floor($root);
		if($root == $root_floor) {
		} else {
			$root_floor = $this->add($root_floor, 1);
			
		}
		$value = $this->execute_power_whole(array('value' => $root_floor, 'remainder' => '0/1'), array('value' => $set_power, 'remainder' => '0/1'));
		return array('value' => $value['value'], 'root' => $root_floor);
	}
	
	private $next_rational_root_start_first;
	private $next_rational_root_start_second;
	
	function next_rational_root($value, $set_power, $same=true) {
		$root = $this->root($value, $set_power);
		
		if($root !== false) {
			return array('root' => $root, 'value' => $value);	
		}
		$root = $this->root_closest_result;
		$root = $this->add($root, 1);
		return array('root' => $root, 'value' => $this->execute_power_whole($root, $set_power));
	}
	
	function list_rational_roots($from, $to, $set_power=2) {
		if(!$this->larger($from, 1)) {
			$from = 1;
		}
		$root_results = array();
		$rational_root = $this->next_rational_root_list($from, $set_power, true, false);
		$root_results[] = $rational_root;
		while($this->larger($to, $rational_root['value'])) {
			$rational_root = $this->next_rational_root_list($this->add($rational_root['value'], 1), $set_power, true, true);
			if($this->larger($to, $rational_root['value'])) {
				$root_results[] = $rational_root;
			}
		}
		return $root_results;
	}
	
	function next_rational_root_list($value, $set_power=2, $same=true, $previous_set_start=false) {
		
		$reverse = false;
		$length = strlen($value)-1;
		$power = 2;
		
		$execute_power = $set_power;
		$unaltered_power = $set_power;
		$set_power -= 2;
		$larger_root = true; 		
		if($larger_root) { 			
			$decimal_mult;
			$incremented;
			$decimal_mult_root;
			if(!isset($this->next_rational_root_start_first)) { 				
				
				$start_root_prefix = $this->subtract($this->next_rational_root($value, $unaltered_power)['root'], 2);
				$start_root_prefix_unaltered = $this->result($start_root_prefix, $start_root_prefix);
				
				$decimal_mult_root = $start_root_prefix;				
				$decimal_mult;	
				
				
				$incremented_root = $this->add($decimal_mult_root, 1);
				$incremented = $this->execute_power_whole($incremented_root, $power)['value'];
				$decimal_mult = $start_root_prefix_unaltered;
				
				$set_root = $decimal_mult;
				$this->next_rational_root_start_first = array('value' => $start_root_prefix_unaltered, 'root' => $start_root_prefix);
				$this->next_rational_root_start_second = array('value' => $incremented, 'root' => $incremented_root);
			} else {
				$first = $this->next_rational_root_start_first;
				$second = $this->next_rational_root_start_second;
				$decimal_mult_root = $first['root'];
				$incremented = $second['value'];
				$decimal_mult = $first['value'];
					
			}
			
			
			
			
			$next_root;
			$next_root = $this->add($this->subtract($this->result($incremented, $power), $decimal_mult), $power);
			$current_root_root = $this->add($decimal_mult_root, 2);
			
			$next_root_value;
			if($set_power >= 1) {
				$next_root_value = $this->execute_power_whole(array('value' => $current_root_root, 'remainder' => '0/1'), $set_power);
				$next_root_value = $this->multiply_total($next_root_value, array('value' => $next_root, 'remainder' => '0/1'))['value'];
			} else {
				$next_root_value = $next_root;	
			}
				$set_root = $next_root_value;
			$count = 0;
			$store = $next_root;
			while(!$this->larger($next_root_value, $value)) { 				
				$store = $next_root;
				$next_root = $this->add($this->subtract($this->result($next_root, $power), $incremented), $power);	
				$current_root_root = $this->add($current_root_root, 1);
				
				if($set_power >= 1) {
					$next_root_value = $this->execute_power_whole(array('value' => $current_root_root, 'remainder' => '0/1'), $set_power);
					$next_root_value = $this->multiply_total($next_root_value, array('value' => $next_root, 'remainder' => '0/1'))['value'];
				} else {
					$next_root_value = $next_root;	
				}
				if($this->larger($next_root_value, $value, false)) { 					
					$set_root = $next_root_value;				
				}
				$incremented = $store;
				
			}
			
			return array('value' => $set_root, 'root' => $current_root_root);
		} else {
			$counter = 2;
			$root = $this->result($counter, $counter);
			$root = $this->execute_power_whole(array('value' => $counter, 'remainder' => '0/1'), array('value' => $execute_power, 'remainder' => '0/1'));
			while($this->larger_total(array('value' => $value, 'remainder' => '0/1'), $root, !$same)) {
				$counter++;	
				$root = $this->execute_power_whole(array('value' => $counter, 'remainder' => '0/1'), array('value' => $execute_power, 'remainder' => '0/1'));
			}
			return array('value' => $root['value'], 'root' => $counter);

		}
	}
	
	private function preprocess_power($value, $power) {
		$value_fraction = $this->fraction_values($value['remainder']);
		if(strlen($value['value']) > 255) {			
			$rational_root = $this->next_rational_root($value['value'], $power, true);
			$rational_root_sqrt = $rational_root['root'];
			$rational_root = $rational_root['value'];
			$division_part = $this->execute_divide(array('value' => $rational_root, 'remainder' => '0/1'), $value);
			$result = array('value' => $rational_root_sqrt, 'remainder' => '0/1');
			
			$quick_fraction = $this->numeric_whole($division_part['value'], $this->quick_fraction($division_part['remainder']));
			
			$part_result = $this->execute_power($division_part, $power);
			
			$result = $this->execute_divide($result, $part_result, true);
			return $result;
		} else {
			return $this->execute_power($value, $power);
		}
	}
	
	private function intermediate_process_power($value, $power) {
		$fraction_values = $this->fraction_values($value['remainder']);
		$denominator_root = $this->next_rational_root($fraction_values[1], $power);
		if($denominator_root['value'] == $fraction_values[1]) {
			$whole_value = $this->make_whole($value);
			$value_root = $this->next_rational_root($whole_value['value'], $power);
			if($whole_value['value'] == $value_root['value']) {
				$division = $this->execute_divide($value_root['root'], $denominator_root['root']);
				return $division;
			}
		}
		return $this->execute_power($value, $power);
	}
	
	function power($value, $power) {
		
		$negative_power = false;
		if($this->negative($power)) {
			$negative_power = true;
			$power = $this->absolute($power);	
		}
		$power_fraction_values = $this->fraction_values($power['remainder']);
		$result_fraction = array('value' => 1, 'remainder' => '0/1');
		if($power_fraction_values[0] != 0) {
			$result_fraction = $this->preprocess_power($value, $power_fraction_values[1]); 			
			if($power_fraction_values[0] != 1) {
				$result_fraction = $this->execute_power_whole($result_fraction, $power_fraction_values[0]);
			}
		}
		$result_whole = array('value' => 1, 'remainder' => '0/1');
		if($power['value'] != 0) {
			$result_whole = $this->execute_power_whole($value, array('value' => $power['value']));
		}
		$result = $this->multiply_total($result_whole, $result_fraction);
		
		if($negative_power) {
			$result = $this->execute_divide(1, $result);
		}
		return $result;
	}
	
	
	function execute_power_whole($value, $power) {
		if(!is_array($power)) {
			$power = array('value' => $power, 'remainder' => '0/1');	
		}
		if(!is_array($value)) {
			$value = array('value' => $value, 'remainder' => '0/1');	
		}
		if($power['value'] == 1) {
			return $value;	
		}
		if($power['value'] == 0) {
			return array('value' => '1', 'remainder' => '0/1');	
		}
		if($this->larger($power['value'], 6)) {
			$power_partition = $this->execute_divide($power['value'], 6);
			$set_power = $power_partition['value'];
			$remainder = $this->subtract($power['value'], $this->result($set_power, 6));
			$intermediate_result = $this->execute_power_whole($value, array('value' => $set_power, 'remainder' => '0/1'));
			$power_max = 5;			
			$part_result = $intermediate_result;
			$counter = 0;
			while($this->larger($power_max, $counter, false)) {
				$part_result = $this->multiply_total($part_result, $intermediate_result);
				$counter = $this->add($counter, 1);	
			}
			$counter = 0;
			while($this->larger($remainder, $counter, false)) {
				$part_result = $this->multiply_total($part_result, $value);
				$counter = $this->add($counter, 1);	
			}
			return $part_result;
		} else {
			$counter = 0;
			$result = $value;
			$power_max = $this->subtract($power['value'], 1);
			while($this->larger($power_max, $counter, false)) {
				$result = $this->multiply_total($result, $value);
				$counter = $this->add($counter, 1);	
			}
			return $result;
		}	
	}
	
	private $make_whole_multiplier = 1;
	function make_whole($value) {
		$denominator_fraction_values = $this->fraction_values($value['remainder']);
		$multiplier = array('value' => '0', 'remainder' => $denominator_fraction_values[1]."/1"); 		
		$denominator_division = $this->multiply_total($multiplier, $value);
		$this->make_whole_multiplier = $denominator_fraction_values[1];
		$value = $denominator_division;
		return $value;
	}
		
	private $post_power = 2;
	
	private function postprocess_power($value, $power) {
		$this->post_power = $power;
		$this->decomposed_values = array();
		$whole_fraction = $this->whole_numerator($value);
		$fraction_values = $this->fraction_values($whole_fraction);
		return $this->decompose_values($fraction_values[0], $fraction_values[1]);	
	}
	
	private $decomposed_values = array();
	
	function decompose_values($numerator, $denominator) {
		$stop = false;
		$value_denominator = $this->largest_rational_root_divider($denominator);
		$value_numerator = $this->largest_rational_root_divider($numerator, $value_denominator);
		if($value_denominator == NULL || $value_numerator == NULL) {
			$value_denominator = $this->mid_point_divider($denominator);
			$value_numerator = $this->mid_point_divider($numerator);		
			if($this->larger($value_denominator, $value_numerator, false)) {
				$value_numerator = $numerator;
				$stop = true;
			}
		}
		if($value_denominator == $denominator || $value_numerator == $numerator) {
			$stop = true;	
		}
		$numerator_remainder = $this->execute_divide($numerator, $value_numerator)['value'];
		$denominator_remainder = $this->execute_divide($denominator, $value_denominator)['value'];
		$fraction = $value_numerator."/".$value_denominator;
		$this->decomposed_values[] = $fraction;
		if(!$stop) {
			return $this->decompose_values($numerator_remainder, $denominator_remainder);
		} else {
			return $this->decomposed_values;	
		}
	}
	
	function mid_point_divider($value, $maximum_value) {
		if($this->prime($value)) {
			return $value;	
		}
		$start_point;
		if($maximum_value != NULL) {
			$start_point = $maximum_value;	
		} else {
			$root = sqrt($value);
			$root = $this->floor($root);
			$start_point = $root;	
		}
		$current_value = $start_point;
		$divisible = false;
		if($this->verified_divisible($value, $current_value)) {
			$divisible = true;	
		}
		while($this->larger($current_value, 0, false) && !$divisible) {
			$current_value = $this->subtract($current_value, 1);
			if($this->verified_divisible($value, $current_value)) {
				$divisible = true;	
			}
		}
		if(!$divisible) {
			return $value;	
		}
		return $current_value;
	}
	
	function largest_rational_root_divider($value, $maximum_value=NULL) {
		if($this->prime($value)) {
			return $value;	
		}
		$mid_point;
		$start_point;
		if($maximum_value != NULL) {
			$start_point = $this->next_rational_root($maximum_value, $this->post_power);	
		} else {
			$mid_point = $this->execute_divide($value, 2)['value'];
			$start_point = $this->next_rational_root($mid_point, $this->post_power);	
		}
		$divisible = false;
		
		$next_root = $this->add($start_point['root'], 1);
		$next_value = $this->execute_power_whole(array('value' => $next_root, 'remainder' => '0/1'), array('value' => $this->post_power, 'remainder' => '0/1'))['value'];
		
		$last_value = $next_value;
		$next_value = $start_point['value'];
		
		$calculated_value = $next_value;
		$current_root = $next_root;
		
		if($this->verified_divisible($value, $calculated_value)) {
			$divisible = true;	
		}
		while($this->larger($current_root, 0, false) && !$divisible) {
			$calculated_value = $this->result($next_value, 2);
			$calculated_value = $this->subtract($calculated_value, $last_value);
			$calculated_value = $this->add($calculated_value, 2);
			$current_root = $this->subtract($next_root, 1);
			if($this->verified_divisible($value, $calculated_value)) {
				$divisible = true;	
			}
			
			$last_value = $next_value;
			$next_value = $calculated_value;
		}
		if(!$divisible) {
			return NULL;	
		}
		return $calculated_value;
	}
	
	private function square_root_approximation_series($insert_value, $iteration_count=8) {
		
		$result = array('value' => '1', 'remainder' => '0/1');
		
				
		$iteration = 1;
		$previous_divider = NULL;
		$last_divider = NULL;
		$multiplier = array('value' => 1, 'remainder' => '0/1');
		$interlope = 0;
		$current_power = array('value' => '0', 'remainder' => '-1/2');
		$value_multiplier = array('value' => '0', 'remainder' => '1/2');
		$current_power_numerator = 1;
		
		while($iteration < $iteration_count) {
			$multiplier = $this->multiply_total($multiplier, $insert_value);			
			
			$divider = 1;
			if($iteration > 1) {
				$current_power = array('value' => '0', 'remainder' => "-".$current_power_numerator."/2");
				$value_multiplier = $this->multiply_total($value_multiplier, $current_power);
				$current_power_numerator = $this->add($current_power_numerator, 2);
				
				
				$divider = $this->factorial($iteration);
				
			}
			
			
			$value_multiplier_value = $this->execute_divide($value_multiplier, $divider);
			
			
			$addition_value = $this->multiply_total($multiplier, $value_multiplier_value);
			
			$result = $this->add_total($result, $addition_value);
			$iteration++;
		}	
		return $result;
	}
	
	function get_root_closest_result() {
		return $this->root_closest_result;	
	}
	
	public $root_closest_result;
	
	
	function root($x, $n) {
		$x = $this->absolute($x);
		if($x == 1) {
			$this->root_closest_result = "1";
			return 1;	
		} else if($x == 0) {
			$this->root_closest_result = "0";
			return 0;	
		}
		$root_result = $this->root_sub($x, $n);
		$result = $this->execute_power_whole($root_result, $n)['value'];
		if($x == $result) {
			$this->root_closest_result = $root_result;
			return $root_result;	
		}
		$root_result = $this->subtract($root_result, 1);
		$this->root_closest_result = $root_result;
		return false;	
	}
	
	
	private function root_sub($x, $n) {
		$guess = "1";
		$step = 1;
		$counter = 0;
		while(true) {
			$w = $this->execute_power_whole($this->add($guess, $step), $n)['value'];
			if($w == $x) {
				return $this->add($guess, $step);			
			} else if($this->larger($x, $w)) {
				$step = $this->bit_shift($step, 1);	
			} else if($step == 1) {
				return $this->add($guess, 1);	
			} else {
				$guess = $this->add($guess, $this->bit_shift_right($step, 0));
				$step = 1;	
			}
		}
		
	}
	
	
	public $root_fraction_precision = array('value' => '0', 'remainder' => '1/100');
	public $maximum_root_fraction_iterations = 0;
	
	function root_fraction($number, $root, $p = NULL) {
		if(!is_array($number)) {
			$number = array('value' => $number, 'remainder' => '0/1');	
		}
		if($p == NULL) {
			$p = $this->root_fraction_precision;	
		}
		$whole = $this->whole_numerator($number);
		$root_solver = new root_solver($whole, $root, $this);
		$num = $root_solver->approximate_value();
		
		$x[0] = $num;
		$x[1] = $this->execute_divide($num, $root);		
		$root = array('value' => $root, 'remainder' => '0/1');
		$counter = 0;
		while($this->larger_total($this->absolute($this->subtract_total($x[1], $x[0])), $p)) {			
			$x[0] = $x[1];
						
			$root_term = $this->subtract_total($root, array('value' => 1, 'remainder' => '0/1'));
			$first_term = $this->multiply_total($root_term, $x[1]);
			
			$numerator = $number;			
			$denominator = $this->execute_power_whole($x[1], $root_term);
			
			$second_term = $this->execute_divide($numerator, $denominator);
			
			$total_term = $this->add_total($first_term, $second_term);
			
			$x[1] = $this->execute_divide($total_term, $root);
			if($this->truncate_fractions_length > 0) {
				$x[1]['remainder'] = $this->execute_shorten_fraction($x[1]['remainder']);	
			}
			
			
		}
		return $x[1];
	} 
	
	private function square_root($value) {
		return $this->root($value, 2);
	}
	
	private function cubic_root($value) {
		return $this->root($value, 3);
	}
	
	function trim($value) {
		$digits = str_split($value);
		$remove = 0;
		$decimal_point_found = false;
		foreach($digits as $index => $digit) {
			if(!$decimal_point_found) {
				$remove++;	
			}
			if($digit == "." || $digit != "0") {
				$decimal_point_found = true;	
			}
		}
		$remove -= 1;
		$value = substr($value, $remove);
		return $value;
	}
		
	private function square_root_altered($value) {
		$value_total;
		if(!is_array($value)) {
			$value_total = array('value' => $value, 'remainder' => '0/1');
		} else {
			$value_total = $value;	
		}
		$approximate_value = $this->square_root_approximation($value_total);
		if(!$approximate_value) {
			return $value_total;	
		}
		
		$remainder = array('value' => '1', 'remainder' => '0/1');
		$counter = 0;
		
		$result = $approximate_value;		
		while(!$this->equals_zero($remainder) && $counter < 2) {
			if($counter > 0) {
				$approximate_value = $result;	
			}
			$approach = $this->multiply_total($approximate_value, $approximate_value);
			$remainder = $this->execute_divide($value_total, $approach);
			
			
			$remainder_root = $this->square_root_approximation($remainder);
			if(!$remainder_root) {
				$remainder_root = array('value' => '1', 'remainder' => '0/1');	
			}
			
			
			$result = $this->multiply_total($result, $remainder_root);
			
			$counter++;
		}
		return $result;
	}
		
	function find_continued_fraction($value, $power, $limit, $precision=NULL) {
		$root_solver = new root_solver(NULL, $power, $this);
		$result = $root_solver->solve_root($value, $limit, $precision);	
		return $result;
	}
	
	function square_root_fraction($value, $limit=30) {
		
		$sqrt = $this->root($value, 2);
		$m = $this->root_closest_result;
		
		
		$first_m = $m;
		
		$continued_fraction = array($m);
			
		
		$x_denominator_value = $this->result($m, $m);
		$x_denominator = $this->subtract($value, $x_denominator_value);	
		
		$x_numerator = $this->add($m, $m);
		
		$x_division = $this->execute_divide($x_numerator, $x_denominator);
		
		$continued_fraction[] = $x_division['value'];
		
		$counter = 0;
		$last_x_denominator = $x_denominator;
		while($counter < $limit) {
			
			$x_denominator_value = $this->subtract($first_m, $this->fraction_values($x_division['remainder'])[0]);
			$x_numerator_value = $x_denominator;
			$x_denominator_subtraction = $this->result($x_denominator_value, $x_denominator_value);
			$x_denominator = $this->subtract($value, $x_denominator_subtraction);	
			
			$fraction_value = $x_numerator_value."/".$x_denominator;
			$fraction_value = $this->execute_shorten_fraction($fraction_value, true);
			
			$fraction_values = $this->fraction_values($fraction_value);
			$x_numerator_multiplier = $fraction_values[0];
			$x_denominator = $fraction_values[1];
			$x_numerator = $this->result($x_numerator_multiplier, $x_denominator_value);
			
			$m = $x_numerator;
			
			$x_denominator_value = $this->result($m, $m);	
			
			$x_numerator = $this->add($first_m, $m);
			
			$x_division = $this->execute_divide($x_numerator, $x_denominator);
			
			$continued_fraction[] = $x_division['value'];
			
			$periodic = $this->detect_period_continued_fraction($continued_fraction);
			if($periodic !== false) {
				return $periodic;	
			}
			
			$last_x_denominator = $x_denominator;
			$counter++;
		}
		return false;
	}
	
	private function e_terms($count) {
		$n = 0;
		$result = [2, 1];
		$start = 2;
		$counter = 0;
		while($counter < $count) {
			$result[] = $start;
			$result[] = 1;
			$result[] = 1;
			$start = $this->add($start, 2);
			$counter++;	
		}
		return $result;	
	}
	
	function detect_period_continued_fraction($continued_fraction) {
		$start_value = array_shift($continued_fraction);
		$stop_value = $this->result($start_value, 2);
		
		$period_values = array();
		$stop = false;
		foreach($continued_fraction as $value) {
			if(!$stop) {
				if($value == $stop_value) {
					$stop = true;	
				} else {				
					$period_values[] = $value;	
				}
			}
		}
		if(!$stop) {
			return false;	
		}
		$first_part = array();
		$second_part = array();
		$values_count = floor(count($period_values) / 2);
		$stop_value_found = false;
		foreach($continued_fraction as $index => $value) {
			if($value == $stop_value) {
				$stop_value_found = true;	
			} else {
				if(!$stop_value_found) {
					$first_part[] = $value;	
				} else {
					$second_part[] = $value;	
				}
			}
		}
		if($first_part == $second_part) {
			array_unshift($first_part, $start_value);
			array_push($first_part, $stop_value);
			return $first_part;	
		}
		return false;
	}
	
	private $continued_fraction_resolution_level = 0;
	private $set_continued_fraction_resolution_level = 12;
	
	private $set_continued_fraction_resolution_level_setting = 12;
	
	function set_periodic_continued_fraction_precision($precision) {
		$this->set_continued_fraction_resolution_level_setting = $precision;
	}
	
	private $current_continued_fraction;
	function resolve_continued_fraction($continued_fraction, $value=NULL) {
		$this->continued_fraction_resolution_level = 0;
		if($value != NULL) {
			$this->set_continued_fraction_resolution_level = $this->set_continued_fraction_resolution_level_setting;
		} else {
			$this->set_continued_fraction_resolution_level = 1;
		}
		$this->current_continued_fraction_whole = $continued_fraction;
		$first_value = array('value' => array_shift($continued_fraction), 'remainder' => '0/1');
		$this->current_continued_fraction = $continued_fraction;
		$this->current_continued_fraction_squared_value = $value;
		
		return $this->add_total($first_value, $this->execute_divide(array('value' => 1, 'remainder' => '0/1'), $this->resolve_continued_fraction_sub($continued_fraction)));
	}
	
	private function resolve_continued_fraction_sub($continued_fraction) {
		$first_value = array('value' => array_shift($continued_fraction), 'remainder' => '0/1');
		if(count($continued_fraction) == 0) {
			$continued_fraction = $this->current_continued_fraction;
			$this->continued_fraction_resolution_level++;
			if($this->current_continued_fraction_squared_value == NULL) {
				return $first_value;
			} else if($this->continued_fraction_resolution_level == $this->set_continued_fraction_resolution_level) {
				$result = $this->add_total($first_value, $this->execute_divide(array('value' => 1, 'remainder' => '0/1'), $this->terminating_continued_fraction($this->current_continued_fraction_whole, $this->current_continued_fraction_squared_value)));	
				return $result;
			}
		}
		$result = $this->add_total($first_value, $this->execute_divide(array('value' => 1, 'remainder' => '0/1'), $this->resolve_continued_fraction_sub($continued_fraction)));
		return $result;
	}
	
	function terminating_continued_fraction_values($continued_fraction, $variable=false) {
		if($variable) {
			array_pop($continued_fraction);
			$continued_fraction[] = 1;	
		}
		$values = $continued_fraction;
		$values = array_reverse($values);
		
		$value = array_shift($values);
		$intermediate_result = "1/".$value;
		
		while(count($values) > 0) {
			$next_value = array_shift($values);
			$numerator = $this->result($value, $next_value);
			$fraction_addition = $numerator."/".$value;
			
			$intermediate_result = $this->add_fraction($intermediate_result, $fraction_addition);
			$intermediate_result = $this->flip_fraction($intermediate_result);
			$value = $next_value;
		}
		return $intermediate_result;
	}
	
	function terminating_continued_fraction($continued_fraction, $value) {
		$variable_values = $this->terminating_continued_fraction_values($continued_fraction, true); 		
		$constant_values = $this->terminating_continued_fraction_values($continued_fraction);			
		
		$variable_values = $this->fraction_values($variable_values);
		$constant_values = $this->fraction_values($constant_values);
		
		
		$a = array('value' => $variable_values[0], 'remainder' => '0/1');
		$c = array('value' => $variable_values[1], 'remainder' => '0/1');
		$b = array('value' => $constant_values[0], 'remainder' => '0/1');
		$d = array('value' => $constant_values[1], 'remainder' => '0/1');
		
		$y_approximate = $value;		
		
		$numerator = $this->multiply_total($d, $y_approximate);
		$numerator = $this->subtract_total($b, $numerator);
		$denominator = $this->multiply_total($c, $y_approximate);
		$denominator = $this->subtract_total($denominator, $a);
		
		$result = $this->execute_divide($numerator, $denominator);
		return $result;
			
	}
		
	function perform_cfa_vector($cf) {
		$counter = 0;
		$cfa_continued_fraction_result = array();
		while($counter < 15) {
			$counter2 = 0;
			do {
				while($this->cfa_need_term()) {
					if(count($cf[$this->cfn]) > 0) {
						$term = array_shift($cf[$this->cfn]);
						$this->consume_term($term);	
					} else {
						$this->consume_term();	
					}
				}
				if($this->cfa_have_term) {
					$this->cfa_have_term = false;
					$cfa_continued_fraction_result[] = $this->cfa_this_term;
				}
				$counter2++;
			} while($counter2 < 10);
			$counter++;
		}
		return $cfa_continued_fraction_result;
	}
	
	private $cfa_vector = array(
		'a1' => 0,
		'a' => 0,
		'b1' => 0,
		'b' => 0,
		't' => 0
	);
	
	private $cfa_this_term;
	private $cfa_have_term = false;
	
	private $cfa_matrix = array(
		'a12' => 0,
		'a1' => 0,
		'a2' => 0,
		'a' => 0,
		'b12' => 0,
		'b1' => 0,
		'b2' => 0,
		'b' => 0,
		't' => 0
	);
	
	private $cfn = 0;
	private $ab = array('value' => 0, 'remainder' => '0/1');
	private $a1b1 = array('value' => 0, 'remainder' => '0/1');
	private $a2b2 = array('value' => 0, 'remainder' => '0/1');
	private $a12b12 = array('value' => 0, 'remainder' => '0/1');
	
	function choose_cfn() {
		$subtraction_a = $this->absolute($this->subtract_total($this->a1b1, $this->ab));
		$subtraction_b = $this->absolute($this->subtract_total($this->a2b2, $this->ab));	
		
		if($this->larger_total($subtraction_a, $subtraction_b)) {
			return 0;	
		}
		return 1;
	}
	
	function cfa_matrix($a12, $a1, $a2, $a, $b12, $b1, $b2, $b) {
		$this->cfa_matrix['a12'] = $a12;
		$this->cfa_matrix['a1'] = $a1;
		$this->cfa_matrix['a2'] = $a2;
		$this->cfa_matrix['a'] = $a;
		$this->cfa_matrix['b12'] = $b12;
		$this->cfa_matrix['b1'] = $b1;
		$this->cfa_matrix['b2'] = $b2;
		$this->cfa_matrix['b'] = $b;
	}
	
	function cfa_need_term() {
		if($this->cfa_matrix['b1'] == 0 && $this->cfa_matrix['b'] == 0 && $this->cfa_matrix['b2'] == 0 && $this->cfa_matrix['b12'] == 0) {
			return false;	
		}
		if($this->cfa_matrix['b'] == 0) {
			if($this->cfa_matrix['b2'] == 0) {
				$this->cfn = 0;	
			} else {
				$this->cfn = 1;	
			}
			return true;
		} else {
			$this->ab = $this->execute_divide($this->cfa_matrix['a'], $this->cfa_matrix['b']);
		}
		if($this->cfa_matrix['b2'] == 0) {
			$this->cfn = 1;
			return true;	
		} else {
			$this->a2b2 = $this->execute_divide($this->cfa_matrix['a2'], $this->cfa_matrix['b2']);	
		}
		if($this->cfa_matrix['b1'] == 0) {
			$this->cfn = 0;
			return true;	
		} else {
			$this->a1b1 = $this->execute_divide($this->cfa_matrix['a1'], $this->cfa_matrix['b1']);	
		}
		if($this->cfa_matrix['b12'] == 0) {
			$this->cfn = $this->choose_cfn();
			return true;	
		} else {
			$this->a12b12 = $this->execute_divide($this->cfa_matrix['a12'], $this->cfa_matrix['b12']);	
		}
		$this->cfa_this_term = $this->ab['value'];
		if($this->cfa_this_term == $this->a1b1['value'] && $this->cfa_this_term == $this->a2b2['value'] && $this->cfa_this_term == $this->a12b12['value']) {
			$this->cfa_matrix['t'] = $this->cfa_matrix['a'];
			$this->cfa_matrix['a'] = $this->cfa_matrix['b'];
			$this->cfa_matrix['b'] = $this->subtract($this->cfa_matrix['t'], $this->result($this->cfa_matrix['b'], $this->cfa_this_term));
			$this->cfa_matrix['t'] = $this->cfa_matrix['a1'];
			$this->cfa_matrix['a1'] = $this->cfa_matrix['b1'];
			$this->cfa_matrix['b1'] = $this->subtract($this->cfa_matrix['t'], $this->result($this->cfa_matrix['b1'], $this->cfa_this_term));
			$this->cfa_matrix['t'] = $this->cfa_matrix['a2'];
			$this->cfa_matrix['a2'] = $this->cfa_matrix['b2'];
			$this->cfa_matrix['b2'] = $this->subtract($this->cfa_matrix['t'], $this->result($this->cfa_matrix['b2'], $this->cfa_this_term));
			$this->cfa_matrix['t'] = $this->cfa_matrix['a12'];
			$this->cfa_matrix['a12'] = $this->cfa_matrix['b12'];
			$this->cfa_matrix['b12'] = $this->subtract($this->cfa_matrix['t'], $this->result($this->cfa_matrix['b12'], $this->cfa_this_term));
			$this->cfa_have_term = true;
			return false;
		}
		$this->cfn = $this->choose_cfn();
		return true;
	}
	
	function consume_term($n=NULL) {
		if($n == NULL) {
			if($this->cfn == 0) {
				$this->cfa_matrix['a'] = $this->cfa_matrix['a1'];
				$this->cfa_matrix['a2'] = $this->cfa_matrix['a12'];
				$this->cfa_matrix['b'] = $this->cfa_matrix['b1'];
				$this->cfa_matrix['b2'] = $this->cfa_matrix['b12'];	
			} else {
				$this->cfa_matrix['a'] = $this->cfa_matrix['a2'];
				$this->cfa_matrix['a1'] = $this->cfa_matrix['a12'];
				$this->cfa_matrix['b'] = $this->cfa_matrix['b2'];
				$this->cfa_matrix['b1'] = $this->cfa_matrix['b12'];	
			}
		} else {
			if($this->cfn == 0) {
				$this->cfa_matrix['t'] = $this->cfa_matrix['a'];
				$this->cfa_matrix['a'] = $this->cfa_matrix['a1'];
				$this->cfa_matrix['a1'] = $this->add($this->cfa_matrix['t'], $this->result($this->cfa_matrix['a1'], $n));	
				$this->cfa_matrix['t'] = $this->cfa_matrix['a2'];
				$this->cfa_matrix['a2'] = $this->cfa_matrix['a12'];
				$this->cfa_matrix['a12'] = $this->add($this->cfa_matrix['t'], $this->result($this->cfa_matrix['a12'], $n));	
				$this->cfa_matrix['t'] = $this->cfa_matrix['b'];
				$this->cfa_matrix['b'] = $this->cfa_matrix['b1'];
				$this->cfa_matrix['b1'] = $this->add($this->cfa_matrix['t'], $this->result($this->cfa_matrix['b1'], $n));	
				$this->cfa_matrix['t'] = $this->cfa_matrix['b2'];
				$this->cfa_matrix['b2'] = $this->cfa_matrix['b12'];
				$this->cfa_matrix['b12'] = $this->add($this->cfa_matrix['t'], $this->result($this->cfa_matrix['b12'], $n));	
			} else {
				$this->cfa_matrix['t'] = $this->cfa_matrix['a'];
				$this->cfa_matrix['a'] = $this->cfa_matrix['a2'];
				$this->cfa_matrix['a2'] = $this->add($this->cfa_matrix['t'], $this->result($this->cfa_matrix['a2'], $n));	
				$this->cfa_matrix['t'] = $this->cfa_matrix['a1'];
				$this->cfa_matrix['a1'] = $this->cfa_matrix['a12'];
				$this->cfa_matrix['a12'] = $this->add($this->cfa_matrix['t'], $this->result($this->cfa_matrix['a12'], $n));	
				$this->cfa_matrix['t'] = $this->cfa_matrix['b'];
				$this->cfa_matrix['b'] = $this->cfa_matrix['b2'];
				$this->cfa_matrix['b2'] = $this->add($this->cfa_matrix['t'], $this->result($this->cfa_matrix['b2'], $n));	
				$this->cfa_matrix['t'] = $this->cfa_matrix['b1'];
				$this->cfa_matrix['b1'] = $this->cfa_matrix['b12'];
				$this->cfa_matrix['b12'] = $this->add($this->cfa_matrix['t'], $this->result($this->cfa_matrix['b12'], $n));	
			}
		}
	}
		
	function cfa_vector($a1, $a, $b1, $b) {
		$this->cfa_vector['a1'] = $a1;
		$this->cfa_vector['a'] = $a;
		$this->cfa_vector['b1'] = $b1;
		$this->cfa_vector['b'] = $b;	
	}
	
	function ingress($n) {
		$a = $this->cfa_vector['a'];
		$b = $this->cfa_vector['b'];
		
		$this->cfa_vector['a'] = $this->cfa_vector['a1'];
		$this->cfa_vector['a1'] = $this->add($a, $this->result($this->cfa_vector['a1'], $n)); 		$this->cfa_vector['b'] = $this->cfa_vector['b1'];
		$this->cfa_vector['b1'] = $this->add($b, $this->result($this->cfa_vector['b1'], $n)); 	}
	
	function need_term() {
		return ($this->cfa_vector['b'] == 0 || $this->cfa_vector['b1'] == 0) 
		|| !($this->execute_divide($this->cfa_vector['a'], $this->cfa_vector['b'])['value'] == $this->execute_divide($this->cfa_vector['a1'], $this->cfa_vector['b1'])['value']);	
	}
	
	function egress() {
		$n = $this->execute_divide($this->cfa_vector['a'], $this->cfa_vector['b'])['value'];
		
		
		$a = $this->cfa_vector['a'];
		$a1 = $this->cfa_vector['a1'];
		
		$this->cfa_vector['a'] = $this->cfa_vector['b'];
		$this->cfa_vector['b'] = $this->subtract($a, $this->result($this->cfa_vector['b'], $n));	
		$this->cfa_vector['a1'] = $this->cfa_vector['b1'];
		$this->cfa_vector['b1'] = $this->subtract($a1, $this->result($this->cfa_vector['b1'], $n));	
		return $n;
	}
	
	function egress_done() {
		if($this->need_term()) {
			$this->cfa_vector['a'] = $this->cfa_vector['a1'];
			$this->cfa_vector['b'] = $this->cfa_vector['b1'];	
		}
		return $this->egress();
	}
	
	function done() {
		return 	$this->cfa_vector['b'] == 0 && $this->cfa_vector['b1'] == 0;
	}
	
	function perform_continued_fraction_arithematic($continued_fraction) {
		$result = array();
		foreach($continued_fraction as $value) {
			if(!$this->need_term()) {
				$result[] = $this->egress();
			}
			$this->ingress($value);
		}
		$stop = false;
		while(!$stop) {
			$result[] = $this->egress_done();
			if($this->done()) {
				$stop = true;	
			}
		}
		return $result;
	}
	
	private $cfa_continued_fraction_result;
	
	
	
	function cf_add($cf_x, $cf_y) {
		return $this->cf_arithematic(0, 1, 1, 0,
									 0, 0, 0, 1,
									 $cf_x, $cf_y);	
	}
	
	function cf_subtract($cf_x, $cf_y) {
		return $this->cf_arithematic(0, 1, -1, 0,
									 0, 0, 0, 1,
									 $cf_x, $cf_y);	
	}
	
	function cf_multiply($cf_x, $cf_y) {
		return $this->cf_arithematic(1, 0, 0, 0,
									 0, 0, 0, 1,
									 $cf_x, $cf_y);	
	}
	
	function cf_divide($cf_x, $cf_y) {
		return $this->cf_arithematic(0, 1, 0, 0,
									 0, 0, 1, 0,
									 $cf_x, $cf_y);	
	}
	
	function flip_fraction($value) {
		$fraction_values = $this->fraction_values($value);
		return $fraction_values[1]."/".$fraction_values[0];	
	}
	
	function execute_power_alter_a($value) {
		$fraction_values = $this->fraction_values($value);
		$k = $fraction_values[0];
		$m = $fraction_values[1];
		$km_root = $this->root($this->result($k, $m), 2);
		if($km_root !== false) {
			return $this->execute_divide($k, $km_root);
		}
		return false;
	}
	
	function factor_root($value, $power) {
		$root_solver = new root_solver($this->whole_numerator($value), $power, $this);
		$result = $root_solver->factor_root();	
		return $result;
	}
	
	function solve_remainder_square($value, $remainder_squared) {
		$root_solver = new root_solver(NULL, NULL, $this);
		$result = $root_solver->solve_r_square($value, $remainder_squared);	
		return $result;
	}
	
	function reuse_square_root($value, $known_root) {
		$root_solver = new root_solver($this->whole_numerator($value), 2, $this);
		$result = $root_solver->solve($known_root);	
		return $result;
	}
	
	function root_by_denominator($value, $denominator_root, $power) {
		$root_solver = new root_solver($this->whole_numerator($value), $power, $this);
		$result = $root_solver->root_by_denominator($denominator_root);
		return $result;
	}
	
	function reformulate_root($fraction) {
		$power = 2;
		$value = $fraction;
		$result_values = array();
		
		$fraction_values = $this->fraction_values($value);
		$k = $fraction_values[0];		
		$m = $fraction_values[1];		
		$m_root_unaltered = $m;
		$m_root_o = $m;
		
		$k = $this->result($k, $m);
		$m = $this->result($m, $m);	
		
		$value = $this->result($k, $m);
		
		$counter = 1;
		
		$next_rational_root;
		$multiplier;
		
		$k_unaltered = array('value' => $k, 'remainder' => '0/1');
		$m_unaltered = array('value' => $m, 'remainder' => '0/1');
		$k_o = $k_unaltered;
		$m_o = $m_unaltered;
		
		$result_values["o"] = array('k' => $k_o, 'm' => $m_o, 'm_12' => array('value' => $fraction_values[1], 'remainder' => '0/1'));
		
		$k_squared_o = $this->multiply_total($k_unaltered, $k_unaltered);
		$m_squared_o = $this->multiply_total($m_unaltered, $m_unaltered);
		
		$km_o = $this->multiply_total($k_unaltered, $m_unaltered);
		
		$result_values["o"]["km"] = $km_o;
		
		$stop = false;
		while($counter <= $value && !$stop) {
			$multiplier = $counter;			$multiplied_value = $this->result($value, $multiplier);
			
			$next_rational_root = $this->next_rational_root($multiplied_value, 2, true, true);
			
			if($multiplied_value == $next_rational_root['value']) {
				$stop = true;
			}
			$counter++;
		}
		if(!$stop) {
			$next_rational_root = array(
				'value' => $this->result($value, $value),
				'root' => $value
			);	
			$stop = true;
		}
		$result_values["r"]["ra_2"] = array('value' => $multiplier, 'remainder' => '0/1');
		
			
		$m_squared = $this->execute_divide($this->result($m, $next_rational_root['value']), $k);
		
		$km = $next_rational_root['value'];
		
		$result_values["a"]["km"] = array('value' => $km, 'remainder' => '0/1');
		
		$km_primary = $km;
		$km_primary_a = $km;
		$km_root = array('value' => $next_rational_root['root'], 'remainder' => '0/1');
		
		$result_values["a"]["km_12"] = $km_root;
		$result_values["a"]["m_2"] = $m_squared;		
				
		$k_squared = $this->result($next_rational_root['value'], $next_rational_root['value']);
		$k_squared = $this->execute_divide($k_squared, $m_squared);
		$result_values["a"]["k_2"] = $k_squared;	
		
		
		$k_di_m = $this->execute_divide($k, $m);
		$k_12_di_m_m_12 = $this->execute_divide($k_di_m, $km_root);
		$result_values["a"]["k_di_m"] = $k_di_m;	
		
		
		
		$k_root_o_ra = $this->execute_divide($km_root, $m_root_o);
		return $result_values;
	}
	
	private function execute_power_alter_approximation($value, $power) {
		$fraction_values = $this->fraction_values($value);
		$k = $fraction_values[0];		
		$m = $fraction_values[1];		
		$m_root_unaltered = $m;
		$m_root_o = $m;
		
		$k = $this->result($k, $m);
		$m = $this->result($m, $m);	
		
		$value = $this->result($k, $m);
		
		$counter = 1;
		
		$next_rational_root;
		$multiplier;
		
		$k_unaltered = array('value' => $k, 'remainder' => '0/1');
		$m_unaltered = array('value' => $m, 'remainder' => '0/1');
		$k_o = $k_unaltered;
		$m_o = $m_unaltered;
		
		$k_squared_o = $this->multiply_total($k_unaltered, $k_unaltered);
		$m_squared_o = $this->multiply_total($m_unaltered, $m_unaltered);
		
		$km_o = $this->multiply_total($k_unaltered, $m_unaltered);
		
		$stop = false;
		while($counter < 2500 && !$stop) {
			$multiplier = $counter;			
			$multiplied_value = $this->result($value, $multiplier);
			
			$next_rational_root = $this->next_rational_root($multiplied_value, 2, true, true);
			
			if($multiplied_value == $next_rational_root['value']) {
				$stop = true;
			}
			$counter++;
		}
		if(!$stop) {
			$next_rational_root = array(
				'value' => $this->result($value, $value),
				'root' => $value
			);	
			$stop = true;
		}
		
		$second_multiplier = 1;
		$rational_root_found = false;
		
		$next_rational_root_unaltered = $next_rational_root;
		
		$smallest_subtraction = NULL;
		$smallest_value = NULL;
		$smallest_value_k = false;
		$smallest_value_km = NULL;
		
		while($second_multiplier <= $next_rational_root_unaltered['value'] && !$rational_root_found) {
		
			$next_rational_root['root'] = $this->result($next_rational_root_unaltered['root'], $second_multiplier);
			$next_rational_root['value'] = $this->result($next_rational_root_unaltered['value'], $this->result($second_multiplier, $second_multiplier));
			
			$m_squared = $this->execute_divide($this->result($m, $next_rational_root['value']), $k);
			
			$km = $next_rational_root['value'];
			$km_primary = $km;
			$km_primary_a = $km;
			$km_root = array('value' => $next_rational_root['root'], 'remainder' => '0/1');
					
			$k_squared = $this->result($next_rational_root['value'], $next_rational_root['value']);
			$k_squared = $this->execute_divide($k_squared, $m_squared);
			
			$k_squared_next_root = $this->next_rational_root($k_squared['value']);
			$m_squared_next_root = $this->next_rational_root($m_squared['value']);
			if($k_squared['value'] == $k_squared_next_root['value']) {
				$rational_root_found = true;	
			}
			if($m_squared['value'] == $m_squared_next_root['value']) {
				$rational_root_found = true;	
			}
			
			$subtraction = $this->subtract($k_squared['value'], $k_squared_next_root['value']);
			$subtraction = $this->absolute($subtraction);
			if($smallest_subtraction == NULL || $subtraction < $smallest_subtraction) {
				$smallest_subtraction = $subtraction;	
				$smallest_value = $k_squared_next_root;
				$smallest_value_k = true;
				$smallest_value_km = $next_rational_root;
			}
			$subtraction = $this->subtract($m_squared['value'], $m_squared_next_root['value']);
			$subtraction = $this->absolute($subtraction);
			if($smallest_subtraction == NULL || $subtraction < $smallest_subtraction) {
				$smallest_subtraction = $subtraction;	
				$smallest_value = $m_squared_next_root;
				$smallest_value_k = false;
				$smallest_value_km = $next_rational_root;
			}
			
			$second_multiplier++;
		}
		
		if($smallest_value_k) {
			return $this->execute_divide($smallest_value['root'], $smallest_value_km['root']);	
		} else {
			return $this->execute_divide($smallest_value_km['root'], $smallest_value['root']);	
		}
		
		return NULL;
	}
	
	private function execute_square_root_fraction($value) {
		$closest_rational_root = sqrt($value);
		$closest_rational_root = explode(".", $closest_rational_root)[0];
		$closest_rational_root_root = $closest_rational_root;
		$closest_rational_root = $this->result($closest_rational_root, $closest_rational_root);
		if($closest_rational_root == $value) {
			return array('value' => $closest_rational_root_root, 'remainder' => '0/1');	
		}
		
		$subtraction = $this->subtract($value, $closest_rational_root);
		$a = $subtraction;
		$b = $this->negative_value($this->result("2", $closest_rational_root_root));
		$c = -1;	
		
		
		$a_sub = 1;
		$b_sub = $this->execute_divide($b, $a);
		$c_sub = $this->execute_divide($c, $a);
		
		$b_squared = $this->execute_power_whole($b_sub, 2);
		
		$b_squared = $this->add_total($b_squared, $c_sub);
		if($this->fraction_values($b_squared['remainder'])[0] == 0) {
			$b_rational_root = $this->next_rational_root($b_squared['value']);
			if($b_rational_root['value'] == $b_squared['value']) {
				$b_rational_root_root = array('value' => $b_rational_root['root'], 'remainder' => '0/1');
				$x = $this->subtract_total($b_rational_root_root, $b_sub);
				$x = $this->add_total($x, array('value' => $closest_rational_root_root, 'remainder' => '0/1'));
				return $x;
			}
		}
		
		
		$root_value = $this->execute_power_whole($b, 2)['value'];
		$ac = $this->result($a, $c);
		$ac = $this->result(4, $ac);
		
		$root_value = $this->subtract($root_value, $ac);
		
		return $root_value;
		if($this->larger($root_value, $value)) {
			$continued_fraction_numerator = $this->square_root_fraction($value);
			$root_value = $this->resolve_continued_fraction($continued_fraction_numerator, $value);
			return $root_value;
		}
		$continued_fraction_numerator = $this->square_root_fraction($root_value);
		$root_value = $this->resolve_continued_fraction($continued_fraction_numerator, $root_value);
		
		$b = $this->whole_common($this->negative_value($b));		
		$a2 = $this->result(2, $a);
		$a2 = array('value' => $a2, 'remainder' => '0/1');
		
		$x1 = $this->add_total($b, $root_value);
		$x2 = $this->subtract_total($b, $root_value);
		
		$x1 = $this->execute_divide($x1, $a2);
		$x2 = $this->execute_divide($x2, $a2);
		
		$closest_root = array('value' => $closest_rational_root_root, 'remainder' => '0/1');
		
		$x1 = $this->add_total($closest_root, $x1);
		$x2 = $this->add_total($closest_root, $x2);
		
		$result_x1 = $this->multiply_total($x1, $x1);
		$result_x2 = $this->multiply_total($x2, $x2);
		
		$value_total = array('value' => $value, 'remainder' => '0/1');
		
		$subtraction_x1 = $this->subtract_total($value_total, $result_x1);
		$subtraction_x2 = $this->subtract_total($value_total, $result_x2);
		
		if($this->larger_total($subtraction_x1, $subtraction_x2)) {
			return $x1;	
		}
		return $x2;
		
		
	}
	
	
	private $maximum_exponent_count = 25;
	public $execute_power_approximate_flag = false;
	public $disable_built_in_approximation;
	public $disable_exact_root_results = false;
		
	function execute_power($value, $power) { 		
		if($this->disable_exact_root_results) {
			return $this->root_fraction($value, $power);
		}
		$this->execute_power_approximate_flag = false;
		
		$whole_numerator = $this->whole_numerator($value);
		$whole_values = $this->fraction_values($whole_numerator);
		$root_numerator = $this->next_rational_root($whole_values[0], $power);
		$root_denominator = $this->next_rational_root($whole_values[1], $power);
		if($root_numerator['value'] == $whole_values[0] && $root_denominator['value'] == $whole_values[1]) {
			$division = $this->execute_divide($root_numerator['root'], $root_denominator['root']);
			return $division;
		}
		if($power == 2) {
			$alter_result = $this->execute_power_alter_a($whole_numerator);
			if($alter_result !== false) {
				return $alter_result;	
			}
		}
		if($this->negative($value)) {
			return NULL;	
		}
		if($power == 1) {
			return $value;	
		}
		
		
		$unshortened_value = $value;
		
		
			
		
		$approximate_value;
		
		$division_root = $this->root($value['value'], $power);
		$base_root = $this->root_closest_result;
		
		if($base_root == "0") {
			if($this->disable_built_in_approximation || strlen($whole_values[0]) > $this->maximum_divider_exponent || strlen($whole_values[1]) > $this->maximum_divider_exponent) {
				$numerator_root = $this->root_fraction($whole_values[0], $power);
				$denominator_root = $this->root_fraction($whole_values[1], $power);
				return $this->execute_divide($numerator_root, $denominator_root);	
			} else {		
				$quick_fraction = $this->quick_numeric($value);
				$approximate_value = pow($quick_fraction, 1/$power); 					
				$approximate_value = $this->whole_common($approximate_value);
				return $approximate_value;
			}
		}
		
		$whole = $whole_numerator;		
		$counter = 0;
		$continue = true;
		
		$subtraction_value = $this->execute_power_whole(array('value' => $base_root, 'remainder' => '0/1'), $power);
		
		
		$subtraction = $this->subtract_total($value, $subtraction_value);
		$division = $this->execute_divide($value, $subtraction_value);
		
		if($counter > 0) {
			
			$continue = false;
		}
		
		if($subtraction['value'] == 0 && $this->fraction_values($subtraction['remainder'])[0] == 0) {
			return array('value' => $base_root, 'remainder' => '0/1');	
		}
		
		
		$division_whole_fraction = $this->whole_numerator($division);
		$division_whole_fraction_values = $this->fraction_values($division_whole_fraction);
		
		
		$subtraction_whole_fraction = $this->whole_numerator($subtraction);
		$subtraction_whole_fraction_values = $this->fraction_values($subtraction_whole_fraction);
		
		$ratio = $this->execute_divide($division_whole_fraction_values[1], $subtraction_whole_fraction_values[1])['value'];
		$subtraction_whole_fraction_values[0] = $this->result($subtraction_whole_fraction_values[0], $ratio);
		$subtraction_whole_fraction_values[1] = $this->result($subtraction_whole_fraction_values[1], $ratio);
		
		
		$f = $subtraction_whole_fraction_values[0];
		$b = $subtraction_whole_fraction_values[1];
		$k = $division_whole_fraction_values[0];
		$m = $division_whole_fraction_values[1];	
		$k_multiplier = $k;
		
		
		
		
		$v = $this->execute_power_whole($base_root, $power)['value'];
		$bv = $this->result($b, $v);
		$s = $this->add($bv, $f);
		$s_root = $this->root($s, $power);			
		$d = $this->root($b, $power);			
		if($d === false) {
			$d = $b;
			$multiplier = $this->execute_power_whole(array('value' => $b, 'remainder' => '0/1'), $power-1)['value'];
			$f = $this->result($f, $multiplier);
			$b = $this->result($b, $multiplier);
			$k = $this->result($k, $multiplier);
			$m = $this->result($m, $multiplier);
			$v = $this->execute_power_whole($base_root, $power)['value'];
			$bv = $this->result($b, $v);
			$s = $this->add($bv, $f);
			$s_root = $this->root($s, $power);			
		}
		$d_primary = $d;
		$s_primary = $s_root;
		
		 
		
		if($s_root !== false && $d !== false) {
			$s_division = $this->execute_divide($s_root, $d);
			$root = $s_division;
			return $root;	
		} else {
			
			
			
			$k_unaltered = $k;
			$m_unaltered = $m;
			
			$s;
			
			$k_root = $this->root($k, $power);				
			$k_root_secondary = $this->result($k, $this->result($base_root, $base_root));
			$k_root_secondary = $this->root($k_root_secondary, $power);				
			if($k_root_secondary !== false) {
				$k_root = $k_root_secondary;	
				$k_root_secondary = NULL;
			}
			if($k_root === false) {
				$k_root = $k;
				$k_multiplier = $this->execute_power_whole(array('value' => $k, 'remainder' => '0/1'), $power-1)['value'];
				$k = $this->result($k, $k_multiplier);
				$m = $this->result($m, $k_multiplier);	
			}
			if($k_root_secondary != NULL) {
				$s = $this->result($base_root, $k_root);
			} else {
				$s = $k_root_secondary;	
			}
			$d = $this->root($m, $power);				
			$s_secondary = $s;
			$d_secondary = $d;
			if($d !== false) {
				$s_division = $this->execute_divide($s, $d);
				return $s_division;	
			} else {
				$k = $k_unaltered;
				$m = $m_unaltered;
				$m_root = $m;
				$m_multiplier = $this->execute_power_whole(array('value' => $m, 'remainder' => '0/1'), $power-1)['value'];
				$k = $this->result($k, $m_multiplier);
				$m = $this->result($m, $m_multiplier);	
			}
			$d = $m_root;
			$k = $this->root($k, $power);				
			
		}
		
		$subtraction_value = array('value' => $base_root, 'remainder' => '0/1');
		
		$subtraction = $this->subtract_total($value, $subtraction_value);
		$division = $this->execute_divide($value, $subtraction_value);
		
		$division_whole_fraction = $this->whole_numerator($division);
		$division_whole_fraction_values = $this->fraction_values($division_whole_fraction);
		
		
		$subtraction_whole_fraction = $this->whole_numerator($subtraction);
		$subtraction_whole_fraction_values = $this->fraction_values($subtraction_whole_fraction);
		
		$ratio = $this->execute_divide($division_whole_fraction_values[1], $subtraction_whole_fraction_values[1])['value'];
		$subtraction_whole_fraction_values[0] = $this->result($subtraction_whole_fraction_values[0], $ratio);
		$subtraction_whole_fraction_values[1] = $this->result($subtraction_whole_fraction_values[1], $ratio);
		
		
		$f = $subtraction_whole_fraction_values[0];
		$b = $subtraction_whole_fraction_values[1];
		$k = $division_whole_fraction_values[0];
		$m = $division_whole_fraction_values[1];
		
			
		
		
		$v = $base_root;			
		$bv = $this->result($b, $v);
		$s = $this->add($bv, $f);
		$s_root = $this->root($s, $power);			
		$d = $this->root($b, $power);			
		if($d === false) {
			$d = $b;
			$multiplier = $this->execute_power_whole(array('value' => $b, 'remainder' => '0/1'), $power-1)['value'];
			$f = $this->result($f, $multiplier);
			$b = $this->result($b, $multiplier);
			$k = $this->result($k, $multiplier);
			$m = $this->result($m, $multiplier);
			$v = $base_root;				
			$bv = $this->result($b, $v);
			$s = $this->add($bv, $f);
			$s_root = $this->root($s, $power);			
		}
		$d_primary_alt = $d;
		$s_primary_alt = $s_root;
		
		
		
		
		if($s_root !== false && $d !== false) {
			$s_division = $this->execute_divide($s_root, $d);
			$root = $s_division;
			return $root;	
		} else {
			
			
			
			$k_root = $this->result($k, $base_root);
			$k_root = $this->root($k_root, $power);				
			if($k_root !== false) {
				
				$s = $k_root;
				$d = $this->root($m, $power);					
				if($d !== false) {
					$s_division = $this->execute_divide($s, $d);
					return $s_division;	
				}
			} 
		}
		if($this->disable_built_in_approximation || strlen($whole_values[0]) > $this->maximum_divider_exponent || strlen($whole_values[1]) > $this->maximum_divider_exponent) {
			$numerator_root = $this->root_fraction($whole_values[0], $power);
			$denominator_root = $this->root_fraction($whole_values[1], $power);
			return $this->execute_divide($numerator_root, $denominator_root);	
		}
		$quick_fraction = $this->quick_numeric($value);
		$approximate_value = pow($quick_fraction, 1/$power); 					
		$approximate_value = $this->whole_common($approximate_value);
		return $approximate_value;
		
		return NULL;
		$counter++;	
		$subtraction_value = array('value' => $this->result($base_root, $base_root), 'remainder' => '0/1');
		$subtraction = $this->subtract_total($value, $subtraction_value);
		$division = $this->execute_divide($value, $subtraction_value);
			
		if($subtraction['value'] == 0 && $this->fraction_values($subtraction['remainder'])[0] == 0) {
			return array('value' => $base_root, 'remainder' => '0/1');
		}
		
		$division_whole_fraction = $this->whole_numerator($division);
		$subtraction_whole_fraction = $this->whole_numerator($subtraction);
		
		$fraction_values_division = $this->fraction_values($division_whole_fraction);
		$fraction_values = $this->fraction_values($subtraction_whole_fraction);
		
		$multiplier = $fraction_values[1];
		$remainder = "0/1";
		
		$continue = false;
		
		$possible_numerator;
		$possible_denominator;
		
		$d = $fraction_values[1];
		
		$fraction_values[0] = $this->result($fraction_values[0], $fraction_values[1]);
		$fraction_values[1] = $this->result($fraction_values[1], $fraction_values[1]);
		
		$k = $this->result($base_root, $base_root);
		$k = $this->result($fraction_values[1], $k);
		$k = $this->add($fraction_values[0], $k);
		
		$m = $this->result($base_root, $base_root);
		$m = $this->result($fraction_values[1], $m);
		
		$f = $fraction_values[0];
		$b = $fraction_values[1];
		
		$n = $this->result($d, $base_root);
		$k_root = $this->root($k, 2);
		
		
		
		
		
		$s = $this->result($base_root, $base_root);
		$s = $this->result($s, $b);
		$s = $this->add($s, $f);
		
		$s_root = $this->root($s, 2);
		
		$s_k = $this->result($base_root, $k_root);
		$s_k_root = $s_k;		
		$s_d = $this->root($m, 2);
		

		
		
		
		if($k_root !== false) {
			$n = $this->subtract($k_root, $n);
			$remainder = $n."/".$d;
		} else if($s_root !== false) {
			$s_division = $this->execute_divide($s_root, $d); 			
			$remainder_values = $this->fraction_values($s_division['remainder']);
			$n = $remainder_values[0];
			
			$remainder = $n."/".$d;
		} else {
			$s_whole = $this->whole_common($s_root);
			$d_whole = $this->whole_common($d);
			$s_division = $this->execute_divide($s_whole, $d_whole);
			
			
			$dv_squared = $this->result($d, $base_root);
			$dv_squared = $this->result($dv_squared, $dv_squared);
			
			$under_root = $this->add($f, $dv_squared);
			$dv_squared = $this->result(2, $dv_squared);
			$under_root = $this->result($dv_squared, $under_root);
			$under_root = $this->root($under_root, 2);
			if($under_root !== false) {
				$n = $this->add($f, $dv_squared);
				$n = $this->subtract($n, $under_root);	
				$n = $this->root($n, 2);
				if($n !== false) {
					$remainder = $n."/".$d;
				} else {
					$continue = true;	
				}
			} else {
				
				$continue = true;
			}
		}
		
		if(false && $continue) {
			$continue = false;
			$subtraction_fraction_values = $this->fraction_values($division_whole_fraction);
			$k_alt = $subtraction_fraction_values[0];
			$m_alt = $subtraction_fraction_values[1];
			
			$m_alt_unaltered = $m_alt;
			$k_alt_unaltered = $k_alt;
			
			$k_root = $k_alt;
			
			$m_alt = $this->result($m_alt, $k_alt);
			$k_alt = $this->result($k_alt, $k_alt);
			
			$f_alt = $this->subtract($k_alt, $m_alt);
			
			$b_alt = $this->result($base_root, $base_root);
			$b_alt = $this->execute_divide($m_alt, $b_alt)['value'];
			
			$m_root = $this->root($m_alt, 2);
			
			$b_root = $this->root($b_alt, 2);
			
			$s = $this->result($base_root, $base_root);
			$s = $this->result($b_alt, $s);			$s = $this->add($s, $f);
			
			$s_root = $this->root($s, 2);
			
			$s_k = $this->result($base_root, $k_root);
			
			
			$d_alt;
			if($m_root !== false) {
				$d_alt = $this->execute_divide($m_root, $base_root);
			} else if($b_root !== false) {
				$d_alt = array('value' => $b_root, 'remainder' => '0/1');	
			}
			if(isset($d_alt)) {
				$base_root_value = array('value' => $base_root, 'remainder' => '0/1');
				$dv_alt = $this->multiply_total($d_alt, $base_root_value);
				
				$n_alt = $this->subtract_total(array('value' => $k_root, 'remainder' => '0/1'), $dv_alt);
				$remainder = $n_alt['value']."/".$d_alt['value'];
			} else {
				$m_alt = $m_alt_unaltered;
				$k_alt = $k_alt_unaltered;
				
				$m_root = $m_alt;
				
				$m_alt = $this->result($m_alt, $m_alt);
				$k_alt = $this->result($k_alt, $m_alt);
				
				
				$f_alt = $this->subtract($m_alt, $k_alt);
				
				$b_alt = $this->result($base_root, $base_root);
				$b_alt = $this->execute_divide($k_alt, $b_alt)['value'];
				
				$k_root_alt = $this->root($k_alt, 2);
				$k_root	= $k_root_alt;
				$b_root = $this->root($b_alt, 2);	
				unset($d_alt);
				if($m_root !== false) {
					$d_alt = $this->execute_divide($m_root, $base_root);
					$d_alt = $d_alt['value'];
					$n_alt = $this->subtract($k_root, $m_root);
					$remainder = $n_alt."/".$d_alt;
				} else if($b_root !== false) {
					$d_alt = $b_root;	
				} else {
					$d_alt = $d;	
				}
				if(isset($d_alt) && !isset($n_alt) && strpos($k_root, ".") === false) {
					$dv_alt = $this->result($d_alt, $base_root);
					
					
					$n_alt = $this->subtract($k_root, $dv_alt);
					$remainder = $n_alt."/".$d_alt;
				} else {
					$continue = true;	
				}
			} 		}
		
		if($continue) {
			$continue = false;
			
			
			
			
			
			$whole_value = $this->whole_numerator($value);
			$whole_value = $this->fraction_values($whole_value);
			
			
			$numerator_root = $this->square_root($whole_value[0]);
			$denominator_root = $this->square_root($whole_value[1]);
			
			$root_value;
			if($numerator_root !== false) {
				$root_value = $whole_value[1];
			} else if($denominator_root !== false) {
				$root_value = $whole_value[0];
			} else {
				$whole_value[0] = $this->result($whole_value[0], $whole_value[1]);
				$root_value = $whole_value[0];
			}
			$resolution_value;
			$continued_fraction_numerator = $this->square_root_fraction($root_value);
				if($this->disable_built_in_approximation || strlen($whole[0]) > $this->maximum_divider_exponent || strlen($whole[1]) > $this->maximum_divider_exponent) {
					$approximate_value = $this->root_fraction($root_value, $power);
				} else {
					$approximate_value = sqrt($root_value);
				}
				$approximate_value = $this->whole_common($approximate_value);
				$resolution_value = $approximate_value;			
			if($numerator_root !== false) {
				$result = $this->execute_divide($numerator_root, $resolution_value);
			} else if($denominator_root !== false) {
				$result = $this->execute_divide($resolution_value, $denominator_root);
			} else {
				$result = $this->execute_divide($resolution_value, $whole_value[1]);
			}
			return $result;
			
			
			
		}
		$root = array('value' => $base_root, 'remainder' => $remainder);
		
		return $root;
	}
	
	function next_decimal_value($value) {
		$digits = $this->get_digits($value);
		while($digits[0] != 0) {
			$value = $this->add($value, "1");	
			$digits = $this->get_digits($value);
		}
		return $value;
	}
	
	function first_digit_position($value) {
		$digits = str_split($value);
		$counter = 0;
		foreach($digits as $index => $digit) {
			if($digit != 0 && $digit != ".") {
				return $counter;
			}
			if($digit != ".") {
				$counter++;
			}
		}
		return $counter;
	}
	
	private function quadratic($a, $b, $c) {
		$under_root = $this->result($b, $b);
		$subtraction = $this->result_multiple(array(4, $a, $c));		
		$under_root = $this->subtract($under_root, $subtraction);
		$square = sqrt($under_root);
		if(strpos($square, ".") !== false) {
			return NULL;
		}
		$value = $this->negative_value($b);
		$first_value = $this->add($value, $square);
		$second_value = $this->subtract($value, $square);
				
		$divider = $this->result(2, $a);
		
		$first_value = $this->execute_divide($first_value, $divider);
		$second_value = $this->execute_divide($second_value, $divider);
		return array(
			$first_value,
			$second_value
		);
	}
	
	function root_search($value, $base_root, $approximate_value, $power) {
		$variance = 0.00000000000001;
		$approximate_value = $this->numeric_whole($approximate_value['value'], $approximate_value['remainder']);
		$upper_bound = $approximate_value+$variance;
		$lower_bound = $approximate_value-$variance;
		if($power == 2) {
			$base_squared = $this->result($base_root, $base_root);
			$search = new search(array(
				'base_root' => $base_root,
				'base_squared' => $base_squared,
				'value' => $value,
				'upper_bound' => $upper_bound,
				'lower_bound' => $lower_bound,
				'approximate_value' => $approximate_value,
				'a' => 1,
				'b' => 1,
			), array(
				'a' => 1,
				'b' => 1
			), function($v) {
				
				$current_point = array('value' => $v['base_root'], 'remainder' => $v['a']."/".$v['b']);
				$current_point = $this->numeric_whole($current_point['value'], $current_point['remainder']);
				if($current_point > $v['upper_bound'] || $current_point < $v['lower_bound']) {
					return false;
				}
			}, function(&$v) { 				
				$subtraction = $this->subtract_total($v['value'], array('value' => $v['base_squared'], 'remainder' => '0/1'));
				$subtraction_fraction = $this->fraction_values($subtraction['remainder']);
				
				$numerator =  $this->add($this->result($subtraction['value'], $subtraction_fraction[1]), $subtraction_fraction[0]);
				$denominator = $subtraction_fraction[1];
				$subtraction = $numerator."/".$denominator;
				
				$ab = $v['a']."/".$v['b'];
				$ba = $v['b']."/".$v['a'];				
				$remainder_mult = $this->multiply_fraction($subtraction, $ba);
				$remainder_mult = $this->subtract_fraction($remainder_mult, $ab);
				$fraction_values = $this->fraction_values($remainder_mult);
				$fraction_values[1] = $this->result($fraction_values[1], 2);
				$division = $this->execute_divide($fraction_values[0], $fraction_values[1]);
				$division_fraction = $this->fraction_values($division['remainder']);
				if($division['value'] == $v['base_root'] && $division_fraction[0] == 0) {
					return true;	
				}
				return false;
			}, function($v) {
				$root_value = array('value' => $v['base_root'], 'remainder' => $v['a']."/".$v['b']);
				$root_squared = $this->multiply_total($root_value, $root_value);
				$subtraction = $this->subtract_total($v['value'], $root_squared);
				$real_value = $this->numeric_whole($subtraction['value'], $subtraction['remainder']);
				return $real_value;
			});
			$result = $search->get_result();	
			$root = array('value' => $base_root, 'remainder' => $result['variables']['a']."/".$result['variables']['b']);
			return $root;
		} else {
			$search = new search(array(
				'base_root' => $base_root,
				'value' => $value,
				'upper_bound' => $upper_bound,
				'lower_bound' => $lower_bound,
				'approximate_value' => $approximate_value,
				'power' => $power,
				'a' => 1,
				'b' => 1,
			), array(
				'a' => 1,
				'b' => 1
			), function($v) {
				
				$current_point = array('value' => $v['base_root'], 'remainder' => $v['a']."/".$v['b']);
				$current_point = $this->numeric_whole($current_point['value'], $current_point['remainder']);
				if($current_point > $v['upper_bound'] || $current_point < $v['lower_bound']) {
					return false;
				}
			}, function(&$v) { 				
				$root = array('value' => $v['base_root'], 'remainder' => $v['a']."/".$v['b']);
				$multiplication = $this->execute_power_whole($root, $v['power']);
				$multiplication['remainder'] = $this->execute_shorten_fraction($multiplication['remainder']);
				if($multiplication == $v['value']) {
					return true;	
				}
				return false;
			}, function($v) {
				$root_value = array('value' => $v['base_root'], 'remainder' => $v['a']."/".$v['b']);
				$multiplication = $this->execute_power_whole($root_value, $v['power']);
				$subtraction = $this->subtract_total($v['value'], $multiplication);
				$real_value = $this->numeric_whole($subtraction['value'], $subtraction['remainder']);
				return $real_value;
			});
			$result = $search->get_result();	
			$root = array('value' => $base_root, 'remainder' => $result['variables']['a']."/".$result['variables']['b']);	
			return $root;
		}
	}
	
	
		
	
	
	
	public function equalize_division($value, $divider) {
		$fraction = $value."/".$divider;
		$fraction = $this->execute_shorten_fraction($fraction);
		$values = $this->fraction_values($fraction);
		$value = $values[0];
		$divider = $values[1];
		
		$divider = $this->remove_leading_zeros($divider, true);
		$decimal_drop = $this->remove_zero_count;
		$multiplier = 1;
		$addition = 0;
		if(!$this->prime($divider)) {
			$counter = 9;
			while($counter > 0 && !$this->divisible($divider, $counter)) {
				$counter--;	
			}
			$multiplier = $counter;
			$divider = $this->execute_divide($divider, $multiplier);
			
		} else {
			$divider = $divider - 1;
			$addition = 1;
			$result = $this->equalize_division($value, $divider);
		}
		return array(
			'value' => $value,
			'divider' => $divider
		);
	}
	
	public function alter_divider($value, $divider) {
		$values = array();
		$value = $value/2;
		$values[] = $value;
		$values[] = $value;
		
		$flipped_division_part = array();
		$results = array();
		$return_results = array();
		$total = "";
		foreach($values as $key => $value) {
			$flipped_division = $divider."/".$value."<br>";	
			$flipped_division_parts[] = $flipped_division;
			$result = $divider / $value; 			
			$results[] = $result;
			$flipped = 1 / $result;
			$return_results[] = $flipped;
			$total += $flipped;
		}
		return $total;
	}
	
	private $maximum_divider_exponent = 250;
	
	
	
	
	
	function equals_infinity($value) {
		if($value === "INF") {
			return true;
		}
		if(is_array($value) && $value['value'] === "INF") {
			return true;	
		}
		return false;
	}
	
	public function execute_divide($value, $divider, $shorten=false, $fast=false, $numeric=false, $pre_shorten=false, $absolute=false) {
		$negative = false;
		if(is_array($divider) && !is_array($value)) {
			$value = array('value' => $value, 'remainder' => '0/1');	
		}
		
		if($this->equals_zero($value)) {
			return array('value' => '0', 'remainder' => '0/1');	
		}
		if($this->equals_zero($divider)) {
			return NULL;
		}
		if((($this->negative($value) && !$this->negative($divider)) || (!$this->negative($value) && $this->negative($divider))) && !$absolute) {
			$negative = true;	
		}
		
		if($value == $divider) {
			return array('value' => '1', 'remainder' => '0/1');	
		}
		
		$value = $this->absolute($value);
		$divider = $this->absolute($divider);
		
		
		
		if(!is_array($value) && !is_array($divider)) {
			if($this->larger($divider, $value, false)) {
				$result = array('value' => '0', 'remainder' => $value."/".$divider);	
				if($negative) {
					return $this->negative_value($result);	
				}
				return $result;
			}
			if(strlen($value) == strlen($divider)) {
				if($this->larger($divider, $value, false)) {
					$result = array('value' => '0', 'remainder' => $value."/".$divider);	
					if($negative) {
						return $this->negative_value($result);	
					}
					return $result;	
				} else {
					$counter = -1;
					$last_subtraction;
					$subtraction = $value;
					while(!isset($subtraction) || $this->larger($subtraction, 0)) {
						if(isset($subtraction)) {
							$last_subtraction = $subtraction;
						}
						$subtraction = $this->subtract($subtraction, $divider);
						$counter++;	
					}
					$result = array('value' => $counter, 'remainder' => $last_subtraction."/".$divider);	
					if($negative) {
						return $this->negative_value($result);	
					}
					return $result;
				}
			}
		}
		if(is_array($value) && is_array($divider) && $value['value'] == 0) {
			$divider_fraction = $this->whole_numerator($divider);
			$divider_fraction = $this->flip_fraction($divider_fraction);
			
			
			$mult = $this->multiply_fraction($value['remainder'], $divider_fraction);
			
			
			$fraction_values = $this->fraction_values($mult);
			
			$fraction_values[0] = $this->absolute($fraction_values[0]);
			
			$result = $this->execute_divide($fraction_values[0], $fraction_values[1], false, false);	
			if($negative) {
				return $this->negative_value($result);	
			}
			return $result;
		}
		
				
		if(is_array($divider) && $divider['value'] == 0) {
			$value_fraction = "";
			if(is_array($value)) {
				$value_fraction_values = $this->fraction_values($value['remainder']);
				$numerator;
				$denominator;
				if($value_fraction_values[0] != 0) {
					$numerator = $this->add($this->result($value['value'], $value_fraction_values[1]), $value_fraction_values[0]);
					$denominator = $value_fraction_values[1];	
				} else {
					$numerator = $value['value'];
					$denominator = 1;	
				}
				$value_fraction = $numerator."/".$denominator;
			} else {
				$value_fraction = $value."/1";	
			}
			$fraction_division = $this->divide_fraction($value_fraction, $divider['remainder']);
			$division_values = $this->fraction_values($fraction_division);
			return $this->execute_divide($division_values[0], $division_values[1], false, false);
		}
		if(is_array($divider) && $this->fraction_values($divider['remainder'])[0] != 0) {
			$fraction_values = $this->fraction_values($divider['remainder']);
			$subtraction_multiplier = $fraction_values[0];
			$value_multiplier = $fraction_values[1];
			$value_addition = $this->add($subtraction_multiplier, $this->result($divider['value'], $value_multiplier));
			$subtraction_multiplier = array(
				'value' => $subtraction_multiplier,
				'remainder' => '0/1'
			);
			
			$numerator = $this->multiply_total($subtraction_multiplier, $value);
			$subtraction = $this->execute_divide($numerator, $value_addition, false, false);			
			if(!is_array($value)) {
				$value = array(
					'value' => $value,
					'remainder' => '0/1'
				);	
			}
			$value = $this->subtract_total($value, $subtraction);
			
			$divider = $divider['value'];
		} else if(is_array($divider) && $this->fraction_values($divider['remainder'])[0] == 0) {
			$divider = $divider['value'];	
		}
		$fraction_set;
		if(is_array($value)) {
			$fraction_set = $value['remainder'];
			$value = $value['value'];
			$fraction_values = $this->fraction_values($fraction_set);
			if(!is_array($divider)) {
				$fraction_values[1] = $this->result($fraction_values[1],  $divider);
			}
			$fraction_set = $fraction_values[0]."/".$fraction_values[1];	
		}
		
		
		
		
		$digits = str_split($value);
		$divide_value = "";
		$result = array('value' => '0', 'remainder' => '0/1');
		
		if($fast) {
			
			$result = $this->division->fast_floor_divide($value, $divider);
		} else {
			foreach($digits as $key => $digit) {
				$divide_value = $this->pad_zeros($digit, count($digits)-$key-1);					
				if($divide_value != 0) {
					$division = $this->divide($divide_value, $divider); 						
					$result = $this->add_total($result, $division);
				}
					
			}
			$result = $result['value'];
		}
		
		
		$multiplication = $this->result($result, $divider);
		$remainder_result = $this->subtract($value, $multiplication);
		$divide_value = $remainder_result;
		
		
		
		$remainder = "0/1";
		$remainder_numeric = 0;
		if($divide_value != "0" || isset($fraction_set)) {
			$remainder = $divide_value."/".$divider;
			if(isset($fraction_set)) {
				$remainder = $this->add_fraction($remainder, $fraction_set);
			}
		}
		$remainder_values = $this->fraction_values($remainder);
		if($this->larger($remainder_values[0], $remainder_values[1])) {
			
			$sub_division = $this->execute_divide($remainder_values[0], $remainder_values[1], false, false);
			$result = $this->add($result, $sub_division['value']);
			$remainder = $sub_division['remainder'];
		}
		$fraction_values = $this->fraction_values($remainder);
		if($fraction_values[0] == $fraction_values[1]) {
			$result = $this->add($result, 1);
			$remainder = "0/1";	
		}
		
		if($result == "") {
			$result = "0";	
		}
		if($shorten) {
			$remainder = $this->execute_shorten_fraction($remainder);
		} else {
			$remainder = $this->minimize_fraction($remainder);	
		}
		
		
		if(!$numeric) {
			$result = array(
				'value' => $result,
				'remainder' => $remainder
			);
			if($negative) {
				$result = $this->negative_value($result);	
			}
			$result = $this->clean_remainder($result);
			return $result;	
		}
	}
	
	function execute_divide_sub($value, $divider) {
		$result = array('value' => '0', 'remainder' => '0/1');
		
		$digits = str_split($value);
		
		foreach($digits as $key => $digit) {
			$divide_value = $this->pad_zeros($digit, count($digits)-$key-1);				
			if($divide_value != 0) {
				$division = $this->divide($divide_value, $divider); 					
				$result = $this->add_total($result, $division);
			}
				
		}
		$result = $result['value'];
		
		
		
		$multiplication = $this->result($result, $divider);
		$remainder_result = $this->subtract($value, $multiplication);
		$divide_value = $remainder_result;
		
		
		
		$remainder = "0/1";
		$remainder_numeric = 0;
		if($divide_value != "0" || isset($fraction_set)) {
			$remainder = $divide_value."/".$divider;
			if(isset($fraction_set)) {
				$remainder = $this->add_fraction($remainder, $fraction_set);
			}
		}
		$remainder_values = $this->fraction_values($remainder);
		if($this->larger($remainder_values[0], $remainder_values[1])) {
			
			$sub_division = $this->execute_divide_sub($remainder_values[0], $remainder_values[1]);
			$result = $this->add($result, $sub_division);
		}
		return $result;	
	}
	
	function floor($value) {
		if($this->negative($value)) {
			return $this->negative_value($this->ceil($this->absolute($value)));	
		}
		if(is_array($value)) {
			return $value['value'];	
		}
		if(strpos($value, ".") !== false) {
			$split = explode(".", $value);
			return $split[0];	
		}
		return $value;
	}
	
	private function execute_sub_divide($value, $divider) {
		$division;
		$division = $this->sub_divide($value, $divider);
		return $division;
	}
	
	private function divide($value, $divider, $sub_divide_value=NULL) {
		
		if($value == 0) {
			return array('value' => 0, 'remainder' => '0/1');	
		}
		if($this->larger($divider, $value)) {
			return array('value' => '0', 'remainder' => $value."/".$divider);	
		}
		
		$division = $this->execute_sub_divide($value, $divider);
		
		
		
		
		
		
		$whole_division = $this->common($division);
		$fraction_values = $this->fraction_values($whole_division);
		
		$division = "0.".$fraction_values[0];
		$division = 1/$division;
		$division = $this->floor($division);
		$decimal_point = strlen($fraction_values[0])-strlen($fraction_values[1])+1;
		
		$division = $this->place_decimal_alt($division, $decimal_point, true, true);
		$min_division = $this->floor($division);
		
		
		
		return array('value' => $min_division, 'remainder' => '0/1');
		
		$result = $this->result($divider, $min_division);
		$remainder = $this->subtract($value, $result);
		
		
		if($this->larger($remainder, $divider)) {
			$counter = 1;
			$last_multiplication;
			$multiplication = $this->result($divider, $counter);
			while($this->larger($remainder, $multiplication, false)) {
				$last_multiplication = $multiplication;
				$counter++;	
				$multiplication = $this->result($divider, $counter);
			}
			$counter -= 1;
			$subtraction = $this->subtract($remainder, $last_multiplication);
			$min_division = $this->add($min_division, $counter);
			$remainder = $subtraction;
			
		}
		
		return array(
			'value' => $min_division,
			'remainder' => $remainder."/".$divider
		);
	}
		
	function rational($value) {
		$fraction = $this->fraction_values($value);
		
		$value = $this->execute_divide($fraction[0], $fraction[1]);
		
		$max_length = strlen($fraction[1]);
		
		$real_fraction_value = $this->quick_numeric($value, $max_length+15);
		$real_fraction_value = $this->remove_leading_zeros($real_fraction_value, true);
		
		$real_fraction_value = substr($real_fraction_value, 2);
		
		if(strlen($real_fraction_value) == $max_length+15 && !$this->all_digits_same($real_fraction_value)) {
			return false;	
		}
		return true;
	}
		
	
	public function normalize_divider_alt($value, $divider) {
		$unaltered_divider = $divider;
		$fraction = $value."/".$divider;
		while(!$this->rational($fraction) && $divider > 0 || $divider == $unaltered_divider) {
			$divider--;
			$fraction = $value."/".$divider;
		}
		$divider_translation = $unaltered_divider - $divider;
				
		$subtraction_denominator = $divider*$unaltered_divider;
		$subtraction_numerator = $divider_translation*$value;
		$subtraction_fraction = $value."/".$subtraction_denominator;
		$subtraction_fraction = $this->execute_shorten_fraction($subtraction_fraction);
		$result = $fraction." - (".$subtraction_fraction.")";			
		return $result;
	}
	
	function shorten_normalized_subtraction($subtraction_fraction, $divider=NULL) {
		$fraction_values = $this->fraction_values($subtraction_fraction);
		$binary_numerator = $this->change_base($fraction_values[0], 2);
		$binary_denominator = $this->change_base($fraction_values[1], 2);
		
		$binary_numerator_digits = $this->get_digits($binary_numerator);
		$binary_denominator_digits = $this->get_digits($binary_denominator);	
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
		
		
		$binary_numerator = $this->change_base($binary_numerator, 10, 2);
		$binary_denominator = $this->change_base($binary_denominator, 10, 2);
		
		if($divider != NULL && $binary_denominator == $divider) {
			return $binary_numerator."/".$binary_denominator;
		}
		if($divider != NULL) {
			$binary_numerator_division = $this->execute_divide($binary_numerator, $divider);	
			$binary_denominator_division = $this->execute_divide($binary_denominator, $divider);
			if($this->fraction_values($binary_numerator_division['remainder'])[0] == 0 && $this->fraction_values($binary_denominator_division['remainder'])[0] == 0) {
				$binary_numerator = $binary_numerator_division['value'];	
				$binary_denominator = $binary_denominator_division['value'];
			}
		}
		
		return $binary_numerator."/".$binary_denominator;
	}
	
	function is_binary_power($value, $change_base=true) {
		$binary_value = $value;
		if($change_base) {
			$binary_value = $this->change_base($value, 2);
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
	
	function binary_multiplication($value, $multiplier) {
		$multiplier_digits = $this->get_digits($multiplier);
		$additions = array();
		$prefix = "";
		foreach($multiplier_digits as $key => $digit) {
			if($digit == 1) {
				$additions[] = $value.$prefix;	
			}
			$prefix .= "0";
		}
		$total = "0";
		foreach($additions as $addition) {
			$total = $this->binary_addition($total, $addition);	
		}
		return $total;
	}
	
	private function binary_multiplication_fast($value, $multiplier) {
		$result = $this->karatsuba->karatsuba($value, $multiplier);	
		return $result;
	}
	

	function binary_addition($value, $addition) {
		$result = "";
		$value_digits = $this->get_digits($value);
		$addition_digits = $this->get_digits($addition);
		
		$value = $value_digits;
		$adder = $addition_digits;
		if(count($addition_digits) > count($value_digits)) {
			$value = $addition_digits;
			$adder = $value_digits;	
		}
		$carry_bit = "0";
		foreach($value as $key => $digit) {
			$value_add = "0";
			if(isset($adder[$key])) {
				if($digit == "1" && $adder[$key] == "0") {
					if($carry_bit == "0") {
						$value_add = "1";
					} else {
						$carry_bit = "1";
						$value_add = "0";	
					}
				} else if($digit == "1" && $adder[$key] == "1") {
					if($carry_bit == "0") {
						$value_add = "0";
						$carry_bit = "1";
					} else {
						$value_add = "1";
						$carry_bit = "1";
					}	
				} else if($digit == "0" && $adder[$key] == "1") {
					if($carry_bit == "1") {
						$value_add = "0";
						$carry_bit = "1";	
					} else {
						$value_add = "1";	
					}
				} else {
					if($carry_bit == "1") {
						$value_add = "1";	
						$carry_bit = "0";
					} else {
						$value_add = "0";
					}
				}
			} else {
				if($carry_bit == "1" && $digit == "1") {
					$value_add = "0";
					$carry_bit == "1";
				} else if($digit == "0" && $carry_bit == "1") {
					$value_add = "1";
					$carry_bit = "0";	
				} else {
					$value_add = $digit;
				}
			}
			$result .= $value_add;
		}
		if($carry_bit == "1") {
			$result .= $carry_bit;	
		}
		return strrev($result);
	}
	
	function binary_subtraction($value, $subtraction) {
		$value_digits = $this->get_digits($value);
		$addition_digits = $this->get_digits($subtraction);
		$negative = false;
		$value = $value_digits;
		$adder = $addition_digits;
		$carry_bit = "0";
		foreach($value as $key => $digit) {
			if(isset($adder[$key])) {
				if($adder[$key] == 1 && $digit == 1) {
					$value[$key] = "0";	
				} else if($adder[$key] == 1 && $digit == 0) {
					$value[$key] = "-1";	
				}
			}
		}
		$value = $this->invert_negatives(array_reverse($value));
		$result = implode("", $value);
		return $result;
	}
	
	function binary_subtraction_alt($value, $subtraction) {
			
	}
	
	function invert_negatives($value) {
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
		return $value;
	}
	
	private $normalize_divider_depth = 0;
	public function normalize_divider($value, $divider, $return_as_array=false) {
		$unaltered_divider = $divider;
		$fraction = $value."/".$divider;
		while(!$this->is_binary_power($divider)) {
			$divider = $this->subtract($divider, 1);
			$fraction = $value."/".$divider;
		}
		
		$divider_translation = $this->subtract($unaltered_divider, $divider);
				
		$subtraction_denominator = $this->result($divider, $unaltered_divider);
		$subtraction_numerator = $this->result($divider_translation, $value);
		$subtraction_fraction = $subtraction_numerator."/".$subtraction_denominator;
		$subtraction_fraction = $this->shorten_normalized_subtraction($subtraction_fraction, $unaltered_divider);
		$subtraction_fraction_values = $this->fraction_values($subtraction_fraction);
		$divider_value = $this->execute_divide($subtraction_fraction_values[1], $divider);
		$subtraction_fraction_denominator = $subtraction_fraction_values[1];
		
		$result = $fraction." - (".$subtraction_fraction.")";	
		
				
		if($return_as_array) {
			return array(
				'fraction' => $fraction,
				'subtraction_fraction' => $subtraction_fraction
			);	
		}
		return $result;
	}
		
	private $max_divider_length = 2;	
	private function split_divider($value, $divider) {
		$result;
		if(strlen($divider) > $this->max_divider_length) {
			$digits = $this->get_digits($value);
			$value_length = strlen($value);
			$values = array();
			if($value_length > $this->max_divider_length) {
				$split_length = ($value_length / $this->max_divider_length);
				$split_length = floor($value_length / $split_length);
				$counter = 0;
				$split_counter = 0;
				$value_add = "";
				$start_counter = 0;
				while($counter < $value_length) {
					$value_add = $digits[$counter].$value_add;
					if($split_counter == $split_length || $counter == ($value_length-1)) {
						$values[] = array(
							'value' => $value_add,
							'exponent' => $start_counter
						);
						$value_add = "";
						$split_counter = -1;
						$start_counter = $counter+1;	
					}
					$split_counter++;
					$counter++;	
				}
			}
			
		} else {
		}
		return $result;
	}
	
	
	private function divide_wrap($value, $divider) {
		$total_result;
		$total_fraction = "0/1";
		$digits = $this->get_digits($value);
		foreach($digits as $exponent => $digit) {
			if($digit != 0) {
				$value = $this->add_zeros($digit, $exponent);
				$result = $this->execute_divide($value, $divider);
				if(!isset($total_result)) {
					$total_result = $result['value'];
					$total_fraction = $result['remainder'];	
				} else {
					$total_result = $this->add($total_result, $result['value']);	
					if($result['remainder'] != "0/1") {
						$total_fraction = $this->add_fraction($total_fraction, $result['remainder']);
					}
				}
			}
		}
		$fraction = $this->fraction_values($total_fraction);
		if($this->larger($fraction[0], $fraction[1])) {
			$division = $this->divide_wrap($fraction[0], $fraction[1]);
			$total_result = $this->add($total_result, $division['value']);
			$total_fraction = $division['remainder'];
		}
		return array(
			'value' => $total_result,
			'remainder' => $total_fraction
		);
	}
	
	public function items_exist($array, $values) {
		$all_found = true;
		foreach($values as $value) {
			$found = false;
			foreach($array as $array_item) {
				if($array_item == $value) {
					$found = true;	
				}
			}
			if(!$found) {
				$all_found = false;	
			}
		}
		return $all_found;
	}
	
	public function tabs($space=0) {
		$spaces = "";
		$counter = 0;
		while($counter < $space) {
			$spaces .= "\t";	
			$counter++;
		}
		return $spaces;	
	}
	
	public function print_arr($arr, $space=0) {
		$spaces = "";
		$counter = 0;
		while($counter < $space) {
			$spaces .= "\t";	
			$counter++;
		}
		foreach($arr as $item) {
			if(is_array($item)) {
				$this->print_arr($item, $space+1);	
			} else {
			}
		}
	}
		
	public function integer_fraction($value) {
		$fraction = $this->fraction_values($value);
		if($fraction[0] > $fraction[1]) {
			$division = $this->execute_divide($fraction[0], $fraction[1]);
			$integer = $division['value'];
			$whole_numerator = $this->result($integer, $fraction[1]);			
			$remaining_numerator = $this->subtract($fraction[0], $whole_numerator);			
			return array(
				$integer,
				$remaining_numerator."/".$fraction[1]
			);	
		} else {
			return array(
				0,
				$value
			);	
		}
	}
	
	function compact_fraction($value) {
		$fraction_values = $this->fraction_values($value);
		$length = strlen($fraction_values[0]);
		if($this->decimal_mult($fraction_values[1])) {
			$decimal_length = strlen($fraction_values[1]);
			if($this->larger($decimal_length, $length)) {
				$cutoff = substr($fraction_values[1], 0, $length);	
			}
		}
	}
	
	public function real_fraction($value, $decimal_points=10, $level=0) {
		$negative = "";
		if($this->negative($value)) {
			$negative = "-";	
		}
		$value = $this->absolute($value);
		$fraction_values = $this->fraction_values($value);
		if($fraction_values[0] != "0") {
			$whole = $this->integer_fraction($value);
			$result = substr($this->calculate_real_fraction($whole[1], $decimal_points), 0, $decimal_points);
			
			return $negative.$this->numeric_whole($whole[0], $this->place_decimal_alt($result, $decimal_points));
		}
		return "0";
	}
	
	function quick_numeric($value, $decimal_places=10) {
		return 	$this->numeric_whole($value['value'], $value['remainder'], $decimal_places);
	}
	
	function numeric_whole($value, $fraction, $decimal_places=10) {
		$negative = false;
		if(strpos($value, "-") !== false) {
			$negative = true;	
		}
		if(strpos($fraction, "-") !== false) {
			$negative = true;	
		}
		$value = $this->absolute($value);
		$fraction = $this->absolute($fraction);
		if(strpos($fraction, "/") !== false) {
			$fraction = $this->real_fraction($fraction, $decimal_places);				
			$fraction = $this->absolute($fraction);
		}
		
		if(strpos($fraction, ".") === false) {
			$result = $value;	
		} else {
			$fraction_value = explode(".", $fraction);
			$addition = $this->add($this->absolute($value), $fraction_value[0]);
			$result = $addition.".".$fraction_value[1];
		}
		if($negative) {
			$result = "-".$result;	
		}
		return $result;	
	}
	
	function calculate_real_fraction($value, $decimal_points) {
		if($decimal_points <= 0) {
			return "";	
		}
		$fraction_values = $this->fraction_values($value);
		
		$division = $this->execute_divide($this->add_zeros("1", strlen($fraction_values[1])), $fraction_values[1]);
		$numerator = $this->multiply_total(array('value' => $fraction_values[0], 'remainder' => '0/1'), $division);
		$denominator = $this->multiply_total(array('value' => $fraction_values[1], 'remainder' => '0/1'), $division);
		$result = $numerator['value'];
		$result = $this->pad_zeros($result, strlen($fraction_values[1])-strlen($result), true);
		return $result.$this->calculate_real_fraction($numerator['remainder'], $decimal_points-strlen($result));
	}
	
	
	function divide_fraction($value_a, $value_b, $shorten=false) {
		$fraction_a = $this->fraction_values($value_a);
		$fraction_b = $this->fraction_values($value_b);
		
		$result = $this->result($fraction_a[0], $fraction_b[1])."/".$this->result($fraction_a[1], $fraction_b[0]);
		if($shorten) {
			$this->execute_shorten_fraction($shorten);	
		}
		return $result;	
	}
	
	public function subtract_fraction($value_a, $value_b) {
		
		$common = $this->common_denominator($value_a, $value_b);
		$fraction_a = $this->fraction_values($common[0]);
		$fraction_b = $this->fraction_values($common[1]);
		$numerator = $this->subtract($fraction_a[0], $fraction_b[0]);		
		$denominator = $fraction_a[1];
		return $numerator."/".$denominator;
	}
	
	function subtract_total($value_a, $value_b, $shorten=false) {
		$result;
		if($this->negative($value_a) && $this->negative($value_b)) {				
			$result = $this->subtract_total_sub($this->absolute($value_b), $this->absolute($value_a));
		} else if($this->negative($value_a) && !$this->negative($value_b)) {				
			$result = $this->negative_value($this->add_total_sub($this->absolute($value_b), $this->absolute($value_a)));
		} else if(!$this->negative($value_a) && $this->negative($value_b)) { 			
			$result = $this->add_total_sub($this->absolute($value_a), $this->absolute($value_b));
		} else {
			$result = $this->subtract_total_sub($value_a, $value_b);	
		}
		
		if($shorten) {
			$result['remainder'] = $this->execute_shorten_fraction($result['remainder']);	
		}
		$result = $this->clean_remainder($result);
		return $result;	
	}
	
	private function subtract_total_sub($value_a, $value_b) {
		$fraction_a = $this->fraction_values($value_a['remainder']);
		$fraction_b = $this->fraction_values($value_b['remainder']);
		$fraction_a[0] = $this->add($fraction_a[0], $this->result($fraction_a[1], $value_a['value']));
		$fraction_b[0] = $this->add($fraction_b[0], $this->result($fraction_b[1], $value_b['value']));
		$result = $this->subtract_fraction($this->make_fraction($fraction_a), $this->make_fraction($fraction_b));
		$fraction = $this->fraction_values($result);
		$negative = false;
		if($this->negative($fraction[0])) {
			$negative = true;	
		}
		$result = $this->execute_divide($this->absolute($fraction[0]), $fraction[1]);
		if($negative) {
			return $this->negative_value($result);	
		}
		return $result;
	}
	
	function make_fraction($fraction) {
		return $fraction[0]."/".$fraction[1];
	}
	
	public function add_fraction($value_a, $value_b) {
		$fraction_a = $this->fraction_values($value_a);
		$fraction_b = $this->fraction_values($value_b);
		
		if($fraction_a[0] == 0) {
			return $value_b;	
		}
		if($fraction_b[0] == 0) {
			return $value_a;	
		}
		if($fraction_a[1] != $fraction_b[1]) {
			$common = $this->common_denominator($value_a, $value_b);
			$fraction_a = $this->fraction_values($common[0]);
			$fraction_b = $this->fraction_values($common[1]);
		}
		$numerator = $this->add($fraction_a[0], $fraction_b[0]);		$denominator = $fraction_a[1];
		return $numerator."/".$denominator;
	}
	
	function make_fraction_negative($fraction_value) {
		$fraction_values = $this->fraction_values($fraction_value);
		$fraction_values[0] = $this->negative_value($fraction_values[0]);
		return $fraction_values[0]."/".$fraction_values[1];	
	}
	
	function add_total($term_a, $term_b, $shorten=false) {
		$result;
		if($this->negative($term_a) && $this->negative($term_b)) {
			$result = $this->negative_value($this->add_total_sub($this->absolute($term_a), $this->absolute($term_b)));	
		} else if($this->negative($term_a) && !$this->negative($term_b)) {
			$result = $this->subtract_total($this->absolute($term_b), $this->absolute($term_a));
		} else if(!$this->negative($term_a) && $this->negative($term_b)) {
			$result = $this->subtract_total($this->absolute($term_a), $this->absolute($term_b));
		} else {
			$result = $this->add_total_sub($term_a, $term_b);	
		}
		$remainder_values = $this->fraction_values($result['remainder']);
		if($remainder_values[0] == $remainder_values[1]) {
			$result['value'] = $this->add($result['value'], 1);	
			$result['remainder'] = "0/1";
		} else if($this->larger($remainder_values[0], $remainder_values[1])) {
			$division = $this->execute_divide($remainder_values[0], $remainder_values[1]);
			$result['value'] = $this->add($result['value'], $division['value']);
			$result['remainder'] = $division['remainder'];	
		}
		if($shorten) {
			$result['remainder'] = $this->execute_shorten_fraction($result['remainder']);	
		}	
		$result = $this->clean_remainder($result);
		return $result;		
	}
	
	private function add_total_sub($value_a, $value_b, $shorten=false) {
		if($value_b == NULL || $value_b['remainder'] == "" || $value_b['remainder'] == NULL) {
		}
		
		$addition = $this->add($value_a['value'], $value_b['value']);
		$value_a_negative = false;
		$value_b_negative = false;
		
		
		$fraction = $this->add_fraction($value_a['remainder'], $value_b['remainder']);
		$fraction_values = $this->fraction_values($fraction);
		$fraction_negative = false;
		
		
		$division['value'] = 0;
		if($this->larger($fraction_values[0], $fraction_values[1])) {
			$subtraction = $this->subtract($fraction_values[0], $fraction_values[1]);
			$division['value'] = 1;
			$division['remainder'] = $subtraction."/".$fraction_values[1];
		}
		
		if($division['value'] > 0) {
			$addition = $this->add($addition, $division['value']);
			$fraction = $division['remainder'];
			if($shorten) {
				$fraction = $this->execute_shorten_fraction($division['remainder']);
			}
		}
		
		return array(
			'value' => $addition,
			'remainder' => $fraction
		);
	}
	
	public function exponent($value) {
		$digits = str_split($value);
		$is_exponent = true;
		if($digits[0] != 1) {
			$is_exponent = false;	
		}
		$counter = 1;
		while($counter < count($digits)) {
			if($digits[$counter] != 0) {
				$is_exponent = false;	
			}
			$counter++;	
		}
		return $is_exponent;
	}
	
	public function count_trailing_zeros($value) {
		$digits = $this->get_digits($value);
		$counter = 0;
		$zero_count = 0;
		$break = false;
		while($counter < count($digits)) {
			if($digits[$counter] == 0 && !$break) {
				$zero_count++;
			} else {
				$break = true;	
			}
			$counter++;	
		}
		return $zero_count;
	}
	
	function unit_translation($value, $measure) {
		if($this->larger($this->absolute($measure), 1)) {
			$translation = "1/".$measure;
		} else {
			$translation = $measure."/1";
		}	
		return $translation;
	}
	
	function make_into_fraction($a, $b) {
		$multiplier_value;
		if($this->fraction_values($a['remainder'])[1] == $this->fraction_values($b['remainder'])[1]) {
			$multiplier_value = $this->fraction_values($a['remainder'])[1];
		} else {				
			$multiplier_value = $this->result($this->fraction_values($a['remainder'])[1], $this->fraction_values($b['remainder'])[1]);	
		}
		$multiplier = array('value' => $multiplier_value, 'remainder' => '0/1');
		$a_multiplication = $this->multiply_total($a, $multiplier);
		$b_multiplication = $this->multiply_total($b, $multiplier);
		$fraction = $a_multiplication['value']."/".$b_multiplication['value'];	
		return $fraction;
	}
	
	function equals_zero($value) {
		if(!is_array($value)) {
			if($value == 0) {
				return true;	
			}
		} else {
			if($value['value'] == 0 && $this->fraction_values($value['remainder'])[0] == 0) {
				return true;	
			}
		}
		return false;
	}
	
	function negative($value) {
		if(is_array($value)) {
			if(isset($value['negative']) && $value['negative']) {
				return true;	
			}
			if(strpos($value['value'], "-") !== false || ($value['value'] == 0 && strpos($value['remainder'], "-") !== false)) {
				return true;
			}
			return false;
		}
		if(strpos($value, "-") !== false) {
			return true;	
		}
		return false;
	}
	
	function negative_value($value) {
		if(is_array($value) && isset($value['value'])) {
			
			$fraction_values = $this->fraction_values($value['remainder']);
			if($value['value'] != 0) {
				$value['value'] = $this->negative_value($value['value']);	
			} else if($fraction_values[0] != 0) {
				$value['remainder'] = $this->negative_value($fraction_values[0])."/".$fraction_values[1];
			}
			return $value;
		}
		if(strpos($value, "-") === false) {
			return "-".$value;
		} else {
			$split = explode("-", $value);
			return $split[1];
		}
	}
	
	function absolute($value) {
		if(is_array($value) && isset($value['value'])) {
			return array(
				'value' => $this->absolute($value['value']),
				'remainder' => $this->absolute($value['remainder'])
			);
		}
		if(strpos($value, "-") !== false) {
			$split = explode("-", $value);
			return $split[1];	
		}
		return $value;
	}
	
	public function larger($value_a, $value_b, $equal=true) {
		$larger = true;
		
		$value_a = $this->remove_leading_zeros($value_a);
		$value_b = $this->remove_leading_zeros($value_b);
		
		if($this->negative($value_a) && !$this->negative($value_b)) {
			return false;	
		} else if(!$this->negative($value_a) && $this->negative($value_b)) {
			return true;	
		} else if($this->negative($value_a) && $this->negative($value_b)) {
			return $this->larger($this->absolute($value_b), $this->absolute($value_a), $equal);	
		}
		if(!$equal) {
			if($value_a == $value_b) {
				return false;	
			}
		}
		if(strlen($value_a) < strlen($value_b)) {
			$larger = false;	
		} else if(strlen($value_a) == strlen($value_b)) {
			$digits_a = str_split($value_a);
			$digits_b = str_split($value_b);
			$counter = 0;
			$break = false;
			while($counter < count($digits_a) && $larger && !$break) {
				if($digits_a[$counter] < $digits_b[$counter]) {
					$larger = false;	
				} else if($digits_a[$counter] > $digits_b[$counter]) {	
					$break = true;
				}
				$counter++;
			}
		}
		
		return $larger;
	}
	
	function larger_total($value_a, $value_b, $same=true) {
		if($this->larger($value_a['value'], $value_b['value'], false)) {
			return true;	
		} else if($value_a['value'] == $value_b['value']) {
			
			$common = $this->common_denominator($value_a['remainder'], $value_b['remainder']);
			$fraction_a = $this->fraction_values($common[0]);
			$fraction_b = $this->fraction_values($common[1]);
			if($this->larger($fraction_a[0], $fraction_b[0], $same)) {
				return true;	
			}
		}
		return false;
	}
	
	function larger_fraction($value_a, $value_b) {
		$common = $this->common_denominator($value_a, $value_b);
		$fraction_a = $this->fraction_values($common[0]);
		$fraction_b = $this->fraction_values($common[1]);
		if($this->larger($fraction_a[0], $fraction_b[0])) {
			return true;	
		}
		return false;	
	}
	
	public function even($value) {
		if($value == 0) {
			return true;	
		}
		$digits = $this->get_digits($value);
		$even = true;
		
		if($digits[0] % 2 == 0) {
			return true;	
		}
		return false;
	}
	
	function even_alt($value) {
		$binary_value = $this->change_base($value, 2);
		$digits = $this->get_digits($binary_value);
		if($digits[0] == 0) {
			return true;	
		}
		return false;
	}
		
	function multiple($value, $multiple) {
		$digits = $this->get_digits($value);
		$is_multiple = true;
		foreach($digits as $key => $digit) {
			if($digit % $multiple != 0) {
				$is_multiple = false;	
			}
		}
		return $is_multiple;
	}
	
	private function divisible_sequence($value) {
		$divider = 1;
		
		$mid_point = $this->execute_divide($value, 2)['value'];
		
		$divisible_values = array();
		
		
		while($divider <= $mid_point) {
			if($this->divisible($value, $divider)) {
				$divisible_values[] = $divider;
			}
			if(count($divisible_values) >= 3) {
				$additions = $this->sequence_combinations($divisible_values);
				foreach($additions as $addition) {
					if($this->verified_divisible($value, $addition)) {
						$this->add_arr($divisible_values, $addition);
					}
				}
			}
			$divider++;
		}
		return $divisible_values;
	}
	
	private function sequence_combinations($sequence) {
		$sequence = array_reverse($sequence);
		$combination_values = array();
		if(count($sequence) >= 3) {
			$this->add_arr($combination_values, $this->add_multiple(array($sequence[0], $sequence[1], $sequence[2])));
			$this->add_arr($combination_values, $this->add_multiple(array($sequence[0], $sequence[1], $sequence[1])));
			$this->add_arr($combination_values, $this->add_multiple(array($sequence[0], $sequence[0], $sequence[0])));
			$this->add_arr($combination_values, $this->add_multiple(array($sequence[0], $sequence[0], $sequence[0], $sequence[0]))); 	
		}
		if(count($sequence) >= 4) {
			$this->add_arr($combination_values, $this->add_multiple(array($sequence[0], $sequence[1], $sequence[3])));
			$this->add_arr($combination_values, $this->add_multiple(array($sequence[0], $sequence[1], $sequence[3])));
			$this->add_arr($combination_values, $this->add_multiple(array($sequence[0], $sequence[1], $sequence[1], $sequence[1]))); 	
		}
		return $combination_values;
	}
	
	function add_arr(&$arr, $value) {
		if(!is_array($value)) {
			$value = array($value);
		} 
		foreach($value as $val) {
			if(!in_array($val, $arr)) {
				$arr[] = $val;	
			}
		}	
	}
	
	private $divisible_values_result;
	private $current_divisible_value = array();
	
	private function execute_divisible_values($value) {
		$this->current_divisible_value = $value;
		$this->divisible_values_result[$this->current_divisible_value] = array();
		$this->divisible_values($value);
		return $this->divisible_values_result[$this->current_divisible_value];	
	}
	
	private function divisible_values($value) {
		$mid_point = $this->execute_divide($value, 2)['value'];
		$minimal_value = $this->execute_minimal_value($value);
		$continue = true;
		$remainder = $minimal_value['division']['value'];
		if(in_array($remainder, $this->divisible_values_result[$this->current_divisible_value])) {
			$continue = false;	
		}
		$this->add_arr($this->divisible_values_result[$this->current_divisible_value], array($minimal_value['value'], $remainder));
		
		
		
		if($continue) {
			$this->divisible_values($remainder);
		}
	}
	
	private function alter_divisible($value, $divider) {
		$value_digits = $this->get_digits($value);
		$q = $value_digits[0];
		$t = substr($value, 0, strlen($value)-1);
		$unaltered_divider = $divider;
		$divider_digits = $this->get_digits($divider);
		if($divider_digits[0] != 9) {
			$counter = 1;
			while($divider_digits[0] != 9 && $counter < 13) {
				$divider = $this->result($unaltered_divider, $counter);
				$divider_digits = $this->get_digits($divider);
				$counter++;	
			}
		}
		
		$m = $this->add($divider, 1);
		$m = $this->execute_divide($m, 10);
		
		$numerator = $this->fraction_values($m['remainder'])[0];
		
		$altered_value = $value;
		if($numerator == 0) {
			$m_value = $m['value'];
			$altered_value = $this->add($this->result($m_value, $q), $t);	
		}
		return $altered_value;
	}
	
	private $prime_factors;
	
	private $last_factor_value = 2;
	private function prime_factors_sub($value, $weak=false, $brute_division=false) {
		$prime_factors = [];
		$prime_valid = false;
		if(!$brute_division) {
			if($weak) {
				$prime_valid = $this->prime($value, NULL, true);
			} else {
				$prime_valid = $this->prime_alt($value);	
			}
		}
		if($prime_valid) {
			$prime_factors[] = $value;
		} else {
			$counter = $this->last_factor_value;//"2";
			$prime = $counter;
			$division = array('value' => '1', 'remainder' => '1/1');
			while($this->fraction_values($division['remainder'])[0] != 0 && $this->larger($value, $counter, false)) {
				$division = $this->execute_divide($value, $prime);
				if($this->fraction_values($division['remainder'])[0] == 0 && $division['value'] != "1") {
					$this->last_factor_value = $prime;
					$prime_factors[] = $prime;
					$prime_factors = array_merge($prime_factors, $this->prime_factors_sub($division['value'], $weak, $brute_division));	
				} else {
					$counter = $this->add($counter, 1);
					while(!$this->prime($counter, NULL, true)) {
						$counter = $this->add($counter, 1);
					}
					$prime = $counter;
				}
				if($this->larger($prime, $value)) {
					$prime_factors[] = $value;
					return $prime_factors;
				}
			}
		}
		return $prime_factors;
	}
	
	function prime_factors_alt($value) {
		if($value == 0) {
			return array();	
		}
		$prime_detection = new prime_detection($value, $this);
		return $prime_detection->factor($value);	
	}
	
	function prime_factors($value, $weak=false, $brute_division=false) {
		if($value == 0) {
			return array();	
		}
		$this->last_factor_value = "2";
		$prime_factors = $this->prime_factors_sub($value, $weak, $brute_division);	
		sort($prime_factors);
		return $prime_factors;
	}
	
	function reduce_common_factors($fraction) {
		$fraction_values = $this->fraction_values($fraction);
		$numerator = $this->prime_factors($fraction_values[0]);
		$denominator = $this->prime_factors($fraction_values[1]);
		
		
		$numerator_result = $numerator;
		foreach($numerator as $key_numerator => $prime_factor) {
			$key = array_search($prime_factor, $denominator);
			if($key !== false) {
				unset($denominator[$key]);	
				unset($numerator_result[$key_numerator]);
			}
		}
		$numerator = $numerator_result;
		
		
		
		$numerator_result = 1;
		foreach($numerator as $value) {
			$numerator_result = $this->result($numerator_result, $value);	
		}
		$denominator_result = 1;
		foreach($denominator as $value) {
			$denominator_result = $this->result($denominator_result, $value);	
		}
		
		
		return $numerator_result."/".$denominator_result;	
	}
	
	function divisors($value) {
		$this->divisible_marked_values = array();
		$mid_point = $this->execute_divide($value, 2)['value'];
		
		$counter = 2;
		$divisible_values = array();
		while($counter <= $mid_point) {
			if(isset($this->divisible_marked_values[$counter]) && !$this->divisible_marked_values[$counter]) {
				
			} else {
				if($this->divisible($value, $counter)) {
					$inverse = $this->execute_divide($value, $counter)['value'];
					if(in_array($inverse, $divisible_values)) {
						sort($divisible_values);
						return $divisible_values;	
					} else {
						$divisible_values[] = $inverse;
						$divisible_values[] = $counter;
					}
				}
			}
			$counter++;	
		}
		return $divisible_values;
	}
	
	private function execute_shorten_fraction_common_divisor($fraction) {
		$fraction_values = $this->fraction_values($fraction);
		$common_divisor = $this->common_divisor($fraction_values[0], $fraction_values[1]);
		while($common_divisor != 1) {
			$fraction_values[0] = $this->execute_divide($fraction_values[0], $common_divisor)['value'];
			$fraction_values[1] = $this->execute_divide($fraction_values[1], $common_divisor)['value'];
			$common_divisor = $this->common_divisor($fraction_values[0], $fraction_values[1]);
		}
		return $fraction_values[0]."/".$fraction_values[1];
	}
	
	private function common_divisor($numerator, $denominator) {
		$this->divisible_marked_values = array();
		$value = $denominator;
		if($this->larger($numerator, $denominator)) {
			$value = $numerator;	
		}
		$mid_point = $this->execute_divide($value, 2)['value'];
		
		$counter = 2;
		$divisible_values_numerator = array();
		$divisible_values_denominator = array();
		
		$stop_denominator = false;
		$stop_numerator = false;
		
		while($counter <= $mid_point) {
			if(!$stop_numerator) {
				if(isset($this->divisible_marked_values[$counter]) && !$this->divisible_marked_values[$counter]) {
				} else {
					if($this->divisible($numerator, $counter)) {
						$inverse = $this->execute_divide($numerator, $counter)['value'];
						if(in_array($inverse, $divisible_values_numerator)) {
							$stop_numerator = true;
						} else {
							$divisible_values_numerator[] = $inverse;
							$divisible_values_numerator[] = $counter;
							if(in_array($inverse, $divisible_values_denominator)) {
								return $inverse;	
							} else if(in_array($counter, $divisible_values_denominator)) {
								return $counter;	
							}
						}
					}
				}
			}
			if(!$stop_denominator) {
				if(isset($this->divisible_marked_values[$counter]) && !$this->divisible_marked_values[$counter]) {
				} else {
					if($this->divisible($denominator, $counter)) {
						$inverse = $this->execute_divide($denominator, $counter)['value'];
						if(in_array($inverse, $divisible_values_denominator)) {
							$stop_denominator = true;
						} else {
							$divisible_values_denominator[] = $inverse;
							$divisible_values_denominator[] = $counter;
							if(in_array($inverse, $divisible_values_numerator)) {
								return $inverse;	
							} else if(in_array($counter, $divisible_values_numerator)) {
								return $counter;	
							}
						}
					}
				}
			}
			
			$counter++;	
		}
		return 1;
	}
	
	private $divisible_marked_values = array();
	
	private function divisible($value, $divider) {
		$mid_point = $this->execute_divide($value, 2)['value'];
				
		$multiplier = 1;
		$divisible = false;
		$mark_values = array();
		while(!$divisible && $multiplier < 2) {
			$multiplied_value = $this->result($value, $multiplier);
			$multiplier_secondary = 1;
			$multiplied_divider = 1;
			while(!$divisible && $multiplier_secondary < $mid_point && $this->larger($multiplied_value, $multiplied_divider)) {
				$multiplied_divider = $this->result($divider, $this->result($multiplier, $multiplier_secondary));
			
				if($this->execute_divisible($multiplied_value, $multiplied_divider)) {
					$divisible = true;	
				}
				$multiplier_secondary++;
				$mark_values[] = $multiplied_divider;
			}
			$multiplier++;
		}
		foreach($mark_values as $divider_value) {
			$this->divisible_marked_values[$divider_value] = $divisible;	
		}
		return $divisible;
	}
	
	private function divisibility_pattern($values) {
		$pattern_result = "";
		$counter = 0;
		foreach($values as $value) {
			if($value) {
				$pattern_result .= $counter."-";
				$counter = 1;	
			} else {
				$counter++;	
			}
		}
		return $pattern_result;
	}
	
	private $altered_divisible_value = array();
	private $altered_divisible_value_aux = array();
	
	private function execute_divisible($value, $divider, $alter=true) {			
		if($value == $divider) {
			return true;	
		}
		if($divider == 1) {
			return true;	
		}
		if($divider == 0) {
			return false;	
		}
		if(strlen($divider) > strlen($value)) {
			return false;	
		}
		
		$half_point = $this->execute_divide($value, 2);
		if($this->larger($divider, $half_point['value'], false)) {
			return false;	
		}
		
		$valid = true;
		
		
		
		$double_divider = $this->result($divider, 2);
		
		$sum_value = $this->digit_sum($value);
		$sum_divider = $this->digit_sum($divider);
		$sum_double_divider = $this->digit_sum($double_divider);
		$result = $this->result($value, $divider);
		$sum_result = $this->digit_sum($result);
		$sum_result_sum = $this->digit_sum($sum_result);
		$verified = $sum_value % $sum_divider == 0;		
		$divider_digits = str_split($divider);
		$last_divider_digit = $divider_digits[count($divider_digits)-1];
		 
		if(!$verified) {				
			$valid = true;
		}
		
		if(strlen($divider) == 1) {
			if($this->larger(strlen($value), $this->maximum_divider_exponent)) {
				$division = $this->execute_divide($value, $divider);
				if($this->fraction_values($division)[0] == '0') {
					return true;	
				}
				return false;
			} else {
				if($value % $divider == 0) {
					return true;	
				}
				return false;
			}
		}
		if(strlen($value) == 1) {
			if($value % $divider == 0) {
				return true;	
			}
			return false;
		}
		if($this->prime($value)) {
			return false;	
		}
		$digits = str_split($value);
		$last_value = $digits[count($digits)-1];
		$allow_zeros = false;
		if($last_value >= $divider) {
			$allow_zeros = true;
		}
		
		$value_count = $this->count_trailing_zeros($value);
		$divider_count = $this->count_trailing_zeros($divider);
				
		
		$sum_remainder = $sum_value % $sum_divider;
		$this->divisible_output = "\n\n";
		
		$last_value_inverse = 10 - $last_value;
		$last_divider_inverse = 10 - $last_divider_digit;
		
		
		if($divider == 10 && $last_value == 0) {
		} else if($value == $divider) {
		} else if($this->larger($divider, $value)) {
			$valid = false;
		} else if($this->exponent($divider)) {
			if($divider_count > $value_count) {
				$valid = false;	
			}
		} else if(($last_value == 5 || $last_value == 0) && $divider == 5) {
		} else if($this->even($value) && $divider == 2) {
		} else {
			$false_position_index = 0;
			if($divider_count > $value_count) {
				$valid = false;	
			} else {	
			}
			$sum = 0;
			foreach($digits as $digit) {
				$sum += $digit;	
				if($digit == 0 && !$allow_zeros) {
					$valid = false;	
				}
			}
			
			
			
			$sum_sum = $sum_divider+$sum_result;
			$sum_sum_sum = $this->digit_sum($sum_sum); 
			
			$addition_sum = $sum + $sum_divider;
			$addition_sum_last_value = $this->get_digits($addition_sum)[0];
			
			$last_digit_divider_sum = $this->get_digits($sum_divider)[0];
			$last_digit_divider_sum_inverse = 10 - $last_digit_divider_sum;
			
			$secondary_sum_value = $this->digit_sum($value);
			
			$addition_sum_second = $last_digit_divider_sum_inverse + $secondary_sum_value;
			$last_digit_addition = $this->get_digits($addition_sum)[0];
			
			
			$value_sum = $this->string_sum($value);
			$divider_sum = $this->string_sum($divider);
			
			
			$value_sum_sum = $this->digit_sum($value_sum);
			$divider_sum_sum = $this->digit_sum($divider_sum);
			
			
			$alt_value_sum = $this->string_sum($value);
			$alt_divider_sum = $this->string_sum($divider);
			
			$add_alt = $alt_value_sum + $alt_divider_sum;
			
			$rev_alt_value_sum = $this->string_sum($value, true);
			$rev_alt_divider_sum = $this->string_sum($divider, true);
			
			$add_rev = $rev_alt_value_sum + $rev_alt_divider_sum;
			
			$subtract_value = $this->digit_sum($value, true);
			$subtract_divider = $this->digit_sum($divider, true);
			
			$subtract_value_last_value = $this->get_digits($subtract_value)[0];
			$subtract_divider_last_value = $this->get_digits($subtract_divider)[0];
			
			
			if($sum % 2 == 0 && $divider == 2) {
				
				return false;	
			}
			if(($sum == 10 && $divider != 2) || ($sum % 5 == 0 && $divider == 5)) { 				
				$false_position_index .= 0;
				$valid = false;	
			}
			if(($last_value % 2 == 0 && ($sum_divider % 2 != 0)) || ($last_value % 2 != 0 && $sum_divider % 2 == 0)) {
				$false_position_index .= 1;
				$valid = false;	
			}
			if($sum_divider % 3 == 0) {
				if($sum % 3 != 0) {
					$false_position_index .= 2;
					$valid = false;	
				}
			}
			
			
			if(($last_value % 2 == 0 && ($sum_divider % 2 != 0) && $sum % 2 == 0 && $last_divider_digit % 2 != 0)) { 				
				$valid = false;
			}
			if(($last_value % 2 == 0 && ($sum_divider % 2 != 0) && $sum % 2 != 0 && $last_divider_digit % 2 == 0)) {
				$valid = false;				
			}
			if($last_value % 2 != 0 && $sum_divider % 2 == 0 && $sum % 2 != 0 && $last_divider_digit % 2 == 0) { 				
				$valid = false;	
			}
			if($last_value % 2 != 0 && $sum_divider % 2 == 0 && $sum % 2 == 0 && $last_divider_digit % 2 != 0) { 				
				$valid = false;	
			}
			if(($last_value % 2 != 0 && $sum_divider % 2 == 0) && !$this->prime($divider)) { 				
				$valid = false;	
			}
			if(($last_value % 2 == 0 && $sum_divider % 2 != 0) && $this->prime($divider)) {				
				$valid = false;	
			}
			if(($sum % 2 == 0 && $sum_divider % 2 != 0) || ($sum % 2 != 0 && $sum_divider % 2 == 0)) {
				$valid = false;	
			}
			if($sum % $sum_divider != 0 && ($this->prime($sum) && $this->prime($sum_divider)) && $sum_result % $sum_divider == 0) { 				
				$false_position_index .= 3;
				$valid = false;	
				
			}
			
			if($sum % $sum_divider != 0 && (!$this->prime($sum_divider) || !$this->prime($divider))) {
				$valid = false;	
			}
			
			if(($this->prime($sum) || $this->prime($sum_divider))) {				
				if($this->prime($add_rev) && !$this->prime($add_alt)) {
					if($last_digit_addition == $last_divider_digit) {
						$valid = false;	
					}
				}
			}
			
			
			if($sum == $divider) {
			}
			if($sum == $sum_divider) {
				$false_position_index .= 4;
				$valid = false;	
			}
			
			if($sum % $sum_divider != 0 && (!$this->prime($divider) )) { 				
				$false_position_index .= 5;
				$valid = false;	
			}
			
			
			if($last_digit_divider_sum_inverse == $last_value) {
				
				$valid = false;	
				if($last_value == $last_divider_digit) {
				}
			} else if($this->divisible_sub($last_digit_divider_sum_inverse, $last_value) && $this->divisible_sub($last_divider_digit, $addition_sum)
			 	&& $last_digit_addition == $last_divider_digit) {
			} else if(($last_digit_addition + $last_divider_digit) == $last_value) {
			}
			
			if($this->prime($sum_divider) && !$this->prime($sum_result_sum) && !$this->prime($sum)) {
				
				if($sum_remainder % $sum_divider == 0) {
					$valid = false;	
				}
				if($last_value == $last_divider_digit) {
					$valid = false;	
				} 
			} else if(!$this->prime($sum_divider) && $this->prime($sum_result_sum) && !$this->prime($sum)) {
				if($sum_remainder % $sum_divider != 0) {
					$valid = false;	
				}
				if($last_value != $last_divider_digit) {
					$valid = false;	
				} 
			}
			
			if(!$this->prime($sum_result_sum) && ($sum_result+$sum_divider) == $divider) {
				$false_position_index .= 6;
				$valid = false;	
			}
			if(!$this->prime($sum_result)) {
				$false_position_index .= 7;
				$valid = false;	
			}			
			if(!$this->prime($sum % $sum_divider)) {
				$false_position_index .= 8;
				$valid = false;	
			}
			
			
			$non_positive_indicies = array(5,7,8);
			
			if(in_array($false_position_index, $non_positive_indicies)) {
				
			}
			
			
			if($this->prime($last_digit_divider_sum_inverse) && !$this->prime($last_digit_addition)) {
				$valid = false;	
			}
			
			
			if($subtract_divider_last_value == $last_value && $subtract_value_last_value == $last_divider_digit) {
				$valid = false;	
			}
			if($subtract_divider_last_value == $last_divider_inverse && $subtract_value_last_value == $last_value_inverse) {
				$valid = false;	
			}
			
			
			if($valid) {
				
				
				
			}
			
			if($valid) {
				
			}
			
			$valid_string = "true";
			
			
			$alterable = array(1,3,7,9);
			
			if($alter && in_array($last_divider_digit, $alterable)) {
				if($valid) {
					if(!isset($this->altered_divisible_value[$value])) {
						$this->altered_divisible_value[$value] = $this->alter_divisible($value, $divider);
					}
					$valid = $this->execute_divisible($this->altered_divisible_value[$value], $divider, false);
					
				}
			}
			
			
			
		}   
		
		
		return $valid;
	}
	
	function list_divisors($value) {
		$factors = $this->prime_factors($value);
		$divisors = $factors;
		
		$combinations = $this->combinations($factors);
		
		foreach($combinations as $combination) {
			$result = 1;
			foreach($combination as $combination_value) {
				$result = $this->result($result, $combination_value);
			}
			if(!in_array($result, $divisors)) {
				$divisors[] = $result;
			}
		}
		$divisors[] = 1;
		$divisors = array_unique($divisors);
		sort($divisors);
		return $divisors;
	}
	
	private function list_divisors_alt($value) {
		$counter = 1;
		$results = array(1, $value);
		while($this->larger($value, $counter)) {
			if($this->divisible($value, $counter)) {
				$results[] = $counter;	
			}
			$counter = $this->add($counter, 1);
		}
		return $results;
	}
	
	private function divisible_sub($value, $divider) {
		if($value == 0 || $divider == 0) {
			return false;	
		}
		if($value > $divider) {
			return $value % $divider == 0;	
		}
		return $divider % $value == 0;
	}
	
	function string_sum($value, $alter=false, $subtract=false) {
		$digits = $this->get_digits($value);
		$interlope = 0;
		$sum = 0;
		foreach($digits as $digit) {
			if(!$alter) {
				if($interlope == 0) {
					if(!$subtract) {
						$sum = $this->add($sum, $digit);	
					} else {
						$sum = $this->subtract($sum, $digit);		
					}
				}
			} else {
				if($interlope == 1) {
					if(!$subtract) {
						$sum = $this->add($sum, $digit);	
					} else {
						$sum = $this->subtract($sum, $digit);
					}
				}
			}
			if($interlope == 0) {
				$interlope = 1;	
			} else {
				$interlope = 0;	
			}
		}
		return $sum;
	}
	
	public $divisible_output = "";
	
	private function auxiliary_alter_divisible($value, $divider) {
		$digits = $this->get_digits($value);
		$last_digit = $digits[0];
		$altered_value;
		$multiplier = substr($divider, 0, strlen($divider)-1);
		$divider_last_digit = substr($divider, strlen($divider)-1, 1);
		
		$value_remainder = substr($value, 0, strlen($value)-1);
		
		switch($divider_last_digit) {
			case 1:
				$altered_value = $this->result($last_digit, $multiplier);
				$altered_value = $this->subtract($value_remainder, $altered_value);
				break;
			case 3:
				$multiplier = $this->result($multiplier, 7);
				$multiplier = $this->add($multiplier, 2);
				$altered_value = $this->result($last_digit, $multiplier);
				$altered_value = $this->subtract($value_remainder, $altered_value);
				break;
			case 7:
				$multiplier = $this->result($multiplier, 3);
				$multiplier = $this->add($multiplier, 2);
				$altered_value = $this->result($last_digit, $multiplier);
				$altered_value = $this->subtract($value_remainder, $altered_value);
				break;
			case 9:
				$multiplier = $this->result($multiplier, 9);
				$multiplier = $this->add($multiplier, 2);
				$altered_value = $this->result($last_digit, $multiplier);
				$altered_value = $this->subtract($value_remainder, $altered_value);
				break;
		}
		if($this->negative($altered_value) || $altered_value == 0) {
			return $value;	
		}
		return $altered_value;
	}
	
	function verified_divisible($value, $divider) {
		$division = $this->execute_divide($value, $divider);
		$numerator = $this->fraction_values($division['remainder'])[0];
		if($numerator == 0) {
			return true;	
		}
		return false;
	}
	
	
	function digit_sum($value, $subtract=false) {
		$digits = $this->get_digits($value);
		$sum = 0;
		foreach($digits as $digit) {
			if(!$subtract) {
				$sum += $digit;	
			} else {
				$sum = $digit - $sum;	
			}
			
		}
		return $this->absolute($sum);
	}
	
	function final_digit_sum($value) {
		while(strlen($value) > 1) {
			$value = $this->digit_sum($value);	
		}
		return $value;
	}
	
	public function normalize_base($value, $base) {
		$digits = $this->get_digits($value);
		$exponent_count = count($digits);
		$result = "";
		foreach($digits as $exponent => $digit) {
			if($exponent == 0) {
				$result = $this->add($result, $digit);
			} else {
				$digit = $digit*$base;
				$result = $this->add_place($result, $digit, $exponent-1);
			}
		}
		return $result;
	}
	
	private function add_zeros($value, $count) {
		$counter = 0;
		while($counter < $count) {
			$value .= "0";
			$counter++;	
		}
		return $value;
	}
	
	function bit_shift($value, $places, $change_base=true) {
		$binary_value;
		if($change_base) {
			$binary_value = $this->change_base($value, 2);
		} else {
			$binary_value = $value;	
		}
		$binary_value = $this->pad_zeros($binary_value, $places);
		
		$resutling_value;
		if($change_base) {
			$resulting_value = $this->change_base_decimal($binary_value, 2);
		} else {
			$resulting_value = $binary_value;	
		}
		return $resulting_value;	
	}
	
	function bit_shift_right($value, $places, $change_base=true) {
		$binary_value;
		if($change_base) {
			$binary_value = $this->change_base($value, 2);
		} else {
			$binary_value = $value;	
		}
		$places = $this->subtract(strlen($binary_value), $places);
		$places = $this->subtract($places, "1");
		$binary_value = substr($binary_value, 0, $places); // (int)(strlen($binary_value)-1-$places)	
		$resutling_value;
		if($change_base) {
			$resulting_value = $this->change_base_decimal($binary_value, 2);
		} else {
			$resulting_value = $binary_value;	
		}
		return $resulting_value;	
	}
	
	public function change_base_decimal($value, $old_base) {
		$new_value = "0";
		$digits = $this->get_digits($value);
		$exponent_value = 1;
		foreach($digits as $index => $digit) {
			$value_addition = $this->result($digit, $exponent_value);
			$new_value = $this->add($new_value, $value_addition);
			$exponent_value = $this->result($exponent_value, $old_base);	
		}
		return $new_value;
	}
	
	public function change_base_total($value, $new_base, $base=10, $limit_decimals=false) {
		if(is_array($new_base)) {
			$new_base = $new_base['value'];	
		}
		$value['value'] = $this->change_base($value['value'], $new_base, $base, $limit_decimals);
		$value['remainder'] = $this->fraction_base($value['remainder'], $new_base, $base, $limit_decimals);	
		return $value;
	}
	
	private $maximum_base_change_exponent;
	public function change_base($value, $new_base, $base=10, $limit_decimals=true, $find_last_exponent=true) {
		$unaltered_value = $value;
		if($new_base == 10) {
			return $this->change_base_decimal($value, $base);	
		}
		if($new_base > 10 || $new_base < 2) {
			return false;	
		}
		if($value == 0) {
			return "0";	
		}
		if($value == 1) {
			return "1";	
		}
		if($base == "10") {
			$number_conversion = new number_conversion($this);
			return $number_conversion->convert($value, $new_base);
		}
		$digits = str_split($value);		
		$exponent_count = count($digits)-1;
		$result = 0;
		$carry_bit = 0;
		$exponent_values = array();
		$exponent_value = $new_base;
		$exponent_values[] = 1;
		$exponent_values[] = $exponent_value;
		$counter = 0;
		while($counter < count($digits)-2) {
			$exponent_value = $this->result($exponent_value, $new_base);
			$exponent_values[] = $exponent_value;
			$counter++;
		}
		$exponent_values = array_reverse($exponent_values);
		$this->maximum_base_change_exponent = $exponent_values[0];
		$result = "";
		
		$exponent = 0;
		$updated_value = $value;
		while($exponent <= $exponent_count) {
			$digit = $digits[$exponent]; 
			$exponent_length = $exponent_count - $exponent;
			if($exponent == count($digits)-1) {
				$result = $this->add_sub($result, $digit, $new_base, $limit_decimals); 			
			} else {
				$digit_value = $digit;
				$counter = 0;
				while($counter < $exponent_length) {
					$digit_value .= "0";	
					$counter++;
				}
				$division_value = implode("", $digits);
				$division = $this->execute_divide($division_value, $exponent_values[$exponent]);
				$new_digit = $division['value'];
				$subtract_value = $this->result($new_digit, $exponent_values[$exponent]);
				$remainder = $this->subtract($updated_value, $subtract_value);
				
				$updated_value = $remainder;				
				$count_difference = $exponent_count+1-strlen($updated_value);
				$updated_value = $this->pad_zeros($updated_value, $count_difference, true);
				$digits = str_split($updated_value);
				
				$digit_prefix = "";
				if($new_digit > $new_base) {
					
					$new_digit = $this->change_base($new_digit, $new_base);
				}
				$new_digit = $this->pad_zeros($new_digit, $exponent_length);
				$result = $this->add_sub($result, $new_digit, $new_base, $limit_decimals);
			}
			$exponent++;
		}
		if($find_last_exponent) {
			$exponent_difference = strlen($result)-strlen($unaltered_value);
			$exponent_value = $exponent_values[0];
			$counter = 0;
			if($exponent_difference > 0) {
				while($counter < $exponent_difference) {
					$exponent_value = $this->result($exponent_value, $new_base);
					$this->maximum_base_change_exponent = $exponent_value;
					$counter++;
				}	
			} else {
				$index = abs($exponent_difference);
				$this->maximum_base_change_exponent = $exponent_values[$index];
			}
		}
		return $result;
	}
	
	public function fraction_base($value, $new_base, $base=10, $limit_decimals=false) {
		$split = explode("/", $value);
		$numerator = $this->change_base($split[0], $new_base, $base, $limit_decimals);
		$denominator = $this->change_base($split[1], $new_base, $base, $limit_decimals);
		return $numerator."/".$denominator;	
	}
	
	private function equalize_fraction($value) {
		$fraction = $this->fraction_values($value);
		$decimal_mult = "10";
		$digit_counter = 1;
		$found = false;
		$multiplier = NULL;
		while(!$found) {
			$counter = 1;
			while($counter <= 9 && !$found) {
				$decimal_mult = $counter;
				$decimal_mult = $this->pad_zeros($decimal_mult, $digit_counter);
				if($fraction[1] % $decimal_mult == 0) {
					$found = true;	
					$multiplier = $decimal_mult;
				}
				$counter++;	
			}
			$digit_counter++;
		}
		return $multiplier;	
	}
	
	private function decimal_mult($value) {
		if(strlen($value) <= 1) {
			return false;	
		}
		$digits = str_split($value);
		$counter = 1;	
		$is_decimal_mult = true;
		while($counter < count($digits)) {
			if($digits[$counter] != 0) {
				$is_decimal_mult = false;	
			}
			$counter++;	
		}
		return $is_decimal_mult;
	}	
	
	function all_digits_same($value) {
		$digits = $this->get_digits($value);
		$first_digit = $digits[0];
		foreach($digits as $digit) {
			if($digit != $first_digit) {
				return false;	
			}
		}
		return true;
	}
	
	function modulus($value, $divider) {
		if($value == 0 || $divider == 0) {
			return 0;	
		}
		if($value == $divider) {
			return 0;	
		}
		$division = $this->execute_divide($value, $divider);
		$numerator = $this->fraction_values($division['remainder'])[0];
		return $numerator;
	}
	
	function ord($value, $modulus_value) {
		$power = 1;
		$value_power = 1;
		
		while($power <= strlen($value)) {
			$value_power = $this->result($value_power, $value);
			$modulus = $this->modulus($value_power, $modulus_value);
			if($modulus == 1) {
				return $power;	
			}
			$power = $this->add($power, 1);	
		}
		return 1;
	}
	
	function perfect_power($value) {
		$closest_value = NULL;
		$max_root = $this->ceil($this->natural_logarithm(array('value' => $value, 'remainder' => '0/1')));
		$power = 2;
		while($this->larger($max_root, $power)) {
			$root = $this->root($value, $power);
			if($root !== false) {
				return true;	
			}
			$closest_value = $this->root_closest_result;
			$power = $this->add($power, 1);
		}
		return false;
	}
		
	function gcd($a, $b) {
		if($a == "0") {
			return $b;
		}
		return $this->gcd($this->modulus($b, $a), $a);
	}
	
	function execute_shorten_fraction($value, $bypass_truncation=false) {
		$negative = false;
		$value_unaltered = $value;
		if($this->negative($value)) {
			$negative = true;	
		}
		$value = $this->absolute($value);
		$fraction_values = $this->fraction_values($value);
		
		if($fraction_values[0] === $fraction_values[1]) {
			$value = "1/1";
			if($negative) {
				return $this->negative_value($value);	
			}
			return $value;
		}
		if($fraction_values[0] == "0") {
			return "0/1";	
		}
		if($this->truncate_fractions_length > 0 && $bypass_truncation == false) {
			if(strlen($fraction_values[1]) > $this->truncate_fractions_length) {
				$real_fraction = $this->real_fraction($value, $this->truncate_fractions_length);				
				$whole_value = $this->whole_common($real_fraction);
				$whole_value = $this->whole_numerator($whole_value);
				if($negative) {
					$whole_value = $this->negative_value($whole_value);	
				}
				return $whole_value;	
			}
			return $value_unaltered;
		}
		$a = $fraction_values[0];
		$b = $fraction_values[1];
		$result = $this->shorten_fraction_gcd_sub($a, $b);	
		if($negative) {
			$result = $this->negative_value($result);	
		}
		return $result;
	}
	
	function shorten_fraction_gcd_sub($a, $b) {
		$gcd = $this->gcd($a, $b);
		if($gcd == "1") {
			return $a."/".$b;	
		}
		$a_divided = $this->execute_divide($a, $gcd)['value'];
		$b_divided = $this->execute_divide($b, $gcd)['value'];	
		return $this->shorten_fraction_gcd_sub($a_divided, $b_divided);
	}
  
	function coprime($a, $b)  { 
		if($this->gcd($a, $b) == 1) 
			return true;
		else
			return false;
	}
	
	function modexp($a, $b, $n) {
		$c = 1;
		while($this->larger($b, 0, false)) {
			if($this->modulus($b, 2) == 1) {
				$c = $this->result($c, $a);
				$c = $this->modulus($c, $n);
			}
			$a = $this->result($a, $a);
			$a = $this->modulus($a, $n);
			
			$b = $this->bit_shift_right($b, 0);		
		}
		return $c;
	}
	
	
	private function log_primes() {
		$query = "SELECT value FROM primes ORDER By id DESC LIMIT 1";
		$start = $this->sql->get_row($query)['value'];
		var_dump($start);
		$start = $this->add($start, 1);
		$max = "1100000000";
		while($this->larger($max, $start)) {
			$prime = $this->prime($start, true);
			if($prime) {
				echo "prime: ".$start."\n";
			} else {
				echo "not prime: ".$start."\n";	
			}
			$start = $this->add($start, 1);	
		}
		
	}
	
	
	public $base_primes = array(1, 2, 3, 5, 7, 11, 13, 17, 19, 23, 29, 31, 37, 41, 43, 47, 53, 59, 61, 67, 71, 73, 79, 83, 89, 97);
	private $valid_prime = array(1, 2, 3, 4, 5, 7, 8, 11, 14, 15, 16, 17, 18, 20, 23, 26, 28, 29, 31, 34, 41, 47, 52); 	
	private $valid_prime_last_character = array(9, 3, 1, 7);
	
	private $prime_last_digit;
	
	private $maximum_prime_logged = "1073741789";
	
	private $sub_prime_call_flag = false;
	private function execute_prime($value, $bypass=false) {
		$this->prime_sub_divider_candidates = array();
		$this->sub_prime_call_flag = false;
		return $this->prime($value, $bypass);	
	}
	
	private $known_primes = array();
	private $known_non_primes = array();
	private $primes_looked = array();
	private $prime_maximum_value = NULL;
	
	function prime_p($value) {
		$prime_detection = new prime_detection($value, $this);
		return $prime_detection->pollard_check($value);	
	}
	
	function prime_alt($value) {
		$value = $this->absolute($value);
		
		
		if($value == 0 || $value == NULL) { 			
			return false;	
		}
		if(strlen($value) <= 2) {
			if(in_array($value, $this->base_primes)) {
				return true;
			}
			return false;
		}
		/*if(isset($this->known_primes[$value])) {
			return true;	
		}*/
		/*if(isset($this->known_non_primes[$value])) {
			return false;	
		}*/
		$digits = str_split($value);
		$last_value = $digits[count($digits)-1];
		
		
		
		$this->prime_last_digit = $last_value;
		
		$valid = false;
		if($this->all_digits_same($value) && ($last_value != 1 || strlen($value) > 2)) {
			return false;	
		}
		
		if($this->prime_sum_verification($value) && in_array($last_value, $this->valid_prime_last_character)) {
			$prime_detection = new prime_detection($value, $this);
			
			$sub_prime = substr($value, 1);
			$digit_sum = $this->digit_sum($value);
			$last_character = $this->get_digits($digit_sum)[0];
			$digit_sum_digit_sum = $this->digit_sum($digit_sum);
			$valid = $prime_detection->prime_division_verification($value);
			if($valid) {																		
				if($prime_detection->inspect_bases()) {
					return false;	
				}
				if($prime_detection->palindrome()) {
					return false;	
				}
				if($prime_detection->repeat_value()) {
					return false;	
				}
				$valid = $this->prime_verification($value);	
				if($valid) {
					$root_valid = $this->prime_root_verification($value);
					
					if($root_valid == false) {
						$valid = false;
					} else {
						if(!$this->prime_triangle($value)) {
								
							if($this->prime_row_offset($value)) {
								if($valid) {
									$valid = $this->prime_row_offset_aux($value);	
									if($valid) {
										$valid = $this->prime_row_offset_aux_alt($value);	
										if($valid) {
											$valid = $this->prime_fold($value);	
											if($valid) {
												if($valid) {
													if($valid) {
														$valid = $this->prime_root_auxillary_fast($value);
													}
												}
											}
										}
									}
								}
							} else {
								$valid = false;	
							}
						}
					}
				}
				if($valid) {
				}
				/*if($valid) {
					$this->last_prime_found = $value;
					$this->known_primes[$value] = true;	
				} else {
					$this->known_non_primes[$value] = true;	
				}*/
				return $valid;
			}
		}
		return false;
	}
	
	private $last_prime_found = 41;
	
	function prime($value, $closest_known_prime=NULL, $weak=false, $strength="8") {
		$value = $this->absolute($value);
		
		
		if($value == 0 || $value == NULL) { 			
			return false;	
		}
		if(strlen($value) <= 2) {
			if(in_array($value, $this->base_primes)) {
				return true;
			}
			return false;
		}
		/*if(isset($this->known_primes[$value])) {
			return true;	
		}*/
		/*if(isset($this->known_non_primes[$value])) {
			return false;	
		}*/
		$digits = str_split($value);
		$last_value = $digits[count($digits)-1];
		
		
		
		$this->prime_last_digit = $last_value;
		
		$valid = false;
		if($this->all_digits_same($value) && ($last_value != 1 || strlen($value) > 2)) {
			return false;	
		}
		
		if($strength != "8") {
			$this->binary_modulus->set_strength($strength);	
		}
		
		if($this->prime_sum_verification($value) && in_array($last_value, $this->valid_prime_last_character)) {
			$prime_detection = new prime_detection($value, $this);
			
			$sub_prime = substr($value, 1);
			$digit_sum = $this->digit_sum($value);
			$last_character = $this->get_digits($digit_sum)[0];
			$digit_sum_digit_sum = $this->digit_sum($digit_sum);
			$valid = $prime_detection->prime_division_verification($value);
			if($valid) {																		
				if($prime_detection->inspect_bases()) {
					return false;	
				}
				if($prime_detection->palindrome()) {
					return false;	
				}
				if($prime_detection->repeat_value()) {
					return false;	
				}
				$valid = $this->prime_verification($value);	
				if($valid) {
					$root_valid = $this->prime_root_verification($value);
					
					if($root_valid == false) {
						$valid = false;
					} else {
						if(!$this->prime_triangle($value)) {
								
							if($this->prime_row_offset($value)) {
								if($valid) {
									$valid = $this->prime_row_offset_aux($value);	
									if($valid) {
										$valid = $this->prime_row_offset_aux_alt($value);	
										if($valid) {
											$valid = $this->prime_fold($value);	
											if($valid) {
												/*$digit_sum_result = $prime_detection->inspect_digit_sums();
												if($digit_sum_result) {
													$valid = false;
												} else {*/
													$value_binary = $prime_detection->base_values["2"];	
													if($closest_known_prime !== NULL) {
														$closest_known_prime = $this->change_base($closest_known_prime, "2");
														$this->binary_modulus->set_closest_known_prime($closest_known_prime);	
													}
													$valid = $this->binary_modulus->iterative_subtraction($value_binary, $value);
													if($valid) {
														//$valid = $this->binary_modulus->iterative_subtraction_alt($value_binary);
														if($valid) {
															$valid = $this->binary_modulus->prime_validation($value_binary);
															if($valid) {	
																$valid = $this->binary_modulus->execute_fermat_quotient_modulus($value_binary, $value);
																if($valid) {
																	$valid = $this->binary_modulus->prime($value_binary, $value);
																	if($valid) {
																		//return $valid;
																		/**/
																		if(!$weak) {
																			$valid = $this->binary_modulus->prime_verification_auxillary($value_binary);
																			if($closest_known_prime != NULL) {
																				$valid = $this->binary_modulus->iterative_subtraction_alt($value_binary);
																				if($valid === "undetermined") {
																					$valid = $this->prime_root_auxillary_fast($value);	
																				}
																			}
																		}
																		
																	}
																}
															}
														}
													}
												//}
											}
										}
									}
								}
							} else {
								$valid = false;	
							}
						}
					}
				}
				if($valid) {
				}
				/*if($valid) {
					$this->last_prime_found = $value;
					$this->known_primes[$value] = true;	
				} else {
					$this->known_non_primes[$value] = true;	
				}*/
				return $valid;
			}
		}
		return false;
	}
	
	function prime_base($value) {
		$unaltered_value = $value;
		$last_base = 10;
		while(true) {
			$digits = $this->get_digits($value);
			$last_digit = $digits[0];
			if($last_digit == 0) {
				return false;
			}
			if($last_digit == $last_base || $last_digit == 1) { 
				return true;
			}
			$value = $this->change_base($unaltered_value, $last_digit);
			$last_base = $last_digit;
		}
		return false;
	}
	
	private $exclude_seven = array( );
	
	
	
	private $prime_sum_value = 0;
	
	private $prime_sum_last_value;
	private $prime_sum_primary_value;		
	
	private function prime_sum_verification($value) {
		$sum = $this->digit_sum($value);
		
		$this->prime_sum_primary_value = $sum;
		$division = $this->execute_divide($sum, 3);
		if($this->fraction_values($division['remainder'])[0] == 0) {
			return false;	
		}
		
		
		
		$division = $this->execute_divide($value, $sum);		
		
		$sum_division = $this->execute_divide($sum, $value);		
		$division = $this->execute_divide($division, $sum_division);
		if($this->fraction_values($division['remainder'])[0] == 0) {
			return false;	
		}
		return true;
	}
	
	public $prime_root_result = array();
	public $prime_root_squared = array();
	public $prime_second_root = array();
	private function prime_root_verification($value) {
		$root = $this->root($value, 2);
		if($root !== false) {
			return false;	
		}
		$valid = true;
		$closest_root = $this->root_closest_result;
		$squared = $this->execute_power_whole(array('value' => $closest_root, 'remainder' => '0/1'), 2)['value'];
		if($this->larger($squared, $value)) {
			$closest_root = $this->subtract($closest_root, 1);	
			$squared = $this->execute_power_whole(array('value' => $closest_root, 'remainder' => '0/1'), 2)['value'];
		}
		$this->prime_root_result[$value] = $closest_root;
		$this->prime_root_squared[$value] = $squared;
		
		$squared_mid_point = $closest_root;		
		if($this->larger($squared_mid_point, $value)) {
			$squared_mid_point = $this->subtract($value, 1);	
		}
		$remainder = $this->subtract($value, $squared);
		
		$division = $this->execute_divide($remainder, $closest_root);
		
		$sub_whole = $this->result($division['value'], $closest_root);
		
		$sub_remainder = $this->subtract($remainder, $sub_whole);
		if($sub_remainder == 0) {
			return false;	
		}
		
		$prime_second_root = $this->square_root($closest_root);
		if($prime_second_root === false) {
			$prime_second_root = $this->root_closest_result;	
		}
		$this->prime_second_root[$value] = $prime_second_root;
		
		$row_count = $this->add($closest_root, $division['value']);
		$whole = $this->result($row_count, $closest_root);
		while($this->larger($closest_root, $prime_second_root, false)) {
			
			if($row_count == $sub_remainder) {
				return false;	
			}
			if($row_count > $sub_remainder) {
				return true;	
			}
			
			if(($this->even($sub_remainder) && $this->even($row_count)) || ($this->even($sub_remainder) && $this->even($closest_root)) || ($this->even($sub_remainder) && $this->even($whole))) {
				return false;	
			}
			
			if(!$this->even($sub_remainder) && !$this->even($whole)) {
				return true;	
			}
			if(!$this->even($sub_remainder) && !$this->even($row_count)) {
				return true;	
			}
			if(!$this->even($sub_remainder) && !$this->even($closest_root)) {
				return true;	
			}
			
			$addition = $row_count;
			$sub_remainder_subtraction = $this->subtract($closest_root, $sub_remainder);
			
			if($sub_remainder_subtraction == 0) {
				return false;	
			}
			
			$sub_remainder_subtraction = $this->subtract($sub_remainder_subtraction, 1);
			$sub_remainder = $this->subtract($addition, $sub_remainder_subtraction);
			$row_count_addition = 1;
			
			$closest_root = $this->subtract($closest_root, 1);
			$row_count = $this->add($row_count, $row_count_addition);
			
			$whole = $this->result($row_count, $closest_root);
			
		}
		return true;
	}
	
	private $prime_sub_divider_candidates = array();
	
	private function prime_sub_divider_verification($value) {
		
		return $this->prime_divider_aux($value);
	}
	
	private function prime_root_auxillary_alt($value) {
		$closest_root = $this->prime_root_result[$value];
		
		
		$squared = $this->prime_root_squared[$value];
		
		$squared_mid_point = $closest_root;		if($this->larger($squared_mid_point, $value)) {
			$squared_mid_point = $this->subtract($value, 1);	
		}
		$remainder = $this->subtract($value, $squared);
		$division = $this->execute_divide($remainder, $closest_root);
		$sub_whole = $this->result($division['value'], $closest_root);
		$sub_remainder = $this->subtract($remainder, $sub_whole);
		
		if($sub_remainder == 0) {
			return false;	
		}
		
		$row_count = $this->add($closest_root, $division['value']);
		$whole = $this->result($row_count, $closest_root);
		
		
		if(($this->even($sub_remainder) && $this->even($row_count)) || ($this->even($sub_remainder) && $this->even($closest_root)) || ($this->even($sub_remainder) && $this->even($whole))) {
			return false;	
		}
		if($row_count == $sub_remainder) {
			return false;	
		}
		
		$continue = false;
			$auxillary_division = $this->execute_divide($value, $row_count);
			if($this->fraction_values($auxillary_division['remainder'])[0] == 0) {
				return false;	
			}
			
			while($this->prime($row_count) || $this->prime($sub_remainder) || $sub_remainder == 1) {
				$sub_remainder = $this->add($row_count, $sub_remainder);	
				
				$closest_root = $this->subtract($closest_root, 1);
				
				$division = $this->execute_divide($sub_remainder, $closest_root);
				$sub_whole = $this->result($division['value'], $closest_root);
				$sub_remainder = $this->subtract($sub_remainder, $sub_whole);
				
				$row_count = $this->add($row_count, $division['value']);
				if($row_count == $value) {
					return true;	
				}
				$whole = $this->result($row_count, $closest_root);
				if($sub_remainder == 0) {
					return false;	
				}
				if(($this->even($sub_remainder) && $this->even($row_count)) || ($this->even($sub_remainder) && $this->even($closest_root)) || ($this->even($sub_remainder) && $this->even($whole))) {
					return false;	
				}
				if($row_count == $sub_remainder) {
					return false;	
				}
			}
			$continue = false;
			$sub_remainder_divided = $this->execute_divide($sub_remainder, $row_count)['remainder'];
			$division_shortened = $this->execute_shorten_fraction($sub_remainder_divided);
			if($sub_remainder_divided != $division_shortened) {
				return false;	
			} else {
				$continue = true;	
				$sub_remainder = $this->add($row_count, $sub_remainder);	
				
				$closest_root = $this->subtract($closest_root, 1);
				
				$division = $this->execute_divide($sub_remainder, $closest_root);
				$sub_whole = $this->result($division['value'], $closest_root);
				$sub_remainder = $this->subtract($sub_remainder, $sub_whole);
				
				$row_count = $this->add($row_count, $division['value']);
				
				$whole = $this->result($row_count, $closest_root);
				
				if($sub_remainder == 0) {
					return false;	
				}
				if(($this->even($sub_remainder) && $this->even($row_count)) || ($this->even($sub_remainder) && $this->even($closest_root)) || ($this->even($sub_remainder) && $this->even($whole))) {
					return false;	
				}
				if($row_count == $sub_remainder) {
					return false;	
				}
			}
		return true;
	}
	
	private function prime_triangle($value) {
		$closest_root = $this->prime_root_result[$value];
		$squared = $this->prime_root_squared[$value];
		
		$subtraction = $this->subtract($value, $squared);
		$division = $this->ceil($this->execute_divide($subtraction, $closest_root));
		
		$first_row = $this->add($closest_root, $division);
		if(!$this->even($first_row)) {
			return false;	
		}
		
		$first_row_squared = $this->execute_power_whole($first_row, 2);
		$half = $this->execute_divide($first_row_squared, 2)['value'];
		$vertex = $this->execute_divide($first_row, 2)['value'];
		$half = $this->add($half, $vertex);
		
		if($half == $value) {
			return true;	
		}
		return false;
		
		$row = $this->subtract($first_row, 1);
		$total = $this->add($first_row, $row);
		while($row > 0 && $total <= $value) {
			$row = $this->subtract($row, 1);
			$total = $this->add($total, $row);
		}
		if($total == $value) {
			return true;	
		}
		return false;
	}
	
	private $prime_sub_values = array();
	
	private function prime_row_offset($value) {
		$closest_root = $this->prime_root_result[$value];
		$squared = $this->prime_root_squared[$value];
		
		$offset = $this->subtract($value, $squared);
		
		
		$division = $this->execute_divide($offset, $closest_root);
		$mult = $this->result($division['value'], $closest_root);
		
		
		
		$rows = $this->subtract($closest_root, $this->subtract($offset, $mult));
		
		if($rows == 0) {
			return false;
		} else if($rows == 1) {
			return true;	
		}
		
		while($this->larger($rows, 1)) {
			$columns = $this->execute_divide($value, $rows);
			$remainder = $this->fraction_values($columns['remainder'])[0];
			
			
			
			$sub_column = $this->absolute($this->subtract($columns['value'], $rows));
			
			while($sub_column > 1) {
				if($sub_column != $value && $sub_column != 1) {
					$sub_division = $this->execute_divide($value, $sub_column);
					$sub_remainder = $this->fraction_values($sub_division['remainder'])[0];
					if($sub_remainder == 0) {
						return false;	
					}
					$sub_sub_column = $this->subtract($sub_column, $sub_remainder);
					
					if($sub_sub_column != 1 && $sub_sub_column != $value) {
						$sub_sub_division = $this->execute_divide($value, $sub_sub_column);
						$sub_sub_remainder = $this->fraction_values($sub_sub_division['remainder'])[0];
						if($sub_sub_remainder == 0) {
							return false;
						}
					}
					$sub_sub_column = $this->add($sub_column, $sub_remainder);
					
					if($sub_sub_column != 1 && $sub_sub_column != $value) {
						$sub_sub_division = $this->execute_divide($value, $sub_sub_column);
						$sub_sub_remainder = $this->fraction_values($sub_sub_division['remainder'])[0];
						if($sub_sub_remainder == 0) {
							return false;
						}
					}
					$sub_sub_column = $this->absolute($this->subtract($sub_division['value'], $sub_remainder));
					
					if($sub_sub_column != 1 && $sub_sub_column != $value && $sub_sub_column != 0) {
						$sub_sub_division = $this->execute_divide($value, $sub_sub_column);
						$sub_sub_remainder = $this->fraction_values($sub_sub_division['remainder'])[0];
						if($sub_sub_remainder == 0) {
							return false;
						}
					}
					
					
					$sub_column = $sub_remainder;
				}
			}			
			if($remainder == 0) {
				return false;	
			} else if($remainder == 1) { 				
				return true;	
			}
			$rows = $remainder;
		}
		
		
		
	}
	
	private function prime_row_offset_aux($value) {
		$closest_root = $this->prime_root_result[$value];
		$squared = $this->prime_root_squared[$value];
		
		$offset = $this->subtract($value, $squared);
		
		
		$division = $this->execute_divide($offset, $closest_root);
		$mult = $this->result($division['value'], $closest_root);
		
		
		
		$offset_remainder = $this->subtract($offset, $mult);
		$rows = $this->subtract($closest_root, $offset_remainder);
		$rows = $this->absolute($this->subtract($offset_remainder, $rows));
		
		if($rows == 0) {
			return false;
		} else if($rows == "1") {
			return true;	
		}
		while($this->larger($rows, "1")) {
			$columns = $this->execute_divide($value, $rows);
			$remainder = $this->fraction_values($columns['remainder'])[0];
			
			
			
			
			if($remainder == "0") {
				return false;	
			} else if($remainder == "1") { 				
				return true;	
			}
			$rows = $remainder;
		}
		
		
		
	}
	
	private function prime_row_offset_aux_alt($value) {
		$closest_root = $this->prime_root_result[$value];
		$squared = $this->prime_root_squared[$value];
		
		$offset = $this->subtract($value, $squared);
		
		
		$division = $this->execute_divide($offset, $closest_root);
		$mult = $this->result($division['value'], $closest_root);
		
		
		
		$rows = $this->subtract($closest_root, $this->subtract($offset, $mult));
		$rows = $this->add($rows, $this->result($closest_root, 2));
		
		
		
		while($this->larger($rows, 1)) {
			$columns = $this->execute_divide($value, $rows);
			$remainder = $this->fraction_values($columns['remainder'])[0];
			
			
			
			$sub_column = $this->subtract($rows, $remainder);
			while($sub_column > 1) {
				if($sub_column != $value && $sub_column != 1) {
					$sub_division = $this->execute_divide($value, $sub_column);
					$sub_remainder = $this->fraction_values($sub_division['remainder'])[0];
					if($sub_remainder == 0) {
						return false;	
					}
					$sub_sub_column = $this->subtract($sub_column, $sub_remainder);
					if($sub_sub_column != 1 && $sub_sub_column != $value) {
						$sub_sub_division = $this->execute_divide($value, $sub_sub_column);
						$sub_sub_remainder = $this->fraction_values($sub_sub_division['remainder'])[0];
						if($sub_sub_remainder == 0) {
							return false;
						}
					}
					$sub_sub_column = $this->add($sub_column, $sub_remainder);
					if($sub_sub_column != 1 && $sub_sub_column != $value) {
						$sub_sub_division = $this->execute_divide($value, $sub_sub_column);
						$sub_sub_remainder = $this->fraction_values($sub_sub_division['remainder'])[0];
						if($sub_sub_remainder == 0) {
							return false;
						}
					}
					$sub_sub_column = $this->absolute($this->subtract($sub_division['value'], $sub_remainder));
					if($sub_sub_column != 1 && $sub_sub_column != $value && $sub_sub_column != 0) {
						$sub_sub_division = $this->execute_divide($value, $sub_sub_column);
						$sub_sub_remainder = $this->fraction_values($sub_sub_division['remainder'])[0];
						if($sub_sub_remainder == 0) {
							return false;
						}
					}
					
					
					$sub_column = $sub_remainder;
				}
			}
			
			
			if($remainder == 0) {
				return false;	
			} else if($remainder == 1) { 				
				return true;	
			}
			$rows = $remainder;
		}
		
		
		
	}
	
	private function prime_fold($value) {
		$closest_root = $this->prime_root_result[$value];
		$squared = $this->prime_root_squared[$value];
		
		$squared_mid_point = $closest_root;		if($this->larger($squared_mid_point, $value)) {
			$squared_mid_point = $this->subtract($value, 1);	
		}
		$remainder = $this->subtract($value, $squared);
		$division = $this->execute_divide($remainder, $closest_root);
		$sub_whole = $this->result($division['value'], $closest_root);
		$remainder = $this->subtract($remainder, $sub_whole);	
		$row_count = $this->add(1, $division['value']);
		
		$closest_root = $this->subtract($closest_root, $remainder);
		$row_count = $this->add($row_count, 1);
		
		$partition = $this->execute_divide($closest_root, $row_count);
		$partition_fill = $this->result($row_count, $partition['value']);
		$partition_remainder = $this->subtract($closest_root, $partition_fill);
		
		$partition_remainder_column = $this->add($remainder, $partition['value']);
		$result = $this->result($partition_remainder, $partition_remainder_column);
		$alternative_result = $this->result($row_count, $closest_root);
		$alternative_result = $this->add($alternative_result, $partition_fill);
		$alternative_result = $this->add($alternative_result, $partition_remainder);
		$alternative_result = $this->absolute($this->subtract($alternative_result, $result));
		$result = $this->absolute($result);
		if($result != $value && $result != 1 && $result != 0) {
			$division = $this->execute_divide($value, $result);
			if($this->fraction_values($division['remainder'])[0] == 0) {
				return false;	
			}
		}
		if($alternative_result != $value && $alternative_result != 1 && $alternative_result != 0) {
			$division = $this->execute_divide($value, $alternative_result);
			if($this->fraction_values($division['remainder'])[0] == 0) {
				return false;	
			}
		}
		return true;
	}
	
	private function prime_divider($value) {
		$digit_sum = $this->digit_sum($value);
		
		$division = $this->execute_divide($value, $digit_sum);
		$result_a = $this->round($division);
		var_dump("val: ".$value."res: ".$result_a);
		$modulus = $this->modulus($value, $digit_sum);
		var_dump($modulus);
		if($this->prime($result_a)) { 			
			return true;	
		}
				
		

		return false;
	}
	
	private function prime_divider_aux($value) {
		$digit_sum = $this->digit_sum($value);
		$final_sum = $this->final_digit_sum($digit_sum);
		
		$modulus = $this->modulus($value, $final_sum);
		if($modulus == 0 && $final_sum != 1) {
			return false;	
		}
		if($this->prime($modulus) && $this->prime($digit_sum)) { 			
			return true;	
		}
		$modexp = $this->modexp($value, $final_sum, $digit_sum);
		if($this->prime($modexp)) {
			return true;	
		}
		
	
		$first_modulus = $this->modulus($value, $digit_sum);
		if($this->prime($first_modulus)) {
			return true;	
		} else {
			
		}
		echo "val: ".$value."\n";
		var_dump($digit_sum);
		var_dump($final_sum);
		var_dump($modulus);
		var_dump($modexp);
		var_dump($first_modulus);
		$digit_sum_modulus_modulus = $this->modulus($digit_sum, $modulus);
		$digit_sum_first_modulus_modulus = $this->modulus($digit_sum, $first_modulus);
		var_dump($digit_sum_modulus_modulus);
		var_dump($digit_sum_first_modulus_modulus);
		if($this->prime($digit_sum_modulus_modulus) && $this->prime($digit_sum_first_modulus_modulus)) {
			return true;	
		}
		if($final_sum == 1) {
			return true;	
		}
		$second_modulus = $this->modulus($value, $first_modulus);
		if($this->prime($second_modulus)) {
			return true;	
		}
		$digit_sum_modulus = $this->digit_sum($modulus);
		$third_modulus = $this->modulus($value, $digit_sum_modulus);
		if($this->prime($third_modulus)) {
			return true;	
		}
		return false;
	}
	
	private function prime_root_auxillary_offset($value) {
		$closest_root = $this->prime_root_result[$value];
		$squared = $this->prime_root_squared[$value];
		
		$squared_mid_point = $closest_root;		if($this->larger($squared_mid_point, $value)) {
			$squared_mid_point = $this->subtract($value, 1);	
		}
		$remainder = $this->subtract($value, $squared);
		$division = $this->execute_divide($remainder, $closest_root);
		$sub_whole = $this->result($division['value'], $closest_root);
		$remainder = $this->subtract($remainder, $sub_whole);
		
		if($this->prime($remainder) && $remainder != 1) {
			$sub_division = $this->execute_divide($closest_root, $remainder);
			if($this->fraction_values($sub_division['remainder'])[0] == 0) {
				return true;	
			}
		}
		$fraction = $remainder."/".$closest_root;
		
		
		
		$fraction_values = $this->fraction_values($fraction);
		$remainder = $fraction_values[0];
		$closest_root = $fraction_values[1];
		
		
		
		$row_count = $this->add($closest_root, $division['value']);
		$whole = $this->result($row_count, $closest_root);	
		$sub_remainder = $remainder;
		
		$prime_third_root = $this->square_root($this->prime_second_root[$value]);
		if($prime_third_root === false) {
			$prime_third_root = $this->root_closest_result;	
		}
		$prime_third_root = $this->add($prime_third_root, 1);
		$max_keys = $this->result(2, $prime_third_root);
		
		while($this->larger($closest_root, $prime_third_root, false)) { 			
			if($row_count == $sub_remainder) {
				return false;	
			}
			
			if(($this->even($sub_remainder) && $this->even($row_count)) || ($this->even($sub_remainder) && $this->even($closest_root)) || ($this->even($sub_remainder) && $this->even($whole))) {
				return false;	
			} else {
				
			}
			
			
			if(!$this->even($sub_remainder) && !$this->even($closest_root) && !$this->even($row_count)  && !$this->even($whole)) { 				
				if($sub_remainder != 0 && $row_count != $sub_remainder) {
					return true;	
				}
			}
			
			
			
			$addition = $row_count;			
			if($sub_remainder == 0) {
				return false;	
			}
			$sub_remainder_subtraction = $this->subtract($closest_root, $sub_remainder);
			
			
			
			$sub_remainder_subtraction = $this->subtract($sub_remainder_subtraction, 1);
			$sub_remainder = $this->subtract($addition, $sub_remainder_subtraction);
			
			$closest_root = $this->subtract($closest_root, 1);
			$row_count_addition = $this->execute_divide($sub_remainder, $closest_root)['value'];
			
			$sub_remainder_subtraction_value = $this->result($closest_root, $row_count_addition);
			$sub_remainder = $this->subtract($sub_remainder, $sub_remainder_subtraction_value);
			
			$row_count_addition = $this->add($row_count_addition, 1);
			
			$row_count = $this->add($row_count, $row_count_addition);
			
			$whole = $this->result($row_count, $closest_root);
		}
		return true;
		
	}
	
	private function prime_root_auxillary_secondary($value) {
		
		$value_digit_sum = $this->digit_sum($value);
		$value_modulus = $this->modulus($value, $value_digit_sum);
		
		

		$valid = true;
		return $this->prime_root_auxillary_secondary_sub($value);
		if($valid) {
			if($this->prime($value_modulus)) {
				var_dump("even ".$value);
				return $this->prime_root_auxillary_secondary_sub_even($value);	
			} else {
				return $this->prime_root_auxillary_secondary_sub($value);
				if($this->prime($value_digit_sum)) {
					$second_modulus = $this->modulus($value_digit_sum, $value_modulus);
					if(!$this->even($second_modulus)) {
						var_dump("divider ".$value);
						
							return $this->prime_root_auxillary_secondary_sub_even($value);		
					} else {						var_dump("even ".$value);
						return $this->prime_root_auxillary_secondary_sub_even($value);	
					}
				} else {
					
					var_dump("prime ".$value);
					return $this->prime_root_auxillary_secondary_sub_prime($value);
				}
			}
		}
		return false;
	}
	
	function is_zero($value) {
		if($value == 0) {
			return true;	
		}
		return false;
	}
	
	private function prime_root_alternating($value) {
		$prime_detection = new prime_detection($value, $this);
		return $prime_detection->root_alternating($this->prime_second_root[$value]);	
	}
		
	private function prime_root_auxillary_fast($value) {
		$closest_root = $this->prime_root_result[$value];
		
		$squared = $this->prime_root_squared[$value];
		
		$squared_mid_point = $closest_root;
		if($this->larger($squared_mid_point, $value)) {
			$squared_mid_point = $this->subtract($value, 1);	
		}
		$remainder = $this->subtract($value, $squared);
		$division = $this->execute_divide($remainder, $closest_root);
		$sub_whole = $this->result($division['value'], $closest_root);
		$sub_remainder = $this->subtract($remainder, $sub_whole);
		
		if($sub_remainder == 0) {
			return false;	
		}
		
		
		
		
		$row_count = $this->add($closest_root, $division['value']);
		$whole = $this->result($row_count, $closest_root);
		
		$sub_remainders = array();
		
		$prime_third_root = $this->root($closest_root, 3);
		if($prime_third_root === false) {
			$prime_third_root = $this->root_closest_result;	
		}
		$prime_third_root = $this->add($prime_third_root, 1);
		$max_keys = $this->result(2, $prime_third_root);
		$sub_remainder_subtraction = 0;
		
		$sub_subtraction_value = NULL;
		$last_row_count_pseudo_value = NULL;
		$whole = $this->result($row_count, $closest_root);
				
		
		while($this->larger($closest_root, $prime_third_root, false)) { 
			if($row_count == $sub_remainder) {
				return false;	
			}
			
			
			
			if(($this->even($sub_remainder) && $this->even($row_count)) || ($this->even($sub_remainder) && $this->even($closest_root)) || ($this->even($sub_remainder) && $this->even($whole))) {
				return false;	
			} else {
			}
			
			
			$addition = $row_count;			
			if($sub_remainder == 0) {
				return false;	
			}
			$sub_remainder_subtraction = $this->subtract($closest_root, $sub_remainder);
			$sub_remainder_value = $sub_remainder_subtraction;
			
		
			$sub_remainder_subtraction = $this->subtract($sub_remainder_subtraction, 1);
			$sub_remainder = $this->subtract($addition, $sub_remainder_subtraction);
			
			$sub_remainder_unaltered = $sub_remainder;
			
			
			$closest_root = $this->subtract($closest_root, 1);
			
			
			$row_count_addition = $this->division->fast_floor_divide($sub_remainder, $closest_root);
			
			
			$sub_remainder_subtraction_value = $this->result($closest_root, $row_count_addition);
			
			
			$sub_remainder = $this->subtract($sub_remainder, $sub_remainder_subtraction_value);
			
			$row_count_addition = $this->add($row_count_addition, 1);
			
			$row_count = $this->add($row_count, $row_count_addition);
			
			$whole = $this->subtract($value, $sub_remainder);
			
			$sub_subtraction_value = $sub_remainder_subtraction_value;
		}
		return true;
	}
						
	private function prime_root_auxillary($value) {
		$closest_root = $this->prime_root_result[$value];
		
		
		$squared = $this->prime_root_squared[$value];
		
		$squared_mid_point = $closest_root;		
		if($this->larger($squared_mid_point, $value)) {
			$squared_mid_point = $this->subtract($value, 1);	
		}
		$remainder = $this->subtract($value, $squared);
		$division = $this->execute_divide($remainder, $closest_root, false, false);
		$sub_whole = $this->result($division['value'], $closest_root);
		$sub_remainder = $this->subtract($remainder, $sub_whole);
		
		if($sub_remainder == 0) {
			return false;	
		}
		
		$row_count = $this->add($closest_root, $division['value']);
		$whole = $this->result($row_count, $closest_root);
		
		$sub_remainders = array();
		
		$prime_third_root = $this->root($this->prime_second_root[$value], 2);
		if($prime_third_root === false) {
			$prime_third_root = $this->root_closest_result;	
		}
		$prime_third_root = $this->add($prime_third_root, 1);
		$max_keys = $this->result(2, $prime_third_root);		
		$sub_remainder_subtraction = 0;
		
		
		while($this->larger($closest_root, $prime_third_root, false)) { 			
			if($row_count == $sub_remainder) {
				return false;	
			}
			
			if(($this->even($sub_remainder) && $this->even($row_count)) || ($this->even($sub_remainder) && $this->even($closest_root)) || ($this->even($sub_remainder) && $this->even($whole))) {
				return false;	
			} else {
				
			}
			
			
			if(!$this->even($sub_remainder) && !$this->even($closest_root) && !$this->even($row_count)  && !$this->even($whole)) { 				
				if($sub_remainder != 0 && $row_count != $sub_remainder) {
					return true;	
				}
			}
			
			
			
			
			$addition = $row_count;			
			if($sub_remainder == 0) {
				return false;	
			}
			$sub_remainder_subtraction = $this->subtract($closest_root, $sub_remainder);
			
			
			
			$sub_remainder_subtraction = $this->subtract($sub_remainder_subtraction, 1);
			$sub_remainder = $this->subtract($addition, $sub_remainder_subtraction);
			
			
			
			
			
			$sub_remainder_unaltered = $sub_remainder;
			
			
			$closest_root = $this->subtract($closest_root, 1);
			
			
			$row_count_addition = $this->division->fast_floor_divide($sub_remainder, $closest_root);			
			
			
			
			$sub_remainder_subtraction_value = $this->result($closest_root, $row_count_addition);
			
			$sub_remainder = $this->subtract($sub_remainder, $sub_remainder_subtraction_value);
			
			$row_count_addition = $this->add($row_count_addition, 1);
			
			$row_count = $this->add($row_count, $row_count_addition);
			
			$whole = $this->result($row_count, $closest_root);
		}
		return true;
	}
	
	private function prime_root_auxillary_auxillary($value) {
		$closest_root = $this->prime_root_result[$value];
		
		
		$squared = $this->prime_root_squared[$value];
		
		$squared_mid_point = $closest_root;		if($this->larger($squared_mid_point, $value)) {
			$squared_mid_point = $this->subtract($value, 1);	
		}
		$remainder = $this->subtract($value, $squared);
		$division = $this->execute_divide($remainder, $closest_root);
		$sub_whole = $this->result($division['value'], $closest_root);
		$sub_remainder = $this->subtract($remainder, $sub_whole);
		
		if($sub_remainder == 0) {
			return false;	
		}
		
		$row_count = $this->add($closest_root, $division['value']);
		$whole = $this->result($row_count, $closest_root);
		
		$sub_remainders = array();
		
		$prime_third_root = $this->square_root($this->prime_second_root[$value]);
		if($prime_third_root === false) {
			$prime_third_root = $this->root_closest_result;	
		}
		$prime_third_root = $this->add($prime_third_root, 1);
		$max_keys = $this->result(2, $prime_third_root);		
		while($this->larger($closest_root, $prime_third_root, false)) { 			
			if($row_count == $sub_remainder) {
				return false;	
			}
			
			if(($this->even($sub_remainder) && $this->even($row_count)) || ($this->even($sub_remainder) && $this->even($closest_root)) || ($this->even($sub_remainder) && $this->even($whole))) {
				return false;	
			} else {
				
			}
			
			
			if(!$this->even($sub_remainder) && !$this->even($closest_root) && !$this->even($row_count)  && !$this->even($whole)) { 				
				if($sub_remainder != 0 && $row_count != $sub_remainder) {
					return true;	
				}
			}
			
			
			
			$addition = $row_count;			
			if($sub_remainder == 0) {
				return false;	
			}
			$sub_remainder_subtraction = $this->subtract($closest_root, $sub_remainder);
			
			
			
			$sub_remainder_subtraction = $this->subtract($sub_remainder_subtraction, 1);
			$sub_remainder = $this->subtract($addition, $sub_remainder_subtraction);
			
			
			
			
			
			
			$sub_remainder_unaltered = $sub_remainder;
			
			
			$closest_root = $this->subtract($closest_root, 1);
			$row_count_addition = $this->execute_divide($sub_remainder, $closest_root)['value'];
			
			
			$sub_remainder_subtraction_value = $this->result($closest_root, $row_count_addition);
			
			$sub_remainder = $this->subtract($sub_remainder, $sub_remainder_subtraction_value);
			
			$row_count_addition = $this->add($row_count_addition, 1);
			
			$row_count = $this->add($row_count, $row_count_addition);
			
			$whole = $this->result($row_count, $closest_root);
			
			
		}
		return true;
	}
		
	private function prime_verification($value) {
		$split = $this->execute_divide($value, 2)['value'];
		
		
		$select_amount = 1;
		$select_remainder = $split;
		$select_count = 0;
		
		
		$row_count = 2;
		
		
		
		$select_count = $this->execute_divide($this->subtract($split, 2), 3)['value'];
		$select_amount = $this->result($select_count, 2);
		$select_amount = $this->add($select_amount, 1);
		
		
		$valid_select_amount = $select_amount;
		$valid_select_count = $select_count;
		
		
		$last_split = $split;
		$last_select_amount = $valid_select_amount;
		$select_remainder = $this->subtract($split, $valid_select_count);
		if($valid_select_amount == $select_remainder && $valid_select_amount > 1) {
			
			return false;	
		}
		while($last_split > $row_count) {
			$last_split = $split;
			$last_row_count = $row_count;
			$split = $select_remainder;
			
			$whole = $this->result($split, $row_count);
			
			
			
			$split_interval = $this->subtract($split, $last_select_amount);
			
			
			
			if($this->even($whole) && $this->even($last_select_amount)) {
				return false;
			} else if($this->even($whole) && !$this->even($last_select_amount)) {
				
				return true;
			} 
			
			
			$select_count = $this->subtract($split, $last_select_amount);
			
			
			$select_amount = $this->result($select_count, $row_count);
			
			$select_remainder = $this->subtract($split, $select_count);
			
			
			
			
			if($last_split == $row_count || $select_amount < 0) {
				return false;
			}
			if($select_amount == 0) {
				return false;	
			}
			if($select_amount == $select_remainder) {
				return false;	
			}
			
			$valid_select_count = $select_count;
			$valid_select_amount = $select_amount;
			
			$last_remainder = $this->subtract($split, $last_select_amount);
			$last_select_amount = $this->subtract($select_amount, $last_remainder);
			if($last_select_amount == 0) {
				return false;	
			}
			if($last_select_amount > 0) {			
				$row_count = $this->add($row_count, 1);	
			}
			
			if($row_count > $value) {
				return false;	
			}
			
		}
		return true;
	}
	
	private function prime_verification_aux($value) {
		$split = $this->execute_divide($value, 2)['value'];
		
		
		$select_amount = 1;
		$select_remainder = $split;
		$select_count = 0;
		
		$valid_select_amount = $select_amount;
		$valid_select_count = $select_count;
		
		$row_count = 2;
		
		while($this->larger($select_remainder, $select_amount, true)) {
			$valid_select_count = $select_count;
			
			$valid_select_amount = $select_amount;	
			$select_count = $this->add($select_count, 1);
			$select_amount = $this->result($select_count, 2);
			$select_amount = $this->add($select_amount, 1);
			$select_remainder = $this->subtract($split, $select_count);
			
		}
		
		
		$last_split = $split;
		$last_select_amount = $valid_select_amount;
		$select_remainder = $this->subtract($split, $valid_select_count);
		if($valid_select_amount == $select_remainder && $valid_select_amount > 1) {
			
			return false;	
		}
		while($last_split > $row_count) {
			$last_split = $split;
			$last_row_count = $row_count;
			$split = $select_remainder;
			
			$whole = $this->result($split, $row_count);
			
			
			$split_interval = $this->subtract($split, $last_select_amount);
			
			
			
			if($this->even($whole) && $this->even($last_select_amount)) {
				return false;
			} else if($this->even($whole) && !$this->even($last_select_amount)) {
				if($split_interval == 1) {
					return true;	
				}
				$sub_verification = $this->prime_verification($last_select_amount);
				if($sub_verification) {
					return true;	
				}
			} 
			
			
			$select_count = $this->subtract($split, $last_select_amount);
			
			
			$select_amount = $this->result($select_count, $row_count);
			
			$select_remainder = $this->subtract($split, $select_count);
			
			
			
			
			if($last_split == $row_count || $select_amount < 0) {
				return false;
			}
			if($select_amount == 0) {
				return false;	
			}
			if($select_amount == $select_remainder) {
				return false;	
			}
			
			$valid_select_count = $select_count;
			$valid_select_amount = $select_amount;
			
			$last_remainder = $this->subtract($split, $last_select_amount);
			$last_select_amount = $this->subtract($select_amount, $last_remainder);
			if($last_select_amount == 0) {
				return false;	
			}
			if($last_select_amount > 0) {			
				$row_count = $this->add($row_count, 1);	
			}
			
			if($row_count > $value) {
			}
		}
		return true;
	}
	
	function mod_add($a, $b, $n) {
		$sum = $this->add($a, $b);
		return $this->modulus($sum, $n);	
	}
	
	function mod_mult($a, $b, $n) {
		if($this->larger($a, $n)) {
			$a = $this->modulus($a, $n);	
		}
		if($this->larger($b, $n)) {
			$b = $this->modulus($b, $n);
		}
		$multiplication = $this->result($a, $b);
		return $this->modulus($multiplication, $n);
		
		
	}
	
	private function sprp($n, $a) {
		if($n == $a) {
			return true;	
		}
		$d = $this->subtract($n, 1);
		$s = 1;
		while($this->binary_and($d = $this->bit_shift_right($d, 1), 1) == 0) {
			$s = $this->add($s, 1);	
		}
		$b = $this->modexp($a, $d, $n);
		if($b == 1) {
			return true;	
		}
		$b_addition = $this->add($b, 1);
		if($b_addition == $n) {
			return true;	
		}
		while($this->larger($s, 1, false)) {
			$b = $this->mod_mult($b, $b, $n);
			if($this->add($b, 1)) {
				return true;	
			}
			$s = $this->subtract($s, 1);
		}
		return false;
	}
	
	function absolute_division($value, $divider) {
		if($this->larger($value, $divider))	{
			return $this->execute_divide($value, $divider);	
		}
		return $this->execute_divide($divider, $value);
	}
	
	private function primality_check_alt($value) {
		$digit_sum = $this->digit_sum($value);
		$modulus = $this->modulus($value, $digit_sum);
		if(!$this->prime($modulus) && $modulus != 0) {
			$division = $this->execute_divide($digit_sum, $modulus);
			if($this->fraction_values($division['remainder'])[0] == 0) {
				return false;	
			}
		}
		
		$d = $value;
		while($this->larger($d, $digit_sum, false)) {
			$modulus = $this->mod_mult($d, $digit_sum, $value);
			$d = $this->bit_shift_right($d, 1);
			if(!$this->prime($modulus) && $modulus != 0) {
				$division = $this->execute_divide($digit_sum, $modulus);
				if($this->fraction_values($division['remainder'])[0] == 0) {
					return false;	
				}
			}
			if($modulus == 0) {
			}
		}
		return true;
	}
		
	function binary_modulus($value, $divider) {
		return $this->binary_modulus->execute_modulus($value, $divider);	
	}
	
	function binary_and($a, $b, $to_decimal=true, $change_base=true) {
		if($change_base) {
			$a = $this->change_base($a, 2);
			$b = $this->change_base($b, 2);
		}
		$strlen_a = strlen($a);
		$strlen_b = strlen($b);
		$difference = $this->absolute($this->subtract($strlen_a, $strlen_b));
		if($strlen_a > $strlen_b) {	
			$b = $this->pad_zeros($b, $difference, true);	
		} else if($strlen_b > $strlen_a) {
			$a = $this->pad_zeros($a, $difference, true);
		}
		$digits_a = $this->get_digits($a);
		$digits_b = $this->get_digits($b);
		$result = "";
		foreach($digits_a as $key => $digit_a) {
			if(isset($digits_b[$key])) {
				$digit_b = $digits_b[$key];
				if($digit_a == 1 && $digit_b == 1) {
					$result .= "1";	
				} else {
					$result .= "0";	
				}
			}
		}
		$result = strrev($result);
		if($to_decimal) {
			$result = $this->change_base_decimal($result, 2);
		}
		return $result;
	}
	
	function binary_or($a, $b, $to_decimal=true, $change_base=true) {
		if($change_base) {
			$a = $this->change_base($a, 2);
			$b = $this->change_base($b, 2);
		}
		$strlen_a = strlen($a);
		$strlen_b = strlen($b);
		$difference = $this->absolute($this->subtract($strlen_a, $strlen_b));
		if($strlen_a > $strlen_b) {	
			$b = $this->pad_zeros($b, $difference, true);	
		} else if($strlen_b > $strlen_a) {
			$a = $this->pad_zeros($a, $difference, true);
		}
		$digits_a = $this->get_digits($a);
		$digits_b = $this->get_digits($b);
		$result = "";
		foreach($digits_a as $key => $digit_a) {
			if(isset($digits_b[$key])) {
				$digit_b = $digits_b[$key];
				if($digit_a == 1 || $digit_b == 1) {
					$result .= "1";	
				} else {
					$result .= "0";	
				}
			} else {
				$result .= $digit_a;	
			}
		}
		$result = strrev($result);
		if($to_decimal) {
			$result = $this->change_base_decimal($result, 2);
		}
		return $result;
	}
		
	function binary_xor($a, $b, $to_decimal=true, $change_base=true) {
		if($change_base) {
			$a = $this->change_base($a, 2);
			$b = $this->change_base($b, 2);
		}
		$strlen_a = strlen($a);
		$strlen_b = strlen($b);
		$difference = $this->absolute($this->subtract($strlen_a, $strlen_b));
		if($strlen_a > $strlen_b) {	
			$b = $this->pad_zeros($b, $difference, true);	
		} else if($strlen_b > $strlen_a) {
			$a = $this->pad_zeros($a, $difference, true);
		}
		$digits_a = $this->get_digits($a);
		$digits_b = $this->get_digits($b);
		$result = "";
		foreach($digits_a as $key => $digit_a) {
			if(isset($digits_b[$key])) {
				$digit_b = $digits_b[$key];
				if($digit_a == 1 && $digit_b == 1) {
					$result .= "0";
				} else if($digit_a == 1 || $digit_b == 1) {
					$result .= "1";	
				} else {
					$result .= "0";	
				}
			} else {
				$result .= $digit_a;	
			}
		}
		$result = strrev($result);
		if($to_decimal) {
			$result = $this->change_base_decimal($result, 2);
		}
		return $result;
	}
	
	function binary_and_inverse($value, $b) {
		$value = $this->binary_negation($value);
		$result = $this->binary_and($value, $b, false, false);	
		$result = $this->binary_negation($result);
		return $result;
	}
	
	function binary_negation($a) {
		$digits = str_split($a);
		
		$result = "";
		foreach($digits as $digit) {
			if($digit == "0") {
				$result .= "1";	
			} else {
				$result .= "0";	
			}
		}
		return $result;
	}
			
	function prime_sum($value, $primary=true) {
		$prime_values = [];
		$digits = str_split($value);
		$sum = 0;	
		foreach($digits as $digit) {
			$sum = $this->add($sum, $digit);		}
		
		if($primary) {
			$this->prime_sum_primary_value = $sum;
			$division = $this->execute_divide($sum, 3);
			if($this->fraction_values($division['remainder'])[0] == 0) {
				return array(false);	
			}
			
			
			
			$division = $this->execute_divide($value, $sum);			
			
			$sum_division = $this->execute_divide($sum, $value);			
			$division = $this->execute_divide($division, $sum_division);
			if($this->fraction_values($division['remainder'])[0] == 0) {
				return array(false);	
			}
		}
		
		
		if(strlen($sum) == 1) {
			$this->prime_sum_value = $sum;
			if(in_array($sum, $this->base_primes)) {
				return [true];	
			}
			return [false];
		}
		
		$this->prime_sum_last_value = $sum;
		
		$prime = $this->prime($sum);
		$prime_values[] = $prime;
			
		$prime_values = array_merge($prime_values, $this->prime_sum($sum, false));
		return $prime_values;
	}
	
	private function supplement_primes($sum) {
		$highest_value = $this->valid_prime[count($this->valid_prime)-1];
		if($this->larger($sum, $highest_value)) {
			$counter = $this->add($highest_value, 1);
			while($this->larger($sum, $counter)) {
				if($this->prime($counter)) {
					$this->log_prime($counter);	
				}
				$counter = $this->add($counter, 1);	
			}
		}
	}
	
	
	function generate_primes($finish) {
		$number = 2;
		$range = range(2, $finish);
		$primes = array_combine($range, $range);
		
		$result = array();
		foreach($range as $value) {
			$result[$value] = true;
		}
		
		while ($number*$number < $finish) {
			for ($i = $number; $i <= $finish; $i += $number) {
				if ($i == $number) {
					continue;
				}
				$result[$primes[$i]] = false;
			}
			$number = next($primes);
		}
		return $result;
	}
	
	private function list_primes($max) {
		$primes = array();
		$differences = array();
		$counter = 0;
		$sums = array();
		while($counter < $max) {
			$prime_test = $this->prime($counter);
			if($this->prime($counter)) {
				$primes[] = $counter;
				$digits = str_split($counter);
				$sum = 0;
				foreach($digits as $digit) {
					$sum += $digit;	
				}
				$sums[$sum] = 1;
				$slant = 1;
				if($counter > 0) {
					$diff = ($primes[count($primes)-2]-$counter);
					$differences[] = $diff;
					$slant = $diff/$differences[count($differences)-2];
				}
				if($prime_test) {
				} else {
				}
			} else {
				if($prime_test) {
				}
			}
			$counter++;	
		}
		$arr = $sums;
		$differences = array();
		$sums = array();
		foreach($arr as $value => $one) {
			$digits = str_split($value);
			$sum = 0;
			$diff = 0;
				$diff = $value - $sums[count($sums)-1];	
			$differences[] = $diff;
			foreach($digits as $digit) {	
				$sum += $digit;
			}
			$sums[] = $sum;
		}
	}
	
	function pi() {
		return $this->execute_divide("355", "113");	
	}
	
	function parse_value($value) {
		if(strpos($value, "|") !== false) {
			$split = explode("|", $value);
			$value = array(
				'value' => $split[0],	
				'remainder' => $split[1]
			);
			return $value;
		}
		return array(
			'value' => $value,
			'remainder' => '0/1'
		);
	}
	
	function result_value($value) {
		if(is_array($value)) {
			return $value['value']."|".$value['remainder'];	
		}
		return $value;
	}
	
	function trigonometry($slope, $cot_precise=false, $crd_precise=false) {
		$this->trigonometry->cot_precise = $cot_precise;
		$this->trigonometry->crd_precise = $crd_precise;
		return $this->trigonometry->point($slope);	
	}
	
	function trigonometry_radian($radian, $cot_precise=false, $crd_precise=false) {
		$this->trigonometry->cot_precise = $cot_precise;
		$this->trigonometry->crd_precise = $crd_precise;
		return $this->trigonometry->radian($radian);	
	}
	
	function sine($radian, $precision=NULL) {
		return $this->trigonometry->sine($radian, $precision);	
	}
	
	function cosine($radian, $precision=NULL) {
		return $this->trigonometry->cosine($radian, $precision);	
	}
	
	function arctan($radian, $precision=NULL) {
		return $this->trigonometry->arctan($radian, $precision);	
	}
	
	function arccot($radian, $precision=NULL) {
		return $this->trigonometry->arccot($radian, $precision);	
	}
	
	function arccos($radian, $precision=NULL) {
		return $this->trigonometry->arccos($radian, $precision);	
	}
	
	function arcsin($radian, $precision=NULL) {
		return $this->trigonometry->arcsin($radian, $precision);	
	}
}


?>