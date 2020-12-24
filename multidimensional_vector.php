<?

namespace NumEval;

class multidimensional_vector {
	private $evaluation;
	
	function __construct($evaluation) {
		$this->evaluation = $evaluation;
	}
	
	function normalize_vector($v) {
		$result = array();
		$zero_vector = array();
		foreach($v as $v_value) {
			$zero_vector[] = array('value' => '0', 'remainder' => '0/1');	
		}
		$distance = $this->distance($v, $zero_vector);
		if($distance == 0) {
			return $v;	
		}
		foreach($v as $index => $value) {
			$result[$index] = $this->evaluation->execute_divide($value, $distance);
		}
		return $result;
	}
	
	function stretch_vector($u, $value) {
		$result = array();
		foreach($u as $index => $u_value) {
			$result[$index] = $this->evaluation->multiply_total($u_value, $value);	
		}
		return $result;
	}
	
	function distance($u, $v) {
		$squared = array('value' => '0', 'remainder' => '0/1');
		foreach($u as $index => $value_u) {
			$squared = $this->evaluation->add_total($squared, $this->evaluation->execute_power_whole($this->evaluation->subtract_total($value_u, $v[$index]), 2));
		}
		return $this->evaluation->execute_power($squared, 2);
	}
	
	function subtract_vector($u, $v) {
		$result = array();
		foreach($u as $index => $u_value) {
			$result[$index] = $this->evaluation->subtract_total($u_value, $v[$index]);	
		}
		return $result;
	}
	
	function add_vector($u, $v) {
		$result = array();
		foreach($u as $index => $u_value) {
			$result[$index] = $this->evaluation->add_total($u_value, $v[$index]);	
		}
		return $result;
	}
	
	function median_point($u, $v) {
		$result = array();
		foreach($u as $index => $u_value) {
			$result[$index] = $this->evaluation->execute_divide($this->evaluation->add_total($u_value, $v[$index]), 2);	
		}
		return $result;
	}
	
	function empty_vector($length) {
		$counter = 0;
		$vector = array();
		while($this->evaluation->larger($length, $counter, false)) {
			$vector[] = array('value' => '0', 'remainder' => '0/1');
			$counter = $this->evaluation->add($counter, 1);
		}
		return $vector;
	}
	
	function reverse_vector($v) {
		$result = array();
		foreach($v as $index => $value) {
			$result[$index] = $this->evaluation->negative_value($value);	
		}
		return $result;
	}
	
	function compare_vector($u, $v) {
		if(count($u) != count($v)) {
			return false;	
		}
		foreach($v as $index => $v_value) {
			if($v_value != $u[$index]) {
				return false;	
			}
		}
		return true;
	}
	
	function dot_product($u, $v) {
		$sum = array('value' => '0', 'remainder' => '0/1');
		foreach($u as $index => $u_value) {
			$multiplication = $this->evaluation->multiply_total($u_value, $v[$index]);
			$sum = $this->evaluation->add_total($sum, $multiplication);	
		}
		return $sum;
	}
}

?>