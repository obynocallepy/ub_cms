<?php
$host = getenv('RAILWAY_MYSQL_HOST'); // Hostname
$username = getenv('RAILWAY_MYSQL_USER'); // Database username
$password = getenv('RAILWAY_MYSQL_PASSWORD'); // Database password
$database = getenv('RAILWAY_MYSQL_DATABASE'); // Database name

// Create connection
$link = new mysqli($host, $username, $password, $database);

// Check connection
if ($link->connect_error) {
    die("Connection failed: " . $link->connect_error);
}
?>