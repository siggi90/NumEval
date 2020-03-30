<?

namespace NumEval;

class arithematic {
	private $value = array(
						array(
							'3' => array(
								'value' => array('value' => '7', 'remainder' => '0/1')
							),
							'4' => array(
								'value' => array('value' => '7', 'remainder' => '0/1')
							),	
						),
						array(
							'1' => array(
								'value' => array('value '=> '1', 'remainder' => '0/1')
							)
						)
					);
					
	private $evaluation;
	
	function __construct($evaluation=NULL) {
		if($evaluation != NULL) {
			$this->evaluation = $evaluation;
		} else {
			$this->evaluation = new evaluation();	
		}
	}
	
	function result($terms_a, $terms_b) {
		foreach($terms_a as $index => $term_a) {
			foreach($terms_b as $b_index => $term_b) {
				foreach($term_a as $power => $value) {
					if(isset($term_b[$power])) {
						$terms_b[$b_index][$power] = $this->evaluation->multiply_total($term_a[$power], $term_b[$power]);	
					} else {
						$terms_b[$b_index][$power] = $term_a[$power];	
					}
				}
			}
		}
		return $terms_b;
	}
	
	function divide($terms_a, $terms_b) {
		foreach($terms_a as $index => $term_a) {
			foreach($terms_b as $b_index => $term_b) {
				foreach($term_a as $power => $value) {
					if(isset($term_b[$power])) {
						$terms_b[$b_index][$power] = $this->evaluation->execute_divide($term_a[$power], $term_b[$power]);	
					} else {
						$terms_b[$b_index][$power] = $term_a[$power];	
					}
				}
			}
		}
		return $terms_b;
	}
	
	function add($term_a, $term_b) {
		/*foreach($term_a as $power => $value) {
				
		}*/
		$result = array_merge($term_a, $term_b);
		$result = $this->simplify_terms($result);
		return $result;
	}
		
	function simplify_terms($terms) {
		$addition = array('value' => '0', 'remainder' => '0/1');
		foreach($terms as $index => $term) {
			if(count($term) == 1 && isset($term['1'])) {
				$addition = $this->evaluation->add_total($term['1'], $addition);
				unset($terms[$index]);
			}
		}
		$terms[] = array('1' => $addition );
		return $terms;
	}
	
	function power($terms, $power) {
		$alter_power = $power;
		$power_fraction = $this->evaluation->fraction_values($power['remainder']);
		$power_fraction[0] = $this->evaluation->add($power_fraction[0], $this->evaluation->result($power_fraction[1], $power['value']));
		
		echo "\n\npower\n";
		var_dump($power);
				
		$result = array();
		if(count($terms) > 1) {
			$resolution = $this->resolve($terms);	
			$resolution = $this->power($resolution, $power);
			return array('1' => $resolution);
		}
		
		if(count($terms) == 1) {
			foreach($terms as $term) {
				$addition = array();
				foreach($term as $power => $value) {
					if($power == 1) {
						$value_unaltered = $value;
						$set_power = $this->evaluation->result($power_fraction[1], $power);
						
						$value = $this->evaluation->execute_power_whole($value, $this->evaluation->fraction_values($alter_power['remainder'])[0]);
						$addition[$set_power] = $value;	
						
						if($alter_power['value'] > 0) {
							$power_multiplication = $this->evaluation->execute_power_whole($value_unaltered, $alter_power['value']);
							if(!isset($addition[1])) {
								$addition[1] = $power_multiplication;	
							} else {
								$addition[1] = $this->evaluation->multiply_total($addition[1], $power_multiplication);	
							}
						}
					} else {
						$set_power = $this->evaluation->result($power_fraction[1], $power);
						
						$value = $this->evaluation->execute_power_whole($value, $power_fraction[0]);
						$addition[$set_power] = $value;	
					}
				}
				$result[] = $addition;
			}
		}
		return $result;
	}
	
	function resolve($terms) {
		$addition = array('value' => '0', 'remainder' => '0/1');
		foreach($terms as $term) {
			$multiplication = array('value' => '1', 'remainder' => '0/1');
			foreach($term as $power => $value) {
				$intermediate_result = $this->evaluation->execute_power($value, $power);
				echo "intermediate_result:\n";
				var_dump($intermediate_result);
				$multiplication = $this->evaluation->multiply_total($multiplication, $intermediate_result);
			}
			$addition = $this->evaluation->add_total($multiplication, $addition);
		}
		return $addition;//array('1' => $addition);
	}
}


?>