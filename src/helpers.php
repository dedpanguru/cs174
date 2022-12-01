<?php
    define('SESSION_LIFETIME_IN_SECONDS', 60*2);
    
    function get_fatal_error_message(): string 
    {
        return <<<_END
        <html><head><title>Assignment 6</title></head><body>
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
        $_SESSION = array();
        setcookie(session_name(), '', time() - SESSION_LIFETIME_IN_SECONDS, '/');
        session_destroy();
    }

    function force_logout(string $redirect_url)
    {
        kill_session();
        redirect($redirect_url);
    }

    function redirect(string $url)
    {
        header("Location: $url");
        die();
    }

    function validateName(string $name)
    {
        if (strlen($name) == 0) throw new Exception('Invalid Name');
    }

    function validateID(string $id)
    {
        if (!is_numeric($id)) throw new Exception('Invalid ID!');
        if (intval($id) <= 0) throw new Exception('Invalid ID!');
    }

    function validatePassword(string $password)
    {
        if (strlen($password) < 6) throw new Exception('Password length too small!');
        elseif (!preg_match('/[a-z]/', $password) ||
                !preg_match('/[A-Z]/', $password) ||
                !preg_match('/[0-9]/', $password))
                throw new Exception('Passwords require 1 of each: lowercase character, uppercase character, and digit');        
    }

    function validateEmail(string $email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new Exception('Invalid Email!');
    }
?>