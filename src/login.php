<?php
    $db_hostname = "localhost";
    $db_username = "gurveer";
    $db_password = "gsingh";
    $db_name = "assignment6";
    /*
    CREATE TABLE IF NOT EXISTS credentials (
        id INT UNIQUE NOT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        password BINARY(32) NOT NULL,
        salt BINARY(32) NOT NULL,
        PRIMARY KEY (id)
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

        public function get_name(): string
        {
            return $this->name;
        }
        public function get_email(): string
        {
            return $this->email;
        }
        public function get_password(): string
        {
            return $this->hashed_password;
        }
        public function get_id(): int
        {
            return $this->id;
        }

        public static function get_prompt(string $section, string $pagename): string
        {
            $prompt = '';
            switch ($section){
                case 'Login':
                    $prompt = <<<_END
                    <pre><h2>$section</h2>
                    <form action="$pagename" method="post" enctype="multipart/form-data" id='$section'>
                        Enter Student ID: <input type="number" name="id" required>
                        
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
                    <form action="$pagename" method="post" enctype="multipart/form-data" id = '$section'>
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

        public static function find_from_id(mysqli $conn, string $id): Credentials|null
        {
            $query = "SELECT * FROM credentials WHERE id = '$id'";
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
    
    class Advisor
    {
        private string $name, $email, $phone_number;

        public function __construct(string $name, string $email, string $phone_number) 
        {
            $this->name = $name;
            $this->email = $email;
            $this->phone_number = $phone_number;
        }

        public static function get_advisors_from_id(mysqli $conn, int $id): array
        {
            $query = "SELECT name, email, phone_number FROM advisors WHERE lower_bound <= $id AND upper_bound > $id";
            $result = $conn->query($query);
            if(!$result) die(get_fatal_error_message());
            $advisors = [];
            for ($row_num = 0; $row_num < $result->num_rows; $row_num++)
            {
                $result->data_seek($row_num);
                $row = $result->fetch_array(MYSQLI_ASSOC);
                array_push($advisors, new Advisor($row['name'], $row['email'], $row['phone_number']));
            }
            $result->close();
            return $advisors;
        }

        public function get_html(): string
        {
            return <<<_END
            <section id='advisor-wrapper-$this->name'>
                <p>Name: $this->name</p>
                <p>Email: $this->email</p>
                <p>Telephone Number: $this->phone_number</p>  
            </section>
            _END;
        }
    }
?>