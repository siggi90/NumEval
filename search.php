<?

namespace NumEval;

class search {
	
	private $variables;
	private $constraints;
	private $statement;
	private $function;
	private $heuristic_function;
	private $target_value;
	
	private $previous_states;
	
	private $result;
	
	function __construct($variables, $alter_variables, $constraints, $function, $heuristic_function) {
		
		$this->function = array();
		$this->variables = $variables;
		$this->alter_variables = $alter_variables;
		$this->constraints = array('constraints' => $constraints);
		$this->function['function'] = $function;
		$this->function['heuristic_function'] = $heuristic_function;
		
		$initial_state = array(
			'variables' => $this->variables
		);
		
		$result = $this->execute_search($initial_state);
		$this->result = $result;
	}
	
	public function get_result() {
		return $this->result;	
	}
	
	function execute_search($state) {
		$this->previous_states[] = $state;
				
		if($this->function['function']($state['variables'])) { 			
			return $state;	
		}
		
		$successor_states = $this->successor_states($state);
		$min_value_state = NULL;
		$min_value = NULL;
		foreach($successor_states as $state) {
			$heuristic_value = $this->heuristic($state);
			if($min_value_state == NULL || abs($heuristic_value) < abs($min_value)) {
				$min_value_state = $state;	
				$min_value = $heuristic_value;
			}
		}
		
		return $this->execute_search($min_value_state);
	}
	
	function successor_states($state) {
		$successor_states = array();
		foreach($this->alter_variables as $variable => $value) {
			$increment_state = $state;
			$increment_state['variables'][$variable]++;	
			
			if(!in_array($increment_state, $this->previous_states)) {
				$successor_states[] = $increment_state;
				$this->previous_states[] = $increment_state;
			}
			
		}
		return $successor_states;
	}
	
	function check_constraints($state) {
		
		return $this->constraints['constraints']($state['variables']);
	}
	
	function heuristic($state) {
		$value = $this->function['heuristic_function']($state['variables']);
		return $value;
	}
}


?>