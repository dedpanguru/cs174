<?php
    // DRIVER CODE STARTS HERE
    // create form input
    echo <<<_END
    <html><head><title>Midterm 1</title></head><body>
        <h1>This is a Gurveer Singh Production. If someone else submitted this, clearly they were too lazy to diligently do their work and decided to steal/cheat instead.</h1>
        <form action="primes_in_range.php" method="post" enctype="multipart/form-data">
            Select file: <input type="file" name="upload">
            <input type="submit" value="Upload">
        </form>
    _END;
    // handle form input
    if (isset($_FILES))
    {
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
            $solution = $helper->solve();
            // print solution
            echo "The highest product in a 20x20 matrix of 4 adjacent numbers across rows, columns, and diagonals is ".$solution.'<br>';
        } else {
            echo "Unsupported file extension!<br>";
        }
    }
    echo "</body></html>";

    // CLASS DEFINITIONS START HERE
    class MatrixSolver {

        private $matrix; // will hold the 20x20 matrix

        // Constructor - requires a string parameter to construct the matrix from
        public function __construct(string $content)
        {
            // get the first 400 characters
            $data = $this->get_first_400($content);
            // add rows to the the matrix field in-place
            $this->generate_matrix_from_data($data);
        }

        // preprocessing helper function that extracts a 400-character string from raw file contents
        private function get_first_400(string $content): string
        {
            // get first 400 characters
            $data = "";
            $i = 0;
            while (strlen($data) < 400 && $i < strlen($content)) 
            {
                if ($content[$i] !== "\n" || $content[$i] !== " ") { // ignore whitespace and newlines
                    // convert alphabetic characters to 0
                    if (!is_numeric($content[$i])) {
                        echo "Found ".$content[$i].", replacing it with 0<br>";
                        $content[$i] = "0";
                    }
                    // only consider numbers
                    if (is_numeric($content[$i]))
                    {
                        $data .= $content[$i];
                    }
                }
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

        public function solve(): int
        {
            $max_of_rows = $this->max_from_rows();
            echo "The highest product of 4 adjacent numbers in a row is ".$max_of_rows.'<br>';
            $max_of_cols = $this->max_from_columns();
            echo "The highest product of 4 adjacent numbers in a column is ".$max_of_cols.'<br>';
            $max_of_diags = $this->max_from_diagonals();
            echo "The highest product of 4 adjacent numbers in a diagonal is ".$max_of_diags.'<br>';
            return max($max_of_rows, $max_of_cols, $max_of_diags);
        }

        // helper function to find 4 adjacent numbers in all rows of the matrix that have the highest product
        private function max_from_rows(): int
        {
            // assumes matrix is 20x20
            $max = 0;
            for ($row = 0; $row < 20; $row++)
            {
                // sliding window of size 4 going left to right along rows
                for ($col = 3; $col < 20; $col++)
                {
                    $product = $this->matrix[$row][$col] * $this->matrix[$row][$col-1] * $this->matrix[$row][$col-2] * $this->matrix[$row][$col - 3];
                    if ($product > $max[1]) 
                        $max = $product;
                }
            }
            return $max;
        }

        // helper function to find 4 adjacent numbers in all columns of the matrix that have the highest product
        private function max_from_columns(): int
        {
            // assumes matrix is 20x20
            $max = 0;
            for ($col = 0; $col < 20; $col++)
            {
                // sliding window of size 4 going top to bottom along the columns
                for ($row = 3; $row < 20; $row++)
                {
                    // find the window's product
                    $product = $this->matrix[$row][$col] * $this->matrix[$row-1][$col] * $this->matrix[$row-2][$col] * $this->matrix[$row-3][$col];
                    // check if it is bigger than the maximum product so far and reassign the maximum accordingly
                    if ($product > $max) 
                        $max = $product;
                }
            }
            return $max;
        }

        // helper function to find 4 adjacent numbers in all diagonals of the matrix that have the highest product
        private function max_from_diagonals(): int
        {
            // assumes matrix is 20x20
            $max = 0;
            $diag = $this->top_left_to_bottom_right_diagonal();
            // sliding window of size 4 going left to right along the array
            for ($i = 3; $i < 20; $i++)
            {
                // find the window's product
                $product = $diag[$i] * $diag[$i-1] * $diag[$i-2] * $diag[$i-3];
                // check if it is bigger than the maximum product so far and reassign the maximum accordingly
                if ($product > $max) 
                    $max = $product;
            }
            $diag = $this->bottom_left_to_top_right_diagonal();
            // sliding window of size 4 going left to right along the array
            for ($i = 3; $i < 20; $i++)
            {
                // find the window's product
                $product = $diag[$i] * $diag[$i-1] * $diag[$i-2] * $diag[$i-3];
                // check if it is bigger than the maximum product so far and reassign the maximum accordingly
                if ($product > $max) 
                    $max = $product;
            }
            return $max;
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
            // top left to bottom right includes row/col values such as [0,19], [1,18], ..., [19,0] -> row + col === 20, thus if you have the row, you know which column contains the the diagonal (col = 20 - row)
            for ($i = 0; $i < 20; $i++)
            {
                array_push($diagonal, $this->matrix[$i][20-$i]);
            }
            echo "The bottom left to top right diagonal contains the values: ".$this->array_to_string($diagonal)."<br>";
            return $diagonal;
        }

        // helper function that converts arrays to a csv string
        public static function array_to_string(array $arr): string 
        {
            $string_builder = "[";
            for($i = 0; $i < count($arr); $i++){
                if (is_array($arr[$i])){
                    $string_builder .= MatrixSolver::array_to_string($arr[$i]);
                }
                $string_builder .= strval($arr[$i]);
                if ($i < count($arr) - 1)
                    $string_builder .= ", ";
            }
            return $string_builder.']';
        }
    }
?>