<?php
    // DRIVER CODE STARTS HERE
    // create form input
    echo <<<_END
    <html><head><title>Midterm 1</title></head><body>
    <h1><u>This is a Gurveer Singh Production.</u></h1>
    <form action="midterm_1.php" method="post" enctype="multipart/form-data">
    Select file: <input type="file" name="upload">
    <input type="submit" value="Upload">
    </form>
    _END;
    // handle form input
    if ($_FILES)
    {
        echo "<div>";
        // verify file extension
        if (htmlentities($_FILES["upload"]["type"]) === "text/plain") // text/plain is the mimetype for txt files
        {
            // access temporary name of input file
            $file_name = htmlentities($_FILES["upload"]["tmp_name"]);
            // read the whole file, sanitize data
            $contents = htmlentities(file_get_contents($file_name));
            // create an instance of the MatrixSolver class from the body data
            $solver = new MatrixSolver($contents);
            // get solution
            $solution = $solver->solve();
            // print solution
            echo "The highest product in a 20x20 matrix of 4 adjacent numbers across rows, columns, and diagonals is <b>".$solution.'</b><br>';
        } 
        else 
        {
            echo "Unsupported file extension!<br>";
        }
        echo "</div>";
    }
    echo "<h2><u>Pre-configured test suite below</u></h2>";
    // create and call tester function
    function run_test(string $input, int $expected)
    {
        echo "<h3>Expecting output value ".$expected." from input string: ".$input."</h3>";
        $matrix_solver = new MatrixSolver($input);
        $matrix = $matrix_solver->get_matrix();
        echo "Created matrix: ".MatrixSolver::array_to_string($matrix)."<br>";
        // verify matrix is 20x20
        if (count($matrix) !== 20)
        {
            echo "<b>Matrix does not have 20 rows</b><br>";
            return;
        }
        foreach ($matrix as $row)
        {
            if (!is_array($row))
            {
                echo "<b>rows are not in the form of an array</b><br>";
                return;
            }
            if (count($matrix) !== 20)
            foreach ($row as $col)
            {
                if ($col > 0)
                {
                    echo "<b>Matrix should be all 0s, but it is not</b><br>";
                    return;
                }
            }
        }
        // solve the matrix
        $solution = $matrix_solver->solve();
        echo "Generated solution = ".$solution."<br>";
        // output test success
        ($solution !== $expected) ? print "<b>Test Failed! Expected solution = ".$expected."</b><br>" : print "<b>Passed test for input: ".$input."</b><br>";
    }
    // create test cases, format = [input as string, expected output as integer]
    $test_cases = [
        ["", 0],
        [str_repeat("1", 400), 1],
        [" 1234", 24],
        ["1234", 24],
        ["a?!4 5 F", 0],
        ["-1", 0],
        ["&nbsp;",0],
        ["
        71636269561882670428
        85861560789112949495
        65727333001053367881
        52584907711670556013
        53697817977846174064
        83972241375657056057
        82166370484403199890
        96983520312774506326
        12540698747158523863
        66896648950445244523
        05886116467109405077
        16427171479924442928
        17866458359124566529
        24219022671055626321
        07198403850962455444
        84580156166097919133
        62229893423380308135
        73167176531330624919
        30358907296290491560
        70172427121883998797", 5832]
    ];
    // run the tests
    foreach ($test_cases as $test_case)
    {
        run_test($test_case[0], $test_case[1]);
    }
    // end the html code
    echo "</body></html>";

    // CLASS DEFINITIONS START HERE
    class MatrixSolver {

        private array $matrix = []; // will hold the 20x20 matrix

        // Constructor - requires a string parameter to construct the matrix from
        public function __construct(string $content)
        {
            // get the first 400 characters
            $data = $this->get_first_400($content);
            // add rows to the the matrix
            $this->generate_matrix_from_data($data);
        }

        // getter for matrix field, keeping matrix as read-only
        public function get_matrix(): array
        {
            return $this->matrix;
        }

        // preprocessing helper function that extracts a 400-character string from raw file contents
        private function get_first_400(string $content): string
        {
            // get first 400 characters
            $data = "";
            $i = 0;
            // remove \n, \r, and " " from content, effectively ignoring whitespace and newlines
            $content = str_replace([" ", "\r", "\n"], "", $content);
            while ((strlen($data) < 400) && ($i < strlen($content))) 
            {
                // convert alphabetic characters to 0
                if (!is_numeric($content[$i])) {
                    echo "Found ".$content[$i].", replacing it with 0<br>";
                    $content[$i] = "0";
                }
                // only consider numbers
                if (is_numeric($content[$i]))
                    $data .= $content[$i];
                // move to the next character in the string
                $i++;
            }
            // pad with 0s until data length is 400 characters
            if (strlen($data) < 400) 
                $data .= str_repeat("0", 400-strlen($data));
            return $data;
        }

        // helper function that creates a 20x20 matrix from a 400-character string of data
        private function generate_matrix_from_data(string $data)
        {
            // assumes length of $data is 400
            // Strategy: Create batches of 20 numbers during iteration. Once a batch is full, add it to the overall grid and empty the batch. Batch represents rows in the matrix
            $row = [];
            for ($i = 0; $i < strlen($data); $i++)
            {
                array_push($row, intval($data[$i]));
                if (count($row) === 20)
                {
                    array_push($this->matrix, $row);
                    $row = [];
                }
            }
        }

        // helper function that determines the highest product of 4 adjacent number in each row, column, and diagonal of the matrix
        public function solve(): int
        {
            $max_of_rows = $this->max_from_rows();
            $max_of_cols = $this->max_from_columns();
            $max_of_diags = $this->max_from_diagonals();
            return max($max_of_rows, $max_of_cols, $max_of_diags);
        }

        // helper function to find 4 adjacent numbers in all rows of the matrix that have the highest product
        private function max_from_rows(): int
        {
            // assumes matrix is 20x20
            $max = [0,[],0]; // weird structure exists for a reason [row number of solution, [the four numbers that form the product], the product]
            for ($row = 0; $row < 20; $row++)
            {
                // sliding window of size 4 going left to right along rows
                for ($col = 3; $col < 20; $col++)
                {
                    $product = $this->matrix[$row][$col] * $this->matrix[$row][$col-1] * $this->matrix[$row][$col-2] * $this->matrix[$row][$col-3];
                    if ($product > $max[2])
                    {
                        $max = [
                            $row+1,
                            [$this->matrix[$row][$col-3], $this->matrix[$row][$col-2],  $this->matrix[$row][$col-1], $this->matrix[$row][$col]],
                            $product
                        ];
                    }
                }
            }
            echo "The highest product of 4 adjacent numbers across the rows is ".$max[2].", which is the product of the values: ".MatrixSolver::array_to_string($max[1])." found in row ".$max[0]."<br>";
            return $max[2];
        }

        // helper function to find 4 adjacent numbers in all columns of the matrix that have the highest product
        private function max_from_columns(): int
        {
            // assumes matrix is 20x20
            $max = [0,[],0];
            for ($col = 0; $col < 20; $col++)
            {
                // sliding window of size 4 going top to bottom along the columns
                for ($row = 3; $row < 20; $row++)
                {
                    // find the window's product
                    $product = $this->matrix[$row][$col] * $this->matrix[$row-1][$col] * $this->matrix[$row-2][$col] * $this->matrix[$row-3][$col];
                    // check if it is bigger than the maximum product so far and reassign the maximum accordingly
                    if ($product > $max[2])
                    {
                        $max = [
                            $row+1,
                            [$this->matrix[$row-3][$col], $this->matrix[$row-2][$col],  $this->matrix[$row-1][$col], $this->matrix[$row][$col]],
                            $product
                        ];
                    }
                }
            }
            echo "The highest product of 4 adjacent numbers across the columns is ".$max[2].", which is the product of the values: ".MatrixSolver::array_to_string($max[1])." found in column ".$max[0]."<br>";
            return $max[2];
        }

        // helper function to find 4 adjacent numbers in all diagonals of the matrix that have the highest product
        private function max_from_diagonals(): int
        {
            // assumes matrix is 20x20
            $max = [0,[],0];
            $diag1 = $this->top_left_to_bottom_right_diagonal();
            $diag2 = $this->bottom_left_to_top_right_diagonal();
            // 1 sliding window of size 4 going left to right along BOTH DIAGONALS
            for ($i = 3; $i < 20; $i++)
            {
                // find the window's product for top left to bottom right diagonal
                $product = $diag1[$i] * $diag1[$i-1] * $diag1[$i-2] * $diag1[$i-3];
                // check if it is bigger than the maximum product so far and reassign the maximum accordingly
                if ($product > $max[2])
                {
                    $max = [
                        1,
                        [$diag1[$i-3], $diag1[$i-2], $diag1[$i-1], $diag1[$i]],
                        $product
                    ];
                }
                // find the window's product for bottom left to top right diagonal
                $product = $diag2[$i] * $diag2[$i-1] * $diag2[$i-2] * $diag2[$i-3];
                // check if it is bigger than the maximum product so far and reassign the maximum accordingly
                if ($product > $max[2])
                {
                    $max = [
                        1,
                        [$diag2[$i-3], $diag2[$i-2], $diag2[$i-1], $diag2[$i]],
                        $product
                    ];
                }
            }
            echo "The highest product of 4 adjacent numbers across the diagonals is ".$max[2].", which is the product of the values: ".MatrixSolver::array_to_string($max[1])." found in diagonal ".$max[0]."<br>";
            return $max[2];
        }

        // getter/helper function that retrieves top left to bottom right diagonal of the matrix as an array
        public function top_left_to_bottom_right_diagonal(): array
        {
            $diagonal = [];
            for ($i = 0; $i < 20; $i++)
            {
                // top left to bottom right means row and column values will be equal to each other -> [0,0], [1,1], ..., [19,19]
                array_push($diagonal, $this->matrix[$i][$i]);
            }
            echo "The top left to bottom right diagonal contains the values: ".MatrixSolver::array_to_string($diagonal)."<br>";
            return $diagonal;
        }
        
        // getter/helper function that retrieves bottom left to top right diagonal of matrix as an array 
        public function bottom_left_to_top_right_diagonal(): array
        {
            $diagonal = [];
            // top left to bottom right includes row/col values such as [0,19], [1,18], ..., [19,0] -> row + col === 19, thus if you have the row, you know which column contains the the diagonal (col = 19 - row)
            for ($i = 0; $i < 20; $i++)
            {
                array_push($diagonal, $this->matrix[$i][19-$i]);
            }
            echo "The bottom left to top right diagonal contains the values: ".$this->array_to_string($diagonal)."<br>";
            return $diagonal;
        }

        // helper static function that converts arrays to a csv string
        public static function array_to_string(array $arr): string 
        {
            $string_builder = "[";
            for($i = 0; $i < count($arr); $i++){
                $string_builder .= (is_array($arr[$i])) ? MatrixSolver::array_to_string($arr[$i]): var_export($arr[$i],true);
                if ($i < count($arr) - 1)
                    $string_builder .= ", ";
            }
            return $string_builder.']';
        }
    }
?>