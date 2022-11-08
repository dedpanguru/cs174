<?php
    session_start();
    if (isset($_SESSION['username']))
    {
        echo "Hello user!";
    }
    else {
        header('Location: ./auth.php');
        die();
    }
?>