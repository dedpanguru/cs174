<?php
    require_once 'login.php'; // needs $db_hostname, $db_username, $db_password, $db_name, get_fatal_error_message(), and force_logout()

    // DRIVER CODE STARTS HERE
    session_start();
    if (!isset($_SESSION['check'])) force_logout('second_page.php');
    if ($_SESSION['check'] != hash('ripemd128', $_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT'])) force_logout('second_page.php');

    // attempt database connection
    try
    {
        $conn = new mysqli($db_hostname, $db_username, $db_password, $db_name);
    }
    catch (Exception $e)
    {
        die(get_fatal_error_message());
    }

    $advisors = [];
    // handle any POST requests
    if (isset($_POST))
    {
        // handle logout
        if (isset($_POST['logout']) && $_POST['logout'] === 'true') force_logout('second_page.php');
        // handle advisor search
        elseif (isset($_POST['name']) && isset($_POST['id']))
        {
            $input_name = sanitize($conn, $_POST['name']);
            $input_id = intval(sanitize($conn, $_POST['id']));
            $advisors = Advisor::get_advisors_from_id($conn, $input_id);
        }
    }
    echo <<<_END
    <html>
        <head>
            <title>First Page</title>
        <head>
        <body>
            <h1>Find your Advisor</h1>
            <form action='first_page.php' method='post' enctype='multipart/form-data'>
                <input type='hidden' name='logout' value='true'>
                <input type='submit' value='Logout'>
            </form>
            <section id='advisor-form'>
                <form action='first_page.php' method='post' enctype='multipart/form-data'>
                    <label for='name-input'>Enter Full name: </label><br>
                    <input type='text' name='name' id='name-input' required><br>
                    <br>
                    <label for='id-input'>Enter Student ID: </label><br>
                    <input type='number' name='id' id='id-input' required><br>
                    <br>
                    <input type='submit' value='Submit'>
                </form>
            </section>
    _END;
    
    // if an advisor was found, display their info
    if (empty($advisors)) echo '<h2>No advisors found!</h2>';
    else 
    {
        echo '<h2><u>Advisors found:</u></h2>';
        foreach ($advisors as $advisor) echo $advisor->get_html();
    }

    // close html code
    echo '</body></html>';

    // close db connection
    $conn->close();

    // CLASS DEFINITIONS START HERE
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
            $query = "SELECT name, email, phone_number FROM advisors WHERE lower_bound <= $id AND upper_bound >= $id";
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