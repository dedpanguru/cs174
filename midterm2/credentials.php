<?php
    require_once 'helpers.php'; // contains the get_fatal_error_message function
    
    define('SALT_SIZE', 32);
    define('HASH_ALG', 'ripemd128');
    define('INSERT_NEW_CREDS', 'INSERT INTO credentials VALUES (NULL, ?, ?, ?)');

    class Credentials 
    {
        private string $username, $hashed_password, $salt;
        private int $id = 0;

        public function __construct(string $username, string $password, string $salt = "", int $id = 0) 
        {
            $this->username = $username;
            $this->set_password($password, $salt);
            if (!empty($id)) $this->id = $id;
        }

        public function set_password(string $password, string $salt = "")
        {
            $this->salt = (!empty($salt)) ? $salt : $this::generate_salt();
            $this->hashed_password = hash(HASH_ALG, $password.$this->salt);
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

        public function compare(string $input_password): bool
        {
            return $this->hashed_password === hash(HASH_ALG, $input_password.$this->salt);
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
            $id = $row['id'];
            $result->close();
            if (isset($id)) return $id;
            else return null;
        }

        public function insert(mysqli $conn): bool
        {
            $stmt = $conn->prepare(INSERT_NEW_CREDS);
            $stmt->bind_param('sss', $this->username, $this->hashed_password, $this->salt);
            $success = $stmt->execute();
            if (!$success) die(get_fatal_error_message());
            $rows = $stmt->num_rows();
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
                $creds = new Credentials($row['username'], $row['password'], $row['salt'], $row['id']);
            } 
            $result->close();
            return $creds;
        }
    }
?>