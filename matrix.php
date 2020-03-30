<?

namespace NumEval;

class matrix {
	
	private $evaluation;
	
	function __construct($evaluation) {
		$this->evaluation = $evaluation;
	}
	
	function result($matrix_a, $matrix_b) {
		if(count($matrix_a[0]) != count($matrix_b)) {
			return false;	
		}
		$resulting_matrix = array();
		foreach($matrix_a as $row_a) {
			$row_result = array();
			$columns = $this->columns($matrix_b);
			foreach($columns as $column) {
				$intermediate_result = 0;
				foreach($column as $index => $column_value) {
					$multiplication = $this->evaluation->multiply_total($row_a[$index], $column_value);
					$intermediate_result = $this->evaluation->add_total($intermediate_result, $multiplication);
				}
				$row_result[] = $intermediate_result;
			}
			$resulting_matrix[] = $row_result;
		}
		return $resulting_matrix;
	}
	
	function add($matrix_a, $matrix_b) {
		$result = array();
		foreach($matrix_a as $row_index => $row) {
			foreach($row as $column_index => $column_value) {
				if(!isset($result[$row_index])) {
					$result[$row_index] = array();	
				}
				$result[$row_index][$column_index] = $this->evaluation->add_total($column_value, $matrix_b[$row_index][$column_index]);	
			}
		}
		return $result;
	}
	
	function subtraction($matrix_a, $matrix_b) {
		$result = array();
		foreach($matrix_a as $row_index => $row) {
			foreach($row as $column_index => $column_value) {
				if(!isset($result[$row_index])) {
					$result[$row_index] = array();	
				}
				$result[$row_index][$column_index] = $this->evaluation->subtract_total($column_value, $matrix_b[$row_index][$column_index]);	
			}
		}
		return $result;
	}
	
	function lengthen($matrix, $length) {
		foreach($matrix as $row_index => $row) {
			foreach($row as $column_index => $column_value) {	
				$matrix[$row_index][$column_index] = $this->evaluation->multiply_total($column_value, $length);
			}
		}
		return $matrix;
	}
	
	function row($matrix, $row_number) {
		return $matrix[$row_number];	
	}
	
	function column($matrix, $column_number) {
		$result = array();
		foreach($matrix as $key => $row) {
			$result[] = $row[$column_number];
		}
		return $result;
	}
	
	function columns($matrix) {
		$column_length = count($matrix[0]);
		$counter = 0;
		$columns = array();
		while($counter < $column_length) {
			$columns[] = $this->column($matrix, $counter);
			$counter++;
		}
		return $columns;
	}
	
	function transpose($matrix) {
		return $this->columns($matrix);	
	}
	
	/*function distance($matrix_a, $matrix_b) {
		if(count($matrix_a) == 1 && count($matrix_b) == 1) {
			$vector_a = $matrix_a[0];
			$vector_b = $matrix_b[0];	
		}
	}*/
	
	
}


?>