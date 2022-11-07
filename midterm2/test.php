<?php
    require_once 'login.php';
    require_once 'credentials.php';
    require_once 'files.php';

    $conn = new mysqli($db_hostname, $db_username, $db_password, $db_name);

    // creds test
    $creds = new Credentials("test", "test");
    $creds->insert($conn, $creds);
    // $creds2 = Credentials::find($conn, "test");
    // echo $creds2->get_id().",".$creds2->get_username().",".$creds2->get_password().",".$creds2->get_salt();

    // files test
    $uploader_id = Credentials::get_id_from_username($conn, $creds->get_username());
    if (!$uploader_id) die(get_fatal_error_message());
    $file = new File("test.txt", $uploader_id, "1234\n1234\n1234\n1234\n543", null);
    $file->insert($conn);
    $files = File::find_all($conn, $uploader_id);
    foreach ($files as $f)
    {
        $content = $f->get_file_content();
        print_r($content);
    }
    $conn->close();
?>