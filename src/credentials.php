<?php
    require_once './helpers.php'; // imports get_fatal_error_message()

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

        private static function get_all_salts(mysqli $conn)
        {
            $salts = [];
            $query = "SELECT salt FROM credentials";
            $result = $conn->query($query);
            if (!$result) die(get_fatal_error_message());
            if ($result->num_rows > 0)
            {
                for ($row_num = 0; $row_num < $result->num_rows; $row_num++)
                {
                    $result->data_seek($row_num);
                    $row = $result->fetch_array(MYSQLI_ASSOC);
                    array_push($salts, $row['salt']);
                }
            }
            $result->close();
            return $salts;
        }

        // generates a salt that is not found in the database
        public static function generate_unique_salt(mysqli $conn)
        {
            $salt = Credentials::generate_salt();
            $salts = Credentials::get_all_salts($conn);
            while (in_array($salt, $salts)) $salt = Credentials::generate_salt();
            return $salt;
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
?>