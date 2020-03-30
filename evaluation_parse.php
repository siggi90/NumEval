<?

namespace NumEval;
	
class evaluation_parse {
	public $evaluation;
	private $function;
	private $parse_error_variables;
	public $trigonometry;
	
	function __construct($function, $evaluation=NULL) {
		if($evaluation == NULL) {
			$this->evaluation = new evaluation();
		} else {
			$this->evaluation = $evaluation;	
		}
		
		$this->parse_error_variables = array();
		$function = $this->replace_decimals($function);
		$function = $this->insert_symbols($function);
		$function = $this->replace_subtraction($function);
		$this->function = $function;
		
	}
	
	function set_function($function) {
		$this->parse_error_variables = array();
		$function = $this->replace_decimals($function);
		$function = $this->insert_symbols($function);
		$function = $this->replace_subtraction($function);
		$this->function = $function;
	}
	
	function set_configuration($truncate_fractions_length=0, $logarithm_iteration_count=12, $root_fraction_precision=array('value' => '0', 'remainder' => '1/100'), $disable_built_in_approximation=false, $sine_precision=10, $set_continued_fraction_resolution_level_setting=12, $disable_exact_root_results=false) {
		$this->evaluation->set_configuration($truncate_fractions_length, $logarithm_iteration_count, $root_fraction_precision, $disable_built_in_approximation, $sine_precision, $set_continued_fraction_resolution_level_setting, $disable_exact_root_results);	
	}
	
	function count_paranthesis($function) {
		$digits = str_split($function);
		$paranthesis_count = 0;
		foreach($digits as $key => $digit) {
			if($digit == "(") {
				$paranthesis_count++;	
			} else if($digit == ")") {
				$paranthesis_count--;	
			}
		}
		if($paranthesis_count == 0) {
			return false;	
		}
		return true;
	}
	
	function paranthesis_operator($function, $operator) {
		$digits = str_split($function);
		$paranthesis_count = 0;
		$previous_paranthesis_count;
		$operator_position = array();
		
		$insert = true;
		$operator_end = false;
		$operator_position_insert = NULL;
		$operator_end_insert = NULL;
		foreach($digits as $key => $digit) {
			if($digit == '(') {
				$paranthesis_count++;	
			} else if($digit == ')') {
				$paranthesis_count--;
			} else if($this->is_symbol($digit) && $digit != '^') { 				$operator_position_insert = array('value' => $key, 'type' => 'insert');	
				if(count($operator_position) > 0) {
				}
				$insert = true;
			} 
			if($digit == '^') {
				$operator_end = true;
				$operator_position[] = $operator_position_insert;
				$operator_position_insert = NULL;
			}
			if($this->is_symbol($digit) && $operator_end && $paranthesis_count == 0) {
				$operator_end_insert = true;
				$operator_end = false;	
			}
			if($this->is_symbol($digit) && $operator_end_insert && $paranthesis_count == 0) {
				$position = $key;
				
				$operator_position[] = array('value' => $position, 'type' => 'insert');	
				$operator_end_insert = false;
			}
			
			if(!isset($previous_paranthesis_count)) {
				$previous_paranthesis_count = $paranthesis_count;
			}
			if($paranthesis_count != $previous_paranthesis_count && $insert) {
				$previous_paranthesis_count = $paranthesis_count;	
				$operator_position[] = array('value' => $key, 'type' => 'paranthesis', 'count' => $paranthesis_count);
				$insert = false;
				
			}
		}
		$operator_position[] = strlen($function);
		$operator_index = array();
		foreach($operator_position as $value) {
			$operator_index[$value['value']] = $value;	
		}
		foreach($digits as $key => $digit) {
			$value = '-';
			if(isset($operator_index[$key])) {
				if($operator_index[$key]['type'] == 'insert') {
					$value = '_';	
				} else if($operator_index[$key]['type'] == 'paranthesis') {
					$value = '|';	
				}
			}
		}
		$reverse_positions = array_reverse($operator_position);
		$insert_positions = $this->discover_paranthesis_positions($reverse_positions);
		
		
		foreach($insert_positions as $value) {
		}
		$result = "";
		$last_place;
		$interlope = 0;
		foreach($insert_positions as $index => $position) {
			$position = $position['position'];
			if($interlope == 1) {
				$position += 1;	
			}
			foreach($digits as $key => $digit) {
				if((!isset($last_place) || $key > $last_place) && $key < $position+1) { 					$result .= $digit;
				}
			}
			$last_place = $position;
			if($interlope == 0) {
				$result .= "(";
				$interlope = 1;
			} else {
				$result .= ")";	
				$interlope = 0;
			}
		}
		return $result;
	}
	
	function discover_paranthesis_positions($operator_position, $reverse=false) {
		$previous_insert;
		$successive_paranthesis;
		$operator;
		$par_count;
		$operator_position;
		$insert_position = array();
		foreach($operator_position as $position) {
			$cur_pos = $position['value'];
			if($position['type'] == 'operator') {
				$operator_position = $position;
			} else if($position['type'] == 'insert') {
					
			} else if($position['type'] == 'paranthesis') {
				$par_count = $position;
			}
			$cur_operator_count;
			$insert_pos;
			$addition = 1;
			
			$set_insert = false;
			$previous_sub_position;
			foreach($operator_position as $sub_position) {
				if(!isset($previous_insert) || $sub_position['value'] > $previous_insert) {
					if($sub_position['type'] == 'paranthesis' && $sub_position['value'] == ($cur_pos+$addition) && !isset($cur_operator_count)) {
						$cur_operator_count = $sub_position['count'];
					}
					
					if(isset($cur_operator_count) && $sub_position['type'] == 'paranthesis' && $cur_operator_count == $sub_position['count']) { 						$set_insert = true;
					}
					if((!isset($cur_operator_count) || $set_insert) && $sub_position['type'] == 'insert') {
						$insert_pos = $sub_position['value'];
					}
				}
				$previous_sub_position = $sub_position;
			}
			$insert_position[] = array('position' => $insert_pos, 'reverse' => $reverse);
			$previous_insert = $insert_pos;
		}
		return $insert_position;	
	}
	
	function paranthesis_operator_depr($function, $operator="^") {
		if(strpos($function, $operator) !== false) {
			$split = explode($operator, $function);
			$rev_count = 0;
			$paranthesis_count = 0;
			$complete = "";
			foreach($split as $key => $part) {
				$rev_digits = $this->evaluation->get_digits($part);
				$digits = str_split($part);
				$stop = false;
				$stop_index = count($digits);
				$append = "";
				if($key > 0) { 					foreach($digits as $index => $digit) {
						if($digit == "(") {
							$paranthesis_count++;
						} else if($digit == ")") {
							$paranthesis_count--;	
						}
						if($paranthesis_count == 0 && !is_numeric($digit) && !$stop) {
							$stop = true;
							$stop_index = $index;
							$append .= ")".$digit;
						} 
						if(!$stop) {
							$append .= $digit;	
						}
					}
					$stop_index = count($digits) - $stop_index - 1;
				}
				$prefix = "";
					$rev_count = 0;
					$stop = false;
					$prefix = "";
					foreach($rev_digits as $index => $digit) {
						if($digit == "(") {
							$paranthesis_count++;
						} else if($digit == ")") {
							$paranthesis_count--;	
						}
						if((($digit != ")" && $digit != "(" && !is_numeric($digit)) || ($index == count($digit)-1)) && !$stop) {
							$stop = true;
								$digit = "(".$digit;
							
						}
						if($index < $stop_index) {
							$prefix = $digit.$prefix;
						}
					}
				
				if($key > 0) {
					$complete .= $operator;
				}
				$complete .= $append.$prefix;
			}
			return $complete;
		} else {
			return $function;	
		}
	}
	
	function replace_subtraction($function) {
		if(strpos($function, "-") === false) {
			return $function;	
		}
		$split = explode("-", $function);
		$connect = "";
		$next_paranthesis_count = 0;
		foreach($split as $key => $part) {
			if($key > 0) {
				$digits = str_split($part);
				$append = "+(-";
				$paranthesis_count = $next_paranthesis_count;
				$next_paranthesis_count = 0;
				foreach($digits as $index => $digit) {
					if($digit == "(" && $paranthesis_count < 0) {
						$next_paranthesis_count++;
					} else if($digit == ")" && $paranthesis_count < 0) {
						$next_paranthesis_count--;	
					}
					if($digit == "(" && $paranthesis_count >= 0) {
						$paranthesis_count++;	
					} else if($digit == ")") {
						$paranthesis_count--;
					}
					if($paranthesis_count == 0 && $this->is_symbol($digit)) {
						$paranthesis_count--;
						$append .= ")".$digit;	
					} else {
						$append .= $digit;	
					}
					
					if($paranthesis_count == 0 && $index == (count($digits)-1)) {
						$append .= ")";
						$paranthesis_count--;	
					}
				}
				$connect .= $append;
			} else {
				$connect .= $part;	
			}
		}
		return $connect;
	}
	
	function insert_paranthesis($function) {
		if(strpos($function, "_") === false) {
			return $function;	
		}
		$split = explode("_", $function);
		$connect = "";
		$next_paranthesis_count = 0;
		foreach($split as $key => $part) {
			if($key > 0) {
				$digits = str_split($part);
				$append = "(_";
				$paranthesis_count = $next_paranthesis_count;
				$next_paranthesis_count = 0;
				foreach($digits as $index => $digit) {
					if($digit == "(" && $paranthesis_count < 0) {
						$next_paranthesis_count++;
					} else if($digit == ")" && $paranthesis_count < 0) {
						$next_paranthesis_count--;	
					}
					if($digit == "(" && $paranthesis_count > 0) {
						$paranthesis_count++;	
					} else if($digit == ")") {
						$paranthesis_count--;
					}
					if($paranthesis_count == 0 && $this->is_symbol($digit)) {
						$paranthesis_count = $paranthesis_count - 1;
						$append .= ")".$digit;	
					} else {
						$append .= $digit;	
					}
					if($paranthesis_count == 0 && $index == (count($digits)-1)) {
						$paranthesis_count--;
						$append .= ")";	
					}
				}
				$connect .= $append;
			} else {
				$connect .= $part;	
			}
		}
		return $connect;
	}
	
	function fill_paranthesis($count) {
		$counter = 0;
		$result = "";
		while($counter < $count) {
			$result .= ")";	
			$counter++;
		}
		return $result;
	}
	

	
	
	
	function parse_errors() {
		return $this->parse_error_variables;	
	}
	
	function is_symbol($c) {
		if(in_array($c, $this->symbols)) {
			return true;	
		}
		switch($c) {
			case '+':
			case '*':
			case '_':
			case '(':
			case ')':
			case '^':
			case '/':
			
			case '\'':
			return true;
				break;
			default:
				return false;
		}
	}
	
	function remove_whitespace($s) {
		$return_string = "";
		foreach(str_split($s) as $p) {
			if(trim($p) != "") {
				$return_string .= $p;
			}
		}            
		return $return_string;
	}
	
	function replace_constants($s) {
		$return_string = "";
		$count = 0;
		$parts = explode('e', $s);
		foreach($parts as $part) {
			$return_string .= $part + ($count < strlen($parts) - 1 ? exp() : "");
			$count++;
		}
		return $return_string;
	}
	
	private $variables;
	
	function is_variable($c) {
		foreach($variables as $v) {
			if($c == $v) {
				return true;
			}
		}
		return false;
	}
	
	function multiplication_paranthesis($s) {
		for($x = 0; $x<strlen($s); $x++) {
			$first_char = ($x > 0 ? $s[$x-1] : ' ');
			$second_char = $s[$x];
			if(($first_char == 'x' && $second_char == '(') || ((is_numeric($first_char) && $second_char=='x'))) {
								$s = substr_replace($s, "*", $x);
			}
		}
		return $s;
	}
	
	private $_parse_tree;
	
	function parse_tree() {
		return $this->_parse_tree;
	}
	
	function get_parse_string() {
		return $this->_parse_tree->get_parse_string();
	}
	
	
	
	private $symbols = array(
		'#',
		'@',
		'%',
		
		'a',
		'b',
		'd',
		'f',
		'g',
		'h',
	);
	
	function parse($x="0") {
		$value = 0;
		$counter = 0;
		$this->function = $this->remove_whitespace($this->function);
		$this->function = $this->multiplication_paranthesis($this->function);
		$func_str = $this->function;
		$token;
		$parse_tree = parse_object::init(NULL);
		$last_parse = NULL;
		$symbols = array();
		$next_symbol = '?';
		$sublevel = 0;
		while($counter < strlen($func_str)) {
			$token = $func_str[$counter];
			if($next_symbol != '?') {
				$symbols[] = $next_symbol;
				$next_symbol = '?';
			}
			$extended_nesting = false;
			if($counter == 0) {
				$parse_tree = $parse_tree->child();
				$parse_tree = $parse_tree->child();
			}
			if($token == '(') {
				$parse_tree = $parse_tree->child();
				$parse_tree->set_paranthesis();
				if(count($symbols) > 0) {
					$symbol = $this->pop($symbols);
					$parse_tree->set_symbol($symbol);
				}
				$parse_tree = $parse_tree->child();
			} else if($token == ')') {
				$parse_tree = ($parse_tree->get_parent() != NULL ? $parse_tree->get_parent() : $parse_tree);
				$parse_tree = ($parse_tree->get_parent() != NULL ? $parse_tree->get_parent() : $parse_tree);
			} else if(is_numeric($token) || $token == '.' || $token == "x") { 				if(count($symbols) > 0) {
					if($this->pop($symbols, false) == '+' || $this->pop($symbols, false) == '-') {
						$parse_tree = ($parse_tree->get_parent() != NULL ? $parse_tree->get_parent() : $parse_tree);
						$parse_tree = $parse_tree->child();
					} else if($this->pop($symbols, false) == '_') {
						$parse_tree = $parse_tree->child();
						$extended_nesting = true;
					}
				}
				if($counter-1 > 0) {
					if(!is_numeric($func_str[$counter-1])) {
						$parse_tree = $parse_tree->child();
					}
				}
				if(count($symbols) > 0) {
					$symbol = $this->pop($symbols);
					$parse_tree->set_symbol($symbol);
				}
				if($token == "x") {
					$token = $x;
				}
				$parse_tree->append_value($token);
				if($counter+1 <= (strlen($func_str) - 1)) {
					if(!is_numeric($func_str[$counter+1])) {
						$last_parse = $parse_tree;
						$parse_tree = ($parse_tree->get_parent() != NULL ? $parse_tree->get_parent() : $parse_tree);
						if($extended_nesting) {
							$parse_tree = ($parse_tree->get_parent() != NULL ? $parse_tree->get_parent() : $parse_tree);
						}
					}
				} else {
					$last_parse = $parse_tree;
					$parse_tree = ($parse_tree->get_parent() != NULL ? $parse_tree->get_parent() : $parse_tree);
					if($extended_nesting) {
						$parse_tree = ($parse_tree->get_parent() != NULL ? $parse_tree->get_parent() : $parse_tree);
					}
				}
			} else if($token == '+' || $token == '-' || $token == '*' || $token == '/' || $token == '\'' || $token == '_' || $token == '^' || $token == '#' || $token == '@' || $token == '%' || in_array($token, $this->symbols)) {
				$next_symbol = $token;
			} else {
			}
			$counter++;
		}
		while($parse_tree->get_parent() != NULL) {
			$parse_tree = ($parse_tree->get_parent() != NULL ? $parse_tree->get_parent() : $parse_tree);
		}
		$this->_parse_tree = $parse_tree;
		$parse_result = $this->evaluation->result_value($this->unfold($this->_parse_tree));
		$evaluation_error = $this->evaluation->pop_error();
		if($evaluation_error != NULL) {
			$this->parse_error_variables[] = $evaluation_error;
		}
		return $parse_result;
	}
	
	function browse_paranthesis($parse_tree) {
		foreach($parse_tree->get_children() as $p) {
			$this->browse_paranthesis($p);
			if($p->is_paranthesis()) {
				$this->traverse_paranthesis($parse_tree);
			}
		}
	}
	
	function traverse_paranthesis($parse_tree) {
		$paranthesis_list = array();
		$multiplier =  array();
		$children = $parse_tree->get_children();
		$counter = 0;
		foreach($children as $p) {
			if($p->is_paranthesis()) {
				$paranthesis_list[] = $p;
			} else if($counter+1 < count($children)) {
				if($children[$counter+1]->get_symbol() == '*') {
					$lug = new parse_object("clone", $p);
					$lug->set_symbol('*');
					$lug->value =  array();
					$lug->children =  array();
					foreach($p->value as $$c) {
						$lug->value[] = $c;
					}
					$multiplier[] = $lug;
					$p->value->Clear();
					$p->value[] = '1';
				}
			}
			$counter++;
		}
		$counter = 0;
		foreach($paranthesis_list as $p) {
			$this->compute_paranthesis($p, $multiplier);
			$multiplier =  array();
			foreach($p->get_children() as $child) {
				$multiplier[] = $child;
			}
		}
	}
	
	function compute_paranthesis($parse_tree, $luggage) {
		$multiplier =  array();
		$children = $parse_tree->get_children();
		$counter = 0;
		$paranthesis_list =  array();
		$additional_nodes =  array();
		foreach($children as $p) {
			if($luggage != NULL) {
				$lug_count = 0;
				foreach($luggage as $l) {
					if($lug_count == 0) {
						$p->add_object($l);
					} else {
						$addition = new parse_object("clone", $p);
						$addition->children =  array();
						$addition->set_symbol('+');
						$addition->add_object($l);
						$addition->value = array();
						foreach($p->value as $$c) {
							$addition->value[] = $c;
						}
						$additional_nodes[] = $addition;
					}
					$lug_count++;
				}
			}
			$counter++;
		}
		foreach($additional_nodes as $p) {
			$parse_tree->add_object($p);
		}
		$parse_tree->set_paranthesis(false);
	}
	
	function pop(&$symbols, $remove=true) {
		$count = 0;
		$symbol = '?';
		foreach($symbols as $c) {
			if($count == 0) {
				$symbol = $c;
			}
			$count++;
		}
		if($remove) {
			unset($symbols[(array_search($symbol, $symbols))]);
		}
		return $symbol;
	}
	
	function mark_variables($parse_object) {
		$contains_variables = ($parse_object->get_contains_variables() == 1 ? true : false);
		foreach($parse_object->get_children() as $child) {
			if($this->mark_variables($child) == true) {
				$contains_variables = true;
			}
		}
		if($contains_variables) {
			$parse_object->set_contains_variables(1); 
		} else {
			$parse_object->set_contains_variables(0);
		}
		return $contains_variables;
	}
	
	function parse_string($parse_tree) {
		$return_string = $parse_tree->get_symbol().($parse_tree->get_contains_variables() == 1 ? $parse_tree->get_string_value() : $parse_tree->get_value());
		foreach($parse_tree->get_children() as $child) {
			$return_string .= $this->parse_string($child);
		}
		$return_string;
		return $return_string;
	}
	
	function simplify($parse_tree) {
		$unfold_value;
		if(count($parse_tree->get_children()) > 0) {
			for($x = 0; $x<count($parse_tree->get_children()); $x++) {
				$child = $parse_tree->get_children()[$x];
				if($child->get_contains_variables() == 0) {
					$unfold_value = $this->unfold($child);
					$child->set_value($unfold_value);
				} else {
					$parse_tree->get_children()[$x] = $this->simplify($child);
				}
			}
		} else {
			return $parse_tree;
		}
		return $parse_tree;
	}
	
	function tab_print($count) {
		$counter = 0;
		$return = "";
		while($counter < $count) {
			$return .= "\t";
			$counter++;	
		}
		return $return;
	}
	
	function replace_decimals($statement) {
		$altered_statement = "";
		if(strpos($statement, ".") !== false) {
			$split = explode(".", $statement);
			$interlope = 0;
			$cur_pos = 0;
			$append_statement = array();
			foreach($split as $index => $value) {
				$append = "";
				$cur_pos += strlen($value);
				$set_value = "";
				$cut_length = 0;
				if($interlope == 0) {
					$digits = str_split($value);
					$digits = array_reverse($digits);
					$stop = false;
					foreach($digits as $key => $digit) {
						if(!is_numeric($digit)) {
							$stop = true;	
						}
						if(!$stop) {
							$set_value = $digit.$set_value;	
						}
					}
					$cut_length = strlen($set_value);
					$append = substr($value, 0, (strlen($value)-$cut_length));
					$append .= "(".$set_value."+(";
				} else {
					$digits = str_split($value);
					$stop = false;
					foreach($digits as $key => $digit) {
						if(!is_numeric($digit)) {
							$stop = true;	
						}
						if(!$stop) {
							$set_value = $set_value.$digit;	
						}
					}
					$cut_length = strlen($set_value);
					$common_value = $this->evaluation->common("0.".$set_value);
					$append = substr($value, $cut_length);
					$append = $common_value."))".$append;
				}
				if($index < (count($split)-1) && $interlope == 1) {
					$set_value = "";
					$digits = str_split($append);
					$digits = array_reverse($digits);
					$stop = false;
					foreach($digits as $key => $digit) {
						if(!is_numeric($digit)) {
							$stop = true;	
						}
						if(!$stop) {
							$set_value = $digit.$set_value;	
						}
					}
					$cut_length = strlen($set_value);
					$append = substr($append, 0, (strlen($append)-$cut_length));
					$append .= "(".$set_value."+(";
				}
				$interlope = 1;	
				$append_statement[] = $append;
			}
			return join("", $append_statement);
		}
		return $statement;
	}
	
	function insert_symbols($statement) {
		$strings = array(
			'log2',
			'log6',
			'log10',
			
			'cos',
			'sin',
			'cot',
			'tan',
			'csc',
			'sec',
		);
		
		$statement = str_replace($strings, $this->symbols, $statement);
		return $statement;	
	}
	
	function unfold($parse_tree, $tab=0) {
		$current_value = $this->evaluation->parse_value($parse_tree->get_value());
		$tab_spaces = $this->tab_print($tab);
		
		$fraction_truncate_length = 7;
		foreach($parse_tree->get_children() as $p) {
			
			$sub_value = $this->evaluation->parse_value((count($p->value) > 0 ? $p->get_value() : 0));
			
			
			
			
			$sub_unfold = (count($p->get_children()) > 0 ? $this->unfold($p, $tab+1) : array('value' => '0', 'remainder' => '0/1'));
			
			
			$value = $sub_value;
			
			if(($sub_value['value'] != '0' || $sub_value['remainder'] != '0/1') && ($sub_unfold['value'] != '0' || $sub_unfold['remainder'] != '0/1')) {
				$value = $this->evaluation->add_total($sub_value, $sub_unfold);
			} else if($sub_unfold['value'] != "0" || $sub_unfold['remainder'] != "0/1") {
				$value = $sub_unfold;
			} else {
			}
			
						
			$current_value_result = $current_value;
			$value_result = $value;
			
			$intermediate_value = $value_result;
			if($p->get_symbol() == '-' && !$p->altered) {
				$p->get_parent()->alter_symbol('-');
				$p->set_symbol('+');	
			}
			
			
			$inf_switch = 0;
			switch($p->get_symbol()) {
				case '+':
					if($current_value_result['value'] != '0' || !($current_value_result['remainder'] == '0/1' || $this->evaluation->fraction_values($current_value_result['remainder'])[0] == '0')) {
						
						$intermediate_value = $this->evaluation->add_total($current_value_result, $value_result);
					}
					break;
				case '-':
					$intermediate_value = $this->evaluation->subtract_total($current_value_result, $value_result);
					break;
				case '*':
										$intermediate_value = $this->evaluation->multiply_total($current_value_result, $value_result);
					break;
				case '/':
					$inf_switch = 1;
					$intermediate_value = $this->evaluation->execute_divide($current_value_result, $value_result);
					break;
				case '^':
					$intermediate_value = $this->evaluation->power($current_value_result, $value_result);
					break;
				case '_':
					$d = $value;
					$intermediate_value = $this->evaluation->power($value_result, array('value' => '0', 'remainder' => '1/2'));
					break;
				case '#':
					$intermediate_value = $this->evaluation->logarithm($value_result, array('value' => 2, 'remainder' => '0/1'));
					break;
				case '@':
					$intermediate_value = $this->evaluation->logarithm($value_result, array('value' => 6, 'remainder' => '0/1'));
					break;
				case '%':
					$intermediate_value = $this->evaluation->logarithm($value_result, array('value' => 10, 'remainder' => '0/1'));
					break;
				case 'c':
					$intermediate_value = $this->evaluation->trigonometry($value_result)['cos'];
					break;
				case 's':
					$intermediate_value = $this->evaluation->trigonometry($value_result)['sin'];
					break;
				case 'o':
					$intermediate_value = $this->evaluation->trigonometry($value_result)['cot'];
					break;
				case 't':
					$intermediate_value = $this->evaluation->trigonometry($value_result)['tan'];
					break;
				case 'e':
					$intermediate_value = $this->evaluation->trigonometry($value_result)['csc'];
					break;
				case 'v':
					$intermediate_value = $this->evaluation->trigonometry($value_result)['sec'];
					break;
				case 'a':
					$intermediate_value = $this->evaluation->trigonometry_radian($value_result)['cos'];
					break;
				case 'b':
					$intermediate_value = $this->evaluation->trigonometry_radian($value_result)['sin'];					break;
				case 'd':
					$intermediate_value = $this->evaluation->trigonometry_radian($value_result)['cot'];
					break;
				case 'f':
					$intermediate_value = $this->evaluation->trigonometry_radian($value_result)['tan'];
					break;
				case 'g':
					$intermediate_value = $this->evaluation->trigonometry_radian($value_result)['csc'];
					break;
				case 'h':
					$intermediate_value = $this->evaluation->trigonometry_radian($value_result)['sec'];
					break;
				default:
					if($current_value_result['value'] != '0' || !($current_value_result['remainder'] == '0/1' || $this->evaluation->fraction_values($current_value_result['remainder'])[0] == '0')) {
						
						$intermediate_value = $this->evaluation->add_total($current_value_result, $value_result);
					}
					break;
			}
			$current_value = $intermediate_value;
			
			
			
		}
		return $current_value;
	}
}



?>