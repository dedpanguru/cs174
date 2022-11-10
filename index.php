<?php
    require_once 'login.php'; // contains db_hostname, db_username, db_password, db_name variables needed to make database connection
    require_once 'helpers.php'; // contains get_fatal_error_message, different_user, redirect, split_after_3_lines, and sanitize functions
    require_once 'files.php'; // contains the File class, which will be used to manage all queries regarding the 'files' table in the database
    require_once 'credentials.php'; // contains the Credentials class, which will be used to manage all queries regarding the 'credentials' table in the database

    session_start();

    if (!isset($_SESSION['check']) || !isset($_SESSION['username'])) different_user('./auth.php'); // ensure necessary session info exists
    // enforce packet sniffing security as ip + user agent that logged in should be the same one that is accessing this file upload app
    if (($_SESSION['check'] != hash('ripemd128', $_SERVER['REMOTE_ADDR'].$_SERVER['HTTP_USER_AGENT']))) different_user('./auth.php');

    $username = $_SESSION['username']; 
    echo "<h1>Hello $username</h1>";

    // attempt database connection
    try
    {
        $conn = new mysqli($db_hostname, $db_username, $db_password, $db_name);
    }
    catch (Exception $e)
    {
        die(get_fatal_error_message());
    }
    
    // handle logout
    if(isset($_POST['logout']) && $_POST['logout'] === 'true') different_user('./auth.php');
    
    // prompt logout
    echo <<<_END
    <form action='index.php' method='post' enctype='multipart/form-data'>
        <input type='hidden' name='logout' value='true'>
        <input type='submit' value='Logout'>
    </form>
    _END;
    
    // resolve id from username
    $id = Credentials::get_id_from_username($conn, $username); 
    if (!$id) die(get_fatal_error_message());

    // handle file submission
    if (isset($_FILES['upload']))
    {
        // enforce txt mimetype
        if ($_FILES["upload"]["type"] === "text/plain")
        {
            if (isset($_POST['contentName']))
            {
                $content_name = sanitize($conn, $_POST['contentName']);
                $file_contents = sanitize($conn, file_get_contents($_FILES['upload']['tmp_name']));
                $file = new File($content_name, $id, $file_contents, null);
                $success = $file->insert($conn);
                ($success) ? print '<h1 style="color:green">Success! See your file below!</h1>' : die(get_fatal_error_message());
            }
        }
        else echo '<h1 style="color:red">Unsupported file extension!</h1>';
    }

    // display file submission prompt
    echo File::get_prompt('index.php');

    // display all the files the user uploaded
    echo '<h1><u>My Files</u></h1>';
    $files = File::find_all($conn, $id); // grab all the files from the database
    for($i = 0; $i<count($files); $i++)
    {
        $file = $files[$i];
        // get the content name
        $content_name = $file->get_content_name();
        echo "<br><u>Content Name: $content_name</u><br>";
        // get the content
        [$first, $hide] = $file->get_file_content();
        // prepare the content to be displayed
        $file_html = $first;
        if (!empty($hide))
        {
            $file_html .= "<div id='$i' style='display:none'>$hide</div><br><button id='$i-btn' onclick='revealContent($i)' style='display:inline'>See more</button>";
        }
        echo "Content: <br>$file_html<br>";
    }

    echo <<<_END
    <script>
    function revealContent(id){
        let btn = document.getElementById(`\${id}-btn`);
        btn.style.display='none';
        let content = document.getElementById(`\${id}`); 
        content.style.display='block';
    };
    </script>
    </body>
    </html>
    _END;
    $conn->close();
?>