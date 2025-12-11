<?php
/* This is used to connect to the database. Before connecting ensure that ur database is created 
and included in the /var/www/private/db-config.ini file in your Google VM Instance */

$config = parse_ini_file('/var/www/private/db-config.ini');

if (!$config) {
    die("Failed to read database config file.");
}

try {
    $conn = new mysqli(
        $config['servername'],
        $config['username'],
        $config['password'],
        $config['dbname']
    );

    // Check if the connection failed
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    exit("Database connection failed. Please try again later.");
}
?>
