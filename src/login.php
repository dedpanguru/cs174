<?php
    $db_hostname = "host.docker.internal";
    $db_username = "gurveer";
    $db_password = "gsingh";
    $db_name = "assignment6";
    /*
    CREATE TABLE IF NOT EXISTS credentials (
        id INT UNIQUE NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password BINARY(32) NOT NULL,
        salt BINARY(32) NOT NULL,
        PRIMARY KEY (email)
    );

    CREATE TABLE IF NOT EXISTS advisors (
        name VARCHAR(255) UNIQUE NOT NULL,
        lower_bound INT NOT NULL CHECK(lower_bound >= 1),
        upper_bound INT NOT NULL CHECK(upper_bound > lower_bound),
        email VARCHAR(255) UNIQUE NOT NULL,
        phone_number VARCHAR(15) UNIQUE NOT NULL,
        PRIMARY KEY(email)
    );
    */

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
        setcookie(session_name(), '', time() - 60*2, '/');
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
?>