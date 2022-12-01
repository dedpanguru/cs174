<?php
    require_once 'login.php'; // imports $db_hostname, $db_username, $db_password, $db_name, 
    require_once 'helpers.php'; // imports sanitize(), redirect(), get_fatal_error_message(), SESSION_LIFETIME_IN_SECONDS
    require_once 'credentials.php'; // imports Credentials class

    // attempt database connection
    try
    {
        $conn = new mysqli($db_hostname, $db_username, $db_password, $db_name);
    }
    catch (Exception $e)
    {
        die(get_fatal_error_message());
    }

    // DRIVER CODE STARTS HERE
    // handle any login or registration requests
    if (isset($_POST['id']) && isset($_POST['password']))
    {
        $error = analyze_post($conn);
        if(!empty($error)) echo "<h1 style='color:red'>$error</h1>";
        else
        {
            // initialize session
            ini_set('session.gc_maxlifetime', SESSION_LIFETIME_IN_SECONDS);
            ini_set('session.use_only_cookies', 1);
            session_start();
            // store session info
            $_SESSION['check'] = hash('ripemd128', $_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']); // needed for verifying client
            // commit session info
            if (!session_commit()) die(get_fatal_error_message());
            // redirect to first page
            redirect('first_page.php');
        }
    }

    // print prompts
    echo Credentials::get_prompt('Register', 'second_page.php');
    echo Credentials::get_prompt('Login', 'second_page.php');

    // close html code
    echo '</body></html>';

    // close db connection
    $conn->close();

    // CLASS DEFINITIONS/HELPER FUNCTIONS START HERE
    function analyze_post(mysqli $conn): string
    {
        try 
        {
            // validate id
            $input_id = (isset($_POST['id'])) ? sanitize($conn, $_POST['id']) : throw new Exception('Invalid ID!');
            validateID($input_id);

            // validate password 
            $input_password = sanitize($conn, $_POST['password']);
            validatePassword($input_password);

            if (isset($_POST['Register']) && $_POST['Register'] === 'true') // Register case
            {
                // ensure student does not exist already
                if (!Credentials::find_from_id($conn, $input_id))
                {
                    // validate name
                    $input_name = (isset($_POST['name'])) ? sanitize($conn, $_POST['name']) : throw new Exception('Invalid Name!');
                    validateName($input_name);

                    // validate email
                    $input_email = sanitize($conn, $_POST['email']);
                    validateEmail($input_email);

                    // submit the credentials
                    $creds = new Credentials($input_name, $input_password, $input_email, $input_id, true, Credentials::generate_unique_salt($conn));
                    $success = $creds->insert($conn);
                    if (!$success) die(get_fatal_error_message());
                }
                else throw new Exception('Username taken!');
            }
            else if (isset($_POST['Login']) && $_POST['Login'] === 'true') // Login case
            {
                // get the credentials from the database
                $creds = Credentials::find_from_id($conn, $input_id);
                // verify the input password
                if (!$creds->compare_password($input_password)) throw new Exception('Invalid credentials!');
            }
        }
        catch (Exception $e)
        {
            return $e->getMessage();
        }
        return '';
    }
?>

<script>
    // access the form
    let registerForm = document.getElementById('Register')
    // provide a callback to the submit event to add client-side validation
    registerForm.addEventListener('submit', (event) => {
        // validate the form values
        if (!validateRegistration(registerForm))
            // if the values are invalid, prevent the submission
            event.preventDefault()
    })
    let loginForm = document.getElementById('Login')
    loginForm.addEventListener('submit', (event) => {
        if (!validateLogin(loginForm))
            event.preventDefault()
    })

    function validateRegistration(form) 
    {
        id = form.id.value
        name = form.name.value
        email = form.email.value
        password = form.password.value
        toValidate = [
            [name, validateName],
            [email, validateEmail],
            [id, validateID],
            [password, validatePassword]
        ]
        fail = ''
        for (const [input, validationFunc] of toValidate)
        {   
            fail += validationFunc(input)
            if (fail.length > 0) 
            {
                alert(fail)
                return false
            }
        }
        return true
    }

    function validateLogin(form) 
    {
        id = form.id.value
        password = form.password.value
        toValidate = [
            [id, validateID],
            [password, validatePassword]
        ]
        fail = ''
        for (const [input, validationFunc] of toValidate) 
        {
            fail += validationFunc(input)
            if (fail.length > 0) 
            {
                alert(fail)
                return false
            }
        }
        return true
    }

    // all inputs are required to submit so no input can have no length
    function validateName(name)
    {
        if (name.length == 0) 
            return 'Invalid Name'
        return ''
    }

    function validateID(id)
    {
        if (isNaN(id))
            return 'Invalid ID'
        return ''
    }

    function validatePassword(password)
    {
        if (password.length < 6) return 'Password length too small!'
        else if (!/[a-z]/.test(password) ||
                !/[A-Z]/.test(password) ||
                !/[0-9]/.test(password))
                return 'Passwords require 1 of each: lowercase character, uppercase character, and digit'
        return ''        
    }

    function validateEmail(email)
    {
        // check for presense of a period
        if (/(^\w.*@\w+\.\w)/.test(input)) return 'Invalid Email!'
        return ''
    }
</script>