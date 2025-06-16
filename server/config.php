<?php
// config.php
$host = 'localhost';
$db   = 'cmsdb';
$user = 'root';
$pass = ''; // local dev, adjust if needed

$link = new mysqli($host, $user, $pass, $db);
if ($link->connect_error) {
    die(json_encode(["error" => "Database connection failed: " . $link->connect_error]));
}

// JWT settings
$key = "9f4b8e657bd5ef13c75f413a8d9a8a665b6a7f8a0dc2a93c3fbb7eebc3a2f1df";
$issuer = "http://localhost";
$audience = "http://localhost";
$issuedAt = time();
$expire = $issuedAt + (60 * 60); // 1 hour token
?>
