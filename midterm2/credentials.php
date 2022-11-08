<?php
    require_once 'helpers.php'; // contains the get_fatal_error_message function
    
    define('SALT_SIZE', 32);
    define('HASH_ALG', 'ripemd128');
    define('INSERT_NEW_CREDS', 'INSERT INTO credentials VALUES (NULL, ?, ?, ?)');

    class Credentials 
    {
        private string $username, $hashed_password, $salt;
        private int $id = 0;

        public function __construct(string $username, string $password, bool $auto_hash = true, string $salt = "", int $id = 0) 
        {
            $this->username = $username;
            $this->hashed_password = $password;
            $this->salt = (empty($salt)) ? $this::generate_salt() : $salt;
            if ($auto_hash) $this->hash_password();
            if (!empty($id)) $this->id = $id;
        }

        public function hash_password()
        {
            $this->hashed_password = hash(HASH_ALG, $this->hashed_password.$this->salt);
        }

        public function get_id()
        {
            return $this->id;
        }

        public function get_password()
        {
            return $this->hashed_password;
        }

        public function get_username()
        {
            return $this->username;
        }
        public function get_salt()
        {
            return $this->salt;
        }

        public function compare_password(string $input_password): bool
        {
            return $this->hashed_password === hash(HASH_ALG, $input_password.$this->salt);
        }

        public static function get_prompt(string $section, string $pagename): string
        {
            $prompt = '';
            switch ($section){
                case 'Login':
                case 'Register':
                    $prompt = <<<_END
                    <pre><h2>$section</h2>
                    <form action="$pagename" method="post" enctype="multipart/form-data">
                        Enter Username: <input type="text" name="username" required>
                    
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
            $max = mb_strlen($possible_chars, '8bit') -1;
            for ($i = 0; $i < SALT_SIZE; $i++)
            {
                $pieces []= $possible_chars[random_int(0, $max)];
            }
            return implode('', $pieces);
        }

        public static function get_id_from_username(mysqli $conn, string $username): int | null
        {
            $query = "SELECT id FROM credentials WHERE username = '$username'";
            $result = $conn->query($query);
            if (!$result) die(get_fatal_error_message());
            $result->data_seek(0);
            $row = $result->fetch_array(MYSQLI_ASSOC);
            $id = (isset($row['id'])) ? $row['id'] : null;
            $result->close();
            return $id;
        }

        public function insert(mysqli $conn): bool
        {
            $stmt = $conn->prepare(INSERT_NEW_CREDS);
            $stmt->bind_param('sss', $this->username, $this->hashed_password, $this->salt);
            $success = $stmt->execute();
            if (!$success) die(get_fatal_error_message());
            $rows = $stmt->affected_rows;
            $stmt->close();
            return $rows === 1;
        }

        public static function find(mysqli $conn, string $username): Credentials|null
        {
            $query = "SELECT * FROM credentials WHERE username = '$username'";
            $result = $conn->query($query);
            if (!$result) die(get_fatal_error_message());
            $creds = null;
            if ($result->num_rows === 1)
            {
                $result->data_seek(0);
                $row = $result->fetch_array(MYSQLI_ASSOC);
                $creds = new Credentials($row['username'], $row['password'], false, $row['salt'], $row['id']);
            } 
            $result->close();
            return $creds;
        }
    }
?>