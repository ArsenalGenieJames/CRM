<?php
$host = 'localhost';
$username = 'root';
$password = '';  // XAMPP default has no password
$dbname = 'crm_system';

try {
    // First try to connect to MySQL server
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Read and execute the schema file
    $schemaFile = __DIR__ . '/schema.sql';
    if (file_exists($schemaFile)) {
        $sql = file_get_contents($schemaFile);
        if (!empty($sql)) {
            try {
                // Split SQL into individual statements
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                
                // Execute each statement separately
                foreach ($statements as $statement) {
                    if (!empty($statement)) {
                        try {
                            $pdo->exec($statement);
                        } catch (PDOException $e) {
                            // Log the error but continue with other statements
                            error_log("Error executing statement: " . $e->getMessage());
                            error_log("Statement: " . $statement);
                        }
                    }
                }
            } catch (PDOException $e) {
                error_log("Error executing schema: " . $e->getMessage());
            }
        } else {
            error_log("Schema file is empty");
        }
    } else {
        error_log("Schema file not found at: " . $schemaFile);
    }
    
    // Make PDO connection globally available
    global $pdo;
    
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please check your configuration. Error: " . $e->getMessage());
}
?> 