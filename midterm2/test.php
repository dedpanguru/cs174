<?php
    require_once 'login.php';
    require_once 'credentials.php';

    $conn = new mysqli($db_hostname, $db_username, $db_password, $db_name);
    $creds = new Credentials("test", "test");
    $creds->insert($conn, $creds);
    $creds2 = Credentials::find($conn, "test");
    echo $creds2->get_id().",".$creds2->get_username().",".$creds2->get_password().",".$creds2->get_salt();
    $conn->close();
?>