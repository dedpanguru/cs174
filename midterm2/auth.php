<?php
    require_once 'login.php'; // contains database login info 
    require_once 'credentials.php'; // contains the Credentials class and the HASH_ALG constant
    require_once 'helpers.php'; // contains get_fatal_error_message and sanitize functions

    // attempt database connection
    try
    {
        $conn = new mysqli($db_hostname, $db_username, $db_password, $db_name);
    }
    catch (Exception $e)
    {
        die(get_fatal_error_message());
    }

    // handle any login or registration requests
    if (isset($_POST['username']) && isset($_POST['password']))
    {
        $input_username = sanitize($conn, $_POST['username']);
        $input_password = sanitize($conn, $_POST['password']);
        if (isset($_POST['Register']) && $_POST['Register'] === 'true')
        {
            // validate username is unique
            $id_exists = Credentials::get_id_from_username($conn, $input_username);
            if (!$id_exists)
            {
                // submit the credentials
                $creds = new Credentials($input_username, $input_password);
                $success = $creds->insert($conn);
                ($success) ? print "<h1 style='color:green'>Success! Log in to use the application</h1>" : print '<h1>Server encountered some issues, please try again later</h1>';
            }
            else echo "<h1 style='color:red'>Username unavailable!</h1>";
        }
        else if (isset($_POST['Login']) && $_POST['Login'] === 'true')
        {
            // get the credentials from the database
            $creds = Credentials::find($conn, $input_username);
            // verify the input password
            if ($creds->compare_password($input_password))
            {
                session_start();
                $_SESSION['username'] = $input_username;
                $_SESSION['check'] = hash(HASH_ALG, $_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
                header("Location: ./index.php");
                die(get_fatal_error_message());
            }
        }
    }

    // print prompts
    echo Credentials::get_prompt('Register', 'auth.php');
    echo Credentials::get_prompt('Login', 'auth.php');
?>