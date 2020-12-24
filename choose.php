<?

namespace NumEval;

class choose {
	
	private $items;
	private $eliminate_conditions;
	
	function __construct($items) {
		if($items != NULL) {
			$this->items = $items;
		}
		$this->result = array();
		$this->eliminate_conditions = array();
	}
	
	function set_elimination_condition($condition) {
		$this->eliminate_conditions[] = $condition;	
	}
	
	private $result = array();
	private $choose_count = 1;
		
	function choose($count, $items=NULL) {
		if($items === NULL) {
			$items = $this->items;
			$this->choose_count = $count;	
		}
		if($count == 0 || count($items) == 0) {
			return array();	
		}
		$count--;
		$return_results = array();
		foreach($items as $index => $item) {			
			$return_result = array($item);
			$eliminate = false;
			if(count($this->eliminate_conditions) > 0) {
				$eliminate = true;
				$elimination_index = $this->choose_count-1-$count;
				foreach($this->eliminate_conditions as $eliminate_condition) {
					$intermediate_result = $eliminate_condition($return_result, $elimination_index);
					if($intermediate_result) {
						$eliminate = false;	
					}
				}
			}
			if(!$eliminate) {
				$sub_items = $items;
				unset($sub_items[$index]);
				$results = $this->choose($count, $sub_items);
				foreach($results as $result) {
					$return_results[] = array_merge($return_result, $result);
				}
				if(count($results) == 0) {
					$return_results[] = $return_result;	
				}
			}
		}
		return $return_results;
	}
	
}

?>