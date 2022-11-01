<?php
    // driver code starts here
    require_once 'login.php';
    define('STATEMENT_INSERT_NEW_CREDENTIALS', "INSERT INTO credentials VALUES (NULL, ?, ?, ?,?)");
    define('STATEMENT_INSERT_NEW_COMMENT', 'INSERT INTO comments VALUES (NULL, ?, ?, NULL)');
    define('ALL_CHARS', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()');
    define('SALT_SIZE', 32);
    define('HASH_ALG', 'ripemd128');
    define('COOKIE_LIFETIME_IN_SECONDS', 300); // 5 minute cookie
    
    // attempt database connection
    try
    {
        $conn = new mysqli($db_host, $db_username, $db_password, $db_name);
    }
    catch (Exception $err) // catches any problems with login info
    {
        die(get_fatal_error_message());
    }
    // handle connection error
    if ($conn->connect_error) die(get_fatal_error_message());
    
    // establish manager for credential-related db queries
    $creds_manager = new CredentialsManager($conn);
    
    // Create webpage
    echo "<html><head><title>Assignment 5</title></head><body>
    <h1><u>This is a Gurveer Singh Production.</u></h1>";
    
    // greet user
    $username = (isset($_COOKIE['username'])) ? sanitize($conn, $_COOKIE['username']) : '';

    if ($username) // user is logged in case
    {
        // get the name of the user
        $name = $creds_manager->get_name_from_username($username);
        echo "<h2>Hello $name!</h2>";
        // prompt logout
        echo <<<_END
        <form action='assignment_5.php' method='post' enctype='multipart/form-data'>
            <input type='hidden' name='logout' value='true'>
            <input type='submit' value='Logout'>
        </form>
        _END;
        // handle logout
        if(isset($_POST['logout']) && $_POST['logout'] === 'true')
        {
            // issue an invalid cookie
            setcookie('username', $username, time()-COOKIE_LIFETIME_IN_SECONDS, '/');
            // refresh the browser
            header("Refresh:0");
        }
        $comments_manager = new CommentsManager($conn, $username);
        // prompt comment submission
        echo CommentsManager::get_prompt();
        // handle comment submission
        if(isset($_POST['submit']) && $_POST['submit'] === 'true')
        {
            // get the form inputs
            $input_comment = sanitize($comments_manager->get_connection(), $_POST['comment']);
            // store them in the database
            $success = $comments_manager->store_comment($input_comment);
            // output success or failure
            ($success) ? print '<h3>Success!</h3>' : print '<h3>The server ran into some issues, please try using this application later!</h3>';
        }
        // display all comments MADE BY THE USER
        echo '<h2><u>Your comments</u></h2>';
        foreach ($comments_manager->get_all_comments($conn, $username) as $comment) echo $comment;
    }
    else // user not logged in case
    {
        echo "<h2>Hello!<br>You are not logged in!</h2>";
        // display sign up prompt
        echo CredentialsManager::get_prompt('Register');
        // display login prompt
        echo CredentialsManager::get_prompt('Login');
        // handle auth requests
        if (isset($_POST['username']) && isset($_POST['password']))
        {
            $input_username = sanitize($creds_manager->get_connection(), $_POST['username']);
            $input_password = sanitize($creds_manager->get_connection(), $_POST['password']);
            if (isset($_POST['Login']) && $_POST['Login'] === 'true') // login case
            {
                if ($creds_manager->verify_credentials($input_username, $input_password)) // validate credentials
                {
                    // issue a cookie 
                    setcookie('username', $input_username, time() + COOKIE_LIFETIME_IN_SECONDS);
                    header("Refresh:0");
                }
                else echo '<h4>Invalid Credentials!</h4><br>';
            } 
            else if (isset($_POST['Register']) && $_POST['Register'] === 'true') // signup case
            {
                // validate username is unique
                if (!$creds_manager->username_in_database($input_username))
                {
                    // get the name field
                    $input_name = sanitize($creds_manager->get_connection(), $_POST['name']);
                    // generate salt
                    $salt = generate_salt();
                    // hash password
                    $hashed_password = hash(HASH_ALG, $input_password.$salt);
                    // save in database
                    $success = $creds_manager->store_credentials($input_username, $hashed_password, $salt, $input_name);
                    // output success or failure
                    ($success) ? print '<h3>Success! Please login to access the application </h3>' : print '<h3>The server ran into some issues, please try using this application later!</h3>';
                }
                else echo '<h4>Username unavailable!</h4><br>';
            } 
        }
    }
    // close webpage
    echo "</body></html>";
    // close connection
    $conn->close();

    // CLASS DEFINITIONS AND HELPER FUNCTIONS START HERE

    // get_fatal_error_message - helper function that returns a diplomatic error message which does not leak any database details
    function get_fatal_error_message(): string
    {
        return <<<_END
        <html><head><title>Assignment 4</title></head><body>
        <h1><u>The server ran into some issues, please try using this application later!</u></h1>
        </body></html>
        _END;
    }

    // sanitize - sanitizes a potential input
    function sanitize(mysqli $conn, string $input): string
    {
        return htmlentities($conn->real_escape_string($input));
    }

    // generate_salt - creates a 32-byte random string
    function generate_salt(): string
    {
        return substr(str_shuffle(str_repeat(ALL_CHARS, SALT_SIZE)), 0, SALT_SIZE);
    }

    class CredentialsManager
    {
        private $conn;

        public function __construct(mysqli $conn)
        {
            $this->conn = $conn;
        }

        public function get_connection(): mysqli
        {
            return $this->conn;
        }

        // get_prompt - renders a form input with a given title
        public static function get_prompt(string $section): string
        {
            $prompt = '';
            switch ($section){
                case 'Login':
                    $prompt = <<<_END
                <pre><h2>$section</h2>
                <form action="assignment_5.php" method="post" enctype="multipart/form-data">
                    Enter Username: <input type="text" name="username" required>
        
                    Enter Password: <input type="password" name="password" required>
                    
                    <input type="hidden" name="$section" value="true">
                    <input type="submit" value="$section">
                </form>
                </pre>
                _END;
                break;
                case 'Register':
                    $prompt = <<<_END
                    <pre><h2>$section</h2>
                    <form action="assignment_5.php" method="post" enctype="multipart/form-data">
                        Enter Username: <input type="text" name="username" required>
                    
                        Enter Password: <input type="password" name="password" required>
                    
                        Enter Full Name: <input type="text" name="name" required>
                        <input type="hidden" name="$section" value="true">
                        <input type="submit" value="$section">
                    </form>
                    </pre>
                    _END;
                    break;
            }
            return $prompt;
        }

        // username_in_database - verifies that a given username is found in the database without accessing it or any associated data
        public function username_in_database(string $username): bool
        {
            $query = "SELECT TRUE FROM credentials WHERE username = '$username'";
            $result = $this->conn->query($query);
            if (!$result) die(get_fatal_error_message());
            $rv = $result->num_rows;
            $result->close();
            return $rv === 1;
        }

        // store_credentials - inserts a given username, password and salt into the database and outputs success as a boolean
        public function store_credentials(string $username, string $password, string $salt, string $name): bool
        {
            $stmt = $this->conn->prepare(STATEMENT_INSERT_NEW_CREDENTIALS);
            $stmt->bind_param('ssss', $username, $password, $salt, $name);
            $stmt->execute();
            $rows = $stmt->affected_rows;
            $stmt->close();
            return $rows === 1;
        }

        // verify_credentials - retrieves user credentials from the database and compares them to inputs
        public function verify_credentials(string $username, string $password): bool
        {
            // verify username is in the database
            if (!$this->username_in_database($username)) return false;
            // get the password and salt from the database
            $query = "SELECT password, salt FROM credentials WHERE username = '$username'";
            $result = $this->conn->query($query);
            if (!$result) die(get_fatal_error_message());
            // should only be 1 row so use 0 for data_seek()
            $result->data_seek(0);
            $db_record = $result->fetch_array(MYSQLI_ASSOC);
            $result->close();
            // hash input password with salt from db and compare  
            return $db_record['password'] === hash(HASH_ALG, $password.$db_record['salt']);
        }

        // get_name_from_username - retrieves the name value associated with the given username from the database
        public function get_name_from_username( string $username): string
        {
            $query = "SELECT name FROM credentials WHERE username = '$username'";
            $result = $this->conn->query($query);
            if (!$result) die(get_fatal_error_message());
            $result->data_seek(0);
            $rows = $result->fetch_array(MYSQLI_NUM);
            $name = $rows[0];
            $result->close();
            return $name;
        }
    }

    // CommentsManager - class that manages all writes and reads to the comments table
    class CommentsManager
    {
        // requires a connection and user id
        private $conn, $user_id; // user id links a username to its comments

        // constructor takes a connection and a username
        public function __construct(mysqli $conn, string $username)
        {
            $this->conn = $conn;
            $this->user_id = $this->get_user_id_from_username($username);
        }
        
        // get_connection - getter for connection field
        public function get_connection(): mysqli
        {
            return $this->conn;
        }

        // get_user_id_from_from_username - gets the user id of a username from the credentials table
        private function get_user_id_from_username(string $username): int
        {
            $query = "SELECT id FROM credentials WHERE username = '$username'";
            $result = $this->conn->query($query);
            $result->data_seek(0);
            $row = $result->fetch_array(MYSQLI_NUM);
            $result->close();
            return $row[0];
        }

        // store_comment - stores a given comment into the comments table
        public function store_comment(string $comment)
        {
            $stmt = $this->conn->prepare(STATEMENT_INSERT_NEW_COMMENT);
            $stmt->bind_param('ss', $comment, $this->user_id);
            $stmt->execute();
            $rows = $stmt->affected_rows;
            $stmt->close();
            return $rows === 1;
        }

        // get_prompt - returns a string containing an HTML form prompting a comment submission
        public static function get_prompt(): string
        {
            return <<<_END
            <h2><u>Submit a new comment</u></h3>
            <form action="assignment_5.php" method="post" enctype="multipart/form-data"><pre>
               Enter Comment: <input type="text" name="comment">
               <input type="hidden" name="submit" value="true">
               <input type="submit" value="Submit">
            </pre>
            </form>
            _END;
        }

        // get_all_comments - retrieves all comments of a username as a array of HTML strings containing comment data 
        public function get_all_comments(): array
        {
            // retrieve all the comment data from the database
            $query = "SELECT comment, created_at FROM comments WHERE user_id = $this->user_id ORDER BY id DESC";
            $result = $this->conn->query($query);
            if(!$result) die(get_fatal_error_message());
            $comments = [];
            for ($row_num = 0; $row_num < $result->num_rows; $row_num++)
            {
                $result->data_seek($row_num);
                $row = $result->fetch_array(MYSQLI_ASSOC);
                $text = $row['comment']; 
                $created_at = $row['created_at'] ;
                array_push($comments, 
                "<pre>
    Date Posted: $created_at
    Comment: $text
                </pre>");
            }
            $result->close();
            return $comments;
        }
    }
?>