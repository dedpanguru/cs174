<?php
    require_once 'login.php'; // needs get_fatal_error_message(), $db_hostname, $db_username, $db_password, $db_name, sanitize(), and redirect()
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
    if (isset($_POST['email']) && isset($_POST['password']))
    {
        $error = analyze_post($conn);
        if(!empty($error)) echo "<h1 style='color:red'>$error</h1>";
        else
        {
             // initialize session
             ini_set('session.gc_maxlifetime', 60*2);
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
            // validate email
            $input_email = sanitize($conn, $_POST['email']);
            validateEmail($input_email);

            // validate password 
            $input_password = sanitize($conn, $_POST['password']);
            validatePassword($input_password);

            if (isset($_POST['Register']) && $_POST['Register'] === 'true') // Register case
            {
                // validate email is unique
                $input_id = (isset($_POST['id'])) ? sanitize($conn, $_POST['id']): throw new Exception('Invalid ID!');
                validateID($input_id);

                if (!Credentials::check_id_exists($conn, $input_id))
                {
                    // validate name
                    $input_name = (isset($_POST['name'])) ? sanitize($conn, $_POST['name']) : throw new Exception('Invalid Name!');
                    validateName($input_name);

                    // validate id
                    $input_id = (isset($_POST['id'])) ? sanitize($conn, $_POST['id']) : throw new Exception('Invalid ID!');
                    validateID($input_id);

                    // submit the credentials
                    $creds = new Credentials($input_name, $input_password, $input_email, $input_id);
                    $success = $creds->insert($conn);
                    if (!$success) die(get_fatal_error_message());
                }
                else throw new Exception('That name is are already registered!');
            }
            else if (isset($_POST['Login']) && $_POST['Login'] === 'true') // Login case
            {
                // get the credentials from the database
                $creds = Credentials::find($conn, $input_email);
                // verify the input password
                if ($creds->compare_password($input_password)) throw new Exception('Invalid email!');
            }
        }
        catch (Exception $e)
        {
            return $e->getMessage();
        }
        return '';
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

    class Credentials 
    {
        private string $name, $hashed_password, $salt, $email;
        private int $id;

        public function __construct(string $name, string $password, string $email, int $id, bool $auto_hash = true, string $salt = "") 
        {
            $this->name = $name;
            $this->hashed_password = $password;
            $this->email = $email;
            $this->id = $id;
            $this->salt = (empty($salt)) ? $this::generate_salt() : $salt;
            if ($auto_hash) $this->hash_password();
        }

        public function hash_password(string $salt = '')
        {
            if (empty($salt)) $salt = $this->salt; 
            $this->hashed_password = hash('ripemd128', $this->hashed_password.$salt);
        }

        public function compare_password(string $input_password): bool
        {
            return $this->hashed_password === hash('ripemd128', $input_password.$this->salt);
        }

        public static function get_prompt(string $section, string $pagename): string
        {
            $prompt = '';
            switch ($section){
                case 'Login':
                    $prompt = <<<_END
                    <pre><h2>$section</h2>
                    <form action="$pagename" method="post" enctype="multipart/form-data">
                        Enter Email: <input type="email" name="email" required>
                        
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
                    <form action="$pagename" method="post" enctype="multipart/form-data">
                        Enter Full Name: <input type="text" name="name" required>
                        
                        Enter Email: <input type="email" name="email" required>
                        
                        Enter Student ID: <input type="number" name="id" required>
                        
                        Enter Password: <input type="password" name="password" required>
                        <input type="hidden" name="$section" value="true">
                        <input type="submit" value="$section">
                    </form>
                    </pre>
                    _END;
                    break;
            }
            return $prompt;
        }

        public static function generate_salt(): string 
        {
            $possible_chars = "qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM,.?!$@#%^&*";
            $pieces = [];
            $max = mb_strlen($possible_chars, '8bit') - 1;
            for ($i = 0; $i < 32; $i++)
            {
                $pieces []= $possible_chars[random_int(0, $max)];
            }
            return implode('', $pieces);
        }

        public static function check_id_exists(mysqli $conn, string $id): bool
        {
            $query = "SELECT id FROM credentials WHERE id = '$id'";
            $result = $conn->query($query);
            if (!$result) die(get_fatal_error_message());
            $exists = $result->num_rows == 1;
            $result->close();
            return $exists;
        }

        public function insert(mysqli $conn): bool
        {
            $stmt = $conn->prepare("INSERT INTO credentials VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sssss', $this->id, $this->name, $this->email, $this->hashed_password, $this->salt);
            $success = $stmt->execute();
            if (!$success) die(get_fatal_error_message());
            $rows = $stmt->affected_rows;
            $stmt->close();
            return $rows === 1;
        }

        public static function find(mysqli $conn, string $email): Credentials|null
        {
            $query = "SELECT * FROM credentials WHERE email = '$email'";
            $result = $conn->query($query);
            if (!$result) die(get_fatal_error_message());
            $creds = null;
            if ($result->num_rows === 1)
            {
                $result->data_seek(0);
                $row = $result->fetch_array(MYSQLI_ASSOC);
                $creds = new Credentials($row['name'], $row['password'], $row['email'], $row['id'], false, $row['salt']);
            } 
            $result->close();
            return $creds;
        }
    }
?>

<script>
    fail = validate

    function validateName(name)
    {
        if (name.length == 0) 
            return 'Invalid Name'
    }

    function validateID(id)
    {
        if (isNaN(id))
            return 'Invalid ID'
    }

    function validatePassword(password)
    {
        if (strlen(password) < 6) throw new Exception('Password length too small!');
        elseif (!preg_match('/[a-z]/', password) ||
                !preg_match('/[A-Z]/', password) ||
                !preg_match('/[0-9]/', password))
                throw new Exception('Passwords require 1 of each: lowercase character, uppercase character, and digit');        
    }

    function validateEmail(email)
    {
        if (!filter_var(email, FILTER_VALIDATE_EMAIL)) throw new Exception('Invalid Email!');
    }
</script>