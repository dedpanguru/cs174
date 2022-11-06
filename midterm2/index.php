<?php
    if (isset($_SESSION['username']))
    {

    }
    else {
        header('Location: http://localhost/auth.php');
        die();
    }
?>