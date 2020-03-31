<?
namespace NumEval;

class binary_division {
	
	private $evaluation;
		
	function __construct($evaluation) {
		$this->evaluation = $evaluation;
	}
	
	private $quotient;
	
	function get_quotient() {
		return $this->quotient;	
	}
		
	function divide($n, $d) {
		$q = "0";
		$r = "0";
		$value_length = strlen($n);
		$i = $this->evaluation->subtract($value_length, "1");
		while($this->evaluation->larger($i, 0)) {
			$r = $this->evaluation->bit_shift($r, "1", false);
			$r[strlen($r)-1] = $n[strlen($n)-$i-1];
			
			if($this->evaluation->larger($r, $d)) {
				$r = $this->evaluation->binary_subtraction($r, $d);
				$q = $this->evaluation->get_digits($q);
				$count_q = count($q);
				if($this->evaluation->larger($i, $count_q)) {
					$index = $this->evaluation->subtract($count_q, "1");	
					while($this->evaluation->larger($i, $index, false)) {
						$q[$index] = "0";
						$index = $this->evaluation->add($index, "1");
					}
				}
				$q[$i] = "1";	
				$q = implode("", array_reverse($q));
			}
			$i = $this->evaluation->subtract($i, "1");	
		}
		$this->quotient = $q;
		return $r;
	}
	
	function divide_alt($dividend, $divisor) {
		$dividend = $this->evaluation->change_base($dividend, "10", "2");
		$divisor = $this->evaluation->change_base($divisor, "10", "2");
		return $this->evaluation->change_base($this->evaluation->modulus($dividend, $divisor), "2");
	}
}



?>