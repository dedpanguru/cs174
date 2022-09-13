<?php
    // Driver code starts here
    echo "<h1>This is a Gurveer Singh production</h1>";
    // create instance of solution class
    $pf = new PrimesFinder;
    // run pre-configured test suite
    $pf->run_tests();
    // Create file input form
    echo <<<_END
        <html><head><title>Assignment 2</title></head><body>
        <div class="file-upload-wrapper">
            <form action="primes_in_range.php" method="post" enctype="multipart/form-data">
                Select file: <input type="file" name="upload">
                <input type="submit" value="Upload">
            </form>
        </div>
    _END;
    // begin processing file input here
    if ($_FILES)
    {
        // access file name via its temporary name, any read from $_FILES should be sanitized
        $file_name = htmlentities($_FILES["upload"]["tmp_name"]);
        // open the file in read mode
        $file_handle = fopen($file_name, 'r') or die("Failed to open '$file_name'");
        // read line by line
        while ($line = htmlentities(fgets($file_handle))) // reads the line, sanitizes it, and continues the loop if it is not empty
        {
            // split the line by spaces
            $input = explode(" ", $line);
            // ensure that there will be 2 numbers per line
            if ($input && (count($input) === 2))
            {
                // call the primesInRange function with the input numbers as arguments
                $primes_as_string = $pf->primesInRange(intval($input[0]), intval($input[1]));
                // output the result
                strlen($primes_as_string) !== 0 ? print "All the primes in the range between '$input[0]' and '$input[1]' are: '$primes_as_string'<br>" : print " '$line' does not contain valid inputs!";
            }
            else 
            {
                echo "'$line' does not have valid format!";
            }
        }
        // close the file
        fclose($file_handle);
    }
    echo "</body></html>";
    // CLASS DEFINITIONS
    class PrimesFinder // will be used to determine all primes between 2 numbers
    {
        private $memo; // to be used for memoization in the case where many recalculations will take place
        
        public function __construct() 
        {
            $this->memo = [];
        }

        // executes pre-configured tests
        public function run_tests()
        {
            $test_cases = [ 
                // [param1, param2, boolean representing if length of returned string should be greater than 0]
                // good cases
                [3,10, true],
                [1,100, true],
                [4,6, true],
                // bad cases
                [0,5, false], // 0 shouldn't be taken as input
                [5,0, false],
                [1,1, false], // inputs should not be the same
                // ugly cases
                ["Hello", "World", false],
                [1.0, 3.14159265, false],
                [1, false, false],
                [true, 10, false],
                [null, null, false]
            ];
            // iterate over test cases and run each test case
            foreach($test_cases as $test_case){
                // since booleans aren't printed as their values, I have to check and extract their values as strings.
                $arg1 = (!is_bool($test_case[0])) ? $test_case[0] : var_export($test_case[0],true); 
                $arg2 = (!is_bool($test_case[1])) ? $test_case[1] : var_export($test_case[1],true);
                echo "What are all the prime numbers between ".$arg1." and ".$arg2."?";
                // find the actual result
                $actual = self::primesInRange($test_case[0],$test_case[1]);
                // print actual result
                echo "<br>Output from primesInRange(".$test_case[0].",".$test_case[1].")"." = ".$actual.". <b>Does it pass?</b>";
                // determine if result passes the test by checking if the length of the result string is greater than 0 and if that is the expected result
                strlen($actual) > 0 === $test_case[2] ? print "<b> Yes</b><br>" : print "<b> No</b><br>" ;
            }
        }

        // takes in 2 integers and finds all the prime numbers between them, returns an empty string in error cases or a string containing the primes separated by commas
        public function primesInRange($a,$b) : string
        {
            // validate inputs
            // both parameters must be integers
            if (!is_int($a)) 
            {
                echo var_export($a, true)." is not an integer!";
                return "";
            }
            if (!is_int($b))
            {
                echo var_export($b, true)." is not an integer!";
                return "";
            }
            // both parameters must be greater than 0
            if (($a < 1) || ($b < 1)) 
            {
                echo "Only positive, non-zero inputs are allowed!";
                return "";
            }
            // there must exist a range of whole numbers between the 2 parameters
            if (abs($a - $b) < 2) 
            {
                echo "The range between the inputs does not include whole numbers";
                return "";
            }
            // brute force for prime numbers in the range of the 2 parameters
            $bigger = $a > $b ? $a : $b;
            $smaller = $a > $b ? $b : $a;
            $solution = [];
            for ($num = $smaller; $num <= $bigger; $num++)
            {
                if ($num < 2) continue;
                $not_prime = $this->is_not_prime($num);
                if (!$not_prime)
                    array_push($solution, $num);   
            }
            return $this->array_to_string($solution);
        }

        // helper function that converts arrays to a csv string
        private function array_to_string(array $arr): string 
        {
            $string_builder = "";
            for($i = 0; $i < count($arr); $i++){
                $string_builder .= strval($arr[$i]);
                if ($i < count($arr) - 1)
                    $string_builder .= ", ";
            }
            return $string_builder;
        }

        // helper function that determines if a number is not prime
        private function is_not_prime(int $number): bool
        {
            if (array_key_exists($number, $this->memo))
                return $this->memo[$number];
            $not_prime = false;
            for ($i = 2; $i <= $number / 2; $i++)
                if ($number % $i === 0) 
                    $not_prime = true;
            $this->memo[$number] = $not_prime;
            return $not_prime;
        }
    }
?>