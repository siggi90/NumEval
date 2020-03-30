<?

//ini_set("MAX_EXECUTION_TIME", "1600000");
set_time_limit(1600000); 

include 'binary_modulus.php';
include 'binary_division.php';
include 'trigonometry.php';
include 'search.php';
include 'root_solver.php';
include 'quadratic_solver.php';
include 'prime_detection.php';
include 'parse_object.php';
include 'evaluation_parse.php';
include 'm_vector.php';
include 'factorial.php';
include 'division.php';
include 'arithematic.php';
include '_vector.php';
include 'evaluation.php';

$evaluation = new NumEval\evaluation();

$result = $evaluation->add("2", "2");

var_dump($result);



$parse = new NumEval\evaluation_parse("2+2", $evaluation);
$result = $parse->parse("0");

var_dump($result);



//improving precision of roots using periodic continued fraction
/*$square_root = $evaluation->power(array('value' => '7', 'remainder' => '0/1'), array('value' => '0', 'remainder' => '1/2'));

$periodic_continued_fraction = $evaluation->square_root_fraction("7"); //use find_continued_fraction for other roots.

$resolution = $evaluation->resolve_continued_fraction($periodic_continued_fraction, $square_root);

var_dump($resolution);*/

/*$radian_value = "1.7"; //real fraction value

$radian_common_value = $evaluation->whole_common($radian_value); //common fraction value

$evaluation->set_truncate_fractions("15");

$trigonometry_values = $evaluation->trigonometry_radian($radian_common_value);
var_dump($trigonometry_values);

$cos = $evaluation->quick_numeric($trigonometry_values['cos'], "10"); //real fraction value
var_dump($cos);

//This way results from NumEval can be used in floating point applications and vice-versa.

//Since in this case we're just trying to find the cosine value it would be much faster to use the cosine function:

/*$cos = $this->evaluation->trigonometry->cosine($radian_common_value);
$cos = $this->evaluation->quick_numeric($cos);*/


/*
	For more examples visit http://noob.software/support/#index/app_instructions#3&
*/

?>