<?php
// auth.php
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: authentication/login.php");
    exit;
}

// Check if the user is an admin, if not then redirect or show an error
if($_SESSION["role"] !== "admin"){
    // Redirect to login with an error or a non-admin page
    header("location: index.php?error=not_admin");
    exit;
}
?>