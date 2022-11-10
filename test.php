<?php
    require_once 'login.php';
    require_once 'credentials.php';
    require_once 'files.php';

    $conn = new mysqli($db_hostname, $db_username, $db_password, $db_name);

    // creds test
    $creds = new Credentials("test", "test");
    $success = $creds->insert($conn, $creds);
    echo $success.','.$creds->get_id().",".$creds->get_username().",".$creds->get_password().",".$creds->get_salt()."<br>";
    $creds2 = Credentials::find($conn, "test");
    echo $creds2->get_id().",".$creds2->get_username().",".$creds2->get_password().",".$creds2->get_salt()."<br>";
    ($creds->compare_password("test")) ? print 't' : print 'f';
    echo '<br>';
    ($creds2->compare_password("test")) ? print 't' : print 'f';
    echo '<br>';

    // files test
    // $uploader_id = Credentials::get_id_from_username($conn, $creds->get_username());
    // if (!$uploader_id) die(get_fatal_error_message());
    // $file = new File("test.txt", $uploader_id, "1234\n1234\n1234\n1234\n543", null);
    // $file->insert($conn);
    // $files = File::find_all($conn, $uploader_id);
    // foreach ($files as $f)
    // {
    //     $content = $f->get_file_content();
    //     print_r($content);
    // }
    $conn->close();
?>