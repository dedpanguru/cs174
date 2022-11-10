<?php
    define('SESSION_LIFETIME_IN_SECONDS', 60*2); // 2 minute lifetime

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

    function kill_session() 
    {
        $_SESSION = [];
        setcookie(session_name(), '', time() - SESSION_LIFETIME_IN_SECONDS, '/');
        session_destroy();
    }
    
    function redirect(string $url)
    {
        header("Location: $url");
        die();
    }

    function different_user(string $redirect_url)
    {
        kill_session();
        redirect($redirect_url);
    }
?>