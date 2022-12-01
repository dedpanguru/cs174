<?php
    require_once 'helpers.php'; // imports get_fatal_error_message()
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