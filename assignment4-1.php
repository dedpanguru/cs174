<?php
    // DRIVER CODE STARTS HERE
    // get login credentials
    require_once 'login.php';
    // pre-define query templates
    define('QUERY_RETRIEVE_ALL_ENTRIES', "SELECT * FROM $table");
    define('QUERY_SUBMIT_NEW_ENTRY', "INSERT INTO $table VALUES (NULL,?,?)"); // table should contain 3 columns: id int auto-increment, Name varchar(255), and Content varchar(255)
    // attempt database connection
    try
    {
        $conn = new mysqli($host_name, $username, $password, $database);
    }
    catch (Exception $err) // catches any problems with login info
    {
        die(get_fatal_error_message());
    }
    // handle connection error
    if ($conn->connect_error) die(get_fatal_error_message());

    // create webpage
    echo <<<_END
    <html><head><title>Assignment 4</title></head><body>
    <h1><u>This is a Gurveer Singh Production.</u></h1>
    <form action="assignment4.php" method="post" enctype="multipart/form-data"><pre>
        Select file: <input type="file" name="upload">

        Enter desired filename: <input type="text" name="filename">

        <input type="submit" value="Upload">
    </pre></form>
    _END;
    // submit post request data
    // check within _POST and _FILES
    if (isset($_POST) && isset($_POST['filename']) && isset($_FILES) && isset($_FILES['upload']))
    {
        // validate file mimetype
        if (htmlentities($_FILES['upload']['type']) === 'text/plain')
        {
            // sanitize data
            $sanitized_filename = sanitize($conn, $_POST['filename']);
            $sanitized_contents = sanitize($conn, file_get_contents(sanitize($conn, $_FILES['upload']['tmp_name'])));
            // develop model from data
            $model = new Model($sanitized_filename, $sanitized_contents);
            // create the entry in the database
            $affected = $model->create($conn, $table);
            ($affected === 1) ? print '<h2>Success :)</h2>' : print '<h2>Failure :(</h2>';
        }
        else echo '<h2>Invalid file type</h2>';
    }
    // read and display all entries
    Model::display_all_entries($conn);
    // close database connection
    $conn->close();
    // close html
    echo '</body></html>';

    // CLASS & HELPER FUNCTION DEFINITIONS START HERE

    // get_fatal_error_message - returns an error message that does not leak any database errors
    function get_fatal_error_message()
    {
        return <<<_END
        <html><head><title>Assignment 4</title></head><body>
        <h1><u>The server ran into some issues, please try using this application later!</u></h1>
        </body></html>
        _END;
    }

    // sanitize - sanitizes a potential input
    function sanitize($conn, $input): string
    {
        return htmlentities($conn->real_escape_string($input));
    }

    // Model class - represents an entry as a object
    class Model
    {
       private string $name, $content;
       public function __construct(string $name, string $content)
       {
            $this->name = $name;
            $this->content = $content;
       }

       // get_name - getter for name field
       public function get_name(): string
       {
            return $this->name;
       }

       // get_content - getter for content field
       public function get_content(): string
       {
            return $this->content;
       }

       // create - creates an insert statement from the input, the database connection, and name of the table, executes it, and returns the number of affected rows
       public function create(mysqli $conn): int
       {
            $stmt = $conn->prepare(QUERY_SUBMIT_NEW_ENTRY); // assumes table has an id field and passes null into it
            $stmt->bind_param('ss', $this->name, $this->content);
            $stmt->execute();
            $rv = $stmt->affected_rows;
            $stmt->close();
            return $rv;
       }

       // HTML_string - creates string of html from the columns of the table
       public static function HTML_string(string $name, string $content): string
       {
           return "Name: $name <br> Content: $content <br>";
       }

       // display_all_entries - loads and prints all database content
       public static function display_all_entries(mysqli $conn) 
       {
            // query database
            $result = $conn->query(QUERY_RETRIEVE_ALL_ENTRIES);
            // handle no result
            if (!$result) die(get_fatal_error_message());
            else
            {
                echo '<h1>All entries in the database</h1>';
                // output result
                for ($row_num = $result->num_rows-1; $row_num >= 0; $row_num--) // displaying most recent added entries first
                {
                    $result->data_seek($row_num);
                    $row = $result->fetch_array(MYSQLI_ASSOC);
                    echo Model::HTML_string($row['Name'], $row['Content']);

                }
            }
            // close result
            $result->close();
       }
    }
?>