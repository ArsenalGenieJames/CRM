<?php
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'crm_system';

try {
    // Create database if it doesn't exist
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    $pdo->exec("USE $dbname");
    
    // Read and execute the schema file
    $sql = file_get_contents(__DIR__ . '/schema.sql');
    $pdo->exec($sql);
    
    // Make PDO connection globally available
    global $pdo;
    
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check your configuration.");
}
?> 