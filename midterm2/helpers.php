<?php
    function get_fatal_error_message(): string 
    {
        return <<<_END
        <html><head><title>Midterm 2</title></head><body>
        <h1><u>The server ran into some issues, please try using this application later!</u></h1>
        </body></html>
        _END;
    }

    function sanitize(mysqli $conn, string $input): string
    {
        return htmlentities($conn->real_escape_string($input));
    }
?>