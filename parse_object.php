<?

namespace NumEval;

class parse_object {
	public $children;
	private $parent;
	public $value;
	private $symbol;
	private $paranthesis;
	private $variable;
	private $contains_variables;
	private $parse_string;
	
	function set_symbol($s) {
		$this->symbol = $s;
	}
	function parse_object_parent($parent) {
		$this->children =  array();
		$this->value =  array();
		$this->parent = $parent;
		$this->paranthesis = false;
	}
	public static function init($parent) {
		$instance = new self();
        $instance->parse_object_parent($parent);
        return $instance;
	}
	function parse_object($method=NULL, $copy=NULL) {
		if($method == "copy") { 			$this->children =  array();
			$this->value =  array();
			foreach(str_split($copy->value) as $c) {
				$this->value[] = $c;
			}
			$this->parent = $copy->get_parent();
			$this->paranthesis = $copy->paranthesis;
		} else {
			
		}
	}
	function get_parse_string() {
		return $this->parse_string;   
	}
	function set_parse_string($s) {
		$this->parse_string = $s;
	}
	function set_paranthesis($value=true) {
		$this->paranthesis = $value;
	}
	function is_paranthesis() {
		return $this->paranthesis;
	}
	function set_variable($value) {
		$this->variable = $value;
	}
	function get_variable() {
		return $this->variable;
	}
	function set_contains_variables($contains_variables) {
		$this->contains_variables = $contains_variables;
	}
	function get_contains_variables() {
		return $this->contains_variables;
	}
	function append_value($value) {
		$this->value[] = $value;
	}
	function set_value($value) {
		
		$this->value =  array();
		foreach(str_split($value) as $c) {
			$this->value[] = $c;
		}
	}
	function get_value() {
		$str = $this->get_string_value();
		if(strlen($str) == 0) {
			return 0;
		}
		return $str;
	}
	function get_string_value() {
		$str = "";
		foreach($this->value as $c) {
			$str .= $c;
		}
		return $str;
	}
	function get_parent() {
		return $this->parent;
	}
	function list_top() {
		return $this->children[count($this->children) - 1];
	}
	function add_object($child) {
		$child->parent = $this;
		$this->children[] = $child;
		return $this->list_top();
	}
	function child() {
		$this->children[] = parse_object::init($this);
		return $this->list_top();
	}
	function get_children() {
		return $this->children;
	}
	function get_symbol() {
		return $this->symbol;
	}
	public $altered=false;
	function alter_symbol($symbol, $mark_altered=true) {
		$this->symbol = $symbol;	
		$this->altered = $mark_altered;
	}
}

?>