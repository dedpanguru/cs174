<?php
    require_once 'credentials.php'; // contains the Credentials class, which manages all queries to the credentials table
    require_once 'helpers.php'; // contains the get_fatal_error_message function

    define('INSERT_NEW_FILE', 'INSERT INTO files VALUES (NULL, ?, ?, ?)');
    

    class File
    {
        private int $id, $uploader_id;
        private string $content_name, $filepath; 

        public function __construct(string $filename, int $uploader_id, string|null $contents, string|null $filepath, int $id = 0) 
        {
            $this->content_name = $filename;
            $this->uploader_id = $uploader_id;
            $this->filepath = (!empty($filepath)) ? $filepath : __DIR__.'/media/'.$filename;
            if (!empty($id)) $this->id = $id;
            echo "<br>$contents<br>";
            if(!empty($contents)) file_put_contents($this->filepath, $contents);
        }

        public function get_content_name(): string
        {
            return $this->content_name;
        }

        public function get_filepath(): string
        {
            return $this->filepath;
        }

        public function get_id(): string
        {
            return $this->id;
        }

        public function get_file_content(): array
        {
            $content = file_get_contents($this->filepath);
            echo "<br>$content<br>";
            $lines = preg_split("/\r\n|\n|\r/", $content);
            return [
                implode('<br>', array_slice($lines, 0, 3)), // first 3 lines
                implode('<br>', array_slice($lines, 3)), //  rest of content
            ];
        }

        public function insert(mysqli $conn): bool
        {
            $stmt = $conn->prepare(INSERT_NEW_FILE);
            $stmt->bind_param('sss', $this->uploader_id, $this->content_name, $this->filepath);
            if (!$stmt->execute()) die(get_fatal_error_message());
            $rows = $stmt->affected_rows;
            $stmt->close();
            return $rows === 1; // success is determined by if the number of affected rows was only 1
        }

        public static function find_all(mysqli $conn, string $uploader_id): array
        {
            // since 1 uploader can upload multiple files, there can be 1+ rows returned
            // thus accumulate File objects constructed from the returned data into an array and return it
            $files = [];
            // assemble and execute query
            $query = "SELECT * FROM files WHERE uploader_id = $uploader_id";
            $result = $conn->query($query);
            if (!$result) die(get_fatal_error_message());
            for ($row_num = 0; $row_num < $result->num_rows; $row_num++)
            {
                $result->data_seek($row_num);
                $row = $result->fetch_array(MYSQLI_ASSOC);
                array_push($files, new File($row['content_name'], $row['uploader_id'], null, $row['filepath'], $row['id']));
            }
            $result->close();
            return $files;
        }
    }

?>