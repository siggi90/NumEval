# NumEval
NumEval evaluates numerical values of mathematical statements with complete precision, with no rounding errors. No limit is set on the number of digits of values so you can calculate huge numbers as well as incredibly small fractions. You can receive resulting fractions with as many decimals as you want.

Notable features include:
-adding, subtracting, multiplying and dividing numbers with as many decimals as you want.
-finding exact results for roots (when they exist).
-trigonometry functions with more precison.
-logarithms with more precision.
-roots with more precision.
-deterministic prime detection. 

for more information visit: http://noob.software/support/#index/app_instructions#3

Evaluation:

    Addition
        add (accepts strings as value)
        add_total (accepts value-remainder array pair)
    Subtraction
        subtract
        subtract_total
    Multiplication
        result
        multiply_total
    Division
        execute_divide (accepts both strings and value-remainder array pair. Set fourth parameter $fast to true when dividing large numbers)
    Is Divisible
        verified_divisible (accepts value with value and remainder)
    Modulus
    Shorten Fraction
        execute_shorten_fraction (shortens fraction. If $truncate_fractions_length is greater than zero will truncate fraction to set length.)
        minimize_fraction (removes additional zeros from numerator and denominator)
    Powers/Roots
        power (accepts value with remainder and power as value with remainder, to get roots use fractional powers)
        execute_power_whole (for taking integer/whole powers accepts value-remainder pair and power as string)
        execute_power (for taking integer/whole roots accepts value-remainder pair and power as string)
        root_fraction (used as subroutine in power when disable_built_in_approximation is set to true, can be quicker to use directly)
        root (for taking integer roots, returns false if exact root is not found, but closest result is stored as $root_closest_result
        factor_root (starts by factoring number and takes roots of factors)
    Logarithms
    Trigonometry Functions
        returns an array of sin, cos, cot, tan, csc, sec. Function trigonometry accepts slope (cartesian coordinates) as input (two dimensional vector of value remainder array), use function trigonometry_radian if you need to use radians as input.
            Trigonometry (precision of these functions is based on square root precision, which can be configured)
                sin
                cos
                cot
                tan
                csc
                sec
            sin (accepts as input term precision, but can also be configured with $sine_precision same applies for cos, arctan and arccot.)
            cos
            arctan
            arccot
    List Rational Roots
        list_rational_roots, returns a list of all rational roots from 'from' value to 'to' value.
    Is Prime
        prime (Strength parameter configures strength of the function(default value is 8), pass $closest_known_prime lower than the value being determined as the second parameter for better validation, use $weak=true for a very fast weak test.) (determinstic for suitable strenght value)
        prime_alt (NumEval's old primality test) (deterministic)
        prime_p (Pollard's Rho primality test) (deterministic)
    Absolute
    Negative
    Is Negative
    Floor
    Ceil
    Round
    Is Larger
    Common Fraction Value
        whole_common
    Real Fraction Value
        quick_fraction, takes as input a value-remainder array pair and number of decimals for fraction
        real_fraction, takes as input a fraction and number of decimals for fraction
        numeric_whole, takes as input value, remainder and number of decimals
    Change Base (base <= 10)
    Find Perioidic Continued Fraction
        find_continued_fraction (precision parameter must be equal to or larger than limit parameter for precise result)
        square_root_fraction (finds continued fraction for square roots, returns value if periodic continued fraction is found, false otherwise)
    Resolve Periodic Continued Fraction
        resolve_continued_fraction
    List Divisors
        list_divisors (uses prime_factors function)
    Prime Factors
        prime_factors (uses NumEval's prime function and trial division)
        prime_factors_alt (NumEval's new prime factorization method in combination with Pollard's rho)
    ModExp
    ModMult
    ModAdd
    Co-Prime
    GCD
    Is Perfect Power
    Ord
    Factorial
    Parse Value (parsing value from parse function to value-pair
    Binary Functinos
        Binary Modulus
        Binary Multiplication
        Binary Addition
        Binary Subtraction (negative values are not supported)

Evaluation_parse

    Parse (parse function in evaluation_parse class, evaluates numerical statement which can be set using the constructor or set_function)


In addition to these functions NumEval comes with Vector, Multidimensional_vector, Matrix, Quadratic_solver, Arithematic. Vector contains functions for two dimensional vectors and Matrix contains functions for nxn dimensional matrices, both written using NumEval. Two dimensional vector functions accept values in form of array of value pairs for example:
array(array('value' => '1', 'remainder' => '1/2'), array('value' => '2', 'remainder' => '0/1'));

Matrix functions accept values in the form of two dimensional arrays:
array(
array(array('value' => '1', 'remainder' => '1/2'), array('value' => '2', 'remainder' => '0/1')),
array(array('value' => '3', 'remainder' => '1/2'), array('value' => '1', 'remainder' => '1/3'))
);

Quadratic_solver solves quadratic equations using numeval.

Arithematic is for delaying resolving roots and powers until the end of arithematic which can be useful for maximum precision.

NumEval is also available as a NuGet package: https://www.nuget.org/packages/NumEval/
