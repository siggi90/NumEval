<?

namespace NumEval;


class quadratic_solver {
	
	private $a;
	private $b;
	private $c;
	
	private $evaluation;
	public $exact_result = false;
	
	function __construct($a, $b, $c, $evaluation) {
		$this->exact_result = false;
		$this->a = $a;
		$this->b = $b;
		$this->c = $c;
		$this->evaluation = $evaluation;	
	}
	
	function solve($precise=false) {
		$b_squared = $this->evaluation->execute_power_whole($this->b, 2)['value'];
		$root_value = $this->evaluation->result($this->a, $this->c);
		$root_value = $this->evaluation->result(4, $root_value);
		$root_value = $this->evaluation->subtract($b_squared, $root_value);
		$root_value_unaltered = $root_value;
		$root_value = $this->evaluation->root($root_value, 2);
		if($root_value === false) {
			if($precise === true) {
				return false;
			}
			if($precise === "-1") {
				$root_value = $this->evaluation->root_closest_result;
				$root_value = $this->evaluation->add($root_value, "1");
			} else {
				$root_value = $this->evaluation->execute_power(array('value' => $root_value_unaltered, 'remainder' => '0/1'), 2);
				if($root_value === NULL) {
					return false;	
				}
			}
		} else {
			$this->exact_result = true;	
		}
		$negative_b = $this->evaluation->negative_value($this->b);
		$numerator_a;
		$numerator_b;
		if(!is_array($root_value)) {
			$numerator_a = $this->evaluation->add($negative_b, $root_value);
			$numerator_b = $this->evaluation->subtract($negative_b, $root_value);
		} else {
			$numerator_a = $this->evaluation->add_total(array('value' => $negative_b, 'remainder' => '0/1'), $root_value);	
			$numerator_b = $this->evaluation->subtract_total(array('value' => $negative_b, 'remainder' => '0/1'), $root_value);
		}
		
		$denominator = $this->evaluation->result(2, $this->a);
		
		$value_a = $this->evaluation->execute_divide($numerator_a, $denominator);
		$value_b = $this->evaluation->execute_divide($numerator_b, $denominator);
		
		return array(
			$value_a,
			$value_b
		);
	}
	
	function solve_alt() {
		$b = $this->evaluation->execute_divide($this->b, $this->a);
		$c = $this->evaluation->execute_divide($this->c, $this->a);
		
		$x = $this->evaluation->execute_divide($b, "2");
		$x_squared = $this->evaluation->multiply_total($x, $x);
		$z = $this->evaluation->subtract_total($x_squared, $c);
		$z_root = $this->evaluation->root($z, "2");
		$z_root = $this->evaluation->root_closest_result;
		$z_root = array('value' => $z_root, 'remainder' => '0/1');
		
		return array(
			$this->evaluation->add_total($x, $z_root),
			$this->evaluation->subtract_total($x, $z_root),
		);	
	}
	
	function closest_integer_solution() {
		$b_squared = $this->evaluation->execute_power_whole($this->b, 2)['value'];
		$root_value = $this->evaluation->result($this->a, $this->c);
		$root_value = $this->evaluation->result(4, $root_value);
		$root_value = $this->evaluation->subtract($b_squared, $root_value);
		$root_value_unaltered = $root_value;
		
		$root_value = $this->evaluation->root($root_value, 2);
		$root_value = $this->evaluation->root_closest_result;
		$root_value = $this->evaluation->add($root_value, "1");
		
		$root_value_squared = $this->evaluation->result($root_value, $root_value);	
		
		$c = $this->evaluation->subtract($b_squared, $root_value_squared);
		
		$a4 = $this->evaluation->result("4", $this->a);
		
		$c = $this->evaluation->execute_divide($c, $a4);
		if($this->evaluation->fraction_values($c['remainder'])[0] == 0) {
			return $c['value'];	
		}
		return false;
	}
}

?>